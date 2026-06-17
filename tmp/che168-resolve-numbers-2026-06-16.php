<?php
/**
 * Resolver numerów che168 dla 20 modeli docelowych.
 * Dla każdego (mark, model) -> getOffers -> pierwszy żywy inner_id (z data).
 * Najpierw: szukaj osobnych marek dla niepewnych (Fangchengbao/eπ/WEY07).
 */
$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$f = $api->getFilters('che168');
$d = isset($f['data']) ? $f['data'] : $f;
$marks = $d['mark'];
$markKeys = array_keys($marks);

// --- 1. Szukaj osobnych marek dla niepewnych ---
echo "=== szukam marek: Fangchengbao/Bao/方程豹, eπ/奕派, deπ, WEY/魏 ===\n";
foreach ($markKeys as $k) {
    foreach (['Fangcheng','Bao','方程豹','eπ','奕派','魏','WEY','Voyah','岚图','aito','Wenjie','问界','Leopard','豹'] as $needle) {
        if (mb_stripos($k, $needle) !== false) { echo "  match '$needle' -> [$k]: " . implode(',', array_slice($marks[$k]['model'] ?? [], 0, 12)) . "\n"; break; }
    }
}

// --- 2. Resolver: kandydaci (label => [ [mark,model], ... ] ) ---
$cands = [
  '1 BYD Leopard 5'       => [['BYD','豹5'],['BYD','Leopard 5'],['Fangchengbao','Bao 5'],['方程豹','豹5']],
  '2 AITO M9'             => [['AITO 问界','AITO M9']],
  '3 AITO M8'             => [['AITO 问界','AITO M8']],
  '4 Li Auto L9'          => [['Li Auto','Li L9']],
  '5 IM LS9'              => [['IM','智己LS9']],
  '6 IM LS8'              => [['IM','智己LS8']],
  '7 Tank 300 Hi4-T'      => [['Tank','Tank 300 New Energy'],['Tank','Tank 300']],
  '8 AITO M7'             => [['AITO 问界','AITO M7']],
  '9 Voyah Dream PHEV'    => [['VOYAH','Dreamer']],
  '10 WEY 07'             => [['Wey','Wey Lanshan'],['Wey','07'],['Wey','Wey 07']],
  '11 Tank 300'           => [['Tank','Tank 300']],
  '12 Haval H9'           => [['Haval','H9']],
  '13 NIO ET5 Touring'    => [['NIO','ET5T']],
  '14 NIO ES6'            => [['NIO','ES6']],
  '15 XPeng P7+'          => [['XPeng','P7+']],
  '16 BYD Han DM-i'       => [['BYD','Han']],
  '17 XPeng P7'           => [['XPeng','P7']],
  '18 Dongfeng eπ008'     => [['Dongfeng','eπ008'],['Dongfeng EV','eπ008']],
  '19 Zeekr X'            => [['Zeekr','X']],
  '20 Mazda CX-5'         => [['Mazda','CX-5']],
];

function firstId($api, $mark, $model) {
    $r = $api->getOffers('che168', ['mark' => $mark, 'model' => $model, 'page' => 1]);
    $res = $r['result'] ?? [];
    if (!$res) return [0, null];
    $o = $res[0]['data'] ?? $res[0];
    $id = $res[0]['inner_id'] ?? $o['inner_id'] ?? $o['id'] ?? null;
    $rm = $o['mark'] ?? '?'; $rmod = $o['model'] ?? '?';
    return [count($res), ['id'=>$id,'mark'=>$rm,'model'=>$rmod,'title'=>mb_substr($o['title']??'',0,40)]];
}

echo "\n=== RESOLVE NUMERÓW ===\n";
foreach ($cands as $label => $pairs) {
    $done = false;
    foreach ($pairs as $p) {
        list($cnt, $hit) = firstId($api, $p[0], $p[1]);
        if ($cnt > 0 && $hit && $hit['id']) {
            echo sprintf("%-22s -> %s (mark=%s model=%s) [%d ofert via %s|%s]\n",
                $label, $hit['id'], $hit['mark'], $hit['model'], $cnt, $p[0], $p[1]);
            $done = true; break;
        }
    }
    if (!$done) echo sprintf("%-22s -> BRAK (próbowano: %s)\n", $label,
        implode(' ; ', array_map(fn($p)=>$p[0].'|'.$p[1], $pairs)));
}
