<?php
/**
 * Google Ads dynamic remarketing — feed MODEL-HUBÓW (serie) — Prima Auto.
 * READ-ONLY: czyta listings+terminy z prod DB, generuje JSON z payloadami assets:mutate.
 * NIE woła Ads API, NIE dotyka pluginu. Push robi osobny skrypt po akcepcie.
 *
 * Granularność: 1 asset = 1 model-hub (serie term). id = serie term_id (match z dynx_itemid
 * firowanym na single = serie term auta). finalUrl = get_term_link(serie) = /samochody/{make}/{serie}/.
 * Rotacja: cron tygodniowy (niedziela 06:00) — patrz scripts/refresh-rmkt-feed.sh.
 * Ceny rozjeżdżają się w ~6 tygodni (incydent 2026-07-12: 99% wpisów z błędną ceną).
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

$out = $argv[1] ?? (__DIR__ . '/gads-hub-feed.json');
const MAX_TITLE=25, MAX_SUBTITLE=25, MAX_DESC=25, MAX_CATEGORY=25, MAX_KW=10;
// marki nie-chińskie — wykluczone (spójnie z scripts/build-dsa-pagefeed.php)
$NON_CHINESE = ['volkswagen','volvo','nissan','mazda','audi','mg','smart','mini','lotus','lotus-cars','toyota','iveco'];

function clip($s,$n){ $s=trim((string)$s); return (mb_strlen($s)>$n)?trim(mb_substr($s,0,$n)):$s; }
function enc_url($u){ $p=parse_url($u); if(!$p||empty($p['path'])) return $u;
  $segs=array_map('rawurlencode', explode('/', $p['path']));
  return ($p['scheme']??'https').'://'.($p['host']??'').implode('/',$segs); }

global $wpdb;
// publikowane listingi z ceną>0
$rows = $wpdb->get_results("
  SELECT p.ID, CAST(pm.meta_value AS DECIMAL(12,0)) AS price
  FROM {$wpdb->posts} p
  JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='price'
  WHERE p.post_type='listings' AND p.post_status='publish' AND pm.meta_value+0 > 0
");
// pogrupuj po serie term
$hubs=[]; // serie_term_id => [make_id, prices[], listing_ids[], onlot, intransit]
foreach($rows as $r){
  $pid=(int)$r->ID; $price=(float)$r->price;
  $series=get_the_terms($pid,'serie'); if(!$series||is_wp_error($series)) continue;
  $serie=$series[0];
  $makes=get_the_terms($pid,'make'); $make=($makes&&!is_wp_error($makes))?$makes[0]:null;
  $res=(string)get_post_meta($pid,'_asiaauto_reservation_status',true);
  if(!isset($hubs[$serie->term_id])) $hubs[$serie->term_id]=[
    'serie'=>$serie,'make'=>$make,'prices'=>[],'pids'=>[],'onlot'=>0,'intransit'=>0];
  $h=&$hubs[$serie->term_id];
  $h['prices'][$pid]=$price; $h['pids'][]=$pid;
  if($res==='on_lot')$h['onlot']++; elseif($res==='in_transit')$h['intransit']++;
  if(!$h['make']&&$make)$h['make']=$make;
  unset($h);
}

$assets=[]; $no_img=0; $by_make=[];
$skip_nonchinese=0;
foreach($hubs as $sid=>$h){
  $serie=$h['serie']; $make=$h['make'];
  if($make && in_array($make->slug, $NON_CHINESE, true)){ $skip_nonchinese++; continue; }
  asort($h['prices']); // od najtańszego
  // reprezentatywne zdjęcie: pierwszy (najtańszy) listing z obrazem
  $img='';
  foreach(array_keys($h['prices']) as $pid){
    $gal=get_post_meta($pid,'gallery',true); $gal=is_array($gal)?$gal:[];
    $tid=(int)get_post_thumbnail_id($pid); if($tid&&!in_array($tid,$gal))array_unshift($gal,$tid);
    foreach($gal as $aid){ $u=wp_get_attachment_image_url((int)$aid,'large')?:wp_get_attachment_image_url((int)$aid,'full'); if($u){$img=enc_url($u);break 2;} }
  }
  if(!$img){ $no_img++; continue; }
  $min=(int)min($h['prices']); $cnt=count($h['pids']);
  $make_name=html_entity_decode($make?$make->name:'', ENT_QUOTES|ENT_HTML5, 'UTF-8');
  $model_name=html_entity_decode($serie->name, ENT_QUOTES|ENT_HTML5, 'UTF-8');
  $by_make[$make_name]=($by_make[$make_name]??0)+1;

  // tytuł: usuń nawiasowe aliasy (np. "(Tang L)") żeby nie ucinać w pół
  $title_src=trim(preg_replace('/\s*\([^)]*\)/','', "$make_name $model_name"));
  $title=clip($title_src!=='' ? $title_src : "$make_name $model_name", MAX_TITLE);
  // podtytuł = neutralny spec z reprezentatywnego listingu ($pid po pętli obrazu).
  // NIE deklarujemy dostępności: on_lot = status rezerwacji, NIE fizyczna obecność
  // (np. Xiaomi YU7 ma on_lot ale leży w Kantonie; Denza N9 on_lot ale „w drodze do UE").
  $btr=get_the_terms($pid,'body'); $body=($btr&&!is_wp_error($btr))?$btr[0]->name:'';
  $ftr=get_the_terms($pid,'fuel'); $fuel_raw=($ftr&&!is_wp_error($ftr))?$ftr[0]->name:'';
  $fk=mb_strtolower($fuel_raw);
  $fuel = str_contains($fk,'erev')||str_contains($fk,'range ext') ? 'EREV'
        : (str_contains($fk,'plug') ? 'Hybryda PHEV'
        : (str_contains($fk,'hybr') ? 'Hybryda'
        : (str_contains($fk,'elektry')||str_contains($fk,'(ev)') ? 'Elektryczny'
        : (str_contains($fk,'benzy')||str_contains($fk,'petrol') ? 'Benzyna'
        : (str_contains($fk,'diesel') ? 'Diesel' : trim($fuel_raw))))));
  $sub=clip(trim(implode(' · ', array_filter([$body, $fuel]))), MAX_SUBTITLE);
  if($sub==='') $sub='Import z Chin';
  // opis = sygnał wyboru/breadth (NIE dostępność)
  $desc=clip($cnt>1 ? "$cnt wersji w ofercie" : "Sprawdź ofertę", MAX_DESC);

  $kw=array_values(array_filter(array_unique([
    mb_strtolower(trim("$make_name $model_name")), mb_strtolower($make_name), 'import z chin'])));
  $kw=array_slice($kw,0,MAX_KW);

  $dca=['id'=>(string)$sid,'itemTitle'=>$title,'imageUrl'=>$img,'price'=>$min.' PLN',
        'itemSubtitle'=>$sub,'itemDescription'=>$desc];
  if($kw)$dca['contextualKeywords']=$kw;

  $assets[]=['finalUrls'=>[ get_term_link($serie) ], 'dynamicCustomAsset'=>$dca];
}

file_put_contents($out, json_encode($assets, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
fwrite(STDERR,"OK zapisano: $out\n");
fwrite(STDERR,"model-hubów: ".count($assets)." | pominięto bez obrazu: $no_img\n");
arsort($by_make);
fwrite(STDERR,"\n--- huby per marka (top 25) ---\n");
$i=0; foreach($by_make as $m=>$c){ fwrite(STDERR,sprintf("  %-22s %d\n",$m,$c)); if(++$i>=25)break; }
fwrite(STDERR,"\n--- SAMPLE (6) ---\n");
fwrite(STDERR, json_encode(array_slice($assets,0,6), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n");
