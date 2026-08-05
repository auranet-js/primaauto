<?php
/**
 * t238-z9gt-akapit.php — dokończenie AEO na hubie Z9 GT: akapit wstępny `wiki_body`.
 *
 * `asiaauto_wiki_body` zaczyna się własnym akapitem wstępnym (przed pierwszym <h2>),
 * który duplikuje `_asiaauto_lead`. Poprawka z t238-z9gt-aeo.php objęła lead i sekcję
 * „Dostępność w Polsce”, ale ten akapit został — niósł dalej nieaktualny przedział cen
 * i twierdzenie, że model jest „dopiero u progu oficjalnej dystrybucji” (nieprawda:
 * jest w konfiguratorze denza.com/pl od 526 320 zł, pomiar 2026-08-05).
 *
 * Wykryte kontrolą HTML po zapisie — pojedyncza podmiana sekcji nie wystarczyła,
 * bo ta sama narracja siedziała w dwóch miejscach tego samego pola.
 *
 * Użycie:
 *   wp eval-file scripts/t238-z9gt-akapit.php          # symulacja
 *   wp eval-file scripts/t238-z9gt-akapit.php apply    # zapis
 */

if (!defined('ABSPATH')) { exit; }

$APPLY   = in_array('apply', $args ?? [], true);
$TERM_ID = 4660;
$CENA_OFICJALNA = '526 320';
$POMIAR = 'sierpień 2026';

$q = new WP_Query([
    'post_type' => 'listings', 'post_status' => 'publish', 'posts_per_page' => -1,
    'no_found_rows' => true, 'fields' => 'ids',
    'tax_query' => [['taxonomy' => 'serie', 'field' => 'term_id', 'terms' => $TERM_ID]],
]);
$ceny = [];
foreach ($q->posts as $p) {
    $c = (int) get_post_meta($p, 'price', true);
    if ($c > 0) { $ceny[] = $c; }
}
if (!$ceny) { echo "Brak cen — przerywam.\n"; return; }
$cena_od = number_format(min($ceny), 0, ',', ' ');
$roznica = number_format((int) round((526320 - min($ceny)) / 1000) * 1000, 0, ',', ' ');

// Bez liczby egzemplarzy — rotuje codziennie i nie niesie wartości (decyzja Janka 2026-08-05).
$akapit_nowy =
    '<p>Denza Z9 GT DM-i sprowadzasz z Chin przez Prima-Auto w cenie od ' . $cena_od . ' PLN — to flagowy '
  . 'liftback (shooting-brake) premium marki Denza, sub-brandu koncernu BYD, z hybrydą plug-in o łącznej '
  . 'mocy 870 KM i napędem na cztery koła. Cena obejmuje import: cło, VAT, homologację indywidualną '
  . 'i rejestrację w Polsce. W oficjalnej polskiej dystrybucji ten sam model kosztuje od ' . $CENA_OFICJALNA
  . ' PLN (konfigurator producenta, ' . $POMIAR . '), więc import oznacza oszczędność rzędu ' . $roznica
  . ' PLN — kosztem dłuższego oczekiwania, zwykle 8–12 tygodni. Egzemplarze pochodzą z roczników 2024–2025 '
  . 'i mają niskie przebiegi.</p>';

$wiki = (string) get_term_meta($TERM_ID, 'asiaauto_wiki_body', true);
if (!preg_match('/^\s*<p>.*?<\/p>/su', $wiki, $m)) {
    echo "Nie znalazłem akapitu wstępnego — nic nie zmieniam.\n";
    return;
}
$stary = $m[0];
if (mb_stripos($stary, 'u progu oficjalnej') === false && mb_stripos($stary, '209 000') === false) {
    echo "Akapit wstępny nie zawiera już starej narracji — nic do zrobienia.\n";
    return;
}

$wiki_new = preg_replace_callback('/^\s*<p>.*?<\/p>/su', fn() => $akapit_nowy, $wiki, 1);

echo $APPLY ? "=== ZAPIS ===\n\n" : "=== SYMULACJA (bez zapisu) ===\n\n";
echo "było :\n" . strip_tags($stary) . "\n\n";
echo "będzie:\n" . strip_tags($akapit_nowy) . "\n\n";

if ($APPLY) {
    update_term_meta($TERM_ID, '_t238_backup2_wiki_body', $wiki);
    update_term_meta($TERM_ID, 'asiaauto_wiki_body', $wiki_new);
    echo "Zapisano. Poprzednia wersja w `_t238_backup2_wiki_body`.\n";
} else {
    echo "Nic nie zapisano. Zapis: argument `apply`.\n";
}
