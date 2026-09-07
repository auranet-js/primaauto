<?php
/**
 * hub-marki-changan-qiyuan-2026-09-07.php — treść huba marki Changan Qiyuan.
 *
 * Marka wydzielona z Changana 07.09.2026 (decyzja Janka), wzorem Chery Fulwin. Term 6528
 * istniał wcześniej jako pusty, po wydzieleniu ma 9 ofert w trzech modelach.
 *
 * Title i description zostawiamy `AsiaAuto_HubTitleGenerator` — hub ma oferty.
 *
 * Użycie: wp eval-file scripts/hub-marki-changan-qiyuan-2026-09-07.php [apply]
 */

$apply = (($args[0] ?? '') === 'apply');
$TID   = 6528;

$lead = 'Changan Qiyuan (po chińsku 长安启源) to elektryczna linia koncernu Changan — jednego z największych chińskich producentów samochodów. W Polsce marka nie ma dystrybucji, więc jej auta sprowadzamy na zamówienie. W naszej ofercie znajdziesz sedany A07 i A06 oraz kompaktowego SUV-a Q05, w cenach od około 134 000 zł.';

$wiki = <<<'HTML'
<p>Changan Qiyuan to marka, pod którą koncern Changan sprzedaje swoje samochody elektryczne i hybrydowe szeregowe. Powstała w 2023 roku i szybko stała się jedną z najlepiej sprzedających się linii w Chinach — głównie dlatego, że oferuje duże zasięgi w cenach, do jakich europejscy producenci nawet nie podchodzą.</p>
{{LISTINGS_BAR}}
<h2>Modele Changan Qiyuan w naszej ofercie</h2>
<ul>
<li><strong>A07</strong> — elektryczny sedan z baterią 77,94 kWh i zasięgiem 730 km według chińskiej procedury CLTC. Od około 148 000 zł.</li>
<li><strong>A06</strong> — kompaktowy sedan dostępny w wersji elektrycznej 510 km oraz hybrydowej szeregowej z lidarem. Od około 143 000 zł.</li>
<li><strong>Q05</strong> — miejski SUV elektryczny, bateria 51,9 kWh, zasięg 506 km CLTC. Od około 134 000 zł.</li>
</ul>
<p>Poza nimi marka ma w Chinach modele A05, E07 i Q07, a we wrześniu 2026 wprowadza <strong>Q06</strong> — elektrycznego SUV-a na architekturze 800 V z zasięgiem do 700 km.</p>
<h2>Ile kosztuje Changan Qiyuan w Polsce</h2>
<p>Aktualne ceny naszych egzemplarzy widzisz nad tym opisem. Każda kwota zawiera transport morski z Chin, cło, VAT, homologację i naszą obsługę, więc auto odbierasz zarejestrowane i gotowe do jazdy. Samochody w pełni elektryczne są w Polsce zwolnione z akcyzy, co realnie obniża koszt sprowadzenia — a większość modeli tej marki to właśnie elektryki.</p>
<h2>Elektryczne i hybrydowe szeregowe</h2>
<p>Marka konsekwentnie prowadzi dwie ścieżki napędu. Wersje w pełni elektryczne mają duże baterie i zasięgi od 500 do 730 km według procedury CLTC. Wersje hybrydowe szeregowe łączą mniejszą baterię z silnikiem spalinowym pracującym jako generator — koła zawsze napędza prąd, a benzyna służy tylko do ładowania. Ta druga opcja ma sens przy częstych trasach, bo nie uzależnia od dostępności ładowarek.</p>
<h2>Czym Changan Qiyuan różni się od Changana</h2>
<p>Changan to marka-matka z szeroką gamą aut spalinowych i hybrydowych — modele CS75, UNI-K, UNI-V i inne. Qiyuan jest jej wydzieloną linią elektryczną, z własnym nazewnictwem modeli i własną architekturą elektroniczną SDA Tianshu. W naszym serwisie obie marki mają osobne strony, więc jeśli szukasz spalinowego SUV-a Changana, zajrzyj na stronę marki Changan.</p>
<h2>Czy Changan Qiyuan jest dostępny w polskich salonach</h2>
<p>Nie. Koncern Changan zapowiadał wejście do Europy, ale linia Qiyuan nie jest u nas sprzedawana i nie ma autoryzowanego serwisu. Każdy egzemplarz w Polsce pochodzi z importu indywidualnego. Obsługę serwisową organizujemy przez warsztaty specjalizujące się w samochodach chińskich.</p>
<h2>Jak przebiega sprowadzenie</h2>
<p>Wybierasz auto z naszej listy albo zamawiasz konkretny model, wersję i kolor. Znajdujemy egzemplarz u sprawdzonego chińskiego sprzedawcy, pokazujemy zdjęcia oraz dokumenty przed zakupem, a potem zajmujemy się transportem morskim, odprawą celną, homologacją i rejestracją. Od potwierdzenia zamówienia do odbioru mija zwykle 10–14 tygodni.</p>
HTML;

$faq = [
    ['q' => 'Czym jest marka Changan Qiyuan?', 'a' => 'To elektryczna linia chińskiego koncernu Changan, po chińsku 长安启源, uruchomiona w 2023 roku. Obejmuje samochody w pełni elektryczne oraz hybrydy szeregowe, w których silnik spalinowy pracuje wyłącznie jako generator.'],
    ['q' => 'Jakie modele Changan Qiyuan można sprowadzić?', 'a' => 'W naszej ofercie są sedany A07 i A06 oraz miejski SUV Q05. Marka ma w Chinach także modele A05, E07 i Q07, a we wrześniu 2026 wprowadza SUV-a Q06 — każdy z nich możemy sprowadzić na zamówienie.'],
    ['q' => 'Ile kosztuje Changan Qiyuan w Polsce?', 'a' => 'Ceny w naszej ofercie zaczynają się od około 134 000 zł. Kwota zawiera transport z Chin, cło, VAT, homologację i obsługę Prima-Auto, czyli auto odbierasz zarejestrowane w Polsce.'],
    ['q' => 'Czym Changan Qiyuan różni się od marki Changan?', 'a' => 'Changan to marka-matka z gamą aut spalinowych i hybrydowych, jak CS75 czy UNI-K. Qiyuan jest jej wydzieloną linią elektryczną, z własnym nazewnictwem modeli i architekturą elektroniczną SDA Tianshu.'],
    ['q' => 'Czy Changan Qiyuan ma serwis w Polsce?', 'a' => 'Nie ma autoryzowanego serwisu, bo marka nie jest u nas oficjalnie sprzedawana. Obsługę organizujemy przez warsztaty specjalizujące się w autach chińskich, a części sprowadzamy razem z autem lub na zamówienie.'],
];

$json = wp_json_encode($faq, JSON_UNESCAPED_UNICODE);
$bledy = [];
if (json_decode($json, true) === null) $bledy[] = 'FAQ nie parsuje sie';
foreach (['„', '”', '“', '’'] as $sq) if (strpos($json, $sq) !== false) $bledy[] = "smart quote $sq";
if (!preg_match('/[ąćęłńóśźż]/u', $json)) $bledy[] = 'FAQ bez diakrytyk';
if (strpos($wiki, '{{LISTINGS_BAR}}') === false) $bledy[] = 'brak LISTINGS_BAR';
if (!get_term($TID, 'make') instanceof WP_Term) $bledy[] = "term $TID nie istnieje";

printf("=== WALIDACJA ===\n%s", $bledy ? implode("\n", $bledy) . "\n" : "OK\n");
printf("wiki %d zn. | FAQ %d | H2 %d\n", mb_strlen(wp_strip_all_tags($wiki)), count($faq), substr_count($wiki, '<h2>'));
if (!$apply) { echo "\nDRY-RUN — nic nie zapisane. Zapis: dopisz 'apply'\n"; return; }
if ($bledy) { echo "STOP.\n"; return; }

update_term_meta($TID, 'asiaauto_wiki_body', $wiki);
update_term_meta($TID, 'asiaauto_faq_json', $json);
update_term_meta($TID, '_asiaauto_lead', $lead);
update_term_meta($TID, '_asiaauto_h1_suffix', '— ceny w Polsce i import z Chin');
update_term_meta($TID, '_asiaauto_brand_origin', 'chinese');
update_term_meta($TID, '_asiaauto_pl_availability', 'import_only');
update_term_meta($TID, '_asiaauto_seo_rework', 'v1-premiery-2026-09-07');

$r = AsiaAuto_HubTitleGenerator::regenerateForMakeTerm($TID);
printf("marka %d — tresc zapisana | title: %s\n", $TID, is_array($r) && !empty($r['title']) ? $r['title'] : 'bez zmian');
foreach ([7247, 4770, 4772, 6884, 6583] as $tid) {
    $x = AsiaAuto_HubTitleGenerator::regenerateForTerm($tid);
    printf("  serie %d: %s\n", $tid, is_array($x) && !empty($x['title']) ? $x['title'] : 'bez zmian (skip/brak ofert)');
}
