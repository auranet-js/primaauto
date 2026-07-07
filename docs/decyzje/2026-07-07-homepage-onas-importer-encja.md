# ADR 2026-07-07 — encja „importer" na homepage (hero-sub) + /informacje/o-nas/

## Kontekst

Okresowy audyt SEO/widoczności (2026-07-07). GSC 28d: kliki 1 563 → 3 347 (+114 %), Organic
Search wyprzedził Paid jako kanał #1 (GA4). Przy okazji audytu Ads: fraza **„importer samochodów
z chin"** = najlepiej konwertujący topic-keyword (4 konwersje / 30 dni), a **„import aut z chin"**
pożera budżet (148 zł / 80 klików) przy 0 konwersji bezpośrednich → potwierdzenie, że „importer"
(encja, intencja zakupowa) konwertuje, „import" (czynność) ciągnie ruch informacyjny.

Analiza on-page: słowo **„importer" padało 0 razy** w renderze homepage + klasie SEO oraz na
/informacje/o-nas/. SERP (DataForSEO, PL):
- `importer aut z chin`: my dopiero **#10** (homepage), a nasz **Facebook #4**; konkurencja (vibecar,
  west-motors, superauto, byd-garcarek, autoklasa, samochodyzchin) w topie.
- `importer samochodów z chin`: **naszej domeny brak w top 10**, rankuje nasz Facebook #2.

Wolumen „importer…" jest niski (DFS: poniżej progu), ale to keyword snajperski (niski wolumen /
wysoka intencja) i **oddajemy własny brand-signal Facebookowi** zamiast łapać go na swojej domenie.

## Decyzja

Dodać encję „importer samochodów z Chin" **addytywnie**, bez ruszania działającego „import":
1. **Homepage** — jedynie hero-sub (podtytuł pod H1). H1 („Import samochodów z Chin do Polski"),
   RankMath title, brand i FAQPage **nietknięte**. Miejsce hero-sub wybrane, bo: (a) góra strony,
   (b) przy nazwie brandu, (c) homepage = landing kampanii Ads → wzmacnia Quality Score na
   konwertującej frazie „importer".
2. **/informacje/o-nas/** — semantyczny dom encji (About page). Intro + nowy H2 + meta description.

Świadomie **NIE** ruszamy: H1/title homepage (recent de-kanibalizacja „import aut z chin", B1 06-06),
9×H2 homepage (już pokrywają „chińskie auta/samochody"), model prawny (pośrednictwo/agencyjny —
„importer" użyte jako opis funkcjonalny + „model agencyjny / na Twoją rzecz", bez sprzeczności).
Frazy informacyjne „chińskie samochody" (6 600/mc) — pozostają celem wtórnym (decyzja z 06-07),
nie inwestujemy (ruch top-funnel, dominują producenci/Otomoto/Wikipedia, słaba konwersja).

## Zmiany (v0.33.15)

- `class-asiaauto-homepage.php` (backup `.bak-2026-07-07-importer`): hero-sub
  „import chińskich samochodów" → „**bezpośredni importer samochodów z Chin**".
- `/informacje/o-nas/` (post 198480, backup treści+meta w `tmp/o-nas-*-bak-2026-07-07.*`):
  intro prowadzi z „bezpośredni importer samochodów z Chin"; nowy **H2 „Bezpośredni importer
  samochodów z Chin"** (naprawia też lukę H1→H3); `rank_math_description` z encją. „importer" 0 → 6×.
- Smoke: homepage HTTP 200 (H1/title/FAQPage bez zmian), o-nas HTTP 200 (bloki Gutenberga OK).

## Pomiar

GSC ~2–3 tyg (koniec 07.2026): czy nasza domena wchodzi w SERP „importer aut/samochodów z chin"
i przejmuje pozycję od naszego Facebooka. Baseline: `importer aut z chin` #10 (my) / #4 (FB);
`importer samochodów z chin` brak top 10 (my) / #2 (FB). Wtórnie: QS kampanii Topic/DSA (landing
= homepage) na keywordach „importer".

Raport audytu: `tmp/seo-audyt-2026-07-07.md`. Skrypty: `tmp/gsc-audit-2026-07-07.py`,
`tmp/gads-convert-audit-2026-07-07.py`.
