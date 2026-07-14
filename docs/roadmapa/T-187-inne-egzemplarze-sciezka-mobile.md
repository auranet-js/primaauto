# T-187 — Inne egzemplarze modelu na ofercie + ścieżka do huba modelu na mobile

> Status: **gotowy do odpalenia** (zero blokerów) · Rozmiar: M
> Godziny realnie: **12–16 h** (Janek ~2–3 h, AI ~10–13 h) · Rynkowo: 32–40 h
> Zwiad: 2026-07-14. Poprzednia estymata 17–20 h — obniżona, bo komponent karty i CSS już istnieją.

## Po co

Dwie dziury naraz:
1. **Konwersja** — klient na ofercie nie widzi, że ten sam model stoi u nas taniej w innej sztuce. Musi wrócić do katalogu i szukać ręcznie. **97,8% ofert (2990 z 3056) ma co najmniej jeden inny egzemplarz tego samego modelu** — czyli blok będzie się renderował prawie zawsze.
2. **SEO/struktura** — domyka hierarchię model↔egzemplarz zaczętą w T-203. Konkurencja (tntcars.pl) zbiera ruch na frazach wersyjnych, bo ma tę hierarchię jawną.

## Stan faktyczny (zweryfikowany w kodzie)

| Element | Gdzie |
|---|---|
| Render oferty | `plugins/asiaauto-sync/includes/class-asiaauto-single.php` → `render()`, linie 58–106 |
| Template | `themes/primaauto2026/single-listings.php` (cienki wrapper, shortcode `[asiaauto_single]`) |
| Hub modelu | `themes/primaauto2026/taxonomy-serie.php` |
| **Gotowa karta oferty** | `class-asiaauto-brand-hub.php::renderListingsCompact()`, linie 277–388 |
| CSS karty | `themes/primaauto2026/assets/css/hub.css:161-270` — **ładowany globalnie** (`functions.php:32`), zadziała na ofercie bez dopisywania stylów |
| Istniejący „related" | `class-asiaauto-single.php:505-543` — `relatedModels()` = linki do **innych modeli marki**, NIE do egzemplarzy |
| Breadcrumb ukryty na mobile | `assets/css/asiaauto-single.css:209` → `.aa-single .aa-breadcrumb { display:none }` w `@media (max-width:768px)` |
| Mobilna strzałka „←" | `class-asiaauto-single.php:354` — prowadzi do `/samochody/`, **nie do modelu** |

Wiązanie oferta→model: taksonomia `serie` (2699 termów), term `serie` ma `parent` = term_id marki. Licznik egzemplarzy dostępny bez zapytania: `$serie->count`.

## Plan (kroki)

1. **Wyekstrahować kartę oferty do publicznego helpera.** Dziś ten sam markup jest zduplikowany w 3 miejscach (`brand-hub.php:357`, `homepage.php:216`, `inventory.php:934`). Nowy statyczny helper `AsiaAuto_Brand_Hub::renderCard(int $post_id, array $opts)` → wołany z hubów i z oferty. *Addytywnie — istniejące metody zostają, tylko delegują.*
2. **Nowa metoda `otherUnits()` w `AsiaAuto_Single`** — WP_Query po `serie` bieżącej oferty, `post__not_in => [$pid]`, sort po cenie rosnąco, limit 4. Guard: `serie->count <= 1` → nie renderuj (66 ofert-unikatów).
3. **Wpiąć w `render()`** między linię 94 a 95 (po wyposażeniu, przed „Inne modele marki").
4. **Nagłówek bloku = ścieżka do huba:** „Inne egzemplarze {Model} ({n})" + CTA „Zobacz wszystkie ceny i wersje {Model} →" linkujące do `get_term_link($serie)`. **To załatwia mobile'a** — link jest w treści, nie w breadcrumbie.
5. **Mobilna strzałka „←"** (`class-asiaauto-single.php:354`) — przepiąć z `/samochody/` na hub modelu. Fallback na `/samochody/` gdy brak termu.
6. **Blok wersji na hubie modelu** (`taxonomy-serie.php`) — lista wersji/komplektacji z linkiem do najtańszego egzemplarza każdej.

## Strefy kruche

- `class-asiaauto-single.php` — **nie ruszamy** `renderMeta()`, JSON-LD (`:884-926`), galerii ani `keySpecs()`. Dokładamy jedną linię w `render()` i jedną nową metodę.
- Refaktor karty (krok 1) dotyka 3 miejsc na produkcji → **wymaga zgody przed startem** i osobnego commita (bit-for-bit ten sam HTML).

## Testy

**Automatyczne**
- `php -l` na zmienionych plikach.
- Skrypt WP-CLI: dla 20 losowych ofert (w tym unikat, model z 2 egz., model z 100+ egz.) sprawdź: blok się renderuje / nie renderuje, `post__not_in` działa (oferta nie widzi samej siebie), liczba kart ≤ 4.
- Diff HTML karty przed/po refaktorze na 5 hubach — musi być identyczny.

**Półautomatyczne**
- Zapytanie SQL: rozkład `serie.count` → potwierdź, że guard łapie dokładnie 66 unikatów.
- Lighthouse/PSI na 1 ofercie przed/po (blok dokłada 4 obrazki — sprawdź CLS i LCP).

**MCP (Chrome)**
- Oferta modelu z wieloma egzemplarzami: desktop + mobile (375px) — screenshot bloku, klik w kartę → czy prowadzi do właściwej oferty; klik w CTA → czy prowadzi na hub modelu.
- Mobile: klik strzałki „←" → czy ląduje na hubie modelu (dziś: `/samochody/`).
- Oferta-unikat → blok nieobecny, brak pustej ramki.

## Definicja zrobionego

- Na 97,8% ofert widać blok „Inne egzemplarze {Model}" z kartami posortowanymi od najtańszej.
- Na mobile istnieją **dwie** drogi do huba modelu (CTA w bloku + strzałka w sticky headerze).
- Karta oferty ma jedno źródło prawdy w kodzie (koniec potrójnej duplikacji).
- Zero regresji w HTML kart na hubach i homepage.
