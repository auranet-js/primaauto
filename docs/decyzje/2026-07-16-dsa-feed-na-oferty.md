# 2026-07-16 — DSA: feed celuje w najtańszą ofertę zamiast w hub modelu

## Kontekst

Audyt kampanii `[DSA] Import modele z Chin` (`23896725555`, konto `9506068500`) wykazał dwie rzeczy.

**1. Rework z 12.07 zadziałał w połowie.** `tmp/gads_dsa_rework_2026_07_12.py` dodał 43 huby do page
feedu **bez etykiety `dsa2026`**, a grupa `197286896339` kieruje wyłącznie warunkiem
`CUSTOM_LABEL EQUALS 'dsa2026'`. Skutek: 43 huby leżały martwe 4 dni z **zerem wyświetleń**,
niezauważone, bo skrypt wypisał „OK" i nikt nie sprawdził impresji.

Skutek uboczny był mylący: martwy hub nie daje „braku reklamy" — Google podstawia **najbliższy
etykietowany** hub. Stąd fałszywa diagnoza „Google źle dopasowuje": `zeekr 9x` → `xpeng/x9`
(bo `zeekr/9x` leżał bez etykiety), `denza n9` → `denza/d9-dm-i`, `deepal s09` → `deepal/s07`.

**2. Nagłówka DSA nie da się uintencyjnić.** Google buduje go z title strony docelowej. Title huba
jest celowo informacyjny („Li Auto L9 — cena w Polsce i import z Chin"), bo musi rankować w organicu
na „cena X w Polsce". Ten sam string obsługuje dwa sprzeczne cele: SEO chce informacji, Ads chce
intencji. Efekt zmierzony na 30 dniach: CTR 9,9%, ale **CPA 195 zł przy 37% budżetu konta za 11% konwersji**.

## Decyzja

**Feed DSA celuje w najtańszą sztukę per model (`/oferta/{slug}/`), nie w hub modelu.** Decyzja Janka:
„pierdol huby, daj title najtańszej wersji".

Powód: title oferty niesie konkret — `Li Auto L9 Pro 2024 - 256 000 PLN, 40 000 km | Prima-Auto`,
a desc dorzuca paliwo i moc (`EREV, 449 KM, 40 000 km, 256 000 PLN`). Meta liczą się **przy renderze**
(`class-asiaauto-single.php:1007`, moc przez `AsiaAuto_Inventory::resolvePower()`), więc cena w reklamie
**nigdy się nie zdezaktualizuje** — inaczej niż w wariancie B title huba, który emitował
„XPENG X9 — od 228 000 PLN, 16 sztuk" i „od 236 000 PLN, 7 sztuk" dla tego samego huba (cache snapshotu).

### Odrzucone warianty

- **Filtr `/samochody/?marka=X&model=Y`** (propozycja „linkuj do filtra po cenie") — **ma `noindex`**,
  a DSA korzysta z indeksu Google, więc nie zaserwowałby. Sortowanie po cenie i tak nie działa
  (`sort=price`, `orderby=price` — bez efektu).
- **Zmiana title hubów pod Ads** — rozwaliłaby SEO hubów; huby zostają nietknięte.
- **Copy per hub w opisach DSA** — opis należy do grupy, nie do URL-a; wymagałoby 134 grup
  z `URL_EQUALS`. Zbędne, skoro title oferty niesie to samo.
- **Kotwica `#oferty` na hubach** — wdrożona i **cofnięta** tego samego dnia. Oferta nie ma tej kotwicy
  (ma `#inne-egzemplarze`). Pomiar: kotwica przeskakiwała ~1950 px (~2,5 ekranu mobile), z czego
  **84% to tabela spec, a tylko 16% to lead AEO** — czyli wyrzucała 308 px wartości, by ominąć 1640 px bariery.

## Wdrożenie (16.07)

| Skrypt | Efekt |
|---|---|
| `tmp/gads_dsa_optimize_2026_07_16.py` | tracking template, 3 negatywy brandowe PHRASE, usunięcie 38 martwych wpisów |
| `tmp/gads_dsa_fix_labels_2026_07_16.py` | podpięcie 16 hubów z etykietą (0 bez etykiety) |
| `tmp/gads_dsa_cut_cjk_2026_07_16.py` | wycięcie `exeed/es` (3/3 CJK) i `geely/a7-em` (21/38 CJK) |
| `tmp/gads_dsa_switch_to_offers_2026_07_16.py` | feed 134 huby → oferty, zdjęcie tracking template |
| `scripts/dsa-offer-feed-refresh.py --apply` | naprawa gate: 69 wpisów wymienionych |

**Stan końcowy:** feed **133 oferty**, 100% rocznik 2025/2026, wszystkie z etykietą `dsa2026`,
zero hubów, tracking template zdjęty, kampania ENABLED.

## Gotchy złapane po drodze

1. **`pageFeedAsset` jest immutable** — etykiet nie da się dodać update'em. Trzeba: nowy asset z `labels`
   → odpiąć stary `assetSetAsset` → podpiąć nowy. **Kolejność obowiązkowa**, inaczej `DUPLICATE_EXTERNAL_KEY`.
   W dry-run krok 2 zawsze zgłasza ten błąd (krok 1 się nie wykonał) — artefakt walidacji, nie problem.
2. **Czyszczenie `trackingUrlTemplate` pustym stringiem** → `stringLengthError: TOO_SHORT`. Trzeba `null`.
3. **Brak tiebreakera w `ORDER BY price`** — przy równych cenach MySQL zwracał raz jedną sztukę, raz drugą,
   więc cron wymieniał te same 7 wpisów w kółko, kasując historię reklam. Fix: `ORDER BY price ASC, ID ASC`.
   Idempotencja zweryfikowana trzema przebiegami.
4. **Pierwszy apply feedu złamał gate** — zbudowany z `targets.tsv` (artefakt symulacji, bez filtra
   `ca-year`), więc 66/134 sztuk miało rocznik 2023–2024. Wykryte przez dry-run crona, naprawione.
   Wniosek: **artefakt symulacji nie jest źródłem prawdy do wdrożenia** — generator jest.
5. **CJK w tytułach ofert: 43 sztuki / 15 modeli** (`Galaxy 银河A7 EM`). Dług z importów 10.05–30.06,
   nie regres (feed Dongchedi zamrożony od 01.07). `π` w `Dongfeng eπ008` to **oficjalna nazwa** — nie czyścić.

## Konsekwencje

- **Huby przestają obsługiwać płatny ruch** — lead AEO, wiki i FAQ pracują wyłącznie dla organica.
- **Sztuka schodzi → wpis gnije.** Feed celuje w konkretny egzemplarz; po sprzedaży URL idzie w draft/trash
  i reklama prowadzi w 404/301. Najtańsza sztuka jest najatrakcyjniejsza, więc schodzi pierwsza.
  Tempo zmierzone 16.07: ~12 listingów/30 dni opuszcza `publish` (~1 co 2–3 dni z 3058).
  → **cron `scripts/dsa-offer-feed-refresh.py` codziennie 06:15** (crontab, obok istniejących wpisów).
- **Cofa decyzję z ADR 2026-06-02**, który wskazywał single `/oferta/` jako „korzeń słabej konwersji"
  i dlatego kierował DSA na huby. Jeśli powód nadal aktualny — wrócimy do punktu wyjścia.
- **Zakres celowo bez zmian** (te same modele co przed zmianą), żeby recheck mierzył wyłącznie zmianę
  landingu. Generator `scripts/build-dsa-offer-feed.php` dałby 194 modele (próg „min. 3 sztuki" traci sens,
  gdy celujemy w konkretną sztukę) — osobna decyzja.

## Opisy reklam — decyzja Janka (16.07, po wdrożeniu feedu)

Moje propozycje copy (P1/P2/P3, oparte na cenie pod klucz i terminie) **odrzucone**. Powód, którego
nie widziałem: **cenę niesie już nagłówek** (title oferty), więc powtarzanie „cena pod klucz" w opisie
marnuje miejsce. Opis ma mówić to, czego w nagłówku NIE ma — co robimy dla klienta i co ma zrobić dalej.

Copy Janka:
> „Zamów dla siebie — sprowadzimy i załatwimy formalności. Możliwy leasing." (72 zn.)
> „Sprawdź podobne oferty tego modelu." (35 zn.)

- **„Możliwy leasing"** — spójne z paskiem zaufania z v0.33.31.
- **„Sprawdź podobne oferty"** — trafia w blok „Inne egzemplarze" (T-187), obecny na każdej ofercie,
  więc reklama obiecuje dokładnie to, co czeka na landingu.
- **Zero obietnicy terminu** — „90 dni" nie istnieje w żadnym źródle (wiki: „kilka–kilkanaście tygodni").

**Wymieniona TYLKO jedna z dwóch reklam.** `816552895918` („Zarezerwuj teraz - auto weryfikujemy przed
zakupem…") zaakceptowana przez Janka i **zostaje nietknięta — zachowuje historię i status APPROVED**.
Wymieniona wyłącznie `816552895915` („Zamów online - bez salonu…") → nowa `817108048038`.

Gotcha: **reklama w Google Ads jest immutable** — opisu nie da się zedytować, trzeba utworzyć nową
i usunąć starą. Kolejność: **najpierw create, potem remove**, żeby grupa nie została bez reklamy.
Nowa kreacja startuje z zerową historią i przechodzi ponowną weryfikację (`UNKNOWN` → `APPROVED`).
Skrypt: `tmp/gads_dsa_rewrite_desc_2026_07_16.py`.

## Otwarte
- **20 hubów serwowało mimo niespełnienia gate** rocznika (`jetour/t2` 69 kl/30d) — temat zniknął wraz
  z przejściem na oferty, ale wróci przy decyzji o zakresie.
- **`$NON_CHINESE` niekompletna** — zawiera Mazdę, MG, Nissana, ale nie Forda (`ford/ford-bronco`
  przechodzi; Bronco produkowane w Chinach przez JMC, zostawione świadomie).
- **Dźwignią na CPA jest lejek, nie reklama.** Wszystko zimne kosztuje 98–608 zł za konwersję — także SKAG
  z własnymi intencyjnymi nagłówkami RSA. Tanio jest tylko ciepło: Brand 6 zł, remarketing 12 zł.
  Konwersje Ads to w większości zdarzenia miękkie (klik WhatsApp/telefon), nie leady w bazie
  (`_asiaauto_lead`) — zestawienie z realnymi leadami przed decyzją o budżecie DSA (1365 zł/mc).

## Weryfikacja

- Recheck startu: **17.07 13:00**, raport mailem (`scripts/dsa-offer-report.py`, cron, `send-to-jan`).
  Kryterium: oferty z impresjami > 0. **Dowodem są impresje, nie „OK" ze skryptu** — patrz lekcja z 12.07.
- Recheck wyniku: **30.07**, CTR/CPA vs baseline sprzed zmiany (1755 kl / 1064 zł / CPA 195 zł / 30 dni).
- Rollback: `~/backups/primaauto/2026-07-16-dsa/snapshot-przed-oferty.json` (feed + kampania),
  `crontab-przed.txt` (crontab).
