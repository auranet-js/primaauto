# Recheck Meta — 2026-09-07

> Recheck umówiony na 05.09 nie odbył się, więc odczyt obejmuje **~3 doby** od startu
> (04.09 17:52) i dwie **zamknięte** doby: 05.09 i 06.09. Wszystkie liczby ze świeżego
> odczytu Graph API i Data API GA4 — nie z dashboardu.
> Poprzedni stan: `docs/sesje/2026-09-04-PROMPT-meta-kampanie-kontynuacja.md`.

## 1. Sześć reklam — okres 04–07.09 (07.09 doba niepełna)

| Reklama | wyśw. | kliki | w link | CTR | zł/klik w link | koszt | LPV | ViewContent |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| `[VID] z9-gt` | 3 933 | 566 | 270 | 14,39% | 0,18 | 49,07 | 39 | 90 |
| `[VID] leopard-5-czarny` | 1 408 | 163 | 79 | 11,58% | 0,21 | 16,43 | 4 | 33 |
| `[FOTO] Cała oferta — kadr 1` | 4 510 | 223 | 194 | 4,94% | 0,16 | 30,73 | 61 | 105 |
| `[FOTO] Cała oferta — kadr 2` | 1 068 | 54 | 46 | 5,06% | 0,17 | 7,70 | 9 | 13 |
| `[POST] Denza Z9 DM-i` | 3 593 | 168 | 55 | 4,68% | 0,28 | 15,57 | 30 | — |
| `[POST] Mazda EZ-6` | 3 918 | 89 | 39 | 2,27% | 0,29 | 11,19 | 20 | — |
| **razem** | **18 430** | **1 263** | **683** | **6,85%** | **0,19** | **130,69** | **163** | **241** |

Bez alarmów: żadna reklama nie ma `DISAPPROVED`, `WITH_ISSUES` ani `PENDING_BILLING_INFO`.
Sumy zawierają dwie reklamy zarchiwizowane wieczorem 04.09 przy podmianie tekstów (kilka
złotych) — dlatego suma zgadza się z wydatkiem konta co do grosza.

**Co z tego widać, w obrębie kampanii:**

- `[VID]`: `z9-gt` bije `leopard-5-czarny` na każdej metryce — CTR 14,4% vs 11,6%, a przy
  podobnym koszcie kliknięcia daje 39 wejść na stronę wobec 4. Leopard zbiera obejrzenia
  wideo (666) i nie przenosi ich na ruch: 79 kliknięć w link → 4 wczytania strony.
  Meta sama przesunęła budżet — 06.09 leopard dostał 239 wyświetleń, z9-gt 1 901.
- `[FOTO]`: kadr 1 dowozi (61 wejść, 105 ViewContent, 0,50 zł za wejście), kadr 2 ma ten sam
  CTR, ale trzy razy mniej wyświetleń i wyraźnie gorszą konwersję kliknięcia na wejście
  (46 → 9). Meta wybrała kadr 1.
- `[POST]`: najtańszy zasięg, najdroższe kliknięcie w link (0,28–0,29 zł) i najgorsze
  przełożenie na sesje w GA4 (niżej). Mazda ma CTR 2,27% — najsłabszy wynik z sześciu.

## 2. Budżet — dni pod sufitem

Wydane **130,65 zł** z limitu 1 000 zł, zostało **869 zł**. Tempo z dwóch zamkniętych dób:
58,32 zł (05.09) i 47,57 zł (06.09), średnio **52,95 zł/dobę** — czyli Meta wybiera
zadeklarowane 50 zł/dz w całości (przekroczenie 05.09 to normalne dobowe rozchwianie,
Meta bilansuje tygodniowo).

**Do sufitu ~16,4 doby → limit pęka około 23–24.09.** Dostawa nie kuleje; to jest realny
horyzont kampanii, nie oszczędność.

## 3. Czy UTM-y doszły do GA4 — TAK

`facebook / paid_social` jest w GA4 z nazwą kampanii i nazwą reklamy. Pierwsza doba:
**04.09** (1 sesja, `[FOTO] Cała oferta — kadr 2` — czyli tuż po wpięciu `url_tags`),
pełna dostawa od **05.09**.

| Doba | sesje `facebook / paid_social` |
|---|---:|
| 04.09 | 1 |
| 05.09 | 59 |
| 06.09 | 88 |
| 07.09 (niepełna) | 7 |
| **razem** | **155** |

`utm_kreacje.py --lista`: **0 z 6 reklam bez UTM-ów** — tagi trzymają się wszystkich sześciu,
także dwóch promowanych postów Andrzeja.

Obok stoi ruch `l.facebook.com`, `lm.facebook.com`, `m.facebook.com`, `facebook.com` jako
`referral` (24 + 24 + 8 + 23 sesji) — to organiczne posty, UTM-ów nie mają i mieć nie będą.
Pojawił się też `fb / paid` (5 sesji) — cudze tagowanie, nie nasze kreacje.

## 4. Meta ↔ GA4 na zamkniętych dobach

| Doba | kampania | kliknięcia w link (Meta) | sesje (GA4) | pokrycie |
|---|---|---:|---:|---:|
| 05.09 | `[VID]` | 156 | 33 | 21% |
| 05.09 | `[FOTO]` | 104 | 24 | 23% |
| 05.09 | `[POST]` | 47 | 2 | 4% |
| 06.09 | `[VID]` | 140 | 49 | 35% |
| 06.09 | `[FOTO]` | 85 | 35 | 41% |
| 06.09 | `[POST]` | 26 | 4 | 15% |

**Pomiar działa.** `[VID]` i `[FOTO]` mieszczą się w normie (20–41% przy oczekiwanych 20–40%),
a 06.09 wygląda lepiej niż 05.09, bo pierwsza pełna doba łapie jeszcze ogon sesji sprzed
wpięcia tagów.

**`[POST]` odstaje i to jest wniosek dnia.** 94 kliknięcia w link przez trzy doby dały
9 sesji w GA4, przy czym sama Meta raportuje dla tych reklam 50 wczytań strony. Najbardziej
prawdopodobne wyjaśnienie: w promowanym poście Meta liczy jako „kliknięcie w link" także
kliknięcia rozwijające post, nazwę Strony i permalink — nie tylko wyjście na stronę. To nie
jest dziura w tagowaniu (adContent `[POST] …` widać w GA4), tylko inna definicja kliknięcia.
Praktyczny wniosek: **CPC `[POST]` nie jest porównywalne z CPC pozostałych kampanii nawet
wewnątrz metryki „link"** — porównuj po sesjach GA4.

## 5. Jakość ruchu i jedyny kontakt

GA4, 04–07.09, `facebook / paid_social`: **155 sesji, zaangażowanie 82,6%**, średni czas
2:55, 2,62 odsłony na sesję. Dla porównania `google / cpc` w tym samym okresie: 406 sesji,
zaangażowanie 58,1%, 2:53, 3,25 odsłony. **Ruch z Mety nie jest śmieciowy** — angażuje się
lepiej niż płatne wyszukiwanie, ogląda porównywalnie dużo.

Konwersją jest wyłącznie kontakt (telefon / WhatsApp / formularz). Przez cztery doby
i 130,65 zł Meta dowiozła **1 kliknięcie w WhatsApp** (z `[FOTO] Cała oferta — kadr 1`),
zero telefonów, zero formularzy. Przy 155 sesjach to jest liczba w granicach szumu —
za mało, żeby cokolwiek na niej rozstrzygać, ale też żaden dowód, że kanał sprzedaje.

**Uwaga poboczna, do naprawy przy okazji:** GA4 nie obcina `fbclid`, więc raport stron
docelowych dla ruchu z Mety rozsypuje się na unikaty (`/oferta/denza-z9-dm-i-2025-447002/?fbclid=…`
w czterech wariantach). Do policzenia, które oferty łapią ruch, trzeba czyścić parametr —
albo w GA4 („niechciane parametry zapytania"), albo po stronie zapytania.

## 6. Propozycje następnych ruchów

Kolejność wynika z tego, co pokazał odczyt, nie z listy życzeń.

1. **Kampania na WhatsApp — pierwsza w kolejce.** Meta dowiozła 155 zaangażowanych sesji
   i jeden kontakt; w Google Ads WhatsApp to najlepszy kanał kontaktu (43 konwersje/30 dni).
   Konto ma `WHATSAPP_DESTINATION_ADS`, czyli reklama może prowadzić prosto do rozmowy,
   z pominięciem strony i całego odpadu na wejściu (683 kliknięcia → 155 sesji). To jedyny
   ruch, który realnie zmienia wynik, a nie tylko przesuwa ruch.
2. **Remarketing dynamiczny z katalogu** — 2 990 aut, feed dobowy bez błędów, piksel zbiera
   (241 ViewContent w cztery doby, czyli pula rośnie). Najtańsze źródło kontaktu, bo mówi do
   ludzi, którzy już widzieli konkretne auto.
3. **Grupy z obejrzenia wideo — ręcznie w Menedżerze.** `[VID]` wyprodukowało 2 531 obejrzeń
   wideo; `leopard-5-czarny` przy 666 obejrzeniach dowiózł 4 wejścia na stronę, więc jego
   wartość leży w budowaniu grupy, nie w ruchu. API odbija każdą wersję reguły (`1870049`),
   robimy ręcznie, Meta wypełni wstecz 365 dni.
4. **Czyszczenie `fbclid` w GA4** — bez tego nie policzymy, które oferty z reklam faktycznie
   ciągną ruch. Robota na kwadrans.

**Czego nie proponuję i dlaczego:** wyłączania `leopard-5-czarny` ani `kadr 2` — Meta sama
przesunęła im budżet w dół, a przy kilku tysiącach wyświetleń wyłączenie kreacji to decyzja
podjęta na szumie. Zmian budżetów też nie: temat `[POST]` 10 → 15 zł jest zamknięty przez
Janka 04.09, a łączne 50 zł/dz daje sensowne 16 dni pod sufitem.

## Do decyzji Janka

Nic nie zostało zmienione na koncie — recheck był czytaniem. Do rozstrzygnięcia zostaje,
czy ruszamy z WhatsAppem (punkt 1) i czy przy okazji budujemy remarketing z katalogu (2).
