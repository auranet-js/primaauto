#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Usuwa dwie reklamy Xiaomi YU7 z [SKAG-1] Na placu (decyzja Janka 2026-08-31 — mail ws. marki Xiaomi).

    python3 scripts/gads-usun-reklamy-xiaomi-2026-08-31.py            # validate_only, nic nie zmienia
    python3 scripts/gads-usun-reklamy-xiaomi-2026-08-31.py --apply    # wykonuje

Reklamy (grupa `Xiaomi YU7 — od ręki`, ad_group 204984900668, kampania [SKAG-1] PAUSED):
  816416026504 — nagłówek „Xiaomi YU7 — 320 000 zł"
  816416026507 — nagłówek „Xiaomi YU7 od ręki"
"""
import json, sys, urllib.request, urllib.error
from pathlib import Path
sys.path.insert(0, str(Path(__file__).parent))
from gads_client import load, refresh

CID = "9506068500"
AD_GROUP = "204984900668"
ADS = ["816416026504", "816416026507"]


def main():
    apply = "--apply" in sys.argv
    oauth, tokens, cfg = load()
    api = cfg.get("api_version", "v25")
    hdr = {"Authorization": f"Bearer {refresh(oauth, tokens)}",
           "developer-token": cfg["developer_token"],
           "login-customer-id": CID, "Content-Type": "application/json"}
    body = {
        "operations": [{"remove": f"customers/{CID}/adGroupAds/{AD_GROUP}~{ad}"} for ad in ADS],
        "validateOnly": not apply,
    }
    req = urllib.request.Request(
        f"https://googleads.googleapis.com/{api}/customers/{CID}/adGroupAds:mutate",
        data=json.dumps(body).encode(), headers=hdr)
    try:
        out = json.load(urllib.request.urlopen(req))
    except urllib.error.HTTPError as e:
        print("BŁĄD", e.code, e.read().decode()[:1200])
        return 1
    print(("WYKONANE" if apply else "WALIDACJA OK (nic nie zmieniono)") + ":", json.dumps(out, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    sys.exit(main())
