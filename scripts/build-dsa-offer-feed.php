<?php
/**
 * Generator page feedu DSA celujacego w NAJTANSZA OFERTE per model (2026-07-16).
 *
 * Zmiana strategii (decyzja Janka 16.07): DSA przestaje celowac w huby modeli,
 * celuje w konkretny najtanszy egzemplarz. Powod: naglowek DSA = title strony docelowej,
 * a title huba jest informacyjny („Model — cena w Polsce i import z Chin"), bo musi rankowac
 * w organicu. Title oferty niesie konkret: „Denza N8L DM Flagship Version 2025 - 246 000 PLN,
 * 8 600 km | Prima-Auto". Meta liczone przy renderze => zawsze aktualne, bez crona na copy.
 *
 * Filtr /samochody/?marka=X&model=Y odrzucony: ma noindex, a DSA korzysta z indeksu Google.
 *
 * Gate spojny z build-dsa-pagefeed.php: rocznik 2025/2026, NON_CHINESE, SKAG_EXCLUDE.
 * Rozne od hubowego: bez MIN_COUNT (kazdy model z >=1 sztuka „jak nowa" ma najtansza sztuke).
 *
 * Uzycie: wp eval-file scripts/build-dsa-offer-feed.php > tmp/dsa-offer-feed.csv
 * ADR: docs/decyzje/2026-07-16-dsa-feed-na-oferty.md
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "Uruchom przez: wp eval-file scripts/build-dsa-offer-feed.php\n"); exit(1); }

$GATE_YEARS = ['2025','2026'];
$BASE = 'https://primaauto.com.pl';
$NON_CHINESE = ['volkswagen','volvo','nissan','mazda','audi','mg','smart','mini','lotus','lotus-cars','toyota','iveco'];
$SKAG_EXCLUDE = [
    'byd'    => ['leopard-3','leopard-5','leopard-7','shark-6','sealion-8-dm-i','song-l-ev'],
    'denza'  => ['n8l-dm','n9-dm-i','z9-gt-dm-i'],
    'deepal' => ['g318'],
    'jetour' => ['g700','t2-c-dm'],
    'zeekr'  => ['001','7x','8x','9x','zeekr-8x','zeekr-9x'],
    'geely'  => ['monjaro'],
    'xiaomi' => ['yu7'],
    'exeed'  => ['vx'],
];
// modele z chinskimi znakami w tytulach ofert — wycinamy do czasu naprawy (16.07)
$CJK_BLOCK = ['exeed/es', 'geely/a7-em'];

global $wpdb;
$p = $wpdb->prefix;
$year_ph = implode(',', array_fill(0, count($GATE_YEARS), '%s'));
$rows = $wpdb->get_results($wpdb->prepare("
    SELECT tmk.slug AS make_slug, ts.slug AS serie_slug, po.post_name AS slug, po.post_title AS title,
           CAST(pmp.meta_value AS UNSIGNED) AS price
    FROM {$p}posts po
    JOIN {$p}postmeta pmp ON pmp.post_id=po.ID AND pmp.meta_key='price' AND pmp.meta_value REGEXP '^[0-9]+$'
    JOIN {$p}postmeta y ON y.post_id=po.ID AND y.meta_key='ca-year' AND y.meta_value IN ($year_ph)
    JOIN {$p}term_relationships trs ON trs.object_id=po.ID
    JOIN {$p}term_taxonomy tts ON tts.term_taxonomy_id=trs.term_taxonomy_id AND tts.taxonomy='serie'
    JOIN {$p}terms ts ON ts.term_id=tts.term_id
    JOIN {$p}term_relationships trm ON trm.object_id=po.ID
    JOIN {$p}term_taxonomy ttm ON ttm.term_taxonomy_id=trm.term_taxonomy_id AND ttm.taxonomy='make'
    JOIN {$p}terms tmk ON tmk.term_id=ttm.term_id
    WHERE po.post_type='listings' AND po.post_status='publish'
    ORDER BY price ASC, po.ID ASC
", $GATE_YEARS));

$best = [];
foreach ($rows as $r) {
    $key = $r->make_slug . '/' . $r->serie_slug;
    if (in_array($r->make_slug, $NON_CHINESE, true)) continue;
    if (isset($SKAG_EXCLUDE[$r->make_slug]) && in_array($r->serie_slug, $SKAG_EXCLUDE[$r->make_slug], true)) continue;
    if (in_array($key, $CJK_BLOCK, true)) continue;
    if (isset($best[$key])) continue;           // ORDER BY price ASC => pierwszy = najtanszy
    $best[$key] = $r;
}
echo "Page URL,Custom label\n";
foreach ($best as $key => $r) {
    printf("%s/oferta/%s/,dsa2026\n", $BASE, $r->slug);
}
fwrite(STDERR, sprintf("modeli: %d\n", count($best)));
