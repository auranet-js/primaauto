<?php
/**
 * PURE ANALYTICS — audyt pokrycia hubów pod kątem che168.
 * Przepuszcza cały katalog che168 (getFilters) przez resolveForSource('che168')
 * i sprawdza, które z naszych hubów (brand-mapping + realne serie) są OSIĄGALNE
 * (deterministyczna trasa) vs wpadłyby w fallback (null → surowy term → rozbity hub).
 * Zero zapisu.
 */
$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$f = $api->getFilters('che168');
$d = isset($f['data']) ? $f['data'] : $f;
$marks = $d['mark'];

function ci($s){ $s=preg_replace('/\s+/u',' ',trim($s)); return mb_strtolower($s,'UTF-8'); }

$totalPairs = 0; $resolved = 0; $nullCnt = 0;
$resolvedTargets = [];          // "mark_eu|serie_eu" (ci) => true
$che168MakeResolves = [];       // che168 mark => [resolved, total]

foreach ($marks as $cheMark => $entry) {
    $models = $entry['model'] ?? [];
    if (!is_array($models)) continue;
    foreach ($models as $cheModel) {
        $totalPairs++;
        $che168MakeResolves[$cheMark]['t'] = ($che168MakeResolves[$cheMark]['t'] ?? 0) + 1;
        $eu = AsiaAuto_Mapping::resolveForSource((string)$cheMark, (string)$cheModel, '', 'che168');
        if ($eu !== null && !empty($eu['mark_eu'])) {
            $resolved++;
            $che168MakeResolves[$cheMark]['r'] = ($che168MakeResolves[$cheMark]['r'] ?? 0) + 1;
            $key = ci($eu['mark_eu']) . '|' . ci($eu['serie_eu'] ?? '');
            $resolvedTargets[$key] = true;
        } else {
            $nullCnt++;
        }
    }
}

echo "=== che168 katalog (getFilters) ===\n";
echo "marek: " . count($marks) . " | par (marka×model): $totalPairs\n";
echo "resolveForSource: rozwiązane=$resolved (" . round(100*$resolved/$totalPairs) . "%)  null/fallback=$nullCnt\n";
echo "distinct hubów docelowych osiągniętych: " . count($resolvedTargets) . "\n\n";

// === Nasze huby z brand-mappingu (zmapowany uniwersum) ===
$bm = include ASIAAUTO_PLUGIN_DIR . 'data/brand-mapping-v6.1.php';
$bmHubs = []; foreach ($bm as $e){ if(!empty($e['mark_eu'])) $bmHubs[ci($e['mark_eu']).'|'.ci($e['serie_eu']??'')] = $e['mark_eu'].' / '.($e['serie_eu']??''); }
$bmReach = 0; $bmMiss = [];
foreach ($bmHubs as $k=>$label){ if(isset($resolvedTargets[$k])) $bmReach++; else $bmMiss[]=$label; }
echo "=== Huby brand-mappingu (zmapowane modele): " . count($bmHubs) . " ===\n";
echo "OSIĄGALNE z che168: $bmReach (" . round(100*$bmReach/max(1,count($bmHubs))) . "%)  NIEOSIĄGALNE: " . count($bmMiss) . "\n";
echo "Przykłady NIEOSIĄGALNYCH (che168 musiałby trafić, a nie trafia → fallback): \n";
foreach (array_slice($bmMiss,0,40) as $m) echo "   - $m\n";

// === Realne serie-huby (z ogłoszeniami) ===
$series = get_terms(['taxonomy'=>'serie','hide_empty'=>true]);
echo "\n=== Realne serie-huby z ogłoszeniami: " . count($series) . " ===\n";
// dla każdej serie ustal make (z 1 ogłoszenia) i sprawdz osiągalność
$reach=0; $miss=[]; $checked=0;
foreach ($series as $st) {
    $q = new WP_Query(['post_type'=>'listings','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids',
        'tax_query'=>[['taxonomy'=>'serie','field'=>'term_id','terms'=>$st->term_id]]]);
    if(!$q->posts) continue;
    $mk = wp_get_post_terms($q->posts[0],'make',['fields'=>'names']);
    $mk = is_wp_error($mk)||!$mk ? '' : $mk[0];
    if($mk==='') continue;
    $checked++;
    $k = ci($mk).'|'.ci($st->name);
    if(isset($resolvedTargets[$k])) $reach++; else $miss[]="$mk / {$st->name} ({$st->count})";
}
echo "sprawdzone (make+serie): $checked | OSIĄGALNE: $reach | NIEOSIĄGALNE: " . count($miss) . "\n";
echo "Przykłady NIEOSIĄGALNYCH realnych hubów: \n";
foreach (array_slice($miss,0,40) as $m) echo "   - $m\n";
