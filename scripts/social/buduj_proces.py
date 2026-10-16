#!/usr/bin/env python3
"""Kampania [PROCES] — film i karuzela „jak wygląda zakup” (T-265 v4) do osób, które oglądały
oferty i się nie odezwały. Buduje wszystko jako PAUSED, nic nie uruchamia.

Dlaczego osobna kampania: `[KAT]` i `[RMKT]` są katalogowe i przyjmują tylko reklamy z katalogu.
Film odpowiada na pytanie „jak to działa i czy to bezpieczne” — dlatego trafia do tych samych
ludzi co `[RMKT]`, ale z inną treścią niż kolejna karuzela aut.

Materiał = wersja zaakceptowana przez Ruslana 15.10 (MD5 filmu d556f91c…, ten sam plik co na
Telegramie). Karuzela bez slajdu 11 (klip AI): Meta przyjmuje najwyżej 10 kart.

    python3 scripts/social/buduj_proces.py --sonda     # validate_only, nic nie powstaje
    python3 scripts/social/buduj_proces.py --buduj
"""
import argparse
import hashlib
import json
import os
import subprocess
import sys
import time

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api
from buduj_foto import wyslij_obraz

STAN = 'meta-proces.json'
FFMPEG = '/home/host476470/bin/ffmpeg'
REEL = '/home/host476470/opt/pptr/prima-reel'
FILM = f'{REEL}/reel-v4-lektor.mp4'
FILM_MD5 = 'd556f91c06a49af4439511a754d1df6c'
FILM_URL = ('https://auratest.pl/fe4f58fec53ctmp/'
            'primaauto-T265-proces-zakupu-reel-v4-lektor-2026-10-15.mp4')
SCRATCH = '/tmp/claude-1584/-home-host476470-projekty-primaauto/proces'
LANDING = 'https://primaauto.com.pl/informacje/proces-zamawiania/'
DOMENA = 'primaauto.com.pl'
BUDZET_GR = '500'

KAMPANIA = '[PROCES] Jak wygląda zakup — film i karuzela'
ZESTAW = 'Oglądający oferty 30 dni bez kontaktu'

# Teksty wyłącznie z napisów filmu zaakceptowanego przez Ruslana — nic spoza materiału.
TEKST = ('Jak wygląda zakup auta z Chin w Prima-Auto? Osiem kroków, od wyboru auta na stronie '
         'po odbiór w Rzeszowie albo pod domem. Zgłoszenie jest bezpłatne i do niczego '
         'nie zobowiązuje. 70% ceny dopłacasz później — bez odsetek.')
NAGLOWEK = 'Auto z Chin — od kliknięcia do odbioru'
OPIS = 'Proces zakupu krok po kroku'

# (plik slajdu, nagłówek karty ≤40 znaków). Slajd 2 bez liczby aut — liczba rotuje,
# a kreacji nie da się edytować.
KARTY = [
    ('slajd-01-cover.png', 'Auto z Chin — od kliknięcia do odbioru'),
    ('slajd-02-lista.png', 'Auta z Chin w jednym miejscu'),
    ('slajd-03-filtr.png', 'Filtrujesz markę i model'),
    ('slajd-04-oferta.png', 'Cena na gotowo'),
    ('slajd-05-spec.png', 'Pełna specyfikacja w tabelach'),
    ('slajd-06-zgl.png', '„Chcę to auto” — zgłoszenie bezpłatne'),
    ('slajd-07-dep.png', 'Zwrotny depozyt uruchamia naszą pracę'),
    ('slajd-08-umowa.png', 'Umowa, gdy auto jest sprawdzone'),
    ('slajd-09-panel.png', 'Wszystko widzisz w swoim panelu'),
    ('slajd-10-fin.png', '70% dopłacasz później — bez odsetek'),
]


def jpg(png):
    """PNG → JPG 1080×1350 (te same piksele, mniejszy plik)."""
    cel = os.path.join(SCRATCH, os.path.basename(png).replace('.png', '.jpg'))
    subprocess.run([FFMPEG, '-y', '-loglevel', 'error', '-i', png, '-q:v', '2', cel], check=True)
    return cel


def czekaj_na_film(vid):
    for _ in range(40):
        d, e = api.get(f'{vid}?fields=status')
        stan = (d or {}).get('status', {}).get('video_status')
        if stan == 'ready':
            return True
        if stan == 'error':
            sys.exit(f'Meta odrzuciła film: {d}')
        time.sleep(15)
    sys.exit('film nie przetworzył się w 10 minut')


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--sonda', action='store_true')
    ap.add_argument('--buduj', action='store_true')
    a = ap.parse_args()
    if not (a.sonda or a.buduj):
        ap.error('podaj --sonda albo --buduj')
    waliduj = not a.buduj
    stan = api.stan_wczytaj(STAN)
    os.makedirs(SCRATCH, exist_ok=True)

    if hashlib.md5(open(FILM, 'rb').read()).hexdigest() != FILM_MD5:
        sys.exit('film różni się od wersji zaakceptowanej przez Ruslana — przerywam')
    kod = api.landing_zyje(LANDING)
    print('LANDING', LANDING, kod)
    if kod != 200:
        sys.exit('landing nie zwraca 200')
    for _, nag in KARTY:
        if len(nag) > 40 or 'homolog' in nag.lower():
            sys.exit(f'karta: {nag}')

    grupy, e = api.get(f'{api.ACT}/customaudiences?fields=id,name&limit=100')
    mam = {g['name']: g['id'] for g in grupy['data']}
    odbiorcy = [{'id': mam['Oglądający oferty — 30 dni'], 'name': 'Oglądający oferty — 30 dni'}]
    wyklucz = [{'id': mam[n], 'name': n} for n in
               ('Kontakt tel./WhatsApp — 180 dni', 'Formularz zamówienia — 180 dni')]

    print('\nMATERIAŁ')
    if waliduj:
        print('  (sonda — nic nie wgrywam, kreacje sprawdzę na pierwszym obrazie, jeśli jest)')
    else:
        if 'video_id' not in stan:
            r, e = api.post(f'{api.ACT}/advideos', {'file_url': FILM_URL,
                                                    'name': 'T-265 proces zakupu v4 lektor'},
                            waliduj=False)
            if e:
                sys.exit(f'film: {e}')
            stan['video_id'] = r['id']
            api.stan_zapisz(STAN, stan)
        print('  film', stan['video_id'])
        czekaj_na_film(stan['video_id'])
        hasze = stan.setdefault('hasze', {})
        for plik, _ in KARTY:
            if plik not in hasze:
                h, e = wyslij_obraz(jpg(os.path.join(REEL, 'karuzela', plik)))
                if e:
                    sys.exit(f'{plik}: {e}')
                hasze[plik] = h
                api.stan_zapisz(STAN, stan)
            print(f'  {plik:22} {hasze[plik]}')

    print('\nKAMPANIA')
    kamp = stan.get('kampania')
    if not kamp:
        r, e = api.post(f'{api.ACT}/campaigns', {
            'name': KAMPANIA, 'objective': 'OUTCOME_TRAFFIC', 'status': 'PAUSED',
            'special_ad_categories': '[]', 'buying_type': 'AUCTION',
            'is_adset_budget_sharing_enabled': 'false'}, waliduj=waliduj)
        print(' ', r or e)
        kamp = (r or {}).get('id')
        if kamp and not waliduj:
            stan['kampania'] = kamp
            api.stan_zapisz(STAN, stan)

    print('ZESTAW')
    zestaw = stan.get('zestaw')
    if not zestaw:
        r, e = api.post(f'{api.ACT}/adsets', {
            'name': ZESTAW, 'campaign_id': kamp or '0', 'status': 'PAUSED',
            'daily_budget': BUDZET_GR, 'billing_event': 'IMPRESSIONS',
            'optimization_goal': 'LANDING_PAGE_VIEWS', 'bid_strategy': 'LOWEST_COST_WITHOUT_CAP',
            'destination_type': 'WEBSITE',
            'dsa_beneficiary': api.DSA_PODMIOT, 'dsa_payor': api.DSA_PODMIOT,
            'targeting': json.dumps({
                'geo_locations': {'countries': ['PL'],
                                  'location_types': ['frequently_in', 'home', 'recent']},
                'age_min': 18, 'age_max': 65,
                'custom_audiences': odbiorcy, 'excluded_custom_audiences': wyklucz,
                'targeting_relaxation_types': {'lookalike': 0, 'custom_audience': 0},
                'targeting_automation': {'advantage_audience': 0},
                'publisher_platforms': ['facebook', 'instagram'],
                'facebook_positions': ['feed', 'facebook_reels', 'story'],
                'instagram_positions': ['stream', 'reels', 'story']})}, waliduj=waliduj)
        print(' ', r or e)
        zestaw = (r or {}).get('id')
        if zestaw and not waliduj:
            stan['zestaw'] = zestaw
            api.stan_zapisz(STAN, stan)

    if waliduj:
        print('\nSonda kończy się tutaj — kreacje wymagają wgranego filmu i obrazów.')
        return

    kreacje = {
        '[PROCES] Film 9:16 z lektorem': {
            'page_id': api.PAGE, 'instagram_user_id': api.IG,
            'video_data': {'video_id': stan['video_id'], 'message': TEKST, 'title': NAGLOWEK,
                           'link_description': OPIS,
                           'image_hash': stan['hasze']['slajd-01-cover.png'],
                           'call_to_action': {'type': 'LEARN_MORE', 'value': {'link': LANDING}}}},
        '[PROCES] Karuzela 10 kroków': {
            'page_id': api.PAGE, 'instagram_user_id': api.IG,
            'link_data': {'link': LANDING, 'message': TEKST, 'caption': DOMENA,
                          'multi_share_optimized': False, 'multi_share_end_card': False,
                          'child_attachments': [
                              {'link': LANDING, 'image_hash': stan['hasze'][p], 'name': n,
                               'call_to_action': {'type': 'LEARN_MORE', 'value': {'link': LANDING}}}
                              for p, n in KARTY]}},
    }
    print('\nREKLAMY')
    for nazwa, spec in kreacje.items():
        kk, rk = f'kreacja:{nazwa}', f'reklama:{nazwa}'
        if kk not in stan:
            r, e = api.post(f'{api.ACT}/adcreatives', {
                'name': nazwa, 'object_story_spec': json.dumps(spec),
                'url_tags': api.UTM_TAGI}, waliduj=False)
            if e:
                print(f'  {nazwa}: kreacja BŁĄD {e}')
                continue
            stan[kk] = r['id']
            api.stan_zapisz(STAN, stan)
        if rk not in stan:
            r, e = api.post(f'{api.ACT}/ads', {
                'name': nazwa, 'adset_id': zestaw,
                'creative': json.dumps({'creative_id': stan[kk]}), 'status': 'PAUSED'},
                waliduj=False)
            if e:
                print(f'  {nazwa}: reklama BŁĄD {e}')
                continue
            stan[rk] = r['id']
            api.stan_zapisz(STAN, stan)
        print(f'  {nazwa}: kreacja {stan[kk]}, reklama {stan[rk]}')

    print('\nWszystko PAUSED. Nic się nie wyświetla.')


if __name__ == '__main__':
    main()
