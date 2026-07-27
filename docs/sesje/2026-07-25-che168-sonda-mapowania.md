# Sesja 2026-07-25 — Che168: sonda kanału + domknięcie mapowań (v0.34.3)

> Element T-186 (`docs/roadmapa/T-186-che168-automat.md`). Wejście: prompt kontynuacyjny
> `docs/sesje/2026-07-22-PROMPT-KONTYNUACJA-che168.md`.
> **Stan końcowy: oba źródła nadal WYŁĄCZONE, che168 na `draft`, kursor cofnięty na pozycję
> sprzed sesji nie był — stoi tam, gdzie go zostawił przerwany bieg (9 944 251).**

## Zmiana metody w trakcie sesji (decyzja Janka)

Prompt mówił „bieg wykonujemy tak jak poprzednio", a poprzednio bieg = **pełny import**
(cykle `Sync::run()` zapisujące oferty jako szkice, z pobraniem ~15 zdjęć i tłumaczeniem
opisu przez Gemini na każdą). Tak wystartowałem: 17 ofert w 20 min.

Janek to zatrzymał — słusznie. Rachunek dla całej zaległości: 59 tys. zdarzeń → **~500 draftów**
= ~7500 pobranych zdjęć + ~500 wywołań Gemini + ~5 h, dla danych, z których publikujemy garść.
**Do celu sesji — złapania nowych modeli, parametrów i miast — import jest w ogóle niepotrzebny.**

Metoda zastąpiona: **sonda read-only**. Przemiał kanału tak jak sync, ale zamiast importować —
zlicza i raportuje. Koszt: ~3000 wywołań `/changes` w 14 min, zero zapisów do bazy, zero zdjęć,
zero AI, kursor nietknięty. Do audytu parametrów dodatkowo `getOffer()` na 97 ofertach (24 s).

**Wniosek na przyszłość:** faza kalibracji źródła ≠ faza zbierania podaży. Dane do decyzji
o mapowaniach da się wyciągnąć bez dotykania bazy i bez kosztu obrazków/AI.

## Sonda kanału (read-only, 14 min)

| Co | Wynik |
|---|---|
| Zakres | change_id 9 944 251 → 10 003 251 (22.07 wieczór → 25.07 15:00) |
| Zdarzeń kanału | **59 811** |
| Z tego `added` | 17 026 (28%) |
| Przechodzi filtr Prima Auto | **246** (1,4% `added`) |
| Trafiało w hub PRZED sesją | 208 (85%) |
| **Trafia w hub PO sesji** | **242 (98,4%)** |
| Miasta bez tłumaczenia | **0** |

Kanał che168: **~19,5 tys. zdarzeń/dobę** (23.07: 19 780, 24.07: 19 111) — twardo z `getChangeId`
na starty dób, potwierdza pomiar 20–31 tys. z 22.07.

**Filtr miast, nie mapowania, jest głównym hamulcem podaży.** W rozkładzie powodów odrzutu
`miasto` występuje niemal w każdej kombinacji: samo `miasto` 1139, `miasto+marka/model` 1105,
`rocznik+przebieg+cena+miasto+marka/model` 3456. Miasta przechodzących ofert: Shenzhen 60,
Kanton 47, Foshan 30, Dongguan 28, Nanning 16, Fuzhou 14, Xiamen 12, Huizhou 11, resztki ≤8.
To ta sama prawda co „81% ucięte" z 22.07 — i to decyzja operacyjna Ruslana, nie ustawienie.

Roczniki przechodzących: 2025 = 142, 2024 = 74, 2026 = 30.

## Mapowania modeli — 18/18 orphanów zamkniętych

20 par orphanów (38 ofert). Każda rozstrzygnięta danymi — pełną chińską nazwą wersji
(`param_93` = 车型名称) i stanem taksonomii `serie`, nie „na oko".

**Odkrycie architektoniczne, które przesądziło o zakresie zmian.** Alias w `che168-model-map.php`
sam z siebie **nie odblokowuje guardu**. Ścieżka jest dwustopniowa: adapter woła
`canonicalKeyForSource()` → `resolveChe168()` (to czyta model-mapę) → dostaje entry → i tłumaczy
je **z powrotem na literalny klucz CN brand-mappingu** przez `sigToKey()`. Jeśli para
`mark_eu|serie_eu` nie istnieje w brand-mappingu, funkcja zwraca surowe mark/model i guard
(`isMappedForImport` → `getEuForCn`) dalej odrzuca ofertę. Zmierzone: po samej model-mapie
mapowało się **11/18**, po dołożeniu 7 sygnatur do brand-mappingu **18/18**.

Pierwotnie zdecydowałem „brand-mapping nietknięty" (strefa krucha, 6 referencji) — decyzja była
błędna, bo wynikała z niezrozumienia tej ścieżki. Test przez adapter to wychwycił przed commitem.

### 16 aliasów w `che168-model-map.php`

| Orphan che168 | Nazwa CN (param_93) | → serie_eu | Hub |
|---|---|---|---|
| `BYD\|海狮06` | 海狮06 2025款 DM-i | Sealion 6 DM | 3 |
| `BYD\|海狮05 EV` | — | Sealion 5 EV | 13 |
| `BYD\|汉L` | 汉L 2025款 **EV** (3/3 sztuki) | Han L EV | 25 |
| `BYD\|Tang New Energy` | 唐新能源 DM-i 115KM | Tang DM-i | 32 |
| `BYD\|Frigate 07` | 护卫舰07 荣耀版 DM-i | Frigate 07 | 4 |
| `BYD\|Seal 07 DM-i` | 海豹07 DM-i 2025款 | Seal 7 DM | 4 |
| `BYD\|Seal 07 EV` | — | Seal 07 EV | 0 |
| `BYD\|PLUS New Energy` | **宋PLUS新能源** EV 520km | Song PLUS EV | 5 |
| `Li Auto\|Li MEGA` | — | Li Auto MEGA | 8 |
| `XPeng\|小鹏G7` | 小鹏G7 702 超长续航 Ultra | G7 | 18 |
| `XPeng\|小鹏GX` | 小鹏GX 2026款 增程 1585四驱Ultra | GX | 2 |
| `Galaxy\|银河A7 EM` | — | Galaxy A7 EM-i | 38 |
| `Galaxy\|银河星舰7` | — | Galaxy Starship 7 EM-i | 5 |
| `Chery\|瑞虎8L` | 瑞虎8L 2024款 **2.0T** | Tiggo 9 (Tiggo 8L) | 18 |
| `Hongqi\|红旗天工06` | — | Tiangong 06 | 3 |
| `Haval\|哈弗大狗 PLUS` | — | Big Dog | 9 |
| `Haval\|Menglong New Energy` | 哈弗猛龙新能源 **Hi4** 145 穿越版 | Menglong Hi4 (nowy hub) | — |
| `Tank\|Tank 500 New Energy` | 坦克500新能源 **Hi4-T** | 500 Hi4-T (nowy hub) | — |

Pułapki, które wymagały danych, nie intuicji:
- **`Tang New Energy` ≠ Sealion 8.** 唐新能源 to Tang DM-i; Sealion 8 to Tang **L**, osobny model
  z własnymi termami (17 + 4 listingi). Alias „na oko" wsypałby oferty do złego huba.
- **`汉L` jest engine-agnostyczne** w che168 (jak `海豹06` 22.07) — sprawdzone na wszystkich
  3 sztukach, każda EV, więc override celuje w `Han L EV` (25 listingów vs `Han L DM` 14).
- **`XPeng|小鹏GX` to realny nowy model** (premiera 2026-05-20, EV + EREV, 6 miejsc), a nie
  literówka; term `gx` był już zasiedlony 2 ofertami z ręcznego importu che168.
- **Dublety termów w bazie** rozstrzygane regułą „wygrywa zasiedlony": `Seal 7 DM` (4) zamiast
  pustego `Seal 07 DM`, `Tiangong 06` (3) zamiast pustego `Hongqi Tiangong 06`,
  `G7` (18, XPeng) zamiast pustej sieroty `XPeng G7`.

### 7 sygnatur w `brand-mapping-v6.1.php` (sekcja v6.4)

`Galaxy A7 EM-i`, `Li Auto MEGA`, `Frigate 07`, `Seal 07 EV`, `GX`, `Menglong Hi4`, `500 Hi4-T`.
Wyłącznie nowe klucze. Kontrola regresji przez porównanie z backupem: **284 → 291 wpisów,
0 zmienionych, 0 usuniętych** → dla dongchedi zmiana zerowa.

### Świadome skipy (bez zmian)

`Nissan|Teana` (2 szt., benzyna), `Nissan|Qashqai` (2 szt., benzyna) — zgodnie z decyzją 20.07.

## Parametry — nieznane 457 → 194 wystąpień

Audyt na **97 ofertach** (po jednej na unikalny model z 246 przechodzących), 24 s.
Specyfikacja: **89,6 wiersza na ofertę** (22.07 było 60,9).

20 zmapowanych id. Cztery celują w klucze, które dongchedi już ma — `compression_ratio_s`,
`engine_unique_tech`, oraz `electric_total_horsepower` dla id 102 i 140 (ta sama wartość w dwóch
grupach, wzorzec 84/105; sprawdzone, że id 140 jest zwykle „-", więc nic nie ginie).
Pozostałe 16 to nowe wielkości i **żadna nie nadpisuje istniejącego klucza** — każda różni się
warunkiem pomiaru albo jednostką:

| id | Nazwa CN | Klucz | Dlaczego nie istniejący |
|---|---|---|---|
| 130 | 空载最小离地间隙 | `min_clearance_unloaded` | `min_clearance` (34) to 满载 = z pełnym ładunkiem |
| 149 | NEDC综合续航 | `nedc_combined_range` | `nedc_recharge_mileage` (153/61) to zasięg czysto elektryczny |
| 141 | 系统综合功率(Ps) | `electric_system_horsepower` | moc **układu**, a 102 to moc **silników** |
| 132 | 前备厢容积 | `front_baggage_volume` | `baggage_volume` (39) to bagażnik tylny |
| 136 / 137 | 最大爬坡度(%) / 角度(°) | `max_gradient_percent` / `_angle` | ta sama cecha w dwóch jednostkach |
| 127 | 官方0-50km/h | `acceleration_time_50` | `acceleration_time` (97) to 0-100 |
| 99 / 113 | 实测快充时间 / 实测油耗 | `measured_*` | pomiar vs wartość katalogowa |
| 147 | 高压快充 | `hv_fast_charge` | `quick_charge_interface` (145) to 快充功能 |
| 63 | 中央差速器结构 | `center_diff_struct` | `center_diff_lock` to blokada, inna cecha |

Reszta bez odpowiednika: `cylinder_bore` (7), `piston_stroke` (8), `max_wading_depth` (138),
`rampover_angle` (135, obok istniejących `approach_angle`/`departure_angle`), `nedc_fuel_consumption` (95).

Nadal bez mapowania **świadomie**: `92` (长*宽*高 — duplikuje `length/width/height`),
`93` (车型名称 — źródło tytułu i wersji). To całe pozostałe 194 wystąpienia.

## Ubocznie naprawiony istniejący błąd

`translateExtraPrep()` pomija wiersz, gdy wartość po tłumaczeniu **nadal zawiera CJK**
(„frontend never shows raw Chinese"). Wartość `支持` nie miała wpisu w `values`, więc
**`quick_charge_interface` cicho znikał z karty oferty w 26 z 30 ofert próbki** — pole było
poprawnie zmapowane od 22.07, tylko render je wyrzucał. To samo dotyczyło `battery_swap` (NIO).

Dodane do `values`: `支持`→Tak, `不支持`→Nie, plus 5 struktur dyferencjału centralnego
(`多片离合器`→Sprzęgło wielotarczowe, `电控多片离合器`, `托森式`→Torsen, `牙嵌式`, `开放式`).

## Weryfikacja końcowa

Przez `translateExtraPrep()`, czyli dokładnie to, co widzi klient:

| Oferta | Wierszy w renderze | CJK |
|---|---|---|
| XPeng GX (59124508) | 104 | **0** |
| Tank 500 Hi4-T (59132235) | 107 | **0** |

Nowe pola wychodzą z polskimi etykietami, jednostkami i we właściwych kategoriach —
„Głębokość brodzenia: 700 mm", „Bagażnik przedni (frunk): 42 L", „Dyferencjał centralny:
Sprzęgło wielotarczowe", „Moc układu (KM): 408" obok „Moc układu elektrycznego (KM): 163"
(poprawnie rozróżnione 141 vs 102). Smoke test hubów: `/samochody/xpeng/gx/`,
`/geely/a7-em/`, `/byd/frigate-07/` → 200.

## Blokada wyposażenia — potwierdzona na 97 ofertach

API oddaje **7 grup technicznych i zero grup wyposażenia**: 基本参数, 车身, 变速箱, 底盘转向,
车轮制动 (po 97), 电动机 (83), 发动机 (50). Brak 座椅配置 (fotele), 主动安全/被动安全,
空调/冰箱, 选装包, 多媒体. Wcześniej wiedzieliśmy to z 3 ofert — teraz z 97, więc nie jest to
artefakt próbki.

**Auto-api nie odpisało** (skrzynka `claude@auratest.pl` sprawdzona 25.07 — 15 nowych maili
do [150], nic od `access@auto-api.com`; najnowszy w wątku to nadal nasze zapytanie [147]
z 22.07 21:22). Rozwidlenie (domapowanie nowych grup vs dziedziczenie z bliźniaków
w wariancie konsensusu) zostaje zamrożone.

## Stan bazy na koniec

| Co | Liczba |
|---|---|
| Drafty che168 | 69 (52 z 22.07 + 17 z przerwanego biegu) |
| Publish che168 | 46 (35 z ręcznych importów VI, 11 z 24–25.07) |
| Trash | 2 |
| Kursor `asiaauto_last_change_id_che168` | 9 944 251 |
| Wyłączniki | `asiaauto_sync_enabled`=0, `_dongchedi`=0, `_che168`=0 |
| Status importu che168 | `draft` (default w kodzie) |

Bieg zatrzymany czysto: brak sierot procesów, `IS_USED_LOCK('asiaauto_sync_che168')` = NULL.

## Do decyzji Janka

1. **11 ofert opublikowanych 24–25.07** z ręcznych importów zawiera `Toyota Keti Mountain 3.5L`
   i `Volkswagen Talagon 450TSI` — spalinowe marki spoza segmentu (ręczny import omija filtr
   przez `force=true`). Są **live na produkcji**. Jeśli to były testy przeglądarki — cofnąć do szkiców.
2. **Dublety termów w taksonomii** (`Seal 07 DM` vs `Seal 7 DM`, `Tiangong 06` vs
   `Hongqi Tiangong 06`, `XPeng G7` vs `G7`, `Tank 500*` ×3). Dwa huby na jeden model konkurują
   w SEO. Zgłoszone jako obserwacja, nietknięte — to osobny task porządkowy.
3. **Filtr 31 miast** — nadal ucina ~81% dobrej podaży che168. Decyzja Ruslana.

## Zostało do go-live

- Dedup po VIN (che168 ma VIN w 100%, dongchedi nie ma; zmierzony overlap egzemplarzy 0/30,
  więc guard może być lekki).
- Przegląd 69 szkiców przez Janka → publikacja albo poprawki.
- Włączenie: `asiaauto_sync_status_che168` = `publish`, `asiaauto_sync_enabled_che168` = 1,
  `asiaauto_sync_enabled` = 1, obserwacja dobowa.
- Panel „Źródła" (dziś uproszczony toggle w Status) — opcjonalny.

## Artefakty

- Raport sondy: `https://auratest.pl/fe4f58fec53ctmp/primaauto-che168-sonda-raport-2026-07-25.md`
- Nowe skrypty (read-only): `scripts/che168-sonda-kanalu.php`, `che168-audyt-parametrow.php`,
  `che168-nazwy-parametrow.php`, `che168-audyt-partii.php`
- Backupy: `data/*.bak-2026-07-25-sonda` (model-map, param-map, translations-extra-prep, brand-mapping)

## Godziny

Sesja 25.07: ~4 h (sonda + audyt parametrów + rozstrzygnięcie 20 orphanów + wdrożenie mapowań
+ weryfikacja). Doliczyć do sumy T-186 przy finalnym wpisie w postępie prac — zgodnie z decyzją
Janka z 22.07 T-186 idzie tam **jednym wpisem po uruchomieniu całości**, nie po sesji.
