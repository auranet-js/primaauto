#!/usr/bin/env python3
"""Publikacja Reels na Instagramie @prima_auto.pl z materiału sesji.

Użycie:
    python3 scripts/social/ig_reels.py --dry-run          # pokaż, nic nie wysyłaj
    python3 scripts/social/ig_reels.py --model shark-6    # opublikuj jeden
    python3 scripts/social/ig_reels.py --wszystkie        # cała fala

Wymaga: token SU z instagram_content_publish (~/secrets/meta/tokens/primaauto-portfolio-ruslan.txt).
Film musi leżeć pod publicznym URL z faststart (moov na początku) — patrz drive_lib.py.
"""
import argparse, json, os, sys, time, urllib.parse, urllib.request

IG_ID = '17841457773875233'
TOKEN = '/home/host476470/secrets/meta/tokens/primaauto-portfolio-ruslan.txt'
BAZA_URL = 'https://auratest.pl/fe4f58fec53ctmp/social-reels/'
STAN = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'state', 'ig-opublikowane.json')
GRAPH = 'https://graph.facebook.com/v21.0/'

# Treść pod Instagram — NIE kopia opisu z YouTube.
# Na IG linki w podpisie nie są klikalne, więc CTA kieruje do profilu, nie do URL-a.
FALA2 = {
    'deepal-g318': {
        'plik': 'ig-deepal-g318-pion.mp4',
        'podpis': """Deepal G318 — terenowy SUV, który wygląda jak wojskowy, a jeździ jak hybryda.

430 KM · napęd 4x4 · plug-in z zasięgiem spalinowym
196 000 zł brutto, w Polsce, po wszystkich opłatach
Stoi na naszym placu w Rzeszowie — można obejrzeć i pojechać.

Jesteśmy bezpośrednim importerem: sprowadzamy z Chin na zamówienie, każde auto sprawdzamy przed zakupem, zajmujemy się transportem, cłem, homologacją i rejestracją.

Pełna oferta i ponad 3000 aut w katalogu — link w profilu.

#DeepalG318 #Deepal #autazChin #importaut #SUV #PHEV #hybrydaplugin #samochodyzChin #Rzeszow #PrimaAuto""",
    },
    'leopard-7': {
        'plik': 'ig-leopard-7-pion.mp4',
        'podpis': """BYD Leopard 7 — 640 KM w hybrydzie plug-in.

Napęd na cztery koła · zawieszenie pneumatyczne · segment premium
234 000 zł brutto, w Polsce, po wszystkich opłatach
Od ręki na placu w Rzeszowie.

Bezpośredni import z Chin — auto weryfikujemy przed zakupem, załatwiamy transport, cło, homologację i rejestrację.

Wszystkie egzemplarze i katalog ponad 3000 aut — link w profilu.

#BYDLeopard7 #Leopard7 #FangChengBao #BYD #autazChin #importaut #SUV #PHEV #samochodyzChin #PrimaAuto""",
    },
    'shark-6': {
        'plik': 'ig-byd-shark-6-pion.mp4',
        'podpis': """Pierwszy BYD Shark 6 w Polsce. Stoi u nas w Rzeszowie.

Pickup w hybrydzie plug-in · 435 KM · napęd 4x4
250 000 zł brutto, w Polsce, po wszystkich opłatach

To nie jest zdjęcie z folderu — to konkretny egzemplarz, który przyjechał na nasz plac. Można obejrzeć i pojechać.

Bezpośredni import z Chin: weryfikacja przed zakupem, transport, cło, homologacja, rejestracja.

Katalog ponad 3000 aut — link w profilu.

#BYDShark6 #BYDShark #pickup #pickupPHEV #BYD #pierwszywPolsce #autazChin #importaut #samochodyzChin #Rzeszow #PrimaAuto""",
    },
    'z9-gt': {
        'plik': 'ig-denza-z9-gt-pion.mp4',
        'podpis': """Denza Z9 GT — 870 KM i trzy silniki.

Hybryda plug-in · skręcana tylna oś · wnętrze klasy premium
291 000 zł brutto, w Polsce, po wszystkich opłatach
Od ręki na placu w Rzeszowie.

U oficjalnego dystrybutora ten model zaczyna się grubo powyżej pół miliona. My sprowadzamy bezpośrednio z Chin — transport, cło, homologacja i rejestracja po naszej stronie.

Katalog ponad 3000 aut — link w profilu.

#DenzaZ9GT #Denza #BYD #autazChin #importaut #PHEV #hybrydaplugin #premium #samochodyzChin #PrimaAuto""",
    },
    'n9': {
        'plik': 'ig-denza-n9-pion.mp4',
        'podpis': """Denza N9 — 925 KM i sześć miejsc.

Flagowy SUV · hybryda plug-in · trzy rzędy foteli
344 000 zł brutto, w Polsce, po wszystkich opłatach
Od ręki na placu w Rzeszowie.

Auto dla kogoś, kto potrzebuje przestrzeni i nie chce rezygnować z osiągów. Bezpośredni import z Chin — sprawdzamy przed zakupem, załatwiamy całą drogę do polskich tablic.

Katalog ponad 3000 aut — link w profilu.

#DenzaN9 #Denza #BYD #SUV #autazChin #importaut #PHEV #samochod6osobowy #samochodyzChin #PrimaAuto""",
    },
    'lynk-900': {
        'plik': 'ig-lynk-co-900-pion.mp4',
        'podpis': """Lynk & Co 900 — kupiony, w drodze do Polski.

Sześcioosobowy SUV premium · hybryda plug-in
od 242 000 zł brutto, w Polsce, po wszystkich opłatach

To auto jest już nasze i płynie. Jeśli szukasz takiego modelu, teraz jest moment na rozmowę — zanim trafi na plac.

Bezpośredni import z Chin: weryfikacja przed zakupem, transport, cło, homologacja, rejestracja.

Katalog ponad 3000 aut — link w profilu.

#LynkCo900 #LynkCo #SUV #autazChin #importaut #PHEV #samochod6osobowy #samochodyzChin #PrimaAuto""",
    },
}


def token():
    return open(TOKEN).read().strip()


def graph(sciezka, dane=None, metoda=None):
    url = GRAPH + sciezka
    if dane is None:
        r = urllib.request.urlopen(url)
    else:
        r = urllib.request.urlopen(urllib.request.Request(
            url, data=urllib.parse.urlencode(dane).encode(), method=metoda or 'POST'))
    return json.load(r)


def stan_wczytaj():
    if os.path.exists(STAN):
        return json.load(open(STAN))
    return {}


def stan_zapisz(s):
    os.makedirs(os.path.dirname(STAN), exist_ok=True)
    json.dump(s, open(STAN, 'w'), ensure_ascii=False, indent=2)


def opublikuj(klucz, poz, tok, sucho):
    url = BAZA_URL + poz['plik']
    print(f"\n=== {klucz} ===")
    print(f"plik   {url}")
    print(f"podpis {len(poz['podpis'])} znaków, {poz['podpis'].count('#')} hashtagów")
    if sucho:
        print(poz['podpis'])
        print("[dry-run] nic nie wysłano")
        return None

    kontener = graph(f'{IG_ID}/media', {
        'media_type': 'REELS', 'video_url': url,
        'caption': poz['podpis'], 'share_to_feed': 'true', 'access_token': tok})
    cid = kontener['id']
    print(f"kontener {cid} — czekam na przetworzenie")

    for proba in range(40):
        time.sleep(6)
        st = graph(f'{cid}?fields=status_code,status&access_token={urllib.parse.quote(tok)}')
        kod = st.get('status_code')
        print(f"  [{proba*6+6:>3}s] {kod}")
        if kod == 'FINISHED':
            break
        if kod == 'ERROR':
            print("  BŁĄD:", json.dumps(st, ensure_ascii=False)[:400])
            return None
    else:
        print("  timeout — kontener nie doszedł do FINISHED")
        return None

    wynik = graph(f'{IG_ID}/media_publish', {'creation_id': cid, 'access_token': tok})
    print("OPUBLIKOWANE:", wynik)
    return wynik.get('id')


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--dry-run', action='store_true')
    ap.add_argument('--model', help='klucz z FALA2, np. shark-6')
    ap.add_argument('--wszystkie', action='store_true')
    a = ap.parse_args()

    if not (a.model or a.wszystkie or a.dry_run):
        ap.error('podaj --model, --wszystkie albo --dry-run')

    tok = token()
    stan = stan_wczytaj()
    klucze = [a.model] if a.model else list(FALA2)

    for k in klucze:
        if k not in FALA2:
            print(f"nieznany model: {k}", file=sys.stderr); continue
        if k in stan and not a.dry_run:
            print(f"{k}: już opublikowane ({stan[k]['media_id']}) — pomijam"); continue
        mid = opublikuj(k, FALA2[k], tok, a.dry_run)
        if mid:
            stan[k] = {'media_id': mid, 'plik': FALA2[k]['plik']}
            stan_zapisz(stan)


if __name__ == '__main__':
    main()
