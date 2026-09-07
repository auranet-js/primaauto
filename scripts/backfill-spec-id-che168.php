<?php
/**
 * backfill-spec-id-che168.php — dopisuje brakujący `_asiaauto_spec_id` ofertom che168.
 *
 * Adapter do 07.09.2026 czytał `specid` wyłącznie z `extra.configuration.specid`. Gdy źródło
 * nie oddało specyfikacji dla oferty, to pole przychodziło puste, choć `specid` bywał wypełniony
 * na głównym poziomie odpowiedzi. Efekt: oferta bez klucza do katalogu Autohome, czyli bez
 * szansy na wyposażenie (`backfill-spec-autohome.php` kluczuje właśnie po `_asiaauto_spec_id`).
 *
 * Adapter jest już poprawiony — ten skrypt naprawia egzemplarze zaimportowane wcześniej.
 * Jedno wywołanie `getOffer()` na ofertę, więc świadomie bez limitu: dotyczy 27 sztuk.
 *
 * Użycie:
 *   wp eval-file scripts/backfill-spec-id-che168.php          # DRY-RUN
 *   wp eval-file scripts/backfill-spec-id-che168.php apply
 */

$apply = (($args[0] ?? '') === 'apply');

global $wpdb;
$ids = $wpdb->get_col("
    SELECT p.ID FROM {$wpdb->posts} p
    JOIN {$wpdb->postmeta} s  ON s.post_id = p.ID AND s.meta_key = '_asiaauto_source' AND s.meta_value = 'che168'
    LEFT JOIN {$wpdb->postmeta} sp ON sp.post_id = p.ID AND sp.meta_key = '_asiaauto_spec_id'
    WHERE p.post_type = 'listings' AND p.post_status IN ('publish','draft')
      AND (sp.meta_id IS NULL OR sp.meta_value = '' OR sp.meta_value = '0')
");
printf("=== BACKFILL spec_id — %s ===\nofert che168 bez klucza: %d\n\n", $apply ? 'APPLY' : 'DRY-RUN', count($ids));

$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$ok = $brak = $err = 0;

foreach ($ids as $pid) {
    $inner = (string) get_post_meta($pid, '_asiaauto_inner_id', true);
    if ($inner === '') { printf("  #%d — brak inner_id, pomijam\n", $pid); $err++; continue; }

    $raw = (array) $api->getOffer('che168', $inner);
    if (!$raw) { printf("  #%d (%s) — API nie odpowiedzialo\n", $pid, $inner); $err++; continue; }

    $specid = $raw['extra']['configuration']['specid'] ?? null;
    if ($specid === null || $specid === '' || (int) $specid <= 0) $specid = $raw['specid'] ?? null;

    if ($specid === null || $specid === '' || (int) $specid <= 0) {
        printf("  #%d (%s) — zrodlo nie ma specid w zadnym miejscu\n", $pid, $inner);
        $brak++;
        continue;
    }
    printf("  #%d (%s) -> specid %d  %s\n", $pid, $inner, (int) $specid, mb_substr(get_the_title($pid), 0, 42));
    if ($apply) {
        update_post_meta($pid, '_asiaauto_spec_id', (int) $specid);
        update_post_meta($pid, '_asiaauto_spec_id_backfilled_at', gmdate('c'));
    }
    $ok++;
}

printf("\n=== PODSUMOWANIE ===\n  z odzyskanym specid: %d | zrodlo nie ma: %d | bledy: %d\n", $ok, $brak, $err);
if ($apply && $ok) echo "\n  Nocny cron 04:55 (backfill-spec-autohome.php) dolezy im wyposazenie.\n";
if (!$apply) echo "\n  DRY-RUN — nic nie zapisane. Zapis: dopisz 'apply'\n";
