#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""[Brand] — sterowany udział w wyświetleniach, druga reklama, wykluczenie (decyzja Janka 31.08).

    python3 scripts/gads-brand-optymalizacja-2026-08-31.py            # validate_only
    python3 scripts/gads-brand-optymalizacja-2026-08-31.py --apply

DLACZEGO (pomiar dzienny 05–20.08, nie miesięczna średnia):
  05.08  11,08 zł  udział  87%  utracone przez budżet   0%
  08.08  11,60 zł  udział  14%  utracone przez budżet  85%   ← dzień fali brandowej
  12.08   9,82 zł  udział  10%  utracone przez budżet  90%
  16.08   6,78 zł  udział 100%  utracone przez budżet   0%   ← nie ma czego kupować
Udział skacze od 10% do 100%, bo kampania stoi na MANUAL_CPC bez eCPC — nic nie pilnuje udziału,
a budżet jest rozprowadzany przez dławienie Google'a. Sam budżet tego nie naprawia: w dni ciszy
nie da się go wydać, w dni fali sufit wiąże.

TRZY ZMIANY:
  1. strategia MANUAL_CPC → TARGET_IMPRESSION_SHARE (górna pozycja, cel 90%, sufit CPC 1,50 zł)
  2. druga reklama — kampania miała JEDNĄ; gdyby dostała DISAPPROVED, brand umiera (tak zginęła
     główna reklama [DSA] 22.08). Tekst świadomie bez „cło / rejestracja / dokumenty / VIN / umowa",
     czyli bez słownictwa, które Google czyta jako usługi urzędowe — przy okazji test tezy z sekcji 7.
  3. wykluczenie „bełchatów" — 31 zł za 4 kliknięcia i 0 konwersji na obcej firmie „Prima".
     Dołącza do istniejących: warka, łask, miechów (ten sam wzorzec).

PO WYKONANIU sprawdź `campaign_conversion_goal` tej kampanii — zmiana strategii przywraca domyślny
zestaw celów konta, w którym wracają cele YouTube (patrz mapa sekcja 3a). Skrypt sprawdza to sam.
"""
import json, sys, urllib.request, urllib.error
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from gads_client import load, refresh

CID = "9506068500"
KAMPANIA = "23779860635"
GRUPA = "195903580956"

NAGLOWKI = [
    ("Prima-Auto — Oficjalna Strona", "HEADLINE_1"),
    ("Prima-Auto — Auta z Chin", None),
    ("Bezpośredni Importer z Chin", None),
    ("Auta z Chin od Ręki", None),
    ("Zobacz Auta na Placu", None),
    ("Ponad 3000 Ofert z Chin", None),
    ("Plac w Rzeszowie", None),
    ("Sprawdź Ceny i Dostępność", None),
    ("Zamów Online w 1 Klik", None),
    ("Wyceń Import w 24h", None),
    ("Denza, Zeekr, Voyah, AITO", None),
    ("Auta Elektryczne i PHEV", None),
    ("Umów Wizytę w Rzeszowie", None),
    ("Nowa Dostawa na Placu", None),
    ("Prima-Auto — Twój Importer", None),
]
OPISY = [
    "Prima-Auto — bezpośredni importer aut z Chin. Ceny w ogłoszeniach, zamów online.",
    "Auta na placu w Rzeszowie i w drodze do Polski. Sprawdź ceny i dostępność.",
    "Ponad 3000 ofert z Chin. Wybierasz Ty, sprowadzamy my. Wycena w 24 godziny.",
    "Obejrzyj auto na żywo w Rzeszowie albo zamów online. Dostawa w całej Polsce.",
]
ZAKAZANE = ("cło", "cła", "rejestrac", "homologac", "dokument", "vin", "umow", "celn")


def call(hdr, api, endpoint, body):
    req = urllib.request.Request(
        f"https://googleads.googleapis.com/{api}/customers/{CID}/{endpoint}:mutate",
        data=json.dumps(body).encode(), headers=hdr)
    try:
        return True, json.load(urllib.request.urlopen(req))
    except urllib.error.HTTPError as e:
        return False, e.read().decode()[:1500]


def kontrola():
    zle = []
    for t, _ in NAGLOWKI:
        if len(t) > 30:
            zle.append(f"nagłówek {len(t)}>30: {t}")
    for t in OPISY:
        if len(t) > 90:
            zle.append(f"opis {len(t)}>90: {t}")
    for t in [n for n, _ in NAGLOWKI] + OPISY:
        for z in ZAKAZANE:
            if z in t.lower():
                zle.append("slownictwo urzedowe (%s) w: %s" % (z, t))
    return zle


def main():
    apply = "--apply" in sys.argv
    tryb = "APPLY" if apply else "VALIDATE_ONLY"
    zle = kontrola()
    if zle:
        print("STOP — kontrola tekstów:")
        for z in zle:
            print("  ", z)
        return 1

    oauth, tokens, cfg = load()
    api = cfg.get("api_version", "v25")
    hdr = {"Authorization": f"Bearer {refresh(oauth, tokens)}",
           "developer-token": cfg["developer_token"],
           "login-customer-id": CID, "Content-Type": "application/json"}

    # 1) wykluczenie
    ok, out = call(hdr, api, "campaignCriteria", {"operations": [{"create": {
        "campaign": f"customers/{CID}/campaigns/{KAMPANIA}",
        "negative": True,
        "keyword": {"text": "bełchatów", "matchType": "PHRASE"}}}],
        "validateOnly": not apply})
    print("[%s] wykluczenie belchatow: %s" % (tryb, "OK" if ok else "BLAD"))
    if not ok:
        print(out); return 1

    # 2) druga reklama
    ok, out = call(hdr, api, "adGroupAds", {"operations": [{"create": {
        "adGroup": f"customers/{CID}/adGroups/{GRUPA}",
        "status": "ENABLED",
        "ad": {"finalUrls": ["https://primaauto.com.pl/"],
               "responsiveSearchAd": {
                   "headlines": [{"text": t, **({"pinnedField": p} if p else {})} for t, p in NAGLOWKI],
                   "descriptions": [{"text": t} for t in OPISY]}}}}],
        "validateOnly": not apply})
    print(f"[{tryb}] druga reklama (15 nagłówków, 4 opisy, bez słownictwa urzędowego): "
          f"{'OK' if ok else 'BŁĄD'}")
    if not ok:
        print(out); return 1

    # 3) strategia
    ok, out = call(hdr, api, "campaigns", {"operations": [{
        "update": {"resourceName": f"customers/{CID}/campaigns/{KAMPANIA}",
                   "biddingStrategyType": "TARGET_IMPRESSION_SHARE",
                   "targetImpressionShare": {"location": "TOP_OF_PAGE",
                                             "locationFractionMicros": 900_000,
                                             "cpcBidCeilingMicros": 1_500_000}},
        "updateMask": "target_impression_share.location,"
                      "target_impression_share.location_fraction_micros,"
                      "target_impression_share.cpc_bid_ceiling_micros"}],
        "validateOnly": not apply})
    print(f"[{tryb}] strategia → docelowy udział (górna pozycja 90%, sufit CPC 1,50 zł): "
          f"{'OK' if ok else 'BŁĄD'}")
    if not ok:
        print(out); return 1

    if not apply:
        return 0

    # 4) kontrola celów — zmiana strategii przywraca domyślny zestaw konta (mapa sekcja 3a)
    req = urllib.request.Request(
        f"https://googleads.googleapis.com/{api}/customers/{CID}/googleAds:search",
        data=json.dumps({"query":
            "SELECT campaign.name, campaign_conversion_goal.category, campaign_conversion_goal.origin, "
            "campaign_conversion_goal.biddable FROM campaign_conversion_goal "
            f"WHERE campaign.id = {KAMPANIA}"}).encode(), headers=hdr)
    cele = [r["campaignConversionGoal"] for r in json.load(urllib.request.urlopen(req)).get("results", [])
            if r["campaignConversionGoal"].get("biddable")]
    yt = [c for c in cele if "YOUTUBE" in str(c.get("origin", "")) or "YOUTUBE" in str(c.get("category", ""))]
    print("\n[kontrola celów] biddable po zmianie: "
          + ", ".join(f'{c.get("category")}/{c.get("origin")}' for c in cele))
    print("[kontrola celów] " + ("!! WRÓCIŁY CELE YOUTUBE — uruchom scripts/ads-zawez-cele.py --apply"
                                 if yt else "ok, żadnego celu YouTube"))
    return 0


if __name__ == "__main__":
    sys.exit(main())
