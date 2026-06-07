# ADR 2026-06-07 — Rework SEO strony głównej (treść topiczna)

> Status: WDROŻONE v0.32.71. Track: SEO strony głównej (osobny od hubów/Ads).
> Powiązane: `project_homepage_seo_topic_2026_06_07`, `feedback_homepage_seo_separate_track`,
> `project_b1_cannibal_import_z_chin_2026_06_06` (poprzednik — title homepage).

## Kontekst

B1 (06-06) zmienił tylko `rank_math_title` homepage; treść (render `class-asiaauto-homepage.php`)
pozostała czystym katalogiem bez prozy semantycznej. Strona główna rankowała słabo na head termy
mimo B1. Zadanie: dołożyć treść pod head termy, nie ruszając transakcyjnego rdzenia (wyszukiwarka,
stocki — homepage to główny konwerter wg GA4).

## Kluczowa decyzja procesowa

**Audyt i decyzje SEO strony głównej = osobny track**, z własnym dedykowanym researchem KW
(DataForSEO), nie wyprowadzany z pamięci B1/GSC. Powód: w tej sesji początkowo proponowano copy
z głowy + starych danych GSC, bez DFS — Janek to zatrzymał. Reguła w `feedback_homepage_seo_separate_track`.

## Dane (DataForSEO, PL 2616/pl, 2026-06-07)

Research przewartościował topic. Head termy (vol/mc):
- `chińskie samochody` **22 200** ⭐ — dominujący, homepage prawie go nie używał jako frazy
- `chińskie samochody elektryczne` 1300 · `chińskie auta elektryczne` 590
- `import aut z chin` 260 (head B1) · `auta z chin import` 210 · `samochody elektryczne z chin` 170
- `nowe/używane auta z chin` 140/140 (stock = używane) · `import samochodów z chin` 140
- `tanie auta z chin` 90 · `auta z chin ceny` 70 · `gdzie kupić auta z chin` 50

## Decyzja treściowa (Janek)

**Import = główny intent** (transakcyjny, zgodny z biznesem — importują, nie marketplace;
`chińskie samochody` 22k zbyt szerokie/konkurencyjne-media i rozjechane z konwersją). Pozostałe
frazy (`chińskie samochody [elektryczne]`, używane/nowe/tanie, ceny, gdzie kupić) — **pokrycie
wtórne**, rozłożone w H2/lead/opisach/FAQ. **H1 nietknięte** (nie rozrywać exact-match wstawką „i aut").

## Wdrożone (v0.32.71)

| Element | Zmiana | Fraza docelowa |
|---|---|---|
| H1 | bez zmian | `import …z chin` (główny) |
| hero-sub | „import chińskich samochodów…" | `chińskie samochody` (22 200) |
| Lead (nowy) | answer-first pod hero | chińskie samochody + używane + cena |
| H2 Stock | „Chińskie samochody dostępne od ręki…" | head + dostępność |
| H2 Latest +opis | „Najnowsze auta z Chin do sprowadzenia" | auta z chin import (210) |
| H2 Makes +opis | „Marki chińskich samochodów do importu" | chińskich samochodów + import |
| H2 BodyTypes +opis | „Chińskie samochody według typu nadwozia" | head + nadwozie |
| H2 FuelTypes +opis | „Chińskie samochody elektryczne, hybrydy i spalinowe" | `chińskie samochody elektryczne` (1300) exact |
| H2 USP | „Dlaczego import aut z Chin z Prima-Auto?" | import aut z chin (260) + fix NAP |
| H2 W cenie +sub | „Co zawiera cena importu auta z Chin" | auta z chin ceny (70) |
| Sekcja 9 (nowa) | proza + 5×FAQ (FAQPage) | exact `import aut z chin` + dedup |

## Dedup FAQ

Strony info = 0 FAQ/FAQPage (czysta proza). Huby = FAQPage encjowy (440 termów: „Ile trwa
sprowadzenie {model}", „Ile kosztuje {model}", „Czy {model} używany"). Homepage FAQ = markowo-
transakcyjny, ogólny, omija generyczne wersje pytań hubów oraz tematy stron info
(homologacja/proces/koszt/finansowanie) — te linkowane z prozy sekcji 9.

## Czego NIE ruszono

H1, wyszukiwarka, grid logika, schema AutoDealer/WebSite (NAP już „Prima-Auto"), kolejność sekcji,
slugi `asiaauto-*`. Strony informacyjne (mapa KW) + llms.txt = osobny kolejny task (poza scope).

## Pomiar

GSC recrawl ~2026-06-20. Sprawdzić: czy homepage podnosi pozycje na `chińskie samochody` /
`chińskie samochody elektryczne` / `import …z chin`, czy FAQPage łapie rich results.
