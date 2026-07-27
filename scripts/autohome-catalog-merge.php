<?php
/**
 * autohome-catalog-merge.php — dolewa wyposażenie z katalogu Autohome do extra_prep oferty.
 *
 * Wejście: JSON ze zdekodowanego katalogu (scripts/autohome-catalog-fetch.js), format:
 *   [{"space":"config|option","id":123,"group":"座椅配置","name":"座椅材质","value":"真皮"}, ...]
 *
 * Zasady (jak merge-spec-from-twin.php):
 *  - NIGDY nie nadpisuje istniejącego klucza — dolewa wyłącznie brakujące;
 *  - pomija wartości puste / "-" (parametr nieobecny w tej wersji);
 *  - ● → 标配 (Tak), ○ → 选配 (Opcja) — słownik translations-extra-prep tłumaczy dalej;
 *  - „前● / 后-" i „主● / 副●" rozbijane na parę kluczy [przód, tył];
 *  - stempluje _asiaauto_spec_catalog_* (audyt + rollback).
 *
 * Użycie: wp eval-file scripts/autohome-catalog-merge.php <post_id> <plik.json> <specid> [apply]
 */
$post_id = (int) ($args[0] ?? 0);
$json    = (string) ($args[1] ?? '');
$specid  = (string) ($args[2] ?? '');
$apply   = in_array('apply', $args ?? [], true);
if (!$post_id || !is_readable($json)) { echo "Użycie: <post_id> <plik.json> <specid> [apply]\n"; return; }

$map  = require ASIAAUTO_PLUGIN_DIR . 'data/autohome-catalog-map.php';
$rows = json_decode(file_get_contents($json), true) ?: [];
$ep   = json_decode(get_post_meta($post_id, '_asiaauto_extra_prep', true), true) ?: [];
$before = count($ep);

$norm = static function (string $v): string {
    $v = trim(str_replace(['&nbsp;', "\xc2\xa0"], ' ', $v));
    return $v === '-' || $v === '' ? '' : $v;
};
$flag = static function (string $v): string {
    $v = trim($v);
    if ($v === '●') return '标配';
    if ($v === '○') return '选配';
    return $v;
};

$added = $skipped_exists = $skipped_empty = $unmapped = 0;
$log = [];
foreach ($rows as $r) {
    $name = $r['name'] ?? '';
    if (!isset($map[$name])) { if ($norm((string)($r['value'] ?? ''))) $unmapped++; continue; }
    $target = $map[$name];
    $val    = $norm((string) ($r['value'] ?? ''));
    if ($val === '') { $skipped_empty++; continue; }

    // para „前● / 后●" lub „主● / 副●" → dwa klucze
    if (is_array($target)) {
        $parts = preg_split('~\s*/\s*~u', $val);
        foreach ($target as $i => $key) {
            $piece = $parts[$i] ?? '';
            $piece = trim(preg_replace('~^(前|后|主|副)~u', '', $piece));
            $piece = $norm($flag($piece));
            if ($piece === '' || $piece === '-') { $skipped_empty++; continue; }
            if (array_key_exists($key, $ep)) { $skipped_exists++; continue; }
            $ep[$key] = $piece; $added++; $log[] = "$name → $key = $piece";
        }
        continue;
    }
    if (array_key_exists($target, $ep)) { $skipped_exists++; continue; }
    $ep[$target] = $flag($val); $added++; $log[] = "$name → $target = " . $flag($val);
}

printf("oferta %d (specid %s)\n  extra_prep przed: %d → po: %d  (+%d)\n", $post_id, $specid, $before, count($ep), $added);
printf("  pominięte: %d (już było), %d (pusta wartość) | niezmapowanych z wartością: %d\n", $skipped_exists, $skipped_empty, $unmapped);
foreach (array_slice($log, 0, 15) as $l) echo "    + $l\n";
if (count($log) > 15) printf("    … i %d więcej\n", count($log) - 15);

if ($apply) {
    update_post_meta($post_id, '_asiaauto_extra_prep', wp_json_encode($ep, JSON_UNESCAPED_UNICODE));
    update_post_meta($post_id, '_asiaauto_spec_catalog_specid', $specid);
    update_post_meta($post_id, '_asiaauto_spec_catalog_at', gmdate('c'));
    update_post_meta($post_id, '_asiaauto_spec_catalog_count', $added);
    echo "\n=== ZAPISANO ===\n";
} else {
    echo "\n=== DRY-RUN (dodaj: apply) ===\n";
}
