#!/usr/bin/env python3
"""GSC: frazy 'denza' site-wide (28d vs poprzednie 28d) + performance hubu z9-gt-dm-i."""
import json, urllib.parse, urllib.request, datetime as dt
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
    tok = token()
    end = dt.date.today() - dt.timedelta(days=3)  # GSC ma ~3d opóźnienia
    s1, e1 = end - dt.timedelta(days=27), end
    s0, e0 = s1 - dt.timedelta(days=28), s1 - dt.timedelta(days=1)

    def denza_queries(s, e):
        return q(tok, {
            "startDate": s.isoformat(), "endDate": e.isoformat(),
            "dimensions": ["query"], "rowLimit": 100,
            "dimensionFilterGroups": [{"filters": [
                {"dimension": "query", "operator": "contains", "expression": "denza"}]}],
        }).get("rows", [])

    cur, prev = denza_queries(s1, e1), denza_queries(s0, e0)
    pmap = {r["keys"][0]: r for r in prev}

    print(f"=== Frazy 'denza' site-wide — {s1} .. {e1} (vs {s0} .. {e0}) ===")
    print(f"{'fraza':<38}{'clk':>5}{'imp':>7}{'CTR':>7}{'poz':>6}{'Δimp':>7}{'Δpoz':>7}")
    tc = ti = 0
    for r in sorted(cur, key=lambda x: -x["impressions"]):
        kw = r["keys"][0]; clk=r["clicks"]; imp=r["impressions"]; ctr=r["ctr"]*100; pos=r["position"]
        tc+=clk; ti+=imp
        p = pmap.get(kw)
        dimp = f"{imp-p['impressions']:+.0f}" if p else "NEW"
        dpos = f"{p['position']-pos:+.1f}" if p else "-"
        print(f"{kw[:37]:<38}{clk:>5}{imp:>7}{ctr:>6.1f}%{pos:>6.1f}{dimp:>7}{dpos:>7}")
    print(f"{'RAZEM':<38}{tc:>5}{ti:>7}")

    # Hub z9-gt-dm-i page performance + queries
    hub = "https://primaauto.com.pl/samochody/denza/z9-gt-dm-i/"
    print(f"\n=== Hub {hub} — frazy 28d ===")
    rows = q(tok, {
        "startDate": s1.isoformat(), "endDate": e1.isoformat(),
        "dimensions": ["query"], "rowLimit": 50,
        "dimensionFilterGroups": [{"filters": [
            {"dimension": "page", "operator": "equals", "expression": hub}]}],
    }).get("rows", [])
    if not rows:
        print("  (brak danych — strona bez kliknięć/impresji w 28d LUB inny URL)")
    for r in sorted(rows, key=lambda x: -x["impressions"]):
        print(f"  {r['keys'][0][:42]:<44}{r['clicks']:>4}{r['impressions']:>7}{r['ctr']*100:>6.1f}%{r['position']:>6.1f}")

if __name__ == "__main__":
    main()
