<?php
/**
 * Naprawa 14 ofert z NIEPARSOWALNYM _asiaauto_extra_prep (json_decode → NULL).
 *
 * Korzeń: `update_post_meta()` przepuszcza wartość przez `wp_unslash()`, więc zapis
 * `wp_json_encode()` BEZ `wp_slash()` zjada backslashe — w tym te przed `\"` w wartości,
 * co rozwala strukturę JSON (nie tylko escape'y unicode). Objaw: parametry nie renderują
 * się na hubach (spec robi json_decode wprost); single działa, bo ma własny parser
 * ratunkowy `fixBrokenFlatJson()`.
 *
 * Metoda: odzyskujemy pary klucz→wartość TYM SAMYM parserem co render single
 * (AsiaAuto_Single::fixBrokenFlatJson, przez Reflection — zero duplikacji logiki),
 * odwracamy zjedzone escape'y unicode (uXXXX → znak, tylko codepointy CJK),
 * zapisujemy poprawnie: wp_slash(wp_json_encode(..., JSON_UNESCAPED_UNICODE)).
 *
 * Użycie (WP-CLI przechwytuje --flagi, więc BEZ dashy):
 *   wp eval-file fix-broken-json.php            # dry-run
 *   wp eval-file fix-broken-json.php apply      # zapis
 */

$APPLY = in_array('apply', (array) ($args ?? []), true);

$IDS = [270999, 317106, 330122, 342007, 350394, 351079, 360270, 372067,
        387824, 389961, 389970, 389990, 387826, 387470];

/** Czy codepoint należy do zakresów CJK/fullwidth (jak w fix-escape-unicode-extra-prep.php). */
$is_cjk = static function (int $cp): bool {
    return ($cp >= 0x3000 && $cp <= 0x303F)
        || ($cp >= 0x3400 && $cp <= 0x4DBF)
        || ($cp >= 0x4E00 && $cp <= 0x9FFF)
        || ($cp >= 0xF900 && $cp <= 0xFAFF)
        || ($cp >= 0xFF00 && $cp <= 0xFFEF);
};

$fix_escape = static function (string $s) use ($is_cjk): array {
    $cnt = 0;
    $out = preg_replace_callback('/u([0-9a-fA-F]{4})/', static function ($m) use (&$cnt, $is_cjk) {
        $cp = (int) hexdec($m[1]);
        if (!$is_cjk($cp)) return $m[0];
        $cnt++;
        return mb_chr($cp, 'UTF-8');
    }, $s);
    return [$out === null ? $s : $out, $cnt];
};

// parser ratunkowy z renderu single — ta sama logika, którą widzi użytkownik na ofercie
$ref = new ReflectionMethod('AsiaAuto_Single', 'fixBrokenFlatJson');
$ref->setAccessible(true);
$recover = static fn(string $raw): array => $ref->invoke(null, $raw);

printf("=== NAPRAWA ROZWALONEGO extra_prep — %s ===\n\n", $APPLY ? 'APPLY' : 'DRY-RUN');

$backup = [];
$stat = ['ok' => 0, 'nadal_zle' => 0, 'pominiete' => 0, 'kluczy' => 0, 'escape' => 0];

foreach ($IDS as $id) {
    $post = get_post($id);
    if (!$post) { printf("  #%-7d BRAK POSTA\n", $id); $stat['pominiete']++; continue; }

    $raw = get_post_meta($id, '_asiaauto_extra_prep', true);
    $raw = is_string($raw) ? $raw : (string) json_encode($raw);

    if (is_array(json_decode($raw, true))) {
        printf("  #%-7d JUŻ OK — pomijam\n", $id);
        $stat['pominiete']++;
        continue;
    }

    $backup[$id] = $raw;

    $pary = $recover($raw);
    if (!$pary) { printf("  #%-7d parser nic nie odzyskał\n", $id); $stat['nadal_zle']++; continue; }

    // odwróć zjedzone escape'y w kluczach i wartościach
    $czyste = [];
    $esc = 0;
    foreach ($pary as $k => $v) {
        [$k2, $c1] = $fix_escape((string) $k);
        [$v2, $c2] = is_string($v) ? $fix_escape($v) : [$v, 0];
        $esc += $c1 + $c2;
        $czyste[$k2] = $v2;
    }

    $json = wp_json_encode($czyste, JSON_UNESCAPED_UNICODE);
    if ($json === false || !is_array(json_decode($json, true))) {
        printf("  #%-7d BŁĄD kodowania — pomijam\n", $id);
        $stat['nadal_zle']++;
        continue;
    }

    printf("  #%-7d %-9s %5d B → %5d B | kluczy: %3d | escape: %3d | %s\n",
        $id, $post->post_status, strlen($raw), strlen($json), count($czyste), $esc,
        mb_substr($post->post_title, 0, 34));

    $stat['kluczy'] += count($czyste);
    $stat['escape'] += $esc;

    if ($APPLY) {
        update_post_meta($id, '_asiaauto_extra_prep', wp_slash($json));
        $po = get_post_meta($id, '_asiaauto_extra_prep', true);
        if (is_array(json_decode(is_string($po) ? $po : '', true))) {
            $stat['ok']++;
        } else {
            printf("       ⚠ PO ZAPISIE NADAL NULL\n");
            $stat['nadal_zle']++;
        }
    } else {
        $stat['ok']++;
    }
}

if ($APPLY && $backup) {
    $plik = '/home/host476470/backups/primaauto/2026-07-28/extra-prep-broken-json-przed.json';
    file_put_contents($plik, json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    printf("\nbackup surowych wartości: %s (%d ofert)\n", $plik, count($backup));
}

printf("\n=== PODSUMOWANIE ===\n  naprawione: %d | nadal złe: %d | pominięte: %d\n  odzyskanych kluczy: %d | odwróconych escape'ów: %d\n",
    $stat['ok'], $stat['nadal_zle'], $stat['pominiete'], $stat['kluczy'], $stat['escape']);
if (!$APPLY) echo "\n  DRY-RUN — nic nie zapisane. Zapis: dopisz argument 'apply'.\n";
