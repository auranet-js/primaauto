# Audyt Meta Ads — dlaczego nie ma kontaktów, prompt wykonawczy, 2026-09-10

> **Zlecenie Janka (10.09):** „wydajemy tam sporo pieniędzy, a konwersji z tego nie ma".
> Pytanie do rozstrzygnięcia: **czy Meta w obecnym kształcie w ogóle może dowozić kontakty,
> a jeśli nie — co konkretnie zmienić albo wyłączyć.** Wynik to diagnoza z liczbami
> i decyzje do podjęcia przez Janka, nie zmiany na koncie.
>
> Konwersja = **telefon, WhatsApp, formularz zamówienia**. Nic innego
> (`reference_ads_konwersje_tylko_kontakt`). ViewContent, kliknięcia i obejrzenia to nie wynik.

## Przeczytaj przed startem

1. `docs/meta/plan-kampanii.md` — sekcja 0 (stan po recheck 07.09), 1 (cele i decyzja
   o optymalizacji na ViewContent), 2 (struktura), 9 (zasada bezwzględna).
2. `docs/sesje/2026-09-07-recheck-meta.md` — ostatni pełny odczyt, identyfikatory reklam.
3. `docs/architektura/pomiar-i-monitoring.md` — jak mierzymy, pułapki GA4 „dziś".
4. Pamięć: `project_meta_ads_reset_2026_08_28`, `project_recheck_meta_2026_09_07`,
   `project_top30_remarketing_2026_09_08` (98% pozycji katalogu to auta, których nie ma).

## Stan wyjściowy — odczyt 10.09 ok. 16:00 (API Mety + GA4)

Konto `act_1038563008906171`: wydane łącznie **325,91 zł** z `spend_cap` 1 000 zł, budżety
dzienne razem **62 zł** → sufit około **21.09**. Kampanie chodzą od **04.09 17:52**.

| Kampania | Cel / optymalizacja | Wydane | Kliknięcia w link | LPV Meta | Sesje GA4 | Kontakty GA4 |
|---|---|---|---|---|---|---|
| `[VID]` Auta z Chin — nowi odbiorcy | LEADS / ViewContent | 142,05 zł | 717 | 105 | 167 | 0 |
| `[FOTO]` Zdjęcia z sesji — karuzele | LEADS / ViewContent | 86,47 zł | 601 | 192 | 162 | 1 (WhatsApp) |
| `[POST]` Promowanie postów — ruch | TRAFFIC / LINK_CLICKS | 60,22 zł | 201 | 113 | 18 | 0 |
| `[RMKT]` Remarketing dynamiczny — katalog | SALES / ViewContent | 37,31 zł | 175 | 138 | 34 | 0 |

- Sesje i kontakty GA4 to `facebook / paid_social`, zakres 04.09–09.09 (doba zamknięta).
- **Meta nie przypisała ani jednego zdarzenia `Contact`** żadnej kampanii. Wszystkie zestawy
  mają `attribution_spec` = **CLICK_THROUGH 7 dni**, bez view-through.
- Piksel od 04.09, cała witryna: PageView 10 463, ViewContent 9 916, **Contact 35**.
  GA4 w tym samym czasie: ok. 50 zdarzeń `click_phone` + `click_whatsapp` ze wszystkich źródeł.
- Dla porównania Google Ads: CPA kontaktu ok. 50 zł (`docs/ads/mapa-kampanii.md`).

Te liczby są punktem startu. **Odczytaj je od nowa**, nie przepisuj — w raporcie mają być
świeże wartości z API (`feedback_dashboard_to_podglad_nie_dowod`).

## Hipotezy — sprawdź w tej kolejności, każdą liczbą

### 1. Meta robi dokładnie to, o co ją poprosiliśmy
Trzy z czterech kampanii optymalizują na **ViewContent**. Decyzja z 28.08 była świadoma
(za mało `Contact`, żeby wyjść z fazy uczenia), ale jej koszt jest taki, że algorytm szuka
ludzi, którzy tanio otwierają ofertę, a nie tych, którzy dzwonią. Sprawdź:
- ile `Contact` z piksela na tydzień jest dziś i czy konsolidacja w **jeden zestaw
  z optymalizacją na `Contact`** ma szansę na sensowną liczbę zdarzeń,
- czy da się optymalizować na zdarzeniu pośrednim bliższym kontaktowi (np. własna konwersja:
  ViewContent + czas na stronie albo wejście na `/kontakt/`), i ile takich zdarzeń jest.

### 2. Kontakt jest, ale ląduje gdzie indziej (atrybucja)
Człowiek widzi reklamę na telefonie, wraca później przez Google albo wpisując adres i dzwoni.
GA4 w zasięgu sesji przypisze to organikowi, Meta bez view-through nie zobaczy nic. Sprawdź:
- GA4 w zasięgu **użytkownika**: `firstUserSourceMedium` = `facebook / paid_social`
  × `click_phone` / `click_whatsapp` / `generate_lead`,
- czy liczba kontaktów z organiku / direct wzrosła od 04.09 wobec 4 tygodni wcześniej
  (uwaga: w tym samym czasie działa Google Ads i sezonowość — nie wyciągaj wniosków z samej
  różnicy),
- raport Mety z oknem `1d_view` / `7d_click` na zdarzeniu `Contact` — przez API
  `action_attribution_windows`, bez zmiany ustawień zestawów.

### 3. Zdarzenie `Contact` nie strzela tam, gdzie powinno
Piksel 35 wobec ok. 50 w GA4 to ok. 70% pokrycia — możliwe, że to zgoda i blokery, ale
sprawdź, czy tag `Contact` w GTM (`GTM-T4GTZ2JB`) odpala się na obu przyciskach (telefon
i WhatsApp) na **mobile**, i czy CAPI (`capi_send.sh`) wysyła `Contact` z `fbc`/`fbp`.
Bez `fbc` Meta nie połączy kontaktu z kliknięciem w reklamę.

### 4. Kliknięcia są puste
`[VID]`: 717 kliknięć w link → 105 wczytań strony w Mecie. Rozbij wydatki i kliknięcia
po `publisher_platform` × `platform_position` × `device_platform`: czy pieniądze idą w Audience
Network, Reels z przypadkowymi kliknięciami, Messenger. `[POST]`: 201 kliknięć → 18 sesji,
czyli ok. 3,35 zł za sesję — już wiadomo, że Meta liczy tu też rozwinięcia posta.

### 5. Landing nie prowadzi do kontaktu
Dokąd prowadzi każda reklama (home, hub, oferta, kod odpowiedzi — Denza: tylko `-dm-i`
działają), czy na mobile przycisk telefonu i WhatsApp jest widoczny bez przewijania, ile
trwa sesja i jaki jest odsetek wyjść. Porównaj z `google / cpc` na tych samych landingach.

### 6. Oczekiwania i skala
Auto za 150–400 tys. zł, prospecting do ludzi, którzy o marce nie słyszeli. Policz,
ile kontaktów da się uczciwie oczekiwać przy 62 zł dziennie i CPA z Google Ads jako
punkcie odniesienia. Zaproponuj **próg werdyktu**: przy jakiej kwocie i braku kontaktów
kampania dostaje wyrok, żeby następny recheck nie był znowu „za wcześnie, żeby ocenić".

### 7. `[RMKT]` katalog
Zestaw „Oglądane — 30 dni" reklamuje auta, których w 98% nie ma na stanie. Zestaw
„Na placu w Polsce" — sprawdź, ile pozycji realnie ma i czy w ogóle wydaje.

## Zasady, których nie łamiesz

- **Nie wyłączasz, nie włączasz i nie zmieniasz budżetów żywych reklam sam**
  (`feedback_nie_wylaczaj_zywych_reklam_sam`). Rekomendacje idą do Janka quizem
  (`AskUserQuestion`, sekwencyjnie, rekomendacja jako pierwsza opcja).
- `spend_cap` i doładowania to nie nasza pozycja do pilnowania (plan-kampanii sekcja 2).
- `url_tags` ustawia się **tylko przy tworzeniu kreacji** — każda nowa kreacja z `api.UTM_TAGI`.
- Podmiana kreacji wysyła reklamę do przeglądu. Konto puszcza jeden zapis na 30 s (kod `613`),
  seria odczytów potrafi zamilknąć na kilkanaście minut (`17/2446079`) — pytaj zbiorczo.
- Porównania Meta ↔ GA4 tylko na **dobach zamkniętych** (GA4 „dziś" ma do 64% sesji bez źródła).
- Grupy z piksela pokazują `20-20` — to maskowanie, rozmiar czytaj przez `delivery_estimate`.

## Narzędzia

```bash
python3 scripts/social/recheck_start.py          # dostawa, alarmy, wydatki per reklama
python3 scripts/social/rotacja.py --stan         # co chodzi, flaga UTM przy każdej reklamie
bash ~/secrets/meta/meta_call.sh --project primaauto-2026 "/act_1038563008906171/insights?..."
python3 scripts/checki-pomiaru.py                # czy pomiar w ogóle działa
# GA4: scripts/ga4_query.py (token, run, rows), property 534017542
```

## Co ma powstać

1. `docs/sesje/2026-09-XX-audyt-meta-konwersje.md` — każda hipoteza z wynikiem: potwierdzona,
   obalona albo nierozstrzygnięta, z liczbami i zapytaniem, które je dało.
2. Lista decyzji dla Janka w kolejności wpływu na wydatki, każda z kosztem pozostawienia
   bez zmian do sufitu budżetu.
3. Aktualizacja sekcji 0 w `docs/meta/plan-kampanii.md` i pamięci projektu.
