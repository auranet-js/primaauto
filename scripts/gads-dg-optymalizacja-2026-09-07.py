#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""[DG] optymalizacja 2026-09-07 — budżet 45 -> 35 zł + cięcia wg pomiaru 30 dni.

Decyzja Janka: budżet w dół do 35 zł i optymalizacja. Pomiar, na którym stoją cięcia:

  FORMATY (segments.ad_format_type):
    SHORTS     244,61 zł / 18 konw / CPA 13,59  <- najlepszy
    UNKNOWN    247,35 zł / 10 konw / CPA 24,74
    INFEED     237,28 zł /  3 konw / CPA 79,09  <- zjada tyle co Shorts, dowozi 6x mniej
    INSTREAM    48,59 zł /  1 konw / CPA 48,59

  PLACEMENTY: 4 531 kanałów YT = 264,05 zł / 29 konw (CPA 9,10)
              youtube.com feed  = 182,27 zł /  3 konw (CPA 60,76)

  GODZINY: okno 00:00-01:00 = 13,68 zł / 0 konw; 06:00-08:00 = 18,66 zł / 0 konw.
           Pierwsza konwersja doby wpada o 09:00. Szczyt: 19h (9 konw), 15h (7), 22h (4).

Urządzenia NIE ruszane — bid_modifier 0 z 31.08 działa: od 01.09 leci wyłącznie MOBILE.

    python3 scripts/gads-dg-optymalizacja-2026-09-07.py            # dry-run
    python3 scripts/gads-dg-optymalizacja-2026-09-07.py --apply
"""
import json, os, sys, urllib.request, urllib.error
from datetime import datetime

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from gads_client import load, refresh  # noqa: E402

CID = "9506068500"
CAMPAIGN = 24069066886
BUDGET_RES = "customers/9506068500/campaignBudgets/15746510158"
API = json.load(open("/home/host476470/secrets/google/ads-config.json")).get("api_version", "v25")
APPLY = "--apply" in sys.argv
NOWY_BUDZET = 35_000_000          # 35 zł
START_NOWY = 8                    # 06:00 -> 08:00
OKNO_NOCNE_DO = 1                 # okna 00:00-01:00 do usunięcia

# kanały YT: >=1 zł wydatku w 30 dniach i ZERO konwersji
KANALY = ["Telewizja Republika", "Polsat", "Okiem Wiejskiego", "PolitycznyVibe", "Kanał Zero",
          "PERSONA | Historie gwiazd", "Słuchaj, bo ważne!", "Jan Piński", "polsatnews.pl",
          "Filmowa Przygoda", "Męska Kronika", "Panna młoda - Seriale", "PRZEkanał",
          "Raport Punktu Tętna", "AutoSekretu", "DOKTOR CRINGE", "Cena Zdrady", "Luczek 🐶",
          "KamilX26", "wPolsce24", "RH Recaps", "Sebastian Owczarski"]


def hdr():
    o, t, c = load()
    return {"Authorization": f"Bearer {refresh(o, t)}", "developer-token": c["developer_token"],
            "login-customer-id": CID, "Content-Type": "application/json"}


def gaql(q, h):
    out, tok = [], None
    while True:
        b = {"query": q}
        if tok:
            b["pageToken"] = tok
        u = f"https://googleads.googleapis.com/{API}/customers/{CID}/googleAds:search"
        d = json.loads(urllib.request.urlopen(urllib.request.Request(
            u, data=json.dumps(b).encode(), headers=h)).read())
        out += d.get("results", [])
        tok = d.get("nextPageToken")
        if not tok:
            return out


def mutate(suffix, payload, h, opis=""):
    u = f"https://googleads.googleapis.com/{API}/customers/{CID}/{suffix}"
    try:
        return json.loads(urllib.request.urlopen(urllib.request.Request(
            u, data=json.dumps(payload).encode(), headers=h)).read())
    except urllib.error.HTTPError as e:
        tresc = e.read().decode()
        print(f"  BŁĄD {opis} ({e.code}): {tresc[:500]}", file=sys.stderr)
        return None


h = hdr()
BACKUP = os.path.expanduser(f"~/backups/primaauto/dg-optymalizacja/{datetime.now():%Y-%m-%d}")
os.makedirs(BACKUP, exist_ok=True)

przed = {
    "budzet": gaql(f"SELECT campaign_budget.amount_micros FROM campaign WHERE campaign.id={CAMPAIGN}", h),
    "harmonogram": gaql(f"SELECT campaign_criterion.criterion_id, campaign_criterion.ad_schedule.day_of_week, "
                        f"campaign_criterion.ad_schedule.start_hour, campaign_criterion.ad_schedule.end_hour "
                        f"FROM campaign_criterion WHERE campaign.id={CAMPAIGN} "
                        f"AND campaign_criterion.type='AD_SCHEDULE'", h),
    "kanaly": gaql(f"SELECT campaign_criterion.criterion_id, campaign_criterion.youtube_channel.channel_id "
                   f"FROM campaign_criterion WHERE campaign.id={CAMPAIGN} "
                   f"AND campaign_criterion.type='YOUTUBE_CHANNEL'", h),
}
json.dump(przed, open(f"{BACKUP}/przed.json", "w"), ensure_ascii=False, indent=1)
stary_budzet = int(przed["budzet"][0]["campaignBudget"]["amountMicros"]) / 1e6
print(f"dump przed -> {BACKUP}/przed.json")
print(f"\n1. budżet: {stary_budzet:.2f} -> {NOWY_BUDZET/1e6:.2f} zł/dz")

nocne = [r for r in przed["harmonogram"]
         if r["campaignCriterion"]["adSchedule"].get("endHour") == OKNO_NOCNE_DO]
ranne = [r for r in przed["harmonogram"]
         if r["campaignCriterion"]["adSchedule"].get("startHour") == 6]
print(f"2. harmonogram: usuwam {len(nocne)} okien 00:00-01:00, przesuwam {len(ranne)} okien na {START_NOWY:02d}:00")
print(f"3. kanały YT: {len(KANALY)} do wykluczenia (już wykluczonych: {len(przed['kanaly'])})")
print(f"4. in-feed: próba wyłączenia (validate_only najpierw)")

if not APPLY:
    print("\nDRY-RUN — nic nie wysłano. Dodaj --apply.")
    sys.exit(0)

# --- 1. budżet ---
r = mutate("campaignBudgets:mutate", {"operations": [
    {"update": {"resourceName": BUDGET_RES, "amountMicros": str(NOWY_BUDZET)},
     "updateMask": "amountMicros"}]}, h, "budżet")
print(f"OK budżet -> {NOWY_BUDZET/1e6:.2f} zł/dz" if r else "NIEUDANE: budżet")

# --- 2. harmonogram: usuń nocne, przesuń poranne ---
ops = [{"remove": f"customers/{CID}/campaignCriteria/{CAMPAIGN}~{r['campaignCriterion']['criterionId']}"}
       for r in nocne]
for r in ranne:
    s = r["campaignCriterion"]["adSchedule"]
    ops.append({"remove": f"customers/{CID}/campaignCriteria/{CAMPAIGN}~{r['campaignCriterion']['criterionId']}"})
    ops.append({"create": {"campaign": f"customers/{CID}/campaigns/{CAMPAIGN}",
                           "adSchedule": {"dayOfWeek": s["dayOfWeek"], "startHour": START_NOWY,
                                          "startMinute": "ZERO", "endHour": s.get("endHour", 24),
                                          "endMinute": "ZERO"}}})
r = mutate("campaignCriteria:mutate", {"operations": ops, "partialFailure": False}, h, "harmonogram")
print(f"OK harmonogram: {len(ops)} operacji" if r else "NIEUDANE: harmonogram")

# --- 3. kanały YT: potrzebny channel_id, szukamy po nazwie w raporcie placementów ---
mapa = {}
for x in gaql(f"SELECT group_placement_view.display_name, group_placement_view.target_url, "
              f"group_placement_view.placement "
              f"FROM group_placement_view WHERE campaign.id={CAMPAIGN} "
              f"AND segments.date DURING LAST_30_DAYS", h):
    g = x["groupPlacementView"]
    if g.get("displayName"):
        mapa[g["displayName"]] = g.get("placement")
brak = [k for k in KANALY if not mapa.get(k)]
ops = [{"create": {"campaign": f"customers/{CID}/campaigns/{CAMPAIGN}", "negative": True,
                   "youtubeChannel": {"channelId": mapa[k]}}} for k in KANALY if mapa.get(k)]
if brak:
    print(f"  UWAGA: bez channel_id, pomijam: {', '.join(brak[:6])}")
if ops:
    r = mutate("campaignCriteria:mutate", {"operations": ops, "partialFailure": True}, h, "kanały")
    if r:
        ile = len([x for x in r.get("results", []) if x])
        print(f"OK wykluczono kanałów: {ile}/{len(ops)}")
        if r.get("partialFailureError"):
            print(f"  częściowe błędy: {json.dumps(r['partialFailureError'])[:300]}")

# --- 4. in-feed: sprawdź, czy API w ogóle to przyjmie ---
prob = mutate("campaigns:mutate", {"operations": [
    {"update": {"resourceName": f"customers/{CID}/campaigns/{CAMPAIGN}",
                "videoCampaignSettings": {"videoAdInventoryControl": {"allowInFeed": False}}},
     "updateMask": "video_campaign_settings.video_ad_inventory_control.allow_in_feed"}],
    "validateOnly": True}, h, "in-feed (validate)")
if prob is not None:
    r = mutate("campaigns:mutate", {"operations": [
        {"update": {"resourceName": f"customers/{CID}/campaigns/{CAMPAIGN}",
                    "videoCampaignSettings": {"videoAdInventoryControl": {"allowInFeed": False}}},
         "updateMask": "video_campaign_settings.video_ad_inventory_control.allow_in_feed"}]}, h, "in-feed")
    print("OK in-feed wyłączony" if r else "NIEUDANE: in-feed")
else:
    print("  in-feed: API nie przyjmuje tego pola dla Demand Gen — sterowanie tylko w panelu")

# --- weryfikacja ---
print("\nWERYFIKACJA:")
b = gaql(f"SELECT campaign_budget.amount_micros FROM campaign WHERE campaign.id={CAMPAIGN}", h)
print(f"  budżet: {int(b[0]['campaignBudget']['amountMicros'])/1e6:.2f} zł/dz")
hh = gaql(f"SELECT campaign_criterion.ad_schedule.day_of_week, campaign_criterion.ad_schedule.start_hour, "
          f"campaign_criterion.ad_schedule.end_hour FROM campaign_criterion "
          f"WHERE campaign.id={CAMPAIGN} AND campaign_criterion.type='AD_SCHEDULE'", h)
okna = sorted({(x["campaignCriterion"]["adSchedule"].get("startHour", 0),
                x["campaignCriterion"]["adSchedule"].get("endHour", 24)) for x in hh})
print(f"  harmonogram: {len(hh)} kryteriów, okna {okna}")
k = gaql(f"SELECT campaign_criterion.criterion_id FROM campaign_criterion WHERE campaign.id={CAMPAIGN} "
         f"AND campaign_criterion.type='YOUTUBE_CHANNEL' AND campaign_criterion.negative=true", h)
print(f"  wykluczonych kanałów YT: {len(k)}")
