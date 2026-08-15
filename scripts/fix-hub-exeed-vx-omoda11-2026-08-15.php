<?php
/**
 * fix-hub-exeed-vx-omoda11-2026-08-15.php — hub serie/vx (term 5200, Exeed VX).
 *
 * Dwie rzeczy naraz, bo dotyczą tych samych pól:
 *
 * 1. ALIAS „Omoda 11". Chery sprzedaje 星途揽月 na eksport także jako Omoda 11. DFS PL:
 *    `omoda 11` 480/mc, `omoda 11 cena` 70/mc. GSC (90 dni) pokazuje, że Google JUŻ wiąże
 *    encję z tym hubem — `omoda 11 cena` i `omoda 11 kiedy w polsce` wychodzą na pozycji 1,0
 *    — ale po 1 impresji, bo słowa „Omoda" nie ma na stronie ANI RAZU. Wzorzec aliasu jest
 *    w projekcie ustalony przy Exeed RX (term 5193: `Exeed RX (Omoda 9 SHS)`), VX go nie dostał.
 *    UWAGA: w przeciwieństwie do RX bliźniak NIE jest w polskiej dystrybucji (OMODA & JAECOO
 *    Polska ma 5 / E5 / 7 / 9), więc `_asiaauto_pl_availability` zostaje `import_only`.
 *
 * 2. CENY. Treść wiki/lead/FAQ pochodzi z 23.04 i mówi „3 egzemplarze, 162 000–165 000 PLN".
 *    Realnie (15.08): 4 egzemplarze, 169 000–193 000 PLN. rank_math_title miał już nową cenę,
 *    ale starą liczbę sztuk — hub był niespójny sam ze sobą.
 *
 * Użycie: wp eval-file scripts/fix-hub-exeed-vx-omoda11-2026-08-15.php [apply]
 */

$args  = $args ?? [];
$APPLY = in_array('apply', $args, true);
$TERM  = 5200;

echo $APPLY ? "=== APPLY ===\n\n" : "=== DRY-RUN (dodaj: apply) ===\n\n";

$t = get_term($TERM, 'serie');
if (!$t || is_wp_error($t)) { echo "Brak termu {$TERM}\n"; return; }
printf("term %d: %s (slug=%s, ofert=%d)\n\n", $TERM, $t->name, $t->slug, $t->count);

// --- treść ---------------------------------------------------------------

$wiki = <<<'HTML'
<p>Exeed VX (Lanyue) to sześciomiejscowy SUV segmentu premium z napędem hybrydowym plug-in i stałym napędem 4x4. Na rynkach eksportowych Chery sprzedaje ten sam samochód pod marką Omoda jako <strong>Omoda 11</strong>. W Prima-Auto mamy <strong>4 egzemplarze</strong> w cenach <strong>od 169 000 do 193 000 PLN</strong>, w konfiguracji 2+2+2, roczniki 2025-2026. Exeed to premium-marka koncernu Chery — VX łączy moc 462 KM z zasięgiem elektrycznym 143 km i łącznym ok. 1300 km.</p>

{{LISTINGS_BAR}}

<h2>Cena i dostępność Exeed VX w Polsce</h2>
<p>W Prima-Auto ceny Exeed VX wynoszą <strong>od 169 000 do 193 000 PLN</strong> — to ceny końcowe, za auto z homologacją gotowe do rejestracji. Marka Exeed nie ma w Polsce oficjalnej dystrybucji (Chery wprowadza na nasz rynek siostrzaną markę Exlantix z innymi modelami), więc VX kupisz wyłącznie z importu indywidualnego.</p>
<p>Na cenę końcową składają się: koszt pojazdu (CIF), transport morski, odprawa i agencja celna, cło (10%), akcyza (dla hybryd plug-in bywa obniżona), VAT 23%, homologacja indywidualna i prowizja za pośrednictwo. Część egzemplarzy jest w drodze do UE (dostępne najszybciej), pozostałe sprowadzamy na zamówienie. Aktualna lista z cenami i wersjami jest powyżej.</p>

<h2>Exeed VX a Omoda 11 — ten sam samochód</h2>
<p>Chery używa dla tego modelu trzech nazw, zależnie od rynku: <strong>星途揽月 (Exeed Lanyue)</strong> w Chinach, <strong>Exeed VX</strong> w eksportowej ofercie marki Exeed i <strong>Omoda 11</strong> tam, gdzie koncern buduje sieć pod marką Omoda. To ten sam pojazd — ta sama płyta podłogowa, ten sam układ hybrydowy plug-in i ta sama sześciomiejscowa kabina 2+2+2.</p>
<p>W polskich salonach OMODA &amp; JAECOO dostępne są modele 5, E5, 7 i 9 — <strong>Omoda 11 nie weszła dotąd do oficjalnej dystrybucji w Polsce</strong>. Egzemplarze, które pojawiają się na polskim rynku pod tą nazwą, pochodzą z importu. Jeśli szukałeś Omody 11, to auto z tej strony — sprowadzamy je bezpośrednio z Chin, z homologacją indywidualną i gwarancją importera.</p>

<p>Przy rozstawie osi 2900 mm VX oferuje przestronną, trzyrzędową kabinę 2+2+2. Moc 462 KM i napęd 4x4 dają sprint 0-100 km/h w 5 sekund oraz pewną trakcję, a zasięg łączny ok. 1300 km czyni z niego auto wygodne na długie trasy.</p>

<h2>Wersja Long Range Ultra</h2>
<p>Egzemplarze w ofercie to wariant <strong>180 Ultra Long Range</strong> z napędem 4WD i konfiguracją 6-osobową — najwyższa specyfikacja z pełnym pakietem komfortu i asystentów. Konkretny rocznik i przebieg każdego auta znajdziesz w jego ofercie powyżej.</p>

<h2>Spalanie i zasięg elektryczny Exeed VX</h2>
<p>Jako hybryda plug-in VX oferuje <strong>143 km zasięgu elektrycznego</strong> (WLTC) z baterii 34 kWh oraz zasięg łączny ok. 1300 km. Przy regularnym ładowaniu codzienne przejazdy odbywają się na prądzie, bez spalania paliwa; po wyczerpaniu baterii pracuje silnik 1,5 l i auto jeździ jak klasyczna hybryda — realne zużycie zależy od stylu jazdy i obciążenia.</p>

<h2>Wyposażenie w egzemplarzach Prima-Auto</h2>
<ul>
<li>Kamera 360° i kamera cofania</li>
<li>Bezkluczykowy dostęp i start</li>
<li>Aktywny tempomat z asystentem pasa ruchu</li>
<li>Automatyczne parkowanie</li>
<li>Podgrzewane, wentylowane i masujące fotele przednie</li>
<li>Wyświetlacz przezierny (HUD)</li>
<li>Ładowarka indukcyjna smartfona</li>
<li>Elektryczna klapa bagażnika</li>
</ul>

<h2>Dla kogo jest Exeed VX</h2>
<p>VX to propozycja dla rodziny szukającej przestronnego, 6-osobowego SUV-a premium z napędem plug-in i 4x4, w cenie od 169 000 PLN — znacznie poniżej europejskich rywali tej wielkości i mocy. Łączy komfort trzech rzędów z ekonomią jazdy na prądzie na co dzień. Jako import wymaga homologacji indywidualnej, którą przeprowadzamy w cenie.</p>

<h2>Import Exeed VX przez Prima-Auto — proces, homologacja, gwarancja</h2>
<p>Sprowadzenie prowadzimy kompleksowo, w modelu pośrednictwa: dobór egzemplarza, transport z Chin, odprawa celna, a po przyjeździe — pełna homologacja indywidualna i przygotowanie do rejestracji. Każde auto objęte jest gwarancją importera. Egzemplarze w drodze do UE są dostępne najszybciej; pozostałe sprowadzamy na zamówienie. Rozliczenie opiera się na zwrotnym depozycie gwarancyjnym i jasno określonej prowizji — bez ukrytych kosztów.</p>
HTML;

// FAQ budowany z tablicy → json_encode. Nigdy ręcznie sklejany JSON: smart quotes rozbijają
// parser i FAQPage znika z SERP-u (memory feedback_smart_quotes_break_json).
$faq = [
    ['q' => 'Czy Exeed VX to to samo auto co Omoda 11?',
     'a' => 'Tak. Chery sprzedaje ten model pod trzema nazwami: 星途揽月 (Exeed Lanyue) w Chinach, Exeed VX w eksportowej ofercie marki Exeed oraz Omoda 11 na rynkach, gdzie koncern rozwija markę Omoda. To ten sam samochód - ta sama konstrukcja, ten sam napęd plug-in i ta sama kabina 2+2+2.'],
    ['q' => 'Kiedy Omoda 11 będzie w Polsce?',
     'a' => 'W polskich salonach OMODA & JAECOO są obecnie modele 5, E5, 7 i 9 - Omoda 11 nie weszła dotąd do oficjalnej dystrybucji. Egzemplarze dostępne w Polsce pod tą nazwą pochodzą z importu. W Prima-Auto sprowadzamy je bezpośrednio z Chin jako Exeed VX, z homologacją indywidualną i gwarancją importera.'],
    ['q' => 'Ile kosztuje Exeed VX w Polsce?',
     'a' => 'W Prima-Auto od 169 000 do 193 000 PLN - ceny końcowe, za auto z homologacją gotowe do rejestracji. Exeed nie ma oficjalnej dystrybucji w Polsce, więc VX dostępny jest wyłącznie z importu.'],
    ['q' => 'Czy Exeed VX jest dostępny w Polsce?',
     'a' => 'Marka Exeed nie ma w Polsce salonów (Chery wprowadza u nas siostrzaną markę Exlantix z innymi modelami). W Prima-Auto sprowadzamy VX z Chin - część egzemplarzy jest w drodze do UE, pozostałe na zamówienie.'],
    ['q' => 'Ile pali Exeed VX i jaki ma zasięg elektryczny?',
     'a' => 'To hybryda plug-in z zasięgiem elektrycznym 143 km (WLTC) z baterii 34 kWh i zasięgiem łącznym ok. 1300 km. Przy regularnym ładowaniu codzienna jazda odbywa się na prądzie; potem pracuje silnik 1,5 l, a zużycie zależy od trasy i stylu jazdy.'],
    ['q' => 'Jaki napęd, moc i wymiary ma Exeed VX?',
     'a' => 'Moc układu 462 KM, napęd AWD 4x4, 0-100 km/h w 5,0 s. Wymiary 5010 × 1940 × 1800 mm, rozstaw osi 2900 mm, 6 miejsc w układzie 2+2+2.'],
    ['q' => 'Które wersje Exeed VX ma Prima-Auto?',
     'a' => 'W ofercie wariant 180 Ultra Long Range (4WD, 6-osobowy), roczniki 2025-2026 - najwyższa specyfikacja z pełnym pakietem komfortu i asystentów.'],
];

$lead = 'Exeed VX (Lanyue), na rynkach eksportowych sprzedawany jako Omoda 11, kupisz w Polsce '
      . 'wyłącznie z importu — ani Exeed, ani Omoda 11 nie mają u nas oficjalnej dystrybucji. '
      . 'W Prima-Auto mamy 4 egzemplarze w cenach od 169 000 do 193 000 PLN, część w drodze do UE. '
      . 'To 6-osobowy SUV plug-in 4x4 (462 KM, zasięg elektryczny 143 km), sprowadzany '
      . 'z homologacją indywidualną i gwarancją importera.';

$desc = 'Exeed VX (Omoda 11) w Prima-Auto — 4 egzemplarze, ceny od 169 000 do 193 000 PLN. '
      . 'Import z Chin z homologacją indywidualną i gwarancją importera.';

$zmiany = [
    '_serie_full_title'        => 'Exeed VX (Omoda 11)',
    'rank_math_title'          => 'Exeed VX (Omoda 11) — od 169 000 PLN, 4 sztuki | Import z Chin',
    'rank_math_description'    => $desc,
    'asiaauto_seo_desc'        => $desc,
    'rank_math_focus_keyword'  => 'Exeed VX',
    '_asiaauto_lead'           => $lead,
    'asiaauto_wiki_body'       => $wiki,
    'asiaauto_faq_json'        => json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
];

// --- wykonanie -----------------------------------------------------------

foreach ($zmiany as $key => $new) {
    $old = (string) get_term_meta($TERM, $key, true);
    if ($old === $new) { printf("  = %-26s bez zmian\n", $key); continue; }
    printf("  %s %-26s %d → %d zn.\n", $APPLY ? '✔' : '·', $key, mb_strlen($old), mb_strlen($new));
    if (mb_strlen($new) < 200) {
        printf("      stare: %s\n      nowe : %s\n", $old !== '' ? $old : '(brak)', $new);
    }
    if ($APPLY) update_term_meta($TERM, $key, $new);
}

printf("\nmeta title: %d zn. (cel 61-73)\n", mb_strlen($zmiany['rank_math_title']));
$sprawdz = json_decode($zmiany['asiaauto_faq_json'], true);
printf("FAQ: %s, pytań=%d\n", json_last_error() === JSON_ERROR_NONE ? 'JSON parsuje się' : 'BŁĄD JSON',
    is_array($sprawdz) ? count($sprawdz) : 0);
printf("_asiaauto_pl_availability zostaje: %s (Omoda 11 nie jest w dystrybucji PL)\n",
    get_term_meta($TERM, '_asiaauto_pl_availability', true));

echo $APPLY ? "\nGOTOWE.\n" : "\nNic nie zmieniono.\n";
