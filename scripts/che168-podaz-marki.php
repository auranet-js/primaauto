<?php
/**
 * READ-ONLY: głęboka sonda podaży che168 dla marki (parametr MARKA w kodzie).
 * Pokazuje rozkład po modelach: ile w magazynie vs ile przechodzi filtry Ruslana.
 * Użycie: wp eval-file podaz-lynk.php [liczba_stron]
 */
$pages = (int) ($args[0] ?? 15);

$api      = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$importer = new AsiaAuto_Importer(new AsiaAuto_Translator(), new AsiaAuto_Media());
$cfg_all  = get_option('asiaauto_import_config', []);
$cfg      = $cfg_all['che168'] ?? [];
$year_from = (int) (($cfg['year_from'] ?? 0) ?: 2024);

$stat = [];
$widziane = [];
for ($page = 1; $page <= $pages; $page++) {
    $list = $api->getOffers('che168', ['mark' => 'Lynk & Co', 'year_from' => $year_from, 'page' => $page]);
    $res  = is_array($list) ? ($list['result'] ?? []) : [];
    if (!$res) { printf("  (koniec danych na stronie %d)\n", $page); break; }
    foreach ($res as $row) {
        $raw = (array) ($row['data'] ?? $row);
        if (empty($raw['mark'])) continue;
        $d = AsiaAuto_Che168_Adapter::normalize($raw);
        $inner = (string) ($d['inner_id'] ?? '');
        if ($inner === '' || isset($widziane[$inner])) continue;
        $widziane[$inner] = true;

        $mo = trim((string) ($d['model'] ?? '?'));
        $ok = $importer->isAllowedByConfig($d, 'che168');

        $stat[$mo]['magazyn'] = ($stat[$mo]['magazyn'] ?? 0) + 1;
        $stat[$mo]['filtrOK'] = ($stat[$mo]['filtrOK'] ?? 0) + ($ok ? 1 : 0);
        $cena = (int) ($d['price'] ?? 0);
        $rok  = (int) ($d['year'] ?? 0);
        if (!isset($stat[$mo]['cena_min']) || ($cena > 0 && $cena < $stat[$mo]['cena_min'])) $stat[$mo]['cena_min'] = $cena;
        if (!isset($stat[$mo]['rok_max']) || $rok > $stat[$mo]['rok_max']) $stat[$mo]['rok_max'] = $rok;
    }
}

ksort($stat);
printf("\nPODAŻ che168 — Lynk & Co (stron: %d, unikatów: %d)\n", $pages, count($widziane));
printf("filtry: rocznik>=%d, km<=%s, cena>=%s ¥, miasta=%d\n\n",
    $year_from, $cfg['mileage_to'] ?? '?', $cfg['price_from'] ?? '?', count($cfg['city_filter_cities'] ?? []));
printf("  %-14s %9s %9s %12s %8s\n", 'MODEL', 'magazyn', 'filtrOK', 'cena min ¥', 'rok max');
printf("  %s\n", str_repeat('-', 58));
foreach ($stat as $mo => $s) {
    printf("  %-14s %9d %9d %12s %8s\n", $mo, $s['magazyn'], $s['filtrOK'],
        number_format($s['cena_min'] ?? 0), $s['rok_max'] ?? '-');
}
$suma_m = array_sum(array_column($stat, 'magazyn'));
$suma_f = array_sum(array_column($stat, 'filtrOK'));
printf("  %s\n  %-14s %9d %9d\n", str_repeat('-', 58), 'RAZEM', $suma_m, $suma_f);

foreach (['09 EM-P', '10 EM-P', 'Z20'] as $target) {
    printf("\n  %-9s → %s\n", $target,
        isset($stat[$target])
            ? sprintf('%d w magazynie, %d przechodzi filtr', $stat[$target]['magazyn'], $stat[$target]['filtrOK'])
            : 'ZERO w magazynie (w przeszukanym zakresie)');
}
