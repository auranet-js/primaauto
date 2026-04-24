#!/usr/bin/env python3
"""
gads_refresh_rsa_intent.py — regenerate RSA copy with intent-only vocab.

Strategy: filter info-browsers via language (sprzedawca/action/proces),
not via hard disqualifier. Preserves H1/H2 pinning, path1/path2, finalUrls.
RSA are immutable — performs create (new) + remove (old) in one batch per ad group.

Usage:
  python3 tmp/gads_refresh_rsa_intent.py --dry-run
  python3 tmp/gads_refresh_rsa_intent.py --apply
  python3 tmp/gads_refresh_rsa_intent.py --dry-run --only "Xiaomi SU7"
"""
import json, sys, argparse, urllib.request, urllib.error, os
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from gads_client import load, refresh, headers

CUSTOMER_ID = "9506068500"
API = "https://googleads.googleapis.com/v21"
PRICES_BRAND_FILE = "/tmp/prices_brand.tsv"        # cols: name, slug, n, min_pln
PRICES_MODEL_FILE = "/tmp/prices_all.tsv"          # cols: mark, model, slug, n, min_pln


def load_prices():
    """Returns (brand_min: dict[str,int], model_min: dict, brand_slugs: dict, model_slugs: dict)."""
    brand_min = {}; brand_slugs = {}
    try:
        with open(PRICES_BRAND_FILE) as f:
            for line in f:
                p = line.rstrip("\n").split("\t")
                if len(p) >= 4 and p[3].isdigit():
                    brand_min[p[0].lower()] = int(p[3])
                    brand_slugs[p[0].lower()] = p[1]
    except FileNotFoundError:
        pass
    model_min = {}; model_slugs = {}
    try:
        with open(PRICES_MODEL_FILE) as f:
            for line in f:
                p = line.rstrip("\n").split("\t")
                if len(p) >= 5 and p[4].isdigit():
                    model_min[(p[0].lower(), p[1].lower())] = int(p[4])
                    model_slugs[(p[0].lower(), p[1].lower())] = p[2]
    except FileNotFoundError:
        pass
    return brand_min, model_min, brand_slugs, model_slugs


def price_headline(brand: str, model, prices_brand, prices_model):
    """Return 'Od N 000 PLN' or None if no price found. Rounds DOWN to nearest 1000."""
    if model:
        p = prices_model.get((brand.lower(), model.lower()))
    else:
        p = prices_brand.get(brand.lower())
    if not p:
        return None
    rounded = (p // 1000) * 1000
    return f"Od {rounded:,}".replace(",", " ")


def listings_url(brand: str, model, brand_slugs):
    """Landing = listings archive with filter (/samochody/?marka=X[&model=Y])."""
    bs = brand_slugs.get(brand.lower(), brand.lower().replace(" ", "-"))
    if model:
        ms = model.lower().replace(" ", "-")
        return f"https://primaauto.com.pl/samochody/?marka={bs}&model={ms}"
    return f"https://primaauto.com.pl/samochody/?marka={bs}"


def gaql(tok, dev, login, cid, q):
    url = f"{API}/customers/{cid}/googleAds:searchStream"
    req = urllib.request.Request(url, data=json.dumps({"query": q}).encode(),
                                 headers=headers(tok, dev, login))
    try:
        return json.loads(urllib.request.urlopen(req).read())
    except urllib.error.HTTPError as e:
        print("HTTPError:", e.code, e.read().decode()[:500]); raise


def mutate(tok, dev, login, cid, resource, operations, partial=False):
    url = f"{API}/customers/{cid}/{resource}:mutate"
    body = {"operations": operations, "partialFailure": partial}
    req = urllib.request.Request(url, data=json.dumps(body).encode(),
                                 headers=headers(tok, dev, login))
    try:
        return json.loads(urllib.request.urlopen(req).read())
    except urllib.error.HTTPError as e:
        print("HTTPError:", e.code, e.read().decode()[:800]); raise


# -----------------------------------------------------------------------------
# Template — intent-only vocabulary (max 30 chars per headline, 90 chars per descr)
# -----------------------------------------------------------------------------

_PRICES_BRAND, _PRICES_MODEL, _BRAND_SLUGS, _MODEL_SLUGS = load_prices()


def headlines_for(brand: str, model: "str | None") -> list:
    """Returns [(text, pinned_field_or_None)] — H1 and H2 are pinned, rest rotates.

    For model-level ad groups: pass model="SU7", brand="Xiaomi"
    For brand-level:           pass model=None,  brand="Zeekr"
    """
    ident = f"{brand} {model}" if model else brand
    H = []
    # H1 pin — identifier + explicit "Import z Chin" to filter info-browsers
    if model:
        h1 = f"{brand} {model} Import z Chin"
        if len(h1) > 30:
            h1 = f"{brand} {model} z Chin"  # fallback if model name too long
    else:
        h1 = f"{brand} — Import z Chin"
    H.append((h1, "HEADLINE_1"))
    # H2 pin — seller role
    H.append(("Bezpośredni Importer", "HEADLINE_2"))
    # rotating — transactional / action / role
    H.extend([
        ("Sprawdź Ceny i Zamów", None),
        (f"{ident} Na Zamówienie", None),
        ("Ceny w Ogłoszeniach", None),
        ("Zamów Online w 1 Klik", None),
        (f"Sprowadź {ident}", None),
        (f"Zamów {ident} z Chin", None),
        ("Aktualne Oferty z Chin", None),
        (price_headline(brand, model, _PRICES_BRAND, _PRICES_MODEL) or "Bez Pośredników — Importer", None),
        ("Import Agencyjny z Chin", None),
        ("Importer Aut z Chin", None),
        ("Umowa, Transport, Cło", None),
        ("Dostawa Pod Dom w PL", None),
        ("Legalna Rejestracja w PL", None),
    ])
    # Google limit: 15 headlines. Trim to fit if some are > 30 chars.
    H = [(t, p) for t, p in H if len(t) <= 30][:15]
    return H


def descriptions_for(brand: str, model: "str | None") -> list[str]:
    ident = f"{brand} {model}" if model else brand
    D = [
        f"{ident} sprowadzamy z Chin. Ceny w ogłoszeniach — zamów online w 1 klik.",
        "Aktualne ogłoszenia z Chin — codziennie. Umowa agencyjna, transport, cło.",
        f"Nie salon, nie pośrednik. Zamów {ident} online — bez wizyty, cała Polska.",
        "Dostawa do drzwi, pełna dokumentacja, homologacja PL. Cały proces online.",
    ]
    return [d for d in D if len(d) <= 90][:4]


# -----------------------------------------------------------------------------
# Ad group name → (brand, model) parsing
# -----------------------------------------------------------------------------

BRANDS = [
    "Xiaomi", "BYD", "AITO", "Aito", "Geely", "XPeng", "Zeekr", "Chery", "Hongqi",
    "Avatr", "Changan", "Li Auto", "NIO", "GAC", "Denza", "Leapmotor", "Jetour",
    "Deepal", "Luxeed", "Voyah", "Haval", "Tank", "WEY", "IM Motors", "Nevo",
    "Exlantix", "iCAR", "Galaxy", "Nissan", "Volvo", "Mazda", "MG", "GWM",
]


def parse_ag(name: str) -> tuple["str | None", "str | None"]:
    """
    Parse ad group name → (brand, model).
    Examples:
        "Xiaomi SU7"            → ("Xiaomi", "SU7")
        "Zeekr"                 → ("Zeekr", None)
        "Denza D9 DM"           → ("Denza", "D9 DM")
        "GAC Trumpchi M8"       → ("GAC", "Trumpchi M8")
        "Import aut z Chin"     → (None, None)   # topic, skip
        "Chińskie EV i PHEV"    → (None, None)   # topic, skip
    """
    name = name.strip()
    # Sort brands by length desc so "GAC Trumpchi" etc match before "GAC"
    for brand in sorted(BRANDS, key=len, reverse=True):
        if name.lower() == brand.lower():
            return brand, None
        if name.lower().startswith(brand.lower() + " "):
            return brand, name[len(brand) + 1:].strip()
    return None, None


# -----------------------------------------------------------------------------
# Fetch existing RSA → build new RSA payload
# -----------------------------------------------------------------------------

def fetch_targets(tok, dev, login, cid, only=None):
    """Return list of dicts: {ad_group_id, ad_group_name, campaign_name,
    rsa_resource, path1, path2, final_urls}."""
    q = """
      SELECT ad_group.id, ad_group.name, campaign.name,
             ad_group_ad.resource_name, ad_group_ad.status,
             ad_group_ad.ad.responsive_search_ad.path1,
             ad_group_ad.ad.responsive_search_ad.path2,
             ad_group_ad.ad.final_urls
      FROM ad_group_ad
      WHERE campaign.name IN ('[SKAG] Marki-Modele')
        AND ad_group_ad.status = 'ENABLED'
        AND ad_group.status = 'ENABLED'
    """
    data = gaql(tok, dev, login, cid, q)
    out = []
    for batch in data:
        for r in batch.get("results", []):
            ag = r["adGroup"]
            agn = ag["name"]
            if only and only.lower() not in agn.lower():
                continue
            brand, model = parse_ag(agn)
            if not brand:
                continue  # skip topic-style ad groups
            a = r["adGroupAd"]
            rsa = a["ad"]["responsiveSearchAd"]
            out.append({
                "ad_group_id": ag["id"],
                "ad_group_name": agn,
                "campaign_name": r["campaign"]["name"],
                "rsa_resource": a["resourceName"],
                "brand": brand,
                "model": model,
                "path1": rsa.get("path1", ""),
                "path2": rsa.get("path2", ""),
                "final_urls": a["ad"].get("finalUrls", []),
            })
    return out


def build_operation(target):
    brand, model = target["brand"], target["model"]
    H = headlines_for(brand, model)
    D = descriptions_for(brand, model)
    # Landing = listings archive with filter (not SEO hub).
    new_final_url = listings_url(brand, model, _BRAND_SLUGS)
    # Display path: /samochody/{marka}/{model} — same info visible in SERP.
    bs = _BRAND_SLUGS.get(brand.lower(), brand.lower().replace(" ", "-"))
    p1 = "samochody"
    p2 = (model.lower().replace(" ", "-") if model else bs)[:15]
    new_rsa = {
        "headlines": [{"text": t, **({"pinnedField": p} if p else {})} for t, p in H],
        "descriptions": [{"text": d} for d in D],
        "path1": p1,
        "path2": p2,
    }
    new_ad = {
        "adGroup": f"customers/{CUSTOMER_ID}/adGroups/{target['ad_group_id']}",
        "ad": {
            "finalUrls": [new_final_url],
            "responsiveSearchAd": new_rsa,
        },
        "status": "ENABLED",
    }
    return [
        {"create": new_ad},
        {"remove": target["rsa_resource"]},
    ]


def build_bid_updates(targets, new_bid_micros=1_000_000):
    """Return adGroup update operations setting cpc_bid to new_bid."""
    seen = set()
    ops = []
    for t in targets:
        if t["ad_group_id"] in seen:
            continue
        seen.add(t["ad_group_id"])
        ops.append({
            "update": {
                "resourceName": f"customers/{CUSTOMER_ID}/adGroups/{t['ad_group_id']}",
                "cpcBidMicros": str(new_bid_micros),
            },
            "updateMask": "cpcBidMicros",
        })
    return ops


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--apply", action="store_true")
    ap.add_argument("--dry-run", action="store_true", default=True)
    ap.add_argument("--only", default=None, help="Filter ad group name substring")
    args = ap.parse_args()
    if args.apply:
        args.dry_run = False

    oauth, tokens, cfg = load()
    tok = refresh(oauth, tokens)
    dev = cfg["developer_token"]
    login = CUSTOMER_ID  # direct access

    targets = fetch_targets(tok, dev, login, CUSTOMER_ID, only=args.only)
    print(f"\n=== RSA refresh — {len(targets)} ad groups ===\n")
    if not targets:
        print("No targets matched."); return

    all_ops = []
    for t in targets:
        ops = build_operation(t)
        all_ops.extend(ops)

    # Show preview for first target
    first = targets[0]
    H = headlines_for(first["brand"], first["model"])
    D = descriptions_for(first["brand"], first["model"])
    new_url = listings_url(first["brand"], first["model"], _BRAND_SLUGS)
    print(f"--- PREVIEW: {first['ad_group_name']} ({first['campaign_name']}) ---")
    print(f"NEW finalUrl: {new_url}")
    print(f"(was: {first['final_urls']})")
    print("NEW HEADLINES:")
    for t, p in H:
        pin = f" [{p}]" if p else ""
        print(f"  ({len(t):2d}) {t}{pin}")
    print("NEW DESCRIPTIONS:")
    for d in D:
        print(f"  ({len(d):2d}) {d}")
    print()

    print(f"Targets ({len(targets)}):")
    for t in targets:
        ident = f"{t['brand']} {t['model']}" if t["model"] else f"{t['brand']} (brand-level)"
        print(f"  • {t['ad_group_name']:<30} → {ident}")

    bid_ops = build_bid_updates(targets, new_bid_micros=1_000_000)
    print(f"\nTotal operations: {len(all_ops)} RSA swaps ({len(targets)} create + {len(targets)} remove)")
    print(f"Plus {len(bid_ops)} bid updates → 1.00 zł flat for all ad groups")

    if args.dry_run:
        print("\n[dry-run] Nothing executed. Add --apply to mutate.")
        return

    print(f"\n[apply] batch 0: {len(bid_ops)} bid updates...")
    r = mutate(tok, dev, login, CUSTOMER_ID, "adGroups", bid_ops)
    print(f"         OK — {len(r.get('results', []))} bid updates done")

    # Apply in chunks of 100 ops (API limit tolerance)
    CHUNK = 100
    for i in range(0, len(all_ops), CHUNK):
        chunk = all_ops[i:i + CHUNK]
        print(f"[apply] batch {i//CHUNK + 1}: {len(chunk)} ops...")
        result = mutate(tok, dev, login, CUSTOMER_ID, "adGroupAds", chunk)
        rn = len(result.get("results", []))
        print(f"         OK — {rn} results")


if __name__ == "__main__":
    main()
