# 2026-09-26/27 — pismo Volkswagen AG (Bird & Bird) + zastrzeżenie o autoryzacji

## Pismo
- **Od:** Bird & Bird LLP Hamburg (Dr. Frederik Thiering, Sophia C. Groß — zespół znaków towarowych), w imieniu **Volkswagen AG**. Sygnatura `VOLKS.0520 FRCT SOQG`.
- **Chronologia:** mail 16.07.2026 na `china@primaauto.com.pl`, termin 23.07.2026 (minął). List polecony z warszawskiego biura B&B nadany 23.09.2026 na adres domowy Ruslana (Rzeszów, Pleśniarowicza 2a/38).
- **Zarzut:** znaki EUTM „VOLKSWAGEN” (EM 000703702), logo VW (IR 1555245), „ID.” (IR 1441120). Oferta ID.4 CROZZ (VIN `LFV…`, FAW-VW), hub `/samochody/volkswagen/id-7-vizzion/`, `o-nas`, homologacja. Brak wyczerpania prawa (art. 15 EUTMR — auta wprowadzone na rynek w Chinach).
- **Deklaracja do podpisu:** zaniechanie VW, ID.4, ID.7 i każdego „ID.x”, kara umowna wg uznania VW za każdy tydzień, wycofanie i zniszczenie, ujawnienie dostawców/klientów/zysków, odszkodowanie, **4 897,60 €** kosztów (WPS 500 000 €), prawo niemieckie, sąd w Hamburgu. Osobiście Ruslan + firma.
- **Kontekst:** LG Hamburg 19.03.2026, 312 O 182/23 — VW wygrał z dwoma dealerami ID.6 CROZZ (zakaz, wydanie aut do zniszczenia, odpowiedzialność osobista prezesów).
- Oryginał PDF: skrzynka `claude@auratest.pl`, mail [323] (26.09). Nie w repo (dokument klienta).

## Stan strony (26.09)
- Oferta ID.4 CROZZ 272357 już nie istnieje (301 na hub).
- 10 ofert VW live (wszystkie z che168): Passat, Magotan, Talagon, **ID.ERA 9X** (468717).
- Huby `volkswagen/`, `id-7-vizzion/`, `id-4-crozz/` = 200; hub ID.7 ma cenę i „oferujemy” w treści i w title.
- Zamówienia VW w systemie: 2 (Viloran, 27.05) — oba **anulowane** w maju. Sprzedaży VW przez stronę nie było. Offline — wie tylko Ruslan.

## Co poszło do Ruslana (przez Janka)
1. **Mail 26.09** (send-to-jan): nie podpisywać, prawnik od znaków towarowych, 4 pytania dla prawnika, wzór umowy agencyjnej, 6 kroków do wykonania po jego „tak” (drafty ofert VW, blokada VW w imporcie, huby VW off, usunięcie z Google, VW z Ads/katalogu Meta, zrzut stanu przed/po).
2. **Telegram 27.09** (msg 980): propozycja zastrzeżenia o autoryzacji + makieta
   https://auratest.pl/fe4f58fec53ctmp/primaauto-zastrzezenie-dla-ruslana-2026-09-27.html

**Czekamy na odpowiedź Ruslana. Na stronie NIC nie zmieniono.**

## Zastrzeżenie — ustalenia z Jankiem
- Miejsca: ogłoszenie — po „Wyposażeniu”, przed „Inne modele {marka}” (`class-asiaauto-single.php` render); huby — pod `aa-hub__usp`, przed FAQ (`taxonomy-serie.php`, `taxonomy-make.php`); stopka — jedno zdanie w `pa-footer__bottom` (`footer.php`). Pełny tekst → `/regulamin-uslugi/#zastrzezenia`.
- Tekst ogłoszenia: „Prima-Auto to niezależny, bezpośredni importer samochodów z Chin. Nie jesteśmy autoryzowanym dealerem marki {Marka}. Auto pochodzi z rynku chińskiego, spoza oficjalnej sieci w UE, a nazwa marki służy identyfikacji pojazdu.”
- Pod SEO/AEO: widoczny tekst w HTML, nie w title/H1/meta, na dole treści, z nazwą marki ze strony.
- **„Bezpośredni importer” ZOSTAJE** (decyzja Janka 27.09 — gra z ChatGPT i frazą „import”, na którą rankują huby). Występuje: meta description 3 486 ofert (`class-asiaauto-single.php:1025`), hero strony głównej, `o-nas`, opisy hubów lynk-co / xc60 / 03 / id-3.
- Otwarte (decyzja Janka): „import i **sprzedaż**” w opisie firmy w stopce.
- Wewnętrzne makiety z wariantami: `primaauto-zastrzezenie-makiety-2026-09-27.html`, `…-v2-2026-09-27.html` (auratest tmp).

## Xiaomi (dla porządku)
Pismo Xiaomi Inc. z 31.07.2026 było do **Auto-Ekspert, Mława** (nie do nas), mail [290] z 29.08. Wtedy zaproponowano zastrzeżenie — nie zostało wdrożone.

## Następny ruch
Po „tak” Ruslana: 6 kroków z maila (VW) i/lub wdrożenie zastrzeżenia wg makiety. Tekst zastrzeżenia najlepiej po przejrzeniu przez prawnika Ruslana.
