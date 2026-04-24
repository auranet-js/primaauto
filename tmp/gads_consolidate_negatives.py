#!/usr/bin/env python3
"""
gads_consolidate_negatives.py — merge campaign-level negative keywords
into a single account-wide Shared Set, attach to all ENABLED campaigns.

Phases:
  1. Dump current campaign-level negatives (Topic + SKAG)
  2. Dedup
  3. Create SharedSet "Prima-Auto — Negatywy Globalne" (NEGATIVE_KEYWORDS)
  4. Populate with deduplicated list
  5. Attach to Brand + Topic + SKAG via CampaignSharedSet
  6. [--cleanup mode] Remove campaign-level negatives (only after verification)

Usage:
  python3 tmp/gads_consolidate_negatives.py --dry-run
  python3 tmp/gads_consolidate_negatives.py --apply-create   # phases 1-5 (non-destructive)
  python3 tmp/gads_consolidate_negatives.py --apply-cleanup  # phase 6 (destructive)
"""
import json, sys, argparse, urllib.request, urllib.error, os, time
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from gads_client import load, refresh, headers

CUSTOMER_ID = "9506068500"
API = "https://googleads.googleapis.com/v21"
SHARED_SET_NAME = "Prima-Auto — Negatywy Globalne"
CAMPAIGNS_TO_PROTECT = ["[Brand] Prima-Auto", "[Topic] Import z Chin", "[SKAG] Marki-Modele"]


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


def fetch_negatives(tok, dev, login, cid):
    """Returns list of dicts: {campaign_name, campaign_id, resource_name, text, match_type}."""
    q = """
      SELECT campaign.id, campaign.name, campaign_criterion.resource_name,
             campaign_criterion.keyword.text, campaign_criterion.keyword.match_type
      FROM campaign_criterion
      WHERE campaign_criterion.type = 'KEYWORD'
        AND campaign_criterion.negative = TRUE
    """
    data = gaql(tok, dev, login, cid, q)
    out = []
    for batch in data:
        for r in batch.get("results", []):
            out.append({
                "campaign_name": r["campaign"]["name"],
                "campaign_id": r["campaign"]["id"],
                "resource_name": r["campaignCriterion"]["resourceName"],
                "text": r["campaignCriterion"]["keyword"]["text"],
                "match_type": r["campaignCriterion"]["keyword"]["matchType"],
            })
    return out


def find_shared_set(tok, dev, login, cid):
    q = f"""
      SELECT shared_set.resource_name, shared_set.id, shared_set.name, shared_set.type
      FROM shared_set
      WHERE shared_set.name = '{SHARED_SET_NAME}'
        AND shared_set.status = 'ENABLED'
    """
    data = gaql(tok, dev, login, cid, q)
    for batch in data:
        for r in batch.get("results", []):
            return r["sharedSet"]
    return None


def find_campaigns(tok, dev, login, cid):
    q = """
      SELECT campaign.id, campaign.name, campaign.resource_name, campaign.status
      FROM campaign
      WHERE campaign.name IN ('[Brand] Prima-Auto', '[Topic] Import z Chin', '[SKAG] Marki-Modele')
    """
    data = gaql(tok, dev, login, cid, q)
    out = []
    for batch in data:
        for r in batch.get("results", []):
            out.append(r["campaign"])
    return out


def find_shared_set_attachments(tok, dev, login, cid, shared_set_resource):
    q = f"""
      SELECT campaign_shared_set.resource_name, campaign_shared_set.campaign,
             campaign_shared_set.shared_set
      FROM campaign_shared_set
      WHERE campaign_shared_set.shared_set = '{shared_set_resource}'
    """
    data = gaql(tok, dev, login, cid, q)
    out = []
    for batch in data:
        for r in batch.get("results", []):
            out.append(r["campaignSharedSet"])
    return out


def dedup(negatives):
    """Deduplicate by (text_lowered, match_type). Preserve original text casing from first occurrence."""
    seen = {}
    for n in negatives:
        key = (n["text"].lower().strip(), n["match_type"])
        if key not in seen:
            seen[key] = {"text": n["text"].strip(), "match_type": n["match_type"]}
    return list(seen.values())


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--apply-create", action="store_true", help="Phase 1-5: create shared set + populate + attach")
    ap.add_argument("--apply-cleanup", action="store_true", help="Phase 6: remove campaign-level negs (destructive)")
    ap.add_argument("--dry-run", action="store_true", default=True)
    args = ap.parse_args()
    if args.apply_create or args.apply_cleanup:
        args.dry_run = False

    oauth, tokens, cfg = load()
    tok = refresh(oauth, tokens)
    dev = cfg["developer_token"]
    login = CUSTOMER_ID

    # Phase 1-2: dump + dedup
    print("=== Phase 1-2: Dump + dedup ===\n")
    negs = fetch_negatives(tok, dev, login, CUSTOMER_ID)
    by_camp = {}
    for n in negs:
        by_camp.setdefault(n["campaign_name"], []).append(n)
    for camp, items in sorted(by_camp.items()):
        print(f"  {camp}: {len(items)} neg keywords")

    uniq = dedup(negs)
    print(f"\nTotal rows: {len(negs)}  →  Unique (text,matchType): {len(uniq)}")

    # Check existing SharedSet
    existing_set = find_shared_set(tok, dev, login, CUSTOMER_ID)
    if existing_set:
        print(f"\n[existing] SharedSet '{SHARED_SET_NAME}' already exists: {existing_set['resourceName']}")
    else:
        print(f"\n[new] SharedSet '{SHARED_SET_NAME}' will be created")

    campaigns = find_campaigns(tok, dev, login, CUSTOMER_ID)
    print(f"\nCampaigns to attach ({len(campaigns)}):")
    for c in campaigns:
        print(f"  - {c['name']}  ({c['status']})  {c['resourceName']}")

    # Phase 6: cleanup preview
    if args.apply_cleanup:
        if not existing_set:
            print("\n[error] Can't cleanup — SharedSet doesn't exist yet. Run --apply-create first.")
            return
        attachments = find_shared_set_attachments(tok, dev, login, CUSTOMER_ID, existing_set["resourceName"])
        attached_ids = {a["campaign"] for a in attachments}
        print(f"\nPhase 6: cleanup {len(negs)} campaign-level neg keywords")
        print(f"SharedSet attached to {len(attached_ids)} campaigns")
        for c in campaigns:
            status = "ATTACHED" if c["resourceName"] in attached_ids else "NOT ATTACHED"
            print(f"  - {c['name']}: {status}")
        missing_attach = [c for c in campaigns if c["resourceName"] not in attached_ids]
        if missing_attach:
            print(f"\n[error] {len(missing_attach)} campaigns NOT attached to shared set — aborting cleanup")
            for c in missing_attach:
                print(f"    - {c['name']}")
            return
        print(f"\n[apply-cleanup] removing {len(negs)} campaign-level negs...")
        ops = [{"remove": n["resource_name"]} for n in negs]
        CHUNK = 500
        for i in range(0, len(ops), CHUNK):
            chunk = ops[i:i+CHUNK]
            result = mutate(tok, dev, login, CUSTOMER_ID, "campaignCriteria", chunk)
            print(f"  batch {i//CHUNK+1}: removed {len(result.get('results', []))}")
        print("Done.")
        return

    if args.dry_run:
        print(f"\n[dry-run] Preview of first 15 unique negatives:")
        for n in uniq[:15]:
            print(f"  [{n['match_type']:<6}] {n['text']}")
        print(f"  ... + {len(uniq)-15} more")
        print("\nPlanned operations (apply-create):")
        if not existing_set:
            print(f"  1. create SharedSet '{SHARED_SET_NAME}' (type=NEGATIVE_KEYWORDS)")
        print(f"  2. mutate sharedCriteria: create {len(uniq)} negative keywords")
        print(f"  3. mutate campaignSharedSets: attach to {len(campaigns)} campaigns")
        print(f"\nTotal ops: ~{len(uniq) + len(campaigns) + (0 if existing_set else 1)}")
        print("\nAdd --apply-create to execute phases 1-5 (non-destructive).")
        print("After verification (24h), run --apply-cleanup to remove campaign-level.")
        return

    # --- Phase 3: Create SharedSet ---
    if existing_set:
        shared_set_resource = existing_set["resourceName"]
        print(f"\n[skip] SharedSet exists: {shared_set_resource}")
    else:
        print(f"\n[apply] Phase 3: create SharedSet '{SHARED_SET_NAME}'")
        result = mutate(tok, dev, login, CUSTOMER_ID, "sharedSets", [{
            "create": {
                "name": SHARED_SET_NAME,
                "type": "NEGATIVE_KEYWORDS",
            }
        }])
        shared_set_resource = result["results"][0]["resourceName"]
        print(f"  created: {shared_set_resource}")

    # --- Phase 4: Populate with negatives ---
    print(f"\n[apply] Phase 4: populate with {len(uniq)} negatives")
    ops = []
    for n in uniq:
        ops.append({
            "create": {
                "sharedSet": shared_set_resource,
                "keyword": {"text": n["text"], "matchType": n["match_type"]},
            }
        })
    CHUNK = 500
    for i in range(0, len(ops), CHUNK):
        chunk = ops[i:i+CHUNK]
        result = mutate(tok, dev, login, CUSTOMER_ID, "sharedCriteria", chunk, partial=True)
        succ = len(result.get("results", []))
        print(f"  batch {i//CHUNK+1}: created {succ}/{len(chunk)}")

    # --- Phase 5: Attach to campaigns ---
    print(f"\n[apply] Phase 5: attach to {len(campaigns)} campaigns")
    attach_ops = [{
        "create": {
            "campaign": c["resourceName"],
            "sharedSet": shared_set_resource,
        }
    } for c in campaigns]
    result = mutate(tok, dev, login, CUSTOMER_ID, "campaignSharedSets", attach_ops, partial=True)
    print(f"  attached: {len(result.get('results', []))}/{len(attach_ops)}")
    print("\nDone. After 24h verification, run --apply-cleanup.")


if __name__ == "__main__":
    main()
