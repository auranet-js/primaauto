#!/usr/bin/env python3
"""GSC recon per hub — reusable. Usage: gsc-hub.py <hub_path> <seed1> [seed2...]
   hub_path np. samochody/xiaomi/su7  (bez wiodącego/końcowego /)."""
import json, sys, urllib.parse, urllib.request, datetime as dt
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

def q(tok, body):
    url = f"https://searchconsole.googleapis.com/webmasters/v3/sites/{urllib.parse.quote_plus(SITE)}/searchAnalytics/query"
    req = urllib.request.Request(url, data=json.dumps(body).encode(),
        headers={"Authorization": f"Bearer {tok}", "Content-Type": "application/json"})
    return json.load(urllib.request.urlopen(req))

def main():
    hub_path = sys.argv[1].strip("/")
    seeds = sys.argv[2:]
    hub_url = f"{SITE}{hub_path}/"
    tok = token()
    end = dt.date.today() - dt.timedelta(days=3)
    s1, e1 = end - dt.timedelta(days=27), end

    # 1. frazy site-wide po seedach
    if seeds:
        merged = {}
        for kw in seeds:
            rows = q(tok, {"startDate": s1.isoformat(), "endDate": e1.isoformat(),
                "dimensions": ["query"], "rowLimit": 200,
                "dimensionFilterGroups": [{"filters": [
                    {"dimension": "query", "operator": "contains", "expression": kw}]}]}).get("rows", [])
            for r in rows: merged[r["keys"][0]] = r
        rows = sorted(merged.values(), key=lambda r: -r["impressions"])
        print(f"=== Frazy [{', '.join(seeds)}] site-wide {s1}..{e1} ===")
        print(f"{'fraza':<44}{'clk':>5}{'imp':>7}{'CTR':>7}{'poz':>6}")
        for r in rows[:40]:
            print(f"{r['keys'][0]:<44}{r['clicks']:>5.0f}{r['impressions']:>7.0f}{r['ctr']*100:>6.1f}%{r['position']:>6.1f}")

    # 2. performance samego hubu (po stronie)
    rows = q(tok, {"startDate": s1.isoformat(), "endDate": e1.isoformat(),
        "dimensions": ["query"], "rowLimit": 100,
        "dimensionFilterGroups": [{"filters": [
            {"dimension": "page", "operator": "equals", "expression": hub_url}]}]}).get("rows", [])
    rows = sorted(rows, key=lambda r: -r["impressions"])
    print(f"\n=== Hub {hub_url} — frazy {s1}..{e1} ===")
    if not rows:
        print("(brak impresji w okresie)")
    else:
        print(f"{'fraza':<44}{'clk':>5}{'imp':>7}{'CTR':>7}{'poz':>6}")
        for r in rows[:40]:
            print(f"{r['keys'][0]:<44}{r['clicks']:>5.0f}{r['impressions']:>7.0f}{r['ctr']*100:>6.1f}%{r['position']:>6.1f}")

if __name__ == "__main__":
    main()
