# Brief treści + struktura blogu + strategia zdjęć — Prima-Auto
**Data:** 2026-05-30 · **Status:** propozycja do akceptu, ZERO wdrożeń na produkcji
**Cel:** odzyskać intent „cena / gdzie kupić / cena w Polsce", którego dziś nie domykamy (poz 5–8, 0% CTR), bez kanibalizowania przewagi na „import" (poz 2,5, 33% CTR).

---

# CZĘŚĆ 1 — PILOT: wzbogacony hub Denza Z9 GT

**URL docelowy:** `/samochody/denza/z9-gt-dm-i/` (term 4660, 16 egzemplarzy, ceny 209–279k PLN)
**Stan obecny:** wiki ~900 słów, 5× H2, 2 tabele, **0 zdjęć, 0 FAQ, 0 FAQPage schema**, 1× `{{LISTINGS_BAR}}`.
**Konkurent referencyjny:** `chinskisamochod.com/denza-z9-gt/` — ~4–6k słów, 24× H2, FAQ (ale BEZ FAQPage schema → luka do wygrania), H1 = title = intent.

## 1.1 Title + H1 (pod intent, bez szumu „DM-i" w głównej frazie)

- **Title (≤60 zn.):** `Denza Z9 GT — cena w Polsce i gdzie kupić | od 209 000 zł`
- **H1:** `Denza Z9 GT DM-i — cena w Polsce, gdzie kupić i ile kosztuje import`
- **Meta description:** `Denza Z9 GT DM-i w Prima-Auto — 16 egzemplarzy, od 209 000 zł. Sprawdź cenę w Polsce, dane techniczne, spalanie i jak sprowadzić auto z Chin z homologacją i gwarancją importera.`

Uzasadnienie: GSC pokazuje że na `denza z9 gt cena` (poz 8) i `cena w polsce` (poz 4,9) mamy impresje ale 0% CTR — snippet nie odpowiada na pytanie. Sufiks „DM-i, 16 sztuk" w title nie pasuje do zapytania searchera. „import" zostaje w treści (tam wygrywamy), ale title celuje w cena/gdzie kupić.

## 1.2 Struktura H2/H3 (mapa: PAA + GSC long-tail + braki vs konkurent)

Kolejność = lejek searchera (cena → decyzja → szczegóły → akcja). Przy każdej sekcji źródło intentu.

1. **Denza Z9 GT — cena w Polsce 2026** `[denza z9 gt cena 17 imp · cena w polsce 12 imp · PAA „Ile kosztuje w Polsce?"]`
   - cena w Prima-Auto: od 209 000 zł (16 egz.), zakres do 279 000 zł
   - scenariusze: cena w Chinach (CNY) vs import indywidualny vs (brak) oficjalnej dystrybucji w PL
   - co składa się na cenę importu (cło, akcyza, VAT, homologacja, transport) — przewaga eksperck. nad konkurentem
2. **Gdzie kupić Denza Z9 GT w Polsce** `[PAA „gdzie kupić" · intent gdzie kupić]`
   - status: brak oficjalnego salonu Denza w PL → import indywidualny jako jedyna realna ścieżka
   - jak kupujesz przez Prima-Auto (pośrednictwo, depozyt zwrotny, gwarancja importera) — zgodne z modelem prawnym
   - **↓ kotwica do gridu ofert** (patrz 1.4)
3. **Denza Z9 GT DM-i — dane techniczne** `[dane techniczne 6 imp · wymiary 5 · moc 3 · PAA „jaki silnik"]`
   - tabela: napęd PHEV 870 KM, bateria, zasięg EV, wymiary 5195 mm, masa
4. **Ile pali Denza Z9 GT** `[spalanie 4 imp · PAA „Ile pali na 100 km?"]`
   - spalanie PHEV miasto/trasa, zasięg w trybie EV, ładowanie 800 V
5. **Wersje: Pro / Max / Ultra** `[denza z9 gt ultra 2 imp · ultra cena 2 imp]`
   - tabela porównawcza wyposażenia + które wersje mamy on-lot
6. **Denza Z9 GT vs konkurencja** `[PAA porównania · opinie 1 imp]`
   - vs Porsche Taycan/Panamera, vs BMW i5 — pozycjonowanie wartości (nasze auto realnie dostępne, nie „kiedyś w salonie")
7. **Czy Denza Z9 GT to dobry samochód? — opinie** `[PAA „Czy to dobry samochód / czy opłaca się"]`
   - mocne strony / wady (uczciwie), dla kogo
8. **Import Denza Z9 GT przez Prima-Auto** `[denza z9 gt import poz 2,5 — TU wygrywamy, zostaje]`
   - proces krok po kroku, czas, homologacja, gwarancja
9. **FAQ** (sekcja + **FAQPage schema JSON-LD** — przewaga: konkurent ma FAQ bez schematu)

## 1.3 FAQ (z FAQPage schema) — pytania 1:1 z PAA Google

- Ile kosztuje Denza Z9 GT w Polsce?
- Gdzie kupić Denza Z9 GT w Polsce?
- Ile pali Denza Z9 GT na 100 km?
- Czy Denza Z9 GT będzie w oficjalnej sprzedaży w Polsce?
- Jaki silnik i moc ma Denza Z9 GT DM-i?
- Czy Denza Z9 GT to dobry samochód?
- Ile trwa import Denza Z9 GT z Chin?
- Czy auto ma homologację i gwarancję w Polsce?

Implementacja: `FAQPage` JSON-LD wstrzykiwany przez `class-asiaauto-seo.php` (priority 2 schema, tam gdzie już jest Car/BreadcrumbList). UWAGA na smart quotes w JSON (memory `feedback_smart_quotes_break_json`) — tylko ASCII `"`.

## 1.4 Kotwice pod Ads (kompromis content ↔ konwersja)

Problem: hub bogaty w treść = słaby landing dla performance-Ads (czytanie ≠ konwersja).
Rozwiązanie 3-warstwowe:
- **Sticky CTA bar** (góra, mobile-first): „16 Denza Z9 GT dostępnych · od 209 000 zł · [Zobacz oferty ↓] [WhatsApp]”
- **Kotwica `#oferty`** przy sekcji 2 → grid `{{LISTINGS_BAR}}` z listingami
- **Ads research-intent** (np. „denza z9 gt opinie/cena") → landing = ten hub z `#oferty`
- **Ads performance** (np. „denza z9 gt kup/dostępne") → landing = `/samochody/?marka=denza&model=z9-gt-dm-i` (filtrowane listingi, jak dziś w RSA memory)

Granica: hub = research + dół lejka na jednej stronie; filtr `/samochody/?...` = czysty performance.

## 1.5 Zdjęcia tego artykułu (patrz Część 3 — pełna strategia)

- **1× hero** (above-the-fold): widowiskowy render Denza Z9 GT (scena dramatyczna, auto z realnego listingu, tło podmienione) — WebP
- **2–3× inline**: detal wnętrza / profil / ładowanie — z realnych zdjęć ogłoszeń (upscale + relight, BEZ zmiany detali auta)
- nazewnictwo + alt: patrz 3.4

---

# CZĘŚĆ 2 — STRUKTURA BLOGU / BAZY WIEDZY

**Po co osobny blog (a nie tylko huby):** chinskisamochod zgarnia 2/3 ruchu na contencie informacyjnym, którego huby NIE obsługują (rankingi, przewodniki, „chińskie marki", porównania). Blog bierze ten intent i **linkuje do hubów/listingów**, zamiast z nimi konkurować.

## 2.1 Reguła antykanibalizacyjna (twarda)

| Intent | Gdzie żyje | Dlaczego |
|---|---|---|
| `{model} cena / import / gdzie kupić` | **tylko hub** `/samochody/...` | transakcja, tu rankujemy |
| `{model} opinie / test / vs / dane techniczne` | hub (sekcja) **lub** blog — nie oba na tę samą frazę | wybór per fraza, jedna fraza = jeden URL |
| rankingi / przewodniki / „chińskie marki" / „najlepszy chiński SUV" | **tylko blog** | huby tego nie pokrywają |
| news / premiery / dostępność | **tylko blog** | świeżość, huby statyczne |

Zasada: zanim opublikujemy wpis blogowy, sprawdzamy w GSC czy hub już nie rankuje na tę frazę (audyt kanibalizacji — osobny krok).

## 2.2 Pillary (architektura hub-and-spoke)

**Pillar A — „Chińskie marki samochodów w Polsce"** `[5 400 + 2 400/mc]`
- spoke: lista wszystkich marek (link do /marki/), logo/znaczki `[340/mc]`, premium `[50]`, elektryczne `[70]`, „w Europie", „w 2026"
**Pillar B — Rankingi** (intent „jaki wybrać", waliduje PAA „Co lepsze Jaecoo czy Omoda")
- Najlepszy chiński SUV w Polsce 2026 `[110/mc + long-tail]`
- Chiński samochód 7-osobowy — jaki wybrać `[konkurent ETV 124, DFS gap]`
- Ranking chińskich aut elektrycznych / hybrydowych
- Najtańszy chiński samochód elektryczny `[390/mc + „za 8000 zł" 110]`
**Pillar C — Przewodniki zakupowe** (nasza unikalna ekspertyza importera — przewaga nad blogiem konkurenta)
- Jak sprowadzić auto z Chin krok po kroku (homologacja, cło, VAT, akcyza)
- Ile kosztuje import auta z Chin — kalkulacja
- Homologacja indywidualna chińskiego auta w Polsce
- Gwarancja i serwis chińskiego auta sprowadzonego z Chin
**Pillar D — Porównania modeli** (vs)
- Jaecoo 8 vs Omoda 5, BYD Seal 6 vs konkurencja, itp. → każdy linkuje do hubów obu modeli
**Pillar E — News / premiery / dostępność** (świeżość, „kiedy w polsce")

## 2.3 Priorytet wg popytu (nie wg stocku!)

Kolejność produkcji = realny volume. Top targety treści:
1. Omoda 5 `[154 230/mc]` — ⚠️ ~zero stocku → wpis + lead-gen „sprowadzimy na zamówienie" (decyzja Ruslana)
2. Jetour T2 `[44 830]` — mamy stock 27 ✓
3. Xiaomi SU7 `[40 660]` — stock 63 ✓
4. Jaecoo 8 `[37 200]` — ⚠️ 0 stocku → lead-gen / decyzja sourcing
5. BYD Seal 6 `[10 330]` — stock 57 ✓
6. Pillar A „chińskie marki" `[8 780]`

Insight strategiczny: nasz największy stock (AITO M9 112, Changan UNI-V 112) ma mały popyt — nie pchać tam contentu/Ads na siłę.

---

# CZĘŚĆ 3 — STRATEGIA ZDJĘĆ

**Cel:** z realnych zdjęć ogłoszeń (Dongchedi/Che168) zrobić widowiskowe, dynamiczne grafiki do artykułów — zachowując detale auta — w WebP, z altami i nazwami pod wpis.

## 3.1 Twarda zasada (ważna — flaguję ryzyko)

**Dwie różne pule zdjęć, NIE mieszać:**
- **Zdjęcia DOWODOWE listingu** (galeria konkretnego egzemplarza, którą ocenia kupujący) → **NIE ruszamy, zostają realne.** Stylizacja zdjęcia konkretnego auta na sprzedaż = ryzyko wprowadzenia w błąd, szczególnie przy modelu pośrednictwa/prawnym. To byłby błąd.
- **Zdjęcia EDYTORSKIE / hero do artykułu i blogu** (ilustracja modelu, nie konkretnego egzemplarza) → **tu robimy stylizację/render** — dozwolone i pożądane.

Czyli: „regenerujemy" pod content marketingowy, a nie podmieniamy materiał dowodowy oferty.

## 3.2 Narzędzie — rekomendacja: Gemini (Nano Banana), nie Freepik

Mamy dostęp do pełnej linii obrazowej Gemini (klucz `~/secrets/google/gemini-api-key.txt`):
`gemini-3-pro-image` (Nano Banana Pro), `gemini-3.1-flash-image` (Nano Banana 2), `gemini-2.5-flash-image` (Nano Banana), `imagen-4 / ultra / fast`.

**Dlaczego Gemini > Freepik dla TEGO zadania:** rodzina Nano Banana jest zbudowana pod **edycję obrazu z zachowaniem obiektu** (subject/consistency) — podajesz realne zdjęcie auta + instrukcję „zachowaj auto identycznie, zmień tylko otoczenie/światło", a model trzyma karoserię/proporcje/detale. Rozumie instrukcje po polsku, przyjmuje wiele zdjęć referencyjnych (kilka ujęć tego samego auta = lepsza spójność), generuje w wysokiej rozdzielczości. Freepik wymagałby łańcucha relight+background+upscale i słabiej trzyma subiekt.

| Model | Rola | Uwaga |
|---|---|---|
| **gemini-3.1-flash-image (Nano Banana 2)** | **podstawowy — hero + inline** | najnowsza generacja (3.1), świetnie trzyma subiekt, szybki, tani; domyślny wybór |
| gemini-3-pro-image (Nano Banana Pro) | opcjonalne A/B na hero | maks. wierność + render do 2–4K, gdy 1 kluczowy hero ma być bezkompromisowy; droższy |
| gemini-2.5-flash-image (Nano Banana) | fallback | starsza wersja, gdyby 3.1 miała problem z konkretnym kadrem |
| imagen-4 ultra | TYLKO tła/abstrakcje | ❌ HALUCYNUJE auto — nigdy „nasze auto" |
| Freepik upscaler / Kling | upscale + wideo | upscale do WebP; Kling = „dynamiczne" 3–5 s motion na social (Gemini nie robi wideo) |

**Granica wierności (flaguję):** nawet Nano Banana Pro potrafi subtelnie zmienić emblemat / wzór felg / listwy. Dlatego: do **hero/editorial** OK; do **zdjęć dowodowych listingu — NIGDY** (patrz 3.1). Każdy output weryfikujemy okiem (i ew. AI-classifier) przed publikacją.

Rekomendacja na hero: **realne zdjęcie listingu (profil 3/4) → Nano Banana 2 (gemini-3.1-flash-image) z instrukcją „zachowaj auto 1:1, podmień tło na [studio / górska droga / miasto nocą], dodaj dynamiczne światło/odbicia" → upscale → WebP.** Gdyby 1 sztandarowy hero miał być bezkompromisowy — A/B z Nano Banana Pro. „Dynamiczne wideo": opcjonalnie Kling (Freepik) z hero jako klatką startową.

## 3.3 Pipeline (Gemini, synchronicznie)

1. Wybór 1 źródłowego zdjęcia z listingu (najlepszy profil 3/4) — z `wp-content/uploads/asiaauto/` (można podać 2–3 ujęcia jako referencje spójności)
2. POST `gemini-3.1-flash-image:generateContent` (Nano Banana 2) — obraz(y) inline + prompt edycji (zachowaj auto / zmień scenę); zwraca obraz w odpowiedzi (base64, bez pollingu)
3. (opc.) upscale (Freepik upscaler) jeśli potrzebna wyższa rozdzielczość
4. Konwersja do WebP (jakość ~82, szerokości responsywne 1600/1200/800)
5. Upload do biblioteki WP + wstawienie w wiki_body z alt/nazwą
- Klucz: `~/secrets/google/gemini-api-key.txt`. Koszt per obraz (Nano Banana ~$0,04, Pro wyżej) — sprawdzić PRZED batchem (memory `feedback_check_api_limits_before_batch`; Gemini Flash ~10× tańszy). **Pilot = 1 hero, zmierzyć koszt + jakość, potem skala.**

## 3.4 Nazewnictwo + alt (pod SEO wpisu)

Spójne z konwencją projektu `{mark}-{model}-{year}-{city}-{inner_id}-{n}.webp`, ale dla treści edytorskiej kontekstowo:
- **plik:** `denza-z9-gt-dm-i-cena-polska-hero.webp`, `denza-z9-gt-dm-i-wnetrze.webp`, `denza-z9-gt-dm-i-profil-import.webp`
- **alt:** `Denza Z9 GT DM-i — cena w Polsce i import z Chin | Prima-Auto` / `Wnętrze Denza Z9 GT DM-i — kokpit i ekran` / `Denza Z9 GT DM-i profil — dostępna w Prima-Auto`
- **title/caption:** opcjonalnie, z modelem + „Prima-Auto”
- alt opisuje obraz + 1 fraza, bez keyword-stuffingu

## 3.5 Image SEO bonus

- WebP + width/height (anty-CLS, memory CWV), `loading=lazy` poza hero, hero `fetchpriority=high`
- `ImageObject` w schema artykułu (hero) — wzmacnia rich result
- 1 hero + 2–3 inline per wpis (nie więcej — waga strony)

---

# PODSUMOWANIE — co dalej (każde = osobny akcept, zero auto-deploy)
1. Akcept struktury H2/FAQ Denza → przygotowanie finalnej treści wiki (regen pod nowy outline)
2. Decyzja FAQPage schema w `class-asiaauto-seo.php`
3. Decyzja Ruslana: Omoda 5 / Jaecoo 8 — dosprowadzić czy lead-gen
4. Pilot 1 hero Freepik (relight+tło) — pomiar kosztu przed skalą
5. Audyt kanibalizacji GSC przed startem blogu
