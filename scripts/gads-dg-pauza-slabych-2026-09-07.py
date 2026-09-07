#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""[DG] pauza dwóch najsłabszych kreacji (2026-09-07, decyzja Janka).

Zdejmowane (7 dni emisji od 31.08, zero konwersji, najniższy CTR w stawce):
  822803999502  DG wideo — Denza Z9 GT     2 054 imp / 49 kl / 22,83 zł / 0 konw / CTR 2,39%
  822846696946  DG wideo — BYD Leopard 7   2 174 imp / 62 kl / 20,88 zł / 0 konw / CTR 2,85%

PAUSED, nie REMOVED — wracają jedną mutacją, gdy będzie na to budżet albo nowy montaż.
Zostają: Denza N9 i Lynk & Co 900 (start 05.09, tylko 2 dni emisji — brak podstaw do oceny).

Kontekst: tego samego dnia budżet zszedł 45 -> 35 zł, doszedł harmonogram 08:00-24:00
i 22 wykluczone kanały YT. Przy ocenie 14.09 pamiętać, że atrybucja jest wymieszana.

    python3 scripts/gads-dg-pauza-slabych-2026-09-07.py            # dry-run
    python3 scripts/gads-dg-pauza-slabych-2026-09-07.py --apply
"""
import json, os, sys, urllib.request, urllib.error

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from gads_client import load, refresh  # noqa: E402

CID = "9506068500"
AD_GROUP = 195605725661
API = json.load(open("/home/host476470/secrets/google/ads-config.json")).get("api_version", "v25")
APPLY = "--apply" in sys.argv
DO_PAUZY = {"822803999502": "Denza Z9 GT", "822846696946": "BYD Leopard 7"}

o, t, c = load()
hdr = {"Authorization": f"Bearer {refresh(o, t)}", "developer-token": c["developer_token"],
       "login-customer-id": CID, "Content-Type": "application/json"}


def gaql(q):
    u = f"https://googleads.googleapis.com/{API}/customers/{CID}/googleAds:searchStream"
    d = json.loads(urllib.request.urlopen(urllib.request.Request(
        u, data=json.dumps({"query": q}).encode(), headers=hdr)).read())
    return [r for b in (d or []) for r in b.get("results", [])]


stan = {r["adGroupAd"]["ad"]["id"]: (r["adGroupAd"].get("status"), r["adGroupAd"]["ad"].get("name", "—"))
        for r in gaql(f"SELECT ad_group_ad.ad.id, ad_group_ad.ad.name, ad_group_ad.status "
                      f"FROM ad_group_ad WHERE ad_group.id={AD_GROUP}")}

for aid, nazwa in DO_PAUZY.items():
    obecny = stan.get(aid, ("BRAK", "?"))
    print(f"  {aid}  {nazwa:<18}{obecny[0]} -> PAUSED")
    if obecny[0] != "ENABLED":
        sys.exit(f"ABORT: {aid} nie jest ENABLED (jest {obecny[0]}) — nie ruszam")

aktywne_po = len([1 for a, (s, n) in stan.items() if s == "ENABLED"]) - len(DO_PAUZY)
print(f"\n  aktywnych kreacji: {len([1 for a,(s,n) in stan.items() if s=='ENABLED'])} -> {aktywne_po}")

if not APPLY:
    print("\nDRY-RUN — nic nie wysłano. Dodaj --apply.")
    sys.exit(0)

ops = [{"update": {"resourceName": f"customers/{CID}/adGroupAds/{AD_GROUP}~{aid}",
                   "status": "PAUSED"}, "updateMask": "status"} for aid in DO_PAUZY]
u = f"https://googleads.googleapis.com/{API}/customers/{CID}/adGroupAds:mutate"
try:
    urllib.request.urlopen(urllib.request.Request(
        u, data=json.dumps({"operations": ops, "partialFailure": False}).encode(), headers=hdr)).read()
except urllib.error.HTTPError as e:
    sys.exit(f"BŁĄD {e.code}: {e.read().decode()[:600]}")

print("\nWERYFIKACJA:")
for r in gaql(f"SELECT ad_group_ad.ad.id, ad_group_ad.ad.name, ad_group_ad.status "
              f"FROM ad_group_ad WHERE ad_group.id={AD_GROUP}"):
    a = r["adGroupAd"]
    znak = "  <-- zapauzowana" if a["ad"]["id"] in DO_PAUZY else ""
    print(f"  {a['ad']['id']:<14}{(a['ad'].get('name') or '—')[:34]:<36}{a.get('status','')}{znak}")
