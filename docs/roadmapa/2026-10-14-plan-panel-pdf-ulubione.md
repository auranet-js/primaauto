# Plan: PDF-y → panel klienta → ulubione → dokumenty (od 2026-10-14)

> Kolejność zatwierdzona przez Janka 14.10. Sześć wątków, każdy w osobnej sesji.
> **Zasada wątku:** sesja zaczyna się od sprawdzenia stanu (git pull, serwer) i **jednego quizu** z decyzjami
> z sekcji „Decyzje przed startem" (AskUserQuestion, rekomendacja pierwsza, max 3–4 pytania na rundę).
> Po quizie wątek idzie do końca **bez pytań w trakcie**. Wyjątek to tylko rzeczy z „Stop, pytaj":
> zapis do bazy zamówień, kasowanie plików klientów, `.htaccess`, zmiana poza zakresem wątku.
> Nowa wątpliwość w trakcie → rozstrzygasz wg rekomendacji z tego pliku albo wzorca z kodu i zapisujesz ją
> w raporcie końcowym („rozstrzygnięte w trakcie"), zamiast zatrzymywać pracę.
> Zmiany wizualne: makiety (stan obecny + 2–3 warianty, desktop i telefon) są **pierwszym krokiem wątku** i wchodzą
> w quiz startowy — nie w środek wdrożenia.

## Kolejność i zależności

| # | Wątek | Taski | Wymaga | Godz. realnie |
|---|---|---|---|---|
| W1 | Specyfikacja auta w PDF | T-262 | — | 8–10 |
| W2 | Wydruk ogłoszenia do PDF | T-263 | W1 (wspólny renderer) | 4–6 |
| W3 | Makiety: wejście do konta, ulubione, krok „dane do umowy" | T-204 krok 6, T-114 (header, serce, modal), T-257 (rozpoznanie + makiety) | — (można równolegle z W1/W2) | do wyceny na starcie, szac. 4–6 |
| W4 | Panel klienta — wejście, rejestracja, magic link, dane do umowy | T-204 domknięcie, T-257, T-244 | W3 (wybrane warianty) | do wyceny po W3 |
| W5 | Karta oferty + ulubione | T-212, T-114 | W4 | 14–19 |
| W6 | Dokumenty w zamówieniu | T-249 | W4, W1 (ten sam `class-asiaauto-order-admin.php`) | 45–60 |
| W7 | Prezentacja procesu zakupu (wideo + karuzela) | T-265 | — (teraz, na obecnym panelu); po W4 ponowny render sceny panelu | 16–22 + 1–2 |

Razem bez W3/W4: 71–95 h. Na spotkaniu 07.10 padło „panel klienta w październiku (plus ulubione)" — W3 warto
puścić równolegle z W1, żeby W4–W5 zmieściły się w październiku.

---

## W1 — T-262 „specyfikacja auta w PDF przy zamówieniu"

**Gotowe:** makiety (wariant B „Rysunek techniczny" z logo, 13.10, `docs/makiety/gen-t262-t263-pdf.py`,
`render-mpdf.php`), model wersji (QUEUE T-262: zamrożony opis auta, dane zamówienia na żywo, kolejne wersje
z numerem i datą), wycena 8–10 h, mPDF 8.3.1 jest w `vendor/` pluginu.
**Brak specu w `docs/roadmapa/`** — wątek zaczyna od krótkiego specu z wynikami quizu.

**Decyzje przed startem (quiz):**
1. Stosunek do Załącznika nr 1 umowy — rek.: **osobny dokument, Załącznik nr 1 bez zmian** (`class-asiaauto-contract.php` = strefa krucha).
2. Dostęp klienta, dopóki panel nie działa — rek.: **przycisk pobrania w kreatorze zamówienia** (wejście z maila), od statusu `potwierdzone`; w panelu dojdzie w W4/W6.
3. Zamówienia sprzed wdrożenia — rek.: **snapshot wstecz tylko tam, gdzie ogłoszenie jeszcze istnieje**; pozostałe bez przycisku z notą „opis auta niedostępny" (56 z 206 nie ma ogłoszenia).
4. Miejsce przycisku w karcie zamówienia admina — rek.: w istniejącej sekcji dokumentów/umowy, tak żeby W6 wchłonął go bez przenosin.

**Rozstrzygnięte, nie pytać:** font Barlow dokładamy do mPDF (OFL); generuje tylko admin; nowa klasa obok, addytywnie.
**Stop, pytaj:** testowe zamówienie w bazie (reguła: zakładanie zamówień = zgoda Janka) — test na istniejącym zamówieniu testowym 410903.

## W2 — T-263 „wydruk ogłoszenia do PDF"

**Gotowe:** wygląd (wspólny z W1), zakres adnotacji (art. 66 KC, data i cena „stan na dzień", klauzula T-259, dane firmy).

**Decyzje przed startem (quiz):**
1. **Treść adnotacji prawnych** — wątek przygotowuje gotowy tekst nagłówka i stopki, Janek akceptuje w quizie.
2. **Miejsce przycisku „Pobierz PDF" na ogłoszeniu** — makieta stanu + 2–3 wariantów (desktop i pasek mobilny, gdzie są już 3 przyciski) na starcie wątku.
3. Cache — rek.: generowanie na żądanie, plik w cache do zmiany ceny/tytułu albo 24 h.
4. Pomiar — rek.: zdarzenie GA4 `pdf_download` (bez oznaczania jako konwersja).

**Rozstrzygnięte:** oferty prywatne (T-258) — PDF tylko z ważnym kluczem; szkice — brak.

## W3 — makiety: wejście do konta, ulubione, „dane do umowy"

Jeden zestaw makiet, bo ikona konta, licznik ulubionych i modal „zaloguj się, żeby zapisać" siedzą w tym samym headerze
(spec T-114 krok 5–6, T-204 krok 6). Do tego T-257: rozpoznanie (mail statusu + ekran „dane do umowy",
kotwice w QUEUE T-257) i makiety poprawionej wersji.

**Decyzje przed startem (quiz):**
1. Zakres makiet — rek.: header desktop + menu mobilne (zalogowany / niezalogowany), modal logowania/rejestracji, serce na karcie i na ogłoszeniu, zakładka „Ulubione", ekran „dane do umowy" + mail.
2. Rejestracja — czy dokładamy **nieobowiązkową zgodę marketingową** (cel ulubionych to baza pod mailing, spec T-114) — rek.: tak, osobny checkbox.

**Wynik wątku:** wybór wariantów przez Janka (to jest koniec W3, nie pytanie w trakcie W4).
Pamiętaj: mobile = 79,6% ruchu, pasek mobilny oferty ma już 3 przyciski.

## W4 — T-204 domknięcie + T-257 + T-244

**Gotowe:** panel (v0.40.0): zakładki, rejestracja, logowanie, zmiana hasła, zamrożenie danych umownych.
**Brakuje:** wejście w headerze/menu (wg W3), T-257 wg W3, test ścieżki od zera na produkcji.

**Decyzje przed startem (quiz):**
1. **T-244 „magic link ginie po pierwszym otwarciu i przy drugim zamówieniu"** (zdjęty z kolejki 07.09, defekt nadal jest) — rek.: **naprawić w W4** (2–4 h), bo W6 wymaga go przed powiadomieniami, a W4 i tak dotyka logowania.
2. „Nie pamiętam hasła" — wątek sprawdza na starcie, czy jest; jeśli nie — rek.: dodać w W4.
3. Komunikacja do Ruslana o uruchomieniu panelu — rek.: wpis na stronie postępu + krótka wiadomość przez Janka.

**Stop, pytaj:** test rejestracji tworzy konto na produkcji — konto testowe usuwamy po teście (jak 08.09).

## W5 — T-212 karta oferty + T-114 ulubione

**Uwaga: spec T-212 jest nieaktualny.** Zakładał trzy kopie jednej karty (`aa-home__car`). Stan 14.10:
dwie rodziny kart — `aa-card` (`AsiaAuto_Inventory::renderCard()`: katalog, `/w-rzeszowie/`, `/w-drodze/`, wyszukiwarka)
oraz `aa-home__car` (huby marek/modeli, strona główna, `class-asiaauto-wiki-cars.php`).

**Decyzje przed startem (quiz):**
1. **Karta** — rek.: **bez scalania rodzin** (scalenie = zmiana wizualna na najbardziej widocznych stronach); `aa-home__car` do jednego helpera (HTML identyczny co do znaku, diff przed/po), a serce jako wspólny slot wstrzykiwany do obu rodzin. Alternatywa: jedna karta = makiety + osobna wycena.
2. Limit ulubionych na konto — rek.: brak limitu w UI, test na 200.

**Rozstrzygnięte (spec T-114):** user meta, REST z nonce, intencja zapamiętana przy rejestracji, auto zniknięte = wyszarzone.

## W6 — T-249 dokumenty w zamówieniu

**Gotowe:** spec 45–60 h, lista dokumentów Ruslana (§9, 9.1), decyzja „jedna bryła".

**Decyzje przed startem (quiz):**
1. **VAT ID i EORI** — rek.: **pola w danych zamówienia, nie pliki** (§9.1 wnioski).
2. **Limit plików** — rek.: 20 MB na plik, zdjęcia skalowane po wgraniu do 2560 px; wideo tylko jako link.
3. **Retencja po zakończeniu zamówienia** — rek.: bez kasowania automatycznego w v1, decyzja razem z regulaminem usługi.
4. Makiety sekcji dokumentów (karta zamówienia u Ruslana + kreator krok 4/5 + zakładka w panelu) — 2–3 warianty na starcie.

**Stop, pytaj:** migracja 68 istniejących załączników (dane klientów) — `mysqldump` + tar, dry-run, potem zgoda.

## W7 — T-265 „prezentacja procesu zakupu — wideo + karuzela” (dopisane 14.10)

Spec i decyzje D1–D4: `docs/roadmapa/T-265-prezentacja-procesu-zakupu.md`. Idzie równolegle z W1/W2, bo nie dotyka kodu pluginu.
Zależność od W4 rozwiązana skryptem: scenariusz JSON + silnik renderują sceny z żywej strony, więc po wdrożeniu nowego panelu
uruchamiamy render ponownie (1–2 h), zamiast produkować materiał od nowa.

**Decyzje przed startem (quiz):** 1. akcept scenariusza i tekstów scen (szkic w specu); 2. auto do prezentacji — rek.: auto z placu w Rzeszowie;
3. jak opisać rozliczenie po depozycie (scena 7–9).
**Stop, pytaj:** rozszerzenie silnika w repo `auranet` (commit tam osobno); przekazanie materiału Ruslanowi idzie przez Janka.
