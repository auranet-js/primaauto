<?php
/**
 * t238-z9gt-faq1.php — ostatnia nieaktualna cena na hubie Z9 GT (FAQ nr 1).
 *
 * Kontrola HTML po poprzednich poprawkach wykazała, że przedział „od 209 000 do 279 000 PLN
 * (mediana ok. 235 000)” został jeszcze w pierwszym pytaniu FAQ. Baza: od 197 000 zł.
 *
 * Świadomie NIE wpisujemy górnej granicy ani mediany — obie rotują z ofertą i za tydzień
 * znów byłyby nieprawdą. Zostaje cena wejścia (spójna z tytułem, description i leadem)
 * plus jakościowe wyjaśnienie, od czego zależy rozpiętość.
 *
 * Użycie:
 *   wp eval-file scripts/t238-z9gt-faq1.php          # symulacja
 *   wp eval-file scripts/t238-z9gt-faq1.php apply    # zapis
 */

if (!defined('ABSPATH')) { exit; }

$APPLY   = in_array('apply', $args ?? [], true);
$TERM_ID = 4660;

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

$odp_nowa =
    'W Prima-Auto Denza Z9 GT DM-i z importu kosztuje od ' . $cena_od . ' PLN. To cena końcowa, '
  . 'zawierająca koszt pojazdu, transport morski, cło, VAT, homologację indywidualną i rejestrację '
  . 'w Polsce — bez dopłat na kolejnych etapach. Rozpiętość cen wynika z poziomu wyposażenia '
  . '(wersje Max i Ultra), rocznika i przebiegu konkretnego egzemplarza. Dla porównania: w oficjalnej '
  . 'polskiej dystrybucji Denzy ten sam model kosztuje od 526 320 PLN (konfigurator producenta, '
  . 'sierpień 2026).';

$surowy = (string) get_term_meta($TERM_ID, 'asiaauto_faq_json', true);
$faq = json_decode($surowy, true);
if (!is_array($faq)) { echo "FAQ nie parsuje się jako JSON — przerywam.\n"; return; }

$idx = null;
foreach ($faq as $i => $poz) {
    if (mb_stripos($poz['q'] ?? '', 'Ile kosztuje sprowadzenie') !== false) { $idx = $i; break; }
}
if ($idx === null) { echo "Nie znalazłem pytania o koszt sprowadzenia.\n"; return; }
if ($faq[$idx]['a'] === $odp_nowa) { echo "Odpowiedź już aktualna.\n"; return; }

echo $APPLY ? "=== ZAPIS ===\n\n" : "=== SYMULACJA (bez zapisu) ===\n\n";
printf("pytanie: %s\n\n", $faq[$idx]['q']);
printf("było :\n%s\n\n", $faq[$idx]['a']);
printf("będzie:\n%s\n\n", $odp_nowa);

$faq[$idx]['a'] = $odp_nowa;
$json_new = wp_json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$test = json_decode($json_new, true);
printf("JSON po zmianie: %s (%d pytań)\n", is_array($test) ? 'poprawny' : 'BŁĄD', is_array($test) ? count($test) : 0);

if ($APPLY && is_array($test)) {
    update_term_meta($TERM_ID, '_t238_backup3_faq_json', $surowy);
    update_term_meta($TERM_ID, 'asiaauto_faq_json', $json_new);
    echo "\nZapisano. Poprzednia wersja w `_t238_backup3_faq_json`.\n";
} elseif (!$APPLY) {
    echo "\nNic nie zapisano. Zapis: argument `apply`.\n";
}
