<?php
/**
 * Treść huba Wuling Huajing S (term 7254, nowy 11.09) + aktualizacja huba marki Wuling (term 4442) — v1-2026-09-11.
 *
 * Dane: extra_prep ofert 474792 / 474802 / 474818 (katalog Autohome spec 77192 / 77162 / 74598).
 * Fakty spoza bazy (premiera 8.05.2026, pierwszy model SGMW z Huawei, ADS Pro + HarmonySpace w standardzie,
 * cennik 159 800–203 800 CNY, dostawy VI 5689 / VII 7203) — CnEVPost, GM Authority, Gasgoo, sina.cn.
 * Hub Wuling: dotąd opisywał jedną ofertę (Yangguang) — po publikacji Huajing S lead, intro i FAQ kłamałyby.
 * Metoda: docs/seo/hub-rework-method-2026-05-30.md.
 *
 * Użycie: wp eval-file <plik>          # walidacja, nic nie zapisuje
 *         wp eval-file <plik> apply    # zapis treści + publikacja 3 draftów + regeneracja tytułów
 */

$apply = (($args[0] ?? '') === 'apply');
$TID   = 7254;   // serie: Huajing S (parent Wuling)
$MID   = 4442;   // make: Wuling
$POSTY = [474792, 474802, 474818];

// ───────────────────────── HUB HUAJING S ─────────────────────────

$full_title = 'Wuling Huajing S';
$h1_suffix  = 'cena w Polsce i import z Chin';
$availability = 'import_only';

$lead = 'Wuling Huajing S kosztuje w Prima-Auto od 197 000 do 210 000 PLN brutto z pełnym importem z Chin — to duży, sześciomiejscowy SUV hybrydowy plug-in o długości 5235 mm, pierwszy model SAIC-GM-Wuling opracowany razem z Huawei, z systemem wspomagania jazdy Huawei Qiankun ADS Pro i kokpitem HarmonySpace w standardzie. Do sprzedaży w Chinach wszedł w maju 2026 roku i szybko zdobył klientów: w lipcu odebrali 7203 sztuki. W Polsce nie ma go w salonach, więc jedyną drogą jest import indywidualny — mamy 3 egzemplarze z 2026 roku, z przebiegiem od 10 km.';

$wiki = <<<'HTML'
<p>Wuling Huajing S to duży, sześciomiejscowy SUV z napędem hybrydowym plug-in, który SAIC-GM-Wuling przygotował wspólnie z Huawei. Premiera rynkowa odbyła się 8 maja 2026 roku i był to pierwszy samochód tego producenta z technologiami Huawei na pokładzie. Nadwozie ma 5235 mm długości, 1999 mm szerokości i 1800 mm wysokości, a rozstaw osi wynosi 3105 mm — to wymiary pełnowymiarowego SUV-a z trzema rzędami siedzeń. Chiński cennik obejmuje cztery wersje od 159 800 do 203 800 CNY. W ofercie Prima-Auto mamy trzy egzemplarze z 2026 roku w cenach od 197 000 do 210 000 PLN brutto z kompletnym importem.</p>{{LISTINGS_BAR}}<h2>Ile kosztuje Wuling Huajing S w Polsce</h2><p>Nasze egzemplarze kosztują od 197 000 do 210 000 PLN brutto i jest to kwota kompletna: zakup w Chinach, transport morski, agencja celna, cło 10 procent, transport z portu, homologacja indywidualna, VAT 23 procent oraz prowizja Prima-Auto. Nie ma dopłat na końcu procesu — cena jest znana przed startem i zapisana w umowie. Najtańszy jest egzemplarz w wersji 255 km z napędem na przednią oś i przebiegiem 9000 km (197 000 PLN), praktycznie nowy egzemplarz tej samej wersji z przebiegiem 10 km kosztuje 208 000 PLN, a najmocniejsza wersja 235 km z napędem na cztery koła — 210 000 PLN. Dla porównania ceny katalogowe tych wersji w Chinach to 175 800 CNY, 185 800 CNY i 203 800 CNY.</p><h2>Wuling, Baojun czy Huajing — kto produkuje ten SUV</h2><p>Producentem jest SAIC-GM-Wuling, chińska spółka joint venture SAIC Motor, General Motors i Liuzhou Wuling — ta sama, która stoi za marką Wuling. W chińskim katalogu Autohome Huajing (华境) występuje jako osobna marka, a w części zachodnich mediów model pojawia się jako Baojun Huajing S. Wszystkie te nazwy oznaczają ten sam samochód. My prowadzimy go pod marką Wuling, bo tak jest najczęściej wyszukiwany i tak łatwiej go znaleźć obok innych modeli producenta. Huajing S powstał z myślą o rynku chińskim i nie jest oferowany w polskich salonach, więc jedyną drogą do niego w Polsce jest import indywidualny.</p><h2>Napęd plug-in — do 255 km na prądzie i ponad 1000 km łącznie</h2><p>Pod maską pracuje benzynowy silnik 1.5 turbo o mocy 143 KM i momencie 220 Nm, połączony z jednobiegową przekładnią hybrydową DHT i silnikiem elektrycznym. W wersji z napędem na przednią oś silnik elektryczny ma 200 kW, czyli 272 KM, i 310 Nm, co daje przyspieszenie do 100 km/h w 8,6 sekundy. Wersja z napędem na cztery koła dostaje drugi silnik elektryczny na tylnej osi o mocy 186 kW — łącznie 386 kW, czyli 525 KM, i 620 Nm — i przyspiesza do setki w 5,2 sekundy. Bateria to ogniwa LFP firmy CATL o pojemności 41,9 kWh z chłodzeniem cieczą. Na samym prądzie wersja przednionapędowa przejeżdża 255 km w cyklu CLTC i 175 km w cyklu WLTC, a czteronapędowa 235 km CLTC i 160 km WLTC. Z pełnym zbiornikiem 60 litrów zasięg łączny wynosi 1145 km w cyklu WLTC w wersji przednionapędowej i 1020 km w wersji 4WD. Szybkie ładowanie od 10 do 70 procent trwa około 14 minut, a ładowanie wolne około 6 godzin. Prędkość maksymalna to 190 km/h.</p><h2>Sześć miejsc, wymiary i przestrzeń</h2><p>Kabina jest ułożona pod sześć osób w trzech rzędach, w nadwoziu pięciodrzwiowym. Bagażnik ma od 423 do 1268 litrów, zależnie od ustawienia tylnych siedzeń. Masa własna to 2470 kg w wersji przednionapędowej i 2665 kg w wersji 4WD, a dopuszczalna masa całkowita odpowiednio 3045 i 3175 kg. Zawieszenie jest niezależne na obu osiach — podwójne wahacze z przodu i układ wielowahaczowy z tyłu — a auto stoi na kołach 21-calowych z oponami 265/45 R21. Mimo długości ponad 5,2 metra promień skrętu wynosi 5,65 m. W wyposażeniu egzemplarzy są między innymi elektrycznie otwierana klapa bagażnika, bezkluczykowe uruchamianie, podgrzewana kierownica, osobna klimatyzacja dla tylnej części kabiny z nawiewami, filtr PM2,5, wbudowany rejestrator jazdy oraz aktualizacje oprogramowania przez sieć.</p><h2>Huawei w standardzie — wspomaganie jazdy i kokpit</h2><p>Najważniejszym wyróżnikiem Huajing S jest pakiet Huawei montowany w każdej wersji: system wspomagania jazdy Huawei Qiankun ADS Pro oraz kokpit HarmonySpace z usługami chmurowymi Huawei. To te same rodziny rozwiązań, które Huawei dostarcza do droższych chińskich SUV-ów. Wśród funkcji egzemplarzy są aktywne hamowanie awaryjne, asystent utrzymania pasa ruchu z ostrzeganiem o jego opuszczeniu, automatyczne parkowanie, adaptacyjne światła i elektroniczny hamulec postojowy z funkcją auto hold. Warto pamiętać, że oprogramowanie i usługi pokładowe są przygotowane pod rynek chiński — nawigacja, asystent głosowy i część usług chmurowych działają w pełni w Chinach, a w Polsce ich zakres zależy od konfiguracji po imporcie.</p><h2>Huajing S na tle innych dużych SUV-ów z Chin</h2><p>Sześciomiejscowe SUV-y z Chin to jedna z najliczniejszych grup w naszej ofercie, a Huajing S wpisuje się w nią długością 5235 mm i ceną od 197 000 PLN. <a href="https://primaauto.com.pl/samochody/li-auto/l8/">Li Auto L8</a> zaczyna się w naszej ofercie od 210 000 PLN, <a href="https://primaauto.com.pl/samochody/li-auto/l9/">Li Auto L9</a> od 267 000 PLN, a <a href="https://primaauto.com.pl/samochody/aito/m8/">AITO M8</a> — również z technologią Huawei, ale z wyższej półki — od 301 000 PLN. Z technologią Huawei i w wersjach sześciomiejscowych dostępny jest też <a href="https://primaauto.com.pl/samochody/aito/m7/">AITO M7</a>. Najbliżej rozmiarem jest <a href="https://primaauto.com.pl/samochody/leapmotor/leapmotor-d19/">Leapmotor D19</a>, sześciomiejscowy SUV w cenach od 242 000 PLN. Jeśli sześć miejsc nie jest konieczne, a zależy Ci na systemie Huawei Qiankun, zobacz też <a href="https://primaauto.com.pl/samochody/voyah/free/">Voyah FREE</a>. Pełną ofertę producenta znajdziesz na <a href="https://primaauto.com.pl/samochody/wuling/">stronie marki Wuling</a>.</p><h2>Import Wulinga Huajing S przez Prima-Auto</h2><p>Mamy trzy egzemplarze z 2026 roku z prowincji Guangdong: wersję 255 km z przebiegiem 10 km z Shenzhen, wersję 255 km z przebiegiem 9000 km z Jiangmen oraz wersję 235 km z napędem na cztery koła i przebiegiem 100 km z Shenzhen. Prowadzimy cały proces: weryfikację egzemplarza na miejscu w Chinach, zakup, transport morski, odprawę celną, homologację indywidualną, tłumaczenia dokumentów i przygotowanie do rejestracji w Polsce. Od podpisania umowy do odbioru w Rzeszowie mija zwykle od 10 do 14 tygodni. Rozliczenie opiera się na zwrotnym depozycie gwarancyjnym i jasno określonej prowizji. Fabryczna gwarancja producenta — sześć lat lub 150 tysięcy kilometrów na auto i osiem lat lub 150 tysięcy kilometrów na baterię — obowiązuje na rynku chińskim; w Polsce auto obejmuje gwarancja importera, a serwis warto zaplanować w warsztacie przygotowanym do obsługi wysokiego napięcia. Jeśli szukasz innej wersji Huajing S, sprowadzamy na zamówienie.</p>
HTML;

$faq = [
    ['q' => 'Ile kosztuje Wuling Huajing S w Polsce?',
     'a' => 'W Prima-Auto Huajing S kosztuje od 197 000 do 210 000 PLN brutto, zależnie od wersji i przebiegu. Cena zawiera pełny import: zakup w Chinach, transport morski, agencję celną, cło 10 procent, transport z portu, homologację indywidualną, VAT 23 procent i prowizję Prima-Auto. Ceny katalogowe w Chinach to od 159 800 do 203 800 CNY w czterech wersjach. Kwota jest ostateczna, bez dopłat na końcu procesu.'],
    ['q' => 'Jaki zasięg ma Huajing S na prądzie i łącznie?',
     'a' => 'Bateria LFP firmy CATL o pojemności 41,9 kWh wystarcza na 255 km w cyklu CLTC i 175 km w cyklu WLTC w wersji z napędem na przód oraz na 235 km CLTC i 160 km WLTC w wersji 4WD. Z silnikiem benzynowym 1.5 turbo i zbiornikiem 60 litrów zasięg łączny wynosi 1145 km WLTC w wersji przednionapędowej i 1020 km w wersji 4WD. Szybkie ładowanie od 10 do 70 procent trwa około 14 minut.'],
    ['q' => 'Kto produkuje Huajing S — Wuling czy Baojun?',
     'a' => 'Producentem jest SAIC-GM-Wuling, spółka joint venture SAIC Motor, General Motors i Liuzhou Wuling. W chińskim katalogu Autohome Huajing występuje jako osobna marka, a w części zachodnich mediów model nazywany jest Baojun Huajing S. To ten sam samochód, opracowany razem z Huawei. W Prima-Auto prowadzimy go pod marką Wuling.'],
    ['q' => 'Jakie technologie Huawei ma Huajing S?',
     'a' => 'Każda wersja ma w standardzie system wspomagania jazdy Huawei Qiankun ADS Pro oraz kokpit HarmonySpace z usługami chmurowymi Huawei. Egzemplarze mają też aktywne hamowanie awaryjne, asystenta utrzymania pasa ruchu, automatyczne parkowanie i adaptacyjne światła. Oprogramowanie jest przygotowane pod rynek chiński, więc część usług pokładowych działa w Polsce w ograniczonym zakresie.'],
    ['q' => 'Czy Huajing S jest dostępny w polskich salonach?',
     'a' => 'Nie. Huajing S powstał z myślą o rynku chińskim i nie jest oferowany w polskich salonach, dlatego jedyną drogą do niego jest import indywidualny. Prima-Auto prowadzi cały proces, od weryfikacji egzemplarza w Chinach po homologację indywidualną, a odbiór w Rzeszowie następuje zwykle po 10 do 14 tygodniach. Gwarancja fabryczna obowiązuje w Chinach, a sprowadzone auto obejmuje gwarancja importera.'],
];

// ───────────────────────── HUB MARKI WULING — punktowe podmiany ─────────────────────────

$w_lead = 'Wulinga sprowadzisz do Polski przez Prima-Auto — mamy 4 auta tej marki: 3 egzemplarze dużego, sześciomiejscowego SUV-a hybrydowego Huajing S, opracowanego razem z Huawei, w cenach od 197 000 do 210 000 PLN, oraz elektrycznego vana Yangguang za 120 000 PLN. Wuling to marka koncernu SAIC-GM-Wuling, jednego z największych producentów aut na świecie (twórca Hongguang Mini EV), ale w Polsce nie ma żadnego salonu ani sieci serwisowej. Indywidualny import z Chin to jedyna droga, by jeździć Wulingiem już dziś — z homologacją indywidualną i gwarancją importera.';

// [szukany fragment => zamiennik]; każdy musi wystąpić w wiki DOKŁADNIE raz, inaczej STOP
$w_wiki_repl = [
    'W Prima-Auto mamy <strong>1 ogłoszenie</strong> auta Wuling — model <strong>Yangguang</strong> (elektryczny van/MPV) — w cenie <strong>115 000 PLN</strong>, rocznik <strong>2025</strong>. Auto sprowadzamy z Chin, z homologacją indywidualną i gwarancją importera. Poniżej znajdziesz aktualnie dostępną sztukę:'
        => 'W Prima-Auto mamy <strong>4 auta</strong> Wuling: 3 egzemplarze dużego SUV-a hybrydowego <strong>Huajing S</strong> z 2026 roku w cenach od <strong>197 000 do 210 000 PLN</strong> oraz elektrycznego vana <strong>Yangguang</strong> z 2025 roku za <strong>120 000 PLN</strong>. Auta sprowadzamy z Chin, z homologacją indywidualną i gwarancją importera. Poniżej znajdziesz aktualnie dostępne sztuki:',
    '<h2>Wuling Yangguang — elektryczny van/MPV</h2>'
        => '<h2>Wuling Huajing S — duży SUV z technologią Huawei</h2><p><a href="https://primaauto.com.pl/samochody/wuling/huajing-s/">Wuling Huajing S</a> to sześciomiejscowy SUV hybrydowy plug-in o długości 5235 mm, pierwszy model SAIC-GM-Wuling opracowany razem z Huawei — z systemem wspomagania jazdy Huawei Qiankun ADS Pro i kokpitem HarmonySpace w każdej wersji. Do sprzedaży w Chinach wszedł w maju 2026 roku, a w lipcu klienci odebrali 7203 sztuki. Bateria 41,9 kWh wystarcza na 255 km jazdy na prądzie w cyklu CLTC, a zasięg łączny przekracza 1000 km w cyklu WLTC; wersja z napędem na cztery koła ma 525 KM i przyspiesza do setki w 5,2 sekundy. W naszej ofercie kosztuje od 197 000 do 210 000 PLN z pełnym importem. W części zachodnich mediów model występuje jako Baojun Huajing S.</p><h2>Wuling Yangguang — elektryczny van/MPV</h2>',
    'Wuling Yangguang w naszej ofercie wyceniony jest na <strong>115 000 PLN</strong>.'
        => 'Wuling Huajing S kosztuje w naszej ofercie od <strong>197 000 do 210 000 PLN</strong>, a Wuling Yangguang <strong>120 000 PLN</strong>.',
    'W ofercie Prima-Auto Wuling reprezentowany jest przez model <strong>Yangguang</strong>'
        => 'Drugim Wulingiem w ofercie Prima-Auto jest model <strong>Yangguang</strong>',
];

$w_faq_repl = [
    0 => ['q' => 'Ile kosztuje sprowadzenie Wulinga do Polski?',
          'a' => 'W ofercie Prima-Auto Wuling Huajing S kosztuje od 197 000 do 210 000 PLN, a Wuling Yangguang 120 000 PLN. Są to ceny pod klucz, obejmujące zakup w Chinach, transport, cło, podatki oraz homologację indywidualną. Nie doliczamy ukrytych opłat na koniec procesu.'],
];
$w_faq_add = ['q' => 'Czym jest Wuling Huajing S?',
              'a' => 'Huajing S to duży, sześciomiejscowy SUV hybrydowy plug-in, pierwszy model SAIC-GM-Wuling opracowany razem z Huawei. Ma 5235 mm długości, baterię 41,9 kWh na 255 km jazdy na prądzie w cyklu CLTC, a w standardzie system wspomagania jazdy Huawei Qiankun ADS Pro i kokpit HarmonySpace. W Chinach wszedł do sprzedaży w maju 2026 roku.'];

// ───────────────────────── walidacja ─────────────────────────
$stop = false;
$chk = function (string $label, string $txt) use (&$stop) {
    if (!preg_match('/[ąćęłńóśźż]/u', $txt)) { echo "!! $label bez diakrytyk — STOP\n"; $stop = true; }
};
foreach ($faq as $qa) if (strpos($qa['q'] . $qa['a'], '"') !== false) { echo "!! cudzysłów w FAQ Huajing — STOP\n"; $stop = true; }
$json = json_encode($faq, JSON_UNESCAPED_UNICODE);
if (json_decode($json, true) === null) { echo "!! FAQ Huajing nie parsuje — STOP\n"; $stop = true; }
$chk('FAQ Huajing', $json); $chk('wiki Huajing', $wiki); $chk('lead Huajing', $lead); $chk('lead Wuling', $w_lead);
if (strpos($wiki, '{{LISTINGS_BAR}}') === false) { echo "!! brak {{LISTINGS_BAR}} — STOP\n"; $stop = true; }
if (preg_match('/[„”“]/u', $json)) { echo "!! typograficzny cudzysłów w FAQ — STOP\n"; $stop = true; }

$term = get_term($TID, 'serie');
if (!$term || is_wp_error($term) || (int) $term->parent !== $MID) { echo "!! term $TID nie istnieje lub nie pod Wulingiem — STOP\n"; $stop = true; }

$w_wiki = (string) get_term_meta($MID, 'asiaauto_wiki_body', true);
foreach ($w_wiki_repl as $from => $to) {
    $n = substr_count($w_wiki, $from);
    if ($n !== 1) { echo "!! Wuling wiki: fragment występuje $n razy — STOP: " . mb_substr($from, 0, 60) . "\n"; $stop = true; continue; }
    $w_wiki = str_replace($from, $to, $w_wiki);
}
$w_faq = json_decode((string) get_term_meta($MID, 'asiaauto_faq_json', true), true);
if (!is_array($w_faq) || ($w_faq[0]['q'] ?? '') !== $w_faq_repl[0]['q']) { echo "!! Wuling FAQ[0] nie zgadza się — STOP\n"; $stop = true; }
else { $w_faq[0] = $w_faq_repl[0]; array_splice($w_faq, 1, 0, [$w_faq_add]); }
$w_json = json_encode($w_faq, JSON_UNESCAPED_UNICODE);
if (strpos(json_encode([$w_faq_repl, $w_faq_add], JSON_UNESCAPED_UNICODE), '\"') !== false) { echo "!! cudzysłów w FAQ Wuling — STOP\n"; $stop = true; }

foreach ($POSTY as $pid) {
    $st = get_post_status($pid);
    $terms = wp_get_object_terms($pid, 'serie', ['fields' => 'ids']);
    if ($st !== 'draft' && $st !== 'publish') { echo "!! post $pid status $st — STOP\n"; $stop = true; }
    if (!in_array($TID, (array) $terms, true)) { echo "!! post $pid nie pod termem $TID — STOP\n"; $stop = true; }
}

printf("-- Huajing: wiki %d H2, %d znaków | lead %d | FAQ %d\n", substr_count($wiki, '<h2>'), mb_strlen(wp_strip_all_tags($wiki)), mb_strlen($lead), count($faq));
printf("-- Wuling: wiki %d H2 po zmianie | FAQ %d pytań | lead %d\n", substr_count($w_wiki, '<h2>'), is_array($w_faq) ? count($w_faq) : 0, mb_strlen($w_lead));
if ($stop) { echo "PRZERWANE, nic nie zapisano.\n"; return; }
if (!$apply) { echo "WALIDACJA OK — nic nie zapisane. Zapis: dopisz 'apply'\n"; return; }

// ───────────────────────── zapis ─────────────────────────
$bk = '/home/host476470/backups/primaauto/2026-09-11/termmeta-przed-huajing-tresc.json';
file_put_contents($bk, wp_json_encode([$TID => get_term_meta($TID), $MID => get_term_meta($MID)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "backup termmeta -> $bk\n";

update_term_meta($TID, '_serie_full_title', $full_title);
update_term_meta($TID, '_asiaauto_lead', $lead);
update_term_meta($TID, '_asiaauto_h1_suffix', $h1_suffix);
update_term_meta($TID, '_asiaauto_pl_availability', $availability);
update_term_meta($TID, 'asiaauto_wiki_body', wp_kses_post($wiki));
update_term_meta($TID, 'asiaauto_faq_json', $json);
update_term_meta($TID, '_asiaauto_seo_rework', 'v1-2026-09-11');

update_term_meta($MID, '_asiaauto_lead', $w_lead);
update_term_meta($MID, 'asiaauto_wiki_body', wp_kses_post($w_wiki));
update_term_meta($MID, 'asiaauto_faq_json', $w_json);
clean_term_cache([$TID, $MID]);

foreach ($POSTY as $pid) {
    if (get_post_status($pid) === 'draft') wp_update_post(['ID' => $pid, 'post_status' => 'publish']);
    printf("post %d -> %s %s\n", $pid, get_post_status($pid), get_permalink($pid));
}

if (class_exists('AsiaAuto_HubTitleGenerator')) {
    AsiaAuto_HubTitleGenerator::regenerateForTerm($TID);
    AsiaAuto_HubTitleGenerator::regenerateForMakeTerm($MID);
    printf("title huba  -> %s\ntitle marki -> %s\n", get_term_meta($TID, 'rank_math_title', true), get_term_meta($MID, 'rank_math_title', true));
}
if (class_exists('AsiaAuto_Spec')) AsiaAuto_Spec::saveSnapshot($TID);
echo "ZAPISANE.\n";
