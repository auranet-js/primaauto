<?php
// Merge 11 post-fix orphan duplicates → v6.1 keepers
// Run: wp eval-file (na produkcji)
global $wpdb;

$pairs = [
    [6532, 4824, 'zeekr-9x', '9x'],
    [5080, 6494, 'voyah-taishan', 'taishan'],
    [6551, 5523, 'leopard-5-denza-b5', 'leopard-5'],
    [6534, 3702, 'seal-u-dm-i-song-plus', 'seal-u-dm-i'],
    [6533, 5522, 'leopard-3-tai-3-fcb', 'leopard-3'],
    [6545, 3746, 'sealion-8-dm-i-tang-l', 'sealion-8-dm-i'],
    [6548, 3706, 'atto-3-yuan-plus', 'atto-3'],
    [6549, 6258, 'e008', 'e-008'],
    [6546, 5521, 'leopard-8-denza-b8', 'leopard-8'],
    [6541, 3703, 'sealion-8-tang-l-ev', 'sealion-8-ev'],
    [6547, 6066, 'leopard-7-tai-7-fcb-phev', 'leopard-7'],
];

$summary = [];

foreach ($pairs as [$orphan_tid, $keeper_tid, $orphan_slug, $keeper_slug]) {
    $orphan_ttid = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='serie'", $orphan_tid));
    $keeper_ttid = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='serie'", $keeper_tid));

    if (!$orphan_ttid || !$keeper_ttid) {
        $summary[] = "FAIL #$orphan_tid: ttid lookup";
        continue;
    }

    // 1. INSERT IGNORE — relacje listingów do keeper'a (skip jeśli już są)
    $inserted = $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO {$wpdb->term_relationships} (object_id, term_taxonomy_id, term_order)
         SELECT object_id, %d, term_order FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d",
        $keeper_ttid, $orphan_ttid));

    // 2. DELETE relacji orphan
    $deleted_rel = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $orphan_ttid));

    // 3. Delete orphan term (cleanup termmeta + tt + term)
    $del_result = wp_delete_term($orphan_tid, 'serie');

    // 4. Recount keeper
    wp_update_term_count_now([$keeper_ttid], 'serie');

    $summary[] = sprintf(
        "OK #%d (%s) → #%d (%s): re-tag=%d, removed_rel=%d, term_deleted=%s",
        $orphan_tid, $orphan_slug, $keeper_tid, $keeper_slug,
        $inserted, $deleted_rel, var_export($del_result, true)
    );
}

// Cache flush
delete_option('asiaauto_listing_counts');
clean_taxonomy_cache('serie');
flush_rewrite_rules(false);

echo "=== MERGE COMPLETE ===\n";
foreach ($summary as $s) echo $s . "\n";

// Sanity check: pokazuje liczby po merge
echo "\n=== POST-MERGE COUNTS (BYD) ===\n";
$rows = $wpdb->get_results("
    SELECT t.term_id, t.name, t.slug, tt.count
    FROM {$wpdb->terms} t
    JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id
    WHERE tt.taxonomy='serie' AND tt.parent=3697 AND tt.count>0
    ORDER BY t.name");
foreach ($rows as $r) printf("  #%d %s (slug=%s, count=%d)\n", $r->term_id, $r->name, $r->slug, $r->count);
