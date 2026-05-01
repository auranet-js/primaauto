# Kolejka zadań — Prima Auto

> Aktualizacja: 2026-04-30 (fix mocy KM dla PHEV — analiza pól Dongchedi, wzorce BYD/Denza/non-BYD, plan wdrożenia)

---

## ZADANIE 15 — Fix mocy KM dla PHEV (single + inventory cards) ✅ DONE (0.32.30, 2026-05-01)

> Wdrożone 2026-05-01 wg planu poniżej. Helper `AsiaAuto_Inventory::resolvePower($post_id, $ep)` jako fuel-aware źródło prawdy, `class-asiaauto-single.php::power()` jako cienki wrapper. Weryfikacja klienta: wszystkie 8 testów PASS (Han DM-i, Z9 DM-i Ultra, N9 DM-i Premium, Leopard 7 PHEV, AITO M7 EREV, Volvo S90 T8 combined, Z9 GT EV bez regresji, benzynowiec bez regresji). Etykieta „Moc łączna" → „Moc" dynamicznie z helpera.

### Pierwotne zgłoszenie (2026-04-30)
> Status: zgłoszony przez klienta 2026-04-30 — „dla PHEV liczy źle, EV i benzyna OK". Analiza w pełni potwierdzona na próbce ~25 modeli (BYD, Denza, AITO, Geely, Chery, Hongqi, WEY, Changan, Volvo, Zeekr) + statystyka 614 PHEV w bazie. Kierunek poprawki uzgodniony.

### Diagnoza

Trzy renderery, dwa aktywne błędne:
- `class-asiaauto-inventory.php::parseSystemPower()` (linia 1159) — karty na `/samochody/`
- `class-asiaauto-single.php::power()` (linia 245) — strona pojedynczego ogłoszenia
- `class-asiaauto-shortcodes.php::resolvePower()` (linia 1066) — **logika wzorcowa**, ale shortcode nieaktywny w motywie primaauto2026

Pierwsze dwa opierają się głównie na polu `energy_elect_max_power` które dla PHEV jest niespójne lub puste. Fallback do `_asiaauto_horse_power` (meta) zwraca moc silnika SPALINOWEGO, nie systemu.

Statystyka PHEV (614 listings):
- `energy_elect_max_power`: 193 (32%) ⚠️ niespójne, czasem combined w KM, czasem kW, czasem null
- `electric_max_power`: 392 (64%) ✓ format `"{kW}({KM}Ps)"`
- `front_electric_max_horsepower` + `total_electric_power`: 613 (99,8%) ✓ **najbardziej wiarygodne**
- `engine_max_horsepower`: 613 ✓ moc samego silnika spalinowego

### Konkretne błędy zaobserwowane

| Auto (post ID) | Aktualnie (single) | Powinno być | Manufacturer |
|---|---|---|---|
| Denza Z9 DM-i Ultra (94535) | **152 kW (207 KM)** | 640 kW (870 KM) | 870 PS ✓ |
| Denza N9 DM-i Premium (145822) | 710 kW (965 KM) ⚠️ | 680 kW (925 KM) | 925 PS |
| BYD Han DM-i (96111) | **115 kW (156 KM)** | 200 kW (272 KM) | 272 PS |
| BYD Sealion 8 DM-p 4WD (111353) | **115 kW (156 KM)** | 400 kW (544 KM) | 544 PS |
| BYD Leopard 7 PHEV (168147) | **115 kW (156 KM)** | 360 kW (490 KM) | 490 PS |
| Volvo S90 T8 PHEV (242003) | 228 kW (310 KM) ⚠️ edge | 335 kW (455 KM) combined | 455 KM |

EV-y i benzynowe działają dobrze — bo dla EV `energy_elect_max_power = "{kW}({KM}Ps)"` zawiera moc systemu, a dla benzynowych zawiera moc silnika. PHEV wpada między te dwa wzorce.

### Plan wdrożenia

**Krok 1** — Backup obu plików z datą:
```bash
cd ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/
cp class-asiaauto-single.php class-asiaauto-single.php.bak-2026-04-30-power
cp class-asiaauto-inventory.php class-asiaauto-inventory.php.bak-2026-04-30-power
```

**Krok 2** — Wspólny helper `resolvePower(int $post_id, array $ep): array`. Najlepiej w `class-asiaauto-inventory.php` jako `public static`, a `class-asiaauto-single.php::power()` go używa (DRY). Sygnatura zwraca `['kw'=>int|null, 'km'=>int|null, 'display'=>string, 'label'=>string]`.

**Krok 3** — Logika (kolejność prób):

```
fuel_slug = get_the_terms($pid, 'fuel')[0]->slug
fuel_name = ...

is_phev_like = in_array(fuel_slug, ['phev','erev','hev']) 
            || str_contains(fuel_name, 'PHEV') 
            || str_contains(fuel_name, 'EREV') 
            || str_contains(fuel_name, 'HEV')
            || str_contains(fuel_name, 'Hybryda')
is_ev = in_array(fuel_slug, ['ev','bev']) || str_contains(fuel_name,'Elektryczny')

# PHEV/EREV/HEV/EV → moc systemu elektrycznego
if (is_phev_like || is_ev):
    front_hp  = (int) ($ep['front_electric_max_horsepower'] ?? 0)
    total_kw  = (int) ($ep['total_electric_power'] ?? 0)
    engine_kw = (int) ($ep['engine_max_power'] ?? 0)
    
    # Edge case: ICE dominuje (Volvo S90 T8, niektóre europejskie PHEV)
    # — pokaż combined zamiast samej elektrycznej
    if (is_phev_like && engine_kw > 0 && total_kw > 0 
        && engine_kw * 1.5 > total_kw):
        combined_kw = engine_kw + total_kw
        combined_km = (int) round(combined_kw * 1.36)
        return [kw=>combined_kw, km=>combined_km, 
                display=>"{combined_kw} kW ({combined_km} KM)", 
                label=>'Moc']
    
    # Standard: moc napędu elektrycznego (DM-i, EM-i, EREV, EV)
    if (front_hp > 0 && total_kw > 0):
        return [kw=>total_kw, km=>front_hp, 
                display=>"{total_kw} kW ({front_hp} KM)", 
                label=>'Moc']
    if (front_hp > 0):
        return [kw=>null, km=>front_hp, 
                display=>"{front_hp} KM", label=>'Moc']
    
    # Fallback 1: electric_max_power "kW(KMPs)"
    raw = $ep['electric_max_power'] ?? ''
    if preg_match('/^(\d+)\((\d+)Ps\)$/', trim(raw), m):
        return [kw=>(int)m[1], km=>(int)m[2], 
                display=>"{m[1]} kW ({m[2]} KM)", label=>'Moc']
    
    # Fallback 2: energy_elect_max_power TYLKO w formacie (NPs)
    # NIE używać raw (niejednoznaczne kW vs KM combined)
    raw = $ep['energy_elect_max_power'] ?? ''
    if preg_match('/^(\d+)\((\d+)Ps\)$/', trim(raw), m):
        return [kw=>(int)m[1], km=>(int)m[2], 
                display=>"{m[1]} kW ({m[2]} KM)", label=>'Moc']

# Benzyna/Diesel/inne — moc silnika
engine_hp = (int) ($ep['engine_max_horsepower'] ?? 0)
engine_kw = (int) ($ep['engine_max_power'] ?? 0)
if (engine_hp > 0 && engine_kw > 0):
    return [kw=>engine_kw, km=>engine_hp, 
            display=>"{engine_kw} kW ({engine_hp} KM)", label=>'Moc']

# Ostateczny fallback: meta `_asiaauto_horse_power` 
# (UWAGA: dla PHEV zawiera ICE-only — używać tylko gdy fuel = benzyna/diesel)
if (!is_phev_like && !is_ev):
    meta_hp = (int) get_post_meta($pid, '_asiaauto_horse_power', true)
    if (meta_hp > 0):
        kw = (int) round(meta_hp / 1.3596)
        return [kw=>kw, km=>meta_hp, 
                display=>"{kw} kW ({meta_hp} KM)", label=>'Moc']

return [kw=>null, km=>null, display=>'', label=>'']
```

**Krok 4** — `class-asiaauto-single.php`:
- Linia 230: `$pw = $this->power($d['ep']);` → zostawić sygnaturę, zmienić ciało
- Linia 231: etykieta `'Moc łączna'` → zmienić na `$pw['label']` (czyli `'Moc'`) — bo to nie jest combined
- Linia 245-256: zastąpić logikę nową (wywołanie helpera lub inline)
- Sprawdzić linia 535 i 687 (też używają `power()`) — powinno działać bez zmian

**Krok 5** — `class-asiaauto-inventory.php`:
- Linia 1124: `$hp = self::parseSystemPower($ep);` → zmienić na `$resolved = self::resolvePower($postId, $ep); $hp = $resolved['km'];`
- Linia 1159-1193: zastąpić `parseSystemPower($ep)` nową `resolvePower($postId, $ep)`
- Sygnatura zmienia się — przyjmuje też `$postId` żeby czytać taksonomię fuel

**Krok 6** — Testy weryfikacyjne (otworzyć w przeglądarce):
- `/samochody/byd/han/` — karta + single dla BYD Han DM-i (oczekiwane: 200 kW / 272 KM)
- Single Denza Z9 DM-i (post 94535) — oczekiwane: 640 kW / 870 KM
- Single Denza N9 DM-i (post 145822) — oczekiwane: 680 kW / 925 KM
- Single BYD Leopard 7 PHEV (168147) — oczekiwane: 360 kW / 490 KM
- Single AITO M7 EREV 4WD (244737) — oczekiwane: 330 kW / 449 KM (regresja test)
- Single dowolny EV np. Z9 GT EV (211197) — bez zmian: 710 kW / 966 KM
- Single dowolny benzynowiec — bez zmian
- Single Volvo S90 T8 PHEV (242003) — edge case combined: oczekiwane: 335 kW / 455 KM

**Krok 7** — Bump wersji + commit:
- `ASIAAUTO_VERSION` → bump (sprawdzić aktualną w `asiaauto-sync.php`, np. `0.32.29` → `0.32.30`)
- Commit message: `[fix:][single:][inventory:] vX.Y.Z — moc PHEV z front_electric_max_horsepower zamiast ICE-only`
- Update `docs/VERSIONS.md`

### Czego NIE robić
- ❌ NIE zmieniać `_asiaauto_horse_power` w bazie (614 listings × engine_hp; działa jako fallback dla benzynowych)
- ❌ NIE zmieniać importera (`class-asiaauto-importer.php:434` — `update_post_meta('_asiaauto_horse_power', $data['horse_power'])` — to celowo zapisuje ICE HP z API)
- ❌ NIE ruszać shortcode `resolvePower()` w `class-asiaauto-shortcodes.php` — jest poprawny, służy jako wzorzec; można go skonsolidować z nowym helperem później
- ❌ NIE ruszać MCP, contractu, statusów zamówień — to izolowana zmiana frontu

### Edge case'y do akceptacji
- **Zeekr 9X Ultra PHEV** (post 174380): Dongchedi nie ma combined power (1305 PS wg producenta), pokażemy 660 kW / 898 KM (sam napęd elektryczny). Akceptowalne — manufacturer combined niedostępne w API.
- **Hongqi HS7/HQ9** mają combined w `energy_elect_max_power` (358/300 KM) ale heuristyka wybierze `front_electric_max_horsepower` (324/286 KM). Różnica ~10%, nadal pokazuje moc realną. Akceptowalne.

### Memory
- Po wdrożeniu: dopisać do `project_session_2026_04_30_power_fix.md` (analiza pól Dongchedi PHEV, wnioski).
- Wzbogacić memory `reference_dongchedi_api_quirks.md` o sekcję power fields (`energy_elect_max_power` niespójne, `front_electric_max_horsepower` wiarygodne, `_asiaauto_horse_power` meta = ICE only).

---

## ZADANIE 14 — Panel diagnostyczny admina (asiaauto-sync) ✅ DONE (0.32.3, 2026-04-28)

Pluggable rejestr **10 checków** + Admin UI + WP-CLI + AJAX. Spec: `docs/superpowers/specs/2026-04-28-diagnostyka-admin-panel-design.md`. Plan: `docs/superpowers/plans/2026-04-28-diagnostyka-admin-panel.md`.

### Klastry (10)
- **Integralność (4):** missing-images, chinese-chars, broken-extra-prep, duplicate-listings
- **Pokrycie SEO (6):** make/serie-without-wiki, listings-without-mapping, mapping-without-term, serie-broken-parent, duplicate-serie-terms

### Workflow „dojdzie nowy model" (3 narzędzia w panelu)
1. **Popraw mapping** → `listings-without-mapping` (per-item form → append do `data/brand-mapping-v6.1.php`)
2. **Dodaj hub** → `mapping-without-term` (auto wp_insert_term) + `serie-broken-parent` (heurystyka parenta z listingów)
3. **Wygeneruj opis** → `make/serie-without-wiki` (POST do n8n webhook → wiki_body async ~30-60s)

### Punkty wejścia
- UI: WP admin → Listings → Diagnostyka
- CLI: `wp asiaauto diag list | run | run-all | preview-fix | apply-fix`
- AJAX: `asiaauto_diag_run | preview | apply` z capability `manage_options`

### Live findings (pierwszy run-all 2026-04-28)
- 32 listings bez zdjęć (auto-fix dostępny)
- 370 miejsc CN: 117 tytułów (re-translate) + 1 aktywny term (manual) + 252 orphany (bulk delete) — split w 0.32.3 + edytor mapy
- 1 złamany JSON extra_prep
- 4 aktywne marki bez wiki_body (po fixie meta_key w 0.32.2): Changan Qiyuan, Dongfeng Fengshen, GAC Aion Hyper, Wuling
- 66 aktywnych modeli bez wiki_body (po fixie 0.32.2; było mylone „303")
- **65 orphan termów serie** (parent=0 z listingami) — m.in. Zeekr 9X #6532 (artefakt migracji v6.1)
- **37 grup duplikatów serie** — m.in. Zeekr 9X jako `9x` #4824 + `zeekr-9x` #6532

### Pending v2
- **Rotator + cleanup ogłoszeń (osobny projekt + dokumentacja)** — wykryte 2026-04-28 przy testowaniu panelu. Trzy luki w `class-asiaauto-rotation.php`: (1) `trashOldDrafts` filtruje przez meta `_asiaauto_removed_at` — drafty bez tej meta (manual status change, legacy) ignorowane (z 64 overdue tylko 30 złapane), (2) `cleanOrphanedImages` filtruje przez `_asiaauto_source_url` — z 350 obecnych orphans 0 pasuje, (3) brak Plan D w importerze (`class-asiaauto-media.php` nie ustawia `post_parent` na `wp_insert_attachment` → przyszłe deletions zostawią sieroty). Realne tempo rotacji: ~220 nowych/dzień (mediana 14d, zakres 38-295), ~80/dzień do trash. Steady state: 700-1100 w trashu rolling 7d. Plan: A) fallback `post_modified_gmt`, B) usunąć filter source_url, C) Plan D, D) cleanup historyczny + ADR `2026-04-28-rotator-cleanup.md`. Akceptowano: zostać przy cron 1×/dziennie, TTL 7d trash do rozważenia.
- Klaster lifecycle (rotacja, orphan attachments, trash >30d permanent delete) — Plan D
- Klaster ops (filter cleanup, race detection alerts) — Plan A
- UI form-input modal dla `listings-without-mapping` (applyFix czeka na `$_POST['mappings']` ale JS go nie generuje — count=0 więc niegrający)
- Cron `asiaauto_diag_daily` z mailem alertem

- `duplicate-serie-terms`: heurystyka prefix 3-słów daje false-positives dla intencjonalnych sub-wariantów (EV vs DM-I) — można dodać whitelist po batch review
- **RankMath Pro integracja** (2026-04-28+, user instaluje teraz). Po deploy: weryfikacja konfliktów z `class-asiaauto-seo.php` (sitemap/robots/Car schema dublowanie), dezaktywacja naszych filtrów na rzecz RankMath. Pełen plan: memory `project_rankmath_pro_planning.md`. **n8n batch hub generation PAUZOWANY do tego czasu** — brakujące huby (`tmp/missing-hubs-2026-04-28.md`: 4 marki + 70 modeli, z czego 70 to orphan parent → fix `serie-broken-parent` najpierw) user dodaje ręcznie.
- **Chinese fragments intelligent analysis** (2026-04-28). 84 unmapped fragmenty CN — zamiast batch wpisywania user wymaga per-fragment analizy: gdzie używany, jak globalnie nazywa się, czy podobne istnieje w istniejącej mapie, propozycja EN. Raport: `tmp/chinese-fragments-analysis-2026-04-28.md`.

---

## ZADANIE 12 — Rollout SEO + Google Ads v2 (2026-04-22, aktualizowane 2026-04-24)

> Status: **PRAWIE DOMKNIĘTE** — mapowanie v6.1 DONE w 0.31.2, widoki + content pipeline DONE w 0.31.5, batch contentu DONE (45/47 make + 246/284 serie wiki coverage), AEO (llms.txt+full) DONE 2026-04-24, schema duplikat fix DONE 2026-04-24, Google Ads v2 SKAG/KI DONE w 2026-04-24 (memory `project_ads_ki_architecture.md`).

### Krok 1 — Migracja mapowania ✅ DONE (0.31.2, 2026-04-23)
- [x] Backup 4 tabel w `~/backups/primaauto/2026-04-23-v6.1-taxonomy/terms-112846.sql` (540KB)
- [x] 208 rename serie, 258 move_parent, 510 termmeta, 2 create serie, 8 nowych marek (BAIC, Beijing 212, Chery Fulwin, Exlantix, GAC, GWM, Luxeed, Nevo)
- [x] `class-asiaauto-mapping.php` singleton + `data/brand-mapping-v6.1.php` (260 pozycji) — importer mapuje CN→EU na wejściu
- [x] 301 redirects `redirectV61Brands()` (16 par: fangchengbao/yangwang→byd, galaxy→geely, itd.)
- [x] Batch update 930 post_title (`tmp/update-listing-titles.php`)
- [x] Smoke test 6 URL hubów 200 OK

### Krok 2 — SEO huby marek i modeli
**2a — widoki PHP ✅ DONE (0.31.5, 2026-04-23):**
- [x] `/marki/` — page ID 263572, template `page-marki.php` (grid 29 marek Top 8 + reszta alfabetycznie)
- [x] `taxonomy-make.php` — hero, wiki_body z `{{LISTINGS_BAR}}`, pigułki modeli (`aa-brand-card`), USP box, FAQ
- [x] `taxonomy-serie.php` — dwupoziomowy URL via `template_include`, tabelka 14+ wierszy specs, lista wyposażenia, drugi listing bar „Inne oferty marki", USP box, FAQ
- [x] Child theme `asiaauto/` rozszerzony (functions.php, style.css z wrapperami aa-home__*)
- [x] Strona główna: linki marek → huby (`get_term_link`) zamiast filtered inventory; „Wszystkie marki →" → `/marki/`

**2b — content pipeline n8n ✅ DONE (0.31.5, 2026-04-23):**
- [x] Nowa klasa `AsiaAuto_REST_Hub` — 5 GET + 1 POST endpointów (`facts-for-make/serie`, `latest-by-make/serie`, `aliases-for-make/serie`, POST `hub-content/{tax}/{id}`)
- [x] `factsForSerie` parsuje `_asiaauto_extra_prep` JSON (200+ chińskich kluczy) → specs + features_standard + features_optional + notable + taxonomies breakdown (fuel/drive/body/ca-year)
- [x] 2 workflow n8n (make + serie) na witold140-20140.wykr.es, pipeline: Webhook → 3× HTTP → Code build prompt → Claude Sonnet 4.6 (max 8000 tok, system prompt 4k) → Parse+Lint (regex wycina FAQ/„Modele"+ JSON safety) → Resolve term_id → POST save
- [x] Generator `tmp/generate-n8n-workflows.py` (gitignored JSON — klucze inline). Docelowo: klucze do n8n credentials + czysty JSON do `workflows/`
- [x] Pilot Voyah + Voyah FREE zweryfikowany (3 iteracje promptu)
- [x] Batch ~175 (45 marek + ~130 modeli), `tmp/batch-hub-parallel.sh` z xargs -P3, szacowany czas 60 min, koszt ~$25
- [x] Po batch: coverage 45/47 make + 246/284 serie (z 124/275 wieczór 23-04 — retry zadziałał na ~120 modeli). Pending: 2 marki + 38 modeli bez wiki_body (pewnie świeżo dodane lub failed)

**2c — Schema.org + llms.txt (pending):**
- [x] **Vehicle Schema + BreadcrumbList** na single listings (Car + BreadcrumbList JSON-LD w `class-asiaauto-single.php::renderMeta()` wp_head, Schema #1 z `render()` usunięta 2026-04-24 jako duplikat). OfferShippingDetails — pending, nice-to-have.
- [x] **llms.txt** (122 linie, top 20 marek + top 30 modeli + 7-krokowy proces + kontakt + AI hints) i **llms-full.txt** (667 linii / 48 KB, 47 marek z opisami + wszystkie modele). Deploy 2026-04-24. Generator `tmp/build-llms-full.php`. Patrz memory `reference_aeo_llms_files.md`.
- [x] **Prompt caching n8n** — wdrożone w v0.31.12 (memory `project_hub_pipeline_fix_2026_04_24.md`). Koszt €0,060/hub.
- [ ] OfferShippingDetails w Car schema na single listing (uzupełnienie do gap vs west-motors)
- [ ] Schema #2 (`renderMeta()`) wzbogacić o pola które miała Schema #1: `vehicleEngine` (KW), `vehicleTransmission`, `driveWheelConfiguration`, `color`, `itemCondition` — usunięte przy dedup 2026-04-24, można dograć

### Krok 3 — Google Ads v2 (równolegle z Krokiem 2, po podpięciu API)
- [x] **Google Ads API podpięte** (Basic Access od 2026-04-23, konto Prima-Auto 9506068500 direct, v21). Patrz memory `reference_google_ads_api_client.md`.
- [x] **3 ENABLED kampanie** (Brand 10/Topic 30/SKAG 60 zł/dz, suma 100 zł/dz). 37 grup SKAG, 262 KW, 37 RSA. Patrz memory `reference_google_seo_stack.md` i `project_ads_campaign_structure.md`.
- [x] **Negatywy** — 503 BROAD historyczne + 14 PHRASE gapiowe per Topic/SKAG (opinie/test/recenzja/parametry/specyfikacja/wymiary/premiera/youtube itd.).
- [x] **Architektura SKAG → KI** (2026-04-24): 37 per-model grup → 1 grupa „Długi ogon" z `{KeyWord}` H1 + per-KW finalUrl. Patrz memory `project_ads_ki_architecture.md`.
- [ ] Import konwersji GA4 → Ads (`click_phone`/`click_whatsapp`/`generate_lead`) — pending user-side w UI Ads (Conversions → Import from GA4). Czas propagacji 9h pierwszy raz.
- [ ] n8n workflow: eksport feedu produktowego do Google Merchant Center (decyzja architektoniczna pending)
- [ ] Po 7 dniach: search terms review w GAQL → dosypywać KW/negs

### Zależności i kolejność
```
Ruslan OK → Krok 1 (migracja DB+importer) → Krok 2a (templates) ─┐
                                          → Krok 2b (n8n content)┤→ uruchomienie huby
                                          → Krok 3 (Ads v2)      ┘ równolegle z 2
```

### Ryzyka
- ~~**Bez Kroku 1 Krok 3 jest bez sensu**~~ DONE — Krok 1 zrobiony 2026-04-23, Ads regenerowane pod nowe slugi
- ~~**Krok 2a bez Kroku 1**~~ DONE — kolejność dotrzymana
- ~~**Google Ads API developer_token**~~ DONE — Basic Access 2026-04-23, limit 15k ops/dobę

---

## ZADANIE 13 — Sesja SEO/AEO post-Elementor (2026-04-24) ✅ DONE

Pełen audyt + AEO wdrożenia po przełączeniu na motyw primaauto2026. Patrz memory `project_seo_aeo_session_2026_04_24.md`.

### Wykonane (8 punktów)
- [x] PSI mediana z 3 runów mobile + 1 desktop. Wniosek: regres mobile lab nie jest realnym problemem (desktop 99/0,6s, real users mają błyskawicznie). CrUX field brak (origin <28d).
- [x] GSC indeksacja audyt: 1/10 → 5/10 PASS (z 23-04 wieczór). Brand `primaauto` SERP #4. 4 huby (Chery/Voyah/byd-Seal/informacje) w cache crawl history Google, czekają na pełny index.
- [x] DataForSEO SERP baseline ($0,06 / 17 KW) — primaauto vs west-motors mapping. JSON `tmp/dfs-serp-2026-04-24.json`.
- [x] Internal linking audit: nic do zmiany. 3 ścieżki home/menu/footer do `/marki/`, stamtąd 47/47 marek.
- [x] llms.txt rozbudowane 36 → 122 linii. llms-full.txt nowe (667 linii / 48 KB). Generator `tmp/build-llms-full.php`.
- [x] Numer +48 605 335 559 (prywatny Janka) wywalony z 3 miejsc: llms.txt, header.php nowego motywu, fallback w wizardzie. Zastąpiony firmowym 721 730 507.
- [x] Author archive disclosure: `/?author=ID` + `/author/<login>/` → 301 → home + `is_author()` noindex + `author_link` filter zwraca home_url. Eliminuje login disclosure 9 userów.
- [x] Schema duplikat 2× `@type=Car` na single listing fix: `class-asiaauto-single.php:40` `echo $this->schema($d)` usunięty (Schema #2 z wp_head wystarczy + ma BreadcrumbList). Wynik: 2 JSON-LD zamiast 3.

### Pliki utworzone/zmodyfikowane
- Plugin: `class-asiaauto-seo.php` (+blockAuthorArchive, +filterAuthorLink, +filterRobots is_author), `class-asiaauto-single.php:40` (schema dedup), `class-asiaauto-order-wizard.php:452` (numer 605→721)
- Theme: `themes/primaauto2026/header.php:4-6` (numer 605→721 + use shortcode)
- Domain root: `llms.txt`, `llms-full.txt`
- Repo: `tmp/build-llms-full.php`, `tmp/dfs-serp-2026-04-24.json`, `tmp/psi-after-theme-2026-04-24*/`

### Pozostałe pending z dzisiejszego audytu
- [ ] Hub aa-hub vs aa-serie różnica mobile + horizontal scroll (memory `project_hub_layout_issues.md` częściowo nieaktualne — padding fix już wdrożony w v0.31.8). Wymaga screenshotu.
- [ ] 4 CSS chain blocking scalenie motywu (header+base+footer+hub w 1 plik) — kosmetyka pod mobile lab PSI, real user nie odczuje. Robić jako ostatnie gdy motyw się ustabilizuje.
- [ ] Schema #2 wzbogacić o vehicleEngine KW + transmission + drive + color + itemCondition (utracone przy dedup 2026-04-24).
- [ ] OfferShippingDetails w Car schema (gap vs west-motors).

---

## ZADANIE 11 — Strony frontowe marek + widoki hubów ✅ DONE (0.31.5, 2026-04-23)

Zrealizowane jako PHP templates w child theme `asiaauto/` (nie Elementor — pragmatyka: theme był pusty, PHP szybsze niż konfiguracja Theme Buildera). Szczegóły w ZADANIE 12 Krok 2a.

---

## GRUPA 9.5 — performance ✅ DONE (0.30.10)

- [x] Zidentyfikowano: 3× COUNT na postmeta (~600ms) + JOINy w `renderPricePreview` (~400ms)
- [x] Transient `asiaauto_listing_counts` (10min TTL), invalidate po `ajaxBulkRecalc`
- [x] Trash TTL 30d → 7d (2534 listings w koszu się samooczyści szybciej)

---

## ZADANIE 6 — Filtr miast + aktualizacja marek (NOWE)

> Status: **w planowaniu**. Realizacja w nowym wątku po zamknięciu aktualnej sesji.

### Kontekst biznesowy

Prima Auto ma ludzi na miejscu w południowych Chinach (Guangdong, Fujian, Guangxi, Hainan).
Mogą fizycznie sprawdzać i doglądać auta tylko w wybranych miastach. Import ogłoszeń powinien
być ograniczony do tych lokalizacji — żeby klient widział tylko auta, które faktycznie można
zweryfikować na miejscu.

Jednocześnie klient prześle zaktualizowaną listę marek — obecne orphaned termy (Li Auto, NIO,
Volvo) mogą wrócić lub zostać usunięte.

### Lista miast od klienta

| Region | Miasto (PL) | Miasto (ZH) |
|---|---|---|
| Guangdong | Guangzhou | 广州 |
| Guangdong | Shenzhen | 深圳 |
| Guangdong | Foshan | 佛山 |
| Guangdong | Dongguan | 东莞 |
| Fujian | Xiamen | 厦门 |
| Fujian | Fuzhou | 福州 |
| Guangxi | Beihai | 北海 |
| Guangxi | Nanning | 南宁 |
| Hainan | Haikou | 海口 |
| Hainan | Sanya | 三亚 |

> Uwaga: klient podał też regiony (Fujian, Guangxi, Hainan) — interpretowane jako prowincje,
> z których wybrano konkretne miasta. Filtr dotyczy miast, nie prowincji.

### Podzadania — Krok A: weryfikacja dostępności ogłoszeń ✅ DONE (0.30.10)

- [x] Sprawdzone: API `getFilters()` NIE ma filtra `city` — filtr tylko po stronie PHP
- [x] Scan 80 stron dongchedi (1600 ofert), pole `city` = chińskie znaki (广州, 深圳...)
- [x] Zmapowane wszystkie miasta z 4 prowincji z co najmniej 1 ofertą
- [x] Lista finalna: **31 miast** (15 Guangdong + 6 Fujian + 8 Guangxi + 2 Hainan)
- [x] Nadgorliwość Ruslana skorygowana — dodane m.in. 惠州 (19 ofert), 泉州 (9), 南平 (8), 柳州 (5)

### Podzadania — Krok B: filtr w panelu admina ✅ DONE (0.30.10)

- [x] Opcja `city_filter_enabled` (bool) + `city_filter_cities` (array `[{zh,pl,province}]`) w `asiaauto_import_config[source]`
- [x] UI w zakładce "Filtry": toggle + przycisk "Konfiguruj miasta" otwierający modal
- [x] Modal: 4 sekcje prowincji z checkboxami, per-prowincja "wszystkie/żadne", sekcja "Dodaj miasto" (ZH + PL + prowincja), licznik zaznaczonych
- [x] Filtr w `AsiaAuto_Importer::isAllowedByConfig()` — skip oferty której `city` nie ma na liście `zh`

### Podzadania — Krok C: aktualizacja marek ✅ DONE (0.30.12)

- [x] Klient wybrał 66 marek z panelu — zrzuty ekranu z panelu admina
- [x] Orphaned termy (Li Auto, NIO, Volvo, Lynk & Co) — zostają, część wróci z importem
- [x] Brakujące marki dodane jako terminy taksonomii przez sync

### Podzadania — Krok D: re-import (po finalizacji filtrów) ✅ DONE (0.30.12)

- [x] Filtr miast przetestowany i zatwierdzony przez klienta (31 miast)
- [x] Backup bazy przed purge (`mysqldump` listings + postmeta + options do `~/backups/primaauto/pre-purge-20260417-1524.sql`, 445MB)
- [x] Purge: 2905 listings → trash (marki OR miasta poza listą, rezerwacje chronione)
- [x] Bulk-import przez `diag/bulk-import-by-brand.php`: reverse-order pages, MAX_PAGES=50, probe [50,40,30,20,10,5,2,1], parametr API `mark=X` (nie `brand=`)
- [x] Fix-missing-images: 45/45 (15 OK, 30 TRASH ghost-offers)
- [x] Cron bug fix: `add_action('asiaauto_sync_changes')` + `cron_schedules` filter — sync faktycznie działa (change_id 2868663 → 2870663 po pierwszym handler fire)

### Cena — zmiana filtru

- Klient obniżył `price_from` 120000 → 75000 CNY (2026-04-17 ~16:10); v4 bulk-import wczytał świeży config, zmiana obowiązuje dla cron syncu.

### Zależności i uwagi

- Filtr miast ma być domyślnie **wyłączony** (toggle OFF) — obecne ogłoszenia nie znikają od razu
- Toggle OFF = sync działa jak dotychczas (bez filtra geograficznego)
- Toggle ON = sync pobiera tylko ogłoszenia z wybranych miast
- Krok D dopiero po: Krok A (weryfikacja) + Krok B (filtr gotowy) + Krok C (marki potwierdzone)

---

## ZADANIE 8 — n8n pipeline opisów marek i modeli (NOWE)

> Status: **w planowaniu**. Baza: workflow Victorini (`workflows/VICTORINI PRODUCT DESC TECHNISTONE V4.json`, 33 nody, multi-agent LangChain, V4 = dopracowany).

### Kontekst biznesowy

Generacja długich opisów SEO dla **66 marek** (taksonomia `make`) + **~150 modeli** (taksonomia `serie`). Zastąpienie obecnych krótkich tekstów z B2 SEO (10 marek + 75 modeli) bogatszą treścią z inventory snippets (wstawki aktualnych ofert między paragrafami). **Publikacja od razu** (bez manual review), zabezpieczona deterministycznym fact-check + anti-spam guard. Po stabilizacji — adaptacja wzorca do bloga (ZADANIE 16) i bazy wiedzy (ZADANIE 15).

### Podzadania — Krok A: segmenty EU (prerequisite)

- [ ] Klasyfikacja 66 marek + ~150 modeli do segmentów EU: B, C, D, SUV B, SUV C/D, Premium, Van, Pickup
- [ ] Hybryda: algorytm (długość/szerokość/cena z Dongchedi) → propozycja → ręczna akceptacja (Ruslan/Jan)
- [ ] Mapowanie zapisane w term_meta `_asiaauto_eu_segment` + dublet w Google Sheet
- [ ] **Blokuje dalsze kroki** — brand guidelines odwołują się do segmentu

### Podzadania — Krok B: custom REST endpoints w asiaauto-sync

- [ ] `/wp-json/asiaauto/v1/latest-by-make/{slug}?n=8` — 8 najnowszych listings (ID, tytuł, cena PL, obrazek, permalink)
- [ ] `/wp-json/asiaauto/v1/latest-by-serie/{slug}?n=8` — analogicznie dla modelu
- [ ] `/wp-json/asiaauto/v1/facts-for-make/{slug}` — Fact Block: liczba modeli, zakres cen PL, top 3 modele, segment EU, zakres lat
- [ ] `/wp-json/asiaauto/v1/facts-for-serie/{slug}` — moc, przyspieszenie, zasięg EV (min/max/median), liczba ogłoszeń, zakres cen
- [ ] `/wp-json/asiaauto/v1/desc-queue` — lista termów kwalifikujących się do generacji (reguły invalidation)

### Podzadania — Krok C: klon workflow Victorini

- [ ] Kopia → `workflows/primaauto-brand-desc-v1.json`
- [ ] Podmiana endpointów WC Products → `/wp-json/wp/v2/make/{id}` + `/wp-json/wp/v2/serie/{id}` (term `description` jako target, nie post_content)
- [ ] Dual-LLM (GPT-4o + Gemini) → **Claude Sonnet 4.6** (treść) + **Claude Haiku 4.5** (meta description)
- [ ] Prompt caching na system prompt + Brand Guidelines (oszczędność 2–3× przy 216 generacjach)
- [ ] Node „Fetch Inventory Snippet" — wołanie endpointów z Kroku B, sklejanie HTML bloku wstawianego między paragrafami w Final Assembly

### Podzadania — Krok D: fact-check walidator (deterministyczny)

- [ ] JavaScript node „Fact Block Validator" po każdym agencie generującym treść
- [ ] Regex po liczbach w outpucie → porównanie z Fact Block → tolerancja ±1%
- [ ] Named entity check: tekst marki nie zawiera innych marek poza sekcją porównania (whitelist z segment EU)
- [ ] FAIL → retry max 2× z dopiskiem „Użyłeś liczb spoza DANE, popraw"
- [ ] Po 2 nieudanych próbach → `status=failed` + error message

### Podzadania — Krok E: anti-spam lint (Google E-E-A-T)

- [ ] Lista „AI tells" w regex (kopia Victorini + rozszerzenia: „Warto wspomnieć", „Bez wątpienia", „W dzisiejszym dynamicznym świecie", „Niezrównana jakość")
- [ ] Wykrycie → regeneracja (1 retry)
- [ ] Meta `_asiaauto_desc_author = "Zespół Prima Auto"` + `_asiaauto_desc_updated_ui` renderowane widocznie na archive page
- [ ] Uniqueness seed (hash marki) → różnicowanie kolejności sekcji i punktu startu narracji
- [ ] Obowiązkowe wstawki E-E-A-T w prompcie („Z naszego doświadczenia z importem X…", „Klienci Prima Auto najczęściej pytają o…")

### Podzadania — Krok F: status tracking + admin UI

- [ ] Meta fields na termie:
  - `_asiaauto_desc_status` (pending | generating | published | failed | skipped)
  - `_asiaauto_desc_generated_at`, `_asiaauto_desc_prompt_version`
  - `_asiaauto_desc_inventory_hash`, `_asiaauto_desc_facts_hash`
  - `_asiaauto_desc_backup`, `_asiaauto_desc_error`
- [ ] Meta box na edit term screen (make/serie): status badge, ostatnia generacja, przyciski Wygeneruj/Regeneruj/Przywróć backup/Zablokuj/Odblokuj
- [ ] Kolumna „Opis AI" w taxonomy list + bulk actions (zakolejkuj / zablokuj / odblokuj)
- [ ] Dashboard widget „Pipeline opisów" z licznikami per status
- [ ] Globalna opcja `asiaauto_desc_daily_quota` (domyślnie 20) — ochrona przed runaway

### Podzadania — Krok G: Brand Guidelines

- [ ] Google Sheet „PrimaAuto Brand Guidelines" — kolumny: make_slug, tone, USP, target, zakazane_zwroty, segment_eu, referencyjny_eu_model, flagship_models
- [ ] Wypełnienie 66 marek (research + decyzje Ruslan)

### Podzadania — Krok H: iteracja + batch

- [ ] Dry-run 10 sztuk (5 marek + 5 modeli) → publikacja
- [ ] Spot-check Jan/Ruslan w 24h, backup w `_desc_backup` umożliwia rollback
- [ ] Kalibracja promptów na bazie obserwacji
- [ ] Pełny batch marek (~1h) + batch modeli (~2h)
- [ ] Cron `asiaauto_desc_pipeline_hourly` — quota 20/dzień, invalidation: prompt_version bump, inventory_hash change (7d cooldown), facts_hash change, segment_eu change, kwartalny pełny rerun

### Kolejność wdrożenia (2026-04-21)

1. **Faza 1 — marki** (66 termów `make`): Krok A → B → C → D → E → F → G → H dry-run 5 marek → spot-check → batch 66
2. **Faza 2 — modele** (~150 termów `serie`): po stabilizacji marek. Adaptacja workflow (mniej paragrafów, węższy fact block), batch ~150

### Zależności i uwagi

- **Prerequisite Krok A:** bez segmentacji EU brand guidelines są kalekie
- **Koszt LLM:** ~$5–15 pełny batch (Sonnet 4.6 + prompt caching), reruny tańsze dzięki cache
- **Safety valve:** `_desc_backup` pozwala rollback każdego termu
- **Replacement B2 SEO:** obecne krótkie teksty zostają jako lead paragraph (pierwsze 2–3 zdania), długi AI-content jako rozwinięcie
- **Synergia z ZADANIEM 10:** GSC invalidation trigger — spadek pozycji >20%/tydz → flag `_desc_status=pending`

---

## ZADANIE 10 — API integracje: GSC + GA4 + Google Ads + Meta (NOWE)

> Status: **w planowaniu**. Setup OAuth i tokenów na **osobnym wątku** (Jan ma dostęp do Google Cloud Console + Meta Business Manager).

### Kontekst biznesowy

Podłączenie 4 zewnętrznych API: **GSC** (monitoring organic), **GA4** (attribution + funnel), **Google Ads** (performance + conversion upload), **Meta Marketing** (FB/IG kampanie). Cel read-first: mierzenie → input do iteracji (ZADANIE 8 invalidation trigger, SEO roadmap ZADANIE 18). Write operations na dalszych etapach (Q2+ po 3 miesiącach iteracji).

### Priorytet TERAZ — aplikacje o tokeny (timer 2–4 tyg biegnie równolegle)

- [ ] **Google Ads Developer Token** — aplikacja w Google Cloud Console (1–4 tyg akceptacji)
- [ ] **Meta App Review** (Business Manager → Advanced Access) — developers.facebook.com (1–2 tyg akceptacji)

### Podzadania — Krok A: GSC (najszybszy ROI)

- [ ] OAuth 2.0 credentials w GCP, scope `webmasters.readonly`
- [ ] Tokeny w `~/.bash_profile`: `GSC_CLIENT_ID`, `GSC_CLIENT_SECRET`, `GSC_REFRESH_TOKEN`
- [ ] n8n credentials + workflow „GSC weekly report" — top 20 queries/pages, CTR, pozycja, Δ tydzień do tygodnia
- [ ] Output → Google Sheet + alert Slack/mail

### Podzadania — Krok B: GA4 readonly

- [ ] Service Account w GCP z dostępem do GA4 property, scope `analytics.readonly`
- [ ] Token w `~/.bash_profile`: `GA4_SERVICE_ACCOUNT_JSON` (ścieżka), `GA4_PROPERTY_ID`
- [ ] n8n workflow „GA4 weekly KPI" — sesje per źródło, konwersje, funnel rezerwacje→umowa

### Podzadania — Krok C: integracja z ZADANIEM 8

- [ ] Job „GSC → desc-queue" (n8n, raz/tydz) — detekcja stron ze spadkiem pozycji >20% → flag `_asiaauto_desc_status=pending` → regeneracja w następnym cyklu pipeline
- [ ] Detekcja nowych queries do top 20 → wpis do brand guidelines (nowy keyword do uwzględnienia w prompcie)

### Podzadania — Krok D: Google Ads API (po tokenie)

- [ ] OAuth + Developer Token + Manager Account (MCC) setup
- [ ] Tokeny: `GADS_DEVELOPER_TOKEN`, `GADS_REFRESH_TOKEN`, `GADS_MCC_ID`, `GADS_CUSTOMER_ID`
- [ ] Raporty readonly: kampanie, keywords, CPL, CPC, CTR per kampania
- [ ] **Offline conversion upload:** rezerwacja auta → webhook → Google Ads conversion (label `reservation`) — bidder optymalizuje pod realne rezerwacje, nie clicks

### Podzadania — Krok E: Meta Marketing API (po App Review)

- [ ] OAuth + Advanced Access permissions
- [ ] Token w `~/.bash_profile`: `META_ACCESS_TOKEN`, `META_AD_ACCOUNT_ID`
- [ ] Raporty readonly: kampanie, audiences, creative performance
- [ ] Custom audience „odwiedzili parking (ZADANIE 12) ale nie zalogowani" → remarketing

### Podzadania — Krok F: skonsolidowany dashboard

- [ ] Workflow n8n łączący GSC + GA4 + Ads + Meta
- [ ] Export do Google Sheet / Looker Studio
- [ ] KPI: CPL per source, ROAS, funnel conversion rate, organic traffic growth MoM

### Zależności i uwagi

- **Prerequisite:** osobny wątek na setup OAuth + tokeny (Jan ma Google Cloud Console)
- **Tokeny:** wszystkie w `~/.bash_profile` na Elarze, NIC w repo (wzorzec identyczny jak MCP_TOKEN)
- **Consent Mode v2** — wymagany w EU od 2024; Prima Auto musi mieć poprawnie skonfigurowany cookie banner, inaczej dane GA4 niepełne
- **Rate limits:** GSC 50k/dzień OK, GA4 1250 tokens/h/property OK, Ads 15k ops/dzień (basic access), Meta 200 calls/h
- **NIE robimy na starcie:** automated bidding, auto-kreacje, bulk-edycja kampanii — dopiero po 3 mies stabilnego readonly trackingu
- **Status Prima Auto aktywności kampanii:** do potwierdzenia — GA4 już działa w GTM? Ads/Meta aktywne z budżetem czy planowane? (zmienia priorytety)

---

## Backlog (niski priorytet)

- [ ] **Plan A — fix race condition w sync** (reaktywne, trigger: gdy znów pojawią się duplikaty). Zastąpić transient lock w `class-asiaauto-sync.php:52-58` przez MySQL `GET_LOCK('asiaauto_sync_dongchedi', 0)`. Bez TTL, auto-release na disconnect. ADR: `docs/decyzje/2026-04-22-dedup-i-optymalizacja-bazy.md`.
- [ ] **Plan D — prewencja bloatu bazy**:
  - [ ] `class-asiaauto-media.php`: ustawiać `post_parent = $listing_id` przy `wp_insert_attachment` (bez tego każdy trashowany listing zostawia 10-15 sierot)
  - [ ] `asiaauto_daily_cleanup` cron: kasować na stałe trashed listings starsze niż 30 dni (teraz tylko drafts→trash, nigdy nie kasuje)
- [ ] SKILL/CLAUDE.md: zasada „MCP tylko gdy lokalnie nie da się" — MCP http round-trip marnuje tokeny, jeśli uruchamiamy Claude Code na Elarze mającej bezpośredni dostęp do plików i `wp` CLI. Dotyczy: `read_file`, `list_dir`, `query_db` (zastąpić `wp db query`), `options` (zastąpić `wp option get`), `stats` (własny `wp eval`). MCP sens zachowuje tylko dla zewnętrznych projektów bez local shell (Claude.ai web).
- [ ] Krok 4 manual editor — metabox extra_prep (18 zakładek)
- [ ] Email HTML templates (maile są plain text)
- [ ] Homepage + Contact CSS → pliki zewnętrzne (z inline)
- [ ] Archive/taxonomy pages dla marek (B5 — duże zadanie, osobny projekt)
- [ ] Orphaned terms (Li Auto, Volvo, NIO, Lynk &amp; Co — count=0, czekają na nową listę marek od klienta)

---

## Zrealizowane (archiwum)

- [x] Pending 0: bump wersji 0.29.0-wip → 0.30.7 (2026-04-16)
- [x] Pending 1: pipeline USD-centric — `calculateFromCifUsd()`, `BREAKDOWN_VERSION=2` (2026-04-16)
- [x] ZADANIE 2: załączniki PDF do umowy (renderAttachment1/2), token bezpieczeństwa, nr umowy w tytule przelewu (0.30.8, 2026-04-17)
- [x] ZADANIE 3: maile statusów, etykiety "depozyt zabezpieczający" (2026-04-16)
- [x] ZADANIE 4: smoke test E2E — flow zamówień, PDF, maile, statusy (2026-04-17)
- [x] ZADANIE 5: rework workflow zamówień — model agencyjny, nowe statusy, wizard, panel admina (0.30.7, 2026-04-16)
- [x] B2 SEO: meta/OG/title dla single i inventory, Schema.org, term meta opisów, 10 marek + 75 modeli, llms.txt (0.30.9, 2026-04-17)
- [x] Panel klienta `/klient/` — shortcode, logout, auto-redirect (2026-04-16)
- [x] Fix ghost-crona `asiaauto_daily_cleanup` — handler w bootstrapie + jednorazowy cleanup (trash 5470 → 3559, delete 2077, drafts→trash 166) (0.30.13, 2026-04-20)
- [x] Dedup 36 par listingów + optymalizacja DB 640 MB → 141 MB (−78%): revisions, trashed listings, orphan attachments; `WP_POST_REVISIONS=3` (2026-04-22) — ADR: `docs/decyzje/2026-04-22-dedup-i-optymalizacja-bazy.md`
