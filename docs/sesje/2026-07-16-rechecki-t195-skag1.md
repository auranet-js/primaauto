# Rechecki 2026-07-16 — T-195 (cron llms) + SKAG-1 (zdjęcia grupowe, plac)

> Sesja: odczyt/weryfikacja, **zero mutacji** (decyzja Janka: „nic nie ruszamy").
> Zaległe rechecki z kalendarza „Auranet Claude": T-195 (termin 13.07) + SKAG-1 (termin 11.07).
> Prompty: `tmp/PROMPT-t195-llms-cron-2026-07-09.md`, `docs/przypomnienia/2026-07-11-skag1-recheck.md`.

---

## T-195 — cron llms: ZIELONY, task domknięty

Cron `30 5 * * *` odpalał **4 dni z rzędu (13–16.07)**, bez błędów.

| Kryterium (z promptu) | Wynik |
|---|---|
| (a) mtime = dziś ~05:30 | ✅ `llms.txt` (9514 B) i `llms-full.txt` (91272 B) — `2026-07-16 05:30` |
| (b) `~/.claude/llms-regen.log` bez błędów | ✅ same `OK:`, zero stderr; 2002 B, ostatni wpis 05:30 |
| (c) liczba ofert ≈ publish count | ✅ plik: **3058**; SQL publish: **3058** (zgodność co do sztuki) |
| (d) sanity-gate nie blokuje niepotrzebnie | ✅ nie odpalił się ani razu (spadki dzienne 1–3 szt., próg = <50%) |

- Treść: „Stan na: 2026-07-16", 56 marek / 302 modele, struktura sekcji nienaruszona.
- Licznik w logu rośnie zgodnie z rzeczywistością: 3053 → 3056 → 3057 → 3058 (rotacja kasuje, feed dongchedi
  wciąż zamrożony — memory `project_dongchedi_feed_frozen_2026_07_07`).
- Drift −35% z 09.07 (4673 deklarowane vs 3046 realnych) **zlikwidowany trwale**.
- Sanity-gate wdrożony w obu generatorach: `scripts/build-llms.php:192`, `scripts/build-llms-full.php:150`.

**Event kalendarza oznaczony `✅`.** TODO wiszący od 2026-06-06 („cron do dodania ręcznie") jest realnie zamknięty.

---

## SKAG-1 — główna niepewność ROZSTRZYGNIĘTA

Kampania 23803851563 (search), konto 9506068500. Pytanie z 10.07: **czy Google w kampaniach search honoruje
obrazy podpięte na poziomie GRUPY** (edytor reklamy zarządza obrazami na poziomie kampanii, więc grupowe
podpięcia były tam niewidoczne, a assety wisiały `PENDING`).

### Odpowiedź: TAK, działa

- **20 podpięć** (10 grup × 2 kadry) — wszystkie `primary_status = ELIGIBLE` (przeszły review z `PENDING`).
  Uwaga: prompt mówił „14 zdjęć" — realnie w koncie jest 20 podpięć w 10 grupach.
- **7 dni: 354 wyświetlenia, 48 kliknięć** na assetach grupowych → Google je serwuje.
- **Wniosek: NIE wracamy** do wspólnych zdjęć na poziomie kampanii (wariant (a) z promptu odpada).
  Każda grupa pokazuje własny model — cel z 10.07 osiągnięty.

### Niespodzianka: format 1:1 wygrywa, 1.91:1 martwy

| Kadr | Wyświetlenia (7 dni) |
|---|---|
| `[1:1]` | 353 |
| `[1.91:1]` | 1 (jedyny: Denza N9) |

Google w search praktycznie nie serwuje 1.91:1. Przy kolejnych kadrach → priorytet 1:1.

### Wyniki kampanii (7 dni) — pierwsza konwersja

| Grupa | Impr | Klik | Konw | Koszt |
|---|---|---|---|---|
| Xiaomi YU7 — od ręki | 234 | 12 | 0 | 21,75 zł |
| Denza Z9 GT — od ręki | 81 | 7 | 0 | 11,98 zł |
| **Leopard 5 (Denza B5) — od ręki** | 63 | 23 | **1,0** | 40,81 zł |
| Denza N9 — od ręki | 35 | 9 | 0 | 15,48 zł |
| Geely Monjaro — od ręki | 34 | 15 | 0 | 24,51 zł |
| BYD Tang — od ręki | 33 | 11 | 0 | 18,56 zł |
| Mazda EZ-6 — od ręki | 19 | 3 | 0 | 5,52 zł |
| Sealion 8 / Tang — od ręki | 8 | 5 | 0 | 8,46 zł |
| Leopard 7 — od ręki | 7 | 1 | 0 | 0,99 zł |
| Denza N8L — od ręki (PAUSED) | 1 | 0 | 0 | 0,00 zł |
| **Jetour T2 — od ręki** | **0** | 0 | 0 | 0,00 zł |
| Na placu (PAUSED) | 0 | 0 | 0 | 0,00 zł |
| **RAZEM** | **515** | **86** | **1,0** | **148,06 zł** |

**0-konwersji SKAG-1 drgnęło** — 1 konwersja na Leopard 5 (grupa ma też najlepszy CTR 36,5%).

### RSA — warianty NIEROZSTRZYGNIĘTE (za mało danych)

22 RSA `ENABLED`, wszystkie `APPROVED`. Ad Strength: **16× POOR, 6× AVERAGE**.

Wariant A (z ceną) vs B (bez ceny) po 6 dniach:

| Wariant | Impr | Klik | CTR |
|---|---|---|---|
| B (bez ceny) | 259 | 37 | 14,29% |
| A (z ceną) | 150 | 21 | 14,00% |

Różnica 0,29 pp przy 409 wyświetleniach = **szum, nie sygnał**. Konwersji 0 w obu → brak podstawy do decyzji
(memory `feedback_no_decisions_without_data`). Wracamy za 1–2 tygodnie.

---

## Stan placu (14 aut `on_lot`) vs 10 grup

Źródło prawdy: `_asiaauto_reservation_status = on_lot` (NIE `stm_car_location` — memory
`reference_on_lot_authoritative_not_car_location`). **Wszystkie 14 są `publish`** → martwych grup do pauzy BRAK
(Denza N8L już `PAUSED` od 10.07).

**Auta na placu BEZ grupy w SKAG-1 (4):**

| ID | Auto | Uwaga |
|---|---|---|
| 355801 | Dongfeng Nissan Rich 7 2.3T automat | kandydat już z promptu 10.07 |
| 362371 | AITO M9 2025 EREV Ultra 6-os. | kandydat już z promptu 10.07 |
| 387799 | Hongqi H9 2024 2.0T Style Edition | **NOWE po 10.07** |
| 387815 | Hongqi H9 2024 2.0T Style Edition | **NOWE po 10.07** (drugi egz.) |

Leopard 7 387470 (drugi egzemplarz) — grupa „Leopard 7" istnieje i pokrywa oba (zdjęcie z 317106).

---

## Otwarte — do decyzji Janka (NIC nie ruszane 16.07)

1. **4 auta na placu bez grupy** (wyżej) — dodać grupy? Priorytet: Hongqi H9 ×2 (dwa egz. = realny stok).
2. **Jetour T2: 0 wyświetleń** przez 7 dni mimo grupy `ENABLED` i 2× RSA `APPROVED` — coś dławi
   (hipotezy: stawka pod floorem — patrz `project_session_2026_06_03_dsa_diag`, albo KW bez wolumenu).
   Wymaga diagnozy, nie mutacji w ciemno.
3. **Ad Strength 16× POOR** — osobna robota nad copy RSA.
4. **Warianty A/B** — pomiar za 1–2 tyg. (nie było rechecku w kalendarzu; założyć przy decyzji o wznowieniu).

## Narzędzia / artefakty

- Klient API: `tmp/gads_client.py` (gotcha: pole z `WHERE` musi być też w `SELECT` — `campaign.id` inaczej
  `EXPECTED_REFERENCED_FIELD_IN_SELECT_CLAUSE`; całe wyjście idzie na **stderr**, JSON zaczyna się od linii `[`).
- Dump „before" (rollback z 10.07): `~/backups/primaauto/2026-07-10-skag1/before.json`
- Skrypt mutacji (NIE uruchamiany): `tmp/gads_skag1_rework_2026_07_10.py`
