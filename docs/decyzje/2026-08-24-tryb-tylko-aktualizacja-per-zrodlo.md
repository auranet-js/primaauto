# 2026-08-24 — Tryb „tylko aktualizacja" per źródło jako stan sterowalny z panelu

> **Status:** wdrożone (v0.34.26)
> **Poprzednik:** T-222 (`docs/superpowers/specs/2026-07-30-t222-migracja-dongchedi-che168-design.md`),
> `docs/decyzje/2026-08-16-dongchedi-wznowienie-importu-i-zrownanie-filtrow.md`

## Problem

Utrzymujemy dwa kanały: dongchedi i che168. Oba, gdy działają, dowożą towar — a nie potrzebujemy
przyrostu z obu naraz. Podwójny zaciąg kosztuje transfer, wywołania płatnego API i pulę Indexing
(200/dobę, dzielona ze wszystkimi projektami — `~/.claude/CLAUDE.md` §10a).

Panel oferował jednak tylko dwa stany per źródło: włączone albo wyłączone. Wyłączenie gasi
**cały** sync tego kanału — razem z aktualizacją cen i wycofywaniem ofert zdjętych u źródła.
To nie jest teoretyczne ryzyko, tylko przerobiony scenariusz: zapas dongchedi, odcięty od
aktualizacji, zaczął gnić jako ogłoszenia aut już sprzedanych (1066 sztuk do wygaszenia,
pomiar z 30.07). Klient dostaje ofertę, której nie ma.

Potrzebny jest trzeci stan: **kanał nie dowozi nowego towaru, ale swój dotychczasowy zapas
utrzymuje w prawdzie i sam się wygasza.**

## Ustalenie: mechanizm już istniał, brakowało sterowania

Rozbiór kodu przed pisaniem czegokolwiek: tryb `verify` jest w `class-asiaauto-sync.php`
od 30.07 (T-222) i robi dokładnie to, co opisano wyżej.

| zdarzenie z `/changes` | `full` | `verify` |
|---|---|---|
| `added` | import | skip |
| `changed`, ofertę mamy lokalnie | aktualizacja ceny/przebiegu/zdjęć | **aktualizacja** |
| `changed`, oferty nie mamy | import (druga furtka, ~42% zdarzeń) | skip |
| `removed` | `markRemoved()` → draft → 301 | **`markRemoved()`** |

Domknięcie drugiej furtki jest tu istotne: bez niej strumień `changed` dalej dowoziłby nowy
towar mimo trybu `verify`.

`grep` po całym pluginie wykazał, że `asiaauto_sync_mode_` występuje **wyłącznie**
w `class-asiaauto-sync.php` — opcji nie ustawiało nic w UI. Jedyną drogą było
`wp option update` z konsoli, czyli wiedza zamknięta w głowie i w commitach.

**Decyzja: nie piszemy nowego mechanizmu wygaszania.** Dokładamy przełącznik do tego, co działa.

## Rozwiązanie

Kafelek „Źródła" (Konfiguracja → Status) dostaje trzystanowy select per źródło. Trzy stany UI
mapują się na dwie opcje, których sync używa od T-222:

| stan w panelu | `asiaauto_sync_enabled_{src}` | `asiaauto_sync_mode_{src}` |
|---|---|---|
| pełny import | `1` | `full` |
| tylko aktualizacja | `1` | `verify` |
| wyłączone | `0` | *bez zmiany* |

`mode` przy wyłączaniu zostaje nietknięty celowo — powrót do „włączone" wraca do trybu,
w którym kanał był, zamiast cicho przeskakiwać na `full`.

Kod synca **bez zmian**. Zmiana obejmuje wyłącznie `class-asiaauto-admin.php`:
handler `sync_source_toggle` (przyjmuje `sync_source_mode`, waliduje wobec listy) i render kafelka.

## Konsekwencje

- **Przełączanie tam i z powrotem jest tanie.** Kursor `asiaauto_last_change_id_{source}` biegnie
  także w trybie `verify`, więc powrót na `full` nie ciągnie zaległości — wchodzą tylko zdarzenia
  od chwili przełączenia. Sprawdzone przy powrocie dongchedi na `full` 16.08: pierwsze biegi
  dały 0–13 `added` przy 13–350 `skipped`, bez zalewu.
- **Scenariusz awarii kanału jest teraz jednym kliknięciem.** Gdy jeden feed padnie (che168 stanął
  12–16.08, dongchedi zamarzał trzykrotnie w czerwcu i przez cały lipiec), drugi przechodzi
  z „tylko aktualizacja" na „pełny import" bez konsoli.
- **Nie ma automatycznego wykrywania, że kanał stanął.** Świadomie poza zakresem — decyzja Janka
  z 24.08. Świeżość kanału czyta się z historii synca na tej samej stronie. Ryzyko pozostaje takie
  jak w sierpniu: cisza obu kanałów naraz nie zapala żadnej lampki.
- **Ręczny import nie podlega tym flagom** (menu che168, „Dodaj z Dongchedi") — pozostaje dostępny
  w każdym trybie, bo to świadome działanie operatora, nie strumień.
- Sztuki oznaczone `_asiaauto_manual_import` / `_asiaauto_manual_entry` oraz z rezerwacją są
  pomijane przez sync także w trybie `verify` — bez zmian względem `full`.

## Czego NIE zrobiono

- Odwzorowania filtrów che168 → dongchedi (guard mapowania działa tylko na che168) — otwarte
  z 16.08, osobny temat, nie wchodzi w zakres tej zmiany.
- Wskaźnika świeżości kanału przy przełączniku — patrz wyżej.
