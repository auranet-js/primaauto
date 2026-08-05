<?php
/**
 * t238-wiki-ceny.php — przedziały cenowe rozsiane po `asiaauto_wiki_body`.
 *
 * Generator treści wplata cenę w kilka miejsc jednego pola (na hubie Z9 GT: akapit wstępny,
 * sekcja „import z Chin i cena w Polsce” oraz sekcja „Import … przez Prima-Auto”).
 * Podmiana jednej sekcji zostawia pozostałe — wykryte kontrolą HTML 2026-08-05,
 * dwie rundy poprawek nie wystarczyły.
 *
 * Zastępujemy przedział ceną wejścia z bazy. Górnej granicy i mediany świadomie
 * nie wpisujemy: rotują z ofertą i za tydzień znów byłyby nieprawdą, a wartość niosą
 * żadną. Zostaje jedna liczba, spójna z tytułem, description, leadem i FAQ.
 *
 * Obsługiwane kształty (oba widziane w treści):
 *   „od 209 000 do 279 000 PLN, z medianą ok. 235 000”
 *   „(209 000–279 000 PLN w zależności od …)”
 *
 * Użycie:
 *   wp eval-file scripts/t238-wiki-ceny.php <term_id>          # symulacja
 *   wp eval-file scripts/t238-wiki-ceny.php <term_id> apply    # zapis
 */

if (!defined('ABSPATH')) { exit; }

$APPLY   = in_array('apply', $args ?? [], true);
$TERM_ID = 0;
foreach (($args ?? []) as $a) {
    if (ctype_digit((string) $a)) { $TERM_ID = (int) $a; break; }
}
if (!$TERM_ID) { echo "Podaj term_id: wp eval-file <plik> 4660 [apply]\n"; return; }

$term = get_term($TERM_ID, 'serie');
if (!$term || is_wp_error($term)) { echo "Term $TERM_ID nie istnieje.\n"; return; }

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

$wiki = (string) get_term_meta($TERM_ID, 'asiaauto_wiki_body', true);
if ($wiki === '') { echo "Pusty wiki_body.\n"; return; }

$SEP = '[\s\x{00A0}\x{202F}]';   // spacja zwykła, nbsp, wąska
$L   = '\d{2,3}(?:' . $SEP . '\d{3})+';   // „209 000”

// Mediana bywa oddzielona od ceny tagiem („… 290 000 PLN</strong> (mediana ok. 203 000)”),
// więc NIE wciągamy jej w podmianę przedziału — zabranie tagu rozerwałoby HTML.
// Usuwamy ją osobnym przebiegiem; rotuje z ofertą i nie niesie wartości dla czytelnika.
$wzorce = [
    // „od 209 000 do 279 000 PLN” → „od 197 000 PLN”
    '/od' . $SEP . $L . $SEP . 'do' . $SEP . $L . $SEP . 'PLN/u' => 'od ' . $cena_od . ' PLN',
    // „209 000–279 000 PLN” (en dash lub dywiz)
    '/' . $L . '[–-]' . $L . $SEP . 'PLN/u' => 'od ' . $cena_od . ' PLN',
    // mediana w dwóch kształtach — do usunięcia
    '/,' . $SEP . 'z' . $SEP . 'medianą' . $SEP . 'ok\.' . $SEP . $L . '/u' => '',
    '/' . $SEP . '\(mediana' . $SEP . 'ok\.' . $SEP . $L . '\)/u' => '',
];

$nowy = $wiki;
$trafienia = [];
foreach ($wzorce as $wzor => $zamiast) {
    $przed = $nowy;
    // callback — string zastępujący zawiera cyfry, które po „$” byłyby czytane
    // jako numer grupy (wpadka z 2026-08-04, 77 uszkodzonych pól)
    $nowy = preg_replace_callback($wzor, function ($m) use ($zamiast, &$trafienia) {
        // zapisujemy PARĘ (dopasowanie, zamiennik) — inaczej raport pokazywałby
        // podmianę tam, gdzie w rzeczywistości następuje usunięcie
        $trafienia[] = ['co' => $m[0], 'na' => $zamiast];
        return $zamiast;
    }, $nowy);
    if ($nowy === null) { echo "BŁĄD regexu — przerywam, nic nie zapisano.\n"; return; }
}

echo $APPLY ? "=== ZAPIS ===\n\n" : "=== SYMULACJA (bez zapisu) ===\n\n";
printf("Hub: %s (id %d) | cena wejścia z bazy: %s PLN\n\n", $term->name, $TERM_ID, $cena_od);

if (!$trafienia) { echo "Nie znalazłem przedziałów cenowych — nic do zrobienia.\n"; return; }

printf("Znalezione dopasowania (%d):\n", count($trafienia));
foreach ($trafienia as $t) {
    printf("   - %s\n   + %s\n", trim($t['co']), $t['na'] === '' ? '(usunięte)' : $t['na']);
}

// Kontrola: żadnych osieroconych liczb ze starych przedziałów
$stare = [];
foreach ($trafienia as $t) {
    if (preg_match_all('/' . $L . '/u', $t['co'], $mm)) {
        foreach ($mm[0] as $liczba) {
            if (mb_strpos($nowy, $liczba) !== false && $liczba !== $cena_od) { $stare[] = $liczba; }
        }
    }
}
printf("\nPozostałe wystąpienia starych liczb: %s\n",
    $stare ? implode(', ', array_unique($stare)) . '  ← sprawdź ręcznie' : 'brak');

if ($APPLY) {
    update_term_meta($TERM_ID, '_t238_backup_wiki_ceny', $wiki);
    update_term_meta($TERM_ID, 'asiaauto_wiki_body', $nowy);
    echo "\nZapisano. Poprzednia wersja w `_t238_backup_wiki_ceny`.\n";
} else {
    echo "\nNic nie zapisano. Zapis: dopisz `apply`.\n";
}
