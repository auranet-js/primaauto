# Diagnostyka admin panel — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Admin-only diagnostic panel w `asiaauto-sync` plugin: rejestr 8 checków, scan po jednym kliknięciu, fixy z dry-run, dostęp z UI/AJAX/WP-CLI, rozszerzalność.

**Architecture:** Pluggable check registry (`AsiaAuto_Diag_Registry`) + interface `AsiaAuto_Diag_Check` + abstract base z helperami. Trzy konsumenci (Admin UI, AJAX, WP-CLI) wołają te same metody na klasach checków. Refaktor wybranych skryptów z `diag/` na eksportowane funkcje (zero shell-exec).

**Tech Stack:** PHP 8.1+, WordPress 6.x, WP-CLI, vanilla JS (bez React), CSS bez bundlera. Brak PHPUnit — smoke testing przez `php -l` + `wp eval` + `wp asiaauto diag run`.

**Spec source:** `docs/superpowers/specs/2026-04-28-diagnostyka-admin-panel-design.md`.

**Working tree:** Bezpośrednio na produkcji w `~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/` (zgodnie z workflow projektu — repo jest kontekstowe, source of truth = serwer). `.bak` przed każdą znaczącą edycją istniejącego pliku.

---

## Pre-flight

### Task 0: Backup + branch + version bump

**Files:**
- Modify: `~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/asiaauto-sync.php` (header version + define)

- [ ] **Step 1: Backup pluginu**

```bash
mkdir -p ~/backups/primaauto/2026-04-28-pre-diag
cp -r ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync ~/backups/primaauto/2026-04-28-pre-diag/
ls ~/backups/primaauto/2026-04-28-pre-diag/asiaauto-sync/asiaauto-sync.php
```
Expected: plik istnieje.

- [ ] **Step 2: Sprawdź czystość drzewa repo**

```bash
cd ~/projekty/primaauto && git status
```
Expected: tylko niesynchronizowane tmp/ i docs/QUEUE.md (wpis CLAUDE bootstrap), bez zmian w plugin source.

- [ ] **Step 3: Bump wersji pluginu 0.31.12 → 0.32.0**

W `~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/asiaauto-sync.php`:

Zmień linię 5 z:
```
 * Version: 0.31.12
```
na:
```
 * Version: 0.32.0-dev
```

Zmień linię 16 z:
```php
define('ASIAAUTO_VERSION', '0.31.12');
```
na:
```php
define('ASIAAUTO_VERSION', '0.32.0-dev');
```

Suffix `-dev` zdejmiemy w Task 17 po zielonych smoke testach.

- [ ] **Step 4: Sanity check**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/asiaauto-sync.php
```
Expected: `No syntax errors detected`.

```bash
wp --path=~/domains/primaauto.com.pl/public_html plugin get asiaauto-sync --field=version
```
Expected: `0.32.0-dev`.

- [ ] **Step 5: Commit (opcjonalny — Jan decyduje)**

Sygnalizuj userowi: bump zrobiony, wszystkie kolejne taski wstrzymują się przed commitami; finalny commit na końcu.

---

## Foundation: kontrakty

### Task 1: Interface + abstract base

**Files:**
- Create: `includes/diag-checks/interface-check.php`
- Create: `includes/diag-checks/class-check-base.php`

- [ ] **Step 1: Stwórz katalog**

```bash
mkdir -p ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/diag-checks
```
Expected: katalog utworzony.

- [ ] **Step 2: Stwórz interface**

Plik `includes/diag-checks/interface-check.php`:

```php
<?php
defined('ABSPATH') || exit;

interface AsiaAuto_Diag_Check {
    public function getId(): string;
    public function getLabel(): string;
    public function getCluster(): string;          // 'integrity' | 'seo-coverage'
    public function getEstimatedSeconds(): int;
    public function hasFix(): bool;
    public function getFixMode(): string;          // 'auto' | 'confirm' | 'per-item' | 'none'

    /**
     * @return array{
     *   status: 'ok'|'warn'|'error',
     *   count: int,
     *   issues: array,
     *   summary: string,
     *   duration_ms: int,
     *   ts: int
     * }
     */
    public function run(): array;

    /**
     * @return array{
     *   actions: array,
     *   estimated_changes: array,
     *   apply_token: string,
     *   ttl_seconds: int
     * }
     */
    public function previewFix(array $issue_ids): array;

    /**
     * @return array{
     *   applied: int,
     *   failed: int,
     *   errors: array
     * }
     */
    public function applyFix(array $issue_ids, string $apply_token): array;
}
```

- [ ] **Step 3: Stwórz abstract base**

Plik `includes/diag-checks/class-check-base.php`:

```php
<?php
defined('ABSPATH') || exit;

abstract class AsiaAuto_Diag_Check_Base implements AsiaAuto_Diag_Check {

    protected const TIME_LIMIT = 25;
    protected const TOKEN_TTL = 300;

    public function hasFix(): bool {
        return $this->getFixMode() !== 'none';
    }

    public function getFixMode(): string {
        return 'none';
    }

    public function previewFix(array $issue_ids): array {
        return [
            'actions' => [],
            'estimated_changes' => [],
            'apply_token' => '',
            'ttl_seconds' => 0,
        ];
    }

    public function applyFix(array $issue_ids, string $apply_token): array {
        return ['applied' => 0, 'failed' => 0, 'errors' => ['Fix not implemented']];
    }

    /**
     * Wywołaj na początku run() / applyFix().
     */
    protected function bootstrap(): void {
        if (function_exists('set_time_limit')) {
            @set_time_limit(self::TIME_LIMIT);
        }
    }

    /**
     * Generuje apply_token dla par (issue_ids hash, check_id).
     * Zapis w transient z TTL 300s.
     */
    protected function mintApplyToken(array $issue_ids): string {
        $token = wp_generate_password(32, false, false);
        $key = 'asiaauto_diag_token_' . $token;
        set_transient($key, [
            'check_id' => $this->getId(),
            'issue_ids_hash' => $this->hashIssueIds($issue_ids),
            'created' => time(),
        ], self::TOKEN_TTL);
        return $token;
    }

    /**
     * @return bool true gdy token żyje, ma ten check_id i pasujący hash issue_ids.
     */
    protected function validateApplyToken(string $token, array $issue_ids): bool {
        if ($token === '') return false;
        $data = get_transient('asiaauto_diag_token_' . $token);
        if (!is_array($data)) return false;
        if ($data['check_id'] !== $this->getId()) return false;
        if ($data['issue_ids_hash'] !== $this->hashIssueIds($issue_ids)) return false;
        delete_transient('asiaauto_diag_token_' . $token); // single use
        return true;
    }

    private function hashIssueIds(array $issue_ids): string {
        $sorted = array_map('strval', $issue_ids);
        sort($sorted);
        return md5(implode(',', $sorted));
    }

    /**
     * Standardowy log wpis przez AsiaAuto_Logger.
     */
    protected function log(string $level, string $event, array $context = []): void {
        if (!class_exists('AsiaAuto_Logger')) return;
        $context = array_merge(['check' => $this->getId()], $context);
        $msg = "[diag] {$event} " . wp_json_encode($context);
        switch ($level) {
            case 'error':   AsiaAuto_Logger::error($msg); break;
            case 'warning': AsiaAuto_Logger::warning($msg); break;
            default:        AsiaAuto_Logger::info($msg);
        }
    }

    /**
     * Helper: wrap run() z timingiem.
     */
    protected function timedRun(callable $fn): array {
        $start = microtime(true);
        $result = $fn();
        $result['duration_ms'] = (int) round((microtime(true) - $start) * 1000);
        $result['ts'] = time();
        return $result;
    }

    public function getEstimatedSeconds(): int {
        return 5;
    }
}
```

- [ ] **Step 4: Sanity check**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/diag-checks/interface-check.php
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/diag-checks/class-check-base.php
```
Expected: oba `No syntax errors detected`.

---

### Task 2: Registry

**Files:**
- Create: `includes/class-asiaauto-diag.php`

- [ ] **Step 1: Stwórz registry**

Plik `includes/class-asiaauto-diag.php`:

```php
<?php
defined('ABSPATH') || exit;

class AsiaAuto_Diag_Registry {

    private static ?AsiaAuto_Diag_Registry $instance = null;

    /** @var AsiaAuto_Diag_Check[] */
    private array $checks = [];

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->registerAll();
        }
        return self::$instance;
    }

    private function registerAll(): void {
        // Kolejność = kolejność prezentacji w UI w obrębie clustera.
        $this->add(new AsiaAuto_Check_Missing_Images());
        $this->add(new AsiaAuto_Check_Chinese_Chars());
        $this->add(new AsiaAuto_Check_Broken_Extra_Prep());
        $this->add(new AsiaAuto_Check_Duplicate_Listings());
        $this->add(new AsiaAuto_Check_Make_Without_Wiki());
        $this->add(new AsiaAuto_Check_Serie_Without_Wiki());
        $this->add(new AsiaAuto_Check_Listings_Without_Mapping());
        $this->add(new AsiaAuto_Check_Mapping_Without_Term());
    }

    public function add(AsiaAuto_Diag_Check $check): void {
        $this->checks[$check->getId()] = $check;
    }

    /** @return AsiaAuto_Diag_Check[] */
    public function all(): array {
        return $this->checks;
    }

    public function get(string $id): ?AsiaAuto_Diag_Check {
        return $this->checks[$id] ?? null;
    }

    /** @return array<string, AsiaAuto_Diag_Check[]> */
    public function byCluster(): array {
        $out = [];
        foreach ($this->checks as $c) {
            $out[$c->getCluster()][] = $c;
        }
        return $out;
    }

    /**
     * Zapisuje ostatni run w opcji.
     */
    public function recordRun(string $check_id, array $result): void {
        $opt = get_option('asiaauto_diag_last_runs', []);
        $opt[$check_id] = [
            'ts' => $result['ts'] ?? time(),
            'status' => $result['status'] ?? 'error',
            'count' => $result['count'] ?? 0,
            'duration_ms' => $result['duration_ms'] ?? 0,
        ];
        update_option('asiaauto_diag_last_runs', $opt, false);
    }

    public function getLastRun(string $check_id): ?array {
        $opt = get_option('asiaauto_diag_last_runs', []);
        return $opt[$check_id] ?? null;
    }
}
```

- [ ] **Step 2: Sanity check**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/class-asiaauto-diag.php
```
Expected: `No syntax errors detected`.

(Klasa załadowana zostanie przez `require_once` w bootstrapie w Task 15. Smoke test registry w Task 15.)

---

## Refaktor: diag scripts → funkcje

### Task 3: Refaktor 4 skryptów na funkcje eksportowane

**Cel:** Klasy checków będą wołać funkcje inline (bez shell-exec). Stary `wp eval-file diag/X.php` nadal działa (wywołuje funkcję na końcu pliku).

**Pliki do refaktoru** (czytamy obecne body, zamykamy w funkcji `asiaauto_diag_<id>($apply=false): array`):

- Modify: `diag/fix-missing-images.php`
- Modify: `diag/check-chinese-models.php` (wykorzystamy w `chinese-chars` check do scanu)
- Modify: `diag/fix-chinese-v23.php` (wykorzystamy do fixu)
- Modify: `diag/fix-broken-json-v25.php`
- Modify: `diag/cleanup-duplicates.php`

**Wzorzec refaktoru** (każdy plik analogicznie):

- [ ] **Step 1: Read existing script**

```bash
cat ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/diag/fix-missing-images.php
```
Expected: top-level PHP body. Zanotuj: zmienne globalne, zwracana wartość (echo / return / WP_CLI::log).

- [ ] **Step 2: Refaktor — opakuj w funkcję**

Wzorzec (przykład dla `fix-missing-images.php`):

```php
<?php
/**
 * Fix listings missing featured images.
 *
 * Wywołanie:
 *   wp eval-file diag/fix-missing-images.php           # dry-run
 *   APPLY=1 wp eval-file diag/fix-missing-images.php   # apply
 *   asiaauto_diag_missing_images($apply)               # programowe wywołanie
 */

defined('ABSPATH') || exit;

if (!function_exists('asiaauto_diag_missing_images')) {
    function asiaauto_diag_missing_images(bool $apply = false): array {
        // <oryginalne body przeniesione tu, $apply zastępuje sprawdzanie getenv('APPLY')>
        // Funkcja zwraca array:
        //   ['scanned' => N, 'fixed' => M, 'trashed' => K, 'failed' => L,
        //    'issues' => [['id' => post_id, 'label' => post_title, 'meta' => [...]]]]
        // ...
        return [
            'scanned' => $scanned,
            'fixed' => $fixed,
            'trashed' => $trashed,
            'failed' => $failed,
            'issues' => $issues,
        ];
    }
}

// Backward compat: wywołanie z `wp eval-file`.
if (defined('WP_CLI') && WP_CLI && !defined('ASIAAUTO_DIAG_INTERNAL_CALL')) {
    $apply = (bool) getenv('APPLY');
    $result = asiaauto_diag_missing_images($apply);
    WP_CLI::success(sprintf(
        'Scanned: %d, fixed: %d, trashed: %d, failed: %d',
        $result['scanned'], $result['fixed'], $result['trashed'], $result['failed']
    ));
}
```

Stała `ASIAAUTO_DIAG_INTERNAL_CALL` pozwala check class wywołać `require_once` bez triggera CLI bloku — w klasie ustawiamy `define('ASIAAUTO_DIAG_INTERNAL_CALL', 1)` przed `require_once`.

- [ ] **Step 3: php -l każdego refaktorowanego skryptu**

```bash
for f in fix-missing-images.php check-chinese-models.php fix-chinese-v23.php fix-broken-json-v25.php cleanup-duplicates.php; do
  php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/diag/$f
done
```
Expected: 5× `No syntax errors detected`.

- [ ] **Step 4: Smoke — backward compat działa**

```bash
wp --path=~/domains/primaauto.com.pl/public_html eval-file ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/diag/check-chinese-models.php
```
Expected: zachowanie identyczne jak przed refaktorem (raport bez błędu).

- [ ] **Step 5: Smoke — programowe wywołanie**

```bash
wp --path=~/domains/primaauto.com.pl/public_html eval 'require ABSPATH . "wp-content/plugins/asiaauto-sync/diag/fix-missing-images.php"; var_export(asiaauto_diag_missing_images(false));'
```
Expected: zwraca array ze strukturą jak w Step 2.

**Uwaga implementacyjna:** Nie modyfikuj istniejącej logiki — przenieś body 1:1, zmień tylko warunek apply z `getenv('APPLY')` na argument `$apply`. Test backward compat (Step 4) chroni przed regresją.

---

## Checki: implementacja

Wszystkie pliki w `includes/diag-checks/`. Wzorzec: każda klasa extends `AsiaAuto_Diag_Check_Base`, deklaruje metadata, `run()` woła SQL/skrypt diag, `applyFix()` (opcjonalnie) odpala fix.

### Task 4: Check #1 — missing-images

**Files:**
- Create: `includes/diag-checks/class-check-missing-images.php`

- [ ] **Step 1: Stwórz klasę**

Plik `includes/diag-checks/class-check-missing-images.php`:

```php
<?php
defined('ABSPATH') || exit;

class AsiaAuto_Check_Missing_Images extends AsiaAuto_Diag_Check_Base {

    public function getId(): string { return 'missing-images'; }
    public function getLabel(): string { return 'Listings bez zdjęć'; }
    public function getCluster(): string { return 'integrity'; }
    public function getEstimatedSeconds(): int { return 8; }
    public function getFixMode(): string { return 'auto'; }

    public function run(): array {
        $this->bootstrap();
        return $this->timedRun(function () {
            global $wpdb;
            $rows = $wpdb->get_results("
                SELECT p.ID, p.post_title
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm
                  ON pm.post_id = p.ID AND pm.meta_key = '_thumbnail_id'
                WHERE p.post_type = 'listings'
                  AND p.post_status IN ('publish', 'draft')
                  AND (pm.meta_value IS NULL OR pm.meta_value = '0' OR pm.meta_value = '')
                LIMIT 500
            ");

            $issues = [];
            foreach ($rows as $r) {
                $issues[] = [
                    'id' => (string) $r->ID,
                    'label' => $r->post_title ?: ('Listing #' . $r->ID),
                    'meta' => ['post_id' => (int) $r->ID],
                ];
            }
            $count = count($issues);
            return [
                'status' => $count === 0 ? 'ok' : 'warn',
                'count' => $count,
                'issues' => $issues,
                'summary' => $count === 0
                    ? 'Wszystkie listings mają zdjęcie wyróżniające.'
                    : sprintf('%d listings bez zdjęcia (limit 500).', $count),
            ];
        });
    }

    public function previewFix(array $issue_ids): array {
        $count = empty($issue_ids) ? $this->lastRunCount() : count($issue_ids);
        return [
            'actions' => [
                sprintf('Pobierz brakujące zdjęcia z Dongchedi cache dla %d listingów.', $count),
                'Listings z ghost-offer URL (404) zostaną przeniesione do trasha.',
            ],
            'estimated_changes' => ['fixed_or_trashed' => $count],
            'apply_token' => $this->mintApplyToken($issue_ids),
            'ttl_seconds' => self::TOKEN_TTL,
        ];
    }

    public function applyFix(array $issue_ids, string $apply_token): array {
        $this->bootstrap();
        if (!$this->validateApplyToken($apply_token, $issue_ids)) {
            return ['applied' => 0, 'failed' => 0, 'errors' => ['Token wygasł lub niepoprawny — odśwież preview.']];
        }

        if (!defined('ASIAAUTO_DIAG_INTERNAL_CALL')) {
            define('ASIAAUTO_DIAG_INTERNAL_CALL', 1);
        }
        require_once ASIAAUTO_PLUGIN_DIR . 'diag/fix-missing-images.php';

        $result = asiaauto_diag_missing_images(true);
        $applied = ($result['fixed'] ?? 0) + ($result['trashed'] ?? 0);
        $failed = $result['failed'] ?? 0;

        $this->log('info', 'apply', ['applied' => $applied, 'failed' => $failed]);
        return [
            'applied' => $applied,
            'failed' => $failed,
            'errors' => $failed > 0 ? ['Niektóre fixy nie powiodły się — patrz logs/diag.log'] : [],
        ];
    }

    private function lastRunCount(): int {
        $registry = AsiaAuto_Diag_Registry::instance();
        $last = $registry->getLastRun($this->getId());
        return $last['count'] ?? 0;
    }
}
```

- [ ] **Step 2: php -l**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/diag-checks/class-check-missing-images.php
```
Expected: `No syntax errors detected`.

(Smoke test w Task 15 po pełnym bootstrap — w izolacji nie da się odpalić.)

---

### Task 5: Check #2 — chinese-chars

**Files:**
- Create: `includes/diag-checks/class-check-chinese-chars.php`

- [ ] **Step 1: Stwórz klasę**

Plik `includes/diag-checks/class-check-chinese-chars.php`:

```php
<?php
defined('ABSPATH') || exit;

class AsiaAuto_Check_Chinese_Chars extends AsiaAuto_Diag_Check_Base {

    public function getId(): string { return 'chinese-chars'; }
    public function getLabel(): string { return 'Chińskie znaki w tytułach i taksonomii'; }
    public function getCluster(): string { return 'integrity'; }
    public function getEstimatedSeconds(): int { return 4; }
    public function getFixMode(): string { return 'confirm'; }

    public function run(): array {
        $this->bootstrap();
        return $this->timedRun(function () {
            global $wpdb;
            $issues = [];

            // post_title z chińskimi znakami
            $posts = $wpdb->get_results("
                SELECT ID, post_title
                FROM {$wpdb->posts}
                WHERE post_type = 'listings'
                  AND post_status IN ('publish', 'draft')
                  AND post_title REGEXP '[\\x{4e00}-\\x{9fff}]'
                LIMIT 300
            ");
            foreach ($posts as $p) {
                $issues[] = [
                    'id' => 'post_' . $p->ID,
                    'label' => $p->post_title,
                    'meta' => ['kind' => 'post_title', 'post_id' => (int) $p->ID],
                ];
            }

            // termy z chińskimi znakami
            $terms = $wpdb->get_results("
                SELECT t.term_id, t.name, tt.taxonomy
                FROM {$wpdb->terms} t
                JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
                WHERE tt.taxonomy IN ('make', 'serie')
                  AND t.name REGEXP '[\\x{4e00}-\\x{9fff}]'
            ");
            foreach ($terms as $t) {
                $issues[] = [
                    'id' => "term_{$t->taxonomy}_{$t->term_id}",
                    'label' => "{$t->taxonomy}: {$t->name}",
                    'meta' => ['kind' => 'term', 'taxonomy' => $t->taxonomy, 'term_id' => (int) $t->term_id],
                ];
            }

            $count = count($issues);
            return [
                'status' => $count === 0 ? 'ok' : 'warn',
                'count' => $count,
                'issues' => $issues,
                'summary' => $count === 0
                    ? 'Brak chińskich znaków w tytułach ani taksonomii.'
                    : sprintf('%d miejsc z chińskimi znakami.', $count),
            ];
        });
    }

    public function previewFix(array $issue_ids): array {
        return [
            'actions' => [
                sprintf('Uruchom diag/fix-chinese-v23.php (apply mode) — naprawi %d miejsc.', count($issue_ids) ?: $this->lastRunCount()),
                'Mapa źródłowa: data/translations-models.php.',
            ],
            'estimated_changes' => ['fixed' => count($issue_ids) ?: $this->lastRunCount()],
            'apply_token' => $this->mintApplyToken($issue_ids),
            'ttl_seconds' => self::TOKEN_TTL,
        ];
    }

    public function applyFix(array $issue_ids, string $apply_token): array {
        $this->bootstrap();
        if (!$this->validateApplyToken($apply_token, $issue_ids)) {
            return ['applied' => 0, 'failed' => 0, 'errors' => ['Token wygasł lub niepoprawny — odśwież preview.']];
        }

        if (!defined('ASIAAUTO_DIAG_INTERNAL_CALL')) {
            define('ASIAAUTO_DIAG_INTERNAL_CALL', 1);
        }
        require_once ASIAAUTO_PLUGIN_DIR . 'diag/fix-chinese-v23.php';

        $result = asiaauto_diag_chinese_v23(true);
        $applied = $result['fixed'] ?? 0;
        $failed = $result['failed'] ?? 0;

        $this->log('info', 'apply', ['applied' => $applied, 'failed' => $failed]);
        return [
            'applied' => $applied,
            'failed' => $failed,
            'errors' => $result['errors'] ?? [],
        ];
    }

    private function lastRunCount(): int {
        $last = AsiaAuto_Diag_Registry::instance()->getLastRun($this->getId());
        return $last['count'] ?? 0;
    }
}
```

- [ ] **Step 2: php -l**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/diag-checks/class-check-chinese-chars.php
```
Expected: `No syntax errors detected`.

---

### Task 6: Check #3 — broken-extra-prep

**Files:**
- Create: `includes/diag-checks/class-check-broken-extra-prep.php`

- [ ] **Step 1: Stwórz klasę**

Plik `includes/diag-checks/class-check-broken-extra-prep.php`:

```php
<?php
defined('ABSPATH') || exit;

class AsiaAuto_Check_Broken_Extra_Prep extends AsiaAuto_Diag_Check_Base {

    public function getId(): string { return 'broken-extra-prep'; }
    public function getLabel(): string { return 'Złamany JSON _asiaauto_extra_prep'; }
    public function getCluster(): string { return 'integrity'; }
    public function getEstimatedSeconds(): int { return 6; }
    public function getFixMode(): string { return 'confirm'; }

    public function run(): array {
        $this->bootstrap();
        return $this->timedRun(function () {
            global $wpdb;
            $rows = $wpdb->get_results("
                SELECT pm.post_id, p.post_title, LEFT(pm.meta_value, 100) AS preview
                FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_key = '_asiaauto_extra_prep'
                  AND pm.meta_value != ''
                  AND pm.meta_value NOT IN ('null', '[]', '{}')
            ");

            $issues = [];
            foreach ($rows as $r) {
                $raw = get_post_meta($r->post_id, '_asiaauto_extra_prep', true);
                if (json_decode($raw, true) === null) {
                    $issues[] = [
                        'id' => (string) $r->post_id,
                        'label' => $r->post_title ?: ('Listing #' . $r->post_id),
                        'meta' => [
                            'post_id' => (int) $r->post_id,
                            'preview' => $r->preview,
                        ],
                    ];
                    if (count($issues) >= 200) break;
                }
            }
            $count = count($issues);
            return [
                'status' => $count === 0 ? 'ok' : 'warn',
                'count' => $count,
                'issues' => $issues,
                'summary' => $count === 0
                    ? 'Wszystkie extra_prep parsują się poprawnie.'
                    : sprintf('%d listings ze złamanym JSON-em.', $count),
            ];
        });
    }

    public function previewFix(array $issue_ids): array {
        $n = count($issue_ids) ?: $this->lastRunCount();
        return [
            'actions' => [
                sprintf('Uruchom diag/fix-broken-json-v25.php — wp_unslash + retry parse dla %d listingów.', $n),
                'Listings nieparsujące się po fixie zostaną oflagowane jako requires-manual w log/diag.log.',
            ],
            'estimated_changes' => ['fixed' => $n],
            'apply_token' => $this->mintApplyToken($issue_ids),
            'ttl_seconds' => self::TOKEN_TTL,
        ];
    }

    public function applyFix(array $issue_ids, string $apply_token): array {
        $this->bootstrap();
        if (!$this->validateApplyToken($apply_token, $issue_ids)) {
            return ['applied' => 0, 'failed' => 0, 'errors' => ['Token wygasł.']];
        }
        if (!defined('ASIAAUTO_DIAG_INTERNAL_CALL')) {
            define('ASIAAUTO_DIAG_INTERNAL_CALL', 1);
        }
        require_once ASIAAUTO_PLUGIN_DIR . 'diag/fix-broken-json-v25.php';
        $result = asiaauto_diag_broken_json_v25(true);
        $applied = $result['fixed'] ?? 0;
        $failed = $result['failed'] ?? 0;
        $this->log('info', 'apply', ['applied' => $applied, 'failed' => $failed]);
        return ['applied' => $applied, 'failed' => $failed, 'errors' => $result['errors'] ?? []];
    }

    private function lastRunCount(): int {
        $last = AsiaAuto_Diag_Registry::instance()->getLastRun($this->getId());
        return $last['count'] ?? 0;
    }
}
```

- [ ] **Step 2: php -l**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/diag-checks/class-check-broken-extra-prep.php
```
Expected: `No syntax errors detected`.

---

### Task 7: Check #4 — duplicate-listings

**Files:**
- Create: `includes/diag-checks/class-check-duplicate-listings.php`

- [ ] **Step 1: Stwórz klasę**

Plik `includes/diag-checks/class-check-duplicate-listings.php`:

```php
<?php
defined('ABSPATH') || exit;

class AsiaAuto_Check_Duplicate_Listings extends AsiaAuto_Diag_Check_Base {

    public function getId(): string { return 'duplicate-listings'; }
    public function getLabel(): string { return 'Duplikaty po _asiaauto_inner_id'; }
    public function getCluster(): string { return 'integrity'; }
    public function getEstimatedSeconds(): int { return 5; }
    public function getFixMode(): string { return 'per-item'; }

    public function run(): array {
        $this->bootstrap();
        return $this->timedRun(function () {
            global $wpdb;
            $rows = $wpdb->get_results("
                SELECT pm.meta_value AS inner_id,
                       GROUP_CONCAT(pm.post_id ORDER BY p.post_modified DESC) AS post_ids,
                       COUNT(*) AS cnt
                FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_key = '_asiaauto_inner_id'
                  AND p.post_type = 'listings'
                  AND p.post_status IN ('publish', 'draft')
                GROUP BY pm.meta_value
                HAVING cnt > 1
            ");

            $issues = [];
            foreach ($rows as $r) {
                $post_ids = array_map('intval', explode(',', $r->post_ids));
                $titles = [];
                foreach ($post_ids as $pid) {
                    $titles[$pid] = get_the_title($pid) ?: ('Listing #' . $pid);
                }
                $issues[] = [
                    'id' => 'inner_' . $r->inner_id,
                    'label' => sprintf('inner_id=%s — %d duplikatów', $r->inner_id, $r->cnt),
                    'meta' => [
                        'inner_id' => $r->inner_id,
                        'post_ids' => $post_ids,
                        'titles' => $titles,
                        'suggested_keep' => $post_ids[0], // najnowszy
                    ],
                ];
            }
            $count = count($issues);
            return [
                'status' => $count === 0 ? 'ok' : 'warn',
                'count' => $count,
                'issues' => $issues,
                'summary' => $count === 0
                    ? 'Brak duplikatów po inner_id.'
                    : sprintf('%d grup duplikatów (Plan A race condition kandydat — patrz docs/decyzje/2026-04-22-...).', $count),
            ];
        });
    }

    public function previewFix(array $issue_ids): array {
        $details = [];
        foreach ($issue_ids as $iid) {
            $details[] = sprintf('Grupa %s: zostanie zachowany 1 post (najnowszy), reszta do trasha.', $iid);
        }
        return [
            'actions' => $details,
            'estimated_changes' => ['groups_to_resolve' => count($issue_ids)],
            'apply_token' => $this->mintApplyToken($issue_ids),
            'ttl_seconds' => self::TOKEN_TTL,
        ];
    }

    public function applyFix(array $issue_ids, string $apply_token): array {
        $this->bootstrap();
        if (!$this->validateApplyToken($apply_token, $issue_ids)) {
            return ['applied' => 0, 'failed' => 0, 'errors' => ['Token wygasł.']];
        }

        // Re-run scan żeby mieć świeże dane (preview był sprzed >0s).
        $report = $this->run();
        $by_iid = [];
        foreach ($report['issues'] as $issue) {
            $by_iid[$issue['id']] = $issue;
        }

        $applied = 0; $failed = 0; $errors = [];
        foreach ($issue_ids as $iid) {
            if (!isset($by_iid[$iid])) {
                $failed++;
                $errors[] = "Grupa {$iid} już rozwiązana.";
                continue;
            }
            $post_ids = $by_iid[$iid]['meta']['post_ids'];
            $keep = $by_iid[$iid]['meta']['suggested_keep'];
            foreach ($post_ids as $pid) {
                if ($pid === $keep) continue;
                $r = wp_trash_post($pid);
                if ($r) { $applied++; } else { $failed++; $errors[] = "Trash post {$pid} failed."; }
            }
        }

        $this->log('warning', 'duplicate-resolved', ['applied' => $applied, 'failed' => $failed, 'groups' => count($issue_ids)]);
        return ['applied' => $applied, 'failed' => $failed, 'errors' => $errors];
    }
}
```

- [ ] **Step 2: php -l**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/diag-checks/class-check-duplicate-listings.php
```
Expected: `No syntax errors detected`.

---

### Task 8: Check #5 + #6 — make/serie-without-wiki

**Files:**
- Create: `includes/diag-checks/class-check-make-without-wiki.php`
- Create: `includes/diag-checks/class-check-serie-without-wiki.php`

**Wzorzec wspólny** — oba checki różnią się tylko `taxonomy`. Robimy DRY przez trait, ale konkretnie tu — dwie krótkie klasy są bardziej czytelne niż wspólna baza. Zostawiam jako duplikat z różnicą jednej stałej.

- [ ] **Step 1: Make**

Plik `includes/diag-checks/class-check-make-without-wiki.php`:

```php
<?php
defined('ABSPATH') || exit;

class AsiaAuto_Check_Make_Without_Wiki extends AsiaAuto_Diag_Check_Base {

    private const TAXONOMY = 'make';
    private const META_KEY = 'wiki_body';

    public function getId(): string { return 'make-without-wiki'; }
    public function getLabel(): string { return 'Marki bez wiki_body'; }
    public function getCluster(): string { return 'seo-coverage'; }
    public function getEstimatedSeconds(): int { return 2; }
    public function getFixMode(): string { return 'none'; }

    public function run(): array {
        $this->bootstrap();
        return $this->timedRun(function () {
            $terms = get_terms([
                'taxonomy' => self::TAXONOMY,
                'hide_empty' => false,
            ]);
            if (is_wp_error($terms)) {
                return ['status' => 'error', 'count' => 0, 'issues' => [], 'summary' => $terms->get_error_message()];
            }

            $issues = [];
            foreach ($terms as $t) {
                $body = get_term_meta($t->term_id, self::META_KEY, true);
                if (empty($body)) {
                    $issues[] = [
                        'id' => 'term_' . $t->term_id,
                        'label' => $t->name,
                        'meta' => [
                            'term_id' => $t->term_id,
                            'slug' => $t->slug,
                            'edit_url' => admin_url("term.php?taxonomy=" . self::TAXONOMY . "&tag_ID={$t->term_id}"),
                        ],
                    ];
                }
            }
            $count = count($issues);
            return [
                'status' => $count === 0 ? 'ok' : 'warn',
                'count' => $count,
                'issues' => $issues,
                'summary' => $count === 0
                    ? 'Wszystkie marki mają wiki_body.'
                    : sprintf('%d marek bez wiki_body — wygeneruj przez n8n make pipeline.', $count),
            ];
        });
    }
}
```

- [ ] **Step 2: Serie**

Plik `includes/diag-checks/class-check-serie-without-wiki.php`:

Identyczny do make z trzema różnicami:
- nazwa klasy `AsiaAuto_Check_Serie_Without_Wiki`
- `getId(): 'serie-without-wiki'`
- `getLabel(): 'Modele bez wiki_body'`
- `TAXONOMY = 'serie'`

```php
<?php
defined('ABSPATH') || exit;

class AsiaAuto_Check_Serie_Without_Wiki extends AsiaAuto_Diag_Check_Base {

    private const TAXONOMY = 'serie';
    private const META_KEY = 'wiki_body';

    public function getId(): string { return 'serie-without-wiki'; }
    public function getLabel(): string { return 'Modele bez wiki_body'; }
    public function getCluster(): string { return 'seo-coverage'; }
    public function getEstimatedSeconds(): int { return 3; }
    public function getFixMode(): string { return 'none'; }

    public function run(): array {
        $this->bootstrap();
        return $this->timedRun(function () {
            $terms = get_terms([
                'taxonomy' => self::TAXONOMY,
                'hide_empty' => false,
            ]);
            if (is_wp_error($terms)) {
                return ['status' => 'error', 'count' => 0, 'issues' => [], 'summary' => $terms->get_error_message()];
            }

            $issues = [];
            foreach ($terms as $t) {
                $body = get_term_meta($t->term_id, self::META_KEY, true);
                if (empty($body)) {
                    $issues[] = [
                        'id' => 'term_' . $t->term_id,
                        'label' => $t->name,
                        'meta' => [
                            'term_id' => $t->term_id,
                            'slug' => $t->slug,
                            'parent' => $t->parent,
                            'edit_url' => admin_url("term.php?taxonomy=" . self::TAXONOMY . "&tag_ID={$t->term_id}"),
                        ],
                    ];
                }
            }
            $count = count($issues);
            return [
                'status' => $count === 0 ? 'ok' : 'warn',
                'count' => $count,
                'issues' => $issues,
                'summary' => $count === 0
                    ? 'Wszystkie modele mają wiki_body.'
                    : sprintf('%d modeli bez wiki_body — wygeneruj przez n8n serie pipeline.', $count),
            ];
        });
    }
}
```

- [ ] **Step 3: php -l**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/diag-checks/class-check-make-without-wiki.php
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/diag-checks/class-check-serie-without-wiki.php
```
Expected: 2× `No syntax errors detected`.

---

### Task 9: Check #7 — listings-without-mapping

**Files:**
- Create: `includes/diag-checks/class-check-listings-without-mapping.php`

- [ ] **Step 1: Stwórz klasę**

Plik `includes/diag-checks/class-check-listings-without-mapping.php`:

```php
<?php
defined('ABSPATH') || exit;

class AsiaAuto_Check_Listings_Without_Mapping extends AsiaAuto_Diag_Check_Base {

    public function getId(): string { return 'listings-without-mapping'; }
    public function getLabel(): string { return 'Listingi z marką/modelem CN spoza v6.1 mappingu'; }
    public function getCluster(): string { return 'seo-coverage'; }
    public function getEstimatedSeconds(): int { return 5; }
    public function getFixMode(): string { return 'none'; }

    public function run(): array {
        $this->bootstrap();
        return $this->timedRun(function () {
            global $wpdb;

            $mapping_file = ASIAAUTO_PLUGIN_DIR . 'data/brand-mapping-v6.1.php';
            if (!file_exists($mapping_file)) {
                return ['status' => 'error', 'count' => 0, 'issues' => [], 'summary' => "Brak {$mapping_file}"];
            }
            $mapping = include $mapping_file;
            if (!is_array($mapping)) {
                return ['status' => 'error', 'count' => 0, 'issues' => [], 'summary' => 'Mapping zwrócił nie-array.'];
            }

            // Set par (make_cn|serie_cn) z mappingu
            $known = [];
            foreach ($mapping as $row) {
                $key = trim($row['make_cn'] ?? '') . '|' . trim($row['serie_cn'] ?? '');
                $known[$key] = true;
            }

            // Listings ich (make_cn, serie_cn) z meta
            $rows = $wpdb->get_results("
                SELECT p.ID, p.post_title,
                       MAX(CASE WHEN pm.meta_key='_asiaauto_make_cn' THEN pm.meta_value END) AS make_cn,
                       MAX(CASE WHEN pm.meta_key='_asiaauto_serie_cn' THEN pm.meta_value END) AS serie_cn
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                WHERE p.post_type = 'listings'
                  AND p.post_status IN ('publish', 'draft')
                  AND pm.meta_key IN ('_asiaauto_make_cn', '_asiaauto_serie_cn')
                GROUP BY p.ID
                HAVING make_cn IS NOT NULL OR serie_cn IS NOT NULL
            ");

            // Agregacja per (make_cn, serie_cn)
            $unmapped = [];
            foreach ($rows as $r) {
                $key = trim($r->make_cn ?? '') . '|' . trim($r->serie_cn ?? '');
                if (!isset($known[$key])) {
                    if (!isset($unmapped[$key])) {
                        $unmapped[$key] = [
                            'make_cn' => $r->make_cn,
                            'serie_cn' => $r->serie_cn,
                            'count' => 0,
                            'sample_ids' => [],
                        ];
                    }
                    $unmapped[$key]['count']++;
                    if (count($unmapped[$key]['sample_ids']) < 3) {
                        $unmapped[$key]['sample_ids'][] = (int) $r->ID;
                    }
                }
            }

            $issues = [];
            foreach ($unmapped as $key => $data) {
                $issues[] = [
                    'id' => md5($key),
                    'label' => sprintf('%s / %s — %d listingów', $data['make_cn'], $data['serie_cn'], $data['count']),
                    'meta' => $data,
                ];
            }

            // Sortuj po count desc
            usort($issues, fn($a, $b) => $b['meta']['count'] - $a['meta']['count']);

            $count = count($issues);
            return [
                'status' => $count === 0 ? 'ok' : 'warn',
                'count' => $count,
                'issues' => $issues,
                'summary' => $count === 0
                    ? 'Wszystkie listings mają (make_cn, serie_cn) w mappingu.'
                    : sprintf('%d niezamapowanych par CN — kandydaci do dopisania w data/brand-mapping-v6.1.php.', $count),
            ];
        });
    }
}
```

- [ ] **Step 2: php -l**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/diag-checks/class-check-listings-without-mapping.php
```
Expected: `No syntax errors detected`.

---

### Task 10: Check #8 — mapping-without-term

**Files:**
- Create: `includes/diag-checks/class-check-mapping-without-term.php`

- [ ] **Step 1: Stwórz klasę**

Plik `includes/diag-checks/class-check-mapping-without-term.php`:

```php
<?php
defined('ABSPATH') || exit;

class AsiaAuto_Check_Mapping_Without_Term extends AsiaAuto_Diag_Check_Base {

    public function getId(): string { return 'mapping-without-term'; }
    public function getLabel(): string { return 'Pozycje mappingu bez utworzonego termu'; }
    public function getCluster(): string { return 'seo-coverage'; }
    public function getEstimatedSeconds(): int { return 4; }
    public function getFixMode(): string { return 'confirm'; }

    public function run(): array {
        $this->bootstrap();
        return $this->timedRun(function () {
            $mapping_file = ASIAAUTO_PLUGIN_DIR . 'data/brand-mapping-v6.1.php';
            if (!file_exists($mapping_file)) {
                return ['status' => 'error', 'count' => 0, 'issues' => [], 'summary' => "Brak {$mapping_file}"];
            }
            $mapping = include $mapping_file;
            if (!is_array($mapping)) {
                return ['status' => 'error', 'count' => 0, 'issues' => [], 'summary' => 'Mapping zwrócił nie-array.'];
            }

            $issues = [];
            foreach ($mapping as $row) {
                $make_eu = $row['make_eu'] ?? '';
                $serie_eu = $row['serie_eu'] ?? '';
                $make_slug = sanitize_title($make_eu);
                $serie_slug = sanitize_title($serie_eu);

                if ($make_eu === '' || $serie_eu === '') continue;

                $make_term = term_exists($make_slug, 'make');
                $serie_term = term_exists($serie_slug, 'serie');

                if (!$make_term) {
                    $issues[] = [
                        'id' => 'make_' . $make_slug,
                        'label' => sprintf('Marka brak: %s (slug %s)', $make_eu, $make_slug),
                        'meta' => ['kind' => 'make', 'name' => $make_eu, 'slug' => $make_slug],
                    ];
                }
                if (!$serie_term) {
                    $issues[] = [
                        'id' => 'serie_' . $serie_slug,
                        'label' => sprintf('Model brak: %s / %s (slug %s)', $make_eu, $serie_eu, $serie_slug),
                        'meta' => [
                            'kind' => 'serie',
                            'name' => $serie_eu,
                            'slug' => $serie_slug,
                            'parent_make_slug' => $make_slug,
                        ],
                    ];
                }
            }

            // Dedup po id
            $issues = array_values(array_combine(array_column($issues, 'id'), $issues));

            $count = count($issues);
            return [
                'status' => $count === 0 ? 'ok' : 'warn',
                'count' => $count,
                'issues' => $issues,
                'summary' => $count === 0
                    ? 'Wszystkie pozycje mappingu mają termy.'
                    : sprintf('%d brakujących termów (np. Zeekr 9X-typu).', $count),
            ];
        });
    }

    public function previewFix(array $issue_ids): array {
        return [
            'actions' => [
                sprintf('Utworzy %d brakujących termów (wp_insert_term).', count($issue_ids)),
                'Termy serie z parentem make zostaną podpięte pod właściwą markę.',
            ],
            'estimated_changes' => ['terms_to_create' => count($issue_ids)],
            'apply_token' => $this->mintApplyToken($issue_ids),
            'ttl_seconds' => self::TOKEN_TTL,
        ];
    }

    public function applyFix(array $issue_ids, string $apply_token): array {
        $this->bootstrap();
        if (!$this->validateApplyToken($apply_token, $issue_ids)) {
            return ['applied' => 0, 'failed' => 0, 'errors' => ['Token wygasł.']];
        }

        $report = $this->run();
        $by_id = [];
        foreach ($report['issues'] as $issue) {
            $by_id[$issue['id']] = $issue;
        }

        $applied = 0; $failed = 0; $errors = [];

        // Najpierw make (parent dla serie)
        foreach ($issue_ids as $iid) {
            if (!isset($by_id[$iid])) continue;
            $issue = $by_id[$iid];
            if ($issue['meta']['kind'] !== 'make') continue;
            $r = wp_insert_term($issue['meta']['name'], 'make', ['slug' => $issue['meta']['slug']]);
            if (is_wp_error($r)) {
                $failed++;
                $errors[] = "Make {$issue['meta']['slug']}: " . $r->get_error_message();
            } else {
                $applied++;
            }
        }

        // Potem serie
        foreach ($issue_ids as $iid) {
            if (!isset($by_id[$iid])) continue;
            $issue = $by_id[$iid];
            if ($issue['meta']['kind'] !== 'serie') continue;

            $parent_make = term_exists($issue['meta']['parent_make_slug'], 'make');
            $parent_id = is_array($parent_make) ? (int) $parent_make['term_id'] : 0;

            $r = wp_insert_term($issue['meta']['name'], 'serie', [
                'slug' => $issue['meta']['slug'],
                'parent' => $parent_id,
            ]);
            if (is_wp_error($r)) {
                $failed++;
                $errors[] = "Serie {$issue['meta']['slug']}: " . $r->get_error_message();
            } else {
                $applied++;
            }
        }

        $this->log('info', 'terms-created', ['applied' => $applied, 'failed' => $failed]);
        return ['applied' => $applied, 'failed' => $failed, 'errors' => $errors];
    }
}
```

- [ ] **Step 2: php -l**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/diag-checks/class-check-mapping-without-term.php
```
Expected: `No syntax errors detected`.

---

## Konsumenci: AJAX, UI, CLI

### Task 11: AJAX endpointy

**Files:**
- Modify: `includes/class-asiaauto-diag.php` — dodaj `register_ajax_handlers()` + 3 metody.

- [ ] **Step 1: Otwórz `class-asiaauto-diag.php` i dodaj na końcu klasy `AsiaAuto_Diag_Registry`**

Po metodzie `getLastRun()`:

```php
    public function registerAjaxHandlers(): void {
        add_action('wp_ajax_asiaauto_diag_run', [$this, 'ajaxRun']);
        add_action('wp_ajax_asiaauto_diag_preview', [$this, 'ajaxPreview']);
        add_action('wp_ajax_asiaauto_diag_apply', [$this, 'ajaxApply']);
    }

    private function ajaxGuard(string $action): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['msg' => 'forbidden'], 403);
        }
        check_ajax_referer('asiaauto_diag_' . $action, '_wpnonce');
    }

    public function ajaxRun(): void {
        $this->ajaxGuard('run');
        $check_id = sanitize_key($_POST['check_id'] ?? '');
        $force = !empty($_POST['force']);
        $check = $this->get($check_id);
        if (!$check) {
            wp_send_json_error(['msg' => 'unknown check'], 404);
        }

        // Throttle 30s
        if (!$force) {
            $last = $this->getLastRun($check_id);
            if ($last && (time() - $last['ts']) < 30) {
                wp_send_json_error([
                    'msg' => 'throttled',
                    'ago' => time() - $last['ts'],
                ], 429);
            }
        }

        $result = $check->run();
        $this->recordRun($check_id, $result);
        wp_send_json_success($result);
    }

    public function ajaxPreview(): void {
        $this->ajaxGuard('preview');
        $check_id = sanitize_key($_POST['check_id'] ?? '');
        $issue_ids = array_map('sanitize_text_field', (array) ($_POST['issue_ids'] ?? []));
        $check = $this->get($check_id);
        if (!$check) wp_send_json_error(['msg' => 'unknown check'], 404);
        wp_send_json_success($check->previewFix($issue_ids));
    }

    public function ajaxApply(): void {
        $this->ajaxGuard('apply');
        $check_id = sanitize_key($_POST['check_id'] ?? '');
        $issue_ids = array_map('sanitize_text_field', (array) ($_POST['issue_ids'] ?? []));
        $apply_token = sanitize_text_field($_POST['apply_token'] ?? '');
        $check = $this->get($check_id);
        if (!$check) wp_send_json_error(['msg' => 'unknown check'], 404);
        $result = $check->applyFix($issue_ids, $apply_token);
        wp_send_json_success($result);
    }
```

- [ ] **Step 2: php -l**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/class-asiaauto-diag.php
```
Expected: `No syntax errors detected`.

---

### Task 12: Admin page render

**Files:**
- Create: `includes/class-asiaauto-admin-diag.php`
- Modify: `includes/class-asiaauto-admin.php` — dodaj `add_submenu_page` + assets enqueue switch.

- [ ] **Step 1: Stwórz `class-asiaauto-admin-diag.php`**

Plik `includes/class-asiaauto-admin-diag.php`:

```php
<?php
defined('ABSPATH') || exit;

class AsiaAuto_Admin_Diag {

    public static function render(): void {
        if (!current_user_can('manage_options')) wp_die('forbidden');

        $registry = AsiaAuto_Diag_Registry::instance();
        $byCluster = $registry->byCluster();

        $cluster_labels = [
            'integrity' => 'Integralność danych',
            'seo-coverage' => 'Pokrycie SEO',
        ];

        $nonces = [
            'run' => wp_create_nonce('asiaauto_diag_run'),
            'preview' => wp_create_nonce('asiaauto_diag_preview'),
            'apply' => wp_create_nonce('asiaauto_diag_apply'),
        ];

        $checks_data = [];
        foreach ($registry->all() as $c) {
            $last = $registry->getLastRun($c->getId());
            $checks_data[] = [
                'id' => $c->getId(),
                'label' => $c->getLabel(),
                'cluster' => $c->getCluster(),
                'estimated_seconds' => $c->getEstimatedSeconds(),
                'has_fix' => $c->hasFix(),
                'fix_mode' => $c->getFixMode(),
                'last' => $last,
            ];
        }
        ?>
        <div class="wrap asiaauto-diag-wrap">
            <h1>Diagnostyka Prima Auto</h1>
            <p class="description">Skanowanie systemu pod kątem znanych problemów: integralność danych + pokrycie SEO.</p>
            <p>
                <button id="asiaauto-diag-scan-all" class="button button-primary">Skanuj wszystko</button>
                <span id="asiaauto-diag-progress" style="margin-left:1em;"></span>
            </p>

            <?php foreach ($byCluster as $cluster => $checks): ?>
                <h2><?php echo esc_html($cluster_labels[$cluster] ?? $cluster); ?></h2>
                <table class="widefat asiaauto-diag-table" data-cluster="<?php echo esc_attr($cluster); ?>">
                    <thead>
                        <tr>
                            <th>Check</th>
                            <th style="width:120px">Status</th>
                            <th style="width:100px">Znaleziono</th>
                            <th style="width:140px">Ostatnio</th>
                            <th style="width:240px">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checks as $c): $last = $registry->getLastRun($c->getId()); ?>
                            <tr data-check-id="<?php echo esc_attr($c->getId()); ?>">
                                <td><?php echo esc_html($c->getLabel()); ?></td>
                                <td class="aa-diag-status"><?php echo self::renderStatusBadge($last); ?></td>
                                <td class="aa-diag-count"><?php echo $last ? (int) $last['count'] : '–'; ?></td>
                                <td class="aa-diag-ts"><?php echo $last ? esc_html(human_time_diff($last['ts']) . ' temu') : '–'; ?></td>
                                <td>
                                    <button class="button aa-diag-scan">Skanuj</button>
                                    <?php if ($c->hasFix()): ?>
                                        <button class="button aa-diag-report" disabled>Raport…</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>

            <div id="asiaauto-diag-modal" class="aa-diag-modal" style="display:none;">
                <div class="aa-diag-modal__content">
                    <h2 class="aa-diag-modal__title">…</h2>
                    <div class="aa-diag-modal__body"></div>
                    <div class="aa-diag-modal__actions">
                        <button class="button aa-diag-modal__close">Zamknij</button>
                        <button class="button button-primary aa-diag-modal__apply" style="display:none;">Wykonaj</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            window.asiaautoDiag = {
                ajaxUrl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
                nonces: <?php echo wp_json_encode($nonces); ?>,
                checks: <?php echo wp_json_encode($checks_data); ?>,
            };
        </script>
        <?php
    }

    private static function renderStatusBadge(?array $last): string {
        if (!$last) return '<span class="aa-badge aa-badge--idle">—</span>';
        switch ($last['status']) {
            case 'ok': return '<span class="aa-badge aa-badge--ok">✓ OK</span>';
            case 'warn': return '<span class="aa-badge aa-badge--warn">⚠ ' . (int) $last['count'] . '</span>';
            case 'error': return '<span class="aa-badge aa-badge--err">✗ błąd</span>';
        }
        return '<span class="aa-badge aa-badge--idle">—</span>';
    }
}
```

- [ ] **Step 2: Modify `includes/class-asiaauto-admin.php` — dodaj submenu**

Najpierw przeczytaj obecną strukturę:

```bash
grep -n "add_submenu_page\|add_menu_page\|admin_menu" ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/class-asiaauto-admin.php | head -20
```

Zlokalizuj metodę rejestrującą menu (typowo `addMenuPages()` lub anonim w `add_action('admin_menu', …)`). W tej metodzie, po istniejących `add_submenu_page()`, dodaj:

```php
add_submenu_page(
    'asiaauto',                         // parent slug — sprawdź w istniejącym kodzie, użyj tego samego co inne submenu
    'Diagnostyka',                       // page title
    'Diagnostyka',                       // menu title
    'manage_options',                    // capability
    'asiaauto-diag',                     // menu slug
    ['AsiaAuto_Admin_Diag', 'render']    // callback
);
```

Plus enqueue assets. Znajdź metodę `enqueueScripts()` lub odpowiednik (hook `admin_enqueue_scripts`) i dodaj warunek:

```php
if (isset($_GET['page']) && $_GET['page'] === 'asiaauto-diag') {
    wp_enqueue_style(
        'asiaauto-admin-diag',
        ASIAAUTO_PLUGIN_URL . 'assets/admin-diag.css',
        [],
        ASIAAUTO_VERSION
    );
    wp_enqueue_script(
        'asiaauto-admin-diag',
        ASIAAUTO_PLUGIN_URL . 'assets/admin-diag.js',
        [],
        ASIAAUTO_VERSION,
        true
    );
}
```

- [ ] **Step 3: php -l**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/class-asiaauto-admin-diag.php
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/class-asiaauto-admin.php
```
Expected: 2× `No syntax errors detected`.

---

### Task 13: Frontend assets (JS + CSS)

**Files:**
- Create: `assets/admin-diag.js`
- Create: `assets/admin-diag.css`

- [ ] **Step 1: Stwórz JS**

Plik `assets/admin-diag.js`:

```javascript
(function () {
    'use strict';

    const cfg = window.asiaautoDiag || {};
    if (!cfg.ajaxUrl) return;

    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

    async function ajaxPost(action, body) {
        const fd = new FormData();
        fd.append('action', 'asiaauto_diag_' + action);
        fd.append('_wpnonce', cfg.nonces[action]);
        Object.entries(body || {}).forEach(([k, v]) => {
            if (Array.isArray(v)) {
                v.forEach(item => fd.append(k + '[]', item));
            } else {
                fd.append(k, v);
            }
        });
        const r = await fetch(cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
        const json = await r.json().catch(() => ({ success: false, data: { msg: 'Invalid JSON' } }));
        if (!json.success) {
            const msg = (json.data && json.data.msg) || 'request failed';
            throw new Error(msg);
        }
        return json.data;
    }

    function setRowStatus(row, result) {
        const status = $('.aa-diag-status', row);
        const count = $('.aa-diag-count', row);
        const ts = $('.aa-diag-ts', row);
        const reportBtn = $('.aa-diag-report', row);

        if (result.status === 'ok') {
            status.innerHTML = '<span class="aa-badge aa-badge--ok">✓ OK</span>';
        } else if (result.status === 'warn') {
            status.innerHTML = '<span class="aa-badge aa-badge--warn">⚠ ' + result.count + '</span>';
        } else {
            status.innerHTML = '<span class="aa-badge aa-badge--err">✗ błąd</span>';
        }
        count.textContent = result.count;
        ts.textContent = 'teraz';
        if (reportBtn && result.count > 0) reportBtn.disabled = false;
        row.dataset.lastResult = JSON.stringify(result);
    }

    async function scanCheck(row) {
        const id = row.dataset.checkId;
        $('.aa-diag-status', row).innerHTML = '<span class="aa-badge aa-badge--running">⏳ …</span>';
        try {
            const result = await ajaxPost('run', { check_id: id, force: '1' });
            setRowStatus(row, result);
        } catch (e) {
            $('.aa-diag-status', row).innerHTML = '<span class="aa-badge aa-badge--err" title="' + e.message + '">✗ błąd</span>';
            console.warn('diag scan failed', id, e);
        }
    }

    async function scanAll() {
        const btn = $('#asiaauto-diag-scan-all');
        const progress = $('#asiaauto-diag-progress');
        btn.disabled = true;
        const rows = $$('.asiaauto-diag-table tbody tr');
        let totalIssues = 0;
        for (let i = 0; i < rows.length; i++) {
            progress.textContent = 'Skanuję ' + (i + 1) + '/' + rows.length + '…';
            await scanCheck(rows[i]);
            try {
                const r = JSON.parse(rows[i].dataset.lastResult || '{}');
                totalIssues += r.count || 0;
            } catch (_) {}
        }
        progress.textContent = 'Gotowe. Łącznie ' + totalIssues + ' issue.';
        btn.disabled = false;
    }

    function openModal(title, bodyHTML, applyHandler) {
        const modal = $('#asiaauto-diag-modal');
        $('.aa-diag-modal__title', modal).textContent = title;
        $('.aa-diag-modal__body', modal).innerHTML = bodyHTML;
        const applyBtn = $('.aa-diag-modal__apply', modal);
        if (applyHandler) {
            applyBtn.style.display = '';
            applyBtn.onclick = applyHandler;
        } else {
            applyBtn.style.display = 'none';
        }
        modal.style.display = '';
    }
    function closeModal() { $('#asiaauto-diag-modal').style.display = 'none'; }

    async function showReport(row) {
        const id = row.dataset.checkId;
        const meta = (cfg.checks || []).find(c => c.id === id) || {};
        let result;
        try {
            result = JSON.parse(row.dataset.lastResult || '{}');
        } catch (_) {
            result = { issues: [] };
        }

        let html = '<p>' + (result.summary || '') + '</p>';
        html += '<ul class="aa-diag-issues">';
        (result.issues || []).slice(0, 100).forEach(issue => {
            html += '<li><label><input type="checkbox" checked data-issue-id="' + issue.id + '"> ' + escapeHtml(issue.label) + '</label></li>';
        });
        html += '</ul>';

        if (meta.has_fix && (result.issues || []).length > 0) {
            html += '<p><button class="button button-primary aa-diag-fix-now">Napraw zaznaczone</button></p>';
        }

        openModal(meta.label || id, html, null);

        const fixBtn = $('.aa-diag-fix-now');
        if (fixBtn) {
            fixBtn.addEventListener('click', () => previewAndApply(id));
        }
    }

    async function previewAndApply(checkId) {
        const issueIds = $$('#asiaauto-diag-modal .aa-diag-issues input[type=checkbox]:checked')
            .map(cb => cb.dataset.issueId);
        if (issueIds.length === 0) {
            alert('Zaznacz co najmniej 1 issue.');
            return;
        }
        try {
            const preview = await ajaxPost('preview', { check_id: checkId, issue_ids: issueIds });
            let html = '<p>Wykonane akcje:</p><ul>';
            (preview.actions || []).forEach(a => { html += '<li>' + escapeHtml(a) + '</li>'; });
            html += '</ul>';
            const meta = (cfg.checks || []).find(c => c.id === checkId) || {};
            openModal(meta.label + ' — preview', html, async () => {
                try {
                    const apply = await ajaxPost('apply', {
                        check_id: checkId,
                        issue_ids: issueIds,
                        apply_token: preview.apply_token,
                    });
                    openModal('Wynik', '<p>Naprawiono: ' + apply.applied + ', błędów: ' + apply.failed + '</p>' +
                        ((apply.errors || []).length ? '<pre>' + escapeHtml(apply.errors.join('\n')) + '</pre>' : ''), null);
                    // Re-scan dotyczącego checka
                    const row = document.querySelector('tr[data-check-id="' + checkId + '"]');
                    if (row) scanCheck(row);
                } catch (e) {
                    alert('Apply błąd: ' + e.message);
                }
            });
        } catch (e) {
            alert('Preview błąd: ' + e.message);
        }
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    document.addEventListener('click', (e) => {
        if (e.target.matches('#asiaauto-diag-scan-all')) scanAll();
        else if (e.target.matches('.aa-diag-scan')) scanCheck(e.target.closest('tr'));
        else if (e.target.matches('.aa-diag-report')) showReport(e.target.closest('tr'));
        else if (e.target.matches('.aa-diag-modal__close')) closeModal();
    });
})();
```

- [ ] **Step 2: Stwórz CSS**

Plik `assets/admin-diag.css`:

```css
.asiaauto-diag-wrap h2 { margin-top: 2em; }
.asiaauto-diag-table { margin-bottom: 1em; }
.asiaauto-diag-table th { font-weight: 600; }

.aa-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; }
.aa-badge--idle { background: #f0f0f0; color: #888; }
.aa-badge--running { background: #e7f3ff; color: #1d6fa5; }
.aa-badge--ok { background: #e6f4ea; color: #1e8e3e; }
.aa-badge--warn { background: #fef7e0; color: #b06000; }
.aa-badge--err { background: #fce8e6; color: #c5221f; }

.aa-diag-modal {
    position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 100000;
    display: flex; align-items: center; justify-content: center;
}
.aa-diag-modal__content {
    background: #fff; max-width: 720px; width: 90%; max-height: 80vh; overflow-y: auto;
    padding: 24px; border-radius: 4px;
}
.aa-diag-modal__title { margin-top: 0; }
.aa-diag-modal__actions { margin-top: 1em; text-align: right; }
.aa-diag-modal__actions .button { margin-left: 8px; }

.aa-diag-issues { list-style: none; padding: 0; max-height: 50vh; overflow-y: auto; }
.aa-diag-issues li { padding: 4px 0; border-bottom: 1px solid #eee; }
```

- [ ] **Step 3: Sanity — pliki istnieją i są niepuste**

```bash
ls -la ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/assets/admin-diag.{js,css}
wc -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/assets/admin-diag.{js,css}
```
Expected: oba pliki >50 linii.

---

### Task 14: WP-CLI subcommands

**Files:**
- Modify: `cli/class-asiaauto-cli.php` — dodaj metodę `diag()` z subdispatchem.

- [ ] **Step 1: Dodaj metodę `diag` do `AsiaAuto_CLI`**

W `cli/class-asiaauto-cli.php`, na końcu klasy (przed zamykającym `}`):

```php
    /**
     * Diagnostyka systemu (panel admina jako CLI).
     *
     * ## OPTIONS
     *
     * <subcommand>
     * : list | run | run-all | preview-fix | apply-fix
     *
     * [<check-id>]
     * : ID checku (dla run / preview-fix / apply-fix). Lista: `wp asiaauto diag list`.
     *
     * [--issue-ids=<ids>]
     * : Comma-separated lista issue ID. Bez tego: wszystkie z ostatniego runu.
     *
     * [--format=<format>]
     * : table | json | count. Default: table.
     *
     * [--yes]
     * : Wymagane dla apply-fix.
     *
     * ## EXAMPLES
     *
     *     wp asiaauto diag list
     *     wp asiaauto diag run missing-images
     *     wp asiaauto diag run-all --format=json
     *     wp asiaauto diag preview-fix chinese-chars
     *     wp asiaauto diag apply-fix chinese-chars --yes
     *
     * @when after_wp_load
     */
    public function diag($args, $assoc_args) {
        $sub = $args[0] ?? '';
        $registry = AsiaAuto_Diag_Registry::instance();

        switch ($sub) {
            case 'list':
                $this->diagList($registry, $assoc_args);
                break;
            case 'run':
                $check_id = $args[1] ?? '';
                $this->diagRun($registry, $check_id, $assoc_args);
                break;
            case 'run-all':
                $this->diagRunAll($registry, $assoc_args);
                break;
            case 'preview-fix':
                $check_id = $args[1] ?? '';
                $this->diagPreview($registry, $check_id, $assoc_args);
                break;
            case 'apply-fix':
                $check_id = $args[1] ?? '';
                $this->diagApply($registry, $check_id, $assoc_args);
                break;
            default:
                WP_CLI::error("Unknown subcommand. Try: list | run | run-all | preview-fix | apply-fix");
        }
    }

    private function diagList(AsiaAuto_Diag_Registry $registry, array $assoc_args): void {
        $rows = [];
        foreach ($registry->all() as $c) {
            $last = $registry->getLastRun($c->getId());
            $rows[] = [
                'id' => $c->getId(),
                'cluster' => $c->getCluster(),
                'label' => $c->getLabel(),
                'has_fix' => $c->hasFix() ? 'yes' : 'no',
                'fix_mode' => $c->getFixMode(),
                'last_count' => $last['count'] ?? '—',
                'last_ts' => $last ? date('Y-m-d H:i', $last['ts']) : '—',
            ];
        }
        $format = $assoc_args['format'] ?? 'table';
        WP_CLI\Utils\format_items($format, $rows, ['id', 'cluster', 'label', 'has_fix', 'fix_mode', 'last_count', 'last_ts']);
    }

    private function diagRun(AsiaAuto_Diag_Registry $registry, string $check_id, array $assoc_args): void {
        $check = $registry->get($check_id);
        if (!$check) WP_CLI::error("Unknown check: {$check_id}");
        $result = $check->run();
        $registry->recordRun($check_id, $result);

        $format = $assoc_args['format'] ?? 'table';
        if ($format === 'json') {
            WP_CLI::line(wp_json_encode($result));
        } elseif ($format === 'count') {
            WP_CLI::line((string) ($result['count'] ?? 0));
        } else {
            WP_CLI::log(sprintf('[%s] %s — count=%d (%dms)', $result['status'], $check->getLabel(), $result['count'], $result['duration_ms']));
            WP_CLI::log($result['summary'] ?? '');
            if ($result['count'] > 0) {
                $rows = [];
                foreach (array_slice($result['issues'], 0, 20) as $i) {
                    $rows[] = ['id' => $i['id'], 'label' => $i['label']];
                }
                WP_CLI\Utils\format_items('table', $rows, ['id', 'label']);
                if ($result['count'] > 20) WP_CLI::log(sprintf('… +%d więcej (użyj --format=json).', $result['count'] - 20));
            }
        }
    }

    private function diagRunAll(AsiaAuto_Diag_Registry $registry, array $assoc_args): void {
        $format = $assoc_args['format'] ?? 'table';
        $all = ['ts' => time(), 'checks' => []];
        foreach ($registry->all() as $c) {
            $r = $c->run();
            $registry->recordRun($c->getId(), $r);
            $all['checks'][$c->getId()] = [
                'label' => $c->getLabel(),
                'status' => $r['status'],
                'count' => $r['count'],
                'duration_ms' => $r['duration_ms'],
            ];
        }
        if ($format === 'json') {
            WP_CLI::line(wp_json_encode($all));
        } else {
            $rows = [];
            foreach ($all['checks'] as $id => $r) {
                $rows[] = ['id' => $id, 'status' => $r['status'], 'count' => $r['count'], 'ms' => $r['duration_ms']];
            }
            WP_CLI\Utils\format_items('table', $rows, ['id', 'status', 'count', 'ms']);
        }
    }

    private function diagPreview(AsiaAuto_Diag_Registry $registry, string $check_id, array $assoc_args): void {
        $check = $registry->get($check_id);
        if (!$check) WP_CLI::error("Unknown check: {$check_id}");
        $issue_ids = isset($assoc_args['issue-ids'])
            ? array_filter(array_map('trim', explode(',', $assoc_args['issue-ids'])))
            : $this->lastIssueIds($check);
        $preview = $check->previewFix($issue_ids);
        WP_CLI::log('Akcje:');
        foreach ($preview['actions'] as $a) WP_CLI::log('  - ' . $a);
        WP_CLI::log('Estimated changes: ' . wp_json_encode($preview['estimated_changes']));
        WP_CLI::log('Apply token (TTL ' . $preview['ttl_seconds'] . 's): ' . $preview['apply_token']);
    }

    private function diagApply(AsiaAuto_Diag_Registry $registry, string $check_id, array $assoc_args): void {
        if (empty($assoc_args['yes'])) {
            WP_CLI::error("apply-fix wymaga --yes (analog APPLY=1).");
        }
        $check = $registry->get($check_id);
        if (!$check) WP_CLI::error("Unknown check: {$check_id}");

        $issue_ids = isset($assoc_args['issue-ids'])
            ? array_filter(array_map('trim', explode(',', $assoc_args['issue-ids'])))
            : $this->lastIssueIds($check);

        // Mint token + zastosuj od razu (CLI: token i jego użycie w ramach jednej sesji).
        $preview = $check->previewFix($issue_ids);
        $token = $preview['apply_token'];

        $r = $check->applyFix($issue_ids, $token);
        WP_CLI::log(sprintf('Applied: %d, failed: %d', $r['applied'], $r['failed']));
        if (!empty($r['errors'])) {
            WP_CLI::warning(implode("\n", $r['errors']));
        }
    }

    private function lastIssueIds(AsiaAuto_Diag_Check $check): array {
        $r = $check->run();
        return array_column($r['issues'], 'id');
    }
```

- [ ] **Step 2: php -l**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/cli/class-asiaauto-cli.php
```
Expected: `No syntax errors detected`.

---

## Bootstrap + smoke

### Task 15: Bootstrap requires + register + smoke

**Files:**
- Modify: `asiaauto-sync.php`

- [ ] **Step 1: Dodaj require_once dla nowych klas**

W `asiaauto-sync.php`, **przed** `// ─── WP-CLI ───────────────...` (linia ~64) wstaw:

```php
// ─── Diagnostyka admin panel (v0.32.0+) ──────────────────────────────
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/interface-check.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-base.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-missing-images.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-chinese-chars.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-broken-extra-prep.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-duplicate-listings.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-make-without-wiki.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-serie-without-wiki.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-listings-without-mapping.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/diag-checks/class-check-mapping-without-term.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-diag.php';
require_once ASIAAUTO_PLUGIN_DIR . 'includes/class-asiaauto-admin-diag.php';

add_action('admin_init', function () {
    AsiaAuto_Diag_Registry::instance()->registerAjaxHandlers();
});
```

- [ ] **Step 2: php -l głównego pliku**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/asiaauto-sync.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Smoke — `wp asiaauto diag list`**

```bash
wp --path=~/domains/primaauto.com.pl/public_html asiaauto diag list
```
Expected: tabela z 8 wierszami (id, cluster, label, has_fix, fix_mode, last_count='—', last_ts='—').

- [ ] **Step 4: Smoke — `run` jednego checku**

```bash
wp --path=~/domains/primaauto.com.pl/public_html asiaauto diag run missing-images --format=json
```
Expected: JSON z polami `status`, `count`, `issues`, `duration_ms`. Nie wybucha.

- [ ] **Step 5: Smoke — `run-all`**

```bash
wp --path=~/domains/primaauto.com.pl/public_html asiaauto diag run-all
```
Expected: tabela 8 wierszy ze statusami. Suma `duration_ms` <60000ms (1 minuta).

- [ ] **Step 6: Smoke — admin UI**

W przeglądarce: zaloguj się jako Jan, wejdź na `/wp-admin/admin.php?page=asiaauto-diag`.
Expected:
- Strona renderuje się bez PHP errorów (sprawdź `wp-content/debug.log` jeśli włączony).
- 2 sekcje (`Integralność danych`, `Pokrycie SEO`) z 4+4 wierszami.
- Klik „Skanuj wszystko" — progress bar leci, na końcu wszystkie wiersze mają badge.
- Klik „Raport…" przy checku z >0 issues — modal otwiera się z listą.

- [ ] **Step 7: Smoke — fix flow (auto mode)**

W admin UI: jeśli `missing-images` ma >0, klik „Skanuj" → `Raport…` → „Napraw zaznaczone" → preview modal → „Wykonaj".
Expected: po apply, re-scan pokazuje mniejszą liczbę issues. `logs/diag.log` ma wpis `[diag] apply {"check":"missing-images",...}`.

- [ ] **Step 8: Smoke — throttle**

```bash
wp --path=~/domains/primaauto.com.pl/public_html asiaauto diag run missing-images --format=count
wp --path=~/domains/primaauto.com.pl/public_html asiaauto diag run missing-images --format=count
```
Drugi run zwraca count, ale pamiętaj że throttle 30s jest tylko po stronie AJAX (CLI nie throttluje — i tak działa pod kontrolą operatora).

- [ ] **Step 9: Smoke — apply token expiry (CLI flow)**

```bash
wp --path=~/domains/primaauto.com.pl/public_html asiaauto diag preview-fix mapping-without-term
# Skopiuj apply_token, czekaj 6 minut
sleep 360
wp --path=~/domains/primaauto.com.pl/public_html asiaauto diag apply-fix mapping-without-term --yes
```
Expected: w trakcie apply token zostaje wygenerowany na nowo (CLI flow), więc apply pójdzie. Test stale token jest dla AJAX, nie CLI — i to OK.

---

### Task 16: Update istniejącej dokumentacji

**Files:**
- Modify: `~/projekty/primaauto/docs/QUEUE.md`
- Modify: `~/projekty/primaauto/docs/VERSIONS.md`
- Modify: `~/projekty/primaauto/docs/architektura/diag-scripts.md` (dopisek o nowym panelu)

- [ ] **Step 1: Dodaj wpis w QUEUE.md**

Na początku (po metadanych) wstaw nową sekcję:

```markdown
## ZADANIE 14 — Panel diagnostyczny admina (asiaauto-sync) ✅ DONE (0.32.0, 2026-04-28)

Pluggable rejestr 8 checków + Admin UI + WP-CLI + AJAX. Spec: `docs/superpowers/specs/2026-04-28-diagnostyka-admin-panel-design.md`. Plan: `docs/superpowers/plans/2026-04-28-diagnostyka-admin-panel.md`.

### Klastry v1
- Integralność (4): missing-images, chinese-chars, broken-extra-prep, duplicate-listings
- Pokrycie SEO (4): make/serie-without-wiki, listings-without-mapping, mapping-without-term

### Punkty wejścia
- UI: WP admin → Asia Auto → Diagnostyka
- CLI: `wp asiaauto diag list | run | run-all | preview-fix | apply-fix`
- AJAX: `asiaauto_diag_run | preview | apply` z capability `manage_options`

### Pending v2
- Klaster lifecycle (rotacja, orphan attachments, trash >30d permanent delete) — Plan D
- Klaster ops (filter cleanup, race detection alerts) — Plan A
- Auto-fix dla `make/serie-without-wiki` przez n8n REST endpointy (osobne ZADANIE)
- Cron `asiaauto_diag_daily` z mailem alertem
```

- [ ] **Step 2: Dodaj wpis w VERSIONS.md**

```markdown
## 0.32.0 — 2026-04-28

- **Diagnostyka admin panel** — nowe submenu `Asia Auto → Diagnostyka`. 8 checków integralność + SEO coverage. Trzywarstwowy dostęp: UI / AJAX / WP-CLI (`wp asiaauto diag …`). Pluggable rejestr — dodanie checku = 1 plik + 1 linia. Patrz `docs/superpowers/specs/2026-04-28-diagnostyka-admin-panel-design.md`.
- Refaktor 5 skryptów `diag/*.php` na eksportowane funkcje — backward-compat z `wp eval-file` zachowana.
```

- [ ] **Step 3: Dopisek w architektura/diag-scripts.md**

Na końcu pliku dodaj:

```markdown
## Dostęp z UI / WP-CLI (od v0.32.0)

Skrypty `diag/*.php` są jednocześnie wywoływalne przez panel **Asia Auto → Diagnostyka** (admin UI) oraz przez `wp asiaauto diag`. Każdy check w rejestrze (`includes/diag-checks/`) deleguje do funkcji eksportowanej z odpowiedniego skryptu `diag/`. Workflow `wp eval-file diag/X.php` nadal działa bez zmian.
```

- [ ] **Step 4: Sanity sprawdzenie**

```bash
git -C ~/projekty/primaauto status
```
Expected: 3 zmodyfikowane pliki w `docs/`.

---

### Task 17: Final bump + commit

**Files:**
- Modify: `~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/asiaauto-sync.php`

- [ ] **Step 1: Zdejmij `-dev` suffix**

W `asiaauto-sync.php` zamień:
- `Version: 0.32.0-dev` → `Version: 0.32.0`
- `define('ASIAAUTO_VERSION', '0.32.0-dev');` → `define('ASIAAUTO_VERSION', '0.32.0');`

- [ ] **Step 2: php -l + sanity wersji**

```bash
php -l ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/asiaauto-sync.php
wp --path=~/domains/primaauto.com.pl/public_html plugin get asiaauto-sync --field=version
```
Expected: `0.32.0`.

- [ ] **Step 3: Powiadom Jana o gotowości do commita**

NIE commituj automatycznie. Wyświetl użytkownikowi:

> Implementacja zakończona, smoke green. Plugin v0.32.0 deployed. Repo `primaauto` ma niesynchronizowane zmiany w `docs/`. Czy commitujemy?

Jeśli OK od użytkownika:
```bash
cd ~/projekty/primaauto && git add docs/ && git commit -m "[diag:] panel diagnostyczny admin v1 (8 checków + UI + CLI)"
```

(Push tylko jeśli user wyraźnie napisze „pushujemy" — feedback memory `feedback_git_push_no_confirm.md`.)

---

## Post-deploy

### Task 18: Verification checklist (uruchom po commicie)

- [ ] **Verify 1: UI responsywność**

W admin UI klik „Skanuj wszystko" — wszystkie 8 checków zielone albo z legitnymi countami; brak błędów w `wp-content/debug.log` (jeśli `WP_DEBUG_LOG=true`).

- [ ] **Verify 2: CLI workflow**

```bash
wp --path=~/domains/primaauto.com.pl/public_html asiaauto diag run-all --format=json | jq '.checks | to_entries[] | select(.value.count > 0) | .key'
```
Expected: lista checków z >0 issues. Można dalej drążyć:
```bash
wp --path=~/domains/primaauto.com.pl/public_html asiaauto diag run mapping-without-term --format=json | jq '.issues[].label'
```

- [ ] **Verify 3: Admin capability gate**

W incognito (jako użytkownik bez `manage_options`) wejdź na `/wp-admin/admin.php?page=asiaauto-diag`.
Expected: 403/permission denied.

- [ ] **Verify 4: AJAX nonce gate**

```bash
curl -X POST 'https://primaauto.com.pl/wp-admin/admin-ajax.php?action=asiaauto_diag_run' -d 'check_id=missing-images'
```
Expected: 403/0 response (brak nonce + brak sesji).

- [ ] **Verify 5: Logger zapisuje**

```bash
tail -50 ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/logs/diag.log
```
Expected: wpisy `[diag] apply` / `[diag] terms-created` z poprzednich smoke testów.

---

## Self-review

**Spec coverage check:**

| Spec sekcja | Plan task |
|---|---|
| 3 Architektura → registry + base | Task 1, 2 |
| 3 Pliki nowe (8 checków) | Tasks 4–10 |
| 3 Pliki modyfikowane (asiaauto-sync.php, admin.php, cli.php, diag/*) | Tasks 0, 3, 12, 14, 15, 17 |
| 4 Kontrakt checku (interface + base) | Task 1 |
| 5 Inwentarz checków v1 (8) | Tasks 4–10 |
| 6 AJAX endpointy (3) | Task 11 |
| 7 WP-CLI subcommands | Task 14 |
| 8 Frontend admin page | Task 12, 13 |
| 9 Bezpieczeństwo (nonce, capability, token, time_limit, logger) | Task 1, 11 |
| 10 Extensibility | Task 2 (`registerAll()` + 1 linia) |
| 11 Plan deploymentu | Task 0, 15, 17 |
| 12 Test plan (10 kroków) | Task 15 (Steps 3–9) + Task 18 |

Brak luk.

**Placeholder scan:** Każdy step ma kod albo komendę. Refaktor diag/*.php (Task 3) ma wzorzec ale wymaga przeniesienia istniejącego body — standardowa mechaniczna operacja.

**Type consistency:**
- Interface `AsiaAuto_Diag_Check` zwraca `array` — wszystkie klasy używają konsystentnych kluczy (`status, count, issues, summary, duration_ms, ts`).
- `applyFix()` zwraca `['applied', 'failed', 'errors']` — konsystentne we wszystkich.
- `mintApplyToken()` / `validateApplyToken()` symetryczne (oba w base).
- AJAX handlers + CLI commands wołają te same metody — brak rozbieżności.

Plan zatwierdzony do egzekucji.

---

## Dodatki

**Cofanie (rollback):**
1. `cp -r ~/backups/primaauto/2026-04-28-pre-diag/asiaauto-sync ~/domains/primaauto.com.pl/public_html/wp-content/plugins/`
2. `wp plugin get asiaauto-sync --field=version` → `0.31.12`.
3. `git checkout docs/QUEUE.md docs/VERSIONS.md docs/architektura/diag-scripts.md` w repo.

**Future-proofing (not v1):**
- Klaster lifecycle (rotacja + orphans) — `Task v2.1`.
- Klaster ops (filter cleanup, race detection) — `Task v2.2`.
- Cron + email alert — `Task v2.3`.
- Audit log archive (poza `option`, do dedykowanej tabeli) — `Task v2.4`.

Każdy z powyższych = nowa para spec + plan, dodawane gdy potrzebne.
