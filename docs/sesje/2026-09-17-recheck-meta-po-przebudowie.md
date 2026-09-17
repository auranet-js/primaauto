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

- ~~Rozszerzanie listy w `[RMKT]`~~ — **sprostowanie 17.09: już włączone**, zestaw ma
  `targeting_relaxation_types: {lookalike: 0, custom_audience: 1}`. Pozycja nieaktualna.
- Przycisk Messengera na ofercie — makieta `primaauto-makieta-messenger-oferta-2026-09-15.html`, wariant A/B.
- `gclid` wycinany w GTM od v13 — decyzja z 11.09 nadal otwarta; zaniża `google / cpc` w GA4.


---

# Dodatek z 17.09 — wiek i posty (rozpoznanie na polecenie Janka)

## Wiek w żywych zestawach — stan i rekomendacja (NIC NIE ZMIENIONE)

Odczyt targetingu wykazał **rozjazd nazwy z ustawieniem**: zestaw nazywa się `[KAT] PL M 30-60`,
a faktyczny przedział to **25–65**. `[FOTO]` ma 30–60, `[RMKT]` 18–65 bez filtra płci
(w remarketingu to poprawne — lista sama definiuje odbiorcę).

Rozkład za 15–17.09:

| Zestaw | Przedział | Wydane | zł/klik | zł/LPV |
|---|---|---:|---:|---:|
| `[KAT]` | 35–44 | 25,14 zł (32,5%) | 0,078 | **0,20** |
| `[KAT]` | 45–54 | 19,90 zł (25,8%) | 0,089 | 0,26 |
| `[KAT]` | 55–64 | 11,03 zł (14,3%) | 0,065 | 0,27 |
| `[KAT]` | 25–34 | 14,88 zł (19,3%) | 0,076 | 0,33 |
| `[KAT]` | **65+** | 6,29 zł (8,1%) | **0,185** | **0,90** |
| `[RMKT]` | 45–54 | 12,21 zł (29,6%) | 0,094 | **0,16** |
| `[RMKT]` | 35–44 | 13,01 zł (31,5%) | 0,092 | 0,34 |
| `[RMKT]` | 65+ | 4,74 zł (11,5%) | 0,085 | 0,30 |
| `[RMKT]` | 55–64 | 6,37 zł (15,4%) | 0,112 | 0,58 |
| `[RMKT]` | **18–24** | 0,58 zł (1,4%) | 0,048 | **0 wejść** |

Rekomendacja była: `[KAT]` → 25–64 (65+ kosztuje 4,5× więcej na wejście), `[RMKT]` → 25–65
(65+ w remarketingu wypada **dobrze**, do wycięcia tylko 18–24). **Decyzja Janka 17.09: nie ciąć,
zebrać jeszcze dobę** — 65+ ma w `[KAT]` tylko 34 kliknięcia, a zmiana wieku restartuje fazę uczenia.
Wracamy 19.09 na danych z 17. i 18.09.

## Czy wrócić do reklam z postów

**Jak posty wypadały jako reklama** (04–14.09, przed przebudową):

| Reklama | Wydane | CTR w link | zł/klik | zł/LPV |
|---|---:|---:|---:|---:|
| `[POST]` Zeekr 8X | 5,54 zł | 2,84% | 0,241 | **0,29** |
| `[POST]` Denza Z9 DM-i | 86,72 zł | 1,28% | 0,326 | 0,56 |
| `[POST]` Mazda EZ-6 | 11,26 zł | 0,99% | 0,289 | 0,56 |
| `[VID]` z9-gt (dla porównania) | 188,34 zł | 6,01% | 0,216 | 1,06 |
| `[RMKT]` Na placu w Polsce (dla porównania) | 38,62 zł | 7,38% | 0,193 | **0,22** |

Post jako reklama kosztuje **0,29–0,56 zł za wejście**, czyli gorzej niż dzisiejszy `[KAT]`
(0,36 zł/sesja GA4 przy CTR 9,22%). Niski CTR w link jest wbudowany w format — w poście klika się
„więcej", nie link.

**Co się realnie klika w `[KAT]`** (15–17.09, 418 kliknięć z przypisaniem produktu):
Changan UNI-V (26 kliknięć w dwóch rocznikach), Chery Arrizo 8 (15), Hongqi EH7 (14), Hongqi H5 (13),
BYD Han DM-i + Han L (20), JETOUR G700 (10), Zeekr 7X (9). **Czyli sedany i tańsze SUV-y.**

**Co publikuje Andrzej** (posty 03–15.09): iCAR Super V23, Freelander 8, JETOUR G700, Zeekr 8X,
ROX 01, BYD Leopard 7, Voyah Taishan, Dongfeng M-Hero 917. **Czyli premium i nisza.**

Pokrycie obu list to jeden model: **JETOUR G700**. Wszystkie te auta są realnie w Polsce albo w drodze
(baza: 20 `on_lot`, 40 `in_transit`), więc materiał jest prawdziwy — rozjeżdża się dobór modeli,
nie wiarygodność.

**Rekomendacja:** nie wracać do `[POST]` jako formatu ogólnego. Dwie rzeczy warte ruchu, w tej kolejności:
1. **Wznowić `[RMKT] Na placu w Polsce`** — 0,22 zł/LPV przy CTR 7,38%, drugi najlepszy wynik na koncie,
   a zapas 60 aut dostępnych od ręki go karmi. To najtańsze wejście, jakie konto miało poza `[RMKT]` Oglądane.
2. **Poprosić Andrzeja o posty o sedanach z listy klikanych** (Changan UNI-V, Arrizo 8, Hongqi EH7/H5, BYD Han)
   — dziś publikuje o autach, których nikt nie klika. Dopiero taki post ma sens jako reklama.
