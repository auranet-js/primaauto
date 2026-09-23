# che168 stoi, dongchedi wznowiony (23.09.2026)

## che168 — zastój po stronie dostawcy (drugi po 12–19.08)

- Strumień `/changes` normalny do 22.09: 24–28 tys. zdarzeń/dobę (17–22.09), import u nas
  100–137 ofert/dobę (22.09: 137).
- **Ostatnie regularne zdarzenie: 22.09 20:42 (+03:00).** Potem ~10 zdarzeń w 14 h
  (ostatnie change_id 11640182, 23.09 10:13 +03:00). Kursor na głowie, `result: []`,
  `next_change_id: null`. Wszystkie biegi od 23.09 rano: `batches=0`.
- Nie święto: Święto Środka Jesieni 25.09, Złoty Tydzień 1–7.10; dongchedi tym samym
  kluczem płynie normalnie.
- Szkic maila do `access@auto-api.com` wysłany Jankowi (`send-to-jan`, 23.09): status/ETA,
  czy zaległe zdarzenia zostaną wyemitowane, rozliczenie przestoju przy kolejnej fakturze
  (zapowiedź z 19.08). Przestój liczony od 22.09 20:42 (+03:00).

## dongchedi — `verify` → `full` (przełączył Janek w panelu, 23.09 ~10:55)

- Od 24.08 tryb `verify` odrzucał nowy towar (kto przestawił z `full` — nieustalone,
  patrz sesja 28.08). Dostawca dowozi 16–23 tys. zdarzeń/dobę.
- **Bez cofania kursora.** Okno 8 h = 2 211 ofert spoza bazy; dongchedi nie ma prefiltra
  przed `getOffer` (tylko che168, T-186), a dostawca zwraca 429 już po ~220 wywołaniach.
  Nieudany `getOffer` = import z danych częściowych, bez `extra_prep`. Nadrobienie
  zaległości wymagałoby nowego skryptu (filtr na danych z `/changes`, `getOffer` tylko
  dla przechodzących) — nie zlecone.
- Wynik 10:56–16:30: 30 biegów, **46 nowych ofert publish, wszystkie chude**
  (mediana 43 pola `extra_prep`, min 22). Przyczyna: samo źródło — `getOffer` dongchedi
  zwraca ~40 pól (regresja od 20.07), nie 429. Uzupełnia nocna sekwencja
  19:15 bliźniak → 19:25 bank (od 16.08: mediana po dolewce ~360, ~16% zostaje chudych).
- 429 w dwóch biegach (11:56, 14:56 PL, 39 odpowiedzi): 12 ofert poszło ścieżką danych
  częściowych, żadna nie przeszła filtrów → brak skutku w bazie. Drugiego konsumenta
  klucza na serwerze nie ma (jedna instalacja pluginu); przyczyna nieustalona, do obserwacji.

## Do sprawdzenia

- Po 19:30: ile z 46 ofert uzupełniło się dolewką, które zostały chude.
- Otwarte od 16.08: chude oferty dongchedi na `publish` czy `draft` do czasu dolewki.
- che168: restart kanału i odpowiedź dostawcy.
