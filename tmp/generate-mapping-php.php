<?php
/**
 * Generuje wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php
 * z tmp/mapowanie-marek-modeli-v6.1.csv — mapa [markCN|modelCN] => [eu_mark, eu_serie, eu_title, slug_model]
 */

$src = __DIR__ . '/mapowanie-marek-modeli-v6.1.csv';
$dst = '/home/host476470/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php';

$dir = dirname($dst);
if (!is_dir($dir)) mkdir($dir, 0755, true);

$fp = fopen($src, 'r');
fgetcsv($fp);
$map = [];
while (($r = fgetcsv($fp)) !== false) {
    $markaCN = trim($r[2]);
    $markaEU = trim($r[3]);
    $modelCN = trim($r[4]);
    $serieEU = trim($r[5]);
    $titleEU = trim($r[6]);
    $slug    = trim($r[8]);
    $uwagi   = trim($r[12]);

    // Skip pustych i MINI
    if ($markaCN === '' || $modelCN === '') continue;
    if (stripos($uwagi, 'SKIP w imporcie') === 0) continue;

    $key = $markaCN . '|' . $modelCN;
    $slugModel = basename(rtrim($slug, '/'));
    $map[$key] = [
        'mark_eu'  => $markaEU,
        'serie_eu' => $serieEU,
        'title_eu' => $titleEU,
        'slug'     => $slugModel,
    ];
}
fclose($fp);

$php = "<?php\n";
$php .= "/**\n";
$php .= " * Brand/model mapping v6.1 — wygenerowane " . date('Y-m-d H:i:s') . "\n";
$php .= " * Źródło: tmp/mapowanie-marek-modeli-v6.1.csv\n";
$php .= " * Nie edytować ręcznie — regeneracja: tmp/generate-mapping-php.php\n";
$php .= " *\n";
$php .= " * Klucz: 'markaCN|modelCN' (dokładnie jak z API Dongchedi, kolumna E v6.1)\n";
$php .= " * Wartość: ['mark_eu', 'serie_eu', 'title_eu', 'slug']\n";
$php .= " */\n";
$php .= "\n";
$php .= "return " . var_export($map, true) . ";\n";

file_put_contents($dst, $php);
echo "Zapisano: $dst\n";
echo "Pozycji: " . count($map) . "\n";
