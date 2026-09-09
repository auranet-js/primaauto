# Hub BYD Datang (Great Tang) — treść + antykanibalizacja

> 2026-09-09 · term 7244 (`/samochody/byd/datang/`) · metoda: `docs/seo/hub-rework-method-2026-05-30.md`

## Punkt wyjścia

Hub wjechał 05.09 z jedną ręcznie zaimportowaną ofertą (post 464783, 291 000 zł, wersja
`纯电 850KM 四驱激光雷达旗舰型`, katalog Autohome specid 76834 — 186 parametrów). Miał wyłącznie
auto-generowany `rank_math_title`/`description`. Brak: `_serie_full_title` (H1 leciał bez marki:
„Datang — import z Chin"), `_asiaauto_lead`, `asiaauto_wiki_body`, `asiaauto_faq_json`,
`_asiaauto_h1_suffix`, `_asiaauto_pl_availability`. GSC 90 dni: 0 impresji.

## Nazewnictwo — decyzja

Producent na własnej stronie (`byd.com/cn/dynasty-home/models/tang/da-tang-ev`) używa **大唐EV /
Datang EV**, slug URL `da-tang-ev`, alias **Great Tang**. W polskich mediach i u CNEVPost dominuje
pisownia rozdzielna „Da Tang".

- nazwa termu **zostaje `Datang`** → zero ruchu w tytułach odświeżanych ofert
  (patrz `reference_nazwa_termu_serie_idzie_do_tytulu_oferty`),
- `_serie_full_title` = **`BYD Datang (Great Tang)`** — wzorzec `Exeed VX (Omoda 11)`; naprawia H1
  bez marki i wnosi alias do title/H1,
- slug **`datang` nietknięty** → zero 301,
- pisownia „Da Tang" i znaki 大唐 pokryte w treści i FAQ.

## Kanibalizacja — nie ma jej

Rodzina Tang to **osobne modele**, nie warianty jednego: Tang DM-i, Tang EV, Tang L DM, Tang L EV
(eksportowo Sealion 8), a teraz dochodzi Datang EV. Każdy ma własny hub i własną intencję —
korekta Janka 09.09 do pierwotnej, błędnej diagnozy „konflikt hub-vs-hub".

Stan pomiarowy dla porządku: `/samochody/byd/tang-dm-i/` (90 dni: 69 klików / 2622 impresje /
poz. 9,0) trzyma „byd tang" (DFS 4400/mc) i „byd tang cena" (720/mc) i pokazywał się też na
„byd da tang" (2 impresje, poz. 9,5) — po prostu dlatego, że strony Datanga jeszcze nie było.
Frazy `byd da tang` / `byd datang` / `byd da tang cena` **nie mają jeszcze wolumenu w DFS** —
model wszedł do sprzedaży 17.06.2026. Sekcja rozróżniająca modele w treści zostaje, ale jako
nawigacja dla czytelnika i rozdzielenie encji, nie jako obrona przed kanibalizacją.

Artykuły (obie strony rankują, intencja zdarzeniowa, nie transakcyjna):

| URL | 90 dni (klik / imp / poz.) |
|---|---|
| `/aktualnosci/byd-da-tang-ev-10-000-dostaw-w-miesiac-po-premierze/` | 1 / 61 / 5,6 |
| `/aktualnosci/byd-tang-trzeciej-generacji-zadebiutuje-w-chengdu-21-sierpnia/` | 1 / 45 / 4,4 |

**Kanonikala z newsów na hub NIE ustawiamy.** To nie duplikaty — inna treść i inna intencja.
Canonical wyrzuciłby z indeksu dwie strony rankujące na poz. 4–6, a przy niepodobnych treściach
Google i tak zwykle go ignoruje. Właściwe narzędzie: rozjazd intencji w title/H1 + linkowanie.

## Co wdrożone

Skrypt: `scripts/hub-content-datang-2026-09-09.php` (nośnik zapisu; treść generował model
z `extra_prep` + katalogu Autohome + danych z newsów). Flaga `_asiaauto_seo_rework=v1-2026-09-09`.

1. Hub `datang`: `_serie_full_title`, `_asiaauto_lead`, `_asiaauto_h1_suffix`
   (`cena w Polsce i import z Chin`), `_asiaauto_pl_availability=brand_in_pl_model_import_only`,
   `asiaauto_wiki_body` (7×H2, 8010 zn.), `asiaauto_faq_json` (5 pytań), regeneracja title/desc.
2. Sekcja „Datang a pozostałe modele z rodziny Tang" w wiki huba Datang — Tang DM-i, Tang EV,
   Tang L DM/EV (eksportowo Sealion 8), III generacja Tanga — z linkami do `tang-dm-i`,
   `sealion-8-dm-i` i `sealion-8-ev`.
3. Wstawka odwrotna w hubie `tang-dm-i` (H2 „Rodzina Tang — Tang, Tang EV, Tang L i Datang",
   wstawiona przed sekcją importu, additive) z linkiem do `datang`.
4. Linki z obu newsów: pierwsza wzmianka w treści + przepisane CTA na końcu → hub modelu
   (wcześniej linkowały tylko do `/samochody/byd/`).
5. `index-submit --type URL_UPDATED` na hub (1/100 budżetu ad-hoc 09.09).

Backup: `~/backups/primaauto/2026-09-09/` (termmeta 7244+3700, treść obu newsów).

Smoke: hub HTTP 200, H1 `BYD Datang (Great Tang) — cena w Polsce i import z Chin`,
title z aliasem, FAQPage parsuje (5 pytań), `{{LISTINGS_BAR}}` skonsumowany, 3786 słów na stronie,
linki dwukierunkowe potwierdzone curl-em.

## Znaleziska obok (nie ruszane)

- **DMC 3575 kg** (`full_load_weight` z katalogu Autohome) przekracza 3500 kg z kategorii B.
  W treści podana jest sama liczba, bez wniosków prawnych — do sprawdzenia z homologacją, bo jeśli
  polska homologacja indywidualna przepisze tę wartość, część klientów nie ma prawa jazdy na to auto.
- `AsiaAuto_HubTitleGenerator::buildDescription()` nie odmienia liczebnika — dla count=1 wychodzi
  „1 egzemplarzy" (widoczne w description hubów z jedną ofertą). `buildTitle()` odmienia poprawnie.

## Re-check

Za 3–4 tygodnie (ok. 07.10): GSC dla `/samochody/byd/datang/` — czy zbiera „da tang / datang"
i czy `tang-dm-i` nie stracił na „byd tang". Wolumen fraz „da tang" w DFS sprawdzić ponownie.
