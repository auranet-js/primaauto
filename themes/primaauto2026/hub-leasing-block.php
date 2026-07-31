<?php
/**
 * T-219 część B — blok leasingowy na hubach serie.
 *
 * Cała logika trzymana OBOK taxonomy-serie.php (strefa krucha) — tam wchodzą tylko trzy linie.
 *
 * Zasady treści (decyzje 2026-07-31, patrz docs/roadmapa/T-219-landing-leasing.md §10-11):
 * - H2 w szyku `{model} leasing`, nie `Leasing {model}` — GSC 90 dni: 54 imp vs 4 imp.
 * - ZERO liczb: bez cen (rotują i wracają nieaktualne w cytatach LLM), bez liczby ofert
 *   (87 z 317 hubów ma jedną ofertę), bez procentu depozytu (`leasing_deposit_percent`
 *   w `asiaauto_order_config` to nastawa zmieniana ad hoc — hardkod w 317 stron = 317 kłamstw
 *   po jej zmianie). Warunki finansowe żyją WYŁĄCZNIE na landingu, w jednym miejscu.
 * - Trzy warianty akapitu i anchora, parowane po `term_id % 3` (rozkład 118 / 95 / 104),
 *   żeby 317 stron nie dostało identycznego bloku.
 * - Anchor NIE zawiera nazwy modelu — link prowadzi na stronę ogólną, więc obietnica
 *   „leasing {model}" w anchorze byłaby mismatchem intencji.
 *
 * @package primaauto2026
 */

if (!defined('ABSPATH')) { exit; }

if (!function_exists('primaauto_hub_leasing_block')) :
/**
 * Zwraca HTML bloku leasingowego dla hubu serie.
 *
 * Zero zapytań do bazy poza jednym get_term_meta — dane bierze z tego, co szablon już ma.
 *
 * @param WP_Term|null $term      Term taksonomii `serie`.
 * @param WP_Term|null $make_term Term marki, jeśli szablon go zna (unika dodatkowego zapytania).
 * @return string HTML albo pusty string, gdy brak danych.
 */
function primaauto_hub_leasing_block($term, $make_term = null) {
    if (!$term instanceof WP_Term) { return ''; }

    $full_title = (string) (get_term_meta($term->term_id, '_serie_full_title', true) ?: $term->name);
    if ($full_title === '') { return ''; }

    $marka = ($make_term instanceof WP_Term) ? (string) $make_term->name : '';

    switch ($term->term_id % 3) {
        case 0:
            $tekst  = sprintf(
                '%s można wziąć w leasing operacyjny — również jako samochód sprowadzany z Chin. Finansowanie organizujemy my: proponujemy leasingodawcę, który finansuje auta importowane spoza Unii Europejskiej, i prowadzimy Cię przez wniosek.',
                $full_title
            );
            $anchor = 'Jak działa leasing samochodu z Chin →';
            break;

        case 1:
            $tekst  = sprintf(
                'Tak, %s da się wziąć w leasing — i nie musisz szukać firmy leasingowej na własną rękę. Zamiast wykładać całą kwotę za samochód, płacisz opłatę wstępną i miesięczne raty, a auto pracuje w Twojej firmie od pierwszego dnia.',
                $full_title
            );
            $anchor = 'Leasing auta z importu — krok po kroku →';
            break;

        default:
            // full_title zwykle ZAWIERA już markę („Geely Monjaro") — doklejamy ją tylko wtedy,
            // gdy jej tam nie ma, żeby nie wyszło „Geely Geely Monjaro".
            $podmiot = ($marka !== '' && stripos($full_title, $marka) !== 0)
                ? $marka . ' ' . $full_title
                : $full_title;
            $tekst  = sprintf(
                'Leasing operacyjny na %s jest dostępny dla firm — od jednoosobowej działalności po spółki. %s sprowadzamy z Chin w cenie kompletnej: transport morski, cło, VAT, homologacja i rejestracja są już w kwocie, którą finansuje firma leasingowa.',
                $full_title,
                $podmiot
            );
            $anchor = 'Zasady leasingu samochodu sprowadzanego z Chin →';
            break;
    }

    return sprintf(
        '<section class="aa-hub__leasing">%s<h2>%s leasing</h2>%s<p>%s</p>%s<p><a href="/leasing-samochodu-z-chin/">%s</a></p>%s</section>',
        "\n            ",
        esc_html($full_title),
        "\n            ",
        esc_html($tekst),
        "\n            ",
        esc_html($anchor),
        "\n        "
    );
}
endif;
