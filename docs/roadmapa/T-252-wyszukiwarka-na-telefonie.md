# T-252 — wyszukiwarka na telefonie: arkusz filtra i wyposażenie

> Prompt wykonawczy. Powstał 2026-09-03 z pytania Janka: **„wybrałem kombi i SUV — i co teraz
> z tym oknem?"**. Odpowiedź: dziś nic tego nie mówi. Diagnoza i pomiary poniżej są zrobione,
> nie trzeba ich powtarzać — od razu do wdrożenia.
>
> Przeczytaj przed startem:
> 1. `docs/roadmapa/T-251-wyszukiwarka-po-odbiorze.md` — punkty A i B (zamknięte 03.09: autolinki
>    zostają subtelne, pastylka NIE prowadzi do słownika). Nie odgrzewaj tych decyzji.
> 2. Makieta wariantów: `docs/makiety/gen-arkusz-mobilny.py` → wystawiona na
>    `https://auratest.pl/fe4f58fec53ctmp/primaauto-makiety/arkusz-mobilny-2026-09-03.html`
> 3. Memory `project_t116_kierunek_makieta_i_2026_09_03` — co w układzie wyszukiwarki już odrzucone.

## 1. Co jest nie tak (zmierzone 03.09, 390 × 844, emulacja dotyku)

### Arkusz filtra — ślepa uliczka
Arkusz (`.aas__pop` w trybie ≤ 768 px) to **sama lista opcji**. Nie ma nagłówka, przycisku ani
widocznego licznika. Wyjścia, jakie dziś istnieją:

- tap w zasłonę **nad** arkuszem — arkusz zajmuje `max-height: 78vh`, więc zostaje ~180 px u góry;
- `Escape` — klawiatury na telefonie nie ma;
- tap w `.aas__sel` — ale to pole jest pod zasłoną;
- **Android: gest wstecz wychodzi z CAŁEJ strony.** Otwarcie arkusza nie dodaje wpisu do historii
  (`asiaauto-search.js:355` robi `history.replaceState`, nie `pushState`), a `popstate` (`:589`)
  woła `location.reload()`. Odruch „wstecz zamyka arkusz" kosztuje wyjście z wyszukiwarki.

Do tego **kreska u góry arkusza kłamie**: `.aas__pop::before` rysuje uchwyt sugerujący zsuwanie,
a obsługi gestu nie ma. Gorzej niż brak kreski — obiecuje zachowanie, którego nie dostarcza.

I nie widać efektu wyboru: liczba wyników przelicza się na żywo (`debounced()`, 350 ms → `refresh()`),
ale `.aas__total` siedzi w pasku narzędzi **zasłoniętym przez arkusz**.

### Wyposażenie — 1,8 ekranu płaskiej listy
| pomiar | wartość |
|---|---|
| sekcja zwinięta | 431 px, 8 pastylek + „Więcej wyposażenia (+28)" |
| sekcja rozwinięta | **1 535 px = 1,8 ekranu**, 36 pastylek w **29 rzędach** |
| wysokość pastylki | **38 px** |
| grupowanie w UI | **brak** — płaska lista sortowana po liczbie ofert |
| szukajka | brak |

Trzy wnioski:
1. **Grupy już istnieją w kodzie i nie są pokazywane.** `AsiaAuto_Search::FLAG_GROUPS` ma cztery
   zestawy (Fotele i kierownica 7, Kamery i asystenci 7, Ekrany i multimedia 8, Komfort i elektryka 14),
   ale używa ich wyłącznie `etykietaFlagi()` — do wyciągnięcia nazwy. Użytkownik dostaje listę,
   w której „Masaż foteli tył" sąsiaduje z „AR-HUD".
2. **38 px to poniżej progu dotykowego** zalecanego przez iOS HIG i Material (44 px). Axe milczy,
   bo WCAG 2.5.8 AA wymaga tylko 24 × 24 — bramka świeci na zielono, a palec trafia w sąsiada.
3. Z listy 129 kandydatów (`T-116-kandydaci-pastylek.md`) każde dołożenie pastylek pogarsza to
   liniowo: 3–5 ekranów przewijania przed dojściem do wyników.

## 2. Decyzja (Janek, 03.09): A dla list, C dla wyposażenia

Dwa różne problemy, więc dwie formy. Pełny ekran dla „Napędu 4x4" (trzy opcje) byłby absurdem;
arkusz dla wyposażenia nie ma zastosowania, bo pastylki nie siedzą w arkuszu — rozpychają stronę.

| | listy rozwijane | wyposażenie |
|---|---|---|
| ile pozycji | **11 z 13 list ≤ 11 pozycji** | 36 dziś, do 129 docelowo |
| forma | **A** — arkusz z nagłówkiem i stopką | **C** — pełny ekran z grupami |

Rozmiary list (`wp7j_asiaauto_specs`, publish, 03.09), bo to one przesądziły:
`serie` 269 globalnie / do 44 po marce (BYD), `make` 58 (40 z ofertami), `sound_brand` 19,
`color` 13, `interior_color` 12, `body` 10, `transmission`/`sunroof`/`fuel` po 6,
`upholstery`/`seats` po 5, `drive` 3, `suspension` 2.

Arkusz mieści **11 pozycji** bez przewijania (78vh = 658 px − nagłówek 50 − stopka 70, pozycja 48 px).
Pełny ekran mieści 13. Różnica dwóch pozycji nie uzasadnia przebudowy — dlatego C tylko tam,
gdzie zawartość jest naprawdę duża.

## 3. Zakres wdrożenia

### A. Arkusz filtra (listy rozwijane)
1. **Nagłówek**: tytuł filtra (z `$pole['label']`), licznik zaznaczonych, przycisk „×" (44 × 44).
2. **Stopka**: przycisk pełnej szerokości **„Pokaż N ofert"** — N bierz z tego, co już liczy
   `refresh()`; żadnego nowego zapytania do REST. Odmiana: 1 → „ofertę", 2–4 → „oferty",
   reszta → „ofert" (uwaga na 12–14 i 112: `n % 100 < 10 || n % 100 > 20`).
3. **Automatyczna wysokość**: lista ≤ 11 pozycji → arkusz niski (dopasowany do treści);
   dłuższa → pełna wysokość ekranu, szukajka przyklejona pod nagłówkiem. Marka i model dostają
   wtedy to, co dawał wariant C, a „Napęd 4x4" zostaje niskim arkuszem na trzy opcje.
4. **Zsuwanie palcem w dół** zamyka — tylko gdy `lista.scrollTop <= 0`, próg ~70 px,
   `touchstart`/`touchmove` jako `{ passive: true }`.
5. **`pushState` przy otwarciu, `history.back()` przy zamknięciu przyciskiem.** `popstate` ma
   zamykać arkusz zamiast przeładowywać stronę — dziś `:589` robi `location.reload()`,
   trzeba rozróżnić stan „arkusz otwarty" od zmiany filtrów w URL.
6. Kreska u góry zostaje — od tej chwili mówi prawdę.

### C. Wyposażenie (pełny ekran)
1. Sekcja „Wyposażenie i technologie" na telefonie: zamiast 36 pastylek inline — **przycisk
   otwierający pełnoekranowy widok** z podsumowaniem („Wyposażenie · 3 wybrane").
2. Widok: pasek górny (strzałka wstecz + tytuł, bez „×" — jedna droga wyjścia), szukajka,
   lista **pogrupowana wg `FLAG_GROUPS`** z nagłówkami grup, stopka „Pokaż N ofert".
3. Pozycje jako wiersze listy (nie pastylki) — checkbox + nazwa + liczba ofert, min. 48 px.
4. Na desktopie **bez zmian**: pastylki zostają jak są.
5. Podnieś pastylkę do **44 px** (dotyczy też widoku desktopowego — sprawdź, czy nie rozjeżdża
   siatki `.aas__chips`).

## 4. Gdzie to siedzi

- `plugins/asiaauto-sync/includes/class-asiaauto-search.php` — `renderPole()` (~660) buduje
  `.aas__pop`, `renderOpcja()` (~683), pastylki (~740–755), `FLAG_GROUPS` (~826), `etykietaFlagi()`.
- `plugins/asiaauto-sync/assets/js/asiaauto-search.js` — `otworzListe()` (~459), `zamknijListy()` (~449),
  `debounced()` (~440), delegacja zdarzeń (~494), `popstate` (~589).
- `plugins/asiaauto-sync/assets/css/asiaauto-search.css` — `@media (max-width: 768px)` (299–346).
- Makieta referencyjna: `docs/makiety/gen-arkusz-mobilny.py` (warianty A/B/C, prawdziwe liczby).

## 5. Pułapki (z tej i poprzedniej sesji)

1. **Kod działa, a użytkownik nic nie widzi.** 03.09 trzy błędy tej klasy jednego dnia
   (autolinki bez podkreślenia przez specyficzność, CTA gubiące filtr, 12 haseł poza indeksem) —
   żadnego nie złapały bramki liczbowe. **Po każdej zmianie zobacz stronę oczami, nie tylko liczby.**
2. **Checkboxy mają `opacity: 0`** — w teście klikaj etykietę, nigdy `input`.
3. **Baner Complianz przechwytuje kliknięcia** — w headless odrzuć cookies na starcie.
4. **iOS Safari zoomuje przy fokusie na polu < 16 px** i nie cofa powiększenia — dlatego
   `font-size: 16px` na wszystkich polach ≤ 768 px. Nie zmniejszaj tego w nowym markupie.
5. **Link wewnątrz kontrolki = `nested-interactive` w axe.** Cokolwiek dokładasz do wiersza listy,
   ma być osobnym celem albo nie być linkiem.
6. `display: flex` na przycisku zjada spacje między `<span>`; `opacity` na tekście psuje kontrast —
   szarz kolorem, nie przezroczystością.
7. **`env(safe-area-inset-bottom)`** w stopce arkusza i pełnego ekranu — inaczej przycisk wejdzie
   pod pasek gestów iPhone'a.
8. Blokada przewijania tła: `document.body.style.overflow = 'hidden'` już jest w `otworzListe()` —
   pamiętaj o zdjęciu jej w każdej ścieżce wyjścia (przycisk, gest, wstecz, zasłona).

## 6. Bramki przed ogłoszeniem „gotowe"

- `axe` 320 / 390 / 1366 px → **0 naruszeń** (dziś zero, nie zepsuj).
- `impeccable detect` → 0.
- `scripts/test-ui-wyszukiwarka.mjs` → bez błędów (kopiuj do `~/projekty/auranet/scripts/`,
  `node_modules` z puppeteer-core i axe-core są tam; `CHROME_BIN=~/.cache/ms-playwright/chromium-1223/chrome-linux64/chrome`).
- `scripts/porownaj-search.php` 50 kombinacji → 0 rozjazdów.
- Ręcznie na 390 px: otwórz arkusz, zaznacz dwie wartości, wyjdź **każdą** drogą (przycisk, „×",
  gest w dół, zasłona, wstecz) i sprawdź, że filtry zostały, a strona nie przeładowała się.
- Telefon Janka (iPhone 15 Pro) — potwierdzenie, że nie ma zoomu przy dotknięciu szukajki.

## 7. Czego nie ruszać

- `class-asiaauto-inventory.php`, importer, `renderCard()`, nazwy klas/CPT/meta/ról.
- Widok desktopowy list — zmiany wyłącznie w `@media (max-width: 768px)` i w JS pod warunkiem
  `matchMedia('(max-width: 768px)')`.
- Decyzje z T-251 A i B: autolinki zostają subtelne, pastylka nie prowadzi do słownika.
- Publikacja treści = osobna zgoda Janka.

## 8. Stan wejściowy

Plugin **0.37.15**, motyw **1.3.6**, repo czyste na `c9cabe2`. Wyszukiwarka: `/wyszukiwarka/`
podlinkowana w czterech miejscach, 36 pastylek, filtry zależne, bramki na zerze.
