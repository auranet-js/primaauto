# T-211 — Pakiet higieny technicznej

> Status: **gotowy do odpalenia** · Rozmiar: M
> Godziny realnie: **20–26 h** (Janek ~3 h, AI ~17–23 h) · Rynkowo: 50–62 h
> Obniżone z 25–32 h: jedna pozycja jest już zrobiona, dwie okazały się godzinami zamiast dni. **Ale największa z nich jest większa, niż sądziliśmy.**

## Weryfikacja 7 pierwotnych pozycji

| # | Pozycja | Werdykt po zwiadzie |
|---|---|---|
| 1 | Puste strony modeli | 🔴 **WIĘKSZE: 2633 strony, zero noindex** |
| 2 | Puchnięcie bazy | 🟡 aktualne, ale **inna przyczyna** niż zakładaliśmy |
| 3 | Dane strukturalne (schema) | 🟢 **~85% zrobione**, został 1 bug |
| 4 | Chińskie nazwy miast | 🟢 **105 ofert, nie 3000** → pół godziny |
| 5 | Resztkowe 404 | 🟢 16 śmieciowych adresów, świadomie odłożone |
| 6 | Galeria klientów | ✅ **ZROBIONE** (v0.32.57) — wykreślam |
| 7 | Szlify mobilne | ⚠️ **niewyceniane bez zrzutu ekranu** |

---

## 🔴 1. Puste strony modeli — 2633, wszystkie indeksowalne (~6–8 h)

**To jest największa wartość w całym pakiecie.**

- **2397 modeli** i **236 marek** ma **zero ofert** → razem **2633 puste strony**.
- **Żadna nie ma noindex.** Sprawdzone: `class-asiaauto-seo.php:25` — lista „cienkich" taksonomii obejmuje kolor, skrzynię, napęd itd., ale **`serie` i `make` nie są na niej**.
- Sitemap RankMath: **nic nie wykluczone**.

Czyli 2633 puste strony konkurują w Google z naszymi realnymi stronami i rozcieńczają budżet indeksowania.

**Fix jest tani:** warunek „zero ofert → noindex" w dwóch filtrach + wykluczenie z sitemapy.

⚠️ **Jedna decyzja produktowa:** **100 pustych modeli ma napisaną treść wiki** (huby przygotowane pod auta, których nie mamy). Tam „noindex" oznacza wyrzucenie gotowej treści z Google. Do rozstrzygnięcia z Jankiem: schować czy zostawić jako treść bez oferty.

## 🟡 2. Puchnięcie bazy — przyczyna inna niż w opisie (~5–7 h)

Kosztorys mówił o „sierotach". **Sierot jest 11.** To nie jest problem.

Prawdziwy obraz:
| Tabela | Rozmiar |
|---|---|
| `postmeta` | **446,7 MB** (306 531 wierszy) |
| `posts` | 83,7 MB (43 051) |

- **40 595 załączników na 3056 ofert** (~13 zdjęć/auto) generuje **~70% wierszy** w postmeta.
- Suma danych ≈ 130 MB, a tabela zajmuje 446 MB → **~300 MB to narzut i fragmentacja**.
- 269 osieroconych plików multimedialnych.

**Do zrobienia:** rotacja zdjęć sprzedanych/wycofanych aut, defragmentacja tabeli (ostrożnie — blokada na produkcji), limit logów harmonogramu zadań. **Plus rotacja loga pluginu — 17,9 MB i rośnie bez ograniczeń.**

## 🟢 3. Dane strukturalne — bug: kolor nigdy nie trafia do schematu (~2–3 h)

**Wbrew kosztorysowi silnik i skrzynia SĄ już emitowane** (razem z mocą w kW, typem nadwozia, paliwem, napędem, przebiegiem, dostawą).

**Ale jest błąd:** kod pobiera kolor z taksonomii `color` — **która nie istnieje** (są `exterior-color` i `interior-color`). Efekt: **kolor nie jest emitowany dla żadnej z 3056 ofert.**

Fix: jedna linia. Plus usunięcie martwej, starszej wersji generatora schematu (myląca przy czytaniu kodu). Opcjonalnie dorzucić: kolor wnętrza, liczbę drzwi, liczbę miejsc, VIN.

## 🟢 4. Chińskie nazwy miast — 105 ofert (~0,5–1 h)

Mapa tłumaczeń ma **89 miast** i pokrywa cały top (Shenzhen 585 ofert, Dongguan 501, Guangzhou 491…).
**Brakuje 20 miast → 105 ofert** wyświetla surowy chiński znak.

To nie jest osobna pozycja — to dopisanie 20 wpisów. Panel do tego już istnieje.

## 🟢 5. Resztkowe 404 (~1–2 h)

Z 697 adresów raportowanych przez Google **675 to duchy** (już naprawione). **Realnie zepsutych: 16** — śmieci techniczne, stary system, jedna literówka. Świadomie odłożone.

Wartość jest nie w naprawie tych 16, tylko w **automatyzacji sprawdzania** (skrypt + cykliczny raport), żeby nie robić tego ręcznie co miesiąc.

## ⚠️ 6. Szlify mobilne — NIE WYCENIAM bez zrzutu ekranu

W kolejce wisi wpis o różnicy w wyglądzie hubów na telefonie i poziomym przewijaniu — ale **pamięć projektu jest przestarzała** (część problemów naprawiono w v0.31.8), a zgłoszenie nie ma reprodukcji.

**Bez zrzutu ekranu albo konkretnego adresu z problemem to nie jest zadanie, tylko przeczucie.** Do wyceny osobno, gdy będzie repro. Druga pozycja (optymalizacja ładowania CSS) jest świadomie odłożona jako kosmetyka, której realny użytkownik nie odczuje.

---

## Kolejność (od najwyższego zwrotu)

1. **Puste huby (noindex)** — największy zysk SEO, 2633 strony.
2. **Bug koloru w schemacie** — jedna linia, 3056 ofert.
3. **Chińskie miasta** — pół godziny, 105 ofert.
4. **Rotacja loga + baza** — zapobiega problemom, nie naprawia.
5. **404 (automat)** — porządek.

## Testy

**Automatyczne**
- Pusty hub → nagłówek `noindex`, nieobecny w sitemapie. Hub z ofertami → bez zmian (**regresja!**).
- Schemat oferty waliduje się w narzędziu Google i **zawiera kolor**.
- Zero chińskich znaków w nazwach miast na froncie.

**Półautomatyczne**
- Po wdrożeniu noindex: obserwacja w Search Console przez 2–4 tygodnie — czy liczba zindeksowanych stron spada (o puste), a wyświetlenia realnych stron rosną.
- Defragmentacja bazy: **kopia zapasowa przed**, pomiar rozmiaru przed/po.

## Definicja zrobionego

- 2633 puste strony zniknęły z indeksu Google; realne huby nietknięte.
- Kolor pojawia się w danych strukturalnych wszystkich ofert.
- Żadna oferta nie pokazuje chińskiej nazwy miasta.
- Log pluginu ma rotację; baza odzyskała miejsce.
- Sprawdzanie 404 jest zautomatyzowane.
