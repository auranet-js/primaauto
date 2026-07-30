# T-222 — Migracja źródła: wygaszenie dongchedi, przejęcie przez che168

> **Data:** 2026-07-30
> **Status:** spec do akceptu
> **Termin twardy:** 15.08.2026 — koniec opłaconego okresu auto-api; wtedy decyzja o pozostawieniu jednego źródła
> **Kierunek:** che168 (decyzja Janka 30.07)
> **Poprzednik:** T-186 (`docs/roadmapa/T-186-che168-automat.md`)

---

## 1. Po co

Nie finansujemy dwóch feedów auto-api naraz. Do 15.08 trzeba przenieść ofertę z dongchedi na che168 tak, żeby:

- zapas dongchedi **wygasł prawdziwie** (nie zgnił jako oferty aut już sprzedanych),
- wiedza słownikowa z dongchedi **nie przepadła** razem ze źródłem,
- che168 dowoził wolumen porównywalny z dongchedi,
- oferty che168 miały pełną specyfikację **w chwili publikacji**, a nie po dobie.

**Dlaczego che168, a nie dongchedi:** crawler dongchedi był awaryjny przez ostatnie dwa miesiące (trzy pady w czerwcu, zamrożenie feedu 01–29.07), a od 29.07 dowozi nowe oferty z **43 polami zamiast ~342** — regresja nienaprawiona od miesiąca. Dostawca sam kieruje na che168 od maja. Świadomy koszt tej decyzji: dongchedi ma **większy wolumen** (234 vs 34 oferty w ostatniej dobie) i po dolaniu z banku **bogatszą specyfikację** (mediana 346 vs 98 pól). Wybieramy stabilność źródła, nie jego dzisiejszą wydajność.

---

## 2. Stan zastany (pomiar 2026-07-30, produkcja)

### Zapas

| | dongchedi | che168 |
|---|---|---|
| `publish` | 2562 | ~200 |
| `draft` | 591 | 74 |
| martwe do wygaszenia (werdykt przemiału) | 1066 | — |
| żywe po odjęciu martwych | ~1496 | ~200 |

Crony z 29.07 **działają** (wbrew notatce w memory, że gaszenie nie ruszyło):
`gasz-martwe-oferty.php` o 4:25 zdjął pierwsze 200; `dolej-spec-z-banku.php` o 4:45 uzupełnił 82 oferty.
Przy 200/dobę pula 1066 martwych schodzi do ~5.08.

### Wejście z ostatniej doby — 268 ofert

| | dongchedi (234) | che168 (34) |
|---|---|---|
| `extra_prep` z API (mediana) | 42 | 89 |
| `extra_prep` po banku (mediana) | **346** | 98 |
| trafienie dokładne w banku | **81%** | **3%** |
| ma `_asiaauto_spec_id` | 0 | **100%** |

### Bank specyfikacji vs che168 — problem nazewnictwa, nie danych

Bank: **933 klucze**, 294 serie, 809 unikalnych stringów wersji.
che168 w bazie: 282 oferty, 196 kluczy, **117 serii**, 173 stringi wersji.

Rozbicie braków dla che168 (ostatnia doba, 34 oferty):

| kategoria | udział |
|---|---|
| trafienie dokładne | 3% |
| ta sama wersja, inny rocznik w banku | 9% |
| **seria w banku, ale inna nazwa wersji** | **82%** |
| banku nie zna tej serii wcale | 6% |

Dane **są** w banku — oba źródła tłumaczą tę samą chińską wersję na inny angielski string:

```
che168: zeekr|001    | we 100kwh rwd [2024]
bank  : zeekr|001    | we edition 100kwh rwd [2024]          ← to samo auto

che168: xpeng|g6     | 625 long range max flagship [2025]
bank  : xpeng|g6     | bev 625 long range max flagship [2025] ← to samo auto
```

Poluzowanie dopasowania jest **odrzucone** — w tym samym zestawie siedzi pułapka:

```
che168: voyah|free   | free+ 4WD kunlun [2025]
bank  : voyah|free   | free+ RWD kunlun [2025]                ← INNE auto
```

Fuzzy match wkleiłby specyfikację tylnonapędową do auta 4×4. To dokładnie ta kontaminacja,
z którą walczyliśmy przy spec-hubach (`reference_spec_hub_detector_dimension_variance`).

### Co API dongchedi jeszcze ma do oddania — sonda 29 wywołań

Hipoteza wyjściowa („dla starych rekordów `getOffer` oddaje komplet, więc dociągnijmy 190 ubogich ofert")
okazała się **źle zaadresowana**. Pierwsza sonda 22 wywołań dała zero trafień — bo próbka objęła
najnowsze rekordy, a te miały werdykt `usunieta`. Dla martwych ofert API zwraca `null` (8/8).
Dla świeżo zaimportowanych z regresją API zwraca dokładnie tyle, ile mamy (10/10 po 41–43 pola).

Druga sonda, celowana w oferty z werdyktem **`zywa`** i zerowym `extra_prep`, dała **3 trafienia
na 7** — po 384, 292 i 399 pól. Czyli te oferty są ubogie **nie przez źródło, tylko przez nasz
nieudany import** (ścieżka `getOffer failed → partial data z /changes`).

Rozkład celu — `dongchedi` `publish`, werdykt × bogactwo:

| werdykt | 0 pól | 1–99 pól | 100+ pól |
|---|---|---|---|
| `zywa` | **43** | **13** | 1209 |
| `usunieta` | 80 | 1 | 980 |
| `wydmuszka` | 1 | 0 | 4 |
| brak werdyktu | **3** | **55** | 171 |

**Cel żniwa: 114 ofert** (żywe + bez werdyktu, <100 pól). Z tego 55 to świeże rekordy z regresją
43-polową — dla nich API nic nie doda. Realny zysk to głównie 43 oferty `zywa` z zerową
specyfikacją, przy trafialności ~43% z sondy → **ok. 20–25 napraw po ~350 pól**.

### Regresja 43 pól NIE zagraża istniejącym ofertom

Sprawdzone w kodzie: `updateListing()` (ścieżka `changed`) dotyka wyłącznie ceny, przebiegu,
zdjęć i `_asiaauto_last_sync` — **nie zapisuje `extra_prep`**. `importListing()` z kolei wychodzi
przez `return $existing` dla znanego `inner_id` (poza trybem `force`). Nie ma więc ścieżki, którą
43-polowa odpowiedź API nadpisałaby bogatą specyfikację. Ryzyko utraty danych: brak.

### Filtry importu — różnica jest jedna

`asiaauto_import_config` trzyma oba źródła. Rocznik ≥2024, przebieg ≤40 000 km, cena ≥85 000 ¥,
te same 31 miast — **identyczne**. Różni się wyłącznie lista marek: **dongchedi 57, che168 12**
(+ 2 wpisy `model_blacklist` po stronie che168).

---

## 3. Decyzje

| # | Decyzja | Uzasadnienie |
|---|---|---|
| D1 | Dongchedi przechodzi w tryb **`verify`**, nie jest odcinane od razu | Po odcięciu API nie ma czym sprawdzić, które z ~1496 żywych ofert istnieją. Oferta na dongchedi żyje 2–4 tygodnie (rozkład wieku martwych: 15–30 dni → 800 szt.), więc bez weryfikacji cały zapas zgnije w ~6 tygodni i nie będziemy wiedzieć które. |
| D2 | Specyfikacja che168 idzie z **katalogu Autohome po `spec_id`**, nie z mapowania nazw | `spec_id` to dokładny identyfikator wersji — zero ryzyka RWD/4WD. Ma go 186/202 ofert (92%) i 100% z ostatniej doby. Katalog daje ~290 parametrów, więcej niż bank. |
| D3 | **Bank douczna się z Autohome** | Bank jest dziś zamrożonym zrzutem dongchedi. Po wzbogaceniu oferty che168 wynik wraca do banku pod kluczem w nazewnictwie che168 — po kilku tygodniach bank ma własne pokrycie che168, bez ani jednej linii tablicy mapowań. To odpowiedź na „po odcięciu i tak trzeba będzie bank kompletować na che168". |
| D4 | **Nie budujemy tablicy mapowań** 113 par | Policzone: 113 par dla dzisiejszych 117 serii. Ale przy 57 markach che168 dojdzie do skali dongchedi (302 serie, 868 stringów wersji) → 600–800 par, rosnących z każdym rocznikiem. Do tego 29 serii che168 nie ma w banku odpowiednika i nigdy nie będzie miało (BAIC BJ60, Lynk & Co Z20/Z10/06 EM-P, Volvo EM90/S90, Audi E7X, Mercedes EQE SUV, Foton Tunland V9/V7, Maxus Interstellar, iCAR V27, IM Motors LS9). `spec_id` pokrywa i jedno, i drugie. |
| D5 | Marki che168 **12 → 57 naraz**, gdy kaskada spec jest gotowa | Do 15.08 trzeba odbudować ofertę; che168 musi dowozić ~90–100/dobę wobec dzisiejszych 34. Świadomy koszt: sonda jakości z 28.07 przestaje działać — błędy mapowania i rozjazdy hubów wyjdą z monitora po fakcie, wymieszane. |
| D6 | Cronów **nie dokładamy — odejmujemy** (10 → 8) | Rozłażą się. Bank wchodzi inline do importu (kod pluginu, zero crona), kolejka Autohome jedzie na ogonie istniejącego synca. |

---

## 4. Architektura

### A. Tryb `verify` dla dongchedi

Nowa opcja `asiaauto_sync_mode_dongchedi` (`full` | `verify`, domyślnie `full`).
W `AsiaAuto_Sync::run()` dwa `if`-y:

- `case 'added'` → pomiń, licz jako `skipped`,
- `case 'changed'` gałąź „nie ma lokalnie" → pomiń zamiast `importWithFullData()`.

Bez zmian: `changed` na istniejących ofertach (aktualizacja ceny) i `removed`
(`AsiaAuto_Rotation::markRemoved()` → `draft` → 301 na hub modelu przez
`class-asiaauto-redirects.php`). To jest mechanizm naturalnego wygaszania.

Zmiana addytywna — nie dotyka strefy kruchej „importer/sync", dokłada gałąź obok.

**Wariant bez kodu — filtr niemożliwy (uwaga Janka, 30.07).** Ten sam efekt osiąga się z panelu,
ustawiając kryterium, którego nic nie spełnia: `price_from` = 999999999 albo `year_from` = 2030.
Weryfikacja działa dalej **niezależnie od filtrów** — `removed` idzie `findByInnerId()` →
`markRemoved()`, `changed` na istniejącej ofercie idzie do `updateListing()`; żadna z tych ścieżek
nie woła `isAllowedByConfig()`. Filtry bramkują wyłącznie `importListing()` (`:89`) i prefilter
che168 (`class-asiaauto-sync.php:338`).

Przewaga tej drogi: **działa z panelu, bez dostępu do plików** — właściwa ścieżka, gdy trzeba
zatrzymać dopływ na innym źródle albo cudzymi rękami.
Przewaga flagi: odcina **przed** `getOffer()`. Dla dongchedi nie ma prefiltra, więc przy zwykłych
filtrach każde zdarzenie `added` kosztuje pobranie, które potem odrzucamy (~130 na bieg 08:36 →
0 na biegu 08:40 po fladze). To różnica kosztowa, nie funkcjonalna.

⚠️ **Czego NIE robić: czyścić filtrów ani ustawiać `enabled=false`.** `isAllowedByConfig()` jest
bramkowane `!empty()` na każdym kroku, a pierwsza linia brzmi
`if (empty($source_config) || empty($source_config['enabled'])) return true;` — pusty konfig i
wyłączony `enabled` znaczą **„nie filtruj"**, czyli wpuść cały strumień, a nie „nie importuj".

### B. Żniwo z dongchedi przed 15.08

Jednorazowy skrypt `scripts/zniwo-spec-dongchedi.php`, **celowany w werdykt `zywa` / brak werdyktu
i <100 pól** — 114 kandydatów, 114 wywołań. Adresowanie po werdykcie jest istotne: dla ofert
`usunieta` API zwraca `null` (potwierdzone 8/8) i każde takie wywołanie byłoby zmarnowane.

Oczekiwany zysk realistycznie **20–25 napraw po ~350 pól** (trafialność 3/7 z sondy). To naprawa
**naszych** nieudanych importów, nie wyciąganie czegoś, czego źródło jeszcze nie oddało.
Wynik: uzupełnienie oferty **i** wsyp do banku.

Krok tani i mały, ale po 15.08 nieodwracalny — dlatego zostaje w planie mimo skromnego zysku.
Dry-run obowiązkowy (>20 iteracji).

### C. Filtry che168 do parytetu

`asiaauto_import_config['che168']['marks']` 12 → 57 (lista z dongchedi).
Backup opcji przed zmianą (wzorem `asiaauto_import_config_che168_backup_2026_07_22`).

⚠️ **Guard mapowania.** che168 ma `isMappedForImport()`, którego dongchedi nie ma — marka bez wpisu
w `che168-model-map.php` / `brand-mapping` da **zero ofert** mimo przejścia filtrów.
Krok wymaga przerobienia kolejki `asiaauto_che168_unmapped` po pierwszej dobie.

### D. Kaskada specyfikacji — `AsiaAuto_Spec_Enricher`

Nowa klasa, wołana z `AsiaAuto_Sync::importWithFullData()` zaraz po `importListing()`.

**Krok 1 — bank, dopasowanie wyłącznie dokładne** (inline, plik JSON, ~0 ms; sync nie zwalnia).
Klucz `marka|seria|wersja|rocznik`, rocznik z meta **`ca-year`**. Nie nadpisuje istniejących kluczy.

**Krok 2 — kolejka Autohome.** Jeśli po banku nadal <100 pól i jest `_asiaauto_spec_id` →
`post_id` do kolejki (opcja `asiaauto_spec_queue`).

**Krok 3 — przerobienie kolejki na ogonie synca**, 5 sztuk na bieg. Przy biegu co 15 min daje
480/dobę zapasu wobec dzisiejszych 34 ofert che168. Cache katalogu w
`uploads/asiaauto/autohome-catalog/{spec_id}.json` — ten sam `spec_id` pobierany **raz na zawsze**;
przy powtórkach wersji koszt sieciowy spada do zera. Zero nowych cronów.

**Krok 4 — bank się douczna.** Po wzbogaceniu, jeśli oferta jest bogatsza niż wpis w banku dla
swojego klucza, wpis w banku zostaje zaktualizowany (klucz w nazewnictwie che168).

Zasady wspólne dla wszystkich kroków, zgodne z istniejącymi skryptami: nigdy nie nadpisujemy
istniejącego klucza; oryginał do `_asiaauto_extra_prep_orig` przed pierwszą zmianą; pomijamy oferty
ręczne (`_asiaauto_manual_import` / `_asiaauto_manual_entry`); stempel audytowy per ścieżka.

`scripts/merge-spec-from-twin.php` i `merge-spec-from-dongchedi-twin.php` **odpadają** — bank jest
ich nadzbiorem (zawiera też 319 wariantów-sierot z ofert już wygaszonych).

### E. Konsolidacja cronów: 10 → 8

Scalenie w jeden **„konserwator zapasu"** o 4:25, trzy kroki:
1. gaszenie 200 martwych (dziś osobny cron 4:25),
2. dosprzątanie kolejki spec — to, czego sync nie zdążył (dziś osobny cron 4:45),
3. douczenie banku.

Kasujemy martwy `dsa-offer-report` (jednorazowy, `0 13 17 7 *` — data minęła 17.07, mieli w kółko).

Zmiany crontaba **wyłącznie przez `~/bin/cron-install`** (strażnik po incydentach 12.07 i 22.07).

---

## 5. Kolejność wykonania

| kiedy | krok | dlaczego wtedy |
|---|---|---|
| dziś–jutro | **D** kaskada spec | musi być gotowa **zanim** puścimy większy strumień |
| jutro | **A** tryb `verify` | koniec importu nowych z dongchedi |
| do ~2.08 | **B** żniwo 114 ofert | termin twardy 15.08 |
| po D+B | **C** marki 12 → 57 | dopiero gdy kaskada przyjmie strumień |
| ~5.08 | — | pula 1066 martwych wyczyszczona |
| po C | **E** konsolidacja cronów | gdy kolejka spec ma zmierzony przerób |
| 15.08 | decyzja o subskrypcji + reguła dla resztek dongchedi | patrz ryzyko R1 |

---

## 6. Ryzyka

| # | Ryzyko | Reakcja |
|---|---|---|
| **R1** | **Po 15.08 nie ma czym weryfikować resztek dongchedi.** Tryb `verify` zdejmie sporo do 15.08, ale reszta zostanie bez możliwości sprawdzenia. | **Termin ustalony: weryfikacja 10.08** (5 dni zapasu na wykonanie, nie na podjęcie decyzji). Warianty A/reguła wieku ÷ B/twarde wygaszenie ÷ C/przedłużenie subskrypcji, komendy pomiarowe i „jak wygląda zrobione" w `docs/przypomnienia/2026-08-10-t222-decyzja-o-resztkach-dongchedi.md`. Event na kalendarzu „Auranet Claude". |
| ~~**R2**~~ | ~~Podaż che168 przy 60 markach nieznana.~~ | **ZAAKCEPTOWANE przez Janka 30.07** („będzie dobrze"). Nie blokuje. Liczba i tak zostaje w krokach pomiarowych przypomnienia z 10.08, bo od niej zależy wybór wariantu w R1. |
| **R3** | **Antyscraping Autohome.** Katalog obchodzi ochronę przez uruchomienie deszyfratora ze stubem DOM. Blokada = utrata jedynego źródła wyposażenia che168. | Cache per `spec_id` jest warstwą odporności — raz pobrane zostaje nasze. Przy blokadzie fallback: tablica mapowań 113 par (D4, świadomie odłożona). |
| **R4** | **57 marek naraz gubi sondę jakości** (D5, świadomy koszt). | `che168-monitor.php` po pierwszej dobie: rozjazdy hubów, orphany mapowania, kompletność spec. |
| **R5** | Kolejka spec rośnie szybciej, niż sync ją przerabia. | Krok 2 konserwatora zapasu dosprząta nadmiar nocą. Przy przepełnieniu: podnieść batch z 5. |
| **R6** | **Werdykty przemiału starzeją się.** `zywa` pochodzi z 29.07, a oferta żyje 2–4 tygodnie; 229 ofert nie ma werdyktu wcale (weszły po przemiale). Gaszenie bierze wyłącznie `usunieta\|wydmuszka`, więc oferta, która umrze po 29.07, nie trafi do puli. | Samo się leczy dopóki mamy API: tryb `verify` łapie `removed` na bieżąco i woła `markRemoved()`. Problem wraca dopiero po 15.08 — i wtedy zbiega się z R1, więc reguła wieku musi objąć **cały** zapas dongchedi, nie tylko resztki z przemiału. |

---

## 7. Poza zakresem

- Tablica mapowań 113 par che168 ↔ bank (D4) — tylko jako fallback dla R3.
- Reklamacja u auto-api za 43-polową regresję dongchedi (materiał w
  `reference_dongchedi_nowe_oferty_uboga_specyfikacja`) — osobny wątek handlowy.
- Reguła wieku dla resztek dongchedi po 15.08 (R1) — osobna decyzja przed terminem.
- Zmiana nazw klas / CPT / meta — slugi `asiaauto-*` zostają (CLAUDE.md §1).
