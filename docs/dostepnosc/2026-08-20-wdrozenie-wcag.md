# T-246 — wdrożenie poprawek dostępności WCAG 2.2 (raport wykonawczy)

> Data: **2026-08-20** · Wykonanie: Janek + Claude · Serwis: primaauto.com.pl
> Zlecenie: `~/projekty/auranet/docs/uslugi/wdrozenia/2026-08-19-primaauto-wdrozenie-v2.md`
> Audyt źródłowy: `~/projekty/auranet/docs/uslugi/audyty/primaauto-2026-08-13.md`
> Spec: `docs/roadmapa/T-246-wdrozenie-dostepnosc-wcag.md`
>
> **To wdrożenie nie orzeka o zgodności.** Orzeka reaudyt po stronie audytora.

---

## 1. Najważniejsze: dwie karty audytu okazały się fałszywe

Weryfikacja przed wdrożeniem (KROK 0) wykazała, że **N-4 i N-7 opisują stan, którego nie ma**.
Pliki nie były modyfikowane po dacie audytu (`class-asiaauto-shortcodes.php` — 04.08,
`class-asiaauto-inventory.php` — 31.07, audyt — 13.08), więc nie chodzi o naprawę w międzyczasie.

| Karta | Twierdzenie audytu | Stan zmierzony 20.08 |
|---|---|---|
| **N-4** pole wyszukiwarki bez etykiety | „bez `<label>`, `aria-label` ani `aria-labelledby`" | `aria-label="Szukaj marki lub modelu"` obecne w `class-asiaauto-inventory.php:203` |
| **N-7** przycisk logowania bez nazwy | „wyliczenie nazwy dostępnej zwraca pusty wynik" | `wp_login_form()` z `'label_log_in' => 'Zaloguj się'` → renderuje `value="Zaloguj się"` |

**Metoda dowodu:** drzewo dostępności przeglądarki przez CDP (`Accessibility.getFullAXTree`),
czyli to samo źródło, z którego korzysta czytnik ekranu — nie heurystyka narzędzia.

- `/samochody/`: **33 kontrolki, 0 bez nazwy dostępnej**
- `/klient/`: **8 kontrolek, 0 bez nazwy dostępnej**

Prawdopodobna przyczyna po stronie audytu: analiza statyczna kodu, która nie rozwinęła
wywołania `wp_login_form()` i nie objęła atrybutu `aria-label` w generowanym HTML.

**Do rozstrzygnięcia przez audytora przy reaudycie** — my ich nie zamykamy, bo nie było czego naprawiać.

## 2. Korekty merytoryczne wobec zlecenia

**a) Reguła CSS z promptu nie zadziałałaby.** Zaproponowano
`.aa-inv :where(a, button, …):focus-visible` — specyficzność **(0,2,0)**. Reguła gasząca to
`.aa-inv input[type="text"].aa-price-inputs__input:focus` — **(0,4,1)**. Przy równym `!important`
rozstrzyga specyficzność, więc wskaźnik nadal by nie wrócił. Wdrożono wariant z potrojoną
klasą — **(0,5,0)**.

**b) Skala N-5 większa niż w raporcie.** Audyt: „9 kolejnych elementów". Pomiar: **17**
(2 pola ceny, 12 próbek koloru, pole szukania, lista sortowania).

**c) Dwa miejsca poza wykazem zlecenia** — `asiaauto-payu.css:119` i `class-asiaauto-login.php:121`
(zlecenie wymieniało 5, jest 7).

**d) Kolor wskaźnika:** `#C92A2B` zamiast proponowanego granatu — motyw daje ten sam wskaźnik
reszcie serwisu (`base.css:214`), więc granat tylko na filtrach byłby niespójny.

**e) Podbicie wersji wtyczki zbędne** — assety wtyczki wersjonowane po `filemtime()`, nie po
`ASIAAUTO_VERSION`. Podbito natomiast **wersję motywu 1.2.2 → 1.2.3**, bo tam wersjonowanie
idzie po stałej i bez tego zmiany w `base.css`/`footer.css` nie dotarłyby do przeglądarek z cache.

## 3. Wyniki — przed i po

### axe-core 4.10.2 (WCAG 2.0/2.1/2.2 A + AA)

| Strona | Przed | Po |
|---|---|---|
| `/` | 70 elementów | **7** |
| `/samochody/` | 81 | **0** |
| `/klient/` | 8 | **0** |
| `/oferta/{listing}/` | 61 | **1** |
| `/samochody/smart/5/` | 24 | **6** |
| `/kontakt/` | 22 | **1** |
| **Razem** | **266** | **15** |

**Wszystkie 15 pozostałych zgłoszeń to jeden i ten sam przypadek:** czerwień marki `#D63031`
jako tekst na jasnoszarym tle `#F5F6F8` — **4,48 wobec progu 4,50**. Pozostawione świadomie,
decyzja Janka z przeglądu 20.08 (§4a). Przejściowo osiągnięto tu 0, ale kosztem odcienia marki
w całym serwisie — koszt nieproporcjonalny do różnicy 0,02 punktu.

### Przejście klawiaturą (wskaźnik fokusu, 160 przystanków)

| Strona | Przed | Po |
|---|---|---|
| `/samochody/` | 17 bez wskaźnika | **0** |
| `/oferta/{listing}/` | — | **0** |
| `/zamow/?listing=` | — | **0** |

### Fokus zasłaniany przez baner zgód (2.4.11)

| Strona | Przed | Po |
|---|---|---|
| `/` | 5 | **0** |
| `/samochody/` | 9 | **0** |
| `/oferta/{listing}/` | 2 | **0** |

### Kontrola regresji

- **2.1.2 (brak pułapki klawiatury) — zachowane.** Po kliknięciu „Akceptuj" baner znika,
  a fokus swobodnie obchodzi stronę: 34 różne elementy na 40 naciśnięć Tab (home), 27 (katalog).
- **Reflow 320 px:** `scrollWidth` 320, przewijanie poziome brak, elementów wystających **0**.
- **Konsola:** 0 błędów JS.
- **Przyrost wagi:** `asiaauto-inventory.css` +1 588 B, `footer.css` +286 B, nowy skrypt 2 649 B
  — łącznie ok. **4,5 KB**.

## 4. Co zmieniono

### N-5 · Wskaźnik fokusu (2.4.7 + 1.4.11)
- `assets/css/asiaauto-inventory.css` — reguła `:focus-visible` na końcu arkusza, specyficzność (0,5,0)
- `assets/css/asiaauto-order-wizard.css` — jw. dla kreatora (tam wskaźnikiem był `box-shadow` o kryciu 8 %)
- **Żadna istniejąca deklaracja nie została usunięta** — poprawka wyłącznie addytywna

### N-1 · Kontrast (1.4.3)
- **Tekst drugorzędny `#718096` → `#5C6B7F`** w 11 plikach frontowych (39 wystąpień):
  4,02 → 5,43 na białym, 3,71 → 5,03 na `#F5F6F8`
- **Stopka odwrotnie** — tam tekst leży na granacie, więc `.pa-footer { --c-secondary: #A8B6C8 }`
  (3,54 → 6,90). Globalna zmiana sama w sobie pogorszyłaby stopkę do 3,0
- ~~**Czerwień `#D63031` → `#C92A2B`**~~ — **WYCOFANE 20.08 po przeglądzie wizualnym.**
  Zmiana obejmowała `--c-accent`, czyli całą czerwień serwisu (ceny, przyciski, linki), żeby
  domknąć kryterium przy różnicy **0,02 punktu**. Koszt nieproporcjonalny do zysku — przywrócono
  pierwotny odcień. Zostaje 15 zgłoszeń axe, wszystkie tego jednego typu
- **Szary `#6b7280` → `#5C6B7F`** w `hub.css` (5×): 4,47 → 5,03
- **Badge „Nowe"** — biały na bursztynie dawał **2,03**; tło bursztynowe zostaje, tekst na
  ciemny brąz `#4A3800`: **5,57**. Pierwsza wersja użyła granatu (7,00), ale odrzucona przy
  przeglądzie jako obcy kolor — brąz to w istocie bardzo ciemny żółty, więc zostaje w rodzinie
  tła i badge zachowuje charakter. Warianty `--pl` i `--transit` dostały jawne `color: #fff`,
  bo stoją na ciemnym tle
- **Przycisk WhatsApp** — biały na zieleni dawał **1,98**; zieleń marki zostaje,
  tekst na granat: **7,17** (na hoverze 4,59)
- **Zieleń jako tekst** `#25D366 → #0E7A3C` w `contact.php`: 1,83 → 5,02
- **Tytuł kolumny USP** — krycie 0,9 na czerwonym dawało 4,17; pełne krycie: 4,85
- **CTA „Wszystkie auta"** — dziedziczyło `#2D3748` przez `.aa-home a { color: inherit }`
  mimo `color:#fff` (2,46). Podbita specyficzność wzorem `.aa-home__promo-cta` z T-239

### N-3 · Cele dotykowe (2.5.8)
- `.aa-search__clear` 20 × 20 → **24 × 24 px** (pozycjonowany absolutnie, układ nietknięty)
- `.aa-breadcrumb__link` i `.aa-usp-col__list li a` — `padding-block: 3px` (18 → 24 px)
- ⚠️ **Pierwsze podejście było błędne:** `padding` + ujemny `margin` miał nie ruszać układu,
  ale sprawił, że sąsiednie cele **nachodzą na siebie** — axe zgłosił je jako „partially obscured".
  Ujemny margines zdjęty

### N-6 · Baner zgód zasłania fokus (2.4.11)
- Complianz Premium 7.5.7.2 **nie ma pętli fokusu** (baner ma już `role="dialog"` i `aria-modal="true"`)
- Nowy `themes/primaauto2026/assets/js/a11y-consent-focus.js` (2,6 KB) — pętla działa
  **tylko gdy baner jest widoczny**, z trzema bezpiecznikami cofającymi skrypt do bezczynności
- Podpięty w `functions.php`, wersja motywu 1.2.2 → 1.2.3

### 4a. Przegląd wizualny z Jankiem (20.08, po pierwszym przebiegu)

Wdrożone zmiany kolorystyczne zostały przedstawione w formie par „przed / po" w realnym
kontekście (`primaauto-zmiany-kolorow-2026-08-20.html`). Dwie korekty:

| Pozycja | Decyzja | Powód |
|---|---|---|
| Czerwień marki | **cofnięta do `#D63031`** | zmiana całego odcienia marki dla 0,02 punktu kontrastu to zły stosunek kosztu do zysku |
| Badge „Nowe" | **granat → ciemny brąz `#4A3800`** | granat czytał się jako kolor obcy wobec bursztynowego tła; brąz daje 5,57 i zostaje w rodzinie koloru |

Pozostałe siedem zmian zaakceptowanych bez korekt.

**Wniosek na przyszłość:** przy kryteriach kontrastu, gdzie brakuje ułamka punktu, domyślnie
zgłaszać jako pozycję do decyzji, a nie zamykać zmianą koloru marki. Liczba jest argumentem,
nie rozstrzygnięciem — o wyglądzie decyduje właściciel serwisu.

### Karty niezamknięte
- **N-2** (przeskok poziomu nagłówka) — **nie potwierdzony**: 0 przeskoków na `/`, `/samochody/`
  i stronie ogłoszenia. Audyt wskazywał 2 z 16 stron, ale nie podaje których. Do wskazania przy reaudycie
- **N-4, N-7** — fałszywe, patrz §1

## 5. Kopie zapasowe

Konwencja `<plik>.bak-2026-08-20-a11y` obok każdego zmienionego pliku (data faktycznego wdrożenia;
zlecenie proponowało `-2026-08-19-`) + komplet w `~/backups/primaauto/2026-08-20/`.

## 6. Poza zakresem — świadomie nietknięte

- `class-asiaauto-order-admin.php` (31×), `class-asiaauto-contract.php` (16×),
  `asiaauto-order-admin.css`, `class-asiaauto-admin.php` — panel administracyjny i generator umowy PDF.
  Audyt obejmował strony publiczne; kolor w dokumencie prawnym to osobna decyzja
- `class-asiaauto-order.php`, `class-asiaauto-cpt.php` — strefa krucha (statusy)
- Import, synchronizacja, logika zamówień — nietknięte

## 7. Prompt zwrotny do audytora (do wklejenia w projekcie `auranet`)

```
Reaudyt primaauto.com.pl po wdrożeniu poprawek dostępności z 20.08.2026.

Wdrożenie zamknięte, raport: ~/projekty/primaauto/docs/dostepnosc/2026-08-20-wdrozenie-wcag.md

Zmienione pliki (kopie .bak-2026-08-20-a11y obok każdego):
  wtyczka asiaauto-sync (warstwa prezentacji):
    assets/css/asiaauto-inventory.css, asiaauto-order-wizard.css, asiaauto-single.css,
    asiaauto-payu.css
    includes/class-asiaauto-shortcodes.php, -inventory.php, -homepage.php, -contact.php, -login.php
  motyw primaauto2026 (1.2.2 → 1.2.3):
    assets/css/base.css, footer.css, hub.css
    assets/js/a11y-consent-focus.js (nowy), functions.php

Karty zgłoszone do ponownego orzeczenia: N-1 (częściowo — patrz niżej), N-3, N-5, N-6
N-1 zamknięta poza jednym przypadkiem: czerwień marki #D63031 jako tekst na tle #F5F6F8
  daje 4,48 przy progu 4,50. Właściciel serwisu świadomie zachowuje odcień marki.
  Prosimy o odnotowanie jako pozycji niezamkniętej z uzasadnieniem, nie jako przeoczenia.
Karty niezamknięte: N-2 (nie potwierdzony — 0 przeskoków na 3 sprawdzonych stronach;
  proszę wskazać, które 2 z 16 stron próbki go miały)
Karty zakwestionowane: N-4 i N-7 — patrz §1 raportu. Drzewo dostępności (CDP) pokazuje
  0 kontrolek bez nazwy dostępnej na /samochody/ (33) i /klient/ (8); pole wyszukiwarki ma
  aria-label, przycisk logowania ma value="Zaloguj się". Pliki nietknięte po dacie audytu.

Wyniki testów po stronie wykonawcy:
  - axe (6 stron): 266 → 15 elementów z błędem (wszystkie: czerwień marki jako tekst
    na jasnoszarym tle, 4,48 wobec progu 4,50 — pozostawione świadomie, patrz §4a)
  - przejście klawiaturą /samochody/: 160 przystanków, 17 → 0 bez wskaźnika
  - fokus zasłonięty banerem: 5/9/2 → 0/0/0
  - 2.1.2 po zmianie: zachowane (34 elementy na 40 Tabów po akceptacji banera)
  - reflow 320 px: 0 elementów wystających, brak przewijania poziomego
  - konsola: 0 błędów JS; przyrost wagi ok. 4,5 KB

Wykonaj reaudyt zgodnie z procedurą 7.4: ta sama próbka 16 adresów, pełne 55 kryteriów,
wydanie 2 raportu ze statusem „końcowy", kolumny „przed" i „po" wypełnione.
Zaktualizuj wpis w docs/uslugi/rejestr-projektow-wcag.md.
```

## 8. Czas

| Etap | Czas |
|---|---|
| KROK 0 — weryfikacja zlecenia i pomiar „przed" | ~50 min |
| Etap 1 — poprawki N-5, N-1, N-3, N-6 | ~65 min |
| Etapy 2–4 — testy, pomiary, kontrola regresji | ~35 min |
| Etap 5 — raport | ~15 min |
| **Razem** | **~2 h 45 min** |
