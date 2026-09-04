#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Kolektor Google Ads dla dashboardu pomiaru — JSON obok danych Mety i GA4.

Powstało 2026-09-04. Dotąd Ads dało się obejrzeć tylko przez `ads-recheck.py` na żądanie;
dashboard pokazywał Metę i GA4, więc trzeciego kanału po prostu nie było widać.

Cron co 30 minut, dwa zapytania GAQL na przebieg (dziś + wczoraj). Wynik trafia do
`ads.json` w tym samym zahaszowanym katalogu co reszta — w pliku są wyłącznie liczby
i nazwy kampanii, nic z sekretów.

    python3 scripts/ads-live-collector.py            # zapis do DEST
    python3 scripts/ads-live-collector.py --stdout   # podgląd

Gotchy (te same, co w ads-recheck.py):
- wersja API z ~/secrets/google/ads-config.json, nigdy hardkod,
- `login-customer-id` = 9506068500 (konto direct, nie pod MCC),
- konwersje Ads i zdarzenia GA4 to NIE jest ta sama liczba — patrz docs/ads/mapa-kampanii.md.
"""
import json
import sys
from datetime import datetime
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from gads_client import load, refresh, gaql          # noqa: E402

CID = "9506068500"
DEST = Path("/home/host476470/domains/auratest.pl/public_html/"
            "pa-live-8aaf08d6ece0db176603/ads.json")

METRYKI = ("metrics.impressions, metrics.clicks, metrics.cost_micros, "
           "metrics.conversions, metrics.ctr, metrics.average_cpc")


def liczby(m):
    return {
        "wyswietlenia": int(m.get("impressions", 0)),
        "kliki": int(m.get("clicks", 0)),
        "koszt": round(int(m.get("costMicros", 0)) / 1e6, 2),
        "konwersje": round(float(m.get("conversions", 0)), 1),
        "ctr": round(float(m.get("ctr", 0)) * 100, 2),
        "cpc": round(int(m.get("averageCpc", 0)) / 1e6, 2),
    }


def sumuj(kampanie, klucz):
    pola = ("wyswietlenia", "kliki", "koszt", "konwersje")
    s = {p: round(sum(k[klucz][p] for k in kampanie), 2) for p in pola}
    s["ctr"] = round(100 * s["kliki"] / s["wyswietlenia"], 2) if s["wyswietlenia"] else 0
    s["cpc"] = round(s["koszt"] / s["kliki"], 2) if s["kliki"] else 0
    return s


def main():
    oauth, tokeny, cfg = load()
    tok = refresh(oauth, tokeny)
    dev = cfg["developer_token"]

    okresy = {}
    for etykieta, during in (("dzis", "TODAY"), ("wczoraj", "YESTERDAY")):
        wiersze = gaql(tok, dev, CID, CID,
                       f"SELECT campaign.id, campaign.name, campaign.status, {METRYKI} "
                       f"FROM campaign WHERE segments.date DURING {during} "
                       f"AND campaign.status != 'REMOVED'")
        okresy[etykieta] = {}
        for partia in wiersze:
            for w in partia.get("results", []):
                c = w["campaign"]
                okresy[etykieta][c["id"]] = {"nazwa": c["name"], "status": c["status"],
                                             "liczby": liczby(w["metrics"])}

    # Reklamy odrzucone — cichy zabójca: kampania dalej ENABLED, a reklamy nie ma.
    # Reagujemy WYŁĄCZNIE na DISAPPROVED; APPROVED_LIMITED przypina się do egzemplarza
    # i nie znaczy, że reklama stoi (patrz memory: etykieta polityki nie śledzi tekstu).
    odrzucone = []
    for partia in gaql(tok, dev, CID, CID,
                       "SELECT campaign.name, ad_group_ad.ad.id, "
                       "ad_group_ad.policy_summary.approval_status FROM ad_group_ad "
                       "WHERE ad_group_ad.status != 'REMOVED' AND campaign.status = 'ENABLED'"):
        for w in partia.get("results", []):
            if w["adGroupAd"].get("policySummary", {}).get("approvalStatus") == "DISAPPROVED":
                odrzucone.append({"kampania": w["campaign"]["name"],
                                  "reklama": w["adGroupAd"]["ad"]["id"]})

    kampanie = []
    for cid, dane in okresy["dzis"].items():
        kampanie.append({
            "nazwa": dane["nazwa"], "status": dane["status"],
            "dzis": dane["liczby"],
            "wczoraj": okresy["wczoraj"].get(cid, {}).get("liczby", liczby({})),
        })
    for cid, dane in okresy["wczoraj"].items():          # kampania, która dziś nie ruszyła
        if cid not in okresy["dzis"]:
            kampanie.append({"nazwa": dane["nazwa"], "status": dane["status"],
                             "dzis": liczby({}), "wczoraj": dane["liczby"]})
    kampanie.sort(key=lambda k: -k["dzis"]["koszt"])
    zywe = [k for k in kampanie if k["status"] == "ENABLED"]

    wynik = {
        "aktualizacja": datetime.now().strftime("%Y-%m-%d %H:%M"),
        "dzis": sumuj(kampanie, "dzis") if kampanie else {},
        "wczoraj": sumuj(kampanie, "wczoraj") if kampanie else {},
        "kampanie": kampanie,
        "zywych": len(zywe),
        "odrzucone": odrzucone,
    }

    tresc = json.dumps(wynik, ensure_ascii=False, indent=1)
    if "--stdout" in sys.argv:
        print(tresc)
    else:
        DEST.write_text(tresc)


if __name__ == "__main__":
    main()
