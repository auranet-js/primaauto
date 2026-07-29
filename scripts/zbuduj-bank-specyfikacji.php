<?php
/**
 * zbuduj-bank-specyfikacji.php — wyciąga bogate extra_prep z ofert do plików poza WordPressem.
 *
 * Po co: 319 wariantów (marka|seria|wersja|rocznik) występuje wyłącznie w ofertach martwych
 * u źródła. Gdy je wygasimy i kiedyś posprzątamy, ta specyfikacja przepada bezpowrotnie —
 * a jest jedynym dawcą dla nowych ofert dongchedi, które od 29.07 wchodzą z ~43 polami
 * zamiast ~342. Bank odcina los danych od losu ogłoszenia.
 *
 * TYLKO ODCZYT bazy. Zapisuje do uploads/asiaauto/spec-bank/<marka>.json.
 *
 * Użycie:  php zbuduj-bank-specyfikacji.php [min_pol]      (domyślnie 200)
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

$MIN    = isset($argv[1]) ? (int) $argv[1] : 200;
$upload = wp_get_upload_dir();
$dir    = $upload['basedir'] . '/asiaauto/spec-bank';

if (!wp_mkdir_p($dir)) {
    fwrite(STDERR, "Nie moge utworzyc katalogu: {$dir}\n");
    exit(1);
}

global $wpdb;
fwrite(STDERR, "Czytam oferty z extra_prep >= {$MIN} pol...\n");

$rows = $wpdb->get_results("
    SELECT p.ID, p.post_status, p.post_title,
           ep.meta_value  AS ep,
           src.meta_value AS source,
           inn.meta_value AS inner_id,
           ch.meta_value  AS werdykt,
           cy.meta_value  AS ca_year,
           cx.meta_value  AS compl
      FROM {$wpdb->posts} p
      JOIN {$wpdb->postmeta} ep  ON ep.post_id=p.ID  AND ep.meta_key='_asiaauto_extra_prep'
      JOIN {$wpdb->postmeta} src ON src.post_id=p.ID AND src.meta_key='_asiaauto_source'
 LEFT JOIN {$wpdb->postmeta} inn ON inn.post_id=p.ID AND inn.meta_key='_asiaauto_inner_id'
 LEFT JOIN {$wpdb->postmeta} ch  ON ch.post_id=p.ID  AND ch.meta_key='_asiaauto_source_check'
 LEFT JOIN {$wpdb->postmeta} cy  ON cy.post_id=p.ID  AND cy.meta_key='ca-year'
 LEFT JOIN {$wpdb->postmeta} cx  ON cx.post_id=p.ID  AND cx.meta_key='_asiaauto_complectation'
     WHERE p.post_type='listings'");

$bank = [];   // marka => [klucz => wpis]
$zywi = [];   // klucz => true (istnieje nosiciel żywy u źródła)
$skan = 0;

foreach ($rows as $r) {
    $fields = json_decode((string) $r->ep, true);
    if (!is_array($fields) || count($fields) < $MIN) {
        continue;
    }
    $skan++;

    $mk = wp_get_post_terms($r->ID, 'make',  ['fields' => 'slugs']);
    $se = wp_get_post_terms($r->ID, 'serie', ['fields' => 'slugs']);
    $marka = $mk[0] ?? 'bez-marki';
    $seria = $se[0] ?? 'bez-serii';

    // Rocznik: meta `ca-year` (tak jak merge-spec-from-twin.php), fallback na taksonomię o tej
    // samej nazwie. Bez rocznika klucz scalałby warianty z różnych lat w jeden wpis.
    $rocznik = trim((string) $r->ca_year);
    if ($rocznik === '') {
        $cy = wp_get_post_terms($r->ID, 'ca-year', ['fields' => 'names']);
        $rocznik = (is_array($cy) && $cy) ? trim((string) $cy[0]) : '';
    }
    $klucz = $marka . '|' . $seria . '|' . mb_strtolower(trim((string) $r->compl)) . '|' . $rocznik;

    $martwa = in_array((string) $r->werdykt, ['usunieta', 'wydmuszka'], true);
    if (!$martwa) {
        $zywi[$klucz] = true;
    }

    // Per wariant trzymamy NAJBOGATSZY znaleziony komplet.
    $stary = $bank[$marka][$klucz]['pol'] ?? -1;
    if (count($fields) > $stary) {
        $bank[$marka][$klucz] = [
            'klucz'      => $klucz,
            'marka'      => $marka,
            'seria'      => $seria,
            'wersja'     => (string) $r->compl,
            'rocznik'    => $rocznik,
            'pol'        => count($fields),
            'zrodlo'     => (string) $r->source,
            'z_oferty'   => (int) $r->ID,
            'inner_id'   => (string) $r->inner_id,
            'tytul'      => (string) $r->post_title,
            'extra_prep' => $fields,
        ];
    }
}

// Oznacz sieroty — warianty bez żadnego żywego nosiciela. To one są powodem całej operacji.
$sieroty = 0;
$warianty = 0;
foreach ($bank as $marka => $wpisy) {
    foreach ($wpisy as $k => $w) {
        $bank[$marka][$k]['sierota'] = !isset($zywi[$k]);
        if (!isset($zywi[$k])) $sieroty++;
        $warianty++;
    }
}

$stamp = gmdate('c');
$bajty = 0;
$manifest = ['zbudowany' => $stamp, 'min_pol' => $MIN, 'warianty' => $warianty,
             'sieroty' => $sieroty, 'marki' => []];

foreach ($bank as $marka => $wpisy) {
    foreach ($wpisy as $k => $w) {
        $bank[$marka][$k]['zbudowany'] = $stamp;
    }
    $plik = $dir . '/' . $marka . '.json';
    $json = wp_json_encode(array_values($wpisy), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    file_put_contents($plik, $json);
    $bajty += strlen($json);
    $manifest['marki'][$marka] = [
        'plik'     => basename($plik),
        'warianty' => count($wpisy),
        'sieroty'  => count(array_filter($wpisy, fn($w) => $w['sierota'])),
    ];
}
ksort($manifest['marki']);
file_put_contents($dir . '/_manifest.json', wp_json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

fwrite(STDERR, sprintf("\nPrzeskanowane oferty z bogatym extra_prep: %d\n", $skan));
fwrite(STDERR, sprintf("Warianty w banku: %d  (w tym sierot bez zywego nosiciela: %d)\n", $warianty, $sieroty));
fwrite(STDERR, sprintf("Marek: %d   rozmiar: %.1f MB\n", count($bank), $bajty / 1048576));
fwrite(STDERR, "Katalog: {$dir}\n");
