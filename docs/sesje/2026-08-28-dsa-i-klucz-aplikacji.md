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

## Korekta wieczorna — porównanie z Victorini podważa diagnozę

Janek przeszedł tego samego dnia ten sam kreator weryfikacji na koncie **Victorini**.
Sprawdzenie tamtego konta (read-only, `~/secrets/meta/tokens/victorini.txt`):

- `act_2090715591128335` — aktywne zestawy `SHOP-PARAPETY-ALU` (Tarnów +80 km) i
  `SHOP-PODBITKA` (Małopolska), `dsa_beneficiary` = `dsa_payor` = „Victorini sp. z o.o.",
  **1 400 zł / 191 988 wyświetleń w 30 dniach** — reklamy realnie serwują do UE
- `act_*/dsa_recommendations` → **zwraca podmiot**; na PrimaAuto ten sam endpoint zwraca `[]`
- portfolio „Victorini" `645964997316686` → `verification_status: not_verified`,
  a reklamy i tak chodzą (konto należy do innego portfolio, `owner 1103943545901678`,
  poza zasięgiem naszego tokenu). **Weryfikacja firmy ≠ weryfikacja reklamodawcy** —
  potwierdzone na żywym przykładzie.

**Ale wszystkie działające zestawy Victorini powstały w styczniu i kwietniu 2026, żaden
dzisiaj.** Więc „reklamy chodzą" dowodzi, że konto z historią unijnych reklam nadal dowozi
— nie że nowy zestaw utworzony tam dziś by przeszedł walidację. Mój dowód „Victorini
zweryfikowane" jest słabszy, niż go przedstawiłem.

**Sonda nie działa na Victorini.** Cztery konfiguracje na dwóch kampaniach-kontenerach:
`1885760` bez wskazania pola (L1-RUCH-NEW ma budżet na poziomie kampanii) i `2490408`
blame `optimization_goal` (SHOP-PAR-KAM-TAR to OUTCOME_SALES, nie przyjmuje LINK_CLICKS).
Każdy wariant pada **także na US**, więc kontrastu geograficznego tam nie ma i sonda nie
odpowiada na nic. Token ma `ads_management` i `user_tasks: MANAGE` — to nie brak uprawnień.

### Odwołanie tezy

Komunikat Mety „dowiedz się, jak poprosić o weryfikację" traktowałem jako wskazanie
przyczyny. To jest stały tekst przypisany do tego kodu błędu, nie diagnoza. Na tym oparłem
osiem próśb do klienta w jeden wieczór.

**Twarde, bez interpretacji:** PL i DE odrzucone, GB/US/CH przechodzą tym samym payloadem;
`dsa_beneficiary` odrzucane niezależnie od wartości; Meta nie oddaje dla tego konta żadnego
dopuszczalnego beneficjenta, dla Victorini oddaje. **Dlaczego — nie wiadomo.**

**Hipoteza Janka, mocniejsza od mojej:** różnicą między kontami nie musi być weryfikacja,
tylko dziewiczość konta. Victorini ma historię unijnych reklam, PrimaAuto **nie ma żadnej**
— zero wydatku, zero kiedykolwiek utworzonych zestawów. Jeśli tak, ręczne przebicie
pierwszego zestawu przez interfejs nie jest testem tezy, tylko rozwiązaniem.

**Poniedziałek:** Janek na zdalnym pulpicie tworzy zestaw i reklamę ręcznie, kierowanie na
Polskę, status wstrzymany, budżet minimalny, aż do „Opublikuj" (szkic nie przechodzi
walidacji). Przejdzie → potrzebny identyfikator zestawu, odczytamy z API, co wysłała
przeglądarka. Zatrzyma → zrzut ekranu, bo interfejs nazywa brakującą rzecz wprost.
