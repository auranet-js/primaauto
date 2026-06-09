# ADR 2026-06-06 — Filtr globalny w reworku hubów SEO

## Kontekst
Dokańczanie reworku hubów (metoda `docs/seo/hub-rework-method-2026-05-30.md`). Kolejka niezreworkowanych hubów ze stockiem: 262 (count>0), w tym 17 count≥12, 80 count≥6.

## Decyzja
Przy reworku **filtrujemy huby wg realnej przewagi chińskiej wersji**, nie wg „marka globalna vs chińska".

**POMIJAMY** modele globalne sprzedawane w EU/PL jako **identyczna wersja** — ta sama konstrukcja, ten sam rozstaw, brak przewagi importu. Ogromna konkurencja (salony, Otomoto, dekady historii), a „z Chin" nie daje argumentu. Przykłady (count≥6, zweryfikowane w stocku — brak wariantów L/LWB):
- `golf` (VW Golf, 280/300TSI R-Line — standard)
- `xc60` (Volvo XC60 B5 Luxury), `xc60-t8-phev`, `s60` (Volvo S60 B4/B5)
- `cc` (VW CC/Arteon), `s90-t8-phev` (S90 globalny; LWB już zrobiony osobno)
- `smart-1` (Smart #1 — dystrybucja PL przez Geely-Mercedes)
- `id-4-crozz` / `id-4-x` (VW ID.4 — w EU; chińskie warianty border, niska wartość)

**ROBIMY** modele, gdzie chińska wersja daje realną przewagę (decyzja PER-MODEL w web-recheck — differentiator):
- **Unikalnie chińskie** (brak w EU): BYD/Geely/Chery/Hongqi/Zeekr/Jetour/Changan/Avatr/Deepal/Voyah/Leapmotor/AITO/Li Auto itd., oraz chińskie warianty marek globalnych niedostępne w EU (np. `volvo-xc70` — nowy chiński PHEV; VW `bora`/`sagitar`/`lamando`/`magotan` — chińskie sedany spoza EU).
- **Wersja L/LWB** (wydłużona, niedostępna w EU): np. Mercedes E L, Volvo S90 LWB (zrobiony), Audi A6 L — inny produkt niż salonowy.
- **Bogatsze wyposażenie w standardzie / niższa cena / dostępność od ręki** — gdy mierzalne i prawdziwe.

## Reguła operacyjna
Differentiator oceniamy **per-model w web-recheck** (nie per-marka). Marka globalna nie dyskwalifikuje huba, jeśli chińska wersja ma L/LWB, lepsze wyposażenie lub jest wariantem spoza EU. Brak differentiatora (czysty Golf) = pomijamy.

## Konsekwencje
- Kolejka reworku skraca się o ~9 globalnych bez przewagi (count≥6).
- Wzrasta waga web-recheck per hub (trzeba ustalić differentiator i prawdziwy framing — bez fałszywego „wyłącznie z importu").
- Gdy wejdzie stock typu Mercedes E L / Audi A6 L — robimy mimo globalnej marki.

## Powód korekty
Pierwotne „pomiń wszystko globalne" było za grube (uwaga Janka): Mercedes E z Chin bywa w wersji L, lepiej wyposażony i tańszy — realny powód importu. Filtr ma celować w BRAK przewagi, nie w pochodzenie marki.
