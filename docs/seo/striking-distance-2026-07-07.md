# Striking-distance — title/meta refresh pod intent „cena" (2026-07-07)

Wynik audytu widoczności `tmp/seo-audyt-2026-07-07.md` (sekcja 3+7, rekomendacja #2). Huby rankują (pozycja 5–12), ale tracą CTR na frazach `{model} cena`, bo generowany title (`{Model} — od X PLN, N sztuk | Import z Chin`) **nie zawiera słowa „cena"**. Rozwiązanie = per-term override w formacie działających pilotów (leopard/zeekr, potwierdzone przez T-017) + `skip_regen`.

## Zakres — 4 huby serie

| term_id | slug | make | GSC (audyt, 28d) |
|---|---|---|---|
| 3644 | monjaro | geely | „monjaro cena" 759 wyśw, p12,3, CTR 0,1% |
| 4660 | z9-gt-dm-i | denza | „denza z9 gt cena" 538 wyśw, p10, CTR 0,9% |
| 5150 | yu7 | xiaomi | „xiaomi yu7 cena" 519 wyśw, p8,8, CTR 3,5% |
| 6564 | shark-6 | byd | ⭐ „byd shark" 3 902 wyśw, p9, CTR 0,9% (największy pojedynczy potencjał) |

`byd leopard 7` (6066) z rekomendacji **pominięty świadomie** — już ma format „cena w Polsce" + `skip_regen=1` (pilot 05-30).

## Co zmienione (term meta, prod DB `wp7j_`)

Dla każdego termu:
- `rank_math_title` = `{Model} cena w Polsce 2026 — od {min_price} PLN | Prima-Auto` (min liczony z bazy w momencie zapisu, `{model}` = dokładna fraza GSC — „BYD Shark" nie „Shark 6", „Denza Z9 GT" nie „Z9 GT DM-i”)
- `rank_math_description` + `asiaauto_seo_desc` = wariant „— cena w Polsce od X PLN. {typ nadwozia z leadu} z importu z Chin: homologacja indywidualna, gwarancja importera. Sprawdź ceny i dostępność."
- `_asiaauto_skip_title_regen` = `1` — **konieczne**: bez tego dzienny cron `asiaauto_regen_hub_titles_daily` + hook `asiaauto_after_set_taxonomies` przywróciłyby stary format.
- `_asiaauto_seo_rework` = `v2-striking-2026-07-07`

Finalne tytuły (smoke, żywy HTML, 4×HTTP 200):
- Geely Monjaro cena w Polsce 2026 — od 121 000 PLN | Prima-Auto
- Denza Z9 GT cena w Polsce 2026 — od 210 000 PLN | Prima-Auto
- Xiaomi YU7 cena w Polsce 2026 — od 234 000 PLN | Prima-Auto
- BYD Shark cena w Polsce 2026 — od 245 000 PLN | Prima-Auto

## Re-indexing

4 URL (`/samochody/{geely/monjaro, denza/z9-gt-dm-i, xiaomi/yu7, byd/shark-6}/`) zgłoszone przez `~/bin/index-submit --project primaauto --type URL_UPDATED` (budżet ad-hoc po: 22/100, w rezerwie).

## Rollback

- Backup meta: `~/backups/primaauto/2026-07-07/termmeta-pre-striking-distance.sql` (cała `wp7j_termmeta`).
- Cofnięcie override: usunąć `_asiaauto_skip_title_regen` na 4 termach → następny cron/regen odbuduje stary format „— od X PLN, N sztuk".
- Skrypt: `scratchpad` sesji (`striking-distance-titles.php`, tryb dry-run/apply).

## Znane ograniczenie

`skip_regen=1` zamraża term → `od X PLN` **nie odświeży się automatycznie** przy zmianie cen (jak 6 wcześniejszych pilotów). Liczba sztuk celowo pominięta w title/desc, by nie starzała się. Wymaga okresowego ręcznego refreshu min-ceny.

---

## Kontekst — inne rekomendacje audytu (stan)

- #1 Homepage „importer samochodów z Chin" — ✅ commit `4ad8c08` (v0.33.15, hero-sub + o-nas).
- #3 Huby bez opisu — ✅ 14 modeli, `asiaauto_wiki_body` (3–5 KB) + `asiaauto_faq_json` (5×Q) + lead, `rework=v1-2026-07-07` (content `tmp/content/*`). `li-l7` pod kanonicznym 5739.
