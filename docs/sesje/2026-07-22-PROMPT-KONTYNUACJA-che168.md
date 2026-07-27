# Prompt kontynuacyjny — T-186 Che168, kolejna partia danych

> Wklej treść poniżej na start nowego wątku w `~/projekty/primaauto`.
> Stan zamrożony: 2026-07-22, plugin v0.34.2. **NIE commitowane jako osobny task — to część większego T-186.**

---

Kontynuujemy **T-186 (drugie źródło ofert Che168)**. Przeczytaj najpierw:
- `docs/sesje/2026-07-22-che168-sync-wpiecie.md` — pełne domknięcie poprzedniej sesji
- `docs/roadmapa/T-186-che168-automat.md` — status i co zostało
- memory `reference_che168_api_obcina_wyposazenie` — kluczowe ustalenie o brakującym wyposażeniu

## Gdzie jesteśmy

Sync Che168 jest **wpięty w automat i przetestowany** (v0.34.2). W bazie **46 ofert w szkicach**
(`post_status=draft`, `_asiaauto_source=che168`, ID ≥ 389290), wszystkie w istniejących hubach.
**Nic nie opublikowane, oba źródła wyłączone** (`asiaauto_sync_enabled`=false, `_dongchedi`=0,
`_che168`=0), che168 na statusie `draft`. Kursory: che168=9942251, dongchedi=4521452 (nietknięty).
Kolejka domapowań `asiaauto_che168_unmapped`: 4 pozycje.

Wyłączniki i status per źródło: `AsiaAuto_Sync::isEnabledForSource($src)` / `statusForSource($src)`,
opcje `asiaauto_sync_enabled_{dongchedi,che168}` i `asiaauto_sync_status_{che168}`. Toggle w panelu Status.

## Blokada, na którą czekamy

**Auto-api nie przekazuje wyposażenia dla che168** — `/offer` daje 6-7 grup technicznych, brak pól
`extra_prep`/`equipment` (dongchedi ma je z 340-370 atrybutami). Dane istnieją publicznie u źródła
(`extra.configuration.specid` → katalog Autohome, 11-12 grup). Zapytanie wysłane do auto-api 22.07
(wątek „API inquiry Dongchedi + Che168", mail [147] w `claude@auratest.pl`). **Na dziś BRAK odpowiedzi.**

Zanim zaczniesz — sprawdź, czy nie odpisali: `python3 ~/bin/claude-mail-fetch.py new`. Jeśli tak,
przeczytaj i to zmienia priorytet dalszej pracy (patrz „Rozwidlenie decyzyjne").

## Co robimy w tej sesji (kolejna partia danych)

Cel: **przetestować sync i mapowania na świeżej, większej partii ofert Che168** — poprzednio było
46, teraz idziemy szerzej, żeby złapać modele/parametry/miasta, które nie pojawiły się w pierwszej próbie.

Bieg wykonujemy tak jak poprzednio (skrypt wzorcowy: `scripts/che168-przelicz-extra-prep.php` liczy,
`/tmp/claude-.../run-che168-x5.php` z poprzedniej sesji uruchamiał cykle). Zasady:
1. **Kursor** — świadomie ustawić na start doby albo kontynuować od 9942251 (nowsze zdarzenia).
2. Bieg na statusie `draft`, dongchedi zostaje wyłączony.
3. Po biegu sprawdzić: kolejka domapowań (`asiaauto_che168_unmapped`) — nowe orphany domapować
   wzorcem z poprzedniej sesji (`che168-model-map.php` / `brand-mapping-v6.1.php`, dla marek
   foldowanych indeks `serie_only` w `AsiaAuto_Mapping`).
4. Nowe nieznane parametry (`param_*`) — audyt jak w `scripts/che168-przelicz-extra-prep.php`,
   pobrać nazwy CN z API, zmapować do `che168-param-map.php`. **UWAGA na kolizje semantyczne**
   (poprzednio: id 88 = moc układu ≠ silnika, id 84/105 = ładowanie szybkie ≠ wolne). Zawsze
   weryfikować „zero kolizji, zero znikających kluczy" na próbce przed apply.
5. Nowe miasta bez tłumaczenia → `translations-cities.php` + backfill.

## Rozwidlenie decyzyjne (wyposażenie)

- **Jeśli auto-api ODDA konfigurację:** dziedziczenie z bliźniaków odpada. Domapować nowe grupy
  (fotele/bezpieczeństwo/multimedia) w `che168-param-map.php` tak jak 29 parametrów technicznych.
- **Jeśli ODMÓWI / zażąda dopłaty:** wdrożyć dziedziczenie z bliźniaczych ofert, **wariant konsensusu**
  (marka+seria+rocznik, dolewa tylko pola zgodne u wszystkich dawców). Zmierzone: wyposażenie
  14,5 → 67,7 pozycji/oferta. Silnik: rozszerzyć `scripts/merge-spec-from-twin.php` o tryb luźny+konsensus
  (dziś ma tylko exact). Stempluje `_asiaauto_spec_inherited_from/_at/_count` (audyt + rollback).
  Symulacja porównawcza: `scripts/che168-symulacja-karty-oferty.php`.

## Zostało do go-live (niezależnie od wyposażenia)

- **Dedup po VIN** — che168 ma VIN w 100%, dongchedi nie ma. Lekki guard przed importem
  (`_asiaauto_duplicate_of`). Zmierzony overlap egzemplarzy = 0/30, więc pełna heurystyka zbędna.
- **Panel „Źródła"** — dziś jest uproszczony toggle w Status; pełny panel (statystyki per źródło) opcjonalny.
- **Przegląd szkiców przez Janka** → publikacja albo poprawki.
- Włączenie: `asiaauto_sync_enabled_che168=1`, status `publish`, obserwacja dobowa.

## Decyzja czekająca na Ruslana (nie blokuje techniki)

Filtr 31 miast ucina **81% dobrej podaży che168** (388 z 477 w próbce). Największe wolumeny poza
listą: Szanghaj, Wuhan, Chongqing, Chengdu, Shijiazhuang. Lista 31 = zasięg fizycznych oględzin
(`docs/QUEUE.md`, Zadanie 6) → zmiana to decyzja operacyjna Ruslana, nie ustawienie w panelu.

## Reguły pracy (z tej sesji, sprawdzone)

- Adapter Che168 = jedyne miejsce dialektu; `importListing` i niżej **nietknięte** (ADR
  `2026-06-17-che168-normalize-at-entry`). Wszystko addytywnie.
- Kolejność w guardzie: **filtr konfigu → guard mapowania** (odwrotnie kolejka zapełnia się
  BMW/Mercedesami z całych Chin).
- Filtr wstępny na `/changes` przed `getOffer()` — inaczej bieg spala setki wywołań API.
- Mapowań parametrów NIGDY „na oko" — weryfikować kolizje na próbce (były 2 błędy semantyczne).
- Przy każdym biegu: `.bak` z datą przed nadpisaniem plików danych, `php -l` przed deployem.
- **Postęp prac dla Ruslana** (`docs/kosztorys/`) — NIE dopisywać teraz; T-186 trafi tam jednym
  wpisem po uruchomieniu całości (decyzja Janka 22.07 — task rozbudowany na dużo godzin).

## Godziny (do rozliczenia przy domknięciu T-186)

Sesja 22.07: ~7 h (wpięcie syncu + domapowanie parametrów + analiza wyposażenia + zapytanie do dostawcy).
Doliczyć do sumy T-186 przy finalnym wpisie w postępie prac.
