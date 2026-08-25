# Prima Auto — podsumowanie domknięcia dostępności, wydanie 3

> **Dla audytora (projekt `auranet`).** Dokument zamyka wdrożenie z 25.08.2026 i jest punktem
> wyjścia do wydania 3 raportu.
>
> - Zlecenie wykonawcze: `~/projekty/auranet/docs/uslugi/wdrozenia/2026-08-21-primaauto-wdrozenie-v3.md`
> - Poprzednie podsumowanie: `docs/dostepnosc/2026-08-20-PODSUMOWANIE-DO-REAUDYTU.md`
> - Dowody liczbowe: `docs/dostepnosc/dowody-2026-08-25/`
> - Archiwa pomiarów: `~/projekty/auranet/docs/uslugi/audyty/primaauto-2026-08-25-weryfikacja{,2}/`
>
> **Wdrożenie nie orzeka o zgodności.** Orzeka reaudyt.

---

## 1. Skrót jednym akapitem

Wszystkie cztery pozycje ze zlecenia v3 są zamknięte. Pomiar axe **na tych samych 17 adresach
i tym samym narzędziem, którego użył reaudyt** spadł ze **97 do 27** elementów z błędem
kontrastu, a wszystkie 27 to wyłącznie para `#d63031` na `#f5f6f8` — czyli odstępstwo
właściciela, bez ani jednego elementu spoza niego. Sekwencja nagłówków na `/samochody/`
to teraz 1 → 2 → 3, na `/w-drodze/` 1 → 2, zero przeskoków na obu. Źródło nazwy dostępnej
pól wyszukiwania w filtrach to `aria-label`, a `placeholder` jest w drzewie dostępności jawnie
odrzucony. Wskaźnik fokusu ma na tle nagłówka 8,77:1 zamiast 1,81:1.

**Zastrzeżenie wykonawcy do N-1b było błędne — audytor miał rację.** Opis przyczyny w §4;
warto go przeczytać, bo mechanizm jest niewidoczny dla wyszukiwania po kodzie i wróci
w innych projektach.

## 2. Stan kart

| Karta | Kryterium | Stan | Dowód |
|---|---|---|---|
| **N-1** kontrast | 1.4.3 | ✅ zamknięta poza odstępstwem | 97 → 27 na 17 stronach, §3 |
| **N-2** przeskok nagłówka | 1.3.1 + 2.4.6 | ✅ zamknięta | drzewo dostępności, §5 |
| **N-4** etykiety pól filtrów | 3.3.2 | ✅ zamknięta | źródła nazwy z CDP, §6 |
| **N-5** kontrast wskaźnika fokusu | 1.4.11 | ✅ zamknięta | pomiar na 3 tłach, §7 |

## 3. N-1 · Kontrast — 97 → 27 na 17 stronach

Narzędzie: `scripts/a11y_sesja.mjs` (axe-core), próbka `urls-retest.txt`, ta sama 17-adresowa
lista co reaudyt 21.08. Zakres pomiaru podany przy każdej liczbie zgodnie z prośbą procesową.

| Para tekst / tło | 21.08 (przed) | 25.08 (po) | Co to było |
|---|---:|---:|---|
| `#d63031` na `#f5f6f8` | 52 | **27** | czerwień marki — odstępstwo właściciela |
| `#999999` na `#f5f6f8` | 34 | **0** | podpisy modeli w galerii na `/marki/` |
| `#ffffff` na `#8d97a5` | 9 | **0** | przycisk „Zarezerwowane” — patrz §4 |
| `#64748b` na `#f5f6f8` | 2 | **0** | okruszek na stronach słownika |
| **Razem** | **97** | **27** | na 11 → 9 stronach z 17 |

Spadek z 52 do 27 w pierwszym wierszu nie wynika ze zmiany koloru — `--c-accent` nie był
ruszany. Wcześniejszy pomiar liczył pod tą parą także elementy, które zmieniły się z innych
powodów; po naprawie zostały wyłącznie ceny i linki w odcieniu marki.

Zmiany, które to dały:

| Element | Było | Jest | Kontrast |
|---|---|---|---|
| etykieta „Nowe” na hubach marek | `#fff` na `#E8AC07` | `#1B2A4A` | 2,03 → **7,00:1** |
| etykieta „Nowe” na stronie głównej | `#4A3800` | `#1B2A4A` | 5,57 → **7,00:1** — ujednolicenie, patrz §8 |
| licznik modeli przy marce | `#dc2626` na `#fef2f2` | `#C82333` | 4,41 → **5,13:1** |
| podpisy modeli w galerii `/marki/` | `#999` w atrybucie `style` | klasa, `#6E6E6E` | 2,63 → **4,72:1** |
| okruszek na stronie hasła słownika | `#64748B` na `#f5f6f8` | `#5A6779` | 4,40 → **5,32:1** |
| przycisk „Zarezerwowane” | `#5C6B7F` + `opacity:.7` | `#68727F`, bez `opacity` | 2,96 → **4,88:1** |

Odcień `#767676` wskazany jako alternatywa dla podpisów modeli **nie wystarcza** — daje 4,20:1
na `#f5f6f8`. Użyty `#6E6E6E`.

## 4. N-1b — zastrzeżenie wykonawcy było błędne, i dlaczego to ważne

Przed wdrożeniem zakwestionowaliśmy tę pozycję, opierając się na dwóch faktach: koloru
`#8d97a5` nie ma **nigdzie** w `wp-content` (rekurencyjny grep, zero trafień), a arkusz wydawany
przez serwer podaje w regule przycisku `#5C6B7F` (5,43:1 z bielą) z datą modyfikacji pliku
sprzed reaudytu. Oba fakty były prawdziwe. Wniosek z nich — fałszywy.

Pomiar na renderowanej stronie pokazał 10 elementów `#ffffff` na `#8d97a5`, dokładnie
na przycisku „Zarezerwowane”. Przyczyną jest ostatnia deklaracja w tej samej regule:

```css
opacity: .7;
```

Tło `#5C6B7F` przy kryciu 70% na białej stronie składa się do `rgb(141,151,165)` = `#8d97a5`,
co do bitu. Biały napis pozostaje biały, bo biel zmieszana z bielą się nie zmienia — więc
przygaszeniu ulega wyłącznie tło i kontrast spada z 5,43 do 2,96:1.

**Wniosek metodyczny:** koloru powstającego z kompozycji warstw nie da się znaleźć
wyszukiwaniem po kodzie. Dowód „grep nic nie znajduje” jest tu bezwartościowy, a dowodem
jest wyłącznie odczyt z renderowanej strony. Ta sama pułapka dotyczy `opacity`, `mix-blend-mode`,
półprzezroczystego `rgba()` i gradientów.

Naprawa nie polega na przyciemnieniu tła przy zachowaniu `opacity` — to by nie pomogło, bo
przezroczystość rozjaśniałaby każdą wartość. `opacity` zostało usunięte, a wygaszenie zrobione
samym kolorem: `#68727F` to najjaśniejszy odcień z realnym zapasem nad progiem (**4,88:1**),
więc przycisk nadal czyta się jako wyłączony. Rekomendowany w karcie `#6B7684` daje 4,56:1 —
przechodzi, ale zapas 0,06 punktu uznaliśmy za zbyt cienki wobec zaokrągleń.

## 5. N-2 · Nagłówki — dwie różne przyczyny, dwie różne naprawy

Pomiar: Chrome DevTools Protocol, `Accessibility.getFullAXTree`, węzły `role=heading`
z odfiltrowanymi `ignored`.

| Strona | Przed | Po | Przeskoki |
|---|---|---|---|
| `/samochody/` | 1 → 3 | **1 → 2 → 3** (26 nagłówków) | **0** |
| `/w-drodze/` | 1 → 3 | **1 → 2** (24 nagłówki) | **0** |

**`/w-drodze/`** — karta auta renderowała `h3` niezależnie od kontekstu. Podstrony rezerwacji
nie mają panelu filtrów, więc nie miały żadnego `h2`. Poziom jest teraz zależny od flagi
`is_subpage`, która już istniała w `executeQuery()` i odróżnia podstrony od katalogu. Na
`/samochody/` karty zostają na poziomie 3, bo tam stoją pod „Filtry”. Styl nagłówka siedzi
na klasie `.aa-card__title`, nie na selektorze `h3` — sprawdzone przed zmianą, wygląd bez zmian.

**`/samochody/`** — karta wskazywała jako przyczynę blok mobilny arkusza. Faktyczna przyczyna
była wcześniej i prostsza: reguła bazowa `.aa-inv__sidebar-head { display: none }` w linii 71,
czyli głowa panelu była wygaszona na desktopie w całości. Diagnoza w karcie (0 × 0 px,
`offsetParent = null`) opisywała skutek trafnie, ale wskazywała nie tę regułę.

Naprawa zgodna z rekomendacją co do techniki, w bloku `@media (min-width: 769px)`, żeby nie
kolidować z regułami mobilnymi: głowa panelu wraca jako `display: block`, nagłówek chowany
przez `clip-path` przy wymiarach 1 × 1 px, mobilny przycisk zamykania nadal `display: none`
— nie ma go po co wystawiać czytnikowi na desktopie.

## 6. N-4 · Etykiety pól wyszukiwania w filtrach

Pomiar po kliknięciu przełącznika `button.aa-filter__trigger`, zgodnie z uwagą z karty.
Odczyt `name.sources` z drzewa dostępności:

```
rola:            textbox
nazwa dostępna:  "Szukaj w filtrze: Marka"
źródła nazwy:    attribute[aria-label] = Szukaj w filtrze: Marka
                 placeholder[placeholder] = Szukaj...   <-- ODRZUCONE
```

`placeholder` jest w drzewie jawnie odrzucony na rzecz `aria-label`, czyli technika porażki F82
nie ma już zastosowania. Etykieta jest budowana z nazwy taksonomii filtra, więc każde pole
dostaje własną, rozróżnialną nazwę.

**Uściślenie do karty:** pola są dwa, ale w filtrach **„Marka” i „Rodzaj nadwozia”**, nie
„Marka” i „Model”. Pole wyszukiwania pojawia się dopiero przy więcej niż ośmiu wartościach
w filtrze, a filtr modelu tego progu nie przekracza w stanie zastanym.

## 7. N-5 · Wskaźnik fokusu

Wdrożony wariant dwubarwny, rekomendowany przez audytora. Pomiar `getComputedStyle`
po nadaniu fokusu, kontrast liczony wobec faktycznego tła:

| Tło | Gdzie | Obwódka `#fff` | Poświata `#1B2A4A` | Lepsza z dwóch | Próg 1.4.11 |
|---|---|---:|---:|---:|---|
| `#9b0000` | nagłówek — logo i menu | **8,77:1** | 1,62:1 | **8,77:1** | spełnia |
| `#1b2a4a` | skip-link | **14,22:1** | 1,00:1 | **14,22:1** | spełnia |
| `#ffffff` | treść strony | 1,00:1 | **14,22:1** | **14,22:1** | spełnia |

Mechanizm działa zgodnie z założeniem: na każdym z trzech teł widoczna jest co najmniej jedna
warstwa i zawsze z zapasem ponad trzykrotnym wobec progu.

Wskaźnik został ujednolicony także w dwóch miejscach **poza literą karty**: katalog ofert
i kreator zamówienia nadpisywały go regułami z `!important`. Pozostawienie ich dałoby czerwoną
obwódkę w katalogu i biało-granatową w reszcie serwisu. Oba tła są jasne, więc kryterium
przechodziły i przed zmianą — powód był wyłącznie spójnościowy.

Ocena wizualna właściciela została zebrana przed zgłoszeniem do reaudytu, zgodnie z prośbą
z karty. Wariant dwubarwny zaakceptowany, wariant minimalny nie był potrzebny.

## 8. Zmienione poza zakresem audytu

1. **Etykieta „Nowe” na stronie głównej** — `#4A3800` (5,57:1) → `#1B2A4A` (7,00:1). Kryterium
   przechodziła, zmiana jest wyłącznie spójnościowa: ta sama etykieta miała dwa różne kolory
   na hubie marki i na stronie głównej.
2. **Wskaźnik fokusu w katalogu i kreatorze zamówienia** — patrz §7.
3. **Wersja arkuszy motywu** `1.2.3` → `1.2.4`, żeby przeglądarki pobrały nowy CSS. Arkusze
   wtyczki wersjonują się przez `filemtime` i nie wymagały nic.

## 9. Znalezione przy testach, świadomie NIE naprawione

**Reflow 320 px na `/marki/` — `scrollWidth` 332 przy rzutni 320, przewijanie poziome (1.4.10).**

Winowajcą jest kafel „Leapmotor” z licznikiem `193`: `.aa-brand-card__name` nie ma
`min-width: 0`, więc kolumna `1fr` siatki nie może zejść poniżej szerokości nazwy i wypycha
pigułkę licznika o 12 px poza kartę.

**To nie jest regresja po dzisiejszych zmianach.** Sprawdzone testem A/B: ta sama strona
wczytana z arkuszami sprzed wdrożenia, podmienianymi przez przechwycenie żądania sieciowego,
daje identyczne 332 px i ten sam element. Jedyną zmienną był CSS — dane, DOM i rzutnia te same.
Reaudyt z 21.08 zmierzył tu 0 wystających; rozbieżność jest po stronie warunków pomiaru,
nie kodu.

Kandydat poprawki sprawdzony w przeglądarce — `scrollWidth` spada z 332 na 320, zero
wystających:

```css
.aa-brand-card__name { min-width: 0; overflow-wrap: anywhere; }
```

Nie wdrożono, bo pozycja jest poza zleceniem naprawczym, a zmiana wpływa na zachowanie kafli
marek: bardzo długie nazwy zaczną się łamać zamiast rozpychać kartę. Do decyzji właściciela
przed reaudytem.

## 10. Kryteria przejścia ze zlecenia v3

| Pomiar | Wymagane | Zmierzone | |
|---|---|---|---|
| axe `color-contrast` na 17 stronach | ≤ 27, wyłącznie `#d63031`/`#f5f6f8` | **27, wyłącznie ta para** | ✅ |
| Nagłówki `/samochody/` | 1 → 2 → 3, bez przeskoku | **1 → 2 → 3, 0 przeskoków** | ✅ |
| Nagłówki `/w-drodze/` | 1 → 2, bez przeskoku | **1 → 2, 0 przeskoków** | ✅ |
| Źródło nazwy `.aa-filter__search input` | `aria-label`, nie `placeholder` | **`aria-label`, placeholder odrzucony** | ✅ |
| Kontrast wskaźnika fokusu na `#9b0000` i `#1b2a4a` | ≥ 3:1 | **8,77:1 i 14,22:1** | ✅ |
| Reflow 320 px | `scrollWidth` = 320, 0 wystających | `/samochody/` i `/w-drodze/` **320 / 0**; `/marki/` **332 / 1** | ⚠ §9 |
| Przejście klawiaturą | 0 elementów bez wskaźnika | **0** | ✅ |

## 11. Co zostało dotknięte

| Plik | Pozycja |
|---|---|
| `themes/primaauto2026/assets/css/hub.css` | 1a, 1c, 1e |
| `themes/primaauto2026/assets/css/kb.css` | 1d |
| `themes/primaauto2026/assets/css/base.css` | 4 |
| `themes/primaauto2026/functions.php` | wersja arkuszy |
| `plugins/asiaauto-sync/assets/css/asiaauto-inventory.css` | 1b, 2b, 4 |
| `plugins/asiaauto-sync/assets/css/asiaauto-order-wizard.css` | 4 |
| `plugins/asiaauto-sync/includes/class-asiaauto-inventory.php` | 2a, 3 |
| `plugins/asiaauto-sync/includes/class-asiaauto-homepage.php` | §8 |
| baza: strona `Chińskie marki samochodów` (263572) | 1e — 6 × atrybut `style` → klasa |

Kopie zapasowe: `.bak-2026-08-25-a11y-v3` obok każdego pliku oraz w
`~/backups/primaauto/2026-08-25/`. Zrzut strony przed zmianą treści:
`~/backups/primaauto/2026-08-25/post-263572-marki-przed-a11y-v3.sql`.

Nie ruszano importu, synchronizacji, logiki zamówień, generatora umowy PDF ani panelu
administracyjnego. Nazwy klas, CPT, meta i shortcodów bez zmian.

## 12. Prośba do audytora

Reaudyt wg procedury 7.4: ta sama próbka 17 adresów, te same narzędzia, pełne 55 kryteriów.
Prosimy o odnotowanie w wydaniu 3:

1. `#d63031` na `#f5f6f8` (27 elementów na 9 stronach z 17) jako **odstępstwo właściciela**,
   nie przeoczenie wykonawcy — uzasadnienie w podsumowaniu z 20.08 §3.
2. Uściślenia do N-2 i N-4 z §5 i §6 — dotyczą wskazania przyczyny i lokalizacji, nie
   zasadności kart. Obie karty były trafne co do istnienia naruszenia.
3. Pozycji z §9 jako **znaleziska wykonawcy poza zakresem zlecenia**, zgłoszonego przed
   reaudytem wraz z gotową poprawką.
