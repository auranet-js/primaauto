<?php
/**
 * SZEROKI dry-run che168 pod wpięcie syncu — ZERO zapisów do bazy.
 *
 * Symuluje config che168 z pełną listą marek dongchedi (65) — konfiguracji w bazie NIE zmienia.
 * Dla każdej marki: getOffers (1 strona, server-side mark+year) → adapter → PRAWDZIWY
 * isAllowedByConfig() (Reflection) w dwóch wariantach:
 *   - "dziś"  = dane surowe, tak jak automat podaje je importerowi teraz (adapter niewpięty)
 *   - "po fix"= dane po AsiaAuto_Che168_Adapter::normalize()
 */

$cfg_all = get_option('asiaauto_import_config', []);
$dc      = $cfg_all['dongchedi'] ?? [];
$che     = $cfg_all['che168'] ?? [];

// Symulowany config: limity/miasta che168 (te same co dongchedi), ale PEŁNA lista marek.
$sim = $che;
$sim['marks'] = $dc['marks'] ?? [];

$marks = $sim['marks'];
$zh    = array_column($sim['city_filter_cities'] ?? [], 'zh');
$bl    = $sim['model_blacklist'] ?? [];

echo "=== SYMULOWANY CONFIG (baza nietknięta) ===\n";
echo "marek: " . count($marks) . " | rocznik>= {$sim['year_from']} | km<= {$sim['km_to']} | cena>= {$sim['price_from']} ¥ | miast: " . count($zh) . "\n\n";

$api      = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$importer = (new ReflectionClass('AsiaAuto_Importer'))->newInstanceWithoutConstructor();
$ref      = new ReflectionMethod('AsiaAuto_Importer', 'isAllowedByConfig');
$ref->setAccessible(true);

// Podmiana configu widzianego przez isAllowedByConfig — filtr get_option, bez zapisu.
add_filter('option_asiaauto_import_config', function ($v) use ($cfg_all, $sim) {
    $v = is_array($v) ? $v : $cfg_all;
    $v['che168'] = $sim;
    return $v;
}, 999);

$tot = ['n' => 0, 'raw_ok' => 0, 'norm_ok' => 0, 'mapped' => 0, 'no_city' => 0];
$why_hist = [];
$per_mark = [];
$orphans  = [];
$keys_dumped = false;
$passed_examples = [];

foreach ($marks as $i => $mark) {
    $list = $api->getOffers('che168', ['mark' => $mark, 'year_from' => (int) ($sim['year_from'] ?: 2024), 'page' => 1]);
    $rows = is_array($list) ? ($list['result'] ?? []) : [];
    if (!$rows) { $per_mark[$mark] = [0, 0, 0]; continue; }

    if (!$keys_dumped) {
        $d0 = $rows[0]['data'] ?? $rows[0];
        echo "klucze rekordu z getOffers: " . implode(', ', array_keys($d0)) . "\n\n";
        $keys_dumped = true;
    }

    $m_n = $m_ok = $m_map = 0;
    foreach ($rows as $row) {
        $raw = $row['data'] ?? $row;
        if (empty($raw['mark'])) continue;

        $verdict_raw = $ref->invoke($importer, $raw, 'che168');
        $norm        = AsiaAuto_Che168_Adapter::normalize($raw);
        $verdict_norm= $ref->invoke($importer, $norm, 'che168');
        $mapped      = AsiaAuto_Mapping::getEuForCn((string) ($norm['mark'] ?? ''), (string) ($norm['model'] ?? '')) !== null;

        $tot['n']++; $m_n++;
        $tot['raw_ok']  += $verdict_raw ? 1 : 0;
        $tot['norm_ok'] += $verdict_norm ? 1 : 0;
        $tot['mapped']  += $mapped ? 1 : 0;
        $m_ok  += $verdict_norm ? 1 : 0;
        $m_map += $mapped ? 1 : 0;
        if (empty($raw['city'])) $tot['no_city']++;

        if (!$verdict_norm) {
            $w = [];
            if (!in_array($norm['mark'] ?? '', $marks, true)) $w[] = 'marka poza listą';
            if (!empty($sim['year_from']) && (int) ($norm['year'] ?? 0) < $sim['year_from']) $w[] = 'rocznik';
            if (!empty($sim['km_to']) && (int) ($norm['km_age'] ?? 0) > $sim['km_to']) $w[] = 'przebieg';
            if (!empty($sim['price_from']) && (int) ($norm['price'] ?? 0) < $sim['price_from']) $w[] = 'cena';
            if (!empty($sim['city_filter_enabled']) && !in_array($norm['city'] ?? '', $zh, true)) $w[] = 'miasto';
            $k = $w ? implode('+', $w) : '???';
            $why_hist[$k] = ($why_hist[$k] ?? 0) + 1;
        } else {
            if (count($passed_examples) < 12) {
                $passed_examples[] = sprintf('%s|%s → %s|%s  rok=%s km=%s cena=%s %s hub=%s',
                    $raw['mark'], $raw['model'] ?? '-', $norm['mark'], $norm['model'] ?? '-',
                    $norm['year'] ?? '-', $norm['km_age'] ?? '-', $norm['price'] ?? '-',
                    $norm['city'] ?? '-', $mapped ? 'OK' : 'ORPHAN');
            }
            if (!$mapped) {
                $ok = ($norm['mark'] ?? '') . '|' . ($norm['model'] ?? '');
                $orphans[$ok] = ($orphans[$ok] ?? 0) + 1;
            }
        }
    }
    $per_mark[$mark] = [$m_n, $m_ok, $m_map];
    usleep(150000);
}

echo "=== WYNIK (n = {$tot['n']} ofert, 1 strona/marka) ===\n";
printf("przechodzi filtr DZIŚ (automat bez adaptera): %d\n", $tot['raw_ok']);
printf("przechodzi filtr PO wpięciu adaptera:         %d\n", $tot['norm_ok']);
printf("trafia w istniejący hub:                      %d (%.0f%%)\n", $tot['mapped'], $tot['n'] ? 100 * $tot['mapped'] / $tot['n'] : 0);
printf("rekordów bez pola 'city' w surowcu:           %d\n", $tot['no_city']);

echo "\n--- powody odrzutu (po adapterze) ---\n";
arsort($why_hist);
foreach ($why_hist as $k => $v) printf("%-40s %d\n", $k, $v);

echo "\n--- marki, które coś przepuszczają ---\n";
foreach ($per_mark as $m => [$n, $ok, $map]) {
    if ($ok > 0) printf("%-22s ofert=%-3d przechodzi=%-3d w hubie=%d\n", $m, $n, $ok, $map);
}

echo "\n--- przykłady ofert, które weszłyby do importu ---\n";
foreach ($passed_examples as $e) echo "  $e\n";

if ($orphans) {
    echo "\n--- ORPHANY wśród przechodzących (do domapowania) ---\n";
    arsort($orphans);
    foreach ($orphans as $k => $v) printf("  %-40s %d\n", $k, $v);
}

echo "\n(zero zapisów do bazy)\n";
