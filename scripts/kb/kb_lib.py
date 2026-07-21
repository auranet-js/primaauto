#!/usr/bin/env python3
"""
Biblioteka wspólna działu wiedzy (T-214): generowanie przez `claude -p`
(headless Claude Code W RAMACH ABONAMENTU — zero kosztów API; decyzja Janka
2026-07-21: nie używamy Anthropic API), HTTP, lint, WP-CLI, mail.
"""
import html
import json
import re
import shlex
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

MODEL = "sonnet"  # alias modelu dla claude -p (abonament)
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


def call_model(system_prompt, user_msg, max_tokens=None, retries=1):
    """Generowanie przez `claude -p` (headless, abonament). Zwraca (text, usage)."""
    prompt = f"INSTRUKCJA SYSTEMOWA (stosuj bezwzględnie):\n{system_prompt}\n\n{'=' * 30}\n\n{user_msg}"
    for attempt in range(retries + 1):
        result = subprocess.run(
            ["/bin/bash", "-lc", f"claude -p --model {MODEL} --output-format json"],
            input=prompt, capture_output=True, text=True, timeout=600,
        )
        try:
            data = json.loads(result.stdout)
        except json.JSONDecodeError:
            data = None
        if data and not data.get("is_error") and data.get("result"):
            return data["result"], data.get("usage", {})
        err = (data or {}).get("result") or result.stderr[:300] or result.stdout[:300]
        if attempt < retries:
            time.sleep(30)
            continue
        raise RuntimeError(f"claude -p error: {err}")


def parse_json_response(text):
    s = text.strip()
    if s.startswith("```"):
        s = re.sub(r"^```(?:json)?\s*", "", s)
        s = re.sub(r"\s*```\s*$", "", s)
    try:
        return json.loads(s)
    except json.JSONDecodeError:
        # Model czasem owija JSON prozą — wytnij od pierwszego '{' do ostatniego '}'
        start, end = s.find("{"), s.rfind("}")
        if start >= 0 and end > start:
            return json.loads(s[start:end + 1])
        raise


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
        ["/bin/bash", "-lc", "cd %s && wp %s" % (WP_PATH, " ".join(shlex.quote(a) for a in args))],
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


PROOF_PROMPT = """Jesteś korektorem wydawniczym polskiego serwisu motoryzacyjnego. Dostajesz pola tekstowe artykułu (JSON).
Popraw WYŁĄCZNIE: literówki, ortografię, gramatykę, interpunkcję, kalki z angielskiego, niespójne jednostki (km, kW, KM, kWh), podwójne spacje. NIE zmieniaj sensu, struktury HTML, liczb ani stylu.
Zwróć czysty JSON: {"fields": {<te same klucze z poprawionym tekstem>}, "changes": ["krótki opis każdej poprawki", ...]} — jeśli nic do poprawy, "changes": []."""


def proofread(fields: dict):
    """Korekta wydawnicza pól tekstowych. Zwraca (poprawione_pola, lista_zmian)."""
    text, _ = call_model(PROOF_PROMPT, json.dumps(fields, ensure_ascii=False), max_tokens=6000)
    data = parse_json_response(text)
    fixed = data.get("fields") or {}
    # Bezpiecznik: korekta nie może zgubić pola ani drastycznie skrócić treści
    out = {}
    for k, v in fields.items():
        new = fixed.get(k)
        out[k] = new if (isinstance(new, str) and len(new) >= 0.7 * len(v)) else v
    return out, data.get("changes", [])


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
