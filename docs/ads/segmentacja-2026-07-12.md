# Segmentacja klientów — targetowanie demograficzne i geograficzne (2026-07-12)

> Konto Ads 9506068500 · GA4 534017542 · dane od cutoveru **2026-04-21** (~9 000 osób)
> Poprzednie w tej sesji: [RMKT/SKAG](rmkt-optymalizacja-2026-07-12.md)
> Raport: `tmp/primaauto-segmentacja-2026-07-12.html` (auratest)

---

## 0. OSTRZEŻENIE METODOLOGICZNE — czytaj przed każdą analizą GA4

**Nie licz leadów na `eventCount`. Licz na `totalUsers`.**

Pierwsze przeliczenie geografii na zdarzeniach dało: „Kostrzyn nad Odrą — CVR 21%, index 1087,
najlepsze miasto w Polsce". **To była JEDNA osoba, która kliknęła WhatsApp 16 razy.**
Podobnie Olkusz (1 osoba / 8 zdarzeń), Żary (1 / 5), Mogoșoaia (1 / 4).

To ten sam problem `MANY_PER_EVENT`, który naprawiliśmy tego samego dnia
(GA4 keyEvents → `ONCE_PER_SESSION`). **Dane historyczne w GA4 zostają zawyżone** — każda analiza
sprzed 2026-07-12 musi liczyć na osobach.

Skrypty: `tmp/ga4_segments_users_2026_07_12.py` (POPRAWNY, na osobach),
`tmp/ga4_segments_2026_07_12.py` (na zdarzeniach — **zostawiony jako przestroga**, nie używać do geo).

---

## 1. Stan wyjścia: kampanie nie były kierowane praktycznie wcale

| Wymiar | Przed |
|---|---|
| Demografia | **ZERO kryteriów** — reklamy leciały do wszystkich grup wieku |
| Geografia | cała Polska (2616), **bez modyfikatorów** (wyjątek: DSA miał 6 miast) |
| Harmonogram | pn-nd 7:00–22:00, **bez modyfikatorów** |
| Urządzenia | bez modyfikatorów |

## 2. Wiek — najmocniejszy sygnał (INDEX na osobach)

| Wiek | Osób | Odezwało się | CVR | Index | Decyzja |
|---|---|---|---|---|---|
| **55-64** | 439 | 15 | 3,4% | **153** | **+25%** |
| **45-54** | 827 | 22 | 2,7% | **119** | **+15%** |
| 35-44 | 772 | 16 | 2,1% | 93 | bez zmian |
| **65+** | 333 | **0** | 0% | 0 | **WYKLUCZONY** |
| **25-34** | 260 | **0** | 0% | 0 | **WYKLUCZONY** |
| 18-24 | 86 | 0 | 0% | 0 | **bez zmian** (za mała próbka) |
| nieznany | 5 318 (66%) | 127 | 2,4% | 107 | nietknięty |

**Test istotności (Poisson, baseline CVR 2,3%):**
- 65+: oczekiwane ~8 kontaktów, dostaliśmy 0 → **p = 0,05%**. Istotne.
- 25-34: oczekiwane ~6, dostaliśmy 0 → **p = 0,25%**. Istotne.
- 18-24: oczekiwane ~2, dostaliśmy 0 → **p = 13%**. **NIEISTOTNE — dlatego NIE wykluczamy.**

**Kluczowe:** Google rozpoznaje wiek tylko u 34% userów. Modyfikatory działają **wyłącznie na
rozpoznanych** — grupa „nieznany" (66% ruchu, index 107) pozostaje nietknięta. Nikogo nie tracimy.

Interpretacja: auto za 150–300 tys. kupuje człowiek 45–64, u szczytu zdolności finansowej.

## 3. Geografia — plac w Rzeszowie realnie sprzedaje

| Województwo | Osób | Odezwało się | CVR | Index | Decyzja |
|---|---|---|---|---|---|
| **Podkarpackie** | 360 | 20 | **5,6%** | **255** | **+30%** |
| **Małopolskie** | 660 | 21 | 3,2% | **146** | **+15%** |
| Mazowieckie | 2 698 | 55 | 2,0% | 94 | bez zmian (29% ruchu) |
| Śląskie | 1 030 | 19 | 1,8% | 85 | bez zmian |
| Lubelskie | 220 | 1 | 0,5% | 21 | **bez zmian** — za mały wolumen na pewność |
| Zachodniopomorskie | 147 | 0 | 0% | 0 | **bez zmian** — j.w. |

Podkarpackie konwertuje **2,5× lepiej** (20 różnych osób, p < 0,1%). **Bo plac jest w Rzeszowie** —
klient może przyjechać i obejrzeć auto przed wydaniem 200 tys. Małopolskie (sąsiednie) drugie.

> **Wniosek strategiczny (do wykorzystania w copy):** „przyjedź i obejrzyj" to nasza realna przewaga
> nad importerem wysyłającym auto z Chin prosto do klienta — **a nie ma jej w żadnej reklamie**.

## 4. Kanały — Paid Search konwertuje najgorzej

| Kanał | Osób | Odezwało się | CVR | Index |
|---|---|---|---|---|
| **AI Assistant** (ChatGPT) | 63 | 8 | **12,7%** | **514** |
| **Direct** | 804 | 55 | 6,8% | **277** |
| Organic Social | 635 | 19 | 3,0% | 121 |
| Display (RMKT) | 396 | 9 | 2,3% | 92 |
| Organic Search | 2 795 | 56 | 2,0% | 81 |
| **Paid Search** | 3 427 | 51 | 1,5% | **60** |

**Nie wyciągaj z tego wniosku „Ads się nie opłaca".** GA4 przypisuje kontakt *ostatniemu* kliknięciu —
człowiek, który przyszedł z reklamy, wrócił po tygodniu bezpośrednio i dopiero wtedy napisał,
liczy się jako „Direct". Wysoki wynik Direct (277) **częściowo JEST zasługą Ads**.

**ChatGPT (index 514)** — mały wolumen, ale najwyższa konwersja na koncie. Potwierdza zwrot z pracy
nad `llms.txt` / AEO. Kanał będzie rósł.

## 5. Urządzenia — PUŁAPKA, celowo nietknięte

| Źródło | Desktop | Mobile | Wniosek |
|---|---|---|---|
| Google Ads (CPA, 30d) | 137 zł | 84 zł | desktop **gorszy** |
| GA4 (CVR na osobach) | 3,9% | 2,3% | desktop **1,7× lepszy** |

Sprzeczność wynika z `MANY_PER_CLICK`: z telefonu łatwo kliknąć WhatsApp kilka razy, a każde
kliknięcie liczyło się osobno → **mobile sztucznie zawyżone w Ads**. Naprawione 2026-07-12,
ale dane historyczne zostają zafałszowane.

**NIE ustawiać modyfikatorów urządzeń przed ~2026-07-26** (potrzebne ~2 tygodnie czystych danych).

## 6. Dzień tygodnia — sygnał zbyt słaby

Wtorek 122, piątek 121, **niedziela 73**. Różnice na granicy istotności. Nie wdrożono.

## 7. Co wdrożono (2026-07-12)

**102 operacje**, wszystkie zweryfikowane odczytem:

- **Wiek** × **23 grupy reklam** (wszystkie kampanie SEARCH):
  `AGE_RANGE_65_UP` → wykluczony, `AGE_RANGE_25_34` → wykluczony,
  `AGE_RANGE_55_64` → bid **+25%**, `AGE_RANGE_45_54` → bid **+15%**
- **Geo** × **5 kampanii SEARCH** (Brand, Topic, SKAG-1, SKAG-2, DSA):
  Podkarpackie (`20856`) → **+30%**, Małopolskie (`20852`) → **+15%**

Skrypt: `tmp/gads_targeting_2026_07_12.py` · Backup: `~/backups/primaauto/2026-07-12-targeting/`

### Gotchy

- Demografia w Search to **`ad_group_criterion`** (per grupa), NIE `campaign_criterion`.
  Wykluczenie = `negative: true`; podbicie = `bidModifier` (osobne kryterium).
- Geo z modyfikatorem **dodaje się OBOK** istniejącego targetowania na Polskę (2616) —
  nie zawęża zasięgu, tylko podbija stawkę w tym regionie.
- ID województw PL: Podkarpackie **20856**, Małopolskie **20852**, Mazowieckie 20853, Śląskie 20859.
  (`geo_target_constant`, `target_type = 'Region'`, `country_code = 'PL'`)

## 8. Recheck

- [ ] **D+14 (2026-07-26):** czy modyfikatory wieku/geo poprawiły CPA. Mierzyć na **czystych** danych
      (po naprawie liczenia) i na **osobach** w GA4.
- [ ] **D+14:** dopiero wtedy decyzja o modyfikatorach urządzeń (patrz §5).
- [ ] **Copy:** przetestować przekaz „przyjedź i obejrzyj auto na placu w Rzeszowie" — najmocniejsza
      przewaga wynikająca z danych (§3), dziś nieobecna w reklamach.
- [ ] 18-24 i słabe województwa — wrócić, gdy uzbiera się wolumen.
