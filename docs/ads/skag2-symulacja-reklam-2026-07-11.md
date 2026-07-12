# SKAG-2 „W drodze" — symulacja obecnych reklam + stan zastany (2026-07-11)

> Kampania **23803851566** `[SKAG-2] W drodze` · konto 9506068500 · Manual CPC · budżet **25 zł/dz**
> Recon API 2026-07-11. Zero zmian wykonanych — czysty odczyt.

## Metryki kampanii (LAST_30_DAYS)

| Koszt | Kliki | Wyśw. | CTR | Śr. CPC | Konwersje | CPA | IS | Budget lost IS | Rank lost IS |
|---|---|---|---|---|---|---|---|---|---|
| 762,90 zł | 788 | 4 903 | 16,1% | 0,97 zł | 4 | 190,73 zł | **10,0%** | **90,0%** | 1,7% |

Budżet 25 zł/dz wydaje się w całości każdego dnia (763 zł / 30 dni = 25,4). Rank lost 1,7% = jakość/bid NIE jest problemem — **kampania jest zdławiona budżetem**, Google urywa emisję po wydaniu dziennej puli.

## Grupy — metryki 30d (sort po koszcie)

| Grupa | Status | Bid | Koszt | Kliki | Wyśw. | CTR | Konw. | Auto (serwowany URL) |
|---|---|---|---|---|---|---|---|---|
| Zeekr 9X | ENABLED | 1,00 | 168,79 | 174 | 1098 | 15,8% | 0 | in_transit ✓ |
| Denza Z9 GT | ENABLED | 1,00 | 124,69 | 128 | 1130 | 11,3% | 0 | **on_lot ✗** |
| Zeekr 8X | ENABLED | 1,00 | 96,94 | 100 | 471 | 21,2% | **1** | in_transit ✓ |
| Denza N9 | ENABLED | 1,00 | 91,43 | 95 | 512 | 18,6% | 0 | in_transit ✓ (ale RSA URL → inny egzemplarz on_lot) |
| BYD Leopard 5 | ENABLED | 1,00 | 86,29 | 90 | 355 | 25,4% | 0 | in_transit ✓ |
| BYD Shark 6 | ENABLED | 1,00 | 50,53 | 52 | 618 | 8,4% | 0 | in_transit ✓ |
| Zeekr 7X | ENABLED | 1,00 | 41,12 | 43 | 165 | 26,1% | **1** | in_transit ✓ |
| Jetour G700 | ENABLED | 1,00 | 27,99 | 29 | 104 | 27,9% | 0 | in_transit ✓ |
| Zeekr 001 | ENABLED | 1,00 | 25,45 | 26 | 140 | 18,6% | 0 | in_transit ✓ |
| Leopard 7 | ENABLED | 1,00 | 19,61 | 20 | 77 | 26,0% | **2** | **on_lot ✗** |
| Mazda EZ-6 | ENABLED | 1,00 | 11,83 | 12 | 141 | 8,5% | 0 | in_transit ✓ |
| BYD Sealion 8 | ENABLED | 1,00 | 8,67 | 9 | 46 | 19,6% | 0 | **MARTWY** — listing 301→hub, keywordy PAUSED |
| Leopard 3 | ENABLED | 1,00 | 2,86 | 3 | 9 | 33% | 0 | in_transit ✓ |
| Deepal G318 | ENABLED | 1,00 | 2,77 | 3 | 9 | 33% | 0 | in_transit ✓ |
| Exeed VX | ENABLED | 1,00 | 1,98 | 2 | 19 | 10,5% | 0 | in_transit ✓ |
| BYD Song L EV | ENABLED | 1,00 | 1,94 | 2 | 8 | 25% | 0 | in_transit ✓ |
| Jetour T2 C-DM | ENABLED | 1,00 | 0,00 | 0 | 1 | — | 0 | **on_lot ✗** |
| Geely LEVC L380 | PAUSED | 1,00 | 0 | 0 | 0 | — | 0 | listing **410 Gone** (pauza zasadna) |
| W drodze do Polski (zbiorcza) | PAUSED | 1,80 | 0 | 0 | 0 | — | 0 | /w-drodze/ 200 · trzyma 52 duplikaty keywordów per-model |

Konwersje 30d: **Leopard 7 ×2, Zeekr 7X ×1, Zeekr 8X ×1**.

## Jak reklama wygląda DZIŚ (symulacja)

**Wszystkie 18 grup modelowych ma IDENTYCZNĄ reklamę** — 1 RSA/grupa, ten sam zestaw 15 nagłówków i 4 opisów; różni się WYŁĄCZNIE przypięty HEADLINE_1 (model + cena). Path: `primaauto.com.pl/samochody/w-drodze`. Brak drugiego wariantu (SKAG-1 po reworku ma 2 RSA/grupę: z ceną / bez ceny).

Przykładowy render (H1 pinowany + rotacja):

```
[Ad] BYD Leopard 5 — 210 000 PLN | Prima-Auto — Importer | Już w Transporcie z Chin
primaauto.com.pl/samochody/w-drodze
Auto już zakupione w Chinach. Transport morski 6-8 tyg. Zarezerwuj kaucją
5 000 PLN zwrotną. Pełna obsługa do rejestracji.
```

### Wspólna pula nagłówków (H1 pinowany, reszta rotuje)

1. **[PIN H1]** `<Model> — <cena> PLN` (per grupa, tabela niżej)
2. Prima-Auto — Importer
3. Już w Transporcie z Chin
4. Dotrze w 6-8 Tygodni
5. Zarezerwuj Online
6. Kaucja 5 000 PLN Zwrotna
7. Pełna Dokumentacja VIN
8. Rejestracja w Cenie
9. Cena All-In z VAT i Cłem
10. Dostawa Pod Twój Dom
11. Bezpośredni Importer
12. Auto Zamówione w Chinach
13. Polski Importer
14. Cena Końcowa Bez Kruczków
15. Pierwsi w Kolejce

### Wspólne opisy

1. Cena all-in: cło, VAT, rejestracja. Dotrze 6-8 tyg. Kaucja 5 000 PLN zwrotna.
2. Auto już zakupione w Chinach. Pierwsi w kolejce, zarezerwuj online.
3. Pełna obsługa importera Prima-Auto. VIN, dokumenty, dostawa pod dom.
4. Bezpośrednio z Chin, bez pośredników. Cena końcowa bez kruczków.

### H1 per grupa — cena w reklamie vs cena DZIŚ (breakdown `total.cena_koncowa_pln`)

| Grupa | H1 w reklamie | Cena dziś | Δ | Ad Strength |
|---|---|---|---|---|
| Leopard 3 | BYD Leopard 3 — 143 000 PLN | 147 000 | −4 000 | AVERAGE |
| Leopard 7 | BYD Leopard 7 — 209 000 PLN | 229 000 | **−20 000** | AVERAGE |
| Deepal G318 | Deepal G318 — 173 000 PLN | 178 000 | −5 000 | AVERAGE |
| Denza N9 | Denza N9 — 306 000 PLN | 316 000 (egz. 351079) | −10 000 | AVERAGE |
| Exeed VX | Exeed VX — 162 000 PLN | 170 000 | −8 000 | POOR |
| Geely LEVC L380 | Geely LEVC L380 — 245 000 PLN | listing 410 | — | AVERAGE (PAUSED) |
| Jetour G700 | Jetour G700 — 338 000 PLN | 349 000 | −11 000 | AVERAGE |
| Jetour T2 C-DM | Jetour T2 C-DM — 171 000 PLN | 197 000 | **−26 000** | AVERAGE |
| Zeekr 8X | Zeekr 8X — od 411 000 PLN | 434 000 | **−23 000** | AVERAGE |
| Zeekr 9X | Zeekr 9X — 524 000 PLN | 546 000 | **−22 000** | AVERAGE |
| Denza Z9 GT | Denza Z9 GT — 209 000 PLN | 285 000 (egz. 314155) | **−76 000** | AVERAGE |
| BYD Shark 6 | BYD Shark 6 — 237 000 PLN | 245 000 | −8 000 | AVERAGE |
| Mazda EZ-6 | Mazda EZ-6 — 133 000 PLN | 132 000 | +1 000 | AVERAGE |
| BYD Leopard 5 | BYD Leopard 5 — 210 000 PLN | 221 000 | −11 000 | AVERAGE |
| BYD Sealion 8 | BYD Sealion 8 — 202 000 PLN | listing martwy (301) | — | AVERAGE |
| BYD Song L EV | BYD Song L EV — 171 000 PLN | 176 000 | −5 000 | AVERAGE |
| Zeekr 001 | Zeekr 001 — 206 000 PLN | 214 000 | −8 000 | AVERAGE |
| Zeekr 7X | Zeekr 7X — 179 000 PLN | 184 000 | −5 000 | AVERAGE |
| (zbiorcza, PAUSED) | {KeyWord:Auto z Chin w drodze} | — | — | POOR |

**Każda żywa cena w nagłówkach jest zaniżona** (poza EZ-6). Klient klika „Denza Z9 GT — 209 000", na stronie widzi 285 000.

### Zdjęcia (AD_IMAGE)

**Brak jakichkolwiek** — 0 na poziomie konta, 0 na kampanii (jest tylko BUSINESS_LOGO), 0 na grupach. Reklamy SKAG-2 serwują się gołe tekstowo. (SKAG-1 po reworku 2026-07-10 ma 14 zdjęć per model na poziomie grup — w SKAG-2 nigdy nie było nawet wspólnych WhatsApp.)

### Sitelinki

Kampania nie ma dedykowanych — **dziedziczy 20 generycznych z konta** (Katalog aut, Samochody BYD/AITO/Xiaomi/XPeng/…, Finansowanie, Gwarancja, O nas, Kontakt). Sitelink „Katalog aut z Chin" obiecuje „dostępne teraz" — sprzeczne z przekazem „w drodze". (SKAG-1 ma 4 dedykowane sitelinki „od ręki" na poziomie kampanii.)

### Rozjazdy URL

- **Denza Z9 GT**: RSA final URL → `denza-z9-gt-dm-i-2024-313838` (301 → hub); serwuje keywordowy `...2025-314155` (który jest **on_lot**, nie w drodze).
- **Denza N9**: RSA final URL → egz. 270838 (**on_lot**, 350 000 zł); keywordy → egz. 351079 (in_transit, 316 000). Serwuje keywordowy, ale RSA URL do naprawy.
- **BYD Sealion 8**: keywordy PAUSED, listing Tang L 238824 → 301 na hub `/model/tang-l-dm/`. Grupa ENABLED-wydmuszka.
- **5 RSA** (Leopard 5, Sealion 8, Song L EV, Zeekr 001, Zeekr 7X) ma final URL `/w-drodze/` zamiast listingu — kryte przez keywordowe final_urls, ale niespójne.

## Diagnoza — czemu SKAG-2 ma słabe wyniki

1. **Budżet dławi kampanię (główna przyczyna „słabych wyników")**: budget lost IS = 90%, rank lost = 1,7%. Popyt jest ~10× większy niż budżet. CTR 16% i CPC 0,97 zł mówią, że reklamy trafiają — po prostu przestają się wyświetlać po wydaniu 25 zł (rano/przedpołudniem, potem cisza).
2. **CR 0,5% (4/788) — problem jest PO kliknięciu, nie przed**: klik prowadzi na listing, gdzie: (a) cena wyższa niż obiecana w nagłówku (do −76k), (b) auto „w drodze" wymaga kaucji 5 000 zł w ciemno bez obejrzenia — próg zaufania wyżej niż przy „od ręki". Nieaktualne ceny = pieniądz za klik wydany na rozczarowanie.
3. **Alokacja: 39% budżetu w 2 grupy bez konwersji** — Zeekr 9X (169 zł, auto za 546k, wąski rynek) + Denza Z9 GT (125 zł, przekaz błędny bo auto już na placu). Grupy które konwertują (Leopard 7, Zeekr 7X/8X) głodują w tym samym budżecie.
4. **3 grupy kłamią o dostępności** (auto już on_lot: Leopard 7, Denza Z9 GT, Jetour T2) — „dotrze w 6-8 tygodni" gdy auto stoi w Rzeszowie. To kandydaci do SKAG-1 „od ręki" (mocniejszy przekaz, który właśnie tam konwertuje) — zbieżne z rechecku SKAG-1 (Leopard 7 na liście kandydatów).
5. **Monotonia RSA**: 1 wariant/grupa, identyczne body w 18 grupach, Ad Strength AVERAGE/POOR. Brak testu z ceną/bez ceny (wzorzec SKAG-1).
6. **Zero zdjęć + generyczne sitelinki** — reklama uboga wizualnie vs SKAG-1; sitelink „dostępne teraz" sprzeczny z narracją transportu.
7. **Higiena**: martwa grupa Sealion 8 (ENABLED bez keywordów żywych), 52 duplikaty keywordów w pauzowanej zbiorczej, 2 RSA z final URL przez 301.

## Dane źródłowe

Dumpy JSON z recon: scratchpad sesji (`skag2-*.json`) · zapytania w `tmp/gads_client.py` · ceny: `wp db` breakdown v2.
