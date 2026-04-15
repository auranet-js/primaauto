# Kolejka zadań — Prima Auto

> Aktualizacja: 2026-04-15 (bootstrap)

## Pending: podbicie wersji v0.30.6

Patche wgrane na serwer, header nie podbity. Trzeba zweryfikować + bump.

- [ ] Sprawdzić Patch A (kolumna CIF dwuwierszowa) w `class-asiaauto-admin.php`
- [ ] Sprawdzić Patch B (`_asiaauto_original_price` refresh) w `class-asiaauto-importer.php`
- [ ] Sprawdzić Patch C (`warning→info` override) w `class-asiaauto-price.php`
- [ ] Podbić `ASIAAUTO_VERSION` → `0.30.6`
- [ ] Test: `?aa_profile=1`, kolumna CIF, sync z override

---

## GRUPA 9 — po v0.30.6

- [ ] Transient cache na 3× COUNT w `renderPriceTab` (analiza outputu profilera `?aa_profile=1`)

---

## ZADANIE 2 — Załączniki do umowy PDF

- [ ] Załącznik nr 1 — snapshot aukcji (dane z Dongchedi w momencie zamówienia)
- [ ] Załącznik nr 2 — breakdown v2 etapów (tabela kosztów dla klienta)

---

## ZADANIE 3 — Krok 5 umowy (maile + etykiety statusów)

Narracja: „depozyt zabezpieczający" zamiast „zaliczka". Formuła wyliczenia bez zmian.

- [ ] Maile statusów — zmienić treści w admin UI (`asiaauto_order_email_templates`)
- [ ] Display labels — zmienić w admin UI (`asiaauto_order_status_display`)
- [ ] Przetestować na zamówieniu testowym

---

## ZADANIE 4 — Smoke test E2E

- [ ] Pełny test na zamówieniu #222262
- [ ] Wizard flow, PDF, maile, statusy, reservation
