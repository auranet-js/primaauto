#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Raport mailowy: czy DSA na ofertach wystartowalo (2026-07-17, jednorazowy).

16.07 feed DSA przestawiony z hubow modeli na najtansza oferte per model (133 URL-e /oferta/).
Lekcja z 12.07: skrypt mowiacy „OK" NIE jest dowodem — 43 huby lezaly wtedy martwe 4 dni,
bo nikt nie sprawdzil impresji. Dowodem sa impresje.

Wysylka: ~/bin/send-to-jan (jedyny autoryzowany kanal, SMTP claude@auratest.pl -> js@auranet.com.pl).
"""
import os, sys, json, subprocess, urllib.request, re
from datetime import datetime
sys.path.insert(0, "/home/host476470/projekty/primaauto/tmp")
from gads_client import load, refresh

CID="9506068500"; API="v21"; FEED_SET_ID="9118569940"; CAMP="23896725555"
oauth, tokens, cfg = load(); AT = refresh(oauth, tokens)
H={"Authorization":f"Bearer {AT}","developer-token":cfg["developer_token"],
   "login-customer-id":cfg["mcc_customer_id"],"Content-Type":"application/json"}
def q(g):
    r=urllib.request.Request(f"https://googleads.googleapis.com/{API}/customers/{CID}/googleAds:search",
        data=json.dumps({"query":g}).encode(), headers=H)
    return json.load(urllib.request.urlopen(r)).get("results",[])

feed=[x["asset"]["pageFeedAsset"] for x in q(f"SELECT asset.page_feed_asset.page_url, asset.page_feed_asset.labels "
      f"FROM asset_set_asset WHERE asset_set.id={FEED_SET_ID} AND asset_set_asset.status!='REMOVED'")]
urls=[f["pageUrl"] for f in feed]
nolabel=sum(1 for f in feed if not f.get("labels"))
huby=sum(1 for u in urls if "/samochody/" in u)
years={}
for u in urls:
    m=re.search(r"-(20\d\d)-\d+/?$", u); k=m.group(1) if m else "?"
    years[k]=years.get(k,0)+1
gate_bad=sum(v for k,v in years.items() if k in ("2022","2023","2024"))

rows=q(f"""SELECT dynamic_search_ads_search_term_view.search_term, dynamic_search_ads_search_term_view.headline,
 dynamic_search_ads_search_term_view.landing_page, metrics.impressions, metrics.clicks, metrics.cost_micros
FROM dynamic_search_ads_search_term_view WHERE campaign.id={CAMP} AND segments.date DURING TODAY""")
imp=cl=0; cost=0.0; per={}
for r in rows:
    v=r["dynamicSearchAdsSearchTermView"]; m=r["metrics"]
    i=int(m.get("impressions",0)); c=int(m.get("clicks",0))
    imp+=i; cl+=c; cost+=int(m.get("costMicros",0))/1e6
    lp=v.get("landingPage","")
    a=per.setdefault(lp,[0,0,v.get("headline","")]); a[0]+=i; a[1]+=c
oferta_lp=[k for k in per if "/oferta/" in k]
hub_lp=[k for k in per if "/samochody/" in k]
zywe=len([k for k in oferta_lp if per[k][0]>0])
camp=q(f"SELECT campaign.status, campaign.tracking_url_template FROM campaign WHERE campaign.id={CAMP}")[0]["campaign"]

verdict = "WCHODZI" if zywe>0 else "CISZA — NIE WCHODZI"
L=[]
L.append(f"# DSA na ofertach — raport startu ({datetime.now():%Y-%m-%d %H:%M})")
L.append("")
L.append(f"## WERDYKT: {verdict}")
L.append("")
L.append(f"Ofert w feedzie z impresjami dzis: **{zywe} / {len(urls)}**")
L.append("")
L.append("## Dzis (od polnocy)")
L.append(f"- wyswietlenia: {imp}")
L.append(f"- kliki: {cl}")
L.append(f"- koszt: {cost:.2f} zl")
L.append(f"- landingi /oferta/ z ruchem: {len(oferta_lp)}")
L.append(f"- landingi /samochody/ (huby, NIE powinno byc): {len(hub_lp)}")
L.append("")
L.append("## Stan konfiguracji")
L.append(f"- feed: {len(urls)} URL-i | huby: {huby} (ma byc 0) | bez etykiety: {nolabel} (ma byc 0)")
L.append(f"- roczniki: " + ", ".join(f"{k}: {v}" for k,v in sorted(years.items())) + f" | lamiacych gate 2025/2026: {gate_bad}")
L.append(f"- kampania: {camp['status']} | tracking template: {camp.get('trackingUrlTemplate') or '(zdjety — OK, oferta nie ma kotwicy #oferty)'}")
L.append("")
if per:
    L.append("## Top 15 reklam dzis (naglowek = title oferty, przepisany przez Google)")
    L.append("")
    for lp,(i,c,h) in sorted(per.items(), key=lambda x:-x[1][1])[:15]:
        L.append(f"- **{c} kl / {i} wysw** — {h}")
        L.append(f"  {lp}")
    L.append("")
L.append("## Co dalej")
if zywe==0:
    L.append('- **Cisza = problem.** Nie zakladaj „potrzeba wiecej czasu” — 12.07 tak wlasnie przespalismy')
    L.append("  43 martwe huby przez 4 dni. Sprawdz etykiety i status assetow.")
else:
    L.append("- Mechanizm dziala. Pelna ocena CTR/CPA: 30.07 (baseline sprzed zmiany: 1755 kl / 1064 zl / CPA 195 zl).")
L.append("- Cron `dsa-offer-feed-refresh.py` pilnuje aktualnosci codziennie 06:15 (sztuka schodzi -> podmiana URL).")
L.append("- Log crona: ~/.claude/dsa-offer-feed.log")
body="\n".join(L)
p="/tmp/dsa-report-body.md"
open(p,"w").write(body)
r=subprocess.run([os.path.expanduser("~/bin/send-to-jan"), "-s",
                  f"[primaauto] DSA na ofertach — {verdict} ({zywe}/{len(urls)} z impresjami)", "-B", p],
                 capture_output=True, text=True)
print("wysylka:", "OK" if r.returncode==0 else f"BLAD {r.stderr[:300]}")
print(body[:600])
