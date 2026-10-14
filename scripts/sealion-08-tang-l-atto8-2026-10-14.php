<?php
/**
 * sealion-08-tang-l-atto8-2026-10-14.php — rozdzielenie BYD Sealion 08 i Tang L (Atto 8).
 *
 * Ludzie wpisują „sealion 8", a szukają nowego Sealion 08 (seria Ocean, premiera w Chinach 2.09.2026).
 * Dotąd ten ruch lądował na hubie Tang L hybrydy (/sealion-8-dm-i/) i nie konwertował
 * (GA4 90 dni: 748 wejść, 2 kontakty). Plan zaakceptowany przez Janka i Ruslana 13–14.10.2026:
 *
 *   - nowy hub /samochody/byd/sealion-08/ (premierowy, bez ofert, na indeksowej whiteliście),
 *   - term 3746 „Sealion 8 DM-I (Tang L)" → „Tang L DM-i", slug tang-l-dm-i, H1 „BYD Tang L DM-i (Atto 8)",
 *   - term 3703 „Sealion 8 (Tang L) EV" → „Tang L EV", slug tang-l-ev (nazwa huba bez zmian),
 *   - tytuły ofert hybrydy „BYD Tang L DM-i (Atto 8) …",
 *   - linki w hubach Tang DM-i (3700), Datang (7244) i rankingu baterii (post 406167),
 *   - kasowanie pustych sierot tang-l-ev (6621) i tang-l-dm (6630), bo blokują slugi.
 *
 * 301 ze starych adresów robi V62_SERIE_REDIRECTS w class-asiaauto-redirects.php (osobna zmiana).
 * Dane Sealion 08: katalog Autohome seriesid 8484, 8 wersji (specid 76343, 77791–77793, 78857,
 * 78957, 79055, 79056). Ceny PL: AsiaAuto_Price::calculateFromCny(), kurs z 14.10.2026
 * (CNY/USD 0,149254; USD/PLN 4,00). Treść wygenerował model w sesji; skrypt jest nośnikiem zapisu.
 *
 * Użycie:
 *   wp eval-file scripts/sealion-08-tang-l-atto8-2026-10-14.php podglad
 *   wp eval-file scripts/sealion-08-tang-l-atto8-2026-10-14.php apply
 */

$tryb = (string) ($args[0] ?? 'podglad');

const S08_MAKE_ID = 3697;   // BYD (make)
const TERM_DMI    = 3746;
const TERM_EV     = 3703;
const ORPHANS     = [6621, 6630];

// ------------------------------------------------------------------ Tang L DM-i (3746)

$DMI_LEAD = 'BYD Tang L DM-i, w Europie wprowadzany pod nazwą Atto 8, sprowadzasz z Chin przez Prima-Auto w cenach od 196 000 do 219 000 PLN — część egzemplarzy jest w Polsce lub w drodze do UE. To 7-osobowy SUV plug-in z napędem 4x4 (do 544 KM, do 135 km na prądzie). W Europie BYD zapowiedział Atto 8 tylko jako elektryk, więc hybrydę sprowadzamy z Chin, z homologacją i gwarancją importera.';

$DMI_WIKI = <<<'HTML'
<p>BYD Tang L DM-i to siedmiomiejscowy SUV z napędem hybrydowym plug-in DM-p i stałym 4x4 — w Europie BYD wprowadza ten model pod nazwą <strong>Atto 8</strong>. W Prima-Auto mamy egzemplarze w cenach <strong>od 196 000 do 219 000 PLN</strong>, w konfiguracji 2+3+2, rocznik 2025. Przy długości 5040 mm i mocy do 544 KM to przestronny, rodzinny SUV klasy wyższej z zasięgiem elektrycznym do codziennej jazdy bez paliwa.</p>
<p><strong>Szukasz nowego BYD Sealion 08?</strong> To inny model — SUV z serii Ocean, który wszedł do sprzedaży w Chinach we wrześniu 2026 r. Opisujemy go na stronie <a href="/samochody/byd/sealion-08/">BYD Sealion 08</a>.</p>

{{LISTINGS_BAR}}

<h2>Cena i dostępność BYD Tang L DM-i w Polsce</h2>
<p>W Prima-Auto ceny BYD Tang L DM-i wynoszą <strong>od 196 000 do 219 000 PLN</strong> — to ceny końcowe, za auto z homologacją gotowe do rejestracji. BYD jest w Polsce obecny oficjalnie, ale hybrydy Tang L nie ma w polskiej ofercie salonowej — sprowadzamy ją indywidualnie z Chin. Część egzemplarzy jest w Polsce lub w drodze do UE, pozostałe sprowadzamy na zamówienie.</p>
<p>Na cenę końcową składają się: koszt pojazdu (CIF), transport morski, odprawa i agencja celna, cło (10%), akcyza (dla hybryd plug-in bywa obniżona), VAT 23%, homologacja indywidualna i prowizja za pośrednictwo.</p>
<p>Układ 2+3+2 daje siedem pełnowartościowych miejsc, a moc do 544 KM i napęd 4x4 — sprint 0-100 km/h w 4-5 s mimo dużych gabarytów. Zasięg elektryczny 135 km pokrywa typowe dzienne przejazdy bez uruchamiania silnika.</p>

<h2>Tang L DM-i a BYD Atto 8</h2>
<p>Atto 8 to nazwa, pod którą BYD wprowadza Tang L do Europy. Premiera odbyła się w Paryżu w październiku 2026 r., a sprzedaż w salonach ma ruszyć w I kwartale 2027 r. — na start wyłącznie w wersji elektrycznej. Hybrydy plug-in DM-p, którą oferujemy na tej stronie, BYD do Europy nie zapowiedział, dlatego sprowadzamy ją z Chin. Elektrycznego Tang L znajdziesz na stronie <a href="/samochody/byd/tang-l-ev/">BYD Tang L EV (Atto 8)</a>.</p>

<h2>Wersje BYD Tang L DM-i w ofercie Prima-Auto</h2>
<ul>
<li><strong>DM-p 4WD</strong> — wszystkie egzemplarze z napędem na cztery koła, w wariantach różniących się zasięgiem elektrycznym.</li>
<li><strong>LiDAR Flagship</strong> — najwyższa specyfikacja z czujnikiem LiDAR i rozszerzonym pakietem asystentów.</li>
</ul>
<p>Konkretną wersję, zasięg i przebieg każdego auta znajdziesz w jego ofercie powyżej.</p>

<h2>Spalanie i zasięg elektryczny BYD Tang L DM-i</h2>
<p>Jako hybryda plug-in Tang L DM-i oferuje <strong>135 km zasięgu elektrycznego</strong> (WLTC) z baterii 35-36 kWh. Przy regularnym ładowaniu codzienne przejazdy rodzinne odbywają się na prądzie, bez spalania paliwa; po wyczerpaniu baterii pracuje silnik 1,5 l i auto jeździ jak klasyczna hybryda. To rozwiązanie łączące ekonomię z brakiem ograniczeń zasięgu na dłuższych trasach z pełnym obłożeniem.</p>

<h2>Wyposażenie w egzemplarzach Prima-Auto</h2>
<ul>
<li>Kamera 360° i kamera cofania</li>
<li>Bezkluczykowy dostęp i start</li>
<li>Aktywny tempomat z asystentem pasa ruchu</li>
<li>Automatyczne parkowanie</li>
<li>Oświetlenie ambientowe</li>
<li>Podgrzewane, wentylowane i masujące fotele przednie</li>
<li>Elektryczna klapa bagażnika z wysuwanymi klamkami</li>
<li>Wyświetlacz przezierny (HUD)</li>
</ul>

<h2>Dla kogo jest BYD Tang L DM-i</h2>
<p>Tang L DM-i to auto dla rodziny potrzebującej realnych siedmiu miejsc i mocnego, oszczędnego napędu plug-in — w cenie od 196 000 PLN, poniżej europejskich 7-osobowych SUV-ów hybrydowych tej klasy. Codzienna jazda na prądzie, przestrzeń w trzech rzędach i napęd 4x4 czynią z niego praktyczne auto wielozadaniowe. Jako import wymaga homologacji indywidualnej, którą przeprowadzamy w cenie.</p>

<h2>Import BYD Tang L DM-i przez Prima-Auto — proces, homologacja, gwarancja</h2>
<p>Sprowadzenie prowadzimy kompleksowo, w modelu pośrednictwa: dobór egzemplarza, transport z Chin, odprawa celna, a po przyjeździe — pełna homologacja indywidualna i przygotowanie do rejestracji. Każde auto objęte jest gwarancją importera. Egzemplarze w Polsce lub w drodze do UE są dostępne najszybciej; pozostałe sprowadzamy na zamówienie. Rozliczenie opiera się na zwrotnym depozycie gwarancyjnym i jasno określonej prowizji.</p>
HTML;

$DMI_FAQ = [
    ['q' => 'Ile kosztuje BYD Tang L DM-i (Atto 8) w Polsce?', 'a' => 'W Prima-Auto od 196 000 do 219 000 PLN — ceny końcowe, za auto z homologacją gotowe do rejestracji. To 7-osobowy SUV plug-in 4x4, którego cena jest wyraźnie niższa niż europejskich 7-osobowych hybryd tej klasy.'],
    ['q' => 'Czy BYD Tang L DM-i to BYD Atto 8?', 'a' => 'Tak, to ten sam model: Atto 8 to europejska nazwa Tang L. W Europie BYD zapowiedział jednak tylko wersję elektryczną, ze sprzedażą od I kwartału 2027 r. Hybrydę plug-in DM-p z tej strony sprowadzamy z Chin.'],
    ['q' => 'Czy to ten sam samochód co BYD Sealion 08?', 'a' => 'Nie. Sealion 08 to osobny, nowy SUV z serii Ocean, który wszedł do sprzedaży w Chinach we wrześniu 2026 r. — ma 5115 mm długości i 5 lub 6 miejsc. Tang L DM-i to 7-osobowy SUV z linii Dynasty. Sealion 08 opisujemy na osobnej stronie.'],
    ['q' => 'Ile pali BYD Tang L DM-i i jaki ma zasięg elektryczny?', 'a' => 'To hybryda plug-in z zasięgiem elektrycznym 135 km (WLTC) z baterii 35-36 kWh. Przy regularnym ładowaniu codzienna jazda odbywa się na prądzie; po rozładowaniu baterii pracuje silnik 1,5 l, a zużycie zależy od obciążenia i stylu jazdy.'],
    ['q' => 'Ile miejsc i jaką moc ma BYD Tang L DM-i?', 'a' => 'Tang L DM-i ma 7 miejsc w układzie 2+3+2, moc układu do 544 KM (plus silnik 1,5 l 156 KM), napęd AWD 4x4, 0-100 km/h w 4-5 s. Długość 5040 mm, szerokość 1996 mm, rozstaw osi 2950 mm.'],
    ['q' => 'Które wersje BYD Tang L DM-i ma Prima-Auto?', 'a' => 'Wszystkie egzemplarze to DM-p 4WD, w wariantach różniących się zasięgiem elektrycznym, w tym wersja LiDAR Flagship z czujnikiem LiDAR. Rocznik 2025 — szczegóły w ofertach powyżej.'],
];

// ------------------------------------------------------------------ Sealion 08 (nowy)

$S08 = [
    'name'      => 'Sealion 08',
    'slug'      => 'sealion-08',
    'full'      => 'BYD Sealion 08',
    'api'       => '海狮08',
    'title'     => 'BYD Sealion 08 / Sealion 8 — cena w Polsce od 255 000 zł | Prima-Auto',
    'desc'      => 'BYD Sealion 08 — cena w Polsce od 255 000 zł. Elektryczny (do 900 km CLTC) lub hybryda plug-in, 5 lub 6 miejsc. Nowość z Chin 2026, import przez Prima-Auto.',
    'h1_suffix' => 'cena w Polsce i import z Chin',
    'lead'      => 'BYD Sealion 08 to nowy duży SUV z serii Ocean, który wszedł do sprzedaży w Chinach 2 września 2026 r. w cenie od 229 900 juanów — po sprowadzeniu do Polski przez Prima-Auto to orientacyjnie od 255 000 zł z cłem, VAT-em i homologacją. Do wyboru jest wersja elektryczna z zasięgiem do 900 km CLTC i hybryda plug-in z zasięgiem łącznym do 1650 km, z 5 lub 6 miejscami. W Polsce bywa szukany jako Sealion 8 — tej nazwy BYD może użyć w Europie, ale jej jeszcze nie potwierdził. Modelu nie ma w polskich salonach, sprowadzamy go na indywidualne zamówienie.',
];

$S08_WIKI = <<<'HTML'
<p><strong>Szukasz 7-osobowej hybrydy BYD Tang L (Atto 8)?</strong> To inny model — mamy go od ręki na stronie <a href="/samochody/byd/tang-l-dm-i/">BYD Tang L DM-i (Atto 8)</a>.</p>
<p>BYD Sealion 08 (chińskie 海狮08) to flagowy SUV serii Ocean, którego stylistyka nawiązuje do koncepcyjnego Ocean S. Do sprzedaży w Chinach wszedł 2 września 2026 r. jako rocznik 2026 i w pierwszej dobie zebrał ponad 12 000 zamówień. Producent oferuje go w ośmiu wersjach — czterech elektrycznych i czterech hybrydowych plug-in — z nadwoziem pięcio- lub sześcioosobowym. Marka BYD ma w Polsce salony, ale Sealion 08 nie należy do ich oferty, więc dziś można go mieć wyłącznie z importu indywidualnego.</p>

{{LISTINGS_BAR}}

<h2>Ile kosztuje BYD Sealion 08 w Polsce</h2>
<p>Chiński cennik zaczyna się od 229 900 juanów i kończy na 279 900 juanów. Po przeliczeniu przez nasz kalkulator importu — z transportem, cłem 10%, VAT-em 23%, homologacją i obsługą — daje to orientacyjnie <strong>od 255 000 do 300 000 zł</strong>. Akcyzy nie ma: samochody elektryczne są z niej zwolnione, a hybrydy plug-in z silnikiem do 2000 cm³ również.</p>
<ul>
<li><strong>DM-i 400 km, 5 miejsc</strong> — 229 900 juanów, u nas ok. 255 000 zł</li>
<li><strong>EV 900 km, 5 miejsc</strong> — 239 900 juanów, ok. 264 000 zł</li>
<li><strong>DM-i 400 km, 6 miejsc</strong> i <strong>DM-i 350 km 4x4, 5 miejsc</strong> — 249 900 juanów, ok. 273 000 zł</li>
<li><strong>EV 900 km, 6 miejsc</strong> i <strong>EV 800 km 4x4, 5 miejsc</strong> — 259 900 juanów, ok. 282 000 zł</li>
<li><strong>DM-i 350 km 4x4, 6 miejsc</strong> — 269 900 juanów, ok. 291 000 zł</li>
<li><strong>EV 800 km 4x4, 6 miejsc</strong> — 279 900 juanów, ok. 300 000 zł</li>
</ul>
<p>Kwoty zależą od kursu juana i konkretnego egzemplarza — dokładną wycenę przygotowujemy przy zamówieniu.</p>

<h2>Wersja elektryczna czy hybryda plug-in</h2>
<p><strong>Sealion 08 EV</strong> ma baterię LFP o pojemności 115 kWh. Wersja z napędem na tył (370 kW, 500 Nm) przejeżdża do 900 km CLTC i przyspiesza do 100 km/h w 5,9 s. Dwusilnikowa wersja 4x4 ma 585 kW i 810 Nm, 800 km zasięgu CLTC i 3,9 s do setki, przy prędkości maksymalnej 250 km/h.</p>
<p><strong>Sealion 08 DM-i</strong> łączy silnik 1.5 turbo (156 KM) z baterią LFP 55,8 kWh. Wersja z napędem na przód (200 kW) przejeżdża na samym prądzie 400 km CLTC, a łącznie do 1650 km. Wersja 4x4 ma 400 kW i 675 Nm, 350 km na prądzie, 1500 km łącznie i 4,7 s do setki.</p>

<h2>Wymiary i wnętrze: 5 czy 6 miejsc</h2>
<p>Nadwozie ma 5115 mm długości, 1999 mm szerokości i 1800 mm wysokości przy rozstawie osi 3030 mm, a masa własna wynosi od 2445 do 2765 kg zależnie od wersji. Wersje sześciomiejscowe mają w drugim rzędzie dwa fotele kapitańskie z elektryczną regulacją, podłokietnikami i podparciem nóg, a opcjonalnie także duży ekran rozrywkowy na suficie dla pasażerów z tyłu. Kabina w stylistyce Ocean Aesthetics 2.0 utrzymana jest w jasnych barwach, z fizycznymi przyciskami najczęściej używanych funkcji obok pływającego ekranu centralnego.</p>

<h2>Ładowanie i technologia</h2>
<p>Wszystkie wersje mają lidar na dachu i system wspomagania jazdy God's Eye B. Katalog producenta podaje czas szybkiego ładowania 0,15 h, czyli około 9 minut przy ładowarce obsługującej szybkie ładowanie BYD — przy typowej europejskiej ładowarce DC czas będzie dłuższy.</p>

<h2>Sealion 08, Sealion 8, Tang L i Atto 8 — co jest czym</h2>
<p>Sealion 08 to nazwa chińska. W Polsce model często szukany jest jako „Sealion 8” i tak może zostać nazwany w Europie, choć BYD tego jeszcze nie potwierdził. To inny samochód niż BYD Tang L — 7-osobowy SUV z linii Dynasty, którego elektryczną odmianę BYD wprowadza do Europy jako Atto 8. Tang L mamy w ofercie od ręki: <a href="/samochody/byd/tang-l-dm-i/">hybryda Tang L DM-i (Atto 8)</a> i <a href="/samochody/byd/tang-l-ev/">elektryczny Tang L EV (Atto 8)</a>.</p>

<h2>Kiedy BYD Sealion 08 będzie w Polsce</h2>
<p>BYD nie ogłosił daty europejskiej premiery Sealion 08 ani polskiego cennika. Prima-Auto sprowadza go na zamówienie już teraz: ustalamy wersję i kolor, znajdujemy egzemplarz u sprawdzonego sprzedawcy, przechodzimy przez transport morski, odprawę celną i homologację, a auto odbierasz zarejestrowane w Polsce. Cały proces zajmuje zwykle 10–14 tygodni.</p>

<h2>Dla kogo jest Sealion 08</h2>
<p>To propozycja dla rodziny, która szuka dużego SUV-a z bardzo długim zasięgiem — elektrycznego do 900 km albo hybrydy, z którą trasa przez całą Europę nie wymaga planowania postojów. Wersja sześciomiejscowa z fotelami kapitańskimi celuje w komfort pasażerów drugiego rzędu, a cena pozostaje wyraźnie niższa niż europejskich SUV-ów tej wielkości.</p>
HTML;

$S08_FAQ = [
    ['q' => 'Ile kosztuje BYD Sealion 08 w Polsce?', 'a' => 'Orientacyjnie od 255 000 zł za hybrydę DM-i 400 km z 5 miejscami do 300 000 zł za elektryczną wersję 4x4 z 6 miejscami. Kwoty zawierają transport z Chin, cło, VAT, homologację i obsługę Prima-Auto. Ostateczna cena zależy od kursu walut i konkretnego egzemplarza.'],
    ['q' => 'Czy BYD Sealion 08 i Sealion 8 to ten sam samochód?', 'a' => 'Sealion 08 to nazwa chińska, a w Polsce model często szukany jest jako Sealion 8 — pod tą nazwą może trafić do Europy, choć BYD tego nie potwierdził. To inny samochód niż 7-osobowy BYD Tang L, którego BYD wprowadza do Europy jako Atto 8.'],
    ['q' => 'Jaki zasięg ma BYD Sealion 08?', 'a' => 'Wersja elektryczna przejeżdża 800 lub 900 km według chińskiej procedury CLTC, z baterii 115 kWh. Hybryda plug-in przejeżdża na samym prądzie 350 lub 400 km, a łącznie z silnikiem spalinowym do 1650 km.'],
    ['q' => 'Kiedy BYD Sealion 08 będzie dostępny w Polsce?', 'a' => 'BYD nie ogłosił europejskiej premiery ani polskiego cennika. Sealion 08 sprowadzamy na indywidualne zamówienie już teraz, z homologacją i rejestracją w Polsce.'],
    ['q' => 'Ile trwa sprowadzenie BYD Sealion 08 z Chin?', 'a' => 'Zwykle 10–14 tygodni od potwierdzenia zamówienia: wyszukanie egzemplarza, transport morski, odprawa celna, homologacja i rejestracja. Przez cały czas dostajesz od nas zdjęcia i status auta.'],
];

// ------------------------------------------------------------------ walidacja

$bledy = [];
foreach (['DMI' => $DMI_FAQ, 'S08' => $S08_FAQ] as $k => $faq) {
    $j = wp_json_encode($faq, JSON_UNESCAPED_UNICODE);
    if (json_decode($j, true) === null) $bledy[] = "$k: FAQ nie przechodzi json_decode";
    if (preg_match('/[\x{201C}\x{201D}\x{201E}]/u', $j)) $bledy[] = "$k: smart quotes w FAQ";
}
foreach (['DMI' => $DMI_WIKI, 'S08' => $S08_WIKI] as $k => $w) {
    if (substr_count($w, '{{LISTINGS_BAR}}') !== 1) $bledy[] = "$k: LISTINGS_BAR != 1";
}
if (get_term_by('slug', 'sealion-08', 'serie')) $bledy[] = 'slug sealion-08 już istnieje';
foreach (ORPHANS as $o) {
    $t = get_term($o, 'serie');
    if ($t && !is_wp_error($t) && (int) $t->count !== 0) $bledy[] = "sierota $o ma oferty ({$t->count})";
}

// ------------------------------------------------------------------ tytuły ofert hybrydy

global $wpdb;
$ids = $wpdb->get_col($wpdb->prepare(
    "SELECT r.object_id FROM {$wpdb->term_relationships} r JOIN {$wpdb->term_taxonomy} tt USING(term_taxonomy_id) WHERE tt.term_id=%d", TERM_DMI));
$tytuly = [];
foreach ($ids as $id) {
    $p = get_post($id);
    if (!$p || !in_array($p->post_status, ['publish', 'draft'], true)) continue;
    $t = $p->post_title;
    $n = preg_replace('/Sealion 8 DM-I \(Tang L\)/u', 'Tang L DM-i (Atto 8)', $t, 1, $c);
    if (!$c) $n = preg_replace('/\bTang L(?! DM-i)/u', 'Tang L DM-i (Atto 8)', $t, 1, $c);
    if ($n !== $t) $tytuly[(int) $id] = [$t, $n];
}

// ------------------------------------------------------------------ podmiany w innych miejscach

$LINKI = [
    // [typ, id, meta_key|null, stary fragment, nowy fragment]
    ['term', 3700, 'asiaauto_wiki_body',
     'sprzedawanym na eksport jako <a href="https://primaauto.com.pl/samochody/byd/sealion-8-dm-i/">Sealion 8 DM-i</a> i <a href="https://primaauto.com.pl/samochody/byd/sealion-8-ev/">Sealion 8 EV</a>',
     'w Europie wprowadzanym jako Atto 8 — u nas jako <a href="https://primaauto.com.pl/samochody/byd/tang-l-dm-i/">Tang L DM-i (Atto 8)</a> i <a href="https://primaauto.com.pl/samochody/byd/tang-l-ev/">Tang L EV (Atto 8)</a>'],
    ['term', 7244, 'asiaauto_wiki_body',
     'sprzedawanym na eksport jako Sealion 8 — mamy go jako <a href="https://primaauto.com.pl/samochody/byd/sealion-8-dm-i/">Sealion 8 DM-i</a> i <a href="https://primaauto.com.pl/samochody/byd/sealion-8-ev/">Sealion 8 EV</a>',
     'w Europie wprowadzanym jako Atto 8 — mamy go jako <a href="https://primaauto.com.pl/samochody/byd/tang-l-dm-i/">Tang L DM-i (Atto 8)</a> i <a href="https://primaauto.com.pl/samochody/byd/tang-l-ev/">Tang L EV (Atto 8)</a>'],
    ['post', 406167, null,
     'https://primaauto.com.pl/samochody/byd/sealion-8-ev/', 'https://primaauto.com.pl/samochody/byd/tang-l-ev/'],
    ['post', 406167, null,
     'Sealion 8 (Tang L) EV<span', 'Tang L EV (Atto 8)<span'],
];
foreach ($LINKI as [$typ, $id, $mk, $old, $new]) {
    $src = $typ === 'term' ? (string) get_term_meta($id, $mk, true) : (string) get_post_field('post_content', $id, 'raw');
    if (substr_count($src, $old) !== 1) $bledy[] = "$typ $id: fragment występuje " . substr_count($src, $old) . 'x: ' . mb_substr($old, 0, 60);
}

// ------------------------------------------------------------------ hub EV (3703)

$ev_lead = (string) get_term_meta(TERM_EV, '_asiaauto_lead', true);
$ev_wiki = (string) get_term_meta(TERM_EV, 'asiaauto_wiki_body', true);
$ev_faq  = json_decode((string) get_term_meta(TERM_EV, 'asiaauto_faq_json', true), true);

$EV_SEKCJA_OLD_START = '<h2>Sealion 8, Tang L, Atto 8 — to ten sam samochód?</h2>';
$EV_SEKCJA_NEW = "<h2>Tang L EV, Atto 8 i Sealion 08 — co jest czym?</h2>\n"
    . '<p>Tang L EV to nazwa chińska, a Atto 8 — nazwa, pod którą BYD wprowadza ten model do Europy. Nie myl go z BYD Sealion 08, w Polsce często wyszukiwanym jako Sealion 8: to osobny, nowy SUV z serii Ocean, który zadebiutował w Chinach we wrześniu 2026 r. — opisujemy go na stronie <a href="/samochody/byd/sealion-08/">BYD Sealion 08</a>. Hybrydę plug-in Tang L znajdziesz na stronie <a href="/samochody/byd/tang-l-dm-i/">BYD Tang L DM-i (Atto 8)</a>. W Europie BYD jej nie zapowiedział.</p>';

$ev_wiki_new = $ev_wiki;
$p0 = strpos($ev_wiki_new, $EV_SEKCJA_OLD_START);
if ($p0 === false) { $bledy[] = 'EV: brak sekcji „to ten sam samochód"'; }
else {
    $p1 = strpos($ev_wiki_new, '</p>', $p0);
    $ev_wiki_new = substr($ev_wiki_new, 0, $p0) . $EV_SEKCJA_NEW . substr($ev_wiki_new, $p1 + 4);
}
$ev_wiki_new = str_replace(
    'BYD Sealion 8 EV to duży, w pełni elektryczny SUV klasy premium - chińska, elektryczna odmiana modelu znanego jako Tang L.',
    'BYD Tang L EV to duży, w pełni elektryczny SUV klasy premium — elektryczna odmiana modelu Tang L, w Europie wprowadzana jako Atto 8.',
    $ev_wiki_new, $c1);
if ($c1 !== 1) $bledy[] = 'EV: zdanie otwierające nie znalezione';
$ev_wiki_new = str_replace('BYD Sealion 8 EV', 'BYD Tang L EV', $ev_wiki_new);
if (preg_match('/Sealion 8(?!0)/u', strip_tags(str_replace('Sealion 08', '', $ev_wiki_new)), $mm)) {
    // dopuszczalne tylko „wyszukiwanym jako Sealion 8"
    $reszta = preg_replace('/wyszukiwanym jako Sealion 8/u', '', strip_tags(str_replace('Sealion 08', '', $ev_wiki_new)));
    if (preg_match('/Sealion 8/u', $reszta)) $bledy[] = 'EV: w treści został „Sealion 8"';
}
$ev_lead_new = str_replace('BYD Sealion 8 EV, w Europie sprzedawany jako BYD Atto 8 (w Chinach: Tang L EV),', 'BYD Tang L EV, w Europie sprzedawany jako BYD Atto 8,', $ev_lead, $c2);
if ($c2 !== 1) $bledy[] = 'EV: lead nie pasuje';
if (!is_array($ev_faq) || count($ev_faq) !== 5) { $bledy[] = 'EV: FAQ != 5'; }
else {
    foreach ($ev_faq as &$f) {
        $f['q'] = str_replace('BYD Sealion 8 EV', 'BYD Tang L EV', $f['q']);
        $f['a'] = str_replace('BYD Sealion 8 EV', 'BYD Tang L EV', $f['a']);
    }
    unset($f);
    $ev_faq[2] = ['q' => 'Czy BYD Tang L EV to BYD Atto 8?', 'a' => 'Tak. Tang L EV to nazwa chińska, Atto 8 europejska. To inny model niż BYD Sealion 08 z serii Ocean, w Polsce często wyszukiwany jako Sealion 8, i inny niż starszy BYD Tang.'];
}

// ------------------------------------------------------------------ podgląd

printf("Oferty hybrydy do zmiany tytułu: %d\n", count($tytuly));
foreach ($tytuly as $id => [$a, $b]) printf("  %d | %s\n       → %s\n", $id, $a, $b);
printf("EV wiki: %d → %d znaków, lead %s\n", strlen($ev_wiki), strlen($ev_wiki_new), $c2 === 1 ? 'OK' : 'BŁĄD');
printf("Sieroty do skasowania: %s\n", implode(', ', ORPHANS));
if ($bledy) { echo "\nBŁĘDY:\n - " . implode("\n - ", $bledy) . "\n"; }

if ($tryb !== 'apply') { echo "\n(podgląd — nic nie zapisano)\n"; return; }
if ($bledy) { echo "\nSTOP — walidacja nie przeszła, nic nie zapisuję.\n"; return; }

// ------------------------------------------------------------------ zapis

foreach (ORPHANS as $o) {
    $r = wp_delete_term($o, 'serie');
    echo "sierota $o: " . ($r === true ? 'skasowana' : 'BŁĄD') . "\n";
}

// Tang L DM-i
$r = wp_update_term(TERM_DMI, 'serie', ['name' => 'Tang L DM-i', 'slug' => 'tang-l-dm-i']);
echo 'term 3746: ' . (is_wp_error($r) ? 'BŁĄD ' . $r->get_error_message() : 'Tang L DM-i / tang-l-dm-i') . "\n";
update_term_meta(TERM_DMI, '_serie_full_title', 'BYD Tang L DM-i (Atto 8)');
update_term_meta(TERM_DMI, 'rank_math_focus_keyword', 'BYD Tang L DM-i (Atto 8)');
update_term_meta(TERM_DMI, '_asiaauto_lead', $DMI_LEAD);
update_term_meta(TERM_DMI, 'asiaauto_wiki_body', $DMI_WIKI);
update_term_meta(TERM_DMI, 'asiaauto_faq_json', wp_json_encode($DMI_FAQ, JSON_UNESCAPED_UNICODE));
update_term_meta(TERM_DMI, '_asiaauto_seo_rework', 'v1-tang-l-atto8-2026-10-14');

// Tang L EV
$r = wp_update_term(TERM_EV, 'serie', ['name' => 'Tang L EV', 'slug' => 'tang-l-ev']);
echo 'term 3703: ' . (is_wp_error($r) ? 'BŁĄD ' . $r->get_error_message() : 'Tang L EV / tang-l-ev') . "\n";
update_term_meta(TERM_EV, '_asiaauto_lead', $ev_lead_new);
update_term_meta(TERM_EV, 'asiaauto_wiki_body', $ev_wiki_new);
update_term_meta(TERM_EV, 'asiaauto_faq_json', wp_json_encode($ev_faq, JSON_UNESCAPED_UNICODE));

// tytuły ofert hybrydy
foreach ($tytuly as $id => [$a, $b]) {
    $wpdb->update($wpdb->posts, ['post_title' => $b], ['ID' => $id]);
    clean_post_cache($id);
}
echo 'tytuły ofert: ' . count($tytuly) . "\n";

// Sealion 08
$r = wp_insert_term($S08['name'], 'serie', ['slug' => $S08['slug'], 'parent' => S08_MAKE_ID]);
if (is_wp_error($r)) { echo 'Sealion 08: BŁĄD ' . $r->get_error_message() . "\n"; }
else {
    $tid = (int) $r['term_id'];
    update_term_meta($tid, '_serie_full_title', $S08['full']);
    update_term_meta($tid, '_serie_api_value', $S08['api']);
    update_term_meta($tid, '_asiaauto_primary_make_slug', 'byd');
    update_term_meta($tid, '_asiaauto_lead', $S08['lead']);
    update_term_meta($tid, '_asiaauto_h1_suffix', $S08['h1_suffix']);
    update_term_meta($tid, '_asiaauto_pl_availability', 'import_only');
    update_term_meta($tid, 'asiaauto_wiki_body', $S08_WIKI);
    update_term_meta($tid, 'asiaauto_faq_json', wp_json_encode($S08_FAQ, JSON_UNESCAPED_UNICODE));
    update_term_meta($tid, 'asiaauto_seo_desc', $S08['desc']);
    update_term_meta($tid, 'rank_math_title', $S08['title']);
    update_term_meta($tid, 'rank_math_description', $S08['desc']);
    update_term_meta($tid, 'rank_math_focus_keyword', $S08['full']);
    update_term_meta($tid, 'rank_math_robots', ['index']);
    update_term_meta($tid, '_asiaauto_seo_rework', 'v1-premiera-sealion08-2026-10-14');
    update_term_meta($tid, '_asiaauto_skip_title_regen', 1);
    $wl = (array) get_option('asiaauto_hub_index_whitelist', []);
    if (!in_array($tid, array_map('intval', $wl), true)) { $wl[] = $tid; update_option('asiaauto_hub_index_whitelist', array_values($wl)); }
    echo "Sealion 08: term $tid, whitelist OK\n";
}

// linki w innych miejscach
foreach ($LINKI as [$typ, $id, $mk, $old, $new]) {
    if ($typ === 'term') {
        update_term_meta($id, $mk, str_replace($old, $new, (string) get_term_meta($id, $mk, true)));
    } else {
        $c = (string) get_post_field('post_content', $id, 'raw');
        $wpdb->update($wpdb->posts, ['post_content' => str_replace($old, $new, $c)], ['ID' => $id]);
        clean_post_cache($id);
    }
    echo "link: $typ $id OK\n";
}

// tytuły/opisy hubów z ofertami z generatora
foreach ([TERM_DMI, TERM_EV] as $t) {
    $g = AsiaAuto_HubTitleGenerator::regenerateForTerm($t);
    echo "title $t: " . ($g['title'] ?? '?') . "\n";
}
clean_term_cache([TERM_DMI, TERM_EV], 'serie');
echo "\nGotowe.\n";
