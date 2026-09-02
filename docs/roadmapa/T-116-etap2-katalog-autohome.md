# T-116 / etap 2 — domknięcie katalogu Autohome

> Prompt wykonawczy do odpalenia w nowym wątku w `~/projekty/primaauto`.
> Zakres wybrany przez Janka 2026-09-02: **tylko dane**. Filtrów, UI wyszukiwarki,
> tabeli `wp7j_asiaauto_specs` ani deep-linków **nie ruszasz** — to osobna decyzja
> po zobaczeniu kompletu danych. Nie wchodź w nie bez polecenia.
>
> Wszystkie liczby w tym dokumencie są zmierzone 2026-09-02 na produkcji.
> Zweryfikuj kluczowe na starcie — mogły się zmienić.

---

## 1. Po co to robimy

Wyposażenie w `_asiaauto_extra_prep` ma dziś flagi tylko na **42% ofert (1250 z 3000)**.
Cel etapu: **~95% (2860)**. Bez tego nie ma o czym rozmawiać przy filtrach zaawansowanych —
58% bazy nie ma ani jednej flagi wyposażenia, więc filtr „masaż" czy „lidar" zwracałby
przypadkowy podzbiór, nie odpowiedź.

## 2. Stan wejściowy — co jest prawdą, a co nieaktualne w spec T-116

Spec `docs/roadmapa/T-116-wyszukiwarka-zaawansowana.md` powstał 2026-07-14 i **rozjechał się
z rzeczywistością w czterech miejscach**. Nie sugeruj się nim bez sprawdzenia:

| zapis w T-116 | stan zmierzony 02.09 |
|---|---|
| „Dongchedi 96% bazy" | **odwrócone**: che168 1938 (67%), dongchedi 957 (33%) |
| `_asiaauto_horse_power` pokrycie 100% | **40%** (1188 / 3005); to samo `_asiaauto_owners_count` |
| 🐛 uszkodzony unicode (`u4e2d` bez ukośnika) blokuje filtrowanie | **bloker nie istnieje** — 0 błędnych JSON na 2873 ofertach. Nie szukaj go, nie „naprawiaj" |
| che168 = 37 flag wyposażenia, „5× uboższy" | z auto-api **~0 flag**, ale z katalogu Autohome **303 parametry** — więcej niż dongchedi API (282) |

Ostatni wiersz odwraca wniosek strategiczny: wyposażenie na che168 nie jest uboższe,
tylko **niepobrane**.

Pozostałe fakty wejściowe:
- che168 ma `_asiaauto_spec_id` na **1923 z 1938 ofert (99%)**; unikalnych specid: **720**.
- dongchedi ma `spec_id` na 0 ofert (korzysta z własnego API) — poza tym etapem.
- `scripts/autohome-catalog-fetch.js` **działa**: 303 parametry, 24 grupy, 0 nieodszyfrowanych znaków, 4/4 pobrania OK.
- Zmierzony koszt pełnego backfillu: **~70 min** (4 pobrania / 23 s przy throttle 2 s).

## 3. Dlaczego nocne dolewanie nie wystarcza

**Ważne, żeby nie wyważać otwartych drzwi:** wzbogacanie **działa co noc**, dwoma cronami:

- `35 4 * * *` → `merge-spec-from-twin.php apply` (bliźniak z dongchedi)
- `45 4 * * *` → `dolej-spec-z-banku.php` (bank wariantów)

Oba działają poprawnie i realnie uzupełniają oferty (np. 85 → 365 pól). **Ale wyczerpały dawców**,
bo oba czerpią wyłącznie z tego, co już mamy lokalnie:

| okres | bliźniak /dobę | bank /dobę |
|---|---:|---:|
| 20–25.08 | 42–164 | 5–12 |
| 26.08–02.09 | 3–12 | 1–2 |

Ostatniej nocy razem **7 ofert**. Log banku z tej samej nocy: **926 ofert z <100 polami czeka,
a dla 925 nie ma dawcy**. Przyczyna jest strukturalna — dongchedi jest zamrożone i się kurczy,
che168 rośnie, więc dawców nie przybywa, a ubogich ofert przybywa codziennie.

**Katalog Autohome jest jedyną ścieżką niezależną od stanu lokalnego** i jedyną nieautomatyczną.
720 specid pokrywa 1923 oferty bez względu na to, czy istnieje bliźniak.

Dowód, że katalog chodzi ręcznie (trzy niezależne przesłanki):
1. Rozkład `_asiaauto_spec_catalog_at`: 27.07 (2), 29.07 (10), 30.07 (94), 13.08 (527), 20.08 (1) —
   nierówne partie w nierównych odstępach. Ostatni przebieg **20.08**, a oferty che168 wchodzą codziennie.
2. `wp cron event list` — pięć haków `asiaauto_*`, żadnego katalogowego.
3. `crontab -l` — jest bliźniak i bank, nie ma nic z `autohome-catalog`.

---

## 4. Krok 1 — poprawka fetchera (rdzeń etapu)

**Diagnoza zweryfikowana, nie hipoteza.** `scripts/autohome-catalog-fetch.js` czyta wartość
wyłącznie z `valueitems[].value`. Dla `displaytype: 1` to pole jest puste, a dane siedzą
w `valueitems[].sublist[]`.

Pomiar: specid 73246 → **99 z 303 pozycji pustych, 99 z 99 ma sublist**. Powtórzone na 72159 → 82/229.
To nie jest losowa jedna trzecia — puste są dokładnie pola **złożone**, czyli te, o które chodzi
w całym tasku: fotele (masaże), kamery (360°), mirroring telefonu, napęd, tapicerka, liczba kamer.

Struktura `sublist` — nazwa funkcji jest w `subname`, flaga w `subvalue`:

```json
"手机互联/映射": [{"price":0,"subname":"支持HUAWEI HiCar","subvalue":1}]
"前排座椅功能":  [{"subname":"加热","subvalue":1},{"subname":"通风","subvalue":1},{"subname":"按摩","subvalue":1}]
"整车质保":     [{"subname":"","subvalue":"六年15万公里"}]
```

Reguła: `subname` niepuste → bierz `subname` (to nazwa funkcji), puste → `subvalue` (to wartość).

Zmiana to jedna linia `value: dec(...)` w pętli budującej `rows`:

```js
value: dec(v ? (String(v.value || '').trim()
  ? v.value
  : (v.sublist || [])
      .map((x) => (String(x.subname || '').trim() ? x.subname : x.subvalue))
      .filter((y) => y !== '' && y !== null && y !== undefined)
      .join(' / ')
) : ''),
```

**Bramka — bez tego nie idź dalej:** na 3 losowych specid z produkcji liczba pozycji z pustą
wartością musi spaść do **0**, a `前排座椅功能` musi zwrócić listę typu `加热 / 通风 / 按摩`.
Kod wyjścia nadal 0 (brak `□`).

## 5. Krok 2 — rozszerzenie `data/autohome-catalog-map.php`

Dziś **218 wpisów**. W próbce 14 specid pojawiły się **393 unikalne nazwy, 182 niezmapowane** —
pełna lista z grupami, częstością i przykładową wartością:
**`docs/roadmapa/T-116-etap2-inwentarz-autohome.md`**.

Priorytet: pozycje obecne w **≥50% egzemplarzy próbki**. Rzadkie (1–2 wystąpienia, zwykle opcje
pakietowe typu `Nappa高级内饰套装`) zostaw niezmapowane — koszt bez zwrotu.

### Trzeci tryb mapy — nowa jakość, której dziś nie ma

Mapa umie `string` (klucz 1:1) i `array [a,b]` (rozbicie 前/后). Potrzebny trzeci tryb:
**jedna nazwa CN → wiele kluczy docelowych, wybieranych po obecności podnazwy w wartości.**

```php
'前排座椅功能'  => ['加热' => 'front_seat_heating_1',
                   '通风' => 'front_seat_ventilation_1',
                   '按摩' => 'front_seat_massage_1'],
'第二排座椅功能' => ['加热' => 'rear_seat_heating',
                    '通风' => 'rear_seat_ventilation',
                    '按摩' => 'rear_seat_massage'],
'驾驶辅助影像'  => ['360度全景影像' => 'panoramic_camera',
                   '车侧盲区影像'   => 'blind_spot_image'],
```

**Nie wymyślaj nowych nazw kluczy docelowych.** Użyj tych, które wysyła dongchedi, żeby oba
źródła trafiały w to samo pole — inaczej filtr będzie widział dwa rozłączne zbiory. Sprawdzone,
że są już w `data/translations-extra-prep.php` (z tłumaczeniem PL): `front_seat_massage_1`,
`rear_seat_massage`, `front_seat_ventilation_1`, `front_seat_heating_1`, `skylight_type`,
`seat_material`, `drive_mode_1`, `panoramic_camera`, `camera_count`, `speaker`, `sound_brand`,
`ar_hud`, `laser_radar`, `automatic_drive_level`, `center_screen_size`, `adaptive_cruise`,
`exter_mirror_elec_adjustment`. Gdy klucza brak — dopisz go do słownika razem z tłumaczeniem,
w tym samym commicie.

Pola złożone warte rozbicia (z inwentarza): `前排座椅功能`, `第二排座椅功能`, `外后视镜功能`,
`天窗类型`, `驾驶模式切换`, `手机互联/映射`, `钥匙类型`, `多媒体/充电接口`, `内后视镜功能`,
`电动座椅记忆功能`, `第二排座椅调节`, `手机APP远程功能`, `辅助驾驶芯片`, `辅助驾驶路段`.

### Trzy pola z listy Janka są nieosiągalne

Nie szukaj ich i nie improwizuj zamienników:

| pole | dlaczego |
|---|---|
| kolor wnętrza | nie ma go na stronie konfiguracji Autohome — to cecha egzemplarza, nie wersji. U nas taksonomia `interior-color` ma 181 z 3000 ofert (6%), che168 tego nie wysyła |
| tylne koła skrętne | brak takiego parametru w katalogu (sprawdzone na 2 egzemplarzach) |
| Apple CarPlay jako osobny filtr | pole `手机互联/映射` istnieje, ale wartości to HiCar / Carlink. Zmapuj uczciwie jako mirroring telefonu, bez podpierania się marką Apple |

## 6. Krok 3 — backfill 720 specid

`scripts/autohome-catalog-merge.php` już istnieje i ma właściwe zasady: **nigdy nie nadpisuje
istniejącego klucza**, pomija puste i `-`, zamienia ● → 标配 / ○ → 选配, stempluje
`_asiaauto_spec_catalog_*` (audyt + rollback). Tej logiki nie zmieniaj — dołóż wyłącznie
obsługę trybu złożonego z kroku 2.

Kolejność, bez skrótów:

1. **`mysqldump`** przed czymkolwiek, do `~/backups/primaauto/<data>/`. Dotykamy 1923 ofert.
2. **Dry-run na 20 ofertach** — raport: ile kluczy dołożono, ile pominięto jako istniejące,
   ile niezmapowanych. **Pokaż Jankowi zanim ruszysz resztę.**
3. Backfill **per specid**, nie per oferta — 720 pobrań, wynik z jednego specid dolewany do
   wszystkich ofert, które go mają. Throttle **2 s**, cache JSON na dysk.
4. Log przebiegu: liczba ofert dotkniętych, suma dołożonych kluczy, lista pobrań nieudanych.

**Pomiar zamykający — bez niego wdrożenie nie jest zamknięte.** Pokrycie sześciu pól kontrolnych
przed i po. Punkt odniesienia z 02.09:

| pole | przed |
|---|---:|
| `front_seat_massage_1` | 18% |
| `laser_radar` | 16% |
| `ar_hud` | 10% |
| `panoramic_camera` | 44% |
| `speaker` | 34% |
| `sound_brand` | 11% |

## 7. Krok 4 — trzecie ogniwo nocnej sekwencji

Dołóż katalog jako trzecie ogniwo **po** bliźniaku (04:35) i banku (04:45), ok. **04:55**,
działające na tym, co im zostało: oferty che168 ze `spec_id`, bez flag wyposażenia,
z limitem dobowym jak w pozostałych skryptach.

Kolejność jest celowa: bliźniak i bank są darmowe (czytają lokalną bazę), katalog kosztuje
request na zewnątrz. Puszczanie katalogu pierwszego marnowałoby pobrania na oferty, które
i tak zostałyby uzupełnione lokalnie.

Najpierw cache po specid — 720 wersji pokrywa 1923 oferty, więc nowa oferta zwykle trafia
w znaną wersję i **nie wymaga żadnego pobrania**. Świeży request tylko dla nieznanego specid.

**Crontab modyfikuj wyłącznie przez `~/bin/cron-install <plik>`.** Bezpośrednia edycja jest
zablokowana hookiem po incydencie 12.07, w którym nadpisanie tabeli skasowało 6 jobów.
Odczyt: `crontab -l`.

---

## 8. Strefy kruche — czego nie wolno

- Nie ruszaj `class-asiaauto-importer.php` w części identyfikacji ofert (`findByInnerId`),
  slugów, rezerwacji i obrazów. Dokładasz **obok**, addytywnie.
- Nie nadpisuj istniejących kluczy w `extra_prep` — zasada merge'a zostaje nienaruszona.
- **Nie przebudowuj banku specyfikacji.** `zbuduj-bank-specyfikacji.php` **niszczy bank**
  (933 → 574 warianty — buduje od zera zamiast scalać). Ten etap go nie dotyka.
- Nie zmieniaj nazw klas, CPT, meta keys, shortcodów, ról. Slugi `asiaauto-*` zostają.
- Backup przed każdym zapisem masowym, bez wyjątku.
- Theme aktywny to `primaauto2026`; `themes/asiaauto/` jest martwy. Tu i tak nie dotykasz motywu.

## 9. Gotchy, na które natrafisz

- **Ta sama nazwa CN w dwóch grupach.** `车身结构` występuje w `基本参数` (wartość `5门7座SUV`)
  i w `车身` (wartość `SUV`); `能源类型` podobnie. Mapa jest kluczowana nazwą — rozstrzygnij
  po grupie albo zmapuj tylko wystąpienie o pewnej semantyce.
- **ID parametrów katalogu ≠ ID auto-api** — 1 wspólny ID na ~270 i to z inną semantyką
  (53 = 备胎规格 w API, 级别 w katalogu). Most idzie po **nazwie CN**. Nie próbuj po ID.
- **Nazwy są częściowo zaciemnione** (`hs_kwNN_*`, numeracja losowa per żądanie). Deszyfrator
  w fetcherze działa. Jeśli kod wyjścia to **2** (zostały `□`) — pobranie jest niepełne,
  **odrzuć je, nie merguj**.
- **Liczby w inwentarzu potrafią przekroczyć wielkość próbki** (`能源类型 23/14`) — to duplikaty
  nazw między przestrzeniami `config` i `option`, nie błąd danych.

## 10. Definicja zrobionego

1. Fetcher zwraca **0 pustych wartości** na 3 losowych specid; `前排座椅功能` daje listę funkcji.
2. Mapa pokrywa wszystkie pozycje z inwentarza obecne w ≥50% próbki; pola złożone rozbite na flagi.
3. Backfill 720 specid wykonany, z logiem i **tabelą pokrycia przed → po** dla 6 pól kontrolnych.
4. Katalog wpięty jako trzecie ogniwo nocnej sekwencji (~04:55), przez `~/bin/cron-install`.
5. `extra_prep` nietknięty tam, gdzie już miał wartość — zweryfikowane na próbce 20 ofert.
6. Bump wersji pluginu, wpis w `docs/VERSIONS.md`, ADR w `docs/decyzje/` z tabelą pokrycia.

## 11. Czego ten etap świadomie NIE dowozi

Filtrów w wyszukiwarce, tabeli `wp7j_asiaauto_specs`, sekcji „Zaawansowane" w UI, deep-linków.

Warstwa liczbowa jest w danych **gotowa od dziś** i czeka na osobną decyzję Janka:
liczba miejsc 99%, rozstaw osi 99%, wymiary 95%, masa własna 95%, DMC 94%, moc elektryczna
łączna 88%, przyspieszenie 0–100 81%, zasięg CLTC 80%, pojemność baterii 79%, bagażnik 79%,
przebieg 100%, plus gotowe taksonomie (skrzynia, stan, napęd, paliwo, nadwozie) 99–100%.
