# Recheck Meta po restarcie konta — porównanie okien 15–17.09 vs 22–24.09

> Przypomnienie na **25.09.2026 rano**. Projekt: `primaauto`.
> Charakter: **tylko odczyt i raport.** Nic nie zmieniamy — status reklam, budżety i targetowanie
> to decyzja Janka ([[feedback_nie_wylaczaj_zywych_reklam_sam]]).

## Po co to

Przebudowa Meta weszła **15.09** (`[KAT]` + `[FOTO]` na Contact + `[RMKT]`, 65 zł/dz,
mapa: `docs/meta/plan-kampanii.md`). Zdążyła przepracować trzy doby i **18.09 o 12:00 konto stanęło**
na limicie wydatków — postój trwał do **21.09 do 17:00**. Recheck planowany na 17.09 opisał tylko
dobę 16.09, a ten z 20.09 był diagnozą awarii, nie oceną kreacji.

25.09 mamy pierwszy uczciwy materiał: **22, 23 i 24.09 to trzy pełne doby po restarcie**,
równoważne z 15–17.09 sprzed postoju. Pytanie do rozstrzygnięcia brzmi: czy przebudowa
realnie poprawia koszt wejścia i kontakty, czy tylko odbudowała stan sprzed niej.

## Co zrobić

Wykonaj **część 2** promptu `docs/sesje/2026-09-16-PROMPT-recheck-meta-i-telefony.md` (punkty 2a–2e)
na oknie **22–24.09**, z trzema różnicami wobec tamtego przebiegu:

1. **Pierwsza tabela = per reklama** (wydane, wyświetlenia, CTR w link, kliknięcia, zł/klik, LPV Meta,
   sesje GA4, zł/sesja, zaangażowanie, ViewContent, Contact) — kolejność Janka, nie statystyka
   ([[feedback_audyt_kampanii_od_reklam_nie_od_statystyki]]).
2. **Druga tabela = te same wiersze dla 15–17.09** (baseline sprzed postoju, jedno zapytanie
   z `time_range`) i kolumna różnicy. Baseline dzienny, już zmierzony:
   15.09 — 70,81 zł / 9 527 wyśw. / 763 klik · 16.09 — 44,06 zł / 5 675 / 478 · 17.09 — 54,42 zł / 5 624 / 518.
   Razem z ułamkiem 18.09: **185,84 zł i 1 912 kliknięć**.
3. **Trzecia = propozycja wymiany kreacji** z uzasadnieniem liczbą. Zgłoszenie, nie wykonanie.

Sprawdź przy okazji, czy `[FOTO]` (`Karuzele PL M 30-60 — Contact`) nadal się dławi — 16.09 wydał
3,28 zł z 15 zł budżetu (22%). To był jedyny zgrzyt poprzedniego rechecku.

## Czego NIE robić

- Nie licz „ile zostało do limitu" ani daty sufitu ([[feedback_meta_limit_1000_nie_jest_horyzontem]]).
  Limit po restarcie wynosi 1 800 zł, licznik wyzerowany 21.09 — to tło, nie horyzont decyzji.
- Nie traktuj postoju 18–21.09 jako danych. Doby 18, 19, 20 i ułamek 21.09 **wypadają z porównania**.
- Nie wyłączaj, nie włączaj i nie zmieniaj budżetów.

## Jak wygląda „zrobione"

Raport w `docs/sesje/2026-09-25-recheck-meta-po-restarcie.md`: wniosek w jednym zdaniu, trzy tabele,
trend dzienny, krótki blok pomiaru TAK/NIE z dowodem. Treść wystawiona na auratest + wklejona do czatu.

## Czym mierzyć

- **Meta:** `bash ~/secrets/meta/meta_call.sh --project primaauto-2026 "/act_1038563008906171/insights?..."`
  (konto `primaauto-2026`, nie `primaauto` — tamto jest martwe).
- **GA4:** `scripts/ga4_query.py`, property `534017542`, źródło `facebook / paid_social`,
  rozbicie po `utm_content`. Kontakty = `click_phone` + `click_whatsapp`.
- **Uwaga na odczyt:** LPV Meta zawyża wobec sesji GA4 — kosztem operacyjnym jest zł/sesja GA4.
  Piksel do 15.09 siedział tylko na `/oferta/` ([[reference_piksel_meta_tylko_na_ofertach]]),
  więc ViewContent sprzed przebudowy nie jest porównywalny z dzisiejszym.

## Kontekst historyczny

- Diagnoza postoju: `docs/sesje/2026-09-20-recheck-meta-dostawa-stoi.md` + [[reference_meta_dostawa_stoi_mimo_active]]
- Poprzedni recheck (doba 16.09): `docs/sesje/2026-09-17-recheck-meta-po-przebudowie.md`
- Mapa kampanii: `docs/meta/plan-kampanii.md`
