<?php
/**
 * huby-premiery-2026-09-07.php — 4 nowe huby modeli, których premiera przypada na wrzesień 2026.
 *
 * Modele: Changan Qiyuan Q06, Aistaland GX7, Xiaomi SkyNomad N90, Xiaomi SkyNomad N70.
 * Żaden nie ma jeszcze ofert w feedach (sprawdzone 07.09.2026 na che168 i dongchedi) — huby
 * powstają PRZED podażą, świadomą decyzją Janka, żeby zacząć rankować na frazy premierowe.
 *
 * Dane techniczne i ceny katalogowe: katalog Autohome (specid), pobrane
 * `scripts/autohome-catalog-fetch.js`. Ceny PL: `AsiaAuto_Price::calculateFromCny()`,
 * kurs ze snapshotu z 07.09.2026 (CNY/USD 0,148368; USD/PLN 3,85).
 *
 * Użycie:
 *   wp eval-file scripts/huby-premiery-2026-09-07.php podglad <plik.html>   # nic nie zapisuje
 *   wp eval-file scripts/huby-premiery-2026-09-07.php apply                 # tworzy termy + treść
 */

$tryb = (string) ($args[0] ?? 'podglad');
$out  = (string) ($args[1] ?? '');

// ---------------------------------------------------------------- treści

$HUBY = [];

// ------------------------------------------------ Changan Qiyuan Q06
$HUBY['q06'] = [
    'make_slug'   => 'changan',
    'make_name'   => 'Changan',
    'serie_name'  => 'Qiyuan Q06',
    'serie_slug'  => 'qiyuan-q06',
    'full_title'  => 'Changan Qiyuan Q06',
    'api_value'   => 'Changan Qiyuan Q06',
    'title'       => 'Changan Qiyuan Q06 cena w Polsce — od 177 000 zł | Prima-Auto',
    'desc'        => 'Changan Qiyuan Q06 — cena w Polsce od 177 000 zł. Elektryczny SUV 602 lub 700 km CLTC, wersja EREV 730 Nm. Premiera w Chinach wrzesień 2026. Import przez Prima-Auto.',
    'h1_suffix'   => 'cena w Polsce i import z Chin',
    'focus_kw'    => 'Changan Qiyuan Q06',
    'lead'        => 'Changan Qiyuan Q06 startuje w Chinach we wrześniu 2026 w cenie od 149 900 juanów — po sprowadzeniu do Polski przez Prima-Auto to orientacyjnie od 177 000 zł z cłem, VAT-em i homologacją. Do wyboru są dwa napędy: czysto elektryczny z zasięgiem 602 lub 700 km CLTC oraz hybryda szeregowa EREV o moc 270 kW. Modelu nie ma w polskich salonach — sprowadzamy go na indywidualne zamówienie.',
    'wiki'        => <<<'HTML'
<p>Changan Qiyuan Q06 to kompaktowy SUV koncernu Changan, pokazany w wersji produkcyjnej 15 lipca 2026 i wprowadzany na rynek chiński we wrześniu 2026 jako rocznik 2027. Changan Qiyuan (chińskie 长安启源) to elektryczna linia koncernu Changan i nie ma dystrybucji w Polsce, więc jedyną drogą do tego auta jest import indywidualny.</p>
{{LISTINGS_BAR}}
<h2>Ile kosztuje Changan Qiyuan Q06 w Polsce</h2>
<p>Chińskie ceny katalogowe zaczynają się od 149 900 juanów za wersję elektryczną 602 km i sięgają 191 900 juanów za topową 700 智尊 Ultra. Przedsprzedaż producent otworzył od 147 900 juanów. Po przeliczeniu przez nasz kalkulator importu — z transportem, cłem 10%, VAT-em 23%, homologacją i obsługą — daje to orientacyjnie <strong>od 177 000 do 213 000 zł</strong> dla wersji elektrycznych i około 196 000 zł dla hybrydy EREV. Samochody elektryczne i hybrydy plug-in o pojemności do 2000 cm³ są w Polsce zwolnione z akcyzy, co realnie obniża koszt sprowadzenia.</p>
<h2>Wersje i różnice między nimi</h2>
<p>Katalog producenta wymienia dziewięć wersji rocznika 2027, w trzech grupach:</p>
<ul>
<li><strong>602 km CLTC</strong> — silnik 225 kW (306 KM), 290 Nm, napęd na tył, bateria LFP 66,02 kWh, 0–100 km/h w 7,1 s.</li>
<li><strong>700 km CLTC</strong> — mocniejszy silnik 245 kW i większa bateria, ta sama architektura 800 V.</li>
<li><strong>EREV dwusilnikowa</strong> — 270 kW i 730 Nm, bateria 28,39 kWh, 200 km na samym prądzie i generator 1.5 (98 KM) do dłuższych tras; 0–100 km/h w 5,8 s.</li>
</ul>
<p>Wersje Pro i Ultra różnią się poziomem asystenta jazdy Tianshu — Ultra dostaje skaner LiDAR na dachu.</p>
<h2>Zasięg, ładowanie i architektura 800 V</h2>
<p>Wersje elektryczne pracują na instalacji 800 V z obsługą ładowania 6C — producent deklaruje uzupełnienie baterii w około 9 minut na odpowiednio mocnej ładowarce. Wariant EREV korzysta z instalacji 400 V i ładuje się w około 15 minut, ale nie wymaga ładowarki w codziennym użyciu: przy rozładowanej baterii pracę przejmuje generator, a auto tankuje się jak spalinowe.</p>
<h2>Wymiary i przestrzeń</h2>
<p>Nadwozie ma 4837 mm długości, 1970 mm szerokości i 1670 mm wysokości przy rozstawie osi 2940 mm. To wymiary klasy średniej — dłuższy i szerszy niż typowy kompakt, z rozstawem osi bliższym większym SUV-om. Masa własna wersji elektrycznej to 1925 kg, EREV — 1930 kg. Pięć miejsc, nadwozie pięciodrzwiowe.</p>
<h2>Technologia i wyposażenie</h2>
<p>Q06 stoi na architekturze SDA Tianshu, czyli tej samej warstwie elektronicznej, którą Changan wprowadza w całej gamie Qiyuan. Wersje z dopiskiem 天枢领航 (Tianshu Navigation Pilot) mają asystenta jazdy z nawigacją po drogach miejskich i szybkiego ruchu, a odmiany Ultra dokładają LiDAR. Prędkość maksymalna to 200 km/h dla wersji elektrycznej i 220 km/h dla EREV.</p>
<h2>Kiedy Q06 będzie dostępny w Polsce</h2>
<p>Model wchodzi do sprzedaży w Chinach we wrześniu 2026, więc pierwsze egzemplarze na rynku wtórnym i u chińskich dealerów pojawią się w kolejnych tygodniach. Prima-Auto sprowadza auta na zamówienie: ustalamy wersję i kolor, znajdujemy egzemplarz u sprawdzonego sprzedawcy, przechodzimy przez transport morski, odprawę celną i homologację, a auto odbierasz zarejestrowane w Polsce. Cały proces zajmuje zwykle 10–14 tygodni.</p>
<h2>Dla kogo jest Q06</h2>
<p>To propozycja dla kogoś, kto szuka elektrycznego SUV-a klasy średniej z realnym zasięgiem powyżej 600 km i nie chce dopłacać do europejskiego znaczka. Jeśli zależy Ci na dostępności od ręki, zobacz nasze bieżące oferty aut elektrycznych — wiele modeli o zbliżonych parametrach mamy już na placu albo w drodze do Polski.</p>
HTML,
    'faq'         => [
        ['q' => 'Ile kosztuje Changan Qiyuan Q06 w Polsce?', 'a' => 'Orientacyjnie od 177 000 zł za wersję elektryczną 602 km do 213 000 zł za topową 700 km. Wersja EREV to około 196 000 zł. Kwoty zawierają transport z Chin, cło, VAT, homologację i obsługę Prima-Auto. Ostateczna cena zależy od kursu walut i konkretnego egzemplarza.'],
        ['q' => 'Jaki zasięg ma Changan Qiyuan Q06?', 'a' => 'Wersje elektryczne mają 602 lub 700 km według chińskiej procedury CLTC. Wersja EREV przejeżdża 200 km na samym prądzie, a dalej pracuje generator spalinowy 1.5, więc łączny zasięg jest ograniczony wyłącznie pojemnością baku.'],
        ['q' => 'Czy Changan Qiyuan Q06 jest dostępny w polskich salonach?', 'a' => 'Nie. Changan Qiyuan, czyli elektryczna linia koncernu Changan, nie ma w Polsce oficjalnej dystrybucji. Jedyną drogą do tego modelu jest import indywidualny z Chin.'],
        ['q' => 'Jak szybko ładuje się Changan Qiyuan Q06?', 'a' => 'Wersje elektryczne pracują na instalacji 800 V i obsługują ładowanie 6C — producent podaje około 9 minut na mocnej ładowarce DC. Wersja EREV korzysta z instalacji 400 V i ładuje się w około 15 minut.'],
        ['q' => 'Ile trwa sprowadzenie Changan Qiyuan Q06 z Chin?', 'a' => 'Zwykle 10–14 tygodni od potwierdzenia zamówienia: wyszukanie egzemplarza, transport morski, odprawa celna, homologacja i rejestracja. Przez cały czas dostajesz od nas zdjęcia i status auta.'],
    ],
];

// ------------------------------------------------ Aistaland GX7
$HUBY['gx7'] = [
    'make_slug'   => 'aistaland',
    'make_name'   => 'Aistaland',
    'serie_name'  => 'GX7',
    'serie_slug'  => 'gx7',
    'full_title'  => 'Aistaland GX7',
    'api_value'   => '启境GX7',
    'title'       => 'Aistaland GX7 cena w Polsce — od 262 000 zł | Prima-Auto',
    'desc'        => 'Aistaland GX7 — cena w Polsce od 262 000 zł. SUV EREV marki GAC i Huawei: do 550 kW, 4 lidary, ADS 5, zasięg łączny do 1400 km. Import przez Prima-Auto.',
    'h1_suffix'   => 'cena w Polsce i import z Chin',
    'focus_kw'    => 'Aistaland GX7',
    'lead'        => 'Aistaland GX7 to duży SUV z napędem hybrydowym szeregowym, stworzony wspólnie przez GAC i Huaweia — przedsprzedaż w Chinach ruszyła 4 września 2026, ceny katalogowe zaczynają się od 249 900 juanów. Po sprowadzeniu do Polski przez Prima-Auto to orientacyjnie od 262 000 zł z cłem, VAT-em i homologacją. Marka nie ma dystrybucji w Europie, więc dostępna jest wyłącznie z importu.',
    'wiki'        => <<<'HTML'
<p>Aistaland (chińskie 启境) to marka powołana wspólnie przez koncern GAC i Huaweia. GX7 jest jej drugim modelem po sedanie GT7 — dużym, pięcioosobowym SUV-em z napędem hybrydowym szeregowym, pokazanym 26 czerwca 2026 i wprowadzanym do sprzedaży jesienią 2026 jako rocznik 2027.</p>
{{LISTINGS_BAR}}
<h2>Ile kosztuje Aistaland GX7 w Polsce</h2>
<p>Chińskie ceny katalogowe to od 249 900 do 311 900 juanów w zależności od liczby silników. Po przeliczeniu przez nasz kalkulator importu — z transportem, cłem 10%, VAT-em 23%, homologacją i obsługą — wychodzi orientacyjnie <strong>od 262 000 do 315 000 zł</strong>. Napęd hybrydowy plug-in o pojemności 1,5 litra jest w Polsce zwolniony z akcyzy, więc ten koszt nie obciąża sprowadzenia.</p>
<h2>Trzy wersje, trzy poziomy mocy</h2>
<ul>
<li><strong>Ultra</strong> — 200 kW, zasięg elektryczny 340 km CLTC.</li>
<li><strong>Ultra dwusilnikowa 4×4</strong> — 340 kW i 550 Nm, 325 km CLTC na prądzie, 0–100 km/h w 4,9 s, prędkość maksymalna 210 km/h.</li>
<li><strong>Ultra trzysilnikowa 4×4</strong> — 550 kW i zasięg elektryczny 385 km CLTC.</li>
</ul>
<p>Wszystkie warianty łączy ten sam generator: silnik 1.5 o mocy 170 KM, który nie napędza kół, tylko ładuje baterię.</p>
<h2>Zasięg, bateria i ładowanie</h2>
<p>Bateria litowo-żelazowo-fosforanowa ma 52 kWh i pracuje w instalacji 800 V z ładowaniem 5C — uzupełnienie zajmuje około 15 minut. Sam prąd wystarcza na 260 km według surowszej procedury WLTC i 325 km według chińskiej CLTC, a razem z generatorem producent deklaruje do 1400 km łącznego zasięgu.</p>
<h2>Wymiary i przestrzeń</h2>
<p>GX7 mierzy 5099 mm długości, 2006 mm szerokości i 1750 mm wysokości, przy rozstawie osi 3070 mm. To wymiary pełnowymiarowego SUV-a — dłuższy niż BMW X5, z rozstawem osi bliższym modelom siedmioosobowym, mimo że producent oferuje wyłącznie układ pięcioosobowy. Masa własna wersji dwusilnikowej to 2599 kg.</p>
<h2>Elektronika Huawei</h2>
<p>To model, w którym Huawei odpowiada za całą warstwę cyfrową. GX7 dostaje asystenta jazdy Qiankun ADS 5 i cztery skanery LiDAR, w tym jeden o rozdzielczości 896 kanałów, a w kabinie system HarmonySpace 6, dwa połączone ekrany 15,6 cala i wyświetlacz AR na przedniej szybie o przekątnej 88 cali.</p>
<h2>Kiedy GX7 będzie dostępny w Polsce</h2>
<p>Przedsprzedaż w Chinach ruszyła 4 września 2026, pierwsze dostawy do klientów planowane są jesienią. Prima-Auto sprowadza auta na zamówienie — ustalamy wersję, znajdujemy egzemplarz, organizujemy transport morski, odprawę celną i homologację, a odbierasz auto zarejestrowane w Polsce. Zwykle zajmuje to 10–14 tygodni od potwierdzenia zamówienia.</p>
<h2>Dla kogo jest GX7</h2>
<p>Dla kogoś, kto chce dużego SUV-a z najnowszą elektroniką Huawei i nie boi się marki bez europejskiej sieci. Jeśli wolisz model dostępny od ręki, zobacz nasze aktualne oferty dużych SUV-ów hybrydowych — część z nich stoi już na placu w Polsce.</p>
HTML,
    'faq'         => [
        ['q' => 'Ile kosztuje Aistaland GX7 w Polsce?', 'a' => 'Orientacyjnie od 262 000 zł za wersję podstawową do 315 000 zł za trzysilnikową. Kwoty zawierają transport z Chin, cło, VAT, homologację i obsługę Prima-Auto. Ostateczna cena zależy od kursu walut i konkretnego egzemplarza.'],
        ['q' => 'Czym jest marka Aistaland?', 'a' => 'To marka powołana wspólnie przez chiński koncern GAC i Huaweia, po chińsku 启境. GX7 jest jej drugim modelem, po sedanie GT7. Huawei odpowiada w tych autach za asystenta jazdy i system multimedialny.'],
        ['q' => 'Jaki zasięg ma Aistaland GX7?', 'a' => 'Na samym prądzie od 325 do 385 km według chińskiej procedury CLTC, zależnie od wersji. Razem z generatorem spalinowym producent deklaruje do 1400 km łącznego zasięgu.'],
        ['q' => 'Czy Aistaland GX7 jest dostępny w Europie?', 'a' => 'Nie. Marka nie ma dystrybucji w Europie ani w Polsce, a GX7 jest sprzedawany wyłącznie na rynku chińskim. Do Polski trafia tylko drogą importu indywidualnego.'],
        ['q' => 'Jak działa napęd EREV w GX7?', 'a' => 'Koła napędzają wyłącznie silniki elektryczne, a jednostka spalinowa 1.5 o mocy 170 KM pracuje jako generator ładujący baterię. Na krótkich trasach jeździsz jak autem elektrycznym, na dłuższych tankujesz benzynę i nie szukasz ładowarki.'],
    ],
];

// ------------------------------------------------ Xiaomi SkyNomad N90
$HUBY['n90'] = [
    'make_slug'   => 'xiaomi',
    'make_name'   => 'Xiaomi',
    'serie_name'  => 'SkyNomad N90',
    'serie_slug'  => 'skynomad-n90',
    'full_title'  => 'Xiaomi SkyNomad N90',
    'api_value'   => 'Xiaomi N90',
    'title'       => 'Xiaomi SkyNomad N90 cena w Polsce — od 305 000 zł | Prima-Auto',
    'desc'        => 'Xiaomi SkyNomad N90 — cena w Polsce od 305 000 zł. Siedmioosobowy SUV EREV, 310 kW, 464 km CLTC na prądzie, zasięg łączny do 1705 km. Import przez Prima-Auto.',
    'h1_suffix'   => 'cena w Polsce i import z Chin',
    'focus_kw'    => 'Xiaomi SkyNomad N90',
    'lead'        => 'Xiaomi SkyNomad N90 to pierwszy duży SUV Xiaomi i pierwszy model marki z napędem hybrydowym szeregowym — przedsprzedaż w Chinach ruszyła 30 lipca 2026 w cenie 299 900 juanów, premiera rynkowa przypada na wrzesień 2026. Po sprowadzeniu do Polski przez Prima-Auto to orientacyjnie od 305 000 zł z cłem, VAT-em i homologacją. Xiaomi nie sprzedaje samochodów w Europie, więc jedyną drogą jest import z Chin.',
    'wiki'        => <<<'HTML'
<p>SkyNomad (chińskie 澎程) to druga linia produktowa Xiaomi po sedanie SU7 i SUV-ie YU7, ogłoszona 9 lipca 2026. N90 jest jej modelem flagowym: siedmioosobowym SUV-em z napędem hybrydowym szeregowym, zbudowanym na nowej architekturze Kunlun.</p>
{{LISTINGS_BAR}}
<h2>Ile kosztuje Xiaomi SkyNomad N90 w Polsce</h2>
<p>Chińska cena przedsprzedażowa wersji Max siedmioosobowej to 299 900 juanów. Po przeliczeniu przez nasz kalkulator importu — z transportem, cłem 10%, VAT-em 23%, homologacją i obsługą — daje to orientacyjnie <strong>od 305 000 zł</strong>. Wersja pięcioosobowa Max ma na razie nieogłoszoną cenę katalogową. Hybrydy plug-in o pojemności do 2000 cm³ są w Polsce zwolnione z akcyzy.</p>
<h2>Napęd i zasięg</h2>
<p>N90 ma dwa silniki elektryczne o łącznej mocy 310 kW i napęd na cztery koła, baterię niklowo-manganowo-kobaltową 76 kWh oraz generator — silnik 1.5 o mocy 152 KM. Na samym prądzie przejeżdża 464 km według chińskiej procedury CLTC (370 km w surowszej WLTC), a łączny zasięg producent deklaruje na 1705 km. W teście drogowym przeprowadzonym pod koniec sierpnia 2026 auto przejechało 1230 km na jednym tankowaniu i naładowaniu.</p>
<h2>Wymiary i siedem miejsc</h2>
<p>Nadwozie mierzy 5285 mm długości, 1998 mm szerokości i 1825 mm wysokości przy rozstawie osi 3080 mm — to wymiary pełnowymiarowego SUV-a, porównywalne z Volvo EX90 czy Li Auto L9. Masa własna to 2800 kg. Wersja Max ma trzy rzędy siedzeń i siedem miejsc, wariant pięcioosobowy stawia na przestrzeń zamiast trzeciego rzędu.</p>
<h2>Wnętrze i wersja kempingowa</h2>
<p>Xiaomi projektowało kabinę N90 jako przestrzeń do przebywania, nie tylko do jazdy — przednie fotele obracają się do tyłu, a producent zapowiedział odmianę Camping z podnoszonym dachem sypialnym. To pierwszy model marki pomyślany pod dłuższe wyjazdy rodzinne, a nie pod osiągi, którymi Xiaomi budowało rozpoznawalność przy SU7.</p>
<h2>Ładowanie</h2>
<p>Bateria ładuje się od 20 do 80 procent w około 18 minut na szybkiej ładowarce prądu stałego. W codziennym użyciu ładowarka nie jest jednak konieczna — przy pustej baterii uruchamia się generator, a auto tankuje się jak samochód spalinowy.</p>
<h2>Kiedy SkyNomad N90 będzie dostępny w Polsce</h2>
<p>Premiera rynkowa w Chinach przypada na wrzesień 2026, więc pierwsze egzemplarze u chińskich dealerów pojawią się w następnych tygodniach. Prima-Auto sprowadza auta na zamówienie: ustalamy wersję i kolor, znajdujemy egzemplarz, organizujemy transport morski, odprawę celną i homologację. Od potwierdzenia zamówienia do odbioru auta zarejestrowanego w Polsce mija zwykle 10–14 tygodni.</p>
<h2>Dla kogo jest N90</h2>
<p>Dla rodziny, która potrzebuje siedmiu miejsc i nie chce się rozglądać za ładowarką w trasie. Jeśli wolisz model dostępny od ręki, sprawdź nasze aktualne oferty dużych SUV-ów hybrydowych — część z nich czeka już na placu w Polsce.</p>
HTML,
    'faq'         => [
        ['q' => 'Ile kosztuje Xiaomi SkyNomad N90 w Polsce?', 'a' => 'Orientacyjnie od 305 000 zł za wersję Max siedmioosobową. Kwota zawiera transport z Chin, cło, VAT, homologację i obsługę Prima-Auto. Ostateczna cena zależy od kursu walut i konkretnego egzemplarza.'],
        ['q' => 'Czym SkyNomad różni się od Xiaomi SU7 i YU7?', 'a' => 'SU7 i YU7 to samochody czysto elektryczne. SkyNomad to osobna linia z napędem hybrydowym szeregowym: koła napędza prąd, a silnik spalinowy 1.5 pracuje jako generator. N90 jest też znacznie większy i ma siedem miejsc.'],
        ['q' => 'Jaki zasięg ma Xiaomi SkyNomad N90?', 'a' => 'Na samym prądzie 464 km według chińskiej procedury CLTC i 370 km według surowszej WLTC. Razem z generatorem producent deklaruje do 1705 km; w teście drogowym z sierpnia 2026 auto przejechało 1230 km.'],
        ['q' => 'Czy Xiaomi sprzedaje samochody w Polsce?', 'a' => 'Nie. Xiaomi sprzedaje auta wyłącznie na rynku chińskim i nie ma w Europie sieci dealerskiej. Każdy egzemplarz w Polsce pochodzi z importu indywidualnego.'],
        ['q' => 'Ile miejsc ma Xiaomi SkyNomad N90?', 'a' => 'Wersja Max ma trzy rzędy siedzeń i siedem miejsc. Dostępny jest też wariant pięcioosobowy, w którym miejsce trzeciego rzędu zajmuje dodatkowa przestrzeń bagażowa.'],
    ],
];

// ------------------------------------------------ Xiaomi SkyNomad N70
$HUBY['n70'] = [
    'make_slug'   => 'xiaomi',
    'make_name'   => 'Xiaomi',
    'serie_name'  => 'SkyNomad N70',
    'serie_slug'  => 'skynomad-n70',
    'full_title'  => 'Xiaomi SkyNomad N70',
    'api_value'   => 'Xiaomi N70',
    'title'       => 'Xiaomi SkyNomad N70 cena w Polsce — od 271 000 zł | Prima-Auto',
    'desc'        => 'Xiaomi SkyNomad N70 — cena w Polsce od 271 000 zł. Pięcioosobowy SUV EREV, do 310 kW i 505 km CLTC na prądzie. Premiera 2026. Import przez Prima-Auto.',
    'h1_suffix'   => 'cena w Polsce i import z Chin',
    'focus_kw'    => 'Xiaomi SkyNomad N70',
    'lead'        => 'Xiaomi SkyNomad N70 to mniejszy, pięcioosobowy brat SUV-a N90 — również z napędem hybrydowym szeregowym, w cenie katalogowej od 259 900 juanów za wersję Max. Po sprowadzeniu do Polski przez Prima-Auto to orientacyjnie od 271 000 zł z cłem, VAT-em i homologacją. Xiaomi nie prowadzi sprzedaży w Europie, więc auto dostępne jest wyłącznie z importu.',
    'wiki'        => <<<'HTML'
<p>N70 to drugi model linii SkyNomad (chińskie 澎程), obok większego N90. Jest krótszy o ponad 30 centymetrów, ma pięć miejsc zamiast siedmiu i został pomyślany jako propozycja dla mniejszej rodziny, która chce hybrydy szeregowej bez wymiarów auta pełnowymiarowego.</p>
{{LISTINGS_BAR}}
<h2>Ile kosztuje Xiaomi SkyNomad N70 w Polsce</h2>
<p>Chińska cena katalogowa wersji Max to 259 900 juanów; wersja Pro nie ma jeszcze ogłoszonej ceny. Po przeliczeniu przez nasz kalkulator importu — z transportem, cłem 10%, VAT-em 23%, homologacją i obsługą — wychodzi orientacyjnie <strong>od 271 000 zł</strong>. Hybrydy plug-in o pojemności do 2000 cm³ są w Polsce zwolnione z akcyzy.</p>
<h2>Dwie wersje: Pro i Max</h2>
<ul>
<li><strong>Pro</strong> — jeden silnik 210 kW, napęd na przód, bateria litowo-żelazowo-fosforanowa 52 kWh, 270 km zasięgu elektrycznego według WLTC, prędkość maksymalna 190 km/h.</li>
<li><strong>Max</strong> — dwa silniki o łącznej mocy 310 kW i napęd na cztery koła, bateria niklowo-manganowo-kobaltowa 76 kWh, 505 km według CLTC i 380 km według WLTC.</li>
</ul>
<p>Obie wersje korzystają z tego samego generatora: silnika 1.5 o mocy 152 KM, który ładuje baterię i nie napędza kół bezpośrednio.</p>
<h2>Wymiary i przestrzeń</h2>
<p>N70 mierzy 4960 mm długości, 1998 mm szerokości i 1785 mm wysokości przy rozstawie osi 2950 mm. Jest więc niemal tak szeroki jak N90, ale wyraźnie krótszy — bliżej mu wymiarami do klasycznego SUV-a klasy średniej. Masa własna wersji Pro to 2453 kg. Pięć miejsc, nadwozie pięciodrzwiowe.</p>
<h2>Ładowanie i codzienne użytkowanie</h2>
<p>Wersja Max ładuje baterię od 20 do 80 procent w około 18 minut na ładowarce prądu stałego. Sam zasięg elektryczny — od 270 do 505 km zależnie od wersji — w praktyce pokrywa całą codzienną jazdę, a generator włącza się dopiero w dłuższej trasie, więc auto nie jest uzależnione od dostępności ładowarek.</p>
<h2>Czego jeszcze nie wiadomo o N70</h2>
<p>Model jest tuż przed premierą, więc część informacji producent zachowuje na start sprzedaży. Nie ma jeszcze ogłoszonej ceny katalogowej wersji Pro, a oficjalny katalog nie ujawnia list wyposażenia poszczególnych odmian — znane są na razie dane napędu, baterii i nadwozia. Gdy Xiaomi opublikuje pełną specyfikację, uzupełnimy tę stronę; jeśli szukasz konkretnej wersji, napisz do nas, a sprawdzimy dostępność u chińskich dealerów.</p>
<h2>Kiedy N70 będzie dostępny w Polsce</h2>
<p>Model wchodzi na rynek chiński w 2026 roku razem z resztą linii SkyNomad. Prima-Auto sprowadza auta na zamówienie: ustalamy wersję i kolor, znajdujemy egzemplarz u sprawdzonego sprzedawcy, organizujemy transport morski, odprawę celną i homologację. Od potwierdzenia zamówienia do odbioru zarejestrowanego auta mija zwykle 10–14 tygodni.</p>
<h2>N70 czy N90</h2>
<p>N70 wybierzesz, jeśli zależy Ci na hybrydzie szeregowej Xiaomi, ale nie potrzebujesz trzeciego rzędu siedzeń i wolisz auto łatwiejsze w mieście. N90 ma siedem miejsc, dłuższy rozstaw osi i wyższą cenę. Jeśli szukasz czegoś dostępnego od ręki, zobacz nasze bieżące oferty SUV-ów hybrydowych.</p>
HTML,
    'faq'         => [
        ['q' => 'Ile kosztuje Xiaomi SkyNomad N70 w Polsce?', 'a' => 'Orientacyjnie od 271 000 zł za wersję Max. Kwota zawiera transport z Chin, cło, VAT, homologację i obsługę Prima-Auto. Cena wersji Pro nie została jeszcze ogłoszona przez producenta.'],
        ['q' => 'Czym N70 różni się od N90?', 'a' => 'N70 jest krótszy o ponad 30 centymetrów, ma pięć miejsc zamiast siedmiu i krótszy rozstaw osi. Oba modele korzystają z tego samego napędu hybrydowego szeregowego i tego samego generatora 1.5 o mocy 152 KM.'],
        ['q' => 'Jaki zasięg ma Xiaomi SkyNomad N70?', 'a' => 'Wersja Pro przejeżdża 270 km na prądzie według procedury WLTC, wersja Max 505 km według chińskiej CLTC i 380 km według WLTC. Po rozładowaniu baterii pracę przejmuje generator spalinowy.'],
        ['q' => 'Czy N70 ma napęd na cztery koła?', 'a' => 'Tylko wersja Max, która ma dwa silniki elektryczne o łącznej mocy 310 kW. Wersja Pro ma jeden silnik 210 kW i napęd na przednią oś.'],
        ['q' => 'Czy Xiaomi SkyNomad N70 można kupić w polskim salonie?', 'a' => 'Nie. Xiaomi sprzedaje samochody wyłącznie na rynku chińskim i nie ma w Europie sieci dealerskiej, więc każdy egzemplarz w Polsce pochodzi z importu indywidualnego.'],
    ],
];

// ---------------------------------------------------------------- walidacja

$bledy = [];
foreach ($HUBY as $k => $h) {
    if (mb_strlen($h['title']) > 65)  $bledy[] = "$k: title ma " . mb_strlen($h['title']) . " znakow (limit 65)";
    if (mb_strlen($h['desc']) > 165)  $bledy[] = "$k: desc ma " . mb_strlen($h['desc']) . " znakow (limit 165)";
    $json = wp_json_encode($h['faq'], JSON_UNESCAPED_UNICODE);
    if (json_decode($json, true) === null) $bledy[] = "$k: FAQ nie parsuje sie jako JSON";
    foreach (['„', '”', '“', '’'] as $smart) {
        if (strpos($json, $smart) !== false) $bledy[] = "$k: FAQ zawiera smart quote $smart";
    }
    if (!preg_match('/[ąćęłńóśźż]/u', $json)) $bledy[] = "$k: FAQ bez diakrytyk (regresja T-193)";
    if (strpos($h['wiki'], '{{LISTINGS_BAR}}') === false) $bledy[] = "$k: wiki bez tokenu LISTINGS_BAR";
    if (substr_count($h['wiki'], '<h2>') !== 7) $bledy[] = "$k: wiki ma " . substr_count($h['wiki'], '<h2>') . " naglowkow H2 (oczekiwane 7)";
}

echo "=== WALIDACJA ===\n";
echo $bledy ? implode("\n", $bledy) . "\n" : "OK — wszystkie huby przechodza walidacje\n";
foreach ($HUBY as $k => $h) {
    printf("%-6s title %2d zn. | desc %3d zn. | wiki %4d zn. | FAQ %d pytan\n",
        $k, mb_strlen($h['title']), mb_strlen($h['desc']), mb_strlen(wp_strip_all_tags($h['wiki'])), count($h['faq']));
}

// ---------------------------------------------------------------- podglad

if ($tryb === 'podglad') {
    if ($out === '') { echo "\nPodaj plik wyjsciowy HTML.\n"; return; }
    $html = '<!doctype html><meta charset="utf-8"><title>Huby premierowe — podglad</title>'
        . '<style>body{font:16px/1.6 system-ui;max-width:820px;margin:2rem auto;padding:0 1rem;color:#111}'
        . 'h1{font-size:1.6rem}h2{font-size:1.15rem;margin-top:1.6rem}h3{font-size:1rem;color:#666;margin:1.4rem 0 .3rem}'
        . '.hub{border:1px solid #ddd;border-radius:10px;padding:1.2rem 1.5rem;margin:2rem 0}'
        . '.meta{background:#f6f6f6;padding:.8rem 1rem;border-radius:6px;font-size:.9rem}'
        . '.lead{background:#eef5ff;padding:.8rem 1rem;border-radius:6px}'
        . 'table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:.35rem .5rem;text-align:left;font-size:.9rem}'
        . 'code{background:#f2f2f2;padding:.1rem .3rem;border-radius:3px}</style>'
        . '<h1>Huby premierowe — podglad do akceptu (' . date('Y-m-d H:i') . ')</h1>'
        . '<p>Nic z tego nie jest jeszcze zapisane w bazie. Ceny PL policzone kalkulatorem importu '
        . '(<code>AsiaAuto_Price::calculateFromCny</code>), dane techniczne z katalogu Autohome.</p>';
    foreach ($HUBY as $k => $h) {
        $html .= '<div class="hub"><h1>' . esc_html($h['full_title']) . '</h1>';
        $html .= '<div class="meta"><b>URL:</b> /samochody/' . esc_html($h['make_slug']) . '/' . esc_html($h['serie_slug']) . '/<br>'
            . '<b>H1:</b> ' . esc_html($h['full_title'] . ' ' . $h['h1_suffix']) . '<br>'
            . '<b>Title:</b> ' . esc_html($h['title']) . ' <i>(' . mb_strlen($h['title']) . ' zn.)</i><br>'
            . '<b>Description:</b> ' . esc_html($h['desc']) . ' <i>(' . mb_strlen($h['desc']) . ' zn.)</i><br>'
            . '<b>Nazwa termu:</b> ' . esc_html($h['serie_name']) . ' &nbsp; <b>marka:</b> ' . esc_html($h['make_name']) . '</div>';
        $html .= '<h3>Lead (nad ofertami)</h3><div class="lead">' . esc_html($h['lead']) . '</div>';
        $html .= '<h3>Tresc huba</h3>' . str_replace('{{LISTINGS_BAR}}',
            '<p style="background:#fff8e1;padding:.5rem 1rem;border-radius:6px;font-size:.85rem">[tu pasek z ofertami — dzis pusty, bo model nie ma jeszcze ofert]</p>', $h['wiki']);
        $html .= '<h3>FAQ</h3><table><tr><th>Pytanie</th><th>Odpowiedz</th></tr>';
        foreach ($h['faq'] as $f) $html .= '<tr><td>' . esc_html($f['q']) . '</td><td>' . esc_html($f['a']) . '</td></tr>';
        $html .= '</table></div>';
    }
    file_put_contents($out, $html);
    echo "\nPodglad zapisany: $out\n";
    return;
}

// ---------------------------------------------------------------- zapis

if ($tryb !== 'apply') { echo "\nNieznany tryb '$tryb'. Uzyj: podglad <plik.html> albo apply\n"; return; }
if ($bledy) { echo "\nSTOP — walidacja nie przeszla, nic nie zapisuje.\n"; return; }

foreach ($HUBY as $k => $h) {
    // marka
    $make = get_term_by('slug', $h['make_slug'], 'make');
    if (!$make) {
        $r = wp_insert_term($h['make_name'], 'make', ['slug' => $h['make_slug']]);
        if (is_wp_error($r)) { echo "$k: blad marki — " . $r->get_error_message() . "\n"; continue; }
        $make = get_term($r['term_id'], 'make');
        echo "$k: utworzona marka {$h['make_name']} (term {$make->term_id})\n";
    }
    // seria (pod marka jako parent — taksonomia serie jest hierarchiczna)
    $serie = get_terms(['taxonomy' => 'serie', 'slug' => $h['serie_slug'], 'parent' => $make->term_id, 'hide_empty' => false]);
    $serie = (!is_wp_error($serie) && $serie) ? $serie[0] : null;
    if (!$serie) {
        $r = wp_insert_term($h['serie_name'], 'serie', ['slug' => $h['serie_slug'], 'parent' => $make->term_id]);
        if (is_wp_error($r)) { echo "$k: blad serii — " . $r->get_error_message() . "\n"; continue; }
        $serie = get_term($r['term_id'], 'serie');
        echo "$k: utworzony hub {$h['serie_name']} (term {$serie->term_id})\n";
    }
    $tid = (int) $serie->term_id;

    update_term_meta($tid, '_serie_full_title', $h['full_title']);
    update_term_meta($tid, '_serie_api_value', $h['api_value']);
    update_term_meta($tid, '_asiaauto_primary_make_slug', $h['make_slug']);
    update_term_meta($tid, '_asiaauto_lead', $h['lead']);
    update_term_meta($tid, '_asiaauto_h1_suffix', $h['h1_suffix']);
    update_term_meta($tid, '_asiaauto_pl_availability', 'import_only');
    update_term_meta($tid, 'asiaauto_wiki_body', $h['wiki']);
    update_term_meta($tid, 'asiaauto_faq_json', wp_json_encode($h['faq'], JSON_UNESCAPED_UNICODE));
    update_term_meta($tid, 'asiaauto_seo_desc', $h['desc']);
    update_term_meta($tid, 'rank_math_title', $h['title']);
    update_term_meta($tid, 'rank_math_description', $h['desc']);
    update_term_meta($tid, 'rank_math_focus_keyword', $h['focus_kw']);
    update_term_meta($tid, 'rank_math_robots', ['index']);
    update_term_meta($tid, '_asiaauto_seo_rework', 'v1-premiery-2026-09-07');
    // cena w tytule pochodzi z katalogu producenta, nie z ofert — cron przeliczajacy
    // tytuly z ofert nie ma tu czego liczyc, wiec zamrazamy do czasu pierwszej oferty
    update_term_meta($tid, '_asiaauto_skip_title_regen', 1);

    printf("%s: zapisane meta dla term %d (/samochody/%s/%s/)\n", $k, $tid, $h['make_slug'], $h['serie_slug']);
}
echo "\nGotowe. Smoke test: curl kazdego URL-a, sprawdz H1, lead, robots i FAQPage.\n";
