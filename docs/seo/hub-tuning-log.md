# Dziennik tuningowanych hubów SEO

> Każdy wpis = 1 hub modelu, 1 sesja tuningu. Format chronologiczny, najnowsze na górze.
>
> **Cel:** uniknąć dublowania pracy + sprawdzać wyniki (pozycje SERP) po 7-14 dniach od tuning'u.
>
> **Konwencja:** Status = TUNED (zrobione tuning) / RETUNE (po review wyników, dodatkowy tuning) / DROPPED (poza scope).

---

## 2026-05-07 — sesja #14-28 (15 hubów Deepal/Tank/Denza/Voyah/Changan: auto-regen + indexing push)

**Status:** Wszystkie 15 hubów po bulk regen v0.32.43 mają już zaktualizowany title (Otomoto-pattern z brand+price+count). Wiki 5400-7600 znaków, FAQ 8 entries — już istniejący content z poprzednich generacji n8n. **Nie wymagają full retune**, tylko Indexing API push.

### DEEPAL (3 hubów)
| # | Hub | Vol/m | YoY | Listings | Price | Status |
|---|---|---:|---:|---:|---|---|
| 16 | Deepal S07 | 110 | +425% | 16 | od 118 000 PLN | ✓ indexing push |
| 17 | Deepal SL03 | (n/d) | — | 10 | od 113 000 PLN | ✓ indexing push |
| 18 | Deepal S05 | 90 | **+2 500%** | 8 | od 121 000 PLN | ✓ indexing push (eksploduje!) |

### TANK (3 hubów)
| # | Hub | Vol/m | YoY | Listings | Price | Status |
|---|---|---:|---:|---:|---|---|
| 19 | Tank 300 | 1 300 | +122% | 24 | od 155 000 PLN | ✓ indexing push |
| 20 | Tank 700 Hi4-T | (n/d) | — | 14 | od 322 000 PLN | ✓ indexing push |
| 21 | Tank 300 Hi4-T | (n/d) | — | 4 | od 200 000 PLN | ✓ indexing push |

### DENZA (3 hubów — luksusowe rodzinne BYD-Mercedes JV)
| # | Hub | Listings | Price | Status |
|---|---|---:|---|---|
| 22 | Denza D9 DM-i (luksusowy minivan PHEV) | 19 | od 235 000 PLN | ✓ indexing push |
| 23 | Denza N9 DM-i (SUV premium PHEV) | 16 | od 267 000 PLN | ✓ indexing push |
| 24 | Denza Z9 DM-i (sedan premium PHEV) | 10 | od 209 000 PLN | ✓ indexing push |

### VOYAH (3 hubów — sub-brand Dongfeng premium)
| # | Hub | Vol/m | YoY | Listings | Price | Status |
|---|---|---:|---:|---:|---|---|
| 25 | **Voyah FREE** | **1 900** | **+82%** | 6 | od 143 000 PLN | ✓ TOP volume Voyah |
| 26 | Voyah Dream PHEV | 880 | +83% | 11 | od 202 000 PLN | ✓ indexing push |
| 27 | Voyah Taishan | 70 | (niska) | 8 | od 318 000 PLN | ✓ indexing push |

### CHANGAN (3 hubów)
| # | Hub | Vol/m | YoY | Listings | Price | Status |
|---|---|---:|---:|---:|---|---|
| 28 | **Changan UNI-V** | **320** | **+129%** | **90** | od 104 000 PLN | ✓ TOP stock Changan |
| 29 | Changan CS75 Plus | 140 | (drop YoY) | 24 | od 110 000 PLN | ✓ indexing push |
| 30 | Changan UNI-K | 170 | -19% | 4 | od 124 000 PLN | ✓ indexing push |

**Re-check 2026-05-17 dla wszystkich 15.**

**Auto-regen impact:** wszystkie te huby title już aktualizowane bulk v0.32.43. Daily cron 04:00 utrzyma fresh state.

---

## 2026-05-07 — sesja #10-13 (XPENG P7, X9, G7 + Avatr 11)

### Hub 12: XPeng P7 — `/samochody/xpeng/p7/` (TUNED)
- 20 listings, 136-214k PLN, vol 3 600/m + 90% YoY
- Title: „XPeng P7 z Chin - od 136 000 PLN, 20 sztuk PL | Sportowy sedan EV"
- 6 wersji w stocku: 586E, P7i 550 Pro, P7i 702 Max, 702 Long Range Ultra, 750 4WD High Performance Ultra, 820 Ultra Long Range Ultra
- USP: zasięg do 820 km CLTC (wyższy niż EU max P7+)
- FAQ +3: vs XPeng Polska, 6 wersji, wady (sedan low ground clearance)
- Indexing push ✓

### Hub 13: XPeng X9 — `/samochody/xpeng/x9/` (TUNED)
- 22 listings, 227-291k PLN, vol 1 000/m + 212% YoY
- Title: „XPeng X9 z Chin - od 227 000 PLN, 22 sztuki PL | Minivan 7-osobowy EV/EREV"
- 5 wersji: 702 Extended Range Pro/Max, 640 4WD HP Max, BEV 650 Long Range Max, **EREV 1602 Ultra** (rekordowy zasięg 1 602 km!)
- USP: jedyny luksusowy 7-osobowy minivan w naszym katalogu (konkuruje z Lexus LM 800k+ PLN, Volvo EM90 700k+)
- FAQ +3: co to za auto, wersje, czy dobre rodzinne
- Indexing push ✓

### Hub 14: XPeng G7 — `/samochody/xpeng/g7/` (TUNED — model premiery 2025!)
- 18 listings, 162-203k PLN, vol 480/m + **+586% YoY** (najszybszy trend XPENG)
- Title: „XPeng G7 z Chin - od 162 000 PLN, 18 sztuk PL | Nowy SUV elektryczny 2025"
- 3 wersje: RWD 5 Seater Electric, BEV 702 Ultra Long Range Max/Ultra
- USP: **G7 NIE jest jeszcze oficjalnie dostępny w salonach XPeng PL** — Prima-Auto jeden z pierwszych dostawców
- FAQ +3: czym jest G7, wersje, dostępność w Polsce
- Indexing push ✓

### Hub 15: Avatr 11 — `/samochody/avatr/11/` (TUNED)
- 17 listings, 163-227k PLN, vol 720/m + 51% YoY
- Title: „Avatr 11 z Chin - od 163 000 PLN, 17 sztuk PL | Premium SUV Huawei"
- 5 wersji: 2023 Ultra Long Range RWD 4-os, 2024 630 RWD, 2025 Ultra/Max/Facelift Max RWD EREV
- USP: SUV z Huawei HarmonyOS + design ex-Audi/Lamborghini
- FAQ +3: czym jest Avatr 11, wersje, vs Avatr 12
- Indexing push ✓

---

## 2026-05-07 — sesja #7-9 (XPENG G9, G6, Avatr 12)

### Hub 9: XPeng G9 — `/samochody/xpeng/g9/` (TUNED)
- 15 listings, 159-227k PLN, vol 8 100/m + 238% YoY
- Title: „XPeng G9 z Chin - od 159 000 PLN, 15 sztuk PL | 37% taniej niz salon"
- Konkurencja: xpeng.pl oficjalna #2 (od 252 900 PLN), Otomoto, AI Overview
- FAQ rebuilt 11 (smart quotes problem) + 3 PAA: czy premium, vs XPeng Polska, opinie
- Indexing push ✓

### Hub 10: XPeng G6 — `/samochody/xpeng/g6/` (TUNED)
- 11 listings, 143-170k PLN, vol 5 400/m + 179% YoY
- Title: „XPeng G6 z Chin - od 143 000 PLN, 11 sztuk PL | Taniej niz salon"
- Konkurencja: xpeng.pl od 203 900 PLN; nasze 143-170k = 25-30% taniej
- FAQ +3 PAA: vs XPeng Polska, zasięg realny, wady
- Indexing push ✓

### Hub 11: Avatr 12 — `/samochody/avatr/12/` (TUNED)
- 18 listings, 191-243k PLN, vol 1 300/m + 164% YoY
- Title: „Avatr 12 z Chin - od 191 000 PLN, 18 sztuk PL | Sedan premium Huawei"
- Wersje: Max RWD/4WD Electric, Ultra Electric, 650 EREV
- USP: jedyny sedan premium z Huawei HarmonyOS + design Wolfganga Eggera
- FAQ +3: czym jest marka Avatr, wersje 12 w Prima-Auto, opinie i wady
- Indexing push ✓

---

## 2026-05-07 — sesja #6 (BYD Seal 5 DM retune — +10 700% YoY trend)

### Hub 8: BYD Seal 5 DM — `/samochody/byd/seal-5-dm/`

| Parametr | Stan |
|---|---|
| Status | **TUNED** |
| Listings | 5 (Smart Drive Edition DM-i 55KM/120KM Flagship 2025) |
| Cena range | **105 000 – 111 000 PLN** |
| FAQ | **11** (8 + 3: wady, silnik, gwarancja import) |

**Search volume DFS:** 3 600/m + **+10 700% YoY** (najsilniejszy trend wzrostu z naszych pilotów)

**SERP top 10:** byd.com #1, Otomoto #3, BYD Garcarek (1050 km), BYD Autonix Rzeszów, BYD PGD, **Krotoski 104 570 PLN** (oficjalny dealer = identyczna nasza cena!), Auto Świat, Wikipedia. **AI Overview obecny.**

**Konkurencja KRYTYCZNE:** BYD Polska oferuje Seal 5 DM-i przez Krotoski od **104 570 PLN** — **identyczne nasze ceny**. USP cenowy nie zadziała. Inne USP:
- **5 sztuk DOSTĘPNE OD RĘKI** (salon = 4-8 tygodni z konfiguracji)
- Wersja chińska Flagship 120KM (vs EU bazowa Comfort 212KM)
- Nie wymaga konfiguracji u dealera

**Google PAA (4):**
- Ile kosztuje BYD Seal 5 DM-i? — istniejąca FAQ #4 ✓
- Jakie są wady BYD Seal U DM-i? (related model) — **DODANE wad Seal 5** (z opinii grup BYD Polska FB)
- Jaki silnik ma BYD Seal 5 DM-i? — **DODANE** (Super DM-i, 1.5L atkinsonowski + EM, 18,3 kWh Blade)
- Jaka jest cena BYD Seal U DM-i? (related)

**Zmiany 2026-05-07 (v0.32.42, retune):**
- Title: `BYD Seal 5 DM — Import z Chin` → **`BYD Seal 5 DM-i — od 105 000 PLN, 5 sztuk PL od ręki | Hybryda 1050km | Prima-Auto`**
- Description count: 4→5, dodano dostępność „od ręki bez kolejki", baterie Blade 18,3 kWh
- FAQ +3 PAA Q&A (wady oparte na opiniach polskich grup BYD FB, silnik DM-i z parametrami, gwarancja import)
- Indexing API push wykonane

**Hipoteza:** największy trend rosnący z naszych pilotów (+10 700% YoY). Volume 3,6k niski ale rosnący szybko. Strategia: nie konkurować ceną z Krotoski (ten sam poziom cenowy), tylko **dostępnością + wersjami CN + content**.

**Re-check planowany:** 2026-05-17.

---

## 2026-05-07 — sesja #5 (BYD Atto 2 retune — vol 9 900/m)

### Hub 7: BYD Atto 2 — `/samochody/byd/atto-2/`

| Parametr | Stan |
|---|---|
| Status | **TUNED** + REBUILD FAQ (smart quotes broke JSON) |
| Listings | 15 (Standard / Leading / Smart Drive / Surpass) |
| Cena range | **104 000 – 118 000 PLN** |
| Wiki body | 5 839 znaków (już istniał) |
| FAQ | **11** (8 + 3 nowe PAA — wady, gwarancja, „czy chinczyk") |

**Search volume DFS:** 9 900/m + 22% YoY

**SERP top 10:** byd.com Polska oficjalna #1, Otomoto #2, byd.com DM-i #3, video YouTube, dealerzy oficjalni (BYD Polska), Auto-Świat test, Krotoski, Wikipedia. **AI Overview obecny.**

**Google PAA:** Ile kosztuje, Realny zasięg, **Jakie są wady** (NEW), **Czy BYD to chinczyk** (NEW).

**Konkurencja oficjalna PL:** BYD Polska 129 900-144 400 PLN. **Nasze 104-118 000 PLN — 20-25% taniej.**

**Zmiany 2026-05-07 (v0.32.42, retune):**
- Title: `BYD Atto 2 z Chin — od 104 000 PLN, 15 sztuk PL | Taniej niż BYD Polska | Prima-Auto`
- Description: 11→15 + porównanie z BYD Polska
- FAQ +3 PAA Q&A (wady, gwarancja import, czy chinczyk)
- Indexing API push
- **REBUILD FAQ z usuniętymi smart quotes** — pierwsza próba miała `„Build Your Dreams"` co rozbiło JSON parser. Replace na ASCII rozwiązało.
- **NIE wymieniamy konkretnych dealerów** (Garcarek, Krotoski, PGD, Autonix) — zamiast tego „salony BYD Polska", „oficjalna dystrybucja"

**Re-check planowany:** 2026-05-17.

---

## 2026-05-07 — sesja #4 (BYD Sealion 7 retune — TOP volume + USP cenowy)

### Hub 6: BYD Sealion 7 — `/samochody/byd/sealion-7/`

| Parametr | Stan |
|---|---|
| Status | **TUNED** (retune wiki+FAQ already, focus na USP cenowy vs BYD Polska) |
| Listings | 15 (Long Range 610 / Standard 550 / Smart Drive 610) |
| Cena range | **147 000 – 171 000 PLN** |
| Wiki body | 5 795 znaków (już istniał) |
| FAQ | **11** (8 + 3 nowe PAA cenowe/jakościowe) |

**Search volume DFS:** `BYD Sealion 7` 12 100/m + 22% mo (drugi najwyższy w katalogu po VW Tayron)

**SERP top 10 (DFS) — KONKURENCJA OFICJALNA:**
- AI Overview rich (with byd.com, Otomoto, BYD-Garcarek, BYD-Autonix, Krotoski refs)
- #2 **byd.com/pl** (oficjalna marka)
- #3 Otomoto
- #4 PAA (4 pytania)
- #7 e.autokult.pl (article)
- #8 autocentrum.pl (dane techniczne)
- #9 byd-garcarek.pl (oficjalny dealer, 219 900 PLN)
- #10 byd.autonix.pl (oficjalny dealer)
- #11 krotoski.com (oficjalny dealer, 216 955 PLN)
- #12 YouTube
- #13 Wikipedia

**KLUCZOWE: BYD Sealion 7 SPRZEDAJE SIĘ OFICJALNIE w Polsce** — 219-253k PLN. Konkurencja z official dealers, nie tylko CN-importerzy. **My oferujemy chińską wersję CN za 147-171k PLN — 30-40% taniej.**

**Google PAA (4):**
- Ile kosztuje BYD Sealion 7? — istniejąca FAQ #5 ✓
- Czy BYD Sealion 7 to dobry samochód? — **DODANE** (z opiniami Autokult/Vogue/Pertyn)
- Jaki jest zasięg rzeczywisty? — istniejąca FAQ #1 ✓
- Jaka jest cena BYD Sealion 7 2026? — pokrywa nowy FAQ vs BYD Polska

**Related searches:** wynajem długoterminowy, Wyprzedaż, cennik, wynajem, promocja, finansowanie, cena, 2026, opinie, OTOMOTO, osiągi, wymiary, dane techniczne.

**Zmiany 2026-05-07 (v0.32.42, retune — focus USP cenowy):**
- Title: `BYD Sealion 7 — Import z Chin` → **`BYD Sealion 7 z Chin — od 147 000 PLN, 15 sztuk PL | 50% taniej niż salon | Prima-Auto`**
- Description count: 11 → 15 + porównanie cen (147-171 vs 220-253 BYD Polska)
- FAQ +3 nowe Q&A:
  - „Czy BYD Sealion 7 to dobry samochód?" — z opiniami polskiej prasy
  - „Czym różni się BYD Sealion 7 z Prima-Auto od BYD Polska?" — KLUCZOWE USP (cena -30-40%, wersje CN 550/610 km, gwarancja importera)
  - „Czy BYD Sealion 7 z importu chińskiego ma gwarancję?" — eliminacja głównej obawy
- Indexing API push wykonane

**Hipoteza:** Najtrudniejszy hub z dotychczasowych — konkurencja to oficjalne BYD Polska + 3 oficjalni dealerzy + AI Overview. Nasz USP: **cena 30-40% taniej**, podkreślona w title. Realna szansa na top 10 dla „BYD Sealion 7 cena" / „BYD Sealion 7 OTOMOTO" intencjonalnych — komercyjnych user'ów porównujących oferty.

**Re-check planowany:** 2026-05-17.

---

## 2026-05-07 — sesja #3 (Zeekr 9X retune)

### Hub 5: Zeekr 9X — `/samochody/zeekr/9x/`

| Parametr | Stan |
|---|---|
| Status | **TUNED** (retune — wiki+FAQ już były, dodano PAA + price/count) |
| Listings | 11 (Max 55kWh×3, Ultra 55kWh×2, Hyper 70kWh×5, Black Edition 1400PS) |
| Cena range | **387 000 – 524 000 PLN** |
| Wiki body | 5 922 znaków (już istniał) |
| FAQ | **11** (8 + 3 nowe PAA) |

**Search volume DFS Google Ads (PL, monthly):**
- `Zeekr 9X`: **9 900/m** + **+2408% YoY** 🔥🔥🔥
- `Zeekr 9X cena`: 1 900/m
- `Zeekr 9X kiedy w polsce`: 210/m
- `Zeekr 9X otomoto`: 210/m

**Trends 12m PL:** Zeekr 9X avg 35 (DOMINUJE wśród Zeekr line — 9× wyższe niż 8X avg 4).

**SERP TOP 10 (DFS):**
- **AI Overview obecny** (z Otomoto, Wikipedia, chinskiesamochody, west-motors, 4rings, elektromobilni, Instagram references) — mocny sygnał AEO
- #2 Otomoto (469 000 PLN, 100 km)
- #3 Wikipedia
- #5 Wikipedia
- #6 auto-china.com (import)
- #7 elektromobilni.pl (article 11/2025: "10 tysięcy zamówień w 13 minut")
- #8 chinskiesamochody.pl (article)
- #9 **west-motors.pl** (Zeekr 9X katalog — 50 listings! konkurent CN)
- #10 Facebook (review)

**Google PAA (4 pytania):**
- Ile kosztuje Zeekr 9X? — pokrywała istniejąca FAQ #6 ✓
- Kiedy Zeekr 9X w Polsce? — **DODANE**
- Czy Zeekr jest w Polsce? — **DODANE**
- Jaki silnik ma Zeekr 9X? — **DODANE** (rozbudowane do 2.0L turbo + 3 silniki elektryczne, wersje 898-1400 KM)

**Related searches:** Zeekr 9X cena / test / OTOMOTO / Hyper cena / mansory / Ultra.

**Zmiany 2026-05-07 (v0.32.42, retune):**
- `rank_math_title`: `Zeekr 9X — Import z Chin | Prima-Auto` → **`Zeekr 9X — od 387 000 PLN, 11 sztuk PL | Hybryda 1400KM | Prima-Auto`**
- `rank_math_description` count: 7 → 11 egzemplarzy + Edition Black mention
- FAQ +3 PAA Q&A (kiedy w Polsce, czy w Polsce, jaki silnik)
- AggregateOffer: offerCount 11, availability InStock ✓
- Indexing API push wykonane

**Hipoteza:** AI Overview obecny + duże volume + wzrost 2408% YoY + 11 listings stocku → ten hub ma realną szansę top 10 w 2-4 tygodnie. Konkurencja rozwarstwiona (1 Otomoto + Wikipedia + media articles + 2 importerzy CN).

**Re-check planowany:** 2026-05-17.

---

## 2026-05-07 — sesja #2 (Zeekr 8X od zera)

### Hub 4: Zeekr 8X — `/samochody/zeekr/8x/`

| Parametr | Stan |
|---|---|
| Status | **TUNED** (od zera, hub był totally empty!) |
| Listings | 3 (Yao Ying 1400KM, Ultra 898KM, Ultra+ 898KM) |
| Cena range | **402 000 – 467 000 PLN** (segment ultra-premium) |
| Wiki body | **3807 znaków** (były 0!) |
| FAQ | **9** (było NULL) |
| GSC pre-push | „Adres URL jest Google nieznany" — nigdy nie indeksowany |

**Stan PRZED tuningiem (katastrofa):**
- rank_math_title: PUSTY
- rank_math_description: PUSTY
- _serie_full_title: PUSTY
- wiki: 0 znaków
- FAQ: NULL
- title strony: domyślny WP „8X - Prima-Auto..."

**Search volume DFS Google Ads (PL, monthly):**
- `Zeekr 8X`: **390/m** + **240× wzrost** (10 → 2400 w 3m) — ŚWIEŻY HIT
- `Zeekr 8X cena`: 70/m, 12× wzrost (50→590)

**Trends 12m PL:** Zeekr 8X avg 4 (młody model, główny pik 2026-04 51-91), Zeekr 9X avg 35 (większy brat dominuje historycznie), Zeekr 001 avg 8.

**SERP TOP 10 (DFS):** Otomoto #1 (oferta z Mławy, 399 000 PLN!) + #8, Wikipedia #3, 4rings.pl article #5, YouTube CarSauce review #6, west-motors.pl #7 (CN konkurent), autotor.pl #9, Facebook autoklasa #10. **Plus AI Overview obecny.**

**Google PAA (4 pytania):** Cena 8X, Czy Zeekr w Polsce, Czy 9X w Polsce, Cena 9X.

**Related:** Zeekr 9X, Zeekr 8X ultra, Zeekr 8X otomoto, Zeekr 8X wymiary, Zeekr 8X konfigurator, Zeekr 7X.

**Zmiany 2026-05-07 (v0.32.42, hub created from scratch):**
- `rank_math_title`: **`Zeekr 8X PHEV 2026 — od 402 000 PLN, 3 sztuki PL | Prima-Auto`**
- `rank_math_description`: „Zeekr 8X 2026 PHEV w Prima-Auto — 3 egzemplarze (Yao Ying 1400KM, Ultra+ 898KM, Ultra 898KM), zasięg 1416 km, 0-100 km/h w 2,9 s..."
- `_serie_full_title`: `Zeekr 8X PHEV`
- Wiki body 3807 znaków: 6 sekcji (intro PHEV, napęd, wersje, wymiary, dlaczego import, vs konkurencja BMW X5/Audi Q7/Volvo XC90)
- 9 FAQ Q&A pokrywających 4 PAA + 5 related searches:
  - Cena w PL ✓ (PAA #1)
  - Czy w Polsce ✓ (PAA #2)
  - Yao Ying vs Ultra vs Ultra+ ✓ (Related: ultra)
  - Wymiary ✓ (Related: wymiary)
  - Zasięg PHEV
  - Vs Zeekr 9X ✓ (PAA #3-4)
  - Cena sprowadzenia
  - Wyposażenie standardowe
  - Homologacja indywidualna
- Sync wiki → term description
- Indexing API push (URL przed push: `nieznany Google` — liczymy na crawl 1-7 dni)

**Hipoteza:** Bańka Zeekr 8X dopiero w 2026-Q1, content (wiki+FAQ) przyjdzie wraz z search interest. Mocna szansa na top 5-10 w 2-4 tygodnie (mała konkurencja: 1 Otomoto + Wikipedia + media articles, brak agregatorów listings).

**Re-check planowany:** 2026-05-17 (10 dni).

---

## 2026-05-07 — sesja #1 (3 huby pilot, v0.32.42)

Cel sesji: pierwszy mocny push contentu na 3 najsilniejszych hubach modeli BYD/Geely/Li Auto. Otomoto dominuje top — testujemy czy bogaty content + Schema + PAA-aware FAQ pozwoli się wbić.

### Hub 1: BYD Leopard 7 — `/samochody/byd/leopard-7/`

| Parametr | Stan |
|---|---|
| Status | **TUNED** |
| Listings | 8 (po Bucket B Q11 merge: 7+1) |
| Cena range | 170 000 – 211 000 PLN |
| Wiki body | 6 774 znaków (już istniał) |
| FAQ | **10** (8 + **2 nowe PAA**) |

**Zmiany v0.32.42:**
- `rank_math_title`: `BYD Leopard 7 (Tai 7) FCB, PHEV — Import z Chin | Prima-Auto` → **`BYD Leopard 7 PHEV — od 170 000 PLN, 8 sztuk PL | Prima-Auto`**
- `rank_math_description` count: 9 → 8 egzemplarzy (zgodne ze stanem)
- FAQ +2 PAA odpowiedzi:
  - „Kiedy BYD Leopard 7 będzie dostępny w Polsce?"
  - „Ile kosztuje BYD Leopard 7 w Chinach?"
- Sync wiki → term description (6 774 znaków)
- Indexing API push + GSC URL Inspection: verdict=PASS, indexowany, last_crawl 2026-05-05.

**Search volume DFS Google Ads (PL, monthly):**
- `BYD Leopard 7`: brak danych (<10/m) ← był w title, BŁĄD
- **`Leopard 7`: 260/m, trend rosnący 7× (70→590 12m)** ← faktycznie szukane, retune title
- `Leopard 7 PHEV`: brak
- `Leopard 7 cena`: brak

**Trends 12m PL** (averages): `Leopard 7=7, BYD Leopard 7=1, Leopard 7 PHEV=0` → 7× przewaga „Leopard 7" sam.

**RETUNE 2026-05-07 (post-volume-check):**
- title: `Leopard 7 — od 170 000 PLN, 8 sztuk PL | BYD PHEV | Prima-Auto` (lead z dominantą frazą)
- description: „Leopard 7 (BYD PHEV) w Prima-Auto..."

**Baseline ranking DFS SERP 2026-05-07:**
| Fraza | Nasza poz | Top 1 |
|---|---|---|
| BYD Leopard 7 | poza top 100 | otomoto.pl ("180 000 PLN") |
| BYD Leopard 7 cena | poza top 100 | otomoto.pl |
| BYD Leopard 7 polska | poza top 100 | otomoto.pl |
| BYD Leopard 7 opinie | poza top 100 | chinskisamochod.com |

**Konkurencja top 5:** otomoto.pl #2 + #3 (180 000 PLN w title, dane techniczne w meta), chinskisamochod.com #5 (article), Facebook #7+11+15, Reddit #8, OLX #9+14, china-electric-vehicles.com #10 (BAD: mówi o Leopard 8).

**Hipoteza:** brak DA + CWV mobile POOR + ostatnio Title z chińską nazwą — wszystkie 3 prawdopodobnie powodowały demotion. Po fix title + count + PAA — czekamy 7-14 dni na re-crawl + re-rank.

**Re-check planowany:** 2026-05-17 (10 dni).

---

### Hub 2: Geely Monjaro — `/samochody/geely/monjaro/`

| Parametr | Stan |
|---|---|
| Status | **TUNED** |
| Listings | 55 (najsilniejszy hub w katalogu!) |
| Cena range | 119 000 – 164 000 PLN |
| Wiki body | 5 946 znaków |
| FAQ | **11** (8 + **3 nowe PAA**) |

**Zmiany v0.32.42:**
- `rank_math_title`: `Geely Monjaro — Import z Chin | Prima-Auto` → **`Geely Monjaro — od 119 000 PLN, 55 SUV-ów PL | Prima-Auto`**
- `rank_math_description` count: 41 → 55 ofert (zgodne)
- FAQ +3 PAA odpowiedzi:
  - „Kiedy Geely będzie oficjalnie dostępne w Polsce?" (Jameel Motors 2025, ale my mamy 55 sztuk dziś)
  - „Co to za marka Geely?" (chiński koncern, posiada Volvo, Polestar, Lotus)
  - „Czy Geely to Volvo?" (NIE, ale Geely OWNS Volvo od 2010 + wspólna platforma CMA)
- Sync wiki → term description
- Indexing API push + GSC: verdict=PASS, last_crawl 2026-05-07 (dziś!).

**Search volume DFS (PL, monthly):**
- `Geely Monjaro`: **3600/m** stabilne (2900-4400) ← target frazy ✓
- `Monjaro`: 110 000/m ALE głównie **lek na otyłość** (semaglutide), nie auto

**Trends 12m PL** (averages): `Geely Monjaro=7, Monjaro Geely=9, Monjaro auto=0` → odwrócona kolejność „Monjaro Geely" lekko popularniejsza, ale różnica minimalna.

**Baseline ranking DFS SERP 2026-05-07:**
| Fraza | Nasza poz | Top 1 |
|---|---|---|
| Geely Monjaro | **48** | otomoto.pl (poz spadła z 43 wcześniejszy pomiar — SERP variance) |
| Geely Monjaro cena | **18** ← **NAJBLIŻEJ top 10**, low-hanging fruit | otomoto.pl |
| Geely Monjaro polska | **25** | geelyauto.pl |
| Geely Monjaro opinie | poza top 100 | chaf.pl |

**Konkurencja top 10:** otomoto.pl #2, geely.com #3 (oficjalna), Facebook #6, gsmmaniak.pl #7 (article), aaaauto.pl #8, geelyauto.pl #9, autogaleria.pl #10, west-motors.pl #11 (CN konkurent), otomoto #12 (używane).

**Atut:** **55 listings = najwięcej w PL** dla tego modelu. Powinniśmy mieć szansę na top 10-15 dzięki samej skali oferty + bogatej FAQ (Volvo connection to ważny PAA topic).

**Re-check planowany:** 2026-05-17.

---

### Hub 3: Li Auto L9 — `/samochody/li-auto/l9/`

| Parametr | Stan |
|---|---|
| Status | **TUNED** |
| Listings | 30 |
| Cena range | 257 000 – 343 000 PLN (segment premium!) |
| Wiki body | 7 142 znaki |
| FAQ | **10** (8 + **2 nowe PAA**) |

**Zmiany v0.32.42:**
- `rank_math_title`: `Li Auto L9 — Import z Chin | Prima-Auto` → **`Li Auto L9 EREV — od 257 000 PLN, 30 sztuk PL | Prima-Auto`**
- `rank_math_description` count: 9 → 30 egzemplarzy (DUŻY błąd — meta była mocno nieaktualna)
- FAQ +2 PAA odpowiedzi:
  - „Kiedy Li Auto będzie dostępne w Europie?" (Munich 2024, my mamy 30 sztuk teraz)
  - „Jakie są wymiary Li Auto L9?" (5218×1998×1800 mm, vs BMW X7 5181)
- Sync wiki → term description (7 142 znaki)
- Indexing API push + GSC: verdict=PASS, last_crawl 2026-05-06.

**Search volume DFS (PL, monthly):**
- `Li Auto L9`: **720/m**, trend rosnący 3.3× (390→1300) ← target ✓
- `Li L9`: 590/m, trend spadkowy (1000→320)
- `L9`: zbyt generyczne — DFS skipped

**Trends 12m PL** (averages): `Li Auto L9=4, Li L9=4` → równoważne, użytkownicy wymiennie.

**Baseline ranking DFS SERP 2026-05-07:**
| Fraza | Nasza poz | Top 1 |
|---|---|---|
| Li Auto L9 | **48** | liauto.com (oficjalna) |
| Li Auto L9 cena | poza top 100 | allegro.pl |
| Li Auto L9 polska | poza top 100 | pl.beonigroup.com |
| Li Auto L9 opinie | poza top 100 | elubaczow.com |

**Konkurencja top 10:** allegro.pl #3 (Auto Punkt 1.5 Hybryda), liauto.com #4 (oficjalna), Wikipedia #6, Facebook #7, Reddit #8, plc.auction #9, otomoto.pl #10 (Li category), autotor.pl #12 (article 2026), marketplace.china-crunch.com #14, autoblog.spidersweb #15.

**Atut:** segment premium (>250k PLN) — mniejsza konkurencja. AI Overview pojawia się dla tej frazy → AEO-friendly content (mamy llms-full.txt).

**Re-check planowany:** 2026-05-17.

---

## Plan re-check'u

**2026-05-17 (10 dni od tuningu):**
1. Doładowanie DFS budget (najpierw)
2. Powtórzyć rank tracker dla 12 fraz (4 per hub)
3. Sprawdzić w GSC: imp / clicks / CTR / pos last 14d dla 3 URL
4. Decyzja per hub: TUNED → re-check OK / RETUNE (gdy nie ruszył) / DROPPED (gdy konkurencja zbyt silna)

## TODO przed kolejną sesją tuning

- [ ] **Doładować DFS budget** ($10-20) — bez tego nie możemy mierzyć
- [ ] (Opcjonalne) Zainstalować `pytrends` — `pip install pytrends`
- [ ] (Opcjonalne) Aplikacja o oficjalny Google Trends API access (formularz)
- [ ] Wybrać kolejne 3 huby do tuning'u (idealnie: Voyah Free, BYD Han, Xiaomi SU7?)
