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

- [ ] **1.1** Pobranie feedów `carnewschina.com/feed/` i `cnevpost.com/feed/`, filtr po słowach
  kluczowych tematu (np. „sales", „ranking", „top", „range") i po dacie.
- [ ] **1.2** Ekstrakcja liczb z treści artykułu (nie z tytułu) wraz z URL-em źródła i datą publikacji.
  Każda pozycja niesie `zrodlo_url` i `zrodlo_data` — bez tego nie trafia do rankingu.
- [ ] **1.3** Dla rankingów parametrycznych (zasięg, bateria, ładowanie): parametry z katalogu
  Autohome przez `autohome-catalog-fetch.js` po `specid`, nie z feedów.
- [ ] **1.4** Normalizacja nazw modeli CN → nasze (钛7 → Leopard 7, 问界 → AITO, 智界 → Luxeed) —
  reuse mapowania z `data/brand-mapping-v6.1.php` i `che168-model-map.php`.
- [ ] **1.5 Test:** dla „TOP 10 zasięg BEV" każda pozycja ma liczbę, jednostkę, normę pomiaru
  i działający URL źródła; żadna liczba nie pochodzi ze zrzutu WeChat.

---

## Task 2 — Warstwa naszej dostępności (`ranking_stock.py`)

**Deliverable:** dla każdej pozycji rankingu informacja, czy i za ile mamy ją w ofercie.

- [ ] **2.1** Dopasowanie nazwy modelu z rankingu do taksonomii `serie` (po nazwie i aliasach).
- [ ] **2.2** Dla dopasowanych: liczba sztuk `publish`, najniższa cena, URL huba modelu,
  zdjęcie z galerii najtańszego egzemplarza.
- [ ] **2.3** Render bloku między `<!--OFERTA:START-->` / `<!--OFERTA:END-->`:
  „Mamy w ofercie: 3 szt., od 218 000 zł" + miniatura + link. Brak dopasowania → blok pusty,
  **bez** komunikatu „nie mamy" (to reklama konkurencji).
- [ ] **2.4** ⚠️ Pułapka: `Hybryda plug-in (PHEV)` zawiera ciąg „EV" — filtry po slugu termu,
  nigdy przez `str_contains` na nazwie.
- [ ] **2.5 Test:** ranking z 10 pozycjami, z których mamy 4 — dokładnie 4 bloki wypełnione,
  ceny zgodne z zapytaniem kontrolnym, miniatury się ładują.

---

## Task 3 — Generator wpisu (`ranking_generate.py`)

**Deliverable:** draft wpisu w kategorii `rankingi` gotowy do przejrzenia.

- [ ] **3.1** Wejście z `rankingi.json`: temat, kryterium, fraza główna (z DFS), tytuł, slug, źródła.
- [ ] **3.2** Prompt generuje **wyłącznie narrację**: lead, komentarz do czołowych pozycji,
  sekcję „co to znaczy dla kupującego w Polsce", FAQ (5 pytań z PAA).
  **Liczby wchodzą z Task 1 i 2 — model ich nie dotyka** (reguła sprawdzona w `make_hub_generate.py`).
- [ ] **3.3** Sekcja „Skąd te dane" — obowiązkowa, z linkami do źródeł i datą. To jest różnica
  między nami a przepisywaczami zrzutów.
- [ ] **3.4** Bramki: diakrytyki (regresja T-193), obecność znaczników bloków, brak opisu procesu
  importu (reguła z T-236), każda liczba w narracji zgodna z tabelą.
- [ ] **3.5** Zapis jako **draft** + mail do Janka z podglądem.
- [ ] **3.6 Test:** draft ma tabelę z liczbami mającymi źródła, FAQ parsuje się, narracja nie
  zaprzecza tabeli.

---

## Task 4 — Odświeżanie (`ranking_refresh.py`)

**Deliverable:** komenda przeliczająca bloki we wpisach — osobno dostępność, osobno dane rynkowe.

- [ ] **4.1a** `--oferta` (domyślne, pod cron): przelicza wyłącznie bloki `OFERTA`.
  **Ranking i narracja pozostają nietknięte** — zmienia się tylko to, co u nas rotuje.
- [ ] **4.1b** `--ranking --zrodlo <url>`: podmienia blok `RANKING` na nowe dane okresowe
  i aktualizuje zdanie o okresie w leadzie. Uruchamiane świadomie, nie z crona (D8).
- [ ] **4.2** Guard: spadek liczby dopasowań o >50% względem poprzedniego przebiegu → nie podmieniaj,
  zgłoś mailem (wzorzec sanity-gate z generatorów llms).
- [ ] **4.3** Cron dopiero po miesiącu obserwacji, nie od razu.
- [ ] **4.4 Test:** `diff` treści przed/po pokazuje wyłącznie bloki dostępności.

---

## Task 5 — Wpięcie w serwis

- [ ] **5.1** Autolinker: warunek z `aktualnosci` rozszerzyć na `rankingi`, `porownania`, `poradniki`.
  **ZAWSZE PYTAJ — diff.**
- [ ] **5.2** Schema `ItemList` dla wpisów w `rankingi`; pozycje = modele rankingu.
- [ ] **5.3** Sprawdzić sekcję „Najnowsze rankingi" na `/marki/` po pierwszym `publish`.
- [ ] **5.4** `/rankingi/` do `llms.txt` — w generatorze, nie w pliku.
- [ ] **5.5 Test:** Rich Results waliduje `ItemList`; linki do słownika pojawiają się w treści.

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
