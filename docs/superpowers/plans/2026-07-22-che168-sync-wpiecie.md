# Plan wpięcia syncu Che168 (T-186, Faza 2 „lite")

> Data: 2026-07-22 · Stan pluginu: v0.34.0 · Wszystkie liczby poniżej **zmierzone dzisiaj** na produkcji, w trybie read-only (zero zapisów do bazy).
> Skrypty pomiarowe: `tmp/che168-dryrun/` (scratchpad sesji) — do przeniesienia do `scripts/` przy wdrożeniu.

## 1. Fakty z pomiarów (fundament decyzji)

### Kanał `/changes` — żyje i jest duży

| Doba | Zdarzeń che168 |
|---|---|
| 17→18.07 | 27 904 |
| 18→19.07 | 22 843 |
| 19→20.07 | 22 218 |
| 20→21.07 | 30 844 |
| 21→22.07 | 20 179 |

Dongchedi w tym samym pomiarze: **8 981 zdarzeń/dobę**, ale `added` ≈ 2 (feed zamrożony od 01.07).

Próbka 2400 zdarzeń che168 (120 batchy): `added` 901 (37,5%), `changed` 649, `removed` 850.
Z 901 `added` filtr przepuszcza **8** → **0,9%**. Ekstrapolacja na dobę: **60–100 nowych ofert dziennie**.

### Adapter jest warunkiem koniecznym — i wystarczającym

Próbka 730 ofert (65 marek × 1 strona `getOffers`), filtr = prawdziwy `isAllowedByConfig()` przez Reflection:

| Wariant | Przechodzi filtr |
|---|---|
| dziś (automat bez adaptera, dane surowe) | **0 / 730** |
| po wpięciu adaptera | **89 / 730 (12%)** |

Przyczyna zera: 730/730 surowych rekordów che168 **nie ma pola `city`** (lokalizacja siedzi w `address`). Filtr miast ucina wszystko. Czyli `run('che168')` dopisane dziś do crona nie zaśmieciłoby bazy — po prostu nie zaimportowałoby nic.

Rozkład odrzutów po adapterze: miasto (samo lub w kombinacji) ≈ 575, cena ≈ 170, przebieg ≈ 94.

### Jakość mapowania

Trafialność w istniejące huby: **70% całej próbki**. Ale wśród 89 przechodzących orphanów jest 17 (19%), przy gate z T-186 <5%. Dwie różne kategorie:

- **do domapowania:** `Yangwang|仰望U7`, `Yangwang|仰望U8 L`, `Leapmotor|零跑B01` (CJK — wleciałoby chińszczyzną do tytułu), `BYD|L EV`, `Changan|UNI-K`, `Exeed|Zhaofeng`, `HiPhi|HiPhi X`, `Yangwang|Yangwang U9`
- **świadome skipy ICE, których nie chcemy:** `Lotus|Emira`, `Mazda|CX-30`, `Nissan|Teana`, `Nissan|Qashqai` — te **przechodzą filtr**, więc automat by je wciągnął

### Specyfikacja ze źródła

Pomiar na 10 ofertach che168 (pełne `getOffer`): **66–106 pól `extra_prep`** (mediana ~92), z czego 15–20 to nieznane `param_*` (ukryte w UI). Dongchedi zdrowa oferta: **339–369 pól**.

Wniosek: che168 daje ~25–30% objętości specyfikacji dongchedi — to granica źródła (brak list wyposażenia), znana od v0.33.8. **Mechanizm ratunkowy już istnieje**: `scripts/merge-spec-from-twin.php` (commit 43e14df) dopasowuje po `make|serie|complectation|ca-year` niezależnie od źródła, dolewa wyłącznie brakujące klucze i pomija wpisy ręczne oraz powiązane z zamówieniem. Oferty che168 mogą dziedziczyć pełną specyfikację po bliźniaczych ofertach dongchedi z bazy.

### Geografia — filtr miast jest główną gilotyną

Ta sama próbka 730 ofert: **477 spełnia wszystko oprócz miasta**, **89 spełnia wszystko razem z miastem**. Filtr miast odcina **388 dobrych ofert = 81% straty**.

Kształt danych:
- **che168**: `address` = `"miasto, dzielnica"` (`"杭州, 拱墅"`, `"东莞, 寮步镇"`, często dzielnica pusta `"成都, "`). Adapter bierze część przed przecinkiem. Poziom dzielnicy jest dostępny, prowincji **nie ma**.
- **dongchedi**: osobne `city` (miasto) + `address` = pełny adres pocztowy, czasem z prowincją (`"安徽省宣城市..."`). Prowincji jako pola też **nie ma**.
- Nasza lista trzyma `province` jako etykietę wpisaną ręcznie — filtrowanie po prowincji wymagałoby dorobienia mapy miasto→prowincja (dla realnego wolumenu wystarczy top ~50 miast).

Miasta z listy, które realnie coś przepuszczają: Dongguan 28, Shenzhen 15, Kanton 10, Foshan 9, Xiamen 8, Haikou 3, Quanzhou 3, Jiangmen 3. **Pozostałe 23 z 31 miast: zero ofert.**

Największy wolumen poza listą: Szanghaj 26, Wuhan 24, Chongqing 21, Chengdu 20, Shijiazhuang 16, Zhengzhou 15, Hefei 12, Xi'an 12, Guiyang 11, Wuhu 11, Wuxi 10.

**Skąd wzięła się lista 31 miast** (`docs/QUEUE.md`, Zadanie 6): Prima Auto ma ludzi na miejscu w Guangdong, Fujian, Guangxi i Hainan i może **fizycznie sprawdzać auta tylko tam**. Ruslan podał 10 miast, my rozszerzyliśmy do 31 w obrębie tych samych 4 prowincji. To ograniczenie **logistyczne, nie techniczne** — jego zmiana jest decyzją Ruslana o modelu operacyjnym, nie ustawieniem w panelu.

### Defekt uboczny (dotyczy też dongchedi, nie tylko che168)

**15 z 31 miast na liście importu nie ma tłumaczenia** w `translations-cities.php`. Skutek na produkcji: **89 opublikowanych ofert ma chińską nazwę miasta w polu lokalizacji** — 江门 28, 汕头 15, 揭阳 14, 贵港 7, 宁德 7, 韶关 7, 茂名 7, 肇庆 4, i dalej. Widoczne dla klienta na froncie. Fix: 15 wpisów + backfill meta `stm_car_location`.

## 2. Zmiany w kodzie

Wszystko addytywne, zgodnie z ADR `2026-06-17-che168-normalize-at-entry.md`. `importListing()` / `setTaxonomies()` / `setMotorsMeta()` — **nietknięte**.

**a) Osobne wyłączniki per źródło.** Dziś `asiaauto_sync_enabled` gasi oba źródła naraz (i jest dziś na `0`, świadomie — regresja `extra_prep` u dongchedi). Nowe: `asiaauto_sync_enabled_dongchedi` / `asiaauto_sync_enabled_che168`, z fallbackiem na globalny gdy brak wpisu. Toggle w panelu obok istniejącego.

**b) Adapter w ścieżce automatycznej.** W `AsiaAuto_Sync::importWithFullData()`: jeśli `$source === 'che168'` → `AsiaAuto_Che168_Adapter::normalize()` na danych z `getOffer()` **i** na fallbacku z `/changes`. Jedno miejsce, dwie linie.

**c) Guard „pomijaj niezmapowane".** Dla che168: gdy `AsiaAuto_Mapping::getEuForCn(mark, model) === null` → skip + wpis do kolejki domapowań (log/opcja do przeglądu w panelu). Chroni jednocześnie przed CJK w tytułach i przed ICE (Emira, Qashqai, Teana, CX-30).

**d) Cron handler.** `run('dongchedi')` i `run('che168')` niezależnie, każdy za swoim wyłącznikiem. Lock i kursor `change_id` są już per-source — nie wymagają zmian.

**e) Kursor startowy che168 — ustawić świadomie.** Przy pierwszym biegu `run()` bierze `change_id` z wczoraj, czyli 20–31 tys. zdarzeń do przemielenia wstecz. `max_batches = 100` = 2000 zdarzeń na bieg; przy cyklu 15 min zdolność to ~192 tys./dobę wobec ~25 tys. potrzeby, więc nadrobi — ale pierwsze biegi będą długie i zaimportują dobę wstecz. Alternatywa: `asiaauto_last_change_id_che168` ustawić na „teraz" i startować od bieżących ofert.

**f) Status importu na czas obserwacji.** `importListing()` przyjmuje `$status` — dla che168 przez pierwsze biegi `'draft'`, przegląd ręczny, dopiero potem `'publish'`.

**g) Dedup po VIN.** Che168 ma VIN w 100% ofert. Lekki guard: przed importem sprawdzić, czy VIN już jest w bazie → skip + `_asiaauto_duplicate_of`. Zmierzony overlap egzemplarzy dongchedi↔che168 = 0/30, więc pełna heurystyka jest zbędna.

## 3. Kolejność wykonania

1. Domapowanie 8 orphanów z listy powyżej (`che168-model-map` / `brand-mapping`) — zbija 19% orphanów do okolic gate'u <5%.
2. 15 brakujących tłumaczeń miast + backfill 89 listingów z CJK w lokalizacji (osobny, samodzielny fix — wart wdrożenia niezależnie od che168).
3. Punkty a–d z sekcji 2, `php -l`, `.bak` przed nadpisaniem.
4. Bieg ręczny (`wp eval`) z `che168` na `draft`, kursor ustawiony na „teraz", jeden cykl. Oczekiwane: kilka–kilkanaście ofert.
5. Przegląd na froncie: tytuł bez CJK, specyfikacja po polsku, zdjęcia, cena, hub, lokalizacja.
6. `merge-spec-from-twin.php --dry-run` na tych draftach — ile pól doleje z bliźniaków dongchedi.
7. Dopiero po tym: `publish`, wyłącznik che168 na `1`, cron.
8. Obserwacja dobowa: ile weszło, ile pominiętych niezmapowanych, kolejka domapowań.

## 4. Decyzje do podjęcia

**Geografia (Ruslan).** Filtr miast kosztuje 81% dobrej podaży che168: ~60–100 ofert/dobę zamiast ~300–500. Pytanie nie brzmi „czy rozszerzyć filtr", tylko **czy ludzie Ruslana obsłużą auta spoza Guangdong/Fujian/Guangxi/Hainan** — a jeśli nie, czy dopuszcza zakup bez oględzin na miejscu albo transport wewnętrzny z Szanghaju/Wuhanu/Chongqingu/Chengdu (razem 91 dobrych ofert w próbce 730, czyli więcej niż cała obecna lista 31 miast daje).

**Wolumen.** 60–100 ofert/dobę to ~2–3 tys. miesięcznie — inny rząd wielkości niż dzisiejszy kuratorski import per numer. Do ustalenia, czy katalog ma tak rosnąć, czy filtry mają być węższe (np. wyższy próg ceny).

**Specyfikacja.** Czy ~90 pól ze źródła akceptujemy, czy che168 zawsze przechodzi przez `merge-spec-from-twin` przed publikacją.

## 5. Ryzyka i rollback

- Największe ryzyko to nie import śmieci (filtr + guard niezmapowanych to blokują), tylko **wolumen** — 100 ofert/dobę na `publish` bez przeglądu zmienia charakter katalogu. Stąd `draft` na start.
- Rollback: wyłącznik `asiaauto_sync_enabled_che168` na `0` zatrzymuje wyłącznie che168, dongchedi bez zmian. Kursor `change_id` per-source pozwala wznowić od miejsca zatrzymania.
- Strefa krucha (`importListing` i niżej) pozostaje nietknięta — zmiany są w `Sync` i w mapach danych.
