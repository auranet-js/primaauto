# ADR 2026-08-19 — faktura auto-api 146 EUR: płacimy, downtime zapisany na przyszłość

## Kontekst

19.08 o 13:38 dostawca (`access@auto-api.com`) napisał: *„We have restored the Che168 API; everything is now working as usual"* i przypomniał o płatności za kolejny okres (15.08 – 17.09). W załączniku **faktura EST-281435 z 19.08.2026 na 146 EUR** (VAT 0, „Website support services / Database support for 1 month", wystawca EasyStaff LP, płatnik AURANET Jan Schenk, NIP PL8732896856).

Zgłoszenie Janka: kwota nie odpowiada ustaleniom, opis nie mówi, za co płacimy, a przypomnienie o płatności dotyczy faktury otrzymanej tego samego dnia.

### Historia stawek (z korespondencji + faktur)

| Data | Kwota | Uwagi |
|---|---|---|
| 16.03 | cennik: 170 USD jedno źródło / 300 USD oba | oferta wyjściowa |
| 17.03 | **100 USD/mc** | rabat na fazę deweloperską, start 18.03 |
| 18.04 | 140 EUR → **100 USD** | skorygowane po proteście Janka |
| 18.05 | **145 EUR** (EST-257244) | cichy powrót do pełnej stawki |
| 18.06 | 145 EUR | |
| 15.07 | **87 EUR** (EST-272067) | rabat dev przedłużony na dwa źródła; dostawca: *„The discount will be valid during the development period. We are also configuring Dongchedi."* |
| **19.08** | **146 EUR** (EST-281435) | +68% wobec lipca, bez zapowiedzi i bez informacji o zakończeniu okresu dev |

To trzeci raz, gdy kwota rusza się bez ustalenia (kwiecień, maj, sierpień).

### Downtime Che168 — pomiar na produkcji

Strumień `/changes` che168 stanął **12.08 o 12:04 (+03:00)** i ruszył **rano 19.08** — około 6,5 dnia. Przyrost `change_id` na dobę w tym okresie: 11 / 0 / 91 / 44 / 21 / 37 (13–18.08), wobec 13–28 tys. przed awarią. Restart potwierdzony: 19.08 kursor 10505575 → 10521986 (delta 16 411), `+46 added`.

Import u nas z kanału che168: **1 oferta łącznie w dniach 13–18.08**, wobec 80–150 na dobę w pierwszym tygodniu sierpnia (3–7.08: 154 / 83 / 92 / 146 / 81).

Pytanie o kompensatę z maila 13.08 (*„Please confirm how this downtime will be compensated before the next invoice is issued"*) **pozostało bez odpowiedzi** — faktura przyszła bez korekty. Pytanie o crawler Dongchedi również pominięte.

## Decyzja — płacimy, bez eskalacji

Decyzja Janka 19.08: **faktura opłacona kartą** (ta sama co przy poprzednich fakturach, wystawiona na AURANET Jan Schenk — dostawca wymaga płatności od podmiotu z faktury, KYC).

Uzasadnienie: 146 EUR to mniej więcej cennikowa stawka **jednego** źródła (170 USD), a realnie korzystamy z **dwóch** — dongchedi dowozi `added` od 16.08 (71 / 145 / 101 na 16–18.08, po naszym wznowieniu importu, patrz ADR 2026-08-16), che168 wrócił 19.08. Przy cenniku 300 USD za oba jest to układ korzystny; awantura o 59 EUR różnicy wobec lipca ryzykuje utratę tej asymetrii.

### Świadomie NIE pytamy, co obejmuje kwota

Pierwotny draft zawierał pytanie „Che168 only, Dongchedi only, or both?". **Wycięte.** Odpowiedź mogłaby uruchomić doliczenie drugiego źródła do 300 USD. Dziś nie ma nic na piśmie, że płacimy za jeden kanał — milczenie działa na naszą korzyść. Do tematu wracamy dopiero, gdyby to dostawca podniósł stawkę.

### Co poszło w mailu

Wysłane przez Janka **19.08 o 17:05** (DW na `claude@auratest.pl`, mail [262]) w wątku „Re: API inquiry Dongchedi + Che168 - client project evaluation":

1. Potwierdzenie restartu Che168 z naszymi liczbami (kursor 10505575 → 10521986).
2. Faktura opłacona kartą, tą samą co zawsze, z podmiotu wskazanego na fakturze.
3. Płacimy pełną stawkę — nadzieja, że kłopoty są za nami i usługa pojedzie równo przez cały okres (wersja miękka, na życzenie Janka; twardsze „we count on a fully functional service" odrzucone).
4. **Zastrzeżenie na piśmie:** płacimy z góry, więc traktujemy okres jako okres działającej usługi; monitorujemy oba kanały (wolumen zdarzeń, ruch kursora, import na dobę), zgłaszamy przerwy od razu i **odnotowujemy dni niedostępności do rozliczenia przy kolejnej fakturze**.
5. Jawne zaznaczenie, że to nie jest reklamacja bieżącej faktury.

Treść: `https://auratest.pl/fe4f58fec53ctmp/primaauto-autoapi-faktura-est281435-2026-08-19-v5.md`.

## Konsekwencje

- **Zapis „rozliczymy w kolejnym okresie" ma moc tylko wtedy, gdy przy następnej awarii faktycznie zejdziemy z kwoty faktury.** Jeśli znów zapłacimy pełne, zastrzeżenie traci wartość.
- Dowody do udokumentowania ewentualnej przerwy: log `wp-content/plugins/asiaauto-sync/logs/asiaauto-sync.log` (delta `change_id` i `+N added` per bieg) oraz `wp7j_posts` per doba per `_asiaauto_source`.
- Kolejny termin płatności: **17.09.2026**. Jeśli kwota znów wzrośnie (np. do ~300 USD za oba źródła), wracamy do pytania o zakres, tym razem z argumentem historii stawek z tego ADR.
- Kosztorys dla Ruslana (`docs/kosztorys/dane/koszty.json`) wymaga dopisania pozycji 15.08–17.09 = 146 EUR; rabat deweloperski wygaszony.

Powiązane: `docs/decyzje/2026-08-13-autoapi-che168-outage-eskalacja.md`, `docs/decyzje/2026-06-15-autoapi-dongchedi-eskalacja.md`, `docs/decyzje/2026-08-16-dongchedi-wznowienie-importu-i-zrownanie-filtrow.md`.
