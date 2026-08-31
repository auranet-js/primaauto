#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Dokłada do [DG] Demand Gen reklamy wideo z filmami opublikowanymi 27–30.08.2026.

    python3 scripts/gads-dg-nowe-filmy-2026-08-31.py            # validate_only
    python3 scripts/gads-dg-nowe-filmy-2026-08-31.py --apply

Wzorzec skopiowany z działającej reklamy „DG wideo — Leopard 5 czarny" (818199638800):
5 nagłówków, 1 długi, 4 opisy, logo + CTA jako assety, para wideo poziome + Short.
Landingi wzięte z deep-linków w opisach filmów na YouTube i sprawdzone (200, cena zgodna).

Uwaga: [DG] to Demand Gen — inaczej niż kampania VIDEO, przyjmuje mutacje przez API.
"""
import json, sys, urllib.request, urllib.error
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from gads_client import load, refresh

CID = "9506068500"
AD_GROUP = "195605725661"          # DG — świeży ruch PL (YouTube)
LOGO = f"customers/{CID}/assets/391090171360"
CTA = f"customers/{CID}/assets/398997821677"

# (klucz, tytuł assetu, youtube_video_id)
WIDEO = [
    ("shark6_poz",  "Prima-Auto — BYD Shark 6 250 000 zł (poziome)", "zB_bIQPCm8c"),
    ("shark6_sh",   "Prima-Auto — BYD Shark 6 250 tys. (Short)",     "crWB8tGy9zs"),
    ("g318_poz",    "Prima-Auto — Deepal G318 196 000 zł (poziome)", "M40E56narKg"),
    ("g318_sh",     "Prima-Auto — Deepal G318 196 tys. (Short)",     "Ku-Sbs9J5As"),
    ("leo7_poz",    "Prima-Auto — BYD Leopard 7 234 000 zł (poziome)", "B9_Tm_ObRFg"),
    ("leo7_sh",     "Prima-Auto — BYD Leopard 7 234 tys. (Short)",   "60GjiT8HDIg"),
    ("z9gt_sh",     "Prima-Auto — Denza Z9 GT 291 tys. (Short)",     "kwHaiADCVps"),
]

REKLAMY = [
    {
        "name": "DG wideo — BYD Shark 6",
        "url": "https://primaauto.com.pl/oferta/byd-shark-6-premium-awd-2026-phev-new/",
        "videos": ["shark6_poz", "shark6_sh"],
        "headlines": ["BYD Shark 6 za 250 000 zł", "Pierwszy pickup PHEV w Polsce",
                      "Dostępny od ręki w Rzeszowie", "Pickup 4x4 z hybrydą plug-in",
                      "Zobacz na żywo w Rzeszowie"],
        "long": "BYD Shark 6 za 250 000 zł — pickup PHEV 4x4 dostępny od ręki w Rzeszowie",
        "desc": ["Obejrzyj auto na placu w Rzeszowie i odbierz od ręki. Pełna obsługa importu z Chin.",
                 "Bez czekania na sprowadzanie. Umów wizytę w Rzeszowie już dziś.",
                 "Pickup hybrydowy za 250 000 zł. Sprawdź wyposażenie i dostępność.",
                 "Auto stoi na placu w Rzeszowie. Bezpośredni importer aut z Chin."],
    },
    {
        "name": "DG wideo — Deepal G318",
        "url": "https://primaauto.com.pl/oferta/deepal-g318-2026-272010/",
        "videos": ["g318_poz", "g318_sh"],
        "headlines": ["Deepal G318 za 196 000 zł", "Terenówka PHEV z napędem 4x4",
                      "Dostępny od ręki w Rzeszowie", "Auto na placu w Rzeszowie",
                      "Zobacz na żywo w Rzeszowie"],
        "long": "Deepal G318 za 196 000 zł — terenowy SUV PHEV 4x4 od ręki w Rzeszowie",
        "desc": ["Obejrzyj auto na placu w Rzeszowie i odbierz od ręki. Pełna obsługa importu z Chin.",
                 "Bez czekania na sprowadzanie. Umów wizytę w Rzeszowie już dziś.",
                 "Terenówka hybrydowa za 196 000 zł. Sprawdź wyposażenie i dostępność.",
                 "Auto stoi na placu w Rzeszowie. Bezpośredni importer aut z Chin."],
    },
    {
        "name": "DG wideo — BYD Leopard 7",
        "url": "https://primaauto.com.pl/oferta/byd-leopard-7-tai-7-fcb-phev-2026-317106/",
        "videos": ["leo7_poz", "leo7_sh"],
        "headlines": ["BYD Leopard 7 za 234 000 zł", "Hybrydowy SUV z napędem 4x4",
                      "Dostępny od ręki w Rzeszowie", "Auto na placu w Rzeszowie",
                      "Zobacz na żywo w Rzeszowie"],
        "long": "BYD Leopard 7 za 234 000 zł — hybrydowy SUV 4x4 od ręki w Rzeszowie",
        "desc": ["Obejrzyj auto na placu w Rzeszowie i odbierz od ręki. Pełna obsługa importu z Chin.",
                 "Bez czekania na sprowadzanie. Umów wizytę w Rzeszowie już dziś.",
                 "Hybrydowy SUV za 234 000 zł. Sprawdź wyposażenie i dostępność.",
                 "Auto stoi na placu w Rzeszowie. Bezpośredni importer aut z Chin."],
    },
    {
        "name": "DG wideo — Denza Z9 GT",
        "url": "https://primaauto.com.pl/oferta/denza-z9-gt-dm-i-2025-314155/",
        "videos": ["z9gt_sh"],
        "headlines": ["Denza Z9 GT za 291 000 zł", "870 KM w hybrydzie plug-in",
                      "Dostępny od ręki w Rzeszowie", "Auto na placu w Rzeszowie",
                      "Zobacz na żywo w Rzeszowie"],
        "long": "Denza Z9 GT za 291 000 zł — 870 KM w hybrydzie plug-in, od ręki w Rzeszowie",
        "desc": ["Obejrzyj auto na placu w Rzeszowie i odbierz od ręki. Pełna obsługa importu z Chin.",
                 "Bez czekania na sprowadzanie. Umów wizytę w Rzeszowie już dziś.",
                 "Hybryda plug-in za 291 000 zł. Sprawdź wyposażenie i dostępność.",
                 "Auto stoi na placu w Rzeszowie. Bezpośredni importer aut z Chin."],
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
    """Kontrola długości ZANIM pójdzie do API — Demand Gen: nagłówek 40, długi 90, opis 90."""
    zle = []
    for r in REKLAMY:
        for h in r["headlines"]:
            if len(h) > 40: zle.append(f'nagłówek {len(h)}>40: {h}')
        if len(r["long"]) > 90: zle.append(f'długi nagłówek {len(r["long"])}>90: {r["long"]}')
        for d in r["desc"]:
            if len(d) > 90: zle.append(f'opis {len(d)}>90: {d}')
    return zle


def main():
    apply = "--apply" in sys.argv
    tryb = "APPLY" if apply else "VALIDATE_ONLY"
    zle = limity()
    if zle:
        print("STOP — przekroczone limity znaków:")
        for z in zle: print("  ", z)
        return 1

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
        print(f"[{tryb}] assety wideo: wszystkie 7 już istnieje")

    # 2) reklamy
    if not apply:
        print(f"[{tryb}] reklamy: walidacja pominięta dla nowych assetów "
              f"(nie mają jeszcze ID) — sprawdzone zostaną przy --apply")
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
