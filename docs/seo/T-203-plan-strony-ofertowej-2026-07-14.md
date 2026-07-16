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

## 3. PRIORYTET 2 — champion ROZSTRZYGNIĘTY 2026-07-16: **NIE ROBIMY**

> Pomiar rozstrzygający (pkt 10 z listy Janka) wykonany. Skrypty: `scratchpad/gsc_champion_check.py`,
> `gsc_hub_missing.py`, `hub_state.php`, `inspect_hubs.py`. Okno GSC 2026-04-17…07-15 (90 dni).

**Uwaga terminologiczna (nieporozumienie z 2026-07-16):** champion ≠ canonical. Canonical oferta→oferta
jest **wykluczony** — to fizycznie różne auta (VIN/cena/przebieg), kanonikalizacja 30→1 wyrzuciłaby
29 realnych sztuk z long-taila. Champion = wybór jednej oferty per (model, wersja), do której **hub
linkuje** anchorem wersyjnym. Każda oferta zostaje **self-canonical**.

### Skala (SQL, publish)
**2 526 z 3 058 ofert (83%) dzieli identyczny `post_title`** z inną ofertą — 499 grup.
Top: AITO M9 2024 EREV Ultra 6-Seater ×49, Voyah Dream PHEV ×39, AITO M9 2025 ×39, NIO ET5 Touring ×30,
Xiaomi YU7 4WD Max ×30. Skala rozjazdu jest więc realna — ale to nie znaczy, że kosztuje.

### Bilans GSC 90 dni — kanibalizacja hub↔oferta jest TANIA

| | fraz | kliki |
|---|---|---|
| oferta bije hub (kanibalizacja) | 75 | **28** |
| hub bije oferty | 87 | **601** |
| hub nie rankuje wcale, tylko oferty | 38 | — |

**28 klików / 90 dni = szum.** Tam, gdzie hub rankuje, broni się sam i to na frazach grubych:
`zeekr 8x` hub 2,7 vs oferta 7,0 · `byd leopard 3` 2,1 vs 8,0 · `byd leopard 5` 1,0 vs 6,0 ·
`geely monjaro` 4,3 vs 10,0. Champion nie ma tam czego naprawiać.

### Cztery powody, dla których champion jest ODRZUCONY

1. **Nie ma czego ratować** — 28 klików na 90 dni w całym konflikcie.
2. **Oferty są EFEMERYCZNE — to zabija pomysł u podstaw.** Z 30 realnych fraz „hub nie rankuje"
   **16 (53%) rankowało ofertą, która już NIE ISTNIEJE** — auto się sprzedało. Zweryfikowane:
   te URL robią **301 na hub** (`geely preface` → `/samochody/geely/preface/`, `yangwang u8` →
   `/samochody/byd/yangwang-u8/`, `voyah dream phev` → `/samochody/voyah/dream-phev/`).
   Champion linkowałby z huba do strony, która za kilka tygodni przekieruje z powrotem na hub.
   **Hub jest jedynym trwałym bytem w tym katalogu.**
3. **Tam gdzie hub przegrywa, champion nie pomoże** — problem nie leży w ofertach, tylko w tym,
   że Google nie uznaje huba za odpowiedź. Link z huba do oferty tego nie zmieni.
4. **Właściwy lek już wdrożony (2026-07-16, v0.33.23-26)** — 2 908 ofert zaczęło linkować do hubów
   pełną nazwą (breadcrumb + navrow + JSON-LD). To pcha sygnał **do** huba; champion działa
   w przeciwną stronę.

**Status: champion zostaje jako opcja awaryjna.** Wraca tylko, jeśli po pomiarze ~15.08 oferty nadal
wypierają huby, a bilans klików w konflikcie urośnie z 28 do poziomu, który cokolwiek znaczy.

---

## 3b. ZNALEZISKO — 30 fraz, gdzie hub NIE rankuje, choć jest zdrowy

**Huby nie są zepsute — i to jest zagadka.** Sprawdzone 8 z nich przez URL Inspection:
**wszystkie PASS, „Strona przesłana i zindeksowana", canonical OK, crawl 05–16.07.**
Wszystkie mają wiki (3,5–6 tys. znaków), FAQ, `rank_math_title` i oferty na stanie.
Czyli: **nie indeksacja, nie treść, nie canonical, nie techniczne.** Zostaje trafność/autorytet —
Google uznaje pojedynczą ofertę za lepszą odpowiedź niż agregat.

**Odsiew:** 8 z 38 fraz to **dorki scrapera** (`"g5.pasha" -site:reddit.com`, `"oferta" "auto"
-site:reddit.com`, `"max" "w polsce" -"burgers"`, `"pompa prima"…`) — nie zapytania klientów,
patrz [[project_scraping_recon_2026_07_12]]. Zostaje 30 realnych.

**Lista (imp / ofert w SERP / hub → stan):**

| fraza | imp | ofert | hub (serie) | ofert na stanie | wiki |
|---|---|---|---|---|---|
| geely preface | 200 | 32 | Geely Preface | 91 | 4 711 |
| tank 700 | 92 | 6 | Tank 700 Hi4-T | 14 | 5 769 |
| geely atlas pro | 48 | 10 | Geely Atlas Pro | 31 | 3 679 |
| tank 700 hi4-t | 47 | 3 | Tank 700 Hi4-T | 14 | 5 769 |
| yangwang u8 | 43 | 2 | BYD Yangwang U8 | — | — |
| voyah dream phev | 35 | 14 | Voyah Dream PHEV | 56 | — |
| exlantix et | 30 | 3 | Exlantix ET | 4 | 3 712 |
| sagitar | 28 | 9 | (oferta 301) | — | — |
| geely preface 2026 | 26 | 14 | Geely Preface | 91 | 4 711 |
| geely ex2 | 23 | 5 | (oferta 301) | — | — |
| hongqi hq9 | 23 | 4 | (oferta 301) | — | — |
| byd tang dm-i | 20 | 10 | BYD Tang DM-i | 31 | 4 051 |
| forting | 18 | 2 | Dongfeng Forting U-Tour V9 | 3 | 6 093 |
| tank 700 kiedy w polsce | 17 | 1 | Tank 700 Hi4-T | 14 | 5 769 |
| changan cs75 plus spalanie | 16 | 3 | Changan CS75 Plus | 35 | 3 911 |
| exlantix et erev | 16 | 4 | (oferta 301) | — | — |
| v27 auto | 16 | 1 | (oferta 301) | — | — |
| xpeng p7 ultra | 14 | 5 | (oferta 301) | — | — |
| voyah free | 14 | 3 | (oferta 301) | — | — |
| byd qin l dm-i | 12 | 7 | BYD Qin L DM-i | 22 | 3 825 |
| tank700 | 12 | 2 | Tank 700 Hi4-T | 14 | 5 769 |
| wey 07 | 11 | 5 | (oferta 301) | — | — |
| geely starship 7 | 11 | 2 | (oferta 301) | — | — |
| li auto l9 ultra 2026 | 11 | 2 | (oferta 301) | — | — |
| mg 7 2.0t | 11 | 4 | MG 7 | 25 | 3 523 |
| tank 700 otomoto | 11 | 4 | Tank 700 Hi4-T | 14 | 5 769 |
| icar v23 501 | 10 | 5 | (oferta 301) | — | — |
| denza leopard 8 | 10 | 4 | BYD Leopard 8 (Denza B8) | 9 | 4 044 |
| geely emgrand 2026 | 10 | 2 | (oferta 301) | — | — |
| tank 300 | 10 | 6 | Tank 300 | 26 | 3 637 |

**Skupiska:** Tank 700 Hi4-T (5 fraz: „tank 700", „tank 700 hi4-t", „tank700", „tank 700 kiedy
w polsce", „tank 700 otomoto"), Geely Preface (2 frazy, 91 aut, 32 oferty w SERP zamiast huba).
Wolumeny są **małe** (10–200 imp/90d) — to długi ogon, nie fraza grube. Wartość naprawy:
umiarkowana, ale wzorzec systemowy.

**Co dalej (NIE teraz, po pomiarze ~15.08):**
1. Czy dzisiejsze linkowanie oferta→hub (v0.33.23-26) wepchnęło te huby do SERP — to jest test
   hipotezy „hubowi brakowało sygnału z własnych podstron".
2. Jeśli nie — zbadać, czemu hub z 91 ofertami przegrywa z własną ofertą na „geely preface".
   Kandydaci: hub przegrywa świeżością/konkretem (zdjęcia, cena egzemplarza), masa 32 ofert
   rozbija wybór, brak linków zewnętrznych (1 backlink vs 24 u TNT).
3. To jest **track HUBÓW**, nie ofert — huby stoją #1–2 na frazach „cena", więc obowiązuje
   baseline + próg rollback (`T-203-baseline-gsc-2026-07-13.md`).

---

## 3z. (pierwotny) PRIORYTET 2 — kanibalizacja broad między ofertami (rozstrzygnięcie kierunku)

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

**Dołączone do tego wątku (decyzja Janka 2026-07-16):** **T-199 resztka — „Prima Auto" → „Prima-Auto"**
w `renderMeta():914`. Dziś title kończy się `| Prima-Auto`, a meta description mówi
`Prima Auto — bezpośredni importer` — niespójność w obrębie JEDNEJ strony. Zysk SEO **zerowy**
(Google dopasowuje encję niezależnie od myślnika), robimy wyłącznie dlatego, że to jeden string
w tym samym pliku, co reszta 4b. **GBP i FB NIE ruszamy** — nazwa GBP z keywordem („Prima - Auto -
Import Samochodów z Chin") to zamknięta decyzja Janka ([[feedback_gbp_name_keyword]]), FB
(„PRIMA AUTO - Import aut Chiny Korea | Rzeszów") rządzi się swoim. Druga część T-199 (drugi H1
→ `div role=heading`) — **zrobiona 2026-07-16 w v0.33.26**.

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
