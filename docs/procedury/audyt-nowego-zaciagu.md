# Procedura: audyt nowego zaciągu ofert

> Utworzona 2026-07-29 na podstawie audytu 11 ofert dodanych ręcznie przez Ruslana 28.07.
> Dotyczy sytuacji, w której do bazy wchodzą **nowe marki i modele**, a nie kolejne egzemplarze
> modeli już obecnych. Baza modeli jest w miarę kompletna, więc to będzie coraz częstszy przypadek.

**Nie ma z tego narzędzia i świadomie nie ma.** Decyzja Janka 2026-07-29: do każdej oferty trzeba
podejść indywidualnie — rozstrzygnięcia (tożsamość modelu, nazwa eksportowa, routing sub-marki)
wymagają danych z zewnątrz i osądu, a nie reguły. Automatyzowalne są tylko pomiary, i te mają
gotowe skrypty wskazane niżej.

---

## Dlaczego to w ogóle jest potrzebne

**Ręczny import che168 nie ma guarda mapowania — sync ma.** `Admin_Che168_Import::ajaxImport()`
liczy flagę `mapped` i pokazuje „❌ sierota", ale **nie blokuje**; bramkami są tylko `importEnabled`,
duplikat i `isEmptyShell`. Skutkiem jest to, że oferta nowej marki wchodzi jako sierota, a importer
tworzy taksonomię fallbackiem:

- marka z nazwą CJK i slugiem URL-encoded (`大通` → `%e5%a4%a7%e9%80%9a`),
- serie z `parent=0` albo z prefiksem marki w nazwie („Foton Mars" → `/foton/foton-mars/`),
- tytuł oferty z surowych nazw che168 („MAXUS", „M-Hero 917" bez marki).

Efekt widoczny na zewnątrz: hub pod naturalnym URL-em zwraca **soft-404** (HTTP 200 + „Nie znaleziono"),
a auto jest osiągalne wyłącznie pod adresem z zakodowanym CJK.

---

## Kolejność — i dlaczego właśnie taka

Kolejność nie jest dowolna: każdy krok jest wejściem dla następnego.

1. **Taksonomia i tożsamość** — dopóki auto siedzi pod złym termem, wszystko dalej opisuje zły obiekt.
2. **Mapowania** — dopóki marki nie ma w mapach, każdy kolejny import odtworzy te same śmieci.
3. **Wyposażenie** — `extra_prep` jest źródłem tabeli technicznej i treści, więc musi być przed treścią.
4. **Treść hubów** — dopiero na poprawnych nazwach i pełnych danych.
5. **Indexing API** — **na samym końcu**. Zgłoszenie przed ustaleniem docelowych URL-i i treści
   pali budżet dwa razy na tych samych adresach (pula 200/dobę jest wspólna dla wszystkich projektów).

---

## Krok 0 — co realnie weszło

Odsiej ręczne od syncowych. Sync ma guard, więc problemy prawie zawsze siedzą w ręcznych.

```php
// meta _asiaauto_manual_import = 1 → import z panelu
// _asiaauto_source = che168 | dongchedi
```

Dla każdej oferty zbierz: `post_status`, termy `make`/`serie` (+ `parent` serii), liczbę pól
`_asiaauto_extra_prep`, `_asiaauto_spec_id`, długość opisu, miniaturę, cenę.

**Uwaga:** galeria siedzi pod kluczem `gallery` (tablica), nie `_asiaauto_gallery` — łatwo
fałszywie zaraportować „0 zdjęć".

---

## Krok 1 — taksonomia i tożsamość

### 1a. Pomiar rozjazdów (cała baza, nie tylko nowe)

Niezmiennik: dla każdego termu `serie` z `count>0`, `serie.parent` musi równać się termowi `make`
przypisanych postów. **Stan zdrowy = 0 rozjazdów.** 29.07 było 4 na 320 i wszystkie pochodziły
z jednego dnia ręcznych importów — to dobry test, czy ścieżka automatyczna jest czysta.

Sprawdź też: marki ze slugiem URL-encoded / nazwą CJK oraz serie z `parent=0` **i `count>0`**.
Serie z `parent=0` i `count=0` to zaciągnięty katalog modeli (29.07: 2279 sztuk) — **nie ruszać**.

### 1b. Tożsamość modelu — arbitrem jest katalog Autohome, nie API

Pole `model` z auto-api **kłamie**. Udokumentowane przypadki: Voyah Passion wracał jako „Zeekr"
(27.07), Sienna jako `华东牌商务车`. Zawsze weryfikuj po `_asiaauto_spec_id`:

```
https://car.autohome.com.cn/config/spec/{spec_id}.html   → tytuł strony = prawdziwa tożsamość
```

Tytuł ma format `【{seria} {rok}款 {wersja}参数配置表】价格单_{marka}_汽车之家`, czyli daje jednocześnie
**serię, wersję i markę**. To rozstrzyga, czy tworzyć nowy hub, czy oferta należy do istniejącego.

**Siedmiocyfrowy `spec_id` = katalog pojazdów użytkowych.** Rozpoznanie ważne dla kroku 3.

### 1c. Nazwa — nazwa eksportowa bije chińską, DFS rozstrzyga

Chińska nazwa modelu bywa zupełnie inna niż eksportowa, a różnica w wolumenie jest ogromna:

| chińska | eksportowa | DFS PL |
|---|---|---|
| 火星9 (Mars 9) | **Tunland V9** | `foton tunland v9` **720/mc** wobec `foton mars` 10/mc |
| 追光 (Zhuiguang) | **Passion** | rename 27.07, hub Zhuiguang miał 0 impresji |
| 昊铂 / Aion Hyper | **Hyptec** | rename 28.07 |

Sprawdź w DFS **oba warianty** zanim nazwiesz term. Pamiętaj o lagu DFS przy świeżych modelach:
zero w DFS ≠ brak popytu (`lynk & co 900` = null w DFS, 2172 imp w GSC). **GSC jest arbitrem, DFS wskazówką.**

Konwencja nazw: nazwa termu **krótka, bez marki** (`G90`, `Tunland V9`), marka w `_serie_full_title`
(`Maxus G90`). Rename nazwy termu nie rusza SEO — H1 bierze `_serie_full_title`, a title/description
są w termmeta literalnie. Slug zmieniaj świadomie, bo to już 301.

### 1d. Sub-marki — reguła routingu z T-190

Sub-marka z prawdziwym rodzicem → **fold pod markę-rodzica, nazwa sub-marki zostaje w modelu**
(wzorzec `byd/leopard-5`). Wyjątek: gdy istniejący 301 prowadzi do złego producenta.

Przykład 29.07: Mengshi (10/mc) → Dongfeng, model „M-Hero 917" (`m-hero` 1600/mc).
Nazwy sub-marki **nie wstawiaj w nawias** w nazwie termu — trafiłaby do etykiety filtra i H1.
Stara nazwa idzie jako **alias w treści wiki** (wzorzec z „Aion Hyper" przy Hyptec).

### 1e. Po zmianie taksonomii — trzy rzeczy, o których łatwo zapomnieć

1. **`_asiaauto_primary_make_slug`** (termmeta) ma **priorytet nad `parent`** w `ensureBrandPrefix()`.
   Zostaje stara po przeniesieniu → tytuł wychodzi jako `%e5%a4%a7%e9%80%9a Maxus V70`.
2. **Post meta `make` / `serie`** (slugi) nie aktualizują się same wraz z termami.
3. **301 zakładaj PRZED skasowaniem starej marki**, nie po — inaczej jest okno 404 na rankującym URL-u.
   Marka: `V61_MAKE_REDIRECTS` (zachowuje człon serii). Slug serii: `V62_SERIE_REDIRECTS`.
   Gdy marka jest w obu mapach — `V63_MAKE_SERIE_REDIRECTS` na priorytecie −1.

---

## Krok 2 — mapowania

**Alias w `che168-model-map.php` jest MARTWY bez pary `mark_eu|serie_eu` w `brand-mapping-v6.1.php`** —
`sigToKey()` tłumaczy entry z powrotem na literalny klucz brand-mappingu, a guard czyta tylko
brand-mapping. Potrzebne są **dwa** wpisy.

Klucz w `che168-model-map.php` musi mieć **formę, w jakiej che168 realnie wysyła** `mark|model` —
zmierz ją, nie zgaduj:

```
getOffer() → Che168_Adapter::normalize() → getEuForCn()
```

To jedyna poprawna ścieżka testu. `getEuForCn()` na surowych danych zawsze zwróci null i będzie
wyglądać na porażkę mapowania. Zmierzone formy z 29.07: `大通|G90` (CJK), `Mengshi|Mengshi 917`,
`Foton|Foton Mars`, `华东汽车|华东牌商务车` — czyli raz CJK, raz łacinka, bez reguły.

**Po dopisaniu zawsze porównaj z backupem:** `zmienionych=0, usuniętych=0`. Dopisywanie jest
bezpieczne, nadpisywanie nie.

**Granica, którą trzeba znać:** che168 potrafi wysyłać **jeden klucz dla dwóch modeli**.
`Foton|Foton Mars` to zarówno Tunland V9, jak i V7 — rozróżnia je wyłącznie `spec_id`. Mapa celuje
w wariant o wyższym wolumenie, drugi wymaga ręcznej korekty po imporcie.

---

## Krok 3 — wyposażenie

Ścieżka: `scripts/autohome-catalog-fetch.js <spec_id> <out.json>` → `scripts/autohome-catalog-merge.php <post_id> <json> <spec_id> [apply]`.

Fetch zdejmuje antyscraping (klasy `hs_kwNN_*` losowe per żądanie) wykonując inline-JS ze stubem DOM —
bez przeglądarki. Działa też dla pojazdów użytkowych. Merge **dolewa wyłącznie brakujące klucze**,
nigdy nie nadpisuje naszych.

**Most idzie po NAZWIE CN, nie po id** — przestrzenie ID katalogu i auto-api są rozłączne.

### Gdy nazw brakuje w `autohome-catalog-map.php`

Mapa z 27.07 pokrywała tylko **wyposażenie**, bo technika miała iść z auto-api. Dla pojazdów
użytkowych auto-api oddaje 1–12 pól, więc technika też musi iść z katalogu.

Wyprowadź mapowania **automatycznie, złączeniem po id auto-api** — nie po nazwie:

```
wp eval-file scripts/wyprowadz-nazwy-katalogu.php "<inner_id,inner_id,...>"
```

Odpowiedź auto-api niesie i `id`, i nazwę CN; `che168-param-map.php` mapuje `id → nasz klucz`
(zweryfikowane ręcznie). Mapowanie „na oko" po nazwie dało 22.07 dwa błędy semantyczne
(id 88 = moc układu ≠ silnika, id 84/105 = ładowanie szybkie ≠ wolne).

Skrypt odsiewa dwa rodzaje kolizji i **obu nie dopisuje**:
- **wprzód** — jedna nazwa CN → kilka kluczy (`最大功率(kW)` występuje w grupie silnika i układu,
  a mapa jest bez grupy);
- **wstecz** — kilka nazw CN → jeden klucz (`进气形式` i `配气机构` → `air_supply`; to błąd
  w `che168-param-map.php`, osobny temat).

Kontrola poprawności wyprowadzenia: po rozszerzeniu mapy licznik **„pominięte (już było)"** na
bogatych ofertach musi skoczyć (29.07: 17 → 54–73). To dowód, że nowe nazwy trafiają w klucze,
które już mamy, o tej samej semantyce — a nie tworzą duplikatów.

Zysk zmierzony 29.07 (mapa 125 → 218 nazw): oferty użytkowe 1–12 → 43–93 pól, osobowe 81–103 → 120–181.

Brak `spec_id` = niepełna odpowiedź API („pusta wydmuszka") → oferta do ponownego zaciągu.

### Wersja egzemplarza — z tego samego katalogu

Te same oferty, które mają chude wyposażenie, nie mają też **wersji** (`_asiaauto_complectation`),
bo che168 jej nie podaje. Objaw: tytuł kończy się na roczniku („Maxus V70 2026") zamiast nieść
wersję. Źródłem jest **tytuł strony katalogu Autohome**:

```
【{seria} {rok}款 {WERSJA}参数配置表】价格单_{marka}_汽车之家
```

Tłumacz ręcznie — `translateComplectation()` nie zna słownictwa użytkowego:
`长箱` = długa skrzynia, `短轴低顶` = krótki rozstaw / niski dach, `启航版` = Launch,
`标轴旗舰型` = standardowy rozstaw / Flagship.

Po uzupełnieniu przelicz tytuły. **Trzymaj meta title w 61–73 znakach** — tyle ma zastana baza po
T-203 v3/v4; rozwlekła wersja wypycha tytuł do 86–90 i Google go przycina. Wersja **nie wchodzi
do sluga** (`sanitize_title(marka serie rok)`), więc jej zmiana nie generuje 301.

---

## Krok 4 — treść hubów

Pipeline: `tmp/deploy-hub.php <config.json>` (wiki + FAQ + lead + `pl_availability` + `h1_suffix`).

- **Nie zamrażaj tabeli spec w `wiki_body`.** Od 13.06 renderuje ją `AsiaAuto_Spec` z `extra_prep`;
  wtedy usunięto 122 zamrożone tabele. Sprawdź `aa-spec` w HTML huba.
- `{{LISTINGS_BAR}}` musi być w wiki, FAQ minimum 3 pytania.
- **Cudzysłowy wyłącznie ASCII** — deploy odrzuca typograficzne (rozbijają JSON FAQ).
- **Pełne polskie diakrytyki.** Deploy sprawdza tylko cudzysłowy, więc treść bez ogonków przejdzie
  walidację i wyląduje na produkcji.
- Boilerplate spójny z resztą: siedziba **Rzeszów**, cena końcowa all-inclusive, 10–14 tygodni.
- Po deployu: `AsiaAuto_HubTitleGenerator::regenerateForTerm()` + `regenerateForMakeTerm()`
  oraz `AsiaAuto_Spec::saveSnapshot()`.
- Przy przeniesieniu huba pod inną markę przejrzyj **starą treść**: linki filtrów `?marka=<stara>`
  przestają działać, a liczby sztuk i widełki cen są sprzed zmiany.

---

## Krok 5 — Indexing API

- Budżet ad-hoc **100/dobę**, pula GCP 200 wspólna dla wszystkich projektów. Zawsze `--status` przed.
- Wyłącznie przez `~/bin/index-submit` (hook blokuje surowe wywołania).
- **Zachowaj listę URL per batch** (`tmp/indexing/<temat>-SUBMITTED-YYYY-MM-DD.txt`) — log wrappera
  zapisuje tylko liczby, więc bez listy nie odpowiesz później „czy ten URL poszedł".
- Zgłoś huby modeli **i huby marek** (ich tytuły i liczniki też się zmieniły) oraz oferty ze
  zmienionym slugiem.
- Pomiar: GSC `lastCrawlTime` po ~14 dniach, nie „czy w indeksie".

---

## Weryfikacja końcowa

| co | oczekiwane |
|---|---|
| rozjazdy `serie.parent` vs `make` | **0** |
| huby: HTTP + render | 200, brak „Nie znaleziono", `aa-spec`, `FAQPage` |
| H1 | `{Marka} {Model} — cena w Polsce i import z Chin` |
| title | `{Marka} {Model} — od X PLN, N sztuk \| Import z Chin \| Prima-Auto` |
| tytuł oferty | niesie wersję, nie kończy się na roczniku; meta title 61–73 znaków |
| stare URL-e | 301, nie 404 |
| mapowania | `getOffer → normalize → getEuForCn` zwraca EU dla wszystkich |
| mapy | wobec backupu: zmienionych 0, usuniętych 0 |

## Backupy — zawsze przed

```
~/backups/primaauto/<data>/terms-przed-<temat>.sql        # wp db export terms,term_taxonomy,termmeta,term_relationships
~/backups/primaauto/<data>/extra-prep-przed-<temat>.json   # extra_prep dotkniętych ofert
<plik>.bak-<data>-<temat>                                  # mapy i klasy pluginu
```
