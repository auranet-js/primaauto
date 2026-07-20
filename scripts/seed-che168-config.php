<?php
/**
 * Seed asiaauto_import_config['che168'] — jednorazowy, idempotentny.
 * Uruchomienie (przy deployu, po akcepcie Janka):
 *   wp eval-file scripts/seed-che168-config.php
 * Kopia limitów/miast z dongchedi; marki = przecięcie marek dongchedi
 * z markami kanonicznego słownika che168; blacklista pusta; enabled=true.
 *
 * T-186 / spec docs/superpowers/specs/2026-07-20-che168-model-filter-design.md
 */
$config = get_option('asiaauto_import_config', []);
if (!empty($config['che168'])) {
    echo "che168 config już istnieje — nic nie robię.\n";
    return;
}
$dc = $config['dongchedi'] ?? [];
if (empty($dc['marks'])) {
    echo "BRAK configu dongchedi — przerwane.\n";
    return;
}
$dict = AsiaAuto_Che168_Dictionary::canonicalized();
if ($dict === []) {
    echo "BRAK słownika che168 (API?) — przerwane.\n";
    return;
}
$marks = array_values(array_intersect($dc['marks'], array_keys($dict)));
$config['che168'] = [
    'enabled'             => true,
    'marks'               => $marks,
    'model_blacklist'     => [],
    'year_from'           => $dc['year_from'] ?? 2024,
    'year_to'             => $dc['year_to'] ?? null,
    'km_from'             => $dc['km_from'] ?? null,
    'km_to'               => $dc['km_to'] ?? 40000,
    'price_from'          => $dc['price_from'] ?? 85000,
    'price_to'            => $dc['price_to'] ?? null,
    'city_filter_enabled' => !empty($dc['city_filter_enabled']),
    'city_filter_cities'  => $dc['city_filter_cities'] ?? [],
];
update_option('asiaauto_import_config', $config);
echo 'Seed OK: ' . count($marks) . " marek, limity z dongchedi, blacklista pusta, enabled=true.\n";
