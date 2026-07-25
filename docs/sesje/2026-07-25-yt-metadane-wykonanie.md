# Sesja 2026-07-25 — audyt i przebudowa metadanych YouTube (WYKONANE)

> Realizacja promptu `docs/sesje/2026-07-26-prompt-audyt-metadanych-yt.md`.
> Tło: `2026-07-24-youtube-kanal-demand-gen.md`, `2026-07-25-yt-ads-dg-vid-naprawa.md`.
> Zapis przez Chrome automation w YouTube Studio (delegacja Janka) — **bez potwierdzania tożsamości**.

---

## 1. Recheck stanu (przed zmianami)

| Sprawdzenie | Wynik |
|---|---|
| Wyświetlenia 8 filmów | bez istotnej zmiany vs snapshot (Leopard Short 1215→1222, Leopard poziomy 627→629) |
| 11 linków użytych w opisach | **wszystkie 200** (`/`, `/w-rzeszowie/`, `/w-drodze/`, `/samochody/`, 3 huby, 4 oferty) |
| `NIO`, `Li Auto` w bazie | **POTWIERDZONE** — NIO 156 listings, Li Auto 139. Lista marek w opisie kanału nie kłamie |
| „Ponad 3000 ofert" | 3051 opublikowanych listings — twierdzenie prawdziwe |
| `/w-rzeszowie/` | renderuje 11 aut na placu |
| Tagi | 0 we wszystkich 8 filmach |

### Kluczowe ustalenie: ruch jest organiczny, nie z reklam

[DG] Demand Gen dała **288 obejrzeń wideo** (249 w 24.07 + 39 w 25.07, Ads API). Filmy mają
**3832 wyświetlenia** → ~92% (≈3540) to **organiczny feed Shorts**.

**Konsekwencja dla zakresu:** dwa Shortsy (Exeed 1436, Leopard 1222) są w realnej fali
dystrybucyjnej. **Tytuły i miniatury zostały nietknięte we wszystkich 8 filmach** — podmiana
tytułu w trakcie fali resetuje model CTR i potrafi ją uciąć. Opis i tagi nie wchodzą w CTR
w feedzie, więc tam zmiany są bezpieczne i tam wykonano całość poprawek.

Zakres zatwierdzony przez Janka (quiz): **wszystkie 8 filmów — opisy + tagi**.

---

## 2. Opis kanału — ZMIENIONE (LIVE, 996/1000 znaków)

- „specjalista w imporcie" → **„bezpośredni importer"** (ujednolicenie z resztą komunikacji)
- dopisane zdanie Janka: *„Auta z rynku chińskiego dostępne na indywidualne zamówienie. Każdy
  samochód przed zakupem jest przez nas weryfikowany, a następnie sprowadzany do Polski dla klienta."*
- `📠 Tell: +48 721 730 507` + wcięcie kropką w drugim numerze → dwie czyste linie `Tel.`
  z dopiskiem „(także WhatsApp)"; ikona faksu usunięta
- lista marek rozszerzona o realnie dominujące w katalogu: **Geely (305), AITO (280),
  Hongqi (160), XPeng (153), Jetour, Exeed** obok BYD/Denza/NIO/Li Auto/Zeekr
- dodane **trzy poziomy linków** (`/w-rzeszowie/`, `/w-drodze/`, `/samochody/`)
- usunięty pusty przechwał („Szeroki wybór… Znajdź swój wymarzony samochód"), skrócone bloki
  wyliczeń, żeby zmieścić się w limicie 1000 znaków
- emoji **zostały** (decyzja: bez zmian)
- brand **„Prima Auto" ze spacją** — zgodnie z zamkniętym sporem z 16.07, nie ujednolicamy

**Linki na kanale:** doszedł piąty — „Auta od ręki — plac Rzeszów" → `/w-rzeszowie/`.
Próba przeciągnięcia go na 1. pozycję (link eksponowany na banerze) **nie wyszła** — drag&drop
w Studio nie reaguje na automatyzację. Pierwszy link nadal „Ponad 3000 aut z Chin" → `/samochody/`.
Do ręcznego przestawienia przez Janka, jeśli chce mieć plac na banerze.

---

## 3. Osiem filmów — ZMIENIONE (opisy + tagi, tytuły nietknięte)

Weryfikacja przez YouTube Data API po zapisie — wszystkie 8 na TAK w każdej kolumnie:

| ID | Typ | Wyśw. | tagi | `/w-rzeszowie/` | `/samochody/` | „Ten samochód" | tytuł |
|---|---|---:|---:|:--:|:--:|:--:|:--:|
| `9W5mp7_CTVk` | Short | 1465 | 11 | ✔ | ✔ | ✔ | nietknięty |
| `JREnGhOomqI` | Short | 1231 | 12 | ✔ | ✔ | ✔ | nietknięty |
| `ptYgxcc99ok` | poziomy | 631 | 12 | ✔ | ✔ | ✔ | nietknięty |
| `X_Olrxx4y1U` | poziomy | 344 | 11 | ✔ | ✔ | ✔ | nietknięty |
| `4Ave82-_7yg` | Short | 236 | 10 | ✔ | ✔ | ✔ | nietknięty |
| `Gc9khBpjq_I` | Short | 10 | 12 | ✔ | ✔ | ✔ | nietknięty |
| `7UTtfN7WaII` | poziomy | 8 | 12 | ✔ | ✔ | ✔ | nietknięty |
| `foaG-5R1uzk` | poziomy | 9 | 10 | ✔ | ✔ | ✔ | nietknięty |

### Co się zmieniło w treści

**Otwarcie:** „Ta oferta (dostępny od ręki):" → **„Ten samochód:"** (mniej handlowe, naturalniejsze).

**Naprawiony zarzut C** — nagłówek *„Auta dostępne od ręki na placu w Rzeszowie oraz w drodze do
Polski:"* zapowiadał dwie rzeczy, a dawał jeden link. Zamiast nagłówka trzy jawne linie, każda
z własnym linkiem:

```
Auta od ręki na placu w Rzeszowie: https://primaauto.com.pl/w-rzeszowie/
Auta kupione, w drodze do Polski: https://primaauto.com.pl/w-drodze/
Katalog ponad 3000 aut z Chin: https://primaauto.com.pl/samochody/
```

„Ponad 3000 ofert" prowadziło na **homepage** — teraz na **`/samochody/`** (katalog). Decyzja podjęta:
homepage nie jest katalogiem, a intencja tej linii jest katalogowa.

**Blok zamykający** przepisany na narrację „bezpośredni importer": *„Jesteśmy bezpośrednim
importerem — auta z Chin sprowadzamy na indywidualne zamówienie i każde weryfikujemy przed zakupem.
Zajmujemy się całością: transport z Chin, cło, homologacja i rejestracja w Polsce."*

**Shorts (zarzut F)** — świadomie **NIE** dostały pełnego bloku kontaktowego. W Shorcie opis jest
zwinięty do jednej linii, klikalny jest praktycznie tylko pierwszy link, a długi blok obniża
czytelność. Dołożono jedną brakującą warstwę (`/w-rzeszowie/`) i podpis „Bezpośredni importer aut
z Chin" — spójność komunikatu bez rozdmuchania.

**Tagi (zarzut E)** — uzupełnione, 10–12 per film. Uzasadnienie: tagi mają minimalny wpływ na
ranking (to prawda z researchu 24.07), ale są **niewidoczne dla widza**, więc ich edycja nie niesie
ryzyka dla filmów w fali — inaczej niż tytuł. Kanał nie miał żadnego sygnału tematycznego.
Koszt ~0, ryzyko 0.

---

## 4. Czego NIE zrobiono

| Rzecz | Dlaczego |
|---|---|
| `defaultLanguage` kanału = `pl` | Studio nie wystawia tego jako zwykłego pola; ustawia się przez wersje językowe („Dodaj język"), co zakłada wprowadzanie tłumaczeń. Kraj = PL już jest, wszystkie filmy mają `pl`. Wartość marginalna — odłożone. |
| Nazwa kanału `Prima - Auto - …` → `Prima Auto — …` | Wymaga osobnej zgody Janka. **Realna wada:** spacje wokół myślnika to jedyna gorsza forma zapisu (Google czyta ją jak separator tytułu → „Prima" + tagline). YouTube limituje zmiany nazwy do 2 na 14 dni. |
| Przestawienie `/w-rzeszowie/` na 1. link kanału | drag&drop w Studio nie działa pod automatyzacją — do ręcznego kliknięcia. |
| Tytuły i miniatury | Świadomie nietknięte (fala organiczna Shortsów). |
| Stare 11 Shortsów | Zakaz Janka — bez zmian. |

---

## 5. Wnioski operacyjne

1. **Publikować po jednej pozycji na dzień**, nie paczkę naraz. Trzy ostatnie filmy z paczki 24.07
   dostały 8–10 wyświetleń, gdy pierwszy Short zebrał 1436. Wrzut całości naraz dusi ogon.
2. **Shorts są dziś jedynym źródłem organicznego zasięgu** — 2872 z 3832 wyświetleń (75%).
   Filmy poziome bez wsparcia reklamowego praktycznie nie startują.
3. **[DG] nie napędza wyświetleń wideo** (288 obejrzeń przy 15,54 + 6,12 zł kosztu) — po naprawie
   z 25.07 warto sprawdzić za 3–4 dni, czy kanały moto weszły do miejsc docelowych.
4. Pomiar efektu tej sesji: CTR i kliki w linki opisu nie są widoczne w Data API — do oceny
   przez GA4 (ruch z youtube.com na `/w-rzeszowie/` i `/samochody/`) po ~7 dniach.

## 6. Artefakty

- `tmp/yt_state_2026_07_25.py` — skrypt odczytu stanu kanału + 8 filmów (READ only, token
  `~/secrets/google/youtube-tokens.json`, tożsamość Victorini wystarcza do odczytu publicznego)
- `tmp/yt-audyt-metadanych-propozycje-2026-07-25.md` — propozycje przedstawione do akceptu
  (kopia: `https://auratest.pl/fe4f58fec53ctmp/primaauto-yt-audyt-metadanych-2026-07-25.md`)
- `tmp/yt-ch-desc.txt` — finalny opis kanału
