#!/usr/bin/env python3
"""
T-209 etap 2 — eksport zaakceptowanych treści maili z generatora makiet do JSON,
gotowego do wgrania w opcję `asiaauto_order_email_templates`.

Makiety mają wartości przykładowe (Marcin, #1042, BYD Sealion 7…). Tutaj zamieniamy
je na placeholdery obsługiwane przez AsiaAuto_Order_Content::render().

Użycie: python3 scripts/t209_export_templates.py [--out plik.json]
"""
import importlib.util
import json
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
OUT = ROOT / 'tmp' / 't209-templates.json'

# Przykład z makiety → placeholder. Kolejność ma znaczenie (najdłuższe najpierw).
REPLACEMENTS = [
    ('BYD Sealion 7 Excellence AWD 82,5 kWh 2024', '{listing_title}'),
    ('BYD Sealion 7 Excellence AWD 82,5 kWh', '{listing_title}'),
    ('BYD Sealion 7', '{listing_title}'),
    ('marcin.kowalczyk@example.com', '{customer_email}'),
    ('marcin.k@example.com', '{customer_email}'),
    ('Marcin Kowalczyk', '{customer_full_name}'),
    ('Marcin', '{customer_name}'),
    ('#1042', '#{order_id}'),
    ('1042/2026', '{contract_number}'),
    ('1042', '{order_id}'),
    ('+48 721 730 507', '{company_phone}'),
]

# Linki. Makiety mają przykładowy adres zamówienia zaszyty na sztywno — bez tej podmiany
# przycisk „Opłać depozyt" w każdym mailu prowadziłby donikąd (wykryte przy odbiorze 10.08).
LINKS = [
    ('https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f', '{magic_link}'),
    ('primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f', '{magic_link}'),
]

# Kwoty. Placeholder podstawia samą liczbę, więc „zł" musi zostać w treści —
# bez tego przy depozycie 1 zł mail pokazywał gołe „(1)" (odbiór 10.08).
NBSP = '\u00a0'

MONEY = [
    # Makiety używają twardej spacji w kwotach (214&nbsp;000) — bez tego wariantu
    # podmiana nie łapała nic i mail pokazywał przykładową cenę z makiety.
    ('214&nbsp;000', '{price_final}'),
    ('173&nbsp;984', '{price_final_net}'),
    ('189&nbsp;400', '{price_final}'),
    ('6&nbsp;150', '{deposit_amount}'),
    ('214' + NBSP + '000', '{price_final}'),
    ('173' + NBSP + '984', '{price_final_net}'),
    ('189' + NBSP + '400', '{price_final}'),
    ('6' + NBSP + '150', '{deposit_amount}'),
    ('189 400,00 zł', '{price_final} zł'),
    ('189 400 zł', '{price_final} zł'),
    ('189 400', '{price_final}'),
    ('6 150,00 zł', '{deposit_amount} zł'),
    ('6 150 zł', '{deposit_amount} zł'),
    ('6 150', '{deposit_amount}'),
    # Blok „Przewidywana cena całkowita" w mailu o dostępności — brutto i netto.
    ('214 000', '{price_final}'),
    ('173 984', '{price_final_net}'),
]

# Dane z aneksu VIN — makieta miała przykładowy egzemplarz i numer umowy.
ANNEX = [
    ('LC0C74DE9R0123456', '{vin}'),
    ('AA/2026/07/0042', '{contract_number}'),
    ('14.07.2026', '{contract_date}'),
]

# Poprawki treści zgłoszone przy odbiorze.
COPY = [
    # „przed wyprzedaniem" brzmi jak żargon magazynowy — mówimy wprost, o co chodzi.
    ('zabezpieczyć ofertę przed wyprzedaniem',
     'zabezpieczyć ofertę, zanim ktoś nas ubiegnie'),
    # Samo „dostępny" klient czyta jako „stoi na placu w Polsce". To dwie różne rzeczy:
    # auto jest dostępne U ŹRÓDŁA i dopiero je sprowadzamy.
    ('— pojazd DOSTĘPNY', '— DOSTĘPNY DO IMPORTU'),
    ('pojazd dostępny (zamówienie', 'pojazd dostępny do importu (zamówienie'),
]


def load_mails():
    spec = importlib.util.spec_from_file_location('t209', ROOT / 'scripts' / 't209_build_tresci.py')
    mod = importlib.util.module_from_spec(spec)
    sys.argv = ['t209']
    try:
        spec.loader.exec_module(mod)
    except SystemExit:
        pass
    return mod.MAILS


# Czytelność na telefonie (zgłoszone przy odbiorze 10.08 — „na telefonie za mały").
# Sztywne 560 px zmuszało telefon do skalowania całego maila w dół razem z tekstem.
# Kontener robi się elastyczny (100% do 560 px), a bazowa typografia rośnie o stopień.
MOBILE = [
    # 560 px było wąskie i na desktopie, i na telefonie (odbiór 10.08) — 640 px to
    # standardowa szerokość maila transakcyjnego, wciąż bezpieczna dla Outlooka.
    # Klasa `aa-shell` daje uchwyt regule responsywnej doklejanej przez wrapper.
    ('<table width="560" cellpadding="0" cellspacing="0" border="0" style="width:560px;max-width:100%;',
     '<table class="aa-shell" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;'),
    ('style="width:560px;max-width:100%;', 'style="width:600px;max-width:100%;'),
    # Mniejsze marginesy boczne = więcej treści na ekranie telefonu.
    ('padding:26px 26px', 'padding:22px 20px'),
    ('padding:0 26px', 'padding:0 20px'),
    ('padding:10px 26px', 'padding:10px 20px'),
    ('padding:8px 26px', 'padding:8px 20px'),
    ('padding:4px 26px', 'padding:4px 20px'),
    ('padding:6px 26px', 'padding:6px 20px'),
    ('padding:18px 26px', 'padding:18px 20px'),
    ('padding:24px 26px', 'padding:24px 20px'),
    ('padding:14px 26px', 'padding:14px 20px'),
    ('font-size:14px', 'font-size:16px'),
    ('font-size:13px', 'font-size:15px'),
    ('font-size:12px', 'font-size:14px'),
    ('font-size:11px', 'font-size:13px'),
]


# Przyciski w makietach były atrapami — `href="#"`, a prawdziwy adres stał tylko
# jako tekst pod spodem (wykryte przy odbiorze 10.08: klient klikał i nic się nie działo).
# Każdy przycisk dostaje cel dobrany po etykiecie.
BUTTONS = {
    'Zobacz dostępne auta': 'https://primaauto.com.pl/samochody/',
    'Wystaw opinię w Google': 'https://primaauto.com.pl/kontakt/',
}
BUTTON_DEFAULT = '{magic_link}'


def fix_buttons(text: str) -> str:
    """Podmienia href="#" na właściwy adres, patrząc na etykietę przycisku."""
    def repl(m):
        label = m.group(2).strip()
        target = BUTTONS.get(label, BUTTON_DEFAULT)
        return m.group(0).replace('href="#"', f'href="{target}"', 1)

    return re.sub(r'href="#"[^>]*>([^<]*?)?([^<]{3,60})<', repl, text)


# Makiety wypisywały adres zamówienia jeszcze raz, gołym tekstem pod przyciskiem —
# bo sam przycisk był atrapą. Po naprawie href to już tylko szary, łamiący się w dwie
# linie duplikat, w dodatku z tokenem dostępu do zamówienia. Wycinamy cały wiersz
# tabeli, który nie zawiera nic poza {magic_link}. (Odbiór 10.08.)
BARE_LINK_ROW = re.compile(
    r'\s*<tr>\s*<td[^>]*>\s*\{magic_link\}\s*</td>\s*</tr>',
    re.IGNORECASE,
)


def drop_bare_link(text: str) -> str:
    return BARE_LINK_ROW.sub('', text)


def to_template(text: str) -> str:
    for needle, placeholder in COPY + LINKS + ANNEX + REPLACEMENTS + MONEY + MOBILE:
        text = text.replace(needle, placeholder)
    return drop_bare_link(fix_buttons(text)).strip()


def main() -> int:
    out = {}
    skipped = []

    for entry in load_mails():
        key = entry['key']
        state = entry.get('state', '')

        # Mail powitalny wycofany ze ścieżki — nie nadpisujemy go treścią.
        if state == 'killed':
            skipped.append(f'{key} ({state})')
            continue

        body = entry.get('after') or ''
        subject = entry.get('subj_after') or ''

        if not body.strip():
            skipped.append(f'{key} (pusta treść)')
            continue

        # Makietowy „fallback statusu" obsługuje statusy bez własnej treści.
        # Zapisujemy go pod realnymi kluczami, żeby hardkodowany fallback
        # w class-asiaauto-order.php:1893 w ogóle się nie odpalał.
        if key.startswith('('):
            for fallback_key, label in (('status_nowe', 'Nowe'), ('status_weryfikacja', 'Weryfikacja')):
                out[fallback_key] = {
                    'subject': to_template(subject).replace('Weryfikacja', label),
                    'body': to_template(body).replace('Weryfikacja', label),
                }
            continue

        out[key] = {
            'subject': to_template(subject),
            'body': to_template(body),
        }

    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(out, ensure_ascii=False, indent=1), encoding='utf-8')

    print(f'zapisano {len(out)} szablonów → {OUT}')
    for key, tpl in out.items():
        leftovers = re.findall(r'(Marcin|1042|BYD Sealion|example\.com|\?k=a7f3e91b|214 000|173 984|0123456|AA/2026)', tpl['body'])
        flag = f'  ⚠ pozostały przykłady: {sorted(set(leftovers))}' if leftovers else ''
        print(f'  {key:26} {len(tpl["body"]):6} B{flag}')

    if skipped:
        print('\npominięte:', ', '.join(skipped))

    return 0


if __name__ == '__main__':
    raise SystemExit(main())
