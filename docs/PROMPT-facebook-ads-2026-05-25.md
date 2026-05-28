# PROMPT — wątek Facebook/Meta Ads dla primaauto.com.pl

> Wklej treść z sekcji „PROMPT DO WKLEJENIA" po uruchomieniu `cd ~/projekty/primaauto && claude`.
> Kontekst decyzyjny: ADR `~/projekty/auranet/docs/decyzje/2026-05-25-meta-ads-app-per-client.md`.

---

## PROMPT DO WKLEJENIA

Pracujemy nad **połączeniem primaauto.com.pl z Meta (Facebook/Instagram) i pomiarem reklam**. Cel Janka: (1) pełna kontrola + diagnostyka reklam i promowanych postów, (2) wiedzieć jaki **ruch** idzie z Facebooka, (3) wiedzieć jakie **konwersje** idą z Facebooka. Stan: **nic nie jest połączone** ze stroną — budujemy od zera.

Model ustalony (ADR 2026-05-25, app-per-client): setup Meta robimy **w tym projekcie**, w portfolio klienta. Dla pomiaru NIE potrzebujemy osobnej appki deweloperskiej — wystarcza **Piksel + CAPI z tokenem datasetu** z Events Managera.

### Co już istnieje (Meta strona)
- **Portfolio: Prima Auto** — Business ID `145818221430407`, 1 konto reklamowe, 2 strony. Janek ma pełną kontrolę.
- Stara appka **„PrimaAuto"** (App ID `765618113211634`, właściciel: portfolio Prima Auto, *In development*) — do audytu (czy ma żywą integrację) i najpewniej wygaszenia. NIE wygaszać bez sprawdzenia.
- Zaparkowana appka **„Auranet API"** (App ID `1012248818452132`, niepodpięta) — opcjonalnie do rehome jako appka Marketing API PrimaAuto **dopiero** gdy wejdziemy w programowe zarządzanie kampaniami. Do pomiaru niepotrzebna.
- Konto deweloperskie appek: `js@duonet.eu`.

### Specyfika primaauto.com.pl (ważne dla eventów)
- NIE standardowy checkout Woo. Własny CPT `listings` (auta) + `asiaauto_order` (11 statusów, **model depozytowy**, umowa PDF). Realne konwersje ≠ Woo „Purchase".
- Slugi wewnętrzne `asiaauto_*` zostają (klasy/CPT/meta/REST). Rebranding tylko user-facing.
- Płatności: PayU (depozyt) — `reference_payu_sandbox`.
- Zgody: **Complianz / Consent Mode v2** — CAPI wysyła eventy server-side tylko przy zgodzie marketingowej (`reference_complianz_consent_mode`).
- Hosting Hostido **open_basedir**: PHP-FPM nie czyta `~/secrets/` → token CAPI musi mieć kopię w `<domain>/.private/` (chmod 600), patrz `reference_hostido_open_basedir`.

### Plan (fazami)
1. **Events Manager (panel Meta, portfolio Prima Auto):** utwórz **Piksel/dataset**, zapisz **Pixel ID**, wygeneruj **token CAPI (dataset access token)**. Token → `~/secrets/meta/tokens/primaauto-capi.txt` (CLI) + kopia do `<domain>/.private/primaauto-capi.txt` (PHP-FPM). Uzupełnij `dataset_id` i `pixel_id` w `~/secrets/meta/accounts.json` (sekcja `primaauto`).
2. **Piksel base (browser) na primaauto.com.pl:** `PageView` globalnie + `ViewContent` na karcie listingu. Wpięcie z poszanowaniem Complianz (consent gating). Rozważyć GTM vs bezpośredni snippet.
3. **Eventy konwersji — CAPI server-side**, hooki w lifecycle `asiaauto_order`:
   - `Lead` — zapytanie / WhatsApp / formularz kontaktu
   - custom `DepositPaid` — wpłata depozytu (PayU / zmiana statusu zamówienia)
   - custom `ContractSigned` — podpisanie umowy
   - (opcjonalnie) `InitiateCheckout` na starcie zamówienia
   - **Wspólny `event_id` browser↔server** → deduplikacja. PII (email/phone) **SHA256** po normalizacji, nigdy plaintext.
   - Helper do testu: `~/secrets/meta/capi_send.sh --project primaauto <EVENT> <TEST_EVENT_CODE>` (po uzupełnieniu `dataset_id` w accounts.json).
4. **Weryfikacja:** `test_event_code` w Events Managerze pokazuje eventy, **Event Match Quality** > akceptowalny próg, deduplikacja browser↔server działa.
5. **(Opcjonalnie, później)** Marketing API — programowy odczyt Insights (ruch/konwersje/ROAS/CPA) + zarządzanie kampaniami. Wtedy: rehome appki „Auranet API" do portfolio Prima Auto (rename) albo użyj/napraw starą `765618113211634`. Helper `~/secrets/meta/meta_call.sh` (po uzupełnieniu app_id/secret/token per projekt — patrz niżej).

### Najpierw zrób
- Audyt starej appki `765618113211634` (czy coś żywego — pixel/integracja) → `docs/decyzje/`.
- Sprawdź obecny stan trackingu na primaauto.com.pl (powinno być: zero Meta).
- Potwierdź z Jankiem które **konto reklamowe** Prima Auto jest docelowe (jest 1).

### Reguły / referencje
- Sekrety NIGDY w repo. Struktura: `~/secrets/meta/` (helpery + `accounts.json`). Memory: `reference_meta_marketing_api`, `reference_hostido_open_basedir`, `reference_complianz_consent_mode`, `reference_payu_sandbox`.
- Helpery zakładały jedną centralną `app.json`; przy app-per-client trzymaj `app_id`/`app_secret`/`token` **per projekt w `accounts.json`** (drobny refactor helperów, jeśli wejdziemy w Marketing API).
- Maile dot. klienta → wyłącznie do Janka (`js@auranet.com.pl`), nigdy do klienta.
- Commity decyduje Janek. Decyzje → `docs/decyzje/YYYY-MM-DD-*.md`.

### Definicja sukcesu (mierzalna)
- Events Manager pokazuje eventy CAPI z primaauto.com.pl (`test_event_code`), EMQ > próg, dedup bez podwójnego liczenia.
- W Ads Managerze / GA4 widać ruch i konwersje przypisane do kampanii FB.
