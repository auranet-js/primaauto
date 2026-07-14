# T-206 — Google Ads: strategia konwersyjna + cykliczny przegląd fraz

> Status: **gotowy do odpalenia** · Rozmiar: S
> Godziny realnie: **5–7 h** (Janek ~1–2 h, AI ~4–5 h) · Rynkowo: 14–16 h
> **Obniżone z 7–8 h — bo import konwersji JEST JUŻ ZROBIONY** (prace z 09.07). Kosztorys opisywał to jako pracę do wykonania.

## Stan faktyczny (konto 9506068500, zweryfikowane 14.07)

### ✅ Import konwersji z GA4 — DZIAŁA

| Cel | Status | Konwersje 30d |
|---|---|---|
| click_phone | ENABLED, primary | **29,7** |
| click_whatsapp | ENABLED, primary | **29,5** |
| generate_lead | ENABLED, primary | **5,0** |
| purchase / qualify_lead / close_convert_lead | zadeklarowane, **nigdy nie odpalają** | 0 |

**Razem 64,2 konwersji / 30 dni.** To jest zrobione — nie wyceniamy tego ponownie.

### ❌ Czego brakuje: wszystkie kampanie na ręcznych stawkach

| Kampania | Strategia | Budżet/d | Koszt 30d | Konw. 30d |
|---|---|---|---|---|
| [Brand] Prima-Auto | MANUAL_CPC | 10 zł | 141 zł | **24,2** |
| [RMKT] Dynamic Remarketing | MANUAL_CPC | 17 zł | 257 zł | **22,0** |
| [Topic] Import z Chin | MANUAL_CPC | 35 zł | 621 zł | 7 |
| [DSA] Import modele z Chin | MANUAL_CPC | 45 zł | **1 335 zł** | 6 |
| [SKAG-2] W drodze | MANUAL_CPC | 25 zł | 759 zł | 4 |
| [SKAG-1] Na placu | MANUAL_CPC | 20 zł | 610 zł | **1** |

Wydatek 30d ≈ **3 722 zł**. Google ma dane o konwersjach, ale **nie używa ich do licytacji** — każda stawka jest ustawiana ręcznie.

## ⚠️ Kluczowe ograniczenie (nie obiecujemy cudów)

Inteligentna licytacja (tCPA/maxConv) potrzebuje **~30 konwersji / 30 dni na kampanię**, żeby działać sensownie.

- **[Brand] (24) i [RMKT] (22)** — blisko progu, tu ma to sens.
- **[DSA], [Topic], [SKAG]** — 1–7 konwersji. **Za mało.** Włączenie tam smart biddingu pogorszy wyniki, nie poprawi.

**Wniosek: to nie jest „przełączamy wszystko na konwersje".** To jest: dwie kampanie na strategię konwersyjną, reszta zostaje na ręcznych stawkach i optymalizujemy je przeglądem fraz.

## ⚠️ Sygnał ostrzegawczy: pomiar prawdopodobnie zaniża konwersje ~4×

GA4 widzi **11 rozpoczętych zamówień** w 30 dni. W bazie jest ich **45**. Różnica to najpewniej zgody na cookies (Complianz blokuje pomiar bez zgody użytkownika).

**Konsekwencja:** Google optymalizuje na zaniżonym sygnale. Zanim przełączymy licytację na konwersje, warto to zbadać — bo jeśli realnie mamy 4× więcej konwersji, niż widzi Google, to progi wolumenowe wyglądają zupełnie inaczej. **To jest osobne 2–3 h diagnostyki i powinno pójść pierwsze.**

## Plan

1. **Diagnoza luki pomiarowej** (2–3 h) — dlaczego GA4 widzi 11 z 45. Consent Mode, modelowanie konwersji, czy zdarzenie odpala się przed zgodą.
2. **[Brand] → maxConv** (albo tCPA po ustabilizowaniu). Najbezpieczniejsza kampania na start — najwyższy wolumen, najniższy koszt.
3. **[RMKT] → maxConv.**
4. **Reszta zostaje na MANUAL_CPC** — i tam robimy przegląd fraz: raport wyszukiwanych haseł, dosypanie wykluczeń (marnujące kliknięcia), dosypanie fraz, które konwertują.
5. **[SKAG-1] Na placu — do przeglądu:** 610 zł za **1 konwersję** w 30 dni. Coś tam jest nie tak. Albo frazy, albo landing, albo intencja.
6. **Cykl:** przegląd fraz raz w miesiącu → to jest już w pracach regularnych (Ads), nie w tym tasku.

## Poza zakresem (osobny, większy temat)

**Import konwersji offline** (realne rezerwacje z systemu → Google Ads) — to zamknęłoby pętlę: Google uczyłby się na faktycznych zamówieniach, nie na kliknięciach w telefon. **Ale wymaga trzech rzeczy, z których nie istnieje żadna:**
1. Przechwytywanie `gclid` do cookie i zapisywanie go przy zamówieniu (grep: **zero** w całym kodzie).
2. Powiązanie zamówienia ze źródłem ruchu.
3. Wysyłka konwersji offline do Ads.

To jest realnie ~15–20 h i dotyka strefy kruchej zamówień. **Osobna pozycja, nie tutaj.**

## Testy

**Półautomatyczne**
- Przed zmianą strategii: zapisać baseline (koszt/konwersję, CPC, pozycja) dla obu kampanii.
- Po 14 dniach: porównanie. Smart bidding potrzebuje okresu nauki — **nie oceniamy po 3 dniach**.
- Kontrola, czy koszt nie wystrzelił (maxConv bez limitu potrafi podnieść CPC).

## Definicja zrobionego

- Wyjaśnione, dlaczego GA4 widzi 11 z 45 zamówień.
- [Brand] i [RMKT] na strategii konwersyjnej, z baseline'em do porównania.
- Pozostałe kampanie z odświeżonymi wykluczeniami.
- [SKAG-1] zdiagnozowany (610 zł / 1 konwersja).
