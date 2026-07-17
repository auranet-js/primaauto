# ADR 2026-07-17 — /oferta/ 301, cron indexing zaległości, werdykt schemy ofert, audyt hubów „cena"

Sesja SEO/infra 2026-07-17. Kilka decyzji z konsekwencjami.

## 1. Archiwum CPT `/oferta/` → 301 na `/samochody/`

**Problem:** `/oferta/` to auto-generowane archiwum CPT `listings` (`has_archive => true`, slug `oferta`). WordPress renderuje generyczny fallback = ściana pełnych `the_content` kolejnych ofert (Denza N8L, AITO M9, Hongqi H9…) — **thin + duplicate content**, `index,follow`, w sitemap RankMath (`listings-sitemap1.xml`). Nie ma go w menu, nic wewnętrznie nie linkuje (0 linków na home/samochody/informacje/kontakt/klienci). Jedyne źródło = sitemap. Ruch znikomy: **7 odsłon/90 dni** (5 organic google, 1 direct, 1 yahoo), **0 paid** (trop DSA obalony — DSA celuje huby z etykietą `dsa2026`).

**Decyzja:** hard **301 `/oferta/` → `/samochody/`**. Kod: `class-asiaauto-seo.php::redirectListingsArchive()` na `template_redirect`, warunek `is_post_type_archive('listings')`. Wzorzec jak `blockAuthorArchive()`.

**Bezpieczeństwo:** dotyczy WYŁĄCZNIE archiwum. Pojedyncze oferty `/oferta/{slug}/` to `is_singular('listings')` — nietknięte. Smoke: `/oferta/`→301→/samochody/, `/oferta/geely-monjaro-2025-327110/`→200, `/samochody/`→200.

**Nierozwiązane (drobiazg):** `/oferta/` **zostaje w sitemap RankMath** — filter `rank_math/sitemap/entry` nie łapie archive (RankMath dodaje archive URL osobną ścieżką, poza entry-filtrem; brak flagi archive w opcjach — tylko `pt_listings_sitemap` na cały CPT). Martwy filter usunięty z kodu. Redirect 301 i tak deindeksuje; ewentualny GSC-warning „URL in sitemap redirects" = kosmetyka. Do dopięcia tylko jeśli GSC zgłosi.

## 2. Werdykt schemy ofert — NIE ruszać (Rich Results Test)

Rozważany fix schemy ofert (dodać `description` + galeria `image` zamiast 1 `$thumb`) w celu „zgniecenia" TNT rich snippetem. **Odrzucony na twardych danych:**
- **Google Rich Results Test** na naszej ofercie: wykrywa `[Product, Car]` jako **„Opisy produktów" (Product snippet)** — NIE „Vehicle listing" (ten geo-ograniczony do US). Jedyne „braki" (niekrytyczne): `review` + `aggregateRating` — **świadomie pomijane** (fake review = ryzyko manual action).
- Google **nie zgłasza** braku `description`/`image` → dodawanie ich nic nie da. Google PL renderuje dla aut Product snippet (cena + dostępność + 1 miniatura), nie carousel. Dowód empiryczny: TNT #1 na „zeekr 8x" ma 1 miniaturę, nie galerię.
- Nasza schema jest **bogatsza niż TNT** (`[Product,Car]` + mileage/fuel/engine/transmission + priceValidUntil + shipping vs ich goły Product). TNT wygrywa **authority/backlinkami** (AS 0 nasze, ~24 ich), nie schemą ani title. Realna dźwignia = **link building**.

## 3. Indexing API — wykorzystanie pustej puli (feed martwy)

Feed Dongchedi/Che168 zamrożony → auto-indexing pluginu nic nie zgłasza, pula 200/dobę stoi wolna. Dziś zgłoszone **200/200**: 100 ad-hoc (34 stock na placu/w drodze [Rzeszów+Pabianice+„W drodze do UE"] + 66 świeżych niezaindeksowanych) + 100 rezerwa (`ok_retry`). Wszystkie z dobrymi title (T-203 rework objął komplet ofert). Cron reindeksacji zaległości `ok_retry` (1097 szt.): `scripts/cron-index-retry.sh` — patrz memory `reference_cron_index_retry_temporary`.

## 4. Audyt hubów „{model} cena w polsce" — huby ZDROWE

Metodyka: **GSC = prawda o nas** (realny ruch/pozycja), DFS/Semrush = benchmark z lagiem 1-2 mies. DFS nie widzi AI Overview ani krótkich fraz → myli dla NASZYCH pozycji (monjaro DFS „brak" vs GSC poz 8,4/116 klik + cytowany w AI Overview). Wynik: **99 hubów GSC poz ≤5, 168/196 z ruchem w top10, tylko 6 realnych FIX**. „DFS #1" to często brand-globalny (.com — aitoauto/xpeng/byd), NIE konkurent PL. Realni wrogowie: west-motors (1926 KW), TNT (28, rośnie). Raporty: `auratest.pl/fe4f58fec53ctmp/primaauto-master-huby-modelowe-2026-07-17.html`. Memory: `project_audyt_huby_cena_gsc_2026_07_17`, `reference_seo_measurement_gsc_truth_dfs_semrush_lag`.

**Następny front:** link building (jedyna przewaga west/TNT) + fix 6 hubów FIX.
