# ADR 2026-06-18 — Che168: kalibracja specyfikacji, tożsamości i tytułu (v0.33.4–0.33.11)

## Kontekst

Sesja kalibracji ręcznego importu Che168 (po normalizacji tożsamości z 2026-06-17). Punkt
wyjścia: ogłoszenie 58545168 (Denza N8L) pokazywało 3 taksonomie „🆕 zostanie utworzony"
(enumy), a 58779380 (nowa marka 尚界) wychodziło sierotą + „mało danych" w specyfikacji.
**Import che168 został w trakcie sesji włączony** (`ASIAAUTO_CHE168_IMPORT_ENABLED=true`) —
faza obserwacji domknięta; import per-numer kuratorski przez osobną stronę „Import z Che168".

## Decyzje (wszystkie ADDYTYWNE — strefa krucha `importListing`/`setTaxonomies` NIETKNIĘTA)

### 1. Normalizacja enumów atrybutów (v0.33.4)
Korzeń: tożsamość termu w `setTaxonomyAndMeta` idzie po **slugu z surowego `api_value`**, nie z
tłumaczenia → surowiec che168 (`crossover/suv`, `plug-in hybrid`, `awd (front-engine)`) dawał
śmieciowe duplikaty. Fix: `data/che168-enum-map.php` (płaska mapa danych, domena ZAMKNIĘTA z
`getFilters`) + `Adapter::normalizeEnums()` przed kanonizacją tożsamości. Słowniki Dongchedi
reużyte 1:1. Mild-hybryd zwinięty w HEV (decyzja Janka). ADR: `2026-06-18-che168-enum-normalize.md`.

### 2. Cleanup (v0.33.5)
Usunięty martwy `resolveForSource()` (po 2026-06-17 wołany tylko `canonicalKeyForSource`+`getEuForCn`).

### 3. Grupa B — luki mark/model (v0.33.6)
`Tank|Tank 300 New Energy`→konsolidacja do huba Tank/300 (`che168-model-map`); `IM Motors|LS8`
→ nowy wpis `brand-mapping-v6.1` (realny nowy model).

### 4. Nowa marka 尚界 → SAIC Shangjie (v0.33.7)
**Procedura onboardingu marki czysto-CJK:** API daje tylko CJK → romanizacja z VIN (WMI `LSJ`=SAIC)
+ web/Wikipedia + reference west-motors. Etykieta **SAIC Shangjie** (decyzja Janka; alt: SAIC /
Shangjie). Wpięcie: `resolveChe168` `cnPrefix`+`$markAlias` `尚界`→`SAIC Shangjie` (skaluje — kolejne
modele = tylko wpis w brand-mappingu) + `brand-mapping-v6.1` `SAIC Shangjie|Z7T`.

### 5. Specyfikacja — param-map + wartości (v0.33.8/0.33.9)
Diagnoza „mało danych": che168 ZE ŹRÓDŁA zwraca mniej (~59 vs dongchedi 371; brak list
wyposażenia — `extra.option`/`moreoptions` puste). To granica nieusuwalna. Co da się odzyskać:
- `che168-param-map.php` to **adapter `id→klucz`** (che168 podaje param pod numerycznym `id`,
  dongchedi pod stringiem); **cała reszta — etykiety/kategorie/sortowanie/wartości — to WSPÓLNY
  `translations-extra-prep.php`** (ten sam co dongchedi → grupowanie identyczne automatycznie).
- Sample-based (próbka 21 ofert dopasowanych do filtrów = same chińskie marki): +32 mapowania
  (zasięg/zużycie/moc/moment/bateria…) + blok wartości CJK che168 (zawieszenie `悬架`≠dongchedi
  `悬挂`, ogniwa CATL/CALB/EVE…) + wzorce w `translateExtraPrepValue` (gwarancja z chińskimi
  cyframi lat, `增程器`, oktan `号`). Pokrycie Z7T 28→44, próbka 47%→75%, 0 blokad CJK.

### 6. Tytuł z wersją + fallback rocznika (v0.33.10/0.33.11)
Builder tytułu/sluga jest **wspólny i agnostyczny** (`computeIdentity`): tytuł =
`{mark} {model} {year} {complectation}`, slug = `{mark}-{model}-{year}-{post_id}` (BEZ wersji).
Różnica była w DANYCH: che168 ma `complectation` puste, trim w `param_93` (车型名称) za „YYYY款".
Fix: adapter ekstrahuje trim → `complectation`, fallback rocznika `year`→`first_registration`→
`param_93`. `translateComplectation` strip resztkowego CJK (guarded, dongchedi bez regresji).

## Zasady utrwalone
- **Utwórz vs przemapuj:** przemapuj gdy duplikat istniejącego termu; utwórz tylko realnie nową
  czystą kategorię (mhev odrzucony, ale bi-fuel/LS8/SAIC Shangjie = legalne utworzenia).
- **Adapter = jedyne miejsce dialektu che168** (mark/model/enumy/wersja/rok/spec-id). Downstream
  wspólny z dongchedi. Strefa krucha nietknięta.
- **Sample-based mapowanie:** mapę buduj z próbki dopasowanej do filtrów importu, nie z 1 oferty.
  API w abonamencie (nie per-token) → próbkować można swobodnie.

## Stan / pliki
Serwer v0.33.11. `data/{che168-enum-map,che168-param-map,che168-model-map,brand-mapping-v6.1,
translations-extra-prep}.php`, `includes/{class-asiaauto-che168-adapter,class-asiaauto-mapping,
class-asiaauto-translator}.php`. Backupy `.bak-2026-06-18-*`. Import che168 **ON**.
Powiązane: `2026-06-16-che168-manual-import.md`, `2026-06-17-che168-normalize-at-entry.md`.
