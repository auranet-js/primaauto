<?php
/**
 * naprawa-fulwin-passion.php — scala sieroty Voyah Passion L i Chery Fulwin T10,
 * porządkuje nazewnictwo serii pod Chery Fulwin.
 *
 * Powód: sync dongchedi nie ma guarda mapowania (`normalizeForSource()` przepuszcza
 * wszystko poza che168), więc niezmapowane pary mark|model tworzą taksonomię fallbackiem.
 * Zmierzone 2026-07-29: "Voyah|Voyah Zhuiguang L" i "Chery Fengyun|Fengyun T10" → getEuForCn NULL.
 *
 * Kierunek scalenia potwierdzony dwoma źródłami:
 *   passion (DFS 320/mc, GSC 39 imp) vs zhuiguang (DFS —, GSC 1 imp)
 *   fulwin  (DFS  90/mc, GSC 91 imp) vs fengyun   (DFS 30/mc, GSC 4 imp)
 * Nazwa "T10" (nie "Fengyun T10") — DFS nie ma danych dla żadnego wariantu, więc
 * rozstrzyga konwencja większości pod tą marką: T8, T9, T11, A8L, A9L, X3 PLUS.
 *
 * Uzycie: php naprawa-fulwin-passion.php [apply]
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

$apply = in_array('apply', $argv, true);
$log   = [];
function krok(string $s) { global $log; $log[] = $s; echo $s . "\n"; }

/** Przenosi oferty pod term docelowy razem z meta `make`/`serie` (nie aktualizują się same). */
function przeniesOferty(int $zTerm, int $doTerm, bool $apply): int {
    $ids = get_posts([
        'post_type' => 'listings', 'numberposts' => -1, 'fields' => 'ids',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'tax_query' => [['taxonomy' => 'serie', 'field' => 'term_id', 'terms' => $zTerm]],
    ]);
    if (!$ids) return 0;

    $cel     = get_term($doTerm, 'serie');
    $celMake = $cel->parent ? get_term($cel->parent, 'make') : null;

    foreach ($ids as $id) {
        krok(sprintf('    #%d %s', $id, get_the_title($id)));
        if (!$apply) continue;
        wp_set_object_terms($id, [$doTerm], 'serie', false);
        update_post_meta($id, 'serie', $cel->slug);
        if ($celMake) {
            wp_set_object_terms($id, [$celMake->term_id], 'make', false);
            update_post_meta($id, 'make', $celMake->slug);
        }
    }
    return count($ids);
}

function ustawFullTitle(int $tid, string $ft, bool $apply): void {
    $t = get_term($tid, 'serie');
    if (!$t || is_wp_error($t)) { krok("    (brak termu #$tid)"); return; }
    $stare = get_term_meta($tid, '_serie_full_title', true);
    krok(sprintf('    #%d %-24s _serie_full_title: "%s" -> "%s"', $tid, $t->name, $stare, $ft));
    if ($apply) update_term_meta($tid, '_serie_full_title', $ft);
}

echo $apply ? "=== APPLY ===\n\n" : "=== DRY-RUN (dodaj: apply) ===\n\n";

/* ─────────────── 1. VOYAH PASSION ─────────────── */
krok('── Voyah Passion');
krok('  scalenie sieroty #7217 "Voyah Zhuiguang L" -> #5081 "passion-l":');
$n = przeniesOferty(7217, 5081, $apply);
krok("  przeniesionych: $n");

krok('  nazwy termow (konwencja: nazwa bez marki, marka w _serie_full_title):');
foreach ([5081 => ['Passion L', 'Voyah Passion L'], 5078 => ['Passion PHEV', 'Voyah Passion PHEV']] as $tid => $v) {
    $t = get_term($tid, 'serie');
    krok(sprintf('    #%d nazwa: "%s" -> "%s"', $tid, $t->name, $v[0]));
    if ($apply) wp_update_term($tid, 'serie', ['name' => $v[0]]);   // slug BEZ zmian (passion-l zostaje)
    ustawFullTitle($tid, $v[1], $apply);
}

/* ─────────────── 2. CHERY FULWIN ─────────────── */
krok('');
krok('── Chery Fulwin');
krok('  scalenie sieroty #7187 "Fengyun T10" (marka chery-fengyun) -> #6598 pod chery-fulwin:');
$n = przeniesOferty(7187, 6598, $apply);
krok("  przeniesionych: $n");

krok('  duplikat X3L — #6849 "Fengyun X3L" -> #6234 "X3L":');
$n = przeniesOferty(6849, 6234, $apply);
krok("  przeniesionych: $n");

krok('  rename serii do konwencji (T8/T9/T11/A9L — bez prefiksu "Fengyun"):');
foreach ([6598 => ['T10', 'Chery Fulwin T10'], 6234 => ['X3L', 'Chery Fulwin X3L']] as $tid => $v) {
    $t = get_term($tid, 'serie');
    krok(sprintf('    #%d nazwa: "%s" -> "%s"  (slug %s bez zmian)', $tid, $t->name, $v[0], $t->slug));
    if ($apply) wp_update_term($tid, 'serie', ['name' => $v[0]]);
    ustawFullTitle($tid, $v[1], $apply);
}

/* ─────────────── 3. KASACJA PUSTYCH SIEROT ─────────────── */
krok('');
krok('── kasacja termow-sierot (dopiero gdy count=0; 301 juz istnieja w class-asiaauto-redirects.php)');
foreach ([
    [7217, 'serie', 'Voyah Zhuiguang L'],
    [7187, 'serie', 'Fengyun T10 (dupl.)'],
    [6849, 'serie', 'Fengyun X3L (dupl.)'],
    [5191, 'serie', 'Fengyun X3 (pusty)'],
    [7186, 'make',  'Chery Fengyun'],
] as [$tid, $tax, $label]) {
    $t = get_term($tid, $tax);
    if (!$t || is_wp_error($t)) { krok("    #$tid $label — juz nie istnieje"); continue; }
    // po przeniesieniu count bywa nieodswiezony — licz realnie
    $ile = count(get_posts(['post_type' => 'listings', 'numberposts' => -1, 'fields' => 'ids',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'tax_query' => [['taxonomy' => $tax, 'field' => 'term_id', 'terms' => $tid]]]));
    if ($ile > 0) { krok("    #$tid $label — WCIAZ MA $ile ofert, NIE kasuje"); continue; }
    krok("    #$tid $label — kasuje (pusty)");
    if ($apply) wp_delete_term($tid, $tax);
}

/* ─────────────── 4. HIGIENA PO ZMIANIE TAKSONOMII ─────────────── */
krok('');
krok('── _asiaauto_primary_make_slug (ma priorytet nad parent w ensureBrandPrefix)');
foreach ([5081 => 'voyah', 5078 => 'voyah', 6598 => 'chery-fulwin', 6234 => 'chery-fulwin'] as $tid => $slug) {
    $t = get_term($tid, 'serie');
    if (!$t || is_wp_error($t)) continue;
    $stare = get_term_meta($tid, '_asiaauto_primary_make_slug', true);
    if ($stare === $slug) { krok("    #$tid juz '$slug'"); continue; }
    krok("    #$tid '$stare' -> '$slug'");
    if ($apply) update_term_meta($tid, '_asiaauto_primary_make_slug', $slug);
}

if ($apply) {
    foreach (['serie' => [5081, 5078, 6598, 6234], 'make' => [5073, 6523]] as $tax => $ids) {
        wp_update_term_count_now($ids, $tax);
    }
    clean_term_cache([5081, 5078, 6598, 6234], 'serie');
    AsiaAuto_Logger::info('naprawa-fulwin-passion: scalone sieroty Passion L + Fulwin T10, uporzadkowane nazwy serii');
    krok("\n=== ZAPISANO ===");
} else {
    krok("\n=== DRY-RUN — nic nie zapisano ===");
}
