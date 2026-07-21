# T-214 — Dział wiedzy: Leksykon + Magazyn newsowy + Blog (wspólny silnik, bez n8n)

> Status: **F0–F3 WDROŻONE 2026-07-21, zaakceptowane przez Janka („podoba mi się")**. Live: 10 haseł Słownika (/wiki/, index+follow, w llms.txt, zgłoszone do indeksacji) + 2 newsy (/aktualnosci/) + cron newsowy 06:45 (mail akceptacyjny) + menu Słownik/Aktualności. Pozostało: F2 Tier 2 (~80 haseł hurtowo generatorem), auto-linker (filtr w renderach ofert/hubów — strefa ZAWSZE PYTAJ), sekcje dynamiczne „auta z technologią" pod hasłami, F4 blog/rankingi.
>
> **Lekcje z wdrożenia 21.07 (WAŻNE dla Tier 2):** (1) generacja = JEDEN przebieg claude -p (draft+samokontrola+korekta w jednym prompcie, Opus, 3 równolegle ~2,5 min/hasło) — NIE osobne procesy na recenzję/korektę (15 min/hasło przez narzut startu); (2) research w sieci PRZED pisaniem obowiązkowy (claude -p --allowedTools WebSearch,WebFetch) — bez niego hasła typu Blade Battery nie znają generacji; (3) FAQ z realnych PAA (DFS SERP ~$0.002/hasło), nie syntetyczne; (4) H2 zawsze z frazą w odmianie, nigdy generyczne „Jak to działa"; (5) frazy główne z DFS przed generacją (hybryda plug in 9900 > phev 2400); (6) okładki brandowe = og:image i kafle, NIE hero w treści (dublują tytuł); zdjęcia w treści = kadry z galerii ofert (technologie widoczne) + diagramy HTML/CSS .pa-diagram (niewidoczne); (7) nazwa sekcji „Słownik" (nie „Leksykon").
> Rozmiar: XL, fazowany · Godziny realnie: **76–102 h silnik (F0–F4)** + nadzór ~3–5 h/tydz na starcie
> Pełny plan (analiza + liczby + architektura): https://auratest.pl/fe4f58fec53ctmp/primaauto-plan-dzial-wiedzy-2026-07-21.html
> Nadrzędny wobec: **T-162** (blog — filar C tego planu, strategia D2/D3 z T-162 obowiązuje) · buduje fact-check z **T-208 §1**

## Cel

Prima Auto = polskie źródło prawdy o chińskiej motoryzacji. Trzy sekcje na jednym silniku generowania (Claude API, wzorzec wieloetapowy z workflowów Laguz przeniesiony do Pythona — bez n8n):

- **A. Leksykon `/wiki/`** — ~120 haseł technologii (PSM, LFP, Blade Battery, EREV, 800V, LiDAR, Devialet, V2L…) z danych `_asiaauto_extra_prep` + **auto-linker** (filtr `asiaauto_autolink_html` w 4 punktach renderu: techSpecs, equipment, opis oferty, wiki+FAQ hubów) + sekcje dynamiczne „auta z tą technologią" (linkowanie dwukierunkowe). Schema DefinedTerm/DefinedTermSet + FAQPage.
- **B. Magazyn `/aktualnosci/`** — codziennie 1–3 newsy z rynku CN. Radar RSS: carnewschina.com/feed (~7/dz) + cnevpost.com/feed (~7/dz) + electrive + insideevs (zweryfikowane 21.07). Cron 06:45, publikacja 08:00–09:00 CET tego samego dnia co źródła EN. NewsArticle + news sitemap (moduł Google News RankMath Pro — nieaktywny, włączyć) + strona „O redakcji" (wymóg Google News i kont prasowych). Google News/Discover = algorytmiczne, bez zgłoszeń.
- **C. Blog `/rankingi/` · `/porownania/` · `/poradniki/`** — realizacja T-162 (rankingi data-driven z naszej bazy, porównania, marki). Filar E (newsy) wyjęty do Magazynu.

**Silnik wpisów (rozstrzygnięte):** Magazyn i Blog = **natywne wpisy WP** rozdzielone kategoriami (`aktualnosci`, `rankingi`, `porownania`, `poradniki`), permalink `/%category%/%postname%/` (0 opublikowanych postów → zmiana bezbolesna; strony i CPT `listings` nietknięte), RankMath strip category base. RSS/archiwa/BlogPosting/moduł News out-of-box. CPT tylko dla Leksykonu (`asiaauto_wiki`).

## Kluczowe fakty z analizy (2026-07-21)

- **Dane:** 2917/3060 ofert z pełnym spec JSON (324 pola); liczności: LFP 1246, LiDAR 1163, V2L 2118, pneumatyka 730, 800V 456, 12 marek audio (Devialet 98, Dynaudio 232 — vol 1600/mc!). Seed haseł = `data/translations-extra-prep*.php`. Gotcha: ~3–5% rekordów z zepsutymi escapami unicode.
- **Popyt:** Trends ×9 (2022→2025); brandy: hongqi 27k, xpeng/xiaomi su7 14,8k, zeekr 9,9k; content: chińskie marki aut 8,1k, chiński suv 5,4k. GSC 28 dni: 60k impresji, **rankujemy już na frazy newsowe** (byd shark 11,6k impresji, zeekr 9x cena poz. 2).
- **Luka:** nikt w PL nie robi codziennych newsów z rynku chińskiego (chinskisamochod.com = kilka/tydz, fokus EU).
- **Infra reuse:** generator hubów $0,07/tekst (**odzyskać z `~/backups/primaauto/2026-07-14/tmp-cleanup-1023.tar.gz`** → `scripts/kb/`), REST `facts-*`, gen-hero.py $0,035, crony llms 05:30/sitemap 05:40, wzorzec crona z guardami (cron-index-retry.sh), klucz `~/secrets/anthropic/api-key.txt`.
- **Routing wolny:** `/wiki/`, `/aktualnosci/`, `/blog/` — zero kolizji z rewrite hubów/redirectami. Blog fizycznie nie istnieje (0 szablonów w primaauto2026).
- **Obrazy — system pozyskiwania (plan §4.5):** kolejność źródeł: (1) **własne infografiki/schematy** SVG/AI z brandingiem (EREV, PSM vs ASM, LFP vs NMC, 800V — rdzeń Leksykonu, unikat) → (2) kadry z galerii własnych ofert (Devialet, HUD, lodówka — crop + link do auta) → (3) materiały prasowe producentów/dostawców (BYD, Geely, Devialet press, CATL) → (4) wolne licencje: Wikimedia Commons + **Openverse API** (krok automatyczny w pipeline, atrybucja CC = legalność) → (5) hero AI + embedy YT/X. **Rejestr licencji:** meta załącznika (źródło/licencja/URL/data), figcaption renderowany automatycznie z meta. NIE kopiujemy z Weibo; samo „podanie źródła" nie legalizuje — działa tylko przy press/CC.
- **Koszt AI:** ~$6–12/mc operacyjnie + ~$10–15 jednorazowo Leksykon.

## Fazy

| Faza | Zakres | h |
|---|---|---|
| F0 Fundamenty | 3 CPT + szablony motywu + menu/footer + schema + sitemapy/News + strony redakcyjne + llms-generatory | 18–24 |
| F1 Silnik | scripts/kb/ (7 etapów: collect→facts→draft→**fact-check**→media→publish→distribute), prompty 3 typów | 20–26 |
| F2 Leksykon MVP | 40 haseł Tier 1 + auto-linker + indeks /wiki/ | 16–22 |
| F3 Magazyn start | cron 06:45 tryb draft+akcept (2 tyg.) → decyzja auto-publish; rejestracje press roomów | 12–16 |
| F4 Blog | 5 pierwszych rankingów + gate kanibalizacyjny + cron 2×/tydz | 10–14 |
| F5 Autonomia | auto-publish, Tier 2 (~120 haseł), pomiar GSC, raport tygodniowy | 8–12 |

## Decyzje — ROZSTRZYGNIĘTE (Janek, 2026-07-21)

- **D1** Autorstwo: byline **„Redakcja Prima-Auto"** (Organization jako author w NewsArticle). Bez fikcyjnej postaci (ryzyko przy kontach prasowych/weryfikacjach, zero zysku); Janek i Ruslan nie figurują jako autorzy.
- **D2** Publikacja: **codzienny mail akceptacyjny** (send-to-jan) — tytuł + lead + podgląd + tokenowy link „Opublikuj" per news; auto-publish jako flaga na później.
- **D3** Magazyn i Blog = **natywne wpisy WP** z kategoriami (nie CPT); CPT tylko Leksykon.
- **D4** Podział puli Indexing API: **bez podziału na razie** — czas pokaże (być może rezygnacja z crona retry).
- **D5** URL-e: `/aktualnosci/` + `/rankingi/` + `/porownania/` + `/poradniki/` (kategorie, permalink `/%category%/%postname%/`) + `/wiki/{haslo}/` (CPT).
- **D6** Konta prasowe producentów (BYD media.byd.com/eu, Geely) = zwykła rejestracja formularzem (nazwa serwisu + mail), nie wymaga statusu prasy; zakładane po F0. Odmowa → publiczne materiały / foto z ofert / hero AI, bez blokera.

## Plan wdrożeniowy (sesja po sesji)

- **F0 (2–3 sesje, produkcja — checklista deploy):** (1) backup DB → permalinki `/%category%/%postname%/` + strip category base → smoke test /oferta/, /samochody/, /informacje/; (2) kategorie ×4 + user „Redakcja Prima-Auto"; (3) CPT `asiaauto_wiki` addytywnie + flush; (4) szablony motywu (single.php, category.php, wiki, indeks) jako wrappery na nową klasę renderującą w pluginie; (5) schema NewsArticle/Article/DefinedTerm w `class-asiaauto-seo.php` + sitemapy + moduł News + „O redakcji" + menu/footer + llms-generatory; (6) bump wersji + VERSIONS.md + commit.
- **F1 (2 sesje):** generator z backupu 14.07 → `scripts/kb/`; framework 7 etapów; prompty 3 typów; fact-check liczb; mail akceptacyjny + tokenowy endpoint „Opublikuj"; dry-run 2–3 próbki mailem do Janka.
- **F2 (2–3 sesje):** 40 haseł Tier 1 batchami po 10 (mail do akceptu); auto-linker — diff `class-asiaauto-single.php` do akceptu Janka (ZAWSZE PYTAJ); indeks /wiki/.
- **F3 (1 sesja + 2 tyg. obserwacji):** cron 06:45 + pierwsze maile akceptacyjne; Janek zakłada konta prasowe (BYD/Geely, gotowe dane do formularzy ode mnie); news sitemap live.
- **F4 (1–2 sesje):** 5 rankingów przez gate kanibalizacyjny GSC; cron 2×/tydz.
- **Od Janka:** „ok" na diffy stref kruchych (permalinki, single.php), kliki w maile akceptacyjne, konta prasowe. Każda faza = commit + smoke test; kill-switch plikowy od F1.

## Strefy kruche / reguły

- Auto-linker = addytywny filtr, **zero refaktoru** renderów ([[feedback_additive_not_fragile_zone]]); wpięcia w `class-asiaauto-shortcodes.php`, `class-asiaauto-single.php` (ZAWSZE PYTAJ), `class-asiaauto-brand-hub.php`, 2 templatki taxonomy.
- Reguła antykanibalizacyjna D2 z T-162 obowiązuje wszystkie sekcje; gate GSC przed publikacją bloga.
- Fact-check liczb = bramka bez wyjątków (piszemy o cenach 100–300 tys. zł).
- Pomiar TYLKO GSC (decyzja D6 z T-162).
- Crony krótkie z flock, zero rezydentów (limity LVE).
- Indexing wyłącznie przez `~/bin/index-submit`.
