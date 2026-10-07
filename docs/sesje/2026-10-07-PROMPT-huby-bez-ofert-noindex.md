# PROMPT — huby bez ofert wypadają z indeksu (start nowego wątku, 07.10)

> Zgłoszenie: Janek 07.10, przy okazji Audi. Oczekiwanie: **hub, który chwilowo nie ma ofert, NIE może wypadać z indeksu.**
> Najpierw pomiar i diagnoza, potem propozycja. Bez zmian na produkcji przed akceptem.

## Mechanizm (sprawdzony 07.10)

- RankMath: `rank-math-options-titles.noindex_empty_taxonomies = on` → **każdy hub marki (`make`) i modelu (`serie`) z `term_taxonomy.count = 0` dostaje automatycznie `noindex`**. Nikt tego nie ustawiał per hub.
- Na Audi wyszło przypadkiem: 16 ofert w drafcie → count 0 → `/samochody/audi/`, E5 Sportback, E7X mają `noindex`.
- `count` liczy tylko oferty `publish` przypięte bezpośrednio do termu. Zjawisko z lipca (`class-asiaauto-seo.php`, docblock ok. l. 437): hub agreguje warianty (np. `CS55 Plus PHEV`), więc **count bywa 0 mimo realnego towaru** — wtedy wypadło 88 hubów z ofertami.
- Jedyny wyjątek dziś: `asiaauto_hub_index_whitelist` (5 term_id, huby premierowe z 07.09), działa **tylko dla `serie`**, nie dla `make`.
- Skala termów z count=0 (07.10): `make` 241 z 296, `serie` 2 340 z 2 613 — **większość to termy-widma** (nigdy nie miały oferty), więc „zdjąć regułę RankMath” = wpuścić śmieci.
- Sitemapa: RankMath wycina count=0 już w query (`hide_empty`); `tax_serie_include_empty` próbowane 18.07 i cofnięte (sitemapa 297 → 63, N+1).

## Historia — przeczytaj przed propozycją

- `class-asiaauto-seo.php`: `filterRankMathRobots()`, `termQualifiesForIndex()` (NIEUŻYWANE od 18.07 — kryterium „wiki ≥500 LUB spec ≥200” wpuściło 67 termów-widm), komentarz o sitemapie.
- `docs/VERSIONS.md` — wpis z 18.07 (szukaj `termQualifiesForIndex`).
- Docblock wskazuje poprawne kryterium: **(1) historia ofert, (2) dane techniczne (`_asiaauto_spec_snapshot`), (3) dedupe wariantów** (Tiggo 8 vs 8 Pro / 8 PLUS).
- `docs/sesje/2026-09-07-huby-premierowe-stelato-aistaland.md` + memory `project_huby_premierowe_i_stelato_2026_09_07`, `project_hub_taxonomy_decisions_2026_07_07`, `project_t019_taksonomia_merge_2026_06_19`.
- `docs/seo/recheck-2026-10-04.md` — ostatni stan SEO.

## Krok 1 — pomiar (tylko odczyt)

1. Lista hubów (`make` + `serie`) z `noindex` z powodu count=0, które **realnie mają wartość**:
   - mają lub miały oferty — uwaga: `term_relationships` po rotacji (draft 48 h → trash 7 dni → kasacja) traci historię; sprawdź, czy jest inne źródło (log indexingu `~/.claude/indexing-submit.log`, GSC, meta na termie, ranking/wiki),
   - **ruch w GSC** (wyświetlenia/kliknięcia 90 dni) — GSC = prawda (memory `reference_seo_measurement_gsc_truth_dfs_semrush_lag`),
   - stan w indeksie (URL Inspection / lastCrawl — memory `reference_indexing_audit_via_gsc_lastcrawl`).
2. Osobno: huby z count=0, które **renderują oferty** (wariant pod innym termem) — to błąd liczenia, nie brak towaru.
3. Ile z nich wypadło od kiedy (porównanie z poprzednimi recheckami).

Wynik: tabela hub | typ | oferty dziś (render) | count | GSC 90 dni | indeks | wniosek.

## Krok 2 — propozycja (do akceptu Janka)

Kierunek do oceny, nie przesądzony: własna reguła „hub zostaje w indeksie, jeśli kiedykolwiek miał ofertę + ma dane techniczne + nie jest duplikatem wariantu”, zamiast globalnego `noindex_empty_taxonomies`; plus obsługa `make`. Uwzględnij:
- **marki wyłączone świadomie** (Audi, VW po piśmie Bird & Bird — `HUB_INDEX_BLOCKED_MAKES`) — mają zostać `noindex`, zapytaj Janka o listę,
- sitemapę (osobny problem, N+1),
- zgłoszenie odblokowanych do Google tylko przez `~/bin/index-submit` z budżetem (globalny CLAUDE.md §10a).

## Zasady

- Strefa: SEO/robots, nie kruche strefy z CLAUDE.md, ale **filtr robots dotyka wszystkich hubów** — dry-run na liście przed wdrożeniem, porównanie przed/po.
- Nie zmieniać ustawień RankMath bez akceptu.
- Raport → auratest + `docs/seo/2026-10-07-huby-bez-ofert-noindex.md`.
