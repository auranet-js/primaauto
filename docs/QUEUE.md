# Kolejka zadań — Prima Auto

> Aktualizacja: 2026-04-17 (sesja 4: kolejka wyczyszczona, ZADANIE 6 dodane)

---

## GRUPA 9.5 — performance (backlog, gdy pojawi się potrzeba)

- [ ] Uruchomić profiler (`?aa_profile=1`) na panelu admina
- [ ] Zidentyfikować bottleneck (najpewniej 3× COUNT na postmeta w `renderPriceTab`)
- [ ] Transient cache z invalidate przy `ajaxBulkRecalc` i `saveConfig`

> Wdrożyć gdy Ruslan zgłosi wolne ładowanie admina. Na razie brak sygnału.

---

## ZADANIE 6 — Filtr miast + aktualizacja marek (NOWE)

> Status: **w planowaniu**. Realizacja w nowym wątku po zamknięciu aktualnej sesji.

### Kontekst biznesowy

Prima Auto ma ludzi na miejscu w południowych Chinach (Guangdong, Fujian, Guangxi, Hainan).
Mogą fizycznie sprawdzać i doglądać auta tylko w wybranych miastach. Import ogłoszeń powinien
być ograniczony do tych lokalizacji — żeby klient widział tylko auta, które faktycznie można
zweryfikować na miejscu.

Jednocześnie klient prześle zaktualizowaną listę marek — obecne orphaned termy (Li Auto, NIO,
Volvo) mogą wrócić lub zostać usunięte.

### Lista miast od klienta

| Region | Miasto (PL) | Miasto (ZH) |
|---|---|---|
| Guangdong | Guangzhou | 广州 |
| Guangdong | Shenzhen | 深圳 |
| Guangdong | Foshan | 佛山 |
| Guangdong | Dongguan | 东莞 |
| Fujian | Xiamen | 厦门 |
| Fujian | Fuzhou | 福州 |
| Guangxi | Beihai | 北海 |
| Guangxi | Nanning | 南宁 |
| Hainan | Haikou | 海口 |
| Hainan | Sanya | 三亚 |

> Uwaga: klient podał też regiony (Fujian, Guangxi, Hainan) — interpretowane jako prowincje,
> z których wybrano konkretne miasta. Filtr dotyczy miast, nie prowincji.

### Podzadania — Krok A: weryfikacja dostępności ogłoszeń

- [ ] Sprawdzić jak API (Dongchedi/Che168) koduje pole `city` — slugi, ID czy pełna nazwa
- [ ] Odpytać pierwsze ~50 stron API bez filtra, zebrać unikalne wartości `city`
- [ ] Zmapować wartości API → lista miast klienta
- [ ] Zliczyć dostępne ogłoszenia per miasto → tabela: miasto | liczba ofert | zasadność
- [ ] Przedstawić wyniki klientowi — potwierdzenie finalnej listy

### Podzadania — Krok B: filtr w panelu admina

- [ ] Nowa opcja w `asiaauto_price_config` (lub osobna): `city_filter_enabled` (bool, domyślnie OFF)
- [ ] Nowa opcja: `city_filter_cities` (array slugów/kodów miast z API)
- [ ] UI w panelu admina (sekcja "Import — filtr miast"):
  - toggle "Filtruj po miastach: TAK/NIE" (domyślnie NIE)
  - lista miast z checkboxami (wypełniona po weryfikacji z API)
- [ ] Zastosowanie filtra w `class-asiaauto-sync.php` / `class-asiaauto-api.php` przy buildowaniu query do API

### Podzadania — Krok C: aktualizacja marek

- [ ] Klient przesyła zaktualizowaną listę marek
- [ ] Weryfikacja orphaned termów (Li Auto, NIO, Volvo, Lynk & Co) — usunąć lub zostawić
- [ ] Ewentualne dodanie brakujących marek jako terminy taksonomii

### Podzadania — Krok D: re-import (po finalizacji filtrów)

- [ ] Filtr miast przetestowany i zatwierdzony przez klienta
- [ ] Backup aktualnej bazy ogłoszeń (`mysqldump` listings + postmeta)
- [ ] Decyzja: ręczny import jednorazowy czy cron automatyczny
- [ ] Wyczyszczenie aktualnych ogłoszeń (`listings` CPT)
- [ ] Import z filtrem miast i marek — monitoring pierwszych partii

### Zależności i uwagi

- Filtr miast ma być domyślnie **wyłączony** (toggle OFF) — obecne ogłoszenia nie znikają od razu
- Toggle OFF = sync działa jak dotychczas (bez filtra geograficznego)
- Toggle ON = sync pobiera tylko ogłoszenia z wybranych miast
- Krok D dopiero po: Krok A (weryfikacja) + Krok B (filtr gotowy) + Krok C (marki potwierdzone)

---

## Backlog (niski priorytet)

- [ ] Krok 4 manual editor — metabox extra_prep (18 zakładek)
- [ ] Email HTML templates (maile są plain text)
- [ ] Homepage + Contact CSS → pliki zewnętrzne (z inline)
- [ ] Archive/taxonomy pages dla marek (B5 — duże zadanie, osobny projekt)
- [ ] Orphaned terms (Li Auto, Volvo, NIO, Lynk &amp; Co — count=0, czekają na nową listę marek od klienta)

---

## Zrealizowane (archiwum)

- [x] Pending 0: bump wersji 0.29.0-wip → 0.30.7 (2026-04-16)
- [x] Pending 1: pipeline USD-centric — `calculateFromCifUsd()`, `BREAKDOWN_VERSION=2` (2026-04-16)
- [x] ZADANIE 2: załączniki PDF do umowy (renderAttachment1/2), token bezpieczeństwa, nr umowy w tytule przelewu (0.30.8, 2026-04-17)
- [x] ZADANIE 3: maile statusów, etykiety "depozyt zabezpieczający" (2026-04-16)
- [x] ZADANIE 4: smoke test E2E — flow zamówień, PDF, maile, statusy (2026-04-17)
- [x] ZADANIE 5: rework workflow zamówień — model agencyjny, nowe statusy, wizard, panel admina (0.30.7, 2026-04-16)
- [x] B2 SEO: meta/OG/title dla single i inventory, Schema.org, term meta opisów, 10 marek + 75 modeli, llms.txt (0.30.9, 2026-04-17)
- [x] Panel klienta `/klient/` — shortcode, logout, auto-redirect (2026-04-16)
