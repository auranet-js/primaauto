<?php
/**
 * Naprawa 4 hubów wykrytych w rechecku SEO 2026-09-04.
 *
 * Wzorzec problemu: import utworzył duplikat serii z sufiksem (-2) albo term bez
 * make_slug, bo docelowy slug był już zajęty przez pusty term z brand-mappingu.
 * Skutkiem są URL-e typu /samochody//king-kong-cannon/ i rozbita siła hubów.
 *
 * Uruchomienie (WP-CLI odrzuca własne --flagi przy eval-file, stąd argument pozycyjny):
 *   wp eval-file scripts/fix-huby-zepsute-2026-09-04.php          # symulacja
 *   wp eval-file scripts/fix-huby-zepsute-2026-09-04.php apply    # zapis
 */

$apply = in_array('apply', $args ?? [], true);
echo $apply ? "=== TRYB ZAPISU ===\n\n" : "=== SYMULACJA (dopisz 'apply' aby zapisać) ===\n\n";

/**
 * Przepięcie oferty na inny term serii + usunięcie osieroconego termu.
 */
function przepnij(int $post_id, int $z_termu, int $do_termu, bool $apply): bool {
    $z  = get_term($z_termu, 'serie');
    $do = get_term($do_termu, 'serie');
    if (!$z || is_wp_error($z) || !$do || is_wp_error($do)) {
        echo "    ! brak termu ({$z_termu} -> {$do_termu})\n";
        return false;
    }
    printf("    oferta %d: serie '%s' (%d) -> '%s' (%d)\n",
        $post_id, $z->slug, $z_termu, $do->slug, $do_termu);
    if (!$apply) return true;
    $r = wp_set_object_terms($post_id, [$do_termu], 'serie', false);
    if (is_wp_error($r)) { echo "    ! blad: ".$r->get_error_message()."\n"; return false; }
    return true;
}

function skasuj_term(int $term_id, bool $apply): void {
    if ($apply) {
        // wp_set_object_terms nie odswieza od razu licznika w cache — wymus przeliczenie,
        // inaczej term wyglada na wciaz zajety i kasowanie sie nie wykona.
        wp_update_term_count_now([$term_id], 'serie');
        clean_term_cache([$term_id], 'serie');
    }
    $t = get_term($term_id, 'serie');
    if (!$t || is_wp_error($t)) return;
    if ($t->count > 0) {
        printf("    ! term %d (%s) ma nadal %d ofert — NIE kasuje\n", $term_id, $t->slug, $t->count);
        return;
    }
    printf("    kasuje pusty term %d ('%s')\n", $term_id, $t->slug);
    if ($apply) wp_delete_term($term_id, 'serie');
}

// ── 1. BYD Seal 07 EV → Sealion 7 ────────────────────────────────────────────
// 'Seal 07 EV' i 'Sealion 7' to ten sam samochód (海狮07EV): Sealion 7 to nazwa
// europejska. Hub sealion-7 zbiera 7 352 impresji/mies., seal-07-ev-2 zero.
echo "1. BYD Seal 07 EV -> Sealion 7\n";
przepnij(455246, 7236, 3760, $apply);
skasuj_term(7236, $apply);   // 'seal-07-ev-2' — duplikat z sufiksem
skasuj_term(7172, $apply);   // 'seal-07-ev'   — pusty term z mapowania

// ── 2. King Kong Cannon → Cannon King Kong (zgodnie z brand-mappingiem) ──────
// Term 6763 powstał poza mapowaniem: brak _asiaauto_primary_make_slug, przez co
// URL renderuje się jako /samochody//king-kong-cannon/ (pusty segment marki).
// Term 4434 jest zgodny z wpisem 'Great Wall|King Kong Cannon' (mark_eu=GWM).
echo "\n2. King Kong Cannon -> Cannon King Kong\n";
przepnij(447190, 6763, 4434, $apply);
skasuj_term(6763, $apply);

// ── 3. 智界V9 → Luxeed V9 ────────────────────────────────────────────────────
// Oferta weszła 24.08 z nieprzetlumaczona marka CJK (智界 = Luxeed). Mapowania
// 'Maextro|智界V9' i 'Luxeed|V9' istnieja od 18.08 — to pozostalosc sprzed guarda.
echo "\n3. 智界V9 -> Luxeed V9\n";
przepnij(447607, 7240, 7229, $apply);
if ($apply) {
    $r = wp_set_object_terms(447607, ['luxeed'], 'make', false);
    echo is_wp_error($r) ? "    ! blad marki: ".$r->get_error_message()."\n"
                         : "    marka oferty: 智界 -> Luxeed\n";
} else {
    echo "    oferta 447607: make '智界' -> 'luxeed'\n";
}
skasuj_term(7240, $apply);
if ($apply) { wp_update_term_count_now([7239], 'make'); clean_term_cache([7239], 'make'); }
$cjk = get_term(7239, 'make');
if ($cjk && !is_wp_error($cjk)) {
    if ($cjk->count == 0) {
        echo "    kasuje pusta marke 7239 ('智界')\n";
        if ($apply) wp_delete_term(7239, 'make');
    } else {
        printf("    ! marka 7239 ma nadal %d ofert — NIE kasuje\n", $cjk->count);
    }
}

// ── 4. Avatr 07L — uzupelnienie metadanych ───────────────────────────────────
// Jedyny hub z ofertami w calej witrynie bez rank_math_title. Model realny
// (oferta 460987 z 03.09), wiec term zostaje — brakuje mu opisu tozsamosci.
echo "\n4. Avatr 07L — uzupelnienie metadanych\n";
$t = get_term(7242, 'serie');
if ($t && !is_wp_error($t)) {
    $full = get_term_meta(7242, '_serie_full_title', true);
    if (!$full) {
        echo "    _serie_full_title: (puste) -> 'Avatr 07L'\n";
        if ($apply) update_term_meta(7242, '_serie_full_title', 'Avatr 07L');
    }
    // rank_math_title generuje cron asiaauto_regen_hub_titles_daily — nie wpisujemy
    // ceny recznie ([[feedback_nie_naprawiaj_danych_ktore_system_odswieza]]).
    if (class_exists('AsiaAuto_HubTitleGenerator')) {
        echo "    rank_math_title: generuje AsiaAuto_HubTitleGenerator\n";
        if ($apply) {
            $g = new AsiaAuto_HubTitleGenerator();
            foreach (['regenerateTerm','generateForTerm','processTerm'] as $m) {
                if (method_exists($g, $m)) { $g->$m($t); echo "      -> $m()\n"; break; }
            }
        }
    } else {
        echo "    ! AsiaAuto_HubTitleGenerator niedostepny — title zrobi nocny cron\n";
    }
    echo "    wiki_body: BRAK — wymaga pipeline'u n8n (poza tym skryptem)\n";
}

echo "\n=== koniec ===\n";
