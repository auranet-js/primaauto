# Historia wersji asiaauto-sync

| Data | Wersja | Źródło | Uwagi |
|------|--------|--------|-------|
| 2026-04-17 | 0.30.10 | prod asiaauto.pl | ZADANIE 6 Krok A+B: filtr miast (31 domyślnych z Guangdong/Fujian/Guangxi/Hainan, modal w panelu admina, dodawanie custom), filtr w `isAllowedByConfig`. Perf: transient cache na 3× COUNT postmeta (10min TTL, invalidate po bulk recalc). Trash TTL: 30d → 7d. |
| 2026-04-17 | 0.30.9 | prod asiaauto.pl | B2 SEO: meta/OG/title dla single listing + inventory, blok SEO marki/modelu, term meta `asiaauto_seo_desc`, min-price transient, `llms.txt`. |
| 2026-04-17 | 0.30.8 | prod asiaauto.pl | Załączniki PDF (akcyza 0% widoczna), token w nazwie PDF, UPLOAD_DIR→contracts, nr umowy w tytule przelewu, info o podpisach w wizardzie. Bootstrap odtworzony po uszkodzeniu sed. |
| 2026-04-16 | 0.30.7 | prod asiaauto.pl | Sesja 2: CIF fix, panel klienta, version bump. ZADANIE 5 core DONE. |
| 2026-04-15 | 0.29.0-wip | prod asiaauto.pl | Bootstrap repo primaauto. PHP lint clean (PHP 8.3). Pending: v0.30.6 (3 patche, nie wgrane). |
