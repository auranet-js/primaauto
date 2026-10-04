# 2026-10-04 — [DSA] ustawiony pod profil klienta (GA4 wszystkie kanały + zamówienia)

**Status:** wdrożone 04.10 21:12
**Koryguje:** `2026-07-16-dsa-feed-na-oferty.md` (zakres feedu) i segmentację z 12.07 (`docs/ads/segmentacja-2026-07-12.md`, wiek w [DSA]).
**Nie zmienia:** reguły „feedu lepkiego” z `2026-07-17-dsa-feed-lepki.md`. Żywego wpisu nadal nie podmieniamy.

## Skąd decyzja

Janek: [DSA] optymalizujemy na podstawie profilu klienta z całej strony, a nie danych z samego [DSA].

- **GA4, wszystkie kanały, 21.04–03.10, liczone na osobach (`totalUsers`, nie `eventCount`):** 738 osób z kontaktem (telefon, WhatsApp, formularz).
- **Zamówienia z bazy:** 65 realnych (bez anulowanych i odrzuconych) oraz 60 anulowanych. 29 realnych ma PESEL. Z PESEL-u liczony jest tylko przedział wieku i płeć, z kodu pocztowego województwo. Wyniki wyłącznie zbiorcze.

| sygnał | GA4: indeks kontaktu | klienci (realne / anulowane) |
|---|---|---|
| 45–54 | 118 | 12 / 3 |
| 65+ | 131 (49 osób) | 3 / 1 |
| 55–64 | 101 | 2 / 3 |
| 35–44 | 71 | 8 / **11** |
| 25–34 | 68 | 3 / 0 |
| podkarpackie / świętokrzyskie / małopolskie | 172 / 163 / 115 | — |
| dni robocze 9–16 vs wieczór / niedziela | 110–149 vs ~75 / 65 | — |

Marki według kontaktów i zakupów: BYD (indeks 150, 14 zakupów), Zeekr (119, 6), Denza (139, 5), Mazda (234, 8), Exeed (166), Lynk &amp; Co (153), Deepal (127). Jetour wszedł na listę z powodu zakupów (3), mimo indeksu 76. Dawny feed był zdominowany przez marki o niskim indeksie (Li Auto 50, Chery 47, Hongqi 80), a marki konwertujące miały w nim 0–1 ofertę.

**Wykluczenie 65+ z 12.07 odwrócone.** Tamta decyzja opierała się na 333 osobach i zerze kontaktów. Na danych do 03.10 grupa 65+ ma indeks 131.

## Co wdrożone

1. **Feed (cron `dsa-offer-feed-refresh.py`, crontab bez zmian):** tylko marki `byd, zeekr, denza, mazda, exeed, lynk-co, deepal, jetour, xpeng`. Każdy model ma mieć co najmniej 2 oferty (najtańsze żywe z rocznika 2025/2026), a każde auto na placu jest w feedzie. Marka spoza listy wypada, nawet gdy oferta żyje (dotyczy także Xiaomi). Stan po wdrożeniu: 81 → **188 ofert**; bieg kontrolny „bez zmian”.
2. **Wiek:** 65+ ×1,15 (wcześniej wykluczony), 45–54 ×1,30, 55–64 ×1,10, 35–44 ×0,80, 25–34 nadal wykluczony.
3. **Harmonogram (7–22 bez zmian):** dni robocze 7–9 ×0,7, 9–17 ×1,2, 17–19 ×1,0, 19–22 ×0,8; sobota ×0,9; niedziela ×0,7.
4. **Region:** świętokrzyskie (20858) ×1,15, obok podkarpackiego ×1,30 i małopolskiego ×1,15.

Skrypty: `scripts/dsa-offer-feed-refresh.py` (cron) i `scripts/dsa-profil-2026-10-04.py --bez-feedu` (stawki). Kampania jest na ręcznym CPC, budżet 15 zł/dz bez zmian.

## Zastrzeżenia i ocena

GA4 zna wiek u ok. 38% osób, a PESEL ma 29 z 65 zamówień. To kierunek, nie pomiar co do procenta. Ocena po 30 dniach wobec 04.09–03.10, kiedy [DSA] dał 454 zł i 2 kontakty. Uwaga: do 04.10 GTM wycinał `gclid`, więc kontakty z okna bazowego mogą być zaniżone.
