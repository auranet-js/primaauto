# T-238 — odmrożenie cen w hubach + AEO na Z9 GT (2026-08-05)

> Task powstał w trakcie sesji rankingowej: mierząc frazy pod tytuły rankingów, trafiłem
> na hub z frazą 14 800/mc stojący na 4. pozycji z zawyżoną ceną w tytule.
> Wątek główny (T-229/T-230, rankingi) — patrz prompt kontynuacyjny na końcu.

## Co się okazało

**Flaga `_asiaauto_skip_title_regen = 1` zamraża cenę w tytule huba.** Chroni ręcznie
napisany `rank_math_title` przed codziennym cronem `AsiaAuto_HubTitleGenerator` — i to
działa zgodnie z zamysłem. Skutek uboczny: cena wpisana w ten tytuł zostaje z dnia
wpisania, a oferta rotuje.

Pomiar: **12 termów z flagą, wszystkie 12 rozjechane**, suma odchyleń 156 000 zł.
Rozjazd w obie strony, obie szkodzą:

| kierunek | przykład | skutek |
|---|---|---|
| tytuł drożej niż baza | Zeekr 8X: 420 000 wobec 343 000 | tracimy przewagę cenową w SERP |
| tytuł taniej niż baza | Denza N9: 255 000 wobec 275 000 | klient klika obietnicę, widzi drożej |

**Google przepisywał tytuł na trzech hubach.** Z9 GT, Zeekr 9X i Zeekr 8X nie miały
`_asiaauto_h1_suffix`, więc renderowały domyślne H1 „— import z Chin”, które nie
potwierdzało obietnicy z `<title>` („cena w Polsce 2026 — od …”). Google rozstrzygał
rozjazd na korzyść H1 i w SERP pokazywał wynik **bez ceny** — czyli bez naszego
najmocniejszego argumentu. Pozostałe 265 hubów ma H1 zawierające słowo „cena”.

**Denza jest oficjalnie w Polsce.** Treść huba Z9 GT twierdziła, że model „dopiero pojawi
się w polskiej dystrybucji”. Nieprawda — jest w konfiguratorze `denza.com/pl`:
**526 320 zł** za wersję DM w konfiguracji bazowej (pomiar u źródła, kolor Obsidian bez
dopłaty; Emerald Green +12 000). Krążące po artykułach 511 700 zł jest nieaktualne.
Nasza oferta: od 197 000 zł. Szczegóły: memory `reference_denza_oficjalnie_w_polsce_cennik`.

## Co zrobione

Wszystko jako **dane w bazie — bez zmian w kodzie pluginu** (decyzja Janka).

- **12 tytułów** — cena zsynchronizowana z `min(price)`
- **3 H1** dopisane (Z9 GT, Zeekr 9X, Zeekr 8X)
- **24 opisy** (`rank_math_description` + `asiaauto_seo_desc`) — cena i liczba egzemplarzy
- **Hub Z9 GT pod AEO** — usunięty fałsz o dystrybucji, wstawione porównanie
  526 320 wobec 197 000, nowe pytanie FAQ „Ile kosztuje BYD Denza Z9 GT?” pod PAA
- **4 huby** (Z9 GT, Zeekr 001, Arrizo 8, YU7) — przedziały cenowe w treści zastąpione
  ceną wejścia, mediany usunięte

## Czego świadomie NIE zrobiono

**8 hubów zostało z nieaktualnymi przedziałami w treści** — automatyczna podmiana
by je uszkodziła:

- **Zeekr 8X** ma w tekście cennik wersji („Ultra 898KM to 416 000 PLN, Ultra+ — 436 000,
  Yao Ying — 487 000”). Podmiana przedziału na „od 343 000” tworzy sprzeczność z akapitem obok.
- **Leopard 7** ma tabelę porównawczą z BMW X5 i Mercedesem GLE, gdzie cena jest wierszem
  zestawienia.
- Podobnie 9X, EZ-6, Leopard 5, Monjaro, N9. Shark 6 nie ma przedziałów wcale.

Te wymagają ręcznej redakcji albo regeneracji treści, nie podmianki. Skrypt sam je
wykrywa — kontrola „Pozostałe wystąpienia starych liczb” wypisuje osierocone liczby.

**Liczby egzemplarzy w narracji** zostają nietknięte — rotują codziennie i nie niosą
wartości (decyzja Janka: „narracja o ilości nie jest w ogóle istotna”).

**Przyczyna nie jest domknięta.** Flaga dalej blokuje cron, więc ceny znów odjadą.
Bez zmian w kodzie jedyne wyjście to wpis w crontabie odpalający
`t238-hub-title-refresh.php` cyklicznie. Do decyzji.

## Dowód braku degradacji

Dwa niezależne poziomy, oba czyste.

**Baza** (`t238-audyt-degradacji.php`) — 12 hubów, 0 zastrzeżeń: balans tagów
`p/strong/h2/h3/table/tr/td` zerowy, JSON FAQ parsuje się, ceny w tytule i opisie zgodne
z `min(price)`. Ubytek treści 22–44 znaki na hub = dokładnie usunięte mediany.

**Produkcja** (HTTP, 12 hubów) — wszystkie `200 OK`, dokładnie jeden `<h1>`,
JSON-LD 5/5 poprawnych (Shark 6: 4/4), cena z bazy obecna w HTML, zero zlepków typu
`197 000PLN`.

## Wpadki tej sesji

1. **Regex zjadł spację** — pierwszy przebieg dałby „od 197 000PLN” w 12 tytułach naraz.
   Ten sam typ błędu co 04.08 (77 uszkodzonych pól). Złapane w symulacji, nie na produkcji.
2. **Jedna podmiana nie wystarczyła** — ta sama narracja siedziała w `wiki_body`
   w czterech miejscach. Kontrola HTML po zapisie wykryła pozostałości trzy razy z rzędu.
   Wniosek: po każdej podmianie w polu HTML sprawdzaj wyrenderowaną stronę, nie samo pole.
3. **Raport skryptu kłamał** — pokazywał podmianę tam, gdzie następowało usunięcie
   (mediana). Poprawione: `$trafienia` trzyma parę (dopasowanie, zamiennik).
4. **Tag w środku wzorca** — „PLN`</strong>` (mediana …)”. Wciągnięcie tagu w podmianę
   rozerwałoby HTML; rozdzielone na dwa niezależne przebiegi.

## Skrypty

| plik | co robi |
|---|---|
| `t238-hub-title-refresh.php` | tytuły + H1 + opisy dla wszystkich termów z flagą |
| `t238-wiki-ceny.php` | przedziały cenowe w `wiki_body`, per `term_id`, z kontrolą sierot |
| `t238-z9gt-aeo.php` | lead + sekcja + FAQ na Z9 GT |
| `t238-z9gt-akapit.php` | akapit wstępny `wiki_body` |
| `t238-z9gt-faq1.php` | FAQ nr 1 |
| `t238-audyt-degradacji.php` | read-only audyt niezmienników |

Wszystkie: **symulacja domyślnie**, zapis argumentem pozycyjnym `apply`
(WP-CLI odrzuca własne `--flagi` przy `eval-file`), backup w meta `_t238_backup_*`.

## Otwarte

- 8 hubów z przedziałami wymagającymi ręcznej redakcji
- cron pilnujący cen (albo świadoma zgoda na ręczne przebiegi)
- zgłoszenie Z9 GT do ponownej indeksacji + baseline pozycji przed/po
- pomiar: ile innych hubów siedzi na pozycjach 4–10 przy frazach >1000/mc
  (hipoteza „dociąganie hubów tańsze niż nowa treść” — niezweryfikowana)
