# Przypomnienie 16.10 — T-267 „dystrybucja prezentacji procesu zakupu”: social

> Zapisane 2026-10-15 wieczorem. Stan: **T-266 „/proces-zamawiania/: dopisać depozyt” wdrożone**, strona z filmem LIVE (commit `e61287b`).
> Co zostało: publikacja GTM (zdarzenie GA4) + cała dystrybucja social.

```text
Kontynuujemy T-267 „dystrybucja prezentacji procesu zakupu” w ~/projekty/primaauto.
Przeczytaj: docs/roadmapa/T-267-dystrybucja-prezentacji-procesu.md, wpis T-267 w docs/QUEUE.md.

STAN NA 15.10 WIECZÓR (zrobione, zweryfikowane pptr — nie powtarzać):
- /informacje/proces-zamawiania/ — depozyt dopisany (T-266), film na górze (uploads/asiaauto/wideo/
  jak-kupic-auto-z-chin-prima-auto-proces.mp4 + .webp), link z okienkiem na /zamow/ (tylko krok 1 kreatora).
- UWAGA: prawdziwy adres to /informacje/proces-zamawiania/ — /proces-zamawiania/ = 404. W opisach social
  używać pełnego: https://primaauto.com.pl/informacje/proces-zamawiania/
- dataLayer `video_proces_play` + `video_location` działa. GTM: tag 43 / wyzwalacz 42 / zmienna 41 ZBUDOWANE
  w Default Workspace, NIEOPUBLIKOWANE.
- Decyzje Janka 15.10: D1 strona najpierw (zrobione), D2 TikTok publikuje Ruslan/Andrzej z gotowców,
  D3 link na stronie oferty — nie teraz, D4 reklamy — poza zakresem (żywych kampanii nie ruszamy).

KROK 0 — quiz (AskUserQuestion): publikować GTM teraz? (rek. tak: `python3 scripts/gtm-video-proces.py`
  recon → `--publikuj`). Przed publikacją recon: oczekujące zmiany = tylko nasze 3.

KROK 1 — OPISY (najpierw W CZACIE do akceptu, link auratest dopiero po akcepcie):
- jeden tekst bazowy → YouTube Short (tytuł ≤70 zn. + opis z linkiem do /informacje/proces-zamawiania/),
  FB Reel, IG Reel (bez linków w treści — link w bio), TikTok (krótko + hashtagi), karuzela FB/IG.
- Kluczowe sformułowanie płatności: „70% dopłacasz później — bez odsetek” (bez terminu, bez słowa „kredyt”).
- Auto w materiale: Zeekr 8X. Końcówka = klip AI (Veo 3.1, osoba fikcyjna) — nie przedstawiamy jej jako klienta.

KROK 2 — TERMINY (każda publikacja = osobna zgoda Janka na TREŚĆ i TERMIN; wszystko jako ZAPLANOWANE):
- YT Short + FB Reel + IG Reel tego samego dnia o 18:00 (plan: 16.10, jeśli akcept zdąży; inaczej najbliższy dzień).
- Karuzela FB (post z 11 zdjęciami) + IG (11 × JPG — IG nie przyjmuje PNG) 3–4 dni później.
- Oznaczenie AI na KAŻDEJ platformie: YT `containsSyntheticMedia: true`; Meta „AI info” (sprawdzić, czy API
  pozwala — jeśli nie, zgłosić Jankowi przed planowaniem); TikTok przełącznik „AI-generated content”.
- Dostępy sprawdzić PONOWNIE, nie z pamięci: YT (`youtube-auth.py --whoami` → Prima-Auto), token Meta SU
  (pages_manage_posts + instagram_content_publish; Strona 1146829831857839, IG @prima_auto.pl).

KROK 3 — GOTOWCE dla Ruslana/Andrzeja (TikTok film + karuzela zdjęć, stara Strona FB prima1auto, Telegram):
- pliki + opisy + instrukcja włączenia etykiety AI; wysyłka do Janka (Telegram), on przekazuje.

MATERIAŁ:
- z lektorem (YT/FB/IG): ~/opt/pptr/prima-reel/reel-v4-lektor.mp4
  https://auratest.pl/fe4f58fec53ctmp/primaauto-T265-proces-zakupu-reel-v4-lektor-2026-10-15.mp4
- bez lektora (platformy z muzyką): ~/opt/pptr/prima-reel/reel-v4.mp4
- karuzela 11 × PNG 1080×1350: ~/opt/pptr/prima-reel/karuzela/ (+ auratest …/primaauto-T265-karuzela-v4-2026-10-15/)

ZROBIONE = GTM opublikowany, YT/FB/IG Reel + karuzela zaplanowane z oznaczeniem AI, gotowce u Janka,
QUEUE T-267 zaktualizowany, godziny do postępu (docs/kosztorys/dane/postep.json + build_postep.py --deploy),
commit + push.

ZASADY: nie publikuj niczego bez osobnej zgody; nie ruszaj żywych kampanii Ads/Meta.
```

Godziny 15.10 (do dopisania przy domknięciu): T-266 + strona T-267 ≈ 1,5 h.
