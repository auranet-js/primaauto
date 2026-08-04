# Fala „treść i linkowanie wewnętrzne" — porządek i kolejność (2026-08-04)

> Powód: sześć wątków (huby marek, `/marki/`, aktualności, słownik, linkowanie wewnętrzne, specyfikacja oferty) to w rzeczywistości **jedna warstwa** — graf treści, który prowadzi użytkownika i robota od parametru w ofercie aż do rankingu. Były rozrzucone po trzech dużych taskach i po jednym numerze widmo.
> Zasada porządkowania (decyzja Janka 2026-08-04): **małe taski** — łatwiejsze do zamknięcia i opisania.

## 1. Stan faktyczny warstwy treści (zweryfikowany na produkcji 2026-08-04)

| Element | Stan | Ruch (GSC 28 dni) |
|---|---|---|
| Huby marek (58) | ✅ `wiki_body` + FAQ + lead + `h1_suffix` | huby razem **8 158 klik. / 171 946 wyśw. z 402 URL-i (85% ruchu)** |
| Huby modeli (~300) | ✅ rework T-203 (title/H1/desc) | jw. |
| Słownik `/wiki/` | ✅ **74 hasła** (plan zakładał 40) | 40 klik. / 2 330 wyśw. / 68 URL-i |
| Aktualności `/aktualnosci/` | ✅ 38 wpisów — ⚠️ **stoi od 22.07** | 413 klik. / 3 578 wyśw. / 36 URL-i |
| Auto-linker | ✅ **wdrożony 22.07**, 4 punkty wpięcia, 6 linków `/wiki/` na ofercie | — |
| `/rankingi/`, `/porownania/`, `/poradniki/` | ⚠️ listingi HTTP 200, **0 wpisów** | 0 |
| `/marki/` | ❌ lista 58 nazw, jedno zdanie tekstu | **631 wyśw. / 2 klik. w 90 dni**, poz. 15,6–43,3 |
| Dane pojazdu na ofercie | ❌ brak Marki, Modelu, Wersji, Koloru; zero linkowania z parametrów | oferty: 282 klik. / 8 363 wyśw. z 1 228 URL-i |

**Sekcja „Dane podstawowe" na ofercie wygląda dziś tak** (Xiaomi SU7, sprawdzone w Chrome):
`Rok modelowy · Typ nadwozia · Długość · Szerokość · Wysokość · Rozstaw osi · Rozstaw kół przód · Rozstaw kół tył` → „Więcej".

Czyli sekcja identyfikująca pojazd otwiera się **wymiarami**, a nie tym, czym auto jest. Brakuje: **Marka, Model, Wersja, Kolor nadwozia, Kolor wnętrza, Napęd, Liczba miejsc**. Żaden parametr nie jest linkiem, choć każdy z nich ma po drugiej stronie gotową stronę docelową (hub marki, hub modelu, katalog z filtrem nadwozia/koloru/paliwa).

## 2. Co było nie tak z listą

- **T-162 i T-214 to ten sam wątek pod dwoma otwartymi numerami** — T-162 ma w opisie „wchłonięte jako filar C przez T-214", a mimo to wisi jako otwarty.
- **T-214 opisywał stan sprzed trzech tygodni** — mówił o 10 hasłach i 2 newsach (jest 74 i 38) i trzymał auto-linker jako niezrobiony, choć działa od 22.07.
- **Taski były za duże** — T-214 to 74–100 h w jednym wpisie; nie da się tego zamknąć ani sensownie zaraportować Ruslanowi.
- **Numery widmo:** T-215, T-216, T-218, T-222 cytowane w dokumentach, bez wpisu w kolejce i bez specu.

## 3. Porządkowanie

**Scalenia**
- T-162 → zamknąć jako wchłonięty przez T-214 (jeden wątek = jeden numer).
- T-214 zostaje **parasolem** (strategia, decyzje D1–D6, silnik) — ale realizacja idzie przez małe taski poniżej, nie przez „F4".

**Rozbicia**
- T-225 (10–14 h) → cztery taski po 2–4 h.
- F4 z T-214 → osobny task na szablon rankingu + osobne na treści.

## 4. Kolejność — pięć bloków

Zasada: **najpierw fundament linkowania (bo z niego korzysta wszystko dalej), potem pillar, potem treści.**

### Blok 1 — Fundament: dane pojazdu i linkowanie z parametrów

| Task | Zakres | h |
|---|---|---:|
| **T-226** | „Dane podstawowe" na ofercie: dodać **Marka, Model, Wersja, Kolor nadwozia, Kolor wnętrza, Napęd, Liczba miejsc** na początek sekcji, przed wymiarami | 3–4 |
| **T-227** | Parametry jako linki **do hubów i katalogu**: marka → hub marki, model → hub modelu, nadwozie/paliwo/kolor/napęd → katalog z filtrem; **`noindex` na kombinacjach filtrów** | 3–4 |

Dlaczego pierwsze: 1228 stron ofert to największy zbiór URL-i w serwisie i dziś prawie nie linkuje w głąb. Każdy dodany link działa dla całego zbioru naraz.

⚠️ **Korekta po audycie słownika 2026-08-04** (`docs/analizy/2026-08-04-slownik-luki-audyt.md`): mechanizm linkowania z parametrów technicznych **istnieje** (`AsiaAuto_Autolink`, wpięty w `tech_specs` i `equipment`), ale **jego limity są zaszyte na sztywno i już wysycone**: `tech_specs` 3 + `equipment` 3 = 6, i dokładnie 6 linków jest dziś na ofercie (`Rok modelowy`, `CLTC`, `LFP`, `Keyless`, `Filtr PM2.5`, `OTA`).

Nowe hasła **nie zwiększą liczby linków w ofertach** — zmienią tylko, które hasła wygrywają (dłuższy alias ma pierwszeństwo), co i tak poprawia trafność: dziś w tabeli specyfikacji linkuje się „Rok modelowy" zamiast parametrów konstrukcyjnych.

**Nowy punkt do decyzji — T-227b:** czy podnieść limity w `tech_specs`/`equipment`. Zmiana jednolinijkowa, ale dotyka renderu oferty (ZAWSZE PYTAJ) i grozi przelinkowaniem tabeli.

T-233a zostaje w Bloku 0, ale **uzasadnieniem jest własny ruch haseł** (74 hasła = 2330 wyśw./28 dni), AEO i pokrycie merytoryczne — nie „odblokowanie linków w ofertach". T-227 obejmuje wyłącznie linki do hubów i katalogu, których autolinker nie robi w ogóle.

### Blok 2 — Pillar: strona `/marki/` (rozbite T-225)

| Task | Zakres | h |
|---|---|---:|
| **T-225a** | Wstęp merytoryczny + mapa koncernów w `post_content` (bez dotykania kodu) + uzupełnienie 9 brakujących `description` | 3–4 |
| **T-225b** | Szablon: 3 sekcje (chinese / chinese_owned / global_jv), TOP wg `count` zamiast hardkodu, teasery z istniejących 49 opisów | 3–4 |
| **T-225c** | Logotypy marek + rejestr licencji (fraza „logo chińskich samochodów" poz. 7,4 przy zerze obrazków) | 3 |
| **T-225d** | FAQ + `ItemList`/`FAQPage` + sekcja „Najnowsze rankingi" + indeksacja i baseline | 2–3 |

⚠️ **D1 nadal otwarte:** czy marki `global_jv` (VW, Toyota, Mazda, Audi, Nissan, Honda, Ford, Mercedes) zostają w osobnej sekcji, czy znikają z gridu. Ich huby robią 864 klik. / 16 460 wyśw. w 90 dni.

### Blok 3 — Huby marek: domknięcie grafu

| Task | Zakres | h |
|---|---|---:|
| **T-228** | Hub marki ↔ pillar ↔ rankingi: link powrotny do `/marki/`, logo marki w hero (reużycie z T-225c), sekcja „rankingi, w których występuje ta marka" (ukryta przy zerze) | 3 |

### Blok 4 — Treści rankingowe (F4 z T-214)

| Task | Zakres | h |
|---|---|---:|
| **T-229** | Szablon rankingu: generator listy z bazy (dane z `_asiaauto_extra_prep`), tabela, `ItemList`, gate kanibalizacyjny GSC, obsługa pułapek danych (CLTC vs WLTC, hybrydy mieszające zasięg EV z łącznym) | 5–6 |
| **T-230** | Pierwsze 3 rankingi: **terenówki (~3 450/mc) → pickupy (~2 480) → vany i 7-osobowe (~2 140)** | 4–5 |
| **T-231** | Kolejne: kombi (480), sportowe (~520), SUV-y (~6 500 — największy, po pilocie) | 4–5 |

### Blok 5 — Higiena istniejących sekcji

| Task | Zakres | h |
|---|---|---:|
| **T-232** | Aktualności: diagnoza crona 06:45 (stoi od 22.07) i wznowienie | 1–2 |
| **T-234** | Słownik: sekcje „auta z tą technologią" pod hasłami (ostatni otwarty punkt z T-214) | 3–4 |

### Blok 0 — Słownik jako dźwignia linkowania (awansowany po audycie)

| Task | Zakres | h |
|---|---|---:|
| **T-233a** | **~15 haseł o pokryciu >1000 aut:** zawieszenie (wielowahaczowe 1449, McPherson 958, dwuwahaczowe 886, pięciowahaczowe 345, regulacja twardości/wysokości 579), hamulce (tarcze wentylowane 2001, EPB 2029), struktura nadwozia (samonośna 1933 / **ramowa 103**), mapy (Amap 857), radar mmWave 1061, skrzynia stałoprzełożeniowa w EV 1079, wtrysk bezpośredni 1101, turbo 806 | 6–8 |
| **T-233b** | **~20 haseł średniego pokrycia:** producenci baterii (CALB 125, SVOLT 49, EVE 26, Sunwoda 24, Gotion 22), systemy kokpitu (DiLink 221, Leapmotor OS, Flyme Auto, Galaxy OS, Lion), chipy (Snapdragon 8295/8155, NVIDIA Orin X/N, co znaczy TOPS), systemy ADAS (DiPilot 190, Leapmotor Pilot, NOP, Li AD Max) | 6–8 |
| **T-233c** | **~15 haseł uzupełniających:** audio (Sony 69, BOSE 33, Infinity 29, Yamaha, Pioneer, KEF, Alpine), technologie baterii (Huawei Juwhale 40, Shield 29, Xiaoyao 16, Amber 9), ogniwa mieszane LFP+NMC 81, układ asynchroniczny przód + PMSM tył 345, materiały foteli | 5–6 |

**Uwaga wykonawcza:** wartości w danych mają warianty zapisu tego samego pojęcia (`双叉臂式独立悬架` 682 vs `双叉臂式独立悬挂` 204 — różnica w ostatnim znaku). Mapowanie w autolinkerze musi je normalizować, inaczej hasło złapie połowę aut. `battery_brand` ma 72 wartości przy ~12 realnych producentach (reszta to spółki JV i zakłady) — jedno hasło wchłania warianty.

**Razem: 15 tasków, 58–76 h.** Każdy zamykalny osobno i opisywalny jednym zdaniem w raporcie dla Ruslana.

## 5. Kolejność wykonania (rekomendacja)

1. **T-233a** — najtańsza dźwignia: 15 haseł zamienia się w linki na ~2000 ofert bez dotykania kodu
2. **T-226 + T-227** — dane pojazdu i linki do hubów/katalogu
3. **T-225a + T-225b** — pillar zaczyna nieść treść
4. **T-232** — tanie, a Magazyn stoi już 13 dni
5. **T-229 + T-230** — pierwsze rankingi wchodzą do pustego listingu
6. **T-233b + T-233c** — reszta słownika
7. **T-225c + T-225d + T-228** — domknięcie wizualne i grafu
8. **T-231 + T-234** — reszta treści

Alternatywa, jeśli priorytetem jest szybki efekt na `/marki/`: zacząć od bloku 2, ale wtedy sekcja „Najnowsze rankingi" (T-225d) czeka na T-230 i tak.
