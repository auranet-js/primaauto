#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""GTM: zdarzenie GA4 `video_proces_play` (T-267, film procesu zakupu).

Strona wysyła do dataLayer `{event: 'video_proces_play', video_location: 'proces-zamawiania'|'zamow'}`
przy pierwszym odtworzeniu filmu (motyw primaauto2026, assets/js/wideo-proces.js).
Ten skrypt dokłada w GTM: zmienną DLV, wyzwalacz custom event i tag GA4 na wzór
„GA4 - zamowienie_start” (te same wyzwalacze blokujące). Bez oznaczania jako konwersja.

    python3 scripts/gtm-video-proces.py              # recon — nic nie zmienia
    python3 scripts/gtm-video-proces.py --zbuduj     # zmienna + wyzwalacz + tag w Default Workspace
    python3 scripts/gtm-video-proces.py --publikuj   # publikacja (osobna decyzja Janka)

Reguła kontenera: wyłącznie „Default Workspace”, szukany po nazwie (id zmienia się po publikacji).
"""
import argparse
import importlib.util
import sys
from pathlib import Path

_s = importlib.util.spec_from_file_location("gtm", Path(__file__).with_name("gtm-fbclid.py"))
gtm = importlib.util.module_from_spec(_s)
_s.loader.exec_module(gtm)

ZDARZENIE = "video_proces_play"
ZMIENNA = "DLV - video_location"
WYZWALACZ = f"CE - {ZDARZENIE}"
TAG = f"GA4 - {ZDARZENIE}"
WZOR_TAGU = "GA4 - zamowienie_start"


def po_nazwie(lista, nazwa):
    return next((x for x in lista if x["name"] == nazwa), None)


def main():
    a = argparse.ArgumentParser()
    a.add_argument("--zbuduj", action="store_true")
    a.add_argument("--publikuj", action="store_true")
    arg = a.parse_args()

    tok = gtm.token()
    W = lambda *x: gtm.wolaj(tok, *x)
    ws = gtm.default_workspace(tok)
    p = ws["path"]
    zmienne = W(f"{p}/variables")[0].get("variable", [])
    wyzw = W(f"{p}/triggers")[0].get("trigger", [])
    tagi = W(f"{p}/tags")[0].get("tag", [])
    zm, tr, tg = po_nazwie(zmienne, ZMIENNA), po_nazwie(wyzw, WYZWALACZ), po_nazwie(tagi, TAG)
    print(f"Default Workspace {ws['workspaceId']} | zmienna: {bool(zm)} | wyzwalacz: {bool(tr)} | tag: {bool(tg)}")
    print("oczekujące zmiany:", len(W(f"{p}/status")[0].get("workspaceChange", [])))

    if arg.zbuduj:
        if not zm:
            zm, e = W(f"{p}/variables", {"name": ZMIENNA, "type": "v", "parameter": [
                {"type": "integer", "key": "dataLayerVersion", "value": "2"},
                {"type": "boolean", "key": "setDefaultValue", "value": "false"},
                {"type": "template", "key": "name", "value": "video_location"}]})
            print("zmienna:", e or zm["variableId"])
        if not tr:
            tr, e = W(f"{p}/triggers", {"name": WYZWALACZ, "type": "customEvent", "customEventFilter": [
                {"type": "equals", "parameter": [
                    {"type": "template", "key": "arg0", "value": "{{_event}}"},
                    {"type": "template", "key": "arg1", "value": ZDARZENIE}]}]})
            print("wyzwalacz:", e or tr["triggerId"])
        if not tg:
            wzor = po_nazwie(tagi, WZOR_TAGU)
            if not wzor:
                sys.exit(f"brak wzoru „{WZOR_TAGU}”")
            tg, e = W(f"{p}/tags", {
                "name": TAG, "type": "gaawe",
                "parameter": [
                    {"type": "list", "key": "eventSettingsTable", "list": [{"type": "map", "map": [
                        {"type": "template", "key": "parameter", "value": "video_location"},
                        {"type": "template", "key": "parameterValue", "value": "{{" + ZMIENNA + "}}"}]}]},
                    {"type": "template", "key": "eventName", "value": ZDARZENIE},
                    {"type": "template", "key": "measurementIdOverride", "value": "{{GA4-ID}}"}],
                "firingTriggerId": [tr["triggerId"]],
                "blockingTriggerId": wzor.get("blockingTriggerId", []),
                "consentSettings": wzor.get("consentSettings", {"consentStatus": "notSet"})})
            print("tag:", e or tg["tagId"])

    if arg.publikuj:
        v, e = W(f"{p}:create_version", {"name": f"T-267 {ZDARZENIE}",
                                          "notes": "Zdarzenie GA4 odtworzenia filmu procesu zakupu (bez konwersji)."})
        if e:
            sys.exit(f"create_version: {e}")
        cv = v["containerVersion"]
        r, e = W(f"{cv['path']}:publish", {})
        print("publikacja:", e or f"wersja {cv['containerVersionId']} LIVE")


if __name__ == "__main__":
    main()
