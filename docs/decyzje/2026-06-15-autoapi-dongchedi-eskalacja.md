# ADR 2026-06-15 — Eskalacja do dostawcy auto-api.com (feed dongchedi nie działa)

## Kontekst

Feed dongchedi (auto-api.com → plugin `asiaauto-sync`) ponownie nie dostarcza nowych ofert. Analiza logu `logs/asiaauto-sync.log` 2026-06-15:

- Cron działa, `change_id` rośnie, brak błędów krytycznych — ale `getChanges` zwraca prawie wyłącznie `changed`/`skipped`, **`+0 added`**. Crawler dongchedi po stronie dostawcy nie wpuszcza nowych ofert (ten sam cichy failure co [[../../../.claude/projects/-home-host476470-projekty-primaauto/memory/sync-cicho-stan-feed-dostawcy-bez-event-w-added-2026-05-25]] w maju).

Realny dzienny przyrost nowych aut:

| Okres | Nowe auta/dobę | Stan |
|---|---|---|
| 1–23 maja | 130–370 | sprawny |
| 24–31 maja | 2–8 | pad #1 (naprawiony ~1.06) |
| 1–2 czerwca | 101, 226 | recovery |
| 3–7 czerwca | 2–15 | pad #2 |
| 8–9 czerwca | 170, 97 | krótki recovery |
| 10–15 czerwca (trwa) | 0–8 (11.06 = 0) | pad #3 |

Stabilnie tylko do 23 maja; od 24 maja seria padów. Dostawca sam proponuje migrację na **Che168** (gorsza jakość danych i parametryzacja — dlatego od początku wybraliśmy dongchedi).

## Fakty handlowe (korespondencja + faktura)

- Płaci **Auranet / Jan Schenk** (NIE klient Ruslan); NIP PL8732896856.
- Cennik dostawcy: Dongchedi 170 USD/mc. **Wynegocjowano 100 USD/mc** (zgoda 17.03.2026), start 18.03.
- Cykl 18. dnia. Mc1–mc2 wg ~100 USD. **Ostatnia faktura EST-257244 (18.05, 145 EUR ~170 USD, okres 18.05–18.06)** = cichy powrót do cennika — w miesiącu, gdy feed padł.
- Procesor płatności: EasyStaff LP (UK, SL036041).

## Decyzja

Wysłać do dostawcy reklamację (w imieniu Auranet) z trzema osiami:

1. **Status/przyczyna + prawdopodobieństwo powrotu** dongchedi do stabilnej pracy (pytanie otwarte).
2. **Che168 warunkowo**: jeśli to jedyna stabilna ścieżka — żądać (a) gwarancji/SLA oraz (b) **min. 50% zniżki na Che168 na fazę re-developmentu** (migracja = realna nowa praca integracyjna). Sformułowane warunkowo, by nie sygnalizować zgody na migrację i nie osłabiać nacisku „najpierw naprawcie dongchedi".
3. **Kompensata za downtime**: pro-rata z faktury EST-257244 — ~22/31 dni feed martwy → **~100 EUR** (145 EUR × 22/31 ≈ 103 EUR) do zwrotu lub creditu.

Mail jest **draftem** — wysyłkę do dostawcy wykonuje Janek (autoryzacja: maile do podmiotów zewnętrznych nie wychodzą automatycznie). Draft: `tmp/autoapi-dongchedi-mail-2026-06-15.html` (v3), dostarczony Jankowi przez `send-to-jan`.

## Konsekwencje / otwarte

- Decyzja strategiczna **dongchedi vs Che168** czeka na odpowiedź dostawcy (prawdopodobieństwo + SLA).
- **Luka watchdog** nadal otwarta: brak alertu „0 added przez >X h" — awaria jest cicha. Do rozważenia osobny task (mail do js@auranet.com.pl, NIGDY do klienta).
- Workaround na czas awarii: import ręczny z UI (`force=true`, omija filtry; guard `isEmptyShell` chroni przed śmieciem) — patrz [[../../../.claude/projects/-home-host476470-projekty-primaauto/memory/Dongchedi API (auto-api.com) quirks]].
