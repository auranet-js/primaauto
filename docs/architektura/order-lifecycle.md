# Order lifecycle — moduł zamówień

> Aktualizacja: 2026-04-15. Wersja: v0.22.0 (statusy), v0.30.4 (contract rework).

## CPT i typy zamówień

- **CPT:** `asiaauto_order` (nie public, custom admin UI)
- **Customer** (`_order_type = 'customer'`) — listing zablokowany, nikt inny nie może zamówić
- **Stock** (`_order_type = 'stock'`) — listing dostępny, klienci mogą zamawiać (auto stock→customer conversion)

## 11 statusów

```
CUSTOMER PATH:
nowe → weryfikacja → potwierdzone → dane_klienta → umowa_gotowa → podpisane → zarezerwowane
                  └→ odrzucone

FULFILLMENT (oba typy):
zarezerwowane → zakupione → w_drodze → na_placu → zakonczone
                  (flexible — admin może skakać)

STOCK PATH (direct entry):
createInternal() → zakupione | w_drodze | na_placu → zakonczone

TERMINAL: zakonczone (czyści rezerwację), anulowane (czyści rezerwację), odrzucone
```

## Flexible transitions

| Z | Dozwolone następne |
|---|--------------------|
| nowe | weryfikacja |
| weryfikacja | potwierdzone, odrzucone |
| potwierdzone | dane_klienta |
| dane_klienta | umowa_gotowa |
| umowa_gotowa | podpisane |
| podpisane | zarezerwowane, umowa_gotowa (admin revert) |
| zarezerwowane | zakupione, w_drodze, na_placu |
| zakupione | w_drodze, na_placu |
| w_drodze | na_placu |
| na_placu | zakonczone |

`anulowane` dostępne z każdego nie-terminalnego statusu.

## Wizard frontend — 5 kroków

| Step | Statusy | Akcja klienta |
|------|---------|---------------|
| 1 | nowe | Dane kontaktowe + RODO + regulamin |
| 2 | weryfikacja | Oczekiwanie na admina |
| 3 | potwierdzone, dane_klienta | 12 pól billing |
| 4 | umowa_gotowa, podpisane | PDF → upload podpisanej → przelew → upload potwierdzenia |
| 5 | zarezerwowane → zakonczone | Tracking realizacji |
| -1 | odrzucone, anulowane | Terminal |

## Rezerwacja listingu

| Meta listing | Wartości | Cel |
|-------------|----------|-----|
| `_asiaauto_reservation_status` | `reserved`, `in_transit`, `on_lot` | Status fizyczny |
| `_asiaauto_reservation_type` | `customer`, `stock` | Typ → button vs badge |
| `_asiaauto_reservation_order_id` | int | Które zamówienie |

**Mapowanie status zamówienia → rezerwacja:**

| Status zamówienia | reservation_status | Strona |
|---|---|---|
| potwierdzone → zarezerwowane | `reserved` | Główny inventory (badge) |
| zakupione, w_drodze | `in_transit` | /w-drodze/ |
| na_placu | `on_lot` | /w-rzeszowie/ |
| zakonczone, anulowane | **CZYŚCI** | Znika |

## Kluczowe meta zamówienia

| Meta | Typ | Opis |
|------|-----|------|
| `_order_listing_id` | int | Listing powiązany |
| `_order_customer_id` | int | WP user ID |
| `_order_status` | string | Aktualny status |
| `_order_type` | string | customer / stock |
| `_order_price_final` | int | Cena końcowa PLN |
| `_order_price_breakdown` | JSON | Kopia breakdownu z momentu zamówienia |
| `_order_deposit_amount` | int | Depozyt zabezpieczający PLN |
| `_order_deposit_paid` | 0/1 | Wpłacony? |
| `_order_contract_commission_net` | int | Wynagrodzenie netto PLN (§3 umowy) |
| `_order_vin` | string | VIN (edytowalny, fallback do listingu) |
| `_order_contract_number` | string | AA/2026/0001 |
| `_order_contract_attachment_id` | int | PDF umowy |
| `_order_signed_attachment_id` | JSON | Podpisane umowy (array IDs) |
| `_order_status_log` | JSON | Historia zmian statusów |

## Depozyt — formuła

```php
$calculated = ceil($price_pln * $percent / 100);
$deposit = max($calculated, $deposit_min);
$deposit = ceil($deposit / $rounding) * $rounding;
```

## Wynagrodzenie kontraktowe — formuła

```php
$commission = max($price_pln * $percent / 100, $min);
// Bez zaokrąglenia (w odróżnieniu od depozytu)
```

## REST API (namespace `asiaauto/v1`)

| Metoda | Endpoint | Auth | Opis |
|--------|----------|------|------|
| GET | `/listing/{id}/reserve` | public | Dostępność + cena + depozyt |
| POST | `/order/start` | public* | Rejestracja + zamówienie |
| GET | `/order/{id}` | owner/admin | Stan wizarda |
| POST | `/order/{id}/billing` | owner/admin | Dane billing |
| POST | `/order/{id}/upload` | owner/admin | Upload umowy/dowodu |
| POST | `/order/{id}/delete-upload` | owner/admin | Usuń upload |
| POST | `/order/{id}/confirm-signed` | owner/admin | Potwierdź podpis |
| GET | `/order/{id}/transfer` | owner/admin | Dane przelewu |
| POST | `/auth/login` | public* | Login klienta |

*Honeypot + rate limit 10/h.

## Email — 15+1 szablonów

Edytowalne w admin: Narzędzia → AsiaAuto Zamówienia → Treści maili.
wp_options: `asiaauto_order_email_templates` (JSON).

Placeholdery: `{customer_name}`, `{order_id}`, `{listing_title}`, `{price_final}`, `{deposit_amount}`, `{status_label}`, `{magic_link}`, `{company_phone}`, `{customer_phone}`.

## Contract PDF

- mPDF v8.3, auto-generated na `umowa_gotowa`
- Deferred w REST (wp_schedule_single_event 5s), immediate w CLI
- Treść: "Umowa Zlecenia Sprowadzenia Samochodu z Chin" §1-§9
- Model agencyjny Prima-Auto (depozyt zabezpieczający + wynagrodzenie §3)
- Admin: regeneracja + revert do umowa_gotowa
