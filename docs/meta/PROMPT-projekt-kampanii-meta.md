# PROMPT — projekt kampanii Meta Ads (kampanie, harmonogramy, odbiorcy, geo)

> Wątek do otwarcia w `~/projekty/primaauto`. Napisany 02.09.2026, po zdjęciu blokera DSA.
> **To jest prompt wykonawczy — przeczytaj go w całości, zanim cokolwiek zrobisz.**

## Cel wątku

Zaprojektować **całość obecności Prima-Auto w Meta Ads** i doprowadzić do stanu „gotowe do
włączenia": struktura kampanii, budżety, harmonogramy emisji, grupy odbiorców, kierowanie
geograficzne, wykluczenia, kreacje i kryteria oceny. Nic nie startuje z budżetem bez
wyraźnej zgody Janka — patrz „Zasady twarde".

Konto reklamowe nigdy nie wyemitowało ani jednej reklamy. **To jest projekt od zera**, nie
optymalizacja istniejącego.

---

## KROK 0 — sprawdź stan sam, zanim uwierzysz w cokolwiek poniżej

Liczby w tym dokumencie są zmierzone 02.09.2026 i **zestarzeją się**. Zacznij od:

```bash
cd ~/projekty/primaauto
python3 scripts/social/dsa_status.py          # czy PL nadal przechodzi
python3 - <<'PY'
import sys, json; sys.path.insert(0,'scripts/social')
import meta_api as api
for t,s in [('KAMPANIE', f'{api.ACT}/campaigns?fields=id,name,status,objective,daily_budget&limit=25'),
            ('ZESTAWY',  f'{api.ACT}/adsets?fields=id,name,status,effective_status,targeting&limit=25'),
            ('REKLAMY',  f'{api.ACT}/ads?fields=id,name,effective_status&limit=25'),
            ('AUDIENCJE',f'{api.ACT}/customaudiences?fields=id,name,subtype,retention_days,approximate_count_lower_bound&limit=25'),
            ('WYDATKI',  f'{api.ACT}/insights?fields=spend,impressions,clicks&date_preset=maximum')]:
    d,e = api.get(s); print(f'=== {t} ===\n', json.dumps(d, ensure_ascii=False, indent=1)[:1500] if d else e)
PY
```

Czytaj też **`docs/meta/plan-kampanii.md`** — to źródło prawdy dla celów, budżetów i gotchy.
Historia blokera: `docs/sesje/2026-08-31-test-reczny-dsa-u-ruslana.md`.

---

## Stan zmierzony 02.09.2026

**Bloker DSA zdjęty.** Zgłoszenie Beneficiary/Payer Inquiry z 31.08 rozpatrzone w ~28 h.
Wszystkie warianty PL (ruch i konwersje) przechodzą walidację.

**Podmiot DSA: `PRIMA AUTO RUSLAN PRIMA`** — stała `DSA_PODMIOT` w `scripts/social/meta_api.py`.
Nazwa portfolio „Prima Auto" jest **odrzucana** kodem `3858196`. Nie zmieniaj tej stałej.
`dsa_recommendations` zwraca `[]` **także po odblokowaniu** — nie jest miernikiem niczego.

| zasób | stan |
|---|---|
| konto `act_1038563008906171` | aktywne, `HAS_VALID_PAYMENT_METHODS`, limit **1 000 zł**, wydane **0 zł** |
| nasza rola | `DRAFT, ANALYZE, ADVERTISE, MANAGE` — komplet |
| piksel | `1634147041766916`, wpięty w katalog |
| katalog | `2936723456671912`, **2 937 pojazdów** |
| Strona FB | `1146829831857839`, 154 fanów, Andrzej publikuje ~20 postów/30 dni |
| Instagram | `17841457773875233` |

**Kampanie — do posprzątania na start:**
- `120248880398990243` `[POST] Test PL — Zeekr 001` — **ACTIVE, ale PUSTA** (0 zestawów,
  0 reklam, 0 wydatku). Pozostałość po ręcznym teście 31.08. **Skasuj albo wstrzymaj.**
- `120248809387930243` `[VID] Auta z Chin — nowi odbiorcy` — PAUSED, `OUTCOME_LEADS`, pusta.
  Używana jako kontener sondy DSA — **jeśli ją kasujesz, popraw `KAMPANIA_TESTOWA`
  w `scripts/social/dsa_status.py`**.

**Grupy odbiorców — istnieją i zbierają** (wszystkie `WEBSITE`, status „Normalny"):
`Wszyscy odwiedzający — 180 dni`, `Oglądający oferty — 30 dni`, `Oglądający oferty — 90 dni`,
`Kontakt tel./WhatsApp — 180 dni`, `Formularz zamówienia — 180 dni`.
Wszystkie pokazują `approximate_count_lower_bound: 20` — **to wartość progowa Mety, nie
realna liczebność**; przed użyciem w kierowaniu sprawdź, czy którakolwiek urosła na tyle,
by dało się na niej optymalizować.

**Zestawy produktów w katalogu:** Wszystkie pojazdy 2 937 · Do 200 tys. zł 1 743 ·
SUV-y 1 649 · Elektryczne 1 292 · Hybrydy 1 180 · W drodze do Polski 26 · Na placu 19.

**Cele przeniesione z Google Ads** (konto `9506068500`, 30 dni): `click_phone` 34,0 ·
`click_whatsapp` 43,3 · `generate_lead` **0** · Clicks to call **0**.
Piksel / 7 dni: `PageView` 14 364 · `ViewContent` 3 343 · `Contact` 38 · `Lead` 4.

---

## Zakres do zaprojektowania

1. **Struktura kampanii** — ile, jakie cele, jak się nie kanibalizują.
   Punkt wyjścia w `plan-kampanii.md`: `[VID]` prospecting 25 zł/dz, `[POST]` promowanie
   postów Andrzeja 10–15 zł/dz, `[RMKT]` dynamiczny remarketing z katalogu.
2. **Budżety** — podział i kolejność uruchamiania. Limit konta to 1 000 zł, zasilane ręcznie.
3. **Harmonogramy emisji** — doba czy okna godzinowe; w Google Ads pracuje 6:00–24:00 + 0:00–1:00.
4. **Odbiorcy** — szeroko vs zainteresowania vs lookalike; które z istniejących audiencji
   w kierowaniu, a które w wykluczeniach.
5. **Geo** — cała Polska czy promień wokół Rzeszowa; auta stoją na placu w Rzeszowie,
   ale sprzedaż jest ogólnopolska. **Feed nie niesie sygnału „na placu"** — `address.city`
   to Rzeszów dla wszystkich pozycji.
6. **Kreacje** — filmy z sesji (lipiec i sierpień, `docs/meta/plan-kampanii.md` ma inwentarz
   z Dysku i informację, które wymagają remuksu `+faststart`), posty Andrzeja, karuzele z katalogu.
7. **Kryteria oceny** — po jakim czasie i przy jakim progu uznajemy, że działa albo nie.

---

## Tryb pracy — WAŻNE

**Najpierw sprawdź stan (KROK 0), potem pytaj, na końcu podsumuj.**

**Pytania zadawaj quizem przez `AskUserQuestion`, POJEDYNCZO** — jedno pytanie, odpowiedź,
dopiero potem następne. Nie wysyłaj czterech naraz (`feedback_quiz_sequential`).

Każde pytanie ma mieć **2–4 warianty zbudowane z tego, co realnie widzisz w danych** —
nie z powietrza. Wariant z Twoją rekomendacją jako pierwszy, z krótkim uzasadnieniem
opartym na liczbach. Janek zna kontekst klienta, którego nie ma w repo, więc jego odpowiedź
bije Twoją rekomendację.

**Zanim zaproponujesz cokolwiek do dopytania klienta** — najpierw quiz do Janka.
Zwykle zna odpowiedź z rozmów i maili.

**Na końcu, przed jakimkolwiek tworzeniem obiektów: podsumowanie całości do akceptacji** —
struktura, budżety, geo, odbiorcy, harmonogramy, kreacje, kryteria oceny, w jednym miejscu.
Czekasz na wyraźne „go". Dopiero wtedy budujesz.

---

## Zasady twarde

- **Nic nie startuje z budżetem bez wyraźnej zgody.** Budowanie obiektów w statusie
  `PAUSED` jest w porządku; włączenie emisji to osobna decyzja Janka.
- **Publikacja na kanałach klienta wymaga osobnej zgody** na treść i termin — „leć"
  i „działaj" jej nie zastępują (`feedback_publikacja_zewnetrzna_wymaga_osobnej_zgody`).
- **Sonduj przed tworzeniem.** `api.post(..., waliduj=True)` dopisuje `execution_options:
  ["validate_only"]` i nic nie tworzy. **WYJĄTEK: `customaudiences` IGNORUJE `validate_only`
  i tworzy grupę naprawdę** (`reference_meta_customaudiences_ignoruje_validate_only`).
- Twierdzenia „działa / zrobione / sprawdzone" wyłącznie z dowodem obok — wywołanie i wynik.
- Nie zmieniaj `DSA_PODMIOT`.

## Gotchy, które już nas kosztowały

- Filtr ceny w `product_set` to **integer w groszach**: `{"price":{"lte":20000000}}`.
  Zapis `"200000 PLN"` daje błąd `1798095`, samo `200000` — pusty zestaw z mylącym komunikatem.
- Dowodem na kartę jest **`HAS_VALID_PAYMENT_METHODS` w `capabilities`**, nie `funding_source`.
- Landingi Denza: działają wyłącznie `/samochody/denza/z9-gt-dm-i/` i `/n9-dm-i/`;
  `/z9-gt/` i `/n9/` dają 301 na hub marki. **Sprawdzaj kod odpowiedzi przed każdą kreacją.**
- Filtr katalogu po uploadzie **laguje kilkanaście minut** — licz pozycje lokalnie.
- Meta potrzebuje ~50 konwersji tygodniowo na zestaw, by wyjść z fazy uczenia. Przy `Contact`
  (38/7 dni z całej witryny) i budżecie rzędu 25 zł/dzień to nieosiągalne —
  stąd start na `ViewContent`.

## Materiały

- `docs/meta/plan-kampanii.md` — cele, budżety, rotacja, inwentarz kreacji (źródło prawdy)
- `docs/sesje/2026-08-31-test-reczny-dsa-u-ruslana.md` — jak zdjęto bloker
- `docs/ads/mapa-kampanii.md` — co robi Google Ads, żeby się nie dublować
- `scripts/social/meta_api.py` — warstwa dostępu; `dsa_status.py`, `grupy_odbiorcow.py`
