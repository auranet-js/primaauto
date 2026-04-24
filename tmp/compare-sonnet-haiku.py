#!/usr/bin/env python3
"""
Porównuje output Sonnet 4.6 vs Haiku 4.5 na tym samym promptcie/dane
dla generowania hub-content marki w primaauto.
Użycie: python3 compare-sonnet-haiku.py <make_slug>
"""
import json
import sys
import urllib.request
from pathlib import Path

make_slug = sys.argv[1] if len(sys.argv) > 1 else "gac"

ANTHROPIC_KEY = Path.home().joinpath("secrets/anthropic/api-key.txt").read_text().strip()
SYSTEM_PROMPT = Path("/tmp/sample_make_system.txt").read_text()

# Fetch facts + aliases + latest for the make
WP_BASE = "https://primaauto.com.pl/wp-json/asiaauto/v1"

def get(url):
    req = urllib.request.Request(url)
    with urllib.request.urlopen(req, timeout=30) as r:
        return json.loads(r.read())

facts = get(f"{WP_BASE}/facts-for-make/{make_slug}")
latest = get(f"{WP_BASE}/latest-by-make/{make_slug}?n=6")
aliases = get(f"{WP_BASE}/aliases-for-make/{make_slug}")

data = {
    "make_slug": facts.get("make_slug"),
    "make_name": facts.get("make_name"),
    "total_listings": facts.get("total_listings"),
    "models_count": facts.get("models_count"),
    "top3_models": facts.get("top3_models"),
    "price_range_pln": facts.get("price_range_pln"),
    "year_range": facts.get("year_range"),
    "segment_eu": facts.get("segment_eu"),
    "has_aliases": bool(aliases and aliases.get("has_aliases")),
    "aliases": aliases.get("groups", []) if aliases else [],
    "latest_titles": [l.get("title") for l in (latest or [])][:6],
}
user_msg = f"DATA:\n{json.dumps(data, indent=2, ensure_ascii=False)}\n\nGenerate hub content per instructions."

def call(model_id, label):
    body = {
        "model": model_id,
        "max_tokens": 8000,
        "system": SYSTEM_PROMPT,
        "messages": [{"role": "user", "content": user_msg}],
    }
    req = urllib.request.Request(
        "https://api.anthropic.com/v1/messages",
        data=json.dumps(body).encode("utf-8"),
        headers={
            "x-api-key": ANTHROPIC_KEY,
            "anthropic-version": "2023-06-01",
            "content-type": "application/json",
        },
    )
    try:
        with urllib.request.urlopen(req, timeout=120) as r:
            resp = json.loads(r.read())
    except urllib.error.HTTPError as e:
        err_body = e.read().decode("utf-8", errors="replace")
        print(f"  HTTP {e.code}: {err_body[:400]}")
        raise
    usage = resp.get("usage", {})
    text = (resp.get("content") or [{}])[0].get("text", "")
    Path(f"/tmp/hub-{make_slug}-{label}.json").write_text(
        json.dumps(resp, ensure_ascii=False, indent=2)
    )
    Path(f"/tmp/hub-{make_slug}-{label}.txt").write_text(text)
    return usage, text

print(f"=== {make_slug} :: {data['make_name']} :: {data['total_listings']} listingów ===\n")

print("--- SONNET 4.6 ---")
s_usage, s_text = call("claude-sonnet-4-5", "sonnet")
s_cost = (s_usage["input_tokens"] * 3 + s_usage["output_tokens"] * 15) / 1_000_000
print(f"  in={s_usage['input_tokens']} out={s_usage['output_tokens']} cost=${s_cost:.4f}")
print(f"  wiki_len={len(s_text)} chars")

print("\n--- HAIKU 4.5 ---")
h_usage, h_text = call("claude-haiku-4-5", "haiku")
h_cost = (h_usage["input_tokens"] * 1 + h_usage["output_tokens"] * 5) / 1_000_000
print(f"  in={h_usage['input_tokens']} out={h_usage['output_tokens']} cost=${h_cost:.4f}")
print(f"  wiki_len={len(h_text)} chars")

print(f"\n=== Cost ratio Haiku/Sonnet: {h_cost/s_cost:.2f}× ===")
print(f"=== Estimated 53 hubs: Sonnet ${s_cost*53:.2f} ({s_cost*53*0.92:.2f}€) vs Haiku ${h_cost*53:.2f} ({h_cost*53*0.92:.2f}€) ===")
print(f"\nOutputs saved: /tmp/hub-{make_slug}-{{sonnet,haiku}}.{{json,txt}}")
