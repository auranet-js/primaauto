#!/usr/bin/env python3
"""T-265: końcówka filmu — klip AI „Twoje auto czeka”.

  koncowka.py kadr  <zdjecie_auta.jpg> <wyjscie.png> [--wariant N]   pierwszy kadr (Gemini 2.5 flash image, 9:16)
  koncowka.py wideo <kadr.png> <wyjscie.mp4> [--jakosc 720p|1080p]    animacja (Veo 3.1 Fast, 9:16, 6 s)

Prompty: koncowka-prompt.md (ten katalog) — sekcje ```kadr``` i ```wideo```.
Koszt (cennik 10.2026): kadr ~$0.04, wideo Fast 720p $0.10/s, 1080p $0.12/s.
Log prób: koncowka-proby.log obok wyjścia.
"""
import base64, json, pathlib, re, sys, time, urllib.request

KEY = pathlib.Path('~/secrets/google/gemini-api-key.txt').expanduser().read_text().strip()
API = 'https://generativelanguage.googleapis.com/v1beta'
TU = pathlib.Path(__file__).resolve().parent


def prompt(nazwa):
    md = (TU / 'koncowka-prompt.md').read_text()
    m = re.search(r'```' + nazwa + r'\n(.*?)```', md, re.S)
    return m.group(1).strip()


def post(url, body, timeout=180):
    req = urllib.request.Request(url, json.dumps(body).encode(), {'Content-Type': 'application/json', 'x-goog-api-key': KEY})
    with urllib.request.urlopen(req, timeout=timeout) as r:
        return json.load(r)


def get(url, timeout=60):
    req = urllib.request.Request(url, headers={'x-goog-api-key': KEY})
    with urllib.request.urlopen(req, timeout=timeout) as r:
        return r.read()


def log(out, msg):
    with open(pathlib.Path(out).parent / 'koncowka-proby.log', 'a') as f:
        f.write(time.strftime('%F %T ') + msg + '\n')
    print(msg)


def kadr(foto, out, wariant=''):
    p = prompt('kadr') + (f'\n\nVariation seed: {wariant}' if wariant else '')
    mime = {'png': 'image/png', 'webp': 'image/webp'}.get(foto.rsplit('.', 1)[-1], 'image/jpeg')
    r = post(f'{API}/models/gemini-2.5-flash-image:generateContent', {
        'contents': [{'parts': [{'inline_data': {'mime_type': mime, 'data': base64.b64encode(open(foto, 'rb').read()).decode()}}, {'text': p}]}],
        'generationConfig': {'responseModalities': ['TEXT', 'IMAGE'], 'imageConfig': {'aspectRatio': '9:16'}}})
    for part in r['candidates'][0]['content']['parts']:
        if 'inlineData' in part:
            open(out, 'wb').write(base64.b64decode(part['inlineData']['data']))
            log(out, f'kadr {out} {part["inlineData"]["mimeType"]} ~$0.04')
            return
    sys.exit('brak obrazu: ' + json.dumps(r)[:500])


def wideo(img, out, jakosc='720p'):
    mime = 'image/png' if img.endswith('.png') else 'image/jpeg'
    op = post(f'{API}/models/veo-3.1-fast-generate-preview:predictLongRunning', {
        'instances': [{'prompt': prompt('wideo'), 'image': {'bytesBase64Encoded': base64.b64encode(open(img, 'rb').read()).decode(), 'mimeType': mime}}],
        'parameters': {'aspectRatio': '9:16', 'durationSeconds': 6, 'resolution': jakosc, 'personGeneration': 'allow_adult'}})
    name = op['name']
    print('operacja', name)
    for _ in range(90):
        time.sleep(10)
        op = json.loads(get(f'{API}/{name}'))
        if op.get('done'):
            break
    if 'error' in op or not op.get('done'):
        sys.exit('błąd: ' + json.dumps(op)[:800])
    resp = op['response']['generateVideoResponse']
    smp = resp.get('generatedSamples') or []
    if not smp:
        sys.exit('brak wideo (filtr?): ' + json.dumps(resp)[:800])
    open(out, 'wb').write(get(smp[0]['video']['uri'] + ('&' if '?' in smp[0]['video']['uri'] else '?') + 'alt=media', 300))
    cena = {'720p': .10, '1080p': .12}[jakosc] * 6
    log(out, f'wideo {out} {jakosc} 6s ~${cena:.2f}')


if __name__ == '__main__':
    a = sys.argv[1:]
    opt = dict(zip(a[3::2], a[4::2]))
    if a[0] == 'kadr':
        kadr(a[1], a[2], opt.get('--wariant', ''))
    elif a[0] == 'wideo':
        wideo(a[1], a[2], opt.get('--jakosc', '720p'))
    else:
        sys.exit(__doc__)
