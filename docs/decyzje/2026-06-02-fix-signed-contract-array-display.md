# 2026-06-02 — Fix: karta zamówienia w wp-admin pokazywała „klient nie przesłał umowy" mimo przesłanej, podpisanej umowy

**Wersja:** asiaauto-sync 0.32.67
**Obszar:** order lifecycle (strefa krucha #4) — render karty admina, odczyt meta podpisanej umowy
**Typ:** bugfix (wyłącznie wizualny, dane nienaruszone)

## Kontekst / zgłoszenie

Ruslan zgłosił, że realny klient (Miron Orłowski) na zamówieniu Denza N9 DM-i (`#351079`, post `351145`) podpisał umowę Profilem Zaufanym i przesłał ją przez panel klienta — ale w panelu admina widnieje „Podpisana umowa: Brak — klient jeszcze nie przesłał". Polecenie: zweryfikować jak faktycznie było, usunąć bug.

## Ustalenia (dane produkcyjne)

Umowa **została** przesłana i potwierdzona — bug był po stronie wyświetlania, nie danych:

- `_order_status` = `podpisane`
- `_order_signed_attachment_id` = `[351149]`
- załącznik `351149`: PDF `2026/06/AA-2026-0015-b368ea5a-podpisana.pdf`, `post_parent = 351145`
- `_order_signed_at` = `2026-06-02T14:44:22Z`
- log statusów: „Klient potwierdził przesłanie podpisanej umowy (1 plik(ów))", `user_id = 25` (klient), 2026-06-02 14:44

Sygnał diagnostyczny: ta sama karta pokazywała badge statusu **„podpisane"** i jednocześnie **„Brak"** w wierszu podpisanej umowy — wewnętrzna sprzeczność.

## Root cause

Podpisana umowa od v0.22.0 wspiera **wiele plików** (wielostronicowy skan / kilka załączników) i jest przechowywana jako **tablica JSON**:

- zapis: `AsiaAuto_Order_Api` — `update_post_meta($id, META_SIGNED_ID, wp_json_encode($ids))` (`order-api.php:577`, `:658`)
- odczyt poprawny (klient/REST): `getSignedAttachmentIds()` (`order-api.php:845`) — parsuje JSON
- odczyt poprawny (regeneracja umowy): `handleGenerateContract()` (`order-admin.php:369-382`) — parsuje JSON

Jedyny **zepsuty** odczyt: `AsiaAuto_Order::getOrderData()` (`order.php:1457`) czytał meta przez `(int) get_post_meta(...)`. W PHP `(int) "[351149]" === 0`, więc render karty (`order-admin.php`, wiersz „Podpisana umowa”) wpadał w gałąź `else` → „Brak — klient jeszcze nie przesłał”. `$data['signed_id']` z `getOrderData` nie był używany nigdzie indziej.

## Audyt zakresu (cały plugin + motyw)

Pełny sweep `META_SIGNED_ID` / `_order_signed_attachment_id` / `signed_id` w pluginie i motywie:

- **Zapisy:** JSON-array (add/delete, `order-api`) albo reset `0` — nigdy bare single-int.
- **Odczyty:** dwa poprawne (REST + regeneracja), jeden zepsuty (`getOrderData`). Potwierdzono: dokładnie jedno miejsce do naprawy + jeden konsument.
- **Bliźniak `payment_proof`:** zapisywany jako pojedynczy int (`order-api.php:584`, jeden plik z założenia). `(int)` cast w `order.php:1460` **poprawny** — bez analogicznego buga.
- **Motyw `asiaauto`:** zero odwołań do tej meta (panel klienta idzie przez REST).

## Decyzja / zmiana

1. `class-asiaauto-order.php`
   - Nowy publiczny helper `parseSignedAttachmentIds($raw): array` — obsługuje JSON-array (nowy format) i pojedynczy int (legacy). Mirror logiki z `order-api.php:845`, umieszczony w modelu jako współdzielone źródło.
   - `getOrderData()` zwraca `signed_ids` (tablica ID) oraz `signed_id` (pierwszy element lub `0`) dla wstecznej kompatybilności.
2. `class-asiaauto-order-admin.php`
   - Wiersz „Podpisana umowa" renderowany w pętli po `signed_ids` — pokazuje **wszystkie** przesłane pliki (link + MIME). „Brak — klient jeszcze nie przesłał" tylko gdy tablica realnie pusta.

## Weryfikacja

- `php -l` — czysty (`order.php`, `order-admin.php`, `asiaauto-sync.php`).
- Smoke (`wp eval AsiaAuto_Order::getOrderData`):
  - `#351145` (Denza/Miron) → `signed_id=351149`, `signed_ids=[351149]`
  - `#326921` (Exeed/Bogdan, drugi dotknięty) → `[337660]`
  - `#351144` (stock, bez podpisu) → `signed_id=0`, `signed_ids=[]`
- Helper: `"[351149]"`→`[351149]`, `"[123,456]"`→`[123,456]`, `"789"`→`[789]`, `"0"`→`[]`, `""`→`[]`.

## Konsekwencje

- Karta admina pokazuje realny stan podpisanej umowy dla wszystkich zamówień (cofnięte fałszywe „Brak"). Dotknięte też historycznie: Exeed `#265748`, Galaxy `#251809`, BYD Tang `#238824`.
- Brak migracji danych — format meta bez zmian, naprawiony tylko odczyt.
- `signed_id` zachowany w `getOrderData` jako kompatybilny alias (pierwszy plik), gdyby przyszły kod go używał.

## Uwaga poboczna (nie naprawiane tutaj)

`_order_signature_method` dla tego zamówienia jest puste → karta pokazuje „Metoda podpisu: —", mimo że klient podpisał Profilem Zaufanym. Endpoint `confirmSigned` nie ustawia metody i brak UI do jej wyboru. To odrębna luka (informacyjna, nie blokująca) — do rozważenia osobno.
