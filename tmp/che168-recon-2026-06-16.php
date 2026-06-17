<?php
/**
 * Recon che168: ustal nazwy marek + przetestuj getOffers (jak filtrować po marce/modelu).
 * Uruchom: wp eval-file tmp/che168-recon-2026-06-16.php
 */
$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);

$f = $api->getFilters('che168');
$d = isset($f['data']) ? $f['data'] : $f;
$marks = $d['mark'];

echo "=== mark: typ=" . gettype($marks) . " count=" . count($marks) . " ===\n";
$keys = array_keys($marks);
echo "pierwsze 10 kluczy: " . implode(' | ', array_slice($keys, 0, 10)) . "\n\n";

// Nasze marki docelowe (warianty nazw EN/CN)
$want = ['AITO','Aito','Seres','Li','Leapmotor','IM','NIO','XPeng','XPENG','Xpeng','Voyah','Zeekr',
         'Tank','Haval','WEY','Wey','Dongfeng','BYD','Denza','Mazda','Avatr','Hongqi'];
echo "=== klucze marek pasujące do naszych ===\n";
foreach ($keys as $k) {
    foreach ($want as $w) {
        if (stripos($k, $w) !== false) { echo "  [$k]\n"; break; }
    }
}

// Test getOffers — bez filtra (page 1) żeby zobaczyć strukturę
echo "\n=== getOffers('che168', ['page'=>1]) struktura ===\n";
$o = $api->getOffers('che168', ['page' => 1]);
$od = isset($o['data']) ? $o['data'] : $o;
if (is_array($od)) {
    echo "klucze top: " . implode(', ', array_keys($od)) . "\n";
    // znajdz liste ofert
    $list = null;
    foreach (['offers','items','list','data','results'] as $lk) {
        if (isset($od[$lk]) && is_array($od[$lk])) { $list = $od[$lk]; echo "lista pod '$lk', count=" . count($list) . "\n"; break; }
    }
    if ($list === null && isset($od[0])) { $list = $od; echo "lista = numeryczna, count=" . count($list) . "\n"; }
    if ($list) {
        $first = reset($list);
        echo "klucze 1. oferty: " . implode(', ', array_keys($first)) . "\n";
        echo "1. oferta: id=" . ($first['inner_id'] ?? $first['id'] ?? '?')
           . " mark=" . ($first['mark'] ?? '?')
           . " model=" . ($first['model'] ?? '?')
           . " title=" . mb_substr($first['title'] ?? '', 0, 40) . "\n";
    }
} else {
    echo "getOffers zwrócił nie-tablicę / pusto\n";
}
