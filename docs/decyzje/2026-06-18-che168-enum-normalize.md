# ADR 2026-06-18 — Che168: normalizacja enumów atrybutów PRZY WEJŚCIU (T-186, v0.33.4)

## Kontekst

Normalizacja tożsamości che168 (`mark`/`model`) była już rozwiązana przy wejściu (ADR
2026-06-17). Pozostała druga oś: **enumy atrybutów** — `body_type`, `engine_type`,
`drive_type`, `color`. Dry-run oferty **58545168** (Denza N8L) pokazał 3 taksonomie
ze statusem „🆕 zostanie utworzony": `crossover/suv`, `plug-in hybrid`, `awd (front-engine)`.

## Diagnoza

Tożsamość termu w `setTaxonomyAndMeta()` (strefa krucha) rozstrzyga się po **slugu liczonym
z surowej wartości źródła** (`sanitize_title($api_value)`), nie z tłumaczenia. Che168 używa
innego „dialektu" enumów niż Dongchedi (`crossover/suv` vs `suv`, `plug-in hybrid` vs `phev`,
`awd (front-engine)` vs `AWD`) → surowiec che168 dawałby nowe, śmieciowe slugi (`crossover-suv`
obok czystego `suv`) = **duplikaty taksonomii / kontaminacja filtrów**.

**Dlaczego sama dopiska do `translations-*.php` nie wystarcza:** słowniki sterują tylko NAZWĄ
termu (`$value`), a matching/dedup idzie po SLUGU z `api_value`. Dopisanie aliasu dałoby term
z polską nazwą, ale wciąż slugiem `crossover-suv` → nadal duplikat. Odrzucono.

**Alternatywa „slug z przetłumaczonej wartości w setTaxonomyAndMeta":** dotyka strefy kruchej
i grozi churnem slugów Dongchedi (istniejące termy mają slug z api_value) → odrzucono.

## Decyzja

Rozszerzyć normalizację-przy-wejściu z `mark/model` na enumy — tym samym wzorcem:

- **`data/che168-enum-map.php`** — płaska mapa danych „surowiec che168 → klucz słownika
  Dongchedi" dla body/engine/drive/color. Domena **zamknięta** (pełna lista z
  `getFilters('che168')`) → mapa danych, **bez resolvera algorytmicznego** (inaczej niż
  mark/model, gdzie domena otwarta).
- **`AsiaAuto_Che168_Adapter::normalizeEnums()`** — wołane w `normalize()` PRZED kanonizacją
  tożsamości (by `canonicalKeyForSource()` dostało już kanoniczny `engine_type` — oś wariantu
  napędu). Surowiec zachowany w `{field}_che168_raw`.
- **Słowniki `translations-*.php` reużyte 1:1** — zero nowych kluczy che168.
- **`importListing`/`setTaxonomies` — bajt w bajt nietknięte.** Strefa krucha NIETKNIĘTA.

### Reguła „utworzyć vs przemapować"

- **Przemapowanie** (semantyczny duplikat) — domyślny przypadek: `crossover/suv`→SUV,
  `plug-in hybrid`→PHEV, `awd (*)`/`rwd (*)`→AWD/RWD (oś umiejscowienia silnika / liczby
  motorów ZWIJANA — taksonomia ma tylko AWD/FWD/RWD; detal w `extra_prep`).
- **Utworzenie** — tylko gdy to realna nowa kategoria z czystym slugiem (np. `bi-fuel`).
- **Mild-hybrydy** (24v/48v/90v, diesel+48v) — **ZWINIĘTE w `hybrid` (HEV)** (decyzja Janka
  2026-06-18): świadomie bez osobnego filtra MHEV zasilanego tylko autami che168.

## Pokrycie (zmierzone, pełny zamknięty słownik `getFilters('che168')`)

- body **10/10**, engine **14/14**, drive **11/11** → istniejące termy.
- **Zero nowych termów, zero śmieciowych slugów.**
- 58545168: 9/9 taksonomii ISTNIEJE. Bez regresji mark/model (smoke 4/5 mapped jak wcześniej).

## Konsekwencje

- Realny import che168 **nadal OFF** (`ASIAAUTO_CHE168_IMPORT_ENABLED=false`). To kalibracja
  fazy obserwacji — dry-run/log pokazują poprawne taksonomie.
- Asymetria: normalizujemy tylko wejście che168; Dongchedi leci surowo. Pełne ujednolicenie =
  ewentualny T-187 (migracja slugów) — poza zakresem.

## Pliki

- `data/che168-enum-map.php` (nowy), `includes/class-asiaauto-che168-adapter.php`
  (`normalizeEnums()` + `enumMap()`). Backup `.bak-2026-06-18-enum`.
- Skrypty dowodowe (gitignored, `tmp/`): `che168-filters-vocab.php`,
  `che168-enum-coverage.php`, `che168-diag-58545168.php`.
- Powiązane: `2026-06-17-che168-normalize-at-entry.md`, `2026-06-16-che168-manual-import.md`.
