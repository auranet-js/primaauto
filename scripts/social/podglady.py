#!/usr/bin/env python3
"""Podglądy reklam Meta na jednej stronie — dla kogoś, kto nie ma konta na Facebooku.

Menedżer Reklam wymaga logowania i dostępu do portfolio, więc dla Janka jest ślepy.
Meta oddaje jednak przez `/previews` gotowy render reklamy w iframie — ten sam, który
widzi klient — i taki iframe otwiera się w przeglądarce bez logowania.

    python3 scripts/social/podglady.py > tmp/podglady.html          # tylko chodzące
    python3 scripts/social/podglady.py --wszystkie > tmp/all.html

UWAGA: adres iframe niesie token z terminem ważności (Meta daje ~kilka dni). Strona jest
podglądem na teraz, nie archiwum — po wygaśnięciu wygeneruj ją ponownie.
"""
import argparse
import html
import json
import os
import sys
import time

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api

FORMATY = [('MOBILE_FEED_STANDARD', 'Facebook — telefon'),
           ('INSTAGRAM_STANDARD', 'Instagram')]

STYL = """body{font:14px/1.5 system-ui,sans-serif;margin:0;padding:24px;background:#f5f6f7;color:#111}
h1{font-size:20px;margin:0 0 4px} .meta{color:#666;margin-bottom:24px}
.ad{background:#fff;border-radius:10px;padding:16px;margin-bottom:20px;box-shadow:0 1px 3px #0002}
.ad h2{font-size:15px;margin:0 0 2px} .st{font-size:12px;color:#666;margin-bottom:12px}
.ACTIVE{color:#0a7} .PENDING_REVIEW,.IN_PROCESS{color:#c80} .PAUSED{color:#999}
.ramki{display:flex;gap:16px;flex-wrap:wrap} .ramka span{font-size:12px;color:#666;display:block;margin-bottom:4px}
iframe{border:1px solid #ddd;border-radius:6px;background:#fff}"""


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--wszystkie', action='store_true')
    a = ap.parse_args()
    statusy = '["ACTIVE","PENDING_REVIEW","IN_PROCESS"]' if not a.wszystkie \
        else '["ACTIVE","PENDING_REVIEW","IN_PROCESS","PAUSED","ADSET_PAUSED","CAMPAIGN_PAUSED"]'
    d, e = api.get(f'{api.ACT}/ads?fields=id,name,effective_status,campaign{{name}}'
                   f'&effective_status={statusy}&limit=100')
    if e:
        sys.exit(f'nie mogę odczytać reklam: {e}')

    czesci = [f'<h1>Prima-Auto — reklamy na Facebooku i Instagramie</h1>',
              f'<div class=meta>Stan na {time.strftime("%Y-%m-%d %H:%M")}. '
              f'Podglądy renderuje Meta — to dokładnie to, co widzi klient. '
              f'Adresy wygasają po kilku dniach.</div>']
    for ad in sorted(d['data'], key=lambda x: (x['effective_status'] != 'ACTIVE', x['name'])):
        st = ad['effective_status']
        czesci.append(f'<div class=ad><h2>{html.escape(ad["name"])}</h2>'
                      f'<div class=st>{html.escape(ad["campaign"]["name"])} · '
                      f'<b class={st}>{st}</b> · id {ad["id"]}</div><div class=ramki>')
        for fmt, etykieta in FORMATY:
            p, err = api.get(f'{ad["id"]}/previews?ad_format={fmt}')
            ramka = (p or {}).get('data', [{}])[0].get('body')
            if not ramka:
                czesci.append(f'<div class=ramka><span>{etykieta}</span>'
                              f'<i>brak podglądu ({str(err)[:60]})</i></div>')
                continue
            czesci.append(f'<div class=ramka><span>{etykieta}</span>{ramka}</div>')
            time.sleep(1)               # konto ma kroczący limit wywołań
        czesci.append('</div></div>')

    print(f'<!doctype html><meta charset=utf-8><title>Reklamy Prima-Auto</title>'
          f'<style>{STYL}</style>' + '\n'.join(czesci))


if __name__ == '__main__':
    main()
