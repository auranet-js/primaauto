# PayU — przyczyna blokady wyjaśniona + linki prawne na stronie (2026-08-13)

> Ciąg dalszy `2026-08-10-odbior-wyniki-T121-T209-T244.md`.
> **Produkcyjne płatności nie działały, bo konto jest w trakcie weryfikacji AML w PayU** —
> nie z powodu naszego kodu.

---

## 1. Skąd wiemy

Mail od PayU (PL Operations, Karolina Rogalska, Junior AML and Risk Analyst) do Ruslana:
żeby włączyć płatności, trzeba spełnić dwa warunki.

**a) Dokumenty (AML, ustawa z 16.11.2000 o przeciwdziałaniu praniu pieniędzy):**
w Panelu Menedżerskim, zakładka „Dokumenty" — skan obu stron karty pobytu w kolorze
albo dokument nadania numeru PESEL Ruslana Primy. **Wyłącznie po stronie Ruslana.**

**b) Strona internetowa:**
- regulamin: polityka zwrotów i reklamacji, adres do kontaktu, czas rozpatrzenia reklamacji,
  **maksymalny czas realizacji zamówienia w dniach roboczych**, pełne dane firmy (nazwa, NIP,
  adres, dane kontaktowe)
- polityka prywatności z informacją, kto jest administratorem danych osobowych

Równolegle mec. Małgorzata Dziadzio (kancelaria, konsultacja Ruslana) potwierdziła, że
**dokumenty są poprawne**, ale: „nie widzę dostępu do Polityki Prywatności po tym, jak zamknie
się komunikat po wejściu na stronę. Nie jestem pewna, czy pracownikowi PayU udało się też
zapoznać z Regulaminem" — i doradziła wysłać PayU linki bezpośrednio.

To wyjaśnia `PENDING` z pustą `payMethod` z 10.08: POS zwracał 19 metod jako aktywne
(konfiguracja), ale punkt nie był rozliczeniowo włączony.

---

## 2. Co było nie tak na stronie

Problem nie leżał w treści dokumentów, tylko w **dostępie do nich**.

| Dokument | URL | Skąd był dostępny (przed) |
|---|---|---|
| Regulamin świadczenia usług drogą elektroniczną | `/informacje/regulamin/` | menu „Informacje" |
| **Regulamin usługi organizacji importu** | `/regulamin-uslugi/` | **tylko kreator zamówienia + maile** |
| **Polityka prywatności** | `/polityka-prywatnosci/` | **tylko baner cookies + kreator** |

Stopka nie miała żadnego linku prawnego. Analityk PayU znalazł więc jedyny widoczny
„Regulamin" — ten o świadczeniu usług drogą elektroniczną (przeglądanie strony, formularz
kontaktowy) — który nie mówi o zamówieniach, zwrotach ani czasie realizacji. Stąd wniosek,
że wymagań nie ma.

**Właściwy dokument (`/regulamin-uslugi/`) ma niemal komplet:** dane firmy (Prima-Auto Ruslan
Prima, ul. Pleśniarowicza 2A/38, 35-117 Rzeszów, NIP 8133898576, REGON 525442846,
china@primaauto.com.pl, tel. 783 807 381), depozyt i płatności przez PayU, zwrot depozytu
w 3 albo 7 dni roboczych, odstąpienie konsumenta 14 dni, reklamacje rozpatrywane w 14 dni,
odesłanie do polityki prywatności. Polityka prywatności wskazuje administratora danych.

---

## 3. Zrobione

**Stopka** (`themes/primaauto2026/footer.php`, `.bak-2026-08-13-legal`) — dolny pasek,
zamiast „Wszelkie prawa zastrzeżone" (zwrot bez skutku prawnego):

```
© 2026 Prima-Auto    Regulamin serwisu · Regulamin usługi importu · Polityka prywatności    Auranet
```

CSS (`assets/css/footer.css`, `.bak-2026-08-13-legal`): `.pa-footer__legal` — flex z zawijaniem,
separatory `.pa-footer__legal-sep` przez `aria-hidden`. Pasek poniżej 540 px szedł już wcześniej
w kolumnę. **Widoku telefonu nie udało się zweryfikować** — okno zmieniło rozmiar, ale strona
renderowała się dalej po desktopowemu. Do sprawdzenia na realnym telefonie.

**Menu Header → „Informacje":**
- „Regulamin" → **„Regulamin serwisu"** (pozycja #153883)
- dodane **„Regulamin usługi importu"** → `/regulamin-uslugi/` (pozycja #411421, `menu_order` 14,
  tuż pod regulaminem serwisu, przed „O Prima-Auto")

Politykę prywatności zostawiono poza menu — jej konwencjonalnym miejscem jest stopka.

**Uwaga o `/informacje/`:** sam hub leci 301 na home (antykanibalizacja, 17.07 —
[[project_informacje_301_antykanibalizacja_2026_07_17]]), ale **podstrony żyją i zwracają 200**.
Zweryfikowane curl-em: `/informacje/regulamin/`, `/regulamin-uslugi/`, `/polityka-prywatnosci/`
→ wszystkie 200. Link w stopce jest bezpieczny.

---

## 4. Co zostało

**Brakuje jednego wymogu PayU: maksymalnego czasu realizacji zamówienia w dniach roboczych.**
Dziś §1 `/regulamin-uslugi/` odsyła terminy do indywidualnej umowy. Proponowane brzmienie
(czeka na liczbę od Ruslana — kreator pokazuje „szac. 120 dni", ale to szacunek kalendarzowy
transportu, a PayU chce maksimum, którego Ruslan jest gotów bronić):

> Usługa realizowana jest w terminie do **X dni roboczych** od dnia zaksięgowania Depozytu.
> Szczegółowy termin określa umowa zawierana z Klientem.

**Po stronie Ruslana:** skan dokumentu w Panelu Menedżerskim + odpowiedź do Karoliny Rogalskiej
z bezpośrednimi linkami do trzech dokumentów. Info dla niego wysłane Jankowi mailem 13.08.

**Płatności pozostają wyłączone** (`asiaauto_payu_enabled = 0`, tryb `prod`) do zakończenia
weryfikacji. Klient widzi przelew tradycyjny. Włączenie = jedna opcja.
