#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""[RMKT] optymalizacja 2026-09-07 — po naprawie feedu (patrz gads-rmkt-feed-refresh.py).

Trzy zmiany, każda z własnym uzasadnieniem z pomiaru 30 dni:
  1. wykluczenie 11 placementów: >=5 zł wydatku i ZERO konwersji (101,61 zł = 21% budżetu),
  2. desktop i tablet z modyfikatorem 0,5 na 0: 3 131 impresji, 67 kliknięć, 0 konwersji, 12,40 zł,
  3. druga reklama RDA: kampania miała JEDNĄ — tak zginęła [DSA] 22.08 (jeden DISAPPROVED
     = 9 dni martwej kampanii = -71% kliknięć w Paid Search).

Teksty drugiej reklamy świadomie NIE zawierają słów "cło / rejestracja / dokumenty / VIN / umowa /
homologacja" — Google czyta je jako GOVERNMENT_DOCUMENTS_AND_OFFICIAL_SERVICES i przypina
APPROVED_LIMITED (mapa sekcja 7). Skrypt sprawdza to twardo przed wysyłką.

    python3 scripts/gads-rmkt-optymalizacja-2026-09-07.py            # dry-run
    python3 scripts/gads-rmkt-optymalizacja-2026-09-07.py --apply
"""
import json, os, sys, urllib.request, urllib.error
from datetime import datetime

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from gads_client import load, refresh  # noqa: E402

CID = "9506068500"
CAMPAIGN = 23897599362
AD_GROUP = 200662928841
API = json.load(open("/home/host476470/secrets/google/ads-config.json")).get("api_version", "v25")
APPLY = "--apply" in sys.argv
BACKUP = os.path.expanduser(f"~/backups/primaauto/rmkt-optymalizacja/{datetime.now():%Y-%m-%d}")

# 1. placementy: >=5 zł i zero konwersji w 30 dniach
PLACEMENTY = ["o2.pl", "fakt.pl", "businessinsider.com.pl", "abczdrowie.pl", "auto-swiat.pl",
              "moto.pl", "namasce.pl", "tvn24.pl", "niezalezna.pl", "olx.pl", "jastrzabpost.pl"]

# 3. druga kreacja — przekaz katalogowy (ten sam zwrot co [DG] 05.09)
NAGLOWKI = ["Auta z Chin w Polsce", "Setki modeli do wyboru", "Elektryki i hybrydy",
            "Zobacz pełną ofertę", "Ceny prosto z Chin"]
LONG = "Setki modeli z Chin — elektryki, hybrydy i spalinowe. Sprawdź aktualne ceny."
OPISY = ["Katalog aut z Chin: elektryczne, hybrydowe i spalinowe. Aktualne ceny i dostępność.",
         "Wybierz model z setek dostępnych. Zajmiemy się całym procesem za Ciebie.",
         "Nowe auta prosto od producenta. Sprawdź, ile kosztuje model, który Cię interesuje."]
ZAKAZANE = ["cło", "clo", "rejestracj", "dokument", "vin", "umow", "homologacj", "urząd", "urzed"]


def hdr():
    o, t, c = load()
    return {"Authorization": f"Bearer {refresh(o, t)}", "developer-token": c["developer_token"],
            "login-customer-id": CID, "Content-Type": "application/json"}


def gaql(q, h):
    u = f"https://googleads.googleapis.com/{API}/customers/{CID}/googleAds:searchStream"
    d = json.loads(urllib.request.urlopen(urllib.request.Request(
        u, data=json.dumps({"query": q}).encode(), headers=h)).read())
    return [r for b in (d or []) for r in b.get("results", [])]


def mutate(url_suffix, payload, h):
    u = f"https://googleads.googleapis.com/{API}/customers/{CID}/{url_suffix}"
    try:
        return json.loads(urllib.request.urlopen(urllib.request.Request(
            u, data=json.dumps(payload).encode(), headers=h)).read())
    except urllib.error.HTTPError as e:
        print(f"  BŁĄD {e.code}: {e.read().decode()[:900]}", file=sys.stderr)
        raise


# --- kontrola tekstów przed czymkolwiek ---
for t in NAGLOWKI + OPISY + [LONG]:
    low = t.lower()
    for z in ZAKAZANE:
        if z in low:
            sys.exit(f"ABORT: tekst zawiera zakazane słowo '{z}': {t}")
    if len(t) > 90:
        sys.exit(f"ABORT: tekst za długi ({len(t)}): {t}")
for t in NAGLOWKI:
    if len(t) > 30:
        sys.exit(f"ABORT: nagłówek >30 znaków ({len(t)}): {t}")
if len(LONG) > 90:
    sys.exit(f"ABORT: long headline >90 ({len(LONG)})")
print(f"kontrola tekstów OK ({len(NAGLOWKI)} nagłówków, {len(OPISY)} opisów)")

h = hdr()
os.makedirs(BACKUP, exist_ok=True)

# --- stan PRZED (dump) ---
przed = {
    "placementy": gaql(f"SELECT campaign_criterion.criterion_id, campaign_criterion.placement.url, "
                       f"campaign_criterion.negative FROM campaign_criterion "
                       f"WHERE campaign.id={CAMPAIGN} AND campaign_criterion.type='PLACEMENT'", h),
    "urzadzenia": gaql(f"SELECT campaign_criterion.criterion_id, campaign_criterion.device.type, "
                       f"campaign_criterion.bid_modifier FROM campaign_criterion "
                       f"WHERE campaign.id={CAMPAIGN} AND campaign_criterion.type='DEVICE'", h),
    "reklamy": gaql(f"SELECT ad_group_ad.ad.id, ad_group_ad.status FROM ad_group_ad "
                    f"WHERE campaign.id={CAMPAIGN}", h),
}
json.dump(przed, open(f"{BACKUP}/przed.json", "w"), ensure_ascii=False, indent=1)
print(f"dump przed -> {BACKUP}/przed.json")

istniejace = {r["campaignCriterion"].get("placement", {}).get("url", "").lower()
              for r in przed["placementy"]}
do_wykluczenia = [p for p in PLACEMENTY if p.lower() not in istniejace]
print(f"\n1. placementy: {len(do_wykluczenia)} do wykluczenia "
      f"({len(PLACEMENTY) - len(do_wykluczenia)} już wykluczonych)")

dev = {r["campaignCriterion"].get("device", {}).get("type"): r["campaignCriterion"]
       for r in przed["urzadzenia"]}
do_zerowania = [d for d in ("DESKTOP", "TABLET")
                if d in dev and float(dev[d].get("bidModifier", 1)) != 0]
print(f"2. urządzenia: {do_zerowania or 'nic do zmiany'}")

ile_reklam = len([r for r in przed["reklamy"] if r["adGroupAd"].get("status") != "REMOVED"])
print(f"3. reklamy w kampanii: {ile_reklam} -> {ile_reklam + 1}")

if not APPLY:
    print("\nDRY-RUN — nic nie wysłano. Dodaj --apply.")
    sys.exit(0)

# --- 1. wykluczenie placementów ---
if do_wykluczenia:
    ops = [{"create": {"campaign": f"customers/{CID}/campaigns/{CAMPAIGN}",
                       "negative": True, "placement": {"url": p}}} for p in do_wykluczenia]
    mutate("campaignCriteria:mutate", {"operations": ops, "partialFailure": False}, h)
    print(f"OK wykluczono {len(do_wykluczenia)} placementów")

# --- 2. desktop/tablet na 0 ---
if do_zerowania:
    ops = []
    for d in do_zerowania:
        ops.append({"update": {"resourceName": f"customers/{CID}/campaignCriteria/{CAMPAIGN}~{dev[d]['criterionId']}",
                               "bidModifier": 0},
                    "updateMask": "bidModifier"})
    mutate("campaignCriteria:mutate", {"operations": ops, "partialFailure": False}, h)
    print(f"OK modyfikator 0 na: {', '.join(do_zerowania)}")

# --- 3. druga reklama: klon assetów graficznych z istniejącej RDA ---
stara = gaql(f"SELECT ad_group_ad.ad.responsive_display_ad.marketing_images, "
             f"ad_group_ad.ad.responsive_display_ad.square_marketing_images, "
             f"ad_group_ad.ad.responsive_display_ad.logo_images, "
             f"ad_group_ad.ad.responsive_display_ad.square_logo_images, "
             f"ad_group_ad.ad.final_urls FROM ad_group_ad WHERE campaign.id={CAMPAIGN}", h)
rda = stara[0]["adGroupAd"]["ad"]["responsiveDisplayAd"]
ad = {"adGroup": f"customers/{CID}/adGroups/{AD_GROUP}",
      "status": "ENABLED",
      "ad": {"finalUrls": stara[0]["adGroupAd"]["ad"]["finalUrls"],
             "responsiveDisplayAd": {
                 "marketingImages": rda.get("marketingImages", []),
                 "squareMarketingImages": rda.get("squareMarketingImages", []),
                 "logoImages": rda.get("logoImages", []),
                 "squareLogoImages": rda.get("squareLogoImages", []),
                 "headlines": [{"text": t} for t in NAGLOWKI],
                 "longHeadline": {"text": LONG},
                 "descriptions": [{"text": t} for t in OPISY],
                 "businessName": "Prima-Auto"}}}
r = mutate("adGroupAds:mutate", {"operations": [{"create": ad}], "partialFailure": False}, h)
nowa = r["results"][0]["resourceName"]
print(f"OK nowa reklama: {nowa}")

# --- weryfikacja odczytem ---
po = gaql(f"SELECT ad_group_ad.ad.id, ad_group_ad.status, ad_group_ad.policy_summary.approval_status "
          f"FROM ad_group_ad WHERE campaign.id={CAMPAIGN}", h)
print("\nWERYFIKACJA — reklamy w kampanii:")
for x in po:
    a = x["adGroupAd"]
    print(f"  ad={a['ad']['id']:<14}{a.get('status',''):<10}{a.get('policySummary',{}).get('approvalStatus','—')}")
pl = gaql(f"SELECT campaign_criterion.placement.url FROM campaign_criterion "
          f"WHERE campaign.id={CAMPAIGN} AND campaign_criterion.type='PLACEMENT' "
          f"AND campaign_criterion.negative=true", h)
print(f"  wykluczonych placementów: {len(pl)}")
dv = gaql(f"SELECT campaign_criterion.device.type, campaign_criterion.bid_modifier "
          f"FROM campaign_criterion WHERE campaign.id={CAMPAIGN} AND campaign_criterion.type='DEVICE'", h)
for x in dv:
    c = x["campaignCriterion"]
    print(f"  {c.get('device',{}).get('type'):<16}modifier={c.get('bidModifier','brak (100%)')}")
