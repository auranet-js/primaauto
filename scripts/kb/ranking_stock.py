#!/usr/bin/env python3
"""
ranking_stock.py — warstwa NASZEJ DOSTĘPNOŚCI dla rankingów (T-229, Task 2).

Ranking opisuje rynek (D1), a ta warstwa dokłada przy pozycjach, które mamy w ofercie:
liczbę sztuk, cenę od, zdjęcie z naszej galerii i link. To ta część rotuje codziennie,
dlatego siedzi w osobnym bloku `<!--OFERTA:START/END-->` i przelicza się automatem (D4).

Pozycja bez dopasowania dostaje pusty blok — **nigdy komunikatu „nie mamy tego modelu"**,
bo to reklamowanie konkurencji na własnej stronie.

Poziomy dopasowania:
  marka  — rankingi sprzedaży („BYD 419 211 sztuk") → taksonomia `make`
  model  — rankingi parametryczne („zasięg", „800V")  → taksonomia `serie`

Dopasowanie jest ścisłe (nazwa albo slug). Świadomie **nie zgadujemy** — pozycja bez
pewnego dopasowania zostaje bez naszej oferty, zamiast trafić pod przypadkowy hub.

Użycie:
  python3 ranking_stock.py --json tmp/ranking-lipiec-2026.json --poziom marka
  python3 ranking_stock.py --json <plik> --poziom model --out <plik>
"""
import argparse
import json
import re
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb


def slug(s: str) -> str:
    s = (s or "").lower().strip()
    s = re.sub(r"[^\w\s-]", "", s, flags=re.UNICODE)
    return re.sub(r"[\s_]+", "-", s).strip("-")


def stan_taksonomii(taxonomy: str) -> dict:
    """Mapa nazwa/slug → dane o dostępności, jednym zapytaniem do WP."""
    php = f'''
$out = [];
foreach (get_terms(["taxonomy" => "{taxonomy}", "hide_empty" => true]) as $t) {{
    $q = new WP_Query([
        "post_type" => "listings", "post_status" => "publish", "posts_per_page" => -1,
        "no_found_rows" => true,
        "tax_query" => [["taxonomy" => "{taxonomy}", "field" => "term_id", "terms" => $t->term_id]],
    ]);
    if (!$q->posts) {{ continue; }}
    $ceny = []; $foto = ""; $min_id = 0; $min = PHP_INT_MAX; $marki = [];
    foreach ($q->posts as $p) {{
        $c = (int) get_post_meta($p->ID, "price", true);
        if ($c > 0) {{ $ceny[] = $c; if ($c < $min) {{ $min = $c; $min_id = $p->ID; }} }}
        foreach (wp_get_post_terms($p->ID, "make", ["fields" => "names"]) as $m) {{
            $marki[$m] = ($marki[$m] ?? 0) + 1;
        }}
    }}
    arsort($marki);
    if ($min_id) {{
        $tid = get_post_thumbnail_id($min_id);
        if ($tid) {{ $foto = wp_get_attachment_image_url($tid, "medium_large"); }}
    }}
    $out[] = [
        "nazwa" => $t->name,
        "slug"  => $t->slug,
        "marka" => (string) array_key_first($marki),
        "sztuk" => count($q->posts),
        "cena_od" => $ceny ? min($ceny) : 0,
        "url"   => get_term_link($t),
        "foto"  => $foto,
        "oferta_url" => $min_id ? get_permalink($min_id) : "",
    ];
}}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
'''
    dane = json.loads(kb.wp("eval", php))
    mapa = {}
    for d in dane:
        mapa[d["nazwa"].lower()] = d
        mapa[d["slug"]] = d
        mapa[slug(d["nazwa"])] = d
    return mapa


def ta_sama_marka(z_rankingu: str, nasza: str) -> bool:
    """Marki z rankingu bywają submarkami („Geely Galaxy", „GAC Aion", „BAIC Off-Road"),
    a taksonomia trzyma markę główną. Zgodność = jedna zawiera się w drugiej albo mają
    wspólny pierwszy człon."""
    a, b = (z_rankingu or "").lower().strip(), (nasza or "").lower().strip()
    if not a or not b:
        return True
    return a == b or a.startswith(b) or b.startswith(a) or a.split()[0] == b.split()[0]


def dopasuj(pozycja: str, mapa: dict, marka: str = ""):
    """Ścisłe dopasowanie: nazwa → slug. Bez fuzzy — lepiej brak niż zły hub.

    Sama nazwa modelu nie wystarcza: nazwy krótkie powtarzają się między markami. Casus
    z 2026-08-05: „AITO M6" (SUV) trafiał w nasz hub „M6", który jest vanem GAC-a. Dlatego
    dopasowanie po nazwie musi jeszcze zgadzać się co do marki."""
    for klucz in (pozycja.lower().strip(), slug(pozycja)):
        d = mapa.get(klucz)
        if d and ta_sama_marka(marka, d.get("marka", "")):
            return d
    return None


def klucz_pozycji(r: dict) -> str:
    """Stabilny identyfikator pozycji w treści wpisu — po nim `ranking_refresh.py` odnajduje
    blok do przeliczenia. Musi być niezależny od tego, czy akurat mamy model w ofercie,
    bo blok pusty też ma się kiedyś wypełnić."""
    return slug(f"{r.get('marka', '')} {r.get('model') or r.get('podmiot', '')}")


def blok_oferty_kompakt(d, klucz: str) -> str:
    """Wersja do komórki tabeli — sam tekst i link, bez zdjęcia. Ranking czyta się na telefonie
    (79,6% ruchu), a miniatura w każdym wierszu rozwala układ."""
    srodek = ""
    if d:
        cena = f"{d['cena_od']:,}".replace(",", " ")
        srodek = f'<a href="{d["url"]}">{d["sztuk"]} szt., od {cena} zł</a>'
    return f"<!--OFERTA:START:{klucz}-->{srodek}<!--OFERTA:END-->"


def blok_oferty(d) -> str:
    """HTML bloku dostępności. Brak dopasowania → blok pusty, bez komunikatu."""
    if not d:
        return "<!--OFERTA:START--><!--OFERTA:END-->"
    cena = f"{d['cena_od']:,}".replace(",", " ")
    foto = (f'<a href="{d["url"]}"><img src="{d["foto"]}" alt="{d["nazwa"]} — oferta Prima-Auto" '
            f'loading="lazy" decoding="async" style="width:100%;height:auto;border-radius:6px"></a>'
            if d.get("foto") else "")
    return (
        "<!--OFERTA:START-->"
        f'<div class="aa-rank-oferta">{foto}'
        f'<p><strong>Mamy w ofercie:</strong> {d["sztuk"]} '
        f'{"sztuka" if d["sztuk"] == 1 else "szt."}, od {cena} zł '
        f'— <a href="{d["url"]}">zobacz dostępne egzemplarze</a></p></div>'
        "<!--OFERTA:END-->"
    )


def wzbogac(ranking: list, poziom: str = "model", mapa: dict = None):
    """Dokłada do każdej pozycji `nasza_oferta`, `blok_html` i `blok_kompakt`.
    Wspólne wejście dla generatora (Task 3) i odświeżacza (Task 4)."""
    mapa = mapa if mapa is not None else stan_taksonomii("make" if poziom == "marka" else "serie")
    trafione = 0
    for r in ranking:
        d = dopasuj(r["podmiot"], mapa, r.get("nasza_marka") or r.get("marka", ""))
        r["nasza_oferta"] = d
        r["klucz"] = klucz_pozycji(r)
        r["blok_html"] = blok_oferty(d)
        r["blok_kompakt"] = blok_oferty_kompakt(d, r["klucz"])
        if d:
            trafione += 1
    return ranking, trafione


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--json", required=True, help="wynik ranking_market.py")
    ap.add_argument("--poziom", choices=["marka", "model"], default="marka")
    ap.add_argument("--out", help="zapisz wzbogacony ranking")
    a = ap.parse_args()

    dane = json.loads(Path(a.json).read_text(encoding="utf-8"))
    ranking = dane.get("ranking", [])
    if not ranking:
        print("Brak pozycji w rankingu — nic do dopasowania."); return

    taxonomy = "make" if a.poziom == "marka" else "serie"
    print(f"Dopasowanie po taksonomii `{taxonomy}` ({len(ranking)} pozycji)…\n")
    mapa = stan_taksonomii(taxonomy)

    ranking, trafione = wzbogac(ranking, a.poziom, mapa)
    for r in ranking:
        d = r["nasza_oferta"]
        if d:
            cena = f"{d['cena_od']:,}".replace(",", " ")
            print(f"  ✓ {r['podmiot']:<18} {d['sztuk']:>3} szt., od {cena:>9} zł   {'foto' if d['foto'] else 'BEZ FOTO'}")
        else:
            print(f"  · {r['podmiot']:<18} (nie mamy — blok pusty)")

    print(f"\nDopasowanych: {trafione} z {len(ranking)}")
    braki_foto = sum(1 for r in ranking if r["nasza_oferta"] and not r["nasza_oferta"].get("foto"))
    if braki_foto:
        print(f"⚠ Dopasowane bez zdjęcia: {braki_foto} — blok wyjdzie bez ilustracji")

    if a.out:
        dane["ranking"] = ranking
        dane["dopasowanych"] = trafione
        Path(a.out).write_text(json.dumps(dane, ensure_ascii=False, indent=2), encoding="utf-8")
        print(f"zapisano: {a.out}")


if __name__ == "__main__":
    main()
