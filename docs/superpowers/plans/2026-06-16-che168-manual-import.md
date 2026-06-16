# T-185 — Che168 ręczny import + log wdrożeniowy (kalibrator mapowania) — plan implementacji

> **For agentic workers:** REQUIRED SUB-SKILL: użyj `superpowers:subagent-driven-development` (rekomendowane) lub `superpowers:executing-plans` do realizacji task-by-task. Kroki używają checkbox (`- [ ]`).

**Goal:** Dodać do istniejącej strony „Dodaj z Dongchedi" obsługę źródła **Che168** — ukrytą i dostępną tylko dla Janka — która dla każdego wskazanego ogłoszenia pokazuje **pełną symulację importu** (tytuł, slug, wszystkie termy taksonomii, wszystkie meta, kompletny przetłumaczony extra_prep, zdjęcia, kalkulacja ceny) BEZ zapisu do bazy, oraz pozwala zapisać snapshot tej symulacji do **logu wdrożeniowego Che168** (dataset JSON do wspólnej kalibracji mapowania przez kilka dni przed włączeniem realnego importu).

**Architecture:** Sednem jest **wspólny kod symulacji i realnego importu** — refaktor `AsiaAuto_Importer::importListing()` tak, by czysta (bez efektów ubocznych) metoda `buildPlan(array $data, string $source): array` liczyła wszystkie pola docelowe, a `importListing()` ją konsumowała i wykonywała zapisy. Podgląd woła ten sam `buildPlan()`. Dzięki temu to, co Janek ogląda w dry-run, jest **dokładnie tym**, co trafi do bazy przy realnym imporcie — inaczej weryfikowałby atrapę. Refaktor jest chroniony testem regresji (identyczne posty dongchedi przed/po). Adapter Che168 normalizuje inny kształt danych (`address`→city, `extra.configuration`→`extra_prep`, `vin`+`first_registration`, obrazy permanentne), a resolver marka/model (reverse-index brand-mappingu) wskazuje właściwy hub.

**Tech Stack:** PHP 8.x, WordPress, plugin `asiaauto-sync`. Brak suite PHPUnit w repo → weryfikacja per-task przez `php -l` + smoke przez `wp eval-file <skrypt>.php` na żywych numerach Che168 (read-only) + ręczny przegląd renderu w wp-admin.

## Global Constraints

- **Strefa krucha (CLAUDE.md §3.2):** `class-asiaauto-importer.php` — refaktor TYLKO przez ekstrakcję bez zmiany zachowania; każdy task dotykający importera kończy testem regresji dongchedi (0 różnic). Nie zmieniać nazw klas / CPT / meta keys / shortcodów / ról.
- **Slugi wewnętrzne `asiaauto-*` zostają** (CLAUDE.md §1) — nowe meta/opcje prefiksujemy `_asiaauto_*` / `asiaauto_*`.
- **Gate:** funkcja Che168 (submenu wpis + źródło + log) widoczna i wykonywalna **wyłącznie** dla loginów z nowej stałej `ASIAAUTO_CHE168_PREVIEW` w `wp-config.php`; przy braku stałej — całość niewidoczna (zero zmian UX dongchedi). Dodatkowo `current_user_can(AsiaAuto_Security::IMPORT_CAP)`.
- **Brak realnego importu Che168 w fazie obserwacji:** dopóki stała `ASIAAUTO_CHE168_IMPORT_ENABLED` nie jest `true`, przycisk „Zaimportuj" dla źródła Che168 jest ukryty — zostaje tylko podgląd + „Zapisz do logu wdrożeniowego". Dongchedi nietknięte.
- **Backup przed nadpisaniem** każdego pliku produkcyjnego: `.bak-2026-06-16-che168` (CLAUDE.md §6). Deploy = edycja in-place na serwerze po `php -l`.
- **Diakrytyki:** stringi PL przez `esc_html` w JS-renderze nie wolno ASCII-zować (memory `make_hubs_rework` GOTCHA) — render po stronie JS używa `esc()` (textContent), wartości przychodzą już po polsku z PHP.
- **Źródło prawdy o kodzie = serwer** (`~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/`), repo = kontekst.

---

## Mapa plików

**Tworzone:**
- `includes/class-asiaauto-che168-adapter.php` — `AsiaAuto_Che168_Adapter`: normalizacja surowego `$data` Che168 → wspólny kształt importera (`city` z `address`, `extra_prep` z `extra.configuration` po `id`, `vin`, `first_registration`, obrazy).
- `data/che168-param-map.php` — tabela `che168_param_id (int) → label PL` (~50–100 ID; jednorazowa, dorabiana w fazie obserwacji).
- `data/che168-model-map.php` — opcjonalne ręczne nadpisania resolvera dla ogona CN (sub-brandy spoza brand-mapping); startowo z `tmp/che168-mapping-proposal-2026-06-05`.
- `includes/class-asiaauto-che168-log.php` — `AsiaAuto_Che168_Log`: zapis/odczyt snapshotów dry-run do `wp-content/uploads/asiaauto/che168-dryrun/`.
- `tmp/che168-smoke-buildplan-2026-06-16.php` — skrypt `wp eval-file` smoke (dry-run plan dla listy żywych numerów Che168).

**Modyfikowane:**
- `includes/class-asiaauto-importer.php` — ekstrakcja `buildPlan()`; `importListing()` konsumuje plan. (strefa krucha)
- `includes/class-asiaauto-mapping.php` — dodać `resolveForSource($mark, $model, $engine, $source): ?array`.
- `includes/class-asiaauto-admin-manual-import.php` — gate Che168, select źródła, rozbudowa preview (pełna tabela + extra_prep + symulacja title/slug/terms/meta), przycisk „Zapisz do logu", warunkowy przycisk importu.
- `asiaauto-sync.php` — `require_once` nowych klas + bump `ASIAAUTO_VERSION`.

---

## Task 1 — Gate „ukryte, tylko dla Janka"

**Files:**
- Modify: `includes/class-asiaauto-admin-manual-import.php` (metody `addMenuPage`, `ajaxPreview`, `ajaxImport`, render)
- Reference: `includes/class-asiaauto-security.php` (`IMPORT_CAP`)

**Interfaces:**
- Produkuje: `private function che168Allowed(): bool` — `true` gdy `defined('ASIAAUTO_CHE168_PREVIEW')` i login bieżącego usera jest na liście (CSV) ORAZ `current_user_can(IMPORT_CAP)`. Używane przez Task 5/6 do warunkowego renderu.

- [ ] **Krok 1: Dodać helper gate**

W `class-asiaauto-admin-manual-import.php` (po `makeApi()`):
```php
/**
 * Czy bieżący user ma dostęp do funkcji Che168 (ukryta, opt-in per login).
 * Stała w wp-config: define('ASIAAUTO_CHE168_PREVIEW', 'janek,jan');  // CSV loginów
 * Brak stałej = funkcja całkowicie niewidoczna (zero zmian UX dongchedi).
 */
private function che168Allowed(): bool {
    if (!current_user_can(AsiaAuto_Security::IMPORT_CAP)) return false;
    if (!defined('ASIAAUTO_CHE168_PREVIEW')) return false;
    $allowed = array_filter(array_map('trim', explode(',', (string) ASIAAUTO_CHE168_PREVIEW)));
    if (empty($allowed)) return false;
    $user = wp_get_current_user();
    return in_array($user->user_login, $allowed, true);
}

/** Czy realny import Che168 jest włączony (faza obserwacji = false). */
private function che168ImportEnabled(): bool {
    return defined('ASIAAUTO_CHE168_IMPORT_ENABLED') && ASIAAUTO_CHE168_IMPORT_ENABLED === true;
}
```

- [ ] **Krok 2: Ustawić stałą w wp-config na produkcji**

Dodać do `wp-config.php` (powyżej `/* That's all */`), po backupie pliku:
```php
define('ASIAAUTO_CHE168_PREVIEW', 'janek');   // ← podmienić na realny user_login Janka
```
Weryfikacja loginu: `wp user list --role=administrator --field=user_login`.

- [ ] **Krok 3: `php -l` + smoke gate**

Run: `php -l includes/class-asiaauto-admin-manual-import.php` → `No syntax errors`.
Smoke: zalogowany jako Janek → strona „Dodaj z Dongchedi" działa jak dziś (regresja zero). Jako inny admin (gdyby istniał) → brak elementów Che168.

- [ ] **Krok 4: Commit**
```bash
git commit -am "[import:] T-185 krok 1 — gate Che168 (ukryty, opt-in per login wp-config)"
```

---

## Task 2 — `buildPlan()`: ekstrakcja czystej symulacji z importera ⚠ STREFA KRUCHA

**Files:**
- Modify: `includes/class-asiaauto-importer.php:69-223` (`importListing`)
- Test: `tmp/che168-smoke-buildplan-2026-06-16.php` (smoke regresji)

**Interfaces:**
- Produkuje: `public function buildPlan(array $data, string $source): array` zwraca:
```php
[
  'title'        => string,           // identyczny jak dziś importListing linia 127-130
  'slug_pattern' => string,           // buildListingSlug z {post_id} placeholder
  'mark'         => string,           // EU po mapping/resolver
  'serie'        => string,
  'mapped'       => bool,             // czy getEuForCn/resolver trafił (false = sierota)
  'terms'        => [ ['taxonomy'=>, 'value'=>, 'slug'=>, 'exists'=>bool], ... ],
  'meta'         => [ meta_key => value, ... ],   // wszystko co setMotorsMeta by zapisał
  'extra_prep'   => array,            // surowy (do translateExtraPrep w preview)
  'images'       => string[],
  'price_cny'    => float,
  'description_status' => string,
  'warnings'     => string[],         // np. "model bez mapowania → sierota"
]
```
- Konsumuje: `AsiaAuto_Mapping::getEuForCn()` / `resolveForSource()` (Task 3), translator (istnieje).

- [ ] **Krok 1: Smoke baseline PRZED refaktorem (regresja)**

Skrypt `tmp/che168-smoke-buildplan-2026-06-16.php` — eksportuje obecny stan 3 listingów dongchedi (hash title+meta) do `tmp/regression-baseline.json`:
```php
<?php // wp eval-file tmp/che168-smoke-buildplan-2026-06-16.php
$ids = (new WP_Query(['post_type'=>'listings','posts_per_page'=>3,'fields'=>'ids',
  'meta_query'=>[['key'=>'_asiaauto_source','value'=>'dongchedi']]]))->posts;
$out = [];
foreach ($ids as $id) {
  $meta = get_post_meta($id);
  ksort($meta);
  $out[$id] = ['title'=>get_post($id)->post_title,'name'=>get_post($id)->post_name,'meta'=>$meta];
}
file_put_contents(ABSPATH.'../tmp-regression-baseline.json', wp_json_encode($out, JSON_PRETTY_PRINT));
echo "baseline: ".count($out)." listings\n";
```
Run: `cd <plugin-dir> && wp eval-file tmp/che168-smoke-buildplan-2026-06-16.php`
Expected: `baseline: 3 listings`

- [ ] **Krok 2: Wyekstrahować `buildPlan()` z `importListing()`**

Przenieść logikę linii 99-130 (mark/model/eu/title) + budowę meta (z `setMotorsMeta` linie 430-507, ale jako **obliczenie do tablicy**, nie `update_post_meta`) + budowę termów (z `setTaxonomies` 509-583, jako lista `['taxonomy','value','slug','exists']` przez `get_term_by` bez `wp_insert_term`) do nowej publicznej `buildPlan()`. `importListing()` woła `buildPlan()`, potem wykonuje zapisy z gotowego planu. **Zero zmiany kolejności/wartości zapisów.** Slug: `buildListingSlug($mark,$model,$year, 0)` z podmianą `0`→`{post_id}` w `slug_pattern`.

- [ ] **Krok 3: `php -l`**

Run: `php -l includes/class-asiaauto-importer.php` → `No syntax errors`.

- [ ] **Krok 4: Test regresji — import 1 nowego dongchedi przez UI, porównać**

Zaimportować ręcznie 1 świeży listing dongchedi (jak dziś). Uruchomić smoke ponownie na nowym + 3 starych. Diff `tmp-regression-baseline.json` vs nowy snapshot dla 3 starych = **0 różnic**. Nowy listing: title/slug/meta zgodne ze wzorcem dongchedi sprzed refaktoru.
Expected: brak różnic w polach 3 baseline'owych listingów.

- [ ] **Krok 5: Commit**
```bash
git commit -am "[import:] T-185 krok 2 — buildPlan() czysta symulacja wyekstrahowana z importListing (regresja dongchedi 0 różnic)"
```

---

## Task 3 — Resolver marka/model dla Che168 (reverse-index brand-mappingu)

**Files:**
- Modify: `includes/class-asiaauto-mapping.php`
- Create: `data/che168-model-map.php` (nadpisania ogona CN)
- Test: `tmp/che168-smoke-buildplan-2026-06-16.php` (rozszerzony o resolver na 5 numerach z spec)

**Interfaces:**
- Produkuje: `public static function resolveForSource(string $mark, string $model, string $engine, string $source): ?array` — dla `che168`: reverse-index po `(mark_eu, serie_eu)` i `(mark_eu, slug)` + reguły normalizacji (strip prefiksu CN marki, strip marki EN z modelu, wariant napędu z `$engine`, normalizacja spacji), fallback `getEuForCn`. Dla `dongchedi`: zwraca `getEuForCn($mark,$model)` (bez zmian). Zwraca `['mark_eu','serie_eu','title_eu','slug']` lub `null`.
- Konsumuje: `data/brand-mapping-v6.1.php`, `data/che168-model-map.php`.

- [ ] **Krok 1: Dodać `resolveForSource()` + reverse-index**

W `class-asiaauto-mapping.php` zbudować raz indeks odwrotny `(mark_eu,serie_eu)→entry` i `(mark_eu,slug)→entry` z `load()`. Reguły normalizacji modelu Che168 (kolejność wg spec 5A.3): (1) strip prefiksu CN marki `腾势→Denza` itd. + set mark; (2) strip marki EN; (3) napęd z `$engine` (`plug-in hybrid`→`DM-i`, `electric`→`EV`) dla serie rozbitych per-napęd; (4) CAPS-insensitive + normalizacja spacji `007GT→007 GT`. Najpierw sprawdzić `che168-model-map.php` (ręczne nadpisania), potem reverse-index, potem fallback.

- [ ] **Krok 2: Podpiąć resolver w `buildPlan()`**

W `buildPlan()` (Task 2) zamienić `getEuForCn($markCN,$modelCN)` na `resolveForSource($markCN,$modelCN, $data['engine_type'] ?? '', $source)`. Dla `dongchedi` wynik identyczny → regresja nienaruszona.

- [ ] **Krok 3: `php -l` + smoke na 5 numerach z spec**

Rozszerzyć smoke o 5 żywych numerów Che168 (`getOffer('che168', $iid)` → adapter → `buildPlan`) i wypisać `mark/serie/mapped/slug_pattern`:
```
57877387 (腾势N8L)  → denza / n8l / mapped=1
57888520 (Denza D9) → denza / d9-dm-i / mapped=1
55765635 (001)      → zeekr / 001 / mapped=1
57946822 (Avatr 12) → avatr / 12 / mapped=1
55903341 (红旗金葵花国耀) → mapped=0 (sierota — kandydat do che168-model-map)
```
Run: `wp eval-file tmp/che168-smoke-buildplan-2026-06-16.php`
Expected: ≥4/5 `mapped=1`, hub zgodny z tabelą spec 5A.3a.

- [ ] **Krok 4: Commit**
```bash
git commit -am "[import:] T-185 krok 3 — resolveForSource() reverse-index brand-mappingu dla Che168 (4/5 trafia hub)"
```

---

## Task 4 — Adapter Che168 (normalizacja surowych danych)

**Files:**
- Create: `includes/class-asiaauto-che168-adapter.php`
- Create: `data/che168-param-map.php`
- Modify: `asiaauto-sync.php` (require_once)
- Modify: `includes/class-asiaauto-admin-manual-import.php` (`extractData` / `parseInput` dla Che168)

**Interfaces:**
- Produkuje: `AsiaAuto_Che168_Adapter::normalize(array $rawData): array` — zwraca `$data` w kształcie zgodnym z `buildPlan()`/importerem: `mark`, `model`, `year`, `price`, `km_age`, `city` (z `explode(', ', address)[0]`), `address`, `extra_prep` (z `extra.configuration.paramtypeitems[].paramitems[]` mapując `id`→label przez `che168-param-map.php` + wartości przez `translations-extra-prep-values`), `vin`, `first_registration`, `images`, `engine_type`, `body_type` itd.
- Konsumuje: `data/che168-param-map.php`, `data/translations-extra-prep-values.php`.

- [ ] **Krok 1: Napisać adapter + tabelę param-id**

`che168-param-map.php` zwraca `[28=>'Długość', 31=>'Rozstaw osi', 90=>'Typ energii', 91=>'Segment', ...]` (start ~20 ID z reconu, reszta dorabiana w fazie obserwacji). Adapter konwertuje `extra.configuration` → płaski `extra_prep` (klucz = label PL ze param-mapy lub `param_{id}`, wartość = surowa CN do dalszego tłumaczenia przez `translateExtraPrep`). City: `explode(', ', $raw['address'] ?? '')[0]`.

- [ ] **Krok 2: Podpiąć adapter w `ajaxPreview`/`ajaxImport` dla Che168**

W `class-asiaauto-admin-manual-import.php`: po `getOffer($source,$iid)` i `extractData()`, gdy `$source==='che168'` → `$data = AsiaAuto_Che168_Adapter::normalize($data);`. `parseInput()`: dodać regexy `che168.com` URL + numer (zakres 8-cyfrowy 43-56M nie koliduje z dongchedi 14-24M).

- [ ] **Krok 3: `require_once` + `php -l`**

`asiaauto-sync.php`: `require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-che168-adapter.php';`
Run: `php -l` obu plików → `No syntax errors`.

- [ ] **Krok 4: Smoke — adapter na 5 numerach**

Rozszerzyć smoke: surowy Che168 → `normalize()` → sprawdzić obecność `city`, `vin`, `first_registration`, niepusty `extra_prep`, `images` (host `2sc2.autoimg.cn`).
Expected: 5/5 mają vin+first_registration (spec: 100% coverage), city wyekstrahowane, extra_prep niepusty.

- [ ] **Krok 5: Commit**
```bash
git commit -am "[import:] T-185 krok 4 — adapter Che168 (address→city, extra.configuration→extra_prep, vin/first_registration, obrazy permanent)"
```

---

## Task 5 — Pełna tabela zgodności w podglądzie (symulacja title/slug/terms/meta/extra_prep)

**Files:**
- Modify: `includes/class-asiaauto-admin-manual-import.php` (`ajaxPreview` + JS `renderPreview`)

**Interfaces:**
- Konsumuje: `buildPlan()` (Task 2), `resolveForSource()` (Task 3), adapter (Task 4), `translateExtraPrep()` (istnieje, zwraca pogrupowane `[cat_id=>['label','items'=>[['label','value']]]]`).
- Produkuje: rozszerzony JSON `ajaxPreview` o klucze `plan` (z `buildPlan`), `extra_prep_translated` (z `translateExtraPrep`).

- [ ] **Krok 1: Rozszerzyć `ajaxPreview` o pełen plan**

Po zbudowaniu `$data` (z adapterem dla Che168) dołożyć do `wp_send_json_success`:
```php
$importer = new AsiaAuto_Importer(new AsiaAuto_Translator(), new AsiaAuto_Media());
$plan = $importer->buildPlan($data, $source);
$ep_translated = !empty($data['extra_prep']) && is_array($data['extra_prep'])
    ? (new AsiaAuto_Translator())->translateExtraPrep($data['extra_prep'])
    : [];
// ... 'plan' => $plan, 'extra_prep_translated' => $ep_translated,
```

- [ ] **Krok 2: Render — sekcja „Symulacja importu (dry-run)"**

W JS `renderPreview` dodać sekcje (nad przyciskami):
- **Tytuł i slug:** `plan.title`, `plan.slug_pattern` (z `{post_id}`).
- **Mapowanie:** `plan.mapped` → ✅ „trafia w hub /samochody/{mark}/{serie}/" lub ❌ „SIEROTA — model bez mapowania (kandydat do che168-model-map)".
- **Taksonomie:** tabela `plan.terms` — kolumny: taksonomia | wartość PL | slug | status (`exists` ✅ istniejący term / 🆕 zostanie utworzony). Pozwala wychwycić sieroty/duplikaty.
- **Meta:** tabela `plan.meta` (key→value) — wszystkie pola Motors Pro (mileage, vin_number, registration_date, stm_car_location, engine...).
- **Specyfikacja (extra_prep):** pełna tabela z `extra_prep_translated` pogrupowana po kategoriach (label kategorii + wiersze label→value), zamiast obecnego samego licznika (linia 276-279). Licznik zostaje jako nagłówek („N parametrów").

- [ ] **Krok 3: Smoke render w wp-admin**

Wkleić numer Che168 → podgląd pokazuje: dane pojazdu, ✅/❌ mapowanie, tabelę termów ze statusami, tabelę meta, pełen extra_prep pogrupowany, zdjęcia, kalkulację ceny. Diakrytyki poprawne (nie ASCII).

- [ ] **Krok 4: Commit**
```bash
git commit -am "[import:] T-185 krok 5 — pełna tabela zgodności w podglądzie (title/slug/terms/meta/extra_prep symulowane z buildPlan)"
```

---

## Task 6 — Log wdrożeniowy Che168 (dataset do kalibracji) + warunkowy import

**Files:**
- Create: `includes/class-asiaauto-che168-log.php`
- Modify: `includes/class-asiaauto-admin-manual-import.php` (przycisk + AJAX `asiaauto_che168_log` + warunek importu + widok listy logu)
- Modify: `asiaauto-sync.php` (require_once + bump wersji)

**Interfaces:**
- Produkuje: `AsiaAuto_Che168_Log::save(array $snapshot): string` (zwraca ścieżkę pliku), `::all(): array` (lista wpisów do widoku/analizy). Snapshot = `{saved_at, saved_by, inner_id, source, raw_data, plan, extra_prep_translated, would_import: bool, warnings}`. Pliki: `wp-content/uploads/asiaauto/che168-dryrun/<inner_id>-<timestamp>.json`.

- [ ] **Krok 1: Klasa logu**

`AsiaAuto_Che168_Log::save()` — `wp_mkdir_p` katalogu, `file_put_contents` JSON (pretty). `::all()` — `glob` + `json_decode`, sort po `saved_at`. Katalog poza indeksowaniem (dopisać `index.html` zaślepkę przy pierwszym zapisie). **To jest dataset, który Claude czyta `Read`-em podczas wspólnej analizy z Jankiem.**

- [ ] **Krok 2: Przycisk „Zapisz do logu wdrożeniowego Che168" + AJAX**

W renderze (Task 5), gdy `source==='che168'` i gate ON: obok/zamiast „Zaimportuj" pokazać `button` „💾 Zapisz do logu wdrożeniowego Che168". AJAX `asiaauto_che168_log` (nonce + gate `che168Allowed()`): odtwarza `getOffer`→adapter→`buildPlan` (nie ufa danym z klienta), zapisuje snapshot przez `Che168_Log::save()`, zwraca potwierdzenie + ścieżkę. Przycisk „Zaimportuj" dla Che168 renderowany **tylko gdy** `che168ImportEnabled()` (faza obserwacji = ukryty).

- [ ] **Krok 3: Widok listy zapisanych wpisów**

Pod formularzem (gdy gate ON): tabela `Che168_Log::all()` — inner_id | data | mark/serie | mapped ✅/❌ | #termów-sierot | link do JSON. Daje Jankowi i Claude przegląd „co zebrano" bez czytania plików ręcznie.

- [ ] **Krok 4: require_once + bump wersji + `php -l`**

`asiaauto-sync.php`: `require_once ... che168-log.php`; bump `ASIAAUTO_VERSION` (zweryfikować aktualną — header może mówić `0.29.0-wip`, realnie ~`0.32.74`; nadać kolejny minor np. `0.33.0`) + nagłówek `Version:`. Run: `php -l` wszystkich dotkniętych.

- [ ] **Krok 5: Smoke pełnej pętli**

Jako Janek: numer Che168 → podgląd (pełna tabela) → „Zapisz do logu" → wpis w `che168-dryrun/` + widoczny na liście. Brak przycisku importu (faza obserwacji). `Read` pliku JSON = czytelny snapshot do analizy.

- [ ] **Krok 6: Commit**
```bash
git commit -am "[import:] T-185 krok 6 — log wdrożeniowy Che168 (snapshot dry-run JSON + widok listy) + import za flagą; bump ASIAAUTO_VERSION"
```

---

## Faza obserwacji (po wdrożeniu Tasków 1-6) — wspólna kalibracja

Nie jest taskiem kodowym — to **pętla operacyjna** napędzająca dopracowanie mapowania:

1. Przez kilka dni Janek wkleja numery Che168 i klika „Zapisz do logu" dla przypadków wartych analizy (trafione + sieroty + dziwne).
2. Wspólny przegląd (Claude `Read`-uje `che168-dryrun/*.json` + widok listy): co weszłoby poprawnie, co zostało sierotą, które extra_prep `id` nie mają labela, które pola meta puste vs dongchedi.
3. Iteracyjne douzupełnianie: `che168-model-map.php` (sieroty marek), `che168-param-map.php` (brakujące `id`), `translations-extra-prep-values` (ogon wartości), reguły resolvera.
4. Gdy pokrycie zadowalające → decyzja Janka o włączeniu realnego importu: `define('ASIAAUTO_CHE168_IMPORT_ENABLED', true)` (odsłania przycisk „Zaimportuj" dla Che168). To moment domknięcia T-185; ewentualny automat = osobny T-186.

---

## Self-Review

- **Pokrycie spec T-185:** komponent 1 (model-map) → Task 3 + `che168-model-map.php`; komponent 2 (UI source) → Task 1+4+5; komponent 3 (adapter importera) → Task 4. ✅ Plus wymagania Janka: gate ukryty (Task 1), pełna tabela zgodności (Task 5), przycisk log + analiza (Task 6 + Faza obserwacji). ✅
- **Strefa krucha:** jedyny dotyk importera (Task 2) jest ekstrakcją bez zmiany zachowania + test regresji 0 różnic. ✅
- **Placeholdery:** kod kluczowych metod podany; smoke-skrypty z konkretnymi numerami z spec. Tabela `che168-param-map` startowo częściowa — świadomie dorabiana w Fazie obserwacji (zalogowane, nie ukryte). 
- **Spójność typów:** `buildPlan()` zwraca strukturę konsumowaną przez Task 5 (`plan`) i Task 6 (snapshot); `resolveForSource()` zwraca ten sam kształt co `getEuForCn` (4 klucze) → `buildPlan` agnostyczny względem źródła. ✅

---

## ⚠ Punkt świadomej zgody (przed startem)

**Task 2 refaktoruje `class-asiaauto-importer.php` — strefę kruchą (CLAUDE.md §3.2, §4).** Jest to konieczne, by symulacja dry-run była **identyczna** z realnym importem (inaczej weryfikowałbyś atrapę, co przeczy celowi kalibracji). Refaktor = czysta ekstrakcja `buildPlan()` bez zmiany zachowania, chroniona testem regresji dongchedi (0 różnic). Alternatywa (osobny symulator duplikujący logikę) jest **odrzucona** — drift sim↔real sabotowałby cel. Start Task 2 wymaga Twojej akceptacji tego dotyku.
