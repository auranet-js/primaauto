<?php
/**
 * backfill-spec-autohome.php — dolewa wyposażenie z katalogu Autohome do ofert che168,
 * które mają `_asiaauto_spec_id`, ale ubogie `extra_prep`.
 *
 * Powód: auto-api dla che168 oddaje ~82 pola (dane techniczne, zero wyposażenia). Katalog
 * Autohome dla tego samego `specid` ma ~237 parametrów w 22 grupach. `spec_id` to DOKŁADNY
 * identyfikator wersji, więc dopasowanie jest bezbłędne — inaczej niż bank specyfikacji, który
 * dla che168 trafia w 3% (rozjazd nazewnictwa wersji, patrz T-222 §2).
 *
 * CACHE: katalog per `specid` ląduje w uploads/asiaauto/autohome-catalog/{specid}.json i jest
 * odtąd darmowy. Ten sam `specid` powtarza się między ofertami, więc realna liczba pobrań jest
 * niższa niż liczba ofert.
 *
 * Reuse: pobieranie robi scripts/autohome-catalog-fetch.js (deszyfracja antyscrapingu),
 * scalanie scripts/autohome-catalog-merge.php (nie nadpisuje istniejących kluczy, stempluje audyt).
 * Ten skrypt jest wyłącznie orkiestracją — logiki nie duplikuje.
 *
 * Użycie:  php backfill-spec-autohome.php [prog_pol] [limit] [apply] [manual]
 *          prog_pol — próg ubóstwa (domyślnie 150), limit — 0 = bez limitu, bez `apply` = dry-run
 *          manual   — obejmij TAKŻE oferty ręczne (`_asiaauto_manual_import`/`_manual_entry`)
 *
 * Dlaczego `manual` jest bezpieczne (decyzja Janka 2026-07-30): guard chroni pracę Ruslana, ale
 * `autohome-catalog-merge.php` NIGDY nie nadpisuje istniejącego klucza — dolewa wyłącznie brakujące.
 * Pomiar: 36 z 72 ofert ręcznych siedziało poniżej 100 pól przy medianie 85, czyli guard blokował
 * czysty zysk. W kaskadzie przy imporcie guard ZOSTAJE — tam pilnuje, by sync nie wchodził
 * w oferty zarządzane ręcznie, co jest innym problemem.
 *
 * @since 2026-07-30 (T-222)
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

$prog  = isset($argv[1]) ? (int) $argv[1] : 150;
$limit = isset($argv[2]) && ctype_digit((string) $argv[2]) ? (int) $argv[2] : 0;
$apply     = in_array('apply', $argv, true);
$zRecznymi = in_array('manual', $argv, true);
// Domyślnie pomijamy oferty, z których katalog już wyciśnięto (`_asiaauto_spec_catalog_at`).
// Bez tego skrypt nie jest idempotentny: po pierwszym biegu część ofert ląduje w 100-149 pól,
// więc nadal jest pod progiem 150 i kwalifikuje się ponownie — mielilibyśmy je na zero zysku
// (pomiar 2026-07-30: 129 kandydatów zamiast 51, z czego ~78 to powtórki).
$force     = in_array('force', $argv, true);

$REPO      = '/home/host476470/projekty/primaauto';
$WP        = '/home/host476470/domains/primaauto.com.pl/public_html';
$CACHE_DIR = wp_get_upload_dir()['basedir'] . '/asiaauto/autohome-catalog';
$THROTTLE  = 2;   // sekundy między POBRANIAMI (nie dotyczy trafień w cache)

if (!is_dir($CACHE_DIR) && !wp_mkdir_p($CACHE_DIR)) {
    fwrite(STDERR, "Nie moge utworzyc cache: {$CACHE_DIR}\n");
    exit(1);
}

global $wpdb;
$sql = "
    SELECT p.ID, p.post_title, sp.meta_value AS specid, ep.meta_value AS ep
      FROM {$wpdb->posts} p
      JOIN {$wpdb->postmeta} src ON src.post_id=p.ID AND src.meta_key='_asiaauto_source'
                                AND src.meta_value='che168'
      JOIN {$wpdb->postmeta} sp  ON sp.post_id=p.ID  AND sp.meta_key='_asiaauto_spec_id'
                                AND sp.meta_value > 0
 LEFT JOIN {$wpdb->postmeta} ep  ON ep.post_id=p.ID  AND ep.meta_key='_asiaauto_extra_prep'
     WHERE p.post_type='listings' AND p.post_status IN ('publish','draft')
       " . ($zRecznymi ? '' : "AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} mm WHERE mm.post_id=p.ID
                         AND mm.meta_key IN ('_asiaauto_manual_import','_asiaauto_manual_entry')
                         AND mm.meta_value='1')") . "
       " . ($force ? '' : "AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} cc WHERE cc.post_id=p.ID
                         AND cc.meta_key='_asiaauto_spec_catalog_at')") . "
     ORDER BY p.ID DESC";

$kandydaci = [];
foreach ($wpdb->get_results($sql) as $r) {
    $n = $r->ep ? count((array) json_decode($r->ep, true)) : 0;
    if ($n >= $prog) continue;
    $kandydaci[] = ['id' => (int) $r->ID, 'specid' => (int) $r->specid,
                    'tytul' => $r->post_title, 'pol' => $n];
    if ($limit && count($kandydaci) >= $limit) break;
}

$unikalne = count(array_unique(array_column($kandydaci, 'specid')));
printf("Kandydaci: %d ofert (<%d pol), unikalnych specid: %d\n", count($kandydaci), $prog, $unikalne);
printf("Cache: %s (%d plikow)\n", $CACHE_DIR, count(glob($CACHE_DIR . '/*.json')));
printf("TRYB: %s%s%s\n\n", $apply ? "APPLY" : "DRY-RUN (dodaj: apply)", $zRecznymi ? " + OFERTY RECZNE" : "", $force ? " + POWTORKI (force)" : "");

$pobrane = $zCache = $bledy = $scalone = $bezZmian = 0;
$polRazem = 0;

foreach ($kandydaci as $i => $k) {
    $plik = $CACHE_DIR . '/' . $k['specid'] . '.json';

    if (is_readable($plik) && filesize($plik) > 100) {
        $zCache++;
    } else {
        $cmd = sprintf('node %s/scripts/autohome-catalog-fetch.js %d %s 2>&1',
            escapeshellarg($REPO) === "'{$REPO}'" ? $REPO : $REPO, $k['specid'], escapeshellarg($plik));
        exec($cmd, $out, $rc);
        if ($rc !== 0 || !is_readable($plik)) {
            $bledy++;
            printf("  #%-7d specid=%-7d POBRANIE NIEUDANE (rc=%d) %s\n",
                $k['id'], $k['specid'], $rc, trim(implode(' ', array_slice($out, -1))));
            $out = [];
            sleep($THROTTLE);
            continue;
        }
        $out = [];
        $pobrane++;
        sleep($THROTTLE);
    }

    $cmd = sprintf('cd %s && wp eval-file %s/scripts/autohome-catalog-merge.php %d %s %d %s 2>&1',
        escapeshellarg($WP), $REPO, $k['id'], escapeshellarg($plik), $k['specid'], $apply ? 'apply' : '');
    exec($cmd, $mout, $mrc);
    $txt = implode("\n", $mout);
    $mout = [];

    if (preg_match('/przed:\s*(\d+)\s*→\s*po:\s*(\d+)\s*\(\+(\d+)\)/u', $txt, $m)) {
        $delta = (int) $m[3];
        if ($delta > 0) { $scalone++; $polRazem += $delta; }
        else            { $bezZmian++; }
        printf("  #%-7d specid=%-7d %-40s %3d -> %3d (+%d)\n",
            $k['id'], $k['specid'], mb_substr(html_entity_decode($k['tytul']), 0, 40),
            (int) $m[1], (int) $m[2], $delta);
    } else {
        $bledy++;
        printf("  #%-7d specid=%-7d SCALANIE NIEUDANE: %s\n",
            $k['id'], $k['specid'], mb_substr(trim($txt), 0, 120));
    }

    if (($i + 1) % 25 === 0) {
        printf("  --- %d/%d (pobran %d, z cache %d, bledow %d) ---\n",
            $i + 1, count($kandydaci), $pobrane, $zCache, $bledy);
    }
}

printf("\n=== %s ===\n", $apply ? 'ZAPISANO' : 'DRY-RUN');
printf("ofert wzbogaconych: %d | bez zmian: %d | bledow: %d\n", $scalone, $bezZmian, $bledy);
printf("pobran z sieci: %d | trafien w cache: %d\n", $pobrane, $zCache);
printf("pol dolanych razem: %d (srednio +%.0f na oferte)\n",
    $polRazem, $scalone ? $polRazem / $scalone : 0);

if ($apply) {
    AsiaAuto_Logger::info("backfill-spec-autohome: {$scalone} ofert, +{$polRazem} pol, {$pobrane} pobran");
}
