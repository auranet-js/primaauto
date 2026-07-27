<?php
/**
 * Scalenie extra_prep z bliźniaka dongchedi na oferty importowane z che168.
 * che168 oddaje 7 grup technicznych i ZERO grup wyposażenia (blokada API,
 * potwierdzona na 97 ofertach 2026-07-25) — dongchedi dla tej samej WERSJI ma pełny set.
 *
 * Zasady: dokładamy TYLKO brakujące klucze, nigdy nie nadpisujemy naszych;
 * raportujemy konflikty na kluczach wspólnych (kontrola, czy to ta sama wersja).
 * Uruchomienie: DRY=1 wp eval-file <plik>  |  wp eval-file <plik>
 */

$dry = (bool) getenv('DRY');
$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);

// [post_id, inner_id dongchedi, oczekiwana sygnatura wersji (fragment param_93/title)]
$pairs = [
    [390560, '24221140', '尊享6座标准续航版'],
    [390186, '23589815', '阿尔法36周年纪念版'],
];

foreach ($pairs as [$pid, $dcd, $sig]) {
    $our = json_decode((string) get_post_meta($pid, '_asiaauto_extra_prep', true), true) ?: [];
    $r   = $api->getOffer('dongchedi', $dcd);
    $o   = $r['result'] ?? $r; if (isset($o[0])) { $o = $o[0]; }
    $twin = is_array($o['extra_prep'] ?? null) ? $o['extra_prep'] : [];

    if (!$twin) { printf("!! #%d: bliźniak %s bez extra_prep — pomijam\n", $pid, $dcd); continue; }

    // GATE 1: ta sama wersja (sygnatura w tytule dawcy)
    $title = (string) ($o['title'] ?? '');
    if ($sig !== '' && mb_strpos($title, $sig) === false) {
        printf("!! #%d: dawca %s ma tytuł [%s] bez sygnatury [%s] — STOP\n", $pid, $dcd, $title, $sig);
        continue;
    }

    // GATE 2: konflikty na kluczach wspólnych
    $common = array_intersect_key($our, $twin);
    $conflicts = [];
    foreach ($common as $k => $v) {
        if ((string) (is_array($v) ? json_encode($v) : $v) !== (string) (is_array($twin[$k]) ? json_encode($twin[$k]) : $twin[$k])) {
            $conflicts[$k] = [(string) $v, (string) $twin[$k]];
        }
    }
    $missing = array_diff_key($twin, $our);

    printf("\n=== #%d (%s) dawca dongchedi %s\n", $pid, get_the_title($pid), $dcd);
    printf("    tytuł dawcy: %s\n", $title);
    printf("    nasze=%d | dawca=%d | wspólnych=%d | konfliktów=%d | do dolania=%d\n",
        count($our), count($twin), count($common), count($conflicts), count($missing));
    foreach (array_slice($conflicts, 0, 12, true) as $k => $c) {
        printf("      ~ %-28s nasze=[%s] dawca=[%s]\n", $k, mb_substr($c[0],0,24), mb_substr($c[1],0,24));
    }

    if ($dry) { printf("    DRY — bez zapisu\n"); continue; }

    $merged = $our + $missing;   // nasze mają pierwszeństwo
    ksort($merged);
    update_post_meta($pid, '_asiaauto_extra_prep', wp_json_encode($merged, JSON_UNESCAPED_UNICODE));
    update_post_meta($pid, '_asiaauto_spec_merged_from', 'dongchedi:' . $dcd);
    update_post_meta($pid, '_asiaauto_spec_merged_at', gmdate('c'));

    $after = json_decode((string) get_post_meta($pid, '_asiaauto_extra_prep', true), true) ?: [];
    printf("    ZAPISANE: %d -> %d kluczy\n", count($our), count($after));
}
