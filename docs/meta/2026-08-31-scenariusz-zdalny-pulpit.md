# Zdalny pulpit u Ruslana — scenariusz 31.08.2026

Cel: w kilkanaście minut przebić **pierwszą działającą reklamę** na Polskę i odczytać
z interfejsu to, czego API nie oddaje. Kolejność jest celowa — od najtańszego dowodu
do najdroższego.

## Stan zmierzony przed sesją (31.08, przez API)

| Rzecz | Stan |
|---|---|
| Sonda DSA | **BLOKER TRWA** — PL i PL-konwersje odbite `100/3858196`, US przechodzi |
| Konto `act_1038563008906171` | `account_status: 1`, `disable_reason: 0`, `HAS_VALID_PAYMENT_METHODS` |
| Limit wydatków | **1 000 zł ustawiony** (`spend_cap: 100000` gr) — prośba z pisma v5 wykonana |
| Wydano do dziś | **0 zł** — konto nigdy nie wyemitowało reklamy (hipoteza „dziewiczości") |
| Portfolio „Prima Auto" | `verification_status: verified` |
| Nasza rola na koncie | `DRAFT, ANALYZE, ADVERTISE` — **nadal brak `MANAGE`** |
| Utworzone obiekty | 1 kampania `[VID]` PAUSED, **0 zestawów, 0 reklam** |
| Token SU | 11 scope'ów, `ads_management` + `ads_read` żyją — API **nie jest zablokowane** |
| `dsa_recommendations` | `[]` (na Victorini zwraca podmiot) |
| Strona FB | 154 fanów, Andrzej opublikował dziś 07:26, wszystkie posty promowalne |

Odczytu statusu weryfikacji reklamodawcy **przez API nie da się zrobić** — sprawdzone
11 nazw pól i edge'y, wszystkie `(#100)` albo `2500`. Dlatego to musi zobaczyć człowiek.

---

## Krok 1 — Jakość konta (30 sekund, zaczynamy od tego)

    https://www.facebook.com/accountquality

Ta strona wymienia ograniczenia **osobno dla konta reklamowego, Strony i portfolio**
i nazywa brakującą rzecz wprost. Nie ma jej odpowiednika w API.

**Do odczytania:** czy przy „Prima-Auto" (konto reklamowe) i przy Stronie widnieje
jakiekolwiek ograniczenie, wymagany krok albo „wymaga weryfikacji". Zrzut ekranu.

## Krok 2 — Centrum informacji o firmie (1 minuta)

    Ustawienia firmy → Centrum informacji o firmie (Info Centre)
    https://business.facebook.com/settings/info

**Do odczytania:** stan **weryfikacji reklamodawcy** (to inna rzecz niż weryfikacja firmy,
która jest już `verified`). Jeśli jest tam przycisk „Rozpocznij weryfikację" albo status
„W trakcie / Wymagane działanie" — mamy odpowiedź na całą sprawę. Zrzut ekranu.

## Krok 3 — Promowanie postu (2–3 minuty) — NAJSZYBSZA DZIAŁAJĄCA REKLAMA

To jest najkrótsza droga do „reklama chodzi", bo omija kreator zestawu.

Post Andrzeja z dziś rano (Zeekr 001, 240 tys. zł), promowalny:

    https://www.facebook.com/122111931201416024/posts/122113038303416024

Na Stronie → przycisk **Promuj post** pod tym postem:
- cel: **Więcej odwiedzin witryny** (jeśli do wyboru) albo domyślny
- odbiorcy: **Polska**, wiek 25–65
- budżet: **20 zł / 1 dzień** (minimum, żeby nie palić kasy)
- → **Promuj teraz**

**Wynik A — przeszło:** reklama pracuje, konto UE odblokowane, ściana była tylko przy
tworzeniu przez API albo przy pierwszym zestawie. Podaj mi to i jadę dalej z API.
**Wynik B — zatrzymało:** zrzut komunikatu. Interfejs w tym miejscu pisze konkretnie,
czego brakuje — inaczej niż stały tekst kodu `3858196` w API.

## Krok 4 — Ręczny zestaw w Menedżerze reklam (5 minut, jeśli krok 3 przeszedł lub zawiódł)

    https://adsmanager.facebook.com/adsmanager → Utwórz

- kampania: **Ruch**, bez kategorii specjalnych, budżet na poziomie **zestawu**
- zestaw: kierowanie **Polska**, budżet **20 zł/dzień**, miejsce docelowe: witryna
- reklama: dowolne zdjęcie, adres `https://primaauto.com.pl/samochody/`
- **status: wstrzymana**, ale **kliknąć „Opublikuj"** — szkic nie przechodzi walidacji,
  więc zapisanie szkicu nie mówi nic

**To jest test rozstrzygający hipotezy o dziewiczości konta.** Kreator poprosi po drodze
o **beneficjenta i płatnika reklamy** (pole DSA) — zapamiętaj, czy podpowiada gotowy
podmiot, czy każe wpisać ręcznie. To dokładnie to, czego API nam odmawia (`dsa_recommendations` → `[]`).

**Przeszło:** podaj mi **identyfikator zestawu** (widoczny w kolumnie ID albo w adresie
URL po utworzeniu) — odczytam z API, czym różni się to, co wysłała przeglądarka, od naszego
ładunku, i dalej lecimy przez API.
**Zatrzymało:** zrzut ekranu z pełnym komunikatem.

## Krok 5 — Przy okazji, 20 sekund (tylko jeśli kroki 1–4 poszły gładko)

Ustawienia firmy → Konta → Konta reklamowe → „Prima-Auto" → System User
`Auranet API` → dodać zadanie **Zarządzanie kampaniami** (`MANAGE`).

Daje nam odczyt metody płatności i limitów bez pytania Ruslana. **Nie jest blokerem** —
jeśli sesja się przeciąga, odpuść.

---

## Czego dziś NIE ruszamy

- TikTok (konto firmowe + Business Center) — osobny wątek, zegar audytu liczony w tygodniach
- `read_insights` / `pages_read_user_content` — odpuszczone decyzją z 28.08, appka
  nie ma tych uprawnień na liście dostępnych
- Weryfikacja domeny — zamknięta 28.08, potwierdzona zrzutem
