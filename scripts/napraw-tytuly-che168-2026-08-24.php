<?php
/**
 * Naprawa tytułów ofert che168 zdublowanych po zmianie w auto-api (18/19.08.2026).
 *
 * auto-api zaczęło wypełniać pole `complectation` pełną nazwą pojazdu zamiast samą wersją,
 * przez co importer skleił tytuł "{marka} {seria} {rok} {PEŁNA NAZWA Z ROKIEM}".
 * Skrypt odtwarza właściwą wersję z param_93 (część po 款 — tak jak robi adapter) i wymienia
 * WYŁĄCZNIE ogon tytułu. Prefiks (marka, seria, rocznik) i `post_name` zostają nietknięte.
 *
 * Użycie:  wp eval-file scripts/napraw-tytuly-che168-2026-08-24.php [zapisz]
 * Bez argumentu "zapisz" działa jako dry-run.
 */

$zapisz = in_array('zapisz', (array) ($args ?? []), true);

/** Ta sama logika czyszczenia co AsiaAuto_Che168_Adapter (T-186). */
$wytnijTrim = static function (string $name93): string {
    if ($name93 === '' || mb_strpos($name93, '款') === false) {
        return '';
    }
    // Tniemy po znaczniku ROCZNIKA, nie po ostatnim 款 — 豪越L 2025款 2.0T DCT尊享款
    // ma dwa 款 i end(explode()) dawał pusty trim. Zgodne z adapterem (fix 2026-08-24).
    $czesci = preg_split('/(?:19|20)\d\d\s*款/u', $name93, 2);
    if (is_array($czesci) && isset($czesci[1]) && trim($czesci[1]) !== '') {
        $trim = trim($czesci[1]);
    } else {
        $fallback = explode('款', $name93);
        $trim     = trim((string) end($fallback));
    }
    $trim  = strtr($trim, ['（' => '(', '）' => ')', '，' => ',', '、' => ',', '　' => ' ']);
    $trim  = preg_replace(['/\(\s+/u', '/\s+\)/u', '/\(\s*\)/u'], ['(', ')', ''], $trim);
    return trim(preg_replace('/\s{2,}/u', ' ', (string) $trim));
};

global $wpdb;
$rows = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.post_status, p.post_date,
           c.meta_value AS compl, e.meta_value AS ep
    FROM {$wpdb->posts} p
    JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = '_asiaauto_source'  AND s.meta_value = 'che168'
    JOIN {$wpdb->postmeta} c ON c.post_id = p.ID AND c.meta_key = '_asiaauto_complectation' AND c.meta_value <> ''
    JOIN {$wpdb->postmeta} e ON e.post_id = p.ID AND e.meta_key = '_asiaauto_extra_prep'
    WHERE p.post_type = 'listings' AND p.post_status IN ('publish','draft')
");

$translator = new AsiaAuto_Translator();
$stat = ['zbadane' => 0, 'naprawione' => 0, 'bez_zmian' => 0, 'brak_param93' => 0,
         'ogon_nie_pasuje' => 0, 'trim_pusty' => 0, 'nadal_cjk' => 0];
$probka = [];
$doRecznego = [];

foreach ($rows as $r) {
    $stat['zbadane']++;
    $ep  = json_decode((string) $r->ep, true);
    $p93 = is_array($ep) ? (string) ($ep['param_93'] ?? '') : '';

    // Duplikat rozpoznajemy mechanicznie: rocznik z param_93 stoi PRZED 款, więc jego
    // obecność w complectation dowodzi, że pole niesie całą nazwę, a nie samą wersję.
    if ($p93 === '' || !preg_match('/((?:19|20)\d\d)\s*款/u', $p93, $m)) {
        $stat['brak_param93']++;
        continue;
    }
    $stary = (string) $r->compl;
    if (!str_contains($stary, $m[1])) {
        $stat['bez_zmian']++;
        continue;
    }

    $trim = $wytnijTrim($p93);
    if ($trim === '') {
        $stat['trim_pusty']++;
        $doRecznego[] = [$r->ID, $p93, $stary, $r->post_title, 'trim pusty'];
        continue;
    }
    $nowy = $translator->translateComplectation($trim);
    if ($nowy === '') {
        $stat['trim_pusty']++;
        $doRecznego[] = [$r->ID, $p93, $stary, $r->post_title, 'tłumaczenie puste'];
        continue;
    }

    // Tytuł powstał jako "{prefiks} {complectation}" — wymieniamy WYŁĄCZNIE ogon.
    // WP trzyma w post_title encje HTML (&amp;), a meta ma surowe & — stąd dwa warianty
    // dopasowania; kodowanie wykryte na ogonie stosujemy też do nowej wersji.
    $tytul  = (string) $r->post_title;
    $warianty = [$stary => false, str_replace('&', '&amp;', $stary) => true];
    $prefiks = null;
    foreach ($warianty as $kandydat => $enc) {
        $ogon = ' ' . $kandydat;
        if (str_ends_with($tytul, $ogon)) {
            $prefiks = substr($tytul, 0, -strlen($ogon));
            $nowy_do_tytulu = $enc ? str_replace('&', '&amp;', $nowy) : $nowy;
            break;
        }
    }
    if ($prefiks === null) {
        $stat['ogon_nie_pasuje']++;
        $doRecznego[] = [$r->ID, $p93, $stary, $tytul, 'tytuł nie kończy się starym complectation'];
        continue;
    }
    $nowyTytul = trim($prefiks . ' ' . $nowy_do_tytulu);

    if (preg_match('/[\x{4e00}-\x{9fff}]/u', $nowyTytul)) {
        $stat['nadal_cjk']++;
        $doRecznego[] = [$r->ID, $p93, $stary, $nowyTytul, 'CJK w nowym tytule — brak wpisu w mapie'];
        continue;
    }

    $stat['naprawione']++;
    if (count($probka) < 15) {
        $probka[] = [$r->ID, $tytul, $nowyTytul];
    }

    if ($zapisz) {
        // post_name celowo pominięty — slug nie zawiera complectation, adres zostaje.
        $wpdb->update($wpdb->posts, ['post_title' => $nowyTytul], ['ID' => (int) $r->ID]);
        clean_post_cache((int) $r->ID);
        update_post_meta((int) $r->ID, '_asiaauto_complectation', $nowy);
        if ($nowy !== $trim) {
            update_post_meta((int) $r->ID, '_asiaauto_complectation_original', $trim);
        }
    }
}

echo ($zapisz ? "=== ZAPIS ===\n" : "=== DRY-RUN (bez argumentu 'zapisz' nic nie jest zapisywane) ===\n");
foreach ($stat as $k => $v) {
    echo str_pad($k, 20) . $v . "\n";
}
echo "\nPRÓBKA:\n";
foreach ($probka as $p) {
    echo "  #{$p[0]}\n    było: {$p[1]}\n    ma być: {$p[2]}\n";
}
if ($doRecznego) {
    echo "\nDO RĘCZNEGO PRZEJRZENIA (" . count($doRecznego) . "):\n";
    foreach (array_slice($doRecznego, 0, 20) as $d) {
        echo "  #{$d[0]} [{$d[4]}]\n    param_93: {$d[1]}\n    tytuł: {$d[3]}\n";
    }
}
