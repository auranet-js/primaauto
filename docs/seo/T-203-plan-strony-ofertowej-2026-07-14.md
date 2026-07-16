# Plan przebudowy strony OFERTOWEJ pod SEO (T-203)

> Data: 2026-07-14 | Status: **PLAN — nic nie wdrożone poza tym, co oznaczone LIVE**
> Zakres: **wyłącznie strona oferty** (`/oferta/…`). Huby = osobny track,
> patrz `docs/seo/znaleziska-huby-2026-07-14.md`. Nie mieszać.

---

## 0. Co już zrobione w tym wątku (LIVE 2026-07-14)

- **v0.33.21 — szyk title v3:** `{Marka} {Model} {Wersja} {Rok} - cena, km`. Rok na koniec (dane GSC:
  wersja 3 413 imp > rok 1 117). + prefiks marki gdy brak (55 ofert: Galaxy→Geely, Beijing→BAW) + wycięcie
  CJK (40 ofert). Render-only, `post_title` nietknięty.
- **v0.33.22 — hoist wersji:** token wersji tuż za model (`Xiaomi YU7 Max 4WD 2025`). Kotwica = term serie.
  Guard `v4FindTrim` przeciw wymyślaniu wersji (bug „YU7 Ultra" z „Ultra Long Range" wyłapany na smoke).
- Szczegóły: `docs/VERSIONS.md` 0.33.21-22, memory `project_t203_v3_v4_szyk_title_2026_07_14`.

---

## 1. Stan indeksacji ofert (zmierzone, NIE jest problemem)

URL Inspection 280 ofert modeli TNT: **259 PASS (93%)**. Niezaindeksowanych 21. Wniosek: Indexing API
to NIE dźwignia — 259 stron już w indeksie i to nie one przegrywają. Higiena, nie priorytet.

---

## 2. PRIORYTET 1 — linkowanie oferta → hub ✅ WYKONANE 2026-07-16 (v0.33.23-26)

Zrobione w całości i szerzej niż plan zakładał — szczegóły `docs/VERSIONS.md` 0.33.23-26:
- **anchor pełną nazwą** (`serieAnchor()`, jedna funkcja dla breadcrumbu + JSON-LD + navrow).
  Skala okazała się większa niż w planie: **258 z 302 modeli / 2 908 ofert** linkowało bez marki.
  Symulacja 100 serii wyłapała kolizję (Sealion 5 DM/EV → ten sam anchor) PRZED wdrożeniem: 1 → 0.
- **breadcrumb na mobile** — nie jako osobna linia (ciasno), tylko jako **link w sticky navrow**
  w linii z badge'ami (pomysł Janka): jedzie przez cały scroll, zero dodatkowej wysokości.
- **`#oferty`** — klient ląduje na liście egzemplarzy, nie na lead/wiki/FAQ hubu.
- **desktop**: „Wróć do wyników" (`/samochody/`) → „← Wszystkie oferty {model}" → `hub#oferty`.
- **UX/a11y**: target 44px (było ~16 = poniżej WCAG 2.2), tytuł 2 linie (widać wersję),
  cena 22px + „z VAT", jeden H1 (mobile-first).

**Pomiar:** efekt linkowania materializuje się tygodniami — sprawdzić razem z pomiarem
title v3/v4 (~27.07) i ponownie ~15.08. Metryka: pozycje hubów na frazach broad (`{model}`
bez „cena”) + kliki hubów. Baseline: `T-203-baseline-gsc-2026-07-13.md`.

### (pierwotny opis P1)

**Problem (zmierzony):** 3 056 ofert linkuje do hubów **tylko breadcrumbem `display:none` na mobile**
(`asiaauto-single.css:209`). Google indeksuje mobile-first → te linki są dyskontowane. Hub, który ma
wygrywać frazy broad, nie dostaje siły od 3 000 własnych podstron. Anchor to w dodatku samo `YU7`, nie
`Xiaomi YU7`.

**Zmiany (WYŁĄCZNIE strona oferty):**
1. Breadcrumb widoczny na mobile — zdjąć `display:none` (ew. kompaktowy wariant), byle w DOM i klikalny.
2. Anchor breadcrumbu = pełna nazwa (`Xiaomi YU7`, nie `YU7`) — link niesie frazę.
3. Rozważyć drugi link kontekstowy w treści oferty: „Zobacz wszystkie oferty **Xiaomi YU7**" → hub.

**Ryzyko:** dotyka `asiaauto-single.css` + breadcrumb (zmiana wizualna mobile → wymaga „ok", reguła
homepage/single = zawsze pytaj). **Hub NIE dotykany.** **Effort: S. Zwrot: największy w planie.**

---

## 3. PRIORYTET 2 — kanibalizacja broad między ofertami (rozstrzygnięcie kierunku)

**Problem (zmierzony):** po hoiście v4 wiele ofert jednego modelu zawiera exact `Xiaomi YU7 Max` → biją
się o broad nawzajem. Dowód: `li auto l9` = 5 naszych ofert na poz. **29–63**, TNT jedną stroną #20.

**Rozstrzygnięcie z SERP (`xiaomi yu7`, `zeekr 8x`, `li auto l9` — otomoto #1, agregatory):** frazy broad
ma brać **HUB** (agregator = właściwy typ), oferty zostają na long-tailu z ceną/przebiegiem.
- **NIE** noindex/canonical na ofertach — to fizycznie różne auta (VIN/cena/przebieg), nie duplikaty;
  kanonikalizacja wyrzuciłaby realny towar z long-taila.
- **Champion NIE konieczny**, jeśli hub przewyższy oferty (robi to P1). Zostaje jako opcja awaryjna.
- Champion (gdyby trzeba, deterministycznie): wybór per (serie, wersja) `on_lot` → min. przebieg →
  najwięcej zdjęć → najstarszy post; zapis term_meta; hub linkuje anchorem `{model} {wersja}`. NIE wdrażać
  bez decyzji. **Domyślnie: pomijamy — P1 powinno wystarczyć.**

**Effort:** 0 jeśli pomijamy champion; M jeśli wchodzi.

---

## 4. PRIORYTET 3 — higiena danych ofert (niskie ryzyko, niezależne)

- **21 niezaindeksowanych** URL → Indexing API (budżet wolny, feed stoi). S.
- **1 oferta** z `post_title` od `[` (`[Xiaomi SU7 2024…`) — śmieć feedu. S.
- **Podwójne „DM-i"** BYD Tang (defekt feedu, hoist uwypuklił) — opcjonalny dedup. S.
- **`SU7 RWD Standard Long Range`** bez hoistu (guard „Long Range") — świadomy kompromis; słownik wersji
  per model tylko jeśli model istotny. M.

---

## 4b. DO ANALIZY (zgłoszenie Janka 2026-07-16) — GSC widzi tylko 79 poprawnych „opisów produktów", i to HUBY

**Obserwacja:** raport GSC „Opisy produktów" (Product snippets / Merchant listings) pokazuje
**79 poprawnych** — a to są **huby**, nie oferty. Mamy **3 056 ofert**, każda z konkretnym
egzemplarzem (VIN, cena, przebieg, dostępność). Pytanie Janka: **czy tam nie powinny być oferty?**

**Dlaczego to nie jest oczywiste (do dyskusji, NIE przesądzać):**
- Oferta = pojedynczy egzemplarz z ceną → wygląda na naturalnego kandydata na `Product`/`Offer`
  (rich result z ceną i dostępnością w SERP = wyższy CTR).
- Hub = agregator wielu egzemplarzy → raczej `ItemList`, a `Product` z ceną „od" bywa naciągane.
- Ale: oferty mają **405 klików/90d na ~3 000 stron** i są stroną **konwersyjną, nie akwizycyjną**
  (676 sesji organic vs 26 978 odsłon — memory `reference_mobile_share_and_offers_are_conversion_pages`).
  Rich result na stronie, która nie rankuje, nie da ruchu. Huby rankują (5 600 klików).
- Ryzyko odwrotne: `Product` na hubie z ceną „od" przy 44 egzemplarzach różnych cen może być
  niezgodne z wytycznymi Google (cena musi dotyczyć konkretnego produktu).

**Co ustalić PRZED decyzją (kolejność):**
1. **Który raport GSC** — „Merchant listings" czy „Product snippets"? To dwa różne raporty
   o różnych wymaganiach (Merchant wymaga m.in. `priceValidUntil`, `shippingDetails`, `returnPolicy`).
2. **Co dziś emitują oferty** — recon 2026-07-16: `renderMeta()` (`class-asiaauto-single.php:867+`)
   emituje schema; historycznie był fix **multi-type `[Product, Car]`**
   (memory `project_session_2026_06_07_gsc_full_audit`). Sprawdzić, czy multi-type nie jest powodem,
   dla którego GSC ich **nie liczy** jako Product.
3. **Co emitują huby** — skąd te 79 (a nie 302)? Czemu akurat 79?
4. **Czy 79 to nie jest po prostu „tyle ilu Google zdążył przecrawlować/uznać"** — sprawdzić daty.
5. Dopiero potem: czy chcemy Product na ofertach, czy ItemList na hubach + Product na ofertach.

**Powiązane resztki schema (ZAD.12/13, otwarte):** brak `OfferShippingDetails` w Car schema
(gap vs west-motors); utracone przy dedupie 2026-04-24: `vehicleEngine` (KW), `vehicleTransmission`,
`driveWheelConfiguration`, `color`, `itemCondition`. **Plus T-211 ⚡2: kod pyta o taksonomię `color`,
która NIE ISTNIEJE** (są `exterior-color`/`interior-color`) → kolor nie trafia do schematu żadnej
z 3 056 ofert; fix = 1 linia. Te trzy rzeczy warto zrobić jednym ruchem z powyższą analizą.

**Status: DO DYSKUSJI — nic nie przesądzone, nic nie wdrożone.**

---

## 5. Poza planem on-page, ale to decyduje o broad — link building

Na `xiaomi yu7` konkurujemy z mi.com/otomoto/Wikipedią. **1 backlink vs 24 (TNT).** Bez linków top10 na
najgrubszych frazach jest poza zasięgiem niezależnie od on-page. Dwie rzeczy:
- **T-194: 301 z `asiaauto.pl`** (dziś 200-pustka/404) — jedyne historyczne equity, trywialne.
- **5–10 linków** (katalogi moto, PR, guest) — przy lidze 1-linkowej każdy przesuwa.
Osobny track, ale bez niego P1–P3 mają sufit.

---

## Kolejność rekomendowana (tylko oferty)

1. **P1** (breadcrumb oferta→hub) — najtańsze, największy zwrot, hub nietknięty. **START.**
2. **P3** (higiena: 21 URL + śmieci) — równolegle, niezależne.
3. **P2** — domyślnie pomijamy champion; wracamy tylko jeśli po P1 oferty dalej zabierają hubom broad.
4. **Link building / T-194** — osobny track, najwyższy sufit.

**Track HUBÓW (H1 „cena w Polsce", cena per wersja, BAW/Zeekr 8X) = osobny dokument, osobna decyzja.**

**Nic nie wdrożone poza szykiem title (0.33.21-22). Czekam na wybór priorytetu ofertowego.**
