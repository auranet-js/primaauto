# Mapy tłumaczeń — data/

> Aktualizacja: 2026-04-15.

## Pliki i rozmiary

| Plik | Wpisów | Format | Opis |
|------|--------|--------|------|
| `translations-colors.php` | 18 | EN→PL | Kolory nadwozia z API + fallback |
| `translations-body-types.php` | 17 | EN→PL | Typy nadwozia (uwaga: SUV + suv — case-sensitivity API) |
| `translations-transmissions.php` | 18 | EN+ZH→PL | 12 angielskich + 6 chińskich wariantów skrzyń |
| `translations-engine-types.php` | 14 | EN→PL | Typy paliwa/napędu, warianty upper/lower |
| `translations-drive-types.php` | 4 | EN→PL | FWD, RWD, AWD, - |
| `translations-seller-types.php` | 4 | EN→PL | dealer/private w wariantach case |
| `translations-cities.php` | 104 | ZH→PL | 99 chińskich + 5 EN fallback, 3 tiery, polskie egzonimy |
| `translations-models.php` | 29 | ZH→EN | Nazwy modeli bez prefiksu marki |
| `translations-complectations.php` | 183 | ZH→EN/PL | Segmentowy — każdy fragment zamieniany niezależnie |
| `translations-extra-prep.php` | ~923 | wielosekcyjny | 1495 linii: categories, labels, units, skip |
| `translations-extra-prep-values.php` | 296 | ZH→PL | Wartości spec: materiały, baterie, zawieszenia, tryby... |

## translations-extra-prep.php — struktura

5 sekcji w jednym pliku:

1. **`categories`** — 11 kategorii wyświetlania z ikonami dashicons i listami kluczy:
   basic, dimensions, engine, fuel, gearbox, suspension, wheels, ev, safety, exterior, interior

2. **`labels`** — klucz API → polski label (`'wheelbase' => 'Rozstaw osi'`)

3. **`values`** — delegowane do `translations-extra-prep-values.php`

4. **`units`** — 39 wpisów: klucz → jednostka (mm, kg, L, kW, KM, Nm, obr/min, km/h, s, kWh, km)

5. **`skip`** — 6 kluczy do pominięcia (redundantne)

## translations-extra-prep-values.php — kategorie wartości

296 tłumaczeń ZH→PL w grupach:
- Boolean: `标配`=standard, `选配`=null (skip)
- Baterie: CATL, BYD, Gotion, EVE Power, Farasis, Welion... (15+ marek)
- Autonomia: L2, L2+, L3
- Emisje: `国VI => China VI (≈Euro 6)`
- Zawieszenia, hamulce, materiały, napędy, silniki EV, skrzynie, paliwa, nadwozia, fotele, klimatyzacja, oświetlenie, szyby, parkowanie, bezpieczeństwo...

**Pattern-based** (w kodzie PHP, nie w mapie): 马力→KM, 万→×10000, 年或万公里→warranty, 色→colors, czasy ładowania.

## Workflow diagnostyczny tłumaczeń

```bash
wp eval-file diag/check-translations.php        # brakujące labele/wartości
wp eval-file diag/check-chinese-models.php       # chińskie nazwy modeli
APPLY=1 wp eval-file diag/fix-chinese-v23.php    # fix modeli + tytułów
```

## Klasa AsiaAuto_Translator — metody

| Metoda | Opis |
|--------|------|
| `translate($field, $value)` | Tłumaczenie z mapy statycznej |
| `translateModel($model)` | ZH→EN, ASCII passthrough |
| `translateCity($city)` | Chińskie miasto → PL |
| `translateExtraPrep($ep)` | 200+ kluczy spec → 11 kategorii |
| `translateComplectation($raw)` | Segmentowy ZH→EN/PL |
| `translateDescription($text)` | Gemini Flash 2.5 (primary), DeepL (fallback) |
