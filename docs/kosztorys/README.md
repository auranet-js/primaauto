# Kosztorys / historia inwestycji Prima Auto

Odświeżalny raport dla Ruslana: ile godzin (rynkowo i realnie) pochłonął projekt
w podziale na etapy + wycena roadmapy + koszty zewnętrzne.

## Struktura

- `dane/meta.json` — timeline, liczby projektu, metodologia dwóch kolumn godzin
- `dane/etap1.json` — bloki budowy platformy do prezentacji 08.04.2026 (12 bloków)
- `dane/etap2.json` — chronologiczne taski od bootstrapu repo do dziś (~55 pozycji)
- `dane/etap3.json` — roadmapa z widełkami godzin (20 pozycji)
- `dane/koszty.json` — koszty zewnętrzne (feed auto-api, hosting, AI, narzędzia)
- `build.py` — generator HTML (stdlib only)
- `raport.html` — wygenerowany raport (commitowalny snapshot)

## Odświeżanie (kolejne sesje)

1. Dopisz nowe pozycje do `dane/etap2.json` (nowe prace) / zaktualizuj `etap3.json`
   (zrobione pozycje roadmapy przenieś do etapu 2) / `koszty.json` (nowe faktury)
   / `meta.json` (liczby projektu: listings, zamówienia, wydania).
2. `python3 build.py --deploy`

URL raportu (zahaszowany, niepubliczny — bez linkowania):
https://auratest.pl/pa-kosztorys-3ee728365b3d1a5b895e/

## Metodologia godzin (decyzja Janka 2026-07-03)

Dwie kolumny: **rynkowo** (ile zajęłoby klasycznemu zespołowi bez AI — miara
wartości pracy) i **realnie** (faktyczny czas zespołu Auranet z warsztatem AI).

Kalibracja kolumny realnej: baza czasowa — czerwiec 2026 zmierzony z transkryptów
sesji (aktywne okna 10-min ≈ 63 h + praca poza terminalem ≈ 72 h), kwiecień/maj
proporcjonalnie do gęstości commitów (84/42 commity → ~94/~66 h), era pre-repo
(16.03–15.04, budowa na claude.ai web) ≈ 180 h. **Baza × 2** (decyzja Janka
2026-07-04): praca szła dwutorowo — godziny Janka (prowadzenie, decyzje, testy)
i godziny zespołu AI (wykonanie) liczone osobno. Etap 1 rynkowo = suma estymat
per blok funkcjonalny (klasy/linie kodu wg `docs/architektura/plugin-map.md`).

Źródła ekstrakcji (2026-07-03): git log (174 commity), VERSIONS.md (90 wydań),
35 ADR-ów, docs/seo/, QUEUE.md, memory projektu, baza produkcyjna (daty), historia
korespondencji auto-api. Surowe ekstrakty subagentów w scratchpadzie sesji.
