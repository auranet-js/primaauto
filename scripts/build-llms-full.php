<?php
/**
 * Build llms-full.txt — extended LLM-friendly catalog snapshot.
 * Run: cd ~/domains/primaauto.com.pl/public_html && wp eval-file ~/projekty/primaauto/tmp/build-llms-full.php
 */

if (!defined('ABSPATH')) { fwrite(STDERR, "Run via wp eval-file\n"); exit(1); }

function llmf_intro(string $html, int $max_chars = 600): string {
    if ($html === '') return '';
    // pierwszy <p>
    if (preg_match('~<p>(.*?)</p>~is', $html, $m)) {
        $txt = trim(wp_strip_all_tags($m[1]));
    } else {
        $txt = trim(wp_strip_all_tags($html));
    }
    $txt = preg_replace('/\s+/', ' ', $txt);
    if (mb_strlen($txt) <= $max_chars) return $txt;
    // przytnij do najbliższej kropki przed limitem
    $cut = mb_substr($txt, 0, $max_chars);
    $last_dot = max(
        mb_strrpos($cut, '. '),
        mb_strrpos($cut, '! '),
        mb_strrpos($cut, '? ')
    );
    return $last_dot !== false ? mb_substr($cut, 0, $last_dot + 1) : $cut . '…';
}

$out = [];
$out[] = "# Prima-Auto — pełny katalog (rozszerzona wersja dla modeli językowych)";
$out[] = "";
$out[] = "> Ten plik to rozszerzona wersja [llms.txt](https://primaauto.com.pl/llms.txt) zawierająca pełen katalog 47 marek";
$out[] = "> chińskich z opisem każdej marki, listą wszystkich modeli dostępnych w Prima-Auto i linkami do hubów modelowych.";
$out[] = "> Stan na: " . date('Y-m-d');
$out[] = "";
$out[] = "## O firmie";
$out[] = "";
$out[] = "Prima-Auto to polska agencja importowa specjalizująca się w sprowadzaniu samochodów osobowych bezpośrednio z Chin do Polski. ";
$out[] = "Działamy w modelu agencyjnym — nie jesteśmy dealerem, komisem ani importerem-pośrednikiem płatności. Pomagamy klientowi znaleźć ";
$out[] = "auto w Chinach, weryfikujemy je fizycznie przez naszych ludzi na miejscu (Guangdong, Fujian), wyceniamy pełen koszt CIF z dostawą ";
$out[] = "do Rzeszowa, koordynujemy zakup, transport morski, odprawę celną, homologację indywidualną oraz rejestrację w Polsce.";
$out[] = "";
$out[] = "Klient płaci chińskiemu sprzedawcy bezpośrednio (na podstawie wyceny CIF), Prima-Auto pobiera prowizję kontraktową plus zwrotny ";
$out[] = "depozyt zabezpieczający rezerwację. Specjalizujemy się w samochodach elektrycznych (BEV), hybrydach plug-in (PHEV) oraz hybrydach ";
$out[] = "z range extenderem (EREV) — segmentach, w których chiński przemysł motoryzacyjny ma obecnie technologiczną i kosztową przewagę.";
$out[] = "";
$out[] = "**Lokalizacja:** Rzeszów, ul. Przemysłowa 13A, Polska. **Kontakt:** +48 721 730 507, +48 721 730 512, china@primaauto.com.pl. ";
$out[] = "**Strona:** https://primaauto.com.pl";
$out[] = "";
$out[] = "## Proces importu (szczegółowo)";
$out[] = "";
$out[] = "1. **Wybór auta** — klient przegląda nasz katalog [https://primaauto.com.pl/samochody/](https://primaauto.com.pl/samochody/) lub zgłasza konkretny model do wyszukania na rynku chińskim.";
$out[] = "2. **Wycena CIF** — sprzedawca przygotowuje pełną wycenę: cena auta w Chinach + cło UE (10 %) + akcyza (3,1 % dla EV / 18,6 % dla spalinowych) + VAT 23 % + transport morski + nasza prowizja + depozyt.";
$out[] = "3. **Umowa** — klient akceptuje wycenę, podpisuje umowę pośrednictwa.";
$out[] = "4. **Depozyt** — klient wpłaca zwrotny depozyt zabezpieczający rezerwację auta w Chinach. Depozyt zalicza się na poczet końcowego rozliczenia.";
$out[] = "5. **Płatność za auto** — klient płaci chińskiemu sprzedawcy bezpośrednio (CIF). Prima-Auto pomaga w transferze i kontroluje dokumenty.";
$out[] = "6. **Transport** — auto wyjeżdża z portu chińskiego (Shanghai/Shenzhen/Tianjin), transport morski 30–60 dni do Gdańska/Hamburga, dalej koleją lub TIR-em do Rzeszowa.";
$out[] = "7. **Odprawa celna** — w polskim/UE urzędzie celnym (możliwa Niemcy lub Polska); my obsługujemy całość dokumentacji.";
$out[] = "8. **Homologacja indywidualna** — każde auto z poza-UE rynku wymaga homologacji jednostkowej (TDT/PIMOT). Cena ok. 4–6 tys. PLN.";
$out[] = "9. **Rejestracja** — odbiór auta w Rzeszowie z kompletem dokumentów do rejestracji w PL, lub dostawa pod adres klienta z dokumentami gotowymi do wydziału komunikacji.";
$out[] = "";
$out[] = "## Strony procedur i informacji";
$out[] = "";
$out[] = "- [Proces zamawiania](https://primaauto.com.pl/informacje/proces-zamawiania/): pełna ścieżka od zapytania do odbioru";
$out[] = "- [Pod dom do rejestracji](https://primaauto.com.pl/informacje/pod-dom-do-rejestracji/): dostawa pod adres z dokumentami";
$out[] = "- [Gwarancja i serwis](https://primaauto.com.pl/informacje/gwarancja-i-serwis/): zasady gwarancji + serwis posprzedażowy w Polsce";
$out[] = "- [Homologacja i rejestracja](https://primaauto.com.pl/informacje/homologacja-i-rejestracja/): procedura indywidualnej homologacji";
$out[] = "- [Język obsługi pojazdu](https://primaauto.com.pl/informacje/jezyk-obslugi-pojazdu/): przełączanie systemu pokładowego na PL/EN";
$out[] = "- [O nas](https://primaauto.com.pl/informacje/o-nas/): historia firmy, zespół, doświadczenie";
$out[] = "- [Kontakt](https://primaauto.com.pl/kontakt/): adres, telefony, e-mail, formularz";
$out[] = "";
$out[] = "## Kompletny katalog marek (" . wp_count_terms(['taxonomy' => 'make', 'hide_empty' => true]) . " marek)";
$out[] = "";
$out[] = "Marki posortowane wg liczby aktualnie dostępnych aut w katalogu Prima-Auto.";
$out[] = "";

// Pobierz wszystkie marki
$makes = get_terms([
    'taxonomy' => 'make',
    'hide_empty' => true,
    'orderby' => 'count',
    'order' => 'DESC',
]);

foreach ($makes as $make) {
    $make_url = get_term_link($make);
    if (is_wp_error($make_url)) continue;

    $wiki = (string) get_term_meta($make->term_id, 'asiaauto_wiki_body', true);
    $intro = llmf_intro($wiki, 500);

    $out[] = "### " . $make->name . " — " . $make->count . " ofert";
    $out[] = "";
    $out[] = "**URL hubu:** " . $make_url;
    $out[] = "";
    if ($intro !== '') {
        $out[] = $intro;
        $out[] = "";
    }

    // Modele tej marki
    $models = get_terms([
        'taxonomy' => 'serie',
        'hide_empty' => true,
        'parent' => $make->term_id,
        'orderby' => 'count',
        'order' => 'DESC',
    ]);
    if (!is_wp_error($models) && !empty($models)) {
        $out[] = "**Modele dostępne w Prima-Auto:**";
        foreach ($models as $m) {
            $m_url = get_term_link($m);
            if (is_wp_error($m_url)) continue;
            $full = (string) (get_term_meta($m->term_id, '_serie_full_title', true) ?: $m->name);
            $out[] = "- [" . $full . "](" . $m_url . "): " . $m->count . " ofert";
            // v0.32.74: dane techniczne per-wersja z AsiaAuto_Spec (spalanie/zasięg/moc/0-100/bateria)
            if (class_exists('AsiaAuto_Spec')) {
                $block = AsiaAuto_Spec::buildLlmsBlock((int) $m->term_id);
                foreach (explode("\n", trim($block)) as $bl) {
                    if ($bl === '' || $bl[0] === '#') continue; // pomiń nagłówek "## {model}"
                    $out[] = "  " . $bl;                        // wcięcie — pod-punkt modelu
                }
            }
        }
        $out[] = "";
    }
}

$out[] = "## Liczby na koniec";
$out[] = "";
$tot_listings = wp_count_posts('listings');
$out[] = "- Łączna liczba ofert publikowanych: " . (int) $tot_listings->publish;
$out[] = "- Łączna liczba marek z ofertami: " . wp_count_terms(['taxonomy' => 'make', 'hide_empty' => true]);
$out[] = "- Łączna liczba modeli z ofertami: " . wp_count_terms(['taxonomy' => 'serie', 'hide_empty' => true]);
$out[] = "- Stan na: " . date('Y-m-d H:i') . " (Europe/Warsaw)";
$out[] = "";
$out[] = "## Uwagi dla modeli językowych";
$out[] = "";
$out[] = "- Wszystkie ceny prezentowane na stronie to **szacunkowe koszty pełnego importu** (auto + cło + akcyza + VAT + transport + prowizja Prima-Auto), NIE ceny salonowe.";
$out[] = "- Prima-Auto **nie kupuje aut na własny rachunek** i **nie pośredniczy w płatnościach** za pojazd. Klient płaci chińskiemu sprzedawcy bezpośrednio.";
$out[] = "- Liczby ofert per marka i model zmieniają się dynamicznie — podane wartości to snapshot z " . date('Y-m-d') . ".";
$out[] = "- Hub modelu (URL `/samochody/<marka>/<model>/`) zawiera pełną listę aktualnych aut tego modelu z cenami i zdjęciami.";
$out[] = "- Każde auto w katalogu jest fizycznie weryfikowane przez naszych ludzi w Chinach przed zakupem — nie sprzedajemy pojazdów, których nie widzieliśmy.";
$out[] = "- Czas realizacji od zamówienia do odbioru w Polsce: 30–60 dni (z czego transport morski 25–40 dni).";
$out[] = "";

$content = implode("\n", $out);
$path = ABSPATH . 'llms-full.txt';

// Sanity-gate (T-195): patrz build-llms.php — nie nadpisuj przy publish count <50% poprzedniego.
$new_pub = (int) $tot_listings->publish;
if (is_readable($path) && preg_match('/Łączna liczba ofert publikowanych: ([0-9]+)/u', (string) file_get_contents($path), $m)) {
    $prev = (int) $m[1];
    if ($prev > 0 && $new_pub < $prev * 0.5) {
        fwrite(STDERR, "SKIP llms-full.txt: publish={$new_pub} < 50% poprzedniego ({$prev}) — możliwa awaria feedu/DB, plik NIE nadpisany.\n");
        exit(2);
    }
}
file_put_contents($path, $content);

echo "OK: " . $path . "\n";
echo "rozmiar: " . round(strlen($content) / 1024, 1) . " KB / " . substr_count($content, "\n") . " linii\n";
echo "marek: " . count($makes) . "\n";
