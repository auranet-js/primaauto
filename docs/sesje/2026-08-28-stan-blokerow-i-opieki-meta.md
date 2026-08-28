# Stan blokerów Mety i opieki nad reklamami — pomiar 28.08 wieczorem

Odpowiedź na prompt `2026-08-29-PROMPT-blokery-i-opieka-meta.md`. Wszystko poniżej
z dowodu API, nie z notatek. Sesja była **wyłącznie odczytowa** — nic nie powstało
na koncie klienta, kampania nadal bez zestawu reklam.

---

## Blokery — wszystkie cztery stoją

| Poz. | Stan | Dowód |
|---|---|---|
| R5 DSA | **STOI** | `dsa_status.py`: PL z `dsa_*` → `100/3858196`, PL bez → `100/3858081`, **US przechodzi** |
| R6 domena | **BRAK** | brak `facebook-domain-verification` w HTML; jedyny TXT to `v=spf1 a mx include:spf.hostido.pl -all` |
| R7 scope'y | **BRAK** | `debug_token`: 11 scope'ów, bez `read_insights` i `pages_read_user_content` |
| R1 listy z pliku | **BRAK** | `act_*?fields=tos_accepted` → `{"web_custom_audience_tos": 1}` — brak `custom_audience_tos` |
| R8 kontakty | **BRAK** | `{page}/?fields=leadgen_tos_accepted` page tokenem → `false` |

Konto zdrowe poza tym: `account_status 1`, `disable_reason 0`, PLN, portfolio `Prima Auto`.

**R1 da się sprawdzić bez sondy.** `tos_accepted` na koncie reklamowym daje ten sam wniosek
co `200/1870090`, ale **nie tworzy grupy** — w przeciwieństwie do `POST /act_*/customaudiences`,
który ignoruje `validate_only`. Używaj tej drogi.

**Memory `reference-token-system-user-scopes-dynamiczne` obowiązuje, ale jej warunek
u nas nie zaszedł.** Scope'y dziedziczą się na stare tokeny dopiero **gdy administrator
portfolio wygeneruje nowy token** — od 27.08 nikt tego nie robił, więc na dysku i w API
wciąż stoi ten sam komplet 11. Wniosek: R7 **nie jest do naprawienia po naszej stronie**,
wymaga prośby do Ruslana o regenerację z `read_insights` i `pages_read_user_content`.
Niezależny pomiar na poście
Strony z 27.08 (page tokenem): `post_impressions` / `post_impressions_unique` /
`post_engaged_users` → `#100 not a valid insights metric`; `post_clicks` → `{"data": []}`;
odczyt `reactions`/`comments` → `#10 requires 'pages_read_user_content'`.
Czyli brak wyników organicznych jest realny, nie kosmetyczny.

---

## Opieka — co żyje

- **Piksel `1634147041766916`** — 7 dni: 18 091 zdarzeń (PageView 14 540, ViewContent 3 507,
  Contact 40, Lead 4), 2,1–2,9 tys./dobę. Wpięty w `external_event_sources` katalogu.
  Proporcja warta pamiętania: **40 kontaktów i 4 leady na 14,5 tys. odsłon**.
- **CAPI** — `capi_send.sh --project primaauto PageView TEST_PRIMA_2608` → `events_received: 1`.
- **Katalog `2936723456671912`** — 3 113 pozycji, feed dobowy 06:30 Europe/Warsaw,
  pobranie 28.08 06:31 z `error_count 0` i 3 113/3 113 zapisanych. Pięć zestawów
  (Wszystkie 3 113 · Do 200 tys. 1 857 · SUV-y 1 746 · Elektryczne 1 341 · Hybrydy 1 271).
- **Osiem grup odbiorców**, wszystkie `gotowa do użytku`, zero spoza planu.
  Pięć z piksela ma `approximate_count_lower_bound = 20` — najcieńsze wiadro w zestawie.
- **Kampania `[VID]` `120248809387930243`** — `PAUSED`, `OUTCOME_LEADS`, bez budżetu.
  Kreacja `4545681435750921` `ACTIVE`. **0 zestawów reklam, 0 reklam** — tak zostawia to R5.
- **Rotatory sprawdzone biegiem.** `rotator_postow.py` czyta 10 postów Andrzeja i sam
  raportuje brak `read_insights`. `rotator_wideo.py` widzi 10 materiałów, wszystkie
  z landingiem 200; gotowe od ręki: `leopard-5-niebieski`, `exeed-vx`, `jetour-t2`,
  pozostałe siedem do remuksu faststart. Żaden jeszcze nie w Mecie.

---

## Czego nie mamy

- **Wyników organicznych** (R7) — rotacja `[POST]` idzie na regule z bazy, nie na zasięgu.
- **Sygnału „na placu" w feedzie.** Zmierzone na CSV: **wszystkie 3 113 wierszy** mają
  `address.city = Rzeszów` i `availability = available` (`state_of_vehicle`: 3 069 USED / 44 NEW).
  W bazie tymczasem **18 × `on_lot`, 27 × `in_transit`, 1 × `reserved`**. Osiemnaście aut
  stojących w Polsce jest w katalogu Mety nieodróżnialnych od trzech tysięcy ofert z Chin —
  a to najmocniejszy argument sprzedażowy, jaki mamy.
  Nasza robota w `scripts/build-meta-vehicle-feed.php`, **nie prośba do Ruslana**.
- **Czystej biblioteki reklam.** Potwierdzone do skasowania: wideo `4040666229569687`
  („[TEST] BYD Shark 6…"), `2853400968357319` („TEST Auranet — do skasowania"),
  obrazy `2410ca18ffde1dc6` (`test-zdjecie.jpg`), `1828a665ec14cbf1` (`m.jpg`).
  **Nierozstrzygnięte:** dwa obrazy `untitled` z 26 i 28.08 (`e0672a35900e392f`,
  `335a56499c433314`) — nie wiadomo, czy śmieć testowy, czy materiał kreacji Leoparda.

---

## Gdy Ruslan zgłosi, że zrobił

1. **Najpierw pomiar, potem wnioski** — `python3 scripts/social/dsa_status.py`.
   Dopóki PL nie przechodzi, reszta jest bez znaczenia dla uruchomienia.
2. Po padnięciu R5 domknięcie kampanii to **jedno** wywołanie:
   `python3 scripts/social/meta_kampania_wideo.py --domknij`
   (dokłada zestaw reklam z wykluczeniami i reklamę do `120248809387930243`,
   kreacja `4545681435750921`, wszystko `PAUSED`).
   **`--buduj` robi NOWĄ kampanię — nie używaj do dokładania.**
3. **Włączenie z budżetem = osobna zgoda Janka na konkretną datę.**
   Patrz `docs/meta/plan-kampanii.md` sekcja 9 i memory
   `feedback-publikacja-zewnetrzna-wymaga-osobnej-zgody`.
4. R7 po regeneracji tokenu przez Ruslana sprawdzaj `debug_token` **i** pomiarem na realnym
   poście. Pliku tokenu **nie musisz wymieniać** — stary odziedziczy scope'y (memory
   `reference-token-system-user-scopes-dynamiczne`); zweryfikuj to jednak pomiarem, nie teorią.
