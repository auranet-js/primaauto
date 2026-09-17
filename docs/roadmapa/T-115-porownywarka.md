# T-115 — Porównywarka aut

> Status: **wdrożone 16.09 w v0.42.0 — `/porownywarka/` działa, noindex, niepodlinkowana** · Rozmiar: M/L
> Gate z T-116 (tabela specs) **zdjęty** — tabela `wp7j_asiaauto_specs` działa od 02.09.
> Poprzednia wersja tego pliku (porównanie ofert, `/porownaj/`, limit 4, checkbox na karcie) — w historii gita.

## Decyzje (16.09.2026)

| Temat | Decyzja |
|---|---|
| Co porównujemy | **Wersje** (`_asiaauto_spec_id` = specid katalogu Autohome), nie egzemplarze. Porównanie nie wygasa razem z ofertą. |
| Wzór | **cpubenchmark.net** (`singleCompare.php` + `compare/AvsB`). Układ versus.com odrzucony. |
| Adres | **`/porownywarka/`** (start) i `/porownywarka/{wersja-a}-vs-{wersja-b}/`. Nie `/porownanie/` — obok jest kategoria bloga `/porownania/` (T-214), jedna litera różnicy. |
| Limit | **3 wersje**. |
| Indeks | **Wszystko `noindex`** na start. Log porównań zbieramy od pierwszego dnia; o indeksie decyzja po danych. Link nigdzie nie wisi. |
| Strony pojedynczych wersji | **Nie ma i nie będzie.** Slug wersji służy wyłącznie do złożenia adresu porównania `a-vs-b`. Osobna strona wersji = kopia karty oferty i kanibalizacja huba modelu (który ma tabelę „Wersje”). |
| Ochrona przed kanibalizacją | `noindex` meta **i** nagłówkiem `X-Robots-Tag` na całym `/porownywarka/`; **bez** `Disallow` w robots.txt (Google musi zobaczyć noindex); poza sitemapą i Indexing API; brak linków. Porównanie wersji jednego modelu celuje w to samo zapytanie co hub. Rezerwa: adresy na numerach (`73246vs75103`) — rozważone 16.09, na razie slugi. |
| Nagłówki | **Od słowa „Porównanie”** (17.09): title i H1 `Porównanie A vs B`, H2 `Porównanie parametrów` / `Porównanie na wykresach`, start `Porównanie samochodów`. Odróżnia intencję od huba modelu (`Zeekr 9X — cena w Polsce…`); główną ochroną przed kanibalizacją pozostaje noindex. |
| Logowanie | **Niepotrzebne.** Wybrane wersje w localStorage. |
| Wejście | **Pole wyszukiwania z podpowiedziami** (model → lista wersji z liczbą ofert i ceną „od”). |
| Przycisk na karcie oferty | **Etap 2**, do przemyślenia — ~23% ofert nie ma `specid`. |
| Cena | **Tylko z ofert** (najtańsza oferta tej wersji). Wersja bez ofert = „brak aut”, bez szacunku z katalogu. |
| Ocena punktowa / werdykt | **Brak.** Zamiast tego „% różnicy do najlepszej” przy mocy, 0–100 i zasięgu (jak „% diff. to max in group”). |
| Desktop | Tabela **4 kolumny**: parametry + 3 miejsca na auta (puste = „+ Dodaj auto”). Kontener 1200 px, styl tabel z huba („Wersje”: granatowy nagłówek). |
| Telefon | **Wariant A** — ta sama tabela przewijana w bok, kolumna parametrów przyklejona. (B „2 auta + przełącznik” i C „karty” odrzucone.) |
| Wiersze | **Sekcje karty oferty 1:1** — `buildTechSpecSections()` (6 sekcji) + logika `renderEquipment()` (9 sekcji), te same etykiety i pomijane klucze. Bez Marki/Modelu/kolorów (to cechy egzemplarza). |
| Słownik | Nazwy parametrów linkowane do `/wiki/` przez `AsiaAuto_Autolink::map()`. |
| Kolory | Najlepsza wartość w podsumowaniu zielona, strata % czerwona; wiersze z różnicą na jasnożółtym; ✓ zielony / „—” szary; puste miejsce zakreskowane. |

## Decyzje etapu 2 — przycisk „Porównaj” i uruchomienie (17.09.2026, quiz)

| Temat | Decyzja |
|---|---|
| Co dodaje przycisk | **Wersję** (specid) do **jednego schowka porównań** `aa_porownanie` w całym serwisie. **Ulubione (T-114) = osobny schowek**: egzemplarze, na koncie, bez limitu, ikona serca. Na karcie przewidujemy miejsce na **wagę i serce** obok siebie. |
| Miejsce | Przy cenie/wersji (karta produktu) i na karcie w listingu. **Do makiet:** waga + serce także w dolnym pasku mobilnym (`aa-mcta`) jako wariant; **wejście do porównań i ulubionych w górnym pasku** (telefon: przed hamburgerem, obok pigułki telefon/WhatsApp; serce jako zarezerwowane miejsce do T-114). |
| Po dodaniu | **Licznik przy wadze w nagłówku + przyklejony pasek schowka na dole** („Porównanie 2/3 — [Porównaj →] [wyczyść]”, zamykalny). Na karcie produktu na telefonie pasek nad `aa-mcta`. |
| Pełny schowek (3) | **Okienko „Zamień jedno”** — trzy przyciski z nazwami wersji + „Anuluj”. |
| Dwie oferty tej samej wersji | **Obie „W porównaniu ✓”**, podpowiedź „Ta wersja jest już w porównaniu”, klik = usuń. |
| Oferta bez specid (681 z 3 065, 22%) | **Przycisk prowadzi do porównywarki z modelem** — `/porownywarka/?model=<slug>` otwiera wyszukiwanie z listą wersji tego modelu (parametr do dołożenia, dziś nie istnieje). |
| Oferta ze specid bez wiersza wersji (18, cron 05:15) | **Odpuszczone na razie** — bez hooka w imporcie; do rana zachowuje się jak oferta bez specid (link do modelu). |
| Inna karta przeglądarki | Strona porównania **sama przechodzi na aktualny schowek, gdy klient wraca do karty** (`storage` + `visibilitychange`). **Obcy link** (schowek ≠ adres i adres nie zawiera się w schowku): pokazuje auta z linku, **nie kasuje schowka**, podpowiedź „Masz w porównaniu X, Y — pokaż →”. Adres ⊂ schowek → przejście na pełny schowek. **Błąd do naprawy:** dziś `asiaauto-compare.js` w trybie porównania nadpisuje schowek autami z adresu (`zapisz(lista)`). |
| Niezalogowani | Tylko localStorage (bez zmian, decyzja 16.09). |
| Zalogowani — synchronizacja | **Później, razem z T-114** (wspólna infrastruktura zapisu na koncie). |
| Pomiar | Historia porównań: nowe źródła **`oferta`** i **`listing`**. **Bez zdarzeń GA4.** |

## Makiety

- Desktop: `docs/makiety/gen-porownywarka-cpu.py` → `auratest.pl/fe4f58fec53ctmp/primaauto-porownywarka-makieta-cpu-2026-09-16.html`
- Telefon (A/B/C): `docs/makiety/gen-porownywarka-mobile.py` → `…-makieta-mobile-2026-09-16.html`
- Odrzucona (versus): `docs/makiety/gen-porownywarka.py`

Pięć porównań testowych: Zeekr 9X Ultra/Hyper/Max · Denza N8L vs NIO ES8 · Denza Z9 GT vs BYD Han L · BMW i5 vs Xiaomi SU7 · Leapmotor D19 vs Li Auto L8.

## Układ strony porównania

1. Okruszki, H1 „A vs B”, **wstęp składany z danych** (moc %, najszybsze 0–100, najdłuższy zasięg, najtańszy egzemplarz, źródło danych).
2. „Tylko różnice” + licznik („29 różnic w 285 pozycjach”).
3. Tabela: **Podsumowanie** (cena jako przycisk „od X zł” + liczba ofert tej wersji → hub; moc, 0–100, zasięg dużymi cyframi z %), potem sekcje karty oferty (zwijane).
4. Wykresy słupkowe: moc, 0–100, zasięg, cena od.

Start `/porownywarka/`: ta sama tabela z pustymi miejscami, pole wyszukiwania, „Porównaj”, lista **popularnych porównań (ostatnia doba)** z logu.

## Dane — co ustalono pomiarem

- Oferty publish: 3 051, z `specid` **2 348 (77%)**. Wersji (`specid`) w ofertach publish: ~800.
- Dane wersji = **oferta o najpełniejszym `extra_prep`** dla danego `specid`. Oferty tej samej wersji bywają różnie kompletne (np. Leopard 5 #463845: 176 kluczy przy ~300 u innych), a flagi w tabeli specs są niespójne w 95 z 383 wersji z ≥2 ofertami.
- **50 flag tabeli specs nie odróżnia wersji** (Zeekr 9X Ultra/Hyper/Max: po 39, zero różnic). Różnice są w pełnym `extra_prep` (w 9X: 30 na ekranie — 3 silniki, 1401 KM, reflektory pikselowe…). Tabela specs służy tylko do liczb w podsumowaniu.
- Po translatorze **0 wartości z CJK** na ekranie (oryginał zostaje w `raw`).
- **Zasięg:** porównujemy ten sam rodzaj u wszystkich. Łączny (`range_total`), a gdy któremuś brak — elektryczny (`range_cltc`); u elektryka oba są równe → etykieta „Zasięg CLTC”. Błąd złapany na makiecie: Denza Z9 GT łączny 1 101 km vs BYD Han L elektryczny 180 km.
- Kompletność danych uzupełniona 16.09: jednorazowy `backfill-spec-autohome.php 9999 0 manual apply` → 214 ofert, +111 pól średnio (backup `~/backups/primaauto/2026-09-16/`).

## Otwarte do wdrożenia

1. ✅ **Tabela wersji `wp7j_asiaauto_versions`** (16.09) — klasa `includes/class-asiaauto-versions-table.php` (na serwerze, **jeszcze niepodpięta** w `asiaauto-sync.php`), skrypt `scripts/zbuduj-wersje.php` (dry-run domyślnie, `apply`, `limit=`, `specid=`). Wiersz = specid: model, wersja, stały slug, rok, paliwo, nadwozie, moc/0-100/zasięgi/bateria, liczba ofert i cena „od”, oferta wzorcowa (najwięcej kluczy extra_prep) + **zrzut sekcji karty oferty** (JSON, 15 sekcji). Wersja bez ofert zachowuje zrzut, cena → brak. Zrzut odświeżany tylko, gdy nowy wzorzec jest co najmniej tak pełny. Techniczne sekcje przez Reflection na `buildTechSpecSections()` — do udostępnienia w kroku 2.
   Pierwszy bieg: **807 wersji, 2 335 ofert, 807 unikalnych slugów** (42 z dopiskiem roku, 2 z specid), 3 s, 7,2 MB zrzutów. 2 wersje bez nazwy (Voyah Dream PHEV 56516, BAIC BJ40 EREV 67937 — puste `_asiaauto_complectation`). **Mediana: 1 oferta na wersję; 414 z 807 wersji ma wzorzec < 250 kluczy** — połowa porównań będzie na uboższych danych.
   Podpięte w v0.42.0; cron **05:15** po `zbuduj-specs.php`. Odświeżania przy imporcie **nie ma** — nowa oferta nowej wersji trafia do porównywarki następnej nocy.
2. ✅ **Slug wersji** stały — nadawany raz, zapisany w tabeli.
3. ✅ **Strona porównania i start** (v0.42.0) — `class-asiaauto-compare.php`, strona WP 481993, szczegóły w `docs/VERSIONS.md` (0.42.0).
4. ✅ **Log porównań** `wp7j_asiaauto_compare_log` → „Popularne porównania” na starcie (ostatnia doba, przy pustym — 30 dni).
5. ✅ **Wersja znika z ofert** — zrzut sekcji zostaje w tabeli wersji, cena = „brak aut”.
6. ✅ **Historia porównań i lista ostatnich** (0.42.2) — `wp7j_asiaauto_compare_history`, każde wejście osobno. **Plan:** po ~1000 porównaniach analiza par modeli → ręczny wybór do indeksu (whitelist jak `asiaauto_hub_index_whitelist`), dla nich 301 na jedną kolejność, treść porównawcza, osobna sitemapa, sekcja „Popularne”; rywale → blok „Porównaj z” na hubach.
6a. **Etap 2 (w toku 17.09):** zasady przycisku „Porównaj” ustalone (sekcja „Decyzje etapu 2”); teraz makiety w 2–3 wariantach: pasek „Nowość”, link na stronie głównej, nagłówek/menu, przycisk na karcie listingu i produktu, pasek schowka, okienko zamiany. Decyzja o indeksie po danych z logu.
7. **Nazwy wersji BYD** tłumaczone dosłownie („Cloud Suspension Sky God Ultra”); 曜黑版 ma trzy tłumaczenia. Nie blokuje — wyszukiwanie idzie po modelu.
8. Słownik: „Wyświetlacz AR HUD” trafia w `hud` zamiast `ar-hud`; „Sterowanie głosem (ekran)” w „rozpoznawanie głosu bez wybudzania”.
9. „Spalanie (cykl mieszany)” 0.39 L/100km w Zeekr 9X przy 3.16 w katalogu — sprawdzić mapowanie (niezweryfikowane).
10. Hybryda vs elektryk w zasięgu łącznym (N8L 1 300 km vs ES8 635 km) — dopisek, że hybryda liczy z paliwem.
11. Oferty bez `specid` (703) nie wejdą do porównywarki; 8 ofert ze `specid` bez katalogu (poza che168).

## Strefy kruche

Addytywne: nowa klasa + nowa tabela + szablon strony. **Nie** dotyka importera, pipeline'u cenowego ani karty oferty — porównywarka **czyta** `buildTechSpecSections()` (dziś `private` → potrzebny publiczny dostęp albo wydzielenie).

## Testy

- Porównanie 3 wersji z obu źródeł, EV + PHEV → brak CJK, braki jako „—”, zasięg tego samego rodzaju.
- `/porownywarka/*` zwraca `noindex`.
- Telefon 390 px: tabela przewija się w bok, kolumna parametrów stoi, nagłówek aut przyklejony przy przewijaniu w dół.
- Wersja bez ofert → „brak aut”, strona działa.
- Czas renderu porównania 3 wersji < 500 ms.
