# ADR 2026-06-14 — Kampania CTR-boost TRACK 1: chirurgiczny retune tytułów 6 hubów

> Status: WDROŻONE (DB termmeta, bez zmian w kodzie). Task: T-163 (TRACK 1).

## Kontekst

Prompt `tmp/PROMPT-ctr-boost-2026-06-14.md` zakładał systemową lukę CTR na hubach
(baseline „poz. 8,8 / CTR 3,40%", scope 30-50 hubów) i 3 tracki: (1) title/meta refresh,
(2) Vehicle rich results + dateModified, (3) Review/AggregateRating (gwiazdki).

Prompt nakazywał KROK 0 — twardą weryfikację stanu, bo baseline mógł być nieaktualny po
reworkach (05-30 hub modeli, 06-08 hub marek, 06-13 fresh hubs fala 1).

## Co wykazał KROK 0 (stan faktyczny ≠ prompt)

**GSC 28 dni (2026-05-15..06-11), skrypt `tmp/gsc-hub-ctr-gap-2026-06-14.py`):**

- **Hub modelu (agregat):** 345 stron, śr. poz **6,9** / CTR **3,66%** — przy poz 6,9 krzywa
  CTR daje ~3,5%, czyli **na krzywej**. Luka systemowa z promptu jest w większości ZAMKNIĘTA.
- **Hub marki (agregat):** poz 10,1 / CTR 3,01% — większość marek **powyżej** krzywej
  (zeekr 10%, changan 3,7% vs 0,5% oczek., byd 2,6% vs 1,7%, aito 3,8% vs 2,6%). Rework 06-08
  zadziałał → **zero eksploatowalnej luki na hubach marki**.
- Realna luka = **punktowa, ~6 hubów modeli** z dużymi impresjami i CTR poniżej krzywej
  (sort wg impresje×luka): chery/arrizo-8 (3251 imp), zeekr/9x (3715), zeekr/8x (2747),
  denza/n9-dm-i (768), byd/leopard-5 (2235), byd/leopard-7 (1491).

**Frazy (per hub):** dominuje czysty intent cenowy — „{model} **cena**", „cena w polsce",
„ile kosztuje". Tytuły miały „od X PLN" ale **nie zawierały słowa „cena"**, którego ludzie
wpisują (Google pogrubia dopasowanie w tytule → wyższy CTR).

**TRACK 2 — już wdrożony** (potwierdzone curl JSON-LD live): Product+AggregateOffer (lowPrice
PLN), Car+vehicleConfiguration, FAQPage, ItemList, breadcrumbs — bez duplikatu Car. `dateModified`
emituje na hubach marki, ale NIE na hubach modelu (facts['updated'] pusty). Rozjazd wersji:
komentarz „v0.32.74" w `class-asiaauto-seo.php` vs plugin 0.32.73. → reszta do T-168.

**TRACK 3 — zablokowany:** AggregateRating nieobecny, brak danych ocen od Ruslana. → T-169.

## Decyzja

1. **Zakres TRACK 1 zredukowany z 30-50 do 6 hubów** (data-driven, [[feedback_no_decisions_without_data]]).
   Powód: większość hubów na/powyżej krzywej; systemowa zmiana wzoru generatora ryzykowałaby
   regresję „winnerów" + hubów marki. Po pomiarze (T-165) ew. rollout.
2. **Format tytułu — front-load „cena w Polsce"** (decyzja Janka), brand na końcu (i tak ucinany):
   `{Model} cena w Polsce 2026 — od {min} PLN | Prima-Auto`. Dla leopard-5 zachowany alias
   „(Denza B5)" (108+63 imp na „denza b5"), „2026" pominięte ze względu na długość.
   „PHEV" usunięte z tytułów 8x/leopard-7 (nie w zapytaniach; wyświetlana nazwa modelu zostaje).
3. **Mechanizm:** `update_term_meta` na `rank_math_title` + `rank_math_description` +
   `asiaauto_seo_desc`, oraz `_asiaauto_skip_title_regen=1` (daily cron `hub-title-generator`
   nie nadpisze). **Trade-off:** cena/liczba sztuk zamrożona na 2026-06-14 — przy zmianie stocku
   nie zaktualizuje się automatycznie. Akceptowalne dla 6 hubów; po pomiarze flaga do zdjęcia.
4. **Description** answer-first ≤155 zn. (cena + stan + USP + CTA, diakrytyki zachowane).

## Hubs (term_id) — wdrożone wartości

| slug | tid | title |
|---|---|---|
| arrizo-8 | 3581 | Chery Arrizo 8 cena w Polsce 2026 — od 112 000 PLN \| Prima-Auto |
| 9x | 4824 | Zeekr 9X cena w Polsce 2026 — od 430 000 PLN \| Prima-Auto |
| 8x | 6569 | Zeekr 8X cena w Polsce 2026 — od 420 000 PLN \| Prima-Auto |
| n9-dm-i | 4656 | Denza N9 DM-i cena w Polsce 2026 — od 255 000 PLN \| Prima-Auto |
| leopard-5 | 5523 | BYD Leopard 5 (Denza B5) — cena w Polsce od 189 000 PLN \| Prima-Auto |
| leopard-7 | 6066 | BYD Leopard 7 cena w Polsce 2026 — od 187 000 PLN \| Prima-Auto |

Indexing API: 6/6 OK przez `~/bin/index-submit` (13/100 budżetu ad-hoc), URL w
`tmp/ctr-boost-retune-urls-2026-06-14.txt`.

## Konsekwencje / pomiar

- **T-165** — pomiar CTR ~2026-07-05. Gate `tmp/gsc-inspect.py` lastCrawl≥2026-06-14 PRZED
  odczytem (recrawl≠reindeks). Baseline w `tmp/gsc-hub-ctr-gap-2026-06-14.csv`.
- Sukces (CTR↑ przy stałej pozycji) → rozważyć rollout / systemowy hook „cena" w generatorze.
- Flop → zdjąć `_asiaauto_skip_title_regen` (wróci wzór generatora z auto-ceną).

## Obserwacje na osobne taski

- **T-166** arrizo-8: alias „Arrizo S" (eksportowa nazwa, 1290+162 imp) w treści/H1.
- **T-167** zeekr/8x: „zeekr 8x cena" (808 imp) na poz 10,8 = problem pozycji, nie snippetu.
- **T-168** TRACK 2: bump 0.32.74 + dateModified na hubach modelu (niski prio CTR).
- **T-169** TRACK 3: gwiazdki — PRE-REQ realne oceny od Ruslana (task dla Janka).
