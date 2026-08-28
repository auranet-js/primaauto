# Prompt kontynuacyjny — co jeszcze blokuje reklamy Mety i co już mamy pod opieką

> Wątek otwarty po sesji 28.08. Dwa pytania, oba na dowodach, nie z notatek:
> **czy blokery z pisma do klienta nadal stoją** i **co realnie mamy zbudowane
> w opiece nad reklamami Meta**.

---

## Skąd zaczynasz

Źródło prawdy o planie: `docs/meta/plan-kampanii.md` (po korekcie wieczornej 28.08).
Pismo do klienta w wersji wysłanej: `docs/meta/prosby-koncowe-2026-08-28.html`
(kopia: `https://auratest.pl/fe4f58fec53ctmp/primaauto-prosby-koncowe-v5-2026-08-28.html`).

Pismo jest **mocno okrojone przez Janka** — świadomie wypadły z niego: limit wydatków
i rola `MANAGE` (Ruslan zasila konto ręcznie, pilnują tego z Jankiem — **nie wracaj
do tematu**), powiązanie kanału YouTube z Google Ads (**połączenie ISTNIEJE**, moje
`account_link`/`data_link` = zero wierszy było błędnym wnioskiem — te zasoby tego nie
pokazują; nie powtarzaj tej tezy), kalendarz publikacji z Andrzejem, cała sekcja
„o co nie prosimy", tabela zbiorcza i wstęp.

---

## Część 1 — czy blokery z pisma nadal stoją

Cztery pozycje, każda ma zmierzalny stan. Sprawdź **każdą osobno**, nie zbiorczo:

| Poz. | Co sprawdzić | Czym | Stan 28.08 |
|---|---|---|---|
| R5 | weryfikacja reklamodawcy (DSA) | `python3 scripts/social/dsa_status.py` | STOI — `100/3858196` na PL, US przechodzi |
| R6 | weryfikacja domeny `primaauto.com.pl` | `curl -s https://primaauto.com.pl/ \| grep facebook-domain-verification` **oraz** `dig +short TXT primaauto.com.pl` | BRAK obu. Edge `owned_domains` niedostępny dla naszej appki w v19–v25 — **nie szukaj go ponownie** |
| R7 | scope `read_insights` + `pages_read_user_content` | `debug_token` na tokenie SU (patrz `scripts/social/meta_api.py`) | BRAK — 11 scope'ów, żadnego z tych dwóch |
| R1 / R8 | regulamin list z pliku / regulamin kontaktów | `POST /act_*/customaudiences` z `subtype=CUSTOM` **tylko przez `grupy_odbiorcow.py`**; `{page}/?fields=leadgen_tos_accepted` page tokenem | `200/1870090` / `false` |

**Jeśli R5 padł** — domknięcie kampanii to jedno wywołanie:
`python3 scripts/social/meta_kampania_wideo.py --domknij`
(dokłada zestaw reklam z wykluczeniami i reklamę do istniejącej kampanii
`120248809387930243`, kreacja `4545681435750921`, wszystko `PAUSED`).
**Włączenie z budżetem to osobna zgoda Janka na konkretną datę** — patrz
`docs/meta/plan-kampanii.md` sekcja 9 i memory `feedback-publikacja-zewnetrzna-wymaga-osobnej-zgody`.

**Jeśli R7 padł** — sprawdź `debug_token`, nie przyjmuj na słowo, że trzeba wymieniać plik
z tokenem (memory `reference-token-system-user-scopes-dynamiczne` mówi, że scope'y dziedziczą
się na istniejące tokeny — zweryfikuj to pomiarem).

---

## Część 2 — co realnie mamy w opiece nad reklamami Meta

Wypisz stan faktyczny, **z dowodu z API, nie z tej listy** — ona jest punktem wyjścia,
nie prawdą. Co powinno być na 28.08 wieczorem:

**Zbudowane i żywe**
- piksel `1634147041766916` — 14 tys. PageView / 7 dni, wpięty w `external_event_sources` katalogu;
- Conversions API działa na tokenie SU (`~/secrets/meta/capi_send.sh` → `events_received: 1`);
- katalog `2936723456671912` — 3 113 pozycji, feed dobowy 06:30, dziesięć pobrań bez błędu,
  pięć zestawów produktów;
- **komplet ośmiu grup odbiorców** (`python3 scripts/social/grupy_odbiorcow.py` — zestawienie);
- kampania `[VID]` + kreacja + wideo — `PAUSED`, bez zestawu reklam (bloker R5).

**Narzędzia** w `scripts/social/`
```
meta_api.py          warstwa wspólna; guard GLUCHE_NA_WALIDACJE
grupy_odbiorcow.py   komplet grup: podgląd / --zaloz / --przemianuj
rotator_postow.py    [POST] — dwie tabele do decyzji, --wstaw / --wyjmij
rotator_wideo.py     [VID] — materiał z Dysku, faststart, --wgraj / --wstaw
meta_kampania_wideo.py  --dry-run / --buduj / --domknij
dsa_status.py        czy PL przechodzi walidację
```

**Czego NIE mamy i warto to nazwać przy okazji**
- wyników organicznych postów (R7) — więc rotacja `[POST]` idzie dziś na regule z bazy,
  nie na zasięgu;
- sygnału „na placu" w feedzie pojazdów (`address.city` Rzeszów dla wszystkich pozycji) —
  to nasza robota w `scripts/build-meta-vehicle-feed.php`, nie prośba do klienta;
- czystej biblioteki reklam: do skasowania `4040666229569687`, `2853400968357319`
  (wideo testowe) oraz dwa testowe obrazy — patrz `plan-kampanii.md` sekcja 7.

---

## Pułapki, które już kosztowały — nie wchodź w nie drugi raz

- **`/act_*/customaudiences` IGNORUJE `validate_only`** — sonda tworzy prawdziwe grupy.
  Guard siedzi w `meta_api.py`. Rozpoznanie: `{"success": true}` = walidacja,
  `{"id": …}` = obiekt powstał. Memory: `reference-meta-customaudiences-ignoruje-validate-only`.
- **Trzy różne kody błędu to trzy różne przyczyny**, dopóki nie udowodnisz, że jest inaczej.
  Zlepienie `1870090` + `1870053` + `1713151` w „jeden regulamin" dało błędne pismo do klienta
  i drugie sprostowanie tego samego dnia.
- **`meta_kampania_wideo.py --buduj` robi NOWĄ kampanię przy każdym biegu.** Do dokładania
  brakujących obiektów jest `--domknij`.
- Landingi Denzy działają tylko na slugach bez prefiksu marki (`z9-gt-dm-i`, `n9-dm-i`).
- `published_posts` wymaga **page tokenu**, mimo że SU ma `pages_read_engagement`.

---

## Jak wygląda „zrobione"

Jedno zestawienie dla Janka w czacie: **co z blokerów padło, co stoi** (z dowodem przy każdym)
i **co mamy pod opieką** (z dowodem, nie z tej listy). Bez pisma do klienta — pismo poszło
28.08 i kolejnego nie piszemy, dopóki Janek nie powie.
Jeśli R5 padł, dopisz jedno zdanie: co dokładnie zostaje do uruchomienia kampanii i czego
do tego potrzeba od Janka.
