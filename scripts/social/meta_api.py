"""Wspólna warstwa dostępu do Graph API dla narzędzi Meta w tym repo.

Jedno miejsce, w którym siedzi token, wersja API i tłumaczenie błędów Mety na czytelny
komunikat. Rotatory (`rotator_wideo.py`, `rotator_postow.py`) i sonda `dsa_status.py`
korzystają wyłącznie stąd — dzięki temu podbicie wersji Graph albo zmiana ścieżki tokenu
to jedna edycja, nie cztery.

    from meta_api import get, post, page_token, ACT, PAGE

Wywołania piszące domyślnie NIC NIE TWORZĄ, dopóki nie podasz `waliduj=False`:
`post(..., waliduj=True)` dopisuje `execution_options=["validate_only"]`, czyli Meta
sprawdza komplet parametrów i uprawnień, po czym odrzuca obiekt. To jest sposób na
sprawdzenie „czy przejdzie" bez dotykania konta klienta.
"""
import json
import os
import urllib.error
import urllib.parse
import urllib.request

WERSJA = 'v25.0'
GRAPH = f'https://graph.facebook.com/{WERSJA}/'
TOKEN_PLIK = '/home/host476470/secrets/meta/tokens/primaauto-portfolio-ruslan.txt'

ACT = 'act_1038563008906171'          # konto reklamowe „Prima-Auto"
PAGE = '1146829831857839'             # Strona FB (nowa) — tu publikuje Andrzej
IG = '17841457773875233'              # profil Instagram
PIKSEL = '1634147041766916'
KATALOG = '2936723456671912'
DSA_PODMIOT = 'Prima Auto'            # dokładna nazwa zweryfikowanego portfolio

KATALOG_STANU = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'state')

_token_cache = {}


def token():
    """Token System Usera — bezterminowy, z uprawnieniami reklamowymi."""
    if 'su' not in _token_cache:
        _token_cache['su'] = open(TOKEN_PLIK).read().strip()
    return _token_cache['su']


def page_token():
    """Token Strony — WYMAGANY do odczytu postów.

    Token System Usera ma `pages_read_engagement` na liście scope'ów, ale `published_posts`
    i tak odrzuca go z błędem 210 „A page access token is required". Zmierzone 28.08 —
    nie próbuj skracać drogi tokenem SU, bo błąd sugeruje brak uprawnień, a nie zły typ tokenu.
    """
    if 'page' not in _token_cache:
        d, e = get('me/accounts?fields=id,access_token')
        if e:
            raise RuntimeError(f'nie mogę pobrać tokenu Strony: {e}')
        pasujace = [p['access_token'] for p in d.get('data', []) if p['id'] == PAGE]
        if not pasujace:
            raise RuntimeError(f'Strona {PAGE} nie jest widoczna dla tego System Usera')
        _token_cache['page'] = pasujace[0]
    return _token_cache['page']


def _blad(e):
    tresc = json.load(e).get('error', {})
    kod = f"{tresc.get('code')}/{tresc.get('error_subcode')}"
    tytul = tresc.get('error_user_title') or ''
    opis = tresc.get('error_user_msg') or tresc.get('message', '')
    return f"{kod} {tytul} :: {opis[:200]}".strip()


def get(sciezka, tok=None):
    """GET. Zwraca (dane, None) albo (None, opis_błędu)."""
    tok = tok or token()
    url = GRAPH + sciezka + ('&' if '?' in sciezka else '?') + 'access_token=' + urllib.parse.quote(tok)
    try:
        return json.load(urllib.request.urlopen(urllib.request.Request(url))), None
    except urllib.error.HTTPError as e:
        return None, _blad(e)


def post(sciezka, dane, tok=None, waliduj=True):
    """POST. Domyślnie `validate_only` — Meta sprawdza wszystko i nic nie tworzy.

    Dopiero `waliduj=False` zapisuje obiekt na koncie klienta. Ta domyślność jest celowa:
    pomyłka w skrypcie ma kosztować komunikat, nie kampanię w bibliotece reklamowej.
    """
    tok = tok or token()
    dane = dict(dane, access_token=tok)
    if waliduj:
        dane['execution_options'] = json.dumps(['validate_only'])
    req = urllib.request.Request(GRAPH + sciezka, data=urllib.parse.urlencode(dane).encode())
    try:
        return json.load(urllib.request.urlopen(req)), None
    except urllib.error.HTTPError as e:
        return None, _blad(e)


def stan_wczytaj(nazwa, domyslny=None):
    sciezka = os.path.join(KATALOG_STANU, nazwa)
    if not os.path.exists(sciezka):
        return domyslny if domyslny is not None else {}
    return json.load(open(sciezka, encoding='utf-8'))


def stan_zapisz(nazwa, dane):
    os.makedirs(KATALOG_STANU, exist_ok=True)
    sciezka = os.path.join(KATALOG_STANU, nazwa)
    json.dump(dane, open(sciezka, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
    return sciezka


def landing_zyje(url, limit_s=10):
    """Kod odpowiedzi landingu. 301 na hubie marki = kreacja marnuje budżet.

    Nie podąża za przekierowaniem celowo — chcemy wiedzieć, że slug jest zły, a nie
    dostać 200 z docelowego huba i uznać sprawę za załatwioną.
    """
    class BezPrzekierowan(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, *a, **k):
            return None

    opener = urllib.request.build_opener(BezPrzekierowan)
    req = urllib.request.Request(url, method='HEAD', headers={'User-Agent': 'primaauto-rotator/1.0'})
    try:
        return opener.open(req, timeout=limit_s).status
    except urllib.error.HTTPError as e:
        return e.code
    except Exception:
        return 0
