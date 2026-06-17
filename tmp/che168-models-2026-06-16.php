<?php
/** Dump list modeli che168 dla naszych marek docelowych. */
$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$f = $api->getFilters('che168');
$d = isset($f['data']) ? $f['data'] : $f;
$marks = $d['mark'];

$targets = ['BYD','Denza','AITO 问界','Li Auto','IM','Tank','Voyah','VOYAH','WEY','Wey',
            'Haval','NIO','XPeng','Dongfeng','Dongfeng EV','Zeekr','Mazda','Avatr','SERES'];

foreach ($targets as $t) {
    if (!isset($marks[$t])) { echo "[$t] BRAK W TAKSONOMII\n"; continue; }
    $entry = $marks[$t];
    $models = $entry['model'] ?? $entry;
    if (!is_array($models)) { echo "[$t] model nie-tablica: " . json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n"; continue; }
    echo "[$t] (" . count($models) . "): " . implode(' | ', $models) . "\n";
}
