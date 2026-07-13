# T-203 — Oferty: title wersyjne + sekcja wersji + linkowanie hub↔oferta (anty-kanibalizacja)

> Data: 2026-07-13 | Status: SPEC (czeka na akcept)
> Geneza: analiza konkurencyjna tntcars.pl (`https://auratest.pl/fe4f58fec53ctmp/primaauto-analiza-tntcars-2026-07-13.md`).
> Symulacja przed/po: `https://auratest.pl/fe4f58fec53ctmp/primaauto-t203-symulacja-przed-po-2026-07-13.html`

---

## 1. Problem

TNT Cars wygrywa frazy **broad po nazwie modelu/wersji** (największy wolumen: „xiaomi yu7" 6600/mc → oni #7, my #36; „zeekr 8x" 2900/mc → oni #2, my #17) **stronami ofert** z title = czysta nazwa wersji. Nasze oferty mają title generowany rotacją 10 szablonów (`inner_id % 10`), z których żaden nie eksponuje wyróżnika, a część jest szkodliwa:

- szablon 0: „**Używane** {base} z Chin" — psuje intent (auta 5–15 tys. km są de facto nowe) i CTR,
- szablon 9: „Zamów Online {base} z Chin w 2026" — spam-pattern,
- **rotacja nie gwarantuje unikalności**: dwie oferty tej samej wersji z `inner_id % 10` dającym ten sam indeks mają **identyczny title** (realny przykład: YU7 4WD Max #386592 i #386374 → obie szablon 5).

Jednocześnie: frazy „{model} cena" wygrywają nasze huby (yu7 cena #2, zeekr 9x cena #1) — **tego nie wolno naruszyć**.

### Stan faktyczny /oferta/ w GSC (90 dni, do 2026-07-10)

| Metryka | Huby /samochody/ | Oferty /oferta/ |
|---|---|---|
| Kliki | 5 600 | **405** (6,7% ruchu przy ~4 500 stronach) |
| Impresje | 143 300 | 15 023 |
| CTR | 3,91% | **2,70%** |
| Śr. pozycja | 6,6 | 10,4 |

- Najlepsza oferta = 11 klików/90 dni. 3 050 z 3 372 stron z impresjami ma **0 klików**.
- **Oferty już rankują na frazy wersyjne bez optymalizacji**: „aito m9 ultra" poz. 4,6, „geely preface" 6,7, „tank 700" 7,6 — Google chce je pokazywać, title nie konwertuje impresji.
- **Mikro-kanibalizacja już istnieje**: frazy z „cena" lądują na ofertach („geely preface cena" poz. 5,3, „xpeng mona m03 cena", „tank 700 cena") zamiast na hubach → zakaz „cena" w title ofert porządkuje istniejący problem, nie tworzy nowego.

## 2. Zasada anty-kanibalizacyjna (linia demarkacyjna)

| Warstwa | Frazy docelowe | Wzorzec title | Zakazy |
|---|---|---|---|
| **Hub** (`/samochody/{make}/{model}/`) | `{model}`, `{model} cena`, `{model} import`, `{model} dane techniczne` | „{Model} cena w Polsce 2026 — od X PLN" (bez zmian!) | — |
| **Oferta** (`/oferta/{slug}/`) | `{model} {rok} {wersja}`, `{wersja} cena`, long-tail egzemplarza | „{base} — import z Chin, {cena} PLN \| Prima-Auto" | słowo „**cena**", wzorzec „cena w Polsce", goły model bez wersji |

Treść modelowa (historia, porównania, FAQ, wiki) **wyłącznie na hubach** — bez zmian polityki. Oferta dostaje tylko dane egzemplarza (data-driven, zero copywritingu).

## 3. Zakres — 4 kroki

### KROK 0 — weryfikacja serwera ✅ WYKONANE 2026-07-13
Ustalenia:
- **Single JUŻ renderuje pełne dane z `extra_prep`**: `[asiaauto_tech_specs]` (84 wiersze / 6 sekcji dla próbki YU7) + `[asiaauto_equipment]` (checklist 8+ grup, ~107 KB HTML) — shortcody z `class-asiaauto-shortcodes.php` (od v0.10.2), wołane w `AsiaAuto_Single::render()`. Wcześniejsza diagnoza „TNT ma głębszy content na ofertach" była błędna co do danych — luka jest w **title**, nie w treści. Prywatna metoda `techSpecs()` w klasie Single (markup `aa-spec-table`) to martwy kod — realny render idzie przez shortcody (`aa-ts`/`aa-eq`).
- **KROK 2 (sekcja „Wyposażenie tej wersji") jest ZBĘDNY** — patrz wyżej; wykreślony z zakresu.
- Pokrycie `extra_prep` w seriach pilotażowych: 94–100% (YU7 52/55, SU7 63/66, M9 126/132, Preface 90/91, Arrizo 8 46/46, 9X 14/14, 8X 6/6, G700 5/5).
- **Gotcha slug**: `m9` w taksonomii serie łapie i AITO M9, i Galaxy M9 → **gate po `term_id`**, nie slugu.
- Baseline GSC (28 dni) zapisany: `docs/seo/T-203-baseline-gsc-2026-07-13.md` — pozycje hubów na frazach „cena" (rollback-guard: yu7 7,1 / zeekr 9x 2,6 / zeekr 8x 4,0 / g700 3,1 / aito m9 2,6 / arrizo 8 2,3) + stan ofert (oferty serii pilotażowych mają dziś 1–12 stron z impresjami i 0–4 kliki — praktycznie niewidoczne).

### KROK 1 — title ofert (core, `filterTitle()`)
Zastąpić 10 szablonów jednym deterministycznym (decyzje Janka 2026-07-13: separator `-`, bez frazy „import z Chin", **bez CTA** — wzorzec klasyfikatorów, cena jako hook; quiz rozstrzygnięty):

```
default:  {base} - {price} PLN | Prima-Auto
stock:    {base} - {price} PLN, dostępny od ręki | Prima-Auto   (gdy stm_car_location niepuste)
kolizja:  {base} - {price} PLN, {mileage} km | Prima-Auto
```

- `{base}` = `post_title` (ma już markę+model+rok+wersję, importer dedupuje prefix marki).
- `{price}` = `number_format(price, 0, ',', ' ')`; **bez słowa „cena"** (liczba nie konkuruje z hubem o KW „cena").
- Fallback bez ceny: `{base} | Prima-Auto`.
- **Kaskada unikalności**: gdy istnieje druga publish-oferta o tym samym `post_title` i tej samej cenie → dołóż przebieg (realny case: YU7 4WD Max #386592 vs #381460, obie 280 000 PLN, 7 000 vs 30 000 km). Wykrycie kolizji: 1 lekki query przy renderze single (bez N+1 — dotyczy jednej strony).
- `og_title` w `renderMeta()` już jest OK („{base} – import z Chin | Prima Auto") — ujednolicić z ceną analogicznie.
- H1 = `post_title` — **bez zmian**.
- Meta description — bez zmian (już zawiera paliwo, przebieg, cenę; usunąć tylko prefiks „Używane” jeśli występuje w opisach — do weryfikacji w KROKU 0).

**Pilot → rollout**: allowlist 9 serii — „TNT-battle" (YU7, SU7, Zeekr 9X, Zeekr 8X, G700) + „GSC-proven" (Tank 700, Geely Preface, AITO M9, Arrizo 8); pomiar 2–3 tyg. w GSC, potem globalnie. Gate: opcja `asiaauto_offer_title_v2_series` = CSV **term_id** taksonomii serie (`*` = wszystkie) — term_id, nie slug, bo slug `m9` jest niejednoznaczny (AITO M9 vs Galaxy M9).

> **AKTUALIZACJA 2026-07-13 (ten sam dzień, decyzja Janka w planie naprawy):** rollout na `*` wykonany od razu — powód: odkrycie **1 012 ofert ze zdublowanym title** poza pilotem (rotacja daje identyczny title dla tej samej wersji w tym samym koszyku `inner_id % 10`); v2 z ceną likwiduje duplikaty natychmiast. Pomiar ~27.07 pozostaje (baseline niezależny od zasięgu rolloutu); rollback selektywny możliwy przez wpisanie CSV term_id z powrotem.

### ~~KROK 2 — sekcja „Wyposażenie tej wersji"~~ WYKREŚLONY (KROK 0: treść już jest)
Oferty renderują pełny spec + wyposażenie z `extra_prep` przez `[asiaauto_tech_specs]` + `[asiaauto_equipment]`. Nic do dodania.

### KROK 3 — linkowanie hub↔oferta → PRZENIESIONY do T-187 (decyzja Janka 2026-07-13)
Scalony z taskiem roadmapy **T-187 „Inne egzemplarze modelu na stronie oferty + ścieżka do strony modelu na mobile"** (kosztorys `dane/etap3.json`, pozycja 1, rozmiar M). Powód scalenia: ustalenie z 2026-07-13, że na mobile breadcrumb i „Wróć do wyników" są celowo ukryte (`asiaauto-single.css:209-210`), a strzałka sticky-head prowadzi do `/samochody/` — brak widocznej ścieżki oferta→hub; blok „inne egzemplarze" załatwia linkowanie + konwersję + mobile naraz. **Nie realizujemy teraz** — czeka na decyzję po stronie kosztorysu. Pierwotny opis kroku poniżej (jako wsad do T-187):
- **Oferta → hub**: kontekstowy link nad/pod opisem z anchorem modelowym: „Xiaomi YU7 — cena w Polsce i wszystkie oferty" (breadcrumb już jest; to dodatkowy in-content).
- **Hub → oferty**: dynamiczny blok „Wersje {model} w ofercie" — grupowanie publish-ofert po wersji (wersja = `post_title` minus make/model/rok — parser heurystyczny, **wersja nie jest osobnym polem** — gotcha), link do najtańszej oferty per wersja z anchorem wersyjnym („YU7 4WD Max od 280 000 zł"). Blok renderowany pod istniejącą sekcją ofert, nie rusza wiki/FAQ.

### KROK 3b — duplikaty H1 ofert ✅ WDROŻONE 2026-07-13 (decyzja Janka: bez czekania na pomiar)
`h1WithVariantSuffix()` w v0.33.19 — przy duplikacie `post_title` H1 dostaje ` - {przebieg} km`, przy bliźniaku z tym samym przebiegiem dodatkowo `, {cena} PLN`. Render-only, `post_title` nietknięty. Duplikaty H1: **2 524 → 99** (resztka = egzemplarze identyczne tytułem+przebiegiem+ceną — brak wyróżnika; dzielą też title/desc). Badanie GSC „Duplicate chose different canonical" pozostaje ciekawostką pomiaru ~27.07, nie blokerem. Pierwotny opis poniżej:

### (pierwotnie) KROK 3b — audyt duplikatów H1 ofert (dodany 2026-07-13, zgłoszenie Janka po crawlu Screaming Frog)
Stan faktyczny (SQL 2026-07-13): **2 524 publish ofert dzieli H1 z inną ofertą** (499 zduplikowanych tytułów; top: AITO M9 2024 Ultra ×49, Voyah Dream PHEV ×39, YU7 4WD Max ×30). Przyczyna strukturalna: H1 = `post_title`, a feed importuje wiele egzemplarzy tej samej wersji. Do zbadania/decyzji:
- czy różnicować H1 dynamicznie przy renderze (stickyHead w `class-asiaauto-single.php`) tą samą kaskadą co title v2 (przebieg/kolor przy duplikacie) — **bez dotykania `post_title`** (używany w umowach/feedach);
- czy duplikaty H1 + niemal identyczna treść (te same tabele spec dla tej samej wersji) nie są współprzyczyną „3 050 stron z impresjami i 0 klików" (Google wybiera jednego kanoniczna-reprezentanta, reszta nie rankuje) — sprawdzić w GSC „Duplicate, Google chose different canonical" dla próbki;
- decyzja po pomiarze pilota title v2 (~27.07): jeśli title-cascade wystarczy, H1 może zostać.

### KROK 4 — pomiar
- GSC po 2–3 tyg. od pilota: impresje/pozycje fraz wersyjnych + kontrola pozycji hubów na „cena" (regresja > 3 pozycje na dowolnym hubie pilota = rollback flagi).
- Re-submit sitemap ofert; **bez** batcha Indexing API (quota — memory `feedback_ask_before_quota_burn`; hook transition_post_status i tak zgłasza zmiany).

## 4. Czego świadomie NIE robimy

- Nie ruszamy title/H1/treści hubów (rework „cena" działa: imp ~3×).
- Żadnych canonical oferta→hub (osobne produkty, self-canonical).
- Żadnego editorialu/LLM na ofertach (treść modelowa = huby, decyzja Janka 2026-07-13).
- Nie ruszamy: importera, slug generation, reservation logic, nazw klas/CPT/meta.

## 5. Pliki dotykane

| Plik | Zmiana |
|---|---|
| `includes/class-asiaauto-single.php` | `filterTitle()` — nowy szablon + kaskada + gate; `renderMeta()` — og_title; nowa metoda renderu extra_prep |
| `themes/primaauto2026/taxonomy-serie.php` (lub odpowiedni render hubów w pluginie) | blok „Wersje w ofercie" — **ZAWSZE PYTAJ przed Edit** (memory `feedback_no_edit_homepage_without_ok`) |
| `docs/VERSIONS.md` | bump po deploy |

Deploy wg checklisty (backup `.bak`, `php -l`, smoke, bump `ASIAAUTO_VERSION`).

## 6. Ryzyka

| Ryzyko | Mitygacja |
|---|---|
| Masowa zmiana title ~4 500 stron naraz → przetasowanie SERP | pilot allowlist 5 modeli → pomiar → rollout |
| Cena w title nieaktualna po przeliczeniu kursu | title generowany dynamicznie przy renderze — zawsze bieżąca cena z meta |
| Kanibalizacja hub↔oferta mimo podziału | zakaz słowa „cena" w title ofert + linkowanie hierarchiczne + monitoring GSC per hub |
| Parser wersji z post_title błędny dla egzotycznych nazw | fallback: brak grupowania (blok wersji pomijany dla serie bez czystego parsowania) |
