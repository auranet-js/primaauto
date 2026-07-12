# Plan reworku SKAG-2 „W drodze" — wzorcem SKAG-1 (2026-07-11)

> **STATUS: WYKONANE 2026-07-11** — apply 60 ops (googleAds:mutate, atomowo) + 6 ad_group_asset AD_IMAGE.
> Copy finalne = symulacja v6 (`primaauto-skag2-symulacja-nowe-2026-07-11.html` na auratest): bez kaucji,
> bez „6-8 tygodni", bez „cena końcowa/cło/VAT", km zaokrąglone, model raz w tytule, separator „-".
> Wariant A: pin H1 `{Model} - {cena}` + pin H2 `W drodze do Polski`; wariant B: pin H1 `{Model} - już
> w drodze` + pin H2 `{rok} - {km|fabrycznie nowy}`. Opis: „Zarezerwuj teraz. Auto w drodze do Polski."
> + `{Model} - {rok} - {km} - najbogatsza wersja.`
> Werdykt kroku 0: obrazki grupowe w search DZIAŁAJĄ (14× ELIGIBLE, 57 imp/7d) → podpięte kadry do 3 nowych
> grup SKAG-1 (Leopard7b-317106, DenzaZ9GT-314155, JetourT2-264454). Nowe grupy SKAG-1: 203991900371 /
> 197026400863 / 198353773357. RSA i sitelinki w review (PENDING/UNKNOWN) — recheck D+1.
> OTWARTE: zdjęcia AD_IMAGE dla 10 grup SKAG-2 (kadry z galerii listingów — do akceptu Janka); budżet 25 zł/dz.

> Kampania **23803851566** · konto 9506068500 · wzorzec: rework SKAG-1 z 2026-07-10
> Stan zastany: `docs/ads/skag2-symulacja-reklam-2026-07-11.md` + symulacja HTML na auratest.
> Zasada: dry-run (validateOnly) → dump before → apply → weryfikacja API. Zero mutacji przed akceptem.

## Krok 0 — zależność: recheck SKAG-1 (dziś)

Runbook `docs/przypomnienia/2026-07-11-skag1-recheck.md`: czy zdjęcia **grupowe** (ad_group_asset AD_IMAGE)
w search przeszły review i zbierają wyświetlenia. Werdykt decyduje o kroku 5 (zdjęcia SKAG-2 tym samym
mechanizmem czy inaczej). Kroki 1–4 od werdyktu NIE zależą.

## Krok 1 — backup

Dump `~/backups/primaauto/2026-07-11-skag2/before.json` (grupy + RSA + assety + keywordy z final_urls) —
rollback jak w SKAG-1.

## Krok 2 — higiena grup (DECYZJE JANKA 2026-07-11)

Recon SKAG-1 wykazał: Leopard 7 / Z9 GT / Jetour T2 **nie mają** grup w SKAG-1 (tylko kadry w puli);
za to Denza N9, BYD Leopard 5 i Mazda EZ-6 są w OBU kampaniach na tych samych keywordach EXACT
(różne egzemplarze: SKAG-1 on_lot / SKAG-2 in_transit).

| Grupa SKAG-2 | Akcja | Powód |
|---|---|---|
| BYD Sealion 8 | **PAUZA** | wydmuszka: listing 301→hub, keywordy PAUSED |
| Leopard 7 (2 konw.!) | **PAUZA + nowa grupa w SKAG-1** | auto on_lot (egz. 317106, 229 000 zł) |
| Denza Z9 GT | **PAUZA + nowa grupa w SKAG-1** | auto on_lot (egz. 314155, 285 000 zł) |
| Jetour T2 C-DM | **PAUZA + nowa grupa w SKAG-1** | auto on_lot (egz. 264454, 197 000 zł) |
| Denza N9 | **PAUZA** (dubel) | model już w SKAG-1 „od ręki" (egz. 270838) — decyzja: zero wewn. konkurencji |
| BYD Leopard 5 | **PAUZA** (dubel) | model już w SKAG-1 (egz. 270959) |
| Mazda EZ-6 | **PAUZA** (dubel) | model już w SKAG-1 (egz. 273962) |
| Geely LEVC L380 | zostaje PAUSED | listing 410 |
| „W drodze do Polski" (zbiorcza) | zostaje PAUSED | 52 duplikaty KW nie serwują; czystka opcjonalnie później |

Nowe grupy SKAG-1 („od ręki", wzorzec wczorajszy — copy z `gads_skag1_rsa_rework_2026_07_10.py`, 2 RSA/grupa,
KW EXACT `model` + `model cena`, bid 1,00): Leopard 7 → 317106 (kadr `Leopard7b-317106` gotowy; druga sztuka
387470 identyczna cenowo — zostaje poza Ads), Denza Z9 GT → 314155 (`DenzaZ9GT-314155`), Jetour T2 → 264454
(`JetourT2-264454`). Zdjęcia podpinane po werdykcie kroku 0.

## Krok 3 — RSA rework (10 grup in_transit)

**2 RSA/grupa** (A z ceną / B bez ceny), stara RSA remove — mechanika 1:1 z `tmp/gads_skag1_rsa_rework_2026_07_10.py`.

- **Pin H1:** `{Model} — już w drodze` (≤30 zn)
- **Pin H2 wariant A:** `{Model} — {cena dziś} zł` — ceny ŚWIEŻE z breakdownu (tabela niżej)
- **Final URL = listing z keywordów** (naprawia: Denza N9 → egz. 351079; 5 RSA z URL `/w-drodze/` → listingi; Z9 GT jeśli zostaje → 314155)
- **Path:** `w-drodze/rezerwacja`

### Copy — nagłówki generyczne (do akceptu; hasłowo, bez „od ręki")

1. Już płynie do Polski
2. Auto już kupione — w drodze
3. Zarezerwuj zanim dopłynie
4. Dotrze za 6-8 tygodni
5. Kaucja 5 000 zł zwrotna
6. Bądź pierwszy w kolejce
7. Rezerwacja online w 5 minut
8. Cło, VAT, rejestracja w cenie
9. Cena końcowa bez kruczków
10. Odbiór lub dostawa pod dom
11. Topowa wersja wyposażenia
12. Rezerwacja bez ryzyka
13. Polski importer

### Copy — opisy

1. Auto już kupione, płynie do Polski. Zarezerwuj teraz — będzie Twoje, gdy dopłynie.
2. Rezerwacja kaucją 5 000 zł, w pełni zwrotną. Cena all-in: cło, VAT, rejestracja.
3. Nie czekaj na zamówienie z Chin — to auto już jest w drodze. Dotrze za 6-8 tygodni.
4. Pełna obsługa importera: VIN, dokumenty, rejestracja i dostawa pod dom.

### Ceny do pinu H2 (breakdown, 2026-07-11)

| Grupa | H2 wariant A |
|---|---|
| Zeekr 9X | Zeekr 9X — 546 000 zł |
| Zeekr 8X | Zeekr 8X — 434 000 zł |
| BYD Shark 6 | BYD Shark 6 — 245 000 zł |
| Zeekr 7X | Zeekr 7X — 184 000 zł |
| Jetour G700 | Jetour G700 — 349 000 zł |
| Zeekr 001 | Zeekr 001 — 214 000 zł |
| Leopard 3 | BYD Leopard 3 — 147 000 zł |
| Deepal G318 | Deepal G318 — 178 000 zł |
| Exeed VX | Exeed VX — 170 000 zł |
| BYD Song L EV | BYD Song L EV — 176 000 zł |

(Denza N9, BYD Leopard 5, Mazda EZ-6, Leopard 7, Z9 GT, Jetour T2 — wypadają z reworku SKAG-2, patrz krok 2.)

## Krok 4 — sitelinki dedykowane (4, poziom kampanii)

| Link text (≤25 zn) | URL (zweryfikowane 200) |
|---|---|
| Auta w drodze — lista | /w-drodze/ |
| Jak działa rezerwacja | /informacje/proces-zamawiania/ |
| Faktura VAT i leasing | /informacje/finansowanie/ |
| Zapytaj o termin | /kontakt/ |

Kampanijne nadpiszą generyczne z konta (m.in. sprzeczny „Katalog… dostępne teraz").

## Krok 5 — zdjęcia AD_IMAGE per grupa (warunkowo, po werdykcie kroku 0)

- Auta fizycznie w transporcie → brak kadrów z placu; źródło = **galeria listingu** (webp na serwerze;
  prawa do zdjęć po stronie Ruslana — potwierdzone 2026-07-10).
- Kadry: square 1:1 (≥300×300) + landscape 1.91:1 (≥600×314) × 14 aut → podgląd na auratest **do akceptu**.
- Upload asssetów + `ad_group_asset` AD_IMAGE per grupa (wzorzec `tmp/gads_upload_onlot_image_assets_2026_07_10.py`).
- Jeśli recheck SKAG-1 wykaże, że grupowe obrazy w search nie serwują → decyzja z Jankiem (wspólne kampanijne / pomijamy).

## Krok 6 — weryfikacja i artefakty

1. GAQL po apply: RSA approval + Ad Strength, sitelinki primary_status, obrazy PENDING→?
2. Symulacja HTML „po" na auratest (obok symulacji „przed").
3. Runbook rechecku D+1 w `docs/przypomnienia/` + wpis kalendarza.
4. Commit docs (+ ewentualny wpis VERSIONS nie dotyczy — brak zmian w kodzie WP).

## Poza zakresem (osobne decyzje Janka)

- **Budżet 25 zł/dz** — budget lost 90%: bez podniesienia rework poprawi CR/CPA, ale kampania dalej będzie
  serwować ~10% popytu. Ewentualna realokacja z DSA/RMKT = NIE ruszać bez osobnej decyzji (memory 06-19).
- Bidy zostają 1,00 zł (wzorzec SKAG-1).
- Czystka 52 duplikatów KW w pauzowanej zbiorczej.

## Wykonanie

Skrypt `tmp/gads_skag2_rework_2026_07_11.py` (tryby: plan / dry-run validateOnly / `--apply`),
partialFailure=true, wszystkie mutacje po dump before. Kolejność apply: pauzy → RSA → sitelinki → (zdjęcia).
