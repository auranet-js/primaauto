#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Zawęża cele konwersji kampanii do kontaktu i formularza (ustalenie Janka 2026-08-19).

Zostawia biddable: CONTACT/WEBSITE, SUBMIT_LEAD_FORM/WEBSITE, PURCHASE/WEBSITE.
Zdejmuje biddable z reszty — w praktyce celów YouTube (ENGAGEMENT/YOUTUBE_HOSTED,
YOUTUBE_FOLLOW_ON_VIEWS/YOUTUBE_HOSTED) i UNKNOWN/GOOGLE_HOSTED.

PURCHASE zostaje świadomie: to realna transakcja PayU, dziś 0 konwersji (płatności
wyłączone flagą), ale gdy ruszą, ma być liczona.

    python3 scripts/ads-zawez-cele.py            # dry-run
    python3 scripts/ads-zawez-cele.py --apply    # wykonanie
"""
import json, sys, urllib.request, urllib.error
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent.parent / "tmp"))
from gads_client import load, refresh  # noqa: E402

CID = "9506068500"
ZOSTAJA = {("CONTACT", "WEBSITE"), ("SUBMIT_LEAD_FORM", "WEBSITE"), ("PURCHASE", "WEBSITE")}

oauth, tokens, cfg = load()
API = cfg.get("api_version", "v25")
H = {"Authorization": f"Bearer {refresh(oauth, tokens)}", "developer-token": cfg["developer_token"],
     "login-customer-id": CID, "Content-Type": "application/json"}


def q(gaql):
    r = urllib.request.Request(f"https://googleads.googleapis.com/{API}/customers/{CID}/googleAds:search",
                               data=json.dumps({"query": gaql}).encode(), headers=H)
    return json.load(urllib.request.urlopen(r)).get("results", [])


rows = q("""SELECT campaign.name, campaign.id, campaign.status, campaign_conversion_goal.category,
    campaign_conversion_goal.origin, campaign_conversion_goal.biddable,
    campaign_conversion_goal.resource_name FROM campaign_conversion_goal""")

ops = []
for r in rows:
    g = r["campaignConversionGoal"]
    cat, org = g.get("category"), g.get("origin")
    if not g.get("biddable") or (cat, org) in ZOSTAJA:
        continue
    # Celu UNKNOWN/GOOGLE_HOSTED nie da się zaadresować — API odrzuca resource name
    # z członem "UNKNOWN" (BAD_RESOURCE_ID). Cel-widmo, nic nie zlicza — pomijamy.
    if cat == "UNKNOWN":
        continue
    # Kampania REMOVED nie przyjmuje mutacji celów (RESOURCE_NOT_FOUND).
    if r["campaign"]["status"] == "REMOVED":
        continue
    ops.append({"campaign": r["campaign"]["name"], "status": r["campaign"]["status"],
                "cel": f"{cat}/{org}", "rn": g["resourceName"]})

print(f"Do zdjęcia biddable: {len(ops)} celów\n")
per = {}
for o in ops:
    per.setdefault(o["campaign"], []).append(o["cel"])
for nm in sorted(per):
    print(f'  {nm}')
    for c in per[nm]:
        print(f'      - {c}')

if "--apply" not in sys.argv:
    print("\n=== DRY-RUN (dodaj --apply) ===")
    sys.exit(0)

mut = [{"update": {"resourceName": o["rn"], "biddable": False}, "updateMask": "biddable"} for o in ops]
req = urllib.request.Request(
    f"https://googleads.googleapis.com/{API}/customers/{CID}/campaignConversionGoals:mutate",
    data=json.dumps({"operations": mut}).encode(), headers=H)
try:
    res = json.load(urllib.request.urlopen(req))
    print(f"\nWYKONANE: {len(res.get('results', []))} operacji")
except urllib.error.HTTPError as e:
    print(f"\nBLAD {e.code}: {e.read().decode()[:900]}")
    sys.exit(1)
