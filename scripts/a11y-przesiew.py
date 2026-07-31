#!/usr/bin/env python3
"""Przesiew dostepnosci primaauto.com.pl przez PageSpeed Insights (Lighthouse/axe).

Audyt WCAG 2.2 AA z 2026-07-31 — patrz docs/audyty/2026-07-31-dostepnosc-wcag22-aa.md
Sluzy do porownania "przed / po" naprawie: baseline z 31.07 lezy w
~/backups/primaauto/2026-07-31/a11y-baseline/

UWAGA: automat pokrywa ok. 30 proc. kryteriow WCAG. Wynik to "gdzie jestesmy",
NIE deklaracja zgodnosci. Z 17 rodzajow naruszen znalezionych w audycie
automat wykryl 5.

Uzycie:
    python3 scripts/a11y-przesiew.py                      # wszystkie typy stron
    python3 scripts/a11y-przesiew.py --out tmp/a11y-po/   # inny katalog na JSON-y
    python3 scripts/a11y-przesiew.py --only home,oferta   # wybrane typy
"""
import argparse
import json
import os
import urllib.parse
import urllib.request
from concurrent.futures import ThreadPoolExecutor

KLUCZ_PLIK = os.path.expanduser("~/secrets/google/psi-crux-key.txt")
API = "https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed"

# 12 typow stron objetych audytem 2026-07-31.
# UWAGA na /zamow/ — bez listing_id to komunikat "Nie wskazano pojazdu",
# a nie kreator zamowienia. Oba stany mierzymy osobno, bo to dwa rozne ekrany.
STRONY = [
    ("home",         "https://primaauto.com.pl/"),
    ("listing",      "https://primaauto.com.pl/samochody/"),
    ("oferta",       "https://primaauto.com.pl/oferta/jetour-shanhai-t1-2024-398935/"),
    ("kontakt",      "https://primaauto.com.pl/kontakt/"),
    ("zamow-realny", "https://primaauto.com.pl/zamow/?listing_id=398935"),
    ("zamow-pusty",  "https://primaauto.com.pl/zamow/"),
    ("szukaj-ok",    "https://primaauto.com.pl/?s=byd"),
    ("szukaj-brak",  "https://primaauto.com.pl/?s=zxqwvbnmasdf"),
    ("o-nas",        "https://primaauto.com.pl/informacje/o-nas/"),
    ("polityka",     "https://primaauto.com.pl/polityka-prywatnosci/"),
    ("regulamin",    "https://primaauto.com.pl/informacje/regulamin/"),
    ("404",          "https://primaauto.com.pl/nie-ma-takiej-strony-test404/"),
]


def audyt(nazwa, url, strategy, katalog, klucz):
    plik = os.path.join(katalog, "%s-%s.json" % (nazwa, strategy))
    if os.path.exists(plik) and os.path.getsize(plik) > 1000:
        with open(plik) as f:
            return nazwa, url, strategy, json.load(f), None
    q = urllib.parse.urlencode({
        "url": url, "strategy": strategy, "category": "accessibility",
        "locale": "pl", "key": klucz,
    })
    try:
        with urllib.request.urlopen(API + "?" + q, timeout=180) as r:
            dane = json.load(r)
    except Exception as e:
        return nazwa, url, strategy, None, str(e)
    with open(plik, "w") as f:
        json.dump(dane, f)
    return nazwa, url, strategy, dane, None


def oblane(dane):
    lh = dane["lighthouseResult"]
    wynik = round(lh["categories"]["accessibility"]["score"] * 100)
    lista = []
    for ident, a in lh["audits"].items():
        if a.get("scoreDisplayMode") in ("notApplicable", "manual", "informative"):
            continue
        if a.get("score") is not None and a["score"] < 1:
            lista.append((len(a.get("details", {}).get("items", [])), ident, a.get("title", "")))
    return wynik, sorted(lista, reverse=True)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--out", default="tmp/a11y-raw", help="katalog na surowe JSON-y")
    ap.add_argument("--only", default="", help="lista nazw po przecinku")
    args = ap.parse_args()

    os.makedirs(args.out, exist_ok=True)
    klucz = open(KLUCZ_PLIK).read().strip()

    strony = STRONY
    if args.only:
        chce = {s.strip() for s in args.only.split(",")}
        strony = [s for s in STRONY if s[0] in chce]

    zadania = [(n, u, s, args.out, klucz) for n, u in strony for s in ("mobile", "desktop")]
    with ThreadPoolExecutor(max_workers=4) as ex:
        wyniki = list(ex.map(lambda z: audyt(*z), zadania))

    kolejnosc = [n for n, _ in strony]
    wyniki.sort(key=lambda w: (kolejnosc.index(w[0]), w[2]))
    for nazwa, url, strategy, dane, blad in wyniki:
        if blad:
            print("\n=== %-14s %-8s BLAD: %s" % (nazwa, strategy, blad[:120]))
            continue
        wynik, lista = oblane(dane)
        print("\n=== %-14s %-8s %3d/100   %s" % (nazwa, strategy, wynik, url))
        for ile, ident, tytul in lista:
            print("      %-26s %-50s %3d elem." % (ident, tytul[:50], ile))
        if not lista:
            print("      bez naruszen wykrywalnych automatem")


if __name__ == "__main__":
    main()
