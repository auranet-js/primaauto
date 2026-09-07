#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""[RMKT] harmonogram + geo (2026-09-07). Druga część optymalizacji po pytaniach Janka.

Pomiar 30 dni, rozkład godzinowy:
  23:00-07:00 = 132,77 zł (27% wydatku) i 0,8 konwersji (9%) — CPA 177 zł
  07:00-23:00 = 359,34 zł i 7,7 konwersji — CPA 46,87 zł
  01:00-04:00 = 4 589 impresji, 107 kliknięć, ZERO konwersji, 34,93 zł

Kluczowa obserwacja: po 16:00 impresje praktycznie znikają (17h=273, 19h=69, 20h=0).
Kampania nie chodzi całą dobę — startuje o północy i wypala 17 zł budżetu do 16:00.
Wycięcie nocy nie oszczędza, tylko przesuwa budżet na godziny, które konwertują.

Geo: kampania nie miała ŻADNEGO kryterium LOCATION, czyli celowała w cały świat.

    python3 scripts/gads-rmkt-harmonogram-geo-2026-09-07.py            # dry-run
    python3 scripts/gads-rmkt-harmonogram-geo-2026-09-07.py --apply
"""
import json, os, sys, urllib.request, urllib.error
from datetime import datetime

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from gads_client import load, refresh  # noqa: E402

CID = "9506068500"
CAMPAIGN = 23897599362
API = json.load(open("/home/host476470/secrets/google/ads-config.json")).get("api_version", "v25")
APPLY = "--apply" in sys.argv
POLSKA = "geoTargetConstants/2616"
DNI = ["MONDAY", "TUESDAY", "WEDNESDAY", "THURSDAY", "FRIDAY", "SATURDAY", "SUNDAY"]
START, KONIEC = 7, 23     # okno emisji


def hdr():
    o, t, c = load()
    return {"Authorization": f"Bearer {refresh(o, t)}", "developer-token": c["developer_token"],
            "login-customer-id": CID, "Content-Type": "application/json"}


def gaql(q, h):
    u = f"https://googleads.googleapis.com/{API}/customers/{CID}/googleAds:searchStream"
    d = json.loads(urllib.request.urlopen(urllib.request.Request(
        u, data=json.dumps({"query": q}).encode(), headers=h)).read())
    return [r for b in (d or []) for r in b.get("results", [])]


def mutate(suffix, payload, h):
    u = f"https://googleads.googleapis.com/{API}/customers/{CID}/{suffix}"
    try:
        return json.loads(urllib.request.urlopen(urllib.request.Request(
            u, data=json.dumps(payload).encode(), headers=h)).read())
    except urllib.error.HTTPError as e:
        print(f"  BŁĄD {e.code}: {e.read().decode()[:900]}", file=sys.stderr)
        raise


h = hdr()

# strefa czasowa konta — harmonogram liczy się w NIEJ, nie w strefie serwera
tz = gaql("SELECT customer.time_zone, customer.currency_code FROM customer", h)
strefa = tz[0]["customer"].get("timeZone") if tz else "?"
print(f"strefa czasowa konta: {strefa}")
if strefa not in ("Europe/Warsaw", "Poland"):
    sys.exit(f"ABORT: nieoczekiwana strefa konta ({strefa}) — harmonogram liczyłby się źle")

est = gaql(f"SELECT campaign_criterion.criterion_id, campaign_criterion.type "
           f"FROM campaign_criterion WHERE campaign.id={CAMPAIGN} "
           f"AND campaign_criterion.type IN ('AD_SCHEDULE','LOCATION')", h)
ma_harmonogram = [r for r in est if r["campaignCriterion"]["type"] == "AD_SCHEDULE"]
ma_geo = [r for r in est if r["campaignCriterion"]["type"] == "LOCATION"]
print(f"istniejące: harmonogram={len(ma_harmonogram)} kryteriów, geo={len(ma_geo)} kryteriów")

if ma_harmonogram:
    sys.exit("ABORT: harmonogram już istnieje — nie nadpisuję bez decyzji człowieka")

print(f"\n1. harmonogram: {len(DNI)} × {START:02d}:00-{KONIEC:02d}:00 (poza tym oknem kampania milczy)")
print(f"2. geo: {POLSKA} (Polska){'  — JUŻ USTAWIONE, pomijam' if ma_geo else ''}")

if not APPLY:
    print("\nDRY-RUN — nic nie wysłano. Dodaj --apply.")
    sys.exit(0)

BACKUP = os.path.expanduser(f"~/backups/primaauto/rmkt-optymalizacja/{datetime.now():%Y-%m-%d}")
os.makedirs(BACKUP, exist_ok=True)
json.dump(est, open(f"{BACKUP}/przed-harmonogram-geo.json", "w"), ensure_ascii=False, indent=1)

ops = [{"create": {"campaign": f"customers/{CID}/campaigns/{CAMPAIGN}",
                   "adSchedule": {"dayOfWeek": d, "startHour": START, "startMinute": "ZERO",
                                  "endHour": KONIEC, "endMinute": "ZERO"}}} for d in DNI]
if not ma_geo:
    ops.append({"create": {"campaign": f"customers/{CID}/campaigns/{CAMPAIGN}",
                           "location": {"geoTargetConstant": POLSKA}}})
mutate("campaignCriteria:mutate", {"operations": ops, "partialFailure": False}, h)
print(f"OK zastosowano {len(ops)} operacji")

# weryfikacja odczytem
po = gaql(f"SELECT campaign_criterion.type, campaign_criterion.ad_schedule.day_of_week, "
          f"campaign_criterion.ad_schedule.start_hour, campaign_criterion.ad_schedule.end_hour, "
          f"campaign_criterion.location.geo_target_constant "
          f"FROM campaign_criterion WHERE campaign.id={CAMPAIGN} "
          f"AND campaign_criterion.type IN ('AD_SCHEDULE','LOCATION')", h)
print("\nWERYFIKACJA:")
for r in po:
    c = r["campaignCriterion"]
    if c["type"] == "AD_SCHEDULE":
        s = c["adSchedule"]
        print(f"  {s['dayOfWeek']:<12}{s['startHour']:02d}:00-{s['endHour']:02d}:00")
    else:
        print(f"  GEO: {c['location']['geoTargetConstant']}")
