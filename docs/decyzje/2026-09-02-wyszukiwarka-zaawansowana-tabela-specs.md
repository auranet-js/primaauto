# 2026-09-02 — Wyszukiwarka zaawansowana: spłaszczona tabela `specs` obok `extra_prep` (T-116 etap 3)

> **Status:** wdrożone (v0.35.0), `/wyszukiwarka/` na produkcji, nigdzie nie podlinkowana
> **Poprzednik:** `docs/roadmapa/T-116-etap3-wyszukiwarka.md` (prompt wykonawczy),
> `docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md` (spec danych),
> `docs/decyzje/2026-09-02-katalog-autohome-trzecie-ogniwo-nocne.md` (skąd dane)
> **Następnik:** odbiór Janka + tydzień obserwacji, potem osobna decyzja o linkowaniu

## Problem

Po etapie 2 dane są (flagi wyposażenia na 98,2% ofert publish), ale siedzą w JSON-ie
`_asiaauto_extra_prep` o medianie 283 kluczy. Filtrowanie po blobie to `meta_query` po
3,4 tys. serializowanych wartości — przy 5 filtrach zapytania sekundowe, a wartości
nie są jednorodne (9 typów rozjazdu ze spec danych). Konkurencja filtruje po wyposażeniu,
my po 7 taksonomiach i cenie.

## Decyzja

1. **Spłaszczyć do `wp7j_asiaauto_specs`** — jeden wiersz na ofertę, kolumny typowane,
   normalizacja w momencie spłaszczania. `extra_prep` pozostaje źródłem prawdy; tabela
   jest odtwarzalna w całości jednym poleceniem (`zbuduj-specs.php apply`, 5 s na 3 396 ofert).
2. **Wszystko obok, nic w środku.** Nowe klasy `AsiaAuto_Specs_Table` i `AsiaAuto_Search`.
   `class-asiaauto-inventory.php` (1 815 linii) i `/samochody/` bez jednej zmiany — dowód:
   mtime pliku 2026-08-25, sprzed sesji. Jedyny styk to `AsiaAuto_Inventory::renderCard()`,
   publiczna od dawna, więc nawet wrapper z promptu okazał się zbędny.
3. **Osobna strona bez linkowania.** `/wyszukiwarka/`, publiczna, w indeksie i sitemapie,
   ale zero linków wewnętrznych do czasu odbioru. Podpięcie do nawigacji, CTA na `/wiki/`
   i ewentualna podmiana `/samochody/` to osobna decyzja.
4. **Klasa nazwana `AsiaAuto_Specs_Table`**, wbrew prompcie (`AsiaAuto_Specs`) — obok żyje
   `AsiaAuto_Spec`, generator tabeli technicznej huba. Różnica jednej litery myliłaby przy grepie.

## Ustalenia, które zmieniły plan

1. **Pusta wartość klucza znaczy NIE.** Spec danych wymieniał zbiór negatywny
   `{选配, 选装, -, 0, 不支持, 无}`, ale nie pustkę. Pomiar: `air_suspension` ma klucz
   w 1 653 ofertach — 804 puste, 41 `选配`, 808 `标配`. Bez odrzucenia pustych flaga
   pokazywałaby 55,5% zamiast 27,1%, a spec danych mówi 28%. Ta sama zgodność wyszła
   na `header_display_system`, `heat_pump`, `sentinel_mode`, `lane_center`. Reguła bez
   tego byłaby zawyżona o 10–27 punktów na pięciu flagach.
2. **`定速巡航` to tempomat ZWYKŁY.** Flaga „tempomat adaptacyjny" nie może opierać się
   na obecności klucza `cruise_system` — 111 ofert ma tam zwykły tempomat. Stąd warunek
   na wartości (`自适应`) obok koalescencji trzech kluczy. Realne pokrycie unii to 92,8%,
   nie 80% jak szacował spec (który podawał liczbę dla jednego klucza).
3. **Bramka porównawcza wymaga marki**, a marki nie było w projekcie tabeli. Dołożone
   kolumny `make`, `serie`, `color` — bez nich nowa wyszukiwarka nie miałaby filtra marki,
   czyli nie byłoby czego pokazać ani z czym porównać.
4. **Pierwszy przebieg bramki był bezwartościowy.** Losowanie kombinacji jednostajne po
   slugach dało 35 z 50 kombinacji z zerem wyników (rzadkie marki). Losowanie ważone
   `sqrt(count)` daje kombinacje z realnymi wynikami — dopiero to jest test.
5. **`_asiaauto_reservation_status` ma 1,5% pokrycia, nie 100%** jak twierdził prompt.
   46 ofert publish. Nie wpływa na etap (pole nie jest w zestawie), ale zapis był nieprawdziwy.

## Bramki

200 kombinacji filtrów podstawowych (4 seedy × 50) — **0 rozjazdów** wobec `/listings`.
`search` 64–71 ms przy 6 filtrach, `search-counts` 49–52 ms z cache (106 ms bez).
axe WCAG A/AA/2.1/2.2 AA przy 320 px i 1366 px — **0 naruszeń**, zero reflow, zero błędów JS.
Deep-link odtwarza stan i po stronie SSR, i po odświeżeniu w przeglądarce.
30 realnych `extra_prep` (10 che168 / 10 dongchedi / 10 ręcznych) zweryfikowanych ręcznie.

## Konsekwencje

- **Tabela musi być odświeżana, inaczej kłamie.** Trzy ścieżki: hook
  `asiaauto_after_set_taxonomies` (import), `transition_post_status` (draft/trash),
  cron 05:05 `since=48h` (oferty wzbogacone nocą). Trash usuwa wiersz, przywrócenie go odtwarza.
- **Nocna sekwencja ma teraz cztery ogniwa:** bliźniak 04:35 → bank 04:45 → katalog Autohome
  04:55 → specs 05:05. Kolejność nie jest przypadkowa: specs muszą iść po wszystkim,
  co dokłada pola do `extra_prep`.
- **Filtr mocy kłamie na 101 ofertach PHEV/EREV** (66 bez stempla źródła): `_asiaauto_horse_power`
  trzyma tam moc silnika, nie układu — AITO M9 EREV 152 KM zamiast ~490, Leapmotor D19 zero.
  Prompt zakazywał obniżania istniejących wartości, więc podniesienie do mocy układu przez
  `km_from_power()` zostaje jako osobna decyzja.
- Zmiana słownika enumów lub reguł flag wymaga przebiegu `zbuduj-specs.php apply` na całości
  — tabela nie przelicza się sama wstecz.
- Karta oferty jest wspólna z `/samochody/`, więc grid MUSI nosić klasę `aa-inv`
  (15 reguł CSS stylizuje wnętrze karty przez `.aa-inv .aa-card__…`).

## Pliki

- plugin: `includes/class-asiaauto-specs-table.php`, `includes/class-asiaauto-search.php`,
  `assets/css/asiaauto-search.css`, `assets/js/asiaauto-search.js`, `asiaauto-sync.php` (0.35.0)
- repo: `scripts/zbuduj-specs.php`, `scripts/porownaj-search.php`, kopie klas w `plugins/asiaauto-sync/`
- strona: post 459262 (`/wyszukiwarka/`), treść to sam shortcode `[asiaauto_search]`
- backupy: `~/backups/primaauto/2026-09-02-t116e3/wp521-przed-specs.sql.gz` (przed `CREATE TABLE`),
  `asiaauto-sync.php.bak-2026-09-02-t116e3`, `~/backups/crontab/crontab-2026-09-02-154640.bak`
- log crona: `~/.claude/zbuduj-specs.log`

---

## Dopisek 2026-09-02 wieczorem — co wyszło z przeglądu w przeglądarce (0.35.1)

Przegląd `/wyszukiwarka/` w Chrome, po wdrożeniu 0.35.0 z kompletem zielonych bramek.
Wyszło pięć usterek UI i jedna luka w odświeżaniu danych. Szczegóły: `docs/VERSIONS.md` 0.35.1.

**Wniosek metodyczny, ważniejszy niż same usterki:** testy sprawdzały, czy **liczby się zgadzają**
(total, liczniki, pokrycia, czas, axe). Nie sprawdzały, czy **panel zachowuje się sensownie**:
widoczności opcji, przycisku „Wyczyść", paginacji, sortowania. Cztery z pięciu usterek leżały
dokładnie w tej luce, a piąta (spacja tysięcy) była niewidoczna, bo test porównywał wartość
samą ze sobą po obu stronach.

**Luka architektoniczna, warta zapamiętania przy każdej kolejnej kolumnie tabeli:**
zapis do `postmeta` przez `update_post_meta` **nie odpala** `transition_post_status` ani
`asiaauto_after_set_taxonomies`. Pipeline cenowy robi dokładnie to — 14 ofert miało w tabeli
starą cenę. Każda kolumna `specs` brana wprost z meta potrzebuje albo hooka na `updated_post_meta`,
albo pozycji w siatce bezpieczeństwa `idsToRebuild()`. Dziś objęte: `price`, `mileage`,
`_asiaauto_horse_power`.

---

## Dopisek 2026-09-02 wieczorem — ile realnie waży „rodzaj oferty" (pomiar GA4)

Przy projektowaniu paska poziomego rodzaj oferty dostał najwięcej miejsca na stronie
z uzasadnieniem „to najważniejsza informacja handlowa". **To była hipoteza z modelu
biznesowego, nie wniosek z pomiaru** — czyli dokładnie to, czego zakazuje nasza własna
zasada „decyzje wymagają data check". Janek to zakwestionował, więc zmierzone (GA4,
property 534017542, 90 dni do 2026-09-01):

| strona | sesje | wejścia z zewnątrz (landing) |
|---|---:|---:|
| `/samochody/` | 8 158 | — |
| `/w-rzeszowie/` | 2 690 | 423 |
| `/w-drodze/` | 2 083 | 218 |

**Wnioski, które zmieniają obraz:**

1. Strony dostępności zbierają **21% ruchu katalogu**, pokazując **1,5% oferty**
   (45 aut z 2 967). Zainteresowanie jest nieproporcjonalne do udziału w stanie
   magazynowym, więc wyróżnienie tego wymiaru ma pokrycie w zachowaniu użytkowników.
2. Ponad **4 000 sesji dociera tam z wnętrza serwisu** (2 690 + 2 083 sesji przy
   641 wejściach z zewnątrz). „Dostępne od ręki" i „W drodze" to pozycje menu głównego
   i ludzie z nich korzystają. To nie jest filtr niszowy — to jedna z głównych osi
   nawigacji po ofercie.
3. **Forma była mimo to przeskalowana.** Waga uzasadnia wyróżnienie, nie uzasadnia
   25% wysokości ekranu. Segmentowany przełącznik w jednej linii (48 px, warianty A/B)
   oddaje tę samą hierarchię.

**Pytanie otwarte, ważniejsze niż rozmiar kontrolki:** skoro `/w-rzeszowie/` i `/w-drodze/`
mają własny ruch organiczny, własne SEO i miejsce w menu, to filtr w wyszukiwarce ich
**nie zastępuje** — jest trzecią drogą do tych samych 45 aut. Do rozstrzygnięcia przy
decyzji o linkowaniu (krok 7 promptu etapu 3): czy wyszukiwarka ma dublować to, co już
działa jako osobne strony, czy te strony docelowo mają być deep-linkami do niej.
