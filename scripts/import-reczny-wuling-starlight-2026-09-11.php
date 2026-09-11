<?php
/**
 * import-reczny-wuling-starlight-2026-09-11.php — pierwsze sztuki Wuling Starlight (星光) z che168, 5 modeli.
 *
 * Marka Wuling nie była w filtrze che168 — 11.09 dopisana (decyzja Janka) razem z 5 mapowaniami:
 * Xingguang → Starlight (sedan), Xingguang S → Starlight S (kompaktowy SUV), Xingguang L → Starlight L
 * (6-miejscowy SUV), Xingguang 560 / 730 New Energy → Starlight 560 / 730 (tylko warianty NEV — spalinowe
 * leżą poniżej progu 85 000 CNY). Niezmapowane modele Wuling nadal lądują w kolejce domapowań.
 *
 * Wybór: cała podaż przechodząca filtr na 11.09 (11 sztuk). Status DRAFT — publikacja razem z treścią hubów.
 * 59337927 nie ma spec_id, ale ma 72 pola extra_prep i nie jest wydmuszką — biorę.
 *
 * Użycie:
 *   wp eval-file scripts/import-reczny-wuling-starlight-2026-09-11.php          # DRY-RUN
 *   wp eval-file scripts/import-reczny-wuling-starlight-2026-09-11.php apply
 */

$apply = (($args[0] ?? '') === 'apply');

$WYBRANE = [
    // [zrodlo, inner_id, opis]
    ['che168', '58230756', 'Starlight sedan BEV 610 km · Liuzhou · 89 000 CNY · 2025 · 7 800 km'],
    ['che168', '59336433', 'Starlight sedan PHEV 150 km · Liuzhou · 85 800 CNY · 2026 · 100 km'],
    ['che168', '59587456', 'Starlight sedan BEV 510 · Liuzhou · 85 800 CNY · 2026 · 100 km'],
    ['che168', '59337927', 'Starlight S BEV 510 km · Liuzhou · 89 800 CNY · 2026 · 100 km (bez spec_id)'],
    ['che168', '59335609', 'Starlight S BEV 510 km · Liuzhou · 95 800 CNY · 2025 · 100 km'],
    ['che168', '59357029', 'Starlight L PHEV 260 km 6 miejsc · Nanning · 123 800 CNY · 2026 · 2 300 km'],
    ['che168', '59824706', 'Starlight 560 BEV 500 km · Nanning · 85 500 CNY · 2025 · 5 600 km'],
    ['che168', '59388372', 'Starlight 730 PHEV 125 km · Liuzhou · 93 800 CNY · 2025 · 3 000 km'],
    ['che168', '59612218', 'Starlight 730 BEV 500 km · Shenzhen · 93 800 CNY · 2025 · 26 000 km'],
    ['che168', '59737112', 'Starlight 730 PHEV 125 km · Liuzhou · 91 199 CNY · 2025 · 3 100 km'],
    ['che168', '59636433', 'Starlight 730 BEV 500 km · Kanton · 96 800 CNY · 2025 · 5 000 km'],
];

$api        = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$translator = new AsiaAuto_Translator();
$importer   = new AsiaAuto_Importer($translator, new AsiaAuto_Media());

printf("=== RECZNY DOCIAG — %s ===\n\n", $apply ? 'APPLY (draft)' : 'DRY-RUN');
$ok = $err = $pomin = 0;

foreach ($WYBRANE as [$src, $inner, $opis]) {
    printf("%s\n", $opis);
    if ($istnieje = $importer->findByInnerId($inner, $src)) {
        printf("   [POMIN] juz w bazie jako post #%d\n\n", $istnieje);
        $pomin++;
        continue;
    }
    $raw = (array) $api->getOffer($src, $inner);
    if (!$raw || empty($raw['inner_id'])) { printf("   [BLAD] API nie zwrocilo danych\n\n"); $err++; continue; }

    $data = ($src === 'che168') ? AsiaAuto_Che168_Adapter::normalize($raw) : $raw;
    if (empty($data['inner_id'])) $data['inner_id'] = $inner;
    if (AsiaAuto_Importer::isEmptyShell($data)) { printf("   [BLAD] pusta wydmuszka\n\n"); $err++; continue; }

    $eu = AsiaAuto_Mapping::getEuForCn((string) ($data['mark'] ?? ''), (string) ($data['model'] ?? ''));
    if (!$eu) {
        printf("   [BLAD] orphan — brak mapowania dla %s|%s\n\n", $data['mark'] ?? '?', $data['model'] ?? '?');
        $err++;
        continue;
    }
    $cena = AsiaAuto_Price::calculateFromCny((int) ($data['price'] ?? 0), null, (string) ($data['engine_type'] ?? ''));
    printf("   -> %s | %s | slug %s | ok. %s PLN po sprowadzeniu\n", $eu['mark_eu'], $eu['serie_eu'], $eu['slug'],
        number_format($cena['cena_koncowa'] ?? 0, 0, ',', ' '));

    if (!$apply) { echo "\n"; continue; }

    $post_id = $importer->importListing($data, $src, true, 'draft');
    if (!$post_id) { printf("   [BLAD] import nieudany\n\n"); $err++; continue; }
    update_post_meta($post_id, '_asiaauto_manual_import', '1');
    update_post_meta($post_id, '_asiaauto_manual_import_at', gmdate('c'));
    printf("   [OK] post #%d %s\n\n", $post_id, get_permalink($post_id));
    $ok++;
}

printf("=== PODSUMOWANIE ===\n  wybranych %d | zaimportowane %d | bledy %d | pominiete %d\n",
    count($WYBRANE), $ok, $err, $pomin);
if (!$apply) echo "\n  DRY-RUN — nic nie zapisane. Import: dopisz 'apply'\n";
