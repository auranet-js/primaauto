# 2026-06-06 — Google Ads: przebudowa RSA kampanii Topic pod filtr intentu DIY

## Kontekst

Konto Prima-Auto (9506068500), kampania `[Topic] Import z Chin` (id 23779860638).
Analiza 30 dni: Topic dominuje aukcję na generyczne frazy „import/sprowadzanie aut
z chin" (IS 92%, pozycja #1, CTR 54%), ale konwertuje słabo — **5 konwersji za ~946 zł,
wszystkie typu `click_phone`**. CPC ~2,18 zł jest strukturalny (floor aukcji), bid-down
już raz cofnięty (commit dc064da) bo wypada z aukcji.

Janek postawił cel precyzyjnie: **nie obniżać kosztu klika ani nie tracić pozycji #1,
tylko odsiać klikających po samą informację (DIY — ludzie, którzy chcą sami sprowadzić
auto), utrzymując impresje na top 1.**

Ustalenie kluczowe: frazy Topic to **nierozdzielalny string** — w „import aut z chin"
siedzi i klient, i DIY-owiec. Negatywem/bidem ich nie rozdzielisz bez utraty impresji.
Jedyne narzędzie, które zachowuje pozycję #1 a odsiewa DIY, to **przekaz reklamy
(autoselekcja kliknięcia)**: reklama jawnie „płatna usługa, my robimy całość" zniechęca
kogoś, kto szuka poradnika „jak zrobić to samemu".

Stan techniczny: w Topic żyje **tylko jedna grupa** — „Import aut z Chin" (id 195903580996,
reklama R5 id 806715493106, serwująca od 24.04). Grupa „Chińskie EV i PHEV" jest REMOVED
od 30+ dni (0/0/0) — pominięta. Asset performance RSA = lifetime PENDING (28 wyśw./dzień
na 15 nagłówków → Google nigdy nie oceni per-asset), więc efekt mierzymy na poziomie
kampanii, nie per nagłówek.

## Decyzje

1. **Wymiana RSA R5** (immutable → create + remove). Nowa reklama id 811967380201,
   stara 806715493106 REMOVED. finalUrl `/`, ścieżka `/auta-z-chin/import` — bez zmian.

2. **7 zmian nagłówków/pinningu + 1 opis:**
   - **Odpięcie H2** z pozycji 2 (zostaje H1 „Import Aut z Chin" pin P1 dla trafności/QS).
     Powód: H1+H2 zabetonowane zajmowały 2 z ~3 widocznych pozycji → filtry lądowały
     wyłącznie na pozycji 3 (ucinanej). Po odpięciu filtr może wejść na P2.
   - **Filtry DIY** (nowe): H3 „Wybierz. Zamów. Odbierz", H5 „Ty wybierasz. My sprowadzamy",
     H8 „Gotowe do Rejestracji w PL" (rejestracja/homologacja = to, czego DIY-owiec się boi).
   - **Marki import-only** (nowe): H6 „Denza, Zeekr, Voyah, AITO", H13 „Leopard, Geely,
     iCAR, Xiaomi". Świadomie NIE marki dostępne w salonach PL (BYD/XPeng/Hongqi/Chery) —
     reklama importu marki z salonu PL to słaby argument; marki spoza PL bronią sensu importu.
   - **CTA konwersji** (nowy): H12 „Wyceń Import w 24h" — pod jedyny działający kanał
     konwersji (telefon, 5/5 `click_phone`); wcześniej R5 nie miało CTA kontaktowego.
   - **Opis D3** → „Auta na placu, auta w drodze i 4000 ofert do sprowadzenia z Chin —
     import załatwiamy my." (trzy poziomy dostępności + filtr w jednym zdaniu; zastąpił
     „Bez salonu, bez wizyty…"). Bez pinu, do puli.

3. **Sitelink galerii** (campaign-level Topic): „Zobacz sprowadzone auta" → /klienci/
   (asset 369875725272). Social proof = dowód realizacji, sygnał wykonawcy usługi, nie
   poradnika. Galeria jako sitelink (własny URL), NIE jako nagłówek (nagłówek prowadzi
   na finalUrl reklamy = mismatch oczekiwań).

4. **Świadomie NIE zmieniane:** bid (2,18 zł), budżet, status, keywordy, finalUrl `/`,
   landing. To wyłącznie zmiana copy → impresje #1 zostają, działa autoselekcja kliknięcia.

## Konsekwencje

- **Test hipotezy:** jeśli filtr działa, za 7–14 dni zobaczymy **spadek CTR przy
  utrzymanych impresjach** (mniej, ale trafniejszych klików), oraz stabilny/lepszy
  koszt-per-`click_phone`. Wzrost CTR lub spadek impresji = hipoteza nietrafiona.
- **Ryzyko QS:** minimalne — keyword-match nadal w H1 (pin P1), H4, D1, D2; ścieżka
  trzyma keyword w URL. Poświęcone sloty to były duplikaty frazy.
- **Approval:** nowa RSA na starcie `UNKNOWN` (review Google). Interpunkcja „Wybierz.
  Zamów. Odbierz" i wielkości liter bywają czepiane — kontrola ~24h.
- **Pozostaje nieobsłużone:** finalUrl R5 = homepage `/` (nie landing importu) —
  potencjalnie słabe, ale poza zakresem tej zmiany (osobny workstream landingu).

## Artefakty

- Skrypty (gitignored, `tmp/`): `gads_topic_rsa_2026_06_06.py`, `gads_topic_sitelink_2026_06_06.py`
- Backup: `~/backups/primaauto/2026-06-06-ads-topic-rsa/R5-backup.json`
- Reguła operacyjna utrwalona: memory `feedback_verify_status_and_activity_before_work`
  (sprawdzaj status łańcucha + ruch ostatnich 7–30 dni PRZED pracą nad obiektem Ads —
  geneza: w tej sesji błędnie potraktowałem REMOVED grupę EV jako aktywną).
