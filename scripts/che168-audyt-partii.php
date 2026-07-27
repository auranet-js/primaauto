<?php
/**
 * Audyt świeżej partii draftów che168 (T-186). READ-ONLY — nic nie zapisuje.
 *
 * Zbiera z draftów che168 powyżej podanego ID:
 *   - rozkład marek / modeli / roczników / miast,
 *   - nieznane parametry `param_*` z częstością (kandydaci do che168-param-map.php),
 *   - miasta z CJK (kandydaci do translations-cities.php),
 *   - stan kolejki `asiaauto_che168_unmapped`.
 *
 * Użycie: wp eval-file scripts/che168-audyt-partii.php <min_id>
 *   np.   wp eval-file scripts/che168-audyt-partii.php 390275
 */

global $wpdb;

$min_id = (int) ($args[0] ?? 0);
if ($min_id <= 0) {
    echo "Podaj min_id: wp eval-file scripts/che168-audyt-partii.php 390275\n";
    return;
}

$ids = $wpdb->get_col($wpdb->prepare(
    "SELECT p.ID FROM {$wpdb->posts} p
     JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
          AND pm.meta_key = '_asiaauto_source' AND pm.meta_value = 'che168'
     WHERE p.post_type = 'listings' AND p.post_status = 'draft' AND p.ID > %d
     ORDER BY p.ID",
    $min_id
));

printf("Draftów che168 z ID > %d: %d\n\n", $min_id, count($ids));
if (!$ids) {
    return;
}

$marks = $models = $years = $cities = $unknown = $engines = [];
$cjk_cities = [];
$spec_rows = 0;

foreach ($ids as $pid) {
    $mark  = (string) get_post_meta($pid, '_asiaauto_mark', true);
    $model = (string) get_post_meta($pid, '_asiaauto_model', true);
    $year  = (string) get_post_meta($pid, '_asiaauto_year', true);
    $city  = (string) get_post_meta($pid, 'stm_car_location', true);
    $eng   = (string) get_post_meta($pid, '_asiaauto_engine_type', true);

    if ($mark !== '')  { $marks[$mark] = ($marks[$mark] ?? 0) + 1; }
    if ($model !== '') { $k = ($mark !== '' ? $mark . '|' : '') . $model; $models[$k] = ($models[$k] ?? 0) + 1; }
    if ($year !== '')  { $years[$year] = ($years[$year] ?? 0) + 1; }
    if ($eng !== '')   { $engines[$eng] = ($engines[$eng] ?? 0) + 1; }
    if ($city !== '') {
        $cities[$city] = ($cities[$city] ?? 0) + 1;
        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $city)) {
            $cjk_cities[$city] = ($cjk_cities[$city] ?? 0) + 1;
        }
    }

    $ep = get_post_meta($pid, '_asiaauto_extra_prep', true);
    $ep = is_string($ep) ? json_decode($ep, true) : $ep;
    if (is_array($ep)) {
        $spec_rows += count($ep);
        foreach (array_keys($ep) as $key) {
            if (str_starts_with((string) $key, 'param_')) {
                $unknown[$key] = ($unknown[$key] ?? 0) + 1;
            }
        }
    }
}

$dump = static function (string $title, array $arr, int $limit = 0) {
    arsort($arr);
    printf("--- %s (%d) ---\n", $title, count($arr));
    $i = 0;
    foreach ($arr as $k => $v) {
        printf("  %-46s %d\n", $k, $v);
        if ($limit && ++$i >= $limit) {
            printf("  ... (+%d więcej)\n", count($arr) - $limit);
            break;
        }
    }
    echo "\n";
};

printf("Średnio wierszy specyfikacji na ofertę: %.1f\n\n", count($ids) ? $spec_rows / count($ids) : 0);

$dump('Marki', $marks);
$dump('Modele', $models, 40);
$dump('Roczniki', $years);
$dump('Napęd', $engines);
$dump('Miasta', $cities, 40);
$dump('MIASTA Z CJK (do translations-cities.php)', $cjk_cities);
$dump('NIEZNANE PARAMETRY param_* (do che168-param-map.php)', $unknown);

$q = get_option('asiaauto_che168_unmapped', []);
$q = is_array($q) ? $q : [];
printf("--- Kolejka domapowań asiaauto_che168_unmapped (%d) ---\n", count($q));
foreach ($q as $key => $row) {
    printf("  %-40s count=%-4s engine=%-9s price=%-9s example=%s\n",
        $key,
        $row['count'] ?? '?',
        $row['engine'] ?? '-',
        $row['price'] ?? '-',
        $row['example'] ?? '-'
    );
}
echo "\n";
