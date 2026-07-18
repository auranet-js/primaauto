# Recheck SKAG-1 — review zdjęć grupowych + stan placu

> **✅ ZROBIONE 2026-07-16.** Zdjęcia z poziomu GRUPY działają w search (20 podpięć ELIGIBLE, 515 impr / 86 klik / 1 konw — Leopard 5).
> Wynik: `docs/sesje/2026-07-16-rechecki-t195-skag1.md` · memory `project_skag1_stan_2026_07_16.md`.
> Otwarte punkty (4 auta on_lot bez grupy, Jetour T2 0 impr, Ad Strength, A/B RSA) przeniesione do sesji — świadoma decyzja „nic nie ruszamy".

> Utworzono: 2026-07-10 · Termin: 2026-07-11 10:00 · Projekt: primaauto
> Konto Ads: 9506068500 (direct) · Kampania SKAG-1: 23803851563

## Kontekst (co się wydarzyło 2026-07-10)
Sesja optymalizacji Ads. Brand domknięty ręcznie przez Janka. SKAG-1:
- pauza martwej grupy „Denza N8L" (auto w trash),
- usunięto 7 wspólnych zdjęć WhatsApp z **poziomu kampanii** (dawały te same zdjęcia we wszystkich grupach),
- podpięto **14 zdjęć per model na poziomie GRUPY** (ad_group_asset AD_IMAGE) — inne zdjęcie dla każdej grupy.

**Niepewność do rozstrzygnięcia:** czy Google w kampaniach **search** honoruje obrazy z poziomu **grupy**. Edytor reklamy zarządza obrazami na poziomie **kampanii** („Dodaj obrazy do kampanii"), więc grupowe podpięcia tam się nie pokazują. Zdjęcia były `PENDING` (ASSET_UNDER_REVIEW).

## Co sprawdzić (KROK 0 — zawsze weryfikuj serwer)
1. **Status review** 14 podpięć:
   ```
   python3 tmp/gads_client.py 9506068500 "SELECT campaign.id, ad_group.name, ad_group_asset.primary_status, asset.name FROM ad_group_asset WHERE campaign.id=23803851563 AND ad_group_asset.field_type='AD_IMAGE' AND ad_group_asset.status!='REMOVED'"
   ```
   — czy `PENDING` → `APPROVED`?
2. **Czy zbierają wyświetlenia** (czy Google je serwuje):
   ```
   python3 tmp/gads_client.py 9506068500 "SELECT ad_group.name, asset.name, metrics.impressions FROM ad_group_asset WHERE campaign.id=23803851563 AND ad_group_asset.field_type='AD_IMAGE' AND segments.date DURING LAST_7_DAYS"
   ```

## RSA rework + sitelinki (wgrane 2026-07-10 wieczorem)
Doszło po podpięciu zdjęć: **14 nowych RSA** (2/grupa — wariant z ceną + bez ceny, copy „od ręki"/wyposażenie),
stare RSA usunięte, **4 dedykowane sitelinki** „od ręki" na poziomie kampanii. Skrypt: `tmp/gads_skag1_rsa_rework_2026_07_10.py`,
dump: `~/backups/primaauto/2026-07-10-skag1/rsa-before.json`. Sprawdzić jutro:
```
python3 tmp/gads_client.py 9506068500 "SELECT ad_group.name, ad_group_ad.ad_strength, ad_group_ad.policy_summary.approval_status FROM ad_group_ad WHERE campaign.id=23803851563 AND ad_group_ad.status='ENABLED'"
```
— czy RSA APPROVED, jaki Ad Strength. Sitelinki: `campaign_asset` SITELINK primary_status.
Po 1-2 tyg.: który wariant (A z ceną / B bez ceny) ma lepszy CTR/konwersje — i czy 0-konwersji SKAG-1 drgnęło.

## Decyzja
- **APPROVED + impressions rosną** → cel osiągnięty (różne zdjęcia per model). Nic nie robić.
- **APPROVED ale 0 impressions / Google ignoruje grupowe** → grupowe w search nie działają. Wybór z Jankiem:
  - (a) obrazy wspólne na poziomie **kampanii** (pewnie się wyświetlą, ale te same wszędzie — jak było z WhatsApp), albo
  - (b) rozbicie/inne podejście.
- **ODRZUCONE (policy)** → sprawdzić powód, ewentualnie inne kadry.

## Recheck placu (drugi cel, cykl co 2 tyg.)
- Nowe `on_lot` BEZ grupy w SKAG-1 (kandydaci do dodania): **Leopard 7 ×2 (317106, 387470), AITO M9 (362371), Dongfeng Rich 7 (355801)**.
- Martwe oferty (jak N8L 247010 → trash) → pauza grupy + repoint.
- Definicja placu: `_asiaauto_reservation_status = on_lot` (NIE `stm_car_location` — patrz memory `reference_on_lot_authoritative_not_car_location`).

## Narzędzia / artefakty
- Skrypt mutacji: `tmp/gads_skag1_rework_2026_07_10.py` (tryby: plan / `--run` / `--run --apply`)
- Dump „before" (rollback): `~/backups/primaauto/2026-07-10-skag1/before.json`
- Pula zdjęć: 24 image assety `onlot` w bibliotece konta + kadry na auratest `primaauto-onlot-brand-2026-07-10/`
- Klient API: `tmp/gads_client.py`
