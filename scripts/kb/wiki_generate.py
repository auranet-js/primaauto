#!/usr/bin/env python3
"""
Generator haseł Leksykonu (T-214 F2). Wejście: wiki_tier1.json (konfiguracja
haseł: tytuł, slug, kategoria, aliasy, klucze extra_prep, kontekst faktograficzny).
Generowanie przez claude -p (abonament), draft CPT asiaauto_wiki + meta,
mail zbiorczy do Janka z tokenowymi linkami "Opublikuj".

Użycie:
  python3 wiki_generate.py --limit 10            # pierwsze N z konfiguracji (pomija istniejące slugi)
  python3 wiki_generate.py --slugs silnik-psm    # konkretne
  python3 wiki_generate.py --no-mail             # bez maila (debug)
"""
import argparse
import json
import secrets as pysecrets
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb

SITE = "https://primaauto.com.pl"


def existing_slugs():
    out = kb.wp("post", "list", "--post_type=asiaauto_wiki", "--post_status=any",
                "--field=post_name", "--posts_per_page=-1")
    return set(s for s in out.split("\n") if s)


def build_entry(cfg, system_prompt):
    user_msg = (f"HASŁO: {cfg['title']}\n"
                f"KONTEKST Z NASZEJ BAZY (fakty do wykorzystania, nie cytuj dosłownie):\n{cfg['context']}\n\n"
                "Napisz hasło zgodnie z instrukcją systemową. Zwróć czysty JSON.")
    text, _ = kb.call_model(system_prompt, user_msg)
    entry = kb.parse_json_response(kb.normalize_quotes(text))

    full_text = entry["definition"] + " " + entry["body_html"] + " " + json.dumps(entry["faq"], ensure_ascii=False)
    lint = kb.lint_text(full_text)
    if lint:
        raise RuntimeError("Lint: " + "; ".join(lint))
    if len(entry.get("faq", [])) < 3:
        raise RuntimeError("Za mało pytań FAQ")
    if len(entry["body_html"]) < 1200:
        raise RuntimeError(f"body_html za krótkie ({len(entry['body_html'])})")
    return entry


def create_wiki_draft(cfg, entry):
    faq_html = "".join(
        f"<details><summary>{f['q']}</summary><p>{f['a']}</p></details>"
        for f in entry["faq"]
    )
    content = (f"<p class=\"wiki-definition\"><strong>{entry['definition']}</strong></p>\n"
               + entry["body_html"]
               + f"\n<h2>Najczęstsze pytania</h2>\n{faq_html}")
    body_file = kb.STATE_DIR / "_wiki_body.html"
    kb.STATE_DIR.mkdir(parents=True, exist_ok=True)
    body_file.write_text(content)
    post_id = kb.wp(
        "post", "create", str(body_file),
        "--post_status=draft", "--post_type=asiaauto_wiki",
        "--post_author=55",
        f"--post_title={cfg['title']}",
        f"--post_name={cfg['slug']}",
        f"--post_excerpt={entry['excerpt']}",
        "--porcelain",
    )
    kb.wp("post", "meta", "set", post_id, "_wiki_category", cfg["category"])
    kb.wp("post", "meta", "set", post_id, "_wiki_aliases", cfg["aliases"])
    kb.wp("post", "meta", "set", post_id, "_wiki_term_keys", json.dumps(cfg["term_keys"], ensure_ascii=False))
    kb.wp("post", "meta", "set", post_id, "_kb_faq_json", json.dumps(entry["faq"], ensure_ascii=False))
    token = pysecrets.token_hex(20)
    kb.wp("post", "meta", "set", post_id, "_kb_publish_token", token)
    publish_url = f"{SITE}/wp-json/asiaauto/v1/kb-publish?post={post_id}&token={token}"
    return post_id, publish_url


def build_mail(results, failed):
    rows = []
    for r in results:
        e = r["entry"]
        faq_html = "".join(f"<p style='margin:4px 0'><b>{f['q']}</b><br>{f['a']}</p>" for f in e["faq"])
        rows.append(f"""
<div style="border:1px solid #ddd;border-radius:8px;padding:16px;margin:16px 0">
  <p style="margin:0 0 4px;color:#888;font-size:12px">Leksykon · {r['cfg']['category']} · hasło #{r['post_id']}</p>
  <h2 style="margin:0 0 8px;font-size:19px">{r['cfg']['title']}</h2>
  <p style="font-weight:bold">{e['definition']}</p>
  {e['body_html']}
  <h3>FAQ</h3>{faq_html}
  <p style="margin:14px 0 0"><a href="{r['publish_url']}" style="background:#1B2A4A;color:#fff;padding:10px 22px;border-radius:6px;text-decoration:none;font-weight:bold">✅ OPUBLIKUJ hasło</a></p>
</div>""")
    fail_html = ("<p style='color:#a00'>Nieudane: " + "; ".join(failed) + "</p>") if failed else ""
    return ("<p>Hasła Leksykonu do akceptacji. Klik = publikacja pod /wiki/.</p>"
            + "".join(rows) + fail_html)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--limit", type=int, default=10)
    ap.add_argument("--slugs", help="comma-separated")
    ap.add_argument("--no-mail", action="store_true")
    args = ap.parse_args()

    cfgs = json.loads((kb.KB_DIR / "wiki_tier1.json").read_text())
    done = existing_slugs()
    if args.slugs:
        want = set(args.slugs.split(","))
        cfgs = [c for c in cfgs if c["slug"] in want]
    else:
        cfgs = [c for c in cfgs if c["slug"] not in done][:args.limit]

    if not cfgs:
        print("Nic do wygenerowania (wszystkie slugi istnieją).")
        return

    system_prompt = (kb.KB_DIR / "prompts" / "wiki_system.txt").read_text()
    results, failed = [], []
    for i, cfg in enumerate(cfgs, 1):
        print(f"[{i}/{len(cfgs)}] {cfg['slug']}", flush=True)
        try:
            entry = build_entry(cfg, system_prompt)
            post_id, publish_url = create_wiki_draft(cfg, entry)
            results.append({"cfg": cfg, "entry": entry, "post_id": post_id, "publish_url": publish_url})
            print(f"    OK draft #{post_id} ({len(entry['body_html'])}c, FAQ {len(entry['faq'])})", flush=True)
        except Exception as e:
            failed.append(f"{cfg['slug']}: {e}")
            print(f"    FAIL: {e}", flush=True)

    print(f"\nGotowe: {len(results)} OK, {len(failed)} błędów.")
    if results and not args.no_mail:
        kb.send_mail(f"[primaauto] Leksykon: {len(results)} haseł do akceptacji",
                     build_mail(results, failed))
        print("Mail wysłany.")


if __name__ == "__main__":
    main()
