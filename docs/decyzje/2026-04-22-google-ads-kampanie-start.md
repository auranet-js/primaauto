# ADR 2026-04-22 — Start kampanii Google Ads

## Kontekst

Po wdrożeniu konwersji GA4 (v0.31.1, commit 72ca37f: `click_phone`, `click_whatsapp`,
`generate_lead`) ruszamy płatny ruch. Do czasu przyznania Google Ads developer_token
(wnioskowanie pending, 1–4 tyg) konfiguracja kampanii idzie przez CSV import w
Google Ads Editor — nie przez API.

Konto: `Prima-Auto Ruslan Prima` (ID `950-606-8500`). Stan wejściowy — istniejąca
`Campaign #1`: Search + Display Network + Search Partners, budżet 50 PLN/dobę,
Maximize clicks z capem 0,90 PLN, geotargeting tylko Sędziszów + Łańcut (40/34 km),
1 keyword `import aut z chin` exact, 1 RSA z ad strength Poor. Decyzja: pauza i
zbudowanie od zera.

## Decyzja

### Struktura trzech kampanii

| Kampania | Budżet/dobę | Manual CPC | Cel | Final URL |
|---|---|---|---|---|
| `[Brand] Prima-Auto` | 10 PLN | 0,50 PLN | tani ruch brandowy, obrona SERP | `/` |
| `[Topic] Import z Chin` | 30 PLN | 1,00 PLN | tematyczny head-term „import z Chin" | `/samochody/` |
| `[SKAG] Marki-Modele` | 60 PLN | 0,80 PLN | long-tail per marka+model (20 grup) | `/samochody/<marka>/<model>/` |

Łącznie 100 PLN/dobę. Harmonogram: Pon–Pt 09:00–17:00 (godziny pracy firmy).
Geo: cała Polska (ID `2616`). Język: `pl`. Sieć: tylko Google search
(Search Partners i Display Network **wyłączone** na starcie — czystszy sygnał,
włączymy po osiągnięciu istotnego wolumenu).

### Grupy w `[SKAG] Marki-Modele` (20 na start, próg ≥10 listings)

AITO Aito M9 · Xiaomi SU7 · AITO Aito M7 · AITO Aito M8 · Geely Xingyue L ·
BYD Tang DM · Geely Xingrui · Hongqi H5 · XPeng P7+ · AITO Aito M5 ·
Chery Arrizo 8 · Xiaomi YU7 · Avatr 06 · Denza D9 DM · Changan UNI-V ·
GAC Trumpchi M8 · BYD Song L EV · BYD Han DM · Avatr 12 · XPeng X9.

Wzorzec fraz per grupa (exact + phrase):
`[marka model]`, `[marka model import]`, `[marka model cena]`,
`[marka model polska]`, `[marka model opinie]`, `[marka model używany]`,
`"marka model"`, `"marka model cena"`.

Po 2 tygodniach danych: dobudowa kolejnych grup na podstawie search terms
report i zbudowanie z top wydajnych fraz list wykluczeń dla reszty.

### RSA

Każda grupa → 1 RSA z 15 nagłówkami (H1 i H2 pinned: brand/promise + location/import),
4 opisami. Walidacja długości: H ≤30 znaków, D ≤90, Path ≤15.

### Komponenty (account-level)

- **9 sitelinków**: Katalog aut z Chin · Samochody BYD · Samochody AITO ·
  Samochody Xiaomi · Samochody XPeng · Finansowanie · Gwarancja ·
  O nas — Prima-Auto · Kontakt (wszystkie z description line 1+2).
- **10 calloutów**: Odbiór w Rzeszowie · Dostawa cała Polska · Bezpośredni
  importer · Umowa agencyjna · Pełna obsługa celna · Dokumenty i VIN ·
  Rocznik 2024/2025 · Wycena w 24h · Bez ukrytych kosztów · Kontakt: 783 807 381.
- **Call extension 783 807 381** — już było na koncie, objmuje wszystkie
  kampanie automatycznie.
- **Structured snippets + Logo** — CSV Editor tego nie wspiera formatem
  159-kolumnowym, dodawane ręcznie w Editor (Tools → Shared library).

### Negatywy

Campaign-level, 3 kategorie:

- **Śmieci ogólne** (90 fraz × Negative Broad): informacyjne/non-commercial
  — `forum`, `wikipedia`, `tapeta`, `zabawka`, `rc`, `gra`, `film`, `praca`,
  `kurs`, `allegro`, `olx`, itd.
- **Śmieci moto** (74 fraz × Negative Broad): `części`, `warsztat`, `wrak`,
  `komis`, `leasing`, `wynajem`, `tuning`, `alibaba`, `aliexpress`,
  `hurt`, `dropshipping`, itd.
- **Brand-kolizje** (11 fraz × Negative Phrase, TYLKO `[Brand] Prima-Auto`):
  `primark`, `prime video`, `amazon prime`, `primavera`, `primator`,
  `primabar`, `primacom`, `primabiotic`, `prima sport`, `prima bank`,
  `auto świat`.

### Strategia bidu

Manual CPC na starcie (zgodnie z wytycznymi 0,50 / 1,00 / 0,80 PLN per kampania).
**Po przyznaniu developer_token** → import konwersji GA4 jako conversion actions
(`click_phone` + `click_whatsapp` + `generate_lead`, wartości do ustalenia
po 2 tyg. danych) i migracja na Maximize conversions lub Target CPA.
Do tego czasu: obserwujemy search terms, CTR, landing page experience,
co tydzień czyścimy negatywami i dobudowujemy exact z wydajnych fraz.

## Pliki źródłowe

W `tmp/` (artefakty do importu w Google Ads Editor — `Account → Import → From file`):

| Plik | Zawartość | Wiersze |
|---|---|---|
| `tmp/primaauto_ads_import.csv` | Pauza Campaign #1 + 3 kampanie + 23 grupy + 200 keywordów + 23 RSA | 253 |
| `tmp/negative_keywords.csv` | Negatywy campaign-level × 3 kampanie + brand-kolizje | 503 |
| `tmp/components_assets.csv` | 9 sitelinków + 10 calloutów account-level | 19 |
| `tmp/negative_keywords.txt` | Czytelna lista negatywów do ewentualnego shared set |  |
| `tmp/generate_ads_csv.py` | Generator głównego CSV |  |
| `tmp/generate_negative_keywords.py` | Generator negatywów |  |
| `tmp/generate_components.py` | Generator komponentów |  |

Wszystkie CSV: tab-separated, UTF-16 LE z BOM, CRLF, 159 kolumn
(format zgodny z eksportem Google Ads Editor).

### Kolejność importu w Editor

1. Get recent changes
2. `primaauto_ads_import.csv` — struktura
3. `components_assets.csv` — komponenty
4. `negative_keywords.csv` — negatywy
5. Ręcznie: structured snippets + logo
6. Post

## Konsekwencje

- Bieżący koszt: ~3000 PLN/miesiąc przy pełnym wykorzystaniu budżetu.
- Po pierwszym tygodniu potrzebna sesja czyszczenia search terms
  i uzupełniania negatywów.
- Gdy developer_token zostanie przyznany (ZADANIE 10 w QUEUE):
  import konwersji GA4, zmiana strategii bidu, raporty readonly
  (CPL, CPC per kampania, performance per keyword) — dopiero wtedy
  włącza się pełna pętla optymalizacji opisana w QUEUE.
- Structured snippets i logo — do dodania ręcznie przed Post.
- Regeneracja dowolnego artefaktu: `cd tmp && python3 generate_*.py`.
