<?php
/**
 * Poprawka akapitów o wyposażeniu w wiki hubów ROX 01 i Toyota KEDE Shanchuan —
 * pisane były na uciętym extra_prep z che168 (112 i 62 pola), a po scaleniu
 * z bliźniakiem dongchedi mamy 386 i 288 pól, w tym pełne grupy wyposażenia.
 * Uruchomienie: wp eval-file <plik>
 */

$patches = [
    // ── ROX 01 (term 7199) ──────────────────────────────────────────────
    7199 => [
        'from' => 'Standardowe wyposażenie tego egzemplarza obejmuje między innymi podgrzewaną kierownicę, cyfrowe zegary, wideorejestrator, aktualizacje OTA, filtr PM2.5, oczyszczacz powietrza, gniazdo zasilania oraz interfejsy do fotelików dziecięcych. Do systemów wsparcia kierowcy należą aktywne hamowanie, asystent utrzymania pasa, ostrzeganie o zmianie pasa, automatyczne parkowanie i auto hold.',
        'to'   => 'Wyposażenie standardowe tego egzemplarza to 71 pozycji. Drugi rząd ma niezależne fotele z funkcją boss (regulacja fotela pasażera z tyłu), własny ekran i panel dotykowy, niezależną klimatyzację, nawiewy i uchwyty na kubki. Do tego panoramiczny dach z roletą, nagłośnienie z 14 głośnikami i karaoke, ładowanie indukcyjne telefonu, gniazdo 230 V, jonizator, oczyszczacz powietrza z filtrem PM2.5, podgrzewana kierownica, zdalny rozruch i aktualizacje OTA. Zestaw systemów wsparcia kierowcy jest szeroki: aktywne hamowanie, utrzymanie i centrowanie na pasie, ostrzeganie o opuszczeniu pasa, automatyczna zmiana pasa, rozpoznawanie znaków drogowych, automatyczne parkowanie z funkcją wjazdu do garażu, zdalne manewrowanie autem, auto hold, ESP, EBD, wspomaganie hamowania, kurtyny powietrzne i połączenie alarmowe eCall.',
    ],
    // ── Toyota KEDE Shanchuan (term 7201) ───────────────────────────────
    7201 => [
        'from' => 'Wnętrze jest zaaranżowane pod przewożenie pasażerów, nie bagaży. Siedem miejsc obejmuje pojedyncze fotele kapitańskie w drugim rzędzie, z rozbudowaną regulacją i konsolą między siedzeniami. Sufit mieści rozkładany ekran multimedialny i podświetlenie dekoracyjne, okna wyposażono w zasłony, jest osobna klimatyzacja z wylotami dla tylnych rzędów, elektryczna klapa bagażnika, gniazdo zasilania, oczyszczacz powietrza i interfejsy do fotelików dziecięcych. Zdjęcia w ogłoszeniu pokazują skórzaną tapicerkę w kolorze karmelowym z czarnym podsufitem i wykończeniami w połysku.',
        'to'   => 'Wnętrze jest zaaranżowane pod przewożenie pasażerów, nie bagaży. Siedem miejsc obejmuje pojedyncze fotele kapitańskie w drugim rzędzie z funkcją boss, elektrycznym składaniem i rozkładanym stolikiem. Pasażerowie z tyłu mają własny ekran z panelem dotykowym, niezależną klimatyzację z nawiewami, uchwyty na kubki, gniazdo 230 V, ładowanie indukcyjne i lodówkę samochodową. Do tego elektryczna klapa bagażnika, gniazdo 12 V w przestrzeni bagażowej, oczyszczacz i odświeżacz powietrza, przyciemniane szyby oraz interfejsy ISOFIX. W karcie wyposażenia producenta zabudowy wskazane jest także zawieszenie pneumatyczne — element typowy dla przebudów KEDE, warto potwierdzić przy oględzinach konkretnego egzemplarza. Zdjęcia w ogłoszeniu pokazują skórzaną tapicerkę w kolorze karmelowym z czarnym podsufitem, podświetleniem dekoracyjnym i wykończeniami w wysokim połysku.',
    ],
];

foreach ($patches as $tid => $p) {
    $wiki = (string) get_term_meta($tid, 'asiaauto_wiki_body', true);
    if ($wiki === '') { printf("!! #%d: brak wiki\n", $tid); continue; }
    if (mb_strpos($wiki, $p['from']) === false) { printf("!! #%d: nie znalazłem akapitu do podmiany — STOP\n", $tid); continue; }
    $new = str_replace($p['from'], $p['to'], $wiki);
    if (!preg_match('/[ąćęłńóśźż]/u', $new)) { printf("!! #%d: brak diakrytyk — STOP\n", $tid); continue; }
    if (mb_strpos($new, '{{LISTINGS_BAR}}') === false) { printf("!! #%d: zgubiony token — STOP\n", $tid); continue; }
    update_term_meta($tid, 'asiaauto_wiki_body', wp_kses_post($new));
    clean_term_cache([$tid], 'serie');
    printf("OK #%d %s: wiki %d -> %d zn.\n", $tid, get_term($tid, 'serie')->name, mb_strlen($wiki), mb_strlen($new));
}
