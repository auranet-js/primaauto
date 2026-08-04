# Rekonesans: rankingi segmentowe + porównania (T-162/T-214 F4 × T-115)

> Data: 2026-08-04 · Wejście: 19 zrzutów WeChat (Drive `PrimaAuto/temp`) + pomysł Janka na rankingi segmentowe i porównania
> Źródła pomiaru: DataForSEO (Google Ads volume + Labs suggestions, PL/pl, koszt $0,17) · GSC 2026-05-03…2026-08-01 (9593 frazy) · baza produkcyjna (2089 ofert z `_asiaauto_extra_prep`)

---

## 1. Popyt — gdzie on naprawdę jest

### 1.1 Segmenty nadwozia wygrywają z parametrami technicznymi

| Fraza | DFS /mc | Nasz stan (GSC 90 dni) | Nasza podaż |
|---|---:|---|---:|
| chiński suv / chińskie suv | 5 400 | „najlepszy chiński suv" poz. 2,7 (3 wyśw.) | 1184 aut / 177 modeli |
| chińskie marki samochodów | 5 400 | poz. **15,6–43,3** (255 wyśw., 0 klik.) | 57 marek |
| **chiński pickup** | **1 900** | **„byd pickup" 890 wyśw., poz. 7,0, 16 klik.** | **7 aut / 4 modele** |
| chiński sedan | 1 300 | — | 636 aut / 84 modele |
| chiński van | 1 000 | „byd minivan" 39 wyśw. poz. 9,7 | 117 aut / 21 modeli |
| chińskie samochody elektryczne | 1 000 | — | 707 aut |
| chińskie limuzyny | 880 | — | 636 sedanów |
| chiński suv 7-osobowy | 590 | „jetour t2 7 osobowy" poz. 7,8 | do policzenia |
| **chińskie kombi** | **480** | — | **26 aut / 4 modele** |
| największy chiński suv (w polsce) | ~140 | **poz. 1,0 — 36 wyśw., 2 klik.** | mamy |

### 1.2 Rankingi „techniczne" mają mikropopyt jako frazy

| Fraza | DFS /mc |
|---|---:|
| najszybszy samochód elektryczny | 110 |
| największy zasięg samochodu elektrycznego | 70 |
| najdłuższy zasięg samochodu elektrycznego | 20 |
| samochód elektryczny 1000 km | 10 |
| największa bateria samochód elektryczny | 0 |

**Wniosek:** „najdłuższe zasięgi / największe baterie / najszybsze" **nie udźwigną osobnych artykułów jako cel SEO**. Ale mają dwa realne zastosowania:
1. **Tabele wewnątrz artykułów segmentowych** — to one dają artykułowi unikalność (nikt w PL nie ma tych liczb).
2. **AEO.** GSC pokazuje, że wchodzą do nas zapytania promptowe, na których stoimy na pozycji 1: *„które chińskie auta w polsce mają architekturę 800 V i najszybsze ładowanie na stacjach hpc"* (9 wyśw.), *„jakie chińskie modele mają najlepsze asystenty parkowania 360…"* (22 wyśw., poz. 6,3), *„które chińskie hybrydy plug-in mają najlepszy system zarządzania energią…"*. Ustrukturyzowane rankingi to dokładnie to, czym się karmi AI Overviews.

### 1.3 Pułapka intentu cenowego

Long-tail „chiński suv" to w większości **tanie auta**: *za 54 tys.* (880), *za 77 tys.* (880), *za 50 tys.* (390), *najtańszy* (320), *otomoto* (590), *olx* (210). Nasza oferta zaczyna się ~150 tys. zł. Ranking „chińskie SUV-y 2026" ściągnie ruch, który szuka MG ZS.

**Konsekwencja — decyzja Janka 2026-08-04: idziemy w head terms, nie w long-tail.** Budujemy mocny content na frazy główne (chiński suv, chińskie marki samochodów, chiński pickup, chińskie kombi), a intent cenowy filtrujemy *treścią* — artykuł mówi wprost, w jakim przedziale gramy, i prowadzi na huby. Modyfikatory (*premium*, *największy*, *7-osobowy*, *4x4 hybryda*) wchodzą jako H2/sekcje wewnątrz tych artykułów, nie jako osobne teksty.

---

### 1.4 Wykluczenia w Google Ads — dowód popytu kupiony za własne pieniądze

W kampanii **[DSA] Import modele z Chin** wykluczyliśmy dokładnie te frazy, o które chodzi:

| Wykluczenie | Dopasowanie |
|---|---|
| chińskie suvy | BROAD |
| chiński suv / chinski suv | PHRASE |
| chińskie kombi | **BROAD + EXACT** |
| kombi z chin | PHRASE |
| chiński pickup | BROAD |
| chinski sedan | BROAD |
| chińska hybryda | BROAD |
| chinski defender | BROAD |
| chińskie auto sportowe | EXACT |
| luksusowa limuzyna z chin | BROAD |
| chińskie auta / chińskie samochody | PHRASE |

Plus lista współdzielona **„Globalne negatywy info-intent"**: `ranking` (PHRASE), `porównanie` (PHRASE). Razem 164 negatywy campaign-level + 199 w listach współdzielonych.

**Jak te frazy zachowywały się, zanim je odcięliśmy** (konto 2026-04-22…2026-08-03, całe konto: 68 563 wyśw., 10 359 klik., 94,7 konw., 9 523 zł, CPC 0,92 zł):

| Grupa zapytań | Zapytań | Wyśw. | Klik. | CTR | Koszt | Konwersje |
|---|---:|---:|---:|---:|---:|---:|
| kombi | 17 | 119 | 16 | **13,4%** | 9 zł | **0** |
| pickup / terenowe | 59 | 242 | 30 | **12,4%** | 20 zł | **0** |
| 7-osobowe / van | 52 | 229 | 26 | **11,4%** | 16 zł | **0** |
| „chińskie …" generyczne | 280 | 1 045 | 136 | **13,0%** | 108 zł | **0** |
| sedan / limuzyna | 28 | 122 | 10 | 8,2% | 6 zł | **0** |
| ranking / porównanie | 4 | 5 | 0 | — | 0 zł | 0 |

Rekordziści CTR: „chińskie kombi" **55,6%**, „chiński jeep jetour" 29,4%, „chińskie minivany" 28,6%, „byd kombi" 64 wyświetlenia.

**Wniosek — decyzja w Ads była słuszna i jest jednocześnie uzasadnieniem contentu.** Ludzie klikają w te frazy ponadprzeciętnie, ale **nie kupują z nich od razu — 0 konwersji przy ~160 zł wydanych**. Płacić za nie w Ads nie ma sensu. Wziąć je organicznie — ma, bo koszt krańcowy jest zerowy, a to jest ruch top-funnel, który potem wraca na huby i oferty.

**Zapytania segmentowe przechodzą do dziś mimo wykluczeń** (90 dni: 364 zapytania, 1791 wyśw., 184 klik.) — popyt jest ciągły, nie jednorazowy.

---

## 1.5 Wzrost serwisu — punkt odniesienia

**Google Search Console (kliknięcia miesięcznie, domena po cutoverze 2026-04-21):**

| Miesiąc | Kliki | Wyświetlenia | CTR | Śr. pozycja |
|---|---:|---:|---:|---:|
| 2026-04 (od 21.) | 66 | 849 | 7,8% | 7,4 |
| 2026-05 | 1 512 | 36 687 | 4,1% | 7,2 |
| 2026-06 | 2 853 | 66 765 | 4,3% | 6,7 |
| 2026-07 | **9 577** | 187 047 | 5,1% | 5,7 |
| 2026-08 (2 dni) | 1 143 | 17 376 | 6,6% | **5,4** |

Ostatnie 28 dni: **9 558 klik., 181 856 wyśw., poz. 5,6** — wobec 3 602 klik. w poprzednich 28 dniach. **+165% miesiąc do miesiąca**, przy jednoczesnej poprawie pozycji z 6,5 na 5,6 i CTR z 4,5% na 5,3%.

**Struktura tego ruchu (28 dni, 1761 URL-i):**

| Typ strony | Kliki | Wyświetlenia | URL-i |
|---|---:|---:|---:|
| huby marka/model | **8 158** | 171 946 | 402 |
| strona główna | 620 | 4 765 | 1 |
| **aktualności** | **413** | 3 578 | **36** |
| oferty | 282 | 8 363 | 1 228 |
| informacje | 88 | 3 153 | 9 |
| słownik /wiki/ | 40 | 2 330 | 68 |

**Google Ads (konto od 2026-04-22):**

| Miesiąc | Wyświetlenia | Kliki | CTR | Koszt | CPC | Konwersje |
|---|---:|---:|---:|---:|---:|---:|
| 2026-04 | 5 040 | 661 | 13,1% | 715 zł | 1,08 zł | 2,0 |
| 2026-05 | 14 921 | 3 185 | 21,3% | 3 179 zł | 1,00 zł | 38,0 |
| 2026-06 | 40 217 | 4 342 | 10,8% | 3 277 zł | 0,75 zł | 54,1 |
| 2026-07 | 134 825 | 6 514 | 4,8% | 4 383 zł | 0,67 zł | **92,9** |

**Dwa fakty, które ustawiają priorytet:**

1. **Organiczny ruch przerósł płatny.** Lipiec: 9 577 klików z Google za darmo wobec 6 514 klików za 4 383 zł. CPC spadł 1,08 → 0,67 zł i konwersje rosną, więc Ads działa — ale skala przyrostu jest teraz po stronie SEO.
2. **Świeży content wchodzi szybko.** 36 wpisów w Aktualnościach, publikowanych od 2026-07-21, zrobiło **413 kliknięć w niecałe dwa tygodnie** — więcej niż 1228 stron ofert (282). Przy 402 hubach robiących 85% ruchu widać, gdzie leży dźwignia: strony tematyczne, nie egzemplarze.

Jeśli rankingi segmentowe wejdą na head terms (chiński suv 5 400/mc, chińskie marki samochodów 5 400/mc, chiński pickup 1 900/mc) tak, jak weszły newsy, mówimy o rzędzie **kilkuset do ~1000 klików/mc na artykuł** przy pozycjach 2–5 — szacunek na podstawie CTR 5,3%, jaki mamy dziś na pozycji 5,6, i wolumenu DFS, do zweryfikowania pomiarem po 30 dniach.

---

## 2. Dane — mamy własny silnik rankingowy, nie potrzebujemy chińskich zrzutów

Pokrycie pól numerycznych w `_asiaauto_extra_prep` (2089 ofert, **0 uszkodzonych JSON-ów**):

| Pole | Pokrycie |
|---|---:|
| `length` / wymiary | 97,5% |
| `max_speed` | 97,1% |
| `acceleration_time` (0–100) | 78,7% |
| `battery_type` / `total_electric_power` | 76,0% |
| `battery_capacity` (kWh) | 75,8% |
| `cltc_recharge_mileage` | 72,3% |
| **`wltc_recharge_mileage`** | **41,3%** |
| `max_fast_charge_power` | 41,4% |

**Mamy zasięg WLTC w danych.** To zdejmuje główne ryzyko merytoryczne rankingów zasięgowych — nie musimy publikować chińskiego CLTC ani go szacować.

Próbne TOP-listy wygenerowane z bazy (deduplikacja po modelu, cena = nasza cena PL):

- **Zasięg WLTC:** Zeekr 007 GT 590 km · Li Auto i8 585 · Li Auto MEGA 575
- **Bateria:** Geely LEVC L380 140 kWh · Denza Z9 GT 122,5 · HiPhi Z 120 · Avatr 11 116,8
- **0–100:** Zeekr 001 FR 2,02 s · Xiaomi SU7 Ultra 2,1 · Hyptec SSR 2,3 · Yangwang U9 2,36
- **Ładowanie:** BYD Han L EV i Sealion 8 — **1000 kW** · Hyptec HL 560 · Zeekr 009 560
- **SUV wg zasięgu CLTC:** Xiaomi YU7 835 · Hyptec HT 825 · Avatr 11 815 · Luxeed R7 802
- **Kombi (komplet):** Shangjie Z7T 776 · Zeekr 007 GT 650 · Audi E5 Sportback 647 · NIO ET5 Touring 550

### Dwie pułapki w danych — do obsłużenia w generatorze

1. **PHEV/EREV mieszają zasięg elektryczny z łącznym.** Denza Z9 GT DM-i ma w `cltc_recharge_mileage` wartość 1036 km — to zasięg łączny, nie elektryczny. Ranking musi rozdzielać wiersze per typ napędu i dla hybryd brać osobne pole.
2. **Filtrowanie po nazwie napędu jest zdradliwe** — `Hybryda plug-in (PHEV)` zawiera ciąg „EV". Filtry po `fuel` muszą iść po slugu termu, nie po `str_contains`.

Unikalnych modeli: SUV 177 · Sedan 84 · Minivan 21 · Liftback 17 · **Kombi 4** · Pickup 4.

**Kombi:** 480 wyszukań/mc przy 4 modelach w ofercie. To nie jest „TOP 10 chińskich kombi" — to artykuł *„Chińskie kombi — dlaczego prawie ich nie ma i co da się kupić"*. Uczciwy, zeruje konkurencję (nisza pusta — DFS nie zwrócił ani jednego long-tail), ale nie udawajmy rankingu.

---

## 3. Źródła chińskie

**Mamy już infrastrukturę do ciągnięcia danych programowo:**
- `scripts/autohome-catalog-fetch.js` — dekoder katalogu Autohome, **292 parametry na model**, zdejmuje antyscraping bez headless Chrome.
- `data/autohome-catalog-map.php` — most nazw CN → nasze klucze.
- Feed dongchedi + che168 przez auto-api.

Do sprawdzenia jako źródło danych sprzedażowych (1 sonda): **CPCA 乘联会** (cpcadata.com.cn) i CAAM — oficjalne, miesięczne, cytowalne. Wtórnie: carnewschina.com i cnevpost.com, które już są w radarze newsowym T-214.

**Status zrzutów WeChat:** to **trop tematyczny, nie źródło**. Dwa zrzuty tej samej listy Yiche mają różne wartości punktacji (złapane w trakcie animacji), a twórcy (中汽数研, 电车说, 车之榜) nie podają metodologii. Liczby z nich wolno użyć wyłącznie po potwierdzeniu u źródła pierwotnego.

**Zdjęcia** — zgodnie z ustaleniem: własne galerie ofert (2135 ofert ze zdjęciami) + materiały prasowe producentów. Cudzych kart/infografik nie kopiujemy nawet z podpisem — cytat legalizuje **dane i fragment tekstu**, nie cudzą grafikę.

---

## 4. Porównania — GSC mówi, że popyt już jest

Frazy porównawcze, na których **już się wyświetlamy, nie mając ani jednego artykułu**:

| Fraza | Wyśw. | Poz. |
|---|---:|---:|
| denza b5 vs leopard 5 | 16 | 4,1 |
| zeekr 8x vs 9x | 15 | 4,1 |
| leopard 5 vs jetour t2 | 5 | 1,0 |
| **geely monjaro vs volvo xc60** | **4** | **2,0** |
| byd leopard 5 vs 8 | 3 | 1,0 |
| byd leopard 8 vs jetour g700 | 2 | 1,0 |
| cltc vs wltp | 3 | 4,7 |

Dwa wnioski:
1. **Ludzie porównują nasze modele między sobą** — to bezpośrednie uzasadnienie T-115 (porównywarka) i porównań wewnątrzofertowych.
2. **Intuicja Janka co do Monjaro trafia w punkt.** „geely monjaro" = 3600/mc, „geely monjaro kiedy w polsce" 390, „gdzie kupić" 40 — a **mamy 33 Monjaro na stanie** i już stoimy na pozycji 2 na frazie vs XC60. Volvo XC60 samo w sobie ma 165 000/mc.

### 4.1 Jak spiąć blog z porównywarką (T-115)

Jedna warstwa danych, dwa wyjścia:

| | Blog `/porownania/{a}-vs-{b}/` | Narzędzie `/porownaj/` |
|---|---|---|
| Zawartość | 20–30 kuratorowanych par + narracja, werdykt, cena PL | dowolna kombinacja, do 4 aut |
| SEO | indeksowane, to jest cel ruchu | **noindex** (twardy wymóg T-115 — index bloat) |
| Rola | wejście z Google/AI | konwersja, czas na stronie |
| Wspólne | ta sama tabela specs (T-116) + **ten sam renderer tabeli różnic** | |

Linkowanie dwukierunkowe: artykuł kończy się „porównaj sam inne auta" → narzędzie; narzędzie przy parze, dla której mamy artykuł, podlinkowuje artykuł.

**To obniża koszt T-115** — renderer różnic i tak powstanie przy pierwszych porównaniach blogowych, więc porównywarka zostaje głównie UI (tak jak zakłada jej plan).

### 4.2 Bloker do rozwiązania: rywale spoza Chin

XC60, Sportage, Tiguan nie istnieją w naszej bazie. Bez ich danych porównania „chińskie vs europejskie" — czyli te z największym wolumenem — są niewykonalne.

Rekomendacja: **mała tabela referencyjna ~40 aut EU/KR/JP** (najpopularniejsze w PL wg rejestracji), uzupełniana ręcznie z danych producenta, tylko parametry z naszej siatki porównawczej. Nie API, nie scraping — 40 rekordów × ~25 pól to jednorazowo kilka godzin, a odblokowuje cały filar „vs Europa".

---

## 5. Rekomendowana kolejność

**Pilot (przed budową silnika) — 3 artykuły, pomiar po 30 dniach:**

1. **„Chińskie pickupy"** — 1900/mc w DFS, 890 wyświetleń w GSC na poz. 7, zero treści u nas. Najostrzejsza luka podaż/popyt jaką widać w danych.
2. **„Największe chińskie SUV-y dostępne w Polsce"** — już stoimy poz. 1,0 bez artykułu; artykuł zamienia wyświetlenia na kliknięcia.
3. **„Geely Monjaro vs Volvo XC60"** — pilot formatu porównawczego, 33 auta na stanie, poz. 2,0 bez treści. Wymaga rekordu referencyjnego XC60 (pierwszy wpis do tabeli z §4.2).

**Potem, jeśli pilot mierzy się dobrze:** silnik F4 (szablon rankingu + generator z bazy + gate kanibalizacyjny GSC) i seria: 7-osobowe, marki chińskie (5400/mc, stoimy 15–43 — najgorszy rozjazd w całym pomiarze), sedany/limuzyny, kombi jako artykuł niszowy.

**Rankingi techniczne** (zasięg WLTC, bateria, ładowanie, 0–100) — jako **tabele w artykułach segmentowych** i osobne strony wyłącznie pod AEO, nie pod wolumen.

## 6. Otwarte pytania

- Czy zaczynamy od pilota (3 teksty ręcznie), czy od razu budujemy silnik F4?
- Tabela referencyjna aut EU — robimy 40 pozycji od razu, czy 5 pod pierwsze porównania?
- Sonda CPCA jako źródło danych sprzedażowych — robimy?
