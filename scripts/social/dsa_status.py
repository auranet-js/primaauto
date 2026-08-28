#!/usr/bin/env python3
"""Czy weryfikacja reklamodawcy (DSA) nadal blokuje reklamy do Polski.

Jedno polecenie zamiast trzech wersji sondy w scratchpadzie. Nic nie tworzy — każde
wywołanie idzie z `validate_only`, więc Meta sprawdza komplet parametrów i uprawnień,
po czym odrzuca obiekt.

    python3 scripts/social/dsa_status.py

Wariant US jest kontrolą, nie ozdobą: jeśli PL pada, a US przechodzi, to znaczy, że
konto, token, piksel i budżet są sprawne, a jedyną przeszkodą jest weryfikacja podmiotu.
Bez tej kontroli każdy błąd na PL wygląda jak awaria konta.
"""
import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api

KAMPANIA_TESTOWA = '120248809387930243'   # [VID], PAUSED — służy tylko za kontener dla sondy


def sonda(nazwa, kraje, dsa=True, dodatki=None):
    dane = {
        'name': 'SONDA DSA', 'campaign_id': KAMPANIA_TESTOWA, 'status': 'PAUSED',
        'daily_budget': '2500', 'billing_event': 'IMPRESSIONS',
        'bid_strategy': 'LOWEST_COST_WITHOUT_CAP', 'optimization_goal': 'LINK_CLICKS',
        'destination_type': 'WEBSITE',
        'targeting': json.dumps({'geo_locations': {'countries': kraje},
                                 'targeting_automation': {'advantage_audience': 0}}),
    }
    if dsa:
        dane['dsa_beneficiary'] = api.DSA_PODMIOT
        dane['dsa_payor'] = api.DSA_PODMIOT
    dane.update(dodatki or {})
    r, e = api.post(f'{api.ACT}/adsets', dane)          # waliduj=True domyślnie
    print(f'{nazwa:38s} → {"PRZECHODZI" if r else e}')
    return bool(r)


def main():
    print('Sonda weryfikacji DSA — validate_only, nic nie powstaje\n')
    pl = sonda('PL · ruch · z dsa_beneficiary/payor', ['PL'])
    sonda('PL · ruch · bez dsa_*', ['PL'], dsa=False)
    us = sonda('US · ruch · kontrola', ['US'], dsa=False)
    pl_konw = sonda('PL · konwersje · z dsa_*', ['PL'], dodatki={
        'optimization_goal': 'OFFSITE_CONVERSIONS',
        'promoted_object': json.dumps({'pixel_id': api.PIKSEL, 'custom_event_type': 'CONTENT_VIEW'})})

    print()
    if pl and pl_konw:
        print('BLOKER ZDJĘTY — zestawy reklam do Polski przechodzą walidację.')
        print('Następny ruch: zestaw + reklama, potem decyzja o włączeniu z budżetem.')
    elif us:
        print('BLOKER TRWA — konto jest sprawne (US przechodzi), stoi wyłącznie weryfikacja podmiotu.')
        print('Zdejmuje ją Ruslan w Menedżerze Reklam: dokumenty firmy, beneficjent i płatnik.')
    else:
        print('Pada także kontrola na US — to nie jest DSA. Sprawdź token, konto i budżet.')


if __name__ == '__main__':
    main()
