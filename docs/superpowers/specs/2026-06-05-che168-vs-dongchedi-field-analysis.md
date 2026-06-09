# Che168 vs dongchedi — analiza field-level konkretnych ofert

**Data:** 2026-06-05
**Status:** IN PROGRESS — budowany iteracyjnie, model po modelu (ręczne `getOffer`).
**Cel:** zrozumieć różnice w parametrach / `extra_prep` / specyfikacji technicznej między źródłami na **realnych ofertach tego samego modelu**, zanim ruszymy z importerem dual-source.
**Metoda:** read-only `getOffer` Che168 (probe `tmp/che168-denza-probe-2026-06-05.php`) + porównanie z naszym żywym listingiem dongchedi w bazie (`wp7j_postmeta._asiaauto_*`). Skrypt porównawczy: `tmp/n8l-compare-2026-06-05.php`.
**Powiązane:** `2026-06-01-che168-recon-report.md`, `2026-06-01-che168-second-source-design.md`.

---

## 0. KOREKTA wniosku z reconu 01.06 ⚠

Recon-report (Q1) twierdził: *„getOffer Che168 zwraca 34 pola vs dongchedi ~15"* — sugerując że Che168 jest bogatsze w dane.

**To było mylące — liczyło tylko pola TOP-LEVEL, pomijając `extra_prep`.** Po rozłożeniu realnej oferty:

| Wymiar | dongchedi | Che168 |
|---|---|---|
| Pola top-level | ~15 (mark, model, year, price, km_age, color, complectation, displacement, description, images, city/address, is_dealer, salon, seller, owners) | 34 (jw. + **vin**, **first_registration**, drive_type, body_type, engine_type, granularne moce ice/hybrid/electric ps/kw) |
| Specyfikacja / wyposażenie | **`extra_prep` = 361–405 pól** | **`extra.configuration` = 92 parametry** (7 grup) + `extra.option.displayopts` (4) |
| Format specyfikacji | płaskie klucze EN → wartości CN (unicode-escaped) | grupy nazwane CN + ID parametru + value |

**Wniosek odwrócony: dongchedi ma ~4× więcej pól specyfikacji niż Che168.** Che168 wygrywa formatem (czytelne grupy, ID) i ma VIN + datę rejestracji, ale jest UBOŻSZE w wyposażenie. Recon nie docenił dongchedi bo nie zajrzał do `extra_prep`.

---

## 1. Denza N8L (2025 Flagship / 尊荣型) — rozkład na czynniki pierwsze

Porównanie tego samego modelu:
- **dongchedi:** post `260999` „Denza N8L 2025 Flagship Version" (iid 20929778 to inny listing; ten N8L iid wewn. dongchedi)
- **Che168:** iid `57877387`, model w API = `腾势N8L` (uwaga: CN, nie „Denza N8L")

### 1a. Top-level — co każde źródło daje

| Pole | dongchedi (post 260999) | Che168 (iid 57877387) |
|---|---|---|
| mark | Denza | Denza |
| model | Denza N8L | **腾势N8L** ← CN forma |
| year | 2025 | 2025 |
| price (CNY) | 279000 (2 właścicieli) | 257000 (6000 km, 1. rej.) |
| km_age | — (w extra) | 6000 |
| **vin** | **BRAK** | LC0DD4C4XS6293407 |
| **first_registration** | **BRAK** | 2025-11 |
| engine_type | (w extra: 插电式混合动力) | plug-in hybrid |
| drive_type | (w extra) | awd (front-engine) |
| horse_power | 207 | 207 (power) + granularne ice/electric |
| owners_count | 2 | (w extra.option.map: 过户次数) |
| city | 广州 (Guangzhou) | 石家庄 (Shijiazhuang) — address bez dzielnicy |

**Wartości techniczne zgodne między źródłami** (oba: 大型SUV, length 5200, wheelbase 3075, width 1999, 2.0T 207KM, PHEV) → **dobre dla dedup heurystycznego po specyfikacji**.

### 1b. Specyfikacja — struktura

**dongchedi `extra_prep`: 405 pól** (płaskie). Przykłady zdekodowane (klucz EN = wartość CN):
- Wymiary/technika: `length=5200`, `width=1999`, `height=1820`, `wheelbase=3075`, `max_speed=220`, `capacity_l=2.0`, `engine_model=BYD479…`, `gas_form=涡轮增压` (turbo), `battery_type=磷酸铁锂` (LFP)
- **Wyposażenie (to czego Che168 NIE rozbija):** `gps=标配`, `wifi=标配`, `ar_hud=标配`, `karaoke=标配`, `speaker=20`, `auto_park=标配`, `tank_turn=标配` (tank turn), `wing_door=标配`, `queen_seat=标配`, `app_store=标配`, `car_purifier=标配`, `active_brake=标配`, 3 typy kluczy, 4 tryby jazdy…
- `标配` = „w standardzie" (boolean wyposażenia)

**Che168 `extra.configuration`: 92 parametry w 7 grupach** — wyłącznie technika, zero flag wyposażenia komfort/multimedia:

| Grupa (CN) | PL | # |
|---|---|---|
| 基本参数 | parametry podstawowe | 23 |
| 车身 | nadwozie | 17 |
| 发动机 | silnik spalinowy | 21 |
| 电动机 | silnik elektryczny | 22 |
| 变速箱 | skrzynia | 3 |
| 底盘转向 | podwozie/układ kierowniczy | 7 |
| 车轮制动 | koła/hamulce | 6 |

+ `extra.option.displayopts` = tylko 4 wyróżnione (asystenci: martwe pole, pas ruchu, hamowanie awaryjne).

Che168 bonus: wydzielona grupa **电动机** (22 param: typ ogniw 磷酸铁锂/弗迪=FinDreams, moce 3 silników, ładowanie CLTC 230 km, 46.9 kWh). dongchedi ma to porozrzucane płasko w extra_prep.

---

## 1B. Denza D9 (masowy model — 106 szt. w próbce Che168) — 2026-06-05

Porównanie:
- **dongchedi:** post `94073` „Denza D9 DM-i 2024 980 4WD Premium Version"
- **Che168:** iid `57888520`, model w API = `Denza D9` (EN, z marką w stringu), 车型名称 = `腾势D9 2024款 DM-i 980 四驱旗舰型` → **ten sam wariant** (DM-i 980 4WD flagship)

### Top-level

| Pole | dongchedi (94073) | Che168 (57888520) |
|---|---|---|
| price (CNY) | 255300 (1 wł.) | 335600 (3700 km, prawie nowy) |
| km | (w extra) | 3700 |
| vin | BRAK | LC0DD4C42R0290028 |
| first_registration | BRAK | 2024-09 |
| city | 广州 | 西安 (Xi'an) |
| hp | 139 (silnik spalinowy) | engine 1.5T 139KM + el. 299 kW |
| spec | extra_prep **361 pól** | configuration **109 param** (7 grup) |

D9 ma więcej param Che168 niż N8L (109 vs 92) — grupa 电动机 = 27 (vs 22). `option.displayopts` tego egzemplarza = **0** → wyróżnione opcje są **zmienne per oferta** (salon wypełnia ad-hoc), nie strukturalne. Nie liczyć na nie.

### ⭐ Zgodność wartości technicznych — PERFEKCYJNA (kluczowy dowód dla dedup)

| Parametr | dongchedi | Che168 | match |
|---|---|---|---|
| długość (mm) | 5250 | 5250 | ✓ |
| szerokość (mm) | 1960 | 1960 | ✓ |
| wysokość (mm) | 1920 | 1920 | ✓ |
| **rozstaw osi (mm)** | 3110 | 3110 | ✓ |
| pojemność (L) | 1.5 | 1.5 | ✓ |
| segment | 中大型MPV | 中大型MPV | ✓ |

**Wszystkie wymiary identyczne co do cyfry.** To znaczy: gdy ten sam wariant istnieje w obu źródłach, **dopasowanie spec (długość + rozstaw osi + pojemność) jest twardym dyskryminatorem** — mocniejszym niż tolerancyjne km/price. Rekomendacja do dedup: dorzucić wymiary jako sygnał potwierdzający (exact), a km/price jako tolerancyjny tie-breaker.

---

## 2. Implikacje (z N8L + D9; do potwierdzenia kolejnymi modelami)

### Dla brand/model mapping ⚠ KRYTYCZNE
- Che168 zwraca **niespójne nazwy modeli**: `Denza D9`/`N7`/`Z9`/`X` (EN) ale `腾势N8L`/`腾势N9` (CN). Nowsze modele (premiera 2025) nie mają jeszcze formy EN w auto-api.
- **Pole `model` Che168 zawiera markę w stringu** (`"Denza D9"`, nie samo `"D9"`; `"腾势N8L"` = 腾势+N8L bez separacji). Importer musi strippować markę z modelu.
- Nasz importer ma brand-mapping CN→EU, ale model `腾势N8L` wpadłby jako CJK w tytuł → ryzyko (memory: dbamy o „0 CJK w post_title publish"). **Mapping musi obsłużyć formy 腾势* dla Che168.**

### Dla dedup
- ✅✅ **Wymiary identyczne co do cyfry** między źródłami (D9: dł/szer/wys/rozstaw/pojemność wszystkie ✓). **To twardszy dyskryminator niż km/price.** Rekomendacja: dedup = mark+model(znormalizowany)+year+**wymiary exact** jako rdzeń, km±5%/price±10% jako tie-breaker (nie rdzeń — różne egzemplarze tego samego modelu mają różne km/price).
- ⚠ `model` string nie matchuje dosłownie (`Denza N8L` vs `腾势N8L`; `Denza D9` zawiera markę) — dedup MUSI normalizować model PRZED porównaniem (strip marki + CN→EN), inaczej cross-source = false negative.
- ✅ VIN Che168 = 100% → wewnątrz-Che168 dedup deterministyczny (N8L `LC0DD4C4XS6293407`, D9 `LC0DD4C42R0290028`, walidowalne).

### Dla importera / `extra_prep`
- Che168 `extra.configuration` to **inny kształt** niż dongchedi `extra_prep` (grupy vs płaskie). Importer dual-source potrzebuje **adaptera per źródło** mapującego Che168 grupy → nasz model `extra_prep` (albo trzymamy 2 schematy i front renderuje oba).
- Strata przy Che168: brak ~300 flag wyposażenia (karaoke, hud, foteli, asystentów) które dongchedi daje. Karty Che168 byłyby UBOŻSZE w sekcji wyposażenia.
- Zysk przy Che168: VIN + data 1. rejestracji (eliminuje workaround `reference_dongchedi_year_fields`), wydzielone parametry EV.

---

## 1C. Hongqi (红旗) — test sub-brandów — 2026-06-05

120 ofert Hongqi w Che168 (filtr `mark=Hongqi` działa). Rozkład modeli ujawnia **wzorzec sub-brandów**:

**Główny trzon = angielski (pokryty mappingiem):**
`Hongqi H5` ×27, `HS5` ×23, `H9` ×13, `E-HS9` ×12, `EH7` ×8, `H7` ×7, `HQ9` ×6, `E-QM5`, `HS7`, `H6`, `Tiangong 08`, `E-HS3`… → wszystkie EN. `brand-mapping-v6.1` ma **14 kluczy `Hongqi|Hongqi *`** → **match 1:1** (dongchedi też używa EN dla Hongqi, więc Che168 wpada w ten sam klucz).

**Sub-brand 金葵花 (Jinkuihua / „Golden Sunflower", linia luksusowa) = chiński (LUKI):**
`红旗金葵花国悦` ×3, `红旗金葵花国雅` ×2, `红旗金葵花国耀` ×1 → pełna forma CN (marka+sub-brand+wariant).
- `translations-models.php` pokrywa **2/3**: `红旗金葵花国雅→Jinkuihua Guoya`, `红旗金葵花国悦→Jinkuihua Guoyue`. **`国耀` (Guoyao) BRAK.**
- Żaden z nich nie jest w `brand-mapping-v6.1` → bez douzupełnienia = **orphan**.
- Plus `Mingshi` ×1, `Shengshi` ×2 — pojedyncze, nietypowe.

**Kontrast segmentów (potwierdza recon):** nasze dongchedi-Hongqi to świeże NEV (`H5 2025`, `EH7 2025`, `Tiangong 05/06 2025`, `H5 PHEV`). Reprezentant Che168 wylosował się jako `红旗盛世 2009款 4.3L 精英型` (Shengshi 2010, V8 benzyna, 级别=中大型车) — **stare używane**. Ten sam producent (一汽红旗 / FAW Hongqi), zupełnie inny segment rocznikowy.

**Wniosek o skali normalizatora (uściślenie z 2C):** problem nazw CN **nie dotyczy głównego trzonu** (główne modele Che168 są EN i trafiają w istniejący mapping). Luki to **ogon CN: sub-brandy luksusowe + świeże premiery** (Denza `腾势N8L/N9`, Hongqi `金葵花国耀`). Normalizator/douzupełnienie mappingu celuje w ten ogon, nie w cały katalog → mniejszy zakres niż zakładano.

---

## 1D. Zeekr (极氪) — EV-only, ujawnia niespójność formatu `model` ⚠ — 2026-06-05

120 ofert Zeekr. Wynik **koryguje optymizm z Hongqi** — pojawia się problem niewidoczny wcześniej.

**Modele Che168:** `001` ×48, `009` ×36, `X` ×13, `007` ×4, `7X` ×1 (EN, **bez marki w stringu!**) + `极氪007GT` ×9, `极氪9X` ×9 (CN).

### ⚠ Pole `model` Che168 ma format NIESPÓJNY per marka

| Marka | `model` Che168 | zawiera markę? |
|---|---|---|
| Denza | `Denza D9`, `腾势N8L` | tak (EN i CN) |
| Hongqi | `Hongqi H5`, `红旗金葵花国悦` | tak |
| **Zeekr** | **`001`, `009`, `X`** | **NIE** (samo oznaczenie) / `极氪007GT` CN tak |

### ⚠ Brand-mapping coverage Zeekr = 0% — mimo że modele są po angielsku

`brand-mapping-v6.1` ma klucze `Zeekr|ZEEKR 001`, `Zeekr|ZEEKR 009` (model = `ZEEKR 001`, CAPS, z marką — format dongchedi). Che168 daje `model=001` → klucz `Zeekr|001` → **brak**. **Wszystkie 7 modeli Zeekr nietrafione**, choć są EN. `translations-models` dla `极氪` = BRAK.

**To obala wniosek z 1C** („główny trzon EN = pokryty"). Pokrycie nie zależy od EN vs CN, lecz od **zgodności formatu stringa `model`** z kluczem dongchedi — a ten różni się per marka (z marką / bez / CAPS / CN).

### Wymiary cross-source — rozstaw osi + szerokość znów identyczne

Model 001 (dongchedi #249371 vs Che168 #55765635):

| Parametr | dongchedi | Che168 | |
|---|---|---|---|
| rozstaw osi (mm) | 3005 | 3005 | ✓ identyczny |
| szerokość (mm) | 1999 | 1999 | ✓ identyczny |
| długość (mm) | 4977 | 4970 | −7 (różne warianty/rocznik 2023/2024) |
| wysokość (mm) | 1545 | 1560 | +15 |

**Uściślenie dla dedup:** **rozstaw osi (轴距) to najpewniejszy dyskryminator** — konstrukcyjny, stały w obrębie modelu. Długość/wysokość mogą drgać między rocznikami/wariantami → dla nich tolerancja ±~20 mm, nie exact. (N8L i D9 miały wszystko exact bo to był ten sam wariant; 001 to różne warianty → widać które wymiary są stabilne.)

Spec Che168 Zeekr 001 = 72 param (brak grupy 发动机 — czysty EV, jest tylko 电动机). Potwierdza: liczba grup zależy od napędu (ICE+EV: 7 grup, czysty EV: 6, bez silnika spalinowego).

---

## 1E. Zeekr — weryfikacja krzyżowa 3 warstw (surowe Che ↔ surowe dongchedi ↔ nasza baza) — 2026-06-05

Bo mamy warstwę obróbki (translator + mapping) między API a bazą, zweryfikowałem Zeekr na surowych danych OBU źródeł, nie na założeniach. Skrypt: `tmp/zeekr-crosscheck-2026-06-05.php`.

| Warstwa | Źródło | `mark` | `model` | klucz `mark\|model` w brand-mapping |
|---|---|---|---|---|
| **1. surowe dongchedi** (getOffer API) | `ZEEKR 001`, `ZEEKR 009`, `ZEEKR X` | Zeekr | **`ZEEKR 001`** (z marką, CAPS) | ✅ OK (`Zeekr\|ZEEKR 001`) |
| **2. nasza baza** (po obróbce) | serie-term | Zeekr | **`001`, `7X`, `007 GT`** (czyste) / czasem `Zeekr 9X` | — (to już nasz term, nie klucz) |
| **3. surowe Che168** (getOffer API) | Zeekr | **`001`, `009`, `007`** (bez marki) | ❌ BRAK (`Zeekr\|001`) |

**Werdykt potwierdzony:** brand-mapping jest poprawny dla dongchedi (surowy dongchedi = `ZEEKR 001` = klucz). Che168 daje `001` (inny format) → exact-key 0%. **Mój wcześniejszy wniosek trzyma się na surowych danych.**

### ⭐ Odkrycie które zmienia rozwiązanie: nasze `serie_eu` == format Che168

Warstwa 2 pokazała, że **nasze docelowe `serie_eu` to czyste oznaczenia** (`001`, `7X`) — czyli **TEN SAM format co Che168 `model`**. Test indeksu odwrotnego z istniejącego brand-mappingu po `(mark_eu, serie_eu/slug)`:

```
Che168 model="001" → serie_eu "001" → Zeekr 001 ✓   (cały trzon Zeekr 100%)
Che168 model="极氪9X" → strip 极氪 → "9X" → Zeekr 9X ✓
Che168 model="Hongqi H5" → strip marki → "H5" → Hongqi H5 ✓
Che168 model="腾势N8L" → strip 腾势 → "N8L" → Denza N8L ✓
```

**Nie trzeba budować nowego mappingu** — wystarczy odwrócić istniejący po `serie_eu`/`slug`. Ale test ujawnił 3 klasy przypadków brzegowych:

| Przypadek | Przykład | Status | Powód |
|---|---|---|---|
| serie = czyste oznaczenie | Zeekr `001`/`9X`, Hongqi `H5`, Denza `N7`/`N8L` | ✅ działa | Che168 model == serie_eu |
| **serie rozbite per napęd** | Denza `D9 DM-i` vs `D9 EV` | ❌ wymaga reguły | Che168 `model="Denza D9"` bez napędu → trzeba dołożyć `engine_type` (PHEV→DM-i, EV→EV) by wybrać wariant serie |
| sub-brand CN spoza mappingu | Hongqi `金葵花国耀` | ❌ luka | brak wpisu + brak w `translations-models` |
| różnica formatowania | Che168 `极氪007GT` vs serie `007 GT` | ❌ normalizacja | brak spacji |

**To jest realny, zmierzony zakres resolvera** — nie hipoteza. Główny trzon każdej marki działa przez reverse-index; koszt to ~3 reguły normalizacji (CN-prefix, napęd-z-engine_type, spacje/CAPS) + douzupełnienie ogona CN.

---

## 1F. ⭐ Test overlap EGZEMPLARZY — 30 ostatnich dongchedi vs Che168 (2026-06-05)

**Pytanie Janka:** może nie trzeba komplikować dedup — sprawdźmy czy oferty Che168 wpadłyby identyczne jak nasze dongchedi. Test: 30 ostatnich dongchedi z bazy × surowy Che168 (pule per marka, 18 marek po ~120 ofert). Skrypt: `tmp/cross30-2026-06-05.php` + weryfikacja `tmp/verify3-2026-06-05.php`.

### Wynik

| Klasa | Liczba | Znaczenie |
|---|---|---|
| **SAME-CAR (ten sam egzemplarz)** | **0** | (3 kandydatów — wszystkie false positive, patrz niżej) |
| SAME-MODEL (ten sam model, inny egz.) | 6 | Che ma model, ale inne sztuki |
| NO-MODEL (Che nie ma modelu) | 16 | |
| NO-MARK (Che nie ma marki) | 5 | AITO, Luxeed = 0 ofert w Che168 |

**21/30 (70%) — Che168 nie ma nawet modelu/marki.** Świeże premiery których Che168 (rynek wtórny) nie zna: AITO M8/M9, Luxeed R7/S7, Xiaomi YU7, Li Auto L8, Zeekr 9X, BYD Tang/Song L/Sealion 5, Voyah Dream, Geely Galaxy Starship.

### 3 kandydatów SAME-CAR — wszystkie FALSE POSITIVE (różne miasta + VIN)

| Para | nasze miasto | Che168 miasto | km (nasz/Che) | werdykt |
|---|---|---|---|---|
| NIO ES6 2025 | 广州 Guangzhou | 郑州 Zhengzhou | 14000 / 12900 | różne egz. |
| Voyah FREE 2025 | 深圳 Shenzhen | 武汉 Wuhan | 7000 / 7000 | różne egz. |
| Avatr 12 2025 | 深圳 Shenzhen | 济南 Jinan | 9700 / 9500 | różne egz. |

Wymiary **identyczne** (NIO 4854/2915, Avatr 5020/3020) — bo ten sam MODEL, nie egzemplarz. km/price zbieżne przypadkiem (nowe auta dealerskie: niskie km + cena rynkowa). VIN Che168 za każdym razem inny.

### Wnioski — DWA różne problemy, nie jeden

1. **Dedup cross-source (Che↔dongchedi) jest MARGINALNY.** 0/30 realnych duplikatów egzemplarzy. Ten sam VIN ogłoszony na obu platformach jednocześnie = rzadkość (różni sprzedawcy, różne platformy). **Nie komplikować** — wystarczy lekki guard, nie zaawansowana heurystyka.
2. **MIASTO to decydujący dyskryminator egzemplarza** — wszystkie 3 FP miały identyczne wymiary i zbliżone km/price, rozróżniło je dopiero miasto. Jeśli robimy guard cross-source: miasto MUSI być warunkiem wymaganym, nie „boostem".
3. **Resolver marka+model (5A.3) zostaje potrzebny — ale do TAKSONOMII, nie dedup.** Importowane auto Che168 musi trafić do właściwego make/serie term (inaczej orphan). To realny powód resolvera, niezależny od dedup.

**Rewizja rekomendacji:** kierunek upraszczamy — Che168 to w ~przeważającej części **unikalny katalog** (inne modele/egzemplarze), nie lustro dongchedi. Wartość Che168 = poszerzenie oferty, nie duplikaty. Maszyneria dedup może być minimalna (VIN wewnątrz-Che + guard z miastem); inwestycja idzie w resolver taksonomii + adapter spec.

---

## 2C. Słowniki tłumaczeń w projekcie — co reużyć dla Che168 (2026-06-05)

Mamy rozbudowany system słowników w `wp-content/plugins/asiaauto-sync/data/` (ładowane przez `class-asiaauto-translator.php` + `class-asiaauto-mapping.php`). Audyt pod kątem Che168:

| Warstwa | Plik | Wpisy | Reuse dla Che168 | Uwaga |
|---|---|---|---|---|
| Marka+model → EU | `brand-mapping-v6.1.php` | 1355 | ⚠ **częściowo** | klucz = `markaCN\|modelCN` w formacie dongchedi, np. `Denza\|Denza D9 DM`, `Denza\|Denza N8L DM`. Che168 daje `mark=Denza` + `model=Denza D9` (BEZ ` DM`) lub `腾势N8L` (CN, BEZ marki, BEZ DM). **Klucz nie trafia 1:1** |
| Model CN → EN | `translations-models.php` | 39 | ⚠ **częściowo** | ma `腾势N8L DM→N8L DM`, `腾势D9 DM→D9 DM`… ale Che168 zwraca `腾势N8L` bez ` DM` → miss |
| Wersja CN → EN | `translations-complectations.php` | 244 | ✅ **tak** | `尊荣型→Premium`, `旗舰型→Flagship` działają na `车型名称` Che168 (`腾势D9 …四驱旗舰型`) |
| Wartości spec CN → PL | `translations-extra-prep-values.php` | 216 | ✅ **w większości** | wartości chińskie wspólne dla obu źródeł: `标配`, `涡轮增压`, `磷酸铁锂电池`, `插电式混合动力`, `中大型MPV` — wszystkie obecne. Brakuje części zawieszeń (`双叉臂式独立悬架`) → douzupełnić |
| Nazwy/klucze parametrów | `translations-extra-prep.php` (labels) | 1019 | ❌ **nie** | zbudowane dla **kluczy dongchedi** (`jb`, `length`, `width`, `wheelbase`…). Che168 nie ma tych kluczy — ma chińskie nazwy parametrów + **stabilne `id`** |
| Pozostałe (city/color/body/engine/drive/transmission/seller) | `translations-*.php` | — | ✅ pola top-level Che168 są EN (`plug-in hybrid`, `awd`, `crossover/suv`) → translacja w ogóle nie potrzebna albo te same mapy |

### Kluczowy wniosek: Che168 mapujemy po `id`, nie po nazwie CN

Che168 `paramitems` mają **stabilne numeryczne `id`** (id=28 长度/długość, id=31 轴距/rozstaw, id=91 级别/segment, id=90 能源类型/typ energii…). Adapter spec Che168 → nasz model **nie wymaga tłumaczenia chińskich nazw** — wystarczy jednorazowa tabela `id → label PL` (~50–100 ID). Wartości CN tłumaczymy istniejącym `translations-extra-prep-values.php` (reuse).

### Co trzeba dorobić dla Che168 (po Zeekr — zakres większy niż 2 artefakty)

**Problem `model` (po 4 markach):** klucz dongchedi `markaCN|modelCN` w `brand-mapping-v6.1` ma format niespójny z Che168 i **różny per marka**:
- Denza/Hongqi: `model` z marką (`Denza D9`) ale klucz dongchedi ma suffix napędu (`Denza D9 DM`)
- Zeekr: `model` bez marki (`001`) a klucz ma `ZEEKR 001` (CAPS + marka)
- świeże premiery / sub-brandy: CN (`腾势N8L`, `极氪9X`, `红旗金葵花国耀`)

Exact-key lookup zawodzi w każdym z tych przypadków. **Pewny jest tylko `mark` Che168** (EN, poprawny: Zeekr/Denza/Hongqi).

1. **Resolver marka+model Che168 → nasz term** = **indeks odwrotny istniejącego brand-mappingu** po `(mark_eu, serie_eu)` i `(mark_eu, slug)` (potwierdzone w 1E: nasze `serie_eu` ma format Che168). Reguły normalizacji modelu Che168 przed lookupem:
   - **CN-prefix marki:** `腾势→Denza`, `极氪→Zeekr`, `红旗→Hongqi`, `深蓝→Deepal`… (strip prefiksu z modelu)
   - **strip marki EN** z modelu (`"Denza D9"`→`"D9"`, `"Hongqi H5"`→`"H5"`)
   - **napęd z `engine_type`** dla marek z serie rozbitym per napęd (Denza): `plug-in hybrid`→ wariant `DM-i`, `electric`→`EV` → match `D9 DM-i`/`D9 EV`
   - **normalizacja formatu:** CAPS, spacje (`007GT`→`007 GT`)
   - **fallback:** wymiary (rozstaw osi) jako tie-breaker gdy niejednoznaczne
   - douzupełnić ogon CN spoza mappingu (`金葵花国耀` itp.) — ręcznie, mała lista
2. **Tabela `che168_param_id → label PL`** (~50–100 ID) + reuse `translations-extra-prep-values.php` dla wartości. Opcjonalnie mapowanie grup Che168 (`车身`/`发动机`/`电动机`…) → nasze `categories`.
3. **Douzupełnić `translations-models` o formy CN Che168** (`极氪*`, `金葵花国耀`, `腾势*` bez ` DM`).

**Bilans:** warstwa WARTOŚCI (spec) ~70% reuse — tania (ID + istniejące values). Warstwa MARKA+MODEL — **droższa niż sądziłem po Hongqi**: wymaga resolvera fuzzy lub reconu pełnego katalogu Che168, bo `brand-mapping` jest dongchedi-specific co do formatu stringa. To główny koszt Phase 3, nie spec.

---

## 3. Kolejne modele do rozłożenia (TODO)

Metoda: `tmp/che168-denza-probe-2026-06-05.php` (zmienić mark/model) → `getOffer` → porównać z naszym listingiem.

- [x] **Denza N8L** (PHEV SUV, świeża premiera, model CN `腾势N8L`) — 2026-06-05
- [x] **Denza D9** (masowy MPV, model EN `Denza D9`, wymiary 100% zgodne cross-source) — 2026-06-05
- [x] **Hongqi** (test sub-brandów: główny trzon EN/pokryty, sub-brand 金葵花 CN/luki) — 2026-06-05
- [x] **Zeekr** (EV-only: `model` bez marki `001`, brand-mapping 0%) + **cross-check 3 warstw** (surowe Che ↔ surowe dongchedi ↔ baza) — 2026-06-05
- [ ] _(wzorzec potwierdzony + zweryfikowany krzyżowo → przejście do specu)_

**Wzorzec potwierdzony (4 marki + weryfikacja krzyżowa):** (1) wartości spec CN wspólne → reuse słowników; (2) wymiary cross-source zgodne, rozstaw osi najpewniejszy (długość/wys. ±20mm między wariantami); (3) format `model` Che168 niespójny per marka, ale **nasze `serie_eu` ma format Che168 → resolver = indeks odwrotny brand-mappingu + ~3 reguły normalizacji** (nie trzeba nowego mappingu); (4) Che168 = używane/starsze (blocker biznesowy z reconu trzyma się).

Rozkład Denza w próbce Che168 (160 ofert, 8 stron, filtr `mark=Denza` DZIAŁA):
`Denza D9` ×106, `腾势N9` ×20, `Denza Z9` ×11, `Denza X` ×7, `Denza Z9GT` ×6, `腾势N8L` ×6, `Denza N7` ×4.
