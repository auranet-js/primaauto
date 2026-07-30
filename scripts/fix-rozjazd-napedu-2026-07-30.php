<?php
/**
 * fix-rozjazd-napedu-2026-07-30.php — przepina oferty, które trafiły do huba o złym napędzie.
 *
 * Powód: override'y w `che168-model-map.php` były ślepe na napęd, a che168 trzyma warianty EV
 * i DM-i pod jedną nazwą modelu (`Han`, `海豹06`, `PLUS New Energy`). Nasze huby są rozbite po
 * napędzie, więc wariant mniejszościowy zawsze lądował w cudzym hubie. Przyczyna naprawiona
 * w v0.34.13 (`by_engine` w kroku 0 `resolveChe168()`); ten skrypt sprząta 6 ofert sprzed poprawki.
 *
 * Po przepięciu URUCHOM: wp eval-file scripts/odswiez-tytuly-ofert.php "<id,...>" apply
 * — tytuł zawiera nazwę serii i powstaje tylko przy imporcie, więc sam się nie przeliczy.
 *
 * Użycie: php fix-rozjazd-napedu-2026-07-30.php [apply]
 *
 * @since 2026-07-30 (T-222)
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

$apply = in_array('apply', $argv, true);

/** post_id => [oczekiwany_fuel, docelowy_slug_serii] */
$PRZEPIECIA = [
    392678 => ['electric',       'han-ev'],
    392561 => ['electric',       'seal-6-ev'],
    396700 => ['electric',       'seal-6-ev'],
    392588 => ['phev',           'seal-u-dm-i'],
    395825 => ['phev',           'seal-u-dm-i'],
    396835 => ['phev',           'seal-u-dm-i'],
];

$ok = 0; $pominiete = 0; $zrobione = [];

foreach ($PRZEPIECIA as $pid => [$oczFuel, $celSlug]) {
    $p = get_post($pid);
    if (!$p || $p->post_type !== 'listings') {
        printf("  #%-7d POMINIETE — nie ma takiej oferty\n", $pid);
        $pominiete++;
        continue;
    }

    // Guard: nie przepinaj, jeśli napęd nie zgadza się z założeniem (dane mogły się zmienić).
    $fuel = (string) get_post_meta($pid, 'fuel', true);
    if ($fuel !== $oczFuel) {
        printf("  #%-7d POMINIETE — fuel=%s, oczekiwano %s\n", $pid, $fuel ?: '(brak)', $oczFuel);
        $pominiete++;
        continue;
    }

    $cel = get_term_by('slug', $celSlug, 'serie');
    if (!$cel) {
        printf("  #%-7d POMINIETE — brak termu docelowego '%s'\n", $pid, $celSlug);
        $pominiete++;
        continue;
    }

    $obecne = wp_get_post_terms($pid, 'serie', ['fields' => 'all']);
    $zSlug  = $obecne ? $obecne[0]->slug : '(brak)';
    $zNazwa = $obecne ? $obecne[0]->name : '(brak)';

    if ($zSlug === $celSlug) {
        printf("  #%-7d POMINIETE — juz w '%s'\n", $pid, $celSlug);
        $pominiete++;
        continue;
    }

    printf("  #%-7d fuel=%-9s %-24s -> %-24s  %s\n",
        $pid, $fuel, $zNazwa, $cel->name, mb_substr(html_entity_decode($p->post_title), 0, 44));

    if ($apply) {
        $r = wp_set_object_terms($pid, [(int) $cel->term_id], 'serie', false);
        if (is_wp_error($r)) {
            printf("      BLAD: %s\n", $r->get_error_message());
            $pominiete++;
            continue;
        }
        update_post_meta($pid, '_asiaauto_serie_fix', 'by_engine-2026-07-30');
        update_post_meta($pid, '_asiaauto_serie_fix_from', $zSlug);
        $zrobione[] = $pid;
    }
    $ok++;
}

printf("\n=== %s ===\n", $apply ? 'PRZEPIETO' : 'DRY-RUN (dodaj: apply)');
printf("do przepiecia: %d | pominiete: %d\n", $ok, $pominiete);

if ($apply && $zrobione) {
    // Przelicz liczniki termów — inaczej huby pokazują stary count.
    wp_cache_flush();
    echo "\nTERAZ URUCHOM (tytuly i slugi nie przelicza sie same):\n";
    echo "  cd /home/host476470/domains/primaauto.com.pl/public_html && \\\n";
    printf("  wp eval-file /home/host476470/projekty/primaauto/scripts/odswiez-tytuly-ofert.php \"%s\" apply\n",
        implode(',', $zrobione));
    AsiaAuto_Logger::info('fix-rozjazd-napedu: przepieto ' . count($zrobione) . ' ofert');
}
