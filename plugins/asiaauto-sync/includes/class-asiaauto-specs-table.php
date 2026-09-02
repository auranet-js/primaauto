<?php
/**
 * AsiaAuto_Specs_Table — spłaszczona tabela parametrów ofert (wp7j_asiaauto_specs)
 * pod wyszukiwarkę zaawansowaną (T-116 etap 3).
 *
 * Odpowiada za: schemat tabeli, NORMALIZATORY wartości z `_asiaauto_extra_prep`
 * (czyste funkcje statyczne, testowalne bez bazy) oraz przebudowę wierszy.
 *
 * Nazwa świadomie `_Table`, żeby nie mylić się z istniejącą `AsiaAuto_Spec`
 * (generator tabeli technicznej huba modelu, read-only).
 *
 * Reguły normalizacji: docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md sekcja 2.
 * Zestaw pól: docs/roadmapa/T-116-etap3-wyszukiwarka.md sekcja 4.
 *
 * @since v0.35.0 (T-116 etap 3)
 */

defined('ABSPATH') || exit;

class AsiaAuto_Specs_Table
{
    const TABLE = 'asiaauto_specs';
    const SCHEMA_VERSION = 1;

    /**
     * Wartości znaczące NIE / OPCJA. Do flagi liczymy tylko standard.
     * Pusty string zmierzony 02.09 jako „funkcji nie ma" (air_suspension: 804 pustych
     * przy 808 `标配` = 27%, zgodne z pokryciem 28% ze spec danych).
     */
    const NEGATIVE = ['', '-', '--', '0', '选配', '选装', '不支持', '无', '否', 'null'];

    /**
     * 20 flag pierwszej wersji. `keys` = koalescencja (OR) w ustalonej kolejności,
     * `match` = dodatkowy warunek na wartości (bez niego wystarczy obecność ≠ NEGATIVE).
     */
    const FLAGS = [
        // fotele
        'seat_heat_f'     => ['keys' => ['front_seat_heating_1']],
        'seat_vent_f'     => ['keys' => ['front_seat_ventilation_1']],
        'seat_massage_f'  => ['keys' => ['front_seat_massage_1']],
        'seat_memory'     => ['keys' => ['front_seat_memory_1']],
        'seat_heat_r'     => ['keys' => ['rear_seat_heating']],
        'wheel_heat'      => ['keys' => ['steer_wheel_heating']],
        // kamery i ADAS
        'cam_360'         => ['keys' => ['panoramic_camera']],
        'lidar'           => ['keys' => ['laser_radar'], 'match' => '~[1-9]~'],
        // `定速巡航` to tempomat ZWYKŁY — sama obecność klucza nie wystarcza (98 ofert 02.09)
        'adaptive_cruise' => ['keys' => ['cruise_system', 'full_speed_adaptive_cruise', 'adaptive_cruise'],
                              'match' => '~自适应|标配~u'],
        'lane_center'     => ['keys' => ['lane_center']],
        'auto_park'       => ['keys' => ['auto_park']],
        'sentinel'        => ['keys' => ['sentinel_mode']],
        // wyświetlacze i multimedia
        'hud'             => ['keys' => ['header_display_system']],
        'ar_hud'          => ['keys' => ['ar_hud']],
        'phone_mirror'    => ['keys' => ['mobile_system_8', 'mobile_system_11', 'mobile_system_1']],
        'net_5g'          => ['keys' => ['data_network_2']],
        'wireless_charge' => ['keys' => ['mobile_wireless_charging']],
        // komfort i EV
        'heat_pump'       => ['keys' => ['heat_pump_management_system']],
        'air_susp'        => ['keys' => ['air_suspension']],
        'v2l'             => ['keys' => ['vtol_power_station']],
    ];

    /** Zakresy liczbowe z extra_prep (moc i cena/przebieg/rok idą z meta i taksonomii). */
    const RANGES = [
        'range_cltc'  => ['keys' => ['cltc_recharge_mileage', 'recharge_mileage'], 'cast' => 'int'],
        'battery_kwh' => ['keys' => ['battery_capacity'], 'cast' => 'float'],
        'seats'       => ['keys' => ['seat_count'], 'cast' => 'int'],
        'accel_s'     => ['keys' => ['acceleration_time'], 'cast' => 'float'],
    ];

    /** Scalenia slugów taksonomii (śmieci i duplikaty w bazie). */
    const TAX_ALIAS = [
        'transmission' => [
            '%e7%94%b5%e5%8a%a8%e8%bd%a6%e5%8d%95%e9%80%9f%e5%8f%98%e9%80%9f%e7%ae%b1' => 'single-speed',
            '1%e6%8c%a1dht' => 'dht',
            '8%e6%8c%a1%e8%87%aa%e5%8a%a8%e5%8f%98%e9%80%9f%e7%ae%b1%ef%bc%88at%ef%bc%89' => 'automatic',
        ],
        'fuel' => [
            '%e6%b1%bd%e6%b2%b948v%e8%bd%bb%e6%b7%b7%e7%b3%bb%e7%bb%9f' => 'hybrid',
        ],
    ];

    // ============================================================ normalizatory

    /**
     * Che168 zapisuje warianty wersji jako „A / B" (standard / z pakietem).
     * Bierzemy pierwszy człon = standard. Rozbijamy TYLKO po ` / ` (spacje po obu
     * stronach) — goły `/` jest częścią wartości (`皮/翻毛皮`, `245/45 R19`).
     */
    public static function firstVariant($v): string
    {
        $v = trim((string) $v);
        $parts = preg_split('~\s+/\s+~u', $v);
        return trim($parts[0]);
    }

    /** Czy wartość znaczy „ma to na standard". */
    public static function isPositive($v, ?string $match = null): bool
    {
        $v = self::firstVariant($v);
        if (in_array($v, self::NEGATIVE, true)) return false;
        if ($match !== null && !preg_match($match, $v)) return false;
        return true;
    }

    /** Flaga 0/1 z koalescencji kluczy. */
    public static function flag(array $e, array $spec): int
    {
        $match = $spec['match'] ?? null;
        foreach ($spec['keys'] as $k) {
            if (!array_key_exists($k, $e) || !is_scalar($e[$k])) continue;
            if (self::isPositive($e[$k], $match)) return 1;
        }
        return 0;
    }

    /** Pierwsza liczba z pierwszego wariantu; koalescencja kluczy w kolejności. */
    public static function num(array $e, array $keys): ?float
    {
        foreach ($keys as $k) {
            if (!array_key_exists($k, $e) || !is_scalar($e[$k])) continue;
            $v = self::firstVariant($e[$k]);
            if ($v === '' || in_array($v, self::NEGATIVE, true)) continue;
            if (preg_match('~-?\d+(\.\d+)?~', $v, $m)) {
                $f = (float) $m[0];
                if ($f > 0) return $f;
            }
        }
        return null;
    }

    /** Rozmiar felgi w calach z `rear_tire_size` („255/50 R20" → 20). Pokrycie 95,3%. */
    public static function rimIn(array $e): ?int
    {
        foreach (['rear_tire_size', 'front_tire_size'] as $k) {
            if (empty($e[$k]) || !is_scalar($e[$k])) continue;
            if (preg_match('~R\s?(\d{2})~i', self::firstVariant($e[$k]), $m)) return (int) $m[1];
        }
        return null;
    }

    /** Tapicerka: skora / ekoskora / mieszana / alcantara / tkanina. */
    public static function upholstery(array $e): ?string
    {
        if (empty($e['seat_material']) || !is_scalar($e['seat_material'])) return null;
        $v = self::firstVariant($e['seat_material']);
        if ($v === '' || in_array($v, self::NEGATIVE, true)) return null;
        if (preg_match('~Alcantara|麂皮~ui', $v))                 return 'alcantara';
        if (str_contains($v, '混搭') || str_contains($v, '+')
            || str_contains($v, '皮/翻毛') || str_contains($v, '皮织')) return 'mieszana';
        if (str_contains($v, '真皮'))                              return 'skora';
        if (str_contains($v, '仿皮') || str_contains($v, '皮质'))  return 'ekoskora';
        if (str_contains($v, '织物'))                              return 'tkanina';
        if (str_contains($v, '翻毛'))                              return 'alcantara';
        return null; // nieznane → log, nie zgadywanie
    }

    /** Szyberdach: panorama-otwierany / panorama-staly / dzielony / szklany-dach / zwykly / brak. */
    public static function sunroof(array $e): ?string
    {
        if (!array_key_exists('skylight_type', $e) || !is_scalar($e['skylight_type'])) return null;
        $v = self::firstVariant($e['skylight_type']);
        if ($v === '' || in_array($v, self::NEGATIVE, true)) return 'brak';
        if (str_contains($v, '分段式'))                            return 'dzielony';
        if (str_contains($v, '天幕'))                              return 'szklany-dach';
        if (str_contains($v, '全景')) {
            return str_contains($v, '不可开启') ? 'panorama-staly' : 'panorama-otwierany';
        }
        if (str_contains($v, '天窗'))                              return 'zwykly';
        return null;
    }

    /** Slug taksonomii po scaleniu aliasów. */
    public static function taxSlug(?string $slug, string $tax): ?string
    {
        if ($slug === null || $slug === '') return null;
        return self::TAX_ALIAS[$tax][$slug] ?? $slug;
    }

    /** Cały wiersz z surowców — czysta funkcja, bez bazy (testowalna). */
    public static function buildRow(array $e, array $meta, array $tax): array
    {
        $row = [
            // Rodzaj oferty. `stm_car_location` NIE jest wiarygodne (nie odświeża się po
            // przyjeździe do PL) — jedynym źródłem jest `_asiaauto_reservation_status`.
            // Brak wartości = auto do sprowadzenia z Chin (2 921 z 2 967 ofert, pomiar 02.09).
            'reservation'  => !empty($meta['reservation']) ? (string) $meta['reservation'] : null,
            'price'        => isset($meta['price']) ? (int) $meta['price'] : null,
            'mileage'      => isset($meta['mileage']) ? (int) $meta['mileage'] : null,
            'year'         => isset($tax['ca-year']) ? (int) $tax['ca-year'] : null,
            'power_km'     => !empty($meta['power_km']) ? (int) $meta['power_km'] : null,
            'rim_in'       => self::rimIn($e),
            'make'         => $tax['make'] ?? null,
            'serie'        => $tax['serie'] ?? null,
            'color'        => $tax['exterior-color'] ?? null,
            'fuel'         => self::taxSlug($tax['fuel'] ?? null, 'fuel'),
            'body'         => self::taxSlug($tax['body'] ?? null, 'body'),
            'drive'        => self::taxSlug($tax['drive'] ?? null, 'drive'),
            'transmission' => self::taxSlug($tax['transmission'] ?? null, 'transmission'),
            'upholstery'   => self::upholstery($e),
            'sunroof'      => self::sunroof($e),
        ];
        foreach (self::RANGES as $col => $spec) {
            $n = self::num($e, $spec['keys']);
            $row[$col] = $n === null ? null : ($spec['cast'] === 'int' ? (int) $n : round($n, 1));
        }
        foreach (self::FLAGS as $col => $spec) {
            $row[$col] = self::flag($e, $spec);
        }
        return $row;
    }

    // ================================================================== schemat

    public static function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    /** Kolumny enum/tekstowe (poza flagami i zakresami). */
    const ENUMS = ['make', 'serie', 'color', 'fuel', 'body', 'drive', 'transmission', 'upholstery', 'sunroof'];

    /**
     * Indeksy świadomie TYLKO na kolumnach zakresowych i enumach.
     * Na flagach TINYINT(1) o kardynalnosci 2 przy ~3,4 tys. wierszy optymalizator
     * i tak wybierze skan tabeli — indeks byłby martwym kosztem przy zapisie.
     * Jeśli pomiar czasu przekroczy bramkę 200 ms, dokładamy ALTER-em.
     */
    public static function schemaSql(): string
    {
        global $wpdb;
        $t = self::table();
        $collate = $wpdb->get_charset_collate();
        $flags = '';
        foreach (array_keys(self::FLAGS) as $f) {
            $flags .= "  `$f` tinyint(1) NOT NULL DEFAULT 0,\n";
        }
        return "CREATE TABLE `$t` (
  `post_id` bigint(20) unsigned NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT '',
  `source` varchar(12) NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `published_at` datetime DEFAULT NULL,
  `price` int(10) unsigned DEFAULT NULL,
  `mileage` int(10) unsigned DEFAULT NULL,
  `year` smallint(5) unsigned DEFAULT NULL,
  `power_km` smallint(5) unsigned DEFAULT NULL,
  `range_cltc` smallint(5) unsigned DEFAULT NULL,
  `battery_kwh` decimal(5,1) DEFAULT NULL,
  `accel_s` decimal(3,1) DEFAULT NULL,
  `reservation` varchar(12) DEFAULT NULL,
  `seats` tinyint(3) unsigned DEFAULT NULL,
  `rim_in` tinyint(3) unsigned DEFAULT NULL,
  `make` varchar(48) DEFAULT NULL,
  `serie` varchar(48) DEFAULT NULL,
  `color` varchar(24) DEFAULT NULL,
  `fuel` varchar(24) DEFAULT NULL,
  `body` varchar(24) DEFAULT NULL,
  `drive` varchar(24) DEFAULT NULL,
  `transmission` varchar(24) DEFAULT NULL,
  `upholstery` varchar(24) DEFAULT NULL,
  `sunroof` varchar(24) DEFAULT NULL,
$flags  PRIMARY KEY (`post_id`),
  KEY `status` (`status`),
  KEY `published_at` (`published_at`),
  KEY `price` (`price`),
  KEY `mileage` (`mileage`),
  KEY `year` (`year`),
  KEY `power_km` (`power_km`),
  KEY `range_cltc` (`range_cltc`),
  KEY `accel_s` (`accel_s`),
  KEY `reservation` (`reservation`),
  KEY `make` (`make`),
  KEY `serie` (`serie`),
  KEY `color` (`color`),
  KEY `fuel` (`fuel`),
  KEY `body` (`body`),
  KEY `drive` (`drive`),
  KEY `transmission` (`transmission`)
) $collate";
    }

    /** Tworzy/aktualizuje tabelę. Bezpieczne do wielokrotnego wywołania. */
    public static function install(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta(self::schemaSql());
        update_option('asiaauto_specs_schema', self::SCHEMA_VERSION, false);
    }

    public static function tableExists(): bool
    {
        global $wpdb;
        $t = self::table();
        return (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t));
    }

    // =============================================================== przebudowa

    /** Surowce jednej oferty: extra_prep + meta + slugi taksonomii. */
    public static function gather(int $post_id): array
    {
        $raw = get_post_meta($post_id, '_asiaauto_extra_prep', true);
        $e = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);
        if (!is_array($e)) $e = [];
        $tax = [];
        foreach (['make', 'serie', 'exterior-color', 'fuel', 'body', 'drive', 'transmission', 'ca-year'] as $t) {
            $slugs = wp_get_object_terms($post_id, $t, ['fields' => 'slugs']);
            $tax[$t] = (!is_wp_error($slugs) && $slugs) ? $slugs[0] : null;
        }
        $meta = [
            'price'    => get_post_meta($post_id, 'price', true),
            'mileage'  => get_post_meta($post_id, 'mileage', true),
            'power_km' => get_post_meta($post_id, '_asiaauto_horse_power', true),
            'reservation' => get_post_meta($post_id, '_asiaauto_reservation_status', true),
        ];
        return [$e, $meta, $tax];
    }

    /**
     * Przebudowa wiersza jednej oferty. Zwraca zbudowany wiersz albo null,
     * gdy post nie jest ofertą. Nie dotyka żadnych danych poza własną tabelą.
     */
    public static function rebuildRow(int $post_id): ?array
    {
        global $wpdb;
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'listings') return null;
        if (!self::tableExists()) return null;

        if (in_array($post->post_status, ['trash', 'auto-draft'], true)) {
            $wpdb->delete(self::table(), ['post_id' => $post_id], ['%d']);
            return null;
        }

        [$e, $meta, $tax] = self::gather($post_id);
        $row = self::buildRow($e, $meta, $tax);
        $row['post_id']    = $post_id;
        $row['status']     = $post->post_status;
        $row['source']     = (string) get_post_meta($post_id, '_asiaauto_source', true);
        $row['updated_at'] = current_time('mysql');
        // data publikacji w tabeli, żeby sortowanie „najnowsze" nie wymagało JOIN-a do wp_posts
        $row['published_at'] = $post->post_date;

        $wpdb->replace(self::table(), $row);
        if (class_exists('AsiaAuto_Search')) AsiaAuto_Search::flushCache();
        return $row;
    }

    /** Tylko kolumna status — do `transition_post_status`, bez czytania extra_prep. */
    public static function touchStatus(int $post_id, string $status): void
    {
        global $wpdb;
        if (!self::tableExists()) return;
        if (in_array($status, ['trash', 'auto-draft'], true)) {
            $wpdb->delete(self::table(), ['post_id' => $post_id], ['%d']);
            return;
        }
        $wpdb->update(self::table(), ['status' => $status], ['post_id' => $post_id], ['%s'], ['%d']);
    }

    /** ID ofert do przebudowy. `$since` = liczba godzin wstecz wg stempli wzbogacania. */
    public static function idsToRebuild(?int $sinceHours = null): array
    {
        global $wpdb;
        if ($sinceHours === null) {
            return array_map('intval', $wpdb->get_col(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_type='listings' AND post_status IN ('publish','draft','private')
                 ORDER BY ID"
            ));
        }
        $cut = gmdate('Y-m-d H:i:s', time() - $sinceHours * 3600);
        $stamps = ['_asiaauto_spec_inherited_at', '_asiaauto_spec_bank_at', '_asiaauto_spec_catalog_at',
                   '_asiaauto_spec_merged_at', '_asiaauto_fix_t116_at'];
        $in = implode(',', array_fill(0, count($stamps), '%s'));
        $sql = $wpdb->prepare(
            "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
             JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key IN ($in)
             WHERE p.post_type='listings' AND p.post_status IN ('publish','draft','private')
               AND (pm.meta_value >= %s OR p.post_modified_gmt >= %s)
             ORDER BY p.ID",
            array_merge($stamps, [$cut, $cut])
        );
        $ids = array_map('intval', $wpdb->get_col($sql));
        // plus oferty, których w tabeli jeszcze nie ma
        $t = self::table();
        $missing = array_map('intval', $wpdb->get_col(
            "SELECT p.ID FROM {$wpdb->posts} p
             LEFT JOIN $t s ON s.post_id = p.ID
             WHERE p.post_type='listings' AND p.post_status IN ('publish','draft','private')
               AND s.post_id IS NULL"
        ));
        // ...plus siatka bezpieczeństwa: wiersze, w których cena albo przebieg rozjechały się
        // z meta. Hook na `updated_post_meta` powinien to łapać na bieżąco, ale zapis meta
        // z surowego SQL-a albo z importu z wyłączonymi hookami go omija.
        $rozjazd = array_map('intval', $wpdb->get_col(
            "SELECT s.post_id FROM $t s
             JOIN {$wpdb->postmeta} pc ON pc.post_id = s.post_id AND pc.meta_key = 'price'
             LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = s.post_id AND pm.meta_key = 'mileage'
             WHERE s.status = 'publish'
               AND (s.price <> CAST(pc.meta_value AS SIGNED)
                    OR s.mileage <> CAST(pm.meta_value AS SIGNED))"
        ));
        return array_values(array_unique(array_merge($ids, $missing, $rozjazd)));
    }

    // ==================================================================== hooki

    /**
     * Wpięcie addytywne: tabela odświeża się sama po imporcie i po zmianie statusu.
     * Importer nie jest modyfikowany — `asiaauto_after_set_taxonomies` istnieje w nim
     * od dawna (class-asiaauto-importer.php:610) i tutaj jest tylko nasłuchiwany.
     */
    public static function boot(): void
    {
        add_action('asiaauto_after_set_taxonomies', [__CLASS__, 'onAfterImport'], 20, 1);
        add_action('transition_post_status', [__CLASS__, 'onTransition'], 20, 3);
        // Pipeline cenowy zapisuje `price` przez update_post_meta, BEZ wp_update_post —
        // ani `asiaauto_after_set_taxonomies`, ani `transition_post_status` się wtedy nie
        // odpalają i tabela zostaje ze starą ceną. Zmierzone 02.09: 14 ofert po przeliczeniu
        // kursu, filtr ceny odsiewał je po złej stronie progu.
        add_action('updated_post_meta', [__CLASS__, 'onMetaChange'], 20, 4);
        add_action('added_post_meta', [__CLASS__, 'onMetaChange'], 20, 4);
    }

    /** meta_key => kolumna. Tylko liczby wprost z meta; reszta idzie przez rebuildRow(). */
    const META_COLUMNS = [
        'price'                  => 'price',
        'mileage'                => 'mileage',
        '_asiaauto_horse_power'  => 'power_km',
    ];

    /** Meta tekstowe aktualizowane punktowo — status rezerwacji zmienia się bez wp_update_post. */
    const META_COLUMNS_TEXT = ['_asiaauto_reservation_status' => 'reservation'];

    /** @var bool czy w tym żądaniu trzeba jeszcze wyczyścić cache liczników */
    private static $flushPending = false;

    public static function onMetaChange($meta_id, $post_id, $meta_key, $meta_value): void
    {
        $tekstowe = isset(self::META_COLUMNS_TEXT[$meta_key]);
        if (!isset(self::META_COLUMNS[$meta_key]) && !$tekstowe) return;
        if (!self::tableExists()) return;
        if (get_post_type((int) $post_id) !== 'listings') return;

        global $wpdb;
        if ($tekstowe) {
            $col = self::META_COLUMNS_TEXT[$meta_key];
            $val = ($meta_value === '' || $meta_value === null) ? null : (string) $meta_value;
            $format = ['%s'];
        } else {
            $col = self::META_COLUMNS[$meta_key];
            $val = is_numeric($meta_value) ? (int) $meta_value : null;
            if ($val !== null && $val <= 0 && $col === 'power_km') $val = null;
            $format = [$val === null ? '%s' : '%d'];
        }

        $zmieniono = $wpdb->update(self::table(), [$col => $val], ['post_id' => (int) $post_id],
            $format, ['%d']);

        // Przy masowym przeliczaniu cen czyścimy cache RAZ na żądanie, nie raz na ofertę.
        if ($zmieniono && !self::$flushPending) {
            self::$flushPending = true;
            add_action('shutdown', static function () {
                if (class_exists('AsiaAuto_Search')) AsiaAuto_Search::flushCache();
            });
        }
    }

    public static function onAfterImport($post_id): void
    {
        self::rebuildRow((int) $post_id);
    }

    public static function onTransition($new_status, $old_status, $post): void
    {
        if (!$post instanceof WP_Post || $post->post_type !== 'listings') return;
        if ($new_status === $old_status) return;
        // publish/draft/private: pełna przebudowa (oferta mogła wejść po raz pierwszy),
        // trash/auto-draft: rebuildRow sam usuwa wiersz.
        if (in_array($new_status, ['trash', 'auto-draft'], true)) {
            self::touchStatus((int) $post->ID, $new_status);
            if (class_exists('AsiaAuto_Search')) AsiaAuto_Search::flushCache();
            return;
        }
        self::rebuildRow((int) $post->ID);
    }
}
