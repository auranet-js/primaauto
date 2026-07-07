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
