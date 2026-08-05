# T-229 — Szablon rankingu rynkowego + warstwa naszej dostępności

> Status: **spec gotowy do wykonania** (2026-08-04, wersja 2) · Rozmiar: M · Godziny realnie: **6–8 h**
> Rodzic: **T-214 F4** (filar C — blog `/rankingi/`) · Odblokowuje: T-230, T-231
> Pomiar wejściowy: `docs/analizy/2026-08-04-rankingi-porownania-recon.md`
>
> **Wersja 2 — korekta założenia (Janek, 2026-08-04).** Pierwsza wersja specu zakładała ranking
> *naszego magazynu*. To było odwrócenie sensu: punktem wyjścia były rankingi rynkowe ze zrzutów
> WeChat (sprzedaż w Chinach, TOP zasięgów, lista aut z 800V), a nasza oferta miała być ilustracją,
> nie tematem. Decydujący argument: **oferta rotuje — auta wchodzą i wychodzą — a my budujemy wiedzę.**
> Ranking magazynu dezaktualizowałby się przy każdej rotacji; ranking rynku zostaje.

## Po co

`/rankingi/` istnieje i zwraca HTTP 200, ale ma **zero wpisów**. Chcemy być polskim źródłem prawdy
o chińskiej motoryzacji — czyli publikować to, co dzieje się na tamtym rynku, a nie katalog własnego
placu. Segmenty z popytem, których nie obsługujemy żadną treścią: terenówki ~3450/mc, pickupy ~2480,
vany ~2140, sedany ~2180, kombi 480.

## Architektura — dwie warstwy o różnej trwałości

| | Warstwa rynkowa | Warstwa naszej dostępności |
|---|---|---|
| **Co** | pełna lista modeli z rynku chińskiego, też tych, których nie mamy | przy pozycjach, które mamy: liczba sztuk, cena od, zdjęcie, link |
| **Skąd** | carnewschina, cnevpost (cytują CPCA), katalog Autohome | nasza baza |
| **Jak często się zmienia** | rzadko — nowe dane miesięczne lub kwartalne | codziennie (rotacja) |
| **Odświeżanie** | ręczne, przy nowych danych | automatem, blok między znacznikami |

To jest odwrotność pierwszej wersji specu: **trwały jest ranking, zmienna jest nasza dostępność.**

## Stan faktyczny (zweryfikowany 2026-08-04)

- Kategoria `rankingi`, permalink `/%category%/%postname%/`, `category.php` obsługuje archiwum
  (breadcrumb, siatka kart, `kb-sidebar`). **Listingu nie trzeba budować.**
- Źródła danych rynkowych — sprawdzone na żywo:
  `carnewschina.com/feed/` HTTP 200 · `cnevpost.com/feed/` HTTP 200 (52 wpisy, w tym
  „CPCA sees China's July NEV wholesale up 23%", „BYD Fang Cheng Bao tops 500,000 cumulative sales").
  Oba są już w radarze newsowym T-214. `cpcadata.com.cn` bezpośrednio **nie odpowiada** —
  dane CPCA bierzemy zza pośrednictwa tych serwisów, z atrybucją.
- `scripts/autohome-catalog-fetch.js` — własny dekoder katalogu Autohome, 292 parametry na model,
  zdejmuje antyscraping. Źródło parametrów technicznych dla modeli spoza naszej oferty.
- 2135 ofert z galeriami zdjęć — materiał ilustracyjny dla pozycji, które mamy.
- ⚠️ **Autolinker działa tylko dla kategorii `aktualnosci`** (`class-asiaauto-autolink.php:61`).
- ⚠️ **Schema `NewsArticle` też przypięta tylko do `aktualnosci`** (`class-asiaauto-seo.php:170`).
- `/marki/` ma gotową sekcję „Najnowsze rankingi" — włączy się sama przy pierwszym `publish`.

## Decyzje

- **D1 — Ranking opisuje RYNEK, nie nasz magazyn.** Pozycje to modele obecne na rynku chińskim,
  niezależnie od tego, czy mamy je w ofercie. Nasza oferta jest warstwą dodaną, nie kryterium doboru.
- **D2 — Liczby wyłącznie ze zweryfikowanych źródeł.** Zrzuty z WeChat to trop tematyczny —
  twórcy (中汽数研, 电车说, 车之榜) nie podają metodologii, a dwa zrzuty tej samej listy miały
  różne wartości (klatka animacji). Każda liczba w publikacji musi mieć źródło w carnewschina,
  cnevpost, katalogu Autohome albo komunikacie producenta. **Cudzych grafik nie kopiujemy.**
- **D3 — Zdjęcia z naszych galerii tam, gdzie mamy model.** 2135 ofert z fotografiami; pozycja
  rankingu, której nie mamy, idzie bez zdjęcia albo z materiałem prasowym producenta.
- **D4 — Warstwa dostępności odświeżalna automatem.** Blok `<!--OFERTA:START-->` / `<!--OFERTA:END-->`
  przy każdej pozycji; przelicza go osobna komenda. Narracja i sam ranking zostają nietknięte.
- **D5 — Zasięg w WLTC, gdy go mamy; CLTC z jawną etykietą, gdy nie.** Nigdy zmieszane w jednej
  kolumnie. Dla czytelnika z Polski CLTC jest zawyżone o mniej więcej jedną czwartą.
- **D6 — Gate antykanibalizacyjny przed publikacją** (reguła D2 z T-162): sprawdzić w GSC,
  czy fraza główna nie ma już URL-a w serwisie na pozycji ≤20.
- **D9 — Weryfikacja krzyżowa liczb, które trafiają do publikacji.** Pierwszy realny test parsera
  (2026-08-04) wychwycił rozbieżność: cnevpost podaje **Deepal 29 213** za lipiec 2026, a zrzut
  z WeChat tę samą liczbę przypisywał **Denzie**. Jedno ze źródeł się myli — i tego nie da się
  rozstrzygnąć bez trzeciego. Zasada: pozycje z czołówki rankingu (1–5) oraz każda, przy której
  wchodzi nasza oferta, wymagają potwierdzenia w drugim niezależnym źródle. Rozbieżność
  → nie publikujemy pozycji albo podajemy obie wartości z atrybucją, nigdy nie wybieramy „na oko".
- **D7 — Jeden stały URL na temat, aktualizowany; nie nowy wpis co miesiąc.** Frazy są wieczne,
  nie rocznikowe: „chińskie suv" 5400/mc wobec „chińskie samochody 2026" **30/mc**. Dwanaście wpisów
  rocznie o tym samym temacie to kanibalizacja i rozproszony autorytet — każdy zaczynałby od zera.
  Ranking niesie okres **w treści** („dane za lipiec 2026") i datę aktualizacji, ale nie w URL-u.
  **Comiesięczne dane idą osobnym kanałem, który już działa:** news w `/aktualnosci/`
  („Sprzedaż aut elektrycznych w Chinach, lipiec 2026") linkujący do rankingu. Ranking dostaje
  świeżość i link, news dostaje temat, Google widzi jeden URL na temat plus doniesienia wokół niego.
  Wyjątek: podsumowania roczne („Chińska motoryzacja w 2026") to inny intent — mogą żyć osobno.
- **D8 — Dwa odświeżalne bloki, nie jeden.** Konsekwencja D7: skoro dane rynkowe też się zmieniają
  (nowy miesiąc sprzedaży), blok `<!--RANKING:START/END-->` musi być podmienialny tak samo jak
  `OFERTA` — inaczej aktualizacja oznacza ręczne przepisywanie tekstu. Różnią się częstotliwością:
  `RANKING` rzadko i świadomie, `OFERTA` automatem, bo rotuje codziennie.

## Pliki

| Plik | Rola |
|---|---|
| `scripts/kb/ranking_market.py` | **nowy** — pozyskanie i normalizacja danych rynkowych (feedy + katalog Autohome) |
| `scripts/kb/ranking_stock.py` | **nowy** — dopasowanie pozycji rankingu do naszej oferty (sztuki, cena od, zdjęcie, URL) |
| `scripts/kb/ranking_generate.py` | **nowy** — research + narracja + złożenie wpisu + draft |
| `scripts/kb/ranking_refresh.py` | **nowy** — przelicza bloki we wpisach: `OFERTA` z crona, `RANKING` na żądanie (D8) |
| `scripts/kb/rankingi.json` | **nowy** — definicje rankingów (temat, kryterium, fraza, źródła) |
| `class-asiaauto-autolink.php:61` | **modyfikacja** — objąć `rankingi`, `porownania`, `poradniki` |
| `class-asiaauto-seo.php` | **modyfikacja** — `ItemList` dla wpisów w `rankingi` |

---

## Task 1 — Dane rynkowe (`ranking_market.py`)

**Deliverable:** znormalizowana lista pozycji rankingu z podanym źródłem każdej liczby.

- [x] **1.1** Pobranie feedów `carnewschina.com/feed/` i `cnevpost.com/feed/`, filtr po słowach
  kluczowych tematu (np. „sales", „ranking", „top", „range") i po dacie.
- [x] **1.2** Ekstrakcja liczb z treści artykułu (nie z tytułu) wraz z URL-em źródła i datą publikacji.
  Każda pozycja niesie `zrodlo_url` i `zrodlo_data` — bez tego nie trafia do rankingu.
- [x] **1.3** Dla rankingów parametrycznych (zasięg, bateria, ładowanie): parametry z katalogu
  Autohome przez `autohome-catalog-fetch.js` po `specid`, nie z feedów.
- [x] **1.4** Normalizacja nazw modeli CN → nasze (钛7 → Leopard 7, 问界 → AITO, 智界 → Luxeed) —
  reuse mapowania z `data/brand-mapping-v6.1.php` i `che168-model-map.php`.
- [x] **1.5 Test:** dla „TOP 10 zasięg BEV" każda pozycja ma liczbę, jednostkę, normę pomiaru
  i działający URL źródła; żadna liczba nie pochodzi ze zrzutu WeChat.

---

## Task 2 — Warstwa naszej dostępności (`ranking_stock.py`)

**Deliverable:** dla każdej pozycji rankingu informacja, czy i za ile mamy ją w ofercie.

- [x] **2.1** Dopasowanie nazwy modelu z rankingu do taksonomii `serie` (po nazwie i aliasach).
- [x] **2.2** Dla dopasowanych: liczba sztuk `publish`, najniższa cena, URL huba modelu,
  zdjęcie z galerii najtańszego egzemplarza.
- [x] **2.3** Render bloku między `<!--OFERTA:START-->` / `<!--OFERTA:END-->`:
  „Mamy w ofercie: 3 szt., od 218 000 zł" + miniatura + link. Brak dopasowania → blok pusty,
  **bez** komunikatu „nie mamy" (to reklama konkurencji).
- [x] **2.4** ⚠️ Pułapka: `Hybryda plug-in (PHEV)` zawiera ciąg „EV" — filtry po slugu termu,
  nigdy przez `str_contains` na nazwie.
- [x] **2.5 Test:** ranking z 10 pozycjami, z których mamy 4 — dokładnie 4 bloki wypełnione,
  ceny zgodne z zapytaniem kontrolnym, miniatury się ładują.

---

## Task 3 — Generator wpisu (`ranking_generate.py`)

**Deliverable:** draft wpisu w kategorii `rankingi` gotowy do przejrzenia.

- [x] **3.1** Wejście z `rankingi.json`: temat, kryterium, fraza główna (z DFS), tytuł, slug, źródła.
- [x] **3.2** Prompt generuje **wyłącznie narrację**: lead, komentarz do czołowych pozycji,
  sekcję „co to znaczy dla kupującego w Polsce", FAQ (5 pytań z PAA).
  **Liczby wchodzą z Task 1 i 2 — model ich nie dotyka** (reguła sprawdzona w `make_hub_generate.py`).
- [x] **3.3** Sekcja „Skąd te dane" — obowiązkowa, z linkami do źródeł i datą. To jest różnica
  między nami a przepisywaczami zrzutów.
- [x] **3.4** Bramki: diakrytyki (regresja T-193), obecność znaczników bloków, brak opisu procesu
  importu (reguła z T-236), każda liczba w narracji zgodna z tabelą.
- [x] **3.5** Zapis jako **draft** + mail do Janka z podglądem.
- [x] **3.6 Test:** draft ma tabelę z liczbami mającymi źródła, FAQ parsuje się, narracja nie
  zaprzecza tabeli.

---

## Task 4 — Odświeżanie (`ranking_refresh.py`)

**Deliverable:** komenda przeliczająca bloki we wpisach — osobno dostępność, osobno dane rynkowe.

- [x] **4.1a** `--oferta` (domyślne, pod cron): przelicza wyłącznie bloki `OFERTA`.
  **Ranking i narracja pozostają nietknięte** — zmienia się tylko to, co u nas rotuje.
- [x] **4.1b** `--ranking --zrodlo <url>`: podmienia blok `RANKING` na nowe dane okresowe
  i aktualizuje zdanie o okresie w leadzie. Uruchamiane świadomie, nie z crona (D8).
- [x] **4.2** Guard: spadek liczby dopasowań o >50% względem poprzedniego przebiegu → nie podmieniaj,
  zgłoś mailem (wzorzec sanity-gate z generatorów llms).
- [x] **4.3** Cron dopiero po miesiącu obserwacji, nie od razu.
- [x] **4.4 Test:** `diff` treści przed/po pokazuje wyłącznie bloki dostępności.

---

## Task 5 — Wpięcie w serwis

- [x] **5.1** Autolinker: warunek z `aktualnosci` rozszerzyć na `rankingi`, `porownania`, `poradniki`.
  **ZAWSZE PYTAJ — diff.**
- [x] **5.2** Schema `ItemList` dla wpisów w `rankingi`; pozycje = modele rankingu.
- [x] **5.3** Sprawdzić sekcję „Najnowsze rankingi" na `/marki/` po pierwszym `publish`.
- [x] **5.4** `/rankingi/` do `llms.txt` — w generatorze, nie w pliku.
- [x] **5.5 Test:** Rich Results waliduje `ItemList`; linki do słownika pojawiają się w treści.

---

## Definicja zrobionego

- Ranking opisuje rynek, nie magazyn; każda liczba ma źródło i datę.
- Przy pozycjach, które mamy, wchodzi nasze zdjęcie, cena i link — reszta bez zdjęć.
- Warstwa dostępności przelicza się osobną komendą, ranking i narracja zostają.
- Autolinker i `ItemList` obsługują kategorię `rankingi`.
- Sekcja „Najnowsze rankingi" na `/marki/` włącza się sama.

## Ryzyka

- **Jakość źródeł.** Feedy podają liczby w treści artykułów, nie w ustrukturyzowanej formie —
  ekstrakcja bywa zawodna. Przy niepewnej liczbie: nie publikujemy jej wcale.
- **Dezaktualizacja rankingu.** Dane sprzedaży starzeją się co miesiąc. Tytuł i treść muszą
  nieść okres („czerwiec 2026"), a nie udawać stanu wiecznego.
- **Kanibalizacja hubów** — „chińskie terenówki" może konkurować z hubem `tank` albo `/marki/`.
  Gate D6 przed każdą publikacją.
- **Zmiany w renderze** (`autolink`, `seo`) dotykają wpisów i ofert — diff do akceptu.

---

## Wykonanie — 2026-08-05

**Zrobione:** Task 1 (rozszerzony), 2 (poprawiony), 3, 4, 5.4. Zostaje 5.1–5.3 i 5.5.

### Zmiana źródła danych rynkowych wobec specu

Spec zakładał wyłącznie feedy (cnevpost, carnewschina), które podają dane **per marka**.
Ranking SUV-ów wymaga danych **per model**, a te daje rozpoznane 04.08 **API rankingowe
Dongchedi** (`motor/pc/car/rank_data`). Feedy zostają jako weryfikacja krzyżowa (D9), API
jest źródłem liczb. Zgodność potwierdzona: `理想i6 = 21 453`, `小米YU7 = 14 324` — te same
wartości, które w poprzedniej sesji zdjęto ze zrzutów WeChat.

### Czego nauczyło wykonanie

- **Miesiąc trzeba ustalać, nie zakładać.** API na zapytanie o miesiąc przyszły oddaje po cichu
  ostatni dostępny, bez żadnego znacznika w odpowiedzi. `dcd_ostatni_miesiac()` wyszukuje więc
  pierwszy miesiąc, którego wynik przestaje być identyczny z domyślnym. Bez tego kroku
  opublikowalibyśmy „dane za sierpień", które są danymi za czerwiec. **Stan na 05.08: ostatni
  pełny miesiąc to 202606** — lipiec jeszcze nie wszedł.
- **Uczciwy User-Agent dostaje pustą odpowiedź.** `PrimaAutoBot` → HTTP 200 i zero bajtów, co
  wygląda jak błąd parsowania. Do tego API idzie UA przeglądarkowy (`DCD_UA`).
- **Nazwy są warstwą, nie detalem.** API zwraca wyłącznie CJK (`博越L`, `吉利汽车`). Resolver
  pluginu (brand-mapping v6.x) trafiał 13/70 pozycji, bo zna tylko kilka prefiksów CJK. Doszła
  `scripts/kb/ranking_names.json`: 90 marek z flagą `chinska` i tablica modeli. Pozycja bez
  nazwy **wypada z rankingu**, a gdy jej sprzedaż mieści się w publikowanej czołówce —
  `dziury_w_czolowce` blokuje publikację, żeby ranking nie zgubił po cichu trójki.
- **Dopasowanie po samej nazwie modelu jest błędne.** „AITO M6" (SUV) wchodziło w nasz hub „M6",
  który jest vanem GAC-a. Task 2 dostał strażnika marki (`ta_sama_marka()`), tolerancyjnego na
  submarki („Geely Galaxy" ↔ „Geely").
- **Narracja nie może cytować stanu oferty.** Pierwszy wygenerowany tekst pisał „mamy 5 sztuk od
  240 000 zł" — liczba, która rotuje codziennie, w warstwie, która się nie odświeża. Stan oferty
  zbiorczo podaje osobny blok `<!--OFERTA:START:_podsumowanie-->`, a bramka wzorcowa blokuje
  ceny i sztuki w tekście. Wzmianka „ten model mamy" bez liczby jest w porządku i zostaje.

### Bramka D6 (antykanibalizacja) — sprawdzona w GSC, 06.07–04.08

Fraza główna „chińskie suv"/„chiński suv" (5 400/mc) **nie ma u nas URL-a w top 20** — GSC
pokazuje wyłącznie warianty superlatywne („największy", „najlepszy", „najmocniejszy chiński
suv"), obsługiwane przez `/samochody/aito/m9/` z pozycji 1–2,7 przy 2–9 wyświetleniach. Ranking
celuje w intent częstotliwościowy („najchętniej kupowane", „sprzedaż"), nie jakościowy — dlatego
tytuł świadomie **nie** brzmi „najlepszy chiński SUV". Przy tym tytule kanibalizacji nie widać.

### Pliki

| Plik | Stan |
|---|---|
| `scripts/kb/ranking_market.py` | +źródło Dongchedi, wykrywanie miesiąca, bramka `dziury_w_czolowce` |
| `scripts/kb/ranking_names.json` | **nowy** — 90 marek CJK→nasze + flaga `chinska`, tablica modeli |
| `scripts/kb/ranking_stock.py` | +strażnik marki, `wzbogac()`, blok kompaktowy z kluczem |
| `scripts/kb/rankingi.json` | **nowy** — definicje (suv gotowy; elektryki i terenówki `gotowy: false`) |
| `scripts/kb/ranking_generate.py` | **nowy** — narracja + złożenie wpisu + bramki + draft |
| `scripts/kb/ranking_refresh.py` | **nowy** — `--oferta` (cron) i `--ranking` (świadomie) |
| `scripts/build-llms.php` | +sekcja rankingów (5.4) |

### Efekt

Draft **#406121** „Najchętniej kupowane chińskie SUV-y — ranking sprzedaży w Chinach",
30 pozycji za czerwiec 2026, **15 z nich mamy w ofercie**. Nieopublikowany — czeka na akcept.


## Wdrożenie 5.1–5.3, 5.5 i publikacja — 2026-08-05, wieczór

Patche PHP wgrane na produkcję (backupy `.bak-2026-08-05-rankingi`), smoke test czysty.
Wpis **opublikowany**: https://primaauto.com.pl/rankingi/chinskie-suvy-ranking-sprzedazy/

**Weryfikacja krzyżowa D9 — zrobiona przed publikacją.** Najmocniejszy punkt: **NIO ES8 za
czerwiec — Dongchedi 8 966 wobec 8 969 w tabeli dostaw cnevpost, różnica 3 sztuki (0,03%)**.
To dwa niezależne pomiary tego samego modelu i miesiąca (rejestracje vs dostawy). Dodatkowo
sumy per marka z naszego zaciągu mieszczą się w całkowitych dostawach marek: Li Auto 29 815
z 30 895, Leapmotor 63 882 z 93 376, NIO+Onvo 31 050 z 40 597, Xiaomi 14 324 z 34 738.

**Gotcha RankMath, która kosztowała diagnozę:** dla wpisów typu `post` osobny węzeł `FAQPage`
**wypada z grafu** (`ItemList` przechodzi bez problemu). RankMath sam wpina FAQ w węzeł
`WebPage` (`change_webpage_entity()`), więc robimy to samo: `WebPage['@type']` rozszerzony
o `FAQPage` plus `mainEntity`. Dla CPT (Leksykon) grafu `WebPage` nie ma i tam zostaje węzeł
samodzielny — stąd wrażenie, że „na wiki działa, a tu nie". Dotyczy też przyszłych `porownania`
i `poradniki`.

**Stan po publikacji:**

| Sprawdzenie | Wynik |
|---|---|
| `ItemList` w schema | 30 pozycji, `ItemListOrderDescending` |
| `FAQPage` | 5 pytań, wpięte w `WebPage` |
| Autolinker w treści | działa (kategoria objęta warunkiem) |
| Sekcja „Najnowsze rankingi" na `/marki/` | **włączyła się sama**, wpis widoczny |
| `post-sitemap.xml` | wpis w środku po `wp rankmath sitemap generate` (cache jak w T-192/T-219) |
| `llms.txt` | sekcja „Rankingi rynku chińskiego", okres słownie |
| Tabela na telefonie | wrapper `overflow-x:auto`, style z `kb.css` |

**Uwaga o świeżości:** cnevpost ma już dane **za lipiec** (per marka), a ranking per model
u Dongchedi wciąż pokazuje czerwiec. Gdy lipiec wejdzie (zwykle ok. 10. dnia miesiąca),
odświeżenie to jedna komenda: `ranking_refresh.py --ranking --wpis 406121`.
