# 2026-06-02 — Google Ads Faza 1: DSA + odświeżenie SKAG + Brand/Topic

## Kontekst

Audyt konta Prima-Auto (9506068500), dane 30 dni. Brand = jedyna rentowna kampania
(CPA 7,6 zł, 13 konw). SKAG-1/2/3 + Topic paliły ~2700 zł/mc na ~10 konw. SKAG-3
(lejek importowy) — najwięcej ruchu, najgorszy CTR/CPA. Korzeń słabej konwersji =
landing single `/oferta/` (osobny workstream, poza tą fazą).

## Decyzje

1. **SKAG-3 → DSA.** Lejek importowy „sprowadzimy" przerzucony z keywordowego SKAG-3
   na Dynamic Search Ads. Powód: auto-pokrycie ~modeli bez ręcznego budowania grup,
   łapie long-tail, samo-aktualizuje się ze stockiem, tani klik. SKAG-3 PAUSED (rollback).

2. **DSA — parametry:**
   - Bidding **Maximize clicks + cap CPC 0,30 zł** (rynek model-exact ~0,48 → cap poniżej,
     świadomie: tani ruch, mniej marnotrawstwa w dziurawym lejku; monitor → ew. 0,40).
   - **Page feed `useSuppliedUrlsOnly=true`** — tylko nasze URL-e, landing = **model-huby**
     `/samochody/{make}/{serie}/` (nie marka-huby, nie /oferta/). Po reworku OGON title
     hubów = „Model — od X PLN | Import z Chin" → Google buduje trafny nagłówek z ceną.
   - **Harmonogram 7-22** (wzorzec SKAG), **geo 10 dużych miast** (Warszawa/Kraków/Wrocław/
     Poznań/Gdańsk/Łódź/Katowice/Szczecin/Lublin/Bydgoszcz — NIE PL-wide).
   - Opisy niosą „jak nowe" + akcję (nagłówek generuje Google).

3. **Zapisany filtr feedu (kanoniczne kryterium DSA-eligible hub):**
   serie (model) z ≥1 publish listingiem `ca-year=2026` (gate „jak nowe", 368→97)
   MINUS marki nie-chińskie (VW/Volvo/Nissan/Mazda/Audi/MG/Smart/MINI/Lotus)
   MINUS modele aktywne w SKAG-1/2 (anty-kanibalizacja)
   dedup po (make, serie_name) → najwyższy c2026, tylko HTTP 200.
   Wynik: **58 hubów.** Generator: `scripts/build-dsa-pagefeed.php`.

4. **Definicja stocku = STATUS rezerwacji (nie lokalizacja).** Lustro podstron:
   - SKAG-1 „od ręki" = `/w-rzeszowie/` = status `on_lot` (7 modeli).
   - SKAG-2 „w drodze" = `/w-drodze/` = status `in_transit` (24 sztuki → 17 modeli).
   - Jedna grupa = jeden model; przy duplikacie sztuka **nowszy rocznik → tańszy**.

5. **SKAG-1:** +1 grupa „Denza N9 — od ręki" (on_lot 270838). Pozostałe 6 celują poprawnie.
   YU7 ZOSTAJE (jest na /w-rzeszowie/). Zero pauz.

6. **SKAG-2:** repoint KW finalUrls Denza N9 (→351079) i Denza Z9 GT (→314155);
   PAUSE „Geely LEVC L380" (251809 trash, brak żywej sztuki — jedyna pauza);
   +5 nowych grup: Leopard 5, Sealion 8, Song L EV, Zeekr 001, Zeekr 7X.

7. **Brand:** bid ad group 0,42 → 1,00 zł + 3 negatywy PHRASE `łask`/`warka`/`miechów`
   (inne firmy „Prima Auto"). ZOSTAJE (decyzja Janka). Uzasadnienie z danych 14d po rewizji:
   konwertujący keyword `prima auto` [PHRASE] (6 konw/14d) trzyma #1 tylko w 48% → tam siedzi
   gros z 24% rank-lost; bid-up odzyskuje niedopokryte konwertujące zapytania brandowe.
   `prima auto` [EXACT] = ruch nawigacyjny (#1=91%, 0 konw, obrona marki). Budżet-lost 1% → OK.

8. **Topic:** bid-down 3,10→1,80 był BŁĘDEM (realny CPC 2,18 → 1,80 zrzuciłoby nas z #1) —
   **COFNIĘTE do 3,10**. Topic to dominacja kategorii, NIE kampania konwersyjna: trzymamy
   **#1 (76% abs top, 94% IS) na cały temat „import aut z Chin"**. Oceniać po pozycji, nie CPA.
   Dane 14d po rewizji: CPA 131 zł, rank-lost 6%, budżet-lost 0%. Zostaje 3,10.

   **Uwaga metodologiczna:** Brand/Topic analizować WYŁĄCZNIE na oknie po zmianach (od 19.05),
   nie 30d blob (ciągnie usunięte frazy jak „auta z chin import" + stare stawki).

## Stan końcowy (budżet 112 zł/dz bez zmian)

Brand 7 (ENABLED) · DSA 25 (ENABLED) · SKAG-1 20 · SKAG-2 25 · Topic 35 · SKAG-3 PAUSED.

## Skrypty (tmp/, scratch)

`gads_phase1_chunk1` (SKAG-3+Brand+Topic) · `gads_phase1_skag2` · `gads_phase1_skag1`
· `gads_dsa_build` (4 kroki) · `build-dsa-pagefeed`. Backup konta: `~/backups/primaauto/2026-06-02-ads/`.

## Faza 2 (za 2-3 tyg.) — remarketing dynamiczny

Feed Google + tag `dynx_itemid` w GTM + Display dynamiczny. Baza dziś: lista tag-owa
840 (Consent Mode v2 ogranicza pulę). Po DSA audiencja urośnie.

## Monitoring

T+48h serving/Ad Strength/cap · T+7d search terms+CPC · T+14d DSA vs SKAG-3 + geo + próg ceny.
SKAG-1/2 co ~2 tyg (stock rotuje). Korzeń = konwersja landingu single (osobny temat).
