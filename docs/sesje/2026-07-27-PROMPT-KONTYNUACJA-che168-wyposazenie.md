# Prompt kontynuacyjny — T-186, wyposażenie che168 i decyzja o automatyzacji

> Wklej treść poniżej na start nowego wątku w `~/projekty/primaauto`.
> Stan zamrożony: 2026-07-27, plugin v0.34.3.

---

Kontynuujemy **T-186 (che168 jako drugie źródło ofert)**. Przeczytaj najpierw:

- `docs/roadmapa/T-186-che168-automat.md` — sekcja **„Wyposażenie — rozstrzygnięcie 2026-07-27"**
- `docs/sesje/2026-07-27-katalog-autohome-wyposazenie.md` — domknięcie ostatniej sesji
- memory: `project_che168_sonda_katalog_autohome_2026_07_27`, `reference_che168_alias_wymaga_sygnatury_brandmapping`

## Gdzie jesteśmy

Blokada wyposażenia jest **rozstrzygnięta**. auto-api odmówiło (27.07): pełnej konfiguracji che168
nie mają i nie będzie jej na żadnym planie. Zbudowaliśmy własną ścieżkę przez publiczny katalog
Autohome i **przetestowaliśmy ją end-to-end na dwóch ofertach na produkcji**:

- `/oferta/voyah-passion-l-2025-390681/` — `extra_prep` 90 → 196, 88 pozycji wyposażenia po polsku
- `/oferta/voyah-passion-phev-2024-390697/` — 104 → 173, 71 pozycji

Działające klocki (wszystkie na produkcji, wszystkie addytywne):

| Klocek | Gdzie |
|---|---|
| `specid` stemplowany przy imporcie (ręcznym i sync) | adapter che168 → `$data['spec_id']`, importer → `_asiaauto_spec_id` |
| Fetch + zdjęcie obfuskacji | `scripts/autohome-catalog-fetch.js <specid> [out.json]` (Node, ~6 s, 450 KB) |
| Most nazwa CN → klucz `extra_prep` | `data/autohome-catalog-map.php` (126 nazw) |
| Dolewka do oferty | `scripts/autohome-catalog-merge.php <post_id> <json> <specid> [apply]` |

Backfill zrobiony: **99 ze 120 ofert che168 ma `_asiaauto_spec_id`** (21 zwraca 404 — wygasły
u źródła), 89 unikalnych specidów.

## Zadanie tej sesji: uruchomić sync i zebrać liczby

Decyzję o automatyzacji (cron vs wpięcie w import) świadomie odłożyliśmy do momentu, aż zobaczymy
realny strumień. **Nie automatyzuj niczego przed zebraniem tych danych.**

Bieg: włącz sync che168 na statusie `draft` (`asiaauto_sync_enabled_che168`, status `draft`),
dongchedi zostaje wyłączony. Kursor: kontynuuj albo ustaw na start doby — świadomie.

Po biegu zmierz i przedstaw:

1. **Ile nowych ofert ma bliźniaka** (marka+seria+wersja+rocznik) z pełnym `extra_prep` (≥200 pól)
   → tam wystarczy dziedziczenie. Skrypt pomiarowy: `tmp/analiza-scenariusz-katalog.php` z poprzedniej
   sesji (jeśli zniknął — logika jest opisana w T-186; ostatni pomiar: 63/120 = 52,5%).
2. **Ile wymaga katalogu** (brak bliźniaka). Ostatni pomiar: 57/120 = 47,5%.
3. **Czy `specid` przychodzi w 100%** nowych ofert (powinien — pole jest w `extra.configuration`).
4. **Ile razy nazwa modelu z auto-api rozjeżdża się z tytułem strony katalogu.** To wykryło „Zeekr"
   (auto-api zwracało obcą markę dla Voyah Passion). Podejrzenie: to nie jedyny taki przypadek.
   Tytuł strony katalogu ma format `【{model} {rok}款 {wersja}参数配置表】…`.
5. **Ile pozycji katalogu pozostaje niezmapowanych** w `autohome-catalog-map.php` (ostatnio 97-98
   na ofertę) i które nazwy CN powtarzają się najczęściej — to lista do dopisania do mapy.

## Reguła decyzyjna (uzgodniona, do zaimplementowania dopiero po pomiarach)

1. `extra_prep` ≥200 pól → nie ruszamy.
2. Bliźniak exact z pełnym `extra_prep` → **dziedziczenie** (zero requestów, bogatsze: 378 kluczy
   wobec 292 z katalogu — zmierzone na Passion L).
3. Jest `_asiaauto_spec_id` → **katalog Autohome**.
4. Brak obu → zostaje jak jest albo dziedziczenie luźne z konsensusem.

⚠️ **Dziedziczenie NIE jest wpięte w import ani sync** — `scripts/merge-spec-from-twin.php` odpala się
ręcznie i dopasowuje **wyłącznie exact** (marka+seria+wersja+rocznik). Po włączeniu synca oferty
wpadną z samą techniką, dopóki ktoś czegoś nie odpali. To jest właśnie do rozstrzygnięcia w tej sesji.

⚠️ **Fetch katalogu świadomie NIE w trakcie importu**: 450 KB, ~6 s, wymaga Node, może paść przy
zmianie zabezpieczeń u źródła — nie może wywracać synca. Jeśli automatyzujemy, to cronem z throttlingiem
i dziennym limitem (wzorzec: `scripts/cron-index-retry.sh`) plus cache per `specid`.

## Zasady pracy (sprawdzone, nie łam)

- Adapter che168 = jedyne miejsce dialektu; `importListing` i niżej **nietknięte** (ADR
  `2026-06-17-che168-normalize-at-entry`). Wszystko addytywnie.
- Mapowanie testuj **wyłącznie** ścieżką `getOffer()` → `Che168_Adapter::normalize()` → `getEuForCn()`.
  Na surowych danych zawsze zwróci null i wygląda to jak porażka mapowania.
- Nowy model che168 wymaga **dwóch** wpisów: alias w `che168-model-map.php` **i** sygnatura
  `mark_eu|serie_eu` w `brand-mapping-v6.1.php` (inaczej `sigToKey()` zwraca surowe mark/model
  i guard odrzuca ofertę jako orphan).
- Przed każdym batchem >20 iteracji: dry-run. Przed zapisem do produkcyjnej bazy: `mysqldump`.
- Slugi ofert i hubów, które mogą być zaindeksowane — nie ruszamy bez sprawdzenia GSC.

## Otwarte wątki (nie blokują)

- **169 ofert ma chińskie znaki w nazwach plików zdjęć** (2114 plików) — skrypt renamingu sprawdzony
  na 210 plikach (`tmp/rename-zdjecia-voyah.php`), rusza pliki + `guid` + `_wp_attached_file` +
  `_wp_attachment_metadata` + alt. Czeka na decyzję Janka.
- **Obcięty `extra_prep` dongchedi** (>inner_id ~24,34M: 43 pola zamiast ~360) — auto-api pominęło
  to pytanie w odpowiedzi. Draft dopytania gotowy, **nie wysłany**.
- **Oficjalny MCP Autohome** (`open.autohome.com.cn`, token na wniosek: `zhijieru@autohome.com.cn`) —
  draft maila gotowy, nie wysłany. Ich narzędzia działają po nazwie serii, nie po `specid`, więc mogą
  nie schodzić do poziomu wersji. Do sprawdzenia, jeśli ścieżka katalogowa zacznie sprawiać problemy.
- Dedup po VIN, panel „Źródła", decyzja Ruslana o filtrze miast — bez zmian od 22.07.
