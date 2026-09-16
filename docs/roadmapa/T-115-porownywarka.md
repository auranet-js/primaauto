# T-115 — Porównywarka aut

> Status: **wdrożenie w toku — krok 1 (tabela wersji) zrobiony 16.09** · Rozmiar: M/L
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
   **Nie podpięte:** cron (miejsce: po `zbuduj-specs.php` 05:05) i odświeżanie przy imporcie — do decyzji przy kroku 2.
2. ✅ **Slug wersji** stały — nadawany raz, zapisany w tabeli.
3. **Log porównań** (para/trójka specid, data, licznik) → lista popularnych.
4. **Wersja znika z ofert** — strona porównania dalej działa (dane z ostatniej oferty wzorcowej trzymane w tabeli wersji / banku), cena = „brak aut”.
5. **Nazwy wersji BYD** tłumaczone dosłownie („Cloud Suspension Sky God Ultra”); 曜黑版 ma trzy tłumaczenia. Nie blokuje — wyszukiwanie idzie po modelu.
6. Słownik: „Wyświetlacz AR HUD” trafia w `hud` zamiast `ar-hud`; „Sterowanie głosem (ekran)” w „rozpoznawanie głosu bez wybudzania”.
7. „Spalanie (cykl mieszany)” 0.39 L/100km w Zeekr 9X przy 3.16 w katalogu — sprawdzić mapowanie (niezweryfikowane).
8. Hybryda vs elektryk w zasięgu łącznym (N8L 1 300 km vs ES8 635 km) — dopisek, że hybryda liczy z paliwem.
9. Oferty bez `specid` (703) nie wejdą do porównywarki; 8 ofert ze `specid` bez katalogu (poza che168).

## Strefy kruche

Addytywne: nowa klasa + nowa tabela + szablon strony. **Nie** dotyka importera, pipeline'u cenowego ani karty oferty — porównywarka **czyta** `buildTechSpecSections()` (dziś `private` → potrzebny publiczny dostęp albo wydzielenie).

## Testy

- Porównanie 3 wersji z obu źródeł, EV + PHEV → brak CJK, braki jako „—”, zasięg tego samego rodzaju.
- `/porownywarka/*` zwraca `noindex`.
- Telefon 390 px: tabela przewija się w bok, kolumna parametrów stoi, nagłówek aut przyklejony przy przewijaniu w dół.
- Wersja bez ofert → „brak aut”, strona działa.
- Czas renderu porównania 3 wersji < 500 ms.
