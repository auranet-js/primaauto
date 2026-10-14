# 2026-10-14 — Płatności online za depozyt: temat zamknięty, zawieszony na obecnym etapie

**Decyzja (Janek, 14.10):** odpuszczamy płatności elektroniczne całkowicie. Tpay po odpowiedzi AML zażądał kolejnych dokumentów (dowód osobisty Ruslana + oświadczenie bez wzoru), których nie zamierzamy dosyłać. Nie kontynuujemy ani z Tpay, ani z innym operatorem (Przelewy24, Autopay). Depozyt 6 150 zł klienci płacą wyłącznie przelewem, księgowanym ręcznie.

Zamyka: **T-121 „płatność PayU za depozyt”** (zastąpione 07.10) i **T-256 „przepięcie depozytu na Tpay”**.

## Przebieg

| Data | Etap | Godz. (postęp) |
|---|---|---|
| 27.07 | T-221: regulamin usługi + strona `/depozyt/` pod operatora (wpis wspólny z umową leasingową, nie liczony niżej) | (6 h łącznie) |
| 06.08–20.08 | T-121 PayU: rozpoznanie, pakiet wykonawczy, integracja BLIK + karta/przelew, sandbox OK 10.08, weryfikacja AML PayU, linki prawne w stopce/menu, bramka LIVE 20.08 | 27 h |
| 21.08–07.09 | POS PayU: 19/19 metod `DISABLED`, klienci odbijani (zam. 423100 — 7 prób) | — |
| 14.09 | PayU odmawia → bramka OFF (v0.40.1), blok „Płatność online uruchamiamy” + dane do przelewu, rozpoznanie Autopay | 1 h |
| 16.09 | Porównanie 10 operatorów → Tpay Business (zapas P24), konto, integracja v0.41.0, testy sandbox, flaga OFF | 6 h |
| 07.10 | Odpowiedź AML do Tpay (limit 1000 znaków), test konta prod (tryb testowy), stopka bez „sprzedaż” | 1 h |
| 09.10 | Tpay: żądanie dowodu osobistego + oświadczenia; Janek wysłał pytanie o paszport i wzór | (niezalogowane) |

**Razem zaraportowane: 35 h** (T-121 27 h + T-256 8 h; T-256 wyceniony na 12 h, nie dokończony). Kalendarzowo: 06.08 → 14.10, ok. 10 tygodni.

## Stan, w jakim zostawiamy (zweryfikowane 14.10)

- `asiaauto_payu_enabled = 0` — płatność online wyłączona dla obu operatorów; kod PayU i Tpay (v0.41.0) zostaje w pluginie, nieużywany.
- Kreator zamówienia (`class-asiaauto-payu-wizard.php:286`) pokazuje: „Płatność online uruchamiamy. Do tego czasu opłać depozyt przelewem…” + dane do przelewu.
- Stopka: „import samochodów” (bez „sprzedaż”, zmiana z 07.10 pod AML Tpay; backup `footer.php.bak-2026-10-07-sprzedaz`).
- Konta: PayU (odmowa), Tpay 227596 (niezweryfikowane, tryb testowy) — nic nie zamykamy po stronie operatorów.

## Konsekwencje / otwarte (nie ruszone tą decyzją)

- Komunikat „uruchamiamy” obiecuje coś, czego nie będzie — do decyzji, czy zostaje, czy zamieniamy na sam przelew.
- „Sprzedaż” w stopce miała wrócić po aktywacji Tpay — aktywacji nie będzie, do decyzji.
- Strona postępu dla Ruslana: T-256 ma status „w toku, czeka na Tpay” — do przestawienia na zawieszone.

Historia: `docs/decyzje/2026-09-14-payu-odmowa-wylaczenie-bramki.md`, `docs/biznes/2026-09-16-bramka-platnosci-porownanie.md`, `docs/biznes/2026-10-07-tpay-odpowiedz-aml.md`, `docs/roadmapa/T-121-platnosc-online-depozyt.md`.
