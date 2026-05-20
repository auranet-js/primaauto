# Indexing API — wyłącznie URL_UPDATED (bez URL_DELETED)

- **Data:** 2026-05-20
- **Status:** przyjęta
- **Obszar:** SEO / Indexing API / `class-asiaauto-indexing.php`
- **Wersja wdrożenia:** v0.32.51

## Kontekst

Hook `transition_post_status` dla CPT `listings` (klasa `AsiaAuto_Indexing_API`) wdrożono
2026-05-19 (v0.32.49). Zawierał DWA typy powiadomień:
- `publish` (z draft/pending/auto-draft/future) → **URL_UPDATED** — **zlecone** (zgłaszanie nowych ogłoszeń do indeksu)
- `trash` (z publish) → **URL_DELETED** — **NIEzlecone**, dodane przez Claude na własną rękę „dla symetrii"

URL_DELETED nigdy nie był wymaganiem użytkownika. Intencja zlecenia: *zgłaszać nowe auta do
indeksu przy publikacji*. O deindeksacji sprzedanych nie było mowy.

## Decyzja

Hook Indexing API wysyła **wyłącznie URL_UPDATED** przy publikacji nowego ogłoszenia.
**URL_DELETED nie jest używany.** Sprzedaż auta (publish→trash→delete) obsługuje
**301-na-hub modelu** po stronie HTTP (`class-asiaauto-redirects.php`).

## Uzasadnienie

1. **Niezlecone.** Funkcja nie wynikała z żadnego wymagania — czysty scope creep.
2. **Sprzeczne z 301.** Nie da się jednocześnie zgłaszać „usuń" (URL_DELETED) i serwować
   301 (przeniesione). Google podąża za realnym statusem HTTP — zobaczy 301 i pójdzie na hub,
   ignorując URL_DELETED. Sygnał bezużyteczny.
3. **Bug `__trashed`.** `get_permalink()` dla trashowanego posta zwraca URL z sufiksem
   `__trashed`, którego Google nigdy nie indeksował → URL_DELETED leciał na nieistniejący adres.
4. **Marnowanie quoty.** Quota Indexing API jest **wspólna dla wszystkich projektów**
   (jeden GCP project `iconic-works-462211-e7`, 200/dzień per project, NIE per domena).
   Każda sprzedaż = 1 zmarnowany request z puli dzielonej z auranet i innymi.

## Konsekwencje

- Sprzedaż auta = **0 requestów** do Indexing API.
- Deindeksacja sprzedanych: przez 301-na-hub (transfer equity na hub modelu) + naturalny
  recrawl Google. Strategia spójna ze stanem sprzed hooka (detectListingNotFound, v0.32.23).
- Wdrożenie: usunięty branch `trash→TYPE_DELETED` z `resolveNotificationType()`.
  Stała `TYPE_DELETED` pozostaje w kodzie (nieużywana, na wypadek świadomej przyszłej decyzji
  o 410 Gone dla martwych modeli — patrz plan redirectów 404 z 2026-05-20).
- Backup przed zmianą: `class-asiaauto-indexing.php.bak-2026-05-20-pre-urldelete-cut`.

## Meta-zasada (dlaczego ten ADR powstał)

**Nie dodawać niezleconych „symetrycznych uzupełnień" do integracji, które kosztują
quotę / budżet / wysyłają sygnały do zewnętrznych usług.** Zlecenie definiuje zakres.
„Skoro robimy X, to dla kompletności dorzućmy Y" — Y wymaga osobnej, świadomej zgody.
Powiązane: globalna reguła no-scope-creep.
