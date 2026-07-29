<?php
/**
 * Uzupełnienie pola wersji (`_asiaauto_complectation`) dla ofert, którym che168 go nie podało.
 *
 * Objaw: tytuł oferty kończy się na roczniku („Maxus V70 2026") zamiast nieść wersję
 * („Maxus V70 2026 2.0T Elite 5/6-osobowy"). Dotyczy dokładnie tych ofert, które mają
 * 7-cyfrowy `spec_id` (katalog pojazdów użytkowych) — auto-api oddaje dla nich szczątkowe dane.
 *
 * Źródło wersji: tytuł strony katalogu Autohome, format
 * 【{seria} {rok}款 {WERSJA}参数配置表】价格单_{marka}_汽车之家 — czyli ta sama strona,
 * z której bierzemy wyposażenie. Tłumaczenia ręczne, bo `translateComplectation()` nie zna
 * słownictwa użytkowego (长箱 = długa skrzynia, 短轴低顶 = krótki rozstaw / niski dach).
 *
 * Użycie: wp eval-file scripts/uzupelnij-wersje-zaciagu-2026-07-29.php [apply]
 */
$APPLY = in_array('apply', $args ?? [], true);

// post_id => [wersja CN z katalogu, wersja PL]
$wersje = [
    392298 => ['2.0T柴油自动四驱标轴旗舰型', '2.0T Diesel 4x4 Flagship'],
    392329 => ['2.0T柴油自动四驱长箱启航版', '2.0T Diesel 4x4 Launch długa skrzynia'],
    392313 => ['长轴低顶7座 宁德时代53.58kwh', '7-osobowy 53,58 kWh CATL'],
    392359 => ['星际L 2.0T 柴油 自动四驱豪华版长箱', 'L 2.0T Diesel 4x4 Luxury długa skrzynia'],
    392391 => ['2.0T 精英版 自动短轴低顶 5/6座', '2.0T Elite 5/6-osobowy'],
];

echo $APPLY ? "=== APPLY ===\n\n" : "=== DRY-RUN (dodaj: apply) ===\n\n";

foreach ($wersje as $pid => [$cn, $pl]) {
    $p = get_post($pid);
    if (!$p) { echo "#$pid — brak posta\n"; continue; }
    $obecna = (string) get_post_meta($pid, '_asiaauto_complectation', true);
    if ($obecna !== '') { echo "#$pid — wersja już jest („$obecna\"), pomijam\n"; continue; }
    echo "#$pid  {$p->post_title}\n";
    echo "   CN: $cn\n";
    echo "   PL: $pl\n";
    if ($APPLY) {
        update_post_meta($pid, '_asiaauto_complectation', $pl);
        update_post_meta($pid, '_asiaauto_complectation_original', $cn);
        update_post_meta($pid, '_asiaauto_complectation_source', 'autohome-catalog-2026-07-29');
    }
}

echo "\nDraft 392343 (Maxus V70) pominięty — brak `spec_id`, więc nie ma z czego wziąć wersji.\n";
echo $APPLY ? "\nGOTOWE. Przelicz tytuły: scripts/odswiez-tytuly-zaciagu-2026-07-29.php apply\n" : "\nNic nie zmieniono.\n";
