#!/usr/bin/env python3
"""Audyt indeksacji przez GSC URL Inspection API (quota 2000/dobę, osobna od Indexing API).

Wejście: plik z pełnymi URL-ami (jeden na linię, puste i '#' pomijane).
Wyjście: tabela na stdout + JSON (opcjonalny 2. argument).

    python3 scripts/gsc-inspect.py urls.txt wynik.json

GSC Inspection jest wolne (~0,4-9 s/URL przez throttling) — dla >100 URL-i podziel plik
(`split -n l/8`) i odpal równolegle; limit 600/min/property daje zapas.

Odtworzone 2026-07-30 (poprzednia kopia tmp/gsc-inspect.py przepadła w czystce tmp 14.07 —
dlatego teraz mieszka w scripts/, nie w tmp/).
"""
import json, sys, time, urllib.parse, urllib.request
from pathlib import Path

SECRETS = Path.home() / "secrets" / "google"
SITE = "https://primaauto.com.pl/"


def token():
    o = json.loads((SECRETS / "oauth-desktop-client.json").read_text())["installed"]
    t = json.loads((SECRETS / "tokens.json").read_text())
    data = urllib.parse.urlencode({
        "client_id": o["client_id"], "client_secret": o["client_secret"],
        "refresh_token": t["refresh_token"], "grant_type": "refresh_token",
    }).encode()
    return json.load(urllib.request.urlopen(
        urllib.request.Request("https://oauth2.googleapis.com/token", data=data)))["access_token"]


def inspect(tok, url):
    req = urllib.request.Request(
        "https://searchconsole.googleapis.com/v1/urlInspection/index:inspect",
        data=json.dumps({"inspectionUrl": url, "siteUrl": SITE, "languageCode": "pl"}).encode(),
        headers={"Authorization": f"Bearer {tok}", "Content-Type": "application/json"})
    try:
        r = json.load(urllib.request.urlopen(req))["inspectionResult"]["indexStatusResult"]
        return {
            "verdict": r.get("verdict"),
            "coverage": r.get("coverageState"),
            "lastCrawl": (r.get("lastCrawlTime") or "")[:10],
            "canonical": r.get("googleCanonical", ""),
            "robots": r.get("robotsTxtState"),
        }
    except urllib.error.HTTPError as e:
        return {"verdict": "ERROR", "coverage": f"HTTP {e.code}", "lastCrawl": "", "canonical": "", "robots": ""}


def main():
    urls = [l.strip() for l in Path(sys.argv[1]).read_text().splitlines()
            if l.strip() and not l.startswith("#")]
    tok = token()
    out = []
    for url in urls:
        r = inspect(tok, url)
        r["url"] = url
        out.append(r)
        idx = "OK " if r["verdict"] == "PASS" else "NIE"
        print(f"{idx} {(r['coverage'] or '—'):<44} crawl={r['lastCrawl'] or '—':<11} {url}", flush=True)
        time.sleep(0.35)
    if len(sys.argv) > 2:
        Path(sys.argv[2]).write_text(json.dumps(out, ensure_ascii=False, indent=1))
    ok = sum(1 for r in out if r["verdict"] == "PASS")
    print(f"\nZaindeksowane: {ok}/{len(out)}")


if __name__ == "__main__":
    main()
