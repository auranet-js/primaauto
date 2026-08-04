<?php
/**
 * che168-zaciag-per-hub.php — zaciąg po N sztuk NA HUB dla wskazanych marek źródłowych.
 *
 * DLACZEGO ISTNIEJE (2026-08-03):
 * `che168-domknij-luke.php` przelatuje CAŁY magazyn wszystkich marek z filtrów i bierze
 * wszystko, co znajdzie. Gdy trzeba tylko ożywić konkretne huby (np. po dopięciu marek,
 * które sync dotąd odrzucał), potrzebny jest zaciąg płytki i szeroki: kilka sztuk na hub,
 * a nie komplet jednej marki. Limit liczony po `serie_eu` z mapowania, nie po marce.
 *
 * DRUGI POWÓD: domknij-luke woła `getOffers(mark)` nazwą Z CONFIGU, a config trzyma nazwy
 * PO normalizacji adaptera. Dla marek, gdzie te dwie nazwy się różnią (`GAC Aion Hyper` vs
 * źródłowe `Hyper`, `Fangchengbao` vs `Fang Cheng Bao`, `Dongfeng Yipai` vs `eπ`,
 * `AITO` vs `AITO 问界`), API zwraca pustkę i marka jest dla backfillu niewidzialna.
 * Tutaj podajemy nazwy ŹRÓDŁOWE wprost.
 *
 * Ścieżka importu identyczna jak w syncu i w domknij-luke: Che168_Adapter::normalize()
 * → isAllowedByConfig() → guard mapowania → getOffer() → importListing().
 *
 * Użycie:
 *   wp eval-file scripts/che168-zaciag-per-hub.php                          # DRY-RUN
 *   wp eval-file scripts/che168-zaciag-per-hub.php --apply --per-hub=3
 *   wp eval-file scripts/che168-zaciag-per-hub.php --marks="Galaxy,Yangwang" --apply
 */

$opt = ['apply' => false, 'pages' => 6, 'per_hub' => 3, 'marks' => null, 'status' => null, 'nowe_huby' => false];
foreach ((array) ($args ?? []) as $a) {
    if ($a === '--apply') $opt['apply'] = true;
    elseif ($a === '--nowe-huby') $opt['nowe_huby'] = true;
    elseif (preg_match('/^--pages=(\d+)$/', (string) $a, $m)) $opt['pages'] = (int) $m[1];
    elseif (preg_match('/^--per-hub=(\d+)$/', (string) $a, $m)) $opt['per_hub'] = (int) $m[1];
    elseif (preg_match('/^--marks=(.+)$/', (string) $a, $m)) $opt['marks'] = array_map('trim', explode(',', trim($m[1], "\"' ")));
    elseif (preg_match('/^--status=(publish|draft)$/', (string) $a, $m)) $opt['status'] = $m[1];
}

// Nazwy ŹRÓDŁOWE che168 (takie, jakie przyjmuje getOffers), nie te z configu.
$DOMYSLNE = [
    'Galaxy', 'Yangwang', 'Hyper', 'Shanhai', 'IM', 'Wey', 'smart',
    'Beijing Off-Road', 'Mengshi', '奥迪AUDI', 'Lotus',
];
$marks = $opt['marks'] ?: $DOMYSLNE;

$cfg    = get_option('asiaauto_import_config', [])['che168'] ?? [];
$status = $opt['status'] ?? AsiaAuto_Sync::statusForSource('che168');

$api        = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$translator = new AsiaAuto_Translator();
$media      = new AsiaAuto_Media();
$importer   = new AsiaAuto_Importer($translator, $media);

printf("=== ZACIĄG PER HUB che168 — %s ===\n", $opt['apply'] ? 'IMPORT' : 'DRY-RUN');
printf("marki źródłowe: %s\n", implode(', ', $marks));
printf("limit/hub: %d | stron/marka: %d | status: %s\n\n", $opt['per_hub'], $opt['pages'], $status);

$stat = ['n' => 0, 'cfg' => 0, 'orph' => 0, 'have' => 0, 'cap' => 0, 'nowy_hub' => 0, 'ok' => 0, 'err' => 0];
$naHub = [];   // serie_eu => ile wzięto
$done  = [];   // post_id => etykieta
$sieroty = [];
$nowe    = [];   // serie_eu, którego nie ma jeszcze w bazie

foreach ($marks as $mark) {
    for ($page = 1; $page <= $opt['pages']; $page++) {
        $list = $api->getOffers('che168', ['mark' => $mark, 'year_from' => (int) ($cfg['year_from'] ?: 2024), 'page' => $page]);
        $res  = is_array($list) ? ($list['result'] ?? []) : [];
        if (!$res) break;

        foreach ($res as $row) {
            $raw = (array) ($row['data'] ?? $row);
            if (empty($raw['mark'])) continue;
            $stat['n']++;

            $d = AsiaAuto_Che168_Adapter::normalize($raw);
            if (!$importer->isAllowedByConfig($d, 'che168')) continue;
            $stat['cfg']++;

            $mk = (string) ($d['mark'] ?? '');
            $md = (string) ($d['model'] ?? '');
            $eu = AsiaAuto_Mapping::getEuForCn($mk, $md);
            if ($eu === null) {
                $stat['orph']++;
                $k = "{$mk}|{$md}";
                $sieroty[$k] = ($sieroty[$k] ?? 0) + 1;
                continue;
            }

            $hub = (string) ($eu['serie_eu'] ?? '?');

            // GUARD (2026-08-03): bierzemy TYLKO do hubów, które już istnieją.
            //
            // Powód: mapa potrafi wskazać serie_eu, którego w bazie nie ma, choć hub tego auta
            // istnieje pod inną nazwą — 'LS8' obok zasiedlonego 'IM LS8', '#1'/'#3' obok
            // 'smart #1'/'smart #3'. Import stworzyłby wtedy DRUGI hub na ten sam model i
            // rozbił ruch (dokładnie casus N8L z 27.07 i merge'y z T-019). Cel tego zaciągu to
            // ożywić zasiedlone huby, a nie zakładać nowe — nowe nazwy idą do raportu, nie do bazy.
            if (!$opt['nowe_huby'] && get_term_by('name', $hub, 'serie') === false) {
                $stat['nowy_hub']++;
                $nowe[$hub] = ($nowe[$hub] ?? 0) + 1;
                continue;
            }

            // Huby SPORNE — term istnieje, ale obok niego stoi drugi na ten sam model
            // ('LS9' obok 'IM LS9', '#5' obok 'smart #5' i 'Smart #5 EHD Super Hybrid').
            // Dosypanie aut pogłębia rozdwojenie, więc czekają na decyzję o merge'u.
            if (!$opt['nowe_huby'] && in_array($hub, ['LS9', '#5'], true)) {
                $stat['nowy_hub']++;
                $nowe[$hub . ' (sporny — duplikat w bazie)'] = ($nowe[$hub . ' (sporny — duplikat w bazie)'] ?? 0) + 1;
                continue;
            }

            $iid = (string) ($d['inner_id'] ?? '');
            if ($importer->findByInnerId($iid, 'che168')) { $stat['have']++; continue; }

            if (($naHub[$hub] ?? 0) >= $opt['per_hub']) { $stat['cap']++; continue; }

            $label = sprintf('%s | %s / %s | %s ¥ | %s km | %s | %s', $iid,
                (string) ($eu['mark_eu'] ?? '?'), $hub,
                number_format((int) ($d['price'] ?? 0), 0, ',', ' '),
                number_format((int) ($d['km_age'] ?? 0), 0, ',', ' '),
                (string) ($d['year'] ?? '?'), (string) ($d['city'] ?? '?'));

            if (!$opt['apply']) {
                $naHub[$hub] = ($naHub[$hub] ?? 0) + 1;
                echo "  [DRY] {$label}\n";
                continue;
            }

            // Pełne dane — /offers zwraca listing skrócony (bez extra_prep), jak w syncu.
            $full = $api->getOffer('che168', $iid);
            if ($full !== null) {
                if (!isset($full['inner_id'])) $full['inner_id'] = $iid;
                $d2 = AsiaAuto_Che168_Adapter::normalize($full);
                $eu2 = AsiaAuto_Mapping::getEuForCn((string) ($d2['mark'] ?? ''), (string) ($d2['model'] ?? ''));
                if ($eu2 === null) {
                    echo "  [SKIP] {$label} — po getOffer wypadł z mapowania\n";
                    $stat['orph']++;
                    continue;
                }
                $d   = $d2;
                $hub = (string) ($eu2['serie_eu'] ?? $hub);
                if (($naHub[$hub] ?? 0) >= $opt['per_hub']) { $stat['cap']++; continue; }
            }

            $post_id = $importer->importListing($d, 'che168', false, $status);
            if ($post_id) {
                $stat['ok']++;
                $naHub[$hub] = ($naHub[$hub] ?? 0) + 1;
                $done[$post_id] = $label;
                printf("  [OK] #%d %s\n", $post_id, $label);
            } else {
                $stat['err']++;
                echo "  [ERR] {$label}\n";
            }
            usleep(400000); // throttle — auto-api zwraca 429 przy zbyt gęstych seriach
        }

        if (!($list['meta']['next_page'] ?? null)) break;
        usleep(200000);
    }
}

echo "\n=== PODSUMOWANIE ===\n";
printf("  przejrzane:              %d\n", $stat['n']);
printf("  przeszły filtr:          %d\n", $stat['cfg']);
printf("  orphany (brak mapy):     %d\n", $stat['orph']);
printf("  już w bazie:             %d\n", $stat['have']);
printf("  pominięte (limit/hub):   %d\n", $stat['cap']);
printf("  pominięte (nowy hub):    %d\n", $stat['nowy_hub']);
printf("  %s %d (błędy: %d)\n", $opt['apply'] ? 'ZAIMPORTOWANE:          ' : 'DO WZIĘCIA:             ', $stat['ok'] ?: array_sum($naHub), $stat['err']);

echo "\n  ROZKŁAD PO HUBACH:\n";
ksort($naHub);
foreach ($naHub as $h => $n) printf("    %-30s %d\n", $h, $n);

if ($nowe) {
    arsort($nowe);
    echo "\n  NOWE HUBY — mapa wskazuje serie_eu, którego NIE MA w bazie (pominięte, do decyzji):\n";
    foreach ($nowe as $k => $v) printf("    %-34s %d szt\n", $k, $v);
}

if ($sieroty) {
    arsort($sieroty);
    echo "\n  SIEROTY (przeszły filtr, brak wpisu w mapie):\n";
    foreach ($sieroty as $k => $v) printf("    %-34s %d szt\n", $k, $v);
}

if ($done) {
    echo "\n  ZAIMPORTOWANE ID: " . implode(', ', array_keys($done)) . "\n";
}
