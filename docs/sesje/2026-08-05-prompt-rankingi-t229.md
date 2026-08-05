# Prompt kontynuacyjny — rankingi (T-229 / T-230)

> Skopiuj poniższe do nowego wątku w `~/projekty/primaauto`.
> Stan na 2026-08-05, po sesji zakończonej commitem `76b1838`.

---

Wracamy do rankingów rynkowych — T-229 (mechanizm) i T-230 (pierwsze wpisy).
Przeczytaj najpierw `docs/roadmapa/T-229-szablon-rankingu.md` oraz
`docs/analizy/2026-08-04-40-rankingow-typowanie.md`, potem działaj.

## Gdzie jesteśmy

Zrobione i zacommitowane:

- **Task 1** — `scripts/kb/ranking_market.py`, parser danych rynkowych (cnevpost, carnewschina)
- **Task 2** — `scripts/kb/ranking_stock.py`, warstwa naszej dostępności; dopasowanie ścisłe
  po nazwie albo slugu, bez fuzzy; brak dopasowania → **pusty blok**, nigdy komunikat
  „nie mamy tego modelu” (to reklamowanie konkurencji na własnej stronie)
- **Spec** z decyzjami D1–D9 i lista 40 możliwych rankingów z typowaniem
- **Rozpoznane API rankingowe Dongchedi** — źródło sprzedaży per model, zgodne co do sztuki
  ze zrzutami z WeChat, które dały początek tematowi

Do zrobienia: **Task 3** (generator wpisu), **Task 4** (odświeżanie), **Task 5** (wpięcie
w serwis: autolinker + ItemList dla kategorii `rankingi`), potem **T-230** — pierwszy wpis.

## API Dongchedi — jak wołać

```
https://www.dongchedi.com/motor/pc/car/rank_data
  ?aid=1839&app_name=auto_web_pc
  &rank_data_type=11        # 11 = sprzedaż miesięczna, 1 = popularność (codziennie)
  &outter_detail_type=12    # klasa pojazdu, patrz niżej
  &new_energy_type=         # 2 = PHEV, 3 = EREV, puste = wszystkie
  &month=202507             # historia — pozwala liczyć dynamikę m/m i r/r
  &count=100&offset=0&nation=0
```
Wymagany nagłówek: `Referer: https://www.dongchedi.com/sales`

`outter_detail_type`: 1 miejskie · 2 kompaktowy sedan · 3 średni sedan · 4 średnio-duży ·
**5 limuzyny/luksusowe** · 10 mały SUV · 11 kompaktowy SUV · 12 średni SUV ·
13 średnio-duży SUV · 14 duży SUV · 21–24 MPV od małego do premium.

Każda pozycja niesie: `rank`, `series_name`, `count` (sztuki), `min_price`/`max_price` (万 RMB),
`last_rank` (zmiana m/m), `image`, `series_id`.

Typy `rank_data_type` 2–5 i 10 są **nierozpoznane** — nie wiemy, co mierzą. Nie publikuj
z nich nic, zanim nie ustalisz metodologii.

## Decyzje, których się trzymamy

- **D1** — ranking opisuje **rynek chiński**, nie nasz magazyn. Oferta rotuje, wiedza zostaje.
- **D2** — liczby wyłącznie ze zweryfikowanych źródeł. Zrzuty z WeChat to trop tematyczny,
  nie źródło. Cudzych grafik nie kopiujemy.
- **D4/D8** — dwa odświeżalne bloki: RANKING (rzadko, ręcznie) i OFERTA (automatem,
  `<!--OFERTA:START/END-->`).
- **D7** — jeden stały URL aktualizowany w miejscu; dane miesięczne idą osobno jako news.
- **D9** — weryfikacja krzyżowa liczb między źródłami przed publikacją.
- Ranking **musi mieć numerację i jawne kryterium** — „co to za ranking bez numerków”.
- Rankingi dostają **własny listing**, jak `/aktualnosci/` i `/wiki/`. Na `/marki/` tylko
  sekcja z kilkoma ostatnimi.

## Ograniczenia od Janka

- **Nie interesuje nas polski content** jako źródło — „bo polski to my tworzymy”.
  Szukaj chińskich i anglojęzycznych źródeł.
- **Nie opisujemy procesu importu** — konkurencja nie ma wiedzieć, jak pracujemy.
- Zdjęcia: **z naszych ogłoszeń albo od producentów**, nie cudze grafiki.
- Rozmiar próbki nie jest argumentem przy wyborze tematu artykułu.

## Lista 10 tematów — zaakceptowana, zaczynamy od SUV-ów

Pierwszy wpis: **najchętniej kupowane chińskie SUV-y** (fraza 6 280/mc, największa
w zestawieniu; 1180 aut / 172 modele po naszej stronie). Da się rozbić na podsekcje wg klas
(`outter_detail_type` 10→14), co daje miejsce na warianty long-tail w jednym tekście.

Kolejne mocne: chińskie terenówki i 4×4 (3 450/mc, nikt w PL tego nie obsługuje),
chińskie pickupy (2 480/mc), najtańsze chińskie samochody (910/mc łącznie),
chińskie elektryki-bestsellery (1 880/mc).

## Tytuły zmierzone w DFS — do wykorzystania

„Ultraluksusowe” ma zero wyszukań. Działają potoczne określenia:

```
chińska limuzyna / limuzyny   880 + 880      chiński maybach        880
chiński rolls royce           880            chińskie auta premium  390
chińskie kombi                480            zeekr 007 gt           480
```

Proponowane: **„Chińskie limuzyny — ile kosztuje odpowiednik Maybacha z Chin”**
oraz **„Chińskie kombi i shooting brake’i — Denza Z9 GT, Zeekr 007 GT i reszta”**.

Odkrycie z pomiaru: **„denza z9 gt” ma 14 800/mc** — największa pojedyncza fraza w projekcie,
większa niż „chiński suv” (5 400) i „chińskie marki samochodów” (5 400). W chińskim wydaniu
GT bywa shooting brakiem, więc kombi i GT to często to samo auto.

## Czego świadomie nie robimy

- **Ranking marek wg sprzedaży** — 10/mc, a fraza „chińskie marki samochodów” (5 400)
  należy już do `/marki/`. Publikacja kanibalizowałaby własną stronę.
- Rankingów z nierozpoznanych typów API.

## Kontekst z ostatniej sesji

Przy okazji mierzenia fraz pod tytuły wyszedł osobny task **T-238** (huby, ceny, AEO) —
zamknięty, opisany w `docs/sesje/2026-08-05-t238-huby-ceny-aeo.md`. Stamtąd dwie rzeczy
warte zapamiętania przy rankingach:

1. **Zanim napiszesz nowy tekst, sprawdź w GSC, co już masz na ten temat.** Hub Z9 GT stał
   czwarty przy frazie 14 800/mc z zawyżoną ceną — dociągnięcie go było tańsze niż
   jakikolwiek nowy artykuł.
2. **Denza ma oficjalną dystrybucję w Polsce** (Z9 GT od 526 320 zł w konfiguratorze).
   Przy rankingach limuzyn i GT to gotowy punkt odniesienia — ale cenę sprawdź ponownie,
   rotuje.

Otwarte z T-238, gdyby starczyło czasu: 8 hubów z przedziałami wymagającymi ręcznej
redakcji, cron pilnujący cen, zgłoszenie Z9 GT do reindeksacji.
