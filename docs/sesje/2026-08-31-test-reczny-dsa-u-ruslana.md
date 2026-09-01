# Test ręczny blokera DSA na zdalnym pulpicie u Ruslana — 31.08.2026

Wykonany test rozstrzygający zaplanowany 28.08 (`2026-08-28-dsa-i-klucz-aplikacji.md`).
Janek na zdalnym pulpicie Ruslana, portfolio firmowe Prima Auto.

## Wynik jednym zdaniem

**Interfejs odbija tak samo jak API** — hipoteza „dziewiczości konta" i podejrzenie
o nasz ładunek upadły. Ale kod błędu **drgnął po raz pierwszy od 28.08**, co pokazało,
że to nie jeden mur, tylko sekwencja dwóch kroków.

## Sekwencja zmierzona

| Moment | Kod | Znaczenie |
|---|---|---|
| Start sesji (sonda API) | `3858196` | brak danych reklamodawcy |
| Kreator zestawu, przed podaniem podmiotu | `3858196` | to samo w interfejsie |
| Po dodaniu podmiotu w oknie „Wymagane informacje o reklamodawcy" | **`3858211`** | podmiot jest, ale **niezweryfikowany** |
| Po kliknięciu „Opublikuj" | `3858211` | twarde odrzucenie, nie ostrzeżenie |

Interfejs przy `3858196` pokazuje to, czego API nigdy nie powiedziało: *„Podaj więcej
informacji o osobie lub organizacji promowanej w reklamie"* — plus przycisk **Dodaj informacje**.
Tego przycisku nie ma odpowiednika w Graph API i dlatego trzy dni szukaliśmy po Ustawieniach.

## Podmiot reklamodawcy — utworzony

Okno przyjęło: **PRIMA AUTO RUSLAN PRIMA**, organizacja, kraj rejestracji Polska,
ul. Jerzego Pleśniarowicza 2A lok. 38, Rzeszów 35-117, NIP `PL8133898576`,
`https://primaauto.com.pl`. Beneficjent = płatnik (bez rozdzielenia).

**JDG zgłaszamy jako organizację, nie osobę fizyczną** — reklamodawcą jest firma z NIP,
nazwa w polu to pełna nazwa z CEIDG. Przy „osobie fizycznej" Meta żądałaby dokumentu
tożsamości i pokazywała w bibliotece reklam imię i nazwisko prywatne zamiast nazwy firmy.

## Co obalone

- **Hipoteza dziewiczości konta (Janek, 28.08)** — ręczne przebicie pierwszego zestawu
  przez interfejs **nie działa**. Kliknięcie „Opublikuj" kończy się tym samym błędem.
- **Podejrzenie o nasz ładunek API** — przeglądarka wysyła to samo i dostaje to samo.
- **Account Quality: „brak problemów dotyczących zasobów"** na wszystkich zasobach.
  To nie jest kara ani ograniczenie za naruszenie.
- **Weryfikacja firmy nie jest przyczyną** — status „Zweryfikowano 26 sie 2026",
  a reklamy odbijają od tygodnia.
- **Brak Strony głównej portfolio nie był przyczyną** — uzupełnione w trakcie sesji
  (`primary_page` = Prima-Auto 1146829831857839), sonda po zmianie bez różnicy.

## Ścieżka wyjścia — ustalona

Komunikat `3858211` linkuje do `facebook.com/business/help/1024444835591336`, a ta strona
okazuje się formularzem **Beneficiary/Payer Inquiry** — „Prośba o weryfikację problemu
z informacjami o reklamodawcy lub płatniku".

**To nie jest kreator weryfikacji w Ustawieniach — to zgłoszenie do rozpatrzenia przez
zespół Mety.** Dlatego nie dało się tego znaleźć w Business Settings: tam tego nie ma.
„Strony z informacjami" w menu ustawień to rejestr wydawców prasowych, mylące nazwą.

**Zgłoszenie wysłane 31.08** z konta Ruslana: konto `1038563008906171`, podmiot
`PRIMA AUTO RUSLAN PRIMA`, komplet danych rejestrowych, opis kontrastu UE vs poza-UE.
Pole „Identyfikator reklamy" zostawione puste — żadna reklama nigdy nie powstała.

## Stan po sesji

- `dsa_recommendations` → nadal `[]`; Meta oddaje podmiot dopiero po weryfikacji,
  nie po samym wpisaniu. **To jest nasz miernik postępu** — sprawdzalny bez Ruslana.
- Sonda `dsa_status.py` → nadal `3858196` na PL, US przechodzi. API zostaje o krok
  za przeglądarką, bo podmiot zapisał się przy zestawie, nie na koncie.
- Kampania `[POST] Test PL — Zeekr 001` została wersją roboczą. **Drafty nie są widoczne
  przez API** — dlatego test musiał iść przez interfejs.
- Rola `MANAGE` dla System Usera `Auranet API` — **NADANA** w tej sesji (`DRAFT, ANALYZE, ADVERTISE, MANAGE`).

## Następny ruch

Czekamy na odpowiedź na zgłoszenie. Sondę odpalamy sami — sygnał zdjęcia blokera to
`dsa_recommendations` zwracające podmiot albo zmiana kodu na sondzie. Do klienta
**nie wracamy z niczym**, dopóki nie ma odpowiedzi od Mety.
