<?php
/**
 * Etap 3c v2: migracja term_relationships make — PER-SERIE.
 * Dla każdego wiersza v6.1, znajdź listingi po serie-term i zamień ich make-term.
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

// Cache marki term IDs
$allMakes = [];
foreach ($wpdb->get_results("SELECT t.term_id, t.name, tt.term_taxonomy_id FROM {$pfx}terms t JOIN {$pfx}term_taxonomy tt ON t.term_id=tt.term_id WHERE tt.taxonomy='make'") as $r) {
    $allMakes[mb_strtolower(trim($r->name))] = ['term_id' => (int)$r->term_id, 'tt_id' => (int)$r->term_taxonomy_id];
}

$stats = ['skip' => 0, 'same_make' => 0, 'no_serie_term' => 0, 'migrated' => 0];
$totalListings = 0;

foreach ($rows as $r) {
    $no = $r[0];
    $markaCN = trim($r[2]); $markaEU = trim($r[3]);
    $modelCN = trim($r[4]); $serieEU = trim($r[5]);
    if ($markaCN === '' || $modelCN === '') { $stats['skip']++; continue; }
    if (mb_strtolower($markaCN) === mb_strtolower($markaEU)) { $stats['same_make']++; continue; }

    $oldMake = $allMakes[mb_strtolower($markaCN)] ?? null;
    $newMake = $allMakes[mb_strtolower($markaEU)] ?? null;
    if (!$oldMake || !$newMake) {
        echo "  [#$no] ERR: brak termu make '$markaCN' lub '$markaEU'\n";
        continue;
    }

    // Znajdź term serie EU bezpośrednio SQL (parent restriction)
    $slugModel = basename(rtrim(trim($r[8]), '/'));
    $serieRow = $wpdb->get_row($wpdb->prepare(
        "SELECT t.term_id, tt.term_taxonomy_id
         FROM {$pfx}terms t
         JOIN {$pfx}term_taxonomy tt ON t.term_id=tt.term_id
         WHERE tt.taxonomy='serie' AND t.slug=%s AND tt.parent=%d",
        $slugModel, $newMake['term_id']
    ));
    if (!$serieRow) {
        $stats['no_serie_term']++;
        echo "  [#$no] ERR: brak serie-term slug=$slugModel pod make={$markaEU}\n";
        continue;
    }
    $serieTtId = (int)$serieRow->term_taxonomy_id;

    // Znajdź listingi które mają ten serie-term w term_relationships
    $listings = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT tr.object_id
         FROM {$pfx}term_relationships tr
         JOIN {$pfx}posts p ON p.ID = tr.object_id
         WHERE tr.term_taxonomy_id = %d AND p.post_type = 'listings'",
        $serieTtId
    ));

    if (empty($listings)) continue;

    printf("  [#%-3s] %-25s → %-20s  serie=%-20s  listings=%d", $no, $markaCN, $markaEU, $slugModel, count($listings));

    if ($mode === 'APPLY') {
        $in = implode(',', array_map('intval', $listings));
        // Insert nowy make-rel (ignore duplicates), potem delete stary
        $ins = $wpdb->query(
            "INSERT IGNORE INTO {$pfx}term_relationships (object_id, term_taxonomy_id, term_order)
             SELECT tr.object_id, {$newMake['tt_id']}, tr.term_order
             FROM {$pfx}term_relationships tr
             WHERE tr.term_taxonomy_id = {$oldMake['tt_id']}
               AND tr.object_id IN ($in)"
        );
        $del = $wpdb->query(
            "DELETE FROM {$pfx}term_relationships
             WHERE term_taxonomy_id = {$oldMake['tt_id']}
               AND object_id IN ($in)"
        );
        printf("  ins=%d del=%d\n", $ins, $del);
    } else {
        echo "  DRY_RUN\n";
    }
    $stats['migrated']++;
    $totalListings += count($listings);
}

if ($mode === 'APPLY') {
    $tts = $wpdb->get_col("SELECT term_taxonomy_id FROM {$pfx}term_taxonomy WHERE taxonomy='make'");
    wp_update_term_count_now($tts, 'make');
    echo "\nRecount make counts: OK\n";
}

echo "\n=== Statystyki ===\n";
foreach ($stats as $k => $v) printf("  %-15s %d\n", $k, $v);
echo "  total_listings   $totalListings\n";
