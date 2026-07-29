<?php
/**
 * dolej-spec-z-banku.php — uzupełnia ubogie extra_prep danymi z banku specyfikacji.
 *
 * Powód: od 29.07 nowe oferty dongchedi wchodzą z ~43 polami zamiast ~342 (źródło przestało
 * oddawać listę wyposażenia). Bank (uploads/asiaauto/spec-bank/) trzyma najbogatszy znany
 * komplet per wariant `marka|seria|wersja|rocznik` — także z ofert już wygaszonych.
 *
 * Zasady (jak autohome-catalog-merge.php / merge-spec-from-twin.php):
 *  - NIGDY nie nadpisuje istniejącego klucza — dolewa wyłącznie brakujące;
 *  - dopasowanie WYŁĄCZNIE exact po pełnym kluczu (bez zgadywania po samej serii);
 *  - pomija oferty ręczne (Ruslan mógł je opisać sam);
 *  - przed pierwszą zmianą zapisuje oryginał do _asiaauto_extra_prep_orig (rollback);
 *  - stempluje _asiaauto_spec_bank_at / _spec_bank_count / _spec_bank_key (audyt).
 *
 * Użycie:  php dolej-spec-z-banku.php [prog_pol] [limit] [apply]     (domyślnie prog 100, bez limitu)
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

$prog  = isset($argv[1]) ? (int) $argv[1] : 100;
$limit = isset($argv[2]) && ctype_digit((string) $argv[2]) ? (int) $argv[2] : 0;
$apply = in_array('apply', $argv, true);

$dir = wp_get_upload_dir()['basedir'] . '/asiaauto/spec-bank';
if (!is_dir($dir)) {
    fwrite(STDERR, "Brak banku: {$dir} — najpierw scripts/zbuduj-bank-specyfikacji.php\n");
    exit(1);
}

/* Wczytaj bank do indeksu klucz => extra_prep */
$idx = [];
foreach (glob($dir . '/*.json') as $f) {
    if (basename($f) === '_manifest.json') continue;
    foreach ((array) json_decode(file_get_contents($f), true) as $w) {
        if (isset($w['klucz']) && is_array($w['extra_prep'] ?? null)) {
            $idx[$w['klucz']] = $w['extra_prep'];
        }
    }
}
fwrite(STDERR, "Bank: " . count($idx) . " wariantow\n");

global $wpdb;
$sql = "
    SELECT p.ID, p.post_title, ep.meta_value AS ep, cy.meta_value AS yr, cx.meta_value AS compl
      FROM {$wpdb->posts} p
      JOIN {$wpdb->postmeta} ep ON ep.post_id=p.ID AND ep.meta_key='_asiaauto_extra_prep'
 LEFT JOIN {$wpdb->postmeta} cy ON cy.post_id=p.ID AND cy.meta_key='ca-year'
 LEFT JOIN {$wpdb->postmeta} cx ON cx.post_id=p.ID AND cx.meta_key='_asiaauto_complectation'
     WHERE p.post_type='listings' AND p.post_status='publish'
       AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} mm WHERE mm.post_id=p.ID
                         AND mm.meta_key IN ('_asiaauto_manual_import','_asiaauto_manual_entry')
                         AND mm.meta_value='1')
     ORDER BY p.ID DESC";
if ($limit > 0) $sql .= " LIMIT {$limit}";

$ubogie = 0; $trafione = 0; $brak = 0; $zmienione = 0; $dolaneRazem = 0;
$przyklady = []; $brakiKluczy = [];

foreach ($wpdb->get_results($sql) as $r) {
    $ma = json_decode((string) $r->ep, true);
    if (!is_array($ma) || count($ma) >= $prog) continue;
    $ubogie++;

    $mk = wp_get_post_terms($r->ID, 'make',  ['fields' => 'slugs']);
    $se = wp_get_post_terms($r->ID, 'serie', ['fields' => 'slugs']);
    $yr = trim((string) $r->yr);
    if ($yr === '') {
        $t  = wp_get_post_terms($r->ID, 'ca-year', ['fields' => 'names']);
        $yr = (is_array($t) && $t) ? trim((string) $t[0]) : '';
    }
    $klucz = ($mk[0] ?? '?') . '|' . ($se[0] ?? '?') . '|' . mb_strtolower(trim((string) $r->compl)) . '|' . $yr;

    if (!isset($idx[$klucz])) {
        $brak++;
        $brakiKluczy[$klucz] = ($brakiKluczy[$klucz] ?? 0) + 1;
        continue;
    }
    $trafione++;

    $doleje = array_diff_key($idx[$klucz], $ma);
    if (!$doleje) continue;

    $dolaneRazem += count($doleje);
    if (count($przyklady) < 8) {
        $przyklady[] = sprintf('#%d %s: %d -> %d (+%d)', $r->ID, mb_substr($r->post_title, 0, 40),
            count($ma), count($ma) + count($doleje), count($doleje));
    }

    if ($apply) {
        if (!get_post_meta($r->ID, '_asiaauto_extra_prep_orig', true)) {
            update_post_meta($r->ID, '_asiaauto_extra_prep_orig', wp_slash((string) $r->ep));
        }
        $nowe = $ma + $doleje;   // + zachowuje istniejące klucze, nie nadpisuje
        update_post_meta($r->ID, '_asiaauto_extra_prep', wp_slash(wp_json_encode($nowe, JSON_UNESCAPED_UNICODE)));
        update_post_meta($r->ID, '_asiaauto_spec_bank_at', gmdate('c'));
        update_post_meta($r->ID, '_asiaauto_spec_bank_count', count($doleje));
        update_post_meta($r->ID, '_asiaauto_spec_bank_key', $klucz);
    }
    $zmienione++;
}

printf("\nOferty publish z extra_prep < %d pol: %d\n", $prog, $ubogie);
printf("  dawca w banku:  %d\n  brak dawcy:     %d\n", $trafione, $brak);
printf("  do uzupelnienia: %d ofert, razem +%d pol (srednio +%d)\n",
    $zmienione, $dolaneRazem, $zmienione ? $dolaneRazem / $zmienione : 0);
foreach ($przyklady as $p) echo "    $p\n";

if ($brakiKluczy) {
    arsort($brakiKluczy);
    echo "\n  Najczestsze braki w banku (klucz => ile ofert):\n";
    foreach (array_slice($brakiKluczy, 0, 8, true) as $k => $n) echo "    $n x  $k\n";
}

if ($apply) {
    AsiaAuto_Logger::info("dolej-spec-z-banku: uzupelniono {$zmienione} ofert, +{$dolaneRazem} pol");
    echo "\n=== ZAPISANO ({$zmienione} ofert) ===\n";
} else {
    echo "\n=== DRY-RUN (dodaj: apply) ===\n";
}
