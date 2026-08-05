<?php
/**
 * t238-audyt-degradacji.php — dowód, że poprawki T-238 niczego nie zepsuły.
 *
 * Porównuje stan po zmianach z kopiami sprzed zmian (`_t238_backup*`) i sprawdza
 * niezmienniki, które łatwo złamać podmianą w polu HTML:
 *
 *   1. balans tagów <p>, <strong>, <h2>, <h3>, <table> — usuwanie fragmentów
 *      (np. „(mediana ok. 203 000)”) mogło rozerwać znacznik
 *   2. brak zlepków typu „197 000PLN” (wpadka regexowa złapana w symulacji 05.08)
 *   3. długość treści — spadek większy niż oczekiwany sygnalizuje wycięcie za dużo
 *   4. poprawność JSON w `asiaauto_faq_json` (zasila schema FAQPage)
 *   5. zgodność ceny w tytule i opisie z min(price) w bazie
 *
 * Read-only. Nic nie zapisuje.
 *
 * Użycie: wp eval-file scripts/t238-audyt-degradacji.php
 */

if (!defined('ABSPATH')) { exit; }

/** Liczba otwarć minus liczba zamknięć danego znacznika. */
function t238_balans(string $html, string $tag): int {
    $otw = preg_match_all('/<' . $tag . '(?:\s[^>]*)?>/i', $html);
    $zam = preg_match_all('/<\/' . $tag . '>/i', $html);
    return $otw - $zam;
}

$terms = get_terms(['taxonomy' => 'serie', 'hide_empty' => true]);
$wiersze = [];
$problemy = [];

foreach ($terms as $t) {
    if (get_term_meta($t->term_id, '_asiaauto_skip_title_regen', true) !== '1') { continue; }

    $id    = $t->term_id;
    $wiki  = (string) get_term_meta($id, 'asiaauto_wiki_body', true);
    $tytul = (string) get_term_meta($id, 'rank_math_title', true);
    $opis  = (string) get_term_meta($id, 'rank_math_description', true);
    $faq   = (string) get_term_meta($id, 'asiaauto_faq_json', true);

    // stan sprzed zmian — pierwszy dostępny backup
    $przed = '';
    foreach (['_t238_backup_wiki_ceny', '_t238_backup2_wiki_body', '_t238_backup_asiaauto_wiki_body'] as $k) {
        $v = (string) get_term_meta($id, $k, true);
        if ($v !== '') { $przed = $v; break; }
    }

    $q = new WP_Query([
        'post_type' => 'listings', 'post_status' => 'publish', 'posts_per_page' => -1,
        'no_found_rows' => true, 'fields' => 'ids',
        'tax_query' => [['taxonomy' => 'serie', 'field' => 'term_id', 'terms' => $id]],
    ]);
    $ceny = [];
    foreach ($q->posts as $p) {
        $c = (int) get_post_meta($p, 'price', true);
        if ($c > 0) { $ceny[] = $c; }
    }
    $min = $ceny ? min($ceny) : 0;
    $min_fmt = number_format($min, 0, ',', ' ');

    // 1. balans tagów
    $bal = [];
    foreach (['p', 'strong', 'h2', 'h3', 'table', 'tr', 'td'] as $tag) {
        $b = t238_balans($wiki, $tag);
        if ($b !== 0) { $bal[] = "$tag:$b"; }
    }

    // 2. zlepki
    $zlepki = preg_match('/\d(?:PLN|zł)/u', $wiki) || preg_match('/\d(?:PLN|zł)/u', $tytul);

    // 3. długość
    $dl_teraz = mb_strlen($wiki);
    $dl_przed = $przed !== '' ? mb_strlen($przed) : 0;
    $delta = $dl_przed ? $dl_teraz - $dl_przed : 0;

    // 4. JSON FAQ
    $faq_ok = $faq === '' ? null : is_array(json_decode($faq, true));

    // 5. spójność ceny
    preg_match('/od ([\d\x{00A0}\x{202F} ]+) PLN/u', $tytul, $mt);
    preg_match('/od ([\d\x{00A0}\x{202F} ]+) PLN/u', $opis, $mo);
    $cena_tyt = $mt ? (int) preg_replace('/\D/', '', $mt[1]) : 0;
    $cena_op  = $mo ? (int) preg_replace('/\D/', '', $mo[1]) : 0;
    $cena_ok  = ($cena_tyt === $min) && ($cena_op === 0 || $cena_op === $min);

    $status = [];
    if ($bal)                 { $status[] = 'TAGI(' . implode(',', $bal) . ')'; }
    if ($zlepki)              { $status[] = 'ZLEPEK'; }
    if ($faq_ok === false)    { $status[] = 'FAQ-JSON'; }
    if (!$cena_ok)            { $status[] = 'CENA'; }
    if ($dl_przed && $delta < -1200) { $status[] = 'UBYTEK'; }

    if ($status) { $problemy[] = $t->name . ': ' . implode(' ', $status); }

    $wiersze[] = sprintf('%-24s %8s %7s %7s  %-9s %s',
        mb_substr($t->name, 0, 24),
        $min_fmt,
        $dl_przed ?: '—',
        $dl_teraz,
        $dl_przed ? sprintf('%+d', $delta) : '—',
        $status ? implode(' ', $status) : 'ok'
    );
}

echo "AUDYT T-238 — huby z ręcznym tytułem\n";
echo str_repeat('─', 78) . "\n";
printf("%-24s %8s %7s %7s  %-9s %s\n", 'hub', 'cena od', 'przed', 'teraz', 'delta', 'stan');
echo str_repeat('─', 78) . "\n";
foreach ($wiersze as $w) { echo $w . "\n"; }
echo str_repeat('─', 78) . "\n";
printf("Hubów: %d | z zastrzeżeniami: %d\n", count($wiersze), count($problemy));
foreach ($problemy as $p) { echo "  ! $p\n"; }
if (!$problemy) { echo "Brak degradacji: tagi zbalansowane, JSON poprawny, ceny spójne z bazą.\n"; }
