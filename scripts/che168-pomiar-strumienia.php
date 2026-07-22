<?php
/**
 * Pomiar kanału /changes che168 (fundament Sync::run) — ZERO zapisów.
 * Przelatuje batche od change_id z wczoraj, liczy typy zdarzeń i sprawdza,
 * ile 'added' przeszłoby filtr po normalizacji adapterem.
 */

$MAX_BATCHES = (int) ($argv[1] ?? 120);

$cfg_all = get_option('asiaauto_import_config', []);
$sim     = $cfg_all['che168'] ?? [];
$sim['marks'] = $cfg_all['dongchedi']['marks'] ?? [];   // szeroko, jak w poprzednim biegu
$zh = array_column($sim['city_filter_cities'] ?? [], 'zh');

add_filter('option_asiaauto_import_config', function ($v) use ($cfg_all, $sim) {
    $v = is_array($v) ? $v : $cfg_all;
    $v['che168'] = $sim;
    return $v;
}, 999);

$api      = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$importer = (new ReflectionClass('AsiaAuto_Importer'))->newInstanceWithoutConstructor();
$ref      = new ReflectionMethod('AsiaAuto_Importer', 'isAllowedByConfig');
$ref->setAccessible(true);

$yesterday = gmdate('Y-m-d', strtotime('-1 day'));
$cid = $api->getChangeId('che168', $yesterday);
echo "start change_id ({$yesterday}) = " . var_export($cid, true) . " | max batchy: {$MAX_BATCHES}\n\n";
if ($cid === null) { echo "BRAK change_id — kanał niedostępny\n"; return; }

$types = []; $n = 0; $b = 0; $added_ok = 0; $added_total = 0; $added_mapped = 0;
$keys_dumped = false; $why = []; $examples = []; $t0 = microtime(true);

while ($b < $MAX_BATCHES) {
    $resp = $api->getChanges('che168', $cid);
    if ($resp === null) { echo "błąd API na change_id={$cid}\n"; break; }
    $res = $resp['result'] ?? [];
    if (!$res) { echo "pusty batch — koniec strumienia na change_id={$cid}\n"; break; }

    foreach ($res as $c) {
        $t = $c['change_type'] ?? '?';
        $types[$t] = ($types[$t] ?? 0) + 1;
        $n++;
        if ($t !== 'added') continue;

        $raw = $c['data'] ?? [];
        if (!$keys_dumped && $raw) {
            echo "klucze data w zdarzeniu 'added': " . implode(', ', array_keys($raw)) . "\n\n";
            $keys_dumped = true;
        }
        if (empty($raw['mark'])) continue;
        $added_total++;

        $norm = AsiaAuto_Che168_Adapter::normalize($raw);
        $ok   = $ref->invoke($importer, $norm, 'che168');
        $mapped = AsiaAuto_Mapping::getEuForCn((string) ($norm['mark'] ?? ''), (string) ($norm['model'] ?? '')) !== null;
        if ($ok) {
            $added_ok++;
            if ($mapped) $added_mapped++;
            if (count($examples) < 15) {
                $examples[] = sprintf('%s|%s rok=%s km=%s cena=%s %s hub=%s',
                    $norm['mark'], $norm['model'] ?? '-', $norm['year'] ?? '-',
                    $norm['km_age'] ?? '-', $norm['price'] ?? '-', $norm['city'] ?? '-',
                    $mapped ? 'OK' : 'ORPHAN');
            }
        } else {
            $w = [];
            if (!in_array($norm['mark'] ?? '', $sim['marks'], true)) $w[] = 'marka spoza listy';
            if ((int) ($norm['year'] ?? 0) < $sim['year_from']) $w[] = 'rocznik';
            if (!empty($sim['km_to']) && (int) ($norm['km_age'] ?? 0) > $sim['km_to']) $w[] = 'przebieg';
            if (!empty($sim['price_from']) && (int) ($norm['price'] ?? 0) < $sim['price_from']) $w[] = 'cena';
            if (!in_array($norm['city'] ?? '', $zh, true)) $w[] = 'miasto';
            $k = $w ? implode('+', $w) : '???';
            $why[$k] = ($why[$k] ?? 0) + 1;
        }
    }

    $next = $resp['meta']['next_change_id'] ?? $cid;
    if ($next <= $cid) { echo "next_change_id <= cur — koniec strumienia\n"; break; }
    $cid = $next;
    $b++;
    usleep(120000);
}

$secs = round(microtime(true) - $t0);
echo "=== KANAŁ /changes che168 ===\n";
echo "batchy: {$b} | zdarzeń: {$n} | czas: {$secs}s\n";
echo "typy: " . json_encode($types) . "\n";
echo "ostatni change_id: {$cid}\n\n";
echo "'added' z markami: {$added_total} | przechodzi filtr: {$added_ok} | z tego w hubie: {$added_mapped}\n";

if ($why) { echo "\npowody odrzutu 'added':\n"; arsort($why); foreach ($why as $k => $v) printf("  %-34s %d\n", $k, $v); }
if ($examples) { echo "\nprzykłady 'added', które weszłyby:\n"; foreach ($examples as $e) echo "  $e\n"; }
echo "\n(zero zapisów do bazy)\n";
