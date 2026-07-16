# T-211 — Pakiet higieny technicznej

> Status: **gotowy do odpalenia** · Rozmiar: M
> Godziny realnie: **20–26 h** (Janek ~3 h, AI ~17–23 h) · Rynkowo: 50–62 h
> Obniżone z 25–32 h: jedna pozycja jest już zrobiona, dwie okazały się godzinami zamiast dni. **Ale największa z nich jest większa, niż sądziliśmy.**

## ❌ SPROSTOWANIE (2026-07-14): „2633 puste strony indeksowalne" — TO BYŁ FAŁSZYWY ALARM

Wcześniejszy zwiad twierdził, że puste huby są w pełni indeksowalne. **Nieprawda — zweryfikowane na żywych stronach.**

- RankMath ma włączone **`noindex_empty_taxonomies = on`** → **każda** taksonomia z zerem ofert dostaje `noindex` automatycznie.
- Sprawdzone 13 losowych pustych hubów (marki i modele) → **wszystkie zwracają `noindex`**.
- Sitemapy zawierają wyłącznie termy z ofertami (`make`: 54 przy 56 z ofertami; `serie`: analogicznie).

**Skąd wziął się błąd:** w kodzie pluginu `serie`/`make` faktycznie nie ma na liście `THIN_TAXONOMIES` — ale noindex leci z RankMath, nie z pluginu. Wniosek wyciągnięto z kodu, bez odpytania strony.

**Nie ma tu nic do naprawy.**

---

## ⚡ SZYBKIE ZYSKI — do wzięcia od ręki

| # | Co | Czas | Efekt |
|---|---|---|---|
| ⚡1 | **Odsłonić 100 hubów z treścią** (patrz niżej — odwrotny problem) | 5–7 h | Odzyskujemy gotowe, bogate strony schowane przed Google |
| ⚡2 | ✅ **ZROBIONE 2026-07-16 (v0.33.27)** — `color` → `exterior-color` w `class-asiaauto-single.php:1047`. Pokrycie 2 906/3 058 ofert (95%). Smoke na 3 paliwach OK. | **1 linia** | ~~Bogatszy wynik w Google~~ → wdrożone |
| ⚡3 | **20 nieprzetłumaczonych chińskich miast** (105 ofert pokazuje surowy chiński znak) | 0,5–1 h | Koniec z chińskimi krzakami na froncie |

**Osobno, poza tym pakietem (należy do T-205/Meta):** cron regenerujący feed pojazdów **zniknął** — plik stoi na 01.06, sześć tygodni bez odświeżenia. Prawdopodobnie zginął przy nadpisaniu crontaba 12.07. ⚠️ Backup crontaba przed jakąkolwiek edycją.

---

## 🔄 ODWROTNY PROBLEM: 100 gotowych stron schowanych przed Google (5–7 h)

**Decyzja Janka 2026-07-14: odsłaniamy.**

**100 modeli ma wygenerowaną treść wiki, ale zero ofert na stanie** → RankMath automatycznie daje im `noindex`.

### Te strony NIE są puste — sprawdzone na żywo

| Strona | Treść | Karty aut |
|---|---|---|
| `/samochody/volkswagen/golf/` | ~39 800 znaków | **30** |
| `/samochody/mazda/cx-50/` | ~38 100 znaków | **30** |
| `/samochody/denza/d9-ev/` | ~37 900 znaków | **30** |

Każda ma H1, opis modelu, FAQ i 30 kart samochodów (alternatywnych, bo tego modelu akurat nie mamy). **To są pełnowartościowe strony** — RankMath patrzy tylko na licznik przypisanych ofert, nie na to, co się faktycznie renderuje.

### Dlaczego warto je odsłonić

1. **Zapłaciliśmy za tę treść i ją schowaliśmy.**
2. **Te modele MIAŁY oferty** — generator wiki karmił się faktami z naszej bazy, więc nie napisałby opisu modelu, którego nigdy nie mieliśmy. To auta, które sprowadzaliśmy i które zrotowały. **Mogą wrócić na stan w każdej chwili** — a wtedy strona i tak się odsłoni. Trzymanie jej w ukryciu w międzyczasie = **utrata ciągłości indeksacji** (za każdym powrotem Google zaczyna od zera).
3. To jest ruch informacyjny („{model} cena", „{model} z Chin"), a nasz model biznesowy to **import na zamówienie** — nie musimy mieć auta na placu, żeby je sprowadzić.

### Plan

1. **Warunek indeksacji nadpisujący RankMath:** strona idzie do indeksu, jeśli **ma treść wiki** ORAZ **renderuje karty** (choćby alternatywne). Zostaje ukryta, jeśli nie ma ani treści, ani czego pokazać → **2297 pustych modeli i 236 marek zostaje schowanych, i słusznie.**
2. **Dopisać te huby do sitemapy.**
3. **🔴 Uczciwy komunikat dla użytkownika (warunek konieczny):** jeśli klient wchodzi na stronę Golfa, a Golfa nie mamy — musi zobaczyć **jasny komunikat**: *„Tego modelu nie mamy teraz na stanie — sprowadzimy go na zamówienie"* + CTA/formularz. Dopiero pod tym alternatywne auta.
   **Bez tego Google uzna stronę za mylącą — i będzie miał rację.** Tytuł mówi o Golfie, ofert Golfa nie ma.
4. Zgłoszenie do indeksacji (⚠️ wspólna pula Indexing API — patrz zasady, `index-submit`).

### Testy

- Hub z wiki + 0 ofert → `index`, obecny w sitemapie, **z komunikatem o braku na stanie**.
- Hub bez wiki + 0 ofert → nadal `noindex`, nieobecny w sitemapie (**regresja!**).
- Hub z ofertami → bez zmian.
- Po 3–4 tygodniach: GSC — czy te 100 stron zbiera wyświetlenia i czy nie generuje skarg na „cienką treść".

---

## Weryfikacja 7 pierwotnych pozycji

| # | Pozycja | Werdykt po zwiadzie |
|---|---|---|
| 1 | Puste strony modeli | ❌ **NIE ISTNIEJE** — RankMath już je noindeksuje (patrz sprostowanie wyżej). Zamiast tego: odsłonić 100 hubów Z TREŚCIĄ |
| 2 | Puchnięcie bazy | 🟡 aktualne, ale **inna przyczyna** niż zakładaliśmy |
| 3 | Dane strukturalne (schema) | ✅ **ZAMKNIĘTE 2026-07-16 (v0.33.27)** — ostatni bug (kolor) naprawiony. Reszta schematu była zdrowa już wcześniej: `vehicleEngine`/`vehicleTransmission`/`driveWheelConfiguration`/`itemCondition`/`OfferShippingDetails` **są na produkcji**. Rzekome „utracone pola" (ZAD.12/13) = fałszywy alarm — backlog opisywał **martwą** funkcję `schema():632`, której nikt nie woła. Hipoteza „multi-type [Product,Car] blokuje" **obalona** URL Inspection API (huby i oferty: PASS jako „Opisy produktów"). |
| 4 | Chińskie nazwy miast | 🟢 **105 ofert, nie 3000** → pół godziny |
| 5 | Resztkowe 404 | 🟢 16 śmieciowych adresów, świadomie odłożone |
| 6 | Galeria klientów | ✅ **ZROBIONE** (v0.32.57) — wykreślam |
| 7 | Szlify mobilne | ⚠️ **niewyceniane bez zrzutu ekranu** |

---

## 🟡 2. Puchnięcie bazy — przyczyna inna niż w opisie (~5–7 h)

Kosztorys mówił o „sierotach". **Sierot jest 11.** To nie jest problem.

Prawdziwy obraz:
| Tabela | Rozmiar |
|---|---|
| `postmeta` | **446,7 MB** (306 531 wierszy) |
| `posts` | 83,7 MB (43 051) |

- **40 595 załączników na 3056 ofert** (~13 zdjęć/auto) generuje **~70% wierszy** w postmeta.
- Suma danych ≈ 130 MB, a tabela zajmuje 446 MB → **~300 MB to narzut i fragmentacja**.
- 269 osieroconych plików multimedialnych.

**Do zrobienia:** rotacja zdjęć sprzedanych/wycofanych aut, defragmentacja tabeli (ostrożnie — blokada na produkcji), limit logów harmonogramu zadań. **Plus rotacja loga pluginu — 17,9 MB i rośnie bez ograniczeń.**

## 🟢 3. Dane strukturalne — bug: kolor nigdy nie trafia do schematu (~2–3 h)

**Wbrew kosztorysowi silnik i skrzynia SĄ już emitowane** (razem z mocą w kW, typem nadwozia, paliwem, napędem, przebiegiem, dostawą).

**Ale jest błąd:** kod pobiera kolor z taksonomii `color` — **która nie istnieje** (są `exterior-color` i `interior-color`). Efekt: **kolor nie jest emitowany dla żadnej z 3056 ofert.**

Fix: jedna linia. Plus usunięcie martwej, starszej wersji generatora schematu (myląca przy czytaniu kodu). Opcjonalnie dorzucić: kolor wnętrza, liczbę drzwi, liczbę miejsc, VIN.

## 🟢 4. Chińskie nazwy miast — 105 ofert (~0,5–1 h)

Mapa tłumaczeń ma **89 miast** i pokrywa cały top (Shenzhen 585 ofert, Dongguan 501, Guangzhou 491…).
**Brakuje 20 miast → 105 ofert** wyświetla surowy chiński znak.

To nie jest osobna pozycja — to dopisanie 20 wpisów. Panel do tego już istnieje.

## 🟢 5. Resztkowe 404 (~1–2 h)

Z 697 adresów raportowanych przez Google **675 to duchy** (już naprawione). **Realnie zepsutych: 16** — śmieci techniczne, stary system, jedna literówka. Świadomie odłożone.

Wartość jest nie w naprawie tych 16, tylko w **automatyzacji sprawdzania** (skrypt + cykliczny raport), żeby nie robić tego ręcznie co miesiąc.

## ⚠️ 6. Szlify mobilne — NIE WYCENIAM bez zrzutu ekranu

W kolejce wisi wpis o różnicy w wyglądzie hubów na telefonie i poziomym przewijaniu — ale **pamięć projektu jest przestarzała** (część problemów naprawiono w v0.31.8), a zgłoszenie nie ma reprodukcji.

**Bez zrzutu ekranu albo konkretnego adresu z problemem to nie jest zadanie, tylko przeczucie.** Do wyceny osobno, gdy będzie repro. Druga pozycja (optymalizacja ładowania CSS) jest świadomie odłożona jako kosmetyka, której realny użytkownik nie odczuje.

---

## Kolejność (od najwyższego zwrotu)

1. **Odsłonić 100 hubów z treścią** (+ komunikat „sprowadzimy na zamówienie") — 5–7 h.
2. **Bug koloru w schemacie** — jedna linia, 3056 ofert.
3. **Chińskie miasta** — pół godziny, 105 ofert.
4. **Rotacja loga + baza** — zapobiega problemom, nie naprawia.
5. **404 (automat)** — porządek.

## Testy

**Automatyczne**
- Hub z wiki + 0 ofert → `index` + w sitemapie + komunikat o braku na stanie.
- Hub bez wiki + 0 ofert → nadal `noindex`, poza sitemapą (**regresja!**).
- Hub z ofertami → bez zmian (**regresja!**).
- Schemat oferty waliduje się w narzędziu Google i **zawiera kolor**.
- Zero chińskich znaków w nazwach miast na froncie.

**Półautomatyczne**
- Po odsłonięciu: Search Console przez 3–4 tygodnie — czy te 100 stron zbiera wyświetlenia i czy nie pojawiają się ostrzeżenia o cienkiej treści.
- Defragmentacja bazy: **kopia zapasowa przed**, pomiar rozmiaru przed/po.

## Definicja zrobionego

- 100 hubów z treścią jest w indeksie i w sitemapie, z uczciwym komunikatem o braku na stanie i CTA „sprowadzimy na zamówienie".
- Huby bez treści i bez ofert **pozostają ukryte**.
- Kolor pojawia się w danych strukturalnych wszystkich ofert.
- Żadna oferta nie pokazuje chińskiej nazwy miasta.
- Log pluginu ma rotację; baza odzyskała miejsce.
- Sprawdzanie 404 jest zautomatyzowane.
