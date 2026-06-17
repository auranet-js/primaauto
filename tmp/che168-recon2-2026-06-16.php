<?php
/** Recon che168 #2: struktura result + filtrowanie po marce/modelu. */
$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);

function dump_offer($o) {
    return "id=" . ($o['inner_id'] ?? $o['id'] ?? '?')
        . " mark=" . ($o['mark'] ?? '?')
        . " model=" . ($o['model'] ?? '?')
        . " year=" . ($o['year'] ?? '?')
        . " | " . mb_substr($o['title'] ?? ($o['complectation'] ?? ''), 0, 45);
}

echo "=== getOffers page1 -> result/meta ===\n";
$o = $api->getOffers('che168', ['page' => 1]);
$res = $o['result'] ?? null;
$meta = $o['meta'] ?? null;
echo "meta: " . json_encode($meta, JSON_UNESCAPED_UNICODE) . "\n";
echo "result typ=" . gettype($res) . " count=" . (is_array($res) ? count($res) : 0) . "\n";
if (is_array($res) && $res) {
    $first = $res[0] ?? reset($res);
    echo "klucze oferty: " . implode(', ', array_keys($first)) . "\n";
    echo "1. oferta: " . dump_offer($first) . "\n";
    echo "3 przyklady:\n";
    foreach (array_slice($res, 0, 3) as $x) echo "  " . dump_offer($x) . "\n";
}

// Test filtrowania po marce — probujemy kilka nazw parametru
echo "\n=== TEST filtr po marce (Tank) ===\n";
foreach (['mark', 'brand', 'mark_name'] as $pk) {
    $r = $api->getOffers('che168', ['page' => 1, $pk => 'Tank']);
    $rr = $r['result'] ?? [];
    $marks_seen = [];
    foreach (array_slice($rr, 0, 8) as $x) $marks_seen[$x['mark'] ?? '?'] = true;
    echo "param '$pk'='Tank' -> " . count($rr) . " ofert, marki: " . implode(',', array_keys($marks_seen)) . "\n";
}

// Test filtr mark+model (Tank / Tank 300)
echo "\n=== TEST mark=Tank model=Tank 300 ===\n";
foreach ([['mark'=>'Tank','model'=>'Tank 300'], ['mark'=>'Tank','model'=>'300']] as $params) {
    $params['page'] = 1;
    $r = $api->getOffers('che168', $params);
    $rr = $r['result'] ?? [];
    echo json_encode($params, JSON_UNESCAPED_UNICODE) . " -> " . count($rr) . " ofert\n";
    foreach (array_slice($rr, 0, 3) as $x) echo "  " . dump_offer($x) . "\n";
}
