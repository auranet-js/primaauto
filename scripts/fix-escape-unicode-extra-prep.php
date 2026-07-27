<?php
/**
 * fix-escape-unicode-extra-prep.php — naprawa zjedzonych escape'ów unicode w extra_prep.
 *
 * PROBLEM (diagnoza 2026-07-27): `wp_json_encode($ep)` bez JSON_UNESCAPED_UNICODE koduje CJK
 * jako "马力", a update_post_meta() przepuszcza wartość przez wp_unslash(), który
 * zjada backslashe → w bazie ląduje "u9a6cu529b". Źródło: diag/backfill-extra-prep.php:73
 * (poprawione 2026-07-27). Importer był czysty (wp_slash), sync che168 też.
 *
 * SKUTEK NA STRONIE: render tabeli wersji na hubie serii tłumaczy 马力→KM w locie
 * (AsiaAuto_Spec::tr_engine). Zepsuty string nie jest rozpoznawany, więc ta sama wersja auta
 * pojawia się w tabeli DWA RAZY — raz jako „2.0T 207 KM L4 PHEV", raz jako
 * „2.0T 207u9a6cu529b L4 u63d2u7535u5f0fu6df7u52a8". 31 z 44 hubów, 114 ofert publish.
 *
 * NAPRAWA: odwracamy uszkodzenie do surowego CJK (tak wygląda zdrowy rekord — tłumaczenie
 * jest w renderze, nie w bazie). Zamieniamy WYŁĄCZNIE sekwencje uXXXX, których codepoint
 * mieści się w zakresach CJK/fullwidth — żeby nie tknąć legalnego tekstu łacińskiego.
 *
 * Użycie:
 *   wp eval-file scripts/fix-escape-unicode-extra-prep.php              # DRY-RUN
 *   wp eval-file scripts/fix-escape-unicode-extra-prep.php --apply
 *   wp eval-file scripts/fix-escape-unicode-extra-prep.php --limit=3    # próbka
 */

// Bez dashy — WP-CLI przechwytuje "--apply" jako własną flagę i przerywa z błędem.
// Użycie: wp eval-file scripts/fix-escape-unicode-extra-prep.php apply limit=3 only=390652
$opt = ['apply' => false, 'limit' => 0, 'only' => []];
foreach ((array) ($args ?? []) as $a) {
    $a = (string) $a;
    if ($a === 'apply') $opt['apply'] = true;
    elseif (preg_match('/^limit=(\d+)$/', $a, $m)) $opt['limit'] = (int) $m[1];
    elseif (preg_match('/^only=([\d,]+)$/', $a, $m)) $opt['only'] = array_filter(array_map('intval', explode(',', $m[1])));
}

/** Czy codepoint to CJK / fullwidth — tylko takie odwracamy. */
function fix_cp_is_cjk(int $cp): bool {
    return ($cp >= 0x2E80 && $cp <= 0x2EFF)   // CJK Radicals Supplement
        || ($cp >= 0x3000 && $cp <= 0x303F)   // CJK Symbols and Punctuation
        || ($cp >= 0x3400 && $cp <= 0x4DBF)   // CJK Ext A
        || ($cp >= 0x4E00 && $cp <= 0x9FFF)   // CJK Unified Ideographs
        || ($cp >= 0xF900 && $cp <= 0xFAFF)   // CJK Compatibility Ideographs
        || ($cp >= 0xFF00 && $cp <= 0xFFEF);  // Halfwidth and Fullwidth Forms
}

/** Odwraca "u9a6cu529b" → "马力". Zwraca [naprawiony_string, liczba_zamian]. */
function fix_unicode_escape(string $s): array {
    $cnt = 0;
    $out = preg_replace_callback('/u([0-9a-fA-F]{4})/', function ($m) use (&$cnt) {
        $cp = hexdec($m[1]);
        if (!fix_cp_is_cjk((int) $cp)) return $m[0];
        $cnt++;
        return mb_chr((int) $cp, 'UTF-8');
    }, $s);
    return [$out === null ? $s : $out, $cnt];
}

global $wpdb;
printf("=== NAPRAWA ESCAPE'ÓW extra_prep — %s ===\n\n", $opt['apply'] ? 'APPLY' : 'DRY-RUN');

$rows = $wpdb->get_results("SELECT pm.post_id, pm.meta_value, p.post_status, p.post_title
    FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id
    WHERE pm.meta_key = '_asiaauto_extra_prep'
      AND pm.meta_value REGEXP 'u[0-9a-f]{4}'
      AND p.post_type = 'listings'
    ORDER BY p.post_status, pm.post_id DESC");
printf("kandydatów z bazy: %d\n\n", count($rows));

$stat = ['tkniete' => 0, 'pol' => 0, 'zamian' => 0, 'zapisane' => 0, 'bez_zmian' => 0, 'err' => 0];
$pokazane = 0;

foreach ($rows as $r) {
    if ($opt['only'] && !in_array((int) $r->post_id, $opt['only'], true)) continue;
    $arr = json_decode((string) $r->meta_value, true);
    if (!is_array($arr)) { $stat['err']++; continue; }

    $pol = 0; $zam = 0; $probka = [];
    // Klucze bywają zepsute tak samo jak wartości ("map_brand_u9ad8u5fb7" zamiast
    // "map_brand_高德") — przebudowujemy tablicę z zachowaniem kolejności. Przy kolizji
    // (dwa klucze schodzące się po naprawie do jednego) wygrywa pierwszy — nie nadpisujemy.
    $fixed = [];
    foreach ($arr as $k => $v) {
        $nk = (string) $k;
        if (is_string($k) || is_int($k)) {
            [$kk, $kc] = fix_unicode_escape((string) $k);
            if ($kc > 0 && $kk !== (string) $k) {
                if (count($probka) < 3) $probka[] = ['[KLUCZ] ' . $k, (string) $k, $kk];
                $nk = $kk; $pol++; $zam += $kc;
            }
        }
        if (is_string($v)) {
            [$new, $c] = fix_unicode_escape($v);
            if ($c > 0 && $new !== $v) {
                if (count($probka) < 3) $probka[] = [$nk, $v, $new];
                $v = $new; $pol++; $zam += $c;
            }
        }
        if (!array_key_exists($nk, $fixed)) $fixed[$nk] = $v;
    }
    $arr = $fixed;

    if ($pol === 0) { $stat['bez_zmian']++; continue; }
    $stat['tkniete']++; $stat['pol'] += $pol; $stat['zamian'] += $zam;

    if ($pokazane < 5) {
        printf("#%d [%s] %s\n", $r->post_id, $r->post_status, mb_substr($r->post_title, 0, 48));
        printf("   pól naprawionych: %d (zamian: %d)\n", $pol, $zam);
        foreach ($probka as [$k, $old, $new]) {
            printf("   %-22s PRZED: %s\n", $k, mb_substr($old, 0, 52));
            printf("   %-22s   PO : %s\n", '', mb_substr($new, 0, 52));
        }
        echo "\n";
        $pokazane++;
    }

    if (!$opt['apply']) continue;

    // wp_slash + JSON_UNESCAPED_UNICODE — oba zabezpieczenia, żeby zapis nie odtworzył buga.
    $ok = update_post_meta($r->post_id, '_asiaauto_extra_prep', wp_slash(wp_json_encode($arr, JSON_UNESCAPED_UNICODE)));
    if ($ok !== false) $stat['zapisane']++;

    // Kontrola: odczyt po zapisie musi być wolny od zepsutych sekwencji.
    $back = (string) get_post_meta($r->post_id, '_asiaauto_extra_prep', true);
    if (preg_match('/u[0-9a-f]{4}u[0-9a-f]{4}/', $back)) {
        printf("  !! #%d po zapisie NADAL zepsute — przerywam\n", $r->post_id);
        break;
    }

    if ($opt['limit'] && $stat['zapisane'] >= $opt['limit']) {
        printf("  limit %d osiągnięty — stop\n", $opt['limit']);
        break;
    }
}

echo "=== PODSUMOWANIE ===\n";
printf("  ofert do naprawy:      %d\n", $stat['tkniete']);
printf("  pól łącznie:           %d\n", $stat['pol']);
printf("  zamian znaków:         %d\n", $stat['zamian']);
printf("  kandydatów bez zmian:  %d (uXXXX poza zakresem CJK — nietknięte)\n", $stat['bez_zmian']);
printf("  niedekodowalny JSON:   %d\n", $stat['err']);
if ($opt['apply']) {
    printf("  ZAPISANE:              %d\n", $stat['zapisane']);
} else {
    echo "\n  DRY-RUN — baza nietknięta. Naprawa: dopisz --apply\n";
}
