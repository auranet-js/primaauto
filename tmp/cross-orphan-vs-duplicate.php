<?php
require_once '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';
global $wpdb;

$fixed_term_ids = [6513,6532,6551,6493,3960,5080,3941,6534,6533,4290,6545,6548,6539,6549,3377,4539,6259,3938,6540,3980,3944,5734,3383,6537,4047,5206,4032,3993,4805,6543,4614,6542,6239,3504,4332,6546,6544,4411,5343,3972,3722,3638,3610,6541,4778,4327,6547,3494,6550,6271,5078,4412,4401,4399,4336,4298,4037,4286,4262,4458,4527,5625,5489,5215,5201,5197,5195,3982,4775,4044];

echo "=== Cross-check: 70 fixed orphans vs istniejące termy serie ===\n";
echo str_pad("orphan_id", 11) . str_pad("name", 30) . str_pad("slug", 28) . str_pad("count", 8) . "DUPLIKAT?\n";
echo str_repeat("-", 100) . "\n";

$dups = []; $uniques = [];
foreach ($fixed_term_ids as $tid) {
    $t = get_term($tid, 'serie');
    if (!$t || is_wp_error($t)) continue;
    
    // Szukaj innych termów serie o tej samej (znormalizowanej) nazwie
    $norm = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '', $t->name)));
    
    $candidates = $wpdb->get_results($wpdb->prepare("
        SELECT t.term_id, t.name, t.slug, tt.count, tt.parent
        FROM {$wpdb->terms} t
        JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
        WHERE tt.taxonomy='serie' AND t.term_id != %d AND tt.count > 0
    ", $tid));
    
    $match = null;
    foreach ($candidates as $c) {
        $cnorm = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '', $c->name)));
        // Heurystyka: dokładnie ta sama nazwa po normalizacji LUB suffix marki
        if ($cnorm === $norm) { $match = $c; break; }
        // Dla "Li i6" vs "i6", "Trumpchi M6" vs "M6", itd.
        if (strlen($cnorm) > 3 && (str_ends_with($norm, $cnorm) || str_ends_with($cnorm, $norm))) {
            // Dodatkowa walidacja: oba muszą mieć ten sam parent (po fix)
            $match = $c; $match->_partial = true; break;
        }
    }
    
    $row = sprintf("%-11d%-30s%-28s%-8d", $tid, mb_substr($t->name,0,28), $t->slug, $t->count);
    if ($match) {
        $partial = isset($match->_partial) ? ' (partial)' : '';
        $row .= "DUP: #{$match->term_id} '{$match->name}' (slug={$match->slug}, count={$match->count}, parent={$match->parent})$partial";
        $dups[] = ['orphan_id'=>$tid,'orphan_name'=>$t->name,'orphan_slug'=>$t->slug,'orphan_count'=>$t->count,'keeper_id'=>$match->term_id,'keeper_name'=>$match->name,'keeper_slug'=>$match->slug,'keeper_count'=>$match->count,'partial'=>!empty($match->_partial)];
    } else {
        $row .= "OK (unique)";
        $uniques[] = ['id'=>$tid,'name'=>$t->name,'slug'=>$t->slug,'count'=>$t->count];
    }
    echo $row . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "Duplikaty: " . count($dups) . "\n";
echo "Unikalne: " . count($uniques) . "\n";
echo "\n=== TYLKO DUPLIKATY (do merge) ===\n";
foreach ($dups as $d) {
    echo sprintf("MERGE #%d '%s' (count=%d) → #%d '%s' (slug=%s, count=%d)%s\n",
        $d['orphan_id'], $d['orphan_name'], $d['orphan_count'],
        $d['keeper_id'], $d['keeper_name'], $d['keeper_slug'], $d['keeper_count'],
        $d['partial'] ? ' [PARTIAL match — ZWERYFIKUJ]' : ''
    );
}
