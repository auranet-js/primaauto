<?php
/**
 * zbuduj-wersje.php — buduje i odświeża tabelę `wp7j_asiaauto_versions` (T-115 krok 1).
 *
 * Jedna wersja = jeden `_asiaauto_spec_id`. Wiersz trzyma zrzut sekcji karty oferty z oferty
 * wzorcowej (najpełniejsze extra_prep), więc porównanie działa także po zniknięciu ofert.
 * Skrypt NICZEGO poza własną tabelą nie zapisuje; logika w AsiaAuto_Versions_Table.
 *
 * Czyta z tabeli wyszukiwarki `wp7j_asiaauto_specs` (cena, rok, moc, zasięg) — uruchamiać
 * PO `zbuduj-specs.php` (w nocnej sekwencji: 05:05 specs → wersje).
 *
 * Użycie:  php zbuduj-wersje.php [apply] [limit=N] [specid=N]
 *          bez `apply` — dry-run: liczy wszystko, nie zapisuje nic
 *
 * @since 2026-09-16 (T-115 krok 1)
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';
if (!class_exists('AsiaAuto_Versions_Table')) {
    require_once WP_PLUGIN_DIR . '/asiaauto-sync/includes/class-asiaauto-versions-table.php';
}

$apply = in_array('apply', $argv, true);
$limit = 0; $only = 0;
foreach ($argv as $a) {
    if (preg_match('~^limit=(\d+)$~', (string) $a, $m))  $limit = (int) $m[1];
    if (preg_match('~^specid=(\d+)$~', (string) $a, $m)) $only = (int) $m[1];
}

$t0 = microtime(true);
echo "== zbuduj-wersje.php — " . ($apply ? 'APPLY' : 'DRY-RUN') . "\n";
if ($apply && !AsiaAuto_Versions_Table::tableExists()) {
    AsiaAuto_Versions_Table::install();
    echo "Tabela " . AsiaAuto_Versions_Table::table() . " utworzona.\n";
}

$by = AsiaAuto_Versions_Table::offersBySpecid();
if ($only) $by = array_intersect_key($by, [$only => 1]);
if ($limit) $by = array_slice($by, 0, $limit, true);
$bez = $only || $limit ? [] : AsiaAuto_Versions_Table::specidsWithoutOffers($by);
echo "Wersji z ofertami publish: " . count($by) . " · w tabeli bez ofert (cena → brak): " . count($bez) . "\n\n";

$st = ['nowe' => 0, 'aktualizacja' => 0, 'zrzut' => 0, 'bez_modelu' => 0, 'bez_nazwy' => 0, 'slug_rok' => 0, 'slug_id' => 0];
$klucze = []; $sekcje = []; $bajty = 0; $ofert = []; $przyklady = [];
foreach ($by as $specid => $ids) {
    $old = AsiaAuto_Versions_Table::existing($specid);
    $row = AsiaAuto_Versions_Table::buildRow($specid, $ids, $old);
    $st[$old ? 'aktualizacja' : 'nowe']++;
    if (!$old || ($old['snapshot_at'] ?? '') !== ($row['snapshot_at'] ?? '')) $st['zrzut']++;
    if ($row['model_name'] === '') $st['bez_modelu']++;
    if ($row['version_name'] === '') $st['bez_nazwy']++;
    if (!empty($row['year']) && str_ends_with($row['slug'], '-' . $row['year'])) $st['slug_rok']++;
    if (str_ends_with($row['slug'], '-' . $specid)) $st['slug_id']++;
    $klucze[] = (int) $row['ref_keys'];
    $s = json_decode($row['sections'] ?? '[]', true) ?: [];
    $sekcje[] = array_sum(array_map(fn($x) => count($x['rows']), $s));
    $bajty += strlen($row['sections'] ?? '');
    $ofert[] = (int) $row['offers_count'];
    if (count($przyklady) < 8 || in_array($specid, [75103, 73246, 74471, 71435, 67880], true)) {
        $przyklady[$specid] = sprintf('%-7d %-60s %3d of. od %-7s %3d kluczy %3d poz.', $specid, $row['slug'], $row['offers_count'], $row['price_from'] ?? '-', $row['ref_keys'], end($sekcje));
    }
    if ($apply) AsiaAuto_Versions_Table::save($row);
}
if ($apply) {
    foreach ($bez as $specid) {
        $old = AsiaAuto_Versions_Table::existing($specid);
        AsiaAuto_Versions_Table::save(AsiaAuto_Versions_Table::buildRow($specid, [], $old));
    }
}

$med = function (array $a) { sort($a); return $a ? $a[intdiv(count($a), 2)] : 0; };
echo "Statusy: "; foreach ($st as $k => $v) echo "$k=$v "; echo "\n";
printf("Klucze extra_prep oferty wzorcowej: mediana %d, min %d · <250: %d wersji\n", $med($klucze), $klucze ? min($klucze) : 0, count(array_filter($klucze, fn($k) => $k < 250)));
printf("Pozycji w zrzucie sekcji: mediana %d, min %d · Oferty na wersję: mediana %d, max %d\n", $med($sekcje), $sekcje ? min($sekcje) : 0, $med($ofert), $ofert ? max($ofert) : 0);
printf("Rozmiar zrzutów: %.1f MB (śr. %.1f KB)\n", $bajty / 1048576, count($by) ? $bajty / count($by) / 1024 : 0);
echo "\nPrzykłady:\n" . implode("\n", $przyklady) . "\n";
printf("\nCzas: %.1f s\n", microtime(true) - $t0);
