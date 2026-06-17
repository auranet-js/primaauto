# ADR 2026-06-17 — Che168: normalizacja tożsamości PRZY WEJŚCIU (T-186, v0.33.2/0.33.3)

## Kontekst

T-185 (faza obserwacji) zostawił otwarty spór: czy surowe `mark`/`model` z che168 są
identyczne z Dongchedi (→ ten sam pipeline → ten sam hub), czy się rozjeżdżają. Realny
import che168 był OFF, `resolveForSource`/`resolveChe168` żyły tylko w ścieżce dry-run,
a plan T-186 zakładał „świadome wpięcie resolvera w `importListing`" (edycję strefy kruchej).

## Dowód (read-only, `tmp/che168-vs-dongchedi-proof-2026-06-17.php`)

Na 12 par dongchedi↔che168 tego samego auta:
- surowe `mark`/`model` identyczne: **0/12**,
- `getEuForCn(surowiec che168)` (pipeline Dongchedi karmiony che168): trafia **1/16**.

che168 używa chińskich nazw rynkowych aut używanych (`AITO 问界`, `Wey Lanshan`, `智己LS9`,
`Dreamer`, `Fang Cheng Bao`), Dongchedi — nazw z feedu (`AITO`, `Blue Mountain`, `IM LS9`).
**Rozjazd jest realny i systematyczny.** Teza „ten sam surowiec → ten sam hub" obalona.

## Decyzja

Normalizować tożsamość che168 **przy wejściu** (w adapterze), zamiast wpinać resolver w
strefę kruchą:

- `AsiaAuto_Mapping::canonicalKeyForSource($mark,$model,$engine,$source)` — dla che168
  rozwiązuje przez `resolveChe168()` i **reverse-mapuje wynik na literalny klucz CN
  brand-mappingu** (kształt Dongchedi); dongchedi = pass-through.
- `AsiaAuto_Che168_Adapter::normalize()` stempluje `mark`/`model` wartością kanoniczną
  (surowiec zachowany w `*_che168_raw`).
- `importListing()` / `setTaxonomies()` — **bez zmian** (zwykłe `getEuForCn`). Strefa krucha
  nietknięta. Stary plan „resolver w importListing" UNIEWAŻNIONY.
- `computeIdentity()` / `computeTerms()` (dry-run) przełączone z `resolveForSource` na
  `getEuForCn` → **dry-run == realny import** (dane wchodzą już skanonizowane).

### Reguły normalizacji (`resolveChe168`)
0. override `che168-model-map.php` (klucz surowy che168);
0a. wczesny `getEuForCn` na surowych — łapie osobną markę che168 (np. „Galaxy / Galaxy L6"
    == klucz brand-mappingu) PRZED stripem marki, który by to rozbił;
0b. strip CJK z marki (`AITO 问界`→`AITO`) + alias `IM`→`IM Motors`; prefiks modelu `智己`;
1-5. (istniejące) strip prefiksu CN, strip marki z modelu, warianty napędu, reverse-index.

Aliasy nazw domowych (dane, nie kod): Lanshan, Dreamer (PHEV), eπ008, Fang Cheng Bao
Leopard 5, Li L9, Li L6, ET5T, CS75 PLUS iDD / 长安CS75PLUS.

## Zasada jednomiejscowej poprawki

brand-mapping-v6.1 = jedno źródło prawdy. Dodanie **jednego** wpisu z właściwym `serie_eu`
obsługuje przez reverse-index **oba** źródła (che168 normalizator + Dongchedi). Jedyny
wyjątek: dongchedi-side sieroty, gdzie surowiec ≠ własny klucz (np. `IM Motors|IM LS9`
orphan, choć klucz `IM Motors|LS9` istnieje) — rzadkie (kolejne 50 ogłoszeń Dongchedi:
48/50 mapuje). Pełne ujednolicenie (ten sam normalizator też na wejściu Dongchedi) =
ewentualny **T-187** z migracją redirectów (~2-4% z 4742 żywych URL) — osobny, świadomy task.

## Pokrycie (zmierzone)

- Pierwsza 20 ogłoszeń (ścieżka adaptera→getEuForCn): **17/20**.
- Kolejne 41 modeli (po realnym słowniku che168 `getFilters`): **35/41**.
- Reszta = grupa B (decyzje per-hub, nie błędy): Dongfeng Fengxing Xinghai T5
  (sub-brand „Forthing"), BYD Seal U/Song Plus (che168 nazwa wieloznaczna), BYD Han L EV,
  iCAR Super V23, Geely Galaxy Starship 8 (nowe warianty / inna nazwa che168),
  Mazda 3 Axela (poza importem).

## Konsekwencje

- Realny import che168 **nadal OFF** (`ASIAAUTO_CHE168_IMPORT_ENABLED=false`). Strona ręczna:
  analiza / preview / „Zapisz do logu" działa i pokazuje poprawne huby. Realny IMPORT za
  flagą — do świadomego włączenia.
- Przy włączeniu importu: surowy feed che168 = ~90% używane nie-chińskie (Mercedes/BMW/Toyota)
  → import per-numer kuratorski, nie masowy (filtr marek konieczny dla automatu).
- Skrypty dowodowe (gitignored, `tmp/`): `che168-vs-dongchedi-proof`,
  `che168-normalize-at-entry-proof`, `che168-verify-adapter-path`, `che168-vocab-pair-41`,
  `che168-regen-dryrun`.

## Pliki

`includes/class-asiaauto-mapping.php`, `includes/class-asiaauto-che168-adapter.php`,
`includes/class-asiaauto-importer.php` (dry-run compute*), `data/che168-model-map.php`.
Backupy `.bak-2026-06-17-*`. Powiązane: `2026-06-16-che168-manual-import.md`.
