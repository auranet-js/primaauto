<?php
/**
 * napraw-extra-prep-t116.php — trzy jednorazowe korekty wartości w `_asiaauto_extra_prep`
 * (T-116 etap 2, mockup 2026-09-02). Addytywne, per klucz, zapis przez wp_slash.
 *
 *   pary     — „8个 / 12个" (standard / pakiet) → 8 w kluczach liczbowych z katalogu Autohome
 *   unicode  — „u7406u60f3" (relikt importów ręcznych sprzed wp_slash) → znaki; także „360u00b0"
 *   air      — che168-param-map mapował doładowanie (param 13) na `air_supply` (= rozrząd):
 *              wartość 增压/吸气 przenosi się do `gas_form` (gdy pusty), `air_supply` dostaje
 *              配气机构 z cache katalogu (gdy jest) albo znika
 *
 * Użycie: php scripts/napraw-extra-prep-t116.php <pary|unicode|air|all> [apply]
 * Stempel: _asiaauto_fix_t116_at (audyt). Dump przed: ~/backups/primaauto/2026-09-02/.
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';
$mode  = $argv[1] ?? '';
$apply = in_array('apply', $argv, true);
if (!in_array($mode, ['pary', 'unicode', 'air', 'all'], true)) { echo "Użycie: <pary|unicode|air|all> [apply]\n"; exit(1); }
$CACHE = wp_get_upload_dir()['basedir'] . '/asiaauto/autohome-catalog';
global $wpdb;
$rows = $wpdb->get_results("SELECT p.ID, p.post_title, ep.meta_value ep, sp.meta_value specid
    FROM {$wpdb->posts} p
    JOIN {$wpdb->postmeta} ep ON ep.post_id=p.ID AND ep.meta_key='_asiaauto_extra_prep'
    LEFT JOIN {$wpdb->postmeta} sp ON sp.post_id=p.ID AND sp.meta_key='_asiaauto_spec_id'
    WHERE p.post_type='listings' AND p.post_status IN ('publish','draft')");
$PAIR_KEYS = ['camera_count', 'incar_camera_count_2', 'ultrasonic_radar', 'millimeter_wave_radar', 'laser_radar'];
$uni = static fn(string $v): string => preg_replace_callback('/u([0-9a-f]{4})/', fn($m) => mb_chr(hexdec($m[1]), 'UTF-8'), $v);
$stat = ['pary' => 0, 'unicode' => 0, 'air_gas' => 0, 'air_dohc' => 0, 'air_clear' => 0];
$touched = 0; $log = [];
foreach ($rows as $r) {
    $e = json_decode($r->ep, true);
    if (!is_array($e)) continue;
    $orig = $e; $why = [];
    if ($mode === 'pary' || $mode === 'all') {
        foreach ($PAIR_KEYS as $k) if (isset($e[$k]) && preg_match('~^(\d+)个 / \d+个$~u', (string) $e[$k], $m)) { $why[] = "$k: {$e[$k]} → {$m[1]}"; $e[$k] = $m[1]; $stat['pary']++; }
    }
    if ($mode === 'unicode' || $mode === 'all') {
        foreach ($e as $k => $v) {
            $v = (string) $v;
            if (preg_match('~(?<![\\\\\w])u[0-9a-f]{4}~', $v) || str_contains($v, '360u00b0')) {
                $n = $uni($v); if ($n !== $v) { $why[] = "$k: " . mb_strimwidth($v, 0, 40, '…') . " → " . mb_strimwidth($n, 0, 30, '…'); $e[$k] = $n; $stat['unicode']++; }
            }
        }
    }
    if ($mode === 'air' || $mode === 'all') {
        $a = (string) ($e['air_supply'] ?? '');
        if ($a !== '' && preg_match('~增压|吸气~u', $a)) {
            if (empty($e['gas_form'])) { $e['gas_form'] = $a; $stat['air_gas']++; $why[] = "gas_form ← $a"; }
            $dohc = '';
            if ($r->specid && is_readable("$CACHE/{$r->specid}.json")) {
                foreach ((array) json_decode(file_get_contents("$CACHE/{$r->specid}.json"), true) as $row) {
                    if (($row['name'] ?? '') === '配气机构' && trim((string) $row['value']) !== '' && $row['value'] !== '-') { $dohc = trim($row['value']); break; }
                }
            }
            if ($dohc !== '') { $e['air_supply'] = $dohc; $stat['air_dohc']++; $why[] = "air_supply: $a → $dohc"; }
            else { unset($e['air_supply']); $stat['air_clear']++; $why[] = "air_supply: $a → (usunięte)"; }
        }
    }
    if ($e === $orig) continue;
    $touched++;
    if (count($log) < 40) $log[] = sprintf('#%d %s: %s', $r->ID, mb_strimwidth(html_entity_decode($r->post_title), 0, 38, '…'), implode(' | ', array_slice($why, 0, 4)) . (count($why) > 4 ? ' … +' . (count($why) - 4) : ''));
    if ($apply) {
        update_post_meta($r->ID, '_asiaauto_extra_prep', wp_slash(wp_json_encode($e, JSON_UNESCAPED_UNICODE)));
        update_post_meta($r->ID, '_asiaauto_fix_t116_at', gmdate('c'));
    }
}
printf("tryb %s | ofert: %d | %s\n", $mode, count($rows), $apply ? 'APPLY' : 'DRY-RUN');
printf("zmienione oferty: %d | pary: %d | unicode: %d | air→gas_form: %d | air→DOHC z cache: %d | air usunięte: %d\n", $touched, $stat['pary'], $stat['unicode'], $stat['air_gas'], $stat['air_dohc'], $stat['air_clear']);
foreach ($log as $l) echo "  $l\n";
if ($touched > count($log)) printf("  … i %d więcej\n", $touched - count($log));
