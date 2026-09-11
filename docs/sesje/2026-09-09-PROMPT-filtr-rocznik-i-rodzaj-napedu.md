# PROMPT — wyszukiwarka zaawansowana: filtr Rocznik + zmiana nazwy „Napęd 4x4"

> Zlecenie Janka 2026-09-09. Dotyczy **wyszukiwarki zaawansowanej** (`/wyszukiwarka/`,
> `includes/class-asiaauto-search.php`), NIE katalogu `/samochody/`.
> Wielkość: dwie zmiany w tablicy `SEKCJE` + weryfikacja. To nie jest projekt, to poprawka.

## Co ma być zrobione

**1. Zmiana etykiety.** `class-asiaauto-search.php:97` — `'label' => 'Napęd 4x4'` → `'Rodzaj napędu'`.

Powód: pole `drive` to **enum trzech wartości**, nie flaga tak/nie. Rozkład na publish
(`wp7j_asiaauto_specs`, pomiar 09.09): `awd` 1419 · `rwd` 845 · `fwd` 740 · NULL 4.
Etykiety termów: „4x4 (AWD)" / „Tylny (RWD)" / „Przedni (FWD)"
(`data/translations-drive-types.php`). Nazwa „Napęd 4x4" sugeruje checkbox „ma 4x4",
a pod spodem są trzy pozycje do wyboru.

**Dodatkowy argument, ważniejszy niż estetyka:** katalog `/samochody/` nazywa to pole
**już teraz** „Rodzaj napędu" (`class-asiaauto-inventory.php:760`). Dwa ekrany mówią
o tej samej taksonomii dwiema nazwami. Ta zmiana usuwa niespójność, nie tworzy nowej.

**2. Filtr Rocznik**, jako **enum z checkboxami** (dokładnie jak „Rocznik" na `/samochody/`
i jak sąsiednie „Liczba miejsc" / „Liczba drzwi”), wstawiony **bezpośrednio za napędem**
w sekcji „Nadwozie":

```php
['typ' => 'enum', 'col' => 'drive', 'label' => 'Rodzaj napędu'],
['typ' => 'enum', 'col' => 'year',  'label' => 'Rocznik'],      // ← nowe
```

## Dlaczego to tak mało pracy — infrastruktura już istnieje

Sprawdzone w kodzie 09.09, nie zakładane:

| element | gdzie | stan |
|---|---|---|
| parametr URL/API `rocznik` → kolumna `year` | `ENUM_PARAMS`, search.php:36 | **jest** |
| sortowanie roczników malejąco | search.php:685 (`krsort … SORT_NUMERIC`) | **jest** |
| etykieta = sama wartość (bez mapy słownikowej) | search.php:1024 | **jest** |
| brak separatora tysięcy („2022", nie „2 022") | search.php:1030, `$grupuj=false` | **jest** |
| `RANGE_PARAMS['rok']` + `RANGE_LABELS['rok']` | search.php:52, 990 | jest — **zostaw, nie ruszaj**, to wariant zakresowy dla API |

Brakuje **wyłącznie** wpisu w `SEKCJE`. Filtr działa dziś przez API (`?rocznik=2025`),
tylko nie ma go w interfejsie.

Dane do sanity-checku po wdrożeniu (publish, 09.09): 2026 — 543 · 2025 — 1585 ·
2024 — 839 · 2023 — 39 · 2022 — 2. Razem 3008.

## Na co uważać

**Siatka.** Sekcja „Nadwozie" ma `'kol' => 5` i dziewięć pól (dwa rzędy: 5 + 4).
Dziesiąte pole domyka układ do równych 5 + 5 — sprawdź na zrzucie, czy nie trzeba
korygować `kol`. Historia z 08.09: przy 9 polach siatka 7-kolumnowa dawała paski po 90 px.

**Rocznik był świadomie zdjęty z UI 03.09.** Komentarz przy sekcji `tech`
(search.php:126) mówi: „cena/rocznik/przebieg tylko przez sortowanie". To była decyzja
Janka przy likwidacji „Więcej filtrów". Teraz Janek ją zmienia — zaktualizuj ten komentarz,
żeby nie został jako ślad nieaktualnej decyzji. Cena i przebieg zostają poza UI.

**`year` to rok MODELOWY, nie produkcji ani rejestracji.** To jest jedyna rzecz w tym
zadaniu, która może wymagać rozmowy z Jankiem — reszta jest mechaniczna:

- kolumna `year` w `wp7j_asiaauto_specs` pochodzi z taksonomii `ca-year` = rok modelowy ze źródła
- karta oferty i umowa PDF pokazują **rok pierwszej rejestracji** z meta `registration_date`
  (`class-asiaauto-inventory.php:1101`, `class-asiaauto-contract.php::extractRegistrationYear()`,
  ADR `docs/decyzje/2026-05-15-rok-rejestracji-zamiast-produkcji.md`), pokrycie 98,4%
- dla aut prawie-nowych oba są zwykle takie same, ale nie zawsze

Katalog `/samochody/` filtruje po `ca-year`, więc **nowy filtr będzie z nim spójny** —
i to jest argument, żeby zostawić `year` tak jak jest. Ale jeśli zauważysz rozbieżność
między filtrem a rokiem na karcie, zgłoś Jankowi, nie naprawiaj po cichu.
Memory: `reference_dongchedi_year_fields.md`.

## Weryfikacja przed zgłoszeniem „zrobione"

1. `php -l` **przed** wgraniem na produkcję.
2. Wersję wtyczki podbij **Pythonem, nie `sed`-em** — wzorzec z nawiasem zjadł kiedyś `)`
   w `define('ASIAAUTO_VERSION', …)` i zostawił parse error na produkcji.
3. `filter-counts`: liczniki przy rocznikach mają zgadzać się z tabelą wyżej i **zawężać się**
   po wybraniu marki (mechanizm jak przy pozostałych enumach).
4. **Zrzut ekranu desktop + telefon.** Dwa regresy mobilne z 08.09 przeszły przez zielone
   bramki i wyszły dopiero na zrzucie. Sprawdź: wysokość wiersza 44 px, jeden kwadrat
   w wierszu, brak przewijania poziomego, sekcja zwija się i pokazuje podsumowanie w nagłówku.
5. Bramka axe: target-size 2.5.8 — checkbox rozciągnięty na cały wiersz + kwadracik z `::before`.
6. Sprawdź URL-e: `?rocznik=2025`, `?rocznik=2025,2024`, kombinacja z `naped=awd`.
7. Etykieta „Rodzaj napędu" ma się pojawić **w wyszukiwarce**; nie ruszaj `/samochody/`,
   tam jest już poprawna.

## Czego NIE ruszać

- `/samochody/` (`class-asiaauto-inventory.php`) — poza zakresem, etykieta tam jest już dobra
- `RANGE_PARAMS['rok']` / `RANGE_LABELS['rok']` — zostają dla API
- kolumna `year`, taksonomia `ca-year`, `SCHEMA_VERSION` — przebudowa tabeli **nie jest potrzebna**,
  `year` już w niej jest i jest wypełniona
- sortowanie „Najnowszy rocznik" (`year_desc`) — działa, zostaje

## Kontekst na start wątku

- CLAUDE.md projektu, sekcja 3 (strefy kruche) — wyszukiwarka nie jest strefą kruchą, ale
  `class-asiaauto-search.php` ma ~1000+ linii i obsługuje wszystkie ekrany filtrowania
- memory: `project_filtry_drzwi_kolumny_2026_09_08` (START dla filtrów),
  `project_filtr_wyszukiwarki_2026_09_07`, `feedback_no_unverified_ui_claims`
- makiety filtrów: `docs/makiety/gen-j2.py` (desktop), `gen-m.py` (arkusz mobilny)
