<?php
/**
 * merge-spec-from-twin.php — uzupelnia okrojony _asiaauto_extra_prep danymi
 * z blizniaczej oferty (ta sama marka + model + wersja + rocznik).
 *
 * Kontekst: od ~20.07.2026 auto-api zwraca dla nowych ofert dongchedi
 * extra_prep okrojony do ~42 pol zamiast ~350 (regresja po stronie dostawcy,
 * potwierdzona bezposrednim zapytaniem do /offer). Specyfikacja w extra_prep
 * jest cecha WERSJI, nie egzemplarza — nie zawiera vin/mileage/koloru/miasta —
 * wiec da sie ja odtworzyc z innej sztuki tej samej wersji i rocznika.
 *
 * Zasady bezpieczenstwa:
 *  - pomija wpisy reczne (_asiaauto_manual_entry / _asiaauto_manual_import)
 *    oraz wszystko powiazane z zamowieniem (_order_listing_id) — auta w drodze
 *    i na placu opisuje Ruslan z palca, tam nie zgadujemy;
 *  - dopasowanie WYLACZNIE exact: make|serie|complectation|ca-year.
 *    Zmierzone: ta sama wersja -> mediana roznic 0%, inna wersja -> 6,6%
 *    (rozne pojemnosci silnika, masy, predkosci — patrz docs/decyzje);
 *  - dawca musi byc zdrowy (odpada 97 ofert ze zjedzonymi escape'ami \u z bugu v0.25);
 *  - przy >=2 dawcach dolewane sa tylko pola, co do ktorych wszyscy sie zgadzaja;
 *  - NIGDY nie nadpisuje istniejacego klucza — dolewa wylacznie brakujace;
 *  - stempluje _asiaauto_spec_inherited_from / _at / _count (rollback + audyt).
 *
 * Uzycie:
 *   wp eval-file scripts/merge-spec-from-twin.php          # dry-run
 *   wp eval-file scripts/merge-spec-from-twin.php apply    # zapis
 */

$APPLY = in_array('apply', $args ?? [], true);

// pola wahajace sie miedzy egzemplarzami tej samej wersji (wyznaczone empirycznie na 475 grupach)
$VOLATILE = ['steer_wheel_material_6','front_windshield_electric_heating_2',
 'battery_temperature_management_system_cooling','battery_temperature_management_system_heating',
 'rear_suspension_form','front_suspension_form','navigation_assisted_driving_2',
 'voice_wake_up_recognition_2','navigation_assisted_driving_1','Xiaomi HAD_1','Xiaomi HAD_2',
 'light_sensing_canopy_2','skylight_type_4','battery_charge_time','streaming_media_side_mirror',
 'temperature_partition_control_3','rear_makeup_mirror_1','wing_door'];

global $wpdb;

$rows = $wpdb->get_results("SELECT p.ID, p.post_title,
  (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='_asiaauto_extra_prep') ep,
  (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='_asiaauto_source') src,
  (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='_asiaauto_manual_entry') me,
  (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='_asiaauto_manual_import') mi,
  (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='_asiaauto_complectation') comp,
  (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='ca-year') yr,
  (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='make') mk,
  (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='serie') se
 FROM {$wpdb->posts} p WHERE p.post_type='listings' AND p.post_status='publish'");

$zarezerwowane = array_flip(array_map('intval', $wpdb->get_col(
  "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
   JOIN {$wpdb->posts} p ON p.ID=pm.post_id AND p.post_type='asiaauto_order'
   WHERE pm.meta_key='_order_listing_id'")));

$dawcy = []; $cele = [];
foreach ($rows as $r) {
    $j = json_decode((string) $r->ep, true);
    $r->spec = is_array($j) ? $j : [];
    $r->n = count($r->spec);
    $reczny = ($r->me === '1' || $r->mi === '1' || $r->src === '' || $r->src === null);

    if ($r->n >= 200) {
        $uszkodzone = 0;
        foreach ($r->spec as $v) {
            if (is_string($v) && preg_match('/^(u[0-9a-f]{4})+$/i', $v)) $uszkodzone++;
        }
        if ($uszkodzone > 3) continue;                       // dawca z rozbitym escape
        $dawcy[strtolower("$r->mk|$r->se|$r->comp|$r->yr")][] = $r;
    } elseif ($r->n > 0 && $r->n < 100 && !$reczny && !isset($zarezerwowane[$r->ID])) {
        $cele[] = $r;
    }
}

WP_CLI::log($APPLY ? '=== ZAPIS (--apply) ===' : '=== DRY-RUN (bez zapisu) ===');
WP_CLI::log(sprintf("Celow: %d | grup dawcow: %d\n", count($cele), count($dawcy)));

$ruszone = 0; $polaRazem = 0; $pominiete = [];

foreach ($cele as $r) {
    $klucz = strtolower("$r->mk|$r->se|$r->comp|$r->yr");
    if (empty($dawcy[$klucz])) { $pominiete[] = $r; continue; }

    $grupa = $dawcy[$klucz];
    usort($grupa, fn($a, $b) => $b->n <=> $a->n);
    $wzor = $grupa[0];

    $dolane = [];
    foreach ($wzor->spec as $k => $v) {
        if (in_array($k, $VOLATILE, true)) continue;
        if (array_key_exists($k, $r->spec)) continue;        // nigdy nie nadpisujemy

        $zgodni = true;                                       // konsensus wszystkich dawcow
        foreach ($grupa as $d) {
            if (!array_key_exists($k, $d->spec)) { $zgodni = false; break; }
            $a = is_scalar($v) ? (string) $v : json_encode($v);
            $b = is_scalar($d->spec[$k]) ? (string) $d->spec[$k] : json_encode($d->spec[$k]);
            if ($a !== $b) { $zgodni = false; break; }
        }
        if ($zgodni) $dolane[$k] = $v;
    }

    if (!$dolane) { $pominiete[] = $r; continue; }

    $ruszone++; $polaRazem += count($dolane);
    WP_CLI::log(sprintf('#%d  %d -> %d pol  (dawca #%d, w grupie %d)  %s',
        $r->ID, $r->n, $r->n + count($dolane), $wzor->ID, count($grupa), mb_substr($r->post_title, 0, 46)));

    if ($APPLY) {
        $scalone = $r->spec + $dolane;                        // + zachowuje lewa strone
        $json = wp_json_encode($scalone);
        if (!is_string($json) || json_decode($json, true) === null) {
            WP_CLI::warning("#{$r->ID}: blad kodowania JSON — pomijam");
            continue;
        }
        update_post_meta($r->ID, '_asiaauto_extra_prep', wp_slash($json));

        $kontrola = json_decode(get_post_meta($r->ID, '_asiaauto_extra_prep', true), true);
        if (!is_array($kontrola) || count($kontrola) !== count($scalone)) {
            WP_CLI::warning("#{$r->ID}: weryfikacja po zapisie nie zgadza sie — sprawdz recznie");
            continue;
        }
        update_post_meta($r->ID, '_asiaauto_spec_inherited_from', $wzor->ID);
        update_post_meta($r->ID, '_asiaauto_spec_inherited_at', gmdate('c'));
        update_post_meta($r->ID, '_asiaauto_spec_inherited_count', count($dolane));
    }
}

WP_CLI::log(str_repeat('-', 78));
WP_CLI::log(sprintf('Ofert %s: %d | dolanych pol: %d (srednio %.0f)',
    $APPLY ? 'zapisanych' : 'do zapisu', $ruszone, $polaRazem, $ruszone ? $polaRazem / $ruszone : 0));
WP_CLI::log(sprintf('Bez blizniaka (zostaja chude): %d', count($pominiete)));
foreach ($pominiete as $r) WP_CLI::log(sprintf('   #%d %s', $r->ID, mb_substr($r->post_title, 0, 54)));
if ($APPLY) WP_CLI::success('Zrobione. Rollback: ~/backups/primaauto/2026-07-22/wp7j_postmeta-przed-merge-spec.sql.gz');
