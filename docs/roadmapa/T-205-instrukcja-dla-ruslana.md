# Prima-Auto na Facebooku — odbudowa od zera

**Instrukcja wykonawcza dla zespołu Prima-Auto**
Wersja 1.0 · 29 lipca 2026 · przygotował: Auranet

---

## Dlaczego odbudowa, a nie odzyskiwanie

3 czerwca 2026 w nocy nieznana osoba przejęła kontrolę nad portfolio biznesowym Prima Auto. Uruchomiła obcą kampanię reklamową (targeting: Wietnam i Rumunia), wydała 54,12 zł z podpiętej karty i odcięła dostęp wszystkim uprawnionym osobom. Meta potwierdziła pisemnie, że było to nadużycie i nie obciążyła konta — ale **dostępu nie przywróciła**.

Stan po dwóch miesiącach:

- zgłoszenie w Meta Business Support zamknięte bez rozstrzygnięcia (7 lipca),
- wezwanie do jednoznacznej odpowiedzi wysłane 7 lipca, termin minął 21 lipca — **bez reakcji**,
- od 14 lipca Meta blokuje również dostęp techniczny (API) — narzędzia, którymi obsługiwaliśmy konto, nie odczytują już niczego,
- sprawa jest w kancelarii prawnej, ale to ścieżka na miesiące i **nie odblokuje reklam**.

**Wniosek: czekanie nie jest planem.** Poniżej jest komplet kroków, które wykonuje zespół Prima-Auto samodzielnie. Po ich zakończeniu Auranet przejmuje część techniczną (piksel, katalog aut, kampanie) i uruchamiamy reklamy.

**Czas potrzebny po Waszej stronie: ok. 3–5 godzin pracy** rozłożone na kilka dni, plus oczekiwanie na weryfikację Meta (od 2 dni do 2 tygodni, niezależne od nas).

---

## Zanim zaczniecie — trzy zasady, bez których to się powtórzy

Atak z 3 czerwca nie był przypadkiem. Ten sam sprawca tej samej nocy przejął konta trzech różnych firm. Wektor wejścia był zawsze taki sam: **obcy „partner" albo obcy użytkownik systemowy dodany do portfolio**. Nie złamane hasło — dodany dostęp.

**Zasada 1 — jedno czyste konto właściciela.**
Portfolio zakłada jedna osoba, z jednego konta osobistego Facebooka, które ma włączone dwuskładnikowe uwierzytelnianie **aplikacją** (Google Authenticator / Authy), nie SMS-em. SMS można przejąć. Przed startem: Ustawienia → Bezpieczeństwo → sprawdzić listę zalogowanych urządzeń i wylogować wszystko, czego nie rozpoznajecie; sprawdzić, czy nie ma obcych kluczy bezpieczeństwa.

**Zasada 2 — nikt z zewnątrz nie dostaje „pełnej kontroli".**
Do portfolio nie dodajemy partnerów biznesowych ani użytkowników systemowych, których nie znamy z imienia i nazwiska. Jeśli ktoś pisze „jestem z pomocy technicznej Meta i potrzebuję dostępu" — to zawsze oszustwo. Meta nigdy nie prosi o dodanie do portfolio.

**Zasada 3 — przegląd raz w miesiącu.**
Ustawienia portfolio → Osoby / Partnerzy / Użytkownicy systemowi. Jeśli pojawi się cokolwiek, czego nie dodaliście świadomie — usunąć natychmiast i dać nam znać.

---

## Krok 1. Nowa Strona Prima-Auto

**Kto:** Ruslan (albo osoba wyznaczona na właściciela).
**Gdzie:** facebook.com → Strony → Utwórz nową Stronę.

| Pole | Co wpisać |
|---|---|
| Nazwa Strony | `Prima-Auto` |
| Kategoria | Sprzedawca samochodów (Car dealership) |
| Opis | Import samochodów z Chin i Korei. Sprowadzamy auta na zamówienie — od wyboru oferty po odbiór w Polsce. |
| Adres WWW | `https://primaauto.com.pl` |
| Telefon / e-mail | dane kontaktowe firmy (te same, co na stronie WWW) |

Po utworzeniu: dodać zdjęcie profilowe (logo) i zdjęcie w tle. Jeśli nie macie plików w dobrej rozdzielczości — napiszcie, przygotujemy.

**Gotowe, gdy:** Strona jest widoczna publicznie i ma poprawny link do primaauto.com.pl.

---

## Krok 2. Nowe portfolio biznesowe

**Kto:** ta sama osoba, która utworzyła Stronę.
**Gdzie:** business.facebook.com → Utwórz konto / portfolio biznesowe.

> **Ruslan ma już utworzone portfolio o numerze `1486783363131026`** (założone przy poprzednim podejściu w czerwcu). Jeśli jest puste i nietknięte — można użyć jego zamiast zakładać nowe. Jeśli macie wątpliwości co do jego stanu, załóżcie nowe: nic to nie kosztuje, a czysty start jest bezpieczniejszy.

### To jest miejsce, w którym poprzednio polegliśmy — czytać uważnie

Dane firmy w portfolio muszą być **identyczne, co do znaku, z danymi w CEIDG**. Poprzednia weryfikacja została odrzucona między innymi dlatego, że w adresie była literówka („Jeżego" zamiast „Jerzego") i figurowały dwa różne adresy z dwoma różnymi kodami pocztowymi.

| Pole | Wartość |
|---|---|
| Nazwa firmy | dokładnie jak w CEIDG (pełna nazwa działalności) |
| Adres | ulica, numer, lokal, kod pocztowy, miejscowość, województwo — **przepisane z CEIDG, nie z pamięci** |
| NIP | numer firmy |
| Strona WWW | `https://primaauto.com.pl` |
| E-mail służbowy | adres w domenie firmy, nie prywatna skrzynka |

Przed zapisaniem: otworzyć wydruk z CEIDG obok i porównać znak po znaku. Pięć minut tutaj oszczędza dwa tygodnie odrzuconej weryfikacji.

**Gotowe, gdy:** portfolio istnieje, dane zgadzają się z CEIDG. **Zanotujcie numer portfolio** (widoczny w Ustawieniach, w adresie strony jako `business_id=`) — będzie nam potrzebny.

---

## Krok 3. Przypięcie Strony do portfolio

**Gdzie:** Ustawienia portfolio → Konta → Strony → Dodaj → **Dodaj istniejącą Stronę**.

Uwaga: wybrać „dodaj istniejącą", a nie „poproś o dostęp do Strony". Skoro Strona i portfolio należą do tej samej osoby, przypięcie jest natychmiastowe.

**Gotowe, gdy:** Strona figuruje na liście zasobów portfolio ze statusem pełnej kontroli.

---

## Krok 4. Instagram firmowy

**Dlaczego:** reklamy na Instagramie kosztują mniej niż na Facebooku i docierają do młodszej części odbiorców. Bez podpiętego konta reklamy wyświetlą się tylko na Facebooku.

Poprzednio do Strony podpięte było **obce konto Instagram** (`@gillesmeralda988507`) — jeden z elementów przejęcia. Tym razem zakładamy własne.

1. Załóż konto Instagram `primaauto.pl` (albo podobna wolna nazwa) — **konto firmowe**, nie prywatne.
2. Ustawienia portfolio → Konta → Konta na Instagramie → Dodaj.
3. Powiąż je ze Stroną Prima-Auto.

**Gotowe, gdy:** konto Instagram widnieje w portfolio i jest połączone ze Stroną.

---

## Krok 5. Konto reklamowe

**Gdzie:** Ustawienia portfolio → Konta → Konta reklamowe → Dodaj → Utwórz nowe konto reklamowe.

⚠️ **Dwa ustawienia są nieodwracalne — po zapisaniu nie da się ich zmienić:**

| Pole | Wartość | Uwaga |
|---|---|---|
| Waluta | **PLN (złoty polski)** | poprzednie konto miało hrywnę ukraińską, co komplikowało rozliczenia |
| Strefa czasowa | **Europe/Warsaw** | poprzednie miało Europe/Berlin — raporty rozjeżdżały się o godzinę |
| Nazwa konta | `Prima-Auto` | dowolna, ale czytelna |

**Gotowe, gdy:** konto istnieje, waluta PLN, strefa Warszawa. **Zanotujcie numer konta reklamowego.**

---

## Krok 6. Płatność i limit bezpieczeństwa

**Gdzie:** Ustawienia konta reklamowego → Płatności.

1. Podpiąć kartę firmową.
2. **Ustawić limit wydatków konta** (Spend limit) — np. 2 000 zł. To jest bezpiecznik: nawet gdyby ktoś przejął konto, więcej niż limit nie wyda. Limit podnosi się jednym kliknięciem, gdy kampanie ruszą na dobre.

Rekomendacja: karta z niskim limitem dziennym, przeznaczona wyłącznie na reklamy. Nie karta, z której płacicie za wszystko inne.

**Gotowe, gdy:** karta zaakceptowana, limit wydatków ustawiony.

---

## Krok 7. Weryfikacja firmy

**Gdzie:** Ustawienia portfolio → Centrum bezpieczeństwa → Weryfikacja firmy.

Meta poprosi o dokument potwierdzający istnienie firmy. **Poprzednim razem odrzuciła konkretny dokument** — dlatego tym razem warto zadbać o jakość zgłoszenia:

- **Dokument:** wydruk z CEIDG (aktualny, z datą) lub zaświadczenie o nadaniu NIP.
- **Skan:** cała strona w kadrze, wszystkie krawędzie widoczne, tekst ostry, bez cieni i przycięć. Zdjęcie telefonem jest akceptowane, jeśli jest czytelne — ale skan jest pewniejszy.
- **Zgodność:** nazwa i adres na dokumencie muszą być identyczne z tymi wpisanymi w portfolio (Krok 2). Jakakolwiek różnica = odrzucenie.
- Meta może dodatkowo poprosić o potwierdzenie numeru telefonu lub e-maila w domenie firmy.

**Czas:** zwykle 2–5 dni roboczych.

**Gotowe, gdy:** status weryfikacji to „Zweryfikowano".

---

## Krok 8. Weryfikacja reklamodawcy w Unii Europejskiej

**To jest twardy bloker.** Bez niej nie da się utworzyć żadnej kampanii reklamowej kierowanej na Polskę — Meta blokuje to na poziomie systemu (wymóg unijnego aktu o usługach cyfrowych, DSA). Poprzednim razem zatrzymaliśmy się dokładnie tutaj.

**Gdzie:** facebook.com/accountquality → sekcja weryfikacji reklamodawcy UE.

Meta poprosi o:
- potwierdzenie danych firmy (te same, co w Kroku 2 i 7),
- dokument tożsamości osoby reprezentującej firmę,
- wskazanie **beneficjenta i płatnika reklam** — czyli kto płaci i na czyją korzyść reklamy działają. W obu polach wpisujemy nazwę firmy Prima-Auto. Ta nazwa będzie **publicznie widoczna** przy każdej reklamie w unijnej bibliotece reklam — to normalne i dotyczy wszystkich reklamodawców w UE.

**Czas:** od 1 dnia do 2 tygodni. Nie da się przyspieszyć.

**Gotowe, gdy:** status reklamodawcy UE to „Zweryfikowano" i przy koncie nie ma ostrzeżenia o braku weryfikacji.

---

## Krok 9. Dostęp dla Auranet

Tutaj są dwie możliwe drogi. **Rekomendujemy drogę A** — jest szybsza i bezpieczniejsza dla Was.

### Droga A (rekomendowana) — użytkownik systemowy

Konto osobiste Jana Schenka na Facebooku zostało przez atakującego trwale usunięte i Meta go nie przywróciła. Zamiast czekać na odbudowę konta osobistego, pracujemy przez **użytkownika systemowego** — to nie jest człowiek, tylko techniczny dostęp należący do Waszego portfolio, który w każdej chwili możecie wyłączyć jednym kliknięciem.

**Co robicie:**

1. **Utwórzcie aplikację.** developers.facebook.com → Moje aplikacje → Utwórz aplikację → typ **Firma** → nazwa `Prima-Auto API` → powiązać z nowym portfolio.
2. **Ustawienia portfolio → Użytkownicy systemowi → Dodaj:**
   - Nazwa: `Auranet API`
   - Rola: **Administrator** ⚠️ (nie „Pracownik" — przy roli pracownika nie da się utworzyć katalogu samochodów, sprawdzone w maju)
3. **Przypiszcie zasoby** (przycisk „Przypisz zasoby" przy utworzonym użytkowniku) — wszystkie cztery:
   - konto reklamowe → Zarządzanie kampaniami + Wyświetlanie wyników,
   - Stronę Prima-Auto → pełna kontrola,
   - konto Instagram → pełna kontrola,
   - aplikację `Prima-Auto API` → **Zarządzanie aplikacją**.

   ⚠️ Czwarty punkt jest najczęściej pomijany. Bez roli w aplikacji generowanie tokenu kończy się komunikatem „Brak dostępnych uprawnień".
4. **Wygenerujcie token** (przycisk „Wygeneruj token" przy użytkowniku systemowym):
   - aplikacja: `Prima-Auto API`
   - wygasanie: **Nigdy**
   - uprawnienia — zaznaczyć **wszystkie z tej listy**:
     `ads_management`, `ads_read`, `business_management`, `catalog_management`, `pages_read_engagement`, `pages_manage_ads`, `pages_show_list`, `instagram_basic`, `leads_retrieval`

   ⚠️ `ads_management` jest krytyczne. W czerwcu mieliśmy token bez tego uprawnienia i nie mogliśmy zatrzymać obcej kampanii, mimo że ją widzieliśmy.
5. **Przekażcie token bezpiecznie.** To jest hasło do konta reklamowego — nie wysyłajcie go jednym mailem ani jedną wiadomością. Prosta metoda: podzielić na pół i wysłać dwoma różnymi kanałami (np. połowa WhatsAppem, połowa SMS-em). Odbiorca: Jan Schenk, js@auranet.com.pl / telefon znany Ruslanowi.

**Co dzięki temu możecie:** cofnąć nam dostęp w 5 sekund (usunięcie użytkownika systemowego), zobaczyć w dzienniku każdą naszą operację, zachować pełną własność wszystkich zasobów.

### Droga B (alternatywna) — dostęp partnera

Jeśli wolicie klasyczny model współpracy agencyjnej, Jan zakłada nowe konto Facebook, Auranet zakłada własne portfolio, a Wy nadajecie mu dostęp partnera (Ustawienia → Partnerzy → Dodaj partnera → numer portfolio Auranet). To dodaje ok. tygodnia (zakładanie i „docieranie" nowego konta) i wiąże się z ryzykiem, że Meta powiąże nowe konto Jana z usuniętym i je zablokuje.

**Decyzja należy do Was.** Droga A działa od razu i nie zależy od tego, czy Meta kiedykolwiek odblokuje konto Jana.

---

## Krok 10. Przeniesienie obserwujących ze starej Strony

Stara Strona ma **16 676 obserwujących**. Nie da się ich przenieść ani przepiąć — Meta nie udostępnia takiej operacji, a Strona siedzi w przejętym portfolio. Można natomiast **zaprosić ich do przejścia**.

**Kto:** Andrzej — według naszej wiedzy z czerwca **wciąż ma na starej Stronie uprawnienia menedżera**. Trzeba to najpierw sprawdzić: czy da się z niej opublikować post.

**Co opublikować** (propozycja — dostosujcie do własnego stylu):

> Zmieniamy adres. Ta strona przestaje być przez nas obsługiwana — zapraszamy na nowy profil Prima-Auto, gdzie codziennie publikujemy aktualne oferty aut z Chin i Korei.
> 👉 [link do nowej Strony]
> Wszystkie oferty jak zawsze na primaauto.com.pl

**Jak to wzmocnić:**
- przypiąć post na górze starej Strony,
- powtórzyć go 2–3 razy w odstępie tygodnia,
- zmienić zdjęcie w tle starej Strony na grafikę z informacją o przeprowadzce,
- publikować przez pierwsze tygodnie równolegle na obu Stronach, dopóki stara jeszcze działa.

**Realistycznie:** przejdzie kilka do kilkunastu procent obserwujących. To jest cena przejęcia konta, nie da się jej uniknąć. Nowa Strona zaczyna praktycznie od zera i pierwsze tygodnie zasięgu będą słabe — to normalne i przewidziane.

⚠️ Jeśli Andrzej stracił już dostęp do starej Strony — dajcie znać, ten krok wtedy odpada, a resztę robimy bez zmian.

---

## Czego nie robić

| Nie róbcie tego | Dlaczego |
|---|---|
| Nie próbujcie odzyskiwać starego portfolio „przy okazji" | Każda próba logowania do przejętych zasobów może powiązać nowe konto ze starym i ściągnąć na nie blokadę |
| Nie dodawajcie starego konta reklamowego do nowego portfolio | Ma status nieuregulowany i obcą kampanię — zainfekuje nowe portfolio |
| Nie akceptujcie zaproszeń „partnerskich", których nie inicjowaliście | To był dokładny wektor ataku z 3 czerwca |
| Nie klikajcie w linki „pomoc Meta" z wiadomości prywatnych | Meta nie kontaktuje się przez Messenger w sprawach konta |
| Nie zmieniajcie waluty ani strefy czasowej „na później" | Są nieodwracalne — trzeba by zakładać konto od nowa |
| Nie wpisujcie danych firmy „z pamięci" | Jedna literówka = odrzucona weryfikacja i dwa tygodnie w plecy |

---

## Co nam przekazać, gdy skończycie

Jedna wiadomość z tymi danymi wystarczy, żebyśmy ruszyli:

| Co | Gdzie to znaleźć |
|---|---|
| Numer portfolio biznesowego | Ustawienia portfolio → Informacje o firmie |
| Numer konta reklamowego | Menedżer reklam, na górze (format `act_...`) |
| Numer Strony | Strona → Informacje → na dole |
| Nazwa konta Instagram | — |
| Token użytkownika systemowego | z Kroku 9, **w dwóch częściach, dwoma kanałami** |
| Status weryfikacji firmy | zrzut ekranu z Centrum bezpieczeństwa |
| Status weryfikacji reklamodawcy UE | zrzut ekranu z facebook.com/accountquality |

---

## Co robimy my, gdy dostaniemy dostęp

Żeby było jasne, po co te wszystkie kroki — po Waszej stronie robota się kończy, zaczyna nasza:

1. **Nowy piksel** i przepięcie pomiaru na stronie primaauto.com.pl — mierzymy obejrzenia ofert, kliknięcia w telefon, kliknięcia w WhatsApp i wysłane formularze. Cała mechanika jest już zbudowana i przetestowana na żywym ruchu, to tylko przepięcie na nowy numer.
2. **Katalog samochodów** — automatyczny plik z ponad 3 000 aktualnych ofert (ceny, zdjęcia, dostępność), odświeżany codziennie. Generator jest gotowy. Dzięki temu Facebook może pokazywać konkretne auta osobom, które je wcześniej oglądały na stronie.
3. **Grupy odbiorców** — osoby, które odwiedziły stronę, oglądały konkretne oferty, kontaktowały się; plus grupy „podobnych odbiorców" budowane przez Facebooka na tej podstawie. ⚠️ To wymaga czasu — grupy zbierają się tygodniami, tego nie da się przyspieszyć.
4. **Kampanie** — targeting jest już rozpisany z poprzedniego podejścia (8 największych miast plus Śląsk, wiek 35–60, zainteresowania motoryzacyjne) i wytypowaliśmy 5 postów ofertowych jako materiał startowy. Do reużycia w całości.

**Nasza część to 16–22 godziny pracy.** Zaczyna się dopiero po Kroku 9, a kampanie ruszają dopiero po Kroku 8 (weryfikacja UE).

---

## Kolejność i zależności

Kroki 1–6 można zrobić jednego popołudnia. Kroki 7 i 8 to czekanie na Metę. Krok 9 można zrobić od razu po Kroku 5 — nie trzeba czekać na weryfikacje, my w tym czasie przygotujemy piksel i katalog.

```
Krok 1 (Strona) ─┐
                 ├─→ Krok 3 (przypięcie) → Krok 4 (Instagram)
Krok 2 (portfolio)┘         │
                            ├─→ Krok 5 (konto reklamowe) → Krok 6 (karta + limit)
                            │            │
                            │            └─→ Krok 9 (dostęp dla nas) → my: piksel + katalog
                            │
                            ├─→ Krok 7 (weryfikacja firmy) ──┐
                            └─→ Krok 8 (weryfikacja UE) ─────┴─→ START KAMPANII

Krok 10 (post ze starej Strony) — niezależny, im wcześniej tym lepiej
```

---

## Gdyby coś nie zadziałało

Przy każdym kroku może pojawić się komunikat błędu, którego nie ma w tej instrukcji — Meta zmienia interfejs co kilka tygodni. **Nie kombinujcie i nie klikajcie na oślep.** Zróbcie zrzut ekranu z komunikatem i wyślijcie do Jana. Większość takich ścian znamy z poprzedniego podejścia i wiemy, jak je obejść.

---

*Auranet · marketing oparty na danych · js@auranet.com.pl*
