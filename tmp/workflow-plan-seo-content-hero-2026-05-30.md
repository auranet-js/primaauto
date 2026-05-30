# Plan workflow — SEO / treść / hero (Prima-Auto)
**Data:** 2026-05-30 · **Status:** plan handoff do nowego wątku · **Zasada:** dotąd ZERO wdrożeń na produkcji

Skonsolidowany plan po sesji diagnozy + briefu + pipeline'u zdjęć. Nowy wątek startuje od Fazy 1.

---

## DECYZJE PODJĘTE (zamknięte, nie re-dyskutować)
- **D1.** Plugin claude-seo: NIE aktualizować (v2.0.0 dostępne, lokalne skrypty wystarczają).
- **D2.** Architektura: **hub = transakcja** (import+cena+listingi, landing Ads) · **blog = informacja** (opinie/vs/rankingi/przewodniki, linkuje do hubu). Reguła antykanibalizacyjna: **jedna fraza = jeden URL** (`{model} cena/import/gdzie kupić`→hub; reszta→blog).
- **D3.** Priorytet = **popyt z zamówień × bliska podaż**, NIE volume zewnętrzny. Rdzeń: **Leopard (3/5/7) + Denza (Z9/N9/N8L)**. Omoda 5 / Jaecoo 8 OUT (nie sprzedajemy).
- **D4.** Hero zdjęcia: **Gemini Nano Banana 2 (`gemini-3.1-flash-image`)**, tylko editorial/hero — NIGDY galeria dowodowa listingu.
- **D5.** Title/H1 hubów: pivot pod intent **cena / gdzie kupić / cena w Polsce** (import zostaje w treści — tam wygrywamy poz 2,5/33% CTR).
- **D6.** Pomiar prawdy o nas = **GSC** (nie DFS — datacenter nas nie widzi).

## DECYZJE OTWARTE (do podjęcia w nowym wątku)
- **O1.** FAQPage schema w `class-asiaauto-seo.php` — zmiana kodu, wymaga OK (strefa krucha).
- **O2.** Blog: gdzie żyje? WP posts vs nowy CPT, RankMath sitemap, kategorie = pillary.
- **O3.** Hero produkcyjne: dodać do promptu „czysta/pusta tablica, usuń chiński watermark".
- **O4.** Ruslan: Omoda 5 / Jaecoo 8 — potwierdzić że pomijamy (lub lead-gen).
- **O5.** Kolejność modeli do treści: Leopard 5/7 + Denza Z9/N9 (najwięcej zamówień) jako pierwsze.

---

## FAZA 0 — DONE (ten wątek)
- Diagnoza luki intentu (cena/gdzie kupić poz 5-8 0% CTR; import poz 2,5 33% CTR) — memory `project_intent_gap_cena_gdzie_kupic_2026_05_30`
- Analiza konkurenta chinskisamochod.com (= wydawca/blog, 2/3 ruchu nie-zakupowe, import 0%)
- Brief Denza Z9 GT + struktura blogu (5 pillarów) — `tmp/brief-denza-z9-gt-blog-2026-05-30.md` / auratest
- Pipeline hero działa — 18 hero wygenerowanych (~$0,65) — memory `reference_hero_image_pipeline_and_stock_demand_2026_05_30`
- Stock intel (placu 5 / w drodze 16) + popyt z zamówień (66) — pokrywają się, rdzeń Leopard+Denza

## FAZA 1 — Briefy + treść hubów rdzenia (NASTĘPNY KROK)
Cel: per model rdzenia zrobić brief wg szablonu Denza (title/H1 pod intent, H2 z PAA+GSC, FAQ+FAQPage, kotwice Ads), potem regenerować `asiaauto_wiki_body`.
1. Brief per model: **Leopard 5, Leopard 7, Denza Z9 GT, Denza N9** (kolejność = zamówienia) → potem Leopard 3, Sealion 8, Exeed VX, Deepal G318, Zeekr 9X, Jetour G700, Mazda EZ-6.
2. Zebrać PAA + GSC long-tail per model (jak dla Denzy: `tmp/gsc-*` + DFS SERP advanced PAA).
3. Wygenerować wiki pod nowy outline (pipeline `tmp/hub-wiki-generate.py` — rozszerzyć o sekcje cena/gdzie kupić/FAQ).
4. Akcept treści (auratest) PRZED deployem.

## FAZA 2 — Hero produkcyjne + wpięcie
1. Regen hero rdzenia z czystą tablicą (O3) + alty/nazwy SEO per wpis.
2. Upscale sztandarowych do ~1920 szer. (opcja, Freepik upscaler lub Pro A/B).
3. Wpięcie hero w wiki_body hubów (img WebP + fetchpriority hero + ImageObject schema).

## FAZA 3 — Blog / baza wiedzy
1. Decyzja O2 (gdzie blog, CPT/posts, RankMath).
2. **Audyt kanibalizacji GSC** PRZED publikacją (czy hub już nie rankuje na frazę wpisu).
3. Pillary: A) chińskie marki (8 780/mc) B) rankingi (najlepszy chiński SUV / 7-osobowy / EV) C) przewodniki zakupowe (import krok po kroku — nasza ekspertyza) D) porównania (vs) E) news/premiery.
4. Każdy wpis linkuje do hubów/listingów.

## FAZA 4 — Ads alignment
1. Landing: hub (research-intent) + `/samochody/?marka=X&model=Y` (performance).
2. Repoint kampanii pod rdzeń Leopard+Denza (zgodnie z popytem×podażą).
3. Hero jako kreacje (Performance Max / display) — opcja.

## FAZA 5 — Pomiar
1. GSC CTR delta na reworked hubach (7-10 dni po deploy).
2. Indexing API push (przez `~/bin/index-submit`, budżet ad-hoc 100/dobę — rezerwa dla PrimaAuto).
3. Iteracja.

---

## ZASOBY / SKRYPTY (reużywalne)
- `~/scratch/gemini-hero-test/gen-hero.py` — hero (Nano Banana 2)
- `tmp/gsc-denza-2026-05-30.py` — GSC frazy + per-hub (template)
- `tmp/dfs-domain-profile-2026-05-30.py` — profil domeny (konkurent)
- `tmp/dfs-kwsugg-paa-2026-05-30.py` — keyword volume + PAA
- `tmp/hub-wiki-generate.py` — generator wiki (direct-Sonnet)
- Stock: `stm_car_location` · Popyt: `asiaauto_order._order_listing_id`
- Klucze: Gemini `~/secrets/google/gemini-api-key.txt` · DFS `~/secrets/dataforseo/` · GSC `~/secrets/google/tokens.json`

## MEMORY (kontekst dla nowego wątku — czytać na start)
- `project_intent_gap_cena_gdzie_kupic_2026_05_30` — diagnoza + brief + struktura + reguły
- `reference_hero_image_pipeline_and_stock_demand_2026_05_30` — pipeline hero + stock + popyt
- `reference_google_seo_stack` — START sesji SEO (API/skrypty)
