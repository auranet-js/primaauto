# T-162 — Sekcja blogowa / baza wiedzy (rankingi, porównania, poradniki)

> Status: **gotowy do odpalenia** (strategia gotowa, zero blokerów zewnętrznych) · Rozmiar: L + cykl
> **Silnik (jednorazowo): 35–45 h** (Janek ~6 h, AI ~30–40 h) · Rynkowo: 90–110 h
> **Produkcja (cyklicznie): 20–28 h/mc** → osobna pozycja w pracach regularnych
> Skala: **8–12 tekstów/mc — ofensywa** (decyzja Janka 2026-07-14)

## Dlaczego stara wycena (50–60 h) nie mogła się zgadzać

Bo mieszała dwie różne rzeczy: **zbudowanie bloga** i **pisanie na bloga**. Pierwsze to projekt, drugie to abonament. Rozdzielamy.

## Po co — twarde dane, nie przeczucie

**chinskisamochod.com to nie dealer — to wydawca.** Ma **501 fraz w top 30 i ~14 800 wejść/mc** z Google. I najważniejsze: **66,7% tego ruchu to treści NIEzakupowe** (mandaty, ładowarki, strefy czystego transportu, leasing). Czyli ich przewaga to **metoda**, nie nisza — i ta metoda jest do skopiowania.

Co dziś tracimy:
- `{model} import` → **my, pozycja 2,5, CTR 33%** ✅ (konkurent ma 0% na „import")
- `{model} cena` → pozycja 8,0, **CTR 0%** ❌
- `{model} cena w polsce` → pozycja 4,9, **CTR 0%** ❌
- `gdzie kupić` → **konkurent #2** ❌

Ich słabości do wykorzystania: FAQ **bez schemy**, brak meta description, **zero realnej oferty aut**. My mamy 3056 aut na stanie — możemy z każdego artykułu linkować do realnych ofert. Tego oni nie zrobią.

## Co JUŻ mamy (infrastruktura z hubów — do reużycia)

| Element | Stan | Koszt |
|---|---|---|
| Generator treści (Sonnet, direct API, fact-check + anti-spam lint) | ✅ `tmp/hub-wiki-generate.py` | **$0,07/tekst** |
| Generator obrazów hero (Gemini) | ✅ `~/scratch/gemini-hero-test/gen-hero.py` | **$0,035/szt** |
| REST API faktów z bazy (karmi generator prawdziwymi danymi) | ✅ `class-asiaauto-rest-hub.php` | — |
| FAQ + schema FAQPage | ✅ na hubach | — |
| llms.txt / llms-full.txt + cron | ✅ | — |
| RankMath Pro — **schema `BlogPosting` dla postów już skonfigurowana** | ✅ | — |

**Koszt AI za treść to grosze. Płacimy za research, fact-check, redakcję i wpięcie w strukturę — nie za generowanie.**

## Czego NIE ma — blog fizycznie nie istnieje

Sprawdzone na produkcji:
- **0 opublikowanych postów** (2 auto-drafty), **0 kategorii w użyciu**, strona bloga nieustawiona.
- Motyw `primaauto2026` **nie ma żadnych szablonów bloga**: brak `single.php`, `archive.php`, `category.php`, `home.php`, `search.php`. Post trafiłby na goły `index.php` (460 bajtów: tytuł + treść, bez nagłówka, bez powiązanych, bez CTA).
- Brak decyzji o prefiksie URL (permalinki `/%postname%/` → kolizja z `/informacje/`).

## Strategia (już ustalona — NIE wymyślamy od nowa)

**5 filarów** (z `tmp/brief-denza-z9-gt-blog-2026-05-30.md`):

- **A — Chińskie marki w Polsce** `[8 780/mc]` → lista marek, znaczki/logo, premium, elektryczne
- **B — Rankingi** → „Najlepszy chiński SUV 2026", „Najtańszy chiński EV" `[390]`, „Chiński 7-osobowy" (luka u konkurenta), rankingi EV
- **C — Przewodniki zakupowe** ⚠️ **UWAGA: koliduje z `/informacje/*`**, które ten intent już przejęły (rework 06-07). **Nie kopiujemy — rozbudowujemy istniejące strony info, a na blogu robimy treści szersze** („co się zmienia w cle w 2026", „homologacja krok po kroku")
- **D — Porównania „vs"** → „Jaecoo 8 vs Omoda 5" (PAA potwierdza popyt), każdy linkuje do hubów obu modeli
- **E — News / premiery** → świeżość, „kiedy w Polsce", nowe modele z Chin (twoje życzenie — one wychodzą co chwilę)

**Reguła antykanibalizacyjna (twarda, zamknięta decyzja D2):**
> `{model} cena / import / gdzie kupić` → **tylko hub**
> rankingi / przewodniki / „chińskie marki" → **tylko blog**
> opinie / test / vs → hub **albo** blog, **nigdy oba**
> **Jedna fraza = jeden URL.** Przed publikacją: audyt kanibalizacji w GSC.

**Priorytet modeli (decyzja D3):** popyt z **naszych zamówień × dostępność**, NIE wolumen zewnętrzny. Omoda 5 (154 tys./mc!) i Jaecoo 8 **wypadają — nie sprzedajemy ich**. Rdzeń: Leopard 3/5/7 + Denza Z9 GT/N9/N8L.

## Plan — Silnik (35–45 h, jednorazowo)

1. **Decyzja: natywne `post` vs CPT.** *Rekomendacja: natywne `post`* — RankMath ma już skonfigurowaną schemę BlogPosting i sugestie linków; CPT wymagałby konfiguracji od zera.
2. **Prefiks URL** — np. `/wiedza/` albo `/blog/`. Do rozstrzygnięcia (wpływa na strukturę i przyszłe przekierowania — **decyzja nieodwracalna tanio**).
3. **Kategorie = filary** (A–E), opisy kategorii jako strony pillar.
4. **Szablony motywu:** `single.php` (artykuł + spis treści + FAQ + **powiązane auta z naszej bazy** + CTA), `archive.php`/`category.php`, `home.php`, `search.php` + CSS.
5. **Powiązane oferty w artykule** — automatyczne wstawianie kart aut z bazy (reuse helpera karty z T-187). **To jest nasza przewaga nad konkurentem-wydawcą.**
6. **Schema:** BlogPosting + FAQPage (nie dublować FAQ z hubów — reguła dedup).
7. **Pipeline pod blog** — adaptacja `hub-wiki-generate.py`: prompt per typ treści (ranking / porównanie / poradnik / news), fact-check na danych z REST.
8. **Audyt kanibalizacji jako gate** — skrypt sprawdzający w GSC, czy hub już nie rankuje na frazę planowanego artykułu. **Bez zielonego światła nie publikujemy.**
9. **Pierwsze 3–5 tekstów** — kalibracja jakości i tonu.

## Plan — Produkcja (20–28 h/mc, prace regularne)

- 8–12 tekstów/mc: research fraz → audyt kanibalizacji → generowanie → **fact-check (obowiązkowy — piszemy o cenach i przepisach)** → redakcja → hero → publikacja → linkowanie wewnętrzne → zgłoszenie do indeksacji.
- Miesięczny pomiar w GSC: pozycje, CTR, kanibalizacja.
- **Pomiar TYLKO w GSC** (decyzja D6) — DataForSEO nas nie widzi (7 fraz vs 512 realnych wyświetleń).

## Testy

**Automatyczne**
- Schema BlogPosting waliduje się (Rich Results Test).
- Każdy artykuł ma: meta description, hero, ≥1 link do huba, ≥1 kartę auta z bazy.
- Sitemap zawiera posty; llms.txt je uwzględnia.

**Półautomatyczne**
- **Audyt kanibalizacji przed każdą publikacją** (GSC: czy hub rankuje na tę frazę).
- Fact-check treści: ceny, zasięgi, terminy — **przeciw naszej bazie**, nie przeciw modelowi.
- Po 30 dniach: pierwsze pozycje w GSC dla opublikowanych.

**MCP (Chrome)**
- Artykuł: desktop + mobile — spis treści, FAQ, karty aut, CTA.
- Archiwum kategorii, paginacja, wyszukiwarka bloga.

## Definicja zrobionego (silnik)

- Blog ma szablony, kategorie-filary, schemę, prefiks URL.
- Artykuł automatycznie pokazuje powiązane auta z naszej oferty.
- Pipeline generuje treść z fact-checkiem na naszych danych.
- Gate kanibalizacyjny działa i blokuje publikację kolidującą z hubem.
- 3–5 pierwszych tekstów opublikowanych i zindeksowanych.
