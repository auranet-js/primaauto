<?php
/**
 * Wyciąga chińskie nazwy nieznanych parametrów che168 (T-186) — READ-ONLY.
 *
 * Dla każdego `param_<id>` bez wpisu w che168-param-map.php podaje: nazwę CN,
 * grupę konfiguracji, jednostkę i zestaw obserwowanych wartości. To materiał wejściowy
 * do mapowania — bez tego mapowałoby się „na oko" (2 błędy semantyczne 22.07:
 * id 88 = moc układu ≠ silnika, id 84/105 = ładowanie szybkie ≠ wolne).
 *
 * Użycie: wp eval-file scripts/che168-nazwy-parametrow.php <plik.json> [max_ofert]
 */

$file = (string) ($args[0] ?? '');
$max  = (int) ($args[1] ?? 100);

if ($file === '' || !is_readable($file)) {
    echo "Podaj plik JSON z sondy.\n";
    return;
}

$data   = json_decode((string) file_get_contents($file), true);
$offers = $data['offers'] ?? [];

$sample = [];
foreach ($offers as $o) {
    $k = (string) ($o['key'] ?? '?');
    if (!isset($sample[$k])) { $sample[$k] = $o; }
}
$sample = array_slice($sample, 0, $max, true);

$map = include ASIAAUTO_PLUGIN_DIR . 'data/che168-param-map.php';
$map = is_array($map) ? $map : [];

$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);

printf("=== NAZWY NIEZNANYCH PARAMETRÓW che168 ===\n");
printf("badam %d ofert | wpisów w che168-param-map.php: %d\n\n", count($sample), count($map));

$found = [];
$done = 0;

foreach ($sample as $o) {
    $r = $api->getOffer('che168', (string) ($o['inner_id'] ?? ''));
    if (!$r) { continue; }
    $d = $r['data'] ?? ($r['result'][0]['data'] ?? $r);

    foreach (($d['extra']['configuration']['paramtypeitems'] ?? []) as $group) {
        $gname = (string) ($group['name'] ?? '?');
        foreach (($group['paramitems'] ?? []) as $item) {
            $id = (string) ($item['id'] ?? '');
            if ($id === '' || isset($map[$id]) || isset($map['param_' . $id])) {
                continue;
            }
            $name = (string) ($item['name'] ?? '?');
            $val  = (string) ($item['value'] ?? '');
            if (!isset($found[$id])) {
                $found[$id] = ['name' => $name, 'group' => $gname, 'n' => 0, 'vals' => []];
            }
            $found[$id]['n']++;
            if ($val !== '' && count($found[$id]['vals']) < 6 && !in_array($val, $found[$id]['vals'], true)) {
                $found[$id]['vals'][] = $val;
            }
        }
    }
    $done++;
    usleep(120000);
}

uasort($found, static fn($a, $b) => $b['n'] <=> $a['n']);

printf("zbadanych ofert: %d | nieznanych id: %d\n\n", $done, count($found));
printf("%-6s %-8s %-26s %-16s %s\n", 'id', 'wystąp', 'nazwa CN', 'grupa', 'obserwowane wartości');
echo str_repeat('-', 120) . "\n";
foreach ($found as $id => $f) {
    printf("%-6s %-8d %-26s %-16s %s\n",
        $id, $f['n'], $f['name'], $f['group'], implode(' | ', $f['vals']));
}

echo "\n(zero zapisów do bazy)\n";
