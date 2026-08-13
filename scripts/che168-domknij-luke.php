<?php
/**
 * che168-domknij-luke.php — drugi kanał wejścia ofert che168: przelot MAGAZYNU.
 *
 * DLACZEGO ISTNIEJE (pomiar 2026-07-27):
 * Sync po /changes łapie wyłącznie zdarzenia 'added'. Zdarzenie 'changed' (42% strumienia)
 * ma w `data` JEDNO pole — `new_price` — więc prefilter w Sync::importWithFullData()
 * odrzuca je na braku marki i getOffer() nigdy nie leci. Oferta, która leży w magazynie
 * che168 od tygodni (jej 'added' był przed kursorem) NIE MA drugiej szansy przez sync.
 * Przelot magazynu (getOffers per marka, server-side mark+year) domyka tę lukę za 24
 * wywołania API, wobec ~7700 getOffer/dobę, których wymagałaby droga 'changed'.
 *
 * Ścieżka identyczna jak w syncu — Che168_Adapter::normalize() → guard mapowania →
 * importListing(). Zero zmian w pluginie, zero gałęzi per-source poza adapterem
 * (ADR 2026-06-17-che168-normalize-at-entry).
 *
 * Użycie:
 *   wp eval-file scripts/che168-domknij-luke.php                    # DRY-RUN (domyślnie)
 *   wp eval-file scripts/che168-domknij-luke.php --apply            # import
 *   wp eval-file scripts/che168-domknij-luke.php --apply --limit=5  # ostrożnie
 *   wp eval-file scripts/che168-domknij-luke.php --pages=10 --status=draft
 */

$opt = ['apply' => false, 'pages' => 6, 'limit' => 0, 'status' => null, 'marks' => null];
foreach ((array) ($args ?? []) as $a) {
    if ($a === '--apply') $opt['apply'] = true;
    elseif (preg_match('/^--pages=(\d+)$/', (string) $a, $m)) $opt['pages'] = (int) $m[1];
    elseif (preg_match('/^--limit=(\d+)$/', (string) $a, $m)) $opt['limit'] = (int) $m[1];
    elseif (preg_match('/^--status=(publish|draft)$/', (string) $a, $m)) $opt['status'] = $m[1];
    elseif (preg_match('/^--marks=(.+)$/', (string) $a, $m)) $opt['marks'] = array_map('trim', explode(',', trim($m[1], "\"' ")));
}

$cfg   = get_option('asiaauto_import_config', [])['che168'] ?? [];
// --marks zawęża i USTALA KOLEJNOŚĆ przelotu (przy --limit decyduje, co wejdzie najpierw).
// Filtry Ruslana zostają nietknięte — marka spoza konfiguracji i tak nie przejdzie isAllowedByConfig().
$marks = $opt['marks'] ?: (array) ($cfg['marks'] ?? []);
if (!$marks) { echo "Brak marek w filtrach che168 — nic do roboty.\n"; return; }

// Status importu bierzemy z tego samego źródła prawdy co sync, by kanały się nie rozjechały.
$status = $opt['status'] ?? AsiaAuto_Sync::statusForSource('che168');

$api        = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$translator = new AsiaAuto_Translator();
$media      = new AsiaAuto_Media();
$importer   = new AsiaAuto_Importer($translator, $media);

printf("=== DOMKNIĘCIE LUKI che168 — %s ===\n", $opt['apply'] ? 'IMPORT' : 'DRY-RUN');
printf("marki: %s | stron/marka: %d | status: %s%s\n\n",
    implode(', ', $marks), $opt['pages'], $status, $opt['limit'] ? " | limit: {$opt['limit']}" : '');

$stat = ['n' => 0, 'cfg' => 0, 'orph' => 0, 'have' => 0, 'new' => 0, 'ok' => 0, 'err' => 0];
$done = [];

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
            if (AsiaAuto_Mapping::getEuForCn($mk, $md) === null) { $stat['orph']++; continue; }

            $iid = (string) ($d['inner_id'] ?? '');
            if ($importer->findByInnerId($iid, 'che168')) { $stat['have']++; continue; }
            $stat['new']++;

            $label = sprintf('%s | %s %s (%s) | %s ¥ | %s', $iid, $mk, $md,
                (string) ($d['year'] ?? '?'), number_format((int) ($d['price'] ?? 0), 0, ',', ' '),
                (string) ($d['city'] ?? '?'));

            if (!$opt['apply']) { echo "  [DRY] {$label}\n"; continue; }
            if ($opt['limit'] && $stat['ok'] >= $opt['limit']) { echo "  limit {$opt['limit']} osiągnięty — stop\n"; break 3; }

            // Pełne dane: /offers zwraca listing skrócony (bez extra_prep) — jak sync,
            // dociągamy getOffer() i normalizujemy ponownie.
            $full = $api->getOffer('che168', $iid);
            if ($full !== null) {
                if (!isset($full['inner_id'])) $full['inner_id'] = $iid;
                $d = AsiaAuto_Che168_Adapter::normalize($full);
                if (AsiaAuto_Mapping::getEuForCn((string) ($d['mark'] ?? ''), (string) ($d['model'] ?? '')) === null) {
                    echo "  [SKIP] {$label} — po getOffer wypadł z mapowania\n";
                    $stat['orph']++;
                    continue;
                }
            }

            $post_id = $importer->importListing($d, 'che168', false, $status);
            if ($post_id) {
                $stat['ok']++;
                $done[] = $post_id;
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
printf("  przejrzane:            %d\n", $stat['n']);
printf("  przeszły filtr:        %d\n", $stat['cfg']);
printf("  orphany (brak mapy):   %d\n", $stat['orph']);
printf("  już w bazie:           %d\n", $stat['have']);
printf("  luka (do wzięcia):     %d\n", $stat['new']);
if ($opt['apply']) {
    printf("  ZAIMPORTOWANE:         %d (błędy: %d)\n", $stat['ok'], $stat['err']);
    if ($done) echo '  ID: ' . implode(', ', $done) . "\n";
} else {
    echo "\n  DRY-RUN — nic nie zapisane. Import: dopisz --apply\n";
}
