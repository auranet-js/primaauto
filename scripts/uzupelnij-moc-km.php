<?php
/**
 * uzupelnij-moc-km.php — `_asiaauto_horse_power` (KM) z `_asiaauto_extra_prep`.
 *
 * Powód: meta miało wartość w 40% ofert (che168 nie wysyła), a tam, gdzie miało, przy PHEV/EREV
 * była to moc SILNIKA spalinowego (AITO M9: 152 zamiast 496). Do filtra „moc od–do" liczy się
 * moc UKŁADU, więc kolejność źródeł (mockup 2026-09-02, wariant B):
 *   1. „160(218Ps)" w electric_max_power / energy_elect_max_power  (dongchedi, moc układu)
 *   2. system_max_power kW × 1,36                                    (katalog: 最大功率(kW)@基本参数)
 *   3. electric_total_horsepower                                     (katalog: 电动机(Ps))
 *   4. engine_max_horsepower                                         (ICE bez danych układu)
 *   5. total_electric_power kW × 1,36
 *   6. front_electric_max_horsepower
 * Stempel `_asiaauto_horse_power_source` = użyty klucz (audyt). Zapis tylko gdy wartość się różni.
 *
 * Użycie: php scripts/uzupelnij-moc-km.php [apply] [all]
 *   bez `all` — tylko oferty bez meta (wariant A); z `all` — przelicz wszystkie (wariant B)
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';
$apply = in_array('apply', $argv, true);
$all   = in_array('all', $argv, true);
global $wpdb;
$rows = $wpdb->get_results("SELECT p.ID, p.post_title, ep.meta_value ep, hp.meta_value hp
    FROM {$wpdb->posts} p
    JOIN {$wpdb->postmeta} ep ON ep.post_id=p.ID AND ep.meta_key='_asiaauto_extra_prep'
    LEFT JOIN {$wpdb->postmeta} hp ON hp.post_id=p.ID AND hp.meta_key='_asiaauto_horse_power'
    WHERE p.post_type='listings' AND p.post_status IN ('publish','draft')");
function moc_km(array $e): array {
    foreach (['electric_max_power', 'energy_elect_max_power'] as $k)
        if (!empty($e[$k]) && preg_match('/\((\d+)\s*Ps\)/i', (string) $e[$k], $m)) return [(int) $m[1], $k];
    if (!empty($e['system_max_power']) && is_numeric($e['system_max_power'])) return [(int) round($e['system_max_power'] * 1.36), 'system_max_power'];
    if (!empty($e['electric_total_horsepower']) && preg_match('/\d+/', (string) $e['electric_total_horsepower'], $m)) return [(int) $m[0], 'electric_total_horsepower'];
    if (!empty($e['engine_max_horsepower']) && preg_match('/\d+/', (string) $e['engine_max_horsepower'], $m)) return [(int) $m[0], 'engine_max_horsepower'];
    if (!empty($e['total_electric_power']) && is_numeric($e['total_electric_power'])) return [(int) round($e['total_electric_power'] * 1.36), 'total_electric_power'];
    if (!empty($e['front_electric_max_horsepower']) && preg_match('/\d+/', (string) $e['front_electric_max_horsepower'], $m)) return [(int) $m[0], 'front_electric_max_horsepower'];
    return [0, ''];
}
$filled = $changed = $same = $none = $skipped = $kept = 0; $by = []; $log = [];
foreach ($rows as $r) {
    $has = $r->hp !== null && $r->hp !== '' && (int) $r->hp > 0;
    if ($has && !$all) { $skipped++; continue; }
    $e = json_decode($r->ep, true) ?: [];
    [$km, $src] = moc_km($e);
    if ($km <= 0) { $none++; continue; }
    if ($has && (int) $r->hp === $km) { $same++; continue; }
    // Istniejącej wartości nie obniżamy do mocy SILNIKA: gdy katalog nie ma mocy układu (Lynk & Co 900:
    // 734 → 254), zostaje to, co wpisał importer/Ruslan. Nadpisują tylko źródła mocy układu (1–3).
    if ($has && in_array($src, ['engine_max_horsepower', 'total_electric_power', 'front_electric_max_horsepower'], true)) { $kept++; continue; }
    if ($has) { $changed++; if (count($log) < 30) $log[] = sprintf('#%d %s: %d → %d KM (%s)', $r->ID, mb_strimwidth(html_entity_decode($r->post_title), 0, 40, '…'), (int) $r->hp, $km, $src); }
    else $filled++;
    $by[$src] = ($by[$src] ?? 0) + 1;
    if ($apply) { update_post_meta($r->ID, '_asiaauto_horse_power', (string) $km); update_post_meta($r->ID, '_asiaauto_horse_power_source', $src); }
}
printf("%s | wariant %s | ofert: %d\n", $apply ? 'APPLY' : 'DRY-RUN', $all ? 'B (wszystkie)' : 'A (tylko brakujące)', count($rows));
printf("uzupełnione: %d | zmienione istniejące: %d | bez zmian: %d | zachowane (źródło = silnik): %d | bez źródła: %d | pominięte (A): %d\n", $filled, $changed, $same, $kept, $none, $skipped);
arsort($by); foreach ($by as $k => $n) printf("  %-32s %d\n", $k, $n);
echo "zmienione istniejące (próbka):\n"; foreach ($log as $l) echo "  $l\n";
