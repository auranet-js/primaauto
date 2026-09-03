<?php
/**
 * napraw-moc-ukladu.php — podnosi `_asiaauto_horse_power` do mocy UKŁADU w hybrydach (T-116 domknięcie).
 *
 * Powód: filtr mocy w wyszukiwarce czyta meta wprost, a w PHEV/EREV meta trzymało moc SILNIKA
 * spalinowego (AITO M9: 160 KM zamiast 496) albo było puste (nowe oferty che168 dostają katalog
 * Autohome nocą, ale nikt z niego mocy do meta nie przepisywał). Karta oferty liczy moc z extra_prep
 * (`AsiaAuto_Inventory::resolvePower()`), więc klient widział 496 KM, a filtr „od 300 KM" auto odrzucał.
 *
 * Reguły:
 *   - źródło = `AsiaAuto_Spec::system_km_from_extra_prep()` (bez `system_max_power`, bez `engine_*`);
 *   - TYLKO PODNOSI: wartość równa lub niższa od istniejącej zostaje (zakaz obniżania z etapu 3);
 *   - stempluje `_asiaauto_horse_power_source` użytym kluczem;
 *   - oferty ze stemplem `engine_max_horsepower` pomija, chyba że podasz `silnik`;
 *   - zapis przez update_post_meta → hook `updated_post_meta` w AsiaAuto_Specs_Table od razu
 *     aktualizuje kolumnę `power_km` w tabeli specs.
 *
 * Użycie:  php scripts/napraw-moc-ukladu.php [apply] [since=48h] [limit=N] [silnik] [paliwa=phev,erev] [raport=plik.md]
 *          bez `apply` — dry-run, nic nie zapisuje
 *          since=48h  — tylko oferty nowe/wzbogacone w ostatnich N godzinach (pod cron nocny)
 *
 * @since 2026-09-03
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

if (!method_exists('AsiaAuto_Spec', 'system_km_from_extra_prep')) {
    fwrite(STDERR, "BŁĄD: AsiaAuto_Spec::system_km_from_extra_prep() niedostępna — plugin < 0.37.0?\n");
    exit(1);
}

$apply = in_array('apply', $argv, true);
$silnik = in_array('silnik', $argv, true);
$since = null; $limit = 0; $paliwa = ['phev', 'erev']; $raport = '';
foreach ($argv as $a) {
    if (preg_match('~^since=(\d+)h?$~', (string) $a, $m)) $since = (int) $m[1];
    if (preg_match('~^limit=(\d+)$~', (string) $a, $m))   $limit = (int) $m[1];
    if (preg_match('~^paliwa=(.+)$~', (string) $a, $m))   $paliwa = array_map('trim', explode(',', $m[1]));
    if (preg_match('~^raport=(.+)$~', (string) $a, $m))   $raport = $m[1];
}

global $wpdb;
$t = $wpdb->prefix . 'asiaauto_specs';
$inF = implode(',', array_fill(0, count($paliwa), '%s'));
$sinceSql = '';
$params = $paliwa;
if ($since) {
    $cut = gmdate('Y-m-d H:i:s', time() - $since * 3600);
    $stamps = ['_asiaauto_spec_inherited_at', '_asiaauto_spec_bank_at', '_asiaauto_spec_catalog_at',
               '_asiaauto_spec_merged_at', '_asiaauto_fix_t116_at'];
    $inS = implode(',', array_fill(0, count($stamps), '%s'));
    $sinceSql = " AND (p.post_date_gmt >= %s OR p.post_modified_gmt >= %s OR EXISTS (
        SELECT 1 FROM {$wpdb->postmeta} st WHERE st.post_id = p.ID AND st.meta_key IN ($inS) AND st.meta_value >= %s))";
    $params = array_merge($params, [$cut, $cut], $stamps, [$cut]);
}
$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT p.ID, p.post_title, s.fuel, hp.meta_value hp, src.meta_value src, ep.meta_value ep
     FROM {$wpdb->posts} p
     JOIN $t s ON s.post_id = p.ID
     LEFT JOIN {$wpdb->postmeta} hp  ON hp.post_id  = p.ID AND hp.meta_key  = '_asiaauto_horse_power'
     LEFT JOIN {$wpdb->postmeta} src ON src.post_id = p.ID AND src.meta_key = '_asiaauto_horse_power_source'
     LEFT JOIN {$wpdb->postmeta} ep  ON ep.post_id  = p.ID AND ep.meta_key  = '_asiaauto_extra_prep'
     WHERE p.post_type = 'listings' AND p.post_status = 'publish' AND s.fuel IN ($inF)$sinceSql
     ORDER BY p.ID", $params));
if ($limit) $rows = array_slice($rows, 0, $limit);

$podniesione = []; $silnikPominiete = []; $bezZrodla = []; $zostaje = 0; $byKey = []; $kartaRozjazd = 0;
foreach ($rows as $r) {
    $e = json_decode((string) $r->ep, true) ?: [];
    $przed = (int) $r->hp;
    [$km, $key] = AsiaAuto_Spec::system_km_from_extra_prep($e);
    $karta = class_exists('AsiaAuto_Inventory') ? (int) (AsiaAuto_Inventory::resolvePower((int) $r->ID, $e)['km'] ?? 0) : 0;
    $wiersz = ['id' => (int) $r->ID, 'tytul' => html_entity_decode($r->post_title), 'paliwo' => $r->fuel,
               'przed' => $przed, 'po' => $km, 'klucz' => $key, 'stempel' => (string) $r->src, 'karta' => $karta];
    if ($km <= 0) { if ($przed <= 0) $bezZrodla[] = $wiersz; else $zostaje++; continue; }
    if ($km <= $przed) { $zostaje++; continue; }
    if ($r->src === 'engine_max_horsepower' && !$silnik) { $silnikPominiete[] = $wiersz; continue; }
    $podniesione[] = $wiersz;
    $byKey[$key] = ($byKey[$key] ?? 0) + 1;
    if ($karta && $karta !== $km) $kartaRozjazd++;
    if ($apply) {
        update_post_meta((int) $r->ID, '_asiaauto_horse_power', (string) $km);
        update_post_meta((int) $r->ID, '_asiaauto_horse_power_source', $key);
    }
}

$fmt = fn($w) => sprintf('| %d | %s | %s | %s | **%d** | `%s` | %s | %s |', $w['id'],
    mb_strimwidth($w['tytul'], 0, 48, '…'), strtoupper($w['paliwo']), $w['przed'] ?: '—', $w['po'],
    $w['klucz'], $w['stempel'] ?: '—', $w['karta'] ?: '—');
$naglowek = "| ID | Tytuł | Paliwo | Przed | Po | Klucz | Stempel przed | Karta |\n|---|---|---|---|---|---|---|---|";

$out = [];
$out[] = sprintf("# napraw-moc-ukladu.php — %s (%s, paliwa: %s)", $apply ? 'APPLY' : 'DRY-RUN',
    $since ? "since={$since}h" : 'pełny', implode(',', $paliwa));
$out[] = sprintf("\nofert w zakresie: %d | podniesione: %d | bez zmian (równe lub wyższe): %d | pominięte (stempel silnika): %d | bez źródła mocy układu i z pustą mocą: %d",
    count($rows), count($podniesione), $zostaje, count($silnikPominiete), count($bezZrodla));
$out[] = sprintf("rozjazd wartość vs karta oferty wśród podniesionych: %d (karta liczy inaczej — front motor, patrz resolvePower)", $kartaRozjazd);
arsort($byKey);
$out[] = "\n## Użyte klucze\n";
foreach ($byKey as $k => $n) $out[] = sprintf("- `%s`: %d", $k, $n);
$out[] = "\n## Podniesione (" . count($podniesione) . ")\n\n$naglowek";
foreach ($podniesione as $w) $out[] = $fmt($w);
if ($silnikPominiete) {
    $out[] = "\n## Pominięte — stempel `engine_max_horsepower` (" . count($silnikPominiete) . "), włącz argumentem `silnik`\n\n$naglowek";
    foreach ($silnikPominiete as $w) $out[] = $fmt($w);
}
if ($bezZrodla) {
    $out[] = "\n## Bez źródła mocy układu, moc nadal pusta/zero (" . count($bezZrodla) . ")\n\n| ID | Tytuł | Paliwo | Przed | Karta |\n|---|---|---|---|---|";
    foreach ($bezZrodla as $w) $out[] = sprintf('| %d | %s | %s | %s | %s |', $w['id'], mb_strimwidth($w['tytul'], 0, 48, '…'), strtoupper($w['paliwo']), $w['przed'] ?: '—', $w['karta'] ?: '—');
}
$txt = implode("\n", $out) . "\n";
echo $txt;
if ($raport) { file_put_contents($raport, $txt); fwrite(STDERR, "raport: $raport\n"); }
