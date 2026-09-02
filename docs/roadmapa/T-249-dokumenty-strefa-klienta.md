# T-249 — Dokumenty w strefie klienta (wymiana plików Ruslan ↔ klient)

> Status: **nowa pozycja** (decyzja Janka 2026-09-02) · Rozmiar: L
> Godziny realnie: **45–60 h** · Rynkowo: 110–145 h
> **Wymaga:** T-204 (panel klienta — dziś `/klient/` jest atrapą) · **T-244** (magic link) przed uruchomieniem powiadomień
> **Kolizja plikowa:** T-218 (panel zamówień admina) i T-209 (maile HTML) ruszają te same pliki — nie robić równolegle
> Kształt zadania: **jedna bryła po T-204** (decyzja Janka 02.09; wariant z podziałem na 3 fazy odrzucony)

---

## 1. Po co

Wymiana dokumentów w zamówieniu idzie dziś kanałami poza stroną — mail, WhatsApp. System przyjmuje
od klienta **dokładnie dwa pliki**, w sztywnych typach, w oknie dwóch statusów, **bez możliwości opisania,
czym plik jest**. Ruslan ze swojej strony może wgrać **wyłącznie własną umowę PDF** (i to tylko z numerem umowy).
Wszystko, co przychodzi później — faktura, dokumenty do rejestracji, zdjęcia z placu, papiery celne —
nie ma gdzie usiąść.

Skutek operacyjny: dokument istnieje w skrzynce jednej ze stron, nie przy zamówieniu. Przy 140 zamówieniach
i cyklu importu liczonym w miesiącach to znaczy, że „gdzie jest ten skan” rozstrzyga się telefonem do Ruslana.

Skutek prawny: 68 załączników z danymi osobowymi klientów (PESEL, adres, podpis odręczny) leży w katalogu
serwowanym publicznie — chroni je wyłącznie to, że nazwa pliku ma losowy hash. Patrz pomiar niżej.

## 2. Stan faktyczny (zmierzony 2026-09-02 na produkcji)

| Element | Gdzie | Stan |
|---|---|---|
| Upload klienta (2 typy) | `class-asiaauto-order-api.php:484` `POST /order/{id}/upload` | ✅ działa — `signed_contract` (multi-file) + `payment_proof` (single) |
| Kasowanie pliku przed potwierdzeniem | `…-order-api.php:614` `POST /order/{id}/delete-upload` | ✅ działa |
| Autoryzacja dostępu | `…-order-api.php:137` `canAccessOrder()` | ✅ poprawna — właściciel zamówienia albo `manage_options` |
| Okno statusów na upload | `…-order-api.php:501` | ⚠️ tylko `umowa_gotowa` i `podpisane` — po podpisaniu klient nie ma kanału |
| Opis pliku / nazwa własna | — | ❌ nie ma; tytuł załącznika generowany sztywno („Podpisana umowa — zamówienie #ID”) |
| Upload po stronie Ruslana | `class-asiaauto-order-admin.php:499` `upload_custom_contract` | ⚠️ tylko umowa PDF, wymaga numeru umowy, max 20 MB |
| Lista oczekiwanych dokumentów | — | ❌ nie istnieje w żadnej postaci |
| Krok 5 kreatora (statusy `zarezerwowane`…`zakonczone`) | `class-asiaauto-order-wizard.php:670` | ✅ istnieje — naturalne miejsce na dokumenty fazy transportowej, dziś bez sekcji plików |
| Panel klienta `/klient/` | `class-asiaauto-shortcodes.php:2618` | ❌ atrapa (T-204) — martwy kod listy zamówień |
| Ochrona plików | `wp-content/uploads/contracts/` | ⚠️ **plik oddawany bez logowania** (HTTP 206 na losowym PDF-ie zamówienia) |

**Pomiary liczbowe:**

- **68 załączników** przypiętych do zamówień: 62 PDF, 5 JPG, 1 DOCX.
- **170 plików** w `uploads/contracts/` przy 62 PDF-ach — WordPress dogenerował **miniatury JPG pierwszej strony
  każdej umowy** (`*-pdf-724x1024.jpg`). Pierwsza strona to strona z danymi klienta.
- Co **działa** jako obrona: listing katalogu → HTTP 403, `wp-json/wp/v2/media/{id}` → HTTP 401,
  strona załącznika → 301 na rodzica, a CPT `asiaauto_order` ma `public => false` (URL oddaje stronę główną).
- Co **nie działa**: sam plik pod bezpośrednim URL-em oddaje się każdemu. Nazwa ma 8-znakowy hash,
  więc to *security by obscurity* — wystarczy, że URL wycieknie mailem, historią przeglądarki albo logiem.

## 3. Zakres

**W zakresie:** dokumenty przypięte do zamówienia — dodawanie przez Ruslana i przez klienta, z opisem;
lista dokumentów oczekiwanych (kto dostarcza, na jakim etapie, czy już wpłynął); widok u klienta
(kreator krok 4 i 5 + zakładka w panelu z T-204) i w karcie zamówienia u Ruslana; ochrona plików i migracja
istniejących 68 załączników; powiadomienie mailowe o nowym dokumencie.

**Poza zakresem** (świadomie): podpis elektroniczny i weryfikacja podpisu, OCR i odczyt treści dokumentów,
wersjonowanie pliku (nowy plik = nowa pozycja, nie „wersja 2”), archiwum dokumentów niezwiązanych z zamówieniem,
zmiana wzorców umów (T-217/T-220), przebudowa listy zamówień (T-218).

## 4. Plan (fazy, wykonywane jako jedno zadanie)

| # | Faza | h |
|---|---|---|
| 0 | **Katalog dokumentów** — ustalić z Ruslanem, jakie dokumenty realnie krążą w cyklu importu, kto je dostarcza i na jakim etapie. Bez tego lista oczekiwanych dokumentów jest zgadywana. ⚠️ Przed wysłaniem czegokolwiek do Ruslana — quiz do Janka (reguła z globalnego CLAUDE.md §3) | 2–3 |
| 1 | **Ochrona plików + migracja** — katalog z `deny from all`, endpoint serwujący przez istniejące `canAccessOrder()`, `Content-Disposition`, migracja 68 załączników ze skryptem mapującym meta, usunięcie dogenerowanych miniatur stron umów | 6–8 |
| 2 | **Model danych** — jedna klasa `class-asiaauto-documents.php` obok istniejących: dokument = plik + opis + typ + kto wgrał + kiedy + status (oczekiwany / wpłynął / zaakceptowany / odrzucony z powodem). Lista oczekiwanych = szablon per typ zamówienia (pośrednictwo / leasing) z możliwością dopisania pozycji ręcznie w konkretnym zamówieniu | 5–7 |
| 3 | **Panel Ruslana** — sekcja w karcie zamówienia: dodaj plik z opisem, oznacz dokument jako oczekiwany, przyjmij albo odrzuć to, co przysłał klient (z powodem, bo powód idzie do klienta), licznik braków widoczny na liście zamówień | 10–13 |
| 4 | **Strona klienta** — sekcja dokumentów w kroku 4 i **kroku 5** kreatora (faza transportowa, dziś bez kanału) + zakładka „Dokumenty” w panelu klienta z T-204; klient widzi, czego się od niego oczekuje, co już wysłał i co zostało przyjęte | 12–15 |
| 5 | **Powiadomienia** — mail do Ruslana o pliku od klienta, mail do klienta o nowym dokumencie i o odrzuceniu. Format spina się z T-209; **wymaga naprawionego T-244**, bo link z maila jest jedyną drogą klienta dopóki nie loguje się hasłem | 3–5 |
| 6 | **Testy, migracja produkcyjna, regresja** | 7–9 |
| | **Razem** | **45–60** |

## 5. Strefy kruche

- `class-asiaauto-order.php` (1977 linii) — **strefa krucha**. Dokumenty **nie dotykają** `TRANSITIONS`,
  `markDepositPaid()`, `calculateDeposit()`, `LISTING_RESERVATION_MAP`. Status dokumentu jest **metadaną opisową,
  nigdy warunkiem przejścia zamówienia** — inaczej brak skanu blokuje sprzedaż.
- `class-asiaauto-order-admin.php` (3090) i `class-asiaauto-order-mail.php` (508) — te same pliki rusza T-218 i T-209.
  Kolejność: albo tamte przed, albo po. Nie równolegle (source of truth = serwer, nie repo).
- Metoda **addytywna**: nowa klasa i nowe endpointy obok istniejących; istniejące `POST /order/{id}/upload`
  zostaje działające i staje się szczególnym przypadkiem nowego mechanizmu.
- Migracja plików dotyka danych klientów — `mysqldump` + tar `uploads/contracts/` przed uruchomieniem skryptu.

## 6. Testy

**Automatyczne**
- Snapshot statusów wszystkich zamówień przed i po deployu — żaden nie może drgnąć.
- Klient A na dokument klienta B → 403 na nowym endpoincie **i** na starym bezpośrednim URL-u po migracji.
- Wszystkie 68 istniejących załączników otwierają się po przeniesieniu (skrypt przechodzi listę, sprawdza kod HTTP).
- Odrzucenie pliku podszywającego się typem MIME; limit rozmiaru; limit liczby plików na zamówienie.

**Półautomatyczne**
- Klient bez zamówienia (konto z rejestracji T-204) nie widzi żadnych dokumentów.
- Odrzucenie dokumentu przez Ruslana → klient widzi powód i może wgrać poprawiony.

**MCP (Chrome), 390 px**
- Kreator krok 4 i krok 5: wgranie zdjęcia prosto z telefonu (duży JPG z aparatu — realny przypadek klienta).
- Karta zamówienia u Ruslana z telefonu: dodanie pliku z opisem, oznaczenie braku.
- Panel klienta: zakładka „Dokumenty” po zalogowaniu hasłem i po wejściu linkiem z maila.

## 7. Definicja zrobionego

- Ruslan dodaje do zamówienia dowolny plik z własnym opisem; klient go widzi.
- Klient dodaje plik z opisem — także po podpisaniu umowy, w fazie transportu.
- Zamówienie pokazuje obu stronom listę dokumentów oczekiwanych ze stanem każdego z nich.
- Żaden dokument nie jest dostępny bez autoryzacji — także 68 istniejących.
- Statusy zamówień i przejścia między nimi działają dokładnie jak przed wdrożeniem.

## 8. Otwarte punkty

1. **Katalog dokumentów** (faza 0) — jakie pozycje wchodzą do listy oczekiwanych i na jakim etapie.
   Do ustalenia z Ruslanem, **po wcześniejszym quizie do Janka**.
2. **Retencja** — jak długo trzymamy dokumenty po zakończeniu zamówienia. Pytanie do rozstrzygnięcia razem
   z pakietem prawnym (T-221), nie osobno.
3. **Limit objętości** — dziś 10 MB na plik po stronie klienta, 20 MB u Ruslana. Zdjęcia z placu potrafią
   przekroczyć oba; do decyzji przy fazie 2, razem z pytaniem, czy pliki mają być skalowane po wgraniu.
