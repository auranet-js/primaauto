# Kolejka zadań — Prima Auto

> Aktualizacja: 2026-04-17 (sesja 3: załączniki PDF, bezpieczeństwo, wizard, bump 0.30.8)

## ~~Pending 0: podbicie wersji~~ DONE

- [x] `ASIAAUTO_VERSION`: `0.29.0-wip` → `0.30.7` (2026-04-16)
- [ ] Test: `?aa_profile=1`, kolumna CIF, sync z override (backlog)

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

> Kod wgrany 2026-04-16, przetestowane 2026-04-17.

### Załącznik nr 1 — snapshot aukcji

- [x] `renderAttachment1()` — foto + tabela 12 parametrów + podpisy (wgrane 2026-04-16)
- [x] Test: PDF z załącznikiem na zamówieniu #238868 (2026-04-17) — OK

### Załącznik nr 2 — kalkulacja kosztów

- [x] `renderAttachment2()` — CIF USD→PLN, etapy, §3, extras, suma (wgrane 2026-04-16)
- [x] `suggestClientCif()` — hint przy inpucie CIF w adminie
- [x] `calculateOrderPrice()` — auto-przeliczenie price_final po zapisie CIF
- [x] Fix: `$client_cif` niezdefiniowane → input CIF zawsze pusty po zapisie (2026-04-16)
- [x] Akcyza 0% — widoczna z adnotacją „zwolnione" zamiast ukrytej (2026-04-17)
- [x] Test: PDF z oboma załącznikami — regeneracja #238868 OK (2026-04-17)

### Poprawki umowy i bezpieczeństwo (sesja 3, 2026-04-17)

- [x] Nr umowy w tytule przelewu depozytowego (deposit-box §1)
- [x] Token w nazwie pliku PDF (bezpieczeństwo — URL nieodgadywalny)
- [x] `UPLOAD_DIR` zmieniony z `asiaauto/contracts` na `contracts`
- [x] Info o formach podpisu w wizardzie klienta (odręczny/kwalifikowany/ePUAP)
- [x] Bootstrap `asiaauto-sync.php` odtworzony po uszkodzeniu sed, bump 0.30.8

---

## ~~ZADANIE 3 — Krok 5 umowy (maile + etykiety statusów)~~ DONE

> Zrealizowane w sesji 2026-04-16 w ramach ZADANIE 5.
> Szablony maili: wszystkie statusy przepisane (podpisane, zarezerwowane, zakupione, w_drodze, na_placu, w_dostawie, zakonczone, odrzucone, anulowane).
> Etykiety: "depozyt zabezpieczający" wszędzie, "zaliczka" usunięta.
> Opisy statusów na stronie klienta zaktualizowane.

---

## ZADANIE 4 — Smoke test E2E

- [ ] Wizard flow, PDF z załącznikami, maile, statusy, reservation
- [ ] Test `w_dostawie` (tracking bar, mail)
- [ ] Test edge cases: depozyt przed podpisem, regeneracja po VIN, anulowanie po depozycie

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
- [x] Panel klienta `/klient/` — shortcode `[asiaauto_klient_panel]`, logout→/klient/, auto-redirect do zamówienia (2026-04-16)
- [ ] Email HTML templates (obecnie plain text)
- [ ] SEO: meta title/desc, Open Graph, Schema ItemList na inventory, llms.txt
- [ ] Homepage + Contact CSS → pliki zewnętrzne (z inline)
- [ ] Orphaned taxonomy terms (Li Auto, Volvo, NIO z count=0)
- [ ] Duplikat `Lynk & Co` / `Lynk &amp; Co` w make
- [ ] Archive/taxonomy pages (brand pages)
