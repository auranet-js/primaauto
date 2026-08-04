# T-225 — Rework `/marki/`: z listy nazw w stronę filarową działu wiedzy

> Status: **plan gotowy do wykonania** (2026-08-04) · Rozmiar: M · Godziny realnie: **10–14 h**
> Rodzic: **T-214 F4** (rankingi jako wpisy w `/rankingi/`; ta strona jest ich pillarem) · Pomiar wejściowy: `docs/analizy/2026-08-04-rankingi-porownania-recon.md`
> Dotyka `page-marki.php` = **strefa ZAWSZE PYTAJ** (diff do akceptu przed każdym deployem szablonu)

## Cel

`/marki/` przestaje być listą 58 nazw z licznikami, a staje się stroną, która odpowiada na pytanie „kto jest kim na rynku chińskim" — i rozprowadza ruch na huby marek oraz rankingi segmentowe.

## Stan faktyczny (zweryfikowany na produkcji i w Chrome 2026-08-04)

- Strona ID **263572**, slug `/marki/`, szablon `page-marki.php` (88 linii), **`post_content` pusty**.
- Render to: `H1` + jedno zdanie + `H2 Najpopularniejsze marki` (8 kafelków) + `H2 Pozostałe marki` (50 kafelków) + stopka. Kafelek = `<span>nazwa</span><span>liczba</span>`. **Zero obrazków, zero akapitów, zero FAQ, brak `ItemList`.**
- **Szablon ma już hook na treść:** `if (trim(get_the_content()) !== '')` renderuje `.aa-marki__intro` **między podtytułem a gridem** (l. 62–68). Tekst wpisany w edytorze pojawi się bez dotykania kodu. To jedyne miejsce na treść — wszystko pod gridem wymaga szablonu.
- **TOP8 jest zahardkodowany** (l. 12): `['byd','aito','geely','xiaomi','xpeng','volkswagen','zeekr','avatr']`. Efekt: **Volkswagen z 8 ofertami stoi w „Najpopularniejszych", a Hongqi (116), Li Auto (116), Leapmotor (115), Denza (108) i NIO (90) są w „Pozostałych".**
- **49 z 58 marek ma wypełnione `term_taxonomy.description`** (min 6 585, śr. 8 821, max 12 908 znaków) — pełne opisy marek, **nigdzie nierenderowane**. Hub marki renderuje inne pole: `asiaauto_wiki_body` (58 marek) + `asiaauto_faq_json` (55). Czyli treść istnieje i leży odłogiem.
- **9 marek bez `description`:** Ford (4), Foton (4), Maxus (3), Toyota (3), Shangjie (2), BAW (2), Dongfeng Fengxing (1), Honda (1), Mercedes-Benz (1).
- Inne gotowe dane na termach: `_asiaauto_pl_availability` (`import_only` / `salon_available`, 46 marek), `_asiaauto_facts`, `_asiaauto_lead`.
- `/rankingi/` zwraca **HTTP 200, 0 wpisów**; `category.php` obsługuje sekcję. `kb-sidebar.php` = gotowy wzorzec widgetu „najnowsze".
- **Wydajność dziś (GSC 90 dni): 631 wyświetleń, 2 kliknięcia.** „marki chińskich samochodów" poz. 15,6 · „chińskie marki samochodów" poz. 35,7 · „chinskie marki aut" poz. 43,3. Fraza główna ma **5 400/mc** w DFS.
- **Huby marek o rodowodzie nie-chińskim robią 864 klik. / 16 460 wyśw. w 90 dni** (VW 469, Mazda 236, Volvo 57, MG 30, Audi 28) — argument przeciw usuwaniu ich ze strony.
- Popyt potwierdzający sekcję o własności: **„kto jest właścicielem volvo" 720/mc**, „chińskie volvo" 140/mc; GSC: „volkswagen z chin" 62 wyśw. poz. 7,7, „chiński mercedes" 18 wyśw. **poz. 1,2**, „chińska toyota" 21 wyśw.
- **„logo chińskich samochodów" — poz. 7,4** przy zerowej liczbie obrazków na stronie.

## Decyzje

- **D1 — ⚠️ CZEKA NA POTWIERDZENIE JANKA.** Rekomendacja: marek nie-chińskich **nie usuwać**, tylko rozdzielić sekcjami. Usunięcie odcięłoby linkowanie wewnętrzne do hubów robiących 864 kliknięcia i wyrzuciło udokumentowany popyt („kto jest właścicielem volvo" 720/mc, „volkswagen z chin" poz. 7,7, „chiński mercedes" poz. 1,2). Rozmycie tematyczne bierze się z braku wyjaśnienia, nie z obecności marek — nagłówek sekcji je usuwa.
  **Wariant alternatywny (jeśli Janek podtrzyma usunięcie):** Task 2 zostaje bez zmian, w Task 4 sekcja `global_jv` znika z gridu (8 marek), sekcja `chinese_owned` **zostaje** (Volvo/MG/Smart/Lotus/Maxus to własnościowo marki chińskie). Wtedy trzeba dodać krok: zapewnić hubom VW/Mazda/Toyota/Audi/Nissan/Honda/Ford/Mercedes linkowanie z innego miejsca (np. z katalogu `/samochody/`), żeby nie zostały osierocone.
- **D2 — Klasyfikacja jako dane, nie kod:** term meta `_asiaauto_brand_origin` ∈ {`chinese`, `chinese_owned`, `global_jv`}. Do reużycia na hubach i w filtrach.
- **D3 — Opisy w kafelkach ze skrótu `description`** (pierwsze zdanie), nie z `asiaauto_seo_desc` (ta jest szablonowa: „X w Prima-Auto — N ofert, ceny od…" — powtarzalna, bezwartościowa jako opis marki).
- **D4 — Rankingi mają własny listing `/rankingi/`;** na `/marki/` ląduje tylko sekcja z 3–4 ostatnimi, ukrywana gdy brak wpisów.
- **D5 — Logotypy:** źródła w kolejności Wikimedia Commons → materiały prasowe producenta, z rejestrem licencji w meta załącznika (procedura z T-214 §4.5). Brak logo = kafelek tekstowy jak dziś, bez pustego miejsca.

## Pliki

| Plik | Rola |
|---|---|
| `wp-content/themes/primaauto2026/page-marki.php` | **modyfikacja** — 3 sekcje, TOP wg `count`, opisy i logo w kafelku, FAQ, schema, widget rankingów |
| `wp-content/themes/primaauto2026/assets/css/*` | **modyfikacja** — style kafelka z logo i opisem, sekcja FAQ |
| `scripts/set-brand-origin.php` | **nowy** — jednorazowy setter `_asiaauto_brand_origin` (idempotentny, `--dry-run`) |
| `scripts/fetch-brand-logos.php` | **nowy** — pobranie logo + zapis rejestru licencji w meta załącznika |
| strona ID 263572 (`post_content`) | **modyfikacja** — wstęp merytoryczny nad gridem |
| 9 termów `make` bez `description` | **uzupełnienie** treści |

---

## Task 1 — Wstęp merytoryczny w treści strony (bez dotykania kodu)

**Deliverable:** strona ma 400–600 słów sensownego tekstu nad gridem. Zero ryzyka — sam `post_content`.

- [ ] **1.1** Napisać wstęp (3–4 akapity), obowiązkowo pokrywający: ile marek i skąd rozbieżność z „chińskie marki" w potocznym rozumieniu; kto do kogo należy (BYD → Denza, Yangwang, Fangchengbao; Geely → Zeekr, Lynk & Co, Galaxy, Volvo, Lotus, Smart, LEVC; Chery → Jetour, Exeed, iCAR, Luxeed, Exlantix; SAIC → MG, Maxus, IM Motors, Shangjie; Changan → Deepal, Avatr, Qiyuan; GWM → Haval, Tank, WEY, Ora; Dongfeng → Voyah, Fengshen, Yipai; Huawei HIMA → AITO, Luxeed, Stelato, Maextro); czym różni się marka chińska od auta produkowanego w Chinach.
- [ ] **1.2** Pokazać treść Jankowi do akceptu (drop na auratest).
- [ ] **1.3** Wgrać: `wp post update 263572 --post_content="$(cat tresc.html)"` — **przed** tym `wp post get 263572 --field=post_content > ~/backups/primaauto/$(date +%F)/marki-content.before`.
- [ ] **1.4 Test:** `curl -s https://primaauto.com.pl/marki/ | grep -c "aa-marki__intro"` → 1; liczba słów w `<main>` ≥ 400; strona nadal HTTP 200; grid nietknięty (58 kafelków).
- [ ] **1.5** Commit w repo kontekstowym: `[content:] T-225 wstęp merytoryczny na /marki/`

---

## Task 2 — Klasyfikacja marek jako dane

**Deliverable:** każdy term `make` z `count>0` ma `_asiaauto_brand_origin`.

Przypisanie (58 marek, stan 2026-08-04):

- `chinese_owned` (5): **Volvo, Lotus, Smart, MG, Maxus** — europejski/brytyjski rodowód, chiński właściciel (Geely: Volvo, Lotus, Smart 50/50 z Mercedesem; SAIC: MG, Maxus).
- `global_jv` (8): **Volkswagen, Audi, Toyota, Nissan, Mazda, Honda, Ford, Mercedes-Benz** — marki globalne, modele produkowane w Chinach w joint-venture, często niedostępne w Europie.
- `chinese` (45): cała reszta, w tym Maextro (Huawei + JAC) i Auxun.

- [ ] **2.1** Napisać `scripts/set-brand-origin.php` — mapa `slug => origin`, domyślnie `chinese`, `--dry-run` wypisuje planowane zmiany bez zapisu, uruchomienie idempotentne.

```php
$MAP = [
    'volvo' => 'chinese_owned', 'lotus' => 'chinese_owned', 'smart' => 'chinese_owned',
    'mg' => 'chinese_owned', 'maxus' => 'chinese_owned',
    'volkswagen' => 'global_jv', 'audi' => 'global_jv', 'toyota' => 'global_jv',
    'nissan' => 'global_jv', 'mazda' => 'global_jv', 'honda' => 'global_jv',
    'ford' => 'global_jv', 'mercedes-benz' => 'global_jv',
];
foreach (get_terms(['taxonomy' => 'make', 'hide_empty' => true]) as $t) {
    $origin = $MAP[$t->slug] ?? 'chinese';
    if ($dry) { printf("%-22s %-14s (było: %s)\n", $t->slug, $origin,
        get_term_meta($t->term_id, '_asiaauto_brand_origin', true) ?: '—'); continue; }
    update_term_meta($t->term_id, '_asiaauto_brand_origin', $origin);
}
```

- [ ] **2.2** `php -l scripts/set-brand-origin.php`
- [ ] **2.3** `wp eval-file scripts/set-brand-origin.php --dry-run` → sprawdzić, że 5 + 8 + 45 = 58 i żadna marka chińska nie wpadła do `global_jv`.
- [ ] **2.4** Uruchomić bez `--dry-run`.
- [ ] **2.5 Test:** `wp db query "SELECT meta_value, COUNT(*) FROM wp7j_termmeta WHERE meta_key='_asiaauto_brand_origin' GROUP BY meta_value"` → `chinese 45`, `chinese_owned 5`, `global_jv 8`.
- [ ] **2.6** Commit: `[theme:] T-225 klasyfikacja pochodzenia marek (_asiaauto_brand_origin)`

---

## Task 3 — Uzupełnienie 9 brakujących opisów marek

**Deliverable:** wszystkie 58 marek mają `description`; skrót do kafelka da się wyciąć dla każdej.

- [ ] **3.1** Napisać opisy dla: Ford, Foton, Maxus, Toyota, Shangjie, BAW, Dongfeng Fengxing, Honda, Mercedes-Benz. Dla `global_jv` opis musi wprost mówić, **dlaczego marka jest na tej stronie** (joint-venture, modele wyłącznie na rynek chiński) — to jest treść odpowiadająca na „chińska toyota" i „chiński mercedes" (poz. 1,2 w GSC).
- [ ] **3.2** Akcept Janka (drop na auratest).
- [ ] **3.3** Wgrać: `wp term update make <term_id> --description="..."` (backup `description` wszystkich 58 termów do pliku przed operacją).
- [ ] **3.4 Test:** `wp db query "SELECT COUNT(*) FROM wp7j_term_taxonomy WHERE taxonomy='make' AND count>0 AND description=''"` → **0**.
- [ ] **3.5** Commit: `[content:] T-225 opisy 9 brakujących marek`

---

## Task 4 — Szablon: sekcje, kolejność, opisy w kafelkach

**Deliverable:** grid rozbity na 3 nazwane sekcje, TOP liczony z danych, kafelek z jednozdaniowym opisem.

- [ ] **4.1** Backup: `cp page-marki.php page-marki.php.bak-$(date +%F)`
- [ ] **4.2** Zamienić hardkod TOP8 na sortowanie po `count` (tylko marki `chinese`, próg: 8 pierwszych).
- [ ] **4.3** Rozbić grid na trzy sekcje z nagłówkami i zdaniem wprowadzającym w każdej:
  - `H2 Chińskie marki` — podzielone na „Najpopularniejsze" (8 wg `count`) i „Pozostałe"
  - `H2 Europejskie marki należące do chińskich koncernów` + zdanie: kto jest właścicielem
  - `H2 Globalne marki produkowane w Chinach` + zdanie: joint-venture, modele spoza Europy
- [ ] **4.4** Dodać skrót opisu do kafelka — pierwsze zdanie z `description`, ucięte do 140 znaków:

```php
$aa_brand_teaser = static function (WP_Term $t): string {
    $raw = trim(wp_strip_all_tags($t->description));
    if ($raw === '') { return ''; }
    $first = preg_split('/(?<=[.!?])\s+/u', $raw, 2)[0];
    return mb_strlen($first) > 140 ? mb_substr($first, 0, 137) . '…' : $first;
};
```

- [ ] **4.5** CSS dla `.aa-brand-card__teaser` (rozmiar mniejszy od nazwy, 2 linie z `-webkit-line-clamp`, kontrast zgodny z WCAG).
- [ ] **4.6** `php -l page-marki.php`
- [ ] **4.7** **Pokazać diff Jankowi, czekać na „ok"** (strefa ZAWSZE PYTAJ).
- [ ] **4.8** Deploy, flush cache.
- [ ] **4.9 Test:** curl → 3 sekcje `H2`, 58 kafelków bez ubytku, Volkswagen **nie** w „Najpopularniejszych", Hongqi/Li Auto/Leapmotor/Denza/NIO **w** „Najpopularniejszych"; każdy kafelek ma teaser.
- [ ] **4.10 Test MCP Chrome:** desktop + **mobile 375 px** — kafelek z opisem nie rozjeżdża gridu, teksty nie zachodzą.
- [ ] **4.11** Commit + bump wersji motywu + `docs/THEME-VERSIONS.md`

---

## Task 5 — Logotypy marek

**Deliverable:** kafelki z logo, rejestr źródeł, odpowiedź na „logo chińskich samochodów" (dziś poz. 7,4 przy zerze obrazków).

- [ ] **5.1** Zebrać logo dla 58 marek: Wikimedia Commons → materiały prasowe producenta. Format: PNG/SVG na przezroczystym tle, docelowo ≤ 200 px szerokości, konwersja do WebP.
- [ ] **5.2** Wgrać do biblioteki mediów z meta `_asiaauto_asset_source`, `_asiaauto_asset_license`, `_asiaauto_asset_url`, `_asiaauto_asset_fetched_at` (rejestr licencji wg T-214 §4.5) + `alt` w formacie `Logo <marka>`.
- [ ] **5.3** Powiązać z termem: `update_term_meta($id, '_asiaauto_brand_logo_id', $attachment_id)`.
- [ ] **5.4** Render w kafelku z `loading="lazy"`, `width`/`height` (bez CLS); brak logo → kafelek tekstowy jak dziś.
- [ ] **5.5 Test:** liczba `<img>` w gridzie = liczba marek z logo; Lighthouse/PSI — CLS bez regresji; `alt` niepusty na każdym.
- [ ] **5.6** Commit: `[theme:] T-225 logotypy marek na /marki/`

---

## Task 6 — FAQ, schema i sekcja rankingów pod gridem

**Deliverable:** strona zamyka się treścią i linkami zamiast urywać się na ostatnim kafelku.

- [ ] **6.1** FAQ (4–6 pytań) pod gridem, z realnych zapytań GSC/DFS: „Ile jest chińskich marek samochodów?", „Kto jest właścicielem Volvo?" (**720/mc**), „Czy MG to chińska marka?", „Które chińskie marki są dostępne w polskich salonach?" (dane z `_asiaauto_pl_availability`), „Czym różni się chińska marka od auta produkowanego w Chinach?".
- [ ] **6.2** Schema: `ItemList` z marek (`position`, `name`, `url`) + `FAQPage`. Wpiąć w `class-asiaauto-seo.php` wzorcem z T-214 — **nie dublować** `FAQPage`, jeśli RankMath już je wystawia na tej stronie (sprawdzić Rich Results Test przed i po).
- [ ] **6.3** Sekcja „Najnowsze rankingi" — 3–4 ostatnie wpisy z kategorii `rankingi`, wzorzec z `kb-sidebar.php`:

```php
$aa_rankings = get_posts([
    'post_type' => 'post', 'category_name' => 'rankingi',
    'posts_per_page' => 4, 'orderby' => 'date', 'order' => 'DESC',
]);
```

  Sekcja renderuje się **tylko gdy `$aa_rankings` niepuste** — dziś kategoria ma 0 wpisów, więc do czasu pierwszych rankingów strona wygląda jak bez niej. Nagłówek linkuje do `/rankingi/`.
- [ ] **6.4** `php -l`, diff do akceptu Janka, deploy.
- [ ] **6.5 Test:** Rich Results Test dla `/marki/` — `ItemList` i `FAQPage` walidują się, brak duplikatu `FAQPage`; sekcja rankingów niewidoczna przy 0 wpisach; po dodaniu testowego draftu **nadal** niewidoczna (tylko `publish`).
- [ ] **6.6** Commit + bump + `THEME-VERSIONS.md`

---

## Task 7 — Domknięcie SEO

- [ ] **7.1** Sprawdzić `title`/`description` strony w RankMath (dziś: „Marki chińskich samochodów — pełna lista | Prima-Auto") pod frazę główną `chińskie marki samochodów` / `marki chińskich samochodów`.
- [ ] **7.2** Zgłosić `/marki/` do indeksacji **wyłącznie** przez `~/bin/index-submit --project primaauto --type URL_UPDATED` (1 URL; najpierw `--status`, budżet dzienny).
- [ ] **7.3** Sprawdzić obecność `/marki/` w `llms.txt` / `llms-full.txt`; jeśli brak — dopisać **w generatorze** (`scripts/build-llms.php`), nie w pliku.
- [ ] **7.4** Zapisać baseline pomiaru: pozycje i wyświetlenia fraz „marki chińskich samochodów", „chińskie marki samochodów", „chinskie marki aut", „logo chińskich samochodów", „kto jest właścicielem volvo" — do porównania po 30 dniach.
- [ ] **7.5** Commit: `[docs:] T-225 baseline pomiaru /marki/`

---

## Testy końcowe

**Automatyczne**
- `/marki/` HTTP 200, 58 kafelków, 3 sekcje, ≥ 900 słów w `<main>` (dziś: 367, z czego tekstem jest jedno zdanie).
- Żaden hub marki nie stracił linku wewnętrznego (porównanie listy `href` przed/po).
- `ItemList` + `FAQPage` walidują się; brak duplikatu.
- Sekcja rankingów ukryta przy zerze opublikowanych wpisów.

**Półautomatyczne**
- Rich Results Test, PSI (CLS po dodaniu 58 logo).
- Przegląd 3 losowych teaserów — czy pierwsze zdanie `description` czyta się sensownie po ucięciu.

**MCP Chrome**
- Desktop i mobile 375 px: sekcje, kafelki z logo i opisem, FAQ, sekcja rankingów.
- Przejście `/marki/` → hub marki → oferta (ścieżka pillar → cluster → konwersja).

## Definicja zrobionego

- Trzy nazwane sekcje zamiast dwóch bezimiennych; Volkswagen nie udaje najpopularniejszej chińskiej marki.
- Każda marka ma opis (58/58) i teaser w kafelku; 49 gotowych opisów przestaje leżeć odłogiem.
- Logotypy na kafelkach z zarejestrowanym źródłem licencji.
- FAQ + `ItemList` + `FAQPage`.
- Sekcja „Najnowsze rankingi" gotowa i podpięta do `/rankingi/`, ukryta do czasu pierwszych wpisów.
- Baseline pomiaru zapisany; recheck GSC po 30 dniach.

## Ryzyka

- **Szablon = ZAWSZE PYTAJ** — każdy deploy `page-marki.php` z diffem do akceptu, `.bak` z datą, `php -l` przed wgraniem.
- **Logotypy** — znaki towarowe; użycie informacyjno-katalogowe bez sugerowania powiązania, rejestr źródeł obowiązkowy. Brak logo o pewnym pochodzeniu → zostaje kafelek tekstowy, nie „coś podobnego".
- **Kanibalizacja** — `/marki/` przejmuje frazy „chińskie marki…". Żaden przyszły ranking ani hasło `/wiki/` nie może celować w tę samą frazę (reguła D2 z T-162, gate GSC przed publikacją).
- **Teaser z `description`** — opisy mają 6,5–12,9 tys. znaków; jeśli pierwsze zdanie któregoś jest nagłówkiem lub urwane, teaser wyjdzie dziwny. Stąd przegląd ręczny w testach.
