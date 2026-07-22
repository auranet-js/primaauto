<?php

defined('ABSPATH') || exit;

/**
 * Handles ongoing synchronization via the /changes endpoint.
 * Processes added/changed/removed events and delegates to Importer/Rotation.
 *
 * For 'added' events, fetches full offer data via getOffer() because /changes
 * does not include extra_prep, equipment, and other fields.
 *
 * Saves sync history to wp_options for admin panel display.
 *
 * @since 0.9.0  Sync history logging.
 * @since 0.9.2  Transient lock, inner_id injection, getOffer() for added.
 */
class AsiaAuto_Sync {

    private AsiaAuto_API $api;
    private AsiaAuto_Importer $importer;
    private AsiaAuto_Rotation $rotation;

    /**
     * Max sync runs to keep in history.
     */
    private int $max_history = 50;

    public function __construct(AsiaAuto_API $api) {
        $this->api = $api;

        $translator     = new AsiaAuto_Translator();
        $media          = new AsiaAuto_Media();
        $this->importer = new AsiaAuto_Importer($translator, $media);
        $this->rotation = new AsiaAuto_Rotation();
    }

    /**
     * Check if sync is enabled in admin panel.
     */
    public static function isEnabled(): bool {
        return (bool) get_option('asiaauto_sync_enabled', false);
    }

    /**
     * Czy sync danego źródła jest włączony (T-186, 2026-07-22).
     *
     * Globalny `asiaauto_sync_enabled` zostaje master-switchem (gasi wszystko), a każde
     * źródło ma własny przełącznik `asiaauto_sync_enabled_{source}`. Brak wpisu per-source
     * = dziedziczenie po globalnym (zachowanie sprzed zmiany dla dongchedi).
     */
    public static function isEnabledForSource(string $source): bool {
        if (!self::isEnabled()) {
            return false;
        }
        $per = get_option("asiaauto_sync_enabled_{$source}", null);
        return $per === null ? true : (bool) $per;
    }

    /**
     * Status importowanych ofert per źródło (T-186, 2026-07-22).
     * Che168 startuje na 'draft' (faza obserwacji) — przełączenie na 'publish'
     * przez opcję `asiaauto_sync_status_{source}`, bez zmiany kodu.
     */
    public static function statusForSource(string $source): string {
        $s = (string) get_option("asiaauto_sync_status_{$source}", $source === 'che168' ? 'draft' : 'publish');
        return in_array($s, ['publish', 'draft'], true) ? $s : 'publish';
    }

    /**
     * Run a full sync cycle for a source.
     * Fetches all changes since last known change_id and processes them.
     *
     * @param string $source  'dongchedi' or 'che168'
     */
    public function run(string $source): void {
        global $wpdb;

        // Lock: prevent parallel sync (cron overlap protection).
        // MySQL advisory lock — auto-released on connection end (PHP crash),
        // no TTL to expire mid-sync. Fixes race that produced duplicate posts
        // when sync exceeded old 10-min transient TTL (see ADR 2026-04-22).
        $lock_name = "asiaauto_sync_{$source}";
        $got_lock  = $wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s, %d)", $lock_name, 0));
        if ($got_lock !== '1') {
            AsiaAuto_Logger::warning("Sync for {$source} already running (lock held), skipping");
            return;
        }

        $option_key = "asiaauto_last_change_id_{$source}";
        $last_change_id = (int) get_option($option_key, 0);

        // If first run ever, get change_id from yesterday
        if ($last_change_id === 0) {
            $yesterday = gmdate('Y-m-d', strtotime('-1 day'));
            $change_id = $this->api->getChangeId($source, $yesterday);

            if ($change_id === null) {
                AsiaAuto_Logger::error("Failed to get initial change_id for {$source}");
                $this->logRun($source, 0, 0, 0, 0, 0, 'error', 'Failed to get initial change_id');
                $wpdb->query($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock_name));
                return;
            }

            $last_change_id = $change_id;
            AsiaAuto_Logger::info("Initial change_id for {$source}: {$last_change_id}");
        }

        $start_change_id = $last_change_id;
        $total_added     = 0;
        $total_changed   = 0;
        $total_removed   = 0;
        $total_skipped   = 0;
        $batches         = 0;
        $max_batches     = 100; // Safety limit per run
        $errors          = [];

        AsiaAuto_Logger::info("Starting sync for {$source} from change_id={$last_change_id}");

        while ($batches < $max_batches) {
            $response = $this->api->getChanges($source, $last_change_id);

            if ($response === null) {
                $errors[] = "API error at change_id={$last_change_id}";
                AsiaAuto_Logger::error("Failed to fetch changes for {$source} at change_id={$last_change_id}");
                break;
            }

            $results = $response['result'] ?? [];
            $meta    = $response['meta'] ?? [];

            if (empty($results)) {
                break;
            }

            foreach ($results as $change) {
                $type     = $change['change_type'] ?? '';
                $inner_id = $change['inner_id'] ?? '';
                $data     = $change['data'] ?? [];

                // Ensure inner_id is in data (API returns it at change level, not inside data)
                if ($inner_id !== "" && !isset($data["inner_id"])) {
                    $data["inner_id"] = $inner_id;
                }

                if (empty($inner_id)) {
                    continue;
                }

                switch ($type) {
                    case 'added':
                        $post_id = $this->importWithFullData($inner_id, $source, $data);
                        if ($post_id) {
                            $total_added++;
                        } else {
                            $total_skipped++;
                        }
                        break;

                    case 'changed':
                        $post_id = $this->importer->findByInnerId($inner_id, $source);
                        if ($post_id) {
                            // Skip update if listing is reserved (price/meta locked)
                            $res_status = get_post_meta($post_id, '_asiaauto_reservation_status', true);
                            if (!empty($res_status)) {
                                AsiaAuto_Logger::info("Sync skip: listing #{$post_id} ({$inner_id}) is reserved ({$res_status}), skipping update");
                                $total_skipped++;
                                break;
                            }
                            // W1 2026-05-16: skip listings zarządzane ręcznie (UI Dodaj-z-Dongchedi / metabox)
                            if ($this->isManuallyManaged($post_id)) {
                                AsiaAuto_Logger::info("Sync skip: listing #{$post_id} ({$inner_id}) is manually managed, skipping changed");
                                $total_skipped++;
                                break;
                            }
                            // T-186: dla che168 aktualizacja też idzie przez adapter —
                            // inaczej updateListing dostałby surowy dialekt (CJK, brak city).
                            $upd = $this->normalizeForSource($data, $source);
                            if ($upd === null) {
                                $total_skipped++;
                                break;
                            }
                            $this->importer->updateListing($post_id, $upd);
                            $total_changed++;
                        } else {
                            // Listing doesn't exist locally — treat as new (full fetch)
                            $new_id = $this->importWithFullData($inner_id, $source, $data);
                            if ($new_id) {
                                $total_added++;
                            } else {
                                $total_skipped++;
                            }
                        }
                        break;

                    case 'removed':
                        $post_id = $this->importer->findByInnerId($inner_id, $source);
                        if ($post_id) {
                            // W1 2026-05-16: skip listings zarządzane ręcznie — sync nie wycofuje
                            if ($this->isManuallyManaged($post_id)) {
                                AsiaAuto_Logger::info("Sync skip: listing #{$post_id} ({$inner_id}) is manually managed, skipping removed");
                                $total_skipped++;
                                break;
                            }
                            $this->rotation->markRemoved($post_id);
                            $total_removed++;
                        }
                        break;

                    default:
                        AsiaAuto_Logger::warning("Unknown change_type: '{$type}' for {$source}:{$inner_id}");
                }
            }

            // Update last processed change_id
            $next_change_id = $meta['next_change_id'] ?? $last_change_id;

            if ($next_change_id <= $last_change_id) {
                break;
            }

            $last_change_id = $next_change_id;
            $batches++;

            usleep(150_000); // 150ms throttle
        }

        // Persist last change_id
        update_option($option_key, $last_change_id);
        // Release advisory lock
        $wpdb->query($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock_name));

        $status = empty($errors) ? 'ok' : 'partial';

        AsiaAuto_Logger::info(
            "Sync complete for {$source}: "
            . "+{$total_added} added, ~{$total_changed} changed, -{$total_removed} removed, "
            . "x{$total_skipped} skipped "
            . "({$batches} batches, change_id: {$start_change_id}→{$last_change_id})"
        );

        // Save to sync history
        $this->logRun(
            $source,
            $total_added,
            $total_changed,
            $total_removed,
            $total_skipped,
            $batches,
            $status,
            implode('; ', $errors)
        );
    }

    /**
     * Sprawdza czy listing jest zarządzany ręcznie (NIE należy do automatycznego sync).
     *
     * Ustawiane przez:
     * - _asiaauto_manual_import=1 — import z UI „Dodaj z Dongchedi" (AsiaAuto_Admin_Manual_Import::ajaxImport)
     * - _asiaauto_manual_entry=1  — pierwszy zapis listingu przez metabox „Dane pojazdu" (AsiaAuto_Listing_Editor::handleSave)
     *
     * Wprowadzone w W1 2026-05-16 — wcześniej sync wycofywał ogłoszenia które Ruslan dodawał ręcznie.
     */
    private function isManuallyManaged(int $post_id): bool {
        if ((string) get_post_meta($post_id, '_asiaauto_manual_import', true) === '1') {
            return true;
        }
        if ((string) get_post_meta($post_id, '_asiaauto_manual_entry', true) === '1') {
            return true;
        }
        return false;
    }

    /**
     * Fetch full offer data via getOffer() and import.
     *
     * /changes 'added' only returns partial data (no extra_prep, no equipment).
     * getOffer() returns the complete listing with all fields.
     * Falls back to partial data from /changes if getOffer() fails.
     *
     * @param string $inner_id  Listing inner_id
     * @param string $source    Source platform
     * @param array  $data      Partial data from /changes (fallback)
     * @return int|null          Post ID or null
     */
    private function importWithFullData(string $inner_id, string $source, array $data): ?int {
        $status = self::statusForSource($source);

        // T-186: filtr wstępny na danych z /changes, PRZED getOffer(). Che168 wypuszcza
        // ~7-11 tys. nowych ofert na dobę, z czego kryteria spełnia ~1% — bez tego
        // każde zdarzenie kosztowałoby jedno wywołanie API. Dane z /changes mają komplet
        // pól filtra (mark/model/year/km_age/price/address), więc werdykt jest ten sam.
        if ($source === 'che168' && !empty($data)) {
            $pre = AsiaAuto_Che168_Adapter::normalize($data);
            // Kolejność ma znaczenie: najpierw filtr konfigu (marka/rocznik/przebieg/cena/
            // miasto), dopiero potem guard mapowania. Odwrotnie kolejka domapowań zapełnia
            // się BMW/Mercedesami z całych Chin, których i tak nigdy nie importujemy.
            if (!$this->importer->isAllowedByConfig($pre, $source)) {
                return null;
            }
            if (!$this->isMappedForImport($pre)) {
                return null;
            }
        }

        // Try to fetch complete data via getOffer()
        $full_data = $this->api->getOffer($source, $inner_id);

        if ($full_data !== null) {
            // getOffer returns flat array with all fields
            // Ensure inner_id is present
            if (!isset($full_data['inner_id'])) {
                $full_data['inner_id'] = $inner_id;
            }
            $full_data = $this->normalizeForSource($full_data, $source);
            if ($full_data === null) {
                return null;
            }
            return $this->importer->importListing($full_data, $source, false, $status);
        }

        // Fallback: use partial data from /changes (no extra_prep)
        AsiaAuto_Logger::warning("getOffer failed for {$source}:{$inner_id}, using partial data from /changes");
        $data = $this->normalizeForSource($data, $source);
        if ($data === null) {
            return null;
        }
        return $this->importer->importListing($data, $source, false, $status);
    }

    /**
     * Normalizacja dialektu źródła PRZY WEJŚCIU do ścieżki automatycznej (T-186, 2026-07-22).
     *
     * Dotąd adapter Che168 był wołany wyłącznie w imporcie ręcznym — automat podawał
     * importerowi surowy dialekt (brak `city` → filtr miast odrzucał 100%, CJK w mark/model).
     * ADR `2026-06-17-che168-normalize-at-entry.md`: normalizujemy w adapterze,
     * `importListing()` i niżej pozostają bez gałęzi per-source.
     *
     * Zwraca null, gdy oferty nie wolno importować automatem (niezmapowany model —
     * chroni przed CJK w tytułach i przed spalinowymi modelami spoza segmentu).
     */
    private function normalizeForSource(array $data, string $source): ?array {
        if ($source !== 'che168') {
            return $data;
        }

        $data = AsiaAuto_Che168_Adapter::normalize($data);

        return $this->isMappedForImport($data) ? $data : null;
    }

    /**
     * Guard: czy znormalizowana oferta che168 trafia w istniejący hub.
     * Chroni przed CJK w tytułach i przed modelami spoza segmentu (spalinowe,
     * marki wycofane) — te zostają w kolejce domapowań do decyzji człowieka.
     */
    private function isMappedForImport(array $data): bool {
        $mark  = (string) ($data['mark'] ?? '');
        $model = (string) ($data['model'] ?? '');
        if (AsiaAuto_Mapping::getEuForCn($mark, $model) !== null) {
            return true;
        }
        self::logUnmapped($data);
        AsiaAuto_Logger::info("Sync skip (che168): niezmapowany model '{$mark}|{$model}' "
            . '(' . ($data['inner_id'] ?? '?') . ') — do kolejki domapowań');
        return false;
    }

    /**
     * Kolejka domapowań — niezmapowane pary mark|model pominięte przez automat.
     * Trzymane w opcji (ostatnie 200), do przeglądu w panelu / przy kalibracji map.
     */
    private static function logUnmapped(array $data): void {
        $queue = get_option('asiaauto_che168_unmapped', []);
        if (!is_array($queue)) {
            $queue = [];
        }
        $key = (string) ($data['mark'] ?? '?') . '|' . (string) ($data['model'] ?? '?');
        if (!isset($queue[$key])) {
            $queue[$key] = [
                'count'     => 0,
                'first_seen'=> gmdate('c'),
                'raw'       => (string) ($data['mark_che168_raw'] ?? '') . '|' . (string) ($data['model_che168_raw'] ?? ''),
                'example'   => (string) ($data['inner_id'] ?? ''),
                'engine'    => (string) ($data['engine_type'] ?? ''),
                'price'     => (int) ($data['price'] ?? 0),
            ];
        }
        $queue[$key]['count']++;
        $queue[$key]['last_seen'] = gmdate('c');
        if (count($queue) > 200) {
            $queue = array_slice($queue, -200, null, true);
        }
        update_option('asiaauto_che168_unmapped', $queue, false);
    }

    /**
     * Log sync run to wp_options history.
     */
    private function logRun(
        string $source,
        int $added,
        int $changed,
        int $removed,
        int $skipped,
        int $batches,
        string $status,
        string $error_msg = ''
    ): void {
        $history = get_option('asiaauto_sync_history', []);

        if (!is_array($history)) {
            $history = [];
        }

        $entry = [
            'ts'      => gmdate('c'),
            'source'  => $source,
            'added'   => $added,
            'changed' => $changed,
            'removed' => $removed,
            'skipped' => $skipped,
            'batches' => $batches,
            'status'  => $status,
        ];

        if (!empty($error_msg)) {
            $entry['error'] = $error_msg;
        }

        // Prepend (newest first)
        array_unshift($history, $entry);

        // Keep only last N entries
        if (count($history) > $this->max_history) {
            $history = array_slice($history, 0, $this->max_history);
        }

        update_option('asiaauto_sync_history', $history, false); // no autoload
    }

    /**
     * Get sync history.
     *
     * @param int $limit  Max entries to return
     * @return array
     */
    public static function getHistory(int $limit = 20): array {
        $history = get_option('asiaauto_sync_history', []);
        return is_array($history) ? array_slice($history, 0, $limit) : [];
    }

    /**
     * Get last sync summary (for admin status widget).
     *
     * @return array|null  Last sync entry or null
     */
    public static function getLastSync(): ?array {
        $history = get_option('asiaauto_sync_history', []);
        return is_array($history) && !empty($history) ? $history[0] : null;
    }

    /**
     * Clear sync history.
     */
    public static function clearHistory(): void {
        delete_option('asiaauto_sync_history');
    }
}
