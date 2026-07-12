#!/usr/bin/env python3
"""Minimal Google Ads REST client — refresh token → listAccessibleCustomers → GAQL searchStream."""
import json
import sys
import urllib.request
import urllib.parse

SECRETS = "/home/host476470/secrets/google"
API_VERSION = "v21"


def load():
    oauth = json.load(open(f"{SECRETS}/oauth-desktop-client.json"))["installed"]
    tokens = json.load(open(f"{SECRETS}/tokens.json"))
    cfg = json.load(open(f"{SECRETS}/ads-config.json"))
    return oauth, tokens, cfg


def refresh(oauth, tokens):
    data = urllib.parse.urlencode({
        "client_id": oauth["client_id"],
        "client_secret": oauth["client_secret"],
        "refresh_token": tokens["refresh_token"],
        "grant_type": "refresh_token",
    }).encode()
    req = urllib.request.Request("https://oauth2.googleapis.com/token", data=data)
    return json.load(urllib.request.urlopen(req))["access_token"]


def headers(access_token, dev_token, mcc_id, customer_id=None):
    h = {
        "Authorization": f"Bearer {access_token}",
        "developer-token": dev_token,
        "login-customer-id": mcc_id,
        "Content-Type": "application/json",
    }
    return h


def list_accessible(access_token, dev_token, mcc_id):
    url = f"https://googleads.googleapis.com/{API_VERSION}/customers:listAccessibleCustomers"
    req = urllib.request.Request(url, headers=headers(access_token, dev_token, mcc_id))
    return json.load(urllib.request.urlopen(req))


def gaql(access_token, dev_token, mcc_id, customer_id, query):
    url = f"https://googleads.googleapis.com/{API_VERSION}/customers/{customer_id}/googleAds:searchStream"
    body = json.dumps({"query": query}).encode()
    req = urllib.request.Request(url, data=body, headers=headers(access_token, dev_token, mcc_id))
    return json.loads(urllib.request.urlopen(req).read())


def main():
    oauth, tokens, cfg = load()
    mcc = cfg["mcc_customer_id"]
    dev = cfg["developer_token"]
    tok = refresh(oauth, tokens)
    print(f"[ok] access_token refreshed ({len(tok)} chars)", file=sys.stderr)
    if "--no-list" not in sys.argv:
        accessible = list_accessible(tok, dev, mcc)
        print(f"[accessible]", json.dumps(accessible, indent=2), file=sys.stderr)

    if len(sys.argv) > 1:
        customer_id = sys.argv[1].replace("-", "")
        query = sys.argv[2] if len(sys.argv) > 2 else (
            "SELECT campaign.id, campaign.name, campaign.status, "
            "campaign_budget.amount_micros, campaign.advertising_channel_type "
            "FROM campaign ORDER BY campaign.name"
        )
        # Direct access: login-customer-id = customer_id (account not under MCC)
        login = customer_id if customer_id != mcc else mcc
        try:
            result = gaql(tok, dev, login, customer_id, query)
        except urllib.error.HTTPError as e:
            print("HTTPError:", e.code, e.read().decode()[:600])
            return
        print(json.dumps(result, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
