# 2026-07-12 — Anty-scraping: blokada UA „crawler" + IP Płock (.htaccess)

## Kontekst

Recon logów (13–14.06 + 29.06–12.07, 797k requestów) wykrył **dwie operacje scrapingu** katalogu:

1. **„Tablet z Płocka" — 84.40.222.42** (Netia Static Broadband, Płock). Fizyczny Galaxy Tab S4
   z przeglądarką DuckDuckGo (blokuje GA4 → niewidoczny w analityce). Automat/półautomat:
   6.07 — 5 h bez przerwy >5 min, do 491 ofert/h, interwały 3,7±2,4 s, mechaniczna paginacja
   `sort=date_desc&strona=N`. Zebrał 2188/~3000 ofert (73%), aktywny od czerwca, wraca po nowości.
2. **Operacja na komercyjnych proxy** (m.in. Oxylabs, DZCRD, GTT) — codziennie od ≥13.06.
   HTML przez Puppeteer (frozen UA `Chrome/142.0.0.0` macOS 10_15_7, 350–880 req/dz., ≥1154 uniq ofert,
   2205 IP po 1 requeście); miniaturki przez UA `Mozilla/5.0 (compatible; crawler)` z **6613 rotujących IP**.
   Fokus: cykliczny monitoring hubów Voyah Free (676×) i Chery Tiggo 8 (651×) → monitoring cen/dostępności.

**Oczyszczeni z podejrzeń:** 37.31.42.176, 185.157.14.188 (filtry PHEV/marka + widełki cenowe = kupujący),
91.123.188.241 (Olkusz — w GA4 od maja, sesje 10–20 min = człowiek). Gotcha: UA `Android 10; K` =
standardowy zredukowany UA Chrome Mobile; CGNAT skleja wielu userów pod 1 IP.

**Szkoda na 2026-07-12: bliska zera.** <2% ruchu, kradzież treści NIEPOTWIERDZONA (frazy wiki
nieobecne w Google, west-motors czysty), REST API nie nadużywane.

Pełny raport: `https://auratest.pl/fe4f58fec53ctmp/primaauto-scraping-recon-2026-07-12.html`

## Decyzja (Janek)

Blokada w `.htaccess` (blok `# BEGIN Prima anty-scraping (2026-07-12)`, po Security Headers):

- **403 dla UA dokładnie** `Mozilla/5.0 (compatible; crawler)` (kotwice `^...$` — bingbot/Googlebot
  mają dłuższe UA, nie łapią się).
- **403 dla IP** `84.40.222.42`.

Odrzucone (świadomie): rate-limit (bezsilny na 1 req/IP, ryzyko dla userów za CGNAT),
Cloudflare (nieproporcjonalny do szkody ~0), zabezpieczanie REST (nie jest nadużywane, strefa krucha).
Watermark w pipeline treści — **odłożony, nie odrzucony** (Janek wybrał tylko blokady).

## Konsekwencje / kruchość

- Płock może zmienić IP restartem routera; proxy-operacja może zmienić UA w 5 minut.
  To tani pierwszy krok, nie pancerz.
- Warstwa HTML operacji proxy (frozen Chrome/142 UA) **pozostaje niezablokowana** — UA zbyt
  podobny do realnych przeglądarek, blokada groziłaby false-positive.
- Backup: `.htaccess.bak-2026-07-12`. Smoke test po deploy: strona 200, oferty 200,
  UA crawler 403, bingbot 200, Googlebot 200.
- Re-check logów ~za miesiąc: czy Płock zmienił IP, czy proxy zmieniło UA, czy frazy wiki
  wypłynęły w Google.
