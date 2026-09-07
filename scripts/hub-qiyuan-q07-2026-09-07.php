<?php
/**
 * hub-qiyuan-q07-2026-09-07.php — treść huba Changan Qiyuan Q07.
 *
 * Hub był pusty do czasu ręcznego dociągu oferty #468676 (Guiyang, poza filtrem miast).
 * Fakty z `_asiaauto_extra_prep` tej oferty: SUV 4837×1920×1690, rozstaw 2905, masa 1935 kg,
 * bateria 31,7 kWh, 215 km CLTC na prądzie, silnik 1.5T 150 KM, 0–100 w 7,57 s.
 *
 * Title i description zostawiamy generatorowi — hub ma ofertę.
 *
 * Użycie: wp eval-file scripts/hub-qiyuan-q07-2026-09-07.php [apply]
 */

$apply = (($args[0] ?? '') === 'apply');
$TID   = 4770;

$lead = 'Changan Qiyuan Q07 to hybrydowy SUV z baterią 31,7 kWh, która pozwala przejechać 215 km na samym prądzie, i silnikiem 1.5T pracującym jako wsparcie na dłuższych trasach. W naszej ofercie kosztuje około 168 000 zł. Model nie jest sprzedawany w Polsce, więc trafia tu wyłącznie z importu.';

$wiki = <<<'HTML'
<p>Changan Qiyuan Q07 to SUV klasy średniej z elektrycznej linii koncernu Changan. Łączy dużą jak na hybrydę baterię z silnikiem benzynowym, dzięki czemu na co dzień jeździ się nim jak autem elektrycznym, a w trasie nie trzeba szukać ładowarki.</p>
{{LISTINGS_BAR}}
<h2>Ile kosztuje Changan Qiyuan Q07 w Polsce</h2>
<p>Aktualną cenę naszego egzemplarza widzisz nad tym opisem — to około 168 000 zł. Kwota zawiera transport morski z Chin, cło, VAT, homologację i naszą obsługę, więc auto odbierasz zarejestrowane i gotowe do jazdy. Hybrydy plug-in o pojemności do 2000 cm³ są w Polsce zwolnione z akcyzy, co przy tym modelu obniża koszt sprowadzenia.</p>
<h2>Napęd hybrydowy z dużą baterią</h2>
<p>Bateria ma 31,7 kWh — dwa, trzy razy więcej niż typowa hybryda plug-in europejskiego producenta — i wystarcza na 215 km według chińskiej procedury CLTC. Realnie oznacza to cały tydzień dojazdów bez uruchamiania silnika. Gdy akumulator się rozładuje, do pracy wchodzi jednostka 1.5T o mocy 150 KM. Deklarowane zużycie paliwa w cyklu mieszanym to 0,75 l/100 km, ale ta liczba zakłada codzienne ładowanie; przy pustej baterii auto spala tyle, co zwykły SUV z silnikiem 1.5.</p>
<h2>Osiągi i ładowanie</h2>
<p>Przyspieszenie do stu kilometrów na godzinę zajmuje 7,57 sekundy, prędkość maksymalna to 180 km/h. Baterię na szybkiej ładowarce prądu stałego uzupełnisz w około 15 minut, a przez noc naładujesz ją z domowego gniazda.</p>
<h2>Wymiary i przestrzeń</h2>
<p>Nadwozie ma 4837 mm długości, 1920 mm szerokości i 1690 mm wysokości przy rozstawie osi 2905 mm. To wymiary porównywalne z Toyotą RAV4 czy Volkswagenem Tiguanem Allspace, przy masie własnej 1935 kg. Pięć miejsc, pięcioro drzwi.</p>
<h2>Q07 czy Q05</h2>
<p>Q05 jest wyraźnie mniejszy — 4435 mm długości — i w pełni elektryczny, z zasięgiem 506 km. Q07 jest większy, ma napęd hybrydowy i nie wymaga ładowarki w trasie. Jeśli jeździsz głównie po mieście i masz gdzie ładować, tańszy Q05 wystarczy; Q07 wybierzesz, gdy potrzebujesz przestrzeni i swobody w dłuższych podróżach.</p>
<h2>Dostępność w Polsce</h2>
<p>Marka Changan Qiyuan nie ma polskiej dystrybucji ani autoryzowanego serwisu, więc każdy egzemplarz pochodzi z importu indywidualnego. Obsługę serwisową organizujemy przez warsztaty specjalizujące się w autach chińskich, a części sprowadzamy razem z autem lub na zamówienie.</p>
<h2>Jak przebiega sprowadzenie</h2>
<p>Wybierasz egzemplarz albo zamawiasz konkretną wersję i kolor. Znajdujemy auto u sprawdzonego chińskiego sprzedawcy, pokazujemy zdjęcia i dokumenty przed zakupem, a potem zajmujemy się transportem morskim, odprawą celną, homologacją i rejestracją. Od potwierdzenia zamówienia do odbioru mija zwykle 10–14 tygodni.</p>
HTML;

$faq = [
    ['q' => 'Ile kosztuje Changan Qiyuan Q07 w Polsce?', 'a' => 'Nasz obecny egzemplarz kosztuje około 168 000 zł. Kwota zawiera transport z Chin, cło, VAT, homologację i obsługę Prima-Auto, czyli auto odbierasz zarejestrowane w Polsce.'],
    ['q' => 'Ile Changan Qiyuan Q07 przejedzie na prądzie?', 'a' => 'Bateria 31,7 kWh daje 215 km według chińskiej procedury CLTC. Realnie, przy europejskim stylu jazdy, warto liczyć na około 160–180 km, co pokrywa tydzień typowych dojazdów.'],
    ['q' => 'Czy Q07 wymaga ładowania?', 'a' => 'Nie. Po rozładowaniu baterii pracuje silnik benzynowy 1.5T o mocy 150 KM, więc auto tankuje się jak zwykły samochód. Ładowanie po prostu obniża koszty eksploatacji.'],
    ['q' => 'Jakiej wielkości jest Changan Qiyuan Q07?', 'a' => 'To SUV o długości 4837 mm, szerokości 1920 mm i wysokości 1690 mm, z rozstawem osi 2905 mm — wymiarami porównywalny z Toyotą RAV4. Ma pięć miejsc.'],
    ['q' => 'Czy Changan Qiyuan Q07 można kupić w polskim salonie?', 'a' => 'Nie. Marka nie ma w Polsce dystrybucji ani autoryzowanego serwisu, więc każdy egzemplarz pochodzi z importu indywidualnego z Chin.'],
];

$json  = wp_json_encode($faq, JSON_UNESCAPED_UNICODE);
$bledy = [];
if (json_decode($json, true) === null) $bledy[] = 'FAQ nie parsuje sie';
foreach (['„', '”', '“', '’'] as $sq) if (strpos($json, $sq) !== false) $bledy[] = "smart quote $sq";
if (!preg_match('/[ąćęłńóśźż]/u', $json)) $bledy[] = 'FAQ bez diakrytyk';
if (strpos($wiki, '{{LISTINGS_BAR}}') === false) $bledy[] = 'brak LISTINGS_BAR';

printf("=== WALIDACJA ===\n%s", $bledy ? implode("\n", $bledy) . "\n" : "OK\n");
printf("wiki %d zn. | FAQ %d | H2 %d | ofert na hubie: %d\n",
    mb_strlen(wp_strip_all_tags($wiki)), count($faq), substr_count($wiki, '<h2>'), get_term($TID, 'serie')->count);
if (!$apply) { echo "\nDRY-RUN — zapis: dopisz 'apply'\n"; return; }
if ($bledy) { echo "STOP.\n"; return; }

update_term_meta($TID, 'asiaauto_wiki_body', $wiki);
update_term_meta($TID, 'asiaauto_faq_json', $json);
update_term_meta($TID, '_asiaauto_lead', $lead);
update_term_meta($TID, '_asiaauto_h1_suffix', 'cena w Polsce i import z Chin');
update_term_meta($TID, '_serie_full_title', 'Changan Qiyuan Q07');
update_term_meta($TID, '_asiaauto_primary_make_slug', 'changan-qiyuan');
update_term_meta($TID, '_asiaauto_pl_availability', 'import_only');
update_term_meta($TID, '_asiaauto_seo_rework', 'v1-premiery-2026-09-07');

$r = AsiaAuto_HubTitleGenerator::regenerateForTerm($TID);
printf("hub Q07 (term %d) — tresc zapisana | title: %s\n", $TID, is_array($r) && !empty($r['title']) ? $r['title'] : 'bez zmian');
$m = AsiaAuto_HubTitleGenerator::regenerateForMakeTerm(6528);
printf("marka Changan Qiyuan — title: %s\n", is_array($m) && !empty($m['title']) ? $m['title'] : 'bez zmian');
