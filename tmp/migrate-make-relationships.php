<?php
/**
 * Etap 3c: migracja term_relationships dla make — przenieś listingi z "starych" marek CN do docelowych EU.
 * Dry-run domyślnie. APPLY jako argv.
 */

require_once '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';
$mode = ($argv[1] ?? 'DRY_RUN') === 'APPLY' ? 'APPLY' : 'DRY_RUN';
echo ">>> MODE: $mode\n";

global $wpdb;
$pfx = $wpdb->prefix;

// Wczytaj v6.1
$fp = fopen(__DIR__ . '/mapowanie-marek-modeli-v6.1.csv', 'r');
fgetcsv($fp);
$rows = [];
while (($r = fgetcsv($fp)) !== false) $rows[] = $r;
fclose($fp);

// Budujemy mapę: marka_CN → marka_EU (gdy różne)
$makeMap = [];
foreach ($rows as $r) {
    $cn = trim($r[2]); $eu = trim($r[3]);
    if ($cn === '' || $eu === '' || mb_strtolower($cn) === mb_strtolower($eu)) continue;
    $makeMap[mb_strtolower($cn)] = $eu;
}

echo "Mapa marek (CN → EU):\n";
foreach ($makeMap as $cn => $eu) printf("  %-25s → %s\n", $cn, $eu);

// Cache term IDs
$allMakes = [];
$rows_db = $wpdb->get_results("SELECT t.term_id, t.name, tt.term_taxonomy_id FROM {$pfx}terms t JOIN {$pfx}term_taxonomy tt ON t.term_id=tt.term_id WHERE tt.taxonomy='make'");
foreach ($rows_db as $r) {
    $allMakes[mb_strtolower(trim($r->name))] = ['term_id' => (int)$r->term_id, 'tt_id' => (int)$r->term_taxonomy_id];
}

$totalUpdated = 0;
foreach ($makeMap as $cnLower => $euName) {
    $old = $allMakes[$cnLower] ?? null;
    $new = $allMakes[mb_strtolower($euName)] ?? null;
    if (!$old) { echo "  [skip] brak starego termu dla '$cnLower'\n"; continue; }
    if (!$new) { echo "  [skip] brak nowego termu dla '$euName'\n"; continue; }

    $oldTt = $old['tt_id']; $newTt = $new['tt_id'];

    // Sprawdź ile listingów ma stary term
    $count = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT tr.object_id)
         FROM {$pfx}term_relationships tr
         JOIN {$pfx}posts p ON p.ID = tr.object_id
         WHERE tr.term_taxonomy_id = %d AND p.post_type = 'listings'",
        $oldTt
    ));

    if ($count === 0) continue;

    printf("  %-25s → %-20s  listings: %d  (tt %d → %d)  ", $cnLower, $euName, $count, $oldTt, $newTt);

    if ($mode === 'APPLY') {
        // UPDATE: przepisz relację (ale tylko dla listings, innych typów nie ruszamy)
        // Uwaga na duplikaty: jeśli listing miał już obie relacje, UPDATE spowoduje kolizję PRIMARY KEY
        // Rozwiązanie: INSERT IGNORE new, potem DELETE old
        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$pfx}term_relationships (object_id, term_taxonomy_id, term_order)
             SELECT tr.object_id, %d, tr.term_order
             FROM {$pfx}term_relationships tr
             JOIN {$pfx}posts p ON p.ID = tr.object_id
             WHERE tr.term_taxonomy_id = %d AND p.post_type = 'listings'",
            $newTt, $oldTt
        ));
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE tr FROM {$pfx}term_relationships tr
             JOIN {$pfx}posts p ON p.ID = tr.object_id
             WHERE tr.term_taxonomy_id = %d AND p.post_type = 'listings'",
            $oldTt
        ));
        printf("inserted=%d deleted=%d\n", $inserted, $deleted);
    } else {
        echo "DRY_RUN\n";
    }
    $totalUpdated += $count;
}

if ($mode === 'APPLY') {
    // Recount
    $tts = $wpdb->get_col("SELECT term_taxonomy_id FROM {$pfx}term_taxonomy WHERE taxonomy='make'");
    wp_update_term_count_now($tts, 'make');
    echo "\nRecount make counts: OK\n";
}

echo "\nTotal listings affected: $totalUpdated\n";
