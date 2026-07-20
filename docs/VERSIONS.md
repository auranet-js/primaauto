# Historia wersji asiaauto-sync

## 0.33.38 — 2026-07-20 (przeglądarka „Przeglądaj Che168", GATED na js)

Etap 2 specu filtra modeli. Nowa podstrona pod Ogłoszeniami (`asiaauto-che168-browse`,
`class-asiaauto-admin-che168-browse.php`) — pozycja menu rejestrowana **tylko dla loginów
z `ASIAAUTO_CHE168_PREVIEW`** (Ruslan nie widzi). Filtry: marka (pełny słownik giełdy, 282+ marek),
chipy modeli (max 5 na zapytanie; nazwy kanoniczne, 🆕 bez huba, ⛔ na blackliście), rocznik od,
cena od, miasta (całe Chiny / nasza lista). Wyniki: karty ze zdjęciami (permanentne URL-e che168),
badge hub/orphan, „(w bazie)" dla już zaimportowanych; paginacja „Załaduj więcej". Podgląd = modal
(hub, wycena PLN `cena_koncowa`, VIN, wyposażenie, zdjęcia, warningi) + import per klik — oba przez
**istniejące** AJAX-y `asiaauto_che168_preview`/`asiaauto_che168_import` (ta sama ścieżka co „Dodaj
z Che168", strefa krucha nietknięta). Nowy AJAX `asiaauto_che168_browse` → `getOffers` server-side
(mark+model+year), miasta/cena filtrowane u nas. Smoke na żywym API: BYD Seal 14 ofert/strona,
filtr miast 5/20, gate js=tak / primaauto=nie.

**Hotfix zdjęć (ta sama wersja, po teście E2E w Chrome):** CDN Autohome (`2sc2.autoimg.cn`) tnie
hotlinki po nagłówku Referer → miniatury nie wstawały. Fix: karty i modal renderują `<img
referrerpolicy="no-referrer" loading="lazy">` zamiast CSS background-image. Po fixie 26/26 obrazków
załadowanych, 0 błędów (test E2E: strona → BYD → Szukaj → Podgląd z wyceną PLN). Przy okazji
usunięte `alert()`/`confirm()` (blokowały automatyzację; komunikaty w pasku statusu, import
potwierdzany dwuklikiem „Potwierdź import").

**Deep-link do pełnej analizy (feedback Janka):** modal podglądu w przeglądarce dostał przycisk
„📋 Pełna analiza (Dodaj z Che168) →" — otwiera stronę ręcznego importu z `?inner_id=` (prefill
numeru + automatyczny podgląd po DOMContentLoaded; wcześniejszy wariant odpalał `wp.ajax` przed
załadowaniem wp-util → ReferenceError, złapane w teście E2E). Test: browse → Podgląd → Pełna
analiza → strona „Import z Che168" sama pokazuje dry-run (hub, tytuł, taksonomie).

## 0.33.37 — 2026-07-20 (filtr modeli Che168 w konfiguratorze + domapowania, GATED na js)

Element T-186. Zakładka „Filtry importu" dostaje subtabs Dongchedi | Che168 (widoczne **tylko dla
loginów z `ASIAAUTO_CHE168_PREVIEW`** — faza testów, Ruslan nie widzi). Che168: marki z pełnego
słownika API (`getFilters`, nowa klasa `AsiaAuto_Che168_Dictionary`, transient 7 dni + „Odśwież"),
**blacklista modeli per marka** (`model_blacklist` w `asiaauto_import_config['che168']`), limity
seedowane kopią dongchedi (2024+, ≤40 tys. km, ≥85 tys. ¥, 31 miast, 47 marek, enabled=true).
Egzekwowanie: addytywny blok w `isAllowedByConfig()` (strefa krucha poza tym nietknięta); ręczny
import per numer omija blacklistę. **Domapowania:** +25 aliasów `che168-model-map` + 8 nowych modeli
`brand-mapping` v6.3 (Leapmotor T03, eπ007, WEY Gaoshan, Smart #1/#3, Lotus Eletre, Jetour Shanhai
T1, BAIC BJ30) → trafialność ofert 2024-2026 w huby: 61% → ~81% (próbka 3550 ofert / 46 marek).
Spec: `docs/superpowers/specs/2026-07-20-che168-model-filter-design.md`. Etap 2 (przeglądarka
ofert „Przeglądaj Che168") — zaakceptowany mockup, do wdrożenia po testach etapu 1.

## 0.33.36 — 2026-07-20 (lista zamówień: 20 → 30 pozycji na stronę)

Prośba Janka (+50%). Przy „Wszystkie" (102) daje 4 strony zamiast 6, przy domyślnym widoku
klientów (41) — 2 zamiast 3. Dotyczy też kart na telefonie.

## 0.33.35 — 2026-07-20 (rotacja: ochrona ogłoszeń z aktywnym zamówieniem)

**Problem:** rotacja kasowała trwale ogłoszenia, na które wskazywały niezakończone zamówienia —
27 zamówień (16 aktywnych) straciło zdjęcie i nazwę auta. Stary strażnik `isReserved()` sprawdzał tylko
meta rezerwacji, a rezerwację zakładają wyłącznie statusy z `LISTING_RESERVATION_MAP`
(`zarezerwowane`/`zakupione`/`w_drodze`/`na_placu`/`w_dostawie`). Zamówienie w **weryfikacji,
potwierdzone, umowa gotowa, podpisane** nie chroniło niczego.

**Fix:** `AsiaAuto_Rotation::listingsWithActiveOrders()` — jedno zapytanie na przebieg zwracające
ogłoszenia z zamówieniem w statusie innym niż `anulowane`/`odrzucone`; `deleteOldTrash()` je pomija
(z wpisem WARN w logu). Trafia 28 ogłoszeń, z czego 5 leżało w koszu/draftach gotowych do skasowania.

**Dlaczego NIE rozszerzyliśmy `LISTING_RESERVATION_MAP`** (pierwotny pomysł, odrzucony po analizie):
rezerwacja steruje też sprzedażą — `listingIsBlockedForOrders()` w kreatorze i API pokazuje
„Ten samochód jest już zarezerwowany". Dodanie tam `weryfikacji` blokowałoby auto dla wszystkich
pozostałych klientów od momentu pierwszego zamówienia, przy ~46% anulat po stronie klientów
(35 z 76). Chronimy przed **kasowaniem**, nie przed **sprzedażą**. `class-asiaauto-order.php` nietknięty.

**Test e2e na produkcji** (sztuczna para ogłoszenie+zamówienie, posprzątana po teście):
zamówienie aktywne → `Skipped permanent delete of post #387953 (in trash but has an active order)`,
ogłoszenie przetrwało; po zmianie statusu na `anulowane` → `Permanently deleted 1 trashed posts`.
Ochrona zwalnia się poprawnie. Kopia: `class-asiaauto-rotation.php.bak-2026-07-20`.

## 0.33.34 — 2026-07-20 (fix: biały ekran karty zamówienia dla usuniętych ogłoszeń)

**Objaw:** wejście w 27 zamówień (16 aktywnych) kończyło się „W witrynie wystąpił krytyczny błąd".

**Korzeń — błąd sprzed T-218, nie jego skutek** (identyczny kod w `*.bak-2026-07-20`):
`renderOrderCard()` robiło `$data['listing_id'] ? get_edit_post_link($id) : ''`. Gdy ogłoszenie zostało
usunięte przez rotację, `listing_id` nadal jest niezerowe, ale `get_edit_post_link()` zwraca **null**
(a `get_permalink()` **false**) → `renderCardListing(array, string, string)` rzucało `TypeError`.
T-218 tylko ujawniło problem: wcześniej te zamówienia trudno było znaleźć na liście.

**Fix:** rzutowanie na `string` + wyraźny komunikat w karcie („Ogłoszenie #X nie istnieje już w bazie —
usunięte przy rotacji ofert; dane zamówienia nienaruszone"). Auto pozostaje rozpoznawalne po
`_order_source_url` (link do Dongchedi), który zamówienie przechowuje.

**Znaleziona przy okazji luka systemowa (NIE naprawiona — wymaga decyzji, patrz QUEUE):**
`AsiaAuto_Rotation::deleteOldTrash()` chroni ogłoszenia przez `isReserved()`, ale rezerwację zakłada
tylko `LISTING_RESERVATION_MAP` = `zarezerwowane`/`zakupione`/`w_drodze`/`na_placu`/`w_dostawie`.
Zamówienia w **weryfikacji, potwierdzone, umowa gotowa, podpisane** nie rezerwują ogłoszenia →
po 30 dniach w koszu leci `wp_delete_post(force)`. Zamówienie traci zdjęcie i nazwę auta bezpowrotnie
(brak snapshotu tytułu w meta zamówienia).

## 0.33.33 — 2026-07-20 (T-218: przebudowa listy zamówień)

**Filtry.** Kafle statystyk usunięte (2 z 6 pokazywały stale 0 — `nowe`/`zarezerwowane` to statusy
przelotowe; „Na placu" nie należy do panelu zamówień). W ich miejsce rząd trzech łączących się filtrów:
typ (`Klientów` / `Wewnętrzne` / `Wszystkie`), status z pełnej listy 13 pozycji z licznikami,
checkbox „Ukryj anulowane". Domyślne wejście: **zamówienia klientów bez anulat — 41 zamiast 140 pozycji**
(anulowane + odrzucone = 38 z 140, po stronie klientów 35 z 76).

Liczniki przy typie są dynamiczne — reagują na checkbox (41/61/102 ↔ 76/64/140). Wybór statusu anulowanego
automatycznie wyłącza checkbox (inaczej wynik byłby zawsze pusty).

**Kolumny.** „Depozyt" + „CIF" scalone w jedną „Wpłaty" (znaczniki D/C, kwota w tooltipie) — obie kolumny
pokazywały w każdym wierszu tę samą wartość. Naprawiona szerokość ID (łamał numer na dwie linie).
Wiersze anulowane wyszarzone.

**Telefon (<782 px).** Tabela ustępuje kartom: zdjęcie, tytuł (przycięty do 2 linii), status, cena, klient,
wpłata. Zero przewijania w bok, kontrolki 44–56 px. Usunięte stare reguły ukrywające kolumny przez
`nth-child` (w tym jedna z błędnym komentarzem — opisywała ukrywanie daty, faktycznie ukrywała CIF).

**Przy okazji:** wersja CSS panelu była zahardkodowana na `0.30.5`, więc przeglądarki trzymały plik z kwietnia
mimo zmian. Podpięty `filemtime()` — każda edycja CSS sama unieważnia cache.

**Strefa krucha nietknięta:** `class-asiaauto-order.php` bez zmian (mtime 2026-06-02). Filtr typu oparty
o istniejący parametr `order_type` w `getOrders()`, „ukryj anulowane" o `status` jako tablicę (`IN`).
Zmienione pliki: `includes/class-asiaauto-order-admin.php`, `assets/css/asiaauto-order-admin.css`.
Kopie: `*.bak-2026-07-20`.

## 2026-07-18 — poprawka danych: `_asiaauto_primary_make_slug` na 6 hubach (bez zmian w kodzie)

**Objaw:** 6 hubów miało `<link rel="canonical">` wskazujący poza siebie, w tym 3 na hub **innego
samochodu**. Strona z ofertami oddawała swoje wyniki cudzej marce.

| hub | canonical przed | ofert | impr/90d |
|---|---|---|---|
| `/samochody/haval/h6/` | → `/hongqi/h6/` (inne auto) | 5 | 74 |
| `/samochody/wey/07/` | → `/avatr/07/` (inne auto) | 14 | 32 |
| `/samochody/gac/m8/` | → `/aito/m8/` (inne auto) | 11 | 25 |
| `/samochody/geely/a7-em/` | → `/galaxy/a7-em/` (301 z powrotem) | 38 | 12 |
| `/samochody/gac/hyper-a800/` | → `/gac-aion-hyper/…` (301 z powrotem) | 2 | 2 |
| `/samochody/chery-fulwin/fengyun-x3l/` | → `/chery-fengyun/…` (301 z powrotem) | 1 | 0 |

**Korzeń:** osad po backfillu z kwietnia 2026 — dopasowywał termy po samym slugu, bez uwzględnienia
rodzica, a slugi `h6`, `m8`, `07` istnieją pod kilkoma markami naraz. Importer robi to już poprawnie
od v0.33.16 (T-190, z parenta — `importer.php:702`), ale te 6 nie było od tego czasu reimportowanych
(feed stoi od 01.07; oferty modyfikowane ostatnio 30.04 / 08.06 / 30.06).

Pole steruje trzema rzeczami: URL hubu (`cpt.php::filterSerieTermLink`), canonical (`seo.php`)
i prefiksem marki w title (`hub-title-generator::ensureBrandPrefix`) — stąd tytuły
„Hongqi Haval H6", „Avatr WEY 07", „AITO GAC M8".

**Diagnoza — 3 niezależne dowody, że właściwy jest rodzic:** parent w taksonomii, marka
opublikowanych ofert pod termem, oraz `_serie_api_value` („Haval H6", „Trumpchi Xiangwang M8").
We wszystkich 6 przypadkach zgodne; odstawała wyłącznie meta.

**Zmiana:** `primary_make := slug rodzica` na termach 3381, 4398, 5388, 6539, 6849, 6945.
Backup + rollback SQL: `~/backups/primaauto/2026-07-18-primary-make-fix/`.

**Dlaczego trwałe:** każda oferta pod tymi termami ma **dokładnie jedną** markę, więc gdy feed ruszy,
`updateSerieprimaryMake()` zapisze tę samą wartość. Przekierowania są od tego pola niezależne —
używają `dominantMakeSlug()` liczonej z ofert (v0.32.70, komentarz *„bywa nieaktualna"*).

**Weryfikacja:** pełny re-skan 436 wariantów URL (każdy term pod adresem z mety ORAZ z rodzica) —
rozjazdy **6 → 1**. Pozostały `/avatr/07/` → `/wey/07/` jest poprawny: pod `avatr` nie ma termu „07",
to artefakt rewrite'u akceptującego dowolną markę w ścieżce (docelowo powinien dawać 301).
Tytuły: z 423 porównanych zmieniło się 5 — 4 nasze + 1 aktualizacja ceny (`chery-fulwin/a8l`).
Oferty nietknięte (własne permalinki, canonical self); zyskały poprawne linki wewnętrzne do hubów.
Zgłoszone do Indexing API: 6/6 OK (z rezerwy, lista w `tmp/primary-make-fix-SUBMITTED-2026-07-18.txt`).

⚠ Przy okazji odpalony ręcznie cron `asiaauto_regen_hub_titles_daily` — przeliczył 290 serie + 55 make,
choć zlecenie obejmowało 6 wartości. Realny efekt pokrył się z zakresem (5 zmian), ale następnym razem
regenerację trzeba rozdzielić od poprawki danych.

## 0.33.32 — 2026-07-18 (WYCOFANE tego samego dnia — nieudana próba odblokowania hubów serii)

**Kod wgrany, funkcja WYŁĄCZONA.** Stan robots identyczny jak w 0.33.31. Wpis zostaje jako
dokumentacja błędu, żeby nikt nie powtórzył tego kryterium.

**Punkt wyjścia:** 88 hubów serii z `count=0` siedziało na `noindex` (RankMath
`noindex_empty_taxonomies=on`), mimo że renderowały oferty i miały title „N sztuk".

**Co zrobiono:** `termQualifiesForIndex()` z kryterium „`asiaauto_wiki_body` ≥ 500 **LUB**
`_asiaauto_spec_snapshot` ≥ 200" + worek marek. Odblokowało 68 hubów.

**DLACZEGO TO BYŁO BŁĘDNE — weryfikacja po fakcie:**
- **67 z 68 odblokowanych termów NIGDY nie miało ani jednej oferty** — zero wierszy w
  `term_relationships` w całej historii. Tylko `ET5` (21) i `Galaxy E8` (6) miały cokolwiek.
- 30 z 68 nie miało nawet danych technicznych — kwalifikowały się wyłącznie opisem z LLM,
  dogenerowanym hurtem w kwietniu/maju do wszystkiego, co przyszło z feedu.
- Wśród odblokowanych: `Omoda` (to nazwa marki, nie model — realne są Omoda 5 / Omoda 9),
  `Hongqi HS7` (realny wariant to `HS7 PHEV`, count=10), `Jetour X90` (realne: `X90 PLUS`,
  `X90 PRO`), `Geely ICON` (0 ofert w całej historii).
- Efekt netto byłby odwrotny do zamierzonego: wpuszczenie do indeksu thin contentu i duplikatów
  kanibalizujących huby, które realnie rankują (`Tiggo 8` vs `Tiggo 8 Pro` / `Tiggo 8 PLUS`).

**Korzeń pomyłki:** przyjąłem, że skoro hub renderuje oferty i ma opis, to jest wartościowy.
Hub renderuje oferty przez fuzzy match na nazwie/rodzicu, więc „widzę towar na stronie" NIE dowodzi,
że term jest realnym modelem. Dowodem jest historia `term_relationships` — i tej nie sprawdziłem
przed wdrożeniem, tylko po. Treść jest najsłabszym możliwym kryterium, bo opisy generowano hurtem.

**Warunki poprawnego odblokowania (na przyszłość):** historia ofert > 0 **ORAZ** dane techniczne
**ORAZ** dedupe wariantów w taksonomii. Punkt trzeci to osobna robota, nie filtr robots.

**Nie zgłoszono niczego do Indexing API** — pula czekała na decyzję i nie została użyta.
Sitemapa serii nietknięta przez cały epizod: 297.

**Sitemapa świadomie NIE ruszana.** RankMath wycina `count=0` już na poziomie query (`hide_empty`),
więc filtr `rank_math/sitemap/entry` ich nie widzi. Próba włączenia `tax_serie_include_empty`
wpuszcza ~2700 termów i przy N+1 na term_meta generacja się nie domyka — sitemapa serii spadła
297→63. **Cofnięte**, stan przywrócony. Trwałe wejście do sitemapy = osobny task (wymaga batchowania
meta). Odblokowane huby idą do Google przez `~/bin/index-submit`; crawl i tak je odwiedza —
mediana `lastCrawl` na próbce 48 hubów to 4 dni.

## 0.33.31 — 2026-07-16 (T-213: leasing w pasku zaufania + skrót treści)

Treść paska `trustLine()` po korekcie Janka:
> Cena zawiera cło, VAT, akcyzę, transport, odprawę celną, homologację i przygotowanie.
> **Możliwy leasing.**

- **Usunięte:** „— bez dopłat na odbiorze" (decyzja Janka).
- **Dodane:** „Możliwy leasing." (`.aa-trustline__fin`, `--primary` + bold).

**Zmiany:** `class-asiaauto-single.php` (`trustLine()`), `assets/css/asiaauto-single.css`
(`.aa-trustline__fin`). Smoke: treść potwierdzona na produkcji.

⚠️ **Dopisek o leasingu wyprzedza stan T-113** (plan: **GATED na partnerze finansującym**).
Otwarte u Ruslana: czy jest partner i **czy finansuje auto importowane z Chin PRZED rejestracją
w PL** (plan T-113: „kluczowe pytanie i realne ryzyko"). Jest na 3 058 ofertach — przy negatywnej
odpowiedzi do zdjęcia lub przeformułowania.

## 0.33.30 — 2026-07-16 (T-213 resztka: zdanie zaufania na ofercie)

**Wariant B** (decyzja Janka 2026-07-14). Wariant A (pełne rozbicie kosztów) odrzucony: konkurent
tym rozbiciem **zaniża podatki i ukrywa marżę w opłatach**, a wersja uczciwa **odsłaniałaby naszą
marżę** i nie dawałaby ani jednego wejścia z Google.

**Treść:** „Cena zawiera cło, VAT, akcyzę, transport, odprawę celną, homologację i przygotowanie —
**bez dopłat na odbiorze**."

**Umiejscowienie: między `keySpecs()` a `uspStrip()`** (~13% strony) — wariant C z mockupu
`primaauto-t213-zdanie-zaufania-mockup-2026-07-16.html`. Powód: wysoko (tuż po cenie i danych),
czyta się jak zdanie, a nie jak punkt listy; biały box z czerwoną krawędzią odcina się od
czerwonego USP stripa.

**Odrzucone warianty:**
- **pod ceną w sticky headzie** — cofałoby v0.33.26 (walka o kompakt: tytuł 2 linie, target 44px).
  Zdanie ma ~110 znaków = 3 linie na 375px, pasek rósłby ~2×. Sticky ma być skrótem, nie akapitem.
- **podpis w kolumnie „Dlaczego my"** — ginie w czerwieni jako 13. pozycja listy haseł.

**Zmiany:**
- `class-asiaauto-single.php` — nowa `trustLine()`, wpięta w `render()` między keySpecs a uspStrip
- `assets/css/asiaauto-single.css` — `.aa-trustline` (biały box, `border-left: 3px solid var(--accent)`, ikona tarczy SVG)

**Kontekst:** `uspStrip()` ma hasło **„Transparentna cena"** — obietnicę bez pokrycia. To zdanie
jest jej pokryciem. ⚠️ Do rozważenia: hasło pada teraz dwa razy pod rząd (pasek + punkt w USP) —
ewentualnie usunąć je z USP.

**⚠️ Link do kalkulatora NIE dopięty** — kalkulator (T-213 pkt 1, osobna strona) **nie istnieje**.
`TODO` w kodzie; dopięcie to 1 linia, gdy powstanie.

**Smoke:** 3 paliwa (PHEV/benzyna/EV) → 1 wystąpienie każde. Kolejność `trustline` przed `usp-strip`
potwierdzona. CSS pluginu bustuje się sam (`filemtime`, `ver=1784216574`) — theme nietknięty,
więc `PRIMAAUTO_THEME_VERSION` bez zmian.

**Backupy:** `class-asiaauto-single.php.bak-2026-07-16-t213`, `asiaauto-single.css.bak-2026-07-16-t213`

## 0.33.29 — 2026-07-16 (T-187 fix: blok „Inne egzemplarze" na ofertach BEZ extra_prep)

**Bug (znalazł Janek, przykład `/oferta/hongqi-h9-2024-387815/`):** blok wpięty w `renderTechSpecs()`
dziedziczył jej early return — `if (empty($ep)) return '';` → **139 ofert (4,5%) nie dostawało bloku
w ogóle**. Blok nigdy nie zależał od `extra_prep` (potrzebuje tylko taksonomii `serie`); to był
skutek uboczny miejsca wpięcia.

**Fix (`class-asiaauto-shortcodes.php`, 2 linie):** zamiast pustki — sam blok.
```php
if (empty($ep))       return $this->otherUnitsBlock($post_id);
if (empty($sections)) return $this->otherUnitsBlock($post_id);
```

**Smoke:** `/oferta/hongqi-h9-2024-387815/` (0 sekcji tech) → „Inne egzemplarze Hongqi H9 (5)",
4 karty, CTA OK. `/oferta/aito-m9-2024-217558/` (6 sekcji) → blok po 2., **1 wystąpienie** (brak
duplikacji). Regresja hubów/homepage/katalogu: bez zmian (blok nie odpala się poza ofertą).

### Dlaczego 139 ofert nie ma extra_prep — diagnoza (NIE naprawiane, decyzja Janka)

Hipoteza „Ruslan dodawał ręcznie, powinno się podłączyć" **obalona danymi**: ręcznych jest **6**,
z importu **133** (mają `_asiaauto_inner_id`), wszystkie z lipca 2026.

Przyczyna = **awaria dostawcy auto-api od 01.07** ([[project_dongchedi_feed_frozen_2026_07_07]]):
`/changes` daje dane częściowe BEZ extra_prep, pełną specyfikację dokłada `getOffer()` per oferta;
gdy `getOffer()` pada → `importWithFullData` świadomie zapisuje partial + `WARN: getOffer failed…
using partial data`. Kod syncu nietknięty od 16.05 — nasz kod OK, wina źródła.

**Decyzja Janka (2026-07-16, podtrzymana z 07-07): NIE backfillować teraz** — najpierw wyświetlanie
bloku, extra_prep osobno. Dane odzyskiwalne (`getOffer()` już odpowiada → re-sync uzupełni).

## 0.33.28 — 2026-07-16 (T-187: blok „Inne egzemplarze {Model}" na stronie oferty)

**Po co:** klient na ofercie nie wiedział, że ten sam model stoi u nas w innych sztukach, często
taniej — musiał wracać do katalogu i szukać ręcznie. 2 526 z 3 058 ofert (83%) dzieli identyczny
`post_title` z inną ofertą (499 grup; AITO M9 ×133, Voyah Dream ×39). Cel Janka: „żeby ludzie
widzieli, że to nie jedyny egzemplarz, jaki mamy".

**Zero nowego kodu renderującego — reuse istniejącego komponentu** (decyzja Janka: „mamy już te
metody na stronie głównej i hubach, więc tylko odpytanie konkretnych ofert, nie pisz od nowa").

### Zmiany

**`class-asiaauto-brand-hub.php`** — 4 addytywne zmiany w `[asiaauto_hub_listings]`, wszystkie
z pustymi domyślnymi → **hub i homepage bez zmian**:
- nowe atrybuty: `make`, `serie` (fallback, gdy brak query var — poza hubem ich nie ma),
  `exclude` (CSV → `post__not_in`), `orderby` (`price` = cena rosnąco)
- `renderListingsCompact()` + parametry `$exclude`, `$orderby`

**`class-asiaauto-shortcodes.php`**:
- nowa `otherUnitsBlock(int $post_id)` — pobiera `make`/`serie` posta, guard `count-1 < 1`,
  woła `do_shortcode('[asiaauto_hub_listings n=4 …]')`
- `renderTechSpecs()` — licznik wyrenderowanych sekcji, wstawka **po 2.**, fallback na koniec

### Decyzje

- **Umiejscowienie: po 2. wyrenderowanej sekcji technicznej** (~39% strony) — pomysł Janka:
  „klient mobile już wie, że przewija zestawy danych, więc kolejny zestaw pasuje". Janek:
  „to nie ma znaczenia, bo jak już to mamy gotowe, to zawsze możemy przenieść".
- 🔴 **Kotwica po INDEKSIE, nie po nazwie sekcji** — „Układ elektryczny" nie istnieje dla ~24%
  katalogu (spalinowe). Potwierdzone smoke'em: EV ma 5 sekcji, spalinowe 6 — blok i tak po 2.
- **H2 = `Inne egzemplarze {serieAnchor} ({n})`** — niesie frazę `{marka} {model}`, ta sama nazwa
  co breadcrumb i sticky navrow (v0.33.23).
- **CTA → zafiltrowany katalog** `/samochody/?marka=X&model=Y`, **nie hub**. Powód (dane, nie opinia):
  hub pokazuje tylko ~11 kart przy 133 sztukach AITO M9, a **hub sam linkuje „Wszystkie (133) →"
  właśnie na filtr** — powtarzamy istniejący wzorzec. Filtr = `noindex, follow` (equity przepływa).
  Do huba klient ma już dwie drogi z góry strony (breadcrumb + navrow), trzecia byłaby powtórzeniem.
- **Sort: cena rosnąco** — sens konwersyjny bloku to „jest taniej".

### Smoke (po deployu)

| paliwo | oferta | blok | pozycja |
|---|---|---|---|
| EV | BYD Han EV | „Inne egzemplarze BYD Han EV (8)" | po 2 z 5 sekcji |
| benzyna | Hongqi H5 | „(93)" | po 2 z 6 |
| PHEV | Denza Z9 DM-i | „(14)" | po 2 z 6 |
| AITO M9 | 4 karty, ceny 283→297→302→304 tys. | cena rosnąco OK | CTA → `?marka=aito&model=m9` |

- **Guard**: oferta-unikat (`serie.count=1`) → 0 wystąpień bloku, brak pustej ramki.
- **`post__not_in`**: własny URL nie występuje w bloku (tylko oembed/share).
- **Regresja**: homepage, katalog (s1/s3/filtr), hub-zeekr-8x, hub-aito-m9, hub-denza-d9 —
  **HTML identyczny co do znaku** vs baseline sprzed zmiany.

**Backupy:** `class-asiaauto-brand-hub.php.bak-2026-07-16-t187`, `class-asiaauto-shortcodes.php.bak-2026-07-16-t187`

### 🐛 Znalezisko poboczne (NIE naprawiane) — niestabilne sortowanie kart marek

Przy weryfikacji regresji wyszło, że `aa-brand-card` („modele marki" na hubach) ma **losową
kolejność między pobraniami** — dwa `curl` pod rząd, **tym samym kodem**, dają 8 różnic
(hub-hongqi-h9, hubmarka-zeekr/geely). Prawdopodobnie `ORDER BY count` bez tie-breakera →
MySQL zwraca dowolną kolejność przy równych wartościach. **To nie jest regresja T-187** —
potwierdzone testem `n1 vs n2` na niezmienionym kodzie. Karty ofert (`aa-home__car`) są stabilne.
Skutek: diff HTML hubów jest niewiarygodny dla sekcji marek. Do rozważenia: tie-breaker po `term_id`.

## 0.33.27 — 2026-07-16 (T-211 ⚡2: kolor w schemacie ofert)

**Geneza:** wątek 4b. Blok Schema.org w `renderMeta()` pytał o taksonomię `color`, która
**nie istnieje** — w bazie są `exterior-color` (13 termów) i `interior-color` (13). Wyrażenie
zawsze zwracało `''`, a `array_filter` wycinał puste pole → **kolor nie trafiał do schematu
żadnej z 3 058 ofert**. Reszta pluginu znała poprawną nazwę (linia 136 używa `exterior-color`
od dawna) — to była literówka w jednym miejscu, nie świadomy wybór.

**Zmiana (`class-asiaauto-single.php:1047`, 1 linia):**
- `get_the_terms($pid, 'color')` → `get_the_terms($pid, 'exterior-color')`

**Pokrycie:** 2 906 z 3 058 opublikowanych ofert (95%) ma przypisany `exterior-color`.
Rozkład: Czarny 807, Biały 540, Ciemnoszary 423, Srebrny 347, Niebieski 296, Zielony 213,
Fioletowy 129, Czerwony 65, pozostałe <35.

**Smoke (3 paliwa, po deployu):** EV `byd-han-ev-2024-186099` → „Ciemnoszary";
PHEV `denza-z9-dm-2024-94535` → „Ciemnoszary"; benzyna `hongqi-h5-2025-186609` → „Czarny".
Pozostałe pola (`fuelType`, `vehicleTransmission`, `itemCondition`) bez zmian.

**Backup:** `class-asiaauto-single.php.bak-2026-07-16-t211-color`

### Ustalenia analityczne wątku 4b (bez zmian w kodzie)

- **Hipoteza „multi-type [Product, Car] blokuje liczenie ofert jako Product" — OBALONA.**
  URL Inspection API (3 huby + 4 oferty): **wszystkie PASS jako „Opisy produktów"**, oferty
  również, z nazwą wersyjną. Multi-type nie przeszkadza. Nie ma tu czego naprawiać.
- **„79 opisów produktów = tylko huby" — artefakt raportu, nie bug.** Raport rich resultów
  pokazuje próbkę URL-i i liczy tylko przecrawlowane. Huby są stabilne, oferty rotują
  (część URL-i z GSC jest martwa, 301 na hub).
- **Huby jako Product + AggregateOffer (lowPrice/highPrice) = poprawny, celowy wzorzec.**
  Daje „od X zł" w SERP na frazach „cena", gdzie huby stoją #1–2. Zostawiamy.
- **ZAD.12/13 („utracone pola schema") — FAŁSZYWY ALARM z nieaktualnego backlogu.**
  `vehicleEngine`, `vehicleTransmission`, `driveWheelConfiguration`, `itemCondition`
  oraz `OfferShippingDetails` **są w kodzie żywym i lecą na produkcję** (potwierdzone curlem).
  Backlog opisywał funkcję `schema()` (linia 632), która jest **martwym kodem** — nikt jej
  nie woła (komentarz w 1043 stwierdza to wprost: Elementor używa pojedynczych shortcodów).
- **WARNING GSC „brak review / aggregateRating"** na hubach i ofertach — pola opcjonalne.
  **Nie dodajemy** — nie mamy prawdziwych recenzji, fabrykowanie = ryzyko manual action.

### T-199 resztka „Prima Auto" → „Prima-Auto" — ODRZUCONE na danych (decyzja Janka)

Punkt 4 promptu 4b zakładał ujednolicenie zapisu. **Nie robimy.** Powody:
- **Myślnik jest dla Google separatorem słów, nie łącznikiem** — `Prima-Auto` tokenizuje się
  jako „prima"+„auto". Zapis ze spacją i z myślnikiem są wyszukiwarkowo równoważne.
- **Dowód empiryczny (GSC 90d, kliki brandowe):** `prima auto` (ze spacją) **343**,
  `primaauto` 34, `prima-auto` 13. Zapytanie ze spacją rankuje **poz. 2,7** („prima auto
  rzeszów" — 1,3) **mimo** że strona wszędzie pisze `Prima-Auto`. Gdyby myślnik sklejał
  w jeden token, tych 343 klików by nie było.
- **Wniosek:** zysk zerowy w obie strony; to wybór brandingowy, nie SEO. Janek preferuje
  „Prima Auto" (2 encje) — desc już tak mówi, więc **najtańszy ruch to żaden**.
  Niespójność title (`| Prima-Auto`) ↔ desc (`Prima Auto`) **zostawiona świadomie**.
- **Unikać:** `Prima - Auto` ze spacjami wokół myślnika (czytane jak separator tytułu →
  „Prima" + tagline „Auto").
- **NIE odwracać kierunku na całości** (blogname, schema `seller.name`, title 3 056 ofert)
  — duża zmiana bez zysku, ruszyłaby title w trakcie pomiaru do 27.07.
- Znalezisko poboczne: „Prima Auto" występuje w **12 miejscach**, nie w jednym (jak mówił
  prompt) — 10 z nich to szablony **title** (686-695), fallback aktywny gdy `offerTitleV2`
  wyłączone. Nie ruszane. Do rozstrzygnięcia po pomiarze 27.07, jeśli w ogóle.

## 0.33.26 — 2026-07-16 (T-203: UX/dostępność sticky-heada + jeden H1 + desktop back→hub)

**Geneza:** zrzut Janka z telefonu. „Trudno trafić palcem" — link „← {model}" miał `font-size: 12.5px`
bez paddingu, czyli **target ~16px**, poniżej minimum WCAG 2.2 SC 2.5.8 (24×24) i daleko od
Apple HIG (44). Auranet robi audyty EAA/WCAG — tym bardziej nie u nas.

**Zmiany (`asiaauto-single.css` + `class-asiaauto-single.php`):**
- **Target 44px** — nakładka `::after { inset: -14px -8px }` na `.aa-single__hubback`.
  Palec dostaje 44px, layout **nie rośnie o piksel** (link zostaje w linii z badge'ami). Font 12.5→13px.
- **Tytuł: 2 linie** (`-webkit-line-clamp: 2`) zamiast `nowrap`+ellipsis, 15→16px. **SEO bez zmian**
  — ellipsis był czysto wizualny, pełny `post_title` zawsze był w DOM i Google go czytał. Ale przy
  20+ egzemplarzach tego samego modelu **wersja** jest jedynym wyróżnikiem — i to ona wypadała
  poza ellipsis („Xiaomi YU7 Max 4WD…"). Czysta decyzja UX/CTR, nie rankingowa.
- **Cena 18→22px**, netto 12→13px, `z VAT` jako dopisek (`.aa-single__price-vat`, `.55em`, szary).
  Cena > tytuł **świadomie** — klient zna model (z listy/H1/linku obok), przychodzi po cenę; wzorzec mobile.de.
- **Pastylki**: padding 4/10→6/10, font 12→12.5px.
- **pkt 1 — JEDEN H1**: `stickyHead()` dostał `bool $as_h1`. Kopia **mobilna** = `<h1>`,
  desktopowa (sidebar) = `<div role="heading" aria-level="1">`. Powód: Google indeksuje mobile-first,
  a kopia nieaktywna ma `display:none` — czyli wcześniej JEDEN z dwóch H1 był zawsze schowany
  przed indekserem. Zweryfikowane: `<h1>` = 1, `role="heading"` = 1.
- **Desktop `.aa-single__back`**: „Wróć do wyników" → `/samochody/` (cały katalog 3 000 aut = donikąd
  konkretnie) → **„← Wszystkie oferty {model}"** → `hub#oferty`. Ta sama zasada co na mobile,
  ten sam `serieAnchor()`. Fallback na /samochody/ gdy oferta bez taksonomii serie.
  Target: `.aa-single__back` miał już `padding: 14px 0` = 44px, bez zmian.

---

## 0.33.25 — 2026-07-16 (T-203: kotwica #oferty na hubie + cache-bust themu)

**Po co:** link „← {model}" z oferty rzucał klienta na górę hubu (H1 + lead + wiki + FAQ), a on
przychodzi **z oferty tego modelu** — model zna, chce zobaczyć inne egzemplarze.

- **`[asiaauto_hub_listings]` + atrybut `anchor`** (domyślnie pusty → pozostałe użycia, m.in. homepage,
  bez zmian). Renderuje `id` na `.aa-hub__latest-wrap`.
- **`taxonomy-serie.php`**: bar ofert dostaje `anchor="oferty"`. Na hubie są 4 takie bary
  (oferty / w drodze / na placu) — **id dostaje tylko jeden**, zweryfikowane.
- **`hubBackLink()`**: URL + `#oferty`.
- **`hub.css`**: `scroll-margin-top: calc(var(--header-h) + 40px)` (+86px dla `body.admin-bar`) —
  bez tego nagłówek „Oferty {model}" wlatuje pod sticky `.pa-header`.
- **SEO bez zmian**: Google ignoruje fragment przy indeksacji (`/samochody/zeekr/8x/#oferty`
  = ten sam URL), equity płynie do hubu tak samo. Fragment działa tylko u użytkownika.

**⚠️ GOTCHA (kosztowała pół godziny): theme NIE ma cache-bustingu po `filemtime()`** — style idą
z hardcodowanej stałej `PRIMAAUTO_THEME_VERSION` w `functions.php` (`hub.css?ver=1.0.8`).
Zmiana CSS bez bumpu = przeglądarka serwuje starą kopię i zmiany „nie działają". Bump 1.0.8→**1.0.9**.
**Każda zmiana w `themes/primaauto2026/assets/css/*` wymaga bumpu tej stałej.** Plugin busta się sam.

---

## 0.33.24 — 2026-07-16 (T-203: link do hubu w sticky navrow + „z VAT")

**Pomysł Janka:** wstawić link do hubu w tę samą linię, co pastylki „Hybryda"/„W drodze" — wtedy
jedzie ze sticky-headem (widoczny przez CAŁY scroll) i **nie kosztuje ani piksela wysokości**,
bo rząd z badge'ami i tak tam był. Lepsze niż breadcrumb jako osobna linia (0.33.23) — na mobile
nad tytułem jest ciasno.

- **`hubBackLink()`** (nowa) — anchor z `AsiaAuto_Shortcodes::serieAnchor()`, ta sama funkcja co
  breadcrumb i JSON-LD, żeby trzy miejsca nie mówiły trzech różnych rzeczy.
- **`.aa-single__navrow`** — flex `[← {model}]` + `[badge'y]`; `min-width: 0` + `ellipsis` na spanie,
  więc długie nazwy („BYD Leopard 5 (Denza B5)") skracają się zamiast rozpychać badge.
  **Tylko `--mobile`** — desktop ma pełny breadcrumb, drugi link byłby duplikatem i rozjechałby sidebar.
- **`.aa-single__sticky-back` usunięta z markupu** (prowadziła do `/samochody/`).
  `grid-template-columns: 32px 1fr` → `1fr` — **tytuł odzyskał 42px**.
- **Breadcrumb `<nav>` z powrotem `display:none` na mobile** (cofnięte z 0.33.23) — rolę przejął
  navrow: ten sam URL i ten sam anchor, więc bez rozjazdu; `<nav>` zostaje w DOM + JSON-LD dla SERP.
- **Cena: `246 000 PLN z VAT`** (`.aa-single__price-vat`) — prośba Janka.

---

## 0.33.23 — 2026-07-16 (T-203 pkt 7+8: breadcrumb kompakt na mobile + anchor pełną nazwą modelu)

**Po co:** 3 056 ofert linkowało do hubów breadcrumbem ukrytym na mobile (`display:none`
w `@media max-width:768px`) — a mobile to **79,6% sesji** (GA4 90d, memory
`reference_mobile_share_and_offers_are_conversion_pages`). Do tego anchor był surowym
`$serie->name`, czyli **258 z 302 modeli (2 908 ofert) linkowało do hubu napisem bez marki** („8X").
Hub walczy o „zeekr 8x", a dostawał od 3 000 własnych podstron link „8X".
Komentarz w CSS (`Hide breadcrumb … SEO intact`) był **mylący**: JSON-LD ratuje wygląd ścieżki
w SERP, nie link equity.

**`AsiaAuto_Shortcodes::serieAnchor()`** (nowa, `public static` — wołana z `renderBreadcrumb()`
i z JSON-LD BreadcrumbList w `class-asiaauto-single.php:1061`, żeby się nie rozjechały).
Baza = `_serie_full_title` (kuratorowane: bez nawiasów, bez dubletów marki, case marki,
encje) + 3 korekty. **Zasada:** anchor prowadzi do HUBA, więc niesie frazę huba (`{model}`);
frazę wersyjną niesie title/H1 oferty (decyzja Janka: „zeekr 8x → hub, zeekr 8x ultra → oferta").
1. **zdejmij** napęd nieobecny w nazwie termu → `Zeekr 8X PHEV` = **`Zeekr 8X`**,
2. **dopnij** napęd obecny w nazwie termu, a zgubiony przez full_title → `BYD Han` = **`BYD Han DM-i`**,
3. **alias marki** (lustro `V3_BRAND_ALIAS`) → `Beijing 212 T01` = **`BAW 212 T01`**.
Fallback dla 35 serii bez full_title: make+name z guardem antydubletowym („IM LS7" vs „IM Motors").

**BUG wyłapany na symulacji 100 serii — bez kroku 2 sami tworzylibyśmy kanibalizację:**
`Sealion 5 DM` i `Sealion 5 EV` to **dwa różne huby**, a `full_title` dawał obu identyczne
„BYD Sealion 5". Kolizje anchora na całej puli: **1 → 0**. Symulacja:
`auratest .../primaauto-t203-sym-anchor-100-2026-07-16.html`.

**Breadcrumb kompakt (`asiaauto-single.css`, `@media max-width:768px`):** breadcrumb odsłonięty,
ale zredukowany do JEDNEGO poziomu — link do hubu modelu (`--root`/`--make`/`--current`/`--sep`
chowane, `--serie` z prefiksem `←`). **`aa-single__sticky-back` usunięta z mobile** (prowadziła do
`/samochody/`, czyli do całego katalogu; nawigację przejął breadcrumb → hub modelu).
`grid-template-columns: 32px 1fr` → `1fr` — **tytuł odzyskał 42px** (mniej ellipsis).
`margin-top: -16px` → `0` (nad sticky-head jest teraz breadcrumb; -16px wciągałby go pod spód).
Hub marki nie traci: dostaje link z każdego z 302 hubów modeli (`taxonomy-serie.php:71-95`).

**Huby NIETKNIĘTE** — zweryfikowane grepem przed zmianą (`titleBaseV3`/`v4*`/`v3BrandPrefix`
tylko w `class-asiaauto-single.php`; `ensureBrandPrefix` tylko w `class-asiaauto-hub-title-generator.php`;
`[asiaauto_tech_specs]`/`[asiaauto_equipment]` wołane wyłącznie z `class-asiaauto-single.php:93-94`)
i po deployu na żywo (title/H1 `/zeekr/8x/`, `/byd/han-dm-i/`, `/denza/n8l-dm/` bez zmian).

**Znalezisko uboczne → `docs/seo/znaleziska-huby-2026-07-14.md` Z4:** ten sam defekt
`_serie_full_title` **już produkuje zduplikowane title hubów** — `/byd/sealion-5-dm/` i
`/byd/sealion-5-ev/` mają IDENTYCZNY title „BYD Sealion 5"; `/byd/han-dm-i/` = „BYD Han"
(bez frazy „byd han dm-i"). 13 serii dotkniętych. Anchor jest teraz precyzyjniejszy niż hub,
do którego prowadzi. Naprawa hubów = osobna decyzja (baseline + próg rollback).

**Smoke:** 3 oferty różnych paliw (PHEV/EREV/benzyna) HTTP 200, anchor i JSON-LD BreadcrumbList
zgodne; 6 kluczowych serii przez `serieAnchor()` na produkcji = wartości z symulacji.
Backupy: `.bak-2026-07-16-t203-anchor` (×2), `.bak-2026-07-16-t203-bc` (CSS).

**NIEZROBIONE z tego zakresu:** pkt 1 (drugi H1 → `div role=heading`; `stickyHead()` wołany
2× — `:85` mobile i `:314` w `sidebar()`) — zaakceptowany, ale test mobile-first→desktop
nie wykonany (Chrome nie dał mobilnego viewportu). Pkt 2 (NAP „Prima Auto"→„Prima-Auto"
w `renderMeta()`) — do decyzji, zysk SEO zerowy. T-187 (box „inne oferty tego modelu")
— świadomie osobno, wymaga T-212.

---

## 0.33.22 — 2026-07-14 (T-203 v4: hoist wersji tuż za nazwę modelu)

**Po co:** sam przerzut rocznika (0.33.21) nie wystarczył — feed wstawia napęd/baterię PRZED wersję,
więc fraza dalej się rozjeżdżała: `Xiaomi YU7 4WD Max` nie zawiera ciągu „xiaomi yu7 max" (6 600/mc),
`Zeekr 8X 55kWh Ultra` nie zawiera „zeekr 8x ultra" (2 900/mc). Symulacja 120 ofert zaakceptowana
(`auratest .../primaauto-t203-v4-hoist-symulacja-2026-07-14.html`).

**`v4HoistTrim()`** — token wersji przesuwany tuż za nazwę modelu. **Kotwicą jest nazwa termu `serie`**
(`YU7`, `8X`, `L9`, `Galaxy M9`, `212 T01`), nie heurystyka po liczbie tokenów — wiemy dokładnie,
gdzie kończy się model. Wersja jedzie razem z doklejonym „Edition"/„Version", żeby nie zostawiać sierot.
Efekt: `Xiaomi YU7 Max 4WD 2025`, `Zeekr 8X Ultra 55kWh 2026`, `Avatr 11 Max Facelift RWD EREV 2025`,
`AITO M9 Ultra EREV 6-osobowy 2025`, `BYD Leopard 5 (Denza B5) Ultra 125KM 2025`.

**`v4FindTrim()` — BUG wyłapany na smoke-teście, NIE puszczać naiwnego „pierwsze trafienie":**
`Xiaomi YU7 2025 AWD **Ultra Long Range** … **Max Version**` → naiwny hoist brał „Ultra" (opis
zasięgu baterii!) i produkował **`Xiaomi YU7 Ultra` — wersję, która NIE ISTNIEJE** (YU7 ma
Standard/Pro/Max). Zmyślony wariant jest gorszy niż brak hoistu. Kolejność wyboru jest teraz jawna:
1. trim + „Edition"/„Version" (najpewniejszy marker realnego wariantu: „Max Version"),
2. samotny trim, ale NIE taki, po którym idzie „(Long) Range" ani myślnik („Ultra-long"),
3. nic — tytuł zostaje nietknięty.

**Świadomy kompromis:** `Xiaomi SU7 RWD **Standard** Long Range Edition` NIE dostaje hoistu, bo
strażnik odrzuca każdy trim przed „Long Range" — a „Ultra Long Range" (zasięg) i „Standard Long
Range" (wersja) są strukturalnie nieodróżnialne bez słownika wersji per model. Tracimy przyleganie
na „su7 standard", ale nie ryzykujemy powtórki z „YU7 Ultra". Audyt całej bazy (765 par
serie→przypisana wersja) — zero wymyślonych wariantów.

**Zakres:** WYŁĄCZNIE oferty (`is_singular('listings')`). Huby jadą osobnym generatorem
(`class-asiaauto-hub-title-generator.php`) i są NIETKNIĘTE — zweryfikowane na żywo po deployu.

**⚠️ Kanibalizacja NIE jest rozwiązana i hoist ją ZAOSTRZA.** Wcześniej 30 ofert YU7 miało
`Xiaomi YU7 4WD Max` (żadna nie pasowała exact do „xiaomi yu7 max"); teraz każda z 30 zawiera tę
frazę idealnie → są dla Google równie trafne i tym mocniej biją się o nią nawzajem. Canonical tego
NIE naprawi: każda oferta jest self-canonical i **słusznie** — to fizycznie różne auta (inny VIN,
cena, przebieg), nie duplikaty; kanonikalizacja 29→1 wyrzuciłaby realny towar z long-taila.
Lek = **strategia championa** (jedna oferta per wersja linkowana z huba anchorem „{model} {wersja}",
reszta zostaje na frazach z ceną/przebiegiem). To NASTĘPNY task — bez niego v4 sam z siebie nie
przełoży się na pozycje. Dowód skali: `li auto l9` = 5 naszych ofert po 1 impresji na poz. 29–63,
TNT jedną stroną na #20.

## 0.33.21 — 2026-07-14 (T-203 v3: szyk tytułu ofert {Marka} {Model} {Wersja} {Rok})

**Geneza (dane, nie intuicja):** GSC 90 dni — frazy z WERSJĄ (max/ultra/hyper) = 3 413 impresji,
z ROKIEM = 1 117, kombinacja „model + rok + wersja" = 303 (szum). Do tego ta sama wersja wraca
w kilku rocznikach (Li Auto L9 Ultra: 2024 i 2025), więc rok jest **rozróżnikiem** — jego miejsce
jest w ogonie, nie w środku. Stary szyk rozrywał frazę: „Zeekr 9X **2025** Ultra" nie zawiera ciągu
„zeekr 9x ultra"; nowy („Zeekr 9X Ultra 55kWh 2025") zawiera. Symulacja 114 ofert zaakceptowana
przed wdrożeniem (`auratest .../primaauto-t203-v3-szyk-symulacja-2026-07-14.html`).

**Wdrożone w `class-asiaauto-single.php` (wszystko RENDER-ONLY, `post_title` w DB nietknięty):**
- `v3YearLast()` — rocznik ze środka bazy na koniec. Regex `\b(19|20)\d{2}\b` nie łapie `2.0T`,
  `4WD`, `70kWh`, `1400PS`. No-op gdy rocznik już na końcu albo baza to sam rocznik.
- `v3BrandPrefix()` — marka z taksonomii `make`, gdy nie ma jej w tytule (**55 ofert publish**:
  Geely 34 — cały Galaxy M9, BAW 8, Lynk & Co 3). Guardy przeniesione z `hub-title-generator`
  (2026-07-13): `html_entity_decode` na encję `&amp;` + porównanie pierwszego tokenu („IM LS7"
  vs marka „IM Motors" — marka de facto już jest w nazwie).
- `V3_BRAND_ALIAS` — feed niesie inną nazwę tej samej marki: `Beijing 212 T01` + `make=BAW`
  → **`BAW 212 T01`** (podmiana, nie doklejanie prefiksu). TNT rankuje #12 na „baw" (1 300/mc)
  i #8 na „baw 212 gdzie kupić" (480/mc) — u nas token „BAW" nie występował nigdzie na stronie.
- `v3StripCjk()` — chińskie znaki z importu Dongchedi (**40 ofert publish**), np.
  `Beijing 212 T01 2024 2.0T 检阅官`, `Galaxy 银河A7 EM`, `MG Cyberster 580km Super 然致远`.
- `titleBaseV3()` — kompozycja trzech wyżej + static cache per `$pid`.

**Podpięte pod:** `buildTitleV2()` (meta title), `h1WithVariantSuffix()` (H1), `renderMeta()`
(meta description + `og:title`). **NIE podpięte pod `dataLayer.item_name`** — karmi GA4 i katalog
Meta, to nie warstwa SEO. Zapytania o duplikaty (`titlePriceCollides`, dupe-check w H1) nadal lecą
po **oryginalnym** `post_title`, bo to on siedzi w `wp_posts` — v3 to warstwa wyświetlania.

**Huby NIETKNIĘTE** (decyzja Janka z 2026-07-13 podtrzymana). Rollback: opcja
`asiaauto_offer_title_v2_series` = `""`. Backup: `.bak-2026-07-14-t203v3`.

**Znane, NIENAPRAWIONE (do decyzji):** feed czasem wstawia napęd/baterię PRZED oznaczenie wersji,
więc na dwóch największych frazach przyleganie dalej nie wychodzi: `Xiaomi YU7 **4WD** Max 2025`
(fraza „xiaomi yu7 max", 6 600/mc) i `Zeekr 8X **55kWh** Ultra 2026` („zeekr 8x ultra", 2 900/mc).
Naprawa = hoist tokenu wersji tuż za nazwę serii z taksonomii — osobny task, wymaga symulacji.
Osobno: jedna oferta ma tytuł zaczynający się od `[` (śmieć z feedu).

## 0.33.20 — 2026-07-13 (T-203/desc: meta description ofert v2 — pełny opis + „bezpośredni importer")

**Decyzje Janka:** hubów NIE ruszamy (fix „1 egzemplarzy" w generatorze hubów ODRZUCONY — zostaje
jako znany defekt); desc ofert maksymalnie długi, końcówka „Prima Auto — bezpośredni importer".
Symulacja 100 ofert zaakceptowana przed wdrożeniem (`auratest .../primaauto-sym-desc-100-ofert-2026-07-13.html`).

**Zmiana (`renderMeta()` w `includes/class-asiaauto-single.php`, backup `.bak-2026-07-13-desc`):**
- wzorzec: `{base} — {FUEL_SHORT}, {moc} KM (gdy w extra_prep), {przebieg} km, {cena} PLN.
  [Dostępny od ręki w Rzeszowie.] Prima Auto — bezpośredni importer samochodów z Chin.`
- usunięte błędne „od {cena}" (konkretny egzemplarz ≠ zakres huba), paliwo skrótem (EV/EREV/PHEV —
  pełne nazwy typu „elektryczny z range extenderem (erev)" rozsadzały limit 160 zn),
  moc przez `AsiaAuto_Inventory::resolvePower()` na surowym extra_prep (bez translatora).
- Symulacja na próbce: śr 128 zn (było ~100), max 163, 1/100 ponad 160, moc w 30/100 najnowszych
  (świeże importy bez extra_prep — zamrożony feed; w starszej bazie pokrycie ~95%).

## 0.33.19 — 2026-07-13 (T-203/H1: różnicowanie H1 ofert kaskadą przebieg→cena)

**Powód:** Screaming Frog Janka — 2 524 publish ofert dzieliło H1 z inną ofertą (H1 = `post_title`,
feed wozi po kilkadziesiąt egzemplarzy tej samej wersji). Decyzja Janka: różnicować od razu
(pierwotnie KROK 3b czekał na pomiar).

**Zmiana (`includes/class-asiaauto-single.php`, backup `.bak-2026-07-13-h1`):**
- nowa `h1WithVariantSuffix()` — gdy inna publish-oferta ma identyczny `post_title`, H1 dostaje
  ` - {przebieg} km`; gdy bliźniak ma też ten sam (lub zerowy) przebieg → dodatkowo `, {cena} PLN`.
  Render-only: `post_title` w bazie nietknięty (umowy/feedy/dataLayer/WhatsApp bez zmian).
- stickyHead renderuje H1 przez nową metodę (obie kopie: mobile+desktop); static cache per pid
  (2 wywołania per stronę = 1 zestaw query). Gate: ten sam `offerTitleV2()` (obecnie `*`).
- Efekt zmierzony SQL: duplikaty H1 2 524 → **99** (bliźniaki identyczne tytułem+przebiegiem+ceną
  naraz — brak kolejnego sensownego wyróżnika; te same 95-99 sztuk dzieli też title/desc).
- Smoke: para YU7 4WD Max (7 000 vs 30 000 km), czwórka AITO M5 z parami o identycznym przebiegu
  (rozróżnione ceną), unikalny tytuł bez suffixu.

## 0.33.18 — 2026-07-13 (rollout title v2 na WSZYSTKIE oferty + fix ensureBrandPrefix — 9 hubów)

**Kontekst:** plan naprawy po ocenie szkód (duplikaty title/H1 nieobjęte żadnym audytem; rotacja
title aktywna od v0.32.36/2026-05-06 bez weryfikacji unikalności — 1 012 ofert ze zdublowanym
title poza pilotem, H1: 2 524 oferty/499 grup — otwarte jako KROK 3b w specu T-203).

**Zmiany:**
- **Rollout title v2**: option `asiaauto_offer_title_v2_series` = `*` (z pilotowego CSV) —
  wszystkie ~4 500 ofert na wzorcu `{base} - {cena} PLN [...]`; likwiduje 1 012 duplikatów title,
  resztki „Używane" i spam-szablonów. Bez zmian w kodzie. Rollback selektywny = wpis CSV term_id.
- **Fix `ensureBrandPrefix()`** (`includes/class-asiaauto-hub-title-generator.php`, backup
  `.bak-2026-07-13-brandprefix`): (1) `html_entity_decode` obu stron porównania — encja
  `&amp;` w term name dublowała „Lynk & Co Lynk &amp; Co 03/06/07/08/10"; (2) porównanie
  tokenowe pierwszego słowa — „IM LS7" vs marka „IM Motors" nie dublują już prefixu;
  (3) mapa marek + `im-motors` → „IM Motors", `baw` → „BAW".
- Regeneracja `rank_math_title` 8 hubów (regenerateForTerm) + ręczny fix termu 6601
  (Lynk & Co 10 EM-P, count=0 → generator go pomija by design, stale meta poprawione str_replace).
  Smoke live: lynk-co-03, im-ls7, baw/t01 — czyste.

## 0.33.17 — 2026-07-13 (T-203: title ofert v2 — wersja + cena, pilot 9 serii)

**Powód:** analiza konkurencyjna tntcars.pl — frazy wersyjne (największy wolumen) przegrywane
przez title z rotacji 10 szablonów (`inner_id % 10`): duplikaty title w obrębie tej samej wersji
(6/10 na próbce YU7), prefiks „Używane" psujący intent, brak ceny/wyróżnika. Spec:
`docs/seo/T-203-oferty-wersje-title-spec-2026-07-13.md`, baseline: `docs/seo/T-203-baseline-gsc-2026-07-13.md`.

**Zmiana (`includes/class-asiaauto-single.php`, backup `.bak-2026-07-13-t203`):**
- `filterTitle()`: gałąź v2 przed rotacją legacy — `{base} - {cena} PLN [, {przebieg} km przy
  kolizji ceny][, dostępny od ręki dla on_lot] | Prima-Auto`; rotacja zostaje dla serii poza gate'em
- nowe: `offerTitleV2()` (gate: option `asiaauto_offer_title_v2_series` = CSV term_id serie, `*` = wszystkie),
  `buildTitleV2()`, `titlePriceCollides()` (1 query przy renderze single),
  `detexturizeTitleV2()` na `document_title` prio 20 (wptexturize zamieniał ` - ` na półpauzę)
- `renderMeta()`: og:title spójny z title v2 gdy gate aktywny
- stock = `_asiaauto_reservation_status` = `on_lot` (parytet z inventory); **NIE** `stm_car_location`
  (to miasto z feedu — pierwsza wersja się na tym przejechała, wykryte w smoke teście)

**Pilot (option ustawiony):** `5149,5150,4824,6569,6581,3981,6558,5304,3581` = SU7, YU7, Zeekr 9X,
Zeekr 8X, G700, Tank 700 Hi4-T, Preface, AITO M9, Arrizo 8 (~430 ofert). Rollback: option na `""`.
Pomiar GSC ~2026-07-27; progi rollbacku w baseline.

## 0.33.16 — 2026-07-07 (T-190: guard importera — make-aware serie)

**Powód:** importer odtwarzał zmergowane duplikaty i kontaminował huby cudzych marek —
lookup termu `serie` był globalny po slugu (bez marki), nowy term powstawał bez parenta
(sieroty), a slug szedł ze stale mapowania. Skala: 19 wzorców / ~106 aut na złych termach,
nawroty wszystkich 3 merge'ów galaxy-* z T-019 (diagnoza: `docs/seo/t190-log.md` KROK 0).

**Zmiana (`includes/class-asiaauto-importer.php`, backup `.bak-2026-07-07-t190-guard`):**
- nowe `setSerieTaxonomyAndMeta()` + `resolveSerieTermId()` + `isTokenSuffix()` — serie
  szukane wśród DZIECI marki (slug → znormalizowana nazwa → jednoznaczny wariant
  z/bez prefiksu sub-marki); nowy term zawsze z `parent`=make; meta `serie` = realny slug
  termu; `_asiaauto_primary_make_slug` od pierwszego zapisu; niejednoznaczność = osobny
  term + warning (bez zgadywania);
- `computeTerms()` (dry-run Che168) — `exists` tym samym resolverem (dry-run == import);
- `setTaxonomyAndMeta()` i pozostałe taksonomie NIETKNIĘTE; brak marki → fallback 1:1.
- header `Version` zsynchronizowany ze stałą (drift 0.33.14/0.33.15 → oba 0.33.16).

**Test:** 5/5 PASS (reflection, testowy draft, auto-cleanup) — AITO M8↛GAC, galaxy-e5→e5,
Avatr 07↛WEY, nowy term z parentem, fallback. Smoke 200. ADR Galaxy:
`docs/decyzje/2026-07-07-t190-galaxy-pod-geely.md`. Dziennik: `docs/seo/t190-log.md`.

---

## 0.33.15 — 2026-07-07 (homepage hero-sub + /informacje/o-nas/ — encja „importer")

**Powód:** audyt SEO 2026-07-07. „importer samochodów z chin" = najlepiej konwertujący topic-keyword
w Ads (4 konw./30 dni), a słowo „importer" padało 0× na stronie. SERP: konkurencja w topie na
„importer aut/samochodów z chin", nasza domena #10 / brak top 10 (rankuje nasz Facebook). Wtręt
addytywny — bez ruszania działającego „import".

**Zmiana:**
- `includes/class-asiaauto-homepage.php` (backup `.bak-2026-07-07-importer`) — hero-sub
  „import chińskich samochodów" → „bezpośredni importer samochodów z Chin". H1/title/FAQPage nietknięte.
- `/informacje/o-nas/` (post 198480, poza pluginem) — intro z encją „importer" + nowy H2
  „Bezpośredni importer samochodów z Chin" + `rank_math_description`. „importer" 0 → 6×.

**Uwaga:** model prawny = pośrednictwo/agencyjny; „importer" użyte jako opis funkcjonalny
(„model agencyjny / na Twoją rzecz"), bez sprzeczności. ADR: `docs/decyzje/2026-07-07-homepage-onas-importer-encja.md`.

---

## 0.33.14 — 2026-06-19 (Che168: import przeniesiony do menu Ogłoszeń + dostęp dla sprzedawcy)

**Powód:** faza obserwacji domknięta, realny import che168 WŁĄCZONY (v0.33.11). Janek chce,
żeby Che168 było drugim źródłem importu ręcznego dostępnym tak samo jak Dongchedi — w tym dla
Ruslana (sprzedawca). Pierwotny gate „osobne top-level menu, tylko login `js`" stracił rację bytu.

**Zmiana (`includes/class-asiaauto-admin-che168-import.php`):**
- `addMenuPage()` — `add_menu_page` (top-level „Import z Che168", pozycja 27) → `add_submenu_page`
  pod `edit.php?post_type=listings` jako **„Dodaj z Che168"**, obok „Dodaj z Dongchedi". Usunięty
  early-return gate — dostępem steruje cap `IMPORT_CAP` (param submenu).
- `allowed()` — przepisane z gate'u login∈`ASIAAUTO_CHE168_PREVIEW` na zwykłe
  `current_user_can(IMPORT_CAP)`; otwiera wszystkie handlery (preview/log/import/render) dla
  admina **i** sprzedawcy (rola `primaauto` = Ruslan). Stała `ASIAAUTO_CHE168_PREVIEW` przestaje
  być używana (można posprzątać z wp-config, nieszkodliwa).
- `enqueueScripts()` — hook `toplevel_page_` → `listings_page_` (zmiana typu strony).
- H1 — usunięte „(ukryte — tylko dla Ciebie)".

**Weryfikacja dostępu:** `js` (administrator) = DOSTĘP, `primaauto` (sprzedawca) = DOSTĘP.
Strefa krucha (`importListing`/taksonomie/adapter) NIETKNIĘTA. Backup
`class-asiaauto-admin-che168-import.php.bak-2026-06-19-submenu`.

## 0.33.13 — 2026-06-19 (Che168: alias marki Shanhai → Jetour)

**Powód:** ogłoszenie 58660114 wychodziło sierotą — che168 wystawia serię new-energy Jetoura
„Shanhai" (山海) jako osobną MARKĘ `mark="Shanhai"`, model `"Shanhai L9"` (wersja EN che168
pokazuje przy tym „Jetour"). Surowy klucz `Shanhai|Shanhai L9` nie istnieje w brand-mappingu
(tam kanoniczny `Jetour Shanhai|Jetour Shanhai L9`), więc `resolveChe168` zwracało null → `mapped=NIE`.

**Zmiana (addytywna, normalizacja-przy-wejściu; strefa krucha NIETKNIĘTA):**
- `data/che168-model-map.php` — +1 override `'Shanhai|Shanhai L9'` → sygnatura EU `Jetour|Shanhai L9`.
  Mostkuje surowy klucz che168 do istniejącej sygnatury brand-mappingu; `sigToKey` rozwija ją do
  kanonicznego klucza CN → dowiązanie do **istniejącego** huba serie 5624 (slug `shanhai-l9`,
  parent = make Jetour 4525). Zero nowych termów.

**Wynik:** 58660114 → `mapped=TAK`, mark=Jetour, serie „Shanhai L9", tytuł
„Jetour Shanhai L9 2025 1.5TD 2DHT Air 7-osobowy". Wyposażenie: `extra.option` (11/11 optionname
zmapowanych, 0 braków). Backup `che168-model-map.php.bak-2026-06-19-shanhai`.

## 0.33.12 — 2026-06-18 (Che168: import wyposażenia z extra.option)

**Powód:** ogłoszenia che168 miały „Dane techniczne", ale pustą sekcję wyposażenia
(`[asiaauto_equipment]`). Diagnoza: che168 trzyma wyposażenie OSOBNO w `extra.option`
(displayopts + moreoptions[].opts, CJK), a `extra.configuration` to wyłącznie spec techniczny —
adapter czytał tylko configuration. Dongchedi wplata wyposażenie w `extra_prep`, stąd u niego
działa „na maksa". Dokumentacja auto-api (`autoapicom/auto-api-php`) nie opisuje pól — źródłem
prawdy odpowiedź API.

**Zmiany (addytywne, normalizacja-przy-wejściu; strefa krucha importer/render NIETKNIĘTA):**
- `data/che168-option-map.php` — **nowy**. Mapa 37 distinct optionname (CJK) → klucz `extra_prep`,
  zbudowana z PRÓBKI 120 ofert (`tmp/che168-option-aggregate-out.tsv`).
- `class-asiaauto-che168-adapter.php` — `extractOptions()` czyta `extra.option`, wstrzykuje do
  `extra_prep` wartość `标配` (→ „Tak" → checkmark) tylko gdy klucza brak (spec config wygrywa).
- `data/translations-extra-prep.php` — 5 nowych etykiet (`auto_brake_hold`, `rear_air_outlet`,
  `uv_protection_glass`, `center_diff_lock`, `phone_mapping`) + dopięcie istniejących kluczy
  uncategorized do kategorii (adas: `active_brake`/`lane_keeping_assist`/`line_support`); relabel
  `active_closed_inlet_grid` „Aktywny grill"→„Aktywna atrapa".

**Wynik:** BAIC BJ60 58760644 (post 361348) → 22 pozycje wyposażenia. Istniejące drafty
odświeżone re-adapterem (`_asiaauto_extra_prep`), bez re-importu; nowe importy automatycznie.
58779380 ma 0 (oferta bez `extra.option` w feedzie — nie luka).

## 0.33.11 — 2026-06-18 (Che168: fallback rocznika gdy year=0)

**Powód:** część ofert che168 ma `year=0` (auto nierejestrowane, `first_registration="未上牌"`)
→ tytuł „AITO M9 0 …". Rok modelowy jest jednak w `param_93` ("YYYY款").

**Zmiana:** `class-asiaauto-che168-adapter.php` `normalize()` — fallback rocznika
`year` → `first_registration` (YYYY) → `param_93` ("YYYY款"). Refaktor: `name93` wyciągane raz
(wspólne dla wersji i rocznika). Wynik: AITO M9 55603575 `year=0`→2025. 1/21 w próbce dotknięte.

## 0.33.10 — 2026-06-18 (Che168: wersja/trim w tytule — parytet z dongchedi)

**Powód:** builder tytułu (`computeIdentity`) jest wspólny i agnostyczny — tytuł =
`{mark} {model} {year} {complectation}`. Różnica była w DANYCH: dongchedi podaje `complectation`
(trim) na wierzchu, che168 zostawia je puste — trim siedzi w `param_93` (车型名称) za prefiksem
„YYYY款" (np. `尚界Z7T 2026款 Max`). Slug bez różnicy (nie zawiera trim).

**Zmiany (addytywne, normalizacja-przy-wejściu):**
- `class-asiaauto-che168-adapter.php` `normalize()` — ekstrakcja trim z `param_93` (część po „款")
  → `$data['complectation']` (gdy puste). Wspólny builder daje odtąd tytuł z wersją jak dongchedi.
- `class-asiaauto-translator.php` `translateComplectation` — strip resztkowego CJK na końcu
  (marketing che168 `很有型`/`激光雷达` nieobjęty mapą). **Guarded** → no-op dla czystych ASCII
  (dongchedi bez regresji; mapa już zna 智驾版→Smart Drive, 旗舰型→Flagship, 激光雷达→LiDAR…).

**Wynik (próbka):** „SAIC Shangjie Z7T 2026 Max", „Li Auto L9 2025 Ultra", „BYD Han DM-i 2025
DM-i Smart Drive 125KM LiDAR Flagship", „Zeekr X 2024 4-osobowy RWD Cube" — czyste, z wersją.
Slugi niezmienione. Backupy `.bak-2026-06-18-compl`.

**Znane (osobne, nie z tej zmiany):** część ofert che168 ma `year=0` (źródło nie podało roku
→ „AITO M9 0 …") — do ekstrakcji `year`; redundancja trim×model (ET5 Touring…Touring) = kosmetyka.

## 0.33.9 — 2026-06-18 (Che168 Faza 2: tłumaczenia wartości CJK)

**Powód:** po param-mapie (0.33.8) 11 kluczy kategorycznych było zmapowanych, ale ukrytych przez
wartości CJK (filtr anty-CJK w renderze). Zbiór realnych wartości blokujących zebrany z próbki 21.

**Zmiany (addytywne):**
- `data/translations-extra-prep.php` (wspólny config, sekcja `values`) — blok wariantów Che168:
  zawieszenie (che168 `悬架` ≠ dongchedi `悬挂`), nadwozie, hamulce, typ silnika EV, producenci
  ogniw (CATL/CALB/EVE/Gotion/SVOLT), typ baterii kombi, koło zapasowe, sub-marki.
- `class-asiaauto-translator.php` `translateExtraPrepValue` — wzorce (kod): gwarancja z CHIŃSKIMI
  cyframi lat (`八年或16万公里`→`8 lat / 160 000 km`; dongchedi dawał cyfry arabskie), pierwszy
  właściciel bez limitu, `增程器`→Range extender, oktan `92号`→`92 oktanów`.

**Wynik:** Z7T 40→**44** pokazanych, próbka 21 → 68%→**75%** (z 47% na starcie), **0 wartości
blokujących CJK**. Reszta ukrytych = rzadki ogon `param_{id}` bez czystego klucza + `选配`/puste.
Backupy `.bak-2026-06-18-{che168vals,patterns}`.

## 0.33.8 — 2026-06-18 (Che168 param-map: +32 id, odzysk specyfikacji EV/silnika)

**Powód:** che168 pokazywał mało danych (Z7T 28/59 param vs SU7 dongchedi 233/371). Diagnoza:
(1) che168 ze źródła zwraca mniej (~59 vs 371, brak list wyposażenia — `option`/`moreoptions`
puste); (2) z tego 31 ukrytych przez luki param-mapy + wartości CJK. che168 podaje parametry pod
numerycznymi `id`, więc `che168-param-map.php` to **adapter id→klucz** — cała reszta (etykiety,
kategorie, sortowanie, grupowanie) to **wspólny** `translations-extra-prep.php` (ten sam co dongchedi).

**Sample-based (próbka 21 ofert dopasowanych do filtrów importu = same chińskie marki):** agregacja
ujawniła 76 niezmapowanych id wg częstości. Dodane **32 mapowania** w `data/che168-param-map.php`:
- **21 numerycznych** (renderują od razu): zasięg CLTC/WLTC, zużycie energii/ekwiwalent,
  moc/moment łączny + przód/tył silnika EV, moc/moment/obroty silnika spalinowego, moc szybkiego
  ładowania, zakres %, czas ładowania, promień zawracania.
- **11 kategorycznych** (zmapowane, czekają na tłumaczenia wartości CJK — Faza 2): typ/producent/
  gwarancja baterii, typ/liczba/układ silników EV, rodzaj energii, norma emisji, układ silnika,
  typ 4x4, koło zapasowe.

**Wynik:** Z7T 28→**40** pokazanych, próbka 21 → **68%**; kolejność kategorii identyczna jak
dongchedi (ten sam config). Post #361316 (zaimportowany Z7T) — meta odświeżone. Backup
`.bak-2026-06-18-expand`. **Faza 2 (pending):** tłumaczenia wartości CJK; uwaga — che168 używa
innych znaków niż dongchedi dla części wartości (zawieszenie `悬架` vs `悬挂`).

## 0.33.7 — 2026-06-18 (nowa marka Che168: 尚界 → SAIC Shangjie)

**Powód:** ogłoszenie 58779380 — nowa marka `尚界` (Shangjie, HIMA SAIC+Huawei) + model
`尚界Z7T`, oba CJK → sierota, make z pustym slugiem. VIN `LSJ`=SAIC, web/Wikipedia + reference
west-motors → „SAIC Z7/Z7T". Etykieta marki **SAIC Shangjie** (decyzja Janka, hub
`/samochody/saic-shangjie/z7t/`).

**Zmiany (addytywne, 0 regresji — nowe klucze):**
- `class-asiaauto-mapping.php` `resolveChe168` — marka `尚界`→`SAIC Shangjie` w `cnPrefix`
  (strip prefiksu modelu) + `$markAlias` (marka czysto-CJK, nieosiągalna stripem). Skaluje:
  kolejne modele 尚界 = już tylko wpis w brand-mappingu.
- `data/brand-mapping-v6.1.php` — wpis `SAIC Shangjie|Z7T` (Z7T = shooting-brake; Z7 = sedan).

**Weryfikacja:** 58779380 → SAIC Shangjie/Z7T mapped=1, slug make `saic-shangjie` (nie pusty),
serie `z7t`; enumy znormalizowane (rwd-rear-engine→RWD, 银/灰色→silver). Smoke 4/5 bez regresji.

## 0.33.6 — 2026-06-18 (grupa B: domknięcie 2 luk mark/model Che168)

**Powód:** odświeżenie logów dry-run (v0.33.4) zostawiło 2 rezydua mark/model (nie enumy):
57762274 „Tank 300 New Energy" (sierota), 58645565 „IM"/`智己LS8` (best-effort raw zamiast hubu).

**Zmiany (addytywne — nowe klucze, zero zmian istniejących linii, 0 orphanów):**
- `data/che168-model-map.php` — `Tank|Tank 300 New Energy` → konsolidacja do istniejącego huba
  Tank/300 (wariant nazwy „New Energy" = hybryda/EREV; wzorzec jak `Changan|CS75 PLUS iDD`).
- `data/brand-mapping-v6.1.php` — nowy wpis `IM Motors|LS8` (realny nowy model; CJK `智己`
  zdejmuje algorytm resolvera, więc wystarczy entry). Hub LS8 utworzy się czysto przy imporcie.

**Weryfikacja:** 57762274 → Tank/300 (0 nowych termów), 58645565 → IM Motors/LS8 (1 nowy term
`serie:LS8` = legalny nowy model). Backupy `.bak-2026-06-18-{tank,ls8}`.

## 0.33.5 — 2026-06-18 (cleanup: usunięty martwy resolveForSource)

**Powód:** po decyzji „normalizuj na wejściu" (ADR 2026-06-17) `AsiaAuto_Mapping::resolveForSource()`
przestał być wołany przez żywy kod — pozostały tylko `canonicalKeyForSource()` (woła
`resolveChe168()` bezpośrednio z adaptera) i `getEuForCn()`. Dług usunięty świadomym commitem.

**Zmiany:** usunięta metoda `resolveForSource()` z `class-asiaauto-mapping.php`; poprawione
dwa nieaktualne komentarze (`class-asiaauto-importer.php`, `data/che168-model-map.php`)
wskazujące na usuniętą metodę → teraz `resolveChe168()`. Backup `.bak-2026-06-18-deadcode`.
Bez regresji: smoke mark/model 4/5 mapped jak wcześniej.

## 0.33.4 — 2026-06-18 (T-186: normalizacja enumów atrybutów Che168 PRZY WEJŚCIU)

**Powód:** dry-run oferty 58545168 (Denza N8L) — 3 taksonomie „🆕 zostanie utworzony"
(`crossover/suv`, `plug-in hybrid`, `awd (front-engine)`). Surowiec enumów che168 ≠ Dongchedi →
slug z `api_value` dawałby śmieciowe duplikaty (`crossover-suv` obok `suv`). Tożsamość termu
idzie po slugu, nie po tłumaczeniu — sama dopiska do słowników nie wystarcza.

**Zmiany (addytywne, `importListing`/`setTaxonomies` NIETKNIĘTE):**
- `data/che168-enum-map.php` (nowy) — płaska mapa danych surowiec che168 → klucz słownika
  Dongchedi (body/engine/drive/color). Domena zamknięta (`getFilters`) → mapa, bez resolvera.
- `class-asiaauto-che168-adapter.php` — `normalizeEnums()` w `normalize()` przed kanonizacją
  tożsamości; surowiec w `{field}_che168_raw`. Słowniki `translations-*.php` reużyte 1:1.
- Mild-hybrydy zwinięte w `hybrid` (HEV) — decyzja Janka (bez osobnego filtra MHEV).

**Pokrycie:** body 10/10 + engine 14/14 + drive 11/11 → istniejące termy; zero nowych termów,
zero śmieci. 58545168: 9/9 ISTNIEJE. Bez regresji mark/model. Import che168 nadal OFF.
ADR: `docs/decyzje/2026-06-18-che168-enum-normalize.md`.

## 0.33.3 — 2026-06-17 (T-186 fala 2: wczesny getEuForCn + aliasy nazw domowych)

**Kontekst:** rozszerzenie analityki na kolejne 50 ogłoszeń Dongchedi (41 modeli) wykazało pokrycie 30/41; domknięte do **35/41** addytywnie.

**Zmiany:**
- `class-asiaauto-mapping.php` `resolveChe168` — krok **0a: wczesny `getEuForCn`** na surowych mark/model PRZED stripem marki. Łapie osobną markę che168 „Galaxy / Galaxy L6/L7" (== klucz brand-mappingu `Galaxy|Galaxy L6`), który strip kroku 2 wcześniej rozbijał („Galaxy L6"→„L6"→miss).
- `data/che168-model-map.php` — 4 aliasy nazw domowych: `Li Auto|Li L6`→L6, `NIO|ET5T`→ET5 Touring, `Changan|CS75 PLUS iDD` i `Changan|长安CS75PLUS`→CS75 Plus.
- Bez regresji: pierwsza 20 16→**17/20**.

**Grupa B (pending, decyzje per-hub):** Dongfeng Fengxing Xinghai T5 (sub-brand→Forthing), BYD Seal U/Song Plus (nazwa che168 wieloznaczna), BYD Han L EV, iCAR Super V23, Geely Galaxy Starship 8 (nowe warianty), Mazda 3 Axela (poza importem). ADR: `docs/decyzje/2026-06-17-che168-normalize-at-entry.md`.

## 0.33.2 — 2026-06-17 (T-186: normalizacja tożsamości Che168 PRZY WEJŚCIU)

**Powód:** dowód (`tmp/che168-vs-dongchedi-proof`) — surowe mark/model che168≠Dongchedi (0/12 identycznych), `getEuForCn(surowiec che168)`=1/16. Rozjazd realny. Zamiast wpinać resolver w strefę kruchą (stary plan T-186) — normalizacja w adapterze.

**Zmiany (addytywne, `importListing` NIETKNIĘTY):**
- `class-asiaauto-mapping.php` — nowa `canonicalKeyForSource()` (che168 → klucz CN brand-mappingu przez `resolveChe168` + reverse-index sig→klucz; dongchedi pass-through). `resolveChe168`: strip CJK z marki (`AITO 问界`→`AITO`), alias `IM`→`IM Motors`, prefiks `智己`.
- `class-asiaauto-che168-adapter.php` `normalize()` — stempluje kanoniczny `mark`/`model` (raw w `*_che168_raw`).
- `class-asiaauto-importer.php` `computeIdentity`/`computeTerms` (dry-run) — `resolveForSource`→`getEuForCn` (dry-run == realny import). Komentarz nagłówkowy zaktualizowany (stary plan „resolver w importListing" unieważniony).
- `data/che168-model-map.php` — 5 aliasów: Wey Lanshan, eπ008, Li L9, Fang Cheng Bao Leopard 5, VOYAH Dreamer (PHEV; override ślepy na napęd — EV do dorobienia).

**Weryfikacja:** realna ścieżka adaptera→getEuForCn 16/20 (4 NULL = luki brand-mappingu wspólne z Dongchedi). Import che168 nadal OFF. ADR: `docs/decyzje/2026-06-17-che168-normalize-at-entry.md`.

## 0.33.1 — 2026-06-16 (T-185 rewizja: OSOBNA strona „Import z Che168" + strefa krucha cofnięta do addytywu)

**Powód rewizji (decyzja Janka):** w 0.33.0 che168 był dołożony do współdzielonego panelu „Dodaj z Dongchedi" (używa go też sprzedawca/Ruslan) + refaktorował `importListing` (strefa krucha). Oba zbędne. Lepsza architektura: **osobne menu, czysto samo Che168**, reużywające wspólnych klas jako konsument, bez dotykania panelu Ruslana i bez refaktoru kruchej ścieżki.

**Zmiany:**
- **Nowa `class-asiaauto-admin-che168-import.php`** — samodzielne top-level menu „Import z Che168", **całe za gate** (`add_menu_page` rejestrowane tylko gdy login∈ASIAAUTO_CHE168_PREVIEW → Ruslan nie dostaje nawet pozycji menu). Własne AJAX-y (`asiaauto_che168_{preview,log,import}`), pełna tabela dry-run, „Zapisz do logu", lista logu, import za flagą.
- **`class-asiaauto-importer.php` PRZYWRÓCONY do oryginału** (704 linie) — `importListing`/`setMotorsMeta`/`setTaxonomies` bajt w bajt jak przed T-185. `buildPlan`/`computeIdentity`/`computeMeta`/`computeTerms` dopisane jako **czyste metody OBOK** (woła je tylko dry-run; ścieżka realnego importu ich nie używa). **Strefa krucha NIETKNIĘTA.** Wierność dry-run vs realny listing dongchedi: 6/6 title, 88/88 meta, 54/54 terms.
- **`class-asiaauto-admin-manual-import.php` PRZYWRÓCONY do stanu sprzed T-185** — panel Ruslana bez śladu che168/dry-run.
- Bez zmian (czyste, konsumowane przez nową stronę): adapter, `resolveForSource` w mapping, che168-model-map, param-map, klasa logu, stałe wp-config.

**Weryfikacja:** gate per user (`js`=widzi menu, `primaauto`=nie); test E2E w wp-admin (Chrome, jako js) — Denza D9 57888520: dry-run pełny, fuel/drive `🆕 nowy`, „Zapisz do logu" → snapshot `57888520-...json` na liście. Wiązanie `resolveForSource` w realny import odroczone do włączenia che168 (T-186, świadoma edycja kruchej ścieżki).

## 0.33.0 — 2026-06-16 (T-185: Che168 ręczny import + log wdrożeniowy — ukryte, faza obserwacji)

**Kontekst:** feed dongchedi nawracająco pada (T-182); dostawca steruje fallbackiem na Che168 (C2C aut używanych, inny profil — kurator wybiera auta ręcznie, nie automat). Pełny ADR: `docs/decyzje/2026-06-16-che168-manual-import.md`.

**Architektura:** wspólny kod symulacji i realnego importu. `AsiaAuto_Importer::importListing()` zrefaktorowany (ekstrakcja czystych `computeIdentity`/`computeMeta`/`computeTerms`/`buildPlan` bez zmiany zachowania — `setMotorsMeta`/`setTaxonomies` to teraz pętle po `compute*`). Dry-run == realny import (zero driftu). Strefa krucha za świadomą zgodą Janka; chroniona testem regresji **6/6 title, 88/88 meta, 54/54 terms** na realnych listingach dongchedi.

**Nowe pliki:** `class-asiaauto-che168-adapter.php` (address→city, first_registration→reg_date, extra.configuration→extra_prep po `id`), `data/che168-param-map.php` (51 id→klucz dongchedi), `class-asiaauto-che168-log.php` (snapshot dry-run → `uploads/asiaauto/che168-dryrun/`), `data/che168-model-map.php` (51 nadpisań resolvera).

**Zmienione:** `class-asiaauto-mapping.php` (`resolveForSource()` — reverse-index brand-mappingu + reguły normalizacji; dongchedi→getEuForCn bez zmian), `class-asiaauto-admin-manual-import.php` (gate `che168Allowed`, detekcja źródła, pełna tabela zgodności dry-run [title/slug/mapowanie/taksonomie ze statusem/meta/extra_prep pogrupowany], przycisk „Zapisz do logu wdrożeniowego", warunkowy import, widok listy logu), `asiaauto-sync.php` (require_once + bump).

**Gate:** `wp-config.php` `ASIAAUTO_CHE168_PREVIEW='js'` (brak stałej = niewidoczne, zero zmian UX dongchedi) + `ASIAAUTO_CHE168_IMPORT_ENABLED=false` (faza obserwacji — realny import che168 ukryty).

**Smoke:** resolver che168 5/5 ze spec; adapter 5/5 (city+vin+reg_date, extra_prep, obrazy `2sc2.autoimg.cn`); tabela podglądu Denza D9 = 9 taksonomii/11 meta/8 kat·46 wierszy; log save→all→re-decode OK (diakrytyki zachowane). Backupy `.bak-2026-06-16-che168` (importer/mapping/manual-import/asiaauto-sync/wp-config).

**Faza obserwacji (otwarta):** Janek zbiera snapshoty → wspólna kalibracja `che168-model-map`/`che168-param-map`/wartości/resolver → po pokryciu `ASIAAUTO_CHE168_IMPORT_ENABLED=true`. Automat = osobny T-186.

## 0.32.73 — 2026-06-09 (rotation: kasowanie zdjęć przy permanent delete + backfill 54508 sierot)

**Kontekst:** audyt inode konta — `uploads/asiaauto/` urósł do ~575 tys. plików / 13,2 GB (główny konsument inode). Korzeń: `deleteOldTrash()` kasował listingi przez `wp_delete_post($pid, true)`, a WP core (`wp-includes/post.php:3861` — „Point all attachments to this post up one level") **przepina attachmenty usuwanego posta na `post_parent=0`**. `cleanOrphanedImages()` filtruje `post_parent>0` → nigdy ich nie łapał → „0 orphaned images removed" przez wszystkie 84 uruchomienia crona od marca. Zdjęcia każdego usuniętego auta (oryginał + 4 miniatury) wyciekały na dysk → **54 508 osieroconych attachmentów**.

**Zmiana — `includes/class-asiaauto-rotation.php` (backup `.bak-2026-06-09`):**
- W `deleteOldTrash()`, w pętli przed `wp_delete_post()`: `$this->media()->removeImages($pid, true)` — kasuje pliki + miniatury + wiersze attachment PÓKI listing żyje (czyta meta `gallery`, omija reparenting WP). Licznik `$images_removed` + log „Permanently deleted N trashed posts (M images removed)".
- Helper `media()` (leniwa `AsiaAuto_Media`) + property `$media`. `isReserved()` nietknięte.
- **Listingi ręczne bezpieczne bez dodatkowego guardu:** `markRemoved()` (jedyny setter `_asiaauto_removed_at` → jedyne wejście do trash→delete) wołany tylko z sync (guard `isManuallyManaged()`) i order-cancel (gate `_asiaauto_api_removed`, nieosiągalny dla ręcznych). `removeImages()` nie jest manual-aware (pętla galerii kasuje bezwarunkowo), ale ręczne nigdy nie trafiają do `deleteOldTrash` przez rotację.

**Backfill istniejących sierot** (skrypt chunkowy, re-weryfikacja 0 referencji galerii + skip `_asiaauto_manual_upload`; kryterium: `post_parent=0` + `_asiaauto_source_url`, 0 referencji w `gallery`/`_thumbnail_id` żadnego listingu):
- 2026/03: 742 → 106M/4204 plików → 38M/1155
- 2026/04: 34 538 → 5,5G/252980 → 1,9G/82785
- 2026/05: 19 228 → 6,1G/282114 → 4,1G/184507
- Pozostałe `parent=0` sieroty: **0**. Odzysk **~5,7 GB / ~254 tys. inode**.

**Walidacja:** `php -l` clean, dry-run cleanup OK, test forward-fixu na 355476 (9 attach / 53 pliki na dysku) → 0 plików, 0 wierszy attachment, post usunięty. Backup DB przed: `~/backups/primaauto/2026-06-09/posts-postmeta-pre-orphan-cleanup.sql` (372 MB). Smoke homepage/oferta/marki = 200. Pomiar w boju: następny cron 2026-06-10 03:00 zaloguje M>0 images removed. ADR `docs/decyzje/2026-06-09-rotation-image-cleanup.md`.

## 0.32.72 — 2026-06-08 (hub marek: template pod answer-first lead + fact strip + dateModified)

**Kontekst:** rework hubów MAREK (`/samochody/<marka>/`) — domknięcie wzorca answer-first lead z pilota modeli. KROK 1 = template (treść per marka w osobnym batchu). Analiza KW + recon: `docs/seo/make-hubs-kw-analysis-2026-06-08.md`. Dane: `{marka} import`≈0 wolumenu → orientacja cena/Polska, nie import.

**3 pliki (strefa ZAWSZE PYTAJ, backupy `*.bak-2026-06-08`):**
- **`themes/primaauto2026/taxonomy-make.php`:** H1 z mechanizmem `_asiaauto_h1_suffix` (fallback „z Chin") + fact strip (`_asiaauto_facts` JSON: models/count/price_min-max-median/year_min-max/avail_label/updated) + „Stan oferty: {rok}" (`date('Y')`) + lead `_asiaauto_lead` (mirror `taxonomy-serie.php:107-111`). Wszystko render-if-meta — brak meta = stare zachowanie.
- **`themes/primaauto2026/assets/css/hub.css`:** `.aa-hub__facts` (+ `-note`) — nowa klasa, nieobecna na `taxonomy-serie.php` → 0 regresji 228 hubów modeli. Lead bez CSS.
- **`includes/class-asiaauto-seo.php`:** `dateModified` przez filtr `rank_math/json_ld` (`addHubDateModified`) z `_asiaauto_facts.updated`. Uwaga: `buildCollectionPage()` to dead code gdy RankMath aktywny (renderSchema emituje tylko ItemList+Product; CollectionPage/@graph robi RankMath).

**Smoke PASS:** baic (test facts) → H1 suffix + strip 6 pól + lead + dateModified 2026-06-08; byd (bez facts) → „BYD z Chin" nietknięte, HTTP 200, brak strip/lead/dateModified; modele (leopard-5) nietknięte. Fact strip = dane STORED (odświeżane reworkiem, nie live). Decyzja: bez newsów/premier (osobny projekt). KROK 2 = generacja treści per marka (subagenty, PYTAĆ przed batch).

## 0.32.71 — 2026-06-07 (SEO rework strony głównej — treść topiczna pod head termy)

**Kontekst:** osobny track SEO strony głównej (homepage = własna encja topiczna, [[feedback_homepage_seo_separate_track]]). Dedykowany research DataForSEO przewartościował topic: head term `chińskie samochody` **22 200/mc** (+ `chińskie samochody elektryczne` 1300), 85× większy niż frazy import- z B1 (`import aut z chin` 260). Decyzja Janka: **import = główny intent** (H1/title nietknięte), resztę pokryć wtórnie. Dotąd homepage = czysty katalog, zero prozy semantycznej.

**Zmiana — `class-asiaauto-homepage.php` (strefa ZAWSZE PYTAJ, backup `.bak-2026-06-07-seo-rework`):**
- **H1 BEZ ZMIAN** „Import samochodów z Chin do Polski" (nie rozrywać exact-match głównej frazy).
- **hero-sub** przerobiony → „import chińskich samochodów…" (lemma `chińskie samochody` pod H1).
- **Lead answer-first** (`renderLead()`) pod hero: `chińskie samochody` + `używane auta` + cena końcowa (AEO).
- **Przeróbka 7 H2** + opisy pod gridami (Latest/Makes/BodyTypes/FuelTypes): rozłożone `chińskie samochody`, `chińskie samochody elektryczne` (1300, exact w H2 paliwa), `auta z chin do sprowadzenia`, intent cena/import. Fix NAP „Prima Auto"→„Prima-Auto" w H2 USP.
- **Sekcja prozy + FAQ** (`renderTopicFaq()`) przed CTA: H2 `Import aut z Chin do Polski — jak to działa` (exact head term) + 5 pytań FAQ (FAQPage, ASCII quotes), markowo-transakcyjnych, zdedupowanych względem hubów (encjowe) i stron info (proces/koszt/homologacja). Link do `/informacje/` (oddanie intentu informacyjnego).
- CSS dla lead/proza/FAQ (akordeon `<details>` natywny).

**Smoke:** 11/11 treści live, FAQPage waliduje (5 Q), 0 PHP errorów, `php -l` clean. **Pomiar GSC ~06-20** (recrawl). Memory: [[project_homepage_seo_topic_2026_06_07]]. ADR `docs/decyzje/2026-06-07-homepage-seo-rework.md`.

## 0.32.70 — 2026-06-07 (SEO P2: fallback resolwera `/model/` — zamknięcie klasy 404)

**Kontekst:** audyt GSC 2026-06-07. Stary handler `/model/<slug>/` (`class-asiaauto-redirects.php` → `redirectLegacyTaxonomy`) przekierowywał **tylko** gdy żywy term serie istnieje pod dokładnym slugiem; po fuzji marek / normalizacji importera slug się zmieniał → `null` → 404 (żywe `/model/e008/`, `/model/galaxy-l6/` w indeksie Google jako „zaindeksowana", a HTTP 404). Nowszy `redirectHubMakePrefix` ma 3-warstwowy samonaprawiający fallback — stary `/model/` go nie miał.

**Zmiana — `/model/<slug>/` dostał 4-warstwowy fallback (każda warstwa redirectuje TYLKO gdy cel żyje):**
1. Term żyje → hub modelu.
2. Term martwy → match **bez myślników** (`e008` → `e-008` Dongfeng) — przed stripem zer, bo „008" to odrębny numer modelu, nie „8".
3. Term martwy → **pełna normalizacja** (`sea-lion-07dm` → `sealion-7-dm`).
4. Zdejmij **prefiks żywej marki** (`galaxy-l6` → make `galaxy` + remainder `l6`) → hub modelu remaindera, w ostateczności hub marki.
Brak żywego celu → przepuszczamy (naturalny 404, nie zgadujemy). Samonaprawiające: gdy model wróci (term=200), wcześniejsze warstwy go łapią.

**Kluczowa decyzja techniczna — markę wyznaczamy z dominującej marki listingów serii, NIE z mety `_asiaauto_primary_make_slug`.** Meta jest niewiarygodna: serie 3399 „Galaxy L6" ma metę `li-auto`, a 15/16 listingów to Geely → meta dałaby `/samochody/li-auto/l6/` (zły model, choć 200). Dominacja listingów daje poprawne `/samochody/geely/l6/`. To częściowo obchodzi P7 dla ścieżki redirectu (P7 nadal wart dla kanonicznych linków `filterSerieTermLink`).

**Nowe helpery (prywatne, redirects):** `serieHubUrl`, `dominantMakeSlug`, `serieHubBySlug`, `serieSlugMaps` (jeden przelot `get_terms` → 2 mapy: dashless + norm, cache per-request), `serieHubByDashlessSlug`, `serieHubByNormalizedSlug`, `modelOrMakeHubFromPrefixedSlug`. Heavy operacje (`get_terms` 2738 serii, `get_posts` 100) odpalają się **tylko na trafieniach legacy `/model/`** (rzadkie).

**Weryfikacja:** `php -l` czysty. Smoke (final HTTP, 2 hopy = 1×301+200, zero łańcuchów):
- `/model/galaxy-l6/` → `/samochody/geely/l6/` ✅ (dominacja naprawiła błędną metę)
- `/model/e008/` → `/samochody/dongfeng/e-008/` ✅ (dashless tier — Dongfeng E008, nie GAC/Geely E8)
- `/model/sea-lion-07dm/` → `/samochody/byd/sealion-7-dm/` ✅ (normalizacja)
- Regresja `/model/leopard-5,arrizo-8,8x/` → właściwe huby 301 ✅, `/marka/byd/` 301 ✅
Backup: `class-asiaauto-redirects.php.bak-2026-06-07`. **Pending: P3 — Janek eksportuje pełną listę 404 z GSC UI do rewalidacji.**

## 0.32.69 — 2026-06-07 (SEO P1: oferty jako Product snippet — multi-type `["Product","Car"]`)

**Kontekst:** audyt GSC 2026-06-07 (memory `project_session_2026_06_07_gsc_full_audit`). URL Inspection `richResultsResult` dowiódł, że single oferta (`/oferta/...`) z `@type=Car`+`Offer` jest przez Google promowana **tylko do breadcrumbu**, nie do „Opisów produktów" — licznik „Opisy produktów" w GSC zbierał wyłącznie ~28 hubów modelu, mimo 4458 ofert z ceną. Car⊂Vehicle⊂Product w Schema.org, ale Google kwalifikuje do „Product snippets" dopiero przy **jawnym** `Product`.

**Zmiana:** w żywym builderze schematu single (`class-asiaauto-single.php` → `renderMeta()`, ~linia 721) `'@type'=>'Car'` → `'@type'=>['Product','Car']`. Cały blok `offers` (Offer / price PLN / InStock / seller / shippingDetails / priceValidUntil) zachowany bez zmian. Ujednolicono `seller.name` „Prima Auto" → „Prima-Auto" (NAP) w obu blokach. **NIE** dodano `aggregateRating`/`review` (brak realnych ocen = ryzyko kary).

**Uwaga techniczna:** w pliku istnieje też martwa metoda `schema()` (~linia 558, zero wywołań) — dla spójności też dostała `["Product","Car"]`+„Prima-Auto", ale renderowanie idzie wyłącznie przez `renderMeta()`.

**Weryfikacja:** `php -l` czysty. Smoke 3 oferty (Leapmotor Lafa5 / iCAR Super V23 / AITO M8) → wyrenderowany JSON-LD ma `@type:["Product","Car"]`, `seller:"Prima-Auto"`, komplet wymogów Product snippet (name+image+offers.price/priceCurrency/availability). Backup: `class-asiaauto-single.php.bak-2026-06-07`. **Pending: Rich Results Test (Janek) + pomiar „Opisy produktów" w GSC po recrawl (~2 tyg).**

## 0.32.68 — 2026-06-05 (Fix: import ręczny robił śmieć z pustej oferty — „Listing {id}" + slug=ID + zero parametrów)

**Zgłoszenie:** Ruslan — w panelu „Dodaj z Dongchedi" niektóre oferty po imporcie nie miały parametrów, dostawały tytuł „Listing {inner_id}" i slug w postaci samego numeru ID zamiast `marka-model-rok-ID`.

**Diagnoza (API na żywo, nie intuicja):** auto-api.com zwraca dla części ofert **pustą wydmuszkę** — ten sam zestaw 33 kluczy, ale `mark`/`model`/`year`/`complectation`/`body_type`/`engine_type`/`images`/`extra_prep` = pusty string; wypełnione tylko `id`, `inner_id`, `url`, `price`, `created_at`. To oferty sprzedane/usunięte na Dongchedi (auto-api trzyma rekord z ceną, szczegóły znikają — pokrewne incydentowi crawlera z 25.05). `extractData()` działa poprawnie (nie błąd zagnieżdżenia).

**Root cause (nasz bug):** import ręczny woła `importListing(..., force=true)`, co omija filtr konfiguracji i preflight zdjęć W1 — żadna bramka nie zatrzymywała pustki. Efekt: tytuł `trim("")` → fallback „Listing {id}"; `buildListingSlug('','','',ID)` → same puste człony → slug = sam `post_id`; `extra_prep` pusty → blok parametrów pominięty; taksonomie tylko `condition` (default). Cena zapisywała się (jedyne wypełnione pole).

**Dowody:** 3 zepsute posty (`355913`, `355869`, `303690`) — wszystkie ręczne importy. API na żywo: `23958107`/`23984272` → wydmuszki; `23701521` (Denza N8L DM) → komplet, 402 parametry.

**Zmiany:**
1. `class-asiaauto-importer.php` — nowy helper `isEmptyShell()` (mark+model puste) + bramka w `importListing()` (po sprawdzeniu `inner_id`, przed filtrami) → `return null` + log. Chroni **wszystkie** ścieżki, też force.
2. `class-asiaauto-admin-manual-import.php` — guard w `ajaxImport()` (czytelny komunikat zamiast cichego null) + flaga `is_empty_shell` w `ajaxPreview()` + blok JS w podglądzie (blokada przycisku „Zaimportuj" + notatka, że oferta sprzedana/usunięta).

`buildListingSlug()` **nietknięty** — „dziwny slug" był objawem pustych danych, nie wadą slugu; bramka eliminuje przypadek u źródła. Backup: `*.bak-2026-06-05-emptyshell`. `php -l` czysty. Smoke: `isEmptyShell` blokuje `23958107`+`23984272`, przepuszcza `23701521`. Mirror `asiaauto.pl` nie istnieje — sync zbędny. **Sprzątanie 3 śmieci pending (osobno).**

## 0.32.67 — 2026-06-02 (Fix: panel admina kłamał „klient nie przesłał umowy" przy wielo-plikowym podpisie)

**Zgłoszenie:** Ruslan — realny klient (Miron Orłowski, zamówienie Denza N9 `#351079`) podpisał umowę Profilem Zaufanym i przesłał ją przez panel, ale karta zamówienia w wp-admin pokazywała „Podpisana umowa: Brak — klient jeszcze nie przesłał".

**Diagnoza (dane realne, nie intuicja):** umowa była na miejscu — załącznik `351149` (`AA-2026-0015-…-podpisana.pdf`), status zamówienia `podpisane`, log: „Klient potwierdził przesłanie podpisanej umowy (1 plik(ów))" 2026-06-02 14:44. Bug był wyłącznie wizualny.

**Root cause:** podpisana umowa wspiera **wiele plików** (wielostronicowy skan) i jest zapisywana jako **JSON-array** (`_order_signed_attachment_id` = `[351149]`, zapis przez `wp_json_encode` w `order-api.php:577/658`). `getOrderData()` czytało tę meta przez `(int) get_post_meta(...)`, a `(int) "[351149]" === 0` w PHP → render karty (`order-admin.php`) wpadał w gałąź „Brak". Kod klienta (REST `getSignedAttachmentIds`) i regeneracja umowy parsowały JSON poprawnie — stąd status doszedł do `podpisane`, ale karta admina pokazywała sprzeczność (badge „podpisane" + „Brak").

**Audyt zakresu (cały plugin + motyw):** dokładnie **jedno** zepsute miejsce odczytu (`order.php:1457`) i jego jedyny konsument (render `order-admin.php`). Bliźniak `payment_proof` zapisywany jako pojedynczy int (jeden plik z założenia) — `(int)` cast tam **poprawny**, bez buga. Motyw `asiaauto` — zero odwołań do tej meta.

**Zmiany:**
1. `class-asiaauto-order.php` — nowy helper `parseSignedAttachmentIds()` (mirror `order-api.php:845`, w modelu); `getOrderData()` zwraca `signed_ids` (tablica) + `signed_id` (pierwszy plik, wsteczna kompatybilność).
2. `class-asiaauto-order-admin.php` — wiersz „Podpisana umowa" renderowany w pętli po `signed_ids` (pokazuje wszystkie pliki; „Brak" tylko gdy realnie pusto).

Backup: `*.bak-2026-06-02-signed-array`. `php -l` czysty. Smoke: `getOrderData(351145)` → `signed_ids=[351149]`; `getOrderData(326921)` (Exeed, drugi dotknięty) → `[337660]`; stock bez podpisu → `[]`. Helper przetestowany: JSON-single/multi, legacy bare-int, `"0"`, `""`. Mirror `asiaauto.pl` już nie istnieje (czysty 301) — sync zbędny. ADR: `docs/decyzje/2026-06-02-fix-signed-contract-array-display.md`.

## 0.32.66 — 2026-06-02 (dataLayer `serie_id` pod dynamic remarketing Google Ads — Faza 2)

**Powód:** Faza 2 Google Ads (Display dynamic remarketing, feed model-hubów). Feed używa `id = serie term_id`, ale dataLayer `view_item` w `renderMeta()` wystawiał tylko `inner_id` auta — brak identyfikatora model-huba, więc GTM nie miał czym ustawić `dynx_itemid`.

**Zmiany (`class-asiaauto-single.php::renderMeta()`, addytywne):**
- Nowa zmienna `$serie_id` (= serie term_id, w idiomie sąsiednich termów).
- Nowy klucz `listing.serie_id` w dataLayer (obok `inner_id`). Czyta go tylko nowy DLV w GTM → `dynx_itemid`. Bloki `ecommerce`/`item_id`/schema/og **nietknięte** (GA4/Meta ViewContent bez zmian).

Backup: `class-asiaauto-single.php.bak-2026-06-02-dynx`. Smoke OK (single AITO M9 → `serie_id:5304` match z feedem hubów). `php -l` czysty, OPcache podchwycił od razu.

## 0.32.65 — 2026-05-30 (Spójność modelu: „zaliczka" → „depozyt" w 2 stringach user-facing)

**Powód:** model biznesowy Prima-Auto to **pośrednictwo** (umowa zlecenia sprowadzenia, **zwrotny depozyt gwarancyjny**, prowizja — Zleceniodawca/Zleceniobiorca), a nie sprzedaż z zaliczką. Audyt przy okazji przeglądu dokumentów prawnych (regulamin/polityka od prawnika Ruslana) wykrył 2 user-facing stringi wciąż mówiące „zaliczka", niezgodne z umową generowaną przez `class-asiaauto-contract.php` (tam wszędzie „depozyt").

**Zmiany:**

1. `class-asiaauto-order-api.php:729` — komunikat REST po potwierdzeniu umowy: „Oczekujemy na wpłatę **zaliczki**." → „...**depozytu**."
2. `class-asiaauto-order-content.php:32` — opis placeholdera `{deposit_amount}`: „Kwota **zaliczki**" → „Kwota **depozytu**".

**Nie ruszane:** komentarz/changelog w nagłówku `class-asiaauto-order-admin.php` (słowo „Zaliczka" jako ślad historyczny renamingu, nie user-facing). `.bak` pominięte.

**Kontekst szerszy:** obecny live regulamin (strona ID 153866) i polityka (ID 198526) też operują na błędnym modelu sprzedaży — czekają na poprawione dokumenty od prawnika Ruslana (zero wdrożenia hybrydy). Notka zwrotna z prawidłowym słownikiem modelu wysłana Ruslanowi.

---

## 0.32.64 — 2026-05-30 (Przywrócenie badge źródła umowy po pomyłce cache)

**Powód:** w v0.32.63 usunąłem badge „Indywidualna" / „Auto-generowana" na podstawie komentarza Janka „nie wiem po co jest pojawia się niezależnie od tego czy umowa jest generowana czy załącznikiem". Po refleksji: badge **działał poprawnie** (Chrome MCP weryfikacja: Stefan #350835 manual upload → „Indywidualna" pomarańczowy, Miron #350537 auto-gen → „Auto-generowana" niebieski). Janek miał cache CSS / nieodświeżone style — widział tylko pomarańczowy badge na różnych zamówieniach, stąd wrażenie że nie reaguje na typ umowy.

**Lesson:** „nie wiem po co jest" ≠ „usuń". Powinienem był najpierw wyjaśnić co badge robi i poczekać na decyzję.

**Zmiana:** revert `c999881` (`docs/VERSIONS.md`) + manualny rewert kodu w `renderCardContract()` — badge przywrócony 1:1 z v0.32.62. Smoke w Chrome: badge widoczny dla Stefana po `Ctrl+Shift+R`.

**Komentarz w kodzie** (jako ślad): `// v0.32.61 dodany, v0.32.63 usunięty, v0.32.64 przywrócony`.

---

## 0.32.62 — 2026-05-30 (UX uploadu własnej umowy: obok Regeneruj, zielone tło, +DOC/DOCX/ODT)

**Powód:** drobne UX poprawki po review v0.32.61. Janek poprosił o:
1. Toggle „Wgraj własną" obok przycisku Regeneruj/Wygeneruj (nie pod spodem)
2. Zielone tło sekcji uploadu (zamiast pomarańczowego)
3. Akceptacja plików `.doc`, `.docx`, `.odt` obok PDF (Word z LibreOffice też się liczy)

**Zmiany:**

1. **Nowa metoda `renderUploadCustomContractToggle(array $data, bool $has_contract)`** w `class-asiaauto-order-admin.php` — wydzielony toggle z formularzem. Wywoływana inline po przycisku Regeneruj (gdy umowa istnieje) lub po Wygeneruj PDF (gdy brak).
2. **CSS:** `<details>` z `display:inline-block` + `vertical-align:top`, summary jako zielony pill (`#C6F6D5` bg, `#9AE6B4` border, `#22543D` text). Formularz wypada pod summary jako blok (`F0FFF4` bg, zielona ramka).
3. **`accept` attribute** w input file:
   ```
   .pdf,.doc,.docx,.odt,application/pdf,application/msword,
   application/vnd.openxmlformats-officedocument.wordprocessingml.document,
   application/vnd.oasis.opendocument.text
   ```
4. **Handler `handleUploadCustomContract()`** — walidacja po **rozszerzeniu** (mime DOCX/ODT to ZIP, nie wiarygodne):
   ```php
   $allowed_exts = ['pdf', 'doc', 'docx', 'odt'];
   $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
   if (!in_array($ext, $allowed_exts, true)) → error
   ```
   `wp_handle_upload` z whitelistą mimes (4 typy) — dodatkowa walidacja po stronie WP.

**Nie ruszane:**
- Logika handlera (purge starego attachment, bump licznika, meta `_aa_contract_source`) — nadal działa
- Klient pobiera plik przez `wp_get_attachment_url()` → przeglądarka decyduje co zrobić (PDF otworzy się inline, DOC/DOCX/ODT pobierze i otworzy w Word/LibreOffice)

**Backupy:** `*.bak-2026-05-30-upload-custom-contract` (z poprzedniego patcha v0.32.61 — wystarczają).

**Lint:** czysty / Produkcja: 200 / `renderUploadCustomContractToggle` istnieje.

---

## 0.32.61 — 2026-05-30 (Generowanie PDF z `potwierdzone` + upload własnej umowy + walidacja numeru)

**Dwie sprawy do naprawienia w workflow po v0.32.59:**

1. **Brak ręcznego generowania umowy zanim Ruslan wyśle ją klientowi.** Po wyłączeniu auto-advance w v0.32.59 status zostaje `potwierdzone`, ale przycisk „Wygeneruj PDF" pokazywał się dopiero od `umowa_gotowa`. Jedyna ścieżka: zmiana statusu → PDF generuje się automatycznie + mail do klienta leci jednocześnie. Brak chwili na podgląd.

2. **Ruslan czasem przygotowuje własną umowę poza systemem** (klient zagraniczny ze specyficznymi załącznikami, indywidualne klauzule, klient w biurze podpisał inną wersję). Wcześniej taka umowa szła mailem off-system → brak śladu w bazie, status zalegał na `potwierdzone`, klient nie miał PDF w panelu.

**Patch (4 pliki):**

1. **`class-asiaauto-order-admin.php::renderCardContract()`** — przycisk „Wygeneruj PDF" widoczny też dla statusu `potwierdzone` (z guardem `isCustomerDataComplete=true`). Komunikat „(uzupełnij dane klienta, żeby wygenerować)" gdy bramka nie spełniona. PDF powstaje, status zostaje `potwierdzone` — Ruslan ogląda, ewentualnie regeneruje, dopiero potem dropdown na `umowa_gotowa` → hook widzi że PDF istnieje (`$existing > 0 && get_post()` linia 73) → nie generuje znowu → tylko mail leci.

2. **`class-asiaauto-order-admin.php::renderCardContract()`** — nowa sekcja `<details>` „Wgraj własną umowę" widoczna dla statusów `potwierdzone+`:
   - Pole upload PDF (max 20MB, walidacja `mime_content_type === application/pdf`)
   - Pole text „Numer umowy" (default: `previewNextContractNumber()`)
   - Plus badge w UI: „Indywidualna (wgrana ręcznie)" pomarańczowy vs „Auto-generowana" niebieski

3. **`class-asiaauto-order-admin.php::handleUploadCustomContract()`** — nowy handler:
   - Walidacje: numer non-empty, max 50, unikalny (`isContractNumberInUse`); plik PDF, max 20MB, mime check
   - `wp_handle_upload` + `wp_insert_attachment` z `post_parent=$order_id`
   - Stary `_order_contract_attachment_id` (jeśli istniał) — `wp_delete_attachment(true)` (purge)
   - Meta: nowy `META_CONTRACT_ID`, `META_CONTRACT_NUMBER`, **`_aa_contract_source = 'manual_upload'`**
   - **Bumpnij licznik** przez `bumpContractCounterFromManual()` jeśli numer w formacie `AA/YYYY/NNNN` z NNNN > current
   - Status NIE zmieniany, mail NIE leci

4. **`class-asiaauto-order.php`** — 3 nowe helpery statyczne:
   - `previewNextContractNumber()` — `AA/YYYY/NNNN+1` bez inkrementacji licznika (pre-fill formularza)
   - `isContractNumberInUse(string $number, int $exclude_order_id = 0)` — `SELECT post_id FROM postmeta WHERE meta_key=_order_contract_number AND meta_value=$number AND post_id != $exclude`
   - `bumpContractCounterFromManual(string $number)` — jeśli pasuje regex `^{prefix}/(\d{4})/(\d+)$` i numer > licznik dla tego roku → `update_option(counter_prefix_YYYY, N)`. Log info.

5. **`class-asiaauto-order.php::changeStatus()`** — bramka `isCustomerDataComplete` w przejściu na `umowa_gotowa` **pomijana gdy `_aa_contract_source = 'manual_upload'`**. Powód: Ruslan zna dane klienta z PDF, user_meta może być niekompletny.

**Hotfix smart quotes** — initial deploy padł `Parse error` przez `"` w środku polskiego cytatu „Umowa gotowa". Naprawione przez polski cudzysłów zamykający `”` (U+201D). Pierwsza lekcja [[feedback_smart_quotes_break_json.md]] dotyczyła JSON-a, teraz przypomnienie dla PHP stringów.

**Smoke (po deployu):**
- `previewNextContractNumber()` → `AA/2026/0014` (licznik = 13 po Mironie) ✓
- `isContractNumberInUse(AA/2026/0013)` → true ✓
- `isContractNumberInUse(CUSTOM-XYZ)` → false ✓
- `method_exists(handleUploadCustomContract)` → true ✓
- `https://primaauto.com.pl/` → 200 (po hotfix) ✓

**Backupy:** `*.bak-2026-05-30-upload-custom-contract` (3 pliki: order, order-admin, contract).

**Workflow Stefana Nicolae (#350835, RO) — dwie ścieżki dziś:**
1. *Templatem:* Ruslan klika „Wygeneruj PDF" (przycisk widoczny bo dane Stefana kompletne po v0.32.60 sesji chrome), ogląda PDF, OK → dropdown na „Umowa gotowa" → mail leci.
2. *Własną umową:* Ruslan rozwija sekcję „Wgraj własną umowę", wybiera plik PDF z dysku (wcześniej przygotowany w Word/Adobe), wpisuje numer (`AA/2026/0014` lub własny), klika „Wgraj umowę" → PDF zapisany. Potem dropdown na „Umowa gotowa" → mail z magic linkiem leci, klient widzi w panelu wgraną umowę zamiast template'owej.

---

## 0.32.60 — 2026-05-30 (Walidacja billing dla klientów zagranicznych — NIP/CUI 8-13 cyfr, kod pocztowy elastyczny)

**Powód:** Stefan Nicolae (zamówienie #350835, RO) podał Ruslanowi dane firmy rumuńskiej SC Burger Society SRL — CUI `46732411` (8 cyfr) i kod pocztowy `010025` (6 cyfr ciągiem). Walidacja w `saveCustomerData()` miała sztywne regexy pod PL: `^\d{10}$` dla NIP i `^\d{2}-\d{3}$` dla kodu — oba odrzucały rumuńskie dane. Ruslan nie mógł wpisać.

**Dry-run pokazał (przed fixem):**
- `46732411` → ODRZUCONE („NIP musi mieć 10 cyfr")
- `010025` → ODRZUCONE („Kod pocztowy: format XX-XXX")
- `+40763971874` → PRZEJDZIE (regex telefonu już międzynarodowy)

**Decyzja klienta (Janka):** zluzować globalnie, bez dropdowna kraju — najprostsze, działa dla większości EU bez UI changes.

**Zmiany:**

1. **`class-asiaauto-order.php::saveCustomerData()`** — luźniejsze regexy:
   - NIP/CUI: `^\d{8,13}$` (PL=10, RO=8, DE=9-11, CZ=8-10, IT=11, FR=9-11). Strip prefiksu kraju (`PL`/`RO`/`DE`/itd.) przed walidacją — wpisanie `PL1234567890` lub `RO46732411` też przejdzie.
   - Kod pocztowy: `^[A-Z0-9][A-Z0-9\s\-]{2,9}$` (case-insensitive). Akceptuje PL `XX-XXX`, RO `123456`, DE `12345`, CZ `123 45`, UK `SW1A 1AA`, US `12345`. Odrzuca <3 znaki i >10 znaków.

2. **`class-asiaauto-order-admin.php::renderCardCustomer()`** — podbita `maxlength` w formularzu admin:
   - NIP: `13 → 15` (z marginesem na prefiks), placeholder zaktualizowany („10 cyfr (PL) / 8 cyfr (RO CUI) / itd.")
   - Kod pocztowy: `6 → 10`, placeholder „PL: XX-XXX / RO: 6 cyfr / DE: 5 cyfr"

**Test po fixie (`/home/host476470/tmp/test-validation-after.php`):**
- NIP: PL ✓ / RO ✓ / DE ✓ / CZ ✓ / PL z prefiksem ✓ / RO z prefiksem ✓ / <8 cyfr ✗ / >13 cyfr ✗
- Kod: PL XX-XXX ✓ / RO 010025 ✓ / DE 10115 ✓ / CZ „110 00" ✓ / UK SW1A 1AA ✓ / US 90210 ✓ / <3 znaki ✗ / >10 znaków ✗

**Backupy:**
- `class-asiaauto-order.php.bak-2026-05-30-loose-international`
- `class-asiaauto-order-admin.php.bak-2026-05-30-loose-international`

---

## 0.32.59 — 2026-05-30 (Workflow zamawiania: usunięcie auto-advance + powiadomienie admina o wypełnieniu billing)

**Powód:** klient (Ruslan) chce sam decydować kiedy wystawić umowę. Obecnie gdy klient wypełnił komplet danych w wizardzie krok 3 (`/order/{id}/billing`), system automatycznie zmieniał status `potwierdzone → umowa_gotowa`, przydzielał numer kontraktu, generował PDF i wysyłał mail klientowi — Ruslan tracił kontrolę nad timingiem. Plus problem klientów zagranicznych (np. Stefan Nicolae, RO, zamówienie #350835 — firma SC Burger Society SRL z CUI/adresem rumuńskim), gdzie Ruslan musi dane przetłumaczyć/sprawdzić przed wystawieniem umowy.

**Cztery zmiany:**

1. **`class-asiaauto-order-api.php::submitBilling()`** — usunięty auto-advance `potwierdzone → umowa_gotowa`. Po wypełnieniu billing status zostaje `potwierdzone`. Sprzedawca sam zmienia status (bramka v0.32.54 sprawdzi komplet, hook wygeneruje PDF, mail do klienta poleci `status_umowa_gotowa`).

2. **`class-asiaauto-order.php::sendBillingCompletedEmail()`** — nowa metoda, wysyła mail do admina (Reply-To: klient) gdy klient wypełnił komplet danych przy statusie `potwierdzone`. Wywoływana z `submitBilling()` zamiast auto-advance. Wymaga `admin_email_notifications=1` w config.

3. **`class-asiaauto-order-content.php`**:
   - Nowy default template `customer_billing_completed` (subject + 9-linijkowy body) z `{admin_link}` do panelu zamówienia.
   - Dodany do `getTemplateKeyLabels()` jako „Klient wypełnił dane → Admin" (panel ustawień templates).
   - Poprawiona treść `status_potwierdzone` punkt 3: zamiast „Umowa zostanie wygenerowana automatycznie..." → „Sprzedawca przygotuje umowę po uzupełnieniu danych — otrzymasz osobne powiadomienie mailem gdy umowa będzie gotowa do podpisu."

4. **`submitBilling()` response message** — zamiast „Dane zapisane. Zaakceptuj warunki umowy." (sugerowało że umowa jest gotowa) → „Dane zapisane. Sprzedawca przygotuje umowę i powiadomi Cię mailem." Pole `step` zawsze 3 (nie skoczy na 4).

**Zachowane bez zmian:**
- Auto-regen PDF gdy klient poprawia dane na **już istniejącej umowie** (status `umowa_gotowa+`) — to inny use case, dane↔PDF muszą być spójne.
- Bramka v0.32.54 `isCustomerDataComplete` przy `changeStatus → umowa_gotowa` — działa.
- Maile statusowe po ręcznej zmianie statusu przez admina (klient dostaje `status_umowa_gotowa` z magic linkiem normalnie).

**Workflow docelowy:**
1. Ruslan klika „Potwierdź" → status `potwierdzone` → mail do klienta z magic linkiem (template z pkt 3 poprawiony, mówi „sprzedawca przygotuje umowę po uzupełnieniu")
2. Klient wpisuje dane → walidacja → zapis → komunikat „Sprzedawca przygotuje umowę i powiadomi Cię mailem"
3. **Mail do Ruslana** „Klient wypełnił dane → Admin"
4. Ruslan sprawdza w panelu (sekcja „Dane do umowy" v0.32.55 z badge'em Komplet), ewentualnie poprawia
5. Ruslan klika dropdown statusu → „Umowa gotowa" → bramka v0.32.54 OK → numer kontraktu + PDF + mail `status_umowa_gotowa` do klienta z magic linkiem

**Backupy:** `*.bak-2026-05-30-no-autoadvance` (3 pliki: order-api, order-content, order).

---

## 0.32.58 — 2026-05-28 (page.php H1 fix + cross-link „Galeria sprzedanych aut" na single listing)

**Cel:** naprawa duplikatu tytułu w edytorze Gutenberga `/klienci/` (post_title pole + wp:heading {level:1} w content) + cross-link do galerii klientów z każdego single listing.

**Zakres:**

1. **`themes/primaauto2026/page.php`** — dodany `<h1 class="entry-title"><?php the_title(); ?></h1>` przed `the_content()`. Wcześniej page.php renderował tylko content, więc tytuł postu nie pojawiał się jako H1 nigdzie na stronie. To był ukryty bug (audyt podczas tej sesji: 8 z 13 stron page.php nie miało H1 w ogóle). Po fixie: post_title jest H1 (standardowy WP).

2. **`themes/primaauto2026/functions.php`** — `PRIMAAUTO_THEME_VERSION` 1.0.6 → 1.0.7.

3. **`includes/class-asiaauto-contact.php`** linia 154 — H1 → H2 dla `aa-contact__hero-title` (uniknięcie duplikatu z `entry-title` z page.php). CSS selectorem `.aa-contact__hero-title` styling nieruszany.

4. **`includes/class-asiaauto-single.php`**:
   - `infoBox()` (sidebar desktop + mobile) — dodany 5-ty link `['Galeria sprzedanych aut', home_url('/klienci/')]`
   - `uspStrip()` kolumna „Informacje" — dodany 5-ty wpis `['icon' => 'dot', 'text' => 'Galeria sprzedanych aut', 'href' => home_url('/klienci/')]`

5. **WP page 350745 Klienci** — `post_title` cofnięty na pełną nazwę („Klienci Prima-Auto — auta, które dla nich sprowadziliśmy"), wp:heading {level:1} usunięty z content (duplikat z entry-title).

6. **WP pages 153875 (W drodze) + 153877 (W rzeszowie)** — usunięte `<h1>` z post_content (duplikat z entry-title).

**Stan H1 po fixie (smoke test wszystkich page.php pages):**
- ✅ klienci, kontakt (H2 teraz), polityka-prywatnosci, w-drodze, w-rzeszowie — 1 H1 z entry-title
- ⚠️ regulamin, o-nas, finansowanie, jezyk-obslugi-pojazdu, pod-dom-do-rejestracji, proces-zamawiania, gwarancja-i-serwis, homologacja-i-rejestracja — **nadal 0 H1** (te strony nie używają primaauto2026/page.php — pewnie Elementor Pro mimo `add_filter('elementor/theme/get_location_templates', '__return_empty_array')`. Wymaga osobnego audytu).

**Pending follow-up:** zdiagnozować dlaczego 8 stron nie używa naszego page.php (`pa-main` markup nieobecny). Możliwe sources: Elementor Pro Theme Builder mimo disable filtra, jakiś inny plugin/snippet, page templates ustawiane per page z UI.

**Backupy:** `*.bak-2026-05-28-h1` (page.php, class-asiaauto-contact.php), `*.bak-2026-05-28-klienci-link` (class-asiaauto-single.php).

---

## 0.32.57 — 2026-05-28 (Galeria klientów `/klienci/` — Gutenberg Gallery block, ZERO kodu)

> **Wieczorny rollback:** pierwotnie wdrożone jako custom page template `themes/primaauto2026/page-klienci.php` (~360 linii: PHP query + inline CSS grid 4/3/2 + vanilla JS lightbox + ImageGallery JSON-LD + theme bump 1.0.6→1.0.7). **Cofnięte.** Powód: Gutenberg ma `wp:gallery` z `imageCrop:true` + per-image `lightbox.enabled:true` (Interactivity API od WP 6.4+). Zero custom kodu, drag&drop dla Ruslana, edycja w wp-admin. Plik usunięty z theme i repo, `PRIMAAUTO_THEME_VERSION` z powrotem 1.0.6.

**Stan po rollbacku:**

1. **WP page `/klienci/`** (ID 350745) — content = bloki Gutenberga: `wp:heading {level:1}` (H1) + `wp:paragraph` (lead) + `wp:gallery {columns:4, imageCrop:true, linkTo:"none", sizeSlug:"medium_large"}` z 47 zagnieżdżonymi `wp:image` (każdy z `lightbox:{enabled:true}`) + `wp:heading {level:2}` + CTA. `_wp_page_template=''` (default `page.php`). Featured 350682, RankMath meta bez zmian.

2. **Menu „Klienci"** — pos 5 w `header`, `db_id=350746` (bez zmian).

3. **Zarządzanie galerią dla Ruslana:** wp-admin → Strony → Klienci → Edytuj. Blok Galeria → klik `+` żeby dodać z biblioteki, drag żeby przestawić, `×` żeby usunąć, „Aktualizuj". Koniec. Auto-discovery po nazwie usunięte — Gallery block trzyma listę ID-ków w post_content (`{"ids":[...]}`).

**Konsekwencje:**
- HTML 240KB (vs 143KB w custom) — Gutenberg dodaje `wp-block-library` CSS + Interactivity API JS dla lightboxa. Akceptowalne: 0.12s response time, lazyload native.
- Brak `ImageGallery` JSON-LD — Gallery block sam się indeksuje przez `<img>` + alty. Jeśli za 1-2 mies. okaże się brak signalu, dorzucimy filterem w functions.php.
- Kolejność = ręczna w edytorze (drag&drop), nie ASC po nazwie. Ruslan widzi co dodaje gdzie.

**Smoke test (rollback):** HTTP 200, 0.12s, 240KB, 47 `wp-block-image`, lightbox triggers via Interactivity API obecne.

**Backupy:** brak (rollback przez `wp eval-file` na ID 350745 + `git rm` theme files).

**Pending follow-up:**
- Cross-site linki do `/klienci/` (single listing, strona główna, `/zamow/`) — decyzja gdzie/jak.
- OG image dedykowany 1200×630 (obecnie #001 ~3:4).
- Banner z liczbami w hero — wymaga konkretu od Ruslana.

<details><summary>Historyczna treść (custom template — usunięte)</summary>

**Cel:** wdrożenie galerii social proof — 47 zdjęć klientów Prima-Auto z autami sprowadzonymi z Chin. Decyzje produktowe zamknięte 2026-05-27 (memory `project-client-gallery-consents`); user dorzucił batch zdjęć do biblioteki mediów (mask `klienci-prima-auto-NNN.webp`, 001-047).

**To NIE jest zmiana pluginu `asiaauto-sync`** — pełen zakres siedzi w themie `primaauto2026`. Wpisujemy pod 0.32.57 tylko dla spójności trackingu sesji. `ASIAAUTO_VERSION` w pluginie NIE bumpowane.

**Zakres:**

1. **`themes/primaauto2026/page-klienci.php`** (NEW) — Template Name „Klienci — galeria social proof". Query attachmentów: `post_type=attachment`, `post_mime_type=image/webp`, `s=klienci-prima-auto`, filter po `post_name` LIKE `klienci-prima-auto-*`, orderby title ASC. 47 unikalnych ID (350682-350728). Grid 4/3/2 col (desktop/tablet/mobile), kwadrat `aspect-ratio: 1/1` + `object-fit: cover` (rozwiązuje różne proporcje oryginałów bez letterboxa). Inline `<style>` + `<script>` (scope `.aa-klienci-*`, vanilla JS ~80 linii lightbox z klawiaturą + swipe touch).

2. **`themes/primaauto2026/functions.php`** — `PRIMAAUTO_THEME_VERSION` 1.0.6 → 1.0.7 (cache bust).

3. **WP page `/klienci/`** (ID 350745) — `post_status=publish`, `_wp_page_template=page-klienci.php`, featured image 350682 (klienci-prima-auto-001). RankMath meta: `title`, `description`, `facebook_title`, `facebook_description`, `facebook_image_id=350682`, `twitter_use_facebook=on`.

4. **Menu `header`** (term_id 6033) — nowa pozycja „Klienci" na pozycji 5, link `/klienci/`, między „Marki" (4) a „Informacje" (6). `db_id=350746`.

5. **Schema ImageGallery JSON-LD** — render warunkowy gdy `$gallery_items` niepuste. 47 `ImageObject` z `contentUrl` (full) + `thumbnailUrl` (medium_large) + `width`/`height` z `wp_get_attachment_image_src`.

**Decyzje techniczne:**
- **Brak osobnego CSS file** (tj. brak `klienci.css` enqueued) — inline `<style>` ładuje się tylko na tej stronie, nie obciąża globalnego CSS. Trade-off: trochę inline kodu vs. dodatkowy roundtrip dla 200 linii CSS.
- **Brak nowego JS file** — vanilla lightbox inline. Trade-off jw. Brak dependency na bibliotekę (Fancybox/GLightbox).
- **Lazyload native** (`loading="lazy"`), pierwsze 6 zdjęć `loading="eager"` — LCP-friendly dla above-the-fold.
- **A11y** — `<button>` zamiast `<a>` dla tile (akcja JS, nie nawigacja), `aria-label` per tile, `role="dialog"` na lightbox, `aria-hidden` toggle, focus return po close.

**Decyzje produktowe (auto-mode defaults wybrane przez Claude bez quiza):**
- Scope: publiczna `/klienci/` (zdjęcia w bibliotece = ten task), NIE admin tab opisany w `PROMPT-zakladka-klienci-2026-05-28.md`.
- Layout: kwadrat object-fit:cover (zgodnie z sugestią user'a „miniatury w kwadracie albo trzeba JAKIŚ ŁADNY wygląd").
- Lightbox: vanilla inline (brak istniejącego lightboxa w themie — grep nic nie znalazł).
- SEO: ImageGallery JSON-LD + neutralny lead (bez konkretnych liczb, bo Ruslan ich nie podał).

**Smoke test:**
- `https://primaauto.com.pl/klienci/` → HTTP 200, 0.18s, 143KB.
- 47 `<button class="aa-klienci-tile">` w renderze (grep `data-index=` == 47).
- 1 `ImageGallery` JSON-LD obecne.
- H1 prawidłowy (z post_title).

**Backupy:** brak (nowy plik `page-klienci.php`, jedyna zmiana w `functions.php` to bump wersji 1 char).

**Pending follow-up (NIE w tej wersji):**
- Cross-site linki do `/klienci/` (single listing, strona główna, `/zamow/`).
- OG image dedykowany 1200×630 (obecnie #001 ~3:4).
- Banner przy hero (konkretne liczby działalności — wymaga decyzji Ruslana).

</details>

---

## 0.32.56 — 2026-05-28 (Wycofanie pól „Typ dokumentu" + „Numer dokumentu" z UI i PDF)

**Decyzja klienta (Ruslan, 28.05):** pola „typ dokumentu" (dowód osobisty/paszport) i „numer dokumentu" nigdy nie były używane, komplikowały klientom wypełnianie umowy i są zbędne. Wycofujemy z UI i PDF.

**Zakres:**

1. **`class-asiaauto-order.php::CUSTOMER_META`** — `billing_id_type` i `billing_id_number` zmienione z `required: true` → `required: false`. Wpisy zostają w strukturze (getCustomerData() nadal je zwróci) na wypadek istniejących danych w user_meta starych klientów. `isCustomerDataComplete()` (iteracja po required=true) przestaje ich wymagać.

2. **`class-asiaauto-order-wizard.php::getBillingFieldDefs()`** — usunięte 2 wpisy z definicji pól wizard frontend. Klient nie widzi już tych pól w kroku „Dane do umowy".

3. **`class-asiaauto-order-admin.php`**:
   - `renderCardCustomer()` — usunięty blok `cols-id` z typem dokumentu (select) i numerem (input).
   - `handleUpdateCustomerBilling()` — usunięte 2 keys (`billing_id_type`, `billing_id_number`) z whitelist pól przyjmowanych z $_POST.

4. **`class-asiaauto-contract.php`**:
   - Mapowanie placeholderów (linie 254-255) — usunięte `customer_id_type` i `customer_id_num`.
   - Layout PDF (linia ~571) — usunięta linia `{customer_id_type}: {customer_id_num}<br>` z nagłówka „Zleceniodawca".

**Historyczne dane:**
- Wpisy w user_meta `billing_id_type` / `billing_id_number` zostają w bazie (nie czyścimy).
- 5 historycznych umów z dziurami (AA/2026/0006, 0008, 0011, 0012, 0013) — bez ruchu. Gdy ktoś ręcznie zregeneruje PDF, linia „dowód osobisty: ..." zniknie z dokumentu (bo placeholder już nie istnieje).
- 0 userów stało się „nagle kompletnymi" po fixie (smoke test) — brak side effectów na istniejące dane.

**`case 'billing_id_type'` w `saveCustomerData()`** — zostawiony jako defensywna walidacja (gdyby ktoś jednak wysłał payload z `billing_id_type` z innej drogi, sprawdza czy `dowod|paszport`). Nie szkodzi, w UI pola nie ma.

**Backupy:** `*.bak-2026-05-28-remove-id-doc` (4 pliki: order, order-wizard, order-admin, contract).

---

## 0.32.55 — 2026-05-28 (Formularz „Dane do umowy" w panelu zamówienia + auto-regen PDF)

**Problem (następstwo v0.32.54):** bramka v0.32.54 zaczęła blokować przejście na `umowa_gotowa` gdy klient nie ma kompletu billing — ale **nie istniało UI dla admina** do uzupełnienia tych danych. Funkcja `saveCustomerData()` była dostępna tylko przez REST endpoint `submitBilling` (wizard frontend dla klienta, z guardem `status === 'potwierdzone'`). W standardowym WP-admin → Users → Edit klient widać tylko WP-natywne pola, nie nasze `billing_pesel/nip/id_type/id_number/address_*`. Ruslan po wdrożeniu v0.32.54 zadzwonił, że klikał „edytuj użytkownika" i nie widzi gdzie wpisać dane.

**Fix v0.32.55 — trzy zmiany:**

1. **`class-asiaauto-order-admin.php` — nowa sekcja w karcie „Klient"** (`renderCardCustomer`): badge „Komplet"/"Niekompletne" + formularz inline z 4 sekcjami (Dane osobowe / Identyfikator / Adres / Firma). Layout: kod pocztowy 140px obok miasta, typ dokumentu 200px obok numeru, reszta 50/50. Submit → `handleUpdateCustomerBilling()` → woła istniejące `AsiaAuto_Order::saveCustomerData()`.

2. **Auto-regen PDF** w `handleUpdateCustomerBilling()`: po zapisie billing, jeśli `isCustomerDataComplete()` zwraca true i status zamówienia to `umowa_gotowa`/`podpisane`/`zarezerwowane`/`zakupione`/`w_drodze`/`na_placu`/`w_dostawie` → `AsiaAuto_Contract::regenerate($order_id)`. Powód: dla zamówień z dziurami z czasu sprzed v0.32.54 (5 historycznych umów: AA/2026/0006, 0008, 0011, 0012, 0013) admin wpisuje dane → PDF się odświeża jednym kliknięciem zapisu, bez konieczności osobnego „Regeneruj umowę".

3. **`class-asiaauto-order-api.php` — rozluźniony guard `submitBilling`:** klient może teraz edytować billing w statusach `potwierdzone`/`umowa_gotowa`/`podpisane`/`zarezerwowane`/`zakupione`/`w_drodze`/`na_placu`/`w_dostawie` (wcześniej tylko `potwierdzone`). Auto-advance `potwierdzone → umowa_gotowa` zostaje (historyczne zachowanie). Plus analogiczny auto-regen PDF jak w (2) — gdy klient sam poprawia dane w wizardzie po wygenerowaniu umowy.

**Backupy:** `class-asiaauto-order-admin.php.bak-2026-05-28-customer-billing-form`, `class-asiaauto-order-api.php.bak-2026-05-28-customer-billing-form`

**Lessons learned (auto-memory):** w mailu do Rusłana z 27.05 obiecałem „uzupełnij dane ręcznie w panelu admina" zakładając, że UI istnieje skoro `saveCustomerData()` jest w kodzie — bez grep'a po pliku order-admin. Funkcji w UI nie było. Konfabulacja, którą Ruslan wykrył dzwoniąc 28.05. Patrz `feedback_no_unverified_ui_claims.md`.

---

## 0.32.54 — 2026-05-27 (Bramka kompletności danych klienta przed `umowa_gotowa`)

**Problem:** umowa `AA/2026/0013` (Miron Orłowski) wygenerowana z pustym adresem i napisem „dowód osobisty:" bez numeru. Admin (Ruslan) przeszedł `potwierdzone → umowa_gotowa` 2 minuty po `weryfikacja → potwierdzone`, klient nie zdążył (i nie musiał) wypełnić kroku 3 wizardu z billing. PDF wygenerował się automatycznie z fallbackami: pusty adres, ternary `?? 'dowod'` wstawił „dowód osobisty" mimo że nic nie wybrano.

**Root cause:** `AsiaAuto_Order::changeStatus()` sprawdzała tylko graf `TRANSITIONS`, nie wołała `isCustomerDataComplete()` (funkcja istniała od dawna w `order.php:1069`, tylko nikt jej nie używał przy zmianie statusu).

**Fix (`class-asiaauto-order.php::changeStatus()`):** bramka po sprawdzeniu TRANSITIONS — gdy `$new_status === 'umowa_gotowa'` i `$order_type === TYPE_CUSTOMER` i `!isCustomerDataComplete($customer_id)` → `WP_Error('customer_data_incomplete', ...)`. Komunikat wskazuje administratorowi gdzie uzupełnić (krok wizardu klienta lub panel admin).

**Stock orders nietknięte:** `createInternal()` wchodzi w `w_drodze`/`na_placu`/`zakupione`/`zarezerwowane` — graf TRANSITIONS z tych statusów nie prowadzi do `umowa_gotowa`. Guard `$order_type === TYPE_CUSTOMER` = defense in depth.

**Stan po deployu:**
- 1 customer order natychmiast zablokowany: `#339595` (BYD Leopard 5, Agnieszka Koman, `potwierdzone`, brak billing) — admin musi poprosić klientkę o uzupełnienie lub wypełnić ręcznie.
- 5 historycznych umów z dziurami (AA/2026/0006, 0008, 0011, 0012, 0013) zostaje — forward-only fix, naprawiane per case.

**Backup:** `class-asiaauto-order.php.bak-2026-05-27-customer-data-gate`

---

## 0.32.53 — 2026-05-20 (Generyczne redirecty 404 — porządkowanie GSC)

**Cel:** wyczyścić ~1300 trafień 404 Googlebota/dzień (martwe huby + sprzedane listingi). Przyczyna: stare slugi sprzed importer slug fix (v0.32.42) + listingi trwale usunięte (>30d, poza zasięgiem detectListingNotFound).

**Dodane do `class-asiaauto-redirects.php` — generyczne, samonaprawiające, BEZ hardcode i BEZ 410 dla hubów (bo modele wracają):**
- `redirectHubMakePrefix()` (template_redirect prio 1, tylko na is_404) — 3 warstwy dla hubów `/samochody/<make>/<serie>/`:
  1. **Prefiks marki**: `changan/changan-uni-z` → odcina `changan-` → 301 na `uni-z` (get_term_link, kanoniczny URL).
  2. **Normalized match**: `sea-lion-07dm` → `sealion-7-dm`, `seal-05-dm` → `seal-5-dm` (normalizacja: usuń myślniki + wiodące zera; tylko exact-norm, nie prefix).
  3. **Brand fallback**: model martwy/zmieniony, marka żyje → 301 na hub marki. Warunkowane is_404 → gdy model wróci (term=200), redirect się NIE odpala. Zero blokady powrotu.
  + feed strip: `/samochody/<x>/feed/` → 301 na hub bez feed (główny `/feed/` bloga nietknięty).
- `resolveHubFromSlug()` + sam-make case — parsuje markę+model ze slugu listingu (longest-prefix po slugach marek), działa po trwałym usunięciu posta. Wpięte w `detectListingNotFound`: 301 hub modelu → hub marki. Listing bez modelu (`haval-2025-id`) → hub marki.
- `send410()` — 410 Gone TYLKO dla listingu (sprzedany egzemplarz NIE wraca) bez żadnej rozpoznawalnej marki. Huby nigdy nie dostają 410.
- helpery: `serieLinkBySlug()`, `normalizeSlug()`, `findSerieByNormalizedSlug()`, `getMakeSlugs()` (cache per-request).

**Wynik (pomiar curl 683 unikalnych 404 z logów maja):** **677 → 301 (99,1%)**. Zostaje 5 śmieci (U+2060 w URL, listing bez roku) = 404 słusznie. 0 niepożądanych 410. Regression: żywe huby/listingi/feed bloga = 200; hub `super-v23` ożył naturalnie (nowy import → 200).

**Backup:** `class-asiaauto-redirects.php.bak-2026-05-20-pre-generic-404`, `*-pre-brand-fallback`

**Zdiagnozowane przy okazji (osobne taski, NIE w tej wersji):**
- **1876/2239 serie z count=0** (puste huby) — NIE w sitemapie (RankMath wyklucza puste), więc nie zgłaszane Google. Do rozważenia: noindex gdy count=0 (samonaprawiające).
- rewrite slug taksonomii `serie` = `model` → niektóre `get_term_link` dają 2-hop chain (`/model/i6/` → `/samochody/li-auto/i6/`). Minor.

## 0.32.51 — 2026-05-20 (Indexing API — wycięcie URL_DELETED, go-live URL_UPDATED-only)

**Cel:** hook Indexing API (v0.32.49) zawierał niezlecony `trash→URL_DELETED` (scope creep). Wycięty — hook zgłasza wyłącznie URL_UPDATED na publish nowego ogłoszenia. Sprzedaż (publish→trash) obsługuje 301-na-hub w `class-asiaauto-redirects.php`.

**Powody wycięcia:** niezlecony + sprzeczny z 301 (Google idzie za realnym HTTP) + bug (`get_permalink()` trashowanego posta zwraca URL z `__trashed`, nigdy nieindeksowany) + marnował wspólną quotę Indexing API (200/dz per GCP project, dzielona z innymi projektami).

**Pliki:**
- `includes/class-asiaauto-indexing.php` — `resolveNotificationType()`: usunięty branch `trash→TYPE_DELETED` (zwraca `null`). Stała `TYPE_DELETED` pozostaje (nieużywana). Docblock zaktualizowany.

**Go-live (2026-05-20 ~09:16):** `asiaauto_indexing_enabled=1`, armed. Live test ID 340966 → HTTP 200. Bez daily cap (decyzja: hook rozkłada ~162/dz na 24h, praca 8-18).

**Backup:** `class-asiaauto-indexing.php.bak-2026-05-20-pre-urldelete-cut`

**ADR:** `docs/decyzje/2026-05-20-indexing-api-url-update-only.md`

## 0.32.50 — 2026-05-19 (Hub marek — pivot title na agregator-style Wariant C)

**Cel:** ujednolicić title hub marek z formatem hub modeli (działający, 60% fraz `{model} import` w DFS top 10). Hub marek miały dotychczas `{Make} — Auto z Chin | Prima-Auto` — bez ceny i licznika. Pivot na `{Make} — od {min} PLN, {count} sztuk | Import z Chin | Prima-Auto`.

**Decyzja oparta na danych:**
- GSC 28d, 10 top hubów marek: **0% impressions z „import"**, 254 bez. Pivot na poziomie marki NIE z powodu „import" jako KW (zero search demand) ale z powodu spójności wzorca + dodanie ceny+count (intent „cena" wszechobecny w GSC).
- Wariant C wybrany przez Janka: „szkoda marnować znaków" — pełne aggregator-style.

**Pliki:**
- `includes/class-asiaauto-hub-title-generator.php` — rozszerzenie:
  - `regenerateForMakeTerm(int $term_id)` — odpowiednik `regenerateForTerm` dla `make`
  - `regenerateAllMakes()` — bulk wszystkie marki z count > 0
  - `buildMakeTitle()` + `buildMakeDescription()` — wzorce
  - `getMakePriceRange()` — SQL JOIN przez `make` taxonomy (zamiast `serie`)
  - `pluralizeOferty()` — dla description
  - `brandSlugToDisplay()` zmienione z `private` na `public` (utility)
  - Hook `asiaauto_after_set_taxonomies` rozszerzony: po imporcie regen + serie + make
  - Daily cron `asiaauto_regen_hub_titles_daily` woła obie funkcje
- `cli/class-asiaauto-cli.php` — nowy sub-command `regen-make-titles [--all|--term=<id>] [--dry-run]`

**Backup:**
- `class-asiaauto-hub-title-generator.php.bak-2026-05-19-pre-make`
- termmeta dump: `~/backups/primaauto/2026-05-19-make-titles/before-bulk.tsv`

**Wynik bulk:** 61/61 marek z `count > 0` zaktualizowane (100%). Dystrybucja długości title:
- ≤60ch: 11
- 61-70ch: 47
- >70ch: 3 (Dongfeng Fengshen 73, Dongfeng Fengxing 73, Beijing Off-Road 72)
- Avg 63, max 73

**Live verification:** 3 sample URL-i sprawdzone (BYD/Geely/Volkswagen) — title + description renderują się natychmiast (zero RankMath cache).

**Co NIE ruszono:**
- Hub modeli (~340) — już Wariant C, działa (DFS 60% top 10)
- Single (3915) — GSC 0% imp z „import", 2.7% z „cena", marginal ruch — bez wartości pivotu
- 18 marek bez listings publish (Mercedes-Benz, Rolls-Royce, Aston Martin itd.) — count=0, fallback WP `{TermName} - {SiteTitle}` (osobny temat: noindex empty hubów)

**Memory cross-link:** [[project_session_2026_05_19_make_titles_pivot]], [[project_session_2026_05_07_seo]] (kontekst v0.32.43 generator dla serie).

---

## 0.32.49 — 2026-05-19 (Indexing API integration — przygotowane, DEFAULT OFF)

**Cel:** zamiast batch'owego pushu single listings do Google Indexing API (jak rano 2026-05-19, 192 URL wyczerpało quota), wstawiamy hook `transition_post_status` — każde nowe ogłoszenie zaraz po publish wysyła URL_UPDATED do Indexing API, każde przejście do trash wysyła URL_DELETED. Naturalna, real-time integracja. Quota Google 200/dzień mieści się w ~150-200 sync/dzień bez wybuchów batch.

**Status: WYŁĄCZONA do ręcznego włączenia.** Procedura włączenia: `tmp/indexing-api-go-live-2026-05-20.md`. Włączenie zaplanowane 2026-05-20 po 02:00 PL (po reset quota).

**Pliki:**
- `includes/class-asiaauto-indexing.php` — NOWA klasa `AsiaAuto_Indexing_API`:
  - hook `transition_post_status@20` → `onTransition()` (publish/trash dla CPT `listings`)
  - cron godzinny `asiaauto_indexing_retry_cron` → `processRetryQueue()` (max 50/run, retry 5×, stop na 429)
  - `getAccessToken()` — OAuth refresh z `~/secrets/google/{oauth-desktop-client.json,tokens.json}`, transient cache 50 min
  - `callApi()` — POST `indexing.googleapis.com/v3/urlNotifications:publish` z URL_UPDATED/DELETED
  - guard `isEnabled()` (option `asiaauto_indexing_enabled`, default false) + `isArmed()` (option `asiaauto_indexing_armed_after_utc`)
  - logi przez `AsiaAuto_Logger`
- `asiaauto-sync.php` — `require_once class-asiaauto-indexing.php` + `new AsiaAuto_Indexing_API()` w `plugins_loaded`
- `cli/class-asiaauto-cli.php` — 3 sub-commands:
  - `wp asiaauto indexing-test --id=<id> [--type=URL_UPDATED|URL_DELETED] [--live]` (dry-run domyślnie, OAuth refresh test)
  - `wp asiaauto indexing-status` (queue size, flags, cron schedule)
  - `wp asiaauto indexing-drain` (manual drain retry queue, respektuje quota/guards)

**Backups:**
- `asiaauto-sync.php.bak-2026-05-19-pre-indexing`

**Bezpieczniki (option ustawione 2026-05-19):**
- `asiaauto_indexing_enabled = 0`
- `asiaauto_indexing_armed_after_utc = 2026-05-20T00:00:00Z`

**Dry-run test 2026-05-19:** post #338530 (geely-galaxy-l6) — OAuth OK (token 254), oba guard'y blokują, success, zero API call. Quota Google nietknięta.

**Memory cross-link:** `[[project_session_2026_05_18_seo_hubs_in_progress]]`, `[[reference_google_seo_stack]]`.

---

## 0.32.48 — 2026-05-16 (W3: filtr „Ręczny import" w admin views)

**Cel:** w `edit.php?post_type=listings` dodać link „Ręczny import (X)" obok natywnych „Wszystkie | Moje | Opublikowane | Szkice | Kosz". Filtruje po `_asiaauto_manual_import=1` (TYLKO listings dodane przez UI „Dodaj z Dongchedi"). Bez ograniczenia po autorze — admin i Ruslan widzą tę samą listę.

**Pliki:**
- `includes/class-asiaauto-admin-listings-views.php` — NOWA klasa `AsiaAuto_Admin_Listings_Views`:
  - hook `views_edit-listings` → `addManualImportView()` (dodaje link)
  - hook `pre_get_posts` → `filterManualImportQuery()` (ustawia `meta_query` gdy `?asiaauto_view=manual_import`)
  - prywatna `countManualImports()` z DISTINCT count na `_asiaauto_manual_import=1` (NOT IN trash, auto-draft)
- `asiaauto-sync.php` — `require_once` + `new AsiaAuto_Admin_Listings_Views()` w bloku `if (is_admin())`

**Backup:** `asiaauto-sync.php.bak-2026-05-16-pre-w3`

**Smoke test (`tmp/w3-smoke-test.php`):**
- Klasa ładowana via `require_once` ✓
- Hooki registered (`views_edit-listings@10`, `pre_get_posts@10`) ✓
- `countManualImports()=69` vs direct SQL=69 ✓ (po restore 8 listings)
- `addManualImportView()` dorzuca klucz `asiaauto_manual_import` ✓
- `filterManualImportQuery()` bez param → meta_query empty ✓; z `?asiaauto_view=manual_import` → `meta_query[0]={key:_asiaauto_manual_import, value:1, compare:=}` ✓

**Weryfikacja w admin:** `https://primaauto.com.pl/wp-admin/edit.php?post_type=listings` — link „Ręczny import (69)" widoczny, klik filtruje.

**Decyzja w `docs/decyzje/2026-05-16-ochrona-recznie-zarzadzanych-listings.md` (sekcja „W3").**

---

## 0.32.47 — 2026-05-16 (W1: sync guard — pomijaj ręcznie zarządzane listings)

**Problem:** `AsiaAuto_Sync::run()` sprawdzał tylko `_asiaauto_reservation_status` przed `updateListing()` (case `changed`) i nic przed `markRemoved()` (case `removed`). Ignorował flagi `_asiaauto_manual_import` (UI „Dodaj z Dongchedi", 71 listings) i `_asiaauto_manual_entry` (pierwszy zapis przez metabox „Dane pojazdu", 75 listings). Skutek: ogłoszenia które Ruslan dodawał ręcznie były wycofywane przez sync z powrotem do trash (`removal_reason=sold`).

**Fix:** w `class-asiaauto-sync.php` dodana prywatna metoda `isManuallyManaged(int $post_id): bool` (sprawdza obie flagi). Wstrzelona jako guard w `case 'changed'` (przed `updateListing`) i `case 'removed'` (przed `markRemoved`). Gdy listing jest manual: log `info("Sync skip: listing #X (inner_id: Y) is manually managed, skipping changed|removed")` + `$total_skipped++`.

**Pliki:**
- `class-asiaauto-sync.php:134-167` — 2 guard'y w switch-case + `$total_skipped++` per pominięty
- `class-asiaauto-sync.php:222-240` — nowa prywatna metoda `isManuallyManaged()`

**Backup:** `class-asiaauto-sync.php.bak-2026-05-16-pre-w1`

**Zasięg ochrony (81 unique aktywnych listings):**
- `_asiaauto_manual_import=1` → 71 sztuk (publish 54, draft 8, trash 9)
- `_asiaauto_manual_entry=1` → 75 sztuk
- Konkretne wzbudzenia (Ruslan edytował metabox): 249638 (BYD Yangwang U7), 306890 (Denza Z9 DM-i)

**Smoke test (`tmp/w1-smoke-test.php` przez Reflection):**
- Case 1 (manual_import=1 #260409) → `true` ✓
- Case 2 (manual_entry=1 bez import #222255) → `true` ✓
- Case 3 (normalny sync-owy #94073) → `false` ✓
- Case 4 (planned-protect 249638, 306890) → `true` ✓ (oba)
- Real `wp asiaauto sync --source=dongchedi` — brak fatal po patchu (0 zmian z API w tej iteracji)

**Decyzja w `docs/decyzje/2026-05-16-ochrona-recznie-zarzadzanych-listings.md` (sekcja „W1").**

---

## 0.32.46 — 2026-05-16 (W2: fix DUP_BLOCKED_META — kopia nie dziedziczy rezerwacji)

**Problem:** `AsiaAuto_Listing_Editor::handleDuplicate()` przy duplikacji kopiowała wszystkie meta poza wąską blocklist. Kopia dziedziczyła `_asiaauto_reservation_status` + `_asiaauto_reservation_order_id` → blokada utworzenia drugiego zamówienia na ten sam res_order_id („order się zduplikował"). Dowody w DB (2026-05-16):
- 303534 + 314155 → `res_order_id=303657` (Denza Z9 GT DM-i)
- 317106 + 324822 → `res_order_id=317400` (BYD Leopard 7)

Dodatkowo kopia dziedziczyła historię sync-removal (`_asiaauto_removed_at`, `_asiaauto_removal_reason`, `_asiaauto_api_removed`) i flagi importu z UI „Dodaj z Dongchedi" (`_asiaauto_manual_import*`) — przekłamywało statystyki i mogło wywoływać późniejsze decyzje sync na kopię.

**Fix:** dopisane 8 kluczy do `DUP_BLOCKED_META` w `class-asiaauto-listing-editor.php`:
```php
'_asiaauto_api_removed',
'_asiaauto_removed_at',
'_asiaauto_removal_reason',
'_asiaauto_manual_import',
'_asiaauto_manual_import_at',
'_asiaauto_manual_import_by',
'_asiaauto_reservation_status',
'_asiaauto_reservation_order_id',
```

**Pliki:**
- `class-asiaauto-listing-editor.php:80-111` — rozszerzona stała `DUP_BLOCKED_META`

**Backup:** `class-asiaauto-listing-editor.php.bak-2026-05-16-pre-w2`

**Decyzja w `docs/decyzje/2026-05-16-ochrona-recznie-zarzadzanych-listings.md` (sekcja „W2").**

**Cleanup istniejących par (303534+314155, 317106+324822) — osobnym krokiem po smoke teście, z backupem mysqldump.**

---

## 0.32.45 — 2026-05-15 (umowa: „rok pierwszej rejestracji" zamiast „rok produkcji")

**Problem:** Umowa generowała w polu „§1 b) rok produkcji" i tabeli specyfikacji „Rok produkcji" wartość z taxonomy `ca-year`, która w praktyce trzyma **rok modelowy** (z pola `year` API Dongchedi). Dla aut sprzedawanych jako prawie-nowe (dealer rejestruje na siebie żeby zwolnić VAT, klient odbiera po 1-12 miesiącach) `year` API ≠ kalendarzowy rok produkcji. Klient #329788 zgłosił rozbieżność: auto wyprodukowane w 2024 (potwierdza VIN `LURMCWEY6RA017761` — 10. znak `R` = rok modelowy 2024 wg ISO 3779), pierwsza rejestracja 2025-01-01, umowa pokazywała „2025".

**Ustalenie diagnostyczne:** API Dongchedi nie zwraca osobnego pola „rok produkcji". Zwraca tylko `year` (rok modelowy), `reg_date` (pierwsza rejestracja) i `extra_prep.market_time` (data wprowadzenia modelu na rynek). Wszystkie trzy często się pokrywają i dla 23653477 wszystkie były „2025".

**Decyzja:** Zmiana etykiety w umowie na „rok pierwszej rejestracji" + podstawiamy rok z meta `registration_date` (format `DD/MM/YYYY` → ostatnie 4 cyfry). Fallback: `ca-year` gdy brak rejestracji w API (1.6% listingów w 14d — głównie nowe modele/dealer-stock bez `reg_date`).

**Pliki:**
- `class-asiaauto-contract.php:327` — `'year' => self::extractRegistrationYear($listing_id, $get_term('ca-year'))`
- `class-asiaauto-contract.php` — nowa metoda `extractRegistrationYear(int $listing_id, string $fallback_model_year): string` (regex `#/(\d{4})$#` na meta `registration_date`, fallback na rok modelowy)
- `class-asiaauto-contract.php:604` — `<tr><td>b)</td><td>rok pierwszej rejestracji: ...</td></tr>` (było: „rok produkcji")
- `class-asiaauto-contract.php:975` — `['Rok pierwszej rejestracji', ...]` w tabeli specyfikacji (było: „Rok produkcji")

**Weryfikacja:** Test live `extractRegistrationYear()`:
- post 329788 (z `registration_date=01/01/2025`) → `2025` ✓
- post 328905 (bez `registration_date`, świeży Avatr 11) → fallback do `ca-year` ✓
- post 0 (nieistniejący) → fallback ✓

**Pokrycie 14d (2026-05-01 → 2026-05-15):** 2067/2101 listings (98.4%) ma `registration_date` → poprawna wartość w umowie. 34/2101 (1.6%) fallback do roku modelowego — wartość sensowna, choć etykieta wtedy lekko niespójna. Trade-off akceptowalny, problem dotyczy tylko świeżych dealer-stock przed pierwszą sprzedażą.

**Decyzja w `docs/decyzje/2026-05-15-rok-rejestracji-zamiast-produkcji.md`.**

---

## 0.32.44 — 2026-05-13 (bugfix suggestClientCif — match ceny katalogowej)

**Bug:** `AsiaAuto_Order::suggestClientCif()` używała liniowego transferu marży (`prowizja_wewn - §3`) → dopłaty do CIF. Wzór nie kompensował że pipeline B (`calculateOrderPrice()` — umowa) ma inną podstawę cła (CIF zamiast CIF+agencja) i VAT (CIF+cło zamiast pełnej bazy z pipeline A).

**Skutek:** Jak admin wpisał sugerowaną wartość w pole "CIF dla klienta" i wygenerował umowę, Załącznik nr 2 pokazywał "Szacowany łączny koszt sprowadzenia" **~1-2 tys. zł niższy** niż cena widoczna na ofercie samochodu. Klient zgłaszał rozbieżność (#315462 Deepal: 171k vs 173k, #323757 BYD: 200k vs 202k).

**Fix — nowy wzór odwrotny do `calculateOrderPrice()`:**
```
cif_pln_target = (subtotal_A − fixed_pln) / M
M              = 1 + clo% + (1+clo%)·akcyza% + (1+clo%)·(1+akcyza%)·vat%
fixed_pln      = agencja + transport + homologacja + commission_gross
```

Dla phev/bev (akcyza=0%): M ≈ 1,353. Dla petrol (akcyza 3,1%): M ≈ 1,395. Multiplikator zależny od paliwa listingu.

**Pliki:**
- `class-asiaauto-order.php` ~817-880 — nowy wzór + legacy fallback gdy brak `subtotal_pln` w breakdown
- `class-asiaauto-contract.php` ~1033-1045 — w `renderAttachment2()` gdy `_order_client_cif_usd <= 0`, użyj `suggestClientCif()` zamiast raw `real_cif_usd` z breakdown listingu (PDF od razu pokazuje poprawną cenę, bez konieczności ręcznego wpisywania przez admina)
- `class-asiaauto-order-admin.php` ~1097 — UI label "daje cenę w umowie ≈ cena katalogowa" zamiast mylącego "CIF + marża"

**Weryfikacja:** 3 ręczne testy — wszystkie cena umowy = cena listingu co do 1 zł przed zaokrągleniem ceil/1000:

| Zamówienie | Paliwo | Listing | Sugestia nowa | Cena umowy | Match |
|---|---|---|---|---|---|
| #323757 BYD Sealion 8 | phev | 202 000 | 37 688 USD | 202 000 | ✓ |
| #315462 Deepal G318 | phev | 173 000 | 31 839 USD | 173 000 | ✓ |
| #323747 smoke test | petrol | 176 000 | 31 601 USD | 176 000 | ✓ |

**Dla istniejących zamówień (status `weryfikacja`/`potwierdzone`/`umowa_gotowa`):**
- Te z `_order_client_cif_usd = 0` (admin nie wpisał) → po deployu automatycznie pokażą poprawną cenę w umowie (fallback w `contract.php`)
- Te z `_order_client_cif_usd > 0` (admin wpisał starą sugestię) → trzeba odświeżyć panel, wpisać nową sugerowaną wartość (UI pokaże poprawną liczbę), zapisać → auto-rekalkulacja `_order_price_final` → "Regeneruj umowę" → klient po zalogowaniu zobaczy nowy PDF

**Uwaga regen:** Regeneracja umowy na statusie != `podpisane` NIE wysyła automatycznie maila do klienta (linia 332-346 `handleGenerateContract`). Admin musi powiadomić klienta ręcznie.

**Backup:** `.bak-2026-05-13-cif-suggest` per file.

---

## 0.32.43 — 2026-05-07 (auto-regen hub titles — agregator pattern)

**Nowa klasa:** `class-asiaauto-hub-title-generator.php` (~200 linii) — generator title + description dla hub modelu na bazie aktualnych count + min/max(price).

**Wzór title:** `{Brand} {Model} — od {min_price} PLN, {count} sztuk | Import z Chin | Prima-Auto`

Przykłady:
- `BYD Atto 2 — od 104 000 PLN, 15 sztuk | Import z Chin | Prima-Auto`
- `Geely Preface — od 97 000 PLN, 53 sztuki | Import z Chin | Prima-Auto`
- `AITO M9 — od 290 000 PLN, 89 sztuk | Import z Chin | Prima-Auto`

**Kluczowe features:**
- **Brand prefix auto-add** — gdy term name nie zawiera marki (np. „Preface" → „Geely Preface"), dodawany z `_asiaauto_primary_make_slug` lub parent term
- **Polish pluralization** — 1 sztuka / 2-4 sztuki / 5+ sztuk
- **Brand display map** dla special cases — BYD/GAC/MG/AITO/NIO (allcaps), XPeng/HiPhi/iCAR (mixed), Mercedes-Benz/Land Rover/Lynk & Co (multi-word)
- **Skip flag** — `_asiaauto_skip_title_regen=1` per term blokuje regen (manual override)

**Hooks:**
- `asiaauto_after_set_taxonomies` — wywoływany w `class-asiaauto-importer.php:580` po `setTaxonomies()` per importowany listing → regen wszystkich serie terms którym ten listing należy
- `asiaauto_regen_hub_titles_daily` — daily cron 04:00 lokalnego czasu (catch-up)

**WP-CLI:**
- `wp asiaauto regen_hub_titles --all` — bulk regen wszystkich
- `wp asiaauto regen_hub_titles --term=<id> --dry-run` — test pojedynczego

**Bulk regen executed 2026-05-07:** **333 hubów** updated (wszystkie z `count > 0`). Smoke test 5 random URL'i: title format poprawny, brand prefix gdzie trzeba, cena+count z DB.

**Co straciliśmy** (trade-off vs spójność): 15 ręcznie tunowanych dziś hubów straciło custom USP w title (np. „50% taniej niż salon" dla BYD Sealion 7, „Hybryda 1400KM" dla Zeekr 9X). USP nadal w description. Jeśli chcemy custom USP w title per hub — można później dodać `_asiaauto_title_suffix` opcjonalny.

**Reasoning** (dlaczego dynamic title):
- Backlinko 2022: title z ceną → +15% CTR dla commercial queries
- Otodom/AAAauto/Allegro używają tego wzoru i rangują top 10
- Cena min zmienia się rzadko (~tygodnie), count codziennie → daily cron rozwiązuje stale info
- LLM-y (ChatGPT/Perplexity/AI Overviews) cytują dosłownie konkretne fakty z title → AEO benefit

---

## 0.32.42 — 2026-05-07 (v6.2 residuals cleanup phase 2: importer fix + bucket B 15)

**Kluczowy systemowy fix — importer ignorował `slug` field z brand-mapping:**

`class-asiaauto-importer.php:514` — `setTaxonomies()` przekazywał do `setTaxonomyAndMeta()` tylko 3 argumenty (post_id, taxonomy, value). Bez 4-go argumentu `$api_value` slug auto-derive'ował się przez `slugify($value)` z labela "ATTO 3 (Yuan PLUS)" → `atto-3-yuan-plus`. **Pole `'slug' => 'atto-3'` z brand-mapping-v6.1.php było dead code.**

Fix (1 linia):
```php
$serieSlug = isset($eu['slug']) ? (string) $eu['slug'] : '';
$this->setTaxonomyAndMeta($post_id, $this->tax_model, $serieFinal, $serieSlug);
```

Od v0.32.42: każdy nowy listing z mapowaną parą CN→EU dostaje keeper-slug → zero nowych orphanów dla mapped combinations. Listings podejmowanych UPDATE też zostaną reasiagned do keepera przy następnym sync'u.

**Bucket B — 15 termów wykonane (10 merge + 5 parent fix):**

MERGE do existing keepera (10):
- `seal-u-dm-i-song-plus` (13) → `seal-u-dm-i` (28 total)
- `atto-3-yuan-plus` (10) → `atto-3` (23)
- `leopard-3-tai-3-fcb` (8) → `leopard-3` (19)
- `sealion-8-dm-i-tang-l` (2) → `sealion-8-dm-i` (14)
- `sealion-8-tang-l-ev` (2) → `sealion-8-ev` (3) — EV osobno od PHEV
- `leopard-5-denza-b5` (1) → `leopard-5` (9)
- `leopard-7-tai-7-fcb-phev` (1) → `leopard-7` (8)
- `voyah-taishan` (1) → `taishan` (8)
- `zeekr-9x` (2) → `9x` (11)
- `e008` (2) → `e-008` (6)
- `fengyun-t11` (1) → `t11` (2) — keeper był ukryty pod `chery-fulwin`, dodano V62 entry

PARENT FIX (5, slug zostaje, deprecated-make pattern):
- `yangwang-u8` (5) — parent=byd, pms=byd
- `fengyun-x3` (1) — parent=chery-fulwin, pms=chery-fulwin
- `jetour-shanhai-l7-plus` (1) — parent=jetour, pms=jetour
- `yangwang-u7-ev` (1) — parent=byd, pms=byd

**Brand-mapping rozszerzone (5 nowych entries dla CN keys które wcześniej tworzyły orphany):**
- `'Chery Fengyun|Fengyun X3'` → slug='fengyun-x3'
- `'Chery Fengyun|Fengyun T11'` → slug='t11'
- `'Jetour Shanhai|Jetour Shanhai L7 PLUS'` → slug='jetour-shanhai-l7-plus'
- `'Yangwang|Yangwang U7 EV'` → slug='yangwang-u7-ev'
- `'Yangwang|Yangwang U8'` → slug='yangwang-u8'

**V62 dodane:** `'chery-fulwin' => ['fengyun-t11' => 't11']` (nowy klucz pierwszego poziomu).

**Verify:**
- Orphans: 15 → **0** ✓
- Serie terms total: 2256 → 2216 (40 ghosts deleted across A+B)
- 10 merge'y: ghost URL → 301 → keeper (200) ✓
- 5 parent fix URLs → 200 ✓ (`/samochody/byd/yangwang-u8/`, `/samochody/chery-fulwin/fengyun-x3/`, etc.)

---

## 0.32.41 — 2026-05-07 (v6.2 residuals cleanup phase 1: A delete 29 / C rename 12 / D parent fix 35)

**Scope:** taxonomy `serie` cleanup po 4 merge'ach v6.1 (GAC Trumpchi 04-29, iCAR + Galaxy 05-04, Jetour Zongheng 05-06). 95 targets, 80 wykonane, 15 pending user review (bucket B).

**Bucket A — bulk DELETE (29 termów, parent=0+count=0+merged-prefix):**
6× `great-wall-*`, 13× `trumpchi-*`, 3× `beijing-off-road-*`, 2× `changan-qiyuan-*`, 2× `fengyun-*`, 2× `yangwang-*`, 1× `jetour-shanhai-l6`. DELETE z wp7j_terms + term_taxonomy + termmeta.

**Bucket C — slug rename + V62 (12 termów):**
- 9 galaxy-* → bez prefix (geely): `a7-phev/e5/e8/l6/l7/m9/starship-6/starship-7-em-i/starship-8-phev`. V62 `'geely' => [9 entries]`.
- 3 trumpchi-* → bez prefix (gac): `m6/m8/s7`. V62 `'gac' => [+3 entries]`.
- **REVERT 4 termów:** `yangwang-u7`, `changan-qiyuan-a06-classic`, `changan-qiyuan-e07`, `jetour-shanhai-t1`. Były błędnie w bucket C — ich parent_make jest w V61 (yangwang→byd, changan-qiyuan→nevo, jetour-shanhai→jetour), więc URL `/samochody/<v61-target>/<bezprefix>/` po V61 bounceuje z powrotem. **Slug-z-prefiksem jest poprawnym wzorcem** dla deprecated makes. V63 entries dodane wcześniej (nevo/byd/jetour) cofnięte.

**Bucket D — orphan parent repair (35 termów + 4 pms fix):**
Heurystyka `_asiaauto_primary_make_slug` z listingów per orphan. UPDATE wp7j_term_taxonomy.parent dla 35. Dodatkowo INSERT/UPDATE pms dla 4 missing/wrong: `8x→zeekr`, `fushun→jmc-ev`, `shark-6→byd`, `zunjie-s800: zunju→maextro`. Wszystkie 35 mają teraz poprawny parent + pms (URL `/samochody/<make>/<slug>/` → 200).

**Bucket B — pending user review (15 termów, NO DB CHANGES):**
`tmp/v6.2-bucket-B-mapping.md`. Plan B1 (10 z keeperem — listings migration), B2 (5 bez keepera — parent fix + slug rename).

**V62/V63 zmiany w `class-asiaauto-redirects.php`:**
- V62: dodane `'geely'` (9 entries) + `'gac'` (3 nowe entries: `trumpchi-m6/m8/s7`)
- V63: bez zmian (próba dodania nevo/byd/jetour cofnięta)

**Verify finalny (smoke test):**
- 12× bucket C 301 → bezprefix target (200) ✓
- 4× revert 200 ✓ (`/samochody/byd/yangwang-u7/`, `/samochody/nevo/changan-qiyuan-e07/`, etc.)
- Bucket D sample 5/35 200 ✓
- Bucket A `/samochody/gac/trumpchi-ga3/` → 404 (term deleted) ✓
- Sitemap: serie-sitemap1=199 + sitemap2=121 = 320 (close to expected 321)
- Orphan count: 50 → 15 (= bucket B pending) ✓

**Backup:** `~/backups/primaauto/2026-05-07-v6.2-cleanup/terms-full.sql` (8.2 MB).

**Lekcja:** V61_MAKE_REDIRECTS dla deprecated marek = slug-z-prefiksem jest poprawny dla terms w hierarchii deprecated. URL routing przez V61 bounce → bezprefix slug = błąd.

---

## 0.32.40 — 2026-05-06 (Jetour Zongheng cleanup — V61 zongheng→jetour, V62 zongheng-g700→g700)

**Stan przed:** chaos — `Zongheng` istniał jako oddzielny `make` (term 6536, count=0), term `serie` `zongheng-g700` (6537, parent=jetour 4525, count=4) z URL `/samochody/zongheng/zongheng-g700/`. Listings post_title już marketingowe „JETOUR G700" ale slug i hub URL trzymały „Zongheng".

**Plan migracji (11 kroków, wykonane):**
1. Create new term `g700` w `serie` parent=jetour (term_id 6581, tt_id 6581)
2. Copy 6 termmeta z 6537 → 6581 (wiki_body 6795, faq_json 3593, seo_desc 155, _asiaauto_primary_make_slug, etc.)
3. Reassign 5 listings (4 publish + 1 trash) z tt_id 6537 → 6581
4. Update count: 6537 count=0, 6581 count=4
5. Delete orphan `zongheng` make (term_id 6536, term_taxonomy + term + termmeta)
6. Add `'zongheng' => 'jetour'` do `V61_MAKE_REDIRECTS`
7. Add `'jetour' => ['zongheng-g700' => 'g700']` do `V62_SERIE_REDIRECTS`
8. Bump `ASIAAUTO_VERSION` 0.32.40
9. Flush RankMath sitemap cache + regenerate
10. **Krytyczny gotcha:** termmeta `_asiaauto_primary_make_slug` skopiowana z 6537 zawierała `'zongheng'` (źle dla nowego term). Plugin `AsiaAuto_CPT::filterSerieTermLink` używa tej meta jako source-of-truth dla URL hub'a (nie taxonomy parent). **Update 6581 `_asiaauto_primary_make_slug = 'jetour'`** — bez tego URL hub'a był `/samochody/zongheng/g700/` zamiast `/samochody/jetour/g700/`, RM Sitemap też publikował zły URL i go wycinał (count=0 dla zongheng make).
11. Commit + push

**Verify finalny:**
- `/samochody/jetour/g700/` → 200 ✓ (4 listings, wiki, FAQ, schema)
- `/samochody/zongheng/` → 301 → `/samochody/jetour` (V61) ✓
- `/samochody/zongheng/zongheng-g700/` → 301 → `/samochody/jetour/zongheng-g700/` (V61) ✓
- `/samochody/jetour/zongheng-g700/` → 301 → `/samochody/jetour/g700/` (V62) ✓
- `/oferta/jetour-zongheng-g700-2026-302325/` → 200 (post slugi zachowane, decyzja Q1=zachowaj) ✓
- serie-sitemap: 320 → 321 (+ g700, − zongheng-g700 wycięte przez filter)
- make-sitemap: 47 (zongheng wycięte przez V61)

**Incydent:** podczas debugowania niewidoczności g700 w sitemap, `Cache::invalidate_storage()` zniszczył `rank-math-options-sitemap` (option z 30 kluczami → null). Sitemap_index.xml zwracał 404 dla wszystkich. **Recovery:** hardkodowane defaults (`pt_listings_sitemap=on`, `tax_make_sitemap=on`, `tax_serie_sitemap=on`, items_per_page=200, 30 kluczy łącznie) + `wp rewrite flush --hard`. Stan przywrócony.

**TODO osobne sesje (audyt wykazał):**
- ~25 martwych terms `serie` ze starymi prefixami sub-brand (`great-wall-*`, `trumpchi-*`, `beijing-off-road-*`, `changan-qiyuan-*`) — count=0, parent=0, do bulk delete
- ~25 z listings przypiętymi (count>0): atto-3-yuan-plus (10), seal-u-dm-i-song-plus (13), yangwang-u8 (5), galaxy-l7 (24), galaxy-starship-7-em-i (16), trumpchi-m6 (8), etc. — wymagają per-term migracji wzorem Jetour Zongheng

---

## 0.32.39 — 2026-05-06 (diag-check make/serie-without-wiki: filtr V61/V62/V63 redirected)

**Problem (znaleziony przez user'a):** diag-check „Marki bez wiki_body" zgłaszał 8 marek do generacji wiki przez n8n, ale **5 z 8** to V61_MAKE_REDIRECTS (chery-fengyun, galaxy, lotus-cars, maextro + chery-fengyun) — czyli marki które robią 301 do innych. Generowanie wiki dla nich = waste (~€0.06 × 5 = €0.30 + zaśmiecone webhooks). 

User uruchomił apply-fix, dostał 8 webhook timeoutów (n8n offline), ale wskazał że Galaxy w generacji to bałagan.

**Fix:**
- Public API w `AsiaAuto_Redirects`: `isMakeRedirected(slug)` + `isSerieRedirected(make_slug, serie_slug)` — re-use w diag-checks.
- `class-check-make-without-wiki.php`: skip gdy `isMakeRedirected($t->slug)`.
- `class-check-serie-without-wiki.php`: skip gdy `isSerieRedirected($make_slug, $t->slug)` (tylko dla termów z `parent != 0` — orphans z `parent=0` to osobny problem dla `serie-broken-parent` check).

**Verify (live scan po fix):**
- Marki bez wiki: **8 → 0** (wszystkie 8 były redirected) ✓
- Modele bez wiki: 46 (top 10 to głównie ORPHAN parent=0 — broken historic import; oraz `icar/03t` po V63 merge — legit, wymaga wiki gen)

**Pending:** orphan terms (parent=0) to oddzielny problem — `class-check-serie-broken-parent` powinien je naprawiać przed generacją wiki. n8n webhook 5s timeout oznacza że workflow `primaauto-make-desc` jest offline lub muli się — sprawdź w n8n.

---

## 0.32.38 — 2026-05-06 (serie-sitemap: wycięcie 23 redirected series V61/V62/V63 + URL-based filter)

**Problem (zdiagnozowany przez GSC URL Inspection 344 hubów modeli):** 13/344 = NEUTRAL „Strona zawiera przekierowanie". `serie-sitemap.xml` publikował slugi modeli których URL robi 301:
- modele marki redirectowanej (V61): `chery-fengyun/fengyun-x3`, `gac-aion-hyper/hyper-ssr`, `dongfeng-yipai/yipai-007`, `jetour-shanhai/jetour-shanhai-l7-plus`, `yangwang/yangwang-u7-ev`, etc.
- serie zdedupowane V62: `byd/leopard-5-denza-b5`, `byd/seal-u-dm-i-song-plus`, `byd/leopard-3-tai-3-fcb`, `byd/sealion-8-dm-i-tang-l`, `byd/atto-3-yuan-plus`, `byd/leopard-8-denza-b8`, `byd/sealion-8-tang-l-ev`, `byd/leopard-7-tai-7-fcb-phev`, `zeekr/zeekr-9x`, `voyah/voyah-taishan`, `dongfeng/e008`, `gac/trumpchi-e8`, etc.
- serie cross-make migrated V63: `chery/icar-03`, `chery/icar-03t`, `chery/icar-v27`

**Fix:** rozszerzenie hooka `rank_math/sitemap/entry` w `AsiaAuto_Redirects` — `excludeRedirectedTermsFromSitemap()`:
- **make:** ten sam check co v0.32.37 (slug w V61_MAKE_REDIRECTS)
- **serie:** **URL-based parsing** zamiast `$term->parent` (wiele serie-termów to orphans z `parent=0`). Wyciągamy `<make_slug>/<serie_slug>` z URL ścieżki `/samochody/<make>/<serie>/`, deterministycznie sprawdzamy V61 (parent_make redirects), V62 (serie dedup w obrębie make), V63 (cross-make migration).
- **Bonus fix:** RankMath przekazuje `$url` jako tablicę `['loc' => ..., 'mod' => ..., 'images' => ...]`, nie string. Refactor obsługuje oba typy (forward/backward compat).

**Verify (po `wp rankmath sitemap generate`):**
- make-sitemap.xml: 47 URL (bez zmian, v0.32.37)
- serie-sitemap1.xml + serie-sitemap2.xml: **344 → 321 URL** (wycięte 23 redirected)
- Sample 6/6 URL'i 301-redirect: usunięte ✓

**Indexing API quota:** dziś submitowanych 12 (huby make z poprzedniej sesji) + 29 (huby serie NEUTRAL waiting) = **41/200**. Zostaje 159 na inne potrzeby do końca dnia.

---

## 0.32.37 — 2026-05-06 (make-sitemap: wycięcie 15 redirected makes V61)

**Problem (zdiagnozowany przez GSC URL Inspection 54 hubów marek):** 6/54 hubów = NEUTRAL „Strona zawiera przekierowanie". `make-sitemap.xml` publikował slugi marek które robią 301 (V61_MAKE_REDIRECTS w `class-asiaauto-redirects.php`) — Google odrzucał je z indeksu jako redirect.

**Fix:** `AsiaAuto_Redirects::excludeRedirectedMakeFromSitemap()` — hook `rank_math/sitemap/entry` priority 10. Per-entry filter zwraca `false` dla term'ów `make` których slug jest kluczem w `V61_MAKE_REDIRECTS` (15 slugów). Bez ruszania DB — listings podpięte pod te termy nadal indeksowane przez `listings-sitemap*` (osobne sitemaps dla CPT `listings`).

**Verify (po `wp rankmath sitemap generate`):** make-sitemap.xml: 54 → **47 URL**. Zniknęły: `galaxy`, `great-wall`, `dongfeng-yipai`, `gac-aion-hyper`, `jetour-shanhai`, `yangwang`, `fangchengbao`, `chery-fengyun`, `gac-trumpchi`, `maextro`, `changan-qiyuan`, `beijing-off-road`, `212`, `dongfeng-fengxing`, `lotus-cars`. `polestar/` + `xingchi/` (NEUTRAL „zeskanowana, czeka na index") zostały — submit do Indexing API dla acceleracji.

**GSC URL Inspection wynik finalny dla 54 hubów make (przed fix):** 46 PASS / 2 NEUTRAL waiting / 6 NEUTRAL redirect = po fix: 47 sitemap URL, z czego 46 PASS + 2 waiting. Realnie **48 marek (54 - 6 redirected) z których 46 zaindeksowane = 96%**.

**Indexing API submitowane w sesji:** 12/200 quota:
- 10 hubów (pretendenci top10 z GSC + świeże po batch n8n + huby po merge'ach)
- 2 hub-y NEUTRAL (`polestar/`, `xingchi/`)

---

## 0.32.36 — 2026-05-06 (fix dup meta description single listing — RankMath suppression)

**Problem (zdiagnozowany live curl):** single listings `/oferta/*` emitowały **2× `<meta name="description">`** + 2× `og:type/og:title/og:description/og:image`:
- RankMath Pro: auto-extract z `post_content` → łapie chińskie znaki z importu Dongchedi (np. „Nie można wystawić faktury VAT【Stan zewnętrzny】Drobne rysy【Stan lakieru】..."). **Śmieciowa desc**.
- `class-asiaauto-single::renderMeta()`: bogata desc z marką/rokiem/paliwem/przebiegiem/ceną/USP („Import z Chin – Prima Auto"). **Lepsza dla CTR**.

**Decyzja:** zostawić `class-asiaauto-single` (bogata custom emisja), zsupressować RankMath dla single listings. Memory v0.32.0 „single nietknięte" było prawidłowe — broniło przed RM auto-extract.

**Fix:** `class-asiaauto-single::initRankMathSuppression()` — 11 filtrów zwracających `''` lub `[]` dla `is_singular('listings')`:
- `rank_math/frontend/title`, `rank_math/frontend/description`, `rank_math/frontend/robots`
- `rank_math/opengraph/facebook/og_title`, `og_description`, `og_type`, `og_image`
- `rank_math/opengraph/twitter/twitter_title`, `twitter_description`, `twitter_image`, `card_type`

**Bonus:** wyłączenie `rank_math/frontend/title` aktywuje `class-asiaauto-single::filterTitle` (wcześniej dead code) — 10 wariantowych templatów title rotuje per inner_id (`Używane {base} z Chin`, `{base} import z Chin`, `Sprowadź {base} z Chin`, etc.).

**Smoke 2/2 listingi (Zeekr 8X / BYD Sealion 8):**
- 1× `meta description` (bogata: marka, rok, paliwo, przebieg, cena, USP) ✓
- 1× `og:type=product` (nie article) ✓
- 1× `og:title/description/image` (custom template) ✓
- 1× `<title>` (template z filterTitle) ✓
- 1× `meta robots` (max-snippet/max-image-preview, brak `noindex`) ✓
- 1× `link canonical`, 1× `twitter:card` ✓

**Hand-off konwencji RankMath ↔ AsiaAuto (po v0.32.36):**
- **RankMath rządzi:** home, page (`/samochody/`, `/marki/`, `/informacje/*`), taxonomy (make, serie, body, fuel, ca-year)
- **AsiaAuto rządzi:** single listings (`/oferta/*`) — title + meta + og + twitter + Schema Car + dataLayer (RankMath supressed). Plus huby make/serie — Schema ItemList/FAQPage/BreadcrumbList (RankMath nie generuje, custom emituje równolegle do RM CollectionPage).

Backup: `class-asiaauto-single.php.bak-2026-05-06-rm-dedup`.

---

## 0.32.35 — 2026-05-06 (audyt SEO Plan A: GSC sitemap cleanup + dup desc fix + /marki/ meta)

**Audyt SEO 2026-05-06 — porównanie do baseline 2026-04-23:**

| Metryka | 2026-04-23 | 2026-05-06 |
|---|---|---|
| Indeksacja 10 URL | 1/10 PASS | **10/10 PASS** |
| GSC 30d impresje | 4 | **1282** |
| GSC 30d clicks | 0 | **98** (CTR 7.64%, avg pos 7.6) |
| Top query | brak | **"prima auto rzeszów" pos 2.4 CTR 26.1%** |

**Konflikt RankMath ↔ class-asiaauto-seo (zdiagnozowany, fixed):**
- Pierwsze meta desc na `/samochody/`: RankMath ("Tylko aktualne i sprawdzone oferty…")
- Drugie meta desc: `class-asiaauto-inventory.php::renderInventoryMeta()` linia 1732 ("Elektryki, hybrydy…") — **duplikat**
- `class-asiaauto-seo.php` ma already early return gdy `defined('RANK_MATH_VERSION')` (v0.32.0), `class-asiaauto-inventory.php` nie miał — **fix w tej wersji**

**Fix A1 — DELETE stary sitemap z GSC:** `wp-sitemap.xml` (3609 URL submitted, downloaded 2026-05-01) — duplikat z RankMath `sitemap_index.xml` (3691 URL). API DELETE → HTTP 204.

**Fix A2 — Submit 24 RankMath sitemaps do GSC:** GSC wcześniej widział tylko 4 z 18 listings sitemaps (sitemap1-4). PUT przez `webmasters/v3/sites/{site}/sitemaps/{url}` dla pełnego setu: `sitemap_index.xml` + `page-sitemap.xml` + `make-sitemap.xml` + `serie-sitemap1-2.xml` + `local-sitemap.xml` + `listings-sitemap1-18.xml`. Wszystkie 24 → 0 errors. Łącznie 3691 URL submitted, w tym 18 sitemaps × 200 listings = ~3600 (sitemap18 ma 43, sitemap17 ma 82 — końcówki).

**Fix A3 — `class-asiaauto-inventory.php::renderInventoryMeta()` early return gdy RankMath aktywny:** dodane w linii 1700 `if (defined('RANK_MATH_VERSION')) return;`. URL-e parametryczne (`?marka=X&model=Y`) i tak są noindex od v0.32.5/8 (`isInventoryPage()` + filter params whitelist). RankMath obsługuje główny `/samochody/` z `rank_math_title`/`rank_math_description` ustawionymi w admin. Backup: `class-asiaauto-inventory.php.bak-2026-05-06-rm-handoff`. Po fix smoke `/samochody/` ma desc:1 (RankMath: "Tylko aktualne i sprawdzone oferty aut z rynku chińskiego. Bezpośredni importer.").

**Fix A4 — `/marki/` (page_id 263572) brakujące rank_math_*:** `rank_math_title` = "Marki samochodów z Chin — Prima-Auto", `rank_math_description` = "Pełen katalog 50+ marek samochodów z Chin: BYD, Xiaomi, Chery, Geely, Voyah, AITO, XPeng, Zeekr i inne. Import do Polski, ceny końcowe, gwarancja." (155 chars), `rank_math_focus_keyword` = "marki samochodów z Chin". `wp post meta update 263572` × 3.

**Smoke test 5/5:** `/samochody/` desc:1 ✓ (RankMath), `/marki/` desc:1 ✓ (nowy desc + custom title), `/samochody/?marka=byd` desc:1 ✓ (RankMath), Listing Denza desc:2 (out-of-scope, do osobnej decyzji), Hub BYD/SU7 bez zmian.

**KRYTYCZNE pozostałe (osobna sesja):**
- **PSI mobile home REGRES**: perf 75 (04-23) → **39** (05-06), TBT 160ms → **3890ms** (24×!), LCP 4.9s → 6.1s. CrUX field data **wszystkie 4 metryki = POOR (F)** — Google klasyfikuje jako poor CWV → ranking penalty. Source: prawdopodobnie RankMath analytics + Complianz + asiaauto-tracking + GTM stacked w main thread. Wymaga audytu JS payloadu i defer/async refactor. Theme `primaauto2026` 1.0.4 (Elementor wycofany 2026-04-24, ale TBT regres jest inny problem).
- **Listing desc:2** — `class-asiaauto-single.php` (custom z marką/modelem/ceną/przebiegiem) konkuruje z RankMath auto-extract z post content. AsiaAuto desc lepsza SEO-wise (zawiera focus-keywordy), RankMath desc generic. Decyzja: zostawić AsiaAuto + wyłączyć RM dla CPT `listings` (filter `rank_math/frontend/description` return false dla `is_singular('listings')`) lub w admin RM disable post type.

**Top pages w GSC (30d, what's working):** `/` 469imp/56clk/CTR11.9%, `/samochody/` 147/3, `/samochody/aito/` 89/3, `/samochody/byd/` 69/3, `/marki/` 63/2, `/samochody/byd/leopard-5/` 101/2. Niche-modele rankują: "tank 300 cena w polsce" pos 6.4, "geely preface cena" pos 8.7, "aito m9", "li auto l9", "zeekr 9x 2025", "changan uni-v" pos 27 (do dopchnięcia).

## 0.32.34 — 2026-05-06 (W1+W2: prevent ghost-offer publish-then-trash churn)

**Problem:** importer publikował listingi mimo że auto-api.com zwracał już-wygasłe URL-e Dongchedi (`x-expires` < `synced_at`). Listingi w `publish` bez thumbnail → indeksowane przez Google → potem masowy cleanup przez `diag missing-images` (2026-05-03: 60 listings; rano 2026-05-06: znów 93). Strata budżetu indeksacji + churn URL-i.

**Rozwiązanie 2-warstwowe:**

**W1 — preflight w `AsiaAuto_Importer::importListing()`:**
Przed `wp_insert_post` parsuje obrazy i sprawdza `allUrlsExpired()` (nowy static helper). Jeśli WSZYSTKIE URL-e z parametrem `x-expires` są po terminie → `return null`, log warning. Cron pomija ofertę i ponowi przy następnym sync (świeżych danych z API). **Manual import (`force=true`) pomija preflight** — klient świadomie wskazał ofertę.

**W2 — guard w `AsiaAuto_Media::downloadAndStore()`:**
Gdy wszystkie pobrania zwrócą 403 (`empty($attachment_ids)`) — zamiast zostawić post w `publish` bez thumbnail, przenosi go do `draft` i zapisuje `_asiaauto_image_failure_at`. Listing nie pojawia się publicznie. `updateListing` przy kolejnym sync może go odzyskać (`downloadMissingImages` nadal próbuje).

**Manual import UI:**
`ajaxImport()` po imporcie sprawdza gallery i jeśli pusta — zwraca `data.warning` z instrukcją: „URL-e wygasły, otwórz ofertę na Dongchedi (auto-odświeży cache po stronie auto-api.com), ponów import za ~30s". JS renderuje jako `notice notice-warning`.

**Helper `AsiaAuto_Importer::allUrlsExpired(array $urls): bool`:**
- `[]` → `false` (no-op, nic do importu)
- URL-e che168 / bez `x-expires` → `false` (conservative: nie blokujemy)
- Mieszane (chociaż 1 świeży) → `false`
- Wszystkie z `x-expires` po terminie → `true`

**Pliki:** `asiaauto-sync.php` (version), `includes/class-asiaauto-importer.php` (W1 + helper), `includes/class-asiaauto-media.php` (W2), `includes/class-asiaauto-admin-manual-import.php` (UI warning + JS).

**Backupy:** `*.bak-2026-05-03-w1`, `*.bak-2026-05-03-w2`, `*.bak-2026-05-03-warning`.

**Smoke test:** `php -l` × 4 czysty. `wp eval allUrlsExpired()` dla 5 case'ów: poprawne wyniki (mixed=false, all_old=true, no_param=false, empty=false, real_dongchedi_old=true).

**Co dalej:** monitor logu po następnym cronie (`grep "API cache stale" logs/asiaauto-sync.log`) — ile ofert pominiętych. Jeśli liczba jest wysoka i nie spada przez 2-3 cykle, zbadać dlaczego auto-api.com serwuje stary cache (może tam jest TTL do podkręcenia).

## 0.32.33 — 2026-05-04 (HOTFIX: martwe linki asiaauto.pl w mailingu i umowie PDF)

W trybie autonomous przy v0.32.32 zostawiłem 7 hardcoded URL-i `https://asiaauto.pl/*` w mailach do klientów i logo URL w umowie PDF jako „TODO osobny task" — uznając że „działa bo plik istnieje na asiaauto.pl". To było błędne. Klient zwrócił uwagę: domena `asiaauto.pl` zwraca **HTTP 500 na wszystkich routach poza wąskim zakresem statycznych plików w `/2026/04/`**. Klienci dostawali maile z linkami `https://asiaauto.pl/proces/`, `/homologacja/`, `/faq/`, `/samochody/` — wszystkie 500. Umowa PDF używała LOGO_URL z asiaauto.pl który czasem dawał 200, czasem 500 (warunkowo).

**Szybka inwentaryzacja stanu asiaauto.pl (curl -I):**
- `/`, `/proces/`, `/homologacja/`, `/faq/`, `/samochody/` — **HTTP 500**
- `/wp-content/uploads/2026/04/primaauto-logo-round.png` — **HTTP 200** (jeden statyczny plik z 04/ działa)
- `/wp-content/uploads/2026/03/primaauto-logo-round.png` — **HTTP 500**

Przyczyna 500-ek na asiaauto.pl wymaga osobnej diagnozy (pewnie wp-config WP_HOME na primaauto + brak fallback dla starej domeny w .htaccess albo plugin asiaauto-sync który DB wspólną i coś failuje). **Cutover 2026-04-21 zakładał 301 na całej domenie — nie działa zgodnie z założeniem.** Do osobnego task (TODO).

**`class-asiaauto-order-content.php` (6 linków w 4 statusach maili):**
- 116-118: lista przydatnych informacji w mailu „Zamówienie przyjęte" — 3 linki:
  - `https://asiaauto.pl/proces/` → `https://primaauto.com.pl/informacje/proces-zamawiania/`
  - `https://asiaauto.pl/homologacja/` → `https://primaauto.com.pl/informacje/homologacja-i-rejestracja/`
  - `https://asiaauto.pl/faq/` (nigdy nie istniała na primaauto) → `https://primaauto.com.pl/informacje/` (parent landing dla wszystkich podstron informacyjnych)
- 159: link do procesu w mailu „Wycena": `asiaauto.pl/proces/` → `primaauto.com.pl/informacje/proces-zamawiania/`
- 350: oferty alternatywne w mailu „Niedostępny": `asiaauto.pl/samochody/` → `primaauto.com.pl/samochody/`
- 371: oferty w mailu „Anulowane": `asiaauto.pl/samochody/` → `primaauto.com.pl/samochody/`

**`class-asiaauto-contract.php` (LOGO w PDF umowy):**
- Linia 53: `LOGO_URL = 'https://asiaauto.pl/wp-content/uploads/2026/04/primaauto-logo-round.png'` → `'https://primaauto.com.pl/wp-content/uploads/2026/03/primaauto-logo-round.png'`
- Linia 290: `resolveLogoPath()` próbuje najpierw lokalny `$upload_dir/2026/04/primaauto-logo-round.png` — który **nie istnieje** (plik leży w `/2026/03/`). Path zmieniony na `/2026/03/primaauto-logo-round.png` (200 lokalnie + 200 z URL fallback).

**Smoke test (PASS):**
- `/informacje/proces-zamawiania/` 200 ✓
- `/informacje/homologacja-i-rejestracja/` 200 ✓
- `/informacje/` 200 ✓ (landing dla wszystkich info)
- `/samochody/` 200 ✓
- `/wp-content/uploads/2026/03/primaauto-logo-round.png` 200 ✓

**Sync legacy domain:** 3 pliki skopiowane do `~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/` (rutynowo, choć sama domena daje 500).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.32 → 0.32.33
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-order-content.php` — 6 linków asiaauto.pl → primaauto.com.pl
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-contract.php` — LOGO_URL + lokalny path do 2026/03/

**Diagnoza + fix asiaauto.pl 500 (przy okazji v0.32.33, infra-only):**

Przyczyna: `wp eval` na asiaauto.pl rzucał `Fatal error: Failed opening required '...wp-content/plugins/asiaauto-sync/includes/class-asiaauto-mapping.php'`. Plik powstał 2026-04-23 przy v6.1 brand-mapping (`AsiaAuto_Mapping::getEuForCn()`), ale **sync legacy do asiaauto.pl był niekompletny** — kopiowaliśmy tylko aktualnie zmieniane pliki w danej sesji, nigdy nie robiliśmy `rsync` całego plugin dir. `diff -rq` pokazał **kilkadziesiąt** plików brakujących na asiaauto.pl (admin-diag.css/js, asiaauto-tracking.js, kilka diag/, kilka data/translations + część kluczowa: `class-asiaauto-mapping.php`, `class-asiaauto-admin-diag.php`).

Fix: ZAMIAST sync wszystkiego z primaauto, zgodnie z założeniem cutover 2026-04-21 (memory: „asiaauto = uśpiona kopia z 301") → **bezwarunkowy 301 w `.htaccess` przed jakimkolwiek przetwarzaniem PHP**:

```apache
# BEGIN AsiaAuto 301 → primaauto.com.pl
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^(.*)$ https://primaauto.com.pl/$1 [R=301,L]
</IfModule>
# END AsiaAuto 301
```

Backup: `~/domains/asiaauto.pl/public_html/.htaccess.bak-2026-05-04` (oryginalny ze starym Login Hide block + WP rewrites — bez 301).

**Smoke test po fix .htaccess:**
- `https://asiaauto.pl/` → 301 → `primaauto.com.pl/` 200 ✓
- `https://asiaauto.pl/proces/` → 301 → `primaauto.com.pl/proces/` → 301 → `primaauto.com.pl/informacje/proces-zamawiania/` 200 ✓
- `https://asiaauto.pl/samochody/icar/03/` → 301 → `primaauto.com.pl/samochody/icar/03/` 200 ✓ (potwierdza migrację iCAR działa też z legacy domain)
- `https://asiaauto.pl/wp-content/uploads/2026/03/primaauto-logo-round.png` → 301 → primaauto

**Implikacja:** sync legacy do asiaauto.pl staje się zbędny — domena zwraca 301 dla wszystkiego, nie odpala WP, nie używa pluginu. Można w przyszłości całkowicie zrezygnować z kopiowania plików (sam plik `.htaccess` musi tam zostać). Pliki kopiowane w sesji v0.32.31-33 do asiaauto.pl/wp-content są bezużyteczne, ale nie szkodzą.

---

## 0.32.32 — 2026-05-04 (Galaxy → Geely merge + chinese cleanup + /kontakt/ fix)

Druga część sesji 2026-05-04 (po iCAR merge v0.32.31): scalenie residuala Galaxy do Geely, doczyszczenie chińskich znaków w 21 post_title i naprawa shortcode `[asiaauto_contact]` na stronie /kontakt/ (subject mail + 404 schema image).

**Galaxy → Geely (v6.1 residual finalized):**
- Term `Galaxy` (3394, 16 listingów) — pusty po migracji, **usunięty** (`wp term delete`). Redirect `galaxy → geely` był w V61_MAKE_REDIRECTS od 2026-04-23 — działa po usunięciu termu.
- 17 listingów: `term_relationships.term_taxonomy_id=3394` → `3626` (Geely)
- 1 seria pod parent=Galaxy: `Galaxy M9` (6550) → parent=3626 (reszta serii Galaxy* już była pod Geely)
- `wp7j_postmeta`: `make=galaxy` → `geely` (17 wpisów)
- `wp7j_termmeta`: `_asiaauto_primary_make_slug=galaxy` → `geely` (term 6550)
- `wp7j_posts.post_title`:
  1. REPLACE `'Galaxy 银河'` → `'Geely Galaxy '` (chinese cleanup + Geely prefix; 6 listingów A7 EM)
  2. REPLACE `'Galaxy Galaxy'` → `'Geely Galaxy'` (de-duplicate; 1 listing 282264 z poprzednim partial fix)
  3. CONCAT `'Geely '` przed `'Galaxy %'` gdzie nie zaczyna się od `'Geely '` (10 pozostałych)
- `wp term recount`: Geely 341 → 357 (+16 publish, 17 minus 1 draft)

**Brand-mapping bez zmian:** wszystkie 12 wpisów `Galaxy|*` już mapowały na `mark_eu='Geely'` od v6.1 (importer importował nowe listingi pod Geely; tylko stare pod taxonomy Galaxy zostały do dziś).

**Chinese cleanup (translations-complectations.php — 15 nowych mapowań):**

Dodano sekcję `// === 2026-05-04 — chinese-fragments cleanup po Galaxy/iCAR merge (15 nowych) ===`:
- 巅峰性能 → Peak Performance (iCAR Super V23 V23S, listing 299535)
- 大家庭欢乐 → Family Joy (Jetour X90 PLUS)
- 星辉 → Starlight (MAEXTRO S800)
- 锦绣 → Splendid (Geely Atlas Pro)
- 启航 → Voyager (Geely Galaxy E5)
- 凌云 → Soaring (Exeed TXL)
- 智慧 → Smart (Changan CS75 Plus)
- 威赫 → Mighty (Xingchi Bochi Venus)
- 出行 → Mobility (Hongqi E-QM5)
- 公务 → Official (Geely Galaxy E5)
- 高功 → High Power (Geely Monjaro)
- 霄汉 → Skyward (Geely Monjaro)
- 乘势 → Momentum (Chery Tiggo 8 PLUS)
- 电 → Electric (Jetour Shanhai L7 PLUS)
- 星 → Star (Exeed TXL — single char na końcu mapy, longest-first PHP str_replace iteruje by-array-order więc 星舰/星耀/星辉/星空龙耀/星月女神 zamienione przed)

**APPLY `diag/fix-chinese-v23.php`:** 15 listings zaktualizowanych. Po: `SELECT COUNT(*) WHERE post_title REGEXP '[一-龥]'` = **0** (ZERO chińskich w post_title publish). Galaxy listings z chińskim 银河 obsłużone wcześniej w SQL transaction Galaxy migration (REPLACE 'Galaxy 银河' → 'Geely Galaxy ').

**Shortcode `[asiaauto_contact]` na /kontakt/ (`class-asiaauto-contact.php`):**
- Linia 127: `'image' => home_url('/wp-content/themes/asiaauto/assets/asia-auto-logo.png')` → `home_url('/wp-content/uploads/2026/03/primaauto-logo-round.png')`. Plik `asia-auto-logo.png` w themes/asiaauto/assets/ **nie istniał** (404) — schema.org/AutoDealer image był broken. Nowy URL → 200.
- Linia 306: subject mailto `'Zapytanie ze strony asiaauto.pl'` → `'Zapytanie ze strony primaauto.com.pl'`. To było user-facing (klient widział temat w mailu od użytkownika). User nie mógł poprawić bo treść strony to shortcode z PHP.

**Smoke test (PASS):**
- `/samochody/galaxy/` → 301 → `/samochody/geely/` 200 ✓
- `/samochody/galaxy/galaxy-m9/` → 301 → `/samochody/geely/galaxy-m9/` 200 ✓
- `/samochody/geely/a7-em/` 200 ✓ (16+ listingów A7 EM)
- `/kontakt/` 200 ✓ — schema image primaauto-logo-round.png, mailto subject „Zapytanie ze strony primaauto.com.pl"
- 0 listings publish z chińskimi znakami w post_title

**Backup DB:** `~/backups/primaauto/2026-05-04-galaxy-merge/terms-and-posts.sql` (8.4 MB, 4 tabele).

**Sync legacy domain:** 3 pliki skopiowane do `~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/`.

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.31 → 0.32.32
- `wp-content/plugins/asiaauto-sync/data/translations-complectations.php` — 15 nowych mapowań
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-contact.php` — schema image URL fix + mailto subject

**Pozostałe odwołania `asiaauto.pl` w kodzie (NIE naprawione w tej sesji — poza scope dziś):**
- `class-asiaauto-contract.php:53` — `LOGO_URL = 'https://asiaauto.pl/wp-content/uploads/2026/04/primaauto-logo-round.png'` (działa bo plik istnieje na asiaauto.pl, ale powinno wskazywać na primaauto.com.pl)
- `class-asiaauto-order-content.php:116-118, 159, 350, 371` — emaile statusów zamówień zawierają linki `https://asiaauto.pl/proces/`, `/homologacja/`, `/faq/`, `/samochody/` (user-facing — TODO osobny task)

---

## 0.32.31 — 2026-05-04 (iCAR merge: 03/03T/V27 z Chery do iCAR)

Klient zgłosił 2026-05-04: iCAR ma być wyłącznie marką, modele iCAR widniejące pod Chery (iCAR 03, iCAR 03T, iCAR V27) trzeba przerzucić pod główną markę iCAR. Stan przed migracją był niespójny: brand-mapping v6.1 mapował `iCAR Super V23` na markę iCAR, ale `iCAR 03` i `iCAR V27` na Chery; redirect `icar → chery` w V61_MAKE_REDIRECTS sprzeczny ze stanem (term marki iCAR istniał z 9 listingami). Plus orphan `iCAR 03T` (term 5519, parent=0) bez wpisu w mapping.

**Migracja DB live (2026-05-04 ~17:05):**
- `wp7j_terms`: rename serie 5518 (iCAR 03 → 03 / `03`), 5519 (iCAR 03T → 03T / `03t`), 6508 (iCAR V27 → V27 / `v27`). Naming bez prefiksu — wzorzec spójny z istniejącą serią V23 (term 5517, sam numer/oznaczenie, prefix marki tylko w post_title)
- `wp7j_term_taxonomy`: serie 5517/5518/5519/6508 → `parent=5516` (iCAR). Przy okazji fix orphana 5517 V23 (był parent=0) i 5519 (był parent=0)
- `wp7j_term_relationships`: 12 wpisów `term_taxonomy_id=3578 (Chery)` → `5516 (iCAR)` dla listingów: 245892, 249700, 249717, 250800, 259268, 265157, 267536, 271571, 273041, 287720, 287730, 291872 (287534 już miał make=iCAR)
- `wp7j_postmeta`: 7× klucz `make=chery` → `icar` (część listingów miała już `icar` w postmeta — niespójność postmeta vs taxonomy uleczona)
- `wp7j_termmeta`: `_asiaauto_primary_make_slug=chery` → `icar` dla 5518 i 6508 (5519 i 5517 już miały `icar`)
- `wp7j_posts`: REPLACE `'Chery iCAR'` → `'iCAR'` w post_title, 11 listingów (287534 już bez prefiksu)
- `wp term recount make serie` — count: Chery 132 → 124 (-8 publish), iCAR 9 → 17 (+8 publish), suma 141 = 141 ✓

**Brand-mapping uzupełniony** (`data/brand-mapping-v6.1.php`):
- `'iCAR|iCAR 03'` → mark_eu=`iCAR` (zmiana z Chery), serie_eu=`03`, title_eu=`iCAR 03`, slug=`03`
- `'iCAR|iCAR 03T'` → **nowy wpis** (rozwiązuje orphan), mark_eu=`iCAR`, serie_eu=`03T`, slug=`03t`
- `'iCAR|iCAR V27'` → mark_eu=`iCAR` (zmiana z Chery), serie_eu=`V27`, slug=`v27`

Bez tego importer przy reimporcie cofnąłby zmiany (wrzucał iCAR 03/V27 z powrotem pod Chery i nie tworzyłby relacji dla 03T).

**Redirects (`class-asiaauto-redirects.php`):**
- **USUNIĘTO** `'icar' => 'chery'` z `V61_MAKE_REDIRECTS` (niespójność: marka iCAR istnieje, redirect ją zabijał)
- **DODANO** nową stałą `V63_MAKE_SERIE_REDIRECTS` (mapa `[old_make][old_serie] => [new_make, new_serie]`) + metodę `redirectV63MakeSerieMoves()` na `template_redirect` priorytet 0. Mapa: `chery/icar-03 → icar/03`, `chery/icar-03t → icar/03t`, `chery/icar-v27 → icar/v27`. Mechanizm rozszerzalny — następne migracje modeli między markami w jednym miejscu.

**Smoke test (PASS):**
- `/samochody/icar/` 200 (17 listingów)
- `/samochody/icar/03/`, `/icar/03t/`, `/icar/v27/`, `/icar/v23/` 200
- `/samochody/chery/icar-03/` → 301 → `/samochody/icar/03/` 200 ✓
- `/samochody/chery/icar-03t/` → 301 → `/samochody/icar/03t/` 200 ✓
- `/samochody/chery/icar-v27/` → 301 → `/samochody/icar/v27/` 200 ✓
- `/samochody/chery/` 200 (124 listingów, bez iCAR)

**Backup DB:** `~/backups/primaauto/2026-05-04-icar-merge/terms-full.sql` (8.4 MB, 4 tabele: terms/term_taxonomy/term_relationships/termmeta).

**Sync legacy domain:** 3 pliki skopiowane też do `~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/` (legacy 301-redirect na primaauto, ale wp-content musi być spójne — wspólna DB).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.30 → 0.32.31
- `wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php` — 3 wpisy iCAR (mark_eu zmiana + nowy 03T)
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-redirects.php` — usunięty `icar→chery`, dodany `V63_MAKE_SERIE_REDIRECTS` + `redirectV63MakeSerieMoves()`

---

## 0.32.30 — 2026-05-01 (Fix mocy KM dla PHEV — single + karty inventory)

Klient zgłosił 2026-04-30: PHEV-y pokazują absurdalnie niskie liczby KM (BYD Han DM-i 156 zamiast 272, Denza Z9 DM-i 207 zamiast 870, Sealion 8 DM-p 4WD 156 zamiast 544). Diagnoza pełna w `docs/QUEUE.md` ZADANIE 15: dwa renderery (`class-asiaauto-inventory.php::parseSystemPower`, `class-asiaauto-single.php::power`) opierały się na `energy_elect_max_power` (niespójne dla PHEV) z fallbackiem do meta `_asiaauto_horse_power` (= moc samego silnika spalinowego).

**Wdrożenie:**
- Nowy `public static AsiaAuto_Inventory::resolvePower(int $post_id, array $ep): array` — fuel-aware: dla PHEV/EREV/HEV/EV używa `front_electric_max_horsepower` + `total_electric_power` (wiarygodne 99,8% PHEV w bazie). Edge case PHEV: gdy ICE dominuje (`engine_kw * 1.5 > total_kw`, np. Volvo S90 T8) → pokaż combined `engine_kw + total_kw`. Dla benzyny/diesla — `engine_max_horsepower` + `engine_max_power`. Meta `_asiaauto_horse_power` jako ostateczny fallback **tylko dla ICE** (dla PHEV nadal zawiera ICE-only HP, ale nie jest dla nich brany pod uwagę).
- `class-asiaauto-inventory.php` — karta listings woła `resolvePower($postId, $ep)` zamiast `parseSystemPower($ep)`. Stary helper zostawiony jako `@deprecated` (nieużywany).
- `class-asiaauto-single.php::power($ep, $post_id = null)` — cienki wrapper na `AsiaAuto_Inventory::resolvePower()`. Etykieta nad mocą zmieniona z „Moc łączna" na „Moc" (dynamicznie z `$pw['label']`). Caller w `wp_head` (linia 687) dostaje `$pid` jawnie — `get_the_ID()` bywa nullem przed The Loop.

**Weryfikacja klienta (2026-05-01):** wszystkie 8 testów z planu PASS — Han DM-i, Z9 DM-i Ultra, N9 DM-i Premium, Leopard 7 PHEV, AITO M7 EREV, Volvo S90 T8 (combined), Z9 GT EV (bez regresji), benzynowiec (bez regresji).

**Czego NIE ruszono:** importer (`_asiaauto_horse_power` celowo zapisuje ICE HP — zostaje), `class-asiaauto-shortcodes.php::resolvePower()` (wzorcowa logika — nieaktywna w motywie primaauto2026, pozostaje na osobny refactor konsolidujący).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.29 → 0.32.30
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-inventory.php` — `resolvePower()` static helper + podmiana w karcie listings
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-single.php` — `power()` jako wrapper, etykieta „Moc", `$pid` jawny w wp_head schema

---

## 0.32.29 — 2026-04-30 (Mobile single redesign — sticky title bar pod headerem strony)

Single listing przebudowany na mobile: title + brutto/netto przyklejone u góry pod headerem strony, gallery tuż pod, "Informacje" (linki do podstron) zjechały na sam dół pod inne moduły, breadcrumb i top „Wróć do wyników" ukryte na mobile (BreadcrumbList JSON-LD nadal w `<head>`).

**Architektura sticky head — dwa warianty (desktop sidebar + mobile poza layoutem):**
- Wyciągnięty `stickyHead($d, $variant)` z `sidebar()`. Renderowany 2× z modyfikatorami `--desktop` (w `<aside>`) i `--mobile` (poza `<div class="aa-single__layout">`, jako sibling).
- Variant `--mobile` ma `position: sticky; top: var(--header-h, 70px); z-index: 90` — przykleja się POD `.pa-header` (z-index 100). Backup-y atemptów: position: fixed (porzucony — wymagał dynamicznego JS measure margin-top), display: contents na sidebar (porzucony — niestabilne w grid + sticky cascade).
- Negatywny margin-top `-16px` niweluje `--space-2` padding na `.pa-main` na mobile — title bar bez gapu po site headerze.
- Mobile sticky-back arrow (←) jako lewa kolumna grid sticky head; na desktop hidden (`display: none`).
- `aa-info--desktop` (w sidebar) vs `aa-info--mobile` (na końcu main) — info linki widoczne tylko w odpowiednim viewporcie.
- `aa-sep--desktop`, `aa-single__updated--desktop` — dodatkowe dekoracje sidebar ukryte na mobile.

**iOS Safari fix mobile CTA (3 buttons):** `position: fixed; bottom: 0` ląduje pod toolbarem Safari. JS `visualViewport` API liczy gap między layout a visual viewport i ustawia `bottom: <gap>px` żeby CTA podążał za widocznym dołem ekranu. Resize/scroll listenery.

**iOS Safari fix dolnego CTA (`asiaauto-single.js`):**
```js
var bottomGap = window.innerHeight - (vv.height + vv.offsetTop);
mobileCta.style.bottom = bottomGap > 0 ? bottomGap + 'px' : '';
```

**Asset versioning:** `wp_enqueue_style/script` dla `aa-single` przerzucone z hardcoded `'0.25.0'` na `filemtime()` z fallback do `ASIAAUTO_VERSION` — automatyczne cache-busting przy edytach CSS/JS.

**`wp_kses` fix w `taxonomy-make.php` + `taxonomy-serie.php` (theme-side):**
Sanitizer `wp_kses()`/`wp_kses_post()` na zmergowanej zawartości `wiki + bar` strip'ował `<img fetchpriority="high" decoding="async" />` i `<svg>` z attrybutami nieuwzględnionymi w domyślnym allowlist. Skutek: niedomknięte `</div>` w kartach + zagnieżdżone `<a>` w `<a>` → karuzela "Najnowsze oferty" w hubach renderowała się jako wysokie kolumny pionowe (browser parse'ował broken HTML). Fix: split `$wiki` przez placeholder `{{LISTINGS_BAR}}`, sanityzowanie tylko user-side wiki content, `$bar` (nasz zaufany hub-listings HTML) wstawiany RAW.

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.28 → 0.32.29
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-single.php` — extracted `stickyHead()` + `infoBox()`, dwa renderingi w `render()`, filemtime cache busting
- `wp-content/plugins/asiaauto-sync/assets/css/asiaauto-single.css` — sekcja `@media (max-width: 768px)` z sticky head variants, hide breadcrumb/back, mobile info-box
- `wp-content/plugins/asiaauto-sync/assets/js/asiaauto-single.js` — visualViewport listener dla iOS Safari mobile CTA fix
- `themes/primaauto2026/taxonomy-make.php` + `taxonomy-serie.php` — split `$wiki/$bar` przy `wp_kses` (rozwiązanie karuzeli rozjechanej)
- `themes/primaauto2026/assets/css/footer.css` — mobile centered brand col + social icons
- `themes/primaauto2026/assets/css/hub.css` — `.aa-container { padding: 0 12px }` zamiast `0`, listing slider `flex-direction: row !important; flex-wrap: nowrap !important; flex: 0 0 70vw` na mobile, `aa-home__section-header { flex-wrap: wrap }`
- `themes/primaauto2026/assets/css/header.css` — `.pa-header { z-index: 9000 }` (było 100) — fix problemu zasłaniania mobile menu hamburgera przez sticky inventory toolbar
- `themes/primaauto2026/functions.php` — bump `PRIMAAUTO_THEME_VERSION` 1.0.4 → 1.0.6

**Smoke test:** `/oferta/<slug>/`, `/marki/`, `/samochody/<make>/`, `/samochody/<make>/<serie>/`, `/samochody/` → 200; rendered HTML zawiera oba sticky head variants, info-mobile + info-desktop, karty z `<img fetchpriority>` i zamkniętymi tagami.

**Rollback:** wszystkie pliki z .bak-2026-04-29-mobilesingle (plus header.css, hub.css, taxonomy-*.php, footer.css w temacie).

## 0.32.28 — 2026-04-29 (Cleanup serii pod GAC — usunięcie prefiksu „Trumpchi" z 4/7 modeli)

Po scaleniu marki (v0.32.27) seryjne kosmetyczne czyszczenie nazw 7 serii pod GAC z prefixem „Trumpchi" (E8, E8 PHEV, ES9 PHEV, E9 PHEV, M6, S7, M8). 4 udało się przepisać do czystych slugów; 3 zostały — kolizje slugów w taksonomii `serie` (globalnie unique w WP od 4.2):

**Zmienione (4 termy):**
- `Trumpchi E8` (3392) → `E8`, slug `e8`
- `Trumpchi E8 PHEV` (3389) → `E8 PHEV`, slug `e8-phev`
- `Trumpchi E9 PHEV` (3383) → `E9 PHEV`, slug `e9-phev`
- `Trumpchi ES9 PHEV` (3384) → `ES9 PHEV`, slug `es9-phev`

**Pozostawione (3 termy):**
- `Trumpchi M6` (3377) — kolizja z term 6557 (M6, parent=0, AITO orphan, 1 listing 283901)
- `Trumpchi S7` (3373) — kolizja z term 5674 (Luxeed S7, parent=6527, 10 listingów)
- `Trumpchi M8` (3381) — kolizja z term 3372 (GAC M8 zwykły, parent=6525, 53 listingi). Faktycznie inny model (Xiangwang M8 = top trim).

**Post_title batch (REPLACE w bezpiecznej kolejności od najdłuższego):**
1. `GAC Trumpchi ES9 PHEV ` → `GAC ES9 PHEV ` (3 wpisy)
2. `GAC Trumpchi E9 PHEV ` → `GAC E9 PHEV ` (5)
3. `GAC Trumpchi E8 PHEV ` → `GAC E8 PHEV ` (8)
4. `GAC Trumpchi E8 ` → `GAC E8 ` (4)
5. `GAC Trumpchi M8 ` → `GAC M8 ` (2 — historyczny listing pod term 3372 GAC M8 z błędnym title po v6.1; bonus cleanup; nie zmienia „Xiangwang M8")
6. `GAC Trumpchi Empow ` → `GAC Empow ` (1 — bonus cleanup)

Łącznie 23 listingi z post_title zaktualizowane.

**Brand-mapping (`data/brand-mapping-v6.1.php`):** 4 wpisy (`GAC Trumpchi|Trumpchi E8`, `…E8 PHEV`, `…ES9 PHEV`, `…E9 PHEV`) — `serie_eu`, `title_eu`, `slug` zmienione z prefiksowanych na czyste (E8/E8 PHEV/...). Inne wpisy GAC Trumpchi nietknięte (Xiangwang M8/S7 zachowują pełną nazwę CN, M6 z prefixem).

**Redirecty 301 (`class-asiaauto-redirects.php::V62_SERIE_REDIRECTS`):** dodana sekcja `'gac' => [...]` z 4 mapowaniami starych slugów (`trumpchi-e8` itd. → `e8` itd.). Łącznie z istniejącymi `byd`/`zeekr`/`voyah`/`dongfeng` jeden wspólny mechanizm dla orphan-fix duplicate slug redirects.

**Smoke test (curl):**
- `/samochody/gac/e8/`, `/e8-phev/`, `/e9-phev/`, `/es9-phev/` → 200
- `/samochody/gac/trumpchi-e8/` itd. → 301 → odpowiednio czysty slug
- `/samochody/gac/trumpchi-m6/`, `/trumpchi-s7/`, `/trumpchi-m8/` → 200 (zachowane)

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.27 → 0.32.28
- `wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php` — 4 wpisy zaktualizowane
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-redirects.php` — `V62_SERIE_REDIRECTS['gac']` (+6 linii)
- legacy `~/domains/asiaauto.pl/...` — sync 3 plików

**Pending (kosmetyka, niski priorytet):**
- `Trumpchi M6` cleanup wymagałby usunięcia/przeniesienia term 6557 (orphan AITO M6, parent=0, listing 283901 → powinien być pod właściwym AITO term). To by też naprawiło inny orphan z raportu 2026-04-28.
- `Trumpchi S7` cleanup wymagałby zmiany slug Luxeed S7 (term 5674) — nieproporcjonalne ryzyko dla SEO Luxeed.
- `Trumpchi M8` (Xiangwang M8) — nazwa słusznie zachowana, top trim ≠ podstawowa M8.

## 0.32.27 — 2026-04-29 (GAC Trumpchi → GAC merge — domknięcie residuals v6.1)

Domknięcie świadomie zostawionego residuum z v6.1 (2026-04-23): marka `GAC Trumpchi` (term_id 3368, 11 listingów) była utrzymywana obok `GAC` (6525), co generowało dublujące się prefiksy w post_title („GAC Trumpchi Trumpchi M6"). Po raporcie orphan-fix z 2026-04-28 i diagnozie obecnej sesji decyzja: zmergować w jedną markę GAC.

**Migracja DB (live na produkcji + legacy asiaauto.pl plik plugin):**
1. **Reparent serii** — `Trumpchi M6` (3377) i `Trumpchi E9 PHEV` (3383) zmienione `parent` z 3368 na 6525 w `wp7j_term_taxonomy`.
2. **Reparent listingów** — 12 wpisów w `wp7j_term_relationships` z `term_taxonomy_id=3368` przeniesione na 6525. Zero kolizji (żaden listing nie miał już GAC).
3. **Postmeta cleanup** — `meta_key=make` z wartością `gac-trumpchi` → `gac` (67 listingów; legacy meta nieużywane przez plugin/theme, ale spójne z taksonomią).
4. **Termmeta cleanup** — `_asiaauto_primary_make_slug` na seriach 3377 i 3383: `gac-trumpchi` → `gac`.
5. **Post_title batch update** — `REPLACE('GAC Trumpchi Trumpchi', 'GAC Trumpchi')` w `post_title` dla `post_type='listings'`. 13 listingów (12 z mojej listy + 1 historyczny ID 239842 „GAC Trumpchi Trumpchi M8" → „GAC Trumpchi M8").
6. **Recount** — `wp_update_term_count_now()` dla terms 6525, 3377, 3383. GAC: 112 → 123 (publish only; 134 łącznie w relationships).
7. **Usunięcie term 3368** — `wp term delete make 3368` (kasuje też termmeta: rank_math_*, asiaauto_wiki_body, asiaauto_seo_desc, asiaauto_faq_json, _asiaauto_desc_*).
8. **Brand-mapping uzupełniony** — `data/brand-mapping-v6.1.php` dopisane wpisy `'GAC Trumpchi|Trumpchi M6'` i `'GAC Trumpchi|Trumpchi E9 PHEV'` → `mark_eu=GAC`, zachowujące prefix „Trumpchi" w `serie_eu`/`title_eu`/`slug` (spójnie z istniejącymi wpisami E8/Xiangwang M8/S7/E8 PHEV/ES9 PHEV). Bez tego importer przy reimporcie wracałby do tworzenia term 3368 ponownie.

**Redirect 301 już istniał** (`V61_MAKE_REDIRECTS` w `class-asiaauto-redirects.php:37`): `gac-trumpchi → gac`. `/samochody/gac-trumpchi/*` → `/samochody/gac/*` ✓.

**Smoke test (curl):**
- `/samochody/gac/trumpchi-m6/` → 200
- `/samochody/gac/trumpchi-e9-phev/` → 200
- `/samochody/gac-trumpchi/trumpchi-m6/` → 301 → `/samochody/gac/trumpchi-m6`
- `/samochody/gac-trumpchi/` → 301 → `/samochody/gac`
- `/samochody/gac/` → 200

**Backup DB:** `~/backups/primaauto/2026-04-29-gac-trumpchi-merge/terms-full.sql` (8.4 MB — wp7j_terms, term_taxonomy, term_relationships, termmeta).

**Pliki zmienione:**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.26 → 0.32.27 (header + ASIAAUTO_VERSION).
- `wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php` — +14 linii (2 wpisy).
- `domains/asiaauto.pl/.../asiaauto-sync.php` + `brand-mapping-v6.1.php` — sync legacy (rollback).

**Co odpada w przyszłych sesjach:** raport orphan-fix `tmp/missing-hubs-2026-04-28.md` linie z Trumpchi M6/E9 PHEV — już rozwiązane (pod prawidłowym parent).

**Pending kosmetyka (nie blokuje):** serie pod GAC z prefixem „Trumpchi" w nazwie (`Trumpchi E8`, `Trumpchi M8`, `Trumpchi S7`, `Trumpchi E8 PHEV`, `Trumpchi ES9 PHEV`, `Trumpchi M6`, `Trumpchi E9 PHEV`) — można w v6.2 wyczyścić do `E8`, `M8` itd. Wymagałoby: rename term name (zachowując slug), batch update post_title, regen sitemap.

## 0.32.26 — 2026-04-29 (Social media — sameAs schema + ikony w stopce)

W sesji konfiguracji wizytówki Google Business Profile dodano profile social media (FB / IG / TT) na stronie:

1. **`sameAs` w `AutoDealer` schema na home** (`class-asiaauto-homepage.php::renderSchema`) — array z 3 URL:
   - `https://www.facebook.com/prima1auto/`
   - `https://www.instagram.com/prima_auto.pl/`
   - `https://www.tiktok.com/@primaauto.pl`
2. **`sameAs` w `LocalBusiness` schema na /kontakt/** (`class-asiaauto-contact.php::renderSchema`) — te same 3 URL.
3. **Ikony social w stopce theme primaauto2026** (`themes/primaauto2026/footer.php`) — pod `<p class="pa-footer__about">` w kolumnie brand. Inline SVG (FB/IG/TT), `target="_blank" rel="noopener nofollow"`. Lista `<ul class="pa-footer__social">` z aria-label.
4. **CSS w `themes/primaauto2026/assets/css/footer.css`** — `.pa-footer__social` (flex gap 10px), kółko 38×38 z `rgba(255,255,255,.08)`, hover na `var(--c-secondary)` z translateY(-1px).

**Backupy:** `class-asiaauto-homepage.php.bak-2026-04-29-social`, `class-asiaauto-contact.php.bak-2026-04-29-social`, `footer.php.bak-2026-04-29-social`, `footer.css.bak-2026-04-29-social`.

**Smoke test:** `curl https://primaauto.com.pl/` + `/kontakt/` — `sameAs` w obu schemach ✓, footer renderuje ikony ✓ (200 OK).

**Dlaczego ważne:** `sameAs` to oficjalny sygnał Schema.org dla Google Knowledge Graph — łączy wizytówkę GBP, profile social i stronę w jeden węzeł tożsamości firmy. Bez `sameAs` Knowledge Panel zostaje pusty (brak boxa „Profile") i Google ma trudność potwierdzić, że profil FB/IG i strona to ten sam podmiot. Wzmacnia E-E-A-T.

## 0.32.25 — 2026-04-29 (Schema NAP fix — AutoDealer name + usunięty numberOfEmployees bug)

Audyt przed wizytówką Google ujawnił dwa problemy w schema na home (`class-asiaauto-homepage.php::renderSchema`):

1. **Niespójność NAP:** `AutoDealer.name = "Prima Auto"` (bez myślnika) vs `LocalBusiness.name = "Prima-Auto"` na `/kontakt/` (z myślnikiem). Google bierze niespójność NAP jako negatywny sygnał Local SEO. Fix: ujednolicenie do `"Prima-Auto"` (zgodnie z legalName "Prima-Auto Ruslan Prima"). Dotyczy też `WebSite.name` ("Prima Auto — Samochody z Chin" → "Prima-Auto — Samochody z Chin").
2. **Bug `numberOfEmployees`:** `numberOfEmployees: { value: 2677, unitText: "vehicles in stock" }` — pole Schema.org `numberOfEmployees` opisuje LICZBĘ PRACOWNIKÓW (oczekiwany QuantitativeValue z liczbą osób), nie samochodów. Google validator może zignorować cały blok lub flaguje inconsistency. Fix: pole usunięte. Stock count i tak jest pokazywany przez `Product/AggregateOffer` per hub modelu (v0.32.23) — nie potrzebujemy go w org schema.

**Backup:** `class-asiaauto-homepage.php.bak-2026-04-29-schema-nap`.

**Smoke test home:**
- Przed: `"name": "Prima Auto"` + `numberOfEmployees: 2677`
- Po: `"name": "Prima-Auto"` + brak numberOfEmployees ✓

**Dlaczego ważne:** w sesji wizytówki Google (Google Business Profile) kluczowe jest aby NAP na stronie 1:1 zgadzało się z nazwą GBP i wizytówką w Knowledge Panel. Niespójność typu "Prima Auto" vs "Prima-Auto" działa jak dwa różne podmioty dla algorytmu Local SEO — utrudnia łączenie sygnałów.

## 0.32.24 — 2026-04-29 (Single listing — sekcja „Inne modele tej samej marki")

- **Internal linking single → hub modelu** (TODO #3 z planu SEO 2026-04-29). Single listing wcześniej linkował do hubów tylko przez breadcrumb i CTA „Wróć do wyników" — brak dedicated cross-link do sibling modeli tej samej marki.
- **Nowa metoda `relatedModels(array $d)`** w `class-asiaauto-single.php`:
  - Pobiera make_term i serie_term listingu przez `wp_get_object_terms`.
  - `get_terms` po taxonomy=serie z parent=make.term_id, exclude=[serie.term_id], hide_empty=true, orderby=count DESC, limit=8.
  - Render: `<section class="aa-related">` z grid kafelek (auto-fill, min 180px). Każdy kafelek = nazwa modelu (display_name termmeta lub fallback name) + count z polską odmianą („27 ofert", „2 oferty", „1 oferta").
  - Link do `get_term_link($sibling)` = hub modelu.
- **Wstawione w `render()`** po `[asiaauto_equipment]`, przed zamknięciem `aa-single__main`. Mobile sticky CTA (`mobileCta`) nadal na końcu.
- **CSS** w `assets/css/asiaauto-single.css`: `.aa-related` (margin-top 32px, separator border-top), `.aa-related__grid` (CSS grid auto-fill), `.aa-related__item` (border 1px, hover translateY+border-accent).
- **Smoke test:** `/oferta/byd-han-dm-2025-96111/` (BYD Han DM-i):
  - Tytuł: „Inne modele BYD" ✓
  - Wyklucza Han DM-i (serie listingu) ✓
  - 8 modeli sortowanych count DESC: Tang DM-i (37), Seal 6 DM-i (37), Song Pro DM-i (29), Qin L DM-i (25), Song L DM-i (21), Song L EV (19), Seal U DM-I (17), Atto 2 (16)
  - Leopard 3 (15, pozycja 9 w DB) odcięty przez limit ✓
- **Dlaczego ważne:** każde single listing daje 8 nowych internal links do hubów modeli tej samej marki. Skala: ~1841 listings × 8 = ~14k nowych internal linków po stronie. To wzmacnia hub authority i daje user-flow „BYD Han DM-i → Tang DM-i" zamiast „BYD Han DM-i → /samochody/" (utrata kontekstu marki).
- **Backupy:** `class-asiaauto-single.php.bak-2026-04-29-related`, `asiaauto-single.css.bak-2026-04-29-related`.

## 0.32.23 — 2026-04-29 (SEO: Product/AggregateOffer hub modelu + lifecycle 301 sprzedanych)

Dwa wins z planu SEO (audyt 2026-04-29 popołudnie):

### #1 Product + AggregateOffer schema na hub modelu (`class-asiaauto-seo.php`)

- **Nowa metoda `buildProductForSerieHub($ctx)`** — emituje `@type: Product` z `offers: AggregateOffer { lowPrice, highPrice, offerCount, priceCurrency, availability }` dla huba modelu (`is_hub === 'serie'`). Hub marki celowo pominięty (zbyt szeroka grupa, niska trafność dla Product Snippet).
- **Nowa `getPriceStatsForTerm(WP_Term $term)`** — single SQL query (JOIN posts × postmeta `price` × term_relationships × term_taxonomy) liczy MIN/MAX/COUNT po wszystkich publish listingach w danym serie term. Filtruje `price > 0`.
- **Wstawione do `renderSchema()`** w obu gałęziach (RankMath ON / OFF).
- **Smoke test:** `/samochody/byd/leopard-8/` → schema dokładnie zgodne z DB:
  - DB: `lowPrice=283000, highPrice=325000, offerCount=5`
  - HTML schema: identyczne wartości ✓
- **Dlaczego ważne:** Google Vehicle Search i Product Snippet wyciągają „od X PLN" z `lowPrice` w `AggregateOffer`. Każdy hub modelu z N>0 listingów dostaje rich result.

### #2 Lifecycle 301 sprzedanych listings → hub modelu (`class-asiaauto-redirects.php`)

- **Modyfikacja `detectListingNotFound()`** — przed dotychczasowym fallbackiem (`is_listing_404 = true` + static make context dla shortcode `[asiaauto_404_listing]`) próbuje `resolveHubUrlForListing($post_id)` i robi `wp_safe_redirect($hub_url, 301); exit;`.
- **Nowa `resolveHubUrlForListing($post_id)`** — preferowany hub modelu (taxonomy=serie), fallback hub marki (taxonomy=make). `wp_get_object_terms` zwraca terms niezależnie od post_status, więc działa dla draft i trash do permanent delete (~30 dni po sprzedaży).
- **Smoke test:**
  - `/oferta/byd-song-l-ev-2025-100886/` (draft) → 301 → `/samochody/byd/song-l-ev/` (200) ✓
  - `/oferta/zeekr-001-2025-108296/` (draft) → 301 → `/samochody/zeekr/001/` (200) ✓
  - `/oferta/nieistniejacy-slug-2024-99999999/` (deleted) → 404 ✓ (poprawny fallback gdy post nie istnieje)
- **Dlaczego ważne:** sprzedane listingi (publish→draft→trash w `class-asiaauto-rotation.php`) wcześniej dawały soft 404 w GSC i traciły equity z backlinków/historycznego rankingu. Teraz 301 do hub modelu kumuluje sygnały SEO na hubach, które są właściwym targetem dla brand+model queries.

### Backupy
- `class-asiaauto-seo.php.bak-2026-04-29-aggoffer`
- `class-asiaauto-redirects.php.bak-2026-04-29-301hub`

## 0.32.22 — 2026-04-29 (Single listing — netto pod brutto, regresja po migracji theme z Elementor)

- **Przywrócenie linii „netto: X PLN"** pod ceną brutto na single listing. Regresja z 2026-04-24 (cutover Elementor → primaauto2026): stary template Elementora 101874 używał shortcode `[asiaauto_price_breakdown]` (rozbicie brutto + netto, VAT 23%); nowy `single-listings.php` woła `[asiaauto_single]`, który w `class-asiaauto-single.php::sidebar()` renderował tylko brutto.
- **Zmiana w `class-asiaauto-single.php:312-321`** (gałąź `if` z ceną): dodany `<span class="aa-single__price-netto">` z netto = brutto / 1.23 (VAT hardcode 23%, spójnie z `[asiaauto_price_breakdown]` w `class-asiaauto-shortcodes.php:1617-1618`). Gałąź `else` („Cena na zapytanie") bez zmian.
- **CSS** w `assets/css/asiaauto-single.css:34`: nowa reguła `.aa-single__price-netto` (14px, var(--sec) szary, font-weight 500, display block).
- **Smoke test:** `/oferta/denza-d9-dm-2024-94073/` → brutto 247 000 PLN + netto 200 813 PLN ✓.
- **Backupy:** `class-asiaauto-single.php.bak-2026-04-29-netto`, `asiaauto-single.css.bak-2026-04-29-netto`.

## 0.32.21 — 2026-04-29 (Stock bary na hub make/serie — przed głównym contentem)

- **Hub make/serie pokazują stock listings PRZED głównym contentem.** User insight: "tych aut nie będziemy mieć dużo, możemy wyciągnąć w Rzeszowie/w drodze przed nowościami" — eksponuj realnie dostępne pojazdy z stocku sprzedawcy na każdym hubu marki/modelu, jeśli istnieją.
- **Shortcode `[asiaauto_hub_listings]` rozszerzony o `reservation_status` parametr** w `class-asiaauto-brand-hub.php`:
  - Filter `reservation_status="on_lot"` lub `"in_transit"` (whitelist).
  - Pusty wynik dla filtra → zwraca `''` cicho (nie pokazuje "Brak ofert").
  - Meta_query dodany do WP_Query.
- **Templates `taxonomy-make.php` + `taxonomy-serie.php`** (theme primaauto2026):
  - 2 nowe shortcody przed `<section class="aa-hub__body">` w nowej `<section class="aa-hub__stock">`:
    - `🇵🇱 {model/marka} — na placu w Rzeszowie` (CTA: `/w-rzeszowie/`)
    - `🚢 {model/marka} — w drodze do Polski` (CTA: `/w-drodze/`)
  - n=6 sztuk na sekcję, sortowanie domyślne (data DESC).
  - Sekcja renderowana **warunkowo** — tylko jeśli przynajmniej jedna z dwóch sub-sekcji ma listings (else nic nie pokazuj, brak placeholdera).
- **Test:** `/byd/sealion-8-dm-i/` pokazuje obie sekcje (1 + 1 listing); `/byd/` (make) pokazuje agregat marki (5 + 9); `/byd/tang-dm-i/` (brak stocku) → 0 sekcji aa-hub__stock ✓.
- **Spójność z v0.32.19/20:** te same emoji + colors + badge na karcie. Klient widzi status w warstwach: home Stock Highlights → hub make/serie stock bars → per-listing badge.

## 0.32.20 — 2026-04-29 (Listing card badges priorytet on_lot/in_transit/nowe)

- **Listing card badges priorytet:** `on_lot` (`🇵🇱 Na placu`, zielony #1B5E20) → `in_transit` (`🚢 W drodze`, niebieski #0D47A1) → `is_new` (`Nowe`, żółty #E8AC07 — fallback). Dotąd listingi miały tylko badge "Nowe" (post < 24h) — nie eksponowały statusu fizycznego pojazdu na froncie.
- **Implementacja w 2 plikach:**
  - `class-asiaauto-homepage.php::getLatestListings()` + `renderLatest()` — sekcja "Najnowsze oferty samochodów z Chin" na home.
  - `class-asiaauto-brand-hub.php::renderListings()` — shortcode `[asiaauto_hub_listings]` używany na hub make/serie.
- **CSS** w `class-asiaauto-homepage.php::renderCSS()` — dodane warianty `.aa-home__car-badge--pl` i `.aa-home__car-badge--transit` (dziedziczą positioning z bazowego `.aa-home__car-badge`).
- **Test live:** `/samochody/byd/sealion-8-dm-i/` pokazuje "🇵🇱 Na placu" na pierwszym listingu (BYD Sealion 8 z `_asiaauto_reservation_status=on_lot`).
- **Spójność z Stock Highlights** (v0.32.19): te same emoji + colors. Sekcja "Auta dostępne teraz" na home + badge na każdej karcie listingu = pełna sygnalizacja statusu fizycznego pojazdu w obu warstwach (home overview + per-listing).

## 0.32.19 — 2026-04-29 (Stock Highlights na home)

- **Sekcja "Auta dostępne teraz" (W Polsce + W drodze)** na stronie głównej — między `renderLatest` a `renderMakes`. User insight: "sprzedawca wie co się powinno teraz sprzedawać" — auta z `_asiaauto_reservation_status` ∈ `{in_transit, on_lot}` to realne flagshipy biznesowe (sprzedawca już zainwestował). Trust signal + 12 internal links z home do hub modeli (poprzednio 0).
- **Implementacja w `class-asiaauto-homepage.php`:**
  - `getStockHighlights()` — DB query po listings z `_asiaauto_reservation_status`, JOIN make+serie, grupowanie per model (make_slug+serie_slug) z licznikami `on_lot`/`in_transit`, sort priorytet on_lot DESC. Cache transient 1h.
  - `renderStockHighlights()` — kafelki (thumb 4:3, title, badge `🇵🇱 W Polsce: N` + `🚢 W drodze: N`). CTA: linki do `/w-rzeszowie/` i `/w-drodze/` (istniejące strony z shortcodami `[asiaauto_inventory reservation_status="on_lot|in_transit"]`).
  - `invalidateStockOnMeta()` — hooki `updated/added/deleted_post_meta` dla klucza `_asiaauto_reservation_status` flushuje transient.
- **CSS** ~30 linii w `renderCSS()`: grid responsywny (2 kolumny <600px), card hover, badges (zielony PL / niebieski transit), CTA primary buttons.
- **Dane na deploy:** 12 unique modeli (5 on_lot + 9 in_transit). Top: BYD Sealion 8 (1+1), BYD Leopard 5 (1+1), Geely Monjaro (1), Denza N8L DM (1), Mazda EZ-6 (1), reszta in_transit-only.
- **Decyzja modele vs listingi:** modele wygrywają — hub trwa wiecznie a single listing znika po sprzedaży = SEO equity przepada; linki do hub kumulują authority dla "BYD Leopard 5/8" itd. które chcemy rankować; badge "1 dostępne dziś" daje trust + klient klika do hub gdzie widzi WSZYSTKIE oferty modelu.

## 0.32.18 — 2026-04-29 (sesja nocna)

- **GSC sitemap fix**: Google indeksował nas na bazie starego `wp-sitemap.xml` (WP native, RankMath ma `noindex` na nim) — submitted 2026-04-23, downloaded 2026-04-27 z 1 warning. **Submit nowych 8 sitemap RankMath** przez Search Console API: `/sitemap_index.xml` + child sitemaps (`page-sitemap.xml`, `make-sitemap.xml`, `serie-sitemap.xml`, `listings-sitemap1-4.xml`). Wszystkie 8 z 0 errors, 0 warnings. Po tym Google zacznie crawl-ować huby modeli (były "URL is unknown to Google" przed).
- **GSC indeks audyt** (11 kluczowych URLs): `/`, `/samochody/`, `/marki/`, `/samochody/byd/` = **PASS** (zaindeksowane, last crawl 2026-04-28). Hub modeli = **NEUTRAL "URL is unknown to Google"** lub "Discovered - currently not indexed" (5/7 hub serie sample). Pierwszy listing single = "URL is unknown" — czyli Google nie wszedł jeszcze głębiej. Sitemap fix + title fix v0.32.17 powinien to odblokować.
- **Search Analytics top 20 queries (28 dni)** — 153 impr na home pos=5.8, brand-dominant (prima auto rzeszów / prima auto / prima-auto). Long-tail flagship już widoczne: `denza z9 gt` pos=42, `geely galaxy starship 8` pos=11, `aito seres` pos=1, `icar v23 cena w polsce` pos=9, `mg auto import` pos=11. Domena świeża, ranking rośnie naturalnie.
- **4 nowe orphan parents serie** (importer dorzucił po popołudniowej naprawie): 3 unique parent fix (`smart #3`, `Jetour X70 PRO`, `Mazda 3 Axela`) + 1 duplikat MERGE (`#6553 Seal U DM-I (Song Plus)` orphan → `#3702` keeper, `seal-u-dm-i` slug). 301 redirect już istnieje w V62_SERIE_REDIRECTS od popołudnia. `serie-broken-parent: 4 → 0`.
- **Chinese-chars batch ×2** w `translations-complectations.php`:
  - +18 entries TIER 4 (`二`→II, `超`→Super, `星夜`→Starnight, `智能超`→Smart Super, `超然致远`→Transcendent Vision, `陆冠`→Land Crown, `星空龙耀`→Starsky Dragon, `定制`→Custom, `首发`→Debut, `途昂`→Teramont, `出众`→Outstanding, `骑士`→Knight, `致行`→Drive, `自在`→Free, `花生`→Peanut, `银河`→Galaxy, `旅行升级`→Travel Upgrade, `纵野`→Wild, `享境`→Journey).
  - +2 entries (`智能`→Smart, `星月女神`→Star Goddess).
  - **chinese-chars: 26 → 8** (dwa tytuły wciąż failują, fragment `为`/`然致远` to sub-fragmenty oraz nowo zaimportowane).

## 0.32.17 — 2026-04-28

- **KRYTYCZNY FIX: title/meta/schema dla hub MODELU.** User zauważył że hub `/samochody/byd/leopard-8/` ma w `<head>` title z hub MARKI: `"BYD — Auto z Chin | Prima-Auto"` zamiast `"BYD Leopard 8 (Denza B8) — Import z Chin | Prima-Auto"`. Powód: WP rewrite `^samochody/(make)/(serie)/?$` ustawia oba query vars, ale **`get_queried_object()` zwraca pierwsze (make=BYD)** — RankMath/theme/schema generują z perspektywy hub MARKI. Każdy hub modelu Google indeksował jako duplikat hub make → 0 rank dla "BYD Leopard 8/5/7", "Denza Z9", itd.
- **Fix w `class-asiaauto-brand-hub.php`** — nowy hook `wp` (prio=5) `fixQueriedObjectForSerieHub()`: dla URL z make+serie nadpisuje `$wp_query->queried_object` na **serie** term + `is_tax=true`. RankMath teraz widzi Leopard 8 jako queried object → bierze `rank_math_title`/`rank_math_description`/`rank_math_focus_keyword` z termmeta serie. Test:
  - `/byd/leopard-8/` → `BYD Leopard 8 (Denza B8) — Import z Chin | Prima-Auto` + meta desc z 3 egzemplarzy 2025...
  - `/byd/leopard-5/` → `BYD Leopard 5 (Denza B5) — Import z Chin | Prima-Auto`
  - `/byd/leopard-7/` → `BYD Leopard 7 (Tai 7) FCB, PHEV — Import z Chin | Prima-Auto`
  - `/aito/m8/` → `AITO M8 — Import z Chin | Prima-Auto`
- **Bonus**: dodane `renderRankMathTitle()` helper resolves `%term%/%sep%/%sitename%/%title%` placeholders w stored RankMath title patterns dla `filterHubTitle` + `overrideHubDocumentTitle` (priorytet rank_math_title term meta jeśli istnieje).

## 0.32.16 — 2026-04-28

- **Sync `wiki_body + FAQ` → natywny `term->description` (RankMath SEO Analyser).** User-pytanie: RankMath nie ma czego analizować bo content jest w custom termmeta (`asiaauto_wiki_body`, `asiaauto_faq_json`), a natywne `term->description` było puste. Fix:
  - **REST endpoint `POST /wp-json/asiaauto/v1/hub-content/{tax}/{id}`**: przy save `wiki_body` syncuje do `term->description`. Przy save `faq_json` dorzuca FAQ jako `<h2 class="aa-rm-faq">...</h2>` + `<details><summary>Q</summary>A</details>` po wiki_body. n8n NIE zmienia się (nadal woła ten sam endpoint).
  - **Backfill całego DB** (raw SQL UPDATE wp7j_term_taxonomy, omija `wp_kses_post`): 49 make + 307 serie termów ma teraz `description` = `wiki_body + FAQ HTML5`. Przykład Chery (term_id 3578): 9950 chars, 5× h2, 8× details. RankMath SEO Analyser teraz analizuje pełen content.
- **FAQPage schema NIE duplikat** — RankMath rozpoznaje tylko własny block `wp:rank-math/faq-block`, NIE rozpoznaje natywnych `<details>` jako FAQ → nie generuje drugiej FAQPage. Sprawdzone: hub `/samochody/chery/` ma nadal 1× FAQPage (nasz custom z `class-asiaauto-brand-hub.php`). GSC FAQ rich results bezpieczne.
- **Frontend nieaktualizowany** — template `taxonomy-make.php`/`taxonomy-serie.php` renderuje wiki_body + FAQ z termmeta (nie z `description`). Wizualnie strona bez zmian. RankMath analizuje description niezależnie od frontu.

## 0.32.15 — 2026-04-28

- **Car schema parity vs west-motors**: dodane `manufacturer` (Organization z brand name) + `offers.priceValidUntil` (+90 dni od teraz, format YYYY-MM-DD). Drobne quality signals dla Google Product Snippet (bold price w SERP). Schema validator (schema.org/validate): **0 errors, 0 warnings** — 14 typów rozpoznanych (Car, Brand, Organization, Offer, OfferShippingDetails, ShippingDeliveryTime, MonetaryAmount, EngineSpecification, QuantitativeValue, DefinedRegion, Country, BreadcrumbList, ListItem, Thing).
- Sitemap audit: 4 `listings-sitemap{1-4}.xml` × 200 URL = ~800 listings indexable. Najnowszy lastmod: 2026-04-28T19:07:46Z (świeży). RankMath emituje sitemap_index.xml + listings-* + page-sitemap.xml. Robots.txt zawiera referencję.

## 0.32.14 — 2026-04-28

- **TIER 3 ×1 corner cases** — `data/translations-complectations.php` +25 entries (user-aprobowane wszystkie ✓+⚠): `尊`→Honor, `劲`→Power, `山河`→Mountain, `东方曜`→Eastern Glory, `今朝`→Today, `御`→Royal, `加长`→Extended, `征服`→Conqueror, `超长蓝鲸`→Long Range Blue Whale, `辰光`→Starlight, `美好`→Wonderful, `过道`→Walkway, `新蓝鲸`→New Blue Whale, `天枢`→Tianshu, `乘用`→Passenger, `领先`→Leading, `龙腾`→Dragon, `灵`→Spirit, `省心`→Worry-Free, `奢享`→Luxury Premium, `幸福`→Happiness, `真香`→Sweet Spot, `跃享`→Joy Premium, `劲为`→Power, `巡礼众享款`→Pilgrimage Edition. Retranslate: 44 → 24 tytułów chińskich (20 naprawionych jednorazowo + trwały efekt na importer).

## 0.32.13 — 2026-04-28

- **Car schema enrichment + OfferShippingDetails na single listings.** `class-asiaauto-single.php::renderMeta()` wzbogacone (utracone przy dedup 2026-04-24): `bodyType`, `vehicleTransmission`, `driveWheelConfiguration`, `color`, `itemCondition` (z taksonomii body/transmission/drive/color/condition), `vehicleEngine` (enginePower KW z `power($ep)` na bazie `_asiaauto_extra_prep` `energy_elect_max_power`). Plus `offers.shippingDetails` (`OfferShippingDetails`): shippingRate 0 PLN, addressCountry PL, handlingTime 0-7 dni, transitTime 56-84 dni — gap vs west-motors zamknięty. Test #281026: 16 kluczy w Car schema (vs 11 wcześniej), 392 KW enginePower, AWD, SUV, UsedCondition, full ShippingDetails.
- **`银河A7 EM` term cleanup.** Term #6539 rename `银河A7 EM` → `Galaxy A7 EM-i` + parent change Galaxy (#3394) → Geely (#3626). 5 listingów strip `银河` z post_title (`Galaxy 银河A7 EM` → `Galaxy A7 EM`). URL `/samochody/galaxy/a7-em/` → 301 → `/samochody/geely/a7-em/` (V61 redirect).

## 0.32.12 — 2026-04-28

- **Tłumaczenia TIER 1⚠ + TIER 3 (25 nowych w `translations-complectations.php`).** User-aprobowane:
  - **TIER 1⚠** (5): `奕派007`→`ePi 007`, `奕派008`→`ePi 008`, `奕派`→`ePi`, `途昂Pro`→`Pro`, `高山8`→`Gaoshan 8` (`超级混`→`Super Hybrid` był już w mapie). Per analizy `tmp/chinese-fragments-analysis-2026-04-28.md`.
  - **TIER 3** (20 top frequency): `式`→`Style`, `商用`→`Commercial`, `智享`→`Smart Premium`, `智慧新蓝鲸`→`Blue Whale 2.0`, `万辆甄选款`→`Selected Edition`, `超越`→`Surpass`, `进取`→`Progressive`, `超级`→`Super`, `战舰`→`Battleship`, `至臻`→`Premium+`, `智雅`→`Smart Elegance`, `鸿蒙座舱`→`HarmonyOS Cabin`, `智逸`→`Smart Comfort`, `悦享`→`Joy Edition`, `向往`→`Aspire`, `传奇`→`Legend`, `冠军`→`Champion`, `磷酸铁锂`→`LFP`, `停产`→`Discontinued`, `运`→`Sport`.
- Mapa używana zarówno przez `wp asiaauto diag apply-fix chinese-chars` (retranslate post_title) jak i przez `class-asiaauto-translator.php:750` przy każdym imporcie z dongchedi → trwały efekt na obecne (98 → 50 tytułów chińskich) + przyszłe.
- **Termmeta `marka=` link sync** (47 termmeta rows): w `asiaauto_wiki_body` zamieniono stare slugi v6.1 marek (`marka=fangchengbao` → `marka=byd`, `marka=galaxy` → `marka=geely`, etc.) na docelowe — eliminacja "linki do Fangchengbao" w hub Leopard/Voyah/itd. po migracji v6.1 marek (treści generowane 2026-04-23 przed v6.1).

## 0.32.11 — 2026-04-28

- **Hub fallback luka: `/samochody/{make}/{cokolwiek}/` → 404.** Wcześniej dowolny string w drugim segmencie URL renderował hub marki (200 + index) — `/samochody/byd/cokolwiek-fake-12345/` zwracał `BYD — Auto z Chin` z `index, follow`. Każdy crawler/spam/typo URL = thin index = duplicate content. Fix w `class-asiaauto-cpt.php::filterHubQuery()`: jeśli `make` lub `serie` term nie istnieje (po `get_term_by('slug', ...)`), `$q->set_404() + status_header(404) + nocache_headers()`. Test: fake → 404 ✓, prawdziwy hub → 200 ✓, V62 redirects → 301 ✓.

## 0.32.10 — 2026-04-28

- **`/zamow/?listing_id=X` → noindex,follow.** User zauważył że formularz wizard zamówienia per listing jest indeksowalny — 1841 ogłoszeń × identyczny szablon = duplicate content na masową skalę. Canonical do `/zamow/` był ustawiony, ale Google czasem ignoruje canonical przy silnych sygnałach (np. linki wewnętrzne z każdego ogłoszenia). Fix analogiczny do v0.32.8 inventory filters: nowa metoda `isOrderWizardPerListing()` w `class-asiaauto-seo.php` (detekcja `post_name='zamow'` + `$_GET['listing_id']`) podpięta w obu hookach `wp_robots` + `rank_math/frontend/robots`. Test: `/zamow/?listing_id=278417` → noindex,follow ✓; `/zamow/` (bez param) → index,follow ✓.

## 0.32.9 — 2026-04-28

- **Dedup orphan-fix duplicates + V62_SERIE_REDIRECTS.** Fix błędu z 0.32.0 `serie-broken-parent apply`: 70 orphan termów dostało parent, ale 11 z nich to były duplikaty istniejących keeperów v6.1 (np. `zeekr-9x`/`9x`, `leopard-5-denza-b5`/`leopard-5`, `atto-3-yuan-plus`/`atto-3`, `seal-u-dm-i-song-plus`/`seal-u-dm-i`, `voyah-taishan`/`taishan`...). Każdy duplikat = 2 huby na ten sam model = split SEO. Naprawa:
  1. **Skrypt `tmp/merge-11-duplicates.php`** — re-tag listings (INSERT IGNORE term_relationships) → keeper, DELETE orphan term, recount keeper. 11/11 OK.
  2. **`class-asiaauto-redirects.php`** — dodana stała `V62_SERIE_REDIRECTS` (mapa per-make `[old_slug → new_slug]`) + metoda `redirectV62SerieDuplicates()` (priorytet 0, wzorzec V61). 11 starych URLs dostaje 301 → keeper.
  3. **termmeta `_asiaauto_primary_make_slug`** — 32 termy zsynchronizowane z v6.1 marek (fangchengbao→byd, galaxy→geely, gac-trumpchi→gac, itd.). Bez tego breadcrumb na hubach pokazywał starą markę (`Fangchengbao` zamiast `BYD`).
- Wynik: serie-broken-parent 70 → 0; duplicate-serie-terms 37 → 29 (reszta = intencjonalne sub-warianty EV/DM); BYD hub clean (1 term per model); breadcrumb po v6.1 marek poprawny. Backup pre-merge: `~/backups/primaauto/2026-04-28-orphan-parent-fix/terms-pre-fix.sql` (3.7 MB).

## 0.32.8 — 2026-04-28

- **Inventory filter URLs noindex.** User zauważył w Screaming Frog że `/samochody/?nadwozie=suv`, `/samochody/?paliwo=hybrid`, `/samochody/?marka=byd` itd. są nadal indeksowalne — duplikat treści z hubów (`/samochody/byd/`). Pierwszy fix v0.32.6 używał `is_post_type_archive('listings')`, ale to nie zwraca true bo `/samochody/` to **WP page z shortcode `[asiaauto_inventory]`**, nie WP archive. Zmiana detekcji na `has_shortcode($post->post_content, 'asiaauto_inventory')` + sprawdzenie `$_GET` z whitelistą filter params (`nadwozie, paliwo, marka, model, naped, rok, cena, kolor, skrzynia`). Aktywne w obu hookach: `wp_robots` (core) + `rank_math/frontend/robots`. Test: `/samochody/?marka=byd` → noindex,follow ✓; `/samochody/` (czysty) → index ✓.

## 0.32.7 — 2026-04-28

- **Homepage SEO refocus na „import samochodów z Chin".** User cel: pierwsza pozycja w Google we frazie „import samochodów z Chin". Zmiany:
  - H1 w `class-asiaauto-homepage.php::renderHero()`: „Samochody elektryczne i hybrydowe z Chin" → **„Import samochodów z Chin do Polski"**.
  - Hero subtitle: „Prima-Auto — agencja importu samochodów z Chin. EV, PHEV, hybrydy. Pełna obsługa: transport, cło, homologacja i rejestracja w cenie."
  - `rank_math_title` page_id=93629: **„Import samochodów z Chin do Polski | Prima-Auto"** (62 chars).
  - `rank_math_description`: focus kw na początku + USP + 1841 ofert + Rzeszów (155 chars).
  - `rank_math_focus_keyword`: **„import samochodów z Chin"**.
  - OG/Twitter title + description ustawione bezpośrednio przez `rank_math_facebook_*` i `rank_math_twitter_*` term meta.
- Strona główna jest WP page (id=93629) z content `[asiaauto_homepage]` shortcode — RM widzi tylko shortcode w editor, ale frontend ma pełną treść (RM analizuje frontend, nie source content).

## 0.32.6 — 2026-04-28

- **RankMath Pro migration — total przeniesienie SEO meta na RankMath.** User po instalacji RM Pro wykrył dublowanie 3 meta z `class-asiaauto-seo.php`: 2× description, 2× canonical, 2× CollectionPage JSON-LD na hubach marek/modeli. Strategia: total migration — RM zarządza title/description/canonical/og/twitter/CollectionPage, zostawiamy tylko nasz ItemList (lista listingów per hub — RM tego nie ma) + FAQPage (z `aa-hub-faq` w wiki_body) + BreadcrumbList na single listings (`class-asiaauto-single.php` nietknięte).
- **Zmiany w `class-asiaauto-seo.php`:**
  - `renderMeta()` — early return gdy `defined('RANK_MATH_VERSION')`. RM emituje canonical/description/og/twitter z `rank_math_*` term meta.
  - `renderSchema()` — gdy RM aktywny, emituje TYLKO ItemList (BreadcrumbList + CollectionPage przejęte przez RM).
  - `filterHomeTitle()` — early return gdy RM aktywny (RM kontroluje title z templates).
  - Backward compat: kod fallback gdy RM dezaktywowany (np. tymczasowo).
- **Bulk setup 284 hubów** (46 marek + 238 modeli z `asiaauto_wiki_body`):
  - `rank_math_focus_keyword` — make: nazwa marki, serie: „Marka Model" (parent-aware).
  - `rank_math_description` — z `asiaauto_seo_desc` (z REST hub-content endpoint, generowane przez n8n batch 0.31.5).
  - `rank_math_title` — template: make `{Marka} — Auto z Chin | Prima-Auto`, serie `{Marka} {Model} — Import z Chin | Prima-Auto`.
- **Thin tax noindex fix.** Nasz `wp_robots` filter był przykryty przez RM. Dodany `rank_math/frontend/robots` filter w `class-asiaauto-seo.php` — wymusza `noindex, follow` na taxonomy `transmission`, `drive`, `exterior-color`, `interior-color`, `condition`. Test: `/skrzynia-biegow/cvt/`, `/naped/awd/`, `/kolor-nadwozia/red/` → noindex ✓; `/paliwo/electric/`, `/samochody/byd/` → index (zostawione, wartościowe).
- **Sitemap regenerowany** przez `wp rankmath sitemap generate`. RM sitemap zawiera: make, serie (×2 plików), listings (×13), pages, local. Brak thin tax (RM domyślnie wyklucza taxonomy z 0 wpisami i niewartościowe).

**Pending (user-side):**
- W RankMath admin UI: Status & Tools → Database Tools → „Re-analyze SEO Score" — bulk obliczy score dla 284 hubów (auto przez admin, niedostępne via WP-CLI).
- Po score: review najgorszych w `Listings → Marki/Modele → Edit` (RM panel z prawej).
- Brakujące huby (4 marki + 70 modeli z `tmp/missing-hubs-2026-04-28.md`): n8n PAUZOWANE, najpierw dobry score na obecnych, potem dodawanie nowych.

## 0.32.5 — 2026-04-28

- **`missing-images` chunked apply + scope fix.** Bug: user dostawał alert „Apply błąd: Invalid JSON" + 2 listingi „nie do ruszenia". Trzy przyczyny:
  1. **Scope rozjazd:** check class scanował `post_status IN ('publish','draft')`, ale skrypt `diag/fix-missing-images.php` filtrował tylko `publish`. Stuck drafts (Xiaomi SU7 #242486, AITO M9 #246353 — oba ghost-offers 404) były znajdowane ale nigdy naprawiane.
  2. **Stdout w response:** `AsiaAuto_API::getOffer()` przy 404 wypisywał warning na stdout (poza zasięgiem `WP_CLI` guard), psuło JSON gdy AJAX response.
  3. **Proxy timeout:** apply na 18+ listingach ~3-4 min > LiteSpeed proxy timeout (~60-90s), klient dostawał truncated response.
- **Fixy:**
  - `diag/fix-missing-images.php`: scope `IN ('publish','draft')`. Plus `$max_items` 3rd arg (chunk size).
  - `class-check-missing-images.php` `applyFix`: `ob_start/ob_get_clean` wokół invocation (output do logger jako warning, JSON czysty); chunk_size=8 per request; zwraca `has_more` + `remaining`.
  - `class-check-base.php` `validateApplyToken`: usunięta `delete_transient` — token TTL-based 300s, reuse w obrębie chunked apply (bez tego każdy chunk wymagałby nowego preview).
  - `assets/admin-diag.js` `previewAndApply`: pętla while max 50 chunks, per chunk update modal z progress („Chunk 3: naprawiono 8 (łącznie 24). Pozostało: 6"), re-scan rowka po wszystkim.

## 0.32.4 — 2026-04-28

- **`missing-images` apply timeout fix.** Domyślny `set_time_limit(25)` w abstract base był za krótki dla `fix-missing-images.php` (iteruje API getOffer + downloadAndStore per listing, ~1-3s/listing × 30+ = >25s). Skutek: skrypt był **przerywany w środku** — naprawiał N listingów, AJAX wracał z `applied=0`, token był single-use'd przed timeoutem. User widział „Naprawiono: 0" ale bazowy count realnie spadał (32→23).
- Override `set_time_limit(300)` w `applyFix()` tylko dla missing-images.
- Logger zapisuje teraz `apply-start` (przed) i `apply-end` (po) — diagnoza timeoutów.
- JS: button „Wykonaj" disabled + tekst „Wykonywanie… (do 5 min)" po kliku, anti-double-click + jasny feedback że apply trwa.

## 0.32.3 — 2026-04-28

- **`chinese-chars` check — split na 3 grupy + edytor tłumaczeń.** Wcześniejsze „370 miejsc z CN" było bezużytecznym agregatem 3 fundamentalnie różnych problemów. Teraz w UI rozdzielone:
  - **Tytuły z CN (117):** post_title z nieprzetłumaczonymi fragmentami. Fix: `asiaauto_diag_chinese_v23(true)` — re-translate przez `translations-models.php` + `translations-complectations.php`.
  - **Aktywne termy z CN (1):** np. `银河A7 EM` parent=0, count=4. Wymagają ręcznej decyzji (rename + migracja listingów na canonical EN). NIE batchujemy — pomijane w apply z error msg.
  - **Orphany count=0 (252):** bagaż migracji v6.1, niewidoczne na froncie. Fix: bulk `wp_delete_term` (kosmetyka bazy).
- **Edytor tłumaczeń inline w modalu.** Sekcja „Niezamapowane fragmenty CN" pokazuje 84 unikalnych fragmentów (`高能`, `征服者`, `真香`, `劲擎`, `头等舱`, …) z formularzem `[CN] [EN input] [select model/complectation] [Dodaj]`. Klik wywołuje nowy AJAX `asiaauto_diag_add_translation` → atomic append do odpowiedniego `translations-*.php` z `.bak-YYYY-MM-DD-HHMMSS` + parse-verify + rollback. Po dodaniu wpisu można re-run `chinese-chars` apply żeby title-fix był pełniejszy.
- Issue meta `unmapped_fragments` array per title issue — pozwala UI pokazać które tytuły wymagają wpisów w mapie.

## 0.32.2 — 2026-04-28

- **Bugfix `make/serie-without-wiki` — meta_key prefix.** Checki używały `wiki_body` jako klucz term_meta zamiast `asiaauto_wiki_body` (faktyczny klucz zapisywany przez REST endpoint `hub-content/{tax}/{id}` z 0.31.5). Skutek: WSZYSTKIE aktywne termy raportowane jako bez wiki, mimo że BYD/Chery/Voyah itd. mają 6000+ znaków opisów. Real counts po fixie: make 50→4 (Changan Qiyuan, Dongfeng Fengshen, GAC Aion Hyper, Wuling), serie 303→66.

## 0.32.1 — 2026-04-28

- **Diag panel — uzupełnienia v1.1 (operacyjne fixy z 1. dnia)** — wszystkie 10 checków mają teraz fix actions, mapując workflow „dojdzie nowy model → popraw mapping → dodaj hub → wygeneruj opis":
  - **Tool 1 (mapping):** `listings-without-mapping` → fix per-item (form `make_eu`/`serie_eu` → atomic write do `data/brand-mapping-v6.1.php` z `.bak`). Future-facing — meta keys w listingach jeszcze niewypełniane.
  - **Tool 2 (hub structure):** Dwa NOWE checki:
    - `serie-broken-parent` — termy serie z `parent=0` ale `count > 0`. Heurystyka: `guessMakeFromListings()` JOIN przez term_relationships → `wp_update_term($id, ['parent' => $make_id])`. Live: 65 termów (artefakty migracji v6.1).
    - `duplicate-serie-terms` — grupuje listingi po prefiksie 3 słów post_title; gdy >1 term serie pod tym prefiksem → grupa. Fix: `wp_set_object_terms` na canonical (priorytet: parent>0 + post_count) + `wp_delete_term` reszty + `flush_rewrite_rules`. Live: 37 grup (m.in. Zeekr 9X #4824 vs #6532). UWAGA: per-item review wymagany, sub-warianty (EV/DM-I) mogą być false-positive.
  - **Tool 3 (description):** `make/serie-without-wiki` (już dodane w 0.32.0 v1.0.1) wysyłają webhook do `https://witold140-20140.wykr.es/webhook/primaauto-{make,serie}-desc` z `usleep(300000)` throttle.
- **`make/serie-without-wiki` — `hide_empty=true`** (zamiast false). Liczą tylko aktywne marki/modele z listingami. Counts: 296→50 (make), 2460→303 (serie) — sygnał operacyjny zamiast szumu.
- **`missing-images` — `getFixMode='confirm'`** (było `auto`) + dry-run probe HEAD na próbie ≤50 listingów w `previewFix()`. Modal pokazuje split: `~X dostanie zdjęcia, ~Y do KOSZA (ghost-offer 404)`.

Pełna trasa zmian: `docs/superpowers/specs/2026-04-28-diagnostyka-admin-panel-design.md` + `docs/superpowers/plans/2026-04-28-diagnostyka-admin-panel.md`.

## 0.32.0 — 2026-04-28

- **Diagnostyka admin panel** — nowe submenu `Listings → Diagnostyka`. 8 checków integralność + SEO coverage. Trzywarstwowy dostęp: UI / AJAX / WP-CLI (`wp asiaauto diag …`). Pluggable rejestr — dodanie checku = 1 plik + 1 linia. Patrz `docs/superpowers/specs/2026-04-28-diagnostyka-admin-panel-design.md`.
- Refaktor 5 skryptów `diag/*.php` na eksportowane funkcje — backward-compat z `wp eval-file` zachowana.

| Data | Wersja | Źródło | Uwagi |
|------|--------|--------|-------|
| 2026-04-24 | 0.31.12 | prod primaauto.com.pl | **Hub content pipeline fix + Galaxy cleanup + batch brakujących hubów.** (a) **Galaxy post-migracja cleanup** — `diag/fix-galaxy-migration.php`: merge 3 duplikatów serii Galaxy pod Geely (A7 PHEV 6079→6517 keep hub content, E5 3667→3397, Starship 6 6078→6516), migracja 6 listingów spod `make=galaxy` (#3394, count=6) na `make=geely` (#3626) + fix CJK w post_title `Galaxy 银河A7 EM → Galaxy A7 EM`, usunięcie orphan `Galaxy M9` #3402. Term make=galaxy zostaje z count=0 (redirect 301 pre-cutover realizuje `AsiaAuto_Redirects::redirectV61Brands`, niezależny od istnienia termu). (b) **n8n prompt caching** — `tmp/generate-n8n-workflows.py`: `system: string` zastąpiony `system: [{type:'text', text:..., cache_control:{type:'ephemeral'}}]`. Per execution 26015 (GAC make) cache_write=2233, exec 26016 (GAC Trumpchi) cache_read=2233 (90% rabat na input). Batch 13 hubów dzisiaj: $0.85 = €0.78 (bez cache byłoby €0.85, saving 8.5% — mniej niż modelowy 15%, bo output 85% kosztu nie cache'uje się). (c) **CRITICAL FIX — workflow routing term_id**: n8n node `Resolve Term ID` używał `/wp/v2/serie?slug=X` bez filtra parent → dla slugów kolidujących między markami (M8 @ GAC Trumpchi + AITO, 07 @ WEY + Avatr, H5 @ Haval + Hongqi, H6 @ Haval + Hongqi, ET5 @ Exeed + NIO, N7 @ Denza + Nissan, plus duplikatów Galaxy-like: Atlas Pro, T2 C-DM, Tiggo 9 vs "Tiggo 9 (Tiggo 8L)") zwracał pierwszy term_id globalnie (zwykle mniejszy) zamiast tego pod zamawianą marką. 9 z 10 wczorajszych zapisów serie trafiło do złych termów. Fix: (1) `class-asiaauto-rest-hub.php::factsForMake/factsForSerie` zwraca pole `term_id` (parent-aware, używa `getSerieTerm($slug, $make)` które filtruje po parent). (2) Workflow n8n: node `Resolve Term ID` WYCIĘTY, URL w `Save to WP` używa bezpośrednio `{{ $('Fetch Facts').first().json.term_id }}`. 2 nody mniej (10 zamiast 11), lżejszy workflow, zero race na resolver. (d) **Rescue skrypt** `diag/fix-batch-hub-routing.php`: move hub_content z 6 błędnych term_id na właściwe (M8/07/H5/H6/ET5/N7) + merge 3 same-brand duplicates (Atlas Pro 3632→3646, T2 C-DM 6510→6518, "Tiggo 9 (Tiggo 8L)" 3586→3582) z przeniesieniem 7 listingów i usunięciem 3 termów. Smoke test: `/samochody/aito/m8/`, `/samochody/avatr/07/`, `/samochody/hongqi/{h5,h6}/`, `/samochody/nio/et5/`, `/samochody/nissan/n7/` — wszystkie 200 z właściwym hub contentem. (e) **Batch 13 brakujących hubów** (3 make GAC/GAC Trumpchi/Wuling + 10 serie): wszystkie zakończone HTTP 200, zero lint_warnings. Pozostało ~35 serie orphan (parent=NULL) — osobny problem (importer-time bug z parametru v6.1 keys; patrz Pending). **Pending (osobna sesja):** systemowy fix importera `class-asiaauto-importer.php:87-110` (translateMark+translateModel PRZED AsiaAuto_Mapping::getEuForCn, fallback partial z parent=mark_term_id, guard CJK w nowych termach, wspólny advisory lock taxonomy writes). Bez tego fix, przyszłe synchronizacje będą tworzyć nowe orphan termy przy każdym modelu brakującym w mapping v6.1. |
| 2026-04-24 | — | prod primaauto.com.pl | **CJK cleanup: merge 3 orphan serie termów + uzupełnienie mapping v6.1.** User zgłosił chińskie znaki w nazwach modeli. Znaleziono 3 aktywne orphan termy serie z parent=0 i CJK w name: `日产N6` #6117 (7 listingów Nissan), `风云X3 PLUS` #6235 (1 listing Chery Fulwin), `奕派008` #6535 (1 listing Dongfeng). Pod właściwymi markami istniały już prawidłowe EN termy hierarchiczne (#6514 N6 / #6519 X3 PLUS / #6258 eπ008) — orphany powstały bo `AsiaAuto_Mapping::getEuForCn()` nie trafiał w klucze CN+CN z API Dongchedi (mapping miał tylko warianty EN+EN: `Nissan|Nissan N6`, `Chery Fengyun|Fengyun X3 PLUS`, `Dongfeng Yipai|eπ008`). Fix (a) `data/brand-mapping-v6.1.php`: 3 nowe klucze CN+CN (`Nissan|日产N6`, `Chery Fulwin|风云X3 PLUS`, `Dongfeng Yipai|奕派008`) wskazujące na te same mark_eu/serie_eu/title_eu co istniejące EN warianty — prewencja powtórzenia orphan-ów przy następnym syncu. (b) `data/translations-complectations.php`: `智领 => 'Smart Leader'` (listing Chery Fulwin X3 PLUS miał `智领` w komplektacji). (c) Nowy `diag/merge-orphan-cjk-serie.php` — hardcoded mapa 3 par (orphan→target), per każda para: remove object terms z orphan, set na target (append=true), update post_title (str_replace CJK→EN + `$translator->translateComplectation`), clean_post_cache, wp_delete_term(orphan), wp_update_term_count_now(target). Guards: orphan musi mieć parent=0, target musi mieć parent>0 (ABORT jeśli nie). Dry-run + APPLY=1. Wynik APPLY: 3 orphan termy usunięte, 9 listingów przeniesionych (7+1+1), 9 tytułów naprawionych. Smoke test: `/samochody/nissan/n6/`, `/samochody/chery-fulwin/x3-plus/`, `/samochody/dongfeng/e-008/` — wszystkie 200 OK z properly scoped listings. Pozostało 0 aktywnych CJK termów (38 orphanów w `make` count=0 to osobny projekt cleanup). Brak bump wersji pluginu — zmiany data-only, kod importera niezmieniony. |
| 2026-04-24 | 0.31.11 | prod primaauto.com.pl | **Breadcrumb single-listing — model klikalny + tytuł listingu jako current (cofnięcie decyzji v0.31.10).** User zgłosił że w breadcrumbie single ogłoszenia nie da się kliknąć w model żeby wejść na jego hub. v0.31.10 świadomie skróciła breadcrumb do 3-poziom (ostatni = serie nieklikalna) dla uniknięcia duplikacji z H1 w sidebarze. Decyzja wygrywa UX: hub modelu ma teraz własny wiki_body + specyfikację, link z breadcrumbu single listing prowadzi do głębszej nawigacji (katalog → hub marki → hub modelu). Fix w `class-asiaauto-shortcodes.php::renderBreadcrumb()` linie 265-280: (a) `$serie->url` zmienione z `null` na `get_term_link($serie)` z guardem `is_wp_error`. (b) Dodany 4-ty element breadcrumbu `get_the_title($post_id)` z `url=null` jako ostatni (current span). Wynik: `Samochody › Marka › Model(link) › Tytuł(current)`. BreadcrumbList JSON-LD w `class-asiaauto-single.php::renderMeta()` już był 4-poziom od v0.31.3 (nietknięty), teraz wizualny DOM znowu zgadza się ze schema. Smoke test na `/oferta/wuling-wuling-yangguang-2025-265775/` — 4 poziomy, Wuling Yangguang linkuje do `/samochody/wuling/wuling-yangguang/`. |
| 2026-04-24 | 0.31.10 | **Single listing + wizard refactor po cutover na standalone theme primaauto2026.** (a) **USP strip (czerwona sekcja 3 kolumny)** — nowa metoda `AsiaAuto_Single::uspStrip()` wywoływana w `render()` między `keySpecs` a `description`. 3 kolumny: „W cenie" (6 pozycji z doprecyzowaniami: „Sterowanie głosowe po polsku (dla wybranych modeli, np. BYD)", „Dwa komplety filtrów (oleju, powietrza, kabinowy)", „Ładowarka 7 kW EU (PHEV / EREV / elektryki)" itd.), „Dlaczego my" (5: Pełna obsługa importu, Odbiór w Rzeszowie, Transparentna cena, 20 lat doświadczenia, Umowa agencyjna), „Informacje" (4 klikalne: Proces zamawiania / Gwarancja / **Homologacja i rejestracja** / Język — Finansowanie i Regulamin wyrzucone, druga wersja Elementor template 101874). Ikonki inline SVG (bez Font Awesome). CSS `.aa-usp-strip` z tłem `var(--accent)` w `asiaauto-single.css`, czcionki 12-13px. (b) **Pogrupowane tech specs + wyposażenie** — `$this->techSpecs($d)` (spłaszcza grupy w jedną tabelę) zastąpione przez `do_shortcode('[asiaauto_tech_specs]')` (6 sekcji: Podstawowe/Silnik/Elektryczny/Skrzynia/Zawieszenie/Zużycie, 8 wierszy rozwinięte + „Więcej" per sekcja). Plus dodane `do_shortcode('[asiaauto_equipment]')` pod tech-specs (wyposażenie checklistami). (c) **Przyciski „Zamów" prowadzą do wizarda** — `cta()` (desktop sidebar), `mobileCta()` (mobile sticky), `renderCTA()` shortcode `[asiaauto_cta]` — wszystkie 3 zmienione z `#zarezerwuj` (placeholder JS alert) na `/zamow/?listing_id=X`. Przycisk „Zarezerwuj" → „Zamów" tekstowo wszędzie. (d) **Breadcrumb 3-poziomowy** — `renderBreadcrumb()` w `class-asiaauto-shortcodes.php` kończył na pełnym tytule listingu → duplikacja z H1 w sidebarze. Teraz ostatni element = nazwa Serie (nieklikalna): `Samochody › Marka › Model`. BreadcrumbList JSON-LD w `renderMeta()` zostaje 4-poziomowy (dla Google). (e) **Sidebar `aa-info` z klikalnymi linkami** — 4 pozycje (Proces/Gwarancja/Homologacja/Język) były `<li>` bez `<a>`. Teraz `<a href=/informacje/.../>` z CSS `.aa-info__list li a` (kropkowany underline, hover akcent). (f) **Breadcrumb + Wyjątki layoutu single** — `[aa_breadcrumb]` dodany do `render()` pod `.aa-single__back`. (g) **Wizard `/zamow/` — zastąpienie `[elementor-template id="174645"]`** w `class-asiaauto-order-wizard.php:440`. Shortcode nie renderował się (Elementor deaktywowany), pokazywał literal string. Natywny HTML: heading „Informacje" + 6 linków (/informacje/*, 2 bugowe slug-i poprawione: Finansowanie/Regulamin wskazywały na `/jezyk-obslugi-pojazdu/`) + 2 buttony (Zadzwoń via `[aa_phone]`, WhatsApp via `[aa_whatsapp]` — zamiast hardcoded starego `+48 783 807 381`). CSS `.aa-wiz__info*` w `asiaauto-order-wizard.css`. |
| 2026-04-23 | 0.31.8 | prod primaauto.com.pl | **Blok B Core Web Vitals — render-blocking + LCP optymalizacja (skromna wersja, po regresji wizualnej cofnięte 3 z 4 Elementor experiments).** Baseline mobile (PSI v5, post-Plan A SEO): home 70/LCP 6,0 s, hub-make-byd 79/3,5 s, hub-serie-byd-han-dm-i 83/3,7 s, listing-jetour 79/3,8 s, outlier xiaomi/su7 83/3,0 s. Render-blocking insight 2,5 s na home. LCP element home = baner cookies Complianza (`#cmplz-message-1-optin`) — TTFB 922 ms + element render delay 1445 ms. (a) **Elementor experiments testowane, 3 z 4 cofnięte po wykryciu regresji wizualnej (padding menu + horizontal scroll na mobile na hubach marek/serii i single listing).** Aktualnie aktywne: tylko `elementor_font_display=swap` (eliminuje FOIT na Inter, BEZ wpływu na layout). Cofnięte do `default`: `elementor_experiment-e_optimized_markup` (zmiana struktury DOM Elementora rozjeżdżała stare CSS theme), `elementor_experiment-e_font_icon_svg`, `elementor_load_fa4_shim` (oba pakiet Font Awesome — bezpieczniej zostawić bez zmian). Backup wartości startowych: `tmp/elementor-options-backup-2026-04-23.txt`. (b) **Resource hints w `class-asiaauto-seo.php`** — nowy hook `wp_head` priority 0 `renderResourceHints()`: `<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>` + `<link rel="dns-prefetch" href="https://fonts.googleapis.com">`. Skraca discovery font Inter o ~150-300 ms. (c) **LCP hero `fetchpriority="high"` + `decoding="async"` w 3 widokach** — `class-asiaauto-homepage.php` linia 270 (grid najnowszych ofert na home, pierwszy `<img>` z iteratora `$i === 0`), `class-asiaauto-brand-hub.php` linia 290 (grid hubów marek/serii, iterator `$aa_idx === 0`), `class-asiaauto-shortcodes.php` linia 374 (`renderGallery` main image w single-listing). Reszta `<img>` w gridach: `loading="lazy" decoding="async"`. (d) **Nowa klasa `class-asiaauto-perf.php`** (~70 linii) — wpięta przez `require_once` w `asiaauto-sync.php` po `class-asiaauto-seo.php`, self-instantiating pattern jak SEO. Konstruktor wcześnie returnuje na `is_admin()` (perf hooki tylko na frontendzie). Dwa hooki: (1) `wp_enqueue_scripts` priority 100 `dequeueUnused()` — na hubach taxonomy `make`/`serie`, archive `listings`, single `listings` (warunek `isPluginRenderedPage()` — strony renderowane szablonami PHP / shortcodami `[asiaauto_*]`, nigdy Gutenberg blocks) `wp_dequeue_style` dla `wp-block-library` + `wp-block-library-theme`. **WAŻNE:** pierwotnie wycinaliśmy też `global-styles` + `classic-theme-styles`, ale to powodowało regresję wizualną — Hello Elementor i child theme `asiaauto` polegają na zmiennych CSS `--wp--preset--*` (padding, spacing, color tokens) emitowanych przez `global-styles`. Komentarz w kodzie z ostrzeżeniem żeby nie ruszać. Wycina 14,6 KiB unused CSS na hubach/listing. (2) `wp_default_scripts` `removeJqueryMigrate()` — usuwa `jquery-migrate` z `deps` zarejestrowanej `jquery` (frontend-only, admin nietknięty żeby nie psuć starszych wtyczek admina). Wycina 5 KB JS + 363 ms render-blocking. Smoke test: na hubie BYD, hubie serie, single listing — `block-library` zniknął z HTML, `jquery-migrate` zniknął z `<script>` na wszystkich stronach (frontend), `jquery-core-js` ładuje się standalone. (e) **`elementor_css_print_method=internal` testowane i COFNIĘTE** — internal inlinuje cały CSS Elementora w `<head>` (eliminuje 5 osobnych blocking requests `post-XXX.css` ~700 ms parallel), ale na home (Frontpage Elementor template) zwiększa HTML body ze wszystkim CSS frontpage → TBT 190→320 ms i PERF 74→69. Po reverse to `external` home wraca, ale per-post CSS pliki znów blokują (akceptowalny trade-off). Backup: `class-asiaauto-{seo,homepage,brand-hub,shortcodes}.php.bak-2026-04-23-cwv` + `asiaauto-sync.php.bak-2026-04-23-cwv` + `taxonomy-make.php.bak-2026-04-23-cwv` (ten ostatni niewykorzystany — myliłem ścieżki, faktyczny grid jest w `class-asiaauto-brand-hub.php`). **Wynik finalny (PSI mobile, po reverse experiments do baseline + naprawie dequeue + włączeniu tylko font_display=swap):** home 69/6,1 s LCP, hub-make-byd 83/3,5 s LCP (+4 PERF), hub-serie-byd 80/3,6 s LCP, listing-jetour 82/3,8 s LCP (+3 PERF), outlier xiaomi/su7 84/3,5 s LCP. **Skromny zysk +3-4 PERF na hubach/listing** (gros wygranej z 4 experimentami był w fazie z `e_optimized_markup=active` + `e_font_icon_svg=active` które zostały cofnięte z powodu regresji wizualnej — vide notatka wyżej). LCP huby spadł z 3,5-3,7 s do 3,5-3,6 s — niewielka delta vs noisy PSI. **Pełny potencjał (z 4 experimentami): hub-make 88/3,0 s, hub-serie 86/3,0 s, listing 85/3,5 s** — osiągalny po refactorze theme (header/footer w czystym kodzie zamiast Elementor templates). FCP wszędzie 3,0 s (wąskie gardło: jQuery 30 KB 600-740 ms blocking, niemożliwe do wycięcia). RB insight wciąż 2,1-2,3 s — to suma wastedMs, faktyczny serial chain krótszy bo wiele plików ładuje się równolegle. **Pending Blok C (osobne projekty):** (i) **Header/footer rewrite na czysty kod child theme** (4-8h, Twoja sugestia po regresji wizualnej) — zostawia Elementor tylko dla single-listing template; wycina ~70% Elementor render-blocking CSS na hubach (większość z 19 plików ładowana dla header/footer); LCP huby pewnie spadnie poniżej 2,5 s = green. Po tym można bezpiecznie ponownie włączyć `e_optimized_markup` i `e_font_icon_svg` bez regresji (bo header/footer już nie zależą od Elementora). (ii) **Fix hubów make/serie z dziś** — padding na mobile za duży na obu, na hubach marek listingi wyświetlają się "źle" (różnie niż na hubach modeli mimo że oba używają `[asiaauto_hub_listings n=5]` → `renderListingsCompact` — pewnie wiki_body BYD od n8n zawiera tabelkę/element overflow-x na mobile, do zbadania ze screenshotem). 15-30 min. (iii) Cookie banner Complianza jako LCP element home (4,9-6,1 s) — UX redesign na mniejszy bottom-bar; opcja `cmplz_cookiebanners.use_custom_cookie_css=yes` + custom_css redukujący wysokość banera. Home ≠ landing SEO (huby są), niski priorytet. (iv) Critical CSS inline (above-the-fold extract, defer reszty). (v) Self-host Inter font (eliminacja preconnect → 0 ms cross-origin penalty). (vi) Cron PSI weekly + GSC 7d delta monitoring. |
| 2026-04-23 | 0.31.7 | prod primaauto.com.pl | **SEO meta/schema dla home + huby marek/serii + sitemap filter — Plan A sesji SEO.** Audyt baseline (2026-04-23 21:15): sitemap submitted do GSC (primaauto.com.pl zweryfikowany siteOwner, 1841 listings + 45 marek + 275 serii + 27 URL-i w 5 thin taksonomiach), 12/12 hubów bez canonical/meta desc/og (listingi 3/3 OK), PSI mobile LCP 3.5-6.1s (outliery 51-69 perf score = SU7 + MG MG4 bez wiki_body cachowanych), SEO score wszędzie 92/100. Raport w `tmp/seo-audyt-2026-04-23.md`. Fix: nowa klasa `AsiaAuto_SEO` (`includes/class-asiaauto-seo.php`, 381 linii) — hooki `wp_head` priority 1 (meta) + 2 (schema): (a) Homepage — canonical + desc + og:website/image + twitter:card + og:locale=pl_PL; title override przez `document_title_parts` filter dedup blogname vs blogdescription (było: `"Prima-Auto - Import Samochodów z Chin – Samochody z Chin — import, sprzedaż, gwarancja"` → jest: `"Prima-Auto — Import samochodów z Chin do Polski"`). Schema WebSite/AutoDealer bez zmian (nadal w `class-asiaauto-homepage.php`). (b) Hub marki `/samochody/<make>/` — canonical + desc z `asiaauto_wiki_body` term_meta trim 158 chars (np. BYD: „BYD (Build Your Dreams) to chiński koncern założony w 1995 roku…") → fallback template `"Importuj {marka} prosto z Chin do Polski — {count} ofert…"`; og:image = pierwsze zdjęcie najnowszego listingu → fallback `get_theme_mod('custom_logo')`; BreadcrumbList 3-poziom (Home→Samochody→Marka) + CollectionPage + ItemList top 10 najnowszych listings. (c) Hub serii `/samochody/<make>/<serie>/` — jw. z BreadcrumbList 4-poziom, `display_name` term meta → fallback term->name. (d) Filter `wp_sitemaps_taxonomies` wyklucza 5 thin taksonomii (`transmission, drive, exterior-color, interior-color, condition`) z `wp-sitemap.xml` — po deploy sitemap zawiera tylko `make, serie, body, fuel, ca-year` + posts + page. (e) Filter `wp_robots` dokleja `noindex, follow, max-image-preview:large` na te 5 thin taxonomii — po deploy `/skrzynia-biegow/cvt/`, `/naped/awd/`, `/kolor-nadwozia/*`, `/kolor-wnetrza/*`, `/stan/*` mają meta robots noindex (Google wyczyści z indeksu). Single listings (`/oferta/*`) bez zmian — pełen SEO (Car + BreadcrumbList + og:product) nadal w `class-asiaauto-single.php`. FAQPage schema z `class-asiaauto-brand-hub.php` nadal emitowany jako osobny JSON-LD (hub BYD ma teraz 4 JSON-LD bloki: BreadcrumbList + CollectionPage + ItemList + FAQPage). Deploy: backup `asiaauto-sync.php.bak-2026-04-23-seo` (wcześniejsze 0.31.6), copy `tmp/class-asiaauto-seo-draft.php` → `includes/class-asiaauto-seo.php`, require_once w asiaauto-sync.php po `class-asiaauto-contact.php`, bump `ASIAAUTO_VERSION 0.31.6 → 0.31.7`, `php -l` obu OK, `wp cache flush`. Smoke test 7/7: home title dedup ✓, canonical/desc/og home ✓, hub BYD wiki_body desc + og:image z pierwszego listingu Song L EV ✓, hub SU7 z zera → 3 schema + fallback desc „51 ofert…" ✓, thin tax `noindex, follow` ✓, sitemap index bez 5 thin taksonomii ✓, listing Denza bez zmian (Car+BreadcrumbList+og:product) ✓. PSI re-check mobile: home perf 75→70/SEO **92→100**, hub BYD perf 76→84/SEO **92→100**, hub SU7 perf 51→84/SEO **92→100** (SU7 skok perf = cache warm po smoke; variance, nie optymalizacja CWV). LCP mobile 3.5s unchanged — CWV to osobny Blok B (fetchpriority/preload LCP image, Elementor CSS combine, GTM/Complianz defer). GSC `wp-sitemap.xml` resubmit via API 2026-04-23 19:18 UTC (errors:0, warnings:0). Pending Blok C (po batch wiki_body dojedzie): GSC URL Inspection request-indexing dla top 10-20 hubów, DataForSEO SERP baseline (pozycje brand/model vs west-motors.pl), cron PSI weekly + GSC 7d delta monitoring. |
| 2026-04-23 | 0.31.6 | prod primaauto.com.pl | **Importer używa mapowania v6.1 również dla `post_title` i slug.** User zgłosił „GAC Trumpchi Trumpchi GS4" x2 (dwa osobne inner_id 23417343/23567330 z różnych miast — Huizhou/Jieyang — to dwa fizyczne auta, nie race condition; ale tytuł/slug mimo v6.1 był surowy CN). Przyczyna: w `importListing()` (linie 87-102) `$title` i `$model_for_slug` były budowane z `$data['mark']` / `$data['model']` przez translator, **mapping v6.1 odpalał się dopiero w `setTaxonomies()`** — taksonomie jechały EU, ale `post_title` i `post_name` zostawały CN. Batch v6.1 z 0.31.2 naprawił 930 istniejących, ale każdy nowy import od tamtej pory generował znów raw CN (dzisiaj: 263366, 263590). Fix: dodana gałąź `AsiaAuto_Mapping::getEuForCn($markCN, $modelCN)` przed budową title; przy hicie używamy `mark_eu` + `serie_eu` (fallback na obecną logikę z translatorem dla niezmapowanych par). Cleanup istniejących (4 listingi): 263366/263590 GS4 → „GAC GS4 2024 ...", 241831/243443 → „GAC Hyptec HT 2024 ..." (post_title + post_name). Nie ruszane: 4× GWM Cannon — ich `title_eu` w v6.1 intencjonalnie zachowuje prefix „GWM Cannon Great Wall Pao" (chińskie znaki w komplektacji to osobny gap translatora, nie dotyczy mapy). 3× residuale nie obecne w v6.1 (249841 GAC Aion Hyper HL, 259561+262459 Dongfeng Yipai eπ007) — zostają do v6.2 zgodnie z ADR 0.31.2. |
| 2026-04-23 | 0.31.5 | prod primaauto.com.pl | **SEO content huby: 3 widoki + n8n content pipeline (Claude Sonnet 4.6).** (a) Strona `/marki/` — page_template `page-marki.php` w child theme, grid 29 marek z count (Top 8 hardcoded + reszta alfabetycznie), page ID 263572. (b) Archive `make` — `taxonomy-make.php` — hero, `{{LISTINGS_BAR}}` w wiki_body podmieniany na compact grid 5×, sekcja pigułek modeli (`aa-brand-card`, renderowana przez `AsiaAuto_Brand_Hub::renderModelPills()`), USP box („W cenie / Dlaczego Prima-Auto / Informacje"), FAQ. (c) Archive `serie` — `taxonomy-serie.php` wymuszany przez `template_include` filter (dwupoziomowy URL). Tabelka specyfikacji z 14 wierszami (wymiary, waga, zasięg WLTC+total, bateria, przyspieszenie, moc el., napęd/paliwo breakdown), lista wyposażenia standardowego z PL-labelami (22 cechy: kamera 360, keyless, ambient, masaż/wentylacja/podgrzewanie foteli, HUD, auto park itd.), sekcja porównawcza z EU, drugi listing bar „Zobacz też inne oferty [marka]", USP box, FAQ. (d) Nowa klasa `AsiaAuto_REST_Hub` (class-asiaauto-rest-hub.php, ~400 linii) — 5 GET endpointów: `facts-for-make/{slug}`, `facts-for-serie/{slug}?make=X`, `latest-by-make/{slug}?n=N`, `latest-by-serie/{slug}?n=N&make=X`, `aliases-for-make/{slug}`, `aliases-for-serie/{slug}?make=X` (wyciąga sub-brand aliasy z `data/brand-mapping-v6.1.php`: Fangchengbao, Yangwang, Denza). Plus POST `hub-content/{taxonomy}/{id}` z auth `X-AsiaAuto-Token` (shared secret `ASIAAUTO_HUB_TOKEN` w wp-config, kopia w `~/secrets/primaauto/hub-token.txt`) — zapisuje `asiaauto_wiki_body`, `asiaauto_faq_json`, `asiaauto_seo_desc` do term_meta. Fact block `factsForSerie` parsuje `_asiaauto_extra_prep` (JSON z ~200 chińskich kluczy per listing) → wyciąga specs (wymiary, bateria, zasięg, przyspieszenie, waga, moc) + `features_standard/optional` (zlicza % pokrycia) + `notable` (seat_material, sound_brand, autonomy_level) + taxonomies `fuel/drive/body/ca-year` breakdown. (e) `class-asiaauto-brand-hub.php` rozszerzone o `renderListingsCompact($make, $serie, $n, $cta_url, $cta_label, $heading)` — kopiuje markup `aa-home__car*` z homepage (badge „Nowe" dla <24h, specs `paliwo · rocznik · przebieg km`, CSS variables na wrapper `.aa-hub__latest-wrap`, grid 5 kolumn na desktop / slider mobile). Plus `renderModelPills($make_slug)` → markup `aa-brand-card`. Shortcode `[asiaauto_hub_listings]` teraz akceptuje atrybuty `n`, `cta_url`, `cta_label`, `heading`. `renderFaq` przestał renderować własny `<h2>Najczęściej zadawane pytania</h2>` — nagłówek decyduje template (zniesienie duplikatu H2). (f) 2 workflow n8n (aktywne na witold140-20140.wykr.es): `primaauto-make-desc` (ID `BqM9UJ2HnxGVAllb`) + `primaauto-serie-desc` (ID `dt4Q78mqwyuzc1Ta`). Pipeline: Webhook POST → 3× HTTP Request (fetch facts + latest + aliases) → Merge → Code (build prompt + anthropic_body inline) → HTTP POST Anthropic (model `claude-sonnet-4-5` = Sonnet 4.6, max_tokens 8000, system prompt ~4000 tokens z kontekstem Prima-Auto, USP „praktycznie nowe auta, nie klasyczne używane", realnym procesem 8-12 tyg, zakazem „+15-20k za import", FORBIDDEN phrases, wymogiem placeholder `{{LISTINGS_BAR}}`, zakazem sekcji FAQ/„Modele" w wiki_body, wymogiem tabelki 14+ wierszy dla serie, porównania z segmentem EU, linków wewnętrznych do filtrów `/samochody/?paliwo=erev&marka=X&naped=awd`) → Code Parse+Lint (regex wycina ewentualne sekcje FAQ i „Modele ... dostępne" z wiki_body, JSON safety: zakaz `"` wewnątrz wartości, lint FORBIDDEN phrases, warning gdy brak LISTINGS_BAR) → HTTP GET `/wp/v2/make?slug=X&_fields=id` → HTTP POST `/asiaauto/v1/hub-content/{tax}/{id}` → Respond. Workflow JSON generowane przez `tmp/generate-n8n-workflows.py` (pobiera klucze z `~/secrets/`, output gitignored bo inline keys). Czysty JSON z placeholderami zostanie commitowany do `workflows/` po przeniesieniu do n8n credentials. (g) Strona główna: linki marek w `AsiaAuto_Homepage::renderMakes()` zmienione z `$inv_url.'?marka='.slug` (filtered inventory) na `get_term_link($term)` (hub marki) — buduje autorytet hubów w indeksacji Google zamiast parametrycznych wariantów inventory. „Wszystkie marki →" → `/marki/`. Filtry body/fuel bez zmian (nie mają jeszcze dedykowanych hubów). (h) Batch generacji ruszył w tle: `tmp/batch-hub-parallel.sh` z xargs -P3 (3 concurrent webhooks), kolejka ~175 (45 marek + ~130 modeli count>0), szacowany czas ~60 min, koszt ~$25 Sonnet 4.6. Log: `tmp/batch-hub-parallel-*.log`. Pilot Voyah + Voyah FREE zweryfikowany przed batchem — 3 iteracje system promptu: v1 (pierwszy render), v2 (USP „jak nowe", ceny końcowe, realny proces, zakaz FAQ/„Modele" w wiki_body, JSON safety), v3 (tabelka 14 wierszy z specs, lista wyposażenia z PL-labelami z `features_standard`, aliasy sub-brand, internal linki). Pending: przeniesienie kluczy Anthropic + `X-AsiaAuto-Token` do n8n credentials (po weryfikacji batch), prompt caching (`cache_control: ephemeral`) dla oszczędności 2-3× przy kolejnych runach. |
| 2026-04-23 | 0.31.4 | prod primaauto.com.pl | **Plan A wdrożony — MySQL advisory lock zamiast transient w sync.** Nawrót dupli: 25 par (9 z 2026-04-22 17:04-17:11 + 16 z 2026-04-23 09:20-09:25). Root cause ten sam co w ADR 2026-04-22: sync trwa >10 min, transient TTL wygasa, równoległy cron drugiej instancji pali ten sam `change_id` i `findByInnerId` dla obu zwraca null. Fix w `class-asiaauto-sync.php`: transient `asiaauto_sync_lock_{source}` (get/set/delete) zastąpiony przez `GET_LOCK('asiaauto_sync_{source}', 0)` + `RELEASE_LOCK` w punktach wyjścia (normalne + error-return po getChangeId). Plusy: auto-release przy crashu PHP (sesja MySQL kończy się), brak TTL do wygaśnięcia. Cleanup: `wp post delete --force` na 25 MAX(post_id) — 0 rezerwacji, 0 zamówień na usuwanych. Backup przed dedupem: `~/backups/primaauto/2026-04-23-pre-dedup-posts-postmeta.sql.gz` (19 MB — tylko posts+postmeta, reszta bazy nietknięta). Legacy asiaauto.pl NIE zaktualizowany (odseparowany na 0.30.15-16, `DISABLE_WP_CRON=true` → race nie występuje; full sync pluginu to osobny projekt). |
| 2026-04-23 | 0.31.3 | prod primaauto.com.pl | **Fixy UX ogłoszenia + breadcrumbs + upload zdjęć.** (a) Fix bugu detekcji `fuel_type` w podglądzie importu ręcznego (`class-asiaauto-admin-manual-import.php`) — kolejność `$fuel_map` zmieniona tak, by `phev/mhev/erev/electric` były sprawdzane przed ogólną `hybryda`. Poprzednio `str_contains` łapał `hybryda` z nazwy „Hybryda plug-in (PHEV)" i ustawiał `hybrid` (akcyza 1.55%) zamiast `phev` (0%) → preview pokazywał cenę o ~2000 zł wyższą niż ogłoszenie (160k vs 162k). Dotyczyło wszystkich PHEV/MHEV/EREV. Importer/applyToListing bez zmian (czytał slug taxonomy bezpośrednio). (b) Data pierwszej rejestracji na froncie: shortcode `[asiaauto_key_specs]` kafelek „Rok" (ca-year) → „Rejestracja" MM/YYYY z `registration_date` (fallback: kafelek ukryty gdy meta brak). 1828/1840 listingów ma reg_date. Rok modelowy przeniesiony do shortcode `[asiaauto_tech_specs]` jako pierwszy wiersz sekcji „Dane podstawowe" (`buildTechSpecSections` rozszerzone o opcjonalny `$post_id`, `array_unshift` na sekcję `podstawowe`). Karta listingu w `/samochody/` (`AsiaAuto_Inventory::getSmartSpecs`): rok z reg_date z fallbackiem na ca-year. `class-asiaauto-single.php::gather()` + `keySpecs()` analogicznie (na wypadek `[asiaauto_single]`). (c) BreadcrumbList JSON-LD w `AsiaAuto_Single::renderMeta()` — 4 poziomy: Samochody › Marka › Model › Tytuł (każdy z `item` URL zgodnie z rekomendacją Google, mirror wizualnej wersji z shortcode). Shortcode `[aa_breadcrumb]`: root „Oferta"→„Samochody" (zgodność z URL `/samochody/`), ostatni element zmieniony z samej Serie na pełny tytuł ogłoszenia (nieklikalny current), Model klikalny (get_term_link dwupoziomowy). (d) Fix fatalu przy ręcznym uploadzie zdjęć (`AsiaAuto_Media::storeLocalUpload` linia 301): `buildAltText($listing,...)` → `buildAltText($post_id,...)`. `$listing` (array) był błędnie przekazywany tam, gdzie PHP 8 strict types wymagał `int` → TypeError, 0/N plików zapisanych, komunikat "Argument #1 (\$post_id) must be of type int, array given". Importer (downloadSingleImage, linia 176) był OK. Backupy: `class-asiaauto-admin-manual-import.php.bak-2026-04-23`, `class-asiaauto-single.php.bak-2026-04-23-regdate`, `class-asiaauto-shortcodes.php.bak-2026-04-23-regdate`, `class-asiaauto-inventory.php.bak-2026-04-23-regdate`, `class-asiaauto-media.php.bak-2026-04-23`. |
| 2026-04-23 | 0.31.2 | prod primaauto.com.pl | **Mapowanie marek/modeli v6.1 (CN→EU) — rebuild taksonomii + parent-aware URL handler + importer-time mapping.** Źródło: `tmp/mapowanie-marek-modeli-v6.1.csv` (264 wiersze) = xlsx klienta z 2026-04-23 + 16 decyzji quizu + #264 Exeed VX dopisany. (a) **Etap 1 — CSV v6.1** (90 zmian + 57 synchronizacji slug): Q1 BYD prefix dla Leopard, Q3 Denza B5/B8 w nawiasach, Q4 Sealion uniformity (Sea Lion/SeaLion→Sealion, ALE tylko w serie/title — model CN zostawiony bo pasuje do API Dongchedi), Q6 GAC Aion Hypec→GAC + Hyptec HT, Q10 Chery iCAR 03/V27 label prefix, Q14 WEY 7→07, Q15a Li Auto i6 scalenie #57+#188, Q15b MINI skip, Q16 Nissan N6 scalenie #83+#142. XPENG P7+ slug `p7-plus` (fix: sanitize_title zjadał `+`). (b) **Etap 2 — Backup** `~/backups/primaauto/2026-04-23-v6.1-taxonomy/terms-*.sql` (540KB, 4 tabele). (c) **Etap 3 — Rebuild taksonomii**: 8 nowych marek (term_id 6521-6528): BAIC, Beijing 212, Chery Fulwin, Exlantix, GAC, GWM, Luxeed, Nevo. 208 rename serie, 258 move_parent (Fangchengbao/Yangwang→BYD +28, Galaxy→Geely +62, iCAR→Chery +5, Trumpchi/Aion Hyper→GAC +55, Fengyun→Chery Fulwin +21, Maextro→Luxeed +18, Qiyuan→Nevo +7). 510 termmeta (`_serie_full_title` + `_serie_api_value`). 2 create serie (Hyptec HT term 6529 pod GAC — listingi przeniesione z starego term 5489 Hyper HT; Lynk & Co 900 term 6530). Migracja per-serie: 256 listingów przepisanych między markami przez term_relationships INSERT IGNORE + DELETE. Recount make counts. Residuals (Exeed 4, iCAR 6, Galaxy 5, Jetour Shanhai 3, Dongfeng Yipai 2 = 20 listingów niezmapowanych w v6.1, zostają pod starą marką do v6.2). Skrypty: `tmp/analyze-taxonomy.php` (dry-run raport), `tmp/apply-taxonomy.php` (APPLY), `tmp/migrate-make-per-serie.php` (APPLY). (d) **Etap 4 — Kod**: nowa `class-asiaauto-mapping.php` (singleton lookup), `data/brand-mapping-v6.1.php` (260 pozycji, klucz `markCN\|modelCN`). Importer `setTaxonomies()` przed `setTaxonomyAndMeta` wywołuje `AsiaAuto_Mapping::getEuForCn()` — nowe listingi z Dongchedi trafiają od razu pod EU-markę i EU-serie (np. `Galaxy\|Galaxy E5` → make=Geely, serie=Galaxy E5). `class-asiaauto-brand-hub.php`: **parent-aware** `getContextTerm()` (priorytet: dwupoziomowy URL `/samochody/<make>/<serie>/` zwraca serie-term filtrując przez parent=make, rozwiązuje kolizje slug typu `n7` Denza vs Nissan, `m8` AITO vs GAC Trumpchi, `07` Avatr vs WEY), nowe hooki `document_title_parts` + `pre_get_document_title` czerpiące `_serie_full_title` (np. `<title>BYD ATTO 3 (Yuan PLUS)</title>` zamiast samego `BYD`). `class-asiaauto-inventory.php`: nowa prywatna `getSerieTermByMakeParent()` + fix 3 miejsc (filterInventoryTitle, renderInventoryMeta, renderSeoBlock). Backupy: `class-asiaauto-brand-hub.php.bak-2026-04-23`, `class-asiaauto-inventory.php.bak-2026-04-23`. **Redirecty 301 — dodane wstecznie** (klient zmienił decyzję): `class-asiaauto-redirects.php` nowa metoda `redirectV61Brands()` + stała `V61_MAKE_REDIRECTS` (16 par: fangchengbao/yangwang→byd, galaxy→geely, chery-fengyun→chery-fulwin, gac-trumpchi/gac-aion-hyper→gac, icar→chery, maextro→luxeed, great-wall→gwm, changan-qiyuan→nevo, beijing-off-road→baic, 212→beijing-212, jetour-shanhai→jetour, dongfeng-fengxing/yipai→dongfeng, lotus-cars→lotus). Pattern: `^samochody/<old>/(.*)` → 301 `/samochody/<new>/$1`. **Batch update post_title**: 930 listingów zaktualizowanych (`tmp/update-listing-titles.php`) wg mapy v6.1 — parser 3-poziomowy (full prefix markCN+modelCN, modelCN self-contained, markCN multi-word z dedupe ostatniego słowa). Np. `Fangchengbao Leopard 5 2025...` → `BYD Leopard 5 (Denza B5) 2025...`, `Chery Fengyun T11 2026...` → `Chery Fulwin T11 2026...`, `BYD Haishi 07 EV...` → `BYD Sealion 7...`, `Geely Xingyue L...` → `Geely Monjaro...`. 18 listingów zostało z CN-prefix celowo (7× iCAR Super V23 niezmapowane w v6.1 + 11× GAC Trumpchi X gdzie title_eu w v6.1 zachowuje prefix „GAC Trumpchi"). Smoke test: 6 URL hubów 200 OK z poprawnymi title — `/samochody/byd/atto-3/`→`BYD ATTO 3`, `/gac/hyptec-ht/`→`GAC Hyptec HT`, `/byd/leopard-5/`→`BYD Leopard 5 (Denza B5)` (dual-name), `/exeed/vx/`→`Exeed VX` (nowy model #267), `/chery/icar-03/`→`Chery iCAR 03` (label rozróżnia od Tiggo 4/7/8/9 pod Chery). |
| 2026-04-22 | 0.31.1 | prod primaauto.com.pl | **Konwersje GA4 dla Google Ads: click_phone, click_whatsapp, generate_lead.** (a) GA4 property Prima-Auto.pl (534017542, stream G-F1NCC3D2HZ) — 3 key events utworzone przez Admin API: `click_phone`, `click_whatsapp`, `generate_lead`. (b) GTM container GTM-T4GTZ2JB (account 6351095501, container 250095450, workspace 5) — przez Tag Manager API v2 utworzone: 3 triggery Custom Event (`CE - click_phone/whatsapp/generate_lead`), 3 tagi GA4 Event (`GA4 - click_phone/whatsapp/generate_lead` używające zmiennej `{{GA4-ID}}`), 3 Data Layer Variables (`DLV - listing_id`, `DLV - vehicle_name`, `DLV - value`). Publikacja ręczna przez UI (scope `tagmanager.edit.containerversions` nie dodany do OAuth). (c) Nowy plik `assets/js/asiaauto-tracking.js` — delegated click listener dla `a[href^="tel:"]` → `dataLayer.push({event:'click_phone'})`, dla `a[href*="wa.me/"]` / `whatsapp://` / `api.whatsapp.com` → `dataLayer.push({event:'click_whatsapp'})`. (d) Enqueue globalny w `asiaauto-sync.php` hook `wp_enqueue_scripts` (każda strona frontu, cache-buster po `filemtime`). (e) `assets/js/asiaauto-order-wizard.js` w `handleStart()` po sukces `order/start` pushuje `generate_lead` z `currency:'PLN'`, `value` (z `C.init.price_pln`), `listing_id`, `vehicle_name` (z `C.init.listing.title`). Import do Google Ads conversion actions czeka na Basic access developer_tokena (obecnie `test`). |
| 2026-04-21 | 0.31.0 | prod primaauto.com.pl | **SEO: ALT rotacja + GTM dataLayer + huby /samochody/marka/model/.** Importer dedupe NIE ruszany — „Trumpchi Trumpchi" i sub-brandy (Galaxy→Geely) idą w osobnej warstwie `translations-complectations.php`. (a) ALT-y: nowa `AsiaAuto_Media::buildAltText()` — rotacja 10 szablonów po `$n % 10` (zdjęcie N, używany z Chin, import Prima Auto, rynek chiński, zamów z Chin, …). Argument `$listing` przekazany do `downloadSingleImage`. Mark/model używane z termów jak są. (b) `AsiaAuto_Single::filterTitle()` — rotacja 10 szablonów SEO title po `inner_id % 10` (używane, import, zamów, gotowy do rejestracji, z rynku chińskiego, na zamówienie, sprowadź, import prosto z Chin, kup w 2026). (c) `AsiaAuto_Single::renderMeta()` — `dataLayer.push` z eventem `view_item` (ecommerce.items + listing.{inner_id,mark,model,year,fuel,drive,body,city,cif_usd}) przed metami OG/schema. (d) Taksonomia `make` slug zmieniony: `marka` → `samochody`. Rewrite rule `^samochody/([^/]+)/([^/]+)/?$` → query `make+serie` (`registerHubRewrites`). `filterHubQuery` dokleja `tax_query AND` dla obu taksonomii. Filter `term_link` dla `serie` generuje `/samochody/<primary_make>/<slug>/`. (e) Term meta `_asiaauto_primary_make_slug` na termach serie — wypełnia importer przy każdym imporcie (`updateSerieprimaryMake`) + batch `diag/backfill-serie-primary-make.php` (252 termy zbackfillowane). (f) Term admin UI rozszerzone o `asiaauto_wiki_body` (HTML) i `asiaauto_faq_json` obok istniejącego `asiaauto_seo_desc`. (g) Nowa klasa `AsiaAuto_Brand_Hub` — shortcody `[asiaauto_hub_wiki]`, `[asiaauto_hub_faq]` (+ Schema.org FAQPage), `[asiaauto_hub_listings]` (deleguje do `[asiaauto_inventory]` z inject kontekstu archive do `$_GET[marka,model]`). (h) `AsiaAuto_Redirects::redirectLegacyTaxonomy()` 301: `/marka/*` → `/samochody/*`, `/model/*` → `/samochody/<primary_make>/*`. (i) Batch `diag/update-listing-alts.php` zaktualizował 22 034 istniejące zdjęcia (1697 listingów, 33 bez galerii). `wp rewrite flush` zrobiony. Filtry inventory (`?marka=byd,nio` GET params) nietknięte. **Fix post-deploy:** (1) w `filterTitle` dodane `unset($parts['site'])` — szablony same zawierają „Prima Auto", WP bez tego doklejał blog name `– Prima-Auto` dając podwójne branding w `<title>`. (2) Szablony 0, 7 i 9 poprawione — „używane"→„Używane", „sprowadź"→„Sprowadź", „kup"→„Zamów Online" (wielka litera na początku zdania, CTA brzmi lepiej niż „kup"). (3) `filterTitle` i `renderMeta` — baza przełączona z klejenia `{make} {serie}` na `get_the_title($pid)`. Importer w linii 93-97 robi dedupe prefixu marki przy zapisie `post_title`, więc nie ma duplikatu „Denza Denza D9 DM". Term `serie` tego nie ma (zawiera pełny „Denza D9 DM"). Dotyczy 10 szablonów SEO title, OG title, meta description, dataLayer `item_name`. (4) ALT również — `buildAltText($post_id, $n, $total)` zamiast `$listing` array, base z post_title. Szablon ALT 1 „używany" → „Używany". Batch diag re-run: 22 034 zdjęcia z nowym czystym ALT. |
| 2026-04-21 | 0.30.16 | prod primaauto.com.pl | **Cutover na docelową domenę + rebrand user-facing.** (a) Kopia 8.9GB plików asiaauto.pl→primaauto.com.pl (rsync wykluczający `mcp-test/`, backupy SQL, logi testowe). (b) DB wspólna wp7j_ (siteurl/home zmienione search-replace: 106493 URL replacements) — asiaauto.pl od teraz daje 301 canonical przez WP. (c) `DISABLE_WP_CRON=true` w asiaauto/wp-config (cron pisał nowe uploads do złego katalogu → 404 obrazków, delta rsync dociągnął 417 plików). (d) MCP `asiaauto.pl/mcp-test/` usunięty (backup w ~/backups/). (e) Rebrand user-facing: 57 wystąpień `AsiaAuto`→`Prima-Auto` w pluginie (sed z regexem chroniącym klasy `AsiaAuto_*`), 17 subjectów mail, From Name, WhatsApp prefilled message ×3, theme `style.css` Theme Name, `blogname` option, regulamin. 222 klasy `AsiaAuto_*` nietknięte. (f) Email: `zamowienia@asiaauto.pl` + `samochody@asiaauto.pl` → `china@primaauto.com.pl` (plugin filter + DB 46 zamian). (g) **Reply-To fix** w `class-asiaauto-order.php:1443,1491,1518` — admin klikając Reply na powiadomienie pisze od razu do klienta (header `Reply-To: {customer_email}` w `wp_mail()`). (h) Plugin + theme zsynchronizowane 1:1 do legacy katalogu asiaauto.pl (rollback backup). Backupy: `2026-04-21-pre-cutover.sql.gz`, `2026-04-21-plugin-theme-pre-rebrand.tar.gz`, `2026-04-21-mcp-test-asiaauto.tar.gz`. |
| 2026-04-21 | 0.30.15 | prod asiaauto.pl | Fix zapisu w panelu Ustawienia zamówień: 7× `admin_url('tools.php')` → `admin_url('admin.php')` w `class-asiaauto-order-admin.php` (handleSaveConfig + handleSaveEmailTemplates + handleSaveStatusDisplay + 3× reset + zakładki w renderConfigPage). Zaległość po 0.30.11 gdy submenu przeszło spod `tools.php` pod parent `asiaauto-orders`, ale redirecty zostały stare → po zapisie leciał 403 „Brak uprawnień". Drugi numer kontaktowy w dziale Kontakt i w stopce: `company_phone_2` (`+48 721 730 512`) w `asiaauto_order_config`, pole edytowalne w panelu (Dane firmy), `[aa_phone slot="2"]` w `class-asiaauto-shortcodes.php`, inline obok głównego telefonu w `class-asiaauto-contact.php` (jeden wiersz, oba klikalne), nowy item w footer icon-list (post 93679) z dynamicznymi shortcodami `[aa_phone format="raw" slot="2"]` / `[aa_phone slot="2"]`. |
| 2026-04-20 | 0.30.14 | prod asiaauto.pl | Sesja 7: Prima Auto rebranding na stronie głównej — schema.org name="Prima Auto", hero sub bez "homologacja", USP rozszerzone 3→6 (grid 2×3 desktop, 9 nowych ikon SVG), tytuł "Dlaczego Prima Auto", nowa sekcja "W cenie" z 6 pozycjami pakietu CIF (język, głos, ładowarka 7kW, przejściówka, kluczyk, filtry), fuel header poprawiony "Rodzaj paliwa". Umowa PDF §6: "Przygotowanie auta do odbioru, tłumaczenie dokumentów, obsługa akcyzy, przegląd i ubezpieczenie na miesiąc" (zamiast "Homologacja, przygotowanie pojazdu, rejestracja"). Admin kursy: UI odwrócony na USD→CNY (np. 6.80) z automatyczną inwersją przy zapisie, storage nadal jako `kurs_cny_usd`, pipeline cenowy bez zmian. Inventory: nowy filtr `drive` (taxonomy Motors Pro, AWD/FWD/RWD, URL param `?naped=`, REST counters endpoint). Shortcody cenowe: `[asiaauto_price_netto]` (cena netto, format identyczny jak `[asiaauto_price]`), `[asiaauto_price_breakdown]` (brutto czerwony 24/30px + VAT + netto w kolumnie obok, flex 50%, breakpoint 768px), `[asiaauto_included]` (ul z 6 bulletami pakietu CIF). Elementor template single-listing (ID 101874): podmiana 2 widgetów html → icon-list via one-shot skrypt `scripts/replace-singleelementor-htmlwith-iconlist.php`, backup JSON w `/home/host476470/backups/primaauto/`. |
| 2026-04-20 | 0.30.13 | prod asiaauto.pl | Fix ghost-crona `asiaauto_daily_cleanup`: event był zaschedulowany ale brakował `add_action('asiaauto_daily_cleanup')` w bootstrapie (bliźniaczy bug do `asiaauto_sync_changes` naprawionego w 0.30.12 — przegapiony). Handler wywołuje `AsiaAuto_Rotation::cleanup()`. Jednorazowy run po wgraniu: 166 drafts→trash, 2077 trash→permanent delete (starsze niż 7d), 0 orphaned images. Stan trash: 5470 → 3559. Reszta (głównie 3141 z purge 04-17) wyleci naturalnie w kolejnych dniach. |
| 2026-04-17 | 0.30.12 | prod asiaauto.pl | Sesja 6: cron bug fixes — `cron_schedules` filter rejestruje `asiaauto_15min` (interval 900s); `add_action('asiaauto_sync_changes')` handler wywołuje `AsiaAuto_Sync::run('dongchedi')` (wcześniej event był ghostem bez handlera, sync leciał tylko przez CLI). ZADANIE 6 Krok D: purge 2905 listings (marki OR miasta poza listą), bulk-import-by-brand.php (reverse-order pages + MAX_PAGES=50 + probe [50,40,30,20,10,5,2,1], parametr `mark=X` nie `brand=`), fix-missing-images.php (45/45: 15 OK + 30 TRASH ghost-offers po expired CDN x-expires). UX fix: modal miast auto-fill 31 defaults gdy saved=[]. Net: publish 736→809 (+73), cron zweryfikowany działa. |
| 2026-04-17 | 0.30.11 | prod asiaauto.pl | Uprawnienia sprzedawcy: nowy cap `manage_asiaauto_import` (rola `primaauto` + admin), trzy strony admina wychodzą spod `tools.php`: Konfigurator importu → `Ogłoszenia → Konfigurator importu` (IMPORT_CAP), Dodaj z Dongchedi → nadal pod Ogłoszeniami (IMPORT_CAP, + 2× AJAX), Ustawienia zamówień → submenu pod głównym menu Zamówień (ORDER_CAP). Globalny `menu_order` filter: Kokpit → Zamówienia → Ogłoszenia → Strony → reszta. `grantAdminImportCap()` w activation hooku, one-shot uruchomiony przez `wp eval` na prod. |
| 2026-04-17 | 0.30.10 | prod asiaauto.pl | ZADANIE 6 Krok A+B: filtr miast (31 domyślnych z Guangdong/Fujian/Guangxi/Hainan, modal w panelu admina, dodawanie custom), filtr w `isAllowedByConfig`. Perf: transient cache na 3× COUNT postmeta (10min TTL, invalidate po bulk recalc). Trash TTL: 30d → 7d. |
| 2026-04-17 | 0.30.9 | prod asiaauto.pl | B2 SEO: meta/OG/title dla single listing + inventory, blok SEO marki/modelu, term meta `asiaauto_seo_desc`, min-price transient, `llms.txt`. |
| 2026-04-17 | 0.30.8 | prod asiaauto.pl | Załączniki PDF (akcyza 0% widoczna), token w nazwie PDF, UPLOAD_DIR→contracts, nr umowy w tytule przelewu, info o podpisach w wizardzie. Bootstrap odtworzony po uszkodzeniu sed. |
| 2026-04-16 | 0.30.7 | prod asiaauto.pl | Sesja 2: CIF fix, panel klienta, version bump. ZADANIE 5 core DONE. |
| 2026-04-15 | 0.29.0-wip | prod asiaauto.pl | Bootstrap repo primaauto. PHP lint clean (PHP 8.3). Pending: v0.30.6 (3 patche, nie wgrane). |
