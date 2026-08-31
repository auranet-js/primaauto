#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""[DG]: modyfikator -100% na desktop, tablet i TV (analiza DG 2026-08-31).

    python3 scripts/gads-dg-wylacz-desktop-2026-08-31.py            # validate_only
    python3 scripts/gads-dg-wylacz-desktop-2026-08-31.py --apply

Pomiar 1–30.08: MOBILE 63 928 wyświetleń / 2 310 kliknięć / 26 konwersji.
DESKTOP 3 185 / 74 / 0, TABLET 2 786 / 68 / 0, CONNECTED_TV 542 / 1 / 0 —
razem 6 513 wyświetleń, 143 kliknięcia, 34,67 zł i ZERO konwersji.
Przy CVR mobile (1,1%) z 143 kliknięć oczekiwalibyśmy ~1,6 konwersji.
Kwota mała, kierunek jednoznaczny; 100% konwersji [DG] robi mobile.

Kandydat zgłoszony już 25.07 („Desktop: 243 wyśw. / 0 klik.") i wtedy nie wykonany.
"""
import json, sys, urllib.request, urllib.error
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from gads_client import load, refresh

CID = "9506068500"
DG = "24069066886"
URZADZENIA = [("30000", "DESKTOP"), ("30002", "TABLET"), ("30004", "CONNECTED_TV")]


def main():
    apply = "--apply" in sys.argv
    tryb = "APPLY" if apply else "VALIDATE_ONLY"
    oauth, tokens, cfg = load()
    api = cfg.get("api_version", "v25")
    hdr = {"Authorization": f"Bearer {refresh(oauth, tokens)}",
           "developer-token": cfg["developer_token"],
           "login-customer-id": CID, "Content-Type": "application/json"}
    body = {"operations": [
        {"update": {"resourceName": f"customers/{CID}/campaignCriteria/{DG}~{cid}",
                    "bidModifier": 0.0}, "updateMask": "bid_modifier"}
        for cid, _ in URZADZENIA], "validateOnly": not apply}
    req = urllib.request.Request(
        f"https://googleads.googleapis.com/{api}/customers/{CID}/campaignCriteria:mutate",
        data=json.dumps(body).encode(), headers=hdr)
    try:
        out = json.load(urllib.request.urlopen(req))
    except urllib.error.HTTPError as e:
        print("BŁĄD", e.code, e.read().decode()[:900]); return 1
    print(f"[{tryb}] -100% na: " + ", ".join(n for _, n in URZADZENIA) + f" — OK {out.get('results', '')}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
