<?php
/**
 * Domapowanie 3 modeli, które weszły kanałem dongchedi zanim guard mapowania objął ten
 * kanał (v0.34.23, 18.08.2026). Bez wpisu w brand-mappingu importer zbudował term `serie`
 * z surowej chińskiej nazwy — stąd huby o slugach `es9`, `v9`, `phev`.
 *
 * Wzorzec 1:1 jak przy 'Maextro|Luxeed R7' — dongchedi wysyła markę „Maextro", a mapa
 * przepisuje ją na 'Luxeed' (智界 = Luxeed, 尊界 = Maextro; źródło nie rozróżnia).
 *
 * Sygnatury mark_eu|serie_eu są konieczne obok kluczy źródłowych: guard czyta wyłącznie
 * brand-mapping, a sigToKey() tłumaczy entry z powrotem na literalny klucz.
 *
 * Użycie: php scripts/dopisz-mapowania-2026-08-18.php [apply]
 */

$DIR   = '/home/host476470/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/data/';
$APPLY = in_array('apply', $argv, true);
$STAMP = '2026-08-18-guard-dongchedi';

/* Klucze w formie, w jakiej dongchedi REALNIE wysyła mark|model — zmierzone na strumieniu /changes. */
$zrodlo = [
    'Maextro|智界V9'        => ['Luxeed', 'V9',           'Luxeed V9',           'v9'],
    'NIO|蔚来ES9'           => ['NIO',    'ES9',          'NIO ES9',             'es9'],
    'Voyah|岚图泰山 PHEV'   => ['Voyah',  'Taishan',      'Voyah Taishan',       'taishan'],
];

/* Sygnatury mark_eu|serie_eu — bez nich powyższe klucze są martwe dla guarda. */
$sygnatury = [
    'Luxeed|V9'           => ['Luxeed', 'V9',           'Luxeed V9',          'v9'],
    'NIO|ES9'             => ['NIO',    'ES9',          'NIO ES9',            'es9'],
    'Voyah|Taishan'      => ['Voyah',  'Taishan',      'Voyah Taishan',      'taishan'],
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

$naglowek = 'v6.9 / 2026-08-18 — modele z dongchedi sprzed guarda (NIO ES9, Luxeed V9, Voyah Taishan)';
foreach ([[$zrodlo, 'klucze źródłowe dongchedi'], [$sygnatury, 'sygnatury EU']] as [$rows, $opis]) {
    $path = $DIR . 'brand-mapping-v6.1.php';
    $src  = file_get_contents($path);
    $existing = include $path;
    $before = count($existing);

    $new = array_filter($rows, fn($k) => !isset($existing[$k]), ARRAY_FILTER_USE_KEY);
    echo "── brand-mapping ($before wpisów) — $opis: dopisuję " . count($new) . ", pomijam " . (count($rows) - count($new)) . " istniejących\n";
    foreach ($new as $k => $v) { echo "     + $k → {$v[0]} / {$v[1]}\n"; }
    if (!$new) { continue; }

    $pos = strrpos($src, ');');
    if ($pos === false) { echo "     !! nie znalazłem zamknięcia tablicy — POMIJAM\n"; continue; }
    $patched = substr($src, 0, $pos) . renderEntries($new, $naglowek . ' — ' . $opis) . substr($src, $pos);

    if ($APPLY) {
        if (!file_exists($path . '.bak-' . $STAMP)) { copy($path, $path . '.bak-' . $STAMP); }
        file_put_contents($path, $patched);
        $check = include $path;
        echo "     zapisane: " . count($check) . " wpisów (było $before)\n";
    }
}
echo $APPLY ? "\nGOTOWE.\n" : "\nDRY-RUN — nic nie zapisano. Dodaj: apply\n";
