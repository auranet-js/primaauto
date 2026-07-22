# Sesja 2026-07-22 — Newsy auto-publikacja, audyt Google/schema, Tier 3 słownika (72/72), krytyka /wiki/, plan linkowania dwukierunkowego

> Kontynuacja T-214 (dział wiedzy). Poprzednia sesja: `docs/sesje/` brak wpisu (patrz auto-memory `project_dzial_wiedzy_watek_kontynuacyjny_2026_07_22.md` za 22.07 rano — Tier 2 kompletny, 62 hasła). Ta sesja = 22.07 popołudnie/wieczór.
> Commity: `38c3595` (newsy auto-publikacja), `c53e4a0` (Tier 3 słownika).

## Co zrobione

### 1. Newsy — auto-publikacja bez akceptacji mailem (decyzja Janka)

`news_daily.py` przebudowany: publikuje od razu (`--post_status=publish`), mail to już tylko digest z linkami do opublikowanego, nie przycisk „Opublikuj". Stary mechanizm tokenowy (`_kb_publish_token`, endpoint `class-asiaauto-kb-publish.php`) zostawiony jako martwy kod (nieużywany, nieszkodliwy).

**Dodatkowe zdjęcia z galerii źródła w treści** (nie tylko okładka): `extract_gallery_images()` obsługuje klasyczny `<img class="wp-image-N" src="...">` (CarNewsChina) ORAZ lazy-load `<picture class="wp-image-N">...<noscript><img src="..."></noscript></picture>` (CNEVPost — bez tego src to placeholder `data:image/svg`, nie prawdziwy URL). `pick_spaced()` dobiera do 2 zdjęć rozłożonych po całej galerii (nie pierwsze z brzegu — unika duplikatów z tego samego ujęcia). `insert_figures()` wstawia je na granicach akapitów.

**Kredyt zdjęć — tylko gdy źródło jawnie deklaruje** (`extract_verified_credit()`): dwa wzorce branżowe — (1) schema.org JSON-LD `ImageObject.caption` zawierający "Credit: X" (CarNewsChina), (2) klasyczny WP `<figcaption class="wp-caption-text">Credit: X</figcaption>` (CNEVPost). Bez potwierdzenia podpis jest neutralny „fot. {źródło}", NIGDY nie zgadujemy „materiały prasowe {marka}" bez dowodu — to była realna poprawka po tym, jak Janek złapał nadinterpretację (pierwsza wersja podpisywała WSZYSTKIE zdjęcia jako "materiały prasowe marka" bez weryfikacji).

`kb_lib.py`: nowy wspólny helper `download_webp(url, out_stub)` (pobierz + konwertuj ImageMagick), używany i dla okładki, i dla zdjęć w treści.

**Opublikowane dziś 3 newsy** (wszystkie zweryfikowane merytorycznie 1:1 ze źródłem — liczby, ceny, wymiary):
- https://primaauto.com.pl/aktualnosci/byd-qin-max-wnetrze-osiagi-i-ceny-nowego-sedana/
- https://primaauto.com.pl/aktualnosci/geely-galaxy-cruiser-700-terenowy-phev-z-moca-1128-km/
- https://primaauto.com.pl/aktualnosci/voyah-passion-s-rywal-xiaomi-yu7-w-przedsprzedazy-od-24-lipca/

Wszystkie 3 marki (BYD, Geely, Voyah) potwierdzone w ofercie przez `wp term list make` — nie zgadywane.

### 2. Audyt Google News / schema / GSC (dowód, nie założenia)

- **NewsArticle schema kompletna** (headline, datePublished/dateModified, author, publisher+logo, image 1200×675, mainEntityOfPage) — RankMath PRO, moduł Google News aktywny.
- **`news-sitemap.xml` istniał, poprawny wg protokołu (`<news:publication>`/`<news:publication_date>`/`<news:title>`), wpięty w `sitemap_index.xml`+`robots.txt`, ale NIGDY nie był jawnie zgłoszony w GSC.** Zgłoszony 22.07 (PUT Search Console API, status 204).
- Drobna usterka (nienaprawiona): logo Organization w schema ma 200×55px — poniżej typowego minimum (Google woli ≥60px wysokości).
- **Google Publisher Center** — NIE zrobione, wymaga loginu/decyzji Janka (rejestracja publikacji, branding). Google News od 2019 nie wymaga ręcznej akceptacji do samego pojawiania się.
- Indeksacja: newsy NIE są spięte z Indexing API (hook w `class-asiaauto-indexing.php` obsługuje tylko `listings`) — polegają na news-sitemap+crawl. Wczorajsze 2 newsy (Jetour, MG07) zaindeksowały się same w ~24h. Dzisiejsze 3 — „Google nieznany" w momencie sprawdzenia (świeże).
- Meta/H1/title sprawdzone na żywo (BYD Qin Max): 1×H1 zgodny z title, canonical self-ref, robots `index,follow,max-image-preview:large` — czyste.
- Skrypt audytowy: `tmp/gsc-check-news-2026-07-22.py` (URL Inspection + sitemaps list, wzorzec z istniejących `tmp/gsc-*.py`).

### 3. Ryzyko kanibalizacji news↔hub — realna, udokumentowana luka

**Zasada D2 z T-162** ("jedna fraza = jeden URL", `{model} cena/import/gdzie kupić → tylko hub`, gate GSC przed publikacją) jest jawnie zacytowana w planie T-214 jako obowiązująca „wszystkie sekcje" — ale **`news_daily.py` NIGDY jej nie implementował.**

Dzisiejsze 3 newsy nie kolidują (żaden z modeli — Qin Max, Geely Cruiser 700, Voyah Passion S — nie jest w naszej ofercie; sprawdzone przez `wp term list serie`), ale to był przypadek, nie zabezpieczenie. Realne ryzyko pojawi się przy newsie o modelu, który już sprzedajemy (np. facelift Qin L — mamy huby "Qin L DM-i" 21 aut, "Qin L EV" 10 aut).

**Zaprojektowany mechanizm (NIE canonical — zła treść na zła treść, Google może zignorować):**
1. Detekcja: dopasowanie tytułu kandydata do taksonomii `serie` (analogicznie do `match_our_offer()`, ale na poziomie modelu, nie marki).
2. Jeśli model w ofercie → prompt zmienia ramowanie z "cena/specyfikacja" (terytorium huba) na "wydarzenie/zmiana" + **link z anchor tekstem pieniężnym do HUBA MODELU** (nie tylko marki jak dziś).
3. Okresowy audyt post-publikacyjny w GSC (hub już ma historię zapytań, news nie ma na starcie) — porównanie wydajności, reakcja: wzmocnić linkowanie albo `noindex,follow` na słabszej stronie.

**NIE wdrożone — czeka na "rób".**

### 4. Tier 3 słownika — 72/72 haseł live

Dobrane **z realnych liczności w `_asiaauto_extra_prep`** (nie zgadywane) — metoda: klucze z próbki JSON minus 34 klucze już użyte w `term_keys` Tier1+2, COUNT() per kandydat w pełnej bazie.

10 nowych haseł (`wiki_tier3.json` + `wiki_tier3b.json`): materiały tapicerki (2483 aut), głos bez wybudzania (2494), rozpoznawanie twarzy (1706), oczyszczacz powietrza (1691), mapy HD (314), V2X (132), elektryczny spoiler (214), zawracanie w miejscu + crab walk kontekstowo (181, + Denza N8L/Z9 GT), lusterka kamerowe (74, bez zdjęcia — 0 pasujących ofert z galerią), wysuwany podnóżek (199).

**TPMS odrzucony** — zbyt generyczny (standard UE od 2014), nie pasuje do celu słownika ("co jest specyficznie chińskie/mniej znane polskiemu kupującemu").

**Gotcha (ważna lekcja):** `_asiaauto_extra_prep` ma luki na konkretnych partiach importu — Denza Z9 GT (listing 254260, sprawdzone bezpośrednio, 385 pól) w ogóle nie ma pola `tank_turn`, mimo że auto realnie ma tę architekturę (3 silniki, tryb torowy). **"0 w danych" ≠ "0 w rzeczywistości"** — przy rzadkich/topowych funkcjach trzeba sprawdzać krzyżowo z wiedzą o modelu, nie tylko ufać COUNT() z bazy. Janek złapał to na żywo, gdy pierwsza wersja hasła fałszywie twierdziła "nie mamy w ofercie".

FAQ = potwierdzone realne pytania z DataForSEO PAA (`people_also_ask`, SERP live advanced), nie synteza modelu — sprawdzone w kodzie `wiki_generate.py`.

### 5. Krytyka budowy `/wiki/` — 2 problemy, 1 naprawiony

1. **BUG naprawiony:** title/meta indeksu `/wiki/` = dosłowne „Słownik Archive - Prima-Auto..." (nietknięty fallback RankMath — `pt_asiaauto_wiki_archive_title` nigdy nie ustawiony, w przeciwieństwie do `pt_listings_archive_title`). Naprawa: `wp eval` + `update_option('rank-math-options-titles', ...)`, zweryfikowane na żywo.
2. **Brak breadcrumbs na stronach haseł** (zero `BreadcrumbList` w schema, zero widocznego HTML) — **NIE naprawione**, czeka na zgodę (edycja `single-asiaauto_wiki.php` = strefa ZAWSZE PYTAJ).

Co jest OK (żeby nie przesadzić w drugą stronę): schema `DefinedTerm`/`DefinedTermSet`+`FAQPage` poprawna, indeks skategoryzowany (6 grup: Bateria, Napęd, ADAS, Audio, Komfort, Normy), głębokość treści 4000-7700 znaków/hasło, czyste slugi.

### 6. Plan linkowania dwukierunkowego (sekcje „auta z tą funkcją")

To NIE nowy pomysł — jest w oryginalnym planie T-214 (sekcja A: "sekcje dynamiczne 'auta z tą technologią' — linkowanie dwukierunkowe"), tylko niezbudowany. **Silnik dopasowania już istnieje**: `wiki_photos.py` już dziś dopasowuje auto do hasła przez `term_keys` → `_asiaauto_extra_prep`, ale bierze `LIMIT 1` (jedno zdjęcie raz, statycznie wpisane do treści).

**Do zrobienia:** rozszerzyć do `COUNT(*)` + kilka kart ofert (reuse komponentu karty z hubów, T-187) + fallback na brand hub gdy 0 aut (nie pokazywać „0 aut"). **To musi być żywe zapytanie w szablonie** (`single-asiaauto_wiki.php`), nie batch script — inwentarz zmienia się codziennie. Czeka na zgodę (ZAWSZE PYTAJ template) + pokazanie diffu.

## Otwarte / następne ruchy (w kolejności zależności)

1. **Auto-linker** — filtr `asiaauto_autolink_html` już NAPISANY wcześniej (przed tą sesją), wpięty w 4 punkty renderu (techSpecs, equipment, opis oferty, wiki+FAQ hubów), czeka na pokazanie diffu i zgodę Janka (`class-asiaauto-single.php` = ZAWSZE PYTAJ). Teraz że słownik jest kompletny (72 hasła) — logiczny następny krok.
2. **Sekcje „auta z tą funkcją"** (linkowanie dwukierunkowe, punkt 6 wyżej) — projekt gotowy, czeka na implementację + diff.
3. **Breadcrumbs na `/wiki/*`** (punkt 5.2 wyżej) — mała, bezpieczna zmiana szablonu, czeka na zgodę.
4. **Gate antykanibalizacyjny w `news_daily.py`** (punkt 3 wyżej) — zaprojektowany, niewdrożony.
5. **Logo Organization schema** (200×55px, poniżej minimum) — kosmetyczne, niski priorytet.
6. **Google Publisher Center** — decyzja/akcja Janka, nie techniczna.
7. Stare z poprzedniej sesji, wciąż aktualne: konta prasowe BYD/Geely (Janek, ~10 min), F4 Blog/rankingi (T-162, w ogóle nieruszone).

## Komendy startowe

```bash
cd ~/projekty/primaauto/scripts/kb
python3 news_daily.py --limit 2                                          # dzienny bieg (cron 06:45, teraz auto-publikacja)
python3 wiki_generate.py --config wiki_tierN.json --limit 60 --no-mail   # kolejne hasła, jeśli będzie Tier 4
python3 wiki_photos.py --config wiki_tierN.json                          # zdjęcia z galerii
```

## Prompt kontynuacyjny (wklej na start nowego wątku)

> Kontynuacja T-214 (dział wiedzy Prima-Auto). Przeczytaj `docs/sesje/2026-07-22-newsy-autopublikacja-tier3-krytyka-linkowanie.md` — tam pełny stan. Skrót: newsy auto-publikują się od razu (bez maila akceptacyjnego), 3 opublikowane i zweryfikowane 22.07; słownik ma teraz 72/72 hasła (Tier 1+2+3 kompletne); naprawiony bug title/meta na `/wiki/` (RankMath archive). Otwarte, w kolejności: (1) auto-linker — kod już napisany, czeka na diff+zgodę do wgrania w `class-asiaauto-single.php`; (2) sekcje „auta z tą funkcją" pod hasłami — projekt gotowy (rozszerzyć `wiki_photos.py`'s dopasowanie term_keys→`_asiaauto_extra_prep` z LIMIT 1 na COUNT+kilka kart), wymaga edycji `single-asiaauto_wiki.php` (ZAWSZE PYTAJ, pokaż diff); (3) breadcrumbs na stronach haseł (brakuje całkiem — ani schema, ani HTML); (4) gate antykanibalizacyjny news↔hub w `news_daily.py` (zaprojektowany w sesji 22.07, niewdrożony — detekcja modelu z taksonomii `serie` przed generacją + zmiana ramowania promptu + link do huba modelu zamiast marki). Zapytaj, od czego zacząć.
