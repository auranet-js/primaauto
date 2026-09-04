# Meta Ads — prompt kontynuacyjny, stan na 2026-09-04 wieczór

> Sesja: budowa kampanii Meta po zdjęciu blokera DSA. Zbudowane wszystko, uruchomione
> prawie nic, teksty do przepisania od nowa według zasady, którą Janek postawił na końcu.

## Zacznij od tego

**Teksty reklam bierzemy z zasobów Google Ads, dosłownie — nie układamy własnych zdań.**
Polecenie Janka, wieczór 04.09, po tym jak wstawiłem do kreacji własne opisy z liczbami
i cechami aut: *„po to ci kazałem wziąć assety z google ads, bo tam nie ma faktów liczbowych
i fantazji"*. Sprawdzone teksty (konto `9506068500`, 30 dni, konwersja na klik):

| Pole | Tekst | konw/klik | kliki |
|---|---|---:|---:|
| DESCRIPTION | Prima-Auto — bezpośredni importer aut z Chin. Ceny w ogłoszeniach, zamów online. | 3,25% | 727 |
| DESCRIPTION | Aktualne ogłoszenia z Chin — codziennie. Umowa agencyjna, transport, cło. | 2,87% | 650 |
| DESCRIPTION | Bezpośredni Importer samochodów z Chin. Ceny w ogłoszeniach — zamów online w 1 klik. | 1,71% | 351 |
| HEADLINE | Aktualne Oferty z Chin | 4,24% | 440 |
| HEADLINE | Import bezpośredni z Chin | 2,75% | 409 |
| HEADLINE | Zobacz Ofertę Aut z Chin | 4,62% | 173 |

Ostatnie zestawienie zostało przerwane przez Janka słowem „nie" — **nie zakładaj, że wybór
tekstów jest zatwierdzony.** Pokaż propozycję opartą na powyższych zasobach i czekaj.

## Czego NIE robić — błędy tej sesji

1. **Nie wymyślaj cech aut.** Wpisałem „Denza Z9 GT — kombi" (jest sportowy sedan/liftback
   premium, marka Denza z koncernu BYD), „Jetour T2 PHEV" przy serii benzynowej (hybryda to
   osobna seria **T2 C-DM**, 21 szt., od 153 tys.; na sesji stał właśnie C-DM — 1.5TD DHT
   206 KM XWD, 5-osobowy, 2025, na placu w Rzeszowie), „Exeed VX siedmioosobowy" (6 miejsc),
   „Denza N9 siedmioosobowy" (6 miejsc). Cechy są w bazie: `body`, `fuel`, `drive` w meta
   oferty, `seat_count` w `_asiaauto_extra_prep`.
2. **Nigdy słowo „homologacja"** w treści widzianej przez klienta — zawsze „przygotowanie
   do rejestracji" (`feedback_nigdy_slowo_homologacja`). Złamałem to w 10 kreacjach `[VID]`
   i 4 karuzelach. Reklama z tym słowem chodziła przez ~40 minut z zerem wyświetleń.
3. **Nie obiecuj rejestracji.** Strona mówi „przygotowanie do rejestracji, żebyś mógł udać
   się do wydziału komunikacji", umowa zastrzega dodatkowe procedury. My przygotowujemy,
   klient rejestruje.
4. **Nie wsadzaj liczb, które się starzeją** (zapas, liczba ofert) do kreacji — Mety nie da
   się edytować, trzeba budować nową.
5. **Nie wyłączaj i nie włączaj niczego z własnej inicjatywy.** Wyłączyłem dwie chodzące
   reklamy, bo zobaczyłem w nich błąd merytoryczny — Janek tego nie zlecił i słusznie to
   wytknął. Błąd w żywej reklamie zgłaszasz jednym zdaniem i czekasz; decyzja o wyłączeniu
   jest jego, nie twoja. To samo dotyczy budżetów: pytanie „czy mamy 25/15/15?" jest pytaniem
   o stan, nie poleceniem zmiany.
6. **Nie czekaj na pytania Janka.** Cztery razy pod rząd to on podniósł temat (karuzele,
   opisy kart, CPC, strategia). Kładź propozycje sam.

## Stan konta — sprawdzony przez API 04.09 wieczorem

```
act_1038563008906171 · aktywne · karta OK · wydane 0,00 zł z limitu 1 000 zł
DSA zdjęty 02.09, podmiot „PRIMA AUTO RUSLAN PRIMA" (nazwa portfolio jest odrzucana)

[VID] Auta z Chin — nowi odbiorcy       ACTIVE   25 zł/dz · ViewContent · PL 25-65
  10 reklam v2; WŁĄCZONE 2: leopard-5-czarny, z9-gt (teksty v2 — do wymiany, patrz wyżej)
[POST] Promowanie postów — ruch         ACTIVE   10 zł/dz · LINK_CLICKS
  10 reklam z postów Andrzeja; WŁĄCZONE 2: Mazda EZ-6, Denza Z9 DM-i
[FOTO] Zdjęcia z sesji — karuzele       ACTIVE   15 zł/dz · ViewContent
  4 karuzele; WŁĄCZONE 2: „Cała oferta — kadr 1" i „kadr 2" (po 8 kart)
  Opisy kart mają jeszcze błędy (Z9 GT „kombi", T2 „PHEV" i link na serię benzynową,
  VX i N9 „7 osób") — poprawiamy je NA DZIAŁAJĄCYCH reklamach, nie przez pauzowanie.
```

Janek oczekuje docelowo: **po 2 filmy, 2 posty, 2 karuzele włączone, budżety 25 / 15 / 15**.
Na 04.09 wieczorem chodzi **6 reklam: 2 filmy, 2 posty, 2 karuzele** — zgodnie z tym,
czego chciał. Zostaje podniesienie `[POST]` z 10 na 15 zł — **nie ruszaj bez jego słowa**,
zapytał o budżet, a nie zlecił zmianę.

**Poprawki robimy na działających reklamach.** Polecenie Janka 04.09: *„będziemy w kolejnym
wątku poprawiać działające reklamy, a nie co chwilę pauzować"*. Zmiana treści = nowa kreacja
obok, podmiana reklamy w tym samym zestawie, stara do archiwum — dostawa nie staje ani na
chwilę, faza uczenia zestawu zostaje.

Grupy odbiorców: 8 z piksela i zaangażowania + 2 lookalike 1% PL (FB, IG) — wszystkie gotowe.
Wykluczenia w `[VID]` i `[FOTO]`: odwiedzający 180 dni + kontakt 180 dni; w `[POST]` sam kontakt.
`advantage_audience: 0` (Meta nie rozszerza grupy), `location_types`: `recent` zdjęte,
zostaje `home` + `frequently_in` (Meta dopina drugie sama, nie da się rozdzielić przez API).

## Co jest zbudowane i gdzie

| Skrypt | Do czego |
|---|---|
| `scripts/social/buduj_start.py` | `[VID]` i `[POST]` — kampanie, zestawy, reklamy (idempotentny) |
| `scripts/social/buduj_foto.py` | `[FOTO]` — kadry z Dysku → kwadrat 1080 → karuzele |
| `scripts/social/przepisz_teksty.py` | podmiana tekstów `[VID]` na cechy z bazy |
| `scripts/social/mockup_kampanii.py` | makieta wszystkiego na jednej stronie HTML |
| `scripts/social/kontaktowka_sesji.py` | 196 zdjęć z sesji na jednej stronie, do wyboru kadrów |
| `scripts/social/recheck_start.py` | recheck: status weryfikacji, wyświetlenia, koszt |

Makieta: `https://auratest.pl/fe4f58fec53ctmp/primaauto-meta-makieta-2026-09-04.html`
Kontaktówka: `https://auratest.pl/fe4f58fec53ctmp/primaauto-sesje-kontaktowka-2026-09-04.html`

10 filmów z sesji wgranych do Mety (7 przeszło remuks faststart), 27 kadrów wgranych jako
`adimages`. Identyfikatory w `scripts/social/state/`.

## Otwarte, w kolejności

1. **Teksty od nowa z zasobów Ads** — patrz góra. Bez tego nic nie włączamy.
2. **Karuzele do przebudowy** — opisy kart mają błędy, T2 musi wskazywać na `t2-c-dm`
   (od 153 tys.), nie na serię benzynową.
3. **Budżet `[POST]` 10 → 15 zł** — czeka na zgodę.
4. **Grupy z obejrzenia wideo** — API odbija każdy format reguły (`1870049`, tak samo v19/v21/v25),
   dokumentacja tego nie opisuje. Zrobić ręcznie w Menedżerze Reklam; Meta wypełnia wstecz 365 dni.
5. **Remarketing dynamiczny z katalogu** — katalog 2 990 aut, feed dobowy bez błędów, piksel
   wpięty, 7 zestawów produktów (w tym „Na placu w Polsce" i „W drodze do Polski"). Nie zbudowany.
6. **Kampania na WhatsApp** — nietknięta, a to nasz najlepszy kanał kontaktu (43 kontakty/30 dni
   w Ads). Konto ma `WHATSAPP_DESTINATION_ADS`.
7. **`spend_cap` 1 000 zł łącznie** — przy 50 zł dziennie limit pęka po 20 dniach. Decyzja
   Janka i Ruslana, nie nasza.

## Zasady, które obowiązują

- Uruchomienie z budżetem to publikacja — **osobna zgoda na konkretną reklamę i termin**.
- Konto ma kroczący limit wywołań (`17/2446079`) — po serii zapisów odczyty przestają działać
  na kilkanaście minut. Buduj zapytania zbiorczo (jedno `/ads` z zagnieżdżonymi polami zamiast
  pętli po zestawach).
- `/customaudiences` ignoruje `validate_only` — sonda TWORZY grupę.
- Kreacji w Mecie nie da się edytować. Zmiana treści = nowa kreacja + nowa reklama,
  stara do archiwum. Faza uczenia zestawu przy tym nie siada.
