<?php
/**
 * Admin page: Manual import of individual offers from Dongchedi.
 *
 * Adds "Dodaj z Dongchedi" submenu under Listings CPT.
 * Flow: paste URL/inner_id → AJAX preview (filter analysis + price breakdown + images)
 *       → click "Zaimportuj" → AJAX import with config bypass.
 *
 * @since 0.15.0
 * @since 0.30.5  JS preview breakdownu przepisany pod pipeline v2 (step_1 USD,
 *                step_2 USD, cif, pełne kroki 3-9 z akcyzą i homologacją).
 */

defined('ABSPATH') || exit;

class AsiaAuto_Admin_Manual_Import {

    private string $page_slug = 'asiaauto-manual-import';

    public function __construct() {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_ajax_asiaauto_preview_offer', [$this, 'ajaxPreview']);
        add_action('wp_ajax_asiaauto_manual_import', [$this, 'ajaxImport']);
    }

    public function addMenuPage(): void {
        add_submenu_page(
            'edit.php?post_type=listings',
            'Dodaj z Dongchedi',
            'Dodaj z Dongchedi',
            AsiaAuto_Security::IMPORT_CAP,
            $this->page_slug,
            [$this, 'renderPage']
        );
    }

    public function enqueueScripts(string $hook): void {
        if ($hook !== 'listings_page_' . $this->page_slug) {
            return;
        }
        wp_enqueue_script('wp-util');
    }

    // =========================================================================
    // PAGE RENDER
    // =========================================================================

    public function renderPage(): void {
        ?>
        <div class="wrap">
            <h1>Dodaj ofertę z Dongchedi</h1>
            <p class="description">
                Wklej link z dongchedi.com lub sam numer inner_id.
                System pobierze dane z API, pokaże podgląd i umożliwi ręczny import
                (z pominięciem filtrów konfiguracji).
            </p>

            <div id="aa-mi-form" style="margin:20px 0;padding:20px;background:#fff;border:1px solid #ccd0d4;">
                <label for="aa-mi-input" style="font-weight:600;">URL lub Inner ID:</label><br>
                <input type="text" id="aa-mi-input"
                       placeholder="https://www.dongchedi.com/usedcar/23133224 lub 23133224"
                       style="width:500px;max-width:100%;margin:8px 0;">
                <button type="button" id="aa-mi-fetch" class="button button-primary">Pobierz podgląd</button>
                <span id="aa-mi-spinner" class="spinner" style="float:none;margin-top:4px;"></span>
                <div id="aa-mi-error" style="color:#d63638;margin-top:8px;display:none;"></div>
            </div>

            <div id="aa-mi-preview" style="display:none;"></div>
        </div>

        <style>
            .aa-filter-pass { color: #38a169; font-weight: 600; }
            .aa-filter-fail { color: #d63638; font-weight: 600; }
            .aa-mi-section { background:#fff; border:1px solid #ccd0d4; padding:15px 20px; margin-bottom:15px; }
            .aa-mi-section h3 { margin-top:0; }
            .aa-mi-images { display:flex; flex-wrap:wrap; gap:8px; }
            .aa-mi-images img { width:160px; height:110px; object-fit:cover; border-radius:4px; border:1px solid #ddd; }
            .aa-mi-table { width:100%; border-collapse:collapse; }
            .aa-mi-table th, .aa-mi-table td { padding:6px 10px; text-align:left; border-bottom:1px solid #eee; }
            .aa-mi-table th { width:220px; color:#50575e; font-weight:500; }
            .aa-mi-table td { font-variant-numeric: tabular-nums; }
            .aa-mi-table tr.aa-highlight { background:#f0faf0; }
            .aa-mi-table tr.aa-warn { background:#fef7f1; }
            .aa-mi-table tr.aa-cif { background:#eff6ff; font-weight:700; }
            .aa-mi-table tr.aa-cif th, .aa-mi-table tr.aa-cif td { color:#1e40af; }
            .aa-mi-table tr.aa-final th, .aa-mi-table tr.aa-final td { background:#f0f0f1; font-weight:700; font-size:14px; }
            .aa-mi-table tr.aa-step-usd th { padding-left:20px; color:#92400e; }
            .aa-mi-table tr.aa-step-usd td { color:#92400e; }
            .aa-mi-table tr.aa-sub th { padding-left:30px; color:#94a3b8; font-size:12px; font-style:italic; }
            .aa-mi-table tr.aa-sub td { color:#94a3b8; font-size:12px; font-style:italic; }
            #aa-mi-import-result { margin-top:15px; padding:12px 15px; display:none; }
        </style>

        <script>
        (function() {
            const input    = document.getElementById('aa-mi-input');
            const fetchBtn = document.getElementById('aa-mi-fetch');
            const spinner  = document.getElementById('aa-mi-spinner');
            const errorEl  = document.getElementById('aa-mi-error');
            const preview  = document.getElementById('aa-mi-preview');

            fetchBtn.addEventListener('click', doFetch);
            input.addEventListener('keydown', function(e) { if (e.key === 'Enter') doFetch(); });

            function doFetch() {
                const raw = input.value.trim();
                if (!raw) { showError('Wpisz URL lub inner_id.'); return; }

                showError('');
                preview.style.display = 'none';
                spinner.classList.add('is-active');
                fetchBtn.disabled = true;

                wp.ajax.post('asiaauto_preview_offer', {
                    input: raw,
                    _nonce: '<?php echo wp_create_nonce('asiaauto_manual_import'); ?>'
                }).done(function(data) {
                    renderPreview(data);
                    preview.style.display = 'block';
                }).fail(function(err) {
                    showError(err || 'Błąd pobierania danych z API.');
                }).always(function() {
                    spinner.classList.remove('is-active');
                    fetchBtn.disabled = false;
                });
            }

            function showError(msg) {
                errorEl.textContent = msg;
                errorEl.style.display = msg ? 'block' : 'none';
            }

            function renderPreview(d) {
                const api = d.api_data;
                const fa  = d.filter_analysis;
                const pb  = d.price_breakdown;
                const tr  = d.translations;
                const imgs = d.images || [];

                let html = '';

                // === Existing in WP? ===
                if (d.exists_in_wp) {
                    html += '<div class="notice notice-warning" style="margin:0 0 15px;padding:10px 15px;">'
                         + '<strong>Ta oferta już istnieje w WordPress</strong> — post #' + d.wp_post_id
                         + ' (<a href="' + d.wp_edit_url + '" target="_blank">edytuj</a>)'
                         + '</div>';
                }

                // === Vehicle info ===
                html += '<div class="aa-mi-section"><h3>Dane pojazdu</h3>';
                html += '<table class="aa-mi-table">';
                html += trRow('Inner ID', api.inner_id);
                html += trRow('URL', '<a href="' + esc(api.url) + '" target="_blank">' + esc(api.url) + '</a>');
                html += trFilter('Marka', api.mark, fa.mark);
                html += trRow('Model', api.model);
                html += trRow('Konfiguracja', api.complectation || '—');
                html += trFilter('Rocznik', api.year, fa.year);
                html += trFilter('Przebieg', number(api.km_age) + ' km', fa.km_age);
                html += trFilter('Cena CNY', number(api.price) + ' ¥', fa.price);
                html += trRow('Nadwozie', (tr.body_type || api.body_type || '—'));
                html += trRow('Silnik', (tr.engine_type || api.engine_type || '—'));
                html += trRow('Skrzynia', (tr.transmission_type || api.transmission_type || '—'));
                html += trRow('Napęd', (tr.drive_type || api.drive_type || '—'));
                html += trRow('Moc', (api.horse_power || '—') + ' KM');
                html += trRow('Kolor', (tr.color || api.color || '—'));
                html += trRow('Właściciele', api.owners_count || '—');
                html += trRow('Rej. data', api.reg_date || '—');
                html += trRow('Miasto', api.city || '—');
                html += trRow('Sprzedawca', api.seller || '—');
                html += '</table>';

                if (d.all_filters_pass) {
                    html += '<p class="aa-filter-pass" style="margin-top:10px;">✅ Wszystkie filtry importu spełnione — ta oferta weszłaby automatycznie.</p>';
                } else {
                    html += '<p class="aa-filter-fail" style="margin-top:10px;">❌ Nie spełnia filtrów importu (oznaczone na czerwono). Ręczny import pominie filtry.</p>';
                }
                html += '</div>';

                // === Price breakdown v2 ===
                if (pb && pb.przeliczone) {
                    html += '<div class="aa-mi-section"><h3>Kalkulacja ceny (pipeline v' + (pb.version || 2) + ')</h3>';
                    html += '<table class="aa-mi-table">';

                    // FX snapshot
                    if (pb.fx) {
                        html += trRow('Kurs CNY → USD', num4(pb.fx.kurs_cny_usd));
                        html += trRow('Kurs USD → PLN', num4(pb.fx.kurs_usd_pln));
                    }

                    // Source price
                    if (pb.source_price) {
                        html += trRow('Cena wejściowa', number(pb.source_price.amount) + ' ' + (pb.source_price.currency === 'CNY' ? '¥' : (pb.source_price.currency === 'USD' ? '$' : 'zł')));
                    }

                    // Step 1 — cena bazowa USD
                    if (pb.step_1_cena_bazowa) {
                        html += trStep('1. Cena bazowa (USD)', number(pb.step_1_cena_bazowa.amount_usd) + ' $');
                        html += trSub('→ w PLN', number(pb.step_1_cena_bazowa.amount_pln) + ' zł');
                    }

                    // Step 2 — Chiny/transport USD
                    if (pb.step_2_chiny_transport) {
                        if (pb.step_2_chiny_transport.amount_usd > 0) {
                            html += trStep('2. + Chiny/transport (USD)', number(pb.step_2_chiny_transport.amount_usd) + ' $');
                            html += trSub('→ w PLN', number(pb.step_2_chiny_transport.amount_pln) + ' zł');
                        } else {
                            html += trRow('2. Chiny/transport', '<em>wliczone w CIF (0)</em>');
                        }
                    }

                    // CIF (highlighted)
                    if (pb.cif) {
                        html += trCif('CIF (USD / PLN)', number(pb.cif.amount_usd) + ' $ / ' + number(pb.cif.amount_pln) + ' zł');
                    }

                    // Steps 3-9 PLN
                    if (pb.step_3_agencja) {
                        html += trRow('3. + Agencja celna / wyładunek', number(pb.step_3_agencja.amount_pln) + ' zł');
                    }
                    if (pb.step_4_clo) {
                        html += trRow('4. + Cło ' + pb.step_4_clo.percent + '%', number(pb.step_4_clo.amount_pln) + ' zł');
                    }
                    if (pb.step_5_transport_portu) {
                        html += trRow('5. + Transport z portu', number(pb.step_5_transport_portu.amount_pln) + ' zł');
                    }
                    if (pb.step_6_akcyza) {
                        const fuelLbl = pb.step_6_akcyza.fuel_type ? ' [' + pb.step_6_akcyza.fuel_type + ']' : '';
                        html += trRow('6. + Akcyza ' + pb.step_6_akcyza.percent + '%' + fuelLbl, number(pb.step_6_akcyza.amount_pln) + ' zł');
                    }
                    if (pb.step_7_homologacja) {
                        html += trRow('7. + Homologacja / detailing', number(pb.step_7_homologacja.amount_pln) + ' zł');
                    }
                    if (pb.step_8_prowizja_wewnetrzna) {
                        const s8 = pb.step_8_prowizja_wewnetrzna;
                        let prowNote = '';
                        if (s8.amount_pln > s8.calculated_pln) {
                            prowNote = ' <span style="color:#d63638;">(min ' + number(s8.min_pln) + ' zł)</span>';
                        }
                        html += trRow('8. + Prowizja wewnętrzna ' + s8.percent + '%', number(s8.amount_pln) + ' zł' + prowNote);
                    }
                    if (pb.step_9_vat) {
                        html += trRow('9. + VAT ' + pb.step_9_vat.percent + '%', number(pb.step_9_vat.amount_pln) + ' zł');
                    }

                    // Final
                    if (pb.total) {
                        html += trRow('Suma przed zaokrągleniem', number(pb.total.subtotal_pln) + ' zł');
                        html += trRow('Zaokrąglenie', 'do ' + number(pb.total.rounding_pln) + ' zł');
                        html += '<tr class="aa-final"><th>🏁 Cena końcowa</th><td>' + number(pb.total.cena_koncowa_pln) + ' zł</td></tr>';
                    }

                    html += '</table></div>';
                } else if (pb) {
                    html += '<div class="aa-mi-section"><h3>Kalkulacja ceny</h3>'
                         + '<p class="description">Kursy walut nie ustawione — cena nie zostanie przeliczona. '
                         + '<a href="' + '<?php echo esc_url(admin_url('tools.php?page=asiaauto-import-config&tab=price')); ?>' + '">Ustaw kursy CNY→USD i USD→PLN</a>.</p></div>';
                }

                // === Images ===
                if (imgs.length > 0) {
                    html += '<div class="aa-mi-section"><h3>Zdjęcia (' + imgs.length + ')</h3>';
                    html += '<div class="aa-mi-images">';
                    const show = imgs.slice(0, 12);
                    show.forEach(function(url) {
                        html += '<img src="' + esc(url) + '" loading="lazy" onerror="this.style.display=\'none\'">';
                    });
                    if (imgs.length > 12) {
                        html += '<div style="display:flex;align-items:center;padding:0 15px;color:#50575e;">+' + (imgs.length - 12) + ' więcej</div>';
                    }
                    html += '</div></div>';
                }

                // === Extra prep summary ===
                if (d.extra_prep_count > 0) {
                    html += '<div class="aa-mi-section"><h3>Specyfikacja</h3>'
                         + '<p>' + d.extra_prep_count + ' parametrów w extra_prep — zostaną zaimportowane.</p></div>';
                }

                // === Pusta wydmuszka — blokada importu ===
                if (d.is_empty_shell) {
                    html += '<div class="aa-mi-section"><div class="notice notice-error" style="margin:0;padding:12px 15px;">'
                         + '<strong>Pusta oferta</strong> — API nie zwróciło marki, modelu ani parametrów '
                         + '(tylko cena). Oferta jest prawdopodobnie sprzedana lub usunięta na Dongchedi. '
                         + 'Import zablokowany, bo powstałby listing bez danych. '
                         + 'Jeśli na pewno jest dostępna — otwórz ją na dongchedi.com, odczekaj ~30 s i odśwież podgląd.'
                         + '</div></div>';
                }

                // === Import button ===
                if (!d.exists_in_wp && !d.is_empty_shell) {
                    html += '<div class="aa-mi-section">';
                    html += '<button type="button" id="aa-mi-import-btn" class="button button-primary button-hero" '
                         + 'data-inner-id="' + esc(api.inner_id) + '" data-source="' + esc(d.source) + '">'
                         + 'Zaimportuj tę ofertę</button>';
                    html += '<span id="aa-mi-import-spinner" class="spinner" style="float:none;margin-top:10px;"></span>';
                    html += '<div id="aa-mi-import-result"></div>';
                    html += '</div>';
                }

                preview.innerHTML = html;

                const importBtn = document.getElementById('aa-mi-import-btn');
                if (importBtn) {
                    importBtn.addEventListener('click', doImport);
                }
            }

            function doImport() {
                const btn = document.getElementById('aa-mi-import-btn');
                const spn = document.getElementById('aa-mi-import-spinner');
                const res = document.getElementById('aa-mi-import-result');

                if (!confirm('Zaimportować tę ofertę? Zostanie dodana z pominięciem filtrów konfiguracji.')) return;

                btn.disabled = true;
                spn.classList.add('is-active');
                res.style.display = 'none';

                wp.ajax.post('asiaauto_manual_import', {
                    inner_id: btn.dataset.innerId,
                    source: btn.dataset.source,
                    _nonce: '<?php echo wp_create_nonce('asiaauto_manual_import'); ?>'
                }).done(function(data) {
                    res.className = data.warning ? 'notice notice-warning' : 'notice notice-success';
                    var headline = data.warning ? 'Zaimportowano (z ostrzeżeniem)' : 'Zaimportowano!';
                    var html = '<p><strong>' + headline + '</strong> Post #' + data.post_id
                        + ' — <a href="' + data.edit_url + '">Edytuj</a>'
                        + ' | <a href="' + data.view_url + '" target="_blank">Zobacz na stronie</a></p>';
                    if (data.warning) {
                        html += '<p style="margin-top:8px;"><strong>⚠ ' + esc(data.warning) + '</strong></p>';
                    }
                    res.innerHTML = html;
                    res.style.display = 'block';
                    btn.style.display = 'none';
                }).fail(function(err) {
                    res.className = 'notice notice-error';
                    res.innerHTML = '<p>' + (err || 'Błąd importu.') + '</p>';
                    res.style.display = 'block';
                    btn.disabled = false;
                }).always(function() {
                    spn.classList.remove('is-active');
                });
            }

            // Helpers
            function trRow(label, value) {
                return '<tr><th>' + label + '</th><td>' + value + '</td></tr>';
            }
            function trStep(label, value) {
                return '<tr class="aa-step-usd"><th>' + label + '</th><td>' + value + '</td></tr>';
            }
            function trSub(label, value) {
                return '<tr class="aa-sub"><th>' + label + '</th><td>' + value + '</td></tr>';
            }
            function trCif(label, value) {
                return '<tr class="aa-cif"><th>' + label + '</th><td>' + value + '</td></tr>';
            }

            function trFilter(label, displayValue, analysis) {
                if (!analysis) return trRow(label, displayValue);
                const cls = analysis.pass ? 'aa-highlight' : 'aa-warn';
                const icon = analysis.pass ? '<span class="aa-filter-pass">✅</span>' : '<span class="aa-filter-fail">❌</span>';
                let note = '';
                if (analysis.reason) {
                    note = ' <small style="color:#666;">(' + esc(analysis.reason) + ')</small>';
                }
                return '<tr class="' + cls + '"><th>' + label + '</th><td>' + icon + ' ' + displayValue + note + '</td></tr>';
            }

            function number(v) {
                if (v === null || v === undefined || v === '') return '—';
                return Number(v).toLocaleString('pl-PL', {maximumFractionDigits: 0});
            }
            function num4(v) {
                if (v === null || v === undefined || v === '') return '—';
                return Number(v).toLocaleString('pl-PL', {maximumFractionDigits: 4});
            }

            function esc(s) {
                if (!s) return '';
                const d = document.createElement('div');
                d.textContent = String(s);
                return d.innerHTML;
            }
        })();
        </script>
        <?php
    }

    // =========================================================================
    // AJAX: PREVIEW
    // =========================================================================

    public function ajaxPreview(): void {
        check_ajax_referer('asiaauto_manual_import', '_nonce');

        if (!current_user_can(AsiaAuto_Security::IMPORT_CAP)) {
            wp_send_json_error('Brak uprawnień.');
        }

        $raw = sanitize_text_field(wp_unslash($_POST['input'] ?? ''));
        if (empty($raw)) {
            wp_send_json_error('Wpisz URL lub inner_id.');
        }

        $inner_id = $this->parseInput($raw);
        if (empty($inner_id)) {
            wp_send_json_error('Nie rozpoznano inner_id. Podaj link dongchedi.com/usedcar/NNNNN lub sam numer.');
        }

        $source = 'dongchedi';

        $api = $this->makeApi();
        if (!$api) {
            wp_send_json_error('API nie skonfigurowane (brak ASIAAUTO_API_KEY / ASIAAUTO_API_BASE_URL w wp-config.php).');
        }

        $response = $api->getOffer($source, $inner_id);
        if (!$response) {
            wp_send_json_error('API nie zwróciło danych dla inner_id: ' . $inner_id . '. Oferta może nie istnieć lub być usunięta.');
        }

        $data = $this->extractData($response);
        if (empty($data['inner_id'])) {
            wp_send_json_error('Odpowiedź API nie zawiera inner_id. Sprawdź logi.');
        }

        $filter_analysis = $this->analyzeFilters($data, $source);

        // Price breakdown v2 — czytaj fuel type z API jeśli dostępny
        $price_cny = (float) ($data['price'] ?? 0);
        $price_breakdown = null;
        if ($price_cny > 0) {
            // Fuel type: z engine_type przez translator, domyślnie '' (akcyza 0)
            $fuel_type = '';
            if (!empty($data['engine_type']) && class_exists('AsiaAuto_Translator')) {
                $translator_tmp = new AsiaAuto_Translator();
                $translated_fuel = $translator_tmp->translate('engine_type', $data['engine_type']);
                if ($translated_fuel) {
                    // Mapowanie polskich nazw na slugi akcyzowe.
                    // WAŻNE: specyficzne warianty (phev/mhev/erev/electric) muszą być PRZED
                    // 'hybryda', bo str_contains() łapie pierwsze trafienie — np. dla
                    // "Hybryda plug-in (PHEV)" bez tej kolejności padłby 'hybrid' zamiast 'phev',
                    // co daje błędną akcyzę (1.55% zamiast 0%) w podglądzie.
                    $fuel_map = [
                        'phev'        => 'phev',
                        'mhev'        => 'mhev',
                        'erev'        => 'erev',
                        'elektryczny' => 'electric',
                        'hybryda'     => 'hybrid',
                        'benzyna'     => 'petrol',
                        'diesel'      => 'diesel',
                        'cng'         => 'cng',
                    ];
                    $lower = mb_strtolower($translated_fuel, 'UTF-8');
                    foreach ($fuel_map as $needle => $slug) {
                        if (str_contains($lower, $needle)) {
                            $fuel_type = $slug;
                            break;
                        }
                    }
                }
            }
            $price_breakdown = AsiaAuto_Price::calculateFromCny($price_cny, null, $fuel_type);
        }

        // Translations preview
        $translator = new AsiaAuto_Translator();
        $translations = [];
        if (!empty($data['engine_type']))        $translations['engine_type']       = $translator->translate('engine_type', $data['engine_type']) ?: $data['engine_type'];
        if (!empty($data['body_type']))          $translations['body_type']         = $translator->translate('body_type', $data['body_type']) ?: $data['body_type'];
        if (!empty($data['transmission_type']))  $translations['transmission_type'] = $translator->translate('transmission', $data['transmission_type']) ?: $data['transmission_type'];
        if (!empty($data['drive_type']))         $translations['drive_type']        = $translator->translate('drive_type', $data['drive_type']) ?: $data['drive_type'];
        if (!empty($data['color']))              $translations['color']             = $translator->translate('color', $data['color']) ?: $data['color'];

        $images = $this->parseImages($data['images'] ?? '');

        $importer = new AsiaAuto_Importer($translator, new AsiaAuto_Media());
        $wp_post_id = $importer->findByInnerId($data['inner_id'], $source);

        $extra_prep = $data['extra_prep'] ?? null;
        $ep_count = 0;
        if (is_array($extra_prep)) {
            $ep_count = count($extra_prep);
        } elseif (is_string($extra_prep) && !empty($extra_prep)) {
            $decoded = json_decode($extra_prep, true);
            $ep_count = is_array($decoded) ? count($decoded) : 0;
        }

        wp_send_json_success([
            'api_data'         => $data,
            'source'           => $source,
            'filter_analysis'  => $filter_analysis,
            'all_filters_pass' => $this->allFiltersPass($filter_analysis),
            'price_breakdown'  => $price_breakdown,
            'translations'     => $translations,
            'images'           => array_slice($images, 0, 20),
            'extra_prep_count' => $ep_count,
            'exists_in_wp'     => (bool) $wp_post_id,
            'wp_post_id'       => $wp_post_id,
            'wp_edit_url'      => $wp_post_id ? get_edit_post_link($wp_post_id, 'raw') : null,
            'is_empty_shell'   => AsiaAuto_Importer::isEmptyShell($data),
        ]);
    }

    // =========================================================================
    // AJAX: IMPORT
    // =========================================================================

    public function ajaxImport(): void {
        check_ajax_referer('asiaauto_manual_import', '_nonce');

        if (!current_user_can(AsiaAuto_Security::IMPORT_CAP)) {
            wp_send_json_error('Brak uprawnień.');
        }

        $inner_id = sanitize_text_field(wp_unslash($_POST['inner_id'] ?? ''));
        $source   = sanitize_text_field(wp_unslash($_POST['source'] ?? 'dongchedi'));

        if (empty($inner_id)) {
            wp_send_json_error('Brak inner_id.');
        }

        $translator = new AsiaAuto_Translator();
        $media      = new AsiaAuto_Media();
        $importer   = new AsiaAuto_Importer($translator, $media);

        $existing = $importer->findByInnerId($inner_id, $source);
        if ($existing) {
            wp_send_json_error('Oferta już istnieje w WordPress — post #' . $existing);
        }

        $api = $this->makeApi();
        if (!$api) {
            wp_send_json_error('API nie skonfigurowane.');
        }

        $response = $api->getOffer($source, $inner_id);
        if (!$response) {
            wp_send_json_error('API nie zwróciło danych. Sprawdź logi.');
        }

        $data = $this->extractData($response);
        if (empty($data['inner_id'])) {
            wp_send_json_error('Odpowiedź API nie zawiera inner_id.');
        }

        if (AsiaAuto_Importer::isEmptyShell($data)) {
            wp_send_json_error(
                'API zwróciło pustą ofertę (brak marki/modelu/parametrów) — '
                . 'oferta jest prawdopodobnie sprzedana lub usunięta na Dongchedi. '
                . 'Jeśli na pewno jest dostępna, otwórz ją w przeglądarce na dongchedi.com, '
                . 'odczekaj ~30 s (odświeży się cache) i spróbuj ponownie.'
            );
        }

        // Kanonizacja mark/model przed importem — ta sama, którą automatyczny sync robi
        // w normalizeForSource(). Bez niej che168 dociera tu surowy (`唐L`), brand-mapping
        // (klucz `BYD|Tang L EV`) nie trafia, importer wpada w fallback translateModel()
        // i zapisuje CJK w post_title — a stamtąd wycieka do breadcrumbu, schema.org,
        // alt-ów, nazw plików zdjęć i dataLayer. Casus 399762/401019/470577 (2026-09-08).
        //
        // Świadomie BEZ guarda isMappedForImport(): sync nim odrzuca niezmapowane oferty,
        // ale tutaj ofertę wskazuje człowiek (force=true) i odrzucenie byłoby błędem.
        // Niezmapowana oferta wchodzi jak dotąd — z CN w tytule, do ręcznej poprawy.
        if ($source === 'che168') {
            $data = AsiaAuto_Che168_Adapter::normalize($data);
        }

        $post_id = $importer->importListing($data, $source, true);

        if (!$post_id) {
            wp_send_json_error('Import nieudany — sprawdź logi (Narzędzia → AsiaAuto Import).');
        }

        update_post_meta($post_id, '_asiaauto_manual_import', '1');
        update_post_meta($post_id, '_asiaauto_manual_import_by', get_current_user_id());
        update_post_meta($post_id, '_asiaauto_manual_import_at', gmdate('c'));

        AsiaAuto_Logger::info("Manual import: {$source}:{$inner_id} → post #{$post_id} by user #" . get_current_user_id());

        $response = [
            'post_id'  => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'view_url' => get_permalink($post_id),
        ];

        // v0.32.34: W2 guard mógł przenieść post do draft (zdjęcia padły)
        $gallery = get_post_meta($post_id, 'gallery', true);
        $no_gallery = empty($gallery) || (is_array($gallery) && count($gallery) === 0);
        if ($no_gallery || get_post_status($post_id) === 'draft') {
            $response['warning'] = 'Zdjęcia nie zostały pobrane (URL-e Dongchedi wygasły — '
                . 'API zwraca stary cache). Listing zapisany jako szkic. Otwórz ofertę '
                . 'na Dongchedi w przeglądarce (auto-odświeży cache po stronie auto-api.com) '
                . 'i ponów import za ~30s.';
        }

        wp_send_json_success($response);
    }

    // =========================================================================
    // HELPERS (bez zmian vs v0.29.x)
    // =========================================================================

    private function parseInput(string $input): string {
        if (preg_match('/^\d{5,15}$/', $input)) {
            return $input;
        }
        if (preg_match('#dongchedi\.com/usedcar/(\d+)#', $input, $m)) {
            return $m[1];
        }
        if (preg_match('/(\d{5,15})/', $input, $m)) {
            return $m[1];
        }
        return '';
    }

    private function analyzeFilters(array $data, string $source): array {
        $config = get_option('asiaauto_import_config', []);
        $sc = $config[$source] ?? [];

        if (empty($sc) || empty($sc['enabled'])) {
            return [
                'mark'  => ['pass' => true, 'reason' => 'Filtry wyłączone'],
                'year'  => ['pass' => true, 'reason' => 'Filtry wyłączone'],
                'km_age' => ['pass' => true, 'reason' => 'Filtry wyłączone'],
                'price' => ['pass' => true, 'reason' => 'Filtry wyłączone'],
            ];
        }

        $result = [];

        $mark = $data['mark'] ?? '';
        $marks_list = $sc['marks'] ?? [];
        if (!empty($marks_list)) {
            $in_list = in_array($mark, $marks_list, true);
            $result['mark'] = [
                'pass'   => $in_list,
                'reason' => $in_list ? 'w liście filtrów' : 'brak w liście filtrów (' . count($marks_list) . ' marek)',
            ];
        } else {
            $result['mark'] = ['pass' => true, 'reason' => 'brak filtra marek'];
        }

        $year = (int) ($data['year'] ?? 0);
        $year_pass = true;
        $year_reason = '';
        if (!empty($sc['year_from']) && $year < $sc['year_from']) {
            $year_pass = false;
            $year_reason = 'min: ' . $sc['year_from'];
        }
        if (!empty($sc['year_to']) && $year > $sc['year_to']) {
            $year_pass = false;
            $year_reason = 'max: ' . $sc['year_to'];
        }
        if ($year_pass) $year_reason = '≥ ' . ($sc['year_from'] ?? '—') . ($sc['year_to'] ? ', ≤ ' . $sc['year_to'] : '');
        $result['year'] = ['pass' => $year_pass, 'reason' => $year_reason];

        $km = (int) ($data['km_age'] ?? 0);
        $km_pass = true;
        $km_reason = '';
        if (!empty($sc['km_from']) && $km < $sc['km_from']) {
            $km_pass = false;
            $km_reason = 'min: ' . number_format($sc['km_from'], 0, ',', ' ') . ' km';
        }
        if (!empty($sc['km_to']) && $km > $sc['km_to']) {
            $km_pass = false;
            $km_reason = 'max: ' . number_format($sc['km_to'], 0, ',', ' ') . ' km';
        }
        if ($km_pass) $km_reason = number_format($sc['km_from'] ?? 0, 0, ',', ' ') . '–' . number_format($sc['km_to'] ?? 999999, 0, ',', ' ') . ' km';
        $result['km_age'] = ['pass' => $km_pass, 'reason' => $km_reason];

        $price = (int) ($data['price'] ?? 0);
        $price_pass = true;
        $price_reason = '';
        if (!empty($sc['price_from']) && $price < $sc['price_from']) {
            $price_pass = false;
            $price_reason = 'min: ' . number_format($sc['price_from'], 0, ',', ' ') . ' ¥';
        }
        if (!empty($sc['price_to']) && $price > $sc['price_to']) {
            $price_pass = false;
            $price_reason = 'max: ' . number_format($sc['price_to'], 0, ',', ' ') . ' ¥';
        }
        if ($price_pass) $price_reason = '≥ ' . number_format($sc['price_from'] ?? 0, 0, ',', ' ') . ' ¥' . ($sc['price_to'] ? ', ≤ ' . number_format($sc['price_to'], 0, ',', ' ') . ' ¥' : '');
        $result['price'] = ['pass' => $price_pass, 'reason' => $price_reason];

        return $result;
    }

    private function allFiltersPass(array $analysis): bool {
        foreach ($analysis as $item) {
            if (!$item['pass']) return false;
        }
        return true;
    }

    private function extractData(array $response): array {
        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }
        if (isset($response['result'][0]['data']) && is_array($response['result'][0]['data'])) {
            return $response['result'][0]['data'];
        }
        if (isset($response['result']) && is_array($response['result']) && !isset($response['result'][0])) {
            return $response['result'];
        }
        return $response;
    }

    private function parseImages($raw): array {
        if (is_array($raw)) return $raw;
        if (is_string($raw) && !empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
            if (str_contains($raw, ',')) return array_map('trim', explode(',', $raw));
            return [$raw];
        }
        return [];
    }

    private function makeApi(): ?AsiaAuto_API {
        $key = defined('ASIAAUTO_API_KEY') ? ASIAAUTO_API_KEY : '';
        $url = defined('ASIAAUTO_API_BASE_URL') ? ASIAAUTO_API_BASE_URL : '';
        if (empty($key) || empty($url)) return null;
        return new AsiaAuto_API($key, $url);
    }
}
