#!/usr/bin/env python3
"""
Biblioteka wspólna działu wiedzy (T-214): Anthropic API, HTTP, lint, WP-CLI.
Wzorzec przeniesiony z hub-wiki-generate.py (odzyskany z backupu 2026-07-14).
"""
import html
import json
import re
import subprocess
import time
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path("/home/host476470/projekty/primaauto")
KB_DIR = ROOT / "scripts" / "kb"
STATE_DIR = KB_DIR / "state"
SECRETS = Path.home() / "secrets"
WP_PATH = "/home/host476470/domains/primaauto.com.pl/public_html"

ANTHROPIC_KEY = (SECRETS / "anthropic/api-key.txt").read_text().strip()
MODEL = "claude-sonnet-4-5"
UA = "Mozilla/5.0 (X11; Linux x86_64) PrimaAutoKB/1.0 (+https://primaauto.com.pl/informacje/o-redakcji/)"

FORBIDDEN_PHRASES = [
    "Warto wspomnieć", "Bez wątpienia", "W dzisiejszym dynamicznym świecie",
    "Niezrównana jakość", "game-changer", "rewolucyjn", "przełomow",
    "innowacyjne rozwiązania", "nieszablonow", "Podsumowując",
]

# Smart quotes psują JSON i spójność redakcyjną (feedback_smart_quotes_break_json)
SMART_QUOTES = {"“": '"', "”": '"', "„": '"', "’": "'", "‘": "'"}


def http_get(url, timeout=30, as_text=False):
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=timeout) as r:
        raw = r.read()
    if as_text:
        return raw.decode("utf-8", errors="replace")
    return json.loads(raw)


def call_anthropic(system_prompt, user_msg, max_tokens=6000, retries=2):
    """Zwraca (text, usage). Prompt caching na system prompt."""
    body = {
        "model": MODEL,
        "max_tokens": max_tokens,
        "system": [{"type": "text", "text": system_prompt, "cache_control": {"type": "ephemeral"}}],
        "messages": [{"role": "user", "content": user_msg}],
    }
    for attempt in range(retries + 1):
        req = urllib.request.Request(
            "https://api.anthropic.com/v1/messages",
            data=json.dumps(body).encode("utf-8"),
            headers={
                "x-api-key": ANTHROPIC_KEY,
                "anthropic-version": "2023-06-01",
                "content-type": "application/json",
            },
            method="POST",
        )
        try:
            with urllib.request.urlopen(req, timeout=240) as r:
                resp = json.loads(r.read())
                text = (resp.get("content") or [{}])[0].get("text", "")
                return text, resp.get("usage", {})
        except urllib.error.HTTPError as e:
            err = e.read().decode("utf-8", errors="replace")[:400]
            if attempt < retries and e.code in (429, 500, 502, 503, 529):
                time.sleep((attempt + 1) * 15)
                continue
            raise RuntimeError(f"Anthropic HTTP {e.code}: {err}")


def cost_usd(usage):
    return (usage.get("input_tokens", 0) * 3
            + usage.get("output_tokens", 0) * 15
            + usage.get("cache_read_input_tokens", 0) * 0.30
            + usage.get("cache_creation_input_tokens", 0) * 3.75) / 1_000_000


def parse_json_response(text):
    s = text.strip()
    if s.startswith("```"):
        s = re.sub(r"^```(?:json)?\s*", "", s)
        s = re.sub(r"\s*```\s*$", "", s)
    return json.loads(s)


def normalize_quotes(s):
    for k, v in SMART_QUOTES.items():
        s = s.replace(k, v)
    return s


def lint_text(text):
    """Anty-slop lint. Zwraca listę problemów (pusta = OK)."""
    issues = []
    for phrase in FORBIDDEN_PHRASES:
        if phrase.lower() in text.lower():
            issues.append(f"FORBIDDEN: '{phrase}'")
    return issues


def strip_html(s):
    s = re.sub(r"<script[^>]*>.*?</script>", " ", s, flags=re.S | re.I)
    s = re.sub(r"<style[^>]*>.*?</style>", " ", s, flags=re.S | re.I)
    s = re.sub(r"<[^>]+>", " ", s)
    s = html.unescape(s)
    return re.sub(r"\s+", " ", s).strip()


def wp(*args, stdin=None):
    """WP-CLI w katalogu produkcji. Zwraca stdout, rzuca przy błędzie."""
    result = subprocess.run(
        ["/bin/bash", "-lc", "cd %s && wp %s" % (WP_PATH, " ".join(a.replace("'", "'\\''") for a in args))],
        capture_output=True, text=True, input=stdin,
    )
    if result.returncode != 0:
        raise RuntimeError(f"wp {' '.join(args[:3])}... failed: {result.stderr[:400]}")
    return result.stdout.strip()


def load_state(name, default):
    f = STATE_DIR / name
    if f.exists():
        return json.loads(f.read_text())
    return default


def save_state(name, data):
    STATE_DIR.mkdir(parents=True, exist_ok=True)
    (STATE_DIR / name).write_text(json.dumps(data, ensure_ascii=False, indent=1))


def send_mail(subject, html_body):
    """Mail do Janka przez autoryzowany kanał send-to-jan."""
    tmp = STATE_DIR / "_mail_body.html"
    STATE_DIR.mkdir(parents=True, exist_ok=True)
    tmp.write_text(html_body)
    result = subprocess.run(
        ["/home/host476470/bin/send-to-jan", "-s", subject, "--html", str(tmp)],
        capture_output=True, text=True,
    )
    if result.returncode != 0:
        raise RuntimeError(f"send-to-jan failed: {result.stderr[:300]}")
