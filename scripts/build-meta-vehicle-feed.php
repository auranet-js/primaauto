<?php
/**
 * Meta vehicle catalog feed (Automotive Inventory Ads) — Prima Auto.
 * READ-ONLY: czyta listings z prod DB, generuje CSV. Nie dotyka pluginu.
 * Użycie: php build-meta-vehicle-feed.php [limit] [outfile]
 *   limit  -1 = wszystkie (domyślnie), liczba = pierwsze N (test)
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

$limit = isset($argv[1]) ? (int)$argv[1] : -1;
$out   = $argv[2] ?? (__DIR__ . '/meta-vehicles-sample.csv');

// adres dealera (z class-asiaauto-contact.php)
const DEALER_STREET='ul. Przemysłowa 13A', DEALER_POSTAL='35-105', DEALER_CITY='Rzeszów',
      DEALER_REGION='podkarpackie', DEALER_COUNTRY='PL', DEALER_NAME='Prima Auto';
const IMG_SLOTS=10;

global $wpdb;
$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type='listings' AND post_status='publish' ORDER BY ID DESC";
if ($limit>0) $sql .= " LIMIT ".$limit;
$ids = $wpdb->get_col($sql);

$cols = ['vehicle_id','title','description','url','make','model','year',
 'mileage.value','mileage.unit','state_of_vehicle','price','availability'];
for ($i=0;$i<IMG_SLOTS;$i++) $cols[]="image[$i].url";
$cols = array_merge($cols, ['exterior_color','body_style','fuel_type','transmission','drivetrain',
 'address.addr1','address.city','address.region','address.postal_code','address.country','dealer_name']);

function tname($pid,$tax){ $t=get_the_terms($pid,$tax); return ($t && !is_wp_error($t))?$t[0]->name:''; }
function tslug($pid,$tax){ $t=get_the_terms($pid,$tax); return ($t && !is_wp_error($t))?$t[0]->slug:''; }
function norm($s){ return strtolower(trim((string)$s)); }

function map_fuel($slug,$name){ $k=norm($slug?:$name); return match(true){
  str_contains($k,'electric')||$k==='ev'||str_contains($k,'elektry')=>'ELECTRIC',
  str_contains($k,'phev')||str_contains($k,'erev')||str_contains($k,'hybr')=>'HYBRID',
  str_contains($k,'petrol')||str_contains($k,'gasol')||str_contains($k,'benzy')=>'GASOLINE',
  str_contains($k,'diesel')=>'DIESEL', default=>'OTHER'}; }
function map_trans($slug,$name){ $k=norm($slug?:$name); return match(true){
  str_contains($k,'manu')=>'MANUAL',
  str_contains($k,'auto')||str_contains($k,'cvt')||str_contains($k,'dct')||str_contains($k,'dht')||str_contains($k,'dsg')||str_contains($k,'jednobieg')||str_contains($k,'single')=>'AUTOMATIC',
  default=>'OTHER'}; }
function map_drive($slug,$name){ $k=norm($slug?:$name); return match(true){
  str_contains($k,'awd')||str_contains($k,'all')=>'AWD',
  $k==='4wd'||str_contains($k,'4x4')||str_contains($k,'four')=>'4X4',
  str_contains($k,'fwd')||str_contains($k,'front')||str_contains($k,'prz')=>'FWD',
  str_contains($k,'rwd')||str_contains($k,'rear')||str_contains($k,'ty')=>'RWD',
  default=>'Other'}; }
function map_body($slug,$name){ $k=norm($slug?:$name); return match(true){
  str_contains($k,'suv')=>'SUV', str_contains($k,'cross')=>'CROSSOVER',
  str_contains($k,'sedan')||str_contains($k,'limuz')=>'SEDAN',
  str_contains($k,'hatch')||str_contains($k,'liftback')=>'HATCHBACK', str_contains($k,'kombi')||str_contains($k,'wagon')=>'WAGON',
  str_contains($k,'coupe')||str_contains($k,'coupé')=>'COUPE',
  str_contains($k,'kabrio')||str_contains($k,'conver')=>'CONVERTIBLE',
  str_contains($k,'van')||str_contains($k,'minivan')||str_contains($k,'mpv')=>'MINIVAN',
  str_contains($k,'pickup')||str_contains($k,'pick-up')||str_contains($k,'truck')=>'TRUCK',
  default=>'OTHER'}; }

$fh = fopen($out,'w');
fputcsv($fh,$cols);
$written=0; $skipped=0; $seen_terms=['body'=>[],'fuel'=>[],'trans'=>[],'drive'=>[]];
foreach ($ids as $pid){
  $price=(float)get_post_meta($pid,'price',true);
  if ($price<=0){ $skipped++; continue; }
  $inner=(string)get_post_meta($pid,'_asiaauto_inner_id',true);
  $vid = $inner!=='' ? $inner : (string)$pid; // parytet z pixelem (single.php): inner_id ?: post_id
  $make=tname($pid,'make'); $model=tname($pid,'serie'); $year=tname($pid,'ca-year');
  $mileage=(int)get_post_meta($pid,'mileage',true);
  $fuel_s=tslug($pid,'fuel'); $fuel_n=tname($pid,'fuel');
  $tr_s=tslug($pid,'transmission'); $tr_n=tname($pid,'transmission');
  $dr_s=tslug($pid,'drive'); $dr_n=tname($pid,'drive');
  $bo_s=tslug($pid,'body'); $bo_n=tname($pid,'body');
  $color=tname($pid,'color');
  $cond=tslug($pid,'condition');
  $state = $cond==='new' ? 'NEW' : 'USED';
  $title=trim((string)get_the_title($pid));
  $desc=$title;
  if($fuel_n)$desc.=', '.$fuel_n;
  if($mileage>0)$desc.=', '.number_format($mileage,0,',',' ').' km';
  $desc.=', od '.number_format($price,0,',',' ').' PLN. Import z Chin – Prima Auto.';
  $url=get_permalink($pid);
  // obrazy
  $gal=get_post_meta($pid,'gallery',true); $gal=is_array($gal)?$gal:[];
  $thumb_id=(int)get_post_thumbnail_id($pid);
  if($thumb_id && !in_array($thumb_id,$gal)) array_unshift($gal,$thumb_id);
  $imgs=[];
  foreach($gal as $aid){ $u=wp_get_attachment_image_url((int)$aid,'large')?:wp_get_attachment_image_url((int)$aid,'full'); if($u)$imgs[]=$u; if(count($imgs)>=IMG_SLOTS)break; }
  if(!$imgs){ $skipped++; continue; } // brak obrazu = Meta odrzuci wiersz

  $row=[$vid,$title,$desc,$url,$make,$model,$year,$mileage>0?$mileage:'', $mileage>0?'KM':'',
        $state, number_format($price,0,'','').' PLN','available'];
  for($i=0;$i<IMG_SLOTS;$i++) $row[]=$imgs[$i]??'';
  $row=array_merge($row,[$color, map_body($bo_s,$bo_n), map_fuel($fuel_s,$fuel_n),
        map_trans($tr_s,$tr_n), map_drive($dr_s,$dr_n),
        DEALER_STREET,DEALER_CITY,DEALER_REGION,DEALER_POSTAL,DEALER_COUNTRY,DEALER_NAME]);
  fputcsv($fh,$row); $written++;
  // diag: zbierz unikalne wartości terminów
  if($bo_n)$seen_terms['body'][$bo_n]=map_body($bo_s,$bo_n);
  if($fuel_n)$seen_terms['fuel'][$fuel_n]=map_fuel($fuel_s,$fuel_n);
  if($tr_n)$seen_terms['trans'][$tr_n]=map_trans($tr_s,$tr_n);
  if($dr_n)$seen_terms['drive'][$dr_n]=map_drive($dr_s,$dr_n);
}
fclose($fh);
fwrite(STDERR,"OK zapisano: $out\nwierszy: $written | pominięto (brak ceny/obrazu): $skipped\n");
fwrite(STDERR,"\nMAPOWANIA (term -> Meta), sprawdź czy nie ma OTHER tam gdzie nie trzeba:\n");
foreach($seen_terms as $tax=>$m){ fwrite(STDERR,"  [$tax] ".json_encode($m,JSON_UNESCAPED_UNICODE)."\n"); }
