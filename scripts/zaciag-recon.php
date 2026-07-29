<?php
/**
 * zaciag-recon.php — READ-ONLY rozpoznanie nowego zaciągu ofert.
 *
 * To NIE jest audytor podejmujący decyzje. Świadomie: decyzje (tożsamość modelu, nazwa
 * eksportowa, routing sub-marki) wymagają danych z zewnątrz i osądu — patrz
 * docs/procedury/audyt-nowego-zaciagu.md. Skrypt robi wyłącznie POMIARY i wskazuje,
 * gdzie patrzeć. Niczego nie zapisuje.
 *
 * Użycie: wp eval-file scripts/zaciag-recon.php [YYYY-MM-DD]
 *         (domyślnie: ostatnie 2 doby)
 */

$od = $args[0] ?? gmdate('Y-m-d', strtotime('-2 days'));
echo "═══ ROZPOZNANIE ZACIĄGU od $od ═══\n\n";

/* ─────────────────────────────────────────────── 1. Co weszło */
$q = new WP_Query([
    'post_type' => 'listings', 'posts_per_page' => -1,
    'post_status' => ['publish', 'draft', 'pending'],
    'date_query' => [['after' => $od . ' 00:00:00']],
    'orderby' => 'date', 'order' => 'ASC', 'fields' => 'ids',
]);
$reczne = $syncowe = [];
foreach ($q->posts as $pid) {
    if (get_post_meta($pid, '_asiaauto_manual_import', true)) { $reczne[] = $pid; } else { $syncowe[] = $pid; }
}
printf("Weszło: %d (ręcznych: %d, z synca: %d)\n", count($q->posts), count($reczne), count($syncowe));
echo "Ręczny import NIE MA guarda mapowania — problemy siedzą prawie zawsze tam.\n\n";

if ($reczne) {
    echo "── OFERTY RĘCZNE ──\n";
    printf("%-8s %-8s %-40s %-24s %-7s %-9s %s\n", 'ID', 'status', 'tytuł', 'make/serie', 'ep', 'spec_id', 'flagi');
    foreach ($reczne as $pid) {
        $p = get_post($pid);
        $ep = json_decode((string) get_post_meta($pid, '_asiaauto_extra_prep', true), true);
        $cnt = is_array($ep) ? count($ep) : 0;
        $mk = wp_get_post_terms($pid, 'make'); $se = wp_get_post_terms($pid, 'serie');
        $spec = (string) get_post_meta($pid, '_asiaauto_spec_id', true);

        $flagi = [];
        if (!is_array($ep)) { $flagi[] = 'EP-ROZWALONY'; }
        elseif ($cnt < 40) { $flagi[] = 'EP-CHUDY'; }
        if ($spec === '') { $flagi[] = 'BRAK-SPECID'; }
        elseif (strlen($spec) >= 7) { $flagi[] = 'UŻYTKOWY'; }
        if (!get_post_meta($pid, '_asiaauto_complectation', true)) { $flagi[] = 'BRAK-WERSJI'; }
        if (mb_strlen(strip_tags($p->post_content)) < 50) { $flagi[] = 'BRAK-OPISU'; }
        if (!get_post_thumbnail_id($pid)) { $flagi[] = 'BRAK-ZDJĘCIA'; }
        if (isset($mk[0], $se[0]) && (int) $se[0]->parent !== (int) $mk[0]->term_id) { $flagi[] = 'ROZJAZD-PARENT'; }
        if (isset($se[0]) && isset($mk[0]) && stripos($se[0]->name, $mk[0]->name) === 0) { $flagi[] = 'PREFIKS-MARKI'; }
        if (isset($se[0]) && preg_match('/[\x{4e00}-\x{9fff}]/u', $se[0]->name)) { $flagi[] = 'CJK-W-NAZWIE'; }

        printf("%-8d %-8s %-40s %-24s %-7s %-9s %s\n", $pid, $p->post_status,
            mb_substr($p->post_title, 0, 40),
            mb_substr(($mk[0]->slug ?? '?') . '/' . ($se[0]->slug ?? '?'), 0, 24),
            $cnt, ($spec ?: '—'), implode(' ', $flagi));
    }
    echo "\n";
}

/* ─────────────────────────── 2. Niezmienniki taksonomii (cała baza) */
echo "── NIEZMIENNIKI TAKSONOMII (cała baza) ──\n";
global $wpdb;
$rows = $wpdb->get_results("SELECT t.term_id, t.name, t.slug, tt.parent, tt.count
    FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id
    WHERE tt.taxonomy='serie' AND tt.count>0");
$mis = [];
foreach ($rows as $r) {
    $posts = get_objects_in_term([$r->term_id], 'serie');
    if (!$posts) { continue; }
    $mk = wp_get_post_terms($posts[0], 'make');
    if (!isset($mk[0])) { continue; }
    if ((int) $r->parent !== (int) $mk[0]->term_id) {
        $pt = $r->parent ? get_term($r->parent) : null;
        $mis[] = "#{$r->term_id} „{$r->name}\" count={$r->count} | parent=" . ($pt ? "{$pt->name} #{$pt->term_id}" : '0')
               . " ≠ make={$mk[0]->name} #{$mk[0]->term_id}";
    }
}
printf("rozjazdy serie.parent vs make: %d / %d  %s\n", count($mis), count($rows), $mis ? '← DO NAPRAWY' : '(zdrowo)');
foreach ($mis as $m) { echo "   $m\n"; }

$bad = [];
foreach (get_terms(['taxonomy' => 'make', 'hide_empty' => false]) as $m) {
    if (preg_match('/%[0-9a-f]{2}/i', $m->slug) || preg_match('/[\x{4e00}-\x{9fff}]/u', $m->name)) {
        $bad[] = "#{$m->term_id} „{$m->name}\" [{$m->slug}] count={$m->count}";
    }
}
printf("\nmarki ze slugiem URL-encoded / nazwą CJK: %d\n", count($bad));
foreach ($bad as $b) { echo "   $b\n"; }
echo "   (count=0 są nieszkodliwe, dopóki nie są rodzicem serii z autami)\n";

/* ─────────────────────────────────── 3. Mapowania dla nowych marek */
echo "\n── MAPOWANIA ──\n";
$bm = require ASIAAUTO_PLUGIN_DIR . 'data/brand-mapping-v6.1.php';
$cm = require ASIAAUTO_PLUGIN_DIR . 'data/che168-model-map.php';
printf("brand-mapping: %d wpisów | che168-model-map: %d wpisów\n", count($bm), count($cm));
$marki = [];
foreach ($reczne as $pid) {
    $mk = wp_get_post_terms($pid, 'make');
    if (isset($mk[0])) { $marki[$mk[0]->name] = true; }
}
foreach (array_keys($marki) as $m) {
    $hb = 0;
    foreach ($bm as $k => $v) { if (($v['mark_eu'] ?? '') === $m) { $hb++; } }
    printf("   %-18s wpisów w brand-mappingu: %-3d %s\n", $m, $hb, $hb ? '' : '← marka NIEZNANA, sync jej nie wpuści');
}

echo "\n═══ NASTĘPNY KROK ═══\n";
echo "Procedura: docs/procedury/audyt-nowego-zaciagu.md\n";
echo "Kolejność: taksonomia/tożsamość → mapowania → wyposażenie → treść hubów → Indexing NA KOŃCU.\n";
echo "Tożsamość każdej flagowanej oferty sprawdź w katalogu: car.autohome.com.cn/config/spec/{spec_id}.html\n";
echo "Mapowanie testuj WYŁĄCZNIE: getOffer() → Che168_Adapter::normalize() → getEuForCn().\n";
