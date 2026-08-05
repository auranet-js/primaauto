<?php
/**
 * t238-hub-title-refresh.php — odmrożenie cen w ręcznych tytułach hubów + brakujące H1.
 *
 * Problem (pomiar 2026-08-05): 12 termów `serie` ma flagę `_asiaauto_skip_title_regen=1`,
 * która chroni ręcznie napisany `rank_math_title` przed codziennym cronem
 * `AsiaAuto_HubTitleGenerator`. Skutek uboczny: cena wpisana w tytuł zostaje zamrożona
 * w dniu wpisania, a oferta rotuje. Wszystkie 12 rozjechały się z bazą (suma |Δ| = 156 000 zł).
 *
 * Osobno: 3 z nich (Z9 GT DM-i, Zeekr 9X, Zeekr 8X) nie mają `_asiaauto_h1_suffix`,
 * więc renderują domyślne H1 „— import z Chin", które nie potwierdza obietnicy z tytułu
 * („cena w Polsce 2026"). Google rozstrzyga taki rozjazd na korzyść H1 i przepisuje tytuł
 * w SERP — potwierdzone dla Z9 GT: zamiast ceny wyświetla „Denza Z9 GT DM-i — import z Chin".
 *
 * Podejście addytywne: NIE ruszamy generatora ani formatu ręcznych tytułów. Podmieniamy
 * wyłącznie liczbę po „od " w istniejącym stringu i dopisujemy brakujące H1 wzorem
 * z pozostałych 265 hubów („cena w Polsce i import z Chin").
 *
 * Użycie:
 *   wp eval-file scripts/t238-hub-title-refresh.php          # symulacja (domyślnie)
 *   wp eval-file scripts/t238-hub-title-refresh.php apply    # zapis (argument pozycyjny)
 */

if (!defined('ABSPATH')) { exit; }

// WP-CLI odrzuca własne `--flagi` przy eval-file, więc zapis włącza argument
// POZYCYJNY: `wp eval-file <plik> apply`. Domyślnie symulacja.
$APPLY = in_array('apply', $args ?? [], true) || in_array('--apply', $argv ?? [], true);
$H1_WZOR = 'cena w Polsce i import z Chin';

/** „197 000" z separatorem spacjowym, jak w istniejących tytułach. */
function t238_format(int $zl): string {
    return number_format($zl, 0, ',', ' ');
}

/** Minimalna cena publikowanych ofert w termie. */
function t238_min_cena(int $term_id): int {
    $q = new WP_Query([
        'post_type' => 'listings', 'post_status' => 'publish', 'posts_per_page' => -1,
        'no_found_rows' => true, 'fields' => 'ids',
        'tax_query' => [['taxonomy' => 'serie', 'field' => 'term_id', 'terms' => $term_id]],
    ]);
    $ceny = [];
    foreach ($q->posts as $p) {
        $c = (int) get_post_meta($p, 'price', true);
        if ($c > 0) { $ceny[] = $c; }
    }
    return $ceny ? min($ceny) : 0;
}

/**
 * Podmiana samej liczby po „od ". Zwraca null, gdy wzorzec nie pasuje —
 * wtedy tytuł zostaje nietknięty, bo nie zgadujemy jego budowy.
 *
 * Uwaga na `$1` w stringu zastępującym: piszemy `${1}`, żeby PHP nie sklejał
 * numeru grupy z następującą cyfrą (wpadka z 2026-08-04, 77 uszkodzonych pól).
 */
function t238_podmien_cene(string $title, int $cena): ?string {
    // Separator tysięcy bywa zwykłą spacją, nbsp lub wąską spacją. Grupa liczby musi
    // kończyć się CYFRĄ, inaczej klasa znaków pochłania też spację przed „PLN”
    // i wychodzi zlepek „197 000PLN” (złapane w symulacji 2026-08-05).
    $wzor = '/(od\s)([\d\x{00A0}\x{202F} ]*\d)(\s*PLN)/u';
    if (!preg_match($wzor, $title)) { return null; }
    return preg_replace($wzor, '${1}' . t238_format($cena) . '${3}', $title, 1);
}

/**
 * Podmiana liczby egzemplarzy w zdaniu „…, 3 szt. dostępne”. „szt.” jest nieodmienne,
 * więc sama liczba wymienia się bez psucia gramatyki. Brak wzorca → null (nie zgadujemy).
 */
function t238_podmien_sztuki(string $tekst, int $ile): ?string {
    $wzor = '/\d+(\s*szt\.)/u';
    if (!preg_match($wzor, $tekst)) { return null; }
    return preg_replace($wzor, $ile . '${1}', $tekst, 1);
}

/** Liczba publikowanych ofert w termie. */
function t238_ile_sztuk(int $term_id): int {
    $q = new WP_Query([
        'post_type' => 'listings', 'post_status' => 'publish', 'posts_per_page' => -1,
        'no_found_rows' => true, 'fields' => 'ids',
        'tax_query' => [['taxonomy' => 'serie', 'field' => 'term_id', 'terms' => $term_id]],
    ]);
    return count($q->posts);
}

$terms = get_terms(['taxonomy' => 'serie', 'hide_empty' => true]);
$plan = [];

foreach ($terms as $t) {
    if (get_term_meta($t->term_id, '_asiaauto_skip_title_regen', true) !== '1') { continue; }

    $tytul = (string) get_term_meta($t->term_id, 'rank_math_title', true);
    $h1    = (string) get_term_meta($t->term_id, '_asiaauto_h1_suffix', true);
    $cena  = t238_min_cena($t->term_id);
    if ($cena <= 0 || $tytul === '') { continue; }

    $nowy = t238_podmien_cene($tytul, $cena);

    // `rank_math_description` i `asiaauto_seo_desc` niosą tę samą zamrożoną cenę „od X”
    // w zdaniu „… — cena w Polsce od 210 000 PLN.” — ta sama podmiana, te same reguły.
    // Część opisów niesie także zamrożoną liczbę egzemplarzy („72 szt. dostępne”
    // przy 19 w bazie), więc poprawiamy oba fakty naraz — inaczej pole zostaje
    // naprawione w połowie i dalej wprowadza w błąd.
    $sztuk = t238_ile_sztuk($t->term_id);
    $opisy = [];
    foreach (['rank_math_description', 'asiaauto_seo_desc'] as $pole) {
        $stary = (string) get_term_meta($t->term_id, $pole, true);
        if ($stary === '') { continue; }
        $now = t238_podmien_cene($stary, $cena) ?? $stary;
        $now = t238_podmien_sztuki($now, $sztuk) ?? $now;
        if ($now !== $stary) {
            $opisy[$pole] = ['old' => $stary, 'new' => $now];
        }
    }

    $plan[] = [
        'id'        => $t->term_id,
        'nazwa'     => $t->name,
        'tytul_old' => $tytul,
        'tytul_new' => ($nowy !== null && $nowy !== $tytul) ? $nowy : null,
        'nieznany_wzorzec' => ($nowy === null),
        'h1_new'    => ($h1 === '') ? $H1_WZOR : null,
        'opisy'     => $opisy,
        'cena'      => $cena,
    ];
}

echo $APPLY ? "=== ZAPIS ===\n\n" : "=== SYMULACJA (bez zapisu) ===\n\n";

$zm_t = 0; $zm_h = 0; $zm_d = 0; $nieznane = 0;
foreach ($plan as $p) {
    $robi = $p['tytul_new'] || $p['h1_new'] || $p['opisy'] || $p['nieznany_wzorzec'];
    if (!$robi) { continue; }

    printf("[%d] %s — od %s zł\n", $p['id'], $p['nazwa'], t238_format($p['cena']));

    if ($p['nieznany_wzorzec']) {
        $nieznane++;
        printf("     ! tytuł nie pasuje do wzorca „od … PLN” — zostawiam bez zmian\n");
        printf("       %s\n", $p['tytul_old']);
    } elseif ($p['tytul_new']) {
        $zm_t++;
        printf("     - %s\n", $p['tytul_old']);
        printf("     + %s\n", $p['tytul_new']);
    }

    if ($p['h1_new']) {
        $zm_h++;
        printf("     H1: BRAK (renderuje „— import z Chin”)\n");
        printf("     H1: + %s\n", $p['h1_new']);
    }

    foreach ($p['opisy'] as $pole => $zm) {
        $zm_d++;
        printf("     %s\n", $pole);
        printf("       - %s\n", mb_substr($zm['old'], 0, 96));
        printf("       + %s\n", mb_substr($zm['new'], 0, 96));
    }
    echo "\n";

    if (!$APPLY) { continue; }

    if ($p['tytul_new']) {
        update_term_meta($p['id'], '_t238_backup_title', $p['tytul_old']);
        update_term_meta($p['id'], 'rank_math_title', $p['tytul_new']);
    }
    if ($p['h1_new']) {
        update_term_meta($p['id'], '_asiaauto_h1_suffix', $p['h1_new']);
    }
    foreach ($p['opisy'] as $pole => $zm) {
        update_term_meta($p['id'], '_t238_backup_' . $pole, $zm['old']);
        update_term_meta($p['id'], $pole, $zm['new']);
    }
}

printf("Tytułów: %d | brakujących H1: %d | opisów: %d | wzorzec nierozpoznany: %d\n",
    $zm_t, $zm_h, $zm_d, $nieznane);
echo $APPLY
    ? "Zapisano. Stare tytuły w meta `_t238_backup_title`.\n"
    : "Nic nie zapisano. Zapis: dopisz argument `apply`.\n";
