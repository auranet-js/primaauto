# T-190 — dziennik zmian (strefa krucha, trace rollbacku)

> Wymóg Janka (2026-07-07 wieczór): każdy krok logowany — dry-run, term_id/listingi, backup, skrypt, smoke.
> Konwencja wpisu: data · krok · co dokładnie · dowody/pliki · jak cofnąć.

---

## 2026-07-07 · KROK 0 — diagnoza (bez zmian na produkcji)

- **Co:** sesja badawcza — mechanizm duplikatów, skala złych przypisań, DFS 212/BJ30/BJ40.
- **Raport:** `https://auratest.pl/fe4f58fec53ctmp/primaauto-t190-diagnoza-2026-07-07.md`
- **Kluczowe liczby:** 19 wzorców / ~106 aut źle przypiętych; nawroty wszystkich 3 merge'ów galaxy-* z T-019; 6 kolizji slugów wpisanych w mapowanie; „baic bj30" 3600/mc.
- **Decyzja Janka:** Galaxy pod Geely, wzorzec BYD Leopard — ADR `docs/decyzje/2026-07-07-t190-galaxy-pod-geely.md` (zastępuje punkt z ADR T-019).
- **Zmiany plików:** tylko docs (QUEUE.md, 2× ADR, memory). Kod/DB nietknięte.

## 2026-07-07 · KROK 1 — guard importera (PROPOZYCJA, czeka na akcept)

- **Co:** make-aware ścieżka dla taksonomii `serie` w `class-asiaauto-importer.php`:
  - `setSerieTaxonomyAndMeta()` — lookup termu wśród DZIECI marki (slug exact → nazwa znormalizowana → jednoznaczny wariant z/bez prefiksu marki), nowy term ZAWSZE z `parent`=make, meta `serie` = realny slug termu, `_asiaauto_primary_make_slug` od pierwszego zapisu;
  - `resolveSerieTermId()` + `isTokenSuffix()` — dopasowanie; niejednoznaczność (≥2 kandydatów) = osobny term + warning w logu (żadnego cichego zgadywania);
  - `computeTerms()` (dry-run Che168) — `exists` liczone tym samym resolverem (dry-run == import);
  - brak termu marki na poście → fallback 1:1 na starą ścieżkę (`setTaxonomyAndMeta` nietknięte, pozostałe taksonomie bez zmian).
- **Diff (179 linii):** `https://auratest.pl/fe4f58fec53ctmp/primaauto-t190-guard-importer.diff`
- **Kopia robocza:** scratchpad sesji; oryginał: `…/class-asiaauto-importer.php.orig`. `php -l` PASS.
- **Rollback:** przywrócić `.bak` sprzed deployu (powstanie przy wgraniu) — zmiana czysto addytywna, stare metody nietknięte.
- **Weryfikacja po deployu (plan):** ręczny import testowy modelu z kolizją slugów (np. AITO M8 → musi trafić w term 5302 AITO, NIE 3381 GAC) + modelu zmergowanego (Galaxy E5 → term `e5` pod Geely, NIE nowa sierota). Feed zamrożony = okno bezpieczne.
- **Status:** ✅ **WDROŻONE 2026-07-07 wieczór** (akcept Janka „tak").
  - Backupy na serwerze: `class-asiaauto-importer.php.bak-2026-07-07-t190-guard` + `asiaauto-sync.php.bak-2026-07-07-t190-guard` (rollback = `cp` z powrotem).
  - Bump: header `Version` 0.33.14→**0.33.16** + `ASIAAUTO_VERSION` 0.33.15→**0.33.16** (przy okazji zsynchronizowany drift header vs stała).
  - **Test (wp eval-file, `scratchpad/t190-guard-test.php`, testowy draft 387561 + reflection, auto-cleanup): 5/5 PASS**
    - A: AITO „M8" slug `m8` → term **5302 AITO** (nie 3381 GAC) ✅
    - B: Geely „Galaxy E5" stale slug `galaxy-e5` → kanoniczny **3397 `e5`** (nie nowa sierota) ✅
    - C: Avatr „07" slug `07` → **6906 `avatr-07`** (nie 5388 WEY) ✅
    - D: nowy model → term **z parent=geely** (term testowy 7176 utworzony i usunięty) ✅
    - E: post bez marki → fallback stara ścieżka ✅
  - Smoke: homepage + huby aito/m8, geely/e5, byd/atto-3 = **200**; brak nowych wpisów w debug.log.
  - Pozostało do pełnej weryfikacji: realny import (ręczny lub feed po odmrożeniu) — kod przetestowany reflection 1:1, ale ścieżka end-to-end jeszcze nie przeszła.

## 2026-07-07 · KROK 2 — audyt DFS całego mapowania (read-only)

- **Co:** 364 huby (mapowanie ∪ baza) × 663 frazy PL, koszt **$0.11** (saldo ~$43.07). Raport: `https://auratest.pl/fe4f58fec53ctmp/primaauto-t190-dfs-audyt-2026-07-07.md`; rekoncyliacja: `…/primaauto-t190-rekoncyliacja-2026-07-07.md`.
- **Kluczowe:** wzorzec sub-marek potwierdzony (`leopard 5` 2400 vs `byd leopard 5` 0; `geely galaxy m9` 390 vs `galaxy m9` 0); `gac m8`/`gac s7` 110 vs `gac trumpchi *` 0 → **decyzja Janka: drop „Trumpchi"**; +9 par duplikatów po NAZWIE pod tym samym make; 33 huby db-only bez mapowania (Qashqai 165k/mc); decyzje Janka: Lotus→`lotus`, Qiyuan→Changan, Fengyun→`chery-fulwin`, Shangjie→`shangjie` (zastępuje etykietę „SAIC Shangjie" z 06-18).

## 2026-07-07 · KROK 3 — mapowanie v6.2 + merge 13 grup + repoint 102 aut + fold 6 marek ✅

- **Backup:** `~/backups/primaauto/2026-07-07/taxonomy-pre-T190-krok3.sql` (4 tabele, zweryfikowany) + `brand-mapping-v6.1.php.bak-2026-07-07-t190-v62` + `class-asiaauto-redirects.php.bak-2026-07-07-t190-krok3`.
- **Mapowanie v6.2** (plik `brand-mapping-v6.1.php` — nazwa celowo bez zmian, 6 referencji w strefie kruchej; wersja w nagłówku): 18 zmian — galaxy-* → kanoniczne (×11), drop Trumpchi (M8/S7/M6 + title_eu), Avatr `07`→`avatr-07`, Haval `h5`→`haval-h5`, IM `ls8`→`im-ls8`, „SAIC Shangjie"→„Shangjie". Diff: `https://auratest.pl/fe4f58fec53ctmp/primaauto-t190-mapping-v62.diff`. **Re-rekoncyliacja po: DRIFT-SLUG 0/273** (było 11).
- **Skrypt:** `scratchpad/t190-merge-repoint.php` (dry-run→apply, problems=0). Wykonane:
  - NOWY term 7177 „Galaxy M7" (slug m7, parent geely) — listing 383469 „Galaxy 银河M7" był na AITO M7 (5301).
  - RE-PARENT/RENAME ×17: ForMe→lotus, Smart#5→smart, Evoque→land-rover, 3381→„M8", 3377→„M6", Eletre→lotus, Hyper GT/HT/HL/SSR/A800→GAC + „Aion Hyper X", Qiyuan E07/A06/A06 Classic/Q05/A05→Changan + drop „Changan".
  - MERGE ×13 (src skasowany, listingi+meta przeniesione): 7160→3406, 7166→3397, 7164→6550, 6590→3400, 7159→7153, 6596→6516, 6585→3407, 7162→5739, 4614→6530, 6592→6249, 3993→6511, 6594→6569, 6512→6271.
  - REPOINT kontaminacji: 26× AITO M8 (3381→5302), 9× Avatr 07 (5388→6906), 2× Denza N7, 2× Hongqi H6, 1× AITO M6, 1× Galaxy M9 (387316), 1× Galaxy M7 (383469).
  - FOLD MAREK (listingi+meta+dzieci, pusty term skasowany): galaxy 6579→geely (39), lotus-cars 5665→lotus, gac-aion-hyper 5485→gac (24), chery-fengyun 5181→chery-fulwin, saic-shangjie 7157→shangjie, changan-qiyuan 4769→changan (8).
  - DELETE puste skorupy: 6735, 6739, 6995, 4273. RECOUNT+REGEN tytułów: 40 termów.
- **Redirecty** (`class-asiaauto-redirects.php`): V61 fix `changan-qiyuan`→`changan` (było `nevo`!) + dodane `saic-shangjie`→`shangjie`; V62 +5 (zeekr-8x, lynk-co-900, mazda-ez-60, volvo-xc70, li-l7). V62 geely galaxy-* i gac trumpchi-* JUŻ istniały (v0.32.41).
- **Weryfikacja:** mismatch make↔serie-parent na całej bazie = **0** (było 21 wzorców / 102 auta); 9×301 na właściwe cele; 10 hubów 200 bez „Nie znaleziono modelu".
- **Rollback:** SQL dump 4 tabel + 3 pliki .bak (patrz wyżej).
- **Zostało (KROK 4 — routing marek):** BAIC×4+BAW/212 (+BJ30→BAIC 3600/mc), Yangwang→BYD, Maextro własna marka (zdjąć V61 `maextro`→`luxeed`), Dongfeng Fengxing, ~20 pustych sierot „Beijing *".

## 2026-07-07 · KROK 4 — routing marek: BAIC/BAW/212 + Yangwang + Maextro + Fengxing ✅

- **Skrypt:** `scratchpad/t190-krok4-routing.php` (dry-run→apply, problems=0). Backup: dump z KROK 3 aktualny (żadnych zmian taksonomii między krokami) + `.bak` redirects z KROK 3.
- **Serie:** 6603 „Beijing Off-road BJ30"→**„BJ30" slug `bj30` pod BAIC** (DFS 3600/mc); 5688 „T01"→**„212 T01" pod BAW** (slug `t01` zostaje); Yangwang U9/U8L (6607/6600)→parent BYD (nazwy/slugi bez zmian — wzorzec Leopard); Xinghai T5 + Lingzhi EV→parent Dongfeng; 5338 „Zunjie S800"→**„S800" slug `s800`** (Maextro zostaje własną marką).
- **Fold marek** (listingi+meta, pusty term skasowany): beijing-off-road 4779→baic, beijing-212 6522→**baw 5547**, yangwang 5640→byd, dongfeng-fengxing 4675→dongfeng. Skasowane puste marki: beijing 5254, 212 5687. **Stan po: baic(11) + baw(8) + maextro(2), zero marek „Beijing*"**.
- **Sieroty:** 23 puste termy (Beijing*/Lingzhi/Xinghai, count=0, parent=0) skasowane.
- **Mapowanie:** `Beijing 212|T01` → mark_eu **BAW**, serie_eu „212 T01" (importer nie odtworzy marki Beijing 212). Yangwang już był pod BYD w mapowaniu; che168-map Beijing Off-Road już celował w BAIC.
- **Redirecty V61/V62:** `212`→`baw` (było beijing-212), +`beijing-212`→`baw`, +`beijing`→`baic`, **USUNIĘTE `maextro`→`luxeed`** (301 szło do złego producenta — hub `/samochody/maextro/` znów żywy); V62 +`baic:beijing-off-road-bj30`→`bj30`, +`maextro:zunjie-s800`→`s800`.
- **Weryfikacja:** 8×301 na właściwe cele, 8 hubów 200 bez „Nie znaleziono modelu" (w tym `/baic/bj30/`, `/baw/t01/`, `/maextro/s800/`, `/byd/yangwang-u9/`). **Niezmienniki globalne: mismatch=0, sieroty-z-autami=0, duplikaty-po-nazwie=0.**
- **Znane ograniczenie (do obserwacji):** guard chroni serie, ale MAKE nadal tworzy się po slugify — niezmapowany import z marką „Yangwang"/„Beijing…" (np. ręczny che168 spoza che168-model-map) odtworzy markę. Ochrona = wpisy w mapowaniach; ewentualny mark-alias-guard to osobny, mały task.

## STAN T-190 po 2026-07-07: guard ✅ · mapowanie v6.2 ✅ · merge+repoint ✅ · routing ✅
**Otwarte resztki:** (a) normalizacja ~40 redundantnych slugów `make-model` z treścią/indeksem (301, niski priorytet); (b) domapowanie 33 hubów db-only z popytem (klucze CN z meta); (c) weryfikacja e2e guarda przy realnym imporcie (feed zamrożony); (d) ewent. mark-alias-guard.
