# T-210 — Edytor danych technicznych auta w panelu

> Status: gotowy do odpalenia · Rozmiar: M/L
> Godziny realnie: **22–28 h** (Janek ~3 h, AI ~19–25 h) · Rynkowo: 55–70 h
> **Podniesione z 15–18 h.** Poprzednia estymata zakładała „mechanizm ochrony zmian już jest, wystarczy dorobić interfejs". **To założenie jest błędne.**

## Po co

Ruslan poprawia błędną specyfikację z chińskiego API bez dzwonienia po programistę. Dane z Dongchedi bywają niepełne albo pomylone, a klient podejmuje decyzję zakupową na ich podstawie.

## 🔴 Sedno problemu: ochrona zmian działa na całe auto, nie na pole

Dziś istnieje mechanizm chroniący ręczne zmiany przed nadpisaniem przez importer (`AsiaAuto_Sync::isManuallyManaged()`, `class-asiaauto-sync.php:231`). Ale działa **wszystko-albo-nic**:

> Jeśli oferta ma flagę „zarządzana ręcznie", importer **całkowicie ją pomija** — nie aktualizuje ceny, nie aktualizuje niczego, i **nie wycofuje jej, gdy zniknie z chińskiej giełdy**.

A flaga zapala się **przy pierwszym zapisie metaboxa** (`class-asiaauto-listing-editor.php:590`).

**Konsekwencja — to jest mina:**
> Ruslan otwiera automatycznie zaciągnięte ogłoszenie, poprawia jedną literówkę → **auto na zawsze wypada z synchronizacji**. Cena przestaje się aktualizować. Oferta nie znika, gdy auto zostanie sprzedane w Chinach. Zostaje w katalogu jako duch.

Przy dzisiejszym metaboxie (9 pól) to jest akceptowalne ryzyko — Ruslan rzadko tam zagląda. **Przy edytorze specyfikacji, którego celem jest zachęcić go do poprawiania danych, to jest gwarantowana katastrofa.**

## Co trzeba zbudować (i czego nie ma wcale)

### 1. 🔴 Blokada per pole (nie istnieje) — ~8–10 h
Nowa meta `_asiaauto_locked_fields` = lista kluczy zmienionych ręcznie. Importer robi różnicę: **aktualizuje wszystko oprócz zablokowanych pól**. Cena dalej się odświeża, rotacja dalej działa, a poprawka Ruslana zostaje.

To jest **serce tego zadania** i główny powód, dla którego to nie jest 15 h.

⚠️ **Strefa krucha** — dotyka `class-asiaauto-importer.php` i `class-asiaauto-sync.php`. Wymaga testów regresji.

### 2. Interfejs edycji specyfikacji (nie istnieje) — ~10–12 h
Dziś **zero UI** do danych technicznych. Grep w edytorze ofert: `extra_prep` nie występuje ani razu.

- Specyfikacja to **~342 klucze na ofertę** (JSON), pogrupowane w 19 kategorii.
- Słowniki tłumaczeń: **970 wpisów** (403 nazwy pól, 501 wartości) — **już istnieją**, reużywamy.
- Interfejs: kategorie zwijane, pole = etykieta po polsku + wartość, oznaczenie „zmienione ręcznie", przycisk „przywróć wartość z API".
- **Nie pokazujemy 342 pól naraz** — sekcja „najczęściej poprawiane" (~30 pól) + reszta pod rozwinięciem.

### 3. Migracja istniejących flag — ~2–3 h
Oferty, które **już mają** flagę „ręczna" i przez to nie synchronizują się od miesięcy — przejrzeć i zdecydować, czy wrócić do automatu (z zablokowanymi tylko realnie zmienionymi polami).

### 4. ⚠️ Uwaga na uszkodzony unicode — ~2–3 h
Chińskie wartości z Dongchedi są w bazie **uszkodzone** (leży `u4e2du5927` zamiast znaków — patrz T-116). Edytor **musi** pokazywać wartość naprawioną, a zapisywać poprawnie — inaczej Ruslan będzie „poprawiał" krzaki. Jeśli T-116 (migracja do tabeli specyfikacji) pójdzie pierwszy, ten problem znika.

## Zależności

- **Najtaniej po T-116** (tam robimy migrację i naprawę unicode).
- Można zrobić wcześniej, ale wtedy dochodzi obsługa uszkodzonych danych w edytorze.

## Testy

**Automatyczne (krytyczne — to strefa krucha)**
- **Regresja:** oferta bez ręcznych zmian → importer aktualizuje ją dokładnie tak jak dziś (bit w bit).
- Oferta z zablokowanym polem „moc" → import aktualizuje cenę i przebieg, **nie rusza mocy**.
- Oferta z zablokowanym polem → **nadal jest wycofywana**, gdy zniknie z API (dziś nie jest!).
- Przywrócenie wartości z API → blokada znika, pole wraca do automatu.

**Półautomatyczne**
- Test na kopii bazy: 20 ofert, ręczna edycja, pełny cykl importu → sprawdzić, co się zmieniło, a co przetrwało.
- Przegląd ofert dziś oznaczonych jako „ręczne" — ile ich jest i czy nie są martwe.

**MCP (Chrome)**
- Edycja specyfikacji w panelu: zmiana wartości, oznaczenie „ręczne", przywrócenie z API.
- Sprawdzenie, że poprawiona wartość pojawia się na stronie oferty.

## Definicja zrobionego

- Ruslan edytuje dowolne pole specyfikacji z panelu.
- **Edycja jednego pola NIE wyrzuca auta z synchronizacji** — cena i rotacja działają dalej.
- Widać, które pola są ręczne, i można cofnąć do wartości z API.
- Zero regresji na ofertach bez ręcznych zmian.
- Oferty z ręcznymi zmianami są **nadal wycofywane**, gdy znikną z chińskiej giełdy.
