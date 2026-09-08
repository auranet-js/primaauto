# 2026-09-08 — Chery Tiggo 9 / 8L / 9X i sprawa Jaecoo: ustalenia, decyzja „nie ruszamy"

> **Status:** rozpoznane, **świadomie bez zmian** (decyzja Janka 08.09.2026)
> **Dotyka:** `data/brand-mapping-v6.1.php`, `data/che168-model-map.php`, taksonomia `serie`,
> huby `/samochody/chery/tiggo-9/`, treść działu wiedzy
> **Powiązane:** `reference_nazwa_termu_serie_idzie_do_tytulu_oferty.md`,
> `project_t019_taksonomia_merge_2026_06_19.md`, `diag/fix-batch-hub-routing.php` (lipcowe sprzątanie termu 3586)

## Zgłoszenie

W filtrze `/samochody/?marka=chery` pojawiają się **dwie pozycje Chery Tiggo**:
`model=tiggo-9` oraz `model=tiggo-9-tiggo-8l`. Do tego obserwacja rynkowa Janka: w Polsce
wnętrze Tiggo 9 wygląda dokładnie jak Jaecoo 8.

## Ustalenia (pomiar, nie hipoteza)

### 1. Skąd duplikat w filtrze

`data/brand-mapping-v6.1.php:495` — wpis `Chery|Tiggo 8L` ma `slug => 'tiggo-9'`, ale
`serie_eu => 'Tiggo 9 (Tiggo 8L)'`. Slug wskazuje istniejący term Tiggo 9, nazwa jest inna,
więc importer nie trafił w term po nazwie, założył nowy, a WP zuniknął mu slug do
`tiggo-9-tiggo-8l` (term **6543**, 1 oferta, ma własny hub z pełną treścią, opisami i FAQ).
Bliźniaczy wpis: `data/che168-model-map.php:944` (`Chery|瑞虎8L`).

To odrost — w lipcu ten sam problem był raz sprzątany (`diag/fix-batch-hub-routing.php:45`,
term 3586 → 3582), ale źródło w mapowaniu zostało nietknięte.

### 2. Pod termem `tiggo-9` (18 ofert) siedzą trzy różne auta

| Auto | Wymiary / rozstaw | Moc | Sztuk |
|---|---|---|---|
| Tiggo 9 właściwy | 4820 × 2820 | 261 KM | 8 |
| **Tiggo 8L** (tytuły „Tiggo 9 (Tiggo 8L)") | 4795 × 2770 | 254 KM | 4 (+1 pod duplikatem 6543) |
| Tiggo 9X, rocznik 2026 | 4817 × 2770 | 254 KM | 5 |

Termy `tiggo-8l` (6612) i `tiggo-7l` (6614) już istnieją, puste — slugi wolne.

### 3. Co mówią źródła

- **Tiggo 8L jest odrębnym modelem w OBU źródłach** (dongchedi 18 ofert po 2024, che168 7) —
  to my go sklejamy mapowaniem, nie źródło.
- **Tiggo 9X nie jest modelem, tylko komplektacją** wewnątrz Tiggo 9
  (`9X 2.0T AT前驱尊贵型 / 豪华型 / 领航型`, 3 z 28 komplektacji dongchedi). Nasze 5 sztuk
  „2026 9X" siedzi pod Tiggo 9 formalnie poprawnie.
- **Jaecoo nie istnieje w żadnym źródle** — sprawdzone pełne słowniki `getFilters`
  (274 marki dongchedi, 282 che168). To marka wyłącznie eksportowa; w Chinach te auta
  chodzą jako Chery / Exeed.

### 4. Jaecoo 8 a Tiggo 9 — obserwacja Janka potwierdzona, ale z zastrzeżeniem

Jaecoo 8 w Polsce: 4820 × 1930 × 1710, rozstaw 2820 — **co do milimetra nasze Tiggo 9**.
Nadwozie i wnętrze to samo auto.

**Ale** Jaecoo 8 w PL sprzedawany jest wyłącznie jako PHEV Super Hybrid 428 KM
(1.5 T-GDI + 34,5 kWh) w cenie **219 900 zł** (5-os.) / 224 900 zł (7-os.), a nasze Tiggo 9
to benzyna 2.0T 254–261 KM za ~130 tys. Te same blachy, inny napęd i inna półka cenowa.
Część źródeł wiąże Jaecoo 8 raczej z Chery Fulwin T10 (PHEV) niż z benzynowym Tiggo 9.

### 5. Popyt (DataForSEO, PL, 08.09.2026)

| Fraza | Wolumen / mc |
|---|---|
| jaecoo 7 | 74 000 |
| **jaecoo 8** | **33 100** |
| chery tiggo 9 | 18 100 |
| jaecoo 8 cena | 4 400 |
| chery tiggo 9 cena | 1 900 |

GSC (90 dni, nasza witryna): `/samochody/chery/tiggo-9/` — 587 impresji / 10 kliknięć / poz. 8,8.
Zapytania „chery tiggo 8 l" — 2 impresje, „jaecoo 8" — 2 impresje. Hub duplikatu
`tiggo-9-tiggo-8l` nie pojawia się w GSC w ogóle.

## Decyzja

**Nie ruszamy.** Ani rozdzielenia Tiggo 8L na własny model, ani kasowania duplikatu termu 6543,
ani przemianowania Tiggo 9 na Jaecoo 8. Stan zostaje taki, jaki jest.

**Dlaczego rebrand na Jaecoo 8 odpada merytorycznie** (gdyby wrócił temat): auto w dokumentach,
homologacji indywidualnej i na tabliczce znamionowej jest Chery Tiggo 9. Jaecoo 8 w PL to
konkretny PHEV za 219 900 zł — klient porównujący 1:1 dostałby inny napęd i inną cenę,
niż sugeruje nazwa.

**Ścieżka, gdyby kiedyś wracać do tematu** (nie realizowana teraz): frazę „jaecoo 8"
(33 100/mc) łapiemy **treścią, nie nazwą modelu** — sekcja „europejski odpowiednik: Jaecoo 8"
w hubie Tiggo 9 plus wpis w dziale wiedzy „Jaecoo 8 czy Chery Tiggo 9 — to samo auto?",
uczciwie rozdzielający napędy i ceny.

## Efekt uboczny, o którym trzeba pamiętać

Dopóki mapowanie zostaje bez zmian, **każda nowa oferta Tiggo 8L z che168 lub dongchedi
trafi do termu 6543 albo dołoży „(Tiggo 8L)" do tytułu oferty pod termem `tiggo-9`** —
duplikat w filtrze będzie się utrzymywał i może odrastać po kolejnych czystkach.
To znany koszt tej decyzji, nie nowy błąd.
