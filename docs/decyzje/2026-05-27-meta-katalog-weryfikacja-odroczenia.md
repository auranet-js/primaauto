# ADR: Meta — katalog pojazdów i weryfikacja firmy, odroczenia

**Data:** 2026-05-27
**Status:** zaakceptowane
**Kontekst:** kontynuacja sesji [[meta-kampania-fb-build-planu-audiencje-2026-05-25]]; Janek dostał rolę **ADMIN portfolio** Prima Auto `145818221430407` (poprzednio Finanse).

## Stan na początek sesji (sprawdzony przez API)

| Pole | Wartość |
|---|---|
| `verification_status` portfolio | `rejected` → `pending_submission` (zmieniło się w trakcie sesji — Janek zaczął re-submit) |
| Konto reklamowe `act_1083673765606618` | aktywne, **karta Mastercard *3519**, waluta **PLN** (była UAH), balance 0 |
| Kampania `120248507523010111` | PAUSED, **0 zestawów / 0 reklam** |
| `vertical` portfolio | `NOT_SET` |
| System User `122100378213334508` (token `primaauto-mktg`) | rola **EMPLOYEE** |
| Katalog pojazdów | brak (`owned_product_catalogs: []`) |

UWAGA: SU ID w memory z 2026-05-25 (`61590035266690`) jest błędne — `/me` żywego tokena zwraca `122100378213334508`. accounts.json odpalić rekonfigurację SU ID przy okazji.

## Stan na koniec sesji

### Załatwione przez Janka (przed sesją + w sesji)
- ✅ **Adres firmy CEIDG-zgodny**: `ul. Jerzego Pleśniarowicza, nr 2a, lok. 38, Rzeszów, podkarpackie 35-117, Polska`. Wcześniej miał literówkę „Jeżego" i drugi adres Przemysłowa, kod 35-105.
- ✅ **Karta + waluta PLN** podpięte (Ruslan/Janek przed sesją)
- ✅ **Janek = ADMIN portfolio** (przed sesją)

### Decyzje odraczające (z powodem)

**1. Katalog Pojazdy — odłożony do promo SU (~2026-06-03)**

- Próba przez API: `POST /145818221430407/owned_product_catalogs?vertical=vehicles` odbita — SU=EMPLOYEE, Meta wymaga ADMIN portfolio dla `business_management` na poziomie create.
- Próba przez UI: nowy formularz Mety (2026, Menedżer sprzedaży) ma typ katalogu tylko: **Produkty fizyczne / Cyfrowe / Usługi / Lokalne / Inne**. **Brak typu Vehicles/Pojazdy.** Stary typ został wycofany z UI — vehicle catalog (AIA, Automotive Inventory Ads) wymaga `vertical=vehicles` ustawianego TYLKO przez API.
- Wniosek: utworzenie katalogu typu „Inne" lub „Fizyczne" w UI nie umożliwi załadowania vehicle feedu (schema mismatch). Meta UI wprost mówi: „Nie można tego zmienić później."
- Plan po promo SU: 1 wywołanie `POST /145818221430407/owned_product_catalogs?name=Prima-Auto Pojazdy&vertical=vehicles` + wpinanie feedu `https://primaauto.com.pl/wp-content/uploads/asiaauto-feed/meta-vehicles.csv` + pixel `2053908905193274` + schedule DAILY hour=5. Total ~5 minut.

**2. Vertical portfolio (NOT_SET → AUTOMOTIVE) — odłożone do promo SU**

- API odbity: `(#3910 / 1690011) You need permission to edit the details of your Business Manager`.
- UI nie ma odpowiednika pola w Business Info Edit (brak „Kategoria/Branża").
- Aktualizacja oceny: vertical NOT_SET **prawdopodobnie NIE jest blokerem weryfikacji UE**. Janek potwierdził że Meta odrzuciła konkretny dokument firmy Ruslana, nie kategorię. Vertical low-priority, zrobimy razem z katalogiem po promo SU.

**3. SU `122100378213334508` Employee → Admin — 7-dniowy cooldown**

- Meta nakłada 7-dniowy security guard po nadaniu admin portfolio (Janek dostał ADMIN niedawno) na zmianę ról innych userów / SU.
- Możliwe od ~2026-06-03 (sprawdzić w UI: `Ustawienia → Użytkownicy systemowi` → SU `122100378213334508` → Edytuj rolę).
- Workaround do tego czasu: krytyczne akcje wymagające ADMIN SU → przez UI Janka.

**4. Główna lokalizacja firmy — odpuszczona**

- Przycisk Edytuj w Business Info `aria-disabled: true`, `cursor: not-allowed`.
- Feature wymaga Meta Locations / Pages Locations lub Meta Verified Business — Janek/Prima nie ma.
- **NIE jest blokerem weryfikacji UE.** Adres Plac Przemysłowa 13A jest już w meta vehicle feed, GBP (sesja 2026-04-29), `class-asiaauto-contact.php`. Jeśli Janek chce dodatkowo — może na Stronie FB `facebook.com/prima1auto/about`.

**5. Weryfikacja firmy UE — czeka na Ruslana (re-upload dokumentu)**

- W Centrum zabezpieczeń → Weryfikacja firmy: status **„Weryfikacja organizacji PRIMA AUTO RUSLAN PRIMA — Rozpoczęto 27 maj 2026 — Oczekuje na wysłanie"** (Janek zaczął re-submit, przerwał gdy Ruslan zgłosił że wrzuca nowy dokument).
- Meta odrzuciła poprzedni dokument firmy Ruslana. Ruslan wrzuca nowy w swoim panelu.
- **Formularza weryfikacji nie dotykamy z Jankiem** — żeby nie konkurować z Ruslanem na re-submit.
- SLA Meta po wysłaniu: 1-3 dni robocze.

### Boczne flagi (z Centrum zabezpieczeń, do podjęcia kiedy indziej)

- Tylko 1/3 użytkowników portfolio ma 2FA — Meta to wytyka, low priority ale do włączenia (Janek i Ruslan).
- 1 user z publicznym domeną email (prawdopodobnie Ruslan).
- 1 nieaktywne konto reklamowe (możliwa stara appka).
- Brak peer approval na koncie reklamowym.

## Konsekwencje

**Plan tygodnia 2026-05-27 → 2026-06-03:**
1. **Ruslan**: re-upload dokumentu firmy w panelu Account Quality / Security Center. Po jego zgłoszeniu Meta przetwarza 1-3 dni robocze.
2. **Janek**: ewentualnie pomaga z 2FA dla Ruslana, dopilnowuje że Ruslan wie który dokument przekazać (CEIDG z aktualną nazwą + adresem).
3. **Claude (po promo SU ~2026-06-03)**:
   - `bash ~/secrets/meta/meta_call.sh --project primaauto-mktg "/145818221430407?vertical=AUTOMOTIVE" POST`
   - `POST /145818221430407/owned_product_catalogs?name=Prima-Auto Pojazdy&vertical=vehicles`
   - Wpinanie feedu CSV + pixel + dzienny harmonogram
4. **Po wszystkim (gdy weryfikacja UE = `verified`)**: dokończenie zestawu reklam `120248507523010111` + 5 reklam z postów + 2 reguły automatyczne dayparting + spend_cap (memory ma szczegóły).

**Daily check (~30s):**
```bash
bash ~/secrets/meta/meta_call.sh --project primaauto-mktg \
  "/145818221430407?fields=verification_status"
```
Gdy `verified` → odblokowana publikacja kampanii UE.

## Powiązane

- Memory: [[meta-pixel-capi-setup-primaauto]], [[meta-kampania-fb-build-planu-audiencje-2026-05-25]]
- ADR poprzednie: `2026-05-25-meta-pixel-gtm-faza2.md`, `auranet/docs/decyzje/2026-05-25-meta-ads-app-per-client.md`
- Reference: `~/secrets/meta/accounts.json`, `~/secrets/meta/meta_call.sh`
