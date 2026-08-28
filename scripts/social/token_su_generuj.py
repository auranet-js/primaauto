#!/usr/bin/env python3
"""Generowanie tokenu System Usera Mety z wybranym zestawem uprawnień.

Od 2026-08-28 mamy klucz aplikacji „Prima-Auto API" (`~/secrets/meta/app-primaauto.json`),
więc rozszerzenie uprawnień nie wymaga już wchodzenia Ruslana w Business Settings —
robimy to sami przez `POST /{system-user-id}/access_tokens`.

    python3 scripts/social/token_su_generuj.py            # pokaż obecne uprawnienia
    python3 scripts/social/token_su_generuj.py --generuj  # wypisz nowy token na ekran

Nowy token trafia na stdout, NIE do pliku — podmianę `tokens/primaauto-portfolio-ruslan.txt`
robisz świadomie, po sprawdzeniu, że nowy działa. Stary zostaje ważny (Meta nie unieważnia
poprzednich tokenów SU przy generowaniu kolejnego).

`pages_manage_posts` świadomie nie ma na liście — ustalenie z 27.08: publikuje Andrzej,
my promujemy to, co napisał. Dołożenie tego uprawnienia to osobna decyzja Janka, nie
efekt uboczny rozszerzania scope'ów.
"""
import argparse
import hashlib
import hmac
import json
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

KONFIG = Path.home() / 'secrets/meta/app-primaauto.json'
TOKEN_PLIK = Path.home() / 'secrets/meta/tokens/primaauto-portfolio-ruslan.txt'

UPRAWNIENIA = [
    'catalog_management',
    'pages_show_list',
    'ads_management',
    'ads_read',
    'business_management',
    'instagram_basic',
    'instagram_manage_insights',
    'instagram_content_publish',
    'pages_read_engagement',
    'pages_manage_ads',
    'read_insights',            # statystyki Strony — zasięg organiczny postów
    'pages_read_user_content',  # reakcje i komentarze
]


def wolaj(url, dane, metoda='POST'):
    if metoda == 'GET':
        zapytanie = urllib.request.Request(f'{url}?{urllib.parse.urlencode(dane)}')
    else:
        zapytanie = urllib.request.Request(url, urllib.parse.urlencode(dane).encode())
    try:
        return json.loads(urllib.request.urlopen(zapytanie).read().decode()), None
    except urllib.error.HTTPError as e:
        return None, e.read().decode()


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--generuj', action='store_true', help='wypisz nowy token na ekran')
    args = p.parse_args()

    cfg = json.loads(KONFIG.read_text())
    token = TOKEN_PLIK.read_text().strip()
    wersja = cfg.get('api_version', 'v25.0')
    app_token = f"{cfg['app_id']}|{cfg['app_secret']}"

    stan, blad = wolaj(
        f'https://graph.facebook.com/{wersja}/debug_token',
        {'input_token': token, 'access_token': app_token}, 'GET')
    if blad:
        raise SystemExit(f'debug_token odmówił: {blad[:400]}')
    obecne = stan['data'].get('scopes', [])
    print(f"Obecny token: {len(obecne)} uprawnień")
    for u in UPRAWNIENIA:
        print(f"  {'JEST ' if u in obecne else 'BRAK '} {u}")

    if not args.generuj:
        print('\nNic nie wygenerowano. Dodaj --generuj, żeby dostać nowy token.')
        return

    proof = hmac.new(cfg['app_secret'].encode(), token.encode(), hashlib.sha256).hexdigest()
    wynik, blad = wolaj(
        f"https://graph.facebook.com/{wersja}/{cfg['system_user_id']}/access_tokens",
        {'business_app': cfg['app_id'], 'scope': ','.join(UPRAWNIENIA),
         'appsecret_proof': proof, 'access_token': token})
    if blad:
        raise SystemExit(f'generowanie odmówione: {blad[:600]}')
    print('\nNOWY TOKEN:')
    print(wynik['access_token'])


if __name__ == '__main__':
    main()
