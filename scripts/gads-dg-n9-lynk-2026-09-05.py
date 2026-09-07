#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Dokłada do [DG] Demand Gen dwie brakujące kreacje fali 2: Denza N9 i Lynk & Co 900.

    python3 scripts/gads-dg-n9-lynk-2026-09-05.py            # validate_only
    python3 scripts/gads-dg-n9-lynk-2026-09-05.py --apply

Filmy obu aut poszły na kanał 31.08–02.09, ale nie miały assetów na koncie Ads, więc jako
jedyne z fali 2 nie rotowały: N9 146 + 43 wyświetlenia, Lynk 532 + 29 — wobec 1 500–4 200
na autach z kreacją. Wzorzec i decyzja „tylko pion" jak w gads-dg-nowe-filmy-2026-08-31.py.

Fakty w tekstach sprawdzone w bazie (wp7j_postmeta), nie przepisane z tytułu filmu:
  N9 270999 — price 344000, horse_power 925, fuel phev, drive awd, seat_count 6,
              _asiaauto_reservation_status = on_lot (na placu), landing 200.
  Lynk & Co  — ŻADNA sztuka nie ma on_lot (3 × in_transit, reszta katalogowa), więc narracja
              „już w drodze", nie „od ręki"; landing to hub /samochody/lynk-co/900/ (200, 20 aut).
              Bez ceny i bez mocy w tekstach — rotują (cena wejścia 242 tys. w dniu filmu,
              254 tys. dziś; 721 KM w 1.5T wobec 734 KM w 2.0T).
Żadne z tekstów nie zawiera „homologacja / cło / rejestracja / dokumenty / VIN / umowa"
(mapa-kampanii.md sekcja 7) — twarda kontrola w limity().
"""
import json, sys, urllib.request, urllib.error
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from gads_client import load, refresh

CID = "9506068500"
AD_GROUP = "195605725661"          # DG — świeży ruch PL (YouTube)
LOGO = f"customers/{CID}/assets/391090171360"
CTA = f"customers/{CID}/assets/398997821677"
ZAKAZANE = ("homologacj", "cło", "clo ", "rejestracj", "dokument", "vin", "umow")

# (klucz, tytuł assetu, youtube_video_id) — pion 9:16, zweryfikowane przez player.embedWidth
WIDEO = [
    ("n9_sh",   "Prima-Auto — Denza N9 344 tys. (Short)", "ZeZGJ8-iA9I"),
    ("lynk_sh", "Prima-Auto — Lynk & Co 900 (Short)",     "1xS5HBbJgXU"),
]

REKLAMY = [
    {
        "name": "DG wideo — Denza N9",
        "url": "https://primaauto.com.pl/oferta/denza-n9-dm-i-2026-270999/",
        "videos": ["n9_sh"],
        "headlines": ["Denza N9 za 344 000 zł", "925 KM w hybrydzie plug-in",
                      "Dostępny od ręki w Rzeszowie", "6-osobowy SUV z napędem 4x4",
                      "Zobacz na żywo w Rzeszowie"],
        "long": "Denza N9 za 344 000 zł — 925 KM, 6-osobowy SUV, od ręki w Rzeszowie",
        "desc": ["Obejrzyj auto na placu w Rzeszowie i odbierz od ręki. Pełna obsługa importu z Chin.",
                 "Bez czekania na sprowadzanie. Umów wizytę w Rzeszowie już dziś.",
                 "Flagowy SUV za 344 000 zł. Sprawdź wyposażenie i dostępność.",
                 "Auto stoi na placu w Rzeszowie. Bezpośredni importer aut z Chin."],
    },
    {
        "name": "DG wideo — Lynk & Co 900",
        "url": "https://primaauto.com.pl/samochody/lynk-co/900/",
        "videos": ["lynk_sh"],
        "headlines": ["Lynk & Co 900 — już w drodze", "6-osobowy SUV z napędem 4x4",
                      "Hybryda plug-in prosto z Chin", "Auta kupione, jadą do Polski",
                      "Sprawdź dostępne egzemplarze"],
        "long": "Lynk & Co 900 — 6-osobowy SUV plug-in, egzemplarze już w drodze do Polski",
        "desc": ["Egzemplarze już kupione i w drodze do Polski. Pełna obsługa importu z Chin.",
                 "Zarezerwuj auto przed przyjazdem do Rzeszowa. Zapytaj o termin dostawy.",
                 "6-osobowy SUV w hybrydzie plug-in. Sprawdź wersje i wyposażenie.",
                 "Bezpośredni importer aut z Chin. Rzeszów — plac i obsługa na miejscu."],
    },
]


def call(hdr, api, endpoint, body):
    req = urllib.request.Request(
        f"https://googleads.googleapis.com/{api}/customers/{CID}/{endpoint}:mutate",
        data=json.dumps(body).encode(), headers=hdr)
    try:
        return True, json.load(urllib.request.urlopen(req))
    except urllib.error.HTTPError as e:
        return False, e.read().decode()[:1500]


def limity():
    """Długości (Demand Gen: nagłówek 40, długi 90, opis 90) + słowa z sekcji 7 mapy."""
    zle = []
    for r in REKLAMY:
        teksty = r["headlines"] + [r["long"]] + r["desc"]
        for h in r["headlines"]:
            if len(h) > 40: zle.append(f'nagłówek {len(h)}>40: {h}')
        if len(r["long"]) > 90: zle.append(f'długi nagłówek {len(r["long"])}>90: {r["long"]}')
        for d in r["desc"]:
            if len(d) > 90: zle.append(f'opis {len(d)}>90: {d}')
        for t in teksty:
            for z in ZAKAZANE:
                if z in t.lower(): zle.append(f'słowo „{z}" w: {t}')
    return zle


def main():
    apply = "--apply" in sys.argv
    tryb = "APPLY" if apply else "VALIDATE_ONLY"
    zle = limity()
    if zle:
        print("STOP — kontrola tekstów:")
        for z in zle: print("  ", z)
        return 1
    print(f"[{tryb}] kontrola tekstów: OK (limity znaków + słowa z sekcji 7)")

    oauth, tokens, cfg = load()
    api = cfg.get("api_version", "v25")
    hdr = {"Authorization": f"Bearer {refresh(oauth, tokens)}",
           "developer-token": cfg["developer_token"],
           "login-customer-id": CID, "Content-Type": "application/json"}

    # 1) assety wideo — istniejące dociągamy, brakujące tworzymy
    znane = {}
    req = urllib.request.Request(
        f"https://googleads.googleapis.com/{api}/customers/{CID}/googleAds:search",
        data=json.dumps({"query": "SELECT asset.resource_name, asset.youtube_video_asset.youtube_video_id "
                                  "FROM asset WHERE asset.type = 'YOUTUBE_VIDEO'"}).encode(), headers=hdr)
    for r in json.load(urllib.request.urlopen(req)).get("results", []):
        znane[r["asset"]["youtubeVideoAsset"]["youtubeVideoId"]] = r["asset"]["resourceName"]

    brak = [(k, n, v) for k, n, v in WIDEO if v not in znane]
    mapa = {k: znane[v] for k, n, v in WIDEO if v in znane}
    if brak:
        ok, out = call(hdr, api, "assets", {
            "operations": [{"create": {"name": n, "youtubeVideoAsset": {"youtubeVideoId": v}}}
                           for k, n, v in brak],
            "validateOnly": not apply})
        print(f"[{tryb}] assety wideo ({len(brak)} nowych): {'OK' if ok else 'BŁĄD'}")
        if not ok:
            print(out); return 1
        if apply:
            for (k, n, v), res in zip(brak, out["results"]):
                mapa[k] = res["resourceName"]
                print(f"       {v} -> {res['resourceName']}")
        else:
            for k, n, v in brak:
                mapa[k] = f"customers/{CID}/assets/PLACEHOLDER"
    else:
        print(f"[{tryb}] assety wideo: wszystkie {len(WIDEO)} już istnieją")

    # 2) reklamy — walidacja tekstów bez istniejących assetów nie ma sensu (brak ID)
    if not apply:
        print(f"[{tryb}] reklamy: pełna walidacja przy --apply (nowe assety nie mają jeszcze ID)")
        for r in REKLAMY:
            print(f'       {r["name"]:26} wideo={len(r["videos"])} → {r["url"]}')
        return 0

    for r in REKLAMY:
        ok, out = call(hdr, api, "adGroupAds", {
            "operations": [{"create": {
                "adGroup": f"customers/{CID}/adGroups/{AD_GROUP}",
                "status": "ENABLED",
                "ad": {
                    "name": r["name"],
                    "finalUrls": [r["url"]],
                    "demandGenVideoResponsiveAd": {
                        "headlines": [{"text": t} for t in r["headlines"]],
                        "longHeadlines": [{"text": r["long"]}],
                        "descriptions": [{"text": t} for t in r["desc"]],
                        "videos": [{"asset": mapa[k]} for k in r["videos"]],
                        "logoImages": [{"asset": LOGO}],
                        "businessName": {"text": "Prima-Auto"},
                        "callToActions": [{"asset": CTA}],
                    },
                },
            }}]})
        print(f'[{tryb}] {r["name"]}: {"OK " + out["results"][0]["resourceName"] if ok else "BŁĄD"}')
        if not ok:
            print(out)
    return 0


if __name__ == "__main__":
    sys.exit(main())
