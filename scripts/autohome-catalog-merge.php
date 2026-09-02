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
 * Składnia mapy (data/autohome-catalog-map.php), od 2026-09-02 (T-116 etap 2):
 *  - 'nazwa CN' => 'klucz'                    → 1:1, wartość katalogu (●→标配, ○→选配)
 *  - 'nazwa CN' => 'klucz=wartość'            → wartość STAŁA (np. `远程启动功能`=1 → 标配);
 *                                               ○ daje 选配, „0" pomija
 *  - 'nazwa CN' => ['k_przód', 'k_tył']       → lista: para „前● / 后●" na dwa klucze
 *  - 'nazwa CN' => ['podnazwa' => 'klucz', …] → ZŁOŻONE: wartość katalogu to lista
 *                                               „加热 / 通风 / 按摩"; każda podnazwa OBECNA
 *                                               w wartości zapala swój klucz. Zapisywana jest
 *                                               podnazwa (kształt jak u dongchedi: `按摩`),
 *                                               chyba że cel ma `=wartość`.
 *  - 'nazwa CN@grupa' => …                     → gdy ta sama nazwa CN żyje w dwóch grupach
 *                                               z inną semantyką (最大功率 w 基本参数 = układ,
 *                                               w 发动机 = silnik). Klucz z grupą ma pierwszeństwo.
 *
 * Użycie: wp eval-file scripts/autohome-catalog-merge.php <post_id> <plik.json> <specid> [apply]
 */
$post_id = (int) ($args[0] ?? 0);
$json    = (string) ($args[1] ?? '');
$specid  = (string) ($args[2] ?? '');
$apply   = in_array('apply', $args ?? [], true);
$verbose = in_array('verbose', $args ?? [], true);   // pełny log + lista niezmapowanych
if (!$post_id || !is_readable($json)) { echo "Użycie: <post_id> <plik.json> <specid> [apply]\n"; return; }

$map  = require ASIAAUTO_PLUGIN_DIR . 'data/autohome-catalog-map.php';
$rows = json_decode(file_get_contents($json), true) ?: [];
$ep   = json_decode(get_post_meta($post_id, '_asiaauto_extra_prep', true), true) ?: [];
$before = count($ep);

$norm = static function (string $v): string {
    $v = trim(str_replace(['&nbsp;', "\xc2\xa0"], ' ', $v));
    // „12.3英寸" → 12.3, „520线" → 520, „5个" → 5, „32喇叭" → 32: liczba z chińskim sufiksem jednostki nie przeszłaby
    // filtra CJK w translatorze (wiersz z chińskim znakiem jest ukrywany na karcie oferty).
    $v = preg_replace('~^(\d+(?:\.\d+)?)(英寸|线|个|喇叭)$~u', '$1', $v);
    // „8个 / 12个" (standard / z pakietem) → 8: do liczby bierzemy wariant standardowy.
    $v = preg_replace('~^(\d+)个 / \d+个$~u', '$1', $v);
    return $v === '-' || $v === '' ? '' : $v;
};
$flag = static function (string $v): string {
    $v = trim($v);
    if ($v === '●') return '标配';
    if ($v === '○') return '选配';
    return $v;
};

$added = $skipped_exists = $skipped_empty = $unmapped = 0;
$log = []; $unmappedList = [];
// Zapis jednego klucza: `klucz` albo `klucz=wartość stała`. Zwraca true gdy dodano.
$put = static function (string $spec, string $val) use (&$ep, &$added, &$skipped_exists, &$skipped_empty, &$log, $flag, $norm): bool {
    [$key, $fixed] = array_pad(explode('=', $spec, 2), 2, null);
    if ($fixed !== null) {
        if ($val === '0') { $skipped_empty++; return false; }
        $store = $val === '○' ? '选配' : $fixed;
    } else {
        $store = $norm($flag($val));
        if ($store === '') { $skipped_empty++; return false; }
    }
    if (array_key_exists($key, $ep)) { $skipped_exists++; return false; }
    $ep[$key] = $store; $added++; $log[] = "→ $key = $store";
    return true;
};

foreach ($rows as $r) {
    $name  = $r['name'] ?? '';
    $group = $r['group'] ?? '';
    $mkey  = isset($map["$name@$group"]) ? "$name@$group" : $name;
    if (!isset($map[$mkey])) { if ($norm((string)($r['value'] ?? ''))) { $unmapped++; $unmappedList[] = "$group / $name = " . $norm((string) $r['value']); } continue; }
    $target = $map[$mkey];
    $val    = $norm((string) ($r['value'] ?? ''));
    if ($val === '') { $skipped_empty++; continue; }

    // ZŁOŻONE: ['podnazwa' => 'klucz[=wartość]', …] — podnazwa obecna w wartości zapala klucz
    if (is_array($target) && !array_is_list($target)) {
        foreach ($target as $sub => $spec) {
            if (mb_strpos($val, (string) $sub) === false) continue;
            $n0 = count($log);
            if ($put($spec, str_contains($spec, '=') ? '●' : (string) $sub) && count($log) > $n0) {
                $log[count($log) - 1] = "$name [$sub] " . $log[count($log) - 1];
            }
        }
        continue;
    }

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
    $n0 = count($log);
    if ($put($target, $val) && count($log) > $n0) {
        $log[count($log) - 1] = "$name " . $log[count($log) - 1];
    }
}

printf("oferta %d (specid %s)\n  extra_prep przed: %d → po: %d  (+%d)\n", $post_id, $specid, $before, count($ep), $added);
printf("  pominięte: %d (już było), %d (pusta wartość) | niezmapowanych z wartością: %d\n", $skipped_exists, $skipped_empty, $unmapped);
foreach ($verbose ? $log : array_slice($log, 0, 15) as $l) echo "    + $l\n";
if (!$verbose && count($log) > 15) printf("    … i %d więcej\n", count($log) - 15);
if ($verbose && $unmappedList) { echo "  niezmapowane z wartością:\n"; foreach ($unmappedList as $u) echo "    ? $u\n"; }

if ($apply) {
    // wp_slash OBOWIĄZKOWY: update_post_meta robi wp_unslash i zjada escape'y JSON ("\n" → "n",
    // "\u9a6c" → "u9a6c"). Bez tego backfill 2026-09-02 uszkodził 151 wartości w 140 ofertach
    // (naprawione z dumpu). Tak samo zapisują merge-spec-from-twin.php i dolej-spec-z-banku.php.
    update_post_meta($post_id, '_asiaauto_extra_prep', wp_slash(wp_json_encode($ep, JSON_UNESCAPED_UNICODE)));
    update_post_meta($post_id, '_asiaauto_spec_catalog_specid', $specid);
    update_post_meta($post_id, '_asiaauto_spec_catalog_at', gmdate('c'));
    update_post_meta($post_id, '_asiaauto_spec_catalog_count', $added);
    echo "\n=== ZAPISANO ===\n";
} else {
    echo "\n=== DRY-RUN (dodaj: apply) ===\n";
}
