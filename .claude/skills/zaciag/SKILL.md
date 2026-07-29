---
name: zaciag
description: Audyt nowego zaciągu ofert w PrimaAuto — gdy Ruslan dodał ręcznie auta albo wjechały nowe marki/modele. Uruchamiaj, gdy padnie „zaciąg", „Ruslan dodał auta", „sprawdź nowe oferty", „nowe auta z wczoraj", „wjechały nowe modele". Sprawdza taksonomię, mapowania, wyposażenie, treść hubów i indexing.
---

# Audyt nowego zaciągu ofert

Źródło prawdy: **`docs/procedury/audyt-nowego-zaciagu.md`** — przeczytaj ją na starcie i trzymaj się jej.
Ten plik jest tylko wyzwalaczem i przypomina, czego nie wolno pominąć.

## Czego ten skill NIE robi

**Nie jest automatem podejmującym decyzje.** Decyzja Janka 2026-07-29: do każdej oferty trzeba
podejść indywidualnie, bo rozstrzygnięcia — tożsamość modelu, nazwa eksportowa, routing sub-marki —
wymagają danych z zewnątrz i osądu. **Nie proponuj budowy audytora.** Zautomatyzowane są wyłącznie
pomiary. Każde rozstrzygnięcie pokaż Jankowi z danymi, na których je opierasz.

## Krok 1 — rozpoznanie (read-only)

```
cd ~/domains/primaauto.com.pl/public_html
wp eval-file ~/projekty/primaauto/scripts/zaciag-recon.php [YYYY-MM-DD] --skip-plugins=wordfence
```

Bez daty bierze ostatnie 2 doby. Zwraca oferty ręczne z flagami, niezmienniki taksonomii
(rozjazdy `serie.parent` vs `make` — **stan zdrowy to 0**), śmieciowe marki i pokrycie mapowań.

Flagi i co znaczą:

| flaga | znaczenie |
|---|---|
| `ROZJAZD-PARENT` | serie pod inną marką niż post → hub soft-404 pod naturalnym URL-em |
| `PREFIKS-MARKI` | nazwa termu z marką → slug typu `/foton/foton-mars/` |
| `CJK-W-NAZWIE` | importer nie zmapował, nazwał chińszczyzną |
| `UŻYTKOWY` | 7-cyfrowy `spec_id` → auto-api oddaje szczątkowe dane, wyposażenie i wersja z katalogu |
| `EP-CHUDY` / `EP-ROZWALONY` | wyposażenie do uzupełnienia lub JSON nie parsuje się |
| `BRAK-SPECID` | niepełna odpowiedź API („pusta wydmuszka") → oferta do ponownego zaciągu |
| `BRAK-WERSJI` | tytuł skończy się na roczniku, wersję weź z tytułu strony katalogu |

## Krok 2 — wykonanie wg procedury

Kolejność jest wiążąca, każdy krok jest wejściem dla następnego:

**taksonomia/tożsamość → mapowania → wyposażenie → treść hubów → Indexing NA KOŃCU**

Indexing przed ustaleniem docelowych URL-i i treści pali budżet dwa razy na tych samych adresach.

Szczegóły, pułapki i gotowe wywołania skryptów — w `docs/procedury/audyt-nowego-zaciagu.md`.

## Trzy zasady, które łamie się najczęściej

1. **Tożsamość rozstrzyga katalog Autohome, nie API.** Pole `model` z auto-api kłamie (Voyah wracał
   jako „Zeekr", Sienna jako `华东牌商务车`). Sprawdzaj `car.autohome.com.cn/config/spec/{spec_id}.html`.
2. **Mapowanie testuj wyłącznie ścieżką** `getOffer() → Che168_Adapter::normalize() → getEuForCn()`.
   Na surowych danych `getEuForCn()` zawsze zwróci null i będzie wyglądać na porażkę mapowania.
3. **301 zakładaj PRZED skasowaniem starej marki.** Odwrotna kolejność = okno 404 na rankującym URL-u.

## Zanim powiesz „gotowe"

Backup przed każdą zmianą (`~/backups/primaauto/<data>/`). Na koniec sprawdź:
rozjazdy **0**, huby 200 bez „Nie znaleziono", `aa-spec` i `FAQPage` obecne, H1 i title poprawne,
stare URL-e 301, mapy wobec backupu **zmienionych 0 / usuniętych 0**, meta title 61–73 znaków.

Pamięć: `project_procedura_audyt_nowego_zaciagu_2026_07_29`, `project_zaciag_reczny_28_07_wykonanie`.
