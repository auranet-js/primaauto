<?php
/**
 * Przeglądaj Dongchedi — przeglądarka ofert giełdy z filtrami marka/model/rocznik/miasto/cena.
 * Czysty konsument: AsiaAuto_API + AsiaAuto_Che168_Dictionary (sparametryzowany źródłem)
 * + istniejące AJAX-y asiaauto_preview_offer / asiaauto_manual_import z „Dodaj z Dongchedi".
 * Strefa krucha nietknięta — zero własnej logiki importu i pipeline'u cenowego.
 *
 * Bez gate'a fazy testów (decyzja 18.09.2026) — sam AsiaAuto_Security::IMPORT_CAP,
 * tak jak „Dodaj z Dongchedi". Strona jest narzędziem operatora do brania pojedynczych sztuk.
 *
 * Powód powstania: dongchedi chodzi w trybie „tylko aktualizacja" (92% pełnego strumienia
 * to modele i roczniki, które już mamy z che168) — unikalne sztuki bierzemy ręcznie.
 *
 * @since 2026-09-18
 */

if (!defined('ABSPATH')) {
    exit;
}

class AsiaAuto_Admin_Dongchedi_Browse {

    private const PAGE       = 'asiaauto-dongchedi-browse';
    private const SOURCE     = 'dongchedi';
    private const MAX_MODELS = 5;  // API przyjmuje jeden model per zapytanie — limit zapytań na „Szukaj"
    private const SPEC_THIN  = 100; // poniżej tylu pól extra_prep oferta wchodzi chuda (patrz renderPage)

    public function __construct() {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('wp_ajax_asiaauto_dongchedi_browse', [$this, 'ajaxBrowse']);
    }

    private function allowed(): bool {
        return current_user_can(AsiaAuto_Security::IMPORT_CAP);
    }

    public function addMenuPage(): void {
        add_submenu_page(
            'edit.php?post_type=listings',
            'Przeglądaj Dongchedi',
            'Przeglądaj Dongchedi',
            AsiaAuto_Security::IMPORT_CAP,
            self::PAGE,
            [$this, 'renderPage']
        );
    }

    // =========================================================================
    // AJAX
    // =========================================================================

    public function ajaxBrowse(): void {
        check_ajax_referer('asiaauto_dongchedi_browse', '_nonce');
        if (!$this->allowed()) {
            wp_send_json_error('Brak dostępu.');
        }

        $args = [
            'mark'            => sanitize_text_field(wp_unslash($_POST['mark'] ?? '')),
            'models'          => array_slice(array_filter(array_map(
                fn($m) => sanitize_text_field(wp_unslash($m)),
                (array) ($_POST['models'] ?? [])
            )), 0, self::MAX_MODELS),
            'year_from'       => (int) ($_POST['year_from'] ?? 0),
            'year_to'         => (int) ($_POST['year_to'] ?? 0),
            'price_min'       => (int) ($_POST['price_min'] ?? 0),
            'only_our_cities' => !empty($_POST['only_our_cities']),
            'page'            => max(1, (int) ($_POST['page'] ?? 1)),
        ];
        if ($args['mark'] === '') {
            wp_send_json_error('Wybierz markę.');
        }

        $result = $this->browseOffers($args);
        if ($result === null) {
            wp_send_json_error('API nie odpowiada — spróbuj ponownie.');
        }
        wp_send_json_success($result);
    }

    /**
     * Core logiki przeglądania — publiczne, testowalne z wp eval bez kontekstu AJAX.
     *
     * @return array{offers: array, next_page: ?int}|null null gdy API padło
     */
    public function browseOffers(array $a): ?array {
        if (!defined('ASIAAUTO_API_KEY') || !defined('ASIAAUTO_API_BASE_URL')) {
            return null;
        }
        $api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);

        $cities_pl = include ASIAAUTO_PLUGIN_DIR . 'data/translations-cities.php';
        $config    = get_option('asiaauto_import_config', []);
        $our_zh    = array_flip(array_column($config[self::SOURCE]['city_filter_cities'] ?? [], 'zh'));
        $blacklist = $config[self::SOURCE]['model_blacklist'] ?? [];

        // API przyjmuje jeden model per zapytanie — bez modeli 1 zapytanie, z modelami po jednym per model.
        $queries   = $a['models'] === [] ? [null] : $a['models'];
        $out       = [];
        $next_page = null;
        $got_any   = false;

        foreach ($queries as $model) {
            $params = ['page' => $a['page'], 'mark' => $a['mark']];
            if ($model !== null) {
                $params['model'] = $model;
            }
            if ($a['year_from'] > 0) {
                $params['year_from'] = $a['year_from'];
            }
            if ($a['year_to'] > 0) {
                $params['year_to'] = $a['year_to'];
            }
            $resp = $api->getOffers(self::SOURCE, $params);
            if (!is_array($resp)) {
                continue;
            }
            $got_any = true;
            if (!empty($resp['meta']['next_page'])) {
                $next_page = (int) $resp['meta']['next_page'];
            }
            foreach (($resp['result'] ?? []) as $row) {
                $o = $row['data'] ?? null;
                if (!$o || empty($o['inner_id']) || isset($out[(string) $o['inner_id']])) {
                    continue;
                }
                if ($a['price_min'] > 0 && (int) ($o['price'] ?? 0) < $a['price_min']) {
                    continue;
                }
                // Dongchedi ma czyste pole `city` (海口); `address` to pełny adres ulicy
                // (海口市保税区76号汽车小镇1号楼) i nigdy nie trafi w listę miast — inaczej niż che168.
                $city = trim((string) ($o['city'] ?? ''));
                if ($city === '') {
                    $city = trim(explode(',', (string) ($o['address'] ?? ''))[0]);
                }
                $is_our = isset($our_zh[$city]);
                if ($a['only_our_cities'] && !$is_our) {
                    continue;
                }
                [$cMark, $cModel] = AsiaAuto_Mapping::canonicalKeyForSource(
                    (string) ($o['mark'] ?? ''),
                    (string) ($o['model'] ?? ''),
                    (string) ($o['engine_type'] ?? ''),
                    self::SOURCE
                );
                $imgs = $o['images'] ?? [];
                if (is_string($imgs)) {
                    $imgs = json_decode($imgs, true) ?: [];
                }
                $prep = $o['extra_prep'] ?? [];
                if (is_string($prep)) {
                    $prep = json_decode($prep, true) ?: [];
                }
                $out[(string) $o['inner_id']] = [
                    'inner_id'    => (string) $o['inner_id'],
                    'mark'        => (string) ($o['mark'] ?? ''),
                    'model'       => (string) ($o['model'] ?? ''),
                    'canon'       => $cMark . '|' . $cModel,
                    'year'        => (int) ($o['year'] ?? 0),
                    // dongchedi trzyma pierwszą rejestrację w reg_date; first_registration jest NULL.
                    'reg'         => (string) ($o['reg_date'] ?? $o['first_registration'] ?? ''),
                    'price'       => (int) ($o['price'] ?? 0),
                    'km'          => (int) ($o['km_age'] ?? 0),
                    'fuel'        => (string) ($o['engine_type'] ?? ''),
                    'city_pl'     => $cities_pl[$city] ?? $city,
                    'city_our'    => $is_our,
                    'img'         => (string) ($imgs[0] ?? ''),
                    'url'         => (string) ($o['url'] ?? ''),
                    'mapped'      => AsiaAuto_Mapping::getEuForCn($cMark, $cModel) !== null,
                    'blacklisted' => in_array($cMark . '|' . $cModel, (array) ($blacklist[$cMark] ?? []), true),
                    'spec'        => is_array($prep) ? count($prep) : 0,
                    'exists'      => 0,
                    'edit_url'    => '',
                ];
            }
        }
        if (!$got_any) {
            return null;
        }

        $importer = new AsiaAuto_Importer(new AsiaAuto_Translator(), new AsiaAuto_Media());
        foreach ($out as &$o) {
            $post_id = (int) $importer->findByInnerId($o['inner_id'], self::SOURCE);
            $o['exists']   = $post_id;
            $o['edit_url'] = $post_id ? (string) get_edit_post_link($post_id, 'raw') : '';
        }
        unset($o);

        return ['offers' => array_values($out), 'next_page' => $next_page];
    }

    // =========================================================================
    // RENDER
    // =========================================================================

    public function renderPage(): void {
        if (!$this->allowed()) {
            wp_die('Brak dostępu.');
        }

        $raw       = AsiaAuto_Che168_Dictionary::get(self::SOURCE);
        $config    = get_option('asiaauto_import_config', []);
        $sc        = $config[self::SOURCE] ?? [];
        $blacklist = $sc['model_blacklist'] ?? [];

        // payload: rawMark => [{m: rawModel, key: canonKey, mapped: bool, bl: bool}]
        $dict = [];
        foreach (($raw['mark'] ?? []) as $srcMark => $info) {
            foreach (AsiaAuto_Che168_Dictionary::modelNames((array) $info) as $srcModel) {
                [$cMark, $cModel] = AsiaAuto_Mapping::canonicalKeyForSource((string) $srcMark, $srcModel, '', self::SOURCE);
                $key = $cMark . '|' . $cModel;
                $dict[$srcMark][] = [
                    'm'      => $srcModel,
                    'key'    => $key,
                    'mapped' => AsiaAuto_Mapping::getEuForCn($cMark, $cModel) !== null,
                    'bl'     => in_array($key, (array) ($blacklist[$cMark] ?? []), true),
                ];
            }
        }
        ksort($dict);

        $defaults = [
            'year_from' => (int) ($sc['year_from'] ?? 2024),
            'price_min' => (int) ($sc['price_from'] ?? 0),
        ];
        $fetched = AsiaAuto_Che168_Dictionary::fetchedAt(self::SOURCE);
        ?>
        <div class="wrap">
            <h1>Przeglądaj Dongchedi</h1>
            <p class="description">
                Wybierz markę (pełny słownik giełdy: <?php echo count($dict); ?> marek<?php
                    echo $fetched ? ', pobrany ' . esc_html(get_date_from_gmt(gmdate('Y-m-d H:i:s', strtotime($fetched)), 'j.m.Y H:i')) : ''; ?>),
                opcjonalnie zawęź do modeli (max <?php echo self::MAX_MODELS; ?>), i szukaj.
                Import per klik idzie tą samą ścieżką co „Dodaj z Dongchedi” (z pominięciem filtrów konfiguracji).
                🆕 = model bez huba (najpierw domapuj, potem importuj). ⛔ = model na blackliście (informacyjnie — import ręczny nie jest blokowany).
                <strong>spec</strong> = liczba pól specyfikacji w payloadzie API; poniżej <?php echo self::SPEC_THIN; ?> oferta wejdzie chuda
                (dongchedi nie ma <code>spec_id</code>, więc katalog Autohome jej nie uzupełni — dopiero nocny cykl z bliźniaka i banku specyfikacji).
            </p>

            <div id="aad-bar" style="background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:14px 16px;display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;margin:14px 0;">
                <div><label style="display:block;font-size:11px;color:#646970;font-weight:600;">MARKA</label>
                    <select id="aad-mark" style="min-width:220px;"><option value="">— wybierz —</option></select></div>
                <div><label style="display:block;font-size:11px;color:#646970;font-weight:600;">ROCZNIK OD</label>
                    <input type="number" id="aad-year" value="<?php echo esc_attr((string) $defaults['year_from']); ?>" min="2000" max="2030" style="width:90px;"></div>
                <div><label style="display:block;font-size:11px;color:#646970;font-weight:600;">CENA OD (¥)</label>
                    <input type="number" id="aad-price" value="<?php echo esc_attr((string) $defaults['price_min']); ?>" step="1000" style="width:110px;"></div>
                <div><label style="display:block;font-size:11px;color:#646970;font-weight:600;">MIASTA</label>
                    <select id="aad-city"><option value="">całe Chiny</option><option value="1">tylko nasza lista (<?php echo count($sc['city_filter_cities'] ?? []); ?>)</option></select></div>
                <div><button type="button" class="button button-primary" id="aad-search">Szukaj</button></div>
                <div><span id="aad-status" style="color:#646970;"></span></div>
            </div>

            <div id="aad-models" style="display:none;background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:10px 16px;margin-bottom:14px;">
                <label style="font-size:11px;color:#646970;font-weight:600;">MODELE (ze słownika API — kliknij, by zawęzić; max <?php echo self::MAX_MODELS; ?>)</label>
                <div id="aad-chips" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;"></div>
            </div>

            <div id="aad-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;"></div>
            <p style="text-align:center;margin:18px 0;"><button type="button" class="button" id="aad-more" style="display:none;">Pokaż więcej</button></p>
        </div>

        <style>
            .aad-card{background:#fff;border:1px solid #dcdcde;border-radius:6px;overflow:hidden;display:flex;flex-direction:column;}
            .aad-ph{position:relative;aspect-ratio:4/3;background:#e5e5e5 center/cover no-repeat;}
            .aad-badge{position:absolute;top:8px;left:8px;background:rgba(0,0,0,.65);color:#fff;border-radius:3px;padding:1px 7px;font-size:11px;}
            .aad-badge.hub{background:#00731d;}.aad-badge.orph{background:#996800;}
            .aad-badge.city{left:auto;right:8px;background:rgba(34,113,177,.9);}
            .aad-body{padding:10px 12px;display:flex;flex-direction:column;gap:4px;flex:1;}
            .aad-body h3{margin:0;font-size:14px;line-height:1.3;}
            .aad-price{font-size:16px;font-weight:700;}
            .aad-specs{color:#646970;font-size:12px;}
            .aad-thin{color:#996800;font-weight:600;}
            .aad-actions{display:flex;gap:6px;margin-top:auto;padding-top:8px;}
            .aad-actions .button{flex:1;text-align:center;font-size:12px;}
            .aad-msg{font-size:12px;margin-top:6px;}
            .aad-msg.err{color:#d63638;}.aad-msg.ok{color:#00731d;}
            .aad-chip{border:1px solid #dcdcde;border-radius:14px;padding:2px 10px;cursor:pointer;user-select:none;font-size:12px;background:#f6f7f7;}
            .aad-chip.on{background:#2271b1;border-color:#2271b1;color:#fff;}
        </style>

        <script>
        (function() {
            const DICT   = <?php echo wp_json_encode($dict); ?>;
            const NONCE  = '<?php echo esc_js(wp_create_nonce('asiaauto_dongchedi_browse')); ?>';
            const IMPORT_NONCE = '<?php echo esc_js(wp_create_nonce('asiaauto_manual_import')); ?>';
            // esc_url_raw, NIE esc_js — esc_js zamienia & na &#038; i "#" ucina URL jako fragment.
            const PREVIEW_PAGE = '<?php echo esc_url_raw(admin_url('edit.php?post_type=listings&page=asiaauto-manual-import')); ?>';
            const THIN = <?php echo self::SPEC_THIN; ?>;
            const FUEL = {'plug-in hybrid':'PHEV','electric':'EV','extended-range':'EREV','gasoline':'benzyna','diesel':'diesel','PHEV':'PHEV','EV':'EV'};
            let selModels = [], page = 1;

            const markSel = document.getElementById('aad-mark');
            Object.keys(DICT)
                .sort((a, b) => String(a).localeCompare(String(b), 'pl'))
                .forEach(m => {
                    const o = document.createElement('option');
                    o.value = m;
                    o.textContent = m + ' (' + DICT[m].length + ')';
                    markSel.appendChild(o);
                });

            markSel.addEventListener('change', () => { selModels = []; renderChips(); });

            function renderChips() {
                const box = document.getElementById('aad-chips');
                const wrap = document.getElementById('aad-models');
                const mark = markSel.value;
                box.innerHTML = '';
                if (!mark || !DICT[mark]) { wrap.style.display = 'none'; return; }
                wrap.style.display = '';
                DICT[mark].forEach(e => {
                    const c = document.createElement('span');
                    c.className = 'aad-chip' + (selModels.includes(e.m) ? ' on' : '');
                    c.textContent = e.m + (e.mapped ? '' : ' 🆕') + (e.bl ? ' ⛔' : '');
                    c.onclick = () => {
                        const i = selModels.indexOf(e.m);
                        if (i !== -1) { selModels.splice(i, 1); }
                        else if (selModels.length < <?php echo self::MAX_MODELS; ?>) { selModels.push(e.m); }
                        else { document.getElementById('aad-status').textContent = 'Max <?php echo self::MAX_MODELS; ?> modeli na raz.'; return; }
                        renderChips();
                    };
                    box.appendChild(c);
                });
            }

            function search(reset) {
                if (!markSel.value) { document.getElementById('aad-status').textContent = 'Najpierw wybierz markę.'; return; }
                if (reset) { page = 1; document.getElementById('aad-grid').innerHTML = ''; }
                document.getElementById('aad-status').textContent = 'Szukam…';
                const fd = new FormData();
                fd.append('action', 'asiaauto_dongchedi_browse');
                fd.append('_nonce', NONCE);
                fd.append('mark', markSel.value);
                selModels.forEach(m => fd.append('models[]', m));
                fd.append('year_from', document.getElementById('aad-year').value || '0');
                fd.append('price_min', document.getElementById('aad-price').value || '0');
                fd.append('only_our_cities', document.getElementById('aad-city').value);
                fd.append('page', String(page));
                fetch(ajaxurl, {method: 'POST', body: fd, credentials: 'same-origin'})
                    .then(r => r.json())
                    .then(d => {
                        if (!d.success) { document.getElementById('aad-status').textContent = d.data || 'Błąd.'; return; }
                        renderCards(d.data.offers);
                        document.getElementById('aad-status').textContent =
                            document.querySelectorAll('.aad-card').length + ' ofert (strona ' + page + ')';
                        const more = document.getElementById('aad-more');
                        if (d.data.next_page) { more.style.display = ''; page = d.data.next_page; }
                        else { more.style.display = 'none'; }
                    })
                    .catch(() => { document.getElementById('aad-status').textContent = 'Błąd sieci.'; });
            }

            function esc(s) { const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML; }

            function renderCards(offers) {
                const g = document.getElementById('aad-grid');
                offers.forEach(o => {
                    const d = document.createElement('div');
                    d.className = 'aad-card';
                    const badge = o.mapped
                        ? '<span class="aad-badge hub">✅ hub</span>'
                        : '<span class="aad-badge orph">🆕 bez huba</span>';
                    // CDN bytedance tnie hotlinki po nagłówku Referer — img MUSI mieć no-referrer.
                    const photo = o.img
                        ? '<img src="' + esc(o.img) + '" referrerpolicy="no-referrer" loading="lazy" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">'
                        : '';
                    const spec = o.spec >= THIN
                        ? '<span>spec: ' + o.spec + ' pól</span>'
                        : '<span class="aad-thin">spec: ' + o.spec + ' pól — chuda, czeka na bliźniaka</span>';
                    const action = o.exists
                        ? '<a class="button" href="' + esc(o.edit_url) + '" target="_blank" rel="noopener">✅ w bazie #' + o.exists + '</a>'
                        : '<button type="button" class="button button-primary aad-import" data-id="' + esc(o.inner_id) + '">⬇ Importuj</button>';
                    d.innerHTML =
                        '<div class="aad-ph">' + photo + badge +
                        '<span class="aad-badge city">' + esc(o.city_pl) + (o.city_our ? '' : ' ⚠') + '</span></div>' +
                        '<div class="aad-body">' +
                        '<h3>' + esc(o.mark + ' ' + o.model) + ' <small style="color:#646970;font-weight:400">' + o.year + '</small>' + (o.blacklisted ? ' ⛔' : '') + '</h3>' +
                        '<div class="aad-price">' + o.price.toLocaleString('pl-PL') + ' ¥</div>' +
                        '<div class="aad-specs">' + esc(FUEL[o.fuel] || o.fuel || '—') + ' · ' + o.km.toLocaleString('pl-PL') + ' km · 1. rej. ' + esc(o.reg || '—') + ' · #' + esc(o.inner_id) + '</div>' +
                        '<div class="aad-specs">' + spec + '</div>' +
                        '<div class="aad-actions">' + action +
                        '<a class="button" target="_blank" rel="noopener" href="' + PREVIEW_PAGE + '&inner_id=' + encodeURIComponent(o.inner_id) + '">🔍 Podgląd</a>' +
                        '<a class="button" href="' + esc(o.url) + '" target="_blank" rel="noopener">dongchedi ↗</a>' +
                        '</div><div class="aad-msg" style="display:none;"></div></div>';
                    g.appendChild(d);
                });
            }

            // Import per klik — ta sama ścieżka co „Dodaj z Dongchedi” (AJAX asiaauto_manual_import),
            // dzięki czemu oferta dostaje _asiaauto_manual_import i wpada w widok „Ręczny import”.
            document.getElementById('aad-grid').addEventListener('click', function(ev) {
                const btn = ev.target.closest('.aad-import');
                if (!btn) { return; }
                const msg = btn.closest('.aad-body').querySelector('.aad-msg');
                btn.disabled = true;
                btn.textContent = 'Importuję…';
                msg.style.display = 'block';
                msg.className = 'aad-msg';
                msg.textContent = 'Pobieram dane i zdjęcia — to może potrwać kilkanaście sekund.';
                const fd = new FormData();
                fd.append('action', 'asiaauto_manual_import');
                fd.append('_nonce', IMPORT_NONCE);
                fd.append('inner_id', btn.dataset.id);
                fd.append('source', 'dongchedi');
                fetch(ajaxurl, {method: 'POST', body: fd, credentials: 'same-origin'})
                    .then(r => r.json())
                    .then(d => {
                        if (!d.success) {
                            msg.className = 'aad-msg err';
                            msg.textContent = (typeof d.data === 'string' ? d.data : 'Import nie powiódł się.');
                            btn.disabled = false;
                            btn.textContent = '⬇ Importuj';
                            return;
                        }
                        msg.className = 'aad-msg ok';
                        msg.innerHTML = 'Zaimportowano → <a href="' + esc(d.data.edit_url) + '" target="_blank" rel="noopener">edytuj #' + esc(d.data.post_id) + '</a>'
                            + (d.data.warning ? '<br><span style="color:#996800;">' + esc(d.data.warning) + '</span>' : '');
                        btn.outerHTML = '<a class="button" href="' + esc(d.data.edit_url) + '" target="_blank" rel="noopener">✅ w bazie #' + esc(d.data.post_id) + '</a>';
                    })
                    .catch(() => {
                        msg.className = 'aad-msg err';
                        msg.textContent = 'Błąd sieci.';
                        btn.disabled = false;
                        btn.textContent = '⬇ Importuj';
                    });
            });

            document.getElementById('aad-search').addEventListener('click', () => search(true));
            document.getElementById('aad-more').addEventListener('click', () => search(false));
        })();
        </script>
        <?php
    }
}
