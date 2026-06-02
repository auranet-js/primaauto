<?php
/**
 * build-dsa-pagefeed.php — generator page-feed dla kampanii Google Ads DSA „Import modele z Chin".
 *
 * ZAPISANY FILTR PROJEKTU (kanoniczne kryterium DSA-eligible hub):
 *   serie (model) z >=1 publish listingiem ca-year=GATE_YEAR  (gate „jak nowe")
 *   MINUS marki nie-chińskie (NON_CHINESE)
 *   MINUS modele aktywne w SKAG-1/SKAG-2 (SKAG_EXCLUDE — anty-kanibalizacja)
 *   dedup po (make, serie_name) -> najwyższy licznik
 * Landing = model-hub /samochody/{make_slug}/{serie_slug}/  (NIE marka-hub, NIE /oferta/).
 *
 * Użycie:  wp eval-file scripts/build-dsa-pagefeed.php > tmp/dsa-pagefeed.csv
 * Po wygenerowaniu: HTTP-check (zostaw 200), upload jako page feed do kampanii DSA.
 *
 * ADR: docs/decyzje/2026-06-02-google-ads-faza1-dsa.md
 */

if (!defined('ABSPATH')) { fwrite(STDERR, "Uruchom przez: wp eval-file scripts/build-dsa-pagefeed.php\n"); exit(1); }

$GATE_YEAR = '2026';
$BASE = 'https://primaauto.com.pl';

// marki nie-chińskie (make slug) — wykluczone z DSA spójnie z polityką SKAG
$NON_CHINESE = ['volkswagen','volvo','nissan','mazda','audi','mg','smart','mini','lotus'];

// modele aktywne w SKAG-1/2 (make_slug => [serie_slug,...]) — aktualizuj przy zmianie stocku SKAG
$SKAG_EXCLUDE = [
    'byd'    => ['leopard-5','leopard-7','shark-6'],
    'denza'  => ['n9-dm-i','z9-gt-dm-i'],
    'deepal' => ['g318'],
    'jetour' => ['g700'],
    'zeekr'  => ['001','8x','zeekr-8x'],
    'geely'  => ['monjaro'],
];

global $wpdb;
$p = $wpdb->prefix;
$rows = $wpdb->get_results($wpdb->prepare("
    SELECT tmk.slug AS make_slug, ts.slug AS serie_slug, ts.name AS serie_name, COUNT(DISTINCT po.ID) AS c
    FROM {$p}terms ts
    JOIN {$p}term_taxonomy tts ON tts.term_id=ts.term_id AND tts.taxonomy='serie'
    JOIN {$p}term_relationships trs ON trs.term_taxonomy_id=tts.term_taxonomy_id
    JOIN {$p}posts po ON po.ID=trs.object_id AND po.post_type='listings' AND po.post_status='publish'
    JOIN {$p}postmeta y ON y.post_id=po.ID AND y.meta_key='ca-year' AND y.meta_value=%s
    JOIN {$p}term_relationships trm ON trm.object_id=po.ID
    JOIN {$p}term_taxonomy ttm ON ttm.term_taxonomy_id=trm.term_taxonomy_id AND ttm.taxonomy='make'
    JOIN {$p}terms tmk ON tmk.term_id=ttm.term_id
    GROUP BY ts.term_id, tmk.term_id
", $GATE_YEAR));

$kept = []; // key make|serie_name => [serie_slug, c]
foreach ($rows as $r) {
    if (in_array($r->make_slug, $NON_CHINESE, true)) continue;
    if (isset($SKAG_EXCLUDE[$r->make_slug]) && in_array($r->serie_slug, $SKAG_EXCLUDE[$r->make_slug], true)) continue;
    $key = $r->make_slug.'|'.html_entity_decode($r->serie_name);
    if (!isset($kept[$key]) || (int)$r->c > $kept[$key][1]) $kept[$key] = [$r->serie_slug, (int)$r->c];
}

ksort($kept);
echo "Page URL,Custom label\n";
foreach ($kept as $key => $v) {
    list($make_slug, $serie_name) = explode('|', $key, 2);
    echo "{$BASE}/samochody/{$make_slug}/{$v[0]}/,{$make_slug}|{$serie_name}\n";
}
fwrite(STDERR, "Wygenerowano ".count($kept)." hubów (gate {$GATE_YEAR}, minus nie-chińskie + SKAG). Po tym: HTTP-check 200.\n");
