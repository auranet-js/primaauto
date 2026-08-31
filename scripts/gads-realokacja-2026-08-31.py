#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Realokacja budżetów Google Ads — decyzja Janka 2026-08-31 (sesja audytu Ads).

    python3 scripts/gads-realokacja-2026-08-31.py            # validate_only
    python3 scripts/gads-realokacja-2026-08-31.py --apply

Co robi (suma konta bez zmian, 137 zł/dz):
  PAUZA  [SKAG-2] W drodze   — 748 zł/30 dni, 0 konwersji przez 8 tygodni
  PAUZA  [VID] Placementy    — 444 zł/30 dni, 0 kontaktów, CTR 0,11%, CPC 12,32 zł
         (kampania VIDEO jest zamknięta dla API — MUTATE_NOT_ALLOWED na campaigns:mutate,
          zweryfikowane 31.08. Obejście: pauza obu reklam przez adGroupAds:mutate, co
          zatrzymuje wyświetlanie. Sama kampania zostaje ENABLED z budżetem 15 zł, który
          nie ma czego wydawać — do właściwej pauzy w panelu.)
  BUDŻET [Brand]  10 → 25 zł — traci 57% wyświetleń przez budżet przy CPC 0,41 zł
  BUDŻET [DG]     20 → 45 zł — CPA 10 zł w tygodniu 17–23.08, jedyny nośnik wideo

Gotcha: kampania VIDEO jest w dużej części zamknięta dla Ads API (MUTATE_NOT_ALLOWED).
Jeśli pauza [VID] przejdzie na walidacji — dobrze; jeśli nie, trzeba ją zapauzować w UI.
"""
import json, sys, urllib.request, urllib.error
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from gads_client import load, refresh

CID = "9506068500"
PAUZY = [("23803851566", "[SKAG-2] W drodze")]
# [VID] — kampania VIDEO nie przyjmuje campaigns:mutate, pauzujemy jej reklamy
VID_REKLAMY = [("205431496984", "818295409418"), ("205431496984", "818352286442")]
BUDZETY = [("15529972480", 25_000_000, "[Brand] 10 → 25 zł"),
           ("15746510158", 45_000_000, "[DG] 20 → 45 zł")]


def call(hdr, api, endpoint, body):
    req = urllib.request.Request(
        f"https://googleads.googleapis.com/{api}/customers/{CID}/{endpoint}:mutate",
        data=json.dumps(body).encode(), headers=hdr)
    try:
        return True, json.load(urllib.request.urlopen(req))
    except urllib.error.HTTPError as e:
        return False, e.read().decode()[:900]


def main():
    apply = "--apply" in sys.argv
    oauth, tokens, cfg = load()
    api = cfg.get("api_version", "v25")
    hdr = {"Authorization": f"Bearer {refresh(oauth, tokens)}",
           "developer-token": cfg["developer_token"],
           "login-customer-id": CID, "Content-Type": "application/json"}
    tryb = "APPLY" if apply else "VALIDATE_ONLY"

    for cid, nazwa in PAUZY:                      # osobno, żeby blokada VIDEO nie wywaliła reszty
        ok, out = call(hdr, api, "campaigns", {
            "operations": [{"update": {"resourceName": f"customers/{CID}/campaigns/{cid}",
                                       "status": "PAUSED"},
                            "updateMask": "status"}],
            "validateOnly": not apply})
        print(f"[{tryb}] PAUZA {nazwa}: {'OK' if ok else 'BŁĄD'} {out if not ok else ''}")

    for ag, ad in VID_REKLAMY:
        ok, out = call(hdr, api, "adGroupAds", {
            "operations": [{"update": {"resourceName": f"customers/{CID}/adGroupAds/{ag}~{ad}",
                                       "status": "PAUSED"},
                            "updateMask": "status"}],
            "validateOnly": not apply})
        print(f"[{tryb}] PAUZA reklamy [VID] {ad}: {'OK' if ok else 'BŁĄD'} {out if not ok else ''}")

    for bid, kwota, opis in BUDZETY:
        ok, out = call(hdr, api, "campaignBudgets", {
            "operations": [{"update": {"resourceName": f"customers/{CID}/campaignBudgets/{bid}",
                                       "amountMicros": kwota},
                            "updateMask": "amount_micros"}],
            "validateOnly": not apply})
        print(f"[{tryb}] BUDŻET {opis}: {'OK' if ok else 'BŁĄD'} {out if not ok else ''}")


if __name__ == "__main__":
    main()
