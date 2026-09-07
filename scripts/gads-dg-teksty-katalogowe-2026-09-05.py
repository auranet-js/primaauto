#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Przepisuje kreacje [DG] Denza N9 i Lynk & Co 900 na przekaz katalogowy.

    python3 scripts/gads-dg-teksty-katalogowe-2026-09-05.py            # validate_only
    python3 scripts/gads-dg-teksty-katalogowe-2026-09-05.py --apply

Decyzja Janka 05.09: w tekstach nie ma cen ani konkretnych egzemplarzy, tylko trzy zwroty
katalogowe — „auta dostępne od ręki", „auta w drodze do Polski", „przeglądaj katalog ofert
do importu". Powód: cena rotuje (cron przelicza co 15 min) i przy kilkunastu sztukach pod
jednym filmem trzeba by ją pilnować ręcznie w trzech miejscach kreacji; „od ręki" przypięte
do jednej sztuki kłamie w dniu, w którym sztuka zejdzie z placu; a landing na pojedynczą
ofertę po rotacji (78% ofert znika w 7 tygodni) i tak kończy jako przekierowanie na hub.
Film pokazuje model, nie egzemplarz — tekst i landing mają mówić to samo.

Landing N9 przechodzi z oferty 270999 na hub modelu (20 aut). Lynk & Co już celował w hub.
Obie reklamy utworzone tego samego dnia, w recenzji, zero wyświetleń — reset uczenia zerowy.
Mockupy zaakceptowane: auratest.pl/fe4f58fec53ctmp/primaauto-dg-mockupy-2026-09-05.html
"""
import json, sys, urllib.request, urllib.error
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from gads_client import load, refresh

CID = "9506068500"
ZAKAZANE = ("homologacj", "cło", "clo ", "rejestracj", "dokument", "vin", "umow")

ZMIANY = [
    {
        "ad": "823595812283",
        "opis": "DG wideo — Denza N9",
        "model": "Denza N9",
        "url": "https://primaauto.com.pl/samochody/denza/n9-dm-i/",
        "headlines": ["Denza N9 — sprawdź oferty", "Auta dostępne od ręki",
                      "Auta w drodze do Polski", "Przeglądaj katalog ofert do importu",
                      "Import aut z Chin — Rzeszów"],
        "long": "Denza N9 — auta dostępne od ręki, w drodze do Polski i w katalogu do importu",
        "desc": ["Sprawdź auta dostępne od ręki na placu w Rzeszowie. Pełna obsługa importu z Chin.",
                 "Przeglądaj katalog ofert do importu. Zapytaj o dostępność i termin.",
                 "Auta od ręki i w drodze do Polski. Bezpośredni importer aut z Chin.",
                 "Umów oglądanie w Rzeszowie. Prowadzimy cały proces importu z Chin."],
    },
    {
        "ad": "823595812541",
        "opis": "DG wideo — Lynk & Co 900",
        "model": "Lynk & Co 900",
        "url": "https://primaauto.com.pl/samochody/lynk-co/900/",
        "headlines": ["Lynk & Co 900 — sprawdź oferty", "Auta w drodze do Polski",
                      "Auta dostępne od ręki", "Przeglądaj katalog ofert do importu",
                      "Import aut z Chin — Rzeszów"],
        "long": "Lynk & Co 900 — auta w drodze do Polski i oferty w katalogu do importu",
        "desc": ["Sprawdź auta w drodze do Polski. Pełna obsługa importu z Chin.",
                 "Przeglądaj katalog ofert do importu. Zapytaj o dostępność i termin.",
                 "Auta od ręki i w drodze do Polski. Bezpośredni importer aut z Chin.",
                 "Umów oglądanie w Rzeszowie. Prowadzimy cały proces importu z Chin."],
    },
]

MASK = ("final_urls,demand_gen_video_responsive_ad.headlines,"
        "demand_gen_video_responsive_ad.long_headlines,"
        "demand_gen_video_responsive_ad.descriptions")


def kontrola():
    """Limity Demand Gen (nagłówek 40, długi 90, opis 90), słowa z sekcji 7 mapy,
    plus zakaz liczb w nagłówkach i opisach poza nazwą modelu — po to ta zmiana jest robiona."""
    zle = []
    for z in ZMIANY:
        for h in z["headlines"]:
            if len(h) > 40: zle.append(f'nagłówek {len(h)}>40: {h}')
            if any(c.isdigit() for c in h.replace(z["model"], "")):
                zle.append(f'liczba w nagłówku poza nazwą modelu: {h}')
        if len(z["long"]) > 90: zle.append(f'długi nagłówek {len(z["long"])}>90: {z["long"]}')
        for d in z["desc"]:
            if len(d) > 90: zle.append(f'opis {len(d)}>90: {d}')
            if any(c.isdigit() for c in d.replace(z["model"], "")):
                zle.append(f'liczba w opisie poza nazwą modelu: {d}')
        for t in z["headlines"] + [z["long"]] + z["desc"]:
            for s in ZAKAZANE:
                if s in t.lower(): zle.append(f'słowo „{s}" w: {t}')
    return zle


def main():
    apply = "--apply" in sys.argv
    tryb = "APPLY" if apply else "VALIDATE_ONLY"
    zle = kontrola()
    if zle:
        print("STOP — kontrola tekstów:")
        for z in zle: print("  ", z)
        return 1
    print(f"[{tryb}] kontrola tekstów: OK (limity, słowa z sekcji 7, brak liczb w nagłówkach)")

    oauth, tokens, cfg = load()
    api = cfg.get("api_version", "v25")
    hdr = {"Authorization": f"Bearer {refresh(oauth, tokens)}",
           "developer-token": cfg["developer_token"],
           "login-customer-id": CID, "Content-Type": "application/json"}

    ops = [{"update": {
                "resourceName": f"customers/{CID}/ads/{z['ad']}",
                "finalUrls": [z["url"]],
                "demandGenVideoResponsiveAd": {
                    "headlines": [{"text": t} for t in z["headlines"]],
                    "longHeadlines": [{"text": z["long"]}],
                    "descriptions": [{"text": t} for t in z["desc"]],
                }},
            "updateMask": MASK} for z in ZMIANY]

    req = urllib.request.Request(
        f"https://googleads.googleapis.com/{api}/customers/{CID}/ads:mutate",
        data=json.dumps({"operations": ops, "validateOnly": not apply}).encode(), headers=hdr)
    try:
        out = json.load(urllib.request.urlopen(req))
    except urllib.error.HTTPError as e:
        print("BŁĄD:", e.read().decode()[:2000]); return 1
    for z, r in zip(ZMIANY, out.get("results", [{}] * len(ZMIANY))):
        print(f'[{tryb}] {z["opis"]}: OK {r.get("resourceName","(walidacja)")} → {z["url"]}')
    return 0


if __name__ == "__main__":
    sys.exit(main())
