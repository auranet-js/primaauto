"""Dostęp do materiałów sesji na Dysku Google (folder Prima Auto/sesje)."""
import json, os, urllib.request, urllib.parse

SEKRETY = '/home/host476470/secrets/google'
FOLDER_SESJE = '1m-8F99y79rIyAhb3zTViOvvmZgCCe87l'


def access_token():
    t = json.load(open(os.path.join(SEKRETY, 'tokens.json')))
    cid, cs = t.get('client_id'), t.get('client_secret')
    if not cid:
        c = json.load(open(os.path.join(SEKRETY, 'oauth-desktop-client.json')))['installed']
        cid, cs = c['client_id'], c['client_secret']
    dane = urllib.parse.urlencode({
        'client_id': cid, 'client_secret': cs,
        'refresh_token': t['refresh_token'], 'grant_type': 'refresh_token'}).encode()
    return json.load(urllib.request.urlopen('https://oauth2.googleapis.com/token', dane))['access_token']


def lista(parent, tok):
    """Pliki w folderze — z metadanymi wideo (kadr rozpoznajemy z width/height, nie z nazwy)."""
    out, strona = [], None
    while True:
        p = {'q': f"'{parent}' in parents and trashed=false",
             'fields': 'nextPageToken,files(id,name,mimeType,size,videoMediaMetadata)',
             'pageSize': 1000, 'orderBy': 'name'}
        if strona:
            p['pageToken'] = strona
        req = urllib.request.Request('https://www.googleapis.com/drive/v3/files?' + urllib.parse.urlencode(p),
                                     headers={'Authorization': 'Bearer ' + tok})
        d = json.load(urllib.request.urlopen(req))
        out += d.get('files', [])
        strona = d.get('nextPageToken')
        if not strona:
            return out


def pobierz(file_id, cel, tok):
    req = urllib.request.Request(f'https://www.googleapis.com/drive/v3/files/{file_id}?alt=media',
                                 headers={'Authorization': 'Bearer ' + tok})
    with urllib.request.urlopen(req) as r, open(cel, 'wb') as f:
        while True:
            kawalek = r.read(1 << 20)
            if not kawalek:
                break
            f.write(kawalek)
    return os.path.getsize(cel)


def filmy_sesji(tok, kadr=None):
    """Zwraca [{model, wariant, nazwa, id, kadr, w, h, rozmiar}] ze wszystkich folderów modeli.

    Schodzi do podfolderów — część sesji ma układ <Model>/<Kolor>/ (np. Leopard-5/Czarny).
    Kadr rozpoznajemy z metadanych wideo, nie z nazwy pliku: nazwy są niekonsekwentne.
    """
    wynik = []

    def zejdz(folder_id, model, wariant, glebokosc=0):
        if glebokosc > 3:
            return
        for p in lista(folder_id, tok):
            if 'folder' in p['mimeType']:
                zejdz(p['id'], model, p['name'], glebokosc + 1)
                continue
            if not p['mimeType'].startswith('video'):
                continue
            m = p.get('videoMediaMetadata') or {}
            w, h = m.get('width'), m.get('height')
            k = '9:16' if (w and h and h > w) else ('16:9' if w else '?')
            if kadr and k != kadr:
                continue
            wynik.append({'model': model, 'wariant': wariant, 'nazwa': p['name'], 'id': p['id'],
                          'kadr': k, 'w': w, 'h': h, 'rozmiar': int(p.get('size', 0))})

    for f in lista(FOLDER_SESJE, tok):
        if 'folder' in f['mimeType']:
            zejdz(f['id'], f['name'], None)
    return wynik
