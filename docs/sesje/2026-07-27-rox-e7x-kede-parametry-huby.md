# 2026-07-27 — ROX 01 / Audi E7X / Toyota KEDE Shanchuan: parametry z dongchedi + 3 huby live

## Cel wątku (pytanie wyjściowe Janka)

Trzy szkice zaimportowane ręcznie z che168 (posty #390560, #390551, #390186) miały zostać
hubami. Pytanie: **czy te same pojazdy są dostępne w dongchedi, gdzie mamy więcej `extra_prep`** —
bo che168 oddaje 7 grup technicznych i zero grup wyposażenia (blokada API, T-186).

## Odpowiedź (sonda read-only na auto-api, bez importu)

| Pojazd | che168 | dongchedi | wynik |
|---|---|---|---|
| ROX 01 (极石01) | 112 pól | **369** pól, `24221140` (ta sama wersja `尊享6座标准续航版`) | scalone |
| Toyota KEDE Shanchuan M7 | 62 pola | **281** pól, `23589815` (**ten sam egzemplarz** — 529 000 CNY, 西安) | scalone |
| Audi E7X | 85 pól | **model nie istnieje** (57 modeli Audi, najnowszy `E5 Sportback`) | brak źródła |

## Metoda scalenia — `scripts/merge-spec-from-dongchedi-twin.php`

Cross-source, z API (nie z naszej bazy, inaczej niż `merge-spec-from-twin.php`, które dodatkowo
pomija wpisy ręczne — a te trzy są ręcznymi importami).

Gate'y: (1) tytuł dawcy musi zawierać sygnaturę wersji; (2) raport konfliktów na kluczach
wspólnych; (3) **nasze wartości mają pierwszeństwo, dolewamy tylko brakujące klucze**.

Trzecia zasada okazała się kluczowa — dawca nie jest jednolicie lepszy:

| pole | nasze (che168) | dawca (dongchedi) | ocena |
|---|---|---|---|
| `curb_weight` (KEDE) | 2650 | **2150** | dawca ewidentnie zaniża (7-osobowy van) |
| `engine_max_horsepower` (ROX) | 152 | 156 | rozbieżność źródeł |
| `sub_brand_name` (ROX) | ROX极石 | 北京汽车制造厂 | dawca podaje zakład produkcyjny |
| `oil_tank_volume`, `period`, `fuel_label` | `70`, `三年…`, `92号` | `70.0`, `3年…`, `92#` | kosmetyka formatu |

Wynik: ROX **112 → 386** pól (wiersze specyfikacji na karcie oferty 47 → **108**),
KEDE **62 → 288** (47 → **59**). Znaczniki: `_asiaauto_spec_merged_from`, `_asiaauto_spec_merged_at`.

## Korekty nazewnictwa (obie zgłoszone przez Janka, obie potwierdzone u źródła)

- **KEDE**, nie Kedi — 克蒂汽车 używa własnej nazwy angielskiej KEDE (`kede-auto.com`,
  dawniej KRYSTAL, przejęte 2016). API romanizują błędnie i różnie: dongchedi `Kedi`, che168 `Kurti`.
- **Nie Alphard, a Alpha** — `阿尔法` = Alpha (seria KEDE), Alphard po chińsku to `埃尔法`.
  Baza techniczna też nie jest Alphardem: napęd na tył, rozstaw 3210 mm, drzwi przesuwne
  = Hiace/Granvia (Alphard: przód napędowy, 3000 mm). Logo Toyoty jest na atrapie i klapie
  (weryfikacja na zdjęciach oferty), więc marka `Toyota` zostaje.
- **ROX 01 (PolarStone 01)** — nazwa międzynarodowa + chińska w nawiasie (konwencja
  `Leopard 5 (Denza B5)`). Nazwy z API (`Jishi 01`, `Extreme Stone 01`) są martwe w wyszukiwarce.

## Nazwy hubów rozstrzygnięte DFS (Google Ads Keyword Planner, PL/pl)

`rox 01` **170/mc** (SERP komercyjny — dwaj polscy sprzedawcy na pozycjach 1-2) vs `jishi 01` 10,
`extreme stone 01` i `polestones 01` brak danych. `rox adamas` 70/mc = lifting, osobny hub.
`audi e7x` **390/mc** (szczyt 1600 w 04.2026), SERP top8 **bez ani jednej oferty** = luka.
Wszystkie warianty `kedi/kede *` = brak danych → hub KEDE istnieje pod katalog i sprzedaż
na zamówienie (decyzja Ruslana: luksus na zamówienie), nie pod ruch z wyszukiwarki.

## Stan końcowy

| Hub | Oferta | Cena | wiki | FAQ |
|---|---|---|---|---|
| `/samochody/rox/01/` | `rox-01-2025-390560` | 260 000 PLN | 5122 zn. | 6 Q |
| `/samochody/audi/e7x/` | `audi-e7x-2026-390551` | 303 000 PLN | 3979 zn. | 5 Q |
| `/samochody/toyota/kede-shanchuan/` | `toyota-kede-shanchuan-2026-390186` | 513 000 PLN | 4687 zn. | 5 Q |

Wszystkie trzy: `_asiaauto_pl_availability=import_only`, `h1_suffix` = `cena w Polsce i import z Chin`,
flaga `_asiaauto_seo_rework=v1-2026-07-27`, tabela spec live z `extra_prep`, FAQPage parsuje.
Indexing: oferty hookiem publikacji, 3 huby przez `index-submit --project primaauto --yes`.

**Kolejność była zła i to warto zapamiętać:** treść hubów pisałem przed scaleniem parametrów,
więc akapity o wyposażeniu trzeba było potem przepisać (`scripts/patch-wiki-wyposazenie-2026-07-27.php`).
Liczby (wymiary, moce, zasięgi, ceny) się nie zmieniły — bliźniak ich nie koryguje — ale
wyposażenie owszem: ROX z 71 pozycjami standardowymi, KEDE z 34.

## Otwarte

1. Puste termy po starym nazewnictwie ROX: `extreme-stone-01` (#7198) i `jishi-01` (#5083,
   ma `rank_math_title` i snapshot specyfikacji po hubie z czasów, gdy stała tam 1 oferta)
   → decyzja o 301 na `/samochody/rox/01/`.
2. Pozostałe 7 modeli KEDE w dongchedi (Shengmufeng, Shanhe, Xueduibai, Diya, Bach, WALD, 翔翼)
   — domapować hurtem, jeśli Ruslan je sprzedaje.
3. Audi E7X zostaje na 85 polach — jedyne źródło to che168 z uciętym wyposażeniem;
   auto-api nie odpowiedziało na zapytanie z 22.07.
4. Druga oferta ROX z dongchedi (`24489347`, wersja long range, 275 000 CNY) — do rozważenia.
