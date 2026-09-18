<?php
/**
 * Słownik marka→modele z getFilters, cache w transientach (7 dni).
 * Konsument AsiaAuto_API + AsiaAuto_Mapping — zero dotykania strefy kruchej.
 * Klucze kanoniczne = kształt brand-mappingu (jak dane źródła po adapterze).
 *
 * T-186 / spec docs/superpowers/specs/2026-07-20-che168-model-filter-design.md
 *
 * @since 2026-09-18  Sparametryzowany źródłem (che168 | dongchedi). Domyślny argument
 *                    'che168' trzyma wsteczną zgodność wszystkich dotychczasowych wywołań,
 *                    che168 zostaje też na starych nazwach transientów (zero invalidacji).
 */

if (!defined('ABSPATH')) {
    exit;
}

class AsiaAuto_Che168_Dictionary {

    private const TRANSIENT_RAW   = 'asiaauto_che168_filters_dict';
    private const TRANSIENT_CANON = 'asiaauto_che168_dict_canon';
    private const TTL             = 7 * DAY_IN_SECONDS;

    private const SOURCES = ['che168', 'dongchedi'];

    /**
     * Nazwy transientów per źródło. che168 zachowuje historyczne klucze — podmiana
     * nazwy wyrzuciłaby żywy cache strony „Przeglądaj Che168" bez powodu.
     *
     * @return array{0:string,1:string} [raw, canon]
     */
    private static function transients(string $source): array {
        if ($source === 'che168') {
            return [self::TRANSIENT_RAW, self::TRANSIENT_CANON];
        }
        return ['asiaauto_' . $source . '_filters_dict', 'asiaauto_' . $source . '_dict_canon'];
    }

    private static function source(string $source): string {
        return in_array($source, self::SOURCES, true) ? $source : 'che168';
    }

    /**
     * Nazwy modeli dla jednej marki, niezależnie od kształtu odpowiedzi źródła.
     *
     * Zmierzone 18.09.2026: che168 oddaje `model` jako LISTĘ stringów (["A1","A3",…]),
     * dongchedi jako MAPĘ nazwa => ['complectation' => […]]. Bez tej normalizacji
     * dongchedi wpadał w „Array to string conversion" i dawał chipy z napisem „Array".
     *
     * @return string[]
     */
    public static function modelNames(array $mark_info): array {
        $models = $mark_info['model'] ?? [];
        if (!is_array($models) || $models === []) {
            return [];
        }
        return array_map('strval', array_is_list($models) ? $models : array_keys($models));
    }

    /**
     * Surowy słownik getFilters (z cache; miss → pobranie z API).
     */
    public static function get(string $source = 'che168'): ?array {
        $source = self::source($source);
        [$raw_key] = self::transients($source);
        $cached = get_transient($raw_key);
        if (is_array($cached) && !empty($cached['mark'])) {
            return $cached;
        }
        return self::refresh($source);
    }

    /**
     * Wymuszone pobranie z API + invalidacja cache kanonicznego.
     */
    public static function refresh(string $source = 'che168'): ?array {
        $source = self::source($source);
        if (!defined('ASIAAUTO_API_KEY') || !defined('ASIAAUTO_API_BASE_URL')) {
            return null;
        }
        $api  = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
        $data = $api->getFilters($source);
        if (empty($data['mark']) || !is_array($data['mark'])) {
            return null;
        }
        $data['_fetched_at'] = gmdate('c');
        [$raw_key, $canon_key] = self::transients($source);
        set_transient($raw_key, $data, self::TTL);
        delete_transient($canon_key);
        return $data;
    }

    public static function fetchedAt(string $source = 'che168'): ?string {
        [$raw_key] = self::transients(self::source($source));
        $raw = get_transient($raw_key);
        return is_array($raw) ? ($raw['_fetched_at'] ?? null) : null;
    }

    /**
     * Pary raw przepuszczone przez canonicalKeyForSource + status huba.
     *
     * @return array [canonMark => [ ['key'=>'Mark|Model','raw'=>'rawMark|rawModel','mapped'=>bool], ... ]]
     */
    public static function canonicalized(string $source = 'che168'): array {
        $source = self::source($source);
        [, $canon_key] = self::transients($source);
        $cached = get_transient($canon_key);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }
        $raw = self::get($source);
        if ($raw === null) {
            return [];
        }
        $out = [];
        foreach ($raw['mark'] as $srcMark => $info) {
            foreach (self::modelNames((array) $info) as $srcModel) {
                [$cMark, $cModel] = AsiaAuto_Mapping::canonicalKeyForSource((string) $srcMark, (string) $srcModel, '', $source);
                $key = $cMark . '|' . $cModel;
                if (isset($out[$cMark][$key])) {
                    continue;
                }
                $out[$cMark][$key] = [
                    'key'    => $key,
                    'raw'    => $srcMark . '|' . $srcModel,
                    'mapped' => AsiaAuto_Mapping::getEuForCn($cMark, $cModel) !== null,
                ];
            }
        }
        ksort($out);
        foreach ($out as &$models) {
            ksort($models);
            $models = array_values($models);
        }
        unset($models);
        set_transient($canon_key, $out, self::TTL);
        return $out;
    }
}
