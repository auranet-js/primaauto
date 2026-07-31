# Audyt dostępności primaauto.com.pl — WCAG 2.2 poziom AA

> Data: 2026-07-31
> Zakres: 12 typów stron, warstwa automatyczna (PageSpeed Insights / Lighthouse-axe) + warstwa ręczna (axe-core 4.10.2 przez Chrome MCP, inspekcja DOM/CSS, testy klawiaturowe, przegląd kodu theme'u i pluginu)
> Stan kodu: theme `primaauto2026`, plugin `asiaauto-sync` v0.34.14
> Powód: EAA obowiązuje od 28.06.2025; primaauto to publicznie dostępny serwis handlowy. Audyt ma też służyć jako wzorzec metodyczny dla audytów płatnych.

---

## 1. Podsumowanie

Znaleziono **17 rodzajów naruszeń** w ok. **290 wystąpieniach** wykrywalnych na przebadanej próbce stron.

| Poziom | Rodzajów naruszeń | Wystąpień |
|---|---|---|
| **A** (blokujące) | 9 | ~35 |
| **AA** | 8 | ~255 |
| Razem | **17** | **~290** |

**Blokujących korzystanie: 4.** Są to naruszenia, które realnie odcinają użytkownika klawiatury albo czytnika ekranu od funkcji serwisu, a nie tylko utrudniają odbiór:

1. **Skip link nie działa** — użytkownik klawiatury nie może ominąć nawigacji (dotyczy każdej strony)
2. **Karta auta nie ma `h1` na desktopie** — brak punktu wejścia w treść dla czytnika (dotyczy ~2752 stron ofert)
3. **Komunikaty błędów w kreatorze zamówienia nie są ogłaszane ani powiązane z polami** — użytkownik czytnika nie dowiaduje się, że i dlaczego zamówienie nie przeszło
4. **Paginacja listingu gubi focus** — po zmianie strony focus spada na `<body>`, przeglądanie oferty klawiaturą wymaga przetabowania całej strony od nowa

**Uwaga o skali liczby wystąpień.** ~253 z ~290 wystąpień to kontrast kolorów, a liczba ta skaluje się z liczbą wyświetlonych rekordów: sam listing generuje 72 wystąpienia na 24 kafle. Naprawa dotyczy jednak nie 253 miejsc, tylko **pięciu tokenów kolorystycznych w CSS**. Liczba wystąpień opisuje zasięg problemu, nie pracochłonność.

**Czego NIE ma w serwisie, mimo że było w zakresie audytu:** formularza kontaktowego (strona `/kontakt/` to dane teleadresowe + `tel:`/`mailto:`/WhatsApp, bez `<form>`) oraz formularza zapytania o pojazd innego niż kreator zamówienia. To nie jest brak dostępności — to korekta zakresu.

---

## 2. Tabela naruszeń

### Poziom A

| # | Kryterium WCAG | Czego dotyczy | Gdzie (szablon, selektor) | Wystąpień | Jak naprawić | Czas |
|---|---|---|---|---|---|---|
| A1 | **2.4.1 Bypass Blocks** | Skip link przewija stronę, ale nie przenosi focusu — `activeElement` zostaje `BODY`, kolejny Tab wraca na początek | `themes/primaauto2026/header.php:18` (`<a class="skip-link" href="#pa-content">`), cel `#pa-content` to `<div>` bez `tabindex` | 1 × każda strona | Dodać `tabindex="-1"` do `#pa-content`, albo przekierować skip link na `<main class="pa-main">` i tam dodać `tabindex="-1"` | 15 min |
| A2 | **2.4.4 + 4.1.2** | Logo w stopce to link bez żadnej nazwy dostępnej (brak tekstu, `aria-label`, `title`) | `themes/primaauto2026/footer.php`, `.pa-footer__logo > a` | 1 × każda strona | `aria-label="Prima-Auto strona główna"` — dokładnie tak jak już jest w `header.php:25` i `:66` | 5 min |
| A3 | **4.1.2 Name, Role, Value** | Trzy `<select>` bez etykiety | `#aa-home-make`, `#aa-home-model` (strona główna); `.aa-sort__select` (listing) | 3 | `<label class="screen-reader-text">` lub `aria-label` („Marka", „Model", „Sortowanie") | 30 min |
| A4 | **1.3.1 Info and Relationships** | Karta auta nie ma `h1` na desktopie. Tytuł renderuje się jako `<div class="aa-single__title">`, jedyny `<h1>` siedzi w `.aa-single__sticky-head--mobile`, ukrytym `display:none` powyżej 768px | `plugins/asiaauto-sync/includes/class-asiaauto-single.php`, CSS `assets/css/asiaauto-single.css:204` | ~2752 strony ofert | Zamienić `<div>` na `<h1>` w wariancie `--desktop`, a w wariancie `--mobile` zejść do `<p>`/`<div>` — tak, by na obu szerokościach istniał dokładnie jeden `h1` | 1–2 h |
| A5 | **1.3.1** | Strona 404 nie ma `h1` — hierarchia startuje od `H2` | `themes/primaauto2026/404.php` | 1 | Podnieść nagłówek komunikatu do `h1` | 15 min |
| A6 | **1.3.1** | Dwie tabele danych bez komórek nagłówkowych — 0 × `<th>`, brak `<caption>` | `/kontakt/`, `table.aa-contact__hours-table` (godziny), `table.aa-contact__data-table` (dane firmy) | 2 | Zamienić pierwszą kolumnę/wiersz na `<th scope="row">`/`<th scope="col">`, dodać `<caption>` | 30 min |
| A7 | **1.3.1 + 3.3.2 Labels or Instructions** | Pola filtrów listingu bez etykiet — nazwę niesie wyłącznie `placeholder`, który znika po wpisaniu treści | `/samochody/`: `.aa-price-inputs__input` × 2 (`placeholder="95 000"` / `"1 070 000"`), `.aa-search__input` (`placeholder="Szukaj marki, modelu..."`) | 3 | Dodać `<label>` (może być wizualnie ukryta) — „Cena od", „Cena do", „Szukaj marki lub modelu" | 1 h |
| A8 | **3.3.1 Error Identification** | Walidacja kreatora dopisuje `<span class="aa-wiz__field-error">Pole wymagane</span>` obok pola, ale bez `aria-describedby`, bez `aria-invalid="true"` i bez przeniesienia focusu na pierwsze błędne pole. Czytnik nie sygnalizuje błędu; focus zostaje na przycisku wysyłki | `plugins/asiaauto-sync/assets/js/asiaauto-order-wizard.js:61-93` (`validateRequired`) oraz `:44-47` (błąd zbiorczy) | 6 pól × 2 formularze | Nadawać `id` komunikatowi, wiązać `aria-describedby`, ustawiać `aria-invalid="true"`, przenosić focus na pierwsze błędne pole; box zbiorczy jako `role="alert"` | 2 h |
| A9 | **2.4.3 Focus Order** | Paginacja listingu działa przez AJAX + `pushState`. Po kliknięciu strony przycisk jest podmieniany, focus spada na `<body>` — kolejny Tab startuje od początku dokumentu | `/samochody/`, `.aa-pagination__btn`; potwierdzone: URL `?strona=2`, `aria-current` przechodzi na „2", kontekst JS przeżywa | 1 (każde przejście strony) | Po przerysowaniu ustawić focus na nagłówku listy wyników albo na przycisku odpowiadającym nowej stronie | 1 h |

### Poziom AA

| # | Kryterium WCAG | Czego dotyczy | Gdzie (szablon, selektor) | Wystąpień | Jak naprawić | Czas |
|---|---|---|---|---|---|---|
| AA1 | **1.4.3 Contrast (Minimum)** | Dziesięć par kolorów poniżej 4,5:1 — szczegóły w sekcji 3 | globalnie, 22 klasy `aa-*` + stopka; **125 hardkodów hex w ~20 plikach**, w tym 65 w PHP | ~253 | Rozdzielić `#718096` na dwa tokeny (jasne/ciemne tło) i przejść 125 wystąpień — patrz sekcja 3 | 3–4 h |
| AA2 | **1.4.11 Non-text Contrast** | Pierścień focusu `:focus-visible { outline: 2px solid var(--c-accent) }`, gdzie `--c-accent` = `#d63031`. Na granatowym tle `#1b2a4a` daje **2,93:1** przy wymaganych 3:1. Na białym 4,85:1 i jasnym 4,49:1 — przechodzi | `themes/primaauto2026/assets/css/base.css`; dotyczy headera, stopki i skip linka | 3 obszary | Osobny kolor obrysu na ciemnych sekcjach (np. `#ffffff` lub `#f5f6f8`), przez zmienną nadpisywaną w `.pa-header` / `.pa-footer` | 30 min |
| AA3 | **4.1.3 Status Messages** | Zmiany treści bez ogłoszenia. Cały serwis ma **zero** `aria-live` / `role="status"` poza banerem cookie: (a) galeria — licznik „1 / 15" zmienia się niemo, (b) listing — filtry, sortowanie i paginacja przerysowują 24 wyniki bez komunikatu, (c) kreator — błędy walidacji i błąd zbiorczy | galeria `class-asiaauto-shortcodes.php`; listing `asiaauto-inventory.js`; kreator `asiaauto-order-wizard.js` | 3 komponenty | Region `role="status" aria-live="polite"` ogłaszający np. „Zdjęcie 2 z 15", „Znaleziono N ofert, strona 2 z 115" | 2–3 h |
| AA4 | **2.5.8 Target Size (Minimum)** | Cele dotykowe poniżej 24×24 px. Uwaga: linki w ciągu tekstu są wyłączone spod tego kryterium (wyjątek „inline") i ich nie liczę | Karta auta: 5 linków (mobile) / 8 (desktop), m.in. `.aa-breadcrumb__link--root`, `--make`, `--serie`; listing: `button.aa-search__clear` **20×20** | 6–9 | Powiększyć obszar klikalny paddingiem do min. 24×24 bez zmiany rozmiaru czcionki | 1 h + decyzja projektowa |
| AA5 | **1.3.1 heading-order** | Przeskoki poziomów: (a) `h2 → h4` w stopce — `h4.pa-footer__heading` („Nawigacja", „Kontakt") na każdej stronie, (b) `h1 → h3` na listingu (tytuły kafli to `h3`), (c) `h1 → h3` na karcie auta (`h3.aa-usp-col__title`) | `footer.php`; `taxonomy-*.php` / listing; `class-asiaauto-single.php` | 3 wzorce × wszystkie strony | Stopka `h4` → `h3`; przejrzeć poziomy nagłówków kafli i kolumn USP | 1 h |
| AA6 | **4.1.2** | Lightbox galerii nie ma `role="dialog"` ani `aria-modal="true"`, `openLightbox()` nie przenosi focusu do środka, brak pułapki focusu — Tab wychodzi poza nakładkę do treści pod spodem. Escape i strzałki **działają** | `class-asiaauto-shortcodes.php:955-1000` | 1 komponent, wszystkie oferty | `role="dialog" aria-modal="true" aria-label`, focus na przycisk zamknięcia przy otwarciu, pułapka focusu, powrót focusu na `.aa-gallery__fullscreen` przy zamknięciu | 2–3 h |
| AA7 | **4.1.2** | Aktywna miniatura galerii oznaczona wyłącznie klasą CSS `--active`, bez `aria-current`/`aria-selected`. Przyciski toggle podmenu mobilnego bez `aria-controls`. `aria-expanded` ustawiane na gołym `<li>` bez roli — nieprawidłowe ARIA | galeria; `themes/primaauto2026/assets/js/nav.js:64-83` | ~15 + 2 | `aria-current="true"` na aktywnej miniaturze; `aria-expanded` tylko na `<button>`, nie na `<li>`; dodać `aria-controls` | 45 min |
| AA8 | **3.1.2 / best practice** | Strona 404 ma `<title>` po angielsku („Page Not Found") przy `lang="pl-PL"` | `themes/primaauto2026/404.php` | 1 | Polski tytuł | 10 min |

---

## 3. Kontrast — rozbicie na pary kolorów

Wszystkie wartości policzone wg WCAG 2.x (współczynnik luminancji względnej), nie oszacowane.

| Para (tekst / tło) | Obecny kontrast | Wymagany | Wystąpień | Gdzie | Proponowany zamiennik | Kontrast po |
|---|---|---|---|---|---|---|
| `#718096` na `#ffffff` | **4,02:1** | 4,5:1 | 108 | tabela specyfikacji, karty ofert, treści | `#647185` | 4,95:1 |
| `#718096` na `#f5f6f8` | **3,71:1** | 4,5:1 | 65 | sekcje na jasnym tle | `#647185` | 4,58:1 |
| `#ffffff` na `#e8ac07` | **2,03:1** | 4,5:1 | 30 | żółty badge na kafelku auta | tekst `#1a1a1a` **albo** tło `#966f04` | 8,57:1 / 4,59:1 |
| `#718096` na `#1b2a4a` | **3,54:1** | 4,5:1 | 22 | **stopka (granat)** | `#9aa8bf` (osobny token!) | 5,91:1 |
| `#d63031` na `#f5f6f8` | **4,49:1** | 4,5:1 | 6 | czerwony akcent | `#d32f30` | 4,60:1 |
| `#fbeaea` na `#d63031` | **4,17:1** | 4,5:1 | 3 | tekst na czerwonym badge | przyciemnić tło lub rozjaśnić tekst | — |
| `#2d3748` na `#d63031` | **2,47:1** | 4,5:1 | 2 | grafit na czerwonym | `#ffffff` na `#d63031` | 4,85:1 |
| `#25d366` na `#f5f6f8` | **1,83:1** | 4,5:1 | 1 | numer WhatsApp na `/kontakt/` | ciemniejsza zieleń, np. `#128c3e` | ~4,9:1 |
| `#ffffff` na `#25d366` | **1,98:1** | 4,5:1 | 2 | przycisk CTA WhatsApp (`.aa-cta__wa`, `.aa-mcta--wa`) | tekst `#0b3d1f` lub ciemniejsze tło marki | — |
| `#ffffff` na `#808080` | **3,95:1** | 4,5:1 | 1 | element wyłączony | `#767676` | 4,54:1 |

### Pułapka przy naprawie kontrastu

**`#718096` nie da się podmienić jednym globalnym search-replace.** Ten sam token występuje na trzech różnych tłach, a proponowany zamiennik `#647185` naprawia dwa z nich i **psuje trzecie**: na granatowej stopce daje 2,87:1, czyli gorzej niż obecne 3,54:1. Ciemniejszy szary na ciemnym tle idzie w złą stronę.

Konieczne są **dwa osobne tokeny**: `--c-muted` (`#647185`) dla jasnych teł i `--c-muted-invert` (`#9aa8bf`) dla stopki i innych ciemnych sekcji.

### Druga pułapka: kolory są zahardkodowane, tokeny są omijane

Theme **ma** system kolorów — `themes/primaauto2026/assets/css/base.css:26-37` definiuje `:root` ze zmiennymi `--c-primary`, `--c-secondary` (`#718096`), `--c-accent`, `--c-amber`, `--c-bg` i pozostałymi. Plugin `asiaauto-sync` tego systemu **nie używa**: wkleja hex na sztywno w blokach `<style>` renderowanych z PHP.

| Kolor | w `.css` | w `.php` | Razem | Plików |
|---|---|---|---|---|
| `#718096` | 12 | **65** | **77** | 17 |
| `#1b2a4a` | 23 | 38 | 61 | 13 |
| `#d63031` | 10 | 15 | 25 | 10 |
| `#e8ac07` | 5 | 9 | 14 | 10 |
| `#25D366` | 3 | 4 | 7 | 5 |
| `#808080` | 0 | 2 | 2 | 1 |

Konsekwencje dla planu naprawy:

1. **To nie jest zmiana „tylko w CSS".** 65 z 77 wystąpień szarego siedzi w PHP, w 13 klasach `class-asiaauto-*`. Wśród nich są pliki spoza warstwy publicznej: `class-asiaauto-order-admin.php`, `class-asiaauto-contract.php` (umowa PDF), `class-asiaauto-login.php`, `class-asiaauto-admin.php`. Zmiana koloru „przy okazji" może dotknąć wyglądu umowy PDF albo panelu admina — obszarów, których ten audyt **nie badał**.
2. **Nie da się tego zrobić przez `sed`.** Każde wystąpienie `#718096` wymaga sprawdzenia, na jakim tle renderuje się dany selektor — biel, `#f5f6f8` czy granat `#1b2a4a` — bo od tego zależy, czy idzie na `#647185`, czy na `#9aa8bf`. Globalna podmiana **wprowadzi regresję w stopce**.
3. **Docelowo warto przejść na zmienne**, żeby następna zmiana palety była jednym edytem zamiast 125. To jednak osobna decyzja refaktorowa, wykraczająca poza naprawę dostępności — zgodnie z zasadą „addytywnie, nie przez kruchy refaktor" nie robię tego mimochodem.

**Uwaga o wielkości liter:** w kodzie hex bywa pisany wielkimi literami (`#25D366`, `#1B2A4A`). Grep bez `-i` daje fałszywe zero. Pierwsze liczenie w tej sesji pokazało `#25d366: 0 wystąpień` właśnie z tego powodu — faktycznie jest ich 7.

**Niewiadoma:** `#fbeaea` (tekst na czerwonym badge, 3 wystąpienia wg axe) **nie występuje** w theme ani w pluginie przy wyszukiwaniu case-insensitive. Źródło tego koloru pozostaje nieustalone — do sprawdzenia przed naprawą.

### Klasy z naruszeniami kontrastu

`aa-tech__label` (41), `aa-card__badge` (24), `aa-card__time` (24), `aa-card__location` (24), `aa-home__usp-desc` (12), `aa-home__make-count` (9), `aa-related__count` (8), `aa-home__section-sub` (6), `aa-home__car-badge` (6), `aa-home__fuel-count` (6), `aa-ks__lbl` (5), `aa-contact__label` (4), `aa-contact__data-label` (4), `aa-home__body-count` (4), `aa-home__stat-label` (3), `aa-home__kb-card-date` (3), `aa-usp-col__title` (3), `aa-contact__hours-day` (3), `aa-home__car-price` (2), `aa-home__car-specs` (2), `aa-stock__cta-link` (2), `aa-wiz__step-label` + `aa-wiz__desc` + etykiety kreatora (13), `pa-footer__bottom-inner > span` i link do auranet.com.pl (2 × każda strona).

---

## 4. Priorytety

### Koszyk 1 — blokujące korzystanie (robimy najpierw)

| Zadanie | Poziom | Czas |
|---|---|---|
| A1 — skip link (`tabindex="-1"`) | A | 15 min |
| A2 — `aria-label` na logo w stopce | A | 5 min |
| A3 — etykiety trzech `<select>` | A | 30 min |
| A4 — `h1` na karcie auta | A | 1–2 h |
| A8 — błędy walidacji kreatora | A | 2 h |
| A9 — focus po paginacji | A | 1 h |
| **Razem** | | **~5–6 h** |

Uzasadnienie kolejności: A1, A2 i A3 to łącznie 50 minut i zdejmują trzy naruszenia poziomu A z **każdej** strony serwisu. Najlepszy stosunek efektu do nakładu w całym audycie. A4 dotyczy najliczniejszego typu strony i jednocześnie strony konwersyjnej. A8 blokuje domknięcie zamówienia, czyli jedyną ścieżkę przychodu.

### Koszyk 2 — naruszenia AA

| Zadanie | Czas |
|---|---|
| AA1 — tokeny kolorystyczne (5 par, 2 nowe zmienne) | 3–4 h |
| AA3 — regiony `aria-live` (galeria, listing, kreator) | 2–3 h |
| AA6 — lightbox jako dialog z pułapką focusu | 2–3 h |
| AA5 — hierarchia nagłówków | 1 h |
| AA2 — kontrast pierścienia focusu na ciemnym tle | 30 min |
| A5, A6, A7 — `h1` na 404, `<th>` w tabelach kontaktu, etykiety filtrów | 1,75 h |
| AA7 — poprawki ARIA (aria-current, aria-controls, `aria-expanded` na `<li>`) | 45 min |
| **Razem** | | **~12–15 h** |

### Koszyk 3 — drobiazgi i best practices

| Zadanie | Czas |
|---|---|
| AA4 — cele dotykowe (wymaga decyzji projektowej) | 1 h + decyzja |
| AA8 — polski `<title>` na 404 | 10 min |
| Widoczny `<caption>` / podsumowania tabel | 30 min |

---

## 5. Plan naprawy wg rodzaju zmiany

### 5a. Warstwa prezentacji (kolory, obrysy, odstępy)

- **AA2** — obrys focusu: nadpisanie koloru obrysu wewnątrz `.pa-header` i `.pa-footer`. Czysto w `base.css`. Ryzyko niskie.
- **AA4** — powiększenie celów dotykowych paddingiem, bez zmiany `font-size`. Ryzyko niskie.
- **AA1** — wymiana kolorów. **Ryzyko średnie, nie niskie**, i wbrew pierwotnemu założeniu **nie jest to zmiana wyłącznie w CSS**: 125 hardkodów hex w ~20 plikach, z czego 65 w PHP (patrz sekcja 3). Kolejność robocza:
  1. Wygenerować listę wszystkich wystąpień z selektorem i plikiem
  2. Sklasyfikować każde po tle (biel / `#f5f6f8` / `#1b2a4a`)
  3. Odłożyć na bok wystąpienia w plikach spoza warstwy publicznej (`class-asiaauto-contract.php`, `class-asiaauto-order-admin.php`, `class-asiaauto-login.php`, `class-asiaauto-admin.php`) — te wymagają osobnego smoke testu, bo umowa PDF i panel admina nie były w zakresie audytu
  4. Ustalić źródło `#fbeaea`
  5. Dopiero wtedy podmiana wsadowa i ponowny przesiew PSI

Zmiany odwracalne, ale zasięg regresji wykracza poza 12 przebadanych typów stron.

### 5b. Szablony i JS

- **A1** — `header.php` (jeden atrybut)
- **A2** — `footer.php` (jeden atrybut)
- **A5, AA8** — `404.php`
- **A3, A7** — szablon strony głównej + listing (etykiety)
- **A6** — szablon `/kontakt/` (`<th scope>`, `<caption>`)
- **AA5** — `footer.php` + szablony listingu i oferty
- **A4** — `class-asiaauto-single.php` + `asiaauto-single.css` — **strefa krucha** (pipeline oferty), wymaga `.bak`, `php -l` i smoke testu wg checklisty deployu
- **A8** — `asiaauto-order-wizard.js` (`validateRequired`)
- **A9, AA3** — `asiaauto-inventory.js`, `asiaauto-order-wizard.js`, galeria w `class-asiaauto-shortcodes.php`
- **AA6, AA7** — galeria w `class-asiaauto-shortcodes.php`, `nav.js`

Ryzyko: średnie przy A4 i AA6 (dotykają renderu oferty i galerii). Reszta punktowa.

### 5c. Wymaga decyzji projektowej (nie ruszam bez Twojej zgody)

1. **Żółty badge `#e8ac07`** — dwie drogi: czarny tekst na obecnym żółtym (8,57:1, zmienia charakter badge'a) albo biały tekst na przyciemnionym `#966f04` (4,59:1, badge przestaje być żółty). To wybór wizerunkowy.
2. **Zieleń WhatsApp `#25d366`** — to kolor marki WhatsApp. Zejście do zgodnego kontrastu oznacza odejście od oficjalnej palety. Alternatywa: zostawić kolor tła, zmienić tekst na bardzo ciemny.
3. **Cele dotykowe na karcie auta** — powiększenie do 24×24 px zmieni odstępy w breadcrumbie i liście „Informacje".
4. **Rozmiary czcionek** — patrz sekcja 7. Świadomie poza zakresem tego audytu.

---

## 6. Luki metodyczne — czego NIE sprawdziłem

Wpisane wprost, bo raport nie może sugerować zgodności, której nie zweryfikowano.

1. **Czytnik ekranu (NVDA / VoiceOver / Orca) — nie uruchomiony.** Nie mam do niego dostępu w tym środowisku. Wnioski o zachowaniu czytnika w tym raporcie są **wywiedzione z kodu i drzewa dostępności**, nie z odsłuchu. Punkty A8, AA3, AA6 i AA7 wymagają potwierdzenia realnym czytnikiem, zanim uznamy je za naprawione. **To najpoważniejsza luka tego audytu.**
2. **1.4.10 Reflow (320 px) — nie zweryfikowany.** Okno Chrome było zmaksymalizowane, a `resize_window` nie zmieniało viewportu (`innerWidth` uparcie 1920). Nie testowałem realnego przewijania poziomego przy 320 px.
3. **1.4.4 Resize text (zoom 200%) — zweryfikowany tylko częściowo.** Potwierdziłem, że `<meta name="viewport">` **nie** blokuje skalowania (`width=device-width, initial-scale=1`, brak `user-scalable=no` i `maximum-scale`). Nie oglądałem układu przy faktycznym zoomie 200%.
4. **Testy na urządzeniu dotykowym — brak.** Cele dotykowe zmierzone w pikselach CSS z `getBoundingClientRect()`, co jest zgodne z metodyką 2.5.8, ale nie zastępuje sprawdzenia kciukiem. Gesty (swipe w galerii) nietestowane.
5. **Widok mobilny sprawdzony przez kod, nie wizualnie.** Menu hamburger, mobilny sticky CTA i mobilny sticky-head przeanalizowałem w DOM i w źródłach (`nav.js`, `asiaauto-single.css`), bo przy 1920 px są `display:none`. Nie widziałem ich wyrenderowanych.
6. **Sesja prowadzona na zalogowanym koncie administratora.** Pasek `#wpadminbar` wykluczałem z wszystkich pomiarów axe, ale przechwytuje on pierwszy Tab na stronie. Kolejność Tab dla wylogowanego użytkownika może się różnić na pierwszych pozycjach.
7. **Nie objęte audytem:** huby marek i serii (`taxonomy-make.php`, `taxonomy-serie.php`), dział wiedzy (`/wiki/`, `archive-asiaauto_wiki.php`), aktualności, panel klienta (`/klient/`, za logowaniem), kroki 2–4 kreatora zamówienia (wymagają rozpoczęcia realnego zamówienia). Huby i wiki to duży kawałek serwisu używający tych samych klas `aa-*`, więc naruszenia kontrastu i hierarchii nagłówków najpewniej się tam powtarzają — ale tego **nie zmierzyłem**.
8. **Nie testowałem wysłania formularza.** Walidację kreatora przeanalizowałem w kodzie źródłowym zamiast wysyłać zgłoszenie, żeby nie utworzyć fikcyjnego zamówienia u Ruslana. Zachowanie po stronie serwera (komunikaty błędów z REST API) niesprawdzone.
9. **1.4.13 Content on Hover** — nie testowane. **2.2.2 Pause, Stop, Hide** — nie znalazłem karuzeli z autoodtwarzaniem, ale nie przeglądałem wszystkich szablonów pod tym kątem.
10. **Źródło koloru `#fbeaea`** (3 wystąpienia wg axe, tekst na czerwonym badge) nieustalone — nie ma go w theme ani w pluginie. Do znalezienia przed naprawą kontrastu.
11. **Wystąpienia kolorów poza warstwą publiczną nieprzejrzane.** `#718096` występuje m.in. w `class-asiaauto-contract.php` (umowa PDF), `class-asiaauto-order-admin.php`, `class-asiaauto-login.php` i `class-asiaauto-admin.php`. Te widoki nie były przedmiotem audytu, więc nie wiem, czy zmiana koloru jest tam bezpieczna ani czy same mają problemy z dostępnością.

---

## 6a. Sprostowania wobec wcześniejszych ustaleń

Zapisane, żeby nie wracać do obalonych hipotez:

- **„Kontrast to wymiana pięciu tokenów CSS"** — nieprawda. Tokeny istnieją w `base.css:26-37`, ale plugin ich nie używa: 125 hardkodów hex w ~20 plikach, 65 w PHP. Sekcje 3 i 5a poprawione.
- **„`#25d366` nie występuje w kodzie"** — artefakt grepa bez `-i`. W kodzie jest `#25D366`, 7 wystąpień.
- **„`button.aa-search__clear` to fałszywy alarm axe, bo ma `display:none`"** — nieprawda. W stanie początkowym jest ukryty, ale po interakcji z wyszukiwarką renderuje się jako `display:flex` o wymiarach **20×20 px**. Naruszenie 2.5.8 jest realne.
- **„Theme nie ma reguł `:focus`"** — błąd mojego skryptu przy pierwszym przejściu CSSOM. Theme **ma** globalny wskaźnik: `:focus-visible { outline: 2px solid var(--c-accent); outline-offset: 2px }` w `base.css`. Problemem nie jest brak obrysu, tylko jego kontrast na ciemnym tle (AA2).
- **„Pole tekstowe bez etykiety w kreatorze zamówienia"** — to honeypot antyspamowy `_hp`, poprawnie ukryty przez `aria-hidden="true"` + `tabindex="-1"`. Nie jest naruszeniem.

---

## 7. Obserwacje projektowe (poza WCAG — do Twojej decyzji)

Zgodnie z ustaleniem nie zmieniam rozmiarów czcionek ani układu przy okazji audytu. Zapisuję osobno:

- **Rozmiary czcionek 10 px**: `aa-home__stat-label`, `aa-home__car-badge`, `aa-home__fuel-count`. **13 px**: `aa-home__section-sub`, `aa-single__hubback`. Na mobile to za mało niezależnie od kontrastu. WCAG nie stawia tu progu, ale przy 79,6% ruchu mobilnego to realny problem czytelności.
- **Stopka `pa-footer__bottom` ma 12 px** przy szarym `#718096` na granacie — kumulacja małego stopnia pisma i najniższego kontrastu w serwisie.
- **Paginacja listingu nie ma `href`** — przyciski są w pełni JS-owe. To nie jest naruszenie dostępności (są to prawidłowe `<button>` w `<nav aria-label="Stronicowanie">` z `aria-current="page"`), ale ma konsekwencje SEO: strony 2–115 nie są linkami do zaindeksowania.

---

## 8. Co wypadło dobrze (nie wymaga pracy)

Odnotowuję, żeby nie remontować rzeczy sprawnych:

- **Menu mobilne** (`nav.js`) — poprawna pułapka focusu, obsługa Escape, powrót focusu na przycisk, przełączany `aria-label`, `hidden` na kontenerze, `aria-label="Menu mobilne"` na `<nav>`. Wzorcowe.
- **Teksty alternatywne** — 71/71 obrazów na stronie głównej ma `alt`, żaden nie jest nazwą pliku ani słowem „zdjęcie". W galerii `alt` **zmienia się** przy przełączaniu zdjęć.
- **Formularz kreatora zamówienia** — etykiety powiązane przez `for`, poprawne `autocomplete` (`given-name`, `family-name`, `email`, `tel`), `required`. Kryterium **1.3.5 Identify Input Purpose spełnione**.
- **Honeypot antyspamowy** `_hp` — `aria-hidden="true"` + `tabindex="-1"` + `autocomplete="off"`. Zrobiony prawidłowo, niewidoczny dla technologii asystujących.
- **Paginacja** — `<nav aria-label="Stronicowanie">` z `aria-current="page"` na aktywnej stronie.
- **Lightbox** — Escape zamyka, strzałki przewijają (`class-asiaauto-shortcodes.php:993-999`).
- **Przyciski galerii** — sensowne `aria-label`: „Poprzednie zdjęcie", „Następne zdjęcie", „Pełny ekran", „Zdjęcie N z 15". Strzałki 40×40, miniatury 80×56 — spełniają 2.5.8.
- **Landmarki** — `<header>`, `<nav>`, `<main>`, `<footer>` obecne na każdej badanej stronie.
- **`lang="pl-PL"`**, unikalne `<title>` per strona.
- **`prefers-reduced-motion`** obsłużone w CSS.
- **Brak `outline: none`** w CSS theme'u i brak dodatnich `tabindex` w całym serwisie.
- **1.4.12 Text Spacing** — przetestowane przez wymuszenie `line-height: 1.5`, `letter-spacing: 0.12em`, `word-spacing: 0.16em` na `/kontakt/` i `/samochody/`: **zero przycięć treści, zero przewijania poziomego**. Kryterium spełnione.
- **Iframe mapy Google** ma `title="Mapa — Prima-Auto plac samochodowy, Rzeszów"`.

---

## 9. Wynik automatu przed naprawą (baseline do porównania po)

PageSpeed Insights, kategoria accessibility, `locale=pl`. Surowe JSON-y zachowane w scratchpadzie sesji.

| Typ strony | URL | Mobile | Desktop | Naruszeń (mobile) |
|---|---|---|---|---|
| Strona główna | `/` | **87** | 87 | 65 |
| Listing pojazdów | `/samochody/` | **86** | 84 | 78 |
| Karta auta | `/oferta/jetour-shanhai-t1-2024-398935/` | **89** | 89 | 74 |
| Kontakt | `/kontakt/` | **92** | 92 | 22 |
| **Kreator zamówienia** | `/zamow/?listing_id=398935` | **93** | 93 | 10 |
| Zamów — stan pusty | `/zamow/` | **92** | 92 | 4 |
| Wyszukiwarka — wyniki | `/?s=byd` | **91** | 91 | 4 |
| Wyszukiwarka — brak wyników | `/?s=zxqwvbnmasdf` | **93** | 93 | 3 |
| O nas | `/informacje/o-nas/` | **93** | 93 | 3 |
| Polityka prywatności | `/polityka-prywatnosci/` | **93** | 93 | 3 |
| Regulamin | `/informacje/regulamin/` | **93** | 93 | 3 |
| 404 | dowolny nieistniejący URL | **91** | 91 | 6 |

**Uwaga do baseline'u:** pierwszy przebieg zmierzył `/zamow/` bez parametru `listing_id`, czyli komunikat „Nie wskazano pojazdu" zamiast właściwego kreatora. Pomiar powtórzony na `?listing_id=398935` — obie wartości zostawiam w tabeli, bo to dwa różne ekrany.

**Czego ten wynik nie mówi.** Automat pokrywa ok. 30% kryteriów WCAG. Wynik 87–93 opisuje wyłącznie tę część. Z 17 rodzajów naruszeń w tym raporcie automat wykrył **5** (kontrast, `link-name`, `select-name`, `heading-order`, `target-size`). Pozostałe 12 — w tym wszystkie cztery blokujące — pochodzi z warstwy ręcznej. **Wynik PSI nie jest deklaracją zgodności i nie może być używany jako dowód spełnienia EAA.**

---

## 10. Metodyka

**Warstwa 1 — automat.** PageSpeed Insights v5 (Lighthouse z axe-core pod spodem), kategoria accessibility, mobile + desktop, 12 typów stron. Skrypt: `scratchpad/a11y_primaauto.py` (na bazie `~/projekty/auranet/tmp/a11y_przesiew.py`). Surowe JSON-y zachowane w celu odtworzenia selektorów i par kolorów.

**Warstwa 2 — ręczna.** axe-core 4.10.2 wstrzyknięty przez Chrome MCP na żywych stronach z wykluczeniem `#wpadminbar`, uruchomiony na tagach `wcag2a`, `wcag2aa`, `wcag21a`, `wcag21aa`, `wcag22aa`, `best-practice`. Do tego autorskie sondy DOM (hierarchia nagłówków, landmarki, `alt`, etykiety pól, `autocomplete`, cele dotykowe z wyjątkiem inline, reguły `:focus` w CSSOM), testy klawiaturowe (Tab, Enter na skip linku, stan `activeElement` po akcji), test wymuszonych odstępów tekstu, oraz przegląd źródeł: `nav.js`, `asiaauto-order-wizard.js`, `class-asiaauto-shortcodes.php`, `class-asiaauto-single.php`, `asiaauto-single.css`.

**Warstwa 3 — czytnik ekranu.** Nie wykonana. Patrz sekcja 6, punkt 1.

**Wszystkie wartości kontrastu policzone**, nie oszacowane — wzorem luminancji względnej WCAG, weryfikacja krzyżowa z wyjaśnieniami axe.

**Dowody wizualne:** `PrimaAuto/temp` na Dysku Google — zrzuty potwierdzające kontrast WhatsApp (1,83:1 i 1,98:1).
