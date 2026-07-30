# 10.08.2026 — T-222: decyzja o resztkach dongchedi przed wygaśnięciem API

> **Projekt:** `primaauto`
> **Deadline, którego to dotyczy:** **15.08.2026** — koniec opłaconego okresu auto-api
> **Dlaczego 10.08:** pięć dni zapasu na wykonanie decyzji, nie na jej podjęcie
> **Ryzyko ze specu:** R1
> **Spec:** `docs/superpowers/specs/2026-07-30-t222-migracja-dongchedi-che168-design.md`
> **Commit bazowy:** `751c8c4`

---

## Problem do rozstrzygnięcia

Po 15.08 nie ma czym weryfikować, które oferty dongchedi jeszcze istnieją.

Dziś działa to tak: dongchedi siedzi w trybie `verify` (`asiaauto_sync_mode_dongchedi=verify`) —
nie importuje nowych ofert, ale nadal łapie zdarzenia `removed` i wygasza je przez `markRemoved()`
→ `draft` → 301 na hub modelu. **To jedyny mechanizm, który mówi nam prawdę o żywotności zapasu.**
Wygasa razem z subskrypcją.

Bez niego oferta dongchedi zostaje na `publish` w nieskończoność, mimo że auto dawno sprzedane.
Oferta na dongchedi żyje realnie **2–4 tygodnie** (rozkład wieku martwych z przemiału 29.07:
15–30 dni → 800 szt., 1–2 mies. → 524). Czyli po odcięciu cały pozostały zapas zgnije w ~6 tygodni.

Do tego dochodzi **R6**: werdykty przemiału się starzeją. Cron gaszenia (`gasz-martwe-oferty.php`,
4:25, 200/dobę) bierze **wyłącznie** `_asiaauto_source_check IN ('usunieta','wydmuszka')`. Oferta,
która umarła po 29.07, nie ma werdyktu i nigdy do puli nie wejdzie. Na 30.07 było 229 ofert bez
werdyktu wcale.

**Wniosek: reguła musi objąć CAŁY zapas dongchedi, nie tylko resztki z przemiału.**

---

## Co sprawdzić (kolejność)

```bash
cd /home/host476470/domains/primaauto.com.pl/public_html

# 1. Ile zostało dongchedi i w jakim stanie
wp eval '
global $wpdb;
foreach ($wpdb->get_results("SELECT p.post_status st, COALESCE(ch.meta_value,\"brak-werdyktu\") w, COUNT(*) c
  FROM {$wpdb->posts} p
  JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key=\"_asiaauto_source\" AND s.meta_value=\"dongchedi\"
  LEFT JOIN {$wpdb->postmeta} ch ON ch.post_id=p.ID AND ch.meta_key=\"_asiaauto_source_check\"
  WHERE p.post_type=\"listings\" GROUP BY 1,2 ORDER BY 1,2") as $r)
  printf("%-10s %-16s %5d\n",$r->st,$r->w,$r->c);'

# 2. Ile che168 realnie dowozi (R2 — Janek 30.07: „będzie dobrze", ale liczba i tak potrzebna)
wp eval 'foreach (AsiaAuto_Sync::getHistory(50) as $r) if ($r["source"]==="che168")
  printf("%s added=%d skipped=%d\n",$r["ts"],$r["added"],$r["skipped"]);'

# 3. Czy gaszenie nadąża (log crona)
tail -30 ~/.claude/gasz-martwe-dongchedi.log

# 4. Ile ofert dongchedi zostało wycofanych przez tryb verify od 30.07
wp eval 'foreach (AsiaAuto_Sync::getHistory(50) as $r) if ($r["source"]==="dongchedi")
  printf("%s removed=%d changed=%d\n",$r["ts"],$r["removed"],$r["changed"]);'
```

---

## Warianty decyzji

| # | Wariant | Kiedy właściwy |
|---|---|---|
| **A** | **Reguła wieku.** Brak potwierdzenia `_asiaauto_last_sync` / `_asiaauto_source_check_at` starszy niż N dni (kandydat: 30) → `draft`. Obejmuje CAŁY zapas dongchedi, nie tylko werdykty. Rozłożone jak dziś, ~200/dobę. | gdy che168 dowozi na tyle, że kurczenie zapasu nie boli |
| **B** | **Twarde wygaszenie całej reszty dongchedi** na 15.08. Radykalne, ale uczciwe — nie udajemy, że wiemy, co żyje. Duży spadek liczby ofert w jednym dniu. | gdy zapas che168 już przewyższa dongchedi |
| **C** | **Przedłużyć subskrypcję o miesiąc** i wygasić naturalnie do końca. ~87 EUR (stawka dev z 15.07) albo 145 EUR (pełna). | gdy che168 NIE dowozi i odcięcie zostawiłoby ofertę-wydmuszkę |

Wybór zależy prawie wyłącznie od odpowiedzi na pytanie z kroku 2: **ile che168 realnie dowozi na dobę.**

---

## Jak wygląda „zrobione"

- Decyzja A/B/C podjęta i zapisana jako ADR w `docs/decyzje/2026-08-1X-<temat>.md`.
- Jeśli A → reguła wieku wdrożona w `gasz-martwe-oferty.php` (dziś czyta tylko werdykt), przetestowana dry-runem.
- Jeśli B → jednorazowy skrypt + backup `mysqldump` przed.
- Jeśli C → faktura opłacona, przypomnienie przestawione na kolejny miesiąc.
- W każdym wariancie: sprawdzić, czy `class-asiaauto-redirects.php` nadal daje 301 na hub modelu
  (a nie 404) dla wygaszonych — na 29.07 działało.

---

## Czego NIE robić

- **Nie czyścić filtrów dongchedi i nie ustawiać `enabled=false`, żeby zatrzymać import.**
  `isAllowedByConfig()` zaczyna od `if (empty($source_config) || empty($source_config['enabled'])) return true;`
  — to znaczy „nie filtruj", czyli **wpuść cały strumień**. Wariant bez kodu to filtr *niemożliwy*
  (`price_from` = 999999999).
- Nie kasować ofert trwale — `markRemoved()` → `draft` daje 301 na hub, twarde usunięcie daje 410.
- Nie ruszać ochrony: aktywne zamówienie / rezerwacja / wpis ręczny. `markRemoved()` broni tylko
  `isReserved()`; `listingsWithActiveOrders()` działa dopiero przy trwałym kasowaniu z kosza
  (incydent 20.07). Cron gaszenia liczy wszystkie trzy warstwy jawnie — nie regresuj tego.

---

## Kontekst, którego nie widać z kodu

- Za auto-api płaci **Auranet / Jan Schenk**, nie Ruslan. Korespondencja (`access@auto-api.com`,
  Telegram `@autodatabase`) idzie w imieniu Auranet.
- Dostawca sam kieruje na che168 od maja; crawler dongchedi to ich **nawracający** problem.
- Nienaprawiona od miesiąca regresja: nowe oferty dongchedi wchodzą z **43 polami zamiast ~342**.
  To materiał reklamacyjny, jeśli wariant C i pełna faktura.
- Memory: `project_t222_migracja_dongchedi_che168_2026_07_30`, `reference_autoapi_billing_owner`,
  `reference_autoapi_correspondence_history`.
