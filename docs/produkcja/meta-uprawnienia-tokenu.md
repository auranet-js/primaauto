# Meta — uprawnienia tokenu System Usera

> Sprawdzone 2026-08-27: `debug_token` + testy odczytu na żywym koncie.
> **Zakres wyłącznie z planu T-243. Nie dopisywać uprawnień „na zapas".**

**Token:** `~/secrets/meta/tokens/primaauto-portfolio-ruslan.txt`
**SU:** `Auranet API` (`122094804249428678`) · **App:** `Prima-Auto API` (`1533997951805022`)
**Aktualny token wystawiony:** 27.08.2026 10:46, nie wygasa.

## Stan — czego brakuje

**Brakuje jednego uprawnienia: `pages_manage_posts`.**

Potrzebne do fazy T-243e (dystrybucja na Facebooku). Bez niego nie opublikujemy nic na Stronie
— a to jedyne miejsce, gdzie da się pracować na szkicach: endpoint rolek przyjmuje
`video_state` = DRAFT / SCHEDULED / PUBLISHED oraz czas publikacji od 10 minut do 29 dni naprzód.

Mamy: `public_profile`, `pages_show_list`, `pages_read_engagement`, `business_management`,
`pages_manage_ads`, `instagram_basic`, `instagram_content_publish`, `instagram_manage_insights`,
`ads_management`, `ads_read`, `catalog_management`.

## Pułapka: lista scope'ów bywa myląca

`read_insights` **nie figuruje** w `debug_token`, a statystyki Strony **działają** — bo użytkownik
systemowy ma na Stronie rolę `ANALYZE`, a dla page tokenu rola wystarcza zamiast scope'u.
Zanim ogłosisz brak uprawnienia — **sprawdź odczytem na żywym koncie**, nie samą listą.

Metryki Page Insights: `page_impressions` i `page_fans` **zostały wycofane** w v26 — zwracają
„not a valid insights metric", co łatwo wziąć za brak dostępu. Działają m.in.
`page_views_total`, `page_daily_follows`, `page_post_engagements`.

## Weryfikacja po otrzymaniu tokenu

```bash
T=$(cat ~/secrets/meta/tokens/primaauto-portfolio-ruslan.txt)
curl -s "https://graph.facebook.com/v21.0/debug_token?input_token=$T&access_token=$T" \
 | python3 -c "
import sys,json
mamy=set(json.load(sys.stdin)['data']['scopes'])
trzeba={'public_profile','pages_show_list','business_management','pages_read_engagement',
'pages_manage_posts','pages_manage_ads','instagram_basic','instagram_content_publish',
'instagram_manage_insights','ads_management','ads_read','catalog_management'}
brak=trzeba-mamy
print('KOMPLET' if not brak else 'BRAKUJE: '+', '.join(sorted(brak)))"
```

## Czego API Meta NIE potrafi — z dokumentacji, nie z domysłu

- **Crossposting rolek z Facebooka na Instagram nie istnieje w API.** Dokumentacja Meta:
  „You can only crosspost Reels to Facebook Pages". Opcja w Business Suite to funkcja
  interfejsu — my jej nie ustawimy ani nie sprawdzimy z zewnątrz.
- **Instagram nie ma planowania ani szkiców przez API.** Kontener żyje 24 h: albo publikacja,
  albo wygaśnięcie. Harmonogram tylko cronem o właściwej godzinie.
- **Instagram nie pozwala usunąć ani edytować opublikowanego medium przez API.**
  Odmowa systemowa. Cofa wyłącznie człowiek w aplikacji.
- Limit publikacji IG: **100 postów / 24 h**, stan przez `content_publishing_limit`.

**Wniosek:** bezpiecznie testować da się tylko na Facebooku, na szkicach. Każda publikacja
na Instagramie jest natychmiastowa i nieodwracalna — wymaga zgody Janka na treść i godzinę.
