# T-220 — Aneks VIN do umowy (generator + status + wysyłka do klienta)

> Status: **gotowe do budowy** (decyzje zapadły 2026-07-27) · Rozmiar: S/M
> Godziny realnie: **5–7 h** (Janek ~1 h, AI ~4–6 h) · Rynkowo: 12–18 h
> Powiązane: T-217 (drugi wzorzec umowy — aneks musi obsłużyć oba), T-218 (panel zamówień — lista, nie karta)
>
> ⚠️ **Pakiet wykonawczy: `docs/sesje/2026-08-03-T-220-PAKIET-WYKONAWCZY.md`** — zastępuje ten spec
> tam, gdzie się rozjeżdżają. Trzy korekty z 2026-08-03 (po weryfikacji kodu i bazy):
> **(a)** D5 „aneks w kroku 4 kreatora” **wycofane** — kreator pokazuje wtedy krok 5, panel 4 jest
> ukryty; aneks idzie do klienta mailem z PDF-em w załączniku;
> **(b)** guard to `isValidVin()` (17 znaków), nie „VIN niepusty” — w bazie są ucięte numery
> i jeden już trafił do zamówienia (#387790: `L1NB`);
> **(c)** D6 potwierdzone jako **w zakresie** — 99,2 % ofert dongchedi nie ma VIN-u, więc `—`
> w umowie pośrednictwa jest regułą, nie wyjątkiem.
> Wymóg nadrzędny dopisany przez Janka: **wygenerowanie aneksu nie wysyła żadnych maili.**

---

## 1. Problem

Umowa jest podpisywana, **zanim znany jest numer VIN** — dotyczy to obu wzorców:

- **pośrednictwo:** VIN w `§1 ust. 1 pkt 1 lit. c`;
- **leasing:** VIN w `§2 lit. e` *oraz* w **Załączniku nr 1** — literalnie „zostanie dodany aneksem do umowy po weryfikacji auta" (umowa #072426-1 z 24.07.2026, Agw Moto).

Dziś uzupełnienie VIN-u odbywa się poza systemem — Ruslan robi aneks ręcznie w Wordzie. Wzorzec istnieje: `Aneks do umowy.docx` (Piotr Piskorz, 04.11.2024) — jedna strona, jeden paragraf zmieniający wyłącznie VIN.

**Zakres aneksu: wyłącznie VIN** — potwierdzone przez Janka 2026-07-27. Nie budujemy generycznego edytora aneksów, **ale konstrukcja ma być rozszerzalna**: aneksy do innych pól (cena, termin, specyfikacja) mają dać się dołożyć bez przebudowy.

### Konsekwencja dla architektury (wymóg, nie sugestia)

Generator przyjmuje **listę zmienianych pozycji**, nie zaszyty na sztywno VIN:

```
[ ['pole' => 'vin', 'jednostka' => '§2 lit. e + Załącznik nr 1', 'wartosc' => 'LC0…'] ]
```

- pojedyncza definicja pola: klucz meta → jednostka redakcyjna per typ umowy → etykieta;
- szablon iteruje po liście i drukuje tyle punktów, ile pozycji dostał;
- dziś lista ma zawsze **jeden** element (VIN) i UI wystawia tylko ten przycisk — dołożenie kolejnego pola to wpis w mapie definicji, nie zmiana szablonu ani logiki.

Koszt tej konstrukcji dziś jest bliski zera; przerabianie generatora zaszytego pod jedno pole kosztowałoby tyle, co napisanie go od nowa.

## 1a. Miejsce aneksu w procesie — ścieżka wyjątkowa, nie główna

Docelowy przebieg (wyjaśnienie Janka 2026-07-27, sedno sensu T-121):

```
potwierdzone → klient płaci depozyt online   ← klient związany PRZED umową
             → weryfikacja konkretnego egzemplarza
             → znamy VIN
             → umowa generowana JUŻ Z VIN-em
```

Umowa nie jest potrzebna do tego, żeby klient zapłacił — wiąże go depozyt. Dlatego **w normalnym przebiegu aneks nie powstaje**. Aneks obsługuje wyjątek: umowa musiała pójść, zanim poznaliśmy VIN.

Potwierdza to stan bazy (2026-07-27): z 29 zamówień z wpisanym VIN **6 ma go już przed zakupem** (3 × `potwierdzone`, 3 × `umowa_gotowa`), a **144 listingi mają VIN już w ogłoszeniu** (`vin_number` — che168 podaje go w ofercie). VIN wchodzi na dowolnym etapie, często wcześnie.

**Konsekwencja projektowa:** nie rozbudowujemy `TRANSITIONS` (strefa krucha, siedzi tam rezerwacja listingu) po to, by obsłużyć ścieżkę wyjątkową → znacznik aneksu jako osobne pole, patrz D4.

## 2. Decyzje (2026-07-27, Janek)

| # | Decyzja |
|---|---|
| D1 | **Jeden uniwersalny szablon aneksu** dla obu typów umów. Treść wzorca Piskorza jest w zasadzie uniwersalna — różni się wyłącznie **odwołaniem do jednostki redakcyjnej** zmienianej umowy (`§1 ust. 1 pkt 1 lit. c` w pośrednictwie, `§2 lit. e` + Załącznik nr 1 w leasingowej). To **parametr podstawiany wg typu umowy**, nie osobny dokument (korekta 27.07 — wcześniej zakładaliśmy dwa szablony) |
| D2 | Osobny przycisk **„Generuj aneks VIN"** przy umowach; aktywny tylko gdy VIN jest wypełniony |
| D3 | Generowanie **nie zmienia statusu** — powstaje tylko dokument |
| D4 | **Znacznik aneksu jako osobne pole** przy statusie głównym (`brak / wygenerowany / wysłany`), przełączany **ręcznie**; przejście na „wysłany" wysyła klientowi powiadomienie z aneksem. Obsługa identyczna jak przy statusie, ale status zamówienia i `TRANSITIONS` pozostają nietknięte. Powód: aneks to ścieżka wyjątkowa (patrz §1a), a status na wspólnej liście przykrywałby etap procesu (auto „w drodze" znikałoby z filtra do czasu ręcznego powrotu) i wymagałby dopisania mapowania kroku w kreatorze klienta |
| D5 | Aneks trafia **do tego samego miejsca co umowa** — klient pobiera go tam, gdzie „Pobierz umowę" (kreator, krok 4, linia ~241) |
| D6 | **Umowa generowana bez VIN-u ma to wprost zapowiadać.** Gdy `_order_vin` jest puste (i brak VIN-u na listingu), w polu VIN drukuje się: *„Numer VIN zostanie uzupełniony w formie aneksu do niniejszej umowy"* — zamiast dzisiejszego `—`. Dotyczy **obu** wzorców; w leasingowym w obu miejscach (§2 lit. e i Załącznik nr 1), gdzie wzorzec Ruslana ma już analogiczne sformułowanie |

## 3. Stan faktyczny (zweryfikowany na produkcji 2026-07-27)

| Element | Stan |
|---|---|
| Pole VIN na zamówieniu | ✅ `_order_vin` (`META_VIN`, `class-asiaauto-order.php:117`), edytowalne w metaboksie „Dane umowy" |
| Fallback VIN | `_order_vin` → `vin_number` listingu → `—` (`class-asiaauto-contract.php:300-313`) — **ostatni człon do zmiany wg D6** |
| Generator PDF | ✅ mPDF, `class-asiaauto-contract.php` (1075 l.) — dziś jeden szablon §1–§9 |
| „Pobierz umowę" u klienta | `class-asiaauto-order-wizard.php:241`, krok 4 |
| Statusy | 13 sztuk, `STATUSES` + `TRANSITIONS` (`:33`, `:59-71`) — **strefa krucha** |
| Rezerwacja listingu | `LISTING_RESERVATION_MAP` — 5 statusów; status spoza mapy to **no-op**, nie czyści rezerwacji (`:661-664`) ✅ |

## 4. Rozwiązanie

### 4.1 Generator aneksu

Metoda **obok** istniejącego generatora umowy (nie modyfikujemy `generate()` — [[feedback_additive_not_fragile_zone]]):

- wejście: `order_id`; czyta `_order_vin`, dane stron z zamówienia, numer i datę **już wygenerowanej** umowy głównej (aneks zawsze odnosi się do konkretnego, istniejącego dokumentu);
- jeden szablon; wg typu umowy (pole z T-217) podstawiane jest wyłącznie odwołanie do zmienianej jednostki redakcyjnej;
- wyjście: PDF w tej samej szacie co umowa, treść wg wzorca Piskorza: data + miejsce, oznaczenie stron, jeden paragraf zmieniający VIN, dwa podpisy;
- zapis jako załącznik zamówienia + meta `_order_annex_vin_id`, `_order_annex_vin_generated_gmt`.

Guard: przycisk nieaktywny, gdy `_order_vin` puste albo umowa główna niewygenerowana. Ponowne generowanie nadpisuje (VIN mógł zostać poprawiony) — poprzedni plik zostaje w bibliotece mediów.

### 4.2 Znacznik aneksu (zamiast statusu)

Nowa meta na zamówieniu `_order_annex_vin_state` = `brak | wygenerowany | wyslany`, wystawiona w karcie zamówienia jako select **obok** statusu głównego.

- `wygenerowany` ustawia się automatycznie po utworzeniu PDF;
- przełączenie na `wyslany` → mail do klienta z aneksem (załącznik albo link) + zapis `_order_annex_vin_sent_gmt`;
- **`STATUSES`, `TRANSITIONS` i `LISTING_RESERVATION_MAP` pozostają nietknięte** — zamówienie zachowuje swój etap, nie znika z filtrów, kreator klienta liczy krok jak dotąd;
- na liście zamówień (T-218) znacznik może wejść jako mała ikona w kolumnie dokumentów — opcjonalnie, poza zakresem tego taska.

### 4.3 Widok klienta

W kroku 4 kreatora, pod „Pobierz umowę", drugi kafel „Pobierz aneks VIN" — widoczny tylko gdy aneks istnieje.

## 5. Strefy kruche

- **`class-asiaauto-order.php`** — dokładamy wyłącznie nową meta i jej obsługę w karcie zamówienia. `STATUSES`, `TRANSITIONS` i `LISTING_RESERVATION_MAP` **nietknięte** — to główny powód wyboru znacznika zamiast statusu (D4).
- **`class-asiaauto-contract.php`** — dokładamy metodę obok, `generate()` nietknięty.

## 6. Testy

**Automatyczne**
- Aneks dla obu typów umowy → poprawny PDF, poprawna jednostka redakcyjna (`§1 ust.1 pkt 1 lit. c` vs `§2 lit. e` + Załącznik nr 1).
- VIN pusty → przycisk nieaktywny, brak możliwości wygenerowania.
- **Regresja statusów:** wszystkie dotychczasowe przejścia działają bez zmian (znacznik nie dotyka `TRANSITIONS`, ale test potwierdza brak efektów ubocznych).
- Ustawienie znacznika nie zmienia `_asiaauto_reservation` ani statusu zamówienia.

**Półautomatyczne**
- Pełna ścieżka na zamówieniu testowym: VIN → generuj → status → mail → pobranie przez klienta.

**MCP (Chrome)**
- Karta zamówienia (przycisk, guard na pustym VIN), kreator krok 4 (dwa kafle), mobile.

## 7. Definicja zrobionego

- Ruslan wpisuje VIN, klika „Generuj aneks VIN", dostaje PDF w szacie Prima-Auto.
- Przełącza znacznik „Aneks VIN → wysłany" → klient dostaje informację i pobiera aneks tam, gdzie umowę.
- Zamówienie zachowuje swój etap; zero zmian w statusach i rezerwacjach listingów.
