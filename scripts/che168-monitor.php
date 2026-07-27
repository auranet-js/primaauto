<?php
/**
 * che168-monitor.php — monitor podaży che168 wobec obecnych filtrów. READ-ONLY.
 *
 * Odpowiada na trzy pytania, na które historia synca nie odpowiada:
 *   1. Czy sync żyje (kursor, zaległość, biegi, cron).
 *   2. LUKA: co przy obecnych filtrach POWINNO być w bazie, a nie jest.
 *   3. DOMAPOWANIA: które brakujące wpisy w mapie odblokują ile ofert (priorytet po sztukach).
 *
 * Kluczowa różnica wobec dry-runu strumienia: mierzy MAGAZYN (getOffers per marka), bo
 * /changes to tylko okno zmian. Oferta leżąca w magazynie od miesiąca nie ma zdarzenia
 * 'added' — wejdzie dopiero przy 'changed' u źródła, więc luka jest realna, nie teoretyczna.
 *
 * Użycie:
 *   wp eval-file scripts/che168-monitor.php                  # raport dla marek z filtrów
 *   wp eval-file scripts/che168-monitor.php --mark="NIO"      # sonda marki PRZED dopięciem
 *   wp eval-file scripts/che168-monitor.php --pages=10        # głębiej w magazyn
 *   wp eval-file scripts/che168-monitor.php --quiet           # bez list, same liczby (cron)
 */

$opt = ['pages' => 6, 'mark' => null, 'quiet' => false];
foreach ((array) ($args ?? []) as $a) {
    if (preg_match('/^--pages=(\d+)$/', (string) $a, $m)) $opt['pages'] = (int) $m[1];
    elseif (preg_match('/^--mark=(.+)$/', (string) $a, $m)) $opt['mark'] = trim($m[1], "\"' ");
    elseif ($a === '--quiet') $opt['quiet'] = true;
}

$cfg_all = get_option('asiaauto_import_config', []);
$cfg     = $cfg_all['che168'] ?? [];
$zh      = array_column((array) ($cfg['city_filter_cities'] ?? []), 'zh');

$api      = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$importer = (new ReflectionClass('AsiaAuto_Importer'))->newInstanceWithoutConstructor();

// Tryb sondy: marka spoza filtrów — podmieniamy widziany config, bazy NIE ruszamy.
$probe = $opt['mark'] !== null;
if ($probe) {
    $sim = $cfg;
    $sim['marks'] = [$opt['mark']];
    $sim['model_blacklist'] = [];
    add_filter('option_asiaauto_import_config', function ($v) use ($cfg_all, $sim) {
        $v = is_array($v) ? $v : $cfg_all;
        $v['che168'] = $sim;
        return $v;
    }, 999);
    $marks = [$opt['mark']];
} else {
    $marks = (array) ($cfg['marks'] ?? []);
}

echo "==========================================================\n";
echo ' MONITOR che168 — ' . gmdate('Y-m-d H:i') . " UTC\n";
echo '==========================================================' . "\n\n";

// ── 1. CZY SYNC ŻYJE ────────────────────────────────────────────────
if (!$probe) {
    $cursor = (int) get_option('asiaauto_last_change_id_che168', 0);
    $head   = $api->getChangeId('che168', gmdate('Y-m-d'));
    $next   = wp_next_scheduled('asiaauto_sync_changes');
    echo "[1] SYNC\n";
    printf("  master=%s che168=%s status=%s\n",
        var_export(get_option('asiaauto_sync_enabled'), true),
        var_export(get_option('asiaauto_sync_enabled_che168'), true),
        AsiaAuto_Sync::statusForSource('che168'));
    printf("  cron: %s\n", $next ? gmdate('Y-m-d H:i', $next) . ' UTC (co ' . (wp_get_schedule('asiaauto_sync_changes') ?: '?') . ')' : 'NIEZAPLANOWANY (!)');
    printf("  kursor=%d | start doby=%s | zaległość=%s zdarzeń\n",
        $cursor, var_export($head, true), $head ? number_format(max(0, $head - $cursor), 0, ',', ' ') : '?');
    $h = (array) get_option('asiaauto_sync_history', []);
    $che = array_values(array_filter($h, fn($r) => ($r['source'] ?? '') === 'che168'));
    $sum24 = 0;
    foreach ($che as $r) {
        if (strtotime($r['ts'] ?? '') > time() - 86400) $sum24 += (int) ($r['added'] ?? 0);
    }
    printf("  biegi che168 w historii: %d | dodane w 24h: %d\n", count($che), $sum24);
    foreach (array_slice($che, 0, 5) as $r) {
        printf("    %s +%d ~%d -%d x%d b%d %s\n", $r['ts'], $r['added'], $r['changed'], $r['removed'], $r['skipped'], $r['batches'], $r['status']);
    }
    echo "\n";
}

// ── 2. LUKA: magazyn vs baza ────────────────────────────────────────
echo $probe ? "[SONDA MARKI: {$opt['mark']}]\n" : "[2] LUKA — co powinno być w bazie\n";
printf("  filtry: rocznik>=%s km<=%s cena>=%s ¥ miasta=%d%s\n",
    var_export($cfg['year_from'] ?? null, true), var_export($cfg['km_to'] ?? null, true),
    var_export($cfg['price_from'] ?? null, true), count($zh),
    $probe ? ' (blacklista wyłączona w sondzie)' : '');
printf("  marki: %s | stron/marka: %d\n\n", implode(', ', $marks), $opt['pages']);

$tot  = ['n' => 0, 'cfg' => 0, 'map' => 0, 'have' => 0, 'luka' => 0];
$orph = [];   // model => ['n'=>szt, 'ex'=>inner_id, 'price'=>..]
$luka = [];   // brakujące, gotowe do wzięcia
$capped = [];
$canon = [];  // "seriaCN|engine" => [hub => szt] — detektor rozjazdów mapowania

/**
 * Seria kanoniczna Autohome z pola 车型名称 (dostępne w /offers, /offer i /changes).
 * Format: "{seria CN} {rok}款 {wersja}" — np. "腾势N8L 2025款 旗舰型" → "腾势N8L".
 * To JEDYNY stabilny identyfikator serii w feedzie: pole `model` różni się między
 * endpointami tego samego API (/offers dawało "N8L DM", /offer "腾势N8L"), więc nazwy
 * modelu nie da się użyć jako klucza kontrolnego. Patrz sesja 2026-07-27 (hub N8L).
 */
function che168_seria_cn(array $raw): string {
    $cfg = (array) ((($raw['extra'] ?? [])['configuration']) ?? []);
    foreach ((array) ($cfg['paramtypeitems'] ?? []) as $grp) {
        foreach ((array) ($grp['paramitems'] ?? []) as $it) {
            if ((string) ($it['name'] ?? '') === '车型名称') {
                $v = trim((string) ($it['value'] ?? ''));
                if ($v === '') return '';
                return preg_match('/^(.*?)\s*\d{4}款/u', $v, $m) ? trim($m[1]) : $v;
            }
        }
    }
    return '';
}

printf("  %-16s %6s %8s %9s %7s %7s\n", 'MARKA', 'ofert', 'filtrOK', 'zmapow.', 'mamy', 'LUKA');
printf("  %s\n", str_repeat('-', 60));

foreach ($marks as $mark) {
    $n = $c = $mp = $hv = 0;
    $hit_cap = false;
    for ($page = 1; $page <= $opt['pages']; $page++) {
        $list = $api->getOffers('che168', ['mark' => $mark, 'year_from' => (int) ($cfg['year_from'] ?: 2024), 'page' => $page]);
        $res  = is_array($list) ? ($list['result'] ?? []) : [];
        if (!$res) break;
        foreach ($res as $row) {
            $raw = (array) ($row['data'] ?? $row);
            if (empty($raw['mark'])) continue;
            $d = AsiaAuto_Che168_Adapter::normalize($raw);
            $n++;
            if (!$importer->isAllowedByConfig($d, 'che168')) continue;
            $c++;
            $mk = (string) ($d['mark'] ?? '');
            $md = (string) ($d['model'] ?? '');

            // Detektor: ta sama seria kanoniczna CN + ten sam napęd MUSI trafiać w jeden hub.
            // Rozbicie serii po napędzie (D9 DM-i vs D9 EV) jest legalne, więc engine wchodzi
            // do klucza; rozjazd przy identycznym napędzie to błąd mapowania.
            $seria_cn = che168_seria_cn($raw);
            if ($seria_cn !== '') {
                $eu_d = AsiaAuto_Mapping::getEuForCn($mk, $md);
                $hub  = $eu_d === null ? '(orphan)' : (($eu_d['mark_eu'] ?? '?') . '/' . ($eu_d['serie_eu'] ?? '?'));
                $ck   = $seria_cn . ' [' . (string) ($d['engine_type'] ?? '?') . ']';
                $canon[$ck][$hub] = ($canon[$ck][$hub] ?? 0) + 1;
            }

            if (AsiaAuto_Mapping::getEuForCn($mk, $md) === null) {
                $k = $mk . '|' . $md;
                if (!isset($orph[$k])) $orph[$k] = ['n' => 0, 'ex' => (string) ($d['inner_id'] ?? ''), 'price' => (int) ($d['price'] ?? 0)];
                $orph[$k]['n']++;
                continue;
            }
            $mp++;
            $id = $importer->findByInnerId((string) ($d['inner_id'] ?? ''), 'che168');
            if ($id) { $hv++; continue; }
            $luka[] = sprintf('%s | %s %s (%s) | %s ¥ | %s km | %s | specid=%s',
                (string) ($d['inner_id'] ?? '?'), $mk, $md, (string) ($d['year'] ?? '?'),
                number_format((int) ($d['price'] ?? 0), 0, ',', ' '),
                number_format((int) ($d['km_age'] ?? 0), 0, ',', ' '),
                (string) ($d['city'] ?? '?'), (string) ($d['spec_id'] ?? '-'));
        }
        if (!($list['meta']['next_page'] ?? null)) break;
        if ($page === $opt['pages']) $hit_cap = true;
        usleep(150000);
    }
    if ($hit_cap) $capped[] = $mark;
    printf("  %-16s %6d %8d %9d %7d %7d\n", $mark, $n, $c, $mp, $hv, $mp - $hv);
    $tot['n'] += $n; $tot['cfg'] += $c; $tot['map'] += $mp; $tot['have'] += $hv;
}
$tot['luka'] = $tot['map'] - $tot['have'];
printf("  %s\n", str_repeat('-', 60));
printf("  %-16s %6d %8d %9d %7d %7d\n\n", 'RAZEM', $tot['n'], $tot['cfg'], $tot['map'], $tot['have'], $tot['luka']);

if ($capped) {
    echo '  ⚠ limit stron wyczerpany dla: ' . implode(', ', $capped) . " — liczby to DOLNA granica, podnieś --pages\n\n";
}

// ── 3. DOMAPOWANIA — priorytet po sztukach ──────────────────────────
echo "[3] DOMAPOWANIA — ile ofert odblokuje każdy wpis\n";
uasort($orph, fn($a, $b) => $b['n'] <=> $a['n']);
$blocked = array_sum(array_column($orph, 'n'));
printf("  zablokowane brakiem mapowania: %d ofert w %d modelach\n", $blocked, count($orph));
foreach ($orph as $k => $v) {
    printf("    %-34s %4d szt  (przykład %s, %s ¥)\n", $k, $v['n'], $v['ex'], number_format($v['price'], 0, ',', ' '));
}
if (!$orph) echo "    BRAK — wszystko co przechodzi filtr jest zmapowane\n";

if (!$probe) {
    $q = (array) get_option('asiaauto_che168_unmapped', []);
    echo "\n  kolejka ze strumienia (asiaauto_che168_unmapped, " . count($q) . " wpisów):\n";
    uasort($q, fn($a, $b) => ($b['count'] ?? 0) <=> ($a['count'] ?? 0));
    foreach (array_slice($q, 0, 12, true) as $k => $v) {
        printf("    %-34s x%-4d ostatnio %s\n", $k, (int) ($v['count'] ?? 0), substr((string) ($v['last_seen'] ?? ''), 0, 16));
    }
}

// ── 3b. DETEKTOR ROZJAZDÓW MAPOWANIA ────────────────────────────────
echo "\n[3b] ROZJAZDY MAPOWANIA (seria kanoniczna CN + napęd → hub)\n";
$rozjazdy = 0;
foreach ($canon as $ck => $hubs) {
    if (count($hubs) <= 1) continue;
    $rozjazdy++;
    arsort($hubs);
    echo "  ⚠ {$ck} rozbite na " . count($hubs) . " huby:\n";
    foreach ($hubs as $h => $cnt) printf("      %-28s %3d szt\n", $h, $cnt);
}
if (!$rozjazdy) {
    printf("  OK — %d serii kanonicznych, każda trafia w jeden hub\n", count($canon));
} else {
    printf("  ZNALEZIONO %d rozjazdów — każdy to dwa huby na to samo auto (rozcieńczenie SEO)\n", $rozjazdy);
}

// ── 4. WERDYKT ──────────────────────────────────────────────────────
echo "\n[4] WERDYKT\n";
printf("  do wzięcia TERAZ (zmapowane, brak w bazie): %d\n", $tot['luka']);
printf("  zablokowane brakiem wpisu w mapie:          %d\n", $blocked);
printf("  odsiane filtrami (poza segmentem):          %d\n", $tot['n'] - $tot['cfg']);
if ($probe) {
    echo "\n  Sonda: powyższe liczby pokazują, co marka '{$opt['mark']}' dałaby PO dopięciu do filtrów.\n";
    echo "  Baza i config nietknięte.\n";
}

if (!$opt['quiet'] && $luka) {
    echo "\n  LUKA — lista (" . count($luka) . "):\n";
    foreach ($luka as $l) echo "    {$l}\n";
}
