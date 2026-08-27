# Prima-Auto — co zostało do zrobienia

**Wersja 3 · 26 sierpnia 2026 · Auranet**
*Aktualizacja listy z 25 sierpnia. Wszystko sprawdzone dzisiaj bezpośrednio na koncie.*

---

## Trzy z czterech rzeczy Ruslana są już zrobione

Sprawdziliśmy każdą osobno, nie na słowo:

| | Wczoraj | Dziś | Jak to sprawdziliśmy |
|---|---|---|---|
| **R1** Regulamin grup odbiorców | ⛔ niezaakceptowany | ✅ **zaakceptowany** | konto reklamowe zwraca `web_custom_audience_tos: 1` |
| **R3** Weryfikacja firmy | ⛔ niezweryfikowana | ✅ **zweryfikowana** | portfolio zwraca `verification_status: verified` |
| **R4** Aplikacja w trybie roboczym | ⛔ deweloperski | ✅ **produkcyjny** | utworzyliśmy testową reklamę z nowym materiałem — przeszła bez błędu, który blokował ją wczoraj; skasowaliśmy ją od razu |
| **A3** Instagram w portfolio | ⛔ nie było | ✅ **konto dodane**, `Auranet API` z pełnym dostępem | widzimy przypisanie |

Doszła też odpowiedź w sprawie haseł reklamowych: **BYD Shark 6 to pierwszy egzemplarz
w Polsce** — piszemy to śmiało. **Deepal G318 nie jest pierwszy**, więc o nim tak nie napiszemy.

Poniżej wyłącznie to, co zostało.

---

# 1. Instagram — ostatni klik, i wyjaśnienie, dlaczego znowu o coś prosimy

**Andrzej lub Ruslan · 5 minut**

Konto `@prima_auto.pl` jest w portfolio, `Auranet API` ma do niego pełny dostęp — to była
większa część roboty i jest za nami. Ale nasze narzędzia wciąż go nie widzą i chcemy uczciwie
powiedzieć dlaczego, żeby to nie wyglądało na kolejne żądanie z powietrza.

**Klucz dostępu, którym się łączymy, powstał 31 lipca — zanim konto Instagrama trafiło
do portfolio.** Uprawnienia zapisują się w takim kluczu w chwili jego tworzenia i późniejsze
dodanie zasobu ich nie dokłada. Klucz ma dziś osiem uprawnień i żadnego instagramowego,
więc pytanie o konto zwraca „nie istnieje lub brak uprawnień" — mimo że przypisanie jest
poprawne. Trzeba go wystawić raz jeszcze, już z kompletem.

**Co zrobić:** Ustawienia portfolio → Użytkownicy → **Użytkownicy systemowi** → `Auranet API`
→ **Wygeneruj nowy token** → aplikacja **Prima-Auto API** → zaznaczyć dotychczasowe
uprawnienia **plus trzy instagramowe**:

```
instagram_basic
instagram_content_publish
instagram_manage_insights
```

Ważność: **Nigdy nie wygasa**. Token pokazuje się **tylko raz** — przekazać go nam od razu
po wygenerowaniu (nie mailem otwartym tekstem, ustalimy kanał).

**Co to odblokowuje:** sześć pionowych filmów z sesji trafia na Instagram jako rolki bez
żadnej dodatkowej pracy — ten sam plik, drugi kanał.

> **Żeby to było ostatni raz.** Próbowaliśmy wygenerować ten klucz sami — Facebook odmawia,
> bo wymaga sekretu aplikacji „Prima-Auto API", a ten jest wyłącznie po Waszej stronie
> (aplikację zakładał Ruslan). Jeśli Ruslan przekaże nam ten sekret raz, bezpiecznym kanałem,
> **każdą kolejną zmianę uprawnień zrobimy sami i nie będziemy Was już o to prosić.**
> Znajduje się w `developers.facebook.com` → Prima-Auto API → Ustawienia → Podstawowe
> → „Klucz tajny aplikacji". Decyzja Wasza — bez tego po prostu wracamy do Was przy każdej zmianie.

---

# 2. Karta na koncie reklamowym — bez tego nie wyświetli się żadna reklama

**Ruslan · 10 minut**

Konto „Prima-Auto" jest aktywne, ma poprawną walutę (PLN) i strefę czasową (Warszawa).
Sprawdzone dzisiaj: **suma wydatków 0 zł, saldo 0 zł, limit wydatków nieustawiony,
brak podpiętej metody płatności.**

Nawet kampania przełączona na zielono **nie wyświetli się ani razu** — Facebook jej nie uruchomi.
Wszystko inne jest już gotowe i czeka wyłącznie na to.

**Co zrobić:** Menedżer Reklam → Ustawienia płatności → dodać kartę firmową
i **ustawić limit wydatków na koncie**.

⚠️ Limit nie jest formalnością — to zabezpieczenie, którego zabrakło w czerwcu. Ustawcie go
od razu przy karcie, na kwotę, przy której spokojnie śpicie.

---

# 3. YouTube — przenieść kanał na konto marki

**Właściciel konta Google · 15 minut**

Publikujemy właśnie **dwanaście filmów z sesji** — po jednym dziennie przez najbliższy tydzień.
Dziś każdy z nich trzeba wyklikać ręcznie w Waszym Studio, bo kanał wisi na **prywatnym koncie
Google osoby, która go założyła 3 grudnia**, a prywatnego konta nie da się podłączyć do żadnych
narzędzi. Po przeniesieniu robimy to automatycznie — razem z opisami, linkami do ofert
i statystykami, których dziś w ogóle nie widzimy.

Druga rzecz jest poważniejsza. Kanał ma **63 556 wyświetleń** i **nie należy do firmy**.
Jeżeli ta osoba odejdzie, straci telefon albo zapomni hasła, **kanał idzie razem z nią**
i nie ma procedury, która by go odzyskała. Facebook już raz pokazał Wam, jak to wygląda.

Konto marki (*Brand Account*) to wydzielona tożsamość Google, która **nie należy do żadnej
osoby**. Ludzie dostają do niej role — nadawane i odbierane w każdej chwili — a kanał zostaje
na miejscu.

**Przeniesienie nie zmienia niczego, co widać:** filmy, subskrybenci, komentarze, playlisty,
statystyki i adres `youtube.com/@primaauto_com_pl` zostają bez zmian. Dla widza nie dzieje się nic.
Zmienia się wyłącznie to, do czyjego konta kanał jest przypięty.

## To są dwa kroki, nie jeden

**Krok 1 — przeniesienie.** `youtube.com` na koncie, na którym jest kanał → Ustawienia
→ Ustawienia zaawansowane → **„Przenieś kanał na konto marki"**. Jeśli lista kont marki jest
pusta, wybrać utworzenie nowego i wpisać nazwę `Prima-Auto`. Google poprosi o ponowne podanie
hasła — to normalne.

**Krok 2 — dopisanie nas.** ⚠️ **Bez tego kroku pierwszy nic nie daje.** Samo przeniesienie
sprawia, że kanał należy do firmy, ale my nadal nie mamy do niego dostępu i dalej klikamy
ręcznie. Trzeba jeszcze:

`myaccount.google.com/brandaccounts` → **Prima-Auto** → **Zarządzaj uprawnieniami**
→ ikona zapraszania → dodać **js@auranet.com.pl** z rolą **Menedżer**.

Rolę możecie odebrać w każdej chwili, jednym kliknięciem, w tym samym miejscu.

⚠️ Przeniesienie robi **wyłącznie właściciel konta Google, na którym jest kanał** — nikt inny
nie ma tam tej opcji. **Nie próbujcie kilka razy pod rząd:** po nieudanej próbie Google potrafi
zablokować kolejną na kilka dni. Zrzut ekranu z komunikatem do nas, podpowiemy.

---

# 4. TikTok — dwie rzeczy, jedna natychmiast, druga przy okazji

**Andrzej · 2 minuty + 10 minut**

### 4a. Adres strony w profilu *(2 minuty, największy zysk do wysiłku na całej liście)*

Konto `@primaauto.pl` ma **334 obserwujących, 127 filmów i 1 781 polubień**. Liczba filmów
jest identyczna jak 14 lipca — od sześciu tygodni nie wyszło nic.

Ważniejsze: **w opisie profilu nie ma linku do strony.** Są dwa numery telefonu, nie ma adresu
`primaauto.com.pl`. Efekt: TikTok **nie przysłał na stronę ani jednego wejścia** przy 127 filmach.
Cała praca włożona w te filmy nie ma dokąd prowadzić.

**Co zrobić:** aplikacja TikTok → profil → **Edytuj profil** → pole **„Witryna"**
→ wpisać `primaauto.com.pl` → Zapisz.

Jeśli pola „Witryna" nie widać — konto nie jest firmowe, i wtedy od razu punkt 4b.

### 4b. Konto firmowe i Centrum biznesowe *(10 minut, skoro i tak przełączacie)*

Andrzej wspominał, że w temacie TikToka na coś czekacie — dajcie znać na co, bo być może
da się to załatwić przy okazji jednym ruchem.

Niezależnie od tego: **jeśli przełączacie konto na firmowe, warto zrobić od razu cały krok,
a nie połowę.** Samo przełączenie odblokowuje pole „Witryna" i statystyki. Centrum biznesowe
dokłada do tego możliwość, żebyśmy pomagali bez proszenia Was o hasło czy telefon przy każdej
zmianie — tak samo, jak działa to na Facebooku.

1. Aplikacja TikTok → profil → Ustawienia → Zarządzaj kontem → **Przełącz na konto biznesowe**
   → kategoria *Motoryzacja*.
2. `business.tiktok.com` → utworzyć **Centrum biznesowe TikTok** dla Prima-Auto
   → dodać konto `@primaauto.pl` jako zasób
   → dodać **Auranet** jako członka z uprawnieniami do zarządzania treścią.

**Gotowe, gdy:** konto figuruje w Centrum biznesowym i mamy do niego dostęp.

> **Mówimy wprost, czego to jeszcze nie załatwia:** w pełni automatyczna publikacja na TikToku
> wymaga dodatkowo zatwierdzenia naszej aplikacji przez samego TikToka — to trwa kilka tygodni
> i wynik nie zależy od nikogo z nas. Do tego czasu przygotowujemy gotowe materiały jako
> **szkice** na koncie, a ostatnie kliknięcie „opublikuj" zostaje po Waszej stronie. To i tak
> zdejmuje z Was cały proces i zostawia jedno kliknięcie.

---

## Zestawienie

| | Co zrobić | Kto | Czas |
|---|---|---|---|
| **1** | Wygenerować token `Auranet API` z trzema uprawnieniami Instagrama | Andrzej / Ruslan | 5 min |
| **2** | **Podpiąć kartę + ustawić limit wydatków** | Ruslan | 10 min |
| **3** | **Przenieść kanał YouTube na konto marki — i dopisać nas jako menedżera** | właściciel konta Google | 15 min |
| **4a** | Wpisać `primaauto.com.pl` w profilu TikToka | Andrzej | 2 min |
| **4b** | Konto firmowe + Centrum biznesowe TikTok, dodać Auranet | Andrzej | 10 min |

**Ruslan: 10 minut. Andrzej: 17 minut.**

Publikacja dwunastu filmów z sesji rusza od razu i nie czeka na nic z tej listy — punkt 3 sprawia tylko, że przestajemy robić to ręcznie. Kampanie na Facebooku i Instagramie
są gotowe do przygotowania i czekają wyłącznie na kartę.

---

**Kontakt:** Jan Schenk, Auranet — js@auranet.com.pl
