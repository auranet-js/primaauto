#!/usr/bin/env python3
"""Komplet grup odbiorców z planu kampanii — zakłada brakujące, nie dubluje istniejących.

    python3 scripts/social/grupy_odbiorcow.py            # co jest, czego brakuje
    python3 scripts/social/grupy_odbiorcow.py --zaloz    # dokłada brakujące
    python3 scripts/social/grupy_odbiorcow.py --przemianuj sonda-www "nowa nazwa"

UWAGA: `/act_*/customaudiences` IGNORUJE `validate_only` (zmierzone 28.08 — sonda założyła
trzy prawdziwe grupy). Dlatego `meta_api.post()` ma tu guard, a ten skrypt woła z `waliduj=False`
świadomie i tylko pod `--zaloz`.

Grupy zbierają dopiero od chwili utworzenia — z wyjątkiem tych z piksela, gdzie `prefill=1`
dociąga historię z okna retencji. Dlatego zakładamy je NIEZALEŻNIE od blokady DSA.
"""
import argparse, json, sys
from meta_api import get, post, ACT, PAGE, PIKSEL, IG

DZIEN = 86400


def z_piksela(zdarzenie, dni):
    return {'inclusions': {'operator': 'or', 'rules': [{
        'event_sources': [{'id': PIKSEL, 'type': 'pixel'}],
        'retention_seconds': dni * DZIEN,
        'filter': {'operator': 'and', 'filters': [
            {'field': 'event', 'operator': 'eq', 'value': zdarzenie}]}}]}}


def z_zaangazowania(zrodlo, typ, zdarzenie, dni):
    return {'inclusions': {'operator': 'or', 'rules': [{
        'event_sources': [{'id': zrodlo, 'type': typ}],
        'retention_seconds': dni * DZIEN,
        'filter': {'operator': 'and', 'filters': [
            {'field': 'event', 'operator': 'eq', 'value': zdarzenie}]}}]}}


PLAN = [
    ('Wszyscy odwiedzający — 180 dni',      z_piksela('PageView', 180),     180, 'wykluczenie z prospectingu'),
    ('Oglądający oferty — 30 dni',          z_piksela('ViewContent', 30),    30, 'remarketing gorący'),
    ('Oglądający oferty — 90 dni',          z_piksela('ViewContent', 90),    90, 'remarketing'),
    ('Oglądający oferty — 180 dni',         z_piksela('ViewContent', 180),  180, 'źródło grup podobnych'),
    ('Kontakt tel./WhatsApp — 180 dni',     z_piksela('Contact', 180),      180, 'wykluczenie + grupy podobne'),
    ('Formularz zamówienia — 180 dni',      z_piksela('Lead', 180),         180, 'wykluczenie'),
    ('Zaangażowani ze Strony FB — 365 dni', z_zaangazowania(PAGE, 'page', 'page_engaged', 365),            365, 'remarketing z organiku'),
    ('Zaangażowani z profilu IG — 365 dni', z_zaangazowania(IG, 'ig_business', 'ig_business_profile_engaged', 365), 365, 'remarketing z organiku'),
]


def istniejace():
    d, e = get(f'{ACT}/customaudiences?fields=id,name,subtype,retention_days,approximate_count_lower_bound&limit=100')
    if e:
        sys.exit(f'nie mogę odczytać grup: {e}')
    return {g['name']: g for g in d.get('data', [])}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--zaloz', action='store_true', help='utwórz brakujące grupy')
    ap.add_argument('--przemianuj', nargs=2, metavar=('STARA', 'NOWA'))
    a = ap.parse_args()

    mam = istniejace()

    if a.przemianuj:
        stara, nowa = a.przemianuj
        if stara not in mam:
            sys.exit(f'nie ma grupy „{stara}"')
        d, e = post(mam[stara]['id'], {'name': nowa}, waliduj=False)
        print(('BŁĄD ' + e) if e else f'{stara} → {nowa}')
        return

    brak = [w for w in PLAN if w[0] not in mam]
    for nazwa, _, dni, po_co in PLAN:
        g = mam.get(nazwa)
        stan = f"JEST {g['id']}" if g else 'BRAK'
        print(f'{stan:26s} {nazwa:40s} {po_co}')
    obce = [n for n in mam if n not in {w[0] for w in PLAN}]
    if obce:
        print('\nspoza planu:', ', '.join(obce))
    if not brak:
        print('\nkomplet — nic do zrobienia')
        return
    if not a.zaloz:
        print(f'\n{len(brak)} do założenia — uruchom z --zaloz')
        return

    print()
    for nazwa, regula, dni, _ in brak:
        dane = {'name': nazwa, 'rule': json.dumps(regula), 'retention_days': dni}
        if 'pixel' in json.dumps(regula):
            dane['prefill'] = 1
        d, e = post(f'{ACT}/customaudiences', dane, waliduj=False)
        print(('BŁĄD ' + e) if e else f"utworzona {d['id']}", '·', nazwa)


if __name__ == '__main__':
    main()
