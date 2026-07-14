# T-186 — Che168 jako pełne, automatyczne drugie źródło

> Status: **GATED** — czeka na odpowiedź auto-api (mail wysłany 2026-07-14) · Rozmiar: XL
> Godziny realnie: **85–100 h** (Janek ~10–12 h, AI ~75–88 h) · Rynkowo: 240–280 h
> Estymata **potwierdzona po zwiadzie** — nie jest przeszacowana.

## ⚠️ Kontekst decyzyjny (2026-07-14)

- **Dostęp dev do Che168 wygasa 15.07.2026** (opłacony przez auto-api „through July 15").
- **Dongchedi praktycznie umarł** — od ~01.07 ok. **2 nowe oferty/dobę** wobec ~680 przy zdrowym feedzie. Trzy awarie w czerwcu.
- Mail do auto-api wysłany 14.07: czy utrzymują Dongchedi, czy mamy przechodzić w całości na Che168, jakie warunki dalszego dostępu.
- **Ta odpowiedź zmienia priorytet T-186 z „miło mieć" na „być albo nie być".** Jeśli Dongchedi jest martwy, Che168 nie jest dywersyfikacją — jest jedynym źródłem.

## Co JEST zrobione (i to dużo)

| Element | Stan |
|---|---|
| Adapter normalizujący dialekt Che168 | ✅ `class-asiaauto-che168-adapter.php` (255 l.) |
| Mapowanie marek/modeli CN→EU | ✅ `canonicalKeyForSource()` + `resolveChe168()` + 5 plików `data/che168-*.php`; **35/41 modeli** |
| Tłumaczenia parametrów i wyposażenia | ✅ `che168-param-map.php` (84), `che168-option-map.php` (37) |
| Ręczny import z podglądem (dry-run) | ✅ `class-asiaauto-admin-che168-import.php`, menu za capability |
| Log kalibracyjny | ✅ `class-asiaauto-che168-log.php` |
| Zdjęcia | ✅ **działają bez zmian** — permanentne URL-e (lepsze niż Dongchedi z `x-expires`) |
| Ceny | ✅ `AsiaAuto_Price` jest source-agnostic |
| Per-source lock / kursor / historia / API / CLI | ✅ `AsiaAuto_Sync::run($source)` już sparametryzowane |
| Spec architektury | ✅ `docs/superpowers/specs/2026-06-01-che168-second-source-design.md` (448 l.) |

**22 commity, praca 01–19.06.** Ruslan wie, że ręczny import działa — narracja „fundament stoi, płacisz za automat" jest uczciwa.

## Czego BRAKUJE (to jest te 85–100 h)

1. **🔴 Adapter nie jest wpięty w ścieżkę automatyczną.** `Che168_Adapter::normalize()` ma **jedno** wywołanie w całym kodzie produkcyjnym — w ręcznym imporcie. Automat (`Sync::run()` → `getOffer()` → `importListing()`) omija adapter i wrzuciłby **surowy chiński dialekt** prosto do bazy (orphany, CJK w tytułach, brak specyfikacji). → **Hook adaptera per-source w Sync/Importer. Strefa krucha.**
2. **🔴 Cron ma zaszyte `dongchedi` na sztywno** (`asiaauto-sync.php:139`). Brak `Source_Manager::syncAll()`.
3. **🔴 Brak konfiguracji `che168` w `asiaauto_import_config`** → `isAllowedByConfig()` odrzuciłby wszystko. Ręczny import to omija przez `force=true`.
4. **🔴 Dedup nie istnieje** — `findByInnerId()` jest per-source, więc to samo auto z obu giełd = 2 listingi. *Mitygacja: zmierzony overlap egzemplarzy = 0/30 → dedup może być lekki (VIN + guard z miastem), nie pełna heurystyka.*
5. **🔴 Brak panelu „Źródła"** w adminie (status, toggle, statystyki per źródło).

## Plan (fazy ze speca, sekcja 8)

**Faza 1 — Source Manager refactor, BEZ Che168** (~25–30 h)
Trzy klasy: `AsiaAuto_Source_Registry`, `AsiaAuto_Source_Manager`, `AsiaAuto_Dedup_Service`. Cron przechodzi przez Source Manager. **Zachowanie bit-for-bit identyczne jak dziś** + testy regresji. To jest faza, w której nic nie zmienia się dla użytkownika — i dlatego jest bezpieczna.

**Faza 2 — Che168 jako stub** (~15–20 h)
Wpięcie adaptera per-source w ścieżkę importu. `enabled=false`. CLI `wp asiaauto sync --source=che168 --dry-run` → pokazuje, co **by** zaimportował, nic nie zapisuje.

**Faza 3 — dedup + panel + go-live** (~35–45 h)
Lekki dedup, konfiguracja filtrów Che168, panel „Źródła", `enabled=true`, obserwacja.

**Faza 0 — decyzja segmentu (Ruslan, ~2 h)** ⚠️ **BLOKUJE FAZĘ 3**
Che168 to **premium używane z całych Chin** — inny rynek niż Dongchedi. Trzeba ustalić, co bierzemy: rocznik ≥?, przebieg ≤?, cena ≥?, które marki. Bez tego nie ma czego wpisać w konfigurację.

## Strefy kruche

- `class-asiaauto-importer.php` + `class-asiaauto-sync.php` — **strefa krucha**. Wpięcie adaptera **addytywnie** (normalizacja przy wejściu, downstream nietknięty — to jest już przyjęta architektura, ADR `2026-06-17-che168-normalize-at-entry.md`).
- Faza 1 dotyka crona produkcyjnego → **testy regresji obowiązkowe**, deploy poza godzinami importu.

## Testy

**Automatyczne**
- **Regresja Fazy 1:** ten sam zestaw 50 ofert Dongchedi zaimportowany przed i po refaktorze → **identyczne meta, taksonomie, slugi, ceny**. Bit-for-bit. Bez tego nie idziemy dalej.
- Adapter: 20 surowych rekordów Che168 → oczekiwany kształt znormalizowany (snapshoty z logu kalibracyjnego, już je mamy).
- Dedup: para tych samych aut z obu źródeł → 1 listing + `_asiaauto_duplicate_of`.

**Półautomatyczne**
- Faza 2: `--dry-run` na 200 ofertach Che168 → ile trafia w mapowanie, ile ląduje jako orphan. **Gate: <5% orphanów, inaczej wracamy do mapy marek.**
- Izolacja: symulacja padu jednego źródła → drugie sync'uje dalej.

**MCP**
- Panel „Źródła": toggle, statusy, ostatni sync.
- Losowe 10 ofert z Che168 na froncie: tytuł bez CJK, specyfikacja po polsku, zdjęcia, cena, przypisanie do właściwego huba.

## Definicja zrobionego

- Cron importuje z obu źródeł niezależnie; pad jednego nie dotyka drugiego.
- Oferty Che168 wchodzą znormalizowane (zero CJK, zero orphanów >5%).
- Duplikaty cross-source oznaczone, nie zdublowane.
- Panel „Źródła" pokazuje stan każdego źródła.
- Regresja Dongchedi: zero różnic.
