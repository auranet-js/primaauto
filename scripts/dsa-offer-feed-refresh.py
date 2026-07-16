#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Cron: utrzymanie page feedu DSA celujacego w najtansza oferte per model.

Feed DSA (asset set 9118569940) celuje w KONKRETNE egzemplarze (/oferta/{slug}/), nie w huby
— decyzja Janka 16.07 (naglowek DSA = title strony docelowej; title oferty niesie cene i parametry,
title huba jest informacyjny bo musi rankowac w organicu).

Ryzyko, ktore ten cron obsluguje: sztuka schodzi (sprzedana -> draft/trash) => URL w feedzie
prowadzi w 404/301 i reklama umiera. Najtansza sztuka jest najatrakcyjniejsza, wiec schodzi pierwsza.
Tempo mierzone 16.07: ~12 listingow/30 dni opuszcza publish (~1 co 2-3 dni z 3058).

Cena w reklamie NIE wymaga odswiezania — meta title liczy sie przy renderze strony.
Ten cron pilnuje wylacznie: (1) czy sztuka z feedu nadal zyje, (2) czy nie ma nowej najtanszej.

Zakres: te same modele co w feedzie (bez rozszerzania) — patrz ADR.
Tryby: (domyslnie) dry-run | --apply | --quiet (bez outputu gdy zero zmian)
Log: ~/.claude/dsa-offer-feed.log
"""
import os, sys, json, subprocess, urllib.request, urllib.error
from datetime import datetime
sys.path.insert(0, "/home/host476470/projekty/primaauto/tmp")
from gads_client import load, refresh

CID="9506068500"; API="v21"; FEED_SET_ID="9118569940"; LABEL="dsa2026"
WP="/home/host476470/domains/primaauto.com.pl/public_html"
LOG=os.path.expanduser("~/.claude/dsa-offer-feed.log")
APPLY="--apply" in sys.argv; QUIET="--quiet" in sys.argv
BASE="https://primaauto.com.pl"

def log(m):
    line=f"[{datetime.now():%Y-%m-%d %H:%M}] {m}"
    if not QUIET: print(line)
    with open(LOG,"a") as f: f.write(line+"\n")

oauth, tokens, cfg = load(); AT = refresh(oauth, tokens)
H={"Authorization":f"Bearer {AT}","developer-token":cfg["developer_token"],
   "login-customer-id":cfg["mcc_customer_id"],"Content-Type":"application/json"}
def call(ep, body):
    body=dict(body); body["validateOnly"]=not APPLY
    req=urllib.request.Request(f"https://googleads.googleapis.com/{API}/customers/{CID}/{ep}",
        data=json.dumps(body).encode(), headers=H)
    try: return json.load(urllib.request.urlopen(req)), None
    except urllib.error.HTTPError as e: return None, e.read().decode()[:400]
def query(g):
    req=urllib.request.Request(f"https://googleads.googleapis.com/{API}/customers/{CID}/googleAds:search",
        data=json.dumps({"query":g}).encode(), headers=H)
    return json.load(urllib.request.urlopen(req)).get("results",[])

SQL = r"""
SELECT CONCAT(mk.slug,'/',ts.slug), x.slug FROM (
  SELECT tt.term_id AS sid,
    SUBSTRING_INDEX(GROUP_CONCAT(p.post_name ORDER BY CAST(pmp.meta_value AS UNSIGNED) ASC, p.ID ASC SEPARATOR '||'),'||',1) AS slug
  FROM wp7j_posts p
  JOIN wp7j_term_relationships tr ON tr.object_id=p.ID
  JOIN wp7j_term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='serie'
  JOIN wp7j_postmeta pmp ON pmp.post_id=p.ID AND pmp.meta_key='price' AND pmp.meta_value REGEXP '^[0-9]+$'
  JOIN wp7j_postmeta pmy ON pmy.post_id=p.ID AND pmy.meta_key='ca-year' AND pmy.meta_value IN ('2025','2026')
  WHERE p.post_type='listings' AND p.post_status='publish'
  GROUP BY tt.term_id
) x
JOIN wp7j_term_taxonomy tt2 ON tt2.term_id=x.sid
JOIN wp7j_terms ts ON ts.term_id=x.sid
LEFT JOIN wp7j_term_taxonomy ttm ON ttm.term_id=tt2.parent
LEFT JOIN wp7j_terms mk ON mk.term_id=ttm.term_id
"""
r=subprocess.run(["wp","db","query",SQL,"--skip-column-names"],cwd=WP,capture_output=True,text=True)
if r.returncode!=0:
    log(f"BLAD wp db query: {r.stderr[:200]}"); sys.exit(1)
best={}
for line in r.stdout.splitlines():
    p=line.split("\t")
    if len(p)>=2 and p[0].strip(): best[p[0]]=p[1]

cur={}   # slug -> assetSetAsset
for r2 in query(f"SELECT asset.page_feed_asset.page_url, asset_set_asset.resource_name FROM asset_set_asset "
                f"WHERE asset_set.id={FEED_SET_ID} AND asset_set_asset.status!='REMOVED'"):
    u=r2["asset"]["pageFeedAsset"]["pageUrl"]
    if "/oferta/" in u: cur[u.rstrip("/").split("/oferta/")[-1]] = r2["assetSetAsset"]["resourceName"]

# zbior docelowy = najtansza sztuka dla modeli, ktore JUZ sa reprezentowane w feedzie.
# mapujemy przez slug -> model, zeby nie rozszerzac zakresu przy okazji.
slug2hub={v:k for k,v in best.items()}
hubs_in_feed=set()
for s in cur:
    if s in slug2hub: hubs_in_feed.add(slug2hub[s])
# sztuki ktore zniknely z publish — ich slug nie jest juz najtanszy ANI zaden w best
orphan=[s for s in cur if s not in slug2hub]
if orphan:
    # ustal model osierocony przez zapytanie o dowolna zywa sztuke tego modelu — inaczej wpis znika bezpowrotnie
    r3=subprocess.run(["wp","db","query",
        "SELECT CONCAT(mk.slug,'/',ts.slug), p.post_name FROM wp7j_posts p "
        "JOIN wp7j_term_relationships tr ON tr.object_id=p.ID "
        "JOIN wp7j_term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='serie' "
        "JOIN wp7j_terms ts ON ts.term_id=tt.term_id "
        "LEFT JOIN wp7j_term_taxonomy ttm ON ttm.term_id=tt.parent "
        "LEFT JOIN wp7j_terms mk ON mk.term_id=ttm.term_id "
        f"WHERE p.post_type='listings' AND p.post_name IN ({','.join(repr(s) for s in orphan)})",
        "--skip-column-names"], cwd=WP, capture_output=True, text=True)
    for line in r3.stdout.splitlines():
        p=line.split("\t")
        if len(p)>=2 and p[0].strip(): hubs_in_feed.add(p[0])

want={h: best[h] for h in hubs_in_feed if h in best}
want_slugs=set(want.values())
add=[(h,s) for h,s in want.items() if s not in cur]
rm =[s for s in cur if s not in want_slugs]

if not add and not rm:
    log(f"OK bez zmian — feed {len(cur)} ofert aktualny"); sys.exit(0)
log(f"{'APPLY' if APPLY else 'DRY-RUN'} feed={len(cur)} | do usuniecia: {len(rm)} | do dodania: {len(add)}")
for h,s in add: log(f"  + {h} -> {s}")
for s in rm:    log(f"  - {s} (sztuka zeszla albo nie jest juz najtansza)")

if rm:
    res,err=call("assetSetAssets:mutate",{"operations":[{"remove":cur[s]} for s in rm]})
    log("  usuniecie: " + ("OK" if res is not None else f"BLAD {err}"))
    if res is None: sys.exit(1)
if add:
    ops=[{"create":{"pageFeedAsset":{"pageUrl":f"{BASE}/oferta/{s}/","labels":[LABEL]}}} for _,s in add]
    res,err=call("assets:mutate",{"operations":ops})
    if res is None: log(f"  BLAD tworzenia assetow: {err}"); sys.exit(1)
    if APPLY:
        names=[x["resourceName"] for x in res["results"]]
        res,err=call("assetSetAssets:mutate",{"operations":[
            {"create":{"assetSet":f"customers/{CID}/assetSets/{FEED_SET_ID}","asset":n}} for n in names]})
        log("  podpiecie: " + ("OK" if res is not None else f"BLAD {err}"))
    else:
        log(f"  walidacja OK ({len(ops)} do dodania)")
