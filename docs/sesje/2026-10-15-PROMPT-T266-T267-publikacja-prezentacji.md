# Prompt kontynuacyjny — T-266 „/proces-zamawiania/: dopisać depozyt” + T-267 „dystrybucja prezentacji”

> Zapisane 2026-10-15. Stan wejściowy: T-265 „prezentacja procesu zakupu” ZAMKNIĘTA (v4 zaakceptowana przez Ruslana,
> lektor „Charon”), commit `0b8003b`. Postęp: 14.10 (4 h) + 15.10 (3 h).

```text
Kontynuujemy publikację prezentacji procesu zakupu w ~/projekty/primaauto.
Przeczytaj: docs/roadmapa/T-267-dystrybucja-prezentacji-procesu.md (plan, dostępy, oznaczenie AI),
wpisy T-266 i T-267 w docs/QUEUE.md, sekcję „v4” w docs/roadmapa/T-265-prezentacja-procesu-zakupu.md.

MATERIAŁ (gotowy, zaakceptowany przez Ruslana 15.10):
- Film główny z lektorem (56 s, 1080×1920, z outro):
  https://auratest.pl/fe4f58fec53ctmp/primaauto-T265-proces-zakupu-reel-v4-lektor-2026-10-15.mp4
  lokalnie ~/opt/pptr/prima-reel/reel-v4-lektor.mp4
- Film bez lektora (na platformy z muzyką): …/primaauto-T265-proces-zakupu-reel-v4-2026-10-15.mp4 (~/opt/pptr/prima-reel/reel-v4.mp4)
- Karuzela 11 × PNG 1080×1350: …/primaauto-T265-karuzela-v4-2026-10-15/ (~/opt/pptr/prima-reel/karuzela/) — IG wymaga JPG.
- Auto w materiale: Zeekr 8X /oferta/zeekr-8x-2026-540080/. Końcówka (scena „Twoje auto czeka”, slajd 11) = klip AI
  (Veo 3.1, osoba fikcyjna) → KAŻDA publikacja z oznaczeniem treści AI (YT containsSyntheticMedia, Meta „AI info”, TikTok przełącznik).
- Kluczowe sformułowanie płatności: „70% dopłacasz później — bez odsetek” (bez terminu, bez słowa „kredyt”).

KROK 1 — QUIZ NA START (AskUserQuestion, rekomendacja pierwsza, jedno pytanie na raz jeśli zależne):
 (a) Czy Ruslan zgodził się na T-266 (dopisanie depozytu 6 150 zł na /proces-zamawiania/ + „Kup teraz/Kup auto” → „Chcę to auto”)?
 (b) D1: najpierw strona, potem social (rek.) czy równolegle?
 (c) D2: TikTok publikuje Ruslan/Andrzej z telefonu z gotowców (rek.)?
 (d) D3 link na stronie oferty — rek. „nie teraz”. D4 reklamy — poza zakresem, nie ruszać żywych kampanii.

KROK 2 — STRONA (po OK na T-266):
- /proces-zamawiania/: krok „Depozyt” zgodny z filmem (0 zł przy zgłoszeniu → zwrotny depozyt 6 150 zł / 5 000 zł netto →
  30% po umowie → 70% później) + słownictwo T-264 („Chcę to auto”, „zgłoszenie rezerwacji”). Zanim zmienisz treść: makieta/
  diff do akceptu Janka; strona jest zaakceptowana przez Ruslana, ruszamy tylko depozyt i nazwę przycisku.
- Wideo na górze tej strony: wersja web 720×1280 (~4–5 MB, z lektorem), plakat JPG z okładki, <video controls playsinline
  preload="none">, bez autoodtwarzania z dźwiękiem; plik w uploads/asiaauto/wideo/jak-kupic-auto-z-chin-prima-auto-proces.mp4;
  NIE embed YouTube (baner zgód). Zdarzenie GA4 video_proces_play (bez konwersji).
- /zamow/: link „Zobacz w 50 sekund, jak wygląda zakup” nad formularzem → okienko z filmem.
- Zmiany w motywie primaauto2026 (sprawdź `wp theme list --status=active`), backup .bak, php -l, smoke test na telefonie (pptr).

KROK 3 — SOCIAL (każda publikacja = osobna zgoda Janka na TREŚĆ i TERMIN; publikujemy jako ZAPLANOWANE):
- Opisy: jeden tekst bazowy → YT (tytuł ≤70 zn. + opis z linkiem do /proces-zamawiania/), FB, IG (bez linków w treści),
  TikTok (krótko + hashtagi). Najpierw wersje robocze W CZACIE do akceptu, link auratest dopiero po akcepcie.
- YT Short + FB Reel + IG Reel tego samego dnia, 18:00, D+1 po stronie; karuzela FB (post z 11 zdjęciami) + IG (JPG) D+4.
  Dostępy sprawdzone 14.10: YT API (kanał Prima-Auto), token Meta SU z pages_manage_posts + instagram_content_publish
  (Strona 1146829831857839, IG @prima_auto.pl). Przed użyciem sprawdź ponownie, nie z pamięci.
- Gotowce dla Ruslana/Andrzeja (TikTok film + zdjęcia, stara Strona FB prima1auto, Telegram): pliki + opisy + instrukcja
  włączenia etykiety AI; wysyłka do Janka na Telegram, on przekazuje.

ZASADY: nie publikuj niczego bez osobnej zgody; nie ruszaj żywych kampanii Ads/Meta; godziny do postępu
(docs/kosztorys/dane/postep.json + build_postep.py --deploy) przy domknięciu; commit + push po każdym etapie.
```
