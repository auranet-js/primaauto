<?php
/**
 * Sonda kanału /changes che168 (T-186) — READ-ONLY, ZERO zapisów do bazy.
 *
 * Przemiela zaległość kanału tak jak sync, ale ZAMIAST importować tylko zlicza:
 *   - ile zdarzeń `added` przechodzi filtr konfigu (`isAllowedByConfig`),
 *   - które pary mark|model są orphanami (kandydaci do che168-model-map / brand-mapping),
 *   - które miasta nie mają tłumaczenia w translations-cities.php,
 *   - rozkład marek / modeli / roczników / cen,
 *   - listę inner_id ofert przechodzących filtr → do celowanego audytu parametrów.
 *
 * Kosztuje 1 wywołanie API na ~2000 zdarzeń kanału. NIE pobiera zdjęć, NIE tłumaczy
 * opisów, NIE wywołuje getOffer(), NIE rusza kursora `asiaauto_last_change_id_che168`.
 *
 * Użycie: wp eval-file scripts/che168-sonda-kanalu.php <from_change_id> <max_stron> [plik_json]
 *   np.   wp eval-file scripts/che168-sonda-kanalu.php 9944251 40 /tmp/sonda.json
 */

$from  = (int) ($args[0] ?? 0);
$pages = (int) ($args[1] ?? 40);
$out   = (string) ($args[2] ?? '');

if ($from <= 0) {
    echo "Podaj change_id startowy: wp eval-file scripts/che168-sonda-kanalu.php 9944251 40\n";
    return;
}

$cfg    = get_option('asiaauto_import_config', []);
$che    = $cfg['che168'] ?? [];
$zh     = array_column($che['city_filter_cities'] ?? [], 'zh');
$cities = include ASIAAUTO_PLUGIN_DIR . 'data/translations-cities.php';
$cities = is_array($cities) ? $cities : [];

$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
// isAllowedByConfig() jest czystą funkcją (czyta tylko opcję configu, zero $this) — instancja
// bez konstruktora wystarcza i nie wciąga translatora ani media pipeline'u.
$importer = (new ReflectionClass('AsiaAuto_Importer'))->newInstanceWithoutConstructor();

printf("=== SONDA KANAŁU che168 (read-only) ===\n");
printf("od change_id: %d | max stron: %d | miast w filtrze: %d | słownik miast: %d\n\n",
    $from, $pages, count($zh), count($cities));

$change_id = $from;
$stat = ['events' => 0, 'added' => 0, 'passed' => 0, 'mapped' => 0, 'orphan' => 0];
$orphans = $passed_models = $years = $city_hist = $city_missing = $why = [];
$inner_ids = [];
$t0 = time();

for ($p = 1; $p <= $pages; $p++) {
    $resp = $api->getChanges('che168', $change_id);
    if ($resp === null) {
        echo "!! błąd API na change_id={$change_id} — przerywam\n";
        break;
    }
    $rows = $resp['result'] ?? [];
    if (!$rows) {
        printf("koniec kanału na change_id=%d (strona %d)\n", $change_id, $p);
        break;
    }

    foreach ($rows as $row) {
        $stat['events']++;
        if (($row['change_type'] ?? '') !== 'added') {
            continue;
        }
        $stat['added']++;

        $raw = $row['data'] ?? [];
        if (!$raw) {
            continue;
        }
        if (!isset($raw['inner_id']) && !empty($row['inner_id'])) {
            $raw['inner_id'] = $row['inner_id'];
        }

        $norm = AsiaAuto_Che168_Adapter::normalize($raw);

        if (!$importer->isAllowedByConfig($norm, 'che168')) {
            $w = [];
            if (!empty($che['year_from']) && (int) ($norm['year'] ?? 0) < $che['year_from'])   { $w[] = 'rocznik'; }
            if (!empty($che['km_to'])     && (int) ($norm['km_age'] ?? 0) > $che['km_to'])     { $w[] = 'przebieg'; }
            if (!empty($che['price_from'])&& (int) ($norm['price'] ?? 0) < $che['price_from']) { $w[] = 'cena'; }
            if (!empty($che['city_filter_enabled']) && !in_array($norm['city'] ?? '', $zh, true)) { $w[] = 'miasto'; }
            if (!in_array($norm['mark'] ?? '', $che['marks'] ?? [], true)) { $w[] = 'marka/model'; }
            $k = $w ? implode('+', $w) : '???';
            $why[$k] = ($why[$k] ?? 0) + 1;
            continue;
        }

        $stat['passed']++;
        $mark  = (string) ($norm['mark'] ?? '?');
        $model = (string) ($norm['model'] ?? '?');
        $key   = $mark . '|' . $model;

        if (AsiaAuto_Mapping::getEuForCn($mark, $model) !== null) {
            $stat['mapped']++;
            $passed_models[$key] = ($passed_models[$key] ?? 0) + 1;
        } else {
            $stat['orphan']++;
            if (!isset($orphans[$key])) {
                $orphans[$key] = ['count' => 0, 'example' => (string) ($norm['inner_id'] ?? ''),
                                  'raw' => (string) ($norm['mark_che168_raw'] ?? '') . '|' . (string) ($norm['model_che168_raw'] ?? ''),
                                  'engine' => (string) ($norm['engine_type'] ?? ''), 'price' => (int) ($norm['price'] ?? 0)];
            }
            $orphans[$key]['count']++;
        }

        $y = (string) ($norm['year'] ?? '?');
        $years[$y] = ($years[$y] ?? 0) + 1;

        $city = (string) ($norm['city'] ?? '');
        if ($city !== '') {
            $city_hist[$city] = ($city_hist[$city] ?? 0) + 1;
            if (!isset($cities[$city])) {
                $city_missing[$city] = ($city_missing[$city] ?? 0) + 1;
            }
        }

        if (!empty($norm['inner_id'])) {
            $inner_ids[] = ['inner_id' => (string) $norm['inner_id'], 'key' => $key,
                            'year' => $y, 'city' => $city, 'price' => (int) ($norm['price'] ?? 0)];
        }
    }

    // Kursor kanału idzie z meta.next_change_id (tak samo jak w AsiaAuto_Sync::run()) —
    // zdarzenia nie noszą własnego change_id.
    $next = (int) ($resp['meta']['next_change_id'] ?? $change_id);
    if ($next <= $change_id) {
        printf("kursor stoi na %d (strona %d) — koniec kanału\n", $change_id, $p);
        break;
    }
    $change_id = $next;

    if ($p % 50 === 0 || $p === 1) {
        printf("req %04d: change_id→%d  zdarzen=%d added=%d przechodzi=%d (hub=%d orphan=%d)  %ds\n",
            $p, $change_id, $stat['events'], $stat['added'], $stat['passed'], $stat['mapped'], $stat['orphan'], time() - $t0);
    }
    usleep(150000);
}

$dump = static function (string $title, array $arr, int $limit = 0) {
    arsort($arr);
    printf("\n--- %s (%d) ---\n", $title, count($arr));
    $i = 0;
    foreach ($arr as $k => $v) {
        printf("  %-46s %d\n", $k, is_array($v) ? ($v['count'] ?? 0) : $v);
        if ($limit && ++$i >= $limit) { printf("  ... (+%d więcej)\n", count($arr) - $limit); break; }
    }
};

printf("\n=== WYNIK (%.1f min) ===\n", (time() - $t0) / 60);
printf("zdarzeń: %d | added: %d | przechodzi filtr: %d | w hubie: %d | orphan: %d\n",
    $stat['events'], $stat['added'], $stat['passed'], $stat['mapped'], $stat['orphan']);
printf("trafialność w huby: %.0f%% przechodzących\n", $stat['passed'] ? 100 * $stat['mapped'] / $stat['passed'] : 0);

$dump('Powody odrzutu', $why);
$dump('ORPHANY do domapowania', $orphans);
$dump('Modele trafiające w huby', $passed_models, 30);
$dump('Roczniki', $years);
$dump('Miasta przechodzących', $city_hist, 25);
$dump('MIASTA BEZ TŁUMACZENIA (do translations-cities.php)', $city_missing);

if ($out !== '') {
    file_put_contents($out, json_encode([
        'from' => $from, 'to' => $change_id, 'stat' => $stat,
        'orphans' => $orphans, 'city_missing' => $city_missing,
        'offers' => $inner_ids,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    printf("\nzapisano %d ofert przechodzących do %s\n", count($inner_ids), $out);
}

echo "\n(zero zapisów do bazy, kursor nietknięty)\n";
