# Historia wersji asiaauto-sync


## 0.38.0 — 2026-09-03 (telefon: arkusz filtra i wyposażenie)

Pytanie Janka: **„wybrałem kombi i SUV — i co teraz z tym oknem?"**. Odpowiedź brzmiała: nic tego
nie mówi. Arkusz od dołu był samą listą — bez tytułu, bez przycisku, bez widocznego licznika.
Wyjścia, jakie istniały: tap w pasek zasłony nad arkuszem (lista zajmowała 78vh, zostawało ~180 px),
`Escape` (klawiatury na telefonie nie ma) i gest wstecz na Androidzie, który **wychodził z całej
strony** — otwarcie arkusza nie robiło `pushState`, a `popstate` wołał `location.reload()`.
Kreska u góry sugerowała zsuwanie, którego nie było.

Pomiar rozstrzygnął, że to dwa różne problemy, więc dostały dwie różne formy.

**Listy rozwijane — arkusz z nagłówkiem i stopką.** 11 z 13 list ma tyle pozycji, że mieści się
bez przewijania (arkusz 78vh = 658 px, minus nagłówek i stopka, daje 11 wierszy po 48 px); pełny
ekran dałby 13, czyli dwie więcej — za mało, by uzasadnić przebudowę. Doszły: tytuł filtra,
licznik zaznaczonych, „×" 44 × 44 i stopka **„Pokaż N ofert"**, gdzie liczba pochodzi z tego samego
`search-counts`, który odświeża pasek narzędzi — zero nowych zapytań. Listy dłuższe niż 11 pozycji
(marka 58, model do 44 po marce) dostają pełną wysokość ekranu. Zsuwanie palcem w dół zamyka,
ale tylko od góry listy, żeby nie kolidowało z przewijaniem opcji.

**Wyposażenie — ten sam arkusz co marka i model.** Sekcja po rozwinięciu miała **1 535 px, czyli
1,8 ekranu**: 36 pastylek w 29 rzędach, płaską listą, bez szukajki. Teraz na telefonie wyposażenie
jest **zwykłym polem filtra** — „Wyposażenie" z przyciskiem, który otwiera ten sam arkusz od dołu
co marka czy model, z tą samą obsługą: nagłówek, szukajka (36 pozycji > 11, więc pełna wysokość),
wiersze z checkboxem i liczbą ofert, stopka „Pokaż N ofert". Podsumowanie w polu działa jak
w enumach („Lidar +1").

Droga do tego była okrężna i warto to zapisać. Pierwsze podejście dało pełnoekranowy panel
z podziałem na cztery grupy z `FLAG_GROUPS` — ładny, ale **inny niż wszystkie pozostałe filtry**,
a przycisk otwierający siedział wewnątrz sekcji, więc przy zwijanych sekcjach kosztował dwa
tapnięcia zamiast jednego. Janek uciął to jednym zdaniem: „lista wyposażenia ma być taka jak lista
modeli czy marek". Spójność interfejsu wygrywa z pomysłowością — pastylki i grupy zostają tam,
gdzie mają sens, czyli na komputerze.

**Desktop nietknięty.** Z tego samego HTML-a powstają płaskie pastylki w 7 rzędach: arkusz
rozpuszcza się przez `display: contents`, a jego nagłówek, szukajka i stopka są ukryte.

**Historia przeglądarki.** Otwarcie arkusza lub panelu dokłada wpis (`pushState`), `popstate`
zamyka go zamiast przeładowywać stronę, a po zamknięciu adres wraca z filtrami zaznaczonymi
w środku (`przywrocUrl()`). Zamknięcie działa natychmiast, wpis z historii zdejmowany jest w tle —
`history.back()` jest asynchroniczne i arkusz zostawałby widoczny przez jeden tick (na tym
oblewał się test `Escape` na 320 px).

Trzy pułapki warte zapamiętania. Reset `.aas button` (0-1-1) bije klasę komponentu — przycisk
stopki wyszedł bez tła, a „×" bez `margin-left: auto`; komentarz w pliku wprost każe dawać prefiks
`.aas `. Klik w pastylkę zamykał panel, bo łapała go reguła „klik poza listą zamyka". Panel łamał
się na dwie kolumny, bo dziedziczył `flex-wrap: wrap` z bazowej reguły `.aas__chips` — połowa listy
wychodziła poza ekran w prawo. Na komputerze arkusz rozpuszczany
przez `display: contents` i tak zostawał niewidoczny, bo `.aas [hidden] { display: none !important }`
bije zwykłą regułę — potrzebny był `!important`; a `.aas__opts` ma `flex-direction: column`, więc
bez jawnego `row` pastylki ustawiały się jedna pod drugą na całą szerokość. Żadnej z nich nie złapałyby liczby: wszystkie trzy widać dopiero
na zrzucie ekranu.

**Wersjonowanie assetów po `filemtime`.** Arkusz i skrypt wyszukiwarki wisiały na stałej
`ASIAAUTO_VERSION`, a jedna wersja objęła kilka poprawek pod rząd — Janek zobaczył wtedy arkusz
wyposażenia **bez pozycji**, bo jego przeglądarka trzymała CSS z etapu pośredniego, w którym
pastylki miały `display: none`. Teraz `?ver` bierze się z czasu modyfikacji pliku (wzorzec
z `asiaauto-tracking`), więc każda zmiana unieważnia cache sama.

Bramki: axe 320 / 390 / 1366 px, także z otwartym arkuszem i otwartym panelem — **0 naruszeń**;
`test-ui-wyszukiwarka.mjs` bez błędów na obu szerokościach; `porownaj-search.php` 50 kombinacji,
**0 rozjazdów**, średnio 3 ms. Prompt i pomiary: `docs/roadmapa/T-252-wyszukiwarka-na-telefonie.md`.


## 0.37.15 — 2026-09-03 (marki i modele alfabetycznie)

Pytanie Janka: „lista marek i modeli niech będzie alfabetyczna, ma to sens?". Ma — i tylko dla
tych dwóch pól. To jedyne listy, w których użytkownik przychodzi ze **znaną nazwą** i skanuje
wzrokiem (58 marek, 2,6 tys. modeli); kolejność po liczbie ofert zmusza go wtedy do czytania
wszystkiego. W krótkich listach (paliwo, nadwozie, kolor, tapicerka) nazw się nie szuka, więc tam
licznik dalej rządzi kolejnością — mówi, gdzie w ogóle jest oferta. Rocznik malejąco, miejsca
rosnąco, bez zmian.

Zrobione w PHP (`renderEnum`) i w JS. **Pułapka:** modele dorysowywane po wyborze marki wchodzą
na koniec listy, więc przy sortowaniu alfabetycznym trzeba przełożyć CAŁĄ listę, nie tylko
doklejone — inaczej „Atto 2" ląduje pod „Song L". Do tego `setlocale(LC_COLLATE, 'pl_PL')`
w `parseParams()`, żeby `strcoll` układał polskie znaki, a nie bajty. Sprawdzone na żywo: BYD →
Atto 2, ATTO 3, BYD e7, Frigate 07, Han DM-i, Han EV… (kolejność zgodna z `localeCompare('pl')`).

## 0.37.14 — 2026-09-03 (telefon: pasek wyników nie udaje przycisku zamykającego)

Zgłoszenie Janka: „jawny przycisk do pokazywania wyników sugeruje, że zamyka się nim filtr,
podczas gdy on jest submitem pokazującym wyniki i w ten sposób opuszczam od razu szafę filtrów".
Przyklejony pasek na telefonie miał napis „Pokaż wyniki" — czytało się jak „zamknij i pokaż".

Teraz: **„Wyniki ↓"** ze strzałką (jawna nawigacja w dół, nie akcja) plus dopisek przy liczniku
**„— filtry zostają"**. Przycisk w sekcji „Oferty" też dostał strzałkę: „Pokaż N ofert ↓".
Zmierzone po zmianie: klik przewija z y=419 na y=1012, liczba otwartych sekcji filtrów bez zmian
(2 → 2), stan filtrów nietknięty.

**Przy okazji, poza wersją pluginu:** ręczny bieg `napraw-moc-ukladu.php apply` — **46 ofert**
PHEV/EREV/EV z dzisiejszego importu che168 dostało moc układu (nie czekały do crona 05:00).
Zostają **3 oferty bez mocy w danych**: Leapmotor D19 2026 EREV (pusty `extra_prep`) i dwa
BYD Han DM-i 2025 „1.5T Auto FWD 5 Seater Edition" (22 klucze, żaden mocowy). **Świadomie
nie wpisano wartości z bliźniaków** — moc trafia do umowy PDF (`class-asiaauto-contract.php:423`),
a dla D19 jedyny bliźniak w serii to wersja BEV (inna technologia). Do uzupełnienia przez Ruslana
w panelu (edytor listingu ma pole „Moc (KM)").

**Sprawdzone przy okazji (punkt „osiągalność haseł"):** wszystkie 112 haseł słownika jest
osiągalnych z indeksu `/wiki/` (porównanie listy z bazą — zero brakujących), sidebar bierze
10 haseł alfabetycznie bez filtrowania kategorii, więc niczego nie gubi.

## 0.37.13 + motyw 1.3.6 — 2026-09-03 (12 haseł słownika nie istniało na indeksie `/wiki/`)

Zgłoszenie Janka: „nie widzę tam pojęcia tylne koła skrętne". Sprawdzone: hasło było opublikowane
i dostępne pod własnym adresem, ale **na indeksie `/wiki/` go nie było**.

**Przyczyna.** `archive-asiaauto_wiki.php` grupuje hasła po `_wiki_category`, a potem iteruje po
`AsiaAuto_Wiki::CATEGORIES` — więc renderuje wyłącznie kategorie z tej listy. Lista miała sześć
pozycji (`bateria`, `naped`, `adas`, `audio`, `komfort`, `normy`), a w bazie żyły jeszcze dwie:
`podwozie` (9 haseł) i `nadwozie` (3). **Dwanaście haseł znikało bez śladu** — nie trafiały nawet
do grupy „Pozostałe", bo ta zbiera tylko hasła z PUSTĄ kategorią. Dotyczyło to również haseł sprzed
tej sesji: wszystkie zawieszenia (McPherson, wielowahaczowe, dwuwahaczowe, pięciowahaczowe,
zależne, regulowane), hamulce, EPB i nadwozie samonośne.

**Naprawa dwuwarstwowa:** (1) `CATEGORIES` dostaje `podwozie` → „Podwozie i zawieszenie"
i `nadwozie` → „Nadwozie" (przy okazji `komfort` z „Komfort i nadwozie" na „Komfort i wnętrze",
żeby nagłówki się nie dublowały); (2) szablon przypisuje hasło z kategorią **spoza listy** do
„Pozostałe" zamiast je gubić — kolejna nieznana kategoria już niczego nie ukryje.

Po zmianie indeks pokazuje **113 haseł w ośmiu grupach** (było 101 w sześciu).

## motyw 1.3.5 — 2026-09-03 (autolinki słownika były NIEWIDOCZNE na ofertach i hubach)

Zgłoszenie Janka przy przeglądzie makiety: „w stanie obecnym nie widzę wykropkowanych linii pod
linkami na stronie oferty". Zmierzone (computed style, Chrome):

| strona | przed | po |
|---|---|---|
| oferta | `text-decoration: none`, kolor = kolor tekstu → **link nierozróżnialny** | kropkowana `#A0AEC0` |
| hub modelu | `none`, kolor czerwony + własna kropkowana ramka | kropkowana `#A0AEC0` |
| hasło `/wiki/` | kropkowana (działało) | bez zmian |

**Przyczyna:** `.aa-autolink` w `base.css` ma specyficzność 0-1-0, a `.aa-single a`
(`asiaauto-single.css:10`, `text-decoration: none`) i `.aa-hub__body a` (`hub.css:77`) mają 0-1-1
i ładują się PÓŹNIEJ — wygrywały remis. Autolinker działał, linki były w HTML (8 na ofercie AITO M9,
w tym 6 w tabeli danych technicznych), ale wyglądały jak zwykły tekst. **To także naruszenie
WCAG 1.4.1** (link w treści nierozróżnialny inaczej niż przez kolor — a tu nie było nawet koloru),
którego audyt nie złapał, bo próbka nie zawierała oferty z autolinkiem.

Naprawa: blok selektorów kontekstowych (0-2-1) w `base.css` — `.aa-single`, `.aa-hub__body`,
`.aa-tech__value`, `.aa-desc-content`, `.pa-article__content`; bez `!important`, z wyzerowaniem
`border-bottom` po regule huba.

**To wyjaśnia pomiar GA4 z T-250:** 3 przejścia oferta → słownik na 50 295 odsłon (0,006 %) przy
40 przejściach z aktualności. Na ofertach nie było czego kliknąć. Wybór docelowego stylu (warianty
A–D, `docs/makiety/autolinki-warianty.html`) zostaje u Janka — ta zmiana tylko przywraca stan A tam,
gdzie go nie było.

## 0.37.12 — 2026-09-03 (T-250 krok 4: CTA pod hasłem → wyszukiwarka z filtrem)

Sekcja „W ofercie: N aut z technologią X" pod hasłem `/wiki/` prowadziła do całego `/samochody/`.
Teraz, gdy hasło ma odpowiednik w filtrach, CTA brzmi **„Wszystkie auta z tą funkcją →"** i wiedzie
do `/wyszukiwarka/` z zaznaczonym filtrem; pozostałe hasła zostają na katalogu.

Mapowanie liczone **raz przy budowie indeksu** (`AsiaAuto_Wiki_Cars::filtrDlaHasla()`, cron co 6 h),
nie przy renderze: klucz `_wiki_term_keys` szukany w `AsiaAuto_Specs_Table::FLAGS` (także po
prefiksie, np. `vice_screen_size_*`), plus jawne wyjątki — `skylight_type 全景` → `roof_panorama`,
`air_suspension` → `air_susp`, `variable_suspension` → `zawieszenie=adaptacyjne`, `seat_material` →
`tapicerka=<slug>`, `sound_brand` → `audio=<marka>`, `fuel_form` → `paliwo=erev|phev|electric|hybrid`.

**Pokrycie: 46 haseł z 104** ma filtr (43 po flagach i enumach + 3 hasła napędów). Sprawdzone na
żywo: `/wiki/tylna-os-skretna/` → `?wyposazenie=rear_steer` (192 oferty), `dach-panoramiczny` →
`roof_panorama` (1 606), `devialet` → `audio=devialet` (119), `erev` → `paliwo=erev`, `lidar` →
`wyposazenie=lidar`. Hasła bez odpowiednika (`materialy-tapicerki` — ogólne, bez jednej wartości;
zawieszenia konstrukcyjne; procesory) zostają na `/samochody/`.

Pułapka zaliczona: `match()` przepisywał wynik do nowej tablicy i gubił nowy klucz `filtr` —
indeks miał dane, a render pokazywał stary CTA.

## 0.37.11 — 2026-09-03 (iPhone: pola 16 px, bez autofokusu na dotyku)

Janek (iPhone 15 Pro): „wybieranie filtrów rozszerza ekran, przez co się rozjeżdża". Typowa
przyczyna na iOS Safari: fokus w polu z fontem < 16 px powiększa stronę i nie cofa powiększenia;
do tego arkusz listy sam ustawiał fokus w szukajce → klawiatura wypychała arkusz. Naprawa:
16 px na wszystkich polach i selektach w panelu przy ≤ 768 px, autofokus tylko na desktopie.
Do potwierdzenia zrzutem z telefonu Janka — headless Chrome tego nie odtwarza.

**Zgłoszone, nierozwiązane:** przyklejony pasek „N ofert · Pokaż wyniki" na telefonie sugeruje
zamknięcie panelu, a przewija do wyników (submit), przez co użytkownik wypada z filtrów.
Kierunek: pasek jako sam licznik albo jawna nawigacja „Wyniki ↓" — decyzja przy zrzucie.

## 0.37.10 + motyw 1.3.4 — 2026-09-03 (ruch D: wyszukiwarka podlinkowana, obok katalogu)

Decyzja Janka: współistnienie z `/samochody/`, `/w-rzeszowie/`, `/w-drodze/` (nic nie zastępujemy,
stare strony zostają ze swoim SEO), **bez przenoszenia filtrów** z katalogu do wyszukiwarki
(„ktoś może mieć inną intencję wyszukiwania"). Cztery miejsca, wszystkie na czysty `/wyszukiwarka/`:

| miejsce | tekst | gdzie w kodzie |
|---|---|---|
| strona główna, pod polem marka/model | „Szukaj po wyposażeniu →" | `class-asiaauto-homepage.php` `renderHero()` + 3 linie CSS inline |
| `/samochody/` pod filtrami (desktop: kolumna; telefon: szuflada nad „Pokaż N wyników") | „Wyszukiwarka zaawansowana →" | `class-asiaauto-inventory.php` — **pierwsza zmiana od 25.08, jedna linia**; styl w `asiaauto-inventory.css` |
| menu mobilne, ostatnia pozycja pod Kontaktem | „Wyszukiwarka zaawansowana" | `header.php` przez `items_wrap` (menu WP nietknięte, desktop bez zmian) |
| stopka, kolumna Nawigacja, po „Samochody z Chin" | „Wyszukiwarka zaawansowana" | `footer.php` (statyczna lista fallback — `menu-2` nie jest podpięte) |

Pomiar: strona główna i `/samochody/` mają po 3 linki do wyszukiwarki, każda inna podstrona 2
(menu + stopka). axe na stronie głównej, `/samochody/` i otwartym menu mobilnym — patrz log sesji.
Backupy `.bak-2026-09-03-link-wyszukiwarka` przy pięciu plikach. Krok 1 ruchu D (CTA pod hasłami
`/wiki/`) idzie w T-250.

## 0.37.9 — 2026-09-03 (szybkość tras REST: −40 zapytań na kartach, cache etykiet, pamięć w przeglądarce)

Pomiar (`wp eval`, czas samych metod, bez startu WP): `counts()` 33–74 ms / 18–34 zapytań,
`query()` 14 ms / **52 zapytania** — miniatury 24 kart ładowane po jednej (`get_post` + meta
załącznika). Sam start WordPressa na trasie REST to ~55 ms (tyle trwa odpowiedź z cache).
Kompresja i cache po stronie serwera już były w porządku: brotli na REST i assetach
(`search` 120 KB → 3,4 KB), `max-age` 7 dni na CSS/JS.

| zmiana | efekt |
|---|---|
| `_prime_post_caches()` na miniaturach strony przed `renderCard()` | `query()` 52 → **9 zapytań** |
| etykiety termów (`optionLabels`) w transiencie pod solą `asiaauto_search_` — zdejmowane przez `flushCache()` po imporcie | −8 `get_terms` na każde `counts()` |
| zakresy zależne: jedno zapytanie MIN/MAX dla kolumn bez własnego filtra + osobne tylko dla aktywnych | 12 → 1 + aktywne |
| pamięć odpowiedzi w JS (klucz = query string, 60 wpisów) | powrót do wcześniejszego stanu filtrów bez sieci |

Po zmianach: REST `search` zimny 93 ms, `search-counts` zimny 85 ms (było 155), z cache 58 ms.
`EXPLAIN` na typowym GROUP BY: `index_merge intersect(make,status)`, 398 wierszy — indeksy działają.

## 0.37.8 — 2026-09-03 (filtry zależne wg zasad UX + nagłówek „Wyposażenie i technologie")

Pytanie Janka: „czy wybór filtrów nie powinien wpływać na pozostałe (wyszarzać)?". Wcześniej: listy
ukrywały zera, pastylki i kafle szarzały, ale dało się je kliknąć w zero wyników, podpowiedzi w polach
liczbowych pokazywały zakres całej bazy. Teraz jedna reguła (wzorce Otomoto / mobile.de / Baymard):

1. **Listy do ~15 opcji**: opcja z zerem widoczna, szara, `disabled`, licznik 0. **Marka i model**:
   zero ukryte (długie listy). Liczniki liczone bez własnego filtra listy (OR w obrębie listy).
2. **Pastylki i kafle „Oferty"**: zero = szare i `disabled`.
3. **Pola liczbowe**: podpowiedź = zakres PO zawężeniu (`bounds` w `search-counts`, np. „Moc od 101"
   przy PHEV zamiast 95); wartość poza zakresem dostaje „Brak ofert w tym zakresie".
4. **Zaznaczone zostaje zaznaczone.** Przy 0 wyników pusty stan wypisuje aktywne filtry jako
   przyciski do zdjęcia jednym kliknięciem; te, których zdjęcie przywraca wyniki, są wyróżnione
   (serwer liczy `blokady` tylko przy zerze: COUNT bez każdego filtra z osobna, ≤ ~15 zapytań;
   heurystyka z liczników po stronie JS oznaczała wszystko — cofnięta).

Nagłówek sekcji: „Technologia i wyposażenie" → **„Wyposażenie i technologie"**.

## 0.37.7 — 2026-09-03 (telefon: zwijane sekcje, otwarta tylko „Nadwozie")

Decyzja Janka po pomiarze (filtry ~1 800 px nad wynikami na 390 px): sekcje zwijane na telefonie,
na start otwarta „Nadwozie" i sekcje z aktywnymi filtrami (SSR z deep-linku), „Oferty" zawsze
otwarte. Nagłówek sekcji to `<button aria-expanded aria-controls>` w `<h2>` (wzorzec disclosure);
zwinięta sekcja nosi odznakę z liczbą aktywnych filtrów, odświeżaną w JS przy każdej zmianie.
Desktop: klasa `is-zwinieta` ignorowana (CSS tylko w `@media ≤ 768 px`), JS przy przejściu na
szeroki ekran rozwija wszystko.

## 0.37.6 — 2026-09-03 (telefon: pastylki zawijane z „Więcej wyposażenia", przyklejony pasek wyników)

**Pomiar przed (390 px):** do paska wyników 1 566 px, do pierwszej karty 1 690 px (dwa ekrany
filtrów); 36 pastylek w JEDNYM przewijanym poziomo rzędzie o szerokości 7 124 px — nie do użycia
palcem. Strona 434 KB, 27 zasobów, `load` 383 ms (headless).

**Zmiany (tylko `@media ≤ 768 px`):**
- pastylki zawijają się w rzędy; bez rozwinięcia widać 8 pierwszych + zaznaczone, reszta po
  „Więcej wyposażenia (+28)" (przycisk w PHP, licznik z konfiguracji; desktop nietknięty);
- przyklejony do dołu pasek „N ofert · Pokaż wyniki", widoczny po przewinięciu > 120 px i tylko
  dopóki pasek wyników nie wjedzie w widok (IntersectionObserver + scroll); „Pokaż wyniki"
  przewija do wyników natychmiast (`behavior: instant`).

**Usterka złapana axe po rozwinięciu pastylek:** pastylka z licznikiem 0 miała `opacity: .5`
→ kontrast 2,79:1. Teraz szary tekst (4,84:1) i przerywana ramka; to samo dla pustych kafli
„Oferty". axe 0 naruszeń przy 390 i 1366 px z 9 pustymi pastylkami w widoku.

**PageSpeed Insights mobile (03.09):** wynik **68**, FCP 2,3 s, **LCP 7,7 s**, TBT 160 ms, CLS 0,
SI 4,7 s, waga 1 000 KiB; „nieużywany JS 228 KiB" = GTM + GA4 + Ads (tracking, nie wyszukiwarka).
**Baseline `/samochody/`: 70, LCP 7,8 s, FCP 2,3 s** — ten sam obraz, więc LCP jest problemem
motywu/karty, nie tej strony. Zauważone obok, nie ruszane: osobny temat wydajności całego serwisu.

**Kandydaci na kolejne pastylki** zapisani: `docs/roadmapa/T-116-kandydaci-pastylek.md` (129 pozycji,
wybór 03.09 oznaczony) + skrypt `scripts/kandydaci-pastylek.php`.

## 0.37.5 — 2026-09-03 (osobne moduły sekcji + 10 pastylek z wyboru Janka)

**0.37.4:** sekcje jako osobne białe moduły (odstęp 10 px, bez kresek); „Marka nagłośnienia" na
końcu wyposażenia, pod pastylkami.

**0.37.5:** pomiar kandydatów na pastylki — 117 cech z ≥ 50 wystąpieniami, po odsianiu tego, co już
jest, i śmieci (nazwy chipów, tryby jazdy, pojemności); lista wyświetlona Jankowi z numerami.
Wybór: 15, 16, 27, 62, 76, 79, 87, 96, 101, 108 → nowe flagi (SCHEMA_VERSION 5):

| flaga | klucz extra_prep | ofert |
|---|---|---:|
| Ogrzewanie lusterek | `external_mirror_heat` | 2 761 |
| Zdalny rozruch | `engine_remote_start` | 2 749 |
| Rozpoznawanie znaków | `road_traffic_sign_recognition` | 2 566 |
| **Tylna oś skrętna** (4WS, `整体转向`) | `overall_turn` | 192 |
| Lodówka | `car_refrigerator` | 794 |
| Dolby Atmos | `dolby_panoramic_sound` | 748 |
| Fotel zero gravity | `zero_gravity_seat` | 620 |
| Głośniki w fotelach | `seat_speakers` | 371 |
| Hak holowniczy | `drag_hook` | 333 |
| Sterowanie gestami | `gesture_control_system` | 157 |

Sekcja „Technologia i wyposażenie" ma 36 pastylek. Skrypt pomiaru kandydatów:
`tmp/` sesji (jednorazowy); reguła obecności ta sama co dla reszty flag (NEGATIVE, pierwszy wariant).

## 0.37.3 — 2026-09-03 (koniec „Więcej filtrów": przegrupowanie wg odbioru Janka)

Wsad: „zasięg CLTC do napędu i rozdziel: zasięg całkowity / zasięg na prądzie (CLTC); skrzynię
chowamy; liczba miejsc do nadwozia na koniec; szyberdach → dach panoramiczny; resztę do
wyposażenia; rocznik i przebieg do sortowania; felgi wyrzucamy — czyścimy całą sekcję".

- **Napęd** ma dwa pola zasięgu: „Zasięg całkowity od" (`range_total`) i „Zasięg na prądzie
  (CLTC) od" (`range_cltc`). CLTC = China Light-Duty Vehicle Test Cycle, zapis poprawny.
- **Nadwozie** kończy się „Liczbą miejsc" (lista, 7 kolumn siatki).
- **Technologia i wyposażenie**: „Marka nagłośnienia" (lista) + 26 pastylek, w tym nowa
  **„Dach panoramiczny"** = flaga `roof_panorama` z enumu `sunroof` (panorama otwierana lub
  stała), **1 658 ofert (54,4%)**, SCHEMA_VERSION 4.
- **Sortowanie** dostało „Najnowszy rocznik" (`year_desc`); cena i przebieg były tam już wcześniej
  (Najtańsze / Najdroższe / Najmniejszy przebieg). Założenie: cena filtruje się odtąd tylko przez
  sortowanie — wsad nie precyzował, do cofnięcia jednym słowem.
- **Poza UI** (parametry API `skrzynia`, `felgi_*`, `cena_*`, `rocznik`, `przebieg_*` zostają dla
  deep-linków): skrzynia biegów, felgi, cena, rocznik, przebieg. Sekcja „Więcej filtrów" nie istnieje.

## 0.37.2 — 2026-09-03 (ekrany dla pasażerów + wszystkie pastylki w jednej sekcji)

Odbiór Janka: „sprawdź, czy gdzieś jest opcja ekranu dla pasażerów albo w zagłówkach; przenieś
[15 pastylek z Więcej filtrów] do Technologia i wyposażenie".

**Pomiar ekranów w `extra_prep`:** ekran tylny `rear_lcd_screen` (klucz w 1 698 ofertach, standard
w 765, opcja 42), ekran pasażera z przodu w kluczach DYNAMICZNYCH z sufiksem wartości
(`vice_screen_size_15.7`, `copilot_screen_resolution_*`), ekranów w zagłówkach brak (jedyny ślad:
`rear_entertainment_screen_resolution` w 116 ofertach = podzbiór ekranu tylnego).

Dwie nowe flagi (SCHEMA_VERSION 3): `screen_rear` „Ekran dla pasażerów z tyłu" **765 (25,1%)**,
`screen_copilot` „Ekran pasażera (przód)" **225 (7,4%)**; oba naraz 67. Pod klucze dynamiczne
`AsiaAuto_Specs_Table::flag()` dostało opcję `prefix` (dopasowanie po początku nazwy klucza)
— reguły NEGATIVE i `firstVariant()` bez zmian. Zgodne ze spec danych etapu 2 (27,5% / 7,6%).

**Sekcja „Technologia i wyposażenie" ma teraz wszystkie 25 pastylek** (8 z makiety + 2 ekrany +
15 przeniesionych); w „Więcej filtrów" zostały: cena, rocznik, przebieg, liczba miejsc, felgi,
zasięg na prądzie, skrzynia, szyberdach, marka nagłośnienia.

## 0.37.1 — 2026-09-03 (liczba miejsc jako lista)

Odbiór Janka: „wygląda nieźle, ilość miejsc tylko bym chciał listą, bo to będzie pewnie 4, 5, 6, 7".
Pole „Liczba miejsc od" (zakres) zamienione na listę rozwijaną z wartościami i licznikami
zależnymi (w bazie: 2, 4, 5, 6, 7; rosnąco). Nowy parametr URL `miejsca=6,7`; zakres
`miejsca_min/_max` zostaje w API. Kolumna `seats` dołączona do `ENUM_PARAMS`, więc liczniki
idą tą samą ścieżką co reszta list. Sprawdzone: SSR 6+7 = 669 = SQL; w przeglądarce liczniki
i przycisk aktualizują się po wyborze.

## 0.37.0 — 2026-09-03 (wyszukiwarka wg makiety I + ruch C: osiem nowych kolumn specs)

**Kierunek zaakceptowany i wdrożony tego samego dnia.** Po pięciu odrzuceniach (A–D, boczna
kolumna, E–H) Janek przyjął **makietę I** („teraz jest OK"): wygląd Otomoto — biały panel,
etykieta nad polem, sekcje w jego kolejności, wszystko widoczne od razu, jeden promień 6 px.
Wdrożenie 1:1 na `/wyszukiwarka/` (nadal niepodlinkowana, `/samochody/` nietknięte — mtime
`class-asiaauto-inventory.php` 25.08, `asiaauto-inventory.js` 20.04, `asiaauto-inventory.css` 26.08).

| sekcja | pola |
|---|---|
| Nadwozie | marka, model (wyłączony bez marki), rodzaj nadwozia, napęd 4x4, **długość od** (m), **DMC do** (kg) |
| Napęd | silnik (paliwo), moc od, przyspieszenie do, bateria od, **zasięg od** (łączny; dla EV = CLTC) |
| Styl i komfort | kolor nadwozia (kropki), **kolor wnętrza** (kropki, 6% pokrycia), materiał tapicerki, **zawieszenie** (pneumatyczne / adaptacyjne) |
| Technologia i wyposażenie | pastylki: lidar, kamera 360°, masaż przód, **masaż tył**, wentylowane fotele, AR-HUD, **autopilot miejski (NOA)**, **nagłośnienie premium** |
| Więcej filtrów (zwijane) | cena od–do, rocznik, przebieg do, miejsca od, felgi od, zasięg na prądzie od, skrzynia, szyberdach, **marka nagłośnienia**, pozostałe 15 flag |
| Oferty (ostatnia) | kafle **do sprowadzenia z Chin / w drodze do Polski / na placu w Polsce** z liczbami PO filtrach; klik = zawęź, drugi klik = wszystkie; czerwony „Pokaż N ofert" przewija do wyników |

Cena, rocznik i przebieg nie były we wsadzie Janka, więc siedzą w „Więcej filtrów". Etykieta
„Na placu w Polsce" (zamiast „w Rzeszowie") — ruch B z promptu rozstrzygnięty makietą: część
aut `on_lot` stoi w Pabianicach i Warszawie.

**Ruch C — osiem nowych kolumn `wp7j_asiaauto_specs`** (SCHEMA_VERSION 2, `dbDelta` dodało
kolumny i indeksy za pierwszym razem, sprawdzone `SHOW COLUMNS`; backup tabeli
`~/backups/primaauto/2026-09-03/specs-przed-ruch-c.sql.gz`):

| kolumna | źródło | pokrycie publish | uwaga |
|---|---|---:|---|
| `length_mm` | `length` (4 cyfry), fallback pierwsza liczba z `length_width_height` | 99,1% | < 1000 → NULL (nie zgadujemy jednostki); 986 ofert ≥ 5 m |
| `gvw_kg` | `full_load_weight` | 95,2% | |
| `range_total` | `combined_cruising_range_cltc`; EV = `range_cltc` | 68,7% | spalinowe bez wartości |
| `interior_color` | taksonomia `interior-color` | 6,0% | filtr z małym pokryciem — świadomie, był we wsadzie |
| `suspension` | `air_suspension` → pneumatyczne (795); `variable_suspension` z `软硬/高低` → adaptacyjne (631) | 46,9% | pneumatyczne ma pierwszeństwo |
| `sound_brand` | `sound_brand` przez słownik `SOUND_BRANDS` (29 zapisów CN/EN → 19 slugów) | 32,5% | **0 nieznanych** w raporcie |
| `seat_massage_r`, `noa_city`, `sound_premium` | flagi: `rear_seat_massage`, `navigation_assisted_driving_1`, rozpoznana marka audio | 994 / 1 284 / 986 | |

Pokrycia zgodne z pomiarem na surowym `extra_prep` (985 ≥ 5 m, 994 masaż tył, 1 284 NOA).
Nowe parametry URL: `dlugosc_min` (metry, ×1000 w `parseParams`), `dmc_max`, `zasieg_calk_min`,
`kolor_wnetrza`, `zawieszenie`, `audio`. Pola są tekstowe („5,0", „100 000") — spacje i przecinek
normalizowane po obu stronach.

**Pomiary (test przepisany pod nowy DOM, `scripts/test-ui-wyszukiwarka.mjs`):** marka → model
(44 modele po BYD), pastylka, długość 5,0 m → 74 oferty, deep-link po odświeżeniu (TAK), kafel
„Na placu" zawęża i drugi klik zdejmuje, „Więcej filtrów" z odznaką, sortowanie, „Wyczyść"
(URL czysty), paginacja, lista (otwarcie + Escape). **axe 0 naruszeń** przy 320 i 1366 px, także
z otwartą listą; 0 błędów JS; brak reflow; `impeccable detect` 0; bramka porównawcza 50 kombinacji =
**0 rozjazdów**.

**Pułapki tej wersji:** przycisk `display:flex` zjada spacje między spanami („Pokaż42oferty") —
`gap` zamiast spacji; szare `.aas__total-slowo` w czerwonym przycisku = kontrast poniżej progu,
nadpisane na biel. Para pól ceny w siatce 6 kolumn potrzebuje `grid-column: span 2`.

Backupy: `.bak-2026-09-03-makieta-i` przy `class-asiaauto-search.php`, CSS i JS;
`.bak-2026-09-03-ruch-c` przy `class-asiaauto-specs-table.php`. Kopie klas w repo
`plugins/asiaauto-sync/`.


## 0.36.2 — 2026-09-03 (moc układu w hybrydach + cron 05:00; boczna kolumna zbudowana i wycofana tego samego dnia)

**Kierunek wizualny — rozstrzygnięty makietą I.** Po odrzuceniu makiet A–D Janek podał wsad
(„styl nie pasuje do reszty serwisu, telefon źle, grupowanie dziwne"), a w quizie wybrał moją
rekomendację „szkielet jak /samochody/". Zbudowana na tym boczna kolumna (sidebar + szuflada
mobilna, komplet bramek zielony) została **odrzucona w odbiorze**: chodziło o pasek GÓRNY jak
w 0.36, jak najwięcej filtrów widocznych od razu i możliwość złożenia „auta marzeń", nie o kopię
katalogu. Kod cofnięty do 0.36.1 (pliki `.bak-2026-09-03-sidebar` na serwerze), numer 0.36.2, bo
w środku została naprawa mocy. Wniosek: zła rekomendacja w quizie — „reszta serwisu" przeczytana
jako „powtórz /samochody/". Runda 2 makiet (E Otomoto, F mobile.de, G pasek z wszystkim, H paski
sekcji) → runda 3: **makieta I zaakceptowana** („teraz jest OK"): wygląd Otomoto, desktop, pięć
sekcji wg wsadu Janka (Nadwozie: marka/model/rodzaj/4x4/długość/DMC · Napęd: silnik/moc/
przyspieszenie/bateria/zasięg · Styl i komfort: kolor nadwozia/kolor wnętrza/materiał/zawieszenie ·
Technologia i wyposażenie: lidar, kamery, masaże… · **Oferty jako piąta sekcja na dole**: kafle
do sprowadzenia / w drodze / na placu z liczbami po filtrach + „Pokaż N ofert"), **jeden promień
6 px** na wszystkich kontrolkach. Generatory: `docs/makiety/gen-e.py` (E–H), `gen-i.py` (I);
podgląd `https://auratest.pl/fe4f58fec53ctmp/primaauto-makiety/`. Wdrożenie = osobny krok
(wymaga nowych kolumn specs: długość, DMC, zasięg łączny, masaż tył, kolor wnętrza, zawieszenie
adaptacyjne, NOA, nagłośnienie).

**Moc układu w hybrydach — ruch A z promptu domknięcia.** Filtr mocy odsiewał PHEV/EREV, bo
`_asiaauto_horse_power` trzymało moc silnika spalinowego albo było puste (nowe oferty che168
dostają katalog Autohome nocą, ale nikt z niego mocy do meta nie przepisywał). Stan na starcie:
**120** ofert PHEV/EREV publish z mocą < 200 KM lub pustą (prompt mówił 98), z tego 85 bez stempla.

Ustalenie, które zmieniło skrypt: **`system_max_power` z katalogu Autohome to „最大功率" z nagłówka,
dla BYD DM-i / Denza / VOYAH równe mocy SILNIKA** (Denza D9: 115 kW = 156 KM silnika, a
`electric_total_horsepower` = 333). Stempel „system_max_power" z 02.09 bywał więc fałszywy. Prawdziwą
moc łączną niesie `electric_system_power` (系统综合功率; Leopard 5: 505 kW = 687 KM, zgodnie
z producentem). Nowa **publiczna** `AsiaAuto_Spec::system_km_from_extra_prep()` (obok prywatnej
`km_from_power()`, która zostaje dla tabeli huba) bierze w kolejności: `electric_system_horsepower` /
`electric_system_power` → `electric_max_power` / `energy_elect_max_power` „kW(Ps)" →
`electric_total_horsepower` / `total_electric_power`; **bez** `system_max_power`, **nigdy** `engine_*`.

`scripts/napraw-moc-ukladu.php` (dry-run domyślnie, `apply`, `since=48h`, `silnik`, `paliwa=`,
`raport=`) **tylko podnosi** i stempluje źródło. Dry-run pokazany Jankowi przed zapisem, wybrany
pełny zakres:

| zakres | podniesione |
|---|---|
| PHEV/EREV (w tym 3 ze stemplem silnika: AITO M9 160 → 496, Leopard 5 194 → 687, Volvo S90 310 → 456) | 126 |
| EV/HEV z pustą mocą | 41 |

Po biegu: PHEV/EREV z mocą 0 KM **22 → 3** (Leapmotor D19, 2× BYD Han DM-i 2025 — brak klucza
mocy w danych, do ręcznego wpisania), poniżej 200 KM **120 → 47** (BYD DM-i 163–197 KM zgodnie
z danymi), EV z 0 KM 39 → 1, meta vs tabela specs 0 rozjazdów. Na 72 ofertach karta pokaże inną
liczbę niż filtr — `resolvePower()` w zamrożonym `class-asiaauto-inventory.php` bierze moc
przedniego silnika, nie łączną. Nie ruszane.

**Cron 05:00** (`napraw-moc-ukladu.php apply silnik since=48h paliwa=phev,erev,electric,hybrid`,
przez `cron-install`, log `~/.claude/napraw-moc-ukladu.log`) — piąte ogniwo nocnej sekwencji, po
katalogu 04:55, **przed** specs 05:05, bo tabela specs czyta moc z meta. Bez niego naprawa wracałaby
z każdą nową hybrydą che168.

Backupy: `~/backups/primaauto/2026-09-03/postmeta-horse-power-przed-moc-ukladu.sql.gz`,
`.bak-2026-09-03-sidebar` przy `class-asiaauto-search.php`, CSS i JS, `.bak-2026-09-03-system-km`
przy `class-asiaauto-spec.php`, `.bak-2026-09-03-v037` przy `asiaauto-sync.php`,
`~/backups/crontab/crontab-2026-09-03-090155.bak`.


## 0.36.1 — 2026-09-02 (hierarchia nagłówków i etykiety grup filtrów)

Znalezione detektorem `impeccable detect` (61 reguł, skanuje kod bez udziału modelu;
`npx impeccable detect <katalog>`, bez instalacji do repo).

**`skipped-heading`:** `<h1>` strony, a zaraz po nim `<h3>` — najpierw etykiety grup
w popoverach, po ich naprawie tytuły ofert z `renderCard()`. Czytniki ekranu budują
nawigację na hierarchii nagłówków, a przeskok poziomu ją łamie. Axe tego nie zgłasza,
bo to reguła dobrych praktyk, nie kryterium WCAG A/AA — dlatego przeszło przez wszystkie
wcześniejsze pomiary.

Dwie naprawy, obie bez ruszania `class-asiaauto-inventory.php`:
- etykiety grup w popoverach (`Marka`, `Cena`, `Fotele i kierownica`…) to nie nagłówki
  dokumentu, tylko opisy grup kontrolek: `<h3>` → `<p>` + `role="group"` z `aria-labelledby`;
- dodany poziom `<h2>` („Wyniki wyszukiwania", dla czytników) między `<h1>` strony a `<h3>`
  w kartach ofert. `/samochody/` ma ten poziom i przechodzi detektor czysto, więc powtarzamy
  tamtejszy wzorzec zamiast dotykać wspólnej karty.

Po naprawie `/wyszukiwarka/` przechodzi detektor bez uwag, hierarchia to `h1 → h2 → h3` jak
na `/samochody/`.

**Przy okazji, do zapamiętania:** ten sam detektor puszczony na makietę wariantu D wyłapał
kontrast **4,2:1** przy akcencie `#E23E3F` w obie strony (biały tekst na czerwieni i czerwień
na ciemnym tle) — poniżej progu 4,5:1. Makieta nigdy nie przeszła przez axe, więc nikt by tego
nie złapał. Wniosek na przyszłość: makiety też trzeba mierzyć, nie tylko wdrożony kod.


## 0.36.0 — 2026-09-02 (wyszukiwarka: układ poziomy, filtr rodzaju oferty, przyspieszenie 0–100)

Przeprojektowanie na życzenie Janka. Boczna kolumna z 16 rozwijanymi sekcjami zamieniona
na **pasek poziomy**: przełącznik rodzaju oferty, pod nim rząd pigułek otwierających popovery.

**Dwa nowe filtry:**

| Filtr | Źródło | Pokrycie |
|---|---|---|
| Przyspieszenie 0–100 | `acceleration_time` → kolumna `accel_s` | 81,4% (2,0–12,9 s, mediana 5,7) |
| Rodzaj oferty | `_asiaauto_reservation_status` → kolumna `reservation` | 100% (brak wartości = do sprowadzenia) |

Rodzaj oferty nie jest zwykłym enumem: „Do sprowadzenia" to **brak wartości**, nie wartość,
więc ma własną obsługę w `buildWhere()`. Rozkład: 2 921 do sprowadzenia, 26 w drodze,
19 na placu, 1 zarezerwowana. `reserved` celowo zostaje tylko w „Wszystkie" — nie jest ani
dostępna do importu, ani w drodze, ani na placu.

**Decyzje projektowe** (skill `frontend-design`; `design-taste-frontend` z repo Leonxlnx
nie jest u nas zainstalowany, Janek zdecydował 02.09, żeby nie instalować):

- **Granat `#1B2A4A` = stan aktywny filtra, czerwień `#C92A2B` wyłącznie akcja i cena.**
  Gdyby aktywny filtr też był czerwony, pasek konkurowałby wzrokowo z „Zamów" w każdej karcie.
- **Przełącznik rodzaju oferty to jedyne mocne miejsce na stronie.** Niesie informację,
  której nie da się wyczytać skądinąd: prawie wszystko trzeba sprowadzić z Chin, ale
  kilkanaście aut stoi w Polsce i można je obejrzeć. Liczby są przy etykietach, nie schowane
  w liczniku wyników — i przeliczają się zależnie (przy BYD: 377 wszystkich, 3 na placu).
- Liczby na `tabular-nums` — zmieniają się w miejscu przy każdym filtrze i nie mogą skakać.
- Jedna kolumna kart, jak w 0.35.0.
- Telefon: przełącznik zostaje pionowo (najważniejszy wybór, nie chowamy go), pigułki
  przewijają się poziomo, popover staje się arkuszem od dołu z zasłoną.

**Trzy usterki złapane przy składaniu:**

1. Pigułki bez tła i ramek — `.aas button` (specyficzność 0-1-1) bije `.aas__chip-btn` (0-1-0).
   Wszystkie stylowane przyciski mają teraz prefiks `.aas `.
2. Przełącznik nie przenosił stanu aktywnego po zmianie bez przeładowania — klasę `is-active`
   ustawiał tylko PHP przy renderze.
3. Wgrany CSS i JS bez PHP dał stronę bez stylów — przy przepisywaniu szablonu zabrakło `cp`.

**Pomiary:** 150 kombinacji = 0 rozjazdów wobec starej trasy; `search` 70–80 ms przy siedmiu
filtrach naraz, `search-counts` 58–64 ms z cache; axe 0 naruszeń przy 320 i 1366 px;
popover otwiera się i zamyka Escapem na obu rzutniach; zero błędów JS.

**Do rozstrzygnięcia przez Janka:** etykieta „Na placu w Rzeszowie" jest niedokładna.
Z 19 ofert `on_lot` tylko 11 ma `stm_car_location` = Rzeszów; pozostałe to Pabianice (4),
„W drodze do UE" (2), Kanton (1), Warszawa (1). Część z tego to nieodświeżone pole
(`stm_car_location` nie jest wiarygodne — patrz memory), ale Pabianice i Warszawa wyglądają
na realne lokalizacje. Albo etykieta wraca do „Na placu" (jak badge na karcie), albo trzeba
uporządkować dane.

**Scenariusz testu przeglądarkowego przepisany** pod nowy układ i rozszerzony o rodzaj oferty,
przyspieszenie oraz mechanikę popovera (otwarcie + Escape) — luka, przez którą 0.35.1 i 0.35.2
przepuściły osiem usterek.


## 0.35.2 — 2026-09-02 (pola liczbowe nie przyjmowały okrągłych wartości, grupy zakresowe się nie zwijały)

Dwie usterki zgłoszone przez Janka przy jego własnym przeglądzie, obie niewidoczne dla testów.

**1. `step` blokował okrągłe liczby.** Pola „od–do" miały `step` (cena 1000, moc i zasięg 10)
oraz `min`/`max` z realnego zakresu danych. HTML liczy krok **od wartości `min`**, nie od zera:
przy `min=95` i `step=10` dozwolone są 95, 105, … 995, 1005 — wpisanie **1000 KM** dawało
komunikat „najbliższe prawidłowe wartości to 995 i 1005". `max` dodatkowo blokował wpisanie
czegokolwiek powyżej zmierzonego maksimum.

W filtrze zakresowym krok nie ma sensu — użytkownik wpisuje dowolną liczbę, nie skacze co 10.
Zostało `step="any"` i `min="0"`, `max` usunięty. Zakres nadal podpowiada placeholder,
teraz sformatowany („od 100 000" zamiast „od 100000"), z wyjątkiem rocznika — tam separator
tysięcy w „od 2 022" wyglądał jak błąd.

**2. Grupy zakresowe w ogóle się nie zwijały.** Kliknięcie w „Cena, rocznik, przebieg",
„Osiągi i bateria" czy „Miejsca i felgi" ustawiało `aria-expanded="false"` i atrybut `hidden`,
ale sekcja zostawała widoczna. Grupy z checkboxami zwijały się normalnie.

Przyczyna to **ten sam mechanizm, co usterka #1 z 0.35.1**: `[hidden]` jest w arkuszu
przeglądarki zwykłym `display: none`, więc każda nasza reguła z `display` je bije.
Tam był `.aas__opt { display: flex }`, tu `.aas__ranges { display: grid }`. Punktowa łatka
z 0.35.1 zamykała tylko pierwszy przypadek.

Zamiast trzeciej łatki — reguła zbiorcza `.aas [hidden] { display: none !important }`,
obejmująca wszystko wewnątrz panelu, także elementy dodane w przyszłości.

**Sprawdzone po naprawie:** wszystkie 8 pól zakresowych przyjmuje 1000 bez komunikatu
walidacji (wynik 0 ofert z czytelnym komunikatem pustego stanu), każda z 16 grup zwija się
i rozwija w obie strony, axe nadal 0 naruszeń przy 320 i 1366 px, 100 kombinacji = 0 rozjazdów.

**Wniosek:** obie usterki tej sesji dotyczyły `[hidden]` przegrywającego z `display`.
Przy dokładaniu elementów do panelu nie ma potrzeby o tym pamiętać — reguła zbiorcza to trzyma
— ale w innych częściach serwisu ten sam wzorzec (`el.hidden = true` przy własnym `display`)
będzie cichy tak samo.


## 0.35.1 — 2026-09-02 (przegląd w przeglądarce: pięć usterek UI + luka w odświeżaniu ceny)

Przegląd `/wyszukiwarka/` w Chrome zaraz po wdrożeniu 0.35.0. Testy automatyczne (axe, puppeteer,
200 kombinacji filtrów) przechodziły na zielono, a mimo to **oko wyłapało pięć rzeczy** — bo
sprawdzały, czy liczby się zgadzają, a nie czy panel zachowuje się sensownie.

| # | Usterka | Dlaczego automat nie widział |
|---|---|---|
| 1 | Opcje bez trafień nie znikały, tylko się wyszarzały — po wybraniu marki sekcja „Model" pokazywała **220 modeli innych marek** z licznikiem 0 | test sprawdzał `total` i liczniki, nie widoczność opcji |
| 2 | Po pierwszym filtrze licznik gubił spację tysięcy: „1179" zamiast „1 179" | test porównywał tę samą wartość po obu stronach, więc zgadzała się sama ze sobą |
| 3 | Po „Wyczyść" sekcja „Model" zostawała widoczna bez wybranej marki | „Wyczyść" w ogóle nie było w scenariuszu testu |
| 4 | Po zmianie strony widok zostawał na dole listy | paginacja nie była w scenariuszu testu |
| 5 | Paginacja JS bez wielokropka, inaczej niż ta z SSR | kosmetyka, poza asercjami |

**Przyczyny, warte zapamiętania:**

1. `[hidden]` to w arkuszu przeglądarki zwykłe `display: none` — nasze `.aas__opt { display: flex }`
   je bije. `label.hidden = true` z JS nie robiło nic. Naprawa: `.aas__opt[hidden] { display: none !important }`.
2. `toLocaleString('pl-PL')` **nie grupuje liczb czterocyfrowych** (CLDR `minimumGroupingDigits=2`
   dla polskiego), a PHP `number_format($n, 0, ',', ' ')` grupuje zawsze. SSR i JS pokazywały
   różne liczby. Naprawa: własny `fmt()` ze spacją co trzy cyfry.
3. Warunkiem widoczności sekcji „Model" było „są jakieś modele w licznikach" — bez marki są
   wszystkie 2 596. Naprawa: warunkiem jest wybrana marka.
4. Motyw ustawia `scroll-behavior: smooth` na `<html>`, więc `window.scrollTo` przed fetchem
   było animowane i Chrome anulował je przy zmianie wysokości dokumentu. Naprawa: przewijanie
   **po** podmianie wyników, z jawnym `behavior: 'instant'`.

**Szósta rzecz, znaleziona przy kontrolnym przebiegu — luka w odświeżaniu tabeli.**
Bramka porównawcza, wcześniej 0 rozjazdów, pokazała 1–2 na 50 kombinacji. Nie regresja kodu:
**14 ofert miało w tabeli inną cenę niż w `postmeta`**. Pipeline cenowy zapisuje `price` przez
`update_post_meta` bez `wp_update_post`, więc ani `asiaauto_after_set_taxonomies`, ani
`transition_post_status` się nie odpalają — przeceny nie docierały do tabeli, a filtr ceny
odsiewał te oferty po złej stronie progu.

Naprawa dwuwarstwowa:
- hook `updated_post_meta` / `added_post_meta` na `price`, `mileage`, `_asiaauto_horse_power`
  → punktowy `UPDATE` jednej kolumny (bez czytania `extra_prep`), z czyszczeniem cache liczników
  **raz na żądanie** (`shutdown`), nie raz na ofertę — przy masowym przeliczaniu cen to różnica
  między jednym `DELETE` a trzema tysiącami;
- siatka bezpieczeństwa w `idsToRebuild()`: cron 05:05 dociąga wiersze, w których cena albo
  przebieg rozjechały się z meta (zapis surowym SQL-em albo import z wyłączonymi hookami
  ominie hook).

**Po naprawie:** 0 rozjazdów cena/przebieg w całej tabeli, **5 seedów × 50 kombinacji = 0 rozjazdów**,
axe nadal 0 naruszeń przy 320 i 1366 px, deep-link odtwarza stan, zero błędów JS.

**Wniosek na przyszłość:** test przeglądarkowy sprawdzał, czy liczby się zgadzają. Nie sprawdzał
„Wyczyść", paginacji, sortowania ani widoczności opcji. Scenariusz w `scripts/test-ui-wyszukiwarka.mjs`
warto o nie rozszerzyć, zanim dołożymy drugi rzut pól.


## 0.35.0 — 2026-09-02 (T-116 etap 3: wyszukiwarka zaawansowana na `/wyszukiwarka/`)

Osobna, publiczna strona; **nigdzie nie podlinkowana** (decyzja Janka: nikt nie ma trafiać
celowo w coś, czego jeszcze nie odebraliśmy). Bez `noindex` — indeks i sitemapa jak każda strona.
`/samochody/` nietknięte: `class-asiaauto-inventory.php` i `asiaauto-inventory.js` mają mtime
sprzed sesji (25.08 i 20.04), jedyny styk to publiczna od dawna `renderCard()`.

**Co doszło (wszystko addytywnie, nowe pliki):**

| Plik | Rola |
|---|---|
| `includes/class-asiaauto-specs-table.php` | tabela `wp7j_asiaauto_specs`, normalizatory (czyste funkcje), `rebuildRow()`, hooki |
| `includes/class-asiaauto-search.php` | REST `asiaauto/v1/search` i `/search-counts`, shortcode `[asiaauto_search]`, UI |
| `assets/css/asiaauto-search.css`, `assets/js/asiaauto-search.js` | panel filtrów, wyniki, stan w URL |
| `scripts/zbuduj-specs.php` | backfill i odświeżanie (`apply`, `since=48h`, `limit=N`), dry-run domyślnie |
| `scripts/porownaj-search.php` | bramka: stara trasa vs nowa na losowych kombinacjach |

Klasa nazwana `AsiaAuto_Specs_Table`, nie `AsiaAuto_Specs` jak w prompcie — obok żyje
`AsiaAuto_Spec` (generator tabeli huba) i różnica jednej litery myliłaby przy grepie.

**Tabela** — 3 396 wierszy (2 970 publish), 1,7 MB, 41 kolumn: 8 zakresów, 9 enumów
(w tym marka/model/kolor, dołożone bo bramka porównawcza filtruje po marce), 20 flag,
`status`/`source`/`published_at` w tabeli, żeby sortowanie i odsiew draftów nie wymagały
JOIN-a do `wp_posts`. Indeksy tylko na kolumnach zakresowych i enumach — na flagach
`tinyint(1)` o kardynalności 2 przy 3,4 tys. wierszy optymalizator i tak wybiera skan.

**Ustalenie, którego nie było w spec danych: pusta wartość klucza znaczy NIE.**
Dowód na `air_suspension`: 1 653 ofert ma klucz, z tego 804 puste, 41 `选配`, 808 `标配`
→ 27,1%, dokładnie pokrycie 28% ze spec danych. Ta sama zgodność na `header_display_system`
(31,9 vs 33%), `heat_pump` (43,7 vs 44%), `sentinel` (69,6 vs 70%), `lane_center` (86,7 vs 87%).
Bez tej reguły flagi byłyby zawyżone o 10–27 punktów.

**Bramki, wszystkie zdane:**

| Bramka | Wymóg | Wynik |
|---|---|---|
| normalizatory na 30 realnych `extra_prep` (10/10/10) | ręczna weryfikacja | 30/30 |
| pokrycie kolumn vs spec danych | ±2 pkt | zgodne; trzy odchylenia wyjaśnione |
| nieznane wartości enumów | brak | 0 (słowniki: 24 warianty tapicerki, 16 szyberdachu) |
| nowa trasa vs `/listings` | 0 rozjazdów | **200 kombinacji × 4 seedy = 0** |
| `search` przy 6 filtrach | < 200 ms | **64–71 ms** (HTTP, z renderem 24 kart) |
| `search-counts` | < 300 ms | **49–52 ms** z cache, 106 ms bez |
| axe WCAG A/AA/2.1/2.2 AA, 320 px i 1366 px | Lighthouse a11y ≥ 95 | **0 naruszeń** na obu |
| reflow przy 320 px | brak | brak |
| deep-link z 5 filtrami po odświeżeniu | odtwarza stan | odtwarza (SSR i JS) |
| import i zmiana statusu | wiersz sam się aktualizuje | hook, `transition_post_status`, trash usuwa wiersz |
| zero linkowania wewnętrznego | grep = 0 | motyw 0, treści 0, menu 0, `llms.txt` 0 |

**Trzy usterki znalezione i naprawione w trakcie** (każda widoczna dopiero w przeglądarce):

1. `.aas__panel-body` bez `min-height: 0` nie kurczył się jako flex-item — szuflada
   przestawała przewijać i filtry wypływały poza ekran (checkbox „Lidar" na y=2360 w rzutni 720).
2. Grid wyników bez klasy `aa-inv` — 15 reguł `asiaauto-inventory.css` stylizuje wnętrze karty
   selektorem `.aa-inv .aa-card__…`, więc „Szczegóły/Zamów" renderowały się jako gołe linki.
3. Dwie kolumny kart na szerokim ekranie rozbijały kartę (tytuł łamany po słowie, drugi przycisk
   poza kartą) — karta jest pozioma i zaprojektowana pod ~840 px. Została jedna kolumna, jak na `/samochody/`.

**Cron 05:05** — czwarte ogniwo nocnej sekwencji (bliźniak 04:35 → bank 04:45 → katalog 04:55
→ specs 05:05), `zbuduj-specs.php apply since=48h`, przez `~/bin/cron-install`.
Backup bazy przed `CREATE TABLE`: `~/backups/primaauto/2026-09-02-t116e3/wp521-przed-specs.sql.gz`.

**Rozjazdy prompt vs produkcja, warte zapamiętania:** `_asiaauto_reservation_status` ma
**1,5%** pokrycia, nie 100% (46 ofert publish: 26 `in_transit`, 19 `on_lot`, 1 `reserved`;
brak meta = „sprowadzimy"). Publish było 2 977, nie 2 988.

**Zauważone obok, nie ruszane:** 101 ofert PHEV/EREV ma `_asiaauto_horse_power` < 200 KM,
w tym 66 bez stempla `_..._source` — to moc silnika, nie układu (AITO M9 EREV: 152 KM zamiast
~490, Leapmotor D19: 0). Filtr mocy na nich kłamie. Naprawa = podniesienie do mocy układu
przez `km_from_power()`, osobna decyzja. Poza tym duplikat `uywany`/`used` w taksonomii
`condition` i termy-śmieci z surowym CJK w slugu w `transmission`/`fuel` (scalane aliasem
w normalizatorze, w bazie zostają).

**Nie dowozi (świadomie, sekcja 9 promptu):** linkowania i promocji strony, podmiany
`/samochody/`, drugiego rzutu pól, pokazywania wyposażenia opcjonalnego jako „opcja",
autocomplete, ofert dongchedi bez `spec_id`.


## 0.34.31 — 2026-09-02 (dogrywka: cztery korekty danych `extra_prep` i mocy, bez zmian kodu pluginu)

Mockup i decyzja Janka (wariant B dla mocy) tego samego dnia. Skrypty w `scripts/`, dump
`postmeta` przed w `~/backups/primaauto/2026-09-02/postmeta-before-poprawki-1-4.sql.gz`,
weryfikacja klucz po kluczu po zapisie: 3 367 ofert, zero zmian poza dozwolonymi.

| Korekta | Zasięg | Narzędzie |
|---|---|---|
| pary „8个 / 12个" → 8 (standard) w 5 kluczach liczbowych z katalogu | 174 ofert, 264 wartości | `napraw-extra-prep-t116.php pary` + reguła w `$norm` mergera |
| uszkodzony unicode `u7406u60f3` → znaki, także `360u00b0` | 129 ofert, 491 wartości | `napraw-extra-prep-t116.php unicode` |
| `air_supply` = doładowanie (che168) → `gas_form`; `air_supply` ← `配气机构` z cache (DOHC) | 1 173 ofert: 1 147 DOHC, 26 usunięte, 28 nowych `gas_form` | `napraw-extra-prep-t116.php air` + `che168-param-map.php` param 13 → `gas_form` |
| `_asiaauto_horse_power` z `extra_prep`, moc UKŁADU przed mocą silnika | 2 231 uzupełnionych, 204 zmienionych (PHEV: AITO M9 152 → 496), 42 zachowanych (katalog bez mocy układu), 5 bez źródła; pokrycie 40% → 99,6% | `uzupelnij-moc-km.php all` + stempel `_asiaauto_horse_power_source` |

Uwaga: umowa PDF czyta to meta jako „Moc … KM" — nowe umowy dla PHEV/EREV pokażą moc układu,
tak jak karty ofert (te już wcześniej brały moc układu z `extra_prep`). Wygenerowane PDF-y
bez zmian.

## 0.34.31 — 2026-09-02 (T-116 etap 2: katalog Autohome jako trzecie ogniwo nocne, backfill wyposażenia)

**Skąd.** Flagi wyposażenia w `extra_prep` miało 51,6% ofert publish; bliźniak (04:35) i bank
(04:45) wyczerpały dawców (7 ofert ostatniej nocy, 925 ubogich bez dawcy). Katalog Autohome
po `spec_id` pokrywa 99% ofert che168 niezależnie od stanu lokalnego, ale był uruchamiany
tylko ręcznie i gubił 1/3 danych. ADR: `docs/decyzje/2026-09-02-katalog-autohome-trzecie-ogniwo-nocne.md`.

| Zmiana | Gdzie | Efekt |
|---|---|---|
| fetcher czyta `valueitems[].sublist[]` (`displaytype: 1`) | `scripts/autohome-catalog-fetch.js` | 99/303 pustych pozycji → 0; fotele, kamery, klucze, mirroring, tryby jazdy |
| trzy tryby mapy: `klucz=wartość`, `['podnazwa' => klucz]`, `nazwa@grupa`; `wp_slash` przy zapisie; flaga `verbose` | `scripts/autohome-catalog-merge.php` | pola złożone rozbite na flagi w kształcie dongchedi; koniec zjadania `\n` |
| mapa 218 → 314 wpisów (wszystkie pozycje ≥50% próbki inwentarza + 16 tanich) | `data/autohome-catalog-map.php` | +30–56 pól na ofertę względem starej mapy |
| +39 etykiet z kategoriami, +52 tłumaczenia wartości, +4 jednostki | `data/translations-extra-prep.php` | nowe klucze renderują się na karcie oferty (212 z 227 par w teście) |
| backfill 2 179 ofert che168 (737 pobrań, 1 442 z cache, 0 błędów, +290 230 pól) | jednorazowo, log `~/.claude/backfill-spec-autohome-2026-09-02.log` | patrz tabela |
| cron `55 4 * * *` bez `force`, limit 100/dobę | harmonogram cron przez `cron-install` | nowe oferty che168 dostają katalog co noc |

**Pokrycie przed → po (publish):** masaż przód 20,0% → 57,3%, lidar 17,6% → 45,7%, AR-HUD
11,4% → 25,0%, kamera 360° 46,1% → 92,5%, głośniki 35,5% → 95,8%, marka audio 12,5% → 33,4%,
**którakolwiek flaga wyposażenia 51,6% → 98,2%** (che168 99,2%). Mediana pól: 283.

**Skaza po drodze, naprawiona.** Merger z lipca zapisywał JSON bez `wp_slash`; backfill zamienił
`\n` na `n` w 151 wartościach 140 ofert. Wykryte porównaniem z dumpem sprzed biegu, przywrócone
z dumpu, zweryfikowane: 2 179/2 179 ofert bez odchyleń. Ręczne biegi VII–VIII mogły zrobić to
samo na ~630 ofertach — niezmierzone.

**Nie dowozi (świadomie):** filtrów, tabeli `wp7j_asiaauto_specs`, UI. Analiza pól i wartości
do tej decyzji: `docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md`.

## 0.34.30 + motyw 1.3.3 — 2026-08-26 (a11y: siedem naruszeń widocznych wyłącznie przy 320 px)

**Skąd.** Próbka audytowa jest mierzona przy 1366 px. Po domknięciu wszystkiego, co widać
na desktopie (79 adresów, zero naruszeń axe), ten sam zestaw został przemierzony **przy
320 px** — i wyszło 27 pozycji, których przemiar desktopowy nie widzi. Powód jest mechaniczny:
ten sam tekst przy mniejszym foncie przechodzi z progu kontrastu 3:1 na 4,5:1, a tabele
o sztywnej szerokości minimalnej rozpychają stronę dopiero na wąskiej rzutni.

| Usterka | Kryterium | Zasięg | Naprawa |
|---|---|---|---|
| tabela w treści szersza od rodzica (`scrollWidth` 321 przy 320) | 1.4.10 | `/leasing-samochodu-z-chin/` | `hub.css` — `.aa-hub__body table { display: block; overflow-x: auto }` |
| tabele w hasłach słownika, `scrollWidth` **329–464** | 1.4.10 | **10 haseł** | `kb.css` — to samo dla `.pa-article__content table` w `@media (max-width: 640px)` |
| obszar przewijany niedostępny z klawiatury | 2.1.1 | `.aa-spec__scroll` na ofertach i hubach modeli + tabele powyżej | nowy `themes/primaauto2026/assets/js/a11y-scroll-regions.js` |
| przycisk WhatsApp: biel na `#25D366` — **1,98:1** przy 13 px | 1.4.3 | **17 stron ofert**, pasek CTA przyklejony do dołu ekranu | `asiaauto-single.css` — tło `#17803D`, **5,01:1** |
| zaślepka Complianz nad mapą: biel na `#808080` — 3,94:1 | 1.4.3 | `/kontakt/` | `base.css` — nadpisanie tła na `#6E6E6E`, **5,10:1** |
| link w akapicie treści odróżniony wyłącznie kolorem, 1,18:1 wobec otoczenia | 1.4.1 | 1 news, ale reguła bazowa dotyczy każdego przyszłego | `kb.css` — podkreślenie `.pa-article__content p a, li a` z pominięciem autolinków |

**Dwie zależności warte zapamiętania.**

Naprawa 1.4.10 przez `overflow-x: auto` **tworzy nowy obowiązek z 2.1.1**: obszar, który się
przewija, musi dać się przewinąć z klawiatury, więc potrzebuje `tabindex`. Stąd skrypt, a nie
sama reguła CSS. Skrypt nadaje `tabindex="0"` + `role="region"` **tylko** wtedy, gdy kontener
faktycznie przewija i **nie ma w środku własnego elementu focusowalnego** — karuzela ofert
z linkami zostaje nietknięta, bo klawiatura dociera tam przez linki. Atrybuty są zdejmowane,
gdy kontener przestaje przewijać (obrót ekranu), żeby nie zostawiać martwych landmarków:
zmierzone przy 1366 px — zero oznaczonych obszarów.

Przycisk WhatsApp to **najgorszy kontrast w całym serwisie** (1,98:1) i siedział na elemencie
konwersyjnym, w pasku widocznym na telefonie, czyli na 79,6% ruchu. Wariant „ciemny napis na
markowej zieleni" (6,15:1) odpadł, bo `.aa-mobile-cta a.aa-mcta--wa` ma `color: #fff !important`
i wymagałby przepisania reguły; ciemniejsza zieleń trzyma też spójność paska — trzy przyciski,
trzy białe etykiety na ciemnym tle.

**Pomiar po wdrożeniu — 79 adresów, obie rzutnie:**

| Przemiar | Wynik |
|---|---|
| axe (WCAG A/AA/2.2 AA), 1366 px | **0 naruszeń** |
| axe (WCAG A/AA/2.2 AA), 320 px | **0 naruszeń** |
| reflow przy 320 px (`scrollWidth` > 320) | **0 stron** |
| nieudane ładowania | 0 |

Skrypt przemiaru zachowany jako `scripts/a11y-przemiar-320.mjs` — z ostrzeżeniem, że każdy adres
musi iść w osobnym `try/catch` przy `protocolTimeout` 420 s, bo jeden ciężki `axe.run` wywala
cały przebieg (zaliczone 26.08 na 7. adresie).

**Wersjonowanie:** `ASIAAUTO_VERSION` `0.34.29` → `0.34.30`; `PRIMAAUTO_THEME_VERSION`
`1.2.8` → `1.3.3` (1.2.9 linki w treści, 1.3.0 tabele hubów, 1.3.1 skrypt obszarów przewijanych,
1.3.2 Complianz, 1.3.3 tabele słownika).

Dowody: `docs/dostepnosc/dowody-2026-08-26/` — logi obu przemiarów przed i po, lista 79 adresów.
Backupy: `.bak-2026-08-26-cta-wa`, `.bak-2026-08-26-scroll-regions` oraz wcześniejsze z tego dnia.


## 0.34.29 + motyw 1.2.8 — 2026-08-26 (a11y: cztery usterki spoza próbki audytowej)

**Skąd się wzięły.** Po domknięciu obu pozycji zlecenia v4 przemiar na **73 adresach**
z sitemap (15 ofert, 22 huby, 15 haseł słownika, wszystkie strony statyczne, 404) wykazał
naruszenia, których próbka 17 adresów nie łapie — bo **nie ma w niej ani jednego huba marki**,
a ze słownika jest jedno hasło, akurat bez podpisu ze zdjęciem. Zasięg ustalony skanem
wszystkich **165** stron statycznych, hubów, haseł i newsów.

| Usterka | Kryterium | Zasięg | Naprawa |
|---|---|---|---|
| `.aa-hub__facts-note` („Stan oferty: RRRR") `#9ca3af` na `#f5f6f8` — **2,35:1** | 1.4.3 | **43 huby marek** | `hub.css:21` → `#5C6B7F` (`--c-secondary`), **5,03:1** |
| link w `figcaption` odróżniony wyłącznie kolorem, 2,61:1 wobec otoczenia przy progu 3,0 | 1.4.1 | **53 hasła słownika** | `kb.css` — podkreślenie `figcaption a:not(.aa-autolink)`; autolinki mają własną kropkowaną linię |
| link wewnątrz `<summary>` → axe `nested-interactive` | 4.1.2 | 4 hasła słownika | `class-asiaauto-autolink.php:36` — `summary` dopisane do `SKIP_TAGS` |
| przeskok nagłówka `h1` → `h3` | 1.3.1 | `/leasing-samochodu-z-chin/` | treść strony 398850: dwa `h3` → `h2` |

**Dlaczego autolinker, a nie treść czterech haseł.** Autolinkowanie działa na filtrze przy
renderowaniu, nie jest zapisane w bazie (`wp post list --s="aa-autolink"` → 0). Jedno słowo
w `SKIP_TAGS` naprawia wszystkie cztery strony i **nie pozwala usterce wrócić** przy nowych
hasłach FAQ. Lista już chroniła `a`, `button` i nagłówki — `summary` jest przyciskiem, więc
należał do niej od początku.

**Regresja złapana przy okazji.** Podniesienie nagłówków na stronie leasingu wypisało je
ze stylu — `hub.css:91` celował wyłącznie w `.aa-hub__usp-block h3`. Selektor rozszerzony
o `h2`, wygląd bez zmian. Wniosek na przyszłość: **zmiana poziomu nagłówka w treści potrafi
odpiąć styl**, bo część reguł motywu wisi na nazwie taga, nie na klasie.

**Wersjonowanie:** `ASIAAUTO_VERSION` `0.34.28` → `0.34.29`, `PRIMAAUTO_THEME_VERSION`
`1.2.6` → `1.2.8` (1.2.7 to stan przed poprawką selektora `h2`).

Podgląd zmian ze zrzutami przed/po i pełnymi listami adresów:
`https://auratest.pl/fe4f58fec53ctmp/primaauto-4-poprawki-2026-08-26/`

Backupy: `.bak-2026-08-26-poza-probka` przy `hub.css`, `kb.css` i `class-asiaauto-autolink.php`;
treść strony 398850 sprzed edycji w archiwum sesji.


## 0.34.28 + motyw 1.2.6 — 2026-08-26 (a11y: domknięcie 1.4.3 — jedna czerwień marki #C92A2B)

Pozycja 2 z `~/projekty/auranet/docs/uslugi/wdrozenia/2026-08-25-primaauto-wdrozenie-v4.md`.
Akcept właściciela 26.08.2026. Po zmianie **serwis spełnia wszystkie 43 stosowalne kryteria
WCAG 2.2 AA** — ostatnie 27 elementów z błędem kontrastu zeszło do zera.

**Recepta z karty odbioru była błędna i nie została użyta.** Karta kazała zmienić jedną zmienną
(`base.css:29`, `--c-accent`). Zmierzone: podmiana `--c-accent` na jaskrawą zieleń w przeglądarce
zmienia wartość zmiennej, ale **zero elementów** przyjmuje nowy kolor — `--c-accent` konsumuje
wyłącznie `kb.css` (słownik, rankingi), w większości w stanach `:hover`. Czerwień marki płynęła
z czterech innych zmiennych i z wartości wpisanych na sztywno.

**Zmienione — 23 linie w 9 plikach**, wszystkie `#D63031` → `#C92A2B`:

| Plik | Linie | Co obsługuje |
|---|---|---|
| `themes/primaauto2026/assets/css/hub.css` | 77, 112, 179, 394 | linki i autolinki w treści hubów (`.aa-hub__body a` — wpisane na sztywno, nie schodziło żadną zmienną), strzałki list USP, `--accent` paska „Oferty …", ramka `.aa-spec__summary` |
| `themes/primaauto2026/assets/css/base.css` | 29 | `--c-accent` — słownik i rankingi |
| `class-asiaauto-homepage.php` | 862, 867 | `--accent` i `--accent-text` strony głównej: ceny aut, linki sekcji, `.aa-home__make-name`, tła przycisków |
| `class-asiaauto-shortcodes.php` | 619, 1717, 1985, 2181, 2296, 2423, 2474, 2476, 2483, 2689 | `--aa-accent` + ceny, CTA i kafle marek na stronie 404 |
| `class-asiaauto-contact.php` | 354 | `--accent` — m.in. `.aa-contact__hours-time--closed` |
| `class-asiaauto-login.php` | 67 | `--aa-accent` panelu klienta |
| `assets/css/asiaauto-inventory.css` | 10 | `--aa-accent` listingu |
| `assets/css/asiaauto-order-wizard.css` | 4 (komentarz), 16, 648 | `--wiz-accent` kreatora zamówienia |
| `assets/css/asiaauto-single.css` | 4 | `--accent` karty oferty |

**Świadomie NIE ruszone:** `class-asiaauto-admin.php` (panel wtyczki, poza zakresem audytu)
i `class-asiaauto-order-mail.php` (szablony maili transakcyjnych — `#D63031` na białym daje
4,85:1 i kryterium spełnia; zmiana wymaga osobnego testu renderowania w klientach pocztowych).
Nagłówek strony `#9B0000` i logo bez zmian. Komentarz w `class-asiaauto-homepage.php:863`
opisywał wariant wycofany w sierpniu („jako TŁO przycisków zostaje nietknięta #D63031") —
zaktualizowany do stanu faktycznego: jedna czerwień, także jako tło (biel na niej 5,46:1
zamiast 4,85:1).

**Pomiar po wdrożeniu** — ta sama próbka 17 adresów i to samo narzędzie, którego użył reaudyt:

| Pomiar | Wynik |
|---|---|
| axe `color-contrast` na 17 adresach | **0** (przed: 27) |
| axe, wszystkie reguły WCAG A/AA/2.2 AA na 17 adresach | **0 naruszeń** |
| elementy nadal renderowane w `#D63031` | **0** |
| przeskoki nagłówków | brak |
| reflow 320 px: `/marki/`, `/samochody/`, `/w-drodze/`, `/` | 320/320 na każdym |

Kontrast `#C92A2B`: 5,04:1 na `#F5F6F8`, 5,46:1 na białym, 4,99:1 na `#FEF2F2`, biel na kolorze
jako tle 5,46:1. Wszystkie powyżej progu 4,50.

**Wersjonowanie:** `ASIAAUTO_VERSION` `0.34.27` → `0.34.28` (nagłówek wtyczki też),
`PRIMAAUTO_THEME_VERSION` `1.2.5` → `1.2.6`. Arkusze wtyczki bustują się przez `filemtime`.

Podgląd zestawienia przed/po (6 widoków × 2 szerokości):
`https://auratest.pl/fe4f58fec53ctmp/primaauto-kolor-2026-08-26/`

Backupy: `.bak-2026-08-26-kolor` obok każdego z 9 plików + `asiaauto-sync.php`.


## motyw 1.2.5 — 2026-08-26 (a11y: reflow 320 px na /marki/ — pozycja 1 z zamknięcia audytu)

Zmiana **wyłącznie w motywie**, wtyczka bez zmian. Realizacja pozycji 1
z `~/projekty/auranet/docs/uslugi/wdrozenia/2026-08-25-primaauto-wdrozenie-v4.md`
(kryterium 1.4.10, karta N-8).

**Objaw przed zmianą:** `/marki/` przy rzutni 320 px dawało `scrollWidth` 332 — pigułka licznika
przy kaflu o najdłuższej nazwie („Leapmotor”, 193) wypychała się poza ekran. Kolumna `1fr`
w siatce kafla nie miała `min-width: 0`, więc nie schodziła poniżej naturalnej szerokości nazwy.

**Zmiany w `themes/primaauto2026/assets/css/hub.css`:**
- `:154` (nowa reguła) — `.aa-brand-card__name { min-width: 0; overflow-wrap: anywhere; }` — poprawka z karty audytu, strukturalna: kolumna może zejść poniżej szerokości nazwy
- `:260` — siatka mobilna `minmax(140px, 1fr)` → `minmax(165px, 1fr)`
- `:261` — `.aa-brand-card` na mobile: `padding: 14px 16px` → `14px 14px`, dodane `column-gap: 8px`

**Dlaczego dwie ostatnie linie, skoro karta audytu prosiła o jedną.** Sama reguła z karty zamyka
kryterium (320/320, zero elementów wystających), ale przy dwukolumnowej siatce mobilnej nazwy
zaczynały się łamać **w środku wyrazu**: zmierzone `XPen/g`, `Changa/n`, `Xiao/mi`, `Leap/motor`,
`Hong/qi`, `Dongfe/ng` przy 320 px oraz `Leapmoto/r` przy 390 px (najczęstsza szerokość telefonu).
Poszerzenie minimalnej kolumny do 165 px daje na wąskich ekranach jedną kolumnę zamiast dwóch,
a odzyskane 8 px z odstępów mieści najdłuższe nazwy w jednej linii. Po zmianie: **zero złamań
w środku wyrazu na dziewięciu szerokościach** (320, 360, 390, 414, 480, 640, 768, 1024, 1366).

**Wersjonowanie:** `themes/primaauto2026/functions.php:4` — `PRIMAAUTO_THEME_VERSION`
`1.2.4` → `1.2.5` (arkusze motywu mają stałą wersję, bez bumpa przeglądarka podałaby stary CSS).

**Pomiary po wdrożeniu** (puppeteer, rzutnia 320 × 720):

| Adres | `scrollWidth` | elementy wystające |
|---|---|---|
| `/marki/` | 320 | 0 |
| `/samochody/` | 320 | 0 |
| `/w-drodze/` | 320 | 0 |
| `/` | 320 | 0 na poziomie strony |

axe (`wcag2a/2aa/21a/21aa/22aa`) na `/marki/` przy 1366 i 320 px: **zero naruszeń**, łącznie
z `color-contrast`.

**Uwaga do przyszłych pomiarów reflow:** na stronie głównej detektor „elementów wystających”
pokazuje kafle `.aa-home__car`. To karuzela w kontenerze `.aa-home__latest` z `overflow-x: auto`
(`scrollWidth` 1320 przy `clientWidth` 288) — zamierzone przewijanie wewnątrz komponentu, nie
naruszenie 1.4.10. Strona sama w sobie ma 320/320. Sprawdzaj `overflow-x` przodków, zanim
zgłosisz taki wynik jako usterkę.

**Pozycja 2 (czerwień marki) — domknięta tego samego dnia, patrz wpis `0.34.28` wyżej.**

Backup: `hub.css.bak-2026-08-26-a11y-v4`.


## 0.34.27 — 2026-08-25 (a11y: domknięcie zlecenia naprawczego v3 przed reaudytem WCAG)

Realizacja `~/projekty/auranet/docs/uslugi/wdrozenia/2026-08-21-primaauto-wdrozenie-v3.md`.
Pełne podsumowanie z dowodami: `docs/dostepnosc/2026-08-25-PODSUMOWANIE-DO-REAUDYTU.md`.

axe `color-contrast` na 17 adresach próbki audytowej: **97 → 27** elementów, przy czym
wszystkie 27 to wyłącznie para `#d63031` / `#f5f6f8` objęta odstępstwem właściciela.

**Kontrast (1.4.3)**
- `themes/primaauto2026/assets/css/hub.css:231` — etykieta „Nowe”: `#fff` → `#1B2A4A` na `#E8AC07` (2,03 → 7,00:1), 18 elementów
- `themes/primaauto2026/assets/css/hub.css:148` — licznik przy marce: `#dc2626` → `#C82333` na `#fef2f2` (4,41 → 5,13:1), 36 elementów
- `themes/primaauto2026/assets/css/hub.css` — nowa `.aa-marki__figcap-model` (`#6E6E6E`, 4,72:1); w treści strony 263572 sześć razy `style="color:#999"` → klasa. `#767676` NIE wystarcza (4,20:1)
- `themes/primaauto2026/assets/css/kb.css:250` — okruszek słownika: `#64748B` → `#5A6779` (4,40 → 5,32:1)
- `plugins/asiaauto-sync/assets/css/asiaauto-inventory.css:407` — przycisk „Zarezerwowane”: usunięte `opacity: .7`, tło `#5C6B7F` → `#68727F` (2,96 → 4,88:1), 10 elementów
- `class-asiaauto-homepage.php:980` — etykieta „Nowe” na stronie głównej: `#4A3800` → `#1B2A4A`; poza zakresem audytu, ujednolicenie z hubami

**Pułapka warta zapamiętania — `opacity` tworzy kolor, którego nie ma w kodzie.** Zgłoszony przez
audytora `#8d97a5` nie występował nigdzie w `wp-content`; powstawał dopiero z `opacity: .7`
nakładającego tło `#5C6B7F` na białą stronę (dokładnie `rgb(141,151,165)`). Biały napis pozostawał
biały, więc przygaszeniu ulegało wyłącznie tło. Grep po kolorze jest tu bezwartościowy jako dowód —
liczy się odczyt z renderowanej strony. To samo dotyczy `mix-blend-mode`, `rgba()` i gradientów.

**Nagłówki (1.3.1 + 2.4.6)**
- `class-asiaauto-inventory.php:1007` — tytuł karty auta `h3` → `h2` na podstronach rezerwacji, po istniejącej fladze `is_subpage` z `executeQuery():638`. Na `/samochody/` zostaje `h3` (stoi pod `h2` „Filtry”). Styl siedzi na `.aa-card__title`, nie na selektorze `h3` — wygląd bez zmian
- `asiaauto-inventory.css:71` — `.aa-inv__sidebar-head { display: none }` usuwało `h2` „Filtry” z drzewa dostępności na desktopie; zastąpione blokiem `@media (min-width: 769px)` z techniką sr-only (`clip-path`, wymiary 1 × 1 px). Karta audytu wskazywała blok mobilny — faktyczna przyczyna była w regule bazowej

**Etykiety pól (3.3.2)**
- `class-asiaauto-inventory.php:793` — `aria-label="Szukaj w filtrze: {taksonomia}"` na polach wyszukiwania w rozwijanych filtrach; wcześniej nazwa dostępna pochodziła wyłącznie z `placeholder` (technika porażki F82). Dotyczy filtrów „Marka” i „Rodzaj nadwozia” (pole pojawia się dopiero powyżej 8 wartości)

**Wskaźnik fokusu (1.4.11)**
- `themes/primaauto2026/assets/css/base.css:214` — wskaźnik dwubarwny: `outline: 2px solid #fff` + `box-shadow: 0 0 0 4px #1B2A4A`. Stały `--c-accent` dawał 1,81:1 na nagłówku `#9B0000` i 2,93:1 na skip-linku. Po zmianie 8,77:1 i 14,22:1
- To samo w `asiaauto-inventory.css:692` i `asiaauto-order-wizard.css:1079` — nadpisywały wskaźnik przez `!important`; oba tła jasne, więc kryterium przechodziły, powód zmiany wyłącznie spójnościowy

**Wersjonowanie**
- `themes/primaauto2026/functions.php:4` — `PRIMAAUTO_THEME_VERSION` `1.2.3` → `1.2.4` (arkusze motywu mają stałą wersję; arkusze wtyczki bustują się przez `filemtime` i nie wymagały nic)

**Znalezione, świadomie NIE naprawione:** reflow 320 px na `/marki/` — `scrollWidth` 332,
kafel „Leapmotor” z licznikiem `193` wypycha pigułkę, bo `.aa-brand-card__name` nie ma `min-width: 0`.
Test A/B z arkuszami sprzed wdrożenia daje identyczny wynik, więc to nie regresja. Poprawka
(`min-width: 0; overflow-wrap: anywhere`) sprawdzona w przeglądarce, czeka na decyzję właściciela.

Backupy: `.bak-2026-08-25-a11y-v3` obok każdego pliku oraz w `~/backups/primaauto/2026-08-25/`
(w tym zrzut strony 263572 sprzed zmiany treści).

## 0.34.26 — 2026-08-24 (tryb „tylko aktualizacja" per źródło — przełącznik w panelu)

**Po co.** Gdy oba feedy (dongchedi, che168) działają, nie potrzebujemy przyrostu z obu naraz —
kosztuje transfer, wywołania API i pulę Indexing. Ale wyłączenie źródła przyciskiem w panelu
gasiło **wszystko**, łącznie z aktualizacją cen i wycofywaniem sztuk zdjętych u źródła. Skutek
byłby taki, jak przy dongchedi w lipcu: zapas zostaje na stronie jako oferty aut już sprzedanych.

**Co się zmieniło.** Nic w mechanice synca — tryb `verify` istnieje od 0.34.x (T-222, 30.07)
i robi dokładnie to, co trzeba (`added` → skip, `changed` na znanej ofercie → aktualizacja,
`removed` → `markRemoved()`). Brakowało wyłącznie sposobu, żeby go ustawić inaczej niż
`wp option update` z konsoli. Panel dostał więc trzeci stan.

**Zmiany.**

1. `class-asiaauto-admin.php` — handler `sync_source_toggle` przyjmuje `sync_source_mode`
   ∈ `{full, verify, off}` zamiast boolowego `source_enabled`; mapuje trzy stany UI na dwie
   istniejące opcje: `full` → `enabled=1 mode=full`, `verify` → `enabled=1 mode=verify`,
   `off` → `enabled=0` (`mode` zostaje nietknięty, żeby powrót wracał do poprzedniego trybu).
   Wartości spoza listy odrzucane przed zapisem.
2. `class-asiaauto-admin.php` — kafelek „Źródła" w zakładce Konfiguracja: `<select>` z trzema
   opcjami + „zapisz", pod spodem opis skutku („bez nowych ofert; ceny i wycofywanie działają").

**Czego nie ruszono.** `class-asiaauto-sync.php` — bez zmian. Kursor `asiaauto_last_change_id_{source}`
biegnie także w trybie `verify`, więc powrót na `full` **nie ciąga zaległości** — wchodzą wyłącznie
zdarzenia od chwili przełączenia (sprawdzone przy powrocie dongchedi 16.08). Ręczny import
(menu che168 / „Dodaj z Dongchedi") tych flag nie dotyczy — pozostaje dostępny w każdym trybie.

**Weryfikacja.** `php -l` na obu plikach. Smoke test przez `handleSave()` na źródle che168:
`verify` → `enabled=1 mode=verify`, `isVerifyOnly=true`; `off` → `enabled=0`, `isEnabledForSource=false`;
`full` → `enabled=1 mode=full`; wartość spoza listy nie zmienia opcji. Render kafelka sprawdzony
przez `renderStatus()` — trzy opcje w select, zaznaczony stan bieżący.

## 0.34.25 — 2026-08-24 (che168: pole `complectation` z API dublowało tytuły)

**Objaw.** Od 19.08 tytuły nowych ofert che168 niosły nazwę pojazdu dwa razy:
`Xiaomi SU7 Ultra 2025 Xiaomi SU7 Ultra 2025 Ultra`, `Geely Galaxy L7 2024 Geely Galaxy L7 2023
1.5T 115km MAX`. Zasięg: **1256 ofert (1195 publish + 61 draft), 34 marki**. Slugi nietknięte —
`post_name` nie zawiera `complectation`, więc adresy i indeks zostały bez zmian.

**Przyczyna — po stronie auto-api, nie u nas.** Adapter che168 opierał się na założeniu z lipca,
że źródło nie podaje pola `complectation` (podaje je dongchedi), więc sam wycinał wersję z
`param_93` — część po `款`. 18/19.08 auto-api zaczęło pole wypełniać **pełną nazwą pojazdu**,
niekonsekwentnie CN/EN. Guard `if (empty($data['complectation']) && …)` przestał wchodzić i cała
nazwa szła do tytułu obok marki, serii i rocznika.

Dowód rozstrzygający: oferta 421414 (inner_id 59273159) zaimportowana 18.08 poprawnie ma
`_asiaauto_complectation_original = 曜黑版 70kWh 五座` — sam trim, zapisywany wyłącznie wtedy, gdy
pole z API było puste. Dziś to samo API dla tego samego rekordu zwraca
`极氪9X 2026款 曜黑版 70kWh 五座`. Pliki decydujące o tytule (`class-asiaauto-che168-adapter.php`,
`class-asiaauto-importer.php`) mają mtime 2026-07-27 — nietknięte. Zmiana z 18.08 (v0.34.23)
dotyczyła wyłącznie guarda mapowania na dongchedi; dla che168 kod jest logicznie identyczny.

**Zmiany.**

1. `class-asiaauto-che168-adapter.php` — `param_93` ma teraz **pierwszeństwo** nad polem z API;
   pole zostaje wyłącznie jako fallback (brak `param_93` / brak `款` / pusty trim).
2. Ten sam plik — cięcie po znaczniku **rocznika** (`2025款`) zamiast po ostatnim `款`:
   `豪越L 2025款 2.0T DCT尊享款` ma dwa `款`, więc `end(explode())` dawał pusty trim i wersja
   przepadała (2 oferty Geely Haoyue L).
3. `data/che168-model-map.php` + `data/brand-mapping-v6.1.php` — dopisana para `Galaxy|银河M9`
   → `Geely / Galaxy M9` (slug `m9`). Bez niej ręczny import 22.08 założył term make `Galaxy`
   (7234) i serie `银河M9` (7235). auto-api podaje `mark='Galaxy'` dla całej submarki.
4. `scripts/napraw-tytuly-che168-2026-08-24.php` — batch naprawczy. Odtwarza wersję z `param_93`
   i wymienia **wyłącznie ogon** tytułu; prefiks (marka, seria, rocznik) i `post_name` nietknięte.
   Wynik: 1256 naprawionych, 0 z CJK po naprawie. Backup: `~/backups/primaauto/2026-08-24/`.
5. Oferta 440759 (Geely Galaxy M9, import ręczny) przepięta z serii **AITO M9** (5304, parent
   AITO) na **Galaxy M9** (6550, parent Geely) — siedziała w cudzym hubie.

**Domknięcie tego samego dnia (druga tura).**

6. Skasowane puste termy-śmieci 7234 `Galaxy` (make) i 7235 `银河M9` (serie). Dziecko przed
   rodzicem, żeby WP nie przepiął go na `parent=0`. Po kasacji `/samochody/galaxy/` i
   `/samochody/galaxy/m9-2/` robią **301 na hub Geely** — wcześniejsze „HTTP 200" było
   wynikiem podążenia za przekierowaniem, nie żywym pustym hubem.
7. `data/translations-complectations.php` — dopisane `进阶` → `Advanced` oraz
   `易三方闪充性能` → `Tri-Motor Flash Charge Performance`. Frazę Denzy trzeba było zapisać
   **bez końcowego `型`**: wcześniejszy wpis `'型' => ''` zjada je w trakcie iteracji
   `str_replace`, więc wariant z `型` nigdy by nie trafił. Wersje tłumaczyły się dotąd do
   pustego łańcucha i oferta zostawała z pełną nazwą w tytule (432040, 442620 — naprawione).
8. `#421846` — duplikat `900 2025` siedział w ŚRODKU tytułu, bo Ruslan dopisał na końcu
   „NOWY różne kolory + hak". Wycięty wyłącznie powielony fragment, dopisek zachowany.
9. `#410869` (BYD, draft) przepięty z serii **AITO M9** (5304) na **Xia Summer** (3742) +
   tytuł `BYD M9` → `BYD Xia Summer`. Znany przypadek: che168 nazywa BYD 夏 dealersko
   `比亚迪M9`, override dopisany 10.08 — ta oferta weszła 10.08 o 11:01, tuż przed nim.
   Hub AITO M9 nie zawiera już obcych marek.

`#390837` i `#439214` zostawione świadomie — mają poprawne tytuły (drugi edytowany ręcznie).

**Trzecia tura — `性能` (Performance) gubione po cichu.** Mapa znała tylko `高性能`
i `巅峰性能`, więc samo `性能` wypadało: `领克08 EM-P 2026款 210四驱性能Ultra H7` dawało
`Lynk & Co 08 EM-P 2026 210 4WD Ultra H7`. Nic tego nie sygnalizowało — guard usuwający
resztkowe CJK wycina znak i tytuł wygląda poprawnie.

Przed decyzją zmierzona symulacja obu wersji mapy na 2353 ofertach z `param_93`:
**11 wyników innych, 2342 bez zmian**, każda zmiana wyłącznie dodaje zgubione słowo.
Zero regresji, bo `高性能` (poz. 36), `巅峰性能` (230) i `易三方闪充性能` (245) stoją
w iteracji przed nowym wpisem (247).

10. `data/translations-complectations.php` — dopisane `性能` → `Performance` wraz
    z komentarzem o kolejności: to wpis **ogólny**, przechwytuje każde złożenie stojące
    po nim, więc przyszłe wersje szczegółowe (`极致性能` itp.) trzeba wstawiać POWYŻEJ.
    Zmierzone: `极致性能版` dopisane po `性能` daje samo `Performance` (przedrostek zjedzony),
    dopisane przed — `Ultimate Performance`. Ta sama pułapka co przy `型` w drugiej turze.
11. `scripts/przelicz-tytuly-po-mapie.php` — **nowe narzędzie**. Przelicza `complectation`
    z `param_93` i synchronizuje ogon tytułu po każdej zmianie mapy tłumaczeń; bez niego
    nowe wpisy działają wyłącznie na ofertach importowanych od tego momentu, a te już
    w bazie zostają ze starym tekstem. Przyjmuje filtr znaku CJK i tryb `zapisz`.
    Przebieg z filtrem `性能`: 26 zbadanych, **9 tytułów poprawionych**, 15 bez zmian,
    2 pominięte (tytuły edytowane ręcznie, już zawierały „Performance").
    Kontrola po zapisie: 18 ofert z „High Performance" nietkniętych.

**Wniosek na przyszłość.** Adapter ufał cudzemu API bez asercji — założenie o kształcie odpowiedzi
było zapisane jako `empty()`, bez logu i alarmu, gdy przestanie być prawdziwe. Wada rozeszła się na
cały zaciąg przez 5 dni, wykryta okiem, nie przez system.

## 0.34.24 — 2026-08-21 (tłumaczenie opisów: wyłączony thinking Gemini)

**Objaw.** Część opisów ofert wyświetlała się po chińsku, znacznie większa część była
urwana w połowie zdania („Shunxiang: 200 starannie wyselekcjonowanych" i koniec).
Zgłoszone jako podejrzenie niezapłaconego Google Cloud — API działało poprawnie, HTTP 200.

**Przyczyna.** `translateViaGemini()` wołał `gemini-2.5-flash` z `maxOutputTokens: 2048`
i bez `thinkingConfig`. Gemini 2.5 wlicza tokeny „myślenia" do tego samego limitu.
Odtworzone wywołanie na opisie oferty #430197 (Geely Galaxy E8):

```
finishReason: MAX_TOKENS
thoughtsTokenCount: 1743   <- z limitu 2048
candidatesTokenCount: 301  <- tyle zostalo na tlumaczenie
```

Gdy myślenie zjadło cały budżet, odpowiedź wracała pusta → plugin szedł na fallback DeepL →
klucz DeepL był zakomentowany w `wp-config.php` → zapisywany był surowy chiński.
Gdy zostawiło resztkę — zapisywane było tłumaczenie ucięte w pół słowa.

**To nie była regresja.** Objaw jest w danych od pierwszego pełnego miesiąca importu,
w stabilnej proporcji — nie było momentu, w którym coś przestało działać:

| miesiąc | ofert | opis po chińsku | opis twardo ucięty |
|---|---|---|---|
| 2026-04 | 153 | 8 (5%) | 20 (13%) |
| 2026-05 | 273 | 12 (4%) | 80 (29%) |
| 2026-06 | 386 | 24 (6%) | 75 (19%) |
| 2026-07 | 437 | 21 (5%) | 92 (21%) |
| 2026-08 | 1730 | 141 (8%) | 195 (11%) |

Przechodziło niezauważone, bo karta na `/samochody/` nie renderuje opisu — widać go
dopiero po wejściu w pojedynczą ofertę, a przy rotacji 48 h większość takich sztuk
wylatuje, zanim ktokolwiek na nią trafi.

**Pomiar alternatyw** (9 losowych opisów, ten sam zestaw, pełne teksty w raporcie
`primaauto-porownanie-tlumaczen-2026-08-21.md`) — sprawdzone, czy zamiast LLM nie wystarczy
zwykły tłumacz maszynowy:

| silnik | koszt/mies (5400 opisów) | czas/opis | jakość |
|---|---|---|---|
| Gemini 2.5 Flash bez thinkingu | $3,27 | 1,8 s | kontekst motoryzacyjny OK |
| Gemini 2.5 Flash z thinkingiem | $27,26 | 8,9 s | jw., ale gubi tagi `<br/>` |
| Google Cloud Translation | $9,72 | 1,4 s | błędy faktograficzne |
| DeepL Free | $0 (limit 500k zn. = 51% wolumenu) | — | jw. |

Google Translate odpadł nie na cenie (choć jest 3× droższy — rozlicza znaki, nie tokeny),
tylko na jakości: `岚图梦想家` → „Lantu Dreamer" zamiast „Voyah Dreamer" (u nas marka to Voyah),
`黑外米内` → „czarne nadwozie i wnętrze" zamiast „czarny lakier, beżowe wnętrze",
`买下24款` → „Kupiono 24 modele" zamiast rocznika 2024, a przy `„查博士"` urwał zdanie
i zgubił cały akapit o ratach. DeepL popełnia ten sam błąd na kolorach.

**Zmiana** (`class-asiaauto-translator.php`):
- `generationConfig`: `maxOutputTokens` 2048 → 4096, dodane `thinkingConfig.thinkingBudget = 0`
- nowy guard: `finishReason !== 'STOP'` odrzuca odpowiedź do fallbacku zamiast zapisywać ucięty tekst
- `wp-config.php`: odkomentowany `ASIAAUTO_DEEPL_API_KEY` (fallback, zużyte 11 z 500 000 znaków limitu)

**Smoke test** — trzy oferty, które wcześniej poszły po chińsku lub ucięte:

| oferta | CN | wynik | czas |
|---|---|---|---|
| #430197 | 413 zn. | OK, 1142 zn. | 2,2 s |
| #430213 | 97 zn. | OK, 338 zn. | 1,3 s |
| #430249 | 404 zn. | OK, 1434 zn. | 2,7 s |

Kompletność zweryfikowana przez porównanie końcówek z oryginałem, tagi `<br/>` zachowane.

**Retranslacja zaległości.** Napisany `diag/retranslate-broken-descriptions.php`
(kopia w repo: `scripts/`) — stary `diag/retranslate-descriptions.php` nie nadaje się,
pochodzi z v0.11 i wpisuje w `post_content` tablicę zwracaną przez `translateDescription()`
zamiast pola `translated`. Kryteria uszkodzenia: ≥3 znaki CJK, PL/CN < 1.8, albo końcówka
na przecinku/średniku którego nie ma w oryginale (to trzecie kryterium wymagało poprawki
po partii próbnej — chińskie opisy nagminnie kończą się na `；`, więc wierne tłumaczenie
dziedziczy ten znak i było odrzucane jako ucięte).

Bieg 2026-08-21: **162 najnowsze oferty naprawione** (~$0,18), **816 zostawionych świadomie**
— decyzja Janka: przy rotacji ofert (48 h draft → 7 dni trash) starsze i tak schodzą z serwisu,
a koszt dokończenia (~$0,90) nie równoważy braku efektu. Skrypt zostaje, gdyby wrócić:
`APPLY=1 LIMIT=100 wp eval-file …` bierze od najnowszych.

**Świadomie nie robimy:** detektora liczącego dziennie świeże oferty z opisem CJK lub uciętym
(propozycja odrzucona 2026-08-21).


## 0.34.23 — 2026-08-18 (guard mapowania rozszerzony na dongchedi)

**To nie jest nowa funkcja, tylko otwarcie bramy przed istniejącym mechanizmem.**
`isMappedForImport()` powstał w T-186 (22.07) i od początku był w środku źródło-agnostyczny —
bierze `mark`/`model`, pyta `AsiaAuto_Mapping::getEuForCn()`, przy pudle loguje parę do kolejki
domapowań. Wołany był jednak wyłącznie za warunkiem `$source === 'che168'`, więc drugi kanał
szedł obok niego bez kontroli.

**Objaw.** Po wznowieniu importu dongchedi w trybie `full` (16.08) do bazy weszły oferty
z chińskimi nazwami w tytule i w nazwie termu `serie` — `Voyah|岚图泰山 PHEV`, `Maextro|智界V9`,
`NIO|蔚来ES9` — z kalekimi slugami hubów (`phev`, `v9`, `es9`), oraz modele spalinowe spoza
segmentu (`Nissan|Pathfinder`, `Audi A4L`, `Mazda 3 Axela`). Che168 tego nie przepuszczał ani razu.

**Pomiar przed zmianą** (read-only, 2400 zdarzeń strumienia dongchedi): 911 `added`, filtr konfigu
przepuszcza 17, z tego guard odrzuca 3 (dokładnie te pary co wyżej) i przepuszcza 14 zmapowanych.
Strata legalnego zaciągu: zero.

**Zmiana** (`class-asiaauto-sync.php`): `normalizeForSource()` woła guard także dla dongchedi, ale
**tylko gdy zdarzenie niesie `mark` i `model`**. Ten warunek jest konieczny, nie kosmetyczny:
`changed` w dongchedi niesie wyłącznie `{new_price}` (480/480 zdarzeń w pomiarze), więc
bezwarunkowy guard zablokowałby każdą aktualizację ceny w magazynie. `isMappedForImport()` dostał
opcjonalny parametr `$source` (domyślnie `che168`, więc istniejące wywołanie bez zmian) — po to,
by log mówił, z którego kanału poszedł skip.

**Regresja che168: zero** — ścieżka che168 zachowana co do znaku (adapter → guard), potwierdzona
testem na dwóch scenariuszach. Smoke test guarda dongchedi: 5/5 (zmapowana przechodzi, trzy
niezmapowane odrzucone, `changed` z samym `new_price` przechodzi).

**Nie objęte tą wersją:** pre-filtr przed `getOffer()` (`class-asiaauto-sync.php:333`) nadal jest
che168-only, choć `/changes` dongchedi ma komplet pól filtra — dziś dongchedi woła `getOffer()`
dla wszystkich 911 `added`, żeby przyjąć 17. Osobna decyzja, wyłącznie wydajność.

Pliki: `includes/class-asiaauto-sync.php` (backup: `.bak-2026-08-18-guard-dongchedi`).


## 0.34.22 — 2026-08-06 (T-220: aneks VIN do umowy)

Druga strona sprawy zamkniętej w 0.34.21. Tamta wersja pozwoliła **usunąć** błędny numer przed
podpisaniem; ta pozwala **dopisać** prawdziwy po weryfikacji auta — aneksem do już podpisanej
umowy, bez ruszania samej umowy.

Powód wagi tematu: **99,2 % ofert dongchedi nie ma VIN-u w ogłoszeniu** (1477 z 1489, pomiar
03.08). Umowa podpisywana przed przyjazdem auta z reguły nie zna numeru, więc aneks to nie
wyjątek, tylko normalny krok obiegu.

**Generator** (`class-asiaauto-contract.php`): nowa metoda `generateAnnex($order_id, ['vin'],
$contract_date)` obok `generate()` — jeden szablon dla obu wzorców (D1), różnicują się tytułem
i odwołaniem do jednostki redakcyjnej z mapy `ANNEX_FIELDS` (pośrednictwo → `§1, ust.1, pkt.1
Umowy podpunkt c`; leasing → `§2 Umowy lit. e oraz w Załączniku nr 1`). Jedna strona A4 w szacie
umowy, dwa podpisy, stopka „Aneks do umowy nr …". Mapa pól jest osią rozszerzalności: dołożenie
ceny czy terminu = jeden wpis, szablon i tak iteruje po liście pozycji.

**Aneks nie ma własnej numeracji** (D8) — identyfikuje się umową: „aneks do umowy nr AA/2026/0031
z dnia 30.07.2026". Data umowy dotąd nigdzie nie była zapisywana (`collectData()` stempluje
`date('d.m.Y')` przy renderze), więc doszło `_order_contract_date` + `resolveContractDate()`:
zapisana data → `post_date` pliku PDF → dziś, z możliwością ręcznej korekty przed generowaniem
(potrzebne dla 6 umów wgranych ręcznie, gdzie data pliku ≠ data umowy).

**Generowanie nie wysyła żadnych maili** — wymóg nadrzędny. Mail z PDF-em w załączniku
(szablon `annex_vin_sent`, edytowalny w Ustawieniach) wychodzi wyłącznie przy świadomym
przełączeniu znacznika `_order_annex_vin_state` na „wysłany", z potwierdzeniem w oknie
dialogowym. Ponowny zapis tej samej wartości nie wysyła drugiego maila; regeneracja cofa
znacznik na „wygenerowany", bo klient nie ma jeszcze tego dokumentu.

**Guard (D3):** umowa główna istnieje, ma numer, a `_order_vin` przechodzi `isValidVin()`
(17 znaków bez I/O/Q). Nie „VIN niepusty" — w bazie leżą numery ucięte przez che168 i jeden
(`L1NB`) już trafił do zamówienia. Guard nie przechodzi → przycisk nieaktywny z podanym powodem.

**Statusy zamówienia nietknięte** (`STATUSES`, `TRANSITIONS`, `LISTING_RESERVATION_MAP`,
`changeStatus()`), kreator klienta i blok ręcznego wgrywania umowy również. Aneks żyje w osobnym
polu obok statusu, więc auto „w drodze" nie znika z filtra.

**D6 — umowa pośrednictwa bez VIN-u zapowiada aneks.** Zamiast myślnika w `§1 ust.1 pkt 1 lit. c`
i w wierszu „VIN" Załącznika nr 1 drukuje się „zostanie uzupełniony w formie aneksu do niniejszej
Umowy". Przy okazji ogryzek numeru z che168 (`LC0EJ5…`) na ogłoszeniu przestał wchodzić do umowy
jako „VIN" — traktowany jest jak brak. Numer **wpisany ręcznie** w zamówieniu drukuje się
dokładnie tak, jak go wpisano (guard tego pola pozostaje otwarty — T-217 §6).

Testy: `tmp/T-220-testy-aneks.php` — **62/62** (generator, guard, maile z licznikiem na
`pre_wp_mail`, D6, brak wpływu na statusy i rezerwacje, weryfikacja sprzątania). Regresje:
`tmp/T-217-regresja-posrednictwo.php` — 5 umów, `pdftotext` + `diff` **bez różnic** przed i po;
`tmp/T-217-testy-leasing.php` — 42/42; `tmp/T-217-e2e-status-i-zalacznik.php` — 22/22;
`tmp/test-vin-guard-2026-08-06.php` — 15/15. W zestawie T-217 poprawione 5 przestarzałych
asercji (T-20 zakładało nieistniejący już fallback VIN-u z oferty — zabity przez T-242;
T-30/T-31/T-04 liczyły numery UL od zera, a są już dwie produkcyjne umowy leasingowe).

## 0.34.21 — 2026-08-06 (VIN w umowie leasingowej respektuje edycję w zamówieniu)

Zgłoszenie Ruslana z rozmowy 06.08: wyczyścił VIN w zamówieniu, a numer dalej drukował się
w umowie i wracał przy każdej regeneracji. Jedyne, co działało, to skasowanie VIN-u na samym
ogłoszeniu — a to zła droga, bo psuje dane oferty pracującej w reklamach i tak czy tak zostaje
nadpisane przy najbliższej aktualizacji z che168 (`class-asiaauto-importer.php:460-462`).
Obejściem awaryjnym było kasowanie i zakładanie zamówienia od nowa, co wysyła klientowi maile
o nieistniejącym zamówieniu.

Przyczyna: `getVehicleData()` liczyło `vin_verified` (wariant leasingowy) z fallbackiem
`_order_vin` → `vin_number` ogłoszenia → `'—'`. Puste pole zamówienia oznaczało więc „weź
z oferty", a nie „brak numeru", i edycja w karcie zamówienia nie miała jak zadziałać.

**Umowa leasingowa czyta teraz VIN wyłącznie z pola karty zamówienia.** Wpisany numer się
drukuje, puste pole daje klauzulę wzorca „zostanie dodany aneksem do umowy po weryfikacji auta"
(§2 lit. e + wiersz „VIN" w Załączniku nr 1). Guard formatu (17 znaków bez I/O/Q) bez zmian —
zamaskowany numer z che168 nadal nie przechodzi.

**Umowa pośrednictwa nietknięta** — klucz `vin` zachowuje stary fallback na ogłoszenie, więc
150 istniejących zamówień renderuje się identycznie. Otwarte i świadomie odłożone: pośrednictwo
dalej drukuje zamaskowane VIN-y z che168 (decyzja D4 z T-217).

**Karta zamówienia** (`class-asiaauto-order-admin.php`): pole VIN przestało wypełniać się samo
numerem z oferty — wcześniej dowolny zapis karty (choćby zmiana prowizji) po cichu utrwalał go
w `_order_vin`, bez decyzji Ruslana i bez weryfikacji auta. W zamian pod polem stoi stały pasek
**„VIN OFERTY: …"** z przyciskiem **Wstaw** i notą, że numer pochodzi od chińskiego sprzedającego
i bywa zamaskowany albo nieprawdziwy. Ruslan widzi numer bez szukania, wkleja jednym kliknięciem
albo zostawia puste.

Test: `tmp/test-vin-guard-2026-08-06.php` (read-only, Reflection, bez PDF i bez zapisu do bazy) —
15 asercji na prawdziwych zamówieniach UL/2026/0001, AA/2026/0030 i #407317. Uruchamiać przy
każdej następnej zmianie w generatorze umów, razem z zestawem T-217.

## 0.34.20 — 2026-08-06 (promocja Terenwizja na stronie głównej)

Pasek promocyjny pod hero strony głównej: szkolenie off-road z Terenwizją i film pamiątkowy
na kanale za sprowadzenie terenowego SUV-a 4x4, którego nie ma jeszcze nikt w Polsce
(propozycja Ruslana z 2026-08-05). Nowa metoda `renderPromo()` w `class-asiaauto-homepage.php`,
wywołana między `renderHero()` a `renderLead()` — zmiana addytywna, bez dotykania istniejących
sekcji. Klik prowadzi na `/samochody/?nadwozie=suv&naped=awd` (670 ofert).

Pasek stoi **pod** akapitem answer-first, nie nad nim. Pierwsza wersja wstawiała go między H1
a akapit — GSC (07.07–03.08) pokazuje, że home ma 634 kliknięcia (6,4% witryny) przy średniej
pozycji 4,9, ale head-termy niebrandowe siedzą nisko: `auta z chin` poz. 8,3, `import aut z chin`
7,7, `import samochodów z chin` 8,8. Wpychanie tekstu o szkoleniu off-road przed akapit pisany
pod te frazy rozmywało otwarcie tematyczne, więc kolejność odwrócona.

Doszła sekcja `renderTerenwizja()` (po „Co zawiera cena importu", przed FAQ) — dowód społeczny:
test Leoparda 5 na kanale TERENWIZJA (394 tys. subskrybentów, 249 tys. wyświetleń), auto z tego
materiału kupione w Prima-Auto, co właściciel mówi w samym filmie. Miniatura to statyczny WebP
w `uploads/asiaauto/byd-leopard-5-prima-auto-test-terenwizja.webp` linkujący do YouTube —
świadomie BEZ iframe (skrypty zewnętrzne, zgoda w Complianz, CWV). Sekcja ma jeden przycisk,
prowadzący do ofert; osobne wezwanie „Obejrzyj test na YouTube" wycięto, żeby najcenniejsza
strona wejścia nie oferowała wyjścia z serwisu jako równorzędnej akcji. Odnośniki do Terenwizji
mają `rel="sponsored noopener"` — to układ partnerski (szkolenie i film za ekspozycję).

Dane materiału trzymają stałe `TW_*` w klasie: przy nowym filmie (zapowiadany na 2026-08-10,
chińskie SUV-y + reklama Prima-Auto) podmieniamy identyfikator, tytuł, miniaturę i liczby.

**A11y — pułapka specyficzności (zaliczona i naprawiona 2026-08-06).** Reguła `.aa-home a
{ color: inherit }` (klasa + typ) bije selektor jednoklasowy, więc `color:#fff` na przyciskach
`<a>` nie wchodziło i tekst dziedziczył `--txt`: **kontrast 2,47:1** przy wymaganych 4,5:1.
Przycisk wyszukiwarki obok trzymał 4,85:1 tylko dlatego, że jest `<button>`, nie `<a>`.
Naprawa: selektory `.aa-home a.aa-home__promo-cta` / `.aa-home a.aa-home__tw-cta` — z tym, że
podbicie bazy wymusiło **identyczne podbicie w media query desktop**, bo inaczej `width:100%`
z wersji mobilnej rozpychało przycisk i ściskało tekst do jednego słowa w linii. Dodatkowo
`tw-stats` przeszło z `--sec` (3,88:1 przy 11,5 px) na `#566577` (5,75:1). Zmierzone na żywej
stronie: 4,85 / 4,85 / 5,75 — wszystko AA.

Przy okazji pomiaru wyszły **zastane** braki kontrastu poza zakresem tej zmiany:
`.aa-home__section-sub` i `.aa-home__stat-label` mają 3,71:1 (kolor `--sec` na jasnym tle).
Nie ruszane — osobny temat dla całej strony głównej.

Wyłącznik bez wchodzenia w kod: `wp option update aa_promo_terenwizja 0` (domyślnie włączone).

Druga propozycja Ruslana — „z hasłem Terenwizja ładowarka i komplet filtrów gratis" — świadomie
**bez zmian w kodzie**: oba są już standardowo w cenie (`renderIncludedInCif()`, kolumna „W cenie"
w karcie oferty, shortcode `[asiaauto_included]`), więc hasło działa wyłącznie jako źródło leada
przy rozmowie, nie jako osobna obietnica na stronie.

## 0.34.19 — 2026-08-04 (T-237: kontaminacja hubów + scalenie duplikatu LS9)

Dwa auta Geely siedziały na seriach należących do innych marek: „Galaxy M9" na termie `M9`
(AITO), „Geely Galaxy L7" na termie `L7` (Li Auto). Marka była poprawna, seria nie — auta
Geely wyświetlały się na cudzych hubach i **zaniżały tam cenę „od"**: AITO M9 pokazywał
224 000 zł zamiast 312 000, Li Auto L7 — 99 000 zamiast 211 000. Przepięte na `Galaxy M9`
(6550) i `Galaxy L7` (7153), liczniki i tytuły przeliczone.

Duplikat serii scalony: ten sam model stał na dwóch indeksowalnych hubach — `LS9` (2 szt.)
i `IM LS9` (1 szt.), kanibalizując frazę „im ls9" (90/mc) i pokazując mniejszą podaż niż
realna. Po scaleniu jeden hub z 3 sztukami; `im-ls9` → `ls9` przez `V62_SERIE_REDIRECTS`
(opcja `_asiaauto_redirects` w bazie okazała się martwa — plugin jej nie czyta).

Nie ruszane, bo to nie duplikaty: `Smart #5` (elektryk) i `Smart #5 EHD Super Hybrid`
(hybryda z range extenderem) to różne auta. `LS8` — dziś jeden term `IM LS8`, ale mapowanie
produkuje nazwę `LS8`, więc przy kolejnym imporcie powstanie drugi term; do poprawy w mapie,
zanim się wydarzy.

## 0.34.18 — 2026-08-04 (T-226: identyfikacja pojazdu w danych podstawowych)

Sekcja „Dane podstawowe" na ofercie otwierała się rokiem modelowym i od razu wymiarami —
nie było odpowiedzi na pytanie, czym to auto właściwie jest. Dodane na początek: Marka,
Model, Wersja, Rok modelowy, Kolor nadwozia, Kolor wnętrza.

Źródła: taksonomie (`make`, `serie`, `ca-year`, `exterior-color`, `interior-color`) + meta
`_asiaauto_complectation`, czyli poza `extra_prep` — dane kompletne dla obu źródeł (dongchedi
i che168) i niezależne od uszkodzonych escapów unicode. Wstawka przez `array_splice` obok
istniejącego bloku roku modelowego, zero zmian w pętli budującej wiersze z extra_prep.

Marki i modelu świadomie NIE linkujemy — mają już po ~4 linki na stronie (breadcrumb,
„Wszystkie oferty", inne egzemplarze, CTA katalogu). Linkowanie nadwozia/paliwa/napędu
do katalogu z filtrem to osobny task (T-227).

## 0.34.17 — 2026-08-04 (T-227b: priorytet niszy w autolinkerze)

Autolinker rozstrzyga konkurencję o ograniczone sloty (3 w specyfikacji, 3 w wyposażeniu)
globalnie, a nie kolejnością komórek tabeli. Nowy przebieg `pickWinners()`: skleja segmenty
tekstowe spoza SKIP_TAGS, szuka pierwszego wystąpienia każdego aliasu i sortuje po
(priorytet, pozycja). Dopiero zwycięzcy trafiają do podmiany — przebieg 2 iteruje po ~3
aliasach zamiast 271, więc całość jest tańsza niż poprzednia wersja (1,94 ms na tabelę
25,8 tys. znaków, <1% TTFB 0,26 s).

Priorytet: 1 = specyfika chińska/EV (bateria, naped, adas + CLTC, NEV), 2 = kokpit/audio/
normy, 3 = uniwersalna motoryzacja (podwozie, nadwozie, turbo, wtrysk, rok modelowy).
Mapowanie po `_wiki_category` + lista wyjątków, więc nowe hasło dziedziczy priorytet kategorii.

Powód: pomiar na 12 ofertach pokazał, że 33 z 72 slotów zajmowała uniwersalna motoryzacja
(McPherson, hamulce, turbo), a tylko 13 specyfika chińska/EV. Po zmianie 33 / 15 / 21.
Efekt na żywo: `CLTC · LFP · FinDreams` zamiast `Rok modelowy · CLTC · LFP`.

## 0.34.16 — 2026-08-03 (T-219 D: claim leasingowy na ofertach prowadzi na landing)

**Co.** Pasek zaufania pod ceną na każdej ofercie kończył się zdaniem „Możliwy leasing." —
zwykłym tekstem, bez linku. Od tej wersji to link na `/leasing-samochodu-z-chin/`.

### `includes/class-asiaauto-single.php` (strefa krucha, ZAWSZE PYTAJ)
- `trustLine()`, linia 522 — **jedna zmieniona linia**: `<span class="aa-trustline__fin">`
  zamienione na `<a class="aa-trustline__fin" href="/leasing-samochodu-z-chin/">`. Klasa
  zostaje na `<a>`, więc kolor (`--primary`) i `font-weight: 600` bez zmian. Kropka
  **poza** linkiem. Backup: `.bak-2026-08-03-leasing-link`.

### `assets/css/asiaauto-single.css`
- `a.aa-trustline__fin` — `text-decoration: underline` + `text-underline-offset: 2px`,
  na hover bez podkreślenia. **Powód: WCAG 1.4.1** — sam kolor nie może być jedynym
  wyróżnikiem linku, a serwis przeszedł audyt WCAG 2.2 AA 31.07 i nie psujemy tego.
  CSS busta się przez `filemtime`, więc bez ręcznego cache-bustu.

**Zasięg: 2174 oferty publish** — 7× więcej niż blok leasingowy na 317 hubach (T-219 B).
To miejsce, w którym klient patrzy na cenę rzędu 400 tys. i się zastanawia, więc dźwignia
konwersyjna największa z całego T-219 D.

**Weryfikacja.** Diff renderu przed/po na 3 losowych ofertach (AITO M9, Mazda EZ-60,
Leapmotor B01): jedyna różnica to zamiana `<span>` na `<a>` plus nowy `?ver=` przy CSS.
Rozbieżność 24 linii przy Leapmotorze to sekcja podobnych ofert, która rotuje niezależnie
(inne auto, inny przebieg) — nie skutek zmiany. Landing odpowiada 200, reguła CSS obecna
na produkcji.


## 0.34.15 — 2026-07-31 (che168: override'y ślepe na napęd + martwy klucz `by_engine`)

**Objaw.** Oferta `che168:59161281` (`汉L 2025款 DM-p`, plug-in hybryda) wylądowała w hubie
`Han L EV`. Audyt całej bazy (serie z sufiksem napędu vs term `fuel` oferty) wykazał
**2 rozjazdy** i **1 uśpiony** — wszystkie z tej samej przyczyny: che168 trzyma warianty EV
i DM/PHEV pod jedną nazwą modelu, a nasze huby są rozbite po napędzie.

### `data/che168-model-map.php`
- `BYD|汉L` — dodane `by_engine: ['phev' => Han L DM]`. Domyślny zostaje EV (27 ofert, sonda
  widziała 3/3 EV). Łacińskie `Han L` z che168 rozpoznawało napęd samo (algorytm sufiksów,
  krok 3 resolvera) — trafiał tam tylko klucz CJK, który short-circuitował na kroku 0.
- `VOYAH|Dreamer` — dodane `by_engine: ['electric' => Dream EV]`. Komentarz przy wpisie
  zapowiadał to „gdy pojawi się EV Dreamer" — pojawił się (oferta 398795,
  `岚图梦想家 2025款 EV 四驱尊贵鲲鹏版`, `fuel_form=纯电动`).
- `BYD|PLUS New Energy` — klucz `by_engine` poprawiony z `'plug-in hybrid'` na `'phev'`.
  **Wpis z 30.07 (T-222) był martwy**: `normalizeEnums()` w adapterze normalizuje
  `engine_type` PRZED kanonizacją mark/model, więc surowa nazwa che168 nigdy nie docierała
  do porównania. Hub `song-plus-ev` jest dziś czysty tylko dlatego, że oferty przepięto
  ręcznie — przy następnym imporcie DM-i wróciłyby do niego.

### `includes/class-asiaauto-mapping.php`
- Nowa `engineKey()` — normalizacja obu końców porównania `by_engine` (`plug-in hybrid`/`dm-i`/
  `dm` → `phev`, `bev` → `electric`, `range extender`/`reev` → `erev`). Siatka bezpieczeństwa,
  żeby kolejny wpis pisany surową nazwą che168 nie umarł po cichu jak ten z 30.07.
- `resolveChe168()` woła `engineKey()` zamiast `ci()` przy dopasowaniu wariantu. Reszta
  resolvera nietknięta.

**Weryfikacja.** 15 przypadków resolvera (`汉L`/`Han L`/`Dreamer`/`PLUS New Energy`/`Han`/`海豹06`
× electric/phev/brak) — wszystkie zgodne, zero regresji na wpisach z 30.07. Audyt bazy po
zmianie: **0 rozjazdów** napęd vs hub.

**Przepięte oferty** (backup stanu: `tmp/backup-repin-2026-07-31.json`):
- #399083 `Han L EV` → `Han L DM`, tytuł/slug przeliczone (`byd-han-l-dm-2026-399083`, stary URL 301)
- #398795 `Dream PHEV` → `Dream EV`, `voyah-dream-ev-2024-398795`

## 0.34.14 — 2026-07-30 (T-217: drugi wzorzec umowy — wariant leasingowy)

**Co doszło.** Generator PDF umiał jeden wzorzec — pośrednictwo, §1–§9. Doszedł drugi:
„Umowa organizacji importu samochodu" (§1–§16 + Załącznik nr 1), treść 1:1 z podpisanego
egzemplarza **#072426-1** (Agw Moto, 24.07.2026). Kasuje robotę w Wordzie i rozjazd numeracji
— Ruslan obchodził brak wzorca przez „Wgraj własną (zastąpi)".

**Zasada wdrożenia: addytywnie, zero zmian w działających umowach.** Umowa pośrednictwa
i ręczne wgrywanie PDF nietknięte — dowód w testach regresji (niżej).

### `class-asiaauto-order.php`
- Nowe meta: `_order_contract_type` (**brak wartości = pośrednictwo**, więc 150 istniejących
  zamówień bez zmian; celowo NIE `_order_type`, który trzyma `customer`/`stock`),
  `_order_year_production`, `_order_origin_country`, `_order_tech_condition`,
  `_order_leasing_financier`, `_order_leasing_initial_fee`, `_order_leasing_deposit_amount`,
  `_order_leasing_deposit_percent`.
- Nowy config: `contract_prefix_leasing` = `UL`, `bank_account_number_leasing`
  = `72 2490 0005 0000 4530 0075 1603` (Alior, z podpisanej umowy), `leasing_deposit_percent` = 10.
  Świadomie **nowe klucze** — `deposit_percent` liczy depozyt dla wszystkich zamówień
  (dziś 0 + min 6150), a `contract_prefix` trzyma pulę AA.
- Osobna pula numeracji: `CONTRACT_COUNTER_LEASING_PREFIX`, `generateLeasingContractNumber()`,
  `previewNextLeasingContractNumber()`, `generateContractNumberForOrder()` (rozgałęzia wg typu).
  `changeStatus()` woła teraz wariant per-order — zamówienie bez typu idzie do AA jak dotąd.
- `bumpContractCounterFromManual()` rozszerzone o pulę UL (ręcznie wpisany `UL/...`
  przy wgrywaniu własnej umowy podbija właściwy licznik).
- `getLeasingDepositAmount()` — kwota **zamrażana** przy generowaniu PDF; zmiana ceny
  jej nie przelicza (klient ma podpisany dokument na konkretną kwotę).

### `class-asiaauto-contract.php` (1345 → 1928 linii)
- `generate()` — jedno rozgałęzienie na wejściu. Ścieżka pośrednictwa woła
  `renderHTML()` + `renderPDF()` bez zmian; leasing ma własne `renderLeasingHTML()`,
  `renderLeasingAttachment1()`, `renderLeasingPDF()`.
- **Załącznik nr 2 dla leasingu nie powstaje** — wzorzec ma jeden załącznik, koszty
  reguluje trójstronna umowa sprzedaży.
- `collectData()` / `getVehicleData()` rozszerzone **wyłącznie addytywnie** (nowe klucze,
  istniejące bez zmian).
- **Guard VIN — tylko w gałęzi leasingowej.** `isValidVin()` (`^[A-HJ-NPR-Z0-9]{17}$`)
  wypełnia nowy klucz `vin_verified`; wartość niepełna = traktowana jak brak i w umowie
  drukuje się klauzula wzorca o aneksie. che168 maskuje VIN w ogłoszeniu (14 z 267 listingów,
  np. `HACRA0B3XS1S...`). Klucz `vin` nietknięty, więc **umowa pośrednictwa dalej drukuje
  zamaskowaną wartość** — naprawa tam zmienia treść działającego dokumentu, czeka na decyzję.
- Odwzorowanie wzorca: §2 lit. a–d zrekonstruowane (w podpisanym egzemplarzu lista zaczyna się
  od `e)`), ustępy §2 numerowane od 2 — inaczej odesłanie „o którym mowa w ust. 2" wskazywałoby
  samo siebie. Limit odpowiedzialności z §11 ust. 4 (10 000 zł) jako stała, nie parametr.

### `class-asiaauto-order-admin.php`
- Metabox „Dane umowy": selektor wzorca + sekcja leasingowa odsłaniana po jego wybraniu
  (rok produkcji, kraj, stan techniczny, procent i kwota depozytu, Finansujący, opłata wstępna),
  z podpowiedziami z ogłoszenia. Etykieta nowego pola: „Depozyt zabezpieczający — leasing".
- Ustawienia: nowa karta „Umowa leasingowa" (prefix, rachunek, procent) obok istniejących.
- **Ręczne wgrywanie PDF (linie 1766–1881 przed zmianą) nietknięte** — potwierdzone diffem
  całego bloku wobec kopii sprzed wdrożenia. Handler uploadu nie zna typu umowy.

### Testy
- **Regresja pośrednictwa (T-01/T-02/T-24):** 5 zamówień (#390039, #387788, #387071, #362513,
  #360448) renderowanych read-only przed i po wdrożeniu → tekst PDF **identyczny co do znaku**.
  #390039 nadal drukuje zamaskowany VIN = potwierdzenie, że zachowanie się nie zmieniło.
- **Wariant leasingowy:** 42 asercje zaliczone, 0 niezaliczonych (T-04, T-10..T-16, T-20..T-23,
  T-30..T-33, T-40..T-43, T-50..T-53, T-60..T-62) — 11 stron, §1–§16, depozyt 10% = 23 200 zł
  z 232 000, rachunek leasingowy w §4, brak Załącznika nr 2, guard VIN w §2 i załączniku,
  brak sierot `§`, stopka na każdej stronie, kwota zamrożona po zmianie ceny 232→300 tys.
- **T-03:** 0 zamówień z typem leasing w bazie, sekcja leasingowa ukryta, pośrednictwo
  preselektowane, etykieta istniejącego depozytu i wszystkie stare pola bez zmian.
- Liczniki po testach przywrócone: **AA = 28** (następny 0029), **UL = 0** (następny 0001).
  Zero sierot w `uploads/contracts`, zero załączników, zero meta leasingowych na prawdziwych
  zamówieniach. Skrypty: `tmp/T-217-regresja-posrednictwo.php`, `tmp/T-217-testy-leasing.php`.

### Uzupełnienie tego samego dnia — E2E, test w przeglądarce, poprawka kosmetyczna

- **E2E przez prawdziwe API (22/22 zaliczonych),** `tmp/T-217-e2e-status-i-zalacznik.php`:
  `changeStatus('umowa_gotowa')` → numer `UL/2026/0001` z właściwej puli przy nietkniętym
  liczniku AA → cron zaplanowany → `deferredGenerate()` → załącznik `UL-2026-0001-*.pdf`
  (183 KB, 11 stron, tytuł „Umowa UL/2026/0001", parent = zamówienie) → depozyt 23 200 zł
  zamrożony w meta. Kontrolne zamówienie **bez** typu poszło ścieżką pośrednictwa:
  `AA/2026/0029`, §1–§9 + Załącznik nr 2, depozyt 6 150 zł. Stock + leasing bez konfliktu.
  Poczta wyciszona filtrem `pre_wp_mail` — **4 maile zablokowane**, w tym jeden do prawdziwego
  klienta („umowa gotowa do podpisu"). Bez tego filtru klikanie w panelu wysłałoby go na serio.
- **T-63 — pakiet opisywał zachowanie, którego nie ma.** Oczekiwanie „nowy plik, poprzedni
  zostaje w bibliotece mediów" jest błędne: `regenerate()` woła `wp_delete_attachment(force=true)`,
  więc stary plik i załącznik przepadają. Zero wersjonowania — regeneracja po podpisaniu
  = utrata egzemplarza klienta. Zachowanie sprzed T-217, nie regresja; do decyzji osobno.
- **Test w przeglądarce (Chrome MCP):** karta zamówienia i strona Ustawień obejrzane na
  produkcji. Selektor nie zapisuje się sam (reload wraca do „Pośrednictwo"), podpowiedzi
  liczą się z prawdziwych danych, sekcja odsłania się poprawnie. Poprawiona jedna wada
  kosmetyczna: podpowiedź przy kwocie depozytu wychodziła z komórki tabeli
  (`<small>` inline → `<p class="description">` pod polem).
- **Sprzątanie po E2E wywaliło się w połowie** na prywatnej `clearListingReservation()`.
  Doczyszczone ręcznie, produkcja zweryfikowana: 0 zamówień testowych, 0 osieroconych
  załączników i plików, liczniki AA=28/UL=0, 0 meta leasingowych na prawdziwych zamówieniach,
  rezerwacje na listingach nietknięte, ręczny PDF na #390039 na miejscu. Skrypt poprawiony —
  zamiast czyścić rezerwację na oślep sprawdza read-only, czy w ogóle powstała.
- **Paliwo świadomie NIE jest edytowalne na zamówieniu** — i tak zostaje. Wartość idzie z
  taksonomii `fuel` listingu, która **napędza akcyzę** w pipeline cenowym
  (`class-asiaauto-order.php:827-835`: benzyna/diesel 3,1%, hybryda/MHEV 1,55%,
  PHEV/EV/EREV 0%). Override per-umowa rozjechałby dokument z wyliczeniem, które ustaliło cenę —
  w umowie pośrednictwa widać to wprost, bo Załącznik nr 2 drukuje slug paliwa obok akcyzy.
  Błędne paliwo poprawia się na **ogłoszeniu** (term taksonomii), bo tam poprawia się
  jednocześnie treść dokumentu i cena.
  Wystąpienia paliwa: §2 lit. b) (nasza rekonstrukcja — w podpisanym egzemplarzu lista zaczyna
  się od `e)`) oraz wiersz „Paliwo" w Załączniku nr 1 (z podpisanego egzemplarza).

**Poza zakresem (świadomie):** zmiana etykiety istniejącego pola depozytu, korekta końcówki
maili statusowych, guard VIN w umowie pośrednictwa, T-220/T-221/T-121/T-113.

## 0.34.13 — 2026-07-30 (T-222: override che168 świadomy napędu — `by_engine`)

**Objaw** (zgłoszenie Janka): `/oferta/byd-song-plus-ev-2025-396835/` — oferta z wersją **DM-i**
(`fuel=phev`) wylądowała w hubie **EV**.

**Przyczyna — ten sam short-circuit co casus N8L z 27.07.** `resolveChe168()` **przyjmuje**
`$engine`, ale krok 0 (ręczne override'y) zwracał przed jego sprawdzeniem:

```php
if (isset($override["{$mark}|{$model}"])) {
    return $override["{$mark}|{$model}"];   // $engine nigdy nie użyty
}
```

Che168 trzyma warianty EV i DM-i **pod jedną nazwą modelu** (`Han`, `海豹06`,
`PLUS New Energy` = skrót od `宋PLUS新能源`), a nasze huby są rozbite po napędzie — rozbicie jest
NASZE, nie źródłowe. Override był więc płaski i celował w wariant dominujący; komentarz w mapie
mówił wprost „engine-agnostyczny … celuje w wariant DM-i (dominujący)". Wariant mniejszościowy
zawsze trafiał do cudzego huba.

**Poprawka — opcjonalny klucz `by_engine`** w `data/che168-model-map.php`, konsultowany w kroku 0:

```php
'BYD|PLUS New Energy' => [
    'serie_eu' => 'Song PLUS EV', ...,                 // domyślny (dominujący)
    'by_engine' => ['plug-in hybrid' => ['serie_eu' => 'Seal U DM-I (Song Plus)', ...]],
],
```

Dopasowanie przez `self::ci()` (case/spacje-insensitive). **Wstecznie zgodne** — wpisy bez
`by_engine` zachowują się identycznie; potwierdzone testem `engine=(brak)` → wariant domyślny.
Objęte 3 override'y: `BYD|Han` (default DM-i, 52 oferty → EV), `BYD|海豹06` (default DM-i → EV),
`BYD|PLUS New Energy` (default EV → DM-i).

**Sprzątnięte 6 ofert sprzed poprawki** (`scripts/fix-rozjazd-napedu-2026-07-30.php`, guard na
`fuel` przed przepięciem) + przeliczone tytuły/slugi (`scripts/odswiez-tytuly-ofert.php`).
Stare URL-e żyją przez natywny `_wp_old_slug` — zweryfikowane: `byd-song-plus-ev-2025-396835`
→ **301** → `byd-seal-u-dm-i-song-plus-2025-396835` → **200**.

**Skala i weryfikacja.** Nowy `scripts/detektor-rozjazdu-napedu.php` porównuje napęd oferty
(`fuel`) z napędem deklarowanym w nazwie serii; zwraca kod 1 gdy są rozjazdy, więc nadaje się
do crona. Przed: 6 rozjazdów na 704 oferty (0,9%), wszystkie che168, wszystkie BYD.
Po: **705/705 zgodnych, 0 rozjazdów**. Kandydat do wciągnięcia do `scripts/che168-monitor.php` —
dziś monitor łapie rozjazdy po `车型名称`, ale nie po napędzie.

Backupy: `data/che168-model-map.php.bak-2026-07-30-byengine`,
`includes/class-asiaauto-mapping.php.bak-2026-07-30-byengine`.

## 0.34.12 — 2026-07-30 (T-222: tryb `verify` dla dongchedi + parytet filtrów che168)

**Tryb pracy synca per źródło.** Nowa opcja `asiaauto_sync_mode_{source}` (`full` | `verify`,
domyślnie `full`) + `AsiaAuto_Sync::modeForSource()` / `isVerifyOnly()`. W trybie `verify` sync
**nie importuje nowych ofert**, ale nadal aktualizuje ceny istniejących i wycofuje zdjęte
u źródła (`markRemoved()` → `draft` → 301 na hub modelu).

Po co: dongchedi jest wygaszane przed końcem opłaconego okresu auto-api (**15.08.2026**), ale
odcięcie API od razu pozbawiłoby nas jedynego sposobu sprawdzenia, które z ~1500 żywych ofert
jeszcze istnieją — zapas zgniłby jako ogłoszenia aut już sprzedanych. Oferta na dongchedi żyje
2–4 tygodnie (rozkład wieku martwych z przemiału 29.07: 15–30 dni → 800 szt.).

**Dwie furtki, nie jedna.** Zablokowanie samego `case 'added'` nie wystarcza — zdarzenie
`changed` na ofercie, której nie ma lokalnie, wpada w gałąź „treat as new" i też importuje.
`changed` to ~42% strumienia. Obie zamknięte pod tą samą flagą.

Zweryfikowane na produkcji: bieg 08:36 (przed flagą) `added=4`, bieg 08:40 (po) `added=0`,
`removed` dalej działa (3 i 2 w biegach 07:48 i 08:19).

**Filtry che168 → parytet z dongchedi.** `asiaauto_import_config['che168']['marks']` **12 → 60**
= suma 57 marek dongchedi + 3 własne che168 (**BAW, Lynk & Co, Volvo**). Uwaga: prosta podmiana
listy skasowałaby te trzy — wymagane **scalenie**, nie przypisanie. `model_blacklist` che168
(2 wpisy, w tym 22 modele Volvo) nietknięty. Pozostałe kryteria były już identyczne: rocznik
≥2024, przebieg ≤40 000 km, cena ≥85 000 ¥, te same 31 miast.

Backup opcji: `asiaauto_import_config_backup_2026_07_30`.
Spec: `docs/superpowers/specs/2026-07-30-t222-migracja-dongchedi-che168-design.md`.

## 0.34.11 — 2026-07-29 (sieroty z kanału dongchedi: Passion L + Fulwin T10; stare URL-e ofert)

**Guard mapowania działa wyłącznie dla che168.** `AsiaAuto_Sync::normalizeForSource()` otwiera się
linią `if ($source !== 'che168') return $data;` — kanał **dongchedi nie ma żadnego guarda**, więc
niezmapowana para `mark|model` wchodzi i importer buduje taksonomię fallbackiem. To koryguje
dotychczasowe założenie „ręczny import bez guarda, sync ma": sync dongchedi też go nie ma.

Klucze zmierzone ścieżką `getOffer() → getEuForCn()` (nie zgadywane) — wszystkie wracały NULL:
`Voyah|Voyah Zhuiguang L`, `Chery Fengyun|Fengyun T10`, `Chery Fengyun|Fengyun X3L`.

**Kierunek scalenia rozstrzygnięty dwoma źródłami**, nie samym istnieniem 301 z 27.07:

| kierunek | DFS PL | GSC 90 dni |
|---|---|---|
| `chery fulwin` vs `chery fengyun` | 90 vs 30/mc | **91 imp / 3 kliki** vs 4 / 0 |
| `voyah passion` vs `zhuiguang` | 320 vs —/mc | **39 imp / 2 kliki** vs 1 / 0 |

Nazwa `T10` (nie `Fengyun T10`) — DFS nie ma danych dla żadnego wariantu, więc rozstrzyga
konwencja większości pod tą marką: T8, T9, T11, A8L, A9L, X3 PLUS. Przy okazji zlikwidowany
duplikat `fengyun-x3l` wobec pustego `x3l` i pusty term `fengyun-x3`.

**Naprawa uboczna, ujawniona przy testach — stare URL-e ofert lądowały na hubie.**
`detectListingNotFound()` wisi na `template_redirect` z priorytetem **1**, a natywny
`wp_old_slug_redirect` dopiero z **10** — więc przy każdej zmianie slugu oferty nasz hook
wyprzedzał mechanizm WP i użytkownik z linku do konkretnego auta trafiał na listę modelu.
Dodana metoda `resolveMovedListingUrl()` (addytywnie, przed dotychczasową ścieżką): działa
tylko dla ofert `publish` ze zmienionym slugiem, więc equity transfer dla sprzedanych
(draft/trash → hub) jest nietknięty — zweryfikowane na `aito-m7-2024-390514`.

Wyposażenie: bank specyfikacji uzupełnił 35 ofert o **+10 763 pola**. Sam Voyah 394247 wskoczył
41 → 399 dopiero **po** naprawie taksonomii — klucz banku `voyah|passion-l|4wd ultra|2026` (378 pól)
nie trafiał, dopóki oferta siedziała pod termem `voyah-zhuiguang-l`. To praktyczny dowód, że
kolejność procedury (taksonomia → mapowania → wyposażenie) nie jest akademicka.

Indexing: 8 URL (huby modeli i marek + 3 oferty ze zmienionym slugiem), lista w
`tmp/indexing/fulwin-passion-SUBMITTED-2026-07-29.txt`. Hub `T10` **świadomie pominięty** —
nie ma jeszcze FAQ, zgłoszenie przed treścią paliłoby budżet drugi raz na tym samym URL-u.

## 0.34.10 — 2026-07-28 (rodzina GAC: Aion Hyper → Hyptec, rename rozstrzygnięty wolumenami)

**Decyzja nazewnicza podjęta na zachowaniu użytkowników, nie na rebrandingu producenta.**
To rozróżnienie jest sednem wpisu: fakt, że GAC przemianował 昊铂 z „Aion Hyper" na „Hyptec",
niczego sam nie dowodzi — ludzie nie muszą iść za producentem. Dowodem są **zliczone zapytania**
(Google Ads search volume, PL, 2026-07-28):

| model | warianty „Hyptec" | warianty „Aion Hyper / Hyper" |
|---|---|---|
| HT | 2300 | 50 |
| HL | 890 | 30 |
| SSR | 280 | 170 |
| GT | 40 | 40 |
| A800 | 30 | 0 |
| **razem** | **3540/mc** | **210/mc** |

Trend 12 mies. pokazuje samą **migrację zachowań**, nie stan: „aion hyper ssr" 70→10 przy
jednoczesnym „hyptec ssr" 40→320. Szczyt „gac hyptec ht" = 3600 we wrześniu 2025 (premiera PL),
dziś 390–880/mc. Kontrole niezależne: Wikipedia PL ma osobne hasła Hyptec HT/HL/SSR/GT;
west-motors (konkurent w tym samym modelu biznesowym) pisze „GAC Hyptec HL **z Chin**";
SERP na „gac aion hyper ht" zwraca wyłącznie strony z „Hyptec" w tytule.

**Dlaczego 0.34.9 poszło w drugą stronę:** oparłem się na GSC, gdzie „hyptec" miało 0 wyświetleń.
To błędne koło — GSC pokazuje tylko frazy, na których **już się wyświetlamy**, a nie wyświetlaliśmy
się na „hyptec", bo nie mieliśmy tak nazwanych hubów. **Reguła na przyszłość: GSC nie odpowiada
na pytanie „jak nazwać coś, czego jeszcze nie mamy" — do tego służą wolumeny rynkowe.**

**Zakres:** 5 termów (nazwa + slug `hyper-*`→`hyptec-*` + `_serie_full_title`), 12 tytułów ofert,
`postmeta.serie`, treść termmeta (wiki/FAQ/lead/seo_desc/schema Brand), oba pliki mapowań
(18 + 15 podmian, klucze CN **nietknięte** — z API nadal przychodzi `GAC Aion Hyper|Hyper *`
i `Hyper|HT`), regen title hubów. Backup: `~/backups/primaauto/2026-07-28-hyptec-rename/`.

**Alias zachowany świadomie.** Do wiki 4 hubów dopisany akapit „GAC Hyptec X a GAC Aion Hyper X —
czy to ten sam samochód?". Przy SSR (170/mc) i GT (40/mc) stara nazwa nadal ma realny wolumen,
bo tam żyje archiwum wideo sprzed rebrandingu. GT nie ma `wiki_body` — pominięty, do fali treści.

**Redirecty:** V62 odwrócone (`hyper-*` → `hyptec-*`, 5 wpisów), V63 `hyper/ht` przecelowane.
Dodatkowo V63 dla `gac-aion-hyper/hyper-*` — bez tego stare URL-e z GSC (m.in. `/gac-aion-hyper/hyper-ssr/`,
16 wyświetleń) robiły **łańcuch 301→301** przez V61. Teraz jeden skok.

**Smoke test:** 5 nowych hubów 200, 10 starych ścieżek 301 pojedynczym skokiem, oferty z tytułem
„GAC Hyptec …", sitemapy wyłącznie z `hyptec-*`, alias renderuje się w treści, iCAR nietknięty.
Kontrola resztek: 0 postów ze starą nazwą, 0 metadanych `serie` ze starym slugiem.

**Znalezisko poboczne (NIE naprawione, czeka na decyzję):** `_asiaauto_spec_snapshot` ma
**rozwalony JSON we wszystkich 341 termach** — `update_term_meta()` przepuszcza wartość przez
`wp_unslash()`, co zjada backslashe z `wp_json_encode()` (`\"` → `"`). To ten sam mechanizm co
przy escape'ach unicode. Skutek: `AsiaAuto_Spec::getSnapshot()` zwraca null wszędzie, więc fallback
dla hubów z `count=0` nie działa. Gate indeksacji jest bezpieczny — mierzy `strlen()`, nie parsuje.
Stan zastany, potwierdzony w backupie sprzed renamu.

## 0.34.9 — 2026-07-28 (GAC Aion Hyper: scalenie efemerycznej marki „Hyper", 5 mapowań, kolejność V63/V61)

> **UWAGA — uzasadnienie nazewnicze w tym wpisie zostało obalone tego samego dnia.**
> Kierunek scalenia (`hyper-*` jako kanoniczny) oparłem na GSC, co było błędnym kołem.
> Rozstrzygnięcie na danych o zachowaniu użytkowników: patrz **0.34.10**. Wpis zostaje
> jako zapis przebiegu, nie jako obowiązujący stan.

**Punkt wejścia:** oferta che168 `hyper-ht-2025-390660` wylądowała pod własną marką „Hyper"
zamiast pod GAC. Przyczyna: che168 podaje tę markę **zlatynizowaną** (`mark='Hyper'`,
`model='HT'`, nie CJK), a mapowania jej nie znały → sierota. Ofertę wprowadzono **ręcznie
z panelu**, a ta ścieżka (`Admin_Che168_Import::ajaxImport`) liczy flagę `mapped` i wyświetla
„❌ sierota", ale **nie blokuje** importu — bramkami są tylko `importEnabled`, duplikat
i `isEmptyShell`. Sync ma guard (`isMappedForImport`) i by tego nie wpuścił; niezależnie od tego
kanał che168 i tak stoi na filtrach konfiguracyjnych. Automat nie mógł tego zrobić — zrobił to
ręczny import na sierocie.

**Cała rodzina Aion Hyper była sierotą brand-mappingu.** Jedyny wpis (`GAC Aion Hyper|Hyptec HT`)
celował w slug `hyptec-ht`, którego nie używał żaden listing. Skutek uboczny: importer budował
`post_title` z surowego mark+model, stąd **9 tytułów z duplikacją** „GAC Aion Hyper **Hyper** HT".

**Kierunek scalenia rozstrzygnięty danymi, nie nazwą producenta.** Międzynarodowo marka to
dziś Hyptec, ale w GSC (90 dni) „hyptec" ma **0 zapytań**, a „aion hyper ht" / „gac aion hyper"
12 wyświetleń; `hyper-ht` ma 5 ofert i wygenerowaną treść hubu, `hyptec-ht` miał 0 ofert.
Kanoniczny jest więc `hyper-*`, a `hyptec-ht` poszedł do kasacji.

**Mapowania (+10 wpisów, 0 zmienionych, 0 usuniętych):** `brand-mapping-v6.1.php` sekcja v6.8 —
`GAC Aion Hyper|Hyper {HT,HL,GT,SSR,A800}` → GAC / `Aion Hyper *`; `che168-model-map.php` —
`Hyper|{HT,HL,GT,SSR,A800}` (5 aliasów prewencyjnych, nie tylko HT z bieżącej oferty).
Stary wpis `Hyptec HT` przeniesiony **na koniec pliku** i przecelowany na `hyper-ht`: `sigToKey()`
bierze PIERWSZY klucz dla sygnatury `GAC|Aion Hyper HT`, więc kolejność w pliku decyduje,
czym adapter che168 podmienia mark/model.

**DB:** listing 390660 → make `gac` + serie `hyper-ht` (relacje, meta, title); 9 tytułów
`REPLACE('GAC Aion Hyper Hyper ')` + 2 warianty `GAC Hyper HL` → `GAC Aion Hyper HL`;
skasowane termy `serie/HT` (7202), `make/Hyper` (7174), `serie/Hyptec HT` (6529).
Recount: GAC 32→33, Aion Hyper HT 4→5. Backup: `~/backups/primaauto/2026-07-28-hyper-gac-merge/`.

**Redirecty + zmiana kolejności hooków.** `V61['hyper'=>'gac']`, `V62['gac']['hyptec-ht'=>'hyper-ht']`,
`V63['hyper']['ht'=>['gac','hyper-ht']]`. Przy okazji wyszła **pułapka projektowa**:
`redirectV63MakeSerieMoves` był rejestrowany PO `redirectV61Brands` (oba priorytet 0), więc
marka obecna w obu mapach traciła człon serii — `/samochody/hyper/ht/` poleciałoby na
`/samochody/gac/ht/` (404). V63 przeniesiony na **priorytet -1** (reguła bardziej szczegółowa
przed ogólną). iCAR nietknięty — jego `old_make` (`chery`) nie występuje w V61, smoke test 301 OK.

**Smoke test:** `/oferta/hyper-ht-2025-390660/` 200 (title „GAC Aion Hyper HT Max 825 2025"),
`/samochody/gac/hyper-ht/` 200 (5 sztuk po `regen_hub_titles`), `/samochody/hyper/ht/` →
`gac/hyper-ht` 301, `/samochody/hyper/` → `gac` 301, `/samochody/gac/hyptec-ht/` → `gac/hyper-ht` 301,
`/samochody/chery/icar-03/` → `icar/03` 301. Sitemapy taksonomii: zero martwych URL-i.

**Zostawione świadomie:** slug oferty `hyper-ht-2025-390660` bez zmian (URL zgłoszony do Indexing
API 27.07 — rename oznaczałby zbędny 301); treść wiki/FAQ hubu `hyper-ht` nadal mówi
„4 egzemplarzy" (LLM-content z 28.04, do fali regeneracji, nie do ręcznej łatki).

## 0.34.8 — 2026-07-28 (Lynk & Co: mapowania + rename hubów, domknięcie luki 92 ofert, tr_val, naprawa JSON)

**Mapowania Lynk & Co — 15 wpisów.** Brand-mapping miał tylko `900`; pozostałe huby powstały
fallbackiem `translateModel` z dongchedi (bez guarda), więc marka była na stronie, a sync che168
ją odrzucał. Dodane `03`, `06/07/08 EM-P`, `Z10`, `Z20` + aliasy CJK `领克900` i `领克10`
(che168 podaje te dwa modele po chińsku, `领克` nie ma w `cnPrefix` resolvera — stąd sieroty).
Mapy: `che168-model-map.php` 115→123, `brand-mapping-v6.1.php` 295→302, **0 wpisów zmienionych
i usuniętych** (regresja dongchedi zero).

**Rename hubów Lynk bez ryzyka SEO.** Filtr pokazuje `$term->name`, a H1/breadcrumb biorą
`_serie_full_title` (fallback na nazwę), title/description są literalne w termmeta — więc
skrócenie nazw (`03` zamiast „Lynk & Co 03") zmienia wyłącznie etykietę w filtrze. Slug nietknięty,
zero 301. Przy okazji naprawiona podwojona marka w 2 tytułach (`ensureBrandPrefix` nie rozpoznawał
marki w nazwie z encją `&amp;`).

**Domknięcie luki: 92 oferty, 0 błędów** (`--pages=18`; przy domyślnych 6 stronach monitor
pokazywał tylko 46). che168 publish 58→151. Huby: `900` 9→16, `03` 2→24, `08 EM-P` 1→8,
`10 EM-P` 0→4, `Z20` 0→2.

**`tr_val()` — częściowe dopasowanie tylko dla kluczy CJK.** Mapa ma jednoznakowe klucze
łacińskie `L`/`V`/`H`/`W`, które trafiały w każdą wartość z tą literą (`800V` → „Widlasty (V)").
Zasięg funkcji: `gearbox_description` i `car_body_struct` na spec-hubach.

**14 ofert z nieparsowalnym `extra_prep`** odzyskanych parserem `fixBrokenFlatJson()` —
5551 kluczy, 12 439 escape'ów, zapis przez `wp_slash` + `JSON_UNESCAPED_UNICODE`.

Sesja: `docs/sesje/2026-07-28-che168-lynk-domkniecie-luki-trval.md`.

## 0.34.7 — 2026-07-27 (katalog Autohome jako źródło wyposażenia che168 + rebranding Passion)

**Blokada wyposażenia che168 rozstrzygnięta.** auto-api odpisało 27.07: pełnej konfiguracji
**nie ma i nie będzie** na żadnym planie (nie zbierają tych grup, oferty płatnej brak). Odesłali
do publicznego katalogu Autohome po `specid`. Zbudowaliśmy tę ścieżkę:

1. **`specid` stemplowany przy imporcie** — `class-asiaauto-che168-adapter.php` wyciąga
   `extra.configuration.specid` do `$data['spec_id']`, `class-asiaauto-importer.php` zapisuje
   `_asiaauto_spec_id` (obie ścieżki: pojedyncza i batch). Backfill: 99 ze 120 ofert
   (21 zwraca 404 — wygasły u źródła), 89 unikalnych specidów.
2. **`scripts/autohome-catalog-fetch.js`** — pobiera stronę katalogu i **zdejmuje obfuskację**.
   Autohome podmienia ~46% nazw i ~4% wartości na `<span class='hs_kwNN_*'>`, z numeracją losową
   per żądanie; deszyfrator to zaciemniony inline-JS, który blokuje odczyt przez `getComputedStyle`,
   ale nie chroni generatora reguł CSS. Uruchomienie go w Node ze stubem DOM daje mapę znaków:
   **292 parametry, zero nieodszyfrowanych**. Bez przeglądarki.
3. **`data/autohome-catalog-map.php`** — most **nazwa CN → klucz `extra_prep`** (126 nazw → 129
   kluczy). Po nazwie, NIE po ID: przestrzenie ID katalogu i auto-api są rozłączne (1 wspólny ID
   na ~270, i to z inną semantyką).
4. **`data/translations-extra-prep.php`** — +9 etykiet PL, wartość `选配` → „Opcja", klucze wpięte
   w 6 istniejących kategorii.
5. **`scripts/autohome-catalog-merge.php`** — dolewa wyłącznie brakujące klucze, stempluje
   `_asiaauto_spec_catalog_*`.

**Efekt:** oferta 390681 `extra_prep` 90 → 196 (**88 pozycji wyposażenia po polsku**),
390697: 104 → 173 (**71 pozycji**). Wcześniej zero. Bez dotykania szablonów.

**Katalog wykrył błąd danych dostawcy:** auto-api dla `岚图追光` (Voyah Passion) zwraca w polu
`model` dosłownie **„Zeekr"** — importer założył serię „Zeekr" pod marką Voyah (usunięta).
Alias `VOYAH|Zeekr` w `che168-model-map.php` + sygnatury w `brand-mapping-v6.1.php`.

**Rebranding Zhuiguang → Passion** (nazwa eksportowa 追光). `追光L` = Passion L to **osobny model**
(seriesid 8259, premiera XII 2025), nie wersja Passion (seriesid 6915). Podstawa: GSC 90 dni — hub
`voyah-zhuiguang-l` miał 0 impresji, „zhuiguang" 1, „passion" 19 + klik. Termy 5081/5078/5079 →
`passion-l`/`passion-phev`/`passion-ev` + 301 w `V62_SERIE_REDIRECTS`; treści hubów, tytuły 11 ofert,
meta `serie`, alt 107 zdjęć, `post_content` 4 ofert, 210 przemianowanych plików zdjęć.
`translations-models.php`: `岚图追光L`/`岚图追光 L` (che168 podaje ze spacją) → `Passion L`.
Hub 5078 uzupełniony o brakujące meta reworku v3 (`_asiaauto_lead`, `_asiaauto_h1_suffix`).

**Slugi ofert dongchedi celowo nietknięte** (mogą być zaindeksowane; zwracają 200).

## 0.34.6 — 2026-07-27 (korekta nazw: KEDE + PolarStone 01, treść 3 hubów)

**Dwie korekty nazewnictwa po weryfikacji u źródła (zgłoszone przez Janka):**

1. **KEDE, nie Kedi.** Własna nazwa angielska producenta 克蒂汽车 to **KEDE** (kede-auto.com;
   dawniej KRYSTAL, przejęte 2016). Oba API romanizują ją błędnie i różnie: dongchedi `Kedi`,
   che168 `Kurti`. Hub: `/samochody/toyota/kedi-shanchuan/` → **`/samochody/toyota/kede-shanchuan/`**.
2. **To nie Alphard.** `阿尔法` = `Alpha` (seria KEDE); Alphard po chińsku to `埃尔法`. Dane
   potwierdzają: napęd na tył, rozstaw osi 3210 mm, drzwi przesuwne = baza Hiace/Granvia
   (Alphard ma przód napędowy i rozstaw 3000 mm). Logo Toyoty jest na atrapie i klapie
   (weryfikacja na zdjęciach oferty), więc marka `Toyota` zostaje.
   `translations-complectations.php`: `Alphard 36th Anniversary` → **`Alpha 36th Anniversary`**.
3. **PolarStone 01 w nawiasie** (decyzja Ruslana/Janka): `ROX 01` to nazwa międzynarodowa,
   `PolarStone 01` (极石01) chińska. Konwencja jak `Leopard 5 (Denza B5)` — serie
   `01 (PolarStone 01)`, slug `01` bez zmian.

**Treść 3 hubów wg `docs/seo/hub-rework-method-2026-05-30.md`** (`_asiaauto_lead`,
`_asiaauto_h1_suffix` = `cena w Polsce i import z Chin`, `_asiaauto_pl_availability` =
`import_only` ×3, `asiaauto_wiki_body` z `{{LISTINGS_BAR}}`, `asiaauto_faq_json`,
`_asiaauto_seo_rework` = `v1-2026-07-27`). Skrypt: `scripts/hub-content-rox-e7x-kede-2026-07-27.php`
(gate przed zapisem: brak `"` w FAQ, `json_decode` przechodzi, diakrytyki obecne w FAQ/wiki/lead,
token `{{LISTINGS_BAR}}` obecny). Tabela spec **nie** jest w wiki — renderuje się live
z `extra_prep` (`class-asiaauto-spec`), zgodnie z reworkiem z 06-13.

| Hub | lead | wiki | FAQ | H2 | title |
|---|---|---|---|---|---|
| ROX 01 (PolarStone 01) | 451 zn. | 4688 zn. | 6 Q | 12 | od 260 000 PLN |
| Audi E7X | 398 zn. | 3979 zn. | 5 Q | 11 | od 303 000 PLN |
| Toyota KEDE Shanchuan | 425 zn. | 4395 zn. | 5 Q | 12 | od 513 000 PLN |

Smoke test curlem ×3: H1 z marką i sufiksem cenowym, lead widoczny, FAQPage parsuje
(6/5/5 `acceptedAnswer`), token `{{LISTINGS_BAR}}` nie wycieka, tabela spec obecna.
Stare URL-e (`kedi-shanchuan`) → 301 na nowe. Indexing: 3 URL-e hubów przez
`~/bin/index-submit --project primaauto --yes` (świadome wejście w rezerwę o 3, ledger 103/100),
oferty zgłoszone wcześniej hookiem publikacji.

Backupy: `.bak-2026-07-27-kede` (3 pliki), `~/backups/primaauto/2026-07-27/termmeta-przed-trescia-hubow.sql`.

## 0.34.5 — 2026-07-27 (Toyota Kedi Shanchuan + trzy huby LIVE z ofertą)

Domknięcie 0.34.4: trzeci model domapowany i wszystkie trzy huby wyszły z pustego stanu —
po jednej ofercie każdy (aktywacja szkiców, bez importu nowych sztuk).

**Toyota Kedi Shanchuan** — luksusowe MPV na zamówienie (Ruslan). 克蒂汽车 to chiński
karosernik przerabiający Toyotę Alphard na wersje VIP. Nazwy w źródłach się rozjeżdżają:
dongchedi `Kedi|Kedi Shanchuan`, che168 `Kurti|克蒂山川` (model czysto-CJK).

**Marka = Toyota (decyzja Janka 2026-07-27).** Pierwsza wersja tego wpisu szła pod marką
`Kedi` — błąd, wycofany tego samego dnia. Powód decyzji: to nadal Alphard po przeróbce,
a `Toyota` ma popyt, którego `Kedi` nie ma (DFS PL/pl: `toyota alphard` 2400/mc,
`alphard` 480/mc, natomiast `kedi m7`, `kedi shanchuan`, `alphard chiny`,
`toyota alphard chiny` = **wszystkie bez danych**; `kedi` 390/mc to szum — tureckie „kot"
i film dokumentalny).

**Model = `Kedi Shanchuan`, NIE `M7`.** Term `serie` `M7` ma 59 listingów innych marek
(AITO M7 i inne), więc hub `/samochody/toyota/m7/` mieszałby marki. Serie na poziomie
modelu, bez wersji — `complectation` ofert i tak wnosi „M7 3.5L …".

Zmiany w danych:
- `data/brand-mapping-v6.1.php` — sekcja **v6.6**: `Kedi|Kedi Shanchuan` →
  `Toyota Kedi Shanchuan` (serie `Kedi Shanchuan`, slug `kedi-shanchuan`).
  293 → 294 wpisy, **0 zmienionych, 0 usuniętych**.
- `data/che168-model-map.php` — override `Kurti|克蒂山川` (model czysto-CJK, reverse-index
  nie ma czego trafić).
- `data/translations-complectations.php` — `阿尔法36周年纪念版` → `Alphard 36th Anniversary`.
  Bez tego segmentu z wersji zostawało w tytule samo „36".

Weryfikacja: che168 `58275243` → `Toyota Kedi Shanchuan`, guard `isMappedForImport()` =
`true`; dongchedi `23589815` i `24522589` → ten sam hub.

**Trzy huby LIVE (każdy z 1 ofertą, HTTP 200):**

| Hub | Oferta | Cena |
|---|---|---|
| `/samochody/rox/01/` | `/oferta/rox-01-2025-390560/` | 260 000 PLN |
| `/samochody/audi/e7x/` | `/oferta/audi-e7x-2026-390551/` | 303 000 PLN |
| `/samochody/toyota/kedi-shanchuan/` | `/oferta/toyota-kedi-shanchuan-2026-390186/` | 513 000 PLN |

Przy publikacji:
- #390186 (ten sam post, nic nie kasowane): `Toyota`/`M7` → `Toyota`/`Kedi Shanchuan`,
  tytuł „Toyota Kedi Shanchuan 2026 M7 3.5L Alphard 36th Anniversary", 14 zdjęć nietknięte.
  Nowy term `serie` #7201 `Kedi Shanchuan` pod `toyota`.
- **Kolizja slugów:** slug `kedi-shanchuan` był już zajęty przez pusty term #7046 zasiany
  z filtrów API (bez parenta, bez meta, 0 ofert) → WP zunikalnił nowy term na
  `kedi-shanchuan-2`. #7046 usunięty, slug #7201 poprawiony na `kedi-shanchuan`,
  `meta serie` posta zsynchronizowana. Usunięty też pusty #7200 `Shanchuan` (0 ofert,
  artefakt wycofanej wersji „marka Kedi").
- Slugi ofert poprawione po publikacji; stare URL-e (`toyota-m7-2026-390186`,
  `kedi-shanchuan-2026-390186`, `rox-extreme-stone-01-2025-390560`) dają **301 na właściwy
  hub** — warstwa redirectów pluginu je łapie, więc żadna nie została na 404.
  Nowe URL-e dosłane do Indexing API (`pushOrQueue`), kolejka retry pusta.
- `_serie_full_title` ustawione na wszystkich trzech termach (`ROX 01`, `Audi E7X`,
  `Toyota Kedi Shanchuan`) — bez tego theme (`taxonomy-serie.php:44`) bierze surową nazwę
  termu i H1 wychodzi bez marki („01 — import z Chin"), znany bug z 2026-07-13.
  Term `serie` zostaje przy gołym modelu (`01`, `E7X`) zgodnie z konwencją pliku
  (Avatr `06`, iCAR `03`, WEY `07`); marka doklejana jest wyłącznie w warstwie
  wyświetlania (`_serie_full_title` / `ensureBrandPrefix()`).
- `AsiaAuto_HubTitleGenerator::regenerateForTerm()` ×3 + `regenerateForMakeTerm()` dla
  `rox`/`kedi`/`audi`/`toyota` → `<title>` i meta description hubów wypełnione.

Backup taksonomii przed zmianami: `~/backups/primaauto/2026-07-27/taxonomy-przed-rox-audi.sql`.
Backupy plików: `.bak-2026-07-27-roxaudi`, `.bak-2026-07-27-kedi`, `.bak-2026-07-27-toyotakedi`.

## 0.34.4 — 2026-07-27 (ROX 01 + AUDI E7X — nazwy hubów rozstrzygnięte danymi DFS)

Wyłącznie pliki danych (`data/*.php`), zero zmian w kodzie. Dwa nowe modele domapowane
pod nazwy **rynkowe**, nie te z API — oba źródła podają dla nich nazwy martwe w wyszukiwarce.

**ROX 01** (极石01, EREV, mid-large SUV). Źródła nie zgadzają się co do nazwy modelu:
dongchedi `ROX|Jishi 01` (pinyin), che168 `ROX|Extreme Stone 01` (kalka dosłowna).
DFS (Google Ads Keyword Planner, PL/pl, 2026-07-27): `rox 01` **170/mc**, `rox adamas` 70,
`rox 01 cena` 50, `rox motor` 40, `jishi 01` **10**, `extreme stone 01` i `polestones 01`
**bez danych**. SERP PL dla „rox 01" jest komercyjny — #1 tntcars.pl, #2 autopunktmlawa.pl
(polscy sprzedawcy już na tej nazwie). Oba źródła zbiegają się więc w term `01` / slug `01`
(konwencja serie-bez-marki jak Avatr `06`, iCAR `03`, WEY `07`), title `ROX 01`.
`ROX Adamas` (70/mc) to **lifting** (2025款 ADAMAS) — świadomie osobny hub w przyszłości.

**Audi E7X** (上汽奥迪, premiera 2026.05, BEV). Dongchedi tego modelu **nie ma w ogóle**
(kontrola 2026-07-27: 57 modeli Audi, najnowszy `Audi E5 Sportback`) — podaż wyłącznie
z che168, klucz w brand-mappingu jest syntetyczny i pełni rolę sygnatury dla `sigToKey()`.
DFS: `audi e7x` **390/mc** (szczyt 1600 w 04.2026, fala premierowa opada), SERP top8 bez
ani jednej oferty sprzedażowej (media + Wikipedia PL + AI Overview) = luka komercyjna.

Zmiany:
- `data/brand-mapping-v6.1.php` — sekcja **v6.5**: `ROX|Jishi 01` → `ROX 01` (serie `01`),
  `Audi|Audi E7X` → `Audi E7X` (serie `E7X`). Kontrola dyfem względem backupu:
  291 → 293 wpisy, **0 zmienionych, 0 usuniętych** → regresja dongchedi zero.
  Brak kolizji sygnatur (`ROX|01`, `Audi|E7X` unikalne) i slugów (`01` tylko ROX).
- `data/che168-model-map.php` — para override'ów dla surowych kluczy che168:
  `ROX|Extreme Stone 01` i `奥迪AUDI|奥迪E7X` (marka i model czysto-CJK, więc reverse-index
  nigdy by nie trafił — override to jedyna droga).

Weryfikacja pełną ścieżką adaptera (`getOffer` → `Che168_Adapter::normalize` → `getEuForCn`),
nie na surowych danych: 3 oferty ROX i 1 E7X z che168 + 2 ROX z dongchedi → wszystkie
`ROX 01` / `Audi E7X`, guard `Sync::isMappedForImport()` = `true` dla wszystkich.

Na produkcji przy okazji (backup taksonomii: `~/backups/primaauto/2026-07-27/`):
term `serie` **奥迪E7X → E7X** (slug `e7x` bez zmian), nowy term `01` pod `rox`,
oraz przepięcie dwóch szkiców — #390560 `ROX 01 2025 Premium 6-osobowy Standard`,
#390551 `Audi E7X 2026 Pioneer quattro`.

**Otwarte:** puste termy `serie` po starym nazewnictwie ROX — `extreme-stone-01` (#7198)
i `jishi-01` (#5083, ma zapisane `rank_math_title` + `_asiaauto_spec_snapshot` po hubie
z czasów, gdy stała tam 1 oferta). Nie kasowane — do decyzji, czy 301 na `/samochody/rox/01/`.

## 0.34.3 — 2026-07-25 (T-186: mapowania z sondy kanału — 100% trafialności w huby)

Wyłącznie pliki danych (`data/*.php`) — **zero zmian w kodzie**, więc zero ryzyka
regresji w strefie kruchej. Materiał wejściowy: sonda całej zaległości kanału che168
(59 811 zdarzeń, 246 ofert przechodzących filtr) wykonana **read-only**, bez importu,
bez pobierania zdjęć i bez tłumaczeń AI. Domknięcie: `docs/sesje/2026-07-25-che168-sonda-mapowania.md`.

**Mapowania modeli — trafialność w huby 85% → 98,4%** (pozostałe 1,6% to świadome
skipy ICE: Nissan Teana, Qashqai). Gate ze speca (`<5% orphanów`) przechodzi.

- `che168-model-map.php` — **16 aliasów** (95 → 111 wpisów). Wersje rozstrzygnięte
  po `param_93` (车型名称), nie „na oko": `海狮06` = DM-i → `Sealion 6 DM`,
  `汉L` = EV na wszystkich 3 sztukach → `Han L EV`, `Tang New Energy` = 唐新能源 DM-i
  → `Tang DM-i` (**nie** Sealion 8 — to Tang L, osobny model), `PLUS New Energy`
  = 宋PLUS新能源 EV → `Song PLUS EV`, `瑞虎8L` = 2.0T → `Tiggo 9 (Tiggo 8L)`.
- `brand-mapping-v6.1.php` — sekcja **v6.4, 7 nowych kluczy** (284 → 291).
  Powód niebanalny: adapter (`canonicalKeyForSource`) tłumaczy entry z model-mapy
  **z powrotem** na literalny klucz CN brand-mappingu przez `sigToKey()`, więc alias
  bez istniejącej sygnatury `mark_eu|serie_eu` jest martwy — guard dalej odrzuca ofertę.
  Zmierzone: bez tej sekcji mapowało się 11/18 orphanów, z nią **18/18**.
  Nowe: `Galaxy A7 EM-i`, `Li Auto MEGA`, `Frigate 07`, `Seal 07 EV`, `GX` (XPeng GX,
  premiera 2026-05-20), `Menglong Hi4`, `500 Hi4-T`. Slugi celują w **zasiedlone termy**
  (a7-em 38 listingów, li-auto-mega 8, frigate-07 4, gx 2), żeby nie rozbijać podaży
  na równoległe huby. Kontrola regresji vs backup: **0 wpisów zmienionych, 0 usuniętych**.

**Parametry — nieznane `param_*` 457 → 194 wystąpień**, a te 194 to wyłącznie dwa
celowo pominięte klucze (`92` wymiary = duplikat `length/width/height`, `93` nazwa
modelu = źródło tytułu). Audyt na 97 ofertach (po jednej na każdy unikalny model).

- `che168-param-map.php` — **20 id** (111 → 131 wpisów). Cztery celują w klucze, które
  dongchedi już ma (`compression_ratio_s`, `engine_unique_tech`, `electric_total_horsepower`
  dla id 102 i 140 — ta sama wartość w dwóch grupach, wzorzec 84/105). Pozostałe 16 to
  nowe wielkości; **żaden wpis nie nadpisuje istniejącego klucza**, bo każdy różni się
  warunkiem pomiaru albo jednostką: `130` prześwit **bez** ładunku vs `min_clearance`
  (满载, z pełnym), `149` zasięg NEDC **łączny** vs `nedc_recharge_mileage` (czysto
  elektryczny), `141` moc **układu** w KM vs `102` moc **silników** w KM, `136` nachylenie
  w **%** vs `137` w **stopniach**, `99`/`113` **pomiar** vs wartości katalogowe.
- `translations-extra-prep.php` — 16 etykiet PL + 14 jednostek + przypisania do kategorii
  (dimensions, body, engine, gearbox, fuel, ev). Bez kategorii adapter zapisałby wartość,
  ale render by ją ukrył jako nieskategoryzowaną.
- **Ubocznie naprawiony istniejący błąd:** `translateExtraPrep()` pomija wiersz, gdy wartość
  po tłumaczeniu nadal ma CJK („frontend never shows raw Chinese"). Wartość `支持` nie
  miała wpisu w `values`, więc **`quick_charge_interface` cicho znikał z karty w 26 z 30
  ofert próbki** (podobnie `battery_swap`). Dodane: `支持`→Tak, `不支持`→Nie oraz 5 struktur
  dyferencjału centralnego (`多片离合器`→Sprzęgło wielotarczowe itd.).

Weryfikacja końcowa przez `translateExtraPrep()` (widok klienta): 104 i 107 wierszy
specyfikacji, **zero CJK**, nowe pola z polskimi etykietami i jednostkami we właściwych
kategoriach. Miasta: **0 bez tłumaczenia** na 18 miastach z przechodzących ofert.
Backupy: `data/*.bak-2026-07-25-sonda` (4 pliki).

## 0.34.2 — 2026-07-22 (T-186: sync Che168 wpięty w automat, faza szkiców)

Adapter Che168 wpięty w ścieżkę automatyczną — dotąd `Che168_Adapter::normalize()`
miał jedno wywołanie w kodzie produkcyjnym (import ręczny), a `Sync::run()` podawał
importerowi surowy dialekt. Zmierzone przed zmianą: **0 z 730 ofert** przechodziło
filtr (surowy rekord che168 nie ma pola `city` — lokalizacja siedzi w `address`),
po wpięciu **89 z 730 (12%)**. `importListing()` i niżej — nietknięte (ADR
2026-06-17-che168-normalize-at-entry).

- `Sync::isEnabledForSource()` / `statusForSource()` — osobne wyłączniki per źródło
  (`asiaauto_sync_enabled_{source}`, brak wpisu = dziedziczy po globalnym) + status
  importu per źródło (che168 startuje na `draft`). Toggle w panelu Status.
- Cron przechodzi po obu źródłach niezależnie (lock/kursor/historia były per-source).
- Guard niezmapowanych: oferta bez huba nie wchodzi automatem, ląduje w kolejce
  `asiaauto_che168_unmapped`. Chroni przed CJK w tytułach i modelami spoza segmentu
  (spalinowe, marki wycofane). Kolejność: filtr konfigu → guard, inaczej kolejka
  zapełnia się BMW/Mercedesami z całych Chin.
- Filtr wstępny na danych z `/changes` przed `getOffer()` — che168 wypuszcza 7–11 tys.
  nowych ofert na dobę przy ~1% trafień, bez tego każde zdarzenie kosztowałoby
  wywołanie API. `Importer::isAllowedByConfig()` zmieniona z private na public
  (sama logika bez zmian).
- Mapping: indeks `serie_eu` bez marki dla marek foldowanych (Yangwang → `mark_eu`
  BYD) z wykluczeniem nazw niejednoznacznych (H6 Haval/Hongqi). Domapowania:
  `BYD|L EV`→Song L EV, `L DM-i`→Song L DM-i, `海豹06`→Seal 6 DM-i,
  `海豹06 DM-i旅行版`→Seal 6 DM Wagon, nowy `Leapmotor|零跑B01`→B01.
- Adapter: interpunkcja pełnoszerokościowa z `param_93` (（）， ) → ASCII; usuwało
  to „… LiDAR ）" z tytułów AITO.
- Tłumaczenia miast +17 (15 z 31 miast filtra importu nie miało tłumaczenia →
  102 listingi pokazywały chińską nazwę miasta; backfill wykonany).

Pierwszy bieg automatu (2000 zdarzeń, 336 s): 10 ofert w szkicach, wszystkie
trafiły w istniejące huby, kolejka domapowań pusta. Plan i pomiary:
`docs/superpowers/plans/2026-07-22-che168-sync-wpiecie.md`.

## 0.34.1 — 2026-07-22 (T-214: menu Wiedza + teaser aktualności na homepage)

Menu Header: nowy dropdown "Wiedza" (Aktualności + Słownik jako dzieci), pozycja
przed Kontaktem (nie za nim). `class-asiaauto-homepage.php` +renderKbTeaser():
sekcja "Aktualności z chińskiego rynku motoryzacyjnego" (3 najnowsze karty +
link do /wiki/) między Topic FAQ a CTA — cel: link z najwyższego autorytetu
strony przyspiesza indeksację i buduje ruch top-funnel (Indexing API/sitemap
wystarczają do samej indeksacji, nie do dystrybucji linku). Backup:
class-asiaauto-homepage.php.bak-2026-07-22.

## 0.34.0 — 2026-07-21 wieczór (T-214 F2/F3: Słownik live + cron newsowy; theme 1.1.2)

Słownik: 10 haseł Tier 1 opublikowanych pod /wiki/ (Opus, frazy DFS, FAQ z PAA, H2 z frazami,
research web dla Blade), index+follow, w llms.txt (build-llms.php + sekcje Słownik/Aktualności),
11 URL zgłoszonych index-submit, menu „Słownik" + „Aktualności". Rename Leksykon→Słownik (labels).
Newsy: 2 opublikowane z okładkami v2; cron 06:45 kb-news-daily.sh zarejestrowany (cron-install).
Theme 1.1.1→1.1.2: design-pass v2 (eyebrow, FAQ-akordeon, definicja z akcentem, karty, indeks
2-kolumnowy) + diagramy .pa-diagram/.pa-flow/.pa-bars (EREV/PHEV/800V) + zdjęcie z galerii w /wiki/lidar/
(wzorzec: technologia widoczna = kadr z oferty + link). Okładki: v2 wycentrowane z siatką, tylko
og:image/kafle (meta _kb_cover_auto wyłącza hero). Generator: single-pass claude -p (Opus, 3 równolegle,
~2,5 min/hasło), research WebSearch/WebFetch, PAA w prompcie. Lekcje w docs/roadmapa/T-214.

## 0.34.0 — 2026-07-21 (T-214 F0: fundamenty działu wiedzy)

Nowa klasa `includes/class-asiaauto-wiki.php` — CPT `asiaauto_wiki` (Leksykon, `/wiki/{haslo}/`,
archiwum `/wiki/`, meta `_wiki_aliases`/`_wiki_term_keys`/`_wiki_category`, 6 kategorii haseł).
`class-asiaauto-seo.php` +filtr `kbSchema` (rank_math/json_ld): wpisy kategorii `aktualnosci` →
NewsArticle z author=Organization „Redakcja Prima-Auto"; hasła wiki → DefinedTerm w DefinedTermSet.
Poza pluginem (konfiguracja WP/RankMath): permalinki `/%category%/%postname%/` + strip category base,
kategorie aktualnosci/rankingi/porownania/poradniki (7182-7185), user `redakcja` (ID 55,
„Redakcja Prima-Auto"), moduł news-sitemap RankMath (publication Prima-Auto, post type post,
`news-sitemap.xml` live), sitemap CPT wiki on, wiki tymczasowo noindex (do pierwszej treści F2),
strona `/informacje/o-redakcji/` (388050), default_comment_status=closed.
Theme 1.1.0: `single.php`, `category.php`, `archive-asiaauto_wiki.php`, `single-asiaauto_wiki.php`,
`assets/css/kb.css`. Backup DB przed permalinkami: `~/backups/primaauto/2026-07-21/`.
Odłożone do F2/F3: menu/footer „Wiedza" (po pierwszej treści), sekcje w generatorach llms.
Plan: `docs/roadmapa/T-214-dzial-wiedzy.md`.

F1 (ten sam deploy 0.34.0): `includes/class-asiaauto-kb-publish.php` — REST GET
`asiaauto/v1/kb-publish?post=&token=` (tokenowa publikacja draftu z maila akceptacyjnego,
hash_equals, token jednorazowy kasowany po użyciu, redirect na artykuł; 403 przy złym tokenie —
przetestowane). Silnik w repo: `scripts/kb/` (kb_lib.py — Anthropic API + lint + WP-CLI + send-to-jan;
news_daily.py — radar 4 RSS → dedup state → selekcja Claude → draft PL → fact-check 2. przebiegiem →
lint → draft WP + token → mail akceptacyjny; prompts/news_system.txt; kb-news-daily.sh — wrapper
cronowy flock+kill-switch, NIEzarejestrowany w crontab do F3). Radar przetestowany: 58 świeżych
kandydatów z 36h. Generowanie: **`claude -p` headless w ramach abonamentu** (decyzja Janka —
nie używamy płatnego Anthropic API; wzorzec jak warmupy w crontab). Skrzynka
redakcja@primaauto.com.pl założona 21.07.

## 0.33.38 — 2026-07-20 (przeglądarka „Przeglądaj Che168", GATED na js)

Etap 2 specu filtra modeli. Nowa podstrona pod Ogłoszeniami (`asiaauto-che168-browse`,
`class-asiaauto-admin-che168-browse.php`) — pozycja menu rejestrowana **tylko dla loginów
z `ASIAAUTO_CHE168_PREVIEW`** (Ruslan nie widzi). Filtry: marka (pełny słownik giełdy, 282+ marek),
chipy modeli (max 5 na zapytanie; nazwy kanoniczne, 🆕 bez huba, ⛔ na blackliście), rocznik od,
cena od, miasta (całe Chiny / nasza lista). Wyniki: karty ze zdjęciami (permanentne URL-e che168),
badge hub/orphan, „(w bazie)" dla już zaimportowanych; paginacja „Załaduj więcej". Podgląd = modal
(hub, wycena PLN `cena_koncowa`, VIN, wyposażenie, zdjęcia, warningi) + import per klik — oba przez
**istniejące** AJAX-y `asiaauto_che168_preview`/`asiaauto_che168_import` (ta sama ścieżka co „Dodaj
z Che168", strefa krucha nietknięta). Nowy AJAX `asiaauto_che168_browse` → `getOffers` server-side
(mark+model+year), miasta/cena filtrowane u nas. Smoke na żywym API: BYD Seal 14 ofert/strona,
filtr miast 5/20, gate js=tak / primaauto=nie.

**Hotfix zdjęć (ta sama wersja, po teście E2E w Chrome):** CDN Autohome (`2sc2.autoimg.cn`) tnie
hotlinki po nagłówku Referer → miniatury nie wstawały. Fix: karty i modal renderują `<img
referrerpolicy="no-referrer" loading="lazy">` zamiast CSS background-image. Po fixie 26/26 obrazków
załadowanych, 0 błędów (test E2E: strona → BYD → Szukaj → Podgląd z wyceną PLN). Przy okazji
usunięte `alert()`/`confirm()` (blokowały automatyzację; komunikaty w pasku statusu, import
potwierdzany dwuklikiem „Potwierdź import").

**Podgląd = deep-link do pełnej analizy (rewizja po feedbacku Janka, 20.07 wieczór):** modal
podglądu USUNIĘTY całkowicie. Przycisk „🔍 Podgląd" na karcie otwiera w nowej karcie stronę
„Dodaj z Che168" z `?inner_id=` — prefill numeru + automatyczny dry-run (hub, tytuł, taksonomie,
rozbicie ceny). Import robi się stamtąd, jak przy ręcznym wklejeniu numeru. Dwa bugi po drodze:
(1) auto-podgląd odpalał `wp.ajax` przed załadowaniem wp-util (stopka) → ReferenceError, fix
DOMContentLoaded; (2) URL budowany przez `esc_js()` — zamienia `&` na `&#038;`, a `#` ucina URL
jako fragment → link lądował na liście ogłoszeń (wcześniejsza diagnoza „OPcache" była BŁĘDNA);
fix `esc_url_raw()`. Test E2E po fixach: karta → Podgląd → pełna analiza ładuje się sama.

**Etykiety marek CJK:** `data/che168-mark-names.php` — 13 pewnych tłumaczeń (HIMA: AITO/Luxeed/
Stelato/Maextro/Shangjie, Maxus, Chery QQ, Sinotruk, Shineray, Firefly NIO, AUDI SAIC, Kama,
Yinlong); select przeglądarki sortowany po etykiecie (`Luxeed (Zhijie) · 智界`). Reszta CJK
(kampery/ciężarówki) celowo bez tłumaczenia.

## 0.33.37 — 2026-07-20 (filtr modeli Che168 w konfiguratorze + domapowania, GATED na js)

Element T-186. Zakładka „Filtry importu" dostaje subtabs Dongchedi | Che168 (widoczne **tylko dla
loginów z `ASIAAUTO_CHE168_PREVIEW`** — faza testów, Ruslan nie widzi). Che168: marki z pełnego
słownika API (`getFilters`, nowa klasa `AsiaAuto_Che168_Dictionary`, transient 7 dni + „Odśwież"),
**blacklista modeli per marka** (`model_blacklist` w `asiaauto_import_config['che168']`), limity
seedowane kopią dongchedi (2024+, ≤40 tys. km, ≥85 tys. ¥, 31 miast, 47 marek, enabled=true).
Egzekwowanie: addytywny blok w `isAllowedByConfig()` (strefa krucha poza tym nietknięta); ręczny
import per numer omija blacklistę. **Domapowania:** +25 aliasów `che168-model-map` + 8 nowych modeli
`brand-mapping` v6.3 (Leapmotor T03, eπ007, WEY Gaoshan, Smart #1/#3, Lotus Eletre, Jetour Shanhai
T1, BAIC BJ30) → trafialność ofert 2024-2026 w huby: 61% → ~81% (próbka 3550 ofert / 46 marek).
Spec: `docs/superpowers/specs/2026-07-20-che168-model-filter-design.md`. Etap 2 (przeglądarka
ofert „Przeglądaj Che168") — zaakceptowany mockup, do wdrożenia po testach etapu 1.

## 0.33.36 — 2026-07-20 (lista zamówień: 20 → 30 pozycji na stronę)

Prośba Janka (+50%). Przy „Wszystkie" (102) daje 4 strony zamiast 6, przy domyślnym widoku
klientów (41) — 2 zamiast 3. Dotyczy też kart na telefonie.

## 0.33.35 — 2026-07-20 (rotacja: ochrona ogłoszeń z aktywnym zamówieniem)

**Problem:** rotacja kasowała trwale ogłoszenia, na które wskazywały niezakończone zamówienia —
27 zamówień (16 aktywnych) straciło zdjęcie i nazwę auta. Stary strażnik `isReserved()` sprawdzał tylko
meta rezerwacji, a rezerwację zakładają wyłącznie statusy z `LISTING_RESERVATION_MAP`
(`zarezerwowane`/`zakupione`/`w_drodze`/`na_placu`/`w_dostawie`). Zamówienie w **weryfikacji,
potwierdzone, umowa gotowa, podpisane** nie chroniło niczego.

**Fix:** `AsiaAuto_Rotation::listingsWithActiveOrders()` — jedno zapytanie na przebieg zwracające
ogłoszenia z zamówieniem w statusie innym niż `anulowane`/`odrzucone`; `deleteOldTrash()` je pomija
(z wpisem WARN w logu). Trafia 28 ogłoszeń, z czego 5 leżało w koszu/draftach gotowych do skasowania.

**Dlaczego NIE rozszerzyliśmy `LISTING_RESERVATION_MAP`** (pierwotny pomysł, odrzucony po analizie):
rezerwacja steruje też sprzedażą — `listingIsBlockedForOrders()` w kreatorze i API pokazuje
„Ten samochód jest już zarezerwowany". Dodanie tam `weryfikacji` blokowałoby auto dla wszystkich
pozostałych klientów od momentu pierwszego zamówienia, przy ~46% anulat po stronie klientów
(35 z 76). Chronimy przed **kasowaniem**, nie przed **sprzedażą**. `class-asiaauto-order.php` nietknięty.

**Test e2e na produkcji** (sztuczna para ogłoszenie+zamówienie, posprzątana po teście):
zamówienie aktywne → `Skipped permanent delete of post #387953 (in trash but has an active order)`,
ogłoszenie przetrwało; po zmianie statusu na `anulowane` → `Permanently deleted 1 trashed posts`.
Ochrona zwalnia się poprawnie. Kopia: `class-asiaauto-rotation.php.bak-2026-07-20`.

## 0.33.34 — 2026-07-20 (fix: biały ekran karty zamówienia dla usuniętych ogłoszeń)

**Objaw:** wejście w 27 zamówień (16 aktywnych) kończyło się „W witrynie wystąpił krytyczny błąd".

**Korzeń — błąd sprzed T-218, nie jego skutek** (identyczny kod w `*.bak-2026-07-20`):
`renderOrderCard()` robiło `$data['listing_id'] ? get_edit_post_link($id) : ''`. Gdy ogłoszenie zostało
usunięte przez rotację, `listing_id` nadal jest niezerowe, ale `get_edit_post_link()` zwraca **null**
(a `get_permalink()` **false**) → `renderCardListing(array, string, string)` rzucało `TypeError`.
T-218 tylko ujawniło problem: wcześniej te zamówienia trudno było znaleźć na liście.

**Fix:** rzutowanie na `string` + wyraźny komunikat w karcie („Ogłoszenie #X nie istnieje już w bazie —
usunięte przy rotacji ofert; dane zamówienia nienaruszone"). Auto pozostaje rozpoznawalne po
`_order_source_url` (link do Dongchedi), który zamówienie przechowuje.

**Znaleziona przy okazji luka systemowa (NIE naprawiona — wymaga decyzji, patrz QUEUE):**
`AsiaAuto_Rotation::deleteOldTrash()` chroni ogłoszenia przez `isReserved()`, ale rezerwację zakłada
tylko `LISTING_RESERVATION_MAP` = `zarezerwowane`/`zakupione`/`w_drodze`/`na_placu`/`w_dostawie`.
Zamówienia w **weryfikacji, potwierdzone, umowa gotowa, podpisane** nie rezerwują ogłoszenia →
po 30 dniach w koszu leci `wp_delete_post(force)`. Zamówienie traci zdjęcie i nazwę auta bezpowrotnie
(brak snapshotu tytułu w meta zamówienia).

## 0.33.33 — 2026-07-20 (T-218: przebudowa listy zamówień)

**Filtry.** Kafle statystyk usunięte (2 z 6 pokazywały stale 0 — `nowe`/`zarezerwowane` to statusy
przelotowe; „Na placu" nie należy do panelu zamówień). W ich miejsce rząd trzech łączących się filtrów:
typ (`Klientów` / `Wewnętrzne` / `Wszystkie`), status z pełnej listy 13 pozycji z licznikami,
checkbox „Ukryj anulowane". Domyślne wejście: **zamówienia klientów bez anulat — 41 zamiast 140 pozycji**
(anulowane + odrzucone = 38 z 140, po stronie klientów 35 z 76).

Liczniki przy typie są dynamiczne — reagują na checkbox (41/61/102 ↔ 76/64/140). Wybór statusu anulowanego
automatycznie wyłącza checkbox (inaczej wynik byłby zawsze pusty).

**Kolumny.** „Depozyt" + „CIF" scalone w jedną „Wpłaty" (znaczniki D/C, kwota w tooltipie) — obie kolumny
pokazywały w każdym wierszu tę samą wartość. Naprawiona szerokość ID (łamał numer na dwie linie).
Wiersze anulowane wyszarzone.

**Telefon (<782 px).** Tabela ustępuje kartom: zdjęcie, tytuł (przycięty do 2 linii), status, cena, klient,
wpłata. Zero przewijania w bok, kontrolki 44–56 px. Usunięte stare reguły ukrywające kolumny przez
`nth-child` (w tym jedna z błędnym komentarzem — opisywała ukrywanie daty, faktycznie ukrywała CIF).

**Przy okazji:** wersja CSS panelu była zahardkodowana na `0.30.5`, więc przeglądarki trzymały plik z kwietnia
mimo zmian. Podpięty `filemtime()` — każda edycja CSS sama unieważnia cache.

**Strefa krucha nietknięta:** `class-asiaauto-order.php` bez zmian (mtime 2026-06-02). Filtr typu oparty
o istniejący parametr `order_type` w `getOrders()`, „ukryj anulowane" o `status` jako tablicę (`IN`).
Zmienione pliki: `includes/class-asiaauto-order-admin.php`, `assets/css/asiaauto-order-admin.css`.
Kopie: `*.bak-2026-07-20`.

## 2026-07-18 — poprawka danych: `_asiaauto_primary_make_slug` na 6 hubach (bez zmian w kodzie)

**Objaw:** 6 hubów miało `<link rel="canonical">` wskazujący poza siebie, w tym 3 na hub **innego
samochodu**. Strona z ofertami oddawała swoje wyniki cudzej marce.

| hub | canonical przed | ofert | impr/90d |
|---|---|---|---|
| `/samochody/haval/h6/` | → `/hongqi/h6/` (inne auto) | 5 | 74 |
| `/samochody/wey/07/` | → `/avatr/07/` (inne auto) | 14 | 32 |
| `/samochody/gac/m8/` | → `/aito/m8/` (inne auto) | 11 | 25 |
| `/samochody/geely/a7-em/` | → `/galaxy/a7-em/` (301 z powrotem) | 38 | 12 |
| `/samochody/gac/hyper-a800/` | → `/gac-aion-hyper/…` (301 z powrotem) | 2 | 2 |
| `/samochody/chery-fulwin/fengyun-x3l/` | → `/chery-fengyun/…` (301 z powrotem) | 1 | 0 |

**Korzeń:** osad po backfillu z kwietnia 2026 — dopasowywał termy po samym slugu, bez uwzględnienia
rodzica, a slugi `h6`, `m8`, `07` istnieją pod kilkoma markami naraz. Importer robi to już poprawnie
od v0.33.16 (T-190, z parenta — `importer.php:702`), ale te 6 nie było od tego czasu reimportowanych
(feed stoi od 01.07; oferty modyfikowane ostatnio 30.04 / 08.06 / 30.06).

Pole steruje trzema rzeczami: URL hubu (`cpt.php::filterSerieTermLink`), canonical (`seo.php`)
i prefiksem marki w title (`hub-title-generator::ensureBrandPrefix`) — stąd tytuły
„Hongqi Haval H6", „Avatr WEY 07", „AITO GAC M8".

**Diagnoza — 3 niezależne dowody, że właściwy jest rodzic:** parent w taksonomii, marka
opublikowanych ofert pod termem, oraz `_serie_api_value` („Haval H6", „Trumpchi Xiangwang M8").
We wszystkich 6 przypadkach zgodne; odstawała wyłącznie meta.

**Zmiana:** `primary_make := slug rodzica` na termach 3381, 4398, 5388, 6539, 6849, 6945.
Backup + rollback SQL: `~/backups/primaauto/2026-07-18-primary-make-fix/`.

**Dlaczego trwałe:** każda oferta pod tymi termami ma **dokładnie jedną** markę, więc gdy feed ruszy,
`updateSerieprimaryMake()` zapisze tę samą wartość. Przekierowania są od tego pola niezależne —
używają `dominantMakeSlug()` liczonej z ofert (v0.32.70, komentarz *„bywa nieaktualna"*).

**Weryfikacja:** pełny re-skan 436 wariantów URL (każdy term pod adresem z mety ORAZ z rodzica) —
rozjazdy **6 → 1**. Pozostały `/avatr/07/` → `/wey/07/` jest poprawny: pod `avatr` nie ma termu „07",
to artefakt rewrite'u akceptującego dowolną markę w ścieżce (docelowo powinien dawać 301).
Tytuły: z 423 porównanych zmieniło się 5 — 4 nasze + 1 aktualizacja ceny (`chery-fulwin/a8l`).
Oferty nietknięte (własne permalinki, canonical self); zyskały poprawne linki wewnętrzne do hubów.
Zgłoszone do Indexing API: 6/6 OK (z rezerwy, lista w `tmp/primary-make-fix-SUBMITTED-2026-07-18.txt`).

⚠ Przy okazji odpalony ręcznie cron `asiaauto_regen_hub_titles_daily` — przeliczył 290 serie + 55 make,
choć zlecenie obejmowało 6 wartości. Realny efekt pokrył się z zakresem (5 zmian), ale następnym razem
regenerację trzeba rozdzielić od poprawki danych.

## 0.33.32 — 2026-07-18 (WYCOFANE tego samego dnia — nieudana próba odblokowania hubów serii)

**Kod wgrany, funkcja WYŁĄCZONA.** Stan robots identyczny jak w 0.33.31. Wpis zostaje jako
dokumentacja błędu, żeby nikt nie powtórzył tego kryterium.

**Punkt wyjścia:** 88 hubów serii z `count=0` siedziało na `noindex` (RankMath
`noindex_empty_taxonomies=on`), mimo że renderowały oferty i miały title „N sztuk".

**Co zrobiono:** `termQualifiesForIndex()` z kryterium „`asiaauto_wiki_body` ≥ 500 **LUB**
`_asiaauto_spec_snapshot` ≥ 200" + worek marek. Odblokowało 68 hubów.

**DLACZEGO TO BYŁO BŁĘDNE — weryfikacja po fakcie:**
- **67 z 68 odblokowanych termów NIGDY nie miało ani jednej oferty** — zero wierszy w
  `term_relationships` w całej historii. Tylko `ET5` (21) i `Galaxy E8` (6) miały cokolwiek.
- 30 z 68 nie miało nawet danych technicznych — kwalifikowały się wyłącznie opisem z LLM,
  dogenerowanym hurtem w kwietniu/maju do wszystkiego, co przyszło z feedu.
- Wśród odblokowanych: `Omoda` (to nazwa marki, nie model — realne są Omoda 5 / Omoda 9),
  `Hongqi HS7` (realny wariant to `HS7 PHEV`, count=10), `Jetour X90` (realne: `X90 PLUS`,
  `X90 PRO`), `Geely ICON` (0 ofert w całej historii).
- Efekt netto byłby odwrotny do zamierzonego: wpuszczenie do indeksu thin contentu i duplikatów
  kanibalizujących huby, które realnie rankują (`Tiggo 8` vs `Tiggo 8 Pro` / `Tiggo 8 PLUS`).

**Korzeń pomyłki:** przyjąłem, że skoro hub renderuje oferty i ma opis, to jest wartościowy.
Hub renderuje oferty przez fuzzy match na nazwie/rodzicu, więc „widzę towar na stronie" NIE dowodzi,
że term jest realnym modelem. Dowodem jest historia `term_relationships` — i tej nie sprawdziłem
przed wdrożeniem, tylko po. Treść jest najsłabszym możliwym kryterium, bo opisy generowano hurtem.

**Warunki poprawnego odblokowania (na przyszłość):** historia ofert > 0 **ORAZ** dane techniczne
**ORAZ** dedupe wariantów w taksonomii. Punkt trzeci to osobna robota, nie filtr robots.

**Nie zgłoszono niczego do Indexing API** — pula czekała na decyzję i nie została użyta.
Sitemapa serii nietknięta przez cały epizod: 297.

**Sitemapa świadomie NIE ruszana.** RankMath wycina `count=0` już na poziomie query (`hide_empty`),
więc filtr `rank_math/sitemap/entry` ich nie widzi. Próba włączenia `tax_serie_include_empty`
wpuszcza ~2700 termów i przy N+1 na term_meta generacja się nie domyka — sitemapa serii spadła
297→63. **Cofnięte**, stan przywrócony. Trwałe wejście do sitemapy = osobny task (wymaga batchowania
meta). Odblokowane huby idą do Google przez `~/bin/index-submit`; crawl i tak je odwiedza —
mediana `lastCrawl` na próbce 48 hubów to 4 dni.

## 0.33.31 — 2026-07-16 (T-213: leasing w pasku zaufania + skrót treści)

Treść paska `trustLine()` po korekcie Janka:
> Cena zawiera cło, VAT, akcyzę, transport, odprawę celną, homologację i przygotowanie.
> **Możliwy leasing.**

- **Usunięte:** „— bez dopłat na odbiorze" (decyzja Janka).
- **Dodane:** „Możliwy leasing." (`.aa-trustline__fin`, `--primary` + bold).

**Zmiany:** `class-asiaauto-single.php` (`trustLine()`), `assets/css/asiaauto-single.css`
(`.aa-trustline__fin`). Smoke: treść potwierdzona na produkcji.

⚠️ **Dopisek o leasingu wyprzedza stan T-113** (plan: **GATED na partnerze finansującym**).
Otwarte u Ruslana: czy jest partner i **czy finansuje auto importowane z Chin PRZED rejestracją
w PL** (plan T-113: „kluczowe pytanie i realne ryzyko"). Jest na 3 058 ofertach — przy negatywnej
odpowiedzi do zdjęcia lub przeformułowania.

## 0.33.30 — 2026-07-16 (T-213 resztka: zdanie zaufania na ofercie)

**Wariant B** (decyzja Janka 2026-07-14). Wariant A (pełne rozbicie kosztów) odrzucony: konkurent
tym rozbiciem **zaniża podatki i ukrywa marżę w opłatach**, a wersja uczciwa **odsłaniałaby naszą
marżę** i nie dawałaby ani jednego wejścia z Google.

**Treść:** „Cena zawiera cło, VAT, akcyzę, transport, odprawę celną, homologację i przygotowanie —
**bez dopłat na odbiorze**."

**Umiejscowienie: między `keySpecs()` a `uspStrip()`** (~13% strony) — wariant C z mockupu
`primaauto-t213-zdanie-zaufania-mockup-2026-07-16.html`. Powód: wysoko (tuż po cenie i danych),
czyta się jak zdanie, a nie jak punkt listy; biały box z czerwoną krawędzią odcina się od
czerwonego USP stripa.

**Odrzucone warianty:**
- **pod ceną w sticky headzie** — cofałoby v0.33.26 (walka o kompakt: tytuł 2 linie, target 44px).
  Zdanie ma ~110 znaków = 3 linie na 375px, pasek rósłby ~2×. Sticky ma być skrótem, nie akapitem.
- **podpis w kolumnie „Dlaczego my"** — ginie w czerwieni jako 13. pozycja listy haseł.

**Zmiany:**
- `class-asiaauto-single.php` — nowa `trustLine()`, wpięta w `render()` między keySpecs a uspStrip
- `assets/css/asiaauto-single.css` — `.aa-trustline` (biały box, `border-left: 3px solid var(--accent)`, ikona tarczy SVG)

**Kontekst:** `uspStrip()` ma hasło **„Transparentna cena"** — obietnicę bez pokrycia. To zdanie
jest jej pokryciem. ⚠️ Do rozważenia: hasło pada teraz dwa razy pod rząd (pasek + punkt w USP) —
ewentualnie usunąć je z USP.

**⚠️ Link do kalkulatora NIE dopięty** — kalkulator (T-213 pkt 1, osobna strona) **nie istnieje**.
`TODO` w kodzie; dopięcie to 1 linia, gdy powstanie.

**Smoke:** 3 paliwa (PHEV/benzyna/EV) → 1 wystąpienie każde. Kolejność `trustline` przed `usp-strip`
potwierdzona. CSS pluginu bustuje się sam (`filemtime`, `ver=1784216574`) — theme nietknięty,
więc `PRIMAAUTO_THEME_VERSION` bez zmian.

**Backupy:** `class-asiaauto-single.php.bak-2026-07-16-t213`, `asiaauto-single.css.bak-2026-07-16-t213`

## 0.33.29 — 2026-07-16 (T-187 fix: blok „Inne egzemplarze" na ofertach BEZ extra_prep)

**Bug (znalazł Janek, przykład `/oferta/hongqi-h9-2024-387815/`):** blok wpięty w `renderTechSpecs()`
dziedziczył jej early return — `if (empty($ep)) return '';` → **139 ofert (4,5%) nie dostawało bloku
w ogóle**. Blok nigdy nie zależał od `extra_prep` (potrzebuje tylko taksonomii `serie`); to był
skutek uboczny miejsca wpięcia.

**Fix (`class-asiaauto-shortcodes.php`, 2 linie):** zamiast pustki — sam blok.
```php
if (empty($ep))       return $this->otherUnitsBlock($post_id);
if (empty($sections)) return $this->otherUnitsBlock($post_id);
```

**Smoke:** `/oferta/hongqi-h9-2024-387815/` (0 sekcji tech) → „Inne egzemplarze Hongqi H9 (5)",
4 karty, CTA OK. `/oferta/aito-m9-2024-217558/` (6 sekcji) → blok po 2., **1 wystąpienie** (brak
duplikacji). Regresja hubów/homepage/katalogu: bez zmian (blok nie odpala się poza ofertą).

### Dlaczego 139 ofert nie ma extra_prep — diagnoza (NIE naprawiane, decyzja Janka)

Hipoteza „Ruslan dodawał ręcznie, powinno się podłączyć" **obalona danymi**: ręcznych jest **6**,
z importu **133** (mają `_asiaauto_inner_id`), wszystkie z lipca 2026.

Przyczyna = **awaria dostawcy auto-api od 01.07** ([[project_dongchedi_feed_frozen_2026_07_07]]):
`/changes` daje dane częściowe BEZ extra_prep, pełną specyfikację dokłada `getOffer()` per oferta;
gdy `getOffer()` pada → `importWithFullData` świadomie zapisuje partial + `WARN: getOffer failed…
using partial data`. Kod syncu nietknięty od 16.05 — nasz kod OK, wina źródła.

**Decyzja Janka (2026-07-16, podtrzymana z 07-07): NIE backfillować teraz** — najpierw wyświetlanie
bloku, extra_prep osobno. Dane odzyskiwalne (`getOffer()` już odpowiada → re-sync uzupełni).

## 0.33.28 — 2026-07-16 (T-187: blok „Inne egzemplarze {Model}" na stronie oferty)

**Po co:** klient na ofercie nie wiedział, że ten sam model stoi u nas w innych sztukach, często
taniej — musiał wracać do katalogu i szukać ręcznie. 2 526 z 3 058 ofert (83%) dzieli identyczny
`post_title` z inną ofertą (499 grup; AITO M9 ×133, Voyah Dream ×39). Cel Janka: „żeby ludzie
widzieli, że to nie jedyny egzemplarz, jaki mamy".

**Zero nowego kodu renderującego — reuse istniejącego komponentu** (decyzja Janka: „mamy już te
metody na stronie głównej i hubach, więc tylko odpytanie konkretnych ofert, nie pisz od nowa").

### Zmiany

**`class-asiaauto-brand-hub.php`** — 4 addytywne zmiany w `[asiaauto_hub_listings]`, wszystkie
z pustymi domyślnymi → **hub i homepage bez zmian**:
- nowe atrybuty: `make`, `serie` (fallback, gdy brak query var — poza hubem ich nie ma),
  `exclude` (CSV → `post__not_in`), `orderby` (`price` = cena rosnąco)
- `renderListingsCompact()` + parametry `$exclude`, `$orderby`

**`class-asiaauto-shortcodes.php`**:
- nowa `otherUnitsBlock(int $post_id)` — pobiera `make`/`serie` posta, guard `count-1 < 1`,
  woła `do_shortcode('[asiaauto_hub_listings n=4 …]')`
- `renderTechSpecs()` — licznik wyrenderowanych sekcji, wstawka **po 2.**, fallback na koniec

### Decyzje

- **Umiejscowienie: po 2. wyrenderowanej sekcji technicznej** (~39% strony) — pomysł Janka:
  „klient mobile już wie, że przewija zestawy danych, więc kolejny zestaw pasuje". Janek:
  „to nie ma znaczenia, bo jak już to mamy gotowe, to zawsze możemy przenieść".
- 🔴 **Kotwica po INDEKSIE, nie po nazwie sekcji** — „Układ elektryczny" nie istnieje dla ~24%
  katalogu (spalinowe). Potwierdzone smoke'em: EV ma 5 sekcji, spalinowe 6 — blok i tak po 2.
- **H2 = `Inne egzemplarze {serieAnchor} ({n})`** — niesie frazę `{marka} {model}`, ta sama nazwa
  co breadcrumb i sticky navrow (v0.33.23).
- **CTA → zafiltrowany katalog** `/samochody/?marka=X&model=Y`, **nie hub**. Powód (dane, nie opinia):
  hub pokazuje tylko ~11 kart przy 133 sztukach AITO M9, a **hub sam linkuje „Wszystkie (133) →"
  właśnie na filtr** — powtarzamy istniejący wzorzec. Filtr = `noindex, follow` (equity przepływa).
  Do huba klient ma już dwie drogi z góry strony (breadcrumb + navrow), trzecia byłaby powtórzeniem.
- **Sort: cena rosnąco** — sens konwersyjny bloku to „jest taniej".

### Smoke (po deployu)

| paliwo | oferta | blok | pozycja |
|---|---|---|---|
| EV | BYD Han EV | „Inne egzemplarze BYD Han EV (8)" | po 2 z 5 sekcji |
| benzyna | Hongqi H5 | „(93)" | po 2 z 6 |
| PHEV | Denza Z9 DM-i | „(14)" | po 2 z 6 |
| AITO M9 | 4 karty, ceny 283→297→302→304 tys. | cena rosnąco OK | CTA → `?marka=aito&model=m9` |

- **Guard**: oferta-unikat (`serie.count=1`) → 0 wystąpień bloku, brak pustej ramki.
- **`post__not_in`**: własny URL nie występuje w bloku (tylko oembed/share).
- **Regresja**: homepage, katalog (s1/s3/filtr), hub-zeekr-8x, hub-aito-m9, hub-denza-d9 —
  **HTML identyczny co do znaku** vs baseline sprzed zmiany.

**Backupy:** `class-asiaauto-brand-hub.php.bak-2026-07-16-t187`, `class-asiaauto-shortcodes.php.bak-2026-07-16-t187`

### 🐛 Znalezisko poboczne (NIE naprawiane) — niestabilne sortowanie kart marek

Przy weryfikacji regresji wyszło, że `aa-brand-card` („modele marki" na hubach) ma **losową
kolejność między pobraniami** — dwa `curl` pod rząd, **tym samym kodem**, dają 8 różnic
(hub-hongqi-h9, hubmarka-zeekr/geely). Prawdopodobnie `ORDER BY count` bez tie-breakera →
MySQL zwraca dowolną kolejność przy równych wartościach. **To nie jest regresja T-187** —
potwierdzone testem `n1 vs n2` na niezmienionym kodzie. Karty ofert (`aa-home__car`) są stabilne.
Skutek: diff HTML hubów jest niewiarygodny dla sekcji marek. Do rozważenia: tie-breaker po `term_id`.

## 0.33.27 — 2026-07-16 (T-211 ⚡2: kolor w schemacie ofert)

**Geneza:** wątek 4b. Blok Schema.org w `renderMeta()` pytał o taksonomię `color`, która
**nie istnieje** — w bazie są `exterior-color` (13 termów) i `interior-color` (13). Wyrażenie
zawsze zwracało `''`, a `array_filter` wycinał puste pole → **kolor nie trafiał do schematu
żadnej z 3 058 ofert**. Reszta pluginu znała poprawną nazwę (linia 136 używa `exterior-color`
od dawna) — to była literówka w jednym miejscu, nie świadomy wybór.

**Zmiana (`class-asiaauto-single.php:1047`, 1 linia):**
- `get_the_terms($pid, 'color')` → `get_the_terms($pid, 'exterior-color')`

**Pokrycie:** 2 906 z 3 058 opublikowanych ofert (95%) ma przypisany `exterior-color`.
Rozkład: Czarny 807, Biały 540, Ciemnoszary 423, Srebrny 347, Niebieski 296, Zielony 213,
Fioletowy 129, Czerwony 65, pozostałe <35.

**Smoke (3 paliwa, po deployu):** EV `byd-han-ev-2024-186099` → „Ciemnoszary";
PHEV `denza-z9-dm-2024-94535` → „Ciemnoszary"; benzyna `hongqi-h5-2025-186609` → „Czarny".
Pozostałe pola (`fuelType`, `vehicleTransmission`, `itemCondition`) bez zmian.

**Backup:** `class-asiaauto-single.php.bak-2026-07-16-t211-color`

### Ustalenia analityczne wątku 4b (bez zmian w kodzie)

- **Hipoteza „multi-type [Product, Car] blokuje liczenie ofert jako Product" — OBALONA.**
  URL Inspection API (3 huby + 4 oferty): **wszystkie PASS jako „Opisy produktów"**, oferty
  również, z nazwą wersyjną. Multi-type nie przeszkadza. Nie ma tu czego naprawiać.
- **„79 opisów produktów = tylko huby" — artefakt raportu, nie bug.** Raport rich resultów
  pokazuje próbkę URL-i i liczy tylko przecrawlowane. Huby są stabilne, oferty rotują
  (część URL-i z GSC jest martwa, 301 na hub).
- **Huby jako Product + AggregateOffer (lowPrice/highPrice) = poprawny, celowy wzorzec.**
  Daje „od X zł" w SERP na frazach „cena", gdzie huby stoją #1–2. Zostawiamy.
- **ZAD.12/13 („utracone pola schema") — FAŁSZYWY ALARM z nieaktualnego backlogu.**
  `vehicleEngine`, `vehicleTransmission`, `driveWheelConfiguration`, `itemCondition`
  oraz `OfferShippingDetails` **są w kodzie żywym i lecą na produkcję** (potwierdzone curlem).
  Backlog opisywał funkcję `schema()` (linia 632), która jest **martwym kodem** — nikt jej
  nie woła (komentarz w 1043 stwierdza to wprost: Elementor używa pojedynczych shortcodów).
- **WARNING GSC „brak review / aggregateRating"** na hubach i ofertach — pola opcjonalne.
  **Nie dodajemy** — nie mamy prawdziwych recenzji, fabrykowanie = ryzyko manual action.

### T-199 resztka „Prima Auto" → „Prima-Auto" — ODRZUCONE na danych (decyzja Janka)

Punkt 4 promptu 4b zakładał ujednolicenie zapisu. **Nie robimy.** Powody:
- **Myślnik jest dla Google separatorem słów, nie łącznikiem** — `Prima-Auto` tokenizuje się
  jako „prima"+„auto". Zapis ze spacją i z myślnikiem są wyszukiwarkowo równoważne.
- **Dowód empiryczny (GSC 90d, kliki brandowe):** `prima auto` (ze spacją) **343**,
  `primaauto` 34, `prima-auto` 13. Zapytanie ze spacją rankuje **poz. 2,7** („prima auto
  rzeszów" — 1,3) **mimo** że strona wszędzie pisze `Prima-Auto`. Gdyby myślnik sklejał
  w jeden token, tych 343 klików by nie było.
- **Wniosek:** zysk zerowy w obie strony; to wybór brandingowy, nie SEO. Janek preferuje
  „Prima Auto" (2 encje) — desc już tak mówi, więc **najtańszy ruch to żaden**.
  Niespójność title (`| Prima-Auto`) ↔ desc (`Prima Auto`) **zostawiona świadomie**.
- **Unikać:** `Prima - Auto` ze spacjami wokół myślnika (czytane jak separator tytułu →
  „Prima" + tagline „Auto").
- **NIE odwracać kierunku na całości** (blogname, schema `seller.name`, title 3 056 ofert)
  — duża zmiana bez zysku, ruszyłaby title w trakcie pomiaru do 27.07.
- Znalezisko poboczne: „Prima Auto" występuje w **12 miejscach**, nie w jednym (jak mówił
  prompt) — 10 z nich to szablony **title** (686-695), fallback aktywny gdy `offerTitleV2`
  wyłączone. Nie ruszane. Do rozstrzygnięcia po pomiarze 27.07, jeśli w ogóle.

## 0.33.26 — 2026-07-16 (T-203: UX/dostępność sticky-heada + jeden H1 + desktop back→hub)

**Geneza:** zrzut Janka z telefonu. „Trudno trafić palcem" — link „← {model}" miał `font-size: 12.5px`
bez paddingu, czyli **target ~16px**, poniżej minimum WCAG 2.2 SC 2.5.8 (24×24) i daleko od
Apple HIG (44). Auranet robi audyty EAA/WCAG — tym bardziej nie u nas.

**Zmiany (`asiaauto-single.css` + `class-asiaauto-single.php`):**
- **Target 44px** — nakładka `::after { inset: -14px -8px }` na `.aa-single__hubback`.
  Palec dostaje 44px, layout **nie rośnie o piksel** (link zostaje w linii z badge'ami). Font 12.5→13px.
- **Tytuł: 2 linie** (`-webkit-line-clamp: 2`) zamiast `nowrap`+ellipsis, 15→16px. **SEO bez zmian**
  — ellipsis był czysto wizualny, pełny `post_title` zawsze był w DOM i Google go czytał. Ale przy
  20+ egzemplarzach tego samego modelu **wersja** jest jedynym wyróżnikiem — i to ona wypadała
  poza ellipsis („Xiaomi YU7 Max 4WD…"). Czysta decyzja UX/CTR, nie rankingowa.
- **Cena 18→22px**, netto 12→13px, `z VAT` jako dopisek (`.aa-single__price-vat`, `.55em`, szary).
  Cena > tytuł **świadomie** — klient zna model (z listy/H1/linku obok), przychodzi po cenę; wzorzec mobile.de.
- **Pastylki**: padding 4/10→6/10, font 12→12.5px.
- **pkt 1 — JEDEN H1**: `stickyHead()` dostał `bool $as_h1`. Kopia **mobilna** = `<h1>`,
  desktopowa (sidebar) = `<div role="heading" aria-level="1">`. Powód: Google indeksuje mobile-first,
  a kopia nieaktywna ma `display:none` — czyli wcześniej JEDEN z dwóch H1 był zawsze schowany
  przed indekserem. Zweryfikowane: `<h1>` = 1, `role="heading"` = 1.
- **Desktop `.aa-single__back`**: „Wróć do wyników" → `/samochody/` (cały katalog 3 000 aut = donikąd
  konkretnie) → **„← Wszystkie oferty {model}"** → `hub#oferty`. Ta sama zasada co na mobile,
  ten sam `serieAnchor()`. Fallback na /samochody/ gdy oferta bez taksonomii serie.
  Target: `.aa-single__back` miał już `padding: 14px 0` = 44px, bez zmian.

---

## 0.33.25 — 2026-07-16 (T-203: kotwica #oferty na hubie + cache-bust themu)

**Po co:** link „← {model}" z oferty rzucał klienta na górę hubu (H1 + lead + wiki + FAQ), a on
przychodzi **z oferty tego modelu** — model zna, chce zobaczyć inne egzemplarze.

- **`[asiaauto_hub_listings]` + atrybut `anchor`** (domyślnie pusty → pozostałe użycia, m.in. homepage,
  bez zmian). Renderuje `id` na `.aa-hub__latest-wrap`.
- **`taxonomy-serie.php`**: bar ofert dostaje `anchor="oferty"`. Na hubie są 4 takie bary
  (oferty / w drodze / na placu) — **id dostaje tylko jeden**, zweryfikowane.
- **`hubBackLink()`**: URL + `#oferty`.
- **`hub.css`**: `scroll-margin-top: calc(var(--header-h) + 40px)` (+86px dla `body.admin-bar`) —
  bez tego nagłówek „Oferty {model}" wlatuje pod sticky `.pa-header`.
- **SEO bez zmian**: Google ignoruje fragment przy indeksacji (`/samochody/zeekr/8x/#oferty`
  = ten sam URL), equity płynie do hubu tak samo. Fragment działa tylko u użytkownika.

**⚠️ GOTCHA (kosztowała pół godziny): theme NIE ma cache-bustingu po `filemtime()`** — style idą
z hardcodowanej stałej `PRIMAAUTO_THEME_VERSION` w `functions.php` (`hub.css?ver=1.0.8`).
Zmiana CSS bez bumpu = przeglądarka serwuje starą kopię i zmiany „nie działają". Bump 1.0.8→**1.0.9**.
**Każda zmiana w `themes/primaauto2026/assets/css/*` wymaga bumpu tej stałej.** Plugin busta się sam.

---

## 0.33.24 — 2026-07-16 (T-203: link do hubu w sticky navrow + „z VAT")

**Pomysł Janka:** wstawić link do hubu w tę samą linię, co pastylki „Hybryda"/„W drodze" — wtedy
jedzie ze sticky-headem (widoczny przez CAŁY scroll) i **nie kosztuje ani piksela wysokości**,
bo rząd z badge'ami i tak tam był. Lepsze niż breadcrumb jako osobna linia (0.33.23) — na mobile
nad tytułem jest ciasno.

- **`hubBackLink()`** (nowa) — anchor z `AsiaAuto_Shortcodes::serieAnchor()`, ta sama funkcja co
  breadcrumb i JSON-LD, żeby trzy miejsca nie mówiły trzech różnych rzeczy.
- **`.aa-single__navrow`** — flex `[← {model}]` + `[badge'y]`; `min-width: 0` + `ellipsis` na spanie,
  więc długie nazwy („BYD Leopard 5 (Denza B5)") skracają się zamiast rozpychać badge.
  **Tylko `--mobile`** — desktop ma pełny breadcrumb, drugi link byłby duplikatem i rozjechałby sidebar.
- **`.aa-single__sticky-back` usunięta z markupu** (prowadziła do `/samochody/`).
  `grid-template-columns: 32px 1fr` → `1fr` — **tytuł odzyskał 42px**.
- **Breadcrumb `<nav>` z powrotem `display:none` na mobile** (cofnięte z 0.33.23) — rolę przejął
  navrow: ten sam URL i ten sam anchor, więc bez rozjazdu; `<nav>` zostaje w DOM + JSON-LD dla SERP.
- **Cena: `246 000 PLN z VAT`** (`.aa-single__price-vat`) — prośba Janka.

---

## 0.33.23 — 2026-07-16 (T-203 pkt 7+8: breadcrumb kompakt na mobile + anchor pełną nazwą modelu)

**Po co:** 3 056 ofert linkowało do hubów breadcrumbem ukrytym na mobile (`display:none`
w `@media max-width:768px`) — a mobile to **79,6% sesji** (GA4 90d, memory
`reference_mobile_share_and_offers_are_conversion_pages`). Do tego anchor był surowym
`$serie->name`, czyli **258 z 302 modeli (2 908 ofert) linkowało do hubu napisem bez marki** („8X").
Hub walczy o „zeekr 8x", a dostawał od 3 000 własnych podstron link „8X".
Komentarz w CSS (`Hide breadcrumb … SEO intact`) był **mylący**: JSON-LD ratuje wygląd ścieżki
w SERP, nie link equity.

**`AsiaAuto_Shortcodes::serieAnchor()`** (nowa, `public static` — wołana z `renderBreadcrumb()`
i z JSON-LD BreadcrumbList w `class-asiaauto-single.php:1061`, żeby się nie rozjechały).
Baza = `_serie_full_title` (kuratorowane: bez nawiasów, bez dubletów marki, case marki,
encje) + 3 korekty. **Zasada:** anchor prowadzi do HUBA, więc niesie frazę huba (`{model}`);
frazę wersyjną niesie title/H1 oferty (decyzja Janka: „zeekr 8x → hub, zeekr 8x ultra → oferta").
1. **zdejmij** napęd nieobecny w nazwie termu → `Zeekr 8X PHEV` = **`Zeekr 8X`**,
2. **dopnij** napęd obecny w nazwie termu, a zgubiony przez full_title → `BYD Han` = **`BYD Han DM-i`**,
3. **alias marki** (lustro `V3_BRAND_ALIAS`) → `Beijing 212 T01` = **`BAW 212 T01`**.
Fallback dla 35 serii bez full_title: make+name z guardem antydubletowym („IM LS7" vs „IM Motors").

**BUG wyłapany na symulacji 100 serii — bez kroku 2 sami tworzylibyśmy kanibalizację:**
`Sealion 5 DM` i `Sealion 5 EV` to **dwa różne huby**, a `full_title` dawał obu identyczne
„BYD Sealion 5". Kolizje anchora na całej puli: **1 → 0**. Symulacja:
`auratest .../primaauto-t203-sym-anchor-100-2026-07-16.html`.

**Breadcrumb kompakt (`asiaauto-single.css`, `@media max-width:768px`):** breadcrumb odsłonięty,
ale zredukowany do JEDNEGO poziomu — link do hubu modelu (`--root`/`--make`/`--current`/`--sep`
chowane, `--serie` z prefiksem `←`). **`aa-single__sticky-back` usunięta z mobile** (prowadziła do
`/samochody/`, czyli do całego katalogu; nawigację przejął breadcrumb → hub modelu).
`grid-template-columns: 32px 1fr` → `1fr` — **tytuł odzyskał 42px** (mniej ellipsis).
`margin-top: -16px` → `0` (nad sticky-head jest teraz breadcrumb; -16px wciągałby go pod spód).
Hub marki nie traci: dostaje link z każdego z 302 hubów modeli (`taxonomy-serie.php:71-95`).

**Huby NIETKNIĘTE** — zweryfikowane grepem przed zmianą (`titleBaseV3`/`v4*`/`v3BrandPrefix`
tylko w `class-asiaauto-single.php`; `ensureBrandPrefix` tylko w `class-asiaauto-hub-title-generator.php`;
`[asiaauto_tech_specs]`/`[asiaauto_equipment]` wołane wyłącznie z `class-asiaauto-single.php:93-94`)
i po deployu na żywo (title/H1 `/zeekr/8x/`, `/byd/han-dm-i/`, `/denza/n8l-dm/` bez zmian).

**Znalezisko uboczne → `docs/seo/znaleziska-huby-2026-07-14.md` Z4:** ten sam defekt
`_serie_full_title` **już produkuje zduplikowane title hubów** — `/byd/sealion-5-dm/` i
`/byd/sealion-5-ev/` mają IDENTYCZNY title „BYD Sealion 5"; `/byd/han-dm-i/` = „BYD Han"
(bez frazy „byd han dm-i"). 13 serii dotkniętych. Anchor jest teraz precyzyjniejszy niż hub,
do którego prowadzi. Naprawa hubów = osobna decyzja (baseline + próg rollback).

**Smoke:** 3 oferty różnych paliw (PHEV/EREV/benzyna) HTTP 200, anchor i JSON-LD BreadcrumbList
zgodne; 6 kluczowych serii przez `serieAnchor()` na produkcji = wartości z symulacji.
Backupy: `.bak-2026-07-16-t203-anchor` (×2), `.bak-2026-07-16-t203-bc` (CSS).

**NIEZROBIONE z tego zakresu:** pkt 1 (drugi H1 → `div role=heading`; `stickyHead()` wołany
2× — `:85` mobile i `:314` w `sidebar()`) — zaakceptowany, ale test mobile-first→desktop
nie wykonany (Chrome nie dał mobilnego viewportu). Pkt 2 (NAP „Prima Auto"→„Prima-Auto"
w `renderMeta()`) — do decyzji, zysk SEO zerowy. T-187 (box „inne oferty tego modelu")
— świadomie osobno, wymaga T-212.

---

## 0.33.22 — 2026-07-14 (T-203 v4: hoist wersji tuż za nazwę modelu)

**Po co:** sam przerzut rocznika (0.33.21) nie wystarczył — feed wstawia napęd/baterię PRZED wersję,
więc fraza dalej się rozjeżdżała: `Xiaomi YU7 4WD Max` nie zawiera ciągu „xiaomi yu7 max" (6 600/mc),
`Zeekr 8X 55kWh Ultra` nie zawiera „zeekr 8x ultra" (2 900/mc). Symulacja 120 ofert zaakceptowana
(`auratest .../primaauto-t203-v4-hoist-symulacja-2026-07-14.html`).

**`v4HoistTrim()`** — token wersji przesuwany tuż za nazwę modelu. **Kotwicą jest nazwa termu `serie`**
(`YU7`, `8X`, `L9`, `Galaxy M9`, `212 T01`), nie heurystyka po liczbie tokenów — wiemy dokładnie,
gdzie kończy się model. Wersja jedzie razem z doklejonym „Edition"/„Version", żeby nie zostawiać sierot.
Efekt: `Xiaomi YU7 Max 4WD 2025`, `Zeekr 8X Ultra 55kWh 2026`, `Avatr 11 Max Facelift RWD EREV 2025`,
`AITO M9 Ultra EREV 6-osobowy 2025`, `BYD Leopard 5 (Denza B5) Ultra 125KM 2025`.

**`v4FindTrim()` — BUG wyłapany na smoke-teście, NIE puszczać naiwnego „pierwsze trafienie":**
`Xiaomi YU7 2025 AWD **Ultra Long Range** … **Max Version**` → naiwny hoist brał „Ultra" (opis
zasięgu baterii!) i produkował **`Xiaomi YU7 Ultra` — wersję, która NIE ISTNIEJE** (YU7 ma
Standard/Pro/Max). Zmyślony wariant jest gorszy niż brak hoistu. Kolejność wyboru jest teraz jawna:
1. trim + „Edition"/„Version" (najpewniejszy marker realnego wariantu: „Max Version"),
2. samotny trim, ale NIE taki, po którym idzie „(Long) Range" ani myślnik („Ultra-long"),
3. nic — tytuł zostaje nietknięty.

**Świadomy kompromis:** `Xiaomi SU7 RWD **Standard** Long Range Edition` NIE dostaje hoistu, bo
strażnik odrzuca każdy trim przed „Long Range" — a „Ultra Long Range" (zasięg) i „Standard Long
Range" (wersja) są strukturalnie nieodróżnialne bez słownika wersji per model. Tracimy przyleganie
na „su7 standard", ale nie ryzykujemy powtórki z „YU7 Ultra". Audyt całej bazy (765 par
serie→przypisana wersja) — zero wymyślonych wariantów.

**Zakres:** WYŁĄCZNIE oferty (`is_singular('listings')`). Huby jadą osobnym generatorem
(`class-asiaauto-hub-title-generator.php`) i są NIETKNIĘTE — zweryfikowane na żywo po deployu.

**⚠️ Kanibalizacja NIE jest rozwiązana i hoist ją ZAOSTRZA.** Wcześniej 30 ofert YU7 miało
`Xiaomi YU7 4WD Max` (żadna nie pasowała exact do „xiaomi yu7 max"); teraz każda z 30 zawiera tę
frazę idealnie → są dla Google równie trafne i tym mocniej biją się o nią nawzajem. Canonical tego
NIE naprawi: każda oferta jest self-canonical i **słusznie** — to fizycznie różne auta (inny VIN,
cena, przebieg), nie duplikaty; kanonikalizacja 29→1 wyrzuciłaby realny towar z long-taila.
Lek = **strategia championa** (jedna oferta per wersja linkowana z huba anchorem „{model} {wersja}",
reszta zostaje na frazach z ceną/przebiegiem). To NASTĘPNY task — bez niego v4 sam z siebie nie
przełoży się na pozycje. Dowód skali: `li auto l9` = 5 naszych ofert po 1 impresji na poz. 29–63,
TNT jedną stroną na #20.

## 0.33.21 — 2026-07-14 (T-203 v3: szyk tytułu ofert {Marka} {Model} {Wersja} {Rok})

**Geneza (dane, nie intuicja):** GSC 90 dni — frazy z WERSJĄ (max/ultra/hyper) = 3 413 impresji,
z ROKIEM = 1 117, kombinacja „model + rok + wersja" = 303 (szum). Do tego ta sama wersja wraca
w kilku rocznikach (Li Auto L9 Ultra: 2024 i 2025), więc rok jest **rozróżnikiem** — jego miejsce
jest w ogonie, nie w środku. Stary szyk rozrywał frazę: „Zeekr 9X **2025** Ultra" nie zawiera ciągu
„zeekr 9x ultra"; nowy („Zeekr 9X Ultra 55kWh 2025") zawiera. Symulacja 114 ofert zaakceptowana
przed wdrożeniem (`auratest .../primaauto-t203-v3-szyk-symulacja-2026-07-14.html`).

**Wdrożone w `class-asiaauto-single.php` (wszystko RENDER-ONLY, `post_title` w DB nietknięty):**
- `v3YearLast()` — rocznik ze środka bazy na koniec. Regex `\b(19|20)\d{2}\b` nie łapie `2.0T`,
  `4WD`, `70kWh`, `1400PS`. No-op gdy rocznik już na końcu albo baza to sam rocznik.
- `v3BrandPrefix()` — marka z taksonomii `make`, gdy nie ma jej w tytule (**55 ofert publish**:
  Geely 34 — cały Galaxy M9, BAW 8, Lynk & Co 3). Guardy przeniesione z `hub-title-generator`
  (2026-07-13): `html_entity_decode` na encję `&amp;` + porównanie pierwszego tokenu („IM LS7"
  vs marka „IM Motors" — marka de facto już jest w nazwie).
- `V3_BRAND_ALIAS` — feed niesie inną nazwę tej samej marki: `Beijing 212 T01` + `make=BAW`
  → **`BAW 212 T01`** (podmiana, nie doklejanie prefiksu). TNT rankuje #12 na „baw" (1 300/mc)
  i #8 na „baw 212 gdzie kupić" (480/mc) — u nas token „BAW" nie występował nigdzie na stronie.
- `v3StripCjk()` — chińskie znaki z importu Dongchedi (**40 ofert publish**), np.
  `Beijing 212 T01 2024 2.0T 检阅官`, `Galaxy 银河A7 EM`, `MG Cyberster 580km Super 然致远`.
- `titleBaseV3()` — kompozycja trzech wyżej + static cache per `$pid`.

**Podpięte pod:** `buildTitleV2()` (meta title), `h1WithVariantSuffix()` (H1), `renderMeta()`
(meta description + `og:title`). **NIE podpięte pod `dataLayer.item_name`** — karmi GA4 i katalog
Meta, to nie warstwa SEO. Zapytania o duplikaty (`titlePriceCollides`, dupe-check w H1) nadal lecą
po **oryginalnym** `post_title`, bo to on siedzi w `wp_posts` — v3 to warstwa wyświetlania.

**Huby NIETKNIĘTE** (decyzja Janka z 2026-07-13 podtrzymana). Rollback: opcja
`asiaauto_offer_title_v2_series` = `""`. Backup: `.bak-2026-07-14-t203v3`.

**Znane, NIENAPRAWIONE (do decyzji):** feed czasem wstawia napęd/baterię PRZED oznaczenie wersji,
więc na dwóch największych frazach przyleganie dalej nie wychodzi: `Xiaomi YU7 **4WD** Max 2025`
(fraza „xiaomi yu7 max", 6 600/mc) i `Zeekr 8X **55kWh** Ultra 2026` („zeekr 8x ultra", 2 900/mc).
Naprawa = hoist tokenu wersji tuż za nazwę serii z taksonomii — osobny task, wymaga symulacji.
Osobno: jedna oferta ma tytuł zaczynający się od `[` (śmieć z feedu).

## 0.33.20 — 2026-07-13 (T-203/desc: meta description ofert v2 — pełny opis + „bezpośredni importer")

**Decyzje Janka:** hubów NIE ruszamy (fix „1 egzemplarzy" w generatorze hubów ODRZUCONY — zostaje
jako znany defekt); desc ofert maksymalnie długi, końcówka „Prima Auto — bezpośredni importer".
Symulacja 100 ofert zaakceptowana przed wdrożeniem (`auratest .../primaauto-sym-desc-100-ofert-2026-07-13.html`).

**Zmiana (`renderMeta()` w `includes/class-asiaauto-single.php`, backup `.bak-2026-07-13-desc`):**
- wzorzec: `{base} — {FUEL_SHORT}, {moc} KM (gdy w extra_prep), {przebieg} km, {cena} PLN.
  [Dostępny od ręki w Rzeszowie.] Prima Auto — bezpośredni importer samochodów z Chin.`
- usunięte błędne „od {cena}" (konkretny egzemplarz ≠ zakres huba), paliwo skrótem (EV/EREV/PHEV —
  pełne nazwy typu „elektryczny z range extenderem (erev)" rozsadzały limit 160 zn),
  moc przez `AsiaAuto_Inventory::resolvePower()` na surowym extra_prep (bez translatora).
- Symulacja na próbce: śr 128 zn (było ~100), max 163, 1/100 ponad 160, moc w 30/100 najnowszych
  (świeże importy bez extra_prep — zamrożony feed; w starszej bazie pokrycie ~95%).

## 0.33.19 — 2026-07-13 (T-203/H1: różnicowanie H1 ofert kaskadą przebieg→cena)

**Powód:** Screaming Frog Janka — 2 524 publish ofert dzieliło H1 z inną ofertą (H1 = `post_title`,
feed wozi po kilkadziesiąt egzemplarzy tej samej wersji). Decyzja Janka: różnicować od razu
(pierwotnie KROK 3b czekał na pomiar).

**Zmiana (`includes/class-asiaauto-single.php`, backup `.bak-2026-07-13-h1`):**
- nowa `h1WithVariantSuffix()` — gdy inna publish-oferta ma identyczny `post_title`, H1 dostaje
  ` - {przebieg} km`; gdy bliźniak ma też ten sam (lub zerowy) przebieg → dodatkowo `, {cena} PLN`.
  Render-only: `post_title` w bazie nietknięty (umowy/feedy/dataLayer/WhatsApp bez zmian).
- stickyHead renderuje H1 przez nową metodę (obie kopie: mobile+desktop); static cache per pid
  (2 wywołania per stronę = 1 zestaw query). Gate: ten sam `offerTitleV2()` (obecnie `*`).
- Efekt zmierzony SQL: duplikaty H1 2 524 → **99** (bliźniaki identyczne tytułem+przebiegiem+ceną
  naraz — brak kolejnego sensownego wyróżnika; te same 95-99 sztuk dzieli też title/desc).
- Smoke: para YU7 4WD Max (7 000 vs 30 000 km), czwórka AITO M5 z parami o identycznym przebiegu
  (rozróżnione ceną), unikalny tytuł bez suffixu.

## 0.33.18 — 2026-07-13 (rollout title v2 na WSZYSTKIE oferty + fix ensureBrandPrefix — 9 hubów)

**Kontekst:** plan naprawy po ocenie szkód (duplikaty title/H1 nieobjęte żadnym audytem; rotacja
title aktywna od v0.32.36/2026-05-06 bez weryfikacji unikalności — 1 012 ofert ze zdublowanym
title poza pilotem, H1: 2 524 oferty/499 grup — otwarte jako KROK 3b w specu T-203).

**Zmiany:**
- **Rollout title v2**: option `asiaauto_offer_title_v2_series` = `*` (z pilotowego CSV) —
  wszystkie ~4 500 ofert na wzorcu `{base} - {cena} PLN [...]`; likwiduje 1 012 duplikatów title,
  resztki „Używane" i spam-szablonów. Bez zmian w kodzie. Rollback selektywny = wpis CSV term_id.
- **Fix `ensureBrandPrefix()`** (`includes/class-asiaauto-hub-title-generator.php`, backup
  `.bak-2026-07-13-brandprefix`): (1) `html_entity_decode` obu stron porównania — encja
  `&amp;` w term name dublowała „Lynk & Co Lynk &amp; Co 03/06/07/08/10"; (2) porównanie
  tokenowe pierwszego słowa — „IM LS7" vs marka „IM Motors" nie dublują już prefixu;
  (3) mapa marek + `im-motors` → „IM Motors", `baw` → „BAW".
- Regeneracja `rank_math_title` 8 hubów (regenerateForTerm) + ręczny fix termu 6601
  (Lynk & Co 10 EM-P, count=0 → generator go pomija by design, stale meta poprawione str_replace).
  Smoke live: lynk-co-03, im-ls7, baw/t01 — czyste.

## 0.33.17 — 2026-07-13 (T-203: title ofert v2 — wersja + cena, pilot 9 serii)

**Powód:** analiza konkurencyjna tntcars.pl — frazy wersyjne (największy wolumen) przegrywane
przez title z rotacji 10 szablonów (`inner_id % 10`): duplikaty title w obrębie tej samej wersji
(6/10 na próbce YU7), prefiks „Używane" psujący intent, brak ceny/wyróżnika. Spec:
`docs/seo/T-203-oferty-wersje-title-spec-2026-07-13.md`, baseline: `docs/seo/T-203-baseline-gsc-2026-07-13.md`.

**Zmiana (`includes/class-asiaauto-single.php`, backup `.bak-2026-07-13-t203`):**
- `filterTitle()`: gałąź v2 przed rotacją legacy — `{base} - {cena} PLN [, {przebieg} km przy
  kolizji ceny][, dostępny od ręki dla on_lot] | Prima-Auto`; rotacja zostaje dla serii poza gate'em
- nowe: `offerTitleV2()` (gate: option `asiaauto_offer_title_v2_series` = CSV term_id serie, `*` = wszystkie),
  `buildTitleV2()`, `titlePriceCollides()` (1 query przy renderze single),
  `detexturizeTitleV2()` na `document_title` prio 20 (wptexturize zamieniał ` - ` na półpauzę)
- `renderMeta()`: og:title spójny z title v2 gdy gate aktywny
- stock = `_asiaauto_reservation_status` = `on_lot` (parytet z inventory); **NIE** `stm_car_location`
  (to miasto z feedu — pierwsza wersja się na tym przejechała, wykryte w smoke teście)

**Pilot (option ustawiony):** `5149,5150,4824,6569,6581,3981,6558,5304,3581` = SU7, YU7, Zeekr 9X,
Zeekr 8X, G700, Tank 700 Hi4-T, Preface, AITO M9, Arrizo 8 (~430 ofert). Rollback: option na `""`.
Pomiar GSC ~2026-07-27; progi rollbacku w baseline.

## 0.33.16 — 2026-07-07 (T-190: guard importera — make-aware serie)

**Powód:** importer odtwarzał zmergowane duplikaty i kontaminował huby cudzych marek —
lookup termu `serie` był globalny po slugu (bez marki), nowy term powstawał bez parenta
(sieroty), a slug szedł ze stale mapowania. Skala: 19 wzorców / ~106 aut na złych termach,
nawroty wszystkich 3 merge'ów galaxy-* z T-019 (diagnoza: `docs/seo/t190-log.md` KROK 0).

**Zmiana (`includes/class-asiaauto-importer.php`, backup `.bak-2026-07-07-t190-guard`):**
- nowe `setSerieTaxonomyAndMeta()` + `resolveSerieTermId()` + `isTokenSuffix()` — serie
  szukane wśród DZIECI marki (slug → znormalizowana nazwa → jednoznaczny wariant
  z/bez prefiksu sub-marki); nowy term zawsze z `parent`=make; meta `serie` = realny slug
  termu; `_asiaauto_primary_make_slug` od pierwszego zapisu; niejednoznaczność = osobny
  term + warning (bez zgadywania);
- `computeTerms()` (dry-run Che168) — `exists` tym samym resolverem (dry-run == import);
- `setTaxonomyAndMeta()` i pozostałe taksonomie NIETKNIĘTE; brak marki → fallback 1:1.
- header `Version` zsynchronizowany ze stałą (drift 0.33.14/0.33.15 → oba 0.33.16).

**Test:** 5/5 PASS (reflection, testowy draft, auto-cleanup) — AITO M8↛GAC, galaxy-e5→e5,
Avatr 07↛WEY, nowy term z parentem, fallback. Smoke 200. ADR Galaxy:
`docs/decyzje/2026-07-07-t190-galaxy-pod-geely.md`. Dziennik: `docs/seo/t190-log.md`.

---

## 0.33.15 — 2026-07-07 (homepage hero-sub + /informacje/o-nas/ — encja „importer")

**Powód:** audyt SEO 2026-07-07. „importer samochodów z chin" = najlepiej konwertujący topic-keyword
w Ads (4 konw./30 dni), a słowo „importer" padało 0× na stronie. SERP: konkurencja w topie na
„importer aut/samochodów z chin", nasza domena #10 / brak top 10 (rankuje nasz Facebook). Wtręt
addytywny — bez ruszania działającego „import".

**Zmiana:**
- `includes/class-asiaauto-homepage.php` (backup `.bak-2026-07-07-importer`) — hero-sub
  „import chińskich samochodów" → „bezpośredni importer samochodów z Chin". H1/title/FAQPage nietknięte.
- `/informacje/o-nas/` (post 198480, poza pluginem) — intro z encją „importer" + nowy H2
  „Bezpośredni importer samochodów z Chin" + `rank_math_description`. „importer" 0 → 6×.

**Uwaga:** model prawny = pośrednictwo/agencyjny; „importer" użyte jako opis funkcjonalny
(„model agencyjny / na Twoją rzecz"), bez sprzeczności. ADR: `docs/decyzje/2026-07-07-homepage-onas-importer-encja.md`.

---

## 0.33.14 — 2026-06-19 (Che168: import przeniesiony do menu Ogłoszeń + dostęp dla sprzedawcy)

**Powód:** faza obserwacji domknięta, realny import che168 WŁĄCZONY (v0.33.11). Janek chce,
żeby Che168 było drugim źródłem importu ręcznego dostępnym tak samo jak Dongchedi — w tym dla
Ruslana (sprzedawca). Pierwotny gate „osobne top-level menu, tylko login `js`" stracił rację bytu.

**Zmiana (`includes/class-asiaauto-admin-che168-import.php`):**
- `addMenuPage()` — `add_menu_page` (top-level „Import z Che168", pozycja 27) → `add_submenu_page`
  pod `edit.php?post_type=listings` jako **„Dodaj z Che168"**, obok „Dodaj z Dongchedi". Usunięty
  early-return gate — dostępem steruje cap `IMPORT_CAP` (param submenu).
- `allowed()` — przepisane z gate'u login∈`ASIAAUTO_CHE168_PREVIEW` na zwykłe
  `current_user_can(IMPORT_CAP)`; otwiera wszystkie handlery (preview/log/import/render) dla
  admina **i** sprzedawcy (rola `primaauto` = Ruslan). Stała `ASIAAUTO_CHE168_PREVIEW` przestaje
  być używana (można posprzątać z wp-config, nieszkodliwa).
- `enqueueScripts()` — hook `toplevel_page_` → `listings_page_` (zmiana typu strony).
- H1 — usunięte „(ukryte — tylko dla Ciebie)".

**Weryfikacja dostępu:** `js` (administrator) = DOSTĘP, `primaauto` (sprzedawca) = DOSTĘP.
Strefa krucha (`importListing`/taksonomie/adapter) NIETKNIĘTA. Backup
`class-asiaauto-admin-che168-import.php.bak-2026-06-19-submenu`.

## 0.33.13 — 2026-06-19 (Che168: alias marki Shanhai → Jetour)

**Powód:** ogłoszenie 58660114 wychodziło sierotą — che168 wystawia serię new-energy Jetoura
„Shanhai" (山海) jako osobną MARKĘ `mark="Shanhai"`, model `"Shanhai L9"` (wersja EN che168
pokazuje przy tym „Jetour"). Surowy klucz `Shanhai|Shanhai L9` nie istnieje w brand-mappingu
(tam kanoniczny `Jetour Shanhai|Jetour Shanhai L9`), więc `resolveChe168` zwracało null → `mapped=NIE`.

**Zmiana (addytywna, normalizacja-przy-wejściu; strefa krucha NIETKNIĘTA):**
- `data/che168-model-map.php` — +1 override `'Shanhai|Shanhai L9'` → sygnatura EU `Jetour|Shanhai L9`.
  Mostkuje surowy klucz che168 do istniejącej sygnatury brand-mappingu; `sigToKey` rozwija ją do
  kanonicznego klucza CN → dowiązanie do **istniejącego** huba serie 5624 (slug `shanhai-l9`,
  parent = make Jetour 4525). Zero nowych termów.

**Wynik:** 58660114 → `mapped=TAK`, mark=Jetour, serie „Shanhai L9", tytuł
„Jetour Shanhai L9 2025 1.5TD 2DHT Air 7-osobowy". Wyposażenie: `extra.option` (11/11 optionname
zmapowanych, 0 braków). Backup `che168-model-map.php.bak-2026-06-19-shanhai`.

## 0.33.12 — 2026-06-18 (Che168: import wyposażenia z extra.option)

**Powód:** ogłoszenia che168 miały „Dane techniczne", ale pustą sekcję wyposażenia
(`[asiaauto_equipment]`). Diagnoza: che168 trzyma wyposażenie OSOBNO w `extra.option`
(displayopts + moreoptions[].opts, CJK), a `extra.configuration` to wyłącznie spec techniczny —
adapter czytał tylko configuration. Dongchedi wplata wyposażenie w `extra_prep`, stąd u niego
działa „na maksa". Dokumentacja auto-api (`autoapicom/auto-api-php`) nie opisuje pól — źródłem
prawdy odpowiedź API.

**Zmiany (addytywne, normalizacja-przy-wejściu; strefa krucha importer/render NIETKNIĘTA):**
- `data/che168-option-map.php` — **nowy**. Mapa 37 distinct optionname (CJK) → klucz `extra_prep`,
  zbudowana z PRÓBKI 120 ofert (`tmp/che168-option-aggregate-out.tsv`).
- `class-asiaauto-che168-adapter.php` — `extractOptions()` czyta `extra.option`, wstrzykuje do
  `extra_prep` wartość `标配` (→ „Tak" → checkmark) tylko gdy klucza brak (spec config wygrywa).
- `data/translations-extra-prep.php` — 5 nowych etykiet (`auto_brake_hold`, `rear_air_outlet`,
  `uv_protection_glass`, `center_diff_lock`, `phone_mapping`) + dopięcie istniejących kluczy
  uncategorized do kategorii (adas: `active_brake`/`lane_keeping_assist`/`line_support`); relabel
  `active_closed_inlet_grid` „Aktywny grill"→„Aktywna atrapa".

**Wynik:** BAIC BJ60 58760644 (post 361348) → 22 pozycje wyposażenia. Istniejące drafty
odświeżone re-adapterem (`_asiaauto_extra_prep`), bez re-importu; nowe importy automatycznie.
58779380 ma 0 (oferta bez `extra.option` w feedzie — nie luka).

## 0.33.11 — 2026-06-18 (Che168: fallback rocznika gdy year=0)

**Powód:** część ofert che168 ma `year=0` (auto nierejestrowane, `first_registration="未上牌"`)
→ tytuł „AITO M9 0 …". Rok modelowy jest jednak w `param_93` ("YYYY款").

**Zmiana:** `class-asiaauto-che168-adapter.php` `normalize()` — fallback rocznika
`year` → `first_registration` (YYYY) → `param_93` ("YYYY款"). Refaktor: `name93` wyciągane raz
(wspólne dla wersji i rocznika). Wynik: AITO M9 55603575 `year=0`→2025. 1/21 w próbce dotknięte.

## 0.33.10 — 2026-06-18 (Che168: wersja/trim w tytule — parytet z dongchedi)

**Powód:** builder tytułu (`computeIdentity`) jest wspólny i agnostyczny — tytuł =
`{mark} {model} {year} {complectation}`. Różnica była w DANYCH: dongchedi podaje `complectation`
(trim) na wierzchu, che168 zostawia je puste — trim siedzi w `param_93` (车型名称) za prefiksem
„YYYY款" (np. `尚界Z7T 2026款 Max`). Slug bez różnicy (nie zawiera trim).

**Zmiany (addytywne, normalizacja-przy-wejściu):**
- `class-asiaauto-che168-adapter.php` `normalize()` — ekstrakcja trim z `param_93` (część po „款")
  → `$data['complectation']` (gdy puste). Wspólny builder daje odtąd tytuł z wersją jak dongchedi.
- `class-asiaauto-translator.php` `translateComplectation` — strip resztkowego CJK na końcu
  (marketing che168 `很有型`/`激光雷达` nieobjęty mapą). **Guarded** → no-op dla czystych ASCII
  (dongchedi bez regresji; mapa już zna 智驾版→Smart Drive, 旗舰型→Flagship, 激光雷达→LiDAR…).

**Wynik (próbka):** „SAIC Shangjie Z7T 2026 Max", „Li Auto L9 2025 Ultra", „BYD Han DM-i 2025
DM-i Smart Drive 125KM LiDAR Flagship", „Zeekr X 2024 4-osobowy RWD Cube" — czyste, z wersją.
Slugi niezmienione. Backupy `.bak-2026-06-18-compl`.

**Znane (osobne, nie z tej zmiany):** część ofert che168 ma `year=0` (źródło nie podało roku
→ „AITO M9 0 …") — do ekstrakcji `year`; redundancja trim×model (ET5 Touring…Touring) = kosmetyka.

## 0.33.9 — 2026-06-18 (Che168 Faza 2: tłumaczenia wartości CJK)

**Powód:** po param-mapie (0.33.8) 11 kluczy kategorycznych było zmapowanych, ale ukrytych przez
wartości CJK (filtr anty-CJK w renderze). Zbiór realnych wartości blokujących zebrany z próbki 21.

**Zmiany (addytywne):**
- `data/translations-extra-prep.php` (wspólny config, sekcja `values`) — blok wariantów Che168:
  zawieszenie (che168 `悬架` ≠ dongchedi `悬挂`), nadwozie, hamulce, typ silnika EV, producenci
  ogniw (CATL/CALB/EVE/Gotion/SVOLT), typ baterii kombi, koło zapasowe, sub-marki.
- `class-asiaauto-translator.php` `translateExtraPrepValue` — wzorce (kod): gwarancja z CHIŃSKIMI
  cyframi lat (`八年或16万公里`→`8 lat / 160 000 km`; dongchedi dawał cyfry arabskie), pierwszy
  właściciel bez limitu, `增程器`→Range extender, oktan `92号`→`92 oktanów`.

**Wynik:** Z7T 40→**44** pokazanych, próbka 21 → 68%→**75%** (z 47% na starcie), **0 wartości
blokujących CJK**. Reszta ukrytych = rzadki ogon `param_{id}` bez czystego klucza + `选配`/puste.
Backupy `.bak-2026-06-18-{che168vals,patterns}`.

## 0.33.8 — 2026-06-18 (Che168 param-map: +32 id, odzysk specyfikacji EV/silnika)

**Powód:** che168 pokazywał mało danych (Z7T 28/59 param vs SU7 dongchedi 233/371). Diagnoza:
(1) che168 ze źródła zwraca mniej (~59 vs 371, brak list wyposażenia — `option`/`moreoptions`
puste); (2) z tego 31 ukrytych przez luki param-mapy + wartości CJK. che168 podaje parametry pod
numerycznymi `id`, więc `che168-param-map.php` to **adapter id→klucz** — cała reszta (etykiety,
kategorie, sortowanie, grupowanie) to **wspólny** `translations-extra-prep.php` (ten sam co dongchedi).

**Sample-based (próbka 21 ofert dopasowanych do filtrów importu = same chińskie marki):** agregacja
ujawniła 76 niezmapowanych id wg częstości. Dodane **32 mapowania** w `data/che168-param-map.php`:
- **21 numerycznych** (renderują od razu): zasięg CLTC/WLTC, zużycie energii/ekwiwalent,
  moc/moment łączny + przód/tył silnika EV, moc/moment/obroty silnika spalinowego, moc szybkiego
  ładowania, zakres %, czas ładowania, promień zawracania.
- **11 kategorycznych** (zmapowane, czekają na tłumaczenia wartości CJK — Faza 2): typ/producent/
  gwarancja baterii, typ/liczba/układ silników EV, rodzaj energii, norma emisji, układ silnika,
  typ 4x4, koło zapasowe.

**Wynik:** Z7T 28→**40** pokazanych, próbka 21 → **68%**; kolejność kategorii identyczna jak
dongchedi (ten sam config). Post #361316 (zaimportowany Z7T) — meta odświeżone. Backup
`.bak-2026-06-18-expand`. **Faza 2 (pending):** tłumaczenia wartości CJK; uwaga — che168 używa
innych znaków niż dongchedi dla części wartości (zawieszenie `悬架` vs `悬挂`).

## 0.33.7 — 2026-06-18 (nowa marka Che168: 尚界 → SAIC Shangjie)

**Powód:** ogłoszenie 58779380 — nowa marka `尚界` (Shangjie, HIMA SAIC+Huawei) + model
`尚界Z7T`, oba CJK → sierota, make z pustym slugiem. VIN `LSJ`=SAIC, web/Wikipedia + reference
west-motors → „SAIC Z7/Z7T". Etykieta marki **SAIC Shangjie** (decyzja Janka, hub
`/samochody/saic-shangjie/z7t/`).

**Zmiany (addytywne, 0 regresji — nowe klucze):**
- `class-asiaauto-mapping.php` `resolveChe168` — marka `尚界`→`SAIC Shangjie` w `cnPrefix`
  (strip prefiksu modelu) + `$markAlias` (marka czysto-CJK, nieosiągalna stripem). Skaluje:
  kolejne modele 尚界 = już tylko wpis w brand-mappingu.
- `data/brand-mapping-v6.1.php` — wpis `SAIC Shangjie|Z7T` (Z7T = shooting-brake; Z7 = sedan).

**Weryfikacja:** 58779380 → SAIC Shangjie/Z7T mapped=1, slug make `saic-shangjie` (nie pusty),
serie `z7t`; enumy znormalizowane (rwd-rear-engine→RWD, 银/灰色→silver). Smoke 4/5 bez regresji.

## 0.33.6 — 2026-06-18 (grupa B: domknięcie 2 luk mark/model Che168)

**Powód:** odświeżenie logów dry-run (v0.33.4) zostawiło 2 rezydua mark/model (nie enumy):
57762274 „Tank 300 New Energy" (sierota), 58645565 „IM"/`智己LS8` (best-effort raw zamiast hubu).

**Zmiany (addytywne — nowe klucze, zero zmian istniejących linii, 0 orphanów):**
- `data/che168-model-map.php` — `Tank|Tank 300 New Energy` → konsolidacja do istniejącego huba
  Tank/300 (wariant nazwy „New Energy" = hybryda/EREV; wzorzec jak `Changan|CS75 PLUS iDD`).
- `data/brand-mapping-v6.1.php` — nowy wpis `IM Motors|LS8` (realny nowy model; CJK `智己`
  zdejmuje algorytm resolvera, więc wystarczy entry). Hub LS8 utworzy się czysto przy imporcie.

**Weryfikacja:** 57762274 → Tank/300 (0 nowych termów), 58645565 → IM Motors/LS8 (1 nowy term
`serie:LS8` = legalny nowy model). Backupy `.bak-2026-06-18-{tank,ls8}`.

## 0.33.5 — 2026-06-18 (cleanup: usunięty martwy resolveForSource)

**Powód:** po decyzji „normalizuj na wejściu" (ADR 2026-06-17) `AsiaAuto_Mapping::resolveForSource()`
przestał być wołany przez żywy kod — pozostały tylko `canonicalKeyForSource()` (woła
`resolveChe168()` bezpośrednio z adaptera) i `getEuForCn()`. Dług usunięty świadomym commitem.

**Zmiany:** usunięta metoda `resolveForSource()` z `class-asiaauto-mapping.php`; poprawione
dwa nieaktualne komentarze (`class-asiaauto-importer.php`, `data/che168-model-map.php`)
wskazujące na usuniętą metodę → teraz `resolveChe168()`. Backup `.bak-2026-06-18-deadcode`.
Bez regresji: smoke mark/model 4/5 mapped jak wcześniej.

## 0.33.4 — 2026-06-18 (T-186: normalizacja enumów atrybutów Che168 PRZY WEJŚCIU)

**Powód:** dry-run oferty 58545168 (Denza N8L) — 3 taksonomie „🆕 zostanie utworzony"
(`crossover/suv`, `plug-in hybrid`, `awd (front-engine)`). Surowiec enumów che168 ≠ Dongchedi →
slug z `api_value` dawałby śmieciowe duplikaty (`crossover-suv` obok `suv`). Tożsamość termu
idzie po slugu, nie po tłumaczeniu — sama dopiska do słowników nie wystarcza.

**Zmiany (addytywne, `importListing`/`setTaxonomies` NIETKNIĘTE):**
- `data/che168-enum-map.php` (nowy) — płaska mapa danych surowiec che168 → klucz słownika
  Dongchedi (body/engine/drive/color). Domena zamknięta (`getFilters`) → mapa, bez resolvera.
- `class-asiaauto-che168-adapter.php` — `normalizeEnums()` w `normalize()` przed kanonizacją
  tożsamości; surowiec w `{field}_che168_raw`. Słowniki `translations-*.php` reużyte 1:1.
- Mild-hybrydy zwinięte w `hybrid` (HEV) — decyzja Janka (bez osobnego filtra MHEV).

**Pokrycie:** body 10/10 + engine 14/14 + drive 11/11 → istniejące termy; zero nowych termów,
zero śmieci. 58545168: 9/9 ISTNIEJE. Bez regresji mark/model. Import che168 nadal OFF.
ADR: `docs/decyzje/2026-06-18-che168-enum-normalize.md`.

## 0.33.3 — 2026-06-17 (T-186 fala 2: wczesny getEuForCn + aliasy nazw domowych)

**Kontekst:** rozszerzenie analityki na kolejne 50 ogłoszeń Dongchedi (41 modeli) wykazało pokrycie 30/41; domknięte do **35/41** addytywnie.

**Zmiany:**
- `class-asiaauto-mapping.php` `resolveChe168` — krok **0a: wczesny `getEuForCn`** na surowych mark/model PRZED stripem marki. Łapie osobną markę che168 „Galaxy / Galaxy L6/L7" (== klucz brand-mappingu `Galaxy|Galaxy L6`), który strip kroku 2 wcześniej rozbijał („Galaxy L6"→„L6"→miss).
- `data/che168-model-map.php` — 4 aliasy nazw domowych: `Li Auto|Li L6`→L6, `NIO|ET5T`→ET5 Touring, `Changan|CS75 PLUS iDD` i `Changan|长安CS75PLUS`→CS75 Plus.
- Bez regresji: pierwsza 20 16→**17/20**.

**Grupa B (pending, decyzje per-hub):** Dongfeng Fengxing Xinghai T5 (sub-brand→Forthing), BYD Seal U/Song Plus (nazwa che168 wieloznaczna), BYD Han L EV, iCAR Super V23, Geely Galaxy Starship 8 (nowe warianty), Mazda 3 Axela (poza importem). ADR: `docs/decyzje/2026-06-17-che168-normalize-at-entry.md`.

## 0.33.2 — 2026-06-17 (T-186: normalizacja tożsamości Che168 PRZY WEJŚCIU)

**Powód:** dowód (`tmp/che168-vs-dongchedi-proof`) — surowe mark/model che168≠Dongchedi (0/12 identycznych), `getEuForCn(surowiec che168)`=1/16. Rozjazd realny. Zamiast wpinać resolver w strefę kruchą (stary plan T-186) — normalizacja w adapterze.

**Zmiany (addytywne, `importListing` NIETKNIĘTY):**
- `class-asiaauto-mapping.php` — nowa `canonicalKeyForSource()` (che168 → klucz CN brand-mappingu przez `resolveChe168` + reverse-index sig→klucz; dongchedi pass-through). `resolveChe168`: strip CJK z marki (`AITO 问界`→`AITO`), alias `IM`→`IM Motors`, prefiks `智己`.
- `class-asiaauto-che168-adapter.php` `normalize()` — stempluje kanoniczny `mark`/`model` (raw w `*_che168_raw`).
- `class-asiaauto-importer.php` `computeIdentity`/`computeTerms` (dry-run) — `resolveForSource`→`getEuForCn` (dry-run == realny import). Komentarz nagłówkowy zaktualizowany (stary plan „resolver w importListing" unieważniony).
- `data/che168-model-map.php` — 5 aliasów: Wey Lanshan, eπ008, Li L9, Fang Cheng Bao Leopard 5, VOYAH Dreamer (PHEV; override ślepy na napęd — EV do dorobienia).

**Weryfikacja:** realna ścieżka adaptera→getEuForCn 16/20 (4 NULL = luki brand-mappingu wspólne z Dongchedi). Import che168 nadal OFF. ADR: `docs/decyzje/2026-06-17-che168-normalize-at-entry.md`.

## 0.33.1 — 2026-06-16 (T-185 rewizja: OSOBNA strona „Import z Che168" + strefa krucha cofnięta do addytywu)

**Powód rewizji (decyzja Janka):** w 0.33.0 che168 był dołożony do współdzielonego panelu „Dodaj z Dongchedi" (używa go też sprzedawca/Ruslan) + refaktorował `importListing` (strefa krucha). Oba zbędne. Lepsza architektura: **osobne menu, czysto samo Che168**, reużywające wspólnych klas jako konsument, bez dotykania panelu Ruslana i bez refaktoru kruchej ścieżki.

**Zmiany:**
- **Nowa `class-asiaauto-admin-che168-import.php`** — samodzielne top-level menu „Import z Che168", **całe za gate** (`add_menu_page` rejestrowane tylko gdy login∈ASIAAUTO_CHE168_PREVIEW → Ruslan nie dostaje nawet pozycji menu). Własne AJAX-y (`asiaauto_che168_{preview,log,import}`), pełna tabela dry-run, „Zapisz do logu", lista logu, import za flagą.
- **`class-asiaauto-importer.php` PRZYWRÓCONY do oryginału** (704 linie) — `importListing`/`setMotorsMeta`/`setTaxonomies` bajt w bajt jak przed T-185. `buildPlan`/`computeIdentity`/`computeMeta`/`computeTerms` dopisane jako **czyste metody OBOK** (woła je tylko dry-run; ścieżka realnego importu ich nie używa). **Strefa krucha NIETKNIĘTA.** Wierność dry-run vs realny listing dongchedi: 6/6 title, 88/88 meta, 54/54 terms.
- **`class-asiaauto-admin-manual-import.php` PRZYWRÓCONY do stanu sprzed T-185** — panel Ruslana bez śladu che168/dry-run.
- Bez zmian (czyste, konsumowane przez nową stronę): adapter, `resolveForSource` w mapping, che168-model-map, param-map, klasa logu, stałe wp-config.

**Weryfikacja:** gate per user (`js`=widzi menu, `primaauto`=nie); test E2E w wp-admin (Chrome, jako js) — Denza D9 57888520: dry-run pełny, fuel/drive `🆕 nowy`, „Zapisz do logu" → snapshot `57888520-...json` na liście. Wiązanie `resolveForSource` w realny import odroczone do włączenia che168 (T-186, świadoma edycja kruchej ścieżki).

## 0.33.0 — 2026-06-16 (T-185: Che168 ręczny import + log wdrożeniowy — ukryte, faza obserwacji)

**Kontekst:** feed dongchedi nawracająco pada (T-182); dostawca steruje fallbackiem na Che168 (C2C aut używanych, inny profil — kurator wybiera auta ręcznie, nie automat). Pełny ADR: `docs/decyzje/2026-06-16-che168-manual-import.md`.

**Architektura:** wspólny kod symulacji i realnego importu. `AsiaAuto_Importer::importListing()` zrefaktorowany (ekstrakcja czystych `computeIdentity`/`computeMeta`/`computeTerms`/`buildPlan` bez zmiany zachowania — `setMotorsMeta`/`setTaxonomies` to teraz pętle po `compute*`). Dry-run == realny import (zero driftu). Strefa krucha za świadomą zgodą Janka; chroniona testem regresji **6/6 title, 88/88 meta, 54/54 terms** na realnych listingach dongchedi.

**Nowe pliki:** `class-asiaauto-che168-adapter.php` (address→city, first_registration→reg_date, extra.configuration→extra_prep po `id`), `data/che168-param-map.php` (51 id→klucz dongchedi), `class-asiaauto-che168-log.php` (snapshot dry-run → `uploads/asiaauto/che168-dryrun/`), `data/che168-model-map.php` (51 nadpisań resolvera).

**Zmienione:** `class-asiaauto-mapping.php` (`resolveForSource()` — reverse-index brand-mappingu + reguły normalizacji; dongchedi→getEuForCn bez zmian), `class-asiaauto-admin-manual-import.php` (gate `che168Allowed`, detekcja źródła, pełna tabela zgodności dry-run [title/slug/mapowanie/taksonomie ze statusem/meta/extra_prep pogrupowany], przycisk „Zapisz do logu wdrożeniowego", warunkowy import, widok listy logu), `asiaauto-sync.php` (require_once + bump).

**Gate:** `wp-config.php` `ASIAAUTO_CHE168_PREVIEW='js'` (brak stałej = niewidoczne, zero zmian UX dongchedi) + `ASIAAUTO_CHE168_IMPORT_ENABLED=false` (faza obserwacji — realny import che168 ukryty).

**Smoke:** resolver che168 5/5 ze spec; adapter 5/5 (city+vin+reg_date, extra_prep, obrazy `2sc2.autoimg.cn`); tabela podglądu Denza D9 = 9 taksonomii/11 meta/8 kat·46 wierszy; log save→all→re-decode OK (diakrytyki zachowane). Backupy `.bak-2026-06-16-che168` (importer/mapping/manual-import/asiaauto-sync/wp-config).

**Faza obserwacji (otwarta):** Janek zbiera snapshoty → wspólna kalibracja `che168-model-map`/`che168-param-map`/wartości/resolver → po pokryciu `ASIAAUTO_CHE168_IMPORT_ENABLED=true`. Automat = osobny T-186.

## 0.32.73 — 2026-06-09 (rotation: kasowanie zdjęć przy permanent delete + backfill 54508 sierot)

**Kontekst:** audyt inode konta — `uploads/asiaauto/` urósł do ~575 tys. plików / 13,2 GB (główny konsument inode). Korzeń: `deleteOldTrash()` kasował listingi przez `wp_delete_post($pid, true)`, a WP core (`wp-includes/post.php:3861` — „Point all attachments to this post up one level") **przepina attachmenty usuwanego posta na `post_parent=0`**. `cleanOrphanedImages()` filtruje `post_parent>0` → nigdy ich nie łapał → „0 orphaned images removed" przez wszystkie 84 uruchomienia crona od marca. Zdjęcia każdego usuniętego auta (oryginał + 4 miniatury) wyciekały na dysk → **54 508 osieroconych attachmentów**.

**Zmiana — `includes/class-asiaauto-rotation.php` (backup `.bak-2026-06-09`):**
- W `deleteOldTrash()`, w pętli przed `wp_delete_post()`: `$this->media()->removeImages($pid, true)` — kasuje pliki + miniatury + wiersze attachment PÓKI listing żyje (czyta meta `gallery`, omija reparenting WP). Licznik `$images_removed` + log „Permanently deleted N trashed posts (M images removed)".
- Helper `media()` (leniwa `AsiaAuto_Media`) + property `$media`. `isReserved()` nietknięte.
- **Listingi ręczne bezpieczne bez dodatkowego guardu:** `markRemoved()` (jedyny setter `_asiaauto_removed_at` → jedyne wejście do trash→delete) wołany tylko z sync (guard `isManuallyManaged()`) i order-cancel (gate `_asiaauto_api_removed`, nieosiągalny dla ręcznych). `removeImages()` nie jest manual-aware (pętla galerii kasuje bezwarunkowo), ale ręczne nigdy nie trafiają do `deleteOldTrash` przez rotację.

**Backfill istniejących sierot** (skrypt chunkowy, re-weryfikacja 0 referencji galerii + skip `_asiaauto_manual_upload`; kryterium: `post_parent=0` + `_asiaauto_source_url`, 0 referencji w `gallery`/`_thumbnail_id` żadnego listingu):
- 2026/03: 742 → 106M/4204 plików → 38M/1155
- 2026/04: 34 538 → 5,5G/252980 → 1,9G/82785
- 2026/05: 19 228 → 6,1G/282114 → 4,1G/184507
- Pozostałe `parent=0` sieroty: **0**. Odzysk **~5,7 GB / ~254 tys. inode**.

**Walidacja:** `php -l` clean, dry-run cleanup OK, test forward-fixu na 355476 (9 attach / 53 pliki na dysku) → 0 plików, 0 wierszy attachment, post usunięty. Backup DB przed: `~/backups/primaauto/2026-06-09/posts-postmeta-pre-orphan-cleanup.sql` (372 MB). Smoke homepage/oferta/marki = 200. Pomiar w boju: następny cron 2026-06-10 03:00 zaloguje M>0 images removed. ADR `docs/decyzje/2026-06-09-rotation-image-cleanup.md`.

## 0.32.72 — 2026-06-08 (hub marek: template pod answer-first lead + fact strip + dateModified)

**Kontekst:** rework hubów MAREK (`/samochody/<marka>/`) — domknięcie wzorca answer-first lead z pilota modeli. KROK 1 = template (treść per marka w osobnym batchu). Analiza KW + recon: `docs/seo/make-hubs-kw-analysis-2026-06-08.md`. Dane: `{marka} import`≈0 wolumenu → orientacja cena/Polska, nie import.

**3 pliki (strefa ZAWSZE PYTAJ, backupy `*.bak-2026-06-08`):**
- **`themes/primaauto2026/taxonomy-make.php`:** H1 z mechanizmem `_asiaauto_h1_suffix` (fallback „z Chin") + fact strip (`_asiaauto_facts` JSON: models/count/price_min-max-median/year_min-max/avail_label/updated) + „Stan oferty: {rok}" (`date('Y')`) + lead `_asiaauto_lead` (mirror `taxonomy-serie.php:107-111`). Wszystko render-if-meta — brak meta = stare zachowanie.
- **`themes/primaauto2026/assets/css/hub.css`:** `.aa-hub__facts` (+ `-note`) — nowa klasa, nieobecna na `taxonomy-serie.php` → 0 regresji 228 hubów modeli. Lead bez CSS.
- **`includes/class-asiaauto-seo.php`:** `dateModified` przez filtr `rank_math/json_ld` (`addHubDateModified`) z `_asiaauto_facts.updated`. Uwaga: `buildCollectionPage()` to dead code gdy RankMath aktywny (renderSchema emituje tylko ItemList+Product; CollectionPage/@graph robi RankMath).

**Smoke PASS:** baic (test facts) → H1 suffix + strip 6 pól + lead + dateModified 2026-06-08; byd (bez facts) → „BYD z Chin" nietknięte, HTTP 200, brak strip/lead/dateModified; modele (leopard-5) nietknięte. Fact strip = dane STORED (odświeżane reworkiem, nie live). Decyzja: bez newsów/premier (osobny projekt). KROK 2 = generacja treści per marka (subagenty, PYTAĆ przed batch).

## 0.32.71 — 2026-06-07 (SEO rework strony głównej — treść topiczna pod head termy)

**Kontekst:** osobny track SEO strony głównej (homepage = własna encja topiczna, [[feedback_homepage_seo_separate_track]]). Dedykowany research DataForSEO przewartościował topic: head term `chińskie samochody` **22 200/mc** (+ `chińskie samochody elektryczne` 1300), 85× większy niż frazy import- z B1 (`import aut z chin` 260). Decyzja Janka: **import = główny intent** (H1/title nietknięte), resztę pokryć wtórnie. Dotąd homepage = czysty katalog, zero prozy semantycznej.

**Zmiana — `class-asiaauto-homepage.php` (strefa ZAWSZE PYTAJ, backup `.bak-2026-06-07-seo-rework`):**
- **H1 BEZ ZMIAN** „Import samochodów z Chin do Polski" (nie rozrywać exact-match głównej frazy).
- **hero-sub** przerobiony → „import chińskich samochodów…" (lemma `chińskie samochody` pod H1).
- **Lead answer-first** (`renderLead()`) pod hero: `chińskie samochody` + `używane auta` + cena końcowa (AEO).
- **Przeróbka 7 H2** + opisy pod gridami (Latest/Makes/BodyTypes/FuelTypes): rozłożone `chińskie samochody`, `chińskie samochody elektryczne` (1300, exact w H2 paliwa), `auta z chin do sprowadzenia`, intent cena/import. Fix NAP „Prima Auto"→„Prima-Auto" w H2 USP.
- **Sekcja prozy + FAQ** (`renderTopicFaq()`) przed CTA: H2 `Import aut z Chin do Polski — jak to działa` (exact head term) + 5 pytań FAQ (FAQPage, ASCII quotes), markowo-transakcyjnych, zdedupowanych względem hubów (encjowe) i stron info (proces/koszt/homologacja). Link do `/informacje/` (oddanie intentu informacyjnego).
- CSS dla lead/proza/FAQ (akordeon `<details>` natywny).

**Smoke:** 11/11 treści live, FAQPage waliduje (5 Q), 0 PHP errorów, `php -l` clean. **Pomiar GSC ~06-20** (recrawl). Memory: [[project_homepage_seo_topic_2026_06_07]]. ADR `docs/decyzje/2026-06-07-homepage-seo-rework.md`.

## 0.32.70 — 2026-06-07 (SEO P2: fallback resolwera `/model/` — zamknięcie klasy 404)

**Kontekst:** audyt GSC 2026-06-07. Stary handler `/model/<slug>/` (`class-asiaauto-redirects.php` → `redirectLegacyTaxonomy`) przekierowywał **tylko** gdy żywy term serie istnieje pod dokładnym slugiem; po fuzji marek / normalizacji importera slug się zmieniał → `null` → 404 (żywe `/model/e008/`, `/model/galaxy-l6/` w indeksie Google jako „zaindeksowana", a HTTP 404). Nowszy `redirectHubMakePrefix` ma 3-warstwowy samonaprawiający fallback — stary `/model/` go nie miał.

**Zmiana — `/model/<slug>/` dostał 4-warstwowy fallback (każda warstwa redirectuje TYLKO gdy cel żyje):**
1. Term żyje → hub modelu.
2. Term martwy → match **bez myślników** (`e008` → `e-008` Dongfeng) — przed stripem zer, bo „008" to odrębny numer modelu, nie „8".
3. Term martwy → **pełna normalizacja** (`sea-lion-07dm` → `sealion-7-dm`).
4. Zdejmij **prefiks żywej marki** (`galaxy-l6` → make `galaxy` + remainder `l6`) → hub modelu remaindera, w ostateczności hub marki.
Brak żywego celu → przepuszczamy (naturalny 404, nie zgadujemy). Samonaprawiające: gdy model wróci (term=200), wcześniejsze warstwy go łapią.

**Kluczowa decyzja techniczna — markę wyznaczamy z dominującej marki listingów serii, NIE z mety `_asiaauto_primary_make_slug`.** Meta jest niewiarygodna: serie 3399 „Galaxy L6" ma metę `li-auto`, a 15/16 listingów to Geely → meta dałaby `/samochody/li-auto/l6/` (zły model, choć 200). Dominacja listingów daje poprawne `/samochody/geely/l6/`. To częściowo obchodzi P7 dla ścieżki redirectu (P7 nadal wart dla kanonicznych linków `filterSerieTermLink`).

**Nowe helpery (prywatne, redirects):** `serieHubUrl`, `dominantMakeSlug`, `serieHubBySlug`, `serieSlugMaps` (jeden przelot `get_terms` → 2 mapy: dashless + norm, cache per-request), `serieHubByDashlessSlug`, `serieHubByNormalizedSlug`, `modelOrMakeHubFromPrefixedSlug`. Heavy operacje (`get_terms` 2738 serii, `get_posts` 100) odpalają się **tylko na trafieniach legacy `/model/`** (rzadkie).

**Weryfikacja:** `php -l` czysty. Smoke (final HTTP, 2 hopy = 1×301+200, zero łańcuchów):
- `/model/galaxy-l6/` → `/samochody/geely/l6/` ✅ (dominacja naprawiła błędną metę)
- `/model/e008/` → `/samochody/dongfeng/e-008/` ✅ (dashless tier — Dongfeng E008, nie GAC/Geely E8)
- `/model/sea-lion-07dm/` → `/samochody/byd/sealion-7-dm/` ✅ (normalizacja)
- Regresja `/model/leopard-5,arrizo-8,8x/` → właściwe huby 301 ✅, `/marka/byd/` 301 ✅
Backup: `class-asiaauto-redirects.php.bak-2026-06-07`. **Pending: P3 — Janek eksportuje pełną listę 404 z GSC UI do rewalidacji.**

## 0.32.69 — 2026-06-07 (SEO P1: oferty jako Product snippet — multi-type `["Product","Car"]`)

**Kontekst:** audyt GSC 2026-06-07 (memory `project_session_2026_06_07_gsc_full_audit`). URL Inspection `richResultsResult` dowiódł, że single oferta (`/oferta/...`) z `@type=Car`+`Offer` jest przez Google promowana **tylko do breadcrumbu**, nie do „Opisów produktów" — licznik „Opisy produktów" w GSC zbierał wyłącznie ~28 hubów modelu, mimo 4458 ofert z ceną. Car⊂Vehicle⊂Product w Schema.org, ale Google kwalifikuje do „Product snippets" dopiero przy **jawnym** `Product`.

**Zmiana:** w żywym builderze schematu single (`class-asiaauto-single.php` → `renderMeta()`, ~linia 721) `'@type'=>'Car'` → `'@type'=>['Product','Car']`. Cały blok `offers` (Offer / price PLN / InStock / seller / shippingDetails / priceValidUntil) zachowany bez zmian. Ujednolicono `seller.name` „Prima Auto" → „Prima-Auto" (NAP) w obu blokach. **NIE** dodano `aggregateRating`/`review` (brak realnych ocen = ryzyko kary).

**Uwaga techniczna:** w pliku istnieje też martwa metoda `schema()` (~linia 558, zero wywołań) — dla spójności też dostała `["Product","Car"]`+„Prima-Auto", ale renderowanie idzie wyłącznie przez `renderMeta()`.

**Weryfikacja:** `php -l` czysty. Smoke 3 oferty (Leapmotor Lafa5 / iCAR Super V23 / AITO M8) → wyrenderowany JSON-LD ma `@type:["Product","Car"]`, `seller:"Prima-Auto"`, komplet wymogów Product snippet (name+image+offers.price/priceCurrency/availability). Backup: `class-asiaauto-single.php.bak-2026-06-07`. **Pending: Rich Results Test (Janek) + pomiar „Opisy produktów" w GSC po recrawl (~2 tyg).**

## 0.32.68 — 2026-06-05 (Fix: import ręczny robił śmieć z pustej oferty — „Listing {id}" + slug=ID + zero parametrów)

**Zgłoszenie:** Ruslan — w panelu „Dodaj z Dongchedi" niektóre oferty po imporcie nie miały parametrów, dostawały tytuł „Listing {inner_id}" i slug w postaci samego numeru ID zamiast `marka-model-rok-ID`.

**Diagnoza (API na żywo, nie intuicja):** auto-api.com zwraca dla części ofert **pustą wydmuszkę** — ten sam zestaw 33 kluczy, ale `mark`/`model`/`year`/`complectation`/`body_type`/`engine_type`/`images`/`extra_prep` = pusty string; wypełnione tylko `id`, `inner_id`, `url`, `price`, `created_at`. To oferty sprzedane/usunięte na Dongchedi (auto-api trzyma rekord z ceną, szczegóły znikają — pokrewne incydentowi crawlera z 25.05). `extractData()` działa poprawnie (nie błąd zagnieżdżenia).

**Root cause (nasz bug):** import ręczny woła `importListing(..., force=true)`, co omija filtr konfiguracji i preflight zdjęć W1 — żadna bramka nie zatrzymywała pustki. Efekt: tytuł `trim("")` → fallback „Listing {id}"; `buildListingSlug('','','',ID)` → same puste człony → slug = sam `post_id`; `extra_prep` pusty → blok parametrów pominięty; taksonomie tylko `condition` (default). Cena zapisywała się (jedyne wypełnione pole).

**Dowody:** 3 zepsute posty (`355913`, `355869`, `303690`) — wszystkie ręczne importy. API na żywo: `23958107`/`23984272` → wydmuszki; `23701521` (Denza N8L DM) → komplet, 402 parametry.

**Zmiany:**
1. `class-asiaauto-importer.php` — nowy helper `isEmptyShell()` (mark+model puste) + bramka w `importListing()` (po sprawdzeniu `inner_id`, przed filtrami) → `return null` + log. Chroni **wszystkie** ścieżki, też force.
2. `class-asiaauto-admin-manual-import.php` — guard w `ajaxImport()` (czytelny komunikat zamiast cichego null) + flaga `is_empty_shell` w `ajaxPreview()` + blok JS w podglądzie (blokada przycisku „Zaimportuj" + notatka, że oferta sprzedana/usunięta).

`buildListingSlug()` **nietknięty** — „dziwny slug" był objawem pustych danych, nie wadą slugu; bramka eliminuje przypadek u źródła. Backup: `*.bak-2026-06-05-emptyshell`. `php -l` czysty. Smoke: `isEmptyShell` blokuje `23958107`+`23984272`, przepuszcza `23701521`. Mirror `asiaauto.pl` nie istnieje — sync zbędny. **Sprzątanie 3 śmieci pending (osobno).**

## 0.32.67 — 2026-06-02 (Fix: panel admina kłamał „klient nie przesłał umowy" przy wielo-plikowym podpisie)

**Zgłoszenie:** Ruslan — realny klient (Miron Orłowski, zamówienie Denza N9 `#351079`) podpisał umowę Profilem Zaufanym i przesłał ją przez panel, ale karta zamówienia w wp-admin pokazywała „Podpisana umowa: Brak — klient jeszcze nie przesłał".

**Diagnoza (dane realne, nie intuicja):** umowa była na miejscu — załącznik `351149` (`AA-2026-0015-…-podpisana.pdf`), status zamówienia `podpisane`, log: „Klient potwierdził przesłanie podpisanej umowy (1 plik(ów))" 2026-06-02 14:44. Bug był wyłącznie wizualny.

**Root cause:** podpisana umowa wspiera **wiele plików** (wielostronicowy skan) i jest zapisywana jako **JSON-array** (`_order_signed_attachment_id` = `[351149]`, zapis przez `wp_json_encode` w `order-api.php:577/658`). `getOrderData()` czytało tę meta przez `(int) get_post_meta(...)`, a `(int) "[351149]" === 0` w PHP → render karty (`order-admin.php`) wpadał w gałąź „Brak". Kod klienta (REST `getSignedAttachmentIds`) i regeneracja umowy parsowały JSON poprawnie — stąd status doszedł do `podpisane`, ale karta admina pokazywała sprzeczność (badge „podpisane" + „Brak").

**Audyt zakresu (cały plugin + motyw):** dokładnie **jedno** zepsute miejsce odczytu (`order.php:1457`) i jego jedyny konsument (render `order-admin.php`). Bliźniak `payment_proof` zapisywany jako pojedynczy int (jeden plik z założenia) — `(int)` cast tam **poprawny**, bez buga. Motyw `asiaauto` — zero odwołań do tej meta.

**Zmiany:**
1. `class-asiaauto-order.php` — nowy helper `parseSignedAttachmentIds()` (mirror `order-api.php:845`, w modelu); `getOrderData()` zwraca `signed_ids` (tablica) + `signed_id` (pierwszy plik, wsteczna kompatybilność).
2. `class-asiaauto-order-admin.php` — wiersz „Podpisana umowa" renderowany w pętli po `signed_ids` (pokazuje wszystkie pliki; „Brak" tylko gdy realnie pusto).

Backup: `*.bak-2026-06-02-signed-array`. `php -l` czysty. Smoke: `getOrderData(351145)` → `signed_ids=[351149]`; `getOrderData(326921)` (Exeed, drugi dotknięty) → `[337660]`; stock bez podpisu → `[]`. Helper przetestowany: JSON-single/multi, legacy bare-int, `"0"`, `""`. Mirror `asiaauto.pl` już nie istnieje (czysty 301) — sync zbędny. ADR: `docs/decyzje/2026-06-02-fix-signed-contract-array-display.md`.

## 0.32.66 — 2026-06-02 (dataLayer `serie_id` pod dynamic remarketing Google Ads — Faza 2)

**Powód:** Faza 2 Google Ads (Display dynamic remarketing, feed model-hubów). Feed używa `id = serie term_id`, ale dataLayer `view_item` w `renderMeta()` wystawiał tylko `inner_id` auta — brak identyfikatora model-huba, więc GTM nie miał czym ustawić `dynx_itemid`.

**Zmiany (`class-asiaauto-single.php::renderMeta()`, addytywne):**
- Nowa zmienna `$serie_id` (= serie term_id, w idiomie sąsiednich termów).
- Nowy klucz `listing.serie_id` w dataLayer (obok `inner_id`). Czyta go tylko nowy DLV w GTM → `dynx_itemid`. Bloki `ecommerce`/`item_id`/schema/og **nietknięte** (GA4/Meta ViewContent bez zmian).

Backup: `class-asiaauto-single.php.bak-2026-06-02-dynx`. Smoke OK (single AITO M9 → `serie_id:5304` match z feedem hubów). `php -l` czysty, OPcache podchwycił od razu.

## 0.32.65 — 2026-05-30 (Spójność modelu: „zaliczka" → „depozyt" w 2 stringach user-facing)

**Powód:** model biznesowy Prima-Auto to **pośrednictwo** (umowa zlecenia sprowadzenia, **zwrotny depozyt gwarancyjny**, prowizja — Zleceniodawca/Zleceniobiorca), a nie sprzedaż z zaliczką. Audyt przy okazji przeglądu dokumentów prawnych (regulamin/polityka od prawnika Ruslana) wykrył 2 user-facing stringi wciąż mówiące „zaliczka", niezgodne z umową generowaną przez `class-asiaauto-contract.php` (tam wszędzie „depozyt").

**Zmiany:**

1. `class-asiaauto-order-api.php:729` — komunikat REST po potwierdzeniu umowy: „Oczekujemy na wpłatę **zaliczki**." → „...**depozytu**."
2. `class-asiaauto-order-content.php:32` — opis placeholdera `{deposit_amount}`: „Kwota **zaliczki**" → „Kwota **depozytu**".

**Nie ruszane:** komentarz/changelog w nagłówku `class-asiaauto-order-admin.php` (słowo „Zaliczka" jako ślad historyczny renamingu, nie user-facing). `.bak` pominięte.

**Kontekst szerszy:** obecny live regulamin (strona ID 153866) i polityka (ID 198526) też operują na błędnym modelu sprzedaży — czekają na poprawione dokumenty od prawnika Ruslana (zero wdrożenia hybrydy). Notka zwrotna z prawidłowym słownikiem modelu wysłana Ruslanowi.

---

## 0.32.64 — 2026-05-30 (Przywrócenie badge źródła umowy po pomyłce cache)

**Powód:** w v0.32.63 usunąłem badge „Indywidualna" / „Auto-generowana" na podstawie komentarza Janka „nie wiem po co jest pojawia się niezależnie od tego czy umowa jest generowana czy załącznikiem". Po refleksji: badge **działał poprawnie** (Chrome MCP weryfikacja: Stefan #350835 manual upload → „Indywidualna" pomarańczowy, Miron #350537 auto-gen → „Auto-generowana" niebieski). Janek miał cache CSS / nieodświeżone style — widział tylko pomarańczowy badge na różnych zamówieniach, stąd wrażenie że nie reaguje na typ umowy.

**Lesson:** „nie wiem po co jest" ≠ „usuń". Powinienem był najpierw wyjaśnić co badge robi i poczekać na decyzję.

**Zmiana:** revert `c999881` (`docs/VERSIONS.md`) + manualny rewert kodu w `renderCardContract()` — badge przywrócony 1:1 z v0.32.62. Smoke w Chrome: badge widoczny dla Stefana po `Ctrl+Shift+R`.

**Komentarz w kodzie** (jako ślad): `// v0.32.61 dodany, v0.32.63 usunięty, v0.32.64 przywrócony`.

---

## 0.32.62 — 2026-05-30 (UX uploadu własnej umowy: obok Regeneruj, zielone tło, +DOC/DOCX/ODT)

**Powód:** drobne UX poprawki po review v0.32.61. Janek poprosił o:
1. Toggle „Wgraj własną" obok przycisku Regeneruj/Wygeneruj (nie pod spodem)
2. Zielone tło sekcji uploadu (zamiast pomarańczowego)
3. Akceptacja plików `.doc`, `.docx`, `.odt` obok PDF (Word z LibreOffice też się liczy)

**Zmiany:**

1. **Nowa metoda `renderUploadCustomContractToggle(array $data, bool $has_contract)`** w `class-asiaauto-order-admin.php` — wydzielony toggle z formularzem. Wywoływana inline po przycisku Regeneruj (gdy umowa istnieje) lub po Wygeneruj PDF (gdy brak).
2. **CSS:** `<details>` z `display:inline-block` + `vertical-align:top`, summary jako zielony pill (`#C6F6D5` bg, `#9AE6B4` border, `#22543D` text). Formularz wypada pod summary jako blok (`F0FFF4` bg, zielona ramka).
3. **`accept` attribute** w input file:
   ```
   .pdf,.doc,.docx,.odt,application/pdf,application/msword,
   application/vnd.openxmlformats-officedocument.wordprocessingml.document,
   application/vnd.oasis.opendocument.text
   ```
4. **Handler `handleUploadCustomContract()`** — walidacja po **rozszerzeniu** (mime DOCX/ODT to ZIP, nie wiarygodne):
   ```php
   $allowed_exts = ['pdf', 'doc', 'docx', 'odt'];
   $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
   if (!in_array($ext, $allowed_exts, true)) → error
   ```
   `wp_handle_upload` z whitelistą mimes (4 typy) — dodatkowa walidacja po stronie WP.

**Nie ruszane:**
- Logika handlera (purge starego attachment, bump licznika, meta `_aa_contract_source`) — nadal działa
- Klient pobiera plik przez `wp_get_attachment_url()` → przeglądarka decyduje co zrobić (PDF otworzy się inline, DOC/DOCX/ODT pobierze i otworzy w Word/LibreOffice)

**Backupy:** `*.bak-2026-05-30-upload-custom-contract` (z poprzedniego patcha v0.32.61 — wystarczają).

**Lint:** czysty / Produkcja: 200 / `renderUploadCustomContractToggle` istnieje.

---

## 0.32.61 — 2026-05-30 (Generowanie PDF z `potwierdzone` + upload własnej umowy + walidacja numeru)

**Dwie sprawy do naprawienia w workflow po v0.32.59:**

1. **Brak ręcznego generowania umowy zanim Ruslan wyśle ją klientowi.** Po wyłączeniu auto-advance w v0.32.59 status zostaje `potwierdzone`, ale przycisk „Wygeneruj PDF" pokazywał się dopiero od `umowa_gotowa`. Jedyna ścieżka: zmiana statusu → PDF generuje się automatycznie + mail do klienta leci jednocześnie. Brak chwili na podgląd.

2. **Ruslan czasem przygotowuje własną umowę poza systemem** (klient zagraniczny ze specyficznymi załącznikami, indywidualne klauzule, klient w biurze podpisał inną wersję). Wcześniej taka umowa szła mailem off-system → brak śladu w bazie, status zalegał na `potwierdzone`, klient nie miał PDF w panelu.

**Patch (4 pliki):**

1. **`class-asiaauto-order-admin.php::renderCardContract()`** — przycisk „Wygeneruj PDF" widoczny też dla statusu `potwierdzone` (z guardem `isCustomerDataComplete=true`). Komunikat „(uzupełnij dane klienta, żeby wygenerować)" gdy bramka nie spełniona. PDF powstaje, status zostaje `potwierdzone` — Ruslan ogląda, ewentualnie regeneruje, dopiero potem dropdown na `umowa_gotowa` → hook widzi że PDF istnieje (`$existing > 0 && get_post()` linia 73) → nie generuje znowu → tylko mail leci.

2. **`class-asiaauto-order-admin.php::renderCardContract()`** — nowa sekcja `<details>` „Wgraj własną umowę" widoczna dla statusów `potwierdzone+`:
   - Pole upload PDF (max 20MB, walidacja `mime_content_type === application/pdf`)
   - Pole text „Numer umowy" (default: `previewNextContractNumber()`)
   - Plus badge w UI: „Indywidualna (wgrana ręcznie)" pomarańczowy vs „Auto-generowana" niebieski

3. **`class-asiaauto-order-admin.php::handleUploadCustomContract()`** — nowy handler:
   - Walidacje: numer non-empty, max 50, unikalny (`isContractNumberInUse`); plik PDF, max 20MB, mime check
   - `wp_handle_upload` + `wp_insert_attachment` z `post_parent=$order_id`
   - Stary `_order_contract_attachment_id` (jeśli istniał) — `wp_delete_attachment(true)` (purge)
   - Meta: nowy `META_CONTRACT_ID`, `META_CONTRACT_NUMBER`, **`_aa_contract_source = 'manual_upload'`**
   - **Bumpnij licznik** przez `bumpContractCounterFromManual()` jeśli numer w formacie `AA/YYYY/NNNN` z NNNN > current
   - Status NIE zmieniany, mail NIE leci

4. **`class-asiaauto-order.php`** — 3 nowe helpery statyczne:
   - `previewNextContractNumber()` — `AA/YYYY/NNNN+1` bez inkrementacji licznika (pre-fill formularza)
   - `isContractNumberInUse(string $number, int $exclude_order_id = 0)` — `SELECT post_id FROM postmeta WHERE meta_key=_order_contract_number AND meta_value=$number AND post_id != $exclude`
   - `bumpContractCounterFromManual(string $number)` — jeśli pasuje regex `^{prefix}/(\d{4})/(\d+)$` i numer > licznik dla tego roku → `update_option(counter_prefix_YYYY, N)`. Log info.

5. **`class-asiaauto-order.php::changeStatus()`** — bramka `isCustomerDataComplete` w przejściu na `umowa_gotowa` **pomijana gdy `_aa_contract_source = 'manual_upload'`**. Powód: Ruslan zna dane klienta z PDF, user_meta może być niekompletny.

**Hotfix smart quotes** — initial deploy padł `Parse error` przez `"` w środku polskiego cytatu „Umowa gotowa". Naprawione przez polski cudzysłów zamykający `”` (U+201D). Pierwsza lekcja [[feedback_smart_quotes_break_json.md]] dotyczyła JSON-a, teraz przypomnienie dla PHP stringów.

**Smoke (po deployu):**
- `previewNextContractNumber()` → `AA/2026/0014` (licznik = 13 po Mironie) ✓
- `isContractNumberInUse(AA/2026/0013)` → true ✓
- `isContractNumberInUse(CUSTOM-XYZ)` → false ✓
- `method_exists(handleUploadCustomContract)` → true ✓
- `https://primaauto.com.pl/` → 200 (po hotfix) ✓

**Backupy:** `*.bak-2026-05-30-upload-custom-contract` (3 pliki: order, order-admin, contract).

**Workflow Stefana Nicolae (#350835, RO) — dwie ścieżki dziś:**
1. *Templatem:* Ruslan klika „Wygeneruj PDF" (przycisk widoczny bo dane Stefana kompletne po v0.32.60 sesji chrome), ogląda PDF, OK → dropdown na „Umowa gotowa" → mail leci.
2. *Własną umową:* Ruslan rozwija sekcję „Wgraj własną umowę", wybiera plik PDF z dysku (wcześniej przygotowany w Word/Adobe), wpisuje numer (`AA/2026/0014` lub własny), klika „Wgraj umowę" → PDF zapisany. Potem dropdown na „Umowa gotowa" → mail z magic linkiem leci, klient widzi w panelu wgraną umowę zamiast template'owej.

---

## 0.32.60 — 2026-05-30 (Walidacja billing dla klientów zagranicznych — NIP/CUI 8-13 cyfr, kod pocztowy elastyczny)

**Powód:** Stefan Nicolae (zamówienie #350835, RO) podał Ruslanowi dane firmy rumuńskiej SC Burger Society SRL — CUI `46732411` (8 cyfr) i kod pocztowy `010025` (6 cyfr ciągiem). Walidacja w `saveCustomerData()` miała sztywne regexy pod PL: `^\d{10}$` dla NIP i `^\d{2}-\d{3}$` dla kodu — oba odrzucały rumuńskie dane. Ruslan nie mógł wpisać.

**Dry-run pokazał (przed fixem):**
- `46732411` → ODRZUCONE („NIP musi mieć 10 cyfr")
- `010025` → ODRZUCONE („Kod pocztowy: format XX-XXX")
- `+40763971874` → PRZEJDZIE (regex telefonu już międzynarodowy)

**Decyzja klienta (Janka):** zluzować globalnie, bez dropdowna kraju — najprostsze, działa dla większości EU bez UI changes.

**Zmiany:**

1. **`class-asiaauto-order.php::saveCustomerData()`** — luźniejsze regexy:
   - NIP/CUI: `^\d{8,13}$` (PL=10, RO=8, DE=9-11, CZ=8-10, IT=11, FR=9-11). Strip prefiksu kraju (`PL`/`RO`/`DE`/itd.) przed walidacją — wpisanie `PL1234567890` lub `RO46732411` też przejdzie.
   - Kod pocztowy: `^[A-Z0-9][A-Z0-9\s\-]{2,9}$` (case-insensitive). Akceptuje PL `XX-XXX`, RO `123456`, DE `12345`, CZ `123 45`, UK `SW1A 1AA`, US `12345`. Odrzuca <3 znaki i >10 znaków.

2. **`class-asiaauto-order-admin.php::renderCardCustomer()`** — podbita `maxlength` w formularzu admin:
   - NIP: `13 → 15` (z marginesem na prefiks), placeholder zaktualizowany („10 cyfr (PL) / 8 cyfr (RO CUI) / itd.")
   - Kod pocztowy: `6 → 10`, placeholder „PL: XX-XXX / RO: 6 cyfr / DE: 5 cyfr"

**Test po fixie (`/home/host476470/tmp/test-validation-after.php`):**
- NIP: PL ✓ / RO ✓ / DE ✓ / CZ ✓ / PL z prefiksem ✓ / RO z prefiksem ✓ / <8 cyfr ✗ / >13 cyfr ✗
- Kod: PL XX-XXX ✓ / RO 010025 ✓ / DE 10115 ✓ / CZ „110 00" ✓ / UK SW1A 1AA ✓ / US 90210 ✓ / <3 znaki ✗ / >10 znaków ✗

**Backupy:**
- `class-asiaauto-order.php.bak-2026-05-30-loose-international`
- `class-asiaauto-order-admin.php.bak-2026-05-30-loose-international`

---

## 0.32.59 — 2026-05-30 (Workflow zamawiania: usunięcie auto-advance + powiadomienie admina o wypełnieniu billing)

**Powód:** klient (Ruslan) chce sam decydować kiedy wystawić umowę. Obecnie gdy klient wypełnił komplet danych w wizardzie krok 3 (`/order/{id}/billing`), system automatycznie zmieniał status `potwierdzone → umowa_gotowa`, przydzielał numer kontraktu, generował PDF i wysyłał mail klientowi — Ruslan tracił kontrolę nad timingiem. Plus problem klientów zagranicznych (np. Stefan Nicolae, RO, zamówienie #350835 — firma SC Burger Society SRL z CUI/adresem rumuńskim), gdzie Ruslan musi dane przetłumaczyć/sprawdzić przed wystawieniem umowy.

**Cztery zmiany:**

1. **`class-asiaauto-order-api.php::submitBilling()`** — usunięty auto-advance `potwierdzone → umowa_gotowa`. Po wypełnieniu billing status zostaje `potwierdzone`. Sprzedawca sam zmienia status (bramka v0.32.54 sprawdzi komplet, hook wygeneruje PDF, mail do klienta poleci `status_umowa_gotowa`).

2. **`class-asiaauto-order.php::sendBillingCompletedEmail()`** — nowa metoda, wysyła mail do admina (Reply-To: klient) gdy klient wypełnił komplet danych przy statusie `potwierdzone`. Wywoływana z `submitBilling()` zamiast auto-advance. Wymaga `admin_email_notifications=1` w config.

3. **`class-asiaauto-order-content.php`**:
   - Nowy default template `customer_billing_completed` (subject + 9-linijkowy body) z `{admin_link}` do panelu zamówienia.
   - Dodany do `getTemplateKeyLabels()` jako „Klient wypełnił dane → Admin" (panel ustawień templates).
   - Poprawiona treść `status_potwierdzone` punkt 3: zamiast „Umowa zostanie wygenerowana automatycznie..." → „Sprzedawca przygotuje umowę po uzupełnieniu danych — otrzymasz osobne powiadomienie mailem gdy umowa będzie gotowa do podpisu."

4. **`submitBilling()` response message** — zamiast „Dane zapisane. Zaakceptuj warunki umowy." (sugerowało że umowa jest gotowa) → „Dane zapisane. Sprzedawca przygotuje umowę i powiadomi Cię mailem." Pole `step` zawsze 3 (nie skoczy na 4).

**Zachowane bez zmian:**
- Auto-regen PDF gdy klient poprawia dane na **już istniejącej umowie** (status `umowa_gotowa+`) — to inny use case, dane↔PDF muszą być spójne.
- Bramka v0.32.54 `isCustomerDataComplete` przy `changeStatus → umowa_gotowa` — działa.
- Maile statusowe po ręcznej zmianie statusu przez admina (klient dostaje `status_umowa_gotowa` z magic linkiem normalnie).

**Workflow docelowy:**
1. Ruslan klika „Potwierdź" → status `potwierdzone` → mail do klienta z magic linkiem (template z pkt 3 poprawiony, mówi „sprzedawca przygotuje umowę po uzupełnieniu")
2. Klient wpisuje dane → walidacja → zapis → komunikat „Sprzedawca przygotuje umowę i powiadomi Cię mailem"
3. **Mail do Ruslana** „Klient wypełnił dane → Admin"
4. Ruslan sprawdza w panelu (sekcja „Dane do umowy" v0.32.55 z badge'em Komplet), ewentualnie poprawia
5. Ruslan klika dropdown statusu → „Umowa gotowa" → bramka v0.32.54 OK → numer kontraktu + PDF + mail `status_umowa_gotowa` do klienta z magic linkiem

**Backupy:** `*.bak-2026-05-30-no-autoadvance` (3 pliki: order-api, order-content, order).

---

## 0.32.58 — 2026-05-28 (page.php H1 fix + cross-link „Galeria sprzedanych aut" na single listing)

**Cel:** naprawa duplikatu tytułu w edytorze Gutenberga `/klienci/` (post_title pole + wp:heading {level:1} w content) + cross-link do galerii klientów z każdego single listing.

**Zakres:**

1. **`themes/primaauto2026/page.php`** — dodany `<h1 class="entry-title"><?php the_title(); ?></h1>` przed `the_content()`. Wcześniej page.php renderował tylko content, więc tytuł postu nie pojawiał się jako H1 nigdzie na stronie. To był ukryty bug (audyt podczas tej sesji: 8 z 13 stron page.php nie miało H1 w ogóle). Po fixie: post_title jest H1 (standardowy WP).

2. **`themes/primaauto2026/functions.php`** — `PRIMAAUTO_THEME_VERSION` 1.0.6 → 1.0.7.

3. **`includes/class-asiaauto-contact.php`** linia 154 — H1 → H2 dla `aa-contact__hero-title` (uniknięcie duplikatu z `entry-title` z page.php). CSS selectorem `.aa-contact__hero-title` styling nieruszany.

4. **`includes/class-asiaauto-single.php`**:
   - `infoBox()` (sidebar desktop + mobile) — dodany 5-ty link `['Galeria sprzedanych aut', home_url('/klienci/')]`
   - `uspStrip()` kolumna „Informacje" — dodany 5-ty wpis `['icon' => 'dot', 'text' => 'Galeria sprzedanych aut', 'href' => home_url('/klienci/')]`

5. **WP page 350745 Klienci** — `post_title` cofnięty na pełną nazwę („Klienci Prima-Auto — auta, które dla nich sprowadziliśmy"), wp:heading {level:1} usunięty z content (duplikat z entry-title).

6. **WP pages 153875 (W drodze) + 153877 (W rzeszowie)** — usunięte `<h1>` z post_content (duplikat z entry-title).

**Stan H1 po fixie (smoke test wszystkich page.php pages):**
- ✅ klienci, kontakt (H2 teraz), polityka-prywatnosci, w-drodze, w-rzeszowie — 1 H1 z entry-title
- ⚠️ regulamin, o-nas, finansowanie, jezyk-obslugi-pojazdu, pod-dom-do-rejestracji, proces-zamawiania, gwarancja-i-serwis, homologacja-i-rejestracja — **nadal 0 H1** (te strony nie używają primaauto2026/page.php — pewnie Elementor Pro mimo `add_filter('elementor/theme/get_location_templates', '__return_empty_array')`. Wymaga osobnego audytu).

**Pending follow-up:** zdiagnozować dlaczego 8 stron nie używa naszego page.php (`pa-main` markup nieobecny). Możliwe sources: Elementor Pro Theme Builder mimo disable filtra, jakiś inny plugin/snippet, page templates ustawiane per page z UI.

**Backupy:** `*.bak-2026-05-28-h1` (page.php, class-asiaauto-contact.php), `*.bak-2026-05-28-klienci-link` (class-asiaauto-single.php).

---

## 0.32.57 — 2026-05-28 (Galeria klientów `/klienci/` — Gutenberg Gallery block, ZERO kodu)

> **Wieczorny rollback:** pierwotnie wdrożone jako custom page template `themes/primaauto2026/page-klienci.php` (~360 linii: PHP query + inline CSS grid 4/3/2 + vanilla JS lightbox + ImageGallery JSON-LD + theme bump 1.0.6→1.0.7). **Cofnięte.** Powód: Gutenberg ma `wp:gallery` z `imageCrop:true` + per-image `lightbox.enabled:true` (Interactivity API od WP 6.4+). Zero custom kodu, drag&drop dla Ruslana, edycja w wp-admin. Plik usunięty z theme i repo, `PRIMAAUTO_THEME_VERSION` z powrotem 1.0.6.

**Stan po rollbacku:**

1. **WP page `/klienci/`** (ID 350745) — content = bloki Gutenberga: `wp:heading {level:1}` (H1) + `wp:paragraph` (lead) + `wp:gallery {columns:4, imageCrop:true, linkTo:"none", sizeSlug:"medium_large"}` z 47 zagnieżdżonymi `wp:image` (każdy z `lightbox:{enabled:true}`) + `wp:heading {level:2}` + CTA. `_wp_page_template=''` (default `page.php`). Featured 350682, RankMath meta bez zmian.

2. **Menu „Klienci"** — pos 5 w `header`, `db_id=350746` (bez zmian).

3. **Zarządzanie galerią dla Ruslana:** wp-admin → Strony → Klienci → Edytuj. Blok Galeria → klik `+` żeby dodać z biblioteki, drag żeby przestawić, `×` żeby usunąć, „Aktualizuj". Koniec. Auto-discovery po nazwie usunięte — Gallery block trzyma listę ID-ków w post_content (`{"ids":[...]}`).

**Konsekwencje:**
- HTML 240KB (vs 143KB w custom) — Gutenberg dodaje `wp-block-library` CSS + Interactivity API JS dla lightboxa. Akceptowalne: 0.12s response time, lazyload native.
- Brak `ImageGallery` JSON-LD — Gallery block sam się indeksuje przez `<img>` + alty. Jeśli za 1-2 mies. okaże się brak signalu, dorzucimy filterem w functions.php.
- Kolejność = ręczna w edytorze (drag&drop), nie ASC po nazwie. Ruslan widzi co dodaje gdzie.

**Smoke test (rollback):** HTTP 200, 0.12s, 240KB, 47 `wp-block-image`, lightbox triggers via Interactivity API obecne.

**Backupy:** brak (rollback przez `wp eval-file` na ID 350745 + `git rm` theme files).

**Pending follow-up:**
- Cross-site linki do `/klienci/` (single listing, strona główna, `/zamow/`) — decyzja gdzie/jak.
- OG image dedykowany 1200×630 (obecnie #001 ~3:4).
- Banner z liczbami w hero — wymaga konkretu od Ruslana.

<details><summary>Historyczna treść (custom template — usunięte)</summary>

**Cel:** wdrożenie galerii social proof — 47 zdjęć klientów Prima-Auto z autami sprowadzonymi z Chin. Decyzje produktowe zamknięte 2026-05-27 (memory `project-client-gallery-consents`); user dorzucił batch zdjęć do biblioteki mediów (mask `klienci-prima-auto-NNN.webp`, 001-047).

**To NIE jest zmiana pluginu `asiaauto-sync`** — pełen zakres siedzi w themie `primaauto2026`. Wpisujemy pod 0.32.57 tylko dla spójności trackingu sesji. `ASIAAUTO_VERSION` w pluginie NIE bumpowane.

**Zakres:**

1. **`themes/primaauto2026/page-klienci.php`** (NEW) — Template Name „Klienci — galeria social proof". Query attachmentów: `post_type=attachment`, `post_mime_type=image/webp`, `s=klienci-prima-auto`, filter po `post_name` LIKE `klienci-prima-auto-*`, orderby title ASC. 47 unikalnych ID (350682-350728). Grid 4/3/2 col (desktop/tablet/mobile), kwadrat `aspect-ratio: 1/1` + `object-fit: cover` (rozwiązuje różne proporcje oryginałów bez letterboxa). Inline `<style>` + `<script>` (scope `.aa-klienci-*`, vanilla JS ~80 linii lightbox z klawiaturą + swipe touch).

2. **`themes/primaauto2026/functions.php`** — `PRIMAAUTO_THEME_VERSION` 1.0.6 → 1.0.7 (cache bust).

3. **WP page `/klienci/`** (ID 350745) — `post_status=publish`, `_wp_page_template=page-klienci.php`, featured image 350682 (klienci-prima-auto-001). RankMath meta: `title`, `description`, `facebook_title`, `facebook_description`, `facebook_image_id=350682`, `twitter_use_facebook=on`.

4. **Menu `header`** (term_id 6033) — nowa pozycja „Klienci" na pozycji 5, link `/klienci/`, między „Marki" (4) a „Informacje" (6). `db_id=350746`.

5. **Schema ImageGallery JSON-LD** — render warunkowy gdy `$gallery_items` niepuste. 47 `ImageObject` z `contentUrl` (full) + `thumbnailUrl` (medium_large) + `width`/`height` z `wp_get_attachment_image_src`.

**Decyzje techniczne:**
- **Brak osobnego CSS file** (tj. brak `klienci.css` enqueued) — inline `<style>` ładuje się tylko na tej stronie, nie obciąża globalnego CSS. Trade-off: trochę inline kodu vs. dodatkowy roundtrip dla 200 linii CSS.
- **Brak nowego JS file** — vanilla lightbox inline. Trade-off jw. Brak dependency na bibliotekę (Fancybox/GLightbox).
- **Lazyload native** (`loading="lazy"`), pierwsze 6 zdjęć `loading="eager"` — LCP-friendly dla above-the-fold.
- **A11y** — `<button>` zamiast `<a>` dla tile (akcja JS, nie nawigacja), `aria-label` per tile, `role="dialog"` na lightbox, `aria-hidden` toggle, focus return po close.

**Decyzje produktowe (auto-mode defaults wybrane przez Claude bez quiza):**
- Scope: publiczna `/klienci/` (zdjęcia w bibliotece = ten task), NIE admin tab opisany w `PROMPT-zakladka-klienci-2026-05-28.md`.
- Layout: kwadrat object-fit:cover (zgodnie z sugestią user'a „miniatury w kwadracie albo trzeba JAKIŚ ŁADNY wygląd").
- Lightbox: vanilla inline (brak istniejącego lightboxa w themie — grep nic nie znalazł).
- SEO: ImageGallery JSON-LD + neutralny lead (bez konkretnych liczb, bo Ruslan ich nie podał).

**Smoke test:**
- `https://primaauto.com.pl/klienci/` → HTTP 200, 0.18s, 143KB.
- 47 `<button class="aa-klienci-tile">` w renderze (grep `data-index=` == 47).
- 1 `ImageGallery` JSON-LD obecne.
- H1 prawidłowy (z post_title).

**Backupy:** brak (nowy plik `page-klienci.php`, jedyna zmiana w `functions.php` to bump wersji 1 char).

**Pending follow-up (NIE w tej wersji):**
- Cross-site linki do `/klienci/` (single listing, strona główna, `/zamow/`).
- OG image dedykowany 1200×630 (obecnie #001 ~3:4).
- Banner przy hero (konkretne liczby działalności — wymaga decyzji Ruslana).

</details>

---

## 0.32.56 — 2026-05-28 (Wycofanie pól „Typ dokumentu" + „Numer dokumentu" z UI i PDF)

**Decyzja klienta (Ruslan, 28.05):** pola „typ dokumentu" (dowód osobisty/paszport) i „numer dokumentu" nigdy nie były używane, komplikowały klientom wypełnianie umowy i są zbędne. Wycofujemy z UI i PDF.

**Zakres:**

1. **`class-asiaauto-order.php::CUSTOMER_META`** — `billing_id_type` i `billing_id_number` zmienione z `required: true` → `required: false`. Wpisy zostają w strukturze (getCustomerData() nadal je zwróci) na wypadek istniejących danych w user_meta starych klientów. `isCustomerDataComplete()` (iteracja po required=true) przestaje ich wymagać.

2. **`class-asiaauto-order-wizard.php::getBillingFieldDefs()`** — usunięte 2 wpisy z definicji pól wizard frontend. Klient nie widzi już tych pól w kroku „Dane do umowy".

3. **`class-asiaauto-order-admin.php`**:
   - `renderCardCustomer()` — usunięty blok `cols-id` z typem dokumentu (select) i numerem (input).
   - `handleUpdateCustomerBilling()` — usunięte 2 keys (`billing_id_type`, `billing_id_number`) z whitelist pól przyjmowanych z $_POST.

4. **`class-asiaauto-contract.php`**:
   - Mapowanie placeholderów (linie 254-255) — usunięte `customer_id_type` i `customer_id_num`.
   - Layout PDF (linia ~571) — usunięta linia `{customer_id_type}: {customer_id_num}<br>` z nagłówka „Zleceniodawca".

**Historyczne dane:**
- Wpisy w user_meta `billing_id_type` / `billing_id_number` zostają w bazie (nie czyścimy).
- 5 historycznych umów z dziurami (AA/2026/0006, 0008, 0011, 0012, 0013) — bez ruchu. Gdy ktoś ręcznie zregeneruje PDF, linia „dowód osobisty: ..." zniknie z dokumentu (bo placeholder już nie istnieje).
- 0 userów stało się „nagle kompletnymi" po fixie (smoke test) — brak side effectów na istniejące dane.

**`case 'billing_id_type'` w `saveCustomerData()`** — zostawiony jako defensywna walidacja (gdyby ktoś jednak wysłał payload z `billing_id_type` z innej drogi, sprawdza czy `dowod|paszport`). Nie szkodzi, w UI pola nie ma.

**Backupy:** `*.bak-2026-05-28-remove-id-doc` (4 pliki: order, order-wizard, order-admin, contract).

---

## 0.32.55 — 2026-05-28 (Formularz „Dane do umowy" w panelu zamówienia + auto-regen PDF)

**Problem (następstwo v0.32.54):** bramka v0.32.54 zaczęła blokować przejście na `umowa_gotowa` gdy klient nie ma kompletu billing — ale **nie istniało UI dla admina** do uzupełnienia tych danych. Funkcja `saveCustomerData()` była dostępna tylko przez REST endpoint `submitBilling` (wizard frontend dla klienta, z guardem `status === 'potwierdzone'`). W standardowym WP-admin → Users → Edit klient widać tylko WP-natywne pola, nie nasze `billing_pesel/nip/id_type/id_number/address_*`. Ruslan po wdrożeniu v0.32.54 zadzwonił, że klikał „edytuj użytkownika" i nie widzi gdzie wpisać dane.

**Fix v0.32.55 — trzy zmiany:**

1. **`class-asiaauto-order-admin.php` — nowa sekcja w karcie „Klient"** (`renderCardCustomer`): badge „Komplet"/"Niekompletne" + formularz inline z 4 sekcjami (Dane osobowe / Identyfikator / Adres / Firma). Layout: kod pocztowy 140px obok miasta, typ dokumentu 200px obok numeru, reszta 50/50. Submit → `handleUpdateCustomerBilling()` → woła istniejące `AsiaAuto_Order::saveCustomerData()`.

2. **Auto-regen PDF** w `handleUpdateCustomerBilling()`: po zapisie billing, jeśli `isCustomerDataComplete()` zwraca true i status zamówienia to `umowa_gotowa`/`podpisane`/`zarezerwowane`/`zakupione`/`w_drodze`/`na_placu`/`w_dostawie` → `AsiaAuto_Contract::regenerate($order_id)`. Powód: dla zamówień z dziurami z czasu sprzed v0.32.54 (5 historycznych umów: AA/2026/0006, 0008, 0011, 0012, 0013) admin wpisuje dane → PDF się odświeża jednym kliknięciem zapisu, bez konieczności osobnego „Regeneruj umowę".

3. **`class-asiaauto-order-api.php` — rozluźniony guard `submitBilling`:** klient może teraz edytować billing w statusach `potwierdzone`/`umowa_gotowa`/`podpisane`/`zarezerwowane`/`zakupione`/`w_drodze`/`na_placu`/`w_dostawie` (wcześniej tylko `potwierdzone`). Auto-advance `potwierdzone → umowa_gotowa` zostaje (historyczne zachowanie). Plus analogiczny auto-regen PDF jak w (2) — gdy klient sam poprawia dane w wizardzie po wygenerowaniu umowy.

**Backupy:** `class-asiaauto-order-admin.php.bak-2026-05-28-customer-billing-form`, `class-asiaauto-order-api.php.bak-2026-05-28-customer-billing-form`

**Lessons learned (auto-memory):** w mailu do Rusłana z 27.05 obiecałem „uzupełnij dane ręcznie w panelu admina" zakładając, że UI istnieje skoro `saveCustomerData()` jest w kodzie — bez grep'a po pliku order-admin. Funkcji w UI nie było. Konfabulacja, którą Ruslan wykrył dzwoniąc 28.05. Patrz `feedback_no_unverified_ui_claims.md`.

---

## 0.32.54 — 2026-05-27 (Bramka kompletności danych klienta przed `umowa_gotowa`)

**Problem:** umowa `AA/2026/0013` (Miron Orłowski) wygenerowana z pustym adresem i napisem „dowód osobisty:" bez numeru. Admin (Ruslan) przeszedł `potwierdzone → umowa_gotowa` 2 minuty po `weryfikacja → potwierdzone`, klient nie zdążył (i nie musiał) wypełnić kroku 3 wizardu z billing. PDF wygenerował się automatycznie z fallbackami: pusty adres, ternary `?? 'dowod'` wstawił „dowód osobisty" mimo że nic nie wybrano.

**Root cause:** `AsiaAuto_Order::changeStatus()` sprawdzała tylko graf `TRANSITIONS`, nie wołała `isCustomerDataComplete()` (funkcja istniała od dawna w `order.php:1069`, tylko nikt jej nie używał przy zmianie statusu).

**Fix (`class-asiaauto-order.php::changeStatus()`):** bramka po sprawdzeniu TRANSITIONS — gdy `$new_status === 'umowa_gotowa'` i `$order_type === TYPE_CUSTOMER` i `!isCustomerDataComplete($customer_id)` → `WP_Error('customer_data_incomplete', ...)`. Komunikat wskazuje administratorowi gdzie uzupełnić (krok wizardu klienta lub panel admin).

**Stock orders nietknięte:** `createInternal()` wchodzi w `w_drodze`/`na_placu`/`zakupione`/`zarezerwowane` — graf TRANSITIONS z tych statusów nie prowadzi do `umowa_gotowa`. Guard `$order_type === TYPE_CUSTOMER` = defense in depth.

**Stan po deployu:**
- 1 customer order natychmiast zablokowany: `#339595` (BYD Leopard 5, Agnieszka Koman, `potwierdzone`, brak billing) — admin musi poprosić klientkę o uzupełnienie lub wypełnić ręcznie.
- 5 historycznych umów z dziurami (AA/2026/0006, 0008, 0011, 0012, 0013) zostaje — forward-only fix, naprawiane per case.

**Backup:** `class-asiaauto-order.php.bak-2026-05-27-customer-data-gate`

---

## 0.32.53 — 2026-05-20 (Generyczne redirecty 404 — porządkowanie GSC)

**Cel:** wyczyścić ~1300 trafień 404 Googlebota/dzień (martwe huby + sprzedane listingi). Przyczyna: stare slugi sprzed importer slug fix (v0.32.42) + listingi trwale usunięte (>30d, poza zasięgiem detectListingNotFound).

**Dodane do `class-asiaauto-redirects.php` — generyczne, samonaprawiające, BEZ hardcode i BEZ 410 dla hubów (bo modele wracają):**
- `redirectHubMakePrefix()` (template_redirect prio 1, tylko na is_404) — 3 warstwy dla hubów `/samochody/<make>/<serie>/`:
  1. **Prefiks marki**: `changan/changan-uni-z` → odcina `changan-` → 301 na `uni-z` (get_term_link, kanoniczny URL).
  2. **Normalized match**: `sea-lion-07dm` → `sealion-7-dm`, `seal-05-dm` → `seal-5-dm` (normalizacja: usuń myślniki + wiodące zera; tylko exact-norm, nie prefix).
  3. **Brand fallback**: model martwy/zmieniony, marka żyje → 301 na hub marki. Warunkowane is_404 → gdy model wróci (term=200), redirect się NIE odpala. Zero blokady powrotu.
  + feed strip: `/samochody/<x>/feed/` → 301 na hub bez feed (główny `/feed/` bloga nietknięty).
- `resolveHubFromSlug()` + sam-make case — parsuje markę+model ze slugu listingu (longest-prefix po slugach marek), działa po trwałym usunięciu posta. Wpięte w `detectListingNotFound`: 301 hub modelu → hub marki. Listing bez modelu (`haval-2025-id`) → hub marki.
- `send410()` — 410 Gone TYLKO dla listingu (sprzedany egzemplarz NIE wraca) bez żadnej rozpoznawalnej marki. Huby nigdy nie dostają 410.
- helpery: `serieLinkBySlug()`, `normalizeSlug()`, `findSerieByNormalizedSlug()`, `getMakeSlugs()` (cache per-request).

**Wynik (pomiar curl 683 unikalnych 404 z logów maja):** **677 → 301 (99,1%)**. Zostaje 5 śmieci (U+2060 w URL, listing bez roku) = 404 słusznie. 0 niepożądanych 410. Regression: żywe huby/listingi/feed bloga = 200; hub `super-v23` ożył naturalnie (nowy import → 200).

**Backup:** `class-asiaauto-redirects.php.bak-2026-05-20-pre-generic-404`, `*-pre-brand-fallback`

**Zdiagnozowane przy okazji (osobne taski, NIE w tej wersji):**
- **1876/2239 serie z count=0** (puste huby) — NIE w sitemapie (RankMath wyklucza puste), więc nie zgłaszane Google. Do rozważenia: noindex gdy count=0 (samonaprawiające).
- rewrite slug taksonomii `serie` = `model` → niektóre `get_term_link` dają 2-hop chain (`/model/i6/` → `/samochody/li-auto/i6/`). Minor.

## 0.32.51 — 2026-05-20 (Indexing API — wycięcie URL_DELETED, go-live URL_UPDATED-only)

**Cel:** hook Indexing API (v0.32.49) zawierał niezlecony `trash→URL_DELETED` (scope creep). Wycięty — hook zgłasza wyłącznie URL_UPDATED na publish nowego ogłoszenia. Sprzedaż (publish→trash) obsługuje 301-na-hub w `class-asiaauto-redirects.php`.

**Powody wycięcia:** niezlecony + sprzeczny z 301 (Google idzie za realnym HTTP) + bug (`get_permalink()` trashowanego posta zwraca URL z `__trashed`, nigdy nieindeksowany) + marnował wspólną quotę Indexing API (200/dz per GCP project, dzielona z innymi projektami).

**Pliki:**
- `includes/class-asiaauto-indexing.php` — `resolveNotificationType()`: usunięty branch `trash→TYPE_DELETED` (zwraca `null`). Stała `TYPE_DELETED` pozostaje (nieużywana). Docblock zaktualizowany.

**Go-live (2026-05-20 ~09:16):** `asiaauto_indexing_enabled=1`, armed. Live test ID 340966 → HTTP 200. Bez daily cap (decyzja: hook rozkłada ~162/dz na 24h, praca 8-18).

**Backup:** `class-asiaauto-indexing.php.bak-2026-05-20-pre-urldelete-cut`

**ADR:** `docs/decyzje/2026-05-20-indexing-api-url-update-only.md`

## 0.32.50 — 2026-05-19 (Hub marek — pivot title na agregator-style Wariant C)

**Cel:** ujednolicić title hub marek z formatem hub modeli (działający, 60% fraz `{model} import` w DFS top 10). Hub marek miały dotychczas `{Make} — Auto z Chin | Prima-Auto` — bez ceny i licznika. Pivot na `{Make} — od {min} PLN, {count} sztuk | Import z Chin | Prima-Auto`.

**Decyzja oparta na danych:**
- GSC 28d, 10 top hubów marek: **0% impressions z „import"**, 254 bez. Pivot na poziomie marki NIE z powodu „import" jako KW (zero search demand) ale z powodu spójności wzorca + dodanie ceny+count (intent „cena" wszechobecny w GSC).
- Wariant C wybrany przez Janka: „szkoda marnować znaków" — pełne aggregator-style.

**Pliki:**
- `includes/class-asiaauto-hub-title-generator.php` — rozszerzenie:
  - `regenerateForMakeTerm(int $term_id)` — odpowiednik `regenerateForTerm` dla `make`
  - `regenerateAllMakes()` — bulk wszystkie marki z count > 0
  - `buildMakeTitle()` + `buildMakeDescription()` — wzorce
  - `getMakePriceRange()` — SQL JOIN przez `make` taxonomy (zamiast `serie`)
  - `pluralizeOferty()` — dla description
  - `brandSlugToDisplay()` zmienione z `private` na `public` (utility)
  - Hook `asiaauto_after_set_taxonomies` rozszerzony: po imporcie regen + serie + make
  - Daily cron `asiaauto_regen_hub_titles_daily` woła obie funkcje
- `cli/class-asiaauto-cli.php` — nowy sub-command `regen-make-titles [--all|--term=<id>] [--dry-run]`

**Backup:**
- `class-asiaauto-hub-title-generator.php.bak-2026-05-19-pre-make`
- termmeta dump: `~/backups/primaauto/2026-05-19-make-titles/before-bulk.tsv`

**Wynik bulk:** 61/61 marek z `count > 0` zaktualizowane (100%). Dystrybucja długości title:
- ≤60ch: 11
- 61-70ch: 47
- >70ch: 3 (Dongfeng Fengshen 73, Dongfeng Fengxing 73, Beijing Off-Road 72)
- Avg 63, max 73

**Live verification:** 3 sample URL-i sprawdzone (BYD/Geely/Volkswagen) — title + description renderują się natychmiast (zero RankMath cache).

**Co NIE ruszono:**
- Hub modeli (~340) — już Wariant C, działa (DFS 60% top 10)
- Single (3915) — GSC 0% imp z „import", 2.7% z „cena", marginal ruch — bez wartości pivotu
- 18 marek bez listings publish (Mercedes-Benz, Rolls-Royce, Aston Martin itd.) — count=0, fallback WP `{TermName} - {SiteTitle}` (osobny temat: noindex empty hubów)

**Memory cross-link:** [[project_session_2026_05_19_make_titles_pivot]], [[project_session_2026_05_07_seo]] (kontekst v0.32.43 generator dla serie).

---

## 0.32.49 — 2026-05-19 (Indexing API integration — przygotowane, DEFAULT OFF)

**Cel:** zamiast batch'owego pushu single listings do Google Indexing API (jak rano 2026-05-19, 192 URL wyczerpało quota), wstawiamy hook `transition_post_status` — każde nowe ogłoszenie zaraz po publish wysyła URL_UPDATED do Indexing API, każde przejście do trash wysyła URL_DELETED. Naturalna, real-time integracja. Quota Google 200/dzień mieści się w ~150-200 sync/dzień bez wybuchów batch.

**Status: WYŁĄCZONA do ręcznego włączenia.** Procedura włączenia: `tmp/indexing-api-go-live-2026-05-20.md`. Włączenie zaplanowane 2026-05-20 po 02:00 PL (po reset quota).

**Pliki:**
- `includes/class-asiaauto-indexing.php` — NOWA klasa `AsiaAuto_Indexing_API`:
  - hook `transition_post_status@20` → `onTransition()` (publish/trash dla CPT `listings`)
  - cron godzinny `asiaauto_indexing_retry_cron` → `processRetryQueue()` (max 50/run, retry 5×, stop na 429)
  - `getAccessToken()` — OAuth refresh z `~/secrets/google/{oauth-desktop-client.json,tokens.json}`, transient cache 50 min
  - `callApi()` — POST `indexing.googleapis.com/v3/urlNotifications:publish` z URL_UPDATED/DELETED
  - guard `isEnabled()` (option `asiaauto_indexing_enabled`, default false) + `isArmed()` (option `asiaauto_indexing_armed_after_utc`)
  - logi przez `AsiaAuto_Logger`
- `asiaauto-sync.php` — `require_once class-asiaauto-indexing.php` + `new AsiaAuto_Indexing_API()` w `plugins_loaded`
- `cli/class-asiaauto-cli.php` — 3 sub-commands:
  - `wp asiaauto indexing-test --id=<id> [--type=URL_UPDATED|URL_DELETED] [--live]` (dry-run domyślnie, OAuth refresh test)
  - `wp asiaauto indexing-status` (queue size, flags, cron schedule)
  - `wp asiaauto indexing-drain` (manual drain retry queue, respektuje quota/guards)

**Backups:**
- `asiaauto-sync.php.bak-2026-05-19-pre-indexing`

**Bezpieczniki (option ustawione 2026-05-19):**
- `asiaauto_indexing_enabled = 0`
- `asiaauto_indexing_armed_after_utc = 2026-05-20T00:00:00Z`

**Dry-run test 2026-05-19:** post #338530 (geely-galaxy-l6) — OAuth OK (token 254), oba guard'y blokują, success, zero API call. Quota Google nietknięta.

**Memory cross-link:** `[[project_session_2026_05_18_seo_hubs_in_progress]]`, `[[reference_google_seo_stack]]`.

---

## 0.32.48 — 2026-05-16 (W3: filtr „Ręczny import" w admin views)

**Cel:** w `edit.php?post_type=listings` dodać link „Ręczny import (X)" obok natywnych „Wszystkie | Moje | Opublikowane | Szkice | Kosz". Filtruje po `_asiaauto_manual_import=1` (TYLKO listings dodane przez UI „Dodaj z Dongchedi"). Bez ograniczenia po autorze — admin i Ruslan widzą tę samą listę.

**Pliki:**
- `includes/class-asiaauto-admin-listings-views.php` — NOWA klasa `AsiaAuto_Admin_Listings_Views`:
  - hook `views_edit-listings` → `addManualImportView()` (dodaje link)
  - hook `pre_get_posts` → `filterManualImportQuery()` (ustawia `meta_query` gdy `?asiaauto_view=manual_import`)
  - prywatna `countManualImports()` z DISTINCT count na `_asiaauto_manual_import=1` (NOT IN trash, auto-draft)
- `asiaauto-sync.php` — `require_once` + `new AsiaAuto_Admin_Listings_Views()` w bloku `if (is_admin())`

**Backup:** `asiaauto-sync.php.bak-2026-05-16-pre-w3`

**Smoke test (`tmp/w3-smoke-test.php`):**
- Klasa ładowana via `require_once` ✓
- Hooki registered (`views_edit-listings@10`, `pre_get_posts@10`) ✓
- `countManualImports()=69` vs direct SQL=69 ✓ (po restore 8 listings)
- `addManualImportView()` dorzuca klucz `asiaauto_manual_import` ✓
- `filterManualImportQuery()` bez param → meta_query empty ✓; z `?asiaauto_view=manual_import` → `meta_query[0]={key:_asiaauto_manual_import, value:1, compare:=}` ✓

**Weryfikacja w admin:** `https://primaauto.com.pl/wp-admin/edit.php?post_type=listings` — link „Ręczny import (69)" widoczny, klik filtruje.

**Decyzja w `docs/decyzje/2026-05-16-ochrona-recznie-zarzadzanych-listings.md` (sekcja „W3").**

---

## 0.32.47 — 2026-05-16 (W1: sync guard — pomijaj ręcznie zarządzane listings)

**Problem:** `AsiaAuto_Sync::run()` sprawdzał tylko `_asiaauto_reservation_status` przed `updateListing()` (case `changed`) i nic przed `markRemoved()` (case `removed`). Ignorował flagi `_asiaauto_manual_import` (UI „Dodaj z Dongchedi", 71 listings) i `_asiaauto_manual_entry` (pierwszy zapis przez metabox „Dane pojazdu", 75 listings). Skutek: ogłoszenia które Ruslan dodawał ręcznie były wycofywane przez sync z powrotem do trash (`removal_reason=sold`).

**Fix:** w `class-asiaauto-sync.php` dodana prywatna metoda `isManuallyManaged(int $post_id): bool` (sprawdza obie flagi). Wstrzelona jako guard w `case 'changed'` (przed `updateListing`) i `case 'removed'` (przed `markRemoved`). Gdy listing jest manual: log `info("Sync skip: listing #X (inner_id: Y) is manually managed, skipping changed|removed")` + `$total_skipped++`.

**Pliki:**
- `class-asiaauto-sync.php:134-167` — 2 guard'y w switch-case + `$total_skipped++` per pominięty
- `class-asiaauto-sync.php:222-240` — nowa prywatna metoda `isManuallyManaged()`

**Backup:** `class-asiaauto-sync.php.bak-2026-05-16-pre-w1`

**Zasięg ochrony (81 unique aktywnych listings):**
- `_asiaauto_manual_import=1` → 71 sztuk (publish 54, draft 8, trash 9)
- `_asiaauto_manual_entry=1` → 75 sztuk
- Konkretne wzbudzenia (Ruslan edytował metabox): 249638 (BYD Yangwang U7), 306890 (Denza Z9 DM-i)

**Smoke test (`tmp/w1-smoke-test.php` przez Reflection):**
- Case 1 (manual_import=1 #260409) → `true` ✓
- Case 2 (manual_entry=1 bez import #222255) → `true` ✓
- Case 3 (normalny sync-owy #94073) → `false` ✓
- Case 4 (planned-protect 249638, 306890) → `true` ✓ (oba)
- Real `wp asiaauto sync --source=dongchedi` — brak fatal po patchu (0 zmian z API w tej iteracji)

**Decyzja w `docs/decyzje/2026-05-16-ochrona-recznie-zarzadzanych-listings.md` (sekcja „W1").**

---

## 0.32.46 — 2026-05-16 (W2: fix DUP_BLOCKED_META — kopia nie dziedziczy rezerwacji)

**Problem:** `AsiaAuto_Listing_Editor::handleDuplicate()` przy duplikacji kopiowała wszystkie meta poza wąską blocklist. Kopia dziedziczyła `_asiaauto_reservation_status` + `_asiaauto_reservation_order_id` → blokada utworzenia drugiego zamówienia na ten sam res_order_id („order się zduplikował"). Dowody w DB (2026-05-16):
- 303534 + 314155 → `res_order_id=303657` (Denza Z9 GT DM-i)
- 317106 + 324822 → `res_order_id=317400` (BYD Leopard 7)

Dodatkowo kopia dziedziczyła historię sync-removal (`_asiaauto_removed_at`, `_asiaauto_removal_reason`, `_asiaauto_api_removed`) i flagi importu z UI „Dodaj z Dongchedi" (`_asiaauto_manual_import*`) — przekłamywało statystyki i mogło wywoływać późniejsze decyzje sync na kopię.

**Fix:** dopisane 8 kluczy do `DUP_BLOCKED_META` w `class-asiaauto-listing-editor.php`:
```php
'_asiaauto_api_removed',
'_asiaauto_removed_at',
'_asiaauto_removal_reason',
'_asiaauto_manual_import',
'_asiaauto_manual_import_at',
'_asiaauto_manual_import_by',
'_asiaauto_reservation_status',
'_asiaauto_reservation_order_id',
```

**Pliki:**
- `class-asiaauto-listing-editor.php:80-111` — rozszerzona stała `DUP_BLOCKED_META`

**Backup:** `class-asiaauto-listing-editor.php.bak-2026-05-16-pre-w2`

**Decyzja w `docs/decyzje/2026-05-16-ochrona-recznie-zarzadzanych-listings.md` (sekcja „W2").**

**Cleanup istniejących par (303534+314155, 317106+324822) — osobnym krokiem po smoke teście, z backupem mysqldump.**

---

## 0.32.45 — 2026-05-15 (umowa: „rok pierwszej rejestracji" zamiast „rok produkcji")

**Problem:** Umowa generowała w polu „§1 b) rok produkcji" i tabeli specyfikacji „Rok produkcji" wartość z taxonomy `ca-year`, która w praktyce trzyma **rok modelowy** (z pola `year` API Dongchedi). Dla aut sprzedawanych jako prawie-nowe (dealer rejestruje na siebie żeby zwolnić VAT, klient odbiera po 1-12 miesiącach) `year` API ≠ kalendarzowy rok produkcji. Klient #329788 zgłosił rozbieżność: auto wyprodukowane w 2024 (potwierdza VIN `LURMCWEY6RA017761` — 10. znak `R` = rok modelowy 2024 wg ISO 3779), pierwsza rejestracja 2025-01-01, umowa pokazywała „2025".

**Ustalenie diagnostyczne:** API Dongchedi nie zwraca osobnego pola „rok produkcji". Zwraca tylko `year` (rok modelowy), `reg_date` (pierwsza rejestracja) i `extra_prep.market_time` (data wprowadzenia modelu na rynek). Wszystkie trzy często się pokrywają i dla 23653477 wszystkie były „2025".

**Decyzja:** Zmiana etykiety w umowie na „rok pierwszej rejestracji" + podstawiamy rok z meta `registration_date` (format `DD/MM/YYYY` → ostatnie 4 cyfry). Fallback: `ca-year` gdy brak rejestracji w API (1.6% listingów w 14d — głównie nowe modele/dealer-stock bez `reg_date`).

**Pliki:**
- `class-asiaauto-contract.php:327` — `'year' => self::extractRegistrationYear($listing_id, $get_term('ca-year'))`
- `class-asiaauto-contract.php` — nowa metoda `extractRegistrationYear(int $listing_id, string $fallback_model_year): string` (regex `#/(\d{4})$#` na meta `registration_date`, fallback na rok modelowy)
- `class-asiaauto-contract.php:604` — `<tr><td>b)</td><td>rok pierwszej rejestracji: ...</td></tr>` (było: „rok produkcji")
- `class-asiaauto-contract.php:975` — `['Rok pierwszej rejestracji', ...]` w tabeli specyfikacji (było: „Rok produkcji")

**Weryfikacja:** Test live `extractRegistrationYear()`:
- post 329788 (z `registration_date=01/01/2025`) → `2025` ✓
- post 328905 (bez `registration_date`, świeży Avatr 11) → fallback do `ca-year` ✓
- post 0 (nieistniejący) → fallback ✓

**Pokrycie 14d (2026-05-01 → 2026-05-15):** 2067/2101 listings (98.4%) ma `registration_date` → poprawna wartość w umowie. 34/2101 (1.6%) fallback do roku modelowego — wartość sensowna, choć etykieta wtedy lekko niespójna. Trade-off akceptowalny, problem dotyczy tylko świeżych dealer-stock przed pierwszą sprzedażą.

**Decyzja w `docs/decyzje/2026-05-15-rok-rejestracji-zamiast-produkcji.md`.**

---

## 0.32.44 — 2026-05-13 (bugfix suggestClientCif — match ceny katalogowej)

**Bug:** `AsiaAuto_Order::suggestClientCif()` używała liniowego transferu marży (`prowizja_wewn - §3`) → dopłaty do CIF. Wzór nie kompensował że pipeline B (`calculateOrderPrice()` — umowa) ma inną podstawę cła (CIF zamiast CIF+agencja) i VAT (CIF+cło zamiast pełnej bazy z pipeline A).

**Skutek:** Jak admin wpisał sugerowaną wartość w pole "CIF dla klienta" i wygenerował umowę, Załącznik nr 2 pokazywał "Szacowany łączny koszt sprowadzenia" **~1-2 tys. zł niższy** niż cena widoczna na ofercie samochodu. Klient zgłaszał rozbieżność (#315462 Deepal: 171k vs 173k, #323757 BYD: 200k vs 202k).

**Fix — nowy wzór odwrotny do `calculateOrderPrice()`:**
```
cif_pln_target = (subtotal_A − fixed_pln) / M
M              = 1 + clo% + (1+clo%)·akcyza% + (1+clo%)·(1+akcyza%)·vat%
fixed_pln      = agencja + transport + homologacja + commission_gross
```

Dla phev/bev (akcyza=0%): M ≈ 1,353. Dla petrol (akcyza 3,1%): M ≈ 1,395. Multiplikator zależny od paliwa listingu.

**Pliki:**
- `class-asiaauto-order.php` ~817-880 — nowy wzór + legacy fallback gdy brak `subtotal_pln` w breakdown
- `class-asiaauto-contract.php` ~1033-1045 — w `renderAttachment2()` gdy `_order_client_cif_usd <= 0`, użyj `suggestClientCif()` zamiast raw `real_cif_usd` z breakdown listingu (PDF od razu pokazuje poprawną cenę, bez konieczności ręcznego wpisywania przez admina)
- `class-asiaauto-order-admin.php` ~1097 — UI label "daje cenę w umowie ≈ cena katalogowa" zamiast mylącego "CIF + marża"

**Weryfikacja:** 3 ręczne testy — wszystkie cena umowy = cena listingu co do 1 zł przed zaokrągleniem ceil/1000:

| Zamówienie | Paliwo | Listing | Sugestia nowa | Cena umowy | Match |
|---|---|---|---|---|---|
| #323757 BYD Sealion 8 | phev | 202 000 | 37 688 USD | 202 000 | ✓ |
| #315462 Deepal G318 | phev | 173 000 | 31 839 USD | 173 000 | ✓ |
| #323747 smoke test | petrol | 176 000 | 31 601 USD | 176 000 | ✓ |

**Dla istniejących zamówień (status `weryfikacja`/`potwierdzone`/`umowa_gotowa`):**
- Te z `_order_client_cif_usd = 0` (admin nie wpisał) → po deployu automatycznie pokażą poprawną cenę w umowie (fallback w `contract.php`)
- Te z `_order_client_cif_usd > 0` (admin wpisał starą sugestię) → trzeba odświeżyć panel, wpisać nową sugerowaną wartość (UI pokaże poprawną liczbę), zapisać → auto-rekalkulacja `_order_price_final` → "Regeneruj umowę" → klient po zalogowaniu zobaczy nowy PDF

**Uwaga regen:** Regeneracja umowy na statusie != `podpisane` NIE wysyła automatycznie maila do klienta (linia 332-346 `handleGenerateContract`). Admin musi powiadomić klienta ręcznie.

**Backup:** `.bak-2026-05-13-cif-suggest` per file.

---

## 0.32.43 — 2026-05-07 (auto-regen hub titles — agregator pattern)

**Nowa klasa:** `class-asiaauto-hub-title-generator.php` (~200 linii) — generator title + description dla hub modelu na bazie aktualnych count + min/max(price).

**Wzór title:** `{Brand} {Model} — od {min_price} PLN, {count} sztuk | Import z Chin | Prima-Auto`

Przykłady:
- `BYD Atto 2 — od 104 000 PLN, 15 sztuk | Import z Chin | Prima-Auto`
- `Geely Preface — od 97 000 PLN, 53 sztuki | Import z Chin | Prima-Auto`
- `AITO M9 — od 290 000 PLN, 89 sztuk | Import z Chin | Prima-Auto`

**Kluczowe features:**
- **Brand prefix auto-add** — gdy term name nie zawiera marki (np. „Preface" → „Geely Preface"), dodawany z `_asiaauto_primary_make_slug` lub parent term
- **Polish pluralization** — 1 sztuka / 2-4 sztuki / 5+ sztuk
- **Brand display map** dla special cases — BYD/GAC/MG/AITO/NIO (allcaps), XPeng/HiPhi/iCAR (mixed), Mercedes-Benz/Land Rover/Lynk & Co (multi-word)
- **Skip flag** — `_asiaauto_skip_title_regen=1` per term blokuje regen (manual override)

**Hooks:**
- `asiaauto_after_set_taxonomies` — wywoływany w `class-asiaauto-importer.php:580` po `setTaxonomies()` per importowany listing → regen wszystkich serie terms którym ten listing należy
- `asiaauto_regen_hub_titles_daily` — daily cron 04:00 lokalnego czasu (catch-up)

**WP-CLI:**
- `wp asiaauto regen_hub_titles --all` — bulk regen wszystkich
- `wp asiaauto regen_hub_titles --term=<id> --dry-run` — test pojedynczego

**Bulk regen executed 2026-05-07:** **333 hubów** updated (wszystkie z `count > 0`). Smoke test 5 random URL'i: title format poprawny, brand prefix gdzie trzeba, cena+count z DB.

**Co straciliśmy** (trade-off vs spójność): 15 ręcznie tunowanych dziś hubów straciło custom USP w title (np. „50% taniej niż salon" dla BYD Sealion 7, „Hybryda 1400KM" dla Zeekr 9X). USP nadal w description. Jeśli chcemy custom USP w title per hub — można później dodać `_asiaauto_title_suffix` opcjonalny.

**Reasoning** (dlaczego dynamic title):
- Backlinko 2022: title z ceną → +15% CTR dla commercial queries
- Otodom/AAAauto/Allegro używają tego wzoru i rangują top 10
- Cena min zmienia się rzadko (~tygodnie), count codziennie → daily cron rozwiązuje stale info
- LLM-y (ChatGPT/Perplexity/AI Overviews) cytują dosłownie konkretne fakty z title → AEO benefit

---

## 0.32.42 — 2026-05-07 (v6.2 residuals cleanup phase 2: importer fix + bucket B 15)

**Kluczowy systemowy fix — importer ignorował `slug` field z brand-mapping:**

`class-asiaauto-importer.php:514` — `setTaxonomies()` przekazywał do `setTaxonomyAndMeta()` tylko 3 argumenty (post_id, taxonomy, value). Bez 4-go argumentu `$api_value` slug auto-derive'ował się przez `slugify($value)` z labela "ATTO 3 (Yuan PLUS)" → `atto-3-yuan-plus`. **Pole `'slug' => 'atto-3'` z brand-mapping-v6.1.php było dead code.**

Fix (1 linia):
```php
$serieSlug = isset($eu['slug']) ? (string) $eu['slug'] : '';
$this->setTaxonomyAndMeta($post_id, $this->tax_model, $serieFinal, $serieSlug);
```

Od v0.32.42: każdy nowy listing z mapowaną parą CN→EU dostaje keeper-slug → zero nowych orphanów dla mapped combinations. Listings podejmowanych UPDATE też zostaną reasiagned do keepera przy następnym sync'u.

**Bucket B — 15 termów wykonane (10 merge + 5 parent fix):**

MERGE do existing keepera (10):
- `seal-u-dm-i-song-plus` (13) → `seal-u-dm-i` (28 total)
- `atto-3-yuan-plus` (10) → `atto-3` (23)
- `leopard-3-tai-3-fcb` (8) → `leopard-3` (19)
- `sealion-8-dm-i-tang-l` (2) → `sealion-8-dm-i` (14)
- `sealion-8-tang-l-ev` (2) → `sealion-8-ev` (3) — EV osobno od PHEV
- `leopard-5-denza-b5` (1) → `leopard-5` (9)
- `leopard-7-tai-7-fcb-phev` (1) → `leopard-7` (8)
- `voyah-taishan` (1) → `taishan` (8)
- `zeekr-9x` (2) → `9x` (11)
- `e008` (2) → `e-008` (6)
- `fengyun-t11` (1) → `t11` (2) — keeper był ukryty pod `chery-fulwin`, dodano V62 entry

PARENT FIX (5, slug zostaje, deprecated-make pattern):
- `yangwang-u8` (5) — parent=byd, pms=byd
- `fengyun-x3` (1) — parent=chery-fulwin, pms=chery-fulwin
- `jetour-shanhai-l7-plus` (1) — parent=jetour, pms=jetour
- `yangwang-u7-ev` (1) — parent=byd, pms=byd

**Brand-mapping rozszerzone (5 nowych entries dla CN keys które wcześniej tworzyły orphany):**
- `'Chery Fengyun|Fengyun X3'` → slug='fengyun-x3'
- `'Chery Fengyun|Fengyun T11'` → slug='t11'
- `'Jetour Shanhai|Jetour Shanhai L7 PLUS'` → slug='jetour-shanhai-l7-plus'
- `'Yangwang|Yangwang U7 EV'` → slug='yangwang-u7-ev'
- `'Yangwang|Yangwang U8'` → slug='yangwang-u8'

**V62 dodane:** `'chery-fulwin' => ['fengyun-t11' => 't11']` (nowy klucz pierwszego poziomu).

**Verify:**
- Orphans: 15 → **0** ✓
- Serie terms total: 2256 → 2216 (40 ghosts deleted across A+B)
- 10 merge'y: ghost URL → 301 → keeper (200) ✓
- 5 parent fix URLs → 200 ✓ (`/samochody/byd/yangwang-u8/`, `/samochody/chery-fulwin/fengyun-x3/`, etc.)

---

## 0.32.41 — 2026-05-07 (v6.2 residuals cleanup phase 1: A delete 29 / C rename 12 / D parent fix 35)

**Scope:** taxonomy `serie` cleanup po 4 merge'ach v6.1 (GAC Trumpchi 04-29, iCAR + Galaxy 05-04, Jetour Zongheng 05-06). 95 targets, 80 wykonane, 15 pending user review (bucket B).

**Bucket A — bulk DELETE (29 termów, parent=0+count=0+merged-prefix):**
6× `great-wall-*`, 13× `trumpchi-*`, 3× `beijing-off-road-*`, 2× `changan-qiyuan-*`, 2× `fengyun-*`, 2× `yangwang-*`, 1× `jetour-shanhai-l6`. DELETE z wp7j_terms + term_taxonomy + termmeta.

**Bucket C — slug rename + V62 (12 termów):**
- 9 galaxy-* → bez prefix (geely): `a7-phev/e5/e8/l6/l7/m9/starship-6/starship-7-em-i/starship-8-phev`. V62 `'geely' => [9 entries]`.
- 3 trumpchi-* → bez prefix (gac): `m6/m8/s7`. V62 `'gac' => [+3 entries]`.
- **REVERT 4 termów:** `yangwang-u7`, `changan-qiyuan-a06-classic`, `changan-qiyuan-e07`, `jetour-shanhai-t1`. Były błędnie w bucket C — ich parent_make jest w V61 (yangwang→byd, changan-qiyuan→nevo, jetour-shanhai→jetour), więc URL `/samochody/<v61-target>/<bezprefix>/` po V61 bounceuje z powrotem. **Slug-z-prefiksem jest poprawnym wzorcem** dla deprecated makes. V63 entries dodane wcześniej (nevo/byd/jetour) cofnięte.

**Bucket D — orphan parent repair (35 termów + 4 pms fix):**
Heurystyka `_asiaauto_primary_make_slug` z listingów per orphan. UPDATE wp7j_term_taxonomy.parent dla 35. Dodatkowo INSERT/UPDATE pms dla 4 missing/wrong: `8x→zeekr`, `fushun→jmc-ev`, `shark-6→byd`, `zunjie-s800: zunju→maextro`. Wszystkie 35 mają teraz poprawny parent + pms (URL `/samochody/<make>/<slug>/` → 200).

**Bucket B — pending user review (15 termów, NO DB CHANGES):**
`tmp/v6.2-bucket-B-mapping.md`. Plan B1 (10 z keeperem — listings migration), B2 (5 bez keepera — parent fix + slug rename).

**V62/V63 zmiany w `class-asiaauto-redirects.php`:**
- V62: dodane `'geely'` (9 entries) + `'gac'` (3 nowe entries: `trumpchi-m6/m8/s7`)
- V63: bez zmian (próba dodania nevo/byd/jetour cofnięta)

**Verify finalny (smoke test):**
- 12× bucket C 301 → bezprefix target (200) ✓
- 4× revert 200 ✓ (`/samochody/byd/yangwang-u7/`, `/samochody/nevo/changan-qiyuan-e07/`, etc.)
- Bucket D sample 5/35 200 ✓
- Bucket A `/samochody/gac/trumpchi-ga3/` → 404 (term deleted) ✓
- Sitemap: serie-sitemap1=199 + sitemap2=121 = 320 (close to expected 321)
- Orphan count: 50 → 15 (= bucket B pending) ✓

**Backup:** `~/backups/primaauto/2026-05-07-v6.2-cleanup/terms-full.sql` (8.2 MB).

**Lekcja:** V61_MAKE_REDIRECTS dla deprecated marek = slug-z-prefiksem jest poprawny dla terms w hierarchii deprecated. URL routing przez V61 bounce → bezprefix slug = błąd.

---

## 0.32.40 — 2026-05-06 (Jetour Zongheng cleanup — V61 zongheng→jetour, V62 zongheng-g700→g700)

**Stan przed:** chaos — `Zongheng` istniał jako oddzielny `make` (term 6536, count=0), term `serie` `zongheng-g700` (6537, parent=jetour 4525, count=4) z URL `/samochody/zongheng/zongheng-g700/`. Listings post_title już marketingowe „JETOUR G700" ale slug i hub URL trzymały „Zongheng".

**Plan migracji (11 kroków, wykonane):**
1. Create new term `g700` w `serie` parent=jetour (term_id 6581, tt_id 6581)
2. Copy 6 termmeta z 6537 → 6581 (wiki_body 6795, faq_json 3593, seo_desc 155, _asiaauto_primary_make_slug, etc.)
3. Reassign 5 listings (4 publish + 1 trash) z tt_id 6537 → 6581
4. Update count: 6537 count=0, 6581 count=4
5. Delete orphan `zongheng` make (term_id 6536, term_taxonomy + term + termmeta)
6. Add `'zongheng' => 'jetour'` do `V61_MAKE_REDIRECTS`
7. Add `'jetour' => ['zongheng-g700' => 'g700']` do `V62_SERIE_REDIRECTS`
8. Bump `ASIAAUTO_VERSION` 0.32.40
9. Flush RankMath sitemap cache + regenerate
10. **Krytyczny gotcha:** termmeta `_asiaauto_primary_make_slug` skopiowana z 6537 zawierała `'zongheng'` (źle dla nowego term). Plugin `AsiaAuto_CPT::filterSerieTermLink` używa tej meta jako source-of-truth dla URL hub'a (nie taxonomy parent). **Update 6581 `_asiaauto_primary_make_slug = 'jetour'`** — bez tego URL hub'a był `/samochody/zongheng/g700/` zamiast `/samochody/jetour/g700/`, RM Sitemap też publikował zły URL i go wycinał (count=0 dla zongheng make).
11. Commit + push

**Verify finalny:**
- `/samochody/jetour/g700/` → 200 ✓ (4 listings, wiki, FAQ, schema)
- `/samochody/zongheng/` → 301 → `/samochody/jetour` (V61) ✓
- `/samochody/zongheng/zongheng-g700/` → 301 → `/samochody/jetour/zongheng-g700/` (V61) ✓
- `/samochody/jetour/zongheng-g700/` → 301 → `/samochody/jetour/g700/` (V62) ✓
- `/oferta/jetour-zongheng-g700-2026-302325/` → 200 (post slugi zachowane, decyzja Q1=zachowaj) ✓
- serie-sitemap: 320 → 321 (+ g700, − zongheng-g700 wycięte przez filter)
- make-sitemap: 47 (zongheng wycięte przez V61)

**Incydent:** podczas debugowania niewidoczności g700 w sitemap, `Cache::invalidate_storage()` zniszczył `rank-math-options-sitemap` (option z 30 kluczami → null). Sitemap_index.xml zwracał 404 dla wszystkich. **Recovery:** hardkodowane defaults (`pt_listings_sitemap=on`, `tax_make_sitemap=on`, `tax_serie_sitemap=on`, items_per_page=200, 30 kluczy łącznie) + `wp rewrite flush --hard`. Stan przywrócony.

**TODO osobne sesje (audyt wykazał):**
- ~25 martwych terms `serie` ze starymi prefixami sub-brand (`great-wall-*`, `trumpchi-*`, `beijing-off-road-*`, `changan-qiyuan-*`) — count=0, parent=0, do bulk delete
- ~25 z listings przypiętymi (count>0): atto-3-yuan-plus (10), seal-u-dm-i-song-plus (13), yangwang-u8 (5), galaxy-l7 (24), galaxy-starship-7-em-i (16), trumpchi-m6 (8), etc. — wymagają per-term migracji wzorem Jetour Zongheng

---

## 0.32.39 — 2026-05-06 (diag-check make/serie-without-wiki: filtr V61/V62/V63 redirected)

**Problem (znaleziony przez user'a):** diag-check „Marki bez wiki_body" zgłaszał 8 marek do generacji wiki przez n8n, ale **5 z 8** to V61_MAKE_REDIRECTS (chery-fengyun, galaxy, lotus-cars, maextro + chery-fengyun) — czyli marki które robią 301 do innych. Generowanie wiki dla nich = waste (~€0.06 × 5 = €0.30 + zaśmiecone webhooks). 

User uruchomił apply-fix, dostał 8 webhook timeoutów (n8n offline), ale wskazał że Galaxy w generacji to bałagan.

**Fix:**
- Public API w `AsiaAuto_Redirects`: `isMakeRedirected(slug)` + `isSerieRedirected(make_slug, serie_slug)` — re-use w diag-checks.
- `class-check-make-without-wiki.php`: skip gdy `isMakeRedirected($t->slug)`.
- `class-check-serie-without-wiki.php`: skip gdy `isSerieRedirected($make_slug, $t->slug)` (tylko dla termów z `parent != 0` — orphans z `parent=0` to osobny problem dla `serie-broken-parent` check).

**Verify (live scan po fix):**
- Marki bez wiki: **8 → 0** (wszystkie 8 były redirected) ✓
- Modele bez wiki: 46 (top 10 to głównie ORPHAN parent=0 — broken historic import; oraz `icar/03t` po V63 merge — legit, wymaga wiki gen)

**Pending:** orphan terms (parent=0) to oddzielny problem — `class-check-serie-broken-parent` powinien je naprawiać przed generacją wiki. n8n webhook 5s timeout oznacza że workflow `primaauto-make-desc` jest offline lub muli się — sprawdź w n8n.

---

## 0.32.38 — 2026-05-06 (serie-sitemap: wycięcie 23 redirected series V61/V62/V63 + URL-based filter)

**Problem (zdiagnozowany przez GSC URL Inspection 344 hubów modeli):** 13/344 = NEUTRAL „Strona zawiera przekierowanie". `serie-sitemap.xml` publikował slugi modeli których URL robi 301:
- modele marki redirectowanej (V61): `chery-fengyun/fengyun-x3`, `gac-aion-hyper/hyper-ssr`, `dongfeng-yipai/yipai-007`, `jetour-shanhai/jetour-shanhai-l7-plus`, `yangwang/yangwang-u7-ev`, etc.
- serie zdedupowane V62: `byd/leopard-5-denza-b5`, `byd/seal-u-dm-i-song-plus`, `byd/leopard-3-tai-3-fcb`, `byd/sealion-8-dm-i-tang-l`, `byd/atto-3-yuan-plus`, `byd/leopard-8-denza-b8`, `byd/sealion-8-tang-l-ev`, `byd/leopard-7-tai-7-fcb-phev`, `zeekr/zeekr-9x`, `voyah/voyah-taishan`, `dongfeng/e008`, `gac/trumpchi-e8`, etc.
- serie cross-make migrated V63: `chery/icar-03`, `chery/icar-03t`, `chery/icar-v27`

**Fix:** rozszerzenie hooka `rank_math/sitemap/entry` w `AsiaAuto_Redirects` — `excludeRedirectedTermsFromSitemap()`:
- **make:** ten sam check co v0.32.37 (slug w V61_MAKE_REDIRECTS)
- **serie:** **URL-based parsing** zamiast `$term->parent` (wiele serie-termów to orphans z `parent=0`). Wyciągamy `<make_slug>/<serie_slug>` z URL ścieżki `/samochody/<make>/<serie>/`, deterministycznie sprawdzamy V61 (parent_make redirects), V62 (serie dedup w obrębie make), V63 (cross-make migration).
- **Bonus fix:** RankMath przekazuje `$url` jako tablicę `['loc' => ..., 'mod' => ..., 'images' => ...]`, nie string. Refactor obsługuje oba typy (forward/backward compat).

**Verify (po `wp rankmath sitemap generate`):**
- make-sitemap.xml: 47 URL (bez zmian, v0.32.37)
- serie-sitemap1.xml + serie-sitemap2.xml: **344 → 321 URL** (wycięte 23 redirected)
- Sample 6/6 URL'i 301-redirect: usunięte ✓

**Indexing API quota:** dziś submitowanych 12 (huby make z poprzedniej sesji) + 29 (huby serie NEUTRAL waiting) = **41/200**. Zostaje 159 na inne potrzeby do końca dnia.

---

## 0.32.37 — 2026-05-06 (make-sitemap: wycięcie 15 redirected makes V61)

**Problem (zdiagnozowany przez GSC URL Inspection 54 hubów marek):** 6/54 hubów = NEUTRAL „Strona zawiera przekierowanie". `make-sitemap.xml` publikował slugi marek które robią 301 (V61_MAKE_REDIRECTS w `class-asiaauto-redirects.php`) — Google odrzucał je z indeksu jako redirect.

**Fix:** `AsiaAuto_Redirects::excludeRedirectedMakeFromSitemap()` — hook `rank_math/sitemap/entry` priority 10. Per-entry filter zwraca `false` dla term'ów `make` których slug jest kluczem w `V61_MAKE_REDIRECTS` (15 slugów). Bez ruszania DB — listings podpięte pod te termy nadal indeksowane przez `listings-sitemap*` (osobne sitemaps dla CPT `listings`).

**Verify (po `wp rankmath sitemap generate`):** make-sitemap.xml: 54 → **47 URL**. Zniknęły: `galaxy`, `great-wall`, `dongfeng-yipai`, `gac-aion-hyper`, `jetour-shanhai`, `yangwang`, `fangchengbao`, `chery-fengyun`, `gac-trumpchi`, `maextro`, `changan-qiyuan`, `beijing-off-road`, `212`, `dongfeng-fengxing`, `lotus-cars`. `polestar/` + `xingchi/` (NEUTRAL „zeskanowana, czeka na index") zostały — submit do Indexing API dla acceleracji.

**GSC URL Inspection wynik finalny dla 54 hubów make (przed fix):** 46 PASS / 2 NEUTRAL waiting / 6 NEUTRAL redirect = po fix: 47 sitemap URL, z czego 46 PASS + 2 waiting. Realnie **48 marek (54 - 6 redirected) z których 46 zaindeksowane = 96%**.

**Indexing API submitowane w sesji:** 12/200 quota:
- 10 hubów (pretendenci top10 z GSC + świeże po batch n8n + huby po merge'ach)
- 2 hub-y NEUTRAL (`polestar/`, `xingchi/`)

---

## 0.32.36 — 2026-05-06 (fix dup meta description single listing — RankMath suppression)

**Problem (zdiagnozowany live curl):** single listings `/oferta/*` emitowały **2× `<meta name="description">`** + 2× `og:type/og:title/og:description/og:image`:
- RankMath Pro: auto-extract z `post_content` → łapie chińskie znaki z importu Dongchedi (np. „Nie można wystawić faktury VAT【Stan zewnętrzny】Drobne rysy【Stan lakieru】..."). **Śmieciowa desc**.
- `class-asiaauto-single::renderMeta()`: bogata desc z marką/rokiem/paliwem/przebiegiem/ceną/USP („Import z Chin – Prima Auto"). **Lepsza dla CTR**.

**Decyzja:** zostawić `class-asiaauto-single` (bogata custom emisja), zsupressować RankMath dla single listings. Memory v0.32.0 „single nietknięte" było prawidłowe — broniło przed RM auto-extract.

**Fix:** `class-asiaauto-single::initRankMathSuppression()` — 11 filtrów zwracających `''` lub `[]` dla `is_singular('listings')`:
- `rank_math/frontend/title`, `rank_math/frontend/description`, `rank_math/frontend/robots`
- `rank_math/opengraph/facebook/og_title`, `og_description`, `og_type`, `og_image`
- `rank_math/opengraph/twitter/twitter_title`, `twitter_description`, `twitter_image`, `card_type`

**Bonus:** wyłączenie `rank_math/frontend/title` aktywuje `class-asiaauto-single::filterTitle` (wcześniej dead code) — 10 wariantowych templatów title rotuje per inner_id (`Używane {base} z Chin`, `{base} import z Chin`, `Sprowadź {base} z Chin`, etc.).

**Smoke 2/2 listingi (Zeekr 8X / BYD Sealion 8):**
- 1× `meta description` (bogata: marka, rok, paliwo, przebieg, cena, USP) ✓
- 1× `og:type=product` (nie article) ✓
- 1× `og:title/description/image` (custom template) ✓
- 1× `<title>` (template z filterTitle) ✓
- 1× `meta robots` (max-snippet/max-image-preview, brak `noindex`) ✓
- 1× `link canonical`, 1× `twitter:card` ✓

**Hand-off konwencji RankMath ↔ AsiaAuto (po v0.32.36):**
- **RankMath rządzi:** home, page (`/samochody/`, `/marki/`, `/informacje/*`), taxonomy (make, serie, body, fuel, ca-year)
- **AsiaAuto rządzi:** single listings (`/oferta/*`) — title + meta + og + twitter + Schema Car + dataLayer (RankMath supressed). Plus huby make/serie — Schema ItemList/FAQPage/BreadcrumbList (RankMath nie generuje, custom emituje równolegle do RM CollectionPage).

Backup: `class-asiaauto-single.php.bak-2026-05-06-rm-dedup`.

---

## 0.32.35 — 2026-05-06 (audyt SEO Plan A: GSC sitemap cleanup + dup desc fix + /marki/ meta)

**Audyt SEO 2026-05-06 — porównanie do baseline 2026-04-23:**

| Metryka | 2026-04-23 | 2026-05-06 |
|---|---|---|
| Indeksacja 10 URL | 1/10 PASS | **10/10 PASS** |
| GSC 30d impresje | 4 | **1282** |
| GSC 30d clicks | 0 | **98** (CTR 7.64%, avg pos 7.6) |
| Top query | brak | **"prima auto rzeszów" pos 2.4 CTR 26.1%** |

**Konflikt RankMath ↔ class-asiaauto-seo (zdiagnozowany, fixed):**
- Pierwsze meta desc na `/samochody/`: RankMath ("Tylko aktualne i sprawdzone oferty…")
- Drugie meta desc: `class-asiaauto-inventory.php::renderInventoryMeta()` linia 1732 ("Elektryki, hybrydy…") — **duplikat**
- `class-asiaauto-seo.php` ma already early return gdy `defined('RANK_MATH_VERSION')` (v0.32.0), `class-asiaauto-inventory.php` nie miał — **fix w tej wersji**

**Fix A1 — DELETE stary sitemap z GSC:** `wp-sitemap.xml` (3609 URL submitted, downloaded 2026-05-01) — duplikat z RankMath `sitemap_index.xml` (3691 URL). API DELETE → HTTP 204.

**Fix A2 — Submit 24 RankMath sitemaps do GSC:** GSC wcześniej widział tylko 4 z 18 listings sitemaps (sitemap1-4). PUT przez `webmasters/v3/sites/{site}/sitemaps/{url}` dla pełnego setu: `sitemap_index.xml` + `page-sitemap.xml` + `make-sitemap.xml` + `serie-sitemap1-2.xml` + `local-sitemap.xml` + `listings-sitemap1-18.xml`. Wszystkie 24 → 0 errors. Łącznie 3691 URL submitted, w tym 18 sitemaps × 200 listings = ~3600 (sitemap18 ma 43, sitemap17 ma 82 — końcówki).

**Fix A3 — `class-asiaauto-inventory.php::renderInventoryMeta()` early return gdy RankMath aktywny:** dodane w linii 1700 `if (defined('RANK_MATH_VERSION')) return;`. URL-e parametryczne (`?marka=X&model=Y`) i tak są noindex od v0.32.5/8 (`isInventoryPage()` + filter params whitelist). RankMath obsługuje główny `/samochody/` z `rank_math_title`/`rank_math_description` ustawionymi w admin. Backup: `class-asiaauto-inventory.php.bak-2026-05-06-rm-handoff`. Po fix smoke `/samochody/` ma desc:1 (RankMath: "Tylko aktualne i sprawdzone oferty aut z rynku chińskiego. Bezpośredni importer.").

**Fix A4 — `/marki/` (page_id 263572) brakujące rank_math_*:** `rank_math_title` = "Marki samochodów z Chin — Prima-Auto", `rank_math_description` = "Pełen katalog 50+ marek samochodów z Chin: BYD, Xiaomi, Chery, Geely, Voyah, AITO, XPeng, Zeekr i inne. Import do Polski, ceny końcowe, gwarancja." (155 chars), `rank_math_focus_keyword` = "marki samochodów z Chin". `wp post meta update 263572` × 3.

**Smoke test 5/5:** `/samochody/` desc:1 ✓ (RankMath), `/marki/` desc:1 ✓ (nowy desc + custom title), `/samochody/?marka=byd` desc:1 ✓ (RankMath), Listing Denza desc:2 (out-of-scope, do osobnej decyzji), Hub BYD/SU7 bez zmian.

**KRYTYCZNE pozostałe (osobna sesja):**
- **PSI mobile home REGRES**: perf 75 (04-23) → **39** (05-06), TBT 160ms → **3890ms** (24×!), LCP 4.9s → 6.1s. CrUX field data **wszystkie 4 metryki = POOR (F)** — Google klasyfikuje jako poor CWV → ranking penalty. Source: prawdopodobnie RankMath analytics + Complianz + asiaauto-tracking + GTM stacked w main thread. Wymaga audytu JS payloadu i defer/async refactor. Theme `primaauto2026` 1.0.4 (Elementor wycofany 2026-04-24, ale TBT regres jest inny problem).
- **Listing desc:2** — `class-asiaauto-single.php` (custom z marką/modelem/ceną/przebiegiem) konkuruje z RankMath auto-extract z post content. AsiaAuto desc lepsza SEO-wise (zawiera focus-keywordy), RankMath desc generic. Decyzja: zostawić AsiaAuto + wyłączyć RM dla CPT `listings` (filter `rank_math/frontend/description` return false dla `is_singular('listings')`) lub w admin RM disable post type.

**Top pages w GSC (30d, what's working):** `/` 469imp/56clk/CTR11.9%, `/samochody/` 147/3, `/samochody/aito/` 89/3, `/samochody/byd/` 69/3, `/marki/` 63/2, `/samochody/byd/leopard-5/` 101/2. Niche-modele rankują: "tank 300 cena w polsce" pos 6.4, "geely preface cena" pos 8.7, "aito m9", "li auto l9", "zeekr 9x 2025", "changan uni-v" pos 27 (do dopchnięcia).

## 0.32.34 — 2026-05-06 (W1+W2: prevent ghost-offer publish-then-trash churn)

**Problem:** importer publikował listingi mimo że auto-api.com zwracał już-wygasłe URL-e Dongchedi (`x-expires` < `synced_at`). Listingi w `publish` bez thumbnail → indeksowane przez Google → potem masowy cleanup przez `diag missing-images` (2026-05-03: 60 listings; rano 2026-05-06: znów 93). Strata budżetu indeksacji + churn URL-i.

**Rozwiązanie 2-warstwowe:**

**W1 — preflight w `AsiaAuto_Importer::importListing()`:**
Przed `wp_insert_post` parsuje obrazy i sprawdza `allUrlsExpired()` (nowy static helper). Jeśli WSZYSTKIE URL-e z parametrem `x-expires` są po terminie → `return null`, log warning. Cron pomija ofertę i ponowi przy następnym sync (świeżych danych z API). **Manual import (`force=true`) pomija preflight** — klient świadomie wskazał ofertę.

**W2 — guard w `AsiaAuto_Media::downloadAndStore()`:**
Gdy wszystkie pobrania zwrócą 403 (`empty($attachment_ids)`) — zamiast zostawić post w `publish` bez thumbnail, przenosi go do `draft` i zapisuje `_asiaauto_image_failure_at`. Listing nie pojawia się publicznie. `updateListing` przy kolejnym sync może go odzyskać (`downloadMissingImages` nadal próbuje).

**Manual import UI:**
`ajaxImport()` po imporcie sprawdza gallery i jeśli pusta — zwraca `data.warning` z instrukcją: „URL-e wygasły, otwórz ofertę na Dongchedi (auto-odświeży cache po stronie auto-api.com), ponów import za ~30s". JS renderuje jako `notice notice-warning`.

**Helper `AsiaAuto_Importer::allUrlsExpired(array $urls): bool`:**
- `[]` → `false` (no-op, nic do importu)
- URL-e che168 / bez `x-expires` → `false` (conservative: nie blokujemy)
- Mieszane (chociaż 1 świeży) → `false`
- Wszystkie z `x-expires` po terminie → `true`

**Pliki:** `asiaauto-sync.php` (version), `includes/class-asiaauto-importer.php` (W1 + helper), `includes/class-asiaauto-media.php` (W2), `includes/class-asiaauto-admin-manual-import.php` (UI warning + JS).

**Backupy:** `*.bak-2026-05-03-w1`, `*.bak-2026-05-03-w2`, `*.bak-2026-05-03-warning`.

**Smoke test:** `php -l` × 4 czysty. `wp eval allUrlsExpired()` dla 5 case'ów: poprawne wyniki (mixed=false, all_old=true, no_param=false, empty=false, real_dongchedi_old=true).

**Co dalej:** monitor logu po następnym cronie (`grep "API cache stale" logs/asiaauto-sync.log`) — ile ofert pominiętych. Jeśli liczba jest wysoka i nie spada przez 2-3 cykle, zbadać dlaczego auto-api.com serwuje stary cache (może tam jest TTL do podkręcenia).

## 0.32.33 — 2026-05-04 (HOTFIX: martwe linki asiaauto.pl w mailingu i umowie PDF)

W trybie autonomous przy v0.32.32 zostawiłem 7 hardcoded URL-i `https://asiaauto.pl/*` w mailach do klientów i logo URL w umowie PDF jako „TODO osobny task" — uznając że „działa bo plik istnieje na asiaauto.pl". To było błędne. Klient zwrócił uwagę: domena `asiaauto.pl` zwraca **HTTP 500 na wszystkich routach poza wąskim zakresem statycznych plików w `/2026/04/`**. Klienci dostawali maile z linkami `https://asiaauto.pl/proces/`, `/homologacja/`, `/faq/`, `/samochody/` — wszystkie 500. Umowa PDF używała LOGO_URL z asiaauto.pl który czasem dawał 200, czasem 500 (warunkowo).

**Szybka inwentaryzacja stanu asiaauto.pl (curl -I):**
- `/`, `/proces/`, `/homologacja/`, `/faq/`, `/samochody/` — **HTTP 500**
- `/wp-content/uploads/2026/04/primaauto-logo-round.png` — **HTTP 200** (jeden statyczny plik z 04/ działa)
- `/wp-content/uploads/2026/03/primaauto-logo-round.png` — **HTTP 500**

Przyczyna 500-ek na asiaauto.pl wymaga osobnej diagnozy (pewnie wp-config WP_HOME na primaauto + brak fallback dla starej domeny w .htaccess albo plugin asiaauto-sync który DB wspólną i coś failuje). **Cutover 2026-04-21 zakładał 301 na całej domenie — nie działa zgodnie z założeniem.** Do osobnego task (TODO).

**`class-asiaauto-order-content.php` (6 linków w 4 statusach maili):**
- 116-118: lista przydatnych informacji w mailu „Zamówienie przyjęte" — 3 linki:
  - `https://asiaauto.pl/proces/` → `https://primaauto.com.pl/informacje/proces-zamawiania/`
  - `https://asiaauto.pl/homologacja/` → `https://primaauto.com.pl/informacje/homologacja-i-rejestracja/`
  - `https://asiaauto.pl/faq/` (nigdy nie istniała na primaauto) → `https://primaauto.com.pl/informacje/` (parent landing dla wszystkich podstron informacyjnych)
- 159: link do procesu w mailu „Wycena": `asiaauto.pl/proces/` → `primaauto.com.pl/informacje/proces-zamawiania/`
- 350: oferty alternatywne w mailu „Niedostępny": `asiaauto.pl/samochody/` → `primaauto.com.pl/samochody/`
- 371: oferty w mailu „Anulowane": `asiaauto.pl/samochody/` → `primaauto.com.pl/samochody/`

**`class-asiaauto-contract.php` (LOGO w PDF umowy):**
- Linia 53: `LOGO_URL = 'https://asiaauto.pl/wp-content/uploads/2026/04/primaauto-logo-round.png'` → `'https://primaauto.com.pl/wp-content/uploads/2026/03/primaauto-logo-round.png'`
- Linia 290: `resolveLogoPath()` próbuje najpierw lokalny `$upload_dir/2026/04/primaauto-logo-round.png` — który **nie istnieje** (plik leży w `/2026/03/`). Path zmieniony na `/2026/03/primaauto-logo-round.png` (200 lokalnie + 200 z URL fallback).

**Smoke test (PASS):**
- `/informacje/proces-zamawiania/` 200 ✓
- `/informacje/homologacja-i-rejestracja/` 200 ✓
- `/informacje/` 200 ✓ (landing dla wszystkich info)
- `/samochody/` 200 ✓
- `/wp-content/uploads/2026/03/primaauto-logo-round.png` 200 ✓

**Sync legacy domain:** 3 pliki skopiowane do `~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/` (rutynowo, choć sama domena daje 500).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.32 → 0.32.33
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-order-content.php` — 6 linków asiaauto.pl → primaauto.com.pl
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-contract.php` — LOGO_URL + lokalny path do 2026/03/

**Diagnoza + fix asiaauto.pl 500 (przy okazji v0.32.33, infra-only):**

Przyczyna: `wp eval` na asiaauto.pl rzucał `Fatal error: Failed opening required '...wp-content/plugins/asiaauto-sync/includes/class-asiaauto-mapping.php'`. Plik powstał 2026-04-23 przy v6.1 brand-mapping (`AsiaAuto_Mapping::getEuForCn()`), ale **sync legacy do asiaauto.pl był niekompletny** — kopiowaliśmy tylko aktualnie zmieniane pliki w danej sesji, nigdy nie robiliśmy `rsync` całego plugin dir. `diff -rq` pokazał **kilkadziesiąt** plików brakujących na asiaauto.pl (admin-diag.css/js, asiaauto-tracking.js, kilka diag/, kilka data/translations + część kluczowa: `class-asiaauto-mapping.php`, `class-asiaauto-admin-diag.php`).

Fix: ZAMIAST sync wszystkiego z primaauto, zgodnie z założeniem cutover 2026-04-21 (memory: „asiaauto = uśpiona kopia z 301") → **bezwarunkowy 301 w `.htaccess` przed jakimkolwiek przetwarzaniem PHP**:

```apache
# BEGIN AsiaAuto 301 → primaauto.com.pl
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^(.*)$ https://primaauto.com.pl/$1 [R=301,L]
</IfModule>
# END AsiaAuto 301
```

Backup: `~/domains/asiaauto.pl/public_html/.htaccess.bak-2026-05-04` (oryginalny ze starym Login Hide block + WP rewrites — bez 301).

**Smoke test po fix .htaccess:**
- `https://asiaauto.pl/` → 301 → `primaauto.com.pl/` 200 ✓
- `https://asiaauto.pl/proces/` → 301 → `primaauto.com.pl/proces/` → 301 → `primaauto.com.pl/informacje/proces-zamawiania/` 200 ✓
- `https://asiaauto.pl/samochody/icar/03/` → 301 → `primaauto.com.pl/samochody/icar/03/` 200 ✓ (potwierdza migrację iCAR działa też z legacy domain)
- `https://asiaauto.pl/wp-content/uploads/2026/03/primaauto-logo-round.png` → 301 → primaauto

**Implikacja:** sync legacy do asiaauto.pl staje się zbędny — domena zwraca 301 dla wszystkiego, nie odpala WP, nie używa pluginu. Można w przyszłości całkowicie zrezygnować z kopiowania plików (sam plik `.htaccess` musi tam zostać). Pliki kopiowane w sesji v0.32.31-33 do asiaauto.pl/wp-content są bezużyteczne, ale nie szkodzą.

---

## 0.32.32 — 2026-05-04 (Galaxy → Geely merge + chinese cleanup + /kontakt/ fix)

Druga część sesji 2026-05-04 (po iCAR merge v0.32.31): scalenie residuala Galaxy do Geely, doczyszczenie chińskich znaków w 21 post_title i naprawa shortcode `[asiaauto_contact]` na stronie /kontakt/ (subject mail + 404 schema image).

**Galaxy → Geely (v6.1 residual finalized):**
- Term `Galaxy` (3394, 16 listingów) — pusty po migracji, **usunięty** (`wp term delete`). Redirect `galaxy → geely` był w V61_MAKE_REDIRECTS od 2026-04-23 — działa po usunięciu termu.
- 17 listingów: `term_relationships.term_taxonomy_id=3394` → `3626` (Geely)
- 1 seria pod parent=Galaxy: `Galaxy M9` (6550) → parent=3626 (reszta serii Galaxy* już była pod Geely)
- `wp7j_postmeta`: `make=galaxy` → `geely` (17 wpisów)
- `wp7j_termmeta`: `_asiaauto_primary_make_slug=galaxy` → `geely` (term 6550)
- `wp7j_posts.post_title`:
  1. REPLACE `'Galaxy 银河'` → `'Geely Galaxy '` (chinese cleanup + Geely prefix; 6 listingów A7 EM)
  2. REPLACE `'Galaxy Galaxy'` → `'Geely Galaxy'` (de-duplicate; 1 listing 282264 z poprzednim partial fix)
  3. CONCAT `'Geely '` przed `'Galaxy %'` gdzie nie zaczyna się od `'Geely '` (10 pozostałych)
- `wp term recount`: Geely 341 → 357 (+16 publish, 17 minus 1 draft)

**Brand-mapping bez zmian:** wszystkie 12 wpisów `Galaxy|*` już mapowały na `mark_eu='Geely'` od v6.1 (importer importował nowe listingi pod Geely; tylko stare pod taxonomy Galaxy zostały do dziś).

**Chinese cleanup (translations-complectations.php — 15 nowych mapowań):**

Dodano sekcję `// === 2026-05-04 — chinese-fragments cleanup po Galaxy/iCAR merge (15 nowych) ===`:
- 巅峰性能 → Peak Performance (iCAR Super V23 V23S, listing 299535)
- 大家庭欢乐 → Family Joy (Jetour X90 PLUS)
- 星辉 → Starlight (MAEXTRO S800)
- 锦绣 → Splendid (Geely Atlas Pro)
- 启航 → Voyager (Geely Galaxy E5)
- 凌云 → Soaring (Exeed TXL)
- 智慧 → Smart (Changan CS75 Plus)
- 威赫 → Mighty (Xingchi Bochi Venus)
- 出行 → Mobility (Hongqi E-QM5)
- 公务 → Official (Geely Galaxy E5)
- 高功 → High Power (Geely Monjaro)
- 霄汉 → Skyward (Geely Monjaro)
- 乘势 → Momentum (Chery Tiggo 8 PLUS)
- 电 → Electric (Jetour Shanhai L7 PLUS)
- 星 → Star (Exeed TXL — single char na końcu mapy, longest-first PHP str_replace iteruje by-array-order więc 星舰/星耀/星辉/星空龙耀/星月女神 zamienione przed)

**APPLY `diag/fix-chinese-v23.php`:** 15 listings zaktualizowanych. Po: `SELECT COUNT(*) WHERE post_title REGEXP '[一-龥]'` = **0** (ZERO chińskich w post_title publish). Galaxy listings z chińskim 银河 obsłużone wcześniej w SQL transaction Galaxy migration (REPLACE 'Galaxy 银河' → 'Geely Galaxy ').

**Shortcode `[asiaauto_contact]` na /kontakt/ (`class-asiaauto-contact.php`):**
- Linia 127: `'image' => home_url('/wp-content/themes/asiaauto/assets/asia-auto-logo.png')` → `home_url('/wp-content/uploads/2026/03/primaauto-logo-round.png')`. Plik `asia-auto-logo.png` w themes/asiaauto/assets/ **nie istniał** (404) — schema.org/AutoDealer image był broken. Nowy URL → 200.
- Linia 306: subject mailto `'Zapytanie ze strony asiaauto.pl'` → `'Zapytanie ze strony primaauto.com.pl'`. To było user-facing (klient widział temat w mailu od użytkownika). User nie mógł poprawić bo treść strony to shortcode z PHP.

**Smoke test (PASS):**
- `/samochody/galaxy/` → 301 → `/samochody/geely/` 200 ✓
- `/samochody/galaxy/galaxy-m9/` → 301 → `/samochody/geely/galaxy-m9/` 200 ✓
- `/samochody/geely/a7-em/` 200 ✓ (16+ listingów A7 EM)
- `/kontakt/` 200 ✓ — schema image primaauto-logo-round.png, mailto subject „Zapytanie ze strony primaauto.com.pl"
- 0 listings publish z chińskimi znakami w post_title

**Backup DB:** `~/backups/primaauto/2026-05-04-galaxy-merge/terms-and-posts.sql` (8.4 MB, 4 tabele).

**Sync legacy domain:** 3 pliki skopiowane do `~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/`.

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.31 → 0.32.32
- `wp-content/plugins/asiaauto-sync/data/translations-complectations.php` — 15 nowych mapowań
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-contact.php` — schema image URL fix + mailto subject

**Pozostałe odwołania `asiaauto.pl` w kodzie (NIE naprawione w tej sesji — poza scope dziś):**
- `class-asiaauto-contract.php:53` — `LOGO_URL = 'https://asiaauto.pl/wp-content/uploads/2026/04/primaauto-logo-round.png'` (działa bo plik istnieje na asiaauto.pl, ale powinno wskazywać na primaauto.com.pl)
- `class-asiaauto-order-content.php:116-118, 159, 350, 371` — emaile statusów zamówień zawierają linki `https://asiaauto.pl/proces/`, `/homologacja/`, `/faq/`, `/samochody/` (user-facing — TODO osobny task)

---

## 0.32.31 — 2026-05-04 (iCAR merge: 03/03T/V27 z Chery do iCAR)

Klient zgłosił 2026-05-04: iCAR ma być wyłącznie marką, modele iCAR widniejące pod Chery (iCAR 03, iCAR 03T, iCAR V27) trzeba przerzucić pod główną markę iCAR. Stan przed migracją był niespójny: brand-mapping v6.1 mapował `iCAR Super V23` na markę iCAR, ale `iCAR 03` i `iCAR V27` na Chery; redirect `icar → chery` w V61_MAKE_REDIRECTS sprzeczny ze stanem (term marki iCAR istniał z 9 listingami). Plus orphan `iCAR 03T` (term 5519, parent=0) bez wpisu w mapping.

**Migracja DB live (2026-05-04 ~17:05):**
- `wp7j_terms`: rename serie 5518 (iCAR 03 → 03 / `03`), 5519 (iCAR 03T → 03T / `03t`), 6508 (iCAR V27 → V27 / `v27`). Naming bez prefiksu — wzorzec spójny z istniejącą serią V23 (term 5517, sam numer/oznaczenie, prefix marki tylko w post_title)
- `wp7j_term_taxonomy`: serie 5517/5518/5519/6508 → `parent=5516` (iCAR). Przy okazji fix orphana 5517 V23 (był parent=0) i 5519 (był parent=0)
- `wp7j_term_relationships`: 12 wpisów `term_taxonomy_id=3578 (Chery)` → `5516 (iCAR)` dla listingów: 245892, 249700, 249717, 250800, 259268, 265157, 267536, 271571, 273041, 287720, 287730, 291872 (287534 już miał make=iCAR)
- `wp7j_postmeta`: 7× klucz `make=chery` → `icar` (część listingów miała już `icar` w postmeta — niespójność postmeta vs taxonomy uleczona)
- `wp7j_termmeta`: `_asiaauto_primary_make_slug=chery` → `icar` dla 5518 i 6508 (5519 i 5517 już miały `icar`)
- `wp7j_posts`: REPLACE `'Chery iCAR'` → `'iCAR'` w post_title, 11 listingów (287534 już bez prefiksu)
- `wp term recount make serie` — count: Chery 132 → 124 (-8 publish), iCAR 9 → 17 (+8 publish), suma 141 = 141 ✓

**Brand-mapping uzupełniony** (`data/brand-mapping-v6.1.php`):
- `'iCAR|iCAR 03'` → mark_eu=`iCAR` (zmiana z Chery), serie_eu=`03`, title_eu=`iCAR 03`, slug=`03`
- `'iCAR|iCAR 03T'` → **nowy wpis** (rozwiązuje orphan), mark_eu=`iCAR`, serie_eu=`03T`, slug=`03t`
- `'iCAR|iCAR V27'` → mark_eu=`iCAR` (zmiana z Chery), serie_eu=`V27`, slug=`v27`

Bez tego importer przy reimporcie cofnąłby zmiany (wrzucał iCAR 03/V27 z powrotem pod Chery i nie tworzyłby relacji dla 03T).

**Redirects (`class-asiaauto-redirects.php`):**
- **USUNIĘTO** `'icar' => 'chery'` z `V61_MAKE_REDIRECTS` (niespójność: marka iCAR istnieje, redirect ją zabijał)
- **DODANO** nową stałą `V63_MAKE_SERIE_REDIRECTS` (mapa `[old_make][old_serie] => [new_make, new_serie]`) + metodę `redirectV63MakeSerieMoves()` na `template_redirect` priorytet 0. Mapa: `chery/icar-03 → icar/03`, `chery/icar-03t → icar/03t`, `chery/icar-v27 → icar/v27`. Mechanizm rozszerzalny — następne migracje modeli między markami w jednym miejscu.

**Smoke test (PASS):**
- `/samochody/icar/` 200 (17 listingów)
- `/samochody/icar/03/`, `/icar/03t/`, `/icar/v27/`, `/icar/v23/` 200
- `/samochody/chery/icar-03/` → 301 → `/samochody/icar/03/` 200 ✓
- `/samochody/chery/icar-03t/` → 301 → `/samochody/icar/03t/` 200 ✓
- `/samochody/chery/icar-v27/` → 301 → `/samochody/icar/v27/` 200 ✓
- `/samochody/chery/` 200 (124 listingów, bez iCAR)

**Backup DB:** `~/backups/primaauto/2026-05-04-icar-merge/terms-full.sql` (8.4 MB, 4 tabele: terms/term_taxonomy/term_relationships/termmeta).

**Sync legacy domain:** 3 pliki skopiowane też do `~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/` (legacy 301-redirect na primaauto, ale wp-content musi być spójne — wspólna DB).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.30 → 0.32.31
- `wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php` — 3 wpisy iCAR (mark_eu zmiana + nowy 03T)
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-redirects.php` — usunięty `icar→chery`, dodany `V63_MAKE_SERIE_REDIRECTS` + `redirectV63MakeSerieMoves()`

---

## 0.32.30 — 2026-05-01 (Fix mocy KM dla PHEV — single + karty inventory)

Klient zgłosił 2026-04-30: PHEV-y pokazują absurdalnie niskie liczby KM (BYD Han DM-i 156 zamiast 272, Denza Z9 DM-i 207 zamiast 870, Sealion 8 DM-p 4WD 156 zamiast 544). Diagnoza pełna w `docs/QUEUE.md` ZADANIE 15: dwa renderery (`class-asiaauto-inventory.php::parseSystemPower`, `class-asiaauto-single.php::power`) opierały się na `energy_elect_max_power` (niespójne dla PHEV) z fallbackiem do meta `_asiaauto_horse_power` (= moc samego silnika spalinowego).

**Wdrożenie:**
- Nowy `public static AsiaAuto_Inventory::resolvePower(int $post_id, array $ep): array` — fuel-aware: dla PHEV/EREV/HEV/EV używa `front_electric_max_horsepower` + `total_electric_power` (wiarygodne 99,8% PHEV w bazie). Edge case PHEV: gdy ICE dominuje (`engine_kw * 1.5 > total_kw`, np. Volvo S90 T8) → pokaż combined `engine_kw + total_kw`. Dla benzyny/diesla — `engine_max_horsepower` + `engine_max_power`. Meta `_asiaauto_horse_power` jako ostateczny fallback **tylko dla ICE** (dla PHEV nadal zawiera ICE-only HP, ale nie jest dla nich brany pod uwagę).
- `class-asiaauto-inventory.php` — karta listings woła `resolvePower($postId, $ep)` zamiast `parseSystemPower($ep)`. Stary helper zostawiony jako `@deprecated` (nieużywany).
- `class-asiaauto-single.php::power($ep, $post_id = null)` — cienki wrapper na `AsiaAuto_Inventory::resolvePower()`. Etykieta nad mocą zmieniona z „Moc łączna" na „Moc" (dynamicznie z `$pw['label']`). Caller w `wp_head` (linia 687) dostaje `$pid` jawnie — `get_the_ID()` bywa nullem przed The Loop.

**Weryfikacja klienta (2026-05-01):** wszystkie 8 testów z planu PASS — Han DM-i, Z9 DM-i Ultra, N9 DM-i Premium, Leopard 7 PHEV, AITO M7 EREV, Volvo S90 T8 (combined), Z9 GT EV (bez regresji), benzynowiec (bez regresji).

**Czego NIE ruszono:** importer (`_asiaauto_horse_power` celowo zapisuje ICE HP — zostaje), `class-asiaauto-shortcodes.php::resolvePower()` (wzorcowa logika — nieaktywna w motywie primaauto2026, pozostaje na osobny refactor konsolidujący).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.29 → 0.32.30
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-inventory.php` — `resolvePower()` static helper + podmiana w karcie listings
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-single.php` — `power()` jako wrapper, etykieta „Moc", `$pid` jawny w wp_head schema

---

## 0.32.29 — 2026-04-30 (Mobile single redesign — sticky title bar pod headerem strony)

Single listing przebudowany na mobile: title + brutto/netto przyklejone u góry pod headerem strony, gallery tuż pod, "Informacje" (linki do podstron) zjechały na sam dół pod inne moduły, breadcrumb i top „Wróć do wyników" ukryte na mobile (BreadcrumbList JSON-LD nadal w `<head>`).

**Architektura sticky head — dwa warianty (desktop sidebar + mobile poza layoutem):**
- Wyciągnięty `stickyHead($d, $variant)` z `sidebar()`. Renderowany 2× z modyfikatorami `--desktop` (w `<aside>`) i `--mobile` (poza `<div class="aa-single__layout">`, jako sibling).
- Variant `--mobile` ma `position: sticky; top: var(--header-h, 70px); z-index: 90` — przykleja się POD `.pa-header` (z-index 100). Backup-y atemptów: position: fixed (porzucony — wymagał dynamicznego JS measure margin-top), display: contents na sidebar (porzucony — niestabilne w grid + sticky cascade).
- Negatywny margin-top `-16px` niweluje `--space-2` padding na `.pa-main` na mobile — title bar bez gapu po site headerze.
- Mobile sticky-back arrow (←) jako lewa kolumna grid sticky head; na desktop hidden (`display: none`).
- `aa-info--desktop` (w sidebar) vs `aa-info--mobile` (na końcu main) — info linki widoczne tylko w odpowiednim viewporcie.
- `aa-sep--desktop`, `aa-single__updated--desktop` — dodatkowe dekoracje sidebar ukryte na mobile.

**iOS Safari fix mobile CTA (3 buttons):** `position: fixed; bottom: 0` ląduje pod toolbarem Safari. JS `visualViewport` API liczy gap między layout a visual viewport i ustawia `bottom: <gap>px` żeby CTA podążał za widocznym dołem ekranu. Resize/scroll listenery.

**iOS Safari fix dolnego CTA (`asiaauto-single.js`):**
```js
var bottomGap = window.innerHeight - (vv.height + vv.offsetTop);
mobileCta.style.bottom = bottomGap > 0 ? bottomGap + 'px' : '';
```

**Asset versioning:** `wp_enqueue_style/script` dla `aa-single` przerzucone z hardcoded `'0.25.0'` na `filemtime()` z fallback do `ASIAAUTO_VERSION` — automatyczne cache-busting przy edytach CSS/JS.

**`wp_kses` fix w `taxonomy-make.php` + `taxonomy-serie.php` (theme-side):**
Sanitizer `wp_kses()`/`wp_kses_post()` na zmergowanej zawartości `wiki + bar` strip'ował `<img fetchpriority="high" decoding="async" />` i `<svg>` z attrybutami nieuwzględnionymi w domyślnym allowlist. Skutek: niedomknięte `</div>` w kartach + zagnieżdżone `<a>` w `<a>` → karuzela "Najnowsze oferty" w hubach renderowała się jako wysokie kolumny pionowe (browser parse'ował broken HTML). Fix: split `$wiki` przez placeholder `{{LISTINGS_BAR}}`, sanityzowanie tylko user-side wiki content, `$bar` (nasz zaufany hub-listings HTML) wstawiany RAW.

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.28 → 0.32.29
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-single.php` — extracted `stickyHead()` + `infoBox()`, dwa renderingi w `render()`, filemtime cache busting
- `wp-content/plugins/asiaauto-sync/assets/css/asiaauto-single.css` — sekcja `@media (max-width: 768px)` z sticky head variants, hide breadcrumb/back, mobile info-box
- `wp-content/plugins/asiaauto-sync/assets/js/asiaauto-single.js` — visualViewport listener dla iOS Safari mobile CTA fix
- `themes/primaauto2026/taxonomy-make.php` + `taxonomy-serie.php` — split `$wiki/$bar` przy `wp_kses` (rozwiązanie karuzeli rozjechanej)
- `themes/primaauto2026/assets/css/footer.css` — mobile centered brand col + social icons
- `themes/primaauto2026/assets/css/hub.css` — `.aa-container { padding: 0 12px }` zamiast `0`, listing slider `flex-direction: row !important; flex-wrap: nowrap !important; flex: 0 0 70vw` na mobile, `aa-home__section-header { flex-wrap: wrap }`
- `themes/primaauto2026/assets/css/header.css` — `.pa-header { z-index: 9000 }` (było 100) — fix problemu zasłaniania mobile menu hamburgera przez sticky inventory toolbar
- `themes/primaauto2026/functions.php` — bump `PRIMAAUTO_THEME_VERSION` 1.0.4 → 1.0.6

**Smoke test:** `/oferta/<slug>/`, `/marki/`, `/samochody/<make>/`, `/samochody/<make>/<serie>/`, `/samochody/` → 200; rendered HTML zawiera oba sticky head variants, info-mobile + info-desktop, karty z `<img fetchpriority>` i zamkniętymi tagami.

**Rollback:** wszystkie pliki z .bak-2026-04-29-mobilesingle (plus header.css, hub.css, taxonomy-*.php, footer.css w temacie).

## 0.32.28 — 2026-04-29 (Cleanup serii pod GAC — usunięcie prefiksu „Trumpchi" z 4/7 modeli)

Po scaleniu marki (v0.32.27) seryjne kosmetyczne czyszczenie nazw 7 serii pod GAC z prefixem „Trumpchi" (E8, E8 PHEV, ES9 PHEV, E9 PHEV, M6, S7, M8). 4 udało się przepisać do czystych slugów; 3 zostały — kolizje slugów w taksonomii `serie` (globalnie unique w WP od 4.2):

**Zmienione (4 termy):**
- `Trumpchi E8` (3392) → `E8`, slug `e8`
- `Trumpchi E8 PHEV` (3389) → `E8 PHEV`, slug `e8-phev`
- `Trumpchi E9 PHEV` (3383) → `E9 PHEV`, slug `e9-phev`
- `Trumpchi ES9 PHEV` (3384) → `ES9 PHEV`, slug `es9-phev`

**Pozostawione (3 termy):**
- `Trumpchi M6` (3377) — kolizja z term 6557 (M6, parent=0, AITO orphan, 1 listing 283901)
- `Trumpchi S7` (3373) — kolizja z term 5674 (Luxeed S7, parent=6527, 10 listingów)
- `Trumpchi M8` (3381) — kolizja z term 3372 (GAC M8 zwykły, parent=6525, 53 listingi). Faktycznie inny model (Xiangwang M8 = top trim).

**Post_title batch (REPLACE w bezpiecznej kolejności od najdłuższego):**
1. `GAC Trumpchi ES9 PHEV ` → `GAC ES9 PHEV ` (3 wpisy)
2. `GAC Trumpchi E9 PHEV ` → `GAC E9 PHEV ` (5)
3. `GAC Trumpchi E8 PHEV ` → `GAC E8 PHEV ` (8)
4. `GAC Trumpchi E8 ` → `GAC E8 ` (4)
5. `GAC Trumpchi M8 ` → `GAC M8 ` (2 — historyczny listing pod term 3372 GAC M8 z błędnym title po v6.1; bonus cleanup; nie zmienia „Xiangwang M8")
6. `GAC Trumpchi Empow ` → `GAC Empow ` (1 — bonus cleanup)

Łącznie 23 listingi z post_title zaktualizowane.

**Brand-mapping (`data/brand-mapping-v6.1.php`):** 4 wpisy (`GAC Trumpchi|Trumpchi E8`, `…E8 PHEV`, `…ES9 PHEV`, `…E9 PHEV`) — `serie_eu`, `title_eu`, `slug` zmienione z prefiksowanych na czyste (E8/E8 PHEV/...). Inne wpisy GAC Trumpchi nietknięte (Xiangwang M8/S7 zachowują pełną nazwę CN, M6 z prefixem).

**Redirecty 301 (`class-asiaauto-redirects.php::V62_SERIE_REDIRECTS`):** dodana sekcja `'gac' => [...]` z 4 mapowaniami starych slugów (`trumpchi-e8` itd. → `e8` itd.). Łącznie z istniejącymi `byd`/`zeekr`/`voyah`/`dongfeng` jeden wspólny mechanizm dla orphan-fix duplicate slug redirects.

**Smoke test (curl):**
- `/samochody/gac/e8/`, `/e8-phev/`, `/e9-phev/`, `/es9-phev/` → 200
- `/samochody/gac/trumpchi-e8/` itd. → 301 → odpowiednio czysty slug
- `/samochody/gac/trumpchi-m6/`, `/trumpchi-s7/`, `/trumpchi-m8/` → 200 (zachowane)

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.27 → 0.32.28
- `wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php` — 4 wpisy zaktualizowane
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-redirects.php` — `V62_SERIE_REDIRECTS['gac']` (+6 linii)
- legacy `~/domains/asiaauto.pl/...` — sync 3 plików

**Pending (kosmetyka, niski priorytet):**
- `Trumpchi M6` cleanup wymagałby usunięcia/przeniesienia term 6557 (orphan AITO M6, parent=0, listing 283901 → powinien być pod właściwym AITO term). To by też naprawiło inny orphan z raportu 2026-04-28.
- `Trumpchi S7` cleanup wymagałby zmiany slug Luxeed S7 (term 5674) — nieproporcjonalne ryzyko dla SEO Luxeed.
- `Trumpchi M8` (Xiangwang M8) — nazwa słusznie zachowana, top trim ≠ podstawowa M8.

## 0.32.27 — 2026-04-29 (GAC Trumpchi → GAC merge — domknięcie residuals v6.1)

Domknięcie świadomie zostawionego residuum z v6.1 (2026-04-23): marka `GAC Trumpchi` (term_id 3368, 11 listingów) była utrzymywana obok `GAC` (6525), co generowało dublujące się prefiksy w post_title („GAC Trumpchi Trumpchi M6"). Po raporcie orphan-fix z 2026-04-28 i diagnozie obecnej sesji decyzja: zmergować w jedną markę GAC.

**Migracja DB (live na produkcji + legacy asiaauto.pl plik plugin):**
1. **Reparent serii** — `Trumpchi M6` (3377) i `Trumpchi E9 PHEV` (3383) zmienione `parent` z 3368 na 6525 w `wp7j_term_taxonomy`.
2. **Reparent listingów** — 12 wpisów w `wp7j_term_relationships` z `term_taxonomy_id=3368` przeniesione na 6525. Zero kolizji (żaden listing nie miał już GAC).
3. **Postmeta cleanup** — `meta_key=make` z wartością `gac-trumpchi` → `gac` (67 listingów; legacy meta nieużywane przez plugin/theme, ale spójne z taksonomią).
4. **Termmeta cleanup** — `_asiaauto_primary_make_slug` na seriach 3377 i 3383: `gac-trumpchi` → `gac`.
5. **Post_title batch update** — `REPLACE('GAC Trumpchi Trumpchi', 'GAC Trumpchi')` w `post_title` dla `post_type='listings'`. 13 listingów (12 z mojej listy + 1 historyczny ID 239842 „GAC Trumpchi Trumpchi M8" → „GAC Trumpchi M8").
6. **Recount** — `wp_update_term_count_now()` dla terms 6525, 3377, 3383. GAC: 112 → 123 (publish only; 134 łącznie w relationships).
7. **Usunięcie term 3368** — `wp term delete make 3368` (kasuje też termmeta: rank_math_*, asiaauto_wiki_body, asiaauto_seo_desc, asiaauto_faq_json, _asiaauto_desc_*).
8. **Brand-mapping uzupełniony** — `data/brand-mapping-v6.1.php` dopisane wpisy `'GAC Trumpchi|Trumpchi M6'` i `'GAC Trumpchi|Trumpchi E9 PHEV'` → `mark_eu=GAC`, zachowujące prefix „Trumpchi" w `serie_eu`/`title_eu`/`slug` (spójnie z istniejącymi wpisami E8/Xiangwang M8/S7/E8 PHEV/ES9 PHEV). Bez tego importer przy reimporcie wracałby do tworzenia term 3368 ponownie.

**Redirect 301 już istniał** (`V61_MAKE_REDIRECTS` w `class-asiaauto-redirects.php:37`): `gac-trumpchi → gac`. `/samochody/gac-trumpchi/*` → `/samochody/gac/*` ✓.

**Smoke test (curl):**
- `/samochody/gac/trumpchi-m6/` → 200
- `/samochody/gac/trumpchi-e9-phev/` → 200
- `/samochody/gac-trumpchi/trumpchi-m6/` → 301 → `/samochody/gac/trumpchi-m6`
- `/samochody/gac-trumpchi/` → 301 → `/samochody/gac`
- `/samochody/gac/` → 200

**Backup DB:** `~/backups/primaauto/2026-04-29-gac-trumpchi-merge/terms-full.sql` (8.4 MB — wp7j_terms, term_taxonomy, term_relationships, termmeta).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.26 → 0.32.27 (header + ASIAAUTO_VERSION).
- `wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php` — +14 linii (2 wpisy).
- `domains/asiaauto.pl/.../asiaauto-sync.php` + `brand-mapping-v6.1.php` — sync legacy (rollback).

**Co odpada w przyszłych sesjach:** raport orphan-fix `tmp/missing-hubs-2026-04-28.md` linie z Trumpchi M6/E9 PHEV — już rozwiązane (pod prawidłowym parent).

**Pending kosmetyka (nie blokuje):** serie pod GAC z prefixem „Trumpchi" w nazwie (`Trumpchi E8`, `Trumpchi M8`, `Trumpchi S7`, `Trumpchi E8 PHEV`, `Trumpchi ES9 PHEV`, `Trumpchi M6`, `Trumpchi E9 PHEV`) — można w v6.2 wyczyścić do `E8`, `M8` itd. Wymagałoby: rename term name (zachowując slug), batch update post_title, regen sitemap.

## 0.32.26 — 2026-04-29 (Social media — sameAs schema + ikony w stopce)

W sesji konfiguracji wizytówki Google Business Profile dodano profile social media (FB / IG / TT) na stronie:

1. **`sameAs` w `AutoDealer` schema na home** (`class-asiaauto-homepage.php::renderSchema`) — array z 3 URL:
   - `https://www.facebook.com/prima1auto/`
   - `https://www.instagram.com/prima_auto.pl/`
   - `https://www.tiktok.com/@primaauto.pl`
2. **`sameAs` w `LocalBusiness` schema na /kontakt/** (`class-asiaauto-contact.php::renderSchema`) — te same 3 URL.
3. **Ikony social w stopce theme primaauto2026** (`themes/primaauto2026/footer.php`) — pod `<p class="pa-footer__about">` w kolumnie brand. Inline SVG (FB/IG/TT), `target="_blank" rel="noopener nofollow"`. Lista `<ul class="pa-footer__social">` z aria-label.
4. **CSS w `themes/primaauto2026/assets/css/footer.css`** — `.pa-footer__social` (flex gap 10px), kółko 38×38 z `rgba(255,255,255,.08)`, hover na `var(--c-secondary)` z translateY(-1px).

**Backupy:** `class-asiaauto-homepage.php.bak-2026-04-29-social`, `class-asiaauto-contact.php.bak-2026-04-29-social`, `footer.php.bak-2026-04-29-social`, `footer.css.bak-2026-04-29-social`.

**Smoke test:** `curl https://primaauto.com.pl/` + `/kontakt/` — `sameAs` w obu schemach ✓, footer renderuje ikony ✓ (200 OK).

**Dlaczego ważne:** `sameAs` to oficjalny sygnał Schema.org dla Google Knowledge Graph — łączy wizytówkę GBP, profile social i stronę w jeden węzeł tożsamości firmy. Bez `sameAs` Knowledge Panel zostaje pusty (brak boxa „Profile") i Google ma trudność potwierdzić, że profil FB/IG i strona to ten sam podmiot. Wzmacnia E-E-A-T.

## 0.32.25 — 2026-04-29 (Schema NAP fix — AutoDealer name + usunięty numberOfEmployees bug)

Audyt przed wizytówką Google ujawnił dwa problemy w schema na home (`class-asiaauto-homepage.php::renderSchema`):

1. **Niespójność NAP:** `AutoDealer.name = "Prima Auto"` (bez myślnika) vs `LocalBusiness.name = "Prima-Auto"` na `/kontakt/` (z myślnikiem). Google bierze niespójność NAP jako negatywny sygnał Local SEO. Fix: ujednolicenie do `"Prima-Auto"` (zgodnie z legalName "Prima-Auto Ruslan Prima"). Dotyczy też `WebSite.name` ("Prima Auto — Samochody z Chin" → "Prima-Auto — Samochody z Chin").
2. **Bug `numberOfEmployees`:** `numberOfEmployees: { value: 2677, unitText: "vehicles in stock" }` — pole Schema.org `numberOfEmployees` opisuje LICZBĘ PRACOWNIKÓW (oczekiwany QuantitativeValue z liczbą osób), nie samochodów. Google validator może zignorować cały blok lub flaguje inconsistency. Fix: pole usunięte. Stock count i tak jest pokazywany przez `Product/AggregateOffer` per hub modelu (v0.32.23) — nie potrzebujemy go w org schema.

**Backup:** `class-asiaauto-homepage.php.bak-2026-04-29-schema-nap`.

**Smoke test home:**
- Przed: `"name": "Prima Auto"` + `numberOfEmployees: 2677`
- Po: `"name": "Prima-Auto"` + brak numberOfEmployees ✓

**Dlaczego ważne:** w sesji wizytówki Google (Google Business Profile) kluczowe jest aby NAP na stronie 1:1 zgadzało się z nazwą GBP i wizytówką w Knowledge Panel. Niespójność typu "Prima Auto" vs "Prima-Auto" działa jak dwa różne podmioty dla algorytmu Local SEO — utrudnia łączenie sygnałów.

## 0.32.24 — 2026-04-29 (Single listing — sekcja „Inne modele tej samej marki")

- **Internal linking single → hub modelu** (TODO #3 z planu SEO 2026-04-29). Single listing wcześniej linkował do hubów tylko przez breadcrumb i CTA „Wróć do wyników" — brak dedicated cross-link do sibling modeli tej samej marki.
- **Nowa metoda `relatedModels(array $d)`** w `class-asiaauto-single.php`:
  - Pobiera make_term i serie_term listingu przez `wp_get_object_terms`.
  - `get_terms` po taxonomy=serie z parent=make.term_id, exclude=[serie.term_id], hide_empty=true, orderby=count DESC, limit=8.
  - Render: `<section class="aa-related">` z grid kafelek (auto-fill, min 180px). Każdy kafelek = nazwa modelu (display_name termmeta lub fallback name) + count z polską odmianą („27 ofert", „2 oferty", „1 oferta").
  - Link do `get_term_link($sibling)` = hub modelu.
- **Wstawione w `render()`** po `[asiaauto_equipment]`, przed zamknięciem `aa-single__main`. Mobile sticky CTA (`mobileCta`) nadal na końcu.
- **CSS** w `assets/css/asiaauto-single.css`: `.aa-related` (margin-top 32px, separator border-top), `.aa-related__grid` (CSS grid auto-fill), `.aa-related__item` (border 1px, hover translateY+border-accent).
- **Smoke test:** `/oferta/byd-han-dm-2025-96111/` (BYD Han DM-i):
  - Tytuł: „Inne modele BYD" ✓
  - Wyklucza Han DM-i (serie listingu) ✓
  - 8 modeli sortowanych count DESC: Tang DM-i (37), Seal 6 DM-i (37), Song Pro DM-i (29), Qin L DM-i (25), Song L DM-i (21), Song L EV (19), Seal U DM-I (17), Atto 2 (16)
  - Leopard 3 (15, pozycja 9 w DB) odcięty przez limit ✓
- **Dlaczego ważne:** każde single listing daje 8 nowych internal links do hubów modeli tej samej marki. Skala: ~1841 listings × 8 = ~14k nowych internal linków po stronie. To wzmacnia hub authority i daje user-flow „BYD Han DM-i → Tang DM-i" zamiast „BYD Han DM-i → /samochody/" (utrata kontekstu marki).
- **Backupy:** `class-asiaauto-single.php.bak-2026-04-29-related`, `asiaauto-single.css.bak-2026-04-29-related`.

## 0.32.23 — 2026-04-29 (SEO: Product/AggregateOffer hub modelu + lifecycle 301 sprzedanych)

Dwa wins z planu SEO (audyt 2026-04-29 popołudnie):

### #1 Product + AggregateOffer schema na hub modelu (`class-asiaauto-seo.php`)

- **Nowa metoda `buildProductForSerieHub($ctx)`** — emituje `@type: Product` z `offers: AggregateOffer { lowPrice, highPrice, offerCount, priceCurrency, availability }` dla huba modelu (`is_hub === 'serie'`). Hub marki celowo pominięty (zbyt szeroka grupa, niska trafność dla Product Snippet).
- **Nowa `getPriceStatsForTerm(WP_Term $term)`** — single SQL query (JOIN posts × postmeta `price` × term_relationships × term_taxonomy) liczy MIN/MAX/COUNT po wszystkich publish listingach w danym serie term. Filtruje `price > 0`.
- **Wstawione do `renderSchema()`** w obu gałęziach (RankMath ON / OFF).
- **Smoke test:** `/samochody/byd/leopard-8/` → schema dokładnie zgodne z DB:
  - DB: `lowPrice=283000, highPrice=325000, offerCount=5`
  - HTML schema: identyczne wartości ✓
- **Dlaczego ważne:** Google Vehicle Search i Product Snippet wyciągają „od X PLN" z `lowPrice` w `AggregateOffer`. Każdy hub modelu z N>0 listingów dostaje rich result.

### #2 Lifecycle 301 sprzedanych listings → hub modelu (`class-asiaauto-redirects.php`)

- **Modyfikacja `detectListingNotFound()`** — przed dotychczasowym fallbackiem (`is_listing_404 = true` + static make context dla shortcode `[asiaauto_404_listing]`) próbuje `resolveHubUrlForListing($post_id)` i robi `wp_safe_redirect($hub_url, 301); exit;`.
- **Nowa `resolveHubUrlForListing($post_id)`** — preferowany hub modelu (taxonomy=serie), fallback hub marki (taxonomy=make). `wp_get_object_terms` zwraca terms niezależnie od post_status, więc działa dla draft i trash do permanent delete (~30 dni po sprzedaży).
- **Smoke test:**
  - `/oferta/byd-song-l-ev-2025-100886/` (draft) → 301 → `/samochody/byd/song-l-ev/` (200) ✓
  - `/oferta/zeekr-001-2025-108296/` (draft) → 301 → `/samochody/zeekr/001/` (200) ✓
  - `/oferta/nieistniejacy-slug-2024-99999999/` (deleted) → 404 ✓ (poprawny fallback gdy post nie istnieje)
- **Dlaczego ważne:** sprzedane listingi (publish→draft→trash w `class-asiaauto-rotation.php`) wcześniej dawały soft 404 w GSC i traciły equity z backlinków/historycznego rankingu. Teraz 301 do hub modelu kumuluje sygnały SEO na hubach, które są właściwym targetem dla brand+model queries.

### Backupy
- `class-asiaauto-seo.php.bak-2026-04-29-aggoffer`
- `class-asiaauto-redirects.php.bak-2026-04-29-301hub`

## 0.32.22 — 2026-04-29 (Single listing — netto pod brutto, regresja po migracji theme z Elementor)

- **Przywrócenie linii „netto: X PLN"** pod ceną brutto na single listing. Regresja z 2026-04-24 (cutover Elementor → primaauto2026): stary template Elementora 101874 używał shortcode `[asiaauto_price_breakdown]` (rozbicie brutto + netto, VAT 23%); nowy `single-listings.php` woła `[asiaauto_single]`, który w `class-asiaauto-single.php::sidebar()` renderował tylko brutto.
- **Zmiana w `class-asiaauto-single.php:312-321`** (gałąź `if` z ceną): dodany `<span class="aa-single__price-netto">` z netto = brutto / 1.23 (VAT hardcode 23%, spójnie z `[asiaauto_price_breakdown]` w `class-asiaauto-shortcodes.php:1617-1618`). Gałąź `else` („Cena na zapytanie") bez zmian.
- **CSS** w `assets/css/asiaauto-single.css:34`: nowa reguła `.aa-single__price-netto` (14px, var(--sec) szary, font-weight 500, display block).
- **Smoke test:** `/oferta/denza-d9-dm-2024-94073/` → brutto 247 000 PLN + netto 200 813 PLN ✓.
- **Backupy:** `class-asiaauto-single.php.bak-2026-04-29-netto`, `asiaauto-single.css.bak-2026-04-29-netto`.

## 0.32.21 — 2026-04-29 (Stock bary na hub make/serie — przed głównym contentem)

- **Hub make/serie pokazują stock listings PRZED głównym contentem.** User insight: "tych aut nie będziemy mieć dużo, możemy wyciągnąć w Rzeszowie/w drodze przed nowościami" — eksponuj realnie dostępne pojazdy z stocku sprzedawcy na każdym hubu marki/modelu, jeśli istnieją.
- **Shortcode `[asiaauto_hub_listings]` rozszerzony o `reservation_status` parametr** w `class-asiaauto-brand-hub.php`:
  - Filter `reservation_status="on_lot"` lub `"in_transit"` (whitelist).
  - Pusty wynik dla filtra → zwraca `''` cicho (nie pokazuje "Brak ofert").
  - Meta_query dodany do WP_Query.
- **Templates `taxonomy-make.php` + `taxonomy-serie.php`** (theme primaauto2026):
  - 2 nowe shortcody przed `<section class="aa-hub__body">` w nowej `<section class="aa-hub__stock">`:
    - `🇵🇱 {model/marka} — na placu w Rzeszowie` (CTA: `/w-rzeszowie/`)
    - `🚢 {model/marka} — w drodze do Polski` (CTA: `/w-drodze/`)
  - n=6 sztuk na sekcję, sortowanie domyślne (data DESC).
  - Sekcja renderowana **warunkowo** — tylko jeśli przynajmniej jedna z dwóch sub-sekcji ma listings (else nic nie pokazuj, brak placeholdera).
- **Test:** `/byd/sealion-8-dm-i/` pokazuje obie sekcje (1 + 1 listing); `/byd/` (make) pokazuje agregat marki (5 + 9); `/byd/tang-dm-i/` (brak stocku) → 0 sekcji aa-hub__stock ✓.
- **Spójność z v0.32.19/20:** te same emoji + colors + badge na karcie. Klient widzi status w warstwach: home Stock Highlights → hub make/serie stock bars → per-listing badge.

## 0.32.20 — 2026-04-29 (Listing card badges priorytet on_lot/in_transit/nowe)

- **Listing card badges priorytet:** `on_lot` (`🇵🇱 Na placu`, zielony #1B5E20) → `in_transit` (`🚢 W drodze`, niebieski #0D47A1) → `is_new` (`Nowe`, żółty #E8AC07 — fallback). Dotąd listingi miały tylko badge "Nowe" (post < 24h) — nie eksponowały statusu fizycznego pojazdu na froncie.
- **Implementacja w 2 plikach:**
  - `class-asiaauto-homepage.php::getLatestListings()` + `renderLatest()` — sekcja "Najnowsze oferty samochodów z Chin" na home.
  - `class-asiaauto-brand-hub.php::renderListings()` — shortcode `[asiaauto_hub_listings]` używany na hub make/serie.
- **CSS** w `class-asiaauto-homepage.php::renderCSS()` — dodane warianty `.aa-home__car-badge--pl` i `.aa-home__car-badge--transit` (dziedziczą positioning z bazowego `.aa-home__car-badge`).
- **Test live:** `/samochody/byd/sealion-8-dm-i/` pokazuje "🇵🇱 Na placu" na pierwszym listingu (BYD Sealion 8 z `_asiaauto_reservation_status=on_lot`).
- **Spójność z Stock Highlights** (v0.32.19): te same emoji + colors. Sekcja "Auta dostępne teraz" na home + badge na każdej karcie listingu = pełna sygnalizacja statusu fizycznego pojazdu w obu warstwach (home overview + per-listing).

## 0.32.19 — 2026-04-29 (Stock Highlights na home)

- **Sekcja "Auta dostępne teraz" (W Polsce + W drodze)** na stronie głównej — między `renderLatest` a `renderMakes`. User insight: "sprzedawca wie co się powinno teraz sprzedawać" — auta z `_asiaauto_reservation_status` ∈ `{in_transit, on_lot}` to realne flagshipy biznesowe (sprzedawca już zainwestował). Trust signal + 12 internal links z home do hub modeli (poprzednio 0).
- **Implementacja w `class-asiaauto-homepage.php`:**
  - `getStockHighlights()` — DB query po listings z `_asiaauto_reservation_status`, JOIN make+serie, grupowanie per model (make_slug+serie_slug) z licznikami `on_lot`/`in_transit`, sort priorytet on_lot DESC. Cache transient 1h.
  - `renderStockHighlights()` — kafelki (thumb 4:3, title, badge `🇵🇱 W Polsce: N` + `🚢 W drodze: N`). CTA: linki do `/w-rzeszowie/` i `/w-drodze/` (istniejące strony z shortcodami `[asiaauto_inventory reservation_status="on_lot|in_transit"]`).
  - `invalidateStockOnMeta()` — hooki `updated/added/deleted_post_meta` dla klucza `_asiaauto_reservation_status` flushuje transient.
- **CSS** ~30 linii w `renderCSS()`: grid responsywny (2 kolumny <600px), card hover, badges (zielony PL / niebieski transit), CTA primary buttons.
- **Dane na deploy:** 12 unique modeli (5 on_lot + 9 in_transit). Top: BYD Sealion 8 (1+1), BYD Leopard 5 (1+1), Geely Monjaro (1), Denza N8L DM (1), Mazda EZ-6 (1), reszta in_transit-only.
- **Decyzja modele vs listingi:** modele wygrywają — hub trwa wiecznie a single listing znika po sprzedaży = SEO equity przepada; linki do hub kumulują authority dla "BYD Leopard 5/8" itd. które chcemy rankować; badge "1 dostępne dziś" daje trust + klient klika do hub gdzie widzi WSZYSTKIE oferty modelu.

## 0.32.18 — 2026-04-29 (sesja nocna)

- **GSC sitemap fix**: Google indeksował nas na bazie starego `wp-sitemap.xml` (WP native, RankMath ma `noindex` na nim) — submitted 2026-04-23, downloaded 2026-04-27 z 1 warning. **Submit nowych 8 sitemap RankMath** przez Search Console API: `/sitemap_index.xml` + child sitemaps (`page-sitemap.xml`, `make-sitemap.xml`, `serie-sitemap.xml`, `listings-sitemap1-4.xml`). Wszystkie 8 z 0 errors, 0 warnings. Po tym Google zacznie crawl-ować huby modeli (były "URL is unknown to Google" przed).
- **GSC indeks audyt** (11 kluczowych URLs): `/`, `/samochody/`, `/marki/`, `/samochody/byd/` = **PASS** (zaindeksowane, last crawl 2026-04-28). Hub modeli = **NEUTRAL "URL is unknown to Google"** lub "Discovered - currently not indexed" (5/7 hub serie sample). Pierwszy listing single = "URL is unknown" — czyli Google nie wszedł jeszcze głębiej. Sitemap fix + title fix v0.32.17 powinien to odblokować.
- **Search Analytics top 20 queries (28 dni)** — 153 impr na home pos=5.8, brand-dominant (prima auto rzeszów / prima auto / prima-auto). Long-tail flagship już widoczne: `denza z9 gt` pos=42, `geely galaxy starship 8` pos=11, `aito seres` pos=1, `icar v23 cena w polsce` pos=9, `mg auto import` pos=11. Domena świeża, ranking rośnie naturalnie.
- **4 nowe orphan parents serie** (importer dorzucił po popołudniowej naprawie): 3 unique parent fix (`smart #3`, `Jetour X70 PRO`, `Mazda 3 Axela`) + 1 duplikat MERGE (`#6553 Seal U DM-I (Song Plus)` orphan → `#3702` keeper, `seal-u-dm-i` slug). 301 redirect już istnieje w V62_SERIE_REDIRECTS od popołudnia. `serie-broken-parent: 4 → 0`.
- **Chinese-chars batch ×2** w `translations-complectations.php`:
  - +18 entries TIER 4 (`二`→II, `超`→Super, `星夜`→Starnight, `智能超`→Smart Super, `超然致远`→Transcendent Vision, `陆冠`→Land Crown, `星空龙耀`→Starsky Dragon, `定制`→Custom, `首发`→Debut, `途昂`→Teramont, `出众`→Outstanding, `骑士`→Knight, `致行`→Drive, `自在`→Free, `花生`→Peanut, `银河`→Galaxy, `旅行升级`→Travel Upgrade, `纵野`→Wild, `享境`→Journey).
  - +2 entries (`智能`→Smart, `星月女神`→Star Goddess).
  - **chinese-chars: 26 → 8** (dwa tytuły wciąż failują, fragment `为`/`然致远` to sub-fragmenty oraz nowo zaimportowane).

## 0.32.17 — 2026-04-28

- **KRYTYCZNY FIX: title/meta/schema dla hub MODELU.** User zauważył że hub `/samochody/byd/leopard-8/` ma w `<head>` title z hub MARKI: `"BYD — Auto z Chin | Prima-Auto"` zamiast `"BYD Leopard 8 (Denza B8) — Import z Chin | Prima-Auto"`. Powód: WP rewrite `^samochody/(make)/(serie)/?$` ustawia oba query vars, ale **`get_queried_object()` zwraca pierwsze (make=BYD)** — RankMath/theme/schema generują z perspektywy hub MARKI. Każdy hub modelu Google indeksował jako duplikat hub make → 0 rank dla "BYD Leopard 8/5/7", "Denza Z9", itd.
- **Fix w `class-asiaauto-brand-hub.php`** — nowy hook `wp` (prio=5) `fixQueriedObjectForSerieHub()`: dla URL z make+serie nadpisuje `$wp_query->queried_object` na **serie** term + `is_tax=true`. RankMath teraz widzi Leopard 8 jako queried object → bierze `rank_math_title`/`rank_math_description`/`rank_math_focus_keyword` z termmeta serie. Test:
  - `/byd/leopard-8/` → `BYD Leopard 8 (Denza B8) — Import z Chin | Prima-Auto` + meta desc z 3 egzemplarzy 2025...
  - `/byd/leopard-5/` → `BYD Leopard 5 (Denza B5) — Import z Chin | Prima-Auto`
  - `/byd/leopard-7/` → `BYD Leopard 7 (Tai 7) FCB, PHEV — Import z Chin | Prima-Auto`
  - `/aito/m8/` → `AITO M8 — Import z Chin | Prima-Auto`
- **Bonus**: dodane `renderRankMathTitle()` helper resolves `%term%/%sep%/%sitename%/%title%` placeholders w stored RankMath title patterns dla `filterHubTitle` + `overrideHubDocumentTitle` (priorytet rank_math_title term meta jeśli istnieje).

## 0.32.16 — 2026-04-28

- **Sync `wiki_body + FAQ` → natywny `term->description` (RankMath SEO Analyser).** User-pytanie: RankMath nie ma czego analizować bo content jest w custom termmeta (`asiaauto_wiki_body`, `asiaauto_faq_json`), a natywne `term->description` było puste. Fix:
  - **REST endpoint `POST /wp-json/asiaauto/v1/hub-content/{tax}/{id}`**: przy save `wiki_body` syncuje do `term->description`. Przy save `faq_json` dorzuca FAQ jako `<h2 class="aa-rm-faq">...</h2>` + `<details><summary>Q</summary>A</details>` po wiki_body. n8n NIE zmienia się (nadal woła ten sam endpoint).
  - **Backfill całego DB** (raw SQL UPDATE wp7j_term_taxonomy, omija `wp_kses_post`): 49 make + 307 serie termów ma teraz `description` = `wiki_body + FAQ HTML5`. Przykład Chery (term_id 3578): 9950 chars, 5× h2, 8× details. RankMath SEO Analyser teraz analizuje pełen content.
- **FAQPage schema NIE duplikat** — RankMath rozpoznaje tylko własny block `wp:rank-math/faq-block`, NIE rozpoznaje natywnych `<details>` jako FAQ → nie generuje drugiej FAQPage. Sprawdzone: hub `/samochody/chery/` ma nadal 1× FAQPage (nasz custom z `class-asiaauto-brand-hub.php`). GSC FAQ rich results bezpieczne.
- **Frontend nieaktualizowany** — template `taxonomy-make.php`/`taxonomy-serie.php` renderuje wiki_body + FAQ z termmeta (nie z `description`). Wizualnie strona bez zmian. RankMath analizuje description niezależnie od frontu.

## 0.32.15 — 2026-04-28

- **Car schema parity vs west-motors**: dodane `manufacturer` (Organization z brand name) + `offers.priceValidUntil` (+90 dni od teraz, format YYYY-MM-DD). Drobne quality signals dla Google Product Snippet (bold price w SERP). Schema validator (schema.org/validate): **0 errors, 0 warnings** — 14 typów rozpoznanych (Car, Brand, Organization, Offer, OfferShippingDetails, ShippingDeliveryTime, MonetaryAmount, EngineSpecification, QuantitativeValue, DefinedRegion, Country, BreadcrumbList, ListItem, Thing).
- Sitemap audit: 4 `listings-sitemap{1-4}.xml` × 200 URL = ~800 listings indexable. Najnowszy lastmod: 2026-04-28T19:07:46Z (świeży). RankMath emituje sitemap_index.xml + listings-* + page-sitemap.xml. Robots.txt zawiera referencję.

## 0.32.14 — 2026-04-28

- **TIER 3 ×1 corner cases** — `data/translations-complectations.php` +25 entries (user-aprobowane wszystkie ✓+⚠): `尊`→Honor, `劲`→Power, `山河`→Mountain, `东方曜`→Eastern Glory, `今朝`→Today, `御`→Royal, `加长`→Extended, `征服`→Conqueror, `超长蓝鲸`→Long Range Blue Whale, `辰光`→Starlight, `美好`→Wonderful, `过道`→Walkway, `新蓝鲸`→New Blue Whale, `天枢`→Tianshu, `乘用`→Passenger, `领先`→Leading, `龙腾`→Dragon, `灵`→Spirit, `省心`→Worry-Free, `奢享`→Luxury Premium, `幸福`→Happiness, `真香`→Sweet Spot, `跃享`→Joy Premium, `劲为`→Power, `巡礼众享款`→Pilgrimage Edition. Retranslate: 44 → 24 tytułów chińskich (20 naprawionych jednorazowo + trwały efekt na importer).

## 0.32.13 — 2026-04-28

- **Car schema enrichment + OfferShippingDetails na single listings.** `class-asiaauto-single.php::renderMeta()` wzbogacone (utracone przy dedup 2026-04-24): `bodyType`, `vehicleTransmission`, `driveWheelConfiguration`, `color`, `itemCondition` (z taksonomii body/transmission/drive/color/condition), `vehicleEngine` (enginePower KW z `power($ep)` na bazie `_asiaauto_extra_prep` `energy_elect_max_power`). Plus `offers.shippingDetails` (`OfferShippingDetails`): shippingRate 0 PLN, addressCountry PL, handlingTime 0-7 dni, transitTime 56-84 dni — gap vs west-motors zamknięty. Test #281026: 16 kluczy w Car schema (vs 11 wcześniej), 392 KW enginePower, AWD, SUV, UsedCondition, full ShippingDetails.
- **`银河A7 EM` term cleanup.** Term #6539 rename `银河A7 EM` → `Galaxy A7 EM-i` + parent change Galaxy (#3394) → Geely (#3626). 5 listingów strip `银河` z post_title (`Galaxy 银河A7 EM` → `Galaxy A7 EM`). URL `/samochody/galaxy/a7-em/` → 301 → `/samochody/geely/a7-em/` (V61 redirect).

## 0.32.12 — 2026-04-28

- **Tłumaczenia TIER 1⚠ + TIER 3 (25 nowych w `translations-complectations.php`).** User-aprobowane:
  - **TIER 1⚠** (5): `奕派007`→`ePi 007`, `奕派008`→`ePi 008`, `奕派`→`ePi`, `途昂Pro`→`Pro`, `高山8`→`Gaoshan 8` (`超级混`→`Super Hybrid` był już w mapie). Per analizy `tmp/chinese-fragments-analysis-2026-04-28.md`.
  - **TIER 3** (20 top frequency): `式`→`Style`, `商用`→`Commercial`, `智享`→`Smart Premium`, `智慧新蓝鲸`→`Blue Whale 2.0`, `万辆甄选款`→`Selected Edition`, `超越`→`Surpass`, `进取`→`Progressive`, `超级`→`Super`, `战舰`→`Battleship`, `至臻`→`Premium+`, `智雅`→`Smart Elegance`, `鸿蒙座舱`→`HarmonyOS Cabin`, `智逸`→`Smart Comfort`, `悦享`→`Joy Edition`, `向往`→`Aspire`, `传奇`→`Legend`, `冠军`→`Champion`, `磷酸铁锂`→`LFP`, `停产`→`Discontinued`, `运`→`Sport`.
- Mapa używana zarówno przez `wp asiaauto diag apply-fix chinese-chars` (retranslate post_title) jak i przez `class-asiaauto-translator.php:750` przy każdym imporcie z dongchedi → trwały efekt na obecne (98 → 50 tytułów chińskich) + przyszłe.
- **Termmeta `marka=` link sync** (47 termmeta rows): w `asiaauto_wiki_body` zamieniono stare slugi v6.1 marek (`marka=fangchengbao` → `marka=byd`, `marka=galaxy` → `marka=geely`, etc.) na docelowe — eliminacja "linki do Fangchengbao" w hub Leopard/Voyah/itd. po migracji v6.1 marek (treści generowane 2026-04-23 przed v6.1).

## 0.32.11 — 2026-04-28

- **Hub fallback luka: `/samochody/{make}/{cokolwiek}/` → 404.** Wcześniej dowolny string w drugim segmencie URL renderował hub marki (200 + index) — `/samochody/byd/cokolwiek-fake-12345/` zwracał `BYD — Auto z Chin` z `index, follow`. Każdy crawler/spam/typo URL = thin index = duplicate content. Fix w `class-asiaauto-cpt.php::filterHubQuery()`: jeśli `make` lub `serie` term nie istnieje (po `get_term_by('slug', ...)`), `$q->set_404() + status_header(404) + nocache_headers()`. Test: fake → 404 ✓, prawdziwy hub → 200 ✓, V62 redirects → 301 ✓.

## 0.32.10 — 2026-04-28

- **`/zamow/?listing_id=X` → noindex,follow.** User zauważył że formularz wizard zamówienia per listing jest indeksowalny — 1841 ogłoszeń × identyczny szablon = duplicate content na masową skalę. Canonical do `/zamow/` był ustawiony, ale Google czasem ignoruje canonical przy silnych sygnałach (np. linki wewnętrzne z każdego ogłoszenia). Fix analogiczny do v0.32.8 inventory filters: nowa metoda `isOrderWizardPerListing()` w `class-asiaauto-seo.php` (detekcja `post_name='zamow'` + `$_GET['listing_id']`) podpięta w obu hookach `wp_robots` + `rank_math/frontend/robots`. Test: `/zamow/?listing_id=278417` → noindex,follow ✓; `/zamow/` (bez param) → index,follow ✓.

## 0.32.9 — 2026-04-28

- **Dedup orphan-fix duplicates + V62_SERIE_REDIRECTS.** Fix błędu z 0.32.0 `serie-broken-parent apply`: 70 orphan termów dostało parent, ale 11 z nich to były duplikaty istniejących keeperów v6.1 (np. `zeekr-9x`/`9x`, `leopard-5-denza-b5`/`leopard-5`, `atto-3-yuan-plus`/`atto-3`, `seal-u-dm-i-song-plus`/`seal-u-dm-i`, `voyah-taishan`/`taishan`...). Każdy duplikat = 2 huby na ten sam model = split SEO. Naprawa:
  1. **Skrypt `tmp/merge-11-duplicates.php`** — re-tag listings (INSERT IGNORE term_relationships) → keeper, DELETE orphan term, recount keeper. 11/11 OK.
  2. **`class-asiaauto-redirects.php`** — dodana stała `V62_SERIE_REDIRECTS` (mapa per-make `[old_slug → new_slug]`) + metoda `redirectV62SerieDuplicates()` (priorytet 0, wzorzec V61). 11 starych URLs dostaje 301 → keeper.
  3. **termmeta `_asiaauto_primary_make_slug`** — 32 termy zsynchronizowane z v6.1 marek (fangchengbao→byd, galaxy→geely, gac-trumpchi→gac, itd.). Bez tego breadcrumb na hubach pokazywał starą markę (`Fangchengbao` zamiast `BYD`).
- Wynik: serie-broken-parent 70 → 0; duplicate-serie-terms 37 → 29 (reszta = intencjonalne sub-warianty EV/DM); BYD hub clean (1 term per model); breadcrumb po v6.1 marek poprawny. Backup pre-merge: `~/backups/primaauto/2026-04-28-orphan-parent-fix/terms-pre-fix.sql` (3.7 MB).

## 0.32.8 — 2026-04-28

- **Inventory filter URLs noindex.** User zauważył w Screaming Frog że `/samochody/?nadwozie=suv`, `/samochody/?paliwo=hybrid`, `/samochody/?marka=byd` itd. są nadal indeksowalne — duplikat treści z hubów (`/samochody/byd/`). Pierwszy fix v0.32.6 używał `is_post_type_archive('listings')`, ale to nie zwraca true bo `/samochody/` to **WP page z shortcode `[asiaauto_inventory]`**, nie WP archive. Zmiana detekcji na `has_shortcode($post->post_content, 'asiaauto_inventory')` + sprawdzenie `$_GET` z whitelistą filter params (`nadwozie, paliwo, marka, model, naped, rok, cena, kolor, skrzynia`). Aktywne w obu hookach: `wp_robots` (core) + `rank_math/frontend/robots`. Test: `/samochody/?marka=byd` → noindex,follow ✓; `/samochody/` (czysty) → index ✓.

## 0.32.7 — 2026-04-28

- **Homepage SEO refocus na „import samochodów z Chin".** User cel: pierwsza pozycja w Google we frazie „import samochodów z Chin". Zmiany:
  - H1 w `class-asiaauto-homepage.php::renderHero()`: „Samochody elektryczne i hybrydowe z Chin" → **„Import samochodów z Chin do Polski"**.
  - Hero subtitle: „Prima-Auto — agencja importu samochodów z Chin. EV, PHEV, hybrydy. Pełna obsługa: transport, cło, homologacja i rejestracja w cenie."
  - `rank_math_title` page_id=93629: **„Import samochodów z Chin do Polski | Prima-Auto"** (62 chars).
  - `rank_math_description`: focus kw na początku + USP + 1841 ofert + Rzeszów (155 chars).
  - `rank_math_focus_keyword`: **„import samochodów z Chin"**.
  - OG/Twitter title + description ustawione bezpośrednio przez `rank_math_facebook_*` i `rank_math_twitter_*` term meta.
- Strona główna jest WP page (id=93629) z content `[asiaauto_homepage]` shortcode — RM widzi tylko shortcode w editor, ale frontend ma pełną treść (RM analizuje frontend, nie source content).

## 0.32.6 — 2026-04-28

- **RankMath Pro migration — total przeniesienie SEO meta na RankMath.** User po instalacji RM Pro wykrył dublowanie 3 meta z `class-asiaauto-seo.php`: 2× description, 2× canonical, 2× CollectionPage JSON-LD na hubach marek/modeli. Strategia: total migration — RM zarządza title/description/canonical/og/twitter/CollectionPage, zostawiamy tylko nasz ItemList (lista listingów per hub — RM tego nie ma) + FAQPage (z `aa-hub-faq` w wiki_body) + BreadcrumbList na single listings (`class-asiaauto-single.php` nietknięte).
- **Zmiany w `class-asiaauto-seo.php`:**
  - `renderMeta()` — early return gdy `defined('RANK_MATH_VERSION')`. RM emituje canonical/description/og/twitter z `rank_math_*` term meta.
  - `renderSchema()` — gdy RM aktywny, emituje TYLKO ItemList (BreadcrumbList + CollectionPage przejęte przez RM).
  - `filterHomeTitle()` — early return gdy RM aktywny (RM kontroluje title z templates).
  - Backward compat: kod fallback gdy RM dezaktywowany (np. tymczasowo).
- **Bulk setup 284 hubów** (46 marek + 238 modeli z `asiaauto_wiki_body`):
  - `rank_math_focus_keyword` — make: nazwa marki, serie: „Marka Model" (parent-aware).
  - `rank_math_description` — z `asiaauto_seo_desc` (z REST hub-content endpoint, generowane przez n8n batch 0.31.5).
  - `rank_math_title` — template: make `{Marka} — Auto z Chin | Prima-Auto`, serie `{Marka} {Model} — Import z Chin | Prima-Auto`.
- **Thin tax noindex fix.** Nasz `wp_robots` filter był przykryty przez RM. Dodany `rank_math/frontend/robots` filter w `class-asiaauto-seo.php` — wymusza `noindex, follow` na taxonomy `transmission`, `drive`, `exterior-color`, `interior-color`, `condition`. Test: `/skrzynia-biegow/cvt/`, `/naped/awd/`, `/kolor-nadwozia/red/` → noindex ✓; `/paliwo/electric/`, `/samochody/byd/` → index (zostawione, wartościowe).
- **Sitemap regenerowany** przez `wp rankmath sitemap generate`. RM sitemap zawiera: make, serie (×2 plików), listings (×13), pages, local. Brak thin tax (RM domyślnie wyklucza taxonomy z 0 wpisami i niewartościowe).

**Pending (user-side):**
- W RankMath admin UI: Status & Tools → Database Tools → „Re-analyze SEO Score" — bulk obliczy score dla 284 hubów (auto przez admin, niedostępne via WP-CLI).
- Po score: review najgorszych w `Listings → Marki/Modele → Edit` (RM panel z prawej).
- Brakujące huby (4 marki + 70 modeli z `tmp/missing-hubs-2026-04-28.md`): n8n PAUZOWANE, najpierw dobry score na obecnych, potem dodawanie nowych.

## 0.32.5 — 2026-04-28

- **`missing-images` chunked apply + scope fix.** Bug: user dostawał alert „Apply błąd: Invalid JSON" + 2 listingi „nie do ruszenia". Trzy przyczyny:
  1. **Scope rozjazd:** check class scanował `post_status IN ('publish','draft')`, ale skrypt `diag/fix-missing-images.php` filtrował tylko `publish`. Stuck drafts (Xiaomi SU7 #242486, AITO M9 #246353 — oba ghost-offers 404) były znajdowane ale nigdy naprawiane.
  2. **Stdout w response:** `AsiaAuto_API::getOffer()` przy 404 wypisywał warning na stdout (poza zasięgiem `WP_CLI` guard), psuło JSON gdy AJAX response.
  3. **Proxy timeout:** apply na 18+ listingach ~3-4 min > LiteSpeed proxy timeout (~60-90s), klient dostawał truncated response.
- **Fixy:**
  - `diag/fix-missing-images.php`: scope `IN ('publish','draft')`. Plus `$max_items` 3rd arg (chunk size).
  - `class-check-missing-images.php` `applyFix`: `ob_start/ob_get_clean` wokół invocation (output do logger jako warning, JSON czysty); chunk_size=8 per request; zwraca `has_more` + `remaining`.
  - `class-check-base.php` `validateApplyToken`: usunięta `delete_transient` — token TTL-based 300s, reuse w obrębie chunked apply (bez tego każdy chunk wymagałby nowego preview).
  - `assets/admin-diag.js` `previewAndApply`: pętla while max 50 chunks, per chunk update modal z progress („Chunk 3: naprawiono 8 (łącznie 24). Pozostało: 6"), re-scan rowka po wszystkim.

## 0.32.4 — 2026-04-28

- **`missing-images` apply timeout fix.** Domyślny `set_time_limit(25)` w abstract base był za krótki dla `fix-missing-images.php` (iteruje API getOffer + downloadAndStore per listing, ~1-3s/listing × 30+ = >25s). Skutek: skrypt był **przerywany w środku** — naprawiał N listingów, AJAX wracał z `applied=0`, token był single-use'd przed timeoutem. User widział „Naprawiono: 0" ale bazowy count realnie spadał (32→23).
- Override `set_time_limit(300)` w `applyFix()` tylko dla missing-images.
- Logger zapisuje teraz `apply-start` (przed) i `apply-end` (po) — diagnoza timeoutów.
- JS: button „Wykonaj" disabled + tekst „Wykonywanie… (do 5 min)" po kliku, anti-double-click + jasny feedback że apply trwa.

## 0.32.3 — 2026-04-28

- **`chinese-chars` check — split na 3 grupy + edytor tłumaczeń.** Wcześniejsze „370 miejsc z CN" było bezużytecznym agregatem 3 fundamentalnie różnych problemów. Teraz w UI rozdzielone:
  - **Tytuły z CN (117):** post_title z nieprzetłumaczonymi fragmentami. Fix: `asiaauto_diag_chinese_v23(true)` — re-translate przez `translations-models.php` + `translations-complectations.php`.
  - **Aktywne termy z CN (1):** np. `银河A7 EM` parent=0, count=4. Wymagają ręcznej decyzji (rename + migracja listingów na canonical EN). NIE batchujemy — pomijane w apply z error msg.
  - **Orphany count=0 (252):** bagaż migracji v6.1, niewidoczne na froncie. Fix: bulk `wp_delete_term` (kosmetyka bazy).
- **Edytor tłumaczeń inline w modalu.** Sekcja „Niezamapowane fragmenty CN" pokazuje 84 unikalnych fragmentów (`高能`, `征服者`, `真香`, `劲擎`, `头等舱`, …) z formularzem `[CN] [EN input] [select model/complectation] [Dodaj]`. Klik wywołuje nowy AJAX `asiaauto_diag_add_translation` → atomic append do odpowiedniego `translations-*.php` z `.bak-YYYY-MM-DD-HHMMSS` + parse-verify + rollback. Po dodaniu wpisu można re-run `chinese-chars` apply żeby title-fix był pełniejszy.
- Issue meta `unmapped_fragments` array per title issue — pozwala UI pokazać które tytuły wymagają wpisów w mapie.

## 0.32.2 — 2026-04-28

- **Bugfix `make/serie-without-wiki` — meta_key prefix.** Checki używały `wiki_body` jako klucz term_meta zamiast `asiaauto_wiki_body` (faktyczny klucz zapisywany przez REST endpoint `hub-content/{tax}/{id}` z 0.31.5). Skutek: WSZYSTKIE aktywne termy raportowane jako bez wiki, mimo że BYD/Chery/Voyah itd. mają 6000+ znaków opisów. Real counts po fixie: make 50→4 (Changan Qiyuan, Dongfeng Fengshen, GAC Aion Hyper, Wuling), serie 303→66.

## 0.32.1 — 2026-04-28

- **Diag panel — uzupełnienia v1.1 (operacyjne fixy z 1. dnia)** — wszystkie 10 checków mają teraz fix actions, mapując workflow „dojdzie nowy model → popraw mapping → dodaj hub → wygeneruj opis":
  - **Tool 1 (mapping):** `listings-without-mapping` → fix per-item (form `make_eu`/`serie_eu` → atomic write do `data/brand-mapping-v6.1.php` z `.bak`). Future-facing — meta keys w listingach jeszcze niewypełniane.
  - **Tool 2 (hub structure):** Dwa NOWE checki:
    - `serie-broken-parent` — termy serie z `parent=0` ale `count > 0`. Heurystyka: `guessMakeFromListings()` JOIN przez term_relationships → `wp_update_term($id, ['parent' => $make_id])`. Live: 65 termów (artefakty migracji v6.1).
    - `duplicate-serie-terms` — grupuje listingi po prefiksie 3 słów post_title; gdy >1 term serie pod tym prefiksem → grupa. Fix: `wp_set_object_terms` na canonical (priorytet: parent>0 + post_count) + `wp_delete_term` reszty + `flush_rewrite_rules`. Live: 37 grup (m.in. Zeekr 9X #4824 vs #6532). UWAGA: per-item review wymagany, sub-warianty (EV/DM-I) mogą być false-positive.
  - **Tool 3 (description):** `make/serie-without-wiki` (już dodane w 0.32.0 v1.0.1) wysyłają webhook do `https://witold140-20140.wykr.es/webhook/primaauto-{make,serie}-desc` z `usleep(300000)` throttle.
- **`make/serie-without-wiki` — `hide_empty=true`** (zamiast false). Liczą tylko aktywne marki/modele z listingami. Counts: 296→50 (make), 2460→303 (serie) — sygnał operacyjny zamiast szumu.
- **`missing-images` — `getFixMode='confirm'`** (było `auto`) + dry-run probe HEAD na próbie ≤50 listingów w `previewFix()`. Modal pokazuje split: `~X dostanie zdjęcia, ~Y do KOSZA (ghost-offer 404)`.

Pełna trasa zmian: `docs/superpowers/specs/2026-04-28-diagnostyka-admin-panel-design.md` + `docs/superpowers/plans/2026-04-28-diagnostyka-admin-panel.md`.

## 0.32.0 — 2026-04-28

- **Diagnostyka admin panel** — nowe submenu `Listings → Diagnostyka`. 8 checków integralność + SEO coverage. Trzywarstwowy dostęp: UI / AJAX / WP-CLI (`wp asiaauto diag …`). Pluggable rejestr — dodanie checku = 1 plik + 1 linia. Patrz `docs/superpowers/specs/2026-04-28-diagnostyka-admin-panel-design.md`.
- Refaktor 5 skryptów `diag/*.php` na eksportowane funkcje — backward-compat z `wp eval-file` zachowana.

| Data | Wersja | Źródło | Uwagi |
|------|--------|--------|-------|
| 2026-04-24 | 0.31.12 | prod primaauto.com.pl | **Hub content pipeline fix + Galaxy cleanup + batch brakujących hubów.** (a) **Galaxy post-migracja cleanup** — `diag/fix-galaxy-migration.php`: merge 3 duplikatów serii Galaxy pod Geely (A7 PHEV 6079→6517 keep hub content, E5 3667→3397, Starship 6 6078→6516), migracja 6 listingów spod `make=galaxy` (#3394, count=6) na `make=geely` (#3626) + fix CJK w post_title `Galaxy 银河A7 EM → Galaxy A7 EM`, usunięcie orphan `Galaxy M9` #3402. Term make=galaxy zostaje z count=0 (redirect 301 pre-cutover realizuje `AsiaAuto_Redirects::redirectV61Brands`, niezależny od istnienia termu). (b) **n8n prompt caching** — `tmp/generate-n8n-workflows.py`: `system: string` zastąpiony `system: [{type:'text', text:..., cache_control:{type:'ephemeral'}}]`. Per execution 26015 (GAC make) cache_write=2233, exec 26016 (GAC Trumpchi) cache_read=2233 (90% rabat na input). Batch 13 hubów dzisiaj: $0.85 = €0.78 (bez cache byłoby €0.85, saving 8.5% — mniej niż modelowy 15%, bo output 85% kosztu nie cache'uje się). (c) **CRITICAL FIX — workflow routing term_id**: n8n node `Resolve Term ID` używał `/wp/v2/serie?slug=X` bez filtra parent → dla slugów kolidujących między markami (M8 @ GAC Trumpchi + AITO, 07 @ WEY + Avatr, H5 @ Haval + Hongqi, H6 @ Haval + Hongqi, ET5 @ Exeed + NIO, N7 @ Denza + Nissan, plus duplikatów Galaxy-like: Atlas Pro, T2 C-DM, Tiggo 9 vs "Tiggo 9 (Tiggo 8L)") zwracał pierwszy term_id globalnie (zwykle mniejszy) zamiast tego pod zamawianą marką. 9 z 10 wczorajszych zapisów serie trafiło do złych termów. Fix: (1) `class-asiaauto-rest-hub.php::factsForMake/factsForSerie` zwraca pole `term_id` (parent-aware, używa `getSerieTerm($slug, $make)` które filtruje po parent). (2) Workflow n8n: node `Resolve Term ID` WYCIĘTY, URL w `Save to WP` używa bezpośrednio `{{ $('Fetch Facts').first().json.term_id }}`. 2 nody mniej (10 zamiast 11), lżejszy workflow, zero race na resolver. (d) **Rescue skrypt** `diag/fix-batch-hub-routing.php`: move hub_content z 6 błędnych term_id na właściwe (M8/07/H5/H6/ET5/N7) + merge 3 same-brand duplicates (Atlas Pro 3632→3646, T2 C-DM 6510→6518, "Tiggo 9 (Tiggo 8L)" 3586→3582) z przeniesieniem 7 listingów i usunięciem 3 termów. Smoke test: `/samochody/aito/m8/`, `/samochody/avatr/07/`, `/samochody/hongqi/{h5,h6}/`, `/samochody/nio/et5/`, `/samochody/nissan/n7/` — wszystkie 200 z właściwym hub contentem. (e) **Batch 13 brakujących hubów** (3 make GAC/GAC Trumpchi/Wuling + 10 serie): wszystkie zakończone HTTP 200, zero lint_warnings. Pozostało ~35 serie orphan (parent=NULL) — osobny problem (importer-time bug z parametru v6.1 keys; patrz Pending). **Pending (osobna sesja):** systemowy fix importera `class-asiaauto-importer.php:87-110` (translateMark+translateModel PRZED AsiaAuto_Mapping::getEuForCn, fallback partial z parent=mark_term_id, guard CJK w nowych termach, wspólny advisory lock taxonomy writes). Bez tego fix, przyszłe synchronizacje będą tworzyć nowe orphan termy przy każdym modelu brakującym w mapping v6.1. |
| 2026-04-24 | — | prod primaauto.com.pl | **CJK cleanup: merge 3 orphan serie termów + uzupełnienie mapping v6.1.** User zgłosił chińskie znaki w nazwach modeli. Znaleziono 3 aktywne orphan termy serie z parent=0 i CJK w name: `日产N6` #6117 (7 listingów Nissan), `风云X3 PLUS` #6235 (1 listing Chery Fulwin), `奕派008` #6535 (1 listing Dongfeng). Pod właściwymi markami istniały już prawidłowe EN termy hierarchiczne (#6514 N6 / #6519 X3 PLUS / #6258 eπ008) — orphany powstały bo `AsiaAuto_Mapping::getEuForCn()` nie trafiał w klucze CN+CN z API Dongchedi (mapping miał tylko warianty EN+EN: `Nissan|Nissan N6`, `Chery Fengyun|Fengyun X3 PLUS`, `Dongfeng Yipai|eπ008`). Fix (a) `data/brand-mapping-v6.1.php`: 3 nowe klucze CN+CN (`Nissan|日产N6`, `Chery Fulwin|风云X3 PLUS`, `Dongfeng Yipai|奕派008`) wskazujące na te same mark_eu/serie_eu/title_eu co istniejące EN warianty — prewencja powtórzenia orphan-ów przy następnym syncu. (b) `data/translations-complectations.php`: `智领 => 'Smart Leader'` (listing Chery Fulwin X3 PLUS miał `智领` w komplektacji). (c) Nowy `diag/merge-orphan-cjk-serie.php` — hardcoded mapa 3 par (orphan→target), per każda para: remove object terms z orphan, set na target (append=true), update post_title (str_replace CJK→EN + `$translator->translateComplectation`), clean_post_cache, wp_delete_term(orphan), wp_update_term_count_now(target). Guards: orphan musi mieć parent=0, target musi mieć parent>0 (ABORT jeśli nie). Dry-run + APPLY=1. Wynik APPLY: 3 orphan termy usunięte, 9 listingów przeniesionych (7+1+1), 9 tytułów naprawionych. Smoke test: `/samochody/nissan/n6/`, `/samochody/chery-fulwin/x3-plus/`, `/samochody/dongfeng/e-008/` — wszystkie 200 OK z properly scoped listings. Pozostało 0 aktywnych CJK termów (38 orphanów w `make` count=0 to osobny projekt cleanup). Brak bump wersji pluginu — zmiany data-only, kod importera niezmieniony. |
| 2026-04-24 | 0.31.11 | prod primaauto.com.pl | **Breadcrumb single-listing — model klikalny + tytuł listingu jako current (cofnięcie decyzji v0.31.10).** User zgłosił że w breadcrumbie single ogłoszenia nie da się kliknąć w model żeby wejść na jego hub. v0.31.10 świadomie skróciła breadcrumb do 3-poziom (ostatni = serie nieklikalna) dla uniknięcia duplikacji z H1 w sidebarze. Decyzja wygrywa UX: hub modelu ma teraz własny wiki_body + specyfikację, link z breadcrumbu single listing prowadzi do głębszej nawigacji (katalog → hub marki → hub modelu). Fix w `class-asiaauto-shortcodes.php::renderBreadcrumb()` linie 265-280: (a) `$serie->url` zmienione z `null` na `get_term_link($serie)` z guardem `is_wp_error`. (b) Dodany 4-ty element breadcrumbu `get_the_title($post_id)` z `url=null` jako ostatni (current span). Wynik: `Samochody › Marka › Model(link) › Tytuł(current)`. BreadcrumbList JSON-LD w `class-asiaauto-single.php::renderMeta()` już był 4-poziom od v0.31.3 (nietknięty), teraz wizualny DOM znowu zgadza się ze schema. Smoke test na `/oferta/wuling-wuling-yangguang-2025-265775/` — 4 poziomy, Wuling Yangguang linkuje do `/samochody/wuling/wuling-yangguang/`. |
| 2026-04-24 | 0.31.10 | **Single listing + wizard refactor po cutover na standalone theme primaauto2026.** (a) **USP strip (czerwona sekcja 3 kolumny)** — nowa metoda `AsiaAuto_Single::uspStrip()` wywoływana w `render()` między `keySpecs` a `description`. 3 kolumny: „W cenie" (6 pozycji z doprecyzowaniami: „Sterowanie głosowe po polsku (dla wybranych modeli, np. BYD)", „Dwa komplety filtrów (oleju, powietrza, kabinowy)", „Ładowarka 7 kW EU (PHEV / EREV / elektryki)" itd.), „Dlaczego my" (5: Pełna obsługa importu, Odbiór w Rzeszowie, Transparentna cena, 20 lat doświadczenia, Umowa agencyjna), „Informacje" (4 klikalne: Proces zamawiania / Gwarancja / **Homologacja i rejestracja** / Język — Finansowanie i Regulamin wyrzucone, druga wersja Elementor template 101874). Ikonki inline SVG (bez Font Awesome). CSS `.aa-usp-strip` z tłem `var(--accent)` w `asiaauto-single.css`, czcionki 12-13px. (b) **Pogrupowane tech specs + wyposażenie** — `$this->techSpecs($d)` (spłaszcza grupy w jedną tabelę) zastąpione przez `do_shortcode('[asiaauto_tech_specs]')` (6 sekcji: Podstawowe/Silnik/Elektryczny/Skrzynia/Zawieszenie/Zużycie, 8 wierszy rozwinięte + „Więcej" per sekcja). Plus dodane `do_shortcode('[asiaauto_equipment]')` pod tech-specs (wyposażenie checklistami). (c) **Przyciski „Zamów" prowadzą do wizarda** — `cta()` (desktop sidebar), `mobileCta()` (mobile sticky), `renderCTA()` shortcode `[asiaauto_cta]` — wszystkie 3 zmienione z `#zarezerwuj` (placeholder JS alert) na `/zamow/?listing_id=X`. Przycisk „Zarezerwuj" → „Zamów" tekstowo wszędzie. (d) **Breadcrumb 3-poziomowy** — `renderBreadcrumb()` w `class-asiaauto-shortcodes.php` kończył na pełnym tytule listingu → duplikacja z H1 w sidebarze. Teraz ostatni element = nazwa Serie (nieklikalna): `Samochody › Marka › Model`. BreadcrumbList JSON-LD w `renderMeta()` zostaje 4-poziomowy (dla Google). (e) **Sidebar `aa-info` z klikalnymi linkami** — 4 pozycje (Proces/Gwarancja/Homologacja/Język) były `<li>` bez `<a>`. Teraz `<a href=/informacje/.../>` z CSS `.aa-info__list li a` (kropkowany underline, hover akcent). (f) **Breadcrumb + Wyjątki layoutu single** — `[aa_breadcrumb]` dodany do `render()` pod `.aa-single__back`. (g) **Wizard `/zamow/` — zastąpienie `[elementor-template id="174645"]`** w `class-asiaauto-order-wizard.php:440`. Shortcode nie renderował się (Elementor deaktywowany), pokazywał literal string. Natywny HTML: heading „Informacje" + 6 linków (/informacje/*, 2 bugowe slug-i poprawione: Finansowanie/Regulamin wskazywały na `/jezyk-obslugi-pojazdu/`) + 2 buttony (Zadzwoń via `[aa_phone]`, WhatsApp via `[aa_whatsapp]` — zamiast hardcoded starego `+48 783 807 381`). CSS `.aa-wiz__info*` w `asiaauto-order-wizard.css`. |
| 2026-04-23 | 0.31.8 | prod primaauto.com.pl | **Blok B Core Web Vitals — render-blocking + LCP optymalizacja (skromna wersja, po regresji wizualnej cofnięte 3 z 4 Elementor experiments).** Baseline mobile (PSI v5, post-Plan A SEO): home 70/LCP 6,0 s, hub-make-byd 79/3,5 s, hub-serie-byd-han-dm-i 83/3,7 s, listing-jetour 79/3,8 s, outlier xiaomi/su7 83/3,0 s. Render-blocking insight 2,5 s na home. LCP element home = baner cookies Complianza (`#cmplz-message-1-optin`) — TTFB 922 ms + element render delay 1445 ms. (a) **Elementor experiments testowane, 3 z 4 cofnięte po wykryciu regresji wizualnej (padding menu + horizontal scroll na mobile na hubach marek/serii i single listing).** Aktualnie aktywne: tylko `elementor_font_display=swap` (eliminuje FOIT na Inter, BEZ wpływu na layout). Cofnięte do `default`: `elementor_experiment-e_optimized_markup` (zmiana struktury DOM Elementora rozjeżdżała stare CSS theme), `elementor_experiment-e_font_icon_svg`, `elementor_load_fa4_shim` (oba pakiet Font Awesome — bezpieczniej zostawić bez zmian). Backup wartości startowych: `tmp/elementor-options-backup-2026-04-23.txt`. (b) **Resource hints w `class-asiaauto-seo.php`** — nowy hook `wp_head` priority 0 `renderResourceHints()`: `<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>` + `<link rel="dns-prefetch" href="https://fonts.googleapis.com">`. Skraca discovery font Inter o ~150-300 ms. (c) **LCP hero `fetchpriority="high"` + `decoding="async"` w 3 widokach** — `class-asiaauto-homepage.php` linia 270 (grid najnowszych ofert na home, pierwszy `<img>` z iteratora `$i === 0`), `class-asiaauto-brand-hub.php` linia 290 (grid hubów marek/serii, iterator `$aa_idx === 0`), `class-asiaauto-shortcodes.php` linia 374 (`renderGallery` main image w single-listing). Reszta `<img>` w gridach: `loading="lazy" decoding="async"`. (d) **Nowa klasa `class-asiaauto-perf.php`** (~70 linii) — wpięta przez `require_once` w `asiaauto-sync.php` po `class-asiaauto-seo.php`, self-instantiating pattern jak SEO. Konstruktor wcześnie returnuje na `is_admin()` (perf hooki tylko na frontendzie). Dwa hooki: (1) `wp_enqueue_scripts` priority 100 `dequeueUnused()` — na hubach taxonomy `make`/`serie`, archive `listings`, single `listings` (warunek `isPluginRenderedPage()` — strony renderowane szablonami PHP / shortcodami `[asiaauto_*]`, nigdy Gutenberg blocks) `wp_dequeue_style` dla `wp-block-library` + `wp-block-library-theme`. **WAŻNE:** pierwotnie wycinaliśmy też `global-styles` + `classic-theme-styles`, ale to powodowało regresję wizualną — Hello Elementor i child theme `asiaauto` polegają na zmiennych CSS `--wp--preset--*` (padding, spacing, color tokens) emitowanych przez `global-styles`. Komentarz w kodzie z ostrzeżeniem żeby nie ruszać. Wycina 14,6 KiB unused CSS na hubach/listing. (2) `wp_default_scripts` `removeJqueryMigrate()` — usuwa `jquery-migrate` z `deps` zarejestrowanej `jquery` (frontend-only, admin nietknięty żeby nie psuć starszych wtyczek admina). Wycina 5 KB JS + 363 ms render-blocking. Smoke test: na hubie BYD, hubie serie, single listing — `block-library` zniknął z HTML, `jquery-migrate` zniknął z `<script>` na wszystkich stronach (frontend), `jquery-core-js` ładuje się standalone. (e) **`elementor_css_print_method=internal` testowane i COFNIĘTE** — internal inlinuje cały CSS Elementora w `<head>` (eliminuje 5 osobnych blocking requests `post-XXX.css` ~700 ms parallel), ale na home (Frontpage Elementor template) zwiększa HTML body ze wszystkim CSS frontpage → TBT 190→320 ms i PERF 74→69. Po reverse to `external` home wraca, ale per-post CSS pliki znów blokują (akceptowalny trade-off). Backup: `class-asiaauto-{seo,homepage,brand-hub,shortcodes}.php.bak-2026-04-23-cwv` + `asiaauto-sync.php.bak-2026-04-23-cwv` + `taxonomy-make.php.bak-2026-04-23-cwv` (ten ostatni niewykorzystany — myliłem ścieżki, faktyczny grid jest w `class-asiaauto-brand-hub.php`). **Wynik finalny (PSI mobile, po reverse experiments do baseline + naprawie dequeue + włączeniu tylko font_display=swap):** home 69/6,1 s LCP, hub-make-byd 83/3,5 s LCP (+4 PERF), hub-serie-byd 80/3,6 s LCP, listing-jetour 82/3,8 s LCP (+3 PERF), outlier xiaomi/su7 84/3,5 s LCP. **Skromny zysk +3-4 PERF na hubach/listing** (gros wygranej z 4 experimentami był w fazie z `e_optimized_markup=active` + `e_font_icon_svg=active` które zostały cofnięte z powodu regresji wizualnej — vide notatka wyżej). LCP huby spadł z 3,5-3,7 s do 3,5-3,6 s — niewielka delta vs noisy PSI. **Pełny potencjał (z 4 experimentami): hub-make 88/3,0 s, hub-serie 86/3,0 s, listing 85/3,5 s** — osiągalny po refactorze theme (header/footer w czystym kodzie zamiast Elementor templates). FCP wszędzie 3,0 s (wąskie gardło: jQuery 30 KB 600-740 ms blocking, niemożliwe do wycięcia). RB insight wciąż 2,1-2,3 s — to suma wastedMs, faktyczny serial chain krótszy bo wiele plików ładuje się równolegle. **Pending Blok C (osobne projekty):** (i) **Header/footer rewrite na czysty kod child theme** (4-8h, Twoja sugestia po regresji wizualnej) — zostawia Elementor tylko dla single-listing template; wycina ~70% Elementor render-blocking CSS na hubach (większość z 19 plików ładowana dla header/footer); LCP huby pewnie spadnie poniżej 2,5 s = green. Po tym można bezpiecznie ponownie włączyć `e_optimized_markup` i `e_font_icon_svg` bez regresji (bo header/footer już nie zależą od Elementora). (ii) **Fix hubów make/serie z dziś** — padding na mobile za duży na obu, na hubach marek listingi wyświetlają się "źle" (różnie niż na hubach modeli mimo że oba używają `[asiaauto_hub_listings n=5]` → `renderListingsCompact` — pewnie wiki_body BYD od n8n zawiera tabelkę/element overflow-x na mobile, do zbadania ze screenshotem). 15-30 min. (iii) Cookie banner Complianza jako LCP element home (4,9-6,1 s) — UX redesign na mniejszy bottom-bar; opcja `cmplz_cookiebanners.use_custom_cookie_css=yes` + custom_css redukujący wysokość banera. Home ≠ landing SEO (huby są), niski priorytet. (iv) Critical CSS inline (above-the-fold extract, defer reszty). (v) Self-host Inter font (eliminacja preconnect → 0 ms cross-origin penalty). (vi) Cron PSI weekly + GSC 7d delta monitoring. |
| 2026-04-23 | 0.31.7 | prod primaauto.com.pl | **SEO meta/schema dla home + huby marek/serii + sitemap filter — Plan A sesji SEO.** Audyt baseline (2026-04-23 21:15): sitemap submitted do GSC (primaauto.com.pl zweryfikowany siteOwner, 1841 listings + 45 marek + 275 serii + 27 URL-i w 5 thin taksonomiach), 12/12 hubów bez canonical/meta desc/og (listingi 3/3 OK), PSI mobile LCP 3.5-6.1s (outliery 51-69 perf score = SU7 + MG MG4 bez wiki_body cachowanych), SEO score wszędzie 92/100. Raport w `tmp/seo-audyt-2026-04-23.md`. Fix: nowa klasa `AsiaAuto_SEO` (`includes/class-asiaauto-seo.php`, 381 linii) — hooki `wp_head` priority 1 (meta) + 2 (schema): (a) Homepage — canonical + desc + og:website/image + twitter:card + og:locale=pl_PL; title override przez `document_title_parts` filter dedup blogname vs blogdescription (było: `"Prima-Auto - Import Samochodów z Chin – Samochody z Chin — import, sprzedaż, gwarancja"` → jest: `"Prima-Auto — Import samochodów z Chin do Polski"`). Schema WebSite/AutoDealer bez zmian (nadal w `class-asiaauto-homepage.php`). (b) Hub marki `/samochody/<make>/` — canonical + desc z `asiaauto_wiki_body` term_meta trim 158 chars (np. BYD: „BYD (Build Your Dreams) to chiński koncern założony w 1995 roku…") → fallback template `"Importuj {marka} prosto z Chin do Polski — {count} ofert…"`; og:image = pierwsze zdjęcie najnowszego listingu → fallback `get_theme_mod('custom_logo')`; BreadcrumbList 3-poziom (Home→Samochody→Marka) + CollectionPage + ItemList top 10 najnowszych listings. (c) Hub serii `/samochody/<make>/<serie>/` — jw. z BreadcrumbList 4-poziom, `display_name` term meta → fallback term->name. (d) Filter `wp_sitemaps_taxonomies` wyklucza 5 thin taksonomii (`transmission, drive, exterior-color, interior-color, condition`) z `wp-sitemap.xml` — po deploy sitemap zawiera tylko `make, serie, body, fuel, ca-year` + posts + page. (e) Filter `wp_robots` dokleja `noindex, follow, max-image-preview:large` na te 5 thin taxonomii — po deploy `/skrzynia-biegow/cvt/`, `/naped/awd/`, `/kolor-nadwozia/*`, `/kolor-wnetrza/*`, `/stan/*` mają meta robots noindex (Google wyczyści z indeksu). Single listings (`/oferta/*`) bez zmian — pełen SEO (Car + BreadcrumbList + og:product) nadal w `class-asiaauto-single.php`. FAQPage schema z `class-asiaauto-brand-hub.php` nadal emitowany jako osobny JSON-LD (hub BYD ma teraz 4 JSON-LD bloki: BreadcrumbList + CollectionPage + ItemList + FAQPage). Deploy: backup `asiaauto-sync.php.bak-2026-04-23-seo` (wcześniejsze 0.31.6), copy `tmp/class-asiaauto-seo-draft.php` → `includes/class-asiaauto-seo.php`, require_once w asiaauto-sync.php po `class-asiaauto-contact.php`, bump `ASIAAUTO_VERSION 0.31.6 → 0.31.7`, `php -l` obu OK, `wp cache flush`. Smoke test 7/7: home title dedup ✓, canonical/desc/og home ✓, hub BYD wiki_body desc + og:image z pierwszego listingu Song L EV ✓, hub SU7 z zera → 3 schema + fallback desc „51 ofert…" ✓, thin tax `noindex, follow` ✓, sitemap index bez 5 thin taksonomii ✓, listing Denza bez zmian (Car+BreadcrumbList+og:product) ✓. PSI re-check mobile: home perf 75→70/SEO **92→100**, hub BYD perf 76→84/SEO **92→100**, hub SU7 perf 51→84/SEO **92→100** (SU7 skok perf = cache warm po smoke; variance, nie optymalizacja CWV). LCP mobile 3.5s unchanged — CWV to osobny Blok B (fetchpriority/preload LCP image, Elementor CSS combine, GTM/Complianz defer). GSC `wp-sitemap.xml` resubmit via API 2026-04-23 19:18 UTC (errors:0, warnings:0). Pending Blok C (po batch wiki_body dojedzie): GSC URL Inspection request-indexing dla top 10-20 hubów, DataForSEO SERP baseline (pozycje brand/model vs west-motors.pl), cron PSI weekly + GSC 7d delta monitoring. |
| 2026-04-23 | 0.31.6 | prod primaauto.com.pl | **Importer używa mapowania v6.1 również dla `post_title` i slug.** User zgłosił „GAC Trumpchi Trumpchi GS4" x2 (dwa osobne inner_id 23417343/23567330 z różnych miast — Huizhou/Jieyang — to dwa fizyczne auta, nie race condition; ale tytuł/slug mimo v6.1 był surowy CN). Przyczyna: w `importListing()` (linie 87-102) `$title` i `$model_for_slug` były budowane z `$data['mark']` / `$data['model']` przez translator, **mapping v6.1 odpalał się dopiero w `setTaxonomies()`** — taksonomie jechały EU, ale `post_title` i `post_name` zostawały CN. Batch v6.1 z 0.31.2 naprawił 930 istniejących, ale każdy nowy import od tamtej pory generował znów raw CN (dzisiaj: 263366, 263590). Fix: dodana gałąź `AsiaAuto_Mapping::getEuForCn($markCN, $modelCN)` przed budową title; przy hicie używamy `mark_eu` + `serie_eu` (fallback na obecną logikę z translatorem dla niezmapowanych par). Cleanup istniejących (4 listingi): 263366/263590 GS4 → „GAC GS4 2024 ...", 241831/243443 → „GAC Hyptec HT 2024 ..." (post_title + post_name). Nie ruszane: 4× GWM Cannon — ich `title_eu` w v6.1 intencjonalnie zachowuje prefix „GWM Cannon Great Wall Pao" (chińskie znaki w komplektacji to osobny gap translatora, nie dotyczy mapy). 3× residuale nie obecne w v6.1 (249841 GAC Aion Hyper HL, 259561+262459 Dongfeng Yipai eπ007) — zostają do v6.2 zgodnie z ADR 0.31.2. |
| 2026-04-23 | 0.31.5 | prod primaauto.com.pl | **SEO content huby: 3 widoki + n8n content pipeline (Claude Sonnet 4.6).** (a) Strona `/marki/` — page_template `page-marki.php` w child theme, grid 29 marek z count (Top 8 hardcoded + reszta alfabetycznie), page ID 263572. (b) Archive `make` — `taxonomy-make.php` — hero, `{{LISTINGS_BAR}}` w wiki_body podmieniany na compact grid 5×, sekcja pigułek modeli (`aa-brand-card`, renderowana przez `AsiaAuto_Brand_Hub::renderModelPills()`), USP box („W cenie / Dlaczego Prima-Auto / Informacje"), FAQ. (c) Archive `serie` — `taxonomy-serie.php` wymuszany przez `template_include` filter (dwupoziomowy URL). Tabelka specyfikacji z 14 wierszami (wymiary, waga, zasięg WLTC+total, bateria, przyspieszenie, moc el., napęd/paliwo breakdown), lista wyposażenia standardowego z PL-labelami (22 cechy: kamera 360, keyless, ambient, masaż/wentylacja/podgrzewanie foteli, HUD, auto park itd.), sekcja porównawcza z EU, drugi listing bar „Zobacz też inne oferty [marka]", USP box, FAQ. (d) Nowa klasa `AsiaAuto_REST_Hub` (class-asiaauto-rest-hub.php, ~400 linii) — 5 GET endpointów: `facts-for-make/{slug}`, `facts-for-serie/{slug}?make=X`, `latest-by-make/{slug}?n=N`, `latest-by-serie/{slug}?n=N&make=X`, `aliases-for-make/{slug}`, `aliases-for-serie/{slug}?make=X` (wyciąga sub-brand aliasy z `data/brand-mapping-v6.1.php`: Fangchengbao, Yangwang, Denza). Plus POST `hub-content/{taxonomy}/{id}` z auth `X-AsiaAuto-Token` (shared secret `ASIAAUTO_HUB_TOKEN` w wp-config, kopia w `~/secrets/primaauto/hub-token.txt`) — zapisuje `asiaauto_wiki_body`, `asiaauto_faq_json`, `asiaauto_seo_desc` do term_meta. Fact block `factsForSerie` parsuje `_asiaauto_extra_prep` (JSON z ~200 chińskich kluczy per listing) → wyciąga specs (wymiary, bateria, zasięg, przyspieszenie, waga, moc) + `features_standard/optional` (zlicza % pokrycia) + `notable` (seat_material, sound_brand, autonomy_level) + taxonomies `fuel/drive/body/ca-year` breakdown. (e) `class-asiaauto-brand-hub.php` rozszerzone o `renderListingsCompact($make, $serie, $n, $cta_url, $cta_label, $heading)` — kopiuje markup `aa-home__car*` z homepage (badge „Nowe" dla <24h, specs `paliwo · rocznik · przebieg km`, CSS variables na wrapper `.aa-hub__latest-wrap`, grid 5 kolumn na desktop / slider mobile). Plus `renderModelPills($make_slug)` → markup `aa-brand-card`. Shortcode `[asiaauto_hub_listings]` teraz akceptuje atrybuty `n`, `cta_url`, `cta_label`, `heading`. `renderFaq` przestał renderować własny `<h2>Najczęściej zadawane pytania</h2>` — nagłówek decyduje template (zniesienie duplikatu H2). (f) 2 workflow n8n (aktywne na witold140-20140.wykr.es): `primaauto-make-desc` (ID `BqM9UJ2HnxGVAllb`) + `primaauto-serie-desc` (ID `dt4Q78mqwyuzc1Ta`). Pipeline: Webhook POST → 3× HTTP Request (fetch facts + latest + aliases) → Merge → Code (build prompt + anthropic_body inline) → HTTP POST Anthropic (model `claude-sonnet-4-5` = Sonnet 4.6, max_tokens 8000, system prompt ~4000 tokens z kontekstem Prima-Auto, USP „praktycznie nowe auta, nie klasyczne używane", realnym procesem 8-12 tyg, zakazem „+15-20k za import", FORBIDDEN phrases, wymogiem placeholder `{{LISTINGS_BAR}}`, zakazem sekcji FAQ/„Modele" w wiki_body, wymogiem tabelki 14+ wierszy dla serie, porównania z segmentem EU, linków wewnętrznych do filtrów `/samochody/?paliwo=erev&marka=X&naped=awd`) → Code Parse+Lint (regex wycina ewentualne sekcje FAQ i „Modele ... dostępne" z wiki_body, JSON safety: zakaz `"` wewnątrz wartości, lint FORBIDDEN phrases, warning gdy brak LISTINGS_BAR) → HTTP GET `/wp/v2/make?slug=X&_fields=id` → HTTP POST `/asiaauto/v1/hub-content/{tax}/{id}` → Respond. Workflow JSON generowane przez `tmp/generate-n8n-workflows.py` (pobiera klucze z `~/secrets/`, output gitignored bo inline keys). Czysty JSON z placeholderami zostanie commitowany do `workflows/` po przeniesieniu do n8n credentials. (g) Strona główna: linki marek w `AsiaAuto_Homepage::renderMakes()` zmienione z `$inv_url.'?marka='.slug` (filtered inventory) na `get_term_link($term)` (hub marki) — buduje autorytet hubów w indeksacji Google zamiast parametrycznych wariantów inventory. „Wszystkie marki →" → `/marki/`. Filtry body/fuel bez zmian (nie mają jeszcze dedykowanych hubów). (h) Batch generacji ruszył w tle: `tmp/batch-hub-parallel.sh` z xargs -P3 (3 concurrent webhooks), kolejka ~175 (45 marek + ~130 modeli count>0), szacowany czas ~60 min, koszt ~$25 Sonnet 4.6. Log: `tmp/batch-hub-parallel-*.log`. Pilot Voyah + Voyah FREE zweryfikowany przed batchem — 3 iteracje system promptu: v1 (pierwszy render), v2 (USP „jak nowe", ceny końcowe, realny proces, zakaz FAQ/„Modele" w wiki_body, JSON safety), v3 (tabelka 14 wierszy z specs, lista wyposażenia z PL-labelami z `features_standard`, aliasy sub-brand, internal linki). Pending: przeniesienie kluczy Anthropic + `X-AsiaAuto-Token` do n8n credentials (po weryfikacji batch), prompt caching (`cache_control: ephemeral`) dla oszczędności 2-3× przy kolejnych runach. |
| 2026-04-23 | 0.31.4 | prod primaauto.com.pl | **Plan A wdrożony — MySQL advisory lock zamiast transient w sync.** Nawrót dupli: 25 par (9 z 2026-04-22 17:04-17:11 + 16 z 2026-04-23 09:20-09:25). Root cause ten sam co w ADR 2026-04-22: sync trwa >10 min, transient TTL wygasa, równoległy cron drugiej instancji pali ten sam `change_id` i `findByInnerId` dla obu zwraca null. Fix w `class-asiaauto-sync.php`: transient `asiaauto_sync_lock_{source}` (get/set/delete) zastąpiony przez `GET_LOCK('asiaauto_sync_{source}', 0)` + `RELEASE_LOCK` w punktach wyjścia (normalne + error-return po getChangeId). Plusy: auto-release przy crashu PHP (sesja MySQL kończy się), brak TTL do wygaśnięcia. Cleanup: `wp post delete --force` na 25 MAX(post_id) — 0 rezerwacji, 0 zamówień na usuwanych. Backup przed dedupem: `~/backups/primaauto/2026-04-23-pre-dedup-posts-postmeta.sql.gz` (19 MB — tylko posts+postmeta, reszta bazy nietknięta). Legacy asiaauto.pl NIE zaktualizowany (odseparowany na 0.30.15-16, `DISABLE_WP_CRON=true` → race nie występuje; full sync pluginu to osobny projekt). |
| 2026-04-23 | 0.31.3 | prod primaauto.com.pl | **Fixy UX ogłoszenia + breadcrumbs + upload zdjęć.** (a) Fix bugu detekcji `fuel_type` w podglądzie importu ręcznego (`class-asiaauto-admin-manual-import.php`) — kolejność `$fuel_map` zmieniona tak, by `phev/mhev/erev/electric` były sprawdzane przed ogólną `hybryda`. Poprzednio `str_contains` łapał `hybryda` z nazwy „Hybryda plug-in (PHEV)" i ustawiał `hybrid` (akcyza 1.55%) zamiast `phev` (0%) → preview pokazywał cenę o ~2000 zł wyższą niż ogłoszenie (160k vs 162k). Dotyczyło wszystkich PHEV/MHEV/EREV. Importer/applyToListing bez zmian (czytał slug taxonomy bezpośrednio). (b) Data pierwszej rejestracji na froncie: shortcode `[asiaauto_key_specs]` kafelek „Rok" (ca-year) → „Rejestracja" MM/YYYY z `registration_date` (fallback: kafelek ukryty gdy meta brak). 1828/1840 listingów ma reg_date. Rok modelowy przeniesiony do shortcode `[asiaauto_tech_specs]` jako pierwszy wiersz sekcji „Dane podstawowe" (`buildTechSpecSections` rozszerzone o opcjonalny `$post_id`, `array_unshift` na sekcję `podstawowe`). Karta listingu w `/samochody/` (`AsiaAuto_Inventory::getSmartSpecs`): rok z reg_date z fallbackiem na ca-year. `class-asiaauto-single.php::gather()` + `keySpecs()` analogicznie (na wypadek `[asiaauto_single]`). (c) BreadcrumbList JSON-LD w `AsiaAuto_Single::renderMeta()` — 4 poziomy: Samochody › Marka › Model › Tytuł (każdy z `item` URL zgodnie z rekomendacją Google, mirror wizualnej wersji z shortcode). Shortcode `[aa_breadcrumb]`: root „Oferta"→„Samochody" (zgodność z URL `/samochody/`), ostatni element zmieniony z samej Serie na pełny tytuł ogłoszenia (nieklikalny current), Model klikalny (get_term_link dwupoziomowy). (d) Fix fatalu przy ręcznym uploadzie zdjęć (`AsiaAuto_Media::storeLocalUpload` linia 301): `buildAltText($listing,...)` → `buildAltText($post_id,...)`. `$listing` (array) był błędnie przekazywany tam, gdzie PHP 8 strict types wymagał `int` → TypeError, 0/N plików zapisanych, komunikat "Argument #1 (\$post_id) must be of type int, array given". Importer (downloadSingleImage, linia 176) był OK. Backupy: `class-asiaauto-admin-manual-import.php.bak-2026-04-23`, `class-asiaauto-single.php.bak-2026-04-23-regdate`, `class-asiaauto-shortcodes.php.bak-2026-04-23-regdate`, `class-asiaauto-inventory.php.bak-2026-04-23-regdate`, `class-asiaauto-media.php.bak-2026-04-23`. |
| 2026-04-23 | 0.31.2 | prod primaauto.com.pl | **Mapowanie marek/modeli v6.1 (CN→EU) — rebuild taksonomii + parent-aware URL handler + importer-time mapping.** Źródło: `tmp/mapowanie-marek-modeli-v6.1.csv` (264 wiersze) = xlsx klienta z 2026-04-23 + 16 decyzji quizu + #264 Exeed VX dopisany. (a) **Etap 1 — CSV v6.1** (90 zmian + 57 synchronizacji slug): Q1 BYD prefix dla Leopard, Q3 Denza B5/B8 w nawiasach, Q4 Sealion uniformity (Sea Lion/SeaLion→Sealion, ALE tylko w serie/title — model CN zostawiony bo pasuje do API Dongchedi), Q6 GAC Aion Hypec→GAC + Hyptec HT, Q10 Chery iCAR 03/V27 label prefix, Q14 WEY 7→07, Q15a Li Auto i6 scalenie #57+#188, Q15b MINI skip, Q16 Nissan N6 scalenie #83+#142. XPENG P7+ slug `p7-plus` (fix: sanitize_title zjadał `+`). (b) **Etap 2 — Backup** `~/backups/primaauto/2026-04-23-v6.1-taxonomy/terms-*.sql` (540KB, 4 tabele). (c) **Etap 3 — Rebuild taksonomii**: 8 nowych marek (term_id 6521-6528): BAIC, Beijing 212, Chery Fulwin, Exlantix, GAC, GWM, Luxeed, Nevo. 208 rename serie, 258 move_parent (Fangchengbao/Yangwang→BYD +28, Galaxy→Geely +62, iCAR→Chery +5, Trumpchi/Aion Hyper→GAC +55, Fengyun→Chery Fulwin +21, Maextro→Luxeed +18, Qiyuan→Nevo +7). 510 termmeta (`_serie_full_title` + `_serie_api_value`). 2 create serie (Hyptec HT term 6529 pod GAC — listingi przeniesione z starego term 5489 Hyper HT; Lynk & Co 900 term 6530). Migracja per-serie: 256 listingów przepisanych między markami przez term_relationships INSERT IGNORE + DELETE. Recount make counts. Residuals (Exeed 4, iCAR 6, Galaxy 5, Jetour Shanhai 3, Dongfeng Yipai 2 = 20 listingów niezmapowanych w v6.1, zostają pod starą marką do v6.2). Skrypty: `tmp/analyze-taxonomy.php` (dry-run raport), `tmp/apply-taxonomy.php` (APPLY), `tmp/migrate-make-per-serie.php` (APPLY). (d) **Etap 4 — Kod**: nowa `class-asiaauto-mapping.php` (singleton lookup), `data/brand-mapping-v6.1.php` (260 pozycji, klucz `markCN\|modelCN`). Importer `setTaxonomies()` przed `setTaxonomyAndMeta` wywołuje `AsiaAuto_Mapping::getEuForCn()` — nowe listingi z Dongchedi trafiają od razu pod EU-markę i EU-serie (np. `Galaxy\|Galaxy E5` → make=Geely, serie=Galaxy E5). `class-asiaauto-brand-hub.php`: **parent-aware** `getContextTerm()` (priorytet: dwupoziomowy URL `/samochody/<make>/<serie>/` zwraca serie-term filtrując przez parent=make, rozwiązuje kolizje slug typu `n7` Denza vs Nissan, `m8` AITO vs GAC Trumpchi, `07` Avatr vs WEY), nowe hooki `document_title_parts` + `pre_get_document_title` czerpiące `_serie_full_title` (np. `<title>BYD ATTO 3 (Yuan PLUS)</title>` zamiast samego `BYD`). `class-asiaauto-inventory.php`: nowa prywatna `getSerieTermByMakeParent()` + fix 3 miejsc (filterInventoryTitle, renderInventoryMeta, renderSeoBlock). Backupy: `class-asiaauto-brand-hub.php.bak-2026-04-23`, `class-asiaauto-inventory.php.bak-2026-04-23`. **Redirecty 301 — dodane wstecznie** (klient zmienił decyzję): `class-asiaauto-redirects.php` nowa metoda `redirectV61Brands()` + stała `V61_MAKE_REDIRECTS` (16 par: fangchengbao/yangwang→byd, galaxy→geely, chery-fengyun→chery-fulwin, gac-trumpchi/gac-aion-hyper→gac, icar→chery, maextro→luxeed, great-wall→gwm, changan-qiyuan→nevo, beijing-off-road→baic, 212→beijing-212, jetour-shanhai→jetour, dongfeng-fengxing/yipai→dongfeng, lotus-cars→lotus). Pattern: `^samochody/<old>/(.*)` → 301 `/samochody/<new>/$1`. **Batch update post_title**: 930 listingów zaktualizowanych (`tmp/update-listing-titles.php`) wg mapy v6.1 — parser 3-poziomowy (full prefix markCN+modelCN, modelCN self-contained, markCN multi-word z dedupe ostatniego słowa). Np. `Fangchengbao Leopard 5 2025...` → `BYD Leopard 5 (Denza B5) 2025...`, `Chery Fengyun T11 2026...` → `Chery Fulwin T11 2026...`, `BYD Haishi 07 EV...` → `BYD Sealion 7...`, `Geely Xingyue L...` → `Geely Monjaro...`. 18 listingów zostało z CN-prefix celowo (7× iCAR Super V23 niezmapowane w v6.1 + 11× GAC Trumpchi X gdzie title_eu w v6.1 zachowuje prefix „GAC Trumpchi"). Smoke test: 6 URL hubów 200 OK z poprawnymi title — `/samochody/byd/atto-3/`→`BYD ATTO 3`, `/gac/hyptec-ht/`→`GAC Hyptec HT`, `/byd/leopard-5/`→`BYD Leopard 5 (Denza B5)` (dual-name), `/exeed/vx/`→`Exeed VX` (nowy model #267), `/chery/icar-03/`→`Chery iCAR 03` (label rozróżnia od Tiggo 4/7/8/9 pod Chery). |
| 2026-04-22 | 0.31.1 | prod primaauto.com.pl | **Konwersje GA4 dla Google Ads: click_phone, click_whatsapp, generate_lead.** (a) GA4 property Prima-Auto.pl (534017542, stream G-F1NCC3D2HZ) — 3 key events utworzone przez Admin API: `click_phone`, `click_whatsapp`, `generate_lead`. (b) GTM container GTM-T4GTZ2JB (account 6351095501, container 250095450, workspace 5) — przez Tag Manager API v2 utworzone: 3 triggery Custom Event (`CE - click_phone/whatsapp/generate_lead`), 3 tagi GA4 Event (`GA4 - click_phone/whatsapp/generate_lead` używające zmiennej `{{GA4-ID}}`), 3 Data Layer Variables (`DLV - listing_id`, `DLV - vehicle_name`, `DLV - value`). Publikacja ręczna przez UI (scope `tagmanager.edit.containerversions` nie dodany do OAuth). (c) Nowy plik `assets/js/asiaauto-tracking.js` — delegated click listener dla `a[href^="tel:"]` → `dataLayer.push({event:'click_phone'})`, dla `a[href*="wa.me/"]` / `whatsapp://` / `api.whatsapp.com` → `dataLayer.push({event:'click_whatsapp'})`. (d) Enqueue globalny w `asiaauto-sync.php` hook `wp_enqueue_scripts` (każda strona frontu, cache-buster po `filemtime`). (e) `assets/js/asiaauto-order-wizard.js` w `handleStart()` po sukces `order/start` pushuje `generate_lead` z `currency:'PLN'`, `value` (z `C.init.price_pln`), `listing_id`, `vehicle_name` (z `C.init.listing.title`). Import do Google Ads conversion actions czeka na Basic access developer_tokena (obecnie `test`). |
| 2026-04-21 | 0.31.0 | prod primaauto.com.pl | **SEO: ALT rotacja + GTM dataLayer + huby /samochody/marka/model/.** Importer dedupe NIE ruszany — „Trumpchi Trumpchi" i sub-brandy (Galaxy→Geely) idą w osobnej warstwie `translations-complectations.php`. (a) ALT-y: nowa `AsiaAuto_Media::buildAltText()` — rotacja 10 szablonów po `$n % 10` (zdjęcie N, używany z Chin, import Prima Auto, rynek chiński, zamów z Chin, …). Argument `$listing` przekazany do `downloadSingleImage`. Mark/model używane z termów jak są. (b) `AsiaAuto_Single::filterTitle()` — rotacja 10 szablonów SEO title po `inner_id % 10` (używane, import, zamów, gotowy do rejestracji, z rynku chińskiego, na zamówienie, sprowadź, import prosto z Chin, kup w 2026). (c) `AsiaAuto_Single::renderMeta()` — `dataLayer.push` z eventem `view_item` (ecommerce.items + listing.{inner_id,mark,model,year,fuel,drive,body,city,cif_usd}) przed metami OG/schema. (d) Taksonomia `make` slug zmieniony: `marka` → `samochody`. Rewrite rule `^samochody/([^/]+)/([^/]+)/?$` → query `make+serie` (`registerHubRewrites`). `filterHubQuery` dokleja `tax_query AND` dla obu taksonomii. Filter `term_link` dla `serie` generuje `/samochody/<primary_make>/<slug>/`. (e) Term meta `_asiaauto_primary_make_slug` na termach serie — wypełnia importer przy każdym imporcie (`updateSerieprimaryMake`) + batch `diag/backfill-serie-primary-make.php` (252 termy zbackfillowane). (f) Term admin UI rozszerzone o `asiaauto_wiki_body` (HTML) i `asiaauto_faq_json` obok istniejącego `asiaauto_seo_desc`. (g) Nowa klasa `AsiaAuto_Brand_Hub` — shortcody `[asiaauto_hub_wiki]`, `[asiaauto_hub_faq]` (+ Schema.org FAQPage), `[asiaauto_hub_listings]` (deleguje do `[asiaauto_inventory]` z inject kontekstu archive do `$_GET[marka,model]`). (h) `AsiaAuto_Redirects::redirectLegacyTaxonomy()` 301: `/marka/*` → `/samochody/*`, `/model/*` → `/samochody/<primary_make>/*`. (i) Batch `diag/update-listing-alts.php` zaktualizował 22 034 istniejące zdjęcia (1697 listingów, 33 bez galerii). `wp rewrite flush` zrobiony. Filtry inventory (`?marka=byd,nio` GET params) nietknięte. **Fix post-deploy:** (1) w `filterTitle` dodane `unset($parts['site'])` — szablony same zawierają „Prima Auto", WP bez tego doklejał blog name `– Prima-Auto` dając podwójne branding w `<title>`. (2) Szablony 0, 7 i 9 poprawione — „używane"→„Używane", „sprowadź"→„Sprowadź", „kup"→„Zamów Online" (wielka litera na początku zdania, CTA brzmi lepiej niż „kup"). (3) `filterTitle` i `renderMeta` — baza przełączona z klejenia `{make} {serie}` na `get_the_title($pid)`. Importer w linii 93-97 robi dedupe prefixu marki przy zapisie `post_title`, więc nie ma duplikatu „Denza Denza D9 DM". Term `serie` tego nie ma (zawiera pełny „Denza D9 DM"). Dotyczy 10 szablonów SEO title, OG title, meta description, dataLayer `item_name`. (4) ALT również — `buildAltText($post_id, $n, $total)` zamiast `$listing` array, base z post_title. Szablon ALT 1 „używany" → „Używany". Batch diag re-run: 22 034 zdjęcia z nowym czystym ALT. |
| 2026-04-21 | 0.30.16 | prod primaauto.com.pl | **Cutover na docelową domenę + rebrand user-facing.** (a) Kopia 8.9GB plików asiaauto.pl→primaauto.com.pl (rsync wykluczający `mcp-test/`, backupy SQL, logi testowe). (b) DB wspólna wp7j_ (siteurl/home zmienione search-replace: 106493 URL replacements) — asiaauto.pl od teraz daje 301 canonical przez WP. (c) `DISABLE_WP_CRON=true` w asiaauto/wp-config (cron pisał nowe uploads do złego katalogu → 404 obrazków, delta rsync dociągnął 417 plików). (d) MCP `asiaauto.pl/mcp-test/` usunięty (backup w ~/backups/). (e) Rebrand user-facing: 57 wystąpień `AsiaAuto`→`Prima-Auto` w pluginie (sed z regexem chroniącym klasy `AsiaAuto_*`), 17 subjectów mail, From Name, WhatsApp prefilled message ×3, theme `style.css` Theme Name, `blogname` option, regulamin. 222 klasy `AsiaAuto_*` nietknięte. (f) Email: `zamowienia@asiaauto.pl` + `samochody@asiaauto.pl` → `china@primaauto.com.pl` (plugin filter + DB 46 zamian). (g) **Reply-To fix** w `class-asiaauto-order.php:1443,1491,1518` — admin klikając Reply na powiadomienie pisze od razu do klienta (header `Reply-To: {customer_email}` w `wp_mail()`). (h) Plugin + theme zsynchronizowane 1:1 do legacy katalogu asiaauto.pl (rollback backup). Backupy: `2026-04-21-pre-cutover.sql.gz`, `2026-04-21-plugin-theme-pre-rebrand.tar.gz`, `2026-04-21-mcp-test-asiaauto.tar.gz`. |
| 2026-04-21 | 0.30.15 | prod asiaauto.pl | Fix zapisu w panelu Ustawienia zamówień: 7× `admin_url('tools.php')` → `admin_url('admin.php')` w `class-asiaauto-order-admin.php` (handleSaveConfig + handleSaveEmailTemplates + handleSaveStatusDisplay + 3× reset + zakładki w renderConfigPage). Zaległość po 0.30.11 gdy submenu przeszło spod `tools.php` pod parent `asiaauto-orders`, ale redirecty zostały stare → po zapisie leciał 403 „Brak uprawnień". Drugi numer kontaktowy w dziale Kontakt i w stopce: `company_phone_2` (`+48 721 730 512`) w `asiaauto_order_config`, pole edytowalne w panelu (Dane firmy), `[aa_phone slot="2"]` w `class-asiaauto-shortcodes.php`, inline obok głównego telefonu w `class-asiaauto-contact.php` (jeden wiersz, oba klikalne), nowy item w footer icon-list (post 93679) z dynamicznymi shortcodami `[aa_phone format="raw" slot="2"]` / `[aa_phone slot="2"]`. |
| 2026-04-20 | 0.30.14 | prod asiaauto.pl | Sesja 7: Prima Auto rebranding na stronie głównej — schema.org name="Prima Auto", hero sub bez "homologacja", USP rozszerzone 3→6 (grid 2×3 desktop, 9 nowych ikon SVG), tytuł "Dlaczego Prima Auto", nowa sekcja "W cenie" z 6 pozycjami pakietu CIF (język, głos, ładowarka 7kW, przejściówka, kluczyk, filtry), fuel header poprawiony "Rodzaj paliwa". Umowa PDF §6: "Przygotowanie auta do odbioru, tłumaczenie dokumentów, obsługa akcyzy, przegląd i ubezpieczenie na miesiąc" (zamiast "Homologacja, przygotowanie pojazdu, rejestracja"). Admin kursy: UI odwrócony na USD→CNY (np. 6.80) z automatyczną inwersją przy zapisie, storage nadal jako `kurs_cny_usd`, pipeline cenowy bez zmian. Inventory: nowy filtr `drive` (taxonomy Motors Pro, AWD/FWD/RWD, URL param `?naped=`, REST counters endpoint). Shortcody cenowe: `[asiaauto_price_netto]` (cena netto, format identyczny jak `[asiaauto_price]`), `[asiaauto_price_breakdown]` (brutto czerwony 24/30px + VAT + netto w kolumnie obok, flex 50%, breakpoint 768px), `[asiaauto_included]` (ul z 6 bulletami pakietu CIF). Elementor template single-listing (ID 101874): podmiana 2 widgetów html → icon-list via one-shot skrypt `scripts/replace-singleelementor-htmlwith-iconlist.php`, backup JSON w `/home/host476470/backups/primaauto/`. |
| 2026-04-20 | 0.30.13 | prod asiaauto.pl | Fix ghost-crona `asiaauto_daily_cleanup`: event był zaschedulowany ale brakował `add_action('asiaauto_daily_cleanup')` w bootstrapie (bliźniaczy bug do `asiaauto_sync_changes` naprawionego w 0.30.12 — przegapiony). Handler wywołuje `AsiaAuto_Rotation::cleanup()`. Jednorazowy run po wgraniu: 166 drafts→trash, 2077 trash→permanent delete (starsze niż 7d), 0 orphaned images. Stan trash: 5470 → 3559. Reszta (głównie 3141 z purge 04-17) wyleci naturalnie w kolejnych dniach. |
| 2026-04-17 | 0.30.12 | prod asiaauto.pl | Sesja 6: cron bug fixes — `cron_schedules` filter rejestruje `asiaauto_15min` (interval 900s); `add_action('asiaauto_sync_changes')` handler wywołuje `AsiaAuto_Sync::run('dongchedi')` (wcześniej event był ghostem bez handlera, sync leciał tylko przez CLI). ZADANIE 6 Krok D: purge 2905 listings (marki OR miasta poza listą), bulk-import-by-brand.php (reverse-order pages + MAX_PAGES=50 + probe [50,40,30,20,10,5,2,1], parametr `mark=X` nie `brand=`), fix-missing-images.php (45/45: 15 OK + 30 TRASH ghost-offers po expired CDN x-expires). UX fix: modal miast auto-fill 31 defaults gdy saved=[]. Net: publish 736→809 (+73), cron zweryfikowany działa. |
| 2026-04-17 | 0.30.11 | prod asiaauto.pl | Uprawnienia sprzedawcy: nowy cap `manage_asiaauto_import` (rola `primaauto` + admin), trzy strony admina wychodzą spod `tools.php`: Konfigurator importu → `Ogłoszenia → Konfigurator importu` (IMPORT_CAP), Dodaj z Dongchedi → nadal pod Ogłoszeniami (IMPORT_CAP, + 2× AJAX), Ustawienia zamówień → submenu pod głównym menu Zamówień (ORDER_CAP). Globalny `menu_order` filter: Kokpit → Zamówienia → Ogłoszenia → Strony → reszta. `grantAdminImportCap()` w activation hooku, one-shot uruchomiony przez `wp eval` na prod. |
| 2026-04-17 | 0.30.10 | prod asiaauto.pl | ZADANIE 6 Krok A+B: filtr miast (31 domyślnych z Guangdong/Fujian/Guangxi/Hainan, modal w panelu admina, dodawanie custom), filtr w `isAllowedByConfig`. Perf: transient cache na 3× COUNT postmeta (10min TTL, invalidate po bulk recalc). Trash TTL: 30d → 7d. |
| 2026-04-17 | 0.30.9 | prod asiaauto.pl | B2 SEO: meta/OG/title dla single listing + inventory, blok SEO marki/modelu, term meta `asiaauto_seo_desc`, min-price transient, `llms.txt`. |
| 2026-04-17 | 0.30.8 | prod asiaauto.pl | Załączniki PDF (akcyza 0% widoczna), token w nazwie PDF, UPLOAD_DIR→contracts, nr umowy w tytule przelewu, info o podpisach w wizardzie. Bootstrap odtworzony po uszkodzeniu sed. |
| 2026-04-16 | 0.30.7 | prod asiaauto.pl | Sesja 2: CIF fix, panel klienta, version bump. ZADANIE 5 core DONE. |
| 2026-04-15 | 0.29.0-wip | prod asiaauto.pl | Bootstrap repo primaauto. PHP lint clean (PHP 8.3). Pending: v0.30.6 (3 patche, nie wgrane). |

## 0.34.15 — 2026-07-31 (a11y: struktura nagłówków oferty + landmark listingu)

Realizacja punktów z `docs/audyty/2026-07-31-dostepnosc-wcag22-aa.md`.

- `class-asiaauto-single.php:564` — kolumny paska USP („W cenie", „Dlaczego my", „Informacje") `h3` → `h2`
- `class-asiaauto-single.php:471` (`infoBox()`) — nagłówek „Informacje" w boksie sidebara `h3` → `h2`
- `class-asiaauto-inventory.php:193` — `<main class="aa-inv__main">` → `<div>`; szablon renderuje się wewnątrz `<main class="pa-main">` z `header.php`, więc były DWA landmarki „główna treść" (axe: `landmark-no-duplicate-main`, `landmark-unique`, `landmark-main-is-top-level`)
- `class-asiaauto-shortcodes.php:2329` — `h2.aa-404__title` → `h1` (strona 404 startowała hierarchię od `h2`)
- `class-asiaauto-homepage.php` — `<label class="aa-sr-only">` dla `#aa-home-make` i `#aa-home-model`
- `class-asiaauto-inventory.php` — `aria-label` na `.aa-sort__select`, obu `.aa-price-inputs__input` i `.aa-search__input` (miały wyłącznie `placeholder`)
- `class-asiaauto-contact.php` — `<th scope="row">` + `<caption class="aa-sr-only">` w tabelach godzin i danych firmy; do `.aa-contact__hours-day` i `.aa-contact__data-label` dołożone `font-weight:400; text-align:left`, żeby domyślne style `th` nie zmieniły wyglądu

**Dlaczego nagłówki oferty to dwie linie, nie jedna:** karta auta ma dwa układy DOM. Na mobile sticky-head z `<h1>` jest na górze (skok `h1 → h3` na kolumnach USP — to zgłaszało PSI), na desktopie sidebar jest w DOM po kolumnie treści, więc tytuł poziomu 1 wypada na 13. pozycji, a skok przenosi się na `h3.aa-info__title` (to zgłaszał axe). Obie zmiany siedzą w kodzie współdzielonym, więc działają na obu szerokościach.

**Nie naprawione świadomie:** na desktopie nagłówek poziomu 1 nadal jest w kolejności czytania po całej treści (sidebar za kolumną główną w DOM). Wymaga przestawienia kolejności i utrzymania układu przez `order` w gridzie — osobna decyzja.

Wyniki PSI: oferta 89 → 93, listing bez zmiany punktowej (reguły landmarków nie są punktowane przez Lighthouse), axe potwierdza usunięcie trzech reguł.

Backupy: `class-asiaauto-single.php.bak-2026-07-31-a11y-headings`, `class-asiaauto-inventory.php.bak-2026-07-31-a11y-main`, `*.bak-2026-07-31-a11y`.

## 2026-09-04 — naprawa 4 zepsutych hubów (recheck SEO)

Znalezione w `docs/seo/recheck-2026-09-04.md` (skan 275 hubów z ofertami).
Skrypt: `scripts/fix-huby-zepsute-2026-09-04.php` (symulacja domyślnie, `apply` zapisuje).
Backup taksonomii: `~/backups/primaauto/2026-09-04-huby-fix/taksonomia-przed.sql` (9,3 MB).

| Problem | Naprawa |
|---|---|
| `seal-07-ev-2` — duplikat Sealion 7 (to samo auto, 海狮07EV) | oferta 455246 → `sealion-7`; skasowane termy 7236 i 7172 |
| `king-kong-cannon` — pusty `_asiaauto_primary_make_slug`, URL `/samochody//king-kong-cannon/` | oferta 447190 → `cannon-king-kong` (zgodnie z brand-mappingiem, make=GWM); skasowany term 6763 |
| `v9-2` „智界V9" — marka CJK zamiast Luxeed | oferta 447607 → `v9` + make `luxeed`; skasowane termy 7240 i marka 7239 |
| `avatr-07l` — jedyny hub z ofertami bez `rank_math_title` | `_serie_full_title` = „Avatr 07L", title przez `AsiaAuto_HubTitleGenerator::regenerateForTerm()` |

**Przekierowania** (`class-asiaauto-redirects.php`, backup `.bak-2026-09-04`):
`byd/seal-07-ev` i `byd/seal-07-ev-2` → `byd/sealion-7` (stary URL miał 32 imp / 2 kliki w 90 dniach),
`luxeed/v9-2` → `luxeed/v9`, marka `智界` → `luxeed` (**oba warianty zapisu** — `$wp->request`
trzyma ścieżkę **URL-zakodowaną wielkimi literami**, wariant zdekodowany nie łapie).

Weryfikacja: wszystkie 4 huby HTTP 200, 3 stare URL-e 301 na właściwy cel.
`/samochody//king-kong-cannon/` zostaje 404 — świadomie, 0 impresji w GSC.
Zgłoszone do Indexing API (4/100 budżetu ad-hoc).

**Znalezisko poboczne, NIE naprawione:** `AsiaAuto_HubTitleGenerator::buildDescription()` generuje
błędną fleksję — „1 egzemplarzy" (**181 hubów**) i „2–4 egzemplarzy" (**80 hubów**).
Poprawnie: „1 egzemplarz", „2 egzemplarze". Widoczne w meta description w SERP. Do decyzji.
