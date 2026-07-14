# T-207 — Skonsolidowany dashboard marketingowy (KPI, koszt leada, ROAS)

> Status: gotowy do odpalenia · Rozmiar: M
> Godziny realnie: **22–28 h** (Janek ~3 h, AI ~19–25 h) · Rynkowo: 55–70 h
> Podniesione z 20–24 h: konektory są gotowe, ale **historia metryk nie istnieje** — magazyn danych trzeba zbudować od zera.

## Po co

Dziś odpowiedź na pytanie „ile kosztuje nas jeden klient" wymaga odpalenia kilku skryptów i ręcznego zestawienia wyników. Każdy raport liczony jest **ad hoc** i nigdzie nie zostaje. Nie da się pokazać trendu, bo nie ma szeregu czasowego.

## Stan faktyczny

### ✅ Konektory — gotowe i sprawdzone w boju
| Źródło | Czym |
|---|---|
| GA4 (property 534017542) | `tmp/ga4_query.py` (zdarzenia, kanały, źródła), + 6 skryptów specjalistycznych |
| Google Ads (9506068500) | `scripts/gads_client.py` — **generyczny runner zapytań**, fundament wszystkiego |
| Search Console | `tmp/gsc-audit-*.py`, `gsc-hub.py` (reużywalny) |
| DataForSEO | `tmp/dfs-rank-tracker.py` |

To jest realna oszczędność — warstwa uwierzytelniania i zapytań działa.

### ❌ Historia metryk — NIE ISTNIEJE
- Katalog `data/` w repo: **pusty** (sam `.gitkeep`).
- Zero cronów raportowych. Zero snapshotów.
- Jedyny szereg czasowy (`tmp/rank-tracker-history.jsonl`) jest **martwy od 19.05** — cron, który miał go zasilać, **nigdy nie istniał** (był tylko sugestią w komentarzu skryptu).
- Wszystkie raporty lądują jako datowane pliki w `tmp/` — to artefakty sesji, nie dane.

**Czyli: potrafimy zapytać o dowolną metrykę dziś, ale nie wiemy, jaka była miesiąc temu.**

### ⚠️ Czego dashboard NIE pokaże (i trzeba to powiedzieć wprost)

**Kosztu leada w pełnym sensie** — bo **nie łączymy zamówienia ze źródłem ruchu**. W kodzie nie ma `gclid`, `utm`, ani żadnego zapisu źródła przy zamówieniu (grep: zero trafień).

Da się pokazać: koszt / konwersję z Google Ads, koszt / rozpoczęte zamówienie (agregat), ruch i konwersje wg kanału z GA4.
**Nie da się:** „to konkretne zamówienie przyszło z kampanii DSA".

Domknięcie tej pętli to osobna praca (~15–20 h, dotyka strefy kruchej zamówień) — **wymieniona w T-206 jako „import konwersji offline"**. Bez niej dashboard jest zestawieniem kanałów, nie atrybucją.

⚠️ Dodatkowo: **pomiar zaniża konwersje ~4×** (GA4 widzi 11 rozpoczętych zamówień, w bazie 45 — prawdopodobnie zgody na cookies). Dashboard oparty na GA4 odziedziczy ten błąd. **Diagnostyka luki (z T-206) powinna pójść przed dashboardem** — inaczej zbudujemy ładny wykres na złych liczbach.

## Plan

1. **Magazyn danych** — prosty i trwały: tabela w bazie albo pliki JSONL w `data/` (append-only, wersjonowane). Bez ciężkich narzędzi.
2. **Kolektor (cron dzienny)** — jeden skrypt zbierający dobowe metryki: Ads (koszt, kliknięcia, konwersje per kampania), GA4 (sesje, konwersje per kanał), GSC (wyświetlenia, kliknięcia, średnia pozycja), plus liczby z systemu (nowe zamówienia, statusy).
   ⚠️ **Backup crontaba przed edycją** — 12.07 był incydent nadpisania.
3. **Metryki systemowe z bazy** — nowe zamówienia/dobę, rozkład statusów, **wskaźnik anulowań** (dziś 24% — to jest KPI, którego nikt nie mierzy).
4. **Prezentacja** — statyczny HTML generowany cyklicznie (jak kosztorys: `build.py --deploy` na zahaszowany URL). Wykresy trendów, tabela kampanii, lejek. Bez logowania, bez frameworków.
5. **Backfill historii** — ile się da wstecz: Ads i GSC pozwalają pobrać dane historyczne (GA4 też). Dzięki temu dashboard od pierwszego dnia pokazuje trend, a nie jeden punkt.

## Testy

**Automatyczne**
- Kolektor uruchomiony 2× tego samego dnia → brak duplikatów w magazynie (idempotencja).
- Brak danych z jednego źródła (np. Ads API timeout) → pozostałe się zapisują, dashboard pokazuje lukę zamiast się wywalać.

**Półautomatyczne**
- Zgodność: liczby w dashboardzie vs bezpośrednie zapytanie do Ads/GA4 za ten sam dzień.
- Backfill: czy dane historyczne zgadzają się z tym, co pokazują panele Google.

## Definicja zrobionego

- Cron dzienny zbiera metryki z Ads, GA4, GSC i z bazy systemu.
- Historia rośnie i przeżywa restart (magazyn trwały, nie `tmp/`).
- Dashboard pod zahaszowanym URL-em pokazuje trendy, nie migawkę.
- Wskaźnik anulowań zamówień jest mierzony.
- Jasno oznaczone, czego dashboard **nie** pokazuje (atrybucja per zamówienie).
