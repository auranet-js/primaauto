#!/usr/bin/env python3
"""
Makieta wariantów arkusza filtra na telefonie (T-251, 2026-09-03).

Pytanie Janka: „wybrałem kombi i SUV — i co teraz z tym oknem?". Dziś arkusz na telefonie nie ma
ani nagłówka, ani przycisku; wychodzi się tapem w pasek zasłony nad arkuszem (78vh zajmuje lista),
Escape'em (klawiatury nie ma) albo gestem wstecz na Androidzie, który wychodzi z CAŁEJ strony,
bo otwarcie arkusza nie dodaje wpisu do historii.

Makieta jest KLIKALNA — do otwarcia na telefonie. Cztery ekrany: stan dzisiejszy jako punkt
odniesienia + trzy warianty. Liczby są prawdziwe (tabela `wp7j_asiaauto_specs`, publish, 03.09).

Wyjście: docs/makiety/out/arkusz-mobilny.html → drop na auratest.
"""
import pathlib

OUT = pathlib.Path(__file__).parent / 'out'
OUT.mkdir(exist_ok=True)

RAZEM = 2964
NADWOZIE = [('SUV', 1672), ('Sedan', 969), ('Minivan', 162), ('Liftback', 88),
            ('Kombi', 34), ('Pickup', 8), ('Hatchback', 6), ('Coupé', 3)]
MARKI = [('BYD', 374), ('XPENG', 241), ('Xiaomi', 199), ('NIO', 196), ('AITO', 188),
         ('Leapmotor', 185), ('Li Auto', 179), ('Hongqi', 161), ('Zeekr', 150), ('Jetour', 123),
         ('Geely', 119), ('Denza', 100), ('VOYAH', 76), ('Lynk & Co', 75), ('Avatr', 60),
         ('Chery', 59), ('Tank', 50), ('iCar', 42), ('Deepal', 42), ('Changan', 41),
         ('GAC', 30), ('Luxeed', 30), ('IM Motors', 28), ('MG', 24), ('Dongfeng', 22),
         ('Haval', 16), ('Mazda', 16), ('Nissan', 15), ('smart', 15), ('BAIC', 12),
         ('Exeed', 11), ('Lotus', 9), ('Volkswagen', 9), ('Volvo', 8), ('Exlantix', 6),
         ('WEY', 6), ('Audi', 6), ('Chery Fulwin', 5), ('Ford', 4), ('Maxus', 3)]


def opcje(dane, prefix):
    return '\n'.join(
        f'<label class="opt"><input type="checkbox" data-n="{n}" data-l="{nazwa}">'
        f'<span class="box"></span><span class="lab">{nazwa}</span>'
        f'<span class="cnt">{n:,}</span></label>'.replace(',', ' ')
        for nazwa, n in dane)


HTML = f"""<title>Arkusz filtra na telefonie</title>
<style>
:root {{
  --granat:#1B2A4A; --tekst:#2D3748; --szary:#5C6B7F; --linia:#E1E4E8; --linia-m:#B9C1CB;
  --tlo:#F5F6F8; --plaszczyzna:#fff; --akcent:#C92A2B; --r:6px;
  --font:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}}
* {{ box-sizing:border-box; }}
body {{ margin:0; font-family:var(--font); background:var(--tlo); color:var(--tekst); }}
.naglowek {{ background:var(--granat); color:#fff; padding:16px 16px 12px; }}
.naglowek h1 {{ margin:0 0 4px; font-size:17px; letter-spacing:.01em; }}
.naglowek p {{ margin:0; font-size:13px; line-height:1.45; color:#C8D0DC; }}
.zakladki {{ display:flex; gap:6px; padding:12px 12px 0; overflow-x:auto; -webkit-overflow-scrolling:touch;
  position:sticky; top:0; z-index:50; background:var(--tlo); border-bottom:1px solid var(--linia); }}
.zakladki button {{ flex:0 0 auto; border:1px solid var(--linia-m); background:#fff; color:var(--tekst);
  font:600 13px/1 var(--font); padding:0 14px; height:40px; border-radius:var(--r) var(--r) 0 0;
  margin-bottom:-1px; cursor:pointer; }}
.zakladki button[aria-selected="true"] {{ background:var(--granat); color:#fff; border-color:var(--granat); }}
.opis {{ padding:14px 16px; font-size:13.5px; line-height:1.5; color:var(--szary);
  border-bottom:1px solid var(--linia); background:#fff; }}
.opis b {{ color:var(--tekst); }}
.opis .zle {{ color:var(--akcent); font-weight:600; }}
.scena {{ padding:16px; }}
.pole {{ margin-bottom:14px; }}
.pole > p {{ margin:0 0 6px; font:600 13px/1.3 var(--font); color:var(--tekst); }}
.sel {{ display:flex; align-items:center; width:100%; min-height:48px; padding:0 12px;
  background:#fff; border:1px solid var(--linia-m); border-radius:var(--r);
  font:400 16px/1 var(--font); color:var(--tekst); text-align:left; cursor:pointer; }}
.sel.is-active {{ border-color:var(--granat); box-shadow:inset 0 0 0 1px var(--granat); font-weight:600; }}
.sel .caret {{ margin-left:auto; width:8px; height:8px; border-right:2px solid var(--szary);
  border-bottom:2px solid var(--szary); transform:rotate(45deg) translate(-2px,-2px); }}
.podpowiedz {{ font-size:12.5px; color:var(--szary); padding:2px 2px 10px; }}
.wynik {{ margin-top:6px; padding:14px; background:#fff; border:1px solid var(--linia); border-radius:var(--r); }}
.wynik b {{ display:block; font-size:26px; color:var(--granat); }}
.wynik span {{ font-size:13px; color:var(--szary); }}

/* --- arkusz --- */
.zaslona {{ position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:900; }}
.ark {{ position:fixed; inset:auto 0 0 0; z-index:901; background:#fff;
  border-radius:12px 12px 0 0; box-shadow:0 -8px 40px rgba(27,42,74,.22);
  max-height:78vh; display:flex; flex-direction:column; }}
.ark[hidden], .zaslona[hidden] {{ display:none; }}
.uchwyt {{ width:38px; height:4px; margin:8px auto 6px; background:var(--linia-m); border-radius:2px; flex:0 0 auto; }}
.ark-top {{ display:flex; align-items:center; gap:10px; padding:4px 16px 12px;
  border-bottom:1px solid var(--linia); flex:0 0 auto; }}
.ark-top h2 {{ margin:0; font-size:16px; color:var(--granat); }}
.ark-top .ile {{ min-width:22px; height:22px; padding:0 7px; border-radius:11px; background:var(--granat);
  color:#fff; font:700 12px/22px var(--font); text-align:center; }}
.ark-top .x {{ margin-left:auto; width:40px; height:40px; border:0; background:none; cursor:pointer;
  font-size:22px; line-height:1; color:var(--szary); }}
.ark-lista {{ overflow-y:auto; -webkit-overflow-scrolling:touch; padding:6px 16px; flex:1 1 auto; }}
.ark-stopka {{ padding:10px 16px calc(12px + env(safe-area-inset-bottom));
  border-top:1px solid var(--linia); flex:0 0 auto; background:#fff; }}
.przycisk {{ display:flex; align-items:center; justify-content:center; width:100%; min-height:48px;
  border:0; border-radius:var(--r); background:var(--akcent); color:#fff;
  font:600 16px/1 var(--font); cursor:pointer; }}
.opt {{ display:flex; align-items:center; gap:10px; min-height:48px; cursor:pointer; }}
.opt input {{ position:absolute; opacity:0; width:0; height:0; }}
.opt .box {{ flex:0 0 auto; width:22px; height:22px; border:2px solid var(--linia-m);
  border-radius:4px; position:relative; }}
.opt input:checked + .box {{ background:var(--granat); border-color:var(--granat); }}
.opt input:checked + .box::after {{ content:''; position:absolute; left:6px; top:2px; width:6px; height:11px;
  border:solid #fff; border-width:0 2px 2px 0; transform:rotate(45deg); }}
.opt .lab {{ font-size:15.5px; }}
.opt .cnt {{ margin-left:auto; font-size:13px; color:var(--szary); font-variant-numeric:tabular-nums; }}
.opt input:focus-visible + .box {{ outline:2px solid var(--granat); outline-offset:2px; }}

/* wariant C — pełny ekran */
.ark.pelny {{ inset:0; max-height:none; border-radius:0; }}
.ark.pelny .uchwyt {{ display:none; }}
.ark.pelny .ark-top {{ padding:calc(10px + env(safe-area-inset-top)) 16px 12px; }}
.ark-top .wstecz {{ width:40px; height:40px; border:0; background:none; font-size:20px; color:var(--granat); cursor:pointer; }}
.szukajka {{ padding:10px 16px 0; flex:0 0 auto; }}
.szukajka input {{ width:100%; min-height:44px; padding:0 12px; font:400 16px/1 var(--font);
  border:1px solid var(--linia-m); border-radius:var(--r); }}

.stopa {{ padding:18px 16px 60px; font-size:13px; line-height:1.55; color:var(--szary); }}
.stopa code {{ background:#fff; padding:1px 5px; border-radius:3px; border:1px solid var(--linia); font-size:12px; }}
</style>

<div class="naglowek">
  <h1>Arkusz filtra na telefonie — warianty</h1>
  <p>Dotknij „Rodzaj nadwozia”, zaznacz kilka wartości i sprawdź, czy wiesz, co zrobić dalej.
     Liczby są prawdziwe (2&nbsp;964 oferty, stan 3.09).</p>
</div>

<div class="zakladki" role="tablist">
  <button role="tab" data-w="dzis" aria-selected="true">Dziś</button>
  <button role="tab" data-w="a" aria-selected="false">A — nagłówek + stopka</button>
  <button role="tab" data-w="b" aria-selected="false">B — sama stopka</button>
  <button role="tab" data-w="c" aria-selected="false">C — pełny ekran</button>
</div>

<div class="opis" id="opis"></div>

<div class="scena">
  <div class="pole">
    <p>Rodzaj nadwozia</p>
    <button class="sel" data-lista="nadwozie"><span class="txt">Wszystkie</span><i class="caret"></i></button>
  </div>
  <div class="pole">
    <p>Marka pojazdu</p>
    <button class="sel" data-lista="marka"><span class="txt">Wszystkie</span><i class="caret"></i></button>
  </div>
  <p class="podpowiedz">Marka ma 40 pozycji — dłuższa lista pokazuje różnicę między arkuszem a pełnym ekranem.</p>
  <div class="wynik"><b id="total">2&nbsp;964</b><span>ofert pasuje do filtrów</span></div>
</div>

<div class="stopa">
  <p><b>Co jest dziś:</b> arkusz to sama lista. Zamyka go tap w ciemny pasek nad nim,
  <code>Escape</code> (klawiatury na telefonie nie ma) albo — na Androidzie — gest wstecz,
  który wychodzi z całej strony, bo otwarcie arkusza nie dodaje wpisu do historii.
  Kreska u góry wygląda jak uchwyt do zsunięcia, ale gestu nie ma.</p>
  <p>W wariantach A i C gest wstecz na Androidzie zamyka arkusz, a nie stronę, i działa
  zsuwanie palcem w dół. Liczba w przycisku bierze się z tego samego zapytania, które już
  dziś przelicza wyniki — nie trzeba nic dokładać po stronie serwera.</p>
</div>

<div class="zaslona" id="zaslona" hidden></div>
<div class="ark" id="ark" hidden role="dialog" aria-modal="true" aria-labelledby="ark-tyt">
  <div class="uchwyt" id="uchwyt"></div>
  <div class="ark-top" id="ark-top">
    <button class="wstecz" id="wstecz" hidden aria-label="Wróć">←</button>
    <h2 id="ark-tyt">Rodzaj nadwozia</h2>
    <span class="ile" id="ile" hidden>0</span>
    <button class="x" id="x" aria-label="Zamknij">×</button>
  </div>
  <div class="szukajka" id="szukajka" hidden><input type="text" placeholder="Szukaj marki…" aria-label="Szukaj"></div>
  <div class="ark-lista" id="ark-lista"></div>
  <div class="ark-stopka" id="ark-stopka" hidden>
    <button class="przycisk" id="pokaz">Pokaż 2&nbsp;964 oferty</button>
  </div>
</div>

<script>
(function () {{
  var LISTY = {{
    nadwozie: {{ tytul:'Rodzaj nadwozia', szukaj:false, html:`{opcje(NADWOZIE, 'n')}` }},
    marka:    {{ tytul:'Marka pojazdu',   szukaj:true,  html:`{opcje(MARKI, 'm')}` }}
  }};
  var OPISY = {{
    dzis: '<b>Stan dzisiejszy.</b> Zaznacz kombi i SUV — i spróbuj wyjść. <span class="zle">Nie ma przycisku, nie ma tytułu, nie widać, ile ofert zostało.</span> Jedyne wyjście to tap w ciemny pasek nad arkuszem.',
    a:    '<b>Wariant A.</b> Tytuł filtra, licznik zaznaczonych i „×” u góry; na dole przycisk z liczbą wyników, która zmienia się przy każdym zaznaczeniu. Zsuwanie palcem w dół też zamyka.',
    b:    '<b>Wariant B.</b> Minimum: tylko przycisk „Gotowe”. Wychodzi się świadomie, ale przy 36 markach nie widać, co się właściwie wybiera, ani ile ofert zostaje.',
    c:    '<b>Wariant C.</b> Pełny ekran ze strzałką wstecz i szukajką — najwięcej miejsca na długie listy. Sprawdź go na „Marce”, tam różnica jest największa.'
  }};
  var RAZEM = {RAZEM};
  var w = 'dzis', biezaca = null;
  var $ = function (id) {{ return document.getElementById(id); }};
  var ark = $('ark'), zas = $('zaslona'), lista = $('ark-lista');

  function fmt(n) {{ return String(n).replace(/\\B(?=(\\d{{3}})+(?!\\d))/g, '\\u00a0'); }}

  function policz() {{
    var z = [].slice.call(lista.querySelectorAll('input:checked'));
    var suma = z.reduce(function (a, i) {{ return a + (+i.dataset.n || 0); }}, 0);
    var n = z.length ? suma : RAZEM;
    $('ile').textContent = z.length; $('ile').hidden = !z.length || w !== 'a';
    $('pokaz').innerHTML = w === 'b' ? 'Gotowe'
      : 'Pokaż ' + fmt(n) + (n === 1 ? ' ofertę' : (n % 10 > 1 && n % 10 < 5 && (n % 100 < 10 || n % 100 > 20) ? ' oferty' : ' ofert'));
    $('total').innerHTML = fmt(n);
    var sel = document.querySelector('.sel[data-lista="' + biezaca + '"]');
    if (sel) {{
      sel.querySelector('.txt').textContent = z.length
        ? (z.length === 1 ? z[0].dataset.l : z[0].dataset.l + ' +' + (z.length - 1)) : 'Wszystkie';
      sel.classList.toggle('is-active', !!z.length);
    }}
  }}

  function otworz(klucz) {{
    biezaca = klucz;
    var L = LISTY[klucz];
    $('ark-tyt').textContent = L.tytul;
    lista.innerHTML = L.html;
    ark.classList.toggle('pelny', w === 'c');
    $('ark-top').hidden = (w === 'dzis' || w === 'b');
    $('uchwyt').hidden = (w === 'c');
    $('wstecz').hidden = (w !== 'c');
    $('x').hidden = (w === 'c');
    $('szukajka').hidden = !(w === 'c' && L.szukaj);
    $('ark-stopka').hidden = (w === 'dzis');
    ark.hidden = false; zas.hidden = (w === 'c');
    document.body.style.overflow = 'hidden';
    policz();
    if (w !== 'dzis') history.pushState({{ ark: 1 }}, '');
  }}

  function zamknij(zHistorii) {{
    if (ark.hidden) return;
    ark.hidden = true; zas.hidden = true; document.body.style.overflow = '';
    if (!zHistorii && w !== 'dzis' && history.state && history.state.ark) history.back();
  }}

  document.addEventListener('click', function (e) {{
    var sel = e.target.closest('.sel');
    if (sel) {{ otworz(sel.dataset.lista); return; }}
    if (e.target.id === 'zaslona' || e.target.id === 'x' || e.target.id === 'pokaz' || e.target.id === 'wstecz') {{
      zamknij(); return;
    }}
    var tab = e.target.closest('.zakladki button');
    if (tab) {{
      w = tab.dataset.w;
      [].forEach.call(document.querySelectorAll('.zakladki button'), function (b) {{
        b.setAttribute('aria-selected', b === tab ? 'true' : 'false');
      }});
      $('opis').innerHTML = OPISY[w];
      zamknij(); return;
    }}
  }});
  lista.addEventListener('change', policz);
  window.addEventListener('popstate', function () {{ zamknij(true); }});
  document.addEventListener('keydown', function (e) {{ if (e.key === 'Escape') zamknij(); }});

  // zsuwanie palcem w dół (A i B; w C pełny ekran zamyka strzałka)
  var y0 = null;
  ark.addEventListener('touchstart', function (e) {{ y0 = e.touches[0].clientY; }}, {{ passive: true }});
  ark.addEventListener('touchmove', function (e) {{
    if (y0 === null || w === 'dzis' || w === 'c') return;
    var d = e.touches[0].clientY - y0;
    if (d > 70 && lista.scrollTop <= 0) {{ y0 = null; zamknij(); }}
  }}, {{ passive: true }});

  $('opis').innerHTML = OPISY.dzis;
}})();
</script>
"""

(OUT / 'arkusz-mobilny.html').write_text(HTML, encoding='utf-8')
print('OK:', OUT / 'arkusz-mobilny.html')
