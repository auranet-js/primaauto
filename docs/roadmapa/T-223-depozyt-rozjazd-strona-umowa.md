# T-223 — Rozjazd: depozyt leasingowy na stronie vs §4 wzorca umowy

> Utworzony: 2026-07-31
> Status: **czeka na odpowiedź Ruslana** (pytanie o faktyczny proces, nie o treść)
> Powiązane: T-217 (wzorzec umowy leasingowej, LIVE v0.34.14), T-219 (landing leasingowy),
> T-221 (pakiet prawny — ta sama klasa problemu: obietnica na stronie bez pokrycia w umowie)

---

## 1. Skąd się wziął

Ruslan przejrzał treść landingu `/leasing-samochodu-z-chin/` i odesłał poprawki
(mail 31.07, `leasing tekst do strony.docx`). Jedna z nich zmienia sens, nie stylistykę.

**Było na stronie (za wzorcem umowy):**

> …to on pozwala nam zarezerwować i kupić konkretne auto w Chinach, **zanim leasingodawca
> za nie zapłaci**.
>
> | Transakcja dochodzi do skutku, leasingodawca płaci za auto | **Zwrot w 7 dni roboczych** od otrzymania pełnej ceny — albo, jeśli tak ustalimy, **zaliczenie na opłatę wstępną leasingu** |

**Po poprawce Ruslana (wdrożone 31.07 — decyzja Janka):**

> …to on pozwala nam zarezerwować i kupić konkretne auto w Chinach. **Kwota depozytu jest
> zaliczana na poczet opłaty wstępnej leasingu.**

Wiersz tabeli opisujący scenariusz sukcesu został usunięty.

## 2. Rozjazd — twarde brzmienie umowy

`class-asiaauto-contract.php:1489` (wzorzec leasingowy, §4, treść z podpisanego
egzemplarza #072426-1):

> „Jeżeli dojdzie do rezerwacji, zakupu lub importu Samochodu, depozyt zostanie **zwrócony
> Zleceniodawcy w terminie 7 dni roboczych** od dnia otrzymania przez Zleceniobiorcę pełnej
> ceny sprzedaży Samochodu od Finansującego, **chyba że Strony uzgodnią na piśmie inne
> rozliczenie depozytu**, w szczególności jego zaliczenie na poczet opłaty wstępnej, udziału
> własnego albo innej należności wynikającej z dokumentów leasingowych."

Czyli:

| | Umowa (§4) | Strona (od 31.07) |
|---|---|---|
| Domyślnie | zwrot na konto klienta w 7 dni roboczych | zaliczenie na opłatę wstępną |
| Zaliczenie na opłatę wstępną | wyjątek, **wymaga pisemnego uzgodnienia Stron** | jedyna opisana ścieżka |

**Ryzyko:** klient czyta stronę, podpisuje umowę mówiącą co innego. Przy każdej transakcji
potrzebny byłby dodatkowy dokument (pisemne uzgodnienie), albo klient dostanie przelew
zwrotny, którego się nie spodziewał — i zapyta, dlaczego strona obiecywała co innego.

Mechanicznie to nie jest oczywiste: depozyt wpłacany jest **do Prima-Auto**, a opłata wstępna
płacona jest **Finansującemu**. Żeby zaliczenie zadziałało, ktoś musi te pieniądze przekazać
dalej — albo Prima-Auto na rachunek Finansującego jako wkład własny klienta, albo Finansujący
płaci Prima-Auto pomniejszoną cenę i uznaje różnicę za wpłatę klienta. Który z tych dwóch
wariantów jest praktyką, wie tylko Ruslan.

## 3. Pytanie do Ruslana (jedno)

> Depozyt 10%, który klient wpłaca nam przed zakupem auta — czy po uruchomieniu leasingu
> **zawsze** idzie na opłatę wstępną u firmy leasingowej, czy bywa też zwracany klientowi
> na konto? Jeśli zawsze na opłatę wstępną — kto go przekazuje: my przelewamy go
> leasingodawcy, czy leasingodawca płaci nam o tę kwotę mniej?

Dopisać do `docs/biznes/2026-07-27-punkty-do-weryfikacji-ruslan.md` (tam już czeka 7 punktów,
m.in. o depozycie i zakresie zwrotu — to punkt ósmy z tej samej rodziny).

## 4. Konsekwencje wg odpowiedzi

| Odpowiedź Ruslana | Co robimy |
|---|---|
| „zawsze na opłatę wstępną" | §4 wzorca do przepisania — zaliczenie jako **reguła**, zwrot jako wyjątek. Dotyka `renderLeasingHTML()` = strefa krucha, wymaga osobnego przejścia i testu na `tmp/T-217-testy-leasing.php`. Strona zostaje jak jest. |
| „bywa różnie" | Strona wraca do wariantu z dwiema ścieżkami (backup treści sprzed zmiany: `~/backups/primaauto/leasing-398850-2026-07-31-1755.html`). Umowa nietknięta. |
| „zwrot na konto, zaliczenie sporadycznie" | Jak wyżej, plus rozważyć, czy w ogóle opisywać zaliczenie na stronie. |

## 5. Czego ten task NIE robi

Nie zmienia wzorca umowy z własnej inicjatywy. Kształt umowy leasingowej wynika z wymogów
leasingodawcy i podpisanego egzemplarza #072426-1 — zmiana idzie przez Ruslana, nie przez nas.
