<?php
/**
 * huby-stelato-2026-09-07.php — treść huba marki Stelato oraz hubów modeli S9 i S9T.
 *
 * Powstały automatycznie przy imporcie 58 ofert (07.09) i jak każdy hub tworzony przez
 * serie-guard zostały bez treści — mają tylko `_asiaauto_primary_make_slug`.
 *
 * Fakty z BAZY (58 zaimportowanych ofert), nie z szacunków:
 *   S9  — 35 ofert, 225 000–294 000 PLN (mediana 269 000), sedan 5160×2005×1486, rozstaw 3050
 *   S9T — 23 oferty, 254 000–327 000 PLN (mediana 286 000), kombi, bagażnik 729–1677 l
 * Specyfikacja: `_asiaauto_extra_prep` ofert + katalog Autohome (specid 73860).
 *
 * `rank_math_title` i `rank_math_description` zostawiamy generatorowi
 * (`AsiaAuto_HubTitleGenerator`) — huby mają oferty, więc cena w tytule ma się odświeżać.
 *
 * Użycie:
 *   wp eval-file scripts/huby-stelato-2026-09-07.php podglad <plik.html>
 *   wp eval-file scripts/huby-stelato-2026-09-07.php apply
 */

$tryb = (string) ($args[0] ?? 'podglad');
$out  = (string) ($args[1] ?? '');

$HUBY = [];

$HUBY['marka'] = [
    'term_id'   => 5630,
    'tax'       => 'make',
    'naglowek'  => 'Hub MARKI Stelato — /samochody/stelato/',
    'h1_suffix' => '— ceny w Polsce i import z Chin',
    'origin'    => 'chinese',
    'lead'      => 'Stelato to marka stworzona wspólnie przez koncern BAIC i Huaweia — po chińsku 享界. W Polsce nie ma jej salonów, więc auta sprowadzamy z Chin na zamówienie. W gamie są dwa modele zbudowane na tej samej płycie: sedan S9 i kombi S9T, oba dostępne z napędem czysto elektrycznym albo hybrydowym szeregowym.',
    'wiki'      => <<<'HTML'
<p>Stelato (chińskie 享界) należy do rodziny marek, które Huawei tworzy razem z chińskimi producentami samochodów — w tym wypadku partnerem jest pekiński koncern BAIC. Huawei odpowiada za asystenta jazdy, system pokładowy i elektronikę, BAIC za konstrukcję i produkcję. Marka celuje w segment premium: to bezpośrednia odpowiedź na BMW serii 5 i Mercedesa klasy E.</p>
{{LISTINGS_BAR}}
<h2>Modele Stelato</h2>
<ul>
<li><strong>S9</strong> — duży sedan o długości 5160 mm z rozstawem osi 3050 mm. Do wyboru napęd hybrydowy szeregowy z baterią 53,4 kWh albo w pełni elektryczny ze 100 kWh i napędem na cztery koła.</li>
<li><strong>S9T</strong> — kombi na tej samej płycie, z bagażnikiem o pojemności od 729 do 1677 litrów. Ta sama gama napędów, nieco wyższa cena.</li>
</ul>
<h2>Ile kosztuje Stelato w Polsce</h2>
<p>Nasze bieżące oferty i widełki cenowe widzisz nad tym opisem. Ceny obejmują transport morski z Chin, cło, VAT, homologację i naszą obsługę — to kwota, za którą odbierasz auto zarejestrowane w Polsce. Dla porównania: w Chinach nowy S9T kosztuje katalogowo 349 800 juanów, a my sprowadzamy egzemplarze kilkunastomiesięczne z niewielkim przebiegiem, co daje realną oszczędność wobec ceny salonowej.</p>
<h2>Elektronika Huawei</h2>
<p>To główny powód, dla którego kupuje się Stelato. Auta mają asystenta jazdy Huawei z skanerem LiDAR — w wersjach podstawowych o rozdzielczości 192 linii, w topowych 896 linii — oraz system pokładowy HarmonyOS, ten sam, który działa w telefonach i tabletach marki. W Europie taki zestaw czujników spotyka się w autach z zupełnie innej półki cenowej.</p>
<h2>Napęd: elektryczny albo z generatorem</h2>
<p>Wersje EREV mają baterię 53,4 kWh i przejeżdżają na prądzie ponad 350 km według chińskiej procedury CLTC, a po jej rozładowaniu uruchamia się generator — silnik 1.5 o mocy 160 KM, który nie napędza kół, tylko ładuje akumulator. Wersje BEV mają baterię 100 kWh i nie mają silnika spalinowego. Przy polskiej sieci ładowarek odmiana EREV jest zwykle wygodniejsza w trasie.</p>
<h2>Czy Stelato jest dostępne w Europie</h2>
<p>Nie. Marka sprzedaje wyłącznie w Chinach i nie ogłosiła planów wejścia do Europy, więc nie ma tu salonów ani autoryzowanego serwisu. Obsługę serwisową organizujemy przez warsztaty specjalizujące się w samochodach chińskich, a części sprowadzamy razem z autem lub na zamówienie.</p>
<h2>Jak wygląda sprowadzenie</h2>
<p>Wybierasz model i wersję, my znajdujemy egzemplarz u sprawdzonego chińskiego sprzedawcy i pokazujemy zdjęcia oraz dokumenty przed zakupem. Dalej transport morski, odprawa celna, homologacja i rejestracja. Od potwierdzenia zamówienia do odbioru mija zwykle 10–14 tygodni.</p>
HTML,
    'faq'       => [
        ['q' => 'Czym jest marka Stelato?', 'a' => 'To marka samochodów premium stworzona wspólnie przez chiński koncern BAIC i Huaweia, po chińsku 享界. Huawei odpowiada za asystenta jazdy i system pokładowy, BAIC za konstrukcję i produkcję.'],
        ['q' => 'Jakie modele ma Stelato?', 'a' => 'Dwa: sedan S9 i kombi S9T, zbudowane na tej samej płycie. Oba są dostępne z napędem hybrydowym szeregowym oraz w wersji czysto elektrycznej.'],
        ['q' => 'Ile kosztuje Stelato w Polsce?', 'a' => 'Aktualne ceny naszych egzemplarzy widoczne są na górze tej strony. Kwota zawiera transport z Chin, cło, VAT, homologację i obsługę Prima-Auto, czyli auto odbierasz zarejestrowane i gotowe do jazdy.'],
        ['q' => 'Czy Stelato można kupić w polskim salonie?', 'a' => 'Nie. Marka sprzedaje wyłącznie na rynku chińskim i nie ma w Europie sieci dealerskiej, więc każdy egzemplarz w Polsce pochodzi z importu indywidualnego.'],
        ['q' => 'Jak wygląda serwis Stelato w Polsce?', 'a' => 'Marka nie ma tu autoryzowanego serwisu. Obsługę organizujemy przez warsztaty specjalizujące się w autach chińskich, a części zamawiamy razem z autem lub osobno z Chin.'],
    ],
];

$HUBY['s9'] = [
    'term_id'   => 5632,
    'tax'       => 'serie',
    'naglowek'  => 'Hub MODELU S9 — /samochody/stelato/s9/',
    'full_title'=> 'Stelato S9',
    'h1_suffix' => 'cena w Polsce i import z Chin',
    'lead'      => 'Stelato S9 to duży sedan marki BAIC i Huawei, konkurent BMW serii 5 i Mercedesa klasy E. Mamy go w ofercie w cenach od około 225 000 zł, w wersjach hybrydowych szeregowych i w pełni elektrycznych — aktualne egzemplarze widzisz nad tym opisem. Auto nie jest sprzedawane w Europie, więc trafia do Polski wyłącznie z importu.',
    'wiki'      => <<<'HTML'
<p>Stelato S9 to flagowy sedan marki powołanej przez BAIC i Huaweia. Ma 5160 mm długości i rozstaw osi 3050 mm, czyli wymiary limuzyny wyższej klasy — więcej niż BMW serii 5 i blisko klasy S. W Chinach jest jednym z najczęściej wybieranych aut służbowych w segmencie premium.</p>
{{LISTINGS_BAR}}
<h2>Ile kosztuje Stelato S9 w Polsce</h2>
<p>W naszej ofercie ceny zaczynają się od około 225 000 zł i sięgają blisko 300 000 zł za wersje topowe — dokładne kwoty przy konkretnych egzemplarzach znajdziesz nad tym opisem. Każda cena zawiera transport morski, cło, VAT, homologację i naszą obsługę, więc auto odbierasz zarejestrowane w Polsce. Sprowadzamy egzemplarze kilkunastomiesięczne, zwykle z przebiegiem poniżej 20 000 km.</p>
<h2>Dwa napędy do wyboru</h2>
<ul>
<li><strong>EREV</strong> — bateria 53,4 kWh daje 365 km zasięgu według chińskiej procedury CLTC, a po jej rozładowaniu pracuje generator: silnik 1.5 o mocy 160 KM. Do stu kilometrów na godzinę auto rozpędza się w 7,3 s. To wersja dominująca w naszej ofercie.</li>
<li><strong>BEV</strong> — w pełni elektryczna, z baterią 100 kWh, dostępna także z napędem na cztery koła w odmianie Ultra Flagship.</li>
</ul>
<p>Prędkość maksymalna obu wersji to 202 km/h, a ładowanie baterii na szybkiej stacji zajmuje około 15 minut.</p>
<h2>Wymiary i wnętrze</h2>
<p>Nadwozie mierzy 5160 mm długości, 2005 mm szerokości i 1486 mm wysokości. Rozstaw osi 3050 mm oznacza bardzo dużo miejsca na tylnej kanapie — S9 projektowano z myślą o pasażerze z tyłu, nie tylko o kierowcy. Masa własna to 2350 kg.</p>
<h2>Asystent jazdy i LiDAR</h2>
<p>Wszystkie sprowadzane przez nas egzemplarze mają skaner LiDAR: w wersjach Max i Ultra o rozdzielczości 192 linii, w topowych odmianach 896 linii. Współpracuje z asystentem jazdy Huawei, który prowadzi auto w ruchu autostradowym i miejskim w granicach poziomu L2 — kierowca cały czas odpowiada za prowadzenie. System pokładowy to HarmonyOS.</p>
<h2>Wersje w naszej ofercie</h2>
<p>Najczęściej spotykane odmiany to EREV Ultra i EREV Ultra Long Range z tylnym napędem, EREV Max Plus oraz elektryczne BEV Max RWD i BEV Ultra 4WD Flagship. Różnią się zasięgiem, mocą i rozdzielczością lidaru; przy każdej ofercie podajemy pełną specyfikację techniczną konkretnego egzemplarza.</p>
<h2>S9 czy S9T</h2>
<p>S9 to sedan, S9T to kombi na tej samej płycie, z bagażnikiem od 729 do 1677 litrów i ceną wyższą o kilkadziesiąt tysięcy złotych. Jeśli potrzebujesz przestrzeni bagażowej, zobacz nasze oferty S9T; jeśli wolisz klasyczną sylwetkę limuzyny, zostań przy S9.</p>
HTML,
    'faq'       => [
        ['q' => 'Ile kosztuje Stelato S9 w Polsce?', 'a' => 'W naszej ofercie ceny zaczynają się od około 225 000 zł i sięgają blisko 300 000 zł za wersje topowe. Kwota zawiera transport z Chin, cło, VAT, homologację i obsługę Prima-Auto.'],
        ['q' => 'Jaki zasięg ma Stelato S9?', 'a' => 'Wersja hybrydowa szeregowa przejeżdża na samym prądzie 365 km według chińskiej procedury CLTC, a dalej pracuje generator spalinowy 1.5 o mocy 160 KM. Wersja w pełni elektryczna ma baterię 100 kWh.'],
        ['q' => 'Czy Stelato S9 ma odpowiednik w Europie?', 'a' => 'Rozmiarami i pozycjonowaniem odpowiada BMW serii 5 i Mercedesowi klasy E, ale przy rozstawie osi 3050 mm oferuje z tyłu więcej miejsca niż oba. W Europie nie jest sprzedawany.'],
        ['q' => 'Czym różni się wersja EREV od BEV?', 'a' => 'EREV ma baterię 53,4 kWh i generator spalinowy, który ładuje ją w trasie, więc nie jest zależna od ładowarek. BEV ma baterię 100 kWh i nie ma silnika spalinowego.'],
        ['q' => 'Czy S9 ma asystenta jazdy Huawei?', 'a' => 'Tak. Wszystkie sprowadzane egzemplarze mają skaner LiDAR o rozdzielczości 192 lub 896 linii i asystenta jazdy Huawei działającego w poziomie L2, a także system pokładowy HarmonyOS.'],
    ],
];

$HUBY['s9t'] = [
    'term_id'   => 6274,
    'tax'       => 'serie',
    'naglowek'  => 'Hub MODELU S9T — /samochody/stelato/s9t/',
    'full_title'=> 'Stelato S9T',
    'h1_suffix' => 'cena w Polsce i import z Chin',
    'lead'      => 'Stelato S9T to kombi zbudowane na płycie sedana S9 — z bagażnikiem od 729 do 1677 litrów i tym samym wyposażeniem elektronicznym Huawei. W naszej ofercie kosztuje od około 254 000 zł, w wersjach hybrydowych szeregowych i elektrycznych. W Europie nie jest sprzedawany, więc do Polski trafia wyłącznie z importu.',
    'wiki'      => <<<'HTML'
<p>S9T to odmiana kombi flagowego sedana Stelato S9. Zachowuje jego długość 5160 mm i rozstaw osi 3050 mm, ale dokłada wysoki, płaski bagażnik o pojemności od 729 do 1677 litrów po złożeniu oparć. W chińskiej ofercie należy do nielicznych dużych kombi z napędem elektrycznym i hybrydowym szeregowym.</p>
{{LISTINGS_BAR}}
<h2>Ile kosztuje Stelato S9T w Polsce</h2>
<p>Nasze ceny zaczynają się od około 254 000 zł i dochodzą do 327 000 zł za najlepiej wyposażone egzemplarze — aktualne kwoty widzisz nad tym opisem. Nowy S9T kosztuje w Chinach katalogowo 349 800 juanów, a my sprowadzamy sztuki kilkunastomiesięczne z niskim przebiegiem. Cena zawiera transport, cło, VAT, homologację i obsługę, więc odbierasz auto zarejestrowane w Polsce.</p>
<h2>Bagażnik i przestrzeń</h2>
<p>Pojemność bagażnika to 729 litrów w układzie pięcioosobowym i 1677 litrów po złożeniu tylnej kanapy. Klapa otwierana elektrycznie ma pamięć położenia, co przy wysokim podnoszeniu w garażu podziemnym bywa istotniejsze, niż się wydaje. Rozstaw osi 3050 mm daje z tyłu miejsce na nogi jak w limuzynie.</p>
<h2>Napęd i zasięg</h2>
<p>Podobnie jak sedan, S9T występuje w dwóch odmianach. Hybrydowa szeregowa ma baterię 53,4 kWh i przejeżdża na prądzie 354 km według procedury CLTC, a dalej korzysta z generatora — silnika 1.5 o mocy 160 KM. Wersja w pełni elektryczna ma większy akumulator i jest dostępna także z napędem na cztery koła. Przyspieszenie do setki zajmuje 7,04 s, prędkość maksymalna to 202 km/h.</p>
<h2>Wymiary</h2>
<p>Nadwozie ma 5160 mm długości, 2005 mm szerokości i 1492 mm wysokości przy rozstawie osi 3050 mm. Masa własna to 2388 kg, czyli o niecałe 40 kg więcej niż sedan — nadwozie kombi kosztuje tu zaskakująco mało dodatkowej masy.</p>
<h2>Elektronika Huawei</h2>
<p>S9T ma ten sam zestaw co sedan: skaner LiDAR o rozdzielczości 192 linii w wersjach Max i Ultra lub 896 linii w topowych odmianach z napędem na cztery koła, asystenta jazdy Huawei w poziomie L2 i system pokładowy HarmonyOS. To wyposażenie, którego w europejskim kombi w tej cenie nie znajdziesz.</p>
<h2>Wersje w naszej ofercie</h2>
<p>W magazynie mamy między innymi EREV Ultra Long Range i EREV Max z lidarem 192-liniowym, EREV Max Long Range oraz elektryczne BEV RWD Max i BEV 4WD Max z lidarem 896-liniowym. Przy każdej ofercie podajemy pełną specyfikację danego egzemplarza, łącznie z przebiegiem i rocznikiem.</p>
<h2>Dla kogo jest S9T</h2>
<p>Dla kogoś, kto potrzebuje przestrzeni kombi, ale nie chce SUV-a — i kogo interesuje elektronika, jakiej europejscy producenci w tej klasie jeszcze nie oferują. Jeśli bagażnik nie jest priorytetem, tańszym wyborem jest sedan S9.</p>
HTML,
    'faq'       => [
        ['q' => 'Ile kosztuje Stelato S9T w Polsce?', 'a' => 'W naszej ofercie od około 254 000 zł do 327 000 zł, zależnie od wersji i przebiegu. Kwota zawiera transport z Chin, cło, VAT, homologację i obsługę Prima-Auto.'],
        ['q' => 'Jaki bagażnik ma Stelato S9T?', 'a' => 'Od 729 litrów w układzie pięcioosobowym do 1677 litrów po złożeniu tylnej kanapy. Klapa otwierana jest elektrycznie i ma pamięć położenia.'],
        ['q' => 'Czym S9T różni się od S9?', 'a' => 'S9T to nadwozie kombi na tej samej płycie co sedan S9 — ta sama długość 5160 mm i rozstaw osi 3050 mm, ale znacznie większy bagażnik i masa wyższa o niecałe 40 kg.'],
        ['q' => 'Jaki zasięg ma Stelato S9T?', 'a' => 'Wersja hybrydowa szeregowa przejeżdża na prądzie 354 km według chińskiej procedury CLTC, a po rozładowaniu baterii pracuje generator 1.5 o mocy 160 KM. Wersja elektryczna ma większą baterię.'],
        ['q' => 'Czy Stelato S9T jest dostępne w Europie?', 'a' => 'Nie. Marka sprzedaje wyłącznie na rynku chińskim, więc S9T trafia do Polski jedynie drogą importu indywidualnego.'],
    ],
];

// ---------------------------------------------------------------- walidacja
$bledy = [];
foreach ($HUBY as $k => $h) {
    $json = wp_json_encode($h['faq'], JSON_UNESCAPED_UNICODE);
    if (json_decode($json, true) === null) $bledy[] = "$k: FAQ nie parsuje sie";
    foreach (['„', '”', '“', '’'] as $sq) if (strpos($json, $sq) !== false) $bledy[] = "$k: smart quote";
    if (!preg_match('/[ąćęłńóśźż]/u', $json)) $bledy[] = "$k: FAQ bez diakrytyk";
    if (strpos($h['wiki'], '{{LISTINGS_BAR}}') === false) $bledy[] = "$k: brak LISTINGS_BAR";
    if (!get_term($h['term_id']) instanceof WP_Term) $bledy[] = "$k: term {$h['term_id']} nie istnieje";
}
echo "=== WALIDACJA ===\n" . ($bledy ? implode("\n", $bledy) . "\n" : "OK\n");
foreach ($HUBY as $k => $h) printf("%-6s wiki %4d zn. | FAQ %d | H2 %d\n", $k,
    mb_strlen(wp_strip_all_tags($h['wiki'])), count($h['faq']), substr_count($h['wiki'], '<h2>'));

if ($tryb === 'podglad') {
    if ($out === '') { echo "Podaj plik wyjsciowy.\n"; return; }
    $x = '<!doctype html><meta charset="utf-8"><title>Stelato — podglad</title>'
       . '<style>body{font:16px/1.6 system-ui;max-width:820px;margin:2rem auto;padding:0 1rem}'
       . 'h2{font-size:1.15rem;margin-top:1.5rem}.box{border:1px solid #ddd;border-radius:10px;padding:1rem 1.4rem;margin:1.6rem 0}'
       . '.lead{background:#eef5ff;padding:.7rem 1rem;border-radius:6px}table{border-collapse:collapse;width:100%}'
       . 'td,th{border:1px solid #ddd;padding:.35rem .5rem;text-align:left;font-size:.9rem}</style>';
    foreach ($HUBY as $h) {
        $x .= '<div class="box"><h1>' . esc_html($h['naglowek']) . '</h1>'
            . '<p><i>Title i description generuje cron z aktualnej ceny i liczby ofert.</i></p>'
            . '<h3>Lead</h3><div class="lead">' . esc_html($h['lead']) . '</div><h3>Tresc</h3>'
            . str_replace('{{LISTINGS_BAR}}', '<p style="background:#fff8e1;padding:.5rem 1rem;border-radius:6px;font-size:.85rem">[pasek ofert]</p>', $h['wiki'])
            . '<h3>FAQ</h3><table><tr><th>Pytanie</th><th>Odpowiedz</th></tr>';
        foreach ($h['faq'] as $f) $x .= '<tr><td>' . esc_html($f['q']) . '</td><td>' . esc_html($f['a']) . '</td></tr>';
        $x .= '</table></div>';
    }
    file_put_contents($out, $x);
    echo "Podglad: $out\n";
    return;
}
if ($tryb !== 'apply') { echo "Uzyj: podglad <plik.html> albo apply\n"; return; }
if ($bledy) { echo "STOP — walidacja nie przeszla.\n"; return; }

foreach ($HUBY as $k => $h) {
    $tid = (int) $h['term_id'];
    update_term_meta($tid, 'asiaauto_wiki_body', $h['wiki']);
    update_term_meta($tid, 'asiaauto_faq_json', wp_json_encode($h['faq'], JSON_UNESCAPED_UNICODE));
    update_term_meta($tid, '_asiaauto_lead', $h['lead']);
    update_term_meta($tid, '_asiaauto_h1_suffix', $h['h1_suffix']);
    update_term_meta($tid, '_asiaauto_pl_availability', 'import_only');
    update_term_meta($tid, '_asiaauto_seo_rework', 'v1-premiery-2026-09-07');
    if (!empty($h['full_title'])) update_term_meta($tid, '_serie_full_title', $h['full_title']);
    if (!empty($h['origin']))     update_term_meta($tid, '_asiaauto_brand_origin', $h['origin']);
    if ($h['tax'] === 'serie')    update_term_meta($tid, '_asiaauto_primary_make_slug', 'stelato');

    $r = ($h['tax'] === 'make')
        ? AsiaAuto_HubTitleGenerator::regenerateForMakeTerm($tid)
        : AsiaAuto_HubTitleGenerator::regenerateForTerm($tid);
    printf("%-6s term %d — tresc zapisana | title: %s\n", $k, $tid,
        is_array($r) && !empty($r['title']) ? $r['title'] : 'bez zmian');
}
echo "\nGotowe.\n";
