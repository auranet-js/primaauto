<?php
/**
 * Klasyfikacja marek: pochodzenie + grupa kapitałowa (T-225b, 2026-08-04).
 *
 *   _asiaauto_brand_origin : chinese | chinese_owned | global_jv
 *   _asiaauto_brand_group  : nazwa koncernu wyświetlana pod nazwą marki w siatce /marki/
 *
 * Decyzja Janka 2026-08-04: marki `global_jv` (Volkswagen, Toyota, Mazda…) znikają z siatki
 * „Chińskie marki samochodów" — nie są chińskie i w tym kontekście nas nie interesują.
 * Ich huby zostają nietknięte, bo chińskie modele tych marek rankują we własnych hubach.
 *
 * Etykieta koncernu zamiast teasera z opisu: 76% pierwszych zdań opisów zaczyna się od
 * „<Marka> to…" (średnio 128 znaków) — 58 takich linijek pod rząd to szum, a kafle urosłyby
 * trzykrotnie przy 79,6% ruchu mobilnego. Dwa słowa niosą tu więcej niż zdanie.
 *
 * Idempotentny. Uruchomienie: wp eval-file set-brand-origin-group.php        (dry-run)
 *                            wp eval-file set-brand-origin-group.php apply
 */

$apply = in_array('apply', $args ?? [], true);

/** slug => [origin, grupa kapitałowa]. Pusta grupa = marka niezależna (bez etykiety). */
$MAPA = [
    // ——— BYD
    'byd'   => ['chinese', 'BYD'],
    'denza' => ['chinese', 'BYD'],

    // ——— Geely
    'geely'     => ['chinese', 'Geely'],
    'zeekr'     => ['chinese', 'Geely'],
    'lynk-co'   => ['chinese', 'Geely'],
    'volvo'     => ['chinese_owned', 'Geely'],
    'lotus'     => ['chinese_owned', 'Geely'],
    'smart'     => ['chinese_owned', 'Geely'],

    // ——— Chery
    'chery'          => ['chinese', 'Chery'],
    'chery-fulwin'   => ['chinese', 'Chery'],
    'jetour'         => ['chinese', 'Chery'],
    'jetour-shanhai' => ['chinese', 'Chery'],
    'exeed'          => ['chinese', 'Chery'],
    'exlantix'       => ['chinese', 'Chery'],
    'icar'           => ['chinese', 'Chery'],
    'luxeed'         => ['chinese', 'Chery i Huawei'],

    // ——— Changan
    'changan' => ['chinese', 'Changan'],
    'deepal'  => ['chinese', 'Changan'],
    'avatr'   => ['chinese', 'Changan i Huawei'],

    // ——— Great Wall Motor
    'gwm'   => ['chinese', 'GWM'],
    'haval' => ['chinese', 'GWM'],
    'tank'  => ['chinese', 'GWM'],
    'wey'   => ['chinese', 'GWM'],

    // ——— Dongfeng
    'dongfeng'          => ['chinese', 'Dongfeng'],
    'voyah'             => ['chinese', 'Dongfeng'],
    'dongfeng-fengshen' => ['chinese', 'Dongfeng'],
    'dongfeng-yipai'    => ['chinese', 'Dongfeng'],
    'dongfeng-fengxing' => ['chinese', 'Dongfeng'],
    'rox'               => ['chinese', 'Dongfeng'],

    // ——— SAIC
    'mg'        => ['chinese_owned', 'SAIC'],
    'maxus'     => ['chinese', 'SAIC'],
    'im-motors' => ['chinese', 'SAIC'],
    'wuling'    => ['chinese', 'SAIC i GM'],
    'shangjie'  => ['chinese', 'SAIC i Huawei'],

    // ——— pozostałe koncerny państwowe
    'hongqi' => ['chinese', 'FAW'],
    'gac'    => ['chinese', 'GAC'],
    'baic'   => ['chinese', 'BAIC'],
    'baw'    => ['chinese', 'BAIC'],
    'foton'  => ['chinese', 'BAIC'],

    // ——— sojusz HIMA (Huawei + producent)
    'aito'    => ['chinese', 'Seres i Huawei'],
    'maextro' => ['chinese', 'JAC i Huawei'],

    // ——— marki niezależne (bez etykiety grupy)
    'xiaomi'    => ['chinese', ''],
    'nio'       => ['chinese', ''],
    'li-auto'   => ['chinese', ''],
    'xpeng'     => ['chinese', ''],
    'leapmotor' => ['chinese', ''],
    'hiphi'     => ['chinese', ''],
    'xingchi'   => ['chinese', 'Skywell'],
    'auxun'     => ['chinese', ''],
    'jmc-ev'    => ['chinese', 'JMC'],

    // ——— marki globalne z produkcją w Chinach — POZA siatką /marki/
    'volkswagen'    => ['global_jv', ''],
    'audi'          => ['global_jv', ''],
    'toyota'        => ['global_jv', ''],
    'honda'         => ['global_jv', ''],
    'nissan'        => ['global_jv', ''],
    'mazda'         => ['global_jv', ''],
    'ford'          => ['global_jv', ''],
    'mercedes-benz' => ['global_jv', ''],
];

$terms = get_terms(['taxonomy' => 'make', 'hide_empty' => true]);
if (is_wp_error($terms)) { echo "błąd pobierania termów\n"; return; }

$stat = ['chinese' => 0, 'chinese_owned' => 0, 'global_jv' => 0];
$brak = [];

foreach ($terms as $t) {
    if (!isset($MAPA[$t->slug])) {
        // Nieznana marka (np. z nowego ręcznego importu) — domyślnie chińska bez grupy,
        // żeby nie zniknęła z siatki. Wypisujemy, żeby dopisać ją do mapy świadomie.
        $brak[] = $t->slug . ' (' . $t->count . ' ofert)';
        [$origin, $group] = ['chinese', ''];
    } else {
        [$origin, $group] = $MAPA[$t->slug];
    }
    $stat[$origin]++;

    if ($apply) {
        update_term_meta($t->term_id, '_asiaauto_brand_origin', $origin);
        if ($group !== '') { update_term_meta($t->term_id, '_asiaauto_brand_group', $group); }
        else               { delete_term_meta($t->term_id, '_asiaauto_brand_group'); }
    }
}

printf("%s — %d marek\n", $apply ? 'ZASTOSOWANO' : 'DRY-RUN', count($terms));
printf("  chinese        %2d  (zostają w siatce)\n", $stat['chinese']);
printf("  chinese_owned  %2d  (zostają — europejski rodowód, chiński właściciel)\n", $stat['chinese_owned']);
printf("  global_jv      %2d  (WYPADAJĄ z siatki, huby zostają)\n", $stat['global_jv']);

if ($brak) {
    printf("\n⚠ Marki spoza mapy (traktowane jako chinese bez grupy — dopisz świadomie):\n   %s\n",
        implode(', ', $brak));
}
