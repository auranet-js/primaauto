# T-229 — Szablon rankingu + generator listy z bazy

> Status: **spec gotowy do wykonania** (2026-08-04) · Rozmiar: M · Godziny realnie: **5–7 h**
> Rodzic: **T-214 F4** (filar C — blog `/rankingi/`) · Odblokowuje: T-230 (pierwsze 3 rankingi), T-231
> Pomiar wejściowy: `docs/analizy/2026-08-04-rankingi-porownania-recon.md`

## Po co

`/rankingi/` istnieje i zwraca HTTP 200, ale ma **zero wpisów**. Pomiar z 04.08 pokazał segmenty
z realnym popytem, których nie obsługujemy żadną treścią: terenówki ~3450/mc, pickupy ~2480,
vany i 7-osobowe ~2140, sedany ~2180, kombi 480. Ten task buduje formę i mechanikę — treści
powstają w T-230.

## Stan faktyczny (zweryfikowany 2026-08-04)

- Kategoria `rankingi` istnieje, permalink `/%category%/%postname%/`, `category.php` obsługuje
  archiwum (breadcrumb, siatka kart, `kb-sidebar`). **Nic nie trzeba budować po stronie listingu.**
- `single.php` renderuje wpisy z `kb-sidebar`.
- ⚠️ **Autolinker działa tylko dla kategorii `aktualnosci`** (`class-asiaauto-autolink.php:61`).
  Wpisy w `rankingi` nie dostaną linków do słownika, dopóki warunek nie obejmie tej kategorii.
- ⚠️ **Schema `NewsArticle` też jest przypięta wyłącznie do `aktualnosci`**
  (`class-asiaauto-seo.php:170`). Rankingi dostaną domyślny `Article`/`BlogPosting` z RankMath —
  brakuje `ItemList`, który jest sednem rankingu.
- `/marki/` ma gotową sekcję „Najnowsze rankingi" — **włączy się sama** przy pierwszym `publish`.
- Pipeline `scripts/kb/`: `kb_lib.py` (call_model, wp, parse_json, normalize_quotes, proofread),
  wzorce w `wiki_generate.py` i `make_hub_generate.py` (research → dane z bazy → generacja → zapis).

### Podaż w segmentach (auta publish / unikalne modele)

| Segment | Aut | Modeli | Popyt /mc |
|---|---:|---:|---:|
| SUV | 1182 | 173 | ~6500 |
| Sedan | 641 | 84 | ~2180 |
| **Terenówki** (nadwozie ramowe) | **102** | **23** | **~3450** |
| Minivan / 7-osobowe | 114 | 20 | ~2140 |
| Kombi | 26 | 4 | 480 |
| Pickup | 6 | 6 | ~2480 |

Pickupy mają największy rozjazd popytu do podaży (2480/mc przy 6 autach) — ranking i tak ma sens,
bo to *cały* dostępny segment, ale tekst musi to nazwać wprost, zamiast udawać szeroki wybór.

## Decyzje

- **D1 — Ranking dotyczy NASZEJ oferty, nie rynku chińskiego.** Nie mamy danych o sprzedaży w Chinach
  (zrzuty z WeChat to trop, nie źródło — patrz analiza z 04.08), za to mamy komplet parametrów
  i **ceny końcowe w PLN po cle i podatkach**. Ramowanie: „co realnie da się kupić w Polsce",
  nie „co się sprzedaje w Chinach". To jest przewaga, której nie ma nikt inny na polskim rynku.
- **D2 — Ranking musi dać się odświeżyć bez przepisywania.** Magazyn zmienia się codziennie.
  Blok z tabelą i liczbami siedzi między znacznikami `<!--RANKING:START-->` / `<!--RANKING:END-->`
  i jest podmieniany skryptem; narracja, FAQ i wnioski zostają nietknięte.
- **D3 — Zasięg podajemy w WLTC, nie CLTC.** Mamy `wltc_recharge_mileage` (41% pokrycia).
  Gdy brak WLTC, pokazujemy CLTC **z jawną etykietą** i notą metodologiczną — nigdy nie mieszamy
  obu w jednej kolumnie.
- **D4 — Kryterium rankingu = jeden mierzalny parametr**, nie „najlepszy". „Najdłuższy zasięg",
  „największa bateria", „najszybsze 0–100", „najniższa cena wejścia". Subiektywne rankingi
  wymagają metodologii, której nie mamy.
- **D5 — Jedna pozycja = jeden MODEL, nie egzemplarz.** Przy 173 modelach SUV lista egzemplarzy
  byłaby bez sensu. Dla modelu bierzemy najlepszy wynik parametru i najniższą cenę.
- **D6 — Gate antykanibalizacyjny przed publikacją** (reguła D2 z T-162): sprawdzamy w GSC,
  czy fraza główna nie ma już URL-a w serwisie na pozycji ≤20. Jeśli ma — albo zmieniamy frazę,
  albo wzmacniamy istniejącą stronę zamiast tworzyć nową.

## Pliki

| Plik | Rola |
|---|---|
| `scripts/kb/ranking_data.py` | **nowy** — liczy listę rankingową z bazy: segment → modele → parametr → cena → URL huba |
| `scripts/kb/ranking_generate.py` | **nowy** — research + generacja narracji + złożenie wpisu + zapis draftu |
| `scripts/kb/ranking_refresh.py` | **nowy** — przelicza blok `<!--RANKING:START/END-->` w istniejących wpisach |
| `scripts/kb/rankingi.json` | **nowy** — definicje rankingów (segment, kryterium, fraza główna, tytuł) |
| `class-asiaauto-autolink.php:61` | **modyfikacja** — objąć kategorie `rankingi`, `porownania`, `poradniki` |
| `class-asiaauto-seo.php` | **modyfikacja** — `ItemList` dla wpisów w `rankingi` |

---

## Task 1 — Warstwa danych (`ranking_data.py`)

**Deliverable:** funkcja zwracająca gotową listę rankingową dla zadanego segmentu i kryterium.

- [ ] **1.1** Definicja segmentu: filtr po taksonomii `body` (Pickup/Kombi/Minivan/SUV/Sedan) **albo**
  po `car_body_structure = 非承载式` (terenówki — nadwozie ramowe, 102 auta). ⚠️ CJK w bazie
  występuje w **dwóch formach** (surowa i escape `\uXXXX`) — użyć `json_decode`, nie `LIKE`.
- [ ] **1.2** Kryteria z `_asiaauto_extra_prep` (pokrycie zmierzone 04.08):
  `wltc_recharge_mileage` 41% · `cltc_recharge_mileage` 72% · `battery_capacity` 76% ·
  `acceleration_time` 79% · `max_speed` 97% · `max_fast_charge_power` 41% · wymiary 97,5%.
- [ ] **1.3** **Pułapki do obsłużenia — bez tego ranking skłamie:**
  - hybrydy i EREV mają w polu zasięgu wartość **łączną**, nie elektryczną (Denza Z9 GT DM-i
    pokazuje 1036 km) → ranking zasięgu **wyłącznie dla BEV**, hybrydy w osobnym z innym polem;
  - filtrowanie po nazwie paliwa jest zdradliwe: `Hybryda plug-in (PHEV)` **zawiera ciąg „EV"** →
    filtr po slugu termu, nigdy przez `str_contains`;
  - modele z jedną sztuką w magazynie wchodzą, ale tekst nie może sugerować szerokiego wyboru.
- [ ] **1.4** Agregacja per model (D5): najlepszy wynik parametru, najniższa cena, liczba sztuk,
  URL huba `serie`, zdjęcie z galerii najtańszego egzemplarza.
- [ ] **1.5 Test:** dla „terenówki + cena wejścia" wynik zawiera Tank, Leopard 5/8, BJ40, G700;
  liczba modeli zgadza się z zapytaniem kontrolnym w bazie; żaden model nie powtarza się dwa razy.

---

## Task 2 — Blok rankingowy w treści

**Deliverable:** HTML tabeli z danymi, wstawiany między znacznikami i podmienialny.

- [ ] **2.1** Render tabeli: pozycja, model (link do huba), kluczowy parametr, cena od, liczba sztuk.
  Kolumna parametru ma **nagłówek z jednostką i normą** („Zasięg WLTC [km]"), nie samo „Zasięg".
- [ ] **2.2** Otoczyć znacznikami `<!--RANKING:START-->` / `<!--RANKING:END-->` (D2).
- [ ] **2.3** Pod tabelą nota metodologiczna generowana z danych: data przeliczenia, liczba modeli,
  źródło parametrów, informacja o brakach („dla N modeli brak danych WLTC — podano CLTC").
- [ ] **2.4** Mobile: tabela w `overflow-x: auto`, pierwsza kolumna przyklejona (79,6% ruchu).
- [ ] **2.5 Test:** podmiana bloku w istniejącym wpisie nie rusza narracji ani FAQ; tabela
  nie rozjeżdża się przy 375 px.

---

## Task 3 — Generator wpisu (`ranking_generate.py`)

**Deliverable:** draft wpisu w kategorii `rankingi`, gotowy do przejrzenia.

- [ ] **3.1** Wejście z `rankingi.json`: segment, kryterium, fraza główna (z DFS), tytuł, slug.
- [ ] **3.2** Research w sieci **przed** pisaniem (lekcja z T-214) — kontekst rynkowy segmentu,
  nie liczby o naszej ofercie. Źródła zapisywane w meta wpisu.
- [ ] **3.3** Prompt generuje **wyłącznie narrację**: lead answer-first, uzasadnienia dla 3–5
  czołowych pozycji, sekcję „na co zwrócić uwagę w tym segmencie", FAQ (5 pytań z PAA).
  **Liczby wchodzą z Task 1, model ich nie dotyka** — ta sama reguła co w `make_hub_generate.py`.
- [ ] **3.4** Bramki przed zapisem: diakrytyki (regresja T-193), obecność znaczników bloku,
  brak opisu procesu importu (reguła z T-236), zgodność liczby pozycji z danymi.
- [ ] **3.5** Zapis jako **draft** + mail do Janka (`send-to-jan`) z podglądem. Rankingi to nie newsy —
  pierwsze idą przez akcept.
- [ ] **3.6 Test:** wygenerowany draft ma tabelę z liczbami zgodnymi z zapytaniem kontrolnym,
  FAQ parsuje się, żadna liczba w narracji nie jest sprzeczna z tabelą.

---

## Task 4 — Odświeżanie (`ranking_refresh.py`)

**Deliverable:** komenda przeliczająca bloki we wszystkich opublikowanych rankingach.

- [ ] **4.1** Dla każdego wpisu w `rankingi`: odczytaj segment i kryterium z meta, przelicz dane,
  podmień blok między znacznikami, zaktualizuj datę przeliczenia.
- [ ] **4.2** Guard: jeśli liczba modeli spadła o >40% względem poprzedniego przeliczenia —
  **nie podmieniaj**, zgłoś mailem (ochrona przed przeliczeniem w środku awarii feedu,
  wzorzec sanity-gate z generatorów llms).
- [ ] **4.3** Dopisać do crona **po** pierwszym miesiącu obserwacji, nie od razu.
- [ ] **4.4 Test:** ręczne uruchomienie na opublikowanym rankingu zmienia tylko blok danych;
  `diff` treści przed/po pokazuje wyłącznie tabelę i notę.

---

## Task 5 — Wpięcie w serwis

- [ ] **5.1** Autolinker: rozszerzyć warunek z `aktualnosci` na `rankingi`, `porownania`, `poradniki`
  (`class-asiaauto-autolink.php:61`). Limit jak dla newsów (5). **Strefa ZAWSZE PYTAJ — diff.**
- [ ] **5.2** Schema `ItemList` dla wpisów w `rankingi` (`class-asiaauto-seo.php`), obok
  `Article`/`BlogPosting` z RankMath. Pozycje = modele z bloku danych.
- [ ] **5.3** Sprawdzić, czy `/marki/` pokazała sekcję „Najnowsze rankingi" po pierwszym `publish`.
- [ ] **5.4** Dopisać `/rankingi/` do `llms.txt` **w generatorze**, nie w pliku.
- [ ] **5.5 Test:** Rich Results — `ItemList` waliduje się, brak duplikatu `Article`;
  linki do słownika pojawiają się w treści rankingu.

---

## Definicja zrobionego

- `rankingi.json` opisuje pierwsze trzy rankingi z T-230.
- Generator tworzy draft z tabelą liczoną z bazy i narracją, w której model nie dotyka liczb.
- Blok danych da się przeliczyć osobną komendą bez ruszania tekstu.
- Autolinker i `ItemList` działają dla kategorii `rankingi`.
- Sekcja „Najnowsze rankingi" na `/marki/` włącza się sama.

## Ryzyka

- **Cienka podaż w niektórych segmentach** (pickupy 6 modeli, kombi 4) — tekst musi to nazywać
  wprost. Ranking czterech kombi podany jako „TOP 10" byłby wprowadzaniem w błąd.
- **Dezaktualizacja** — bez Task 4 rankingi zestarzeją się w miesiąc. To nie jest opcja dodatkowa.
- **Kanibalizacja hubów** — „chińskie terenówki" może konkurować z hubem `tank` albo `/marki/`.
  Gate D6 przed każdą publikacją.
- **Zmiany w renderze** (`autolink`, `seo`) dotykają wpisów i ofert — diff do akceptu.
