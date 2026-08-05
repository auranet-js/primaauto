#!/usr/bin/env python3
"""
ranking_generate.py — generator wpisu rankingowego (T-229, Task 3).

Składa wpis z trzech warstw o różnej odpowiedzialności:
  1. LICZBY   — `ranking_market.py` (API Dongchedi), model językowy ich nie dotyka,
  2. OFERTA   — `ranking_stock.py`, blok przeliczalny automatem (D4),
  3. NARRACJA — model językowy, ale wyłącznie tekst: lead, komentarz, FAQ.

Podział jest twardy: tabela i bloki oferty powstają w kodzie, model dostaje dane tylko po to,
żeby je skomentować. Reguła wyuczona na `make_hub_generate.py` — model, który sam „pamięta"
liczby, zawsze którąś przekręci, a przy rankingu liczba JEST treścią.

Wpis dostaje dwa znaczniki odświeżania (D8):
  <!--RANKING:START/END-->  — dane rynkowe, podmieniane świadomie przy nowym miesiącu
  <!--OFERTA:START:klucz/END--> — nasza dostępność, przeliczana automatem

Użycie:
  python3 ranking_generate.py --ranking suv --dry-run
  python3 ranking_generate.py --ranking suv --apply          # draft + mail do Janka
  python3 ranking_generate.py --ranking suv --miesiac 202605 --dry-run
"""
import argparse
import json
import re
import sys
from datetime import date, datetime
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb
import ranking_market as rm
import ranking_specs as rspec
import ranking_stock as rs
from make_cover import make_cover

DEFINICJE = Path(__file__).parent / "rankingi.json"
AUTOR_ID = 55                      # Redakcja Prima-Auto (ten sam co newsy)
TMP_PUBLIC = Path.home() / "domains" / "auratest.pl" / "public_html" / "fe4f58fec53ctmp"

MIESIACE = {
    "01": "styczeń", "02": "luty", "03": "marzec", "04": "kwiecień", "05": "maj", "06": "czerwiec",
    "07": "lipiec", "08": "sierpień", "09": "wrzesień", "10": "październik", "11": "listopad",
    "12": "grudzień",
}
MIESIACE_D = {
    "01": "styczniu", "02": "lutym", "03": "marcu", "04": "kwietniu", "05": "maju", "06": "czerwcu",
    "07": "lipcu", "08": "sierpniu", "09": "wrześniu", "10": "październiku", "11": "listopadzie",
    "12": "grudniu",
}

GEN_PROMPT = """Piszesz tekst rankingowy dla Prima-Auto — polskiego importera samochodów z Chin.
Ranking opisuje RYNEK CHIŃSKI: co Chińczycy naprawdę kupili w danym miesiącu. Nasza oferta jest
warstwą dodaną, nie tematem.

ZASADY:
1. Polszczyzna z pełnymi diakrytykami. ASCII stosuj WYŁĄCZNIE do cudzysłowów (") — nigdy nie
   zamieniaj polskich liter na łacińskie odpowiedniki.
2. NIE PODAWAJ ŻADNEJ LICZBY, KTÓREJ NIE MA W SEKCJI DANE. Nie zaokrąglaj ich po swojemu, nie
   dodawaj własnych szacunków, nie przeliczaj walut. Liczby są w tabeli, którą czytelnik widzi
   pod tekstem — twoja rola to je zinterpretować, nie powtarzać wszystkie.
3. NIE OPISUJ NASZEGO PROCESU IMPORTU. Zakaz obejmuje nazwy organów i miejsc dopuszczenia do
   ruchu, kolejność etapów sprowadzania, porty, partnerów i czasy trwania kroków. Piszemy
   o rezultacie dla klienta, nigdy o tym, jak to robimy.
4. Nie obiecuj dostępności w polskich salonach. Te auta sprowadzamy z Chin.
5. Ton rzeczowy, bez marketingowego nadęcia, bez wykrzykników, bez „warto wspomnieć"
   i „podsumowując". Piszesz do kogoś, kto rozważa wydanie 150-300 tysięcy złotych.
6. Nagłówki H2 odpowiadają na pytania kupującego, nie brzmią jak spis treści.
7. Nie komentuj modeli, których nie ma w danych. Nie porównuj do aut europejskich z pamięci.
8. NIE PISZ O STANIE NASZEJ OFERTY: ile sztuk mamy, od jakiej ceny, ilu modeli z rankingu nie
   mamy. Te dane rotują codziennie i wchodzą osobnym, automatycznie przeliczanym blokiem —
   zdanie „mamy 5 sztuk od 240 000 zł" zestarzeje się w tydzień. Możesz napisać, że model
   jest u nas dostępny, bez liczb. Ceny podawaj wyłącznie jako fakt z rynku chińskiego,
   jeśli są w danych.

Zwróć czysty JSON:
{
  "lead": "2-3 zdania answer-first: co to za ranking, z jakiego okresu, co z niego wynika",
  "wstep": "<p>...</p> 1-2 akapity HTML wprowadzające do tabeli — czym jest to zestawienie i jak je czytać",
  "sekcje": [{"h2": "nagłówek", "html": "<p>...</p>"}],
  "faq": [{"q": "pytanie", "a": "odpowiedź 2-4 zdania"}],
  "excerpt": "zajawka 1 zdanie do listingu",
  "meta_opis": "opis SEO do 155 znaków"
}
`sekcje` ma mieć 3-4 pozycje: komentarz do czołówki rankingu, co mówi zmiana miesiąc do miesiąca,
co z tego wynika dla kupującego w Polsce. FAQ dokładnie 5 pytań, takich jakie realnie zadaje
ktoś, kto wpisał frazę główną w Google."""


def wczytaj_definicje(nazwa: str) -> dict:
    dane = json.loads(DEFINICJE.read_text(encoding="utf-8"))
    d = dane["rankingi"].get(nazwa)
    if not d:
        raise SystemExit(f"Brak definicji `{nazwa}`. Dostępne: {', '.join(dane['rankingi'])}")
    if d.get("gotowy") is False:
        raise SystemExit(f"Definicja `{nazwa}` oznaczona jako niegotowa: {d.get('uwaga', '')}")
    d["_nazwa"] = nazwa
    return d


def okres_slownie(miesiac: str, dopelniacz=False) -> str:
    tab = MIESIACE_D if dopelniacz else MIESIACE
    return f"{tab[miesiac[4:6]]} {miesiac[:4]}"


def tabela_rankingu(pozycje: list, okres: str) -> str:
    """Tabela = źródło prawdy wpisu. Układ pod telefon (79,6% ruchu): klasa nadwozia jedzie
    w komórce modelu jako druga linia, a kolumna „Klasa" znika pod 700 px — zamiast zmuszać
    do przewijania w bok sześciu kolumn."""
    wiersze = []
    for p in pozycje:
        zmiana = "nowość" if p.get("zmiana_proc") is None else f"{p['zmiana_proc']:+d}%"
        klasa_zm = ""
        if p.get("zmiana_proc") is not None:
            klasa_zm = " aa-rank-up" if p["zmiana_proc"] > 0 else (
                " aa-rank-down" if p["zmiana_proc"] < 0 else "")
        auto = f"{p['marka']} {p['model']}"
        dopiski = []
        if p.get("nasza_serie") and p["nasza_serie"].lower() not in auto.lower():
            dopiski.append(f"u nas: {p['nasza_serie']}")
        drugalinia = (f'<span class="aa-rank-alias">{" · ".join(dopiski)}</span>' if dopiski else "")
        wiersze.append(
            f'<tr><td class="aa-rank-poz">{p["pozycja"]}</td>'
            f'<td class="aa-rank-auto">{auto}{drugalinia}'
            f'<span class="aa-rank-klasa-mob">{p["klasa"]}</span></td>'
            f'<td class="aa-rank-klasa">{p["klasa"]}</td>'
            f'<td class="aa-rank-szt">{p["wartosc"]:,}</td>'.replace(",", "&nbsp;") +
            f'<td class="aa-rank-zm{klasa_zm}">{zmiana}</td>'
            f'<td class="aa-rank-oferta">{p["blok_kompakt"]}</td></tr>'
        )
    return (
        '<div class="aa-rank-tabela">'
        '<table class="aa-rank"><thead><tr>'
        '<th class="aa-rank-poz">#</th><th>Model</th><th class="aa-rank-klasa">Klasa</th>'
        f'<th class="aa-rank-szt">Sprzedaż<span>{okres}</span></th>'
        '<th class="aa-rank-zm">m/m</th><th class="aa-rank-oferta">U nas</th>'
        "</tr></thead><tbody>" + "".join(wiersze) + "</tbody></table></div>"
    )


def tabela_specow(pozycje: list, dane: dict, d: dict) -> str:
    """Tabela rankingu parametrycznego. Kolumny zależą od parametru: przy zasięgu liczy się
    bateria, przy przyspieszeniu moc i prędkość. Zasięg zawsze z etykietą CLTC (D5) — chińska
    norma jest łagodniejsza od WLTP mniej więcej o jedną czwartą."""
    par = dane.get("parametr")
    kolumny = {
        "zasieg": [("Bateria", lambda p: f'{p["bateria"]} kWh' if p.get("bateria") else "—"),
                   ("Ogniwa", lambda p: " ".join(x for x in (p.get("ogniwa"), p.get("typ_ogniw")) if x) or "—")],
        "bateria": [("Zasięg CLTC", lambda p: f'{p["zasieg"]} km' if p.get("zasieg") else "—"),
                    ("Ogniwa", lambda p: " ".join(x for x in (p.get("ogniwa"), p.get("typ_ogniw")) if x) or "—")],
        "przyspieszenie": [("Moc", lambda p: f'{p["moc"]} KM' if p.get("moc") else "—"),
                           ("V-max", lambda p: f'{p["predkosc"]} km/h' if p.get("predkosc") else "—")],
        "cena": [("Napęd", lambda p: p.get("naped", "—")),
                 ("Zasięg CLTC", lambda p: f'{p["zasieg"]} km' if p.get("zasieg") else "—")],
    }[par]

    wiersze = []
    for p in pozycje:
        wart = f'{p["wartosc"]}'.replace(".", ",")
        dodatki = "".join(f'<td class="aa-rank-klasa">{f(p)}</td>' for _, f in kolumny)
        wiersze.append(
            f'<tr><td class="aa-rank-poz">{p["pozycja"]}</td>'
            f'<td class="aa-rank-auto">{p["marka"]} {p["model"]}'
            f'<span class="aa-rank-klasa-mob">{p.get("naped", "")}</span></td>'
            f'<td class="aa-rank-szt">{wart} {dane["jednostka"]}</td>{dodatki}'
            f'<td class="aa-rank-oferta">{p["blok_kompakt"]}</td></tr>'
        )
    naglowki = "".join(f'<th class="aa-rank-klasa">{n}</th>' for n, _ in kolumny)
    return (
        '<div class="aa-rank-tabela"><table class="aa-rank"><thead><tr>'
        '<th class="aa-rank-poz">#</th><th>Model</th>'
        f'<th class="aa-rank-szt">{dane["etykieta"]}</th>{naglowki}'
        '<th class="aa-rank-oferta">U nas</th>'
        "</tr></thead><tbody>" + "".join(wiersze) + "</tbody></table></div>"
    )


def sekcja_zrodla_spec(dane: dict, wzbogacone: int, d: dict) -> str:
    """Uczciwe postawienie sprawy: ranking parametryczny opisuje modele, które da się sprowadzić,
    a nie cały rynek chiński. Bez tego zdania byłaby to cicha zamiana zakresu."""
    dzis = date.today().strftime("%d.%m.%Y")
    cltc = ("<p><strong>CLTC to chińska norma pomiaru</strong> — łagodniejsza od europejskiego "
            "WLTP mniej więcej o jedną czwartą. Auto z deklarowanym zasięgiem 700 km CLTC "
            "w polskich warunkach realnie przejedzie bliżej 500 km, a zimą mniej. Podajemy "
            "wartości katalogowe, bo tylko one są porównywalne między modelami.</p>"
            if dane.get("parametr") in ("zasieg", "bateria") else "")
    return (
        "<h2>Skąd te dane</h2>"
        f"<p>Parametry pochodzą z kart katalogowych Autohome — chińskiego katalogu, w którym "
        f"producenci publikują dane homologacyjne. Zestawienie obejmuje "
        f"<strong>{dane['modeli_z_danymi']} modeli</strong> z kompletną kartą, czyli te, które "
        f"realnie da się sprowadzić z Chin. To nie jest spis całego rynku chińskiego: modele "
        f"niedostępne w imporcie nie mają tu wpisu, nawet jeśli w Chinach się sprzedają.</p>"
        + cltc +
        f"<p>Każda pozycja to najlepszy wynik wśród wersji danego modelu — stan katalogu "
        f"na {dzis}. Kolumna „U nas\" pokazuje bieżącą dostępność i przelicza się automatycznie.</p>"
    )


def podsumowanie_oferty(pozycje: list) -> str:
    """Jedyne miejsce w treści, które mówi o stanie naszej oferty zbiorczo — i dlatego siedzi
    w bloku odświeżanym automatem. Narracja tych liczb nie dotyka (rotują codziennie)."""
    mamy = [p for p in pozycje if p.get("nasza_oferta")]
    if not mamy:
        return "<!--OFERTA:START:_podsumowanie--><!--OFERTA:END-->"
    ceny = [p["nasza_oferta"]["cena_od"] for p in mamy]
    sztuk = sum(p["nasza_oferta"]["sztuk"] for p in mamy)
    od = f"{min(ceny):,}".replace(",", " ")
    return ("<!--OFERTA:START:_podsumowanie-->"
            f'<p class="aa-rank-podsumowanie">Z tego zestawienia mamy dziś w ofercie '
            f'<strong>{len(mamy)} {"model" if len(mamy) == 1 else "modeli"}</strong> '
            f'({sztuk} {"egzemplarz" if sztuk == 1 else "egz."}), ceny od {od} zł. '
            f'Stan aktualizuje się automatycznie — <a href="/samochody/">zobacz pełną ofertę</a>.</p>'
            "<!--OFERTA:END-->")


def sekcja_zrodla(dane: dict, wzbogacone: int) -> str:
    """Obowiązkowa (3.3) — to jest różnica między nami a przepisywaczami zrzutów z WeChat."""
    dzis = date.today().strftime("%d.%m.%Y")
    return (
        "<h2>Skąd te dane</h2>"
        f"<p>Liczby sprzedaży pochodzą z serwisu Dongchedi (懂车帝), który publikuje miesięczne "
        f"zestawienia rejestracji w Chinach w podziale na modele i klasy nadwozia. Zestawienie "
        f"obejmuje {okres_slownie(dane['zrodlo_data'])} i porównuje je z miesiącem poprzednim "
        f"({okres_slownie(dane['porownanie_z'])}). Z listy wybraliśmy wyłącznie marki chińskie — "
        f"w oryginalnym rankingu są też auta koncernów zagranicznych produkowane w Chinach.</p>"
        f"<p>Kolumna „U nas\" pokazuje stan naszej oferty w dniu {dzis}: {wzbogacone} "
        f"{'model' if wzbogacone == 1 else 'modeli'} z tego zestawienia mamy dostępne. "
        f"Ta kolumna aktualizuje się automatycznie — ranking rynkowy zostaje, dostępność rotuje.</p>"
    )


def sekcja_faq(faq: list) -> str:
    czesci = ["<h2>Najczęstsze pytania</h2>"]
    for f in faq:
        czesci.append(f"<h3>{f['q']}</h3><p>{f['a']}</p>")
    return "".join(czesci)


def dane_dla_modelu_spec(dane: dict, pozycje: list, d: dict) -> str:
    linie = []
    for p in pozycje:
        det = []
        for etyk, klucz, jedn in (("bateria", "bateria", "kWh"), ("zasięg CLTC", "zasieg", "km"),
                                  ("moc", "moc", "KM"), ("V-max", "predkosc", "km/h"),
                                  ("0-100", "przyspieszenie", "s")):
            if p.get(klucz):
                det.append(f"{etyk} {p[klucz]} {jedn}")
        if p.get("ogniwa"):
            det.append(f"ogniwa {p['ogniwa']}")
        if p.get("typ_ogniw"):
            det.append(p["typ_ogniw"])
        linie.append(f"  {p['pozycja']}. {p['marka']} {p['model']} — {p['wartosc']} "
                     f"{dane['jednostka']} ({p.get('naped', '')}); {', '.join(det)}"
                     + ("  | mamy w ofercie" if p.get("nasza_oferta") else ""))
    return f"""TEMAT: {d['tytul']}
KRYTERIUM RANKINGU: {d['kryterium']}
ŹRÓDŁO: karty katalogowe Autohome, {dane['modeli_z_danymi']} modeli z kompletnymi danymi
FRAZA, NA KTÓRĄ PISZEMY: {d['fraza_glowna']}
KONTEKST REDAKCYJNY: {d.get('kontekst', '')}

DANE (jedyne dopuszczalne liczby):
{chr(10).join(linie)}

UWAGA O NORMIE: zasięg podany jest w chińskiej normie CLTC, łagodniejszej od WLTP mniej więcej
o jedną czwartą. Jeśli piszesz o zasięgu, ZAWSZE dopisz „CLTC" i nie przeliczaj na WLTP.
(ile z tych modeli mamy w ofercie — NIE PISZ, to osobny blok, który się przelicza)
"""


def dane_dla_modelu(dane: dict, pozycje: list, d: dict) -> str:
    # Świadomie BEZ liczb z naszej oferty (sztuki, ceny): model, który je zobaczy, wplecie je
    # w narrację, a ta się nie odświeża. Informacja „mamy" wystarcza do komentarza.
    linie = []
    for p in pozycje:
        oferta = " | mamy w ofercie" if p.get("nasza_oferta") else ""
        zm = "nowość w zestawieniu" if p.get("zmiana_proc") is None else f"{p['zmiana_proc']:+d}% m/m"
        linie.append(f"  {p['pozycja']}. {p['marka']} {p['model']} — {p['wartosc']:,} szt., "
                     f"{zm}, klasa: {p['klasa']}{oferta}".replace(",", " "))
    mamy = [p for p in pozycje if p.get("nasza_oferta")]
    return f"""TEMAT: {d['tytul']}
KRYTERIUM RANKINGU: {d['kryterium']}
OKRES: {okres_slownie(dane['zrodlo_data'], dopelniacz=True)}
FRAZA, NA KTÓRĄ PISZEMY: {d['fraza_glowna']}
KONTEKST REDAKCYJNY: {d.get('kontekst', '')}

DANE (jedyne dopuszczalne liczby):
{chr(10).join(linie)}

PODSUMOWANIE:
  pozycji w zestawieniu: {len(pozycje)}
  łączna sprzedaż pozycji z zestawienia: {sum(p['wartosc'] for p in pozycje):,} szt.
  (ile z nich mamy w ofercie — NIE PISZ, to osobny blok, który się przelicza)
""".replace(",", " ")


def liczby(tekst: str) -> set:
    """Liczby z tekstu, z tolerancją na spację i twardą spację jako separator tysięcy."""
    czysty = re.sub(r"<[^>]+>", " ", tekst)
    czysty = czysty.replace(" ", " ").replace(" ", " ")
    czysty = re.sub(r"(?<=\d)[  ](?=\d{3}\b)", "", czysty)
    return {int(x) for x in re.findall(r"\b\d+\b", czysty)}


def bramka_liczb(narracja: str, pozycje: list, dane: dict):
    """3.4 — żadna liczba w narracji nie może być spoza danych. Sprawdzamy wartości ≥1000
    (sprzedaż, ceny), bo to one wprowadzają czytelnika w błąd; mniejsze idą jako ostrzeżenie."""
    dozwolone = {len(pozycje), sum(p["wartosc"] for p in pozycje)}
    for p in pozycje:
        dozwolone |= {p["wartosc"], p["pozycja"]}
        if p.get("poprzedni_miesiac"):
            dozwolone.add(p["poprzedni_miesiac"])
        if p.get("zmiana_proc") is not None:
            dozwolone |= {p["zmiana_proc"], abs(p["zmiana_proc"])}
        if p.get("nasza_oferta"):
            dozwolone |= {p["nasza_oferta"]["sztuk"], p["nasza_oferta"]["cena_od"]}
    # Rankingi parametryczne nie mają miesiąca porównawczego — pole bywa puste.
    dozwolone |= {int(x) for x in (dane.get("zrodlo_data"), dane.get("porownanie_z"))
                  if str(x or "").isdigit()}
    # Wartości parametrów (zasięg, kWh, moc, V-max, cena w 万) też są danymi z tabeli.
    for p in pozycje:
        for klucz in ("bateria", "zasieg", "moc", "predkosc", "przyspieszenie"):
            if p.get(klucz):
                dozwolone.add(int(p[klucz]))
    # Ceny z NASZEJ oferty są celowo poza zbiorem dozwolonych: rotują codziennie, a narracja
    # się nie odświeża. Liczby sztuk zostają dozwolone, bo to wartości jednocyfrowe, które
    # kolidują z pozycjami w rankingu i procentami m/m — łapie je osobna bramka tekstowa.
    for p in pozycje:
        if p.get("nasza_oferta"):
            dozwolone.discard(p["nasza_oferta"]["cena_od"])
    dozwolone |= set(range(2015, date.today().year + 2))    # roczniki
    obce = {n for n in liczby(narracja) if n not in dozwolone}
    return {n for n in obce if n >= 1000}, {n for n in obce if n < 1000}


def zbuduj_tresc(gen: dict, dane: dict, pozycje: list, d: dict) -> str:
    spec = d.get("zrodlo") == "spec"
    okres = okres_slownie(dane["zrodlo_data"])
    mamy = sum(1 for p in pozycje if p.get("nasza_oferta"))
    naglowek = (f"<h2>Ranking: {d.get('naglowek_tabeli', 'zestawienie')} — TOP {len(pozycje)}</h2>"
                if spec else
                (f"<h2>Ranking: {len(pozycje)} najlepiej sprzedających się chińskich SUV-ów</h2>"
                 if d["_nazwa"] == "suv" else f"<h2>Ranking — {okres}</h2>"))
    kryterium = (f"<p class=\"aa-rank-kryterium\">Kryterium: {d['kryterium']}.</p>" if spec else
                 f"<p class=\"aa-rank-kryterium\">Kryterium: {d['kryterium']}. Dane za {okres}.</p>")
    czesci = [
        f"<p class=\"aa-lead\"><strong>{gen['lead']}</strong></p>",
        gen["wstep"],
        naglowek,
        kryterium,
        "<!--RANKING:START-->",
        tabela_specow(pozycje, dane, d) if spec else tabela_rankingu(pozycje, okres),
        "<!--RANKING:END-->",
        podsumowanie_oferty(pozycje),
    ]
    for s in gen["sekcje"]:
        czesci.append(f"<h2>{s['h2']}</h2>{s['html']}")
    # Sekcja źródeł niesie okres i datę stanu oferty, więc przy każdym odświeżeniu musi się
    # przeliczyć razem z danymi — stąd własne znaczniki.
    czesci.append("<!--ZRODLA:START-->" +
                  (sekcja_zrodla_spec(dane, mamy, d) if spec else sekcja_zrodla(dane, mamy)) +
                  "<!--ZRODLA:END-->")
    czesci.append(sekcja_faq(gen["faq"]))
    return "\n".join(czesci)


def okladka(post_id: str, d: dict, dane: dict):
    """Okładka wpisu. Trzy rzeczy są obowiązkowe i nie podlegają negocjacji (reguła Janka,
    2026-08-05): **WebP**, **nazwa pliku z frazą** (nie `cover-123.webp`) i **opisy pod SEO** —
    tytuł, alt i podpis. Zdjęcie z sesji, gdy definicja je wskazuje (`foto`); plansza
    typograficzna tylko jako ostatnia deska ratunku, bo prawdziwe auto bije planszę."""
    okres = okres_slownie(dane["zrodlo_data"])
    nazwa = f"{d['slug']}-{okres.replace(' ', '-')}.webp"
    plik = kb.STATE_DIR / nazwa
    zrodlowe = d.get("foto")

    if zrodlowe and Path(zrodlowe).exists():
        # Kadr 16:9 z fotografii — WebP, bo tak ma być każdy obraz w serwisie.
        import subprocess
        subprocess.run(["magick", zrodlowe, "-resize", "2048x", "-gravity", "center",
                        "-crop", "2048x1152+0+0", "+repage", "-resize", "1200x675",
                        "-unsharp", "0x0.6+0.7+0.02", "-quality", "86", str(plik)],
                       check=True, capture_output=True)
        podpis = d.get("foto_podpis", "fot. Prima-Auto")
        alt = d.get("foto_alt") or f"{d['tytul']} — {okres}"
    else:
        make_cover(d["tytul"], str(plik), "RANKING")
        podpis = "grafika: Prima-Auto"
        alt = f"{d['tytul']} — dane za {okres}"

    kb.wp("media", "import", str(plik), f"--post_id={post_id}", "--featured_image",
          f"--title={d['tytul']} — {okres}", f"--alt={alt}", f"--caption={podpis}",
          f"--desc=Ilustracja do rankingu: {d['tytul'].lower()}, dane za {okres}. "
          f"Prima-Auto — import samochodów z Chin.", "--porcelain")
    plik.unlink(missing_ok=True)
    print(f"  okładka: {nazwa}", flush=True)


def zapisz_draft(d: dict, gen: dict, tresc: str, dane: dict, pozycje: list) -> tuple:
    body = kb.STATE_DIR / "_ranking_body.html"
    kb.STATE_DIR.mkdir(parents=True, exist_ok=True)
    body.write_text(tresc, encoding="utf-8")
    post_id = kb.wp(
        "post", "create", str(body),
        "--post_status=draft", "--post_type=post", "--post_category=rankingi",
        f"--post_author={AUTOR_ID}", f"--post_title={d['tytul']}",
        f"--post_name={d['slug']}", f"--post_excerpt={gen['excerpt']}", "--porcelain",
    ).strip()

    # Dane rankingu zostają przy wpisie — z nich żyje `ranking_refresh.py` (Task 4),
    # bez ponownego odpytywania API przy każdym przeliczeniu dostępności.
    meta = {"definicja": d["_nazwa"], "okres": dane["zrodlo_data"],
            "porownanie_z": dane["porownanie_z"], "zrodlo": dane["zrodlo"],
            "zrodlo_url": dane["zrodlo_url"], "pobrano": dane["pobrano"],
            "pozycje": [{k: p.get(k) for k in
                         ("pozycja", "marka", "model", "model_cn", "podmiot", "nasza_marka",
                          "nasza_serie", "wartosc", "poprzedni_miesiac", "zmiana_proc",
                          "klasa", "klucz")} for p in pozycje]}
    kb.wp("post", "meta", "set", post_id, "_asiaauto_ranking_dane",
          json.dumps(meta, ensure_ascii=False))
    kb.wp("post", "meta", "set", post_id, "_kb_faq_json", json.dumps(gen["faq"], ensure_ascii=False))
    kb.wp("post", "meta", "set", post_id, "rank_math_description", gen["meta_opis"])

    try:
        okladka(post_id, d, dane)
    except Exception as e:
        print(f"  okładka nieudana (nie blokuje): {e}", flush=True)

    url = kb.wp("post", "list", f"--post__in={post_id}", "--post_status=draft", "--field=url").strip()
    return post_id, url


def wystaw_podglad(nazwa: str, tresc: str, tytul: str) -> str:
    """Podgląd do przeczytania jednym kliknięciem, zanim cokolwiek pójdzie na produkcję."""
    plik = f"primaauto-ranking-{nazwa}-{date.today().isoformat()}.html"
    html = (f"<!doctype html><meta charset=utf-8><title>{tytul}</title>"
            "<style>body{max-width:820px;margin:2rem auto;padding:0 1rem;font:16px/1.6 system-ui}"
            "table{border-collapse:collapse;width:100%;font-size:14px}"
            "th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}"
            "th{background:#1B2A4A;color:#fff}.aa-rank-alias{color:#666;font-size:12px}"
            "h2{margin-top:2rem}</style>"
            f"<h1>{tytul}</h1>{tresc}")
    (TMP_PUBLIC / plik).write_text(html, encoding="utf-8")
    return f"https://auratest.pl/fe4f58fec53ctmp/{plik}"


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--ranking", required=True, help="klucz z rankingi.json")
    ap.add_argument("--miesiac", help="RRRRMM; domyślnie ostatni dostępny")
    ap.add_argument("--model", default="sonnet")
    ap.add_argument("--apply", action="store_true", help="zapis draftu na produkcji")
    ap.add_argument("--dry-run", action="store_true", help="tylko podgląd (domyślne)")
    ap.add_argument("--no-mail", action="store_true")
    ap.add_argument("--force", action="store_true", help="publikuj mimo ostrzeżeń bramek")
    a = ap.parse_args()

    d = wczytaj_definicje(a.ranking)
    print(f"=== {d['tytul']}", flush=True)

    if d.get("zrodlo") == "spec":
        print("  dane parametryczne (katalog Autohome)…", flush=True)
        dane = rspec.ranking_parametryczny(d["parametr"], d.get("top", 20))
        if dane["odrzucone_rozjazd"]:
            print(f"    odrzucone przy rozjeździe źródeł: {len(dane['odrzucone_rozjazd'])}", flush=True)
    else:
        print("  dane rynkowe…", flush=True)
        dane = rm.dongchedi_ranking(d["klasy"], a.miesiac, d.get("typ", 11), d.get("naped", ""),
                                    d.get("top"))
    pozycje = dane["ranking"]
    if dane["dziury_w_czolowce"]:
        print("  BŁĄD: pozycje bez nazwy mieszczą się w publikowanej czołówce — ranking byłby "
              "zafałszowany. Uzupełnij ranking_names.json:", flush=True)
        for b in dane["dziury_w_czolowce"]:
            print(f"    {b['marka_cn']} {b['model_cn']} — {b['wartosc']} szt.", flush=True)
        if not a.force:
            raise SystemExit(1)

    print("  nasza dostępność…", flush=True)
    pozycje, trafione = rs.wzbogac(pozycje, d.get("poziom_dopasowania", "model"))
    print(f"    {trafione} z {len(pozycje)} pozycji mamy w ofercie", flush=True)

    print("  narracja…", flush=True)
    wejscie = (dane_dla_modelu_spec(dane, pozycje, d) if d.get("zrodlo") == "spec"
               else dane_dla_modelu(dane, pozycje, d))
    gen = None
    for proba in (1, 2):
        tekst, _ = kb.call_model(GEN_PROMPT, wejscie, model=a.model)
        # Surowa odpowiedź na dysk zawsze — przy dłuższych tekstach model bywa ucinany
        # i bez zapisu nie da się orzec, czy to problem promptu, czy limitu.
        (kb.KB_DIR / "tmp").mkdir(exist_ok=True)
        (kb.KB_DIR / "tmp" / f"ranking-{a.ranking}-raw.txt").write_text(tekst, encoding="utf-8")
        try:
            gen = kb.parse_json_response(tekst)
            break
        except Exception as e:
            print(f"    odpowiedź nie jest JSON-em ({e}); początek: {tekst[:160]!r}", flush=True)
            if proba == 2:
                raise SystemExit("BŁĄD: model dwa razy nie oddał JSON-a")
    if not gen or not all(k in gen for k in ("lead", "wstep", "sekcje", "faq", "excerpt", "meta_opis")):
        raise SystemExit("BŁĄD: niepełna odpowiedź modelu")
    for pole in ("lead", "wstep", "excerpt", "meta_opis"):
        gen[pole] = kb.normalize_quotes(gen[pole])
    for s in gen["sekcje"]:
        s["h2"], s["html"] = kb.normalize_quotes(s["h2"]), kb.normalize_quotes(s["html"])
    gen["faq"] = [{"q": kb.normalize_quotes(f["q"]), "a": kb.normalize_quotes(f["a"])}
                  for f in gen["faq"]]

    narracja = " ".join([gen["lead"], gen["wstep"], gen["excerpt"], gen["meta_opis"]] +
                        [s["h2"] + s["html"] for s in gen["sekcje"]] +
                        [f["q"] + f["a"] for f in gen["faq"]])

    problemy = []
    if not re.search(r"[ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]", narracja):
        problemy.append("brak polskich znaków (regresja T-193)")
    if len(gen["faq"]) != 5:
        problemy.append(f"FAQ ma {len(gen['faq'])} pytań zamiast 5")
    for zle in kb.lint_text(narracja):
        problemy.append(zle)
    proces = [s for s in ("homologacj", "odprawa celna", "port w ", "urząd celn", "tłumacz przysięgł")
              if s in narracja.lower()]
    if proces:
        problemy.append(f"opis procesu importu (T-236): {', '.join(proces)}")
    # Wzmianka „mamy ten model" jest w porządku i zostaje; zestarzeje się dopiero LICZBA —
    # cena od, liczba sztuk, ilu modeli z rankingu mamy. Tego szukamy wzorcem, nie frazą,
    # bo pierwsza wersja bramki wywalała zdanie „modele X i Y są dostępne w naszej ofercie",
    # które jest trwałe i wartościowe.
    czysta = re.sub(r"<[^>]+>", " ", narracja).lower()
    wzorce_stanu = [
        r"\bod\s+\d[\d\s ]{2,}\s*zł",                              # „od 240 000 zł"
        r"\b\d+\s*(?:szt\.?|sztuk|sztuki|egzemplarz\w*)\s+(?:od|w\s+ofercie|dostępn)",
        r"\b(?:w|po)\s+\d+\s*(?:szt\.?|egzemplarz\w*)",             # „w 55 egzemplarzach"
        r"\b(?:\d+|jeden|dwa|trzy|cztery|pięć|sześć|siedem|osiem|dziewięć|dziesięć|jedenaście|"
        r"dwanaście|trzynaście|czternaście|piętnaście|szesnaście|siedemnaście|osiemnaście|"
        r"dziewiętnaście|dwadzieścia)\s+(?:z\s+\S+\s+)?(?:modeli|pozycji|aut)\s+"
        r"(?:mamy|jest\s+dostępn|z\s+rankingu\s+mamy)",
        r"\b(?:mamy|posiadamy)\s+(?:w\s+ofercie\s+)?\d+\s",
    ]
    stan = [w for w in wzorce_stanu if re.search(w, czysta)]
    if stan:
        problemy.append(f"narracja podaje liczby z naszej oferty (rotują, zestarzeją się): "
                        f"{len(stan)} dopasowań wzorca")
    duze, male = bramka_liczb(narracja, pozycje, dane)
    if duze:
        problemy.append(f"liczby spoza danych: {sorted(duze)}")
    if male:
        print(f"  uwaga: drobne liczby spoza danych (sprawdź kontekst): {sorted(male)}", flush=True)

    tresc = zbuduj_tresc(gen, dane, pozycje, d)
    for znacznik in ("<!--RANKING:START-->", "<!--RANKING:END-->", "<!--OFERTA:START:"):
        if znacznik not in tresc:
            problemy.append(f"brak znacznika {znacznik}")

    if problemy:
        print("\n  BRAMKI:", flush=True)
        for p in problemy:
            print(f"    ✗ {p}", flush=True)
        if not a.force:
            print("\n  Wstrzymane. Popraw prompt/dane albo uruchom z --force.", flush=True)
    else:
        print("  bramki: OK", flush=True)

    podglad = wystaw_podglad(a.ranking, tresc, d["tytul"])
    print(f"\n  podgląd: {podglad}", flush=True)
    out = kb.KB_DIR / "tmp" / f"ranking-{a.ranking}-{dane['zrodlo_data']}.json"
    out.parent.mkdir(exist_ok=True)
    out.write_text(json.dumps({"definicja": d, "dane": dane, "generacja": gen},
                              ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"  dane: {out}", flush=True)

    if a.apply and (not problemy or a.force):
        post_id, url = zapisz_draft(d, gen, tresc, dane, pozycje)
        print(f"  draft #{post_id}: {url}", flush=True)
        if not a.no_mail:
            kb.send_mail(
                f"Prima-Auto: draft rankingu — {d['tytul']}",
                f"<p>Draft rankingu do przejrzenia (nie jest opublikowany).</p>"
                f"<p><a href=\"{url}\">{d['tytul']}</a> · <a href=\"{podglad}\">podgląd treści</a></p>"
                f"<p>Dane: {okres_slownie(dane['zrodlo_data'])}, {len(pozycje)} pozycji, "
                f"{trafione} mamy w ofercie.</p>")
            print("  mail wysłany", flush=True)
    elif a.apply:
        print("  NIE zapisano — bramki nieprzepuszczone.", flush=True)


if __name__ == "__main__":
    main()
