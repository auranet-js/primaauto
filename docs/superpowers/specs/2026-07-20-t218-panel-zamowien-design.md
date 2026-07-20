# T-218 — przebudowa panelu zamówień (admin) — design

> Data: 2026-07-20 · Status: **do akceptacji** · Rozmiar: S/M (wycena z postep.json: 4–5 h realnie, 10–13 h rynkowo)
> Mockup: `https://auratest.pl/fe4f58fec53ctmp/primaauto-t218-panel-zamowien-mockup-2026-07-20.html`
> Powiązane: T-217 (drugi wzorzec umowy — ten sam plik, osobne zadanie), T-204 (panel KLIENTA — inny panel, nie mylić)

---

## 1. Problem

Panel zamówień (`Ogłoszenia → Zamówienia`, `class-asiaauto-order-admin.php::renderOrderList()`) obsługuje dziś
140 zamówień przez **jeden wymiar filtrowania — status, i to tylko 5 z 10 statusów**, wyklikiwany z paska kafli.
Ruslan pracuje na nim także z telefonu, gdzie układ się rozjeżdża.

Stan faktyczny zmierzony 2026-07-20 (produkcja, `wp7j_`):

| Status | Ile | | Typ | Ile | w tym anul./odrzuc. | realne |
|---|---|---|---|---|---|---|
| anulowane | **36** | | customer | 76 | **35** | **41** |
| w_drodze | 25 | | stock | 64 | 3 | **61** |
| zakonczone | 22 | | | | | |
| weryfikacja | 17 | | **razem** | **140** | **38** | **102** |
| na_placu | 15 | | | | | |
| umowa_gotowa | 7 | | | | | |
| potwierdzone | 7 | | | | | |
| zakupione | 6 | | | | | |
| podpisane | 3 | | | | | |
| odrzucone | 2 | | | | | |

Konkretne wady, potwierdzone oględzinami na żywo (Chrome, zalogowana sesja):

1. **27 % listy to szum.** Anulowane + odrzucone = 38 z 140. Po stronie klientów to prawie połowa (35/76) —
   klient zamawia kilka aut do weryfikacji, reszta idzie do anulowania. Nie da się ich ukryć.
2. **Brak filtra typu.** Zamówienia klientów (realna praca) i wewnętrzne/stock (magazyn) są wymieszane.
3. **8 z 13 statusów nieosiągalnych z paska** — `zakonczone`, `w_drodze`, `zakupione`, `umowa_gotowa`,
   `potwierdzone`, `podpisane` trzeba szukać, przeglądając 7 stron.
4. **2 z 6 kafli są martwe** — „Nowe" i „Zarezerwowane" pokazują 0 (statusy przelotowe, zamówienie w nich nie stoi).
5. **Kafel „Na placu" nie należy do tego panelu** — plac ma osobną obsługę w ofercie.
6. **Kolumna ID za wąska** — łamie numer na dwie linie („#38793 / 9") w każdym wierszu.
7. **Kolumny „Depozyt" i „CIF" nie niosą informacji** — 20 wierszy z rzędu pokazuje „6 150 zł ✗" i „—".
8. **Telefon:** tytuł auta („BYD Leopard 7 (Tai 7) FCB, PHEV 2025 190KM 4WD Ultra") łamie się na 4–6 linii
   i rozpycha wiersz na cały ekran. CSS ukrywa już 5 kolumn, ale to nie rozwiązuje problemu.
9. **Drobiazg w CSS:** komentarz mówi, że reguła ukrywa datę (`nth-child(9)`), a faktycznie ukrywa CIF —
   przesunięcie o jedną kolumnę względem realnej tabeli.

## 2. Zakres

**W zakresie:** widok listy zamówień — filtry, kolumny, układ na telefonie.

**Poza zakresem** (świadomie, nie robimy w tym zadaniu): karta pojedynczego zamówienia, konfiguracja,
statusy i przejścia, wyszukiwarka po kliencie/aucie, sortowanie kolumn, kasowanie zamówień,
drugi wzorzec umowy (T-217).

## 3. Decyzje (uzgodnione z Jankiem 2026-07-20)

| # | Decyzja | Uzasadnienie |
|---|---|---|
| D1 | **Kafle statystyk usunięte w całości** | 2 z 6 martwe, „Na placu" nie dotyczy zamówień; zastępuje je rząd kontrolek |
| D2 | **Filtr typu:** `Klientów \| Wewnętrzne \| Wszystkie`, w tej kolejności | Klienci to codzienna praca, stock ma osobną obsługę |
| D3 | **Filtr statusu = lista rozwijana**, wszystkie statusy z licznikami, domyślnie „Wszystkie statusy" | pokrycie 13/13 zamiast 5/13; jedna kontrolka zamiast paska kafli |
| D4 | **Checkbox „Ukryj anulowane" na końcu rzędu, domyślnie zaznaczony** | zdejmuje 27 % szumu przy wejściu; odznaczalny, gdy trzeba zajrzeć |
| D5 | **Domyślny stan wejścia: Klientów + Wszystkie statusy + anulowane ukryte** | Ruslan widzi 41 realnych zamówień zamiast 140 pozycji |
| D6 | **Liczniki przy typie dynamiczne** — reagują na checkbox (41/61/102 ↔ 76/64/140) | inaczej „Klientów 76" daje 41 wierszy i wygląda jak zgubione dane |
| D7 | **Kolumny „Depozyt" + „CIF" → jedna „Wpłaty"** ze znacznikami `D` i `C` (zielony = zapłacone, czerwony = nie, szary = nie dotyczy) | dwie kolumny × ~90 px pokazywały tę samą wartość; kwota zostaje w karcie zamówienia |
| D8 | **Telefon: karty zamiast tabeli** (wariant B z mockupu) | komplet informacji bez przewijania w bok; zdjęcie auta czytelniejsze niż „Mazda EZ-60 2026 EREV 200 Max R21" |

Odrzucone warianty: kafle jako filtry obok listy statusów (dwa mechanizmy robiące to samo, trzeba je synchronizować);
tabela z przewijaniem poziomym na telefonie (scroll w środku strony bywa mylony ze scrollem strony,
część danych zawsze poza krawędzią).

## 4. Rozwiązanie

### 4.1 Rząd kontrolek (zastępuje kafle)

```
[ Klientów 41 | Wewnętrzne 61 | Wszystkie 102 ]  [ Wszystkie statusy ▾ ]  [ ☑ Ukryj anulowane ]     41 zamówień · str. 1 z 3
```

- Typ — segment trzech przycisków, stan w URL `?typ=customer|stock|all` (domyślnie `customer`).
- Status — `<select>` z 13 statusami + „Wszystkie statusy", każdy z licznikiem; URL `?status=<klucz>`.
- Ukryj anulowane — `<input type=checkbox>`; URL `?anul=0` gdy **odznaczony** (brak parametru = zaznaczony,
  żeby domyślny stan nie wymagał parametru w adresie).
- Filtry **łączą się** (AND). Zmiana dowolnej kontrolki przeładowuje stronę (GET, bez AJAX) i resetuje `paged` na 1.
- Po prawej licznik wyniku — ile pozycji spełnia filtr i która to strona.

Spójność: gdy w liście statusów wybrane jest „Anulowane" lub „Odrzucone", checkbox „Ukryj anulowane"
jest automatycznie ignorowany (inaczej wynik byłby zawsze pusty) i wyszarzony z podpowiedzią.

### 4.2 Kolumny (desktop)

| # | Kolumna | Zmiana |
|---|---|---|
| 1 | ID | szerokość 74 px, `white-space:nowrap` — **fix łamania numeru** |
| 2 | miniatura | 56×40 px (było 50×35) |
| 3 | Auto | bez zmian |
| 4 | Typ | bez zmian |
| 5 | Klient | bez zmian |
| 6 | Status | bez zmian |
| 7 | Cena | bez zmian |
| 8 | **Wpłaty** | **nowa** — zastępuje „Depozyt" + „CIF" (D7) |
| 9 | Data | bez zmian |

Wiersze anulowane/odrzucone: tło `#fafafa`, `opacity:.72` — widoczne, ale wyraźnie drugoplanowe
(dotyczy sytuacji, gdy checkbox jest odznaczony).

### 4.3 Telefon — karty

Poniżej 782 px (breakpoint admina WP) tabela zostaje ukryta, renderuje się lista kart. Jedna karta:

```
┌──────────────────────────────────────────────┐
│ [foto]  #387939                  19.07 19:05 │
│  74×56  BYD Leopard 7 (Tai 7) FCB, PHEV      │
│         2025 190KM 4WD Ultra                 │
│         [Weryfikacja]            206 000 zł  │
│         Krzysztof Mak · Klient · [D]         │
└──────────────────────────────────────────────┘
```

- Tytuł auta przycięty do 2 linii (`-webkit-line-clamp:2`) — to jest lek na rozpychanie wiersza.
- Cała karta klikalna → karta zamówienia.
- Kontrolki nad listą: jedna pod drugą na całą szerokość, segment typu dzieli się na 3 równe części.
- Anulowane: `opacity:.62` + szare tło.

**Renderowanie:** jedna pętla po zamówieniach generuje oba widoki (wiersz `<tr>` i kartę),
CSS decyduje, który jest widoczny. Bez `wp_is_mobile()` — to psuje cache i nie reaguje na obrót ekranu.
Koszt: dublowanie ~20 wierszy markupu na pozycję. Akceptowalne przy 20 pozycjach na stronę.

## 5. Realizacja techniczna

**Pliki:** `includes/class-asiaauto-order-admin.php` (metoda `renderOrderList()` + 2 nowe prywatne
metody pomocnicze) oraz `assets/css/asiaauto-order-admin.css`.

**`class-asiaauto-order.php` NIE jest modyfikowany** — to strefa krucha (statusy, rezerwacje).
Wszystko, czego potrzebujemy, już tam jest:

- `getOrders()` przyjmuje `order_type` (linia ~z `META_ORDER_TYPE`) → filtr typu za darmo.
- `getOrders()` przyjmuje `status` jako **tablicę** z `compare => IN` → „ukryj anulowane" realizujemy
  jako `IN (11 pozostałych statusów)`, bez dokładania `NOT IN` do klasy zamówień.

**Nowe metody w klasie admina:**

1. `getCountsByTypeAndStatus(): array` — jedno zapytanie `$wpdb` grupujące po `_order_type` i `_order_status`
   (wzorzec skopiowany z `AsiaAuto_Order::getCountsByStatus()`, ale z drugim wymiarem). Zwraca macierz,
   z której liczymy wszystkie liczniki kontrolek — **jedno zapytanie na render**, nie 12.
2. `parseListFilters(): array` — czyta i sanityzuje `$_GET` (`typ`, `status`, `anul`, `paged`),
   zwraca komplet argumentów do `getOrders()` + stan kontrolek do wyrenderowania. Jedno miejsce,
   w którym mieszka logika „co jest domyślne" i „co się wyklucza".

Paginacja: `renderPagination()` dostaje komplet aktywnych filtrów zamiast samego statusu
(dziś przekazywany jest tylko `$filter_status`, więc przy nowych filtrach druga strona gubiłaby typ).

## 6. Testy

**Ręczne (desktop):**
- Wejście na panel bez parametrów → Klientów, wszystkie statusy, anulowane ukryte, 41 pozycji.
- Każdy z 3 typów × kilka statusów — licznik wyniku zgadza się z liczbą wierszy i z zapytaniem SQL.
- Odznaczenie „Ukryj anulowane" → liczniki typu skaczą na 76/64/140, pojawiają się wyszarzone wiersze.
- Wybór statusu „Anulowane" → checkbox zignorowany, lista niepusta (36 pozycji).
- Przejście na stronę 2 i 3 → filtry zachowane w URL, wyniki spójne.
- Kolumna ID nie łamie numeru w żadnym wierszu.

**Ręczne (telefon, MCP Chrome, 390 px):**
- Karty: brak przewijania w bok, tytuł maks. 2 linie, ~6 kart na ekran.
- Kontrolki na całą szerokość, klikalne palcem (min. 40 px wysokości).
- Klik w kartę → karta zamówienia.

**Regresja:**
- Przycisk „Zamówienie wewnętrzne" i widok karty zamówienia działają jak przed zmianą.
- `php -l` na zmienionym pliku; `.bak` z datą przed nadpisaniem.
- Liczby w panelu zgodne z zapytaniem kontrolnym w `wp db query` (ten sam podział co w sekcji 1).

## 7. Definicja zrobionego

- Wejście w panel pokazuje domyślnie 41 zamówień klientów bez anulat.
- Wszystkie 13 statusów osiągalnych z listy rozwijanej, z licznikami.
- Anulowane ukrywalne jednym kliknięciem, w obie strony.
- Liczniki zgodne z tym, co realnie pokazuje lista.
- Na telefonie jedna karta = jedno zamówienie z kompletem kluczowych danych, bez przewijania w bok.
- `class-asiaauto-order.php` nietknięty (`git diff` to potwierdza).
- Wersja pluginu podbita, wpis w `docs/VERSIONS.md`.
