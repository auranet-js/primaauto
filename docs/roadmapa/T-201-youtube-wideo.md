# T-201 — Wideo: assety reklamowe + kanał YouTube

> Status: **rozbity na dwie pozycje** (rekomendacja po zwiadzie 14.07)
> ⚠️ **Blokujące pytanie do Ruslana:** kto kręci TikToki i czy mamy pliki źródłowe?

## 🎯 Odkrycie zwiadu: Prima-Auto MA już 127 filmów

**TikTok `@primaauto.pl`: 127 filmów, 319 obserwujących, 1778 polubień.** Ktoś to kręci i montuje od dawna.

Tego nie było w żadnym dokumencie projektu. **Cała wycena T-201 zależy od jednej odpowiedzi:**

> **Kto produkuje te filmy i czy mamy do nich pliki źródłowe?**
> - Jeśli **tak** → YouTube to przepakowanie istniejących materiałów (**10–16 h**).
> - Jeśli **nie** (robiła to poprzednia agencja) → produkcja od zera (**30–46 h**).

### Recon techniczny pobierania (2026-07-14) — sam zwiad, NIC nie pobrano poza 1 testem

Sprawdzenie, czy w razie braku plików źródłowych da się odzyskać materiał wprost z TikToka:
- **Profil `@primaauto.pl` publiczny** (`privateAccount: false`) — bez logowania. Wszystkie **127 filmów wylistowane** (yt-dlp `--flat-playlist`).
- **Test pobrania 1 filmu OK:** `720×1280` (pion 9:16 = natywny YouTube Shorts), **bez znaku wodnego** (czysty stream, nie wersja „playwm"), ~4,6 MB / 51 s. Całość szacunkowo **~600–650 MB**.
- **Tytuły opisowe** (marka + model + rok + wersja, np. „MAZDA EZ6 2025", „Leopard 7 AWD Ultra PHEV 2026") → gotowa baza pod nazewnictwo (T-215) i mapowanie film→model (T-216).
- **Jakość = 720p, nie 1080p** — TikTok trzyma niższą rozdzielczość. Pliki źródłowe od montażysty nadal lepsze; re-download to plan B, ale **plan B działa i jest potwierdzony**.
- Narzędzie: `yt-dlp` zainstalowane przez `python3 -m pip install --user` (konto host476470).
- **Blokujące pytanie o oryginały 1080p nadal otwarte** — recon tylko potwierdził, że ścieżka awaryjna istnieje. Pełnego pobrania (127 szt.) NIE uruchomiono — czeka na decyzję.

**Bez tej odpowiedzi nie wyceniamy T-201b.**

---

## T-201a — Assety wideo + kampania Video/Demand Gen ✅ ROBIĆ

> Godziny realnie: **10–16 h** · Rynkowo: 25–40 h · **Mierzalne w Ads/GA4**

**To jest tania i sensowna część.** Kampania wideo w Google Ads **nie wymaga popularnego kanału** — wymaga kanału jako kontenera i 2–3 filmów (15–30 s).

Stan konta (9506068500): **8 kampanii w wyszukiwarce + 1 display. Zero wideo. Zero assetów YouTube.** Budżet dzienny 152 zł.

**Materiał, który już mamy:**
- **53 269 zdjęć ofertowych** (średnio 17,5 na auto)
- Pipeline generowania grafik (Gemini, ~$0,035/szt., 18 już zrobionych) — gotowy do intro/outro
- Gotowe teksty hubów jako podkład treściowy
- 6 aut fizycznie na placu w Rzeszowie

**Plan:**
1. Kanał YouTube jako kontener (Brand Account — **decyzja: konto firmowe Ruslana, nie prywatne**).
2. 2–3 filmy 15–30 s: pokaz oferty (zdjęcia + ruch kamery + lektor + branding).
   ⚠️ **Maskowanie chińskich tablic i znaków wodnych** ze zdjęć Dongchedi.
3. Kampania Video (in-stream/Shorts) lub Demand Gen, celowana najpierw w **remarketing** (najtańszy, najcieplejszy ruch).
4. Pomiar w GA4/Ads — to jedyna część T-201 z policzalnym zwrotem.

**Ograniczenie prawne (twarde):** **NIE wolno** generować AI-wideo konkretnego egzemplarza („oto Twoje auto") — to materiał dowodowy w modelu pośrednictwa. AI tylko do treści redakcyjnych (rankingi, porównania).

---

## T-201b — Kanał YouTube jako kanał organiczny ⚠️ DECYZJA RUSLANA

> Godziny realnie: **10–16 h** (jeśli mamy pliki TikToka) **albo 30–46 h** (produkcja od zera)
> **+ produkcja regularna 8–14 h/mc** — bez niej kanał jest martwy

### Uczciwa ocena — nisza jest pusta, ale to sygnał dwuznaczny

| Konkurent | YouTube |
|---|---|
| **azjaauto.com** | **brak kanału** |
| **chinskisamochod.com** | 21 filmów, **18 subskrybentów**, 95–191 wyświetleń — praktycznie martwy |
| **west-motors.pl** | **136 tys. subskrybentów**, aktywny (~8–9 filmów/mc) — **ale po rosyjsku, dla rynku RU/BY**, na szerokim asortymencie (USA/Japonia/Korea), nie na chińskich EV dla Polaków |

**Interpretacja bez ściemy:** bezpośredni konkurenci nie robią YouTube'a wcale albo robią go fatalnie. Nisza jest pusta — ale to znaczy albo „nikt nie spróbował", albo „próbowali i nie ma tam pieniędzy". **Nie mam danych, żeby to rozstrzygnąć.**

Dodatkowo: ruch na YouTube wokół chińskich aut **istnieje**, ale to widownia **gapiów** — dokładnie ta, którą świadomie **wykluczyliśmy z kampanii w wyszukiwarce** (frazy „youtube", „test", „recenzja" są u nas negatywami, bo nie kupują).

**Ryzyko: 40 h pracy + 10 h/mc → 300 wyświetleń.** Przykład `@ChinskiSamochod` (21 filmów → 18 subskrybentów) pokazuje, że to nie jest teoretyczne.

### Warianty produkcji

| Wariant | Koszt/film | Uwagi |
|---|---|---|
| **A. Przepakowanie TikToka na Shorts** | 15–20 min | ⚠️ **wymaga plików źródłowych** — najtańszy, jeśli je mamy |
| **B. Pokaz ze zdjęć + lektor** (build pipeline 14–20 h) | 20–40 min | Skalowalne, ale YouTube algorytmicznie karze pokazy slajdów. Dobre jako **assety do reklam**, słabe jako kanał organiczny |
| **C. AI-wideo** | 1,5–3 h | ⛔ **zablokowane** — brak potwierdzonego dostępu + zakaz dla materiału dowodowego |
| **D. Kręcenie na placu** | 2–4 h + czas Ruslana | Jedyny wariant dający autentyczność („odbiór auta przez klienta" — to realnie sprzedaje). Ale na placu stoi **6 aut**, a prośba o własne zdjęcia wisi niewykonana od kwietnia |

## Rekomendacja

**Robić T-201a** (assety + kampania wideo) — tanie, mierzalne, z materiałów, które już mamy.

**T-201b oddać do decyzji Ruslanowi** — bo to inwestycja w kanał, nie w kampanię, i zwrot jest niepewny. Przed decyzją: **odpowiedź o TikToki.** Ona przesądza, czy to 16 h czy 46 h.

## Definicja zrobionego (T-201a)

- Kanał-kontener istnieje, połączony z Google Ads.
- 2–3 filmy reklamowe, bez chińskich tablic i znaków wodnych.
- Kampania wideo działa, mierzona w GA4/Ads.
- Wiadomo, czy wideo obniża koszt kontaktu — czy nie.
