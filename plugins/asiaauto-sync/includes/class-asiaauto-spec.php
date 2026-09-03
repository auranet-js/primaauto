<?php
/**
 * AsiaAuto_Spec — generator tabeli technicznej huba modelu (taxonomy=serie)
 * z danych `_asiaauto_extra_prep`, z klastrowaniem WERSJI.
 *
 * Port logiki z diag/spec-hub-dryrun.php (Faza 1b, 2026-06-13).
 * Trzy wyjścia z jednej struktury buildSpec():
 *   - renderTable()    — tabela HTML (Dane wspólne + Wersje + podsumowanie)
 *   - renderJsonLd()   — schema Car (additionalProperty + vehicleConfiguration)
 *   - buildLlmsBlock() — markdown per-wersja dla /llms-full.txt
 *
 * Read-only względem bazy. Zero zapisu.
 *
 * @since v0.32.74 (spec-hub rework KROK 2 / T-157)
 */

defined('ABSPATH') || exit;

class AsiaAuto_Spec
{
    /** @var array<int,array|null> per-request cache buildSpec() */
    private static $cache = [];

    /** @var array{labels:array,units:array,values:array}|null */
    private static $tr = null;

    private static function translations(): array
    {
        if (self::$tr === null) {
            $t = require ASIAAUTO_PLUGIN_DIR . 'data/translations-extra-prep.php';
            self::$tr = [
                'labels' => $t['labels'] ?? [],
                'units'  => $t['units'] ?? [],
                'values' => $t['values'] ?? [],
            ];
        }
        return self::$tr;
    }

    // ---------------------------------------------------------------- helpers

    private static function tr_val($v): string
    {
        $VALUES = self::translations()['values'];
        $v = trim((string) $v);
        if ($v === '') return '';
        if (isset($VALUES[$v])) return $VALUES[$v];
        // Częściowe dopasowanie (najdłuższy klucz zawarty w wartości) — TYLKO dla kluczy z CJK.
        //
        // 2026-07-28: mapa ma 16 kluczy bez CJK, w tym jednoznakowe 'L'→'Rzędowy (L)',
        // 'V'→'Widlasty (V)', 'H'→'Bokser (H)', 'W'→'W'. Przy częściowym dopasowaniu trafiały
        // w KAŻDĄ wartość zawierającą tę literę, więc '800V' renderowało się jako układ
        // cylindrów, 'OLED' jako 'LED', 'HDMI' jako 'Bokser (H)', '6kW' jako 'W'.
        // Pomiar na 7501 unikatowych wartościach z extra_prep: 353 (4,7%) tłumaczyły się błędnie.
        // Klucze łacińskie akceptujemy wyłącznie przy dopasowaniu PEŁNYM (isset wyżej).
        $best = ''; $bestLen = 0;
        foreach ($VALUES as $cn => $pl) {
            if ($cn === '' || mb_strlen($cn) <= $bestLen) continue;
            if (!preg_match('/[\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}\x{ff00}-\x{ffef}]/u', (string) $cn)) continue;
            if (str_contains($v, $cn)) {
                $best = $pl; $bestLen = mb_strlen($cn);
            }
        }
        return $best !== '' ? $best : $v;
    }

    private static function ep_num(array $e, string $key)
    {
        if (!isset($e[$key])) return null;
        $v = $e[$key];
        if (is_numeric($v)) return (float) $v;
        if (preg_match('/[-+]?\d+(\.\d+)?/', (string) $v, $m)) return (float) $m[0];
        return null;
    }

    private static function km_from_power(array $e): ?int
    {
        // PHEV/EREV moc SYSTEMOWA "640(870Ps)" -> EV -> ICE
        foreach (['electric_max_power', 'energy_elect_max_power'] as $k) {
            if (!empty($e[$k]) && preg_match('/\((\d+)\s*Ps\)/i', $e[$k], $m)) return (int) $m[1];
        }
        $hp = self::ep_num($e, 'engine_max_horsepower');
        if ($hp) return (int) $hp;
        $ehp = self::ep_num($e, 'front_electric_max_horsepower');
        return $ehp ? (int) $ehp : null;
    }

    /**
     * Moc UKŁADU (KM) z extra_prep dla hybryd — bez fallbacku na silnik spalinowy.
     *
     * Publiczna, addytywna (T-116 domknięcie, 2026-09-03). `km_from_power()` zostaje bez zmian,
     * bo tabela huba celowo spada na `engine_max_horsepower` przy ICE. Tutaj chodzi o meta
     * `_asiaauto_horse_power` w PHEV/EREV, gdzie silnik spalinowy to ułamek mocy układu
     * (AITO M9: 160 KM silnika, 496 KM układu), a filtr mocy w wyszukiwarce czyta to meta wprost.
     *
     * Kolejność źródeł, od najbardziej jednoznacznego:
     *   1. electric_system_horsepower (Ps) / electric_system_power (kW) — 系统综合功率, moc łączna
     *   2. electric_max_power / energy_elect_max_power "160(218Ps)"      — dongchedi, moc układu
     *   3. electric_total_horsepower (Ps) / total_electric_power (kW)   — suma silników el.
     *      (dla EREV = układ; dla PHEV dolna granica, zawsze >= moc silnika)
     * Świadomie POMINIĘTE: `system_max_power` — w katalogu Autohome to „最大功率" z nagłówka,
     * dla BYD DM-i / Denza / VOYAH równe mocy SILNIKA (Denza D9: 115 kW = 156 KM silnika,
     * a electric_total_horsepower = 333). Klucze `engine_*` nigdy.
     *
     * @return array{0:int,1:string} [KM, użyty klucz]; [0, ''] gdy brak źródła mocy układu
     */
    public static function system_km_from_extra_prep(array $e): array
    {
        $ps = static function ($v): int {
            if (preg_match('/(\d+)\s*Ps/i', (string) $v, $m)) return (int) $m[1];
            return preg_match('/\d+/', (string) $v, $m) ? (int) $m[0] : 0;
        };
        $kw = static function ($v): int {
            return preg_match('/\d+(\.\d+)?/', (string) $v, $m) ? (int) round((float) $m[0] * 1.36) : 0;
        };
        $order = [
            'electric_system_horsepower' => $ps,
            'electric_system_power'      => $kw,
            'electric_max_power'         => $ps,
            'energy_elect_max_power'     => $ps,
            'electric_total_horsepower'  => $ps,
            'total_electric_power'       => $kw,
        ];
        foreach ($order as $key => $fn) {
            if (empty($e[$key])) continue;
            // electric_max_power / energy_elect_max_power: tylko forma "kW(Ps)" jest jednoznaczna
            if (in_array($key, ['electric_max_power', 'energy_elect_max_power'], true)
                && !preg_match('/\(\s*\d+\s*Ps\s*\)/i', (string) $e[$key])) continue;
            $km = $fn($e[$key]);
            if ($km > 0) return [$km, $key];
        }
        return [0, ''];
    }

    private static function drivetrain(string $compl, array $e): string
    {
        $c = strtoupper($compl);
        if (preg_match('/\b(4WD|AWD|4X4)\b/', $c)) return 'AWD';
        if (preg_match('/\bRWD\b/', $c)) return 'RWD';
        if (preg_match('/\b(FWD|2WD)\b/', $c)) return 'FWD';
        $d = $e['driver_form'] ?? '';
        if (str_contains($d, '四驱')) return 'AWD';
        if (str_contains($d, '后驱')) return 'RWD';
        if (str_contains($d, '前驱') || str_contains($d, '两驱')) return 'FWD';
        return '';
    }

    private static function version_label(string $compl): string
    {
        foreach (['Ultra', 'Max', 'Pro', 'Plus', 'Premium', 'GT', 'Standard'] as $tok) {
            if (stripos($compl, $tok) !== false) return $tok;
        }
        return '';
    }

    private static function fmt_range(array $arr, int $dec = 0): ?string
    {
        $arr = array_filter($arr, fn($x) => $x !== null && $x !== '');
        if (!$arr) return null;
        $min = min($arr); $max = max($arr);
        $f = function ($x) use ($dec) {
            $s = number_format($x, $dec, ',', ' ');
            if ($dec > 0) $s = rtrim(rtrim($s, '0'), ','); // strip TYLKO przy dziesiętnych
            return $s;
        };
        $eps = $dec > 0 ? 0.001 : 0.5;
        if (abs($max - $min) < $eps) return $f($min);
        return $f($min) . '–' . $f($max);
    }

    private static function mode_val(array $arr)
    {
        $arr = array_filter($arr, fn($x) => $x !== null && $x !== '');
        if (!$arr) return null;
        $c = array_count_values(array_map('strval', $arr));
        arsort($c);
        return array_key_first($c);
    }

    private static function has_cjk($s): bool
    {
        return (bool) preg_match('/[\x{4e00}-\x{9fff}]/u', (string) $s);
    }

    private static function tr_engine($s): string
    {
        $map = ['插电式混动' => 'PHEV', '增程式' => 'EREV', '油电混动' => 'HEV', '轻混' => 'MHEV', '混动' => 'hybryda', '纯电动' => 'EV', '马力' => ' KM', '涡轮增压' => 'turbo', '自然吸气' => 'wolnossący'];
        $s = strtr((string) $s, $map);
        $s = preg_replace('/\\\\u[0-9a-f]{4}/i', '', $s);     // literalne \uXXXX (double-escape bug)
        $s = preg_replace('/[\x{4e00}-\x{9fff}]+/u', '', $s); // resztki CJK (pole drugorzędne)
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    private static function get_listing_ids(int $term_id): array
    {
        return get_posts([
            'post_type'   => 'listings',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
            'tax_query'   => [['taxonomy' => 'serie', 'field' => 'term_id', 'terms' => $term_id]],
        ]);
    }

    // ---------------------------------------------------------------- buildSpec

    /**
     * Zbuduj pełną strukturę spec dla huba modelu.
     *
     * @return array|null null gdy brak listingów / brak danych extra_prep
     *   [
     *     'term_id','name','make','make_slug','count',
     *     'ptype' => 'EV'|'PHEV'|'ICE',
     *     'cars'     => [ [...per-listing...], ],
     *     'common'   => [ label => string ],   // gotowe do tabeli
     *     'versions' => [ label => [ car, ... ] ],
     *     'summary'  => [ 'range'=>?,'power'=>?,'accel'=>? ],
     *   ]
     */
    public static function buildSpec(int $term_id): ?array
    {
        if (array_key_exists($term_id, self::$cache)) {
            return self::$cache[$term_id];
        }

        $term = get_term($term_id, 'serie');
        if (!$term instanceof WP_Term) {
            return self::$cache[$term_id] = null;
        }

        // marka: najpierw meta primary_make_slug, fallback parent term
        $makeSlug = (string) get_term_meta($term_id, '_asiaauto_primary_make_slug', true);
        $makeTerm = $makeSlug ? get_term_by('slug', $makeSlug, 'make') : null;
        if (!$makeTerm instanceof WP_Term && (int) $term->parent > 0) {
            $makeTerm = get_term((int) $term->parent, 'make');
            if ($makeTerm instanceof WP_Term && $makeSlug === '') $makeSlug = $makeTerm->slug;
        }
        $makeName = $makeTerm instanceof WP_Term ? $makeTerm->name : '';

        $ids = self::get_listing_ids($term_id);

        $cars = [];
        foreach ($ids as $pid) {
            $raw = get_post_meta($pid, '_asiaauto_extra_prep', true);
            $e = is_string($raw) ? json_decode($raw, true) : $raw;
            if (!is_array($e)) continue;
            $compl = (string) get_post_meta($pid, '_asiaauto_complectation', true);
            $bodyTerms = get_the_terms($pid, 'body');
            $bodyPl = (is_array($bodyTerms) && $bodyTerms) ? $bodyTerms[0]->name : '';
            $l = self::ep_num($e, 'length'); $w = self::ep_num($e, 'width'); $h = self::ep_num($e, 'height');
            $lwh = $e['length_width_height'] ?? '';
            if ((!$l || !$w || !$h) && $lwh && preg_match('/(\d+)\D+(\d+)\D+(\d+)/', $lwh, $mm)) {
                $l = $l ?: (float) $mm[1]; $w = $w ?: (float) $mm[2]; $h = $h ?: (float) $mm[3];
            }
            if (!$lwh && $l && $w && $h) $lwh = "{$l}x{$w}x{$h}";
            $cars[] = [
                'compl'     => $compl,
                'dt'        => self::drivetrain($compl, $e),
                'batt'      => (function ($b) { return ($b !== null && $b < 5) ? null : $b; })(self::ep_num($e, 'battery_capacity')), // <5 kWh = MHEV/48V -> nie EV
                'range_ev'  => self::ep_num($e, 'cltc_recharge_mileage') ?: self::ep_num($e, 'wltc_recharge_mileage') ?: self::ep_num($e, 'recharge_mileage'),
                'range_tot' => self::ep_num($e, 'combined_cruising_range_cltc'),
                'power'     => self::km_from_power($e),
                'torque'    => self::ep_num($e, 'energy_elect_max_torque') ?: self::ep_num($e, 'engine_max_torque'),
                'accel'     => self::ep_num($e, 'acceleration_time'),
                'vmax'      => self::ep_num($e, 'max_speed'),
                'fuel_c'    => self::ep_num($e, 'fuel_comprehensive') ?: self::ep_num($e, 'wltc_fuel_comprehensive') ?: self::ep_num($e, 'nedc_fuel_comprehensive'),
                'engine'    => self::tr_engine($e['engine_description'] ?? ''),
                'disp'      => self::ep_num($e, 'capacity_l'),
                'gearbox'   => self::tr_val($e['gearbox_description'] ?? ''),
                // common
                'lwh'       => $lwh,
                'len'       => $l,
                'wid'       => $w,
                'hei'       => $h,
                'wb'        => self::ep_num($e, 'wheelbase'),
                'curb'      => self::ep_num($e, 'curb_weight'),
                'gross'     => self::ep_num($e, 'full_load_weight') ?: self::ep_num($e, 'gross_weight') ?: self::ep_num($e, 'total_mass'),
                'seats'     => self::ep_num($e, 'seat_count'),
                'doors'     => self::ep_num($e, 'door_nums'),
                'boot'      => self::ep_num($e, 'baggage_volume'),
                'body'      => $bodyPl ?: self::tr_val($e['car_body_struct'] ?? ($e['body_struct'] ?? '')),
                'fuel_form' => $e['fuel_form'] ?? '',
            ];
        }
        if (!$cars) {
            return self::$cache[$term_id] = null;
        }

        // typ napędu (model-level) — po odfiltrowaniu MHEV
        $hasBattM  = (bool) array_filter(array_column($cars, 'batt'));
        $hasFuelCM = (bool) array_filter(array_column($cars, 'fuel_c'));
        $isEV   = $hasBattM && !$hasFuelCM;
        $isPHEV = $hasBattM && $hasFuelCM;
        $ptype  = !$hasBattM ? 'ICE' : ($hasFuelCM ? 'PHEV' : 'EV');

        // klaster wersji: bateria (EV/PHEV) lub silnik (ICE), zawsze + napęd
        $clusters = [];
        foreach ($cars as $c) {
            if ($c['batt']) $sig = round($c['batt'] / 5) * 5 . '|' . $c['dt'];
            else            $sig = 'ice' . (round(($c['power'] ?? 0) / 50) * 50) . '|' . $c['dt'];
            $clusters[$sig][] = $c;
        }
        uasort($clusters, fn($a, $b) => count($b) <=> count($a));
        // cap: max 4 wersje + reszta do „Inne warianty"
        if (count($clusters) > 4) {
            $keep = array_slice($clusters, 0, 4, true);
            $rest = array_slice($clusters, 4, null, true);
            $merged = [];
            foreach ($rest as $list) foreach ($list as $c) $merged[] = $c;
            $keep['__inne__'] = $merged;
            $clusters = $keep;
        }

        // ---- common block (model-constant) ----
        // Jednostka W ETYKIECIE, wartość bez jednostki (DFS: longtail „{model} wymiary/długość/waga").
        // Wymiary rozbite na osobne wiersze (długość/szerokość/wysokość) + combined w schema/summary.
        $common = [
            'Typ nadwozia'        => (string) (self::mode_val(array_column($cars, 'body')) ?: '—'),
            'Długość (mm)'        => self::fmt_range(array_column($cars, 'len')) ?? '—',
            'Szerokość (mm)'      => self::fmt_range(array_column($cars, 'wid')) ?? '—',
            'Wysokość (mm)'       => self::fmt_range(array_column($cars, 'hei')) ?? '—',
            'Rozstaw osi (mm)'    => self::fmt_range(array_column($cars, 'wb')) ?? '—',
            'Masa własna (kg)'    => self::fmt_range(array_column($cars, 'curb')) ?? '—',
            'Masa całkowita (kg)' => self::fmt_range(array_column($cars, 'gross')) ?? '—',
            'Liczba miejsc'       => self::fmt_range(array_column($cars, 'seats')) ?? '—',
            'Liczba drzwi'        => self::fmt_range(array_column($cars, 'doors')) ?? '—',
            'Pojemność bagażnika (l)' => self::fmt_range(array_column($cars, 'boot')) ?? '—',
        ];
        // wiersze całkiem puste („—") wytnij — nie zaśmiecaj tabeli
        $common = array_filter($common, fn($v) => $v !== '—' && $v !== '');

        // ---- inteligentne etykiety: po atrybucie, który RÓŻNICUJE wersje ----
        $reps = [];
        foreach ($clusters as $sig => $list) {
            $reps[$sig] = [
                'trim' => self::version_label((string) self::mode_val(array_column($list, 'compl'))),
                'dt'   => self::mode_val(array_column($list, 'dt')) ?: '',
                'batt' => self::mode_val(array_column($list, 'batt')),
                'rev'  => self::mode_val(array_column($list, 'range_ev')),
                'pow'  => self::mode_val(array_column($list, 'power')),
                'disp' => self::mode_val(array_column($list, 'disp')),
            ];
        }
        $distinct = fn($k) => count(array_unique(array_filter(array_map(fn($r) => (string) $r[$k], $reps), fn($x) => $x !== '')));
        $trimDistinct = ($distinct('trim') === count($reps)) && !in_array('', array_column($reps, 'trim'), true);
        $battVar = $distinct('batt') > 1;
        $revVar  = $distinct('rev')  > 1;
        $powVar  = $distinct('pow')  > 1;

        $versions = [];
        foreach ($clusters as $sig => $list) {
            if ($sig === '__inne__') { $versions['Inne warianty'] = $list; continue; }
            $r = $reps[$sig];
            if ($trimDistinct) {
                $label = $r['trim'];
            } else {
                $parts = [];
                if ($r['trim'] !== '') $parts[] = $r['trim'];
                // główny różnicownik
                if ($isPHEV && $revVar && $r['rev'])         $parts[] = round($r['rev']) . ' km';
                elseif ($isEV && $battVar && $r['batt'])     $parts[] = rtrim(rtrim(number_format($r['batt'], 1, ',', ''), '0'), ',') . ' kWh';
                elseif ($isEV && $revVar && $r['rev'])       $parts[] = round($r['rev']) . ' km';
                elseif (!$hasBattM && $r['disp'])            { $parts[] = rtrim(rtrim((string) $r['disp'], '0'), '.') . 'T' . ($r['pow'] ? " {$r['pow']} KM" : ''); }
                elseif ($powVar && $r['pow'])                $parts[] = $r['pow'] . ' KM';
                // jednowersyjne / brak różnicownika -> oznacz kluczowym spec (nie napędem)
                if (!$parts) {
                    if (($isPHEV || $isEV) && $r['rev']) $parts[] = round($r['rev']) . ' km';
                    elseif ($r['batt'])                  $parts[] = rtrim(rtrim(number_format($r['batt'], 1, ',', ''), '0'), ',') . ' kWh';
                    elseif (!$hasBattM && $r['disp'])    $parts[] = rtrim(rtrim((string) $r['disp'], '0'), '.') . 'T' . ($r['pow'] ? " {$r['pow']} KM" : '');
                    elseif ($r['dt'])                    $parts[] = $r['dt'];
                }
                // dodaj napęd dla rozróżnienia gdy AWD vs nie i to różnicuje
                if ($r['dt'] === 'AWD' && !in_array('AWD', $parts, true)) $parts[] = 'AWD';
                $label = implode(' ', $parts) ?: ($r['dt'] ?: 'Wariant');
            }
            $base = $label; $i = 2;
            while (isset($versions[$label])) { $label = $base . ' (' . $i . ')'; $i++; }
            $versions[$label] = $list;
        }

        $spec = [
            'term_id'   => $term_id,
            'name'      => trim(($makeName ? $makeName . ' ' : '') . $term->name),
            'make'      => $makeName,
            'make_slug' => $makeSlug,
            'count'     => count($ids),
            'ptype'     => $ptype,
            'cars'      => $cars,
            'common'    => $common,
            'versions'  => $versions,
            'summary'   => [
                'lwh'   => self::mode_val(array_filter(array_column($cars, 'lwh'))),
                'curb'  => self::fmt_range(array_column($cars, 'curb')),
                'range' => self::fmt_range(array_column($cars, 'range_ev')),
                'power' => self::fmt_range(array_column($cars, 'power')),
                'accel' => self::fmt_range(array_column($cars, 'accel'), 2),
                'fuel'  => self::fmt_range(array_column($cars, 'fuel_c'), 1),
            ],
        ];

        return self::$cache[$term_id] = $spec;
    }

    // ---------------------------------------------------------------- snapshot (fallback count=0)

    private const SNAPSHOT_META = '_asiaauto_spec_snapshot';

    /** Odczyt snapshotu z term_meta (dekodowany), null gdy brak. */
    private static function getSnapshot(int $term_id): ?array
    {
        $raw = get_term_meta($term_id, self::SNAPSHOT_META, true);
        if (!$raw) return null;
        $s = is_string($raw) ? json_decode($raw, true) : $raw;
        return is_array($s) ? $s : null;
    }

    /**
     * Zbuduj i zapisz snapshot (html + jsonld + llms) do term_meta.
     * Wołane przez batch (KROK 3) i opportunistic refresh w renderze.
     * Zwraca true gdy zapisano/odświeżono, false gdy brak żywych danych.
     */
    public static function saveSnapshot(int $term_id): bool
    {
        $spec = self::buildSpec($term_id);
        if (!$spec) return false;
        $payload = [
            'html'   => self::tableHtml($spec),
            'jsonld' => self::carJsonLd($spec),
            'llms'   => self::llmsBlock($spec),
            'name'   => $spec['name'],
            'count'  => $spec['count'],
            'ts'     => current_time('mysql'),
        ];
        $payload['hash'] = md5($payload['html']);
        update_term_meta($term_id, self::SNAPSHOT_META, wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return true;
    }

    // ---------------------------------------------------------------- renderTable

    /**
     * Tabela HTML: Dane wspólne + Wersje + podsumowanie (snippet longtail).
     * Etykiety wierszy = frazy z query (Wymiary / Zasięg / Spalanie / 0-100 …).
     * Fallback (count=0 / brak danych): ostatni snapshot.
     *
     * @return string '' gdy brak danych spec i brak snapshotu.
     */
    public static function renderTable(int $term_id): string
    {
        $spec = self::buildSpec($term_id);
        if (!$spec) {
            $snap = self::getSnapshot($term_id);
            return $snap['html'] ?? '';
        }
        return self::tableHtml($spec);
    }

    /** Czysta budowa tabeli HTML z gotowego spec (bez I/O snapshotu). */
    private static function tableHtml(array $spec): string
    {
        $common   = $spec['common'];
        $versions = $spec['versions'];

        $h  = '<div class="aa-spec">';

        // Dane wspólne
        $h .= '<h3 class="aa-spec__sub">Dane wspólne</h3>';
        $h .= '<div class="aa-spec__scroll"><table class="aa-spec__table aa-spec__table--common">';
        foreach ($common as $k => $v) {
            $h .= '<tr><th>' . esc_html((string) $k) . '</th><td>' . esc_html((string) $v) . '</td></tr>';
        }
        $h .= '</table></div>';

        // Wersje — jednostka W ETYKIECIE (wartości w komórkach bez jednostki).
        $rows = [
            'dt'        => 'Napęd',
            'power'     => 'Moc (KM)',
            'torque'    => 'Moment obrotowy (Nm)',
            'batt'      => 'Pojemność baterii (kWh)',
            'range_ev'  => 'Zasięg elektryczny (km)',
            'range_tot' => 'Zasięg całkowity (km)',
            'accel'     => 'Przyspieszenie 0–100 km/h (s)',
            'vmax'      => 'Prędkość maksymalna (km/h)',
            'fuel_c'    => 'Spalanie (l/100 km)',
            'engine'    => 'Silnik',
            'gearbox'   => 'Skrzynia biegów',
        ];
        $h .= '<h3 class="aa-spec__sub">Wersje</h3>';
        $h .= '<div class="aa-spec__scroll"><table class="aa-spec__table aa-spec__table--versions"><thead><tr><th>Parametr</th>';
        foreach ($versions as $vl => $_) $h .= '<th>' . esc_html((string) $vl) . '</th>';
        $h .= '</tr></thead><tbody>';
        foreach ($rows as $key => $label) {
            // pomiń wiersz jeśli wszędzie pusto
            $any = false;
            foreach ($versions as $list) { foreach ($list as $c) { if (!empty($c[$key])) { $any = true; break 2; } } }
            if (!$any) continue;
            $h .= '<tr><th>' . esc_html((string) $label) . '</th>';
            foreach ($versions as $list) {
                if (in_array($key, ['dt', 'engine', 'gearbox'], true)) {
                    $val = self::mode_val(array_column($list, $key));
                } else {
                    $dec = $key === 'accel' ? 2 : (in_array($key, ['fuel_c', 'batt'], true) ? 1 : 0);
                    $val = self::fmt_range(array_column($list, $key), $dec);
                }
                $h .= '<td>' . esc_html((string) ($val ?? '—')) . '</td>';
            }
            $h .= '</tr>';
        }
        $h .= '</tbody></table></div>';

        // podsumowanie (longtail/snippet) — pakuje tokeny: wymiary, waga, zasięg, moc, 0-100, spalanie
        $s = $spec['summary'];
        $parts = [];
        if ($s['lwh'])   $parts[] = 'wymiary ' . str_replace('x', ' × ', $s['lwh']) . ' mm';
        if ($s['curb'])  $parts[] = 'masa własna (waga) ' . $s['curb'] . ' kg';
        if ($s['range']) $parts[] = 'zasięg elektryczny ' . $s['range'] . ' km';
        if ($s['power']) $parts[] = 'moc ' . $s['power'] . ' KM';
        if ($s['accel']) $parts[] = 'przyspieszenie 0–100 km/h ' . $s['accel'] . ' s';
        if ($s['fuel'])  $parts[] = 'spalanie ' . $s['fuel'] . ' l/100 km';
        if ($parts) {
            $h .= '<p class="aa-spec__summary"><strong>' . esc_html($spec['name']) . '</strong> — '
                . esc_html(implode(', ', $parts)) . ' (zależnie od wersji).</p>';
        }

        $h .= '</div>';
        return $h;
    }

    // ---------------------------------------------------------------- renderJsonLd

    /**
     * Schema Car: additionalProperty (zakresy) + vehicleConfiguration (lista wersji).
     * Fallback (count=0): jsonld z ostatniego snapshotu. null gdy nic.
     */
    public static function renderJsonLd(int $term_id): ?array
    {
        $spec = self::buildSpec($term_id);
        if (!$spec) {
            $snap = self::getSnapshot($term_id);
            return $snap['jsonld'] ?? null;
        }
        return self::carJsonLd($spec);
    }

    /** Czysta budowa schema Car z gotowego spec. */
    private static function carJsonLd(array $spec): array
    {
        $cars     = $spec['cars'];
        $versions = $spec['versions'];
        $allLwh   = array_filter(array_column($cars, 'lwh'));

        $ld = [
            '@context'             => 'https://schema.org',
            '@type'                => 'Car',
            'name'                 => $spec['name'],
            'brand'                => ['@type' => 'Brand', 'name' => $spec['make']],
            'vehicleConfiguration' => implode(' / ', array_keys($versions)),
            'additionalProperty'   => [],
        ];
        $ap = function ($n, $v, $u = '') use (&$ld) {
            if ($v) $ld['additionalProperty'][] = ['@type' => 'PropertyValue', 'name' => $n, 'value' => $v . ($u ? " $u" : '')];
        };
        $ap('Wymiary', self::mode_val($allLwh), 'mm');
        $ap('Długość', self::fmt_range(array_column($cars, 'len')), 'mm');
        $ap('Szerokość', self::fmt_range(array_column($cars, 'wid')), 'mm');
        $ap('Wysokość', self::fmt_range(array_column($cars, 'hei')), 'mm');
        $ap('Rozstaw osi', self::fmt_range(array_column($cars, 'wb')), 'mm');
        $ap('Masa własna', self::fmt_range(array_column($cars, 'curb')), 'kg');
        $ap('Masa całkowita', self::fmt_range(array_column($cars, 'gross')), 'kg');
        $ap('Zasięg elektryczny', $spec['summary']['range'], 'km');
        $ap('Moc', $spec['summary']['power'], 'KM');
        $ap('Przyspieszenie 0-100 km/h', $spec['summary']['accel'], 's');
        $ap('Prędkość maksymalna', self::fmt_range(array_column($cars, 'vmax')), 'km/h');
        $ap('Pojemność baterii', self::fmt_range(array_column($cars, 'batt'), 1), 'kWh');
        $ap('Pojemność bagażnika', self::fmt_range(array_column($cars, 'boot')), 'l');
        $ap('Spalanie', self::fmt_range(array_column($cars, 'fuel_c'), 1), 'l/100 km');

        return $ld;
    }

    // ---------------------------------------------------------------- buildLlmsBlock

    /**
     * Blok markdown dla /llms-full.txt — per-wersja podsumowanie.
     * Fallback (count=0): blok z ostatniego snapshotu.
     *
     * @return string '' gdy brak danych spec i brak snapshotu.
     */
    public static function buildLlmsBlock(int $term_id): string
    {
        $spec = self::buildSpec($term_id);
        if (!$spec) {
            $snap = self::getSnapshot($term_id);
            return $snap['llms'] ?? '';
        }
        return self::llmsBlock($spec);
    }

    /** Czysta budowa bloku llms z gotowego spec. */
    private static function llmsBlock(array $spec): string
    {
        $name = $spec['name'];
        $out = "## {$name}\n";
        foreach ($spec['versions'] as $vl => $list) {
            $b = self::fmt_range(array_column($list, 'batt'), 1);
            $r = self::fmt_range(array_column($list, 'range_ev'));
            $p = self::fmt_range(array_column($list, 'power'));
            $a = self::fmt_range(array_column($list, 'accel'), 2);
            $seg = [];
            if ($p) $seg[] = "$p KM";
            if ($b) $seg[] = "$b kWh";
            if ($r) $seg[] = "$r km";
            if ($a) $seg[] = "0-100 $a s";
            $out .= "- {$name} {$vl}: " . implode(', ', $seg) . "\n";
        }
        return $out;
    }

    // ---------------------------------------------------------------- lint (parytet/QA)

    /**
     * Krytyka spec — używane przez harness weryfikacyjny (parytet z dry-runem),
     * NIE w renderze produkcyjnym. Próg LINT podniesiony (GRUPA B faceliftów):
     * rozstaw 150→220 mm, długość 120→260 mm.
     *
     * @return string[] lista uwag (pusta = czysty)
     */
    public static function lint(int $term_id): array
    {
        $spec = self::buildSpec($term_id);
        if (!$spec) return ['brak danych extra_prep'];

        $cars = $spec['cars']; $versions = $spec['versions'];
        $iss = [];
        $battAll  = array_filter(array_column($cars, 'batt'));
        $fuelcArr = array_filter(array_column($cars, 'fuel_c'));
        $rngArr   = array_filter(array_column($cars, 'range_ev'));
        $hasBatt = (bool) $battAll; $hasRange = (bool) $rngArr; $hasFuelC = (bool) $fuelcArr;
        $ptype = !$hasBatt ? 'ICE' : ($hasFuelC ? 'PHEV/EREV' : 'EV');

        if (count($versions) > 5) $iss[] = 'nadmierny podział wersji (' . count($versions) . ')';
        foreach ($versions as $vl => $_) if (preg_match('/^(FWD|RWD|AWD|Wariant)\b/u', $vl)) $iss[] = "słaba etykieta wersji: „$vl”";
        foreach ($cars as $c) foreach (['body', 'gearbox', 'engine'] as $k) if (self::has_cjk($c[$k] ?? '')) { $iss[] = "chiński znak w polu „$k”: " . mb_substr($c[$k], 0, 18); break 2; }
        if ($ptype === 'ICE' && $battAll && min($battAll) < 5) $iss[] = 'bateria-szum na ICE (' . min($battAll) . ' kWh — 48V?)';
        if (!array_filter(array_column($cars, 'lwh'))) $iss[] = 'brak wymiarów (length_width_height)';
        if (!array_filter(array_column($cars, 'wb')))  $iss[] = 'brak rozstawu osi';
        // KONTAMINACJA: rozstaw osi (model-constant) — próg 220 mm (był 150)
        $wbArr = array_filter(array_column($cars, 'wb'));
        if ($wbArr && (max($wbArr) - min($wbArr)) > 220) $iss[] = sprintf('rozstaw osi %d–%d mm (Δ%d — kontaminacja taksonomii?)', min($wbArr), max($wbArr), max($wbArr) - min($wbArr));
        if ($ptype !== 'ICE' && !$hasRange) $iss[] = "$ptype bez zasięgu";
        if ($ptype === 'ICE' && !$hasFuelC) $iss[] = 'ICE bez spalania';
        // wymiary — próg 260 mm (był 120)
        $lens = []; $wids = [];
        foreach (array_filter(array_column($cars, 'lwh')) as $x) { $p = explode('x', $x); if (isset($p[0])) $lens[] = (int) $p[0]; if (isset($p[1])) $wids[] = (int) $p[1]; }
        $lens = array_filter($lens); $wids = array_filter($wids);
        if ($lens && (max($lens) - min($lens)) > 260) $iss[] = sprintf('długość %d–%d mm (Δ%d — mix generacji/modeli?)', min($lens), max($lens), max($lens) - min($lens));
        if ($wids && (max($wids) - min($wids)) > 260) $iss[] = sprintf('szerokość %d–%d mm (Δ%d)', min($wids), max($wids), max($wids) - min($wids));

        return $iss;
    }
}
