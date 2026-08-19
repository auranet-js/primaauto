#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Minimalny klient GA4 Data API dla primaauto.com.pl (property 534017542).

Odtworzony 2026-08-19 — poprzednia kopia `tmp/ga4_query.py` przepadła w czystce tmp
(został sam .pyc), dlatego teraz mieszka w scripts/, nie w tmp/.

    from ga4_query import token, run, rows
    tok = token()
    d = run(tok, {"dateRanges": [{"startDate": "30daysAgo", "endDate": "yesterday"}],
                  "dimensions": [{"name": "sessionCampaignName"}],
                  "metrics": [{"name": "sessions"}]})
    hdr_dims, hdr_mets, data = rows(d)
"""
import json, urllib.request, urllib.parse
from pathlib import Path

SECRETS = Path.home() / "secrets" / "google"
PROPERTY = "534017542"
BASE = f"https://analyticsdata.googleapis.com/v1beta/properties/{PROPERTY}"


def token():
    o = json.loads((SECRETS / "oauth-desktop-client.json").read_text())["installed"]
    t = json.loads((SECRETS / "tokens.json").read_text())
    data = urllib.parse.urlencode({
        "client_id": o["client_id"], "client_secret": o["client_secret"],
        "refresh_token": t["refresh_token"], "grant_type": "refresh_token",
    }).encode()
    return json.load(urllib.request.urlopen(
        urllib.request.Request("https://oauth2.googleapis.com/token", data=data)))["access_token"]


def run(tok, body, endpoint="runReport"):
    req = urllib.request.Request(f"{BASE}:{endpoint}", data=json.dumps(body).encode(),
                                 headers={"Authorization": f"Bearer {tok}",
                                          "Content-Type": "application/json"})
    return json.load(urllib.request.urlopen(req))


def rows(d):
    """(nazwy wymiarów, nazwy metryk, [(wartości wymiarów, wartości metryk), ...])"""
    dims = [h["name"] for h in d.get("dimensionHeaders", [])]
    mets = [h["name"] for h in d.get("metricHeaders", [])]
    out = [([v["value"] for v in r.get("dimensionValues", [])],
            [v["value"] for v in r.get("metricValues", [])]) for r in d.get("rows", [])]
    return dims, mets, out
