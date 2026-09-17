# Recheck Meta po przebudowie — doba 16.09 (raport, 2026-09-17)

> Wykonane z przypomnienia kalendarzowego `🔔 [primaauto] Meta po przebudowie — tabela per reklama za 16.09`.
> Zakres eventu: **część 2 promptu** `docs/sesje/2026-09-16-PROMPT-recheck-meta-i-telefony.md` (2a–2e),
> **tylko odczyt i raport**. Część 1 (skąd telefony) nie była w zakresie tego przypomnienia — nie robiona.
> Nic nie zmieniane: kampanie, budżety, reklamy, GTM i strona nietknięte.

## Wniosek w jednym zdaniu

Przebudowa z 15.09 działa: koszt sesji GA4 spadł z **0,59 zł** (faza C, 12–14.09) do **0,38 zł** (16.09),
a konto po raz pierwszy od startu ma kontakty przypisane reklamie — 15.09 cztery, 16.09 dwa, wszystkie z `[KAT]`.
Zgrzyt jest jeden: **`[FOTO]` na Contact się dławi** i wydaje 3,28 zł z 15 zł, przez co konto wykorzystało
44,06 zł z 65 zł dziennych.

## 2b. Tabela per reklama — pełna doba 16.09

GA4 za 16.09 domknięte (0,1% sesji bez przypisania), więc liczby są porównywalne.

| Reklama (zestaw) | Wydane | Wyśw. | CTR w link | Klik. w link | zł/klik | LPV Meta | Sesje GA4 | zł/sesja GA4 | Zaang. GA4 | ViewContent | Contact |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| `[KAT]` Oferta — nowi odbiorcy (`[KAT] PL M 30-60 — cały katalog`) | 26,72 zł | 3 958 | 9,22% | 365 | 0,07 zł | 124 | **74** | **0,36 zł** | 58 | 171 | **1** |
| `[RMKT]` Oglądane — 30 dni | 14,06 zł | 1 405 | 10,68% | 150 | 0,09 zł | 47 | **42** | **0,33 zł** | 27 | 57 | 0 |
| `[FOTO]` Cała oferta — kadr 1 (`Karuzele PL M 30-60 — Contact`) | 2,19 zł | 218 | 1,38% | 3 | 0,73 zł | 1 | 1 | 2,19 zł | 1 | 0 | 0 |
| `[FOTO]` Cała oferta — kadr 2 (ten sam zestaw) | 1,09 zł | 94 | 2,13% | 2 | 0,55 zł | 0 | 0 | — | 0 | 0 | 0 |
| **Razem** | **44,06 zł** | **5 675** | — | **520** | **0,08 zł** | **172** | **117** | **0,38 zł** | **86** | **228** | **1** |

Porównanie z fazą C (12–14.09, sprzed przebudowy): **69,06 zł/dz → 44,06 zł**, **0,59 → 0,38 zł/sesja GA4**,
**0 → 2 kontakty GA4** z reklamy. Jakość ruchu też w górę: `[KAT]` ma 162 s średniej sesji i 3,34 odsłony na sesję.

**Uwaga metodyczna:** LPV Meta (172) i sesje GA4 (117) się nie zgadzają i nie powinny — LPV zawyża,
dlatego kosztem operacyjnym jest zł/sesja GA4, nie zł/LPV.

**Kontakty GA4 z `facebook / paid_social`** (zdarzenia `click_phone` i `click_whatsapp`, po `utm_content`):

| Doba | Reklama | click_phone | click_whatsapp | Razem |
|---|---|---:|---:|---:|
| 15.09 | `[KAT]` Oferta — nowi odbiorcy | 1 | 3 | **4** |
| 16.09 | `[KAT]` Oferta — nowi odbiorcy | 1 | 1 | **2** |

Żadna inna reklama nie ma kontaktu. Contact w pikselu po stronie Mety (`fb_pixel_custom`, 1d_click):
15.09 — 3, 16.09 — 1, 17.09 do godziny raportu — 0.

## 2a. Stan konta i alarmy

Bez alarmów: 4 reklamy `ACTIVE`, zero `DISAPPROVED` / `WITH_ISSUES`, `account_status: 1`, `[KAT]` wyszedł
z weryfikacji i chodzi normalnie. Wydane na koncie 789,75 zł.

Budżety dzienne zestawów: `[KAT]` 35 zł, `[RMKT]` 15 zł, `Karuzele — Contact` 15 zł (razem 65 zł).
Realizacja 16.09: `[KAT]` 26,72 z 35 (76%), `[RMKT]` 14,06 z 15 (94%), `[FOTO]` **3,28 z 15 (22%)**.

## 2c. Per kampania

**`[KAT]` — katalog rozkłada się szeroko, ale płytko.** 16.09 wydatek dotknął **173 różnych aut**;
najdroższe pojedyncze auto to 0,20 zł (Denza D9 DM-i), drugie 0,19 zł (Changan UNI-V). Rozbicie po `product_id`
obejmuje jednak tylko **5,19 zł z 26,72 zł (19%)** — reszta wydatku nie ma przypisanego produktu,
więc „ile aut realnie dostało budżet" jest z tego **niepoliczalne** i tak to traktuję.

Demografia: **96% wydatku poszło na mężczyzn** (25,69 z 26,72 zł), kobiety 0,82 zł, nieokreśleni 0,21 zł.
Najlepszy przedział to **mężczyźni 35–44** (8,40 zł, 138 kliknięć, 0,06 zł/klik), najsłabszy **65+**
(1,70 zł, 4 kliknięcia, 0,43 zł/klik — siedmiokrotnie drożej). Miejsca: Facebook feed 26,26 zł,
Instagram feed 0,46 zł — Instagram praktycznie nie bierze udziału.

Koszt sesji GA4 wobec tego, co `[KAT]` zastąpił: **0,36 zł** przy `[VID]` 0,78 zł — czyli ponad dwukrotnie taniej.

**`[FOTO]` na Contact — dostawa się nie rozkręciła.** Zestaw zoptymalizowany na `Contact` (zdarzenie rzadkie)
wydał 22% budżetu, CTR kadru 1 spadł do 1,38% (przy 4,63% w starym zestawie na ruch), a sesji GA4 przyniósł
**jedną**. To klasyczne dławienie zestawu na rzadkim zdarzeniu — Meta nie ma na czym się uczyć.
`learning_stage_info` nie zwraca danych dla tego zestawu (pole puste), więc fazy uczenia **nie potwierdziłem**;
sam fakt dławienia widać po realizacji budżetu i zerowych konwersjach.

**`[RMKT]` przy 15 zł — zdrowo.** Częstotliwość **1,19** (alarm dashboardu to ≥ 4, daleko), zasięg 1 178 osób
na dobę, CTR w link 10,68% — najwyższy na koncie. Zero kontaktów, ale koszt sesji 0,33 zł jest najlepszy.

## 2d. Trend dzienny (konto, 12–17.09)

| Doba | Wydane | Wyśw. | Klik. w link | LPV | Sesje GA4 z `facebook / paid_social` | zł/sesja | Contact (piksel) |
|---|---:|---:|---:|---:|---:|---:|---:|
| 12.09 | 61,34 zł | 7 486 | 390 | 184 | 111 | 0,55 zł | 0 |
| 13.09 | 78,22 zł | 8 867 | 395 | 197 | 124 | 0,63 zł | 0 |
| 14.09 | 67,65 zł | 8 051 | 336 | 159 | 86 | 0,79 zł | 0 |
| **15.09 — przebudowa (ok. 17:15)** | 70,81 zł | 9 527 | 554 | 188 | 168 | 0,42 zł | 3 |
| **16.09 — pierwsza pełna doba po** | 44,06 zł | 5 675 | 520 | 172 | 117 | **0,38 zł** | 1 |
| 17.09 (niedomknięta, GA4 68% bez przypisania) | 49,91 zł | 5 228 | 519 | 159 | 43 | — | 0 |

Doba 17.09 jest **niedomknięta** — GA4 uzupełnia przypisanie przez 2–3 doby, więc sesji i kosztu sesji
z niej nie liczę. Po stronie Mety 17.09 wygląda jak 16.09: podobny wydatek, CTR i liczba kliknięć.

## 2e. Pomiar — TAK/NIE z dowodem

| Sprawdzane | Wynik | Dowód |
|---|---|---|
| GTM wersja live | **TAK, 15** | `versions:live` → `containerVersionId: 15`, „Meta Pixel — Base raz na zdarzenie + strażnik init" |
| ViewContent poniżej ~50% PageView | **TAK** | 16.09: 984 VC na 2 846 PV = **34,6%**; przed naprawą 12–14.09: 95,6% / 94,9% / 96,0% |
| Contact w pikselu rośnie | **TAK** | 12–14.09: 4 / 2 / 2 → 15.09: **16**, 16.09: **13**, 17.09: 10 |
| Dashboard `checki.json` | **TAK, 13 z 13** | `scripts/checki-pomiaru.py` → „13 gra · 0 do sprawdzenia · 0 awaria" |
| Budżet dzienny konta = 65 zł | **TAK** | zestawy 35 + 15 + 15 |
| UTM-y na żywych kreacjach | **TAK, 4 z 4** | check „Meta — UTM-y na kreacjach": wszystkie 4 otagowane |
| `utm_content` = nazwa reklamy w GA4 | **TAK** | sesje 16.09 przypisane do `[KAT] Oferta — nowi odbiorcy`, `[RMKT] Oglądane — 30 dni`, `[FOTO] Cała oferta — kadr 1` |

Zgodnie z memory `reference_ga4_click_to_outbound_nie_zadzwon` nie liczę zdarzenia `click` jako kontaktu —
w tabelach są wyłącznie `click_phone` i `click_whatsapp`.

## Do decyzji Janka (nic nie wykonane)

1. **`[FOTO]` na Contact** — zestaw wydaje 22% budżetu i przyniósł 1 sesję. Zostawić kolejną dobę na naukę,
   przestawić cel z Contact na ruch/ViewContent, czy wygasić i oddać 15 zł do `[KAT]`?
2. **Niewykorzystane 21 zł dziennie** — konto ma 65 zł budżetu, wydaje 44 zł. Czy przesuwać, czy zostawić.
3. **Wiek 65+ w `[KAT]`** — 0,43 zł za kliknięcie wobec 0,06 zł w 35–44. Zawęzić górną granicę?
4. **Instagram** — 0,46 zł z 26,72 zł. Zostawić jako darmowy dodatek czy wyłączyć miejsce.

## Otwarte z 15.09 (przypomnienie, nie działanie)

- Rozszerzanie listy w `[RMKT]` (`targeting_relaxation_types.custom_audience: 1`).
- Przycisk Messengera na ofercie — makieta `primaauto-makieta-messenger-oferta-2026-09-15.html`, wariant A/B.
- `gclid` wycinany w GTM od v13 — decyzja z 11.09 nadal otwarta; zaniża `google / cpc` w GA4.
