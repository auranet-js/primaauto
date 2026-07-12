# T-200 — Google Ads TOTAL REWORK visual-first — PROJEKT (v4, rekomendacja finalna)

> Data: 2026-07-09 · Status: **PROJEKT do akceptu — zero mutacji na koncie**
> Konto: 9506068500 (direct) · API v21 · klient `tmp/gads_client.py`
> Zadanie od Janka: ludzie kupują auta oczami — rozkminić, jak wykorzystać ~3050 ogłoszeń ze zdjęciami do promocji (Demand Gen? YouTube?). Pomiar konwersji = nieistotny na razie (wszystko Manual CPC) — szczątki w zał. A.

---

## 0. Diagnoza w jednym akapicie

Search pali ~2300 zł/30d na tekstowe reklamy w kategorii, w której klient jeszcze NIE WIE, jakiego modelu chce (frazy per model mają w PL wolumeny szczątkowe — nie ma czego przechwytywać). Jedyna kampania wizualna — [RMKT] na feedzie zdjęć z ogłoszeń — ma najlepszy niebrandowy CPA (10,8 zł), ale celuje wyłącznie w 1100 przeszłych gości za 17 zł/dz. Wniosek: obraz działa, tylko pokazujemy go wyłącznie ludziom, którzy już nas znają. Odwracamy to: **zdjęcia i wideo z ogłoszeń stają się pierwszym kontaktem z marką**, search zostaje tam, gdzie ma dowody (Brand, Topic, 4 grupy SKAG-2, RMKT).

## 1. REKOMENDACJA — kolejność wdrożenia (moja decyzja, do akceptu całości)

| Krok | Co | Kiedy | Budżet/dz |
|---|---|---|---|
| 1 | **Cięcia search** (sekcja 5) — uwalniają ~77 zł/dz | sesja 1 | −77 |
| 2 | **Demand Gen ze zdjęciami ogłoszeń** (sekcja 2) — RDZEŃ | sesja 2-3, start w ciągu dni | 30 |
| 3 | **Kanał YT + filmiki-slajdy** (sekcja 3) — tor równoległy | tydzień 1-3 | 0 (produkcja) |
| 4 | **Kampania VIDEO na widzów testów chińskich aut** (sekcja 3.4) + wideo do DG | po kroku 3 | 15-20 |
| 5 | (opcja) **Dynamic prospecting na feedzie** (sekcja 4) — tania skala | po ocenie 2 i 4 | 20 |

**Dlaczego DG pierwszy, a nie od razu YouTube (pomysł Janka):** pomysł YT zostaje w całości (krok 3-4), ale ma zależności — kanał (decyzja Ruslana), produkcja filmików, akcept jakości. To 2-3 tygodnie zanim pierwsza złotówka zacznie pracować. Demand Gen z reklamami OBRAZOWYMI startuje z tego, co już mamy (zdjęcia ogłoszeń + hero z pipeline'u Gemini), i **też wyświetla się na YouTube** (feed Home, obok wyników wyszukiwania YT, Shorts) plus Discover i Gmail. Czyli: obraz idzie w świat od razu, wideo dołącza jak będzie gotowe — do tej samej kampanii DG (rozszerzenie o `DemandGenVideoResponsiveAd`) i do osobnej kampanii VIDEO z placementami (jedyny sposób, żeby celować dokładnie w widzów KONKRETNYCH filmów-testów — DG placementów nie ma).

**Docelowy podział budżetu (~127 zł/dz):** Brand 15 · Topic 35 · SKAG-2 25 · RMKT 17 · DG 30 · (po kroku 4: VIDEO 15-20 z rezerwy po cięciach).

---

## 2. RDZEŃ — Demand Gen ze zdjęciami ogłoszeń

Powierzchnie: feed YouTube + wyniki wyszukiwania YT, Discover (telefon), Gmail (karta Oferty). Format natywny — duże zdjęcie, cena, nagłówek; dokładnie „kupowanie oczami".

### 2.1 Struktura
```
[DG] Modele wizualnie — pilot         30 zł/dz, Maximize Clicks (start)
├── AG-1 Intencja (custom segment)    → MultiAssetAd top modele + Carousel Leopard 5
│      segment zał. B: szukali „chińskie samochody”, „byd polska”…,
│      oglądali strony konkurencji — najbliższy odpowiednik
│      „ludzi oglądających treści o chińskich autach”
├── AG-2 In-market Motor Vehicles     → MultiAssetAd „dostępne od ręki” (stock on_lot)
│      (New SUVs / Hybrid & EV)
└── AG-3 Remarketing + Lookalike      → Carousel Denza Z9 GT + MultiAssetAd
       lista 90d (1100 ✅) + Product viewers (1000);
       lookalike od listy 90d (seed ≥100 ✅ — zweryfikować dostępność PL w UI)
```
- Bidding: `MAXIMIZE_CLICKS` na start (nie wymaga wolumenu konwersji); po ≥10 konw. miękkich rozważyć `MAXIMIZE_CONVERSIONS`. Bez tCPA (zał. A).
- Landing: **huby `/samochody/{marka}/{model}/`** — nigdy /oferta/{egzemplarz}/ (gnije przy rotacji; huby nigdy nie znikają).
- Budowa przez API v21 (`DEMAND_GEN` + `DemandGenMultiAssetAd`/`DemandGenCarouselAd`); lookalike prawdopodobnie UI. Twój review reklam w UI przed włączeniem.

### 2.2 Assety ze zdjęć ogłoszeń (nie stock-foto)
- **Dobór modeli z danych** (pageviews GA4 × konwersje na stronie × stock fizyczny — 47 aut on_lot/in_transit): Leopard 5 (×15 konwersji GA4), Leopard 7, Denza Z9 GT / N9, Zeekr 7X/8X/9X, Sealion 8, Mazda EZ-6, Exeed VX. Pilot: **8-10 modeli × 3-4 zdjęcia** (hero + ¾ przodu + wnętrze).
- **Formaty:** 1.91:1 (1200×628, źródło: hero-pipeline `gen-hero.py`), 1:1 (1200×1200, crop z galerii), 4:5 (960×1200, opcjonalnie), logo 1:1. JPEG q90 ≤5 MB (galerie w uploads są jpg — feed RMKT już ich używa, przechodzą przez Google).
- **Produkcja:** skrypt `tmp/dg-assets-prep.py` (wybór z DB → crop 3 ratio → JPEG) + upload batch `AssetService`. ~30 obrazów na pilot.
- **Karuzele:** 5-8 kart (ujęcia + karta CTA z ceną all-in); 2 na pilot (Leopard 5, Denza Z9 GT).
- **Teksty z przetestowanych RSA** (ściągnę GAQL-em przy wykonaniu): cena all-in bez ukrytych opłat / import 6-8 tyg. / kaucja zwrotna / „dostępne od ręki" tylko przy stocku on_lot. Business name „Prima-Auto", CTA `LEARN_MORE`.

### 2.3 Kryterium pilota (przed startem)
4 tyg. × 30 zł/dz (~840 zł). **Sukces:** ≥15 konw. miękkich/mies. przy CPA ≤ 120 zł (ocena last + first-touch — DG zasiewa jak Topic, który first-touchem robi 6× więcej niż pokazuje last-click). **Porażka:** <5 konw. i CPA >250 zł → pauza, wnioski, budżet wraca do RMKT/Brand. **Kill-switch:** 2 tyg. / 0 konw. / ≥400 zł.

## 3. Tor równoległy — kanał YouTube + filmiki-slajdy + kampania na widzów testów

### 3.1 Kanał (fundament — reklamy wideo muszą być hostowane na YT)
- Recon: czy istnieje kanał/konto brand Ruslana — pytanie przez Ciebie, nic nie zakładamy.
- Setup: kanał brand „Prima-Auto" (`@primaauto`), właściciel = konto Google od GBP/Ads; Ruslan + Ty menedżerowie. Logo/banner z brand assets, opis + link + WhatsApp.
- **Połączyć kanał z Ads 9506068500** — po połączeniu widzowie filmów stają się listą remarketingową (każde obejrzenie zasila konto, nawet organiczne).
- Filmiki kampanijne mogą być „niepubliczne"; Shorts publicznie (organiczny bonus; tytuły SEO: „BYD Leopard 5 2025 — cena w Polsce, dostępny od ręki | Prima-Auto").

### 3.2 Produkcja slajdów z galerii ogłoszeń
- 6-8 zdjęć po ~3s, crossfade, plansze `drawtext`: model+rocznik → „cena all-in: XXX 000 zł (transport, cło, VAT, rejestracja)" → „dostępny od ręki" / „6-8 tygodni" → outro: logo, URL, telefon/WhatsApp. 20-30s. **16:9 (1920×1080) + 9:16 (1080×1920 Shorts)** z tych samych źródeł.
- Audio: YouTube Audio Library albo brak — nigdy utwory komercyjne.
- Skrypt `tmp/yt-slideshow.py` (ffmpeg). **LVE-safe:** `nice -19`, jeden render, TEST NA 1 AUCIE (Leopard 5) → Twój akcept jakości → batch 8-10. Fallback: render na Twoim Win11 (paczka zdjęć + .bat).
- Upload: ręcznie przez YT Studio (na pilot wystarczy; YouTube Data API = osobny OAuth scope, później).

### 3.3 Ryzyko jakościowe — nazwane wprost
Slideshow obok profesjonalnego testu może wyglądać tanio. Nasza przewaga: **testerzy nie podają ceny PL all-in ani dostępności** — plansza „260 000 zł z transportem, cłem, VAT i rejestracją, dostępny od ręki" to informacja, której widz testu szuka i nie dostaje. Dlatego test jakości na 1 filmie przed batchem.

### 3.4 Kampania `[YT] Widzowie testów chińskich aut` (typ VIDEO — jedyny z placementami)
- Formaty: **in-feed** (obok wyników wyszukiwania YT i powiązanych filmów — user sam klika) + **skippable in-stream** (przed testami). Bidding Manual CPV 0,05-0,12 zł, budżet 15-20 zł/dz.
- **Targetowanie treścią (sedno pomysłu):**
  - placementy: PL kanały motoryzacyjne regularnie testujące BYD/Zeekr/Denza + KONKRETNE filmy-testy naszych topowych modeli (listę zbuduję researchem przy wykonaniu),
  - słowa kluczowe wideo: „test byd", „zeekr 7x test", „chińskie samochody test", „denza recenzja"…,
  - tematy: Motoryzacja → hybrydy i EV / SUV-y.
- Audiencje jako obserwacja (custom segment zał. B, lista 90d) → bid modifier po 2 tyg.
- Landing: huby modelowe. Budowa przez API (VIDEO wspierane w v21), review w UI.
- **Kryteria (wideo zasiewa, nie domyka):** view rate ≥25% in-stream, CPV ≤0,12 zł, wzrost wejść organic/direct na huby reklamowanych modeli (GA4 4 tyg. przed/po), przyrost listy widzów kanału, konwersje first-touch. Kill: 2 tyg. / VR <10% / zero śladu w GA4 → korekta placementów/kreacji, nie kierunku.

## 4. Opcja skali — dynamic prospecting na istniejącym feedzie (po ocenie kroków 2 i 4)

Feed 9118300013 (312 hubów, realne zdjęcia + ceny PLN, matching `dynx_itemid=serie_id` działa — `class-asiaauto-single.php:676`) pokazywany zimnym audiencjom: kampania DISPLAY, RDA z feedem (kopia zaaprobowanej struktury RMKT), in-market + custom segment, wykluczona lista 90d, Manual CPC 0,35-0,40, 20 zł/dz. **Warunek: cron delta-push feedu dziennie** (generator `tmp/build-gads-hub-feed-2026-06-02.php` + push `tmp/gads_rmkt_feed_update_2026_06_19.py`; brak crona odnotowany 06-19 — do dorobienia niezależnie, służy RMKT).

## 5. Finansowanie — cięcia search (~2300 zł/30d ≈ 77 zł/dz)

Mutacje (każda: dry-run → dump before → apply → GAQL verify; skrypt `tmp/gads_t200_faza1_2026_07_09.py`):

| # | Mutacja | Zasób | Uzasadnienie |
|---|---|---|---|
| 1 | [DSA] 23896725555 → PAUSED | `campaigns:mutate` | CPA ~204 zł nawet first-touch; ostatnia konw. 22.06; page-feed zostaje (reaktywacja=1 klik) |
| 2 | [SKAG-1] 23803851563 → PAUSED | `campaigns:mutate` | 0 konw. / 612 zł/30d |
| 3 | [SKAG-2]: pauza **13 grup 0-konw.** | `adGroups:mutate` | Zostają 4 konwertujące (5 konw./288 zł = CPA 58 zł): Leopard 7 `204984900868`, Denza Z9 GT `204984901108`, Zeekr 8X `204984901428`, Zeekr 7X `194864114537`. Pauza: Zeekr 9X `204984901588` (178 zł/0 konw.), BYD Leopard 5 `194864114137`, Denza N9 `204984900948`, Jetour G700 `204984901348`, Zeekr 001 `194864114377`, BYD Shark 6 `202348695091`, Mazda EZ-6 `202348695251`, BYD Sealion 8 `194864114297`, Exeed VX `204984901148`, Leopard 3 `204984900708`, Deepal G318 `204984900908`, BYD Song L EV `194864114337`, Jetour T2 `204984901388` |
| 4 | [Brand] budżet 10→15 zł/dz | `campaignBudgets:mutate` | CPA 6,07 zł, głodzona |
| 5 | [RMKT] bid 0,31→0,39 zł (grupa `200662928841`) | `adGroups:mutate` | CPA 10,8 zł, lost rank ~90%; feeder=organic (12/16 wejść), pauzy search jej nie duszą |
| 6 | [Topic] 23779860638 — **ZERO ZMIAN** | — | first-touch 6 konw. (CPA ~107) — zasiewa 6× vs last-click; 1 grupa, nie ma czego czyścić |

Watchdog stocku 2026-07-09: DEAD 0 — 4 zostające grupy żywe; `byd leopard 7`/`denza z9 gt` wskazują auta już on_lot (oferta mocniejsza — zostawić). Watchdog w recheck co 2 tyg.

## 6. Recon do wykonania przy budowie (bez założeń)
1. Vehicle Ads (Merchant Center) w PL — gdyby dostępne, to format docelowy (zdjęcie+cena auta w wynikach Google); stan wiedzy: PL raczej brak — zweryfikować w MC UI.
2. Kompletność Demand Gen przez REST v21 w praniu — czego nie da się przez API → checklist kroków UI dla Janka.
3. Lookalike segments w PL (UI).
4. Kanał YT: istnienie konta brand Ruslana.

## 7. Ryzyka
| Ryzyko | Mitygacja |
|---|---|
| DG pali budżet bez konwersji (learning 1-2 tyg.) | Maximize Clicks + kill-switch 2 tyg./0 konw./400 zł |
| Formaty zasiewające niewidoczne w last-click | ocena first-touch (`tmp/ga4_assisted_2026_07_09.py` rozszerzyć o DG/VIDEO) + trend organic na huby |
| ffmpeg vs LVE | test 1 render, `nice -19`; fallback Win11 |
| Slideshow wygląda tanio | plansza ceny all-in = unikalna wartość; akcept jakości przed batchem |
| Ceny/stock odklejają się od kreacji | landing=huby (nie egzemplarze); „od ręki" tylko dla on_lot; cron feedu; watchdog co 2 tyg. |
| Pauza DSA/SKAG-1 zdusi RMKT | obalone danymi: RMKT karmi organic (12/16), DSA dawał 3 |

## 8. Plan sesji wykonawczych
1. Akcept → **cięcia** (sekcja 5) + zapis QUEUE/memory.
2. **Assety DG** (`dg-assets-prep.py`, selekcja z danych GA4+stock) + custom segment + build kampanii DG → Twój review w UI → start.
3. Równolegle: **kanał YT** (decyzja Ruslana → setup → link do Ads) + `yt-slideshow.py` test 1 auta → akcept → batch.
4. **Kampania VIDEO** (placementy z researchu) + wideo do DG.
5. **Recheck co 2 tyg.** — event kalendarz „Auranet Claude" (RRULE FREQ=WEEKLY;INTERVAL=2, 09:00): watchdog stocku, CPV/VR, CPA last+first-touch, decyzja skaluj/tnij. Po 4 tyg.: werdykt wg kryteriów.

## 9. Poza scope
Meta/FB (przejęcie konta, weryfikacja UE), SEO/organik, rename kampanii, Merchant Center e-commerce, nagrania realne aut (wątek contentowy Ruslana).

---

## Załącznik A — pomiar konwersji (ZAPARKOWANE — bez znaczenia przy Manual CPC)
Wykonana 1 kosmetyczna mutacja (po akcepcie): `purchase` → secondary (rollback: `~/backups/primaauto/2026-07-09-ads-t200/conversion-actions-before.json`). Primary: click_phone / click_whatsapp / generate_lead / Clicks to call (systemowa GOOGLE_HOSTED = telefony z assetu połączenia i GBP; API nie mutuje, rename niemożliwy). Odłożone na czas ew. smart biddingu: Local actions → secondary (tylko UI), wartości konwersji wg ceny auta (dataLayer `view_item` już ma cenę), enhanced conversions.

## Załącznik B — custom segment intencyjny (wspólny: DG / VIDEO obserwacja / PROSP)
API `customAudiences:mutate`, nazwa `Intent — chińskie auta + konkurencja`: frazy „chińskie samochody", „auta z chin", „import samochodów z chin", „byd polska", „zeekr polska", „denza", „chińskie suv", „tanie auta elektryczne"; URL-e chinskisamochod.com, west-motors.pl, otomoto.pl (sekcje marek chińskich). Nazwy konkurencji TYLKO w targetowaniu, nigdy w treściach (memory `feedback_no_competitor_dealer_names`).

## Załącznik C — stan zastany (recon 2026-07-09, okno 09.06–08.07)
| Kampania | koszt 30d | konw. last | CPA | first-touch |
|---|---|---|---|---|
| [DSA] | 1223 zł | 5 (ostatnia 22.06) | ~245 | 6 (~204) |
| [SKAG-2] | 764 zł | 5 | 153 | 5 (bez assist) |
| [Topic] | 641 zł | 4 | 160 | 6 (~107) — zasiewa |
| [SKAG-1] | 612 zł | 0 | — | 1 |
| [RMKT] | 205 zł | 19 | **10,8** | domyka, karmiony organikiem (12/16) |
| [Brand] | 140 zł | 23 | **6,07** | zdrowa |

Audiencje: 90d `9414602400` = 1100 display/580 search; Product viewers `9401177594` = 1000; All visitors `9372622741` = 1600. Feed `9118300013` = 312 hubów (id=term_id, zdjęcie jpg, cena PLN), matching dynx działa. Konwertujące strony GA4: homepage 22, /kontakt/ 19, /zamow/ 11, oferty fizycznego stocku (Leopard 5 ×15).
