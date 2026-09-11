<?php
/**
 * import-reczny-leapmotor-d19-2026-09-11.php — dociąg Leapmotor D19 z che168 po dopisaniu mapowania.
 *
 * Sync odrzucał D19 jako niezmapowany (`asiaauto_che168_unmapped`: `Leapmotor|Leapmotor D19`
 * od 05.09, `Leapmotor|零跑D19` od 31.08), bo brand-mapping miał pozostałe Leapmotory, a D19 nie.
 * Aliasy dopisane 11.09 (slug `leapmotor-d19` → istniejący hub, term 6604). Sync ich już
 * sam nie złapie: 'added' minął, a 'changed' nie pobiera oferty ponownie.
 *
 * Wybór: wszystkie D19 z che168 w naszych 31 miastach (3 z 12 w podaży). Pozostałe 9 odpada
 * wyłącznie na filtrze miast. Tożsamość sprawdzona w katalogu Autohome po spec_id:
 * 76081 = 零跑D19 2026款 纯电720智尊版 六座, 74726 = 零跑D19 2026款 增程500智尊版 六座.
 *
 * Użycie:
 *   wp eval-file scripts/import-reczny-leapmotor-d19-2026-09-11.php          # DRY-RUN
 *   wp eval-file scripts/import-reczny-leapmotor-d19-2026-09-11.php apply
 */

$apply = (($args[0] ?? '') === 'apply');

$WYBRANE = [
    // [zrodlo, inner_id, opis]
    ['che168', '59686421', 'Leapmotor D19 BEV 720 Supreme · Kanton · 226 000 CNY · 2026 · 4 000 km'],
    ['che168', '59811752', 'Leapmotor D19 EREV 500 Supreme · Shenzhen · 238 800 CNY · 2026 · 100 km'],
    ['che168', '59862969', 'Leapmotor D19 BEV 720 Supreme · Shenzhen · 238 800 CNY · 2026 · 7 000 km'],
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
