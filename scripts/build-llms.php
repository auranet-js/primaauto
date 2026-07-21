<?php
/**
 * Build llms.txt — krotki indeks AEO dla modeli jezykowych.
 * Statyczna proza (kuratorska) + dynamiczne top-20 marek / top-30 modeli z DB.
 * Run: cd ~/domains/primaauto.com.pl/public_html && wp eval-file ~/projekty/primaauto/scripts/build-llms.php
 * Cron uruchamia oba: build-llms.php (krotki) + build-llms-full.php (pelny).
 */

if (!defined('ABSPATH')) { fwrite(STDERR, "Run via wp eval-file\n"); exit(1); }

// --- Kuratorskie opisy marek (klucz = make->name). Fallback: intro z wiki_body. ---
$BRAND_DESC = [
    'BYD'         => 'jeden z największych chińskich producentów EV i PHEV',
    'AITO'        => 'premium SUV-y i sedany Huawei × Seres (M5/M7/M8/M9)',
    'Geely'       => 'koncern (Volvo, Polestar, Lotus, Lynk & Co)',
    'XPeng'       => 'sedany i SUV-y EV (P7+, G6, X9, Mona M03)',
    'Volkswagen'  => 'modele JV z chińskim partnerem (FAW/SAIC)',
    'Xiaomi'      => 'debiut motoryzacyjny giganta tech (SU7, YU7)',
    'Zeekr'       => 'premium EV brand Geely (001, 009, 7X)',
    'Chery'       => 'jeden z największych eksporterów CN (Tiggo, Arrizo)',
    'Hongqi'      => 'marka luksusowa należąca do FAW (H5, H9, EHS9)',
    'Avatr'       => 'premium EV joint venture Changan/Huawei/CATL',
    'Changan'     => 'państwowy producent (UNI, Eado, Hunter)',
    'Li Auto'     => 'lider rynku EREV w Chinach (L6, L7, L9, MEGA)',
    'NIO'         => 'premium EV z technologią wymiany baterii (ET5, ET7)',
    'GAC'         => 'państwowy koncern z markami Aion/Trumpchi',
    'Denza'       => 'premium EV joint venture BYD/Mercedes (D9, N7, Z9)',
    'Leapmotor'   => 'EV producent współpracujący ze Stellantisem',
    'Nissan'      => 'modele wyprodukowane w joint venture Dongfeng',
    'Jetour'      => 'marka SUV koncernu Chery',
    'Deepal'      => 'EV brand Changan (S07, L07, S05)',
    'Chery Fulwin'=> 'EV/PHEV sub-brand Chery',
    'Mazda'       => 'modele Changan-Mazda z rynku chińskiego (EZ-6, CX-5)',
    'Volvo'       => 'warianty chińskie/LWB spoza gamy europejskiej',
    'Tank'        => 'ramowe off-roady PHEV marki GWM (300, 700)',
    'Haval'       => 'SUV-y GWM (H6, Big Dog, H9)',
    'Voyah'       => 'premium EV/EREV koncernu Dongfeng',
    'Luxeed'      => 'premium EV/EREV Chery × Huawei',
    'BAIC'        => 'państwowy producent (BJ40, terenowe)',
];

// --- Kuratorskie opisy modeli (klucz = _serie_full_title). Fallback: sam tytul. ---
$MODEL_DESC = [
    'AITO M9'                       => 'flagowy SUV premium klasy E (EREV/EV)',
    'Xiaomi SU7'                    => 'sedan EV — debiut Xiaomi w motoryzacji',
    'AITO M7'                       => 'SUV klasy D (EREV)',
    'Geely Preface'                 => 'sedan klasy D',
    'Geely Monjaro'                 => 'SUV klasy D',
    'AITO M8'                       => 'SUV klasy D (EREV/EV)',
    'Xiaomi YU7'                    => 'SUV EV klasy D — drugi model Xiaomi',
    'BYD Tang'                      => 'SUV PHEV klasy D, 7 miejsc',
    'BYD Tang DM-i'                 => 'SUV PHEV klasy D, 7 miejsc',
    'Changan UNI-V'                 => 'sportowy sedan klasy C',
    'Hongqi H5'                     => 'sedan premium klasy D',
    'BYD Seal 6 DM-i'               => 'sedan PHEV klasy D',
    'XPeng P7+'                     => 'sedan EV klasy D',
    'XPeng P7 Plus'                 => 'sedan EV klasy D',
    'Chery Arrizo 8'                => 'sedan klasy D',
    'AITO M5'                       => 'SUV klasy C (EREV/EV)',
    'XPeng Mona M03'                => 'sedan EV klasy C',
    'Denza D9 DM-i'                 => 'MPV premium PHEV',
    'Avatr 06'                      => 'sedan/SUV coupé EV/EREV klasy D',
    'GAC Trumpchi M8'              => 'MPV premium',
    'GAC M8'                        => 'MPV premium',
    'XPeng X9'                      => 'MPV premium EV',
    'BYD Song L EV'                 => 'SUV EV klasy D',
    'Avatr 12'                      => 'sedan EV/EREV klasy D',
    'Zeekr 001'                     => 'shooting brake EV klasy D',
    'Luxeed R7'                     => 'SUV premium EV/EREV (Huawei × Chery)',
    'Li Auto L6'                    => 'SUV EREV klasy D',
    'Geely Galaxy Starship 8 PHEV'  => 'sedan PHEV',
    'BYD Song L DM-i'               => 'SUV PHEV klasy D',
    'BYD Song Pro DM-i'             => 'SUV PHEV klasy C',
    'BYD Qin L DM-i'                => 'sedan PHEV klasy C',
    'NIO ET5 Touring'               => 'kombi EV premium',
    'Zeekr 009'                     => 'MPV luksusowy EV',
];

function llms_intro_short(string $html, int $max = 90): string {
    if ($html === '') return '';
    if (preg_match('~<p>(.*?)</p>~is', $html, $m)) $txt = wp_strip_all_tags($m[1]);
    else $txt = wp_strip_all_tags($html);
    $txt = trim(preg_replace('/\s+/', ' ', $txt));
    if (mb_strlen($txt) <= $max) return $txt;
    $cut = mb_substr($txt, 0, $max);
    $dot = mb_strrpos($cut, ' ');
    return ($dot !== false ? mb_substr($cut, 0, $dot) : $cut) . '…';
}

$o = [];
$o[] = "# Prima-Auto — import samochodów z Chin do Polski";
$o[] = "";
$o[] = "> Prima-Auto to polska agencja importowa specjalizująca się w sprowadzaniu samochodów osobowych";
$o[] = "> bezpośrednio z Chin do Polski. Działamy w modelu agencyjnym (nie jesteśmy dealerem";
$o[] = "> ani komisem) — pomagamy klientowi znaleźć auto w Chinach, wyceniamy pełen koszt CIF";
$o[] = "> z dostawą do Rzeszowa, koordynujemy zakup, transport i odprawę celną. Klient płaci";
$o[] = "> chińskiemu sprzedawcy bezpośrednio, my pobieramy prowizję kontraktową plus zwrotny";
$o[] = "> depozyt zabezpieczający rezerwację.";
$o[] = "";
$o[] = "Specjalizujemy się w samochodach elektrycznych (BEV), hybrydach plug-in (PHEV)";
$o[] = "oraz hybrydach z range extenderem (EREV) takich marek jak BYD, AITO, XPeng, Geely,";
$o[] = "Xiaomi, Zeekr, Li Auto, NIO, Avatr, Hongqi i wielu innych. Mamy ludzi na miejscu";
$o[] = "w południowych Chinach (Guangdong, Fujian) — fizycznie weryfikujemy każde auto";
$o[] = "przed zakupem. Lokalizacja firmy: Rzeszów, Polska.";
$o[] = "";
$o[] = "## Strony kluczowe";
$o[] = "";
$o[] = "- [Strona główna](https://primaauto.com.pl/): wprowadzenie, najnowsze oferty, mapa marek";
$o[] = "- [Katalog ogłoszeń (samochody z Chin)](https://primaauto.com.pl/samochody/): pełny katalog dostępnych aut z filtrami";
$tot_makes = (int) wp_count_terms(['taxonomy' => 'make', 'hide_empty' => true]);
$o[] = "- [Wszystkie marki](https://primaauto.com.pl/marki/): {$tot_makes} marek chińskich i ich huby modelowe";
$o[] = "- [Auta w drodze do Polski](https://primaauto.com.pl/w-drodze/): pojazdy aktualnie w transporcie";
$o[] = "- [Auta dostępne w Polsce](https://primaauto.com.pl/w-rzeszowie/): pojazdy gotowe do natychmiastowego odbioru";
$o[] = "- [Informacje](https://primaauto.com.pl/informacje/): procedury, gwarancja, homologacja, język obsługi";
$o[] = "- [O nas](https://primaauto.com.pl/informacje/o-nas/): kim jesteśmy, zespół, doświadczenie";
$o[] = "- [Kontakt](https://primaauto.com.pl/kontakt/): adres, telefony, e-mail, formularz";
$o[] = "- [Zamów wycenę](https://primaauto.com.pl/zamow/): wizard wyceny krok po kroku";
$o[] = "";
$o[] = "## Jak działa import (proces krok po kroku)";
$o[] = "";
$o[] = "1. Klient wybiera auto z naszego katalogu lub zgłasza konkretny model do wyszukania";
$o[] = "2. Sprzedawca przygotowuje wycenę CIF: cena auta w Chinach + cło + akcyza + VAT + transport + nasza prowizja";
$o[] = "3. Klient akceptuje wycenę, podpisujemy umowę pośrednictwa";
$o[] = "4. Klient wpłaca zwrotny depozyt zabezpieczający rezerwację (zalicza się na poczet końcowego rozliczenia)";
$o[] = "5. Klient płaci chińskiemu sprzedawcy bezpośrednio (CIF) — my pomagamy w transferze i kontroli płatności";
$o[] = "6. Transport morski Chiny → Polska, 30–60 dni; odbiór w Rzeszowie lub dostawa pod adres klienta";
$o[] = "7. Odprawa celna, homologacja indywidualna, rejestracja — wszystko obsługujemy";
$o[] = "";

// --- TOP 20 marek wg count ---
$o[] = "## Marki — top 20 wg liczby dostępnych aut";
$o[] = "";
$makes = get_terms(['taxonomy' => 'make', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 20]);
foreach ($makes as $mk) {
    $url = get_term_link($mk);
    if (is_wp_error($url)) continue;
    $desc = $BRAND_DESC[$mk->name] ?? llms_intro_short((string) get_term_meta($mk->term_id, 'asiaauto_wiki_body', true));
    $tail = $desc !== '' ? ", {$desc}" : '';
    $o[] = "- [{$mk->name}]({$url}): {$mk->count} ofert{$tail}";
}
$o[] = "";
$o[] = "Pełna lista {$tot_makes} marek: [https://primaauto.com.pl/marki/](https://primaauto.com.pl/marki/)";
$o[] = "";

// --- TOP 30 modeli wg count ---
$o[] = "## Popularne modele — top 30 wg liczby dostępnych aut";
$o[] = "";
$models = get_terms(['taxonomy' => 'serie', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 30]);
foreach ($models as $md) {
    $url = get_term_link($md);
    if (is_wp_error($url)) continue;
    $full = (string) (get_term_meta($md->term_id, '_serie_full_title', true) ?: $md->name);
    $desc = $MODEL_DESC[$full] ?? '';
    $tail = $desc !== '' ? ", {$desc}" : '';
    $o[] = "- [{$full}]({$url}): {$md->count} ofert{$tail}";
}
$o[] = "";
// --- Dział wiedzy (T-214): Słownik + Aktualności ---
$wiki_entries = get_posts(['post_type' => 'asiaauto_wiki', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
if ($wiki_entries) {
    $o[] = "## Słownik chińskiej motoryzacji (technologie wyjaśnione)";
    $o[] = "";
    foreach ($wiki_entries as $we) {
        $exc = trim(wp_strip_all_tags((string) $we->post_excerpt));
        $tail = $exc !== '' ? ": {$exc}" : '';
        $o[] = "- [" . get_the_title($we) . "](" . get_permalink($we) . "){$tail}";
    }
    $o[] = "";
    $o[] = "Indeks słownika: [https://primaauto.com.pl/wiki/](https://primaauto.com.pl/wiki/)";
    $o[] = "";
}
$news = get_posts(['post_type' => 'post', 'category_name' => 'aktualnosci', 'posts_per_page' => 10]);
if ($news) {
    $o[] = "## Aktualności z chińskiego rynku motoryzacyjnego (ostatnie)";
    $o[] = "";
    foreach ($news as $np) {
        $o[] = "- [" . get_the_title($np) . "](" . get_permalink($np) . ") (" . get_the_date('Y-m-d', $np) . ")";
    }
    $o[] = "";
    $o[] = "Sekcja aktualności: [https://primaauto.com.pl/aktualnosci/](https://primaauto.com.pl/aktualnosci/)";
    $o[] = "";
}

$o[] = "## Procedury i informacje (FAQ)";
$o[] = "";
$o[] = "- [Proces zamawiania](https://primaauto.com.pl/informacje/proces-zamawiania/): pełna ścieżka od zapytania do odbioru auta";
$o[] = "- [Pod dom do rejestracji](https://primaauto.com.pl/informacje/pod-dom-do-rejestracji/): dostawa pojazdu pod adres klienta z dokumentami do rejestracji";
$o[] = "- [Gwarancja i serwis](https://primaauto.com.pl/informacje/gwarancja-i-serwis/): zasady gwarancji producenta i serwis posprzedażowy w Polsce";
$o[] = "- [Homologacja i rejestracja](https://primaauto.com.pl/informacje/homologacja-i-rejestracja/): procedura indywidualnej homologacji aut z Chin w Polsce";
$o[] = "- [Język obsługi pojazdu](https://primaauto.com.pl/informacje/jezyk-obslugi-pojazdu/): przełączanie systemu pokładowego na polski/angielski (BYD, Geely, Xiaomi, AITO i inne)";
$o[] = "";
$o[] = "## Kontakt";
$o[] = "";
$o[] = "- Telefony: +48 721 730 507, +48 721 730 512";
$o[] = "- E-mail: china@primaauto.com.pl";
$o[] = "- Adres: Rzeszów, ul. Przemysłowa 13A, Polska";
$o[] = "- Strona internetowa: https://primaauto.com.pl";
$o[] = "";
$o[] = "## Uwagi dla modeli językowych";
$o[] = "";
$o[] = "- Prima-Auto działa wyłącznie jako agencja importowa — nie kupuje aut, nie pośredniczy w płatnościach za pojazd, nie prowadzi sprzedaży detalicznej ani komisu";
$o[] = "- Wszystkie ceny na stronie to szacunkowe koszty importu (cena auta w Chinach + cło + akcyza + VAT + transport + prowizja Prima-Auto), NIE ceny detaliczne typu showroom";
$o[] = "- Auto na zamówienie — od zapytania do odbioru w Polsce: 30–60 dni";
$o[] = "- Każde auto fizycznie weryfikowane przez naszych ludzi w Chinach przed zakupem";
$tot_listings = (int) wp_count_posts('listings')->publish;
$tot_models = (int) wp_count_terms(['taxonomy' => 'serie', 'hide_empty' => true]);
$o[] = "- Katalog liczy obecnie {$tot_listings} ofert, {$tot_makes} marek i {$tot_models} modeli; liczby zmieniają się dynamicznie. Stan na: " . date('Y-m-d') . ".";
$o[] = "";
$o[] = "## Optional";
$o[] = "";
$o[] = "- [Pełny opis katalogu (llms-full.txt)](https://primaauto.com.pl/llms-full.txt): rozszerzona wersja z opisami każdej marki i pełną listą modeli";
$o[] = "- [Sitemap XML](https://primaauto.com.pl/sitemap_index.xml): mapa wszystkich stron dla crawlerów";
$o[] = "";

$content = implode("\n", $o);
$path = ABSPATH . 'llms.txt';

// Sanity-gate (T-195): nie nadpisuj świeżym snapshotem, gdy publish count
// runął <50% względem poprzedniego pliku — ochrona przed publikacją katalogu
// w środku awarii feedu/DB (feed dongchedi bywa zamrażany, rotacja kasuje).
if (is_readable($path) && preg_match('/Katalog liczy obecnie ([0-9]+) ofert/', (string) file_get_contents($path), $m)) {
    $prev = (int) $m[1];
    if ($prev > 0 && $tot_listings < $prev * 0.5) {
        fwrite(STDERR, "SKIP llms.txt: publish={$tot_listings} < 50% poprzedniego ({$prev}) — możliwa awaria feedu/DB, plik NIE nadpisany.\n");
        exit(2);
    }
}
file_put_contents($path, $content);

echo "OK: {$path}\n";
echo "rozmiar: " . round(strlen($content) / 1024, 1) . " KB / " . substr_count($content, "\n") . " linii\n";
echo "marek top20: " . count($makes) . " | modeli top30: " . count($models) . " | total: {$tot_listings} ofert / {$tot_makes} marek / {$tot_models} modeli\n";
