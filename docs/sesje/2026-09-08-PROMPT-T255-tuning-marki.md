# T-255 — Tuning `/marki/`: galeria z własnych sesji, układ kafli, SEO (pakiet wykonawczy, 2026-09-08)

> ✅ **WYKONANE 2026-09-08, motyw 1.5.0.** Ten plik zostaje jako zapis zakresu i decyzji.
> Co realnie weszło i czym się różni od planu — `docs/THEME-VERSIONS.md` (wpis 1.5.0);
> pomiar do porównania za 30 dni — `docs/seo/2026-09-08-t255-marki-baseline.md`.
> Krok „makieta do akceptu" **pominięty na polecenie Janka** („leć całość od razu, zatrzymaj się
> jak opublikujesz na produkcji") — uwagi zbieramy na żywej stronie.

> Kontynuacja T-225 (rework z 04.08, zamknięty w częściach a/b/d). Strona **żyje i rośnie** — rewrite
> podniósł ją z 3 do 32 kliknięć miesięcznie — ale stoi na progu strony drugiej i ma dwie widoczne
> dziury: martwe zdjęcia w galerii i siatkę 51 kafli ułożoną alfabetycznie, w której marka z trzema
> autami otwiera sekcję przed marką ze stu pięćdziesięcioma.
>
> Motyw na wejściu: **primaauto2026 1.4.0** (`PRIMAAUTO_THEME_VERSION`; `style.css` miał nieaktualizowane 1.0.8 —
> poprawione przy okazji). Wyjście: **1.5.0** (nowa galeria + przebudowa siatki).
> Plugin `asiaauto-sync` **nietykany** — całość zadania siedzi w motywie i w treści strony 263572.
>
> Szacunek: **6–8 h realnie / 16–20 h rynkowo.**

---

## §1. Decyzje zamknięte (nie otwierać ponownie)

| # | Decyzja | Kto, kiedy |
|---|---|---|
| D1 | **Bez logotypów marek.** Kafle zostają tekstowe — nie naruszamy znaków towarowych. Klastra fraz „logo / loga chińskich samochodów" (~79 impresji/mc, poz. 9–24) **świadomie nie gonimy**. Potwierdza wcześniejsze zdjęcie T-225c z 04.08. | Janek, 08.09 |
| D2 | **Galeria z naszych sesji z Dysku Google**, wgrana do `uploads/` jako trwałe media. Nie z rotacji ofert, nie ze zdjęć chińskich placów. | Janek, 08.09 |
| D3 | Kadry linkują do **hubów modeli**, nie do pojedynczych ofert — oferta wypada z rotacji po 7 dniach, hub zostaje. | wynika z D2 |
| D4 | Marki `global_jv` (9 szt.: VW, Toyota, Mazda, Audi, BMW, Ford, Honda, Mercedes, Nissan) **nadal poza siatką**. Ich huby nietknięte. | T-225b, 04.08 |
| D5 | **Nazw termów `make` nie ruszamy.** Nazwa termu idzie do tytułu odświeżanej oferty ([[reference_nazwa_termu_serie_idzie_do_tytulu_oferty]]) — scalanie sub-marek (Great Wall/GWM, Dongfeng Fengshen/Yipai) to osobna zgoda i osobny pomiar, **nie wchodzi tutaj**. | zasada projektu |

---

## §2. Stan faktyczny — zmierzony 2026-09-08, nie z pamięci

### Co jest zepsute wizualnie

- **2 z 6 kadrów galerii są martwe.** Pliki nie istnieją na dysku:
  - `asiaauto/2026/06/galaxy-yizhen-l380-2024-dongguan-24194948-1-768x576.jpg` (LEVC L380)
  - `asiaauto/2026/08/voyah-voyah-dreamer-phev-2026-shenzhen-59215518-1-768x576.webp` (Voyah Dream)
  
  Na stronie renderuje się goły alt-text. Potwierdzone dwiema drogami: `test -f` na ścieżkach
  i pomiar w przeglądarce (`naturalWidth === 0`).
- **Ten drugi plik jest `og:image` strony** — udostępnienie linku na Facebooku pokazuje pustkę.
- Linki tych dwóch kadrów oddają **301 na huby** (`/samochody/geely/levc-l380/`, `/samochody/voyah/dream-phev/`)
  — auta wypadły z rotacji i pociągnęły za sobą zdjęcia. To nie awaria, to normalny cykl życia oferty:
  **galeria zaszyta na sztywno w `post_content` będzie gnić dalej**.
- Galeria stoi w treści posta **263572** jako surowy `<div style="display:grid…">` z inline'owymi stylami.

### Jak wygląda siatka (51 kafli)

- Sekcje: „Najpopularniejsze" (8 sztuk, twardy `array_slice`), „Pozostałe chińskie marki" (39),
  „Marki o europejskim rodowodzie" (4: MG, Smart, Volvo, Lotus).
- **Sekcja „Pozostałe" idzie alfabetycznie** — Aistaland (3 auta) otwiera listę, a Zeekr (154),
  Jetour (120), Geely (111) i Denza (104) leżą wymieszane z BAW (2), GWM (1), Wuling (1).
  Próg „TOP 8" tnie między Hongqi (162) a Zeekr (154), czyli w miejscu bez żadnej różnicy.
- **6 kafli nie ma drugiej linii** — brak `_asiaauto_brand_group` przy: XPeng (243), NIO (229),
  Li Auto (203), Xiaomi (197), Leapmotor (170), HiPhi (1). Pięć z nich siedzi w czołówce,
  więc poszarpana jest najbardziej widoczna sekcja. Na telefonie wysokości kafli w rzędzie pływają.
- **Ogon: 13 marek ma ≤3 oferty** (Aistaland 3, Maextro 3, Maxus 3, BAW 2, Dongfeng Fengshen 2,
  Great Wall 2, JMC EV 2, Shangjie 2, Dongfeng Yipai 1, GWM 1, HiPhi 1, Jetour Shanhai 1, Wuling 1).
- Strona na telefonie ma **9636 px wysokości** przy 79,6% ruchu mobilnego.

### Braki w danych

| Co | Ile | Szczegóły |
|---|---|---|
| Brak `_asiaauto_brand_group` | 6 marek w siatce | XPeng, NIO, Li Auto, Xiaomi, Leapmotor, HiPhi |
| Brak `term->description` | 2 marki w siatce | **Stelato (64 oferty!)**, Aistaland (3) |
| Opis krótszy niż 600 znaków | 4 marki | BAW 506, Foton 498, Maxus 585, Shangjie 570 |

⚠️ `term->description` **nie renderuje się nigdzie na froncie** (ustalone w T-225a, 07.09) — hub czyta
`asiaauto_wiki_body`. Braki opisów nadrabiamy **tylko** jeśli robimy to przy okazji hubów; na samej
`/marki/` nie mają wpływu. Nie zawyżaj przez to zakresu.

### SEO — pomiar GSC (Search Console, nie narzędzie zewnętrzne)

| okno | kliki | impresje | CTR | pozycja |
|---|---|---|---|---|
| 05.06–03.08 (przed reworkiem) | 3 | 1 135 | 0,26 % | 18,0 |
| 05.08–04.09 (po reworku) | 32 | 3 130 | 1,02 % | 12,7 |

161 różnych zapytań. Zaindeksowana, ostatni crawl **06.09** (URL Inspection).

**Gdzie leżą pieniądze — trzy konkretne rzeczy:**

1. **`marka chińskich aut` — 222 impresje, pozycja 8,3, ZERO kliknięć.** Największy pojedynczy zasób
   strony. Dół pierwszej strony, snippet nie zabiera. Focus keyword ustawiony jest dziś na
   `marki samochodów z Chin` — czyli **nie na tę frazę**.
2. **Klaster „ile jest marek" — 5 fraz, ~80 impresji, pozycje 8,3–10,6, CTR 0 %:**
   `ile jest chińskich marek samochodów` (20), `ile marek samochodów jest w chinach` (19),
   `ile jest chinskich marek aut` (18), `ile jest marek chińskich samochodów` (12),
   `ile jest marek samochodów w chinach` (11). Odpowiedź **mamy** — ale schowaną w zwiniętym
   `<details>` na samym dole strony. To gotowy featured snippet do zabrania.
3. `chinskie marki aut` (114 imp, poz. 19,6) i `chińskie marki samochodów` (84 imp, poz. 26,1) —
   te siedzą na stronie 2–3, ich ruszenie to kwestia treści i linkowania, nie snippetu.

**Czego nie gonimy:** klaster „logo" (D1) i frazy zakupowe typu `auta z chin` (poz. 50+) — na te
odpowiada `/samochody/`, nie ta strona.

### Materiał zdjęciowy na Dysku (zweryfikowany 08.09)

`Mój dysk / PrimaAuto / sesje/` — sesje z Rzeszowa, sierpień 2026:

| Folder | Marka | Hub docelowy | Ofert na hubie |
|---|---|---|---|
| `Denza-N9` (22 kadry + 2 wideo) | Denza (BYD) | `/samochody/denza/n9-dm-i/` | 21 |
| `Denza-Z9GT` | Denza (BYD) | `/samochody/denza/z9-gt-dm-i/` | 24 |
| `Byd-Shark-6` | BYD | `/samochody/byd/shark-6/` | 1 |
| `Leopard-5` (Czarny, Niebieski) | BYD / Fangchengbao | `/samochody/byd/leopard-5/` | 20 |
| `Leopard-7` | BYD / Fangchengbao | `/samochody/byd/leopard-7/` | 16 |
| `Jetour-T2` | Jetour (Chery) | `/samochody/jetour/t2/` | 52 |
| `Lynk-Co-900` | Lynk & Co (Geely) | `/samochody/lynk-co/900/` | 22 |
| `Deepal-G318` | Deepal (Changan) | `/samochody/deepal/g318/` | 4 |
| `Exceed-VX` | Exeed (Chery) | `/samochody/exeed/vx/` | 5 |

Pliki to JPG 1,4–2,7 MB, nazwy `SCH02xxx.jpg`. **Żaden nie jest jeszcze wgrany na serwer**
(sprawdzone: `find uploads -name 'SCH*'` → pusto).

Pokrycie koncernów: **4 z 8** (BYD, Chery, Geely, Changan). Brak własnych zdjęć dla GWM, Dongfeng,
SAIC, FAW/GAC i Huawei HIMA — **i to jest w porządku**, galeria ma pokazywać nasze auta, nie
ilustrować mapę koncernów jeden do jednego. Podpis pod galerią zmień tak, żeby nie obiecywał
kompletu (patrz §3A krok 6).

---

## §3. Zakres — co dokładnie powstaje

### A. Galeria z własnych sesji (zastępuje obecną, ~2,5 h)

1. Pobierz z Dysku po **1–2 najlepsze kadry** z każdego z 9 folderów (poziome, całe auto,
   plac/otoczenie bez przypadkowych osób i tablic rejestracyjnych w kadrze — jeśli są, wybierz inny kadr).
2. Kadruj do **3:2**, długość dłuższego boku **1600 px**, zapis **WebP** jakość 82.
   Zrób też wariant 768 px (`-768x512.webp`) — to jest rozmiar, który realnie idzie w `srcset`.
3. **Nazwy plików pod SEO** — wzorzec z [[feedback_obrazy_webp_nazwa_i_opisy_pod_seo]]:
   `denza-n9-prima-auto-rzeszow-2026.webp`, `jetour-t2-prima-auto-rzeszow-2026.webp` itd.
   Marka-model-marka nasza-miasto-rok. **Nie** `SCH02796.webp`.
4. Wgraj do `wp-content/uploads/asiaauto/sesje/2026-08-rzeszow/` (nowy katalog — **poza** drzewem
   importera, żeby żaden automat rotacji ich nie tknął) albo przez bibliotekę mediów WP.
   Jeśli przez media WP — ustaw `alt` w bibliotece, nie tylko w HTML-u.
5. **Galerię przenieś z treści posta do szablonu** `page-marki.php` jako tablicę PHP
   (ścieżka pliku, alt, podpis, URL huba). Powód: treść posta edytuje się w Gutenbergu i inline'owe
   `style=` w niej to proszenie się o rozjazd; w szablonie masz jedno miejsce i klasy CSS.
   Z `post_content` usuń cały blok `<div style="display:grid…">` **wraz z akapitem pod nim**.
6. Podpis pod galerią: dziś brzmi *„Zdjęcia pochodzą z aut, które mamy albo mieliśmy w ofercie"*.
   Nowy ma mówić prawdę o tym, co się zmieniło — że to **nasze zdjęcia z placu w Rzeszowie**,
   a nie zdjęcia od dostawcy. Jedno zdanie, bez patosu.
7. **Ustaw `og:image` na jeden z nowych kadrów** (RankMath: Social → Facebook Image na stronie 263572).
   Dziś wskazuje na plik, którego nie ma.

### B. Układ i sortowanie kafli (~2 h)

8. **Sortowanie w każdej sekcji: malejąco po liczbie ofert**, nie alfabetycznie. To jedyna zmiana,
   która sama z siebie naprawia „Aistaland przed Geely".
9. **Zamiast twardego `array_slice($…, 0, 8)` — próg liczbowy.** Propozycja: czołówka to marki
   z **≥100 ofertami** (dziś 12: BYD 365 … Denza 104), reszta niżej. Próg tnie w miejscu, gdzie
   jest realna przerwa w danych, i nie wymaga ręcznej korekty, gdy magazyn się przesunie.
10. **Ogon (≤3 oferty, 13 marek) — osobna, wizualnie lżejsza grupa** pod siatką: nagłówek
    „Marki pojedynczych egzemplarzy" albo równoważny, kafle w mniejszym wariancie
    (`.aa-brand-card--mini`), bez zmiany treści kafla. Nie ukrywamy ich (huby żyją i mają ruch),
    ale przestają rozbijać rytm głównej siatki.
11. **Uzupełnij `_asiaauto_brand_group` dla 6 marek** — to naprawia poszarpaną czołówkę:
    XPeng → `XPeng`, NIO → `NIO`, Li Auto → `Li Auto`, Xiaomi → `Xiaomi`, Leapmotor → `Leapmotor`,
    HiPhi → `Human Horizons`. ⚠️ Uwaga: szablon **celowo zjada** grupę równą nazwie marki
    (`mb_strtolower($group) === mb_strtolower($t->name)` → pusty), żeby kafel nie powtarzał nazwy.
    Czyli samo wpisanie `XPeng` **nic nie da**. Trzeba albo drugiej linii o innej treści
    (np. `producent niezależny`), albo — lepiej — **wyrównać kafle w CSS**, żeby brak drugiej
    linii nie łamał siatki: `grid-auto-rows: 1fr` na kontenerze plus `min-height` na karcie.
    **Wybierz drogę CSS-ową i pokaż w makiecie** — dopisywanie sztucznych etykiet do 6 marek to
    treść tworzona pod layout, a nie pod czytelnika.
12. **Telefon (79,6 % ruchu):** sprawdź, czy przy 360 px kafle nadal mieszczą dwie kolumny po zmianach
    i czy `Dongfeng Fengshen` (najdłuższa nazwa) nie rozpycha rzędu.

### C. SEO (~1,5 h)

13. **Title.** Dziś: `Marki chińskich samochodów — pełna lista | Prima-Auto`. Fraza z największym
    zasobem to **`marka chińskich aut`** (222 imp, poz. 8,3). Nowy title ma zawierać **liczbę marek**
    i wyrażenie z „chińskich aut" — liczba w tytule jest tym, co przy zapytaniach „ile jest…"
    realnie zbiera kliknięcie. Focus keyword w RankMath przestaw z `marki samochodów z Chin`
    na `marka chińskich aut`.
14. **Meta description.** Dziś mówi „50+ marek" — a mamy **51 w siatce, 60 termów z ofertami**.
    Wstaw konkretną liczbę i konkretny licznik ofert; obie liczby są dynamiczne w szablonie
    (`$total_marek`, `$total_listings`), więc description **zbuduj filtrem**
    `rank_math/frontend/description` z transientem — wzorzec gotowy w T-196 (homepage ma dokładnie
    ten sam problem: statyczne „1841" w snippecie).
15. **Answer-first pod klaster „ile jest marek".** Pod H1, w pierwszym akapicie, ma paść zdanie,
    które **wprost odpowiada liczbą** — dziś podhero mówi „Pełen katalog 51 marek", co jest blisko,
    ale odpowiedzią na „ile jest chińskich marek samochodów" jest zdanie o **rynku chińskim**
    (ponad sto marek, kilkanaście koncernów), a dopiero potem o naszej ofercie. Ta odpowiedź już
    istnieje — siedzi w FAQ w zwiniętym `<details>` na dole. **Przenieś ją na górę jako widoczny
    akapit** i zostaw w FAQ (dublowanie w `FAQPage` jest OK, snippet czyta widoczny tekst).
16. **FAQ rozwinięte przy pierwszym pytaniu** — `<details open>` na pierwszym elemencie. Google
    czyta zwinięte `details`, ale użytkownik mobilny nie wie, że tam cokolwiek jest.
17. **`ItemList` wzbogacić o liczbę ofert** — dziś `ListItem` ma tylko `name` i `url`. Dodaj
    `description` z liczbą dostępnych aut. Kolejność elementów ma odpowiadać **nowej** kolejności
    kafli (posortowanej), nie starej.
18. Po wdrożeniu: **zgłoś `/marki/` do indeksacji** przez `~/bin/index-submit --project primaauto`
    (1 URL, budżet ad-hoc; sprawdź `--status` przed).

---

## §4. Strefy kruche i czego nie ruszać

- 🔴 **`.aa-brand-card` / `.aa-brand-grid` są używane także na hubach** — `assets/css/hub.css:253`
  („Sekcja pigułek modeli (używa tego samego `.aa-brand-card` co `/marki/`)"). Każda zmiana klasy
  bazowej rozjeżdża huby marek i modeli. **Nowe warianty rób wyłącznie jako modyfikatory**
  (`.aa-brand-card--mini`, `.aa-brand-grid--lead`) i sprawdź jeden hub przed i po
  ([[feedback_additive_not_fragile_zone]]).
- 🔴 **Aktywny motyw to `primaauto2026`.** `themes/asiaauto/` ma bliźniacze pliki i jest martwy.
  Przed pierwszą edycją: `wp theme list --status=active`.
- 🔴 **Nie ruszaj nazw termów `make`** (D5) ani klasyfikacji `_asiaauto_brand_origin` bez pomiaru —
  `global_jv` decyduje o tym, kto w ogóle jest na stronie.
- 🟡 `post_content` strony 263572 edytuj **jednym** świadomym podejściem (usunięcie bloku galerii
  + akapitu pod nim + ewentualny akapit answer-first). Backup treści do pliku przed zapisem:
  `wp post get 263572 --field=content > docs/backup/263572-2026-09-08.html`.
- 🟡 Zdjęcia wgrywaj **poza** `uploads/asiaauto/YYYY/MM/` — to drzewo należy do importera i podlega
  sprzątaniu przy rotacji.

---

## §5. Kolejność wykonania

1. Backup: treść posta 263572 + `.bak` na `page-marki.php` i `hub.css` (z datą).
2. **Makieta HTML do akceptu** — jeden plik na `auratest`, pokazujący: nową galerię (na docelowych
   zdjęciach), nową kolejność kafli, wariant `--mini` dla ogona i wyrównanie kafli bez drugiej linii.
   Wersje **przed i po**, obok siebie ([[feedback_mockupy_przed_po]]). **Czekaj na „ok" Janka.**
3. Zdjęcia: pobranie z Dysku → kadr → WebP → nazwy → upload.
4. `page-marki.php`: galeria w szablonie, sortowanie, próg, sekcja ogona, schema.
5. `hub.css`: modyfikatory + wyrównanie siatki, sprawdzenie hubów.
6. `post_content`: usunięcie starej galerii, akapit answer-first.
7. RankMath: title, focus keyword, `og:image`, filtr na description.
8. Testy (§6), bump motywu do 1.3.0, `docs/THEME-VERSIONS.md`, commit.
9. Zgłoszenie do indeksacji + zapis baseline pomiaru (data, kliki, impresje, CTR, pozycja z §2)
   do `docs/seo/` — bez baseline za miesiąc nie udowodnimy efektu.

---

## §6. Testy przed ogłoszeniem „zrobione"

Bez tych dowodów **nie pisz „działa"** ([[feedback_verify_status_and_activity_before_work]]):

- [ ] `php -l page-marki.php` czysto.
- [ ] Pomiar w przeglądarce: **zero obrazów z `naturalWidth === 0`** na `/marki/`
      (skrypt puppeteer z rozpoznania 08.09 — `~/projekty/auranet/scripts` + `CHROME_BIN` z playwright cache).
- [ ] Wszystkie linki galerii oddają **200**, nie 301 (huby, nie oferty).
- [ ] `curl` na `/marki/`: `og:image` wskazuje na istniejący plik (`test -f` na ścieżce).
- [ ] Kafle: pierwsza marka w każdej sekcji ma najwięcej ofert; Aistaland/BAW/Wuling nie stoją
      przed Geely ani Zeekr.
- [ ] Zrzuty 390 px i 1366 px po zmianie, zestawione z tymi z 08.09
      (`auratest:primaauto-marki-stan-*-2026-09-08.png`).
- [ ] Hub marki (np. `/samochody/byd/`) i hub modelu (np. `/samochody/byd/leopard-5/`) wyglądają
      **identycznie jak przed** — dowód, że modyfikatory CSS nie ruszyły klasy bazowej.
- [ ] Schema: `ItemList` i `FAQPage` walidują się (Rich Results Test), liczba `ListItem` = liczba kafli.
- [ ] Waga strony nie urosła o więcej niż ~600 KB (9 kadrów WebP, `loading="lazy"` poza pierwszym).

---

## §7. Prompt do wklejenia w nowym wątku

```
Robimy T-255 — tuning strony /marki/ na primaauto.com.pl. Pakiet wykonawczy:
docs/sesje/2026-09-08-PROMPT-T255-tuning-marki.md — przeczytaj go w całości przed pierwszą edycją.

Decyzje zamknięte, nie otwieraj ich ponownie: bez logotypów marek (znaki towarowe);
galeria z naszych sesji z Dysku Google (PrimaAuto/sesje/), nie z rotacji ofert; kadry
linkują na huby modeli.

Zacznij od kroku 2 z §5 — makieta przed/po na auratest do akceptu. Nie wdrażaj niczego
na produkcję, zanim Janek nie napisze „ok" na makietę.

Aktywny motyw to primaauto2026 (nie asiaauto). Klasa .aa-brand-card jest współdzielona
z hubami — zmiany tylko przez modyfikatory.
```

---

## §8. Co świadomie zostaje poza zakresem

- **Logotypy marek** (D1) — zamknięte.
- **Scalanie sub-marek** (Great Wall/GWM, Dongfeng Fengshen/Yipai, Jetour Shanhai) — dotyka nazw
  termów, czyli tytułów ofert. Osobna zgoda, osobny pomiar.
- **Braki opisów** Stelato (64 oferty) i Aistaland — `term->description` nie renderuje się na froncie,
  więc na `/marki/` to nic nie zmienia. Do zrobienia przy hubach premierowych
  ([[project_huby_premierowe_i_stelato_2026_09_07]]), nie tutaj.
- **T-225e** (wymiana zdjęć na własne) — **domykamy go tym zadaniem**, bo dokładnie o to chodziło.
  Odnotuj to w QUEUE przy zamykaniu T-255.
- **Wideo z sesji** (Denza N9 — 2 pliki mp4) — materiał istnieje, ale wideo na `/marki/` to inny
  ruch niż tuning galerii. Kandydat do T-201 (kanał YouTube), nie tutaj.
