# 40 możliwych rankingów — typowanie SEO i merytoryczne (2026-08-04)

> Źródło danych: **API rankingowe Dongchedi** (`motor/pc/car/rank_data`) — rozpoznane 04.08.
> Popyt: DataForSEO, location 2616 / pl. Podaż: nasza baza, 2090 ofert.
> Reguła nadrzędna: ranking opisuje **rynek chiński**, nasza oferta jest warstwą dodaną (T-229 D1).

## Co potrafi API (rozpoznane parametry)

| Parametr | Wartości | Znaczenie |
|---|---|---|
| `rank_data_type` | **11** = sprzedaż miesięczna · **1** = popularność (关注度, aktualizowana codziennie) · 2–5, 10 = rankingi jakościowe, nierozpoznane | oś rankingu |
| `outter_detail_type` | **1** miejskie · **2** kompaktowe · **3** średnie · **4** średnio-duże · **5** limuzyny · **10–14** SUV od małego do dużego · **21–24** MPV | klasa pojazdu |
| `new_energy_type` | **2** = PHEV · **3** = EREV · **4** = pozostałe elektryfikowane | napęd |
| `month` | np. `202512` | **historia** — pozwala liczyć dynamikę |
| `price`, `brand_id`, `manufacturer`, `nation` | — | filtry dodatkowe |

Każda pozycja niesie: `rank`, `series_name`, `count` (sztuki), `min_price`/`max_price` (万 RMB),
`last_rank` (**zmiana pozycji m/m**), `image`, `series_id`.

Weryfikacja: `理想i6 = 21 453` i `小米YU7 = 14 324` — zgodne co do sztuki ze zrzutami z WeChat,
czyli to samo źródło, tylko przez API zamiast grafiki.

---

## A. Segmenty nadwozia (12)

| # | Ranking | Fraza /mc | Dane | Nasza podaż |
|---|---|---:|---|---|
| A1 | **Najchętniej kupowane chińskie SUV-y** | **6 280** | typ 11 + odt 10–14 | 1180 aut / 172 modele |
| A2 | Chińskie terenówki i auta 4×4 | 3 450 | typ 11, filtr po nadwoziu ramowym | 102 / 23 |
| A3 | Chińskie pickupy | 2 480 | typ 11 + pickupy | 6 / 6 |
| A4 | Chińskie sedany | 1 300 | typ 11 + odt 2–4 | 641 / 84 |
| A5 | Chińskie vany i MPV | 1 000 | typ 11 + odt 21–24 | 114 / 20 |
| A6 | Chińskie limuzyny (klasa E i wyżej) | 880 | typ 11 + odt 5 | podzbiór A4 |
| A7 | Chińskie SUV-y 7-osobowe | 590 | typ 11 + odt 13–14 | 159 / 34 |
| A8 | Chińskie samochody dostawcze | **590** | typ 11 + odt 22 | JMC EV, Maxus |
| A9 | Chińskie kombi | 480 | typ 11 + kombi | 26 / 4 |
| A10 | Chińskie crossovery i małe SUV-y | **170 + 50** | typ 11 + odt 10 | — |
| A11 | Największe chińskie SUV-y | 140 | typ 11 + odt 14 | podzbiór A1 |
| A12 | Chińskie auta miejskie | 80 | typ 11 + odt 1 | — |

## B. Napędy (7)

| # | Ranking | Fraza /mc | Dane |
|---|---|---:|---|
| B1 | Chińskie samochody elektryczne — bestsellery | **1 880** | typ 11, wykluczyć PHEV/EREV |
| B2 | Chińskie hybrydy plug-in | **320** | typ 11 + `new_energy_type=2` |
| B3 | Chińskie auta z range extenderem (EREV) | ~50 | typ 11 + `new_energy_type=3` |
| B4 | Elektryczne SUV-y — bestsellery | 210 | typ 11 + odt 10–14 + EV |
| B5 | Hybrydowe SUV-y | ~120 | typ 11 + odt 10–14 + PHEV |
| B6 | Elektryczne sedany | ~90 | typ 11 + odt 2–4 + EV |
| B7 | Elektryczne vany i dostawcze | ~40 | typ 11 + odt 21–24 + EV |

## C. Cena (5)

| # | Ranking | Fraza /mc | Dane |
|---|---|---:|---|
| C1 | **Najtańsze chińskie samochody** | **590 + 320** | typ 11 + `min_price` rosnąco |
| C2 | Chińskie auta premium | **390** | typ 11 + `price` powyżej progu |
| C3 | Chińskie samochody luksusowe | 70 | typ 11 + odt 5 + cena |
| C4 | Chińskie SUV-y do 150 tys. zł | — | typ 11 + próg cenowy |
| C5 | Ile kosztują chińskie auta — mapa cen segmentów | — | agregat `min_price` per klasa |

## D. Parametry techniczne (8)

| # | Ranking | Fraza /mc | Dane |
|---|---|---:|---|
| D1 | Największy zasięg — auta elektryczne | ~200 | nasza baza + katalog Autohome |
| D2 | Największy zasięg elektryczny w hybrydach i EREV | ~30 | jw. |
| D3 | Ładowanie 800V — kto ma je w standardzie | ~200 | nasza baza (291 aut / 50 modeli) |
| D4 | Najszybsze chińskie auta (0–100) | 520 | nasza baza (1646 aut z danymi) |
| D5 | Największe baterie | ~20 | nasza baza (1591 aut) |
| D6 | Producenci baterii — kto dostarcza komu | ~150 | nasza baza (72 wartości) |
| D7 | Auta z LiDAR-em | ~40 | nasza baza (540 aut) |
| D8 | Najdłuższe i najbardziej przestronne | ~60 | wymiary, 97,5% pokrycia |

## E. Dynamika i zmiana (5)

| # | Ranking | Fraza /mc | Dane |
|---|---|---:|---|
| E1 | Największe wzrosty sprzedaży miesiąca | — | `rank` vs `last_rank` |
| E2 | Co Chińczycy oglądają najczęściej (popularność) | — | **typ 1**, aktualizowany codziennie |
| E3 | Debiuty — modele, które weszły do rankingu | — | porównanie `month` m/m |
| E4 | Kto traci — spadki w rankingu | — | `last_rank` |
| E5 | Rok do roku: jak zmienił się TOP 20 | — | `month=202508` vs `202408` |

## F. Porównawcze i przekrojowe (3)

| # | Ranking | Fraza /mc | Dane |
|---|---|---:|---|
| F1 | Chińska konkurencja Tesli Model Y | — | typ 11 + odt 12 |
| F2 | Marki chińskie wg sprzedaży | 10 | typ 11 agregowany do marek |
| F3 | Chińskie auta, które podbijają Europę | — | doniesienia + nasza oferta |

---

## Typowanie — pierwsza piątka

**1. A1 — Najchętniej kupowane chińskie SUV-y (6 280/mc)**
Największa fraza w całym zestawieniu i największa podaż po naszej stronie (172 modele).
Dane z API są kompletne: pozycja, sztuki, ceny, zmiana m/m, klasa. Ranking da się rozbić na
podsekcje wg klas (mały → duży), co daje naturalne miejsce na warianty long-tail w jednym tekście.
Ryzyko: long-tail przy tej frazie jest cenowy („chiński suv za 54 tys."), więc tekst musi filtrować
intent treścią.

**2. A2 — Chińskie terenówki i auta 4×4 (3 450/mc)**
Druga fraza co do wielkości, a **nikt w Polsce tego nie obsługuje**. Mamy 23 modele z nadwoziem
ramowym, w tym całą serię Tank, Leopardy i BJ40. Dane o sprzedaży z API, konstrukcja z naszej bazy.
Segment kupujących jest konkretny i mało wrażliwy na cenę.

**3. C1 — Najtańsze chińskie samochody (910/mc łącznie)**
„najtańszy chiński samochód" 590 plus „tanie chińskie samochody" 320. To jest dokładnie ten
intent cenowy, który zaśmieca frazy SUV-owe — lepiej obsłużyć go osobnym tekstem, który uczciwie
mówi, ile kosztuje sprowadzenie, niż udawać, że go nie ma. API sortuje po `min_price`.

**4. B1 — Chińskie samochody elektryczne, bestsellery (1 880/mc)**
Fraza duża, dane pełne, a temat jest rdzeniem tego, po co ludzie w ogóle patrzą na chiński rynek.
Naturalnie linkuje się z hasłami słownika (LFP, 800V, CLTC) i z hubami.

**5. A3 — Chińskie pickupy (2 480/mc)**
Najostrzejszy rozjazd popytu do podaży w całym serwisie: 2 480 wyszukań miesięcznie, w GSC
890 wyświetleń na „byd pickup" przy pozycji 7,0 — i zero treści u nas. Mamy tylko 6 modeli,
więc tekst musi to nazwać wprost, ale ranking rynkowy pokaże cały segment.

### Poza pierwszą piątką, ale warte uwagi

**E2 — popularność aktualizowana codziennie** (typ 1). Nie ma frazy, ale to jedyny znany mi
publiczny wskaźnik tego, czym Chińczycy interesują się *dziś*, a nie co kupili miesiąc temu.
Materiał na cykliczny news, nie na ranking.

**D3 — 800V** i **D6 — producenci baterii**: małe wolumeny, ale komplet danych po naszej stronie
i wysoka cytowalność przez modele językowe. Tanie w produkcji, bo nie wymagają nowego źródła.

## Czego świadomie nie robimy

- **F2 (marki wg sprzedaży)** — 10/mc, a fraza „chińskie marki samochodów" (5 400) należy już
  do `/marki/`. Publikacja rankingu marek to kanibalizacja własnej strony.
- **Rankingi jakościowe z API** (typy 2–5, 10) — nie wiemy, co dokładnie mierzą. Do rozpoznania,
  zanim cokolwiek z nich opublikujemy.
