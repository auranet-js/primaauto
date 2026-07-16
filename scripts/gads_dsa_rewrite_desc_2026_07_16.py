#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Przepisanie opisow reklam DSA (2026-07-16).

Reklama w Google Ads jest IMMUTABLE — opisu nie da sie zedytowac. Trzeba utworzyc nowa
w tej samej grupie i usunac stara. Koszt: nowa kreacja startuje z zerowa historia
i przechodzi ponowna weryfikacje.

Naglowek DSA generuje Google z title landingu (od 16.07 = oferta, wiec niesie cene
i parametry). Opis to jedyne pole w 100% nasze — dzis stoja w nim 4 generyki
obslugujace wszystkie 133 oferty:
  „Zamow online - bez salonu. Rocznik 2026, minimalny przebieg. Ceny w ogloszeniach."
  „Auta z rynku chinskiego na indywidualne zamowienie. Weryfikujemy przed zakupem."
  „Zarezerwuj teraz - auto weryfikujemy przed zakupem i sprowadzamy do Polski dla Ciebie."
  „Indywidualne zamowienie, dostawa pod dom, polska homologacja i gwarancja."

Wariant P2 — bez terminu (nie mamy twardej liczby: „90 dni" nie istnieje w zadnym zrodle,
wiki mowi „kilka-kilkanascie tygodni"; w reklamie termin = zobowiazanie).
NIE uzywamy „bez doplat na odbiorze" — zdjete z paska zaufania w v0.33.31.

Limit description = 90 znakow.
Tryby: (domyslnie) dry-run validateOnly | --apply
"""
import os, sys, json, urllib.request, urllib.error
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from gads_client import load, refresh

CID="9506068500"; API="v21"; CAMP="23896725555"; ADGROUP="197286896339"
APPLY = "--apply" in sys.argv

# Copy Janka (16.07). Cene niesie juz naglowek (title oferty), wiec opis mowi to,
# czego w naglowku NIE ma: co robimy dla klienta + co dalej.
# „Mozliwy leasing" — spojne z paskiem zaufania v0.33.31.
# „Sprawdz podobne oferty" — trafia w blok „Inne egzemplarze" (T-187, jest na kazdej ofercie).
NEW = [
    ("Zamów dla siebie — sprowadzimy i załatwimy formalności. Możliwy leasing.",
     "Sprawdź podobne oferty tego modelu."),
]
# reklama 816552895918 („Zarezerwuj teraz…") ZOSTAJE — zaakceptowana przez Janka,
# zachowuje historie. Wymieniamy WYLACZNIE 816552895915 („Zamow online - bez salonu…").
KEEP_AD_ID = "816552895918"
for d1,d2 in NEW:
    for d in (d1,d2):
        assert len(d) <= 90, f"ZA DLUGI ({len(d)}): {d}"

oauth, tokens, cfg = load(); AT = refresh(oauth, tokens)
H={"Authorization":f"Bearer {AT}","developer-token":cfg["developer_token"],
   "login-customer-id":cfg["mcc_customer_id"],"Content-Type":"application/json"}
def call(ep, body):
    body=dict(body); body["validateOnly"]=not APPLY
    req=urllib.request.Request(f"https://googleads.googleapis.com/{API}/customers/{CID}/{ep}",
        data=json.dumps(body).encode(), headers=H)
    try: return json.load(urllib.request.urlopen(req)), None
    except urllib.error.HTTPError as e: return None, e.read().decode()[:500]
def query(g):
    req=urllib.request.Request(f"https://googleads.googleapis.com/{API}/customers/{CID}/googleAds:search",
        data=json.dumps({"query":g}).encode(), headers=H)
    return json.load(urllib.request.urlopen(req)).get("results",[])

allads=query(f"""SELECT ad_group_ad.resource_name, ad_group_ad.ad.id,
 ad_group_ad.ad.expanded_dynamic_search_ad.description, ad_group_ad.ad.expanded_dynamic_search_ad.description2
 FROM ad_group_ad WHERE campaign.id={CAMP} AND ad_group_ad.status!='REMOVED'""")
old=[r for r in allads if r["adGroupAd"]["ad"]["id"] != KEEP_AD_ID]
keep=[r for r in allads if r["adGroupAd"]["ad"]["id"] == KEEP_AD_ID]
print("ZOSTAJE nietknieta (zachowuje historie):")
for r in keep:
    a=r["adGroupAd"]["ad"].get("expandedDynamicSearchAd",{})
    print(f"  id={r['adGroupAd']['ad']['id']}")
    print(f"    1: {a.get('description','')}")
    print(f"    2: {a.get('description2','')}")
print()
print(f"{'APPLY' if APPLY else 'DRY-RUN'} | reklam w grupie: {len(allads)}\n")
print("STARE (do usuniecia):")
for r in old:
    a=r["adGroupAd"]["ad"].get("expandedDynamicSearchAd",{})
    print(f"  id={r['adGroupAd']['ad']['id']}")
    print(f"    1: {a.get('description','')}")
    print(f"    2: {a.get('description2','')}")
print("\nNOWE (do utworzenia):")
for i,(d1,d2) in enumerate(NEW,1):
    print(f"  reklama {i}")
    print(f"    1: [{len(d1):>2}] {d1}")
    print(f"    2: [{len(d2):>2}] {d2}")

# 1) tworzymy nowe (najpierw — zeby grupa nie zostala bez reklam)
ops=[{"create":{"adGroup":f"customers/{CID}/adGroups/{ADGROUP}","status":"ENABLED",
      "ad":{"expandedDynamicSearchAd":{"description":d1,"description2":d2}}}} for d1,d2 in NEW]
res,err=call("adGroupAds:mutate",{"operations":ops})
print("\n1. utworzenie nowych:", "OK" if res is not None else f"BLAD {err}")
if res is None: sys.exit(1)

# 2) usuwamy stare
if APPLY:
    ops=[{"remove":r["adGroupAd"]["resourceName"]} for r in old]
    res,err=call("adGroupAds:mutate",{"operations":ops})
    print("2. usuniecie starych:", "OK" if res is not None else f"BLAD {err}")
else:
    print(f"2. usuniecie {len(old)} starych — tylko przy --apply")
