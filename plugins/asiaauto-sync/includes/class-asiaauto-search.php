<?php
/**
 * AsiaAuto_Search — wyszukiwarka zaawansowana (T-116 etap 3).
 *
 * Osobna strona `/wyszukiwarka/`, shortcode `[asiaauto_search]`, dwie trasy REST
 * w `asiaauto/v1`: `/search` (wyniki) i `/search-counts` (liczniki zależne).
 * Czyta WYŁĄCZNIE tabelę `wp7j_asiaauto_specs` (AsiaAuto_Specs_Table) — zero `meta_query`,
 * zero JOIN-ów do postmeta.
 *
 * Nie dziedziczy i nie modyfikuje `AsiaAuto_Inventory` (`/samochody/`). Jedyny styk:
 * `AsiaAuto_Inventory::renderCard()`, która jest publiczna od dawna — ta sama karta oferty
 * w obu wyszukiwarkach bez duplikowania kodu.
 *
 * @since v0.35.0 (T-116 etap 3)
 */

defined('ABSPATH') || exit;

class AsiaAuto_Search
{
    const PER_PAGE   = 24;
    const CACHE_TTL  = 600; // 10 min, czyszczony przez rebuildRow()
    const CACHE_SALT = 'asiaauto_search_';

    /** parametr URL => kolumna enum. Wartości rozdzielane przecinkiem = OR w obrębie pola. */
    const ENUM_PARAMS = [
        'marka'      => 'make',
        'model'      => 'serie',
        'kolor'      => 'color',
        'paliwo'     => 'fuel',
        'nadwozie'   => 'body',
        'naped'      => 'drive',
        'skrzynia'   => 'transmission',
        'tapicerka'  => 'upholstery',
        'szyberdach' => 'sunroof',
        'rocznik'    => 'year',
    ];

    /** prefiks URL (`_min`/`_max`) => [kolumna, typ]. */
    const RANGE_PARAMS = [
        'cena'     => ['price', 'int'],
        'przebieg' => ['mileage', 'int'],
        'rok'      => ['year', 'int'],
        'moc'      => ['power_km', 'int'],
        'zasieg'   => ['range_cltc', 'int'],
        'bateria'  => ['battery_kwh', 'float'],
        'miejsca'  => ['seats', 'int'],
        'felgi'    => ['rim_in', 'int'],
    ];

    const SORTS = [
        'date_desc'    => 'published_at DESC, post_id DESC',
        'price_asc'    => 'price ASC, post_id DESC',
        'price_desc'   => 'price DESC, post_id DESC',
        'mileage_asc'  => 'mileage ASC, post_id DESC',
        'power_desc'   => 'power_km DESC, post_id DESC',
        'range_desc'   => 'range_cltc DESC, post_id DESC',
    ];

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'registerEndpoints']);
        add_shortcode('asiaauto_search', [$this, 'renderShortcode']);
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
    }

    // ==================================================================== REST

    public function registerEndpoints(): void
    {
        register_rest_route('asiaauto/v1', '/search', [
            'methods'             => 'GET',
            'callback'            => [$this, 'handleSearch'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('asiaauto/v1', '/search-counts', [
            'methods'             => 'GET',
            'callback'            => [$this, 'handleCounts'],
            'permission_callback' => '__return_true',
        ]);
    }

    // ============================================================== parametry

    public function parseParams(array $in): array
    {
        $p = ['enum' => [], 'range' => [], 'flags' => []];

        foreach (self::ENUM_PARAMS as $url => $col) {
            if (!isset($in[$url])) continue;
            // JS buduje czytelny deep-link (`marka=byd,nio`), formularz bez JS wysyła `marka[]`
            $raw = is_array($in[$url]) ? $in[$url] : explode(',', (string) $in[$url]);
            $vals = array_values(array_filter(array_map(
                static fn($v) => sanitize_text_field(trim((string) $v)),
                $raw
            ), static fn($v) => $v !== ''));
            if ($vals) $p['enum'][$col] = $vals;
        }

        foreach (self::RANGE_PARAMS as $url => [$col, $type]) {
            foreach (['min', 'max'] as $side) {
                $key = $url . '_' . $side;
                if (!isset($in[$key]) || $in[$key] === '') continue;
                $v = $type === 'float' ? (float) $in[$key] : (int) $in[$key];
                if ($v <= 0) continue;
                $p['range'][$col][$side] = $v;
            }
        }

        $flagsIn = $in['wyposazenie'] ?? '';
        $flagsArr = is_array($flagsIn) ? $flagsIn : explode(',', (string) $flagsIn);
        foreach (array_filter($flagsArr) as $f) {
            $f = sanitize_key(trim($f));
            if (isset(AsiaAuto_Specs_Table::FLAGS[$f])) $p['flags'][] = $f;
        }
        $p['flags'] = array_values(array_unique($p['flags']));

        $sort = (string) ($in['sort'] ?? 'date_desc');
        $p['sort'] = isset(self::SORTS[$sort]) ? $sort : 'date_desc';
        $p['page'] = max(1, (int) ($in['strona'] ?? 1));

        return $p;
    }

    /**
     * Buduje WHERE. `$skipCol` wyłącza warunek dla jednej kolumny — tak liczy się
     * licznik zależny („ile byłoby wyników, gdybym wybrał tę wartość").
     */
    private function buildWhere(array $p, ?string $skipCol = null, bool $skipFlags = false): array
    {
        global $wpdb;
        $sql  = ["status = 'publish'"];
        $args = [];

        foreach ($p['enum'] as $col => $vals) {
            if ($col === $skipCol) continue;
            $ph = implode(',', array_fill(0, count($vals), '%s'));
            $sql[] = "`$col` IN ($ph)";
            array_push($args, ...$vals);
        }
        foreach ($p['range'] as $col => $mm) {
            if ($col === $skipCol) continue;
            if (isset($mm['min'])) { $sql[] = "`$col` >= %f"; $args[] = $mm['min']; }
            if (isset($mm['max'])) { $sql[] = "`$col` <= %f"; $args[] = $mm['max']; }
        }
        if (!$skipFlags) {
            foreach ($p['flags'] as $f) $sql[] = "`$f` = 1";
        }

        $where = implode(' AND ', $sql);
        return [$args ? $wpdb->prepare($where, $args) : $where, $args];
    }

    // ================================================================= wyniki

    public function handleSearch(\WP_REST_Request $req): \WP_REST_Response
    {
        $p   = $this->parseParams($req->get_params());
        $res = $this->query($p);
        return new \WP_REST_Response([
            'total' => $res['total'],
            'page'  => $res['page'],
            'pages' => $res['pages'],
            'html'  => $res['html'],
        ], 200);
    }

    public function query(array $p): array
    {
        global $wpdb;
        $t = AsiaAuto_Specs_Table::table();
        [$where] = $this->buildWhere($p);

        $total  = (int) $wpdb->get_var("SELECT COUNT(*) FROM `$t` WHERE $where");
        $pages  = max(1, (int) ceil($total / self::PER_PAGE));
        $page   = min($p['page'], $pages);
        $offset = ($page - 1) * self::PER_PAGE;
        $order  = self::SORTS[$p['sort']];

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM `$t` WHERE $where ORDER BY $order LIMIT %d OFFSET %d",
            self::PER_PAGE, $offset
        ));

        $html = '';
        if ($ids) {
            // jedno zapytanie na wszystkie posty strony + rozgrzanie cache meta i termów
            $posts = get_posts([
                'post_type'        => 'listings',
                'post__in'         => array_map('intval', $ids),
                'orderby'          => 'post__in',
                'posts_per_page'   => count($ids),
                'suppress_filters' => false,
            ]);
            foreach ($posts as $post) {
                $html .= AsiaAuto_Inventory::renderCard($post);
            }
        }

        return ['total' => $total, 'page' => $page, 'pages' => $pages, 'html' => $html, 'ids' => $ids];
    }

    // =============================================================== liczniki

    public function handleCounts(\WP_REST_Request $req): \WP_REST_Response
    {
        $p   = $this->parseParams($req->get_params());
        $key = self::CACHE_SALT . md5(wp_json_encode($p));
        $hit = get_transient($key);
        if (is_array($hit)) return new \WP_REST_Response($hit + ['cached' => true], 200);

        $out = $this->counts($p);
        set_transient($key, $out, self::CACHE_TTL);
        return new \WP_REST_Response($out + ['cached' => false], 200);
    }

    public function counts(array $p): array
    {
        global $wpdb;
        $t   = AsiaAuto_Specs_Table::table();
        $out = ['total' => 0, 'enum' => [], 'flags' => []];

        [$whereAll] = $this->buildWhere($p);
        $out['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `$t` WHERE $whereAll");

        // enumy: jedno GROUP BY na kolumnę, z pominięciem filtra tej kolumny.
        // Etykiety lecą razem z licznikami, bo JS musi umieć dorysować opcję,
        // której nie było w SSR (typowo: modele po wybraniu marki).
        $out['labels'] = [];
        foreach (self::ENUM_PARAMS as $col) {
            [$where] = $this->buildWhere($p, $col);
            $rows = $wpdb->get_results(
                "SELECT `$col` AS v, COUNT(*) AS c FROM `$t` WHERE $where AND `$col` IS NOT NULL GROUP BY `$col`",
                ARRAY_A
            );
            $map = [];
            foreach ($rows as $r) $map[(string) $r['v']] = (int) $r['c'];
            $out['enum'][$col] = $map;

            $labels = $this->optionLabels($col);
            $out['labels'][$col] = $labels
                ? array_intersect_key($labels, $map)
                : array_combine(array_keys($map), array_keys($map));
        }

        // flagi: JEDNO zapytanie z 20 SUM-ami (nie 20 zapytań)
        [$whereF] = $this->buildWhere($p);
        $sums = [];
        foreach (array_keys(AsiaAuto_Specs_Table::FLAGS) as $f) $sums[] = "SUM(`$f`) AS `$f`";
        $row = $wpdb->get_row("SELECT " . implode(', ', $sums) . " FROM `$t` WHERE $whereF", ARRAY_A);
        foreach (array_keys(AsiaAuto_Specs_Table::FLAGS) as $f) $out['flags'][$f] = (int) ($row[$f] ?? 0);

        return $out;
    }

    /** Czyści cache liczników — wołane z AsiaAuto_Specs_Table::rebuildRow(). */
    public static function flushCache(): void
    {
        global $wpdb;
        $like = $wpdb->esc_like('_transient_' . self::CACHE_SALT) . '%';
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like));
        $like = $wpdb->esc_like('_transient_timeout_' . self::CACHE_SALT) . '%';
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like));
    }

    // ===================================================================== UI

    public function registerAssets(): void
    {
        // rejestracja bez enqueue — assety wchodzą dopiero w renderShortcode()
        $dir = ASIAAUTO_PLUGIN_URL . 'assets/';
        $ver = defined('ASIAAUTO_VERSION') ? ASIAAUTO_VERSION : '1';
        wp_register_style('asiaauto-search', $dir . 'css/asiaauto-search.css', [], $ver);
        wp_register_script('asiaauto-search', $dir . 'js/asiaauto-search.js', [], $ver, true);
    }

    public function renderShortcode(array $atts = []): string
    {
        wp_enqueue_style('asiaauto-inventory');   // ta sama karta oferty co /samochody/
        wp_enqueue_style('asiaauto-search');
        wp_enqueue_script('asiaauto-search');

        $p      = $this->parseParams($_GET);
        $res    = $this->query($p);
        $counts = $this->counts($p);
        $bounds = $this->bounds();

        wp_localize_script('asiaauto-search', 'AA_SEARCH', [
            'rest'     => esc_url_raw(rest_url('asiaauto/v1/')),
            'perPage'  => self::PER_PAGE,
            'base'     => esc_url_raw(get_permalink()),
            'enums'    => array_values(self::ENUM_PARAMS),
            'ranges'   => array_keys(self::RANGE_PARAMS),
            'flags'    => array_keys(AsiaAuto_Specs_Table::FLAGS),
        ]);

        ob_start();
        ?>
        <div class="aas" data-per-page="<?= self::PER_PAGE ?>">
            <form class="aas__form" method="get" action="<?= esc_url(get_permalink()) ?>">

                <button type="button" class="aas__drawer-open" aria-expanded="false" aria-controls="aas-panel">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6h18M6 12h12M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Filtry<span class="aas__badge" hidden>0</span>
                </button>

                <aside class="aas__panel" id="aas-panel">
                    <div class="aas__panel-head">
                        <h2 class="aas__panel-title">Filtry</h2>
                        <button type="button" class="aas__clear">Wyczyść</button>
                        <button type="button" class="aas__drawer-close" aria-label="Zamknij filtry">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                    </div>

                    <div class="aas__panel-body">
                        <?php
                        $this->renderEnumGroup('make', 'Marka', $p, $counts, true);
                        $this->renderEnumGroup('serie', 'Model', $p, $counts, true, !empty($p['enum']['make']));
                        $this->renderRangeGroup(['cena', 'rok', 'przebieg'], 'Cena, rocznik, przebieg', $p, $bounds, true);
                        $this->renderEnumGroup('fuel', 'Rodzaj napędu', $p, $counts, true);
                        $this->renderEnumGroup('body', 'Nadwozie', $p, $counts, true);
                        $this->renderRangeGroup(['moc', 'zasieg', 'bateria'], 'Osiągi i bateria', $p, $bounds);
                        $this->renderEnumGroup('drive', 'Napęd', $p, $counts);
                        $this->renderEnumGroup('transmission', 'Skrzynia biegów', $p, $counts);
                        $this->renderRangeGroup(['miejsca', 'felgi'], 'Miejsca i felgi', $p, $bounds);
                        $this->renderEnumGroup('upholstery', 'Tapicerka', $p, $counts);
                        $this->renderEnumGroup('sunroof', 'Szyberdach', $p, $counts);
                        $this->renderEnumGroup('color', 'Kolor', $p, $counts);
                        $this->renderFlagGroups($p, $counts);
                        ?>
                    </div>

                    <div class="aas__panel-foot">
                        <button type="submit" class="aas__apply">Pokaż <span class="aas__apply-count"><?= number_format($res['total'], 0, ',', ' ') ?></span> ofert</button>
                    </div>
                </aside>

                <div class="aas__results">
                    <div class="aas__toolbar">
                        <p class="aas__count" role="status" aria-live="polite">
                            Znaleziono <strong class="aas__count-num"><?= number_format($res['total'], 0, ',', ' ') ?></strong> ofert
                        </p>
                        <label class="aas__sort">
                            <span class="aas__sort-label">Sortuj:</span>
                            <select name="sort" class="aas__sort-select">
                                <?php foreach ([
                                    'date_desc'   => 'Najnowsze',
                                    'price_asc'   => 'Cena rosnąco',
                                    'price_desc'  => 'Cena malejąco',
                                    'mileage_asc' => 'Najmniejszy przebieg',
                                    'power_desc'  => 'Największa moc',
                                    'range_desc'  => 'Największy zasięg',
                                ] as $v => $label): ?>
                                    <option value="<?= esc_attr($v) ?>" <?= selected($p['sort'], $v, false) ?>><?= esc_html($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <!-- klasa `aa-inv` jest tu KONIECZNA: 15 reguł w asiaauto-inventory.css stylizuje
                         wnętrze karty selektorem `.aa-inv .aa-card__…` (przyciski akcji, tytuł).
                         Bez niej „Szczegóły/Zamów" renderują się jako gołe linki. Layout `.aa-inv`
                         (flex row) nadpisujemy w asiaauto-search.css przez `.aas__grid.aa-inv`. -->
                    <div class="aas__grid aa-inv" aria-busy="false"><?= $res['html'] ?></div>

                    <?php if ($res['total'] === 0): ?>
                        <p class="aas__empty">Żadna oferta nie spełnia tych kryteriów. Poluzuj filtry albo wyczyść je w całości.</p>
                    <?php endif; ?>

                    <?= $this->renderPagination($res['page'], $res['pages']) ?>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /** Grupa checkboxów dla kolumny enum, z licznikami zależnymi. */
    private function renderEnumGroup(string $col, string $title, array $p, array $counts,
                                     bool $open = false, bool $forceOpen = false): void
    {
        $param   = array_search($col, self::ENUM_PARAMS, true);
        $labels  = $this->optionLabels($col);
        $avail   = $counts['enum'][$col] ?? [];
        $chosen  = $p['enum'][$col] ?? [];

        // model pokazujemy dopiero po wyborze marki — 2 596 termów w jednej liście to nie filtr
        $hidden = ($col === 'serie' && !$forceOpen);

        $opts = [];
        foreach ($avail as $slug => $n) {
            if ($n <= 0 && !in_array($slug, $chosen, true)) continue;
            $opts[$slug] = ['label' => $labels[$slug] ?? $slug, 'n' => $n];
        }
        foreach ($chosen as $slug) {                       // wybrane zostają widoczne nawet przy 0
            if (!isset($opts[$slug])) $opts[$slug] = ['label' => $labels[$slug] ?? $slug, 'n' => 0];
        }
        if (!$opts && !$chosen) return;
        uasort($opts, static fn($a, $b) => $b['n'] <=> $a['n'] ?: strcmp($a['label'], $b['label']));

        $id = 'aas-g-' . $col;
        ?>
        <section class="aas__group<?= $hidden ? ' aas__group--hidden' : '' ?>" data-col="<?= esc_attr($col) ?>"<?= $hidden ? ' hidden' : '' ?>>
            <h3 class="aas__group-title">
                <button type="button" class="aas__group-toggle" aria-expanded="<?= $open || $chosen ? 'true' : 'false' ?>" aria-controls="<?= esc_attr($id) ?>">
                    <?= esc_html($title) ?>
                    <span class="aas__group-mark" aria-hidden="true"></span>
                </button>
            </h3>
            <div class="aas__opts" id="<?= esc_attr($id) ?>"<?= ($open || $chosen) ? '' : ' hidden' ?>>
                <?php foreach ($opts as $slug => $o): ?>
                    <label class="aas__opt<?= $o['n'] === 0 ? ' aas__opt--empty' : '' ?>">
                        <input type="checkbox" name="<?= esc_attr($param) ?>[]" value="<?= esc_attr($slug) ?>"
                               <?= in_array((string) $slug, array_map('strval', $chosen), true) ? 'checked' : '' ?>>
                        <span class="aas__opt-label"><?= esc_html($o['label']) ?></span>
                        <span class="aas__opt-count"><?= number_format($o['n'], 0, ',', ' ') ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    /** Grupa pól „od–do". */
    private function renderRangeGroup(array $keys, string $title, array $p, array $bounds, bool $open = false): void
    {
        $anySet = false;
        foreach ($keys as $k) {
            $col = self::RANGE_PARAMS[$k][0];
            if (isset($p['range'][$col])) { $anySet = true; break; }
        }
        $id = 'aas-r-' . sanitize_key(implode('-', $keys));
        ?>
        <section class="aas__group">
            <h3 class="aas__group-title">
                <button type="button" class="aas__group-toggle" aria-expanded="<?= $open || $anySet ? 'true' : 'false' ?>" aria-controls="<?= esc_attr($id) ?>">
                    <?= esc_html($title) ?>
                    <span class="aas__group-mark" aria-hidden="true"></span>
                </button>
            </h3>
            <div class="aas__ranges" id="<?= esc_attr($id) ?>"<?= ($open || $anySet) ? '' : ' hidden' ?>>
                <?php foreach ($keys as $k):
                    [$col, $type] = self::RANGE_PARAMS[$k];
                    [$label, $unit] = self::RANGE_LABELS[$k];
                    $b   = $bounds[$k] ?? ['min' => null, 'max' => null];
                    $cur = $p['range'][$col] ?? [];
                    $step = self::RANGE_LABELS[$k][2];
                ?>
                    <div class="aas__range">
                        <span class="aas__range-label"><?= esc_html($label) ?><?= $unit ? ' <span class="aas__unit">(' . esc_html($unit) . ')</span>' : '' ?></span>
                        <div class="aas__range-pair">
                            <label class="aas__range-field">
                                <span class="screen-reader-text"><?= esc_html($label) ?> od</span>
                                <input type="number" inputmode="numeric" name="<?= esc_attr($k) ?>_min"
                                       value="<?= isset($cur['min']) ? esc_attr($cur['min']) : '' ?>"
                                       placeholder="od <?= esc_attr($b['min'] ?? '') ?>" step="<?= esc_attr($step) ?>"
                                       min="<?= esc_attr($b['min'] ?? 0) ?>" max="<?= esc_attr($b['max'] ?? '') ?>">
                            </label>
                            <span class="aas__range-dash" aria-hidden="true">–</span>
                            <label class="aas__range-field">
                                <span class="screen-reader-text"><?= esc_html($label) ?> do</span>
                                <input type="number" inputmode="numeric" name="<?= esc_attr($k) ?>_max"
                                       value="<?= isset($cur['max']) ? esc_attr($cur['max']) : '' ?>"
                                       placeholder="do <?= esc_attr($b['max'] ?? '') ?>" step="<?= esc_attr($step) ?>"
                                       min="<?= esc_attr($b['min'] ?? 0) ?>" max="<?= esc_attr($b['max'] ?? '') ?>">
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    /** Wyposażenie — 20 flag w czterech grupach, z licznikami. */
    private function renderFlagGroups(array $p, array $counts): void
    {
        $chosen = $p['flags'];
        foreach (self::FLAG_GROUPS as $title => $flags) {
            $anySet = (bool) array_intersect(array_keys($flags), $chosen);
            $id = 'aas-f-' . sanitize_key($title);
            ?>
            <section class="aas__group">
                <h3 class="aas__group-title">
                    <button type="button" class="aas__group-toggle" aria-expanded="<?= $anySet ? 'true' : 'false' ?>" aria-controls="<?= esc_attr($id) ?>">
                        <?= esc_html($title) ?>
                        <span class="aas__group-mark" aria-hidden="true"></span>
                    </button>
                </h3>
                <div class="aas__opts" id="<?= esc_attr($id) ?>"<?= $anySet ? '' : ' hidden' ?>>
                    <?php foreach ($flags as $flag => $label):
                        $n = $counts['flags'][$flag] ?? 0; ?>
                        <label class="aas__opt<?= $n === 0 ? ' aas__opt--empty' : '' ?>">
                            <input type="checkbox" name="wyposazenie[]" value="<?= esc_attr($flag) ?>"
                                   <?= in_array($flag, $chosen, true) ? 'checked' : '' ?>>
                            <span class="aas__opt-label"><?= esc_html($label) ?></span>
                            <span class="aas__opt-count"><?= number_format($n, 0, ',', ' ') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php
        }
    }

    private function renderPagination(int $current, int $pages): string
    {
        if ($pages < 2) return '';
        $link = static function (int $page): string {
            $q = $_GET;
            $q['strona'] = $page;
            return esc_url(add_query_arg($q, get_permalink()));
        };
        $out = '<nav class="aas__pagination" aria-label="Strony wyników"><ul class="aas__pages">';
        if ($current > 1) {
            $out .= '<li><a class="aas__page aas__page--prev" href="' . $link($current - 1) . '" data-page="' . ($current - 1) . '" rel="prev">Poprzednia</a></li>';
        }
        $from = max(1, $current - 2);
        $to   = min($pages, $current + 2);
        if ($from > 1) $out .= '<li><a class="aas__page" href="' . $link(1) . '" data-page="1">1</a></li>' . ($from > 2 ? '<li class="aas__ellipsis" aria-hidden="true">…</li>' : '');
        for ($i = $from; $i <= $to; $i++) {
            $out .= $i === $current
                ? '<li><span class="aas__page aas__page--current" aria-current="page">' . $i . '</span></li>'
                : '<li><a class="aas__page" href="' . $link($i) . '" data-page="' . $i . '">' . $i . '</a></li>';
        }
        if ($to < $pages) $out .= ($to < $pages - 1 ? '<li class="aas__ellipsis" aria-hidden="true">…</li>' : '') . '<li><a class="aas__page" href="' . $link($pages) . '" data-page="' . $pages . '">' . $pages . '</a></li>';
        if ($current < $pages) {
            $out .= '<li><a class="aas__page aas__page--next" href="' . $link($current + 1) . '" data-page="' . ($current + 1) . '" rel="next">Następna</a></li>';
        }
        return $out . '</ul></nav>';
    }

    // ============================================================== słowniki UI

    /** Etykiety PL kolumn enum, których nie ma w taksonomiach. */
    const ENUM_LABELS = [
        'upholstery' => [
            'skora'    => 'Skóra naturalna',
            'ekoskora' => 'Ekoskóra',
            'mieszana' => 'Mieszana',
            'alcantara'=> 'Alcantara / nubuk',
            'tkanina'  => 'Tkanina',
        ],
        'sunroof' => [
            'panorama-otwierany' => 'Panoramiczny otwierany',
            'panorama-staly'     => 'Panoramiczny stały',
            'dzielony'           => 'Dzielony',
            'szklany-dach'       => 'Szklany dach',
            'zwykly'             => 'Zwykły szyberdach',
            'brak'               => 'Brak',
        ],
    ];

    /** Flagi pogrupowane do UI. */
    const FLAG_GROUPS = [
        'Fotele i kierownica' => [
            'seat_heat_f'    => 'Podgrzewane fotele przód',
            'seat_vent_f'    => 'Wentylowane fotele przód',
            'seat_massage_f' => 'Masaż foteli przód',
            'seat_memory'    => 'Pamięć ustawień fotela',
            'seat_heat_r'    => 'Podgrzewana kanapa',
            'wheel_heat'     => 'Podgrzewana kierownica',
        ],
        'Kamery i asystenci' => [
            'cam_360'         => 'Kamera 360°',
            'lidar'           => 'Lidar',
            'adaptive_cruise' => 'Tempomat adaptacyjny',
            'lane_center'     => 'Centrowanie na pasie',
            'auto_park'       => 'Automatyczne parkowanie',
            'sentinel'        => 'Tryb wartownika',
        ],
        'Ekrany i multimedia' => [
            'hud'             => 'Wyświetlacz HUD',
            'ar_hud'          => 'AR-HUD',
            'phone_mirror'    => 'Mirroring telefonu',
            'net_5g'          => 'Internet 5G',
            'wireless_charge' => 'Ładowanie indukcyjne',
        ],
        'Komfort i elektryka' => [
            'heat_pump' => 'Pompa ciepła',
            'air_susp'  => 'Zawieszenie pneumatyczne',
            'v2l'       => 'Zasilanie urządzeń (V2L)',
        ],
    ];

    /** Zakresy w UI: kolumna => [etykieta, jednostka, krok]. */
    const RANGE_LABELS = [
        'cena'     => ['Cena', 'zł', 1000],
        'przebieg' => ['Przebieg', 'km', 1000],
        'rok'      => ['Rocznik', '', 1],
        'moc'      => ['Moc', 'KM', 10],
        'zasieg'   => ['Zasięg CLTC', 'km', 10],
        'bateria'  => ['Bateria', 'kWh', 1],
        'miejsca'  => ['Liczba miejsc', '', 1],
        'felgi'    => ['Felgi', '"', 1],
    ];

    /** Etykiety wartości enum: z taksonomii (nazwy termów) albo ze słownika. */
    public function optionLabels(string $col): array
    {
        $tax = array_search($col, self::ENUM_PARAMS, true);
        $map = [
            'make' => 'make', 'serie' => 'serie', 'color' => 'exterior-color',
            'fuel' => 'fuel', 'body' => 'body', 'drive' => 'drive',
            'transmission' => 'transmission',
        ];
        if (isset($map[$col])) {
            $out = [];
            $terms = get_terms(['taxonomy' => $map[$col], 'hide_empty' => true]);
            if (!is_wp_error($terms)) foreach ($terms as $t) $out[$t->slug] = $t->name;
            return $out;
        }
        if ($col === 'year') return [];  // etykieta = sama wartość
        return self::ENUM_LABELS[$col] ?? [];
    }

    /** Min/max zakresów z tabeli — do placeholderów i walidacji suwaków. */
    public function bounds(): array
    {
        $cached = get_transient(self::CACHE_SALT . 'bounds');
        if (is_array($cached)) return $cached;

        global $wpdb;
        $t = AsiaAuto_Specs_Table::table();
        $sel = [];
        foreach (self::RANGE_PARAMS as $url => [$col]) {
            $sel[] = "MIN(`$col`) AS `{$url}_min`, MAX(`$col`) AS `{$url}_max`";
        }
        $row = $wpdb->get_row("SELECT " . implode(', ', $sel) . " FROM `$t` WHERE status='publish'", ARRAY_A) ?: [];
        $out = [];
        foreach (self::RANGE_PARAMS as $url => [$col, $type]) {
            $out[$url] = [
                'min' => $row[$url . '_min'] === null ? null : ($type === 'float' ? (float) $row[$url . '_min'] : (int) $row[$url . '_min']),
                'max' => $row[$url . '_max'] === null ? null : ($type === 'float' ? (float) $row[$url . '_max'] : (int) $row[$url . '_max']),
            ];
        }
        set_transient(self::CACHE_SALT . 'bounds', $out, self::CACHE_TTL);
        return $out;
    }
}
