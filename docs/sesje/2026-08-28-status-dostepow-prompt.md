# Prompt do nowego wątku — status pozycji, na które czekamy

Wklej w nowej sesji w `~/projekty/primaauto`. Zadanie: **zmierzyć, co klient zdążył zrobić**,
i tylko na tej podstawie zaktualizować pismo. Nic nie tworzymy, nic nie publikujemy.

> Sprawdź aktualny status pozycji z listy dostępów Prima-Auto. Źródło prawdy dla treści:
> `docs/meta/prosby-v6-2026-08-28.md` (pismo dla klienta) i `docs/meta/plan-kampanii.md`
> (tabele blokerów i zadań). Kontekst techniczny: `docs/roadmapa/T-243a-instrukcja-dostepy-dla-ruslana.md`
> krok 5 i `docs/roadmapa/T-243c-stan-i-plan-kampanii-2026-08-27.md` tabela dostępów.
> Zmierz każdą pozycję poniżej, potem zaktualizuj pismo i plan — **wyłącznie o to, co się zmieniło**.

## Co mierzyć i czym

| # | Pozycja | Pomiar | Stan 28.08 wieczorem |
|---|---|---|---|
| 1 | Weryfikacja reklamodawcy (DSA) | `python3 scripts/social/dsa_status.py` | PL `3858196`, US przechodzi → **blokuje** |
| 2 | TikTok: konto firmowe + Business Center | pytanie do klienta; z naszej strony widać tylko bio: `curl -A "<UA przeglądarki>" https://www.tiktok.com/@primaauto.pl` → szukaj `bioLink` | brak `bioLink`, konto prywatne → **ŚCIEŻKA ZABLOKOWANA PO STRONIE TIKTOKA**, patrz niżej |
| 3 | Dwa scope'y (`read_insights`, `pages_read_user_content`) | `bash ~/secrets/meta/token-debug.sh --project primaauto-2026` | 11 scope'ów, **brak obu** |
| 4 | YouTube: kanał na konto marki + `js@` jako menedżer | próba OAuth chooser / `~/secrets/google/youtube-auth.py` | „Ruslan w toku" (27.08) |
| 5 | Regulamin lead ads | page token → `GET /1146829831857839?fields=leadgen_tos_accepted` | `false` → **otwarte** |
| 6 | Regulamin list z pliku | **nie mierz z automatu** — sonda zakłada grupę na koncie | nieznany, świadomie |
| 7 | Instagram: bio i link | `GET /17841457773875233?fields=biography,website` | „ta Korei", brak `website` |
| 8 | Domena `primaauto.com.pl` | `curl -s https://primaauto.com.pl/ \| grep facebook-domain-verification` | ✅ **zamknięte 28.08** |

Skrót do Graph API: `bash ~/secrets/meta/meta_call.sh --project primaauto-2026 <ŚCIEŻKA>`.
Konto Ads `9506068500` przez `tmp/gads_client.py` (`load()` → `refresh(oauth, tokens)`).

## Pułapki, które już raz kosztowały błędne pismo

- **`account_link` i `data_link` w Google Ads NIE mierzą powiązania kanału YouTube.** Opisują
  partnerów zewnętrznych i filmy w programach twórców. Powiązanie kanału poznasz po konwersjach
  `YOUTUBE_HOSTED` (`subscriptions`, `follow-on views`) na koncie — są `ENABLED`, **kanał jest
  powiązany**, to nie jest prośba do nikogo.
- **`owned_domains` na portfolio zwraca `(#100) nonexisting field`** w v19/v21/v25 mimo scope'u
  `business_management` — brak advanced access appki. Statusu weryfikacji domeny przez API nie
  odczytasz; `2500 Unknown path components` przy innych nazwach = takiej ścieżki po prostu nie ma.
- **`customaudiences` ignoruje `validate_only`** — sonda TWORZY grupę. Regulaminu list z pliku
  nie sprawdzaj „na szybko".
- **Odczyt postów Strony wymaga page tokenu**, token SU zwraca `Invalid OAuth 2.0 Access Token`.
- **`pages_manage_posts` nie jest pozycją do wysłania klientowi.** Brakuje go w tokenie i tak ma
  zostać: publikuje Andrzej, my promujemy. Dokumenty `docs/produkcja/` opisują to jako stan
  tokenu — nie przepisuj do pisma.
- **TikTok — prośba z pisma v6 jest NIEWYKONALNA i nie ponawiaj jej.** TikTok **odrzucił**
  wnioski Andrzeja i Ruslana o konto firmowe / weryfikację firmy (informacja od Janka,
  28.08 wieczorem). Ścieżka „konto biznesowe → Business Center → Auranet jako członek" jest
  zamknięta nie po stronie klienta, tylko po stronie TikToka — kolejne pismo z tą samą prośbą
  wysyła klienta pod ten sam mur. Dopóki nie ustalimy z Jankiem innej drogi (dostęp
  przeglądarkowy do konta albo dostęp aplikacyjny), pozycja jest **wstrzymana, nie otwarta**.
  Link w bio pozostaje osobnym drobiazgiem — nie wymaga konta firmowego i nadal jest do zrobienia.
- **YouTube jest u Ruslana, nie u Andrzeja.** Andrzej ma FB, IG i TikToka.

## Po pomiarze

1. Zaktualizuj `docs/meta/prosby-v6-2026-08-28.md` — pozycje zamknięte przenieś do sekcji
   „Zamknięte", resztę zostaw bez przepisywania.
2. Zregeneruj HTML tym samym konwerterem co v6 (styl 1:1 z pisma v5) i wgraj oba pliki do
   `~/domains/auratest.pl/public_html/fe4f58fec53ctmp/`.
3. Zaktualizuj tabele w `docs/meta/plan-kampanii.md` (sekcja 6 blokery, sekcja 8 zadania).
4. Pokaż Jankowi inline, co się zmieniło, i podaj linki. **Do klienta nic nie wysyłaj.**
