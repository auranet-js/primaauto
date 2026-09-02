<?php
/**
 * porownaj-search.php — bramka T-116 etap 3 krok 3.
 *
 * Losuje N kombinacji filtrów podstawowych (marka, paliwo, nadwozie, zakres ceny)
 * i porównuje liczbę wyników nowej trasy `asiaauto/v1/search` (tabela specs)
 * ze starą `asiaauto/v1/listings` (WP_Query + tax_query). Rozjazd = błąd normalizacji
 * albo nieaktualny wiersz w tabeli.
 *
 * Użycie: php porownaj-search.php [N] [seed]
 *
 * @since 2026-09-02 (T-116 etap 3)
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

$N    = isset($argv[1]) ? (int) $argv[1] : 50;
$seed = isset($argv[2]) ? (int) $argv[2] : 116;
mt_srand($seed);

/**
 * Losujemy WAŻONE liczbą ofert: marka z trzema autami i marka z trzystoma mają
 * wchodzić proporcjonalnie, inaczej większość kombinacji trafia w pustkę i test
 * niczego nie dowodzi (pierwszy bieg: 35 z 50 kombinacji miało 0 wyników).
 */
$wazone = static function (string $tax, int $min = 1): array {
    $terms = get_terms(['taxonomy' => $tax, 'hide_empty' => true]);
    if (is_wp_error($terms)) return [];
    $pula = [];
    foreach ($terms as $t) {
        if ($t->count < $min) continue;
        $waga = max(1, (int) round(sqrt($t->count)));   // sqrt spłaszcza dominację SUV-a
        for ($i = 0; $i < $waga; $i++) $pula[] = $t->slug;
    }
    return $pula;
};
$makes  = $wazone('make', 3);
$fuels  = $wazone('fuel');
$bodies = $wazone('body');
$prices = [100000, 130000, 160000, 200000, 250000, 300000, 400000, 1100000];

$inv = new AsiaAuto_Inventory();
$search = new AsiaAuto_Search();

$rozjazdy = 0;
$czasy = [];
echo str_pad('#', 4) . str_pad('filtry', 62) . str_pad('stara', 8) . str_pad('nowa', 8) . "wynik\n";
echo str_repeat('-', 90) . "\n";

for ($i = 1; $i <= $N; $i++) {
    $q = [];
    // każda kombinacja losuje 1–4 wymiary, żeby pokryć też filtry pojedyncze
    if (mt_rand(0, 3) > 0 && $makes)  $q['marka']    = $makes[mt_rand(0, count($makes) - 1)];
    if (mt_rand(0, 2) > 0 && $fuels)  $q['paliwo']   = $fuels[mt_rand(0, count($fuels) - 1)];
    if (mt_rand(0, 2) > 0 && $bodies) $q['nadwozie'] = $bodies[mt_rand(0, count($bodies) - 1)];
    if (mt_rand(0, 2) > 0) {
        $a = $prices[mt_rand(0, count($prices) - 1)];
        $b = $prices[mt_rand(0, count($prices) - 1)];
        $q['cena_min'] = min($a, $b);
        $q['cena_max'] = max($a, $b);
    }
    if (!$q) $q['paliwo'] = $fuels[0] ?? 'electric';

    // stara trasa
    $reqOld = new WP_REST_Request('GET', '/asiaauto/v1/listings');
    foreach ($q as $k => $v) $reqOld->set_param($k, (string) $v);
    $old = $inv->handleListings($reqOld)->get_data();

    // nowa trasa
    $t0 = microtime(true);
    $p = $search->parseParams($q);
    $new = $search->query($p);
    $czasy[] = (microtime(true) - $t0) * 1000;

    $ok = ((int) $old['total'] === (int) $new['total']);
    if (!$ok) $rozjazdy++;
    $opis = [];
    foreach ($q as $k => $v) $opis[] = "$k=$v";
    printf("%-4d%-62s%-8d%-8d%s\n", $i, mb_substr(implode(' ', $opis), 0, 60),
        (int) $old['total'], (int) $new['total'], $ok ? 'OK' : '>>> ROZJAZD');
}

echo str_repeat('-', 90) . "\n";
printf("Kombinacji: %d, rozjazdów: %d\n", $N, $rozjazdy);
printf("Czas nowej trasy (z renderem kart): śr %.0f ms, max %.0f ms\n",
    array_sum($czasy) / count($czasy), max($czasy));
exit($rozjazdy > 0 ? 1 : 0);
