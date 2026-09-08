# TOP 30 modeli do feeda remarketingowego (Meta + Google Ads)

> Pomiar: 2026-09-08, okno 90 dni (10.06–06.09.2026). Read-only — nic w kampaniach nie ruszone.

## 1. Jak to policzone — sześć źródeł, nie jedno

Feed ma dowozić **kontakt**, więc ranking waży sygnały dolnego lejka wyżej niż ruch:

| Waga | Sygnał | Źródło | Dlaczego |
|---|---|---|---|
| 30% | kontakty (telefon / WhatsApp / formularz) | GA4 `click_phone`, `click_whatsapp`, `generate_lead` per URL → model | jedyna konwersja, którą uznajemy (`reference_ads_konwersje_tylko_kontakt`) |
| 20% | zamówienia z kreatora | baza, CPT `asiaauto_order` → `_order_listing_id` → model | najtwardsza deklaracja intencji |
| 20% | odsłony ofert | GA4 `screenPageViews` na `/oferta/*` | definiuje, kogo w ogóle mamy na liście remarketingowej |
| 15% | zapytania mailowe | skrzynka `china@primaauto.com.pl`, 904 maile (742 INBOX + 162 Sent) | pytania, które nigdy nie przeszły przez formularz |
| 10% | kliknięcia z Google | GSC per strona → model | popyt organiczny, niezależny od naszego budżetu |
| 5% | konwersje Ads na landingu | Google Ads `landing_page_view` | mały wolumen, ale wprost o płatnym ruchu |

Do tego **twardy gate podażowy**: model bez ani jednej żywej oferty wypada, choćby miał sygnał
(reklama prowadząca na pusty hub pali budżet).

**Mapowanie URL → model:** oferty przez `post_name` z bazy, z fallbackiem na dopasowanie
prefiksu `marka-model`. Pokrycie **94,4%** odsłon `/oferta/` (reszta to auta usunięte w rotacji
sprzed >7 dni). Huby przez `/samochody/<marka>/<model>/`.

**Rozdzielenie maili:** korespondencja z dostawcami odfiltrowana od zapytań klientów — bez tego
BYD Han L DM wychodził na 2. miejscu, a to były 25 maili wątku zakupowego z Josephem, nie popyt.

## 2. Lista — TOP 30

| # | Model | Score | Kontakty | Zam. | Maile | Odsłony ofert | GSC | Ofert | Na placu | W drodze | Cena od |
|---:|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | **BYD Leopard 5 (Denza B5)** | 12.80 | 67 | 11 | 30 | 4311 | 1107 | 21 | 1 | 2 | 183 tys |
| 2 | **BYD Leopard 7 (Tai 7) FCB, PHEV** | 6.72 | 26 | 15 | 21 | 1718 | 722 | 16 | 1 | 6 | 198 tys |
| 3 | **Exeed VX** | 4.79 | 19 | 6 | 0 | 1341 | 362 | 7 | 2 | 0 | 163 tys |
| 4 | **Mazda EZ-6** | 4.50 | 22 | 7 | 17 | 1125 | 312 | 8 | 0 | 3 | 134 tys |
| 5 | **Zeekr 8X** | 3.68 | 13 | 8 | 2 | 1105 | 1215 | 7 | 0 | 4 | 343 tys |
| 6 | **Denza N9 DM-i** | 3.17 | 7 | 11 | 5 | 981 | 274 | 20 | 4 | 0 | 275 tys |
| 7 | **Denza Z9 GT DM-i** | 2.97 | 12 | 9 | 4 | 1236 | 407 | 24 | 2 | 0 | 215 tys |
| 8 | **Lynk & Co 900** | 2.95 | 8 | 6 | 4 | 1249 | 313 | 22 | 0 | 3 | 254 tys |
| 9 | **Geely Monjaro** | 2.84 | 12 | 1 | 3 | 1832 | 1780 | 24 | 0 | 1 | 128 tys |
| 10 | **Zeekr 9X** | 2.70 | 11 | 3 | 3 | 1448 | 1505 | 43 | 0 | 1 | 414 tys |
| 11 | **Mazda EZ-60** | 2.60 | 8 | 5 | 9 | 414 | 178 | 8 | 0 | 4 | 147 tys |
| 12 | **Jetour G700** | 2.38 | 10 | 5 | 5 | 819 | 598 | 8 | 1 | 1 | 262 tys |
| 13 | **BYD Shark 6** | 2.31 | 9 | 3 | 4 | 506 | 961 | 1 | 1 | 0 | 250 tys |
| 14 | **Zeekr 001** | 1.83 | 7 | 5 | 6 | 887 | 510 | 18 | 0 | 0 | 183 tys |
| 15 | **Zeekr 7X** | 1.81 | 10 | 1 | 0 | 814 | 978 | 4 | 0 | 0 | 166 tys |
| 16 | **Xiaomi YU7** | 1.48 | 3 | 1 | 6 | 1260 | 700 | 51 | 0 | 0 | 214 tys |
| 17 | **Deepal G318** | 1.26 | 2 | 5 | 4 | 524 | 232 | 5 | 1 | 0 | 166 tys |
| 18 | **BYD Sealion 8 DM-I (Tang L)** | 1.21 | 4 | 0 | 1 | 820 | 1251 | 8 | 0 | 0 | 189 tys |
| 19 | **AITO M9** | 1.12 | 1 | 1 | 0 | 872 | 1272 | 67 | 1 | 0 | 305 tys |
| 20 | **BYD Leopard 3 (Tai 3) FCB** | 1.11 | 3 | 3 | 2 | 453 | 321 | 7 | 0 | 1 | 148 tys |
| 21 | **Denza N8L DM** | 1.07 | 3 | 3 | 2 | 591 | 64 | 22 | 0 | 1 | 248 tys |
| 22 | **Hongqi H9** | 1.07 | 5 | 2 | 0 | 557 | 70 | 7 | 2 | 0 | 203 tys |
| 23 | **Xiaomi SU7** | 1.05 | 4 | 0 | 5 | 567 | 295 | 123 | 0 | 0 | 173 tys |
| 24 | **iCAR Super V23** | 1.00 | 3 | 4 | 4 | 443 | 158 | 26 | 0 | 1 | 127 tys |
| 25 | **BYD Han L DM** | 0.87 | 1 | 1 | 4 | 435 | 263 | 18 | 0 | 1 | 174 tys |
| 26 | **Jetour T2 C-DM** | 0.85 | 2 | 2 | 1 | 743 | 281 | 17 | 1 | 0 | 153 tys |
| 27 | **MG 7** | 0.84 | 5 | 0 | 0 | 575 | 506 | 21 | 0 | 0 | 103 tys |
| 28 | **XPeng GX** | 0.84 | 2 | 4 | 0 | 448 | 217 | 13 | 0 | 0 | 225 tys |
| 29 | **BYD Seal** | 0.81 | 0 | 0 | 10 | 94 | 19 | 7 | 0 | 0 | 125 tys |
| 30 | **BYD Leopard 8 (Denza B8)** | 0.72 | 4 | 0 | 2 | 496 | 103 | 19 | 0 | 0 | 291 tys |

### Ile tracimy, odcinając ogon

| Sygnał | TOP 30 | Całość | Pokrycie |
|---|---:|---:|---:|
| Kontakty (tel./WhatsApp/formularz) | 283 | 364 | **77.7%** |
| Zamówienia z kreatora | 122 | 145 | **84.1%** |
| Zapytania mailowe | 154 | 196 | **78.6%** |
| Odsłony ofert | 28664 | 49658 | **57.7%** |
| Kliknięcia z Google | 16974 | 26326 | **64.5%** |

**30 modeli z 284 mających oferty (10,6%) zbiera 78% kontaktów i 84% zamówień.** Ogon 254 modeli
odpowiada za ~20% sygnału i 79% pozycji feeda. To jest liczbowe uzasadnienie Twojej intuicji.

## 3. Konkretne oferty — po 3 na model

Priorytet: **dostępność** (na placu → w drodze → sprowadzimy), potem ruch GA4, potem komplet zdjęć.


**BYD Leopard 5 (Denza B5)** — 21 ofert, score 12.80

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 233 tys | 9 | 1203 | **na placu** | `/oferta/byd-leopard-5-denza-b5-2025-270959/` |
| 242 tys | 10 | 136 | w drodze | `/oferta/byd-leopard-5-denza-b5-2025-448113/` |
| 279 tys | 10 | 31 | w drodze | `/oferta/byd-leopard-5-denza-b5-2026-463845/` |

**BYD Leopard 7 (Tai 7) FCB, PHEV** — 16 ofert, score 6.72

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 234 tys | 13 | 503 | **na placu** | `/oferta/byd-leopard-7-tai-7-fcb-phev-2026-317106/` |
| 220 tys | 13 | 291 | w drodze | `/oferta/byd-leopard-7-tai-7-fcb-phev-2026-372067/` |
| 220 tys | 13 | 194 | w drodze | `/oferta/byd-leopard-7-tai-7-fcb-phev-2025-303458/` |

**Exeed VX** — 7 ofert, score 4.79

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 189 tys | 28 | 626 | **na placu** | `/oferta/exeed-vx-lanyue-2026-263901/` |
| 193 tys | 28 | 458 | **na placu** | `/oferta/exeed-vx-lanyue-2025-265748/` |
| 169 tys | 28 | 71 | sprowadzimy | `/oferta/exeed-vx-lanyue-2025-398897/` |

**Mazda EZ-6** — 8 ofert, score 4.50

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 134 tys | 24 | 244 | w drodze | `/oferta/mazda-ez-6-2024-333056/` |
| 139 tys | 24 | 113 | w drodze | `/oferta/mazda-ez-6-2025-390023/` |
| 140 tys | 9 | 110 | w drodze | `/oferta/mazda-ez-6-2025-411349/` |

**Zeekr 8X** — 7 ofert, score 3.68

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 522 tys | 9 | 291 | w drodze | `/oferta/zeekr-009-2025-289733/` |
| 463 tys | 9 | 254 | w drodze | `/oferta/zeekr-8x-2026-303501/` |
| 448 tys | 9 | 208 | w drodze | `/oferta/zeekr-8x-2026-303486/` |

**Denza N9 DM-i** — 20 ofert, score 3.17

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 344 tys | 17 | 175 | **na placu** | `/oferta/denza-n9-dm-i-2025-351079/` |
| 344 tys | 17 | 172 | **na placu** | `/oferta/denza-n9-dm-i-2026-389970/` |
| 344 tys | 17 | 133 | **na placu** | `/oferta/denza-n9-dm-i-2026-387824/` |

**Denza Z9 GT DM-i** — 24 ofert, score 2.97

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 291 tys | 16 | 415 | **na placu** | `/oferta/denza-z9-gt-dm-i-2025-314155/` |
| 295 tys | 16 | 207 | **na placu** | `/oferta/denza-z9-gt-dm-i-2025-303534/` |
| 215 tys | 21 | 61 | sprowadzimy | `/oferta/denza-z9-gt-dm-i-2025-371993/` |

**Lynk & Co 900** — 22 ofert, score 2.95

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 309 tys | 24 | 207 | w drodze | `/oferta/lynk-co-900-2026-292582/` |
| 281 tys | 15 | 207 | w drodze | `/oferta/lynk-co-900-2025-360158/` |
| 282 tys | 15 | 173 | w drodze | `/oferta/lynk-co-900-2025-387540/` |

**Geely Monjaro** — 24 ofert, score 2.84

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 171 tys | 9 | 559 | w drodze | `/oferta/geely-monjaro-2026-361289/` |
| 154 tys | 20 | 62 | sprowadzimy | `/oferta/geely-monjaro-2026-411363/` |
| 131 tys | 9 | 59 | sprowadzimy | `/oferta/geely-monjaro-2024-386868/` |

**Zeekr 9X** — 43 ofert, score 2.70

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 630 tys | 9 | 645 | w drodze | `/oferta/zeekr-zeekr-9x-2025-276215/` |
| 440 tys | 7 | 78 | sprowadzimy | `/oferta/zeekr-9x-2026-387765/` |
| 617 tys | 9 | 51 | sprowadzimy | `/oferta/zeekr-9x-2026-390713/` |

**Mazda EZ-60** — 8 ofert, score 2.60

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 157 tys | 18 | 84 | w drodze | `/oferta/mazda-ez-60-2025-400985/` |
| 153 tys | 18 | 42 | w drodze | `/oferta/mazda-ez-60-2025-450787/` |
| 153 tys | 18 | 15 | w drodze | `/oferta/mazda-ez-60-2025-450776/` |

**Jetour G700** — 8 ofert, score 2.38

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 388 tys | 21 | 365 | **na placu** | `/oferta/jetour-zongheng-g700-2026-265702/` |
| 296 tys | 21 | 179 | w drodze | `/oferta/jetour-zongheng-g700-2026-265668/` |
| 274 tys | 21 | 92 | sprowadzimy | `/oferta/jetour-g700-2026-350370/` |

**BYD Shark 6** — 1 ofert, score 2.31

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 250 tys | 0 | 506 | **na placu** | `/oferta/byd-shark-6-premium-awd-2026-phev-new/` |

**Zeekr 001** — 18 ofert, score 1.83

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 254 tys | 33 | 33 | sprowadzimy | `/oferta/zeekr-001-2026-350331/` |
| 222 tys | 22 | 33 | sprowadzimy | `/oferta/zeekr-001-2026-374367/` |
| 305 tys | 33 | 32 | sprowadzimy | `/oferta/zeekr-001-2026-350339/` |

**Zeekr 7X** — 4 ofert, score 1.81

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 182 tys | 16 | 67 | sprowadzimy | `/oferta/zeekr-7x-2025-330429/` |
| 194 tys | 21 | 10 | sprowadzimy | `/oferta/zeekr-7x-2025-440245/` |
| 166 tys | 21 | 7 | sprowadzimy | `/oferta/zeekr-7x-2025-463614/` |

**Xiaomi YU7** — 51 ofert, score 1.48

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 273 tys | 15 | 66 | sprowadzimy | `/oferta/xiaomi-yu7-2025-387780/` |
| 214 tys | 9 | 28 | sprowadzimy | `/oferta/xiaomi-yu7-2025-409109/` |
| 274 tys | 25 | 27 | sprowadzimy | `/oferta/xiaomi-yu7-2025-410733/` |

**Deepal G318** — 5 ofert, score 1.26

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 196 tys | 11 | 434 | **na placu** | `/oferta/deepal-g318-2026-272010/` |
| 166 tys | 13 | 16 | sprowadzimy | `/oferta/deepal-g318-2024-448875/` |
| 169 tys | 24 | 13 | sprowadzimy | `/oferta/deepal-g318-2025-435214/` |

**BYD Sealion 8 DM-I (Tang L)** — 8 ofert, score 1.21

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 214 tys | 11 | 61 | sprowadzimy | `/oferta/byd-sealion-8-dm-i-tang-l-2025-375375/` |
| 202 tys | 11 | 51 | sprowadzimy | `/oferta/byd-sealion-8-dm-i-tang-l-2025-445006/` |
| 199 tys | 14 | 21 | sprowadzimy | `/oferta/byd-sealion-8-dm-i-tang-l-2025-316036/` |

**AITO M9** — 67 ofert, score 1.12

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 357 tys | 26 | 452 | **na placu** | `/oferta/aito-m9-2025-362371/` |
| 371 tys | 17 | 14 | sprowadzimy | `/oferta/aito-m9-2024-387254/` |
| 422 tys | 15 | 12 | sprowadzimy | `/oferta/aito-m9-2025-360450/` |

**BYD Leopard 3 (Tai 3) FCB** — 7 ofert, score 1.11

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 150 tys | 25 | 285 | w drodze | `/oferta/byd-leopard-3-tai-3-fcb-2025-309332/` |
| 150 tys | 11 | 10 | sprowadzimy | `/oferta/byd-leopard-3-tai-3-fcb-2025-300135/` |
| 174 tys | 26 | 6 | sprowadzimy | `/oferta/byd-leopard-3-tai-3-fcb-2025-458282/` |

**Denza N8L DM** — 22 ofert, score 1.07

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 251 tys | 12 | 181 | w drodze | `/oferta/denza-n8l-dm-2026-387849/` |
| 250 tys | 22 | 51 | sprowadzimy | `/oferta/denza-n8l-2025-387173/` |
| 267 tys | 13 | 40 | sprowadzimy | `/oferta/denza-n8l-dm-2025-360481/` |

**Hongqi H9** — 7 ofert, score 1.07

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 203 tys | 9 | 232 | **na placu** | `/oferta/hongqi-h9-2024-387799/` |
| 203 tys | 9 | 193 | **na placu** | `/oferta/hongqi-h9-2024-387815/` |
| 349 tys | 14 | 50 | sprowadzimy | `/oferta/hongqi-h9-2025-390584/` |

**Xiaomi SU7** — 123 ofert, score 1.05

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 194 tys | 13 | 8 | sprowadzimy | `/oferta/xiaomi-su7-2025-432833/` |
| 211 tys | 12 | 8 | sprowadzimy | `/oferta/xiaomi-su7-2025-417712/` |
| 251 tys | 10 | 7 | sprowadzimy | `/oferta/xiaomi-su7-2025-337712/` |

**iCAR Super V23** — 26 ofert, score 1.00

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 147 tys | 10 | 183 | w drodze | `/oferta/icar-super-v23-2026-355778/` |
| 127 tys | 10 | 16 | sprowadzimy | `/oferta/icar-super-v23-2025-398241/` |
| 148 tys | 10 | 10 | sprowadzimy | `/oferta/icar-super-v23-2026-385696/` |

**BYD Han L DM** — 18 ofert, score 0.87

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 189 tys | 20 | 189 | w drodze | `/oferta/byd-han-l-dm-2026-360439/` |
| 177 tys | 13 | 71 | sprowadzimy | `/oferta/byd-han-l-dm-2025-387894/` |
| 176 tys | 16 | 17 | sprowadzimy | `/oferta/byd-han-l-dm-2025-410102/` |

**Jetour T2 C-DM** — 17 ofert, score 0.85

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 197 tys | 6 | 348 | **na placu** | `/oferta/jetour-t2-c-dm-2025-264454/` |
| 194 tys | 23 | 43 | sprowadzimy | `/oferta/jetour-t2-c-dm-2026-390040/` |
| 214 tys | 23 | 26 | sprowadzimy | `/oferta/jetour-t2-c-dm-2026-390576/` |

**MG 7** — 21 ofert, score 0.84

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 103 tys | 9 | 135 | sprowadzimy | `/oferta/mg-7-2023-388024/` |
| 149 tys | 15 | 35 | sprowadzimy | `/oferta/mg-7-2024-387912/` |
| 140 tys | 20 | 25 | sprowadzimy | `/oferta/mg-7-2025-444416/` |

**XPeng GX** — 13 ofert, score 0.84

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 348 tys | 16 | 83 | sprowadzimy | `/oferta/xpeng-gx-2026-362335/` |
| 324 tys | 9 | 74 | sprowadzimy | `/oferta/xpeng-gx-2026-362360/` |
| 346 tys | 6 | 35 | sprowadzimy | `/oferta/xpeng-gx-2026-452149/` |

**BYD Seal** — 7 ofert, score 0.81

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 125 tys | 9 | 6 | sprowadzimy | `/oferta/byd-seal-2024-440560/` |
| 143 tys | 4 | 5 | sprowadzimy | `/oferta/byd-seal-2025-451089/` |
| 147 tys | 11 | 4 | sprowadzimy | `/oferta/byd-seal-2024-451104/` |

**BYD Leopard 8 (Denza B8)** — 19 ofert, score 0.72

| Cena | Zdjęć | Odsłon 90d | Gdzie | Oferta |
|---:|---:|---:|---|---|
| 299 tys | 20 | 51 | sprowadzimy | `/oferta/byd-leopard-8-denza-b8-2025-387220/` |
| 344 tys | 21 | 36 | sprowadzimy | `/oferta/byd-leopard-8-denza-b8-2025-350386/` |
| 291 tys | 14 | 30 | sprowadzimy | `/oferta/byd-leopard-8-denza-b8-2025-390616/` |
## 4. Co z tego wynika operacyjnie

### Dwa feedy, dwie mechaniki — nie mylić

| | Google Ads (RMKT dynamic) | Meta (Automotive Inventory Ads) |
|---|---|---|
| Jednostka feeda | **hub modelu** (`id` = `term_id` taksonomii `serie`) | **pojedyncze auto** (`vehicle_id`) |
| Stan dziś | 1931 assetów `DYNAMIC_CUSTOM` w rejestrze konta | **3002 pozycje** w `feeds/meta-vehicles.csv` |
| Po ograniczeniu | **30 wpisów** | **642 oferty** (21% obecnego feeda) |
| Jak zawęzić | przebudowa feedu (remove + create — `DynamicCustomAsset` jest immutable) | zestaw produktów po `custom_label_1` z listą modeli albo filtr katalogu |

Feed Meta wymaga rozszerzenia buildera o etykietę modelu — dziś `custom_label_0` niesie tylko
lokalizację sztuki. Najtaniej: dołożyć `custom_label_1` = slug modelu i zbudować zestaw produktów
z whitelistą 30 slugów. Wtedy zawężanie jest kwestią jednej listy, bez ruszania builderu przy każdej zmianie.

### Rzecz, która wyszła przy okazji i jest ważniejsza niż sama lista

**Z 3002 pozycji feeda Meta dokładnie 49 to auta, które fizycznie istnieją** — 19 na placu,
30 w drodze. Pozostałe 2953 to katalog „sprowadzimy". Remarketing dogania człowieka, który
oglądał konkretne auto; jeśli w 98% przypadków dogania go zdjęciem auta, którego nie ma na
stanie, to rozmowa zaczyna się od tłumaczenia zamiast od terminu odbioru.

Sugestia do rozważenia (nie robię tego bez Twojej decyzji): **dwa zestawy produktów zamiast jednego** —
osobna kampania na 49 dostępnych sztuk z przekazem „jest na placu / odbiór za X tygodni"
i osobna na TOP 30 modeli z przekazem katalogowym. Dziś oba przekazy są zlane w jeden.

### Popyt bez podaży — do zaciągu, nie do feeda

Trzy modele mają sygnał, a zero żywych ofert: **Luxeed/Honda S7** (2 zamówienia, oferta poszła
w draft), **Geely LEVC L380** (1 zamówienie), **Chery Omoda** (1 kontakt). Do feeda nie wchodzą —
to materiał na zaciąg.

### Czego ta lista nie mówi

- **Zamówienia w kreatorze ≠ sprzedaż.** Ze 145 zamówień z listingiem 98 ma status poważniejszy
  niż weryfikacja, 64 są anulowane. Ranking liczy oba, bo oba są deklaracją zainteresowania modelem.
- **56 zamówień nie ma przypiętego listingu** (auto wyrotowało) — nie weszły do rankingu per model.
- **GA4 mierzy zdarzenia, nie osoby.** Akcje mają `MANY_PER_CLICK`, więc 364 kontakty to mniej
  unikalnych ludzi (rząd wielkości: ~połowa, patrz `project_rmkt_optimization_2026_07_12`).
- **Xiaomi SU7 (123 oferty) i AITO M9 (67) wchodzą głównie podażą i ruchem**, nie kontaktem —
  SU7 ma 4 kontakty przy 567 odsłonach ofert. To kandydaci do wycięcia, jeśli chcesz listę krótszą niż 30.

## 5. Odtworzenie pomiaru

Skrypty jednorazowe w scratchpadzie sesji; źródła i zapytania:

- **GA4** (property 534017542) — `scripts/ga4_query.py`, `runReport`, wymiary `pagePath` / `eventName`,
  filtr zdarzeń: `click_phone`, `click_whatsapp`, `generate_lead`, okno `90daysAgo`–`yesterday`.
- **GSC** — `searchAnalytics/query`, wymiar `page`, 10.06–06.09 (6241 stron, 34 417 kliknięć).
- **Google Ads** — `scripts/gads_client.py`, `googleAds:search`, `landing_page_view` + `search_term_view`,
  `segments.date BETWEEN` (uwaga: `LAST_90_DAYS` nie istnieje w GAQL — zwraca 400).
- **Baza** — `wp db query` na `wp7j_posts`/`wp7j_postmeta`, CPT `listings` i `asiaauto_order`.
- **Poczta** — `/home/host476470/imap/primaauto.com.pl/china/Maildir`, pliki skompresowane
  **zstd** (`/usr/bin/zstd -dc`), nie zwykły maildir — `email.message_from_bytes` na surowym pliku
  zwraca puste nagłówki i to wygląda jak pusta skrzynka.

**Gotcha taksonomiczna:** slug `m9` należy do dwóch termów (`AITO M9` i `Geely Galaxy M9`), podobnie
`s7`, `l6`, `l7`, `e8`, `m7`, `m8`, `n7`, `h6`, `et5`, `m6`. Klucz modelu to zawsze para
**(make, serie)**, nigdy sam slug serii — inaczej ranking skleja dwa różne auta.

---

## 6. Weryfikacja: czy feed w ogóle wymaga zawężania — NIE

**Sekcje 1–5 powstały przy założeniu, że ograniczenie feeda do TOP 30 jest ruchem sensownym.
Weryfikacja tego samego dnia pokazała, że nie jest.** Lista zostaje jako materiał o tym, co
sprzedaje. Feeda nie ruszamy.

### Dlaczego — pomiar dopasowania

Feed nie jest listą reklam, tylko bazą, z której system sam dobiera pod historię użytkownika
(Google: `dynx_itemid` = `term_id` serii; Meta: retargeting po `content_id`). Zawężenie źródła nie
poprawia doboru — odbiera systemowi materiał na ludzi, którzy oglądali cokolwiek spoza listy.

Stan żywego zestawu `[RMKT] Model-huby` (`assetSets/9118300013`), sprawdzony 2026-09-08:

| Miara | Wynik |
|---|---|
| Wpisów ENABLED w zestawie | **257** |
| Odsłony ofert modeli MAJĄCYCH wpis | **43 635 / 49 658 = 87,9%** |
| Odsłony modeli bez wpisu | 6023 = 12,1% |
| Wpisy dotyczące modeli **bez ani jednej odsłony** w 90 dni | **0 z 257** |

Feed nie zawiera śmieci i pokrywa niemal cały realny ruch. Gdyby go przyciąć do 30 modeli,
**42% odsłon ofert straciłoby dopasowanie** (TOP 30 to 57,7% odsłon) — system podstawiłby tym
ludziom coś innego zamiast auta, które oglądali.

### Gdzie feed faktycznie nie dogania — dwie reguły w builderze, nie przypadek

- **Xiaomi** (YU7 1260 odsłon, SU7 567, SU7 Ultra 376) — `$WYCOFANE_MARKI` w
  `build-gads-hub-feed.php:22`, decyzja Janka 2026-09-07 po mailu ws. marki. Zostaje.
- **`$NON_CHINESE`** (`build-gads-hub-feed.php:18`) — tu jest jedyna otwarta sprawa:
  **Mazda EZ-6 (1125 odsłon, 22 kontakty — 4. model rankingu)**, EZ-60 (414 / 8) i MG 7 (575 / 5)
  są wykluczone, choć EZ-6/EZ-60 wychodzą z Changan Mazda, a MG należy do SAIC. Razem ~35 kontaktów
  z modeli, których remarketing nie dogania. Pozostałe wykluczone marki (VW, Volvo, Audi, Toyota,
  Lotus) mają ruch bez kontaktów — tam reguła działa poprawnie. **Czeka na decyzję.**

### Auta dostępne fizycznie — inny produkt, nie droższa półka

Ruslan wyłożył na nie własne pieniądze i poniósł koszty importu, więc są droższe od sprowadzenia
na zamówienie. Konkurencja aut na placu praktycznie nie ma.

| Stan | Sztuk | Odsłon | Kontaktów | Na 1000 odsłon |
|---|---:|---:|---:|---:|
| Na placu (`on_lot`) | 19 | 6843 | 69 | **10,1** |
| W drodze (`in_transit`) | 30 | 5125 | 40 | 7,8 |
| Import (`sprowadzimy`) | 2964 | 16 986 | 97 | 5,7 |

**Narzut cenowy na 22 modelach mających obie opcje: mediana +16%, średnia +19,3%** (Li Auto L8
+64%, Zeekr 9X +52%, Denza Z9 GT +35%, Leopard 5 +50 tys.; Mazda EZ-6 −1,5%, Hongqi H9 0%).

Mimo wyższej ceny **auto dostępne konwertuje 1,8× lepiej niż importowe** — 0,6% oferty daje 19%
wszystkich kontaktów. Zastrzeżenie: te auta dostają lepszy ruch (kafle, `/w-rzeszowie/` — 2865
sesji, reklamy), więc to nie jest czysty eksperyment; kierunek jest jednak jednoznaczny.

Wniosek: cena nie jest tu argumentem konkurencyjnym i nie ma sensu stawiać jej obok ceny importu.
Argumentem jest gotowość — auto stoi, można je obejrzeć i przejechać. Czy to uzasadnia osobną
kampanię przy obecnych budżetach (DG 35 zł/dzień, RMKT kilkanaście) — **nie rozstrzygnięte**,
bo rozdrobnienie budżetu może zjeść korzyść.

### Co z tego wynika operacyjnie

1. **Feed zostaje pełny.** Sekcja 4 („dwa feedy, dwie mechaniki") opisuje mechanikę poprawnie,
   ale rekomendacja zawężenia jest nieaktualna.
2. **Sterowanie zakresem robi się zestawem produktów per kampania**, nigdy cięciem źródła —
   zestaw zmienia się w minutę, feed przebudowuje przez remove+create i traci historię.
3. **TOP 30 przydaje się tam, gdzie system nie zna użytkownika** — prospecting, Advantage+ broad,
   kampanie cenowe. Nie w retargetingu.
4. **Otwarte:** wpuścić Mazdę EZ-6/EZ-60 i MG 7 do feedu (decyzja Janka); sprawdzić, czy piksel
   Meta wysyła `content_id` zgodne z `vehicle_id` z `feeds/meta-vehicles.csv` — przy rozjeździe
   dopasowanie w Meta nie działa mimo pełnego katalogu.
