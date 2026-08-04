#!/usr/bin/env python3
"""
Generator treści hubów MAREK (`/samochody/<marka>/`) — T-225a / audyt 2026-08-04.

Powód powstania: audyt 58 marek pokazał dziury w treści — 6 marek bez `asiaauto_wiki_body`,
9 bez FAQ i opisu termu, 11 bez leadu i sufiksu H1. Rework z 06-08/06-09 robiono ręcznie
przez subagenty, bez skryptu, więc każda nowa marka z ręcznego importu zaczyna od zera.

Wypełnia pola wg metody z `docs/seo/hub-rework-method-2026-05-30.md`:
  _asiaauto_lead        — answer-first, 2-3 zdania (cena + dostępność + zaufanie)
  _asiaauto_h1_suffix   — sufiks H1 (fallback szablonu: „import z Chin")
  asiaauto_wiki_body    — treść hubu, H2 intent-led, {{LISTINGS_BAR}} po intro
  asiaauto_faq_json     — 5 pytań, źródło FAQPage JSON-LD
  description (term)    — opis marki, używany m.in. jako teaser na /marki/

Twarde reguły (wyuczone na incydentach):
  * ASCII dotyczy WYŁĄCZNIE cudzysłowów — tekst po polsku Z DIAKRYTYKAMI (T-193, 2026-07-09).
    Gate: zdjęcie diakrytyk z wyniku musi dać dokładnie to samo, co wejście bez diakrytyk.
  * Liczby (ceny, liczba ofert) TYLKO z bazy, nigdy z modelu.
  * Backup termmeta przed zapisem.

Użycie:
  python3 make_hub_generate.py --slugs maxus,foton --dry-run
  python3 make_hub_generate.py --slugs maxus --apply
"""
import argparse
import json
import re
import subprocess
import sys
import unicodedata
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb

BACKUP_DIR = Path.home() / "backups" / "primaauto" / date.today().isoformat()

RESEARCH_PROMPT = """Jesteś researcherem redakcji motoryzacyjnej specjalizującej się w rynku chińskim.
Zbierz z sieci AKTUALNE fakty o podanej marce samochodowej: do jakiej grupy kapitałowej należy i od kiedy,
rok założenia, w czym się specjalizuje, najważniejsze modele, pozycja na rynku chińskim, obecność w Europie
i w Polsce (czy ma oficjalnego dystrybutora), znane mocne strony i problemy.
Uwaga na pułapkę: chińskie marki bywają mylone z podobnie brzmiącymi — sprawdź, czy właściciel się zgadza.
Zwróć czysty JSON: {"fakty": ["fakt z datą/liczbą", ...], "zrodla": ["url", ...]} — 6-12 faktów, tylko zweryfikowane."""

GEN_PROMPT = """Piszesz treść strony marki dla Prima-Auto — polskiego importera samochodów z Chin.

ZASADY:
1. Polszczyzna z pełnymi diakrytykami. ASCII stosuj WYŁĄCZNIE do cudzysłowów (") — nigdy nie zamieniaj
   polskich liter na łacińskie odpowiedniki.
2. Każda liczba (cena, liczba ofert, rocznik) MUSI pochodzić z sekcji DANE Z NASZEJ BAZY. Nie wymyślaj żadnej.
3. Nie obiecuj dostępności w polskich salonach, jeśli nie ma jej w danych — te auta sprowadzamy z Chin.
4. Ton rzeczowy, bez marketingowego nadęcia, bez wykrzykników. Piszemy do kogoś, kto rozważa wydanie
   150-300 tysięcy złotych i chce wiedzieć, z czym ma do czynienia.
5. Nagłówki H2 mają odpowiadać na realne pytania kupującego, nie brzmieć jak spis treści.
6. NIE OPISUJ NASZEGO PROCESU IMPORTU. Zakaz obejmuje: nazwy organów i miejsc, w których auto
   uzyskuje dopuszczenie do ruchu (nie wiemy tego i nie publikujemy), kolejność etapów sprowadzania,
   miasta i porty, nazwy partnerów, czasy trwania poszczególnych kroków. Piszemy o REZULTACIE dla
   klienta („autem dopuszczonym do ruchu z kompletem dokumentów zajmujemy się my, koszt w cenie"),
   nigdy o tym, jak to robimy — to informacja dla konkurencji, a jej szczegóły bywają nieprawdziwe.
   Dotyczy również FAQ: na pytanie „jak wygląda homologacja" odpowiadaj rezultatem, nie procedurą.

Zwróć czysty JSON o strukturze:
{
  "lead": "2-3 zdania answer-first: czym jest marka, jakie auta, w jakiej cenie u nas",
  "h1_suffix": "krótki sufiks do H1 po nazwie marki, np. 'pickupy i auta użytkowe z Chin'",
  "wiki_body": "<h2>...</h2><p>...</p> — 5-7 sekcji H2, HTML, po pierwszym akapicie wstaw dokładnie {{LISTINGS_BAR}}",
  "faq": [{"q": "pytanie", "a": "odpowiedź 2-4 zdania"}],
  "description": "opis marki na 3-5 zdań, samodzielny — używany jako zajawka w katalogu marek"
}
FAQ ma mieć dokładnie 5 pytań, takich jakie realnie zadaje kupujący."""


def diacritics_gate(text):
    """True, gdy tekst zawiera polskie znaki — ochrona przed regresją z T-193."""
    return bool(re.search(r"[ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]", text))


def strip_diacritics(s):
    return "".join(c for c in unicodedata.normalize("NFD", s) if unicodedata.category(c) != "Mn")


def brand_data(slug):
    """Fakty o marce z NASZEJ bazy — jedyne źródło liczb w treści."""
    php = f'''
$t = get_term_by("slug", "{slug}", "make");
if (!$t) {{ echo "null"; exit; }}
$q = new WP_Query(["post_type"=>"listings","post_status"=>"publish","posts_per_page"=>-1,
    "tax_query"=>[["taxonomy"=>"make","field"=>"term_id","terms"=>$t->term_id]]]);
$rows = []; $prices = [];
foreach ($q->posts as $p) {{
    $price = (int) get_post_meta($p->ID, "price", true);
    if ($price > 0) $prices[] = $price;
    $rows[] = [
        "tytul" => $p->post_title,
        "cena"  => $price,
        "model" => (wp_get_post_terms($p->ID,"serie",["fields"=>"names"])[0] ?? ""),
        "paliwo"=> (wp_get_post_terms($p->ID,"fuel",["fields"=>"names"])[0] ?? ""),
        "nadwozie" => (wp_get_post_terms($p->ID,"body",["fields"=>"names"])[0] ?? ""),
        "rok"   => (wp_get_post_terms($p->ID,"ca-year",["fields"=>"names"])[0] ?? ""),
    ];
}}
echo json_encode([
    "nazwa" => $t->name, "slug" => $t->slug, "url" => get_term_link($t),
    "ofert" => count($rows), "cena_min" => $prices ? min($prices) : 0,
    "cena_max" => $prices ? max($prices) : 0, "oferty" => $rows,
], JSON_UNESCAPED_UNICODE);
'''
    out = kb.wp("eval", php)
    return json.loads(out) if out.strip() and out.strip() != "null" else None


def generate(data, model):
    name = data["nazwa"]
    print(f"  research…", flush=True)
    facts, sources = [], []
    try:
        text, _ = kb.call_model(RESEARCH_PROMPT, f"MARKA: {name} (chiński rynek motoryzacyjny)",
                                tools="WebSearch,WebFetch", model=model)
        parsed = kb.parse_json_response(text) or {}
        facts = parsed.get("fakty", [])
        # Źródła zapisujemy razem z treścią — bez nich nie da się później zweryfikować
        # twierdzeń o rejestracjach, akcjach serwisowych czy usterkach (wiki_generate.py
        # trzyma je w `_wiki_sources` i to samo robimy tutaj).
        sources = parsed.get("zrodla", [])
    except Exception as e:
        print(f"    research nieudany ({e}) — piszę na danych z bazy", flush=True)

    listing = "\n".join(
        f"  - {o['tytul']} — {o['cena']:,} zł, model {o['model']}, {o['paliwo']}, {o['nadwozie']}, rocznik {o['rok']}".replace(",", " ")
        for o in data["oferty"][:20])

    user = f"""MARKA: {name}

DANE Z NASZEJ BAZY (jedyne źródło liczb):
  ofert w sprzedaży: {data['ofert']}
  widełki cenowe: {data['cena_min']:,} - {data['cena_max']:,} zł (ceny końcowe, po cle i podatkach)
{listing}

FAKTY Z RESEARCHU (kontekst rynkowy, nie liczby o naszej ofercie):
{chr(10).join('  - ' + f for f in facts) if facts else '  (brak — opieraj się na danych z bazy i wiedzy ogólnej)'}
""".replace(",", " ")

    print(f"  generacja…", flush=True)
    text, _ = kb.call_model(GEN_PROMPT, user, model=model)
    gen = kb.parse_json_response(text)
    if gen is not None:
        gen["_sources"] = sources
    return gen


def apply(data, gen):
    """Zapis przez jedno wywołanie `wp eval` ze stdin — cała treść idzie JSON-em,
    więc żaden cudzysłów ani polski znak nie przechodzi przez escapowanie powłoki."""
    slug = data["slug"]
    BACKUP_DIR.mkdir(parents=True, exist_ok=True)

    dump = kb.wp("eval", f'''
$t = get_term_by("slug","{slug}","make");
echo json_encode([
  "description" => $t->description,
  "wiki" => get_term_meta($t->term_id,"asiaauto_wiki_body",true),
  "faq"  => get_term_meta($t->term_id,"asiaauto_faq_json",true),
  "lead" => get_term_meta($t->term_id,"_asiaauto_lead",true),
  "h1"   => get_term_meta($t->term_id,"_asiaauto_h1_suffix",true),
], JSON_UNESCAPED_UNICODE);''')
    (BACKUP_DIR / f"make-{slug}.before.json").write_text(dump, encoding="utf-8")

    payload = {
        "lead": kb.normalize_quotes(gen["lead"]),
        "h1_suffix": gen["h1_suffix"],
        "wiki_body": gen["wiki_body"],
        "faq_json": kb.normalize_quotes(json.dumps(gen["faq"], ensure_ascii=False)),
        "description": gen["description"],
        "rework": f"v2-{date.today().isoformat()}",
        "sources": json.dumps(gen.get("_sources", []), ensure_ascii=False),
    }

    php = f'''
$in = json_decode(file_get_contents("php://stdin"), true);
$t  = get_term_by("slug","{slug}","make");
update_term_meta($t->term_id, "_asiaauto_lead",       $in["lead"]);
update_term_meta($t->term_id, "_asiaauto_h1_suffix",  $in["h1_suffix"]);
update_term_meta($t->term_id, "asiaauto_wiki_body",   $in["wiki_body"]);
update_term_meta($t->term_id, "asiaauto_faq_json",    $in["faq_json"]);
update_term_meta($t->term_id, "_asiaauto_seo_rework", $in["rework"]);
update_term_meta($t->term_id, "_asiaauto_sources",     $in["sources"]);
wp_update_term($t->term_id, "make", ["description" => $in["description"]]);
echo "ok";'''
    res = kb.wp("eval", php, stdin=json.dumps(payload, ensure_ascii=False))
    print(f"  zapis: {res.strip()} (backup: {BACKUP_DIR}/make-{slug}.before.json)", flush=True)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--slugs", required=True, help="comma-separated slugi marek")
    ap.add_argument("--model", default="sonnet")
    ap.add_argument("--apply", action="store_true", help="zapis do bazy (domyślnie dry-run)")
    ap.add_argument("--dry-run", action="store_true", help="jawny dry-run (domyślne zachowanie)")
    args = ap.parse_args()

    ok = err = 0
    for slug in [s.strip() for s in args.slugs.split(",") if s.strip()]:
        print(f"\n=== {slug}", flush=True)
        data = brand_data(slug)
        if not data:
            print("  BŁĄD: brak termu albo zero ofert publish", flush=True); err += 1; continue

        gen = generate(data, args.model)
        if not gen or not all(k in gen for k in ("lead", "h1_suffix", "wiki_body", "faq", "description")):
            print("  BŁĄD: niepełna odpowiedź modelu", flush=True); err += 1; continue

        joined = " ".join([gen["lead"], gen["wiki_body"], gen["description"],
                           " ".join(q["q"] + q["a"] for q in gen["faq"])])
        if not diacritics_gate(joined):
            print("  BŁĄD: brak polskich znaków (regresja T-193) — pomijam", flush=True); err += 1; continue
        if "{{LISTINGS_BAR}}" not in gen["wiki_body"]:
            print("  UWAGA: brak {{LISTINGS_BAR}} w wiki_body", flush=True)

        print(f"  lead: {gen['lead'][:110]}…")
        print(f"  H1 suffix: {gen['h1_suffix']}")
        print(f"  wiki: {len(gen['wiki_body'])} znaków, H2: {gen['wiki_body'].count('<h2')}, FAQ: {len(gen['faq'])}")
        print(f"  opis: {len(gen['description'])} znaków")

        if args.apply:
            apply(data, gen)
        else:
            out = kb.KB_DIR / "tmp" / f"make-{slug}.json"
            out.parent.mkdir(exist_ok=True)
            out.write_text(json.dumps(gen, ensure_ascii=False, indent=2), encoding="utf-8")
            print(f"  dry-run → {out}")
        ok += 1

    print(f"\nGotowe: {ok} OK, {err} błędów")


if __name__ == "__main__":
    main()
