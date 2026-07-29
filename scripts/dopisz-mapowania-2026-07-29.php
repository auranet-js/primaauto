<?php
/**
 * Dopisanie mapowań dla marek z ręcznego zaciągu 2026-07-28.
 *
 * Powód: ręczny import che168 nie ma guarda mapowania, więc Foton / Maxus / Mengshi /
 * Mercedes / Huadong weszły jako sieroty i importer utworzył termy fallbackiem (marka CJK,
 * parent=0, prefiks marki w nazwie). Bez wpisów w mapach każdy kolejny import odtworzy śmieci,
 * a sync tych marek w ogóle nie wpuści (guard isMappedForImport).
 *
 * Alias w che168-model-map jest MARTWY bez pary mark_eu|serie_eu w brand-mappingu — sigToKey()
 * tłumaczy entry z powrotem na literalny klucz brand-mappingu. Stąd wpisy w OBU plikach.
 *
 * Użycie: php scripts/dopisz-mapowania-2026-07-29.php [apply]
 */

$DIR   = '/home/host476470/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/data/';
$APPLY = in_array('apply', $argv, true);
$STAMP = '2026-07-29-zaciag';

/* Klucze w formie, w jakiej che168 REALNIE wysyła mark|model — zmierzone getOffer() na 11 ofertach. */
$che168 = [
    'Mercedes-Benz|EQE SUV'      => ['Mercedes-Benz', 'EQE SUV',      'Mercedes-Benz EQE SUV', 'eqe-suv'],
    'Foton|Foton Mars'           => ['Foton',         'Tunland V9',   'Foton Tunland V9',      'tunland-v9'],
    'Foton|V New Energy'         => ['Foton',         'Toano Da V',   'Foton Toano Da V',      'toano-da-v'],
    '大通|V70'                   => ['Maxus',         'V70',          'Maxus V70',             'v70'],
    '大通|Interstellar'          => ['Maxus',         'Interstellar', 'Maxus Interstellar',    'interstellar'],
    '大通|G90'                   => ['Maxus',         'G90',          'Maxus G90',             'g90'],
    'Mengshi|Mengshi 917'        => ['Dongfeng',      'M-Hero 917',   'Dongfeng M-Hero 917',   'm-hero-917'],
    '华东汽车|华东牌商务车'      => ['Toyota',        'Sienna',       'Toyota Sienna',         'sienna'],
];

/* Sygnatury mark_eu|serie_eu — bez nich powyższe aliasy są martwe (guard czyta tylko brand-mapping). */
$brand = [
    'Mercedes-Benz|EQE SUV'  => ['Mercedes-Benz', 'EQE SUV',      'Mercedes-Benz EQE SUV', 'eqe-suv'],
    'Foton|Tunland V9'       => ['Foton',         'Tunland V9',   'Foton Tunland V9',      'tunland-v9'],
    'Foton|Tunland V7'       => ['Foton',         'Tunland V7',   'Foton Tunland V7',      'tunland-v7'],
    'Foton|Toano Da V'       => ['Foton',         'Toano Da V',   'Foton Toano Da V',      'toano-da-v'],
    'Maxus|V70'              => ['Maxus',         'V70',          'Maxus V70',             'v70'],
    'Maxus|Interstellar'     => ['Maxus',         'Interstellar', 'Maxus Interstellar',    'interstellar'],
    'Maxus|G90'              => ['Maxus',         'G90',          'Maxus G90',             'g90'],
    'Dongfeng|M-Hero 917'    => ['Dongfeng',      'M-Hero 917',   'Dongfeng M-Hero 917',   'm-hero-917'],
    'Toyota|Sienna'          => ['Toyota',        'Sienna',       'Toyota Sienna',         'sienna'],
];

function renderEntries(array $rows, string $header): string {
    $out = "\n  // === $header ===\n";
    foreach ($rows as $key => [$mark, $serie, $title, $slug]) {
        $k = str_replace("'", "\\'", $key);
        $out .= "  '$k' =>\n  array (\n"
              . "    'mark_eu' => '" . str_replace("'", "\\'", $mark) . "',\n"
              . "    'serie_eu' => '" . str_replace("'", "\\'", $serie) . "',\n"
              . "    'title_eu' => '" . str_replace("'", "\\'", $title) . "',\n"
              . "    'slug' => '$slug',\n"
              . "  ),\n";
    }
    return $out;
}

foreach ([['che168-model-map.php', $che168, 'v6.8 / 2026-07-29 — zaciąg ręczny 28.07: klucze w formie che168'],
          ['brand-mapping-v6.1.php', $brand, 'v6.8 / 2026-07-29 — sygnatury dla zaciągu ręcznego 28.07']] as [$file, $rows, $header]) {
    $path = $DIR . $file;
    $src  = file_get_contents($path);
    $existing = include $path;
    $before = count($existing);

    $new = array_filter($rows, fn($k) => !isset($existing[$k]), ARRAY_FILTER_USE_KEY);
    echo "── $file  ($before wpisów) — dopisuję " . count($new) . ", pomijam " . (count($rows) - count($new)) . " istniejących\n";
    foreach ($new as $k => $v) { echo "     + $k → {$v[0]} / {$v[1]}\n"; }
    if (!$new) { continue; }

    $pos = strrpos($src, ');');
    if ($pos === false) { echo "     !! nie znalazłem zamknięcia tablicy — POMIJAM\n"; continue; }
    $patched = substr($src, 0, $pos) . renderEntries($new, $header) . substr($src, $pos);

    if ($APPLY) {
        copy($path, $path . '.bak-' . $STAMP);
        file_put_contents($path, $patched);
        $check = include $path;
        echo "     zapisane: " . count($check) . " wpisów (było $before)\n";
    }
}
echo $APPLY ? "\nGOTOWE.\n" : "\nDRY-RUN — nic nie zapisano. Dodaj: apply\n";
