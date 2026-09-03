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
        // ruch C (2026-09-03)
        'kolor_wnetrza' => 'interior_color',
        'zawieszenie'   => 'suspension',
        'audio'         => 'sound_brand',
        // liczba miejsc jako LISTA (Janek 03.09: „to będzie pewnie 4, 5, 6, 7"); zakres `miejsca_min/_max` zostaje w API
        'miejsca'       => 'seats',
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
        // ruch C; trzeci element = mnożnik z jednostki UI na jednostkę kolumny (m → mm)
        'dlugosc'     => ['length_mm', 'float', 1000],
        'dmc'         => ['gvw_kg', 'int'],
        'zasieg_calk' => ['range_total', 'int'],
    ];

    /**
     * Rodzaj oferty. „Do sprowadzenia" to brak wartości w `reservation`, nie wartość —
     * dlatego to nie jest zwykły enum i ma własną obsługę w buildWhere().
     * `reserved` (1 oferta) celowo zostaje tylko w „Wszystkie": jest zarezerwowana,
     * więc nie jest ani dostępna do importu, ani w drodze, ani na placu.
     */
    const OFERTA = [
        'do-sprowadzenia' => ['sql' => '`reservation` IS NULL',        'label' => 'Do sprowadzenia z Chin', 'opis' => 'ok. 8–10 tygodni'],
        'w-drodze'        => ['sql' => "`reservation` = 'in_transit'", 'label' => 'W drodze do Polski',     'opis' => 'odbiór za 2–4 tygodnie'],
        // „w Polsce", nie „w Rzeszowie": część aut on_lot stoi w Pabianicach/Warszawie (pomiar 02.09), makieta I zaakceptowana z tą etykietą
        'na-placu'        => ['sql' => "`reservation` = 'on_lot'",     'label' => 'Na placu w Polsce',      'opis' => 'do obejrzenia od ręki'],
    ];


    /**
     * Układ z makiety I (zaakceptowana 2026-09-03, `docs/makiety/gen-i.py`): wygląd Otomoto —
     * biały panel, etykieta nad polem, sekcje w kolejności Janka, wszystko widoczne od razu,
     * zwijane tylko „Więcej filtrów". Ostatnia sekcja to „Oferty": kafle do sprowadzenia /
     * w drodze / na placu z liczbami po filtrach. Jeden promień 6 px na wszystkim.
     *
     * `typ`: enum (lista rozwijana z checkboxami; `kropki` = kolorowe kropki przy opcjach) /
     * range (jedno pole `side` min|max albo para `both`) / flags (pastylki z listy `flagi`).
     */
    const SEKCJE = [
        ['id' => 'nadwozie', 'label' => 'Nadwozie', 'kol' => 7, 'pola' => [
            ['typ' => 'enum',  'col' => 'make',  'label' => 'Marka pojazdu', 'szukaj' => true],
            ['typ' => 'enum',  'col' => 'serie', 'label' => 'Model pojazdu', 'szukaj' => true, 'po_marce' => true],
            ['typ' => 'enum',  'col' => 'body',  'label' => 'Rodzaj nadwozia'],
            ['typ' => 'enum',  'col' => 'drive', 'label' => 'Napęd 4x4'],
            ['typ' => 'range', 'k' => 'dlugosc', 'side' => 'min'],
            ['typ' => 'range', 'k' => 'dmc',     'side' => 'max'],
            ['typ' => 'enum',  'col' => 'seats', 'label' => 'Liczba miejsc'],
        ]],
        ['id' => 'naped', 'label' => 'Napęd', 'kol' => 6, 'pola' => [
            ['typ' => 'enum',  'col' => 'fuel', 'label' => 'Silnik'],
            ['typ' => 'range', 'k' => 'moc',         'side' => 'min'],
            ['typ' => 'range', 'k' => 'przysp',      'side' => 'max'],
            ['typ' => 'range', 'k' => 'bateria',     'side' => 'min'],
            ['typ' => 'range', 'k' => 'zasieg_calk', 'side' => 'min'],
            ['typ' => 'range', 'k' => 'zasieg',      'side' => 'min'],
        ]],
        ['id' => 'styl', 'label' => 'Styl i komfort', 'kol' => 4, 'pola' => [
            ['typ' => 'enum', 'col' => 'color',          'label' => 'Kolor nadwozia', 'kropki' => true],
            ['typ' => 'enum', 'col' => 'interior_color', 'label' => 'Kolor wnętrza',  'kropki' => true],
            ['typ' => 'enum', 'col' => 'upholstery',     'label' => 'Materiał tapicerki'],
            ['typ' => 'enum', 'col' => 'suspension',     'label' => 'Zawieszenie'],
        ]],
        // wszystkie flagi w jednej sekcji + marka nagłośnienia; „Więcej filtrów" zlikwidowane (Janek 03.09):
        // skrzynia i felgi poza UI (parametry API zostają), cena/rocznik/przebieg tylko przez sortowanie
        ['id' => 'tech', 'label' => 'Wyposażenie i technologie', 'kol' => 4, 'pola' => [
            ['typ' => 'flags', 'flagi' => ['lidar', 'cam_360', 'seat_massage_f', 'seat_massage_r', 'seat_vent_f',
                                           'ar_hud', 'noa_city', 'sound_premium', 'screen_copilot', 'screen_rear',
                                           'roof_panorama', 'seat_heat_f', 'seat_heat_r', 'seat_memory', 'wheel_heat',
                                           'adaptive_cruise', 'lane_center', 'auto_park', 'sentinel', 'hud', 'phone_mirror',
                                           'net_5g', 'wireless_charge', 'heat_pump', 'air_susp', 'v2l',
                                           'rear_steer', 'zero_gravity', 'seat_speakers', 'dolby', 'fridge', 'gesture',
                                           'sign_recog', 'remote_start', 'mirror_heat', 'tow_hook']],
            ['typ' => 'enum',  'col' => 'sound_brand',  'label' => 'Marka nagłośnienia'],
        ]],
    ];

    /** Kropki kolorów — kopia `AsiaAuto_Inventory::$colorHex` (prywatna, plik zamrożony). */
    const COLOR_HEX = [
        'white' => '#FFFFFF', 'black' => '#1A1A1A', 'silver' => '#C0C0C0', 'dark-gray' => '#555555',
        'blue' => '#2563EB', 'red' => '#DC2626', 'brown' => '#8B4513', 'orange' => '#F97316',
        'yellow' => '#EAB308', 'green' => '#16A34A', 'purple' => '#7C3AED', 'champagne' => '#D4C5A9',
        'other' => '#999999', 'gray' => '#9CA3AF', 'grey' => '#9CA3AF', 'gold' => '#D4AF37',
        'beige' => '#D2B48C', 'pink' => '#EC4899',
    ];

    const SORT_LABELS = [
        'date_desc'   => 'Najnowsze',
        'price_asc'   => 'Najtańsze',
        'price_desc'  => 'Najdroższe',
        'mileage_asc' => 'Najmniejszy przebieg',
        'year_desc'   => 'Najnowszy rocznik',
        'power_desc'  => 'Największa moc',
        'range_desc'  => 'Największy zasięg',
    ];

    const SORTS = [
        'date_desc'    => 'published_at DESC, post_id DESC',
        'price_asc'    => 'price ASC, post_id DESC',
        'price_desc'   => 'price DESC, post_id DESC',
        'mileage_asc'  => 'mileage ASC, post_id DESC',
        'power_desc'   => 'power_km DESC, post_id DESC',
        'range_desc'   => 'range_cltc DESC, post_id DESC',
        'year_desc'    => 'year DESC, post_id DESC',
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
                // pola są tekstowe („100 000", „5,0") — spacje precz, przecinek na kropkę; mnożnik m → mm
                $surowe = str_replace([' ', "\u{a0}", ','], ['', '', '.'], (string) $in[$key]);
                $mult = self::RANGE_PARAMS[$url][2] ?? 1;
                $v = $type === 'float' ? (float) $surowe * $mult : (int) $surowe;
                if ($mult !== 1) $v = (int) round($v);
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
        // porównania alfabetyczne po polsku (Ł przed M, Ż na końcu) — bez tego strcoll sortuje po bajtach
        setlocale(LC_COLLATE, 'pl_PL.UTF-8', 'pl_PL.utf8', 'pl_PL', 'C.UTF-8');

        return $p;
    }

    /**
     * Buduje WHERE. `$skipCol` wyłącza warunek dla jednej kolumny — tak liczy się
     * licznik zależny („ile byłoby wyników, gdybym wybrał tę wartość").
     */
    private function buildWhere(array $p, ?string $skipCol = null, bool $skipFlags = false, ?string $skipFlag = null): array
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
            foreach ($p['flags'] as $f) if ($f !== $skipFlag) $sql[] = "`$f` = 1";
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
            // miniatury: jedno zapytanie na wszystkie załączniki strony zamiast 2 na kartę (52 → ~28 zapytań)
            $thumbs = [];
            foreach ($posts as $post) {
                $tid = (int) get_post_meta($post->ID, '_thumbnail_id', true);
                if ($tid) $thumbs[] = $tid;
            }
            if ($thumbs) _prime_post_caches($thumbs, false, true);
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

        // zakresy zależne: MIN/MAX każdej kolumny liczbowej bez własnego filtra — podpowiedź w polu
        // ma pokazywać zakres po zawężeniu („Moc od 156" przy PHEV), nie zakres całej bazy
        // kolumny BEZ własnego filtra mają identyczne WHERE = whereAll → jedno zapytanie na wszystkie;
        // osobne zapytanie tylko dla kolumn z aktywnym filtrem (pomiar 03.09: 12 zapytań → 1 + aktywne)
        $out['bounds'] = [];
        $wspolne = [];
        foreach (self::RANGE_PARAMS as $url => $def) {
            [$col, $type] = $def;
            if (isset($p['range'][$col])) {
                [$whereR] = $this->buildWhere($p, $col);
                $r = $wpdb->get_row("SELECT MIN(`$col`) mn, MAX(`$col`) mx FROM `$t` WHERE $whereR", ARRAY_A) ?: [];
                $out['bounds'][$url] = $this->rzutujZakres($r['mn'] ?? null, $r['mx'] ?? null, $type);
            } else {
                $wspolne[] = "MIN(`$col`) AS `{$url}_min`, MAX(`$col`) AS `{$url}_max`";
            }
        }
        if ($wspolne) {
            $r = $wpdb->get_row("SELECT " . implode(', ', $wspolne) . " FROM `$t` WHERE $whereAll", ARRAY_A) ?: [];
            foreach (self::RANGE_PARAMS as $url => $def) {
                if (isset($out['bounds'][$url])) continue;
                $out['bounds'][$url] = $this->rzutujZakres($r[$url . '_min'] ?? null, $r[$url . '_max'] ?? null, $def[1]);
            }
        }

        // 0 wyników: które filtry da się zdjąć, żeby coś wróciło (liczone TYLKO przy zerze, ≤ ~15 COUNT-ów)
        $out['blokady'] = [];
        if ($out['total'] === 0) {
            $sprawdz = static function (string $where) use ($wpdb, $t): bool {
                return (int) $wpdb->get_var("SELECT COUNT(*) FROM `$t` WHERE $where") > 0;
            };
            foreach (array_keys($p['enum']) as $col)  { [$w] = $this->buildWhere($p, $col);  if ($sprawdz($w)) $out['blokady'][] = 'enum:' . $col; }
            foreach (array_keys($p['range']) as $col) { [$w] = $this->buildWhere($p, $col);  if ($sprawdz($w)) $out['blokady'][] = 'range:' . $col; }
            foreach ($p['flags'] as $f)               { [$w] = $this->buildWhere($p, null, false, $f); if ($sprawdz($w)) $out['blokady'][] = 'flag:' . $f; }
            if (!empty($p['oferta']))                 { [$w] = $this->buildWhere($p, 'oferta'); if ($sprawdz($w)) $out['blokady'][] = 'oferta'; }
        }

        // flagi: JEDNO zapytanie z 20 SUM-ami (nie 20 zapytań)
        [$whereF] = $this->buildWhere($p);
        $sums = [];
        foreach (array_keys(AsiaAuto_Specs_Table::FLAGS) as $f) $sums[] = "SUM(`$f`) AS `$f`";
        $row = $wpdb->get_row("SELECT " . implode(', ', $sums) . " FROM `$t` WHERE $whereF", ARRAY_A);
        foreach (array_keys(AsiaAuto_Specs_Table::FLAGS) as $f) $out['flags'][$f] = (int) ($row[$f] ?? 0);

        return $out;
    }

    private function rzutujZakres($mn, $mx, string $type): array
    {
        $c = static fn($v) => $v === null ? null : ($type === 'float' ? (float) $v : (int) $v);
        return ['min' => $c($mn), 'max' => $c($mx)];
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
        $n      = $this->liczbaFiltrow($p);

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
        <form class="aas" method="get" action="<?= esc_url(get_permalink()) ?>">
            <div class="aas__panel">
                <?php foreach (self::SEKCJE as $sek) $this->renderSekcja($sek, $p, $counts, $counts['bounds'] ?? $bounds); ?>
                <?php $this->renderOferty($p, $counts, $res['total']); ?>
            </div>

            <?php /* telefon: przyklejony pasek z licznikiem, dopóki wyniki nie wjadą w widok (JS chowa) */ ?>
            <div class="aas__pasek-dol" hidden>
                <span class="aas__pasek-dol-t"><strong class="aas__total"><?= $this->fmtLiczba($res['total']) ?></strong> <span class="aas__total-slowo"><?= $this->slowoOfert($res['total']) ?></span></span>
                <?php /* „Pokaż wyniki" sugerowało zamknięcie panelu, a to jest przewinięcie do listy
                         (zgłoszenie Janka 03.09) — strzałka w dół i słowo „Wyniki" mówią, co się stanie. */ ?>
                <button type="button" class="aas__pokaz aas__pokaz--dol">Wyniki <span aria-hidden="true">↓</span></button>
            </div>
            <div class="aas__toolbar">
                <p class="aas__count" role="status" aria-live="polite">
                    <strong class="aas__total"><?= $this->fmtLiczba($res['total']) ?></strong>
                    <span class="aas__total-slowo"><?= $this->slowoOfert($res['total']) ?></span>
                    <span class="aas__count-word">spełnia kryteria</span>
                    <button type="button" class="aas__clear"<?= $n ? '' : ' hidden' ?>>Wyczyść filtry (<span class="aas__n-filtrow"><?= $n ?></span>)</button>
                </p>
                <label class="aas__sort">
                    <span class="aas__sort-label">Sortuj</span>
                    <select name="sort" class="aas__sort-select">
                        <?php foreach (self::SORT_LABELS as $v => $label): ?>
                            <option value="<?= esc_attr($v) ?>" <?= selected($p['sort'], $v, false) ?>><?= esc_html($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <h2 class="screen-reader-text">Wyniki wyszukiwania</h2>

            <div class="aas__grid aa-inv" aria-busy="false"><?= $res['html'] ?></div>

            <?php if ($res['total'] === 0): ?>
                <p class="aas__empty">Żadna oferta nie spełnia tych kryteriów. Poluzuj filtry albo wyczyść je w całości.</p>
            <?php endif; ?>

            <?= $this->renderPagination($res['page'], $res['pages']) ?>
        </form>
        <?php
        return ob_get_clean();
    }

    /** Ile filtrów jest aktywnych — do „Wyczyść (n)". */
    private function liczbaFiltrow(array $p): int
    {
        $n = count($p['flags']) + count($p['range']);
        foreach ($p['enum'] as $vals) $n += count($vals);
        if (!empty($p['oferta'])) $n++;
        return $n;
    }

    /** Ile filtrów jest aktywnych w jednej sekcji — do odznaki na „Więcej filtrów". */
    private function liczbaFiltrowSekcji(array $sek, array $p): int
    {
        $n = 0;
        foreach ($sek['pola'] as $pole) {
            if ($pole['typ'] === 'enum')  $n += count($p['enum'][$pole['col']] ?? []);
            if ($pole['typ'] === 'range') $n += count($p['range'][self::RANGE_PARAMS[$pole['k']][0]] ?? []);
            if ($pole['typ'] === 'flags') $n += count(array_intersect($p['flags'], $pole['flagi']));
        }
        return $n;
    }

    /** „1 ofertę / 3 oferty / 12 ofert" (biernik, jak po „Pokaż"). */
    private function slowoOfert(int $n): string
    {
        $n = abs($n);
        if ($n === 1) return 'ofertę';
        $d = $n % 10; $s = $n % 100;
        return ($d >= 2 && $d <= 4 && !($s >= 12 && $s <= 14)) ? 'oferty' : 'ofert';
    }

    private function renderSekcja(array $sek, array $p, array $counts, array $bounds): void
    {
        $zwijana = !empty($sek['zwijana']);
        $aktywne = $this->liczbaFiltrowSekcji($sek, $p);
        $id = 'aas-sek-' . $sek['id'];
        if ($zwijana): ?>
            <div class="aas__wiecej-pasek">
                <button type="button" class="aas__wiecej" aria-expanded="<?= $aktywne ? 'true' : 'false' ?>" aria-controls="<?= $id ?>">
                    <span class="aas__wiecej-caret" aria-hidden="true"></span>
                    Więcej filtrów<span class="aas__wiecej-n"<?= $aktywne ? '' : ' hidden' ?>>(<?= $aktywne ?>)</span>
                </button>
            </div>
        <?php endif; ?>
        <?php
        // telefon: sekcje zwijane (Janek 03.09) — otwarta „Nadwozie" i sekcje z aktywnymi filtrami;
        // desktop ignoruje klasę `is-zwinieta` (CSS tylko w @media ≤ 768 px)
        $otwarta = $sek['id'] === 'nadwozie' || $aktywne > 0;
        ?>
        <?php
        // T-252: sekcja z pastylkami wyposażenia na telefonie NIE zwija się, tylko otwiera panel
        // pełnoekranowy — jej nagłówek jest przyciskiem otwierającym. Wcześniej trzeba było
        // rozwinąć akordeon, żeby dostać się do jednego przycisku (dwa tapnięcia zamiast jednego).
        $panel = (bool) array_filter($sek['pola'], static fn($x) => $x['typ'] === 'flags');
        ?>
        <section class="aas__sek aas__sek--<?= esc_attr($sek['id']) ?><?= $otwarta ? '' : ' is-zwinieta' ?><?= $panel ? ' aas__sek--panel' : '' ?>" id="<?= $id ?>" aria-labelledby="<?= $id ?>-t"<?= $zwijana && !$aktywne ? ' hidden' : '' ?>>
            <h2 class="aas__sek-t" id="<?= $id ?>-t">
                <button type="button" class="aas__sek-btn" aria-expanded="<?= $otwarta ? 'true' : 'false' ?>" aria-controls="<?= $id ?>-b"<?= $panel ? ' data-panel="1"' : '' ?>>
                    <?= esc_html($sek['label']) ?><span class="aas__sek-n" data-sek="<?= esc_attr($sek['id']) ?>"<?= $aktywne ? '' : ' hidden' ?>><?= $aktywne ?></span>
                    <b class="aas__caret" aria-hidden="true"></b>
                </button>
            </h2>
            <div class="aas__sek-body" id="<?= $id ?>-b">
            <?php if ($panel): ?>
                <div class="aas__panel-top">
                    <p class="aas__panel-tyt" aria-hidden="true"><?= esc_html($sek['label']) ?></p>
                    <span class="aas__panel-ile" aria-hidden="true"<?= $aktywne ? '' : ' hidden' ?>><?= $aktywne ?></span>
                    <button type="button" class="aas__panel-x" aria-label="Zamknij: <?= esc_attr($sek['label']) ?>"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="aas__panel-szukaj">
                    <input type="text" placeholder="Szukaj wyposażenia..." autocomplete="off" aria-label="Szukaj w wyposażeniu">
                </div>
            <?php endif; ?>
            <div class="aas__siatka aas__siatka--<?= (int) $sek['kol'] ?>">
                <?php foreach ($sek['pola'] as $pole) {
                    switch ($pole['typ']) {
                        case 'enum':  $this->renderEnum($pole, $p, $counts); break;
                        case 'range': $this->renderRange($pole, $p, $bounds); break;
                        case 'flags': $this->renderFlags($pole, $p, $counts); break;
                    }
                } ?>
            </div>
            <?php if ($panel): ?>
                <div class="aas__panel-stopka">
                    <button type="button" class="aas__panel-ok">Pokaż wyniki</button>
                </div>
            <?php endif; ?>
            </div>
        </section>
        <?php
    }

    /**
     * Sekcja „Oferty" — ostatnia, bo pokazuje PODZIAŁ wyników po zastosowaniu filtrów:
     * ile z dopasowanych aut trzeba sprowadzić, ile jedzie, ile stoi na placu. Kafel działa jak
     * radio (klik = zawęż do tej grupy, drugi klik = wszystkie). Liczby z liczników zależnych.
     */
    private function renderOferty(array $p, array $counts, int $total): void
    {
        $liczby = $counts['oferta'] ?? [];
        ?>
        <section class="aas__sek aas__sek--oferty" aria-labelledby="aas-sek-oferty-t">
            <h2 class="aas__sek-t" id="aas-sek-oferty-t">Oferty</h2>
            <div class="aas__oferty" role="radiogroup" aria-label="Dostępność ofert">
                <?php foreach (self::OFERTA as $klucz => $def):
                    $n = (int) ($liczby[$klucz] ?? 0);
                    $wybrane = ($p['oferta'] ?? '') === $klucz; ?>
                    <label class="aas__kafel<?= $wybrane ? ' is-active' : '' ?><?= $n === 0 && !$wybrane ? ' is-empty' : '' ?>">
                        <input type="radio" name="oferta" value="<?= esc_attr($klucz) ?>" <?= checked($wybrane, true, false) ?><?= $n === 0 && !$wybrane ? ' disabled' : '' ?>>
                        <strong class="aas__kafel-n" data-oferta="<?= esc_attr($klucz) ?>"><?= $this->fmtLiczba($n) ?></strong>
                        <span class="aas__kafel-l"><?= esc_html($def['label']) ?></span>
                        <small class="aas__kafel-s"><?= esc_html($def['opis']) ?></small>
                    </label>
                <?php endforeach; ?>
                <input type="radio" name="oferta" value="" class="screen-reader-text" <?= checked(empty($p['oferta']), true, false) ?> aria-label="Wszystkie oferty">
                <button type="button" class="aas__pokaz">Pokaż <span class="aas__total"><?= $this->fmtLiczba($total) ?></span> <span class="aas__total-slowo"><?= $this->slowoOfert($total) ?></span> <span aria-hidden="true">↓</span></button>
            </div>
        </section>
        <?php
    }

    /** Skrót wyboru na przycisku listy: „BYD", „BYD +2" albo tekst pusty. */
    private function podsumuj(array $etykiety, array $wybrane, string $pusty): string
    {
        if (!$wybrane) return $pusty;
        $pierwsza = $etykiety[$wybrane[0]] ?? $wybrane[0];
        return count($wybrane) > 1 ? $pierwsza . ' +' . (count($wybrane) - 1) : $pierwsza;
    }

    /** Lista rozwijana z checkboxami i licznikami zależnymi; opcje z kropką koloru dla `kropki`. */
    private function renderEnum(array $pole, array $p, array $counts): void
    {
        $col    = $pole['col'];
        $param  = array_search($col, self::ENUM_PARAMS, true);
        $labels = $this->optionLabels($col);
        $avail  = $counts['enum'][$col] ?? [];
        $chosen = array_map('strval', $p['enum'][$col] ?? []);
        $bezMarki = !empty($pole['po_marce']) && empty($p['enum']['make']);
        $kropki = !empty($pole['kropki']);
        $id = 'aas-f-' . $col;

        $opts = [];
        // Reguła UX (03.09): opcja dająca 0 wyników jest WIDOCZNA, szara i niewybieralna — użytkownik
        // widzi, że istnieje, ale nie wchodzi w ślepą uliczkę. Wyjątek: marka i model (długie listy,
        // 2,6 tys. serii) — tam zera są ukryte, a model bez marki nie idzie do HTML w ogóle.
        $ukrywaj = in_array($col, ['make', 'serie'], true);
        if (!$bezMarki) {
            $zrodlo = $ukrywaj ? $avail : ($labels ? ($labels + $avail) : $avail);
            foreach ($zrodlo as $slug => $x) {
                $n = (int) ($avail[$slug] ?? 0);
                if ($n <= 0 && $ukrywaj && !in_array((string) $slug, $chosen, true)) continue;
                $opts[$slug] = ['label' => $labels[$slug] ?? $slug, 'n' => $n];
            }
            foreach ($chosen as $slug) {
                if (!isset($opts[$slug])) $opts[$slug] = ['label' => $labels[$slug] ?? $slug, 'n' => 0];
            }
            // Marka i model ALFABETYCZNIE (decyzja Janka 03.09): to jedyne pola, w których
            // użytkownik przychodzi ze znaną nazwą i skanuje listę — 58 marek i 2,6 tys. modeli
            // ułożonych po liczbie ofert zmusza do czytania wszystkiego. Krótkie listy (paliwo,
            // nadwozie, kolor) zostają po liczniku: tam liczba mówi, gdzie w ogóle jest oferta.
            if ($col === 'year') krsort($opts, SORT_NUMERIC);
            elseif ($col === 'seats') ksort($opts, SORT_NUMERIC);
            elseif ($col === 'make' || $col === 'serie') {
                uasort($opts, static fn($a, $b) => strcoll($a['label'], $b['label']));
            }
            else uasort($opts, static fn($a, $b) => $b['n'] <=> $a['n'] ?: strcmp($a['label'], $b['label']));
        }
        $tekst = $bezMarki ? 'Najpierw wybierz markę'
               : $this->podsumuj(array_map(static fn($o) => $o['label'], $opts), $chosen, 'Wszystkie');
        $kropka = ($kropki && $chosen) ? (self::COLOR_HEX[$chosen[0]] ?? '#999') : '';
        ?>
        <div class="aas__pole aas__pole--enum" data-col="<?= esc_attr($col) ?>" data-param="<?= esc_attr($param) ?>"<?= !empty($pole['po_marce']) ? ' data-po-marce="1"' : '' ?><?= $kropki ? ' data-kropki="1"' : '' ?><?= $ukrywaj ? ' data-ukrywaj="1"' : '' ?>>
            <p class="aas__label" id="<?= $id ?>-l"><?= esc_html($pole['label']) ?></p>
            <div class="aas__dd">
                <button type="button" class="aas__sel<?= $chosen ? ' is-active' : '' ?>" aria-expanded="false"
                        aria-controls="<?= $id ?>-p" aria-labelledby="<?= $id ?>-l <?= $id ?>-t"<?= $bezMarki ? ' disabled' : '' ?>>
                    <i class="aas__kropka"<?= $kropka ? ' style="background:' . esc_attr($kropka) . '"' : ' hidden' ?> aria-hidden="true"></i>
                    <span class="aas__sel-text" id="<?= $id ?>-t" data-pusty="Wszystkie" data-bez-marki="Najpierw wybierz markę"><?= esc_html($tekst) ?></span>
                    <b class="aas__caret" aria-hidden="true"></b>
                </button>
                <div class="aas__pop" id="<?= $id ?>-p" role="group" aria-labelledby="<?= $id ?>-l"
                     data-opcji="<?= count($opts) ?>" hidden>
                    <?php /* Nagłówek i stopka są widoczne WYŁĄCZNIE na telefonie (@media <=768px).
                             Bez nich arkusz od dołu nie mówi, co się wybiera ani jak wyjść — T-252. */ ?>
                    <i class="aas__pop-uchwyt" aria-hidden="true"></i>
                    <div class="aas__pop-top">
                        <p class="aas__pop-tyt" aria-hidden="true"><?= esc_html($pole['label']) ?></p>
                        <span class="aas__pop-ile" aria-hidden="true" hidden>0</span>
                        <button type="button" class="aas__pop-x" aria-label="Zamknij listę: <?= esc_attr($pole['label']) ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php if (!empty($pole['szukaj'])): ?>
                        <div class="aas__szukaj"><input type="text" placeholder="Szukaj..." autocomplete="off" aria-label="Szukaj w filtrze: <?= esc_attr($pole['label']) ?>"></div>
                    <?php endif; ?>
                    <div class="aas__opts">
                        <?php foreach ($opts as $slug => $o) $this->renderOpcja($param . '[]', (string) $slug, $o['label'], $o['n'],
                            in_array((string) $slug, $chosen, true), $kropki ? (self::COLOR_HEX[$slug] ?? '#999') : '', $ukrywaj); ?>
                    </div>
                    <div class="aas__pop-stopka">
                        <button type="button" class="aas__pop-ok">Pokaż wyniki</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /** Jedna opcja listy — ten sam markup buduje JS w `buildOption()`. */
    private function renderOpcja(string $name, string $value, string $label, int $n, bool $checked, string $kropka = '', bool $ukrywaj = false): void
    {
        $puste = $n === 0 && !$checked;
        ?>
        <label class="aas__opt<?= $puste ? ' is-empty' : '' ?>"<?= $puste && $ukrywaj ? ' hidden' : '' ?>>
            <input type="checkbox" name="<?= esc_attr($name) ?>" value="<?= esc_attr($value) ?>" <?= $checked ? 'checked' : '' ?><?= $puste ? ' disabled' : '' ?>>
            <span class="aas__check" aria-hidden="true"></span>
            <?php if ($kropka): ?><i class="aas__kropka" style="background:<?= esc_attr($kropka) ?>" aria-hidden="true"></i><?php endif; ?>
            <span class="aas__opt-label"><?= esc_html($label) ?></span>
            <span class="aas__opt-count"><?= $this->fmtLiczba($n) ?></span>
        </label>
        <?php
    }

    /** Pole liczbowe: jedna strona („Długość od", „DMC do") albo para (cena). Tekstowe, z jednostką w polu. */
    private function renderRange(array $pole, array $p, array $bounds): void
    {
        $k = $pole['k'];
        [$col, $typ] = self::RANGE_PARAMS[$k];
        $mult = self::RANGE_PARAMS[$k][2] ?? 1;
        [$label, $unit] = self::RANGE_LABELS[$k];
        $b   = $bounds[$k] ?? ['min' => null, 'max' => null];
        $cur = $p['range'][$col] ?? [];
        $g   = $k !== 'rok';
        $unitA = $unit === '"' ? 'cale' : $unit;
        $strony = $pole['side'] === 'both' ? ['min' => 'od', 'max' => 'do'] : [$pole['side'] => ($pole['side'] === 'min' ? 'od' : 'do')];
        $id = 'aas-r-' . sanitize_key($k);
        $pokaz = static fn($v) => $v === null ? '' : ($mult !== 1 ? number_format($v / $mult, 1, ',', ' ') : null);
        $aktywny = isset($cur['min']) || isset($cur['max']);
        ?>
        <div class="aas__pole aas__pole--range<?= $aktywny ? ' is-active' : '' ?>" data-range="<?= esc_attr($k) ?>" data-mult="<?= (int) $mult ?>" data-grupuj="<?= $g ? 1 : 0 ?>">
            <p class="aas__label" id="<?= $id ?>"><?= esc_html($label) ?><?php if ($pole['side'] !== 'both'): ?> <?= $pole['side'] === 'min' ? 'od' : 'do' ?><?php endif; ?></p>
            <div class="aas__para" role="group" aria-labelledby="<?= $id ?>">
                <?php foreach ($strony as $side => $slowo): ?>
                    <?php if ($side === 'max' && $pole['side'] === 'both'): ?><span class="aas__sep" aria-hidden="true">—</span><?php endif; ?>
                    <span class="aas__inp">
                        <input type="text" name="<?= esc_attr($k . '_' . $side) ?>" inputmode="<?= $typ === 'float' ? 'decimal' : 'numeric' ?>" autocomplete="off"
                               value="<?= isset($cur[$side]) ? esc_attr($pokaz($cur[$side]) ?? $this->fmtLiczba($cur[$side], $g)) : '' ?>"
                               placeholder="<?= esc_attr($pokaz($b[$side]) ?? $this->fmtLiczba($b[$side], $g)) ?>"
                               aria-label="<?= esc_attr($label . ' ' . $slowo . ($unitA ? " ($unitA)" : '')) ?>">
                        <?php if ($unit): ?><small aria-hidden="true"><?= esc_html($unit) ?></small><?php endif; ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <p class="aas__hint" role="status" hidden>Brak ofert w tym zakresie</p>
        </div>
        <?php
    }

    /** Pastylki wyposażenia z licznikami; opcjonalnie przycisk „Więcej filtrów" na końcu rzędu (dorysowuje renderSekcja). */
    private function renderFlags(array $pole, array $p, array $counts): void
    {
        // telefon: widać pierwsze 8 pastylek (+ zaznaczone), reszta po „Więcej wyposażenia"; desktop pokazuje wszystkie (CSS)
        $ukryte = max(0, count($pole['flagi']) - 8);

        // T-252: na telefonie wyposażenie idzie na pełny ekran z podziałem na grupy. Kolejność
        // w markupie zostaje ORYGINALNA (ręcznie ułożona, „wabiki" najpierw — tak działa desktop),
        // a grupowanie robi `order` we flexie, włączany wyłącznie w @media <=768px.
        $grupaFlagi = [];
        $indeks = 0;
        foreach (self::FLAG_GROUPS as $nazwa => $flagi) {
            foreach (array_keys($flagi) as $f) $grupaFlagi[$f] = $indeks;
            $indeks++;
        }
        ?>
        <div class="aas__pole aas__pole--flags">
            <div class="aas__chips" data-limit="8">
                <?php $g = 0; foreach (array_keys(self::FLAG_GROUPS) as $nazwaGrupy): ?>
                    <p class="aas__chips-grupa" data-grupa="<?= $g++ ?>" aria-hidden="true"><?= esc_html($nazwaGrupy) ?></p>
                <?php endforeach; ?>
                <?php foreach ($pole['flagi'] as $flaga):
                    $n = (int) ($counts['flags'][$flaga] ?? 0);
                    $on = in_array($flaga, $p['flags'], true); ?>
                    <label class="aas__chip<?= $on ? ' is-active' : '' ?><?= $n === 0 && !$on ? ' is-empty' : '' ?>"
                           data-grupa="<?= (int) ($grupaFlagi[$flaga] ?? 0) ?>">
                        <input type="checkbox" name="wyposazenie[]" value="<?= esc_attr($flaga) ?>" <?= $on ? 'checked' : '' ?><?= $n === 0 && !$on ? ' disabled' : '' ?>>
                        <span class="aas__chip-label"><?= esc_html($this->etykietaFlagi($flaga)) ?></span>
                        <span class="aas__chip-n"><?= $this->fmtLiczba($n) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($ukryte > 0): ?>
                <button type="button" class="aas__chips-wiecej" aria-expanded="false">Więcej wyposażenia <span class="aas__chips-wiecej-n">(+<?= $ukryte ?>)</span></button>
            <?php endif; ?>
        </div>
        <?php
    }

    private function etykietaFlagi(string $flaga): string
    {
        foreach (self::FLAG_GROUPS as $flagi) {
            if (isset($flagi[$flaga])) return $flagi[$flaga];
        }
        return $flaga;
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
        'suspension' => [
            'pneumatyczne' => 'Pneumatyczne',
            'adaptacyjne'  => 'Adaptacyjne (regulowane)',
        ],
        'sound_brand' => [
            'huawei-sound' => 'HUAWEI SOUND', 'dynaudio' => 'Dynaudio', 'devialet' => 'Devialet',
            'harman-kardon' => 'Harman/Kardon', 'sony' => 'Sony', 'infinity' => 'Infinity', 'meridian' => 'Meridian',
            'bose' => 'Bose', 'yamaha' => 'Yamaha', 'naim' => 'Naim', 'kef' => 'KEF', 'beats' => 'Beats',
            'pioneer' => 'Pioneer', 'bang-olufsen' => 'Bang & Olufsen', 'bowers-wilkins' => 'Bowers & Wilkins',
            'sennheiser' => 'Sennheiser', 'lexicon' => 'Lexicon', 'burmester' => 'Burmester', 'alpine' => 'Alpine',
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
            'seat_massage_r' => 'Masaż foteli tył',
        ],
        'Kamery i asystenci' => [
            'cam_360'         => 'Kamera 360°',
            'lidar'           => 'Lidar',
            'adaptive_cruise' => 'Tempomat adaptacyjny',
            'lane_center'     => 'Centrowanie na pasie',
            'auto_park'       => 'Automatyczne parkowanie',
            'sentinel'        => 'Tryb wartownika',
            'noa_city'        => 'Autopilot miejski (NOA)',
        ],
        'Ekrany i multimedia' => [
            'hud'             => 'Wyświetlacz HUD',
            'ar_hud'          => 'AR-HUD',
            'phone_mirror'    => 'Mirroring telefonu',
            'net_5g'          => 'Internet 5G',
            'wireless_charge' => 'Ładowanie indukcyjne',
            'sound_premium'   => 'Nagłośnienie premium',
            'screen_copilot'  => 'Ekran pasażera (przód)',
            'screen_rear'     => 'Ekran dla pasażerów z tyłu',
        ],
        'Komfort i elektryka' => [
            'heat_pump' => 'Pompa ciepła',
            'air_susp'  => 'Zawieszenie pneumatyczne',
            'v2l'       => 'Zasilanie urządzeń (V2L)',
            'roof_panorama' => 'Dach panoramiczny',
            'rear_steer'    => 'Tylna oś skrętna',
            'zero_gravity'  => 'Fotel zero gravity',
            'seat_speakers' => 'Głośniki w fotelach',
            'dolby'         => 'Dolby Atmos',
            'fridge'        => 'Lodówka',
            'gesture'       => 'Sterowanie gestami',
            'sign_recog'    => 'Rozpoznawanie znaków',
            'remote_start'  => 'Zdalny rozruch',
            'mirror_heat'   => 'Ogrzewanie lusterek',
            'tow_hook'      => 'Hak holowniczy',
        ],
    ];

    /** Zakresy w UI: kolumna => [etykieta, jednostka, krok]. */
    const RANGE_LABELS = [
        'cena'     => ['Cena', 'zł', 1000],
        'przebieg' => ['Przebieg', 'km', 1000],
        'rok'      => ['Rocznik', '', 1],
        'moc'      => ['Moc', 'KM', 10],
        'bateria'  => ['Bateria', 'kWh', 1],
        'miejsca'  => ['Liczba miejsc', '', 1],
        'felgi'    => ['Felgi', '"', 1],
        'przysp'   => ['Przyspieszenie 0–100', 's', 0.1],
        'dlugosc'     => ['Długość', 'm', 0.1],
        'dmc'         => ['DMC', 'kg', 100],
        'zasieg_calk' => ['Zasięg całkowity', 'km', 10],
        'zasieg'      => ['Zasięg na prądzie (CLTC)', 'km', 10],
    ];

    /** Etykiety wartości enum: z taksonomii (nazwy termów) albo ze słownika. */
    public function optionLabels(string $col): array
    {
        $tax = array_search($col, self::ENUM_PARAMS, true);
        $map = [
            'make' => 'make', 'serie' => 'serie', 'color' => 'exterior-color', 'interior_color' => 'interior-color',
            'fuel' => 'fuel', 'body' => 'body', 'drive' => 'drive',
            'transmission' => 'transmission',
        ];
        if (isset($map[$col])) {
            // etykiety termów w transiencie pod wspólną solą — flushCache() (po każdym rebuildRow, czyli
            // po imporcie) zdejmuje je razem z licznikami; bez tego 8 zapytań get_terms na każde counts()
            $key = self::CACHE_SALT . 'labels_' . $col;
            $hit = get_transient($key);
            if (is_array($hit)) return $hit;
            $out = [];
            $terms = get_terms(['taxonomy' => $map[$col], 'hide_empty' => true]);
            if (!is_wp_error($terms)) foreach ($terms as $t) $out[$t->slug] = $t->name;
            set_transient($key, $out, self::CACHE_TTL);
            return $out;
        }
        if ($col === 'year' || $col === 'seats') return [];  // etykieta = sama wartość
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
