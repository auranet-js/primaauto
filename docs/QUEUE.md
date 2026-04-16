# Kolejka zadań — Prima Auto

> Aktualizacja: 2026-04-15 (pełna analiza SKILL.md + kodu)

## Pending 0: podbicie wersji v0.30.6

Patche A/B/C wgrane na serwer, header nie podbity. Zweryfikowane 2026-04-15.

- [x] Sprawdzić Patch A (kolumna CIF dwuwierszowa + profiler) w `class-asiaauto-admin.php`
- [x] Sprawdzić Patch B (`_asiaauto_original_price` refresh) w `class-asiaauto-importer.php`
- [x] Sprawdzić Patch C (`warning→info` override) w `class-asiaauto-price.php`
- [ ] Podbić `ASIAAUTO_VERSION` w `asiaauto-sync.php`: `0.29.0-wip` → `0.30.6`
- [ ] Test: `?aa_profile=1`, kolumna CIF, sync z override

---

## Pending 1: v0.30.5 — pipeline USD-centric (NIE WGRANY)

5 plików wygenerowanych w claude.ai, leżą w sandboxie (`/mnt/user-data/outputs/asiaauto-v0.30.5/`).
Na serwerze jest starszy kod z patchami v0.30.6 na wierzchu.

**Pliki:**
- `class-asiaauto-price.php` — pełen rewrite: USD pipeline, `calculateFromCifUsd()`, breakdown v2, lazy migration
- `class-asiaauto-admin.php` — 8 kart sekcyjnych, AJAX bulk recalc, podgląd mieszany CNY+CIF USD
- `class-asiaauto-listing-editor.php` — pole `_asiaauto_cif_price_usd` w metaboksie
- `class-asiaauto-admin-manual-import.php` — JS preview pod v2
- `class-asiaauto-order-admin.php` — `renderCardPriceBreakdownV2()` dispatcher v1/v2

**Decyzja wymagana:** Dostarczyć pliki z sandboxa i wgrać, czy odtworzyć w Claude Code?

---

## GRUPA 9.5 — po v0.30.6

- [ ] Uruchomić profiler (`?aa_profile=1`) na panelu admina
- [ ] Zidentyfikować bottleneck (najpewniej 3× COUNT na postmeta w `renderPriceTab`)
- [ ] Transient cache z invalidate przy `ajaxBulkRecalc` i `saveConfig`

---

## ZADANIE 2 — Załączniki do umowy PDF (GRUPA 10)

> Priorytet: następny. Wymaga analizy istniejącego `class-asiaauto-contract.php` na serwerze.

### Załącznik nr 1 — snapshot aukcji

Strona dodawana do PDF umowy z parametrami pojazdu z ogłoszenia.

- [ ] Analiza: przeczytać `class-asiaauto-contract.php` — jak generowany jest PDF, gdzie dodać attachment pages
- [ ] Analiza: jakie dane mamy w taksonomii/meta listingu (marka, model, rok, paliwo, kolor, przebieg, VIN)
- [ ] `renderAttachment1()` — tabela parametrów pojazdu
  - Dane: taksonomie (`make`, `model`, `fuel_type`, `body_type`, `color`) + meta (`_asiaauto_year`, `_asiaauto_mileage_km`, `_order_vin`)
  - Miniaturka pojazdu (featured image)
  - Link do oryginalnego ogłoszenia (jeśli dostępny w meta)
- [ ] Styling mPDF — spójny z resztą umowy
- [ ] Test: wygenerować PDF z załącznikiem na zamówieniu testowym

### Załącznik nr 2 — kalkulacja kosztów etapów

Strona dodawana do PDF z breakdown kosztów sprowadzenia (wg §2 umowy i załącznika nr 2).

- [ ] Analiza: obecny pipeline cenowy (`class-asiaauto-price.php`) — jakie etapy mamy, format breakdown v2
- [ ] `renderAttachment2()` — tabela kosztów etapów
  - CIF USD → PLN (przeliczenie po kursie)
  - Etapy 3-9 PLN (transport morski, odprawa, transport krajowy, akcyza, VAT, rejestracja, inne)
  - Cena końcowa PLN
  - **`step_8_prowizja_wewnetrzna` — NIGDY w załączniku** (marża wewnętrzna)
  - Wynagrodzenie zleceniobiorcy (§3) — osobna pozycja
- [ ] Sprawdzić czy breakdown v2 jest już na produkcji czy blocked by v0.30.5
- [ ] Styling mPDF — tabela z kwotami, spójne z resztą
- [ ] Test: wygenerować PDF z oboma załącznikami

---

## ~~ZADANIE 3 — Krok 5 umowy (maile + etykiety statusów)~~ DONE

> Zrealizowane w sesji 2026-04-16 w ramach ZADANIE 5.
> Szablony maili: wszystkie statusy przepisane (podpisane, zarezerwowane, zakupione, w_drodze, na_placu, w_dostawie, zakonczone, odrzucone, anulowane).
> Etykiety: "depozyt zabezpieczający" wszędzie, "zaliczka" usunięta.
> Opisy statusów na stronie klienta zaktualizowane.

---

## ZADANIE 4 — Smoke test E2E

- [ ] Pełny test na zamówieniu #222262
- [ ] Wizard flow, PDF, maile, statusy, reservation
- [ ] Test `w_dostawie` (nowy status)

---

## ZADANIE 5 — Rework workflow zamówień (model agencyjny)

> Priorytet: wysoki. Dotyczy core flow biznesowego.

### Kontekst biznesowy

Prima Auto działa jako **agencja importowa**, nie dealer. Ruslan (sprzedawca) nie kupuje aut
i nie pośredniczy w płatności za pojazd. Pomaga klientowi znaleźć auto w Chinach, wycenia
koszty sprowadzenia, koordynuje logistykę. Klient płaci za auto **bezpośrednio** chińskiemu
dealerowi. Prima zarabia na wynagrodzeniu kontraktowym (§3 umowy) + depozycie zabezpieczającym.

Umowa chroni obie strony — klient ma dokument potwierdzający zwrotność depozytu,
sprzedawca ma zobowiązanie klienta do prowizji. Numer umowy w tytule przelewu depozytu.

### Nowy flow statusów

```
nowe           → zgłoszenie (kontakt + RODO, niski próg — lead magnet)
weryfikacja    → sprzedawca ustala parametry wyceny w panelu:
                 CIF, prowizja kontraktowa, depozyt, koszty dodatkowe
                 — ZANIM klient cokolwiek widzi
potwierdzone   → klient widzi wycenę + ogólne warunki (panel + mail)
                 klient podaje billing (12 pól) — wchłonięty obecny dane_klienta
umowa_gotowa   → PDF wygenerowany z numerem AA/2026/XXXX
                 klient może wpłacić depozyt już w tym momencie (z nr umowy w tytule)
podpisane      → umowa podpisana (osobiście lub Autenti w przyszłości)
                 revert → umowa_gotowa możliwy (VIN, zmiana ceny → regeneracja)
                 depozyt — elastyczna kolejność (może być przed lub po podpisie)
zarezerwowane  → depozyt wpłacony + auto zarezerwowane w Chinach
                 sprzedawca RĘCZNIE potwierdza (nie automatyczne)
                 listing zablokowany od tego momentu
                 Ruslan wysyła dane przelewu CIF poza systemem (mail/WhatsApp)
                 klient płaci chińskiemu dealerowi i uploaduje potwierdzenie przez panel
zakupione      → sprzedawca potwierdza płatność CIF
w_drodze       → transport z Chin
na_placu       → auto w Rzeszowie
zakonczone     → wydane klientowi
```

### Kluczowe różnice vs obecny flow

1. **`weryfikacja` rozbudowane** — UI w panelu admina do edycji parametrów wyceny (prowizja, depozyt, CIF override, koszty dodatkowe) PRZED potwierdzeniem
2. **`dane_klienta` usunięty** — billing wchłonięty w krok `potwierdzone`
3. **Depozyt i podpis — elastyczna kolejność** — system nie wymusza sztywnej sekwencji
4. **`zarezerwowane` = ręczne potwierdzenie sprzedawcy** że auto jest zarezerwowane w Chinach + depozyt wpłacony (nie automatyczne przy zmianie statusu)
5. **Blokada listingu dopiero przy `zarezerwowane`** (nie przy `potwierdzone` jak teraz)
6. **Upload potwierdzenia CIF** — nowy element w panelu klienta (krok wizarda po `podpisane`)
7. **Nie udało się zarezerwować** → `anulowane`, depozyt zwrotny (poza systemem), nowe zamówienie na inne auto (nowa umowa)
8. **Wycena klienta nie akceptuje** → po prostu nie reaguje, temat umiera (brak formalnej ścieżki odrzucenia)

### Co się NIE zmienia

- Statusy fulfillment (`zakupione → w_drodze → na_placu → zakonczone`)
- Revert `podpisane → umowa_gotowa` (regeneracja umowy)
- `anulowane` z każdego nie-terminalnego statusu
- Generowanie PDF (mPDF, §1-§9, model agencyjny)
- Dane przelewu CIF — poza systemem
- REST API klienta (dostosowanie endpointów, nie przebudowa)

### Przyszłe integracje (poza scope tego zadania)

- PayU — płatność depozytu online
- Autenti — podpis zdalny przez konto sprzedawcy

### Podzadania

**Analiza i projektowanie:**
- [x] Rewizja transition rules — obecne blokady sekwencyjne vs nowa elastyczność (depozyt/podpis)
- [x] Zaprojektować nową mapę `LISTING_RESERVATION_MAP` (blokada od `zarezerwowane`)
- [x] Zaprojektować merge kroków wizarda (usunięcie `dane_klienta`, billing w `potwierdzone`)

**Admin panel (UX):**
- [x] UI edycji parametrów wyceny w kroku `weryfikacja` (metabox: CIF, prowizja, depozyt, koszty dodatkowe)
- [x] Poprawki UX admin panelu zamówień — karty CIF/Depozyt z badge'ami, dane do przelewu w osobnym bloku
- [x] Przegląd i aktualizacja kart/sekcji w metaboksie zamówienia (karty: Pojazd, Klient, Cena, Dane umowy, Umowa, Notatki, Status, Akcje, Log)
- [x] Admin lista zamówień — kolumna depozyt + kolumna CIF (✓/✗/—)

**Frontend klienta:**
- [x] Aktualizacja wizarda — nowe kroki, labele (depozyt zamiast zaliczka, umowa pośrednictwa)
- [x] Sidebar: szacowany koszt sprowadzenia, CIF z badge ✓/✗, depozyt z badge ✓/✗
- [x] Upload potwierdzenia płatności CIF (blok 4 wizarda, opcjonalny)
- [x] Strona statusu zamówienia — pricing (CIF, depozyt, czas dostawy) widoczny od step 3

**Backend:**
- [x] Aktualizacja transition rules w `class-asiaauto-order.php` (usunięcie `dane_klienta`, `odrzucone` → "Oferta niedostępna")
- [x] Aktualizacja `LISTING_RESERVATION_MAP` (rezerwacja od `zarezerwowane`)
- [x] Nowe meta: `_order_client_cif_usd`, `_order_cif_paid`, `_order_cif_paid_at`
- [x] `LEGACY_STATUS_MAP` — `dane_klienta → potwierdzone`
- [x] Nowy status `w_dostawie` — dostawa pod adres klienta (alternatywa dla `na_placu`)
- [x] Obsługa elastycznej kolejności depozyt/podpis (transitions niezależne od deposit_paid)
- [x] Aktualizacja szablonów maili: `order_started_customer`, `status_potwierdzone`, `status_umowa_gotowa`
- [x] Rewizja szablonów maili: podpisane, zarezerwowane, zakupione, w_drodze, na_placu, w_dostawie, zakonczone, odrzucone, anulowane
- [x] Mail `zakonczone` — link do opinii Google (writereview)
- [x] Aktualizacja opisów statusów na stronie klienta (podpisane → anulowane)
- [x] Logo umowy PDF → `primaauto-logo-round.png`
- [x] Contract PDF — model agencyjny §1-§9, Krok 4 rework DONE

**Testy:**
- [x] E2E test nowego flow: nowe → weryfikacja → potwierdzone → umowa_gotowa (PDF auto-gen)
- [x] Test statusów: podpisane → zarezerwowane → zakupione → w_drodze → na_placu → zakonczone (maile OK)
- [ ] Test statusu `w_dostawie` (tracking bar, mail)
- [ ] Test edge cases: depozyt przed podpisem, regeneracja po VIN, anulowanie po depozycie
- [ ] Test statusu `odrzucone` (Oferta niedostępna)

> **ZADANIE 5 — core flow DONE.** Pozostałe [~] przeniesione do backlogu. Dalej: ZADANIE 2 (załączniki do umowy PDF).

---

## Backlog (niski priorytet)

- [ ] Krok 4 manual editor — metabox extra_prep (18 zakładek)
- [ ] Panel klienta /moje-zamowienia/
- [ ] Email HTML templates (obecnie plain text)
- [ ] SEO: meta title/desc, Open Graph, Schema ItemList na inventory, llms.txt
- [ ] Homepage + Contact CSS → pliki zewnętrzne (z inline)
- [ ] Orphaned taxonomy terms (Li Auto, Volvo, NIO z count=0)
- [ ] Duplikat `Lynk & Co` / `Lynk &amp; Co` w make
- [ ] Archive/taxonomy pages (brand pages)
