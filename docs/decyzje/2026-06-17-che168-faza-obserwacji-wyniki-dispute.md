# 2026-06-17 — Che168 faza obserwacji: wyniki E2E + spór metodologiczny → nowy wątek

> Kontynuacja T-185 (Che168 ręczny import). ADR bazowy: `2026-06-16-che168-manual-import.md`.
> Status na koniec sesji: **brak edycji plików** (czysta analityka). Realny import che168 **zostaje OFF**.

## 1. Co zrobiono (Etap 1 — E2E w Chrome)

- Ustalono numery che168 dla **20 modeli docelowych** przez API (`getOffers` z filtrem mark+model). Lista: `tmp/che168-numbers-2026-06-16.txt`. Wszystkie 20 mają żywe oferty.
- Przepuszczono **20/20 przez stronę „Import z Che168"** (Chrome E2E: wklej numer → Pobierz podgląd → Zapisz do logu). 20 snapshotów w `wp-content/uploads/asiaauto/che168-dryrun/*.json` (+ Denza D9 z poprzedniej sesji = 21).
- Gate potwierdzony: `js` widzi, `primaauto` (Ruslan) NIE (allowlist `ASIAAUTO_CHE168_PREVIEW='js'`). `ASIAAUTO_CHE168_IMPORT_ENABLED=false`.

## 2. Czego NIE zrobiono i dlaczego (Etap 2 wstrzymany)

Etap 2 (domknięcie mapowania) **wstrzymany przed jakąkolwiek edycją** po serii korekt metodologicznych Janka. Pierwsza diagnoza („13 sierot modeli, 77 nieznanych param, enumy do dorobienia") okazała się **porównaniem na złym etapie** — surowy dry-run che168 vs goły stan taksonomii, NIE vs gotowe/surowe dane Dongchedi.

Ustalenia faktyczne (kod + dane, read-only):
- Translacja enumów istnieje: `translations-{body,engine,drive}-types.php` (klucze ANG z feedu → polski term). Dongchedi daje surowo `SUV`/`EREV`/`AWD`; che168 daje `crossover/suv`/`range extender`/`awd (dual-motor)` — **inne stringi**.
- **Dwa różne resolvery:** realny `importListing` → `getEuForCn(markCN,modelCN)` (klucz CN Dongchedi, `brand-mapping-v6.1`, 271 par). Dry-run che168 → `resolveForSource(...,'che168')` → `resolveChe168()` (override `che168-model-map` → strip CN-prefix → strip marki → warianty napędu → reverse-index). **resolveForSource NIE jest wpięty w realny import** (T-186).
- Term-po-termie z 20 snapshotów: 11/20 „sierota" to **fałszywy alarm** (make+serie istnieją, wjeżdża na właściwy hub; „sierota" = brak jawnego override). 9/20 to **różnica stringu** che168 vs nasz istniejący term (np. che168 `Fang Cheng Bao`/`Leopard 5` ↔ nasz `BYD`/`Leopard 5 (Denza B5)`; `VOYAH`/`Dreamer` ↔ `Voyah`/`Dream PHEV`).
- **Realna różnica nazw potwierdzona** (che168 = chińskie nazwy RYNKOWE aut używanych, my/Dongchedi = eksportowe): che168 BYD `Yuan Plus`=nasz `ATTO 3`, `Song`=`Song Plus/Seal U`, `Tang`=`Tang DM-i/EV`. resolveForSource: `Seal`/`Han`/`Dolphin` ✅, `Yuan Plus`/`Song`/`Tang` ❌ NULL.
- **Audyt pokrycia hubów był WADLIWY** (`tmp/che168-hub-coverage-audit-2026-06-16.php`): puszczony z `engine=''` (wyłączył warianty napędu DM-i/EV/EREV → zaniżył) + złączenie po nazwie termu serie vs `serie_eu` (format mismatch). Liczby (34%/241 niezabezpieczonych) **niewiarygodne** — nie ufać.

## 3. Spór nierozstrzygnięty → przeniesiony do nowego wątku

**Teza Janka:** surowe `mark`/`model` z che168 i Dongchedi dla tego samego auta są identyczne → ten sam pipeline (translatory/mapowanie) daje ten sam hub; rozjazdu nie ma.
**Teza przeciwna:** surowe `mark`/`model` różnią się (nazwy rynkowe vs eksportowe) → ten sam pipeline daje inny wynik, bo `brand-mapping-v6.1` kluczowany stringami Dongchedi.

Jeden zmierzony przykład rozjazdu (do potwierdzenia/rozszerzenia): Dongchedi `getOffer('dongchedi','23033039')` → `mark="AITO"`, `model="Aito M9"`; che168 `getOffer('che168','55603575')` → `mark="AITO 问界"`, `model="AITO M9"`. **Różne.** Ale to za mało na werdykt — wątek się rozjechał, decyzja: **rozstrzygnąć na czystych danych w świeżym wątku.**

## 4. Decyzja

- **Realny import che168 zostaje OFF.** Żadnych edycji map/translacji do czasu rozstrzygnięcia sporu.
- Spór rozstrzyga **nowy wątek** wg promptu `tmp/PROMPT-che168-vs-dongchedi-raw-proof-2026-06-17.md` — decydujący test: nakarmić `getEuForCn()` (pipeline Dongchedi) surowcem che168 i porównać hub; werdykt na ~18 parach dongchedi↔che168.
- Dopiero werdykt (rozjazd jest/nie ma, w jakiej skali) decyduje, czy i jakiego mechanizmu domknięcia trzeba (normalizacja che168→słownik Dongchedi, addytywnie; resolver w realny import = T-186).

## 5. Artefakty (reużywalne, read-only)

- `tmp/PROMPT-che168-vs-dongchedi-raw-proof-2026-06-17.md` — prompt nowego wątku (proof).
- `tmp/che168-numbers-2026-06-16.txt` — 20 modeli → numer che168 + mark/model.
- `tmp/che168-aggregate-2026-06-16.py`, `tmp/che168-sierota-why-2026-06-16.py` — agregacja snapshotów / analiza „dlaczego sierota".
- `tmp/che168-hub-coverage-audit-2026-06-16.php` — audyt pokrycia (WADLIWY, do poprawy w nowym wątku).
- `wp-content/uploads/asiaauto/che168-dryrun/*.json` — 20 snapshotów dry-run (na serwerze, nie w repo).
