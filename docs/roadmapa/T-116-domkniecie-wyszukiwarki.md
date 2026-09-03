# T-116 — domknięcie wyszukiwarki zaawansowanej

> **STAN 2026-09-03, 0.37.10:** ruchy A, B, C i D **wykonane** (D = współistnienie z katalogiem,
> 4 linki bez przenoszenia filtrów; CTA pod hasłami `/wiki/` przeniesione do T-250). Wyszukiwarka
> w kształcie makiety I, 36 pastylek, filtry zależne, telefon ze zwijanymi sekcjami. Historia
> w `docs/VERSIONS.md` 0.36.2 → 0.37.10. **Ten prompt jest domknięty.** Otwarte obok: T-250
> (słownik pod wyszukiwarkę), rozszerzanie pastylek z `T-116-kandydaci-pastylek.md`, LCP serwisu.
> Poniżej treść pierwotna, liczby historyczne.

> Prompt wykonawczy do odpalenia w nowym wątku w `~/projekty/primaauto`.
> Powstał 2026-09-03 po sesji, w której wyszukiwarka przeszła z zera do v0.36.1.
> **Zastępuje** `docs/roadmapa/T-116-po-odbiorze-cztery-ruchy.md` (część jego ruchów wykonana,
> reszta przeniesiona tutaj z aktualnymi liczbami).
>
> Przeczytaj przed startem:
> 1. `docs/decyzje/2026-09-02-wyszukiwarka-zaawansowana-tabela-specs.md` — co i dlaczego stoi,
>    plus dwa dopiski: usterki z przeglądu w przeglądarce i **pomiar GA4 wagi rodzaju oferty**.
> 2. `docs/VERSIONS.md` wpisy 0.35.0 → 0.36.1 — pełna historia z pomiarami.
> 3. `docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md` sekcja 3 — wsad do drugiego rzutu pól.

---

## 0. Stan na 2026-09-03 (zweryfikuj liczby na starcie)

`/wyszukiwarka/` (post 459262) działa na produkcji, plugin **0.36.1**. Publiczna, w indeksie,
**nigdzie nie podlinkowana**. `/samochody/` nietknięte przez całą sesję.

| element | stan |
|---|---|
| tabela `wp7j_asiaauto_specs` | 3 313 wierszy (2 994 publish), 43 kolumny |
| pokrycie `accel_s` (przyspieszenie) | 90,4% (rośnie z nocnym wzbogacaniem) |
| `reservation` (rodzaj oferty) | 52 oferty ze statusem, reszta = do sprowadzenia |
| trasy REST | `asiaauto/v1/search`, `/search-counts` |
| cron 05:05 | **działa** — pierwszy bieg 2026-09-03, 2 267 ofert, 6,3 s |
| axe (320 i 1366 px) | 0 naruszeń |
| `impeccable detect` | 0 anty-wzorców |
| bramka porównawcza vs `/listings` | 0 rozjazdów na 150 kombinacjach |

**Wszystkie bramki etapu 3 są domknięte, łącznie z DoD 5** (dowód z logu crona:
`~/.claude/zbuduj-specs.log`, wpis z 05:05).

---

## 1. Jedyna rzecz, która blokuje domknięcie: kierunek wizualny

Powstały cztery makiety, **Janek odrzucił wszystkie**:
`https://auratest.pl/fe4f58fec53ctmp/primaauto-makiety/`
(generatory w repo: `docs/makiety/gen.py`, `gen-d.py`)

| wariant | co proponował | werdykt |
|---|---|---|
| A | stan obecny v0.36.0, pigułki z popoverami | odrzucony |
| B | to samo po Pre-Flight Check taste-skilla | odrzucony |
| C | filtry rozwinięte, cztery kafle rodzaju oferty | odrzucony |
| D | bez ograniczenia motywu: ciemny pulpit, Space Grotesk, oś Kanton → Rzeszów | „nie bardzo" |

**Nie rób piątej makiety na ślepo.** Cztery odrzucone bez podanego powodu to sygnał, że brakuje
wsadu, a nie pomysłów. Zanim zaczniesz rysować, wyciągnij od Janka **jedną** z tych rzeczy:

- adres strony, która mu się podoba (dowolna branża) — wtedy pracujesz na rytmie, typografii
  i temperaturze zdjętej z konkretu, nie z domysłu;
- albo słowo o tym, co odrzuca: za ciemne / za gęste / za techniczne / za mało premium / za bardzo „panel".

Pytaj **raz**, jednym pytaniem (tak każe też sam skill: `0.C` — jedno pytanie, nie lista).

**Co wiadomo o formie z pomiaru** (dopisek w ADR): rodzaj oferty **zasługuje na wyróżnienie**
(strony `/w-rzeszowie/` i `/w-drodze/` zbierają 21% ruchu katalogu przy 1,5% oferty, ponad
4 000 sesji z wnętrza serwisu), ale **nie na 25% wysokości ekranu**. Przełącznik w jednej linii
(48 px, jak w wariantach A i B) oddaje tę samą hierarchię. To jest ustalone danymi, nie gustem
— przy każdym kolejnym wariancie ma zostać.

---

## 2. Ruchy do wykonania, w tej kolejności

### A. Moc układu zamiast mocy silnika (2-3 h, rekomendowany pierwszy)

**98 ofert PHEV/EREV** ma w `_asiaauto_horse_power` moc silnika spalinowego zamiast mocy układu
(AITO M9 EREV: 152 KM zamiast ~490, Leapmotor D19: 0 KM). Filtr mocy je odsiewa, mimo że próg
spełniają. To jedyna rzecz, przez którą wyszukiwarka dziś **kłamie**.

1. Dump `postmeta` dla `_asiaauto_horse_power` + `_asiaauto_horse_power_source` do `~/backups/primaauto/<data>/`.
2. `scripts/napraw-moc-ukladu.php` (dry-run domyślnie, `apply`), wzorzec argumentów jak w `zbuduj-specs.php`.
   Moc układu liczy `AsiaAuto_Spec::km_from_power()` — jest `private`, więc dołóż **czystą metodę
   publiczną obok**, nie przerabiaj istniejącej.
3. **Tylko podnosisz.** Niższa albo równa wartość zostaje; prompt etapu 3 zakazywał obniżania.
4. Stempluj `_asiaauto_horse_power_source`, inaczej za miesiąc znowu nie odróżnisz naprawionych.
5. Po `apply`: `php scripts/zbuduj-specs.php apply` (pełny bieg, ~6 s).

**Bramki:** dry-run z listą (ID, tytuł, przed, po, użyty klucz) **pokazany Jankowi przed apply**;
po biegu 0 ofert publish z mocą 0 KM; liczba PHEV/EREV poniżej 200 KM spada z 98 do wartości,
którą umiesz uzasadnić; trzy adresy sprawdzone w przeglądarce.

**Pułapki:** `engine_max_horsepower` to moc SILNIKA, `system_max_power` i `electric_total_horsepower`
to układ. Dwie oferty mają stempel `engine_max_horsepower` — zapytaj Janka, czy je ruszamy.
Moc wchodzi do tytułów ofert i treści hubów, więc sprawdź `skip_title_regen`
(patrz `reference_skip_title_regen_zamraza_ceny`).

### B. Etykieta „Na placu w Rzeszowie" jest niedokładna (30 min, decyzja Janka)

Z ofert `on_lot` tylko część ma `stm_car_location` = Rzeszów; reszta to Pabianice, Warszawa,
Kanton, „W drodze do UE". Kanton i „W drodze do UE" to najpewniej nieodświeżone pole
(`stm_car_location` nie jest wiarygodne — patrz memory), ale Pabianice i Warszawa wyglądają
na realne. Dwie drogi: etykieta wraca do „Na placu" (jak badge na karcie) albo dane idą
do uporządkowania u Ruslana. **Zapytaj, nie zgaduj.**

### C. Drugi rzut pól (8-12 h, nowy zakres — wyceń przed startem)

Wsad: `docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md` sekcja 3.
Kolejność wg tego, co realnie odblokowują (liczba haseł `/wiki/`, które da się potem podlinkować):

| pole | typ | pokrycie | odblokowuje haseł wiki |
|---|---|---|---|
| `battery_type` (LFP/NMC/LMFP) | E | 80% | 4 |
| `sound_brand` | E | 33% | 3 |
| `battery_brand` (CATL, FinDreams) | E | 80% | 2 |
| `driver_form` (układ napędowy) | E | 99% | 2 |
| `automatic_drive_level` (L2/L2+) | E | 93% | 1 |
| `high_voltage_fast_charging_platform_800` | F | 29% | 1 |
| `navigation_assisted_driving_1/2` (NOA) | F | 44/57% | 1 |
| `active_dms_fatigue_detection` | F | 74% | 1 |
| `memory_parking` (bez `选配`) | F | 25% | 1 |
| `jb` (klasa pojazdu) | E | 99% | filtr segmentu, mocno różnicujący |

**Jak nie rozwalić tego, co stoi:**
1. Kolumny `ALTER`-em przez `AsiaAuto_Specs_Table::install()`; **sprawdź `SHOW COLUMNS` po instalacji**
   (02.09 `dbDelta` dodało kolumny dopiero za drugim wywołaniem).
2. Każdy nowy enum = słownik kanoniczny + raport nieznanych wartości. Bramka „0 nieznanych" zostaje.
3. Po dołożeniu: pełny `zbuduj-specs.php apply` **oraz** `porownaj-search.php` — nowe kolumny
   nie mogą ruszyć wyników filtrów podstawowych (musi zostać 0 rozjazdów).
4. Powtórz `scripts/test-ui-wyszukiwarka.mjs` i `npx impeccable detect` — oba mają zostać na zerze.

**Trzy reguły normalizacji do przeniesienia na nowe pola:** pusta wartość klucza = NIE (nie
„brak danych"); `选配`/`选装` = opcja cennikowa, nie liczy się do flagi; wartości parowe „A / B"
biorą pierwszy człon, rozbijane tylko po ` / ` ze spacjami (nigdy po gołym `/`).

### D. Linkowanie (krok 7 etapu 3 — WYMAGA osobnej zgody na każdy punkt)

**Nie rób z własnej inicjatywy.** Zgoda na jeden punkt nie jest zgodą na pozostałe.

- **CTA „Wszystkie auta" na hasłach `/wiki/`** (`class-asiaauto-wiki-cars.php:168`): z 60 haseł
  z kluczami **16 da się podpiąć dziś**, 44 czekają na ruch C. Sensowna kolejność to **C przed
  linkowaniem**, żeby nie dotykać CTA dwa razy.
- **Menu i stopka** (`themes/primaauto2026/header.php`, `footer.php`): menu jest pełne, więc
  zapytaj **co ma ustąpić**, zamiast dokładać.
- **Pytanie otwarte, ważniejsze niż powyższe:** `/w-rzeszowie/` i `/w-drodze/` mają własny ruch
  organiczny (641 wejść z zewnątrz w 90 dni), własne SEO i pozycje w menu. Filtr w wyszukiwarce
  ich **nie zastępuje** — jest trzecią drogą do tych samych 45 aut. Do rozstrzygnięcia: czy te
  strony docelowo stają się deep-linkami do wyszukiwarki (`?oferta=na-placu`), czy zostają
  osobne. To decyzja SEO, nie UI — nie podejmuj jej sam.
- **Podmiana `/samochody/`**: osobny task z własnym promptem. To strona konwersyjna z ruchem
  płatnym i landingami kampanii Ads/DSA; podmiana bez planu przekierowań to utrata ruchu.

---

## 3. Czego NIE ruszasz

- `class-asiaauto-inventory.php` i `asiaauto-inventory.js` — mtime musi zostać sprzed 2026-09-02.
  Jedyny styk to publiczna `AsiaAuto_Inventory::renderCard()`.
- Logiki importera, nazw klas/CPT/meta/ról, zapisu `_asiaauto_extra_prep` (zawsze przez `wp_slash`).
- Motywu aktywnego: `primaauto2026`. `themes/asiaauto/` jest martwy.
- Cron tylko przez `~/bin/cron-install`. Backup `mysqldump` przed każdą zmianą danych.

---

## 4. Pułapki tej sesji, żeby nie powtórzyć

1. **`[hidden]` przegrywa z każdą regułą `display`.** Dwa razy tego samego dnia: opcje z `display: flex`
   i grupy zakresowe z `display: grid`. W panelu jest już reguła zbiorcza `.aas [hidden]`, ale
   w innych częściach serwisu ten wzorzec będzie cichy tak samo.
2. **`.aas button` (0-1-1) bije `.aas__chip-btn` (0-1-0).** Każdy stylowany przycisk potrzebuje
   prefiksu `.aas `.
3. **`toLocaleString('pl-PL')` nie grupuje liczb czterocyfrowych**, a PHP `number_format` grupuje.
   W JS jest własny `fmt()` — używaj go, nie wracaj do `toLocaleString`.
4. **`step` w polu liczbowym liczy się od `min`, nie od zera.** Dlatego pola zakresów mają
   `step="any"`; nie przywracaj kroku „dla wygody".
5. **Motyw ma `scroll-behavior: smooth` na `<html>`** — przewijanie po zmianie strony musi być
   jawnie `behavior: 'instant'` i wykonane **po** podmianie treści.
6. **Zapis do `postmeta` nie odpala hooków posta.** Każda kolumna `specs` brana wprost z meta
   potrzebuje hooka `updated_post_meta` albo pozycji w siatce `idsToRebuild()`.
7. **Sprzątaj procesy Chromium po testach** (`pkill -f chrome-headless`). Sieroty zjadają limit
   LVE i kolejny przebieg wywala się na timeoucie nawigacji.
8. **Wgrywaj PHP razem z CSS/JS.** Raz poszły same assety i strona wyświetliła się bez stylów.

## 5. Narzędzia

```bash
php scripts/zbuduj-specs.php [apply] [since=48h] [limit=N]   # bez apply = dry-run
php scripts/porownaj-search.php 50 <seed>                    # bramka vs stara trasa
cp scripts/test-ui-wyszukiwarka.mjs ~/projekty/auranet/scripts/tmp-t.mjs
cd ~/projekty/auranet/scripts && CHROME_BIN=~/.cache/ms-playwright/chromium-1223/chrome-linux64/chrome node tmp-t.mjs
npx impeccable@latest detect <katalog>                       # 61 reguł, bez instalacji do repo
```

## 6. Definicja zrobionego dla tego wątku

1. Kierunek wizualny zaakceptowany przez Janka i wdrożony na `/wyszukiwarka/`.
2. Ruch A wykonany: 0 ofert publish z mocą 0 KM, dry-run zaakceptowany przed `apply`.
3. Etykieta rodzaju oferty rozstrzygnięta (B).
4. `/samochody/` bez regresji, `class-asiaauto-inventory.php` z mtime sprzed 2026-09-02.
5. Po każdej zmianie: `test-ui-wyszukiwarka.mjs` (axe 0), `impeccable detect` (0),
   `porownaj-search.php` (0 rozjazdów).
6. Bump wersji, `docs/VERSIONS.md`, memory.
