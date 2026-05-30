# Pre-flight triage hubów do reworku — kontaminacja taksonomii (2026-05-30)

> Skan: `wp eval-file` brand-w-post_titles vs `_serie_full_title` dla wszystkich serie count≥18 (minus zrobione).
> `pure%` = udział dominującej marki w post_titles. XXXX = kontaminacja (skip, decyzja taksonomiczna). OK = czysty (można reworkować).
> Kontekst: metoda `hub-rework-method-2026-05-30.md`, memory `project_session_2026_05_30_hub_rework_pilot`.

## ⚠️ SKONTAMINOWANE — NIE reworkować, wymaga konsolidacji taksonomii (jak V61/V62/V63 merge)

| term | full_title | count | pure | faktyczna zawartość | problem |
|---|---|---|---|---|---|
| 4409 | Haval H5 | 60 | 100% | **Hongqi H5** | marka błędna; DECYZJA Janka: zostawić jak jest |
| 3399 | Geely Galaxy L6 | 35 | 97% | **Li Auto L6** (EREV) | 34/35 to Li Auto, nie Geely; dedykowany term `7052 Li Auto L6` PUSTY |
| 3401 | Geely Galaxy L7 | 35 | 63% | **mix** Geely Galaxy L7 (PHEV ~13) + Li Auto L7 (EREV ~22) | dwa różne auta; term `7055 Li Auto L7` PUSTY |
| 4259 | Volkswagen Lavida Jetta | 35 | 77% | mix VW Jetta + VW Lavida | dwa różne chińskie VW sedany w jednym termie |
| 6539 | Galaxy A7 EM-i | 29 | 69% | Geely Galaxy A7 + CJK „银河A7" w title | CJK w post_title + pusty full_title (był NULL) |
| 4398 | Haval H6 | 25 | 52% | **mix** Haval H6 + Hongqi H6 | ta sama kontaminacja Hongqi co H5 |
| 4812 | Avatr 07 | 24 | 63% | **mix** WEY 07 + Avatr 07 | WEY wpadł do Avatr |

**Duplikaty termów L6/L7 do uporządkowania:** `3399 Galaxy L6`(35) / `6587 Galaxy L6`(14) / `5735 L6`(8) / `7052 Li Auto L6`(0); `3401 Galaxy L7`(35) / `6589 Galaxy L7`(5) / `5739 L7`(9) / `7055 Li Auto L7`(0). Plus inne L7: IM L7, Shanhai L7, Fengshen L7, Jetour Shanhai L7. **To zadanie osobnej sesji konsolidacji** (move listingów do właściwych make/serie + redirecty), nie content.

## ✅ CZYSTE — bezpieczne do reworku (wg count desc)

5074 Voyah Dream PHEV (34) · 4265 VW Lamando (34) · 3978 Tank 300 (34) · 5675 Luxeed R7 (32) · 3936 Nissan Sylphy (31) · 5737 Li Auto L9 (31) · 4809 Avatr 06 (30) · 3646 Geely Atlas Pro (28) · 4534 Jetour T2 Traveller (27) · 4324 NIO ES8 (27) · 3728 BYD Song L (27) · 4823 Zeekr 001 (25) · 5156 Leapmotor C10 (25) · 4762 XPENG G7 (25) · 6052 XPENG P7+ (24) · 4252 VW Tayron (24) · 4653 Denza D9 DM-i (24) · 5736 Li Auto L8 (23) · 4274 VW Tiguan L LWB (23) · 4810 Avatr 11 (23) · 4827 Zeekr 009 (23) · 3702 BYD Seal U DM-i (23) · 3380 GAC Empow (22) · 4281 VW Magotan (21) · 3706 BYD ATTO 3 (20) · 3949 Nissan N7 (20, 85% — drobny bleed Denza N7) · 5155 Leapmotor C11 (20) · 4826 Zeekr 007 GT (20) · 5184 Chery Fulwin T9 (20) · 4301 VW ID.3 (19) · 3584 Chery Tiggo 8 Pro (19) · 4780 BAIC BJ40 (19) · 3582 Chery Tiggo 9 (19) · 3761 BYD Han L EV (19) · 5151 Xiaomi SU7 Ultra (18) · 4323 NIO EC6 (18)

**Reguła:** pre-flight (ten skan) PRZED każdym kolejnym hubem; pure<80% lub marka≠full_title → flaga, nie content.
