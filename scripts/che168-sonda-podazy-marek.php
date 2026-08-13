<?php
/**
 * READ-ONLY: podaż che168 per marka i model, z rozbiciem, KTÓRE kryterium blokuje.
 *
 * Odpowiada na pytanie „czym zapełnić hub, który ma ruch a nie ma ofert" — dla każdego
 * modelu pokazuje: ile leży w magazynie, ile przechodzi obecne filtry Ruslana, ile już mamy
 * w bazie, ile zostaje do wzięcia, a dla odrzuconych — na czym poległy (rocznik / przebieg /
 * cena / miasto). Dzięki temu widać, czy hub da się zapełnić bez ruszania filtrów.
 *
 * Zero zapisów. Wyjście: TSV na stdout (mark, model, magazyn, przez_filtr, w_bazie, luka,
 * blok_rocznik, blok_przebieg, blok_cena, blok_miasto).
 *
 * Użycie (WP-CLI zjada flagi w eval-file — patrz ADR 2026-08-13, dlatego przez eval):
 *   wp eval '$args=["--pages=10","--marks=BYD,Zeekr"]; include "scripts/che168-sonda-podazy-marek.php";'
 *   bez --marks: wszystkie marki z filtrów che168
 */

$opt = ['pages' => 10, 'marks' => null];
foreach ((array) ($args ?? []) as $a) {
    if (preg_match('/^--pages=(\d+)$/', (string) $a, $m)) $opt['pages'] = (int) $m[1];
    elseif (preg_match('/^--marks=(.+)$/', (string) $a, $m)) $opt['marks'] = array_map('trim', explode(',', trim($m[1], "\"' ")));
}

$cfg   = get_option('asiaauto_import_config', [])['che168'] ?? [];
$marks = $opt['marks'] ?: (array) ($cfg['marks'] ?? []);
if (!$marks) { echo "Brak marek.\n"; return; }

$year_from = (int) ($cfg['year_from'] ?? 0);
$km_to     = (int) ($cfg['km_to'] ?? 0);
$price_from = (int) ($cfg['price_from'] ?? 0);
$cities    = array_column((array) ($cfg['city_filter_cities'] ?? []), 'zh');

$api      = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$importer = new AsiaAuto_Importer(new AsiaAuto_Translator(), new AsiaAuto_Media());

echo "mark\tmodel\tmagazyn\tprzez_filtr\tw_bazie\tluka\tblok_rocznik\tblok_przebieg\tblok_cena\tblok_miasto\n";

foreach ($marks as $mark) {
    $stat = [];
    $seen = [];
    for ($page = 1; $page <= $opt['pages']; $page++) {
        $list = $api->getOffers('che168', ['mark' => $mark, 'year_from' => $year_from ?: null, 'page' => $page]);
        $res  = is_array($list) ? ($list['result'] ?? []) : [];
        if (!$res) break;

        foreach ($res as $row) {
            $raw = (array) ($row['data'] ?? $row);
            if (empty($raw['mark'])) continue;
            $d = AsiaAuto_Che168_Adapter::normalize($raw);
            $inner = (string) ($d['inner_id'] ?? '');
            if ($inner === '' || isset($seen[$inner])) continue;
            $seen[$inner] = true;

            $model = trim((string) ($d['model'] ?? '?'));
            if (!isset($stat[$model])) {
                $stat[$model] = ['magazyn' => 0, 'ok' => 0, 'baza' => 0,
                                 'rocznik' => 0, 'przebieg' => 0, 'cena' => 0, 'miasto' => 0];
            }
            $s = &$stat[$model];
            $s['magazyn']++;

            if ($importer->isAllowedByConfig($d, 'che168')) {
                $s['ok']++;
                if ($importer->findByInnerId($inner, 'che168')) $s['baza']++;
                unset($s);
                continue;
            }

            // Które kryterium poległo — liczone niezależnie, jedna oferta może wpaść w kilka.
            if ($year_from && (int) ($d['year'] ?? 0) < $year_from)        $s['rocznik']++;
            if ($km_to && (int) ($d['km_age'] ?? 0) > $km_to)              $s['przebieg']++;
            if ($price_from && (int) ($d['price'] ?? 0) < $price_from)     $s['cena']++;
            if ($cities) {
                $addr = (string) ($d['address'] ?? '');
                $hit = false;
                foreach ($cities as $c) { if ($c !== '' && mb_strpos($addr, $c) !== false) { $hit = true; break; } }
                if (!$hit) $s['miasto']++;
            }
            unset($s);
        }
        usleep(120000);
    }

    foreach ($stat as $model => $s) {
        printf("%s\t%s\t%d\t%d\t%d\t%d\t%d\t%d\t%d\t%d\n",
            $mark, $model, $s['magazyn'], $s['ok'], $s['baza'], $s['ok'] - $s['baza'],
            $s['rocznik'], $s['przebieg'], $s['cena'], $s['miasto']);
    }
}
