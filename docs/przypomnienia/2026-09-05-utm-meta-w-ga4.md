# Czy UTM-y z Meta widać w GA4

**Stan wyjściowy (04.09 wieczorem):** sześć żywych reklam Meta dostało `url_tags`
`utm_source=facebook&utm_medium=paid_social&utm_campaign={{campaign.name}}&utm_content={{ad.name}}`.
Id reklam bez zmian, dostawa nie stanęła. Przed zmianą ruch z Mety wpadał do GA4 jako
`l.facebook.com / referral` (3 sesje) i `lm.facebook.com / referral` (1) — bez kampanii.
Szczegóły i mechanika: `docs/architektura/pomiar-i-monitoring.md`, sekcja o UTM-ach.

## Co sprawdzić

```python
import sys; sys.path.insert(0, "scripts")
from ga4_query import token, run, rows
tok = token()
d = run(tok, {"dateRanges": [{"startDate": "today", "endDate": "today"}],
              "dimensions": [{"name": "sessionSourceMedium"}, {"name": "sessionCampaignName"},
                             {"name": "sessionManualAdContent"}],
              "metrics": [{"name": "sessions"}],
              "dimensionFilter": {"filter": {"fieldName": "sessionSourceMedium",
                  "stringFilter": {"matchType": "CONTAINS", "value": "facebook"}}}})
print(rows(d)[2])
```

**Sukces wygląda tak:** `facebook / paid_social` z nazwą kampanii (`[VID] Auta z Chin — nowi
odbiorcy`, `[FOTO] Zdjęcia z sesji — karuzele`, `[POST] Promowanie postów — ruch`) i nazwą
reklamy w `adContent`. Sesje z jawnymi UTM-ami przypisują się od razu — to nie jest ta
atrybucja, która czeka dobę.

**Czego NIE traktować jak porażki:** `l.facebook.com / referral` obok — to ruch z organicznych
postów Andrzeja, on UTM-ów nie ma i mieć nie będzie. Liczy się, czy **obok** pojawił się
`facebook / paid_social`.

**Jeśli nie widać:** sprawdź `python3 scripts/social/utm_kreacje.py --lista` (czy tagi dalej są
na kreacjach — Meta nie zdejmuje ich sama, ale nowa kreacja od Andrzeja by ich nie miała),
potem czy reklamy w ogóle miały kliknięcia w linki (`inline_link_clicks` w `meta.json` — przy
47 wyświetleniach na starcie mogło ich po prostu nie być).

**Pełny obraz — 06.09**, z doby zamkniętej: dopiero wtedy porównanie kliknięć Meta z sesjami
GA4 ma sens (dziś atrybucja pozostałych kanałów jest niepoliczona, 64% sesji bez źródła).

## Zasada, która obowiązuje w tym sprawdzeniu

Dashboard (`https://auratest.pl/pa-live-8aaf08d6ece0db176603/`) jest pierwszym spojrzeniem,
ale liczba podawana Jankowi idzie ze świeżego odczytu z API — patrz memory
`feedback_dashboard_to_podglad_nie_dowod`.
