# PROMPT — przegląd strony „Postęp prac i plan rozwoju” dla Ruslana

Strona: https://auratest.pl/pa-postep-eb460f6c3c4c12755858/ (adres zahaszowany, nigdzie go nie linkować).
Źródło danych: `docs/kosztorys/dane/postep.json`, budowanie: `python3 docs/kosztorys/build_postep.py --deploy`.
Zasady prowadzenia strony: memory `project_strona_postepu_ruslan_2026_07_17`. Wpisy piszemy językiem dla Ruslana, nie treścią commitów. Nowe wpisy idą na początek listy, z godzinami. Zamknięte taski przenosimy do `todo_ukryte_id`.

Cel: strona ma odpowiadać stanowi faktycznemu na dziś. Stan weryfikujemy przez `git log`, `docs/VERSIONS.md` i serwer, nie przez sam JSON.

## Część 1 — postęp (changelog)

Ostatnie wpisy na stronie: 14.10 (płatności) i 08.10 (flaga A). Między nimi brakuje pracy z 09–14.10. Wg `git log --since=2026-10-08`:
- 13.10: T-264 „Zamów → zgłoszenie rezerwacji” wdrożone (v0.45.2), wraz z pomiarem przycisku i przypomnieniami 27.10 i 10.11.
- 13.10: T-262/T-263, czyli makiety PDF (wybrany wariant B z logo) i model wersji.
- 13.10–14.10: BYD Tang L → „Tang L (Atto 8)”, hub EV, Sealion 08 rozdzielony od Sealion 8, przekierowania 301.
- 14.10: llms.txt i O nas, wyróżnik „auta na placu i w drodze”.
- 13.10: recheck reklam po zmianach z 04.10.

Dla każdej pozycji ustal, czy ma się pokazać Ruslanowi i czy jest już ujęta w innym wpisie. Godziny weź z sesji (`docs/sesje/`), bez zgadywania; brak danych zaznacz Jankowi. Pozycje bez wartości dla Ruslana (np. czysto techniczne korekty dokumentacji) pomiń.

## Część 2 — plan prac (todo)

Widoczne pozycje: T-264, T-256, T-262, T-263, T-224, T-240, T-254, T-243, T-249. Ukryte: 18 ID. Przy każdej widocznej sprawdź status z kodem i git:
- **T-264**: status mówi „wdrożenie po Twoim potwierdzeniu”, a wdrożone jest 13.10. Zmienić na wdrożone albo ukryć.
- **T-256**: zawieszone 14.10. Do decyzji Janka: zostaje widoczne jako zawieszone czy idzie do ukrytych.
- **T-262 / T-263**: czy status odpowiada makietom z 13.10 i kolejności (najpierw T-262).
- **T-224, T-240, T-243, T-254, T-249**: czy opis i status są aktualne (np. T-249 po spotkaniu 07.10, T-243 po podziale ról w social z 07.10).
- Taski z `docs/QUEUE.md` otwarte i uzgodnione z Ruslanem, a nieobecne na stronie: lista do decyzji Janka, bez automatycznego dopisywania.

## Wynik

1. Najpierw raport do akceptu Janka: tabela wpis/task | stan na stronie | stan faktyczny | proponowana zmiana. Nowe wpisy changelogu pokaż jako tekst roboczy w czacie.
2. Po akcepcie: edycja `postep.json` (backup `.bak-YYYY-MM-DD`), build, `--deploy`, sprawdzenie strony po publikacji.
3. Commit `[docs] postęp — …`, push dopiero na polecenie.

## Poza zakresem

Kosztorys etapów (`etap*.json`, `raport.html`). Płatności online są zamknięte, nie wracamy do nich. Rozliczenie miesięczne odbywa się 02.11.
