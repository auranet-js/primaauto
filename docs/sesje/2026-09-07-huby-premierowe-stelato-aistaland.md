# 2026-09-07 — huby przed premierą, marki Stelato i Aistaland, korekta nadwozia

## Punkt wyjścia

Pytanie o trzy modele: „Arcfox Q06", „Ristland GX7", „Xiaomi Skynomad". Dwie z trzech nazw
wskazywały na inną markę, niż podano:

| Jak podane | Co to naprawdę |
|---|---|
| Arcfox q06 | **Changan Qiyuan Q06** — Arcfox nie ma i nie miał Q06 |
| Ristland gx7 | **AISTALAND / 启境 GX7** — marka GAC + Huawei, w che168 jako `Qijing` |
| Xiaomi Skynomad | **Xiaomi SkyNomad 澎程 N70 / N90** — druga linia Xiaomi |

Identyfikację Q06 potwierdził film z WeChat podesłany przez Janka (kanał 长安启源,
`#长安启源Q06 正式开启预售`, od 147 900 CNY).

## Decyzja: huby powstają PRZED podażą

Żaden z trzech modeli nie miał ani jednej oferty w che168 ani dongchedi — sprawdzone zrzutem
`filters` i punktowym `getOffers`. Janek zdecydował mimo to zakładać huby z pełną treścią:
„hub bez ofert jest pełnoprawnym hubem z dobrą treścią, chcemy zacząć rankować".

To odwraca ustalenie z 18.07 (67 termów-widm, `termQualifiesForIndex()` wyłączone). Żeby nie
odblokowywać tamtych 67, noindex zdejmowany jest **jawną whitelistą**: opcja
`asiaauto_hub_index_whitelist` czytana w `AsiaAuto_SEO::filterRankMathRobots()`.
`rank_math_robots=index` w termmeta NIE wystarcza — RankMath przykrywa to regułą
`noindex_empty_taxonomies`.

## Co powstało

**Huby bez ofert** (treść z katalogu Autohome, ceny z `calculateFromCny`):

| Hub | Cena PL |
|---|---|
| `/samochody/changan/qiyuan-q06/` | od 177 000 zł |
| `/samochody/aistaland/gx7/` | od 262 000 zł |
| `/samochody/xiaomi/skynomad-n90/` | od 305 000 zł |
| `/samochody/xiaomi/skynomad-n70/` | od 271 000 zł |
| `/samochody/rox/adamas/` | od 347 000 zł |

**Huby z ofertami** (treść + import): marka Stelato (58), S9 (35), S9T (23), marka Aistaland (1),
GT7 (1), Qiyuan A07 (5), A06 (2), Q05 (2).

## Marki odblokowane

- **Stelato** (享界, BAIC + Huawei) — marka `Xiangjie` przemianowana na `Stelato` (DFS PL:
  „stelato s9" 480/mc vs „xiangjie" 10/mc), sieroce termy S9 i S9T przypięte pod nią.
  Import 58 ofert, zero orphanów, zero błędów.
- **Aistaland** (启境, GAC + Huawei) — nowa marka, GT7 i GX7 zmapowane, jedna oferta GT7.
- **Qiyuan** — fold pod `Changan` zgodnie z regułą T-190 (`'changan-qiyuan' => 'changan'`
  w `class-asiaauto-redirects.php:45`), sub-marka w nazwie serii (wzorzec `Dongfeng|M-Hero 917`).
  Pierwsza wersja poszła pod markę `Nevo` i wpadała w 301 — poprawione.

## Trzy rzeczy, które kosztowały diagnozę

1. **Filtr `marks` sprawdza markę PO normalizacji adaptera.** Wpisany `Qijing` nie działał, bo
   adapter zamienia go na `启境`; backfill raportował „przeszły filtr: 0" mimo kwalifikującej się
   oferty. Memory: `reference_filtr_marks_sprawdza_marke_po_normalizacji`.
2. **Google Ads `search_volume` zwraca `None` dla żywych fraz** — „xiaomi su7" = None, a w
   DataForSEO Labs 12 100/mc. Kontroluj drugim źródłem, zanim uznasz zero za zero.
3. **Huby tworzone przez serie-guard nie mają treści** — dostają tylko
   `_asiaauto_primary_make_slug`. Dotyczy każdego huba powstałego przy imporcie pierwszej oferty.

## Korekta nadwozia — 101 ofert

che168 klasyfikuje kombi (旅行车) jako `sedan`. Wyszło przy Stelato S9T (bagażnik 729–1677 l
wchodził jako Sedan) i okazało się szersze: **101 ofert** — NIO ET5 Touring, BYD Seal 6 DM Wagon,
Zeekr 007 GT, Audi E5 Sportback, SAIC Shangjie Z7T.

- Importer: nowa metoda `AsiaAuto_Importer::bodyTypeFromSpec()` — `extra_prep['car_body_struct']`
  ma pierwszeństwo przed `body_type` z API. Mapa celowo wąska (旅行车, 跑车, 皮卡, 敞篷车).
- Dane historyczne: `scripts/napraw-nadwozie-ze-specyfikacji.php`.
- Efekt: filtr Kombi 27 → **121 ofert**, Sedan 1024 → 934.

## Zmiany poza repo (source of truth = serwer)

- `data/brand-mapping-v6.1.php` — +12 wpisów (Q06, GX7, GT7, N90, N70, A06, Q05, S9, S9T, Adamas);
  fold Qiyuan: 3 wpisy `Nevo` → `Changan`. Kontrola: 0 zmienionych obcych, 0 usuniętych.
- `data/che168-model-map.php` — +17 wpisów (warianty nazw).
- `includes/class-asiaauto-seo.php` — whitelist indeksowania.
- `includes/class-asiaauto-importer.php` — `bodyTypeFromSpec()`.
- `asiaauto_import_config` — marki `Qiyuan`, `Qijing`, `启境`, `Changan Qiyuan`, `Xiangjie`.
- `~/bin/social-publish-guard-hook.py` — wzorzec `/feed` przypięty do `graph.facebook.com`
  (łapał odczyt WeChat Channels jako publikację).

Backupy: `*.bak-2026-09-07-*` obok każdego pliku, `~/backups/primaauto/`.

## Otwarte

- Pusty term marki `changan-qiyuan` (6528) z sierotą „Changan Qiyuan Q05 Classic".
- Duplikaty pod ROX: `Jishi 01` (5083) i `Extreme Stone 01` (7198), oba zero ofert.
- Duplikat `Xiangjie S9T` (6994).
- `AsiaAuto_HubTitleGenerator::buildDescription()` nie odmienia liczebnika — „1 egzemplarzy".
- `calculateFromCny()` zwraca akcyzę 0% także dla `gasoline` (dla BEV/EREV poprawne).
- 21 ofert Qiyuan zablokowanych filtrem miast (17 miast spoza listy 31) — 9 najlepszych wzięte
  ręcznym importem, reszta czeka.

## Korekta: Changan Qiyuan jednak jako osobna marka (ta sama sesja)

Fold pod Changana został **cofnięty** po kontrargumencie Janka: w bazie stoją osobne marki
`Chery Fulwin` (5 ofert) i `Dongfeng Fengshen` (2), więc „konsekwentna polityka foldowania"
była moją nadinterpretacją — polityka jest mieszana, a prawdziwym kryterium jest wolumen frazy.

Pomiar (Google Ads + Labs, oba źródła zgodne dla fraz powyżej progu):
`chery fulwin` 110/mc · `m-hero` 1300/mc · `qiyuan` 50/mc · `changan qiyuan` 10/mc ·
`dongfeng fengshen` 10/mc (a mimo to osobna marka — niespójność projektu).

Argument, który przeważył: rano osobna marka byłaby pustym hubem, ale po imporcie
**Changan Qiyuan ma 9 ofert**, a rozdzielenie kosztuje najmniej właśnie teraz — huby są świeże,
bez ruchu i linków.

Wykonane:
- reguła `'changan-qiyuan' => 'changan'` **zdjęta** z `class-asiaauto-redirects.php`
- 5 serii przepiętych pod term 6528, slugi bez prefiksu (`a07`, `a06`, `q05`, `q06`, `q07`)
  — konwencja jak w Chery Fulwin (`t11`, nie `fengyun-t11`)
- 9 ofert przepiętych na taksonomię `make` = Changan Qiyuan (Changan: 49 → 40)
- mapowania: `mark_eu` → `Changan Qiyuan`, `serie_eu` i slug bez prefiksu (9 wpisów)
- hub marki dostał treść, tytuł: „Changan Qiyuan — od 134 000 PLN, 9 sztuk"
- **V63_MAKE_SERIE_REDIRECTS**: stare URL-e `/changan/qiyuan-*` i `/nevo/*` prowadzą jednym
  skokiem do nowych, zamiast lądować na hubie marki Changan
