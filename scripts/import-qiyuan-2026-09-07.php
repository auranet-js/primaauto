<?php
/**
 * import-qiyuan-2026-09-07.php — ręczny import wybranych ofert sub-marki Qiyuan.
 *
 * Po co: te oferty spełniają rocznik, przebieg i cenę z konfiguracji, ale leżą w miastach
 * spoza listy 31 (17 różnych miast na 21 ofert — rozproszenie zbyt duże, żeby odblokować
 * je poszerzeniem filtra). Ścieżka identyczna z ręcznym importem z panelu:
 * `importListing($data, 'che168', force: true)` pomija `isAllowedByConfig()`.
 *
 * Wybór: rocznik 2026, przebiegi 10–14 500 km, ceny 134–168 tys. zł po sprowadzeniu.
 * Decyzja Janka 2026-09-07.
 *
 * Użycie:
 *   wp eval-file scripts/import-qiyuan-2026-09-07.php            # DRY-RUN
 *   wp eval-file scripts/import-qiyuan-2026-09-07.php apply      # import (status publish)
 */

$apply = (($args[0] ?? '') === 'apply');

$WYBRANE = [
    '58997060', // Qiyuan A07 2026, 10 km, 119 900 ¥, Ningbo
    '59267849', // Qiyuan A07 2026, 100 km, 120 200 ¥, Shangqiu
    '59234521', // Qiyuan A07 2026, 100 km, 124 100 ¥, Weifang
    '59171408', // Qiyuan A06 2026, 100 km, 138 800 ¥, Harbin
    '59210540', // Qiyuan A07 2026, 300 km, 127 899 ¥, Jinhua
    '59000088', // Qiyuan Q05 2026, 3 000 km, 109 800 ¥, Yanbian
    '58820383', // Qiyuan A06 2026, 4 200 km, 108 800 ¥, Nanchong
    '59278007', // Qiyuan A07 2026, 10 100 km, 115 600 ¥, Yangzhou
    '59192796', // Qiyuan Q05 2026, 14 500 km, 97 000 ¥, Kunming
];

$api        = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$translator = new AsiaAuto_Translator();
$importer   = new AsiaAuto_Importer($translator, new AsiaAuto_Media());

printf("=== IMPORT QIYUAN — %s ===\n", $apply ? 'APPLY (publish)' : 'DRY-RUN');
$ok = $err = $pominiete = 0;

foreach ($WYBRANE as $inner) {
    $istnieje = $importer->findByInnerId($inner, 'che168');
    if ($istnieje) { printf("  [POMIN] %s — juz w bazie jako post #%d\n", $inner, $istnieje); $pominiete++; continue; }

    $raw = (array) $api->getOffer('che168', $inner);
    if (!$raw || empty($raw['inner_id'])) { printf("  [BLAD]  %s — API nie zwrocilo danych\n", $inner); $err++; continue; }

    $data = AsiaAuto_Che168_Adapter::normalize($raw);
    if (empty($data['inner_id'])) $data['inner_id'] = $inner;
    if (AsiaAuto_Importer::isEmptyShell($data)) { printf("  [BLAD]  %s — pusta wydmuszka\n", $inner); $err++; continue; }

    $eu = AsiaAuto_Mapping::getEuForCn($data['mark'] ?? '', $data['model'] ?? '');
    if (!$eu) { printf("  [BLAD]  %s — orphan, brak mapowania dla %s|%s\n", $inner, $data['mark'] ?? '?', $data['model'] ?? '?'); $err++; continue; }

    $miasto = trim(explode(',', (string) ($data['address'] ?? ''))[0]);
    printf("  %s %s | %s %s | %s ¥ | rok %s | %s km | %s\n",
        $apply ? '[IMPORT]' : '[DRY]   ', $inner, $eu['mark_eu'], $eu['serie_eu'],
        number_format((int) ($data['price'] ?? 0), 0, ',', ' '), $data['year'] ?? '?',
        number_format((int) ($data['km_age'] ?? 0), 0, ',', ' '), $miasto);

    if (!$apply) { continue; }

    // force=true — swiadomie pomijamy filtr miast, tak jak reczny import z panelu
    $post_id = $importer->importListing($data, 'che168', true, 'publish');
    if (!$post_id) { printf("      -> IMPORT NIEUDANY\n"); $err++; continue; }
    update_post_meta($post_id, '_asiaauto_manual_import', '1');
    update_post_meta($post_id, '_asiaauto_manual_import_at', gmdate('c'));
    printf("      -> post #%d %s\n", $post_id, get_permalink($post_id));
    $ok++;
}

printf("\n=== PODSUMOWANIE ===\n  wybranych: %d | zaimportowane: %d | bledy: %d | pominiete: %d\n",
    count($WYBRANE), $ok, $err, $pominiete);
if (!$apply) echo "\n  DRY-RUN — nic nie zapisane. Import: dopisz 'apply'\n";
