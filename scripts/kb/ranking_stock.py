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
    $ceny = []; $foto = ""; $min_id = 0; $min = PHP_INT_MAX;
    foreach ($q->posts as $p) {{
        $c = (int) get_post_meta($p->ID, "price", true);
        if ($c > 0) {{ $ceny[] = $c; if ($c < $min) {{ $min = $c; $min_id = $p->ID; }} }}
    }}
    if ($min_id) {{
        $tid = get_post_thumbnail_id($min_id);
        if ($tid) {{ $foto = wp_get_attachment_image_url($tid, "medium_large"); }}
    }}
    $out[] = [
        "nazwa" => $t->name,
        "slug"  => $t->slug,
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


def dopasuj(pozycja: str, mapa: dict):
    """Ścisłe dopasowanie: nazwa → slug. Bez fuzzy — lepiej brak niż zły hub."""
    for klucz in (pozycja.lower().strip(), slug(pozycja)):
        if klucz in mapa:
            return mapa[klucz]
    return None


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

    trafione = 0
    for r in ranking:
        d = dopasuj(r["podmiot"], mapa)
        r["nasza_oferta"] = d
        r["blok_html"] = blok_oferty(d)
        if d:
            trafione += 1
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
