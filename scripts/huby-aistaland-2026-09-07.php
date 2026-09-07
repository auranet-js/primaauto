<?php
/**
 * huby-aistaland-2026-09-07.php — treść huba MARKI Aistaland i huba modelu GT7.
 *
 * Po co: marka powstała 07.09 jako rodzic dla GX7, a hub GT7 utworzył się sam przy imporcie
 * pierwszej oferty (post 467615) — oba zostały bez treści. Ten skrypt je uzupełnia.
 *
 * Czego tu NIE ma: `rank_math_title` i `rank_math_description`. Dla hubów Z OFERTAMI generuje
 * je `AsiaAuto_HubTitleGenerator` (cron dzienny `asiaauto_regen_hub_titles_daily`) razem
 * z aktualną ceną i liczbą sztuk — ręczne wpisanie zamroziłoby cenę.
 *
 * Dane: katalog Autohome specid 78000 (wersja z naszej oferty) + `_asiaauto_extra_prep` postu.
 *
 * Użycie:
 *   wp eval-file scripts/huby-aistaland-2026-09-07.php podglad <plik.html>
 *   wp eval-file scripts/huby-aistaland-2026-09-07.php apply
 */

$tryb = (string) ($args[0] ?? 'podglad');
$out  = (string) ($args[1] ?? '');

$MARKA = [
    'term_id'    => 7248,
    'nazwa'      => 'Aistaland',
    'h1_suffix'  => '— ceny w Polsce i import z Chin',
    'origin'     => 'chinese',
    'lead'       => 'Aistaland to marka powołana wspólnie przez chiński koncern GAC i Huaweia — po chińsku 启境. Sprowadzamy ją do Polski na zamówienie, bo nie ma w Europie ani jednego salonu. W gamie są dwa modele: elektryczne kombi GT7 z mocą 565 kW i SUV GX7 z napędem hybrydowym szeregowym, którego przedsprzedaż ruszyła we wrześniu 2026.',
    'wiki'       => <<<'HTML'
<p>Aistaland (chińskie 启境) to jedna z marek, które Huawei tworzy razem z chińskimi producentami samochodów — tu partnerem jest koncern GAC z Kantonu. Huawei nie buduje własnych aut, ale dostarcza w nich całą warstwę cyfrową: asystenta jazdy, system multimedialny i elektronikę pokładową. Marka zadebiutowała w 2026 roku i ma dziś dwa modele.</p>
{{LISTINGS_BAR}}
<h2>Modele Aistaland</h2>
<ul>
<li><strong>GT7</strong> — pięciodrzwiowe kombi z napędem elektrycznym, w topowej wersji trzysilnikowej 565 kW (768 KM) i 915 Nm, z przyspieszeniem do setki w 2,98 s. Bateria 102,8 kWh, architektura 800 V, ładowanie od 10 do 80 procent w niecałe 12 minut.</li>
<li><strong>GX7</strong> — duży SUV z napędem hybrydowym szeregowym, przedsprzedaż w Chinach ruszyła 4 września 2026. Ceny katalogowe od 249 900 do 311 900 juanów, zasięg łączny do 1400 km.</li>
</ul>
<h2>Co daje w tych autach Huawei</h2>
<p>W GT7 pracuje asystent Qiankun ADS 5 w wersji Ultra, oparty na dwóch skanerach LiDAR o rozdzielczości 896 linii, które wykrywają obiekty o odbiciu 10 procent z 250 metrów. Do tego dwanaście czujników ultradźwiękowych, pięć radarów milimetrowych i kamery dookolne. GX7 dostaje tę samą generację systemu, ale z czterema lidarami. To wyposażenie, które w Europie spotyka się w autach o dwa razy wyższej cenie.</p>
<h2>Ile kosztuje Aistaland w Polsce</h2>
<p>Ceny zależą od modelu i wersji. Nasza bieżąca oferta i widełki cenowe są widoczne nad tym opisem — cena zawiera transport z Chin, cło, VAT, homologację i naszą obsługę, więc jest to kwota, za którą odbierasz auto zarejestrowane w Polsce. Samochody elektryczne i hybrydy plug-in o pojemności do 2000 cm³ są zwolnione z akcyzy, co przy tej marce działa na korzyść kupującego.</p>
<h2>Czy Aistaland jest dostępny w Europie</h2>
<p>Nie. Marka sprzedaje wyłącznie na rynku chińskim i nie zapowiedziała wejścia do Europy, więc nie ma tu salonów ani autoryzowanego serwisu. Każdy egzemplarz w Polsce pochodzi z importu indywidualnego. Obsługę gwarancyjną i serwisową organizujemy przez warsztaty specjalizujące się w autach chińskich — części zamienne sprowadzamy razem z autem lub na zamówienie.</p>
<h2>Jak wygląda sprowadzenie</h2>
<p>Ustalamy model, wersję i kolor, znajdujemy egzemplarz u sprawdzonego chińskiego sprzedawcy i pokazujemy Ci zdjęcia oraz dokumenty przed zakupem. Potem transport morski, odprawa celna, homologacja i rejestracja — auto odbierasz gotowe do jazdy. Cały proces trwa zwykle od 10 do 14 tygodni.</p>
HTML,
    'faq'        => [
        ['q' => 'Czym jest marka Aistaland?', 'a' => 'To marka samochodów powołana wspólnie przez chiński koncern GAC i Huaweia, po chińsku 启境. Huawei odpowiada w tych autach za asystenta jazdy, system multimedialny i elektronikę, GAC za konstrukcję i produkcję. Marka zadebiutowała w 2026 roku.'],
        ['q' => 'Jakie modele ma Aistaland?', 'a' => 'Dwa: elektryczne kombi GT7 oraz SUV GX7 z napędem hybrydowym szeregowym, którego przedsprzedaż ruszyła we wrześniu 2026. Oba korzystają z systemów Huawei.'],
        ['q' => 'Czy Aistaland można kupić w polskim salonie?', 'a' => 'Nie. Marka sprzedaje wyłącznie w Chinach i nie ma w Europie sieci dealerskiej, więc każdy egzemplarz w Polsce pochodzi z importu indywidualnego.'],
        ['q' => 'Co zawiera cena auta Aistaland w Prima-Auto?', 'a' => 'Transport morski z Chin, cło, VAT, homologację, rejestrację i naszą obsługę. To kwota, za którą odbierasz auto gotowe do jazdy po polskich drogach, bez dodatkowych opłat.'],
        ['q' => 'Jak wygląda serwis Aistaland w Polsce?', 'a' => 'Marka nie ma tu autoryzowanego serwisu, więc obsługę organizujemy przez warsztaty specjalizujące się w samochodach chińskich. Części sprowadzamy razem z autem lub na zamówienie.'],
    ],
];

$GT7 = [
    'term_id'    => 7252,
    'full_title' => 'Aistaland GT7',
    'h1_suffix'  => 'cena w Polsce i import z Chin',
    'lead'       => 'Aistaland GT7 to elektryczne kombi marki GAC i Huawei — w wersji trzysilnikowej ma 565 kW, czyli 768 KM, i przyspiesza do setki w 2,98 sekundy. Sprowadzamy je do Polski na zamówienie, bo marka nie ma w Europie salonów; aktualne egzemplarze i ceny znajdziesz nad tym opisem. W Chinach ten sam samochód kosztuje katalogowo 299 900 juanów.',
    'wiki'       => <<<'HTML'
<p>GT7 jest pierwszym modelem marki Aistaland — wspólnego przedsięwzięcia koncernu GAC i Huaweia. To pięciodrzwiowe kombi o długości ponad pięciu metrów, z napędem czysto elektrycznym i osiągami, jakich w Europie szuka się w autach sportowych.</p>
{{LISTINGS_BAR}}
<h2>Osiągi i napęd</h2>
<p>Topowa wersja ma trzy silniki elektryczne o łącznej mocy 565 kW, czyli 768 KM, i 915 Nm momentu obrotowego, z napędem na cztery koła. Przyspieszenie od zera do stu kilometrów na godzinę zajmuje 2,98 sekundy, a prędkość maksymalna to 210 km/h. Dostępna jest też słabsza odmiana o mocy 200 kW.</p>
<h2>Bateria i ładowanie</h2>
<p>Akumulator niklowo-manganowo-kobaltowy ma pojemność 102,8 kWh i pracuje w architekturze 800 V. Producent deklaruje moc ładowania do 613 kW, co pozwala uzupełnić baterię od 10 do 80 procent w niecałe 12 minut — o ile trafisz na odpowiednio mocną ładowarkę, bo w Polsce takich stacji jest na razie niewiele.</p>
<h2>Wymiary i przestrzeń</h2>
<p>Nadwozie mierzy 5050 mm długości, 1980 mm szerokości i 1470 mm wysokości przy rozstawie osi 3000 mm. Niska sylwetka kombi przy trzymetrowym rozstawie osi oznacza bardzo dużo miejsca w drugim rzędzie i długi bagażnik. Masa własna to 2450 kg — typowa dla auta elektrycznego z tak dużą baterią.</p>
<h2>Systemy Huawei</h2>
<p>GT7 korzysta z asystenta Qiankun ADS 5 w wersji Ultra. Zestaw czujników obejmuje dwa skanery LiDAR o rozdzielczości 896 linii, wykrywające obiekty o odbiciu 10 procent z odległości 250 metrów, pięć radarów milimetrowych, dwanaście czujników ultradźwiękowych i kamery dookolne. System działa w poziomie L2, czyli kierowca cały czas odpowiada za prowadzenie.</p>
<h2>Ile kosztuje GT7 w Polsce</h2>
<p>Chińska cena katalogowa tej wersji to 299 900 juanów. Egzemplarze używane, z przebiegiem kilku tysięcy kilometrów, kosztują na miejscu wyraźnie mniej — i to je sprowadzamy. Aktualne ceny naszych ofert widzisz nad tym opisem; zawierają transport, cło, VAT, homologację i obsługę, więc odbierasz auto zarejestrowane w Polsce.</p>
<h2>Dla kogo jest GT7</h2>
<p>Dla kogoś, kto chce osiągów klasy supersamochodu w nadwoziu, które mieści rodzinę i bagaże, i nie zależy mu na europejskim znaczku na masce. Za pieniądze, jakich w salonie wystarcza na dobrze wyposażonego kombi z silnikiem 2.0, dostaje się tu 768 KM i wyposażenie elektroniczne z najwyższej półki.</p>
<h2>Czym GT7 różni się od GX7</h2>
<p>GT7 jest kombi i jest w pełni elektryczne — nie ma silnika spalinowego, więc na dłuższej trasie potrzebuje ładowarki. GX7 to SUV z napędem hybrydowym szeregowym: prąd napędza koła, a jednostka 1.5 pracuje jako generator, dzięki czemu łączny zasięg sięga 1400 km. GX7 wchodzi na rynek jesienią 2026.</p>
HTML,
    'faq'        => [
        ['q' => 'Ile kosztuje Aistaland GT7 w Polsce?', 'a' => 'Ceny naszych aktualnych egzemplarzy widoczne są na górze tej strony. Kwota zawiera transport z Chin, cło, VAT, homologację i obsługę Prima-Auto. Chińska cena katalogowa nowego GT7 w wersji trzysilnikowej to 299 900 juanów.'],
        ['q' => 'Jak szybki jest Aistaland GT7?', 'a' => 'Wersja trzysilnikowa ma 565 kW, czyli 768 KM, i 915 Nm. Przyspieszenie do stu kilometrów na godzinę zajmuje 2,98 sekundy, a prędkość maksymalna wynosi 210 km/h.'],
        ['q' => 'Jaką baterię ma GT7 i jak szybko się ładuje?', 'a' => 'Akumulator niklowo-manganowo-kobaltowy o pojemności 102,8 kWh, w architekturze 800 V. Przy mocy ładowania do 613 kW uzupełnienie od 10 do 80 procent zajmuje niecałe 12 minut na odpowiednio mocnej stacji.'],
        ['q' => 'Czy Aistaland GT7 jest dostępny w Europie?', 'a' => 'Nie. Marka Aistaland sprzedaje wyłącznie na rynku chińskim, więc do Polski GT7 trafia jedynie drogą importu indywidualnego.'],
        ['q' => 'Czym GT7 różni się od GX7?', 'a' => 'GT7 to elektryczne kombi bez silnika spalinowego. GX7 to SUV z napędem hybrydowym szeregowym, w którym jednostka 1.5 pracuje jako generator, dzięki czemu łączny zasięg sięga 1400 km.'],
    ],
];

// ---------------------------------------------------------------- walidacja

$bledy = [];
foreach ([['marka', $MARKA], ['gt7', $GT7]] as [$k, $h]) {
    $json = wp_json_encode($h['faq'], JSON_UNESCAPED_UNICODE);
    if (json_decode($json, true) === null) $bledy[] = "$k: FAQ nie parsuje sie";
    foreach (['„', '”', '“', '’'] as $sq) if (strpos($json, $sq) !== false) $bledy[] = "$k: smart quote w FAQ";
    if (!preg_match('/[ąćęłńóśźż]/u', $json)) $bledy[] = "$k: FAQ bez diakrytyk";
    if (strpos($h['wiki'], '{{LISTINGS_BAR}}') === false) $bledy[] = "$k: brak tokenu LISTINGS_BAR";
    $t = get_term($h['term_id']);
    if (!$t instanceof WP_Term) $bledy[] = "$k: term {$h['term_id']} nie istnieje";
}
echo "=== WALIDACJA ===\n" . ($bledy ? implode("\n", $bledy) . "\n" : "OK\n");
printf("marka: wiki %d zn., FAQ %d | gt7: wiki %d zn., FAQ %d\n",
    mb_strlen(wp_strip_all_tags($MARKA['wiki'])), count($MARKA['faq']),
    mb_strlen(wp_strip_all_tags($GT7['wiki'])), count($GT7['faq']));

if ($tryb === 'podglad') {
    if ($out === '') { echo "Podaj plik wyjsciowy.\n"; return; }
    $h = '<!doctype html><meta charset="utf-8"><title>Aistaland — podglad</title>'
       . '<style>body{font:16px/1.6 system-ui;max-width:820px;margin:2rem auto;padding:0 1rem}'
       . 'h2{font-size:1.15rem;margin-top:1.5rem}.box{border:1px solid #ddd;border-radius:10px;padding:1rem 1.4rem;margin:1.5rem 0}'
       . '.lead{background:#eef5ff;padding:.7rem 1rem;border-radius:6px}table{border-collapse:collapse;width:100%}'
       . 'td,th{border:1px solid #ddd;padding:.35rem .5rem;text-align:left;font-size:.9rem}</style>';
    foreach ([['Hub MARKI Aistaland — /samochody/aistaland/', $MARKA], ['Hub MODELU GT7 — /samochody/aistaland/gt7/', $GT7]] as [$naglowek, $d]) {
        $h .= '<div class="box"><h1>' . esc_html($naglowek) . '</h1>';
        $h .= '<p><i>Title i description generuje cron z aktualnej ceny i liczby ofert — nie ustawiamy ich tutaj.</i></p>';
        $h .= '<h3>Lead</h3><div class="lead">' . esc_html($d['lead']) . '</div><h3>Tresc</h3>';
        $h .= str_replace('{{LISTINGS_BAR}}', '<p style="background:#fff8e1;padding:.5rem 1rem;border-radius:6px;font-size:.85rem">[pasek z ofertami]</p>', $d['wiki']);
        $h .= '<h3>FAQ</h3><table><tr><th>Pytanie</th><th>Odpowiedz</th></tr>';
        foreach ($d['faq'] as $f) $h .= '<tr><td>' . esc_html($f['q']) . '</td><td>' . esc_html($f['a']) . '</td></tr>';
        $h .= '</table></div>';
    }
    file_put_contents($out, $h);
    echo "Podglad: $out\n";
    return;
}

if ($tryb !== 'apply') { echo "Uzyj: podglad <plik.html> albo apply\n"; return; }
if ($bledy) { echo "STOP — walidacja nie przeszla.\n"; return; }

// marka
update_term_meta($MARKA['term_id'], 'asiaauto_wiki_body', $MARKA['wiki']);
update_term_meta($MARKA['term_id'], 'asiaauto_faq_json', wp_json_encode($MARKA['faq'], JSON_UNESCAPED_UNICODE));
update_term_meta($MARKA['term_id'], '_asiaauto_lead', $MARKA['lead']);
update_term_meta($MARKA['term_id'], '_asiaauto_h1_suffix', $MARKA['h1_suffix']);
update_term_meta($MARKA['term_id'], '_asiaauto_brand_origin', $MARKA['origin']);
update_term_meta($MARKA['term_id'], '_asiaauto_pl_availability', 'import_only');
update_term_meta($MARKA['term_id'], '_asiaauto_seo_rework', 'v1-premiery-2026-09-07');
printf("marka Aistaland (term %d): tresc zapisana\n", $MARKA['term_id']);

// model GT7
update_term_meta($GT7['term_id'], 'asiaauto_wiki_body', $GT7['wiki']);
update_term_meta($GT7['term_id'], 'asiaauto_faq_json', wp_json_encode($GT7['faq'], JSON_UNESCAPED_UNICODE));
update_term_meta($GT7['term_id'], '_asiaauto_lead', $GT7['lead']);
update_term_meta($GT7['term_id'], '_asiaauto_h1_suffix', $GT7['h1_suffix']);
update_term_meta($GT7['term_id'], '_serie_full_title', $GT7['full_title']);
update_term_meta($GT7['term_id'], '_asiaauto_primary_make_slug', 'aistaland');
update_term_meta($GT7['term_id'], '_asiaauto_pl_availability', 'import_only');
update_term_meta($GT7['term_id'], '_asiaauto_seo_rework', 'v1-premiery-2026-09-07');
printf("hub GT7 (term %d): tresc zapisana\n", $GT7['term_id']);

// title/desc z aktualna cena — generatorem, nie recznie
foreach ([[$MARKA['term_id'], 'make'], [$GT7['term_id'], 'serie']] as [$tid, $tax]) {
    $r = ($tax === 'make')
        ? AsiaAuto_HubTitleGenerator::regenerateForMakeTerm($tid)
        : AsiaAuto_HubTitleGenerator::regenerateForTerm($tid);
    printf("  generator title (%s %d): %s\n", $tax, $tid, is_array($r) ? wp_json_encode($r, JSON_UNESCAPED_UNICODE) : var_export($r, true));
}
echo "\nGotowe.\n";
