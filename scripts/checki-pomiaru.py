#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Checki pomiaru Prima-Auto — jedna lista TAK/NIE dla Mety, GA4 i Google Ads.

Powstało 2026-09-04. Powód: dashboardy pokazują liczby, ale nie mówią, czy te liczby
w ogóle mają prawo być prawdziwe. Kolektor mógł stanąć, piksel przestać przyjmować
zdarzenia, reklama wpaść w DISAPPROVED, landing zniknąć — wszystko to wygląda na
dashboardzie jak „słaby dzień", nie jak awaria.

    python3 scripts/checki-pomiaru.py                 # tabela na stdout
    python3 scripts/checki-pomiaru.py --json plik     # zapis wyniku (dla dashboardu)
    python3 scripts/checki-pomiaru.py --cicho         # tylko problemy

Read-only. Nie dotyka reklam, nie pisze do konta — same odczyty.

Koszt: 3 wywołania Graph API (konto ma kroczący limit), 2 zapytania GA4 Data API,
1 GAQL do Ads, plus HEAD na landingi żywych reklam.

Kod wyjścia: 0 wszystko gra, 1 są ostrzeżenia, 2 jest awaria.
"""
import argparse
import collections
import json
import os
import sys
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timedelta, timezone
from pathlib import Path

TU = Path(__file__).resolve().parent
sys.path.insert(0, str(TU))
sys.path.insert(0, str(TU / "social"))

PUBLICZNY = Path("/home/host476470/domains/auratest.pl/public_html/pa-live-8aaf08d6ece0db176603")
TMP = TU.parent / "tmp"
CID_ADS = "9506068500"          # konto direct, nie pod MCC

OK, UWAGA, AWARIA = "ok", "uwaga", "awaria"
ZNAK = {OK: "TAK", UWAGA: " ? ", AWARIA: "NIE"}

wyniki = []
kontekst = {}          # liczby, które przy okazji warto oddać dashboardowi


def zapisz(nazwa, stan, opis):
    wyniki.append({"check": nazwa, "stan": stan, "opis": opis})


def wiek_minut(sciezka):
    return (datetime.now().timestamp() - os.path.getmtime(sciezka)) / 60


# ── świeżość kolektorów ───────────────────────────────────────────────────────

def check_swiezosc():
    for nazwa, plik, prog in [("GA4 — kolektor co 3 min", "data.json", 12),
                              ("Meta — kolektor co 30 min", "meta.json", 70)]:
        p = PUBLICZNY / plik
        if not p.exists():
            zapisz(nazwa, AWARIA, f"{plik} nie istnieje — cron nigdy nie zapisał")
            continue
        w = wiek_minut(p)
        stan = OK if w <= prog else AWARIA
        zapisz(nazwa, stan, f"{plik} sprzed {w:.0f} min (próg {prog})")


def check_logi_bledow():
    """Log rośnie od przejściowych 5xx Google — to szum, dopóki kolektor się podnosi.

    Awaria jest wtedy, gdy log jest ŚWIEŻSZY niż JSON: ostatnia próba się wywróciła
    i nic nie zostało zapisane.
    """
    for nazwa, plik, wynik in [("GA4 — log błędów", "ga4-live.err.log", "data.json"),
                               ("Meta — log błędów", "meta-live.err.log", "meta.json")]:
        p = TMP / plik
        if not p.exists() or p.stat().st_size == 0:
            zapisz(nazwa, OK, "pusty")
            continue
        tresc = p.read_text(errors="replace").strip().splitlines()
        bledow = sum(1 for l in tresc if l.startswith(("HTTP ", "Traceback")))
        w = wiek_minut(p)
        cel = PUBLICZNY / wynik
        stan = AWARIA if cel.exists() and w < wiek_minut(cel) else OK
        zapisz(nazwa, stan, f"{bledow} błędów w logu, ostatni sprzed {w:.0f} min "
                            f"({'kolektor NIE zapisał wyniku' if stan == AWARIA else 'kolektor się podniósł'})")


# ── Meta ──────────────────────────────────────────────────────────────────────

def check_meta():
    import meta_api as api

    pola = ("name,effective_status,"
            "creative{url_tags,object_story_spec,asset_feed_spec,effective_object_story_id}")
    d, e = api.get(f'{api.ACT}/ads?fields={pola}&limit=100&effective_status='
                   f'["ACTIVE","PENDING_REVIEW","DISAPPROVED","WITH_ISSUES","PENDING_BILLING_INFO"]')
    if e:
        zapisz("Meta — odczyt reklam", AWARIA, e)
        return
    reklamy = d.get("data", [])
    zywe = [a for a in reklamy if a["effective_status"] == "ACTIVE"]

    chore = [f"{a['name']} → {a['effective_status']}" for a in reklamy
             if a["effective_status"] in ("DISAPPROVED", "WITH_ISSUES", "PENDING_BILLING_INFO")]
    zapisz("Meta — nic nie odrzucone", AWARIA if chore else OK,
           "; ".join(chore) if chore else f"{len(zywe)} reklam ACTIVE, zero odrzuconych")

    bez_utm = [a["name"] for a in zywe if not (a.get("creative") or {}).get("url_tags")]
    zapisz("Meta — UTM-y na kreacjach", AWARIA if bez_utm else OK,
           (f"{len(bez_utm)} z {len(zywe)} bez url_tags: " + ", ".join(n[:28] for n in bez_utm[:4]))
           if bez_utm else f"wszystkie {len(zywe)} otagowane")

    linki = sorted({l for a in zywe if (l := link_kreacji(a.get("creative") or {}))})
    sprawdz_landingi(linki)

    k, e = api.get(f"{api.ACT}?fields=amount_spent,spend_cap,balance,account_status")
    if e:
        zapisz("Meta — limit konta", UWAGA, e)
    else:
        wyd, cap = int(k.get("amount_spent", 0)) / 100, int(k.get("spend_cap", 0)) / 100
        zostalo = cap - wyd
        stan = OK if cap == 0 or zostalo > 0.2 * cap else UWAGA
        zapisz("Meta — limit konta", stan,
               f"wydane {wyd:.2f} zł z {cap:.0f} zł, zostało {zostalo:.2f} zł")


def link_kreacji(c):
    oss = c.get("object_story_spec") or {}
    if "link_data" in oss:
        return oss["link_data"].get("link", "")
    if "video_data" in oss:
        return ((oss["video_data"].get("call_to_action") or {}).get("value") or {}).get("link", "")
    afs = c.get("asset_feed_spec") or {}
    if afs.get("link_urls"):
        return afs["link_urls"][0].get("website_url", "")
    return ""


def check_piksel():
    import meta_api as api

    od = (datetime.now(timezone.utc) - timedelta(days=1)).strftime("%Y-%m-%d")
    d, e = api.get(f"{api.PIKSEL}/stats?aggregation=event&start_time={od}")
    if e:
        zapisz("Meta — piksel przyjmuje zdarzenia", AWARIA, e)
        return
    bloki = d.get("data", [])
    if not bloki:
        zapisz("Meta — piksel przyjmuje zdarzenia", AWARIA, "brak zdarzeń od doby")
        return
    suma = collections.Counter()
    for b in bloki:
        for w in b.get("data", []):
            suma[w["value"]] += w["count"]
    ostatni = datetime.strptime(bloki[-1]["start_time"][:19], "%Y-%m-%dT%H:%M:%S")
    lag = (datetime.now(timezone.utc).replace(tzinfo=None) - ostatni).total_seconds() / 3600
    # Meta domyka bloki godzinowe z opóźnieniem — do 3 h to norma, nie awaria.
    stan = OK if suma.get("ViewContent", 0) > 0 and lag < 4 else AWARIA
    zapisz("Meta — piksel przyjmuje zdarzenia", stan,
           f"doba: PageView {suma.get('PageView', 0)}, ViewContent {suma.get('ViewContent', 0)}, "
           f"Contact {suma.get('Contact', 0)}; ostatni blok {lag:.1f} h temu")


# ── landingi ──────────────────────────────────────────────────────────────────

def status_url(u):
    req = urllib.request.Request(u, method="GET", headers={"User-Agent": "primaauto-checki/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=20) as r:
            return r.status
    except urllib.error.HTTPError as e:
        return e.code
    except Exception as e:                       # noqa: BLE001 — sieć: każdy błąd to „nie wiem"
        return str(e)[:60]


def sprawdz_landingi(linki):
    if not linki:
        zapisz("Landingi reklam Meta", UWAGA, "żadna żywa reklama nie ma linku w kreacji "
                                              "(reklamy z gotowego posta)")
        return
    with ThreadPoolExecutor(max_workers=6) as ex:
        kody = list(ex.map(status_url, linki))
    zle = [f"{u.split('primaauto.com.pl')[-1][:40]} → {k}" for u, k in zip(linki, kody) if k != 200]
    zapisz("Landingi reklam Meta", AWARIA if zle else OK,
           "; ".join(zle) if zle else f"{len(linki)} adresów, wszystkie 200")


# ── GA4 ───────────────────────────────────────────────────────────────────────

def check_ga4():
    from ga4_query import token, run, rows
    tok = token()

    d = run(tok, {"dateRanges": [{"startDate": "yesterday", "endDate": "yesterday"}],
                  "dimensions": [{"name": "sessionSourceMedium"}],
                  "metrics": [{"name": "sessions"}], "limit": 100})
    _, _, r = rows(d)
    razem = sum(int(m[0]) for _, m in r) or 1
    slepe = sum(int(m[0]) for dim, m in r if dim[0] in ("(not set)", "(data not available)"))
    # Doba zamknięta to jedyny wiarygodny obraz kanałów — „dziś" ma atrybucję policzoną
    # dopiero w części (zmierzone 04.09: 64% sesji bez źródła dziś, 5,4% po dobie).
    kontekst["doba_zrodla"] = [{"zrodlo": dim[0], "sesje": int(m[0])}
                               for dim, m in sorted(r, key=lambda x: -int(x[1][0]))[:10]]
    kontekst["doba_sesje"] = razem
    kontekst["doba_bez_przypisania"] = round(100 * slepe / razem, 1)
    udzial = 100 * slepe / razem
    # Po dobie atrybucja jest już policzona; wynik powyżej 10% znaczy realny ubytek sygnału.
    zapisz("GA4 — źródła przypisane (doba zamknięta)", OK if udzial < 10 else AWARIA,
           f"bez przypisania {udzial:.1f}% ({slepe} z {razem} sesji wczoraj)")

    d = run(tok, {"dateRanges": [{"startDate": "yesterday", "endDate": "yesterday"}],
                  "dimensions": [{"name": "eventName"}], "metrics": [{"name": "eventCount"}],
                  "dimensionFilter": {"filter": {"fieldName": "eventName", "inListFilter": {
                      "values": ["click_phone", "click_whatsapp", "generate_lead", "form_submit"]}}}})
    _, _, r = rows(d)
    ile = {dim[0]: int(m[0]) for dim, m in r}
    kontekst["doba_kontakty"] = ile
    zapisz("GA4 — zdarzenia kontaktu spływają", OK if sum(ile.values()) > 0 else AWARIA,
           ", ".join(f"{k} {v}" for k, v in sorted(ile.items())) or "zero zdarzeń wczoraj")


# ── Google Ads ────────────────────────────────────────────────────────────────

def check_ads():
    try:
        from gads_client import load, refresh, gaql
    except ImportError as e:
        zapisz("Google Ads — konto pracuje", UWAGA, f"brak klienta: {e}")
        return
    oauth, tokeny, cfg = load()
    zapytanie = ("SELECT campaign.name, metrics.cost_micros, metrics.conversions "
                 "FROM campaign WHERE segments.date DURING YESTERDAY "
                 "AND campaign.status = 'ENABLED'")
    try:
        odp = gaql(refresh(oauth, tokeny), cfg["developer_token"], CID_ADS, CID_ADS, zapytanie)
    except Exception as e:                        # noqa: BLE001
        zapisz("Google Ads — konto pracuje", UWAGA, str(e)[:120])
        return
    koszt = konw = 0.0
    ile = 0
    for partia in odp:
        for w in partia.get("results", []):
            ile += 1
            koszt += int(w["metrics"].get("costMicros", 0)) / 1e6
            konw += float(w["metrics"].get("conversions", 0))
    kontekst["doba_ads"] = {"kampanie": ile, "koszt": round(koszt, 2), "konwersje": round(konw, 1)}
    zapisz("Google Ads — konto pracuje", OK if koszt > 0 else AWARIA,
           f"wczoraj {ile} kampanii ENABLED, koszt {koszt:.2f} zł, konwersje {konw:.1f}")


# ── wyjście ───────────────────────────────────────────────────────────────────

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--json", metavar="PLIK", help="zapisz wynik do pliku JSON")
    ap.add_argument("--cicho", action="store_true", help="wypisz tylko to, co nie gra")
    a = ap.parse_args()

    for f in (check_swiezosc, check_logi_bledow, check_meta, check_piksel, check_ga4, check_ads):
        try:
            f()
        except Exception as e:                    # noqa: BLE001 — jeden zdechły check nie kładzie reszty
            zapisz(f.__name__.replace("check_", ""), AWARIA, f"check się wywrócił: {e}")

    awarie = [w for w in wyniki if w["stan"] == AWARIA]
    uwagi = [w for w in wyniki if w["stan"] == UWAGA]

    for w in wyniki:
        if a.cicho and w["stan"] == OK:
            continue
        print(f"  {ZNAK[w['stan']]}  {w['check']:42} {w['opis']}")
    print(f"\n  {len(wyniki) - len(awarie) - len(uwagi)} gra · {len(uwagi)} do sprawdzenia · "
          f"{len(awarie)} awaria")

    if a.json:
        Path(a.json).write_text(json.dumps(
            {"czas": datetime.now().strftime("%Y-%m-%d %H:%M"), "checki": wyniki,
             "awarie": len(awarie), "uwagi": len(uwagi),
             "gra": len(wyniki) - len(awarie) - len(uwagi),
             "doba": kontekst}, ensure_ascii=False, indent=1))

    sys.exit(2 if awarie else (1 if uwagi else 0))


if __name__ == "__main__":
    main()
