# T-220 — prompt startowy do nowego wątku (stan na 2026-08-06)

> Zastępuje §0 pakietu wykonawczego (`docs/sesje/2026-08-03-T-220-PAKIET-WYKONAWCZY.md`),
> bo między 03.08 a 06.08 zmienił się stan wejściowy: wtyczka **0.34.16 → 0.34.21**,
> doszło T-242 (edycja VIN w zamówieniu) i pierwsze produkcyjne umowy leasingowe.

---

## Prompt do wklejenia

```
Wdrażasz T-220 — aneks VIN do umowy — w projekcie primaauto.

START:
1. Przeczytaj docs/sesje/2026-08-03-T-220-PAKIET-WYKONAWCZY.md — to jest plan wykonawczy,
   wykonujesz §6 w kolejności. Decyzje z §5 (D1-D8) są ZAMKNIĘTE, nie otwierasz ich ponownie.
2. Przeczytaj docs/sesje/2026-08-06-T-220-PROMPT-STARTOWY.md sekcję „Co się zmieniło po 03.08" —
   pakiet jest w trzech miejscach nieaktualny i tam masz poprawki.
3. Dopiero wtedy zacznij. Nie planuj od nowa tego, co jest już rozpisane.

ZASADY:
- Source of truth = serwer (~/domains/primaauto.com.pl/public_html/...). Repo jest kontekstowe.
- Przed każdą edycją: kopia .bak z datą, po edycji php -l. Plik produkcyjny edytujesz in-place,
  więc lint jest obowiązkowy od razu, nie na końcu.
- Wymóg nadrzędny: WYGENEROWANIE ANEKSU NIE WYSYŁA ŻADNYCH MAILI. Mail wychodzi wyłącznie przy
  świadomym przełączeniu znacznika na „wysłany". Implementacja, która to łamie, jest błędna.
- NIE ruszasz: STATUSES, TRANSITIONS, LISTING_RESERVATION_MAP, changeStatus(), generate(),
  kreatora klienta, bloku ręcznego wgrywania umowy.
- W testach ZAWSZE wyciszaj pocztę filtrem pre_wp_mail. Bez tego E2E wysyła maile do prawdziwych
  klientów — zaliczone przy T-217 (4 maile, w tym „umowa gotowa do podpisu”).
- Nie regenerujesz cudzych umów „dla sprawdzenia” — regenerate() kasuje poprzedni PDF bez
  wersjonowania.

NA KONIEC: testy z §7 pakietu, bump wersji, docs/VERSIONS.md, wpis w docs/QUEUE.md, commit.
```

---

## Co się zmieniło po 03.08 — poprawki do pakietu

### 1. `§3.2 Fallback VIN` jest nieaktualny dla wzorca leasingowego

Pakiet pisze: `getVehicleData()` → `_order_vin` → `vin_number` listingu → `'—'`, a `isValidVin()`
liczone z tego samego łańcucha.

**Stan od 0.34.21 (T-242, 06.08):** klucz `vin_verified` (wariant leasingowy) liczy się
**wyłącznie z `_order_vin`**, bez fallbacku na ogłoszenie. Klucz `vin` (pośrednictwo) zachowuje
stary fallback i jest nietknięty.

Powód: Ruslan czyścił VIN w zamówieniu, a numer wracał do umowy z ogłoszenia przy każdej
regeneracji. Szczegóły: `docs/sesje/2026-08-06-vin-edycja-w-zamowieniu.md`.

**Konsekwencja dla T-220:** guard aneksu (D3) i tak czyta `isValidVin(_order_vin)`, więc jest
zgodny — ale nie zakładaj już, że „umowa leasingowa ma VIN" da się wywnioskować z ogłoszenia.
Jedynym źródłem jest pole karty zamówienia.

### 2. Wzorzec leasingowy ma już produkcyjne umowy

Pakiet (§1): „**0** z ustawionym `_order_contract_type` — wzorzec leasingowy nie był jeszcze użyty
produkcyjnie".

**Stan 06.08:** dwa zamówienia leasingowe, oba z wygenerowaną umową:

| Zamówienie | Numer umowy | `_order_vin` | VIN ogłoszenia |
|---|---|---|---|
| 407331 | UL/2026/0001 | puste | 407285 → `HJNNBDLH1TB772781` |
| 407351 | UL/2026/0002 | puste | 407318 → puste |

To są **prawdziwe umowy prawdziwych klientów** — testuj na zamówieniach zakładanych i kasowanych
przez skrypt (wzorzec z T-217: `wp_insert_post`, nie `AsiaAuto_Order::create()`), a tych dwóch
używaj wyłącznie do odczytu. Za to są idealnym materiałem na scenariusz „umowa leasingowa bez
VIN-u czeka na aneks" — dokładnie ta ścieżka, którą T-220 obsługuje.

### 3. Pole VIN w karcie zamówienia wygląda inaczej

Pakiet zakłada pole, które samo wypełnia się numerem z ogłoszenia. **Od 0.34.21 nie wypełnia się** —
pod nim stoi pasek „VIN OFERTY: … [Wstaw]". Ma to znaczenie dla kroku, w którym dokładasz przycisk
„Generuj aneks VIN" do karty: układ wiersza VIN się zmienił, przeczytaj aktualny kod
(`class-asiaauto-order-admin.php`, okolice `$vin_display`), zanim będziesz go opisywał.

### 4. Doszedł test wielokrotnego użytku

`tmp/test-vin-guard-2026-08-06.php` (read-only, Reflection, bez PDF i bez zapisu do bazy) —
15 asercji, m.in. regresja pośrednictwa. **Uruchom go po swoich zmianach**, razem z zestawem T-217
(`tmp/T-217-regresja-posrednictwo.php`, `tmp/T-217-testy-leasing.php`,
`tmp/T-217-e2e-status-i-zalacznik.php`). Aneks dotyka tego samego generatora, więc regresja na
umowie głównej jest realnym ryzykiem.

---

## Czego ten wątek NIE dotyczy

- **T-242 jest zamknięte** (0.34.21 LIVE) — usuwanie błędnego VIN-u przed podpisaniem działa.
  T-220 to druga strona tej samej sprawy: dopisanie prawdziwego numeru po weryfikacji auta.
- **Umowa pośrednictwa i zamaskowane VIN-y z che168** (decyzja D4 z T-217) — osobny temat, choć
  D6 pakietu dotyka pośrednictwa w innym miejscu (klauzula zamiast myślnika przy pustym VIN).
- **Brak wersjonowania PDF przy regeneracji** — znane, otwarte, poza zakresem T-220.

## Raportowanie

T-242 i T-220 raportujemy Ruslanowi **razem**, jako jeden obieg: dopóki nie ma pewnego numeru,
Ruslan zostawia pole puste i umowa sama zapowiada aneks; gdy auto przyjedzie i VIN się potwierdzi,
wpisuje go i generuje aneks. Notatka o T-242 poszła do Janka 06.08.
