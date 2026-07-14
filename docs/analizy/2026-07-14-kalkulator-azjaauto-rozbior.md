# Rozbiór kalkulatora importu konkurenta (azjaauto.com)

> Data analizy: 2026-07-14 · Źródło: publiczna oferta na azjaauto.com
> Auto referencyjne: **FORTHING T5 2022** (1.5T, 87 700 km, benzyna), import przez Holandię

## Dane odczytane wprost z ich kalkulatora

| Pozycja | Kwota |
|---|---|
| Cena auta (netto, „widoczna w ogłoszeniu") | 26 500 zł |
| Opłaty terminalowe THC (420 EUR) | 1 826 zł |
| Opłata bezpieczeństwa ISPS (100 EUR) | 435 zł |
| Opłata handling (110 EUR) | 478 zł |
| Wysyłka do Holandii (2150 EUR) | 9 348 zł |
| **Dostawa razem** | **12 088 zł** |
| Cło 10% | 2 650 zł |
| VAT 23% | 6 705 zł |
| Akcyza 3,1% | 904 zł |
| Agencja celna (550 EUR) | 2 391 zł |
| Licencja eksportowa (610 EUR) | 2 652 zł |
| Tłumacz przysięgły + opłata dokumentowa (650 EUR) | 2 826 zł |
| Odprawa celna importowa (510 EUR) | 2 218 zł |
| **Odprawa razem** | **20 346 zł** |
| Dostawa do PL | 2 800 zł |
| **Prowizja AzjaAuto** | **1 999 zł** |
| **CAŁKOWITA KWOTA BRUTTO** | **63 732 zł** |

Suma dodaje się poprawnie co do złotówki.

## Ustalenie 1 — podstawa opodatkowania jest zaniżona

Odwrócenie ich wzorów:

```
cło    2 650 / 0,10  = 26 500  → podstawa = CENA AUTA
akcyza   904 / 0,031 = 29 161  → podstawa = cena + cło (29 150)
VAT    6 705 / 0,23  = 29 152  → podstawa = cena + cło (29 150)
```

**Transport (12 088 zł) nie wchodzi do podstawy opodatkowania** — mimo że sami go w kalkulatorze wykazują.

### Jak powinno być

Podstawą cła jest **wartość celna** = cena transakcyjna + koszty transportu i ubezpieczenia do miejsca wprowadzenia na obszar celny UE (wartość CIF).

```
CIF                        = 26 500 + 12 088 = 38 588 zł
cło 10% od CIF             =  3 859 zł   (oni: 2 650)
akcyza 3,1% od (CIF+cło)   =  1 316 zł   (oni:   904)
VAT 23% od (CIF+cło+akcyza)= 10 065 zł   (oni: 6 705)
```

| | Ich | Poprawnie | Różnica |
|---|---|---|---|
| Podatki razem | 10 259 zł | **15 240 zł** | **+4 981 zł** |
| Koszt całkowity | 63 732 zł | **68 713 zł** | **+4 981 zł (7,8%)** |

**Klient, który zaufa ich kalkulatorowi, dopłaci ~5 000 zł na odprawie.**

## Ustalenie 2 — prowizja 1999 zł jest fasadą

Deklarowana prowizja wygląda konkurencyjnie. Ale marża siedzi w pozycjach obok:

| Pozycja | Kwota | Realia rynkowe |
|---|---|---|
| Tłumacz przysięgły + opłata dokumentowa | 2 826 zł | tłumaczenie dokumentów auta: ~200–500 zł |
| Licencja eksportowa | 2 652 zł | — |
| Agencja celna | 2 391 zł | — |
| Odprawa celna importowa | 2 218 zł | **de facto dublet agencji celnej** |
| **Razem** | **10 087 zł** | pozycje niemożliwe do zweryfikowania przez klienta |

Do tego fracht 2 150 EUR (9 348 zł) — wysoko jak na transport współdzielony.

**Wniosek:** „prowizja 1999 zł" to przynęta. Realna marża jest rozproszona w opłatach i frachcie.

## Wnioski dla nas

1. **Nasza cena „pod klucz" nie jest droga — ona jest prawdziwa.** Ich 63 732 zł to kwota, która na odprawie urośnie o ~5 tys.
2. **Mamy materiał na mocny wpis** — pokazujący metodę liczenia, bez nazywania konkurencji (zasada: nie nazywamy konkurencyjnych dealerów).
3. **Nie kopiujemy ich modelu transparentności** — bo ich transparentność jest pozorna. Nasz kalkulator ma liczyć poprawnie; to jest przewaga, nie wada.
4. Nasza własna struktura (część marży w CIF) **nie może trafić do publicznego kalkulatora** — kalkulator ma mieć niezależne, jawne stawki.

## Powiązane

- Task: `docs/roadmapa/T-213-kalkulator-importu.md`
- Baza wiedzy: `docs/roadmapa/T-162-blog-baza-wiedzy.md` (wpis wchodzi jako filar „przewodniki")
