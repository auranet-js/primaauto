#!/usr/bin/env python3
"""Makieta P3 (16.09): porównywarka na telefonie — trzy warianty obok siebie na ramkach 390 px.
Desktop (P2, gen-porownywarka-cpu.py) ma 4 kolumny: parametry + 3 auta; na telefonie się nie mieści.
  A — ta sama tabela, przewijana w bok, kolumna parametrów przyklejona
  B — dwa auta na ekranie, przełącznik wybiera, które dwa z trzech
  C — karty parametrów: nazwa na całą szerokość, wartości aut pod sobą
Dane i reguły (sekcje karty oferty, słownik, rodzaj zasięgu) — te same co w P2.
Dane: scratchpad porownania-dane.json. Wynik: drop na auratest."""
import html, json, re, runpy, sys, pathlib

SRC = sys.argv[1] if len(sys.argv) > 1 else 'porownania-dane.json'
OUT = pathlib.Path('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp/primaauto-porownywarka-makieta-mobile-2026-09-16.html')
# Reużycie funkcji z makiety desktopowej (przy okazji odświeża plik P2 — te same dane).
sys.argv = [sys.argv[0], SRC]
P2 = runpy.run_path(str(pathlib.Path(__file__).with_name('gen-porownywarka-cpu.py')), run_name='p2')
D, e = P2['D'], html.escape
wiki, liczba, zl, num, nazwa, ofert_pl, rodzaj_zasiegu = (P2[k] for k in ('wiki', 'liczba', 'zl', 'num', 'nazwa', 'ofert_pl', 'rodzaj_zasiegu'))
KOL = ['#1B2A4A', '#C92A2B', '#E8AC07']


def krotka(ws, w):
    return w['wersja'] if len({x['model'] for x in ws}) == 1 else w['model']


def model_danych(ws):
    """Sekcje jak na karcie oferty → [(tytuł, [(label_html, [wartość|None], różna, typ, klasy)])]."""
    moc = lambda w: num(w['specs'].get('power_km'))
    acc = lambda w: num(w['specs'].get('accel_s'))
    kz, ez = rodzaj_zasiegu(ws)
    podsum = []
    podsum.append(('Cena', [(f'<a class="buy" href="{e(w["hub"])}">od {zl(w["oferty"][0]["cena"])}</a><small>{len(w["oferty"])} {ofert_pl(len(w["oferty"]))}</small>' if w['oferty'] else 'brak aut') for w in ws], False, 'html', None))
    for lab, fn, fm, wiecej in [('Moc układu', moc, lambda v: liczba(v) + ' KM', True), ('0-100 km/h', acc, lambda v: liczba(v, 1) + ' s', False),
                                (ez or 'Zasięg', (lambda w: num(w['specs'].get(kz))) if kz else (lambda w: None), lambda v: liczba(v) + ' km', True)]:
        vals = [fn(w) for w in ws]
        if any(v is None for v in vals):
            continue
        best = max(vals) if wiecej else min(vals)
        cells, klasy = [], []
        for v in vals:
            d = (v - best) / best * 100 if wiecej else (best - v) / best * 100
            cells.append(f'<b class="big">{fm(v)}</b><small>({liczba(d, 1)}%)</small>')
            klasy.append('best' if v == best else 'gorsza')
        podsum.append((lab, cells, True, 'html', klasy))
    sekcje = [('Podsumowanie', [(wiki(l), c, r, t, k) for l, c, r, t, k in podsum])]
    kolejnosc = []
    for zr, pref in (('karta_tech', 't'), ('karta_wyp', 'w')):
        for w in ws:
            for k, s in w[zr].items():
                if (zr, k) not in [x[:2] for x in kolejnosc]:
                    kolejnosc.append((zr, k, s['title']))
    for zr, k, title in kolejnosc:
        etyk = []
        for w in ws:
            for r in w[zr].get(k, {}).get('rows', []):
                if zr == 'karta_wyp' and re.fullmatch(r'\d+(\.\d+)?', str(r['value'])):
                    continue
                if r['label'] not in etyk:
                    etyk.append(r['label'])
        if k == 'podstawowe':
            etyk = [x for x in etyk if x not in ('Marka', 'Model', 'Kolor nadwozia', 'Kolor wnętrza')]
        wiersze = []
        for lab in etyk:
            rs = [next((r for r in w[zr].get(k, {}).get('rows', []) if r['label'] == lab), None) for w in ws]
            surowe = [r['value'] if r else None for r in rs]
            cells = []
            for r in rs:
                if r is None:
                    cells.append('<i class="nie">—</i>')
                elif zr == 'karta_wyp' and r.get('std') and r['value'] == 'Tak':
                    cells.append('<i class="tak">✓</i>')
                else:
                    cells.append(e(str(r['value'])))
            wiersze.append((wiki(lab), cells, len(set(surowe)) > 1, 'html', None))
        sekcje.append((title, wiersze))
    return sekcje


def naglowek_strony(ws, wariant):
    h1 = ' vs '.join(nazwa(w) for w in ws)
    intro = P2['wstep'](ws)
    razem = sum(len(r) for _, r in model_danych(ws)[1:])
    rozne = sum(1 for _, rr in model_danych(ws)[1:] for x in rr if x[2])
    return f'''<div class="ph-hdr"><img src="https://primaauto.com.pl/wp-content/uploads/2026/03/primaauto-logo-szerokie.png" alt=""><span class="vs">VS {len(ws)}</span><span class="burger">☰</span></div>
<div class="pad"><p class="crumbs">Prima-Auto › Porównywarka</p><h1>{e(h1)}</h1>
<details class="intro"><summary>{e(re.sub('<[^>]+>', '', intro))[:140]}… <u>więcej</u></summary><p>{intro}</p></details>
<label class="tylko"><input type="checkbox" data-w="{wariant}"> Tylko różnice <em>{rozne} z {razem}</em></label></div>'''


def wariant_a(ws):
    sek = model_danych(ws)
    th = ''.join(f'<th class="k" style="--k:{KOL[i]}"><b>{e(krotka(ws, w))}</b></th>' for i, w in enumerate(ws))
    body = ''
    for i, (title, rows) in enumerate(sek):
        body += f'<tbody class="grp{" open" if i < 2 else ""}"><tr class="sek"><th colspan="{len(ws) + 1}"><span class="tg"></span>{e(title)}<em>{sum(1 for r in rows if r[2])} różnic</em></th></tr>'
        for lab, cells, rozna, _, klasy in rows:
            body += f'<tr class="{"d" if rozna else "s"}"><th class="p">{lab}</th>' + ''.join(f'<td class="{(klasy or [""] * len(cells))[j]}">{c}</td>' for j, c in enumerate(cells)) + '</tr>'
        body += '</tbody>'
    return f'''<p class="hint pad">Przesuń tabelę w bok →</p>
<div class="scrollx"><table class="mA"><thead><tr><th class="p">Parametr</th>{th}</tr></thead>{body}</table></div>'''


def wariant_b(ws):
    sek = model_danych(ws)
    chips = ''.join(f'<button class="chip{" on" if i < 2 else ""}" data-c="{i}" style="--k:{KOL[i]}">{e(krotka(ws, w))}</button>' for i, w in enumerate(ws))
    th = ''.join(f'<th class="k c{i}" style="--k:{KOL[i]}"><b>{e(krotka(ws, w))}</b></th>' for i, w in enumerate(ws))
    body = ''
    for i, (title, rows) in enumerate(sek):
        body += f'<tbody class="grp{" open" if i < 2 else ""}"><tr class="sek"><th colspan="4"><span class="tg"></span>{e(title)}<em>{sum(1 for r in rows if r[2])} różnic</em></th></tr>'
        for lab, cells, rozna, _, klasy in rows:
            body += f'<tr class="{"d" if rozna else "s"}"><th class="p">{lab}</th>' + ''.join(f'<td class="c{j} {(klasy or [""] * len(cells))[j]}">{c}</td>' for j, c in enumerate(cells)) + '</tr>'
        body += '</tbody>'
    prz = f'<div class="chips pad"><span>Na ekranie:</span>{chips}</div>' if len(ws) > 2 else ''
    return f'''{prz}<table class="mB" data-ukryj="2"><colgroup><col style="width:34%">{''.join(f'<col class="c{i}" style="width:33%">' for i in range(len(ws)))}</colgroup><thead><tr><th class="p">Parametr</th>{th}</tr></thead>{body}</table>'''


def wariant_c(ws):
    sek = model_danych(ws)
    legenda = ''.join(f'<span style="--k:{KOL[i]}"><i></i>{e(krotka(ws, w))}</span>' for i, w in enumerate(ws))
    out = f'<div class="legenda pad">{legenda}</div>'
    for i, (title, rows) in enumerate(sek):
        karty = ''
        for lab, cells, rozna, _, klasy in rows:
            wart = ''.join(f'<div class="v {(klasy or [""] * len(cells))[j]}" style="--k:{KOL[j]}"><i></i><span class="n">{e(krotka(ws, ws[j]))}</span><span class="w">{c}</span></div>' for j, c in enumerate(cells))
            karty += f'<div class="karta {"d" if rozna else "s"}"><p class="lab">{lab}</p>{wart}</div>'
        out += f'<details class="sekC"{" open" if i < 2 else ""}><summary>{e(title)}<em>{sum(1 for r in rows if r[2])} różnic</em></summary>{karty}</details>'
    return out


def wykresy(ws):
    kz, ez = rodzaj_zasiegu(ws)
    defs = [('Moc układu (KM)', lambda w: num(w['specs'].get('power_km')), 0), ('0-100 km/h (s)', lambda w: num(w['specs'].get('accel_s')), 1)]
    if kz:
        defs.append((ez + ' (km)', lambda w: num(w['specs'].get(kz)), 0))
    defs.append(('Cena od (zł)', lambda w: float(w['oferty'][0]['cena']) if w['oferty'] else None, 0))
    out = ''
    for t, fn, dec in defs:
        vals = [fn(w) for w in ws]
        if any(v is None for v in vals):
            continue
        mx = max(vals)
        out += f'<div class="ch"><p>{t}</p>' + ''.join(f'<div class="chr"><span>{e(krotka(ws, w))}</span><i style="width:{60 * v / mx:.0f}%;background:{KOL[j]}"></i><b>{liczba(v, dec)}</b></div>' for j, (w, v) in enumerate(zip(ws, vals))) + '</div>'
    return f'<div class="pad wyk"><h2>Wykresy</h2>{out}</div>'


def ramka(slug, ws, lit, tytul, opis, tresc):
    return f'''<div class="kol"><p class="kol__t"><b>{lit}</b> {tytul}</p><p class="kol__o">{opis}</p>
<div class="phone" data-s="{slug}" data-w="{lit}">{naglowek_strony(ws, lit)}{tresc}{wykresy(ws)}</div></div>'''


sekcje = ''
for i, (slug, ws) in enumerate(D.items()):
    sekcje += f'''<section class="por{" on" if i == 0 else ""}" id="{slug}"><div class="kols">
{ramka(slug, ws, "A", "Przewijana tabela", "Ta sama tabela co na desktopie. Kolumna parametrów stoi, auta przewijają się w bok. Przy 3 autach trzecie jest poza ekranem.", wariant_a(ws))}
{ramka(slug, ws, "B", "Dwa auta + przełącznik", "Na ekranie zawsze 2 kolumny aut, bez przewijania w bok. Przy 3 autach przełącznik u góry wybiera, które dwa widać.", wariant_b(ws))}
{ramka(slug, ws, "C", "Karty parametrów", "Nazwa parametru na całą szerokość, pod nią wartości każdego auta z kolorowym znacznikiem. Mieści 3 auta bez przewijania w bok, ale strona jest dłuższa.", wariant_c(ws))}
</div></section>'''

tabs = ''.join(f'<button data-p="{s}"{" class=on" if i == 0 else ""}>{i + 1}. {e(" vs ".join(w["model"] if len({x["model"] for x in ws}) > 1 else w["wersja"].split()[0] for w in ws))}</button>' for i, (s, ws) in enumerate(D.items()))

page = f'''<!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Porównywarka — makieta P3 telefon</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{{--pri:#1B2A4A;--acc:#C92A2B;--sec:#5C6B7F;--bg:#F5F6F8;--bd:#E5E7EB;--lab:#374151;--row:#FAFBFC;--ok:#2F855A;--bad:#C53030;--diff:#FFF8E1}}
*{{box-sizing:border-box}}body{{margin:0;font:14px/1.45 Inter,system-ui,sans-serif;color:var(--pri);background:#D9DEE4}}
.meta{{background:#111827;color:#E5E7EB;padding:8px 20px;font-size:13px;position:sticky;top:0;z-index:50}}.meta__r{{display:flex;gap:6px;flex-wrap:wrap;align-items:center}}
.meta button{{background:#374151;color:#fff;border:0;border-radius:4px;padding:5px 9px;font:inherit;cursor:pointer}}.meta button.on{{background:var(--acc)}}
.por{{display:none}}.por.on{{display:block}}
.kols{{display:flex;gap:28px;justify-content:center;padding:20px;flex-wrap:wrap}}.kol{{width:390px}}
.kol__t{{margin:0;font-size:16px}}.kol__t b{{background:var(--pri);color:#fff;border-radius:4px;padding:0 7px;margin-right:4px}}.kol__o{{margin:4px 0 10px;font-size:13px;color:#374151;min-height:57px}}
.phone{{width:390px;height:780px;overflow-y:auto;overflow-x:hidden;background:var(--bg);border-radius:28px;border:10px solid #111;position:relative;font-size:14px}}
.ph-hdr{{background:#9B0000;height:56px;display:flex;align-items:center;gap:10px;padding:0 14px;position:sticky;top:0;z-index:20}}.ph-hdr img{{height:28px}}.ph-hdr .vs{{margin-left:auto;background:#fff;color:#9B0000;border-radius:12px;padding:0 8px;font-weight:700;font-size:12px}}.ph-hdr .burger{{color:#fff;font-size:22px}}
.pad{{padding:0 14px}}.crumbs{{font-size:12px;color:var(--sec);margin:10px 0 2px}}
h1{{font-size:21px;line-height:1.25;margin:0 0 8px}}h2{{font-size:18px;margin:22px 0 8px}}
details.intro summary{{font-size:13px;color:#374151;list-style:none;cursor:pointer}}details.intro summary u{{color:var(--acc)}}details.intro[open] summary{{display:none}}details.intro p{{margin:0;font-size:13px}}
.tylko{{display:flex;align-items:center;gap:6px;margin:10px 0;font-weight:600;font-size:14px;min-height:44px}}.tylko input{{width:20px;height:20px}}.tylko em{{font-style:normal;font-weight:400;color:var(--sec);font-size:12px}}
.phone.tylko-r tr.s,.phone.tylko-r .karta.s{{display:none}}
a.wk{{color:inherit;text-decoration:underline dotted #9AA8B6;text-underline-offset:3px}}
.buy{{display:inline-block;background:var(--acc);color:#fff;font-weight:700;font-size:12px;padding:3px 7px;border-radius:4px;text-decoration:none;white-space:nowrap}}td small,.w small{{display:block;color:var(--sec);font-size:11px}}
.big{{font-size:16px;display:block}}.best .big{{color:var(--ok)}}.gorsza small{{color:var(--bad)}}
.tak{{color:var(--ok);font-style:normal;font-weight:700;font-size:16px}}.nie{{color:#A0AEC0;font-style:normal}}
/* A */
.hint{{font-size:12px;color:var(--sec);margin:0 0 4px}}.scrollx{{overflow-x:auto;background:#fff}}
table.mA{{border-collapse:separate;border-spacing:0;width:max-content;min-width:100%}}
.mA th,.mA td{{padding:8px 9px;font-size:13px;border-bottom:1px solid var(--bd);vertical-align:top;text-align:left;background:#fff}}
.mA th.p{{position:sticky;left:0;z-index:2;width:118px;min-width:118px;max-width:118px;background:#F8FAFC;color:var(--lab);font-weight:600;border-right:1px solid var(--bd)}}
.mA td{{width:150px;min-width:150px}}.mA thead th{{position:sticky;top:0;background:var(--pri)!important;color:#fff;z-index:3}}.mA thead th.p{{z-index:4}}
.mA th.k{{box-shadow:inset 0 -3px var(--k)}}.mA thead th.p{{color:#fff}}.mA tr.sek th{{position:sticky;left:0;background:#EEF2F6;font-size:13px;text-align:left}}
.mA tr.d td{{background:var(--diff);font-weight:600}}
/* B */
table.mB{{border-collapse:collapse;width:100%;table-layout:fixed;background:#fff}}
.mB th,.mB td{{padding:8px 8px;font-size:13px;border-bottom:1px solid var(--bd);vertical-align:top;text-align:left;word-wrap:break-word}}
.mB th.p{{width:34%;background:#F8FAFC;color:var(--lab);font-weight:600}}.mB td,.mB th.k{{width:33%}}.mB thead th{{background:var(--pri);color:#fff}}.mB .buy{{white-space:normal;font-size:11px;padding:2px 5px}}.mB th.k{{box-shadow:inset 0 -3px var(--k)}}
.mB tr.d td{{background:var(--diff);font-weight:600}}.mB[data-ukryj="0"] .c0,.mB[data-ukryj="1"] .c1,.mB[data-ukryj="2"] .c2{{display:none}}.mB[data-ukryj="0"] col.c0,.mB[data-ukryj="1"] col.c1,.mB[data-ukryj="2"] col.c2{{visibility:collapse}}
.chips{{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:0 0 8px;font-size:12px;color:var(--sec)}}
.chip{{border:2px solid var(--k);background:#fff;color:var(--pri);border-radius:18px;padding:6px 10px;font:600 12px Inter;min-height:36px;cursor:pointer}}.chip.on{{background:var(--k);color:#fff}}
/* wspólne sekcje */
tr.sek th{{background:#EEF2F6;color:var(--pri);font-weight:700;cursor:pointer;padding:11px 9px}}tr.sek em{{font-style:normal;font-weight:400;color:var(--sec);font-size:11px;margin-left:6px}}
tr.sek .tg:before{{content:"▸ "}}tbody.open tr.sek .tg:before{{content:"▾ "}}tbody:not(.open) tr:not(.sek){{display:none}}
/* C */
.legenda{{display:flex;gap:10px;flex-wrap:wrap;font-size:12px;margin:0 0 8px}}.legenda span{{display:flex;align-items:center;gap:4px}}.legenda i,.v i{{width:10px;height:10px;border-radius:50%;background:var(--k);display:inline-block;flex:none}}
details.sekC{{background:#fff;margin:0 0 8px}}details.sekC>summary{{background:#EEF2F6;padding:12px 14px;font-weight:700;cursor:pointer;min-height:44px}}details.sekC summary em{{font-style:normal;font-weight:400;color:var(--sec);font-size:11px;margin-left:6px}}
.karta{{padding:8px 14px;border-bottom:1px solid var(--bd)}}.karta.d{{background:var(--diff)}}.karta .lab{{margin:0 0 4px;font-weight:600;color:var(--lab);font-size:13px}}
.v{{display:flex;align-items:baseline;gap:6px;font-size:13px;padding:2px 0}}.v .n{{color:var(--sec);width:118px;flex:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px}}.v .w{{flex:1}}.karta.d .v .w{{font-weight:600}}.v .big{{display:inline;font-size:15px}}.v small{{display:inline;margin-left:4px}}
/* wykresy */
.wyk{{padding-bottom:30px}}.ch{{background:#fff;border-radius:8px;padding:10px 12px;margin:0 0 8px}}.ch p{{margin:0 0 6px;font-weight:600;font-size:13px}}
.chr{{display:flex;align-items:center;gap:6px;font-size:12px;padding:3px 0}}.chr span{{width:90px;flex:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--sec)}}.chr i{{height:9px;border-radius:5px;display:block}}.chr b{{margin-left:auto}}
</style></head><body>
<div class="meta"><div class="meta__r"><strong>Makieta P3 · telefon 390 px · 3 warianty</strong>{tabs}</div></div>
{sekcje}
<script>
document.addEventListener('click',ev=>{{
  const p=ev.target.closest('.meta [data-p]');if(p){{document.querySelectorAll('.por').forEach(x=>x.classList.toggle('on',x.id===p.dataset.p));document.querySelectorAll('.meta [data-p]').forEach(b=>b.classList.toggle('on',b===p));return}}
  const sek=ev.target.closest('tr.sek');if(sek&&!ev.target.closest('a')){{sek.parentElement.classList.toggle('open');return}}
  const chip=ev.target.closest('.chip');if(chip){{
    const ph=chip.closest('.phone'),t=ph.querySelector('.mB'),chips=[...ph.querySelectorAll('.chip')];
    if(chip.classList.contains('on'))return;
    // włączone zostaje ostatnio wybrane + kliknięte; ukrywamy trzecie
    const on=chips.filter(c=>c.classList.contains('on'));const zdejmij=on[0];zdejmij.classList.remove('on');chip.classList.add('on');
    t.dataset.ukryj=zdejmij.dataset.c;
    chips.splice(chips.indexOf(chip),1);
  }}
}});
document.querySelectorAll('.tylko input').forEach(c=>c.addEventListener('change',()=>c.closest('.phone').classList.toggle('tylko-r',c.checked)));
</script></body></html>'''
OUT.write_text(page)
print(OUT, len(page))
