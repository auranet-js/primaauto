#!/usr/bin/env python3
"""
T-209 — generator makiet TREŚCI maili (przed/po).
Dane maili trzymane w MAILS; skrypt składa plik HTML z ekspozycją porównawczą.
Dokładanie kolejnego maila = dopisanie wpisu do MAILS i ponowne uruchomienie.

Użycie: python3 t209_build_tresci.py [--deploy]
"""
import sys, html, pathlib, re

OUT = pathlib.Path(__file__).parent / 't209-tresci-maili.html'
DROP = pathlib.Path.home() / 'domains/auratest.pl/public_html/fe4f58fec53ctmp/primaauto-t209-tresci-maili-2026-08-06.html'

FOOTER = '''
  <tr><td style="background-color:#1B2A4A;padding:14px 18px;">
    <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
      <tr>
        <td style="width:76px;vertical-align:middle;padding-right:14px;">
          <img src="https://primaauto.com.pl/primaauto-logo-mail.png" alt="Prima-Auto" width="64" height="64" style="display:block;border:0;">
        </td>
        <td style="vertical-align:middle;">
          <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
            <tr><td style="font-size:16px;font-weight:bold;color:#ffffff;padding-bottom:4px;font-family:Arial,Helvetica,sans-serif;">PRIMA-AUTO</td></tr>
            <tr><td style="font-size:12px;color:#ffffff;line-height:1.45;font-family:Arial,Helvetica,sans-serif;">Auta z rynku chińskiego dostępne na indywidualne zamówienie. Każdy samochód przed zakupem jest przez nas weryfikowany, a następnie sprowadzany do Polski dla klienta.</td></tr>
          </table>
        </td>
      </tr>
    </table>
  </td></tr>
  <tr><td style="padding:12px 18px 4px 18px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;font-family:Arial,Helvetica,sans-serif;">
    <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
      <tr><td style="font-size:12px;padding-bottom:3px;color:#111827;">ul. Przemysłowa 13A, 35-105 Rzeszów &nbsp;·&nbsp; <a href="https://primaauto.com.pl" style="color:#000000;text-decoration:none;">www.primaauto.com.pl</a></td></tr>
      <tr><td style="font-size:11px;color:#6b7280;padding-bottom:8px;">PRIMA-AUTO Ruslan Prima · NIP 8133898576 · REGON 525442846 · <a href="https://primaauto.com.pl/polityka-prywatnosci/" style="color:#6b7280;text-decoration:underline;">RODO</a></td></tr>
    </table>
  </td></tr>
  <tr><td style="background-color:#f8fafc;border:1px solid #e5e7eb;padding:10px 18px;font-size:12px;font-family:Arial,Helvetica,sans-serif;">
    <a href="https://primaauto.com.pl/w-rzeszowie/" style="color:#1B2A4A;text-decoration:none;font-weight:bold;">Samochody dostępne od ręki</a>
    <span style="color:#cbd5e1;">&nbsp;&nbsp;•&nbsp;&nbsp;</span>
    <a href="https://primaauto.com.pl/w-drodze/" style="color:#1B2A4A;text-decoration:none;font-weight:bold;">Zakupione — w drodze</a>
    <span style="color:#cbd5e1;">&nbsp;&nbsp;•&nbsp;&nbsp;</span>
    <a href="https://primaauto.com.pl/samochody/" style="color:#1B2A4A;text-decoration:none;font-weight:bold;">Katalog aut z Chin</a>
  </td></tr>
'''

SIGNOFF = '''  <tr><td style="padding:0 26px 18px 26px;font-size:14px;line-height:1.55;">
    <p style="margin:0 0 4px;">Pozdrawiamy,<br><b>Zespół Prima-Auto</b></p>
    <p style="margin:0;font-size:15px;font-weight:bold;"><a href="tel:+48721730507" style="color:#D63031;text-decoration:none;">+48 721 730 507</a></p>
  </td></tr>'''


def cta(label, url, color='#0A66C2'):
    return f'''  <tr><td align="center" style="padding:10px 26px 4px 26px;">
    <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
      <tr><td style="background:{color};">
        <a href="#" style="display:block;padding:15px 32px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;">{label}</a>
      </td></tr>
    </table>
  </td></tr>
  <tr><td align="center" style="padding:8px 26px 16px 26px;font-size:11px;color:#9CA3AF;word-break:break-all;">{url}</td></tr>'''


STEPS = ['Zgłoszenie', 'Potwierdzenie', 'Umowa', 'Rezerwacja i zakup', 'Transport', 'Odbiór']


def badge(text, bg='#38A169'):
    """Etykieta statusu — tabela, żeby Outlook zniósł tło."""
    return f'''<table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 0 12px;">
      <tr><td style="background:{bg};padding:6px 13px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:bold;color:#ffffff;letter-spacing:.06em;">{text}</td></tr>
    </table>'''


def progress(current):
    """Pasek postępu — jedna linia, tabela (Outlook-safe). current = indeks 0..5."""
    cells = []
    for i, s in enumerate(STEPS):
        if i < current:
            style = 'color:#9CA3AF;'
            txt = f'{s} ✓'
        elif i == current:
            style = 'color:#1B2A4A;font-weight:bold;'
            txt = s
        else:
            style = 'color:#9CA3AF;'
            txt = s
        cells.append(f'<td style="padding:0 6px;font-size:11px;line-height:1.3;{style}white-space:nowrap;">{txt}</td>')
        if i < len(STEPS) - 1:
            cells.append('<td style="padding:0;font-size:11px;color:#D1D5DB;">›</td>')
    return f'''  <tr><td style="padding:4px 26px 18px 26px;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F8FAFC;border:1px solid #E5E7EB;">
      <tr><td style="padding:9px 10px;">
        <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;">
          <tr>{''.join(cells)}</tr>
        </table>
      </td></tr>
    </table>
  </td></tr>'''


def body(inner):
    return f'''  <tr><td style="padding:26px 26px 6px 26px;font-size:14px;line-height:1.55;">{inner}
  </td></tr>'''


def wrap(rows):
    return f'''<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F3F4F6;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" border="0" style="width:560px;max-width:100%;border-collapse:collapse;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#111827;">
{rows}
{FOOTER}
</table>
</td></tr>
</table>'''


# ─────────────────────────────────────────────────────────────────────────────
#  MAILE
# ─────────────────────────────────────────────────────────────────────────────
MAILS = []

# ---- 1. welcome — WYCOFANY -------------------------------------------------
MAILS.append(dict(
    num='1', key='welcome', title='Konto zostało utworzone',
    state='killed',
    where='class-asiaauto-order.php:1399 — natychmiast po złożeniu zgłoszenia, równolegle z mailem nr 2',
    verdict='Wyłączamy ze ścieżki zamówienia',
    why='''Klient nie prosił o konto, a mail wyprzedzał potwierdzenie przyjęcia zlecenia i wysyłał hasło
otwartym tekstem. Hasło jest przy tym niepotrzebne: każdy kolejny mail niesie świeży magic link
(ważny 48 h), więc dostęp do zamówienia przychodzi sam. Konto nadal powstaje po cichu — jest
potrzebne, żeby zamówienie miało właściciela. Klient, który zechce stały dostęp, ustawi sobie
hasło przez „Nie pamiętasz hasła?" na /klient/.''',
    subj_before='Prima-Auto — Twoje konto zostało utworzone',
    before='''Cześć Marcin,

W serwisie Prima-Auto zostało utworzone Twoje konto klienta.
Dzięki niemu możesz śledzić status swoich zamówień.

Dane logowania:
Email: marcin.k@example.com
Hasło: Xk7#mQ2vLp9t

Panel klienta: https://primaauto.com.pl/moje-konto/

Zalecamy zmianę hasła po pierwszym logowaniu.

Pozdrawiamy,
Zespół Prima-Auto
+48 721 730 507''',
    after=None,
))

# ---- 2. order_started_customer ---------------------------------------------
MAILS.append(dict(
    num='2', key='order_started_customer', title='Przyjęliśmy Twoje zgłoszenie',
    state='changed',
    where='class-asiaauto-order.php:1820 — po wyłączeniu maila nr 1 jest to PIERWSZY kontakt z klientem',
    verdict='Skrócony — dowód zamiast przechwałki, jasna następna stacja',
    why='''Wyleciało „dobrze trafiłeś!" i „przeprowadziliśmy dziesiątki udanych transakcji" — deklaracja
bez pokrycia zastąpiona linkiem do /klienci/, gdzie ten sam komunikat jest dowodem. Na tym etapie
sprawdzamy WYŁĄCZNIE dostępność i cenę, więc nie ma tu ani słowa o oglądaniu auta na miejscu —
to argument na później, gdy będzie prawdziwy. Zdanie o braku zobowiązania pada raz, nie dwa.
Doszła jawna następna stacja („Potwierdzone — dostaniesz wycenę") i pasek postępu, żeby klient
widział, gdzie jest w całej drodze. Czas odpowiedzi doprecyzowany na wieczór i weekend — obietnica
„kilka godzin" złożona w piątek o 22:00 działa przeciwko nam.''',
    subj_before='Prima-Auto — przyjęliśmy Twoje zgłoszenie #1042',
    subj_after='Prima-Auto — przyjęliśmy Twoje zgłoszenie #1042',
    before='''Cześć Marcin,

Dziękujemy za zainteresowanie pojazdem BYD Sealion 7 Excellence AWD 82,5 kWh 2024 — dobrze trafiłeś!

Zajmujemy się importem samochodów z Chin od lat i przeprowadziliśmy
już dziesiątki udanych transakcji. Twoje zgłoszenie nr #1042
zostało przyjęte — weryfikujemy teraz dostępność tego pojazdu.

Zazwyczaj odpowiadamy w ciągu kilku godzin roboczych.

W międzyczasie możesz zapoznać się z przydatnymi informacjami:
• Jak wygląda proces importu: https://primaauto.com.pl/informacje/proces-zamawiania/
• Homologacja i rejestracja: https://primaauto.com.pl/informacje/homologacja-i-rejestracja/
• Wszystkie informacje o imporcie: https://primaauto.com.pl/informacje/

Status zgłoszenia możesz śledzić tutaj:
https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

W razie pytań — dzwoń lub pisz, chętnie pomożemy.

Pozdrawiamy,
Zespół Prima Auto
+48 721 730 507''',
    after=wrap(
        body('''
    <p style="margin:0 0 14px;">Dzień dobry <b>Marcin</b>,</p>
    <p style="margin:0 0 14px;">Dziękujemy za zgłoszenie — przyjęliśmy je pod numerem <b>#1042</b>.</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F8FAFC;border:1px solid #E5E7EB;margin:0 0 18px;">
      <tr><td style="padding:12px 16px;font-size:15px;"><span style="color:#6B7280;font-size:12px;">Pojazd</span><br><b>BYD Sealion 7 Excellence AWD 82,5 kWh 2024</b></td></tr>
    </table>
    <p style="margin:0 0 16px;">Sprawdzamy teraz u źródła, czy ten egzemplarz jest nadal dostępny i jaka jest jego aktualna cena. Zgłoszenie nic nie kosztuje i do niczego Cię nie zobowiązuje.</p>
    <p style="margin:0 0 16px;"><b>Kiedy się odezwiemy:</b> zazwyczaj w ciągu kilku godzin roboczych. Jeśli piszesz wieczorem lub w weekend — następnego dnia roboczego.</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F0F7F2;border-left:3px solid #38A169;margin:0 0 4px;">
      <tr><td style="padding:12px 16px;font-size:14px;"><b>Następny krok: „Potwierdzone"</b><br>Dostaniesz od nas wycenę sprowadzenia tego auta do Polski — komplet kosztów, bez rozbijania na raty niespodzianek.</td></tr>
    </table>''')
        + progress(0)
        + cta('Śledź status zgłoszenia', 'https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f')
        + body('''
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F8FAFC;border-left:3px solid #1B2A4A;margin:0 0 14px;">
      <tr><td style="padding:12px 16px;font-size:14px;line-height:1.9;">
        <a href="https://primaauto.com.pl/klienci/" style="color:#1B2A4A;">Auta, które sprowadziliśmy dla naszych klientów</a><br>
        <a href="https://primaauto.com.pl/informacje/proces-zamawiania/" style="color:#1B2A4A;">Jak wygląda cały proces, krok po kroku</a>
      </td></tr>
    </table>
    <p style="margin:0;">W razie pytań — dzwoń lub pisz, chętnie pomożemy.</p>''')
        + SIGNOFF
    ),
))


# ---- 3. status_potwierdzone ------------------------------------------------
MAILS.append(dict(
    num='3', key='status_potwierdzone', title='Pojazd dostępny — wycena i dane do umowy',
    state='changed',
    where='class-asiaauto-order.php:1917 — pierwszy mail, w którym pada konkretna kwota',
    verdict='Pełna wycena brutto/netto z prowizją, depozyt PRZED umową, jeden przycisk',
    why='''Kwota pada wprost w mailu — brutto, obok netto, a pod spodem co zawiera i ile z tego jest
naszą prowizją. Depozyt przesunięty NA POCZĄTEK, przed umowę: klient płaci go razem z uzupełnieniem
danych, bo to on odblokowuje inspekcję pojazdu i blokadę oferty. UWAGA — to zmiana kolejności
procesu, nie tylko treści (dziś depozyt jest wpłacany po podpisaniu umowy, mail status_podpisane).
Płatność PayU wymaga wdrożenia T-121. Depozyt 6 150 zł i prowizja 5 000 zł netto to ta sama kwota:
5 000 × 1,23 = 6 150, dlatego faktura ma sens. Instrukcja „zaloguj się do panelu" przestała być
prawdziwa po wyłączeniu maila z hasłem. „Sprzedawca przygotuje umowę" → „przygotujemy" (jesteśmy
pośrednikiem). Wyleciało „ok. 10% ofert z Chin znika w ciągu 24 godzin" — liczba bez źródła.''',
    subj_before='Prima-Auto — pojazd dostępny! Kolejne kroki (zamówienie #1042)',
    subj_after='Prima-Auto — pojazd dostępny (zamówienie #1042)',
    before='''Cześć Marcin,

Mamy dobrą wiadomość — pojazd BYD Sealion 7 Excellence AWD 82,5 kWh 2024 jest dostępny!

Przygotowaliśmy dla Ciebie wstępną wycenę sprowadzenia.
Żeby przejść dalej, potrzebujemy Twoich danych do umowy pośrednictwa.

Umowa pośrednictwa to standardowy dokument, który chroni obie strony —
określa zakres naszej usługi, kwotę zwrotnego depozytu zabezpieczającego
oraz wynagrodzenie za koordynację importu. Nie zobowiązuje Cię do zakupu
pojazdu na tym etapie.

Co teraz należy zrobić:
1. Zaloguj się do panelu zamówienia (link poniżej)
2. Uzupełnij dane osobowe potrzebne do umowy
3. Sprzedawca przygotuje umowę po uzupełnieniu danych — otrzymasz osobne
   powiadomienie mailem gdy umowa będzie gotowa do podpisu

Przejdź do panelu zamówienia:
https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

Warto działać szybko — ok. 10% ofert z Chin znika w ciągu 24 godzin.

Jeśli masz pytania dotyczące wyceny lub procesu, sprawdź:
https://primaauto.com.pl/informacje/proces-zamawiania/
lub skontaktuj się z nami bezpośrednio.

Pozdrawiamy,
Zespół Prima Auto
+48 721 730 507''',
    after=wrap(
        body('''
    <p style="margin:0 0 14px;">Dzień dobry <b>Marcin</b>,</p>
    <p style="margin:0 0 18px;font-size:17px;font-weight:bold;color:#1B2A4A;line-height:1.35;">BYD Sealion 7 Excellence AWD 82,5 kWh 2024 <span style="font-weight:normal;color:#6B7280;font-size:15px;">(#1042)</span> <span style="color:#38A169;">— pojazd DOSTĘPNY</span></p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F8FAFC;border:1px solid #E5E7EB;margin:0 0 20px;">
      <tr><td style="padding:16px;">
        <p style="margin:0 0 4px;font-size:12px;color:#6B7280;">Przewidywana cena całkowita</p>
        <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 0 12px;">
          <tr>
            <td style="vertical-align:baseline;padding-right:14px;font-size:26px;font-weight:bold;color:#1B2A4A;line-height:1.1;">214&nbsp;000&nbsp;zł <span style="font-size:13px;font-weight:normal;color:#6B7280;">brutto</span></td>
            <td style="vertical-align:baseline;font-size:14px;color:#6B7280;">173&nbsp;984&nbsp;zł netto</td>
          </tr>
        </table>
        <p style="margin:0;font-size:13px;color:#4B5563;line-height:1.55;">Cena zawiera transport morski z Chin, odprawę celną, cło, VAT, akcyzę, transport krajowy oraz przygotowanie do rejestracji w Polsce. Prowizja Prima-Auto.</p>
      </td></tr>
    </table>
    <p style="margin:0 0 12px;font-weight:bold;font-size:15px;">Co teraz</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0;font-size:14px;line-height:1.5;">
      <tr>
        <td width="26" style="vertical-align:top;color:#D63031;font-weight:bold;">1.</td>
        <td>
          <b>Opłać depozyt zwrotny (6 150 zł) i uzupełnij dane do umowy</b>
          <p style="margin:5px 0 0;font-size:13px;color:#4B5563;line-height:1.55;">Pozwoli nam to sprawnie zorganizować inspekcję pojazdu i zabezpieczyć ofertę przed wyprzedaniem. Depozyt opłacisz <b style="color:#111827;">bezpiecznie przez PayU</b> (BLIK, karta, bank) albo zwykłym przelewem — w obu przypadkach otrzymasz od nas fakturę.</p>
          <p style="margin:6px 0 0;font-size:13px;"><a href="https://primaauto.com.pl/depozyt/" style="color:#1B2A4A;">Dowiedz się więcej o depozycie zwrotnym</a></p>
        </td>
      </tr>
    </table>''')
        + cta('Opłać depozyt i uzupełnij dane', 'https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f')
        + body('''
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 0 4px;font-size:14px;line-height:1.5;">
      <tr>
        <td width="26" style="vertical-align:top;color:#9CA3AF;font-weight:bold;">2.</td>
        <td>
          <b style="color:#9CA3AF;">Podpisanie umowy</b>
          <p style="margin:5px 0 0;font-size:13px;color:#9CA3AF;line-height:1.55;">Po uzupełnieniu Twoich danych oraz opłaceniu depozytu przygotowujemy umowę pośrednictwa — to pozwoli nam rozpocząć proces importu Twojego pojazdu.</p>
        </td>
      </tr>
    </table>''')
        + progress(1)
        + SIGNOFF
    ),
))


# ---- 4. status_umowa_gotowa ------------------------------------------------
MAILS.append(dict(
    num='4', key='status_umowa_gotowa', title='Umowa gotowa do podpisu',
    state='changed',
    where='class-asiaauto-order.php:1917 — wysyłany, gdy Ruslan wygeneruje umowę',
    verdict='Pięć kroków skrócone do dwóch — depozyt jest już opłacony etap wcześniej',
    why='''Dzisiejsza piątka kroków kończyła się „wpłać depozyt zabezpieczający przelewem bankowym"
— po przeniesieniu depozytu na etap „Potwierdzone" ten krok znika, razem z akapitem o zwrotności
(padł już w mailu 3). Zostają dwie czynności: podpisz i odeślij. Doszła informacja o akceptowanych
formach podpisu — panel przyjmuje skan, podpis kwalifikowany i profil zaufany
(class-asiaauto-order-wizard.php:265), a klient bez drukarki dziś tego nie wie i to jest realne
tarcie. Wyleciało „warto działać szybko, dostępność ofert zmienia się dynamicznie": depozyt jest
wpłacony, oferta zabezpieczona, więc straszenie na tym etapie działa przeciwko nam.''',
    subj_before='Prima-Auto — umowa gotowa do podpisu (zamówienie #1042)',
    subj_after='Prima-Auto — umowa gotowa do podpisu (zamówienie #1042)',
    before='''Cześć Marcin,

Umowa pośrednictwa importu dla pojazdu BYD Sealion 7 Excellence AWD 82,5 kWh 2024 jest gotowa.

Co teraz:
1. Pobierz umowę PDF z panelu zamówienia
2. Przeczytaj warunki — umowa określa zakres usługi, kwotę depozytu
   zabezpieczającego (6 150 zł) i wynagrodzenie za pośrednictwo
3. Podpisz umowę (wydruk + skan lub podpis elektroniczny)
4. Prześlij podpisaną umowę przez panel
5. Wpłać depozyt zabezpieczający przelewem bankowym

Depozyt jest w pełni zwrotny, jeśli nie uda się zarezerwować pojazdu.

Przejdź do panelu zamówienia:
https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

Po otrzymaniu podpisanej umowy i depozytu rezerwujemy pojazd w Chinach.
Warto działać szybko — dostępność ofert zmienia się dynamicznie.

Pytania? Dzwoń lub pisz — chętnie pomożemy.

Pozdrawiamy,
Zespół Prima Auto
+48 721 730 507''',
    after=wrap(
        body('''
    <p style="margin:0 0 14px;">Dzień dobry <b>Marcin</b>,</p>
    <p style="margin:0 0 18px;font-size:17px;font-weight:bold;color:#1B2A4A;line-height:1.35;">BYD Sealion 7 Excellence AWD 82,5 kWh 2024 <span style="font-weight:normal;color:#6B7280;font-size:15px;">(#1042)</span> <span style="color:#38A169;">— UMOWA GOTOWA</span></p>
    <p style="margin:0 0 18px;">Umowa pośrednictwa importu czeka na Twój podpis w panelu zamówienia. Określa zakres naszej usługi, kwotę zwrotnego depozytu oraz wynagrodzenie za koordynację importu.</p>
    <p style="margin:0 0 12px;font-weight:bold;font-size:15px;">Co teraz</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0;font-size:14px;line-height:1.5;">
      <tr>
        <td width="26" style="vertical-align:top;color:#D63031;font-weight:bold;">1.</td>
        <td>
          <b>Pobierz umowę i podpisz</b>
          <p style="margin:5px 0 0;font-size:13px;color:#4B5563;line-height:1.55;">Akceptujemy podpis odręczny (skan albo zdjęcie), podpis kwalifikowany oraz profil zaufany — jeśli podpisujesz elektronicznie, drukarka nie jest potrzebna.</p>
        </td>
      </tr>
    </table>''')
        + cta('Przejdź do umowy', 'https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f')
        + body('''
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 0 4px;font-size:14px;line-height:1.5;">
      <tr>
        <td width="26" style="vertical-align:top;padding:0 0 12px;color:#D63031;font-weight:bold;">2.</td>
        <td style="padding:0 0 12px;"><b>Prześlij podpisaną umowę przez panel</b></td>
      </tr>
      <tr>
        <td style="vertical-align:top;color:#9CA3AF;font-weight:bold;">3.</td>
        <td>
          <b style="color:#9CA3AF;">Rezerwacja pojazdu</b>
          <p style="margin:5px 0 0;font-size:13px;color:#9CA3AF;line-height:1.55;">Po otrzymaniu podpisanej umowy rezerwujemy dla Ciebie ten egzemplarz w Chinach i rozpoczynamy proces importu.</p>
        </td>
      </tr>
    </table>''')
        + progress(2)
        + SIGNOFF
    ),
))


# ---- 5. status_podpisane ---------------------------------------------------
MAILS.append(dict(
    num='5', key='status_podpisane', title='Umowa podpisana',
    state='changed',
    where='class-asiaauto-order.php:1917 — po przyjęciu podpisanej umowy',
    verdict='Z maila „wpłać depozyt" robi się krótkie potwierdzenie i informacja, co robimy my',
    why='''Dziś połowa tego maila to instrukcja wpłaty depozytu z numerem konta i tytułem przelewu —
po przeniesieniu płatności na etap „Potwierdzone" wszystko to znika. Zostaje krótkie potwierdzenie, że
formalności po stronie klienta są zamknięte, i zapowiedź następnego statusu. Mail nie ma nic do
sprzedania ani nic do wyegzekwowania — jego jedyne zadanie to zamknąć etap i powiedzieć, na co
klient czeka.''',
    subj_before='Prima-Auto — umowa podpisana! (zamówienie #1042)',
    subj_after='Prima-Auto — umowa podpisana (zamówienie #1042)',
    before='''Cześć Marcin,

Umowa pośrednictwa importu dla BYD Sealion 7 Excellence AWD 82,5 kWh 2024 została podpisana.
Jesteśmy o krok bliżej!

Kolejny krok: wpłać depozyt zabezpieczający (6 150 zł)
na konto wskazane w panelu zamówienia.
W tytule przelewu podaj numer umowy.

Depozyt jest w pełni zwrotny, jeśli nie uda się zarezerwować pojazdu.

Po otrzymaniu depozytu rezerwujemy auto w Chinach.

Panel zamówienia: https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

Pytania? Dzwoń: +48 721 730 507

Pozdrawiamy,
Zespół Prima Auto''',
    after=wrap(
        body('''
    <p style="margin:0 0 14px;">Dzień dobry <b>Marcin</b>,</p>
    <p style="margin:0 0 18px;font-size:17px;font-weight:bold;color:#1B2A4A;line-height:1.35;">BYD Sealion 7 Excellence AWD 82,5 kWh 2024 <span style="font-weight:normal;color:#6B7280;font-size:15px;">(#1042)</span> <span style="color:#38A169;">— UMOWA PODPISANA</span></p>
    <p style="margin:0 0 18px;">Mamy Twoją podpisaną umowę — dziękujemy. Formalności po Twojej stronie są zakończone.</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F0F7F2;border-left:3px solid #38A169;margin:0 0 4px;">
      <tr><td style="padding:12px 16px;font-size:14px;"><b>Następny krok: „Zarezerwowane"</b><br>Damy Ci znać mailem, gdy egzemplarz będzie zablokowany na Twoje nazwisko.</td></tr>
    </table>''')
        + cta('Podgląd zamówienia', 'https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f')
        + progress(3)
        + SIGNOFF
    ),
))


def head(status_txt, color='#38A169'):
    return f'''
    <p style="margin:0 0 14px;">Dzień dobry <b>Marcin</b>,</p>
    <p style="margin:0 0 18px;font-size:17px;font-weight:bold;color:#1B2A4A;line-height:1.35;">BYD Sealion 7 Excellence AWD 82,5 kWh 2024 <span style="font-weight:normal;color:#6B7280;font-size:15px;">(#1042)</span> <span style="color:{color};">— {status_txt}</span></p>'''


def nextstep(title, desc):
    return f'''
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F0F7F2;border-left:3px solid #38A169;margin:0 0 4px;">
      <tr><td style="padding:12px 16px;font-size:14px;"><b>{title}</b><br>{desc}</td></tr>
    </table>'''


LINK = 'https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f'

# ---- 6. status_zarezerwowane -----------------------------------------------
MAILS.append(dict(
    num='6', key='status_zarezerwowane', title='Pojazd zarezerwowany',
    state='changed',
    where='class-asiaauto-order.php:1917',
    verdict='Lista etapów zamieniona na pasek postępu, termin wyeksponowany',
    why='''Dzisiejsze wyliczenie „zakup → transport morski → odprawa celna → plac w Rzeszowie" powtarza
to, co pokazuje pasek postępu — leci więc do paska. Zostaje jedyna informacja, na której klientowi
naprawdę zależy: ile to potrwa. Wyciągnąłem ją z akapitu do osobnego bloku, bo dziś ginie w tekście.
To tutaj pada argument o inspekcji przed zakupem: rezerwacja jest właśnie po to, żeby ktoś mógł
obejrzeć auto, zanim wydamy pieniądze.''',
    subj_before='Prima-Auto — pojazd zarezerwowany! (zamówienie #1042)',
    subj_after='Prima-Auto — pojazd zarezerwowany (zamówienie #1042)',
    before='''Cześć Marcin,

Pojazd BYD Sealion 7 Excellence AWD 82,5 kWh 2024 jest zarezerwowany dla Ciebie w Chinach!

Teraz koordynujemy zakup i transport do Polski.
Kolejne etapy: zakup → transport morski → odprawa celna → plac w Rzeszowie.
Będziemy informować o każdej zmianie statusu.

Szacowany czas dostawy: ok. 120 dni.

Panel zamówienia: https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

Pytania? Dzwoń: +48 721 730 507

Pozdrawiamy,
Zespół Prima Auto''',
    after=wrap(
        body(head('ZAREZERWOWANY') + '''
    <p style="margin:0 0 16px;">Ten egzemplarz jest zablokowany na Twoje nazwisko — nikt inny go już nie kupi. Zanim zapłacimy chińskiemu sprzedawcy, nasi ludzie na miejscu oglądają auto i sprawdzają zgodność z opisem.</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F8FAFC;border:1px solid #E5E7EB;margin:0 0 16px;">
      <tr><td style="padding:14px 16px;">
        <p style="margin:0 0 2px;font-size:12px;color:#6B7280;">Szacowany czas do odbioru w Polsce</p>
        <p style="margin:0;font-size:20px;font-weight:bold;color:#1B2A4A;">ok. 120 dni</p>
      </td></tr>
    </table>'''
        + nextstep('Następny krok: „Zakupione"', 'Damy znać, gdy auto będzie opłacone i przygotowywane do transportu.'))
        + cta('Podgląd zamówienia', LINK)
        + progress(3)
        + SIGNOFF
    ),
))

# ---- 7. status_zakupione ---------------------------------------------------
MAILS.append(dict(
    num='7', key='status_zakupione', title='Pojazd zakupiony',
    state='changed',
    where='class-asiaauto-order.php:1917',
    verdict='Bez zmian merytorycznych — sam wrapper i schemat nagłówka',
    why='''Ten mail jest już dobry: krótki, konkretny, mówi co dalej. Zmienia się powitanie, nagłówek
ze statusem, podpis i oprawa. Doszła jedna informacja, której brakowało — że od tego momentu
pojazd jest własnością klienta, a my organizujemy transport.''',
    subj_before='Prima-Auto — pojazd zakupiony! (zamówienie #1042)',
    subj_after='Prima-Auto — pojazd zakupiony (zamówienie #1042)',
    before='''Cześć Marcin,

BYD Sealion 7 Excellence AWD 82,5 kWh 2024 został zakupiony u chińskiego dealera!
Przygotowujemy transport do Polski.

Poinformujemy Cię, gdy auto wyruszy w drogę.

Panel zamówienia: https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

Pozdrawiamy,
Zespół Prima Auto
+48 721 730 507''',
    after=wrap(
        body(head('ZAKUPIONY') + '''
    <p style="margin:0 0 16px;">Auto jest opłacone u chińskiego sprzedawcy i przygotowywane do transportu morskiego. Od teraz zajmujemy się wyłącznie logistyką — Ty nie musisz robić nic.</p>'''
        + nextstep('Następny krok: „W drodze"', 'Napiszemy, gdy pojazd wyruszy w rejs do Europy.'))
        + cta('Podgląd zamówienia', LINK)
        + progress(3)
        + SIGNOFF
    ),
))

# ---- 8. status_w_drodze ----------------------------------------------------
MAILS.append(dict(
    num='8', key='status_w_drodze', title='Samochód w drodze',
    state='changed',
    where='class-asiaauto-order.php:1917',
    verdict='Bez zmian merytorycznych — sam wrapper i schemat nagłówka',
    why='''Krótki mail informacyjny, nie ma czego skracać. Status w nagłówku dostaje kolor granatowy,
nie zielony — to nie jest kamień milowy, tylko informacja o trwającym procesie. Zieleń zostaje dla
momentów, w których coś się dla klienta domyka.''',
    subj_before='Prima-Auto — Twój samochód w drodze! (zamówienie #1042)',
    subj_after='Prima-Auto — Twój samochód w drodze (zamówienie #1042)',
    before='''Cześć Marcin,

BYD Sealion 7 Excellence AWD 82,5 kWh 2024 jest w transporcie do Polski!
Trasa obejmuje transport morski, odprawę celną i dostawę na plac w Rzeszowie.

Poinformujemy Cię, gdy auto dotrze na miejsce.

Panel zamówienia: https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

Pozdrawiamy,
Zespół Prima Auto
+48 721 730 507''',
    after=wrap(
        body(head('W DRODZE', '#1B2A4A') + '''
    <p style="margin:0 0 16px;">Twój samochód płynie do Europy. Po zawinięciu do portu zajmiemy się odprawą celną i transportem na plac w Rzeszowie.</p>'''
        + nextstep('Następny krok: „Na placu"', 'Odezwiemy się, gdy auto dotrze do Rzeszowa i będzie gotowe do odbioru.'))
        + cta('Podgląd zamówienia', LINK)
        + progress(4)
        + SIGNOFF
    ),
))

# ---- 9. status_na_placu ----------------------------------------------------
MAILS.append(dict(
    num='9', key='status_na_placu', title='Samochód na placu w Rzeszowie',
    state='changed',
    where='class-asiaauto-order.php:1917',
    verdict='Dodany adres placu — klient dostaje go dopiero teraz, i to z panelu',
    why='''Dziś mail mówi „dotarł na plac w Rzeszowie", ale adresu nie podaje — klient musi go szukać.
Adres jest stałą w kodzie (class-asiaauto-contact.php: ul. Przemysłowa 13A, 35-105 Rzeszów) i pada
w stopce każdego maila, więc niczego nie trzeba dokładać do systemu. Tutaj wchodzi wprost do treści,
razem z informacją, że termin odbioru ustalamy telefonicznie.''',
    subj_before='Prima-Auto — Twój samochód jest na miejscu! (zamówienie #1042)',
    subj_after='Prima-Auto — samochód czeka na odbiór (zamówienie #1042)',
    before='''Cześć Marcin,

BYD Sealion 7 Excellence AWD 82,5 kWh 2024 dotarł na plac w Rzeszowie i czeka na Ciebie!
Skontaktujemy się, aby umówić termin odbioru.

Masz pytania? Dzwoń: +48 721 730 507

Panel zamówienia: https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

Pozdrawiamy,
Zespół Prima Auto''',
    after=wrap(
        body(head('NA PLACU') + '''
    <p style="margin:0 0 16px;">Twój samochód dotarł do Rzeszowa i czeka na odbiór. Zadzwonimy, żeby umówić dogodny termin — możesz też sam wybrać porę, dzwoniąc na numer poniżej.</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F8FAFC;border:1px solid #E5E7EB;margin:0 0 4px;">
      <tr><td style="padding:14px 16px;">
        <p style="margin:0 0 2px;font-size:12px;color:#6B7280;">Miejsce odbioru</p>
        <p style="margin:0;font-size:15px;font-weight:bold;color:#1B2A4A;">ul. Przemysłowa 13A, 35-105 Rzeszów</p>
      </td></tr>
    </table>''')
        + cta('Podgląd zamówienia', LINK)
        + progress(5)
        + SIGNOFF
    ),
))

# ---- 10. status_w_dostawie -------------------------------------------------
MAILS.append(dict(
    num='10', key='status_w_dostawie', title='Samochód jedzie pod wskazany adres',
    state='changed',
    where='class-asiaauto-order.php:1917',
    verdict='Bez zmian merytorycznych — sam wrapper i schemat nagłówka',
    why='''Wariant równoległy do „Na placu" — dla klientów, którzy wybrali dostawę pod dom zamiast
odbioru w Rzeszowie. Treść zostaje, dochodzi tylko link do strony /informacje/pod-dom-do-rejestracji/,
która opisuje tę usługę, a dziś nie jest podlinkowana z żadnego maila.''',
    subj_before='Prima-Auto — Twój samochód jedzie do Ciebie! (zamówienie #1042)',
    subj_after='Prima-Auto — samochód jedzie pod wskazany adres (zamówienie #1042)',
    before='''Cześć Marcin,

BYD Sealion 7 Excellence AWD 82,5 kWh 2024 jest w drodze pod wskazany adres!
Skontaktujemy się, aby potwierdzić termin dostawy.

Masz pytania? Dzwoń: +48 721 730 507

Panel zamówienia: https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

Pozdrawiamy,
Zespół Prima Auto''',
    after=wrap(
        body(head('W DOSTAWIE', '#1B2A4A') + '''
    <p style="margin:0 0 16px;">Twój samochód jedzie pod wskazany adres. Zadzwonimy, żeby potwierdzić termin i godzinę dostawy.</p>
    <p style="margin:0 0 4px;font-size:13px;"><a href="https://primaauto.com.pl/informacje/pod-dom-do-rejestracji/" style="color:#1B2A4A;">Jak wygląda dostawa pod dom i co przygotować</a></p>''')
        + cta('Podgląd zamówienia', LINK)
        + progress(5)
        + SIGNOFF
    ),
))

# ---- 11. status_zakonczone -------------------------------------------------
MAILS.append(dict(
    num='11', key='status_zakonczone', title='Zamówienie zakończone',
    state='changed',
    where='class-asiaauto-order.php:1917 — ostatni mail w ścieżce',
    verdict='Prośba o opinię zostaje, ale przestaje być gołym linkiem',
    why='''Dziś prośba o opinię wisi w treści jako surowy URL do Google z placeid — wygląda jak
wklejka, nie jak prośba. Zostaje przyciskiem. Reszta bez zmian: podziękowanie i zaproszenie do
kolejnego zakupu. Pasek postępu pokazuje komplet ptaszków — dla klienta to wizualne domknięcie
całej drogi, którą przeszedł od zgłoszenia.''',
    subj_before='Prima-Auto — gratulacje, BYD Sealion 7 Excellence AWD 82,5 kWh 2024 jest Twój! (zamówienie #1042)',
    subj_after='Prima-Auto — gratulacje, BYD Sealion 7 jest Twój (zamówienie #1042)',
    before='''Cześć Marcin,

Gratulacje — BYD Sealion 7 Excellence AWD 82,5 kWh 2024 jest Twój!

Dziękujemy za zaufanie. Cieszymy się, że mogliśmy pomóc sprowadzić
Twój wymarzony samochód z Chin.

Jeśli jesteś zadowolony z naszej obsługi, będzie nam bardzo miło,
gdy podzielisz się swoją opinią w Google:
https://search.google.com/local/writereview?placeid=ChIJG8gzYQD7PEchXBoaMJ8UZg

Każda opinia pomaga kolejnym klientom podjąć decyzję.

Jeśli będziesz szukać kolejnego auta z Chin — wiesz gdzie nas znaleźć.

Pozdrawiamy,
Zespół Prima Auto
+48 721 730 507''',
    after=wrap(
        body(head('ODEBRANY') + '''
    <p style="margin:0 0 16px;">Gratulacje — samochód jest Twój. Dziękujemy za zaufanie; cieszymy się, że mogliśmy przeprowadzić Cię przez cały import.</p>
    <p style="margin:0 0 16px;">Jeśli jesteś zadowolony z obsługi, będzie nam bardzo miło, gdy zostawisz opinię. Każda pomaga kolejnym osobom podjąć decyzję.</p>''')
        + cta('Wystaw opinię w Google', 'https://search.google.com/local/writereview?placeid=ChIJG8gzYQD7PEchXBoaMJ8UZg')
        + body('''
    <p style="margin:0 0 4px;">Gdy będziesz szukać kolejnego auta z Chin — wiesz, gdzie nas znaleźć.</p>''')
        + progress(6)
        + SIGNOFF
    ),
))

# ---- 12. status_odrzucone --------------------------------------------------
MAILS.append(dict(
    num='12', key='status_odrzucone', title='Pojazd niedostępny',
    state='changed',
    where='class-asiaauto-order.php:1917 — ścieżka negatywna',
    verdict='Dopisana informacja o zwrocie depozytu — dziś jej nie ma wcale',
    why='''To najważniejsza poprawka w całym zestawie. Po przeniesieniu płatności na etap
„Potwierdzone" klient w tym momencie ma u nas 6 150 zł, a dzisiejszy mail nie mówi o tych
pieniądzach ani słowa — proponuje za to przeglądanie innych ofert. Tak się traci zaufanie
nieodwracalnie. Nowa wersja zaczyna od pieniędzy: depozyt wraca w całości, my inicjujemy zwrot,
klient nie musi o nic prosić. Dopiero potem propozycja alternatywy.''',
    subj_before='Prima-Auto — pojazd niedostępny (zamówienie #1042)',
    subj_after='Prima-Auto — pojazd niedostępny, zwracamy depozyt (zamówienie #1042)',
    before='''Cześć Marcin,

Niestety, BYD Sealion 7 Excellence AWD 82,5 kWh 2024 nie jest już dostępny na rynku chińskim.

Ale nie martw się — pomagamy znaleźć alternatywę.
Sprawdź aktualne oferty na https://primaauto.com.pl/samochody/
lub skontaktuj się z nami, a dobierzemy auto o podobnych parametrach.

Dzwoń: +48 721 730 507

Pozdrawiamy,
Zespół Prima Auto''',
    after=wrap(
        body(head('NIEDOSTĘPNY', '#D63031') + '''
    <p style="margin:0 0 16px;">Przykro nam — ten egzemplarz został sprzedany na rynku chińskim, zanim udało się go zarezerwować.</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F0F7F2;border-left:3px solid #38A169;margin:0 0 16px;">
      <tr><td style="padding:12px 16px;font-size:14px;"><b>Twój depozyt wraca w całości</b><br>Zwrot 6 150 zł uruchamiamy z naszej strony — nie musisz o nic wnioskować. Pieniądze wrócą na konto, z którego przyszły.</td></tr>
    </table>
    <p style="margin:0 0 16px;">Jeśli chcesz, poszukamy podobnego auta — z tego samego rocznika i w zbliżonej cenie. Wystarczy, że odpiszesz na tego maila albo zadzwonisz.</p>''')
        + cta('Zobacz dostępne auta', 'https://primaauto.com.pl/samochody/')
        + SIGNOFF
    ),
))

# ---- 13. status_anulowane --------------------------------------------------
MAILS.append(dict(
    num='13', key='status_anulowane', title='Zamówienie anulowane',
    state='changed',
    where='class-asiaauto-order.php:1917 — ścieżka negatywna',
    verdict='„Skontaktuj się w sprawie zwrotu" zamienione na „skontaktujemy się"',
    why='''Dzisiejsze zdanie „Jeśli wpłacałeś depozyt — skontaktuj się z nami w sprawie zwrotu"
przerzuca na klienta obowiązek upominania się o własne pieniądze, i to warunkowo („jeśli").
Wiemy, czy wpłacił — system trzyma flagę deposit_paid. Zwrot inicjujemy my.''',
    subj_before='Prima-Auto — zamówienie anulowane (#1042)',
    subj_after='Prima-Auto — zamówienie anulowane (#1042)',
    before='''Cześć Marcin,

Zamówienie #1042 na BYD Sealion 7 Excellence AWD 82,5 kWh 2024 zostało anulowane.

Jeśli wpłacałeś depozyt zabezpieczający — skontaktuj się z nami
w sprawie zwrotu.

Chcesz wrócić do tematu? Sprawdź aktualne oferty:
https://primaauto.com.pl/samochody/
lub zadzwoń: +48 721 730 507

Pozdrawiamy,
Zespół Prima Auto''',
    after=wrap(
        body(head('ANULOWANE', '#6B7280') + '''
    <p style="margin:0 0 16px;">Zamówienie #1042 zostało anulowane.</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F0F7F2;border-left:3px solid #38A169;margin:0 0 16px;">
      <tr><td style="padding:12px 16px;font-size:14px;"><b>Rozliczenie depozytu</b><br>Skontaktujemy się z Tobą w sprawie zwrotu wpłaconego depozytu. Zasady rozliczenia opisuje <a href="https://primaauto.com.pl/depozyt/" style="color:#1B2A4A;">strona o depozycie zwrotnym</a>.</td></tr>
    </table>
    <p style="margin:0 0 16px;">Gdybyś chciał wrócić do tematu — jesteśmy do dyspozycji.</p>''')
        + cta('Zobacz dostępne auta', 'https://primaauto.com.pl/samochody/')
        + SIGNOFF
    ),
))

# ---- 14. contract_regenerated ----------------------------------------------
MAILS.append(dict(
    num='14', key='contract_regenerated', title='Nowa wersja umowy',
    state='changed',
    where='class-asiaauto-order-admin.php:664 — po przegenerowaniu umowy przez Ruslana',
    verdict='Dopisany powód i jasne ostrzeżenie o usuniętych dokumentach',
    why='''Zdanie „Poprzednio przesłane dokumenty zostały usunięte" brzmi dziś jak komunikat błędu
i nie mówi, co klient ma z tym zrobić. Po zmianie jest jasne: poprzednia wersja przestała
obowiązywać, podpisz i odeślij nową. Brakuje pola na powód zmiany — jeśli Ruslan mógłby go wpisać
przy przegenerowaniu, mail tłumaczyłby sam siebie; dziś nie ma takiego pola, więc formułka jest
neutralna.''',
    subj_before='Prima-Auto — Nowa wersja umowy (zamówienie #1042)',
    subj_after='Prima-Auto — nowa wersja umowy do podpisu (zamówienie #1042)',
    before='''Cześć Marcin,

Umowa do zamówienia #1042 została zaktualizowana.
Pobierz nową wersję, podpisz i prześlij ponownie w panelu zamówienia.

Poprzednio przesłane dokumenty zostały usunięte.

Pojazd: BYD Sealion 7 Excellence AWD 82,5 kWh 2024

Panel zamówienia: https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

Pozdrawiamy,
Zespół Prima-Auto
+48 721 730 507''',
    after=wrap(
        body(head('NOWA WERSJA UMOWY', '#1B2A4A') + '''
    <p style="margin:0 0 16px;">Przygotowaliśmy zaktualizowaną wersję umowy do Twojego zamówienia. Poprzednia wersja przestała obowiązywać, a przesłane wcześniej dokumenty usunęliśmy — podpisz i odeślij nową.</p>
    <p style="margin:0 0 16px;font-size:13px;color:#4B5563;">Jeśli nie wiesz, co się zmieniło, zadzwoń — wyjaśnimy przed podpisaniem.</p>''')
        + cta('Przejdź do umowy', LINK)
        + progress(2)
        + SIGNOFF
    ),
))

# ---- 15. annex_vin_sent ----------------------------------------------------
MAILS.append(dict(
    num='15', key='annex_vin_sent', title='Aneks z numerem VIN',
    state='changed',
    where='class-asiaauto-order-admin.php:768 — jedyny mail z załącznikiem PDF',
    verdict='Wyjaśnione, po co ten aneks — dziś klient dostaje dokument bez powodu',
    why='''Klient dostaje do podpisu kolejny dokument i nie wie, dlaczego. VIN jest znany dopiero
po zakupie konkretnego egzemplarza, więc aneks uzupełnia umowę o dane, których w chwili
podpisywania nie było — i to zdanie warto powiedzieć wprost, bo bez niego dosyłany aneks wygląda
podejrzanie. Numer VIN wyciągnięty do osobnego bloku: klient będzie go potrzebował przy
ubezpieczeniu i rejestracji.''',
    subj_before='Prima-Auto — aneks do umowy (zamówienie #1042)',
    subj_after='Prima-Auto — aneks z numerem VIN do podpisu (zamówienie #1042)',
    before='''Cześć Marcin,

do umowy nr AA/2026/07/0042 z dnia 2026-07-14 przygotowaliśmy aneks
uzupełniający numer VIN sprowadzanego pojazdu (BYD Sealion 7 Excellence AWD 82,5 kWh 2024).

Numer VIN: LC0C74DE9R0123456

Aneks znajdziesz w załączniku. Wydrukuj, podpisz i odeślij tak samo jak umowę.

Panel zamówienia: https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

Pozdrawiamy,
Zespół Prima-Auto
+48 721 730 507''',
    after=wrap(
        body(head('ANEKS DO PODPISU', '#1B2A4A') + '''
    <p style="margin:0 0 16px;">Znamy już numer nadwozia Twojego samochodu. Numer VIN jest przypisany do konkretnego egzemplarza i poznajemy go dopiero po zakupie, dlatego uzupełniamy umowę aneksem.</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#F8FAFC;border:1px solid #E5E7EB;margin:0 0 16px;">
      <tr><td style="padding:14px 16px;">
        <p style="margin:0 0 2px;font-size:12px;color:#6B7280;">Numer VIN</p>
        <p style="margin:0 0 8px;font-size:18px;font-weight:bold;color:#1B2A4A;font-family:Consolas,Menlo,monospace;">LC0C74DE9R0123456</p>
        <p style="margin:0;font-size:12px;color:#6B7280;">Do umowy nr AA/2026/07/0042 z dnia 14.07.2026</p>
      </td></tr>
    </table>
    <p style="margin:0 0 16px;">Aneks jest w załączniku tego maila. Podpisz go i odeślij tak samo jak umowę. Numer VIN przyda Ci się też przy ubezpieczeniu i rejestracji.</p>''')
        + cta('Prześlij podpisany aneks', LINK)
        + progress(3)
        + SIGNOFF
    ),
))

# ---- 16. fallback ----------------------------------------------------------
MAILS.append(dict(
    num='16', key='(fallback statusu)', title='Statusy bez własnego szablonu',
    state='changed',
    where='class-asiaauto-order.php:1893-1911 — treść zaszyta w kodzie, poza plikiem szablonów',
    verdict='Musi trafić do wrappera razem z resztą, inaczej dwa statusy wypadną z brandingu',
    why='''Ten szablon nie istnieje w defaultEmailTemplates() — jest wpisany bezpośrednio w kod
i uruchamia się dla statusów bez własnej treści, dziś „nowe" i „weryfikacja". Łatwo go przeoczyć
przy wdrożeniu, a wtedy klient dostanie jedyny goły tekstowy mail w całej ścieżce. Treść zostaje
generyczna, bo taka ma być.''',
    subj_before='Prima-Auto — Weryfikacja (zamówienie #1042)',
    subj_after='Prima-Auto — Weryfikacja (zamówienie #1042)',
    before='''Cześć Marcin,

Status zamówienia zmieniono na: Weryfikacja.

Pojazd: BYD Sealion 7 Excellence AWD 82,5 kWh 2024

Szczegóły: https://primaauto.com.pl/zamowienie/?k=a7f3e91b2c4d8e6f

Pozdrawiamy,
Zespół Prima-Auto
+48 721 730 507''',
    after=wrap(
        body(head('WERYFIKACJA', '#1B2A4A') + '''
    <p style="margin:0 0 16px;">Status Twojego zamówienia zmienił się na <b>Weryfikacja</b>.</p>''')
        + cta('Podgląd zamówienia', LINK)
        + progress(0)
        + SIGNOFF
    ),
))


# ─────────────────────────────────────────────────────────────────────────────
def render():
    secs = []
    for m in MAILS:
        killed = m['state'] == 'killed'
        badge = ('<span class="badge badge--kill">wycofany</span>' if killed
                 else '<span class="badge badge--chg">treść zmieniona</span>')
        after_col = ('''<div class="killed">
          <p class="killed__t">Ten mail nie jest wysyłany</p>
          <p class="killed__d">Klient dostaje wyłącznie potwierdzenie przyjęcia zgłoszenia (mail nr 2).<br>
          Konto powstaje po cichu, hasło nie leci mailem.</p>
        </div>''' if killed else f'''<div class="mail">
        <div class="mail__bar">
          <p class="mail__subj">{html.escape(m.get("subj_after", m["subj_before"]))}</p>
          <p class="mail__from">Zamówienia Prima-Auto &lt;china@primaauto.com.pl&gt;</p>
        </div>
        <div class="mail__body mail__body--wrap">{m["after"]}</div>
      </div>''')

        secs.append(f'''
<div class="sec" id="mail{m['num']}">
  <p class="sec__label">Mail {m['num']} · szablon <code>{m['key']}</code> {badge}</p>
  <p class="sec__title">{html.escape(m['title'])}</p>
  <p class="sec__where">{html.escape(m['where'])}</p>
  <div class="verdict"><b>{html.escape(m['verdict'])}</b><br>{html.escape(m['why']).replace(chr(10), ' ')}</div>
  <div class="cols">
    <div>
      <span class="col__tag col__tag--before">Dziś</span>
      <div class="mail">
        <div class="mail__bar">
          <p class="mail__subj">{html.escape(m['subj_before'])}</p>
          <p class="mail__from">Zamówienia Prima-Auto &lt;china@primaauto.com.pl&gt;</p>
        </div>
        <div class="mail__body"><p class="plain">{html.escape(m['before'])}</p></div>
      </div>
    </div>
    <div>
      <span class="col__tag {'col__tag--kill' if killed else 'col__tag--after'}">{'Po zmianie' if killed else 'Propozycja'}</span>
      {after_col}
    </div>
  </div>
</div>''')

    nav = ' · '.join(f'<a href="#mail{m["num"]}">{m["num"]}. {html.escape(m["title"])}</a>' for m in MAILS)

    variants = [
        ('#1B2A4A', 'Granat marki', 'Kolor podstawowy serwisu. Spina przycisk ze stopką — mail czyta się jak jeden dokument. Poważny, „bankowy", bez emocji.'),
        ('#2563EB', 'Błękit', 'Najbardziej „klikalny" — mózg ma go wyuczonego jako przycisk akcji. Żywszy niż granat, ale wprowadza trzeci kolor, którego nie ma na stronie.'),
        ('#38A169', 'Zieleń', 'Kojarzy się z bezpieczeństwem i potwierdzeniem. Uwaga: zieleń pracuje już jako sygnał statusu („— pojazd DOSTĘPNY", ramki) — jeśli przycisk też będzie zielony, ten sygnał się rozmyje.'),
        ('#D63031', 'Czerwień marki', 'Kolor akcentu z serwisu. Najmocniej przyciąga wzrok, ale w skrzynce czyta się jako alert albo agresywna sprzedaż.'),
    ]
    swatches = ''.join(f'''
    <div>
      <p class="sw__name">{n}</p>
      <div style="background:#F3F4F6;padding:16px;border:1px solid var(--bd);border-radius:var(--r);">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;background:#fff;font-family:Arial,Helvetica,sans-serif;">
          <tr><td style="padding:16px 16px 4px;font-size:13px;color:#111827;line-height:1.5;"><b>1.</b> Opłać depozyt zwrotny (6 150 zł) i uzupełnij dane do umowy</td></tr>
          <tr><td align="center" style="padding:12px 16px 16px;">
            <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
              <tr><td style="background:{c};"><a href="#" style="display:block;padding:14px 26px;font-size:15px;font-weight:bold;color:#fff;text-decoration:none;font-family:Arial,Helvetica,sans-serif;">Opłać depozyt i uzupełnij dane</a></td></tr>
            </table>
          </td></tr>
        </table>
      </div>
      <p class="sw__d">{d}</p>
    </div>''' for c, n, d in variants)

    swatch_sec = f'''
<div class="sec" id="kolor">
  <p class="sec__label">Do decyzji</p>
  <p class="sec__title">Kolor przycisku — cztery warianty</p>
  <p class="sec__where">ten sam fragment maila 3, tylko kolor CTA</p>
  <div class="sw">{swatches}</div>
</div>'''

    return f'''<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>T-209 — treści maili: przegląd status po statusie</title>
<style>
:root{{--pa-primary:#1B2A4A;--pa-accent:#D63031;--txt:#2D3748;--sec:#718096;--bd:#E1E4E8;--r:6px;
--font:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;}}
*{{box-sizing:border-box}}
body{{margin:0;background:#E8EAEE;font-family:var(--font);color:var(--txt);line-height:1.5}}
.page{{max-width:1180px;margin:0 auto;padding:32px 16px 64px}}
h1{{font-size:24px;color:var(--pa-primary);margin:0 0 6px}}
.lead{{color:var(--sec);font-size:14px;margin:0 0 18px;max-width:800px}}
.nav{{background:#fff;border:1px solid var(--bd);border-radius:var(--r);padding:12px 16px;font-size:13px;margin:0 0 36px}}
.nav a{{color:var(--pa-primary);text-decoration:none}}
.nav a:hover{{text-decoration:underline}}
.sec{{margin:0 0 52px;scroll-margin-top:16px}}
.sec__label{{font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--sec);font-weight:600;margin:0 0 4px}}
.sec__label code{{background:#fff;border:1px solid var(--bd);border-radius:3px;padding:1px 5px;text-transform:none;letter-spacing:0}}
.sec__title{{font-size:19px;color:var(--pa-primary);margin:0 0 4px;font-weight:600}}
.sec__where{{font-size:12px;color:var(--sec);margin:0 0 12px;font-family:ui-monospace,Menlo,Consolas,monospace}}
.badge{{font-size:10px;font-weight:700;padding:2px 7px;border-radius:3px;letter-spacing:.04em;margin-left:6px;vertical-align:1px}}
.badge--kill{{background:#FCE8E8;color:#B02020}}
.badge--chg{{background:#E7F0FB;color:#1B4F8A}}
.verdict{{background:#fff;border:1px solid var(--bd);border-left:3px solid var(--pa-accent);border-radius:var(--r);
padding:13px 16px;font-size:13px;margin:0 0 18px;max-width:980px;line-height:1.6}}
.verdict b{{color:var(--pa-primary)}}
.cols{{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start}}
@media(max-width:900px){{.cols{{grid-template-columns:1fr}}}}
.col__tag{{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin:0 0 8px;
display:inline-block;padding:3px 9px;border-radius:3px}}
.col__tag--before{{background:#EEF0F3;color:#6B7280}}
.col__tag--after{{background:#1B2A4A;color:#fff}}
.col__tag--kill{{background:#B02020;color:#fff}}
.mail{{background:#fff;border:1px solid var(--bd);border-radius:var(--r);overflow:hidden}}
.mail__bar{{background:#F7F8FA;border-bottom:1px solid var(--bd);padding:10px 14px}}
.mail__subj{{font-size:14px;color:var(--txt);font-weight:600;margin:0 0 3px}}
.mail__from{{font-size:12px;color:var(--sec);margin:0}}
.mail__body{{padding:18px}}
.mail__body--wrap{{background:#F3F4F6;padding:14px}}
.plain{{font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.45;color:#202124;
white-space:pre-wrap;word-break:break-word;margin:0}}
.sw{{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}}
@media(max-width:1000px){{.sw{{grid-template-columns:repeat(2,1fr)}}}}
@media(max-width:620px){{.sw{{grid-template-columns:1fr}}}}
.sw__name{{font-size:13px;font-weight:600;color:var(--pa-primary);margin:0 0 8px}}
.sw__d{{font-size:12px;color:var(--sec);margin:10px 0 0;line-height:1.55}}
.killed{{background:#fff;border:1px dashed #D0A0A0;border-radius:var(--r);padding:34px 24px;text-align:center}}
.killed__t{{font-size:15px;font-weight:600;color:#B02020;margin:0 0 8px}}
.killed__d{{font-size:13px;color:var(--sec);margin:0;line-height:1.6}}
</style>
</head>
<body>
<div class="page">
<h1>T-209 — treści maili, status po statusie</h1>
<p class="lead">Po lewej dokładnie to, co klient dostaje dziś. Po prawej propozycja — treść przepisana pod budowanie
zaufania, w docelowym wrapperze ze stopką D. Przeglądamy po kolei, mail po mailu.</p>
<div class="nav">{nav}</div>
{''.join(secs)}
</div>
</body>
</html>'''


OUT.write_text(render(), encoding='utf-8')
print(f'zapisano: {OUT} ({OUT.stat().st_size} B)')
if '--deploy' in sys.argv:
    DROP.write_text(OUT.read_text(encoding='utf-8'), encoding='utf-8')
    print(f'deploy:   {DROP}')
