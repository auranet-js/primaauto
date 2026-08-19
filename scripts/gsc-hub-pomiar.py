#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Pomiar GSC per URL huba w dwóch oknach (przed/po) — kliki, impresje, CTR, pozycja.

    python3 scripts/gsc-hub-pomiar.py PRZED_START PRZED_KONIEC PO_START PO_KONIEC url1 url2 ...

Okna podawaj jako YYYY-MM-DD. GSC ma ~2-3 dni opóźnienia — nie pytaj o wczoraj.
"""
import json, sys, urllib.parse, urllib.request
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


def query(tok, start, end, dims, filters=None, limit=100):
    body = {"startDate": start, "endDate": end, "dimensions": dims, "rowLimit": limit}
    if filters:
        body["dimensionFilterGroups"] = [{"filters": filters}]
    req = urllib.request.Request(
        f"https://searchconsole.googleapis.com/webmasters/v3/sites/{urllib.parse.quote(SITE, safe='')}/searchAnalytics/query",
        data=json.dumps(body).encode(),
        headers={"Authorization": f"Bearer {tok}", "Content-Type": "application/json"})
    return json.load(urllib.request.urlopen(req)).get("rows", [])


def page_stats(tok, start, end, url):
    r = query(tok, start, end, ["page"], [{"dimension": "page", "operator": "equals", "expression": url}])
    if not r:
        return {"clicks": 0, "impressions": 0, "ctr": 0, "position": 0}
    return r[0]


if __name__ == "__main__":
    a1, a2, b1, b2 = sys.argv[1:5]
    urls = sys.argv[5:]
    tok = token()
    print(f'{"URL":58} {"kliki":>13} {"impresje":>15} {"CTR":>15} {"pozycja":>15}')
    print(f'{"":58} {"przed→po":>13} {"przed→po":>15} {"przed→po":>15} {"przed→po":>15}')
    for u in urls:
        a = page_stats(tok, a1, a2, u)
        b = page_stats(tok, b1, b2, u)
        short = u.replace("https://primaauto.com.pl", "")
        print(f'{short[:58]:58} {int(a["clicks"]):>5}→{int(b["clicks"]):<7} '
              f'{int(a["impressions"]):>6}→{int(b["impressions"]):<8} '
              f'{a["ctr"]*100:>6.2f}→{b["ctr"]*100:<8.2f} '
              f'{a["position"]:>6.1f}→{b["position"]:<8.1f}')
