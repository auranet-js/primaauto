#!/usr/bin/env python3
"""Budowa startowa Meta Ads — wszystko PAUSED, zero wyświetleń, zero wydatku.

Jeden bieg zamiast pięciu ręcznych. Idempotentny: każdy krok najpierw sprawdza, czy
obiekt już istnieje, więc powtórzenie po błędzie nie robi duplikatów w bibliotece.

    python3 scripts/social/buduj_start.py --sonda     # validate_only, nic nie powstaje
    python3 scripts/social/buduj_start.py --buduj     # zapisuje obiekty, wszystkie PAUSED

Czego ten skrypt NIE robi: nie włącza niczego z budżetem i nie podnosi spend_cap.
Start kampanii to publikacja — osobna zgoda Janka, zgodnie z sekcją 9 planu.
"""
import argparse
import json
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api

STAN = 'meta-start.json'
WYKLUCZENIA = ['Wszyscy odwiedzający — 180 dni', 'Kontakt tel./WhatsApp — 180 dni']

# --- [VID] ---------------------------------------------------------------------------
# Kreacja Leopard 5 już istnieje (4545681435750921). Dwie pozostałe powstają z wideo,
# które trzeba najpierw wgrać: python3 rotator_wideo.py --wgraj shark-6 (i z9-gt).
VID_BUDZET_GR = '2500'
# Dziesięć pozycji z dwóch sesji, kolejność po cenie wejścia w serię (04.09).
# Cen w tekstach świadomie NIE MA — zmieniają się codziennie, a reklama zostaje.
USLUGA = ('Weryfikujemy każde auto u źródła, bierzemy na siebie transport, cło, '
          'homologację i rejestrację w Polsce.')
VID_TEKSTY = {
    'jetour-t2':           ('Jetour T2 PHEV — terenowy SUV z Chin',
                            'Terenowy SUV w hybrydzie plug-in, sprowadzany bezpośrednio z Chin. ' + USLUGA),
    'exeed-vx':            ('Exeed VX — duży SUV dla siedmiu osób',
                            'Siedmioosobowy SUV prosto od chińskiego producenta. ' + USLUGA),
    'g318':                ('Deepal G318 — terenowy SUV z zasięgiem elektrycznym',
                            'Terenowy SUV z napędem elektrycznym i agregatem przedłużającym zasięg. ' + USLUGA),
    'leopard-5-czarny':    ('BYD Leopard 5 — terenowy SUV z Chin',
                            'Terenowy SUV w hybrydzie plug-in, sprowadzany bezpośrednio z Chin. ' + USLUGA),
    'leopard-5-niebieski': ('BYD Leopard 5 — terenowy SUV w hybrydzie plug-in',
                            'Rama, napęd 4x4 i hybryda plug-in w jednym aucie. ' + USLUGA),
    'leopard-7':           ('BYD Leopard 7 — SUV terenowy w hybrydzie plug-in',
                            'Terenowy SUV z napędem hybrydowym plug-in, prosto z Chin. ' + USLUGA),
    'z9-gt':               ('Denza Z9 GT — kombi klasy premium z Chin',
                            'Kombi z segmentu premium, w wersji elektrycznej i hybrydowej plug-in. ' + USLUGA),
    'shark-6':             ('BYD Shark 6 — pickup w hybrydzie plug-in',
                            'Pickup z napędem hybrydowym plug-in, sprowadzany bezpośrednio z Chin. ' + USLUGA),
    'lynk-900':            ('Lynk & Co 900 — duży SUV klasy premium',
                            'Sześcioosobowy SUV premium z napędem hybrydowym. ' + USLUGA),
    'n9':                  ('Denza N9 — siedmioosobowy SUV klasy premium',
                            'Duży SUV premium w wersji hybrydowej plug-in. ' + USLUGA),
}
OPIS = 'Prima-Auto — bezpośredni importer aut z Chin'

# --- [POST] --------------------------------------------------------------------------
POST_BUDZET_GR = '1000'
# Wszystkie posty Andrzeja z żywym landingiem (sprawdzone 04.09, kod 200), po cenie.
# Post starzeje się razem z ofertą, do której linkuje — przed włączeniem sprawdź landing.
POSTY_START = [
    ('1146829831857839_122110620609416024', 'Mazda EZ-6 — 140 tys. (21.08)'),
    ('1146829831857839_122109312729416024', 'Hongqi HS6 PHEV — 187 tys. (16.08)'),
    ('1146829831857839_122111908185416024', 'Denza Z9 DM-i — 240 tys. (26.08)'),
    ('1146829831857839_122112117195416024', 'Denza N8L DM — 248 tys. (27.08)'),
    ('1146829831857839_122107717437416024', 'Xiaomi YU7 — 274 tys. (10.08)'),
    ('1146829831857839_122107949187416024', 'BYD Leopard 8 — 279 tys. (11.08)'),
    ('1146829831857839_122109638577416024', 'BYD Leopard 5 — 279 tys. (17.08)'),
    ('1146829831857839_122113717251416024', 'Dongfeng M-Hero 917 — 322 tys. (03.09)'),
    ('1146829831857839_122113858875416024', 'Voyah Taishan Ultra — 378 tys. (04.09)'),
    ('1146829831857839_122111693775416024', 'Zeekr 8X — 523 tys. (25.08)'),
]

# --- grupy podobnych odbiorców -------------------------------------------------------
LAL = [
    ('Podobni do zaangażowanych FB — 1% PL', 'Zaangażowani ze Strony FB — 365 dni'),
    ('Podobni do zaangażowanych IG — 1% PL', 'Zaangażowani z profilu IG — 365 dni'),
]


def grupy():
    d, e = api.get(f'{api.ACT}/customaudiences?fields=id,name&limit=100')
    if e:
        sys.exit(f'nie mogę odczytać grup odbiorców: {e}')
    return {g['name']: g['id'] for g in d.get('data', [])}


def zapisz(stan, klucz, wartosc):
    stan[klucz] = wartosc
    api.stan_zapisz(STAN, stan)


def krok(nazwa, sciezka, dane, stan, klucz, waliduj):
    """Jeden zapis. Pomija się sam, jeśli obiekt z tego kroku już jest w stanie."""
    if klucz in stan:
        print(f'  {nazwa}: już istnieje ({stan[klucz]}) — pomijam')
        return stan[klucz]
    r, e = api.post(sciezka, dane, waliduj=waliduj)
    if e:
        print(f'  {nazwa}: BŁĄD {e}')
        return None
    ident = r.get('id') if isinstance(r, dict) else None
    print(f'  {nazwa}: OK {ident or r}')
    if ident and not waliduj:
        zapisz(stan, klucz, ident)
    return ident


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--sonda', action='store_true', help='validate_only — nic nie powstaje')
    ap.add_argument('--buduj', action='store_true', help='zapisuje obiekty (PAUSED)')
    a = ap.parse_args()
    if not (a.sonda or a.buduj):
        ap.error('podaj --sonda albo --buduj')
    waliduj = not a.buduj
    stan = api.stan_wczytaj(STAN)
    mam = grupy()

    brak = [n for n in WYKLUCZENIA if n not in mam]
    if brak:
        sys.exit('brak grup do wykluczenia: ' + ', '.join(brak))
    wyklucz = [{'id': mam[n], 'name': n} for n in WYKLUCZENIA]
    wyklucz_kontakt = [{'id': mam['Kontakt tel./WhatsApp — 180 dni'],
                        'name': 'Kontakt tel./WhatsApp — 180 dni'}]

    print('\n=== 1. GRUPY PODOBNYCH ODBIORCÓW (lookalike 1% PL) ===')
    print('  UWAGA: /customaudiences IGNORUJE validate_only — w trybie --sonda pomijane.')
    if a.buduj:
        for nazwa, zrodlo in LAL:
            if nazwa in mam:
                print(f'  {nazwa}: już istnieje — pomijam')
                continue
            krok(nazwa, f'{api.ACT}/customaudiences', {
                'name': nazwa,
                'subtype': 'LOOKALIKE',
                'origin_audience_id': mam[zrodlo],
                'lookalike_spec': json.dumps({'type': 'similarity', 'ratio': 0.01,
                                              'country': 'PL'})},
                 stan, f'lal:{nazwa}', waliduj=False)

    print('\n=== 2. [VID] zestaw reklam — PL 25-65, optymalizacja ViewContent ===')
    kampania_vid = '120248809387930243'
    zestaw_vid = krok('zestaw [VID]', f'{api.ACT}/adsets', {
        'name': 'Prospecting PL 25-65 — wideo z sesji',
        'campaign_id': kampania_vid, 'status': 'PAUSED',
        'daily_budget': VID_BUDZET_GR,
        'billing_event': 'IMPRESSIONS', 'optimization_goal': 'OFFSITE_CONVERSIONS',
        'bid_strategy': 'LOWEST_COST_WITHOUT_CAP', 'destination_type': 'WEBSITE',
        'promoted_object': json.dumps({'pixel_id': api.PIKSEL,
                                       'custom_event_type': 'CONTENT_VIEW'}),
        'dsa_beneficiary': api.DSA_PODMIOT, 'dsa_payor': api.DSA_PODMIOT,
        'targeting': json.dumps({
            'geo_locations': {'countries': ['PL']},
            'age_min': 25, 'age_max': 65,
            'publisher_platforms': ['facebook', 'instagram'],
            'excluded_custom_audiences': wyklucz})},
        stan, 'zestaw_vid', waliduj)

    print('\n=== 3. [VID] reklamy — kreacja Leopard 5 + dwie nowe ===')
    wideo = api.stan_wczytaj('meta-wideo.json').get('wgrane', {})
    for klucz, (naglowek, tekst) in VID_TEKSTY.items():
        if klucz not in wideo:
            print(f'  {klucz}: wideo nie jest wgrane do Mety '
                  f'— najpierw rotator_wideo.py --wgraj {klucz}')
            continue
        vid = wideo[klucz]['video_id']
        d, _ = api.get(f'{vid}/thumbnails?fields=uri,is_preferred&limit=5')
        mini = [t for t in (d or {}).get('data', []) if t.get('is_preferred')] \
            or (d or {}).get('data', [])
        if not mini:
            print(f'  {klucz}: Meta nie wygenerowała jeszcze miniatury — spróbuj za minutę')
            continue
        import rotator_wideo
        landing = rotator_wideo.BAZA + rotator_wideo.KATALOG[klucz][2]
        if api.landing_zyje(landing) != 200:
            print(f'  {klucz}: landing nie zwraca 200 — pomijam, reklama marnowałaby budżet')
            continue
        kre = krok(f'kreacja {klucz}', f'{api.ACT}/adcreatives', {
            'name': f'[VID] {klucz}',
            'object_story_spec': json.dumps({
                'page_id': api.PAGE, 'instagram_user_id': api.IG,
                'video_data': {'video_id': vid, 'image_url': mini[0]['uri'],
                               'title': naglowek, 'message': tekst,
                               'link_description': OPIS,
                               'call_to_action': {'type': 'LEARN_MORE',
                                                  'value': {'link': landing}}}})},
            stan, f'kreacja_{klucz}', waliduj)
        if kre and zestaw_vid:
            krok(f'reklama {klucz}', f'{api.ACT}/ads', {
                'name': f'[VID] {klucz}', 'adset_id': zestaw_vid,
                'creative': json.dumps({'creative_id': kre}), 'status': 'PAUSED'},
                stan, f'ad_{klucz}', waliduj)

    print('\n=== 4. [POST] kampania + zestaw — promowanie postów Andrzeja ===')
    kampania_post = krok('kampania [POST]', f'{api.ACT}/campaigns', {
        'name': '[POST] Promowanie postów — ruch', 'objective': 'OUTCOME_TRAFFIC',
        'status': 'PAUSED', 'special_ad_categories': json.dumps([]),
        'buying_type': 'AUCTION',
        # Budżet trzymamy na zestawie, nie na kampanii. Meta i tak żąda jawnej deklaracji,
        # czy zestawy mogą pożyczać sobie 20% puli — przy jednym zestawie to bez znaczenia.
        'is_adset_budget_sharing_enabled': 'false'}, stan, 'kampania_post', waliduj)
    zestaw_post = None
    if kampania_post:
        zestaw_post = krok('zestaw [POST]', f'{api.ACT}/adsets', {
            'name': 'Posty PL 25-65 — ruch', 'campaign_id': kampania_post, 'status': 'PAUSED',
            'daily_budget': POST_BUDZET_GR,
            'billing_event': 'IMPRESSIONS', 'optimization_goal': 'LANDING_PAGE_VIEWS',
            'bid_strategy': 'LOWEST_COST_WITHOUT_CAP', 'destination_type': 'WEBSITE',
            'dsa_beneficiary': api.DSA_PODMIOT, 'dsa_payor': api.DSA_PODMIOT,
            'targeting': json.dumps({
                'geo_locations': {'countries': ['PL']},
                'age_min': 25, 'age_max': 65,
                'publisher_platforms': ['facebook', 'instagram'],
                'excluded_custom_audiences': wyklucz_kontakt})},
            stan, 'zestaw_post', waliduj)

    print('\n=== 5. [POST] reklamy z dwóch postów ===')
    if zestaw_post:
        for post_id, opis in POSTY_START:
            # Klucz z PEŁNEGO identyfikatora posta. Osiem ostatnich znaków jest u Andrzeja
            # wspólne („…75416024"), więc skracanie sklejało dwa różne posty w jeden wpis.
            pid = post_id.split('_')[-1]
            kre = krok(f'kreacja {opis}', f'{api.ACT}/adcreatives',
                       {'name': f'[POST] {opis}', 'object_story_id': post_id},
                       stan, f'kreacja_post_{pid}', waliduj)
            if kre:
                krok(f'reklama {opis}', f'{api.ACT}/ads', {
                    'name': f'[POST] {opis}', 'adset_id': zestaw_post,
                    'creative': json.dumps({'creative_id': kre}), 'status': 'PAUSED'},
                    stan, f'ad_post_{pid}', waliduj)

    print('\nWszystko PAUSED. Zero wyświetleń, zero wydatku.')
    print('Włączenie z budżetem to publikacja — osobna zgoda Janka.')


if __name__ == '__main__':
    main()
