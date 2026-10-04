<?php
/**
 * dolej-spec-autohome-dongchedi.php — nocna dolewka wyposażenia z katalogu Autohome do CHUDYCH
 * ofert dongchedi (te nie mają `_asiaauto_spec_id`, więc backfill-spec-autohome.php ich nie widzi).
 *
 * Kolejność w nocnej sekwencji: 19:15 bliźniak → 19:25 bank → 19:35 katalog che168 →
 * 19:45 TEN SKRYPT → 19:55 zbuduj-specs (wyszukiwarka czyta już dolane pola).
 *
 * Kroki (jak ręczny przebieg 2026-10-04, 134 z 162 ofert):
 *  1. kandydaci: dongchedi, publish, < PROG niepustych pól, bez `_asiaauto_spec_catalog_at`;
 *  2. specid po chińskiej nazwie wersji — scripts/autohome-specid-szukaj.js (cache, nic nie zapisuje);
 *  3. katalog specid — scripts/autohome-catalog-fetch.js (cache uploads/asiaauto/autohome-catalog/);
 *  4. KONTROLA: rozstaw osi i długość z oferty muszą być równe katalogowym — inaczej pomijamy
 *     (dopasowania „przybliżone" przepuszczamy tylko z tym dowodem, że to ten sam model);
 *  5. kopia `_asiaauto_extra_prep` sprzed zmiany → ~/backups/primaauto/RRRR-MM-DD/;
 *  6. zapis — scripts/autohome-catalog-merge.php (dolewa tylko brakujące klucze).
 *
 * Oferty bez pary wracają następnej nocy — listy wersji w cache żyją 7 dni, więc wersja dopisana
 * później na Autohome w końcu wejdzie.
 *
 * Użycie:  php dolej-spec-autohome-dongchedi.php [limit] [apply]
 *          limit — maks. ofert na bieg (domyślnie 60), bez `apply` = dry-run
 *
 * @since 2026-10-04
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

$limit = isset($argv[1]) && ctype_digit((string) $argv[1]) ? (int) $argv[1] : 60;
$apply = in_array('apply', $argv, true);
$PROG  = 100;

$REPO   = '/home/host476470/projekty/primaauto';
$WP     = '/home/host476470/domains/primaauto.com.pl/public_html';
$CACHE  = wp_get_upload_dir()['basedir'] . '/asiaauto/autohome-catalog';
$LOOKUP = $CACHE . '/_lookup';
wp_mkdir_p($LOOKUP);

printf("== %s dolej-spec-autohome-dongchedi — %s, limit %d\n", wp_date('Y-m-d H:i'), $apply ? 'APPLY' : 'DRY-RUN', $limit);

global $wpdb;
$rows = $wpdb->get_results("
    SELECT p.ID, ep.meta_value AS ep
      FROM {$wpdb->posts} p
      JOIN {$wpdb->postmeta} src ON src.post_id=p.ID AND src.meta_key='_asiaauto_source' AND src.meta_value='dongchedi'
 LEFT JOIN {$wpdb->postmeta} ep  ON ep.post_id=p.ID  AND ep.meta_key='_asiaauto_extra_prep'
     WHERE p.post_type='listings' AND p.post_status='publish'
       AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} cc WHERE cc.post_id=p.ID AND cc.meta_key='_asiaauto_spec_catalog_at')
     ORDER BY p.ID DESC");

$niepuste = static fn(array $a): int => count(array_filter($a, fn($v) => $v !== '' && $v !== null && $v !== '-'));
$kand = [];
foreach ($rows as $r) {
    $a = json_decode((string) $r->ep, true) ?: [];
    if ($niepuste($a) >= $PROG || empty($a['name'])) continue;
    $kand[(int) $r->ID] = $a;
    if (count($kand) >= $limit) break;
}
printf("Kandydaci (< %d pól, bez katalogu): %d\n", $PROG, count($kand));
if (!$kand) return;

// 2. specid po nazwie CN
$in = $LOOKUP . '/_wejscie-' . getmypid() . '.tsv';
file_put_contents($in, implode("\n", array_map(
    fn($id, $a) => $id . "\t" . str_replace(["\t", "\n"], ' ', $a['name']), array_keys($kand), $kand)) . "\n");
exec(sprintf('node %s/scripts/autohome-specid-szukaj.js %s %s 2>&1', $REPO, escapeshellarg($in), escapeshellarg($LOOKUP)), $out, $rc);
@unlink($in);

$wyniki = []; $typy = [];
foreach ($out as $l) {
    $c = explode("\t", $l);
    if (count($c) < 3 || !isset($kand[(int) $c[0]])) { if (trim($l) !== '') echo "  [szukaj] $l\n"; continue; }
    $typy[preg_replace('~[:,+].*~', '', $c[2])] = ($typy[preg_replace('~[:,+].*~', '', $c[2])] ?? 0) + 1;
    if ($c[1] !== '-') $wyniki[(int) $c[0]] = ['specid' => $c[1], 'typ' => $c[2], 'ah' => $c[4] ?? ''];
}
ksort($typy);
echo "Dopasowanie: " . json_encode($typy, JSON_UNESCAPED_UNICODE) . "\n";

// 3–4. katalog + kontrola wymiarów
$doZapisu = []; $odrzucone = 0; $bledyPobrania = 0;
foreach ($wyniki as $id => $w) {
    $plik = "$CACHE/{$w['specid']}.json";
    if (!(is_readable($plik) && filesize($plik) > 100)) {
        for ($i = 0; $i < 2; $i++) {
            exec(sprintf('node %s/scripts/autohome-catalog-fetch.js %d %s 2>&1', $REPO, $w['specid'], escapeshellarg($plik)), $o2, $rc2);
            $o2 = [];
            sleep(2);
            if (is_readable($plik) && filesize($plik) > 100) break;
        }
        if (!(is_readable($plik) && filesize($plik) > 100)) { $bledyPobrania++; printf("  #%d specid %s: POBRANIE NIEUDANE\n", $id, $w['specid']); continue; }
    }
    $cat = [];
    foreach (json_decode(file_get_contents($plik), true) ?: [] as $r) $cat[$r['name']] = (string) $r['value'];
    $cDl = '';
    foreach ($cat as $k => $v) if (str_starts_with($k, '长') && str_contains($k, '宽')) { $cDl = explode('*', $v)[0]; break; }
    $oDl = explode('x', (string) ($kand[$id]['length_width_height'] ?? ''))[0];
    $oRo = (string) ($kand[$id]['wheelbase'] ?? '');
    $cRo = $cat['轴距(mm)'] ?? '';
    if ($oRo === '' || $oDl === '' || $oRo !== $cRo || $oDl !== $cDl) {
        $odrzucone++;
        printf("  #%d specid %s ODRZUCONE — rozstaw %s/%s, długość %s/%s (%s)\n", $id, $w['specid'], $oRo ?: '?', $cRo ?: '?', $oDl ?: '?', $cDl ?: '?', $w['typ']);
        continue;
    }
    $doZapisu[$id] = $w + ['plik' => $plik];
}
printf("Do zapisu: %d | odrzucone na wymiarach: %d | błędy pobrania: %d | bez pary: %d\n",
    count($doZapisu), $odrzucone, $bledyPobrania, count($kand) - count($wyniki));
if (!$doZapisu) return;

// 5. kopia sprzed zmiany
if ($apply) {
    $dir = '/home/host476470/backups/primaauto/' . wp_date('Y-m-d');
    wp_mkdir_p($dir);
    $kopia = [];
    foreach (array_keys($doZapisu) as $id) $kopia[$id] = get_post_meta($id, '_asiaauto_extra_prep', true);
    $f = $dir . '/dolej-spec-autohome-dongchedi-' . wp_date('His') . '.json';
    file_put_contents($f, wp_json_encode(['specid' => array_map(fn($w) => $w['specid'], $doZapisu), 'extra_prep' => $kopia], JSON_UNESCAPED_UNICODE));
    echo "Kopia: $f\n";
}

// 6. zapis
$ok = 0; $pol = 0;
foreach ($doZapisu as $id => $w) {
    exec(sprintf('cd %s && wp eval-file %s/scripts/autohome-catalog-merge.php %d %s %s %s 2>&1',
        escapeshellarg($WP), $REPO, $id, escapeshellarg($w['plik']), escapeshellarg($w['specid']), $apply ? 'apply' : ''), $mo, $mrc);
    $txt = implode("\n", $mo); $mo = [];
    if (preg_match('/przed:\s*(\d+)\s*→\s*po:\s*(\d+)\s*\(\+(\d+)\)/u', $txt, $m)) {
        $ok++; $pol += (int) $m[3];
        printf("  #%d specid %-6s %-22s %3d -> %3d  %s\n", $id, $w['specid'], $w['typ'], $m[1], $m[2], mb_substr($kand[$id]['name'], 0, 40));
    } else {
        printf("  #%d specid %s SCALANIE NIEUDANE: %s\n", $id, $w['specid'], mb_substr(trim($txt), 0, 120));
    }
}
printf("=== %s: %d ofert, +%d pól ===\n", $apply ? 'ZAPISANO' : 'DRY-RUN', $ok, $pol);
if ($apply && $ok) AsiaAuto_Logger::info("dolej-spec-autohome-dongchedi: {$ok} ofert, +{$pol} pol");
