# Prompt kontynuacyjny — T-265 v4 „uwagi Ruslana: 70% bez terminu + emocjonalna końcówka”

> Zapisane 2026-10-15. Źródło: rozmowa Janka z Ruslanem (transkrypcja w wątku z 15.10), po wysłaniu mu v3.
> Stan wejściowy: v3 zaakceptowana przez Janka 14.10, commit `97af091`; pliki `fe4f58fec53ctmp/primaauto-T265-*-v3-2026-10-14*`.

```text
Kontynuujemy T-265 „prezentacja procesu zakupu — wideo + karuzela” w ~/projekty/primaauto.
Przeczytaj: docs/roadmapa/T-265-prezentacja-procesu-zakupu.md, wpis T-265 w docs/QUEUE.md,
docs/sesje/2026-10-15-PROMPT-T265-v4-uwagi-ruslana.md (ten plik) i scripts/reel-proces-zakupu/.
Silnik: reel.html (render(t), tryby reel 1080×1920 i karuzela 1080×1350) + prima-reel-zrzuty.mjs
+ prima-reel-render.mjs, render przez `pptr` na Mikrusie (zasoby w ~/opt/pptr/prima-reel/, rsync na mikrus,
klatki wracają, kodowanie ffmpeg na Elarze -threads 4, outro: Dysk PrimaAuto/video/logo,
primaauto-czolowka-medal-v3-light-9x16-2026-08-25.mp4, xfade 0,5 s). Chrome NIE na Elarze.

UWAGI RUSLANA (rozmowa 15.10) — tylko te dwie rzeczy, reszta v3 zostaje:

1. 70% BEZ TERMINU. Nie piszemy „przed portem”, „przed przybyciem”, „gdy auto jest w Polsce” — to reklama,
   nie umowa. Ma zostać: 30% → potem 70%. Ruslan chce w tym miejscu korzyść: „bezpłatny kredyt na czas
   transportu” (że na czas transportu klient nie płaci reszty — „dajemy to w prezencie”).
   Do zmiany: scena „fin” (nagłówek „70% przed portem, odbiór w Rzeszowie”), slajd 10 karuzeli,
   ewentualnie podtytuł sceny „umowa” („Wtedy wpłacasz 30% ceny”) i licznik wpłat.
   ⚠️ UWAGA — słowo „kredyt” w reklamie dla konsumentów: ustawa o kredycie konsumenckim ma wymogi dla reklam
   kredytu (m.in. informacje o kosztach i RRSO), a odroczona płatność bez umowy kredytowej nazwana „kredytem”
   może być odebrana jako wprowadzająca w błąd. Nie jestem prawnikiem — zgłoś to Jankowi w quizie startowym
   z wariantami, rekomendacja: sformułowanie bez słowa „kredyt”, np.
   „Na czas transportu resztę płacisz później — bez odsetek” / „70% dopłacasz dopiero później, bez odsetek” /
   „Przez transport Twoje pieniądze zostają u Ciebie”. Wariant dosłowny Ruslana też w quizie (jego decyzja,
   przez Janka). Po decyzji sprawdź spójność z regulaminem usługi / stroną /depozyt/ (bez zmian w nich).

2. KOŃCÓWKA Z EMOCJAMI. Dziś końcówka = zdjęcie auta + CTA + outro — Ruslan: „tragiczna, jak film bez końca”.
   Chce: auto przyjechało, zadowolony klient — ktoś podchodzi do auta z kokardą, obejmuje maskę, radość.
   Plan: krótki klip 3–5 s wygenerowany AI (9:16) wstawiony jako nowa scena przed CTA/outro,
   z nakładkami w stylu filmu (trasa dochodzi do „Polska”, licznik 100%, nagłówek np. „Twoje auto czeka”).
   - Narzędzie: Freepik API (Kling, image-to-video; klucz ~/secrets/freepik/api-key.txt, memory
     cross-project reference_freepik_api.md) — pierwszy kadr generowany obrazem (Gemini
     gemini-2.5-flash-image wg zasady „domyślnie tanio”, albo Freepik Mystic), potem animacja.
     PRZED generowaniem: sprawdź cennik/kredyty i saldo, pokaż Jankowi koszt jednej próby i limit prób
     (Janek: to wymaga kilku iteracji, „jak dziecko”) — zgoda na budżet, potem generuj.
   - Auto w klipie: na razie Denza Z9 GT (ta z filmu). Ruslan przyśle docelowy model — wtedy podmieniamy
     ogłoszenie w całym filmie I generujemy końcówkę ponownie, więc trzymaj prompt generacji w repo
     (scripts/reel-proces-zakupu/koncowka-prompt.md) razem z parametrami i wybranym ujęciem.
   - Osoba w klipie: fikcyjna, bez podobieństwa do realnych osób, bez logo innych marek. Przy publikacji
     na Meta/YouTube/TikTok realistyczna treść AI wymaga oznaczenia „wygenerowane przez AI” — dopisz to
     do planu T-267 (docs/roadmapa/T-267-dystrybucja-prezentacji-procesu.md), nie publikuj nic.
   - Kokarda na aucie: duża czerwona (kolor marki #C92A2B) — spina się z paletą filmu.
   - Jeśli żadna z prób nie trzyma jakości (zniekształcone auto, dłonie, twarz) — pokaż Jankowi najlepsze
     2–3 i zaproponuj fallback: zdjęcie stockowe Freepik (osoba z kokardą przy aucie) z animacją
     zbliżenia w naszym silniku — bez udawania, że to nasz klient.

3. PRZYGOTUJ PODMIANĘ AUTA (Janek: „na razie zostaw ten model, potem podmienimy samo ogłoszenie”).
   Zrób z ogłoszenia jeden parametr: listing (ID/URL) w jednym miejscu → zrzuty (oferta, specyfikacja,
   zgłoszenie z listing_id, filtr marki), zdjęcia auta (okładka, fin), nazwa i cena w grafikach
   (panel, umowa: dziś na sztywno „Denza Z9 GT DM-i Ultra”, „302 000 zł”) oraz marka w scenie filtra
   (dziś klik w „Denza” i „Pokaż 146 wyników”). Współrzędne kliknięć bierz z pomiaru DOM, nie na sztywno.
   Dziś zostaje Denza — podmiana dopiero, gdy Ruslan przyśle model.

PRZEBIEG WĄTKU
- Start: git pull, jeden quiz (AskUserQuestion, rekomendacja pierwsza): (a) brzmienie pkt 1 — warianty,
  (b) budżet i liczba prób generacji końcówki, (c) nagłówek sceny końcowej.
- Potem bez pytań w trakcie: zmiany w reel.html, generacja końcówki, render v4 (film + karuzela),
  QA klatek z każdej sceny (oglądaj!), wystawienie na auratest, wysyłka na Telegram Janka (film + album).
- Nie zmieniaj tego, czego Ruslan nie zgłosił. Nie publikuj nigdzie. Commit + push po akcepcie Janka.
- Godziny: dopisz do postępu dopiero przy domknięciu (wpis T-265 ma dziś 4 h).
```
