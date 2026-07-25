# Prompt do nowego wątku — audyt metadanych YouTube (kanał + 8 filmów z 24.07)

> Wklej w nowym wątku: „przeczytaj `docs/sesje/2026-07-26-prompt-audyt-metadanych-yt.md`".
> Tło: `2026-07-24-youtube-kanal-demand-gen.md`, `2026-07-25-yt-ads-dg-vid-naprawa.md`, memory `project_youtube_kanal_i_demand_gen_2026_07_24.md`.
> Stan metadanych zebrany przez YouTube Data API **25.07.2026**.

---

## 1. Co robimy

Audyt i poprawa metadanych na kanale `UCsfcUP3uwrDxPI_mDIle8Pw` (@primaauto_com_pl): **opis kanału** + **8 filmów opublikowanych 24.07**. Nie ruszamy 11 starych Shortsów — Janek tego zakazał.

**Zapis wymaga Chrome/YouTube Studio** — Janek ma tylko delegację Studio, kanał nie jest Brand Accountem, więc OAuth go nie pokaże. Odczyt przez API działa (`~/secrets/google/youtube-tokens.json`). Automatyzacja przez `mcp__claude-in-chrome__*` na sesji Janka; zapis może żądać potwierdzenia tożsamości (klika Janek).

---

## 2. Zastrzeżenia Janka (to jest lista zadań)

### A. Opis kanału — nazewnictwo i brakująca treść
Obecny opis zaczyna się od **„Prima Auto – specjalista w imporcie samochodów z Chin"**. Janek: *wszędzie posługujemy się „bezpośredni importer"* — do ujednolicenia.

Do dopisania (treść od Janka, do wplecenia w opis):
> Auta z rynku chińskiego dostępne na indywidualne zamówienie. Każdy samochód przed zakupem jest przez nas weryfikowany, a następnie sprowadzany do Polski dla klienta.

Dodatkowo w obecnym opisie znalezione:
- literówka **„📠 Tell: +48 721 730 507"** — powinno „Tel.", plus ikona faksu przy numerze telefonu
- drugi numer w dziwnym wcięciu: `.             +48 721 730 512`
- wymienione marki: **BYD, Zeekr, Denza, NIO, Li Auto** — zweryfikować, czy faktycznie mamy je w ofercie (realnie sprzedajemy m.in. Exeed, Jetour, BYD/Denza; NIO i Li Auto do sprawdzenia w bazie)
- **brak linków** do `/w-rzeszowie/`, `/w-drodze/`, `/samochody/`
- `defaultLanguage` kanału = brak (do ustawienia `pl`)

### B. Opisy filmów — „oferta" brzmi zbyt oczywiście
Każdy opis zaczyna się od **„Ta oferta (dostępny od ręki): <link>"**. Janek: rozważyć **„Ten samochód:"** albo inne, mniej handlowe otwarcie. Sprawdzić też ogólną składność i naturalność zdań.

### C. Brakujące trzy poziomy linków — POTWIERDZONE
Janek miał rację. Obecne opisy filmów poziomych mają tylko dwa z trzech poziomów:

| Poziom | Link | Stan |
|---|---|---|
| Auta dostępne od ręki (plac Rzeszów) | `https://primaauto.com.pl/w-rzeszowie/` | **BRAK we wszystkich 8 filmach** |
| Auta kupione, w drodze do Polski | `https://primaauto.com.pl/w-drodze/` | jest (tylko filmy poziome) |
| Katalog aut do importu z Chin | `https://primaauto.com.pl/samochody/` | **BRAK** — zamiast tego „Ponad 3000 ofert" prowadzi na homepage `/` |

Nagłówek w opisie brzmi *„Auta dostępne od ręki na placu w Rzeszowie oraz w drodze do Polski:"*, a pod nim jest wyłącznie link „W drodze" — czyli zapowiada dwie rzeczy, daje jedną.

**Do decyzji:** czy „Ponad 3000 ofert" ma prowadzić na `/samochody/` (katalog) zamiast na homepage.

### D. Weryfikacja linków
Wszystkie linki użyte obecnie w opisach **zwracają 200** (sprawdzone 25.07): `/w-drodze/`, `/w-rzeszowie/`, `/samochody/`, huby `/samochody/jetour/t2/`, `/samochody/exeed/vx/`, `/samochody/byd/leopard-5/` oraz karty ofert `264454`, `263901`, `270959`, `270792`. Po edycji sprawdzić ponownie (oferty mogą zejść ze stanu).

### E. Tagi — zero we wszystkich 8 filmach
Żaden z 8 filmów nie ma ani jednego tagu. Research konkurencji z 24.07 mówił „tagi bez znaczenia", ale to warto zweryfikować, skoro kanał startuje od zera i nie ma żadnych sygnałów tematycznych. **Do decyzji Janka:** uzupełniamy czy zostawiamy.

### F. Spójność Shorts vs filmy poziome
Shortsy mają okrojone opisy — 3 linie, bez bloku kontaktowego, bez `/w-drodze/`, bez informacji o obsłudze importu. Filmy poziome mają pełny blok. Do ujednolicenia (albo świadoma decyzja, że Shorts zostają krótkie).

---

## 3. Stan faktyczny — 8 filmów (API, 25.07)

| ID | Typ | Wyśw. | Tytuł |
|---|---|---:|---|
| `9W5mp7_CTVk` | Short | **1 436** | Exeed VX (Omoda 11) za 189 tys. zł — 6-osobowy SUV premium, od ręki w Rzeszowie |
| `JREnGhOomqI` | Short | **1 215** | Leopard 5 — terenówka z Chin za 233 tys. zł, dostępna od ręki w Rzeszowie |
| `ptYgxcc99ok` | poziomy | 627 | BYD Leopard 5 za 233 000 zł — terenowy PHEV jak Defender, od ręki w Rzeszowie |
| `X_Olrxx4y1U` | poziomy | 331 | Exeed VX (Omoda 11) za 189 000 zł — 6-osobowy SUV premium, dostępny od ręki w Rzeszowie |
| `4Ave82-_7yg` | Short | 214 | Jetour T2 PHEV za 201 tys. zł — SUV jak Defender, od ręki w Rzeszowie |
| `Gc9khBpjq_I` | Short | 10 | Leopard 5 Flagship za 226 tys. zł — stoi na naszym placu w Rzeszowie |
| `7UTtfN7WaII` | poziomy | 8 | BYD Leopard 5 Flagship za 226 000 zł — hybrydowa terenówka PHEV, od ręki w Rzeszowie |
| `foaG-5R1uzk` | poziomy | 8 | Jetour T2 PHEV za 201 000 zł — hybrydowy SUV 4x4 w stylu Defendera, od ręki w Rzeszowie |

**Wniosek z zasięgów:** Exeed VX (Omoda 11) i Leopard 5 czarny zebrały praktycznie cały ruch; trzy ostatnie z paczki (Flagship ×2, Jetour poziomy) dostały po 8–10 wyświetleń. Prawdopodobne zduszenie przez wrzut całej paczki naraz — **rozważyć rozłożenie kolejnych publikacji w czasie**.

### Wzorzec opisu — film poziomy (przykład `foaG-5R1uzk`)
```
Ta oferta (dostępny od ręki): https://primaauto.com.pl/oferta/jetour-t2-c-dm-2025-264454/
Wszystkie Jetour T2: https://primaauto.com.pl/samochody/jetour/t2/

Jetour T2 C-DM — hybryda plug-in (206 KM, napęd XWD) w stylu klasycznej terenówki. Auto obejrzysz i odbierzesz od ręki na naszym placu w Rzeszowie.

Auta dostępne od ręki na placu w Rzeszowie oraz w drodze do Polski:
W drodze: https://primaauto.com.pl/w-drodze/
Ponad 3000 ofert: https://primaauto.com.pl/
Tel. 721 730 507 lub 721 730 512 (także WhatsApp)
Pełna obsługa importu: transport z Chin, cło, homologacja, rejestracja w Polsce.

#JetourT2 #Jetour #AutaZChin #ImportSamochodow #SUV #PHEV #4x4 #PrimaAuto
```

### Wzorzec opisu — Short (przykład `4Ave82-_7yg`)
```
Ta oferta: https://primaauto.com.pl/oferta/jetour-t2-c-dm-2025-264454/
Wszystkie Jetour T2: https://primaauto.com.pl/samochody/jetour/t2/
Tel. 721 730 507 (także WhatsApp)

#JetourT2 #Jetour #AutaZChin #ImportSamochodow #PrimaAuto #Shorts
```

### Mapowanie auto → oferta → hub
| Auto | Karta oferty | Hub modelu |
|---|---|---|
| Leopard 5 czarny 233 tys. | `/oferta/byd-leopard-5-denza-b5-2025-270959/` | `/samochody/byd/leopard-5/` |
| Leopard 5 Flagship 226 tys. | `/oferta/byd-leopard-5-denza-b5-2025-270792/` | `/samochody/byd/leopard-5/` |
| Jetour T2 C-DM 201 tys. | `/oferta/jetour-t2-c-dm-2025-264454/` | `/samochody/jetour/t2/` |
| Exeed VX 189 tys. | `/oferta/exeed-vx-lanyue-2026-263901/` | `/samochody/exeed/vx/` |

---

## 4. Stan faktyczny — opis kanału (API, 25.07)

Kanał: **„Prima - Auto - Import Samochodów z Chin"**, 2 subskrybentów, 19 filmów, 487 wyświetleń, kraj PL, `defaultLanguage` brak.

```
Prima Auto – specjalista w imporcie samochodów z Chin do Polski i Europy.

Na naszym kanale prezentujemy najnowsze samochody elektryczne, hybrydowe oraz spalinowe
takich marek jak BYD, Zeekr, Denza, NIO, Li Auto i innych.

Znajdziesz tutaj:
🚗 prezentacje samochodów
🔋 testy wyposażenia i nowych technologii
📦 informacje o imporcie aut z Chin
⭐ samochody dostępne w naszej ofercie

Oferujemy kompleksową obsługę:
✅ pomoc w wyborze samochodu
✅ transport z Chin do Polski
✅ przygotowanie auta do rejestracji w UE
✅ wsparcie przy zakupie i finansowaniu

📍 Prima Auto – Rzeszów, Polska
🌐 primaauto.com.pl
🌐 Na naszej stronie znajdziesz ponad 3000 samochodów z Chin!
Szeroki wybór samochodów elektrycznych, hybrydowych oraz modeli premium.
Znajdź swój wymarzony samochód razem z Prima Auto.

📠 Tell: +48 721 730 507
.             +48 721 730 512
```

**Keywords kanału (naprawione 24.07, literówka „impor" usunięta):**
`"leopard 5" "leopard 7" denza "import samochodów z Chin" "auta z Chin" "chińskie samochody" "import aut z Chin" "samochody elektryczne z Chin" "chińskie SUV" BYD Zeekr Voyah Jetour Exeed Hongqi "Prima Auto" Rzeszów`

**Linki na kanale (ustawione 24.07):** `/samochody/`, `/w-drodze/`, homepage, TikTok.
**Playlisty (publiczne, 24.07):** „Auta na placu w Rzeszowie — dostępne od ręki", „W drodze do Polski — auta już kupione".

---

## 5. Ograniczenia i zasady

- **Brand:** „Prima-Auto" z myślnikiem = separator, nie łącznik — spór zamknięty, nie otwierać (memory `feedback_brand_prima_auto_spacing_settled`).
- **Bez emoji w komunikacji B2B do Janka**; w opisie kanału emoji już są — decyzja, czy zostają, należy do Janka.
- **Stare 11 Shortsów NIETKNIĘTE.**
- Nie tworzyć plików na Dysku Janka ani niczego, o co nie prosił.
- Zmiany pokazać do akceptu **przed** zapisem w Studio.

---

## 6. Prompt do wklejenia

```
Audyt metadanych YouTube dla primaauto. Przeczytaj docs/sesje/2026-07-26-prompt-audyt-metadanych-yt.md
— stan zebrany przez API 25.07, nie zbieraj wszystkiego od nowa, tylko sprawdź co się zmieniło.

Zakres:
1. Opis kanału: "specjalista" → "bezpośredni importer" (ujednolicenie z resztą komunikacji);
   dopisać zdanie o autach na indywidualne zamówienie i weryfikacji przed zakupem; poprawić
   "📠 Tell:" i wcięcie drugiego numeru; zweryfikować listę marek (NIO, Li Auto — czy mamy
   w bazie); dodać linki /w-rzeszowie/, /w-drodze/, /samochody/.
2. Opisy 8 filmów z 24.07: "Ta oferta" → rozważyć "Ten samochód"; sprawdzić składność;
   dodać brakujący link do aut od ręki (/w-rzeszowie/) i katalogu (/samochody/ zamiast
   homepage przy "Ponad 3000 ofert"); ujednolicić Shorts z filmami poziomymi.
3. Tagi: zero we wszystkich 8 filmach — decyzja czy uzupełniamy.
4. Zweryfikować, czy wszystkie linki nadal zwracają 200.

Najpierw pokaż propozycje tekstów do akceptu, zapis w Studio przez Chrome po "działaj".
```
