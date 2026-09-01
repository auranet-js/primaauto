# YouTube — odzyskany dostęp i pierwszy uczciwy pomiar ruchu (01.09.2026)

## Korekta do commita a13f8e6

Opis tamtego commita mówi, że konto marki Prima-Auto istniało i że filmy wgrywał Andrzej.
**Oba zdania są nieprawdziwe.** Konto marki **nie istniało** — Ruslan utworzył je 01.09
i to była właściwa przyczyna, dla której od lipca nie dało się nadać dostępu (nie ma kogo
zapraszać na zwykłym koncie Google). **Filmy wgrywaliśmy my**, obie fale.

Skąd błąd: zobaczyłem gotowe konto marki na ekranie i uznałem je za zastane, nie sprawdzając
daty utworzenia; autorstwo filmów przypisałem Andrzejowi z rozpędu, mimo że wzorzec tytułów
(„model za X tys. zł — opis, od ręki w Rzeszowie") jest nasz i widać go w danych.

## Dostęp

Konto marki **Prima-Auto** wisi pod kontem Google `primaauto@gmail.com`, nie pod głównym
kontem Ruslana `mwbrothers.pl@gmail.com` — tam `myaccount.google.com/brandaccounts` pokazuje
**pustą listę** i wygląda, jakby konta marki nie było. Pułapka warta zapamiętania.

`js@auranet.com.pl` dodany jako **Администратор/administrator** (role do wyboru to tylko
Владелец/Администратор — „menedżera" tam nie ma). Po akceptacji zaproszenia re-OAuth:
`youtube-tokens.json` przepięty z tożsamości **Victorini** na **Prima-Auto**
(kopia: `youtube-tokens.json.backup-victorini-2026-09-01`).

**Bloker zapisu przez API z 24.07 zdjęty** — metadane, opisy i publikacje idą przez API,
Chrome automation przez Studio nie jest już potrzebna.

## Pomiar ruchu — teza z lipca obalona

YouTube Analytics, okno 24.07–31.08, `insightTrafficSourceType`:

| źródło | wyświetlenia | udział | śr. czas |
|---|---|---|---|
| **ADVERTISING** | **66 044** | **93,5%** | 10 s |
| SHORTS (feed) | 3 208 | 4,5% | 7 s |
| YT_SEARCH | 639 | 0,9% | **21 s** |
| SUBSCRIBER | 315 | 0,4% | 25 s |
| pozostałe | 454 | 0,6% | — |
| **razem** | **70 660** | | |

**Kanał nie urósł organicznie — ten ruch został kupiony.** Koszt kampanii w tym samym oknie:
`[DG]` 796,65 zł + `[VID]` 526,07 zł = **1 322,72 zł**, czyli **2 grosze za wyświetlenie**.
Organik przez pięć tygodni: 4 616 wyświetleń.

Zapis z 25.07 („~92% to organiczny feed Shorts") powstał **dwa dni po starcie kampanii**,
gdy `[DG]` dowiozła dopiero 288 obejrzeń, i uogólniał chwilowy stan na regułę.
**Wniosek „nie ruszamy tytułów i miniatur w trakcie fali, bo reset CTR ucina falę"
stracił podstawę** — fali organicznej nie ma, jest budżet. Tytuły i miniatury można testować.

Rozkład per film potwierdza to od drugiej strony: dwa filmy użyte w kreacjach `[DG]` —
Exeed VX (Omoda 11) `43 416` i Leopard 5 czarny `13 640` — dają 57 tys. z 73 tys. całego
kanału, a filmy z fali sierpniowej, nieużyte w reklamach, mają po 40–420 wyświetleń.

**Jakość ruchu jest odwrotna do wolumenu.** Wyszukiwarka YouTube daje dwukrotnie dłuższe
oglądanie niż reklamy (21 s vs 10 s), subskrybenci trzykrotnie. Suma wyświetleń kanału jest
metryką budżetu, nie zainteresowania — przy ocenie nie patrz na nią samą.

## Fałszywy alarm — `private` to nie zawsze porzucony film

Trzy filmy z 26.08 mają `privacy=private` i zero wyświetleń, co wziąłem za usterkę do naprawy.
Są **zaplanowane** — pola `status.publishAt` (którego początkowo nie pobrałem) mówią:
`1xS5HBbJgXU` Lynk & Co 900 → 01.09 16:00, `WM25drIlPAA` Denza N9 → 01.09 18:00,
`n9KN2RR-v8M` Lynk & Co 900 → 02.09 18:00 (czas polski). Kolejka jeden-na-dzień działa
zgodnie z regułą z 25.07.

**Reguła:** przy `privacyStatus=private` zawsze czytaj `publishAt`, zanim nazwiesz coś usterką.
Ręczne przestawienie tych filmów na `public` wypuściłoby trzy pozycje naraz i zepsuło harmonogram.

## Gotchy pomiarowe

- `metrics.video_views` i `metrics.views` **nie istnieją w Google Ads API v25**
  (`UNRECOGNIZED_FIELD`). Działają `impressions`, `clicks`, `engagements`, `interactions`,
  `video_quartile_p100_rate`. Obejrzeń wideo nie policzysz od strony Ads.
- **YouTube Analytics API wymagało włączenia** w projekcie `325733204269` (Piaskownica).
- Klucz `psi-crux-key.txt` nie działa do YouTube Data API (`API_KEY_SERVICE_BLOCKED`).
