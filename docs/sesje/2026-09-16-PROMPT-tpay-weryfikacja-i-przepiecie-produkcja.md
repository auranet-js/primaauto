# T-256 „przepięcie płatności PayU → Tpay” — weryfikacja konta i przepięcie płatności depozytu na produkcję — prompt wykonawczy, 2026-09-16

> **Cel:** po weryfikacji konta produkcyjnego Tpay włączyć klientom płatność depozytu online (BLIK + przelew)
> w kreatorze zamówienia. Kod jest gotowy od v0.41.0 i przetestowany na sandboxie — ten wątek to
> **sprawdzenie produkcji, jedna płatność testowa za 1 zł i świadome włączenie flagi**, nie programowanie.
>
> Przeczytaj przed startem:
> - `docs/VERSIONS.md` → sekcja **0.41.0** (co wdrożone, jak testowane, gotchy Tpay)
> - `docs/biznes/2026-09-16-bramka-platnosci-porownanie.md` → sekcja 6 (braki na stronie przed wnioskiem)
> - `docs/decyzje/2026-09-14-payu-odmowa-wylaczenie-bramki.md` (dlaczego PayU odpadło)
> - memory: `project_payu_odmowa_2026_09_14` (stan Tpay na dole), cross-project `reference_tpay_sandbox`,
>   `feedback_nie_wdrazaj_polowy_funkcji`, `feedback_nigdy_nie_zakladaj_zamowien_bez_zgody`

## Stan wyjściowy (16.09 wieczór)

| Element | Stan |
|---|---|
| Konto Tpay Business (prod) | założone przez Janka 16.09 na `china@primaauto.com.pl`, **czeka na przelew weryfikacyjny Ruslana z Aliora** |
| Klucze Open API prod | `~/secrets/tpay/prod-primaauto.env` + kopia dla WWW `~/domains/primaauto.com.pl/private-payu/tpay.env.prod` |
| Klucze sandbox | `~/secrets/tpay/sandbox.env` + `private-payu/tpay.env.sandbox` (ID akceptanta 424859) |
| Plugin | `asiaauto-sync` **0.41.0** — `class-asiaauto-tpay.php`, trasa `POST /wp-json/asiaauto/v1/tpay/notify` |
| `asiaauto_payu_enabled` | **0** — klienci widzą blok „uruchamiamy” + przelew |
| `asiaauto_payu_mode` | **sandbox** |
| Token prod | działa (`scope: read` to NIE blokada — sandbox też zwraca `read`, a transakcje tworzy) |
| Kanały prod | 27, BLIK = 64; **PragmaPay 85/86 (BNPL) na liście** |
| Zamówienie testowe | **410903**, status `potwierdzone`, depozyt **1 zł**, konto `auranet`, meta przywrócone po testach |

## Zakres

**Robisz:** odczyty (panel Tpay przez API, baza SELECT, logi), test połączenia na prod, **jedną** płatność BLIK
na 1 zł na zamówieniu 410903 (płaci Janek swoim BLIK-iem), włączenie flagi po OK Janka, smoke test, dokumentacja.
**Nie ruszasz:** kodu pluginu (chyba że test na prod pokaże błąd — wtedy stop i diagnoza), treści strony,
regulaminu, innych zamówień, konfiguracji depozytu. **Logowanie do panelu Tpay hasłem robi Janek** — model nie wpisuje haseł.
Włączenie płatności dla klientów (`asiaauto_payu_enabled 0 → 1`) = **osobne „ok” Janka** po teście 1 zł.

## Krok 0 — bramka: czy konto jest zweryfikowane

Nie zgaduj. Pytaj Janka (quiz, rekomendacja pierwsza, zawsze „nie wiem”):
- czy Ruslan zrobił przelew 1 zł z Aliora (`17 2490 1057 0000 9900 2270 3049`),
- czy przyszedł mail z Tpay o aktywacji konta / czy panel pokazuje konto aktywne,
- czy Tpay prosił o dodatkowe dokumenty (mail na `china@primaauto.com.pl`).

Konto niezweryfikowane → **STOP**, zapisz stan w T-256 (QUEUE + strona postępu) i zaproponuj przypomnienie (trigger `przypomnij`).

## Krok 1 — ustawienia panelu Tpay (produkcja) — robi Janek, Ty podajesz co i sprawdzasz efekt

Janek loguje się do panelu produkcyjnego Tpay. Poproś o (po jednym, z opisem gdzie):
1. **Wyłączyć PragmaPay** (płatności odroczone i ratalne dla biznesu) — depozyt to nie zakup na raty.
   Weryfikacja po Twojej stronie: `GET /transactions/channels` na prod — kanały 85/86 znikają albo `available=false`.
2. **Powiadomienia → „Zezwól na nadpisanie” adresu URL** — zaznaczone. Nasz kod podaje
   `callbacks.notification.url`; bez tej opcji Tpay wyśle notyfikację na adres z panelu.
   Alternatywa/pas bezpieczeństwa: wpisać w panelu jako domyślny URL
   `https://primaauto.com.pl/wp-json/asiaauto/v1/tpay/notify`.
3. **Kod bezpieczeństwa** (Powiadomienia → Zabezpieczenia) — tylko odnotuj, że istnieje. Nasz kod weryfikuje podpis
   JWS (wymóg Tpay), `md5sum` nie jest używany — nie wklejaj kodu do czatu, nie jest potrzebny.
4. **Metody:** BLIK i przelewy aktywne; karty — decyzja 16.09: później (nie włączamy).
5. **Adres e-mail powiadomień o transakcjach** — `china@primaauto.com.pl` (kanał kontrolny Ruslana: mail Tpay
   o transakcji vs nasz mail „depozyt opłacony” — brak naszego = webhook padł).

## Krok 2 — test połączenia na prod (bez transakcji)

```bash
cd ~/domains/primaauto.com.pl/public_html
wp eval '$t=new AsiaAuto_Tpay("prod"); var_dump($t->isConfigured(), is_string($t->getAccessToken()));
foreach ($t->getChannels() as $c) echo $c["id"]," ",$c["name"]," ",$c["available"]?"on":"off","\n";'
```
Oczekiwane: `true true`, BLIK (64) `on`, PragmaPay nieobecny lub `off`. Jeśli PragmaPay dalej `on` — wróć do Kroku 1.

## Krok 3 — przełączenie trybu i JEDNA płatność 1 zł na produkcji

Przed: backup meta zamówienia 410903 **jako TSV, nie `wp db export --where`** (eksport tabeli zawiera DROP/CREATE):
```bash
B=~/backups/primaauto/$(date +%F)-tpay-prod; mkdir -p $B
wp db query "SELECT meta_key, meta_value FROM wp7j_postmeta WHERE post_id=410903 AND (meta_key LIKE '_order_deposit%' OR meta_key LIKE '_order_payment%' OR meta_key IN ('_order_status','_order_status_log'))" > $B/order-410903-meta.tsv
wp option get asiaauto_payu_mode > $B/options.txt; wp option get asiaauto_payu_enabled >> $B/options.txt
```

1. `wp option update asiaauto_payu_mode prod` (flaga dalej **0**).
2. Kwota: `_order_deposit_amount` na 410903 = **1** — sprawdź SELECT-em, nie zakładaj.
3. **Płatność robi Janek swoim BLIK-iem** — trasa `/pay` nie jest zarejestrowana przy fladze 0, więc wywołanie idzie
   z CLI. Poproś Janka o świeży kod BLIK (ważny 2 min) i od razu:
   ```bash
   cat > /tmp/claude-1584/prod-blik.php <<'EOF'
   <?php
   $api=new AsiaAuto_PayU_API(); $r=new WP_REST_Request('POST','/asiaauto/v1/order/410903/pay');
   $r->set_param('id',410903); $r->set_header('content-type','application/json');
   $r->set_body(wp_json_encode(['method'=>'blik','blik_code'=>getenv('KOD')]));
   $x=$api->createPayment($r); echo $x->get_status(),' ',wp_json_encode($x->get_data(),JSON_UNESCAPED_UNICODE),"\n";
   EOF
   KOD=123456 wp eval-file /tmp/claude-1584/prod-blik.php   # KOD = kod od Janka
   ```
   (Maili „depozyt opłacony” NIE blokujemy — to test kanału kontrolnego; uprzedź Janka, że Ruslan dostanie maila
   o zamówieniu 410903 za 1 zł, albo zdefiniuj `ASIAAUTO_NO_MAIL` — **zapytaj**, co woli.)
4. Janek potwierdza w aplikacji banku. Obserwuj:
   ```bash
   tail -f ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/logs/asiaauto-sync.log | grep -i tpay
   ```
   Oczekiwane w ≤ 1 min: `Tpay (notyfikacja): depozyt opłacony — zamówienie #410903`.
   Jeśli przyjdzie tylko `przyjęty`, a notyfikacji brak → sprawdź `payment-status` (dopyta Tpay):
   ```bash
   wp eval '$a=new AsiaAuto_PayU_API(); $s=new WP_REST_Request("GET","/x"); $s->set_param("id",410903); echo wp_json_encode($a->getPaymentStatus($s)->get_data());'
   ```
   Zaksięgowane przez `status transakcji`, a nie `notyfikacja` = **notyfikacje nie docierają** → Krok 1 pkt 2, potem
   „Wyślij ponownie” w panelu Tpay i powtórz obserwację. Nie włączaj flagi, dopóki notyfikacja nie przejdzie.
   `Tpay notify: x5u spoza` / `certyfikat podpisu nie jest wystawiony` w logu = tryb nie jest `prod` albo zmiana
   hostów certyfikatu u Tpay → STOP, diagnoza.
5. Dowody do raportu: linia z logu, `_order_deposit_paid_source = tpay`, transakcja w panelu Tpay (Janek), mail Tpay
   o transakcji na `china@` (Janek/Ruslan).
6. **Zwrot 1 zł** — Janek z panelu Tpay (albo zostaje, decyzja Janka). Chargeback przyjdzie notyfikacją i zostanie
   tylko zalogowany (`ZWROT (chargeback)`) — depozytu kod nie cofa; to oczekiwane.
7. Przywróć meta 410903 z TSV (deposit_paid 0, paid_at pusty, bez `_source`, historia prób i log statusów jak
   w backupie) i sprawdź `diff` backup vs stan — pusty.

## Krok 4 — test w przeglądarce (ścieżka przekierowania) — opcjonalnie, na „ok” Janka

Przelew online (przekierowanie na stronę Tpay) nie był widziany w przeglądarce — Cloudflare blokuje curl z serwera.
Wymaga włączonej flagi (trasa `/pay`), czyli okna, w którym klienci też widzą bramkę. Zaproponuj Jankowi:
włączyć flagę → Janek na koncie `auranet` otwiera `/zamow/?order_id=410903`, klika „Płacę teraz przez Tpay”,
płaci 1 zł przelewem → powrót na `/zamow/?order_id=410903&payu=return` → ekran „Depozyt opłacony”. Zrzuty mobile + desktop.
Jeśli Janek nie chce okna z włączoną flagą przed Krokiem 5 — pomiń, BLIK z Kroku 3 wystarczy jako dowód notyfikacji.

## Krok 5 — włączenie dla klientów (osobne „ok” Janka)

Warunek: Krok 3 zaliczony **notyfikacją** (nie odpytaniem). Pokaż Jankowi stan braków ze strony
(sekcja 6 raportu z 16.09 — zwłaszcza checkbox kreatora → `/informacje/regulamin/` „nieodpłatne” i „30% zaliczki”
na stronie procesu) i zapytaj quizem, czy włączamy przed ich poprawą. Reguła `feedback_nie_wdrazaj_polowy_funkcji`:
działający przycisk + sprzeczna treść obok = półprodukt.

Po „ok”:
```bash
wp option update asiaauto_payu_enabled 1
```
Smoke test: `/zamow/` 200; na zamówieniu testowym blok BLIK aktywny (nie „uruchamiamy”); `curl -X POST .../tpay/notify` bez podpisu → 400;
panel „Płatności online — Tpay” → „Testuj połączenie” (tryb `prod`, token otrzymany, BLIK aktywny).
Rollback: `wp option update asiaauto_payu_enabled 0` (bez deploya; `/tpay/notify` działa dalej).

Maile i treści, które przy włączeniu mówią o płatności online — sprawdź, że nie obiecują kart (decyzja: BLIK + przelew):
mail `status_potwierdzone` („opłacisz online (BLIK, karta)”), `/depozyt/`, `/regulamin-uslugi/`, blok kreatora.
Rozjazd = wypisz, nie poprawiaj bez zgody.

## Krok 6 — dokumentacja i domknięcie

- `docs/decyzje/YYYY-MM-DD-tpay-produkcja.md` — ADR: data włączenia, test 1 zł (dowody), ustawienia panelu, rollback.
- `docs/roadmapa/T-121-platnosc-online-depozyt.md` — status; T-256 w `docs/QUEUE.md` i na stronie postępu (`docs/kosztorys/dane/postep.json`: status, po włączeniu do `todo_ukryte_id` + wpis changelog z godzinami, `build_postep.py --deploy`).
- `docs/VERSIONS.md` — tylko jeśli zmieniał się kod.
- memory `project_payu_odmowa_2026_09_14` — stan: produkcja włączona / zablokowana na kroku X.
- Monitoring pierwszych dni: po pierwszej prawdziwej wpłacie klienta sprawdź log (`notyfikacja`), `_order_payments`
  i czy Ruslan dostał oba maile (Tpay + nasz). Zaproponuj przypomnienie w kalendarzu na D+3.
- Commit + push `[order:] ...`.
