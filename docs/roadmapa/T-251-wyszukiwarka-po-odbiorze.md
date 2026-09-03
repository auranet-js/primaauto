# T-251 — wyszukiwarka po odbiorze: domknięcia, filtry, pomiar

> Prompt wykonawczy do nowego wątku w `~/projekty/primaauto`. Powstał 2026-09-03 po sesji,
> w której wyszukiwarka przeszła z 0.36.1 do **0.37.15**, a słownik z 89 do 112 haseł.
> **Zaktualizowany 03.09 wieczorem** — część pozycji wykonana jeszcze w tamtej sesji, patrz „Zrobione".
> Kolejność ustalona przez Janka: **najpierw domknięcia, potem filtry, na końcu wydajność.**
>
> Przeczytaj przed startem:
> 1. `docs/VERSIONS.md` wpisy 0.36.2 → 0.37.14 (co i dlaczego stoi, z pomiarami).
> 2. `docs/roadmapa/T-116-kandydaci-pastylek.md` — 129 cech gotowych do dołożenia, próg 50 ofert.
> 3. `docs/sesje/2026-09-03-t250-pomiar-wiki.md` — punkt odniesienia GA4 dla słownika.
> 4. Memory `project_t116_kierunek_makieta_i_2026_09_03` (co odrzucone i dlaczego).

## 0. Stan na 2026-09-03 (zweryfikuj na starcie)

`/wyszukiwarka/` **podlinkowana** w czterech miejscach (home, `/samochody/` pod filtrami, menu
mobilne, stopka), plugin **0.37.15**, motyw **1.3.6**. Marki i modele sortowane **alfabetycznie**
(decyzja Janka 03.09; pozostałe listy po liczbie ofert — tam liczba mówi, gdzie jest oferta). Sekcje: Nadwozie · Napęd · Styl i komfort ·
Wyposażenie i technologie (36 pastylek + marka nagłośnienia) · Oferty (kafle). Filtry zależne
(zero = szare i wyłączone, podpowiedzi = zakres po zawężeniu, pusty stan z filtrami do zdjęcia).
Telefon: zwijane sekcje, 8 pastylek + „Więcej wyposażenia", pasek „Wyniki ↓".

| bramka | stan |
|---|---|
| axe 320 / 390 / 1366 px | 0 naruszeń |
| `impeccable detect` | 0 |
| `porownaj-search.php` 50 kombinacji | 0 rozjazdów |
| REST `search` / `search-counts` | ~90 ms / 85 ms zimno, 58 ms z cache |
| `class-asiaauto-inventory.php` | zmieniony 03.09 **jedną linią** (link pod filtrami) |

## 0b. Zrobione po napisaniu tego promptu (nie powtarzaj)

- **Pasek wyników na telefonie** (0.37.14): „Pokaż wyniki" → **„Wyniki ↓"** + dopisek „filtry
  zostają". Zmierzone: klik przewija, sekcje filtrów zostają otwarte.
- **Alfabetyczne marki i modele** (0.37.15) — w PHP i w JS (modele dorysowane po wyborze marki
  przekładają całą listę, nie doklejają na koniec); `setlocale(LC_COLLATE, 'pl_PL')` w `parseParams()`.
- **Moc układu**: ręczny bieg `napraw-moc-ukladu.php apply` → **46 ofert** z importu 03.09.
  Zostają 3 bez danych (punkt E).
- **Osiągalność haseł** (dawny punkt 12): sprawdzone — wszystkie 112 haseł osiągalnych z indeksu
  `/wiki/`, sidebar bierze 10 alfabetycznie i niczego nie filtruje. **Zamknięte.**
- **Strona postępu**: changelog uzupełniony o lukę **26.08–03.09** (7 wpisów, +39 h → **287 h**
  od spotkania), `T-116` dopisany do `todo_ukryte_id`, strona **zbudowana lokalnie**
  (`docs/kosztorys/postep.html`). **NIE wysłana** — patrz punkt D.
  ⚠️ Korekta wcześniejszej diagnozy: changelog sortuje od najnowszych, więc „urywa się 16.07"
  było błędem odczytu (`c[-1]` to najstarszy wpis). Luka wynosiła 9 dni, nie 7 tygodni.

## 1. Domknięcia — najpierw

### A. Wybór wariantu autolinku — ZAMKNIĘTE 03.09
Decyzja Janka: **zostaje wariant A** (kropkowana szara linia, kolor dziedziczony — stan po
naprawie specyficzności w motywie 1.3.5). Zero zmian w kodzie. Uzasadnienie: autolinki do
03.09 nie miały podkreślenia w ogóle, więc najpierw mierzymy, czy sama naprawa ruszyła
przejścia oferta → hasło (dziś 3 na 50 295 odsłon) — patrz punkt H. Makieta zostaje
w `docs/makiety/gen-autolink.py` na wypadek powrotu do tematu po pomiarze.

### B. Ikona „i" przy pastylkach — ZAMKNIĘTE 03.09
Decyzja Janka: **wariant 3 — bez linku z pastylki.** Wyszukiwarka zostaje narzędziem, nie
encyklopedią; droga do hasła prowadzi przez `/wiki/` i autolinki w treści oferty. Zero zmian
w kodzie, bramka axe `nested-interactive` nietknięta.

### C. Weryfikacja poprawki iOS (Janek)
0.37.11 dał 16 px na wszystkich polach przy ≤ 768 px i zdjął autofokus w liście na dotyku —
typowa przyczyna „ekran się rozjeżdża" na iPhonie. **Headless Chrome tego nie odtwarza**, więc
potwierdzenie tylko z telefonu Janka (iPhone 15 Pro). Jeśli zoom nadal jest: sprawdzić `viewport`
w motywie i `user-scalable`, potem szerokość arkusza listy przy otwartej klawiaturze.

### D. Strona postępu — ZAMKNIĘTE 03.09
Godziny zaakceptowane bez korekty (7 wpisów za lukę 26.08–03.09, +39 h → **287 h** od
spotkania 16.07). Decyzja Janka: prace przy Meta (28.08) i Ads (31.08), razem 12 h,
**zostają na tej stronie** — jedna strona na całość, jak wcześniejsze wpisy o PayU, YouTube
i sesji zdjęciowej. `build_postep.py --deploy` wykonany, strona zweryfikowana na żywo
(HTTP 200, 287 h i wpis z 03.09 widoczne). Commit nie był potrzebny — rebuild wyszedł
bajt-identyczny z zawartością `1c7d06e`.
https://auratest.pl/pa-postep-eb460f6c3c4c12755858/

### E. Oferty bez mocy — ZAMKNIĘTE 03.09 (było 21, nie 3)
Pomiar na produkcji obalił założenie „brak danych w źródle". Ofert `publish` bez mocy było **21**,
a 18 ofert dongchedi nie miało w ogóle **wiersza** meta `_asiaauto_extra_prep`. Przyczyną były
trzy sita, każde z innego powodu:

| sito | co odrzucało | naprawa |
|---|---|---|
| `dolej-spec-z-banku.php` | `JOIN` na meta `_asiaauto_extra_prep` — oferta bez wiersza meta niewidzialna dla banku | `LEFT JOIN` + pusty wsad liczony jako 0 pól |
| `merge-spec-from-twin.php` | cel musiał mieć `n > 0` pól, więc oferta z zerem wypadała z listy celów | warunek `n < 100` (ochrona ofert ręcznych działa na `$reczny`, nie na liczbie pól) |
| `backfill-spec-autohome.php` | twardy filtr `source='che168'` | **nie ruszone** — patrz punkt K |

Moc nie wymagała nowego kodu: `uzupelnij-moc-km.php` ma ścieżkę #4 `engine_max_horsepower`
dla ICE i przelicza `system_max_power` kW × 1,36.

**Wykonane** (backup `~/backups/primaauto/2026-09-03/wp521-przed-naprawa-sit.sql.gz`, 42 MB):
bliźniak `apply` → 5 ofert z zera dostało 171–371 pól; bank `apply` → 9 ofert, +2 893 pola;
`uzupelnij-moc-km.php apply` → **14 ofert, 0 zmienionych istniejących**; `zbuduj-specs.php apply since=48h`.
Zweryfikowane oczami na żywo: oferta 371636 (przed: 0 pól) renderuje 14 sekcji specyfikacji,
moc na stronie zgadza się z meta i z tabelą specs. Wartości sprawdzone punktowo — MG 7 2.0T 261 KM,
Haval Big Dog 238 KM, Arrizo 8 1.6T 197 KM, YU7 4WD Max 691 KM (508 kW).

**Wynik: bez mocy 21 → 7, bez wiersza `extra_prep` 24 → 10** (z tego 6 to oferty ręczne,
chronione flagą `_asiaauto_manual_entry` w obu skryptach — celowo).

Dlaczego to bezpieczne: bliźniak dolewa pole tylko przy **konsensusie wszystkich dawców** tej samej
wersji (klucz `marka|seria|wersja|rocznik`), pomija listę `$VOLATILE`, nigdy nie nadpisuje
istniejącego klucza i weryfikuje zapis odczytem. Leapmotor D19 EREV nie dostał nic — mechanizm sam
odrzucił brak dokładnego dawcy, czyli ostrzeżenie „nie wpisywać z bliźniaków" pozostaje spełnione.

### K. Katalog Autohome dla dongchedi (nowa pozycja, 2–3 h)
Zostało **7 ofert bez mocy**: 2 świeże che168 (`460702` Zeekr 009, `460862` GWM Cannon — te weźmie
cron o 04:55, są w zakresie) i **5 dongchedi bez pokrycia w banku i u bliźniaka**: `374367` Zeekr 001
Max 103kWh, `388014` BYD Han L EV 701KM, `396408` Leapmotor D19 EREV, `418328` i `434500` BYD Han DM-i.
Dla nich zostaje czwarta droga — katalog Autohome po nazwie CN (brand → series → config, 3 requesty),
opisana w memory `reference_autohome_specid_recznie_dla_dongchedi`. `backfill-spec-autohome.php`
filtruje dziś twardo `source='che168'`, więc dongchedi nigdy go nie dostaje. To jedyny element
wymagający nowego kodu i zapytań na zewnątrz — świadomie oddzielony od poprawek sit.

## 2. Filtry — potem

### F. Nowe filtry z danych, których nie ma w UI (4–6 h)
Wsad: `docs/roadmapa/T-116-etap2-pola-i-wartosci-wyszukiwarki.md` sekcja 3. Kandydaci z pokryciem:
`battery_type` LFP/NMC/LMFP (80%), `driver_form` układ napędowy (99%), `jb` klasa pojazdu (99%,
mocno różnicujący), `high_voltage_fast_charging_platform_800` (29%), `automatic_drive_level`
L2/L2+ (93%), `battery_brand` CATL/FinDreams (80%).
**Jak nie rozwalić tego, co stoi:** kolumny przez `AsiaAuto_Specs_Table::install()` + `SHOW COLUMNS`;
każdy enum = słownik kanoniczny i raport nieznanych (bramka „0 nieznanych"); po dołożeniu pełny
`zbuduj-specs.php apply` **oraz** `porownaj-search.php` (0 rozjazdów); `test-ui-wyszukiwarka.mjs`
i `impeccable detect` na zerze. Reguły normalizacji: pusta wartość = NIE, `选配` = opcja,
wartości parowe „A / B" tylko po ` / ` ze spacjami.

### G. Kolejne pastylki z listy 129 (1–3 h)
`docs/roadmapa/T-116-kandydaci-pastylek.md`. **Janek podaje numery** — nie dobierać samodzielnie.
⚠️ **Numery z poprzedniego wyboru (15, 16, 27, 62, 76, 79, 87, 96, 101, 108) pochodzą ze starej
listy 117 kandydatów i DZIŚ WSKAZUJĄ CO INNEGO.** Po pomiarze 03.09 lista ma 129 pozycji, bez
wdrożonych — numeracja się przesunęła (wtedy 15 = `external_mirror_heat`, dziś 15 = `alloy_wheel`).
Nazwy wdrożonego wyboru są w `AsiaAuto_Specs_Table::FLAGS` (blok „wybór Janka z listy 117").
Numery bierz wyłącznie z aktualnego pliku. Pozycje > 85% pokrycia jako filtry
prawie nic nie zawężają. Procedura dodania jest w tym samym pliku, sekcja „Jak dodać pastylkę".

### H. Pomiar po dwóch tygodniach (~1 h, nie wcześniej niż 17.09)
Punkt odniesienia: `docs/sesje/2026-09-03-t250-pomiar-wiki.md` (słownik: 189 odsłon w 44 dni,
38 haseł bez odsłony, oferta → hasło 3 przejścia na 50 295 odsłon, 16 % sesji wiki idzie do oferty).
Zmierzyć tym samym skryptem (`scripts/ga4_query.py`): ruch `/wyszukiwarka/` i skąd przychodzi
(4 linki), odsłony `/wiki/*` po naprawie widoczności autolinków i po CTA z filtrem, przejścia
hasło → wyszukiwarka (przed zmianą: 0). Wynik do `docs/sesje/`.

## 3. Wydajność — na końcu

### I. LCP 7,7 s na telefonie (4–8 h, cały serwis)
PSI mobile 03.09: `/wyszukiwarka/` 68 pkt, LCP 7,7 s; `/samochody/` 70 pkt, LCP 7,8 s — **to nie
jest problem wyszukiwarki**, tylko motywu i karty oferty. „Nieużywany JS 228 KiB" to GTM + GA4 +
Ads. Zacząć od ustalenia elementu LCP na obu stronach, potem obrazy kart (rozmiary, `fetchpriority`,
lazy) i kolejność ładowania. Osobny task, nie mieszać z filtrami.

### J. Kontrola crona mocy (15 min, rano)
`~/.claude/napraw-moc-ukladu.log` — pierwszy automatyczny bieg 04.09 o 05:00, przed `zbuduj-specs`
o 05:05. Sprawdzić, czy podniósł moc nowym ofertom z nocnego importu i czy nie ruszył istniejących.

## 4. Czego nie ruszać

- `class-asiaauto-inventory.php`, importer, `renderCard()`, nazwy klas/CPT/meta/ról.
- Publikacja treści (hasła, posty) = **osobna zgoda Janka** za każdym razem.
- Indexing API tylko przez `~/bin/index-submit`; przy budowaniu list URL filtrować puste linie
  (`wp db query --skip-column-names` kończy wyjście pustą linią — 03.09 zjadło jedno zgłoszenie).
- Cron wyłącznie przez `~/bin/cron-install`. Backup `mysqldump` przed każdą zmianą danych.

## 5. Pułapki z tej sesji

1. **Kod działa, a użytkownik nic nie widzi** — trzy błędy tej klasy jednego dnia: autolinki bez
   podkreślenia (specyficzność), CTA gubiące filtr (`match()` przepisywał tablicę bez nowego klucza),
   12 haseł poza indeksem `/wiki/` (kategoria spoza listy w `CATEGORIES`). Żadnego nie złapały
   bramki liczbowe. **Po każdej zmianie sprawdź stronę oczami, nie tylko liczby.**
2. Sitemapa RankMath ma cache — po dodaniu treści `wp rankmath sitemap generate`, inaczej Google
   widzi stan sprzed dni (03.09: 90 wpisów z `lastmod` 04.08).
3. `claude -p` w pipelinie `scripts/kb/` **nie korzysta** z trybu obniżonego priorytetu sesji —
   przy limicie generacja pada w całości (0 z 23) i trzeba czekać na reset.
4. `display: flex` na przycisku zjada spacje między `<span>`; `opacity` na tekście psuje kontrast
   (axe 2,79:1) — szarzyć kolorem.
5. iOS Safari zoomuje przy fokusie na polu < 16 px i nie cofa powiększenia.
