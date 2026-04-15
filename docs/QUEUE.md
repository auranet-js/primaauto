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

## Backlog (niski priorytet)

- [ ] Krok 4 manual editor — metabox extra_prep (18 zakładek)
- [ ] Panel klienta /moje-zamowienia/
- [ ] Email HTML templates (obecnie plain text)
- [ ] SEO: meta title/desc, Open Graph, Schema ItemList na inventory, llms.txt
- [ ] Homepage + Contact CSS → pliki zewnętrzne (z inline)
- [ ] Orphaned taxonomy terms (Li Auto, Volvo, NIO z count=0)
- [ ] Duplikat `Lynk & Co` / `Lynk &amp; Co` w make
- [ ] Archive/taxonomy pages (brand pages)
