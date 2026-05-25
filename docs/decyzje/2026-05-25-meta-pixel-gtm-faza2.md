# ADR: Meta Pixel + konwersje przez GTM (Faza 2/2b) — 2026-05-25

> **Status:** wdrożone na produkcję (Jan Schenk, 2026-05-25)
> **Powiązane:** ADR `auranet/docs/decyzje/2026-05-25-meta-ads-app-per-client.md`, prompt `docs/PROMPT-facebook-ads-2026-05-25.md`, memory `project_meta_pixel_capi_setup`, `feedback_gtm_default_workspace_api_only`

## Kontekst
Podpięcie primaauto.com.pl do Meta (FB/IG Ads) — pomiar ruchu i konwersji. Stan wyjściowy: zero Meta na stronie (był tylko GTM `GTM-T4GTZ2JB` + GA4 + Complianz Consent Mode v2). Pixel/dataset `2053908905193274`, portfolio Prima Auto `145818221430407`, konto reklamowe `1083673765606618`.

## Decyzje

1. **Piksel przez GTM, nie snippet w motywie** — spójne z istniejącym Consent Mode v2 (Complianz) i dataLayer (GA4 ecommerce). Zero edycji kodu produkcyjnego w Fazie 2.

2. **Praca TYLKO w Default Workspace, przez GTM API** (account 6351095501 / container 250095450). Konwencja klienta: nigdy nie tworzyć dodatkowych workspace'ów. ID Default Workspace zmienia się po każdej publikacji — nie hardcode'ować, szukać po `name=='Default Workspace'`.

3. **Reuse istniejących zasobów GTM** zamiast tworzenia nowych:
   - `Contact` (klik telefon/WhatsApp) → istniejące triggery `CE - click_phone` / `CE - click_whatsapp`.
   - `Lead` (formularz/zamówienie) → istniejący `CE - generate_lead` + reuse `DLV - value` / `DLV - listing_id`.
   - Nowe tylko tam gdzie nie ma odpowiednika: 3× DLV ecommerce (`ec_content_id`/`ec_value`/`ec_currency` z `view_item`) + trigger `CE - view_item` (GA4 zjada ecommerce automatycznie przez tag konfiguracyjny, więc triggera nie było; Meta to nie Google-tag → musi mieć własny).

4. **Mapowanie eventów spójne z Google Ads** (sprawdzone `conversion_action.category`, customer 9506068500):
   | Zdarzenie | Ads kategoria | Meta event |
   |---|---|---|
   | click_phone | CONTACT | `Contact` |
   | click_whatsapp | CONTACT | `Contact` |
   | generate_lead | SUBMIT_LEAD_FORM | `Lead` |
   | (page load karty) | — | `ViewContent` |
   | (każda strona) | — | `PageView` |

5. **Consent gating przez Consent Mode** — wszystkie tagi Meta (Custom HTML) mają `consentSettings: needed` dla `ad_storage` + `ad_user_data`. GTM wstrzymuje piksel do zgody marketingowej (Complianz emituje `gtag('consent','default'/'update')`). Bez własnego JS gatingu.

6. **AAM (Automatic Advanced Matching) wyłączone** — dopasowanie PII robimy server-side w CAPI (Faza 3).

7. **Dataset Quality API odrzucone** przy generowaniu tokena CAPI — opcja nieodwracalna, a EMQ widać w UI; wzięty zwykły (odwracalny) token CAPI.

## Stan na produkcji (live wersja 7 „Meta Pixel — Contact + Lead")
5 tagów Meta: `Meta Pixel - Base` (PageView, All Pages), `Meta Pixel - ViewContent` (`CE - view_item`), `Meta Pixel - Contact (phone)`, `Meta Pixel - Contact (whatsapp)`, `Meta Pixel - Lead`. Wszystkie consent-gated. Potwierdzone w opublikowanym `gtm.js`.

## Konsekwencje / TODO
- **CAPI server-side (hooki w zamówieniach) — ODRZUCONE 2026-05-25.** Janek nie wyraził zgody na dotykanie kodu zamówień („strefa krucha" §3) i słusznie: GA4/Ads też nie ruszają orderów — `generate_lead`/`click_*` lecą z przeglądarki (JS dataLayer), a głębsze eventy (purchase/deposit/contract) w GA4/Ads są zadeklarowane ale w kodzie niepodpięte. **Meta browser-side = pełny parytet z GA4/Ads, cel osiągnięty.** Token CAPI istnieje (`~/secrets/meta/...`), ale niepodpięty. Gdyby kiedyś trzeba odporności (adblock/iOS) — robić **bez** dotykania orderów (np. osobny endpoint/server-side GTM), nie przez surgery na `class-asiaauto-order.php`.
- **Audyt starej appki/datasetu** `765618113211634` (widnieje z ⚠️) — użyć/wygasić. NIE dotyka strony ani zamówień.
- `page_id` w `~/secrets/meta/accounts.json` nadal FILL (do pomiaru niepotrzebny; tylko Marketing API).
- Rollback GTM: republish wersji 5 lub 6.
