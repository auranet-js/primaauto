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

### A. Wybór wariantu autolinku (decyzja Janka, potem ~0,5 h)
Makieta: `https://auratest.pl/fe4f58fec53ctmp/primaauto-makiety/autolinki-warianty.html`
(generator `docs/makiety/gen-autolink.py`). **Kontekst zmieniony 03.09:** autolinki na ofertach
i hubach **w ogóle nie miały podkreślenia** (`.aa-autolink` 0-1-0 przegrywał z `.aa-single a`
i `.aa-hub__body a`) — naprawione w motywie 1.3.5, więc wariant A dopiero teraz istnieje naprawdę.
Janek ogląda ofertę na żywo i decyduje: zostaje A, czy wchodzi B / C / D.

### B. Ikona „i" przy pastylkach (decyzja Janka, potem 1–2 h)
Trzy warianty na tej samej makiecie. Rekomendacja: osobna ikona obok pastylki (dwa cele dotykowe;
link **wewnątrz** etykiety = `nested-interactive` w axe). Wdrożenie addytywne, po nim axe 320/1366.

### C. Weryfikacja poprawki iOS (Janek)
0.37.11 dał 16 px na wszystkich polach przy ≤ 768 px i zdjął autofokus w liście na dotyku —
typowa przyczyna „ekran się rozjeżdża" na iPhonie. **Headless Chrome tego nie odtwarza**, więc
potwierdzenie tylko z telefonu Janka (iPhone 15 Pro). Jeśli zoom nadal jest: sprawdzić `viewport`
w motywie i `user-scalable`, potem szerokość arkusza listy przy otwartej klawiaturze.

### D. Strona postępu — akcept godzin, wysyłka, commit (0,5 h)
Wpisy już są w `dane/postep.json` (kopia poprzedniego stanu: `dane/postep.json.bak-2026-09-03`),
strona zbudowana lokalnie. **Do zrobienia:** (1) Janek akceptuje albo koryguje godziny — zostały
policzone z rozpiętości commitów w danym dniu, nie z jego realnego czasu; (2) decyzja, czy prace
przy Meta i Ads (28 i 31.08, razem 12 h) zostają na tej stronie, czy są rozliczane osobno;
(3) `python3 build_postep.py --deploy`; (4) commit `postep.json` + `postep.html`.
⚠️ Memory `feedback_nie_pisz_rozliczone_o_godzinach` — dla Ruslana godziny są „zrealizowane".

### E. Trzy oferty bez mocy (Ruslan)
`396408` Leapmotor D19 2026 EREV (pusty `extra_prep`), `418328` i `434500` BYD Han DM-i 2025
„1.5T Auto FWD 5 Seater Edition". **Nie wpisywać z bliźniaków** — moc idzie do umowy PDF
(`class-asiaauto-contract.php:423`), a bliźniak D19 to wersja BEV. Prośba do Ruslana o wpisanie
w panelu (edytor listingu, pole „Moc (KM)").

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
`docs/roadmapa/T-116-kandydaci-pastylek.md`. **Janek podaje numery** — nie dobierać samodzielnie
(wybór z 03.09: 15, 16, 27, 62, 76, 79, 87, 96, 101, 108). Pozycje > 85% pokrycia jako filtry
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
