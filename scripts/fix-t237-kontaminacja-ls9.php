<?php
/**
 * T-237 — naprawa kontaminacji hubów i scalenie duplikatu LS9 (2026-08-04).
 *
 * 1. KONTAMINACJA: dwa auta Geely siedzą na seriach należących do innych marek —
 *    „Galaxy M9" na termie `M9` (AITO), „Geely Galaxy L7" na termie `L7` (Li Auto).
 *    Marka jest poprawna, zła jest seria, więc auta Geely wyświetlają się na cudzych hubach.
 *
 * 2. DUPLIKAT LS9: ten sam model rozbity na `LS9` (6507) i `IM LS9` (6239) — obie strony
 *    indeksowalne, obie z własnym „od X PLN", razem 3 egzemplarze pokazywane jako 2 i 1.
 *    Scalamy na `LS9` (nazwa bez powtórzonej marki, zgodna z frazą „im ls9” 90/mc).
 *
 * Uruchomienie: wp eval-file fix-t237.php        (dry-run)
 *               wp eval-file fix-t237.php apply
 */

$apply = in_array('apply', $args ?? [], true);

/** [post_id, term_zly, term_dobry, etykieta] */
$KONTAMINACJA = [
    [387316, 5304, 6550, 'Galaxy M9 2025 — z termu M9 (AITO) na Galaxy M9 (Geely)'],
    [390248, 5739, 7153, 'Geely Galaxy L7 2024 — z termu L7 (Li Auto) na Galaxy L7 (Geely)'],
];

/** scalenie duplikatu: [term_zrodlowy, term_docelowy] */
$SCALENIE = [6239, 6507];   // IM LS9 → LS9

echo "=== 1. KONTAMINACJA ===\n";
foreach ($KONTAMINACJA as [$post_id, $zly, $dobry, $opis]) {
    $obecne = wp_get_post_terms($post_id, 'serie', ['fields' => 'ids']);
    if (is_wp_error($obecne)) { echo "  BŁĄD odczytu $post_id\n"; continue; }
    printf("  %s\n    teraz: [%s]  →  docelowo: [%d]\n", $opis, implode(',', $obecne), $dobry);
    if (!in_array($zly, $obecne, true)) { echo "    (już naprawione — pomijam)\n"; continue; }
    if ($apply) {
        wp_set_post_terms($post_id, [$dobry], 'serie', false);   // false = zastąp, nie dopisuj
        printf("    ZASTOSOWANO → %s\n", implode(',', wp_get_post_terms($post_id, 'serie', ['fields' => 'ids'])));
    }
}

echo "\n=== 2. SCALENIE LS9 ===\n";
[$src, $dst] = $SCALENIE;
$src_term = get_term($src, 'serie');
$dst_term = get_term($dst, 'serie');
if (!$src_term || is_wp_error($src_term)) {
    echo "  term źródłowy nie istnieje — już scalone\n";
} else {
    $posty = get_posts([
        'post_type' => 'listings', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids',
        'tax_query' => [['taxonomy' => 'serie', 'field' => 'term_id', 'terms' => $src]],
    ]);
    printf("  „%s” (%d) → „%s” (%d): %d postów\n", $src_term->name, $src, $dst_term->name, $dst, count($posty));
    foreach ($posty as $pid) {
        $p = get_post($pid);
        printf("    %-8d %-8s %s\n", $pid, $p->post_status, mb_substr($p->post_title, 0, 46));
        if ($apply) { wp_set_post_terms($pid, [$dst], 'serie', false); }
    }
    if ($apply) {
        // 301 ze starego adresu — hub był indeksowalny, nie zostawiamy 404
        // Mechanizm redirectów pluginu (AsiaAuto_Redirects): opcja `_asiaauto_redirects`,
        // format „/stara/sciezka/" => „/nowa/sciezka/" — ścieżki, nie pełne URL-e.
        $old_url = '/samochody/im-motors/im-ls9/';
        $new_url = '/samochody/im-motors/ls9/';
        $red = (array) get_option('_asiaauto_redirects', []);
        $red[$old_url] = $new_url;
        update_option('_asiaauto_redirects', $red);
        printf("    301: %s → %s  (razem regul: %d)\n", $old_url, $new_url, count($red));

        wp_delete_term($src, 'serie');
        echo "    term $src usunięty\n";
    }
}

if ($apply) {
    foreach ([5304, 5739, 6550, 7153, 6507] as $tid) {
        $t = get_term($tid, 'serie');
        if ($t && !is_wp_error($t)) {
            wp_update_term_count_now([$t->term_taxonomy_id], 'serie');
            printf("  recount %-14s → %d\n", $t->name, get_term($tid, 'serie')->count);
        }
    }
}

printf("\n%s\n", $apply ? 'ZASTOSOWANO' : 'DRY-RUN — uruchom z „apply”');
