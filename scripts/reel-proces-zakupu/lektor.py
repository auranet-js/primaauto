#!/usr/bin/env python3
"""T-265: lektor do filmu procesu zakupu (Gemini TTS).

  lektor.py probka <glos> <wyjscie.wav> "<tekst>"     jedna kwestia (do wyboru głosu)
  lektor.py sceny <glos> <katalog>                     kwestie z lektor.json -> <katalog>/<id>.wav + czasy.json
  lektor.py miks <katalog> <film.mp4> <wyjscie.mp4>     cisza obcięta, tempo dopasowane do okna (max x1.25), -16 LUFS

Tekst i początki kwestii (sekundy w gotowym filmie): lektor.json. Ton: STYL poniżej.
Koszt: ~25 tokenów audio/s, $20/1M (3.1 flash tts) -> cały film < $0.05.
"""
import base64, json, pathlib, subprocess, sys, urllib.request, wave

FF = str(pathlib.Path('~/bin/ffmpeg').expanduser())

KEY = pathlib.Path('~/secrets/google/gemini-api-key.txt').expanduser().read_text().strip()
MODEL = 'gemini-3.1-flash-tts-preview'
TU = pathlib.Path(__file__).resolve().parent
STYL = ('Przeczytaj po polsku ciepłym, pewnym głosem doświadczonego doradcy, któremu można zaufać. '
        'Żywe, płynne tempo jak w krótkim filmie w mediach społecznościowych, wyraźnie, bez reklamowej przesady:\n\n')


def tts(glos, tekst, out):
    body = {'contents': [{'parts': [{'text': STYL + tekst}]}],
            'generationConfig': {'responseModalities': ['AUDIO'],
                                 'speechConfig': {'voiceConfig': {'prebuiltVoiceConfig': {'voiceName': glos}}}}}
    req = urllib.request.Request(f'https://generativelanguage.googleapis.com/v1beta/models/{MODEL}:generateContent',
                                 json.dumps(body).encode(), {'Content-Type': 'application/json', 'x-goog-api-key': KEY})
    with urllib.request.urlopen(req, timeout=180) as r:
        d = json.load(r)
    pcm = base64.b64decode(d['candidates'][0]['content']['parts'][0]['inlineData']['data'])
    with wave.open(str(out), 'wb') as w:
        w.setnchannels(1); w.setsampwidth(2); w.setframerate(24000); w.writeframes(pcm)
    return len(pcm) / 48000


def miks(kat, film, out):
    czasy = json.loads((kat / 'czasy.json').read_text())
    wej, filtr = ['-i', film], []
    for i, (k, c) in enumerate(czasy.items(), 1):
        # obcięcie ciszy z obu stron, pomiar, tempo tylko gdy kwestia nie mieści się w oknie
        cz = kat / f'{k}-cz.wav'
        subprocess.run([FF, '-loglevel', 'error', '-y', '-threads', '1', '-i', kat / f'{k}.wav', '-af',
                        'silenceremove=start_periods=1:start_threshold=-45dB,areverse,silenceremove=start_periods=1:start_threshold=-45dB,areverse', cz], check=True)
        with wave.open(str(cz)) as w:
            dl = w.getnframes() / w.getframerate()
        t = max(1.0, dl / c['okno'])
        print(f'{k:8} {dl:5.2f} s  okno {c["okno"]}  tempo x{t:.2f}{"  ZA SZYBKO — skróć tekst" if t > 1.25 else ""}')
        wej += ['-i', str(cz)]
        filtr.append(f'[{i}]atempo={min(t, 1.25):.3f},adelay={int(c["start"] * 1000)}:all=1[a{i}]')
    n = len(czasy)
    filtr.append(''.join(f'[a{i}]' for i in range(1, n + 1)) + f'amix=inputs={n}:normalize=0,loudnorm=I=-16:TP=-1.5,aresample=48000[a]')
    subprocess.run([FF, '-loglevel', 'error', '-y', '-threads', '1', '-filter_threads', '1', *wej, '-filter_complex', ';'.join(filtr),
                    '-map', '0:v', '-map', '[a]', '-c:v', 'copy', '-c:a', 'aac', '-b:a', '160k', '-shortest', '-movflags', '+faststart', out], check=True)


if __name__ == '__main__':
    a = sys.argv[1:]
    if a[0] == 'probka':
        print(f'{tts(a[1], a[3], a[2]):.2f} s')
    elif a[0] == 'sceny':
        kat = pathlib.Path(a[2]); kat.mkdir(parents=True, exist_ok=True)
        czasy = {}
        for k in json.loads((TU / 'lektor.json').read_text())['kwestie']:
            dl = tts(a[1], k['tekst'], kat / f"{k['id']}.wav")
            czasy[k['id']] = {'start': k['start'], 'dl': round(dl, 2), 'okno': k['okno']}
            print(f"{k['id']:8} {dl:5.2f} s / okno {k['okno']} s{'  ZA DŁUGO' if dl > k['okno'] else ''}")
        (kat / 'czasy.json').write_text(json.dumps(czasy, indent=1))
    elif a[0] == 'miks':
        miks(pathlib.Path(a[1]), a[2], a[3])
    else:
        sys.exit(__doc__)
