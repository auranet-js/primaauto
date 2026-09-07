<?php
/**
 * import-reczny-rox-aistaland-2026-09-07.php — ręczny dociąg pojedynczych sztuk ROX i Aistaland.
 *
 * Oferty spełniają rocznik, przebieg i cenę, ale leżą poza listą 31 miast (nasze prowincje to
 * Guangdong, Fujian, Guangxi, Hainan). Ścieżka jak w ręcznym imporcie z panelu:
 * `importListing($data, $source, force: true)` pomija `isAllowedByConfig()`.
 *
 * Wybór: rocznik 2026 z przebiegiem do 4 000 km, wschodnie wybrzeże przed głębią lądu.
 * Pominięty świadomie: ROX z Pekinu (rocznik 2024, 105 000 km — poza progiem km_to).
 * SkyNomad N70/N90 nie występuje w żadnym feedzie, więc nie ma go na liście.
 *
 * Użycie:
 *   wp eval-file scripts/import-reczny-rox-aistaland-2026-09-07.php          # DRY-RUN
 *   wp eval-file scripts/import-reczny-rox-aistaland-2026-09-07.php apply
 */

$apply = (($args[0] ?? '') === 'apply');

$WYBRANE = [
    // [zrodlo, inner_id, opis]
    ['che168',    '59080653', 'ROX 01 · Jinhua (Zhejiang) · 267 600 CNY · 2026 · 100 km'],
    ['che168',    '59080694', 'ROX 01 · Jinhua (Zhejiang) · 280 100 CNY · 2026 · 100 km'],
    ['che168',    '59709592', 'ROX 01 · Szanghaj · 253 400 CNY · 2026 · 500 km'],
    ['che168',    '59818007', 'ROX 01 · Xi\'an (Shaanxi) · 238 800 CNY · 2026 · 100 km'],
    ['dongchedi', '356736336', 'Aistaland GT7 · Xiamen (Fujian — nasza prowincja) · 280 000 CNY · 2026 · 4 000 km'],
    ['che168',    '59689680', 'Aistaland GT7 · Cangzhou (Hebei) · 235 000 CNY · 2026 · 1 000 km'],
];

$api        = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$translator = new AsiaAuto_Translator();
$importer   = new AsiaAuto_Importer($translator, new AsiaAuto_Media());

printf("=== RECZNY DOCIAG — %s ===\n\n", $apply ? 'APPLY (publish)' : 'DRY-RUN');
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

    // che168 wymaga normalizacji adapterem; dongchedi jest zrodlem kanonicznym
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
    printf("   -> %s | %s | ok. %s PLN po sprowadzeniu\n", $eu['mark_eu'], $eu['serie_eu'],
        number_format($cena['cena_koncowa'] ?? 0, 0, ',', ' '));

    if (!$apply) { echo "\n"; continue; }

    $post_id = $importer->importListing($data, $src, true, 'publish');
    if (!$post_id) { printf("   [BLAD] import nieudany\n\n"); $err++; continue; }
    update_post_meta($post_id, '_asiaauto_manual_import', '1');
    update_post_meta($post_id, '_asiaauto_manual_import_at', gmdate('c'));
    printf("   [OK] post #%d %s\n\n", $post_id, get_permalink($post_id));
    $ok++;
}

printf("=== PODSUMOWANIE ===\n  wybranych %d | zaimportowane %d | bledy %d | pominiete %d\n",
    count($WYBRANE), $ok, $err, $pomin);
if (!$apply) echo "\n  DRY-RUN — nic nie zapisane. Import: dopisz 'apply'\n";
