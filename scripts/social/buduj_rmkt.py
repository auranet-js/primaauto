#!/usr/bin/env python3
"""[RMKT] — remarketing dynamiczny z katalogu pojazdów. Dwa zestawy, jedna kampania.

Po co osobno od `buduj_start.py`: tam kreacja jest stała (film, post), tu treść karty
generuje Meta z feedu przy każdym wyświetleniu. Nazwa karty to `{{vehicle.title}}`
z ogłoszenia, opis to cena — czyli ta sama zasada, co przy karuzelach `[FOTO]`:
nazwy aut biorą się z tytułów ogłoszeń, nie z opisu cech.

Kierowanie (ustalone 07.09, zasięgi zmierzone przez `delivery_estimate`):
  · „Oglądane"  — lista `Oglądający oferty — 30 dni` (1 400-1 700), katalog cały
  · „Na placu"  — lista `Wszyscy odwiedzający — 180 dni` (2 200-2 600), zestaw 19 aut
Obydwa wykluczają `Kontakt tel./WhatsApp — 180 dni` i `Formularz zamówienia — 180 dni`:
za kogoś, kto już się odezwał, drugi raz nie płacimy.

Optymalizacja na ViewContent, nie na kontakt — świadomie. Meta uczy się przy ~15-20
zdarzeniach tygodniowo, kontaktów mamy jeden na cztery doby. Rozliczamy się z kontaktów,
uczymy na ViewContent; sygnał przełączymy, gdy kontaktów uzbiera się dość.

    python3 scripts/social/buduj_rmkt.py            # sonda validate_only, nic nie tworzy
    python3 scripts/social/buduj_rmkt.py --wgraj    # tworzy, wszystko PAUSED
"""
import argparse
import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api
from przepisz_teksty import D          # bank assetów Google Ads — zdań nie układamy sami

STAN = 'meta-rmkt.json'

# Listy odbiorców — identyfikatory z konta, sprawdzone 07.09.
OGL_30 = '120248812441370243'          # Oglądający oferty — 30 dni
WSZYSCY_180 = '120248812441060243'     # Wszyscy odwiedzający — 180 dni
WYKLUCZ = ['120248812441730243',       # Kontakt tel./WhatsApp — 180 dni
           '120248812442610243']       # Formularz zamówienia — 180 dni

ZESTAW_WSZYSTKIE = '2149568469325662'  # katalog w całości, 2 941 aut
ZESTAW_NA_PLACU = '1410119631315688'   # Na placu w Polsce, 19 aut

# (nazwa, budżet w groszach, lista odbiorców, zestaw produktów, opisy z banku D)
ZESTAWY = [
    ('Oglądane — 30 dni', 700, OGL_30, ZESTAW_WSZYSTKIE, 'AG'),
    ('Na placu w Polsce', 500, WSZYSCY_180, ZESTAW_NA_PLACU, 'AE'),
]


def krok(opis, sciezka, dane, stan, klucz, waliduj):
    if stan.get(klucz):
        print(f'  {opis}: już jest ({stan[klucz]})')
        return stan[klucz]
    r, e = api.post(sciezka, dane, waliduj=waliduj)
    if e:
        print(f'  {opis}: BŁĄD {e}')
        return None
    if waliduj:
        print(f'  {opis}: sonda OK {r}')
        return 'SONDA'
    stan[klucz] = r['id']
    api.stan_zapisz(STAN, stan)
    print(f'  {opis}: {r["id"]}')
    return r['id']


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--wgraj', action='store_true')
    ap.add_argument('--wlacz', action='store_true',
                    help='włącza gotową kampanię: zestawy i reklamy na ACTIVE')
    a = ap.parse_args()
    stan = api.stan_wczytaj(STAN)

    if a.wlacz:
        # Kolejność od góry: kampania, potem zestawy, na końcu reklamy. Odwrotnie Meta
        # potrafi odrzucić włączenie reklamy pod wstrzymanym zestawem.
        for klucz, ident in sorted(stan.items(), key=lambda x: ('ad_' in x[0], 'zestaw' in x[0])):
            r, e = api.post(ident, {'status': 'ACTIVE'}, waliduj=False)
            print(f'  {klucz:28} {ident:22} → {r or e}')
        return

    waliduj = not a.wgraj
    print('SONDA — nic nie powstanie\n' if waliduj else 'ZAPIS — obiekty PAUSED\n')

    kampania = krok('kampania [RMKT]', f'{api.ACT}/campaigns', {
        'name': '[RMKT] Remarketing dynamiczny — katalog', 'objective': 'OUTCOME_SALES',
        'status': 'PAUSED', 'special_ad_categories': json.dumps([]), 'buying_type': 'AUCTION',
        'is_adset_budget_sharing_enabled': 'false'}, stan, 'kampania', waliduj)
    if not kampania:
        sys.exit('bez kampanii nie ma czego wieszać')
    if kampania == 'SONDA':
        # Meta waliduje zestaw tylko przy istniejącym campaign_id, więc do sondy
        # podpinamy kampanię [VID] — walidacja niczego pod nią nie tworzy.
        kampania = api.stan_wczytaj('meta-start.json').get('kampania_post')
        if not kampania:
            sys.exit('sonda potrzebuje istniejącej kampanii do walidacji zestawu')

    for nazwa, budzet, lista, produkty, opisy in ZESTAWY:
        zestaw = krok(f'zestaw {nazwa}', f'{api.ACT}/adsets', {
            'name': f'[RMKT] {nazwa}', 'campaign_id': kampania,
            'status': 'PAUSED', 'daily_budget': budzet,
            'billing_event': 'IMPRESSIONS', 'optimization_goal': 'OFFSITE_CONVERSIONS',
            'bid_strategy': 'LOWEST_COST_WITHOUT_CAP',
            'dsa_beneficiary': api.DSA_PODMIOT, 'dsa_payor': api.DSA_PODMIOT,
            'promoted_object': json.dumps({
                'product_set_id': produkty, 'custom_event_type': 'CONTENT_VIEW',
                'pixel_id': api.PIKSEL}),
            'targeting': json.dumps({
                'geo_locations': {'countries': ['PL']},
                'publisher_platforms': ['facebook', 'instagram'],
                'custom_audiences': [{'id': lista}],
                'excluded_custom_audiences': [{'id': x} for x in WYKLUCZ],
                # Meta nie rozszerza grupy — remarketing ma mówić do tych, których znamy.
                'targeting_automation': {'advantage_audience': 0}})},
            stan, f'zestaw_{nazwa}', waliduj)

        tekst = ' '.join(D[k][0] for k in opisy)
        kre = krok(f'kreacja {nazwa}', f'{api.ACT}/adcreatives', {
            'name': f'[RMKT] {nazwa}',
            'url_tags': api.UTM_TAGI,
            'product_set_id': produkty,
            'object_story_spec': json.dumps({
                'page_id': api.PAGE, 'instagram_user_id': api.IG,
                'template_data': {
                    'message': tekst,
                    'name': '{{vehicle.title}}',
                    'description': '{{vehicle.price}}',
                    'link': 'https://primaauto.com.pl/',
                    'call_to_action': {'type': 'LEARN_MORE'},
                    'multi_share_end_card': True}})},
            stan, f'kreacja_{nazwa}', waliduj)

        if zestaw and kre:
            krok(f'reklama {nazwa}', f'{api.ACT}/ads', {
                'name': f'[RMKT] {nazwa}',
                'adset_id': zestaw if zestaw != 'SONDA' else '1',
                'creative': json.dumps({'creative_id': kre if kre != 'SONDA' else '1'}),
                'status': 'PAUSED'}, stan, f'ad_{nazwa}', waliduj)

    print('\nWszystko PAUSED. Włączenie z budżetem to osobna decyzja.')


if __name__ == '__main__':
    main()
