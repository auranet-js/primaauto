# T-220 — aneks VIN: wdrożenie (2026-08-06)

> Plugin **0.34.21 → 0.34.22**, LIVE na produkcji. Plan wykonawczy:
> `docs/sesje/2026-08-03-T-220-PAKIET-WYKONAWCZY.md` (§6 wykonane w kolejności),
> poprawki wejściowe: `docs/sesje/2026-08-06-T-220-PROMPT-STARTOWY.md`.
> Czas: ~5 h (pakiet szacował 4–6 h).

---

## Co jest w produkcji

| Element | Gdzie |
|---|---|
| `generateAnnex()`, `renderAnnexHTML()`, `renderAnnexPDF()`, `createAnnexAttachment()`, `resolveContractDate()`, `annexBlockedReason()`, `getAnnexUrl()` | `class-asiaauto-contract.php` (na końcu klasy, obok istniejących) |
| `ANNEX_FIELDS`, `ANNEX_TITLES`, `VIN_MISSING`, `VIN_ANNEX_CLAUSE` | tamże, stałe klasy |
| `META_ANNEX_VIN_ID/_GENERATED_GMT/_STATE/_SENT_GMT`, `META_CONTRACT_DATE`, `ANNEX_STATES` | `class-asiaauto-order.php` |
| klucze `annex_vin_id`, `annex_vin_state`, `annex_vin_sent_at`, `contract_date`, `vin` w `getOrderData()` | tamże |
| akcje `generate_annex_vin`, `set_annex_vin_state`, handlery + `sendAnnexVinEmail()`, wiersz `renderAnnexVinRow()` | `class-asiaauto-order-admin.php` |
| szablon `annex_vin_sent` + etykieta w Ustawieniach → Szablony | `class-asiaauto-order-content.php` |

Backupy: `*.bak-2026-08-06-T-220` (4 pliki na serwerze).

## Obieg dla Ruslana

1. VIN nieznany przy podpisywaniu → pole „VIN" w karcie zamówienia zostaje **puste**.
   Umowa sama zapowiada aneks (leasing od T-217, pośrednictwo od dziś — D6).
2. Auto przyjeżdża, numer potwierdzony → Ruslan wpisuje VIN i zapisuje dane umowy.
3. W karcie „Umowa i dokumenty" odsłania się wiersz **Aneks VIN**: data umowy (podpowiadana)
   + przycisk **Generuj aneks VIN**. Powstaje jednostronicowy PDF. **Klient nic nie dostaje.**
4. Gdy dokument jest sprawdzony → znacznik na **„Wysłany do klienta"** → dopiero wtedy leci mail
   z aneksem w załączniku (z potwierdzeniem w oknie dialogowym).

Guard: przycisk jest nieaktywny, dopóki nie ma umowy z numerem albo VIN nie ma 17 znaków —
z podanym powodem („VIN musi mieć 17 znaków bez I/O/Q — obecnie: „L1NB" (4 zn.)").

## Rozstrzygnięcia podjęte w trakcie (poza pakietem)

1. **Guard sprawdza też numer umowy.** Pakiet wymagał tylko istnienia załącznika z umową.
   W teście wyszło, że przy zamówieniu bez nadanego numeru powstaje „aneks do umowy nr "
   i taki sam tytuł załącznika. W normalnym obiegu numer nadaje `changeStatus()` albo ręczne
   wgranie umowy, więc to zabezpieczenie brzegowe — ale zostaje.
2. **Zakres D6 wobec §8 pakietu.** Scenariusz D-3 wymagał klauzuli, gdy pole zamówienia jest
   puste, a ogłoszenie ma numer ucięty; §8 parkował guard zamaskowanych VIN-ów w pośrednictwie.
   Rozstrzygnięcie: `isValidVin()` stosujemy **tylko do wartości pobranej z ogłoszenia**.
   Numer wpisany ręcznie w zamówieniu drukuje się dokładnie tak, jak go wpisano — więc
   #387790 z `L1NB` w polu ma umowę bez zmian, a temat guardu tego pola zostaje otwarty (T-217 §6).
3. **Regeneracja cofa znacznik na „wygenerowany".** Pakiet tego nie precyzował. Nowy plik to nie
   jest dokument, który klient dostał mailem — zostawienie „wysłany" wprowadzałoby w błąd.

## Testy

| Zestaw | Wynik |
|---|---|
| `tmp/T-220-testy-aneks.php` (G-1..G-7, GD-1..GD-5, M-1..M-6, D-1..D-3, R-3, R-4, CLEAN) | **62/62** |
| `tmp/T-217-regresja-posrednictwo.php` przed i po (5 umów, `pdftotext` + `diff`) | **bez różnic** |
| `tmp/T-217-testy-leasing.php` | **42/42** |
| `tmp/T-217-e2e-status-i-zalacznik.php` | **22/22** |
| `tmp/test-vin-guard-2026-08-06.php` | **15/15** |
| MCP Chrome — wiersz aneksu w 3 stanach (guard / gotowy / wygenerowany) | OK |

Cała bateria biegnie z filtrem `pre_wp_mail` — **zero maili wyszło**. Zamówienia testowe
zakładane `wp_insert_post` (nie `AsiaAuto_Order::create()`, żeby nie ruszać rezerwacji),
kasowane po teście; zweryfikowane: 0 zamówień testowych, 0 osieroconych plików `aneks-*`
w `uploads/contracts`, liczniki umów AA=30 / UL=2 bez zmian.

**Wpadka do zapamiętania:** pierwszy przebieg baterii wywalił się na `add_settings_error()`
(funkcja z `wp-admin/includes/template.php`, nieładowana w CLI) i zostawił 4 zamówienia
z załącznikami. Sprzątanie działa tylko wtedy, gdy skrypt dobiegnie do końca — po każdym
przerwanym przebiegu trzeba sprawdzić bazę ręcznie. Sprzątnięte.

### Poprawione przestarzałe asercje w zestawie T-217

Nie regresja T-220, tylko dryf środowiska nieodnotowany 06.08 przy T-242:

- **T-20** sprawdzało, że VIN z ogłoszenia trafia do umowy leasingowej — T-242 świadomie to
  zabiło. Asercja odwrócona: numer oferty **nie może** wejść do dokumentu, przy pustym polu
  ma być klauzula.
- **T-30 / T-31 / T-04** liczyły numery UL od zera („pierwsza umowa → UL/2026/0001"), a są już
  dwie produkcyjne umowy leasingowe. Teraz liczone względem stanu licznika na wejściu.

## Poza zakresem (bez zmian wobec pakietu)

- Aneksy do innych pól niż VIN — konstrukcja gotowa (mapa `ANNEX_FIELDS`), UI wystawia jeden przycisk.
- Sekcja dokumentów w kroku 5 kreatora — klient po podpisaniu umowy nie pobierze z panelu ani
  jej, ani aneksu. Osobny, mały task.
- Brak wersjonowania PDF przy `regenerate()` umowy głównej — znane, otwarte.
- Znacznik aneksu na liście zamówień (T-218).

## Do zgłoszenia Ruslanowi

T-242 i T-220 raportujemy **razem**, jako jeden obieg (patrz „Obieg dla Ruslana" wyżej). Do tego:

1. **Brzmienie odwołania w aneksie leasingowym** — wzorzec Piskorza dotyczy pośrednictwa
   i wskazuje jedną jednostkę; w leasingu VIN stoi w dwóch miejscach, więc odwołanie brzmi
   „…określony w §2 Umowy lit. e oraz w Załączniku nr 1 do Umowy". To nasza konstrukcja,
   nie cytat — warto pokazać przy pierwszym aneksie leasingowym.
2. **6 umów wgranych ręcznie nie ma znanej daty** — przy pierwszym aneksie do takiej umowy
   Ruslan musi ją wpisać (pole jest, z ostrzeżeniem).
3. Punkt 7 listy weryfikacyjnej (`docs/biznes/2026-07-27-punkty-do-weryfikacji-ruslan.md`) —
   czy przewiduje aneksy do innych pól — nadal bez odpowiedzi. Nie blokuje.

Podgląd dokumentów (kopie robocze):
`primaauto-T-220-aneks-posrednictwo-2026-08-06.pdf`, `primaauto-T-220-aneks-leasing-2026-08-06.pdf`
w katalogu tymczasowym auratest.
