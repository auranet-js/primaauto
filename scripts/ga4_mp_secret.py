#!/usr/bin/env python3
"""
GA4 Measurement Protocol — utworzenie/odczyt sekretu dla streamu Prima-Auto.

Sekret jest potrzebny, żeby serwer mógł wysyłać zdarzenia do GA4 (np. `purchase`
przy opłaconym depozycie). Bez niego zdarzenia server-side nie mają jak trafić
do właściwości.

Użycie:
  python3 scripts/ga4_mp_secret.py list
  python3 scripts/ga4_mp_secret.py create
"""
import json
import pathlib
import sys
import urllib.request

SECRETS = pathlib.Path.home() / 'secrets' / 'google'
TOKENS = SECRETS / 'tokens.json'
CLIENT = SECRETS / 'oauth-desktop-client.json'
OUT = SECRETS / 'ga4-mp-secret.json'

PROPERTY = '534017542'          # Prima-Auto.pl
STREAM_MEASUREMENT_ID = 'G-F1NCC3D2HZ'


def access_token() -> str:
    tok = json.loads(TOKENS.read_text())
    cli = json.loads(CLIENT.read_text())
    cli = cli.get('installed') or cli.get('web') or cli

    data = urllib.parse.urlencode({
        'client_id': cli['client_id'],
        'client_secret': cli['client_secret'],
        'refresh_token': tok['refresh_token'],
        'grant_type': 'refresh_token',
    }).encode()

    req = urllib.request.Request('https://oauth2.googleapis.com/token', data=data)
    with urllib.request.urlopen(req, timeout=30) as r:
        return json.load(r)['access_token']


def api(path: str, token: str, payload=None):
    url = f'https://analyticsadmin.googleapis.com/v1beta/{path}'
    body = json.dumps(payload).encode() if payload is not None else None
    req = urllib.request.Request(url, data=body, method='POST' if payload is not None else 'GET')
    req.add_header('Authorization', f'Bearer {token}')
    req.add_header('Content-Type', 'application/json')
    with urllib.request.urlopen(req, timeout=30) as r:
        return json.load(r)


def find_stream(token: str) -> str:
    streams = api(f'properties/{PROPERTY}/dataStreams', token).get('dataStreams', [])
    for s in streams:
        web = s.get('webStreamData', {})
        if web.get('measurementId') == STREAM_MEASUREMENT_ID:
            print(f"stream: {s['name']} ({web.get('defaultUri', '?')})")
            return s['name']
    raise SystemExit(f'nie znaleziono streamu {STREAM_MEASUREMENT_ID}')


def main() -> int:
    cmd = sys.argv[1] if len(sys.argv) > 1 else 'list'
    token = access_token()
    stream = find_stream(token)

    existing = api(f'{stream}/measurementProtocolSecrets', token).get('measurementProtocolSecrets', [])

    if cmd == 'list':
        if not existing:
            print('brak sekretów — uruchom: create')
        for s in existing:
            print(f"  {s.get('displayName')}  {s['name']}")
        return 0

    if cmd != 'create':
        print(__doc__)
        return 1

    for s in existing:
        if s.get('displayName') == 'PrimaAuto server-side':
            print('sekret już istnieje — zapisuję lokalnie')
            OUT.write_text(json.dumps({
                'measurement_id': STREAM_MEASUREMENT_ID,
                'api_secret': s['secretValue'],
                'property': PROPERTY,
            }, indent=1))
            OUT.chmod(0o600)
            print(f'zapisano: {OUT}')
            return 0

    created = api(f'{stream}/measurementProtocolSecrets', token, {'displayName': 'PrimaAuto server-side'})

    OUT.write_text(json.dumps({
        'measurement_id': STREAM_MEASUREMENT_ID,
        'api_secret': created['secretValue'],
        'property': PROPERTY,
    }, indent=1))
    OUT.chmod(0o600)

    print(f"utworzono sekret „{created.get('displayName')}”")
    print(f'zapisano: {OUT}')
    return 0


if __name__ == '__main__':
    import urllib.parse
    raise SystemExit(main())
