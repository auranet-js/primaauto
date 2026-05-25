# PROMPT — Meta kampanie FB: audiencje + promowanie postów (planowanie) — 2026-05-25

> Wklej sekcję „PROMPT DO WKLEJENIA" po `cd ~/projekty/primaauto && claude`.
> Poprzednie: ADR `docs/decyzje/2026-05-25-meta-pixel-gtm-faza2.md` (pomiar). Memory: `project_meta_pixel_capi_setup` (PRZECZYTAJ NAJPIERW), `feedback_gtm_default_workspace_api_only`.

---

## PROMPT DO WKLEJENIA

Budujemy **plan kampanii Facebook/Instagram dla primaauto.com.pl**. Pomiar jest już gotowy (poprzednia sesja). Teraz **planowanie**: przede wszystkim **listy odbiorców (custom audiences) do dodawania/wykluczania** + **metoda promowania codziennych postów ofertowych**. Cel tej sesji: **stworzyć kompletny plan** (to jest „plan zrobienia planu" — najpierw ustalamy elementy, potem składamy w gotowy do odpalenia plan). **Najpierw przeczytaj memory `project_meta_pixel_capi_setup`.** Odpalenia/wydatku NIE robi Claude — kampanie uruchamia Janek w Ads Managerze (albo budujemy szkielet WSTRZYMANY przez API).

### Stan gotowy (NIE robić od nowa)
- **Pomiar LIVE** (GTM): Pixel `2053908905193274`, eventy `PageView`/`ViewContent`/`Contact`(tel+WA)/`Lead`(formularz), consent-gated, zweryfikowane realnym strzałem do Meta. Spójne z GA4/Ads (Contact=CONTACT, Lead=SUBMIT_LEAD_FORM).
- **Marketing API LIVE:** token never-expire `~/secrets/meta/tokens/primaauto-mktg.txt`, wywołania `bash ~/secrets/meta/meta_call.sh --project primaauto-mktg <PATH>` (ads_read + zarządzanie). System User `61590035266690`, appka PrimaAuto `765618113211634`.
- **Konto reklamowe `act_1083673765606618`:** AKTYWNE, **waluta UAH**, TZ Europe/Berlin, **0 kampanii / 0 audiencji / 0 wydatków**, min budżet 44 UAH.
- **Strona FB `prima1auto`** („PRIMA AUTO - Import aut Chiny Korea"): **16k followersów**, organiczny reach martwy (~2 reakcje/post mimo 16k), **codzienne posty konkretnych ofert** (z linkiem do strony) — np. „Xiaomi SU7 Ultra".
- **Budżet:** ~520 UAH/dzień (≈ 50 zł).

### Cel biznesowy (Janek)
Promować codzienne posty ofertowe **do NOWYCH ludzi** w wybranych miastach i wieku → ściągnąć świadomych prospektów na stronę (oglądają zdjęcia/statystyki aut). Konwersje raczej Contact/ViewContent niż Lead (nikt nie rezerwuje auta za 300k przez FB od razu).

### „Plan zrobienia planu" — co ustalić w tej sesji
1. **Audiencje (rdzeń tematu):**
   - **Wykluczające** (żeby promocja trafiała tylko do NOWYCH): a) Page/IG engagers (dostępne od razu z 16k bazy), b) Website Custom Audience z pixela (rośnie od dziś), c) (opcjonalnie) lista klientów — **UWAGA PII: upload listy mailowej = hashowanie + podstawa prawna/zgoda; domyślnie NIE, chyba że Janek potwierdzi legal basis**. Wyklucz a+b z prospectingu.
   - **Lookalike:** z page-engagers / followersów / website visitors / (później) leadów.
   - Budowa: przez Marketing API (`/act_.../customaudiences`) — pixel WCA + engagement + lookalike (bez PII). Rozpisać które, jakie okna (np. visitors 180d), nazewnictwo.
2. **Cadence postów:** ustalić jak często klient publikuje oferty (przypisać Stronę do System Usera → `pages_read_engagement`/`read_insights` przez API i policzyć posty/okres; albo zapytać klienta). Zdecydować rytm promocji (które posty boostować, rotacja kreacji).
3. **Metoda promocji:** 1 kampania CBO „Ruch → Wyświetlenia strony docelowej" (NIE surowy boost per post — fragmentuje budżet/uczenie), kreacje = istniejące posty ofertowe („Użyj istniejącego posta"), nowe dorzucać/stare pauzować. Targeting: PL duże miasta (Warszawa/Kraków/Wrocław/Poznań/Trójmiasto/Śląsk/Łódź) + wiek 35-60 + zainteresowania auto/EV **MINUS audiencje wykluczające**.
4. **Faza konwersji (później):** kampania „Sprzedaż"/optymalizacja `ViewContent` + remarketing (visitors którzy oglądali auta) + lookalike.
5. **Budżet/waluta/harmonogram:** ~520 UAH/dz, Berlin TZ.
6. **(Później) AIA / katalog pojazdów:** Meta Automotive Inventory Ads działa w PL (w przeciwieństwie do Google) — dynamiczny remarketing dokładnie oglądanych aut. Wymaga feedu pojazdów + `content_type: vehicle`. Osobny mocny etap, nie na start.

### Najpierw zrób
- Przeczytaj memory `project_meta_pixel_capi_setup`.
- Przez API (`--project primaauto-mktg`) sprawdź realny stan: audiencje (`/customaudiences`), kampanie, ewentualnie przypisz Stronę do SU i policz cadence postów.
- **Zaproponuj listę audiencji do utworzenia** (nazwy, typy, okna, które wykluczać/lookalike) i **plan kampanii v1** — czekaj na akcept Janka. Audiencje pixel/engagement/lookalike mogę utworzyć przez API; listę klientów (PII) tylko po potwierdzeniu legal basis.

### Reguły
- Claude **nie wydaje budżetu** — kampanie odpala Janek w Ads Managerze, albo budujemy szkielet WSTRZYMANY przez API (zero wydatku do aktywacji).
- GTM: tylko Default Workspace + API. Sekrety nigdy w repo. Maile dot. klienta → tylko do Janka.
- Decyzje → `docs/decyzje/`. Commity/push decyduje Janek.

### Definicja sukcesu (tej sesji)
Gotowy, zaakceptowany **plan**: lista audiencji (wykluczające + lookalike) z nazwami/oknami, metoda promocji postów, targeting, budżet w UAH, harmonogram — gotowy do odpalenia przez Janka.
