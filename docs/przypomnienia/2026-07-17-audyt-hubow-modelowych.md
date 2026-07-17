# PROMPT (nowy wątek) — Audyt hubów modelowych: ranking na „{model} cena w polsce" + gap vs west-motors

**Data utworzenia:** 2026-07-17
**Projekt:** primaauto · **DB:** `host476470_wp521`, prefix `wp7j_` · **Property GSC:** `https://primaauto.com.pl/`

## Cel

Zdiagnozować i naprawić, dlaczego **huby modelowe (`/samochody/{marka}/{model}/`) niekonsekwentnie
rankują na komercyjną frazę „{model} cena w polsce"** (dokładny test Janka w incognito). Cel: każdy
model z żywymi ofertami ma być w top wyników na „{model} cena w polsce" **hubem MODELU** (nie marki),
najlepiej wyżej niż west-motors.pl.

## Punkt zapalny + recon 17.07 (DataForSEO, live SERP, location Poland)

Test: fraza „{model} cena w polsce". Wzorzec **niekonsekwentny** (NIE „popularne vs niszowe"):

| Fraza | Prima | West-motors |
|---|---|---|
| byd sealion 8 cena w polsce | **poz 2** — `/samochody/byd/sealion-8-dm-i/` (hub modelu ✓) | brak |
| geely monjaro cena w polsce | **poz 29** — `/samochody/geely/` (**hub MARKI, nie modelu!**) | poz 2 |
| deepal s07 cena w polsce | **brak w top 93** | poz 5 |
| xpeng g9 cena w polsce | **brak w top 94** | poz 21 |

Dla porównania na SAMO „{model}" (bez „cena w polsce") huby rankują OK (GSC 90d: monjaro poz 5,2/9992 impr;
sealion-8-dm-i 4,7/4551; deepal/s07 4,5/17). **Problem jest specyficzny dla intencji „cena w polsce".**

**Wnioski wstępne:**
- NIE indeksacja (huby zaindeksowane; `chery/tiggo-9` też PASS wg URL Inspection 17.07).
- NIE popularność (monjaro = gigant, leży na 29 marką).
- Sedno = **relevance/optymalizacja huba modelu pod „cena w polsce"**. Gdy hub modelu jest za słaby,
  Google podstawia hub marki (monjaro) albo pomija (s07, g9). west-motors regularnie wygrywa.

## Do zrobienia

1. **KROK 0 — co ma sealion 8, czego nie ma reszta.** Porównaj hub `/samochody/byd/sealion-8-dm-i/`
   (poz 2) z monjaro/s07/g9: title, H1, `asiaauto_wiki_body`, liczba żywych ofert, obecność dosłownego
   „cena w Polsce"/„cena w PLN" w widocznym tekście i w schema (Product/AggregateOffer `priceCurrency=PLN`).
   Hipoteza: sealion 8 ma frazę „cena/w Polsce" w title/H1/lead, reszta nie.
2. **Skala — SERP per model.** DFS `serp/google/organic/live/advanced` (**1 keyword/request** — live nie
   batchuje; dla ~302 modeli użyj `task_post` + `task_get`, saldo 17.07 = **$40,78**, patrz
   [[feedback_check_api_limits_before_batch]]). Dla każdego modelu z ofertami: pozycja Prima na
   „{model} cena w polsce", czy hub modelu czy marki, pozycja west-motors. Zbuduj tabelę.
3. **Klasyfikacja hubów:** (a) rankuje modelem w top10 (OK — wzorzec sealion 8), (b) rankuje marką/nisko
   (monjaro — hub modelu za słaby), (c) brak (s07, g9 — realny fix). Odfiltruj modele o zerowym volume
   (DFS keyword volume) — na nie nic nie zrobisz.
4. **Fix wzorcem sealion 8:** dołożyć do słabych hubów to, co ma sealion 8 (title/H1/lead z „cena w Polsce",
   AggregateOffer z PLN, „od X PLN" widoczne). NIE ruszać hubów które działają. Trzymać się reguł:
   [[reference_offer_schema_healthy_dead_schema_fn]] (huby=Product+AggregateOffer; review/rating NIE dodawać),
   [[feedback_no_scope_creep]], [[feedback_market_gap_check_thoroughly]].
5. **Gap vs west-motors** ([[reference_competitor_west_motors]]) — gdzie nas bije i czym (title/treść/linki).
6. **Sprzątanie taksonomii (poboczne):** sieroty serie `parent=0`/`count=0`/pusty wiki (np. term 6694
   „Deepal S07" parent=NULL, 0 ofert — duplikat żywego 4212 `s07`). Wykryć wszystkie, skonsolidować —
   [[project_hub_taxonomy_decisions_2026_07_07]], [[project_t019_taksonomia_merge_2026_06_19]].
7. **Indeksacja tylko jeśli** krok 2 wykryje realnie niezaindeksowane (asiaauto_indexing, quota — CLAUDE.md
   §10a, pytaj przed batchem [[feedback_ask_before_quota_burn]]).

## Definicja „zrobione"

Raport (wystaw na auratest): tabela `model × [pozycja na „cena w polsce" | hub modelu/marki | west-motors |
cena PLN? | ofert]`, lista hubów do naprawy sklasyfikowana (b)/(c), diagnoza „co ma sealion 8", plan fixu
wzorcem + gap vs west-motors.

## Narzędzia / kontekst

- **START SEO:** [[reference_google_seo_stack]] · DFS curl `~/secrets/dataforseo/` (balance $40,78)
- GSC API, DB `wp7j_` (Bash/wp-cli — MCP asiaauto usunięty)
- Powiązane: [[reference_mobile_share_and_offers_are_conversion_pages]] (equity pchać do hubów),
  [[project_make_hubs_rework_2026_06_08]], [[reference_byd_tang_aliases]] (BYD Tang=Sealion 8)
- Uwaga metod.: [[reference_gsc_striking_measurement_gotchas_2026_07_09]] (fałszywe tropy — sprawdź datę/kontekst)
