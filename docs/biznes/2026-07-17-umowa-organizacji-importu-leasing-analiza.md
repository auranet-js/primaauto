# Wzorzec „Umowa organizacji importu samochodu" (wariant leasingowy) — analiza pól pod T-217

> Data analizy: 2026-07-17. Źródło: `2_5384554645924389148.docx` (Google Drive
> primaauto/umowy, edytowalny — nie skan). To jest WŁAŚCIWY dokument pod T-217;
> wcześniej analizowany skan (umowa o współpracy z EFL) to osobny dokument —
> patrz [2026-07-17-umowa-efl-leasing-analiza.md](2026-07-17-umowa-efl-leasing-analiza.md),
> oba się uzupełniają (EFL = partner finansujący, ten wzorzec = umowa z klientem).

## Co to jest

Wzorzec umowy Prima-Auto ↔ klient dla ścieżki leasingowej. Konstrukcja inna niż obecna
umowa pośrednictwa: klient („Zleceniodawca") NIE kupuje auta — auto po imporcie kupuje
**Finansujący** (firma leasingowa) na mocy **trójstronnej umowy sprzedaży**
(Prima-Auto = sprzedający, Finansujący = kupujący, klient = korzystający), a klient
używa auta na podstawie umowy leasingu z Finansującym.

Struktura: §1-§16 + Załącznik nr 1 (specyfikacja samochodu) + Załącznik nr 2
(kalkulacja ceny).

## Pola do wypełnienia w umowie (placeholdery `[_]`)

1. **Data zawarcia** (§ nagłówek)
2. **Dane Zleceniodawcy** (nagłówek) — osoba/firma; dla leasingu w praktyce firma
   (nazwa, adres, NIP) lub konsument (§15 rozróżnia konsumenta!)
3. **§2 parametry Samochodu** — `[_]` (lista a-f, w tym: VIN „zostanie uzupełniony
   aneksem albo aktualizacją Załącznika nr 1", stan techniczny wg Załącznika nr 1)
4. **§14 dane kontaktowe Zleceniodawcy** — `[_]` (telefon, e-mail; umowa dopuszcza
   komunikację przez WhatsApp)
5. **Załącznik nr 1 — specyfikacja:** marka i model, paliwo, kolor, rok produkcji,
   rok rejestracji, VIN, kraj pochodzenia, stan techniczny, przebieg, uwagi
   (wyposażenie, rynek pochodzenia, dokumentacja, oprogramowanie, stan)
6. **Załącznik nr 2 — kalkulacja ceny (4 kwoty):**
   - maksymalna cena Samochodu w Chinach (zakup + opłata aukcyjna + prowizja +
     załadunek/oględziny + transport CN do portu wysyłki)
   - koszt załadunku i transportu morskiego
   - koszt odprawy celno-skarbowej (agencja celna)
   - koszt transportu z portu docelowego do miejsca wydania

## Wartości stałe wpisane we wzorcu (inne niż w obecnej umowie!)

| Parametr | Wzorzec leasingowy | Obecna umowa pośrednictwa |
|---|---|---|
| Depozyt zabezpieczający | **12 000,00 zł brutto** (stała kwota w §4.1) | konfigurowalny (ostatnio 6 150 zł), % lub kwota |
| Konto do wpłaty | Alior 53 2490 0005 0000 4000 0233 4847, tytuł „depozyt zabezpieczający – import samochodu" | konto w configu zamówień |
| Termin przygotowania do wydania | **do 120 dni** od daty zakupu w Chinach (orientacyjny) | inne sformułowanie / terminy per umowa |
| Limit odpowiedzialności | **10 000 zł** (§11.4) | brak odpowiednika w tej postaci |
| Kara umowna | = wysokość depozytu (§13.1) | inna konstrukcja |
| Wynagrodzenie Prima-Auto | ukryte w „cenie stałej" sprzedaży Finansującemu (§5.1, trójstronna) | jawna prowizja pośrednictwa |
| Zwrot depozytu | 3 dni rob. (brak kosztów) / 7 dni rob. (po potrąceniu / po zapłacie ceny przez Finansującego); możliwe zaliczenie na opłatę wstępną leasingu | zwrot wg obecnego modelu |

Uwaga do memory `reference_order_config_is_operational_knob`: kwoty depozytu Ruslan
zmienia ad hoc — 12 000 zł we wzorcu traktować jako aktualny nastaw, nie stałą systemu.

## Różnice logiki procesu (istotne dla systemu zamówień)

- **Brak przeniesienia własności na klienta** — umowa wprost: „Niniejsza Umowa nie
  stanowi podstawy do przeniesienia własności Samochodu na Zleceniodawcę" (§8.9).
- **Warunki wydania auta** (§8.1): umowa leasingu + trójstronna umowa sprzedaży +
  zapłata ceny przez Finansującego + uregulowanie należności + upoważnienie
  Finansującego do odbioru. To nowe kroki procesu, których obecny cykl statusów
  zamówienia nie zna (dziś: depozyt → zakup → transport → odprawa → odbiór).
- **Finansujący wskazany przez klienta albo uzgodniony** (§3.1) — wzorzec nie wiąże
  z EFL; umowa z EFL to nasz preferowany kanał, ale pole „Finansujący" jest otwarte.
- **Ryzyko odmowy finansowania po stronie klienta** (§3.5-3.6); odmowa Finansującego
  ≠ nienależyte wykonanie przez Prima-Auto.
- **VIN uzupełniany aneksem/aktualizacją załącznika** po ustaleniu — system ma już
  meta `_order_vin` (Contract Rework krok 1-4), pasuje.

## Co musi się zmienić w systemie (mapa miejsc — do wyceny T-217)

1. **Generator umowy PDF** (`class-asiaauto-contract.php`): drugi szablon §1-§16
   + 2 załączniki. Obecny generator ma zaszyte §1-§9 modelu agencyjnego — wybór
   szablonu per zamówienie.
2. **Wybór rodzaju umowy w panelu zamówienia** (wymaganie Ruslana): pośrednictwo
   vs leasing — pole przy zamówieniu, od niego zależy: szablon PDF, treści maili,
   etykiety kwot.
3. **Pola danych zamówienia:** kalkulacja Załącznika nr 2 (4 kwoty) — częściowo
   mapowalna z breakdownu cenowego (cena CN + transport lokalny / fracht morski /
   odprawa / transport krajowy), ale wymaga agregacji do 4 pozycji; pole
   „Finansujący" (nazwa podmiotu); rok rejestracji + kraj pochodzenia + uwagi
   w specyfikacji (mamy w meta listingu).
4. **Depozyt:** inna kwota domyślna (12 000 zł) i inna narracja („depozyt
   zabezpieczający", zaliczany docelowo inaczej niż w pośrednictwie — zwrot po
   zapłacie ceny przez Finansującego albo zaliczenie na opłatę wstępną). Dotyka
   maili statusowych i ekranów kreatora (Krok 5 Contract Rework — „depozyt" —
   nadal pending, warto połączyć prace).
5. **Statusy zamówienia:** ścieżka leasingowa ma dodatkowe bramki (decyzja
   leasingowa, umowa trójstronna, uruchomienie finansowania). Minimalnie: bez
   nowych statusów, notatki operacyjne; docelowo: warianty statusów/etykiet
   per rodzaj umowy — do decyzji przy wycenie.
6. **Maile transakcyjne:** subjecty/treści mówiące o „zakupie" nie pasują do
   leasingu (klient nie kupuje). Wariantowanie treści per rodzaj umowy.
7. **Kreator zamówienia (frontend):** jeśli klient ma deklarować ścieżkę leasingową
   przy składaniu zamówienia — dodatkowe pole/krok + dane firmy (NIP). Alternatywa
   operacyjna na start: rodzaj umowy wybiera Ruslan w panelu, front bez zmian.

## Otwarte pytania przed wyceną

1. Kto wybiera ścieżkę: klient w kreatorze czy Ruslan w panelu? (start: panel?)
2. Czy kalkulacja Załącznika nr 2 ma się liczyć automatycznie z breakdownu
   cenowego listingu, czy Ruslan wpisuje 4 kwoty ręcznie? (breakdown ma więcej
   pozycji — cło/akcyza/VAT siedzą w innych miejscach niż 4 pola załącznika)
3. Czy depozyt 12 000 zł dla leasingu ma być osobnym nastawem w configu
   (obok obecnego), czy per zamówienie?
4. Czy wzorzec był konsultowany prawnie / czy to wersja finalna od Ruslana?
   (dokument wygląda na kompletny, bez śladów rewizji)
5. Spięcie z EFL: czy przy leasingu przez EFL (umowa o współpracy) zgłoszenie
   leadu do Systemu EFL robi Ruslan ręcznie? (patrz analiza EFL, §3 aneksu)
