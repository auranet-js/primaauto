<?php
/**
 * Nowy hub Xiaomi YU7 GT (prośba Ruslana 15.10.2026: „jak SU7 Ultra — osobny hub z treściami").
 *
 * Oba źródła trzymają GT pod serią YU7 (wersja 四驱GT), więc import rozdziela go od 16.10
 * przez `by_complectation` w brand-mapping-v6.1.php. Ten skrypt: zakłada term, przepina
 * istniejące oferty GT, zapisuje treść huba (wg docs/seo/hub-rework-method-2026-05-30.md,
 * dane z bazy i katalogu Autohome specid 76544) i dokłada link z huba YU7.
 *
 * Użycie: wp eval-file scripts/hub-yu7-gt-2026-10-16.php [apply]
 */

$apply = in_array('apply', $args ?? [], true);
$make_id = 5148;   // Xiaomi
$yu7_id  = 5150;   // YU7
$posts   = [467426, 512651, 521975, 524427, 540550, 553316, 529082]; // 529082 = trash, dla porządku

$lead = 'Xiaomi YU7 GT sprowadzasz do Polski przez Prima-Auto — mamy 6 egzemplarzy w cenach od 406 000 do 485 000 PLN (mediana ok. 450 000). To wyczynowa wersja elektrycznego SUV-a Xiaomi YU7: ok. 1003 KM, 0-100 km/h w 2,9 s i 300 km/h prędkości maksymalnej. Xiaomi nie sprzedaje samochodów w Polsce ani w Europie, więc jedyną drogą do YU7 GT jest import z Chin — z homologacją indywidualną i gwarancją importera.';

$wiki = <<<'HTML'
<p>Xiaomi YU7 GT to najmocniejsza odmiana elektrycznego SUV-a Xiaomi YU7 — dwa silniki o łącznej mocy <strong>738 kW (ok. 1003 KM)</strong>, przyspieszenie 0-100 km/h w <strong>2,92 s</strong> i prędkość maksymalna 300 km/h. W Prima-Auto mamy <strong>6 egzemplarzy</strong> w cenach <strong>od 406 000 do 485 000 PLN</strong> (mediana ok. 450 000), rocznik 2026, z przebiegami od 100 do 2 000 km. Marka Xiaomi nie jest dostępna w Europie — sprowadzamy je z Chin.</p>

{{LISTINGS_BAR}}

<h2>Cena i dostępność Xiaomi YU7 GT w Polsce</h2>
<p>W Prima-Auto ceny Xiaomi YU7 GT wynoszą <strong>od 406 000 do 485 000 PLN</strong> (mediana ok. 450 000) — to ceny końcowe, za auto z homologacją gotowe do rejestracji. <strong>Xiaomi nie ma w Polsce ani w Europie salonów samochodowych</strong>, a YU7 GT nie jest objęty żadną oficjalną sprzedażą poza Chinami, więc jedyną drogą do tego modelu jest import. W Chinach YU7 GT zadebiutował w maju 2026 r. z ceną katalogową 389 900 juanów.</p>
<p>Na cenę końcową składają się: koszt pojazdu (CIF), transport morski, odprawa i agencja celna, cło (10%), akcyza, VAT 23%, homologacja indywidualna i prowizja za pośrednictwo. Ceny na stronie są ostateczne — bez ukrytych dopłat.</p>

<h2>Czym jest Xiaomi YU7 GT i czym różni się od YU7 Max</h2>
<p>YU7 GT jest dla SUV-a YU7 tym, czym SU7 Ultra dla sedana SU7 — wersją nastawioną na osiągi. To nie jest pakiet stylistyczny: GT ma mocniejszy układ napędowy, hamulce z tarczami ceramicznymi i większe koła niż topowy dotąd YU7 Max.</p>
<table>
<thead><tr><th>Parametr</th><th>YU7 GT</th><th>YU7 Max</th></tr></thead>
<tbody>
<tr><td>Moc układu</td><td>738 kW (ok. 1003 KM)</td><td>508 kW (ok. 691 KM)</td></tr>
<tr><td>Moment obrotowy</td><td>1 068 Nm</td><td>866 Nm</td></tr>
<tr><td>0-100 km/h</td><td>2,92 s</td><td>3,23 s</td></tr>
<tr><td>Prędkość maksymalna</td><td>300 km/h</td><td>253 km/h</td></tr>
<tr><td>Zasięg CLTC</td><td>705 km</td><td>760 km</td></tr>
<tr><td>Hamulce</td><td>ceramiczne, wentylowane</td><td>tarczowe, wentylowane</td></tr>
<tr><td>Koła</td><td>21", 265/40 przód, 295/35 tył</td><td>19", 245/55</td></tr>
<tr><td>Cena w Prima-Auto</td><td>406 000-485 000 PLN</td><td>259 000-331 000 PLN</td></tr>
</tbody>
</table>
<p>Obie wersje mają tę samą baterię 101,7 kWh i nadwozie o długości ok. 5 m. Kto szuka szybkiego, ale przede wszystkim rodzinnego SUV-a, znajdzie tańsze YU7 na stronie <a href='/samochody/xiaomi/yu7/'>Xiaomi YU7</a>.</p>

<h2>Osiągi, napęd i dane techniczne</h2>
<p>Napęd tworzą dwa silniki synchroniczne z magnesami trwałymi: z przodu 288 kW, z tyłu 450 kW, razem 738 kW i 1 068 Nm. Zawieszenie to podwójne wahacze z przodu i wielowahacz z tyłu, z dwukomorowym zawieszeniem pneumatycznym o regulowanej twardości i wysokości. Współczynnik oporu powietrza wynosi 0,245.</p>
<ul>
<li>Wymiary: 5 015 × 2 007 × 1 597 mm, rozstaw osi 3 000 mm</li>
<li>Masa własna: 2 460 kg</li>
<li>Bagażnik: 660 l z tyłu i 141 l pod przednią pokrywą</li>
<li>Pięć miejsc, pięcioro drzwi</li>
</ul>

<h2>Zasięg i ładowanie Xiaomi YU7 GT</h2>
<p>YU7 GT jest w pełni elektryczny. Bateria litowo-jonowa NMC od CATL ma pojemność 101,7 kWh, a deklarowany zasięg wynosi 705 km w cyklu CLTC — w realnych warunkach i w ostrzejszym cyklu WLTP będzie odpowiednio niższy. Architektura 800 V pozwala naładować baterię od 10 do 80% w ok. 12 minut na odpowiednio mocnej stacji DC. Auto może też zasilać urządzenia zewnętrzne mocą do 6,6 kW.</p>

<h2>Wyposażenie w egzemplarzach Prima-Auto</h2>
<p>YU7 GT ma bardzo bogate wyposażenie seryjne, m.in.:</p>
<ul>
<li>System wspomagania jazdy Xiaomi HAD z lidarem Hesai (128 linii), 11 kamerami i układem NVIDIA Drive AGX Thor (700 TOPS)</li>
<li>Adaptacyjny tempomat, asystent pasa ruchu, kamera 360 stopni, automatyczne parkowanie</li>
<li>Ekran centralny 16,1" 3K, ekran dla pasażerów z tyłu, Apple CarPlay</li>
<li>Fotele przednie i tylne podgrzewane, wentylowane i z masażem, fotele przednie w trybie „zero gravity"</li>
<li>Nagłośnienie z 25 głośnikami i Dolby Atmos, oświetlenie ambientowe w 256 kolorach</li>
<li>Matrycowe reflektory LED, panoramiczny dach, ładowarka indukcyjna 80 W</li>
</ul>
<p>Pełną listę wyposażenia każdego auta widać w jego ofercie.</p>

<h2>Xiaomi YU7 GT w Prima-Auto — czego się spodziewać</h2>
<p>Nasze egzemplarze YU7 GT to auta praktycznie nowe — rocznik 2026, importowane z Chin, z przebiegami od 100 do 2 000 km. To pojazdy demonstracyjne i poekspozycyjne, nie klasyczne auta używane. Każdy pojazd jest oglądany fizycznie przez nasz zespół w Chinach przed zakupem — sprawdzamy stan techniczny, kondycję baterii, lakier i dokumentację.</p>

<h2>Import Xiaomi YU7 GT przez Prima-Auto</h2>
<p>Proces trwa zwykle 8-12 tygodni: rezerwacja pojazdu (zwrotny depozyt) → weryfikacja fizyczna auta w Chinach przez nasz zespół → dokumentacja eksportowa → transport morski (6-8 tygodni) → odprawa celna w Polsce → homologacja indywidualna (badania świateł, emisji, VIN) → rejestracja → odbiór auta z kompletem dokumentów do rejestracji. Ceny na stronie są końcowe: pojazd, transport, cło, VAT 23%, homologacja i rejestracja są już wliczone — bez dopłat. Wszystkie formalności załatwiamy my, klient płaci tylko ustaloną kwotę. <a href='/samochody/?marka=xiaomi&amp;model=yu7-gt'>Wszystkie egzemplarze Xiaomi YU7 GT</a> | <a href='/samochody/xiaomi/su7-ultra/'>Xiaomi SU7 Ultra</a> | <a href='/samochody/?marka=xiaomi'>Wszystkie modele Xiaomi</a></p>
HTML;

$faq = [
    ['q' => 'Ile kosztuje Xiaomi YU7 GT w Polsce?',
     'a' => 'W Prima-Auto Xiaomi YU7 GT kosztuje od 406 000 do 485 000 PLN, z medianą ok. 450 000 PLN. To cena końcowa za auto z homologacją indywidualną, gotowe do rejestracji w Polsce — transport, cło, akcyza, VAT i homologacja są wliczone.'],
    ['q' => 'Kiedy Xiaomi YU7 GT będzie dostępny w Polsce?',
     'a' => 'Xiaomi nie sprzedaje samochodów w Polsce ani w Europie i nie ma tu salonów. YU7 GT jest dostępny wyłącznie z importu z Chin — Prima-Auto sprowadza go już teraz, z homologacją indywidualną i gwarancją importera. Proces trwa zwykle 8-12 tygodni.'],
    ['q' => 'Czym różni się Xiaomi YU7 GT od YU7 Max?',
     'a' => 'YU7 GT ma moc ok. 1003 KM (YU7 Max ok. 691 KM), przyspiesza do 100 km/h w 2,92 s zamiast 3,23 s i osiąga 300 km/h zamiast 253 km/h. Dostaje też hamulce ceramiczne i koła 21 cali. Bateria jest ta sama (101,7 kWh), ale zasięg CLTC GT jest nieco krótszy: 705 km wobec 760 km.'],
    ['q' => 'Czy istnieje Xiaomi YU7 Ultra?',
     'a' => 'Nie. Nazwę Ultra Xiaomi nadało tylko sedanowi SU7 Ultra. Wyczynową wersją SUV-a YU7 jest właśnie YU7 GT — ok. 1003 KM, 0-100 km/h w 2,92 s, 300 km/h. Jeśli szukasz najmocniejszego YU7, chodzi o wersję GT.'],
    ['q' => 'Jaki zasięg i ładowanie ma Xiaomi YU7 GT?',
     'a' => 'YU7 GT ma baterię 101,7 kWh od CATL i zasięg 705 km w cyklu CLTC; w realnej jeździe i wg WLTP będzie niższy. Dzięki architekturze 800 V ładowanie od 10 do 80% trwa ok. 12 minut na odpowiednio mocnej stacji DC.'],
];

// --- self-check danych z bazy (6 opublikowanych ofert, ceny) ---
$prices = [];
foreach ($posts as $pid) {
    if (get_post_status($pid) === 'publish') $prices[] = (int) get_post_meta($pid, 'price', true);
}
sort($prices);
WP_CLI::log('Opublikowane: ' . count($prices) . ', ceny: ' . implode(', ', $prices));
if (count($prices) !== 6 || $prices[0] !== 406000 || end($prices) !== 485000) {
    WP_CLI::error('Dane w bazie nie zgadzają się z treścią — przerwane.');
}
if (!preg_match('/[ąćęłńóśźż]/u', wp_json_encode($faq, JSON_UNESCAPED_UNICODE))) WP_CLI::error('FAQ bez diakrytyków');
foreach ($faq as $f) {
    if (preg_match('/[\x{201C}\x{201D}\x{201E}]/u', $f['q'] . $f['a'])) WP_CLI::error('Smart quotes w FAQ');
}

if (!$apply) { WP_CLI::success('Dry-run OK. Uruchom z argumentem apply.'); return; }

// --- 1. term ---
$existing = get_term_by('slug', 'yu7-gt', 'serie');
if ($existing) {
    $term_id = (int) $existing->term_id;
} else {
    $r = wp_insert_term('YU7 GT', 'serie', ['slug' => 'yu7-gt', 'parent' => $make_id]);
    if (is_wp_error($r)) WP_CLI::error($r->get_error_message());
    $term_id = (int) $r['term_id'];
}
WP_CLI::log("Term YU7 GT: {$term_id}");

// --- 2. przepięcie ofert (taxonomy + dual storage meta `serie`) ---
foreach ($posts as $pid) {
    wp_set_object_terms($pid, [$term_id], 'serie', false);
    update_post_meta($pid, 'serie', 'yu7-gt');
    clean_post_cache($pid);
}
wp_update_term_count_now([$term_id, $yu7_id], 'serie');

// --- 3. treść i meta huba ---
$meta = [
    '_asiaauto_primary_make_slug' => 'xiaomi',
    '_serie_full_title'           => 'Xiaomi YU7 GT',
    '_serie_api_value'            => 'Xiaomi YU7 GT',
    '_asiaauto_lead'              => $lead,
    '_asiaauto_h1_suffix'         => 'cena w Polsce i import z Chin',
    '_asiaauto_pl_availability'   => 'import_only',
    'asiaauto_wiki_body'          => $wiki,
    'asiaauto_faq_json'           => wp_json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'rank_math_focus_keyword'     => 'Xiaomi YU7 GT',
    '_asiaauto_seo_rework'        => 'v1-2026-10-16',
];
foreach ($meta as $k => $v) update_term_meta($term_id, $k, $v);

// --- 4. pochodne: specyfikacja + title/description (oba huby, bo zmienił się count YU7) ---
WP_CLI::log('Spec snapshot: ' . (AsiaAuto_Spec::saveSnapshot($term_id) ? 'ok' : 'brak'));
WP_CLI::log('Title YU7 GT: ' . wp_json_encode(AsiaAuto_HubTitleGenerator::regenerateForTerm($term_id), JSON_UNESCAPED_UNICODE));
WP_CLI::log('Title YU7: ' . wp_json_encode(AsiaAuto_HubTitleGenerator::regenerateForTerm($yu7_id), JSON_UNESCAPED_UNICODE));

// --- 5. link z huba YU7 (tam lądują dziś frazy GT) ---
$yu7_wiki = (string) get_term_meta($yu7_id, 'asiaauto_wiki_body', true);
$needle = '<li><strong>YU7 Max</strong> (4WD) — topowa wersja, ok. 690 KM, 0-100 w ok. 3,2 s, bateria 101,7 kWh.</li>';
if (strpos($yu7_wiki, 'yu7-gt') === false && strpos($yu7_wiki, $needle) !== false) {
    $add = "\n<li><strong>YU7 GT</strong> (4WD) — wyczynowa wersja, ok. 1003 KM, 0-100 w 2,9 s, 300 km/h. Ma osobną stronę: <a href='/samochody/xiaomi/yu7-gt/'>Xiaomi YU7 GT</a>.</li>";
    update_term_meta($yu7_id, 'asiaauto_wiki_body', str_replace($needle, $needle . $add, $yu7_wiki));
    WP_CLI::log('Link z huba YU7 dodany.');
} else {
    WP_CLI::warning('Link z huba YU7 pominięty (już jest albo nie znaleziono miejsca).');
}

WP_CLI::success("Hub: " . get_term_link($term_id, 'serie'));
