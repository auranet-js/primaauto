#!/usr/bin/env python3
"""Frazy, na ktorych rankuja TYLKO oferty, a hubu nie ma w SERP. Pelna lista + URL ofert -> JSON."""
import json, urllib.parse, urllib.request, collections

SECRETS = "/home/host476470/secrets/google"
SITE = "https://primaauto.com.pl/"


def token():
    o = json.load(open(f"{SECRETS}/oauth-desktop-client.json"))["installed"]
    t = json.load(open(f"{SECRETS}/tokens.json"))
    data = urllib.parse.urlencode({
        "client_id": o["client_id"], "client_secret": o["client_secret"],
        "refresh_token": t["refresh_token"], "grant_type": "refresh_token"}).encode()
    return json.load(urllib.request.urlopen(
        urllib.request.Request("https://oauth2.googleapis.com/token", data=data)))["access_token"]


def q(tok, body):
    url = (f"https://searchconsole.googleapis.com/webmasters/v3/sites/"
           f"{urllib.parse.quote_plus(SITE)}/searchAnalytics/query")
    req = urllib.request.Request(url, data=json.dumps(body).encode(), headers={
        "Authorization": f"Bearer {tok}", "Content-Type": "application/json"})
    return json.load(urllib.request.urlopen(req))


tok = token()
rows = q(tok, {"startDate": "2026-04-17", "endDate": "2026-07-15",
               "dimensions": ["query", "page"], "rowLimit": 25000,
               "dataState": "final"}).get("rows", [])

by_query = collections.defaultdict(list)
for r in rows:
    query, page = r["keys"]
    by_query[query].append({"page": page, "clicks": r["clicks"],
                            "imp": r["impressions"], "pos": r["position"]})

SKIP = ("cena", "koszt", "ile kosztuje", "opinie", "import", "sprowadz", "gdzie kupic")
out = []
for query, pages in by_query.items():
    if any(s in query for s in SKIP):
        continue
    if sum(p["imp"] for p in pages) < 10:
        continue
    hubs = [p for p in pages if "/samochody/" in p["page"]]
    offs = [p for p in pages if "/oferta/" in p["page"]]
    if offs and not hubs:
        best = min(offs, key=lambda p: p["pos"])
        out.append({
            "query": query,
            "imp": sum(p["imp"] for p in offs),
            "clicks": sum(p["clicks"] for p in offs),
            "n_offers": len(offs),
            "best_pos": round(best["pos"], 1),
            "best_url": best["page"],
        })

out.sort(key=lambda r: -r["imp"])
json.dump(out, open("/tmp/hub_missing.json", "w"), ensure_ascii=False, indent=1)
print(f"fraz bez hubu w SERP: {len(out)}\n")
print(f"{'fraza':<40}{'imp':>6}{'kliki':>7}{'ofert':>7}{'poz':>6}")
for r in out:
    print(f"{r['query'][:39]:<40}{r['imp']:>6}{r['clicks']:>7}{r['n_offers']:>7}{r['best_pos']:>6}")
print("\n-> /tmp/hub_missing.json")
