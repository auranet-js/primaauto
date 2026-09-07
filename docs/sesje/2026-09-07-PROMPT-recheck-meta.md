# Recheck Meta — prompt wykonawczy, 2026-09-07

> Recheck był umówiony na **05.09 19:00** i nie został zrobiony. Kampanie ruszyły **04.09
> o 17:52**, więc dziś masz za sobą **~3 pełne doby**, nie jedną — to zmienia zakres:
> faza uczenia zestawu (2–3 dni) już minęła, więc **wolno oceniać kreacje**, i są dwie
> **zamknięte** doby (05.09, 06.09) do uczciwego porównania Meta ↔ GA4.
>
> Kontekst kampanii, zasady tekstów i lista błędów, których nie powtarzamy:
> `docs/sesje/2026-09-04-PROMPT-meta-kampanie-kontynuacja.md` — **przeczytaj przed startem.**
> Mechanika sprawdzenia UTM-ów: `docs/przypomnienia/2026-09-05-utm-meta-w-ga4.md`.

## Stan wyjściowy (do porównania)

- Konto `act_1038563008906171`, limit łączny `spend_cap` **1 000 zł**, na 04.09 o 19:44 wydane
  **0,05 zł** przy 11 wyświetleniach i 2 kliknięciach.
- Chodzi **6 reklam** w trzech kampaniach: `[VID]` 25 zł/dz (2 filmy), `[POST]` 10 zł/dz
  (2 posty), `[FOTO]` 15 zł/dz (2 karuzele) — razem **50 zł/dz**.
- Teksty wszystkich reklam przepisane 04.09 na assety z Google Ads; karty karuzel noszą
  `_serie_full_title` z bazy i cenę wejścia.
- Sześć żywych reklam dostało 04.09 wieczorem `url_tags`:
  `utm_source=facebook&utm_medium=paid_social&utm_campaign={{campaign.name}}&utm_content={{ad.name}}`.
  Przed zmianą ruch z Mety wpadał do GA4 wyłącznie jako `l.facebook.com / referral`.

## Kroki

### 1. Stan dostawy i alarmy

```bash
python3 scripts/social/recheck_start.py
```

Czytasz: czy któraś reklama ma `DISAPPROVED` / `WITH_ISSUES` / `PENDING_BILLING_INFO`, ile
wyświetleń i kliknięć zebrała każda z sześciu, jaki CTR, ile zeszło z konta.

**Uwaga na limit wywołań** (`17/2446079`): po serii zapisów odczyty potrafią zamilknąć na
kilkanaście minut. Buduj zapytania zbiorczo — jedno `/ads` z zagnieżdżonymi polami zamiast
pętli po zestawach.

### 2. Budżet — licz dni pod sufitem, nie samą kwotę

Ze `spend_cap` 1 000 zł i 50 zł dziennie limit pęka po ~20 dobach. Podaj: ile wydane, ile
zostało, **ile dni przy obecnym tempie** (`feedback_budzet_sprawdz_dni_pod_sufitem`). Jeśli
realne tempo jest dużo niższe od 50 zł/dz — to sygnał o dostawie, nie oszczędność.

### 3. Czy UTM-y doszły do GA4 (polecenie Janka z 04.09)

Zapytanie gotowe w `docs/przypomnienia/2026-09-05-utm-meta-w-ga4.md`, ale **zmień zakres
z `today` na `2026-09-04` … `2026-09-07`** i rozbij po `date` — chodzi o to, od której doby
`facebook / paid_social` w ogóle się pojawia.

Sukces: `facebook / paid_social` z nazwą kampanii (`[VID] Auta z Chin — nowi odbiorcy`,
`[FOTO] Zdjęcia z sesji — karuzele`, `[POST] Promowanie postów — ruch`) i nazwą reklamy
w `sessionManualAdContent`.

**Nie jest porażką** `l.facebook.com / referral` obok — to organiczne posty Andrzeja, one
UTM-ów nie mają i mieć nie będą. Liczy się, czy **obok** pojawił się `facebook / paid_social`.

Gdy nie widać:
1. `python3 scripts/social/utm_kreacje.py --lista` — czy tagi nadal są na kreacjach (Meta ich
   nie zdejmuje, ale nowa kreacja od Andrzeja by ich nie miała),
2. czy reklamy w ogóle miały kliknięcia w linki (`inline_link_clicks`) — przy kilkudziesięciu
   wyświetleniach mogło ich po prostu nie być; wtedy brak sesji nie jest błędem pomiaru.

### 4. Porównanie Meta ↔ GA4 na zamkniętych dobach

Dopiero teraz to ma sens (05.09 i 06.09 są zamknięte, atrybucja policzona). Zestaw kliknięcia
w linki z Mety z sesjami `facebook / paid_social` w GA4 per doba. Różnica rzędu 20–40% jest
normalna (odbicia, blokery, wielokrotne kliknięcia); różnica rzędu **zera po stronie GA4 przy
kilkudziesięciu kliknięciach w Mecie** oznacza zepsuty pomiar i wtedy to jest wniosek dnia.

Liczby do raportu bierz **ze świeżego odczytu API**, nie z dashboardu
`https://auratest.pl/pa-live-8aaf08d6ece0db176603/` — dashboard to podgląd, nie dowód
(`feedback_dashboard_to_podglad_nie_dowod`).

### 5. Ocena kreacji — dopiero teraz wolno

Po trzech dobach faza uczenia minęła. Dla każdej z 6 reklam: wyświetlenia, CTR, CPC, koszt
zdarzenia. Porównuj **w obrębie kampanii** (inne cele: `[VID]` i `[FOTO]` mają ViewContent,
`[POST]` LINK_CLICKS — CPC między nimi nie jest porównywalny).

Konwersją dla nas jest wyłącznie kontakt: telefon, WhatsApp, formularz
(`reference_ads_konwersje_tylko_kontakt`). ViewContent to sygnał pośredni, nie wynik.

## Czego NIE robić

- **Nie wyłączaj, nie włączaj i nie zmieniaj budżetów z własnej inicjatywy.** Błąd w żywej
  reklamie zgłaszasz jednym zdaniem i czekasz — decyzja jest Janka
  (`feedback_nie_wylaczaj_zywych_reklam_sam`).
- **Budżet `[POST]` 10 → 15 zł: temat zamknięty przez Janka 04.09.** Nie wracaj do niego.
- **Nie przeredagowuj tekstów.** Zmiana treści = wybór innego assetu z konta Google Ads
  (`9506068500`), nigdy własne zdanie (`feedback_teksty_reklam_z_assetow_ads`).
- **Nigdy słowo „homologacja"** w treści widzianej przez klienta — „przygotowanie do rejestracji".
- Nie wyciągaj wniosków z pojedynczej kreacji przy kilkuset wyświetleniach — to szum.

## Zrobione wygląda tak

1. Sześć reklam z liczbami: wyświetlenia, kliknięcia, CTR, koszt, zdarzenia — ze świeżego API.
2. Odpowiedź TAK/NIE na pytanie „czy `facebook / paid_social` jest w GA4", z datą pierwszej doby.
3. Porównanie kliknięć Meta ↔ sesji GA4 dla 05.09 i 06.09.
4. Budżet: wydane / zostało / dni do sufitu.
5. Propozycje następnych ruchów **położone samodzielnie**, nie po pytaniu Janka — z listy
   otwartych: grupy z obejrzenia wideo (ręcznie w Menedżerze, API odbija każdą wersję reguły),
   remarketing dynamiczny z katalogu (2 990 aut, feed czysty, nie zbudowany), kampania
   WhatsApp (najlepszy kanał kontaktu w Ads: 43 kontakty/30 dni, konto ma
   `WHATSAPP_DESTINATION_ADS`).
6. Zapis stanu w `docs/sesje/2026-09-07-recheck-meta.md` + aktualizacja
   `docs/meta/plan-kampanii.md`, commit.
