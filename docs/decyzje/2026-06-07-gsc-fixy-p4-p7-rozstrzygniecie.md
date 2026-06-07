# ADR 2026-06-07 — Rozstrzygnięcie fixów P4-P7 z audytu GSC (zamknięte, nie wracać)

> Kontekst: pełny audyt GSC 2026-06-07 wygenerował plan P1-P7 (`tmp/raport-gsc-2026-06-07.md`).
> P1+P2 wdrożone (v0.32.69/70). P3 = akcja Janka (eksport listy 404 z GSC UI).
> P4-P7 przeszły recon przed wdrożeniem — **dane unieważniły większość planu**. Niżej decyzje finalne.

## Status

| # | Decyzja | Powód (dane) |
|---|---|---|
| P1 | ✅ WDROŻONE | Product schema `["Product","Car"]`+Offer na 4458 ofertach (v0.32.69) |
| P2 | ✅ WDROŻONE | Resolver `/model/` fallback z marki listingów (v0.32.70) |
| P3 | ⬜ Janek | Eksport „Nie znaleziono (404)" z GSC UI (API tego nie daje) |
| P4 | ❌ ODRZUCONE | hub `arrizo-5` PUSTY (0 ofert). „Arrizo S"=Arrizo 5, brak w stocku. `arrizo-8` już bierze frazę (poz 4,9 / 40 klik). Kierowanie 1409 wyśw. na pustą stronę = błąd. To decyzja BIZNESOWA (czy sprowadzać Arrizo 5), nie SEO |
| P5 | ❌ NIE ROBIMY | `arrizo-8` i `arrizo-8-pro` mają już różne, poprawne tytuły z ceną (rework v1 30.05). Pro to wariant 8 → 2 wyniki w SERP to nie problem, to 2× realestate |
| P6 | ❌ NIE ROBIMY | Title z ceną JUŻ JEST (zeekr 8x „od 416 000 PLN", denza n9 „od 255 000 PLN"). Słaby CTR ≠ brak ceny (drogie auta, poz 7-8). `/kontakt/` noindex ryzykowny dla local SEO — home już wygrywa „prima auto rzeszów" (poz 1,5), kontakt poz 7,8 nie odbiera klików |
| **P7** | ❌ **ODRZUCONE NA STAŁE** | **Realny zakres = 0.** 2316 serii bez `_asiaauto_primary_make_slug` to WSZYSTKIE puste (0 publish listingów). Każda seria z ofertami ma metę (importer ustawia auto, `class-asiaauto-importer.php:610`). Backfill pustych: brak z czego wyznaczyć markę, nie pomaga P2 (niezależny od mety — bierze z listingów), nie pomaga tytułom (puste huby nie rankują). Bezprzedmiotowe |

## Wniosek
Z planu P4-P7 **nic nie wdrożono — celowo**. Recon (stan stocku, istniejące tytuły, użycie mety w kodzie) pokazał, że punkty były pisane w raporcie bez weryfikacji produkcji i dane je unieważniły. Zero zmian = zero ryzyka. Trzymamy się zasady „żadnych decyzji bez danych".

## Co ZOSTAJE żywe z wątku GSC
- 80 URL zgłoszone do reindeksu 2026-06-07; **auto re-test `at` 11.06 09:00** (`tmp/gsc-reindex-retest-2026-06-07.py` → mail do Janka).
- P3 (Janek: eksport 404).
- Pomiar B1 + metryki „Opisy produktów" ~20.06 (recrawl).
- Osobny task: **rework strony głównej** (intro/H2/opisy + KW stron info + LLM) — `docs/QUEUE.md`.

## Konsekwencja
**Nie wracać do P4/P5/P6/P7.** Jedyny warunek reaktywacji P4: decyzja biznesowa Ruslana o sprowadzaniu Arrizo 5 (wtedy temat = „uzupełnić stock", nie SEO redirect).
