# Prima Auto — podsumowanie prac dostępnościowych do reaudytu

> **Dla audytora (projekt `auranet`).** Dokument zamyka wdrożenie z 20.08.2026 i jest punktem
> wyjścia do wydania 2 raportu.
>
> - Audyt źródłowy: `~/projekty/auranet/docs/uslugi/audyty/primaauto-2026-08-13.md`
> - Zlecenie wykonawcze: `~/projekty/auranet/docs/uslugi/wdrozenia/2026-08-19-primaauto-wdrozenie-v2.md`
> - Raport wykonawczy (pełny, z dowodami): `docs/dostepnosc/2026-08-20-wdrozenie-wcag.md`
> - Task: `docs/roadmapa/T-246-wdrozenie-dostepnosc-wcag.md`
>
> **Wdrożenie nie orzeka o zgodności.** Orzeka reaudyt.

---

## 1. Skrót jednym akapitem

Z siedmiu niezgodności audytu **cztery zamknięte** (N-1 częściowo, N-3, N-5, N-6),
**dwie zakwestionowane jako nieistniejące** (N-4, N-7), **jedna niepotwierdzona** (N-2).
Pomiar axe na sześciu stronach spadł z **266 do 15** elementów z błędem, a wszystkie 15
to jeden powtórzony przypadek zachowany świadomą decyzją właściciela. Przejście klawiaturą
po katalogu: **17 → 0** elementów bez wskaźnika fokusu. Fokus zasłaniany banerem zgód:
**5 / 9 / 2 → 0 / 0 / 0**. Kryterium 2.1.2, spełnione przed wdrożeniem, pozostaje spełnione.

## 2. Stan kart

| Karta | Kryterium | Stan | Uwaga |
|---|---|---|---|
| **N-1** kontrast | 1.4.3 | 🟡 zamknięta poza jednym przypadkiem | patrz §3 — czerwień marki |
| **N-2** przeskok nagłówka | 1.3.1 | ⚪ niepotwierdzona | 0 przeskoków na 3 sprawdzonych stronach; prosimy o wskazanie, które 2 z 16 |
| **N-3** cele dotykowe | 2.5.8 | ✅ zamknięta | 9 → 0 |
| **N-4** etykieta wyszukiwarki | 3.3.2 | ❌ **zakwestionowana** | patrz §4 |
| **N-5** wskaźnik fokusu | 2.4.7 + 1.4.11 | ✅ zamknięta | 17 → 0 |
| **N-6** baner zasłania fokus | 2.4.11 | ✅ zamknięta | pętla fokusu, 2.1.2 zachowane |
| **N-7** przycisk logowania | 4.1.2 + 3.3.2 | ❌ **zakwestionowana** | patrz §4 |

## 3. Jedyna pozycja zachowana świadomie

**Czerwień marki `#D63031` jako tekst na jasnoszarym tle `#F5F6F8` daje 4,48 przy progu 4,50.**

W trakcie wdrożenia odcień został pogłębiony do `#C92A2B` (5,04) i wszystkie strony osiągnęły
zero zgłoszeń. Zmiana została **wycofana** przy przeglądzie wizualnym: obejmowała `--c-accent`,
czyli ceny, przyciski i linki w całym serwisie, a domykała kryterium przy różnicy **0,02 punktu**.
Właściciel serwisu ocenił koszt jako nieproporcjonalny i zachował odcień marki.

Prosimy odnotować jako **pozycję niezamkniętą z uzasadnieniem**, nie jako przeoczenie wykonawcy.
Dotyczy 15 elementów na 4 stronach próbki (`/`, `/samochody/{marka}/{model}/`, `/kontakt/`,
strona ogłoszenia).

## 4. Dwie karty do ponownego orzeczenia — dowód

Weryfikacja przed wdrożeniem wykazała, że **N-4 i N-7 opisują stan, którego nie ma**.

**Dowód nie pochodzi z narzędzia heurystycznego**, tylko z drzewa dostępności przeglądarki
(Chrome DevTools Protocol, `Accessibility.getFullAXTree`) — czyli z tego samego źródła,
z którego korzysta czytnik ekranu:

- `/samochody/` — **33 kontrolki, 0 bez nazwy dostępnej**
- `/klient/` — **8 kontrolek, 0 bez nazwy dostępnej**

| Karta | Twierdzenie audytu | Stan faktyczny |
|---|---|---|
| N-4 | „pole opisane wyłącznie `placeholder`, bez `<label>`, `aria-label` ani `aria-labelledby`" | `aria-label="Szukaj marki lub modelu"` — `class-asiaauto-inventory.php:203` |
| N-7 | „wyliczenie nazwy dostępnej zwraca pusty wynik" | `wp_login_form()` z `'label_log_in' => 'Zaloguj się'` → `value="Zaloguj się"` |

**To nie jest naprawa w międzyczasie.** Czasy modyfikacji plików są wcześniejsze niż audyt:
`class-asiaauto-shortcodes.php` — 04.08, `class-asiaauto-inventory.php` — 31.07, audyt — 13.08.

Prawdopodobna przyczyna: analiza statyczna nie rozwinęła wywołania `wp_login_form()`
i nie objęła atrybutu `aria-label` w generowanym HTML.

## 5. Korekta merytoryczna do zlecenia wykonawczego

Trzy rzeczy w zleceniu v2 wymagały poprawki — warte odnotowania dla przyszłych wdrożeń:

1. **Wskazana reguła CSS nie zadziałałaby.** `.aa-inv :where(a, button, …):focus-visible`
   ma specyficzność **(0,2,0)**, a gasi ją `.aa-inv input[type="text"].aa-price-inputs__input:focus`
   o specyficzności **(0,4,1)**. Przy równym `!important` rozstrzyga specyficzność — wskaźnik
   nie wróciłby. Wdrożono wariant o specyficzności (0,5,0).
2. **Skala N-5 większa niż w raporcie:** nie 9, lecz **17** kolejnych elementów bez wskaźnika.
3. **Wykaz miejsc niepełny:** zlecenie wymieniało 5 lokalizacji `outline: none`, faktycznie
   jest ich **7** (dodatkowo `asiaauto-payu.css:119` i `class-asiaauto-login.php:121`).

## 6. Pomiary — przed i po

### axe-core 4.10.2 (wcag2a, wcag2aa, wcag21a, wcag21aa, wcag22aa)

| Strona | Przed | Po |
|---|---|---|
| `/` | 70 | 7 |
| `/samochody/` | 81 | **0** |
| `/klient/` | 8 | **0** |
| `/oferta/{listing}/` | 61 | 1 |
| `/samochody/smart/5/` | 24 | 6 |
| `/kontakt/` | 22 | 1 |
| **Razem** | **266** | **15** |

Wszystkie 15 to przypadek z §3.

### Przejście klawiaturą (160 przystanków)

| Strona | Przed | Po |
|---|---|---|
| `/samochody/` | 17 bez wskaźnika | **0** |
| `/oferta/{listing}/` | — | **0** |
| `/zamow/?listing=` | — | **0** |

### Fokus zasłonięty banerem zgód (2.4.11)

| Strona | Przed | Po |
|---|---|---|
| `/` | 5 | **0** |
| `/samochody/` | 9 | **0** |
| `/oferta/{listing}/` | 2 | **0** |

### Kontrola regresji

- **2.1.2** — zachowane. Po akceptacji banera fokus swobodnie obchodzi stronę: 34 różne
  elementy na 40 naciśnięć Tab (home), 27 (katalog).
- **Reflow 320 px** — `scrollWidth` 320, brak przewijania poziomego, 0 elementów wystających.
- **Konsola** — 0 błędów JS. **Waga** — przyrost ok. 4,5 KB.

## 7. Zmienione pliki

Kopie `.bak-2026-08-20-a11y` obok każdego pliku + komplet w `~/backups/primaauto/2026-08-20/`.

**Wtyczka `asiaauto-sync`** (wyłącznie warstwa prezentacji; import, synchronizacja i logika
zamówień nietknięte):
`assets/css/asiaauto-inventory.css`, `asiaauto-order-wizard.css`, `asiaauto-single.css`,
`asiaauto-payu.css`, `includes/class-asiaauto-shortcodes.php`, `-inventory.php`, `-homepage.php`,
`-contact.php`, `-login.php`

**Motyw `primaauto2026`** (1.2.2 → 1.2.3):
`assets/css/base.css`, `footer.css`, `hub.css`, `assets/js/a11y-consent-focus.js` (nowy),
`functions.php`

**Świadomie poza zakresem:** `class-asiaauto-order-admin.php` (31 wystąpień koloru),
`class-asiaauto-contract.php` (16), `asiaauto-order-admin.css`, `class-asiaauto-admin.php` —
panel administracyjny i generator umowy PDF; audyt obejmował strony publiczne.
Oraz `class-asiaauto-order.php` i `class-asiaauto-cpt.php` — strefa krucha.

## 8. ⚠️ Zmiana interfejsu wykonana PO pomiarach — wymaga sprawdzenia w reaudycie

Po zamknięciu pomiarów dostępności, w tej samej sesji, wykonano **zmianę UX poza zakresem
audytu** (T-247). Może wpłynąć na wynik reaudytu na wąskich rzutniach:

- przycisk filtrów w pasku mobilnym `/samochody/`: „Filtry" → **„Marka / model"**, tło czerwone,
  biały napis (**4,85:1**), licznik aktywnych filtrów odwrócony na biały z czerwoną cyfrą (4,85:1)
- etykiety sortowania skrócone: „Cena: od najniższej/najwyższej" → **„Najtańsze" / „Najdroższe"**
- `min-width` selectu sortowania 130 → 112 px
- słowo „ofert" przy liczniku chowane poniżej 400 px (liczba pozostaje w całości)

**Do zweryfikowania w reaudycie:** reflow i brak utraty treści na 320 px w pasku mobilnym.
Pomiar po tej zmianie **nie został dokończony** po stronie wykonawcy — Chrome nie otrzymał
zasobów na koncie hostingowym. Przed zmianą licznik ofert **ucinał się na 320 px już w stanie
zastanym**, więc jest to obszar wymagający uwagi niezależnie od naszej modyfikacji.

## 9. Prompt do wklejenia w projekcie `auranet`

```
Reaudyt primaauto.com.pl po wdrożeniu poprawek dostępności z 20.08.2026.

Podsumowanie wykonawcze: ~/projekty/primaauto/docs/dostepnosc/2026-08-20-PODSUMOWANIE-DO-REAUDYTU.md
Raport pełny z dowodami:  ~/projekty/primaauto/docs/dostepnosc/2026-08-20-wdrozenie-wcag.md

Wykonaj reaudyt zgodnie z procedurą 7.4: ta sama próbka 16 adresów, ten sam baseline,
pełne 55 kryteriów ponownie (poprawka potrafi zepsuć inne kryterium), wydanie 2 raportu
ze statusem „końcowy", kolumny „przed" i „po" wypełnione.
Zaktualizuj wpis w docs/uslugi/rejestr-projektow-wcag.md.

Do rozstrzygnięcia w tym reaudycie:
1. N-4 i N-7 — wykonawca kwestionuje ich istnienie, dowód z drzewa dostępności CDP
   w §4 podsumowania. Prosimy o ponowne orzeczenie na podstawie renderowanego HTML,
   nie analizy statycznej.
2. N-2 — nie potwierdzony (0 przeskoków nagłówków na /, /samochody/ i stronie ogłoszenia).
   Prosimy wskazać, które 2 z 16 stron próbki go wykazywały.
3. N-1 — zamknięta poza jednym przypadkiem: czerwień marki #D63031 jako tekst na tle
   #F5F6F8 daje 4,48 przy progu 4,50. Właściciel świadomie zachowuje odcień marki.
   Prosimy odnotować jako pozycję niezamkniętą z uzasadnieniem.
4. Pasek mobilny /samochody/ — zmiana UX z 20.08 (§8), pomiar reflow 320 px
   niedokończony po stronie wykonawcy. Prosimy o weryfikację.
```
