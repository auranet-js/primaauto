<?php
/**
 * Naprawa taksonomii po ręcznym zaciągu Ruslana z 2026-07-28.
 *
 * Ręczny import che168 nie ma guarda mapowania (sync ma), więc dla marek spoza
 * brand-mappingu importer tworzy termy fallbackiem: marka CJK, serie z parent=0,
 * nazwa z prefiksem marki. Ten skrypt prostuje 4 rozjazdy i nazewnictwo.
 *
 * Użycie:  wp eval-file scripts/fix-zaciag-2026-07-29.php [apply]
 * Domyślnie DRY-RUN. Backup: ~/backups/primaauto/2026-07-29/terms-przed-naprawa-zaciagu.sql
 */

$APPLY = in_array('apply', $args ?? [], true);
echo $APPLY ? "=== TRYB: APPLY ===\n\n" : "=== TRYB: DRY-RUN (dodaj argument: apply) ===\n\n";

$log = [];
function step(string $what): void { echo "  → $what\n"; }

/* ------------------------------------------------------------------ A. MAXUS */
echo "── A. MAXUS — serie spod śmieciowej marki 大通 #7209 pod Maxus #6683\n";
$maxus = [
    7210 => ['name' => 'V70',          'full' => 'Maxus V70'],
    7211 => ['name' => 'Interstellar', 'full' => 'Maxus Interstellar'],
    7212 => ['name' => 'G90',          'full' => 'Maxus G90'],
];
foreach ($maxus as $tid => $cfg) {
    $t = get_term($tid, 'serie');
    if (!$t || is_wp_error($t)) { step("#$tid BRAK — pomijam"); continue; }
    step("#$tid „{$t->name}\" parent {$t->parent} → 6683 | _serie_full_title = „{$cfg['full']}\"");
    if ($APPLY) {
        wp_update_term($tid, 'serie', ['parent' => 6683]);
        update_term_meta($tid, '_serie_full_title', $cfg['full']);
    }
}
step('usuń make #7209 „大通" (count=0, slug URL-encoded)');
if ($APPLY) { wp_delete_term(7209, 'make'); }

/* ----------------------------------------------------------------- B. SIENNA */
echo "\n── B. TOYOTA SIENNA — serie sierota (parent=0) pod Toyotę #3292\n";
$t = get_term(3293, 'serie');
if ($t && !is_wp_error($t)) {
    step("#3293 „{$t->name}\" parent {$t->parent} → 3292 | _serie_full_title = „Toyota Sienna\"");
    if ($APPLY) {
        wp_update_term(3293, 'serie', ['parent' => 3292]);
        update_term_meta(3293, '_serie_full_title', 'Toyota Sienna');
    }
}
step('usuń serie #7215 „华东牌商务车" i make #7214 „华东汽车" (oba count=0)');
if ($APPLY) { wp_delete_term(7215, 'serie'); wp_delete_term(7214, 'make'); }

/* ----------------------------------------------------------------- C. M-HERO */
echo "\n── C. M-HERO — marka Mengshi (10/mc) fold pod Dongfeng, wzorzec BYD Leopard\n";
$posts_mengshi = get_posts([
    'post_type' => 'listings', 'posts_per_page' => -1, 'post_status' => ['publish','draft','pending'],
    'fields' => 'ids', 'tax_query' => [['taxonomy' => 'make', 'field' => 'term_id', 'terms' => 5781]],
]);
step('posty z make=Mengshi → Dongfeng: ' . (implode(', ', $posts_mengshi) ?: 'brak'));
if ($APPLY) {
    foreach ($posts_mengshi as $pid) {
        wp_set_object_terms($pid, [5416], 'make', false);
        update_post_meta($pid, 'make', 'dongfeng');
    }
}
step('serie #5782 „M-Hero 917" parent 5781 → 5416 | _serie_full_title „Mengshi M-Hero 917" → „Dongfeng M-Hero 917"');
if ($APPLY) {
    wp_update_term(5782, 'serie', ['parent' => 5416]);
    update_term_meta(5782, '_serie_full_title', 'Dongfeng M-Hero 917');
}
step('usuń serie #7213 „Mengshi 917" (duplikat, count=0)');
step('usuń make #5781 „Mengshi" (po repoincie count=0) — 301 przez V61_MAKE_REDIRECTS');
if ($APPLY) { wp_delete_term(7213, 'serie'); wp_delete_term(5781, 'make'); }

/* ------------------------------------------------------------------ D. FOTON */
echo "\n── D. FOTON — nazwy eksportowe (Tunland 480/mc vs Mars 10/mc)\n";
step('serie #7207 „Foton Mars" → „Tunland V9" [tunland-v9] | full „Foton Tunland V9"  (火星9, spec 1015912)');
if ($APPLY) {
    wp_update_term(7207, 'serie', ['name' => 'Tunland V9', 'slug' => 'tunland-v9']);
    update_term_meta(7207, '_serie_full_title', 'Foton Tunland V9');
}
step('nowy serie „Tunland V7" [tunland-v7] parent 5052 | full „Foton Tunland V7"  (火星7, spec 1019109)');
step('  → przepnij post 392329 z #7207 na „Tunland V7"');
if ($APPLY) {
    $new = get_term_by('slug', 'tunland-v7', 'serie');
    if (!$new) {
        $r = wp_insert_term('Tunland V7', 'serie', ['slug' => 'tunland-v7', 'parent' => 5052]);
        $new_id = is_wp_error($r) ? 0 : $r['term_id'];
    } else { $new_id = $new->term_id; }
    if ($new_id) {
        update_term_meta($new_id, '_serie_full_title', 'Foton Tunland V7');
        wp_set_object_terms(392329, [$new_id], 'serie', false);
        update_post_meta(392329, 'serie', 'tunland-v7');
        echo "     (nowy term #$new_id)\n";
    }
}
step('serie #7208 „V New Energy" → „Toano Da V" [toano-da-v] | full „Foton Toano Da V"  (图雅诺大V新能源)');
if ($APPLY) {
    wp_update_term(7208, 'serie', ['name' => 'Toano Da V', 'slug' => 'toano-da-v']);
    update_term_meta(7208, '_serie_full_title', 'Foton Toano Da V');
    update_post_meta(392313, 'serie', 'toano-da-v');
}

/* ------------------------------------------------------------- E. MERCEDES */
echo "\n── E. MERCEDES EQE SUV — parent OK, brakuje tylko full_title\n";
step('serie #7206 „EQE SUV" | _serie_full_title = „Mercedes-Benz EQE SUV"');
if ($APPLY) { update_term_meta(7206, '_serie_full_title', 'Mercedes-Benz EQE SUV'); }

/* ------------------------------------------------------------- F. ZEEKR 9X */
echo "\n── F. ZEEKR 9X — bez zmian (hub zdrowy: 22 061 imp / 751 klików, treść v1)\n";

if ($APPLY) {
    echo "\n── czyszczenie cache termów\n";
    clean_term_cache([7206,7207,7208,7210,7211,7212,3293,5782], 'serie');
    delete_option('asiaauto_hub_index_cache');
    wp_cache_flush();
}
echo "\n" . ($APPLY ? "GOTOWE. Zweryfikuj URL-e." : "Nic nie zmieniono. Dodaj --apply.") . "\n";
