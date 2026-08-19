# Mapa kampanii Google Ads — Prima-Auto

> **Po co ten plik:** żeby recheck konta nie zaczynał się od przekopywania wszystkich kampanii.
> Tu siedzi trwałe: rola każdej kampanii, jej historia i ustalenia. Świeże liczby dowozi
> `python3 scripts/ads-recheck.py` (dodaj `--md`, żeby wkleić tabelę niżej).
>
> **Konto:** `9506068500` (direct access, **nie** pod MCC AURANET — `login-customer-id` = to samo ID)
> **GA4:** property `534017542` · **Ostatni recheck:** 2026-08-19

---

## 1. Tabela kampanii — kto czym jest

| kampania | ID | typ | rola | historia / ustalenia |
|---|---|---|---|---|
| **[Brand] Prima-Auto** | 23779860635 | Search | obrona marki, najtańsze leady | od 22.04, oryginalna trójka kampanii |
| **[Topic] Import z Chin** | 23779860638 | Search | intencje generyczne („import aut z Chin") | od 22.04, oryginalna trójka |
| **[DSA] Import modele z Chin** | 23896725555 | Search DSA | page-feed, długi ogon modeli | rework 12.07 (`dsa-rework-2026-07-12.md`); T-200 rekomendował PAUZĘ 09.07 przy CPA 204 zł; 16.07 feed przestawiony z hubów na najtańszą ofertę per model (`docs/decyzje/2026-07-16-dsa-feed-na-oferty.md`), kampania została włączona. Feed odświeża cron co 3 dni (`scripts/dsa-offer-feed-refresh.py`, lepki — podmiana tylko gdy sztuka zeszła z publish) |
| **[RMKT] Dynamic Remarketing — Model-huby** | 23897599362 | Display | powrót niedoszłych, feed model-hubów | recon + optymalizacja 12.07 (`rmkt-optymalizacja-2026-07-12.md`), konwersje ×2 po zmianach; feed odświeżany tygodniowo (`scripts/refresh-rmkt-feed.sh`) |
| **[DG] Demand Gen — auta z Chin (YouTube)** | 24069066886 | Demand Gen | zasięg wizualny na YouTube | z T-200 „visual-first" (09.07) — teza: auta kupuje się oczami, 3 000 ogłoszeń ze zdjęciami jako paliwo |
| **[VID] Placementy — widzowie konkurencji** | 24060052062 | Video | placementy pod kanałami konkurencji | z T-200; konwersje to subskrypcje i follow-on views YouTube, **nie leady** |
| **[SKAG-1] Na placu** | 23803851563 | Search | auta fizycznie na placu | PAUSED. Rework 11.07 (2 RSA/grupę, zdjęcia grupowe); recheck 16.07 potwierdził, że **zdjęcia grupowe działają w Search** (`docs/sesje/2026-07-16-rechecki-t195-skag1.md`); T-200 rekomendował pauzę przy 0 konw. / 612 zł |
| **[SKAG-2] W drodze** | 23803851566 | Search | auta w drodze do Polski | rework 11.07 wzorcem SKAG-1 (`skag2-rework-plan-2026-07-11.md`, 60 ops); 18 grup modelowych |
| **[SKAG-3] Popularne sprowadzenie** | 23803851569 | Search | modele „pod sprowadzenie" | PAUSED, zero wydatku |
| **[SKAG] Marki-Modele** | 23779860641 | Search | pierwotny SKAG z 22.04 | PAUSED, zastąpiony przez SKAG-1/2/3 |

**Poza kampaniami:** `Campaign #1` (23779355912) — default po założeniu konta, status `REMOVED` (zweryfikowane 19.08).

## 2. Stan na 2026-08-19

| kampania | stan | budżet/dz | 7d koszt | 7d konw | 30d koszt | 30d kliki | 30d konw | CPA 30d | GA4 sesje | GA4 phone+wa |
|---|---|---|---|---|---|---|---|---|---|---|
| [Brand] Prima-Auto | ENABLED | 10 zł | 69 zł | 11.0 | 348 zł | 707 | 30.0 | 12 zł | 442 | 26 |
| [DG] Demand Gen — auta z Chin (YouTube) | ENABLED | 20 zł | 140 zł | 12.0 | 524 zł | 1909 | 16.0 | 33 zł | 423 | 9 |
| [DSA] Import modele z Chin | ENABLED | 15 zł | 110 zł | 0.0 | 633 zł | 1006 | 3.0 | 211 zł | 331 | 0 |
| [RMKT] Dynamic Remarketing — Model-huby | ENABLED | 17 zł | 115 zł | 0.2 | 550 zł | 1691 | 17.2 | 32 zł | 1463 | 12 |
| [SKAG-1] Na placu | PAUSED | 20 zł | 0 zł | 0.0 | 201 zł | 101 | 0.0 | — zł | 37 | 0 |
| [SKAG-2] W drodze | ENABLED | 25 zł | 187 zł | 0.0 | 749 zł | 404 | 0.0 | — zł | 130 | 0 |
| [SKAG-3] Popularne sprowadzenie | PAUSED | 25 zł | 0 zł | 0.0 | 0 zł | 0 | 0.0 | — zł | 0 | 0 |
| [SKAG] Marki-Modele | PAUSED | 50 zł | 0 zł | 0.0 | 0 zł | 0 | 0.0 | — zł | 0 | 0 |
| [Topic] Import z Chin | ENABLED | 35 zł | 214 zł | 3.0 | 970 zł | 494 | 17.0 | 57 zł | 224 | 13 |
| [VID] Placementy — widzowie konkurencji | ENABLED | 15 zł | 105 zł | 0.0 | 353 zł | 20 | 7.0 | 50 zł | 0 | 0 |
| **RAZEM** | | | 940 zł | 26.2 | 4327 zł | | 90.2 | 48 zł | | 60 |

Miesięczne tempo: **~4 330 zł/30 dni** (~4 030 zł/mc wg ostatnich 7 dni). Suma budżetów dziennych kampanii ENABLED = 137 zł/dz.

### Co mówi porównanie 7 dni do 30 dni

- **[SKAG-2] W drodze — pali najwięcej bez efektu:** 749 zł / 30 dni i 187 zł / 7 dni przy **zerze konwersji w obu oknach**, mimo reworku z 11.07. Największy pojedynczy wydatek bez zwrotu na koncie.
- **[DSA] — CPA 211 zł na 30 dni i 0 konwersji w ostatnich 7 dniach.** T-200 rekomendował pauzę już 09.07 przy CPA 204 zł; przestawienie feedu na oferty (16.07) tego nie odwróciło.
- **[RMKT] osłabł:** 30 dni daje CPA 32 zł, ale ostatnie 7 dni to 115 zł przy 0,2 konwersji. Do obserwacji — jeśli utrzyma się drugi tydzień, to nie szum.
- **[DG] przyspiesza:** CPA spadł z 33 zł (30 dni) do 12 zł (7 dni).
- **[Brand] najtańszy niezmiennie:** CPA 12 zł / 30 dni, 6 zł / 7 dni. Skala ograniczona budżetem 10 zł/dz.
- **[Topic] drożeje:** 214 zł w 7 dni przy 93 klikach = CPC 2,30 zł wobec 1,96 zł w oknie 30-dniowym.

## 3. Rozjazd Ads ↔ GA4 — czytaj, zanim zaraportujesz liczbę leadów

**Ads pokazuje 90,2 konwersji z płatnych za 30 dni. GA4 widzi w tych samych kampaniach 60 zdarzeń `click_phone` + `click_whatsapp`. Ads liczy o 50% więcej.**

| kampania | Ads konw. | GA4 phone+wa | Ads klików | GA4 sesji |
|---|---|---|---|---|
| [Brand] | 30,0 | 26 | 707 | 442 |
| [Topic] | 17,0 | 13 | 494 | 224 |
| [RMKT] | 17,2 | 12 | 1 691 | 1 463 |
| [DG] | 16,0 | 9 | 1 909 | 423 |
| [DSA] | 3,0 | **0** | 1 006 | 331 |
| [SKAG-1/2] | 0 | 0 | 505 | 167 |
| [VID] | 7,0 | 0 (konwersje to YouTube) | 20 | 0 |

**Skąd różnica — trzy przyczyny, wszystkie strukturalne, nie do „naprawienia":**

1. **Moment zapisu.** Ads przypisuje konwersję do dnia **kliknięcia** (okno do 30 dni wstecz), GA4 do dnia **zdarzenia**. Przy ruchomym oknie 30-dniowym brzegi się nie pokrywają.
2. **Model atrybucji.** Ads: data-driven, cross-device, z modelowaniem przy braku zgody. GA4 w tym raporcie: session-scope — zdarzenie liczy się kampanii **tej sesji**, więc lead, który wrócił z organica, ląduje w organiku (widać to w skrajnym przypadku DSA: 3 konwersje w Ads, 0 zdarzeń w GA4).
3. **Co jest liczone.** W Ads aktywne są tylko dwie akcje wiodące — `click_phone` (43) i `click_whatsapp` (40,2). `generate_lead` ma w Ads **0**, mimo że GA4 rejestruje 8 zdarzeń w 30 dni (z płatnych tylko 1, z Brandu). `purchase` w Ads 0, w GA4 3 — to testy PayU z 10.08, nie sprzedaż.

**Praktyka:**
- **Do decyzji budżetowych używaj Ads** — ranking kampianii jest w obu źródłach ten sam (Brand > Topic ≈ RMKT > DG >> DSA/SKAG), a Ads ma pełne koszty.
- **Do raportu dla Ruslana nie podawaj liczby leadów z Ads** jako faktycznej — realny kontakt telefoniczny/WhatsApp jest bliżej liczby GA4. Bezpieczna formuła: „X kontaktów zmierzonych w analityce, Y konwersji raportowanych przez Google Ads (z modelowaniem)".
- Organic wciąż dowozi najwięcej: 8 813 sesji i 98 zdarzeń kontaktowych w 30 dni — **więcej niż wszystkie kampanie płatne razem** (60).

## 4. Jak odświeżyć te liczby

```bash
python3 scripts/ads-recheck.py          # tabela na stdout + linia rozjazdu Ads/GA4
python3 scripts/ads-recheck.py --md     # ta sama tabela w Markdown, do wklejenia w sekcję 2
python3 scripts/ads-recheck.py --json tmp/ads-dump.json   # surowe dane
```

Skrypt jest read-only. Zmiany na koncie robi się osobnymi skryptami mutującymi (wzorzec: `scripts/gads_dsa_rewrite_desc_2026_07_16.py`).

**Gotchy API (kosztowały czas 19.08):**
- Wersję API bierz z `~/secrets/google/ads-config.json` (pole `api_version`, dziś `v25`). **Hardkod `v21` zwraca 404** — tak było w `scripts/gads_client.py` do 19.08 i w kilku skryptach jednorazowych w `tmp/`.
- **`campaign.start_date` nie istnieje w v25** — całe zapytanie leci na 400 INVALID_ARGUMENT. Daty startu kampanii trzymaj tutaj, w mapie.
- `login-customer-id` = `9506068500`, nie MCC. Konto jest direct-access.
- Klient GA4 (`scripts/ga4_query.py`) odtworzony 19.08 — poprzednia kopia w `tmp/` przepadła w czystce 14.07. **Nie przenoś go z powrotem do `tmp/`.**

## 5. Otwarte decyzje (nie wykonane — czekają na Janka)

1. **SKAG-2 „W drodze"** — pauza czy kolejny rework? 749 zł/30 dni przy zerze konwersji, po już wykonanym reworku z 11.07.
2. **DSA** — utrzymać po zmianie feedu czy zapauzować zgodnie z rekomendacją T-200? Zwolniłoby ~15 zł/dz.
3. **VID** — 353 zł/30 dni za 20 kliknięć; konwersje to subskrypcje YouTube. Zostawiamy jako budowanie kanału czy przenosimy budżet?
4. **Gdzie przenieść uwolnione budżety** — Brand (CPA 12 zł, ale limit 10 zł/dz dławi skalę) i DG (CPA 12 zł w ostatnim tygodniu) są dziś najtańsze.
5. **`generate_lead` w Ads ma 0** przy 8 zdarzeniach w GA4 — sprawdzić import akcji z GA4, jeśli formularz ma być mierzony jako konwersja.
