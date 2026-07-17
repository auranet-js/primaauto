# 2026-07-17 — DSA: feed lepki, koniec reguły „najtańsza sztuka per model"

**Status:** wdrożone
**Kontekst:** koryguje `2026-07-16-dsa-feed-na-oferty.md` (sam kierunek — oferty zamiast hubów — zostaje bez zmian)

## Problem

Cron `dsa-offer-feed-refresh.py` (6:15 dziennie) utrzymywał w feedzie DSA **najtańszą sztukę per model**
i podmieniał URL, gdy pojawiła się tańsza. Pomiar 17.07 pokazał, że reguła rotuje sama z siebie:

- **10/10 sztuk wyrzuconych z feedu nadal było `publish`** — żadna nie zeszła ze sprzedaży
- **6 z 7 podmian miało różnicę ceny = 0 zł** — przerzucanie między bliźniakami o identycznej cenie
  (ta sama seria, ten sam rocznik, obie żywe). Przy remisie reguła nie ma czego rozstrzygać, więc
  pierwotny builder (`build-dsa-offer-feed.php`, grupowanie po `make_slug/serie_slug`) i cron
  (`dsa-offer-feed-refresh.py`, grupowanie po `serie.term_id`) typowały innego bliźniaka.
- jedyna podmiana z realnym uzasadnieniem: voyah/free 176 000 → 175 000 zł

Każda podmiana URL kosztuje: nowy URL musi zostać przetworzony przez Google, zanim zacznie serwować.

## Decyzja

**Wpis zostaje w feedzie, dopóki jego sztuka żyje (`post_status='publish'`). Podmieniamy wyłącznie
wpisy, których sztuka wypadła z publish.** Następca = najtańsza żywa sztuka tego samego modelu.
Brak żywej sztuki w modelu → wpis znika.

**Cron: 6:15 co 3 dni** (`15 6 */3 * *`) zamiast codziennie.

## Uzasadnienie

1. **Bycie najtańszą sztuką nie jest do niczego potrzebne.** Nagłówek DSA bierze cenę z `<title>`
   **tej** strony, która jest w feedzie, liczonego przy renderze (`class-asiaauto-single.php:1007`)
   — cena w reklamie nigdy nie kłamie, niezależnie od tego, czy sztuka jest najtańsza w modelu.
2. **Zeszła oferta nie daje 404, tylko 301 na hub modelu** (potwierdzone:
   `/oferta/nio-es6-2025-320985/` → 301 → `/samochody/nio/es6/`). Pierwotne uzasadnienie crona
   („URL prowadzi w 404, reklama umiera") było **fałszywe**. Reklama żyje dalej, traci tylko cenę
   w nagłówku. To degradacja miękka, nie awaria → nie wymaga dobowej czujki.
3. **Tempo schodzenia sztuk:** ~12 listingów / 30 dni opuszcza publish (~1 co 2–3 dni z 3058).
   Co 3 dni jest z zapasem. Tygodniowo **nie** — wtedy przez kilka dni część reklam po cichu wraca
   na title huba, czyli do tego, od czego uciekliśmy 16.07.

## Konsekwencje

- Feed przestaje rotować bez powodu: dry-run 17.07 15:40 → **0 zmian, 133 sztuki żyją**
  (stara reguła przemieszałaby je nazajutrz o 6:15).
- Feed przestaje gonić „najtańszą" — wpis może z czasem nie być najtańszą sztuką w modelu.
  Świadomie akceptowane (patrz uzasadnienie 1).
- Log rozróżnia teraz powód usunięcia (`sztuka zeszla (draft)` + wskazanie następcy) zamiast
  dawnego zbiorczego „sztuka zeszla albo nie jest juz najtansza", który maskował, co się dzieje.
- Rozbieżność tie-breaku między `build-dsa-offer-feed.php` a cronem **zostaje** (nie ruszana) —
  przy lepkiej regule nie ma znaczenia, bo builder odpala się tylko przy pełnej przebudowie feedu.

## Otwarte

Kampania stanęła 17.07 (104 wyświetlenia vs ~1300/dz, oferty 0/133) po hurtowej podmianie
130 hubów → 133 oferty z 16.07 21:45. Konfiguracja czysta, oferty zaindeksowane (13/15 PASS
w URL Inspection) → hipoteza: lag propagacji page feedu. **Ocena 18.07 rano** — jeśli oferty nadal
0, to nie lag i kopiemy dalej.
