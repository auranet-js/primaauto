# T-267 „dystrybucja prezentacji procesu zakupu (T-265)”

> Zgłoszone 2026-10-14 (Janek). Materiał: T-265 v3 — film 9:16 (51 s, z outro) i karuzela 4:5 (10 slajdów).
> Pliki: `fe4f58fec53ctmp/primaauto-T265-proces-zakupu-reel-v3-2026-10-14.mp4`, `fe4f58fec53ctmp/primaauto-T265-karuzela-v3-2026-10-14/`.
> Status: plan do akceptu. **Każda publikacja = osobna zgoda Janka na treść i termin**
> (`feedback_publikacja_zewnetrzna_wymaga_osobnej_zgody`). Publikujemy jako **zaplanowane**, nie natychmiast.

## Stan dostępów (sprawdzony 14.10, nie z pamięci)

| Kanał | Konto | Dostęp | Jak publikujemy |
|---|---|---|---|
| YouTube | kanał Prima-Auto `UCsfcUP3uwrDxPI_mDIle8Pw`, 31 filmów, 30 subów | API działa (`youtube-auth.py --whoami` → Prima-Auto), upload + metadane | my, przez API, jako Short z `publishAt` |
| Facebook | nowa Strona `1146829831857839`, 237 fanów | token SU: `pages_manage_posts` ✓ | my, przez API, zaplanowane (Reel + post z 10 zdjęciami) |
| Instagram | `@prima_auto.pl`, 136 obserwujących | token SU: `instagram_content_publish` ✓ | my, przez API (Reel + karuzela 10 JPG; IG nie przyjmuje PNG) |
| Stara Strona FB `prima1auto` | 16,7 tys. obserwujących, tylko Andrzej | brak API | gotowiec dla Andrzeja (pliki + opis) |
| TikTok | `@primaauto.pl`, 348 obs., 132 filmy, **konto osobiste** (`commerceUser:false`) | brak API (Business Center odrzucony 28.08, Content Posting wymaga audytu aplikacji) | patrz D2 |
| Telegram | kanał/grupa Ruslana | brak dostępu | gotowiec dla Ruslana |
| Strona primaauto.com.pl | — | pełny | osadzenie wideo, patrz niżej |

## Gdzie na stronie

1. **`/proces-zamawiania/`** — wideo na górze strony, nad opisem 5 kroków. To strona, która opisuje dokładnie to, co pokazuje film,
   i ta sama, którą poprawia **T-266** (brakujący depozyt). Robimy oba **jednym wejściem**: wideo + dopisany krok „Depozyt”,
   zgodny z paskiem wpłat z filmu. Linkują do niej: „Proces zamawiania” w bocznym boksie oferty i w formularzu zgłoszenia.
2. **Formularz zgłoszenia `/zamow/`** — mały link „Zobacz w 50 sekund, jak wygląda zakup” nad formularzem, otwiera film w okienku.
   Klient, który już kliknął „Chcę to auto”, waha się najbardziej — to jest moment na film.
3. **Strona oferty** (opcjonalnie, osobna decyzja) — link obok „Chcę to auto”. To strona konwersyjna i zmiana wizualna,
   więc najpierw makiety 2–3 wariantów (desktop + telefon), zgodnie z regułą makiet.

**Technika:** wideo z naszego serwera, nie embed YouTube — embed YT jest blokowany przez baner zgód do akceptacji cookies
(większość ruchu by go nie zobaczyła) i ciągnie ~1 MB skryptów. Wersja webowa 720×1280 (~4–5 MB), plakat JPG z okładki,
`preload="none"`, `playsinline`, `controls`, bez autoodtwarzania z dźwiękiem. Plik w `uploads/asiaauto/wideo/` z nazwą pod SEO
(`jak-kupic-auto-z-chin-prima-auto-proces.mp4`). Zdarzenie GA4 `video_proces_play` (bez oznaczania jako konwersja).

## Kolejność (propozycja)

| # | Co | Kto | Kiedy |
|---|---|---|---|
| 0 | Materiał + krótka wiadomość do Ruslana i propozycja poprawki `/proces-zamawiania/` (T-266) | Janek (my przygotowujemy tekst) | od razu |
| 1 | Strona: `/proces-zamawiania/` (wideo + depozyt) i link na `/zamow/` | my | po OK Ruslana na T-266 |
| 2 | YouTube Short + FB Reel + IG Reel — ten sam film, ten sam dzień | my, zaplanowane | D+1 po stronie, 18:00 (okno 15–19 z pomiaru kanałów) |
| 3 | Karuzela: post FB z 10 zdjęciami + karuzela IG | my, zaplanowane | D+4 (odstęp ≥3 dni od Reela) |
| 4 | TikTok film + TikTok zdjęcia; stara Strona FB; Telegram | Ruslan / Andrzej z gotowców | w tych samych dniach co 2 i 3 |
| 5 | Reklamy (osobna decyzja, nie w tym tasku) | — | po tygodniu danych organicznych |

Opisy: jeden tekst bazowy dopasowany do każdej platformy (YT tytuł ≤70 znaków + opis z linkiem do `/proces-zamawiania/`;
FB/IG bez linków w treści IG — link w bio; TikTok krótko + hashtagi). Do akceptu Janka przed zaplanowaniem.

## Oznaczenie treści AI (od v4)

Od v4 (15.10) końcówka filmu i ostatni slajd karuzeli to **klip wygenerowany przez AI** (Veo 3.1, osoba fikcyjna). Realistyczna
treść AI wymaga oznaczenia przy publikacji: YouTube — „Altered or synthetic content” = tak w Studio / `containsSyntheticMedia: true`
w API; Meta (FB/IG) — etykieta „AI info” (przy uploadzie przez API: flaga w Business Suite / ręcznie, sprawdzić przy planowaniu);
TikTok — przełącznik „AI-generated content”. Do opisów gotowców dla Ruslana/Andrzeja: dopisać, żeby włączyli tę etykietę.
Nie przedstawiamy osoby z klipu jako naszego klienta.

## Decyzje do podjęcia

- **D1 Strona przed social czy równolegle** — rek.: najpierw strona (krok 1), bo opisy na social kierują na `/proces-zamawiania/`,
  a ta dziś nie zgadza się z filmem (brak depozytu).
- **D2 TikTok** — rek.: **publikuje Ruslan/Andrzej z telefonu** (5 min, gotowe pliki + opis). Alternatywa: Janek dostaje login,
  publikujemy przez przeglądarkę — ryzyko weryfikacji logowania na telefonie Ruslana przy każdym wejściu z nowego miejsca,
  a tryb karuzeli zdjęć jest pewny tylko w aplikacji.
- **D3 Link na stronie oferty** — rek.: **nie teraz**; najpierw zmierzyć odtworzenia na `/proces-zamawiania/` i `/zamow/`.
- **D4 Reklamy** — kandydaci, bez decyzji: Meta `[RMKT]` (odwiedzający bez kontaktu — film odpowiada na „jak to działa”),
  Google Ads Demand Gen / `[VID]` z Shortem jako zasobem. Żywych kampanii nie ruszamy bez osobnego polecenia.

## Wycena

**4–6 h realnie**: strona (wersja web, plakat, osadzenie, link w `/zamow/`, GA4) 2–3 · YT + FB + IG z planowaniem 1–1,5 ·
opisy na 5 platform + gotowce dla Ruslana/Andrzeja 1–1,5. T-266 liczone osobno (1 h).
