# 2026-05-15 — Umowa: „rok pierwszej rejestracji" zamiast „rok produkcji"

## Kontekst

Klient zgłosił rozbieżność na umowie wygenerowanej z listingu [iCAR Super V23 2025](https://primaauto.com.pl/oferta/icar-super-v23-2025-329788/) (post 329788, inner_id 23653477, VIN `LURMCWEY6RA017761`):
- Auto wyprodukowane w **2024** (wg dokumentów oględziny + tabliczka znamionowa + VIN 10. znak `R` = ISO 3779 rok modelowy 2024)
- Pierwsza rejestracja w Chinach: **2025-01-01**
- Umowa pokazywała: „§1 b) rok produkcji: **2025**" oraz tabela specyfikacji „Rok produkcji: **2025**"

## Diagnoza

API Dongchedi (`AsiaAuto_API::getOffer("dongchedi", "23653477")`) zwraca tylko trzy pola czasowe:
- `year` → `2025` (rok modelowy / wprowadzenia auta do salonu)
- `reg_date` → `2025-01-01` (data pierwszej rejestracji)
- `extra_prep.market_time` → `2025.01` (data wprowadzenia modelu na rynek chiński)

**API nie zwraca osobnego pola „rok produkcji".** Dla aut typu „dealer-rejestrowane jako prawie nowe" (powszechny model w Chinach: dealer rejestruje auto na siebie żeby zerować VAT przy zakupie hurtowym, sprzedaje klientowi po 1-12 miesiącach), pole `year` API zazwyczaj pokazuje rok rejestracji, nie kalendarzowy rok produkcji.

Importer (`class-asiaauto-importer.php:524`) zapisuje `year` z API jako term taksonomii `ca-year`. Umowa (`class-asiaauto-contract.php:327`) brała ten term jako wartość pola „rok produkcji". Stąd rozjazd.

## Opcje rozważane

| # | Opcja | Pokrycie | Złożoność | Wniosek |
|---|---|---|---|---|
| A | VIN decoder (ISO 3779 10. znak) | 99%+ listings z VIN-em, ale 30/34 listings bez `registration_date` też nie ma VIN-a → realne pokrycie problemu 4/34 (12%) | Średnia (algorytm znany, edge cases dla CN VIN-ów) | Odrzucone — pokrycie braków słabe |
| B | Manual override per zamówienie (`_order_production_year` meta) | 100% gdy admin uzupełni | Niska | Odrzucone — dodatkowa ręczna robota |
| C | Etykieta „rok pierwszej rejestracji" + `registration_date` | 98.4% (2067/2101 w 14d) automatycznie | Najniższa | **WYBRANE** |
| D | Hybrid: VIN → admin → fallback → walidacja blokująca | 100% z gwarancją | Najwyższa | Odłożone — overkill na 1.6% braków |

## Decyzja

**Opcja C.** W umowie zmieniamy etykietę „rok produkcji" → „rok pierwszej rejestracji". Wartość podstawiamy z meta `registration_date` (format `DD/MM/YYYY` → wyciągamy ostatnie 4 cyfry regexem `#/(\d{4})$#`). Fallback: `ca-year` (rok modelowy) gdy brak rejestracji w API.

**Uzasadnienie:**
- Zgodne z faktycznymi danymi z API (mamy je wprost, bez interpretacji)
- 98.4% listings pokryte automatycznie (z `registration_date`)
- Brak ryzyka wprowadzania klienta w błąd: „pierwsza rejestracja" to fakt prawny weryfikowalny w dokumentach pojazdu, w przeciwieństwie do „roku produkcji" który dla aut z Chin nie ma jednoznacznego źródła w API
- Zero dodatkowej pracy operacyjnej (admin nie musi nic wpisywać)
- Pozostałe 1.6% (świeże dealer-stock bez rejestracji) — fallback na rok modelowy nie jest „kłamstwem" prawnym, bo te auta nie mają jeszcze daty pierwszej rejestracji do podania

## Konsekwencje

- Wszystkie nowo generowane umowy od v0.32.45 pokażą „rok pierwszej rejestracji" zamiast „rok produkcji" w §1 b) i tabeli specyfikacji
- Wcześniej wygenerowane PDF-y zostają jak są (nie regenerujemy automatycznie); jak klient zgłosi → regeneracja przyciskiem w admin
- Jeśli w przyszłości API zacznie zwracać prawdziwy `production_year` lub user zdecyduje że jednak chce rok kalendarzowy produkcji — wracamy do opcji D z VIN decoderem

## Implementacja

Zobacz `docs/VERSIONS.md` → wpis 0.32.45.

Plik: `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-contract.php`
- Linia 327: zmieniony getter `'year'`
- Nowa metoda `extractRegistrationYear()` po `getVehicleData()`
- Linia 604: etykieta sekcji §1 b)
- Linia 975: etykieta w tabeli specyfikacji

Backup: `class-asiaauto-contract.php.bak-2026-05-15-reg-year`.
