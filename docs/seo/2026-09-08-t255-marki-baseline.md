# T-255 — baseline pomiaru `/marki/` przed i po wdrożeniu (2026-09-08)

Bez tej kartki za miesiąc nie udowodnimy efektu. Źródło: GSC Search Analytics (`scripts/gsc-hub-pomiar.py`),
URL Inspection API. Wdrożenie: **2026-09-08**, motyw 1.4.0 → 1.5.0.

## Stan PRZED (pomiar 08.09, przed zmianami)

| okno | kliki | impresje | CTR | pozycja |
|---|---|---|---|---|
| 05.06–03.08 (przed reworkiem T-225) | 3 | 1 135 | 0,26 % | 18,0 |
| **05.08–04.09 (po T-225, przed T-255)** | **32** | **3 130** | **1,02 %** | **12,7** |

161 zapytań w oknie 05.08–04.09. Indeksacja: „Strona przesłana i zindeksowana", ostatni crawl 06.09.

### Frazy, pod które robimy zmianę

| fraza | impresje | pozycja | CTR | co z tym robimy |
|---|---|---|---|---|
| `marka chińskich aut` | 222 | 8,3 | 0 % | focus keyword + title z liczbą marek |
| `chinskie marki aut` | 114 | 19,6 | 0 % | — (strona 2, kwestia treści i linkowania) |
| `chińskie marki samochodów` | 84 | 26,1 | 0 % | — |
| `ile jest chińskich marek samochodów` | 20 | 8,8 | 0 % | answer-first w podhero |
| `ile marek samochodów jest w chinach` | 19 | 10,6 | 0 % | answer-first w podhero |
| `ile jest chinskich marek aut` | 18 | 8,3 | 0 % | answer-first w podhero |
| `ile jest marek chińskich samochodów` | 12 | 8,4 | 0 % | answer-first w podhero |
| `ile jest marek samochodów w chinach` | 11 | 9,4 | 0 % | answer-first w podhero |

**Klaster „ile jest marek" razem: 80 impresji/mc, pozycje 8,3–10,6, zero kliknięć.** Odpowiedź istniała,
ale siedziała w zwiniętym `<details>` na dole strony — teraz jest pierwszym zdaniem pod H1.

**Klaster „logo/loga" (6 fraz, ~79 imp/mc, poz. 9,3–24, CTR 0 %) — świadomie pomijamy.** Decyzja Janka 08.09:
nie naruszamy znaków towarowych. Jeśli za kwartał te frazy nadal będą rosnąć bez naszego udziału, to jest
argument do ponownej rozmowy, nie dowód błędu.

## Co zmierzyć za 30 dni (08.10.2026)

```bash
python3 scripts/gsc-hub-pomiar.py 2026-08-05 2026-09-04 2026-09-09 2026-10-08 https://primaauto.com.pl/marki/
```

Czego szukamy, w kolejności ważności:

1. **CTR** — dziś 1,02 %. Zmiana title i description celuje właśnie w to; przy 3 100 impresjach każdy punkt
   procentowy to ~31 kliknięć miesięcznie.
2. **`marka chińskich aut`** — czy z pozycji 8,3 przy zerowym CTR zaczęła zbierać kliknięcia.
3. **Klaster „ile jest marek"** — czy answer-first zabrał snippet (sygnał: skok CTR na tych pięciu frazach,
   niekoniecznie skok pozycji).
4. Pozycja ogólna — dziś 12,7. Sama zmiana układu jej nie ruszy; ruch pozycji będzie efektem treści,
   nie kafli, więc nie przypisujmy go T-255 bez sprawdzenia, co jeszcze wyszło w tym oknie.

⚠️ **Czego NIE mierzyć jako efekt T-255:** liczby ofert w kaflach zmieniają się z każdym zaciągiem
(BYD 365 → 366 w ciągu jednego dnia pomiaru), więc różnice w licznikach nie są sygnałem.
