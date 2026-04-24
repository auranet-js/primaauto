#!/usr/bin/env python3
"""
gads_ki_longtail.py — replace SKAG 37 ad groups with 1 ad group using
Keyword Insertion + per-keyword final URL override.

Strategy:
  H1 pin: {KeyWord:Auta z Chin}         ← Google inserts matched keyword (Title Case)
  H2 pin: Import z Chin — Prima-Auto
  H3-15:  rotating intent-only generic headlines
  Each keyword has own finalUrls → /samochody/?marka={slug}&model={slug}

Phases:
  1. Remove 37 existing SKAG ad groups (status=REMOVED, soft-delete keeps metrics)
  2. Create new ad group "Długi ogon — import z Chin" (bid 1 zł)
  3. Add ~1500 keywords (5 per brand+model combo, count>=2 in DB)
  4. Add 1 RSA with KI template

Usage:
  python3 tmp/gads_ki_longtail.py --dry-run
  python3 tmp/gads_ki_longtail.py --apply
"""
import json, sys, argparse, urllib.request, urllib.error, os
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from gads_client import load, refresh, headers

CUSTOMER_ID = "9506068500"
API = "https://googleads.googleapis.com/v21"
SKAG_CAMPAIGN_ID = "23779860641"
NEW_AD_GROUP_NAME = "Długi ogon — import z Chin"
BID_MICROS = 1_000_000  # 1.00 zł
PRICES_FILE = "/tmp/prices_all.tsv"
BRAND_SLUGS_FILE = "/tmp/prices_brand.tsv"
MIN_LISTINGS = 2  # filter brand+model with at least N listings


def gaql(tok, dev, login, cid, q):
    url = f"{API}/customers/{cid}/googleAds:searchStream"
    req = urllib.request.Request(url, data=json.dumps({"query": q}).encode(),
                                 headers=headers(tok, dev, login))
    try: return json.loads(urllib.request.urlopen(req).read())
    except urllib.error.HTTPError as e: print("HTTPError:", e.code, e.read().decode()[:500]); raise


def mutate(tok, dev, login, cid, resource, operations, partial=False):
    url = f"{API}/customers/{cid}/{resource}:mutate"
    req = urllib.request.Request(url, data=json.dumps({"operations": operations, "partialFailure": partial}).encode(),
                                 headers=headers(tok, dev, login))
    try: return json.loads(urllib.request.urlopen(req).read())
    except urllib.error.HTTPError as e: print("HTTPError:", e.code, e.read().decode()[:1200]); raise


def load_brand_slugs():
    slugs = {}
    with open(BRAND_SLUGS_FILE) as f:
        for line in f:
            p = line.rstrip("\n").split("\t")
            if len(p) >= 2: slugs[p[0].lower()] = p[1]
    return slugs


def load_pairs():
    """Return list of (brand, model, model_slug_db, count, min_price)."""
    out = []
    with open(PRICES_FILE) as f:
        for line in f:
            p = line.rstrip("\n").split("\t")
            if len(p) >= 5 and p[3].isdigit() and int(p[3]) >= MIN_LISTINGS:
                out.append({
                    "brand": p[0],
                    "model": p[1],
                    "model_slug_db": p[2],
                    "count": int(p[3]),
                    "min_pln": int(p[4]) if p[4].isdigit() else 0,
                })
    return out


def url_for_model(brand, model, brand_slugs):
    bs = brand_slugs.get(brand.lower(), brand.lower().replace(" ", "-"))
    ms = model.lower().replace(" ", "-")
    return f"https://primaauto.com.pl/samochody/?marka={bs}&model={ms}"


def generate_keywords(pair, brand_slugs):
    """5 KW per brand+model combo."""
    brand = pair["brand"]; model = pair["model"]
    ident = f"{brand.lower()} {model.lower()}"
    url = url_for_model(brand, model, brand_slugs)
    return [
        {"text": ident, "match": "EXACT", "url": url},
        {"text": ident, "match": "PHRASE", "url": url},
        {"text": f"{ident} import", "match": "EXACT", "url": url},
        {"text": f"{ident} z chin", "match": "PHRASE", "url": url},
        {"text": f"sprowadź {ident}", "match": "PHRASE", "url": url},
    ]


HEADLINES = [
    ("{KeyWord:Auta z Chin}", "HEADLINE_1"),
    ("Import z Chin — Prima-Auto", "HEADLINE_2"),
    ("Bezpośredni Importer", None),
    ("Zamów Online w 1 Klik", None),
    ("Sprawdź Ceny i Zamów", None),
    ("Ceny w Ogłoszeniach", None),
    ("Aktualne Oferty z Chin", None),
    ("Ponad 200 Modeli z Chin", None),
    ("Import Agencyjny z Chin", None),
    ("Umowa, Transport, Cło", None),
    ("Dostawa Pod Dom w PL", None),
    ("Legalna Rejestracja w PL", None),
    ("Na Zamówienie z Chin", None),
    ("Bez Pośredników — Importer", None),
    ("Prima-Auto — Twój Importer", None),
]

DESCRIPTIONS = [
    "Bezpośredni importer z Chin. Ceny w ogłoszeniach — zamów online w 1 klik.",
    "Aktualne ogłoszenia z Chin — codziennie. Umowa agencyjna, transport, cło.",
    "Bez salonu, bez wizyty. Zamów auto online — cała Polska.",
    "Dostawa do drzwi, pełna dokumentacja, homologacja PL. Cały proces online.",
]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--apply", action="store_true")
    args = ap.parse_args()

    # Validate copy lengths (KI keyword fallback "Auta z Chin" is 11 chars)
    for text, _ in HEADLINES:
        assert len(text) <= 30, f"Headline too long: '{text}' ({len(text)})"
    for text in DESCRIPTIONS:
        assert len(text) <= 90, f"Desc too long: '{text}' ({len(text)})"

    oauth, tokens, cfg = load()
    tok = refresh(oauth, tokens)
    dev = cfg["developer_token"]
    login = CUSTOMER_ID

    brand_slugs = load_brand_slugs()
    pairs = load_pairs()
    print(f"Brand+model pairs (count>={MIN_LISTINGS}): {len(pairs)}")
    all_kws = []
    for p in pairs:
        all_kws.extend(generate_keywords(p, brand_slugs))
    print(f"Generated keywords: {len(all_kws)}")

    # Fetch existing SKAG ad groups to remove
    q = f"""
      SELECT ad_group.id, ad_group.name, ad_group.resource_name
      FROM ad_group
      WHERE campaign.id = {SKAG_CAMPAIGN_ID} AND ad_group.status = 'ENABLED'
    """
    data = gaql(tok, dev, login, CUSTOMER_ID, q)
    existing = []
    for batch in data:
        for r in batch.get("results", []):
            existing.append(r["adGroup"])
    print(f"Existing SKAG ad groups to REMOVE: {len(existing)}")

    # Preview
    print("\nSample keywords (first 10):")
    for k in all_kws[:10]:
        print(f"  [{k['match']:<6}] {k['text']:<30} → {k['url']}")

    print(f"\nRSA preview:")
    for t, p in HEADLINES:
        pin = f" [{p}]" if p else ""
        print(f"  ({len(t):2d}) {t}{pin}")
    for d in DESCRIPTIONS:
        print(f"  D({len(d):2d}) {d}")

    total_ops = len(existing) + 1 + len(all_kws) + 1
    print(f"\nOperations plan:")
    print(f"  1. REMOVE {len(existing)} existing SKAG ad groups")
    print(f"  2. CREATE 1 new ad group '{NEW_AD_GROUP_NAME}'")
    print(f"  3. CREATE {len(all_kws)} keywords with per-KW final URLs")
    print(f"  4. CREATE 1 RSA with Keyword Insertion")
    print(f"  Total: ~{total_ops} operations")

    if not args.apply:
        print("\n[dry-run] Nothing executed. Add --apply to mutate.")
        return

    # --- PHASE 1: Remove existing ad groups ---
    print(f"\n[apply] PHASE 1: remove {len(existing)} ad groups...")
    remove_ops = [{"remove": ag["resourceName"]} for ag in existing]
    for i in range(0, len(remove_ops), 500):
        chunk = remove_ops[i:i+500]
        r = mutate(tok, dev, login, CUSTOMER_ID, "adGroups", chunk)
        print(f"  batch {i//500+1}: removed {len(r.get('results', []))}")

    # --- PHASE 2: Create new ad group ---
    print(f"\n[apply] PHASE 2: create ad group '{NEW_AD_GROUP_NAME}'...")
    create_ag_op = [{
        "create": {
            "campaign": f"customers/{CUSTOMER_ID}/campaigns/{SKAG_CAMPAIGN_ID}",
            "name": NEW_AD_GROUP_NAME,
            "status": "ENABLED",
            "type": "SEARCH_STANDARD",
            "cpcBidMicros": str(BID_MICROS),
        }
    }]
    r = mutate(tok, dev, login, CUSTOMER_ID, "adGroups", create_ag_op)
    new_ag_resource = r["results"][0]["resourceName"]
    print(f"  created: {new_ag_resource}")

    # --- PHASE 3: Add keywords w/ per-KW URL ---
    print(f"\n[apply] PHASE 3: add {len(all_kws)} keywords with finalUrls...")
    kw_ops = []
    for k in all_kws:
        kw_ops.append({
            "create": {
                "adGroup": new_ag_resource,
                "keyword": {"text": k["text"], "matchType": k["match"]},
                "status": "ENABLED",
                "finalUrls": [k["url"]],
            }
        })
    for i in range(0, len(kw_ops), 500):
        chunk = kw_ops[i:i+500]
        r = mutate(tok, dev, login, CUSTOMER_ID, "adGroupCriteria", chunk, partial=True)
        ok = sum(1 for x in r.get("results", []) if x)
        print(f"  batch {i//500+1}: {ok}/{len(chunk)} created")

    # --- PHASE 4: Create RSA with KI ---
    print(f"\n[apply] PHASE 4: create RSA with Keyword Insertion...")
    rsa_op = [{
        "create": {
            "adGroup": new_ag_resource,
            "status": "ENABLED",
            "ad": {
                "finalUrls": ["https://primaauto.com.pl/samochody/"],  # fallback if KW has no override (shouldn't happen)
                "responsiveSearchAd": {
                    "headlines": [{"text": t, **({"pinnedField": p} if p else {})} for t, p in HEADLINES],
                    "descriptions": [{"text": d} for d in DESCRIPTIONS],
                    "path1": "samochody",
                    "path2": "z-chin",
                },
            },
        }
    }]
    r = mutate(tok, dev, login, CUSTOMER_ID, "adGroupAds", rsa_op)
    print(f"  created: {r['results'][0]['resourceName']}")
    print("\nDone.")


if __name__ == "__main__":
    main()
