<?php
/**
 * Treść 5 hubów Wuling Starlight (terms 7255–7259, nowe 11.09) + aktualizacja huba marki Wuling (4442) — v1-2026-09-11.
 *
 * Dane: extra_prep 11 ofert (katalog Autohome przez che168), ceny PLN z bazy, porównania z ofertą Prima-Auto
 * (zapytania 11.09: najtańsze MPV poza Wulingiem to Maxus G90 159 000 i BYD Xia 188 000 PLN).
 * Metoda: docs/seo/hub-rework-method-2026-05-30.md. Treść wg metody idzie bez akceptu (feedback 11.09).
 *
 * Użycie: wp eval-file <plik>          # walidacja
 *         wp eval-file <plik> apply    # zapis + publikacja 11 draftów + tytuły + snapshoty
 */

$apply = (($args[0] ?? '') === 'apply');
$MID = 4442;
$U = 'https://primaauto.com.pl/samochody/';

// Wspólne akapity — ten sam boilerplate co w pozostałych hubach (Rzeszów, all-inclusive, 10–14 tygodni).
$cena_zawiera = 'zakup w Chinach, transport morski, agencja celna, cło 10 procent, transport z portu, homologacja indywidualna, VAT 23 procent oraz prowizja Prima-Auto';
$proces = 'Prowadzimy cały proces: weryfikację egzemplarza na miejscu w Chinach, zakup, transport morski, odprawę celną, homologację indywidualną, tłumaczenia dokumentów i przygotowanie do rejestracji w Polsce. Od podpisania umowy do odbioru w Rzeszowie mija zwykle od 10 do 14 tygodni. Rozliczenie opiera się na zwrotnym depozycie gwarancyjnym i jasno określonej prowizji.';
$gwarancja = 'Fabryczna gwarancja producenta — sześć lat lub 150 tysięcy kilometrów na auto i osiem lat lub 150 tysięcy kilometrów na baterię — obowiązuje na rynku chińskim; w Polsce auto obejmuje gwarancja importera.';
$kto = 'Producentem jest SAIC-GM-Wuling, chińska spółka joint venture SAIC Motor, General Motors i Liuzhou Wuling, z siedzibą w Liuzhou w prowincji Guangxi. Rodzina Starlight (po chińsku Xingguang, 星光) to seria modeli Wulinga z napędem elektrycznym i hybrydowym plug-in, a w części modeli także spalinowym — w jej skład wchodzą sedan Starlight, SUV-y Starlight S, Starlight L i Starlight 560 oraz van Starlight 730. Wuling nie prowadzi w Polsce salonów ani serwisu, więc jedyną drogą do tych aut jest import indywidualny.';
$link_rodzina = function (string $bez) use ($U) {
    $all = ['starlight' => 'Starlight (sedan)', 'starlight-s' => 'Starlight S (kompaktowy SUV)', 'starlight-l' => 'Starlight L (sześciomiejscowy SUV)', 'starlight-560' => 'Starlight 560 (kompaktowy SUV)', 'starlight-730' => 'Starlight 730 (siedmiomiejscowy van)'];
    $out = [];
    foreach ($all as $slug => $label) if ($slug !== $bez) $out[] = '<a href="' . $U . 'wuling/' . $slug . '/">Wuling ' . $label . '</a>';
    return implode(', ', $out);
};

$HUBY = [];

// ───────────────────────── 7255 STARLIGHT (sedan) ─────────────────────────
$HUBY[7255] = [
 'slug' => 'starlight', 'full' => 'Wuling Starlight', 'posts' => [474828, 474844, 474854],
 'lead' => 'Wuling Starlight kosztuje w Prima-Auto od 125 000 do 127 000 PLN brutto z pełnym importem z Chin — to elektryczny lub hybrydowy sedan klasy średniej o długości 4835 mm, z bagażnikiem do 600 litrów i zasięgiem do 610 km na prądzie w cyklu CLTC. Wuling nie ma w Polsce salonów, więc Starlighta sprowadzisz tylko z importu indywidualnego — mamy 3 egzemplarze z lat 2025–2026, dwa praktycznie nowe, z przebiegiem 100 km.',
 'wiki' => '<p>Wuling Starlight to sedan klasy średniej produkowany przez SAIC-GM-Wuling, dostępny jako samochód w pełni elektryczny albo hybryda plug-in. Ma 4835 mm długości, 1860 mm szerokości i rozstaw osi 2800 mm, czyli rozmiar typowej limuzyny klasy średniej. Nasze wersje kosztują w Chinach od 99 800 do 125 800 CNY. W ofercie Prima-Auto mamy trzy egzemplarze w cenach od 125 000 do 127 000 PLN brutto z kompletnym importem.</p>{{LISTINGS_BAR}}'
  . '<h2>Ile kosztuje Wuling Starlight w Polsce</h2><p>Nasze egzemplarze kosztują od 125 000 do 127 000 PLN brutto i jest to kwota kompletna: ' . '%CENA%' . '. Hybryda plug-in z przebiegiem 100 km i elektryczna wersja 510 z przebiegiem 100 km kosztują po 125 000 PLN, a elektryczna wersja 610 km z przebiegiem 7800 km — 127 000 PLN. Ceny katalogowe tych wersji w Chinach to 99 800, 109 800 i 125 800 CNY. Warto wiedzieć, że przy tanim aucie koszty importu — transport, cło, VAT i homologacja — stanowią dużą część ceny końcowej, dlatego Starlight kosztuje w Polsce podobnie jak sedany z Chin z wyższej półki cenowej, na przykład <a href="' . $U . 'byd/qin-l-ev/">BYD Qin L EV</a> od 130 000 PLN.</p>'
  . '<h2>Kto produkuje Wulinga Starlight</h2><p>%KTO%</p>'
  . '<h2>Wersje elektryczne — do 610 km zasięgu</h2><p>Elektryczny Starlight ma silnik o mocy 150 kW, czyli 204 KM, i momencie 310 Nm, napędzający przednie koła. Bateria to ogniwa LFP: 54,5 kWh w wersji 510 i 69,2 kWh w wersji 610, co daje odpowiednio 510 i 610 km zasięgu w cyklu CLTC. Zużycie energii wynosi 12,5–12,8 kWh na 100 km, a szybkie ładowanie od 30 do 80 procent trwa około 20 minut. Prędkość maksymalna to 160 km/h, masa własna 1620–1730 kg.</p>'
  . '<h2>Hybryda plug-in — 150 km na prądzie</h2><p>Wersja hybrydowa łączy benzynowy silnik 1.5 o mocy 106 KM z silnikiem elektrycznym 150 kW (204 KM) i przekładnią E-CVT. Bateria LFP o pojemności 20,5 kWh wystarcza na 150 km jazdy na prądzie w cyklu CLTC i 105 km w cyklu WLTC, a z pełnym zbiornikiem 53 litrów zasięg łączny wynosi 1100 km w cyklu NEDC. Szybkie ładowanie od 30 do 80 procent trwa około 30 minut. Hybryda rozpędza się do 185 km/h i waży 1680 kg.</p>'
  . '<h2>Wnętrze, bagażnik i wyposażenie</h2><p>Kabina mieści pięć osób, a bagażnik ma od 540 do 600 litrów, zależnie od wersji. Zawieszenie z przodu to kolumny McPhersona, z tyłu układ wielowahaczowy, koła 18-calowe z oponami 215/50 R18. W wyposażeniu egzemplarzy są między innymi bezkluczykowe uruchamianie, cyfrowe zegary, funkcja auto hold, nawiewy dla tylnych pasażerów, filtr PM2,5 i aktualizacje oprogramowania przez sieć. Najbogatsza wersja 610 ma dodatkowo asystenta utrzymania pasa ruchu, aktywne hamowanie awaryjne, automatyczne parkowanie i adaptacyjne światła.</p>'
  . '<h2>Starlight na tle innych sedanów z Chin</h2><p>W tej samej cenie sprowadzamy też sedany marek bardziej znanych w Polsce: <a href="' . $U . 'byd/seal-6-dm-i/">BYD Seal 6 DM-i</a> od 106 000 PLN, <a href="' . $U . 'byd/qin-l-dm-i/">BYD Qin L DM-i</a> od 114 000 PLN czy <a href="' . $U . 'deepal/sl03/">Deepal SL03</a> od 118 000 PLN. Starlight wyróżnia się dużym bagażnikiem i zasięgiem do 610 km w wersji elektrycznej. Z tej samej rodziny Wulinga sprowadzamy też: %RODZINA%. Pełną ofertę znajdziesz na <a href="' . $U . 'wuling/">stronie marki Wuling</a>.</p>'
  . '<h2>Import Wulinga Starlight przez Prima-Auto</h2><p>Mamy trzy egzemplarze z Liuzhou: elektryczną wersję 610 km z 2025 roku z przebiegiem 7800 km, elektryczną wersję 510 oraz hybrydę plug-in, obie z przebiegiem 100 km. %PROCES% %GWARANCJA% Jeśli szukasz innej wersji Starlighta, sprowadzamy na zamówienie.</p>',
 'faq' => [
  ['q' => 'Ile kosztuje Wuling Starlight w Polsce?', 'a' => 'W Prima-Auto Wuling Starlight kosztuje od 125 000 do 127 000 PLN brutto. Cena zawiera pełny import: zakup w Chinach, transport, cło, VAT, homologację indywidualną i prowizję Prima-Auto. Ceny katalogowe tych wersji w Chinach to od 99 800 do 125 800 CNY. Kwota jest ostateczna, bez dopłat na końcu procesu.'],
  ['q' => 'Jaki zasięg ma elektryczny Wuling Starlight?', 'a' => 'Wersja z baterią 54,5 kWh przejeżdża 510 km, a wersja z baterią 69,2 kWh 610 km w cyklu CLTC. Silnik ma 204 KM i 310 Nm, zużycie energii wynosi około 12,5 kWh na 100 km, a szybkie ładowanie od 30 do 80 procent trwa około 20 minut.'],
  ['q' => 'Czy Wuling Starlight jest też hybrydą?', 'a' => 'Tak. Hybryda plug-in łączy silnik benzynowy 1.5 o mocy 106 KM z silnikiem elektrycznym 204 KM. Na samym prądzie przejeżdża 150 km w cyklu CLTC i 105 km w cyklu WLTC, a zasięg łączny wynosi 1100 km w cyklu NEDC.'],
  ['q' => 'Jak duży jest bagażnik Wulinga Starlight?', 'a' => 'Bagażnik ma od 540 do 600 litrów, zależnie od wersji. Sam samochód mierzy 4835 mm długości przy rozstawie osi 2800 mm i mieści pięć osób.'],
  ['q' => 'Czy Wuling Starlight jest dostępny w polskich salonach?', 'a' => 'Nie. Wuling nie prowadzi w Polsce salonów ani serwisu, dlatego jedyną drogą do Starlighta jest import indywidualny. Prima-Auto prowadzi cały proces, a odbiór w Rzeszowie następuje zwykle po 10 do 14 tygodniach. Gwarancja fabryczna obowiązuje w Chinach, a sprowadzone auto obejmuje gwarancja importera.'],
 ],
];

// ───────────────────────── 7256 STARLIGHT S (kompaktowy SUV) ─────────────────────────
$HUBY[7256] = [
 'slug' => 'starlight-s', 'full' => 'Wuling Starlight S', 'posts' => [474864, 474874],
 'lead' => 'Wuling Starlight S kosztuje w Prima-Auto od 128 000 do 133 000 PLN brutto z pełnym importem z Chin — to elektryczny SUV o długości 4745 mm, z silnikiem 204 KM, zasięgiem 510 km w cyklu CLTC i bagażnikiem 610 litrów. Wuling nie ma w Polsce salonów, więc Starlight S sprowadzisz tylko z importu indywidualnego — mamy 2 praktycznie nowe egzemplarze z przebiegiem 100 km.',
 'wiki' => '<p>Wuling Starlight S to SUV z rodziny Starlight, produkowany przez SAIC-GM-Wuling i oferowany w Chinach jako samochód elektryczny albo hybryda plug-in. Ma 4745 mm długości, 1890 mm szerokości, 1680 mm wysokości i rozstaw osi 2800 mm, więc rozmiarem odpowiada dużemu SUV-owi kompaktowemu. Oba nasze egzemplarze to wersje elektryczne z zasięgiem 510 km w cyklu CLTC, w cenach od 128 000 do 133 000 PLN brutto z kompletnym importem.</p>{{LISTINGS_BAR}}'
  . '<h2>Ile kosztuje Wuling Starlight S w Polsce</h2><p>Nasze egzemplarze kosztują od 128 000 do 133 000 PLN brutto i jest to kwota kompletna: %CENA%. Wersja 510 km Navigator kosztuje 128 000 PLN, a lepiej wyposażona wersja 510 km Flagship z 2025 roku — 133 000 PLN; oba auta mają przebieg 100 km. Ceny katalogowe tych wersji w Chinach to 119 800 i 129 800 CNY. Przy aucie w tej cenie koszty importu stanowią dużą część kwoty końcowej, dlatego Starlight S kosztuje w Polsce podobnie jak na przykład <a href="' . $U . 'leapmotor/c10/">Leapmotor C10</a> od 125 000 PLN.</p>'
  . '<h2>Kto produkuje Wulinga Starlight S</h2><p>%KTO%</p>'
  . '<h2>Napęd elektryczny — 204 KM i 510 km zasięgu</h2><p>Silnik elektryczny o mocy 150 kW, czyli 204 KM, i momencie 310 Nm napędza przednie koła i pozwala przyspieszyć do 100 km/h w 7,7 sekundy. Bateria LFP o pojemności 60 kWh wystarcza na 510 km w cyklu CLTC przy zużyciu około 14 kWh na 100 km. Szybkie ładowanie od 30 do 80 procent trwa około 20 minut, a pełne ładowanie z domowej ładowarki około 10 godzin. Prędkość maksymalna wynosi 175 km/h.</p>'
  . '<h2>Wymiary, bagażnik i wnętrze</h2><p>Kabina mieści pięć osób, a bagażnik ma 610 litrów — wyraźnie więcej niż typowy SUV kompaktowy. Masa własna to 1735–1780 kg. Z przodu pracują kolumny McPhersona, z tyłu niezależne zawieszenie czterowahaczowe, a auto stoi na kołach 18-calowych z oponami 235/55 R18.</p>'
  . '<h2>Wyposażenie i systemy wspomagania</h2><p>W obu egzemplarzach są bezkluczykowe uruchamianie, cyfrowe zegary, funkcja auto hold, nawiewy dla tylnych pasażerów, filtr PM2,5 i aktualizacje oprogramowania przez sieć. Wersja Flagship ma dodatkowo elektrycznie otwieraną klapę bagażnika, asystenta utrzymania pasa ruchu z ostrzeganiem o jego opuszczeniu, aktywne hamowanie awaryjne i adaptacyjne światła. Oprogramowanie jest przygotowane pod rynek chiński, więc część usług pokładowych działa w Polsce w ograniczonym zakresie.</p>'
  . '<h2>Starlight S na tle innych SUV-ów z Chin</h2><p>W podobnej cenie sprowadzamy <a href="' . $U . 'leapmotor/c10/">Leapmotor C10</a> od 125 000 PLN i <a href="' . $U . 'byd/song-l-dm-i/">BYD Song L DM-i</a> od 127 000 PLN. Starlight S wyróżnia się dużym bagażnikiem i prostą, sprawdzoną konstrukcją elektryczną. Z tej samej rodziny Wulinga sprowadzamy też: %RODZINA%. Pełną ofertę znajdziesz na <a href="' . $U . 'wuling/">stronie marki Wuling</a>.</p>'
  . '<h2>Import Wulinga Starlight S przez Prima-Auto</h2><p>Mamy dwa egzemplarze z Liuzhou, oba z przebiegiem 100 km: wersję Navigator i wersję Flagship z 2025 roku. %PROCES% %GWARANCJA% Jeśli szukasz Starlighta S w wersji hybrydowej, sprowadzamy na zamówienie.</p>',
 'faq' => [
  ['q' => 'Ile kosztuje Wuling Starlight S w Polsce?', 'a' => 'W Prima-Auto Wuling Starlight S kosztuje od 128 000 do 133 000 PLN brutto. Cena zawiera pełny import: zakup w Chinach, transport, cło, VAT, homologację indywidualną i prowizję Prima-Auto. Ceny katalogowe tych wersji w Chinach to 119 800 i 129 800 CNY.'],
  ['q' => 'Jaki zasięg ma Wuling Starlight S?', 'a' => 'Elektryczny Starlight S z baterią 60 kWh przejeżdża 510 km w cyklu CLTC przy zużyciu około 14 kWh na 100 km. Szybkie ładowanie od 30 do 80 procent trwa około 20 minut.'],
  ['q' => 'Jaką moc ma Wuling Starlight S?', 'a' => 'Silnik elektryczny ma 150 kW, czyli 204 KM, i 310 Nm momentu obrotowego, napędza przednie koła. Do 100 km/h auto przyspiesza w 7,7 sekundy, a prędkość maksymalna wynosi 175 km/h.'],
  ['q' => 'Jak duży jest Wuling Starlight S?', 'a' => 'Ma 4745 mm długości, 1890 mm szerokości i 1680 mm wysokości przy rozstawie osi 2800 mm. Mieści pięć osób, a bagażnik ma 610 litrów.'],
  ['q' => 'Czy Wuling Starlight S jest dostępny w polskich salonach?', 'a' => 'Nie. Wuling nie prowadzi w Polsce salonów ani serwisu, dlatego jedyną drogą do Starlighta S jest import indywidualny. Prima-Auto prowadzi cały proces, a odbiór w Rzeszowie następuje zwykle po 10 do 14 tygodniach. Sprowadzone auto obejmuje gwarancja importera.'],
 ],
];

// ───────────────────────── 7257 STARLIGHT L (6-miejscowy SUV) ─────────────────────────
$HUBY[7257] = [
 'slug' => 'starlight-l', 'full' => 'Wuling Starlight L', 'posts' => [474884],
 'lead' => 'Wuling Starlight L kosztuje w Prima-Auto 155 000 PLN brutto z pełnym importem z Chin — to sześciomiejscowy SUV hybrydowy plug-in o długości 4980 mm, z zasięgiem 260 km na prądzie w cyklu CLTC i łączną mocą silnika elektrycznego 231 KM. Model zadebiutował w Chinach w lipcu 2026 roku, a Wuling nie ma w Polsce salonów, więc Starlighta L sprowadzisz tylko z importu indywidualnego — mamy egzemplarz z 2026 roku z przebiegiem 2300 km.',
 'wiki' => '<p>Wuling Starlight L to najnowszy i największy SUV z rodziny Starlight: sześciomiejscowa hybryda plug-in, która trafiła do sprzedaży w Chinach w lipcu 2026 roku. Ma 4980 mm długości, 1930 mm szerokości, 1760 mm wysokości i rozstaw osi 2950 mm, więc mieści trzy rzędy siedzeń w nadwoziu krótszym niż pięciometrowe. W chińskim cenniku nasza wersja Flagship z sześcioma miejscami kosztuje 135 800 CNY. W ofercie Prima-Auto mamy egzemplarz z 2026 roku w cenie 155 000 PLN brutto z kompletnym importem.</p>{{LISTINGS_BAR}}'
  . '<h2>Ile kosztuje Wuling Starlight L w Polsce</h2><p>Nasz egzemplarz kosztuje 155 000 PLN brutto i jest to kwota kompletna: %CENA%. Nie ma dopłat na końcu procesu — cena jest znana przed startem i zapisana w umowie. To auto z przebiegiem 2300 km, w najwyższej wersji Flagship z sześcioma miejscami.</p>'
  . '<h2>Kto produkuje Wulinga Starlight L</h2><p>%KTO%</p>'
  . '<h2>Napęd plug-in — 260 km na prądzie</h2><p>Benzynowy silnik 1.5 o mocy 106 KM współpracuje z silnikiem elektrycznym o mocy 170 kW, czyli 231 KM, i momencie 280 Nm oraz z przekładnią E-CVT; napęd trafia na przednie koła. Bateria LFP o pojemności 37,9 kWh wystarcza na 260 km jazdy na prądzie w cyklu CLTC i 185 km w cyklu WLTC, a szybkie ładowanie od 30 do 80 procent trwa około 15 minut. Zbiornik paliwa ma 52 litry. Do 100 km/h auto przyspiesza w 8,2 sekundy, a prędkość maksymalna wynosi 190 km/h.</p>'
  . '<h2>Sześć miejsc i przestrzeń</h2><p>Kabina jest ułożona pod sześć osób w trzech rzędach. Bagażnik ma od 383 do 1103 litrów, zależnie od ustawienia tylnych siedzeń. Masa własna wynosi 2150 kg, promień skrętu 5,9 m. Z przodu pracują kolumny McPhersona, z tyłu zawieszenie wielowahaczowe, a auto stoi na kołach 20-calowych z oponami 235/55 R20.</p>'
  . '<h2>Starlight L a Huajing S — dwa sześciomiejscowe SUV-y SGMW</h2><p>W naszej ofercie są dwa sześciomiejscowe SUV-y hybrydowe SAIC-GM-Wuling. <a href="' . $U . 'wuling/huajing-s/">Wuling Huajing S</a> jest większy (5235 mm), mocniejszy i ma w standardzie technologie Huawei, ale kosztuje u nas od 197 000 PLN. Starlight L jest prostszy i o ponad 40 tysięcy złotych tańszy. W podobnej cenie sprowadzamy też sześciomiejscowe SUV-y innych marek: <a href="' . $U . 'leapmotor/c16/">Leapmotor C16</a> od 135 000 PLN i <a href="' . $U . 'jetour/shanhai-l9/">Jetour Shanhai L9</a> od 144 000 PLN, a siedmiomiejscowego <a href="' . $U . 'byd/tang-dm-i/">BYD Tang DM-i</a> od 134 000 PLN.</p>'
  . '<h2>Rodzina Starlight w ofercie Prima-Auto</h2><p>Starlight L to jeden z pięciu modeli rodziny Starlight, które sprowadzamy. Pozostałe to: %RODZINA%. Pełną ofertę znajdziesz na <a href="' . $U . 'wuling/">stronie marki Wuling</a>.</p>'
  . '<h2>Import Wulinga Starlight L przez Prima-Auto</h2><p>Nasz egzemplarz pochodzi z Nanning w prowincji Guangxi: rocznik 2026, przebieg 2300 km, wersja Flagship z sześcioma miejscami. %PROCES% Gwarancja fabryczna obowiązuje na rynku chińskim, a w Polsce auto obejmuje gwarancja importera. Jeśli szukasz innej wersji Starlighta L, sprowadzamy na zamówienie.</p>',
 'faq' => [
  ['q' => 'Ile kosztuje Wuling Starlight L w Polsce?', 'a' => 'Nasz egzemplarz z 2026 roku kosztuje 155 000 PLN brutto. Cena zawiera pełny import: zakup w Chinach, transport, cło, VAT, homologację indywidualną i prowizję Prima-Auto. Cena katalogowa tej wersji w Chinach to 135 800 CNY.'],
  ['q' => 'Ile miejsc ma Wuling Starlight L?', 'a' => 'Sześć, w trzech rzędach. Auto ma 4980 mm długości i rozstaw osi 2950 mm, a bagażnik mieści od 383 do 1103 litrów, zależnie od ustawienia tylnych siedzeń.'],
  ['q' => 'Jaki zasięg na prądzie ma Starlight L?', 'a' => 'Bateria LFP o pojemności 37,9 kWh wystarcza na 260 km w cyklu CLTC i 185 km w cyklu WLTC. Szybkie ładowanie od 30 do 80 procent trwa około 15 minut, a zbiornik paliwa ma 52 litry.'],
  ['q' => 'Czym różni się Starlight L od Huajing S?', 'a' => 'Oba to sześciomiejscowe SUV-y hybrydowe SAIC-GM-Wuling. Huajing S jest większy, ma do 525 KM i technologie Huawei w standardzie, ale kosztuje u nas od 197 000 PLN. Starlight L ma 231 KM mocy silnika elektrycznego i kosztuje 155 000 PLN.'],
  ['q' => 'Czy Wuling Starlight L jest dostępny w polskich salonach?', 'a' => 'Nie. Wuling nie prowadzi w Polsce salonów ani serwisu, dlatego jedyną drogą do Starlighta L jest import indywidualny. Prima-Auto prowadzi cały proces, a odbiór w Rzeszowie następuje zwykle po 10 do 14 tygodniach. Sprowadzone auto obejmuje gwarancja importera.'],
 ],
];

// ───────────────────────── 7258 STARLIGHT 560 (kompaktowy SUV, NEV) ─────────────────────────
$HUBY[7258] = [
 'slug' => 'starlight-560', 'full' => 'Wuling Starlight 560', 'posts' => [474900],
 'lead' => 'Wuling Starlight 560 kosztuje w Prima-Auto 125 000 PLN brutto z pełnym importem z Chin — to elektryczny SUV o długości 4745 mm, z zasięgiem 500 km w cyklu CLTC i silnikiem 136 KM. Wuling nie ma w Polsce salonów, więc Starlighta 560 sprowadzisz tylko z importu indywidualnego — mamy egzemplarz z przebiegiem 5600 km.',
 'wiki' => '<p>Wuling Starlight 560 to SUV z rodziny Starlight, dostępny w Chinach z silnikiem benzynowym oraz w odmianie nowej energii — elektrycznej lub hybrydowej. Sprowadzamy wyłącznie odmianę nowej energii. Auto ma 4745 mm długości, 1850 mm szerokości, 1755 mm wysokości i rozstaw osi 2810 mm. Elektryczna wersja 500 km w wyposażeniu Luxury kosztuje w Chinach 98 800 CNY. W ofercie Prima-Auto mamy egzemplarz w cenie 125 000 PLN brutto z kompletnym importem.</p>{{LISTINGS_BAR}}'
  . '<h2>Ile kosztuje Wuling Starlight 560 w Polsce</h2><p>Nasz egzemplarz kosztuje 125 000 PLN brutto i jest to kwota kompletna: %CENA%. Przy aucie, które w Chinach kosztuje niecałe 100 tysięcy juanów, koszty importu stanowią dużą część ceny końcowej. Jeśli zależy Ci na mocniejszym SUV-ie w tej cenie, porównaj go z <a href="' . $U . 'wuling/starlight-s/">Wulingiem Starlight S</a> (204 KM, od 128 000 PLN) albo z <a href="' . $U . 'leapmotor/c10/">Leapmotorem C10</a> od 125 000 PLN.</p>'
  . '<h2>Kto produkuje Wulinga Starlight 560</h2><p>%KTO%</p>'
  . '<h2>Napęd elektryczny — 500 km zasięgu</h2><p>Silnik elektryczny o mocy 100 kW, czyli 136 KM, i momencie 200 Nm napędza przednie koła. Bateria LFP o pojemności 56,7 kWh wystarcza na 500 km w cyklu CLTC przy zużyciu około 13,1 kWh na 100 km. Szybkie ładowanie od 30 do 80 procent trwa około 20 minut. Prędkość maksymalna wynosi 160 km/h, a masa własna 1655 kg — to auto nastawione na oszczędną jazdę, a nie na osiągi.</p>'
  . '<h2>Wymiary i wnętrze</h2><p>Kabina mieści pięć osób. Przy długości 4745 mm i wysokości 1755 mm Starlight 560 jest wyższy od pokrewnego Starlighta S o 75 mm. Z przodu pracują kolumny McPhersona, z tyłu zawieszenie wielowahaczowe, a auto stoi na kołach 18-calowych z oponami 215/55 R18.</p>'
  . '<h2>Starlight 560 czy Starlight S</h2><p>Oba SUV-y mają niemal tę samą długość, ale różnią się charakterem. <a href="' . $U . 'wuling/starlight-s/">Starlight S</a> ma mocniejszy silnik (204 KM), większą baterię (60 kWh) i bagażnik 610 litrów, a Starlight 560 jest wyższy, prostszy i tańszy w Chinach. Pozostałe modele rodziny Starlight w naszej ofercie to: %RODZINA%. Pełną ofertę znajdziesz na <a href="' . $U . 'wuling/">stronie marki Wuling</a>.</p>'
  . '<h2>Import Wulinga Starlight 560 przez Prima-Auto</h2><p>Nasz egzemplarz pochodzi z Nanning w prowincji Guangxi: elektryczna wersja 500 km Luxury z przebiegiem 5600 km. %PROCES% %GWARANCJA% Jeśli szukasz Starlighta 560 w wersji hybrydowej, sprowadzamy na zamówienie.</p>'
  . '<h2>Czy Starlight 560 jest w Polsce</h2><p>Nie. Wuling nie prowadzi w Polsce salonów ani serwisu, a Starlight 560 powstał z myślą o rynku chińskim. Jedyną drogą do tego auta jest import indywidualny, który Prima-Auto przeprowadza od początku do końca.</p>',
 'faq' => [
  ['q' => 'Ile kosztuje Wuling Starlight 560 w Polsce?', 'a' => 'Nasz egzemplarz kosztuje 125 000 PLN brutto. Cena zawiera pełny import: zakup w Chinach, transport, cło, VAT, homologację indywidualną i prowizję Prima-Auto. Cena katalogowa tej wersji w Chinach to 98 800 CNY.'],
  ['q' => 'Jaki zasięg ma elektryczny Starlight 560?', 'a' => 'Bateria LFP o pojemności 56,7 kWh wystarcza na 500 km w cyklu CLTC przy zużyciu około 13,1 kWh na 100 km. Szybkie ładowanie od 30 do 80 procent trwa około 20 minut.'],
  ['q' => 'Jaką moc ma Wuling Starlight 560?', 'a' => 'Silnik elektryczny ma 100 kW, czyli 136 KM, i 200 Nm momentu obrotowego. Prędkość maksymalna wynosi 160 km/h.'],
  ['q' => 'Czym różni się Starlight 560 od Starlight S?', 'a' => 'Mają niemal tę samą długość, ale Starlight S ma mocniejszy silnik 204 KM, baterię 60 kWh i bagażnik 610 litrów. Starlight 560 ma 136 KM i jest wyższy. W naszej ofercie Starlight S kosztuje od 128 000 PLN, a Starlight 560 125 000 PLN.'],
  ['q' => 'Czy Wuling Starlight 560 jest dostępny w polskich salonach?', 'a' => 'Nie. Wuling nie prowadzi w Polsce salonów ani serwisu, dlatego jedyną drogą do Starlighta 560 jest import indywidualny. Odbiór w Rzeszowie następuje zwykle po 10 do 14 tygodniach, a sprowadzone auto obejmuje gwarancja importera.'],
 ],
];

// ───────────────────────── 7259 STARLIGHT 730 (7-miejscowy van, NEV) ─────────────────────────
$HUBY[7259] = [
 'slug' => 'starlight-730', 'full' => 'Wuling Starlight 730', 'posts' => [474916, 474926, 474942, 474958],
 'lead' => 'Wuling Starlight 730 kosztuje w Prima-Auto od 129 000 do 134 000 PLN brutto z pełnym importem z Chin — to siedmiomiejscowy van o długości 4910 mm w wersji hybrydowej plug-in albo elektrycznej z zasięgiem 500 km, najtańszy siedmiomiejscowy van w naszej ofercie. Wuling nie ma w Polsce salonów, więc Starlighta 730 sprowadzisz tylko z importu indywidualnego — mamy 4 egzemplarze z 2025 roku.',
 'wiki' => '<p>Wuling Starlight 730 to kompaktowy van z siedmioma miejscami w trzech rzędach, produkowany przez SAIC-GM-Wuling. W Chinach jest dostępny z silnikiem benzynowym oraz w odmianie nowej energii, a my sprowadzamy wyłącznie tę drugą: hybrydę plug-in i wersję elektryczną. Auto ma 4910 mm długości, 1850 mm szerokości, 1770 mm wysokości i rozstaw osi 2910 mm. Nasze wersje kosztują w Chinach 103 800 CNY (hybryda) i 109 800 CNY (wersja elektryczna). W ofercie Prima-Auto mamy cztery egzemplarze w cenach od 129 000 do 134 000 PLN brutto z kompletnym importem.</p>{{LISTINGS_BAR}}'
  . '<h2>Ile kosztuje Wuling Starlight 730 w Polsce</h2><p>Nasze egzemplarze kosztują od 129 000 do 134 000 PLN brutto i jest to kwota kompletna: %CENA%. Hybrydy z przebiegiem około 3000 km kosztują 129 000 i 131 000 PLN, a wersje elektryczne 131 000 PLN (przebieg 26 000 km) i 134 000 PLN (przebieg 5000 km). To najtańszy siedmiomiejscowy van w naszej ofercie — kolejne to <a href="' . $U . 'maxus/g90/">Maxus G90</a> od 159 000 PLN i <a href="' . $U . 'byd/xia-summer/">BYD Xia</a> od 188 000 PLN, choć oba są wyraźnie większe i mocniejsze.</p>'
  . '<h2>Kto produkuje Wulinga Starlight 730</h2><p>%KTO%</p>'
  . '<h2>Hybryda plug-in czy wersja elektryczna</h2><p>Hybryda łączy benzynowy silnik 1.5 o mocy 106 KM z silnikiem elektrycznym 145 kW (197 KM) i 230 Nm oraz przekładnią E-CVT. Bateria LFP o pojemności 20,5 kWh wystarcza na 125 km jazdy na prądzie w cyklu CLTC i 100 km w cyklu WLTC, a z pełnym zbiornikiem 52 litrów zasięg łączny wynosi 1100 km w cyklu WLTC. Wersja elektryczna ma silnik 100 kW (136 KM) i 200 Nm oraz baterię LFP 60 kWh na 500 km w cyklu CLTC przy zużyciu około 13,6 kWh na 100 km; szybkie ładowanie od 30 do 80 procent trwa około 20 minut. Hybryda rozpędza się do 170 km/h, elektryk do 160 km/h. Na długie trasy z pełnym obciążeniem lepiej sprawdzi się hybryda, na codzienną jazdę po mieście — wersja elektryczna.</p>'
  . '<h2>Siedem miejsc i przestrzeń</h2><p>Kabina jest ułożona pod siedem osób w trzech rzędach, a wysokie nadwozie (1770 mm) i rozstaw osi 2910 mm dają dużo miejsca w drugim i trzecim rzędzie. Masa własna wynosi około 1810 kg. Z przodu pracują kolumny McPhersona, z tyłu zawieszenie wielowahaczowe, a auto stoi na kołach 17-calowych z oponami 215/55 R17. W wyposażeniu egzemplarzy są bezkluczykowe uruchamianie, funkcja auto hold, nawiewy dla tylnych pasażerów, mocowania fotelików ISOFIX i aktualizacje oprogramowania przez sieć.</p>'
  . '<h2>Dla kogo jest Starlight 730</h2><p>To praktyczny, prosty van dla dużej rodziny albo firmy przewożącej ludzi — z siedmioma miejscami w trzech rzędach, niskimi kosztami jazdy na prądzie i najniższą ceną wśród siedmiomiejscowych vanów w naszej ofercie. Jeśli potrzebujesz więcej komfortu i mocy, zobacz większe vany z naszej oferty, na przykład <a href="' . $U . 'denza/d9-dm-i/">Denzę D9 DM-i</a> od 227 000 PLN. Pozostałe modele rodziny Starlight to: %RODZINA%. Pełną ofertę znajdziesz na <a href="' . $U . 'wuling/">stronie marki Wuling</a>.</p>'
  . '<h2>Import Wulinga Starlight 730 przez Prima-Auto</h2><p>Mamy cztery egzemplarze z 2025 roku: dwie hybrydy z Liuzhou z przebiegiem około 3000 km, wersję elektryczną z Kantonu z przebiegiem 5000 km i wersję elektryczną z Shenzhen z przebiegiem 26 000 km. %PROCES% %GWARANCJA% Jeśli szukasz innej wersji Starlighta 730, sprowadzamy na zamówienie.</p>'
  . '<h2>Czy Starlight 730 jest w Polsce</h2><p>Nie. Wuling nie prowadzi w Polsce salonów ani serwisu, a Starlight 730 w odmianie nowej energii powstał z myślą o rynku chińskim. Jedyną drogą do tego vana jest import indywidualny, który Prima-Auto przeprowadza od początku do końca — od weryfikacji egzemplarza w Chinach po homologację i przygotowanie do rejestracji.</p>',
 'faq' => [
  ['q' => 'Ile kosztuje Wuling Starlight 730 w Polsce?', 'a' => 'W Prima-Auto Wuling Starlight 730 kosztuje od 129 000 do 134 000 PLN brutto. Cena zawiera pełny import: zakup w Chinach, transport, cło, VAT, homologację indywidualną i prowizję Prima-Auto. To najtańszy siedmiomiejscowy van w naszej ofercie.'],
  ['q' => 'Ile miejsc ma Wuling Starlight 730?', 'a' => 'Siedem, w trzech rzędach. Auto ma 4910 mm długości, 1770 mm wysokości i rozstaw osi 2910 mm, co daje dużo miejsca także w trzecim rzędzie.'],
  ['q' => 'Jaki zasięg ma Starlight 730?', 'a' => 'Wersja elektryczna z baterią 60 kWh przejeżdża 500 km w cyklu CLTC. Hybryda plug-in z baterią 20,5 kWh przejeżdża na prądzie 125 km w cyklu CLTC i 100 km w cyklu WLTC, a zasięg łączny z pełnym zbiornikiem wynosi 1100 km w cyklu WLTC.'],
  ['q' => 'Hybryda czy elektryk — którego Starlighta 730 wybrać?', 'a' => 'Hybryda ma mocniejszy napęd (197 KM mocy silnika elektrycznego) i zasięg łączny 1100 km, więc lepiej sprawdza się w długich trasach z kompletem pasażerów. Wersja elektryczna ma 136 KM i 500 km zasięgu, i jest tańsza w codziennej eksploatacji w mieście.'],
  ['q' => 'Czy Wuling Starlight 730 jest dostępny w polskich salonach?', 'a' => 'Nie. Wuling nie prowadzi w Polsce salonów ani serwisu, dlatego jedyną drogą do Starlighta 730 jest import indywidualny. Odbiór w Rzeszowie następuje zwykle po 10 do 14 tygodniach, a sprowadzone auto obejmuje gwarancja importera.'],
 ],
];

// ───────────────────────── HUB MARKI WULING (stan po Huajing S) ─────────────────────────
$w_lead = 'Wulinga sprowadzisz do Polski przez Prima-Auto — mamy 15 aut tej marki w cenach od 120 000 do 210 000 PLN: sześciomiejscowego SUV-a Huajing S opracowanego razem z Huawei, pięć modeli elektrycznej i hybrydowej rodziny Starlight (sedan, SUV-y Starlight S, L i 560 oraz siedmiomiejscowy van Starlight 730) i elektrycznego vana Yangguang. Wuling to marka koncernu SAIC-GM-Wuling, jednego z największych producentów aut na świecie, ale w Polsce nie ma żadnego salonu ani sieci serwisowej. Indywidualny import z Chin to jedyna droga, by jeździć Wulingiem już dziś — z homologacją indywidualną i gwarancją importera.';
$w_wiki_repl = [
    'W Prima-Auto mamy <strong>4 auta</strong> Wuling: 3 egzemplarze dużego SUV-a hybrydowego <strong>Huajing S</strong> z 2026 roku w cenach od <strong>197 000 do 210 000 PLN</strong> oraz elektrycznego vana <strong>Yangguang</strong> z 2025 roku za <strong>120 000 PLN</strong>.'
        => 'W Prima-Auto mamy <strong>15 aut</strong> Wuling w cenach od <strong>120 000 do 210 000 PLN</strong>: 3 egzemplarze dużego SUV-a hybrydowego <strong>Huajing S</strong>, 11 aut z rodziny <strong>Starlight</strong> (sedan, SUV-y S, L i 560 oraz van 730) i elektrycznego vana <strong>Yangguang</strong>.',
    '<h2>Wuling Yangguang — elektryczny van/MPV</h2>'
        => '<h2>Wuling Starlight — pięć modeli elektrycznych i hybrydowych</h2><p>Rodzina Starlight (po chińsku Xingguang) to seria modeli Wulinga z napędem elektrycznym i hybrydowym plug-in. Sprowadzamy pięć jej modeli: <a href="https://primaauto.com.pl/samochody/wuling/starlight/">sedan Starlight</a> z zasięgiem do 610 km (od 125 000 PLN), kompaktowego SUV-a <a href="https://primaauto.com.pl/samochody/wuling/starlight-s/">Starlight S</a> z bagażnikiem 610 litrów (od 128 000 PLN), sześciomiejscowego SUV-a hybrydowego <a href="https://primaauto.com.pl/samochody/wuling/starlight-l/">Starlight L</a> (155 000 PLN), elektrycznego SUV-a <a href="https://primaauto.com.pl/samochody/wuling/starlight-560/">Starlight 560</a> (125 000 PLN) oraz siedmiomiejscowego vana <a href="https://primaauto.com.pl/samochody/wuling/starlight-730/">Starlight 730</a> w wersji hybrydowej i elektrycznej (od 129 000 PLN) — najtańszego siedmiomiejscowego vana w naszej ofercie.</p><h2>Wuling Yangguang — elektryczny van/MPV</h2>',
    'Wuling Huajing S kosztuje w naszej ofercie od <strong>197 000 do 210 000 PLN</strong>, a Wuling Yangguang <strong>120 000 PLN</strong>.'
        => 'Wuling Huajing S kosztuje w naszej ofercie od <strong>197 000 do 210 000 PLN</strong>, modele rodziny Starlight od <strong>125 000 do 155 000 PLN</strong>, a Wuling Yangguang <strong>120 000 PLN</strong>.',
];
$w_faq0 = ['q' => 'Ile kosztuje sprowadzenie Wulinga do Polski?',
           'a' => 'W ofercie Prima-Auto Wuling Huajing S kosztuje od 197 000 do 210 000 PLN, modele rodziny Starlight od 125 000 do 155 000 PLN, a Wuling Yangguang 120 000 PLN. Są to ceny pod klucz, obejmujące zakup w Chinach, transport, cło, podatki oraz homologację indywidualną. Nie doliczamy ukrytych opłat na koniec procesu.'];
$w_faq_add = ['q' => 'Jakie modele Wuling Starlight można sprowadzić?',
              'a' => 'Sprowadzamy pięć modeli rodziny Starlight: sedan Starlight z zasięgiem do 610 km, kompaktowego SUV-a Starlight S, sześciomiejscowego SUV-a hybrydowego Starlight L, elektrycznego SUV-a Starlight 560 oraz siedmiomiejscowego vana Starlight 730 w wersji hybrydowej i elektrycznej. Ceny w naszej ofercie wynoszą od 125 000 do 155 000 PLN.'];

// ───────────────────────── składanie + walidacja ─────────────────────────
$stop = false;
foreach ($HUBY as $tid => &$h) {
    $h['wiki'] = strtr($h['wiki'], ['%CENA%' => $cena_zawiera, '%KTO%' => $kto, '%PROCES%' => $proces, '%GWARANCJA%' => $gwarancja, '%RODZINA%' => $link_rodzina($h['slug'])]);
    $t = get_term($tid, 'serie');
    if (!$t || is_wp_error($t) || $t->slug !== $h['slug'] || (int) $t->parent !== $MID) { echo "!! term $tid ≠ {$h['slug']} pod Wulingiem — STOP\n"; $stop = true; }
    if (preg_match('/%[A-Z]+%/', $h['wiki'])) { echo "!! $tid: niepodstawiony znacznik — STOP\n"; $stop = true; }
    if (strpos($h['wiki'], '{{LISTINGS_BAR}}') === false) { echo "!! $tid: brak LISTINGS_BAR — STOP\n"; $stop = true; }
    foreach ($h['faq'] as $qa) if (preg_match('/["„”“]/u', $qa['q'] . $qa['a'])) { echo "!! $tid: cudzysłów w FAQ — STOP\n"; $stop = true; }
    $h['json'] = json_encode($h['faq'], JSON_UNESCAPED_UNICODE);
    if (json_decode($h['json'], true) === null) { echo "!! $tid: FAQ nie parsuje — STOP\n"; $stop = true; }
    foreach (['lead', 'wiki', 'json'] as $k) if (!preg_match('/[ąćęłńóśźż]/u', $h[$k])) { echo "!! $tid: $k bez diakrytyk — STOP\n"; $stop = true; }
    foreach ($h['posts'] as $pid) {
        if (!in_array($tid, wp_get_object_terms($pid, 'serie', ['fields' => 'ids']), true)) { echo "!! post $pid nie pod $tid — STOP\n"; $stop = true; }
    }
    // ceny w leadzie muszą się zgadzać z bazą
    $ceny = array_map(fn($p) => (int) get_post_meta($p, 'price', true), $h['posts']);
    $od = number_format(min($ceny), 0, ',', ' '); $do = number_format(max($ceny), 0, ',', ' ');
    if (strpos($h['lead'], $od) === false || strpos($h['lead'], $do) === false) { echo "!! $tid: lead nie zawiera ceny z bazy ($od–$do) — STOP\n"; $stop = true; }
    printf("-- %-14s %d H2, %5d zn wiki | lead %d | FAQ %d | ceny %s–%s | oferty %d\n", $h['slug'], substr_count($h['wiki'], '<h2>'), mb_strlen(wp_strip_all_tags($h['wiki'])), mb_strlen($h['lead']), count($h['faq']), $od, $do, count($h['posts']));
}
unset($h);

$w_wiki = (string) get_term_meta($MID, 'asiaauto_wiki_body', true);
foreach ($w_wiki_repl as $f => $t) {
    $n = substr_count($w_wiki, $f);
    if ($n !== 1) { echo "!! Wuling wiki: fragment $n razy — STOP: " . mb_substr(wp_strip_all_tags($f), 0, 60) . "\n"; $stop = true; continue; }
    $w_wiki = str_replace($f, $t, $w_wiki);
}
$w_faq = json_decode((string) get_term_meta($MID, 'asiaauto_faq_json', true), true);
if (!is_array($w_faq) || ($w_faq[0]['q'] ?? '') !== $w_faq0['q']) { echo "!! Wuling FAQ[0] — STOP\n"; $stop = true; }
else { $w_faq[0] = $w_faq0; array_splice($w_faq, 2, 0, [$w_faq_add]); }
$w_json = json_encode($w_faq, JSON_UNESCAPED_UNICODE);
printf("-- wuling (marka) wiki %d H2 | FAQ %d\n", substr_count($w_wiki, '<h2>'), is_array($w_faq) ? count($w_faq) : 0);

if ($stop) { echo "PRZERWANE, nic nie zapisano.\n"; return; }
if (!$apply) { echo "WALIDACJA OK — nic nie zapisane. Zapis: dopisz 'apply'\n"; return; }

// ───────────────────────── zapis ─────────────────────────
$bk = '/home/host476470/backups/primaauto/2026-09-11/termmeta-przed-starlight-tresc.json';
$snap = [$MID => get_term_meta($MID)]; foreach (array_keys($HUBY) as $tid) $snap[$tid] = get_term_meta($tid);
file_put_contents($bk, wp_json_encode($snap, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "backup termmeta -> $bk\n";

foreach ($HUBY as $tid => $h) {
    update_term_meta($tid, '_serie_full_title', $h['full']);
    update_term_meta($tid, '_asiaauto_lead', $h['lead']);
    update_term_meta($tid, '_asiaauto_h1_suffix', 'cena w Polsce i import z Chin');
    update_term_meta($tid, '_asiaauto_pl_availability', 'import_only');
    update_term_meta($tid, 'asiaauto_wiki_body', wp_kses_post($h['wiki']));
    update_term_meta($tid, 'asiaauto_faq_json', $h['json']);
    update_term_meta($tid, '_asiaauto_seo_rework', 'v1-2026-09-11');
}
update_term_meta($MID, '_asiaauto_lead', $w_lead);
update_term_meta($MID, 'asiaauto_wiki_body', wp_kses_post($w_wiki));
update_term_meta($MID, 'asiaauto_faq_json', $w_json);
clean_term_cache(array_merge(array_keys($HUBY), [$MID]));

foreach ($HUBY as $tid => $h) foreach ($h['posts'] as $pid) {
    if (get_post_status($pid) === 'draft') wp_update_post(['ID' => $pid, 'post_status' => 'publish']);
    printf("post %d -> %s %s\n", $pid, get_post_status($pid), get_permalink($pid));
}
foreach (array_keys($HUBY) as $tid) {
    AsiaAuto_HubTitleGenerator::regenerateForTerm($tid);
    AsiaAuto_Spec::saveSnapshot($tid);
    printf("title %-14s -> %s\n", get_term($tid)->slug, get_term_meta($tid, 'rank_math_title', true));
}
AsiaAuto_HubTitleGenerator::regenerateForMakeTerm($MID);
printf("title marki -> %s\nZAPISANE.\n", get_term_meta($MID, 'rank_math_title', true));
