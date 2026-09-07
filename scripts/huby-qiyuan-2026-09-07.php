<?php
/**
 * huby-qiyuan-2026-09-07.php — treść hubów Changan Qiyuan A07, A06 i Q05.
 *
 * Huby dostały oferty z ręcznego importu 9 sztuk (07.09) i jak każdy hub tworzony przez
 * serie-guard zostały bez treści. Fakty poniżej pochodzą z bazy — z `_asiaauto_extra_prep`
 * zaimportowanych egzemplarzy, nie z szacunków:
 *   A07 — 5 ofert, 148 000–158 000 PLN, sedan BEV 77,94 kWh, 730 km CLTC
 *   A06 — 2 oferty, 143 000–168 000 PLN, sedan, jedna EREV i jedna BEV
 *   Q05 — 2 oferty, 134 000–144 000 PLN, SUV BEV 51,9 kWh, 506 km CLTC
 *
 * Title i description zostawiamy `AsiaAuto_HubTitleGenerator` (huby mają oferty).
 *
 * Użycie:
 *   wp eval-file scripts/huby-qiyuan-2026-09-07.php podglad <plik.html>
 *   wp eval-file scripts/huby-qiyuan-2026-09-07.php apply
 */

$tryb = (string) ($args[0] ?? 'podglad');
$out  = (string) ($args[1] ?? '');

$HUBY = [];

$HUBY['a07'] = [
    'term_id'    => 4772,
    'naglowek'   => 'Changan Qiyuan A07 — /samochody/changan/qiyuan-a07/',
    'full_title' => 'Changan Qiyuan A07',
    'h1_suffix'  => 'cena w Polsce i import z Chin',
    'lead'       => 'Changan Qiyuan A07 to elektryczny sedan z baterią 77,94 kWh i zasięgiem 730 km według chińskiej procedury CLTC. W naszej ofercie kosztuje od około 148 000 zł — to jedno z tańszych aut z tak dużym zasięgiem, jakie da się dziś sprowadzić z Chin. Marka Qiyuan nie ma polskiej dystrybucji, więc auto trafia tu wyłącznie z importu.',
    'wiki'       => <<<'HTML'
<p>Changan Qiyuan A07 to sedan z elektrycznej linii koncernu Changan. Ma 4905 mm długości i rozstaw osi 2900 mm, czyli wymiary klasy średniej, a przy tym baterię i zasięg, jakich w Europie szuka się w autach o dwa razy wyższej cenie.</p>
{{LISTINGS_BAR}}
<h2>Ile kosztuje Changan Qiyuan A07 w Polsce</h2>
<p>W naszej ofercie ceny zaczynają się od około 148 000 zł i dochodzą do 158 000 zł — dokładne kwoty przy konkretnych egzemplarzach widzisz nad tym opisem. Cena zawiera transport morski z Chin, cło, VAT, homologację i naszą obsługę, więc odbierasz auto zarejestrowane w Polsce. Sprowadzamy sztuki z rocznika 2026, w większości z przebiegiem poniżej tysiąca kilometrów.</p>
<h2>Bateria i zasięg</h2>
<p>Egzemplarze w naszej ofercie mają baterię 77,94 kWh i deklarowany zasięg 730 km według chińskiej procedury CLTC. Procedura ta jest łagodniejsza od europejskiej WLTP, więc realnie należy liczyć na wynik niższy o mniej więcej jedną piątą — i tak zostaje ponad 550 km, czyli wartość, której w tej cenie nie oferuje żaden samochód dostępny w polskim salonie.</p>
<h2>Osiągi i ładowanie</h2>
<p>Auto rozpędza się do stu kilometrów na godzinę w 6,9 sekundy, a prędkość maksymalna to 172 km/h. Ładowanie baterii na szybkiej stacji prądu stałego zajmuje około 15 minut w typowym zakresie roboczym.</p>
<h2>Wymiary i przestrzeń</h2>
<p>Nadwozie mierzy 4905 mm długości, 1910 mm szerokości i 1480 mm wysokości przy rozstawie osi 2900 mm. Masa własna to 1925 kg — jak na auto elektryczne z baterią tej wielkości niewiele, co przekłada się na zużycie energii i zachowanie w zakrętach.</p>
<h2>Wersje w naszej ofercie</h2>
<p>Sprowadzamy odmiany Long Range Blue Whale BEV 730 w wariantach Luxury i Flagship. Różnią się wyposażeniem wnętrza i systemami wspomagania, przy tej samej baterii i tym samym zasięgu. Pełną specyfikację konkretnego egzemplarza podajemy przy każdej ofercie.</p>
<h2>Czym A07 różni się od A06 i Q05</h2>
<p>A06 to mniejszy sedan tej samej marki, dostępny również w wersji hybrydowej szeregowej. Q05 to kompaktowy SUV. A07 jest największy z całej trójki i to on ma najdłuższy zasięg — jeśli szukasz auta na trasy, jest naturalnym wyborem.</p>
<h2>Kupno i sprowadzenie</h2>
<p>Wybierasz egzemplarz z naszej listy albo zamawiasz konkretną wersję, a my znajdujemy ją w Chinach. Dalej transport morski, odprawa celna, homologacja i rejestracja — całość zajmuje zwykle 10–14 tygodni. Samochody elektryczne są w Polsce zwolnione z akcyzy, co realnie obniża koszt sprowadzenia.</p>
HTML,
    'faq'        => [
        ['q' => 'Ile kosztuje Changan Qiyuan A07 w Polsce?', 'a' => 'W naszej ofercie od około 148 000 do 158 000 zł, zależnie od wersji i przebiegu. Kwota zawiera transport z Chin, cło, VAT, homologację i obsługę Prima-Auto.'],
        ['q' => 'Jaki zasięg ma Changan Qiyuan A07?', 'a' => 'Deklarowane 730 km według chińskiej procedury CLTC przy baterii 77,94 kWh. CLTC jest łagodniejsze od europejskiego WLTP, więc realnie należy liczyć na około 550–600 km.'],
        ['q' => 'Czy Changan Qiyuan A07 jest dostępny w polskich salonach?', 'a' => 'Nie. Elektryczna linia Qiyuan koncernu Changan nie ma w Polsce oficjalnej dystrybucji, więc każdy egzemplarz pochodzi z importu indywidualnego.'],
        ['q' => 'Jak szybko ładuje się A07?', 'a' => 'Na szybkiej ładowarce prądu stałego uzupełnienie baterii w typowym zakresie roboczym zajmuje około 15 minut.'],
        ['q' => 'Czy trzeba płacić akcyzę za sprowadzenie A07?', 'a' => 'Nie. Samochody w pełni elektryczne są w Polsce zwolnione z akcyzy, a nasza cena i tak zawiera wszystkie opłaty, cło, VAT i homologację.'],
    ],
];

$HUBY['a06'] = [
    'term_id'    => 6884,
    'naglowek'   => 'Changan Qiyuan A06 — /samochody/changan/qiyuan-a06/',
    'full_title' => 'Changan Qiyuan A06',
    'h1_suffix'  => 'cena w Polsce i import z Chin',
    'lead'       => 'Changan Qiyuan A06 to kompaktowy sedan dostępny w dwóch odmianach napędu: w pełni elektrycznej z zasięgiem 510 km oraz hybrydowej szeregowej, w której silnik 1.5 pracuje jako generator. W naszej ofercie kosztuje od około 143 000 zł. Marka nie ma polskiej dystrybucji, więc auto sprowadzamy z Chin na zamówienie.',
    'wiki'       => <<<'HTML'
<p>Changan Qiyuan A06 to sedan klasy kompaktowej o długości 4885 mm i rozstawie osi 2922 mm — nietypowo długim jak na tę klasę, co widać po miejscu na tylnej kanapie. Auto jest oferowane równolegle z napędem czysto elektrycznym i hybrydowym szeregowym, więc wybór zależy od tego, ile jeździsz w trasie.</p>
{{LISTINGS_BAR}}
<h2>Ile kosztuje Changan Qiyuan A06 w Polsce</h2>
<p>Nasze ceny zaczynają się od około 143 000 zł i sięgają 168 000 zł za wersję z lidarem — aktualne kwoty widzisz nad tym opisem. Cena obejmuje transport morski, cło, VAT, homologację i obsługę, czyli odbierasz auto zarejestrowane w Polsce. Sprowadzamy egzemplarze z rocznika 2026 o niewielkim przebiegu.</p>
<h2>Dwa napędy do wyboru</h2>
<ul>
<li><strong>Wersja elektryczna 510Max</strong> — bateria zapewniająca 510 km zasięgu według procedury CLTC, bez silnika spalinowego.</li>
<li><strong>Wersja hybrydowa szeregowa 240 LiDAR Ultra+</strong> — bateria 28,39 kWh daje 240 km na samym prądzie, a po jej rozładowaniu uruchamia się generator: silnik 1.5 o mocy 98 KM. Ta odmiana ma też skaner LiDAR i rozbudowanego asystenta jazdy.</li>
</ul>
<h2>Wymiary i masa</h2>
<p>Nadwozie ma 4885 mm długości, 1916 mm szerokości i 1496 mm wysokości. Rozstaw osi 2922 mm jest większy niż w wielu autach klasy średniej, co przekłada się na przestrzeń z tyłu. Masa własna wersji hybrydowej to 1800 kg.</p>
<h2>Ładowanie i codzienne użytkowanie</h2>
<p>Bateria ładuje się na szybkiej stacji prądu stałego w około 15 minut. W odmianie hybrydowej ładowarka nie jest warunkiem jazdy — przy pustym akumulatorze auto tankuje się jak spalinowe, a 240 km zasięgu elektrycznego wystarcza na cały tydzień dojazdów do pracy.</p>
<h2>Wyposażenie</h2>
<p>Wersja z dopiskiem LiDAR ma skaner laserowy na dachu i asystenta jazdy zdolnego prowadzić auto w ruchu miejskim i autostradowym w granicach poziomu L2. To wyposażenie, które w europejskich autach tej klasy pojawia się rzadko i zwykle za dużą dopłatą.</p>
<h2>A06 czy A07</h2>
<p>A07 jest większy, wyłącznie elektryczny i ma znacznie dłuższy zasięg — 730 km wobec 510 km. A06 jest tańszy i dostępny w wersji hybrydowej, która nie wymaga ładowarki w trasie. Jeśli jeździsz głównie po mieście, A06 wystarcza; na długie trasy lepszy jest A07 albo hybrydowa odmiana A06.</p>
HTML,
    'faq'        => [
        ['q' => 'Ile kosztuje Changan Qiyuan A06 w Polsce?', 'a' => 'W naszej ofercie od około 143 000 do 168 000 zł, zależnie od wersji napędu i wyposażenia. Kwota zawiera transport z Chin, cło, VAT, homologację i obsługę Prima-Auto.'],
        ['q' => 'Czy A06 jest elektryczny czy hybrydowy?', 'a' => 'Występuje w obu odmianach. Wersja 510Max jest w pełni elektryczna, a wersja 240 LiDAR Ultra+ to hybryda szeregowa: koła napędza prąd, a silnik 1.5 o mocy 98 KM pracuje jako generator.'],
        ['q' => 'Jaki zasięg ma Changan Qiyuan A06?', 'a' => 'Wersja elektryczna 510 km według chińskiej procedury CLTC. Wersja hybrydowa przejeżdża 240 km na samym prądzie, a dalej korzysta z generatora, więc łączny zasięg ogranicza tylko pojemność baku.'],
        ['q' => 'Co oznacza LiDAR w nazwie wersji?', 'a' => 'To skaner laserowy zamontowany na dachu, który zasila asystenta jazdy działającego w poziomie L2 — auto potrafi utrzymywać pas i odległość oraz wspomagać w ruchu miejskim.'],
        ['q' => 'Czy Changan Qiyuan A06 można kupić w Polsce?', 'a' => 'Nie w salonie. Elektryczna linia Qiyuan nie ma polskiej dystrybucji, więc auto trafia tu wyłącznie drogą importu indywidualnego z Chin.'],
    ],
];

$HUBY['q05'] = [
    'term_id'    => 6583,
    'naglowek'   => 'Changan Qiyuan Q05 — /samochody/changan/qiyuan-q05/',
    'full_title' => 'Changan Qiyuan Q05',
    'h1_suffix'  => 'cena w Polsce i import z Chin',
    'lead'       => 'Changan Qiyuan Q05 to kompaktowy SUV elektryczny z baterią 51,9 kWh i zasięgiem 506 km według procedury CLTC. W naszej ofercie kosztuje od około 134 000 zł, co czyni go jednym z najtańszych elektrycznych SUV-ów, jakie sprowadzamy z Chin. Model nie jest sprzedawany w Polsce, więc dostępny jest wyłącznie z importu.',
    'wiki'       => <<<'HTML'
<p>Changan Qiyuan Q05 to niewielki SUV o długości 4435 mm — auto na miasto, bliższe rozmiarami Renault Capturowi niż dużym SUV-om. Jest w pełni elektryczny, waży 1550 kg i przy baterii 51,9 kWh oferuje zasięg, który spokojnie pokrywa tygodniowe użytkowanie bez ładowania.</p>
{{LISTINGS_BAR}}
<h2>Ile kosztuje Changan Qiyuan Q05 w Polsce</h2>
<p>W naszej ofercie ceny zaczynają się od około 134 000 zł i dochodzą do 144 000 zł — dokładne kwoty przy dostępnych egzemplarzach widzisz nad tym opisem. Cena zawiera transport morski z Chin, cło, VAT, homologację i obsługę, więc odbierasz auto zarejestrowane w Polsce. Auta elektryczne są zwolnione z akcyzy, co obniża całkowity koszt sprowadzenia.</p>
<h2>Bateria i zasięg</h2>
<p>Bateria ma 51,9 kWh, a deklarowany zasięg to 506 km według chińskiej procedury CLTC. Ponieważ CLTC jest łagodniejsze od europejskiego WLTP, realnie warto liczyć na około 380–400 km — wciąż dużo jak na auto tej wielkości i w tej cenie. Ładowanie na szybkiej stacji prądu stałego zajmuje około 15 minut.</p>
<h2>Wymiary i przestrzeń</h2>
<p>Nadwozie mierzy 4435 mm długości, 1855 mm szerokości i 1595 mm wysokości przy rozstawie osi 2735 mm. Masa własna 1550 kg jest niska jak na elektrycznego SUV-a, co widać w zużyciu energii. Prędkość maksymalna to 160 km/h — Q05 jest pomyślany jako auto miejskie i podmiejskie, nie autostradowe.</p>
<h2>Wersje w naszej ofercie</h2>
<p>Sprowadzamy odmiany 506Max i 506Max+. Obie mają tę samą baterię i ten sam zasięg, różnią się poziomem wyposażenia wnętrza i systemami wspomagania kierowcy. Pełną specyfikację konkretnego egzemplarza podajemy przy każdej ofercie.</p>
<h2>Dla kogo jest Q05</h2>
<p>Dla kogoś, kto szuka elektrycznego SUV-a do miasta i codziennych dojazdów, a nie chce wydawać ponad 200 000 zł. Za tę cenę w polskim salonie kupuje się mniejsze auto z krótszym zasięgiem. Jeśli potrzebujesz większej przestrzeni albo dłuższych tras, zobacz sedan Qiyuan A07 z zasięgiem 730 km.</p>
<h2>Jak przebiega sprowadzenie</h2>
<p>Wybierasz egzemplarz z naszej listy albo zamawiasz konkretną wersję i kolor. Znajdujemy auto u sprawdzonego chińskiego sprzedawcy, pokazujemy zdjęcia i dokumenty, organizujemy transport morski, odprawę celną, homologację i rejestrację. Od potwierdzenia zamówienia do odbioru mija zwykle 10–14 tygodni.</p>
HTML,
    'faq'        => [
        ['q' => 'Ile kosztuje Changan Qiyuan Q05 w Polsce?', 'a' => 'W naszej ofercie od około 134 000 do 144 000 zł. Kwota zawiera transport z Chin, cło, VAT, homologację i obsługę Prima-Auto, czyli auto odbierasz zarejestrowane i gotowe do jazdy.'],
        ['q' => 'Jaki zasięg ma Changan Qiyuan Q05?', 'a' => 'Deklarowane 506 km według chińskiej procedury CLTC przy baterii 51,9 kWh. Realnie, przy europejskim stylu jazdy, warto liczyć na około 380–400 km.'],
        ['q' => 'Jakiej wielkości jest Q05?', 'a' => 'To kompaktowy SUV o długości 4435 mm, szerokości 1855 mm i wysokości 1595 mm, z rozstawem osi 2735 mm. Rozmiarami odpowiada popularnym miejskim SUV-om segmentu B.'],
        ['q' => 'Czy Q05 nadaje się na trasy?', 'a' => 'Nadaje się, ale został pomyślany jako auto miejskie i podmiejskie — prędkość maksymalna to 160 km/h. Na częste długie trasy lepszy będzie sedan Qiyuan A07 z zasięgiem 730 km.'],
        ['q' => 'Czy za Q05 trzeba płacić akcyzę?', 'a' => 'Nie. Samochody w pełni elektryczne są w Polsce zwolnione z akcyzy. Nasza cena i tak zawiera wszystkie opłaty: cło, VAT, homologację i rejestrację.'],
    ],
];

// ---------------------------------------------------------------- walidacja
$bledy = [];
foreach ($HUBY as $k => $h) {
    $json = wp_json_encode($h['faq'], JSON_UNESCAPED_UNICODE);
    if (json_decode($json, true) === null) $bledy[] = "$k: FAQ nie parsuje sie";
    foreach (['„', '”', '“', '’'] as $sq) if (strpos($json, $sq) !== false) $bledy[] = "$k: smart quote";
    if (!preg_match('/[ąćęłńóśźż]/u', $json)) $bledy[] = "$k: FAQ bez diakrytyk";
    if (strpos($h['wiki'], '{{LISTINGS_BAR}}') === false) $bledy[] = "$k: brak LISTINGS_BAR";
    if (!get_term($h['term_id']) instanceof WP_Term) $bledy[] = "$k: term {$h['term_id']} nie istnieje";
}
echo "=== WALIDACJA ===\n" . ($bledy ? implode("\n", $bledy) . "\n" : "OK\n");
foreach ($HUBY as $k => $h) printf("%-4s wiki %4d zn. | FAQ %d | H2 %d\n", $k,
    mb_strlen(wp_strip_all_tags($h['wiki'])), count($h['faq']), substr_count($h['wiki'], '<h2>'));

if ($tryb === 'podglad') {
    if ($out === '') { echo "Podaj plik wyjsciowy.\n"; return; }
    $x = '<!doctype html><meta charset="utf-8"><title>Qiyuan — podglad</title>'
       . '<style>body{font:16px/1.6 system-ui;max-width:820px;margin:2rem auto;padding:0 1rem}'
       . 'h2{font-size:1.15rem;margin-top:1.5rem}.box{border:1px solid #ddd;border-radius:10px;padding:1rem 1.4rem;margin:1.6rem 0}'
       . '.lead{background:#eef5ff;padding:.7rem 1rem;border-radius:6px}table{border-collapse:collapse;width:100%}'
       . 'td,th{border:1px solid #ddd;padding:.35rem .5rem;text-align:left;font-size:.9rem}</style>';
    foreach ($HUBY as $h) {
        $x .= '<div class="box"><h1>' . esc_html($h['naglowek']) . '</h1>'
            . '<h3>Lead</h3><div class="lead">' . esc_html($h['lead']) . '</div><h3>Tresc</h3>'
            . str_replace('{{LISTINGS_BAR}}', '<p style="background:#fff8e1;padding:.5rem 1rem;border-radius:6px;font-size:.85rem">[pasek ofert]</p>', $h['wiki'])
            . '<h3>FAQ</h3><table><tr><th>Pytanie</th><th>Odpowiedz</th></tr>';
        foreach ($h['faq'] as $f) $x .= '<tr><td>' . esc_html($f['q']) . '</td><td>' . esc_html($f['a']) . '</td></tr>';
        $x .= '</table></div>';
    }
    file_put_contents($out, $x);
    echo "Podglad: $out\n";
    return;
}
if ($tryb !== 'apply') { echo "Uzyj: podglad <plik.html> albo apply\n"; return; }
if ($bledy) { echo "STOP — walidacja nie przeszla.\n"; return; }

foreach ($HUBY as $k => $h) {
    $tid = (int) $h['term_id'];
    update_term_meta($tid, 'asiaauto_wiki_body', $h['wiki']);
    update_term_meta($tid, 'asiaauto_faq_json', wp_json_encode($h['faq'], JSON_UNESCAPED_UNICODE));
    update_term_meta($tid, '_asiaauto_lead', $h['lead']);
    update_term_meta($tid, '_asiaauto_h1_suffix', $h['h1_suffix']);
    update_term_meta($tid, '_serie_full_title', $h['full_title']);
    update_term_meta($tid, '_asiaauto_primary_make_slug', 'changan');
    update_term_meta($tid, '_asiaauto_pl_availability', 'import_only');
    update_term_meta($tid, '_asiaauto_seo_rework', 'v1-premiery-2026-09-07');
    $r = AsiaAuto_HubTitleGenerator::regenerateForTerm($tid);
    printf("%-4s term %d — tresc zapisana | title: %s\n", $k, $tid,
        is_array($r) && !empty($r['title']) ? $r['title'] : 'bez zmian');
}
echo "\nGotowe.\n";
