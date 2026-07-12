# Przypomnienie: re-check logów scrapingu (miesiąc po blokadach)

> Utworzone: 2026-07-12 · Termin: 2026-08-12 · Projekt: primaauto
> Kontekst pełny: docs/decyzje/2026-07-12-anty-scraping-htaccess.md
> Raport reconu: https://auratest.pl/fe4f58fec53ctmp/primaauto-scraping-recon-2026-07-12.html
> Memory: project_scraping_recon_2026_07_12.md (gotchy metodyczne!)

## Co się stało (2026-07-12)

Recon wykrył 2 operacje scrapingu katalogu; wdrożono blokady .htaccess
(blok `# BEGIN Prima anty-scraping (2026-07-12)`, backup `.htaccess.bak-2026-07-12`,
commit ADR 96be435):
- 403 dla UA dokładnie `Mozilla/5.0 (compatible; crawler)` — warstwa zdjęć operacji
  na proxy (Oxylabs, 6613 rotujących IP).
- 403 dla IP `84.40.222.42` — „tablet z Płocka" (Netia, Galaxy Tab S4 + DuckDuckGo,
  zebrał 73% katalogu).

Świadomie NIEzablokowana: warstwa HTML operacji proxy — frozen UA
`Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) ... Chrome/142.0.0.0 Safari/537.36`
(Puppeteer; zbyt podobny do realnych przeglądarek). Watermark treści: odłożony.

## Co sprawdzić (2026-08-12)

Logi: `~/domains/primaauto.com.pl/logs/*.tar.gz*` — **rozpakowywać do OSOBNYCH
katalogów** (każdy tar ma plik o tej samej nazwie). Snapshot bieżący też w
`~/.cagefs/tmp/palog/`.

1. **Czy blokady działają:** ile 403 dla UA 'crawler' i dla 84.40.222.42
   (grep po logach; jeśli 403 spadły do zera → przeciwnik się przesiadł, szukaj gdzie).
2. **Czy Płock wrócił z innego IP:** szukaj wzorca zachowania, nie IP —
   paginacja `sort=date_desc&strona=N`, sesje wieczorne >2h bez przerwy >5 min,
   interwały 2-5 s, UA z `wv` (WebView) lub DuckDuckGo, brak sesji w GA4 przy
   setkach ofert w logach.
3. **Czy operacja proxy zmieniła UA:** sygnatura = rotacja IP (1-3 req/IP) na
   `/oferta/`, frozen UA (jeden identyczny string z tysięcy IP), pobieranie
   miniaturek 190x132. Sprawdź też, czy frozen Chrome/142 nadal chodzi
   (350-880 req/dz.) — może podbił wersję.
4. **Czy huby Voyah Free / Chery Tiggo 8 nadal są cyklicznie odpytywane**
   (monitoring cen) — i czy doszły nowe modele-cele.
5. **Czy treści wypłynęły:** WebSearch po unikalnych frazach z 2-3 losowych wiki
   (`asiaauto_wiki_body` w termmeta — bez podkreślnika!). Szczególnie west-motors.pl.

## Gotchy (nie ucz się ich drugi raz)

- UA `Android 10; K` = standardowy zredukowany UA KAŻDEGO Chrome Mobile — nie sygnał bota.
- CGNAT (Play/T-Mobile/Netia-mobile) = wielu userów za 1 IP — nie oceniaj po liczbie ofert z IP.
- Googlebot weryfikuj po zakresach (googlebot.json) / rDNS `crawl-*.googlebot.com`, nie po UA.
- GPTBot / OAI-SearchBot / ChatGPT-User — NIE blokować, to pożądane AEO.
- Najlepszy dyskryminator bot-vs-człowiek: brak przerw >5 min w wielogodzinnej sesji.

## Jak wygląda „zrobione"

Werdykt per punkt 1-5 + decyzja: (a) status quo, (b) korekta blokad,
(c) eskalacja (watermark / twardsze środki — tylko za zgodą Janka).
Wynik dopisać do docs/decyzje/ (nowy ADR tylko przy zmianie decyzji).
