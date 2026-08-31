#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Odbudowa drugiej reklamy w [DSA] — martwa od 22.08 (decyzja Janka 2026-08-31).

    python3 scripts/gads-dsa-odbuduj-reklame-2026-08-31.py            # validate_only
    python3 scripts/gads-dsa-odbuduj-reklame-2026-08-31.py --apply

Kontekst: ad 816552895918 dostał DISAPPROVED i od 22.08 ma 0 wyświetleń dziennie
(wcześniej 126–276/dz. — była to GŁÓWNA reklama kampanii: 16 247 wyświetleń / 60 dni
i CTR 7,6% wobec 6,3% drugiej). Kampania jedzie odtąd na jednej reklamie.

Nowa reklama powtarza przekaz „rezerwacja + weryfikacja przed zakupem", ale bez słowa
„homologacja" — jedynej frazy, która w tej parze stoi po stronie odrzuconej. Świeży
egzemplarz = świeża recenzja; przy okazji to test tezy, że etykieta idzie za egzemplarzem,
a nie za tekstem (patrz docs/ads/mapa-kampanii.md sekcja 7).

Duplikat 1:1 zaakceptowanej reklamy nie przeszedłby — Google odrzuca identyczne reklamy
w tej samej grupie.
"""
import json, sys, urllib.request, urllib.error
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from gads_client import load, refresh

CID = "9506068500"
AD_GROUP = "197286896339"
MARTWA = "816552895918"
OPIS_1 = "Zarezerwuj teraz — auto sprawdzamy przed zakupem i sprowadzamy do Polski dla Ciebie."
OPIS_2 = "Indywidualne zamówienie, dostawa pod dom, gwarancja i pełne wsparcie."


def call(hdr, api, body):
    req = urllib.request.Request(
        f"https://googleads.googleapis.com/{api}/customers/{CID}/adGroupAds:mutate",
        data=json.dumps(body).encode(), headers=hdr)
    try:
        return True, json.load(urllib.request.urlopen(req))
    except urllib.error.HTTPError as e:
        return False, e.read().decode()[:1200]


def main():
    for opis in (OPIS_1, OPIS_2):
        if len(opis) > 90:
            print(f"STOP — opis {len(opis)}>90 znaków: {opis}")
            return 1

    apply = "--apply" in sys.argv
    tryb = "APPLY" if apply else "VALIDATE_ONLY"
    oauth, tokens, cfg = load()
    api = cfg.get("api_version", "v25")
    hdr = {"Authorization": f"Bearer {refresh(oauth, tokens)}",
           "developer-token": cfg["developer_token"],
           "login-customer-id": CID, "Content-Type": "application/json"}

    ok, out = call(hdr, api, {
        "operations": [{"create": {
            "adGroup": f"customers/{CID}/adGroups/{AD_GROUP}",
            "status": "ENABLED",
            "ad": {"expandedDynamicSearchAd": {"description": OPIS_1, "description2": OPIS_2}},
        }}],
        "validateOnly": not apply})
    print(f"[{tryb}] nowa reklama DSA: {'OK' if ok else 'BŁĄD'} {out if not ok else out.get('results')}")
    if not ok:
        return 1

    ok, out = call(hdr, api, {
        "operations": [{"remove": f"customers/{CID}/adGroupAds/{AD_GROUP}~{MARTWA}"}],
        "validateOnly": not apply})
    print(f"[{tryb}] usunięcie martwej {MARTWA}: {'OK' if ok else 'BŁĄD'} {out if not ok else ''}")
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
