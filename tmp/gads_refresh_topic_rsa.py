#!/usr/bin/env python3
"""
gads_refresh_topic_rsa.py — replace RSA in [Topic] Import z Chin / "Import aut z Chin"
with intent-only Topic-specific template. RSA immutable → create + remove.

Usage:
  python3 tmp/gads_refresh_topic_rsa.py --dry-run
  python3 tmp/gads_refresh_topic_rsa.py --apply
"""
import json, sys, argparse, urllib.request, urllib.error, os
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from gads_client import load, refresh, headers

CUSTOMER_ID = "9506068500"
API = "https://googleads.googleapis.com/v21"
CAMPAIGN_NAME = "[Topic] Import z Chin"
AD_GROUP_NAME = "Import aut z Chin"


def gaql(tok, dev, login, cid, q):
    url = f"{API}/customers/{cid}/googleAds:searchStream"
    req = urllib.request.Request(url, data=json.dumps({"query": q}).encode(),
                                 headers=headers(tok, dev, login))
    try:
        return json.loads(urllib.request.urlopen(req).read())
    except urllib.error.HTTPError as e:
        print("HTTPError:", e.code, e.read().decode()[:500]); raise


def mutate(tok, dev, login, cid, resource, operations):
    url = f"{API}/customers/{cid}/{resource}:mutate"
    body = {"operations": operations, "partialFailure": False}
    req = urllib.request.Request(url, data=json.dumps(body).encode(),
                                 headers=headers(tok, dev, login))
    try:
        return json.loads(urllib.request.urlopen(req).read())
    except urllib.error.HTTPError as e:
        print("HTTPError:", e.code, e.read().decode()[:800]); raise


TOPIC_HEADLINES = [
    ("Import Aut z Chin", "HEADLINE_1"),
    ("Bezpośredni Importer", "HEADLINE_2"),
    ("Zamów Online w 1 Klik", None),
    ("Sprawdź Ceny i Zamów", None),
    ("Ceny w Ogłoszeniach", None),
    ("Aktualne Oferty z Chin", None),
    ("Bez Pośredników — Importer", None),
    ("Ponad 200 Modeli z Chin", None),
    ("Import Agencyjny z Chin", None),
    ("Umowa, Transport, Cło", None),
    ("Dostawa Pod Dom w PL", None),
    ("Legalna Rejestracja w PL", None),
    ("Katalog Aut z Chin Online", None),
    ("Importer Aut z Chin", None),
    ("Prima-Auto — Twój Importer", None),
]

TOPIC_DESCRIPTIONS = [
    "Importer aut z Chin. Ceny w ogłoszeniach — zamów online w 1 klik.",
    "Aktualne ogłoszenia z Chin — codziennie. Umowa agencyjna, transport, cło.",
    "Bez salonu, bez wizyty. Zamów auto online — cała Polska.",
    "Dostawa do drzwi, pełna dokumentacja, homologacja PL. Cały proces online.",
]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--apply", action="store_true")
    args = ap.parse_args()

    oauth, tokens, cfg = load()
    tok = refresh(oauth, tokens)
    dev = cfg["developer_token"]
    login = CUSTOMER_ID

    # Fetch current RSA
    q = f"""
      SELECT ad_group.id, ad_group.name, campaign.name,
             ad_group_ad.resource_name,
             ad_group_ad.ad.responsive_search_ad.path1,
             ad_group_ad.ad.responsive_search_ad.path2,
             ad_group_ad.ad.final_urls
      FROM ad_group_ad
      WHERE campaign.name = '{CAMPAIGN_NAME}'
        AND ad_group.name = '{AD_GROUP_NAME}'
        AND ad_group_ad.status = 'ENABLED'
    """
    data = gaql(tok, dev, login, CUSTOMER_ID, q)
    targets = []
    for batch in data:
        for r in batch.get("results", []):
            a = r["adGroupAd"]
            rsa = a["ad"]["responsiveSearchAd"]
            targets.append({
                "ad_group_id": r["adGroup"]["id"],
                "rsa_resource": a["resourceName"],
                "path1": rsa.get("path1", ""),
                "path2": rsa.get("path2", ""),
                "final_urls": a["ad"].get("finalUrls", []),
            })

    if not targets:
        print("No RSA found."); return
    t = targets[0]
    print(f"Found RSA: {t['rsa_resource']}")
    print(f"  final_urls: {t['final_urls']}")
    print(f"  path: /{t['path1']}/{t['path2']}")

    # Validate lengths
    for text, _ in TOPIC_HEADLINES:
        assert len(text) <= 30, f"Headline '{text}' ({len(text)}) exceeds 30 chars"
    for text in TOPIC_DESCRIPTIONS:
        assert len(text) <= 90, f"Description '{text}' ({len(text)}) exceeds 90 chars"

    new_ad = {
        "adGroup": f"customers/{CUSTOMER_ID}/adGroups/{t['ad_group_id']}",
        "ad": {
            "finalUrls": t["final_urls"],
            "responsiveSearchAd": {
                "headlines": [{"text": text, **({"pinnedField": p} if p else {})} for text, p in TOPIC_HEADLINES],
                "descriptions": [{"text": d} for d in TOPIC_DESCRIPTIONS],
                "path1": t["path1"],
                "path2": t["path2"],
            },
        },
        "status": "ENABLED",
    }

    ops = [{"create": new_ad}, {"remove": t["rsa_resource"]}]
    print(f"\nOperations: {len(ops)} (1 create + 1 remove)")

    if not args.apply:
        print("[dry-run] Nothing executed.")
        return

    print("[apply] mutating...")
    result = mutate(tok, dev, login, CUSTOMER_ID, "adGroupAds", ops)
    print(f"OK: {len(result.get('results', []))} results")


if __name__ == "__main__":
    main()
