# T-205 — plan wykonawczy (rewizja 2026-08-01)

> Kontekst i identyfikatory: [`T-205-meta-restart-nowe-konto.md`](T-205-meta-restart-nowe-konto.md)
> Token: `~/secrets/meta/tokens/primaauto-portfolio-ruslan.txt` · appka **`1533997951805022`** (nie stara `1012248818452132`)
> Konto rekl. `act_1038563008906171` · portfolio `1486783363131026` · Strona `1146829831857839`
> **Piksel `1634147041766916`** · **katalog `2936723456671912`** · feed `1398276155566551`
>
> **Godziny: 15–17 h na całość — 4 h wykonane 01.08, 11–13 h przed nami.**

---

## 1. Wykonane 2026-08-01 — 4 h

| # | Co | h |
|---|---|---|
| 1 | **Ekspertyza blokady** — blokada na poziomie aplikacji, nie tokena; odzyskiwanie było ślepą uliczką | 0,5 |
| 2 | **Instrukcja odbudowy dla Ruslana** — 10 kroków + pułapki (CEIDG co do znaku, nieodwracalna waluta i strefa, wybór drogi dostępu), wersja HTML | 1 |
| 3 | **Odbiór i weryfikacja dostępu** — sklejenie tokenu, test zakresu, inwentaryzacja zasobów | 0,5 |
| 4 | **Pomiar odzyskany** — nowy piksel, przepięcie GTM przez API (backup, wersja 10, publikacja), walidacja na CDN i na żywych zdarzeniach | 1 |
| 5 | **Katalog pojazdów** — katalog, feed 2517 aut, cron dobowy, harmonogram Meta, dwie poprawki generatora | 1 |

**Cel numer jeden osiągnięty:** przejęty piksel `2053908905193274` nie dostaje już danych z primaauto.com.pl. Potwierdzone: zniknął z `gtm.js` na CDN, a nowy piksel zapisał pierwsze zdarzenia w kilka minut po publikacji.

### Dwie usterki wyłapane przez pierwszy zaciąg — naprawione

1. **`mileage` puste przy przebiegu 0** — Meta traktuje przebieg jako wymagany, więc odpadały auta nowe, czyli trzon oferty. Teraz jawnie `0 KM`.
2. **Chińskie slugi mylące mapowanie** — część termów ma polską nazwę, ale slug został z chińskiego URL-encoded (importer stempluje `$api_value`), a generator czytał slug przed nazwą. „Jednobiegowa (EV)" i `汽油+48V轻混系统` wpadały w OTHER. Teraz `key2()` czyta slug i nazwę łącznie, z dekodowaniem.

### Stan otwarty z tej partii

Pierwszy zaciąg katalogu wpuścił 206/2517 i **wisi po stronie Meta od 08:07** bez `end_time`, blokując kolejne (`error_subcode 1798046`). Nie mamy na to wpływu — poprawiony plik wejdzie **harmonogramem o 6:30**.

---

## 2. Do zrobienia — 11–13 h

### 2.1 · Audiencje — 1 h ⬅ NASTĘPNY RUCH

Listy z piksela zbierają dane **od momentu utworzenia, nie wstecz**. Piksel ma dane dopiero od 01.08, więc każdy dzień zwłoki to ruch nie do odzyskania. Nie wymaga weryfikacji, karty ani zgody — **nie wydaje budżetu**.

| Audiencja | Definicja | Do czego |
|---|---|---|
| `WCA — wszyscy odwiedzający 180 d` | wszystkie odsłony | wykluczenie z prospectingu, baza LAL |
| `WCA — ViewContent 180 d` | oglądający konkretne auta | remarketing, rdzeń katalogu dynamicznego |
| `WCA — Contact + Lead 180 d` | telefon, WhatsApp, formularz | wykluczenie z górnego lejka, najwartościowsza baza LAL |
| `Engagement — Strona 365 d` | interakcje z nową Stroną | wykluczenie, baza LAL |

Lookalike 1% dopiero gdy źródło uzbiera sensowną próbę — z Contact+Lead, nie z ruchu ogólnego. **Bez PII** — lista mailowa wymagałaby podstawy prawnej, domyślnie nie.

### 2.2 · Domknięcie katalogu — 1 h

Pełny zaciąg po poprawkach, kontrola ile pozycji weszło i co odpadło, zestawy produktów pod osobne kampanie: elektryki, SUV-y, segment premium.

### 2.3 · Weryfikacja domeny + regulamin pikseli — 0,5 h

Oba wymagają panelu — API nie ma uprawnień (`owned_domains` → `#10`, tworzenie datasetów w portfolio → `1784018`). Dzisiejszy piksel przeszedł obejściem przez konto reklamowe, ale kolejnych tak nie założymy.

### 2.4 · Kreacje z 8 filmów — 2–3 h

Materiał gotowy na Drive (folder `1FMBKl7gj_hvo-imnTRXrGI51sBiHacM1`), cztery auta w dwóch formatach:

| Auto | Formaty |
|---|---|
| BYD Leopard 5 niebieski | pion 9:16 + poziom 16:9 |
| BYD Leopard 5 czarny | pion 9:16 + poziom 16:9 |
| Exeed VX | pion 9:16 + poziom 16:9 |
| Jetour T2 PHEV | pion 9:16 + poziom 16:9 |

- **Pionowe 9:16 idą pod Reels i Stories bez przeróbek** — to gotowy materiał, nie produkcja.
- Poziome 16:9 przyciąć do **4:5** pod feed (Meta faworyzuje pionowy kadr w feedzie).
- Do każdego: plansza końcowa z CTA, napisy (większość ogląda bez dźwięku), warianty tekstowe pod auto.

Nowe filmy będą kręcone — **produkcja to T-201 i T-216, nie ten task**. Tutaj startujemy z tym, co jest.

### 2.5 · Wytyczne publikacyjne dla Andrzeja i Ruslana — 0,5 h

Nowa Strona ma jeden post (29.07) — budujemy od zera, tak jak kiedyś fanpage. Andrzej i Ruslan wracają do regularnych publikacji ofert.

Nasza rola: rytm publikacji, formaty, co się nadaje pod promocję budżetem, czego unikać. **Ich posty budują listę odbiorców zaangażowanych** — darmowe paliwo pod lookalike i wykluczenia, bez wydawania na to budżetu.

### 2.6 · Kampania prospectingowa — 2 h

- Cel: wyświetlenia strony docelowej, nie zasięg.
- Targeting do reużycia: 8 miast + Śląsk, wiek 35–60, 4 grupy zainteresowań.
- **Minus** audiencje wykluczające z 2.1 — promocja ma trafiać do nowych ludzi.
- Kreacje z 2.4, UTM-y spójne z GA4.

### 2.7 · Katalog dynamiczny + remarketing — 2 h

- Reklamy pokazujące **konkretnie oglądane auto**, karmione katalogiem. W tej branży historycznie najmocniejszy format, a w Polsce działa — inaczej niż odpowiednik w Google.
- Warunek: `WCA — ViewContent` musi mieć dane, więc realnie kilka–kilkanaście dni po 2.1.
- Remarketing statyczny dla ruchu, który nie doszedł do karty auta.

### 2.8 · Start i pierwszy tydzień — 2–3 h

Odpalenie, kontrola dostarczania i kosztu, korekta budżetów i wykluczeń. Konto ma `min_daily_budget` 382 (3,82 PLN) — to techniczne minimum, nie rekomendacja; poprzednio planowane ~50 zł/dobę. Kwoty do ustalenia z Ruslanem przy starcie.

---

## 3. Poza zakresem tego taska

- **CAPI na nowym datasecie** (~2 h) — stary token pisze do przejętego datasetu, do wycofania. Nowy System User pozwala postawić zdarzenia serwerowe od nowa, ale podpięcie po stronie WP dotyka **strefy kruchej zamówień**. Decyzja Janka, osobno.
- **Produkcja nowych filmów** — T-201, T-216.

---

## 4. Tor R — po stronie Ruslana (blokery twarde)

| # | Co | Blokuje |
|---|---|---|
| R1 | Weryfikacja firmy — dane **1:1 z CEIDG** | R2 |
| R2 | **Weryfikacja UE / DSA** | 2.8 — bez niej nie powstanie żaden zestaw reklam |
| R3 | Karta płatnicza + limit wydatków | 2.8 |
| R4 | Konto Instagram + przypięcie do Strony | placementy IG, w tym Reels z gotowych pionów |
| R5 | Post zapraszający na starej Stronie (publikuje Andrzej, o ile ma tam managera) | zasięg startowy |
| R6 | Akceptacja regulaminu pikseli w portfolio | tworzenie kolejnych datasetów przez API |

**Uczciwa uwaga do R5:** stara Strona miała 16 676 obserwujących, ale **zasięg organiczny był martwy** — około 2 reakcje na post przy 16 tys. bazy. Utrata obserwujących boli mniej, niż sugeruje sama liczba; realną wartością były audiencje reklamowe, a te i tak trzeba odbudować.

---

## 5. Kolejność

1. **2.1 audiencje** — nie ma na co czekać, licznik zbierania biegnie
2. 2.2 domknięcie katalogu — po przebiegu o 6:30
3. 2.4 kreacje + 2.5 wytyczne — równolegle, niezależne od blokerów
4. 2.6 + 2.7 kampanie PAUSED
5. 2.3 panel — gdy będzie dostęp do interfejsu
6. 2.8 start — po R2 i R3

## 6. Definicja zrobionego

- [x] Zdarzenia lecą do naszego datasetu, stary piksel nie dostaje nic
- [x] Katalog pojazdów zasilany cronem
- [ ] Pełny zaciąg katalogu potwierdzony po poprawkach
- [ ] Audiencje utworzone i zbierają dane
- [ ] Kreacje z 8 filmów gotowe pod formaty Meta
- [ ] Andrzej i Ruslan mają wytyczne publikacyjne
- [ ] Kampanie zbudowane i zwalidowane, w stanie PAUSED
- [ ] Domena zweryfikowana, regulamin pikseli zaakceptowany
- [ ] Ruslan przeszedł weryfikację UE i podpiął płatność
- [ ] Ruslan wie, że obserwujący i audiencje startują od zera
