# Che168 Recon Report — Phase 0

**Data:** 2026-06-01 (probe 15:38-15:42 UTC)
**Skrypty:** `tmp/che168-recon-2026-06-01.php` + `tmp/che168-recon-v2-2026-06-01.php`
**Próbki surowe:** `tmp/che168-recon-samples-offers.json`, `tmp/che168-recon-samples-changes.json`, `tmp/che168-recon-samples-offer-full.json`, `tmp/che168-recon-samples-offers-v2.json`
**Output runów:** `tmp/che168-recon-output-2026-06-01.txt`, `tmp/che168-recon-v2-output.txt`
**Próbka analityczna:** 80 unikalnych inner_id, 50 pełnych `getOffer` calls

---

## TL;DR — flagowy wniosek biznesowy

**Che168 to fundamentalnie inny rynek niż dongchedi i NIE jest sensownym fallbackiem dla obecnej strategii Ruslana (nowe chińskie auta z południa Chin).** Połączony filtr dongchedi-style (year≥2024, km≤50000, price≥70000, marks z listy 66, south Chinese cities) daje **<1% pokrycia próbki**. Żeby Che168 miało sens, klient musiałby zmienić segment: premium używane (Mercedes/BMW/Porsche/Lexus) z całych Chin, mediana rocznika 2017, mediana przebiegu 84k km.

**Decyzja jaką recon wymusza:** zanim ruszymy z impl Source Manager + integracji Che168, Ruslan musi zdecydować o segmencie. Bez tej decyzji wdrożenie techniczne jest bezsensowne.

Technicznie wdrożenie jest dobrze osadzone — Che168 ma `getChanges` analogiczny do dongchedi, VIN-y i first_registration jako bonus, permanentne image URL-e (prostsze handling).

---

## Q1: Schemat pól `getOffer` Che168 vs dongchedi

`getOffer($source, $inner_id)` zwraca **34 pola** dla Che168 (dongchedi ~15):

| Pole | Coverage | Notatka |
|---|---|---|
| `inner_id` | 100% | numeryczny string, zakres ~43.5M-56M |
| `id` | 100% | inny od inner_id, prawdopodobnie auto-api wewnętrzny |
| `url` | 100% | link do oferty na che168.com |
| `mark` | 100% | nazwa marki (Mercedes-Benz, Porsche, Geely…) — angielska/europejska forma |
| `model` | 98% | nazwa modelu |
| `complectation` | wysoka | wersja silnika/wyposażenia (chińskie) |
| `year` | 100% | rok modelowy (int) |
| `color` | 100% | |
| `price` | 100% | CNY |
| `km_age` | 100% | przebieg (int km) |
| `engine_type` | 94% | typ napędu |
| `transmission_type` | 100% | |
| `body_type` | wysoka | |
| `drive_type` | wysoka | RWD/FWD/AWD itp. |
| `displacement` | wysoka | pojemność |
| **`vin`** | **100%** | **17-znakowy VIN, walidowalny** |
| **`first_registration`** | **100%** | **data pierwszej rejestracji — dongchedi tego nie ma!** |
| `address` | 100% | `"<miasto>, <dzielnica>"` (chińskie znaki) — zamiast osobnego `city` |
| `seller_type` | 100% | |
| `is_dealer` | 100% | bool — czy salon czy osoba prywatna |
| `salon_id` | część | id salonu jeśli dealer |
| `section` | część | |
| `description` | wysoka | opis (chiński) |
| `offer_created` | 100% | timestamp powstania oferty |
| `created_at` | 100% | timestamp w naszym feedzie |
| `images` | wysoka | array URL-i CDN `2sc2.autoimg.cn` |
| `power`, `ice_power_ps/kw`, `hybrid_power_ps/kw`, `electric_power_ps/kw` | część | granularne moce silników |
| `extra` | część | |

**Wnioski mapowania:**
- Wszystkie kluczowe pola dongchedi (mark, model, year, price, km_age, complectation, description, images, transmission, color) **są w Che168**.
- Brak bezpośredniego `city` — jest `address` ze schemą `"miasto, dzielnica"`. Extraction prosty: `explode(', ', $address)[0]`.
- **Bonus VIN + first_registration** — game-changer dla umów (memory `reference_dongchedi_year_fields.md`: dongchedi nie ma roku rejestracji, kombinujemy z `registration_date`).

---

## Q2: `getChanges` Che168 schema

✅ **DZIAŁA i jest analogiczny do dongchedi.**

```json
meta: {"cur_change_id":0,"next_change_id":8350544,"limit":20}
result: [
  {"id":8350524,"inner_id":"58081183","change_type":"changed","created_at":"...","data":{"new_price":92899}},
  ...
]
```

- `change_type` ∈ `{added, changed, removed?}` — w pierwszym batchu zaobserwowane: 9 added, 11 changed
- `next_change_id` monotonic — drugi batch potwierdził progression (8350544 → 8350564)
- Aktualny tip feedu Che168: ~**8,350,000+** change_id (vs dongchedi 3.8M — Che168 ma 2× więcej historii eventów)

**Brak blockera architektonicznego.** Sync layer (`AsiaAuto_Sync::run($source)`) działa bez zmian, advisory lock per source niezależny.

**Anomalia do uwagi:** `getOffers` Che168 ZWRACA TEN SAM FORMAT co `getChanges` (event-feed: id/inner_id/change_type/data) zamiast spłaszczonych obiektów ofert jak dongchedi. To znaczy że dla bulk-discovery przez `getOffers` musimy też używać `getOffer($iid)` żeby uzyskać pełne dane (jak w manual import). Wpływ na `class-asiaauto-admin-manual-import.php` — fetch oferty po wskazaniu URL/iid działa, ale lista paginowana wymaga dwóch wywołań na ofertę.

---

## Q3: Miasta — overlap z south Chinese

`address` format: `"<miasto>, <dzielnica>"` (chińskie znaki). Próbka 50:

- **13/50 (26%)** zawiera jedno z naszych 31 południowych miast
- Top south matches: 广州 (Guangzhou) 3×, 东莞 (Dongguan) 3×, 泉州 (Quanzhou) 2×, 海口 (Haikou) 2×, 深圳 (Shenzhen) 2×, 南平 (Nanping) 1×
- **74% próbki** to inne regiony Chin: 北京 (Beijing), 兰州 (Lanzhou, NW), 扬州 (Yangzhou), 台州 (Taizhou), itd.

**Wniosek:** Che168 ma **ogólnonarodowy zasięg**, nie regionalny. Jeśli Ruslan utrzyma restrykcję south Chinese — większość katalogu Che168 odpada.

Decyzja Ruslana: zostawiamy wąski filtr 31 miast (i tracimy 74% Che168), czy rozszerzamy zasięg (nowa strategia logistyczna)?

---

## Q4: Marki — overlap z naszą listą 66

**Tylko 5/22 (23%) marek pokrywa się** z naszą dongchedi config:

| W naszej liście | Spoza listy (NOWE) |
|---|---|
| Nissan, Changan, Geely, Jetour, Volkswagen | Mercedes-Benz (14× w próbce), Porsche (4×), MINI (3×), Buick (3×), Toyota (3×), BMW (2×), Lexus (2×), GMC (2×), Audi (2×), Land Rover (2×), Tesla, Hummer, Venucia, Bentley, Honda, Roewe, Rising Auto |

**Dominacja zachodnich premium używanych** (Mercedes 28% próbki!). To NIE jest target Ruslana — klient kupuje nowe chińskie do importu, nie używane Mercedesy z chińskiego rynku wtórnego.

**Z naszej listy 66 chińskich marek (BYD, Voyah, Zeekr, NIO, XPeng, Avatr, Denza, Hongqi, AITO…) — praktycznie żadnej w próbce Che168.** To potwierdza że Che168 to **C2C marketplace używanych aut**, dominacja zachodnich marek (chiński konsument kupuje używanego Mercedesa, a NEV-y kupuje nowe od dealera = dongchedi).

---

## Q5: Rozkłady — fundamentalna różnica segmentu

| Pole | Statystyka próbka (n=50) |
|---|---|
| YEAR | spread 2003-2024, mediana **2017**. Year≥2024: **4/50 (8%)** |
| PRICE | 6,000 - 2,150,000 CNY, mediana **139,000 CNY** (~76,500 zł). Price≥70,000: ~75% próbki |
| KM_AGE | 7,000 - 290,000 km, mediana **84,000 km**. KM≤50,000: ~25% próbki |

**Połączony filtr dongchedi-style** (year≥2024 × km≤50,000 × price≥70,000 × south_cities × brand_overlap):

`0.08 × 0.25 × 0.75 × 0.26 × 0.23 ≈ 0.0009 = 0.09%`

Statystycznie z 50 ofert Che168 **<1 by przeszło** nasz filtr. Z 4068 listingów które mamy dziś w bazie z dongchedi, Che168 dorzuciłby ~1-5 ofert dziennie przy tym filtrze. Praktycznie zero wartości.

---

## Q6: Image pipeline

✅ **Prostsze niż dongchedi.**

- Host: `2sc2.autoimg.cn` (CDN Autohome)
- **`x-expires` = NO** — URL-e PERMANENT, brak deadline'u
- Format: `https://2sc2.autoimg.cn/escimg/auto/g31/M0B/14/B6/1024x768_c42_autohomecar__ChxoHWe8kyaAXbJVAALj-xXaTqk753.jpg.webp`
- Resolucja 1024×768, format `.webp` natywnie

**Konsekwencje techniczne:**
- Preflight W1 (`allUrlsExpired`) zwróci `false` dla wszystkich Che168 URL-i — żaden listing nie zostanie odrzucony z tego powodu
- Brak ghost-offers (problemu z 6-dniowymi linkami dongchedi)
- Brak konieczności pilnego cachowania — można odłożyć download do kolejnego cyklu bez utraty obrazków
- Naming SEO w `class-asiaauto-media.php` (`{mark}-{model}-{year}-{city}-{inner_id}-{n}.webp`) działa bez zmian

---

## Q7: `inner_id` range — collision check

| Source | min | max |
|---|---|---|
| Dongchedi (z naszej bazy) | 14,649,052 | 23,967,900 |
| Che168 (próbka) | 43,510,789 | 55,959,349 |

Zakresy się **nie nakładają**. Compound key `(source, inner_id)` w `findByInnerId()` **nie jest wymagany dziś**, ale wprowadzamy go defensywnie (Che168 ID-ki w przyszłości mogą rosnąć i kto wie czy dongchedi nie cofnie się do recyklingu starych ID-ków).

---

## Q8: Rate limits + retry

- 4 wywołania `getOffers` + 50 `getOffer` + 2 `getChanges` w ciągu ~120 sekund — **bez żadnego błędu**, bez throttle
- `usleep(150-200ms)` między wywołaniami wystarcza
- Istniejąca `AsiaAuto_API::retry()` (3 próby, exponential backoff) działa per-call → automatycznie kompatybilna z Che168

---

## Q9: Pola dedup — propozycja final

Coverage z 50-sample (wszystkie 100% chyba że oznaczone):

| Pole | Coverage | Wartość dla dedup |
|---|---|---|
| **`vin`** | **100%** | **Złoty standard, ale tylko dla Che168↔Che168 (dongchedi nie ma vin)** |
| `mark` + `model` | 100% / 98% | exact match, after brand mapping verification |
| `year` | 100% | exact |
| `km_age` | 100% | tolerancja `±2000` lub `±5%` |
| `price` | 100% | tolerancja `±5%` CNY (rzadko zmienia się gwałtownie) |
| `first_registration` | 100% (Che168 only) | exact gdy dostępny po obu stronach (nie u nas obecnie) |
| `address` (po extraction city) | 100% | exact city — pomocniczo |
| `engine_type` + `transmission_type` | 94-100% | exact pomocniczo |

**Strategia hybrydowa (rekomendacja):**

1. **Wewnątrz Che168:** dedup po `vin` exact. Jeśli VIN match → ten sam samochód. Szybkie, niezawodne.
2. **Cross-source (Che168 ↔ dongchedi):** heurystyka `mark + model + year + km_age (±5%) + price (±10%)`. VIN nie pomoże bo dongchedi go nie ma.
3. **Tolerancje:** zacząć konserwatywnie (km ±2000, price ±5%), audyt 30 listingów po Phase 3, dostroić.

**Compound primary key:** `_asiaauto_source` + `_asiaauto_inner_id` (już istnieje meta `_asiaauto_source`, kierunkowo).

---

## Lista blockerów dla Source Manager spec

✅ **Brak blockerów technicznych.** Architektura Source Manager + Dedup Service działa dla Che168 jak dla dongchedi. Refaktor istniejących klas wystarczy.

⚠️ **Blocker biznesowy:** segment Che168 vs strategia Ruslana. Bez decyzji o rozszerzeniu segmentu (premium używane, ogólnonarodowo) wdrożenie nie ma sensu — pokrycie <1%.

⚠️ **Anomalia API do uwagi:** `getOffers` Che168 zwraca event-feed format zamiast obiektów ofert. Manual import UI musi inaczej traktować bulk discovery dla Che168 (dwa wywołania: paginacja iid → getOffer pełne). Drobny refactor `class-asiaauto-admin-manual-import.php`.

---

## Rekomendacje konkretne (do specu)

### Filter Che168 — propozycja domyślna gdy idziemy w Che168:

**Wariant A** — „klon dongchedi" (pokrycie <1%, raczej testowy):
```json
{ "marks": [...nasze 66], "year_from": 2024, "km_to": 50000, "price_from": 70000, "city_filter_enabled": true, "city_filter_cities": [...south 31] }
```

**Wariant B** — „premium używane Chiny" (nowy segment, sensowny gdy Ruslan zmienia strategię):
```json
{ "marks": ["Mercedes-Benz", "BMW", "Audi", "Porsche", "Lexus", "Land Rover", "Tesla", "MINI", "Toyota", ...], "year_from": 2020, "km_to": 100000, "price_from": 100000, "city_filter_enabled": false }
```

**Wariant C** — „lekki ekspand dongchedi" (kompromis):
```json
{ "marks": [...nasze 66 + nowe chińskie z Che168 (Roewe, Venucia, Rising Auto)], "year_from": 2024, "km_to": 50000, "price_from": 70000, "city_filter_enabled": false }
```

**REKOMENDACJA:** Wariant B (zmiana strategii) lub **nie wdrażać Che168** (najprostsze, czekamy na dongchedi recovery).

### Dedup config:

```json
{
  "fields_within_source": ["vin"],
  "fields_cross_source": ["mark", "model", "year", "km_age", "price"],
  "tolerances": { "km_age_pct": 5, "price_pct": 10 },
  "preferred_source": "dongchedi"
}
```

### Pola CPT do dodania:

- `_asiaauto_vin` (Che168 ma — wartość dla obsługi posprzedażowej)
- `_asiaauto_first_registration` (Che168 ma — eliminuje workaround dongchedi z `registration_date`)
- `_asiaauto_is_dealer` (Che168 — wpływa na narrację umowy / wycenę)
