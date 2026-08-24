<?php
/**
 * Przelicza wersję wyposażenia (`complectation`) z `param_93` po zmianie mapy tłumaczeń
 * i synchronizuje ogon tytułu. Do użycia PO każdym dopisaniu wpisów do
 * data/translations-complectations.php — bez tego nowe tłumaczenia działają wyłącznie
 * na ofertach importowanych od tego momentu, a te już w bazie zostają ze starym tekstem.
 *
 * Wymienia WYŁĄCZNIE ogon tytułu (prefiks marka/seria/rocznik i `post_name` nietknięte),
 * więc adresy stron się nie zmieniają.
 *
 * Użycie:
 *   wp eval-file scripts/przelicz-tytuly-po-mapie.php                 # dry-run, wszystkie
 *   wp eval-file scripts/przelicz-tytuly-po-mapie.php 性能            # dry-run, tylko param_93 z tym znakiem
 *   wp eval-file scripts/przelicz-tytuly-po-mapie.php 性能 zapisz     # zapis
 */

$argv_   = (array) ($args ?? []);
$zapisz  = in_array('zapisz', $argv_, true);
$filtr   = '';
foreach ($argv_ as $a) { if ($a !== 'zapisz') { $filtr = (string) $a; break; } }

/** Wycięcie wersji z param_93 — identyczne z AsiaAuto_Che168_Adapter. */
$wytnijTrim = static function (string $name93): string {
    if ($name93 === '' || mb_strpos($name93, '款') === false) return '';
    $czesci = preg_split('/(?:19|20)\d\d\s*款/u', $name93, 2);
    if (is_array($czesci) && isset($czesci[1]) && trim($czesci[1]) !== '') {
        $trim = trim($czesci[1]);
    } else {
        $fallback = explode('款', $name93);
        $trim     = trim((string) end($fallback));
    }
    $trim = strtr($trim, ['（' => '(', '）' => ')', '，' => ',', '、' => ',', '　' => ' ']);
    $trim = preg_replace(['/\(\s+/u', '/\s+\)/u', '/\(\s*\)/u'], ['(', ')', ''], $trim);
    return trim(preg_replace('/\s{2,}/u', ' ', (string) $trim));
};

global $wpdb;
$rows = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.post_status, c.meta_value AS compl, e.meta_value AS ep
    FROM {$wpdb->posts} p
    JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = '_asiaauto_source' AND s.meta_value = 'che168'
    JOIN {$wpdb->postmeta} c ON c.post_id = p.ID AND c.meta_key = '_asiaauto_complectation' AND c.meta_value <> ''
    JOIN {$wpdb->postmeta} e ON e.post_id = p.ID AND e.meta_key = '_asiaauto_extra_prep'
    WHERE p.post_type = 'listings' AND p.post_status IN ('publish','draft')
");

$translator = new AsiaAuto_Translator();
$stat = ['zbadane' => 0, 'zmienione' => 0, 'bez_zmian' => 0, 'ogon_nie_pasuje' => 0, 'pusty_wynik' => 0];
$probka = []; $pominiete = [];

foreach ($rows as $r) {
    $ep  = json_decode((string) $r->ep, true);
    $p93 = is_array($ep) ? (string) ($ep['param_93'] ?? '') : '';
    if ($p93 === '') continue;
    if ($filtr !== '' && mb_strpos($p93, $filtr) === false) continue;
    $stat['zbadane']++;

    $trim = $wytnijTrim($p93);
    if ($trim === '') { $stat['pusty_wynik']++; continue; }
    $nowy = $translator->translateComplectation($trim);
    if ($nowy === '' || $nowy === (string) $r->compl) { $stat['bez_zmian']++; continue; }

    // Ogon w dwóch wariantach kodowania — post_title trzyma encje HTML (&amp;), meta surowe &.
    $tytul = (string) $r->post_title;
    $stary = (string) $r->compl;
    $prefiks = null; $nowy_do_tytulu = $nowy;
    foreach ([$stary => false, str_replace('&', '&amp;', $stary) => true] as $kandydat => $enc) {
        if (str_ends_with($tytul, ' ' . $kandydat)) {
            $prefiks = substr($tytul, 0, -strlen(' ' . $kandydat));
            $nowy_do_tytulu = $enc ? str_replace('&', '&amp;', $nowy) : $nowy;
            break;
        }
    }
    if ($prefiks === null) {
        $stat['ogon_nie_pasuje']++;
        $pominiete[] = [$r->ID, $p93, $stary, $nowy, $tytul];
        continue;
    }
    $nowyTytul = trim($prefiks . ' ' . $nowy_do_tytulu);

    $stat['zmienione']++;
    if (count($probka) < 15) $probka[] = [$r->ID, $tytul, $nowyTytul];

    if ($zapisz) {
        $wpdb->update($wpdb->posts, ['post_title' => $nowyTytul], ['ID' => (int) $r->ID]);
        clean_post_cache((int) $r->ID);
        update_post_meta((int) $r->ID, '_asiaauto_complectation', $nowy);
        if ($nowy !== $trim) update_post_meta((int) $r->ID, '_asiaauto_complectation_original', $trim);
    }
}

echo ($zapisz ? "=== ZAPIS" : "=== DRY-RUN") . ($filtr !== '' ? " (filtr param_93: {$filtr})" : " (wszystkie)") . " ===\n";
foreach ($stat as $k => $v) echo str_pad($k, 18) . $v . "\n";
echo "\nPRÓBKA:\n";
foreach ($probka as $p) echo "  #{$p[0]}\n    było:   {$p[1]}\n    ma być: {$p[2]}\n";
if ($pominiete) {
    echo "\nPOMINIĘTE — tytuł nie kończy się zapisaną wersją (ręczna edycja?):\n";
    foreach (array_slice($pominiete, 0, 10) as $x) echo "  #{$x[0]}  \"{$x[2]}\" → \"{$x[3]}\"\n    tytuł: {$x[4]}\n";
}
