# Audyt logów, ruchu i bezpieczeństwa · 2026-07-30

> Źródła: surowe logi Apache `~/domains/primaauto.com.pl/logs/` (23–30.07, **515 705 requestów**, parser własny, 0 linii nieparsowalnych) + AWStats lipiec (`awstats072026.primaauto.com.pl.txt`) + GA4 property `534017542` + GSC URL Inspection.
> Raport dla Janka: `https://auratest.pl/fe4f58fec53ctmp/primaauto-audyt-logow-2026-07-30.html`
> Status: **3 fixy LIVE**, reszta listy otwarta.

---

## 1. Wynik ogólny

Strona **nie jest włamana**. Skany webshelli (`hellopress/wp_filemanager.php`, `this_is_a_new_hello_world.php`, `mac.php`, `.env`, ~50 innych) w 100% odbijają się 404. Ukrycie `wp-login.php` pod `/biuro` i blokada `/wp-admin/` bez ciasteczka sesji działają.

Ruch: ~74 tys. requestów/dobę, 14,2 GB transferu/7 dni (obrazy = 10,76 GB). Boty to 10,4% requestów, ale tylko 0,7 GB transferu.

## 2. Scraper obrazów — blokada z 12.07 trzyma w 100%

UA dosłownie `Mozilla/5.0 (compatible; crawler)`. **11 592 requesty z 10 937 unikalnych IP** w 7 dni (pula proxy urosła z 6 613 w lipcu, +65%), **wszystkie → 403, zero pobranych obrazów**. Max 4 requesty na IP — rotacja residential proxy, blokowanie po adresie bezcelowe.

Screaming Frog, 171 825 hitów w lipcu: **16.07** (`185.227.190.89`, TEL-NET) — potwierdzone przez Janka, to on z Radgoszczy. **22.07** (`85.28.166.159`, okay.pl, 42 281 hitów) — **niewyjaśnione**.

## 3. Obrazy JPG — przyczyna znaleziona, decyzja: nie ruszamy

`downloadSingleImage()` w `class-asiaauto-media.php` robi `mimeToExtension($mime)` → **zapisuje format źródła 1:1**. Wymuszenie WebP ma wyłącznie ścieżka ręcznej galerii. Nagłówek pliku (`Storage: ....webp`, `source images are WebP from byteimg.com`) był nieaktualny już na starcie.

| Źródło | JPG | WebP |
|---|---|---|
| dongchedi | **42 735** | 320 |
| che168 | **0** | 4 623 |
| ręczne | 19 | 968 |

Dongchedi daje JPEG **od marca** (pierwszy miesiąc: 107 JPG / 0 WebP) — to nie regresja, kod nigdy tego nie wymuszał. Stan: uploads **21 GB**, sam czerwiec 16 GB.

**DECYZJA: nie konwertujemy archiwum.** Rotacja skasuje te pliki sama (§4), a zmiana URL-i kosztowałaby indeks Google Images (151 klików / 22 089 impresji / 28 dni). Migracja **T-222 → che168** usuwa problem u źródła.

Pomiar (kontrolowany, te same zdjęcia): WebP q82 lżejszy o **33% (190x132)**, **30% (350x250)**, **36% (800x600)**. Uwaga: średnie z logów kłamią (WebP wychodzi „cięższy" — 41 vs 34 KB — bo oryginały che168 są bogatsze: 204 vs 116 KB).

## 4. Rotacja DZIAŁA — zweryfikowane empirycznie

Fix v0.32.73 spełnia zadanie. Cykl: `publish` → znika ze źródła → `draft` **48 h** → `trash` **7 dni** → `removeImages(force)` + `wp_delete_post(force)`. Razem **~9 dni**.

```
[2026-07-30 03:01:56] [INFO] Permanently deleted 1 trashed posts (15 images removed)
```

| Kontrola | Wynik | Ocena |
|---|---|---|
| Osierocone załączniki | 0 | OK |
| Trash w ramach TTL | 83 / 90 | OK |
| Zalegające > TTL | 7 | celowo — rezerwacja (`on_lot`/`stock`/`in_transit`) lub aktywne zamówienie |
| Drafty ze znacznikiem | 658 / 741 | w kolejce, znaczniki 21–46 h przy progu 48 h |
| Drafty **bez** znacznika | 83 | utkną — 967 zdjęć, ~0,5 GB |

**Pułapka:** `Cleanup complete: 0 trashed` **nie znaczy awarii** — najczęściej znaczniki są młodsze niż 48 h.

## 5. Ruch zagraniczny — czysty, jeden bot

Zagranica ≈ **2%** ruchu GA4 (PL 10 125 sesji, DE 109, SK 71, HU 63, CZ 23, HR 16). Zachowanie normalne (200–600 s/sesja) = Polonia + ruch z ChatGPT (`chatgpt.com / ai-assistant` to realne źródło dla SK, RO, BG, HU).

**Anomalia:** „tablet" Huawei `NDL-W09`, Węgry, źródło `l.facebook.com/referral` — 6 sesji × **4 279 s (71 min)** × **81,2 odsłony**, 639 eventów. To automat z JS. Te 6 sesji robi połowę eventów całych Węgier. Wejście przez Facebooka — warte zestawienia z T-024 (przejęcie konta Meta).

## 6. Błędy 404 — klasyfikacja 7 203 sztuk z 7 dni

| Kategoria | Ile | % | bot/człowiek |
|---|---|---|---|
| Obrazy usuniętych ofert | 3 837 | 53,3% | bot 3 603 |
| Ikony (sonda iOS) | 1 023 | 14,2% | człowiek 1 011 |
| **Martwy service worker `/sw.js`** | 925 | 12,8% | **człowiek 925, bot 0** |
| Skan podatności | 790 | 11,0% | — |
| **BUG: martwe linki w hubach** | 39 | 0,5% | — |
| URL ze znakiem U+2060 (z FB) | 38 | 0,5% | referer `lm.facebook.com` |

## 7. Co WDROŻONE (LIVE)

**A. Martwe linki w hubach** — `primaauto2026/taxonomy-make.php:163-165` + `taxonomy-serie.php:224-226`.
Naprawione **trzy** linki (nie dwa): `/oferta/` → 301 → katalog aut, choć istnieje `/informacje/proces-zamawiania/`.

```
- <li><a href="/oferta/">Proces zamawiania</a></li>
- <li><a href="/gwarancja-i-serwis/">Gwarancja i serwis</a></li>
- <li><a href="/finansowanie/">Finansowanie</a></li>
+ <li><a href="/informacje/proces-zamawiania/">Proces zamawiania</a></li>
+ <li><a href="/informacje/gwarancja-i-serwis/">Gwarancja i serwis</a></li>
+ <li><a href="/informacje/finansowanie/">Finansowanie</a></li>
```

Zasięg: **292 huby marek + 2 714 hubów serii ≈ 6 tys. wewnętrznych linków 404**. GSC potwierdzał `Not found (404)`, lastCrawl 29.07; wersje `/informacje/*` są zaindeksowane (247 impresji/28 dni).
Backupy: `*.bak-2026-07-30` obok plików.

**B. `/403.shtml`** — utworzony. Cel: uciąć 12 100 linii `File not found [.../403.shtml]` z `error.log` (23 732 z 23 936 linii to był śmieć). **Efekt niezweryfikowany** — LiteSpeed serwuje własną stronę 403 (1 242 B), bo w `.htaccess` nie ma `ErrorDocument`. Weryfikacja po rotacji logu (~00:50).

**C. `/sw.js`** — kill-switch (unregister + czyszczenie cache). HTTP 200, `application/javascript`. Gasi 3 055 błędów/mies. u realnych ludzi.

### Weryfikacja (smoke test 25 adresów)
`php -l` czysty; wszystkie 200 poza celowymi 301 z `.htaccess` (mtime 17.07, nietknięty); HTML domknięty; zero błędów PHP; blok „Informacje" = 3 poprawne linki na każdym hubie, starych `href` zero.

## 8. Pomyłka do zapamiętania

**Najpierw wyedytowałem nieaktywny theme `asiaauto`** — bo `CLAUDE.md` mówił „Child theme: `asiaauto`", a aktywny jest **`primaauto2026`** (standalone od 2026-04-24). Oba mają bliźniacze `taxonomy-*.php`. Zmiany cofnięte bit-w-bit (timestampy oryginalne, 0 zmian, brak `.bak`), `CLAUDE.md` poprawiony w tym commicie.

**Zasada:** przed edycją szablonu → `wp theme list --status=active`.

## 9. Znalezisko poboczne — do osobnego wątku

URL-e z **niepasującą marką** zwracają **200 zamiast 404**: `/samochody/avatr/07/` renderuje okrojoną stronę, bo slug `07` należy do **WEY** (term 5388), a WP dopasowuje term po samym slugu ignorując segment marki. Poprawny `/samochody/wey/07/` działa w pełni.
Przy 292 markach × 2 714 seriach to potencjalne źródło thin contentu w indeksie. **Skala niezbadana.**

## 10. Otwarte

| # | Zadanie | Nakład |
|---|---|---|
| 1 | Guard WebP w `downloadSingleImage()` — nie dla archiwum, tylko żeby kolejne źródło nie powtórzyło historii po cichu | 30 min |
| 2 | 83 drafty bez `_asiaauto_removed_at` (967 zdjęć) — dopisać znacznik, żeby rotacja je wchłonęła. **Wymaga `mysqldump`** | 30 min |
| 3 | Filtr węgierskiego bota w GA4 | 20 min |
| 4 | `facebookexternalhit` — 123× 429/tydzień (ryzyko braku miniaturek przy share na FB); ustalić czy limit w `.htaccess` czy w LiteSpeed | 15 min |
| 5 | Weryfikacja `error.log` po rotacji — czy `403.shtml` uciął śmieci; jeśli nie: `ErrorDocument 403 /403.shtml` | 10 min |
| 6 | Zbadać skalę §9 (200 zamiast 404 na obcej marce) | — |
| 7 | **Do Janka:** czy `85.28.166.159` to też Ty; gdzie na FB wisi link z U+2060 (94 wejścia/mies. na 404) | — |

Bez zmian, bo się nie potwierdziło: blokada anty-scrapingowa (100% skuteczności), rekompresja archiwum JPG, ruch z HR/CZ/SK (prawdziwy).

## 11. Narzędzia z tej sesji

- `tmp/ga4_geo_bot_2026_07_30.py` — GA4: kraje × urządzenia × źródła, wykrywanie botów po `averageSessionDuration` / `screenPageViewsPerSession`
- `tmp/gsc-404-audit-2026-07-30.py` — GSC URL Inspection + Search Analytics + ruch z Google Images

Memory: `reference_rotacja_cykl_zycia_oferty`, `reference_import_zapisuje_format_zrodla`.
