#!/usr/bin/env python3
"""GA4 live collector dla Prima-Auto — zbiera dane do lekkiego dashboardu.

Odpalany cronem co kilka minut. Robi 2 rodzaje zapytan do GA4:
  1) Realtime API (runRealtimeReport) — PRAWDZIWE live: aktywni w 30/5 min,
     sparkline per minuta, top strony live, miasta live. Realtime API NIE zna
     wymiaru kampanii/zrodla (ograniczenie Google), wiec kampanie ciagniemy z:
  2) Data API (runReport, zakres 'today') — near-realtime (opoznienie kilka min):
     ruch dzis per sessionCampaignName + sessionSourceMedium + zdarzenia kluczowe.

Wynik: JSON zapisany do DEST (czytany przez statyczny index.html — zero wywolan
API z przegladarki). Dziennie ~ (1440/interwal) * 6 zapytan; przy 3 min = ~2880
zapytan/dobe, grubo ponizej limitow GA4 (Data API 25k tokenow/dzien per property).

Auth: OAuth refresh_token z ~/secrets/google/ (ten sam co reszta stacku Google).
"""
import json
import sys
import time
import urllib.request
import urllib.parse
import urllib.error

SECRETS = "/home/host476470/secrets/google"
PROPERTY = "534017542"
DEST = "/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp/pa-live/data.json"
DATA_BASE = f"https://analyticsdata.googleapis.com/v1beta/properties/{PROPERTY}"

KEY_EVENTS = ["generate_lead", "click_whatsapp", "click_phone"]


def token():
    oauth = json.load(open(f"{SECRETS}/oauth-desktop-client.json"))
    oauth = oauth.get("installed", oauth)
    tokens = json.load(open(f"{SECRETS}/tokens.json"))
    data = urllib.parse.urlencode({
        "client_id": oauth["client_id"],
        "client_secret": oauth["client_secret"],
        "refresh_token": tokens["refresh_token"],
        "grant_type": "refresh_token",
    }).encode()
    req = urllib.request.Request("https://oauth2.googleapis.com/token", data=data)
    return json.load(urllib.request.urlopen(req, timeout=30))["access_token"]


def call(tok, path, body):
    req = urllib.request.Request(
        f"{DATA_BASE}:{path}", data=json.dumps(body).encode(),
        headers={"Authorization": f"Bearer {tok}", "Content-Type": "application/json"},
    )
    try:
        return json.loads(urllib.request.urlopen(req, timeout=30).read())
    except urllib.error.HTTPError as e:
        sys.stderr.write(f"HTTP {e.code} on {path}: {e.read().decode()[:300]}\n")
        return {}


def rows(resp):
    for r in resp.get("rows", []):
        yield ([d["value"] for d in r["dimensionValues"]],
               [m["value"] for m in r["metricValues"]])


def main():
    tok = token()
    out = {"ts": int(time.time()), "property": PROPERTY}

    # 1) Realtime — aktywni per minuta (sparkline 30 slupkow)
    r = call(tok, "runRealtimeReport", {
        "dimensions": [{"name": "minutesAgo"}],
        "metrics": [{"name": "activeUsers"}],
    })
    per_min = {int(d[0]): int(m[0]) for d, m in rows(r)}
    spark = [per_min.get(m, 0) for m in range(29, -1, -1)]  # -29..0
    out["spark"] = spark
    out["active_30"] = sum(spark)
    out["active_5"] = sum(spark[-5:])

    # 2) Realtime — top strony live (nazwa ekranu)
    r = call(tok, "runRealtimeReport", {
        "dimensions": [{"name": "unifiedScreenName"}],
        "metrics": [{"name": "activeUsers"}],
        "orderBys": [{"metric": {"metricName": "activeUsers"}, "desc": True}],
        "limit": 10,
    })
    out["pages_live"] = [{"name": d[0], "users": int(m[0])} for d, m in rows(r)]

    # 3) Realtime — miasta live
    r = call(tok, "runRealtimeReport", {
        "dimensions": [{"name": "city"}],
        "metrics": [{"name": "activeUsers"}],
        "orderBys": [{"metric": {"metricName": "activeUsers"}, "desc": True}],
        "limit": 8,
    })
    out["cities_live"] = [{"name": d[0] or "(nieznane)", "users": int(m[0])} for d, m in rows(r)]

    # 4) Near-realtime — ruch DZIS per kampania (session-scoped = poprawnie liczy RMKT)
    r = call(tok, "runReport", {
        "dateRanges": [{"startDate": "today", "endDate": "today"}],
        "dimensions": [{"name": "sessionCampaignName"}, {"name": "sessionSourceMedium"}],
        "metrics": [{"name": "activeUsers"}, {"name": "sessions"}, {"name": "keyEvents"}],
        "orderBys": [{"metric": {"metricName": "sessions"}, "desc": True}],
        "limit": 25,
    })
    out["today_campaigns"] = [{
        "campaign": d[0], "sourceMedium": d[1],
        "users": int(m[0]), "sessions": int(m[1]), "keyEvents": float(m[2]),
    } for d, m in rows(r)]

    # 5) Near-realtime — zdarzenia kluczowe DZIS per kampania
    r = call(tok, "runReport", {
        "dateRanges": [{"startDate": "today", "endDate": "today"}],
        "dimensions": [{"name": "eventName"}, {"name": "sessionCampaignName"}],
        "metrics": [{"name": "eventCount"}],
        "dimensionFilter": {"filter": {"fieldName": "eventName", "inListFilter": {"values": KEY_EVENTS}}},
        "orderBys": [{"metric": {"metricName": "eventCount"}, "desc": True}],
        "limit": 30,
    })
    out["today_events"] = [{
        "event": d[0], "campaign": d[1], "count": int(m[0]),
    } for d, m in rows(r)]

    tmp = DEST + ".tmp"
    with open(tmp, "w") as f:
        json.dump(out, f, ensure_ascii=False)
    import os
    os.replace(tmp, DEST)
    print(f"[ok] {DEST} active_30={out['active_30']} camps={len(out['today_campaigns'])}")


if __name__ == "__main__":
    main()
