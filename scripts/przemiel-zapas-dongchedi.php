<?php
/**
 * Przemiał zapasu dongchedi — sprawdza u źródła, które oferty jeszcze istnieją.
 *
 * NIC NIE GASI. Odpytuje getOffer() po inner_id i stempluje werdykt w meta:
 *   _asiaauto_source_check     zywa | usunieta | wydmuszka | blad
 *   _asiaauto_source_check_at  znacznik czasu ISO
 *
 * Werdykt zużywa potem cron gaszenia — bez ponownego płacenia za API.
 * Wznawialny: oferty sprawdzone dziś są pomijane, więc po padzie wystarczy odpalić ponownie.
 *
 * Użycie:  php przemiel-zapas-dongchedi.php [limit]
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

if (!defined('ASIAAUTO_API_KEY') || !defined('ASIAAUTO_API_BASE_URL')) {
    fwrite(STDERR, "Brak ASIAAUTO_API_KEY / ASIAAUTO_API_BASE_URL w wp-config.php\n");
    exit(1);
}
$key   = ASIAAUTO_API_KEY;
$base  = ASIAAUTO_API_BASE_URL;
$limit = isset($argv[1]) ? (int) $argv[1] : 0;
$today = gmdate('Y-m-d');

global $wpdb;

/* Ochrona — te oferty nie mogą zostać wygaszone, więc oznaczamy je od razu w CSV.
   markRemoved() sam z siebie broni tylko rezerwacji, dlatego zamówienia liczymy tu jawnie
   (tą samą logiką co AsiaAuto_Rotation::listingsWithActiveOrders, 2026-07-20). */
$ordered = array_flip(array_map('intval', $wpdb->get_col(
    "SELECT DISTINCT m.meta_value
       FROM {$wpdb->postmeta} m
       INNER JOIN {$wpdb->posts} o ON o.ID = m.post_id
              AND o.post_type = 'asiaauto_order' AND o.post_status = 'publish'
       INNER JOIN {$wpdb->postmeta} s ON s.post_id = o.ID AND s.meta_key = '_order_status'
      WHERE m.meta_key = '_order_listing_id' AND m.meta_value > 0
        AND s.meta_value NOT IN ('anulowane', 'odrzucone')"
)));

$sql = "
    SELECT p.ID, p.post_title, p.post_name,
           inn.meta_value AS inner_id,
           ls.meta_value  AS last_sync,
           op.meta_value  AS price_cny,
           res.meta_value AS reservation,
           GREATEST(COALESCE(mi.meta_value,'0'), COALESCE(me.meta_value,'0')) AS manual
      FROM {$wpdb->posts} p
      JOIN {$wpdb->postmeta} src ON src.post_id=p.ID AND src.meta_key='_asiaauto_source' AND src.meta_value='dongchedi'
      JOIN {$wpdb->postmeta} inn ON inn.post_id=p.ID AND inn.meta_key='_asiaauto_inner_id'
 LEFT JOIN {$wpdb->postmeta} ls  ON ls.post_id=p.ID  AND ls.meta_key='_asiaauto_last_sync'
 LEFT JOIN {$wpdb->postmeta} op  ON op.post_id=p.ID  AND op.meta_key='_asiaauto_original_price'
 LEFT JOIN {$wpdb->postmeta} res ON res.post_id=p.ID AND res.meta_key='_asiaauto_reservation_status'
 LEFT JOIN {$wpdb->postmeta} mi  ON mi.post_id=p.ID  AND mi.meta_key='_asiaauto_manual_import'
 LEFT JOIN {$wpdb->postmeta} me  ON me.post_id=p.ID  AND me.meta_key='_asiaauto_manual_entry'
 LEFT JOIN {$wpdb->postmeta} chk ON chk.post_id=p.ID AND chk.meta_key='_asiaauto_source_check_at'
     WHERE p.post_type='listings' AND p.post_status='publish'
       AND (chk.meta_value IS NULL OR chk.meta_value NOT LIKE '{$today}%')
     ORDER BY COALESCE(ls.meta_value,'1970-01-01') ASC";
if ($limit > 0) {
    $sql .= " LIMIT {$limit}";
}
$rows = $wpdb->get_results($sql);

$csvPath = '/home/host476470/projekty/primaauto/tmp/przemial-dongchedi-' . $today . '.csv';
$fresh   = !file_exists($csvPath);
$csv     = fopen($csvPath, 'a');
if ($fresh) {
    fputcsv($csv, ['ID','inner_id','tytul','slug','last_sync','dni_od_sync','werdykt',
                   'chroniona','powod_ochrony','cena_nasza_CNY','cena_zrodlo_CNY','delta_proc']);
}

$stat = ['zywa'=>0,'usunieta'=>0,'wydmuszka'=>0,'blad'=>0];
$prot = 0;
$t0   = time();
$n    = count($rows);
fwrite(STDERR, "Do sprawdzenia: {$n} ofert\n");

foreach ($rows as $i => $r) {
    $resp = wp_remote_get("{$base}/api/v2/dongchedi/offer?inner_id={$r->inner_id}&api_key={$key}", ['timeout' => 30]);
    $code = is_wp_error($resp) ? 0 : (int) wp_remote_retrieve_response_code($resp);
    $data = is_wp_error($resp) ? [] : (json_decode(wp_remote_retrieve_body($resp), true) ?: []);
    $data = $data['result'] ?? $data;

    $srcPrice = null;
    if ($code === 404) {
        $verdict = 'usunieta';
    } elseif ($code === 200 && AsiaAuto_Importer::isEmptyShell((array) $data)) {
        $verdict = 'wydmuszka';
    } elseif ($code === 200) {
        $verdict  = 'zywa';
        $srcPrice = isset($data['price']) ? (float) $data['price'] : null;
    } else {
        $verdict = 'blad';
    }
    $stat[$verdict]++;

    // Werdykt „blad" nie jest wiedzą — nie stemplujemy, żeby kolejny bieg spróbował ponownie.
    if ($verdict !== 'blad') {
        update_post_meta($r->ID, '_asiaauto_source_check', $verdict);
        update_post_meta($r->ID, '_asiaauto_source_check_at', gmdate('c'));
    }

    $powod = [];
    if (isset($ordered[(int) $r->ID])) $powod[] = 'aktywne zamowienie';
    if (!empty($r->reservation))       $powod[] = 'rezerwacja';
    if ((string) $r->manual === '1')   $powod[] = 'wpis reczny';
    if ($powod) $prot++;

    $dni = $r->last_sync ? (int) floor((time() - strtotime($r->last_sync)) / 86400) : '';
    $delta = '';
    if ($verdict === 'zywa' && (float) $r->price_cny > 0 && $srcPrice > 0) {
        $delta = round(($srcPrice - (float) $r->price_cny) / (float) $r->price_cny * 100, 1);
    }

    fputcsv($csv, [$r->ID, $r->inner_id, $r->post_title, $r->post_name, $r->last_sync, $dni, $verdict,
                   $powod ? 'TAK' : '', implode(' + ', $powod), $r->price_cny, $srcPrice, $delta]);

    if (($i + 1) % 50 === 0) {
        $el  = max(time() - $t0, 1);
        $eta = (int) round(($n - $i - 1) / (($i + 1) / $el) / 60);
        fwrite(STDERR, sprintf("  %d/%d  zywe %d / usuniete %d / wydmuszki %d / bledy %d   ETA ~%d min\n",
            $i + 1, $n, $stat['zywa'], $stat['usunieta'], $stat['wydmuszka'], $stat['blad'], $eta));
    }
    usleep(500_000); // throttle 500 ms — sync dongchedi biegnie rownolegle, nie wywolujemy 429
}
fclose($csv);

$martwe = $stat['usunieta'] + $stat['wydmuszka'];
$spr    = array_sum($stat);
fwrite(STDERR, "\n=== KONIEC ===\n");
fwrite(STDERR, sprintf("Sprawdzone: %d w %d min\n", $spr, (int) round((time() - $t0) / 60)));
fwrite(STDERR, sprintf("  zywe       %d\n  usuniete   %d\n  wydmuszki  %d\n  bledy      %d\n",
    $stat['zywa'], $stat['usunieta'], $stat['wydmuszka'], $stat['blad']));
fwrite(STDERR, sprintf("MARTWE RAZEM: %d (%.1f%%)   chronione w calej puli: %d\n",
    $martwe, $spr ? $martwe / $spr * 100 : 0, $prot));
fwrite(STDERR, "CSV: {$csvPath}\n");
