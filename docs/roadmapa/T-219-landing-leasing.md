# T-219 — Landing „Leasing samochodu z Chin" + pokrycie leasingu na hubach

> Utworzony: 2026-07-31
> Status: **spec gotowy do budowy**
> Powiązane: T-217 (wzorzec umowy leasingowej — **LIVE od 2026-07-30**, v0.34.14),
> T-113 (ścieżka finansowania w kreatorze), T-189 (kalkulator raty), T-221 (pakiet prawny),
> T-214 (dział wiedzy — autolinker i llms-full)

---

## 1. Po co to robimy

**Nie dla wolumenu w SERP-ie.** Fraza „leasing samochodu z chin" nie ma dziś mierzalnego
wolumenu (badanie 2026-07-17) i to się nie zmieni z dnia na dzień. Uzasadnienie jest inne
i trzyma się trzech nóg:

> **Korekta 2026-07-31 (GSC, 90 dni):** teza „popytu nie ma" jest prawdziwa **tylko dla frazy
> ogólnej**. Na poziomie modelu popyt ISTNIEJE i już generuje impresje bez żadnej treści
> o leasingu po naszej stronie: `zeekr 9x leasing` 24 imp, `geely monjaro leasing` 10,
> `zeekr 8x leasing` 3, `xiaomi yu7 leasing` 3, `leapmotor c16 leasing` 1 imp / **1 klik**.
> Razem 24 frazy / 69 imp. **Konsekwencja: część B (blok na hubach) ma mocniejsze uzasadnienie
> niż sam landing** — pierwszy rzut kierować na huby, które już te impresje zbierają.

1. **Pokrycie encji dla modeli językowych.** Gdy ktoś pyta ChatGPT/Perplexity „czy chińskie
   auto da się wziąć w leasing", „leasing Zeekr 9X" — musi istnieć zaindeksowany, cytowalny
   dokument, który mówi *tak, oto jak*. Dziś takiego dokumentu nie ma. Mamy już do tego
   infrastrukturę (`llms.txt` / `llms-full.txt`, FAQPage na hubach, autolinker działu wiedzy)
   — landing ją zasila, a blok na hubach rozprowadza fakt na 317 stron modeli.
2. **Konwersja.** Klient na hubie widzi cenę 170 000 zł i odbija. „Możesz wziąć to auto
   w leasing" w tym miejscu zdejmuje barierę — dokładnie tę samą rolę pełni dziś pasek
   zaufania na ofertach (claim „Możliwy leasing", v0.33.31), tylko bez strony, do której
   mógłby prowadzić.
3. **Nisza dopiero powstaje.** Popyt na tę frazę będzie — pytanie kiedy, nie czy. Rankowanie
   dziś jest tanie, bo nie ma konkurencji. Kto ma stronę, gdy popyt przychodzi, ten go zbiera.

**Czego ten task NIE robi:** nie dotyka `/finansowanie/` (zostaje jak jest — link do landingu
dorzucimy po publikacji), nie robi kalkulatora raty (T-189), nie zmienia kreatora `/zamow/`
(T-113), nie rusza generatora umów (T-217 zamknięty).

---

## 2. Stan faktyczny — co już mamy

| Element | Stan | Gdzie |
|---|---|---|
| Wzorzec umowy leasingowej | **LIVE**, §1–§16 + Zał. 1, numeracja `UL/2026/NNNN` | `class-asiaauto-contract.php::renderLeasingHTML()` |
| Treść prawna | z podpisanego egzemplarza **#072426-1** | j.w., linie 1429–1600 |
| Konfiguracja | rachunek leasingowy, procent depozytu (10%) | Ustawienia zamówień → „Umowa leasingowa" |
| Pole „Finansujący" | edytowalne per zamówienie | metabox „Dane umowy" |
| `/finansowanie/` | publish, ID 196330, parent `/informacje/` | RankMath title „Finansowanie i leasing auta z Chin" |
| Pasek zaufania na ofertach | claim „Możliwy leasing" bez linku | v0.33.31 |
| Wzorzec strony contentowej | `/depozyt/` (ID 390646) — jeden blok `wp:html`, klasy hubów | 11,5 KB treści |
| Huby serie z ofertami | **317** | `wp7j_term_taxonomy`, `taxonomy='serie', count>0` |

### 2.1 Kto dobiera Finansującego — to Prima-Auto, nie klient

Wzorzec dopuszcza oba warianty (§3 ust. 1: „podmiot wskazany przez Zleceniodawcę **albo
uzgodniony przez Strony**"), ale **praktyka jest jedna: leasingodawcę proponuje Ruslan** —
firmę, z którą już pracuje albo będzie pracował. Pole „Finansujący" w panelu odzwierciedla
tę propozycję, nie wybór klienta.

**Konsekwencja dla treści:** narracja to *załatwiamy finansowanie* — mamy leasingodawcę,
który finansuje auta sprowadzane z Chin, i dlatego to zwykle się udaje. **Nie** „przyjdź
ze swoim leasingiem". Klient, który ma własnego leasingodawcę, oczywiście może go użyć,
ale to wariant poboczny — jedno zdanie, nie oś strony.

Ryzyko odmowy zostaje w treści, ale sprowadzone do jednej realnej przyczyny:
**zdolność leasingowa po stronie klienta**. Nie robimy z tego sekcji ostrzegawczej.

### 2.2 Zasada nadrzędna dla treści

**Landing nie może obiecać niczego, czego nie ma w umowie.** To dokładnie ta lekcja, którą
płacimy na `/finansowanie/` — obiecuje „30% zaliczki + 70% na 10 dni przed portem", model
nieistniejący w żadnej z dwóch umów (wyłapane w T-221). Każde zdanie o pieniądzach,
terminach i odpowiedzialności ma mieć pokrycie w §1–§16.

---

## 3. Zakres

### A. Strona `/leasing-samochodu-z-chin/`

Top-level page (nie pod `/informacje/`), wzorzec techniczny = `/depozyt/`: jeden blok
`wp:html` w `aa-hub__body`, reużycie klas `aa-hub__usp` / `aa-hub__usp-block`, zero nowego CSS.

Struktura sekcji (H1 + H2), źródło treści w nawiasach:

1. **H1: Leasing samochodu z Chin** — lead: da się, **my to organizujemy** — mamy leasingodawcę,
   który finansuje auta sprowadzane z Chin. (§1 ust. 1–3)
2. **H2: Jak to działa** — dwukolumnowy USP „Co robimy my / Co robisz Ty" (§6, §7).
   Po naszej stronie m.in. **dobór i przedstawienie Finansującego** oraz przeprowadzenie
   przez wniosek; po stronie klienta: dokumenty i podpisy.
3. **H2: Krok po kroku — od wyboru auta do odbioru** — lista numerowana:
   wybór auta → depozyt zabezpieczający → wniosek leasingowy → decyzja Finansującego →
   umowa leasingu → **umowa trójstronna** (Prima-Auto ↔ Finansujący ↔ Ty) → zakup i import →
   zapłata ceny przez Finansującego → wydanie auta. (§3 ust. 3, §8 ust. 1)
4. **H2: Ile to kosztuje na starcie** — depozyt zabezpieczający **10% wartości auta**,
   zwracany po zapłacie ceny przez Finansującego **albo** zaliczany na opłatę wstępną leasingu;
   3 dni robocze zwrotu bez kosztów / 7 dni po potrąceniu, gdy transakcja nie dojdzie do skutku
   nie z winy klienta. (§4 ust. 3–5)
   ⚠️ **Nie mylić z depozytem zwrotnym z panelu** (stała kwota, T-121) — to inny mechanizm.
5. **H2: Kto może wziąć leasing** — leasing operacyjny = firma; korzyści podatkowe (rata jako
   koszt, VAT) opisane ostrożnie, bez konkretów podatkowych, których nie doradzamy.
6. **H2: Kiedy leasing może się nie udać** — **krótko, jeden akapit, bez straszenia.**
   Bariera „leasingodawca nie chce auta spoza UE" u nas nie występuje, bo pracujemy
   z takim, który te auta finansuje. Zostaje jedno realne ryzyko: **zdolność leasingowa
   po stronie klienta**. Wtedy depozyt wraca wg §4 ust. 3–4. Sekcja ma zamykać obiekcję,
   nie ją budować.
7. **H2: Ile trwa** — orientacyjny termin przygotowania do wydania liczony od zakupu auta
   w Chinach (§8 ust. 3 — wartość z configu, nie wpisywać na sztywno).
8. **H2: Najczęstsze pytania** — FAQ, patrz §5 niżej.
9. **Formularz** (sekcja C) + CTA do katalogu.

### B. Blok na hubach serie (317 stron)

Nowe H2 w `themes/primaauto2026/taxonomy-serie.php`, **między sekcją FAQ a `aa-hub__bottom-cta`**
(linie ~232–246). Marek **nie ruszamy** (decyzja: tylko serie).

- Nagłówek: `{full_title} leasing` (np. „BYD Leopard 7 leasing") — **szyk potwierdzony GSC
  2026-07-31** (`tmp/gsc-leasing-szyk-2026-07-31.py`, 90 dni): frazy `{model} leasing` = 16 fraz
  / **54 imp**, frazy `leasing {model}` = 3 frazy / 4 imp. Trzynaście do jednego. Spójne
  z decyzją T-203 (marka i model pierwsze, kwalifikator na końcu). Poprzedni zapis
  („Leasing {full_title}") był kalką z nagłówków hubu, bez pokrycia w danych — **wycofany**.
- 2–3 zdania **zmienne per model** — nazwa modelu + widełki cenowe z hubu, jeśli dostępne;
  **nie identyczny boilerplate ×317** (inaczej to 317 kopii jednego akapitu = ryzyko thin content)
- Link: „Jak działa leasing samochodu z Chin →" → `/leasing-samochodu-z-chin/`
- Wpięcie w autolinker działu wiedzy: hasło „leasing" ma prowadzić na landing

⚠️ `taxonomy-serie.php` = **strefa ZAWSZE PYTAJ** ([[feedback_no_edit_homepage_without_ok]]).
Przed edycją: `wp theme list --status=active` (aktywny `primaauto2026`, **nie** `asiaauto`)
+ `.bak` z datą.

### C. Formularz „Zapytaj o leasing"

**Uwaga do estymaty:** w serwisie **nie ma dziś żadnego komponentu formularza**. Aktywne
pluginy to Complianz, asiaauto-sync, RankMath (×2) — brak CF7/WPForms. `/kontakt/` to
`mailto:` + telefon + WhatsApp, a jedyny prawdziwy formularz to kreator `/zamow/`
(`asiaauto_order_wizard` → CPT `asiaauto_order`). Formularz trzeba zbudować od zera.

Zakres minimalny (nowa klasa, np. `AsiaAuto_Lead_Form`, shortcode `[asiaauto_lead_form]`):

- Pola: imię, telefon, e-mail, firma/NIP (opcjonalnie), interesujący model (opcjonalnie —
  prefill z parametru URL, gdy wejście z hubu), wiadomość
- Zgoda RODO + honeypot/nonce (bez zewnętrznej captchy na start)
- Zapis: CPT `asiaauto_lead` **albo** meta na istniejącym mechanizmie — do rozstrzygnięcia
  na etapie budowy; wymóg twardy: **lead ma być mierzalny w GA4** (event) i widoczny
  w adminie, nie tylko mailem
- Powiadomienie: mail do Ruslana przez istniejący kanał powiadomień zamówień
  (`admin_notification_email` z configu). **Nie** przez `send-to-jan` — to kanał do Janka,
  nie systemowy
- Komponent ma być **reużywalny** — pod niego wejdzie potem kalkulator raty (T-189)
  i ewentualnie alerty (T-188)

### D. Linkowanie

| Miejsce | Co | Kiedy |
|---|---|---|
| Huby serie (317) | H2 + link (sekcja B) | w tym tasku |
| `/finansowanie/` | link „Szczegóły ścieżki leasingowej →" | **po publikacji landingu** |
| Stopka | pozycja w kolumnie informacyjnej | w tym tasku |
| `/informacje/` | pozycja na liście | w tym tasku |
| Pasek zaufania na ofertach | claim „Możliwy leasing" → link | do rozstrzygnięcia (dotyka `class-asiaauto-single.php` = strefa ZAWSZE PYTAJ) |
| `llms.txt` / `llms-full.txt` | wpis + treść | po publikacji, generatorem `build-llms-full.php` |
| Sitemap + Indexing API | zgłoszenie URL | po publikacji |

---

## 4. SEO / AEO

- **Title:** `Leasing samochodu z Chin — jak to działa | Prima-Auto`
- **Slug:** `/leasing-samochodu-z-chin/` (decyzja Janka 2026-07-31)
- **Schema:** `FAQPage` (wzorzec z hubów) + `Service` z `provider` = LocalBusiness Prima-Auto
- **Cytowalność:** każda sekcja odpowiada na jedno pytanie w pierwszym zdaniu (answer-first,
  wzorzec sprawdzony w fali 2 — 14 leadów)
- **Indexing:** zgłoszenie przez wrapper, po publikacji; blok na hubach **nie** wymaga
  reindeksacji 317 stron naraz — huby i tak są w cyklu crawlowania

---

## 5. FAQ — pytania do pokrycia (robocza lista)

1. Czy da się wziąć samochód z Chin w leasing? → tak, i to my organizujemy finansowanie
2. Czy muszę sam znaleźć firmę leasingową? → **nie — proponujemy leasingodawcę, z którym
   pracujemy i który finansuje auta z Chin**; własnego też możesz użyć
3. Czy muszę mieć firmę? → leasing operacyjny tak; dla osób prywatnych osobna ścieżka
4. Ile wpłacam na start? → depozyt zabezpieczający 10%, rozliczany przy finalizacji (§4)
5. Co to jest umowa trójstronna? → Prima-Auto sprzedaje auto Finansującemu, Ty je leasingujesz (§1 ust. 2, §3 ust. 3)
6. Co, jeśli nie dostanę leasingu? → realna przyczyna to zdolność leasingowa; depozyt wraca wg §4 ust. 3–4
7. Kiedy dostanę auto? → po uruchomieniu finansowania i upoważnieniu do odbioru (§8 ust. 1)
8. Czy mogę wziąć w leasing konkretny model, np. Zeekr 9X? → tak, każde auto z oferty

---

## 6. Ryzyka i strefy kruche

| Ryzyko | Mitygacja |
|---|---|
| Edycja `taxonomy-serie.php` (317 stron naraz) | `.bak` z datą, `php -l`, podgląd na jednym hubie przed publikacją, sprawdzenie aktywnego theme'u |
| Pomyłka `themes/asiaauto` vs `primaauto2026` | `wp theme list --status=active` przed każdą edycją (pomyłka zaliczona 2026-07-30) |
| Boilerplate ×317 = thin content | treść zmienna per model (nazwa + cena z hubu) |
| Rozjazd treści z umową | każde zdanie o pieniądzach/terminach mapowane na paragraf; przegląd przed publikacją |
| Formularz = nowy kod przyjmujący dane osobowe | nonce, sanityzacja, zgoda RODO, Complianz; zero danych w logach |
| Nadmierne obietnice podatkowe | nie doradzamy podatkowo — formuły ogólne |

---

## 7. Estymata

| Część | Godziny |
|---|---|
| A — strona (treść + skład + schema) | 4–5 |
| B — blok na hubach + autolinker | 2–3 |
| C — formularz leadowy od zera (backend + admin + GA4 + RODO) | 5–7 |
| D — linkowanie, llms, indexing | 1–2 |
| **Razem** | **12–17 h** |

Kosztorys dla Ruslana mówi dziś **3–4 h realnie** — to była wycena samej strony, sprzed
ustalenia, że dochodzi blok na 317 hubach i formularz budowany od zera. **Do aktualizacji
w `docs/kosztorys/dane/postep.json` przy najbliższym przebudowaniu strony postępu.**

---

## 8. Decyzje zapadłe

| # | Decyzja | Kiedy |
|---|---|---|
| D1 | `/finansowanie/` **zostaje**; nowa strona jest osobna, link z `/finansowanie/` dorzucamy po publikacji | 2026-07-31 |
| D2 | Slug `/leasing-samochodu-z-chin/` | 2026-07-31 |
| D3 | Blok na hubach **tylko serie** (317), marki pomijamy | 2026-07-31 |
| D4 | Osobny formularz leadowy (nie mailto, nie wizard) — pod niego wejdzie kiedyś kalkulator | 2026-07-31 |
| D5 | Brak wolumenu frazy **nie jest** kontrargumentem — cel to pokrycie encji dla LLM-ów + konwersja + zajęcie niszy przed popytem | 2026-07-31 |
| D6 | **Leasingodawcę proponuje Prima-Auto** (firma, z którą Ruslan pracuje / będzie pracował), nie klient. Narracja: „załatwiamy finansowanie", nie „przyjdź ze swoim". Odmowa = w praktyce tylko brak zdolności leasingowej klienta | 2026-07-31 |

## 9. Do rozstrzygnięcia przed budową

0. **Czy nazywamy leasingodawcę z imienia na stronie?** Wymaga potwierdzenia od Ruslana:
   z kim konkretnie pracuje i czy wolno go publicznie wskazać. Wariant bezpieczny na start:
   „leasingodawca, z którym współpracujemy" bez nazwy — treść nie wymaga przeróbki, gdy
   nazwa dojdzie później (albo gdy partner się zmieni).
1. **Storage leada** — CPT `asiaauto_lead` czy lżejszy mechanizm? (wpływa na admin i raportowanie)
2. **Pasek zaufania na ofertach** — podlinkować „Możliwy leasing" teraz czy osobno?
   (dotyka `class-asiaauto-single.php` = strefa ZAWSZE PYTAJ)
3. **Osoby prywatne** — landing wspomina, że leasing operacyjny to firmy. Czy opisujemy
   ścieżkę dla osób prywatnych (kredyt/najem), czy zostawiamy na `/finansowanie/`?

---

## 10. Stan wykonania (2026-07-31, wieczór)

### Zrobione

| Element | Szczegóły |
|---|---|
| **A — strona** | `/leasing-samochodu-z-chin/` ID **398850**, publish. Treść po przeglądzie Ruslana (mail 31.07). Schema `FAQPage` (12 pytań) + `Service` z `provider` → `#organization`. |
| **Sitemapa** | Strona **nie była** w `page-sitemap.xml` — ten sam korzeń co T-192 (cache RankMath). `wp rankmath sitemap generate` → 19 → 20 stron, landing w środku. |
| **Stopka** | Pozycja „Leasing samochodu z Chin" w fallbacku nawigacji, `footer.php` (menu-2 niepodpięte, więc renderuje się fallback). Backup `footer.php.bak-2026-07-31-leasing`, `php -l` czysty. |
| **`/finansowanie/`** | Link „Szczegóły ścieżki leasingowej — jak to działa krok po kroku →" pod akapitem o leasingu operacyjnym. Backup treści w `~/backups/primaauto/finansowanie-196330-2026-07-31.html`. |
| **`llms.txt` / `llms-full.txt`** | Pozycja w obu + dedykowana sekcja „Leasing samochodu sprowadzanego z Chin" w `llms-full` (7 faktów answer-first). Zmiana w generatorach `scripts/build-llms.php` i `scripts/build-llms-full.php`, więc przeżyje regenerację. |
| **Indexing API** | 1 URL zgłoszony przez `~/bin/index-submit` (ad-hoc 1/100). |

### Naprawiony defekt z publikacji

Przy pierwszej publikacji strona została wgrana przez WP-CLI **bez `--user`** → WordPress
potraktował zapis jako pozbawiony `unfiltered_html` i **wyciął tagi `<style>`
i `<script type="application/ld+json">`**, zostawiając ich zawartość jako widoczny tekst.
Na dole strony wisiało **4479 znaków gołego CSS i JSON-a**, a schema FAQPage nie działała
w ogóle. Naprawione zapisem z `--user=1`. **Reguła na przyszłość: każdy `wp post update`
z treścią zawierającą `<script>`/`<style>` musi mieć `--user=1`.**

### Nieaktualne w pierwotnym zakresie

- **`/informacje/` — pozycja na liście: ODPADA.** Strona ma 301 na home od 17.07
  (antykanibalizacja), nie ma tam listy do uzupełnienia.

### Otwarte

| Element | Stan |
|---|---|
| **B — blok na 317 hubach serie** | Nie ruszone. Nagłówek: **`{full_title} leasing`** (patrz korekta w §3A). Priorytet wg GSC: Zeekr 9X (24 imp), Geely Monjaro (10), Zeekr 8X i Xiaomi YU7 (po 3). |
| **C — formularz leadowy** | Nie ruszone. **Dług:** CTA na landingu prowadzi dziś do `/kontakt/`, czyli do `mailto:` — spec zakładał formularz osadzony na stronie (§3A pkt 9). Landing bez formularza = landing bez mierzalnej konwersji. |
| **Pasek zaufania na ofertach** | Nie ruszone — dotyka `class-asiaauto-single.php` (ZAWSZE PYTAJ). |
| **Rozjazd depozytu z §4 umowy** | Wydzielony do **T-223**, czeka na odpowiedź Ruslana (punkt 8 w `docs/biznes/2026-07-27-punkty-do-weryfikacji-ruslan.md`). |

## 11. Decyzje zapadłe — ciąg dalszy

| # | Decyzja | Kiedy |
|---|---|---|
| D7 | Szyk nagłówka na hubach: **`{model} leasing`**, nie `Leasing {model}` — GSC 90 dni: 54 imp vs 4 imp. Spójne z T-203 | 2026-07-31 |
| D8 | Poprawki Ruslana wchodzą **1:1**, łącznie z „pracujemy z najlepszymi" (zamiast konkretu o leasingodawcy finansującym auta spoza UE). Rozjazd z umową idzie do T-223, nie blokuje publikacji | 2026-07-31 |
| D9 | Leasingodawcy **nie nazywamy z nazwy** — Ruslan sam wybrał sformułowanie bez nazwy. Zamyka pytanie 9.0 | 2026-07-31 |
