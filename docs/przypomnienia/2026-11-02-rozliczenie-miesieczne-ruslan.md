# Rozliczenie miesięczne z Ruslanem — procedura (cyklicznie, 1. dzień roboczy miesiąca)

> Event: „Auranet Claude”, pierwszy dzień roboczy każdego miesiąca, 09:00 (od 02.11.2026).
> Kontekst: `docs/rozliczenia/ruslan.md` (rejestr + query), `docs/roadmapa/T-261-flaga-a-rozliczone-auranet.md`.
> Memory: `reference_rejestr_rozliczen_ruslan`, `feedback_ruslan_rozliczenie_zawsze_tabelka`.

## Kroki (półautomat, jak partia 7 w październiku)

1. **Kandydaci z bazy** — query z rejestru („Jak wyznaczyć kolejną partię”), data odcięcia = najpóźniejszy
   `_order_deposit_paid_at` z ostatniej rozliczonej partii. Tylko odczyt.
2. **Odsiać ręcznie:** konta testowe (user 10, 12, 13, 157; zamówienie 410894), status `anulowane`,
   zamówienia już z flagą [A]. Osobno sprawdzić zamówienia stockowe (query ich nie widzi).
3. **Tabelka dla Ruslana** (Janek wysyła WhatsAppem): Lp. | Data | Auto | VIN | Klient | Cena |
   Źródło (depozyt / plac / eksport) | ☐. VIN w całości, krótkie nazwy aut, bez linków.
   Niepewne pozycje (dwa egzemplarze modelu) — wszystkie kandydaty z VIN.
4. **Czekamy na ☑ Ruslana** i jego listę za miesiąc — rozjazdy wyjaśniamy przed fakturą.
5. **Wpis partii w rejestrze** (`docs/rozliczenia/ruslan.md`, na górze).
6. **Faktura** — wystawia Janek (Fakturownia: Claude zapisuje tylko proformę, VAT w UI Janka).
7. **Flaga [A]** po wystawieniu FV: `wp eval-file scripts/rozliczenia-oznacz.php ids=… data=… fv=…`
   najpierw dry-run, potem `apply=1`. Kontrola: lista zamówień w panelu — pin A zielony na tych ID.
8. Commit rejestru + `✅` w tytule eventu tego miesiąca.

## Jak wygląda „zrobione”

Rejestr ma nową partię z numerem FV, wszystkie jej zamówienia mają zielone A, Ruslan dostał tabelkę i ją odhaczył.

## Do czasu wdrożenia T-261

Kroki 1–6 bez zmian, krok 7 pomijamy (flaga jeszcze nie istnieje).
