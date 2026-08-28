#!/usr/bin/env python3
"""Rotacja kampanii [VID] — kreacje z materiału sesji na Dysku Google.

Jak `rotator_postow.py`: pokazuje, co dziś pracuje i z jakim wynikiem, obok tego cały
dostępny materiał — a decyzję podejmuje Janek. Różnica jest w źródle kreacji: tu plik
trzeba najpierw wgrać do Mety (`--wgraj`), dopiero potem staje się reklamą (`--wstaw`).

    python3 scripts/social/rotator_wideo.py                 # zestawienie do decyzji
    python3 scripts/social/rotator_wideo.py --wgraj shark-6
    python3 scripts/social/rotator_wideo.py --wstaw shark-6
    python3 scripts/social/rotator_wideo.py --wyjmij 1203456789

Materiał zmienia się rzadko — sesja raz na kilka tygodni. Katalog poniżej jest listą
tego, co leży na Dysku; po nowej sesji dopisujemy pozycje i landing, reszta działa bez zmian.

Faststart: Meta przyjmie plik bez `moov` na początku, ale odtwarzanie startuje dopiero
po pobraniu całości. Sprawdzamy to pierwszymi 256 kB przez `Range`, bez ściągania pliku,
a `--wgraj` remuksuje w locie (`ffmpeg -c copy -movflags +faststart`) — bez rekompresji.
"""
import argparse
import json
import os
import re
import subprocess
import sys
import urllib.request
from datetime import datetime, timezone

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api
import drive_lib

STAN = 'meta-wideo.json'
SCRATCH = '/tmp/claude-1584/-home-host476470-projekty-primaauto/wideo'
FFMPEG = '/home/host476470/bin/ffmpeg'
SLOTY = 3          # ile kreacji wideo trzymamy równolegle
KADR = '9:16'      # domyślny kadr do reklam — poziome trzymamy na YouTube i Demand Gen

# Klucz → landing. Nazwa folderu na Dysku dopasowywana jest po kluczu, nie odwrotnie:
# nazwy folderów bywają niekonsekwentne (`Exceed-VX`, `Denza-Z9GT`), klucz jest stabilny.
KATALOG = {
    'leopard-5-czarny':    ('Leopard-5', 'Czarny',    '/samochody/byd/leopard-5/#oferty'),
    'leopard-5-niebieski': ('Leopard-5', 'Niebieski', '/samochody/byd/leopard-5/#oferty'),
    'shark-6':             ('Byd-Shark-6', None,      '/samochody/byd/shark-6/#oferty'),
    'z9-gt':               ('Denza-Z9GT', None,       '/samochody/denza/z9-gt-dm-i/#oferty'),
    'n9':                  ('Denza-N9', None,         '/samochody/denza/n9-dm-i/#oferty'),
    'g318':                ('Deepal-G318', None,      '/samochody/deepal/g318/#oferty'),
    'lynk-900':            ('Lynk-Co-900', None,      '/samochody/lynk-co/900/#oferty'),
    'exeed-vx':            ('Exceed-VX', 'Granatowy', '/samochody/exeed/vx/#oferty'),
    'leopard-7':           ('Leopard-7', None,        '/samochody/byd/leopard-7/#oferty'),
    'jetour-t2':           ('Jetour-T2', 'Srebrny',   '/samochody/jetour/t2/#oferty'),
}
BAZA = 'https://primaauto.com.pl'


def _norm(s):
    return re.sub(r'[^a-z0-9]', '', (s or '').lower())


def material(tok):
    """Mapuje klucze katalogu na pliki z Dysku w wybranym kadrze."""
    filmy = drive_lib.filmy_sesji(tok, kadr=KADR)
    out = {}
    for klucz, (folder, wariant, landing) in KATALOG.items():
        pasuje = [f for f in filmy if _norm(f['model']) == _norm(folder)
                  and (wariant is None or _norm(f['wariant']) == _norm(wariant))]
        out[klucz] = {'plik': pasuje[0] if pasuje else None, 'landing': BAZA + landing}
    return out


def faststart(file_id, tok):
    """`moov` przed `mdat` = strumień startuje od razu. Czytamy 256 kB, nie cały plik."""
    req = urllib.request.Request(
        f'https://www.googleapis.com/drive/v3/files/{file_id}?alt=media',
        headers={'Authorization': 'Bearer ' + tok, 'Range': 'bytes=0-262143'})
    try:
        glowa = urllib.request.urlopen(req).read()
    except Exception:
        return None
    atomy = []
    i = 0
    while i + 8 <= len(glowa) and len(atomy) < 6:
        rozmiar = int.from_bytes(glowa[i:i + 4], 'big')
        nazwa = glowa[i + 4:i + 8].decode('latin-1')
        if not re.match(r'^[a-zA-Z0-9 ]{4}$', nazwa) or rozmiar < 8:
            break
        atomy.append(nazwa)
        if nazwa in ('moov', 'mdat'):
            return nazwa == 'moov'
        i += rozmiar
    return None


def wyniki_reklamy(ad_id):
    pola = 'impressions,reach,clicks,ctr,spend,actions,video_p75_watched_actions'
    d, e = api.get(f'{ad_id}/insights?fields={pola}&date_preset=maximum')
    if e or not (d or {}).get('data'):
        return {}
    w = d['data'][0]
    akcje = {a['action_type']: a['value'] for a in w.get('actions', [])}
    p75 = (w.get('video_p75_watched_actions') or [{}])[0].get('value', '0')
    return {'wysw': w.get('impressions', '0'), 'zasieg': w.get('reach', '0'),
            'kliki': w.get('clicks', '0'), 'ctr': w.get('ctr', '0'),
            'koszt': w.get('spend', '0'), 'p75': p75,
            'kontakt': akcje.get('offsite_conversion.fb_pixel_custom', '0')}


def sekcja_aktywne(stan):
    aktywne = stan.get('aktywne', [])
    print(f'\n=== CO DZIŚ PRACUJE ({len(aktywne)}/{SLOTY} slotów) ===')
    if not aktywne:
        print('nic — kampania [VID] ma kreację, ale nie ma zestawu reklam (bloker DSA)')
        return
    print(f"{'od dni':>6}  {'reklama':>16}  {'wyśw.':>8} {'zasięg':>8} {'kliki':>6} {'CTR':>6} "
          f"{'koszt':>8} {'do 75%':>7}  materiał")
    for a in aktywne:
        dni = (datetime.now(timezone.utc) - datetime.fromisoformat(a['od'])).days
        w = wyniki_reklamy(a['ad_id']) if a.get('ad_id') else {}
        print(f"{dni:>6}  {a.get('ad_id','?'):>16}  {w.get('wysw','–'):>8} {w.get('zasieg','–'):>8} "
              f"{w.get('kliki','–'):>6} {str(w.get('ctr','–'))[:5]:>6} {w.get('koszt','–'):>8} "
              f"{w.get('p75','–'):>7}  {a['klucz']}")
    print('\n  wyjęcie:  --wyjmij <reklama>')


def sekcja_material(stan):
    tok = drive_lib.access_token()
    mat = material(tok)
    wgrane = stan.get('wgrane', {})
    historia = stan.get('historia', {})
    aktywne = {a['klucz'] for a in stan.get('aktywne', [])}

    print(f'\n=== MATERIAŁ NA DYSKU — kadr {KADR} ===')
    print(f"{'klucz':>18}  {'MB':>6} {'fast':>5} {'land':>5} {'w Mecie':>16}  uwagi")
    for klucz, d in mat.items():
        p = d['plik']
        if not p:
            print(f'{klucz:>18}  {"BRAK PLIKU NA DYSKU":>34}')
            continue
        fs = faststart(p['id'], tok)
        kod = api.landing_zyje(d['landing'].split('#')[0])
        vid = wgrane.get(klucz, {}).get('video_id', '–')
        uwagi = []
        if klucz in aktywne:
            uwagi.append('PRACUJE TERAZ')
        elif klucz in historia:
            uwagi.append(f"był w rotacji ({historia[klucz].get('do','')[:10]})")
        if fs is False:
            uwagi.append('remuks przed wgraniem')
        elif fs is None:
            uwagi.append('nie rozpoznałem nagłówka')
        if kod != 200:
            uwagi.append(f'landing {kod}')
        print(f"{klucz:>18}  {p['rozmiar']/1048576:>6.1f} "
              f"{('tak' if fs else 'NIE' if fs is False else '?'):>5} {kod:>5} {str(vid):>16}  {'; '.join(uwagi)}")
    print('\n  wgranie do Mety:  --wgraj <klucz>        wstawienie do kampanii:  --wstaw <klucz>')


def wgraj(klucz, stan):
    if klucz not in KATALOG:
        return print('nieznany klucz. Dostępne:', ', '.join(KATALOG))
    tok = drive_lib.access_token()
    d = material(tok)[klucz]
    p = d['plik']
    if not p:
        return print('nie ma pliku na Dysku dla', klucz)
    os.makedirs(SCRATCH, exist_ok=True)
    lokalny = os.path.join(SCRATCH, f'{klucz}.mp4')
    print(f"pobieram {p['nazwa']} ({p['rozmiar']/1048576:.1f} MB)…")
    drive_lib.pobierz(p['id'], lokalny, tok)

    if faststart(p['id'], tok) is False:
        cel = os.path.join(SCRATCH, f'{klucz}-fs.mp4')
        print('remuks faststart (bez rekompresji)…')
        r = subprocess.run([FFMPEG, '-y', '-i', lokalny, '-c', 'copy',
                            '-movflags', '+faststart', cel], capture_output=True, text=True)
        if r.returncode != 0:
            return print('ffmpeg padł:', r.stderr[-300:])
        lokalny = cel

    print('wysyłam do Mety…')
    vid, blad = _wyslij_wideo(lokalny, f'{klucz} {KADR}')
    if blad:
        return print('BŁĄD wgrania:', blad)
    stan.setdefault('wgrane', {})[klucz] = {
        'video_id': vid, 'plik': p['nazwa'], 'kiedy': datetime.now(timezone.utc).isoformat()}
    api.stan_zapisz(STAN, stan)
    print('WGRANE — video_id', vid, '(prywatne, nie pojawia się na Stronie)')


def _wyslij_wideo(sciezka, nazwa):
    """Multipart do /advideos. Bez zewnętrznych bibliotek — jeden boundary, dwa pola."""
    granica = '----primaauto' + datetime.now().strftime('%H%M%S%f')
    dane = open(sciezka, 'rb').read()
    czesci = []
    for pole, wartosc in [('access_token', api.token()), ('name', nazwa)]:
        czesci.append(f'--{granica}\r\nContent-Disposition: form-data; name="{pole}"\r\n\r\n{wartosc}\r\n'.encode())
    czesci.append(f'--{granica}\r\nContent-Disposition: form-data; name="source"; '
                  f'filename="{os.path.basename(sciezka)}"\r\n'
                  f'Content-Type: video/mp4\r\n\r\n'.encode())
    czesci.append(dane)
    czesci.append(f'\r\n--{granica}--\r\n'.encode())
    ciało = b''.join(czesci)
    req = urllib.request.Request(api.GRAPH + api.ACT + '/advideos', data=ciało,
                                 headers={'Content-Type': f'multipart/form-data; boundary={granica}'})
    try:
        return json.load(urllib.request.urlopen(req, timeout=600))['id'], None
    except Exception as e:
        tresc = e.read().decode()[:250] if hasattr(e, 'read') else str(e)
        return None, tresc


def infrastruktura():
    braki, kampania, zestaw = [], None, None
    d, _ = api.get(f'{api.ACT}/campaigns?fields=id,name,status&limit=50')
    for c in (d or {}).get('data', []):
        if c['name'].startswith('[VID]'):
            kampania = c
    if not kampania:
        braki.append('kampania [VID] nie istnieje')
    else:
        d, _ = api.get(f"{kampania['id']}/adsets?fields=id,name,status&limit=20")
        zestaw = ((d or {}).get('data') or [None])[0]
        if not zestaw:
            braki.append('zestaw reklam w [VID] nie istnieje — blokuje go weryfikacja DSA')
    return kampania, zestaw, braki


def wstaw(klucz, stan):
    wgrane = stan.get('wgrane', {}).get(klucz)
    if not wgrane:
        return print(f'{klucz} nie jest wgrane do Mety — najpierw --wgraj {klucz}')
    kampania, zestaw, braki = infrastruktura()
    if braki:
        print('WSTRZYMANE — nic nie wysłano:')
        for b in braki:
            print('  ·', b)
        return
    if len(stan.get('aktywne', [])) >= SLOTY:
        return print(f'wszystkie {SLOTY} sloty zajęte — najpierw --wyjmij')
    tok = api.token()
    d, _ = api.get(f"{wgrane['video_id']}/thumbnails")
    mini = [t for t in (d or {}).get('data', []) if t.get('is_preferred')] or (d or {}).get('data', [])
    if not mini:
        return print('Meta nie wygenerowała jeszcze miniatury — spróbuj za minutę')
    landing = BAZA + KATALOG[klucz][2]
    spec = {'page_id': api.PAGE, 'instagram_user_id': api.IG, 'video_data': {
        'video_id': wgrane['video_id'], 'image_url': mini[0]['uri'],
        'call_to_action': {'type': 'LEARN_MORE', 'value': {'link': landing}}}}
    r, e = api.post(f'{api.ACT}/adcreatives', {'name': f'[VID] {klucz}',
                                               'object_story_spec': json.dumps(spec)}, waliduj=False)
    if e:
        return print('BŁĄD kreacji:', e)
    kre = r['id']
    r, e = api.post(f'{api.ACT}/ads', {'name': f'[VID] {klucz}', 'adset_id': zestaw['id'],
                                       'creative': json.dumps({'creative_id': kre}),
                                       'status': 'PAUSED'}, waliduj=False)
    if e:
        return print('BŁĄD reklamy:', e)
    stan.setdefault('aktywne', []).append({'klucz': klucz, 'ad_id': r['id'], 'kreacja': kre,
                                           'od': datetime.now(timezone.utc).isoformat()})
    api.stan_zapisz(STAN, stan)
    print(f"WSTAWIONE {klucz} → reklama {r['id']}, status PAUSED.")


def wyjmij(ad_id, stan):
    poz = [a for a in stan.get('aktywne', []) if a.get('ad_id') == ad_id]
    if not poz:
        return print(f'reklamy {ad_id} nie ma wśród aktywnych')
    a = poz[0]
    r, e = api.post(ad_id, {'status': 'PAUSED'}, waliduj=False)
    print('PAUZA', ad_id, '→', r or e)
    stan.setdefault('historia', {})[a['klucz']] = {
        'od': a['od'], 'do': datetime.now(timezone.utc).isoformat(),
        'ad_id': ad_id, 'wynik': wyniki_reklamy(ad_id)}
    stan['aktywne'].remove(a)
    api.stan_zapisz(STAN, stan)
    print('wyjęte, wynik zapisany w historii')


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--wgraj', metavar='KLUCZ')
    ap.add_argument('--wstaw', metavar='KLUCZ')
    ap.add_argument('--wyjmij', metavar='AD_ID')
    a = ap.parse_args()
    stan = api.stan_wczytaj(STAN, {'aktywne': [], 'wgrane': {}, 'historia': {}})
    if a.wgraj:
        return wgraj(a.wgraj, stan)
    if a.wstaw:
        return wstaw(a.wstaw, stan)
    if a.wyjmij:
        return wyjmij(a.wyjmij, stan)
    sekcja_aktywne(stan)
    sekcja_material(stan)
    _, _, braki = infrastruktura()
    if braki:
        print('\n=== CZEGO BRAKUJE ===')
        for b in braki:
            print('  ·', b)


if __name__ == '__main__':
    main()
