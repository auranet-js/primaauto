# T-121 — płatność PayU za depozyt: wdrożenie (2026-08-10)

> Wejście: `docs/sesje/2026-08-06-T-121-PAKIET-WYKONAWCZY.md` (kroki K1–K9)
> Stan: **K1–K8 gotowe i przetestowane na sandboxie. K9 (produkcja) czeka na zgodę Janka.**
> Kod jest na produkcji, ale **za flagą `asiaauto_payu_enabled` = OFF** — dla klientów nic się nie zmieniło.
> Testy: **184 asercje, 0 błędów.**

---

## 1. Co powstało

| Plik | Rola |
|---|---|
| `includes/class-asiaauto-payu.php` | OAuth (transient), `createOrder()`, weryfikacja podpisu notyfikacji |
| `includes/class-asiaauto-payu-store.php` | Magazyn prób płatności: JSON historii + meta stanu per próba |
| `includes/class-asiaauto-payu-api.php` | REST: `POST /order/{id}/pay`, `GET /order/{id}/payment-status`, `POST /payu/notify` |
| `includes/class-asiaauto-payu-mail.php` | Maile po zaksięgowaniu (klient + Ruslan) i alarm o podwójnej wpłacie |
| `includes/class-asiaauto-payu-wizard.php` | Blok płatności w kreatorze (BLIK + bramka + przelew) |
| `includes/class-asiaauto-payu-admin.php` | Sekcja „Płatności PayU" w karcie zamówienia (read-only) |
| `assets/js/asiaauto-payu.js`, `assets/css/asiaauto-payu.css` | Front bloku płatności |

**Zmiany w istniejących plikach — pięć miejsc, wszystkie addytywne:**

| Plik | Zmiana | `.bak` |
|---|---|---|
| `asiaauto-sync.php` | 6 × `require_once` + 3 × `new` | `.bak-2026-08-10` |
| `class-asiaauto-order.php` | stała `META_DEPOSIT_PAID_SOURCE`, opcjonalny `$source` w `markDepositPaid()`, `do_action('asiaauto_deposit_paid')` | `.bak-2026-08-10` |
| `class-asiaauto-order-wizard.php` | 2 × `AsiaAuto_PayU_Wizard::renderBlock()` + skrót jednego zdania opisu | `.bak-2026-08-10` |
| `class-asiaauto-order-admin.php` | 1 × `AsiaAuto_PayU_Admin::render()` | `.bak-2026-08-10` |

Punkt rollbacku: `~/backups/primaauto/2026-08-10/asiaauto-sync-0.34.22-przed-T121.tar.gz`.

---

## 2. Odchylenia od pakietu — świadome, z powodem

1. **Guard dostępności auta pyta o zamówienia, nie o ogłoszenie.** `listingIsBlockedForOrders()` blokowałby płatność temu samemu klientowi, którego zamówienie zarezerwowało auto (rezerwacja na ogłoszeniu nie zapisuje, czyja jest). Zamiast tego szukamy *innego* zamówienia w statusie z `LISTING_RESERVATION_MAP`.

2. **Guard otwartej płatności: 90 s, nie 15 minut.** Piętnaście minut blokowałoby klienta, który porzucił bramkę po dwóch. Starsze wiszące próby zamykamy jako `expired`.

3. **`markCompleted()` księguje też z `expired`** — konsekwencja punktu 2 i rzecz krytyczna dla pieniędzy. Sekwencja: klient płaci → wraca → otwiera drugą próbę (pierwsza `expired`) → *dopiero teraz* przychodzi notyfikacja o pierwszej. Gdyby `expired` był końcowy, realna wpłata nie zaksięgowałaby się, a bez cronów reconkiliacji nic by tego nie wyłapało. `failed` i `canceled` zostają końcowe.

4. **Webhook rejestruje się także przy fladze OFF.** Gdyby znikał razem z flagą, notyfikacja o płatności już otwartej trafiłaby w 404. Trasa bez poprawnego podpisu i tak nic nie robi.

5. **Doszedł `GET /order/{id}/payment-status`** (nie było w pakiecie). Powód zmierzony na sandboxie: bank odrzuca kod BLIK **notyfikacją**, już po przyjęciu żądania przez bramkę. Bez tego endpointu ekran „potwierdź w aplikacji" wisiałby pełne 2 minuty również wtedy, gdy odmowa przyszła po dziesięciu sekundach.

6. **Idempotencja przez atomowy `UPDATE ... WHERE meta_value = 'pending'`**, nie przez lock aplikacyjny (pakiet dopuszczał transient). Dowód przy realnej współbieżności: 6 równoległych procesów księgujących tę samą płatność → **1 × WON, 5 × lost**.

---

## 3. Rzeczy zmierzone, nie założone

| Fakt | Skutek |
|---|---|
| `~/secrets/payu` **niedostępny spod HTTP** (`is_readable` = false), kopia w `private-payu` = true | K1; ta sama pułapka co 1611 cichych porażek Indexing API |
| `PAYU_ENV` w produkcji ma wartość **`secure`**, nie `prod` | naiwne `=== 'prod'` wysłałoby produkcyjne płatności na sandbox |
| Sandbox ma tylko `PAYU_SECOND_KEY_MD5`, produkcja oba klucze | loader normalizuje `PAYU_SIGNATURE_KEY ?: PAYU_SECOND_KEY_MD5` |
| `redirectUri` wskazuje **`merch-prod.snd.payu.com`**, nie `secure.snd.payu.com` | whitelista redirectu po domenie `payu.com`, nie po liście hostów |
| POS sandbox **przyjmuje `BLIK_AUTHORIZATION_CODE`** | K7b działa; na produkcyjnym POS-ie do potwierdzenia (bramka Janka) |
| Sandbox przyjmuje też „zły" kod BLIK | odmowa przychodzi notyfikacją → stąd `payment-status` |

---

## 4. Testy (`tmp/`, wielokrotnego użytku)

| Plik | Zakres | Wynik |
|---|---|---|
| `test-payu-k2-2026-08-10.php` | OAuth, podpisy, warianty nazw algorytmu | 18/18 |
| `test-payu-k3-2026-08-10.php` | magazyn prób, idempotencja, parsowanie extOrderId | 30/30 |
| `test-payu-k3-race-worker.php` | wyścig 6 procesów | 1 WON / 5 lost |
| `test-payu-k4-2026-08-10.php` | REST `/pay`, guardy, dostęp, spójność kwoty | 23/23 |
| `test-payu-k5-2026-08-10.php` | webhook: podpis, rozjazd kwoty, powtórki, podwójna wpłata | 26/26 |
| `test-payu-k6-2026-08-10.php` | hook + maile (przechwycone, nic nie wychodzi) | 26/26 |
| `test-payu-k7-2026-08-10.php` | blok kreatora, escapowanie, flaga | 26/26 |
| `test-payu-k7b-2026-08-10.php` | BLIK na sandboxie + `payment-status` | 19/19 |
| `test-payu-k8-2026-08-10.php` | sekcja w panelu | 16/16 |

Żaden test nie wysyła maila i nie zostawia śmieci — sprawdzane asercją na końcu każdego.

Podgląd UI z realnego kodu (nie z makiety):
https://auratest.pl/fe4f58fec53ctmp/primaauto-t121-blok-platnosci-wdrozenie-2026-08-10.html

---

## 5. Co zostało — K9, wymaga Janka

1. **Sprawdzić BLIK na produkcyjnym POS-ie** w panelu akceptanta. Jeśli `BLIK_AUTHORIZATION_CODE` nie jest włączony — to zgłoszenie do PayU, nie poprawka w kodzie.
2. Skopiować `prod-primaauto.env` → `~/domains/primaauto.com.pl/private-payu/payu.env`.
3. Włączyć flagę `asiaauto_payu_enabled`.
4. Jedna realna transakcja na małą kwotę + zwrot.
5. Bump `ASIAAUTO_VERSION` → `0.35.0`, wpis w `docs/VERSIONS.md`, aktualizacja `QUEUE.md`.

**Higiena T-221 (~1,5 h, nie blokuje):** PayU jako odbiorca danych w polityce prywatności (post 198526 — dziś 0 wystąpień, wymóg RODO od chwili pobierania płatności), zdjęcie `noindex` z `/regulamin-uslugi/` i `/depozyt/`, sprzeczność starego `/regulamin/` (post 153866, §4 ust. 3). Blok płatności linkuje już `/regulamin-uslugi/` i politykę prywatności — czyli część podlinkowania z T-221 jest zrobiona.

## 6. Poza zakresem — bez zmian wobec pakietu

Crony (przypomnienia, reconkiliacja, alerty), BLIK one-click, widget `payu-widget.js`, refund przez API, guard „nie rezerwuj auta bez depozytu".
