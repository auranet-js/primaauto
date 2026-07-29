<?php
/**
 * gasz-martwe-oferty.php — wygasza dzienną porcję ofert, których nie ma już u źródła.
 *
 * Bierze wyłącznie oferty z werdyktem przemiału (`_asiaauto_source_check` = usunieta|wydmuszka),
 * więc nie odpytuje API i nic nie kosztuje. Gasi do `draft` przez AsiaAuto_Rotation::markRemoved(),
 * co daje 301 na hub modelu (class-asiaauto-redirects.php) zamiast 404.
 *
 * OCHRONA — trzy warstwy, wszystkie liczone JAWNIE w zapytaniu:
 *   1. aktywne zamówienie  — markRemoved() tego NIE sprawdza (listingsWithActiveOrders działa
 *                            dopiero przy trwałym kasowaniu z kosza); pomijamy sami,
 *   2. rezerwacja          — markRemoved() sam nie zmieni statusu, ale pomijamy wcześniej,
 *   3. wpis ręczny         — _asiaauto_manual_import / _asiaauto_manual_entry.
 *
 * Użycie:  php gasz-martwe-oferty.php [limit] [apply]      (bez `apply` = dry-run)
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

$limit = isset($argv[1]) ? max(1, (int) $argv[1]) : 200;
$apply = in_array('apply', $argv, true);

global $wpdb;

$rows = $wpdb->get_results($wpdb->prepare("
    SELECT p.ID, p.post_title, p.post_name,
           ch.meta_value AS werdykt,
           ls.meta_value AS last_sync
      FROM {$wpdb->posts} p
      JOIN {$wpdb->postmeta} src ON src.post_id=p.ID AND src.meta_key='_asiaauto_source' AND src.meta_value='dongchedi'
      JOIN {$wpdb->postmeta} ch  ON ch.post_id=p.ID  AND ch.meta_key='_asiaauto_source_check'
                                AND ch.meta_value IN ('usunieta','wydmuszka')
 LEFT JOIN {$wpdb->postmeta} ls  ON ls.post_id=p.ID  AND ls.meta_key='_asiaauto_last_sync'
     WHERE p.post_type='listings' AND p.post_status='publish'
       -- 1. aktywne zamowienie
       AND NOT EXISTS (
           SELECT 1 FROM {$wpdb->postmeta} om
             INNER JOIN {$wpdb->posts} o ON o.ID=om.post_id
                    AND o.post_type='asiaauto_order' AND o.post_status='publish'
             INNER JOIN {$wpdb->postmeta} s ON s.post_id=o.ID AND s.meta_key='_order_status'
            WHERE om.meta_key='_order_listing_id' AND om.meta_value=p.ID
              AND s.meta_value NOT IN ('anulowane','odrzucone'))
       -- 2. rezerwacja
       AND NOT EXISTS (
           SELECT 1 FROM {$wpdb->postmeta} r
            WHERE r.post_id=p.ID AND r.meta_key='_asiaauto_reservation_status' AND r.meta_value<>'')
       -- 3. wpis reczny
       AND NOT EXISTS (
           SELECT 1 FROM {$wpdb->postmeta} mm
            WHERE mm.post_id=p.ID
              AND mm.meta_key IN ('_asiaauto_manual_import','_asiaauto_manual_entry')
              AND mm.meta_value='1')
     ORDER BY COALESCE(ls.meta_value,'1970-01-01') ASC
     LIMIT %d", $limit));

$zostalo = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->posts} p
      JOIN {$wpdb->postmeta} src ON src.post_id=p.ID AND src.meta_key='_asiaauto_source' AND src.meta_value='dongchedi'
      JOIN {$wpdb->postmeta} ch  ON ch.post_id=p.ID  AND ch.meta_key='_asiaauto_source_check'
                                AND ch.meta_value IN ('usunieta','wydmuszka')
     WHERE p.post_type='listings' AND p.post_status='publish'");

printf("Do wygaszenia w tej porcji: %d (limit %d)   pozostalo martwych publish: %d\n",
    count($rows), $limit, $zostalo);

if (!$apply) {
    echo "\n=== DRY-RUN (dodaj: apply) ===\n";
    foreach (array_slice($rows, 0, 10) as $r) {
        printf("  #%d  %-52s  last_sync %s\n", $r->ID, mb_substr($r->post_title, 0, 52), substr((string) $r->last_sync, 0, 10));
    }
    if (count($rows) > 10) printf("  … i %d dalszych\n", count($rows) - 10);
    exit(0);
}

$rotation = new AsiaAuto_Rotation();
$ok = 0;
foreach ($rows as $r) {
    $rotation->markRemoved((int) $r->ID, 'source-gone');
    // markRemoved() zostawia ofertę z rezerwacją na publish — tu takich nie ma (odfiltrowane),
    // ale sprawdzamy wynik, żeby licznik nie kłamał.
    if (get_post_status((int) $r->ID) === 'draft') {
        $ok++;
    } else {
        AsiaAuto_Logger::warning("gasz-martwe: post #{$r->ID} nie zmienil statusu na draft");
    }
}

AsiaAuto_Logger::info("gasz-martwe: wygaszono {$ok}/" . count($rows) . " ofert (source-gone), pozostalo " . ($zostalo - $ok));
printf("\n=== WYGASZONO %d z %d ===  pozostalo martwych publish: %d\n", $ok, count($rows), $zostalo - $ok);
