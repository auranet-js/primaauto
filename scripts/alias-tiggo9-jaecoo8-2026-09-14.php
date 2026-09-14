<?php
/**
 * alias-tiggo9-jaecoo8-2026-09-14.php — hub serie/tiggo-9 (term 3582) + tytuły jego ofert.
 *
 * ALIAS „Jaecoo 8". Jaecoo 8 to europejski bliźniak Chery Tiggo 9: to samo nadwozie
 * (4820 × 1930 mm, rozstaw 2820 mm) i podwozie pod inną marką. Jaecoo nie istnieje w żadnym
 * źródle (dongchedi, che168) — w Chinach to auto jeździ jako Tiggo 9. DFS PL (08.09):
 * `jaecoo 8` 33 100/mc, `jaecoo 8 cena` 4 400/mc wobec `chery tiggo 9` 18 100/mc.
 * GSC (90 dni): „jaecoo 8" — 2 impresje, bo słowa „Jaecoo" nie było na stronie ani razu.
 *
 * Wzorzec aliasu jak Exeed RX (Omoda 9 SHS) i Exeed VX (Omoda 11): nazwa termu zostaje
 * „Tiggo 9" (etykieta filtra), alias w `_serie_full_title` → H1, breadcrumb, meta title/desc.
 * Tytuły ofert: prefiks „Chery Tiggo 9" → „Chery Tiggo 9 (Jaecoo 8)"; nowe importy dostają go
 * z brand-mappingu (`serie_eu`, zmiana 14.09). Oferty Tiggo 8L pod tym termem („Tiggo 9
 * (Tiggo 8L)") zostają bez zmian — 8L ma inne wymiary (4795 mm, rozstaw 2770), to nie Jaecoo 8.
 *
 * UCZCIWOŚĆ TREŚCI: w Polsce Jaecoo 8 (od 219 900 zł) i Chery Tiggo 9 (od 209 900 zł) są
 * sprzedawane wyłącznie jako hybryda plug-in 428 KM AWD. Nasze egzemplarze to benzyna 2.0T
 * 254–261 KM FWD — treść mówi to wprost, żeby klient nie porównywał 1:1 innego napędu.
 * Źródła: otomoto.pl/news (Jaecoo 8, Omoda 9 i Tiggo 9 – co je różni), cennik cherypolska.pl
 * nr 6 z 01.07.2026, spidersweb/motoguru (Jaecoo 8 219 900 zł). ADR: docs/decyzje/2026-09-08-…
 *
 * Tytuły ofert zapisywane przez $wpdb (bez wp_update_post): bez hooków save_post edytora
 * ofert, slug (URL) bez zmian.
 *
 * Użycie: wp eval-file scripts/alias-tiggo9-jaecoo8-2026-09-14.php [apply]
 */

global $wpdb;
$args  = $args ?? [];
$APPLY = in_array('apply', $args, true);
$TERM  = 3582;
$ALIAS = 'Chery Tiggo 9 (Jaecoo 8)';

echo $APPLY ? "=== APPLY ===\n\n" : "=== DRY-RUN (dodaj: apply) ===\n\n";

$t = get_term($TERM, 'serie');
if (!$t || is_wp_error($t) || $t->slug !== 'tiggo-9') { echo "Term {$TERM} nie jest tiggo-9 — stop\n"; return; }
printf("term %d: %s (slug=%s, ofert=%d) — nazwa termu ZOSTAJE (filtr)\n\n", $TERM, $t->name, $t->slug, $t->count);

// --- treść ---------------------------------------------------------------

$wiki = <<<'HTML'
<p>Chery Tiggo 9 to duży, flagowy SUV Chery. W Europie ten sam samochód sprzedawany jest także pod marką Jaecoo jako <strong>Jaecoo 8</strong> — z tym samym nadwoziem, podwoziem i układem kabiny. W Prima-Auto mamy <strong>19 egzemplarzy</strong> Tiggo 9 w cenach <strong>od 128 000 do 148 000 PLN</strong> (mediana ok. 136 000), roczniki 2023-2026. To wersje z benzynowym silnikiem 2,0 turbo (254-261 KM), sprowadzane z Chin.</p>

{{LISTINGS_BAR}}

<h2>Cena Chery Tiggo 9 i Jaecoo 8 w Polsce</h2>
<p>W Prima-Auto ceny Chery Tiggo 9 wynoszą <strong>od 128 000 do 148 000 PLN</strong> — to ceny końcowe, za auto z homologacją gotowe do rejestracji. Dla porównania: w polskich salonach Chery Tiggo 9 kosztuje od 209 900 zł, a Jaecoo 8 od 219 900 zł (wersja 5-osobowa) i 224 900 zł (7-osobowa). Różnica to nie tylko cena — w salonie oba modele występują wyłącznie jako hybryda plug-in 428 KM z napędem na obie osie, a <strong>nasze egzemplarze to benzynowa odmiana 2,0 turbo z napędem na przód</strong>, używana lub praktycznie nowa.</p>
<p>Na cenę końcową importu składają się: koszt pojazdu (CIF), transport morski, odprawa i agencja celna, cło (10%), akcyza, VAT 23%, homologacja indywidualna i prowizja za pośrednictwo. Ceny na stronie są ostateczne — bez ukrytych dopłat.</p>

<h2>Jaecoo 8 a Chery Tiggo 9 — to samo auto?</h2>
<p>W dużej mierze tak. Jaecoo to marka koncernu Chery tworzona z myślą o rynkach eksportowych — w Chinach nie występuje, a model, który w Europie nosi nazwę Jaecoo 8, w Chinach jeździ jako Chery Tiggo 9. Oba auta mają to samo podwozie i zawieszenie oraz te same wymiary: <strong>4820 × 1930 mm i rozstaw osi 2820 mm</strong>. Różnią się logo, detalami stylistyki i wykończenia wnętrza oraz — w wersjach europejskich — napędem.</p>
<p>Jeśli szukasz Jaecoo 8 i zależy Ci przede wszystkim na dużym nadwoziu, przestronnej kabinie i bogatym wyposażeniu, Tiggo 9 z importu daje to samo auto o kilkadziesiąt tysięcy złotych taniej. Jeśli kluczowy jest napęd plug-in 428 KM i 4x4 — takiej wersji w naszej ofercie teraz nie ma, a Jaecoo 8 lub Tiggo 9 z polskiego salonu będzie właściwym wyborem.</p>

<h2>Porównanie: Tiggo 9 z importu, Tiggo 9 i Jaecoo 8 z salonu</h2>
<table>
<thead><tr><th>Model</th><th>Cena</th><th>Napęd</th><th>Stan</th></tr></thead>
<tbody>
<tr><td>Chery Tiggo 9 (import, Prima-Auto)</td><td>128 000-148 000 PLN</td><td>benzyna 2,0T, 254-261 KM, FWD</td><td>używany lub niemal nowy, roczniki 2023-2026</td></tr>
<tr><td>Chery Tiggo 9 (salon PL)</td><td>od 209 900 zł</td><td>plug-in hybrid 428 KM, AWD</td><td>nowy, gwarancja producenta</td></tr>
<tr><td>Jaecoo 8 SHS (salon PL)</td><td>od 219 900 zł (5 miejsc) / 224 900 zł (7 miejsc)</td><td>plug-in hybrid 428 KM, AWD</td><td>nowy, gwarancja producenta</td></tr>
</tbody>
</table>
<p>Wybór sprowadza się do priorytetu: hybryda plug-in prosto z salonu albo to samo nadwozie z benzynowym 2,0 turbo znacznie taniej, z gwarancją importera.</p>

<h2>Silnik, spalanie i wymiary</h2>
<p>Egzemplarze w naszej ofercie napędza benzynowy silnik 2,0 turbo o mocy <strong>254-261 KM</strong> i momencie 390-400 Nm, z 7-biegową skrzynią dwusprzęgłową (DCT) i napędem na przednie koła. Prędkość maksymalna to 200-205 km/h, a katalogowe spalanie 7,5-7,9 l/100 km. Tiggo 9 ma 4820 mm długości i rozstaw osi 2820 mm; część egzemplarzy (wersje oznaczone 9X oraz Tiggo 8L) ma nieco krótszy rozstaw osi — 2770 mm. Model występuje w odmianach 5- i 7-osobowych; konfigurację miejsc każdego auta widać w jego ofercie.</p>

<h2>Wyposażenie w egzemplarzach Prima-Auto</h2>
<p>W egzemplarzach Tiggo 9 zwykle znajdziesz m.in.:</p>
<ul>
<li>Kamerę 360 stopni i kamerę cofania</li>
<li>Bezkluczykowy dostęp i start przyciskiem</li>
<li>Duży ekran multimedialny z nawigacją, wielofunkcyjną kierownicę</li>
<li>Adaptacyjny tempomat, asystentów jazdy</li>
<li>Podgrzewane i wentylowane fotele</li>
</ul>
<p>Zakres wyposażenia zależy od wersji (Comfort, Luxury, Premium, Prestige, Leading). Pełną listę dla każdego auta widać w jego ofercie.</p>

<h2>Chery Tiggo 9 w Prima-Auto — czego się spodziewać</h2>
<p>Nasze egzemplarze Tiggo 9 to auta z Chin, roczniki 2023-2026, z udokumentowanym przebiegiem od 0 do ok. 38 000 km. Część to praktycznie nowe auta (rocznik 2026, przebieg do 100 km), część z typowym przebiegiem użytkowym. Każdy pojazd jest oglądany fizycznie przez nasz zespół w Chinach przed zakupem — sprawdzamy stan techniczny, lakier, wnętrze i dokumentację. Wszystkie egzemplarze znajdują się obecnie w Chinach i sprowadzamy je na zamówienie.</p>

<h2>Import Chery Tiggo 9 przez Prima-Auto</h2>
<p>Proces trwa zwykle 8-12 tygodni: rezerwacja pojazdu (zwrotny depozyt) → weryfikacja fizyczna auta w Chinach przez nasz zespół → dokumentacja eksportowa → transport morski (6-8 tygodni) → odprawa celna w Polsce → homologacja indywidualna (badania świateł, emisji, VIN) → rejestracja → odbiór auta z kompletem dokumentów do rejestracji. Ceny na stronie są końcowe: pojazd, transport, cło, VAT 23%, homologacja i rejestracja są już wliczone — bez dopłat. Wszystkie formalności załatwiamy my, klient płaci tylko ustaloną kwotę. <a href='/samochody/?marka=chery&amp;model=tiggo-9'>Wszystkie egzemplarze Chery Tiggo 9</a> | <a href='/samochody/?marka=chery&amp;paliwo=petrol'>Benzynowe Chery</a></p>
HTML;

// FAQ z tablicy → json_encode (smart quotes łamią json_decode — memory feedback_smart_quotes_break_json).
$faq = [
    ['q' => 'Czy Jaecoo 8 to to samo auto co Chery Tiggo 9?',
     'a' => 'W dużej mierze tak. Jaecoo to eksportowa marka koncernu Chery - w Chinach nie występuje, a model sprzedawany w Europie jako Jaecoo 8 w Chinach jeździ jako Chery Tiggo 9. Oba mają to samo podwozie i te same wymiary (4820 × 1930 mm, rozstaw osi 2820 mm). Różnią się logo, detalami wykończenia i napędem: w Polsce Jaecoo 8 to hybryda plug-in 428 KM, a nasze Tiggo 9 z importu mają benzynowy silnik 2,0 turbo.'],
    ['q' => 'Ile kosztuje Jaecoo 8 w Polsce, a ile Chery Tiggo 9 z importu?',
     'a' => 'Jaecoo 8 w polskim salonie kosztuje od 219 900 zł (5 miejsc) i 224 900 zł (7 miejsc), Chery Tiggo 9 z salonu od 209 900 zł - oba jako hybryda plug-in 428 KM z napędem 4x4. W Prima-Auto Chery Tiggo 9 z importu kosztuje od 128 000 do 148 000 PLN (cena końcowa z homologacją) - to wersje benzynowe 2,0 turbo, używane lub praktycznie nowe.'],
    ['q' => 'Czy można sprowadzić Jaecoo 8 z Chin?',
     'a' => 'Marka Jaecoo nie jest sprzedawana w Chinach, więc z chińskiego rynku sprowadza się ten model jako Chery Tiggo 9 - z tym samym nadwoziem co Jaecoo 8. W Prima-Auto sprowadzamy Tiggo 9 z homologacją indywidualną i gwarancją importera.'],
    ['q' => 'Ile kosztuje Chery Tiggo 9 w Prima-Auto?',
     'a' => 'Od 128 000 do 148 000 PLN, mediana ok. 136 000 PLN. To ceny końcowe: pojazd, transport, cło, akcyza, VAT 23% i homologacja są wliczone. W ofercie mamy 19 egzemplarzy, roczniki 2023-2026.'],
    ['q' => 'Jaki silnik ma Chery Tiggo 9 z importu?',
     'a' => 'Benzynowy 2,0 turbo o mocy 254-261 KM i momencie 390-400 Nm, z 7-biegową skrzynią dwusprzęgłową i napędem na przód. Katalogowe spalanie wynosi 7,5-7,9 l/100 km. To inny napęd niż w salonowych Tiggo 9 i Jaecoo 8, które w Polsce są hybrydami plug-in 428 KM.'],
    ['q' => 'Czy Chery Tiggo 9 ma 7 miejsc?',
     'a' => 'Model występuje w wersjach 5- i 7-osobowych. Egzemplarze w naszej ofercie to przede wszystkim wersje 5-osobowe - konfigurację miejsc każdego auta widać w jego ofercie.'],
];

$lead = 'Chery Tiggo 9 — w Europie sprzedawany także jako Jaecoo 8 — sprowadzisz przez Prima-Auto '
      . 'w cenach od 128 000 do 148 000 PLN (19 egzemplarzy, mediana ok. 136 000). '
      . 'To to samo nadwozie i ta sama kabina co Jaecoo 8 z polskich salonów (od 219 900 zł), '
      . 'ale z benzynowym silnikiem 2.0 turbo 254-261 KM zamiast hybrydy plug-in 428 KM. '
      . 'Egzemplarze są używane lub praktycznie nowe, z homologacją indywidualną i gwarancją importera.';

$zmiany = [
    '_serie_full_title'       => $ALIAS,
    '_asiaauto_lead'          => $lead,
    'asiaauto_wiki_body'      => $wiki,
    'asiaauto_faq_json'       => json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    '_asiaauto_seo_rework'    => 'v2-2026-09-14-jaecoo8',
];

// --- gate'y przed zapisem --------------------------------------------------

$sprawdz = json_decode($zmiany['asiaauto_faq_json'], true);
if (json_last_error() !== JSON_ERROR_NONE || count($sprawdz) !== count($faq)) { echo "BŁĄD JSON FAQ — stop\n"; return; }
foreach ($zmiany as $k => $v) {
    if (preg_match('/[\x{201C}\x{201D}\x{201E}]/u', $v) && $k === 'asiaauto_faq_json') { echo "smart quotes w FAQ — stop\n"; return; }
}
if (!preg_match('/[ąćęłńóśźż]/u', $zmiany['asiaauto_faq_json'])) { echo "FAQ bez diakrytyk (T-193) — stop\n"; return; }

// Fakty z bazy vs treść: zakres cen i liczba ofert.
$ceny = $wpdb->get_col($wpdb->prepare(
    "SELECT pm.meta_value+0 FROM {$wpdb->posts} p
     JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID
     JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.term_id=%d
     JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='price'
     WHERE p.post_status='publish' ORDER BY 1", $TERM));
$n = count($ceny);
printf("baza: %d ofert, ceny %s–%s, mediana %s\n", $n, min($ceny), max($ceny), $ceny[intdiv($n, 2)]);
if ($n !== 19 || (int) min($ceny) !== 128000 || (int) max($ceny) !== 148000) {
    echo "Treść rozjechana z bazą (liczby w tekście: 19 / 128 000–148 000) — popraw i puść ponownie\n"; return;
}

// Import nowej oferty trafi w ten sam term po slugu (T-190 guard) — nie założy duplikatu.
$eu = AsiaAuto_Mapping::getEuForCn('Chery', 'Tiggo 9');
printf("mapping Chery|Tiggo 9: serie_eu=%s slug=%s\n\n", $eu['serie_eu'] ?? '-', $eu['slug'] ?? '-');
if (($eu['serie_eu'] ?? '') !== 'Tiggo 9 (Jaecoo 8)') { echo "Brand-mapping jeszcze bez aliasu — stop\n"; return; }

// --- hub -------------------------------------------------------------------

foreach ($zmiany as $key => $new) {
    $old = (string) get_term_meta($TERM, $key, true);
    if ($old === $new) { printf("  = %-24s bez zmian\n", $key); continue; }
    printf("  %s %-24s %d → %d zn.\n", $APPLY ? '✔' : '·', $key, mb_strlen($old), mb_strlen($new));
    if (mb_strlen($new) < 120) printf("      stare: %s\n      nowe : %s\n", $old !== '' ? $old : '(brak)', $new);
    if ($APPLY) update_term_meta($TERM, $key, $new);
}

// --- tytuły ofert ----------------------------------------------------------

echo "\nTytuły ofert:\n";
$posty = $wpdb->get_results($wpdb->prepare(
    "SELECT p.ID, p.post_status, p.post_title FROM {$wpdb->posts} p
     JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID
     JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.term_id=%d
     WHERE p.post_type='listings' ORDER BY p.post_status, p.ID", $TERM));
$zm = 0;
foreach ($posty as $p) {
    if (strpos($p->post_title, '(Tiggo 8L)') !== false || strpos($p->post_title, 'Jaecoo') !== false
        || strpos($p->post_title, 'Chery Tiggo 9 ') !== 0) {
        printf("  = #%d %-7s %s\n", $p->ID, $p->post_status, $p->post_title);
        continue;
    }
    $nowy = $ALIAS . substr($p->post_title, strlen('Chery Tiggo 9'));
    printf("  %s #%d %-7s %s\n", $APPLY ? '✔' : '·', $p->ID, $p->post_status, $nowy);
    if ($APPLY) {
        $wpdb->update($wpdb->posts, ['post_title' => $nowy], ['ID' => $p->ID]);
        clean_post_cache($p->ID);
    }
    $zm++;
}
printf("  → %d tytułów do zmiany\n", $zm);

// --- meta title/desc z _serie_full_title -----------------------------------

if ($APPLY) {
    $r = AsiaAuto_HubTitleGenerator::regenerateForTerm($TERM);
    printf("\nrank_math_title: %s\nrank_math_description: %s\n", $r['title'] ?? '-', $r['desc'] ?? '-');
}

echo $APPLY ? "\nGOTOWE.\n" : "\nNic nie zmieniono.\n";
