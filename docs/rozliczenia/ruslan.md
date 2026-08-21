# Rejestr rozliczeń z Ruslanem

> Ostatnia aktualizacja: 2026-08-21
> Zakres: zamówienia (`asiaauto_order`) rozliczone między Auranet a Prima-Auto.
> **Ten plik jest source of truth dla „co już rozliczone".** Baza nie ma znacznika
> rozliczenia — jedyne co w niej jest to `_order_deposit_paid` / `_order_deposit_paid_at`.

## Jak wyznaczyć kolejną partię

Kryterium: **`_order_deposit_paid = 1` i `_order_deposit_paid_at` późniejszy niż ostatnia
rozliczona pozycja**. Query (na produkcji, `~/domains/primaauto.com.pl/public_html`):

```bash
wp db query "
SELECT p.ID, LEFT(p.post_date,10) AS zam,
  LEFT(MAX(CASE WHEN m.meta_key='_order_deposit_paid_at' THEN m.meta_value END),10) AS dep,
  MAX(CASE WHEN m.meta_key='_order_price_final' THEN m.meta_value END) AS cena,
  MAX(CASE WHEN m.meta_key='_order_status' THEN m.meta_value END) AS st,
  p.post_title
FROM wp7j_posts p JOIN wp7j_postmeta m ON m.post_id=p.ID
WHERE p.post_type='asiaauto_order' AND p.post_status NOT IN ('trash','auto-draft')
GROUP BY p.ID
HAVING MAX(CASE WHEN m.meta_key='_order_deposit_paid' THEN m.meta_value END)='1'
   AND MAX(CASE WHEN m.meta_key='_order_deposit_paid_at' THEN m.meta_value END) > 'DATA_ODCIECIA'
ORDER BY 3;"
```

Z wyniku odsiej ręcznie (query tego nie odróżnia):

- **Konto user ID 13 `andriy1988hudzo@gmail.com` („Andrii Hudzo") = Andrzej, nasz człowiek.**
  Figuruje w bazie jako zwykły klient (`_order_type=customer`). Jego zamówienia
  do rozliczenia **nie wchodzą** — chyba że ustalimy inaczej per pozycja.
- **Wpisy testowe** — np. 410894 (Seal U, Jan Schenk, depozyt 1 zł, 10.08.2026).
- **Anulowane** — status `anulowane` mimo zaksięgowanego depozytu.

`_order_contract_commission_net` = 5000 na każdym zamówieniu to **wartość domyślna
z configu**, nie potwierdzenie rozliczonej prowizji. Nie traktuj jej jako kwoty rozliczenia.

---

## 2026-08-21 — partia 5 (do rozliczenia)

| ID | Zamówienie | Depozyt | Cena | Status | Auto — klient |
|---|---|---|---|---|---|
| 387071 | 2026-07-02 | 2026-07-09 | 506 000 | zakupione | Zeekr 8X 2026 Yao Ying 1400PS — Dariusz Bąkowski |
| 397416 | 2026-07-30 | 2026-07-31 | 139 000 | umowa_gotowa | Mazda EZ-6 2025 EREV 200 Premium — Szymon Dyk |
| 407328 | 2026-08-06 | 2026-08-06 | 251 000 | zakupione | Denza N8L DM 2025 Flagship — Mateusz Pacuła |
| 411381 | 2026-08-12 | 2026-08-13 | 157 000 | zarezerwowane | Mazda EZ-60 2026 EREV 200 Max R21 — Krzysztof Różnicki |
| 422076 | 2026-08-18 | 2026-08-21 | 172 000 | zarezerwowane | Li Auto L6 2026 Max Smart Refresh — Igor Fleytukh |

387071 (Bąkowski) wypadł z poprzedniej partii mimo depozytu z 09.07 — dołożony tutaj.

### Wyłączone z tej partii

| ID | Powód |
|---|---|
| 407331 | NIO ET5 Touring, 193 000, **anulowane** — konto Andrzeja (ID 13). Tego samego dnia co 407351, wygląda na zamianę auta |
| 407351 | BYD Seal 6 EV, 140 000, podpisane — konto Andrzeja (ID 13). **Decyzja Janka pending:** rozliczać normalnie czy osobnym torem |
| 410894 | Seal U, depozyt 1 zł, Jan Schenk — wpis testowy |

---

## 2026-07-22 — partia 13 (wpis wsteczny)

Lista podana przez Janka 2026-08-21 jako już rozliczona. Data odcięcia = najpóźniejszy
depozyt w partii: **387788, 2026-07-22 19:24**.

| ID | Zamówienie | Depozyt | Cena | Status | Auto — klient |
|---|---|---|---|---|---|
| 270958 | 2026-04-25 | — | 306 000 | zakonczone | Denza N9 DM-i 2025 Flagship — stock (kamery, plac) |
| 271002 | 2026-04-25 | — | 312 000 | zakonczone | Denza N9 DM-i 2026 Flagship — stock (ded, plac) |
| 273994 | 2026-04-27 | — | 145 000 | zakonczone | Mazda EZ-6 2024 EREV 200 Premium — stock (plac) |
| 278689 | 2026-04-28 | — | 230 000 | na_placu | BYD Sealion 8 DM-I (Tang L) 2025 — stock (plac) |
| 355784 | 2026-06-02 | 2026-06-10 | 216 000 | zakupione | BYD Leopard 7 (Tai 7) 2025 Ultra + hak — Juliusz Sroczyński |
| 355823 | 2026-06-03 | 2026-06-10 | 140 000 | w_drodze | iCAR Super V23 2026 V23S 501 4WD — Andrzej Roczniak |
| 355931 | 2026-06-05 | 2026-06-11 | 293 000 | zakupione | Lynk & Co 900 2026 2.0T Ultra — Jarosław Kędziora |
| 360445 | 2026-06-13 | 2026-06-13 | 214 000 | zakonczone | BYD Leopard 5 (Denza B5) 2025 Flagship — Krzysztof Pietrzak |
| 360448 | 2026-06-13 | 2026-06-16 | 186 000 | zakupione | BYD Han L DM 2025 DM-p Lidar Flagship — Joseph Bass |
| 361288 | 2026-06-18 | 2026-07-08 | 214 000 | zakupione | BYD Leopard 5 (Denza B5) 2025 Flagship R20 — Agnieszka Koman |
| 387042 | 2026-07-02 | 2026-07-04 | 216 000 | w_drodze | BYD Leopard 7 (Tai 7) 2026 Ultra — Paweł Mroczkowski |
| 387760 | 2026-07-09 | 2026-07-22 | 132 000 | w_drodze | Mazda EZ-6 2024 EREV 200 Premium — Paweł Wajrak |
| 387788 | 2026-07-11 | 2026-07-22 | 147 000 | zakupione | BYD Leopard 3 (Tai 3) 2025 Ultra — Piotr Dyba |

Cztery kwietniowe pozycje to zamówienia stockowe — mają `_order_deposit_paid = 0`,
rozliczone poza pipeline'em depozytów. Query ich nie wyłapie.
