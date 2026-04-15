# Pipeline cenowy — AsiaAuto_Price

> Aktualizacja: 2026-04-15. Wersja kodu: v0.30.5 (na serwerze) + patche v0.30.6.

## Dwa tryby wejścia

| Tryb | Meta key | Metoda | Źródło |
|------|----------|--------|--------|
| CNY (auto-api) | `_asiaauto_original_price` | `calculateFromCny()` | Import Dongchedi |
| CIF USD (override) | `_asiaauto_cif_price_usd` | `calculateFromCifUsd()` | Ręczne ogłoszenia, metabox sprzedawcy |

**Priorytet w `applyToListing()`:** CIF USD > CNY > CIF PLN legacy.

Legacy `_asiaauto_cif_price_pln` — auto-migracja na `_cif_price_usd` przy pierwszym `applyToListing()`.

## 9 kroków pipeline'u (v0.28.0+)

```
1. cena_cny × kurs_cny_usd                              = cena_bazowa USD
2. + chiny_rejestracja_transport_usd (stała 5000 USD)    = koszty w Chinach
   ─── CIF USD = step_1 + step_2 ───
   ─── CIF PLN = CIF USD × kurs_usd_pln ───
3. + agencja_celna_wyladunek (stała 2600 PLN)
4. + cło = (CIF PLN + step_3) × clo_procent (10%)
5. + transport_z_portu (stała 3400 PLN)
6. + akcyza = (po step_5) × stawka_paliwa
7. + homologacja_detailing (stała 1500 PLN)
8. + prowizja = max((1..7) × prowizja_procent, prowizja_min)
9. + VAT = (1..8) × vat_procent (23%)
   → zaokrąglenie w górę do wielokrotności (1000 PLN)
```

**Tryb CIF USD override:** step_1 = cała kwota override, step_2 = 0. Reszta identyczna.

## Akcyza — stawki per typ paliwa

| Fuel slug | Typ | Stawka |
|-----------|-----|--------|
| `petrol` | Benzyna | 3.1% |
| `diesel` | Diesel | 3.1% |
| `hybrid` | HEV | 1.55% |
| `mhev` | MHEV | 1.55% |
| `phev` | PHEV | 0% |
| `electric` | EV | 0% |
| `erev` | EREV | 0% |
| `cng` | CNG | 3.1% |
| `bi-fuel` | Bi-fuel | 3.1% |

## Config (wp_options: `asiaauto_price_config`)

| Klucz | Typ | Default | Opis |
|-------|-----|---------|------|
| `kurs_cny_usd` | float | 0.15 | Kurs CNY → USD (v0.30.5) |
| `kurs_usd_pln` | float | 3.70 | Kurs USD → PLN (v0.30.5) |
| `chiny_rejestracja_transport_usd` | int | 5000 | Koszty w Chinach USD (v0.30.5) |
| `agencja_celna_wyladunek` | int | 2600 | Agencja celna PLN |
| `clo_procent` | float | 10.0 | % cła |
| `transport_z_portu` | int | 3400 | Transport z portu PLN |
| `akcyza_stawki` | array | (per fuel) | Fuel slug → % |
| `homologacja_detailing` | int | 1500 | Homologacja PLN |
| `prowizja_procent` | float | 10.0 | Marża wewnętrzna % |
| `prowizja_min` | int | 11000 | Marża min PLN |
| `vat_procent` | float | 23.0 | VAT % |
| `zaokraglenie` | int | 1000 | Zaokrąglenie w górę |

**Legacy migration:** stary `kurs_cny_pln` → przeliczany na `kurs_cny_usd` przez `getConfig()`.

## Breakdown JSON v2

```json
{
  "version": 2,
  "input_mode": "cny_auto_api | cif_usd_manual",
  "fx": { "kurs_cny_usd", "kurs_usd_pln", "snapshot_at" },
  "source_price": { "currency", "amount", "source" },
  "step_1_cena_bazowa": { "currency": "USD", "amount_usd", "amount_pln" },
  "step_2_chiny_transport": { "currency": "USD", "amount_usd", "amount_pln" },
  "cif": { "amount_usd", "amount_pln" },
  "step_3_agencja": { "amount_pln" },
  "step_4_clo": { "percent", "base_pln", "amount_pln" },
  "step_5_transport_portu": { "amount_pln" },
  "step_6_akcyza": { "percent", "fuel_type", "base_pln", "amount_pln" },
  "step_7_homologacja": { "amount_pln" },
  "step_8_prowizja_wewnetrzna": { "percent", "min_pln", "base_pln", "calculated_pln", "amount_pln" },
  "step_9_vat": { "percent", "base_pln", "amount_pln" },
  "total": { "subtotal_pln", "rounding_pln", "cena_koncowa_pln" },
  "_legacy_flat": { ... klucze v1 dla backward compat }
}
```

## Stałe klasy

```php
BREAKDOWN_VERSION = 2
OPTION_KEY        = 'asiaauto_price_config'
META_BREAKDOWN    = '_asiaauto_price_breakdown'
META_ORIGINAL     = '_asiaauto_original_price'      // CNY z API
META_CIF_USD      = '_asiaauto_cif_price_usd'       // Override ręczny
META_CIF_PLN_LEGACY = '_asiaauto_cif_price_pln'     // Wygaszany
```

## Kluczowe metody

| Metoda | Opis |
|--------|------|
| `calculateFromCny($cny, $config, $fuel)` | MODE CNY — pełny pipeline |
| `calculateFromCifUsd($usd, $config, $fuel)` | MODE CIF USD — od step_3 |
| `applyToListing($post_id, $cny, $fuel)` | Auto-detect trybu, zapis do meta |
| `bulkRecalculate($args, $dry_run)` | Masowe przeliczenie (3 zbiory: CIF USD, CNY, legacy) |
| `getConfig()` | Z lazy migration starych kluczy |
| `getBreakdown($post_id)` | Z lazy upgrade v1→v2 |

## Prowizja wewnętrzna vs wynagrodzenie kontraktowe

To są **dwa niezależne byty**:

- **`step_8_prowizja_wewnetrzna`** — marża ukryta w cenie "pod klucz", nigdy widoczna dla klienta
- **`_order_contract_commission_net`** — wynagrodzenie §3 umowy, faktura VAT, widoczne w PDF

Zero mappingu między nimi. Pipeline cenowy nie dotyka umowy.
