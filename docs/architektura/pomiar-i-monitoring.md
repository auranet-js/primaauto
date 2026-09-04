# Pomiar i monitoring — stan faktyczny

> Ostatnia weryfikacja: 2026-09-04. Wszystkie liczby w tym pliku są zmierzone tego dnia,
> nie oszacowane. Plik opisuje, **co czym mierzymy i czemu można wierzyć** — nie plany.

Powód istnienia tej warstwy: Menedżera Reklam Meta widzą Ruslan i Andrzej, my nie mamy
kont na Facebooku. Bez własnej strony nie ma jak sprawdzić, czy kampanie chodzą i czy
pomiar nie kłamie.

## 1. Gdzie to stoi

Wszystko w jednym zahaszowanym katalogu z `noindex`:
`https://auratest.pl/pa-live-8aaf08d6ece0db176603/`

Zahaszowana nazwa to **zasłona, nie autoryzacja** — nie wkładamy tam danych osobowych
ani niczego z sekretów. W plikach JSON są wyłącznie liczby i nazwy kampanii; tokeny
zostają po stronie serwera.

| Plik | Co w środku | Skąd | Cron |
|---|---|---|---|
| `index.html` | jedna strona pomiaru: checki, Meta, GA4, Ads | `scripts/dashboard-pomiaru.html` | statyczna |
| `data.json` | GA4 realtime + ruch „dziś" per kampania | `scripts/ga4-live-collector.py` | `*/3 * * * *` |
| `meta.json` | Meta Ads: konto, kampanie, reklamy | `scripts/social/meta_live_collector.py` | `*/30 * * * *` |
| `ads.json` | Google Ads: kampanie dziś i wczoraj, reklamy DISAPPROVED | `scripts/ads-live-collector.py` | `*/30 * * * *` |
| `checki.json` | 12 checków TAK/NIE + kontekst doby zamkniętej | `scripts/checki-pomiaru.py` | `7 * * * *` |
| `meta.html` | starszy, osobny widok samej Mety | `scripts/social/meta_dashboard.html` | statyczna |

Strona odświeża się sama co minutę i przy powrocie do karty; nie odpytuje żadnego API
z przeglądarki, czyta gotowe pliki.

**Alarm, gdy cron stanął:** strona liczy wiek każdego pliku i wypisuje czerwony pasek,
gdy przekroczy próg (GA4 12 min, Meta i Ads 70 min, checki 130 min). W nagłówku zawsze
widać wiek każdego z czterech źródeł i to, ile minut ma najstarsza liczba na stronie.

## 2. Checki — jedna lista TAK/NIE

```
python3 scripts/checki-pomiaru.py            # tabela
python3 scripts/checki-pomiaru.py --cicho    # tylko to, co nie gra
python3 scripts/checki-pomiaru.py --json <plik>
```

Read-only. Kod wyjścia: 0 wszystko gra, 1 są uwagi, 2 awaria. Dwanaście checków:
świeżość obu kolektorów, logi błędów, reklamy Meta bez `DISAPPROVED`, UTM-y na kreacjach,
landingi reklam Meta (HTTP 200), limit konta Meta, piksel przyjmujący zdarzenia,
udział źródeł przypisanych w GA4, zdarzenia kontaktu w GA4, praca konta Google Ads.

Koszt przebiegu: 3 wywołania Graph API, 2 zapytania GA4 Data API, 1 GAQL, plus HEAD
na landingi. Konto Meta ma kroczący limit wywołań — stąd godzina, nie 30 minut.

## 3. Czemu można wierzyć, a czemu nie

### GA4 „dziś" NIE nadaje się do liczenia udziału kanałów

Atrybucja sesji dolicza się do ~24 h. Zmierzone 04.09 na tej samej właściwości:

| Zakres | `(not set)` + `(data not available)` |
|---|---|
| dziś | **64,0%** (221 + 97 z 497 sesji) |
| wczoraj | 5,4% |
| 7 dni | 2,0% |
| 30 dni | 1,5% |

To **nie jest ubytek pomiaru** ani wina Consent Mode — to opóźnienie przetwarzania.
Dlatego dashboard pokazuje strukturę kanałów **wyłącznie z doby zamkniętej**, a wiersze
bez przypisania są tam oznaczone. Próg alarmowy w checkach: powyżej 10% w dobie
zamkniętej to już realny ubytek sygnału, wtedy szukamy przyczyny.

**Konsekwencja dla porównań Meta ↔ GA4:** zestawiamy dobę zamkniętą z dobą zamkniętą.
Porównanie „dziś" pokaże rozjazd, którego nie ma.

### Realtime GA4 nie zna źródła ani kampanii

Twarde ograniczenie Google (`runRealtimeReport` nie ma wymiaru kampanii). Licznik
„aktywni teraz" to czysty realtime; rozbicie per kampania idzie z Data API z zakresem
„today" i ma kilka minut opóźnienia. Strona rozdziela te dwie warstwy nagłówkami
i podpisem — nie wolno ich czytać jak jednej liczby.

### Piksel Meta `1634147041766916` — sygnał płynie

Zmierzone 04.09 przez `/{pixel}/stats?aggregation=event`:

| Doba | PageView | ViewContent | Contact |
|---|---|---|---|
| 01.09 | 1 284 | 1 225 | 6 |
| 02.09 | 1 421 | 1 347 | 9 |
| 03.09 | 1 459 | 1 392 | 3 |
| 04.09 (do 19:00) | 972 | 913 | 1 |

Meta domyka bloki godzinowe z opóźnieniem — do ~3 h to norma, nie awaria. Cel
optymalizacji kampanii `[VID]` i `[FOTO]` to ViewContent, więc kampanie mają się na
czym uczyć niezależnie od tego, ile ofert otworzyli ludzie z samych reklam.

### UTM-y na kreacjach Meta — założone 04.09 wieczorem

**Stan aktualny: 6 z 6 żywych reklam ma `url_tags`**, checki świecą 12/12 na zielono.
Ciąg: `utm_source=facebook&utm_medium=paid_social&utm_campaign={{campaign.name}}&utm_content={{ad.name}}`
— makra Mety podstawiają nazwę kampanii i reklamy przy kliknięciu, więc jeden ciąg
obsługuje wszystko, co dojdzie później.

Historia i mechanika poniżej — przydają się przy każdej kolejnej kreacji.

Stan przed zmianą: **6 z 6 żywych reklam bez `url_tags`**. GA4 widzi ten ruch jako
`l.facebook.com / referral` i `lm.facebook.com / referral` — bez kampanii, bez podziału
`[VID]` / `[FOTO]`, bez szansy przypisania leada do reklamy.

Cztery reklamy prowadzą na własne adresy (`…/samochody/<marka>/<model>/#oferty`), dwie
`[POST]` idą przez `object_story_id`, czyli tam, gdzie link w treści posta Andrzeja.

**Zmierzone sondą 04.09** na nieżywej reklamie `[VID] jetour-t2` (ad `120248942515210243`):

| Pytanie | Odpowiedź |
|---|---|
| Da się dopisać `url_tags` do istniejącej kreacji? | **Nie.** Meta: `100/1815573` — edytowalne tylko nazwa, status, etykiety |
| Nowa kreacja z `url_tags` przechodzi? | **Tak**, dla wszystkich sześciu reklam (validate_only `success: true`) |
| Reklamy `[POST]` da się otagować? | **Tak** — kreacja z `object_story_id` + `url_tags` przechodzi |
| Podmiana zachowuje id reklamy? | **Tak**, id i nazwa bez zmian |
| Dostawa stoi na czas podmiany? | **Tak** — reklama przechodzi w `PENDING_REVIEW` |

Dwie pułapki po drodze: `object_story_spec` trzeba przepisać **bez `image_url`** (Meta
odrzuca komplet z `image_hash` błędem `ObjectStorySpecRedundant`), a seria kreacji pod
rząd dostaje `kod 1 — reduce the amount of data` z kroczącego limitu konta; ta sama
kreacja przechodzi po ~20 s.

Sprawdzone też — i **nie działa**, mimo że Meta oddaje `success: true`: zapis `url_tags`
prosto na reklamie (`POST /{ad_id}`), zapis z tym samym `creative_id` plus `url_tags`,
zapis na zestawie. Pole nie istnieje na reklamie, zestawie, kampanii ani koncie
(`(#100) nonexisting field`) — żyje wyłącznie na kreacji i tylko przy jej tworzeniu.
Fałszywy `success` na nieznanym polu to ten sam wzorzec, co `customaudiences`.

Narzędzie: `scripts/social/utm_kreacje.py` (`--lista`, `--sonda`, `--podmien`). Bez
`--podmien` nic nie zmienia. Odstęp 35 s między reklamami jest konieczny: konto puszcza
**jeden zapis na 30 s** (kod `613`).

**Wynik podmiany 04.09, zmierzony przed i po** (snapshoty w `tmp/utm-przed-*.json`):

| Co | Wynik |
|---|---|
| id reklamy | bez zmian, 6 z 6 |
| zestaw i kampania | bez zmian, 6 z 6 |
| kreacja | nowa, 6 z 6, z `url_tags` |
| dostawa w trakcie | **nie stanęła** — `[VID] leopard-5-czarny` 240 → 248 wyświetleń, `[POST] Mazda` 51 → 65 |
| statusy po ~5 min | 5 × `ACTIVE`, 1 × `PENDING_REVIEW`, zero `DISAPPROVED` |

Czyli przerwa w dostawie okazała się teoretyczna: reklamy dowoziły przez cały czas
weryfikacji nowej kreacji.

**Zmiana stanu żywych reklam była jednorazową zgodą Janka na ten konkretny ruch.**
Nie jest precedensem — patrz memory `feedback_nie_wylaczaj_zywych_reklam_sam`.

Reklama `[VID] jetour-t2` (nieżywa, użyta do sondy) też ma kreację z UTM-ami i zostaje
`PAUSED` — po ewentualnym włączeniu będzie od razu otagowana.

### Konwersje Ads ≠ zdarzenia GA4

Osobny temat, opisany w `docs/ads/mapa-kampanii.md`. Konto Ads liczy konwersje
kontaktowe (telefon, WhatsApp, formularz) — nie subskrypcje YouTube, patrz memory
`reference_ads_konwersje_tylko_kontakt`.

## 4. Narzędzia na żądanie (poza cronem)

```
python3 scripts/ads-recheck.py                     # pełny recheck Google Ads + strażnik landingów
python3 scripts/social/recheck_start.py            # recheck reklam Meta po starcie
python3 scripts/social/mockup_kampanii.py --aktywne  # makieta kreacji
python3 scripts/ads-live-collector.py --stdout     # podgląd danych Ads bez zapisu
python3 scripts/social/meta_live_collector.py --stdout
```

## 5. Czego ta warstwa NIE robi

- Nie włącza i nie wyłącza reklam. Wszystko tu jest read-only; zmiana stanu żywej
  kampanii to zawsze decyzja Janka.
- Nie liczy pieniędzy zarobionych — mierzy dostawę, ruch i kontakt. Domknięcie
  (zamówienie, depozyt) siedzi w bazie zamówień, nie tutaj.
- Nie zastępuje `docs/ads/mapa-kampanii.md` — mapa mówi, po co jest która kampania;
  ta warstwa mówi, czy w ogóle działa.
