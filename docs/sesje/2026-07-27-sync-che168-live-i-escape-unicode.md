# Sesja 2026-07-27 — sync che168 na produkcji + naprawa escape'ów unicode

Punkt wyjścia: Ruslan zapisał nowe filtry che168, Janek chciał odpalić sync i sprawdzić
filtrowanie, wykluczenia modeli, filtr miast, trafianie w huby i indeksację.

## Sync che168 — LIVE

Stan końcowy: `asiaauto_sync_enabled=1`, `asiaauto_sync_enabled_che168=1`,
`asiaauto_sync_status_che168=publish`, dongchedi wyłączony. Zdarzenie `asiaauto_sync_changes`
co 15 min (`asiaauto_15min`). WP-Cron odpala je **sam** — potwierdzone biegiem 18:19 UTC.

**Dlaczego stało od 22.07:** cron synca planuje się **wyłącznie z panelu admina**
(`class-asiaauto-admin.php:146`). Ustawienie opcji przez `wp option update` nie tworzy
zdarzenia. Dopięte ręcznie przez `wp_schedule_event()`.

Kursor: był zaległy o 86 590 zdarzeń (25.07 12:45), przestawiony na start doby (10030841).
Stan poprzedni w `~/backups/primaauto/2026-07-27/che168-sync-snapshot-przed.json`.

### Weryfikacja filtrów — na realnym biegu, nie w teorii

| Co | Dowód |
|---|---|
| filtr marek | 1933/1987 zdarzeń `added` odsiane na marce |
| blacklista modeli | 13 trafień w strumieniu (S90, V40, S60, XC40, XC60, XC90), 109 w magazynie |
| filtr miast | wpuścił 深圳 → `stm_car_location=Shenzhen`; odrzucił 299/360 magazynu |
| huby | 3 oferty weszły, wszystkie widoczne na hubach serii |
| indeksacja | 3/3 `Indexing OK URL_UPDATED` (auto-submit na `transition_post_status`) |

Zero rozjazdu między ręcznym liczeniem warunków a produkcyjnym `isAllowedByConfig()`
na 2487 ofertach.

### Dwa kanały wejścia, nie jeden

`/changes` `added` = 58% strumienia, ma komplet pól filtra. **`changed` = 42% i ma w `data`
JEDNO pole: `new_price`** — prefilter w `Sync::importWithFullData()` odpala się na tym,
nie widzi marki, `return null`, `getOffer()` nigdy nie leci. Komentarz w kodzie
(„Dane z /changes mają komplet pól filtra") jest prawdziwy tylko dla `added`.

Skutek: oferta leżąca w magazynie che168 (jej `added` był przed kursorem) **nie ma drugiej
szansy przez sync**. Naiwna łatka jest gorsza niż problem: ~7700 `getOffer`/dobę, a **429
Too Many Requests** przychodzi już po ~270 wywołaniach w kilka minut.

Drugi kanał: `scripts/che168-domknij-luke.php` — `getOffers` per marka (server-side mark+year),
**24 wywołania na cały magazyn 4 marek**. Dry-run domyślnie.

### Monitoring — `scripts/che168-monitor.php`

Trzy metryki, których historia synca nie pokazuje:
1. **luka** = (magazyn ∩ filtry ∩ zmapowane) − (w bazie),
2. **priorytet domapowań** — ile ofert odblokuje jeden wpis w mapie,
3. **detektor rozjazdów** — `车型名称` + `engine_type` → hub; ta sama seria kanoniczna przy tym
   samym napędzie musi trafiać w jeden hub.

`--mark="NIO"` sonduje markę **przed** dopięciem do filtrów (baza i config nietknięte).

Stan luki: **16 ofert do wzięcia** (15 Denza + Volvo EM90, wszystkie ze `specid`).
**Lynk & Co daje 0** — 17 ofert przechodzi filtr, wszystkie orphany. **Fangchengbao: zero
podaży** pod tą nazwą (choć mapuje się poprawnie na BYD Leopard 5).

Dlaczego „mamy Lynk & Co w bazie" ≠ „che168 go wpuści": **dongchedi nie ma guarda mapowania**
(`normalizeForSource` zwraca dane bez sprawdzenia), che168 ma (`isMappedForImport`). Dongchedi
zapełnił większość z 312 serii w bazie, w ogóle nie dotykając brand-mappingu.

### `车型名称` — jedyny stabilny klucz serii

Pole `model` **różni się między endpointami tego samego API**: `/offers` dawało „N8L DM",
`/offer` „腾势N8L". Dlatego mapy trzymały dublujące wpisy na jedno auto.

`extra.configuration.paramtypeitems[基本参数]` → `车型名称`, format `{seria CN} {rok}款 {wersja}`,
**dostępne na wszystkich trzech endpointach**. Uwaga: Autohome **nie zna serii „N8L DM"
ani „D9 DM-i"** — dla D9 `车型名称` to `腾势D9 2024款 DM-i 1050 尊享型`, czyli napęd siedzi
w członie wersji. Rozbicie hubów po napędzie jest nasze, nie źródłowe.

## Casus N8L — dwa huby na jedno auto

Zgłoszone przez Janka po zobaczeniu świeżej oferty. Dwa override'y celowały w `serie_eu='N8L'`
i **short-circuitowały krok 3** resolvera (warianty napędu):
`Denza|腾势N8L` (che168-model-map) i `Denza|N8L DM` (brand-mapping).

Powstał drugi hub `/samochody/denza/n8l/` (**0 impresji w GSC**) obok rankującego
`/samochody/denza/n8l-dm/` (~170 impresji, poz. **4,4** na „denza n8l cena", CTR 27% na
„denza n8 cena"), zasilanego przez dongchedi.

**Naprawa = usunięcie obu wpisów, nie poprawianie wartości.** Reverse-index sam trafia
w `Denza|Denza N8L DM` wniesiony przez dongchedi; warianty napędu są sprawdzane przed nazwą
bazową, więc `N8L DM` wygrywa z `N8L`. Oba endpointy → `Denza/N8L DM [n8l-dm]`, D9/Z9/N9
bez regresji. 4 oferty przeniesione, pusty term usunięty (`/denza/n8l/` → 301).

Slug `n8l-dm` **zostaje** — konwencja `DM-i` (jak D9/Z9/N9) była tu myląca, zmiana zabiłaby
rankujący slug. Oferta `denza-n8l-2025-387173` ma 1 klik / poz. 5,0, więc slugów ofert nie
ruszaliśmy. Nowe oferty che168 dostaną `title_eu = Denza N8L DM`, czyli slug zgodzi się sam.

Backupy map: `*.bak-2026-07-27-n8l` w `wp-content/plugins/asiaauto-sync/data/`.

## Escape'y unicode — `194u9a6cu529b` zamiast `194马力`

Zgłoszone przez Janka: wiersz „Silnik" na hubie pokazywał śmieć, a „Skrzynia biegów"
— „Widlasty (V)". Okazało się, że to **jeden korzeń i trzy objawy**.

**Mechanizm:** `wp_json_encode($ep)` bez `JSON_UNESCAPED_UNICODE` koduje CJK jako `马力`,
a `update_post_meta()` przepuszcza wartość przez `wp_unslash()`, który zjada backslashe.
Winowajca: `diag/backfill-extra-prep.php:73`. Importer się bronił (`wp_slash` w linii 514),
dlatego **sync che168 i ścieżka automatyczna były czyste** — 0 ofert che168 na liście 128.

**Trzy objawy:**
1. surowy śmieć w „Silniku" (tam idzie `tr_engine`),
2. **fałszywe tłumaczenie** w „Skrzyni": `tr_val()` przy braku pełnego trafienia bierze
   „najdłuższy klucz mapy zawarty w wartości", więc dla `E-CVTu65e0u7ea7u53d8u901f` wygrywa
   **jednoznakowy klucz `V`** (z „E-CVT") → „Widlasty (V)"; analogicznie `L` z „L4" → „Rzędowy (L)",
3. **fałszywa dodatkowa kolumna wersji** — zepsuty string ≠ przetłumaczony, więc jedno auto
   liczyło się jako dwie wersje w tabeli spec-hub.

**Nie było to zamknięte archiwum:** 26 z 114 dotkniętych ofert publish młodszych niż 30 dni
(dwie z tego samego dnia), a 107 to wpisy ręczne, które `isManuallyManaged()` wyłącza z rotacji.

**Naprawa** — `scripts/fix-escape-unicode-extra-prep.php`. Odwraca `uXXXX` → znak **tylko dla
codepointów CJK/fullwidth** (2928 trafień poza zakresem zostało nietkniętych), naprawia
**klucze i wartości** (klucze też bywają zepsute: `map_brand_u9ad8u5fb7`), weryfikuje po
każdym zapisie. Argumenty **bez dashy** — WP-CLI przechwytuje `--apply`:
`wp eval-file ... apply limit=3 only=ID,ID`.

Wynik: **114 ofert, 24 531 pól, 83 558 zamian. Huby ze śmieciem: z 31 na 0 z 44.**
Hub `n8l-dm` po naprawie ma 2 kolumny zamiast 3 — fałszywa wersja zniknęła.

Backup: `~/backups/primaauto/2026-07-27/extra-prep-przed-fix-escape.json` (3051 wierszy, 43 MB).
Kolejność: próbka 6 ofert → weryfikacja renderu hubu → całość.

## Odłożone na 2026-07-28

Janek zostawia sync pod obserwacją, spodziewa się kolejnych kwestii z jego działania.
Trzy zadania czekają — szczegóły w memory `project_che168_do_zrobienia_2026_07_28`:

1. **14 ofert z rozwalonym JSON** `extra_prep` (`json_decode`→NULL, parametry nie renderują się
   wcale, hubów nie psują). Ten sam korzeń, silniejszy objaw. Rozmiary powtarzalne
   (14105 / 15692 B). Do odtworzenia z bliźniaka, nie do parsowania.
2. **`tr_val()`** — jednoznakowe klucze w częściowym dopasowaniu to mina; ograniczyć
   dopasowanie częściowe do kluczy CJK. Strefa renderu hubów, więc ostrożnie.
3. **Lynk & Co** — `03` odblokowuje 14 ofert jednym wpisem w `che168-model-map.php`.
   Sprawdzić nazwy istniejących termów, żeby nie powtórzyć duplikatu jak przy N8L.

Start następnej sesji: `wp eval-file ~/projekty/primaauto/scripts/che168-monitor.php`.
