# 2026-07-17 — /informacje/ 301 → home + menu „#" (anty-kanibalizacja „import samochodów z Chin")

## Problem

Homepage słabo rankuje na komercyjne „import samochodów z chin" (GSC 90 dni: poz **17**, 89 impr).
Hipoteza Janka: winne słowo „aut" w title. **Obalona** — fraza z „aut" („import aut z chin") stoi
wyżej (poz 11) niż z „samochodów", a homepage ma dokładny H1 „Import samochodów z Chin do Polski".

Prawdziwa przyczyna: **kanibalizacja przez hub `/informacje/`**. Ta sama fraza pojawia się na 3 stronach:
- `/informacje/` — poz **37**, **133 impr** (najwięcej!)
- `/` — poz 17, 89 impr
- `/informacje/proces-zamawiania/` — poz 76, 15 impr

## Diagnoza (skąd kanibalizacja na hubie)

Nie z title/meta (te były OK) — z **boksów-kafli** podstron. `/informacje/` renderuje 8 kafli, każdy
to link obejmujący nagłówek + excerpt. Excerpty są auto-generowane z treści podstron i naszpikowane
frazami: „Gwarancja i serwis **samochodu z Chin**", „jak sprowadzić **samochód z Chin**",
„Homologacja i rejestracja **samochodu z Chin**", a kafel o-nas ma nagłówek
„O Prima-Auto — **import aut z Chin** bez stresu". Renderowany tekst huba: 18× „samochod", 80× „import",
5× „auto z chin" → Google widzi hub jako mocno komercyjną stronę i miesza ją z homepage.

## Decyzja

Hub jako osobna strona znika; zostaje wyłącznie rozwijane menu do podstron.

1. **Menu „Informacje" → `#`** (pozycja menu 153881: `post_type/page` → `custom`, url `#`).
   8 podstron zostają jako `sub-menu` (dropdown na hover) — dostęp zachowany.
2. **301 `/informacje/` → `/`** — `.htaccess`, exact match:
   ```apache
   RewriteRule ^informacje/?$ https://primaauto.com.pl/ [R=301,L]
   ```
   Łapie tylko `/informacje/` (i bez slasha); `/informacje/<podstrona>/` przepuszczone (200).

301 wybrany zamiast `noindex`, bo dodatkowo **przekazuje link equity + sygnał tematyczny do homepage**
(aktywnie ją podbija), nie tylko usuwa konkurenta z SERP.

## Konsekwencje / do monitorowania

- Homepage powinna skonsolidować pozycję na „import samochodów/aut z chin" (pomiar GSC ~2 tyg, ~31.07).
- Info-intent frazy („jak sprowadzić", „koszt sprowadzenia") powinny przejąć konkretne **podstrony**
  (`/informacje/proces-zamawiania/`, `/pod-dom-do-rejestracji/`), nie hub.
- Zgłosić `/informacje/` do GSC, by Google szybciej zobaczył 301.
- Wcześniejsza zmiana treści/meta parenta `/informacje/` (link do home + info-meta) jest po 301 bez
  znaczenia (strona przekierowana). Backup: `tmp/informacje-backup-2026-07-17-1742.*`.

## Backupy

- `~/domains/primaauto.com.pl/public_html/.htaccess.bak-2026-07-17-*`
- Treść/meta huba: `tmp/informacje-backup-2026-07-17-1742.*`
