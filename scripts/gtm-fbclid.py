#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""GTM: wycięcie `fbclid` z `page_location` wysyłanego do GA4.

Po co: Facebook dokleja do każdego klikniętego linku `?fbclid=…`, inny przy każdym
kliknięciu. GA4 zapisuje go razem z adresem, więc raport stron docelowych rozsypuje się
na unikaty — 04-07.09 jedna oferta Denzy siedzi w czterech wierszach po jednej sesji.
GA4 nie ma na to przełącznika (lista niechcianych ODESŁAŃ to co innego, a „Modyfikuj
zdarzenia" umie nadpisać wartość stałą, nie wyciąć z niej fragmentu). Stąd GTM.

Czego to NIE rusza: UTM-y zostają, więc `facebook / paid_social` i nazwy kampanii
nie znikają. Piksel Meta czyta adres z przeglądarki sam — jego atrybucja bez zmian.
Działa od publikacji w przód; historii nie naprawia.

    python3 scripts/gtm-fbclid.py                # recon — stan kontenera, nic nie zmienia
    python3 scripts/gtm-fbclid.py --zbuduj       # zmienna + podpięcie w Default Workspace
    python3 scripts/gtm-fbclid.py --publikuj     # publikacja (osobna decyzja Janka)

Reguła kontenera: pracujemy WYŁĄCZNIE w „Default Workspace", nigdy nie tworzymy nowego.
Jego id zmienia się po każdej publikacji, więc szukamy po nazwie.
"""
import argparse
import json
import sys
import urllib.parse
import urllib.request
from pathlib import Path

SECRETS = Path.home() / "secrets" / "google"
BAZA = "https://tagmanager.googleapis.com/tagmanager/v2"
SCIEZKA = "accounts/6351095501/containers/250095450"
NAZWA_ZMIENNEJ = "URL bez fbclid"

# Parametry klikowe, które nie niosą informacji o treści strony, a rozbijają raporty.
CZYSC = ["fbclid", "gclid", "wbraid", "gbraid", "msclkid", "ttclid"]

JS = ("function() {\n"
      "  try {\n"
      "    var u = new URL(document.location.href);\n"
      "    var brud = " + json.dumps(CZYSC) + ";\n"
      "    for (var i = 0; i < brud.length; i++) { u.searchParams.delete(brud[i]); }\n"
      "    return u.href;\n"
      "  } catch (e) { return document.location.href; }\n"
      "}")


def token():
    o = json.loads((SECRETS / "oauth-desktop-client.json").read_text())["installed"]
    t = json.loads((SECRETS / "tokens.json").read_text())
    data = urllib.parse.urlencode({
        "client_id": o["client_id"], "client_secret": o["client_secret"],
        "refresh_token": t["refresh_token"], "grant_type": "refresh_token"}).encode()
    return json.load(urllib.request.urlopen(
        urllib.request.Request("https://oauth2.googleapis.com/token", data=data)))["access_token"]


def wolaj(tok, sciezka, dane=None, metoda=None):
    req = urllib.request.Request(
        f"{BAZA}/{sciezka}",
        data=json.dumps(dane).encode() if dane is not None else None,
        headers={"Authorization": f"Bearer {tok}", "Content-Type": "application/json"},
        method=metoda or ("POST" if dane is not None else "GET"))
    try:
        return json.load(urllib.request.urlopen(req)), None
    except urllib.error.HTTPError as e:
        return None, f"{e.code} {e.read().decode()[:300]}"


def default_workspace(tok):
    d, e = wolaj(tok, f"{SCIEZKA}/workspaces")
    if e:
        sys.exit(f"nie mogę odczytać workspace'ów: {e}")
    for w in d.get("workspace", []):
        if w["name"] == "Default Workspace":
            return w
    sys.exit("nie ma Default Workspace — nie twórz nowego, sprawdź kontener ręcznie")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--zbuduj", action="store_true")
    ap.add_argument("--publikuj", action="store_true")
    ap.add_argument("--tag", metavar="ID", help="wypisz surowy JSON tagu i wyjdź")
    a = ap.parse_args()
    tok = token()
    ws = default_workspace(tok)
    wsp = f"{SCIEZKA}/workspaces/{ws['workspaceId']}"
    print(f"Default Workspace: {ws['workspaceId']}")

    if a.tag:
        d, e = wolaj(tok, f"{wsp}/tags/{a.tag}")
        print(json.dumps(d or e, ensure_ascii=False, indent=1))
        return

    st, _ = wolaj(tok, f"{wsp}/status")
    zmiany = (st or {}).get("workspaceChange", [])
    print(f"niezapisane zmiany w workspace: {len(zmiany)}")
    for z in zmiany:
        obj = z.get("tag") or z.get("variable") or z.get("trigger") or {}
        print(f"  · {z.get('changeStatus')}: {obj.get('name', '?')}")

    zm, _ = wolaj(tok, f"{wsp}/variables")
    istnieje = [v for v in (zm or {}).get("variable", []) if v["name"] == NAZWA_ZMIENNEJ]
    tagi, _ = wolaj(tok, f"{wsp}/tags")
    ga4 = [t for t in (tagi or {}).get("tag", []) if t.get("type") == "googtag"
           or "GA4" in t.get("name", "")]
    print(f"\nzmienna \u201e{NAZWA_ZMIENNEJ}\u201d: " + ("JEST" if istnieje else "brak"))
    print("tagi GA4 w kontenerze:")
    for t in ga4:
        par = {p["key"]: p.get("value", p.get("list")) for p in t.get("parameter", [])}
        pl = par.get("configSettingsTable") or par.get("eventSettingsTable")
        print(f"  · {t['tagId']:>3} {t['name'][:44]:44} typ={t['type']}")
        if pl:
            print(f"      ustawienia: {json.dumps(pl, ensure_ascii=False)[:220]}")

    if a.publikuj:
        d, e = wolaj(tok, f"{wsp}:create_version",
                     {"name": "fbclid poza page_location", "notes":
                      "Zmienna \u201eURL bez fbclid\u201d podpieta pod page_location w tagu GA4."})
        if e:
            sys.exit(f"wersja nie powstała: {e}")
        wer = d["containerVersion"]["containerVersionId"]
        d, e = wolaj(tok, f"{SCIEZKA}/versions/{wer}:publish", {})
        print(f"\npublikacja wersji {wer}: {'OK' if not e else e}")
        return

    if not a.zbuduj:
        print("\n(recon — nic nie zmienione; --zbuduj tworzy zmienną i podpięcie)")
        return

    if istnieje:
        print(f"\nzmienna już jest ({istnieje[0]['variableId']}) — nie duplikuję")
        zmienna = istnieje[0]
    else:
        zmienna, e = wolaj(tok, f"{wsp}/variables", {
            "name": NAZWA_ZMIENNEJ, "type": "jsm",
            "parameter": [{"type": "TEMPLATE", "key": "javascript", "value": JS}],
            "notes": "Zdejmuje fbclid/gclid i pokrewne z adresu wysyłanego do GA4."})
        if e:
            sys.exit(f"zmienna nie powstała: {e}")
        print(f"\nzmienna utworzona: {zmienna['variableId']}")
    ref = "{{" + NAZWA_ZMIENNEJ + "}}"

    # Podpięcie pod tag „GA4 Tag" (googtag, id 4). Nadpisanie page_location w tagu
    # konfiguracyjnym obejmuje WSZYSTKIE zdarzenia GA4, więc nie trzeba tykać
    # pojedynczych tagów click_phone / click_whatsapp / generate_lead.
    tag, e = wolaj(tok, f"{wsp}/tags/4")
    if e:
        sys.exit(f"nie mogę odczytać tagu GA4: {e}")
    par = [p for p in tag.get("parameter", []) if p["key"] != "configSettingsTable"]
    stare = [p for p in tag.get("parameter", []) if p["key"] == "configSettingsTable"]
    wiersze = stare[0]["list"] if stare else []
    def klucz(w):
        return next((x["value"] for x in w["map"] if x["key"] == "parameter"), None)
    if any(klucz(w) == "page_location" for w in wiersze):
        print("tag GA4 ma już nadpisane page_location — nie ruszam")
        return
    wiersze.append({"type": "map", "map": [
        {"type": "template", "key": "parameter", "value": "page_location"},
        {"type": "template", "key": "parameterValue", "value": ref}]})
    par.append({"type": "list", "key": "configSettingsTable", "list": wiersze})
    tag["parameter"] = par
    d, e = wolaj(tok, f"{wsp}/tags/4", tag, metoda="PUT")
    if e:
        sys.exit(f"tag nie zaktualizowany: {e}")
    print(f"tag „GA4 Tag\u201d: page_location = {ref}")
    print("\nZmiany LEŻĄ W WORKSPACE, nie na produkcji. Publikacja: --publikuj")


if __name__ == "__main__":
    main()
