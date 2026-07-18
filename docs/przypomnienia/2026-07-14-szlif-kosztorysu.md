# Przypomnienie: szlif kosztorysu przed spotkaniem z Ruslanem (czwartek 16.07)

> **✅ ZROBIONE.** Kosztorys doszlifowany i pokazany Ruslanowi na spotkaniu 2026-07-16. Deadline zamknięty.
> Kontynuacja bieżąca: strona postępu prac (`build_postep.py --deploy`, memory `project_strona_postepu_ruslan_2026_07_17.md`).

> Utworzone: 2026-07-12 · Termin: **wtorek 2026-07-14** · Projekt: primaauto
> Deadline twardy: **czwartek 2026-07-16 — spotkanie Janek↔Ruslan**, kosztorys będzie pokazany na żywo
> Raport live: https://auratest.pl/pa-kosztorys-3ee728365b3d1a5b895e/
> Źródła: `docs/kosztorys/dane/*.json` + `build.py` (regen: `python3 build.py --deploy`)
> Memory: project_kosztorys_ruslan_2026_07_04.md

## Stan na 2026-07-12 (commit a12fdce)

Kosztorys kompletny i wdrożony:
- **Etap 1** (budowa do prezentacji 08.04): 823 h rynkowo / 360 h realnie (12 bloków)
- **Etap 2** (15.04–12.07): 1760 h rynkowo / 521 h realnie (61 pozycji) — dopisane
  sesje 07-07…07-12 (T-190, audyt +114%, audyt 4-obszarowy, DSA/SKAG/RMKT rework,
  anty-scraping)
- **Etap 3** (roadmapa): 21 pozycji, 1412–1682 h rynkowo / 602–724 h realnie —
  dopisane T-201 (YouTube) i T-202 (kreator zamówienia trust)
- **NOWA sekcja „Prace regularne — stała obsługa miesięczna":** 3 pozycje
  (Google Ads optymalizacja / opieka techniczna / SEO), 36–48 h/mc rynkowo,
  15–21 h/mc realnie
- Liczby odświeżone: 3053 ogłoszeń publish, 128 zamówień, 92 wydania, 198 commitów;
  timeline ma wpis wyjaśniający spadek katalogu 6129→3053 (zamrożony feed)

## Co szlifować we wtorek (kandydaci — decyzje Janka)

1. **Widełki godzin** — przejrzeć pozycje etapu 2 z 07-07…12 i prace regularne;
   czy stawki bronią się przy negocjacji.
2. **Przeliczenie na PLN?** — raport dziś pokazuje tylko godziny; metodologia
   „realnie może być podstawą rozliczenia" (etap 3 opis). Decyzja: czy dodać
   stawkę/kwoty przed czwartkiem.
3. **Ton opisów pod rozmowę na żywo** — raport będzie pokazywany, nie wysyłany;
   sprawdzić czy nagłówki/leady dobrze prowadzą narrację (wartość → koszt →
   roadmapa → miesięczna obsługa).
4. **Sekcja prac regularnych** — to nowość i prawdopodobny główny temat negocjacji
   (przejście z projektu na abonament); doprecyzować zakres/warianty jeśli trzeba.
5. **Koszty zewnętrzne** — `dane/koszty.json` nieaktualizowane od 07-04; sprawdzić
   czy nie doszła faktura auto-api (cykl 18. dnia — następna ~18.07, po spotkaniu).

## Jak wygląda „zrobione"

Raport po szlifie zregenerowany (`build.py --deploy`), Janek potwierdza że wersja
jest „na czwartek", commit+push. Event kalendarza oznaczyć ✅.
