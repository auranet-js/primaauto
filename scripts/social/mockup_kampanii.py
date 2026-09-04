#!/usr/bin/env python3
"""Makieta wszystkiego, co stoi na koncie — kampanie, zestawy, reklamy, treść kreacji.

Czyta stan z Mety (nic nie zapisuje) i składa jedną stronę HTML: co jest w strukturze,
z jakim budżetem i targetem, a przy każdej reklamie miniatura, nagłówek, tekst, CTA
i landing z kodem odpowiedzi. Do przeglądu przed decyzją, które reklamy odpauzować.

    python3 scripts/social/mockup_kampanii.py > /tmp/makieta.html
"""
import html
import json
import re
import os
import sys
import time
import urllib.parse

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import meta_api as api


def pobierz(sciezka, prob=8):
    """GET z ponawianiem. Konto ma kroczący limit wywołań (17/2446079) i przy serii
    żądań odcina — to nie błąd konfiguracji, tylko trzeba odczekać."""
    for i in range(prob):
        d, e = api.get(sciezka)
        if d is not None:
            return d
        if '2446079' not in (e or ''):
            sys.exit(f'{sciezka}: {e}')
        czekaj = 60 * (i + 1)
        print(f'limit API — czekam {czekaj} s ({i + 1}/{prob})', file=sys.stderr)
        time.sleep(czekaj)
    sys.exit('limit API nie puścił')


def kreacja_tresc(kre):
    """Nagłówek, tekst, CTA i landing — wideo, gotowy post albo karuzela."""
    spec = kre.get('object_story_spec') or {}
    ld = spec.get('link_data') or {}
    if ld.get('child_attachments'):
        return {'typ': 'karuzela', 'naglowek': f"karuzela · {len(ld['child_attachments'])} kart",
                'tekst': ld.get('message', ''), 'opis': ld.get('caption', ''),
                'landing': ld.get('link', ''), 'obraz': '',
                'karty': [(k.get('image_hash'), k.get('name', ''), k.get('description', ''),
                           k.get('link', '')) for k in ld['child_attachments']]}
    vd = spec.get('video_data') or {}
    if vd:
        cta = (vd.get('call_to_action') or {}).get('value', {}).get('link', '')
        return {'typ': 'wideo', 'naglowek': vd.get('title', ''), 'tekst': vd.get('message', ''),
                'opis': vd.get('link_description', ''), 'landing': cta,
                'obraz': vd.get('image_url', ''), 'karty': []}
    # Reklama z gotowego posta nie ma pola z linkiem — klik prowadzi tam, gdzie link
    # w treści posta. Wyciągamy go, bo to jedyny sposób sprawdzenia, czy oferta jeszcze żyje.
    tresc = kre.get('body') or ''
    m = re.findall(r'https?://primaauto\.com\.pl/oferta/[^\s<>"\)]+', tresc)
    return {'typ': 'post', 'naglowek': kre.get('title') or '', 'tekst': tresc,
            'opis': kre.get('object_story_id', ''),
            'landing': (m[0].rstrip('/') + '/') if m else '(post bez linku do oferty)',
            'obraz': kre.get('thumbnail_url', ''), 'karty': []}


def main():
    czesci = []
    pola = ('id,name,effective_status,'
            'adset{id,name,status,daily_budget,optimization_goal,targeting},'
            'campaign{id,name,objective,effective_status},'
            'creative{id,object_story_spec,body,title,thumbnail_url,object_story_id}')
    dane = pobierz(f'{api.ACT}/ads?fields={pola}&limit=100')

    hasze = {k[0] for a in dane.get('data', [])
             for k in kreacja_tresc(a.get('creative', {})).get('karty', []) if k[0]}
    adresy = {}
    if hasze:
        # separators bez spacji — spacja w URL wywala urllib na kontrolnym znaku
        lista = urllib.parse.quote(json.dumps(sorted(hasze), separators=(',', ':')))
        obrazy = pobierz(f'{api.ACT}/adimages?fields=hash,url&hashes={lista}&limit=200')
        adresy = {o['hash']: o['url'] for o in obrazy.get('data', [])}

    drzewo = {}
    for a in dane.get('data', []):
        k, z = a['campaign'], a['adset']
        drzewo.setdefault(k['id'], (k, {}))[1].setdefault(z['id'], (z, []))[1].append(a)

    for kid, (k, zestawy) in drzewo.items():
        blok = [f"<section><h2>{html.escape(k['name'])}</h2>"
                f"<p class=meta>{k['objective']} · <b>{k['effective_status']}</b> · {kid}</p>"]
        for zid, (z, reklamy) in zestawy.items():
            t = z.get('targeting', {})
            wykl = ', '.join(x['name'] for x in t.get('excluded_custom_audiences', [])) or '—'
            blok.append(
                f"<div class=zestaw><h3>{html.escape(z['name'])}</h3><p class=meta>"
                f"{int(z['daily_budget'])/100:.0f} zł/dzień · {z['optimization_goal']} · "
                f"<b>{z['status']}</b> · {len(reklamy)} reklam<br>"
                f"{', '.join(t.get('geo_locations', {}).get('countries', []))} · "
                f"wiek {t.get('age_min')}–{t.get('age_max')} · "
                f"{', '.join(t.get('publisher_platforms', []))}<br>"
                f"wyklucza: {html.escape(wykl)}</p><div class=siatka>")
            for a in reklamy:
                kre = kreacja_tresc(a.get('creative', {}))
                kod = api.landing_zyje(kre['landing']) if kre['landing'].startswith('http') else ''
                klasa = 'zle' if kod and kod != 200 else 'ok'
                if kre.get('karty'):
                    obraz = ''.join(
                        f"<span><img src='{html.escape(adresy.get(h, ''))}' alt='' loading=lazy>"
                        f"<b>{html.escape(n)}</b><i>{html.escape(o)}</i></span>"
                        for h, n, o, _ in kre['karty'])
                    obraz = f"<div class=karty>{obraz}</div>"
                else:
                    obraz = (f"<img src='{html.escape(kre['obraz'])}' alt='' loading=lazy>"
                             if kre['obraz'] else "<div class=brak>bez miniatury</div>")
                blok.append(
                    f"<article><div class='kadr {'szeroki' if kre.get('karty') else ''}'>"
                    f"{obraz}</div>"
                    f"<p class=stan>{a['effective_status']}</p>"
                    f"<h4>{html.escape(kre['naglowek'] or a['name'])}</h4>"
                    f"<p class=tresc>{html.escape(kre['tekst'][:300])}</p>"
                    f"<p class='land {klasa}'>{html.escape(str(kre['landing']))}"
                    f"{f' → {kod}' if kod else ''}</p>"
                    f"<p class=meta>{html.escape(a['name'])}<br>{a['id']}"
                    + (f"<br>{kre['opis']}" if kre['typ'] == 'post' else "")
                    + "</p></article>")
            blok.append('</div></div>')
        blok.append('</section>')
        czesci.append('\n'.join(blok))

    grupy = pobierz(f'{api.ACT}/customaudiences?fields=id,name,subtype,retention_days&limit=50')
    wiersze = ''.join(
        f"<tr><td>{html.escape(g['name'])}</td><td>{g['subtype']}</td>"
        f"<td>{g.get('retention_days', '—')}</td><td>{g['id']}</td></tr>"
        for g in grupy.get('data', []))

    # Plik ląduje na auratest jako zwykły statyk — bez jawnego charsetu serwer potrafi
    # oddać go jako ISO-8859-1 i polskie znaki się sypią.
    print(f"""<!doctype html><html lang=pl><head><meta charset=utf-8>
<meta name=viewport content="width=device-width,initial-scale=1">
<title>Makieta kampanii Meta — Prima-Auto</title>
<style>
:root {{ --tlo:#faf9f7; --karta:#fff; --tekst:#1a1a1a; --szary:#6b6b6b; --linia:#e4e1dc;
        --zle:#b3261e; --ok:#1e6b3a; }}
@media (prefers-color-scheme: dark) {{ :root:not([data-theme=light]) {{
  --tlo:#16151a; --karta:#201f26; --tekst:#ece9e6; --szary:#9c9791; --linia:#332f3a;
  --zle:#f2b8b5; --ok:#7fd39b; }} }}
body {{ background:var(--tlo); color:var(--tekst); font:15px/1.55 -apple-system,system-ui,sans-serif;
        margin:0; padding:32px 24px 80px; }}
.wrap {{ max-width:1180px; margin:0 auto; }}
h1 {{ font-size:26px; margin:0 0 4px; letter-spacing:-.01em; }}
h2 {{ font-size:20px; margin:40px 0 2px; }}
h3 {{ font-size:16px; margin:0 0 6px; }}
h4 {{ font-size:14px; margin:10px 0 4px; }}
.meta {{ color:var(--szary); font-size:12.5px; margin:2px 0 0; }}
.zestaw {{ border:1px solid var(--linia); border-radius:12px; padding:16px; margin:14px 0 0;
           background:var(--karta); }}
.siatka {{ display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:14px;
           margin-top:16px; }}
article {{ border:1px solid var(--linia); border-radius:10px; overflow:hidden;
           background:var(--tlo); display:flex; flex-direction:column; }}
.kadr.szeroki {{ aspect-ratio:auto; overflow-x:auto; justify-content:flex-start;
  background:var(--tlo); padding:8px; gap:8px; }}
.karty {{ display:flex; gap:8px; }}
.karty span {{ width:120px; flex:0 0 auto; }}
.karty img {{ width:120px; height:120px; object-fit:cover; border-radius:6px; }}
.karty b {{ display:block; font-size:11px; margin-top:4px; font-weight:600; }}
.karty i {{ display:block; font-size:10.5px; color:var(--szary); font-style:normal; }}
.kadr {{ aspect-ratio:9/16; background:#000; display:flex; align-items:center; justify-content:center; }}
.kadr img {{ width:100%; height:100%; object-fit:cover; }}
.brak {{ color:var(--szary); font-size:12px; }}
article > * {{ padding-inline:12px; }}
.kadr {{ padding:0; }}
.stan {{ font-size:11px; letter-spacing:.06em; color:var(--szary); margin:10px 0 0; }}
.tresc {{ font-size:12.5px; color:var(--szary); margin:0 0 8px; }}
.land {{ font-size:11.5px; word-break:break-all; margin:0 0 8px; }}
.land.ok {{ color:var(--ok); }} .land.zle {{ color:var(--zle); font-weight:600; }}
article .meta {{ padding-bottom:12px; font-size:11px; }}
table {{ border-collapse:collapse; width:100%; margin-top:12px; font-size:13px; }}
td, th {{ border-bottom:1px solid var(--linia); padding:7px 10px; text-align:left; }}
.stopka {{ color:var(--szary); font-size:13px; margin-top:36px; }}
</style></head><body>
<div class=wrap>
<h1>Makieta kampanii Meta — Prima-Auto</h1>
<p class=meta>Stan konta act_1038563008906171 odczytany z API. Wszystko wstrzymane,
zero wyświetleń, zero wydatku.</p>
{''.join(czesci)}
<section><h2>Grupy odbiorców</h2><table>
<tr><th>Nazwa</th><th>Typ</th><th>Okno (dni)</th><th>ID</th></tr>{wiersze}</table></section>
<p class=stopka>Zielone światło dotyczy konkretnych reklam — po decyzji odpauzowujemy
tylko wskazane, reszta zostaje w bibliotece.</p>
</div></body></html>""")


if __name__ == '__main__':
    main()
