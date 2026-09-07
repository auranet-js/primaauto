<?php
/**
 * napraw-nadwozie-ze-specyfikacji.php — koryguje taksonomię `body` tam, gdzie API skłamało.
 *
 * che168 klasyfikuje kombi (旅行车) jako 'sedan'. Zmierzone 2026-09-07 na Stelato S9T
 * i Aistaland GT7 — oba weszły jako Sedan, mimo bagażnika 729–1677 l. Prawdziwy kształt
 * nadwozia jest w `_asiaauto_extra_prep['car_body_struct']`.
 *
 * Importer od 07.09 bierze tę wartość pod uwagę (`AsiaAuto_Importer::bodyTypeFromSpec()`),
 * ten skrypt naprawia egzemplarze zaimportowane wcześniej.
 *
 * Użycie:
 *   wp eval-file scripts/napraw-nadwozie-ze-specyfikacji.php          # DRY-RUN
 *   wp eval-file scripts/napraw-nadwozie-ze-specyfikacji.php apply
 */

$apply = (($args[0] ?? '') === 'apply');

// Ta sama mapa co w importerze — celowo wąska, tylko przypadki realnego bledu zrodla.
$MAPA = [
    '旅行车' => 'Kombi',
    '跑车'   => 'Samochód sportowy',
    '皮卡'   => 'Pickup',
    '敞篷车' => 'Kabriolet',
];

global $wpdb;
$ids = $wpdb->get_col("
    SELECT p.ID FROM {$wpdb->posts} p
    JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_asiaauto_extra_prep'
    WHERE p.post_type = 'listings' AND p.post_status IN ('publish','draft')
");
printf("=== KOREKTA NADWOZIA — %s ===\nprzejrzanych ofert z extra_prep: %d\n\n", $apply ? 'APPLY' : 'DRY-RUN', count($ids));

$doZmiany = []; $ok = 0;
foreach ($ids as $pid) {
    $e = json_decode((string) get_post_meta($pid, '_asiaauto_extra_prep', true), true);
    if (!is_array($e)) continue;
    $cn = trim((string) ($e['car_body_struct'] ?? ''));
    if ($cn === '' || !isset($MAPA[$cn])) continue;
    $powinno = $MAPA[$cn];

    $terms = get_the_terms($pid, 'body');
    $jest  = (is_array($terms) && $terms) ? $terms[0]->name : '(brak)';
    if ($jest === $powinno) { $ok++; continue; }
    $doZmiany[] = ['id' => $pid, 'jest' => $jest, 'powinno' => $powinno, 'cn' => $cn, 'tytul' => get_the_title($pid)];
}

printf("juz poprawnych: %d | do korekty: %d\n\n", $ok, count($doZmiany));
$hist = [];
foreach ($doZmiany as $d) $hist[$d['jest'] . ' -> ' . $d['powinno']] = ($hist[$d['jest'] . ' -> ' . $d['powinno']] ?? 0) + 1;
foreach ($hist as $k => $v) printf("  %-28s %d ofert\n", $k, $v);
echo "\n";
foreach (array_slice($doZmiany, 0, 10) as $d) printf("  #%d %s (%s -> %s)\n", $d['id'], mb_substr($d['tytul'], 0, 52), $d['jest'], $d['powinno']);
if (count($doZmiany) > 10) printf("  … i %d dalszych\n", count($doZmiany) - 10);

if (!$apply) { echo "\nDRY-RUN — nic nie zmienione. Korekta: dopisz 'apply'\n"; return; }

$zm = 0;
foreach ($doZmiany as $d) {
    $r = wp_set_object_terms($d['id'], $d['powinno'], 'body', false);
    if (is_wp_error($r)) { printf("  BLAD #%d: %s\n", $d['id'], $r->get_error_message()); continue; }
    update_post_meta($d['id'], '_asiaauto_body_corrected', $d['cn']);
    $zm++;
}
printf("\nPoprawione: %d ofert\n", $zm);
foreach (array_unique(array_column($doZmiany, 'powinno')) as $nazwa) {
    $t = get_term_by('name', $nazwa, 'body');
    if ($t) { wp_update_term_count_now([$t->term_id], 'body'); printf("  term '%s': %d ofert\n", $nazwa, get_term($t->term_id, 'body')->count); }
}
