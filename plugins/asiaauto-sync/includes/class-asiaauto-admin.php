<?php

defined('ABSPATH') || exit;

/**
 * Admin settings page for Prima-Auto import configuration.
 * Accessible via: WP Admin → Narzędzia → Prima-Auto Import
 *
 * Stores config in wp_options:
 * - 'asiaauto_import_config'  — per-source filters (marks, year, km, price)
 * - 'asiaauto_price_config'   — full cost breakdown (via AsiaAuto_Price)
 * - 'asiaauto_sync_enabled'   — sync cron toggle (0/1)
 *
 * @since 0.7.0  Przebudowany moduł cenowy z pełnym pipeline kosztów składowych.
 * @since 0.18.0 Dodano VAT + akcyza do kalkulatora cen.
 * @since 0.19.0 Toggle synchronizacji w panelu Status.
 * @since 0.28.0 Nowy pipeline: reorder + agencja celna + VAT na końcu,
 *               horyzontalna tabela akcyzy (paliwa w kolumnach).
 * @since 0.30.5 Kalkulator cen USD-centric: karty sekcyjne, bulk recalc AJAX,
 *               pola USD zamiast PLN w kroku 1-2, lazy upgrade v1→v2.
 * @since 0.30.6 Widget „Podgląd kalkulacji": kolumna „CIF PLN" zastąpiona
 *               jedną kolumną „CIF" dwuwierszową (USD na górze bold, PLN pod
 *               spodem szare/małe) — spójnie z metaboksem ręcznych ogłoszeń,
 *               gdzie pole override to `_asiaauto_cif_price_usd`.
 *               Dodany profiler `?aa_profile=1` — komentarz HTML z czasami
 *               per sekcja, do diagnostyki wolnego ładowania.
 */
class AsiaAuto_Admin {

    private string $option_key = 'asiaauto_import_config';
    private string $page_slug  = 'asiaauto-import-config';

    /**
     * Profiler — akumulator timestampów per sekcja.
     * Aktywny tylko gdy `?aa_profile=1` jest w URL (i user może manage_options).
     * Wynik renderowany jako HTML comment na końcu strony.
     *
     * @var array<string, array{start:float, end:?float, duration_ms:?float}>
     */
    private array $profile = [];

    private bool $profile_enabled = false;

    public function __construct() {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'handleSave']);
        add_action('wp_ajax_asiaauto_bulk_recalc', [$this, 'ajaxBulkRecalc']);
        add_action('admin_post_asiaauto_che168_dict_refresh', [$this, 'handleChe168DictRefresh']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueDiagAssets']);
    }

    /**
     * Gate fazy testów filtrów Che168: login ∈ ASIAAUTO_CHE168_PREVIEW (CSV, wp-config).
     * Ruslan nie widzi subtabs/che168 do czasu oddania (usunięcie gate = świadoma decyzja).
     */
    private function che168FiltersAllowed(): bool {
        if (!defined('ASIAAUTO_CHE168_PREVIEW') || ASIAAUTO_CHE168_PREVIEW === '') {
            return false;
        }
        $allowed = array_map('trim', explode(',', (string) ASIAAUTO_CHE168_PREVIEW));
        return in_array(wp_get_current_user()->user_login, $allowed, true);
    }

    /**
     * Wymuszone odświeżenie słownika modeli Che168 (przycisk w zakładce Filtry).
     */
    public function handleChe168DictRefresh(): void {
        if (!current_user_can(AsiaAuto_Security::IMPORT_CAP) || !$this->che168FiltersAllowed()) {
            wp_die('Brak uprawnień.');
        }
        check_admin_referer('asiaauto_che168_dict');
        $ok  = AsiaAuto_Che168_Dictionary::refresh() !== null;
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

    public function addMenuPage(): void {
        add_submenu_page(
            'edit.php?post_type=listings',
            'Prima-Auto — Konfigurator importu',
            'Konfigurator importu',
            AsiaAuto_Security::IMPORT_CAP,
            $this->page_slug,
            [$this, 'renderPage']
        );
        add_submenu_page(
            'edit.php?post_type=listings',
            'Prima-Auto — Diagnostyka',
            'Diagnostyka',
            'manage_options',
            'asiaauto-diag',
            ['AsiaAuto_Admin_Diag', 'render']
        );
    }

    public function enqueueDiagAssets(): void {
        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        if ($page !== 'asiaauto-diag') return;

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

    public function handleSave(): void {
        if (!isset($_POST['asiaauto_config_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field((string) wp_unslash($_POST['asiaauto_config_nonce'])), 'asiaauto_save_config')) {
            wp_die('Nonce verification failed.');
        }

        if (!current_user_can(AsiaAuto_Security::IMPORT_CAP)) {
            wp_die('Brak uprawnień.');
        }

        $section = sanitize_text_field((string) wp_unslash($_POST['_save_section'] ?? ''));

        // === Toggle sync cron ===
        if ($section === 'sync_toggle') {
            $new_state = !empty($_POST['sync_enabled']);
            $old_state = (bool) get_option('asiaauto_sync_enabled', false);

            update_option('asiaauto_sync_enabled', $new_state ? '1' : '0');

            if ($new_state && !$old_state) {
                if (!wp_next_scheduled('asiaauto_sync_changes')) {
                    wp_schedule_event(time() + 60, 'asiaauto_15min', 'asiaauto_sync_changes');
                }
                add_settings_error('asiaauto', 'sync_enabled', 'Synchronizacja WŁĄCZONA. Cron zaplanowany.', 'success');
                AsiaAuto_Logger::info('Sync enabled via admin panel');
            } elseif (!$new_state && $old_state) {
                wp_clear_scheduled_hook('asiaauto_sync_changes');
                add_settings_error('asiaauto', 'sync_disabled', 'Synchronizacja WYŁĄCZONA. Cron usunięty.', 'warning');
                AsiaAuto_Logger::info('Sync disabled via admin panel');
            }

            return;
        }

        // === Toggle sync per źródło (T-186, 2026-07-22) ===
        // Master-switch `asiaauto_sync_enabled` gasi wszystko; te przełączniki decydują,
        // które źródło biegnie, gdy master jest włączony.
        if ($section === 'sync_source_toggle') {
            $src = sanitize_key((string) wp_unslash($_POST['sync_source'] ?? ''));
            if (!in_array($src, ['dongchedi', 'che168'], true)) {
                return;
            }
            $new_state = !empty($_POST['source_enabled']);
            update_option("asiaauto_sync_enabled_{$src}", $new_state ? '1' : '0');
            add_settings_error(
                'asiaauto',
                'sync_source',
                sprintf('Źródło %s: sync %s.', $src, $new_state ? 'WŁĄCZONY' : 'WYŁĄCZONY'),
                $new_state ? 'success' : 'warning'
            );
            AsiaAuto_Logger::info("Sync source {$src} " . ($new_state ? 'enabled' : 'disabled') . ' via admin panel');
            return;
        }

        // === Save price config ===
        if ($section === 'price') {
            $akcyza_stawki = [];
            $raw = $_POST['akcyza_stawki'] ?? [];
            if (is_array($raw)) {
                foreach ($raw as $slug => $rate) {
                    $akcyza_stawki[sanitize_key((string) $slug)] = $rate;
                }
            }

            // UI field "kurs_usd_cny" = ile ¥ za 1 USD (np. 6.80). W storage trzymamy odwrotność
            // (kurs_cny_usd, USD za 1 ¥) — pipeline cenowy mnoży price_cny × kurs_cny_usd.
            $kurs_usd_cny_raw = (float) str_replace(',', '.', (string) ($_POST['kurs_usd_cny'] ?? 0));
            $kurs_cny_usd_stored = $kurs_usd_cny_raw > 0 ? round(1 / $kurs_usd_cny_raw, 6) : 0;

            AsiaAuto_Price::saveConfig([
                'kurs_cny_usd'                    => $kurs_cny_usd_stored,
                'kurs_usd_pln'                    => $_POST['kurs_usd_pln'] ?? 0,
                'chiny_rejestracja_transport_usd' => $_POST['chiny_rejestracja_transport_usd'] ?? 5000,
                'agencja_celna_wyladunek'         => $_POST['agencja_celna_wyladunek'] ?? 2600,
                'clo_procent'                     => $_POST['clo_procent'] ?? 10,
                'transport_z_portu'               => $_POST['transport_z_portu'] ?? 3400,
                'akcyza_stawki'                   => $akcyza_stawki,
                'homologacja_detailing'           => $_POST['homologacja_detailing'] ?? 1500,
                'prowizja_procent'                => $_POST['prowizja_procent'] ?? 10,
                'prowizja_min'                    => $_POST['prowizja_min'] ?? 11000,
                'vat_procent'                     => $_POST['vat_procent'] ?? 23,
                'zaokraglenie'                    => $_POST['zaokraglenie'] ?? 1000,
            ]);

            add_settings_error('asiaauto', 'price_saved', 'Konfiguracja cen zapisana. Aby przeliczyć wszystkie ogłoszenia, użyj przycisku „Przelicz wszystkie listingi".', 'success');
            return;
        }

        // === Save import filters ===
        if ($section === 'filters') {
            $source = sanitize_text_field((string) wp_unslash($_POST['source'] ?? 'dongchedi'));

            if ($source === 'che168' && !$this->che168FiltersAllowed()) {
                add_settings_error('asiaauto', 'che168_gated', 'Filtry Che168 są w fazie testów — brak dostępu.', 'error');
                return;
            }

            $config = get_option($this->option_key, []);
            $raw_cities = json_decode(wp_unslash($_POST['city_filter_cities'] ?? '[]'), true);
            $city_filter_cities = [];
            foreach ((is_array($raw_cities) ? $raw_cities : []) as $c) {
                if (!empty($c['zh'])) {
                    $city_filter_cities[] = [
                        'zh'       => sanitize_text_field($c['zh']),
                        'pl'       => sanitize_text_field($c['pl'] ?? ''),
                        'province' => sanitize_text_field($c['province'] ?? ''),
                    ];
                }
            }

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

            $config[$source] = [
                'enabled'             => !empty($_POST['enabled']),
                'marks'               => array_map('sanitize_text_field', (array) ($_POST['marks'] ?? [])),
                'model_blacklist'     => $model_blacklist,
                'year_from'           => !empty($_POST['year_from']) ? (int) $_POST['year_from'] : null,
                'year_to'             => !empty($_POST['year_to']) ? (int) $_POST['year_to'] : null,
                'km_from'             => !empty($_POST['km_from']) ? (int) $_POST['km_from'] : null,
                'km_to'               => !empty($_POST['km_to']) ? (int) $_POST['km_to'] : null,
                'price_from'          => !empty($_POST['price_from']) ? (int) $_POST['price_from'] : null,
                'price_to'            => !empty($_POST['price_to']) ? (int) $_POST['price_to'] : null,
                'city_filter_enabled' => !empty($_POST['city_filter_enabled']),
                'city_filter_cities'  => $city_filter_cities,
            ];
            update_option($this->option_key, $config);

            add_settings_error('asiaauto', 'filters_saved', 'Filtry importu zapisane.', 'success');
            return;
        }
    }

    /**
     * AJAX endpoint: przelicz wszystkie listingi.
     */
    public function ajaxBulkRecalc(): void {
        check_ajax_referer('asiaauto_bulk_recalc', '_nonce');

        if (!current_user_can(AsiaAuto_Security::IMPORT_CAP)) {
            wp_send_json_error(['msg' => 'Brak uprawnień.'], 403);
        }

        try {
            $stats = AsiaAuto_Price::bulkRecalculate();
            delete_transient('asiaauto_listing_counts');
        } catch (\Throwable $e) {
            wp_send_json_error(['msg' => 'Błąd bulk recalc: ' . $e->getMessage()], 500);
        }

        wp_send_json_success([
            'stats' => $stats,
            'msg'   => sprintf(
                'Przeliczono %d z %d listingów (CIF USD: %d, CNY: %d, legacy: %d). Zmian cen: %d.%s',
                $stats['recalculated'],
                $stats['total'],
                $stats['cif_usd_count'],
                $stats['cny_count'],
                $stats['legacy_count'],
                $stats['price_changes'],
                $stats['errors'] > 0 ? ' Błędy: ' . $stats['errors'] . '.' : ''
            ),
        ]);
    }

    // =========================================================================
    // PROFILER — opt-in via ?aa_profile=1
    // =========================================================================

    /**
     * Start a profiling section. No-op when profiler is disabled.
     */
    private function profileStart(string $section): void {
        if (!$this->profile_enabled) {
            return;
        }
        $this->profile[$section] = [
            'start'       => microtime(true),
            'end'         => null,
            'duration_ms' => null,
        ];
    }

    /**
     * End a profiling section.
     */
    private function profileEnd(string $section): void {
        if (!$this->profile_enabled || !isset($this->profile[$section])) {
            return;
        }
        $end = microtime(true);
        $this->profile[$section]['end']         = $end;
        $this->profile[$section]['duration_ms'] = round(($end - $this->profile[$section]['start']) * 1000, 2);
    }

    /**
     * Render profiler results as HTML comment + visible warning bar.
     */
    private function renderProfileReport(): void {
        if (!$this->profile_enabled || empty($this->profile)) {
            return;
        }

        $total = 0.0;
        foreach ($this->profile as $row) {
            if ($row['duration_ms'] !== null) {
                $total += $row['duration_ms'];
            }
        }

        echo "\n<!-- ============================================================\n";
        echo "     Prima-Auto Admin Profiler (?aa_profile=1)\n";
        echo "     ============================================================\n";
        printf("     %-44s %10s\n", 'Section', 'Duration');
        echo "     ------------------------------------------------------------\n";
        foreach ($this->profile as $section => $row) {
            $dur = $row['duration_ms'];
            $dur_str = $dur === null ? '(unclosed)' : number_format($dur, 2, '.', '') . ' ms';
            printf("     %-44s %10s\n", $section, $dur_str);
        }
        echo "     ------------------------------------------------------------\n";
        printf("     %-44s %10s\n", 'TOTAL (sum of measured sections)', number_format($total, 2, '.', '') . ' ms');
        echo "     ============================================================\n";
        echo "-->\n";

        // Visible bar dla wygody
        echo '<div style="background:#fff3cd;border:1px solid #ffc107;border-radius:4px;padding:10px 14px;margin:20px 0;font-family:monospace;font-size:12px;">';
        echo '<strong>⏱ Profiler aktywny</strong> (?aa_profile=1) — sumarycznie <strong>' . esc_html(number_format($total, 2, '.', '')) . ' ms</strong>. Szczegóły w komentarzu HTML źródła strony.';
        echo '<details style="margin-top:8px;"><summary style="cursor:pointer;">Pokaż tabelę inline</summary>';
        echo '<table style="margin-top:8px;border-collapse:collapse;width:100%;"><thead><tr><th style="text-align:left;padding:4px 8px;border-bottom:1px solid #ddd;">Sekcja</th><th style="text-align:right;padding:4px 8px;border-bottom:1px solid #ddd;">Czas</th></tr></thead><tbody>';
        foreach ($this->profile as $section => $row) {
            $dur = $row['duration_ms'];
            $dur_str = $dur === null ? '(unclosed)' : number_format($dur, 2, '.', '') . ' ms';
            $color = $dur !== null && $dur > 100 ? '#dc2626' : ($dur !== null && $dur > 30 ? '#d97706' : '#475569');
            echo '<tr><td style="padding:3px 8px;">' . esc_html($section) . '</td>';
            echo '<td style="padding:3px 8px;text-align:right;color:' . $color . ';font-variant-numeric:tabular-nums;">' . esc_html($dur_str) . '</td></tr>';
        }
        echo '</tbody></table></details></div>';
    }

    public function renderPage(): void {
        // Profiler opt-in
        $this->profile_enabled = isset($_GET['aa_profile']) && $_GET['aa_profile'] === '1' && current_user_can('manage_options');

        $this->profileStart('renderPage:total');

        $source = sanitize_text_field((string) wp_unslash($_GET['source'] ?? 'dongchedi'));
        $tab    = sanitize_text_field((string) wp_unslash($_GET['tab'] ?? 'price'));

        // Faza testów: che168 tylko dla loginów z ASIAAUTO_CHE168_PREVIEW; reszta widzi dongchedi.
        if ($source === 'che168' && !$this->che168FiltersAllowed()) {
            $source = 'dongchedi';
        }

        $this->profileStart('renderPage:get_options');
        $config = get_option($this->option_key, []);
        $sc     = $config[$source] ?? [];
        $pc     = AsiaAuto_Price::getConfig();
        $this->profileEnd('renderPage:get_options');

        $this->profileStart('renderPage:rotation_stats');
        $rotation = new AsiaAuto_Rotation();
        $stats    = $rotation->getStats();
        $this->profileEnd('renderPage:rotation_stats');

        settings_errors('asiaauto');

        if (isset($_GET['dict'])) {
            $dict_ok = $_GET['dict'] === 'ok';
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                $dict_ok ? 'success' : 'error',
                $dict_ok ? 'Słownik modeli Che168 odświeżony z API.' : 'Nie udało się odświeżyć słownika Che168 (API niedostępne?).'
            );
        }
        ?>
        <div class="wrap">
            <h1>Prima-Auto — Konfigurator</h1>

            <?php
            $this->profileStart('renderStatus');
            $this->renderStatus($stats);
            $this->profileEnd('renderStatus');
            ?>

            <nav class="nav-tab-wrapper" style="margin-bottom:20px;">
                <a href="<?php echo esc_url(add_query_arg('tab', 'price')); ?>"
                   class="nav-tab <?php echo $tab === 'price' ? 'nav-tab-active' : ''; ?>">
                    Kalkulator cen
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'filters')); ?>"
                   class="nav-tab <?php echo $tab === 'filters' ? 'nav-tab-active' : ''; ?>">
                    Filtry importu
                </a>
            </nav>

            <?php if ($tab === 'filters' && $this->che168FiltersAllowed()) : ?>
            <ul class="subsubsub" style="margin:-10px 0 20px;float:none;display:block;">
                <li><a href="<?php echo esc_url(add_query_arg(['tab' => 'filters', 'source' => 'dongchedi'])); ?>"
                       class="<?php echo $source === 'dongchedi' ? 'current' : ''; ?>">Dongchedi</a> |</li>
                <li><a href="<?php echo esc_url(add_query_arg(['tab' => 'filters', 'source' => 'che168'])); ?>"
                       class="<?php echo $source === 'che168' ? 'current' : ''; ?>">Che168</a></li>
            </ul>
            <?php endif; ?>

            <?php
            if ($tab === 'price') {
                $this->profileStart('renderPriceTab');
                $this->renderPriceTab($pc);
                $this->profileEnd('renderPriceTab');
            } else {
                $this->profileStart('renderFiltersTab');
                $this->renderFiltersTab($sc, $source);
                $this->profileEnd('renderFiltersTab');
            }
            ?>
        </div>
        <?php

        $this->profileEnd('renderPage:total');
        $this->renderProfileReport();
    }

    // =========================================================================
    // STATUS + SYNC TOGGLE
    // =========================================================================

    private function renderStatus(array $stats): void {
        $sync_enabled = (bool) get_option('asiaauto_sync_enabled', false);
        $next_sync    = wp_next_scheduled('asiaauto_sync_changes');
        $next_cleanup = wp_next_scheduled('asiaauto_daily_cleanup');

        $this->profileStart('renderStatus:disk_usage');
        $disk = get_transient('asiaauto_disk_usage');
        if ($disk === false) {
            $disk = AsiaAuto_Media::getDiskUsage();
            set_transient('asiaauto_disk_usage', $disk, HOUR_IN_SECONDS);
        }
        $this->profileEnd('renderStatus:disk_usage');
        ?>
        <div style="background:#fff;border:1px solid #ccd0d4;padding:15px;margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap;">
                <div style="flex:1;min-width:300px;">
                    <h3 style="margin-top:0;">Status</h3>
                    <table class="widefat" style="max-width:500px;">
                        <tr><td>Aktywne ogłoszenia</td><td><strong><?php echo esc_html($stats['active']); ?></strong></td></tr>
                        <tr><td>Sprzedane (oczekujące 48h)</td><td><?php echo esc_html($stats['sold_pending']); ?></td></tr>
                        <tr><td>W koszu</td><td><?php echo esc_html($stats['trashed']); ?></td></tr>
                        <tr>
                            <td>Zdjęcia na dysku</td>
                            <td>
                                <strong><?php echo esc_html(number_format($disk['count'], 0, ',', ' ')); ?></strong> plików
                                (<?php echo esc_html($disk['size_human']); ?>)
                            </td>
                        </tr>
                        <?php
                        $sources_list = defined('ASIAAUTO_SOURCES') ? ASIAAUTO_SOURCES : ['dongchedi'];
                        foreach ($sources_list as $s) {
                            $cid = get_option("asiaauto_last_change_id_{$s}", 'brak');
                            echo "<tr><td>Ostatni change_id ({$s})</td><td>" . esc_html($cid) . "</td></tr>";
                        }
                        ?>
                        <tr>
                            <td>Następny sync</td>
                            <td><?php echo $next_sync ? esc_html(wp_date('d.m.Y H:i:s', $next_sync)) : '<em>nie zaplanowany</em>'; ?></td>
                        </tr>
                        <tr>
                            <td>Następny cleanup</td>
                            <td><?php echo $next_cleanup ? esc_html(wp_date('d.m.Y H:i:s', $next_cleanup)) : '<em>nie zaplanowany</em>'; ?></td>
                        </tr>
                    </table>
                </div>

                <div style="background:<?php echo $sync_enabled ? '#e6f7ee' : '#fef3f2'; ?>;border:2px solid <?php echo $sync_enabled ? '#38a169' : '#d63031'; ?>;border-radius:8px;padding:15px 20px;text-align:center;min-width:220px;">
                    <div style="font-size:13px;font-weight:600;color:#718096;margin-bottom:5px;">Synchronizacja API</div>
                    <div style="font-size:22px;font-weight:700;color:<?php echo $sync_enabled ? '#276749' : '#9b1c1c'; ?>;margin-bottom:10px;">
                        <?php echo $sync_enabled ? '● AKTYWNA' : '○ WYŁĄCZONA'; ?>
                    </div>
                    <form method="post" style="margin:0;">
                        <?php wp_nonce_field('asiaauto_save_config', 'asiaauto_config_nonce'); ?>
                        <input type="hidden" name="_save_section" value="sync_toggle">
                        <?php if ($sync_enabled): ?>
                            <input type="hidden" name="sync_enabled" value="0">
                            <button type="submit" class="button"
                                    style="background:#d63031;color:#fff;border-color:#b52828;"
                                    onclick="return confirm('Wyłączyć synchronizację? Cron zostanie usunięty.');">
                                ⏸ Zatrzymaj sync
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="sync_enabled" value="1">
                            <button type="submit" class="button button-primary"
                                    style="background:#38a169;border-color:#2f855a;">
                                ▶ Włącz sync
                            </button>
                        <?php endif; ?>
                    </form>
                    <?php if ($sync_enabled && $next_sync): ?>
                        <div style="font-size:11px;color:#718096;margin-top:6px;">co 15 min</div>
                    <?php elseif (!$sync_enabled): ?>
                        <div style="font-size:11px;color:#9b1c1c;margin-top:6px;">import i sync wstrzymane</div>
                    <?php endif; ?>

                    <div style="margin-top:10px;padding-top:8px;border-top:1px solid #e2e8f0;">
                        <div style="font-size:11px;color:#4a5568;margin-bottom:4px;">Źródła</div>
                        <?php foreach (['dongchedi' => 'Dongchedi', 'che168' => 'Che168'] as $src => $label):
                            $src_on     = AsiaAuto_Sync::isEnabledForSource($src);
                            $src_status = AsiaAuto_Sync::statusForSource($src);
                            ?>
                            <form method="post" style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                                <?php wp_nonce_field('asiaauto_save_config', 'asiaauto_config_nonce'); ?>
                                <input type="hidden" name="_save_section" value="sync_source_toggle">
                                <input type="hidden" name="sync_source" value="<?php echo esc_attr($src); ?>">
                                <input type="hidden" name="source_enabled" value="<?php echo $src_on ? '0' : '1'; ?>">
                                <span style="font-size:12px;min-width:74px;"><?php echo esc_html($label); ?></span>
                                <button type="submit" class="button button-small">
                                    <?php echo $src_on ? '⏸ wyłącz' : '▶ włącz'; ?>
                                </button>
                                <span style="font-size:11px;color:<?php echo $src_on ? '#2f855a' : '#9b1c1c'; ?>;">
                                    <?php echo $src_on ? 'aktywne' : 'wstrzymane'; ?>
                                    <?php if ($src_status === 'draft'): ?>
                                        · importuje do szkiców
                                    <?php endif; ?>
                                </span>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    // =========================================================================
    // TAB: KALKULATOR CEN — v0.30.5/0.30.6 karty sekcyjne
    // =========================================================================

    private function renderPriceTab(array $pc): void {
        $fuel_labels = [
            'petrol'   => 'Benzyna',
            'diesel'   => 'Diesel',
            'hybrid'   => 'HEV',
            'mhev'     => 'MHEV',
            'phev'     => 'PHEV',
            'electric' => 'EV',
            'erev'     => 'EREV',
            'cng'      => 'CNG',
            'bi-fuel'  => 'Bi-fuel',
        ];

        $this->profileStart('renderPriceTab:fuel_terms');
        $fuel_terms = get_terms([
            'taxonomy'   => 'fuel',
            'hide_empty' => false,
        ]);
        $fuel_counts = [];
        if (!is_wp_error($fuel_terms)) {
            foreach ($fuel_terms as $term) {
                $fuel_counts[$term->slug] = (int) $term->count;
            }
        }
        $this->profileEnd('renderPriceTab:fuel_terms');

        $akcyza_stawki = $pc['akcyza_stawki'] ?? [];

        // Bulk recalc stats — 3× COUNT(*) na postmeta (cached 10 min)
        $this->profileStart('renderPriceTab:counts_postmeta');
        global $wpdb;
        $listing_counts = get_transient('asiaauto_listing_counts');
        if ($listing_counts === false) {
            $listing_counts = [
                'cny'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_asiaauto_original_price' AND meta_value > 0"),
                'cif_usd' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_asiaauto_cif_price_usd' AND meta_value > 0"),
                'legacy'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_asiaauto_cif_price_pln' AND meta_value > 0"),
            ];
            set_transient('asiaauto_listing_counts', $listing_counts, 10 * MINUTE_IN_SECONDS);
        }
        $cnt_cny        = $listing_counts['cny'];
        $cnt_cif_usd    = $listing_counts['cif_usd'];
        $cnt_cif_legacy = $listing_counts['legacy'];
        $this->profileEnd('renderPriceTab:counts_postmeta');

        $ajax_url = admin_url('admin-ajax.php');
        $recalc_nonce = wp_create_nonce('asiaauto_bulk_recalc');
        ?>
        <style>
            .aa-pc-intro {background:#fff;border-left:4px solid #3b82f6;padding:14px 18px;margin-bottom:20px;border-radius:4px;font-size:13px;line-height:1.6;color:#334155}
            .aa-pc-intro strong{color:#1e293b}
            .aa-pc-intro code{background:#eff6ff;padding:1px 6px;border-radius:3px;font-size:12px;color:#1e40af}

            .aa-pc-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px;margin-bottom:20px}
            @media(max-width:1200px){.aa-pc-grid{grid-template-columns:1fr}}

            .aa-pc-card{background:#fff;border-radius:8px;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(0,0,0,.04);overflow:hidden}
            .aa-pc-card-header{padding:14px 18px;border-bottom:2px solid;display:flex;align-items:center;gap:10px}
            .aa-pc-card-header .aa-icon{font-size:22px;line-height:1}
            .aa-pc-card-header h3{margin:0;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
            .aa-pc-card-body{padding:16px 18px}
            .aa-pc-card-body p.aa-hint{margin:0 0 12px;color:#64748b;font-size:12px;line-height:1.5}
            .aa-pc-card-body p.aa-hint:last-child{margin-bottom:0}
            .aa-pc-card-body .aa-field{margin-bottom:14px}
            .aa-pc-card-body .aa-field:last-child{margin-bottom:0}
            .aa-pc-card-body .aa-field label{display:block;font-weight:600;color:#334155;margin-bottom:4px;font-size:12px;text-transform:uppercase;letter-spacing:.3px}
            .aa-pc-card-body .aa-field label small{text-transform:none;font-weight:500;color:#94a3b8;letter-spacing:0;margin-left:4px}
            .aa-pc-card-body .aa-field input[type=text],
            .aa-pc-card-body .aa-field input[type=number]{width:100%;max-width:180px;padding:7px 10px;border:1px solid #cbd5e1;border-radius:4px;font-size:14px;font-variant-numeric:tabular-nums;box-sizing:border-box}
            .aa-pc-card-body .aa-field .aa-suffix{margin-left:6px;color:#64748b;font-weight:600;font-size:13px}
            .aa-pc-card-body .aa-inline-2{display:flex;gap:14px;flex-wrap:wrap}
            .aa-pc-card-body .aa-inline-2 > div{flex:1;min-width:160px}

            /* Section color themes */
            .aa-pc-card.theme-fx .aa-pc-card-header{background:linear-gradient(135deg,#dbeafe,#eff6ff);border-color:#3b82f6;color:#1e40af}
            .aa-pc-card.theme-usd .aa-pc-card-header{background:linear-gradient(135deg,#fef3c7,#fffbeb);border-color:#f59e0b;color:#92400e}
            .aa-pc-card.theme-pln .aa-pc-card-header{background:linear-gradient(135deg,#dcfce7,#f0fdf4);border-color:#22c55e;color:#15803d}
            .aa-pc-card.theme-akcyza .aa-pc-card-header{background:linear-gradient(135deg,#f3e8ff,#faf5ff);border-color:#a855f7;color:#6b21a8}
            .aa-pc-card.theme-margin .aa-pc-card-header{background:linear-gradient(135deg,#fce7f3,#fdf2f8);border-color:#ec4899;color:#9d174d}
            .aa-pc-card.theme-vat .aa-pc-card-header{background:linear-gradient(135deg,#e0e7ff,#eef2ff);border-color:#6366f1;color:#3730a3}

            .aa-pc-card.aa-full{grid-column:1/-1}

            .aa-akcyza-table{width:100%;border-collapse:collapse;font-size:13px}
            .aa-akcyza-table thead th{padding:6px 4px;text-align:center;font-weight:600;color:#475569;font-size:11px;text-transform:uppercase;letter-spacing:.3px;background:#f8fafc;border-bottom:2px solid #e2e8f0}
            .aa-akcyza-table tbody td{padding:8px 4px;text-align:center;border-bottom:1px solid #f1f5f9}
            .aa-akcyza-table tbody td input{width:55px;padding:5px 6px;border:1px solid #cbd5e1;border-radius:3px;text-align:right;font-variant-numeric:tabular-nums}
            .aa-akcyza-table tbody td small{color:#94a3b8;font-size:10px;display:block;margin-top:2px}

            .aa-recalc-box{background:#fff;border:2px solid #f59e0b;border-radius:8px;padding:16px 20px;margin-top:20px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
            .aa-recalc-box .aa-recalc-info{flex:1;min-width:280px}
            .aa-recalc-box .aa-recalc-info h4{margin:0 0 6px;color:#92400e;font-size:14px}
            .aa-recalc-box .aa-recalc-info p{margin:0;color:#64748b;font-size:12px;line-height:1.5}
            .aa-recalc-box .aa-recalc-info p strong{color:#1e293b}
            .aa-recalc-box .aa-recalc-action{text-align:right}
            .aa-recalc-box .aa-recalc-action button{background:#f59e0b;color:#fff;border:0;padding:10px 18px;font-weight:600;border-radius:4px;cursor:pointer;font-size:14px}
            .aa-recalc-box .aa-recalc-action button:hover{background:#d97706}
            .aa-recalc-box .aa-recalc-action button:disabled{background:#94a3b8;cursor:not-allowed}
            #aa-recalc-result{margin-top:10px;padding:10px 14px;border-radius:4px;font-size:13px;display:none}
            #aa-recalc-result.is-success{background:#dcfce7;border-left:3px solid #22c55e;color:#166534}
            #aa-recalc-result.is-error{background:#fee2e2;border-left:3px solid #ef4444;color:#991b1b}

            .aa-submit-box{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px 20px;margin-top:20px;text-align:right}
            .aa-submit-box button{background:#3b82f6;color:#fff;border:0;padding:10px 24px;font-weight:600;border-radius:4px;cursor:pointer;font-size:14px}
            .aa-submit-box button:hover{background:#2563eb}

            /* v0.30.6 — kolumna CIF dwuwierszowa w preview */
            .aa-cif-cell{line-height:1.25}
            .aa-cif-cell .aa-cif-usd{display:block;font-weight:700;color:#1e293b}
            .aa-cif-cell .aa-cif-pln{display:block;color:#64748b;font-size:11px;margin-top:2px}
        </style>

        <div class="aa-pc-intro">
            <strong>Pipeline v0.30.6 — USD-centric.</strong>
            Kroki 1-2 (cena bazowa + Chiny/transport) są liczone w dolarach i konwertowane na PLN przez kurs <code>USD→PLN</code>.
            Kroki 3-9 (agencja, cło, akcyza, prowizja, VAT) są w PLN bez zmian.
            Listing może mieć cenę w dwóch trybach: <strong>CNY</strong> z auto-api (domyślnie dla importowanych) lub
            <strong>CIF USD</strong> wpisane ręcznie w edytorze listingu (override wygrywa nad CNY).
            <br>
            Zmiana parametrów nie przelicza automatycznie — użyj przycisku <strong>„Przelicz wszystkie listingi"</strong> na dole.
        </div>

        <form method="post" id="aa-price-form">
            <?php wp_nonce_field('asiaauto_save_config', 'asiaauto_config_nonce'); ?>
            <input type="hidden" name="_save_section" value="price">

            <div class="aa-pc-grid">

                <!-- 1. Kursy walut -->
                <div class="aa-pc-card theme-fx">
                    <div class="aa-pc-card-header">
                        <span class="aa-icon">💱</span>
                        <h3>Kursy walut</h3>
                    </div>
                    <div class="aa-pc-card-body">
                        <p class="aa-hint">
                            Oba kursy są aktualizowane ręcznie. <code>USD → CNY</code> dotyczy tylko listingów z auto-api
                            (pre-fill ceny bazowej USD), <code>USD → PLN</code> dotyczy <em>wszystkich</em> listingów
                            (konwersja CIF USD → PLN w kroku 1 i 2 pipeline'u).
                        </p>
                        <div class="aa-inline-2">
                            <div class="aa-field">
                                <label for="kurs_usd_cny">Kurs USD → CNY</label>
                                <?php
                                $stored_cny_usd = (float) ($pc['kurs_cny_usd'] ?? 0.15);
                                $display_usd_cny = $stored_cny_usd > 0 ? round(1 / $stored_cny_usd, 4) : 6.80;
                                ?>
                                <input type="text" id="kurs_usd_cny" name="kurs_usd_cny"
                                       value="<?php echo esc_attr((string) $display_usd_cny); ?>"
                                       placeholder="6.80">
                                <span class="aa-suffix">¥ / USD</span>
                            </div>
                            <div class="aa-field">
                                <label for="kurs_usd_pln">Kurs USD → PLN</label>
                                <input type="text" id="kurs_usd_pln" name="kurs_usd_pln"
                                       value="<?php echo esc_attr((string) ($pc['kurs_usd_pln'] ?? 3.70)); ?>"
                                       placeholder="3.70">
                                <span class="aa-suffix">PLN / $</span>
                            </div>
                        </div>
                        <p class="aa-hint" style="margin-top:10px;">
                            <strong>Efektywny CNY → PLN:</strong>
                            <?php
                            $eff = ((float) ($pc['kurs_cny_usd'] ?? 0)) * ((float) ($pc['kurs_usd_pln'] ?? 0));
                            echo esc_html(number_format($eff, 4, '.', ''));
                            ?>
                            (np. <?php echo esc_html(number_format($display_usd_cny > 0 ? $stored_cny_usd * ((float) ($pc['kurs_usd_pln'] ?? 0)) : 0, 4, '.', '')); ?> PLN / 1 ¥ — informacyjnie)
                        </p>
                    </div>
                </div>

                <!-- 2. Krok 1-2 USD: Chiny / transport -->
                <div class="aa-pc-card theme-usd">
                    <div class="aa-pc-card-header">
                        <span class="aa-icon">🇨🇳</span>
                        <h3>Chiny / rejestracja / transport (USD)</h3>
                    </div>
                    <div class="aa-pc-card-body">
                        <p class="aa-hint">
                            Stała kwota w dolarach dodawana w <strong>kroku 2</strong> pipeline'u CNY.
                            Obejmuje: rejestrację w Chinach, transport morski/kolejowy Chiny→UE, ubezpieczenie cargo.
                            Dla listingów w trybie CIF USD ręczny (override) ta kwota <em>nie jest dodawana</em> —
                            zakładamy że jest wliczona w CIF podany przez sprzedawcę.
                        </p>
                        <div class="aa-field">
                            <label for="chiny_rejestracja_transport_usd">Kwota stała</label>
                            <input type="number" id="chiny_rejestracja_transport_usd"
                                   name="chiny_rejestracja_transport_usd"
                                   value="<?php echo esc_attr((string) ($pc['chiny_rejestracja_transport_usd'] ?? 5000)); ?>"
                                   min="0" step="100" placeholder="5000">
                            <span class="aa-suffix">USD</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Krok 3 PLN: Agencja celna -->
                <div class="aa-pc-card theme-pln">
                    <div class="aa-pc-card-header">
                        <span class="aa-icon">📦</span>
                        <h3>Agencja celna / wyładunek (krok 3)</h3>
                    </div>
                    <div class="aa-pc-card-body">
                        <p class="aa-hint">
                            Stała kwota PLN dodawana <strong>do CIF</strong> przed naliczeniem cła.
                            Obejmuje: obsługę agencji celnej, wyładunek kontenera, opłaty portowe po stronie UE.
                        </p>
                        <div class="aa-field">
                            <label for="agencja_celna_wyladunek">Kwota stała</label>
                            <input type="number" id="agencja_celna_wyladunek" name="agencja_celna_wyladunek"
                                   value="<?php echo esc_attr((string) ($pc['agencja_celna_wyladunek'] ?? 2600)); ?>"
                                   min="0" step="100" placeholder="2600">
                            <span class="aa-suffix">PLN</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Krok 4 PLN: Cło + Krok 5: Transport -->
                <div class="aa-pc-card theme-pln">
                    <div class="aa-pc-card-header">
                        <span class="aa-icon">🛃</span>
                        <h3>Cło + transport z portu (kroki 4-5)</h3>
                    </div>
                    <div class="aa-pc-card-body">
                        <p class="aa-hint">
                            <strong>Cło</strong> naliczane od <code>(CIF PLN + agencja celna)</code>. Domyślna stawka UE na samochody z Chin: 10%.
                            <br>
                            <strong>Transport z portu</strong> to lawetą port UE → Rzeszów. Dodawany przed akcyzą.
                        </p>
                        <div class="aa-inline-2">
                            <div class="aa-field">
                                <label for="clo_procent">Cło</label>
                                <input type="text" id="clo_procent" name="clo_procent"
                                       value="<?php echo esc_attr((string) ($pc['clo_procent'] ?? 10)); ?>"
                                       placeholder="10">
                                <span class="aa-suffix">%</span>
                            </div>
                            <div class="aa-field">
                                <label for="transport_z_portu">Transport z portu UE</label>
                                <input type="number" id="transport_z_portu" name="transport_z_portu"
                                       value="<?php echo esc_attr((string) ($pc['transport_z_portu'] ?? 3400)); ?>"
                                       min="0" step="100" placeholder="3400">
                                <span class="aa-suffix">PLN</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Krok 6 PLN: Akcyza per fuel type (FULL WIDTH) -->
                <div class="aa-pc-card theme-akcyza aa-full">
                    <div class="aa-pc-card-header">
                        <span class="aa-icon">⛽</span>
                        <h3>Akcyza per rodzaj paliwa (krok 6)</h3>
                    </div>
                    <div class="aa-pc-card-body">
                        <p class="aa-hint">
                            Stawka akcyzy zależy od rodzaju paliwa. Podstawa naliczenia:
                            <code>CIF + agencja celna + cło + transport z portu</code>.
                            Domyślne wartości wg polskiego prawa (silniki do 2.0L).
                        </p>
                        <table class="aa-akcyza-table">
                            <thead>
                                <tr>
                                    <?php foreach ($fuel_labels as $slug => $label): ?>
                                        <th><?php echo esc_html($label); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <?php foreach ($fuel_labels as $slug => $label):
                                        $rate = $akcyza_stawki[$slug] ?? 0;
                                    ?>
                                        <td>
                                            <input type="text"
                                                   name="akcyza_stawki[<?php echo esc_attr($slug); ?>]"
                                                   value="<?php echo esc_attr((string) $rate); ?>"> <small>%</small>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <?php foreach ($fuel_labels as $slug => $label):
                                        $count = $fuel_counts[$slug] ?? 0;
                                    ?>
                                        <td><small><?php echo esc_html((string) $count); ?> ogł.</small></td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 6. Krok 7 PLN: Homologacja -->
                <div class="aa-pc-card theme-pln">
                    <div class="aa-pc-card-header">
                        <span class="aa-icon">📋</span>
                        <h3>Homologacja / detailing (krok 7)</h3>
                    </div>
                    <div class="aa-pc-card-body">
                        <p class="aa-hint">
                            Stała kwota PLN. Obejmuje: homologację jednostkową, detailing przedsprzedażowy,
                            rejestrację krajową, OC na pierwszy rok.
                        </p>
                        <div class="aa-field">
                            <label for="homologacja_detailing">Kwota stała</label>
                            <input type="number" id="homologacja_detailing" name="homologacja_detailing"
                                   value="<?php echo esc_attr((string) ($pc['homologacja_detailing'] ?? 1500)); ?>"
                                   min="0" step="100" placeholder="1500">
                            <span class="aa-suffix">PLN</span>
                        </div>
                    </div>
                </div>

                <!-- 7. Krok 8 PLN: Prowizja wewnętrzna -->
                <div class="aa-pc-card theme-margin">
                    <div class="aa-pc-card-header">
                        <span class="aa-icon">💰</span>
                        <h3>Prowizja wewnętrzna (krok 8)</h3>
                    </div>
                    <div class="aa-pc-card-body">
                        <p class="aa-hint">
                            <strong>Marża ukryta w cenie.</strong> Liczona jako <code>max(suma 1..7 × procent, minimalna kwota)</code>.
                            To jest <em>marża firmy</em> wliczona w cenę pod klucz — klient jej nie widzi.
                            Nie ma nic wspólnego z wynagrodzeniem z umowy (§3), które jest ustawiane osobno
                            w panelu Ustawień zamówień.
                        </p>
                        <div class="aa-inline-2">
                            <div class="aa-field">
                                <label for="prowizja_procent">Procent</label>
                                <input type="text" id="prowizja_procent" name="prowizja_procent"
                                       value="<?php echo esc_attr((string) ($pc['prowizja_procent'] ?? 10)); ?>"
                                       placeholder="10">
                                <span class="aa-suffix">%</span>
                            </div>
                            <div class="aa-field">
                                <label for="prowizja_min">Minimalna kwota</label>
                                <input type="number" id="prowizja_min" name="prowizja_min"
                                       value="<?php echo esc_attr((string) ($pc['prowizja_min'] ?? 11000)); ?>"
                                       min="0" step="500" placeholder="11000">
                                <span class="aa-suffix">PLN</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 8. Krok 9 PLN: VAT + zaokrąglenie -->
                <div class="aa-pc-card theme-vat aa-full">
                    <div class="aa-pc-card-header">
                        <span class="aa-icon">🧾</span>
                        <h3>VAT + zaokrąglenie (krok 9)</h3>
                    </div>
                    <div class="aa-pc-card-body">
                        <p class="aa-hint">
                            VAT naliczany od <strong>pełnej sumy 1..8</strong> — czyli od wszystkiego razem z marżą wewnętrzną.
                            Zaokrąglenie dotyczy tylko ceny końcowej wyświetlanej klientowi (w górę do wielokrotności).
                        </p>
                        <div class="aa-inline-2">
                            <div class="aa-field">
                                <label for="vat_procent">VAT</label>
                                <input type="text" id="vat_procent" name="vat_procent"
                                       value="<?php echo esc_attr((string) ($pc['vat_procent'] ?? 23)); ?>"
                                       placeholder="23">
                                <span class="aa-suffix">%</span>
                            </div>
                            <div class="aa-field">
                                <label for="zaokraglenie">Zaokrąglenie ceny końcowej</label>
                                <input type="number" id="zaokraglenie" name="zaokraglenie"
                                       value="<?php echo esc_attr((string) ($pc['zaokraglenie'] ?? 1000)); ?>"
                                       min="1" step="1" placeholder="1000">
                                <span class="aa-suffix">PLN (do wielokrotności)</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.aa-pc-grid -->

            <div class="aa-submit-box">
                <button type="submit">💾 Zapisz konfigurację</button>
            </div>
        </form>

        <!-- Bulk recalc box -->
        <div class="aa-recalc-box">
            <div class="aa-recalc-info">
                <h4>🔄 Przelicz wszystkie listingi</h4>
                <p>
                    W bazie: <strong><?php echo $cnt_cny; ?></strong> z ceną CNY (auto-api),
                    <strong><?php echo $cnt_cif_usd; ?></strong> z CIF USD (ręczne override),
                    <?php if ($cnt_cif_legacy > 0): ?>
                        <strong style="color:#dc2626"><?php echo $cnt_cif_legacy; ?></strong> legacy CIF PLN (do migracji).
                    <?php else: ?>
                        brak legacy CIF PLN.
                    <?php endif; ?>
                    Przelicz <em>po</em> zmianie parametrów albo kursów żeby uaktualnić ceny wyświetlane klientom.
                </p>
            </div>
            <div class="aa-recalc-action">
                <button type="button" id="aa-recalc-btn">Przelicz teraz</button>
                <div id="aa-recalc-result"></div>
            </div>
        </div>

        <?php
        // === Podgląd kalkulacji (ostatnie 10 listingów) ===
        $this->profileStart('renderPricePreview:total');
        $this->renderPricePreview($pc);
        $this->profileEnd('renderPricePreview:total');
        ?>

        <script>
        (function(){
            var btn = document.getElementById('aa-recalc-btn');
            var res = document.getElementById('aa-recalc-result');
            if (!btn) return;

            btn.addEventListener('click', function(){
                if (!confirm('Przeliczyć ceny wszystkich ogłoszeń wg bieżącej konfiguracji? Operacja może zająć kilka-kilkanaście sekund.')) return;

                btn.disabled = true;
                btn.textContent = 'Przeliczam…';
                res.style.display = 'none';
                res.className = '';

                var fd = new FormData();
                fd.append('action', 'asiaauto_bulk_recalc');
                fd.append('_nonce', <?php echo wp_json_encode($recalc_nonce); ?>);

                fetch(<?php echo wp_json_encode($ajax_url); ?>, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    btn.disabled = false;
                    btn.textContent = 'Przelicz teraz';
                    res.style.display = 'block';
                    if (data && data.success) {
                        res.className = 'is-success';
                        res.textContent = '✓ ' + data.data.msg;
                    } else {
                        res.className = 'is-error';
                        res.textContent = '✗ Błąd: ' + ((data && data.data && data.data.msg) || 'nieznany');
                    }
                })
                .catch(function(err){
                    btn.disabled = false;
                    btn.textContent = 'Przelicz teraz';
                    res.style.display = 'block';
                    res.className = 'is-error';
                    res.textContent = '✗ Błąd sieci: ' + err.message;
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Render price preview table — ostatnie 10 listingów + podstawowy breakdown v2.
     *
     * @since 0.30.6  Kolumna „CIF PLN" zastąpiona kolumną „CIF" dwuwierszową:
     *                USD na górze (bold), PLN pod spodem (małe, szare).
     *                Spójność z metaboksem ręcznych ogłoszeń, gdzie pole jest USD.
     */
    private function renderPricePreview(array $pc): void {
        global $wpdb;

        // Mieszanka — 8 z CNY + 2 z CIF USD (jeśli są)
        $this->profileStart('renderPricePreview:select_ids');
        $ids_cny = $wpdb->get_col("
            SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id
              AND pm.meta_key='_asiaauto_original_price' AND pm.meta_value > 0
            WHERE p.post_type='listings' AND p.post_status='publish'
            ORDER BY p.post_date DESC
            LIMIT 8
        ");
        $ids_cif = $wpdb->get_col("
            SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id
              AND pm.meta_key='_asiaauto_cif_price_usd' AND pm.meta_value > 0
            WHERE p.post_type='listings' AND p.post_status='publish'
            ORDER BY p.post_date DESC
            LIMIT 2
        ");
        $this->profileEnd('renderPricePreview:select_ids');

        $all_ids = array_map('intval', array_merge($ids_cny, $ids_cif));
        if (empty($all_ids)) {
            echo '<p class="description" style="margin-top:20px;">Brak opublikowanych ogłoszeń z ceną.</p>';
            return;
        }

        $this->profileStart('renderPricePreview:select_meta');
        $placeholder = implode(',', $all_ids);
        $rows = $wpdb->get_results("
            SELECT p.ID, p.post_title,
                   (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='_asiaauto_original_price' LIMIT 1) AS cny,
                   (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='_asiaauto_cif_price_usd' LIMIT 1) AS cif_usd,
                   (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='price' LIMIT 1) AS price_current
            FROM {$wpdb->posts} p
            WHERE p.ID IN ({$placeholder})
            ORDER BY p.post_date DESC
        ");
        $this->profileEnd('renderPricePreview:select_meta');

        $this->profileStart('renderPricePreview:fuel_map');
        $fuel_map = AsiaAuto_Price::batchGetFuelTypes($all_ids);
        $this->profileEnd('renderPricePreview:fuel_map');

        $fmt = static fn($v) => number_format((float) $v, 0, ',', ' ');
        ?>
        <h3 style="margin-top:30px;">Podgląd kalkulacji — ostatnie ogłoszenia</h3>
        <div style="overflow-x:auto;background:#fff;border-radius:8px;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(0,0,0,.04);">
        <table class="widefat" style="min-width:1600px;font-size:12px;margin:0;border:0;">
            <thead>
                <tr>
                    <th>Ogłoszenie</th>
                    <th>Tryb</th>
                    <th>Paliwo</th>
                    <th>Wejście</th>
                    <th>1. Baza USD</th>
                    <th>2. +Chiny USD</th>
                    <th>CIF</th>
                    <th>+Agencja</th>
                    <th>+Cło</th>
                    <th>+Transp.</th>
                    <th>+Akcyza</th>
                    <th>+Homolog.</th>
                    <th>+Prowizja</th>
                    <th>+VAT</th>
                    <th style="background:#dcfce7;"><strong>Cena końcowa</strong></th>
                    <th>W bazie</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $this->profileStart('renderPricePreview:loop_calc');
                foreach ($rows as $row):
                    $fuel = $fuel_map[(int) $row->ID] ?? '';
                    $cif_usd = (float) $row->cif_usd;
                    $cny = (float) $row->cny;

                    if ($cif_usd > 0) {
                        $b = AsiaAuto_Price::calculateFromCifUsd($cif_usd, $pc, $fuel);
                        $mode_label = '<span style="color:#ec4899;font-weight:600;">CIF USD</span>';
                        $input_label = $fmt($cif_usd) . ' $';
                    } else {
                        $b = AsiaAuto_Price::calculateFromCny($cny, $pc, $fuel);
                        $mode_label = '<span style="color:#f59e0b;font-weight:600;">CNY</span>';
                        $input_label = $fmt($cny) . ' ¥';
                    }

                    $current_price = (int) $row->price_current;
                    $new_price = (int) ($b['total']['cena_koncowa_pln'] ?? 0);
                    $price_differs = $current_price !== $new_price;
                    $edit_url = get_edit_post_link((int) $row->ID);
                    $prow_is_min = $b['step_8_prowizja_wewnetrzna']['amount_pln'] > $b['step_8_prowizja_wewnetrzna']['calculated_pln'];

                    // CIF dwuwierszowo: USD na górze (bold, normal size), PLN pod spodem (small, gray)
                    $cif_usd_display = (float) ($b['cif']['amount_usd'] ?? 0);
                    $cif_pln_display = (float) ($b['cif']['amount_pln'] ?? 0);
                ?>
                <tr>
                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <a href="<?php echo esc_url($edit_url); ?>" title="<?php echo esc_attr($row->post_title); ?>">
                            <?php echo esc_html($row->post_title); ?>
                        </a>
                    </td>
                    <td><?php echo $mode_label; ?></td>
                    <td style="color:#64748b;font-size:11px;"><?php echo esc_html($fuel ?: '—'); ?></td>
                    <td style="font-variant-numeric:tabular-nums;"><?php echo $input_label; ?></td>
                    <td style="font-variant-numeric:tabular-nums;"><?php echo $fmt($b['step_1_cena_bazowa']['amount_usd']); ?> $</td>
                    <td style="font-variant-numeric:tabular-nums;"><?php echo $fmt($b['step_2_chiny_transport']['amount_usd']); ?> $</td>
                    <td style="font-variant-numeric:tabular-nums;background:#f8fafc;">
                        <span class="aa-cif-cell">
                            <span class="aa-cif-usd"><?php echo $fmt($cif_usd_display); ?> $</span>
                            <span class="aa-cif-pln"><?php echo $fmt($cif_pln_display); ?> zł</span>
                        </span>
                    </td>
                    <td style="font-variant-numeric:tabular-nums;"><?php echo $fmt($b['step_3_agencja']['amount_pln']); ?></td>
                    <td style="font-variant-numeric:tabular-nums;"><?php echo $fmt($b['step_4_clo']['amount_pln']); ?></td>
                    <td style="font-variant-numeric:tabular-nums;"><?php echo $fmt($b['step_5_transport_portu']['amount_pln']); ?></td>
                    <td style="font-variant-numeric:tabular-nums;<?php echo $b['step_6_akcyza']['percent'] > 0 ? '' : 'color:#94a3b8;'; ?>">
                        <?php echo $fmt($b['step_6_akcyza']['amount_pln']); ?>
                        <small>(<?php echo esc_html((string) $b['step_6_akcyza']['percent']); ?>%)</small>
                    </td>
                    <td style="font-variant-numeric:tabular-nums;"><?php echo $fmt($b['step_7_homologacja']['amount_pln']); ?></td>
                    <td style="font-variant-numeric:tabular-nums;<?php echo $prow_is_min ? 'color:#dc2626;' : ''; ?>">
                        <?php echo $fmt($b['step_8_prowizja_wewnetrzna']['amount_pln']); ?>
                        <?php if ($prow_is_min): ?><small>(min)</small><?php endif; ?>
                    </td>
                    <td style="font-variant-numeric:tabular-nums;"><?php echo $fmt($b['step_9_vat']['amount_pln']); ?></td>
                    <td style="font-variant-numeric:tabular-nums;background:#dcfce7;">
                        <strong><?php echo $fmt($new_price); ?> zł</strong>
                    </td>
                    <td style="font-variant-numeric:tabular-nums;<?php echo $price_differs ? 'color:#dc2626;' : 'color:#16a34a;'; ?>"
                        title="<?php echo $price_differs ? 'Cena w bazie różni się od bieżącej kalkulacji' : ''; ?>">
                        <?php echo $fmt($current_price); ?> zł
                        <?php echo $price_differs ? ' ⚠' : ' ✓'; ?>
                    </td>
                </tr>
                <?php
                endforeach;
                $this->profileEnd('renderPricePreview:loop_calc');
                ?>
            </tbody>
        </table>
        </div>
        <p class="description" style="margin-top:8px;">
            <span style="color:#dc2626;">(min)</span> = zastosowano minimalną prowizję.
            <span style="color:#dc2626;">⚠</span> = cena w bazie różni się od bieżącej kalkulacji — kliknij „Przelicz teraz".
            <span style="color:#16a34a;">✓</span> = cena aktualna.
        </p>
        <?php
    }

    // =========================================================================
    // TAB: FILTRY IMPORTU (bez zmian vs v0.29)
    // =========================================================================

    private function renderFiltersTab(array $sc, string $source): void {
        $dict_models = [];   // canonMark => lista modeli (tylko che168)
        $mark_names  = [];   // nazwy marek do checkboxów
        $mark_counts = [];   // markName => label licznika
        if ($source === 'che168') {
            $dict_models = AsiaAuto_Che168_Dictionary::canonicalized();
            $mark_names  = array_keys($dict_models);
            foreach ($dict_models as $dm => $dmodels) {
                $mark_counts[$dm] = count($dmodels) . ' modeli';
            }
        } else {
            $terms = get_terms([
                'taxonomy'   => 'make',
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);
            foreach ((is_wp_error($terms) ? [] : $terms) as $term) {
                $mark_names[]             = $term->name;
                $mark_counts[$term->name] = (string) $term->count;
            }
        }

        $selected_marks = $sc['marks'] ?? [];
        $enabled        = !empty($sc['enabled']);
        $year_from      = $sc['year_from'] ?? '';
        $year_to        = $sc['year_to'] ?? '';
        $km_from        = $sc['km_from'] ?? '';
        $km_to          = $sc['km_to'] ?? '';
        $price_from     = $sc['price_from'] ?? '';
        $price_to       = $sc['price_to'] ?? '';
        ?>
        <form method="post">
            <?php wp_nonce_field('asiaauto_save_config', 'asiaauto_config_nonce'); ?>
            <input type="hidden" name="_save_section" value="filters">
            <input type="hidden" name="source" value="<?php echo esc_attr($source); ?>">

            <h2>Filtrowanie importu (źródło: <?php echo esc_html($source); ?>)</h2>
            <table class="form-table">
                <tr>
                    <th>Filtrowanie aktywne</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enabled" value="1" <?php checked($enabled); ?>>
                            Włącz filtrowanie importu (gdy wyłączone — importuje wszystko)
                        </label>
                    </td>
                </tr>

                <tr>
                    <th>Rocznik</th>
                    <td>
                        <input type="number" name="year_from" value="<?php echo esc_attr((string) $year_from); ?>"
                               min="2000" max="2030" style="width:100px;" placeholder="od">
                        —
                        <input type="number" name="year_to" value="<?php echo esc_attr((string) $year_to); ?>"
                               min="2000" max="2030" style="width:100px;" placeholder="do">
                    </td>
                </tr>

                <tr>
                    <th>Przebieg (km)</th>
                    <td>
                        <input type="number" name="km_from" value="<?php echo esc_attr((string) $km_from); ?>"
                               min="0" step="1000" style="width:120px;" placeholder="od">
                        —
                        <input type="number" name="km_to" value="<?php echo esc_attr((string) $km_to); ?>"
                               min="0" step="1000" style="width:120px;" placeholder="do">
                    </td>
                </tr>

                <tr>
                    <th>Cena (CNY)</th>
                    <td>
                        <input type="number" name="price_from" value="<?php echo esc_attr((string) $price_from); ?>"
                               min="0" step="1000" style="width:120px;" placeholder="od">
                        —
                        <input type="number" name="price_to" value="<?php echo esc_attr((string) $price_to); ?>"
                               min="0" step="1000" style="width:120px;" placeholder="do">
                        <p class="description">Cena w juanach (CNY). Puste = bez limitu.</p>
                    </td>
                </tr>

                <tr>
                    <th>Marki (<?php echo count($mark_names); ?> dostępnych)</th>
                    <td>
                        <div style="margin-bottom:10px;">
                            <button type="button" class="button" onclick="asiaAutoSelectAll(true)">Zaznacz wszystkie</button>
                            <button type="button" class="button" onclick="asiaAutoSelectAll(false)">Odznacz wszystkie</button>
                            <input type="text" id="aa-mark-filter" placeholder="Filtruj marki..."
                                   style="margin-left:10px;width:200px;">
                        </div>
                        <div id="aa-marks-container" style="max-height:400px;overflow-y:auto;border:1px solid #ddd;padding:10px;column-count:3;column-gap:20px;">
                            <?php foreach ($mark_names as $mark_name): ?>
                                <label class="aa-mark-label" style="display:block;margin-bottom:3px;break-inside:avoid;">
                                    <input type="checkbox" name="marks[]"
                                           value="<?php echo esc_attr($mark_name); ?>"
                                           <?php checked(in_array($mark_name, $selected_marks, true)); ?>>
                                    <?php echo esc_html($mark_name); ?>
                                    <small style="color:#999;">(<?php echo esc_html($mark_counts[$mark_name] ?? ''); ?>)</small>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description">
                            Zaznaczono: <strong id="aa-selected-count"><?php echo count($selected_marks); ?></strong> marek.
                            Puste = importuj wszystkie marki.
                        </p>
                    </td>
                </tr>

                <?php if ($source === 'che168') :
                    $model_blacklist = $sc['model_blacklist'] ?? [];
                    $dict_fetched    = AsiaAuto_Che168_Dictionary::fetchedAt();
                ?>
                <tr>
                    <th>Modele — wykluczenia<br><small style="font-weight:normal;color:#666;">(blacklista per marka)</small></th>
                    <td>
                        <input type="hidden" name="model_blacklist" id="aa-model-blacklist-input"
                               value="<?php echo esc_attr(wp_json_encode($model_blacklist) ?: '{}'); ?>">
                        <p class="description" style="margin-bottom:8px;">
                            Wszystkie modele zaznaczonej marki wchodzą; zaznacz <strong>niesprzedajne</strong>, żeby je wykluczyć.
                            Nowe modele giełdy przechodzą automatycznie. Ręczny import per numer <strong>omija</strong> blacklistę.
                            🆕 = model bez huba (orphan).
                            Słownik z API: <?php echo $dict_fetched ? esc_html(mysql2date('Y-m-d H:i', $dict_fetched)) : 'brak'; ?>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=asiaauto_che168_dict_refresh'), 'asiaauto_che168_dict')); ?>"
                               class="button button-small" style="margin-left:6px;">Odśwież słownik</a>
                        </p>
                        <div id="aa-models-container" style="max-height:500px;overflow-y:auto;border:1px solid #ddd;padding:10px;">
                            <?php if ($dict_models === []) : ?>
                                <em>Brak słownika (API niedostępne?). Zapisane wykluczenia pozostają aktywne.</em>
                            <?php endif; ?>
                            <?php foreach ($dict_models as $dm => $dmodels) : ?>
                                <details class="aa-models-group" data-mark="<?php echo esc_attr($dm); ?>"
                                         style="margin-bottom:6px;<?php echo in_array($dm, $selected_marks, true) ? '' : 'display:none;'; ?>">
                                    <summary style="cursor:pointer;font-weight:600;">
                                        <?php echo esc_html($dm); ?>
                                        <small style="color:#999;">(<?php echo count($dmodels); ?> modeli,
                                            <span class="aa-bl-count" data-mark="<?php echo esc_attr($dm); ?>">0</span> wykluczonych)</small>
                                    </summary>
                                    <div style="padding:6px 0 4px 18px;column-count:3;column-gap:20px;">
                                        <?php foreach ($dmodels as $m) : ?>
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
            </table>

                <?php
            $city_filter_enabled = !empty($sc['city_filter_enabled']);
            $city_filter_cities  = $sc['city_filter_cities'] ?? [];
            $city_count          = count($city_filter_cities);
            $city_count_label    = $city_count > 0 ? $city_count . ' miast' : '31 miast (domyślnie wszystkie)';
            ?>
            <tr>
                <th>Filtr miast</th>
                <td>
                    <label style="margin-bottom:10px;display:block;">
                        <input type="checkbox" name="city_filter_enabled" value="1" <?php checked($city_filter_enabled); ?>>
                        Filtruj po miastach (gdy wyłączone — importuje z całych Chin)
                    </label>
                    <input type="hidden" name="city_filter_cities" id="aa-city-filter-input"
                           value="<?php echo esc_attr(wp_json_encode($city_filter_cities) ?: '[]'); ?>">
                    <button type="button" class="button" id="aa-city-open-btn">
                        Konfiguruj miasta — <span id="aa-city-count-badge"><?php echo esc_html($city_count_label); ?></span>
                    </button>
                    <p class="description">Domyślnie: 31 miast z Guangdong, Fujian, Guangxi i Hainan — wszystkie zaznaczone.</p>
                </td>
            </tr>

        <?php submit_button('Zapisz filtry importu'); ?>
        </form>

        <div id="aa-city-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:100000;align-items:center;justify-content:center;">
            <div style="background:#fff;width:720px;max-width:95vw;max-height:90vh;overflow-y:auto;border-radius:4px;box-shadow:0 6px 30px rgba(0,0,0,.35);padding:24px 28px;">
                <h2 style="margin:0 0 6px;">Filtr miast — konfiguracja</h2>
                <p style="color:#666;margin:0 0 20px;font-size:13px;">Zaznaczone miasta są <strong>dozwolone</strong> do importu gdy filtr jest aktywny.</p>
                <div id="aa-city-provinces"></div>
                <div style="border-top:1px solid #eee;margin-top:16px;padding-top:16px;">
                    <strong style="font-size:13px;">Dodaj miasto</strong>
                    <div style="display:flex;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap;">
                        <input type="text" id="aa-new-zh" placeholder="Chiński (np. 广州)" style="width:150px;" maxlength="10">
                        <input type="text" id="aa-new-pl" placeholder="Polski (np. Guangzhou)" style="width:160px;" maxlength="50">
                        <select id="aa-new-prov" style="width:130px;">
                            <option>Guangdong</option>
                            <option>Fujian</option>
                            <option>Guangxi</option>
                            <option>Hainan</option>
                            <option value="Inne">Inne</option>
                        </select>
                        <button type="button" class="button" id="aa-add-city-btn">Dodaj</button>
                    </div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #eee;margin-top:20px;padding-top:16px;">
                    <span style="font-size:13px;color:#666;">Zaznaczono: <strong id="aa-total-count">0</strong> miast</span>
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="button" id="aa-modal-cancel">Anuluj</button>
                        <button type="button" class="button button-primary" id="aa-modal-save">Zapisz</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function asiaAutoSelectAll(check) {
            document.querySelectorAll('#aa-marks-container input[type=checkbox]').forEach(function(cb) {
                if (cb.closest('.aa-mark-label').style.display !== 'none') {
                    cb.checked = check;
                }
            });
            asiaAutoUpdateCount();
        }
        function asiaAutoUpdateCount() {
            var count = document.querySelectorAll('#aa-marks-container input[type=checkbox]:checked').length;
            document.getElementById('aa-selected-count').textContent = count;
        }
        document.getElementById('aa-mark-filter').addEventListener('input', function() {
            var q = this.value.toLowerCase();
            document.querySelectorAll('.aa-mark-label').forEach(function(label) {
                label.style.display = label.textContent.toLowerCase().includes(q) ? 'block' : 'none';
            });
        });
        document.querySelectorAll('#aa-marks-container input[type=checkbox]').forEach(function(cb) {
            cb.addEventListener('change', asiaAutoUpdateCount);
        });
        </script>

        <?php if ($source === 'che168') : ?>
        <script>
        (function() {
            var input = document.getElementById('aa-model-blacklist-input');
            if (!input) { return; }
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

        <script>
        (function() {
            var DEFAULTS = <?php echo json_encode(self::defaultCities(), JSON_UNESCAPED_UNICODE); ?>;
            var overlay   = document.getElementById('aa-city-overlay');
            var input     = document.getElementById('aa-city-filter-input');

            function getSaved() {
                try { return JSON.parse(input.value || '[]'); } catch(e) { return []; }
            }

            function esc(s) {
                return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            }

            function renderModal() {
                var saved      = getSaved();
                var savedZh    = saved.map(function(c) { return c.zh; });
                var firstTime  = saved.length === 0;

                var allDefaultZh = [];
                Object.keys(DEFAULTS).forEach(function(p) {
                    DEFAULTS[p].forEach(function(c) { allDefaultZh.push(c.zh); });
                });

                var html = '';
                Object.keys(DEFAULTS).forEach(function(prov) {
                    var cities = DEFAULTS[prov];
                    html += '<div style="margin-bottom:18px;">';
                    html += '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">';
                    html += '<strong style="font-size:13px;">' + esc(prov) + '</strong>';
                    html += '<button type="button" class="button-link aa-sel-prov" data-prov="' + esc(prov) + '" data-val="1" style="font-size:11px;color:#0073aa;">wszystkie</button>';
                    html += '<button type="button" class="button-link aa-sel-prov" data-prov="' + esc(prov) + '" data-val="0" style="font-size:11px;color:#999;">żadne</button>';
                    html += '</div>';
                    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:3px 20px;">';
                    cities.forEach(function(c) {
                        var chk = (firstTime || savedZh.indexOf(c.zh) !== -1) ? ' checked' : '';
                        html += '<label style="display:flex;align-items:center;gap:5px;padding:2px 0;cursor:pointer;">';
                        html += '<input type="checkbox" class="aa-city-cb" data-zh="' + esc(c.zh) + '" data-pl="' + esc(c.pl) + '" data-prov="' + esc(prov) + '"' + chk + '>';
                        html += esc(c.pl) + ' <small style="color:#aaa;font-size:11px;">' + esc(c.zh) + '</small>';
                        html += '</label>';
                    });
                    html += '</div></div>';
                });

                // Custom cities saved but not in defaults
                var custom = saved.filter(function(c) { return allDefaultZh.indexOf(c.zh) === -1; });
                var customDisplay = custom.length ? '' : 'none';
                html += '<div id="aa-custom-wrapper" style="display:' + customDisplay + ';margin-bottom:18px;">';
                html += '<strong style="font-size:13px;display:block;margin-bottom:8px;">Niestandardowe</strong>';
                html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:3px 20px;" id="aa-custom-cities">';
                custom.forEach(function(c) {
                    html += '<label style="display:flex;align-items:center;gap:5px;padding:2px 0;cursor:pointer;">';
                    html += '<input type="checkbox" class="aa-city-cb" data-zh="' + esc(c.zh) + '" data-pl="' + esc(c.pl) + '" data-prov="' + esc(c.province || 'Inne') + '" checked>';
                    html += esc(c.pl) + ' <small style="color:#aaa;font-size:11px;">' + esc(c.zh) + '</small>';
                    html += '</label>';
                });
                html += '</div></div>';

                document.getElementById('aa-city-provinces').innerHTML = html;

                document.querySelectorAll('.aa-sel-prov').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var prov = this.dataset.prov;
                        var val  = this.dataset.val === '1';
                        document.querySelectorAll('.aa-city-cb[data-prov="' + prov + '"]').forEach(function(cb) { cb.checked = val; });
                        updateCount();
                    });
                });

                document.querySelectorAll('.aa-city-cb').forEach(function(cb) {
                    cb.addEventListener('change', updateCount);
                });

                updateCount();
            }

            function updateCount() {
                var n = document.querySelectorAll('.aa-city-cb:checked').length;
                document.getElementById('aa-total-count').textContent = n;
            }

            document.getElementById('aa-city-open-btn').addEventListener('click', function() {
                renderModal();
                overlay.style.display = 'flex';
            });

            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) overlay.style.display = 'none';
            });

            document.getElementById('aa-modal-cancel').addEventListener('click', function() {
                overlay.style.display = 'none';
            });

            document.getElementById('aa-modal-save').addEventListener('click', function() {
                var cities = [];
                document.querySelectorAll('.aa-city-cb:checked').forEach(function(cb) {
                    cities.push({zh: cb.dataset.zh, pl: cb.dataset.pl, province: cb.dataset.prov});
                });
                input.value = JSON.stringify(cities);
                overlay.style.display = 'none';
                var badge = document.getElementById('aa-city-count-badge');
                if (badge) badge.textContent = cities.length + ' miast';
            });

            document.getElementById('aa-add-city-btn').addEventListener('click', function() {
                var zh   = document.getElementById('aa-new-zh').value.trim();
                var pl   = document.getElementById('aa-new-pl').value.trim();
                var prov = document.getElementById('aa-new-prov').value;
                if (!zh || !pl) { alert('Podaj nazwę chińską i polską.'); return; }
                if (document.querySelector('.aa-city-cb[data-zh="' + zh + '"]')) {
                    alert('Miasto ' + zh + ' już jest na liście.'); return;
                }
                var container = document.getElementById('aa-custom-cities');
                var label     = document.createElement('label');
                label.style.cssText = 'display:flex;align-items:center;gap:5px;padding:2px 0;cursor:pointer;';
                label.innerHTML = '<input type="checkbox" class="aa-city-cb" data-zh="' + esc(zh) + '" data-pl="' + esc(pl) + '" data-prov="' + esc(prov) + '" checked> '
                    + esc(pl) + ' <small style="color:#aaa;font-size:11px;">' + esc(zh) + '</small>';
                label.querySelector('input').addEventListener('change', updateCount);
                container.appendChild(label);
                document.getElementById('aa-custom-wrapper').style.display = '';
                document.getElementById('aa-new-zh').value = '';
                document.getElementById('aa-new-pl').value = '';
                updateCount();
            });

            // Init badge + auto-fill input with defaults if empty (prevents UX bug:
            // without this, saving the form without opening the modal stored []).
            var saved0 = getSaved();
            if (saved0.length === 0) {
                var defaults = [];
                Object.keys(DEFAULTS).forEach(function(prov) {
                    DEFAULTS[prov].forEach(function(c) {
                        defaults.push({zh: c.zh, pl: c.pl, province: prov});
                    });
                });
                input.value = JSON.stringify(defaults);
                saved0 = defaults;
            }
            var badge  = document.getElementById('aa-city-count-badge');
            if (badge) badge.textContent = saved0.length + ' miast';
        })();
        </script>
        <?php
    }

    private static function defaultCities(): array {
        return [
            'Guangdong' => [
                ['zh' => '深圳', 'pl' => 'Shenzhen'],
                ['zh' => '广州', 'pl' => 'Guangzhou'],
                ['zh' => '东莞', 'pl' => 'Dongguan'],
                ['zh' => '佛山', 'pl' => 'Foshan'],
                ['zh' => '惠州', 'pl' => 'Huizhou'],
                ['zh' => '江门', 'pl' => 'Jiangmen'],
                ['zh' => '揭阳', 'pl' => 'Jieyang'],
                ['zh' => '中山', 'pl' => 'Zhongshan'],
                ['zh' => '茂名', 'pl' => 'Maoming'],
                ['zh' => '汕头', 'pl' => 'Shantou'],
                ['zh' => '潮州', 'pl' => 'Chaozhou'],
                ['zh' => '梅州', 'pl' => 'Meizhou'],
                ['zh' => '珠海', 'pl' => 'Zhuhai'],
                ['zh' => '肇庆', 'pl' => 'Zhaoqing'],
                ['zh' => '韶关', 'pl' => 'Shaoguan'],
            ],
            'Fujian' => [
                ['zh' => '泉州', 'pl' => 'Quanzhou'],
                ['zh' => '南平', 'pl' => 'Nanping'],
                ['zh' => '厦门', 'pl' => 'Xiamen'],
                ['zh' => '福州', 'pl' => 'Fuzhou'],
                ['zh' => '漳州', 'pl' => 'Zhangzhou'],
                ['zh' => '宁德', 'pl' => 'Ningde'],
            ],
            'Guangxi' => [
                ['zh' => '南宁', 'pl' => 'Nanning'],
                ['zh' => '柳州', 'pl' => 'Liuzhou'],
                ['zh' => '玉林', 'pl' => 'Yulin'],
                ['zh' => '防城港', 'pl' => 'Fangchenggang'],
                ['zh' => '桂林', 'pl' => 'Guilin'],
                ['zh' => '钦州', 'pl' => 'Qinzhou'],
                ['zh' => '贵港', 'pl' => 'Guigang'],
                ['zh' => '北海', 'pl' => 'Beihai'],
            ],
            'Hainan' => [
                ['zh' => '海口', 'pl' => 'Haikou'],
                ['zh' => '三亚', 'pl' => 'Sanya'],
            ],
        ];
    }
}
