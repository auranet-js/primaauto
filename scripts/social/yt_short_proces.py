#!/usr/bin/env python3
"""YouTube Short „jak wygląda zakup” (T-265 v4 z lektorem) — upload jako zaplanowany.

Pionowy film ≤60 s YouTube sam klasyfikuje jako Short. Film ma końcówkę z Veo, więc
`containsSyntheticMedia: true`. Treść opisu zaakceptowana przez Janka 16.10.

    python3 scripts/social/yt_short_proces.py --sonda   # pokazuje metadane, nic nie wysyła
    python3 scripts/social/yt_short_proces.py --wyslij
"""
import argparse
import hashlib
import json
import sys
import urllib.request

import urllib.parse

SEKRETY = '/home/host476470/secrets/google/'


def access_token():
    """Token z youtube-tokens.json. Nie importujemy youtube-auth.py — ten plik przy imporcie
    wykonuje wymianę kodu OAuth z sys.argv (zaliczone 16.10)."""
    c = json.load(open(SEKRETY + 'oauth-desktop-client.json'))['installed']
    t = json.load(open(SEKRETY + 'youtube-tokens.json'))
    dane = urllib.parse.urlencode({'client_id': c['client_id'], 'client_secret': c['client_secret'],
                                   'refresh_token': t['refresh_token'],
                                   'grant_type': 'refresh_token'}).encode()
    return json.load(urllib.request.urlopen('https://oauth2.googleapis.com/token', dane))['access_token']

FILM = '/home/host476470/opt/pptr/prima-reel/reel-v4-lektor.mp4'
FILM_MD5 = 'd556f91c06a49af4439511a754d1df6c'
PUBLIKACJA = '2026-10-16T16:00:00Z'     # 18:00 w Polsce (CEST)

META = {
    'snippet': {
        'title': 'Jak kupić auto z Chin? Cały proces w 50 sekund | Prima-Auto',
        'description': (
            'Jak wygląda zakup auta z Chin w Prima-Auto? 8 kroków — od wyboru auta na stronie '
            'po odbiór w Rzeszowie albo pod domem. Zgłoszenie jest bezpłatne i do niczego '
            'nie zobowiązuje, 70% ceny dopłacasz później — bez odsetek.\n'
            'Szczegóły: https://primaauto.com.pl/informacje/proces-zamawiania/\n'
            '#autazchin #importautzchin #shorts'),
        'categoryId': '2',
        'defaultLanguage': 'pl', 'defaultAudioLanguage': 'pl',
        'tags': ['auta z Chin', 'import aut z Chin', 'Prima-Auto', 'jak kupić auto z Chin'],
    },
    'status': {
        'privacyStatus': 'private', 'publishAt': PUBLIKACJA,
        'selfDeclaredMadeForKids': False, 'containsSyntheticMedia': True,
    },
}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--sonda', action='store_true')
    ap.add_argument('--wyslij', action='store_true')
    a = ap.parse_args()
    dane = open(FILM, 'rb').read()
    if hashlib.md5(dane).hexdigest() != FILM_MD5:
        sys.exit('film różni się od wersji zaakceptowanej — przerywam')
    print(json.dumps(META, ensure_ascii=False, indent=1))
    if not a.wyslij:
        return
    tok = access_token()
    req = urllib.request.Request(
        'https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status',
        data=json.dumps(META).encode(),
        headers={'Authorization': f'Bearer {tok}', 'Content-Type': 'application/json; charset=UTF-8',
                 'X-Upload-Content-Type': 'video/mp4', 'X-Upload-Content-Length': str(len(dane))})
    sesja = urllib.request.urlopen(req).headers['Location']
    req = urllib.request.Request(sesja, data=dane, method='PUT',
                                 headers={'Content-Type': 'video/mp4'})
    wynik = json.load(urllib.request.urlopen(req, timeout=600))
    print('\nWGRANE', wynik['id'], wynik['status'])


if __name__ == '__main__':
    main()
