# ADR 2026-06-16 — Che168 ręczny import + log wdrożeniowy (T-185)

**Status:** wdrożone na prod (v0.33.0). Realny import Che168 WYŁĄCZONY (faza obserwacji).

## Kontekst

Feed **dongchedi** (auto-api.com) nawracająco pada (3 pady w czerwcu, T-182). Dostawca steruje fallbackiem na **Che168** — ale Che168 to C2C marketplace aut **używanych** (zachodnie premium, mediana rocznik 2017/84k km, chińskie NEV-y rzadkie, overlap egzemplarzy z dongchedi 0/30). Automatyczny import w stylu dongchedi = <1% pokrycia. Decyzja: **kurator (Janek/Ruslan) wybiera pojedyncze auta ręcznie**, nie automat.

## Decyzja

Rozbudowa istniejącej strony „Dodaj z Dongchedi" o obsługę źródła Che168 — **ukrytą (gate per login)**, z **pełną symulacją importu (dry-run)** przed jakimkolwiek zapisem, oraz **logiem wdrożeniowym** (dataset JSON do kalibracji mapowania przez kilka dni przed włączeniem realnego importu).

## Architektura (sedno)

**Wspólny kod symulacji i realnego importu.** `AsiaAuto_Importer::importListing()` zrefaktorowany przez ekstrakcję czystych metod:
- `computeIdentity($data,$source)` — mark/serie/model_for_slug/title/mapped/slug_pattern
- `computeMeta($data)` — `[meta_key => value]` (setMotorsMeta jest teraz pętlą po niej)
- `computeTerms($data,$source)` — lista `[taxonomy,value,slug,api_value,exists]` (setTaxonomies iteruje po niej)
- `buildPlan($data,$source)` — składa powyższe + images + price + extra_prep + warnings

To co Janek ogląda w dry-run JEST tym, co trafi do bazy przy realnym imporcie — bez driftu sim↔real. **Strefa krucha** (CLAUDE.md §3.2) dotknięta za świadomą zgodą Janka (2026-06-16); refaktor = czysta ekstrakcja bez zmiany zachowania, chroniony testem regresji.

## Komponenty

| Plik | Rola |
|---|---|
| `class-asiaauto-importer.php` | ekstrakcja compute*/buildPlan (strefa krucha) |
| `class-asiaauto-mapping.php` | `resolveForSource()` — reverse-index brand-mappingu + reguły (strip CN-prefiks, strip marki EN, wariant napędu, CAPS/spacje, guard CJK); dongchedi→getEuForCn bez zmian |
| `data/che168-model-map.php` | 51 ręcznych nadpisań resolvera (seed z reconu 06-05) |
| `class-asiaauto-che168-adapter.php` | normalizacja: address→city, first_registration→reg_date, extra.configuration→extra_prep (po `id`) |
| `data/che168-param-map.php` | 51 id Che168 → klucz extra_prep dongchedi (reszta → `param_{id}`) |
| `class-asiaauto-che168-log.php` | snapshot dry-run JSON → `uploads/asiaauto/che168-dryrun/` |
| `class-asiaauto-admin-manual-import.php` | gate, detekcja źródła, pełna tabela zgodności, przycisk „Zapisz do logu", warunkowy import, widok listy logu |

## Gate (ukryte, tylko dla Janka)

- `wp-config.php`: `ASIAAUTO_CHE168_PREVIEW = 'js'` (CSV loginów) — brak stałej = całość niewidoczna, zero zmian UX dongchedi
- `ASIAAUTO_CHE168_IMPORT_ENABLED = false` — realny import Che168 ukryty (faza obserwacji); przycisk „Zaimportuj" dla che168 renderowany tylko gdy `true`

## Weryfikacja (smoke `wp eval-file`, brak PHPUnit w repo)

- **Regresja dongchedi:** `buildPlan(getOffer)` vs 6 realnych listingów (stworzonych starym kodem) = **title 6/6, meta 88/88, terms 54/54** — przed i po wpięciu resolvera. Zero driftu.
- **Resolver che168:** 5/5 ze spec (denza/n8l, denza/d9-dm-i, zeekr/001, avatr/12, sierota dla 红旗金葵花国耀).
- **Adapter:** 5/5 city+vin+reg_date, extra_prep niepusty (34-51 zmapowanych + 21-50 param_), obrazy host `2sc2.autoimg.cn`.
- **Tabela podglądu:** che168 Denza D9 → 9 taksonomii ze statusami, 11 meta, 8 kategorii/46 wierszy spec. Sygnał kalibracji: top-level enumy che168 po angielsku (`plug-in hybrid`, `awd (front-engine)`) → `🆕nowy` term (do dorobienia w mapach wartości przed realnym importem).
- **Log:** save→all→re-decode OK, diakrytyki zachowane.

## Faza obserwacji (otwarta)

Janek wkleja numery Che168 i klika „Zapisz do logu". Wspólny przegląd `che168-dryrun/*.json`: co weszłoby, co sierota, brakujące param-id / wartości → iteracyjne douzupełnianie `che168-model-map.php` / `che168-param-map.php` / `translations-extra-prep-values` / reguł resolvera. Gdy pokrycie OK → `ASIAAUTO_CHE168_IMPORT_ENABLED=true`. Ewentualny automat = osobny T-186.

## Backupy

`.bak-2026-06-16-che168` na: importer, mapping, manual-import, asiaauto-sync, wp-config.

## Odrzucone

Osobny symulator duplikujący logikę importera — drift sim↔real sabotowałby cel kalibracji.
