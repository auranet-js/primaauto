# T-221 — Pakiet prawny pod PayU (regulamin usługi + strona o depozycie)

> Status: **gotowe do startu** · Rozmiar: M · **Blokuje T-121** (płatność online)
> Godziny realnie: **4–6 h** — treść regulaminu i strony `/depozyt/` jest już **napisana i opublikowana** (rozliczone osobno, 27.07). Pozostałe godziny to: poprawki po akcepcie, PayU w polityce prywatności, korekta `/finansowanie/`, zdjęcie noindex + podlinkowanie, wniosek do PayU. ⚠️ **Nie obejmuje** przebudowy góry `/depozyt/` (kierunek „InPost") — osobne 2–3 h
> Powiązane: T-121 (PayU), T-217 (umowa leasingowa)

---

## 1. Problem — stan zmierzony 2026-07-27

Prima-Auto ma **dwa** dokumenty od prawnika (21.05.2026) i **oba dotyczą wyłącznie strony WWW**:

| Dokument | Gdzie | Co reguluje |
|---|---|---|
| `Regulamin śude` | live na `/regulamin/` | korzystanie ze strony; **§4 ust. 3: „Korzystanie z usług (…) jest nieodpłatne"** |
| `Regulamin strony internetowej` | `docs/`, **nigdy nie wdrożony** | charakter informacyjny strony; §3: treści „nie stanowią oferty w rozumieniu art. 66 KC" |

**Nie istnieje żaden dokument regulujący samą usługę** — organizację importu, depozyt, zwrot, odstąpienie, reklamację usługi. Prawnik nie popełnił błędu: dostał zlecenie na stronę i je wykonał. Nikt nie zamówił regulaminu usługi.

**Konsekwencja dla PayU:** wysyłając wniosek akceptanta z linkiem do dzisiejszego regulaminu, przedstawiamy dokument mówiący, że usługi są nieodpłatne — przy jednoczesnej prośbie o przyjmowanie zwrotnych depozytów po 6 150 zł. To jest blocker, nie formalność.

**Dodatkowo:** strona `/finansowanie/` obiecuje „30% zaliczki przy podpisaniu + 70% na 10 dni przed portem" — model, którego nie ma w żadnej z dwóch umów (tam: depozyt zabezpieczający, a przy leasingu cenę płaci Finansujący). Rozjazd treści marketingowej z podpisywanym dokumentem.

## 2. Decyzje (2026-07-27, Janek)

| # | Decyzja |
|---|---|
| D1 | **Nie rozszerzamy** żadnego z dwóch istniejących regulaminów — piszemy **trzeci dokument** w konwencji prawnika (ta sama numeracja §, ten sam styl definicji), żeby domykał jego pakiet, a nie przepisywał jego pracę |
| D2 | **Treść przygotowujemy my.** Decyzja Janka 27.07: **nie angażujemy prawnika** — robimy minimum funkcjonalne pod wniosek do PayU, bez rozbudowy „na zapas" |
| D3 | **Zwrot depozytu = model kosztowy dla wszystkich** — bez podziału na konsumenta i firmę: depozyt minus udokumentowane koszty minus wynagrodzenie za wykonaną część usługi, reszta wraca |
| D4 | Struktura: **jeden regulamin usługi** (zwroty jako rozdział z kotwicą) + **osobna, nieprawnicza strona `/depozyt/`** |
| D5 | **Zakres regulaminu depozytu = sytuacja, w której nie dojdzie do rezerwacji samochodu** (Janek, 27.07). Dalsze etapy — po rezerwacji, przy leasingu, przy rezygnacji klienta — reguluje umowa i ich nie dublujemy |

### Umowa a regulamin — podział, nie sprzeczność

§4 umowy leasingowej rozróżnia **cztery** stany; regulamin opisuje wyłącznie dwa pierwsze, po ludzku:

| Sytuacja | Umowa | Kto opisuje |
|---|---|---|
| Nie doszło do rezerwacji/zakupu, Prima-Auto nie poniosła kosztów | §4 ust. 4 — zwrot całości, **3 dni robocze** | **regulamin** |
| Nie doszło do rezerwacji/zakupu, koszty poniesione | §4 ust. 5 — zwrot po potrąceniu udokumentowanych kosztów, **7 dni roboczych** | **regulamin** |
| Doszło do rezerwacji, transakcja idzie dalej | §4 ust. 6 — zwrot po zapłacie ceny przez Finansującego albo zaliczenie na opłatę wstępną | umowa |
| Klient rozmyślił się po zarezerwowaniu auta | §4 ust. 7 + §13 — zatrzymanie depozytu | umowa |

To pokrywa się z momentem płatności online: klient płaci depozyt w kroku 3 (`potwierdzone`), **zanim** auto zostanie zarezerwowane — czyli dokładnie w obszarze, który regulamin opisuje.

**Umowa leasingowa pozostaje nietknięta** — jej kształt wynika z wymogów leasingodawcy (Ruslan jej nie zmienia).

Drugorzędna uwaga dla prawnika (nie blocker): §4 ust. 7 wobec **konsumenta** — zatrzymanie całości bez związku z realnymi kosztami bywa kwestionowane jako klauzula abuzywna. Dotyczy etapu po rezerwacji, więc nie wpływa na wniosek do PayU.

## 3. Zakres

### 3.1 `/regulamin-uslugi/` — WDROŻONE 27.07

Opublikowany, `noindex`, bez linków. **Minimum funkcjonalne — 6 paragrafów:**

| § | Treść |
|---|---|
| §1 | Usługodawca i przedmiot usługi — **usługa odpłatna** (zdejmuje kolizję z §4 ust. 3 regulaminu śude); pierwszeństwo umowy indywidualnej |
| §2 | Depozyt i płatności — kwota z zamówienia, przelew/PayU, **faktura VAT** |
| §3 | **Zwrot Depozytu** ← kotwica `#zwrot` (3 dni / 7 dni po potrąceniu) — ten adres idzie do wniosku PayU |
| §4 | Odstąpienie konsumenta (14 dni + art. 35 UPK) |
| §5 | Reklamacje (14 dni) |
| §6 | Postanowienia końcowe |

Świadomie **pominięte:** definicje, dwuetapowość zawierania umowy, wyłączenia z art. 38 UPK, ODR, uwagi dla prawnika. Nie były potrzebne do wniosku.

### 3.2 `/depozyt/` — WDROŻONE 27.07 (strona sprzedażowa)

Szkic narracji do redakcji (surowa myśl Janka, do ubrania):

> **Co depozyt znaczy dla Ciebie**
> Zanim cokolwiek podpiszesz, możemy zająć się tym konkretnym egzemplarzem — sprawdzić go dla Ciebie na miejscu w Chinach, zweryfikować stan i historię, a jeśli znajdziemy coś lepszego w tej samej cenie, po prostu Ci to zaproponujemy. Depozyt jest zwrotny i daje Ci pierwszeństwo w kolejce.
>
> **Co znaczy dla nas**
> Że nam zaufałeś. Zakup samochodu to nie jest zwykły zakup — to jedno z ważniejszych wydarzeń w roku. Depozyt jest dla nas sygnałem, że możemy zacząć pracować konkretnie nad Twoim autem, a nie nad zapytaniem.

Do tego: kiedy wraca, w jakim terminie, co się dzieje przy rezygnacji → link do `#zwrot` regulaminu.

### 3.3 `/polityka-prywatnosci/` — edycja

Dopisanie PayU jako odbiorcy danych (dziś są „podmioty uczestniczące w procesie finansowania", brak operatora płatności).

### 3.4 `/finansowanie/` — korekta

Uzgodnienie treści z realnym modelem płatności (depozyt, nie „30/70"). **Wymaga potwierdzenia u Ruslana, który model jest aktualny.**

### 3.5 Wniosek do PayU

Pismo z opisem schematu: usługa pośrednictwa/organizacji importu, depozyt zwrotny, kwota ustalana per zamówienie, procedura zwrotu pod wskazanym adresem. Pytamy o dopuszczalność **i o prowizje**. Wysyła Janek jako wdrożeniowiec — **dopiero po opublikowaniu regulaminu usługi**.

## 4. Kolejność

```
1. Redakcja regulaminu + strony /depozyt/     ✅ ZROBIONE 27.07 (rozliczone w changelogu)
2. Publikacja, noindex, bez linków            ✅ ZROBIONE 27.07
3. Akcept Janka + ew. poprawki                (Janek)
4. Pokazanie Ruslanowi                        (Janek)
5. PayU w polityce prywatności + korekta /finansowanie/   (my)
6. Zdjęcie noindex + podlinkowanie w stopce i kreatorze   (my)
7. Wniosek do PayU z linkiem do #zwrot        (Janek)
8. → odblokowuje T-121
```

## 5. Definicja zrobionego

- `/regulamin-uslugi/` i `/depozyt/` opublikowane, zlinkowane z kreatora i stopki.
- Polityka prywatności wymienia PayU.
- Wniosek do PayU wysłany, odpowiedź zarchiwizowana w `docs/legal/`.
