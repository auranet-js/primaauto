<?php
/**
 * import-reczny-wuling-huajing-s-2026-09-11.php — pierwsze sztuki Wuling Huajing S (华境S) z che168.
 *
 * 华境 to w che168 osobna marka `Huajing` (Autohome brandid 686, sub-marka SAIC-GM-Wuling), nieobecna
 * w filtrze marek — oferty odpadały na pierwszej bramce, nawet nie trafiały do kolejki domapowań.
 * 11.09: alias `Huajing|Huajing S` → Wuling / Huajing S (fold pod rodzica wg T-190) + `Huajing`
 * dopisane do che168.marks (decyzja Janka). Kolejne sztuki z naszych miast wejdą syncem.
 *
 * Wybór: cała podaż (3 z 3, wszystkie Guangdong). Status DRAFT — publikacja po akcepcie treści huba.
 * Tożsamość z katalogu Autohome: 77192 = 华境S 2026款 255km 乾崑臻享版, 74598 = 华境S 2026款 235km 四驱乾崑尊享版.
 *
 * Użycie:
 *   wp eval-file scripts/import-reczny-wuling-huajing-s-2026-09-11.php          # DRY-RUN
 *   wp eval-file scripts/import-reczny-wuling-huajing-s-2026-09-11.php apply
 */

$apply = (($args[0] ?? '') === 'apply');

$WYBRANE = [
    // [zrodlo, inner_id, opis]
    ['che168', '59765280', 'Huajing S PHEV 255 km Qiankun · Shenzhen · 185 799 CNY · 2026 · 10 km'],
    ['che168', '59836140', 'Huajing S PHEV 255 km Qiankun · Jiangmen · 173 000 CNY · 2026 · 9 000 km'],
    ['che168', '59861259', 'Huajing S PHEV 235 km 4WD Qiankun · Shenzhen · 188 800 CNY · 2026 · 100 km'],
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
