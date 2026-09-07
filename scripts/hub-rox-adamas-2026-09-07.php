<?php
/**
 * hub-rox-adamas-2026-09-07.php — hub modelu ROX Adamas (przed podażą).
 *
 * Kontekst: w brand-mappingu v6.5 (27.07) Adamas został świadomie pominięty z adnotacją
 * „osobny hub, gdy wpadnie egzemplarz". Dziś hub powstaje mimo zera ofert — decyzja Janka
 * o rankowaniu przed podażą (jak Q06, GX7, N90, N70). DFS PL: „rox adamas" 90/mc
 * (w lipcu 70), „rox adamas cena" 40/mc, „rox 01" 170/mc.
 *
 * Dane: katalog Autohome, seria 8430 (极石ADAMAS), wersje 73854 (7 miejsc) i 73853 (6 miejsc).
 * Ceny PL: AsiaAuto_Price::calculateFromCny(), kurs ze snapshotu 07.09.2026.
 *
 * Użycie:
 *   wp eval-file scripts/hub-rox-adamas-2026-09-07.php podglad <plik.html>
 *   wp eval-file scripts/hub-rox-adamas-2026-09-07.php apply
 */

$tryb = (string) ($args[0] ?? 'podglad');
$out  = (string) ($args[1] ?? '');

$H = [
    'make_slug'  => 'rox',
    'make_name'  => 'ROX',
    'serie_name' => 'Adamas',
    'serie_slug' => 'adamas',
    'full_title' => 'ROX Adamas',
    'api_value'  => 'Jishi ADAMAS',
    'title'      => 'ROX Adamas cena w Polsce — od 347 000 zł | Prima-Auto',
    'desc'       => 'ROX Adamas — cena w Polsce od 347 000 zł. Terenowy SUV EREV: 350 kW, 6 lub 7 miejsc, zasięg łączny do 1405 km, zawieszenie pneumatyczne. Import przez Prima-Auto.',
    'h1_suffix'  => 'cena w Polsce i import z Chin',
    'focus_kw'   => 'ROX Adamas',
    'lead'       => 'ROX Adamas to następca modelu ROX 01 — duży terenowy SUV z napędem hybrydowym szeregowym, 350 kW mocy i sześcioma lub siedmioma miejscami. W Chinach kosztuje od 349 900 juanów, co po sprowadzeniu do Polski przez Prima-Auto daje orientacyjnie od 347 000 zł z cłem, VAT-em i homologacją. Marka nie ma w Polsce salonów, więc jedyną drogą jest import indywidualny.',
    'wiki'       => <<<'HTML'
<p>ROX Adamas trafił na rynek chiński pod koniec grudnia 2025 jako następca modelu ROX 01, od którego jest większy i mocniejszy. To terenowy SUV z napędem hybrydowym szeregowym, oferowany w układzie sześcio- i siedmiomiejscowym. Nazwa pochodzi od greckiego słowa oznaczającego „niepokonany".</p>
{{LISTINGS_BAR}}
<h2>Ile kosztuje ROX Adamas w Polsce</h2>
<p>Chińskie ceny katalogowe to 349 900 juanów za wersję siedmiomiejscową i 359 900 za sześciomiejscową odmianę reprezentacyjną. Po przeliczeniu przez nasz kalkulator importu — z transportem morskim, cłem 10%, VAT-em 23%, homologacją i obsługą — wychodzi orientacyjnie <strong>od 347 000 do 356 000 zł</strong>. Hybrydy plug-in o pojemności do 2000 cm³ są w Polsce zwolnione z akcyzy, więc ten koszt nie obciąża sprowadzenia.</p>
<h2>Napęd: 350 kW i generator zamiast ładowarki</h2>
<p>Adamas ma dwa silniki elektryczne — 150 kW z przodu i 200 kW z tyłu, razem 350 kW, czyli 476 KM, i 740 Nm. Napęd na cztery koła jest realizowany elektrycznie, bez wału. Do stu kilometrów na godzinę auto rozpędza się w 5,5 sekundy, co przy masie 2715 kg jest wynikiem osobowego sedana. Prędkość maksymalna to 190 km/h.</p>
<h2>Zasięg i tankowanie</h2>
<p>Bateria niklowo-manganowo-kobaltowa ma 44,5 kWh i wystarcza na 215 km według chińskiej procedury CLTC lub 180 km według surowszej WLTC — czyli na codzienne dojazdy bez uruchamiania silnika. Dalej pracuje generator: doładowywana benzyną jednostka o mocy 150 KM i zbiornik 70 litrów, co daje łączny zasięg deklarowany na 1405 km. Przy pustej baterii auto spala 6,76 l/100 km, co dla SUV-a tej wielkości jest wynikiem dobrym.</p>
<h2>Wymiary, miejsca i zawieszenie</h2>
<p>Nadwozie ma 5050 mm długości, 1985 mm szerokości i 1856 mm wysokości przy rozstawie osi 3010 mm. Wersja siedmiomiejscowa ma trzy rzędy siedzeń, sześciomiejscowa stawia w drugim rzędzie dwa osobne fotele. Standardem jest zawieszenie pneumatyczne, które pozwala regulować prześwit — przy aucie o terenowych ambicjach to nie gadżet, tylko realna zmiana możliwości poza asfaltem.</p>
<h2>Ładowanie</h2>
<p>Na szybkiej ładowarce prądu stałego bateria uzupełnia się od 20 do 80 procent w około 27 minut, na wolnym ładowaniu pełny cykl zajmuje 6,5 godziny. W praktyce ładowarka jest wygodą, a nie warunkiem jazdy — pusty akumulator oznacza tylko, że auto zaczyna zużywać benzynę.</p>
<h2>Czym Adamas różni się od ROX 01</h2>
<p>ROX 01 to wcześniejszy i mniejszy model tej samej marki, dostępny u nas w ofercie. Adamas jest od niego dłuższy, mocniejszy i wyraźnie lepiej wyposażony, ma też układ sześcio- lub siedmiomiejscowy zamiast pięciomiejscowego. Jeśli szukasz czegoś tańszego i dostępnego od ręki, zobacz nasze aktualne oferty ROX 01.</p>
<h2>Kiedy Adamas będzie dostępny w Polsce</h2>
<p>Model jest w sprzedaży w Chinach od grudnia 2025, więc egzemplarze pojawiają się już na tamtejszym rynku wtórnym. Sprowadzamy auta na zamówienie: ustalamy wersję i kolor, znajdujemy sztukę u sprawdzonego sprzedawcy, pokazujemy zdjęcia i dokumenty, organizujemy transport morski, odprawę celną i homologację. Od potwierdzenia zamówienia do odbioru zarejestrowanego auta mija zwykle 10–14 tygodni.</p>
HTML,
    'faq'        => [
        ['q' => 'Ile kosztuje ROX Adamas w Polsce?', 'a' => 'Orientacyjnie od 347 000 zł za wersję siedmiomiejscową do 356 000 zł za sześciomiejscową. Kwoty zawierają transport z Chin, cło, VAT, homologację i obsługę Prima-Auto. Ostateczna cena zależy od kursu walut i konkretnego egzemplarza.'],
        ['q' => 'Jaki zasięg ma ROX Adamas?', 'a' => 'Na samym prądzie 215 km według chińskiej procedury CLTC i 180 km według WLTC. Po rozładowaniu baterii pracuje generator spalinowy, a łączny zasięg producent deklaruje na 1405 km przy pełnym baku 70 litrów.'],
        ['q' => 'Ile miejsc ma ROX Adamas?', 'a' => 'Dostępne są dwie wersje: siedmiomiejscowa z trzema rzędami siedzeń oraz sześciomiejscowa, w której drugi rząd tworzą dwa osobne fotele.'],
        ['q' => 'Czym ROX Adamas różni się od ROX 01?', 'a' => 'Adamas jest następcą modelu 01 — jest dłuższy, mocniejszy, ma 350 kW zamiast słabszego układu, zawieszenie pneumatyczne w standardzie i sześć lub siedem miejsc zamiast pięciu.'],
        ['q' => 'Czy ROX Adamas jest dostępny w polskich salonach?', 'a' => 'Nie. Marka ROX nie ma w Polsce oficjalnej dystrybucji ani serwisu, więc każdy egzemplarz pochodzi z importu indywidualnego z Chin.'],
    ],
];

$bledy = [];
if (mb_strlen($H['title']) > 65) $bledy[] = 'title za dlugi: ' . mb_strlen($H['title']);
if (mb_strlen($H['desc']) > 170)  $bledy[] = 'desc za dlugi: ' . mb_strlen($H['desc']);
$json = wp_json_encode($H['faq'], JSON_UNESCAPED_UNICODE);
if (json_decode($json, true) === null) $bledy[] = 'FAQ nie parsuje sie';
foreach (['„', '”', '“', '’'] as $sq) if (strpos($json, $sq) !== false) $bledy[] = "smart quote $sq w FAQ";
if (!preg_match('/[ąćęłńóśźż]/u', $json)) $bledy[] = 'FAQ bez diakrytyk';
if (strpos($H['wiki'], '{{LISTINGS_BAR}}') === false) $bledy[] = 'brak LISTINGS_BAR';
$h2 = substr_count($H['wiki'], '<h2>');
if ($h2 !== 7) $bledy[] = "wiki ma $h2 naglowkow H2 (oczekiwane 7)";

echo "=== WALIDACJA ===\n" . ($bledy ? implode("\n", $bledy) . "\n" : "OK\n");
printf("title %d zn. | desc %d zn. | wiki %d zn. | FAQ %d\n",
    mb_strlen($H['title']), mb_strlen($H['desc']), mb_strlen(wp_strip_all_tags($H['wiki'])), count($H['faq']));

if ($tryb === 'podglad') {
    if ($out === '') { echo "Podaj plik wyjsciowy.\n"; return; }
    $x = '<!doctype html><meta charset="utf-8"><title>ROX Adamas — podglad</title>'
       . '<style>body{font:16px/1.6 system-ui;max-width:820px;margin:2rem auto;padding:0 1rem}'
       . 'h2{font-size:1.15rem;margin-top:1.5rem}.meta{background:#f6f6f6;padding:.8rem 1rem;border-radius:6px;font-size:.9rem}'
       . '.lead{background:#eef5ff;padding:.7rem 1rem;border-radius:6px}table{border-collapse:collapse;width:100%}'
       . 'td,th{border:1px solid #ddd;padding:.35rem .5rem;text-align:left;font-size:.9rem}</style>'
       . '<h1>' . esc_html($H['full_title']) . '</h1>'
       . '<div class="meta"><b>URL:</b> /samochody/rox/adamas/<br><b>Title:</b> ' . esc_html($H['title'])
       . '<br><b>Description:</b> ' . esc_html($H['desc']) . '</div>'
       . '<h3>Lead</h3><div class="lead">' . esc_html($H['lead']) . '</div><h3>Tresc</h3>'
       . str_replace('{{LISTINGS_BAR}}', '<p style="background:#fff8e1;padding:.5rem 1rem;border-radius:6px;font-size:.85rem">[pasek ofert — dzis pusty]</p>', $H['wiki'])
       . '<h3>FAQ</h3><table><tr><th>Pytanie</th><th>Odpowiedz</th></tr>';
    foreach ($H['faq'] as $f) $x .= '<tr><td>' . esc_html($f['q']) . '</td><td>' . esc_html($f['a']) . '</td></tr>';
    file_put_contents($out, $x . '</table>');
    echo "Podglad: $out\n";
    return;
}
if ($tryb !== 'apply') { echo "Uzyj: podglad <plik.html> albo apply\n"; return; }
if ($bledy) { echo "STOP — walidacja nie przeszla.\n"; return; }

$make = get_term_by('slug', $H['make_slug'], 'make');
if (!$make) { echo "BRAK marki ROX — przerywam.\n"; return; }
$serie = get_terms(['taxonomy' => 'serie', 'slug' => $H['serie_slug'], 'parent' => $make->term_id, 'hide_empty' => false]);
$serie = (!is_wp_error($serie) && $serie) ? $serie[0] : null;
if (!$serie) {
    $r = wp_insert_term($H['serie_name'], 'serie', ['slug' => $H['serie_slug'], 'parent' => $make->term_id]);
    if (is_wp_error($r)) { echo 'BLAD: ' . $r->get_error_message() . "\n"; return; }
    $serie = get_term($r['term_id'], 'serie');
    printf("utworzony hub %s (term %d)\n", $H['serie_name'], $serie->term_id);
}
$tid = (int) $serie->term_id;
update_term_meta($tid, '_serie_full_title', $H['full_title']);
update_term_meta($tid, '_serie_api_value', $H['api_value']);
update_term_meta($tid, '_asiaauto_primary_make_slug', $H['make_slug']);
update_term_meta($tid, '_asiaauto_lead', $H['lead']);
update_term_meta($tid, '_asiaauto_h1_suffix', $H['h1_suffix']);
update_term_meta($tid, '_asiaauto_pl_availability', 'import_only');
update_term_meta($tid, 'asiaauto_wiki_body', $H['wiki']);
update_term_meta($tid, 'asiaauto_faq_json', wp_json_encode($H['faq'], JSON_UNESCAPED_UNICODE));
update_term_meta($tid, 'asiaauto_seo_desc', $H['desc']);
update_term_meta($tid, 'rank_math_title', $H['title']);
update_term_meta($tid, 'rank_math_description', $H['desc']);
update_term_meta($tid, 'rank_math_focus_keyword', $H['focus_kw']);
update_term_meta($tid, 'rank_math_robots', ['index']);
update_term_meta($tid, '_asiaauto_seo_rework', 'v1-premiery-2026-09-07');
// bez ofert nie ma z czego liczyc ceny w tytule — zamrazamy do pierwszego egzemplarza
update_term_meta($tid, '_asiaauto_skip_title_regen', 1);

$wl = array_map('intval', (array) get_option('asiaauto_hub_index_whitelist', []));
if (!in_array($tid, $wl, true)) { $wl[] = $tid; update_option('asiaauto_hub_index_whitelist', $wl); }
printf("zapisane meta dla term %d (/samochody/%s/%s/), whitelist: %s\n",
    $tid, $H['make_slug'], $H['serie_slug'], implode(',', $wl));
