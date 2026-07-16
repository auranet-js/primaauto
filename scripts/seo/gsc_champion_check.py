#!/usr/bin/env python3
"""
Czy champion jest potrzebny? Pytanie rozstrzygajace (T-203 pkt 10):
na frazach BROAD ({model} bez "cena") — kto u nas rankuje: HUB czy OFERTY?

- HUB z przodu, oferty daleko  -> to NIE kanibalizacja, tylko brak autorytetu -> champion nie pomoze
- OFERTY wypieraja hub          -> kanibalizacja realna -> champion ma sens
- Kilka OFERT na te sama fraze  -> rozjazd sygnalu miedzy egzemplarzami
"""
import json, urllib.parse, urllib.request, collections

SECRETS = "/home/host476470/secrets/google"
SITE = "https://primaauto.com.pl/"


def token():
    o = json.load(open(f"{SECRETS}/oauth-desktop-client.json"))["installed"]
    t = json.load(open(f"{SECRETS}/tokens.json"))
    data = urllib.parse.urlencode({
        "client_id": o["client_id"], "client_secret": o["client_secret"],
        "refresh_token": t["refresh_token"], "grant_type": "refresh_token",
    }).encode()
    return json.load(urllib.request.urlopen(
        urllib.request.Request("https://oauth2.googleapis.com/token", data=data)))["access_token"]


def q(tok, body):
    url = (f"https://searchconsole.googleapis.com/webmasters/v3/sites/"
           f"{urllib.parse.quote_plus(SITE)}/searchAnalytics/query")
    req = urllib.request.Request(url, data=json.dumps(body).encode(), headers={
        "Authorization": f"Bearer {tok}", "Content-Type": "application/json"})
    return json.load(urllib.request.urlopen(req))


tok = token()
RANGE = {"startDate": "2026-04-17", "endDate": "2026-07-15"}   # 90 dni

# Wszystkie pary query->page, potem filtrujemy po stronie klienta.
rows = q(tok, {**RANGE, "dimensions": ["query", "page"],
               "rowLimit": 25000, "dataState": "final"}).get("rows", [])

by_query = collections.defaultdict(list)
for r in rows:
    query, page = r["keys"]
    by_query[query].append({
        "page": page, "clicks": r["clicks"], "imp": r["impressions"], "pos": r["position"],
    })


def kind(u):
    if "/oferta/" in u:
        return "OFERTA"
    if "/samochody/" in u:
        return "HUB"
    return "inne"


# frazy BROAD = bez slow intencyjnych; interesuja nas te, gdzie w gre wchodzi oferta
SKIP = ("cena", "koszt", "ile kosztuje", "opinie", "import", "sprowadz", "gdzie kupic")

print(f"okno {RANGE['startDate']} .. {RANGE['endDate']}  |  par query-page: {len(rows)}\n")

konflikt, hub_wins, tylko_oferty, wiele_ofert = [], [], [], []

for query, pages in by_query.items():
    if any(s in query for s in SKIP):
        continue
    tot_imp = sum(p["imp"] for p in pages)
    if tot_imp < 10:
        continue
    hubs = [p for p in pages if kind(p["page"]) == "HUB"]
    offs = [p for p in pages if kind(p["page"]) == "OFERTA"]
    if not offs:
        continue

    best_hub = min(hubs, key=lambda p: p["pos"]) if hubs else None
    best_off = min(offs, key=lambda p: p["pos"])

    rec = {"q": query, "imp": tot_imp, "n_off": len(offs),
           "hub_pos": best_hub["pos"] if best_hub else None,
           "off_pos": best_off["pos"],
           "hub_clicks": best_hub["clicks"] if best_hub else 0,
           "off_clicks": sum(p["clicks"] for p in offs)}

    if len(offs) > 1:
        wiele_ofert.append(rec)
    if not hubs:
        tylko_oferty.append(rec)
    elif best_off["pos"] < best_hub["pos"]:
        konflikt.append(rec)          # OFERTA przed HUBEM = kanibalizacja
    else:
        hub_wins.append(rec)


def show(title, recs, n=12):
    print(f"=== {title}: {len(recs)}")
    if not recs:
        print("   (brak)\n")
        return
    recs.sort(key=lambda r: -r["imp"])
    print(f"   {'fraza':<34}{'imp':>6}{'ofert':>7}{'hub_poz':>9}{'oferta_poz':>11}{'kliki_of':>9}")
    for r in recs[:n]:
        hp = f"{r['hub_pos']:.1f}" if r["hub_pos"] else "—"
        print(f"   {r['q'][:33]:<34}{r['imp']:>6}{r['n_off']:>7}{hp:>9}{r['off_pos']:>11.1f}{r['off_clicks']:>9}")
    print()


show("OFERTA PRZED HUBEM (kanibalizacja — argument ZA championem)", konflikt)
show("HUB PRZED OFERTAMI (hub broni sie sam — champion zbedny)", hub_wins)
show("TYLKO OFERTY, hub NIE rankuje wcale", tylko_oferty)
show("WIELE NASZYCH OFERT na te sama fraze (rozjazd sygnalu)", wiele_ofert)

print("=== BILANS")
print(f"  fraz gdzie oferta bije hub:      {len(konflikt)}")
print(f"  fraz gdzie hub bije oferty:      {len(hub_wins)}")
print(f"  fraz bez hubu w indeksie:        {len(tylko_oferty)}")
print(f"  kliki ofert w konflikcie:        {sum(r['off_clicks'] for r in konflikt)}")
print(f"  kliki hubow gdy wygrywaja:       {sum(r['hub_clicks'] for r in hub_wins)}")
