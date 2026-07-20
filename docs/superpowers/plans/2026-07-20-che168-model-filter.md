# Filtr modeli Che168 w konfiguratorze — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (inline). Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Osobne ustawienia importu `che168` w konfiguratorze (`asiaauto-import-config` → zakładka „Filtry importu") z blacklistą modeli per marka + domapowanie ~40 top orphanów 2024-2026.

**Architecture:** Nowa klasa-słownik (`AsiaAuto_Che168_Dictionary`, transient nad `getFilters`), addytywny blok w `isAllowedByConfig()`, rozszerzenie `renderFiltersTab()`/save o subtabs źródła i UI modeli, seed skrypt konfiguracji, wpisy w `data/che168-model-map.php` + `data/brand-mapping-v6.1.php`. Spec: `docs/superpowers/specs/2026-07-20-che168-model-filter-design.md`.

**Tech Stack:** WP admin (PHP 8.x), transients, wzorce istniejące w pluginie (hidden-input JSON jak `city_filter_cities`).

## Global Constraints

- **ŻADNEGO deployu na produkcję** — praca na kopiach w `tmp/che168-filter-staging/`; wgranie dopiero po „ok" Janka.
- **ŻADNYCH commitów** — commity wykonuje Janek.
- Strefa krucha: `importListing`/`buildPlan`/adapter **nietknięte**; w `class-asiaauto-importer.php` wolno dodać wyłącznie addytywny blok na końcu `isAllowedByConfig()`.
- Klucze modeli w storage i egzekwowaniu: **kanoniczne** `"Mark|Model"` (kształt Dongchedi, po `canonicalKeyForSource`).
- Dongchedi: zakładka wygląda i zapisuje się jak dotychczas (jedyna zmiana: subtabs nawigacji).
- Wersja docelowa przy deployu: `ASIAAUTO_VERSION` → **0.33.37** (bump dopiero na etapie deployu, nie w stagingu).
- Konwencja prefixów: `asiaauto_*` / `AsiaAuto_*`; teksty UI po polsku.

---

### Task 1: Staging — kopie plików źródłowych

**Files:**
- Create: `tmp/che168-filter-staging/` (katalog w repo, gitignored)
- Copy z serwera: `class-asiaauto-admin.php`, `class-asiaauto-importer.php`, `data/che168-model-map.php`, `data/brand-mapping-v6.1.php`

**Steps:**
- [ ] `mkdir -p ~/projekty/primaauto/tmp/che168-filter-staging/{includes,data,scripts}`
- [ ] `cp` czterech plików z `~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/{includes,data}/...` do stagingu (zachowując podkatalogi) + zapisz sumy md5 oryginałów do `tmp/che168-filter-staging/BASELINE.md5` (`md5sum` na plikach serwera) — przy deployu sprawdzimy, że serwer się nie zmienił pod nami.
- [ ] `php -l` na skopiowanych plikach (sanity, że kopiujemy zdrowe).

### Task 2: `AsiaAuto_Che168_Dictionary` (nowa klasa)

**Files:**
- Create: `tmp/che168-filter-staging/includes/class-asiaauto-che168-dictionary.php`

**Interfaces (Produces):**
- `AsiaAuto_Che168_Dictionary::get(): ?array` — surowy słownik `['mark' => [rawMark => ['model' => [...]]], ...]` lub null gdy API padło i brak cache.
- `AsiaAuto_Che168_Dictionary::refresh(): ?array` — wymusza pobranie, czyści oba transienty.
- `AsiaAuto_Che168_Dictionary::canonicalized(): array` — `[canonMark => [ ['key'=>'Mark|Model','raw'=>'rawMark|rawModel','mapped'=>bool], ... ]]`, posortowane po kluczu, dedup po `key`.
- `AsiaAuto_Che168_Dictionary::fetchedAt(): ?string` — data ISO ostatniego pobrania.

**Implementation (pełny plik):**

```php
<?php
/**
 * Słownik marka→modele Che168 z getFilters, cache w transientach.
 * Konsument AsiaAuto_API + AsiaAuto_Mapping — zero dotykania strefy kruchej.
 * Klucze kanoniczne = kształt brand-mappingu (jak dane po adapterze che168).
 */

if (!defined('ABSPATH')) {
    exit;
}

class AsiaAuto_Che168_Dictionary {

    private const TRANSIENT_RAW   = 'asiaauto_che168_filters_dict';
    private const TRANSIENT_CANON = 'asiaauto_che168_dict_canon';
    private const TTL             = 7 * DAY_IN_SECONDS;

    public static function get(): ?array {
        $cached = get_transient(self::TRANSIENT_RAW);
        if (is_array($cached) && !empty($cached['mark'])) {
            return $cached;
        }
        return self::refresh();
    }

    public static function refresh(): ?array {
        if (!defined('ASIAAUTO_API_KEY') || !defined('ASIAAUTO_API_URL')) {
            return null;
        }
        $api  = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_URL);
        $data = $api->getFilters('che168');
        if (empty($data['mark']) || !is_array($data['mark'])) {
            return null;
        }
        $data['_fetched_at'] = gmdate('c');
        set_transient(self::TRANSIENT_RAW, $data, self::TTL);
        delete_transient(self::TRANSIENT_CANON);
        return $data;
    }

    public static function fetchedAt(): ?string {
        $raw = get_transient(self::TRANSIENT_RAW);
        return is_array($raw) ? ($raw['_fetched_at'] ?? null) : null;
    }

    /**
     * Pary raw przepuszczone przez canonicalKeyForSource + status huba.
     * [canonMark => [ ['key'=>'Mark|Model','raw'=>'raw|raw','mapped'=>bool], ... ]]
     */
    public static function canonicalized(): array {
        $cached = get_transient(self::TRANSIENT_CANON);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }
        $raw = self::get();
        if ($raw === null) {
            return [];
        }
        $out = [];
        foreach ($raw['mark'] as $cheMark => $info) {
            foreach (($info['model'] ?? []) as $cheModel) {
                [$cMark, $cModel] = AsiaAuto_Mapping::canonicalKeyForSource((string) $cheMark, (string) $cheModel, '', 'che168');
                $key = $cMark . '|' . $cModel;
                if (isset($out[$cMark][$key])) {
                    continue;
                }
                $out[$cMark][$key] = [
                    'key'    => $key,
                    'raw'    => $cheMark . '|' . $cheModel,
                    'mapped' => AsiaAuto_Mapping::getEuForCn($cMark, $cModel) !== null,
                ];
            }
        }
        ksort($out);
        foreach ($out as &$models) {
            ksort($models);
            $models = array_values($models);
        }
        unset($models);
        set_transient(self::TRANSIENT_CANON, $out, self::TTL);
        return $out;
    }
}
```

**Steps:**
- [ ] Zapisz plik jw.
- [ ] `php -l tmp/che168-filter-staging/includes/class-asiaauto-che168-dictionary.php` → `No syntax errors`.
- [ ] Sprawdź w `asiaauto-sync.php` na serwerze, jak ładowane są klasy (require listy) — dopisz linijkę require do notatki deployowej (Task 7), NIE do serwera. Sprawdź też, że `ASIAAUTO_API_URL` istnieje w wp-config (grep) — jeśli stała ma inną nazwę, popraw w klasie.

### Task 3: `isAllowedByConfig()` — blok blacklisty (edycja addytywna)

**Files:**
- Modify: `tmp/che168-filter-staging/includes/class-asiaauto-importer.php` (metoda `isAllowedByConfig`, po bloku city_filter, przed `return true;`)

**Edit (dokładny):** przed końcowym `return true;` metody wstaw:

```php
        if (!empty($source_config['model_blacklist']) && is_array($source_config['model_blacklist'])) {
            $mark = (string) ($data['mark'] ?? '');
            if ($mark !== '' && !empty($source_config['model_blacklist'][$mark])) {
                $key = $mark . '|' . (string) ($data['model'] ?? '');
                if (in_array($key, (array) $source_config['model_blacklist'][$mark], true)) {
                    return false;
                }
            }
        }
```

**Steps:**
- [ ] Wykonaj edit.
- [ ] `php -l` → pass.
- [ ] `diff` staging vs oryginał → w diffie WYŁĄCZNIE powyższy blok (nic więcej w pliku strefy kruchej).
- [ ] Do checklisty smoke po deployu (Task 7): test przez Reflection w `wp eval` — oferta `['mark'=>'BYD','model'=>'Seagull',...]` z blacklistą `['BYD'=>['BYD|Seagull']]` → false; bez blacklisty → true; config dongchedi (bez klucza) → zachowanie identyczne jak przed zmianą.

### Task 4: Konfigurator — subtabs + UI modeli + save + refresh

**Files:**
- Modify: `tmp/che168-filter-staging/includes/class-asiaauto-admin.php`

**4a. Save handler (sekcja `filters`, po bloku `city_filter_cities`, w budowie `$config[$source]`):**

```php
            $raw_blacklist   = json_decode(wp_unslash($_POST['model_blacklist'] ?? '{}'), true);
            $model_blacklist = [];
            foreach ((is_array($raw_blacklist) ? $raw_blacklist : []) as $bl_mark => $bl_models) {
                $bl_mark = sanitize_text_field((string) $bl_mark);
                $clean   = array_values(array_filter(array_map(
                    fn($m) => sanitize_text_field((string) $m),
                    (array) $bl_models
                )));
                if ($bl_mark !== '' && $clean !== []) {
                    $model_blacklist[$bl_mark] = $clean;
                }
            }
```

i w tablicy `$config[$source]` nowa pozycja: `'model_blacklist' => $model_blacklist,`.

**4b. Subtabs źródła w `renderPage()`** — pod istniejącym `nav-tab-wrapper`, tylko gdy `$tab === 'filters'`:

```php
            <?php if ($tab === 'filters') : ?>
            <ul class="subsubsub" style="margin:-10px 0 20px;">
                <li><a href="<?php echo esc_url(add_query_arg(['tab' => 'filters', 'source' => 'dongchedi'])); ?>"
                       class="<?php echo $source === 'dongchedi' ? 'current' : ''; ?>">Dongchedi</a> |</li>
                <li><a href="<?php echo esc_url(add_query_arg(['tab' => 'filters', 'source' => 'che168'])); ?>"
                       class="<?php echo $source === 'che168' ? 'current' : ''; ?>">Che168</a></li>
            </ul>
            <?php endif; ?>
```

**4c. `renderFiltersTab()` — marki per źródło.** Na początku metody, zamiast bezwarunkowego `get_terms`:

```php
        $dict_models = [];   // canonMark => lista modeli (che168)
        $mark_names  = [];   // nazwy marek do checkboxów
        $mark_counts = [];   // markName => label licznika (dongchedi: count termu)
        if ($source === 'che168') {
            $dict = AsiaAuto_Che168_Dictionary::canonicalized();
            $dict_models = $dict;
            $mark_names  = array_keys($dict);
            foreach ($dict as $dm => $models) {
                $mark_counts[$dm] = count($models) . ' modeli';
            }
        } else {
            $terms = get_terms(['taxonomy' => 'make', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
            foreach ((is_wp_error($terms) ? [] : $terms) as $term) {
                $mark_names[]              = $term->name;
                $mark_counts[$term->name]  = (string) $term->count;
            }
        }
```

Pętla checkboxów marek iteruje po `$mark_names` (label licznika z `$mark_counts`) — dla dongchedi wynik HTML identyczny jak dziś.

**4d. Blok modeli (tylko `che168`), po wierszu „Marki" w tabeli:**

```php
        <?php if ($source === 'che168') :
            $model_blacklist = $sc['model_blacklist'] ?? [];
            $fetched = AsiaAuto_Che168_Dictionary::fetchedAt();
        ?>
                <tr>
                    <th>Modele — wykluczenia<br><small style="font-weight:normal;color:#666;">(blacklista per marka)</small></th>
                    <td>
                        <input type="hidden" name="model_blacklist" id="aa-model-blacklist-input"
                               value="<?php echo esc_attr(wp_json_encode($model_blacklist) ?: '{}'); ?>">
                        <p class="description" style="margin-bottom:8px;">
                            Wszystkie modele zaznaczonej marki wchodzą; odhacz <strong>niesprzedajne</strong>, żeby je wykluczyć.
                            Nowe modele giełdy przechodzą automatycznie. Ręczny import per numer <strong>omija</strong> blacklistę.
                            🆕 = model bez huba (orphan — trafi do mapowania, patrz T-186).
                            Słownik z API: <?php echo $fetched ? esc_html(mysql2date('Y-m-d H:i', $fetched)) : 'brak'; ?>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=asiaauto_che168_dict_refresh'), 'asiaauto_che168_dict')); ?>"
                               class="button button-small" style="margin-left:6px;">Odśwież słownik</a>
                        </p>
                        <div id="aa-models-container" style="max-height:500px;overflow-y:auto;border:1px solid #ddd;padding:10px;">
                            <?php if ($dict_models === []) : ?>
                                <em>Brak słownika (API niedostępne?). Zapisane wykluczenia pozostają aktywne.</em>
                            <?php endif; ?>
                            <?php foreach ($dict_models as $dm => $models) : ?>
                                <details class="aa-models-group" data-mark="<?php echo esc_attr($dm); ?>"
                                         style="margin-bottom:6px;<?php echo in_array($dm, $selected_marks, true) ? '' : 'display:none;'; ?>">
                                    <summary style="cursor:pointer;font-weight:600;">
                                        <?php echo esc_html($dm); ?>
                                        <small style="color:#999;">(<?php echo count($models); ?> modeli,
                                            <span class="aa-bl-count" data-mark="<?php echo esc_attr($dm); ?>">0</span> wykluczonych)</small>
                                    </summary>
                                    <div style="padding:6px 0 4px 18px;column-count:3;column-gap:20px;">
                                        <?php foreach ($models as $m) : ?>
                                            <label style="display:block;margin-bottom:2px;break-inside:avoid;font-size:12px;">
                                                <input type="checkbox" class="aa-model-bl" data-mark="<?php echo esc_attr($dm); ?>"
                                                       value="<?php echo esc_attr($m['key']); ?>">
                                                <?php echo esc_html(explode('|', $m['key'], 2)[1] ?? $m['key']); ?>
                                                <?php if (!$m['mapped']) : ?><span title="brak huba">🆕</span><?php endif; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
        <?php endif; ?>
```

**4e. JS synchronizacji (obok istniejących skryptów zakładki):** inicjalizacja checkboxów z hidden input, aktualizacja JSON przy zmianie, pokazywanie/ukrywanie grup `details` przy (od)zaznaczaniu marki:

```php
        <?php if ($source === 'che168') : ?>
        <script>
        (function() {
            var input = document.getElementById('aa-model-blacklist-input');
            var bl = {};
            try { bl = JSON.parse(input.value || '{}') || {}; } catch (e) { bl = {}; }

            function refreshCounts() {
                document.querySelectorAll('.aa-bl-count').forEach(function(el) {
                    var mark = el.getAttribute('data-mark');
                    el.textContent = (bl[mark] || []).length;
                });
            }
            function syncInput() {
                var clean = {};
                Object.keys(bl).forEach(function(k) { if ((bl[k] || []).length) { clean[k] = bl[k]; } });
                input.value = JSON.stringify(clean);
                refreshCounts();
            }
            document.querySelectorAll('.aa-model-bl').forEach(function(cb) {
                var mark = cb.getAttribute('data-mark');
                cb.checked = (bl[mark] || []).indexOf(cb.value) !== -1;
                cb.addEventListener('change', function() {
                    bl[mark] = bl[mark] || [];
                    var i = bl[mark].indexOf(cb.value);
                    if (cb.checked && i === -1) { bl[mark].push(cb.value); }
                    if (!cb.checked && i !== -1) { bl[mark].splice(i, 1); }
                    syncInput();
                });
            });
            function toggleGroups() {
                var checked = {};
                document.querySelectorAll('#aa-marks-container input[type=checkbox]:checked')
                    .forEach(function(cb) { checked[cb.value] = true; });
                document.querySelectorAll('.aa-models-group').forEach(function(g) {
                    g.style.display = checked[g.getAttribute('data-mark')] ? '' : 'none';
                });
            }
            document.querySelectorAll('#aa-marks-container input[type=checkbox]').forEach(function(cb) {
                cb.addEventListener('change', toggleGroups);
            });
            toggleGroups();
            refreshCounts();
        })();
        </script>
        <?php endif; ?>
```

**4f. Handler odświeżenia słownika** — w `__construct` klasy admin: `add_action('admin_post_asiaauto_che168_dict_refresh', [$this, 'handleChe168DictRefresh']);`, nowa metoda:

```php
    public function handleChe168DictRefresh(): void {
        if (!current_user_can(AsiaAuto_Security::IMPORT_CAP)) {
            wp_die('Brak uprawnień.');
        }
        check_admin_referer('asiaauto_che168_dict');
        $ok = AsiaAuto_Che168_Dictionary::refresh() !== null;
        $url = add_query_arg([
            'post_type' => 'listings',
            'page'      => $this->page_slug,
            'tab'       => 'filters',
            'source'    => 'che168',
            'dict'      => $ok ? 'ok' : 'fail',
        ], admin_url('edit.php'));
        wp_safe_redirect($url);
        exit;
    }
```

(Weryfikacja capability/nonce: wzorzec jak `ajaxBulkRecalc`. Parametry `add_query_arg` dopasować do faktycznego URL strony — sprawdzić `add_submenu_page` w kodzie.)

**Steps:**
- [ ] Wykonaj edits 4a-4f na kopii stagingowej.
- [ ] `php -l` → pass.
- [ ] Harness renderu bez WP niemożliwy — weryfikacja wizualna po deployu (Task 7 smoke). Przejrzyj diff całości: zmiany tylko w `renderPage` (subtabs), `saveConfig` (blacklist), `renderFiltersTab` (marki per źródło + blok modeli + JS), nowa metoda handlera. Zero zmian w zakładce cen.

### Task 5: Seed konfiguracji che168

**Files:**
- Create: `tmp/che168-filter-staging/scripts/seed-che168-config.php` (docelowo `scripts/` w repo)

**Implementation (pełny plik):**

```php
<?php
/**
 * Seed asiaauto_import_config['che168'] — jednorazowy, idempotentny.
 * Uruchomienie (przy deployu, po akcepcie Janka):
 *   wp eval-file scripts/seed-che168-config.php
 * Kopia limitów/miast z dongchedi; marki = przecięcie z markami kanonicznego
 * słownika che168; blacklista pusta; enabled=true.
 */
$config = get_option('asiaauto_import_config', []);
if (!empty($config['che168'])) {
    echo "che168 config już istnieje — nic nie robię.\n";
    return;
}
$dc = $config['dongchedi'] ?? [];
if (empty($dc['marks'])) {
    echo "BRAK configu dongchedi — przerwane.\n";
    return;
}
$dict = AsiaAuto_Che168_Dictionary::canonicalized();
if ($dict === []) {
    echo "BRAK słownika che168 (API?) — przerwane.\n";
    return;
}
$marks = array_values(array_intersect($dc['marks'], array_keys($dict)));
$config['che168'] = [
    'enabled'             => true,
    'marks'               => $marks,
    'year_from'           => $dc['year_from'] ?? 2024,
    'year_to'             => $dc['year_to'] ?? null,
    'km_from'             => $dc['km_from'] ?? null,
    'km_to'               => $dc['km_to'] ?? 40000,
    'price_from'          => $dc['price_from'] ?? 85000,
    'price_to'            => $dc['price_to'] ?? null,
    'city_filter_enabled' => !empty($dc['city_filter_enabled']),
    'city_filter_cities'  => $dc['city_filter_cities'] ?? [],
    'model_blacklist'     => [],
];
update_option('asiaauto_import_config', $config);
echo 'Seed OK: ' . count($marks) . " marek, limity z dongchedi, blacklista pusta, enabled=true.\n";
```

**Steps:**
- [ ] Zapisz plik, `php -l` → pass.

### Task 6: Domapowania top orphanów 2024-2026

**Files:**
- Modify: `tmp/che168-filter-staging/data/che168-model-map.php` (aliasy → istniejące huby)
- Modify: `tmp/che168-filter-staging/data/brand-mapping-v6.1.php` (realnie nowe modele; komentarz v6.3 w headerze z datą i listą)
- Create: `tmp/che168-filter-staging/scripts/validate-mappings.php` (harness walidacyjny, standalone)

**Proces (zasada „utwórz vs przemapuj", addytywnie na końcu plików z komentarzem `// --- T-186 domapowania 2026-07-20 ---`):**
- [ ] Dla każdego z top ~40 orphanów z `che168-report-2024.json` (`top_orphans`): sprawdź w stagingowym brand-mappingu, czy istnieje hub docelowy (grep po `serie_eu`/kluczu). Istnieje → wpis-alias w `che168-model-map.php` (klucz = raw `"CheMark|CheModel"`, wartość = 4 pola jak istniejący hub). Nie istnieje, a model realny/nowy → wpis w `brand-mapping-v6.1.php` (klucz kanoniczny, nowy czysty hub).
- [ ] Kandydaci ze znanym kierunkiem (zweryfikować każdy przed wpisem): `Leapmotor|Leapmotor T03`→Leapmotor/T03; `Li Auto|Li L7`/`Li L8`→Li Auto/L7,L8 (wzorzec istniejącego `Li Auto|Li L9`); `smart|Smart #1`/`#3`→Smart/#1,#3; `Jetour|Traveller`→Jetour/Shanhai? sprawdzić właściwy hub (Traveller=旅行者); `Xiaomi|小米YU7`→NOWY Xiaomi/YU7; `iCAR|iCAR 超级V23`→iCAR/V23 (przemapuj wg zasady konsolidacji); `Shanhai|捷途旅行者C-DM`→Jetour Shanhai; `Shanhai|Shanhai T1`→Jetour Shanhai/T1?; `eπ|eπ007`→Dongfeng/eπ007 (wzorzec eπ008 — jeśli hub nie istnieje → brand-mapping); `Wey|Wey Gaoshan`→WEY/Gaoshan?; `Beijing Off-Road|BJ40`/`BJ30`→BAIC/BJ40,BJ30 (wzorzec BJ60); `Fang Cheng Bao|钛7`/`Titanium 3`→Fangchengbao Tai 7 / Bao 3?; `Tank|Tank 700 New Energy`→Tank/700 (konsolidacja jak Tank 300 New Energy); `Galaxy|Galaxy Xingyao 8`→Geely Galaxy/Xingyao 8?; `Exeed|Xingjiyuan ET`→Exeed/Sterra ET?; `Geometry|Geometry E Firefly`→Geometry/E?
- [ ] **Celowo BEZ mapowania** (ICE/poza segmentem — kandydaci na blacklistę): `Mazda|3 Axela`, `Nissan|Qashqai`, `MG|MG5` i analogiczne spalinowe.
- [ ] Harness `validate-mappings.php` (standalone, bez WP): `include` obu stagingowych plików data; asserty: (1) parsują się i zwracają array; (2) każdy wpis ma 4 pola `mark_eu/serie_eu/title_eu/slug`, niepuste; (3) brak duplikatów kluczy (w pliku i między plikami dla che168-model-map vs poprzednia wersja); (4) slug pattern `[a-z0-9-]+`; (5) dla aliasów: para `(mark_eu, serie_eu)` występuje też w brand-mappingu (hub istnieje). Wynik: lista PASS/FAIL per check.
- [ ] `php scripts/validate-mappings.php` → wszystkie PASS; `php -l` oba pliki data.

### Task 7: Diffy, raport, checklist deployu

**Files:**
- Create: diffy per plik w `tmp/che168-filter-staging/diffs/`
- Create: `tmp/che168-filter-staging/DEPLOY.md` (checklist)
- Drop na auratest: diffy + DEPLOY.md

**Steps:**
- [ ] `diff -u <serwer> <staging>` per plik zmodyfikowany → `diffs/*.diff`; nowe pliki w całości.
- [ ] `DEPLOY.md`: (1) verify `md5sum -c BASELINE.md5` (serwer niezmieniony); (2) `.bak` z datą per plik; (3) kopiowanie plików + require nowej klasy w `asiaauto-sync.php` (dokładna linia wg Task 2) + bump `ASIAAUTO_VERSION` → 0.33.37; (4) `php -l` na serwerze; (5) `wp eval-file scripts/seed-che168-config.php`; (6) smoke: zakładka Filtry oba źródła, zapis blacklisty round-trip, test Reflection `isAllowedByConfig` (kod z Task 3), dry-run che168 per numer smoke (57877387 — blacklista NIE blokuje ręcznego), dry-run numerów domapowanych orphanów → trafiają w huby; (7) `docs/VERSIONS.md` + strona postępu Ruslana (memory: dopis po każdej sesji).
- [ ] Drop diffów + DEPLOY.md na `https://auratest.pl/fe4f58fec53ctmp/primaauto-che168-filtr-modeli-diff-2026-07-20/...`.
- [ ] Raport dla Janka: co gotowe, linki, co czeka na „ok".

### Task 8: Przeglądarka „Przeglądaj Che168" (ETAP 2 — zaakceptowany po mockupie 2026-07-20)

> Wykonać po review/deployu etapu 1 (Task 1-7). Mockup: `https://auratest.pl/fe4f58fec53ctmp/primaauto-che168-przegladarka-mockup-2026-07-20.html`

**Files:**
- Create: `tmp/che168-filter-staging/includes/class-asiaauto-admin-che168-browse.php`
- Przy deployu: require w `asiaauto-sync.php` (po require che168-dictionary) + bump `ASIAAUTO_VERSION` → 0.33.38

**Interfaces:**
- Consumes: `AsiaAuto_Che168_Dictionary::canonicalized()/get()`, `AsiaAuto_API::getOffers('che168', …)`, istniejące akcje AJAX `asiaauto_che168_preview` / `asiaauto_che168_import` (nonce `asiaauto_che168` — strona browse rejestruje własny nonce o tej samej nazwie akcji), `translations-cities.php`, `asiaauto_import_config['che168']` (defaulty filtrów + blacklista do badge).
- Produces: podstrona `edit.php?post_type=listings&page=asiaauto-che168-browse`, AJAX `asiaauto_che168_browse`.

**Implementation (szkielet klasy — pełny kod):**

```php
<?php
/**
 * Przeglądaj Che168 — przeglądarka ofert giełdy z filtrami marka/model/rocznik/miasto.
 * Czysty konsument: API + Dictionary + istniejące AJAX preview/import z „Dodaj z Che168".
 * Strefa krucha nietknięta.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AsiaAuto_Admin_Che168_Browse {

    private const PAGE = 'asiaauto-che168-browse';

    public function __construct() {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('wp_ajax_asiaauto_che168_browse', [$this, 'ajaxBrowse']);
    }

    public function addMenuPage(): void {
        add_submenu_page(
            'edit.php?post_type=listings',
            'Przeglądaj Che168',
            'Przeglądaj Che168',
            AsiaAuto_Security::IMPORT_CAP,
            self::PAGE,
            [$this, 'renderPage']
        );
    }

    public function ajaxBrowse(): void {
        check_ajax_referer('asiaauto_che168', '_nonce');
        if (!current_user_can(AsiaAuto_Security::IMPORT_CAP)) wp_send_json_error('Brak dostępu.');

        $mark      = sanitize_text_field(wp_unslash($_POST['mark'] ?? ''));
        $models    = array_filter(array_map('sanitize_text_field', (array) ($_POST['models'] ?? [])));
        $year_from = (int) ($_POST['year_from'] ?? 0);
        $year_to   = (int) ($_POST['year_to'] ?? 0);
        $price_min = (int) ($_POST['price_min'] ?? 0);
        $only_our  = !empty($_POST['only_our_cities']);
        $page      = max(1, (int) ($_POST['page'] ?? 1));
        if ($mark === '') wp_send_json_error('Wybierz markę.');

        if (!defined('ASIAAUTO_API_KEY') || !defined('ASIAAUTO_API_BASE_URL')) wp_send_json_error('Brak konfiguracji API.');
        $api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);

        $cities_pl = include ASIAAUTO_PLUGIN_DIR . 'data/translations-cities.php';
        $config    = get_option('asiaauto_import_config', []);
        $our_zh    = array_flip(array_column($config['che168']['city_filter_cities'] ?? [], 'zh'));
        $blacklist = $config['che168']['model_blacklist'] ?? [];

        // API przyjmuje jeden model per zapytanie — bez modeli: 1 zapytanie; z modelami: po jednym per model.
        $queries = $models === [] ? [null] : $models;
        $out = [];
        $next_page = null;
        foreach ($queries as $model) {
            $params = ['page' => $page, 'mark' => $mark];
            if ($model !== null) $params['model'] = $model;
            if ($year_from > 0)  $params['year_from'] = $year_from;
            if ($year_to > 0)    $params['year_to'] = $year_to;
            $resp = $api->getOffers('che168', $params);
            if (!$resp || empty($resp['result'])) continue;
            if (!empty($resp['meta']['next_page'])) $next_page = (int) $resp['meta']['next_page'];
            foreach ($resp['result'] as $row) {
                $o = $row['data'] ?? null;
                if (!$o || empty($o['inner_id'])) continue;
                if ($price_min > 0 && (int) ($o['price'] ?? 0) < $price_min) continue;
                $city = trim(explode(',', (string) ($o['address'] ?? ''))[0]);
                $is_our = isset($our_zh[$city]);
                if ($only_our && !$is_our) continue;
                [$cMark, $cModel] = AsiaAuto_Mapping::canonicalKeyForSource((string) $o['mark'], (string) $o['model'], (string) ($o['engine_type'] ?? ''), 'che168');
                $imgs = $o['images'] ?? [];
                if (is_string($imgs)) $imgs = json_decode($imgs, true) ?: [];
                $out[(string) $o['inner_id']] = [
                    'inner_id' => (string) $o['inner_id'],
                    'mark'     => (string) $o['mark'],
                    'model'    => (string) $o['model'],
                    'year'     => (int) ($o['year'] ?? 0),
                    'reg'      => (string) ($o['first_registration'] ?? ''),
                    'price'    => (int) ($o['price'] ?? 0),
                    'km'       => (int) ($o['km_age'] ?? 0),
                    'fuel'     => (string) ($o['engine_type'] ?? ''),
                    'city_pl'  => $cities_pl[$city] ?? $city,
                    'city_our' => $is_our,
                    'img'      => (string) ($imgs[0] ?? ''),
                    'url'      => (string) ($o['url'] ?? ''),
                    'mapped'   => AsiaAuto_Mapping::getEuForCn($cMark, $cModel) !== null,
                    'blacklisted' => in_array($cMark . '|' . $cModel, (array) ($blacklist[$cMark] ?? []), true),
                    'exists'   => false, // uzupełniane niżej
                ];
            }
        }
        // czy już w WP (findByInnerId per sztuka — max ~20-60 na stronę)
        $importer = new AsiaAuto_Importer(new AsiaAuto_Translator(), new AsiaAuto_Media());
        foreach ($out as &$o) { $o['exists'] = (bool) $importer->findByInnerId($o['inner_id'], 'che168'); }
        unset($o);

        wp_send_json_success(['offers' => array_values($out), 'next_page' => $next_page]);
    }

    public function renderPage(): void {
        $dict   = AsiaAuto_Che168_Dictionary::canonicalized();
        $config = get_option('asiaauto_import_config', []);
        $sc     = $config['che168'] ?? [];
        // UI: selecty marka (pełny słownik, count modeli) / chipy modeli (🆕 gdy !mapped) /
        // rocznik od (default $sc['year_from']) / miasto (całe Chiny | nasza lista) / cena od (default $sc['price_from']) /
        // grid kart + „Załaduj więcej" (page=next_page) — markup i JS 1:1 z mockupu
        // (auratest primaauto-che168-przegladarka-mockup-2026-07-20.html), z fetch → ajaxBrowse
        // oraz akcjami Podgląd/Importuj wołającymi asiaauto_che168_preview / asiaauto_che168_import
        // (nonce wp_create_nonce('asiaauto_che168')). Podgląd otwiera modal z tabelą planu jak
        // w „Dodaj z Che168" (renderPreview – skopiować funkcję JS, endpoint zwraca te same pola).
        // Kod przeniesiony z mockupu przy implementacji Task 8 — bez zmian koncepcyjnych.
    }
}
```

**Steps:**
- [ ] Utwórz plik jw. + przenieś markup/JS z mockupu do `renderPage()` (fetch na `ajaxurl`, akcje browse/preview/import, nonce `asiaauto_che168`).
- [ ] `php -l` → pass.
- [ ] Diff/deploy analogicznie do etapu 1 (`.bak`, require w `asiaauto-sync.php`, bump 0.33.38, smoke: wybór marki spoza naszej listy → modele 🆕 widoczne; filtr miast; Podgląd i Import z karty działają; oferta już zaimportowana pokazuje „w bazie").

## Self-Review (wykonany)

- Spec coverage: 4.1→Task 2, 4.2→Task 4a/5, 4.3→Task 4b-4f, 4.4→Task 3, 4.5→Task 5, 4.6→Task 6, sekcja 5 specu→Task 7. Komplet.
- Placeholdery: brak TBD; znaki zapytania w Task 6 to jawne polecenie weryfikacji per wpis (investigation step), nie dziura w planie.
- Spójność typów: `canonicalized()` konsumowane w Task 4c i Task 5 w tym samym kształcie `[mark => [ ['key','raw','mapped'] ]]`; klucz `model_blacklist` spójny Task 3/4a/4d/5.
