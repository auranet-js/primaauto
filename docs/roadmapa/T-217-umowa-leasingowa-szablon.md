# T-217 — Drugi wzorzec umowy (leasing) w generatorze PDF

> Status: **gotowe do budowy** · Rozmiar: M
> Godziny realnie: **14–20 h** (Janek ~3 h, AI ~11–17 h)
> Źródła: `docs/biznes/2026-07-17-umowa-organizacji-importu-leasing-analiza.md` (wzorzec), `docs/biznes/2026-07-17-umowa-efl-leasing-analiza.md` (partner finansujący)
> Powiązane: T-220 (aneks VIN — musi znać typ umowy), T-113 (ścieżka finansowania w kreatorze), T-121 (depozyt)

---

## 1. Co robimy

Generator PDF (`class-asiaauto-contract.php`) umie dziś **jeden** wzorzec — pośrednictwo, §1–§9, model agencyjny. Dokładamy **drugi**: „Umowa organizacji importu samochodu" (wariant leasingowy), §1–§16 + Załącznik nr 1.

**Zasada nadrzędna (Janek, 2026-07-27): treści prawnej nie zmieniamy.** Odtwarzamy dokument Ruslana paragraf po paragrafie, dostosowując wyłącznie **skład i szatę graficzną** do naszego wyglądu oraz podstawiając dane z zamówienia. Żadnych własnych poprawek merytorycznych — uwagi zgłaszamy osobno (patrz `docs/biznes/2026-07-27-punkty-do-weryfikacji-ruslan.md`).

## 2. Wzorzec referencyjny

`GAC Hyper HL … Agw Moto … leasing.docx` — umowa **#072426-1** z 24.07.2026, wypełniony egzemplarz B2B (Google Drive, PrimaAuto/tmp).

Konstrukcja: klient („Zleceniodawca") **nie kupuje auta** — kupuje je Finansujący na mocy trójstronnej umowy sprzedaży, klient używa na podstawie leasingu.

## 3. Rozbieżności egzemplarza 24.07 vs wzorzec analizowany 17.07

| Pole | Wzorzec 17.07 | Egzemplarz 24.07 | Rozstrzygnięcie |
|---|---|---|---|
| Depozyt zabezpieczający | 12 000 zł stała | 10% wartości = 23 200 zł | **Zostaje jak w umowie — % wartości auta** (decyzja Janka 27.07) |
| Konto | Alior 53 2490 0005 0000 4000 0233 4847 | Alior 72 2490 0005 0000 4530 0075 1603 | **Obowiązuje numer z podpisanej umowy (…603)** — decyzja Janka 27.07 |
| Załącznik nr 2 (kalkulacja) | jest | brak | **Pomijamy** — cała kwota wynika z umowy (decyzja Janka 27.07) |

### ⚠️ Dwa różne depozyty — nie mylić

| | Depozyt **zwrotny** | Depozyt **zabezpieczający** |
|---|---|---|
| Gdzie żyje | konfiguracja zamówień (panel) | treść umowy |
| Wysokość | **stała**, niezależna od ceny auta (dziś 6 150 zł) | **% wartości samochodu** (w umowie Agw Moto: 10% = 23 200 zł) |
| Kiedy | **przed** umową, przy statusie `potwierdzone` | wynika z zawartej umowy |
| Czego dotyczy | T-121 (PayU), `/regulamin-uslugi/`, `/depozyt/` | T-217 (szablon PDF) |

**Rozliczenie:** przy realizacji umowy wpłacony depozyt zwrotny zostaje **odliczony od depozytu zabezpieczającego**. Klient nie płaci dwa razy.

Generator umowy leasingowej **nie czyta** kwoty depozytu z konfiguracji zamówień — drukuje depozyt zabezpieczający zgodnie z umową.

## 4. Decyzje

| # | Decyzja |
|---|---|
| D1 | Treść umowy **odtwarzana 1:1**, zmieniamy tylko skład i szatę |
| D2 | **Depozyt zabezpieczający w umowie zostaje bez zmian** — procent wartości samochodu zgodnie z umową (w egzemplarzu Agw Moto: 10% = 23 200 zł). To **inna** pozycja niż depozyt zwrotny z panelu; przy realizacji umowy depozyt zwrotny zostaje od niego odliczony (patrz tabela wyżej) |
| D3 | **Opłata wstępna leasingu** = osobne pole na zamówieniu, poza oboma depozytami |
| D5 | **Załącznik nr 2 (kalkulacja ceny) pomijamy** — cała kwota wynika z umowy |
| D6 | **Osobny rachunek dla umowy leasingowej.** W konfiguracji zamówień dochodzi nowe pole `bank_account_number_leasing` = `72 2490 0005 0000 4530 0075 1603`, czytane **wyłącznie** przez szablon leasingowy. Istniejące `bank_account_number` (`17 2490 1057 0000 9900 2270 3049`) zostaje nietknięte — karmi umowę pośrednictwa, kartę przelewu w kreatorze i stronę `/kontakt/`. ⚠️ W obiegu są **trzy** różne numery (wzorzec 17.07 miał jeszcze `53 2490 0005 0000 4000 0233 4847`) — obowiązuje ten z podpisanej umowy |
| D4 | Wybór typu umowy — pole przy zamówieniu, **ustawia Ruslan w panelu**; front bez zmian |

## 5. Zakres zmian

1. **Pole „typ umowy"** na zamówieniu (pośrednictwo / leasing) + kolumna lub znacznik w panelu.
2. **Drugi szablon** w generatorze — metoda obok istniejącej, `generate()` pośrednictwa nietknięty.
3. **Nowe pola danych:** Finansujący (nazwa podmiotu), opłata wstępna, rok rejestracji, kraj pochodzenia, uwagi do specyfikacji (część mamy w meta listingu).
4. **Załącznik nr 1** — specyfikacja auta z danych listingu.
5. **Wariantowanie maili** — dzisiejsze treści mówią o „zakupie", a przy leasingu klient nie kupuje.
6. **Spięcie z T-220** — aneks VIN musi wiedzieć, którego wzorca dotyczy.

## 6. Strefy kruche

- **`class-asiaauto-contract.php`** — dokładamy obok, nie modyfikujemy istniejącego szablonu ani `generate()`.
- **Statusy** — w tym zadaniu **nie ruszamy**. Bramki leasingowe (decyzja leasingowa, umowa trójstronna, uruchomienie finansowania) to T-113.

## 7. Definicja zrobionego

- Ruslan wybiera typ umowy przy zamówieniu i generuje PDF właściwego wzorca.
- Umowa leasingowa wygląda jak nasza (szata), czyta się jak jego (treść 1:1).
- Kwota depozytu identyczna w PDF, mailu, karcie przelewu i płatności online.
- Zero regresji na dotychczasowej umowie pośrednictwa.
