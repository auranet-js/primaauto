#!/usr/bin/env python3
"""[KAT] — katalog pojazdów do NOWYCH odbiorców (prospecting dynamiczny). Jedna kampania, jeden zestaw.

Decyzja Janka 15.09: „pokazywać ludziom jak najwięcej prawdziwych aut z cenami”, kontakt dopiero
po obejrzeniu oferty. Ta sama mechanika co `[RMKT]` (karta = `{{vehicle.title}}` + cena z feedu),
ale do ludzi, którzy nas nie znają: Meta dobiera z ~3 000 aut to, co pasuje danej osobie.

Kierowanie (quiz Janka 15.09): Polska, mężczyźni 30–60 jako PROPOZYCJA w ramie 25–65 (Advantage+ audience —
Meta może rozszerzyć), tylko aktualności FB + IG, bez wykluczeń. Przycisk „Skorzystaj z oferty”.
Optymalizacja: ViewContent — `Contact` Meta odrzuca przy kampanii katalogowej (sonda 15.09: 100/2446814).
Kontakt łapią przyciski na ofercie (telefon, WhatsApp z nazwą auta).
Budżet 35 zł/dz — z [VID] + [POST], które idą na pauzę (decyzja Janka 15.09).

Zbudowane po naprawie piksela (GTM v15, 15.09) — ViewContent liczy się już tylko u osób ze zgodą.

    python3 scripts/social/buduj_kat.py            # sonda validate_only, nic nie tworzy
    python3 scripts/social/buduj_kat.py --wgraj    # tworzy, wszystko PAUSED
    python3 scripts/social/buduj_kat.py --wlacz    # [KAT] na ACTIVE + reklama [VID] z9-gt na PAUSED
"""
import argparse
import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api
from przepisz_teksty import D          # bank assetów Google Ads — zdań nie układamy sami
import buduj_rmkt
from buduj_rmkt import ZESTAW_WSZYSTKIE, krok
buduj_rmkt.STAN = 'meta-kat.json'     # krok() zapisuje do buduj_rmkt.STAN — bez tego nadpisuje meta-rmkt.json (15.09)

STAN = 'meta-kat.json'
BUDZET = 3500                          # grosze = 35 zł/dz
OPISY = 'AB'
VID_Z9GT = '120248942512890243'        # reklama [VID] z9-gt — pauzowana przy włączeniu [KAT]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--wgraj', action='store_true')
    ap.add_argument('--wlacz', action='store_true')
    a = ap.parse_args()
    stan = api.stan_wczytaj(STAN)

    if a.wlacz:
        for klucz in ('kampania', 'zestaw', 'ad'):
            r, e = api.post(stan[klucz], {'status': 'ACTIVE'}, waliduj=False)
            print(f'  [KAT] {klucz:9} {stan[klucz]} → {r or e}')
        r, e = api.post(VID_Z9GT, {'status': 'PAUSED'}, waliduj=False)
        print(f'  [VID] z9-gt {VID_Z9GT} → PAUSED {r or e}')
        return

    waliduj = not a.wgraj
    print('SONDA — nic nie powstanie\n' if waliduj else 'ZAPIS — obiekty PAUSED\n')

    kampania = krok('kampania [KAT]', f'{api.ACT}/campaigns', {
        'name': '[KAT] Oferta — nowi odbiorcy (katalog)', 'objective': 'OUTCOME_SALES',
        'status': 'PAUSED', 'special_ad_categories': json.dumps([]), 'buying_type': 'AUCTION',
        'is_adset_budget_sharing_enabled': 'false'}, stan, 'kampania', waliduj)
    if not kampania:
        sys.exit('bez kampanii nie ma czego wieszać')
    if kampania == 'SONDA':
        # zestaw waliduje się tylko pod istniejącą kampanią — podpinamy [RMKT] (ten sam cel),
        # walidacja niczego pod nią nie tworzy
        kampania = api.stan_wczytaj('meta-rmkt.json')['kampania']

    zestaw = krok('zestaw [KAT]', f'{api.ACT}/adsets', {
        'name': '[KAT] PL M 30-60 — cały katalog', 'campaign_id': kampania,
        'status': 'PAUSED', 'daily_budget': BUDZET,
        'billing_event': 'IMPRESSIONS', 'optimization_goal': 'OFFSITE_CONVERSIONS',
        'bid_strategy': 'LOWEST_COST_WITHOUT_CAP',
        'dsa_beneficiary': api.DSA_PODMIOT, 'dsa_payor': api.DSA_PODMIOT,
        'promoted_object': json.dumps({
            'product_set_id': ZESTAW_WSZYSTKIE, 'custom_event_type': 'CONTENT_VIEW',
            'pixel_id': api.PIKSEL}),
        'targeting': json.dumps({
            # Advantage+ audience wymaga ramy 25–65 (sonda 15.09: 1870188/1870189) — M 30–60 idzie
            # jako propozycja (`age_range` + `genders`), zgodnie z decyzją Janka „jako sugestia”.
            'geo_locations': {'countries': ['PL']}, 'age_min': 25, 'age_max': 65,
            'genders': [1], 'age_range': [30, 60],
            'publisher_platforms': ['facebook', 'instagram'],
            'facebook_positions': ['feed'], 'instagram_positions': ['stream'],
            'targeting_automation': {'advantage_audience': 1}})},
        stan, 'zestaw', waliduj)

    tekst = ' '.join(D[k][0] for k in OPISY)
    kre = krok('kreacja [KAT]', f'{api.ACT}/adcreatives', {
        'name': '[KAT] Oferta — nowi odbiorcy',
        'url_tags': api.UTM_TAGI,
        'product_set_id': ZESTAW_WSZYSTKIE,
        'object_story_spec': json.dumps({
            'page_id': api.PAGE, 'instagram_user_id': api.IG,
            'template_data': {
                'message': tekst,
                'name': '{{vehicle.title}}',
                'description': '{{vehicle.price}}',
                'link': 'https://primaauto.com.pl/',
                'call_to_action': {'type': 'GET_OFFER'},
                'multi_share_end_card': True}})},
        stan, 'kreacja', waliduj)

    if zestaw and kre:
        krok('reklama [KAT]', f'{api.ACT}/ads', {
            'name': '[KAT] Oferta — nowi odbiorcy',
            'adset_id': zestaw if zestaw != 'SONDA' else '1',
            'creative': json.dumps({'creative_id': kre if kre != 'SONDA' else '1'}),
            'status': 'PAUSED'}, stan, 'ad', waliduj)

    print(f'\nTekst reklamy: {tekst}')
    print('Wszystko PAUSED. Włączenie (i pauza [VID] z9-gt): --wlacz, na polecenie Janka.')


if __name__ == '__main__':
    main()
