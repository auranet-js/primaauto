# Recheck Meta po restarcie konta + wiek + skąd telefony (raport z 04.10.2026)

> Raport zbiera trzy zaległe przypomnienia z kalendarza „Auranet Claude”:
> **25.09** „Recheck Meta po restarcie — okna 15–17.09 vs 22–24.09” (`docs/przypomnienia/2026-09-25-recheck-meta-po-restarcie.md`),
> **19.09** „Wiek w [KAT] i [RMKT]”,
> **18.09** „Skąd 15 kontaktów z 15.09”.
> Zakres rozszerzyłem do 03.10, czyli ostatniej pełnej doby. Kampanie tylko czytałem: statusy, budżety, targetowanie, kreacje i GTM są nietknięte.
> Odczyt: 04.10.2026, ok. 11:30.

## Wniosek w jednym zdaniu

Restart przywrócił konto do pracy i od 22.09 Meta dowozi stabilnie, ale **nie poprawił kosztu wejścia**.
Koszt sesji GA4 wynosi 0,36–0,37 zł, tyle samo co w trzech dobach przed postojem. Wzrosła tylko skala.
Kontaktów z Mety w GA4 jest mało: **6 w 12 dobach za 854 zł**. `[KAT]` zaczyna się wypalać,
bo zasięg spada, a częstotliwość rośnie. `[FOTO]` przestało się dławić, ale wejście kosztuje tam trzy razy więcej niż w `[KAT]`.

## 1. Stan konta i dostawy (punkt 2a)

| Sprawdzane | Wynik |
|---|---|
| Reklamy | 4 × `ACTIVE`, zero `DISAPPROVED` / `WITH_ISSUES` (`recheck_start.py`) |
| Konto | `account_status: 1`, `disable_reason: 0` |
| Limit wydatków | `amount_spent` 208,81 zł, `spend_cap` 1 800 zł, `balance` 85,92 zł |
| Ruchy na koncie | 30.09 obciążenie 500 zł; **01.10 11:04 `ad_account_reset_spend_limit`** (licznik wyzerowany, po stronie Ruslana); 03.10 obciążenie 144,95 zł |
| Dostawa | **nie stoi**: każda doba 21.09–03.10 ma wydatek 53–97 zł (tabela trendu niżej), 04.10 do 11:30 już 25,54 zł |
| Budżety zestawów | `[KAT]` 35 zł, `[RMKT]` 15 zł, `Karuzele — Contact` 15 zł, razem 65 zł/dz |

Limit podaję jako fakt. Zgodnie z regułą nie liczę, kiedy się skończy.
Doby 22, 24 i 25.09 wydały 85–97 zł przy 65 zł budżetu. Meta tak odrabia dzień po restarcie, to dozwolone przekroczenie dzienne, a nie błąd.

## 2. Tabela per reklama: 22–24.09 (po restarcie)

GA4 za te doby jest domknięte, bez przypisania zostało 0,4–0,8% sesji. Kontakty GA4 to `click_phone` + `click_whatsapp`
z `facebook / paid_social`, rozbite po `utm_content`. Contact (piksel) to `fb_pixel_custom` w domyślnej atrybucji Mety.

| Reklama | Wydane | Wyśw. | Częst. | CTR w link | Klik. w link | zł/klik | LPV Meta | Sesje GA4 | zł/sesja GA4 | Zaang. GA4 | ViewContent | Contact (piksel) | Kontakty GA4 |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| `[KAT] Oferta — nowi odbiorcy` | 136,57 zł | 10 685 | 2,45 | 14,13% | 1 510 | 0,09 zł | 758 | 481 | **0,28 zł** | 313 | 883 | 2 | 2 |
| `[FOTO] Cała oferta — kadr 1` | 12,04 zł | 703 | 1,16 | 5,97% | 42 | 0,29 zł | 12 | 14 | 0,86 zł | 11 | 68 | 1 | 0 |
| `[FOTO] Cała oferta — kadr 2` | 43,81 zł | 2 523 | 1,53 | 4,68% | 118 | 0,37 zł | 33 | 54 | 0,81 zł | 42 | 50 | 1 | 1 |
| `[RMKT] Oglądane — 30 dni` | 56,89 zł | 2 892 | 2,08 | 15,94% | 461 | 0,12 zł | 296 | 151 | 0,38 zł | 88 | 332 | 0 | 0 |
| **Razem** | **249,31 zł** | 16 803 | — | 12,68% | 2 131 | 0,12 zł | 1 099 | **700** | **0,36 zł** | 454 | 1 333 | 4 | 3 |

## 3. Te same wiersze dla 15–17.09 (baseline sprzed postoju) i różnica

W baseline doba 15.09 obejmuje jeszcze stare reklamy, aktywne przed przebudową o 17:15:
`[VID] z9-gt` 15,38 zł, `[POST] Denza` 7,28 zł i `[RMKT] Na placu` 2,94 zł. W sumie to 25,60 zł i 32 sesje.
Suma Meta z tych trzech dób to 160,93 zł. Kwota 185,84 zł z przypomnienia zawiera jeszcze ułamek 18.09.

| Reklama | Wydane 15–17.09 | Wydane 22–24.09 | Sesje GA4 15–17 → 22–24 | zł/sesja 15–17 → 22–24 | CTR w link 15–17 → 22–24 | Kontakty GA4 15–17 → 22–24 |
|---|---:|---:|---:|---:|---:|---:|
| `[KAT]` | 78,62 zł | 136,57 zł (+74%) | 243 → 481 | 0,32 → **0,28 zł** | 8,70% → 14,13% | 6 → 2 |
| `[FOTO]` kadr 1 | 11,39 zł | 12,04 zł | 33 → 14 | 0,35 → 0,86 zł | 4,04% → 5,97% | 0 → 0 |
| `[FOTO]` kadr 2 | 3,26 zł | 43,81 zł | 3 → 54 | 1,09 → 0,81 zł | 3,31% → 4,68% | 0 → 1 |
| `[RMKT]` Oglądane | 42,06 zł | 56,89 zł | 127 → 151 | 0,33 → 0,38 zł | 11,98% → 15,94% | 0 → 0 |
| stare (VID/POST/Na placu) | 25,60 zł | — | 32 → — | — | — | 0 → — |
| **Razem** | **160,93 zł** | **249,31 zł (+55%)** | **438 → 700 (+60%)** | **0,37 → 0,36 zł** | 8,08% → 12,68% | **6 → 3** |

**Odpowiedź na pytanie z przypomnienia:** przebudowa z 15.09 **odbudowała skalę, nie poprawiła kosztu wejścia**.
Jednostkowo jest płasko, 0,37 i 0,36 zł za sesję. Wobec fazy C z 12–14.09, czyli 0,59 zł/sesja, poprawa nadal się trzyma.
Przy 55% większym wydatku kontakty GA4 z Mety spadły z 6 do 3. Przy tak małych liczbach to szum, ale wzrostu z pewnością nie widać.

## 4. Rozszerzenie do dziś: 22–27.09 vs 28.09–03.10 (dwa okna po 6 pełnych dób)

**22–27.09**

| Reklama | Wydane | Wyśw. | Częst. | CTR w link | Klik. | zł/klik | LPV | Sesje GA4 | zł/sesja | Zaang. GA4 | VC | Contact | Kontakty GA4 |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| `[KAT]` | 249,17 zł | 19 504 | 3,39 | 14,15% | 2 760 | 0,09 zł | 1 408 | 889 | **0,28 zł** | 574 (65%) | 1 640 | 2 | 2 |
| `[FOTO]` kadr 1 | 59,77 zł | 3 356 | 1,43 | 5,87% | 197 | 0,30 zł | 52 | 59 | 1,01 zł | 51 | 121 | 1 | 0 |
| `[FOTO]` kadr 2 | 54,34 zł | 3 012 | 1,59 | 4,45% | 134 | 0,41 zł | 37 | 62 | 0,88 zł | 49 | 55 | 1 | 1 |
| `[RMKT]` | 108,22 zł | 5 701 | 2,75 | 16,21% | 924 | 0,12 zł | 567 | 308 | 0,35 zł | 190 (62%) | 662 | 1 | 0 |
| **Razem** | **471,50 zł** | 31 573 | — | 12,72% | 4 015 | 0,12 zł | 2 064 | 1 318 | **0,36 zł** | 864 | 2 478 | 5 | 3 |

**28.09–03.10**

| Reklama | Wydane | Wyśw. | Częst. | CTR w link | Klik. | zł/klik | LPV | Sesje GA4 | zł/sesja | Zaang. GA4 | VC | Contact | Kontakty GA4 |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| `[KAT]` | 207,18 zł | 15 480 | 3,73 | 13,53% | 2 095 | 0,10 zł | 1 195 | 704 | **0,29 zł** | 371 (53%) | 1 381 | 3 | 3 |
| `[FOTO]` kadr 1 | 80,83 zł | 4 680 | 1,80 | 5,41% | 253 | 0,32 zł | 69 | 94 | 0,86 zł | 63 | 69 | 0 | 0 |
| `[FOTO]` kadr 2 | 8,23 zł | 507 | 1,29 | 4,14% | 21 | 0,39 zł | 6 | 6 | 1,37 zł | 3 | 35 | 0 | 0 |
| `[RMKT]` | 86,64 zł | 4 275 | 2,42 | 14,08% | 602 | 0,14 zł | 410 | 240 | 0,36 zł | 120 (50%) | 525 | 1 | 0 |
| **Razem** | **382,88 zł** | 24 942 | — | 11,91% | 2 971 | 0,13 zł | 1 680 | 1 044 | **0,37 zł** | 557 | 2 010 | 4 | 3 |

**Zastrzeżenie:** GA4 nie przypisało jeszcze 63,4% sesji z doby 03.10 (sobota), patrz punkt 8. Sesje GA4 w drugim oknie są przez to zaniżone.
Bez 03.10 porównanie wygląda tak: 22–27.09 to 471,50 zł / 1 319 sesji = **0,36 zł**, a 28.09–02.10 to 320,95 zł / 899 sesji = **0,36 zł**. Koszt jednostkowy się nie zmienił.

**Zasięg i częstotliwość per zestaw (okna 6-dobowe):**

| Zestaw | Zasięg 22–27.09 | Zasięg 28.09–03.10 | Częst. | Wydane |
|---|---:|---:|---:|---:|
| `[KAT]` | 5 751 | **4 150 (−28%)** | 3,39 → **3,73** | 249,17 → 207,18 zł |
| `Karuzele — Contact` (`[FOTO]`) | 3 563 | 2 744 | 1,79 → 1,89 | 114,11 → 89,06 zł |
| `[RMKT]` | 2 070 | 1 763 | 2,75 → 2,42 | 108,22 → 86,64 zł |

Za całe 12 dób `[KAT]` ma częstotliwość **4,56** przy zasięgu 7 673 osób. Ten sam katalog dociera do mniejszej grupy coraz częściej.
Zaangażowanie GA4 w `[KAT]` spadło z 65% do 53%, a CTR z 14,15% do 13,53%. Koszt sesji jeszcze się trzyma, ale jakość ruchu już spada.

## 5. Na co patrzeć per kampania (punkt 2c)

**`[KAT]`** daje najtańsze wejście na koncie: 0,28–0,29 zł za sesję, 456,35 zł w 12 dobach, 5 z 6 kontaktów GA4 z Mety.
Wydatek rozkłada się na **co najmniej 500 aut**. Odczyt `breakdowns=product_id` ucina się na pierwszej stronie 500 wierszy
i przypisuje produkt tylko 139,27 zł z 456,35 zł, więc pełnej liczby aut nie podaję. Najczęściej klikane:
Zeekr 7X (82), Ford EVOS (73), Hongqi HS7 PHEV (59), Hongqi H5 (48), Changan UNI-V (44+24), Geely Galaxy Starship 8 (37), Monjaro (36+28).
Facebook bierze 449,12 zł, Instagram 7,23 zł (1,6%). Na mężczyzn idzie 96,4% wydatku.

**`[FOTO]` (`Karuzele PL M 30-60 — Contact`)** już się nie dławi: 203,17 zł w 12 dobach, czyli 16,9 zł/dz przy budżecie 15 zł.
Zgrzyt z 16.09 (22% realizacji) jest zamknięty. Wejście kosztuje tu jednak **0,92 zł za sesję, ponad trzy razy drożej niż w `[KAT]`**.
Zestaw jest optymalizowany na Contact, a za 203 zł dał 2 Contact w pikselu i 1 kontakt GA4 (kadr 2, WhatsApp, 22–24.09).
W drugim oknie Meta sama przerzuciła budżet z kadru 2 na kadr 1: kadr 2 wydał 8,23 zł, kadr 1 80,83 zł.
`learning_stage_info` nadal zwraca puste pole.

**`[RMKT]` przy 15 zł** wypada dobrze: 0,35 zł/sesja, CTR w link 14–16%, najwyższy na koncie, częstotliwość 3,28 w 12 dobach (alarm dopiero od 4).
Ma 0 kontaktów GA4 i 2 Contact w pikselu.

## 6. Trend dzienny (punkt 2d)

| Doba | Wydane | Wyśw. | Klik. w link | LPV | Sesje GA4 `facebook / paid_social` | zł/sesja | Contact (piksel) | Kontakty GA4 (cała strona, tel + WA) |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| 12.09 | 61,34 zł | 7 486 | 390 | 184 | 111 | 0,55 zł | 0 | 6 |
| 13.09 | 78,22 zł | 8 867 | 395 | 197 | 124 | 0,63 zł | 0 | 7 |
| 14.09 | 67,65 zł | 8 051 | 336 | 159 | 86 | 0,79 zł | 0 | 4 |
| **15.09: przebudowa (17:15)** | 70,81 zł | 9 527 | 554 | 188 | 168 | 0,42 zł | 3 | 21 |
| 16.09 | 44,06 zł | 5 675 | 520 | 172 | 117 | 0,38 zł | 1 | 12 |
| 17.09 | 54,42 zł | 5 624 | 563 | 184 | 154 | 0,35 zł | 0 | 11 |
| 18.09: stop o 12:00 | 16,55 zł | 1 429 | 148 | 60 | 39 | — | 0 | 17 |
| 19.09: postój | 0 | 0 | 0 | 0 | 2 | — | 0 | 11 |
| 20.09: postój | 0 | 0 | 0 | 0 | 0 | — | 0 | 18 |
| 21.09: restart ok. 17:00 | 53,19 zł | 4 551 | 469 | 209 | 139 | 0,38 zł | 0 | 8 |
| 22.09 | 88,15 zł | 6 570 | 797 | 385 | 256 | 0,34 zł | 1 | 14 |
| 23.09 | 64,26 zł | 4 190 | 547 | 274 | 173 | 0,37 zł | 2 | 17 |
| 24.09 | 96,90 zł | 6 043 | 787 | 440 | 271 | 0,36 zł | 1 | 19 |
| 25.09 | 84,82 zł | 5 555 | 692 | 374 | 242 | 0,35 zł | 1 | 15 |
| 26.09 (sob.) | 66,49 zł | 4 422 | 495 | 245 | 183 | 0,36 zł | 0 | 13 |
| 27.09 (niedz.) | 70,88 zł | 4 793 | 697 | 346 | 194 | 0,37 zł | 0 | 10 |
| 28.09 | 55,56 zł | 3 624 | 459 | 282 | 163 | 0,34 zł | 2 | 10 |
| 29.09 | 64,77 zł | 3 990 | 444 | 242 | 150 | 0,43 zł | 0 | 28 |
| 30.09 | 61,80 zł | 4 054 | 436 | 212 | 156 | 0,40 zł | 0 | 18 |
| 01.10 | 66,59 zł | 4 365 | 536 | 349 | 210 | 0,32 zł | 0 | 16 |
| 02.10 | 72,23 zł | 4 891 | 630 | 319 | 220 | 0,33 zł | 2 | 17 |
| 03.10 (sob., GA4 niedomknięte) | 61,93 zł | 4 018 | 466 | 276 | 146* | — | 0 | 12 |

\* GA4 nie przypisało jeszcze 63,4% sesji z 03.10.

Postój 18–21.09 nie wpłynął na liczbę kontaktów na stronie: 19–20.09 było 11 i 18 kontaktów. **Strona dzwoni niezależnie od Mety.**

## 7. Wiek w `[KAT]` i `[RMKT]` (przypomnienie z 19.09)

Doby 17–18.09, które miały rozstrzygnąć sprawę, przepadły w postoju. Dlatego rozkład liczę na 12 pełnych dobach po restarcie, 22.09–03.10.

| Zestaw | Wiek | Wydane | Udział | zł/klik | zł/LPV | Contact |
|---|---|---:|---:|---:|---:|---:|
| `[KAT]` | 25–34 | 75,16 zł | 16% | 0,106 | 0,24 | 1 |
| `[KAT]` | 35–44 | 160,81 zł | 35% | 0,105 | 0,21 | 2 |
| `[KAT]` | 45–54 | 138,30 zł | 30% | 0,088 | 0,15 | 2 |
| `[KAT]` | 55–64 | 56,30 zł | 12% | 0,071 | **0,12** | 0 |
| `[KAT]` | **65+** | 25,78 zł | 6% | **0,103** | **0,23** | 0 |
| `[RMKT]` | 18–24 | 2,89 zł | 1% | 0,074 | 0,22 | 0 |
| `[RMKT]` | 25–34 | 18,84 zł | 10% | 0,142 | 0,29 | 0 |
| `[RMKT]` | 35–44 | 68,65 zł | 35% | 0,134 | 0,23 | 1 |
| `[RMKT]` | 45–54 | 65,23 zł | 33% | 0,114 | **0,15** | 1 |
| `[RMKT]` | 55–64 | 25,93 zł | 13% | 0,146 | 0,22 | 0 |
| `[RMKT]` | 65+ | 13,32 zł | 7% | 0,146 | 0,28 | 0 |

**Zmiana wobec 17.09:** w `[KAT]` 65+ kosztowało wtedy 0,90 zł/LPV, czyli 4,5 razy więcej niż średnia, przy 34 kliknięciach.
Na 251 kliknięciach wychodzi **0,23 zł/LPV**, prawie tyle co 25–34 (0,24) i 35–44 (0,21).
Wynik z 17.09 był efektem małej próby. `[RMKT]` 18–24 nie jest już zerowy: 13 wejść po 0,22 zł, a cała grupa kosztuje 2,89 zł.

**Rekomendacja: nie ciąć wieku w żadnym zestawie.** Żaden przedział nie odstaje na tyle, żeby warto było restartować fazę uczenia.
Najtańsze wejście dają 45–64 w obu zestawach.
Rozjazd nazwy `[KAT] PL M 30-60` z ustawieniem 25–65 zostaje do kosmetyki, jeśli Janek zechce. Nazwy nie zmieniałem.

## 8. Skąd telefony 15–16.09 (przypomnienie z 18.09: domknięcie tabeli źródeł)

GA4 domknęło już przypisanie tych dób. 16.09 o 12:00 nie miało przypisanego źródła 71% sesji.
Liczby zdarzeń dojrzały do **21 kontaktów 15.09** (12 tel. + 9 WA) i **12 kontaktów 16.09**. W raporcie z 16.09 było 15 i 5, ale wtedy doby nie były domknięte.

| Źródło / medium | 15.09 tel. | 15.09 WA | 16.09 tel. | 16.09 WA |
|---|---:|---:|---:|---:|
| `(direct) / (none)` | 4 | 3 | 2 | 0 |
| `google / organic` | 4 | 2 | 2 | 2 |
| `facebook / paid_social` (`[KAT]`) | 1 | 3 | 1 | 1 |
| `youtube.com / referral` | 1 | 1 | 1 | 0 |
| `google / cpc` (`[Brand] Prima-Auto`) | 1 | 0 | 1 | 1 |
| `lm.facebook.com / referral` | 0 | 0 | 1 | 0 |
| `(not set)` | 1 | 0 | 0 | 0 |
| **Razem** | **12** | **9** | **8** | **4** |

**Rozstrzygnięcie:** skok z 15.09 zrobiły głównie wejścia bezpośrednie i organiczne z Google: 13 z 21.
Meta `[KAT]` dała 4. Ustalenie z 16.09 („nie przez przebudowę Mety”) zostaje potwierdzone.

**Trend kontaktów na całej stronie** (`click_phone` + `click_whatsapp` + `generate_lead`, tygodnie ISO):

| Tydzień | Kontakty | Na dobę | Organic Search | Organic Video (YouTube) | Paid Search | Paid Social (Meta) |
|---|---:|---:|---:|---:|---:|---:|
| 18–23.08 (6 dób) | 56 | 9,3 | 36 | 0 | 0 | 0 |
| 24–30.08 | 55 | 7,9 | 30 | 1 | 4 | 0 |
| 31.08–06.09 | 62 | 8,9 | 28 | 0 | 14 | 1 |
| 07–13.09 | 73 | 10,4 | 34 | 3 | 9 | 0 |
| 14–20.09 | 96 | 13,7 | 42 | 9 | 12 | 6 |
| 21–27.09 | 97 | 13,9 | 47 | 11 | 8 | 3 |
| 28.09–02.10 (5 dób) | 90 | **18,0** | 44 | 15 | 9 | 3 |

Kontakty rosną: z ok. 8–9 na dobę w sierpniu do 18 w ostatnim tygodniu. Wzrost przychodzi z wyszukiwania organicznego i z YouTube (0 → 15 na tydzień).
**Meta to 3% kontaktów**, 3 z 90 i 3 z 97.
Liczba kontaktów GA4 z Mety jest zgodna z memory: zdarzenie `click` (linki wychodzące) nie jest liczone jako kontakt.
Piksel po naprawie GTM v15 działa na całej stronie, nie tylko na `/oferta/`.

Soboty 26.09 i 03.10 mają **0 kliknięć w telefon** (WhatsApp normalnie, 13 i 12). To wzór weekendowy, nie awaria.

## 9. Pomiar: TAK/NIE z dowodem (punkt 2e)

| Sprawdzane | Wynik | Dowód |
|---|---|---|
| GTM wersja live | **TAK, 15** | `versions:live` → `15` „Meta Pixel — Base raz na zdarzenie + strażnik init” |
| ViewContent poniżej ~50% PageView | **TAK** | piksel, ostatnia doba: PV 2 999, VC 1 261 (**42%**), Contact 16 |
| Checki pomiaru | **12 z 13** | `checki-pomiaru.py` → jedyne NIE: „GA4 — źródła przypisane (doba zamknięta)”: 63,4% bez przypisania za 03.10 |
| Budżet dzienny konta = 65 zł | **TAK** | 35 + 15 + 15 |
| UTM-y na żywych kreacjach | **TAK, 4 z 4** | check „Meta — UTM-y na kreacjach” |
| `utm_content` = nazwa reklamy w GA4 | **TAK** | sesje w tabelach powyżej przypisane po nazwach reklam |

Brak przypisania za 03.10 to jedyny alarm. Doby 12.09–02.10 miały 0,1–1,3% sesji bez przypisania, więc 63% to wartość nietypowa.
Zdarzenia za 03.10 spływają normalnie: 2 165 PV, 12 WA. Najpewniej to opóźnienie przetwarzania GA4 w weekend.
**Niezweryfikowane.** Jeśli 05.10 nadal zobaczymy ponad 10%, sprawa jest do zbadania.

## 10. Propozycje wymiany kreacji: tylko zgłoszenie, nic nie wykonane

| Reklama | Co mówi liczba | Propozycja |
|---|---|---|
| `[FOTO]` kadr 2 | Meta sama ją wygasza: 8,23 zł w ostatnich 6 dobach, 1,37 zł/sesja | wymienić na nowy kadr albo zostawić samą kadrę 1 |
| `[FOTO]` cały zestaw | 0,92 zł/sesja wobec 0,29 zł w `[KAT]`, 1 kontakt GA4 za 203 zł | patrz decyzja 1 |
| `[KAT]` | zasięg −28% tydzień do tygodnia, częstotliwość 4,56 w 12 dobach, zaangażowanie 65% → 53% | dodać drugi wariant tekstu lub szablonu katalogu, żeby odświeżyć wyświetlenia. Koszt sesji jeszcze się trzyma, więc to nie jest pilne |
| `[RMKT]` | 0,35 zł/sesja, CTR 14–16%, częstotliwość < 4 | bez zmian |

## Do decyzji Janka (nic nie wykonane)

1. **`[FOTO]` na Contact.** Dostawa się odblokowała, ale zestaw płaci trzy razy więcej za wejście niż `[KAT]` i w 12 dobach dał 1 kontakt GA4.
   Do wyboru: zostawić, zmienić cel na ruch/ViewContent albo wygasić i przesunąć 15 zł do `[KAT]`.
2. **Odświeżenie `[KAT]`.** Drugi wariant kreacji katalogowej, bo zasięg spada, a częstotliwość rośnie. Czy robimy teraz, czy czekamy, aż koszt sesji realnie drgnie.
3. **Wiek.** Rekomenduję zostawić 65+ w `[KAT]` i 18–24 w `[RMKT]` (sekcja 7). Potrzebne potwierdzenie i zamknięcie tematu z 17.09.
4. **Rola Mety.** Meta daje 3% kontaktów strony: 6 kontaktów GA4 za 854 zł w 12 dobach, czyli ok. 142 zł za kontakt.
   Kontakty rosną przez organik i YouTube. Czy Meta ma zostać kanałem ruchu i remarketingu przy tym samym budżecie, czy budżet ma iść gdzie indziej.
5. Otwarte z 15.09 i nadal bez decyzji: przycisk Messengera na ofercie (makieta `primaauto-makieta-messenger-oferta-2026-09-15.html`),
   `gclid` wycinany w GTM od v13 (decyzja z 11.09).

## Dowody: komendy i zapytania

```
python3 scripts/social/recheck_start.py                      # statusy reklam, limit konta
python3 scripts/checki-pomiaru.py                            # 13 checków (stdout, bez --json)
# Graph API v25.0 (scripts/social/meta_api.py), konto act_1038563008906171:
{act}/insights?level=account&time_increment=1&time_range={2026-09-12..2026-10-04}&fields=spend,impressions,inline_link_clicks,actions
{act}/insights?level=ad&time_range=<okno>&fields=ad_name,adset_name,spend,impressions,reach,frequency,inline_link_clicks,actions
   okna: 15–17.09, 22–24.09, 22–27.09, 28.09–03.10, 22.09–03.10
{act}/insights?level=adset&breakdowns=age|publisher_platform|gender|product_id&time_range=22.09–03.10
{act}/insights?level=adset&fields=reach,frequency (okna 6-dobowe)
{act}?fields=amount_spent,spend_cap,balance,account_status,disable_reason
{act}/activities?since=2026-09-20
{act}/adsets?fields=name,effective_status,daily_budget,optimization_goal,targeting,learning_stage_info
# Contact = action_type offsite_conversion.fb_pixel_custom; LPV = landing_page_view; VC = offsite_conversion.fb_pixel_view_content
# GA4 534017542 (scripts/ga4_query.py):
sessionManualAdContent × (sessions, engagedSessions)        filtr sessionSourceMedium = facebook / paid_social, per okno
sessionManualAdContent × eventName (click_phone, click_whatsapp), ten sam filtr
date × sessionSourceMedium × sessions, 12.09–03.10           (sesje Meta per doba + udział bez przypisania)
date × sessionSourceMedium × sessionCampaignName × eventName (click_phone, click_whatsapp, generate_lead), 15–16.09
isoYearIsoWeek × sessionDefaultChannelGroup × eventCount, 18.08–02.10
# GTM: tagmanager/v2/accounts/6351095501/containers/250095450/versions:live
```
