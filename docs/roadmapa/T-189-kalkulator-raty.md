# T-189 — Kalkulator raty finansowania (rata na kartach ofert)

> Status: **gotowy do odpalenia** (parametry konfigurowalne, partner niepotrzebny na start) · Rozmiar: M · **wymaga T-212** (komponent karty)
> Godziny realnie: **14–18 h** (Janek ~2 h, AI ~12–16 h) · Rynkowo: 35–45 h
> Korekta 2026-07-14 (uwaga Janka): **podniesione z 12–16 h** — po rozbiciu na czynności wyszło, że było ciasno. To nie jest „widget z suwakiem", tylko konfigurator przeliczający cały katalog w locie.
> *(Pierwotnie w kosztorysie: 8–10 h — wtedy zakładano ratę tylko na stronie oferty, bez kart w listingu.)*

## Rozbicie godzin

| Czynność | h |
|---|---|
| Sekcja konfiguracji w adminie (oprocentowanie, prowizja, zakresy suwaków, on/off) | 2–3 |
| Wzór annuitetowy z wartością rezydualną — PHP + JS, ta sama liczba + testy | 1–2 |
| Konfigurator: suwaki, przełącznik firma/osoba prywatna, sticky przycisk, responsywność | 4–5 |
| **Przeliczanie rat na wszystkich kartach bez przeładowania** + zapamiętanie wyboru | 2–3 |
| Rata na karcie (przez slot z T-212) | 1 |
| Blok finansowania na stronie oferty + CTA „zapytaj o finansowanie" → lead | 2–3 |
| Disclaimer prawny, testy, mobile | 1–2 |
| **Razem** | **14–18** |

## Po co

Obniża barierę „całość z góry". Klient widzi „od 292 zł/mc" zamiast „26 500 zł" — i to widzi **już na liście**, nie dopiero po wejściu w ofertę. Generuje leady „zapytaj o finansowanie".

## Wzorzec: azjaauto.com (obejrzany 2026-07-14)

Konkurent, który podoba się Ruslanowi, robi to tak:
- **Karta w listingu:** „Cena netto 26 500 zł" + pod spodem „**Rata netto 292 zł**".
- **Strona oferty:** wielka czerwona „Twoja rata netto", pod nią cena katalogowa (przekreślona) vs „cena dla Ciebie", opłata wstępna (20%), wartość wykupu (25%). CTA: „Zapytaj o auto" / „**Dopasuj ratę**".
- **Konfigurator** (panel z suwakami): Firma / Osoba prywatna · okres 36/48/60 mies. · wpłata początkowa 10–30% · wykup końcowy 15–35%. Adnotacja: *„Zmiany zobaczysz od razu na kartach ofert"* — czyli **parametry są globalne dla sesji i przeliczają cały listing**.
- Sticky przycisk „Dopasuj ratę" na krawędzi ekranu.

## ⚠️ Ich matematyka się nie domyka — NIE kopiujemy liczb

Auto 26 500 zł, wpłata 20% = 5 300, wykup 25% = 6 625, rata 292 zł × 48 mies.

Do sfinansowania: 26 500 − 5 300 = **21 200 zł**.
Klient zapłaci: 292 × 48 + 6 625 = **20 641 zł**.

**To mniej niż kwota finansowana — przy zerowym oprocentowaniu, nie mówiąc o dodatnim.** Albo mają subwencję dealerską, albo rata jest marketingowo zaniżona.

**Decyzja:** bierzemy ich **strukturę i UX**, ale wzór robimy uczciwy — **annuitet z wartością rezydualną**, oprocentowanie konfigurowalne. Nie obiecujemy raty, której klient nie dostanie.

## Parametry — konfigurowalne (decyzja Janka 2026-07-14)

Wszystko w opcji `asiaauto_finance_config` (panel admina), **wartości startowe wzorowane na AzjaAuto**:

| Parametr | Start | Zakres w UI |
|---|---|---|
| Okres | 48 mies. | 36 / 48 / 60 |
| Wpłata początkowa | 20% | 10–30% |
| Wykup końcowy | 25% | 15–35% |
| Typ klienta | Firma | Firma / Osoba prywatna |
| **Oprocentowanie roczne** | **do ustalenia — pole w adminie** | — |
| Prowizja | konfigurowalna | — |

Gdy Ruslan dostarczy parametry realnego partnera (T-113) — **podmieniamy liczby w adminie, zero zmian w kodzie**.

## Stan faktyczny

- `price` — **100% ofert, czysto numeryczne**, 103 000–1 047 000 PLN. Zero blokerów.
- `_asiaauto_price_breakdown` — 100% pokrycia, pełna kaskada kosztów (baza pod ewentualną transparentność w przyszłości).
- Karta oferty: helper wspólny (patrz T-187) → rata dokładana **w jednym miejscu**, pojawia się wszędzie.

## Plan (kroki)

1. **Konfiguracja w adminie** — `asiaauto_finance_config` (oprocentowanie, prowizja, domyślne wpłata/wykup/okres, zakresy suwaków, on/off całej funkcji).
2. **Wzór raty** (PHP + JS, ten sam) — annuitet z wartością rezydualną. Jedna funkcja, testowalna.
3. **Konfigurator (panel)** — Firma/Prywatna, suwaki. Wybór zapisywany w `localStorage` → **przelicza raty na wszystkich kartach bez przeładowania**.
4. **Rata na karcie oferty** (helper karty) — „od X zł/mc".
5. **Blok finansowania na stronie oferty** — rata, wpłata, wykup, CTA „Dopasuj ratę" + CTA „**Zapytaj o finansowanie**" → lead (istniejący mechanizm leadów, pole `_asiaauto_lead`).
6. **Disclaimer** — „Kalkulacja szacunkowa, nie stanowi oferty w rozumieniu KC". **Wymóg prawny, nie ozdoba.**
7. **Filtr „rata do X zł/mc"** w wyszukiwarce — opcjonalnie, ale to jest mocny hak (klient myśli ratą, nie ceną). Wymaga T-116.

## Strefy kruche

- **Pipeline cenowy** (`class-asiaauto-price.php`) — **NIE DOTYKAMY**. Kalkulator czyta gotowe `price`, nic nie liczy w kaskadzie kosztów.

## Testy

**Automatyczne**
- Wzór raty: zestaw 10 przypadków (cena × wpłata × wykup × okres × oprocentowanie) vs niezależnie policzona wartość. **Rata × okres + wykup + wpłata ≥ cena** — ten warunek musi być spełniony zawsze (inaczej powtarzamy błąd AzjaAuto).
- Rata liczona identycznie w PHP i JS (ta sama liczba na karcie z serwera i po przeliczeniu w przeglądarce).
- Auto z ceną 0 / bez ceny → brak raty, brak dzielenia przez zero.

**Półautomatyczne**
- Zmiana oprocentowania w adminie → raty na całym listingu przeliczone.
- Sprawdzić rozrzut: rata dla najtańszego (103 tys.) i najdroższego (1,05 mln) auta — czy nie wychodzą absurdy.

**MCP (Chrome)**
- Listing → raty na kartach → „Dopasuj ratę" → zmiana okresu na 60 → **raty na kartach przeliczają się od razu** (to jest ficzer, który podoba się Ruslanowi).
- Oferta → blok finansowania → „Zapytaj o finansowanie" → lead ląduje w bazie.
- Mobile 375px: sticky „Dopasuj ratę" nie zasłania CTA kontaktu.

## Definicja zrobionego

- Rata widoczna na kartach w listingu i na stronie oferty.
- Konfigurator zmienia parametry i przelicza cały listing bez przeładowania.
- Wszystkie parametry (w tym oprocentowanie) zmienialne z admina bez dotykania kodu.
- Wzór matematycznie uczciwy (rata × okres + wykup + wpłata ≥ cena).
- Disclaimer prawny obecny.
- CTA „Zapytaj o finansowanie" generuje lead.
