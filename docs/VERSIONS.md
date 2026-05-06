# Historia wersji asiaauto-sync

## 0.32.37 — 2026-05-06 (make-sitemap: wycięcie 15 redirected makes V61)

**Problem (zdiagnozowany przez GSC URL Inspection 54 hubów marek):** 6/54 hubów = NEUTRAL „Strona zawiera przekierowanie". `make-sitemap.xml` publikował slugi marek które robią 301 (V61_MAKE_REDIRECTS w `class-asiaauto-redirects.php`) — Google odrzucał je z indeksu jako redirect.

**Fix:** `AsiaAuto_Redirects::excludeRedirectedMakeFromSitemap()` — hook `rank_math/sitemap/entry` priority 10. Per-entry filter zwraca `false` dla term'ów `make` których slug jest kluczem w `V61_MAKE_REDIRECTS` (15 slugów). Bez ruszania DB — listings podpięte pod te termy nadal indeksowane przez `listings-sitemap*` (osobne sitemaps dla CPT `listings`).

**Verify (po `wp rankmath sitemap generate`):** make-sitemap.xml: 54 → **47 URL**. Zniknęły: `galaxy`, `great-wall`, `dongfeng-yipai`, `gac-aion-hyper`, `jetour-shanhai`, `yangwang`, `fangchengbao`, `chery-fengyun`, `gac-trumpchi`, `maextro`, `changan-qiyuan`, `beijing-off-road`, `212`, `dongfeng-fengxing`, `lotus-cars`. `polestar/` + `xingchi/` (NEUTRAL „zeskanowana, czeka na index") zostały — submit do Indexing API dla acceleracji.

**GSC URL Inspection wynik finalny dla 54 hubów make (przed fix):** 46 PASS / 2 NEUTRAL waiting / 6 NEUTRAL redirect = po fix: 47 sitemap URL, z czego 46 PASS + 2 waiting. Realnie **48 marek (54 - 6 redirected) z których 46 zaindeksowane = 96%**.

**Indexing API submitowane w sesji:** 12/200 quota:
- 10 hubów (pretendenci top10 z GSC + świeże po batch n8n + huby po merge'ach)
- 2 hub-y NEUTRAL (`polestar/`, `xingchi/`)

---

## 0.32.36 — 2026-05-06 (fix dup meta description single listing — RankMath suppression)

**Problem (zdiagnozowany live curl):** single listings `/oferta/*` emitowały **2× `<meta name="description">`** + 2× `og:type/og:title/og:description/og:image`:
- RankMath Pro: auto-extract z `post_content` → łapie chińskie znaki z importu Dongchedi (np. „Nie można wystawić faktury VAT【Stan zewnętrzny】Drobne rysy【Stan lakieru】..."). **Śmieciowa desc**.
- `class-asiaauto-single::renderMeta()`: bogata desc z marką/rokiem/paliwem/przebiegiem/ceną/USP („Import z Chin – Prima Auto"). **Lepsza dla CTR**.

**Decyzja:** zostawić `class-asiaauto-single` (bogata custom emisja), zsupressować RankMath dla single listings. Memory v0.32.0 „single nietknięte" było prawidłowe — broniło przed RM auto-extract.

**Fix:** `class-asiaauto-single::initRankMathSuppression()` — 11 filtrów zwracających `''` lub `[]` dla `is_singular('listings')`:
- `rank_math/frontend/title`, `rank_math/frontend/description`, `rank_math/frontend/robots`
- `rank_math/opengraph/facebook/og_title`, `og_description`, `og_type`, `og_image`
- `rank_math/opengraph/twitter/twitter_title`, `twitter_description`, `twitter_image`, `card_type`

**Bonus:** wyłączenie `rank_math/frontend/title` aktywuje `class-asiaauto-single::filterTitle` (wcześniej dead code) — 10 wariantowych templatów title rotuje per inner_id (`Używane {base} z Chin`, `{base} import z Chin`, `Sprowadź {base} z Chin`, etc.).

**Smoke 2/2 listingi (Zeekr 8X / BYD Sealion 8):**
- 1× `meta description` (bogata: marka, rok, paliwo, przebieg, cena, USP) ✓
- 1× `og:type=product` (nie article) ✓
- 1× `og:title/description/image` (custom template) ✓
- 1× `<title>` (template z filterTitle) ✓
- 1× `meta robots` (max-snippet/max-image-preview, brak `noindex`) ✓
- 1× `link canonical`, 1× `twitter:card` ✓

**Hand-off konwencji RankMath ↔ AsiaAuto (po v0.32.36):**
- **RankMath rządzi:** home, page (`/samochody/`, `/marki/`, `/informacje/*`), taxonomy (make, serie, body, fuel, ca-year)
- **AsiaAuto rządzi:** single listings (`/oferta/*`) — title + meta + og + twitter + Schema Car + dataLayer (RankMath supressed). Plus huby make/serie — Schema ItemList/FAQPage/BreadcrumbList (RankMath nie generuje, custom emituje równolegle do RM CollectionPage).

Backup: `class-asiaauto-single.php.bak-2026-05-06-rm-dedup`.

---

## 0.32.35 — 2026-05-06 (audyt SEO Plan A: GSC sitemap cleanup + dup desc fix + /marki/ meta)

**Audyt SEO 2026-05-06 — porównanie do baseline 2026-04-23:**

| Metryka | 2026-04-23 | 2026-05-06 |
|---|---|---|
| Indeksacja 10 URL | 1/10 PASS | **10/10 PASS** |
| GSC 30d impresje | 4 | **1282** |
| GSC 30d clicks | 0 | **98** (CTR 7.64%, avg pos 7.6) |
| Top query | brak | **"prima auto rzeszów" pos 2.4 CTR 26.1%** |

**Konflikt RankMath ↔ class-asiaauto-seo (zdiagnozowany, fixed):**
- Pierwsze meta desc na `/samochody/`: RankMath ("Tylko aktualne i sprawdzone oferty…")
- Drugie meta desc: `class-asiaauto-inventory.php::renderInventoryMeta()` linia 1732 ("Elektryki, hybrydy…") — **duplikat**
- `class-asiaauto-seo.php` ma already early return gdy `defined('RANK_MATH_VERSION')` (v0.32.0), `class-asiaauto-inventory.php` nie miał — **fix w tej wersji**

**Fix A1 — DELETE stary sitemap z GSC:** `wp-sitemap.xml` (3609 URL submitted, downloaded 2026-05-01) — duplikat z RankMath `sitemap_index.xml` (3691 URL). API DELETE → HTTP 204.

**Fix A2 — Submit 24 RankMath sitemaps do GSC:** GSC wcześniej widział tylko 4 z 18 listings sitemaps (sitemap1-4). PUT przez `webmasters/v3/sites/{site}/sitemaps/{url}` dla pełnego setu: `sitemap_index.xml` + `page-sitemap.xml` + `make-sitemap.xml` + `serie-sitemap1-2.xml` + `local-sitemap.xml` + `listings-sitemap1-18.xml`. Wszystkie 24 → 0 errors. Łącznie 3691 URL submitted, w tym 18 sitemaps × 200 listings = ~3600 (sitemap18 ma 43, sitemap17 ma 82 — końcówki).

**Fix A3 — `class-asiaauto-inventory.php::renderInventoryMeta()` early return gdy RankMath aktywny:** dodane w linii 1700 `if (defined('RANK_MATH_VERSION')) return;`. URL-e parametryczne (`?marka=X&model=Y`) i tak są noindex od v0.32.5/8 (`isInventoryPage()` + filter params whitelist). RankMath obsługuje główny `/samochody/` z `rank_math_title`/`rank_math_description` ustawionymi w admin. Backup: `class-asiaauto-inventory.php.bak-2026-05-06-rm-handoff`. Po fix smoke `/samochody/` ma desc:1 (RankMath: "Tylko aktualne i sprawdzone oferty aut z rynku chińskiego. Bezpośredni importer.").

**Fix A4 — `/marki/` (page_id 263572) brakujące rank_math_*:** `rank_math_title` = "Marki samochodów z Chin — Prima-Auto", `rank_math_description` = "Pełen katalog 50+ marek samochodów z Chin: BYD, Xiaomi, Chery, Geely, Voyah, AITO, XPeng, Zeekr i inne. Import do Polski, ceny końcowe, gwarancja." (155 chars), `rank_math_focus_keyword` = "marki samochodów z Chin". `wp post meta update 263572` × 3.

**Smoke test 5/5:** `/samochody/` desc:1 ✓ (RankMath), `/marki/` desc:1 ✓ (nowy desc + custom title), `/samochody/?marka=byd` desc:1 ✓ (RankMath), Listing Denza desc:2 (out-of-scope, do osobnej decyzji), Hub BYD/SU7 bez zmian.

**KRYTYCZNE pozostałe (osobna sesja):**
- **PSI mobile home REGRES**: perf 75 (04-23) → **39** (05-06), TBT 160ms → **3890ms** (24×!), LCP 4.9s → 6.1s. CrUX field data **wszystkie 4 metryki = POOR (F)** — Google klasyfikuje jako poor CWV → ranking penalty. Source: prawdopodobnie RankMath analytics + Complianz + asiaauto-tracking + GTM stacked w main thread. Wymaga audytu JS payloadu i defer/async refactor. Theme `primaauto2026` 1.0.4 (Elementor wycofany 2026-04-24, ale TBT regres jest inny problem).
- **Listing desc:2** — `class-asiaauto-single.php` (custom z marką/modelem/ceną/przebiegiem) konkuruje z RankMath auto-extract z post content. AsiaAuto desc lepsza SEO-wise (zawiera focus-keywordy), RankMath desc generic. Decyzja: zostawić AsiaAuto + wyłączyć RM dla CPT `listings` (filter `rank_math/frontend/description` return false dla `is_singular('listings')`) lub w admin RM disable post type.

**Top pages w GSC (30d, what's working):** `/` 469imp/56clk/CTR11.9%, `/samochody/` 147/3, `/samochody/aito/` 89/3, `/samochody/byd/` 69/3, `/marki/` 63/2, `/samochody/byd/leopard-5/` 101/2. Niche-modele rankują: "tank 300 cena w polsce" pos 6.4, "geely preface cena" pos 8.7, "aito m9", "li auto l9", "zeekr 9x 2025", "changan uni-v" pos 27 (do dopchnięcia).

## 0.32.34 — 2026-05-06 (W1+W2: prevent ghost-offer publish-then-trash churn)

**Problem:** importer publikował listingi mimo że auto-api.com zwracał już-wygasłe URL-e Dongchedi (`x-expires` < `synced_at`). Listingi w `publish` bez thumbnail → indeksowane przez Google → potem masowy cleanup przez `diag missing-images` (2026-05-03: 60 listings; rano 2026-05-06: znów 93). Strata budżetu indeksacji + churn URL-i.

**Rozwiązanie 2-warstwowe:**

**W1 — preflight w `AsiaAuto_Importer::importListing()`:**
Przed `wp_insert_post` parsuje obrazy i sprawdza `allUrlsExpired()` (nowy static helper). Jeśli WSZYSTKIE URL-e z parametrem `x-expires` są po terminie → `return null`, log warning. Cron pomija ofertę i ponowi przy następnym sync (świeżych danych z API). **Manual import (`force=true`) pomija preflight** — klient świadomie wskazał ofertę.

**W2 — guard w `AsiaAuto_Media::downloadAndStore()`:**
Gdy wszystkie pobrania zwrócą 403 (`empty($attachment_ids)`) — zamiast zostawić post w `publish` bez thumbnail, przenosi go do `draft` i zapisuje `_asiaauto_image_failure_at`. Listing nie pojawia się publicznie. `updateListing` przy kolejnym sync może go odzyskać (`downloadMissingImages` nadal próbuje).

**Manual import UI:**
`ajaxImport()` po imporcie sprawdza gallery i jeśli pusta — zwraca `data.warning` z instrukcją: „URL-e wygasły, otwórz ofertę na Dongchedi (auto-odświeży cache po stronie auto-api.com), ponów import za ~30s". JS renderuje jako `notice notice-warning`.

**Helper `AsiaAuto_Importer::allUrlsExpired(array $urls): bool`:**
- `[]` → `false` (no-op, nic do importu)
- URL-e che168 / bez `x-expires` → `false` (conservative: nie blokujemy)
- Mieszane (chociaż 1 świeży) → `false`
- Wszystkie z `x-expires` po terminie → `true`

**Pliki:** `asiaauto-sync.php` (version), `includes/class-asiaauto-importer.php` (W1 + helper), `includes/class-asiaauto-media.php` (W2), `includes/class-asiaauto-admin-manual-import.php` (UI warning + JS).

**Backupy:** `*.bak-2026-05-03-w1`, `*.bak-2026-05-03-w2`, `*.bak-2026-05-03-warning`.

**Smoke test:** `php -l` × 4 czysty. `wp eval allUrlsExpired()` dla 5 case'ów: poprawne wyniki (mixed=false, all_old=true, no_param=false, empty=false, real_dongchedi_old=true).

**Co dalej:** monitor logu po następnym cronie (`grep "API cache stale" logs/asiaauto-sync.log`) — ile ofert pominiętych. Jeśli liczba jest wysoka i nie spada przez 2-3 cykle, zbadać dlaczego auto-api.com serwuje stary cache (może tam jest TTL do podkręcenia).

## 0.32.33 — 2026-05-04 (HOTFIX: martwe linki asiaauto.pl w mailingu i umowie PDF)

W trybie autonomous przy v0.32.32 zostawiłem 7 hardcoded URL-i `https://asiaauto.pl/*` w mailach do klientów i logo URL w umowie PDF jako „TODO osobny task" — uznając że „działa bo plik istnieje na asiaauto.pl". To było błędne. Klient zwrócił uwagę: domena `asiaauto.pl` zwraca **HTTP 500 na wszystkich routach poza wąskim zakresem statycznych plików w `/2026/04/`**. Klienci dostawali maile z linkami `https://asiaauto.pl/proces/`, `/homologacja/`, `/faq/`, `/samochody/` — wszystkie 500. Umowa PDF używała LOGO_URL z asiaauto.pl który czasem dawał 200, czasem 500 (warunkowo).

**Szybka inwentaryzacja stanu asiaauto.pl (curl -I):**
- `/`, `/proces/`, `/homologacja/`, `/faq/`, `/samochody/` — **HTTP 500**
- `/wp-content/uploads/2026/04/primaauto-logo-round.png` — **HTTP 200** (jeden statyczny plik z 04/ działa)
- `/wp-content/uploads/2026/03/primaauto-logo-round.png` — **HTTP 500**

Przyczyna 500-ek na asiaauto.pl wymaga osobnej diagnozy (pewnie wp-config WP_HOME na primaauto + brak fallback dla starej domeny w .htaccess albo plugin asiaauto-sync który DB wspólną i coś failuje). **Cutover 2026-04-21 zakładał 301 na całej domenie — nie działa zgodnie z założeniem.** Do osobnego task (TODO).

**`class-asiaauto-order-content.php` (6 linków w 4 statusach maili):**
- 116-118: lista przydatnych informacji w mailu „Zamówienie przyjęte" — 3 linki:
  - `https://asiaauto.pl/proces/` → `https://primaauto.com.pl/informacje/proces-zamawiania/`
  - `https://asiaauto.pl/homologacja/` → `https://primaauto.com.pl/informacje/homologacja-i-rejestracja/`
  - `https://asiaauto.pl/faq/` (nigdy nie istniała na primaauto) → `https://primaauto.com.pl/informacje/` (parent landing dla wszystkich podstron informacyjnych)
- 159: link do procesu w mailu „Wycena": `asiaauto.pl/proces/` → `primaauto.com.pl/informacje/proces-zamawiania/`
- 350: oferty alternatywne w mailu „Niedostępny": `asiaauto.pl/samochody/` → `primaauto.com.pl/samochody/`
- 371: oferty w mailu „Anulowane": `asiaauto.pl/samochody/` → `primaauto.com.pl/samochody/`

**`class-asiaauto-contract.php` (LOGO w PDF umowy):**
- Linia 53: `LOGO_URL = 'https://asiaauto.pl/wp-content/uploads/2026/04/primaauto-logo-round.png'` → `'https://primaauto.com.pl/wp-content/uploads/2026/03/primaauto-logo-round.png'`
- Linia 290: `resolveLogoPath()` próbuje najpierw lokalny `$upload_dir/2026/04/primaauto-logo-round.png` — który **nie istnieje** (plik leży w `/2026/03/`). Path zmieniony na `/2026/03/primaauto-logo-round.png` (200 lokalnie + 200 z URL fallback).

**Smoke test (PASS):**
- `/informacje/proces-zamawiania/` 200 ✓
- `/informacje/homologacja-i-rejestracja/` 200 ✓
- `/informacje/` 200 ✓ (landing dla wszystkich info)
- `/samochody/` 200 ✓
- `/wp-content/uploads/2026/03/primaauto-logo-round.png` 200 ✓

**Sync legacy domain:** 3 pliki skopiowane do `~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/` (rutynowo, choć sama domena daje 500).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.32 → 0.32.33
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-order-content.php` — 6 linków asiaauto.pl → primaauto.com.pl
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-contract.php` — LOGO_URL + lokalny path do 2026/03/

**Diagnoza + fix asiaauto.pl 500 (przy okazji v0.32.33, infra-only):**

Przyczyna: `wp eval` na asiaauto.pl rzucał `Fatal error: Failed opening required '...wp-content/plugins/asiaauto-sync/includes/class-asiaauto-mapping.php'`. Plik powstał 2026-04-23 przy v6.1 brand-mapping (`AsiaAuto_Mapping::getEuForCn()`), ale **sync legacy do asiaauto.pl był niekompletny** — kopiowaliśmy tylko aktualnie zmieniane pliki w danej sesji, nigdy nie robiliśmy `rsync` całego plugin dir. `diff -rq` pokazał **kilkadziesiąt** plików brakujących na asiaauto.pl (admin-diag.css/js, asiaauto-tracking.js, kilka diag/, kilka data/translations + część kluczowa: `class-asiaauto-mapping.php`, `class-asiaauto-admin-diag.php`).

Fix: ZAMIAST sync wszystkiego z primaauto, zgodnie z założeniem cutover 2026-04-21 (memory: „asiaauto = uśpiona kopia z 301") → **bezwarunkowy 301 w `.htaccess` przed jakimkolwiek przetwarzaniem PHP**:

```apache
# BEGIN AsiaAuto 301 → primaauto.com.pl
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^(.*)$ https://primaauto.com.pl/$1 [R=301,L]
</IfModule>
# END AsiaAuto 301
```

Backup: `~/domains/asiaauto.pl/public_html/.htaccess.bak-2026-05-04` (oryginalny ze starym Login Hide block + WP rewrites — bez 301).

**Smoke test po fix .htaccess:**
- `https://asiaauto.pl/` → 301 → `primaauto.com.pl/` 200 ✓
- `https://asiaauto.pl/proces/` → 301 → `primaauto.com.pl/proces/` → 301 → `primaauto.com.pl/informacje/proces-zamawiania/` 200 ✓
- `https://asiaauto.pl/samochody/icar/03/` → 301 → `primaauto.com.pl/samochody/icar/03/` 200 ✓ (potwierdza migrację iCAR działa też z legacy domain)
- `https://asiaauto.pl/wp-content/uploads/2026/03/primaauto-logo-round.png` → 301 → primaauto

**Implikacja:** sync legacy do asiaauto.pl staje się zbędny — domena zwraca 301 dla wszystkiego, nie odpala WP, nie używa pluginu. Można w przyszłości całkowicie zrezygnować z kopiowania plików (sam plik `.htaccess` musi tam zostać). Pliki kopiowane w sesji v0.32.31-33 do asiaauto.pl/wp-content są bezużyteczne, ale nie szkodzą.

---

## 0.32.32 — 2026-05-04 (Galaxy → Geely merge + chinese cleanup + /kontakt/ fix)

Druga część sesji 2026-05-04 (po iCAR merge v0.32.31): scalenie residuala Galaxy do Geely, doczyszczenie chińskich znaków w 21 post_title i naprawa shortcode `[asiaauto_contact]` na stronie /kontakt/ (subject mail + 404 schema image).

**Galaxy → Geely (v6.1 residual finalized):**
- Term `Galaxy` (3394, 16 listingów) — pusty po migracji, **usunięty** (`wp term delete`). Redirect `galaxy → geely` był w V61_MAKE_REDIRECTS od 2026-04-23 — działa po usunięciu termu.
- 17 listingów: `term_relationships.term_taxonomy_id=3394` → `3626` (Geely)
- 1 seria pod parent=Galaxy: `Galaxy M9` (6550) → parent=3626 (reszta serii Galaxy* już była pod Geely)
- `wp7j_postmeta`: `make=galaxy` → `geely` (17 wpisów)
- `wp7j_termmeta`: `_asiaauto_primary_make_slug=galaxy` → `geely` (term 6550)
- `wp7j_posts.post_title`:
  1. REPLACE `'Galaxy 银河'` → `'Geely Galaxy '` (chinese cleanup + Geely prefix; 6 listingów A7 EM)
  2. REPLACE `'Galaxy Galaxy'` → `'Geely Galaxy'` (de-duplicate; 1 listing 282264 z poprzednim partial fix)
  3. CONCAT `'Geely '` przed `'Galaxy %'` gdzie nie zaczyna się od `'Geely '` (10 pozostałych)
- `wp term recount`: Geely 341 → 357 (+16 publish, 17 minus 1 draft)

**Brand-mapping bez zmian:** wszystkie 12 wpisów `Galaxy|*` już mapowały na `mark_eu='Geely'` od v6.1 (importer importował nowe listingi pod Geely; tylko stare pod taxonomy Galaxy zostały do dziś).

**Chinese cleanup (translations-complectations.php — 15 nowych mapowań):**

Dodano sekcję `// === 2026-05-04 — chinese-fragments cleanup po Galaxy/iCAR merge (15 nowych) ===`:
- 巅峰性能 → Peak Performance (iCAR Super V23 V23S, listing 299535)
- 大家庭欢乐 → Family Joy (Jetour X90 PLUS)
- 星辉 → Starlight (MAEXTRO S800)
- 锦绣 → Splendid (Geely Atlas Pro)
- 启航 → Voyager (Geely Galaxy E5)
- 凌云 → Soaring (Exeed TXL)
- 智慧 → Smart (Changan CS75 Plus)
- 威赫 → Mighty (Xingchi Bochi Venus)
- 出行 → Mobility (Hongqi E-QM5)
- 公务 → Official (Geely Galaxy E5)
- 高功 → High Power (Geely Monjaro)
- 霄汉 → Skyward (Geely Monjaro)
- 乘势 → Momentum (Chery Tiggo 8 PLUS)
- 电 → Electric (Jetour Shanhai L7 PLUS)
- 星 → Star (Exeed TXL — single char na końcu mapy, longest-first PHP str_replace iteruje by-array-order więc 星舰/星耀/星辉/星空龙耀/星月女神 zamienione przed)

**APPLY `diag/fix-chinese-v23.php`:** 15 listings zaktualizowanych. Po: `SELECT COUNT(*) WHERE post_title REGEXP '[一-龥]'` = **0** (ZERO chińskich w post_title publish). Galaxy listings z chińskim 银河 obsłużone wcześniej w SQL transaction Galaxy migration (REPLACE 'Galaxy 银河' → 'Geely Galaxy ').

**Shortcode `[asiaauto_contact]` na /kontakt/ (`class-asiaauto-contact.php`):**
- Linia 127: `'image' => home_url('/wp-content/themes/asiaauto/assets/asia-auto-logo.png')` → `home_url('/wp-content/uploads/2026/03/primaauto-logo-round.png')`. Plik `asia-auto-logo.png` w themes/asiaauto/assets/ **nie istniał** (404) — schema.org/AutoDealer image był broken. Nowy URL → 200.
- Linia 306: subject mailto `'Zapytanie ze strony asiaauto.pl'` → `'Zapytanie ze strony primaauto.com.pl'`. To było user-facing (klient widział temat w mailu od użytkownika). User nie mógł poprawić bo treść strony to shortcode z PHP.

**Smoke test (PASS):**
- `/samochody/galaxy/` → 301 → `/samochody/geely/` 200 ✓
- `/samochody/galaxy/galaxy-m9/` → 301 → `/samochody/geely/galaxy-m9/` 200 ✓
- `/samochody/geely/a7-em/` 200 ✓ (16+ listingów A7 EM)
- `/kontakt/` 200 ✓ — schema image primaauto-logo-round.png, mailto subject „Zapytanie ze strony primaauto.com.pl"
- 0 listings publish z chińskimi znakami w post_title

**Backup DB:** `~/backups/primaauto/2026-05-04-galaxy-merge/terms-and-posts.sql` (8.4 MB, 4 tabele).

**Sync legacy domain:** 3 pliki skopiowane do `~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/`.

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.31 → 0.32.32
- `wp-content/plugins/asiaauto-sync/data/translations-complectations.php` — 15 nowych mapowań
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-contact.php` — schema image URL fix + mailto subject

**Pozostałe odwołania `asiaauto.pl` w kodzie (NIE naprawione w tej sesji — poza scope dziś):**
- `class-asiaauto-contract.php:53` — `LOGO_URL = 'https://asiaauto.pl/wp-content/uploads/2026/04/primaauto-logo-round.png'` (działa bo plik istnieje na asiaauto.pl, ale powinno wskazywać na primaauto.com.pl)
- `class-asiaauto-order-content.php:116-118, 159, 350, 371` — emaile statusów zamówień zawierają linki `https://asiaauto.pl/proces/`, `/homologacja/`, `/faq/`, `/samochody/` (user-facing — TODO osobny task)

---

## 0.32.31 — 2026-05-04 (iCAR merge: 03/03T/V27 z Chery do iCAR)

Klient zgłosił 2026-05-04: iCAR ma być wyłącznie marką, modele iCAR widniejące pod Chery (iCAR 03, iCAR 03T, iCAR V27) trzeba przerzucić pod główną markę iCAR. Stan przed migracją był niespójny: brand-mapping v6.1 mapował `iCAR Super V23` na markę iCAR, ale `iCAR 03` i `iCAR V27` na Chery; redirect `icar → chery` w V61_MAKE_REDIRECTS sprzeczny ze stanem (term marki iCAR istniał z 9 listingami). Plus orphan `iCAR 03T` (term 5519, parent=0) bez wpisu w mapping.

**Migracja DB live (2026-05-04 ~17:05):**
- `wp7j_terms`: rename serie 5518 (iCAR 03 → 03 / `03`), 5519 (iCAR 03T → 03T / `03t`), 6508 (iCAR V27 → V27 / `v27`). Naming bez prefiksu — wzorzec spójny z istniejącą serią V23 (term 5517, sam numer/oznaczenie, prefix marki tylko w post_title)
- `wp7j_term_taxonomy`: serie 5517/5518/5519/6508 → `parent=5516` (iCAR). Przy okazji fix orphana 5517 V23 (był parent=0) i 5519 (był parent=0)
- `wp7j_term_relationships`: 12 wpisów `term_taxonomy_id=3578 (Chery)` → `5516 (iCAR)` dla listingów: 245892, 249700, 249717, 250800, 259268, 265157, 267536, 271571, 273041, 287720, 287730, 291872 (287534 już miał make=iCAR)
- `wp7j_postmeta`: 7× klucz `make=chery` → `icar` (część listingów miała już `icar` w postmeta — niespójność postmeta vs taxonomy uleczona)
- `wp7j_termmeta`: `_asiaauto_primary_make_slug=chery` → `icar` dla 5518 i 6508 (5519 i 5517 już miały `icar`)
- `wp7j_posts`: REPLACE `'Chery iCAR'` → `'iCAR'` w post_title, 11 listingów (287534 już bez prefiksu)
- `wp term recount make serie` — count: Chery 132 → 124 (-8 publish), iCAR 9 → 17 (+8 publish), suma 141 = 141 ✓

**Brand-mapping uzupełniony** (`data/brand-mapping-v6.1.php`):
- `'iCAR|iCAR 03'` → mark_eu=`iCAR` (zmiana z Chery), serie_eu=`03`, title_eu=`iCAR 03`, slug=`03`
- `'iCAR|iCAR 03T'` → **nowy wpis** (rozwiązuje orphan), mark_eu=`iCAR`, serie_eu=`03T`, slug=`03t`
- `'iCAR|iCAR V27'` → mark_eu=`iCAR` (zmiana z Chery), serie_eu=`V27`, slug=`v27`

Bez tego importer przy reimporcie cofnąłby zmiany (wrzucał iCAR 03/V27 z powrotem pod Chery i nie tworzyłby relacji dla 03T).

**Redirects (`class-asiaauto-redirects.php`):**
- **USUNIĘTO** `'icar' => 'chery'` z `V61_MAKE_REDIRECTS` (niespójność: marka iCAR istnieje, redirect ją zabijał)
- **DODANO** nową stałą `V63_MAKE_SERIE_REDIRECTS` (mapa `[old_make][old_serie] => [new_make, new_serie]`) + metodę `redirectV63MakeSerieMoves()` na `template_redirect` priorytet 0. Mapa: `chery/icar-03 → icar/03`, `chery/icar-03t → icar/03t`, `chery/icar-v27 → icar/v27`. Mechanizm rozszerzalny — następne migracje modeli między markami w jednym miejscu.

**Smoke test (PASS):**
- `/samochody/icar/` 200 (17 listingów)
- `/samochody/icar/03/`, `/icar/03t/`, `/icar/v27/`, `/icar/v23/` 200
- `/samochody/chery/icar-03/` → 301 → `/samochody/icar/03/` 200 ✓
- `/samochody/chery/icar-03t/` → 301 → `/samochody/icar/03t/` 200 ✓
- `/samochody/chery/icar-v27/` → 301 → `/samochody/icar/v27/` 200 ✓
- `/samochody/chery/` 200 (124 listingów, bez iCAR)

**Backup DB:** `~/backups/primaauto/2026-05-04-icar-merge/terms-full.sql` (8.4 MB, 4 tabele: terms/term_taxonomy/term_relationships/termmeta).

**Sync legacy domain:** 3 pliki skopiowane też do `~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/` (legacy 301-redirect na primaauto, ale wp-content musi być spójne — wspólna DB).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.30 → 0.32.31
- `wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php` — 3 wpisy iCAR (mark_eu zmiana + nowy 03T)
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-redirects.php` — usunięty `icar→chery`, dodany `V63_MAKE_SERIE_REDIRECTS` + `redirectV63MakeSerieMoves()`

---

## 0.32.30 — 2026-05-01 (Fix mocy KM dla PHEV — single + karty inventory)

Klient zgłosił 2026-04-30: PHEV-y pokazują absurdalnie niskie liczby KM (BYD Han DM-i 156 zamiast 272, Denza Z9 DM-i 207 zamiast 870, Sealion 8 DM-p 4WD 156 zamiast 544). Diagnoza pełna w `docs/QUEUE.md` ZADANIE 15: dwa renderery (`class-asiaauto-inventory.php::parseSystemPower`, `class-asiaauto-single.php::power`) opierały się na `energy_elect_max_power` (niespójne dla PHEV) z fallbackiem do meta `_asiaauto_horse_power` (= moc samego silnika spalinowego).

**Wdrożenie:**
- Nowy `public static AsiaAuto_Inventory::resolvePower(int $post_id, array $ep): array` — fuel-aware: dla PHEV/EREV/HEV/EV używa `front_electric_max_horsepower` + `total_electric_power` (wiarygodne 99,8% PHEV w bazie). Edge case PHEV: gdy ICE dominuje (`engine_kw * 1.5 > total_kw`, np. Volvo S90 T8) → pokaż combined `engine_kw + total_kw`. Dla benzyny/diesla — `engine_max_horsepower` + `engine_max_power`. Meta `_asiaauto_horse_power` jako ostateczny fallback **tylko dla ICE** (dla PHEV nadal zawiera ICE-only HP, ale nie jest dla nich brany pod uwagę).
- `class-asiaauto-inventory.php` — karta listings woła `resolvePower($postId, $ep)` zamiast `parseSystemPower($ep)`. Stary helper zostawiony jako `@deprecated` (nieużywany).
- `class-asiaauto-single.php::power($ep, $post_id = null)` — cienki wrapper na `AsiaAuto_Inventory::resolvePower()`. Etykieta nad mocą zmieniona z „Moc łączna" na „Moc" (dynamicznie z `$pw['label']`). Caller w `wp_head` (linia 687) dostaje `$pid` jawnie — `get_the_ID()` bywa nullem przed The Loop.

**Weryfikacja klienta (2026-05-01):** wszystkie 8 testów z planu PASS — Han DM-i, Z9 DM-i Ultra, N9 DM-i Premium, Leopard 7 PHEV, AITO M7 EREV, Volvo S90 T8 (combined), Z9 GT EV (bez regresji), benzynowiec (bez regresji).

**Czego NIE ruszono:** importer (`_asiaauto_horse_power` celowo zapisuje ICE HP — zostaje), `class-asiaauto-shortcodes.php::resolvePower()` (wzorcowa logika — nieaktywna w motywie primaauto2026, pozostaje na osobny refactor konsolidujący).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.29 → 0.32.30
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-inventory.php` — `resolvePower()` static helper + podmiana w karcie listings
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-single.php` — `power()` jako wrapper, etykieta „Moc", `$pid` jawny w wp_head schema

---

## 0.32.29 — 2026-04-30 (Mobile single redesign — sticky title bar pod headerem strony)

Single listing przebudowany na mobile: title + brutto/netto przyklejone u góry pod headerem strony, gallery tuż pod, "Informacje" (linki do podstron) zjechały na sam dół pod inne moduły, breadcrumb i top „Wróć do wyników" ukryte na mobile (BreadcrumbList JSON-LD nadal w `<head>`).

**Architektura sticky head — dwa warianty (desktop sidebar + mobile poza layoutem):**
- Wyciągnięty `stickyHead($d, $variant)` z `sidebar()`. Renderowany 2× z modyfikatorami `--desktop` (w `<aside>`) i `--mobile` (poza `<div class="aa-single__layout">`, jako sibling).
- Variant `--mobile` ma `position: sticky; top: var(--header-h, 70px); z-index: 90` — przykleja się POD `.pa-header` (z-index 100). Backup-y atemptów: position: fixed (porzucony — wymagał dynamicznego JS measure margin-top), display: contents na sidebar (porzucony — niestabilne w grid + sticky cascade).
- Negatywny margin-top `-16px` niweluje `--space-2` padding na `.pa-main` na mobile — title bar bez gapu po site headerze.
- Mobile sticky-back arrow (←) jako lewa kolumna grid sticky head; na desktop hidden (`display: none`).
- `aa-info--desktop` (w sidebar) vs `aa-info--mobile` (na końcu main) — info linki widoczne tylko w odpowiednim viewporcie.
- `aa-sep--desktop`, `aa-single__updated--desktop` — dodatkowe dekoracje sidebar ukryte na mobile.

**iOS Safari fix mobile CTA (3 buttons):** `position: fixed; bottom: 0` ląduje pod toolbarem Safari. JS `visualViewport` API liczy gap między layout a visual viewport i ustawia `bottom: <gap>px` żeby CTA podążał za widocznym dołem ekranu. Resize/scroll listenery.

**iOS Safari fix dolnego CTA (`asiaauto-single.js`):**
```js
var bottomGap = window.innerHeight - (vv.height + vv.offsetTop);
mobileCta.style.bottom = bottomGap > 0 ? bottomGap + 'px' : '';
```

**Asset versioning:** `wp_enqueue_style/script` dla `aa-single` przerzucone z hardcoded `'0.25.0'` na `filemtime()` z fallback do `ASIAAUTO_VERSION` — automatyczne cache-busting przy edytach CSS/JS.

**`wp_kses` fix w `taxonomy-make.php` + `taxonomy-serie.php` (theme-side):**
Sanitizer `wp_kses()`/`wp_kses_post()` na zmergowanej zawartości `wiki + bar` strip'ował `<img fetchpriority="high" decoding="async" />` i `<svg>` z attrybutami nieuwzględnionymi w domyślnym allowlist. Skutek: niedomknięte `</div>` w kartach + zagnieżdżone `<a>` w `<a>` → karuzela "Najnowsze oferty" w hubach renderowała się jako wysokie kolumny pionowe (browser parse'ował broken HTML). Fix: split `$wiki` przez placeholder `{{LISTINGS_BAR}}`, sanityzowanie tylko user-side wiki content, `$bar` (nasz zaufany hub-listings HTML) wstawiany RAW.

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.28 → 0.32.29
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-single.php` — extracted `stickyHead()` + `infoBox()`, dwa renderingi w `render()`, filemtime cache busting
- `wp-content/plugins/asiaauto-sync/assets/css/asiaauto-single.css` — sekcja `@media (max-width: 768px)` z sticky head variants, hide breadcrumb/back, mobile info-box
- `wp-content/plugins/asiaauto-sync/assets/js/asiaauto-single.js` — visualViewport listener dla iOS Safari mobile CTA fix
- `themes/primaauto2026/taxonomy-make.php` + `taxonomy-serie.php` — split `$wiki/$bar` przy `wp_kses` (rozwiązanie karuzeli rozjechanej)
- `themes/primaauto2026/assets/css/footer.css` — mobile centered brand col + social icons
- `themes/primaauto2026/assets/css/hub.css` — `.aa-container { padding: 0 12px }` zamiast `0`, listing slider `flex-direction: row !important; flex-wrap: nowrap !important; flex: 0 0 70vw` na mobile, `aa-home__section-header { flex-wrap: wrap }`
- `themes/primaauto2026/assets/css/header.css` — `.pa-header { z-index: 9000 }` (było 100) — fix problemu zasłaniania mobile menu hamburgera przez sticky inventory toolbar
- `themes/primaauto2026/functions.php` — bump `PRIMAAUTO_THEME_VERSION` 1.0.4 → 1.0.6

**Smoke test:** `/oferta/<slug>/`, `/marki/`, `/samochody/<make>/`, `/samochody/<make>/<serie>/`, `/samochody/` → 200; rendered HTML zawiera oba sticky head variants, info-mobile + info-desktop, karty z `<img fetchpriority>` i zamkniętymi tagami.

**Rollback:** wszystkie pliki z .bak-2026-04-29-mobilesingle (plus header.css, hub.css, taxonomy-*.php, footer.css w temacie).

## 0.32.28 — 2026-04-29 (Cleanup serii pod GAC — usunięcie prefiksu „Trumpchi" z 4/7 modeli)

Po scaleniu marki (v0.32.27) seryjne kosmetyczne czyszczenie nazw 7 serii pod GAC z prefixem „Trumpchi" (E8, E8 PHEV, ES9 PHEV, E9 PHEV, M6, S7, M8). 4 udało się przepisać do czystych slugów; 3 zostały — kolizje slugów w taksonomii `serie` (globalnie unique w WP od 4.2):

**Zmienione (4 termy):**
- `Trumpchi E8` (3392) → `E8`, slug `e8`
- `Trumpchi E8 PHEV` (3389) → `E8 PHEV`, slug `e8-phev`
- `Trumpchi E9 PHEV` (3383) → `E9 PHEV`, slug `e9-phev`
- `Trumpchi ES9 PHEV` (3384) → `ES9 PHEV`, slug `es9-phev`

**Pozostawione (3 termy):**
- `Trumpchi M6` (3377) — kolizja z term 6557 (M6, parent=0, AITO orphan, 1 listing 283901)
- `Trumpchi S7` (3373) — kolizja z term 5674 (Luxeed S7, parent=6527, 10 listingów)
- `Trumpchi M8` (3381) — kolizja z term 3372 (GAC M8 zwykły, parent=6525, 53 listingi). Faktycznie inny model (Xiangwang M8 = top trim).

**Post_title batch (REPLACE w bezpiecznej kolejności od najdłuższego):**
1. `GAC Trumpchi ES9 PHEV ` → `GAC ES9 PHEV ` (3 wpisy)
2. `GAC Trumpchi E9 PHEV ` → `GAC E9 PHEV ` (5)
3. `GAC Trumpchi E8 PHEV ` → `GAC E8 PHEV ` (8)
4. `GAC Trumpchi E8 ` → `GAC E8 ` (4)
5. `GAC Trumpchi M8 ` → `GAC M8 ` (2 — historyczny listing pod term 3372 GAC M8 z błędnym title po v6.1; bonus cleanup; nie zmienia „Xiangwang M8")
6. `GAC Trumpchi Empow ` → `GAC Empow ` (1 — bonus cleanup)

Łącznie 23 listingi z post_title zaktualizowane.

**Brand-mapping (`data/brand-mapping-v6.1.php`):** 4 wpisy (`GAC Trumpchi|Trumpchi E8`, `…E8 PHEV`, `…ES9 PHEV`, `…E9 PHEV`) — `serie_eu`, `title_eu`, `slug` zmienione z prefiksowanych na czyste (E8/E8 PHEV/...). Inne wpisy GAC Trumpchi nietknięte (Xiangwang M8/S7 zachowują pełną nazwę CN, M6 z prefixem).

**Redirecty 301 (`class-asiaauto-redirects.php::V62_SERIE_REDIRECTS`):** dodana sekcja `'gac' => [...]` z 4 mapowaniami starych slugów (`trumpchi-e8` itd. → `e8` itd.). Łącznie z istniejącymi `byd`/`zeekr`/`voyah`/`dongfeng` jeden wspólny mechanizm dla orphan-fix duplicate slug redirects.

**Smoke test (curl):**
- `/samochody/gac/e8/`, `/e8-phev/`, `/e9-phev/`, `/es9-phev/` → 200
- `/samochody/gac/trumpchi-e8/` itd. → 301 → odpowiednio czysty slug
- `/samochody/gac/trumpchi-m6/`, `/trumpchi-s7/`, `/trumpchi-m8/` → 200 (zachowane)

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.27 → 0.32.28
- `wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php` — 4 wpisy zaktualizowane
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-redirects.php` — `V62_SERIE_REDIRECTS['gac']` (+6 linii)
- legacy `~/domains/asiaauto.pl/...` — sync 3 plików

**Pending (kosmetyka, niski priorytet):**
- `Trumpchi M6` cleanup wymagałby usunięcia/przeniesienia term 6557 (orphan AITO M6, parent=0, listing 283901 → powinien być pod właściwym AITO term). To by też naprawiło inny orphan z raportu 2026-04-28.
- `Trumpchi S7` cleanup wymagałby zmiany slug Luxeed S7 (term 5674) — nieproporcjonalne ryzyko dla SEO Luxeed.
- `Trumpchi M8` (Xiangwang M8) — nazwa słusznie zachowana, top trim ≠ podstawowa M8.

## 0.32.27 — 2026-04-29 (GAC Trumpchi → GAC merge — domknięcie residuals v6.1)

Domknięcie świadomie zostawionego residuum z v6.1 (2026-04-23): marka `GAC Trumpchi` (term_id 3368, 11 listingów) była utrzymywana obok `GAC` (6525), co generowało dublujące się prefiksy w post_title („GAC Trumpchi Trumpchi M6"). Po raporcie orphan-fix z 2026-04-28 i diagnozie obecnej sesji decyzja: zmergować w jedną markę GAC.

**Migracja DB (live na produkcji + legacy asiaauto.pl plik plugin):**
1. **Reparent serii** — `Trumpchi M6` (3377) i `Trumpchi E9 PHEV` (3383) zmienione `parent` z 3368 na 6525 w `wp7j_term_taxonomy`.
2. **Reparent listingów** — 12 wpisów w `wp7j_term_relationships` z `term_taxonomy_id=3368` przeniesione na 6525. Zero kolizji (żaden listing nie miał już GAC).
3. **Postmeta cleanup** — `meta_key=make` z wartością `gac-trumpchi` → `gac` (67 listingów; legacy meta nieużywane przez plugin/theme, ale spójne z taksonomią).
4. **Termmeta cleanup** — `_asiaauto_primary_make_slug` na seriach 3377 i 3383: `gac-trumpchi` → `gac`.
5. **Post_title batch update** — `REPLACE('GAC Trumpchi Trumpchi', 'GAC Trumpchi')` w `post_title` dla `post_type='listings'`. 13 listingów (12 z mojej listy + 1 historyczny ID 239842 „GAC Trumpchi Trumpchi M8" → „GAC Trumpchi M8").
6. **Recount** — `wp_update_term_count_now()` dla terms 6525, 3377, 3383. GAC: 112 → 123 (publish only; 134 łącznie w relationships).
7. **Usunięcie term 3368** — `wp term delete make 3368` (kasuje też termmeta: rank_math_*, asiaauto_wiki_body, asiaauto_seo_desc, asiaauto_faq_json, _asiaauto_desc_*).
8. **Brand-mapping uzupełniony** — `data/brand-mapping-v6.1.php` dopisane wpisy `'GAC Trumpchi|Trumpchi M6'` i `'GAC Trumpchi|Trumpchi E9 PHEV'` → `mark_eu=GAC`, zachowujące prefix „Trumpchi" w `serie_eu`/`title_eu`/`slug` (spójnie z istniejącymi wpisami E8/Xiangwang M8/S7/E8 PHEV/ES9 PHEV). Bez tego importer przy reimporcie wracałby do tworzenia term 3368 ponownie.

**Redirect 301 już istniał** (`V61_MAKE_REDIRECTS` w `class-asiaauto-redirects.php:37`): `gac-trumpchi → gac`. `/samochody/gac-trumpchi/*` → `/samochody/gac/*` ✓.

**Smoke test (curl):**
- `/samochody/gac/trumpchi-m6/` → 200
- `/samochody/gac/trumpchi-e9-phev/` → 200
- `/samochody/gac-trumpchi/trumpchi-m6/` → 301 → `/samochody/gac/trumpchi-m6`
- `/samochody/gac-trumpchi/` → 301 → `/samochody/gac`
- `/samochody/gac/` → 200

**Backup DB:** `~/backups/primaauto/2026-04-29-gac-trumpchi-merge/terms-full.sql` (8.4 MB — wp7j_terms, term_taxonomy, term_relationships, termmeta).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.26 → 0.32.27 (header + ASIAAUTO_VERSION).
- `wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php` — +14 linii (2 wpisy).
- `domains/asiaauto.pl/.../asiaauto-sync.php` + `brand-mapping-v6.1.php` — sync legacy (rollback).

**Co odpada w przyszłych sesjach:** raport orphan-fix `tmp/missing-hubs-2026-04-28.md` linie z Trumpchi M6/E9 PHEV — już rozwiązane (pod prawidłowym parent).

**Pending kosmetyka (nie blokuje):** serie pod GAC z prefixem „Trumpchi" w nazwie (`Trumpchi E8`, `Trumpchi M8`, `Trumpchi S7`, `Trumpchi E8 PHEV`, `Trumpchi ES9 PHEV`, `Trumpchi M6`, `Trumpchi E9 PHEV`) — można w v6.2 wyczyścić do `E8`, `M8` itd. Wymagałoby: rename term name (zachowując slug), batch update post_title, regen sitemap.

## 0.32.26 — 2026-04-29 (Social media — sameAs schema + ikony w stopce)

W sesji konfiguracji wizytówki Google Business Profile dodano profile social media (FB / IG / TT) na stronie:

1. **`sameAs` w `AutoDealer` schema na home** (`class-asiaauto-homepage.php::renderSchema`) — array z 3 URL:
   - `https://www.facebook.com/prima1auto/`
   - `https://www.instagram.com/prima_auto.pl/`
   - `https://www.tiktok.com/@primaauto.pl`
2. **`sameAs` w `LocalBusiness` schema na /kontakt/** (`class-asiaauto-contact.php::renderSchema`) — te same 3 URL.
3. **Ikony social w stopce theme primaauto2026** (`themes/primaauto2026/footer.php`) — pod `<p class="pa-footer__about">` w kolumnie brand. Inline SVG (FB/IG/TT), `target="_blank" rel="noopener nofollow"`. Lista `<ul class="pa-footer__social">` z aria-label.
4. **CSS w `themes/primaauto2026/assets/css/footer.css`** — `.pa-footer__social` (flex gap 10px), kółko 38×38 z `rgba(255,255,255,.08)`, hover na `var(--c-secondary)` z translateY(-1px).

**Backupy:** `class-asiaauto-homepage.php.bak-2026-04-29-social`, `class-asiaauto-contact.php.bak-2026-04-29-social`, `footer.php.bak-2026-04-29-social`, `footer.css.bak-2026-04-29-social`.

**Smoke test:** `curl https://primaauto.com.pl/` + `/kontakt/` — `sameAs` w obu schemach ✓, footer renderuje ikony ✓ (200 OK).

**Dlaczego ważne:** `sameAs` to oficjalny sygnał Schema.org dla Google Knowledge Graph — łączy wizytówkę GBP, profile social i stronę w jeden węzeł tożsamości firmy. Bez `sameAs` Knowledge Panel zostaje pusty (brak boxa „Profile") i Google ma trudność potwierdzić, że profil FB/IG i strona to ten sam podmiot. Wzmacnia E-E-A-T.

## 0.32.25 — 2026-04-29 (Schema NAP fix — AutoDealer name + usunięty numberOfEmployees bug)

Audyt przed wizytówką Google ujawnił dwa problemy w schema na home (`class-asiaauto-homepage.php::renderSchema`):

1. **Niespójność NAP:** `AutoDealer.name = "Prima Auto"` (bez myślnika) vs `LocalBusiness.name = "Prima-Auto"` na `/kontakt/` (z myślnikiem). Google bierze niespójność NAP jako negatywny sygnał Local SEO. Fix: ujednolicenie do `"Prima-Auto"` (zgodnie z legalName "Prima-Auto Ruslan Prima"). Dotyczy też `WebSite.name` ("Prima Auto — Samochody z Chin" → "Prima-Auto — Samochody z Chin").
2. **Bug `numberOfEmployees`:** `numberOfEmployees: { value: 2677, unitText: "vehicles in stock" }` — pole Schema.org `numberOfEmployees` opisuje LICZBĘ PRACOWNIKÓW (oczekiwany QuantitativeValue z liczbą osób), nie samochodów. Google validator może zignorować cały blok lub flaguje inconsistency. Fix: pole usunięte. Stock count i tak jest pokazywany przez `Product/AggregateOffer` per hub modelu (v0.32.23) — nie potrzebujemy go w org schema.

**Backup:** `class-asiaauto-homepage.php.bak-2026-04-29-schema-nap`.

**Smoke test home:**
- Przed: `"name": "Prima Auto"` + `numberOfEmployees: 2677`
- Po: `"name": "Prima-Auto"` + brak numberOfEmployees ✓

**Dlaczego ważne:** w sesji wizytówki Google (Google Business Profile) kluczowe jest aby NAP na stronie 1:1 zgadzało się z nazwą GBP i wizytówką w Knowledge Panel. Niespójność typu "Prima Auto" vs "Prima-Auto" działa jak dwa różne podmioty dla algorytmu Local SEO — utrudnia łączenie sygnałów.

## 0.32.24 — 2026-04-29 (Single listing — sekcja „Inne modele tej samej marki")

- **Internal linking single → hub modelu** (TODO #3 z planu SEO 2026-04-29). Single listing wcześniej linkował do hubów tylko przez breadcrumb i CTA „Wróć do wyników" — brak dedicated cross-link do sibling modeli tej samej marki.
- **Nowa metoda `relatedModels(array $d)`** w `class-asiaauto-single.php`:
  - Pobiera make_term i serie_term listingu przez `wp_get_object_terms`.
  - `get_terms` po taxonomy=serie z parent=make.term_id, exclude=[serie.term_id], hide_empty=true, orderby=count DESC, limit=8.
  - Render: `<section class="aa-related">` z grid kafelek (auto-fill, min 180px). Każdy kafelek = nazwa modelu (display_name termmeta lub fallback name) + count z polską odmianą („27 ofert", „2 oferty", „1 oferta").
  - Link do `get_term_link($sibling)` = hub modelu.
- **Wstawione w `render()`** po `[asiaauto_equipment]`, przed zamknięciem `aa-single__main`. Mobile sticky CTA (`mobileCta`) nadal na końcu.
- **CSS** w `assets/css/asiaauto-single.css`: `.aa-related` (margin-top 32px, separator border-top), `.aa-related__grid` (CSS grid auto-fill), `.aa-related__item` (border 1px, hover translateY+border-accent).
- **Smoke test:** `/oferta/byd-han-dm-2025-96111/` (BYD Han DM-i):
  - Tytuł: „Inne modele BYD" ✓
  - Wyklucza Han DM-i (serie listingu) ✓
  - 8 modeli sortowanych count DESC: Tang DM-i (37), Seal 6 DM-i (37), Song Pro DM-i (29), Qin L DM-i (25), Song L DM-i (21), Song L EV (19), Seal U DM-I (17), Atto 2 (16)
  - Leopard 3 (15, pozycja 9 w DB) odcięty przez limit ✓
- **Dlaczego ważne:** każde single listing daje 8 nowych internal links do hubów modeli tej samej marki. Skala: ~1841 listings × 8 = ~14k nowych internal linków po stronie. To wzmacnia hub authority i daje user-flow „BYD Han DM-i → Tang DM-i" zamiast „BYD Han DM-i → /samochody/" (utrata kontekstu marki).
- **Backupy:** `class-asiaauto-single.php.bak-2026-04-29-related`, `asiaauto-single.css.bak-2026-04-29-related`.

## 0.32.23 — 2026-04-29 (SEO: Product/AggregateOffer hub modelu + lifecycle 301 sprzedanych)

Dwa wins z planu SEO (audyt 2026-04-29 popołudnie):

### #1 Product + AggregateOffer schema na hub modelu (`class-asiaauto-seo.php`)

- **Nowa metoda `buildProductForSerieHub($ctx)`** — emituje `@type: Product` z `offers: AggregateOffer { lowPrice, highPrice, offerCount, priceCurrency, availability }` dla huba modelu (`is_hub === 'serie'`). Hub marki celowo pominięty (zbyt szeroka grupa, niska trafność dla Product Snippet).
- **Nowa `getPriceStatsForTerm(WP_Term $term)`** — single SQL query (JOIN posts × postmeta `price` × term_relationships × term_taxonomy) liczy MIN/MAX/COUNT po wszystkich publish listingach w danym serie term. Filtruje `price > 0`.
- **Wstawione do `renderSchema()`** w obu gałęziach (RankMath ON / OFF).
- **Smoke test:** `/samochody/byd/leopard-8/` → schema dokładnie zgodne z DB:
  - DB: `lowPrice=283000, highPrice=325000, offerCount=5`
  - HTML schema: identyczne wartości ✓
- **Dlaczego ważne:** Google Vehicle Search i Product Snippet wyciągają „od X PLN" z `lowPrice` w `AggregateOffer`. Każdy hub modelu z N>0 listingów dostaje rich result.

### #2 Lifecycle 301 sprzedanych listings → hub modelu (`class-asiaauto-redirects.php`)

- **Modyfikacja `detectListingNotFound()`** — przed dotychczasowym fallbackiem (`is_listing_404 = true` + static make context dla shortcode `[asiaauto_404_listing]`) próbuje `resolveHubUrlForListing($post_id)` i robi `wp_safe_redirect($hub_url, 301); exit;`.
- **Nowa `resolveHubUrlForListing($post_id)`** — preferowany hub modelu (taxonomy=serie), fallback hub marki (taxonomy=make). `wp_get_object_terms` zwraca terms niezależnie od post_status, więc działa dla draft i trash do permanent delete (~30 dni po sprzedaży).
- **Smoke test:**
  - `/oferta/byd-song-l-ev-2025-100886/` (draft) → 301 → `/samochody/byd/song-l-ev/` (200) ✓
  - `/oferta/zeekr-001-2025-108296/` (draft) → 301 → `/samochody/zeekr/001/` (200) ✓
  - `/oferta/nieistniejacy-slug-2024-99999999/` (deleted) → 404 ✓ (poprawny fallback gdy post nie istnieje)
- **Dlaczego ważne:** sprzedane listingi (publish→draft→trash w `class-asiaauto-rotation.php`) wcześniej dawały soft 404 w GSC i traciły equity z backlinków/historycznego rankingu. Teraz 301 do hub modelu kumuluje sygnały SEO na hubach, które są właściwym targetem dla brand+model queries.

### Backupy
- `class-asiaauto-seo.php.bak-2026-04-29-aggoffer`
- `class-asiaauto-redirects.php.bak-2026-04-29-301hub`

## 0.32.22 — 2026-04-29 (Single listing — netto pod brutto, regresja po migracji theme z Elementor)

- **Przywrócenie linii „netto: X PLN"** pod ceną brutto na single listing. Regresja z 2026-04-24 (cutover Elementor → primaauto2026): stary template Elementora 101874 używał shortcode `[asiaauto_price_breakdown]` (rozbicie brutto + netto, VAT 23%); nowy `single-listings.php` woła `[asiaauto_single]`, który w `class-asiaauto-single.php::sidebar()` renderował tylko brutto.
- **Zmiana w `class-asiaauto-single.php:312-321`** (gałąź `if` z ceną): dodany `<span class="aa-single__price-netto">` z netto = brutto / 1.23 (VAT hardcode 23%, spójnie z `[asiaauto_price_breakdown]` w `class-asiaauto-shortcodes.php:1617-1618`). Gałąź `else` („Cena na zapytanie") bez zmian.
- **CSS** w `assets/css/asiaauto-single.css:34`: nowa reguła `.aa-single__price-netto` (14px, var(--sec) szary, font-weight 500, display block).
- **Smoke test:** `/oferta/denza-d9-dm-2024-94073/` → brutto 247 000 PLN + netto 200 813 PLN ✓.
- **Backupy:** `class-asiaauto-single.php.bak-2026-04-29-netto`, `asiaauto-single.css.bak-2026-04-29-netto`.

## 0.32.21 — 2026-04-29 (Stock bary na hub make/serie — przed głównym contentem)

- **Hub make/serie pokazują stock listings PRZED głównym contentem.** User insight: "tych aut nie będziemy mieć dużo, możemy wyciągnąć w Rzeszowie/w drodze przed nowościami" — eksponuj realnie dostępne pojazdy z stocku sprzedawcy na każdym hubu marki/modelu, jeśli istnieją.
- **Shortcode `[asiaauto_hub_listings]` rozszerzony o `reservation_status` parametr** w `class-asiaauto-brand-hub.php`:
  - Filter `reservation_status="on_lot"` lub `"in_transit"` (whitelist).
  - Pusty wynik dla filtra → zwraca `''` cicho (nie pokazuje "Brak ofert").
  - Meta_query dodany do WP_Query.
- **Templates `taxonomy-make.php` + `taxonomy-serie.php`** (theme primaauto2026):
  - 2 nowe shortcody przed `<section class="aa-hub__body">` w nowej `<section class="aa-hub__stock">`:
    - `🇵🇱 {model/marka} — na placu w Rzeszowie` (CTA: `/w-rzeszowie/`)
    - `🚢 {model/marka} — w drodze do Polski` (CTA: `/w-drodze/`)
  - n=6 sztuk na sekcję, sortowanie domyślne (data DESC).
  - Sekcja renderowana **warunkowo** — tylko jeśli przynajmniej jedna z dwóch sub-sekcji ma listings (else nic nie pokazuj, brak placeholdera).
- **Test:** `/byd/sealion-8-dm-i/` pokazuje obie sekcje (1 + 1 listing); `/byd/` (make) pokazuje agregat marki (5 + 9); `/byd/tang-dm-i/` (brak stocku) → 0 sekcji aa-hub__stock ✓.
- **Spójność z v0.32.19/20:** te same emoji + colors + badge na karcie. Klient widzi status w warstwach: home Stock Highlights → hub make/serie stock bars → per-listing badge.

## 0.32.20 — 2026-04-29 (Listing card badges priorytet on_lot/in_transit/nowe)

- **Listing card badges priorytet:** `on_lot` (`🇵🇱 Na placu`, zielony #1B5E20) → `in_transit` (`🚢 W drodze`, niebieski #0D47A1) → `is_new` (`Nowe`, żółty #E8AC07 — fallback). Dotąd listingi miały tylko badge "Nowe" (post < 24h) — nie eksponowały statusu fizycznego pojazdu na froncie.
- **Implementacja w 2 plikach:**
  - `class-asiaauto-homepage.php::getLatestListings()` + `renderLatest()` — sekcja "Najnowsze oferty samochodów z Chin" na home.
  - `class-asiaauto-brand-hub.php::renderListings()` — shortcode `[asiaauto_hub_listings]` używany na hub make/serie.
- **CSS** w `class-asiaauto-homepage.php::renderCSS()` — dodane warianty `.aa-home__car-badge--pl` i `.aa-home__car-badge--transit` (dziedziczą positioning z bazowego `.aa-home__car-badge`).
- **Test live:** `/samochody/byd/sealion-8-dm-i/` pokazuje "🇵🇱 Na placu" na pierwszym listingu (BYD Sealion 8 z `_asiaauto_reservation_status=on_lot`).
- **Spójność z Stock Highlights** (v0.32.19): te same emoji + colors. Sekcja "Auta dostępne teraz" na home + badge na każdej karcie listingu = pełna sygnalizacja statusu fizycznego pojazdu w obu warstwach (home overview + per-listing).

## 0.32.19 — 2026-04-29 (Stock Highlights na home)

- **Sekcja "Auta dostępne teraz" (W Polsce + W drodze)** na stronie głównej — między `renderLatest` a `renderMakes`. User insight: "sprzedawca wie co się powinno teraz sprzedawać" — auta z `_asiaauto_reservation_status` ∈ `{in_transit, on_lot}` to realne flagshipy biznesowe (sprzedawca już zainwestował). Trust signal + 12 internal links z home do hub modeli (poprzednio 0).
- **Implementacja w `class-asiaauto-homepage.php`:**
  - `getStockHighlights()` — DB query po listings z `_asiaauto_reservation_status`, JOIN make+serie, grupowanie per model (make_slug+serie_slug) z licznikami `on_lot`/`in_transit`, sort priorytet on_lot DESC. Cache transient 1h.
  - `renderStockHighlights()` — kafelki (thumb 4:3, title, badge `🇵🇱 W Polsce: N` + `🚢 W drodze: N`). CTA: linki do `/w-rzeszowie/` i `/w-drodze/` (istniejące strony z shortcodami `[asiaauto_inventory reservation_status="on_lot|in_transit"]`).
  - `invalidateStockOnMeta()` — hooki `updated/added/deleted_post_meta` dla klucza `_asiaauto_reservation_status` flushuje transient.
- **CSS** ~30 linii w `renderCSS()`: grid responsywny (2 kolumny <600px), card hover, badges (zielony PL / niebieski transit), CTA primary buttons.
- **Dane na deploy:** 12 unique modeli (5 on_lot + 9 in_transit). Top: BYD Sealion 8 (1+1), BYD Leopard 5 (1+1), Geely Monjaro (1), Denza N8L DM (1), Mazda EZ-6 (1), reszta in_transit-only.
- **Decyzja modele vs listingi:** modele wygrywają — hub trwa wiecznie a single listing znika po sprzedaży = SEO equity przepada; linki do hub kumulują authority dla "BYD Leopard 5/8" itd. które chcemy rankować; badge "1 dostępne dziś" daje trust + klient klika do hub gdzie widzi WSZYSTKIE oferty modelu.

## 0.32.18 — 2026-04-29 (sesja nocna)

- **GSC sitemap fix**: Google indeksował nas na bazie starego `wp-sitemap.xml` (WP native, RankMath ma `noindex` na nim) — submitted 2026-04-23, downloaded 2026-04-27 z 1 warning. **Submit nowych 8 sitemap RankMath** przez Search Console API: `/sitemap_index.xml` + child sitemaps (`page-sitemap.xml`, `make-sitemap.xml`, `serie-sitemap.xml`, `listings-sitemap1-4.xml`). Wszystkie 8 z 0 errors, 0 warnings. Po tym Google zacznie crawl-ować huby modeli (były "URL is unknown to Google" przed).
- **GSC indeks audyt** (11 kluczowych URLs): `/`, `/samochody/`, `/marki/`, `/samochody/byd/` = **PASS** (zaindeksowane, last crawl 2026-04-28). Hub modeli = **NEUTRAL "URL is unknown to Google"** lub "Discovered - currently not indexed" (5/7 hub serie sample). Pierwszy listing single = "URL is unknown" — czyli Google nie wszedł jeszcze głębiej. Sitemap fix + title fix v0.32.17 powinien to odblokować.
- **Search Analytics top 20 queries (28 dni)** — 153 impr na home pos=5.8, brand-dominant (prima auto rzeszów / prima auto / prima-auto). Long-tail flagship już widoczne: `denza z9 gt` pos=42, `geely galaxy starship 8` pos=11, `aito seres` pos=1, `icar v23 cena w polsce` pos=9, `mg auto import` pos=11. Domena świeża, ranking rośnie naturalnie.
- **4 nowe orphan parents serie** (importer dorzucił po popołudniowej naprawie): 3 unique parent fix (`smart #3`, `Jetour X70 PRO`, `Mazda 3 Axela`) + 1 duplikat MERGE (`#6553 Seal U DM-I (Song Plus)` orphan → `#3702` keeper, `seal-u-dm-i` slug). 301 redirect już istnieje w V62_SERIE_REDIRECTS od popołudnia. `serie-broken-parent: 4 → 0`.
- **Chinese-chars batch ×2** w `translations-complectations.php`:
  - +18 entries TIER 4 (`二`→II, `超`→Super, `星夜`→Starnight, `智能超`→Smart Super, `超然致远`→Transcendent Vision, `陆冠`→Land Crown, `星空龙耀`→Starsky Dragon, `定制`→Custom, `首发`→Debut, `途昂`→Teramont, `出众`→Outstanding, `骑士`→Knight, `致行`→Drive, `自在`→Free, `花生`→Peanut, `银河`→Galaxy, `旅行升级`→Travel Upgrade, `纵野`→Wild, `享境`→Journey).
  - +2 entries (`智能`→Smart, `星月女神`→Star Goddess).
  - **chinese-chars: 26 → 8** (dwa tytuły wciąż failują, fragment `为`/`然致远` to sub-fragmenty oraz nowo zaimportowane).

## 0.32.17 — 2026-04-28

- **KRYTYCZNY FIX: title/meta/schema dla hub MODELU.** User zauważył że hub `/samochody/byd/leopard-8/` ma w `<head>` title z hub MARKI: `"BYD — Auto z Chin | Prima-Auto"` zamiast `"BYD Leopard 8 (Denza B8) — Import z Chin | Prima-Auto"`. Powód: WP rewrite `^samochody/(make)/(serie)/?$` ustawia oba query vars, ale **`get_queried_object()` zwraca pierwsze (make=BYD)** — RankMath/theme/schema generują z perspektywy hub MARKI. Każdy hub modelu Google indeksował jako duplikat hub make → 0 rank dla "BYD Leopard 8/5/7", "Denza Z9", itd.
- **Fix w `class-asiaauto-brand-hub.php`** — nowy hook `wp` (prio=5) `fixQueriedObjectForSerieHub()`: dla URL z make+serie nadpisuje `$wp_query->queried_object` na **serie** term + `is_tax=true`. RankMath teraz widzi Leopard 8 jako queried object → bierze `rank_math_title`/`rank_math_description`/`rank_math_focus_keyword` z termmeta serie. Test:
  - `/byd/leopard-8/` → `BYD Leopard 8 (Denza B8) — Import z Chin | Prima-Auto` + meta desc z 3 egzemplarzy 2025...
  - `/byd/leopard-5/` → `BYD Leopard 5 (Denza B5) — Import z Chin | Prima-Auto`
  - `/byd/leopard-7/` → `BYD Leopard 7 (Tai 7) FCB, PHEV — Import z Chin | Prima-Auto`
  - `/aito/m8/` → `AITO M8 — Import z Chin | Prima-Auto`
- **Bonus**: dodane `renderRankMathTitle()` helper resolves `%term%/%sep%/%sitename%/%title%` placeholders w stored RankMath title patterns dla `filterHubTitle` + `overrideHubDocumentTitle` (priorytet rank_math_title term meta jeśli istnieje).

## 0.32.16 — 2026-04-28

- **Sync `wiki_body + FAQ` → natywny `term->description` (RankMath SEO Analyser).** User-pytanie: RankMath nie ma czego analizować bo content jest w custom termmeta (`asiaauto_wiki_body`, `asiaauto_faq_json`), a natywne `term->description` było puste. Fix:
  - **REST endpoint `POST /wp-json/asiaauto/v1/hub-content/{tax}/{id}`**: przy save `wiki_body` syncuje do `term->description`. Przy save `faq_json` dorzuca FAQ jako `<h2 class="aa-rm-faq">...</h2>` + `<details><summary>Q</summary>A</details>` po wiki_body. n8n NIE zmienia się (nadal woła ten sam endpoint).
  - **Backfill całego DB** (raw SQL UPDATE wp7j_term_taxonomy, omija `wp_kses_post`): 49 make + 307 serie termów ma teraz `description` = `wiki_body + FAQ HTML5`. Przykład Chery (term_id 3578): 9950 chars, 5× h2, 8× details. RankMath SEO Analyser teraz analizuje pełen content.
- **FAQPage schema NIE duplikat** — RankMath rozpoznaje tylko własny block `wp:rank-math/faq-block`, NIE rozpoznaje natywnych `<details>` jako FAQ → nie generuje drugiej FAQPage. Sprawdzone: hub `/samochody/chery/` ma nadal 1× FAQPage (nasz custom z `class-asiaauto-brand-hub.php`). GSC FAQ rich results bezpieczne.
- **Frontend nieaktualizowany** — template `taxonomy-make.php`/`taxonomy-serie.php` renderuje wiki_body + FAQ z termmeta (nie z `description`). Wizualnie strona bez zmian. RankMath analizuje description niezależnie od frontu.

## 0.32.15 — 2026-04-28

- **Car schema parity vs west-motors**: dodane `manufacturer` (Organization z brand name) + `offers.priceValidUntil` (+90 dni od teraz, format YYYY-MM-DD). Drobne quality signals dla Google Product Snippet (bold price w SERP). Schema validator (schema.org/validate): **0 errors, 0 warnings** — 14 typów rozpoznanych (Car, Brand, Organization, Offer, OfferShippingDetails, ShippingDeliveryTime, MonetaryAmount, EngineSpecification, QuantitativeValue, DefinedRegion, Country, BreadcrumbList, ListItem, Thing).
- Sitemap audit: 4 `listings-sitemap{1-4}.xml` × 200 URL = ~800 listings indexable. Najnowszy lastmod: 2026-04-28T19:07:46Z (świeży). RankMath emituje sitemap_index.xml + listings-* + page-sitemap.xml. Robots.txt zawiera referencję.

## 0.32.14 — 2026-04-28

- **TIER 3 ×1 corner cases** — `data/translations-complectations.php` +25 entries (user-aprobowane wszystkie ✓+⚠): `尊`→Honor, `劲`→Power, `山河`→Mountain, `东方曜`→Eastern Glory, `今朝`→Today, `御`→Royal, `加长`→Extended, `征服`→Conqueror, `超长蓝鲸`→Long Range Blue Whale, `辰光`→Starlight, `美好`→Wonderful, `过道`→Walkway, `新蓝鲸`→New Blue Whale, `天枢`→Tianshu, `乘用`→Passenger, `领先`→Leading, `龙腾`→Dragon, `灵`→Spirit, `省心`→Worry-Free, `奢享`→Luxury Premium, `幸福`→Happiness, `真香`→Sweet Spot, `跃享`→Joy Premium, `劲为`→Power, `巡礼众享款`→Pilgrimage Edition. Retranslate: 44 → 24 tytułów chińskich (20 naprawionych jednorazowo + trwały efekt na importer).

## 0.32.13 — 2026-04-28

- **Car schema enrichment + OfferShippingDetails na single listings.** `class-asiaauto-single.php::renderMeta()` wzbogacone (utracone przy dedup 2026-04-24): `bodyType`, `vehicleTransmission`, `driveWheelConfiguration`, `color`, `itemCondition` (z taksonomii body/transmission/drive/color/condition), `vehicleEngine` (enginePower KW z `power($ep)` na bazie `_asiaauto_extra_prep` `energy_elect_max_power`). Plus `offers.shippingDetails` (`OfferShippingDetails`): shippingRate 0 PLN, addressCountry PL, handlingTime 0-7 dni, transitTime 56-84 dni — gap vs west-motors zamknięty. Test #281026: 16 kluczy w Car schema (vs 11 wcześniej), 392 KW enginePower, AWD, SUV, UsedCondition, full ShippingDetails.
- **`银河A7 EM` term cleanup.** Term #6539 rename `银河A7 EM` → `Galaxy A7 EM-i` + parent change Galaxy (#3394) → Geely (#3626). 5 listingów strip `银河` z post_title (`Galaxy 银河A7 EM` → `Galaxy A7 EM`). URL `/samochody/galaxy/a7-em/` → 301 → `/samochody/geely/a7-em/` (V61 redirect).

## 0.32.12 — 2026-04-28

- **Tłumaczenia TIER 1⚠ + TIER 3 (25 nowych w `translations-complectations.php`).** User-aprobowane:
  - **TIER 1⚠** (5): `奕派007`→`ePi 007`, `奕派008`→`ePi 008`, `奕派`→`ePi`, `途昂Pro`→`Pro`, `高山8`→`Gaoshan 8` (`超级混`→`Super Hybrid` był już w mapie). Per analizy `tmp/chinese-fragments-analysis-2026-04-28.md`.
  - **TIER 3** (20 top frequency): `式`→`Style`, `商用`→`Commercial`, `智享`→`Smart Premium`, `智慧新蓝鲸`→`Blue Whale 2.0`, `万辆甄选款`→`Selected Edition`, `超越`→`Surpass`, `进取`→`Progressive`, `超级`→`Super`, `战舰`→`Battleship`, `至臻`→`Premium+`, `智雅`→`Smart Elegance`, `鸿蒙座舱`→`HarmonyOS Cabin`, `智逸`→`Smart Comfort`, `悦享`→`Joy Edition`, `向往`→`Aspire`, `传奇`→`Legend`, `冠军`→`Champion`, `磷酸铁锂`→`LFP`, `停产`→`Discontinued`, `运`→`Sport`.
- Mapa używana zarówno przez `wp asiaauto diag apply-fix chinese-chars` (retranslate post_title) jak i przez `class-asiaauto-translator.php:750` przy każdym imporcie z dongchedi → trwały efekt na obecne (98 → 50 tytułów chińskich) + przyszłe.
- **Termmeta `marka=` link sync** (47 termmeta rows): w `asiaauto_wiki_body` zamieniono stare slugi v6.1 marek (`marka=fangchengbao` → `marka=byd`, `marka=galaxy` → `marka=geely`, etc.) na docelowe — eliminacja "linki do Fangchengbao" w hub Leopard/Voyah/itd. po migracji v6.1 marek (treści generowane 2026-04-23 przed v6.1).

## 0.32.11 — 2026-04-28

- **Hub fallback luka: `/samochody/{make}/{cokolwiek}/` → 404.** Wcześniej dowolny string w drugim segmencie URL renderował hub marki (200 + index) — `/samochody/byd/cokolwiek-fake-12345/` zwracał `BYD — Auto z Chin` z `index, follow`. Każdy crawler/spam/typo URL = thin index = duplicate content. Fix w `class-asiaauto-cpt.php::filterHubQuery()`: jeśli `make` lub `serie` term nie istnieje (po `get_term_by('slug', ...)`), `$q->set_404() + status_header(404) + nocache_headers()`. Test: fake → 404 ✓, prawdziwy hub → 200 ✓, V62 redirects → 301 ✓.

## 0.32.10 — 2026-04-28

- **`/zamow/?listing_id=X` → noindex,follow.** User zauważył że formularz wizard zamówienia per listing jest indeksowalny — 1841 ogłoszeń × identyczny szablon = duplicate content na masową skalę. Canonical do `/zamow/` był ustawiony, ale Google czasem ignoruje canonical przy silnych sygnałach (np. linki wewnętrzne z każdego ogłoszenia). Fix analogiczny do v0.32.8 inventory filters: nowa metoda `isOrderWizardPerListing()` w `class-asiaauto-seo.php` (detekcja `post_name='zamow'` + `$_GET['listing_id']`) podpięta w obu hookach `wp_robots` + `rank_math/frontend/robots`. Test: `/zamow/?listing_id=278417` → noindex,follow ✓; `/zamow/` (bez param) → index,follow ✓.

## 0.32.9 — 2026-04-28

- **Dedup orphan-fix duplicates + V62_SERIE_REDIRECTS.** Fix błędu z 0.32.0 `serie-broken-parent apply`: 70 orphan termów dostało parent, ale 11 z nich to były duplikaty istniejących keeperów v6.1 (np. `zeekr-9x`/`9x`, `leopard-5-denza-b5`/`leopard-5`, `atto-3-yuan-plus`/`atto-3`, `seal-u-dm-i-song-plus`/`seal-u-dm-i`, `voyah-taishan`/`taishan`...). Każdy duplikat = 2 huby na ten sam model = split SEO. Naprawa:
  1. **Skrypt `tmp/merge-11-duplicates.php`** — re-tag listings (INSERT IGNORE term_relationships) → keeper, DELETE orphan term, recount keeper. 11/11 OK.
  2. **`class-asiaauto-redirects.php`** — dodana stała `V62_SERIE_REDIRECTS` (mapa per-make `[old_slug → new_slug]`) + metoda `redirectV62SerieDuplicates()` (priorytet 0, wzorzec V61). 11 starych URLs dostaje 301 → keeper.
  3. **termmeta `_asiaauto_primary_make_slug`** — 32 termy zsynchronizowane z v6.1 marek (fangchengbao→byd, galaxy→geely, gac-trumpchi→gac, itd.). Bez tego breadcrumb na hubach pokazywał starą markę (`Fangchengbao` zamiast `BYD`).
- Wynik: serie-broken-parent 70 → 0; duplicate-serie-terms 37 → 29 (reszta = intencjonalne sub-warianty EV/DM); BYD hub clean (1 term per model); breadcrumb po v6.1 marek poprawny. Backup pre-merge: `~/backups/primaauto/2026-04-28-orphan-parent-fix/terms-pre-fix.sql` (3.7 MB).

## 0.32.8 — 2026-04-28

- **Inventory filter URLs noindex.** User zauważył w Screaming Frog że `/samochody/?nadwozie=suv`, `/samochody/?paliwo=hybrid`, `/samochody/?marka=byd` itd. są nadal indeksowalne — duplikat treści z hubów (`/samochody/byd/`). Pierwszy fix v0.32.6 używał `is_post_type_archive('listings')`, ale to nie zwraca true bo `/samochody/` to **WP page z shortcode `[asiaauto_inventory]`**, nie WP archive. Zmiana detekcji na `has_shortcode($post->post_content, 'asiaauto_inventory')` + sprawdzenie `$_GET` z whitelistą filter params (`nadwozie, paliwo, marka, model, naped, rok, cena, kolor, skrzynia`). Aktywne w obu hookach: `wp_robots` (core) + `rank_math/frontend/robots`. Test: `/samochody/?marka=byd` → noindex,follow ✓; `/samochody/` (czysty) → index ✓.

## 0.32.7 — 2026-04-28

- **Homepage SEO refocus na „import samochodów z Chin".** User cel: pierwsza pozycja w Google we frazie „import samochodów z Chin". Zmiany:
  - H1 w `class-asiaauto-homepage.php::renderHero()`: „Samochody elektryczne i hybrydowe z Chin" → **„Import samochodów z Chin do Polski"**.
  - Hero subtitle: „Prima-Auto — agencja importu samochodów z Chin. EV, PHEV, hybrydy. Pełna obsługa: transport, cło, homologacja i rejestracja w cenie."
  - `rank_math_title` page_id=93629: **„Import samochodów z Chin do Polski | Prima-Auto"** (62 chars).
  - `rank_math_description`: focus kw na początku + USP + 1841 ofert + Rzeszów (155 chars).
  - `rank_math_focus_keyword`: **„import samochodów z Chin"**.
  - OG/Twitter title + description ustawione bezpośrednio przez `rank_math_facebook_*` i `rank_math_twitter_*` term meta.
- Strona główna jest WP page (id=93629) z content `[asiaauto_homepage]` shortcode — RM widzi tylko shortcode w editor, ale frontend ma pełną treść (RM analizuje frontend, nie source content).

## 0.32.6 — 2026-04-28

- **RankMath Pro migration — total przeniesienie SEO meta na RankMath.** User po instalacji RM Pro wykrył dublowanie 3 meta z `class-asiaauto-seo.php`: 2× description, 2× canonical, 2× CollectionPage JSON-LD na hubach marek/modeli. Strategia: total migration — RM zarządza title/description/canonical/og/twitter/CollectionPage, zostawiamy tylko nasz ItemList (lista listingów per hub — RM tego nie ma) + FAQPage (z `aa-hub-faq` w wiki_body) + BreadcrumbList na single listings (`class-asiaauto-single.php` nietknięte).
- **Zmiany w `class-asiaauto-seo.php`:**
  - `renderMeta()` — early return gdy `defined('RANK_MATH_VERSION')`. RM emituje canonical/description/og/twitter z `rank_math_*` term meta.
  - `renderSchema()` — gdy RM aktywny, emituje TYLKO ItemList (BreadcrumbList + CollectionPage przejęte przez RM).
  - `filterHomeTitle()` — early return gdy RM aktywny (RM kontroluje title z templates).
  - Backward compat: kod fallback gdy RM dezaktywowany (np. tymczasowo).
- **Bulk setup 284 hubów** (46 marek + 238 modeli z `asiaauto_wiki_body`):
  - `rank_math_focus_keyword` — make: nazwa marki, serie: „Marka Model" (parent-aware).
  - `rank_math_description` — z `asiaauto_seo_desc` (z REST hub-content endpoint, generowane przez n8n batch 0.31.5).
  - `rank_math_title` — template: make `{Marka} — Auto z Chin | Prima-Auto`, serie `{Marka} {Model} — Import z Chin | Prima-Auto`.
- **Thin tax noindex fix.** Nasz `wp_robots` filter był przykryty przez RM. Dodany `rank_math/frontend/robots` filter w `class-asiaauto-seo.php` — wymusza `noindex, follow` na taxonomy `transmission`, `drive`, `exterior-color`, `interior-color`, `condition`. Test: `/skrzynia-biegow/cvt/`, `/naped/awd/`, `/kolor-nadwozia/red/` → noindex ✓; `/paliwo/electric/`, `/samochody/byd/` → index (zostawione, wartościowe).
- **Sitemap regenerowany** przez `wp rankmath sitemap generate`. RM sitemap zawiera: make, serie (×2 plików), listings (×13), pages, local. Brak thin tax (RM domyślnie wyklucza taxonomy z 0 wpisami i niewartościowe).

**Pending (user-side):**
- W RankMath admin UI: Status & Tools → Database Tools → „Re-analyze SEO Score" — bulk obliczy score dla 284 hubów (auto przez admin, niedostępne via WP-CLI).
- Po score: review najgorszych w `Listings → Marki/Modele → Edit` (RM panel z prawej).
- Brakujące huby (4 marki + 70 modeli z `tmp/missing-hubs-2026-04-28.md`): n8n PAUZOWANE, najpierw dobry score na obecnych, potem dodawanie nowych.

## 0.32.5 — 2026-04-28

- **`missing-images` chunked apply + scope fix.** Bug: user dostawał alert „Apply błąd: Invalid JSON" + 2 listingi „nie do ruszenia". Trzy przyczyny:
  1. **Scope rozjazd:** check class scanował `post_status IN ('publish','draft')`, ale skrypt `diag/fix-missing-images.php` filtrował tylko `publish`. Stuck drafts (Xiaomi SU7 #242486, AITO M9 #246353 — oba ghost-offers 404) były znajdowane ale nigdy naprawiane.
  2. **Stdout w response:** `AsiaAuto_API::getOffer()` przy 404 wypisywał warning na stdout (poza zasięgiem `WP_CLI` guard), psuło JSON gdy AJAX response.
  3. **Proxy timeout:** apply na 18+ listingach ~3-4 min > LiteSpeed proxy timeout (~60-90s), klient dostawał truncated response.
- **Fixy:**
  - `diag/fix-missing-images.php`: scope `IN ('publish','draft')`. Plus `$max_items` 3rd arg (chunk size).
  - `class-check-missing-images.php` `applyFix`: `ob_start/ob_get_clean` wokół invocation (output do logger jako warning, JSON czysty); chunk_size=8 per request; zwraca `has_more` + `remaining`.
  - `class-check-base.php` `validateApplyToken`: usunięta `delete_transient` — token TTL-based 300s, reuse w obrębie chunked apply (bez tego każdy chunk wymagałby nowego preview).
  - `assets/admin-diag.js` `previewAndApply`: pętla while max 50 chunks, per chunk update modal z progress („Chunk 3: naprawiono 8 (łącznie 24). Pozostało: 6"), re-scan rowka po wszystkim.

## 0.32.4 — 2026-04-28

- **`missing-images` apply timeout fix.** Domyślny `set_time_limit(25)` w abstract base był za krótki dla `fix-missing-images.php` (iteruje API getOffer + downloadAndStore per listing, ~1-3s/listing × 30+ = >25s). Skutek: skrypt był **przerywany w środku** — naprawiał N listingów, AJAX wracał z `applied=0`, token był single-use'd przed timeoutem. User widział „Naprawiono: 0" ale bazowy count realnie spadał (32→23).
- Override `set_time_limit(300)` w `applyFix()` tylko dla missing-images.
- Logger zapisuje teraz `apply-start` (przed) i `apply-end` (po) — diagnoza timeoutów.
- JS: button „Wykonaj" disabled + tekst „Wykonywanie… (do 5 min)" po kliku, anti-double-click + jasny feedback że apply trwa.

## 0.32.3 — 2026-04-28

- **`chinese-chars` check — split na 3 grupy + edytor tłumaczeń.** Wcześniejsze „370 miejsc z CN" było bezużytecznym agregatem 3 fundamentalnie różnych problemów. Teraz w UI rozdzielone:
  - **Tytuły z CN (117):** post_title z nieprzetłumaczonymi fragmentami. Fix: `asiaauto_diag_chinese_v23(true)` — re-translate przez `translations-models.php` + `translations-complectations.php`.
  - **Aktywne termy z CN (1):** np. `银河A7 EM` parent=0, count=4. Wymagają ręcznej decyzji (rename + migracja listingów na canonical EN). NIE batchujemy — pomijane w apply z error msg.
  - **Orphany count=0 (252):** bagaż migracji v6.1, niewidoczne na froncie. Fix: bulk `wp_delete_term` (kosmetyka bazy).
- **Edytor tłumaczeń inline w modalu.** Sekcja „Niezamapowane fragmenty CN" pokazuje 84 unikalnych fragmentów (`高能`, `征服者`, `真香`, `劲擎`, `头等舱`, …) z formularzem `[CN] [EN input] [select model/complectation] [Dodaj]`. Klik wywołuje nowy AJAX `asiaauto_diag_add_translation` → atomic append do odpowiedniego `translations-*.php` z `.bak-YYYY-MM-DD-HHMMSS` + parse-verify + rollback. Po dodaniu wpisu można re-run `chinese-chars` apply żeby title-fix był pełniejszy.
- Issue meta `unmapped_fragments` array per title issue — pozwala UI pokazać które tytuły wymagają wpisów w mapie.

## 0.32.2 — 2026-04-28

- **Bugfix `make/serie-without-wiki` — meta_key prefix.** Checki używały `wiki_body` jako klucz term_meta zamiast `asiaauto_wiki_body` (faktyczny klucz zapisywany przez REST endpoint `hub-content/{tax}/{id}` z 0.31.5). Skutek: WSZYSTKIE aktywne termy raportowane jako bez wiki, mimo że BYD/Chery/Voyah itd. mają 6000+ znaków opisów. Real counts po fixie: make 50→4 (Changan Qiyuan, Dongfeng Fengshen, GAC Aion Hyper, Wuling), serie 303→66.

## 0.32.1 — 2026-04-28

- **Diag panel — uzupełnienia v1.1 (operacyjne fixy z 1. dnia)** — wszystkie 10 checków mają teraz fix actions, mapując workflow „dojdzie nowy model → popraw mapping → dodaj hub → wygeneruj opis":
  - **Tool 1 (mapping):** `listings-without-mapping` → fix per-item (form `make_eu`/`serie_eu` → atomic write do `data/brand-mapping-v6.1.php` z `.bak`). Future-facing — meta keys w listingach jeszcze niewypełniane.
  - **Tool 2 (hub structure):** Dwa NOWE checki:
    - `serie-broken-parent` — termy serie z `parent=0` ale `count > 0`. Heurystyka: `guessMakeFromListings()` JOIN przez term_relationships → `wp_update_term($id, ['parent' => $make_id])`. Live: 65 termów (artefakty migracji v6.1).
    - `duplicate-serie-terms` — grupuje listingi po prefiksie 3 słów post_title; gdy >1 term serie pod tym prefiksem → grupa. Fix: `wp_set_object_terms` na canonical (priorytet: parent>0 + post_count) + `wp_delete_term` reszty + `flush_rewrite_rules`. Live: 37 grup (m.in. Zeekr 9X #4824 vs #6532). UWAGA: per-item review wymagany, sub-warianty (EV/DM-I) mogą być false-positive.
  - **Tool 3 (description):** `make/serie-without-wiki` (już dodane w 0.32.0 v1.0.1) wysyłają webhook do `https://witold140-20140.wykr.es/webhook/primaauto-{make,serie}-desc` z `usleep(300000)` throttle.
- **`make/serie-without-wiki` — `hide_empty=true`** (zamiast false). Liczą tylko aktywne marki/modele z listingami. Counts: 296→50 (make), 2460→303 (serie) — sygnał operacyjny zamiast szumu.
- **`missing-images` — `getFixMode='confirm'`** (było `auto`) + dry-run probe HEAD na próbie ≤50 listingów w `previewFix()`. Modal pokazuje split: `~X dostanie zdjęcia, ~Y do KOSZA (ghost-offer 404)`.

Pełna trasa zmian: `docs/superpowers/specs/2026-04-28-diagnostyka-admin-panel-design.md` + `docs/superpowers/plans/2026-04-28-diagnostyka-admin-panel.md`.

## 0.32.0 — 2026-04-28

- **Diagnostyka admin panel** — nowe submenu `Listings → Diagnostyka`. 8 checków integralność + SEO coverage. Trzywarstwowy dostęp: UI / AJAX / WP-CLI (`wp asiaauto diag …`). Pluggable rejestr — dodanie checku = 1 plik + 1 linia. Patrz `docs/superpowers/specs/2026-04-28-diagnostyka-admin-panel-design.md`.
- Refaktor 5 skryptów `diag/*.php` na eksportowane funkcje — backward-compat z `wp eval-file` zachowana.

| Data | Wersja | Źródło | Uwagi |
|------|--------|--------|-------|
| 2026-04-24 | 0.31.12 | prod primaauto.com.pl | **Hub content pipeline fix + Galaxy cleanup + batch brakujących hubów.** (a) **Galaxy post-migracja cleanup** — `diag/fix-galaxy-migration.php`: merge 3 duplikatów serii Galaxy pod Geely (A7 PHEV 6079→6517 keep hub content, E5 3667→3397, Starship 6 6078→6516), migracja 6 listingów spod `make=galaxy` (#3394, count=6) na `make=geely` (#3626) + fix CJK w post_title `Galaxy 银河A7 EM → Galaxy A7 EM`, usunięcie orphan `Galaxy M9` #3402. Term make=galaxy zostaje z count=0 (redirect 301 pre-cutover realizuje `AsiaAuto_Redirects::redirectV61Brands`, niezależny od istnienia termu). (b) **n8n prompt caching** — `tmp/generate-n8n-workflows.py`: `system: string` zastąpiony `system: [{type:'text', text:..., cache_control:{type:'ephemeral'}}]`. Per execution 26015 (GAC make) cache_write=2233, exec 26016 (GAC Trumpchi) cache_read=2233 (90% rabat na input). Batch 13 hubów dzisiaj: $0.85 = €0.78 (bez cache byłoby €0.85, saving 8.5% — mniej niż modelowy 15%, bo output 85% kosztu nie cache'uje się). (c) **CRITICAL FIX — workflow routing term_id**: n8n node `Resolve Term ID` używał `/wp/v2/serie?slug=X` bez filtra parent → dla slugów kolidujących między markami (M8 @ GAC Trumpchi + AITO, 07 @ WEY + Avatr, H5 @ Haval + Hongqi, H6 @ Haval + Hongqi, ET5 @ Exeed + NIO, N7 @ Denza + Nissan, plus duplikatów Galaxy-like: Atlas Pro, T2 C-DM, Tiggo 9 vs "Tiggo 9 (Tiggo 8L)") zwracał pierwszy term_id globalnie (zwykle mniejszy) zamiast tego pod zamawianą marką. 9 z 10 wczorajszych zapisów serie trafiło do złych termów. Fix: (1) `class-asiaauto-rest-hub.php::factsForMake/factsForSerie` zwraca pole `term_id` (parent-aware, używa `getSerieTerm($slug, $make)` które filtruje po parent). (2) Workflow n8n: node `Resolve Term ID` WYCIĘTY, URL w `Save to WP` używa bezpośrednio `{{ $('Fetch Facts').first().json.term_id }}`. 2 nody mniej (10 zamiast 11), lżejszy workflow, zero race na resolver. (d) **Rescue skrypt** `diag/fix-batch-hub-routing.php`: move hub_content z 6 błędnych term_id na właściwe (M8/07/H5/H6/ET5/N7) + merge 3 same-brand duplicates (Atlas Pro 3632→3646, T2 C-DM 6510→6518, "Tiggo 9 (Tiggo 8L)" 3586→3582) z przeniesieniem 7 listingów i usunięciem 3 termów. Smoke test: `/samochody/aito/m8/`, `/samochody/avatr/07/`, `/samochody/hongqi/{h5,h6}/`, `/samochody/nio/et5/`, `/samochody/nissan/n7/` — wszystkie 200 z właściwym hub contentem. (e) **Batch 13 brakujących hubów** (3 make GAC/GAC Trumpchi/Wuling + 10 serie): wszystkie zakończone HTTP 200, zero lint_warnings. Pozostało ~35 serie orphan (parent=NULL) — osobny problem (importer-time bug z parametru v6.1 keys; patrz Pending). **Pending (osobna sesja):** systemowy fix importera `class-asiaauto-importer.php:87-110` (translateMark+translateModel PRZED AsiaAuto_Mapping::getEuForCn, fallback partial z parent=mark_term_id, guard CJK w nowych termach, wspólny advisory lock taxonomy writes). Bez tego fix, przyszłe synchronizacje będą tworzyć nowe orphan termy przy każdym modelu brakującym w mapping v6.1. |
| 2026-04-24 | — | prod primaauto.com.pl | **CJK cleanup: merge 3 orphan serie termów + uzupełnienie mapping v6.1.** User zgłosił chińskie znaki w nazwach modeli. Znaleziono 3 aktywne orphan termy serie z parent=0 i CJK w name: `日产N6` #6117 (7 listingów Nissan), `风云X3 PLUS` #6235 (1 listing Chery Fulwin), `奕派008` #6535 (1 listing Dongfeng). Pod właściwymi markami istniały już prawidłowe EN termy hierarchiczne (#6514 N6 / #6519 X3 PLUS / #6258 eπ008) — orphany powstały bo `AsiaAuto_Mapping::getEuForCn()` nie trafiał w klucze CN+CN z API Dongchedi (mapping miał tylko warianty EN+EN: `Nissan|Nissan N6`, `Chery Fengyun|Fengyun X3 PLUS`, `Dongfeng Yipai|eπ008`). Fix (a) `data/brand-mapping-v6.1.php`: 3 nowe klucze CN+CN (`Nissan|日产N6`, `Chery Fulwin|风云X3 PLUS`, `Dongfeng Yipai|奕派008`) wskazujące na te same mark_eu/serie_eu/title_eu co istniejące EN warianty — prewencja powtórzenia orphan-ów przy następnym syncu. (b) `data/translations-complectations.php`: `智领 => 'Smart Leader'` (listing Chery Fulwin X3 PLUS miał `智领` w komplektacji). (c) Nowy `diag/merge-orphan-cjk-serie.php` — hardcoded mapa 3 par (orphan→target), per każda para: remove object terms z orphan, set na target (append=true), update post_title (str_replace CJK→EN + `$translator->translateComplectation`), clean_post_cache, wp_delete_term(orphan), wp_update_term_count_now(target). Guards: orphan musi mieć parent=0, target musi mieć parent>0 (ABORT jeśli nie). Dry-run + APPLY=1. Wynik APPLY: 3 orphan termy usunięte, 9 listingów przeniesionych (7+1+1), 9 tytułów naprawionych. Smoke test: `/samochody/nissan/n6/`, `/samochody/chery-fulwin/x3-plus/`, `/samochody/dongfeng/e-008/` — wszystkie 200 OK z properly scoped listings. Pozostało 0 aktywnych CJK termów (38 orphanów w `make` count=0 to osobny projekt cleanup). Brak bump wersji pluginu — zmiany data-only, kod importera niezmieniony. |
| 2026-04-24 | 0.31.11 | prod primaauto.com.pl | **Breadcrumb single-listing — model klikalny + tytuł listingu jako current (cofnięcie decyzji v0.31.10).** User zgłosił że w breadcrumbie single ogłoszenia nie da się kliknąć w model żeby wejść na jego hub. v0.31.10 świadomie skróciła breadcrumb do 3-poziom (ostatni = serie nieklikalna) dla uniknięcia duplikacji z H1 w sidebarze. Decyzja wygrywa UX: hub modelu ma teraz własny wiki_body + specyfikację, link z breadcrumbu single listing prowadzi do głębszej nawigacji (katalog → hub marki → hub modelu). Fix w `class-asiaauto-shortcodes.php::renderBreadcrumb()` linie 265-280: (a) `$serie->url` zmienione z `null` na `get_term_link($serie)` z guardem `is_wp_error`. (b) Dodany 4-ty element breadcrumbu `get_the_title($post_id)` z `url=null` jako ostatni (current span). Wynik: `Samochody › Marka › Model(link) › Tytuł(current)`. BreadcrumbList JSON-LD w `class-asiaauto-single.php::renderMeta()` już był 4-poziom od v0.31.3 (nietknięty), teraz wizualny DOM znowu zgadza się ze schema. Smoke test na `/oferta/wuling-wuling-yangguang-2025-265775/` — 4 poziomy, Wuling Yangguang linkuje do `/samochody/wuling/wuling-yangguang/`. |
| 2026-04-24 | 0.31.10 | **Single listing + wizard refactor po cutover na standalone theme primaauto2026.** (a) **USP strip (czerwona sekcja 3 kolumny)** — nowa metoda `AsiaAuto_Single::uspStrip()` wywoływana w `render()` między `keySpecs` a `description`. 3 kolumny: „W cenie" (6 pozycji z doprecyzowaniami: „Sterowanie głosowe po polsku (dla wybranych modeli, np. BYD)", „Dwa komplety filtrów (oleju, powietrza, kabinowy)", „Ładowarka 7 kW EU (PHEV / EREV / elektryki)" itd.), „Dlaczego my" (5: Pełna obsługa importu, Odbiór w Rzeszowie, Transparentna cena, 20 lat doświadczenia, Umowa agencyjna), „Informacje" (4 klikalne: Proces zamawiania / Gwarancja / **Homologacja i rejestracja** / Język — Finansowanie i Regulamin wyrzucone, druga wersja Elementor template 101874). Ikonki inline SVG (bez Font Awesome). CSS `.aa-usp-strip` z tłem `var(--accent)` w `asiaauto-single.css`, czcionki 12-13px. (b) **Pogrupowane tech specs + wyposażenie** — `$this->techSpecs($d)` (spłaszcza grupy w jedną tabelę) zastąpione przez `do_shortcode('[asiaauto_tech_specs]')` (6 sekcji: Podstawowe/Silnik/Elektryczny/Skrzynia/Zawieszenie/Zużycie, 8 wierszy rozwinięte + „Więcej" per sekcja). Plus dodane `do_shortcode('[asiaauto_equipment]')` pod tech-specs (wyposażenie checklistami). (c) **Przyciski „Zamów" prowadzą do wizarda** — `cta()` (desktop sidebar), `mobileCta()` (mobile sticky), `renderCTA()` shortcode `[asiaauto_cta]` — wszystkie 3 zmienione z `#zarezerwuj` (placeholder JS alert) na `/zamow/?listing_id=X`. Przycisk „Zarezerwuj" → „Zamów" tekstowo wszędzie. (d) **Breadcrumb 3-poziomowy** — `renderBreadcrumb()` w `class-asiaauto-shortcodes.php` kończył na pełnym tytule listingu → duplikacja z H1 w sidebarze. Teraz ostatni element = nazwa Serie (nieklikalna): `Samochody › Marka › Model`. BreadcrumbList JSON-LD w `renderMeta()` zostaje 4-poziomowy (dla Google). (e) **Sidebar `aa-info` z klikalnymi linkami** — 4 pozycje (Proces/Gwarancja/Homologacja/Język) były `<li>` bez `<a>`. Teraz `<a href=/informacje/.../>` z CSS `.aa-info__list li a` (kropkowany underline, hover akcent). (f) **Breadcrumb + Wyjątki layoutu single** — `[aa_breadcrumb]` dodany do `render()` pod `.aa-single__back`. (g) **Wizard `/zamow/` — zastąpienie `[elementor-template id="174645"]`** w `class-asiaauto-order-wizard.php:440`. Shortcode nie renderował się (Elementor deaktywowany), pokazywał literal string. Natywny HTML: heading „Informacje" + 6 linków (/informacje/*, 2 bugowe slug-i poprawione: Finansowanie/Regulamin wskazywały na `/jezyk-obslugi-pojazdu/`) + 2 buttony (Zadzwoń via `[aa_phone]`, WhatsApp via `[aa_whatsapp]` — zamiast hardcoded starego `+48 783 807 381`). CSS `.aa-wiz__info*` w `asiaauto-order-wizard.css`. |
| 2026-04-23 | 0.31.8 | prod primaauto.com.pl | **Blok B Core Web Vitals — render-blocking + LCP optymalizacja (skromna wersja, po regresji wizualnej cofnięte 3 z 4 Elementor experiments).** Baseline mobile (PSI v5, post-Plan A SEO): home 70/LCP 6,0 s, hub-make-byd 79/3,5 s, hub-serie-byd-han-dm-i 83/3,7 s, listing-jetour 79/3,8 s, outlier xiaomi/su7 83/3,0 s. Render-blocking insight 2,5 s na home. LCP element home = baner cookies Complianza (`#cmplz-message-1-optin`) — TTFB 922 ms + element render delay 1445 ms. (a) **Elementor experiments testowane, 3 z 4 cofnięte po wykryciu regresji wizualnej (padding menu + horizontal scroll na mobile na hubach marek/serii i single listing).** Aktualnie aktywne: tylko `elementor_font_display=swap` (eliminuje FOIT na Inter, BEZ wpływu na layout). Cofnięte do `default`: `elementor_experiment-e_optimized_markup` (zmiana struktury DOM Elementora rozjeżdżała stare CSS theme), `elementor_experiment-e_font_icon_svg`, `elementor_load_fa4_shim` (oba pakiet Font Awesome — bezpieczniej zostawić bez zmian). Backup wartości startowych: `tmp/elementor-options-backup-2026-04-23.txt`. (b) **Resource hints w `class-asiaauto-seo.php`** — nowy hook `wp_head` priority 0 `renderResourceHints()`: `<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>` + `<link rel="dns-prefetch" href="https://fonts.googleapis.com">`. Skraca discovery font Inter o ~150-300 ms. (c) **LCP hero `fetchpriority="high"` + `decoding="async"` w 3 widokach** — `class-asiaauto-homepage.php` linia 270 (grid najnowszych ofert na home, pierwszy `<img>` z iteratora `$i === 0`), `class-asiaauto-brand-hub.php` linia 290 (grid hubów marek/serii, iterator `$aa_idx === 0`), `class-asiaauto-shortcodes.php` linia 374 (`renderGallery` main image w single-listing). Reszta `<img>` w gridach: `loading="lazy" decoding="async"`. (d) **Nowa klasa `class-asiaauto-perf.php`** (~70 linii) — wpięta przez `require_once` w `asiaauto-sync.php` po `class-asiaauto-seo.php`, self-instantiating pattern jak SEO. Konstruktor wcześnie returnuje na `is_admin()` (perf hooki tylko na frontendzie). Dwa hooki: (1) `wp_enqueue_scripts` priority 100 `dequeueUnused()` — na hubach taxonomy `make`/`serie`, archive `listings`, single `listings` (warunek `isPluginRenderedPage()` — strony renderowane szablonami PHP / shortcodami `[asiaauto_*]`, nigdy Gutenberg blocks) `wp_dequeue_style` dla `wp-block-library` + `wp-block-library-theme`. **WAŻNE:** pierwotnie wycinaliśmy też `global-styles` + `classic-theme-styles`, ale to powodowało regresję wizualną — Hello Elementor i child theme `asiaauto` polegają na zmiennych CSS `--wp--preset--*` (padding, spacing, color tokens) emitowanych przez `global-styles`. Komentarz w kodzie z ostrzeżeniem żeby nie ruszać. Wycina 14,6 KiB unused CSS na hubach/listing. (2) `wp_default_scripts` `removeJqueryMigrate()` — usuwa `jquery-migrate` z `deps` zarejestrowanej `jquery` (frontend-only, admin nietknięty żeby nie psuć starszych wtyczek admina). Wycina 5 KB JS + 363 ms render-blocking. Smoke test: na hubie BYD, hubie serie, single listing — `block-library` zniknął z HTML, `jquery-migrate` zniknął z `<script>` na wszystkich stronach (frontend), `jquery-core-js` ładuje się standalone. (e) **`elementor_css_print_method=internal` testowane i COFNIĘTE** — internal inlinuje cały CSS Elementora w `<head>` (eliminuje 5 osobnych blocking requests `post-XXX.css` ~700 ms parallel), ale na home (Frontpage Elementor template) zwiększa HTML body ze wszystkim CSS frontpage → TBT 190→320 ms i PERF 74→69. Po reverse to `external` home wraca, ale per-post CSS pliki znów blokują (akceptowalny trade-off). Backup: `class-asiaauto-{seo,homepage,brand-hub,shortcodes}.php.bak-2026-04-23-cwv` + `asiaauto-sync.php.bak-2026-04-23-cwv` + `taxonomy-make.php.bak-2026-04-23-cwv` (ten ostatni niewykorzystany — myliłem ścieżki, faktyczny grid jest w `class-asiaauto-brand-hub.php`). **Wynik finalny (PSI mobile, po reverse experiments do baseline + naprawie dequeue + włączeniu tylko font_display=swap):** home 69/6,1 s LCP, hub-make-byd 83/3,5 s LCP (+4 PERF), hub-serie-byd 80/3,6 s LCP, listing-jetour 82/3,8 s LCP (+3 PERF), outlier xiaomi/su7 84/3,5 s LCP. **Skromny zysk +3-4 PERF na hubach/listing** (gros wygranej z 4 experimentami był w fazie z `e_optimized_markup=active` + `e_font_icon_svg=active` które zostały cofnięte z powodu regresji wizualnej — vide notatka wyżej). LCP huby spadł z 3,5-3,7 s do 3,5-3,6 s — niewielka delta vs noisy PSI. **Pełny potencjał (z 4 experimentami): hub-make 88/3,0 s, hub-serie 86/3,0 s, listing 85/3,5 s** — osiągalny po refactorze theme (header/footer w czystym kodzie zamiast Elementor templates). FCP wszędzie 3,0 s (wąskie gardło: jQuery 30 KB 600-740 ms blocking, niemożliwe do wycięcia). RB insight wciąż 2,1-2,3 s — to suma wastedMs, faktyczny serial chain krótszy bo wiele plików ładuje się równolegle. **Pending Blok C (osobne projekty):** (i) **Header/footer rewrite na czysty kod child theme** (4-8h, Twoja sugestia po regresji wizualnej) — zostawia Elementor tylko dla single-listing template; wycina ~70% Elementor render-blocking CSS na hubach (większość z 19 plików ładowana dla header/footer); LCP huby pewnie spadnie poniżej 2,5 s = green. Po tym można bezpiecznie ponownie włączyć `e_optimized_markup` i `e_font_icon_svg` bez regresji (bo header/footer już nie zależą od Elementora). (ii) **Fix hubów make/serie z dziś** — padding na mobile za duży na obu, na hubach marek listingi wyświetlają się "źle" (różnie niż na hubach modeli mimo że oba używają `[asiaauto_hub_listings n=5]` → `renderListingsCompact` — pewnie wiki_body BYD od n8n zawiera tabelkę/element overflow-x na mobile, do zbadania ze screenshotem). 15-30 min. (iii) Cookie banner Complianza jako LCP element home (4,9-6,1 s) — UX redesign na mniejszy bottom-bar; opcja `cmplz_cookiebanners.use_custom_cookie_css=yes` + custom_css redukujący wysokość banera. Home ≠ landing SEO (huby są), niski priorytet. (iv) Critical CSS inline (above-the-fold extract, defer reszty). (v) Self-host Inter font (eliminacja preconnect → 0 ms cross-origin penalty). (vi) Cron PSI weekly + GSC 7d delta monitoring. |
| 2026-04-23 | 0.31.7 | prod primaauto.com.pl | **SEO meta/schema dla home + huby marek/serii + sitemap filter — Plan A sesji SEO.** Audyt baseline (2026-04-23 21:15): sitemap submitted do GSC (primaauto.com.pl zweryfikowany siteOwner, 1841 listings + 45 marek + 275 serii + 27 URL-i w 5 thin taksonomiach), 12/12 hubów bez canonical/meta desc/og (listingi 3/3 OK), PSI mobile LCP 3.5-6.1s (outliery 51-69 perf score = SU7 + MG MG4 bez wiki_body cachowanych), SEO score wszędzie 92/100. Raport w `tmp/seo-audyt-2026-04-23.md`. Fix: nowa klasa `AsiaAuto_SEO` (`includes/class-asiaauto-seo.php`, 381 linii) — hooki `wp_head` priority 1 (meta) + 2 (schema): (a) Homepage — canonical + desc + og:website/image + twitter:card + og:locale=pl_PL; title override przez `document_title_parts` filter dedup blogname vs blogdescription (było: `"Prima-Auto - Import Samochodów z Chin – Samochody z Chin — import, sprzedaż, gwarancja"` → jest: `"Prima-Auto — Import samochodów z Chin do Polski"`). Schema WebSite/AutoDealer bez zmian (nadal w `class-asiaauto-homepage.php`). (b) Hub marki `/samochody/<make>/` — canonical + desc z `asiaauto_wiki_body` term_meta trim 158 chars (np. BYD: „BYD (Build Your Dreams) to chiński koncern założony w 1995 roku…") → fallback template `"Importuj {marka} prosto z Chin do Polski — {count} ofert…"`; og:image = pierwsze zdjęcie najnowszego listingu → fallback `get_theme_mod('custom_logo')`; BreadcrumbList 3-poziom (Home→Samochody→Marka) + CollectionPage + ItemList top 10 najnowszych listings. (c) Hub serii `/samochody/<make>/<serie>/` — jw. z BreadcrumbList 4-poziom, `display_name` term meta → fallback term->name. (d) Filter `wp_sitemaps_taxonomies` wyklucza 5 thin taksonomii (`transmission, drive, exterior-color, interior-color, condition`) z `wp-sitemap.xml` — po deploy sitemap zawiera tylko `make, serie, body, fuel, ca-year` + posts + page. (e) Filter `wp_robots` dokleja `noindex, follow, max-image-preview:large` na te 5 thin taxonomii — po deploy `/skrzynia-biegow/cvt/`, `/naped/awd/`, `/kolor-nadwozia/*`, `/kolor-wnetrza/*`, `/stan/*` mają meta robots noindex (Google wyczyści z indeksu). Single listings (`/oferta/*`) bez zmian — pełen SEO (Car + BreadcrumbList + og:product) nadal w `class-asiaauto-single.php`. FAQPage schema z `class-asiaauto-brand-hub.php` nadal emitowany jako osobny JSON-LD (hub BYD ma teraz 4 JSON-LD bloki: BreadcrumbList + CollectionPage + ItemList + FAQPage). Deploy: backup `asiaauto-sync.php.bak-2026-04-23-seo` (wcześniejsze 0.31.6), copy `tmp/class-asiaauto-seo-draft.php` → `includes/class-asiaauto-seo.php`, require_once w asiaauto-sync.php po `class-asiaauto-contact.php`, bump `ASIAAUTO_VERSION 0.31.6 → 0.31.7`, `php -l` obu OK, `wp cache flush`. Smoke test 7/7: home title dedup ✓, canonical/desc/og home ✓, hub BYD wiki_body desc + og:image z pierwszego listingu Song L EV ✓, hub SU7 z zera → 3 schema + fallback desc „51 ofert…" ✓, thin tax `noindex, follow` ✓, sitemap index bez 5 thin taksonomii ✓, listing Denza bez zmian (Car+BreadcrumbList+og:product) ✓. PSI re-check mobile: home perf 75→70/SEO **92→100**, hub BYD perf 76→84/SEO **92→100**, hub SU7 perf 51→84/SEO **92→100** (SU7 skok perf = cache warm po smoke; variance, nie optymalizacja CWV). LCP mobile 3.5s unchanged — CWV to osobny Blok B (fetchpriority/preload LCP image, Elementor CSS combine, GTM/Complianz defer). GSC `wp-sitemap.xml` resubmit via API 2026-04-23 19:18 UTC (errors:0, warnings:0). Pending Blok C (po batch wiki_body dojedzie): GSC URL Inspection request-indexing dla top 10-20 hubów, DataForSEO SERP baseline (pozycje brand/model vs west-motors.pl), cron PSI weekly + GSC 7d delta monitoring. |
| 2026-04-23 | 0.31.6 | prod primaauto.com.pl | **Importer używa mapowania v6.1 również dla `post_title` i slug.** User zgłosił „GAC Trumpchi Trumpchi GS4" x2 (dwa osobne inner_id 23417343/23567330 z różnych miast — Huizhou/Jieyang — to dwa fizyczne auta, nie race condition; ale tytuł/slug mimo v6.1 był surowy CN). Przyczyna: w `importListing()` (linie 87-102) `$title` i `$model_for_slug` były budowane z `$data['mark']` / `$data['model']` przez translator, **mapping v6.1 odpalał się dopiero w `setTaxonomies()`** — taksonomie jechały EU, ale `post_title` i `post_name` zostawały CN. Batch v6.1 z 0.31.2 naprawił 930 istniejących, ale każdy nowy import od tamtej pory generował znów raw CN (dzisiaj: 263366, 263590). Fix: dodana gałąź `AsiaAuto_Mapping::getEuForCn($markCN, $modelCN)` przed budową title; przy hicie używamy `mark_eu` + `serie_eu` (fallback na obecną logikę z translatorem dla niezmapowanych par). Cleanup istniejących (4 listingi): 263366/263590 GS4 → „GAC GS4 2024 ...", 241831/243443 → „GAC Hyptec HT 2024 ..." (post_title + post_name). Nie ruszane: 4× GWM Cannon — ich `title_eu` w v6.1 intencjonalnie zachowuje prefix „GWM Cannon Great Wall Pao" (chińskie znaki w komplektacji to osobny gap translatora, nie dotyczy mapy). 3× residuale nie obecne w v6.1 (249841 GAC Aion Hyper HL, 259561+262459 Dongfeng Yipai eπ007) — zostają do v6.2 zgodnie z ADR 0.31.2. |
| 2026-04-23 | 0.31.5 | prod primaauto.com.pl | **SEO content huby: 3 widoki + n8n content pipeline (Claude Sonnet 4.6).** (a) Strona `/marki/` — page_template `page-marki.php` w child theme, grid 29 marek z count (Top 8 hardcoded + reszta alfabetycznie), page ID 263572. (b) Archive `make` — `taxonomy-make.php` — hero, `{{LISTINGS_BAR}}` w wiki_body podmieniany na compact grid 5×, sekcja pigułek modeli (`aa-brand-card`, renderowana przez `AsiaAuto_Brand_Hub::renderModelPills()`), USP box („W cenie / Dlaczego Prima-Auto / Informacje"), FAQ. (c) Archive `serie` — `taxonomy-serie.php` wymuszany przez `template_include` filter (dwupoziomowy URL). Tabelka specyfikacji z 14 wierszami (wymiary, waga, zasięg WLTC+total, bateria, przyspieszenie, moc el., napęd/paliwo breakdown), lista wyposażenia standardowego z PL-labelami (22 cechy: kamera 360, keyless, ambient, masaż/wentylacja/podgrzewanie foteli, HUD, auto park itd.), sekcja porównawcza z EU, drugi listing bar „Zobacz też inne oferty [marka]", USP box, FAQ. (d) Nowa klasa `AsiaAuto_REST_Hub` (class-asiaauto-rest-hub.php, ~400 linii) — 5 GET endpointów: `facts-for-make/{slug}`, `facts-for-serie/{slug}?make=X`, `latest-by-make/{slug}?n=N`, `latest-by-serie/{slug}?n=N&make=X`, `aliases-for-make/{slug}`, `aliases-for-serie/{slug}?make=X` (wyciąga sub-brand aliasy z `data/brand-mapping-v6.1.php`: Fangchengbao, Yangwang, Denza). Plus POST `hub-content/{taxonomy}/{id}` z auth `X-AsiaAuto-Token` (shared secret `ASIAAUTO_HUB_TOKEN` w wp-config, kopia w `~/secrets/primaauto/hub-token.txt`) — zapisuje `asiaauto_wiki_body`, `asiaauto_faq_json`, `asiaauto_seo_desc` do term_meta. Fact block `factsForSerie` parsuje `_asiaauto_extra_prep` (JSON z ~200 chińskich kluczy per listing) → wyciąga specs (wymiary, bateria, zasięg, przyspieszenie, waga, moc) + `features_standard/optional` (zlicza % pokrycia) + `notable` (seat_material, sound_brand, autonomy_level) + taxonomies `fuel/drive/body/ca-year` breakdown. (e) `class-asiaauto-brand-hub.php` rozszerzone o `renderListingsCompact($make, $serie, $n, $cta_url, $cta_label, $heading)` — kopiuje markup `aa-home__car*` z homepage (badge „Nowe" dla <24h, specs `paliwo · rocznik · przebieg km`, CSS variables na wrapper `.aa-hub__latest-wrap`, grid 5 kolumn na desktop / slider mobile). Plus `renderModelPills($make_slug)` → markup `aa-brand-card`. Shortcode `[asiaauto_hub_listings]` teraz akceptuje atrybuty `n`, `cta_url`, `cta_label`, `heading`. `renderFaq` przestał renderować własny `<h2>Najczęściej zadawane pytania</h2>` — nagłówek decyduje template (zniesienie duplikatu H2). (f) 2 workflow n8n (aktywne na witold140-20140.wykr.es): `primaauto-make-desc` (ID `BqM9UJ2HnxGVAllb`) + `primaauto-serie-desc` (ID `dt4Q78mqwyuzc1Ta`). Pipeline: Webhook POST → 3× HTTP Request (fetch facts + latest + aliases) → Merge → Code (build prompt + anthropic_body inline) → HTTP POST Anthropic (model `claude-sonnet-4-5` = Sonnet 4.6, max_tokens 8000, system prompt ~4000 tokens z kontekstem Prima-Auto, USP „praktycznie nowe auta, nie klasyczne używane", realnym procesem 8-12 tyg, zakazem „+15-20k za import", FORBIDDEN phrases, wymogiem placeholder `{{LISTINGS_BAR}}`, zakazem sekcji FAQ/„Modele" w wiki_body, wymogiem tabelki 14+ wierszy dla serie, porównania z segmentem EU, linków wewnętrznych do filtrów `/samochody/?paliwo=erev&marka=X&naped=awd`) → Code Parse+Lint (regex wycina ewentualne sekcje FAQ i „Modele ... dostępne" z wiki_body, JSON safety: zakaz `"` wewnątrz wartości, lint FORBIDDEN phrases, warning gdy brak LISTINGS_BAR) → HTTP GET `/wp/v2/make?slug=X&_fields=id` → HTTP POST `/asiaauto/v1/hub-content/{tax}/{id}` → Respond. Workflow JSON generowane przez `tmp/generate-n8n-workflows.py` (pobiera klucze z `~/secrets/`, output gitignored bo inline keys). Czysty JSON z placeholderami zostanie commitowany do `workflows/` po przeniesieniu do n8n credentials. (g) Strona główna: linki marek w `AsiaAuto_Homepage::renderMakes()` zmienione z `$inv_url.'?marka='.slug` (filtered inventory) na `get_term_link($term)` (hub marki) — buduje autorytet hubów w indeksacji Google zamiast parametrycznych wariantów inventory. „Wszystkie marki →" → `/marki/`. Filtry body/fuel bez zmian (nie mają jeszcze dedykowanych hubów). (h) Batch generacji ruszył w tle: `tmp/batch-hub-parallel.sh` z xargs -P3 (3 concurrent webhooks), kolejka ~175 (45 marek + ~130 modeli count>0), szacowany czas ~60 min, koszt ~$25 Sonnet 4.6. Log: `tmp/batch-hub-parallel-*.log`. Pilot Voyah + Voyah FREE zweryfikowany przed batchem — 3 iteracje system promptu: v1 (pierwszy render), v2 (USP „jak nowe", ceny końcowe, realny proces, zakaz FAQ/„Modele" w wiki_body, JSON safety), v3 (tabelka 14 wierszy z specs, lista wyposażenia z PL-labelami z `features_standard`, aliasy sub-brand, internal linki). Pending: przeniesienie kluczy Anthropic + `X-AsiaAuto-Token` do n8n credentials (po weryfikacji batch), prompt caching (`cache_control: ephemeral`) dla oszczędności 2-3× przy kolejnych runach. |
| 2026-04-23 | 0.31.4 | prod primaauto.com.pl | **Plan A wdrożony — MySQL advisory lock zamiast transient w sync.** Nawrót dupli: 25 par (9 z 2026-04-22 17:04-17:11 + 16 z 2026-04-23 09:20-09:25). Root cause ten sam co w ADR 2026-04-22: sync trwa >10 min, transient TTL wygasa, równoległy cron drugiej instancji pali ten sam `change_id` i `findByInnerId` dla obu zwraca null. Fix w `class-asiaauto-sync.php`: transient `asiaauto_sync_lock_{source}` (get/set/delete) zastąpiony przez `GET_LOCK('asiaauto_sync_{source}', 0)` + `RELEASE_LOCK` w punktach wyjścia (normalne + error-return po getChangeId). Plusy: auto-release przy crashu PHP (sesja MySQL kończy się), brak TTL do wygaśnięcia. Cleanup: `wp post delete --force` na 25 MAX(post_id) — 0 rezerwacji, 0 zamówień na usuwanych. Backup przed dedupem: `~/backups/primaauto/2026-04-23-pre-dedup-posts-postmeta.sql.gz` (19 MB — tylko posts+postmeta, reszta bazy nietknięta). Legacy asiaauto.pl NIE zaktualizowany (odseparowany na 0.30.15-16, `DISABLE_WP_CRON=true` → race nie występuje; full sync pluginu to osobny projekt). |
| 2026-04-23 | 0.31.3 | prod primaauto.com.pl | **Fixy UX ogłoszenia + breadcrumbs + upload zdjęć.** (a) Fix bugu detekcji `fuel_type` w podglądzie importu ręcznego (`class-asiaauto-admin-manual-import.php`) — kolejność `$fuel_map` zmieniona tak, by `phev/mhev/erev/electric` były sprawdzane przed ogólną `hybryda`. Poprzednio `str_contains` łapał `hybryda` z nazwy „Hybryda plug-in (PHEV)" i ustawiał `hybrid` (akcyza 1.55%) zamiast `phev` (0%) → preview pokazywał cenę o ~2000 zł wyższą niż ogłoszenie (160k vs 162k). Dotyczyło wszystkich PHEV/MHEV/EREV. Importer/applyToListing bez zmian (czytał slug taxonomy bezpośrednio). (b) Data pierwszej rejestracji na froncie: shortcode `[asiaauto_key_specs]` kafelek „Rok" (ca-year) → „Rejestracja" MM/YYYY z `registration_date` (fallback: kafelek ukryty gdy meta brak). 1828/1840 listingów ma reg_date. Rok modelowy przeniesiony do shortcode `[asiaauto_tech_specs]` jako pierwszy wiersz sekcji „Dane podstawowe" (`buildTechSpecSections` rozszerzone o opcjonalny `$post_id`, `array_unshift` na sekcję `podstawowe`). Karta listingu w `/samochody/` (`AsiaAuto_Inventory::getSmartSpecs`): rok z reg_date z fallbackiem na ca-year. `class-asiaauto-single.php::gather()` + `keySpecs()` analogicznie (na wypadek `[asiaauto_single]`). (c) BreadcrumbList JSON-LD w `AsiaAuto_Single::renderMeta()` — 4 poziomy: Samochody › Marka › Model › Tytuł (każdy z `item` URL zgodnie z rekomendacją Google, mirror wizualnej wersji z shortcode). Shortcode `[aa_breadcrumb]`: root „Oferta"→„Samochody" (zgodność z URL `/samochody/`), ostatni element zmieniony z samej Serie na pełny tytuł ogłoszenia (nieklikalny current), Model klikalny (get_term_link dwupoziomowy). (d) Fix fatalu przy ręcznym uploadzie zdjęć (`AsiaAuto_Media::storeLocalUpload` linia 301): `buildAltText($listing,...)` → `buildAltText($post_id,...)`. `$listing` (array) był błędnie przekazywany tam, gdzie PHP 8 strict types wymagał `int` → TypeError, 0/N plików zapisanych, komunikat "Argument #1 (\$post_id) must be of type int, array given". Importer (downloadSingleImage, linia 176) był OK. Backupy: `class-asiaauto-admin-manual-import.php.bak-2026-04-23`, `class-asiaauto-single.php.bak-2026-04-23-regdate`, `class-asiaauto-shortcodes.php.bak-2026-04-23-regdate`, `class-asiaauto-inventory.php.bak-2026-04-23-regdate`, `class-asiaauto-media.php.bak-2026-04-23`. |
| 2026-04-23 | 0.31.2 | prod primaauto.com.pl | **Mapowanie marek/modeli v6.1 (CN→EU) — rebuild taksonomii + parent-aware URL handler + importer-time mapping.** Źródło: `tmp/mapowanie-marek-modeli-v6.1.csv` (264 wiersze) = xlsx klienta z 2026-04-23 + 16 decyzji quizu + #264 Exeed VX dopisany. (a) **Etap 1 — CSV v6.1** (90 zmian + 57 synchronizacji slug): Q1 BYD prefix dla Leopard, Q3 Denza B5/B8 w nawiasach, Q4 Sealion uniformity (Sea Lion/SeaLion→Sealion, ALE tylko w serie/title — model CN zostawiony bo pasuje do API Dongchedi), Q6 GAC Aion Hypec→GAC + Hyptec HT, Q10 Chery iCAR 03/V27 label prefix, Q14 WEY 7→07, Q15a Li Auto i6 scalenie #57+#188, Q15b MINI skip, Q16 Nissan N6 scalenie #83+#142. XPENG P7+ slug `p7-plus` (fix: sanitize_title zjadał `+`). (b) **Etap 2 — Backup** `~/backups/primaauto/2026-04-23-v6.1-taxonomy/terms-*.sql` (540KB, 4 tabele). (c) **Etap 3 — Rebuild taksonomii**: 8 nowych marek (term_id 6521-6528): BAIC, Beijing 212, Chery Fulwin, Exlantix, GAC, GWM, Luxeed, Nevo. 208 rename serie, 258 move_parent (Fangchengbao/Yangwang→BYD +28, Galaxy→Geely +62, iCAR→Chery +5, Trumpchi/Aion Hyper→GAC +55, Fengyun→Chery Fulwin +21, Maextro→Luxeed +18, Qiyuan→Nevo +7). 510 termmeta (`_serie_full_title` + `_serie_api_value`). 2 create serie (Hyptec HT term 6529 pod GAC — listingi przeniesione z starego term 5489 Hyper HT; Lynk & Co 900 term 6530). Migracja per-serie: 256 listingów przepisanych między markami przez term_relationships INSERT IGNORE + DELETE. Recount make counts. Residuals (Exeed 4, iCAR 6, Galaxy 5, Jetour Shanhai 3, Dongfeng Yipai 2 = 20 listingów niezmapowanych w v6.1, zostają pod starą marką do v6.2). Skrypty: `tmp/analyze-taxonomy.php` (dry-run raport), `tmp/apply-taxonomy.php` (APPLY), `tmp/migrate-make-per-serie.php` (APPLY). (d) **Etap 4 — Kod**: nowa `class-asiaauto-mapping.php` (singleton lookup), `data/brand-mapping-v6.1.php` (260 pozycji, klucz `markCN\|modelCN`). Importer `setTaxonomies()` przed `setTaxonomyAndMeta` wywołuje `AsiaAuto_Mapping::getEuForCn()` — nowe listingi z Dongchedi trafiają od razu pod EU-markę i EU-serie (np. `Galaxy\|Galaxy E5` → make=Geely, serie=Galaxy E5). `class-asiaauto-brand-hub.php`: **parent-aware** `getContextTerm()` (priorytet: dwupoziomowy URL `/samochody/<make>/<serie>/` zwraca serie-term filtrując przez parent=make, rozwiązuje kolizje slug typu `n7` Denza vs Nissan, `m8` AITO vs GAC Trumpchi, `07` Avatr vs WEY), nowe hooki `document_title_parts` + `pre_get_document_title` czerpiące `_serie_full_title` (np. `<title>BYD ATTO 3 (Yuan PLUS)</title>` zamiast samego `BYD`). `class-asiaauto-inventory.php`: nowa prywatna `getSerieTermByMakeParent()` + fix 3 miejsc (filterInventoryTitle, renderInventoryMeta, renderSeoBlock). Backupy: `class-asiaauto-brand-hub.php.bak-2026-04-23`, `class-asiaauto-inventory.php.bak-2026-04-23`. **Redirecty 301 — dodane wstecznie** (klient zmienił decyzję): `class-asiaauto-redirects.php` nowa metoda `redirectV61Brands()` + stała `V61_MAKE_REDIRECTS` (16 par: fangchengbao/yangwang→byd, galaxy→geely, chery-fengyun→chery-fulwin, gac-trumpchi/gac-aion-hyper→gac, icar→chery, maextro→luxeed, great-wall→gwm, changan-qiyuan→nevo, beijing-off-road→baic, 212→beijing-212, jetour-shanhai→jetour, dongfeng-fengxing/yipai→dongfeng, lotus-cars→lotus). Pattern: `^samochody/<old>/(.*)` → 301 `/samochody/<new>/$1`. **Batch update post_title**: 930 listingów zaktualizowanych (`tmp/update-listing-titles.php`) wg mapy v6.1 — parser 3-poziomowy (full prefix markCN+modelCN, modelCN self-contained, markCN multi-word z dedupe ostatniego słowa). Np. `Fangchengbao Leopard 5 2025...` → `BYD Leopard 5 (Denza B5) 2025...`, `Chery Fengyun T11 2026...` → `Chery Fulwin T11 2026...`, `BYD Haishi 07 EV...` → `BYD Sealion 7...`, `Geely Xingyue L...` → `Geely Monjaro...`. 18 listingów zostało z CN-prefix celowo (7× iCAR Super V23 niezmapowane w v6.1 + 11× GAC Trumpchi X gdzie title_eu w v6.1 zachowuje prefix „GAC Trumpchi"). Smoke test: 6 URL hubów 200 OK z poprawnymi title — `/samochody/byd/atto-3/`→`BYD ATTO 3`, `/gac/hyptec-ht/`→`GAC Hyptec HT`, `/byd/leopard-5/`→`BYD Leopard 5 (Denza B5)` (dual-name), `/exeed/vx/`→`Exeed VX` (nowy model #267), `/chery/icar-03/`→`Chery iCAR 03` (label rozróżnia od Tiggo 4/7/8/9 pod Chery). |
| 2026-04-22 | 0.31.1 | prod primaauto.com.pl | **Konwersje GA4 dla Google Ads: click_phone, click_whatsapp, generate_lead.** (a) GA4 property Prima-Auto.pl (534017542, stream G-F1NCC3D2HZ) — 3 key events utworzone przez Admin API: `click_phone`, `click_whatsapp`, `generate_lead`. (b) GTM container GTM-T4GTZ2JB (account 6351095501, container 250095450, workspace 5) — przez Tag Manager API v2 utworzone: 3 triggery Custom Event (`CE - click_phone/whatsapp/generate_lead`), 3 tagi GA4 Event (`GA4 - click_phone/whatsapp/generate_lead` używające zmiennej `{{GA4-ID}}`), 3 Data Layer Variables (`DLV - listing_id`, `DLV - vehicle_name`, `DLV - value`). Publikacja ręczna przez UI (scope `tagmanager.edit.containerversions` nie dodany do OAuth). (c) Nowy plik `assets/js/asiaauto-tracking.js` — delegated click listener dla `a[href^="tel:"]` → `dataLayer.push({event:'click_phone'})`, dla `a[href*="wa.me/"]` / `whatsapp://` / `api.whatsapp.com` → `dataLayer.push({event:'click_whatsapp'})`. (d) Enqueue globalny w `asiaauto-sync.php` hook `wp_enqueue_scripts` (każda strona frontu, cache-buster po `filemtime`). (e) `assets/js/asiaauto-order-wizard.js` w `handleStart()` po sukces `order/start` pushuje `generate_lead` z `currency:'PLN'`, `value` (z `C.init.price_pln`), `listing_id`, `vehicle_name` (z `C.init.listing.title`). Import do Google Ads conversion actions czeka na Basic access developer_tokena (obecnie `test`). |
| 2026-04-21 | 0.31.0 | prod primaauto.com.pl | **SEO: ALT rotacja + GTM dataLayer + huby /samochody/marka/model/.** Importer dedupe NIE ruszany — „Trumpchi Trumpchi" i sub-brandy (Galaxy→Geely) idą w osobnej warstwie `translations-complectations.php`. (a) ALT-y: nowa `AsiaAuto_Media::buildAltText()` — rotacja 10 szablonów po `$n % 10` (zdjęcie N, używany z Chin, import Prima Auto, rynek chiński, zamów z Chin, …). Argument `$listing` przekazany do `downloadSingleImage`. Mark/model używane z termów jak są. (b) `AsiaAuto_Single::filterTitle()` — rotacja 10 szablonów SEO title po `inner_id % 10` (używane, import, zamów, gotowy do rejestracji, z rynku chińskiego, na zamówienie, sprowadź, import prosto z Chin, kup w 2026). (c) `AsiaAuto_Single::renderMeta()` — `dataLayer.push` z eventem `view_item` (ecommerce.items + listing.{inner_id,mark,model,year,fuel,drive,body,city,cif_usd}) przed metami OG/schema. (d) Taksonomia `make` slug zmieniony: `marka` → `samochody`. Rewrite rule `^samochody/([^/]+)/([^/]+)/?$` → query `make+serie` (`registerHubRewrites`). `filterHubQuery` dokleja `tax_query AND` dla obu taksonomii. Filter `term_link` dla `serie` generuje `/samochody/<primary_make>/<slug>/`. (e) Term meta `_asiaauto_primary_make_slug` na termach serie — wypełnia importer przy każdym imporcie (`updateSerieprimaryMake`) + batch `diag/backfill-serie-primary-make.php` (252 termy zbackfillowane). (f) Term admin UI rozszerzone o `asiaauto_wiki_body` (HTML) i `asiaauto_faq_json` obok istniejącego `asiaauto_seo_desc`. (g) Nowa klasa `AsiaAuto_Brand_Hub` — shortcody `[asiaauto_hub_wiki]`, `[asiaauto_hub_faq]` (+ Schema.org FAQPage), `[asiaauto_hub_listings]` (deleguje do `[asiaauto_inventory]` z inject kontekstu archive do `$_GET[marka,model]`). (h) `AsiaAuto_Redirects::redirectLegacyTaxonomy()` 301: `/marka/*` → `/samochody/*`, `/model/*` → `/samochody/<primary_make>/*`. (i) Batch `diag/update-listing-alts.php` zaktualizował 22 034 istniejące zdjęcia (1697 listingów, 33 bez galerii). `wp rewrite flush` zrobiony. Filtry inventory (`?marka=byd,nio` GET params) nietknięte. **Fix post-deploy:** (1) w `filterTitle` dodane `unset($parts['site'])` — szablony same zawierają „Prima Auto", WP bez tego doklejał blog name `– Prima-Auto` dając podwójne branding w `<title>`. (2) Szablony 0, 7 i 9 poprawione — „używane"→„Używane", „sprowadź"→„Sprowadź", „kup"→„Zamów Online" (wielka litera na początku zdania, CTA brzmi lepiej niż „kup"). (3) `filterTitle` i `renderMeta` — baza przełączona z klejenia `{make} {serie}` na `get_the_title($pid)`. Importer w linii 93-97 robi dedupe prefixu marki przy zapisie `post_title`, więc nie ma duplikatu „Denza Denza D9 DM". Term `serie` tego nie ma (zawiera pełny „Denza D9 DM"). Dotyczy 10 szablonów SEO title, OG title, meta description, dataLayer `item_name`. (4) ALT również — `buildAltText($post_id, $n, $total)` zamiast `$listing` array, base z post_title. Szablon ALT 1 „używany" → „Używany". Batch diag re-run: 22 034 zdjęcia z nowym czystym ALT. |
| 2026-04-21 | 0.30.16 | prod primaauto.com.pl | **Cutover na docelową domenę + rebrand user-facing.** (a) Kopia 8.9GB plików asiaauto.pl→primaauto.com.pl (rsync wykluczający `mcp-test/`, backupy SQL, logi testowe). (b) DB wspólna wp7j_ (siteurl/home zmienione search-replace: 106493 URL replacements) — asiaauto.pl od teraz daje 301 canonical przez WP. (c) `DISABLE_WP_CRON=true` w asiaauto/wp-config (cron pisał nowe uploads do złego katalogu → 404 obrazków, delta rsync dociągnął 417 plików). (d) MCP `asiaauto.pl/mcp-test/` usunięty (backup w ~/backups/). (e) Rebrand user-facing: 57 wystąpień `AsiaAuto`→`Prima-Auto` w pluginie (sed z regexem chroniącym klasy `AsiaAuto_*`), 17 subjectów mail, From Name, WhatsApp prefilled message ×3, theme `style.css` Theme Name, `blogname` option, regulamin. 222 klasy `AsiaAuto_*` nietknięte. (f) Email: `zamowienia@asiaauto.pl` + `samochody@asiaauto.pl` → `china@primaauto.com.pl` (plugin filter + DB 46 zamian). (g) **Reply-To fix** w `class-asiaauto-order.php:1443,1491,1518` — admin klikając Reply na powiadomienie pisze od razu do klienta (header `Reply-To: {customer_email}` w `wp_mail()`). (h) Plugin + theme zsynchronizowane 1:1 do legacy katalogu asiaauto.pl (rollback backup). Backupy: `2026-04-21-pre-cutover.sql.gz`, `2026-04-21-plugin-theme-pre-rebrand.tar.gz`, `2026-04-21-mcp-test-asiaauto.tar.gz`. |
| 2026-04-21 | 0.30.15 | prod asiaauto.pl | Fix zapisu w panelu Ustawienia zamówień: 7× `admin_url('tools.php')` → `admin_url('admin.php')` w `class-asiaauto-order-admin.php` (handleSaveConfig + handleSaveEmailTemplates + handleSaveStatusDisplay + 3× reset + zakładki w renderConfigPage). Zaległość po 0.30.11 gdy submenu przeszło spod `tools.php` pod parent `asiaauto-orders`, ale redirecty zostały stare → po zapisie leciał 403 „Brak uprawnień". Drugi numer kontaktowy w dziale Kontakt i w stopce: `company_phone_2` (`+48 721 730 512`) w `asiaauto_order_config`, pole edytowalne w panelu (Dane firmy), `[aa_phone slot="2"]` w `class-asiaauto-shortcodes.php`, inline obok głównego telefonu w `class-asiaauto-contact.php` (jeden wiersz, oba klikalne), nowy item w footer icon-list (post 93679) z dynamicznymi shortcodami `[aa_phone format="raw" slot="2"]` / `[aa_phone slot="2"]`. |
| 2026-04-20 | 0.30.14 | prod asiaauto.pl | Sesja 7: Prima Auto rebranding na stronie głównej — schema.org name="Prima Auto", hero sub bez "homologacja", USP rozszerzone 3→6 (grid 2×3 desktop, 9 nowych ikon SVG), tytuł "Dlaczego Prima Auto", nowa sekcja "W cenie" z 6 pozycjami pakietu CIF (język, głos, ładowarka 7kW, przejściówka, kluczyk, filtry), fuel header poprawiony "Rodzaj paliwa". Umowa PDF §6: "Przygotowanie auta do odbioru, tłumaczenie dokumentów, obsługa akcyzy, przegląd i ubezpieczenie na miesiąc" (zamiast "Homologacja, przygotowanie pojazdu, rejestracja"). Admin kursy: UI odwrócony na USD→CNY (np. 6.80) z automatyczną inwersją przy zapisie, storage nadal jako `kurs_cny_usd`, pipeline cenowy bez zmian. Inventory: nowy filtr `drive` (taxonomy Motors Pro, AWD/FWD/RWD, URL param `?naped=`, REST counters endpoint). Shortcody cenowe: `[asiaauto_price_netto]` (cena netto, format identyczny jak `[asiaauto_price]`), `[asiaauto_price_breakdown]` (brutto czerwony 24/30px + VAT + netto w kolumnie obok, flex 50%, breakpoint 768px), `[asiaauto_included]` (ul z 6 bulletami pakietu CIF). Elementor template single-listing (ID 101874): podmiana 2 widgetów html → icon-list via one-shot skrypt `scripts/replace-singleelementor-htmlwith-iconlist.php`, backup JSON w `/home/host476470/backups/primaauto/`. |
| 2026-04-20 | 0.30.13 | prod asiaauto.pl | Fix ghost-crona `asiaauto_daily_cleanup`: event był zaschedulowany ale brakował `add_action('asiaauto_daily_cleanup')` w bootstrapie (bliźniaczy bug do `asiaauto_sync_changes` naprawionego w 0.30.12 — przegapiony). Handler wywołuje `AsiaAuto_Rotation::cleanup()`. Jednorazowy run po wgraniu: 166 drafts→trash, 2077 trash→permanent delete (starsze niż 7d), 0 orphaned images. Stan trash: 5470 → 3559. Reszta (głównie 3141 z purge 04-17) wyleci naturalnie w kolejnych dniach. |
| 2026-04-17 | 0.30.12 | prod asiaauto.pl | Sesja 6: cron bug fixes — `cron_schedules` filter rejestruje `asiaauto_15min` (interval 900s); `add_action('asiaauto_sync_changes')` handler wywołuje `AsiaAuto_Sync::run('dongchedi')` (wcześniej event był ghostem bez handlera, sync leciał tylko przez CLI). ZADANIE 6 Krok D: purge 2905 listings (marki OR miasta poza listą), bulk-import-by-brand.php (reverse-order pages + MAX_PAGES=50 + probe [50,40,30,20,10,5,2,1], parametr `mark=X` nie `brand=`), fix-missing-images.php (45/45: 15 OK + 30 TRASH ghost-offers po expired CDN x-expires). UX fix: modal miast auto-fill 31 defaults gdy saved=[]. Net: publish 736→809 (+73), cron zweryfikowany działa. |
| 2026-04-17 | 0.30.11 | prod asiaauto.pl | Uprawnienia sprzedawcy: nowy cap `manage_asiaauto_import` (rola `primaauto` + admin), trzy strony admina wychodzą spod `tools.php`: Konfigurator importu → `Ogłoszenia → Konfigurator importu` (IMPORT_CAP), Dodaj z Dongchedi → nadal pod Ogłoszeniami (IMPORT_CAP, + 2× AJAX), Ustawienia zamówień → submenu pod głównym menu Zamówień (ORDER_CAP). Globalny `menu_order` filter: Kokpit → Zamówienia → Ogłoszenia → Strony → reszta. `grantAdminImportCap()` w activation hooku, one-shot uruchomiony przez `wp eval` na prod. |
| 2026-04-17 | 0.30.10 | prod asiaauto.pl | ZADANIE 6 Krok A+B: filtr miast (31 domyślnych z Guangdong/Fujian/Guangxi/Hainan, modal w panelu admina, dodawanie custom), filtr w `isAllowedByConfig`. Perf: transient cache na 3× COUNT postmeta (10min TTL, invalidate po bulk recalc). Trash TTL: 30d → 7d. |
| 2026-04-17 | 0.30.9 | prod asiaauto.pl | B2 SEO: meta/OG/title dla single listing + inventory, blok SEO marki/modelu, term meta `asiaauto_seo_desc`, min-price transient, `llms.txt`. |
| 2026-04-17 | 0.30.8 | prod asiaauto.pl | Załączniki PDF (akcyza 0% widoczna), token w nazwie PDF, UPLOAD_DIR→contracts, nr umowy w tytule przelewu, info o podpisach w wizardzie. Bootstrap odtworzony po uszkodzeniu sed. |
| 2026-04-16 | 0.30.7 | prod asiaauto.pl | Sesja 2: CIF fix, panel klienta, version bump. ZADANIE 5 core DONE. |
| 2026-04-15 | 0.29.0-wip | prod asiaauto.pl | Bootstrap repo primaauto. PHP lint clean (PHP 8.3). Pending: v0.30.6 (3 patche, nie wgrane). |
