# Recheck Meta — dostawa stoi od 18.09 (raport, 2026-09-20)

> Zakres: odczyt stanu konta, insights per reklama, trend dzienny, weryfikacja w GA4.
> **Nic nie zmieniane** — kampanie, budżety, reklamy, GTM i strona nietknięte.
> Poprzedni recheck: `docs/sesje/2026-09-17-recheck-meta-po-przebudowie.md`.

## Wniosek w jednym zdaniu

Konto nie wydaje nic od **18.09 godz. 12:00** — nie „słabo idzie", tylko stoi; przyczyną jest
karencja płatnicza (karta Mastercard \*3357 po stronie Ruslana), a nie żadne ustawienie kampanii.

## Awaria — chronologia z dziennika konta

Statusy nie mówią nic: `account_status: 1`, `disable_reason: 0`, 4 reklamy `ACTIVE`,
`ads_volume` = 4, `issues_info` puste, `HAS_VALID_PAYMENT_METHODS` w `all_capabilities`,
budżety zestawów nienaruszone (35 + 15 + 15 zł). Przyczyna widoczna wyłącznie
w `/act_1038563008906171/activities`:

| 18.09 (czas PL) | Zdarzenie |
|---|---|
| 11:36 | `ad_account_update_status`: Aktywne → **„Obowiązuje okres karencji"** |
| 11:51 | cztery `ad_account_billing_charge`: 5,02 + 10,04 + 20,08 + 5,05 zł → status wraca na „Aktywne" |
| po 12:00 | wyświetlenia się kończą i **nie wracają** |

Sygnatura kłopotu z kartą: 16.09 i 17.09 Meta pobierała po 40 zł **jednym** obciążeniem,
18.09 musiała zejść do czterech drobnych prób. Źródło finansowania: `funding_source_details`
→ Mastercard \*3357. Niezapłacone saldo konta: 3,19 zł. Wydane łącznie 811,40 zł
(limit 1 000 zł nie ma tu znaczenia — nie został osiągnięty).

Godzina śmierci dostawy z `breakdowns=hourly_stats_aggregated_by_advertiser_time_zone` za 18.09:
ostatnia doba z wydatkiem to przedział 11:00–11:59 (2,91 zł, 218 wyświetleń), dalej zero.

**Weryfikacja niezależna od Mety** — sesje GA4 z `facebook / paid_social`:
18.09 — 39, 19.09 — **2**, 20.09 — **1**. Zgadza się z zerem po stronie Mety.

## Tabela per reklama — 17.09, ostatnia pełna doba z dostawą

| Reklama | Wydane | Wyśw. | Klik. w link | CTR | zł/klik | Sesje GA4 | zł/sesja | Contact |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| `[KAT]` Oferta — nowi odbiorcy | 32,71 zł | 3 678 | 350 | 9,52% | 0,09 zł | 98 | **0,33 zł** | 0 |
| `[RMKT]` Oglądane — 30 dni | 14,11 zł | 1 253 | 177 | **14,13%** | 0,08 zł | 49 | **0,29 zł** | 0 |
| `[FOTO]` Cała oferta — kadr 1 | 5,85 zł | 527 | 28 | 5,31% | 0,21 zł | 4 | 1,46 zł | 0 |
| `[FOTO]` Cała oferta — kadr 2 | 1,75 zł | 166 | 8 | 4,82% | 0,22 zł | 3 | 0,58 zł | 0 |
| **Razem** | **54,42 zł** | **5 624** | **563** | — | 0,10 zł | **154** | **0,35 zł** | **0** |

## Trend dzienny

| Doba | Wydane | Sesje GA4 | zł/sesja | Kontakty GA4 | Contact (piksel) |
|---|---:|---:|---:|---:|---:|
| 15.09 (przebudowa 17:15) | 70,81 zł | 168 | 0,42 zł | **4** | 3 |
| 16.09 | 44,06 zł | 117 | 0,38 zł | **2** | 1 |
| 17.09 | 54,42 zł | 154 | 0,35 zł | 0 | 0 |
| 18.09 (do 12:00) | 16,55 zł | 39 | 0,42 zł | 0 | 0 |
| 19.09 | **0,00 zł** | 2 | — | 0 | 0 |
| 20.09 | **0,00 zł** | 1 | — | 0 | 0 |

Kontakty GA4 to wyłącznie `click_phone` i `click_whatsapp` (zgodnie z
`reference_ga4_click_to_outbound_nie_zadzwon` nie liczę zdarzenia `click`). Wszystkie sześć
pochodzi z `[KAT] Oferta — nowi odbiorcy`; żadna inna reklama nie ma kontaktu.

## Co poza awarią widać w liczbach

**Koszt ruchu trzyma poziom po przebudowie** — 0,35–0,42 zł za sesję wobec 0,59 zł w fazie C
(12–14.09). `[RMKT]` ma najwyższy CTR na koncie (14,13%) i najtańszą sesję (0,29 zł).

**Kontakty wyschły po dwóch dobach** — 4 i 2, potem zero przy 71 zł wydanych 17–18.09.
Na 6 kontaktów ze 185,84 zł (15–18.09) wychodzi 31 zł za kontakt, ale seria jest za krótka,
żeby traktować to jako stawkę.

**`[FOTO]` wyszedł z dławienia** — 17. i 18.09 wydał po ~5–6 zł przy CTR 5,31% i 7,11%
(16.09 było 1,38% i 2,19 zł). Zestaw na `Contact` zaczął się uczyć dokładnie wtedy,
gdy konto stanęło.

## Do decyzji (nic nie wykonane)

1. **Odblokowanie płatności — u Ruslana.** Przez API nie ponowimy obciążenia. Potrzebne od niego:
   wejście w płatności konta reklamowego, sprawdzenie czy karta \*3357 nie wymaga potwierdzenia
   (3-D Secure) albo nie ma limitu na płatności internetowe, i opłacenie zaległości.
2. **Cztery pytania z rechecku 17.09** (cel `[FOTO]`, niewykorzystane 21 zł, wiek 65+, Instagram)
   zostają otwarte. Do czasu wznowienia dostawy są bezprzedmiotowe, a `[FOTO]` zdążył dać inne
   liczby niż te, na podstawie których pytałem.

## Otwarte z poprzednich sesji (przypomnienie, nie działanie)

- Przycisk Messengera na ofercie — makieta `primaauto-makieta-messenger-oferta-2026-09-15.html`, wariant A/B.
- `gclid` wycinany w GTM od v13 — decyzja z 11.09 nadal otwarta; zaniża `google / cpc` w GA4.
