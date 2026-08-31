#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Druga karuzela w [DG] — auta z sesji 25.08 (decyzja Janka 2026-08-31).

    python3 scripts/gads-dg-karuzela2-2026-08-31.py            # validate_only
    python3 scripts/gads-dg-karuzela2-2026-08-31.py --apply

Dlaczego karuzela: to najlepsza reklama w [DG] — CPA 17 zł i CTR 4,81% wobec 27–36 zł
i 3,0% na reklamach wideo, przy zaledwie 18% wydatku kampanii (132 zł z 751 zł / 60 dni).
Druga karuzela daje Google drugi egzemplarz tego formatu do alokacji.

Zdjęcia: sesja z 2026-08-25 z Dysku (`Prima Auto/sesje/<auto>/`), te same, z których powstały
filmy na YouTube. Kadry: profil boczny w 1.91:1 (całe auto), ujęcie 3/4 w 1:1 (wypełnia kwadrat).
Pliki: ~/domains/auratest.pl/public_html/fe4f58fec53ctmp/primaauto-onlot-sesja-2026-08-31/

Trzy etapy, każdy przerywa przy błędzie: obrazy → karty → reklama.
"""
import base64, json, sys, urllib.request, urllib.error
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from gads_client import load, refresh

CID = "9506068500"
AD_GROUP = "195605725661"
LOGO = f"customers/{CID}/assets/391090171360"
KADRY = Path.home() / "domains/auratest.pl/public_html/fe4f58fec53ctmp/primaauto-onlot-sesja-2026-08-31"

AUTA = [
    {"plik": "Shark6-272113",     "naglowek": "BYD Shark 6 — 250 000 zł, od ręki",
     "url": "https://primaauto.com.pl/oferta/byd-shark-6-premium-awd-2026-phev-new/"},
    {"plik": "DeepalG318-272010", "naglowek": "Deepal G318 — 196 000 zł, od ręki",
     "url": "https://primaauto.com.pl/oferta/deepal-g318-2026-272010/"},
    {"plik": "Leopard7-317106",   "naglowek": "BYD Leopard 7 — 234 000 zł, od ręki",
     "url": "https://primaauto.com.pl/oferta/byd-leopard-7-tai-7-fcb-phev-2026-317106/"},
    {"plik": "DenzaZ9GT-314155",  "naglowek": "Denza Z9 GT — 291 000 zł, od ręki",
     "url": "https://primaauto.com.pl/oferta/denza-z9-gt-dm-i-2025-314155/"},
]

NAZWA_REKLAMY = "DG karuzela — nowa dostawa Rzeszów (sesja 25.08)"
NAGLOWEK = "Nowa dostawa na placu w Rzeszowie"
OPIS = "Terenówki, pickup i GT prosto z Chin. Zobacz ceny i dostępność od ręki."
CTA = "Więcej informacji"          # sprawdzone 24.07: „Dowiedz się więcej" jest odrzucane
URL_GLOWNY = "https://primaauto.com.pl/w-rzeszowie/"


def call(hdr, api, endpoint, body):
    req = urllib.request.Request(
        f"https://googleads.googleapis.com/{api}/customers/{CID}/{endpoint}:mutate",
        data=json.dumps(body).encode(), headers=hdr)
    try:
        return True, json.load(urllib.request.urlopen(req))
    except urllib.error.HTTPError as e:
        return False, e.read().decode()[:1500]


def main():
    apply = "--apply" in sys.argv
    tryb = "APPLY" if apply else "VALIDATE_ONLY"

    for a in AUTA:                                     # limity i obecność plików PRZED API
        if len(a["naglowek"]) > 40:
            print(f'STOP — nagłówek karty {len(a["naglowek"])}>40: {a["naglowek"]}'); return 1
        for suf in ("landscape", "square"):
            if not (KADRY / f'{a["plik"]}-{suf}.jpg').exists():
                print(f'STOP — brak pliku {a["plik"]}-{suf}.jpg w {KADRY}'); return 1
    if len(NAGLOWEK) > 40:
        print(f"STOP — nagłówek reklamy {len(NAGLOWEK)}>40"); return 1
    if len(OPIS) > 90:
        print(f"STOP — opis {len(OPIS)}>90"); return 1

    oauth, tokens, cfg = load()
    api = cfg.get("api_version", "v25")
    hdr = {"Authorization": f"Bearer {refresh(oauth, tokens)}",
           "developer-token": cfg["developer_token"],
           "login-customer-id": CID, "Content-Type": "application/json"}

    # 1) obrazy
    ops, klucze = [], []
    for a in AUTA:
        for suf, ratio in (("landscape", "1.91:1"), ("square", "1:1")):
            f = KADRY / f'{a["plik"]}-{suf}.jpg'
            ops.append({"create": {"name": f'onlot {a["plik"]} sesja0825 [{ratio}]'[:120],
                                   "type": "IMAGE",
                                   "imageAsset": {"data": base64.b64encode(f.read_bytes()).decode()}}})
            klucze.append((a["plik"], suf))
    ok, out = call(hdr, api, "assets", {"operations": ops, "validateOnly": not apply})
    print(f"[{tryb}] obrazy ({len(ops)}): {'OK' if ok else 'BŁĄD'}")
    if not ok:
        print(out); return 1
    if not apply:
        print(f"[{tryb}] karty i reklama — walidacja możliwa dopiero po utworzeniu obrazów")
        for a in AUTA:
            print(f'       karta: {a["naglowek"]:36} → {a["url"]}')
        print(f'       reklama: „{NAZWA_REKLAMY}" / „{NAGLOWEK}" / CTA „{CTA}" → {URL_GLOWNY}')
        return 0
    obrazy = {k: r["resourceName"] for k, r in zip(klucze, out["results"])}

    # 2) karty
    ops = [{"create": {
        "name": f'karta {a["plik"]} sesja0825'[:120],
        "finalUrls": [a["url"]],
        "demandGenCarouselCardAsset": {
            "marketingImage": obrazy[(a["plik"], "landscape")],
            "squareMarketingImage": obrazy[(a["plik"], "square")],
            "headline": a["naglowek"]},
    }} for a in AUTA]
    ok, out = call(hdr, api, "assets", {"operations": ops})
    print(f"[{tryb}] karty ({len(ops)}): {'OK' if ok else 'BŁĄD'}")
    if not ok:
        print(out); return 1
    karty = [r["resourceName"] for r in out["results"]]

    # 3) reklama
    ok, out = call(hdr, api, "adGroupAds", {"operations": [{"create": {
        "adGroup": f"customers/{CID}/adGroups/{AD_GROUP}",
        "status": "ENABLED",
        "ad": {"name": NAZWA_REKLAMY, "finalUrls": [URL_GLOWNY],
               "demandGenCarouselAd": {
                   "businessName": "Prima-Auto",
                   "logoImage": {"asset": LOGO},
                   "headline": {"text": NAGLOWEK},
                   "description": {"text": OPIS},
                   "callToActionText": CTA,
                   "carouselCards": [{"asset": k} for k in karty]}},
    }}]})
    print(f"[{tryb}] reklama karuzelowa: {'OK ' + out['results'][0]['resourceName'] if ok else 'BŁĄD'}")
    if not ok:
        print(out); return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
