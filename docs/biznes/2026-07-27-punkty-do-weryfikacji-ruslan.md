# Punkty do weryfikacji przez Ruslana — umowy, depozyt, PayU

> Data: 2026-07-27 · Kontekst: analiza umowy leasingowej #072426-1 (Agw Moto) i wzorca aneksu VIN
> Powiązane taski: T-217 (drugi wzorzec umowy), T-220 (aneks VIN), T-221 (pakiet prawny), T-121 (PayU)

Lista rzeczy, których **nie rozstrzygamy sami** — wymagają decyzji Ruslana albo jego prawnika.

---

## 1. Numer konta w umowie leasingowej ✅ ROZSTRZYGNIĘTE 27.07

Obowiązuje rachunek z **podpisanego egzemplarza**: Alior **72 2490 0005 0000 4530 0075 1603**.

Rachunek leasingowy dochodzi jako osobne ustawienie w panelu. **Umowa pośrednictwa i jej rachunek zostają bez zmian.**

## 2. Załącznik nr 2 — kalkulacja ceny ✅ ROZSTRZYGNIĘTE 27.07

**Pomijamy** — cała kwota wynika z umowy. Generator nie będzie go produkować. *(poniższy opis zostawiony jako kontekst)*

Wzorzec z 17.07 zawiera Załącznik nr 2 z czterema kwotami (cena w Chinach, fracht morski, odprawa, transport krajowy). W egzemplarzu Agw Moto **go nie ma** — jest tylko Załącznik nr 1 ze specyfikacją auta.

**Pytanie:** czy generator ma produkować Załącznik nr 2, czy świadomie z niego zrezygnowaliście?

## 3. Dwa depozyty — rozróżnienie ✅ ROZSTRZYGNIĘTE 27.07

W systemie i dokumentach rozdzielamy dwie różne rzeczy:

| | Depozyt **zwrotny** | Depozyt **zabezpieczający** |
|---|---|---|
| Gdzie | ustawiany w panelu | zapisany w umowie |
| Ile | **stała kwota**, niezależna od ceny auta | **% wartości samochodu** (w umowie z 24.07: 10% = 23 200 zł) |
| Kiedy | **przed** umową, po potwierdzeniu zamówienia | wynika z zawartej umowy |
| Płatność online (PayU) | tak | nie |

Przy realizacji umowy **depozyt zwrotny zostaje odliczony od depozytu zabezpieczającego** — klient nie płaci dwa razy. Tak jest opisane na stronie `/depozyt/` i w regulaminie usługi.

## 4. Zwrot depozytu — potwierdzenie zakresu regulaminu

**Umowy nie ruszamy** — jej kształt wynika z wymogów leasingodawcy.

Regulamin, który przygotowujemy pod płatności online, opisuje **wyłącznie sytuację, w której nie dojdzie do rezerwacji samochodu** — czyli to, co umowa reguluje w §4 ust. 4 i 5:

- Prima-Auto nie poniosła kosztów → **zwrot całości w 3 dni robocze**;
- koszty zostały poniesione → **zwrot po potrąceniu udokumentowanych kosztów, w 7 dni roboczych**.

Dalszych etapów (§4 ust. 6 — po rezerwacji; §4 ust. 7 i §13 — rezygnacja klienta po zarezerwowaniu auta) regulamin **nie dubluje**; tam pierwszeństwo ma umowa.

Ma to znaczenie praktyczne: klient płaci depozyt online **zanim** auto zostanie zarezerwowane, więc operator płatności ocenia dokładnie ten wycinek.

**Pytanie:** potwierdzasz taki podział? Czy terminy 3 i 7 dni roboczych obowiązują też przy umowie pośrednictwa (poza leasingiem)?

**Drobna uwaga od nas, do rozważenia przez prawnika przy okazji** (nie wpływa na PayU): §4 ust. 7 przewiduje zatrzymanie całego depozytu przy rezygnacji klienta. Wobec **konsumenta** — a takie umowy już były, np. aneks z 04.11.2024 — ryczałtowy przepadek bez związku z rzeczywistymi kosztami bywa kwestionowany jako klauzula niedozwolona. Wobec firm to nie budzi wątpliwości.

## 5. Regulamin usługi — brakujący dokument

Od prawnika macie dwa regulaminy i **oba dotyczą strony internetowej**, nie usługi. Nie ma nigdzie zasad depozytu, zwrotu, odstąpienia ani reklamacji samej usługi importu. Dzisiejszy regulamin mówi wprost, że usługi są **nieodpłatne**.

Bez tego dokumentu nie wystartujemy z płatnościami online (PayU/BLIK).

**Propozycja:** treść przygotowujemy my (żeby nie czekać), Ty przekazujesz prawnikowi wyłącznie do weryfikacji. To dużo szybsze niż zlecanie od zera.

## 6. Strona „Finansowanie" — rozjazd z umowami

`/finansowanie/` obiecuje: „30% zaliczki przy podpisaniu umowy, 70% dopłaty na 10 dni przed przypłynięciem auta do portu".

Żadna z dwóch umów tego modelu nie zawiera — jest depozyt zabezpieczający, a przy leasingu cenę płaci firma leasingowa.

**Pytanie:** który model jest aktualny? Treść na stronie poprawimy pod Twoją odpowiedź.

## 7. Aneks VIN — potwierdzenie zakresu

Rozumiemy, że aneks dotyczy **wyłącznie numeru VIN** i uzupełnia umowę podpisaną, zanim auto zostało zweryfikowane. Dotyczy obu wzorców — pośrednictwa i leasingowego.

**Pytanie:** czy przewidujesz w przyszłości aneksy do innych pól (cena, termin, specyfikacja)? Jeśli tak, zbudujemy generator od razu tak, żeby dało się je dołożyć bez przebudowy.

## 8. Depozyt leasingowy — zwrot czy zaliczenie na opłatę wstępną? (dopisane 2026-07-31)

W poprawkach do landingu leasingowego zapisałeś: *„Kwota depozytu jest zaliczana na poczet opłaty wstępnej leasingu"* — i usunąłeś wiersz o zwrocie po dojściu transakcji do skutku.

Umowa leasingowa (§4, z podpisanego egzemplarza #072426-1) mówi odwrotnie: depozyt **wraca na konto klienta w 7 dni roboczych** od otrzymania pełnej ceny od Finansującego, *„chyba że Strony uzgodnią na piśmie inne rozliczenie"* — czyli zaliczenie na opłatę wstępną jest tam wyjątkiem wymagającym osobnego dokumentu.

**Pytanie:** czy depozyt **zawsze** idzie na opłatę wstępną u firmy leasingowej, czy bywa też zwracany klientowi na konto? Jeśli zawsze — kto go przekazuje: Wy przelewacie go leasingodawcy jako wkład własny klienta, czy leasingodawca płaci Wam o tę kwotę mniej?

Od odpowiedzi zależy, czy poprawiamy §4 wzorca umowy (żeby zgadzał się ze stroną), czy stronę (żeby zgadzała się z umową). Dziś strona mówi to, co napisałeś. Szczegóły: `docs/roadmapa/T-223-depozyt-rozjazd-strona-umowa.md`.
