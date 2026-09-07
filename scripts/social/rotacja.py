#!/usr/bin/env python3
"""Rotacja żywych reklam — wymiana kreacji bez przerwy w dostawie.

Wymiana idzie zawsze parą: nowa reklama wchodzi na ACTIVE, stara schodzi na PAUSED
(nie ARCHIVED — Janek może chcieć wrócić, a archiwum jest jednokierunkowe).
Kolejność ma znaczenie: najpierw włączamy nową, potem gasimy starą. Odwrotnie zestaw
zostaje na chwilę bez żywej reklamy i Meta resetuje fazę uczenia.

    python3 scripts/social/rotacja.py --stan                      # co chodzi
    python3 scripts/social/rotacja.py --wlacz AD_ID --wylacz AD_ID

Bez obu argumentów niczego nie zmienia. Statusy żywych reklam to decyzja Janka —
narzędzie wykonuje ją jednym ruchem, żeby nie było okna bez dostawy.
"""
import argparse
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api


def stan():
    d, e = api.get(f'{api.ACT}/ads?fields=id,name,status,effective_status,'
                   'creative{url_tags},adset{name}'
                   '&effective_status=["ACTIVE","PAUSED","PENDING_REVIEW"]&limit=100')
    if e:
        sys.exit(f'nie mogę odczytać reklam: {e}')
    for a in sorted(d['data'], key=lambda x: (x['effective_status'], x['name'])):
        utm = 'UTM' if (a.get('creative') or {}).get('url_tags') else '— BRAK UTM'
        print(f"  {a['id']:22} {a['effective_status']:15} {a['name'][:40]:40} {utm}")
    return d['data']


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--stan', action='store_true')
    ap.add_argument('--wlacz', metavar='AD_ID')
    ap.add_argument('--wylacz', metavar='AD_ID')
    a = ap.parse_args()

    if a.stan or not (a.wlacz or a.wylacz):
        return stan()

    if a.wlacz:
        r, e = api.post(a.wlacz, {'status': 'ACTIVE'}, waliduj=False)
        print(f'  {a.wlacz} → ACTIVE: {r or e}')
        if e:
            sys.exit('nowa reklama nie ruszyła — starej NIE gaszę, dostawa zostaje')
    if a.wylacz:
        r, e = api.post(a.wylacz, {'status': 'PAUSED'}, waliduj=False)
        print(f'  {a.wylacz} → PAUSED: {r or e}')


if __name__ == '__main__':
    main()
