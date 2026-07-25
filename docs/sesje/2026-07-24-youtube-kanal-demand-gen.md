# Sesja 2026-07-24 — kanał YouTube + kampanie wideo (Demand Gen / placementy)

> **PROMPT KONTYNUACYJNY** — wklej treść tego pliku (albo powiedz „przeczytaj `docs/sesje/2026-07-24-youtube-kanal-demand-gen.md`") w nowym wątku.
> Memory projektu: `project_youtube_kanal_i_demand_gen_2026_07_24.md` (wpisane, START dla YouTube/wideo).

---

## 1. Kanał YouTube — dostęp (ROZSTRZYGNIĘTE, nie wracać)

- Kanał: **`@primaauto_com_pl`**, ID `UCsfcUP3uwrDxPI_mDIle8Pw`, „Prima - Auto - Import Samochodów z Chin", założony 2025-12-03, kraj PL. Połączony z Google Ads (konwersje „YouTube channel subscriptions/follow-on views" są na koncie).
- **Zapis przez API NIEMOŻLIWY.** Kanał nie jest kontem marki (Brand Account), Janek ma tylko delegację z YouTube Studio → nie pojawia się w OAuth chooser. Google nie daje żadnego obejścia (parametr „w imieniu kanału" = tylko partnerzy CMS). **Nie proponować ponownie OAuth na kanał.**
- **Odczyt przez API DZIAŁA:** `~/secrets/google/youtube-tokens.json` (tożsamość Victorini — wystarcza do odczytu publicznego), YouTube Data API v3 włączone w projekcie GCP 325733204269.
- **Wszystkie operacje zapisu na kanale = Chrome automation przez YouTube Studio na sesji Janka** (rozszerzenie Claude in Chrome). Działa sprawnie.

## 2. Co zrobione na kanale (24.07)

**Konfiguracja (etap B):**
- Słowa kluczowe kanału: naprawiona literówka „impor" → 17 fraz (import samochodów z Chin, auta z Chin, chińskie samochody, BYD, Denza, Zeekr, Voyah, Jetour, Exeed, Hongqi, leopard 5, leopard 7, Prima Auto, Rzeszów…).
- Linki na kanale (było 0): `/samochody/`, `/w-drodze/`, homepage, TikTok `@primaauto.pl`. Opublikowane.
- Playlisty (było 0): **„Auta na placu w Rzeszowie — dostępne od ręki"**, **„W drodze do Polski — auta już kupione"** — publiczne, z opisami i linkami.
- Nietknięte: opis kanału (drobiazg: literówka „Tell:" zamiast „Tel."), baner, logo, znak wodny.

**Publikacja 8 nowych filmów (etap A)** — pliki wgrał Janek, metadane i publikację zrobił Claude. Wszystkie publiczne, „nie dla dzieci", w playliście „Auta na placu", każdy z ceną w tytule + deep-linkiem do oferty w 1. linii opisu + blokiem kontaktowym + hashtagami:

| Auto | Cena | Film poziomy | Short (pion) | Oferta |
|---|---|---|---|---|
| Jetour T2 C-DM PHEV | 201 000 zł | `foaG-5R1uzk` | `4Ave82-_7yg` | `/oferta/jetour-t2-c-dm-2025-264454/` |
| Exeed VX **(Omoda 11)** | 189 000 zł | `X_Olrxx4y1U` | `9W5mp7_CTVk` | `/oferta/exeed-vx-lanyue-2026-263901/` |
| BYD Leopard 5 niebieski Flagship | 226 000 zł | `7UTtfN7WaII` | `Gc9khBpjq_I` | `/oferta/byd-leopard-5-denza-b5-2025-270792/` |
| BYD Leopard 5 czarny Smart Drive Ultra | 233 000 zł | `ptYgxcc99ok` | `JREnGhOomqI` | `/oferta/byd-leopard-5-denza-b5-2025-270959/` |

> Obserwacja Janka: **Omoda 11 (Exeed VX) ma najlepszą oglądalność** ze wszystkich rolek dodanych 24.07.

**ZAKAZ:** starych 11 Shortsów (te z tytułami „11/13 июля 2026 г.") **NIE RUSZAMY** — Janek wyraźnie zabronił. Identyfikacja na wypadek zmiany zdania jest w memory.

**Pliki źródłowe:** Dysk Google → Prima Auto/sesje/2026-07-23/filmy (folder `1FMBKl7gj_hvo-imnTRXrGI51sBiHacM1`): 7 MP4 720p + ściąga „METADANE — jak opisywać te filmy (v2).txt" (tytuły, opisy, hashtagi, zasada: poziom 16x9 → YouTube/FB, pion 9x16 → Shorts/Reels/Rolki/TikTok). Obok ~50 zdjęć `SCH024xx`. **Token Google ma teraz pełny scope `drive`** (rename/delete działa) — `~/secrets/google/reauth-add-drive-full.py`.

## 3. Google Ads (konto 9506068500) — etap C

**Zasada budżetowa Janka: BEZ nowego budżetu**, tylko realokacja w ramach 152 zł/dz.

**Zrobione:**
- `[DSA] Import modele z Chin`: **45 → 15 zł/dz** (API). Powód: 204 zł/konwersję + Google wygasza tworzenie nowych DSA.
- **`[DG] Demand Gen — auta z Chin (YouTube)`**, id **24069066886**, **20 zł/dz**, maks. liczby konwersji, **ENABLED**:
  - Cele konwersji zawężone do **Kontakty + przesłania formularzy** (odznaczone: wyświetlenia YouTube, wiadomości Google).
  - Grupa **„DG — świeży ruch PL (YouTube)"**: geo Polska, język polski, **kanały: TYLKO YouTube** (In-Stream + In-Feed + Shorts; Discover, Gmail, GDN, Mapy — WYŁĄCZONE, zgodnie z życzeniem Janka).
  - Odbiorcy: custom segment **„Import aut z Chin — hasła + strony konkurencji"** (8 haseł wyszukiwania + 6 URL-i: autopunktmlawa.pl, autoklasa.pl, azjaauto.com, kanały YT @piotrchiny, @AutoMadeInChina_yt, @autopunktmlawa) + **wykluczenie listy „Wszyscy odwiedzający (suma) — wykluczenie SKAG"** (świeży ruch). Optymalizowane kierowanie ON.
  - Reklamy: **„DG wideo — Leopard 5 czarny"** (2 filmy → oferta 270959), **„DG wideo — Exeed VX (Omoda 11)"** (2 filmy → oferta 263901), **„DG karuzela — auta na placu Rzeszów"** (4 karty: Leopard 5 czarny / Jetour T2 / Exeed VX / Leopard 5 Flagship, każda z własnym deep-linkiem; **główny URL `/w-rzeszowie/`** — poprawka Janka, wcześniej było `/samochody/`). 13 zdjęć z sesji Rzeszów wgrał Janek do biblioteki komponentów Ads.

**NIEDOKOŃCZONE — `[VID] Placementy — widzowie konkurencji`:**
- Kreator kampanii wideo jest **otwarty w Chrome jako wersja robocza** (`draftId=10206016071`, campaignId roboczy `281499045921782`), URL: `ads.google.com/aw/campaigns/new/video?...draftId=10206016071`.
- Ustawione już: nazwa `[VID] Placementy — widzowie konkurencji`, cel „Zasięg, wyświetlenia i interakcje w YouTube" → podtyp **„Wyświetlenia filmu"** (TrueView CPV), formaty In-Stream/In-Feed/Shorts, **budżet dzienny 10,00 zł**, bez daty końca, geo Polska, język polski, **sieć: tylko YouTube** (odznaczona sieć partnerów wideo), grupa reklam **„Placementy — kanały konkurencji"**, **miejsca docelowe: 4/4 kanały** (PIOTR CHINY `UCVigOS7bbFCHfZaYvfoHgjw`, Autopunktmlawa_pl `UCk2YrZO2Y1Rnb3UBHwH1aeA`, Autoklasa `UCE0Ds1aPUka9-hO6oVpJR4A`, AutoMadeInChina `UCY3mFXRAq_zDZZbn7JSM97w`), stawka CPV zastosowana (sugestia 0,07 zł).
- **DO ZROBIENIA:** dodać film(y) w sekcji „Utwórz reklamy wideo" (rekomendacja: `X_Olrxx4y1U` Exeed/Omoda 11 — najlepsza oglądalność — plus `ptYgxcc99ok` Leopard 5 czarny), URL docelowy = odpowiednia oferta, CTA/nagłówek, **limit częstotliwości 3 wyświetlenia/tydzień**, wykluczenia treści (gry/dzieci — w kampanii wideo do ustawienia w „Wykluczenia treści"), potem **„Utwórz kampanię"**. Budżet 10 zł/dz jest już wliczony w podział 152 zł (istnieje też nieużyty budżet API `campaignBudgets/15746508172`).

**Suma po zmianach:** Brand 10 + Topic 35 + SKAG-1 20 + SKAG-2 25 + DSA 15 + RMKT 17 + DG 20 + VID 10 = **152 zł/dz** (bez zmian).

## 4. Gotchas techniczne (zapisane też w memory)

- **Kampanii VIDEO nie da się utworzyć przez Google Ads API** (`MUTATE_NOT_ALLOWED`, trigger „VIDEO") — tylko UI. Demand Gen przez API tworzy się, ale targeting/reklamy trzeba dokończyć w UI (v21 nie zna nowych poziomów: „error code is not in this version").
- **Konwersje `GOOGLE_HOSTED`** (Local actions, YouTube subs/views) są **niemutowalne przez API** (`MUTATE_NOT_ALLOWED`) — nie da się zdjąć „primary" globalnie; obejście = cele konwersji per kampania (zrobione w DG).
- **Frequency cap nie działa w Demand Gen** (`OPERATION_NOT_PERMITTED_FOR_CONTEXT`) — tylko w kampaniach wideo.
- **UI Ads:** teksty wpisywać przez `find` + `form_input` (zwykłe `type` gubi fokus w edytorze DG), `Escape` zamyka cały kreator (nie używać), zapis reklamy może wywołać ekran **„Potwierdź swoją tożsamość"** — musi kliknąć Janek.
- Merchant Center/feed produktowy dla aut **odpada** (polityka Shopping wyklucza kompletne pojazdy; Vehicle Ads niedostępne w PL) — zamiast feedu używamy karuzeli obrazów.
- Linki do ofert są bezpieczne: sprzedane listingi robią **301 na hub modelu/marki** (`class-asiaauto-redirects.php`), 410 tylko gdy marki nie da się rozpoznać.

## 5. Reguły od Janka z tej sesji (przestrzegać)

1. **Nie robić rzeczy, o które nie prosił** (nie duplikować plików na Dysku, nie ruszać starych Shortsów, nie dokładać scope'u).
2. Przy operacjach na Dysku/kanale **najpierw powiedzieć co zrobię**, potem robić.
3. „Działaj" = wykonuj bez dopytywania; pytania tylko przy realnych rozwidleniach.
4. Cena w tytule filmu = OK (zgodne z politykami YouTube, tak robi cała skuteczna konkurencja).

---

## 6. Prompt do nowego wątku (skopiuj)

```
Kontynuujemy wątek YouTube/wideo dla primaauto. Przeczytaj:
- docs/sesje/2026-07-24-youtube-kanal-demand-gen.md (pełny stan)
- memory: project_youtube_kanal_i_demand_gen_2026_07_24.md

Do dokończenia: kampania [VID] Placementy — widzowie konkurencji w Google Ads
(konto 9506068500). Wersja robocza czeka w kreatorze (draftId=10206016071);
jeśli draft przepadł — odtwórz wg opisu z sekcji 3. Zostało: dodać film
(X_Olrxx4y1U Exeed/Omoda 11 + opcjonalnie ptYgxcc99ok Leopard 5), URL docelowy
na ofertę, nagłówek/CTA, limit częstotliwości 3/tydz., wykluczenia treści
(gry, treści dla dzieci), a potem „Utwórz kampanię". Wszystko przez Chrome
(kampanii VIDEO nie da się zrobić przez API).

Potem: raport startowy — co poszło live i co mierzyć za 7-14 dni.
```
