# T-246 — Wdrożenie poprawek dostępności (WCAG 2.2) + zgłoszenie do reaudytu

> Status: **gotowe do startu** · Rozmiar: M · Powstało z promptu wykonawczego audytora
> Źródła (projekt `auranet`, nie kopiujemy — czytamy z oryginału):
> - audyt zamknięty: `~/projekty/auranet/docs/uslugi/audyty/primaauto-2026-08-13.md`
> - prompt wykonawczy **v2 (obowiązujący)**: `~/projekty/auranet/docs/uslugi/wdrozenia/2026-08-19-primaauto-wdrozenie-v2.md`
> - prompt v1 (zastąpiony, ale karty 2–7 mają tam pierwotne brzmienie): `…/2026-08-14-primaauto-wdrozenie.md`
> Kopia robocza promptu v2 wystawiona 19.08: `https://auratest.pl/fe4f58fec53ctmp/auranet-primaauto-wdrozenie-v2-2026-08-19.md`
> Godziny: **nie oszacowane przez audytora** — to pierwszy przebieg pełnego cyklu audyt → wdrożenie → reaudyt,
> a pomiar czasu każdego etapu jest jednym z celów zadania. Czas zapisujemy w raporcie z etapu 5.

---

## 1. Po co ten task istnieje

Audyt dostępności primaauto.com.pl (13.08.2026, próbka 16 adresów, 55 kryteriów WCAG 2.2)
zamknął się siedmioma niezgodnościami: **N-1 … N-7**. Wdrożenie dzieje się **tutaj**, w projekcie
`primaauto`; orzekanie o zgodności zostaje po stronie audytora w `auranet`. Ta sesja **nie orzeka**.

Task założony 20.08.2026, żeby robota miała ślad w kolejce projektu — prompt wykonawczy żył
wyłącznie w `auranet` i na dropie auratest.

---

## 2. Zasady, których nie łamiemy

1. **To nie jest audyt.** Nie zmieniamy orzeczeń, nie dopisujemy nowych niezgodności do raportu.
   Coś nowego znalezione po drodze → `docs/dostepnosc/obserwacje.md` + zgłoszenie Jankowi;
   do raportu wejdzie dopiero przez reaudyt.
2. **Produkcja z backupem.** Kopia pliku przed każdą zmianą (`<plik>.bak-2026-08-19-a11y`),
   `mysqldump` przed dotknięciem bazy.
3. **Diff przed wdrożeniem.** Każda zmiana pokazana Jankowi zanim wyląduje na produkcji.
4. **Commit per karta:** `[a11y] N-5: wskaznik fokusu w elementach sterujacych`.
5. **Nie ruszamy treści merytorycznej ani SEO.**
6. **Nie wysyłamy niczego do klienta.**

---

## 3. Korekta v1 → v2: naprawa siedzi w PLUGINIE, nie w motywie

Karta N-5 wskazywała „szablon (arkusz motywu)" — **błędnie**. W motywie `primaauto2026` nie ma
ani jednej reguły `outline: none`, jest za to poprawne `:focus-visible { outline: 2px solid var(--c-accent) }`
w `assets/css/base.css:214` (wgrane przy lipcowych poprawkach). Wszystkie wygaszenia fokusu siedzą
w pluginie **`asiaauto-sync`** — ustalone 19.08 przeszukaniem `wp-content`:

| Plik | Linia | Selektor / stan | `!important` |
|---|---|---|---|
| `assets/css/asiaauto-inventory.css` | 104 | `select.aa-sort__select:focus` | tak |
| `assets/css/asiaauto-inventory.css` | 163, 207, 239, 465 | pola i przyciski filtrów **w stanie spoczynkowym** | tak |
| `assets/css/asiaauto-order-wizard.css` | 232 | `.aa-wiz__field input/select:focus` | nie |
| `includes/class-asiaauto-login.php` | 181 | `.login input[...]:focus` | tak |
| `includes/class-asiaauto-shortcodes.php` | 2759 | `#aa-klient-login-form input[type=password]:focus` | nie |

Cztery reguły z `asiaauto-inventory.css` tłumaczą pomiar **„9 kolejnych elementów bez wskaźnika
na `/samochody/`"**: nie są pod `:focus`, tylko na stanie podstawowym z `!important`, więc gaszą
obramowanie zawsze i przebijają regułę motywu, która `!important` nie ma.

**Konsekwencja:** zapis z v1 „nie dotykamy `asiaauto-sync`" **przestaje obowiązywać w zakresie
warstwy prezentacji**. Import, synchronizacja i logika zamówień pozostają nietknięte
(por. CLAUDE.md §3 „strefy kruche" — tu ich nie ruszamy).

---

## 4. Strefy oddziaływania — co poprawka rusza i co może zepsuć

| Plik | Renderuje | Strefy | Ryzyko regresji |
|---|---|---|---|
| `asiaauto-inventory.css` | lista i filtry ofert | `/samochody/`, huby `taxonomy-make`, `taxonomy-serie`, wyniki wyszukiwania | **główna ścieżka ruchu i konwersji.** Reguły mają `!important` i sterują też `font-size: 0` na przyciskach — nieostrożna zmiana rozsypie filtry na mobile |
| `asiaauto-order-wizard.css` | kreator zamówienia | ścieżka zakupowa, formularz leada | **ścieżka pieniędzy.** Błąd = utrata leadów, nie tylko brzydki fokus |
| `class-asiaauto-login.php` | logowanie klienta | `/klient/`, panel klienta | zmiana wartości przycisku dotyka selektorów w testach/automatyzacjach |
| `class-asiaauto-shortcodes.php` | formularze osadzane shortcodem | wszędzie, gdzie użyty shortcode | 2700+ linii, HTML zmieszany z CSS — policz wystąpienia przed zmianą |
| baner zgód (Complianz) | overlay | **wszystkie strony** | źle zrobiona pętla fokusu tworzy pułapkę klawiatury i łamie 2.1.2, dziś spełnione |

**Zasada zakresu:** `outline` / `outline-offset` nie zajmują miejsca w modelu pudełkowym, więc
poprawka fokusu jest bezpieczna layoutowo. Kontrast (N-1) i rozmiar celu (N-3) **zmieniają wygląd** —
wymagają zrzutów przed i po.

---

## 5. Etap 1 — poprawki (kolejność od najmniejszego ryzyka)

### Karta 1 · N-5 — wskaźnik fokusu (poważna, 2.4.7 + 1.4.11)

Nie usuwamy istniejących `outline: none` — **dokładamy silniejszą regułę na końcu każdego arkusza**.
Usuwanie deklaracji ze stanu spoczynkowego zmieni wygląd pól; dołożenie `:focus-visible` nie.

```css
/* Dostepnosc 2.4.7 + 1.4.11: wskaznik fokusu klawiatury.
   !important konieczne — reguly wyzej gasza outline z !important. */
.aa-inv :where(a, button, input, select, textarea, [tabindex]):focus-visible,
.aa-wiz__field :where(input, select, textarea):focus-visible {
    outline: 2px solid #1B2A4A !important;
    outline-offset: 2px !important;
}
```

`#1B2A4A` = `--aa-primary` samego pluginu; na jasnych polach filtrów daje kontrast > 3:1, więc
zamyka 2.4.7 i 1.4.11 naraz. **Kontrast na ciemnym nagłówku sprawdzić osobno** — tam ten kolor
może nie wystarczyć i trzeba jaśniejszego wariantu.

W `class-asiaauto-login.php` i `class-asiaauto-shortcodes.php` regułę dopisujemy do tego samego
bloku `<style>`, w którym stoi `outline: none`.

### Karta 2 · N-1 — kontrast tekstu pomocniczego (poważna, 1.4.3)

**Co jest:** teksty pomocnicze poniżej 4,5:1 — **617 elementów** zgłoszonych przez axe w próbce.
**Co zrobić:** znaleźć kolory tekstu drugorzędnego (typowo `#888`, `#999`, `opacity` na tekście)
i pogłębić do progu. **Nie sterować kontrastem przez `opacity`** — jawny kolor.
**Kryterium:** kolejny przebieg axe = zero zgłoszeń `color-contrast`.
⚠️ Znane zastane braki spoza próbki (T-239, 06.08): `.aa-home__section-sub` i `.aa-home__stat-label` — **3,71:1**.
⚠️ Pułapka z T-239: `.aa-home a { color: inherit }` bije selektor jednoklasowy; podbicie specyficzności
trzeba powtórzyć **także w media query**, inaczej `width:100%` rozpycha przycisk.

### Karta 3 · N-7 — przycisk logowania bez nazwy dostępnej (średnia, 4.1.2 + 3.3.2)

`input[type="submit"][name="wp-submit"]` na `/klient/` bez wartości tekstowej i bez `aria-label`.
→ ustawić wartość przycisku („Zaloguj się") w szablonie formularza logowania.

### Karta 4 · N-4 — pole wyszukiwarki katalogu bez trwałej etykiety (średnia, 3.3.2)

Pole opisane wyłącznie `placeholderem`, który znika przy pisaniu.
→ `<label>` powiązana przez `for`/`id`, ukryta wizualnie klasą `.screen-reader-text`, jeśli układ
nie przewiduje widocznej etykiety. `placeholder` zostaje jako podpowiedź, nie jako etykieta.

### Karta 5 · N-6 — baner zgód zasłania element z fokusem (średnia, 2.4.11)

Przy przechodzeniu klawiaturą przez stronę główną **trzy elementy z fokusem** były przykryte
banerem. Baner pokazuje się na 16/16 stron przy pierwszej wizycie.
→ w Complianz włączyć tryb modalny (`role="dialog"`, `aria-modal="true"`) z pętlą fokusu w banerze
do czasu decyzji. Jeśli wtyczka nie pozwala — skrypt w motywie przenoszący fokus do banera przy
pierwszym `Tab` i trzymający go tam do „Akceptuj"/„Odrzuć".
⚠️ **Najłatwiejsza do zepsucia.** Po niej sprawdzić 2.1.2 — pętla jest dozwolona tylko dopóki baner widoczny.

### Karta 6 · N-3 — cele dotykowe poniżej rozmiaru (średnia, 2.5.8)

25 zgłoszeń `target-size`; elementy < 24 × 24 px z sąsiadem bliżej niż 24 px.
→ powiększyć obszar klikalny `paddingiem` (sam znak graficzny może zostać mały). Nie powiększać
ikon wizualnie, jeśli psuje to układ.

### Karta 7 · N-2 — przeskok poziomu nagłówka (drobna, 1.3.1)

Przeskoki poziomów na 2 z 16 stron.
→ poprawić poziomy nagłówków w szablonie listy samochodów tak, żeby wynikały z hierarchii,
a nie z rozmiaru wizualnego. ⚠️ `taxonomy-*.php` = strefa ZAWSZE PYTAJ; edytujemy **aktywny**
motyw `primaauto2026`, nie martwy `asiaauto` (`wp theme list --status=active` przed każdą edycją).

---

## 6. Etap 2 — testy wewnętrzne (przed dotknięciem przeglądarki)

Każdy punkt kończy się wynikiem zapisanym w notatce wdrożenia.

1. **Składnia PHP** — `php -l` na każdym zmienionym pliku. Fatal w pluginie kładzie serwis.
2. **Kopie przed zmianą** — `<plik>.bak-2026-08-19-a11y` obok oryginału.
3. **Wersja pluginu** — podbić numer w nagłówku `asiaauto-sync.php`; bez tego cache przeglądarek
   poda stary arkusz i test pokaże brak poprawki tam, gdzie poprawka jest.
4. **Zasięg shortcodu** — `grep -rn "aa-klient-login" ~/domains/primaauto.com.pl/public_html`
   zanim ruszymy `class-asiaauto-shortcodes.php`. Policzyć dotykane miejsca.
5. **Diff do przeglądu** — `diff -u <plik>.bak-2026-08-19-a11y <plik>` dla każdego pliku,
   pokazany Jankowi **przed** etapem 3.
6. **Brak zmian w logice** — w diffie nic poza CSS i wartościami atrybutów. Jeśli jest — powrót do 5 z wyjaśnieniem.

---

## 7. Etap 3 — testy przeglądarkowe (pptr, headless)

Runner: `~/bin/pptr`, narzędzia w `~/opt/pptr/narzedzia/`.

```bash
# a) axe na trzech widokach kluczowych dla poprawki
pptr wcag https://primaauto.com.pl/samochody/ > /tmp/a11y-samochody-po.txt
pptr wcag https://primaauto.com.pl/klient/    > /tmp/a11y-klient-po.txt
pptr wcag https://primaauto.com.pl/           > /tmp/a11y-home-po.txt

# b) dowody wizualne fokusu
pptr zrzut https://primaauto.com.pl/samochody/ /tmp/samochody-po.png --pelna

# c) reflow — czy poprawki nie rozsypaly ukladu na waskiej rzutni
pptr reflow https://primaauto.com.pl/samochody/

# d) regresja wydajnosci — arkusze uroslly
pptr audyt https://primaauto.com.pl/samochody/ --powtorz 3
```

**Kryterium przejścia:** w `a11y-samochody-po.txt` zero zgłoszeń `color-contrast` i `focus-visible`;
reflow bez utraty treści przy 320 px; CWV nie gorsze niż przed zmianą.

**Czego pptr nie zmierzy:** widoczności obramowania przy realnym przejściu klawiaturą —
`focus()` wywołany programowo daje w tej materii fałszywe wyniki. Dlatego etap 4.

---

## 8. Etap 4 — testy w Chrome MCP (realna klawiatura)

To jest test przesądzający o N-5. Wykonywany w przeglądarce użytkownika, nie headless.

1. Otworzyć `https://primaauto.com.pl/samochody/` w nowej karcie.
2. Naciskać **Tab** od początku dokumentu, licząc przystanki; po każdym zrzut ekranu.
3. Zapisać: ile przystanków, ile miało widoczne obramowanie, gdzie zniknęło.
   Przed poprawką: **9 kolejnych elementów bez wskaźnika**. Po poprawce ma być zero.
4. Powtórzyć na `/klient/` (pola logowania) i w kreatorze zamówienia.
5. **Test pułapki klawiatury** przy banerze zgód: wejść Tabem i sprawdzić wyjście. Jeśli się nie da —
   złamane 2.1.2 (dziś spełnione) i karta N-6 wraca do przeprojektowania.
6. Zrzuty → `docs/dostepnosc/dowody-2026-08-19/`.

⚠️ Nie klikać niczego, co otwiera dialog przeglądarki — blokuje sesję MCP.

---

## 9. Etap 5 — raport i prompt zwrotny do audytora

Po zamknięciu etapów 1–4 zapisujemy w repo: **`docs/dostepnosc/2026-08-19-wdrozenie-wcag.md`** —
lista zmienionych plików, diffy, wyniki etapów 2–4, **czas każdego etapu**, pozycje **niezamknięte** z powodem.

Następnie generujemy prompt zwrotny i **przekazujemy Jankowi do wklejenia w projekcie `auranet`**:

```
Reaudyt primaauto.com.pl po wdrożeniu poprawek dostępności z 19.08.2026.

Wdrożenie zamknięte, raport: ~/projekty/primaauto/docs/dostepnosc/2026-08-19-wdrozenie-wcag.md

Zmienione pliki:
  - <lista z wersjami i kopiami .bak>

Karty zgłoszone do ponownego orzeczenia: N-1, N-2, N-3, N-4, N-5, N-6, N-7
Karty niezamknięte: <numery + powód, albo „brak">

Wyniki testów po stronie wykonawcy:
  - axe /samochody/: <liczba zgłoszeń, w tym color-contrast i focus>
  - przejście klawiaturą /samochody/: <przystanków / bez wskaźnika>
  - reflow 320 px: <wynik>
  - CWV przed/po: <LCP, INP, CLS>

Wykonaj reaudyt zgodnie z procedurą 7.4: ta sama próbka 16 adresów, pełne 55 kryteriów,
wydanie 2 raportu ze statusem „końcowy", kolumny „przed" i „po" wypełnione.
Zaktualizuj wpis w docs/uslugi/rejestr-projektow-wcag.md.
```

**Reguły reaudytu (procedura 7.4)** stosowane po stronie audytora: ta sama próbka i baseline, pełne
55 kryteriów ponownie (poprawka potrafi zepsuć inne kryterium — `aria-label` psuje 2.5.3, pętla
fokusu łamie 2.1.2), pozycje niezamknięte zostają w raporcie ze statusem, nie znikają.

---

## 10. Czego w tym tasku nie robimy

- Nie zmieniamy treści ofert, opisów samochodów ani ustawień SEO.
- Nie ruszamy importu, synchronizacji ani logiki zamówień w `asiaauto-sync` — tylko warstwę
  prezentacji wskazaną w tabeli stref (§4).
- Nie orzekamy zgodności. Wdrożenie kończy się zgłoszeniem gotowości; orzeka reaudyt.
- Nie wysyłamy niczego do klienta.

---

## 11. Definicja „zrobione"

- [ ] Karty N-1 … N-7 wdrożone albo jawnie oznaczone jako niezamknięte z powodem
- [ ] `php -l` czysty, kopie `.bak-2026-08-19-a11y` istnieją, wersja pluginu podbita
- [ ] Diffy przejrzane przez Janka przed produkcją
- [ ] Etap 3 (pptr) z kryterium przejścia spełnionym
- [ ] Etap 4 (Chrome MCP): 0 elementów bez wskaźnika na `/samochody/`, brak pułapki klawiatury
- [ ] `docs/dostepnosc/2026-08-19-wdrozenie-wcag.md` + dowody w `docs/dostepnosc/dowody-2026-08-19/`
- [ ] Prompt zwrotny przekazany Jankowi (reaudyt odpala się w `auranet`)
