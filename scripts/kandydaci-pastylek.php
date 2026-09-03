<?php
/**
 * kandydaci-pastylek.php — lista cech z extra_prep, które mogą zostać pastylkami wyszukiwarki.
 *
 * Liczy oferty publish, w których klucz ma wartość POZYTYWNĄ (ta sama reguła co flagi:
 * NEGATIVE + pierwszy wariant), pomija klucze już użyte we FLAGS, klucze liczbowe/opisowe
 * (regex $skip) i takie o >6 różnych wartościach (to enumy, nie flagi). Próg 50 wystąpień.
 * Na końcu wypisuje klucze związane z układem kierowniczym (pytanie o tylną oś skrętną).
 *
 * Użycie: php scripts/kandydaci-pastylek.php > docs/roadmapa/T-116-kandydaci-pastylek.txt
 * Wynik z 2026-09-03 + wybór Janka: docs/roadmapa/T-116-kandydaci-pastylek.md
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';
global $wpdb;
$tr = include '/home/host476470/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/data/translations-extra-prep.php';
$labels = $tr['labels'] ?? $tr['keys'] ?? [];
if (!$labels) { foreach ($tr as $k=>$v) if (is_array($v) && count($v)>100) { $labels=$v; break; } }
$rows = $wpdb->get_col("SELECT ep.meta_value FROM wp7j_asiaauto_specs s JOIN wp7j_postmeta ep ON ep.post_id=s.post_id AND ep.meta_key='_asiaauto_extra_prep' WHERE s.status='publish'");
$N = count($rows);
$uzyte = [];
foreach (AsiaAuto_Specs_Table::FLAGS as $f) foreach ($f['keys'] as $k) $uzyte[$k]=1;
foreach (['seat_material','skylight_type','sound_brand','variable_suspension','air_suspension','laser_radar','cruise_system','full_speed_adaptive_cruise','adaptive_cruise','rear_lcd_screen','navigation_assisted_driving_1','front_seat_massage_1','rear_seat_massage'] as $k) $uzyte[$k]=1;
$pos=[]; $vals=[]; $has=[];
foreach ($rows as $j) { $e=json_decode($j,true)?:[];
  foreach ($e as $k=>$v) { if(!is_scalar($v)) continue; $has[$k]=($has[$k]??0)+1;
    if (AsiaAuto_Specs_Table::isPositive($v)) { $pos[$k]=($pos[$k]??0)+1; $fv=AsiaAuto_Specs_Table::firstVariant($v); if(!isset($vals[$k][$fv])) $vals[$k][$fv]=0; $vals[$k][$fv]++; } } }
$skip='/size|number|count|capacity|power|torque|weight|length|width|height|speed|time|price|ratio|volume|resolution|ppi|_kw|mileage|range|consumption|rpm|material|brand|color|_type$|description|name|level|_mode$|displacement|cylinder|gear|wheelbase|clearance|tire|tyre|battery_|charge_time|charging_time|energy|structure|form$|adjustment|standard|emission|year|month|date|id$|url|image|spec|title|make|model|series|jb$|period|version|position|display_screen|screen_|dashboard|interior_light|drive_number|_type_|_kind|max_|min_|fuel|oil|tank|axle|suspension_type|steering_type|brake_type|body_|door|seat_count|seat_layout|_layout|warranty/i';
$out=[];
foreach ($pos as $k=>$c) { if ($c<50 || isset($uzyte[$k]) || preg_match($skip,$k)) continue;
  $distinct=count($vals[$k]); if ($distinct>6) continue;
  arsort($vals[$k]); $top=array_slice($vals[$k],0,3,true); $t=[]; foreach($top as $v=>$n) $t[]=mb_strimwidth($v,0,18,'…')."($n)";
  $lab=$labels[$k] ?? ''; if (is_array($lab)) $lab=$lab['label'] ?? $lab[0] ?? '';
  $out[]=[$k,$c,$lab,implode(', ',$t)]; }
usort($out, fn($a,$b)=>$b[1]<=>$a[1]);
echo "ofert: $N\n";
$i=0; foreach ($out as [$k,$c,$lab,$t]) { $i++; printf("%3d. %-42s %5d  %-38s %s\n",$i,$k,$c,mb_strimwidth($lab,0,38,'…'),$t); }
echo "\n--- oś skrętna / steering:\n";
foreach ($has as $k=>$c) if (preg_match('/steer|转向|rear_wheel/i',$k)) printf("  %-45s klucz w %d, pozytywnych %d: %s\n",$k,$c,$pos[$k]??0, isset($vals[$k])? implode(', ', array_map(fn($v,$n)=>"$v($n)", array_keys(array_slice($vals[$k],0,4,true)), array_slice($vals[$k],0,4))) : '');
