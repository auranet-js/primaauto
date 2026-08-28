# DSA i klucz aplikacji — pomiary 28.08 wieczorem

Sesja z Jankiem na zdalnym pulpicie u klienta. Wszystko poniżej z dowodu API.

## Bloker reklam — NIEROZSTRZYGNIĘTY

Sonda odbija cały czas, także po tym, jak Ruslan przeszedł weryfikację na koncie
reklamowym („Weryfikacja powiodła się", zrzut o 16:4x).

Kluczowy pomiar, jeden payload, jedna chwila:

    PL → 3858196    DE → 3858196
    GB → przechodzi  US → przechodzi  CH → przechodzi

Surowy błąd wskazuje pole: `blame_field_specs: [["dsa_beneficiary"]]`, `error_subcode 3858196`.
**Wartość tego pola nie ma znaczenia** — sprawdzone sześć wariantów (nazwa `PRIMA AUTO RUSLAN
PRIMA` dokładnie jak w panelu, identyfikatory każdej z czterech udanych weryfikacji,
identyfikator portfolio `1486783363131026`, brak pola). Wszystkie odbite identycznie.
To wyklucza winę payloadu: ten sam payload przechodzi na GB.

Stan po stronie klienta (zrzuty):
- konto reklamowe Prima-Auto: 4 weryfikacje podmiotu, 3 udane, 1 nieudana (`1396975018958914`),
  plus wiersz „Zweryfikuj siebie albo organizację" — **przeklikany 28.08, zakończony sukcesem**
- portfolio firmowe: 2 udane (`1554364722803885`, `1486783363131026`), plus ten sam wiersz
  „Zweryfikuj siebie albo organizację" **z aktywnym przyciskiem „Rozpocznij weryfikację"** —
  NIE przeklikany, klient wyjechał
- `act_*?fields=default_dsa_beneficiary,default_dsa_payor` → oba `PRIMA AUTO RUSLAN PRIMA`
  (ustawione dziś, wcześniej puste)
- `act_*/dsa_recommendations` → `{"data": []}` — Meta nie oddaje żadnego dopuszczalnego
  beneficjenta dla tego konta

**Hipoteza, NIEPOTWIERDZONA:** brakuje weryfikacji na poziomie portfolio. Wynika z tego,
że wiersz ma jeszcze przycisk — nie z pomiaru. Druga możliwość to propagacja po stronie
Mety (od zakończenia weryfikacji na koncie do ostatniej sondy minęło ~40 min).

**Test rozstrzygający, do wykonania przez Ruslana:** utworzyć w interfejsie zestaw reklam
kierowany na Polskę, bez publikowania. Przepuści → problem jest po naszej stronie i szukamy
różnicy między payloadem przeglądarki a naszym. Zatrzyma → brakuje weryfikacji.

## Klucz aplikacji — jest, ale nie załatwił scope'ów

Ruslan przekazał sekret appki „Prima-Auto API" `1533997951805022`.
Leży w `~/secrets/meta/app-primaauto.json` (600, poza repo).

Działa: `debug_token` znów odpowiada (dotąd odbijał, bo `~/secrets/meta/app.json` trzyma
sekret starej, zablokowanej appki „Auranet API" `1012248818452132`).
`POST /{su}/access_tokens` uwierzytelnia się i przyjmuje żądanie — tokeny SU generujemy sami.
Narzędzie: `scripts/social/token_su_generuj.py`.

**Czego nie załatwił:**

    (#100) Invalid Scopes: read_insights, pages_read_user_content

Appka **nie ma tych uprawnień wśród dostępnych**. Żaden token ich nie poniesie — także token
wygenerowany ręcznie przez Ruslana w Business Settings, więc tamta ścieżka z pisma v6 też by
nie zadziałała. Włączenie idzie przez panel aplikacji (Weryfikacja aplikacji → Uprawnienia
i funkcje), czyli akcję w dashboardzie, do której sekret nie daje wstępu.

Nasz System User `122094804249428678` **jest administratorem appki** (obok `2075524909999950`),
ale System User nie loguje się do dashboardu. Janka nie ma w portfolio (`business_users`:
jeden wpis `prima_auto.pl`, `pending_users` puste), a jego adres nie jest kontem Facebooka —
droga „dodaj Janka jako administratora appki" jest zamknięta.

**Decyzja Janka 28.08: te dwa uprawnienia odpuszczamy.** Nie są blokerem — wyniki płatne
widzimy przez `ads_read`. Tracimy preselekcję postów przed promowaniem; wybór idzie po
historii tego, co chwytało, nie po wykresie zasięgu. Koszt: osiem rund do klienta w jeden
wieczór, przy zerowym wpływie na uruchomienie reklam.

## Wniosek procesowy

Prośby do klienta idą **całą ścieżką sprawdzoną do końca**, nie po jednym kliknięciu.
Dzisiejsza sekwencja (weryfikacja → domyślny reklamodawca → portfolio → klucz → panel
uprawnień) to pięć osobnych próśb, z których każda była zapowiadana jako ostatnia,
a wszystkie razem nie zdjęły blokera.
