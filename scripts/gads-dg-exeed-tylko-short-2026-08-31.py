#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""[DG] „wideo — Exeed VX": zostawia sam Short, usuwa film poziomy (decyzja Janka 2026-08-31).

    python3 scripts/gads-dg-exeed-tylko-short-2026-08-31.py            # validate_only
    python3 scripts/gads-dg-exeed-tylko-short-2026-08-31.py --apply

Pomiar 60 dni na poziomie assetów w tej jednej reklamie:
  Short   9W5mp7_CTVk (asset 398872064193) — 18 535 wyświetleń, 530 kliknięć, 11 konwersji
  poziomy X_Olrxx4y1U (asset 398799205438) — 11 083 wyświetlenia, 371 kliknięć,  0 konwersji
Przy tempie Shorta z 371 kliknięć spodziewalibyśmy się ~7,7 konwersji; zero to nie pech.
To jedyny asset z twardym zerem — poziomy Leopard 5 dowiózł 5 konwersji i ZOSTAJE.

UWAGA: edycja żywej reklamy resetuje jej uczenie się — przez kilka dni wyniki będą nieczytelne.
Film poziomy zostaje na kanale YouTube jako organika, usuwamy go tylko z reklamy.
"""
import json, sys, urllib.request, urllib.error
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from gads_client import load, refresh

CID = "9506068500"
AD = "818269813589"
SHORT = "398872064193"


def main():
    apply = "--apply" in sys.argv
    tryb = "APPLY" if apply else "VALIDATE_ONLY"
    oauth, tokens, cfg = load()
    api = cfg.get("api_version", "v25")
    hdr = {"Authorization": f"Bearer {refresh(oauth, tokens)}",
           "developer-token": cfg["developer_token"],
           "login-customer-id": CID, "Content-Type": "application/json"}
    body = {"operations": [{
        "update": {"resourceName": f"customers/{CID}/ads/{AD}",
                   "demandGenVideoResponsiveAd": {"videos": [{"asset": f"customers/{CID}/assets/{SHORT}"}]}},
        "updateMask": "demand_gen_video_responsive_ad.videos"}],
        "validateOnly": not apply}
    req = urllib.request.Request(
        f"https://googleads.googleapis.com/{api}/customers/{CID}/ads:mutate",
        data=json.dumps(body).encode(), headers=hdr)
    try:
        out = json.load(urllib.request.urlopen(req))
    except urllib.error.HTTPError as e:
        print("BŁĄD", e.code, e.read().decode()[:1200]); return 1
    print(f"[{tryb}] Exeed VX → sam Short: OK {out.get('results', '')}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
