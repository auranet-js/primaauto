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

- [ ] Załącznik nr 1 — snapshot aukcji: `renderAttachment1()` w `class-asiaauto-contract.php`
  - Tabela parametrów z taksonomii + meta, VIN z `_order_vin`, miniaturka
- [ ] Załącznik nr 2 — breakdown v2 etapów (blocked by v0.30.5 deploy)
  - CIF USD/PLN + etapy 3-9 PLN + cena końcowa
  - `step_8_prowizja_wewnetrzna` **NIGDY** w załączniku

---

## ZADANIE 3 — Krok 5 umowy (maile + etykiety statusów)

Contract Rework Krok 5. Edycja przez admin UI, bez deploy PHP.

- [ ] Find-replace w ~15 szablonach: „zaliczka" → „depozyt zabezpieczający"
  - Kluczowe: `status_umowa_gotowa`, `status_podpisane`, `status_zarezerwowane`, `contract_regenerated`
- [ ] Sprawdzić etykiety statusów `zakonczone`/`anulowane` czy nie wspominają zaliczki
- [ ] Test na zamówieniu testowym

---

## ZADANIE 4 — Smoke test E2E

- [ ] Pełny test na zamówieniu #222262
- [ ] Wizard flow, PDF, maile, statusy, reservation

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
- [ ] Rewizja transition rules — obecne blokady sekwencyjne vs nowa elastyczność (depozyt/podpis)
- [ ] Zaprojektować nową mapę `LISTING_RESERVATION_MAP` (blokada od `zarezerwowane`)
- [ ] Zaprojektować merge kroków wizarda (usunięcie `dane_klienta`, billing w `potwierdzone`)

**Admin panel (UX):**
- [ ] UI edycji parametrów wyceny w kroku `weryfikacja` (metabox: CIF, prowizja, depozyt, koszty dodatkowe)
- [ ] Poprawki UX admin panelu zamówień (przy okazji refaktoru — order admin, admin import)
- [ ] Przegląd i aktualizacja kart/sekcji w metaboksie zamówienia

**Frontend klienta:**
- [ ] Aktualizacja wizarda — nowe kroki, labele, podświetlenie obecnego kroku
- [ ] Strona statusu zamówienia — wycena + ogólne warunki po potwierdzeniu
- [ ] Upload potwierdzenia płatności CIF (nowy element po `podpisane`)
- [ ] Poprawki UX panelu klienta

**Backend:**
- [ ] Aktualizacja transition rules w `class-asiaauto-order.php`
- [ ] Aktualizacja `LISTING_RESERVATION_MAP`
- [ ] Obsługa elastycznej kolejności depozyt/podpis
- [ ] Aktualizacja szablonów maili pod nowy flow
- [ ] Aktualizacja contract PDF jeśli flow wpływa na treść §§

**Testy:**
- [ ] E2E test nowego flow na zamówieniu testowym
- [ ] Test edge cases: depozyt przed podpisem, regeneracja po VIN, anulowanie po depozycie

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
