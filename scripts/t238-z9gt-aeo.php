<?php
/**
 * t238-z9gt-aeo.php — hub Denza Z9 GT: prawda o dystrybucji + porównanie cen (AEO).
 *
 * Powód (pomiar u źródła 2026-08-05, konfigurator denza.com/pl): treść huba twierdzi,
 * że Z9 GT „dopiero pojawi się w polskiej dystrybucji”. Nieprawda — model jest
 * w oficjalnym konfiguratorze, wersja DM od 526 320 zł w konfiguracji bazowej.
 *
 * Dla AEO to najgorszy możliwy stan: model językowy czytający hub dostaje fałszywy fakt
 * i nas nie zacytuje. Prawda jest przy tym dla nas korzystniejsza od tego, co tam stoi —
 * argumentem nie jest „jesteśmy pierwsi”, tylko różnica ~330 tys. zł na tym samym aucie.
 *
 * Zakres: `asiaauto_wiki_body` (jedna sekcja), `asiaauto_faq_json` (odpowiedź + nowe pytanie
 * pod PAA „Ile kosztuje BYD Denza Z9 GT?”), `_asiaauto_lead`. Liczby egzemplarzy świadomie
 * NIE wpisujemy — rotują codziennie i nie niosą wartości (decyzja Janka 2026-08-05).
 *
 * Użycie:
 *   wp eval-file scripts/t238-z9gt-aeo.php          # symulacja
 *   wp eval-file scripts/t238-z9gt-aeo.php apply    # zapis
 */

if (!defined('ABSPATH')) { exit; }

$APPLY   = in_array('apply', $args ?? [], true);
$TERM_ID = 4660;                 // serie: Z9 GT DM-i
$CENA_OFICJALNA = '526 320';     // denza.com/pl, wariant DM, kolor bez dopłaty, 2026-08-05
$POMIAR = 'sierpień 2026';

$term = get_term($TERM_ID, 'serie');
if (!$term || is_wp_error($term)) { echo "Term $TERM_ID nie istnieje.\n"; return; }

// Cena „od” z bazy — spójna z tytułem i description po t238-hub-title-refresh.
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
if (!$ceny) { echo "Brak cen w ofertach — przerywam.\n"; return; }
$cena_od = number_format(min($ceny), 0, ',', ' ');
$roznica = number_format((int) round((str_replace(' ', '', $CENA_OFICJALNA) - min($ceny)) / 1000) * 1000, 0, ',', ' ');

// ── nowe treści ──────────────────────────────────────────────────────────────

$sekcja_nowa =
    '<h2>Ile kosztuje Denza Z9 GT w Polsce — dystrybucja a import</h2>'
  . '<p>Auto jest w Polsce dostępne dwiema drogami. W oficjalnym konfiguratorze Denzy wersja DM '
  . 'startuje z poziomu ' . $CENA_OFICJALNA . ' PLN (stan na ' . $POMIAR . '), a wybór lakieru innego '
  . 'niż podstawowy podnosi tę kwotę o kolejne 12 000 PLN. Sprowadzenie tego samego modelu z Chin '
  . 'przez Prima-Auto kosztuje od ' . $cena_od . ' PLN — różnica sięga ' . $roznica . ' PLN przy '
  . 'identycznym samochodzie. Import ma swoją cenę w czasie: od wpłaty depozytu do odbioru mija '
  . 'zwykle 8–12 tygodni, podczas gdy w salonie odbierasz auto z dostępnego stanu. Cena importu jest '
  . 'końcowa — obejmuje transport morski, cło, VAT, akcyzę, homologację indywidualną i rejestrację '
  . 'w Polsce.</p>';

$lead_nowy =
    'Denza Z9 GT DM-i sprowadzasz z Chin przez Prima-Auto w cenie od ' . $cena_od . ' PLN — to flagowy '
  . 'liftback (shooting-brake) premium marki Denza (sub-brand BYD) z hybrydą plug-in 870 KM i napędem 4x4. '
  . 'Cena obejmuje import: cło, VAT, homologację i rejestrację w Polsce. W oficjalnej polskiej dystrybucji '
  . 'ten sam model kosztuje od ' . $CENA_OFICJALNA . ' PLN (konfigurator producenta, ' . $POMIAR . '), '
  . 'więc import oznacza oszczędność rzędu ' . $roznica . ' PLN przy dłuższym oczekiwaniu — zwykle 8–12 tygodni.';

$faq_dostepnosc =
    'Tak. Denza prowadzi już oficjalną dystrybucję w Polsce, a Z9 GT jest dostępny w konfiguratorze '
  . 'producenta — wersja DM (hybryda plug-in) kosztuje ' . $CENA_OFICJALNA . ' PLN w konfiguracji bazowej '
  . '(stan na ' . $POMIAR . '). Ten sam model z importu przez Prima-Auto zaczyna się od ' . $cena_od . ' PLN, '
  . 'czyli około 37% ceny z oficjalnego cennika. Różnica bierze się z tego, że kupujesz auto w chińskiej '
  . 'cenie rynkowej i płacisz za sprowadzenie, zamiast za marżę europejskiej sieci dealerskiej.';

$faq_byd_q = 'Ile kosztuje BYD Denza Z9 GT?';
$faq_byd_a =
    'Denza to marka koncernu BYD, więc auto bywa opisywane jako BYD Denza Z9 GT. W oficjalnej polskiej '
  . 'dystrybucji wersja DM kosztuje od ' . $CENA_OFICJALNA . ' PLN (' . $POMIAR . '). Z importu z Chin przez '
  . 'Prima-Auto ten sam samochód kosztuje od ' . $cena_od . ' PLN — to cena końcowa, zawierająca transport '
  . 'morski, cło, VAT, akcyzę i homologację indywidualną.';

// ── plan zmian ───────────────────────────────────────────────────────────────

$zmiany = [];

$wiki = (string) get_term_meta($TERM_ID, 'asiaauto_wiki_body', true);
$wzor_sekcji = '/<h[23][^>]*>\s*Dostępność w Polsce.*?(?=<h[23]|$)/su';
if (preg_match($wzor_sekcji, $wiki)) {
    // preg_replace_callback — treść zastępująca zawiera znaki, które w stringu
    // zastępującym byłyby interpretowane jako referencje do grup.
    $wiki_new = preg_replace_callback($wzor_sekcji, fn() => $sekcja_nowa, $wiki, 1);
    if ($wiki_new !== $wiki) {
        $zmiany['asiaauto_wiki_body'] = ['old' => $wiki, 'new' => $wiki_new, 'etyk' => 'sekcja „Dostępność w Polsce”'];
    }
} else {
    echo "! Nie znalazłem sekcji „Dostępność w Polsce” — wiki_body zostaje bez zmian.\n";
}

$lead = (string) get_term_meta($TERM_ID, '_asiaauto_lead', true);
if ($lead !== '' && $lead !== $lead_nowy) {
    $zmiany['_asiaauto_lead'] = ['old' => $lead, 'new' => $lead_nowy, 'etyk' => 'lead'];
}

$faq = json_decode((string) get_term_meta($TERM_ID, 'asiaauto_faq_json', true), true);
if (is_array($faq)) {
    $faq_new = $faq;
    $tknieto = false;
    foreach ($faq_new as $i => $poz) {
        if (mb_stripos($poz['q'] ?? '', 'dostępna w Polsce') !== false) {
            $faq_new[$i]['a'] = $faq_dostepnosc;
            $tknieto = true;
        }
    }
    $ma_byd = false;
    foreach ($faq_new as $poz) {
        if (mb_stripos($poz['q'] ?? '', 'BYD Denza') !== false) { $ma_byd = true; }
    }
    if (!$ma_byd) {
        array_splice($faq_new, 1, 0, [['q' => $faq_byd_q, 'a' => $faq_byd_a]]);
        $tknieto = true;
    }
    if ($tknieto) {
        $json_new = wp_json_encode($faq_new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $zmiany['asiaauto_faq_json'] = [
            'old' => (string) get_term_meta($TERM_ID, 'asiaauto_faq_json', true),
            'new' => $json_new,
            'etyk' => 'FAQ: odpowiedź o dystrybucji + nowe pytanie „BYD Denza”',
        ];
    }
}

// ── wykonanie ────────────────────────────────────────────────────────────────

echo $APPLY ? "=== ZAPIS ===\n\n" : "=== SYMULACJA (bez zapisu) ===\n\n";
printf("Hub: %s | cena od %s PLN | dystrybutor %s PLN | różnica %s PLN\n\n",
    $term->name, $cena_od, $CENA_OFICJALNA, $roznica);

if (!$zmiany) { echo "Brak zmian do wprowadzenia.\n"; return; }

/** Wycina okolice pierwszej rozbieżności — pole bywa długie, a zmiana siedzi w środku. */
function t238_fragment_zmiany(string $old, string $new, int $ile = 210): array {
    $o = trim(strip_tags($old));
    $n = trim(strip_tags($new));
    $i = 0;
    $max = min(mb_strlen($o), mb_strlen($n));
    while ($i < $max && mb_substr($o, $i, 1) === mb_substr($n, $i, 1)) { $i++; }
    $start = max(0, $i - 40);
    return [
        ($start > 0 ? '…' : '') . mb_substr($o, $start, $ile),
        ($start > 0 ? '…' : '') . mb_substr($n, $start, $ile),
    ];
}

foreach ($zmiany as $pole => $z) {
    [$frag_o, $frag_n] = t238_fragment_zmiany($z['old'], $z['new']);
    printf("── %s (%s)\n", $pole, $z['etyk']);
    printf("   było : %s\n", $frag_o);
    printf("   będzie: %s\n\n", $frag_n);
    if ($APPLY) {
        update_term_meta($TERM_ID, '_t238_backup_' . $pole, $z['old']);
        update_term_meta($TERM_ID, $pole, $z['new']);
    }
}

// Kontrola poprawności JSON po zmianie — FAQ zasila schema FAQPage.
if (isset($zmiany['asiaauto_faq_json'])) {
    $test = json_decode($zmiany['asiaauto_faq_json']['new'], true);
    printf("JSON FAQ: %s (%d pytań)\n",
        is_array($test) ? 'poprawny' : 'BŁĄD PARSOWANIA', is_array($test) ? count($test) : 0);
}

echo $APPLY
    ? "\nZapisano. Poprzednie wartości w meta `_t238_backup_<pole>`.\n"
    : "\nNic nie zapisano. Zapis: argument `apply`.\n";
