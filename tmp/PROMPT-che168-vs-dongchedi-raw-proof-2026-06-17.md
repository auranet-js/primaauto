# PROMPT — DOWÓD: czy surowe che168 == surowe Dongchedi (ten sam pipeline → ten sam hub?)

> Nowy wątek: `cd ~/projekty/primaauto && claude`. Wklej sekcję „PROMPT DO WKLEJENIA".
> Wymaga: che168 + dongchedi API żywe. Chrome NIE wymagany (czysta analityka przez `wp eval`).
> Powód nowego wątku: poprzedni (kalibracja T-185) zrobił się długi i zaczął się rozjeżdżać — ten ma JEDEN cel: rozstrzygnąć spór danymi.

---

## PROMPT DO WKLEJENIA

Masz **rozstrzygnąć empirycznie jeden spór**, bez edycji jakiegokolwiek pliku (czysta analityka, read-only). Nie zakładaj z góry żadnej strony — niech zdecydują surowe dane.

### Spór do rozstrzygnięcia
- **Teza Janka:** pobieramy surowe dane z Dongchedi i **na tej samej zasadzie** z che168. Surowe `mark`/`model` dla tego samego auta powinny być **identyczne**; potem oba przechodzą przez **ten sam** zestaw translatorów/mapowań, więc na wyjściu powstaje **ten sam hub** (make/serie). Rozjazdu nie ma.
- **Teza przeciwna (do obalenia lub potwierdzenia):** surowe `mark`/`model` **różnią się** między źródłami (che168 = chińskie nazwy rynkowe aut używanych, np. BYD „Yuan Plus"; Dongchedi = nazwy z feedu, np. „Aito M9" / inne), więc **ten sam pipeline daje inny wynik** dla che168, bo tabela mapująca (`brand-mapping-v6.1`) jest kluczowana stringami Dongchedi.

### Co już ustalono w poprzednim wątku (NIE powtarzaj od zera, ale ZWERYFIKUJ na żywo)
- Realny import che168 = **OFF** (`ASIAAUTO_CHE168_IMPORT_ENABLED=false`), zostaje OFF. Wersja pluginu 0.33.1.
- **Realny `importListing`** (class-asiaauto-importer.php ~linia 105) woła `AsiaAuto_Mapping::getEuForCn($markCN, $modelCN)` — lookup po **CN stringach Dongchedi** w `data/brand-mapping-v6.1.php` (271 par). Fallback gdy brak: `mark = surowy`, `serie = translateModel(model)`.
- **Dry-run che168** (computeIdentity/computeTerms, te same pliki) woła `AsiaAuto_Mapping::resolveForSource($mark,$model,$engine,'che168')` → `resolveChe168()`: (0) override `data/che168-model-map.php` → (1) strip CN-prefiksu marki → (2) strip marki EN z modelu → (3) jak CJK zostaje → fallback → (4) warianty napędu DM-i/EV/EREV + reverse-index brand-mappingu → (5) fallback `getEuForCn`.
- **Jeden zmierzony przykład rozjazdu (do potwierdzenia/rozszerzenia):** Dongchedi `getOffer('dongchedi','23033039')` → `mark="AITO"`, `model="Aito M9"`. che168 `getOffer('che168','55603575')` → `mark="AITO 问界"`, `model="AITO M9"`. Różne. Dodatkowo che168 BYD modele: `Yuan Plus`(=nasz ATTO 3), `Song`(=Song Plus/Seal U), `Tang`(=Tang DM-i/EV) — `resolveForSource(...,'che168')` z `engine=''` zwracał NULL.
- **Artefakty (gotowe, reużywalne):**
  - `tmp/che168-numbers-2026-06-16.txt` — 20 modeli → numer che168 + che168 mark/model.
  - `wp-content/uploads/asiaauto/che168-dryrun/*.json` — 20 snapshotów dry-run che168 (raw_data + plan + extra_prep).
  - `tmp/che168-hub-coverage-audit-2026-06-16.php` — audyt pokrycia (UWAGA: wadliwy — `engine=''` zaniżał warianty napędu; złączenie hubów po nazwie termu vs serie_eu = format mismatch). Popraw, nie ufaj jego liczbom.
  - Pary dongchedi↔che168 (inner_id Dongchedi z naszych ogłoszeń ↔ numer che168) do testu:
    ```
    AITO M9        dongchedi 23033039  che168 55603575
    AITO M8        dongchedi 24154786  che168 57345012
    Li Auto L9     dongchedi 24154723  che168 56072168
    IM LS9         dongchedi 24151270  che168 58317978
    IM LS8         dongchedi 24154767  che168 58645565
    Tank 300 Hi4-T dongchedi 24155189  che168 57762274
    AITO M7        dongchedi 24158735  che168 57161580
    Voyah Dream    dongchedi 24083840  che168 56265713
    WEY 07         dongchedi 24155802  che168 57474626
    BYD Han DM-i   dongchedi 24160205  che168 56308602
    Zeekr X        dongchedi 24155610  che168 56907198
    Mazda CX-5     dongchedi 23626981  che168 56958722
    ```
    (inner_id Dongchedi = `_asiaauto_inner_id` naszych ogłoszeń; jeśli `getOffer('dongchedi',…)` zwróci pusto = oferta wygasła → weź inną żywą ofertę Dongchedi tego samego modelu lub odczytaj raw z meta ogłoszenia.)

### KROK 0 — weryfikacja na żywo
1. `grep "ASIAAUTO_VERSION'," asiaauto-sync.php` ≥ 0.33.1; `ASIAAUTO_CHE168_IMPORT_ENABLED=false`.
2. che168 i dongchedi API żywe: `getOffer('che168','55603575')` i `getOffer('dongchedi','23033039')` zwracają dane (read-only). Jeśli któreś puste → zgłoś i dobierz żywe oferty.

### DOWÓD — decydujący eksperyment (czysto analityczny, `wp eval`)
Dla każdej pary z tabeli (i dobierz 5-8 dodatkowych par BYD/Geely/Chery, gdzie nazwy domowe≠eksportowe — np. BYD Yuan Plus/Song/Tang, Geely, Chery):

1. **Surowiec obok surowca.** `getOffer('dongchedi',D)` vs `getOffer('che168',C)`. Wypisz **dosłownie**: `mark_d`, `model_d` | `mark_c`, `model_c`. Kolumna **IDENTYCZNE? (tak/nie)**. To bezpośrednio testuje tezę Janka („surowe mark/model identyczne").
2. **Ten sam pipeline, oba źródła.** Policz i pokaż:
   - `getEuForCn(mark_d, model_d)` → hub Dongchedi (mark_eu/serie_eu lub NULL).
   - `getEuForCn(mark_c, model_c)` → **co da pipeline Dongchedi nakarmiony surowcem che168** (kluczowy test tezy: jeśli identyczne do wiersza wyżej → Janek ma rację).
   - `resolveForSource(mark_c, model_c, engine_c, 'che168')` → hub przez ścieżkę che168 (z **prawdziwym `engine_type`**, nie pustym!).
3. **Werdykt per para:** czy wszystkie trzy lądują na tym samym (mark_eu, serie_eu)? Gdzie i dlaczego się rozjeżdża (CJK w marce? nazwa domowa vs eksport? wariant napędu?).
4. **Podsumowanie liczbowe:** na N par — ile ma identyczny surowy mark/model, ile zbiega się na wyjściu mimo różnego surowca (bo resolveChe168 normalizuje), ile realnie się rozjeżdża. To jest odpowiedź na spór: rozjazd jest, czy go nie ma, i w jakiej skali.
5. **Jeśli rozjazd istnieje** — pokaż minimalny, NIE-dublujący mechanizm domknięcia (normalizacja stringów che168 → słownik Dongchedi, wpięta w istniejący `getEuForCn`/brand-mapping; resolver w realny import = T-186). **Jeśli rozjazdu nie ma** — przyznaj to wprost, teza Janka potwierdzona, T-185 enum/param do przemyślenia od nowa.

### Zasady
- **Zero edycji plików.** Tylko `wp eval`/`Read`/skrypty czytające. Realny import che168 zostaje OFF.
- **Surowe dane dosłownie** (nie parafrazuj „SUV"/„AITO" — pokaż dokładny string z API, łącznie z CJK).
- Nie ufaj wnioskom poprzedniego wątku ani staremu audytowi — **przelicz sam**. Cel: rozstrzygnięcie, które Janek może zweryfikować wzrokiem na surowych danych.
- Pliki danych (`che168-model-map`, `brand-mapping-v6.1`, `translations-*`) = referencja do czytania, nie do zmiany w tym wątku.
- Kontekst: memory `project_che168_manual_import_done_2026_06_16`, `feedback_additive_not_fragile_zone`, `project_brand_model_mapping`; ADR `docs/decyzje/2026-06-16-che168-manual-import.md`.

Zacznij od KROK 0, potem zrób eksperyment na pełnej tabeli + dobranych parach BYD, i podaj Jankowi **werdykt na surowych danych**: rozjazd jest czy nie, gdzie, w jakiej skali.
