# Recheck po zmianach z 04.10 — Google Ads + Meta

> Przypomnienie na **11.10.2026 rano**. Projekt: `primaauto`.
> Charakter: **tylko odczyt i raport.** Każda zmiana w kampaniach to decyzja Janka (quiz).

## Co zmieniło się 04.10 (sprawdzamy, czy działa)

**Google Ads** (`docs/ads/mapa-kampanii.md`, dziennik zmian z 04.10; ADR `docs/decyzje/2026-10-04-dsa-profil-klienta.md`):
- [DSA] pod profil klienta: feed z 9 marek (BYD, Zeekr, Denza, Mazda, Exeed, Lynk & Co, Deepal, Jetour, XPeng), co najmniej 2 oferty na model plus auta na placu, łącznie 188 ofert. Wiek: 65+ ×1,15, 45–54 ×1,30, 55–64 ×1,10, 35–44 ×0,80, 25–34 wykluczone. Harmonogram z korektami, świętokrzyskie ×1,15. Wykluczenia: `olx`, `bronco`; Xiaomi poza feedem.
- [DG]: pauza Shark 6. Exeed VX odrzucony, **nie odtwarzamy** (decyzja Janka: nie rozrzedzamy [DG]).
- GTM v16: `gclid`, `wbraid` i `gbraid` przywrócone (od 07.09 były wycinane przez błąd wdrożenia).

**Meta** (`docs/meta/plan-kampanii.md`, sekcja „04.10 — korekty po rechecku”):
- [KAT] dostał wykluczenia odwiedzających 180 dni i kontaktu 180 dni (wcześniej ich brakowało), budżet 35 → 45 zł.
- [RMKT] ma wyłączone rozszerzanie listy, budżet 15 zł.
- [FOTO] zapauzowany. Konto: 60 zł/dz.

## Co zrobić

1. **`gclid` wrócił?** Sesje `google / cpc` w GA4 per dzień od 05.10. Przed błędem było 103–133 dziennie, w czasie błędu 33–41. Sprawdź, czy [RMKT] i [DG] znów mają sesje `google / cpc`, a nie youtube.com / doubleclick. Porównaj kontakty w Ads z kontaktami w GA4.
2. **Cron feedu [DSA]** chodził 07.10 i 10.10 o 06:15? Sprawdź log `~/.claude/dsa-offer-feed.log` (bez `BLAD`) i bieg kontrolny `python3 scripts/dsa-offer-feed-refresh.py` („bez zmian” albo pojedyncze podmiany).
3. **[DSA] po zmianach:** wyświetlenia, kliknięcia, koszt i kontakty za 05–10.10 wobec średniej tygodniowej sprzed zmian (04.09–03.10: 454 zł i 2 kontakty w 30 dni). Pokaż rozkład wydatku według wieku i godzin, żeby było widać, czy korekty działają. Po tygodniu to obserwacja, nie ocena (ocena po 30 dniach).
4. **Meta, tabela per reklama** za 05–10.10 wobec 28.09–03.10: wydane, wejścia, koszt wejścia, sesje GA4, kontakty GA4 (telefon i WhatsApp z UTM), częstotliwość i zasięg. Kluczowe pytania: czy częstotliwość [KAT] spadła po wykluczeniach, czy [RMKT] po wyłączeniu rozszerzania wydaje całe 15 zł, czy [KAT] wydaje 45 zł.
5. Raport do `docs/sesje/2026-10-11-recheck-po-zmianach.md` i na auratest.

## Czego NIE robić

- Żadnych porad dla [Topic] i [Brand], same liczby (memory `feedback_topic_i_brand_nie_ruszac`).
- Nie wznawiaj zamkniętych tematów: zamrożone ceny w tytułach, Messenger na ofercie, Exeed VX w [DG], wiek z 17.09 (memory `feedback_recheck_nie_wznawiaj_zamknietych_decyzji`).
- Nie licz daty ani progu od limitu wydatków Mety.
- Pytania do Janka tylko quizem (AskUserQuestion), z rekomendacją na pierwszym miejscu.
