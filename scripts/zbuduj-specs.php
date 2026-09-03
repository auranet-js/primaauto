<?php
/**
 * zbuduj-specs.php — buduje i odświeża tabelę `wp7j_asiaauto_specs` (T-116 etap 3).
 *
 * Tabela to spłaszczona, typowana kopia parametrów oferty pod wyszukiwarkę zaawansowaną.
 * Źródłem prawdy pozostaje `_asiaauto_extra_prep` + meta + taksonomie — ten skrypt NICZEGO
 * poza własną tabelą nie zapisuje. Cała normalizacja siedzi w `AsiaAuto_Specs_Table`
 * (czyste funkcje statyczne), tutaj jest wyłącznie orkiestracja i raport.
 *
 * Użycie:  php zbuduj-specs.php [apply] [since=48h] [limit=N]
 *          bez `apply`      — dry-run: liczy wszystko, nie zapisuje nic
 *          since=48h        — tylko oferty wzbogacone/zmienione w ostatnich N godzinach
 *                             (stemple `_asiaauto_spec_*_at`) plus te, których brak w tabeli
 *          limit=N          — ogranicz liczbę ofert (do prób)
 *
 * Reguły normalizacji: docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md sekcja 2.
 *
 * @since 2026-09-02 (T-116 etap 3, krok 2)
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

if (!class_exists('AsiaAuto_Specs_Table')) {
    fwrite(STDERR, "BŁĄD: klasa AsiaAuto_Specs_Table niedostępna (plugin wyłączony?).\n");
    exit(1);
}

$apply = in_array('apply', $argv, true);
$since = null;
$limit = 0;
foreach ($argv as $a) {
    if (preg_match('~^since=(\d+)h?$~', (string) $a, $m)) $since = (int) $m[1];
    if (preg_match('~^limit=(\d+)$~', (string) $a, $m))   $limit = (int) $m[1];
}

$t0 = microtime(true);
echo "== zbuduj-specs.php — " . ($apply ? 'APPLY' : 'DRY-RUN') . ($since ? " (since={$since}h)" : ' (pełny)') . "\n";

if ($apply && !AsiaAuto_Specs_Table::tableExists()) {
    AsiaAuto_Specs_Table::install();
    echo "Tabela " . AsiaAuto_Specs_Table::table() . " utworzona.\n";
}
if (!AsiaAuto_Specs_Table::tableExists()) {
    echo "Tabela nie istnieje — dry-run liczy na sucho, apply ją założy.\n";
}

$ids = AsiaAuto_Specs_Table::idsToRebuild($since);
if ($limit) $ids = array_slice($ids, 0, $limit);
echo "Ofert do przerobienia: " . count($ids) . "\n\n";

$flagCols  = array_keys(AsiaAuto_Specs_Table::FLAGS);
$valueCols = ['price','mileage','year','power_km','range_cltc','battery_kwh','seats','rim_in',
              'fuel','body','drive','transmission','upholstery','sunroof',
              // ruch C (2026-09-03)
              'length_mm','gvw_kg','range_total','interior_color','suspension','sound_brand'];

$filled = $nulls = [];
$unknown = ['upholstery' => [], 'sunroof' => [], 'sound_brand' => []];
$perStatus = [];
$written = 0;

foreach ($ids as $id) {
    $post = get_post($id);
    if (!$post || $post->post_type !== 'listings') continue;
    [$e, $meta, $tax] = AsiaAuto_Specs_Table::gather($id);
    $row = AsiaAuto_Specs_Table::buildRow($e, $meta, $tax);
    $perStatus[$post->post_status] = ($perStatus[$post->post_status] ?? 0) + 1;

    if ($post->post_status === 'publish') {
        foreach ($valueCols as $c) {
            if ($row[$c] === null || $row[$c] === '') $nulls[$c] = ($nulls[$c] ?? 0) + 1;
            else $filled[$c] = ($filled[$c] ?? 0) + 1;
        }
        foreach ($flagCols as $c) $filled[$c] = ($filled[$c] ?? 0) + (int) $row[$c];
        if ($row['upholstery'] === null && !empty($e['seat_material'])) {
            $k = (string) $e['seat_material'];
            $unknown['upholstery'][$k] = ($unknown['upholstery'][$k] ?? 0) + 1;
        }
        if ($row['sound_brand'] === null && !empty($e['sound_brand'])
            && !in_array(AsiaAuto_Specs_Table::firstVariant($e['sound_brand']), AsiaAuto_Specs_Table::NEGATIVE, true)) {
            $k = (string) $e['sound_brand'];
            $unknown['sound_brand'][$k] = ($unknown['sound_brand'][$k] ?? 0) + 1;
        }
        if ($row['sunroof'] === null && array_key_exists('skylight_type', $e)) {
            $k = (string) $e['skylight_type'];
            $unknown['sunroof'][$k] = ($unknown['sunroof'][$k] ?? 0) + 1;
        }
    }
    if ($apply && AsiaAuto_Specs_Table::rebuildRow($id) !== null) $written++;
}

$npub = $perStatus['publish'] ?? 0;
echo "Statusy: "; foreach ($perStatus as $s => $c) echo "$s=$c "; echo "\n";
if ($apply) echo "Wierszy zapisanych: $written\n";
echo "\nPOKRYCIE KOLUMN (na $npub ofertach publish z tej partii):\n";
foreach (array_merge($valueCols, $flagCols) as $c) {
    $f = $filled[$c] ?? 0;
    printf("  %-16s %5d  %5.1f%%%s\n", $c, $f, $npub ? $f * 100 / $npub : 0,
        isset($nulls[$c]) ? "   (NULL: {$nulls[$c]})" : '');
}
echo "\nNIEZNANE WARTOŚCI ENUMÓW:\n";
foreach ($unknown as $col => $vals) {
    if (!$vals) { echo "  [$col] brak\n"; continue; }
    arsort($vals);
    echo "  [$col]\n";
    foreach ($vals as $v => $c) printf("    %4d  %s\n", $c, $v);
}
printf("\nCzas: %.1f s\n", microtime(true) - $t0);
