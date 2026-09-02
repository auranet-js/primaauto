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
        'przysp'   => ['accel_s', 'float'],
    ];

    /**
     * Rodzaj oferty. „Do sprowadzenia" to brak wartości w `reservation`, nie wartość —
     * dlatego to nie jest zwykły enum i ma własną obsługę w buildWhere().
     * `reserved` (1 oferta) celowo zostaje tylko w „Wszystkie": jest zarezerwowana,
     * więc nie jest ani dostępna do importu, ani w drodze, ani na placu.
     */
    const OFERTA = [
        'do-sprowadzenia' => ['sql' => '`reservation` IS NULL',        'label' => 'Do sprowadzenia'],
        'w-drodze'        => ['sql' => "`reservation` = 'in_transit'", 'label' => 'W drodze do Polski'],
        'na-placu'        => ['sql' => "`reservation` = 'on_lot'",     'label' => 'Na placu w Rzeszowie'],
    ];


    /**
     * Pasek poziomy: każda pigułka otwiera popover z jednym filtrem albo z kilkoma
     * pokrewnymi. Kolejność wprost z tego, po czym ludzie zawężają najpierw
     * (marka → cena → rocznik), a nie z kolejności kolumn w tabeli.
     * `enum`/`range`/`flags` mówią, co popover renderuje w środku.
     */
    const CHIPS = [
        ['id' => 'make',     'label' => 'Marka',        'enum'  => ['make']],
        ['id' => 'serie',    'label' => 'Model',        'enum'  => ['serie'], 'po_marce' => true],
        ['id' => 'cena',     'label' => 'Cena',         'range' => ['cena']],
        ['id' => 'rocznik',  'label' => 'Rocznik',      'range' => ['rok']],
        ['id' => 'przebieg', 'label' => 'Przebieg',     'range' => ['przebieg']],
        ['id' => 'fuel',     'label' => 'Napęd',        'enum'  => ['fuel', 'drive', 'transmission']],
        ['id' => 'body',     'label' => 'Nadwozie',     'enum'  => ['body']],
        ['id' => 'osiagi',   'label' => 'Osiągi',       'range' => ['moc', 'przysp', 'zasieg', 'bateria']],
        ['id' => 'wyp',      'label' => 'Wyposażenie',  'flags' => true],
        ['id' => 'wiecej',   'label' => 'Więcej',       'enum'  => ['upholstery', 'sunroof', 'color'],
                                                        'range' => ['miejsca', 'felgi']],
    ];

    /** Etykiety grup w popoverach zbiorczych (gdzie sama pigułka nie wystarcza). */
    const CHIP_SUBLABELS = [
        'fuel' => 'Rodzaj napędu', 'drive' => 'Napęd', 'transmission' => 'Skrzynia biegów',
        'upholstery' => 'Tapicerka', 'sunroof' => 'Szyberdach', 'color' => 'Kolor',
        'make' => 'Marka', 'serie' => 'Model', 'body' => 'Nadwozie',
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

        $oferta = isset($in['oferta']) ? sanitize_key((string) $in['oferta']) : '';
        $p['oferta'] = isset(self::OFERTA[$oferta]) ? $oferta : '';

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

        if (!empty($p['oferta']) && $skipCol !== 'oferta') {
            $sql[] = self::OFERTA[$p['oferta']]['sql'];
        }

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

        // rodzaj oferty: liczniki liczone bez filtra rodzaju, ale z resztą filtrów —
        // przełącznik ma pokazywać, ile ofert czeka w każdym stanie przy bieżącym zawężeniu
        [$whereO] = $this->buildWhere($p, 'oferta');
        $wyb = [];
        foreach (self::OFERTA as $klucz => $def) {
            $wyb[] = "SUM(" . $def['sql'] . ") AS `$klucz`";
        }
        $rowO = $wpdb->get_row("SELECT COUNT(*) AS wszystkie, " . implode(', ', $wyb)
            . " FROM `$t` WHERE $whereO", ARRAY_A) ?: [];
        $out['oferta'] = ['' => (int) ($rowO['wszystkie'] ?? 0)];
        foreach (array_keys(self::OFERTA) as $klucz) {
            $out['oferta'][$klucz] = (int) ($rowO[$klucz] ?? 0);
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
            'rest'    => esc_url_raw(rest_url('asiaauto/v1/')),
            'perPage' => self::PER_PAGE,
            'base'    => esc_url_raw(get_permalink()),
            'enums'   => array_values(self::ENUM_PARAMS),
            'ranges'  => array_keys(self::RANGE_PARAMS),
            'flags'   => array_keys(AsiaAuto_Specs_Table::FLAGS),
        ]);

        ob_start();
        ?>
        <div class="aas" data-per-page="<?= self::PER_PAGE ?>">
            <form class="aas__form" method="get" action="<?= esc_url(get_permalink()) ?>">

                <?php $this->renderOferta($p, $counts); ?>

                <div class="aas__filtry">
                    <div class="aas__chips" role="group" aria-label="Filtry">
                        <?php foreach (self::CHIPS as $chip) $this->renderChip($chip, $p, $counts, $bounds); ?>
                    </div>
                    <button type="button" class="aas__clear"<?= $this->liczbaFiltrow($p) ? '' : ' hidden' ?>>
                        Wyczyść<span class="aas__clear-n"><?= $this->liczbaFiltrow($p) ?></span>
                    </button>
                </div>

                <?php /* Poziom h2 między h1 strony a h3 w kartach ofert. Bez niego hierarchia
                         przeskakuje (detektor impeccable: skipped-heading); /samochody/ ma
                         ten poziom, więc powtarzamy wzorzec zamiast ruszać renderCard(). */ ?>
                <h2 class="screen-reader-text">Wyniki wyszukiwania</h2>

                <div class="aas__toolbar">
                    <p class="aas__count" role="status" aria-live="polite">
                        <strong class="aas__count-num"><?= $this->fmtLiczba($res['total']) ?></strong>
                        <span class="aas__count-word">ofert</span>
                    </p>
                    <label class="aas__sort">
                        <span class="aas__sort-label">Sortuj</span>
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

                <div class="aas__grid aa-inv" aria-busy="false"><?= $res['html'] ?></div>

                <?php if ($res['total'] === 0): ?>
                    <p class="aas__empty">Żadna oferta nie spełnia tych kryteriów. Poluzuj filtry albo wyczyść je w całości.</p>
                <?php endif; ?>

                <?= $this->renderPagination($res['page'], $res['pages']) ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /** Ile filtrów jest aktywnych — do odznaczenia „Wyczyść". */
    private function liczbaFiltrow(array $p): int
    {
        $n = count($p['flags']) + count($p['range']);
        foreach ($p['enum'] as $vals) $n += count($vals);
        if (!empty($p['oferta'])) $n++;
        return $n;
    }

    /**
     * Rodzaj oferty — jedyne mocne miejsce na tej stronie. Niesie informację, której
     * nie da się wyczytać skądinąd: prawie wszystko trzeba sprowadzić z Chin, ale
     * kilkanaście aut stoi w Rzeszowie i można je obejrzeć jutro. Dlatego liczby
     * są przy etykietach, a nie schowane w liczniku wyników.
     */
    private function renderOferta(array $p, array $counts): void
    {
        $liczby = $counts['oferta'] ?? [];
        $opcje  = ['' => 'Wszystkie'];
        foreach (self::OFERTA as $klucz => $def) $opcje[$klucz] = $def['label'];
        ?>
        <div class="aas__oferta" role="radiogroup" aria-label="Rodzaj oferty">
            <?php foreach ($opcje as $wartosc => $label):
                $n = (int) ($liczby[$wartosc] ?? 0);
                $wybrane = ($p['oferta'] ?? '') === $wartosc; ?>
                <label class="aas__seg<?= $wybrane ? ' is-active' : '' ?><?= $n === 0 && !$wybrane ? ' is-empty' : '' ?>">
                    <input type="radio" name="oferta" value="<?= esc_attr($wartosc) ?>" <?= checked($wybrane, true, false) ?>>
                    <span class="aas__seg-label"><?= esc_html($label) ?></span>
                    <span class="aas__seg-n" data-oferta="<?= esc_attr($wartosc) ?>"><?= $this->fmtLiczba($n) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /** Pigułka + popover z zawartością filtra. */
    private function renderChip(array $chip, array $p, array $counts, array $bounds): void
    {
        $id      = 'aas-pop-' . $chip['id'];
        $wybrane = $this->podsumujChip($chip, $p, $counts);
        $ukryta  = !empty($chip['po_marce']) && empty($p['enum']['make']);
        ?>
        <div class="aas__chip<?= $wybrane ? ' is-active' : '' ?>" data-chip="<?= esc_attr($chip['id']) ?>"<?= $ukryta ? ' hidden' : '' ?>>
            <button type="button" class="aas__chip-btn" aria-expanded="false" aria-controls="<?= esc_attr($id) ?>">
                <span class="aas__chip-label"><?= esc_html($chip['label']) ?></span>
                <span class="aas__chip-val"><?= $wybrane ? esc_html($wybrane) : '' ?></span>
                <span class="aas__chip-caret" aria-hidden="true"></span>
            </button>
            <div class="aas__pop" id="<?= esc_attr($id) ?>" hidden>
                <?php
                $wiele = count($chip['enum'] ?? []) + count($chip['range'] ?? []) > 1;
                foreach ($chip['enum'] ?? [] as $col) {
                    $this->renderEnumBox($col, $p, $counts, $wiele ? (self::CHIP_SUBLABELS[$col] ?? '') : '');
                }
                foreach ($chip['range'] ?? [] as $k) {
                    $this->renderRangeBox($k, $p, $bounds);
                }
                if (!empty($chip['flags'])) $this->renderFlagsBox($p, $counts);
                ?>
            </div>
        </div>
        <?php
    }

    /** Skrót wyboru na pigułce: „BYD +2", „od 150 000", „3 wybrane". */
    private function podsumujChip(array $chip, array $p, array $counts): string
    {
        $czesci = [];
        foreach ($chip['enum'] ?? [] as $col) {
            $wyb = $p['enum'][$col] ?? [];
            if (!$wyb) continue;
            $etykiety = $this->optionLabels($col);
            $pierwsza = $etykiety[$wyb[0]] ?? $wyb[0];
            $czesci[] = count($wyb) > 1 ? $pierwsza . ' +' . (count($wyb) - 1) : $pierwsza;
        }
        foreach ($chip['range'] ?? [] as $k) {
            [$col] = self::RANGE_PARAMS[$k];
            $cur = $p['range'][$col] ?? [];
            if (!$cur) continue;
            $grupuj = $k !== 'rok';
            $od = isset($cur['min']) ? $this->fmtLiczba($cur['min'], $grupuj) : '';
            $do = isset($cur['max']) ? $this->fmtLiczba($cur['max'], $grupuj) : '';
            if ($od && $do)      $czesci[] = "$od–$do";
            elseif ($od)         $czesci[] = "od $od";
            else                 $czesci[] = "do $do";
        }
        if (!empty($chip['flags']) && $p['flags']) {
            $n = count($p['flags']);
            $czesci[] = $n === 1
                ? $this->etykietaFlagi($p['flags'][0])
                : $n . ' ' . ($n < 5 ? 'wybrane' : 'wybranych');
        }
        return implode(' · ', array_slice($czesci, 0, 2)) . (count($czesci) > 2 ? ' …' : '');
    }

    private function etykietaFlagi(string $flaga): string
    {
        foreach (self::FLAG_GROUPS as $flagi) {
            if (isset($flagi[$flaga])) return $flagi[$flaga];
        }
        return $flaga;
    }

    /** Lista opcji jednej kolumny enum, z licznikami zależnymi. */
    private function renderEnumBox(string $col, array $p, array $counts, string $naglowek = ''): void
    {
        $param  = array_search($col, self::ENUM_PARAMS, true);
        $labels = $this->optionLabels($col);
        $avail  = $counts['enum'][$col] ?? [];
        $chosen = array_map('strval', $p['enum'][$col] ?? []);

        $opts = [];
        foreach ($avail as $slug => $n) {
            if ($n <= 0 && !in_array((string) $slug, $chosen, true)) continue;
            $opts[$slug] = ['label' => $labels[$slug] ?? $slug, 'n' => $n];
        }
        foreach ($chosen as $slug) {
            if (!isset($opts[$slug])) $opts[$slug] = ['label' => $labels[$slug] ?? $slug, 'n' => 0];
        }
        if (!$opts) return;
        uasort($opts, static fn($a, $b) => $b['n'] <=> $a['n'] ?: strcmp($a['label'], $b['label']));
        ?>
        <?php $idOpisu = 'aas-lb-' . $col; ?>
        <div class="aas__box" data-col="<?= esc_attr($col) ?>"
             <?= $naglowek ? 'role="group" aria-labelledby="' . esc_attr($idOpisu) . '"' : '' ?>>
            <?php if ($naglowek): ?><p class="aas__box-title" id="<?= esc_attr($idOpisu) ?>"><?= esc_html($naglowek) ?></p><?php endif; ?>
            <div class="aas__opts">
                <?php foreach ($opts as $slug => $o): ?>
                    <label class="aas__opt<?= $o['n'] === 0 ? ' aas__opt--empty' : '' ?>">
                        <input type="checkbox" name="<?= esc_attr($param) ?>[]" value="<?= esc_attr($slug) ?>"
                               <?= in_array((string) $slug, $chosen, true) ? 'checked' : '' ?>>
                        <span class="aas__opt-label"><?= esc_html($o['label']) ?></span>
                        <span class="aas__opt-count"><?= $this->fmtLiczba($o['n']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /** Jedno pole „od–do". */
    private function renderRangeBox(string $k, array $p, array $bounds): void
    {
        [$col] = self::RANGE_PARAMS[$k];
        [$label, $unit] = self::RANGE_LABELS[$k];
        $b   = $bounds[$k] ?? ['min' => null, 'max' => null];
        $cur = $p['range'][$col] ?? [];
        $g   = $k !== 'rok';
        ?>
        <?php $idOpisu = 'aas-lb-' . sanitize_key($k); ?>
        <div class="aas__box aas__box--range" role="group" aria-labelledby="<?= esc_attr($idOpisu) ?>">
            <p class="aas__box-title" id="<?= esc_attr($idOpisu) ?>"><?= esc_html($label) ?><?php if ($unit): ?> <span class="aas__unit"><?= esc_html($unit) ?></span><?php endif; ?></p>
            <div class="aas__range-pair">
                <label class="aas__range-field">
                    <span class="screen-reader-text"><?= esc_html($label) ?> od</span>
                    <input type="number" inputmode="decimal" name="<?= esc_attr($k) ?>_min" step="any" min="0"
                           value="<?= isset($cur['min']) ? esc_attr($cur['min']) : '' ?>"
                           placeholder="od <?= esc_attr($this->fmtLiczba($b['min'], $g)) ?>">
                </label>
                <span class="aas__range-dash" aria-hidden="true">–</span>
                <label class="aas__range-field">
                    <span class="screen-reader-text"><?= esc_html($label) ?> do</span>
                    <input type="number" inputmode="decimal" name="<?= esc_attr($k) ?>_max" step="any" min="0"
                           value="<?= isset($cur['max']) ? esc_attr($cur['max']) : '' ?>"
                           placeholder="do <?= esc_attr($this->fmtLiczba($b['max'], $g)) ?>">
                </label>
            </div>
        </div>
        <?php
    }

    /** Wyposażenie — 20 flag w czterech grupach. */
    private function renderFlagsBox(array $p, array $counts): void
    {
        foreach (self::FLAG_GROUPS as $title => $flagi): ?>
            <?php $idOpisu = 'aas-lb-' . sanitize_key($title); ?>
            <div class="aas__box" role="group" aria-labelledby="<?= esc_attr($idOpisu) ?>">
                <p class="aas__box-title" id="<?= esc_attr($idOpisu) ?>"><?= esc_html($title) ?></p>
                <div class="aas__opts">
                    <?php foreach ($flagi as $flaga => $label):
                        $n = $counts['flags'][$flaga] ?? 0; ?>
                        <label class="aas__opt<?= $n === 0 ? ' aas__opt--empty' : '' ?>">
                            <input type="checkbox" name="wyposazenie[]" value="<?= esc_attr($flaga) ?>"
                                   <?= in_array($flaga, $p['flags'], true) ? 'checked' : '' ?>>
                            <span class="aas__opt-label"><?= esc_html($label) ?></span>
                            <span class="aas__opt-count"><?= $this->fmtLiczba($n) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach;
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
        'przysp'   => ['Przyspieszenie 0–100', 's', 0.1],
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

    /**
     * Liczba do placeholdera: spacja co trzy cyfry, przecinek dziesiętny.
     * `$grupuj=false` dla rocznika — „od 2 022" zamiast „od 2022" wygląda jak błąd.
     */
    private function fmtLiczba($n, bool $grupuj = true): string
    {
        if ($n === null || $n === '') return '';
        $dec = (is_float($n) && floor($n) != $n) ? 1 : 0;
        return number_format((float) $n, $dec, ',', $grupuj ? "\u{a0}" : '');
    }

    /** Min/max zakresów z tabeli — do placeholderów. */
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
