# T-242 — edycja VIN w zamówieniu skutkuje w umowie leasingowej

**Data:** 2026-08-06 · **Wtyczka:** 0.34.20 → **0.34.21** (produkcja) · **Czas:** ~1 h
**Do raportu dla Ruslana:** razem z **T-220** (aneks VIN) — patrz sekcja „Jak to raportujemy".

---

## Zgłoszenie

Z rozmowy z Ruslanem 06.08. Wpisał VIN przy zamówieniu leasingowym, potem chciał go usunąć —
bo VIN-y, które che168 podaje w ogłoszeniach, bywają nieprawidłowe albo nieprawdziwe i przed
podpisaniem umowy trzeba je móc wycofać. Wyczyścił pole w zamówieniu, zregenerował umowę,
a numer w dokumencie **został**. Udało mu się dopiero po skasowaniu VIN-u na samym ogłoszeniu
(drugie auto) — wtedy umowa poprawnie wpisała klauzulę o aneksie.

Ruslan sam wskazał, dlaczego obejście jest złe: „jak usuniesz zamówienie, klient dostaje maile
i wchodzi w zamówienie, którego nie ma — to nie jest kierunek, to trzeba zabezpieczyć".

## Diagnoza

Potwierdzona na danych produkcyjnych, nie z opisu:

- **UL/2026/0001** (zamówienie 407331) — `_order_vin` **puste**, ogłoszenie 407285 ma
  `vin_number = HJNNBDLH1TB772781` (17 znaków, przechodzi guard) → umowa drukowała ten numer.
- **UL/2026/0002** (zamówienie 407351) — ogłoszenie 407318 ma `vin_number` puste → tu zadziałało,
  bo Ruslan wyczyścił VIN na ogłoszeniu.

Przyczyna w `class-asiaauto-contract.php::getVehicleData()`: `vin_verified` (klucz wariantu
leasingowego, T-217) liczył się z tego samego `$vin` co pośrednictwo, czyli z fallbackiem
`_order_vin` → `vin_number` ogłoszenia → `'—'`. **Puste pole zamówienia znaczyło „weź z oferty",
a nie „brak numeru"** — więc edycja w karcie zamówienia nie miała jak zadziałać.

Dwie rzeczy, które to pogarszały:

1. **Kasowanie VIN-u na ogłoszeniu jest nietrwałe** — importer nadpisuje `vin_number` z API przy
   każdej aktualizacji oferty (`class-asiaauto-importer.php:460-462`). Numer wróciłby sam, a wraz
   z nim do umowy przy najbliższej regeneracji.
2. **Pole VIN w karcie zamówienia wypełniało się samo numerem z oferty**, więc dowolny zapis karty
   — choćby zmiana prowizji — po cichu utrwalał go w `_order_vin`, bez decyzji Ruslana i bez
   weryfikacji auta.

## Rozwiązanie

**Umowa leasingowa czyta VIN wyłącznie z pola karty zamówienia.** Wpisany numer się drukuje,
puste pole daje klauzulę wzorca „zostanie dodany aneksem do umowy po weryfikacji auta"
(§2 lit. e + wiersz „VIN" w Załączniku nr 1). Guard formatu (17 znaków bez I/O/Q) bez zmian.

**W karcie zamówienia** pole nie wypełnia się już samo, a pod nim stoi stały pasek
**„VIN OFERTY: … [Wstaw]"** z notą, że numer pochodzi od chińskiego sprzedającego i bywa
zamaskowany albo nieprawdziwy. Ruslan widzi numer bez szukania w ofercie, wkleja jednym
kliknięciem albo zostawia puste — i jego decyzja jest trwała, bo sync ogłoszenia jej nie cofnie.

**Umowa pośrednictwa nietknięta** — klucz `vin` zachowuje stary fallback, 150 istniejących
zamówień renderuje się identycznie.

### Zmienione pliki (produkcja, backupy `*.bak-2026-08-06`)

| Plik | Zmiana |
|---|---|
| `includes/class-asiaauto-contract.php` | `vin_verified` liczone z `$vin_order`, nie z `$vin` |
| `includes/class-asiaauto-order-admin.php` | pole bez auto-wypełniania + pasek „VIN OFERTY" z przyciskiem Wstaw |
| `asiaauto-sync.php` | 0.34.20 → 0.34.21 |

## Test

`tmp/test-vin-guard-2026-08-06.php` — read-only (Reflection na prywatnych metodach, bez PDF,
bez zapisu do bazy), **15/15**. Uruchamiać przy każdej następnej zmianie w generatorze umów,
razem z zestawem T-217. Zakres: brak podstawiania z oferty w leasingu, obecność klauzuli o aneksie,
poprawny druk po wpisaniu VIN-u, guard formatu (zamaskowany / za krótki / z literą O),
regresja pośrednictwa na #407328 i #407317.

## Odrzucone warianty

- **Trójstan z flagą `_order_vin_cleared`** (oferta podstawia się domyślnie, ale jawne
  wyczyszczenie jest trwałe) — napisany i wycofany w trakcie sesji. Rozwiązywał ten sam problem
  kosztem nowej meta i trzech ścieżek w panelu; stały podgląd „VIN OFERTY" daje to samo prościej.
- **VIN wyłącznie na ogłoszeniu, aktualizowany przy regeneracji umowy** (pomysł Janka, odrzucony
  przez niego samego) — jedno ogłoszenie potrafi obsłużyć kilka zamówień na różne egzemplarze.
  Przykład z tego samego dnia: ogłoszenie **387849** ma dwa zamówienia (407317 i 407328). VIN jest
  cechą egzemplarza, nie oferty.

## Jak to raportujemy

T-242 i T-220 to jedna sprawa widziana z dwóch stron i tak idą do Ruslana:

- **T-242 (zrobione)** — *usunięcie* błędnego VIN-u przed podpisaniem umowy działa i jest trwałe.
- **T-220 (do zbudowania)** — *dopisanie* prawdziwego VIN-u po weryfikacji auta, aneksem.

Dla Ruslana wynika z tego prosty obieg: dopóki nie ma pewnego numeru, zostawia pole puste i umowa
sama zapowiada aneks; gdy auto przyjedzie i VIN się potwierdzi, wpisuje go i generuje aneks.

## Otwarte

- **Umowa pośrednictwa dalej drukuje zamaskowane VIN-y z che168** (14 listingów z 267) i bierze
  numer z oferty. To decyzja D4 z T-217, świadomie nieruszona — dotyczy 150 działających umów.
- **`regenerate()` kasuje poprzedni PDF** (`wp_delete_attachment(force=true)`, brak wersjonowania) —
  regeneracja po podpisaniu niszczy egzemplarz klienta. Znane z T-217, wciąż otwarte.
- **UL/2026/0001 nie zostało zregenerowane** — świadomie, z powodu punktu wyżej. Ruslan robi to sam,
  gdy zdecyduje.
- **Panel niezweryfikowany klikiem** — sprawdzony lintem i kontrolą zmiennych; sam przycisk „Wstaw"
  wymaga potwierdzenia w adminie.
