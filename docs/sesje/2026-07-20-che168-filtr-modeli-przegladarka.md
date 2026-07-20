# Sesja 2026-07-20 — Che168: filtr modeli + przeglądarka ofert (v0.33.37-38)

> Element T-186 (`docs/roadmapa/T-186-che168-automat.md`). Spec: `docs/superpowers/specs/2026-07-20-che168-model-filter-design.md`, plan: `docs/superpowers/plans/2026-07-20-che168-model-filter.md`.
> **Wszystko GATED na `ASIAAUTO_CHE168_PREVIEW='js'`** (wp-config) — Ruslan nie widzi; testy Janka od 21.07.

## Co wdrożone (produkcja)

**v0.33.37 — konfigurator (etap 1):**
- Zakładka „Filtry importu" → subtabs Dongchedi | Che168 (subtabs widoczne tylko za gate'em; `?source=che168` bez gate'a spada na dongchedi; zapis che168 i refresh słownika też gated).
- Che168: marki z pełnego słownika API (`AsiaAuto_Che168_Dictionary`: `getFilters` w transiencie 7 dni + przycisk „Odśwież słownik"), **blacklista modeli per marka** (`model_blacklist`, klucze kanoniczne `"Mark|Model"`), rozwijane listy modeli z 🆕 dla orphanów.
- Egzekwowanie: addytywny blok na końcu `isAllowedByConfig()` (importer poza tym nietknięty). Ręczny import (`force=true`) omija blacklistę.
- Seed: `scripts/seed-che168-config.php` → 47 marek, limity z dongchedi (2024+, ≤40 tys. km, ≥85 tys. ¥, 31 miast), blacklista pusta, `enabled=true`.
- **33 domapowania**: 25 aliasów `data/che168-model-map.php` + 8 nowych modeli `data/brand-mapping-v6.1.php` (v6.3: Leapmotor T03, Dongfeng eπ007, WEY Gaoshan, Smart #1/#3, Lotus Eletre, Jetour Shanhai T1, BAIC BJ30). Trafialność ofert 2024-2026 w huby: **61% → ~81%**.

**v0.33.38 — przeglądarka „Przeglądaj Che168" (etap 2):**
- Podstrona pod Ogłoszeniami (`class-asiaauto-admin-che168-browse.php`); pozycja menu rejestrowana tylko za gate'em.
- Filtry: marka (pełny słownik 282+ marek, też spoza naszej listy), chipy modeli (max 5/zapytanie; nazwy kanoniczne, 🆕/⛔), rocznik od, cena od, miasta (całe Chiny / nasza lista). AJAX `asiaauto_che168_browse` → `getOffers` (mark+model+year server-side), miasta/cena u nas.
- Karty ze zdjęciami; „🔍 Podgląd" = **deep-link w nowej karcie** do „Dodaj z Che168" z `?inner_id=` (prefill + auto-dry-run) — import stamtąd. Modal podglądu istniał przejściowo i został USUNIĘTY na życzenie Janka (rewizja 20.07 wieczór).

## Bugi złapane w testach E2E (Chrome MCP, sesja js)

1. **Miniatury nie wstawały** — CDN Autohome (`2sc2.autoimg.cn`) tnie hotlinki po nagłówku Referer (błąd w ~300 ms). Fix: `<img referrerpolicy="no-referrer" loading="lazy">` zamiast CSS background-image (karty + modal). Po fixie 26/26 obrazków. **Nie dotyczy importu właściwego** — media pobiera serwer (bez browserowego Referera).
2. **Auto-podgląd deep-linka wisiał** — `wp is not defined`: inline skrypt odpalał `doFetch()` przed załadowaniem `wp-util` ze stopki. Fix: `DOMContentLoaded` fallback.
2b. **Deep-link lądował na liście ogłoszeń** — URL przez `esc_js()` → `&` zamienione na `&#038;`, `#` ucina URL jako fragment (pierwsza diagnoza „OPcache" BŁĘDNA). Fix: `esc_url_raw()`. Lekcja: URL-e do JS nigdy przez esc_js.
3. `alert()`/`confirm()` usunięte ze strony browse (blokują automatyzację; komunikaty w pasku statusu, import przez dwuklik).

## Etykiety marek CJK w przeglądarce (feedback Janka, koniec sesji)

Słownik giełdy ma 44 marki o chińskich nazwach. Nowy plik `data/che168-mark-names.php` — **13 pewnych etykiet** do UI (do API zawsze idzie klucz surowy): rodzina HIMA/Harmony (问界 AITO, 智界 Luxeed, 享界 Stelato, 尊界 Maextro, 尚界 Shangjie), 大通 Maxus (31 modeli!), 奇瑞QQ Chery QQ, 中国重汽 Sinotruk, 鑫源 Shineray, firefly萤火虫 Firefly (NIO), 奥迪AUDI AUDI (SAIC), 凯马 Kama, 银隆 Yinlong. Select sortowany po etykiecie, format `Luxeed (Zhijie) · 智界 (3)`. Pozostałe ~31 CJK = egzotyka (kampery 房车, ciężarówki) — celowo bez tłumaczenia (zła etykieta gorsza niż CJK); dorabiać w tym pliku w miarę potrzeb. Gotcha: PHP rzutuje numeryczny klucz `'212'` na int → w JS porównanie typów; fix `String()`.

## Fakty API (zweryfikowane, ważne dla T-186)

- Dostęp che168 **żyje po 15.07** (zapis w T-186 o wygaśnięciu nieaktualny).
- `getOffers` che168: filtruje server-side po `mark` (nie `brand`!), `model`, `year_from/to`; format event-feed `result[].data` + `meta.next_page` (bez total). Miasta tylko client-side (`address`).
- `getFilters` = pełny słownik marka→modele jednym strzałem (282 marki / 2524 pary).
- **Cron/sync che168 NIE działa** (potwierdzone: handler woła tylko `run('dongchedi')`; `run('che168')` nie występuje w kodzie) — automat to świadome Fazy 1-3 T-186.

## Analiza danych (fundament decyzji)

Sampling 3550 ofert 2024-2026 / 46 marek (241 requestów): 61% mapped przed domapowaniami; tylko **18% podaży w naszych 31 miastach** (decyzja Janka: lista zostaje); wolumeny: BYD/Leapmotor/Li Auto >2000 ofert każda, 16 marek >500. Raport: `https://auratest.pl/fe4f58fec53ctmp/primaauto-che168-modele-2024-2026-raport-2026-07-20.md` (+ JSON). Diffy+DEPLOY.md+kod: `https://auratest.pl/fe4f58fec53ctmp/primaauto-che168-filtr-modeli-2026-07-20/`.

## Otwarte / następne ruchy

1. **Testy Janka (od 21.07):** konfigurator (subtab Che168, blacklista round-trip) + przeglądarka (marki spoza listy, 🆕, podgląd/import/pełna analiza). Feedback → poprawki w fazie testów.
2. Po testach: **oddanie Ruslanowi** = dopisanie loginu do `ASIAAUTO_CHE168_PREVIEW` (albo zdjęcie gate'a — świadoma decyzja, jedna zmiana).
3. Strona konfiguratora che168 renderuje ~1,7 MB HTML (pełny słownik w DOM) — jeśli będzie mulić, doładowywanie modeli na żądanie.
4. Reszta orphanów: świadome skipy ICE (MG5, Mazda 3/CX-50, Qashqai/X-Trail, Emira) + nieustalone (Geometry E Firefly — decyzja fold marki; Galaxy Starburst — który to model; eπ Nano 01 — tnie go filtr ceny).
5. Automat che168 = T-186 Fazy 1-3 (Source Manager, dedup, panel Źródła) — osobna, duża robota; propozycja: automat pomija niezmapowane i loguje do przeglądu.

## Staging / rollback

Kopie robocze + diffy: `tmp/che168-filter-staging/` (gitignored). Backupy na serwerze: `*.bak-2026-07-20-che168filter` / `-t186` / `-v63` / `-browse` / `-referrer` / `-prefill`.
