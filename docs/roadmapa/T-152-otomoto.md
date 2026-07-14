# T-152 — Wystawianie ogłoszeń na Otomoto

> Status: **GATED na konto Business + klucze API** · Rozmiar: L
> Godziny realnie: **spike 4–6 h + wdrożenie 45–65 h** (Janek ~8–10 h, AI ~40–58 h) · Rynkowo: 120–160 h
> **Podniesione z 40–50 h** — poprzednia estymata nie uwzględniała mapowania chińskich marek na słownik Otomoto ani synchronizacji statusów.

## Po co

Automatyczna publikacja ofert na największym polskim portalu motoryzacyjnym — kanał sprzedaży poza własną stroną, tam gdzie klient realnie szuka auta.

## Co mamy z projektu Desal (i to sporo)

Auranet ma **działającą integrację Otomoto w projekcie Desal** (części samochodowe). Do przeniesienia **jako wzorzec, nie jako kod** (Desal to CodeIgniter, my WordPress):

| Element | Reuse |
|---|---|
| Flow OAuth2 (Basic auth + grant `partner`, token 12 h, auto-refresh) | ✅ przepisać 1:1 na `AsiaAuto_Otomoto` |
| Wrapper HTTP | ⚠️ przepisać **z naprawą** — Desal **ignoruje kody HTTP i nie loguje** → ciche awarie. U nas obsługa kodów + log od dnia zero |
| Kształt payloadu `POST /account/adverts` | ✅ struktura zostaje, `params` wymienić na samochodowe |
| Zdjęcia: `POST /imageCollections` (wysyła się **URL-e, nie pliki**) | ✅ **u nas prawie darmowe** — mamy publiczne URL-e w `uploads/asiaauto/` |
| Zapis `otomoto_id` + `otomoto_url` na obiekcie | ✅ → post meta na `listings` |
| Discovery słownika (`GET /categories/{id}` → `parameters`) | ✅ **to jest narzędzie, którym zbudujemy mapę marek** |
| Cache słowników + cron refresh | ✅ wzorzec gotowy |
| Panel „Sprawdź połączenie" (live test OAuth) | ✅ dobry UX, warto skopiować |

Dokumentacja: `~/projekty/desal/docs/API_REFERENCE_OTOMOTO.md` (220 l.), ADR o synchronizacji statusów (`2026-06-17-otomoto-pkt3-delete-sync-analiza.md`).

## Czego NIE ma i trzeba napisać

1. **🔴 Mapa marek/modeli CN → taksonomia Otomoto.** To jest największy koszt i największa niewiadoma. Otomoto wymaga w `params` **slugów ze swojego słownika**, nie tekstu. BYD, Zeekr, Li Auto, AITO, Leapmotor, Chery — część Otomoto zna, część nie, część pod inną nazwą. Mamy własne piekło mapowania CN→EU (mapa v6.2) — tu dochodzi **drugie mapowanie, na słownik Otomoto**. Fallback dla marki nieznanej: nie wystawiamy (nie „Inny").
2. **🔴 Model danych ogłoszenia samochodowego** — rok, przebieg, paliwo, skrzynia, pojemność, moc, nadwozie, kolor, VIN, liczba drzwi/miejsc. Dane mamy (100% pokrycia), ale mapowanie **wartości na slugi Otomoto** to nowa robota.
3. **🔴 UPDATE ogłoszenia** — Desal **nie ma edycji** (tylko create + activate/deactivate). U nas konieczne, bo ceny zmieniają się przy syncu. Endpoint do zweryfikowania po dostaniu kluczy (`PUT /account/adverts/{id}` — **niepotwierdzony**).
4. **🔴 Reconciliacja statusów** — API **nie ma webhooków**, tylko polling. Cron `GET /account/adverts/` → aktualizacja meta → kolumna „Otomoto: aktywne / wygasłe / odrzucone" w liście ogłoszeń. *Desala to zabija przy 11,5 tys. ogłoszeń — **my mamy rzędy wielkości mniej, więc polling jest u nas tani**.*
5. **🔴 Spięcie z naszym lifecycle'em** — auto sprzedane/zarezerwowane (`_asiaauto_reservation_status`) musi **zniknąć z Otomoto**. Rotacja ofert też. To całkiem nasz kod.
6. **🔴 Panel admina** — kolumna statusu, akcja „wystaw na Otomoto", bulk, log.

## ⚠️ Bramki formalne (blokują harmonogram, nie budżet)

- **Ruslan musi mieć konto Business/dealerskie Otomoto** — z API mogą korzystać wyłącznie klienci biznesowi.
- **Rejestracja integratora** (formularz `otomoto.pl/news/rejestracja-api`): dane firmy, opis aplikacji. Klucz idzie na **Auranet** (my utrzymujemy aplikację).
- **Czas oczekiwania na klucze: brak SLA.** W Desalu to były dni–tygodnie. → **Wniosek składać natychmiast po decyzji „bierzemy", równolegle do innych prac.**
- *Bonus:* klucz API bywa uniwersalny — jeśli Auranet ma go dla Desala, prawdopodobnie obsłuży też PrimaAuto (do sprawdzenia).

## ❓ Niewiadoma handlowa — DO USTALENIA Z OTOMOTO

**Nie wiemy, ile kosztuje wystawienie auta.** W API są endpointy pakietów (`GET /packets`, `GET /paid-features`) → model płatny istnieje. Czy PrimaAuto zmieści się w pakiecie dealerskim i ile to kosztuje przy ~3000 ofert — **do wyjaśnienia przy wniosku o konto Business**. To może być koszt większy niż samo wdrożenie.

## ⚠️ Ryzyko strategiczne (powiedzieć Ruslanowi wprost)

OLX Group oznacza **Motors API** (auta) jako *„Stable — no new developments or maintenance"*. Legacy Otomoto Open API działa i to na nim jedzie Desal, ale **to nie jest stack, w który OLX inwestuje**.

## Plan

**Etap 0 — spike (4–6 h)** ⚠️ **wycena Etapu 1 jest orientacyjna do czasu spike'u**
1. Konto Business Ruslana + wniosek o klucze API (Janek).
2. Po otrzymaniu kluczy: pobrać słownik kategorii osobowych + listę `make`/`model` z API.
3. **Porównać ich słownik marek z naszymi 61 markami** → ile trafia, ile nie istnieje. **To jest liczba, która przesądza o wycenie.**
4. Ręcznie wystawić 1 auto przez curl → potwierdzić flow end-to-end.

**Etap 1 — wdrożenie (45–65 h)** — dopiero po spike'u.

## Testy

**Automatyczne**
- Mapowanie: 61 naszych marek → slug Otomoto lub jawny brak. Zero cichych fallbacków.
- Payload: walidacja wymaganych pól przed wysyłką (title ≤ 50 znaków — **twardy limit**).
- **Obsługa kodów HTTP** — każdy błąd API logowany (to jest lekcja z Desala, gdzie ciche awarie żyją od lat).

**Półautomatyczne**
- Wystawienie 3 aut → sprawdzenie na żywym Otomoto (wygląd, zdjęcia, parametry).
- Zmiana ceny u nas → czy update dociera.
- Rezerwacja auta u nas → czy znika z Otomoto.

**MCP (Chrome)**
- Panel: kolumna statusu Otomoto, akcja „wystaw", log.
- Podgląd wystawionego ogłoszenia na otomoto.pl.

## Definicja zrobionego

- Auta wystawiają się automatycznie, z pełną specyfikacją i galerią.
- Zmiana ceny/statusu u nas propaguje się na Otomoto.
- Sprzedane/zarezerwowane znika z portalu.
- W panelu widać, co jest wystawione i w jakim stanie.
- Każda operacja API logowana, kody HTTP obsłużone.
