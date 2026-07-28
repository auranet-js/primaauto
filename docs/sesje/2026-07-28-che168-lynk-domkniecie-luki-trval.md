# 2026-07-28 — Lynk & Co, domknięcie luki che168, tr_val, rozwalony JSON

> Wersja pluginu po sesji: **0.34.8** (z 0.34.7)

## Co zrobione

### 1. Nazwy hubów w filtrze — rename bez ryzyka SEO

Filtr `/samochody/` pokazywał „Lynk & Co 03" zamiast „03". Rozwiązanie oparte na tym, że
**trzy warstwy są rozdzielone**:

| Warstwa | Źródło |
|---|---|
| filtr (dropdown Model) | `$term->name` |
| H1 + breadcrumb | `_serie_full_title`, fallback `$term->name` (`taxonomy-serie.php:44`) |
| title + description | `rank_math_title` / `rank_math_description` — **literalne** w termmeta |

Procedura (7 termów Lynk): `_serie_full_title` = dotychczasowa pełna nazwa (przez
`html_entity_decode` — nazwy trzymają w bazie encję `&amp;`), `name` = krótka forma.
**Slug nietknięty** → zero 301. Zweryfikowane po zmianie: H1, title, description, canonical
i breadcrumb identyczne. Wzorzec nie jest nowy — tak działa już 320 z 2709 termów serie
(iCAR „03" → „Chery iCAR 03", Denza „D9 DM-i" → „Denza D9 DM-i").

Efekt uboczny: naprawiona **podwojona marka** w tytułach (`Lynk & Co Lynk & Co 09 EM-P`,
jedyne 2 takie przypadki w bazie). Przyczyna: `HubTitleGenerator::ensureBrandPrefix()`
porównywał nazwę termu z marką, a nazwa miała encję `&amp;` → dopasowanie nie trafiało
i marka doklejała się drugi raz. Rename usunął źródło.

### 2. Mapowania Lynk & Co — 15 wpisów

Brand-mapping miał **tylko `900`**; huby 03/06/07 EM-P powstały fallbackiem `translateModel`
z dongchedi (bez guarda). Stąd marka była na stronie, a sync che168 ją odrzucał
(log: „niezmapowany model 'Lynk & Co|08 EM-P' — do kolejki domapowań”).

- `che168-model-map.php` 115 → 123, `brand-mapping-v6.1.php` 295 → 302.
- Modele: `03`, `06 EM-P`, `07 EM-P`, `08 EM-P`, `Z10`, `Z20` + aliasy CJK.
- **che168 podaje `900` i `10` PO CHIŃSKU** (`领克900`, `领克10`), resztę gamy po angielsku.
  `领克` nie ma w liście `cnPrefix` w `resolveChe168()` → sieroty. Skutek: hub `/lynk-co/900/`
  (2172 imp / 84 kliki w GSC 90 dni) żywił się wyłącznie dongchedi.
- Kontrola wobec backupu: **0 wpisów zmienionych, 0 usuniętych** → regresja dongchedi zero.
- `09 EM-P` / `10 EM-P` pominięte świadomie do czasu potwierdzenia formy klucza w kolejce.

Weryfikacja wyłącznie ścieżką `getOffer()` → `normalize()` → `getEuForCn()`.

### 3. Domknięcie luki — 92 oferty

`che168-domknij-luke.php --apply --pages=18`. **92 zaimportowane, 0 błędów.**
che168 publish **58 → 151**. Huby: `900` 9→16, `03` 2→24, `08 EM-P` 1→8,
`10 EM-P` 0→4, `Z20` 0→2; nowe termy `06-em-p`, `z10`.

⚠️ **Monitor przy domyślnych 6 stronach pokazywał 46 ofert — realnie było 92. Używać `--pages=18`.**

Jakość partii: 0 bez zdjęcia głównego, 0 bez `extra_prep`, 0 z rozwalonym JSON.
1 oferta (#391480) z `desc: failed` (Gemini connection reset, DeepL nieskonfigurowany)
— dotłumaczona ręcznie. 14 ofert bez opisu, bo **sprzedawca nie podał oryginału**.

Tytuły hubów zregenerowały się same po imporcie (hook `asiaauto_after_set_taxonomies`).

### 4. Czternaście ofert z nieparsowalnym `extra_prep`

Naprawione **własnym parserem ratunkowym pluginu**, nie z bliźniaka:
`AsiaAuto_Single::fixBrokenFlatJson()` odzyskuje pary klucz→wartość z rozwalonego JSON.
To wyjaśnia, dlaczego single renderował się poprawnie, a huby nie — `AsiaAuto_Spec`
robi `json_decode` wprost.

Odzyskano **5551 kluczy** (412 Denza N9 / 381 Leopard 7), odwrócono **12 439 escape'ów**;
zapis `wp_slash(wp_json_encode(…, JSON_UNESCAPED_UNICODE))`. 14/14 parsowalne, CJK zachowane.
Skrypt: `scripts/fix-broken-json-extra-prep.php`.

### 5. `tr_val()` — częściowe dopasowanie tylko dla kluczy CJK

Mapa wartości ma 16 kluczy bez CJK, w tym **jednoznakowe** `L`/`V`/`H`/`W`. Przy dopasowaniu
częściowym trafiały w każdą wartość zawierającą tę literę. Poprawka: częściowe dopasowanie
wyłącznie dla kluczy zawierających CJK; łacińskie tylko przy trafieniu pełnym.

**Zasięg — doprecyzowanie:** `tr_val()` jest wołane w **dwóch miejscach**
(`gearbox_description`, `car_body_struct`), czyli poprawka dotyczy skrzyni biegów i typu
nadwozia na spec-hubach. Wcześniejszy szacunek „353 błędne wartości (4,7%)" opisywał
teoretyczny zasięg funkcji na całym `extra_prep`, **nie** liczbę realnie widocznych błędów.

## Pomiar, który zamknął temat tłumaczeń CJK

Hipoteza „po poprawce 156 wartości pokaże surowy chiński" **okazała się nieprawdziwa**:

- front ofert: **3029 ofert, 675 849 wyrenderowanych pozycji — 0 znaków CJK**,
- spec-huby: **309 hubów — 0 CJK** w gearbox/body,
- kontrola HTTP na 12 losowych ofertach, 1065 wierszy — 0.

Powód: render idzie przez `AsiaAuto_Translator::translateExtraPrep()`, który pokazuje
**wyłącznie pozycje mające kategorię i etykietę**. Nieprzetłumaczone wartości siedzą
w polach typu `param_144`, `drive_mode_24` — nigdy nierenderowanych. Translator ma też
własne reguły wzorcowe (`马力`→KM, `X万`→¥, `X元`→¥).

**Wniosek metodyczny:** liczyć na wyjściu z renderu, nie na surowym `_asiaauto_extra_prep`.

## Otwarte

- **92 huby z zamrożonymi tytułami** — `HubTitleGenerator::regenerateForTerm()` pomija termy
  z `count = 0` (`reason: count=0`), więc hub, który stracił oferty, zostaje z „od X PLN,
  N sztuk" i treścią opisującą nieistniejący egzemplarz. Systemowe, do osobnego przejścia.
- **169 ofert ma chińskie znaki w nazwach plików zdjęć** (wcześniejszy, nietknięty wątek).
- **`09 EM-P`** — 13 sztuk w magazynie che168, ale **0 przechodzi filtry** Ruslana.

## Backupy

- `~/backups/primaauto/2026-07-28/terms-przed-rename-lynk.sql`
- `~/backups/primaauto/2026-07-28/extra-prep-broken-json-przed.json`
- `data/*.bak-2026-07-28-lynk`, `includes/class-asiaauto-spec.php.bak-2026-07-28-trval`

## Indexing API

4 URL-e zgłoszone przez `~/bin/index-submit` (huby `08 EM-P`, `09 EM-P`, `10 EM-P`, `Z20`)
— zużycie ad-hoc **4/100**, rezerwa PrimaAuto nietknięta. Oferty z importu zgłaszane
automatycznie przez hook pluginu.
