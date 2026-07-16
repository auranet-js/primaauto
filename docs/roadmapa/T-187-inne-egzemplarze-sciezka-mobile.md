# T-187 — Inne egzemplarze modelu na ofercie + ścieżka do huba modelu na mobile

> Status: **gotowy do odpalenia** · Rozmiar: M · **wymaga T-212** (wspólny komponent karty)
> Godziny realnie: **9–12 h** (Janek ~2 h, AI ~7–10 h) · Rynkowo: 24–30 h
> Korekta 2026-07-14 (uwaga Janka): **obniżone z 12–16 h**, bo refaktor karty (3–4 h) został wydzielony do **T-212** — służy też T-114, T-189 i T-115, więc nie może obciążać tylko tego zadania.

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

## Ustalenia z sesji 2026-07-16 (T-203) — WSAD DO PLANU, nie odkrywać od nowa

> Kontekst: `docs/sesje/2026-07-16-t203-oferty-linkowanie-do-hubow.md`.
> **Część zadania już odpadła** — ścieżka oferta→hub na mobile ZROBIONA w v0.33.23-26
> (sticky navrow „← {model}" + `#oferty`). Zostają **kafle innych egzemplarzy tego samego modelu**.
> ⚠️ Nie mylić z istniejącym `relatedModels()` = „Inne modele {marka}" (pills do hubów INNYCH modeli).
> Kafli egzemplarzy tego samego modelu **dziś NIE MA w ogóle** — to jest puste miejsce, nie przeniesienie.

### Jest co pokazywać (SQL 2026-07-16)
**2 526 z 3 058 ofert (83%) dzieli identyczny `post_title`** z inną ofertą — 499 grup.
Top: AITO M9 2024 EREV Ultra 6-Seater **×49**, Voyah Dream PHEV ×39, NIO ET5 Touring ×30,
Xiaomi YU7 4WD Max ×30. Cel Janka: **„żeby ludzie widzieli, że to nie jedyny egzemplarz, jaki mamy"**.

### Zmierzony layout (oferta z pełnym extra_prep, `/oferta/denza-n8l-dm-2026-387849/`)
Strona **7 690 px** / 257 tys. znaków HTML:

| blok | pozycja | % |
|---|---|---|
| galeria | 202 px | 3% |
| keySpecs | 919 px | 12% |
| USP | 1 055 px | 14% |
| opis (`post_content`, ~2 200 zn) | 1 385 px | 18% |
| dane techniczne (start) | 2 153 px | 28% |
| **wyposażenie (start)** | 4 541 px | 59% |
| „Inne modele marki" | **7 065 px** | **92%** |

**Samo wyposażenie to 120 tys. znaków = 47% HTML.** Cokolwiek wsadzimy za nim, jest martwe.

### Warianty umiejscowienia (mockupy)
`auratest:primaauto-t203-mockup-mobile-v2-2026-07-16.html` (W1–W4) oraz `…-mockup-mobile-2026-07-16.html`.

- **W1** tylko link do hubu, kafle nisko — najbezpieczniejszy SEO, najsłabszy UX.
- **W2** split: linia „Mamy 3 inne sztuki {model}" wysoko + kafle nisko.
- **W3** pełen box po keySpecs (~14%) — najlepszy UX.
- **W4** box po opisie, przed techniką (~28%).
- **Pomysł Janka (2026-07-16): wcisnąć MIĘDZY sekcje techniczne** (np. po „Silnik", ≈39%) — logika:
  „klient mobile już wie, że przewija zestawy danych, więc kolejny zestaw pasuje". Wykonalne, patrz niżej.

### Rozstrzygnięcie sporu SEO (GA4 90d — NIE blokuj UX tym argumentem)
Pierwotna obawa („kafle linkują do konkurencyjnych ofert → rozlewamy equity") **jest nieistotna**:
`/oferta/` ma **26 978 odsłon vs 676 sesji organic** i 405 klików/90d na 3 000 stron — to strona
**konwersyjna, nie akwizycyjna**. PageRank ofert jest znikomy, więc „rozlanie znikomego" nic nie kosztuje.
Za to mobile = **79,6% sesji**. **UX wygrywa.** Memory: `reference_mobile_share_and_offers_are_conversion_pages`.
Osobno: „czy robot nie wyjdzie ze strony" — **nie**, Googlebot pobiera cały HTML naraz i parsuje
wszystkie linki niezależnie od kolejności; nie czyta liniowo.

### Wykonalność (zweryfikowane w kodzie 2026-07-16)
- **Huby BEZPIECZNE**: `[asiaauto_tech_specs]` i `[asiaauto_equipment]` są wołane **wyłącznie**
  z `class-asiaauto-single.php:93-94`. Zero użycia na hubach (grep). Modyfikacja `renderTechSpecs()`
  ich nie dotyka.
- **Wstawienie MIĘDZY sekcje jest wykonalne** — `renderTechSpecs()` (`class-asiaauto-shortcodes.php:1674+`)
  iteruje `foreach ($sections as $sec_id => $sec)`, więc `extra_prep` jest już pogrubowane (uwaga Janka
  trafna). Wstawka po N-tej sekcji = jedno miejsce w pętli.
- 🔴 **GOTCHA — kotwicz po INDEKSIE sekcji, NIE po jej tytule.** Sekcje są **warunkowe od paliwa**
  (komentarz w kodzie: „Układ elektryczny — *only PHEV/EREV/EV*”) i puste są pomijane
  (`if (empty($sec['rows'])) continue;`). Rozkład paliw (3 058 publish): **EV 969 (32%)**,
  PHEV 755 (25%), EREV 540 (18%), **Benzyna+Diesel+HEV 733 (24%)**. Czyli „między Silnik a Układ
  elektryczny" **nie istnieje dla ~połowy katalogu**. Kotwica po indeksie działa zawsze.
- **Wariant najtańszy (bez dotykania shortcode'a):** wstawka między `[asiaauto_tech_specs]`
  a `[asiaauto_equipment]` w `single.php:93-94` = granica bloków, **59% strony**. Dwie linijki.

### Do decyzji przed budową
1. Miejsce: po keySpecs (14%) / po opisie (28%) / **po N-tej sekcji technicznej (~39%, pomysł Janka)** /
   granica tech|equipment (59%, najtańsza).
2. Czy kafle + CTA do hubu, czy sama linia tekstowa (W2) — CTA do hubu ma nieść anchor
   `serieAnchor()` (v0.33.23), spójnie z breadcrumbem i navrow.
3. Reuse: `AsiaAuto_Inventory::renderCard()` — **dopiero po T-212**, inaczej czwarta kopia kafla.

---

## Plan (kroki)

*(Wspólny komponent karty — patrz **T-212**. Zakładamy, że jest gotowy.)*

1. **Nowa metoda `otherUnits()` w `AsiaAuto_Single`** — WP_Query po `serie` bieżącej oferty, `post__not_in => [$pid]`, sort po cenie rosnąco, limit 4. Guard: `serie->count <= 1` → nie renderuj (66 ofert-unikatów). Karty przez `AsiaAuto_Card::render()`.
2. **Wpiąć w `render()`** między linię 94 a 95 (po wyposażeniu, przed „Inne modele marki").
3. **Nagłówek bloku = ścieżka do huba:** „Inne egzemplarze {Model} ({n})" + CTA „Zobacz wszystkie ceny i wersje {Model} →" linkujące do `get_term_link($serie)`. **To załatwia mobile'a** — link jest w treści, nie w breadcrumbie.
4. **Mobilna strzałka „←"** (`class-asiaauto-single.php:354`) — przepiąć z `/samochody/` na hub modelu. Fallback na `/samochody/` gdy brak termu.
5. **Blok wersji na hubie modelu** (`taxonomy-serie.php`) — lista wersji/komplektacji z linkiem do najtańszego egzemplarza każdej.

## Strefy kruche

- `class-asiaauto-single.php` — **nie ruszamy** `renderMeta()`, JSON-LD (`:884-926`), galerii ani `keySpecs()`. Dokładamy jedną linię w `render()` i jedną nową metodę.

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
