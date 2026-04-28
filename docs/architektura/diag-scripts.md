# Skrypty diagnostyczne — diag/

> Aktualizacja: 2026-04-15.

Wszystkie uruchamiane przez `wp eval-file diag/<skrypt>.php`.
Domyślnie dry-run. Zmiany wymagają `APPLY=1` jako env variable.

## Read-only (diagnostyka)

| Skrypt | Rozmiar | Opis |
|--------|---------|------|
| `check-translations.php` | 3.7 KB | Brakujące labele, niekategoryzowane klucze, nieprzetłumaczone wartości |
| `check-chinese-models.php` | 2.4 KB | Chińskie nazwy w taksonomii serie/make + duplikaty termów |
| `check-data-quality.php` | 4.5 KB | Audyt: slugi, moc EV, extra_prep, opisy, spójność meta↔taksonomia |
| `test-image-sizes.php` | 6.2 KB | Test wariantów rozmiaru obrazów z CDN Dongchedi/ByteDance |

## Maintenance (APPLY=1)

| Skrypt | Rozmiar | Opis |
|--------|---------|------|
| `fix-taxonomy-duplicates.php` | 4.8 KB | Scalanie duplikatów taksonomii (PL slug → EN slug kanoniczny) |
| `fix-chinese-models.php` | 5.0 KB | Rename chińskich termów → EN + fix tytułów i meta |
| `fix-chinese-v23.php` | 7.2 KB | Naprawa chińskich znaków w serie + tytułach (v0.23, combined fixer) |
| `fix-broken-json-v25.php` | 7.2 KB | Naprawa zepsutego JSON w extra_prep (wp_unslash bug, 93 listingów) |
| `retranslate-descriptions.php` | 2.4 KB | Ponowne tłumaczenie opisów ze statusem 'failed' (Gemini) |
| `backfill-extra-prep.php` | 3.2 KB | Uzupełnianie brakujących extra_prep z API |
| `cleanup-duplicates.php` | 3.0 KB | Usuwanie duplikatów po inner_id (zostawia najlepszy post) |

## Workflow po problemach

```bash
# 1. Diagnoza
wp eval-file diag/check-chinese-models.php
wp eval-file diag/check-translations.php
wp eval-file diag/check-data-quality.php

# 2. Fix (jeśli potrzebny)
APPLY=1 wp eval-file diag/fix-chinese-models.php
APPLY=1 wp eval-file diag/fix-chinese-v23.php

# 3. Weryfikacja
wp eval-file diag/check-data-quality.php
```

## Dostęp z UI / WP-CLI (od v0.32.0)

Skrypty `diag/*.php` są jednocześnie wywoływalne przez panel **Listings → Diagnostyka** (admin UI) oraz przez `wp asiaauto diag`. Każdy check w rejestrze (`includes/diag-checks/`) deleguje do funkcji eksportowanej z odpowiedniego skryptu `diag/`. Workflow `wp eval-file diag/X.php` nadal działa bez zmian.

## Workflow po imporcie nowych marek/modeli

```bash
# 1. Sprawdź chińskie nazwy
wp eval-file diag/check-chinese-models.php

# 2. Jeśli znalezione — dodaj do translations-models.php, potem:
APPLY=1 wp eval-file diag/fix-chinese-v23.php

# 3. Sprawdź brakujące tłumaczenia spec
wp eval-file diag/check-translations.php
```
