#!/usr/bin/env python3
"""
ranking_refresh.py — odświeżanie wpisów rankingowych (T-229, Task 4).

Dwa tryby, bo dwie warstwy zmieniają się w różnym rytmie (D8):

  --oferta   (domyślny, pod cron) przelicza WYŁĄCZNIE bloki `<!--OFERTA:START:klucz-->`.
             Ranking i narracja zostają nietknięte — zmienia się tylko to, co u nas rotuje.
  --ranking  podmienia blok `<!--RANKING:START-->` na dane z nowego miesiąca i prostuje
             nazwę okresu w treści. Uruchamiane świadomie, nie z crona.

Guard (4.2): spadek liczby dopasowań o więcej niż połowę względem poprzedniego przebiegu
zatrzymuje zapis i idzie mailem. Wzorzec z generatorów llms — cichy zjazd oferty do zera
wygląda w treści dokładnie tak samo jak poprawny przebieg.

Użycie:
  python3 ranking_refresh.py --oferta --dry-run
  python3 ranking_refresh.py --oferta
  python3 ranking_refresh.py --ranking --wpis 12345 [--miesiac 202607]
"""
import argparse
import json
import re
import sys
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb
import ranking_generate as rg
import ranking_market as rm
import ranking_specs as rspec
import ranking_stock as rs

META_DANE = "_asiaauto_ranking_dane"
META_STAN = "_asiaauto_ranking_stan_oferty"
BACKUP_DIR = Path.home() / "backups" / "primaauto" / date.today().isoformat()


def wpisy_rankingowe(wpis=None) -> list:
    """Wpisy z danymi rankingu. Bez meta `_asiaauto_ranking_dane` nie ma czego przeliczać."""
    php = '''
$q = new WP_Query([
    "post_type" => "post", "post_status" => ["publish", "draft"], "posts_per_page" => -1,
    "category_name" => "rankingi", "no_found_rows" => true,
]);
$out = [];
foreach ($q->posts as $p) {
    $dane = get_post_meta($p->ID, "%s", true);
    if (!$dane) { continue; }
    $out[] = ["id" => $p->ID, "tytul" => $p->post_title, "slug" => $p->post_name,
              "status" => $p->post_status, "tresc" => $p->post_content,
              "dane" => json_decode($dane, true),
              "stan" => (int) get_post_meta($p->ID, "%s", true)];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
''' % (META_DANE, META_STAN)
    wszystkie = json.loads(kb.wp("eval", php) or "[]")
    if wpis:
        wszystkie = [w for w in wszystkie if str(w["id"]) == str(wpis) or w["slug"] == wpis]
    return wszystkie


def podmien_bloki_oferty(tresc: str, pozycje: list):
    """Podmiana in place po kluczu. Blok pusty też jest podmieniany — pozycja, której kiedyś
    nie mieliśmy, ma się wypełnić sama, gdy auto wjedzie na plac."""
    zmian = 0
    for p in pozycje + [{"klucz": "_podsumowanie", "blok_kompakt": rg.podsumowanie_oferty(pozycje)}]:
        klucz = p.get("klucz")
        if not klucz:
            continue
        wzor = re.compile(re.escape(f"<!--OFERTA:START:{klucz}-->") + r".*?" +
                          re.escape("<!--OFERTA:END-->"), re.S)
        nowy = p["blok_kompakt"]
        tresc, n = wzor.subn(lambda _: nowy, tresc)
        zmian += n
    return tresc, zmian


def zapisz_tresc(post_id, tresc, etykieta):
    BACKUP_DIR.mkdir(parents=True, exist_ok=True)
    stara = kb.wp("post", "get", str(post_id), "--field=content")
    (BACKUP_DIR / f"ranking-{post_id}-{etykieta}.before.html").write_text(stara, encoding="utf-8")
    plik = kb.STATE_DIR / "_ranking_refresh.html"
    kb.STATE_DIR.mkdir(parents=True, exist_ok=True)
    plik.write_text(tresc, encoding="utf-8")
    kb.wp("post", "update", str(post_id), str(plik))


def odswiez_oferte(wpisy, dry_run=False, prog=0.5):
    raporty = []
    for w in wpisy:
        pozycje = w["dane"].get("pozycje", [])
        if not pozycje:
            continue
        pozycje, trafione = rs.wzbogac(pozycje, "model")
        poprzednio = w.get("stan") or 0
        print(f"\n#{w['id']} {w['tytul'][:60]} — dopasowań: {trafione} (poprzednio {poprzednio or '?'})")

        if poprzednio and trafione < poprzednio * prog:
            komunikat = (f"spadek dopasowań {poprzednio} → {trafione} (próg {int(prog * 100)}%) "
                         f"— NIE podmieniam, sprawdź stan oferty")
            print(f"  ⚠ {komunikat}")
            raporty.append({"id": w["id"], "tytul": w["tytul"], "blad": komunikat})
            continue

        nowa, zmian = podmien_bloki_oferty(w["tresc"], pozycje)
        if nowa == w["tresc"]:
            print(f"  bez zmian ({zmian} bloków sprawdzonych)")
            # Stan zapisujemy też przy braku zmian — bez tego guard nie ma punktu odniesienia
            # i pierwszy realny spadek przechodzi bez zatrzymania.
            if not dry_run:
                kb.wp("post", "meta", "set", str(w["id"]), META_STAN, str(trafione))
            raporty.append({"id": w["id"], "tytul": w["tytul"], "zmian": 0, "trafione": trafione})
            continue
        print(f"  {zmian} bloków, treść się zmieniła")
        if dry_run:
            print("  (dry-run — nie zapisuję)")
        else:
            zapisz_tresc(w["id"], nowa, "oferta")
            kb.wp("post", "meta", "set", str(w["id"]), META_STAN, str(trafione))
        raporty.append({"id": w["id"], "tytul": w["tytul"], "zmian": zmian, "trafione": trafione})
    return raporty


def przerenderuj_tabele(w, dry_run=False):
    """Przebudowa tabeli z danych już zapisanych przy wpisie — bez ruszania API i bez zmiany
    liczb. Potrzebne, gdy zmienia się sam znacznik/układ tabeli (np. wersja pod telefon),
    a treść ma zostać ta sama."""
    pozycje = w["dane"].get("pozycje", [])
    if not pozycje:
        print("  brak danych przy wpisie"); return None
    pozycje, trafione = rs.wzbogac(pozycje, "model")
    okres = rg.okres_slownie(w["dane"]["okres"])
    # Rankingi parametryczne maja INNA tabele (inne kolumny i nagłowek). Bez tej galezi
    # przebudowa wstawiala im tabele sprzedazowa - kolumna z sekundami dostawala naglowek
    # „Sprzedaz / sierpien 2026" (blad zlapany przez Janka 2026-08-05).
    definicje = json.loads(rg.DEFINICJE.read_text(encoding="utf-8"))["rankingi"]
    d = definicje.get(w["dane"].get("definicja")) or {}
    if d.get("zrodlo") == "spec":
        # Dane parametryczne przeliczamy z katalogu na nowo: sa nasze, tanie w pobraniu
        # i tylko wtedy komplet kolumn (bateria, ogniwa, moc) jest pewny.
        d["_nazwa"] = w["dane"]["definicja"]
        dane_tab = rspec.ranking_parametryczny(d["parametr"], d.get("top", 20))
        pozycje, trafione = rs.wzbogac(dane_tab["ranking"], "model")
        buduj = lambda: rg.tabela_specow(pozycje, dane_tab, d)
    else:
        buduj = lambda: rg.tabela_rankingu(pozycje, okres)
    nowa = re.sub(r"<!--RANKING:START-->.*?<!--RANKING:END-->",
                  lambda _: "<!--RANKING:START-->" + buduj() + "<!--RANKING:END-->",
                  w["tresc"], flags=re.S)
    nowa = re.sub(r"<!--OFERTA:START:_podsumowanie-->.*?<!--OFERTA:END-->",
                  lambda _: rg.podsumowanie_oferty(pozycje), nowa, flags=re.S)
    if nowa == w["tresc"]:
        print("  znacznik bez zmian"); return None
    print(f"  tabela przebudowana ({len(pozycje)} pozycji, {trafione} z ofertą)")
    if dry_run:
        print("  (dry-run — nie zapisuję)"); return None
    zapisz_tresc(w["id"], nowa, "tabela")
    if d.get("zrodlo") == "spec":
        meta = dict(w["dane"])
        meta.update({"parametr": d["parametr"], "jednostka": dane_tab["jednostka"],
                     "etykieta": dane_tab["etykieta"],
                     "pozycje": [{k: p.get(k) for k in
                                  ("pozycja", "marka", "model", "podmiot", "nasza_marka",
                                   "nasza_serie", "wartosc", "klasa", "klucz", "naped",
                                   "bateria", "zasieg", "ogniwa", "typ_ogniw", "moc",
                                   "predkosc", "przyspieszenie")} for p in pozycje]})
        kb.wp("post", "meta", "set", str(w["id"]), META_DANE, json.dumps(meta, ensure_ascii=False))
    return {"pozycji": len(pozycje), "trafione": trafione}


def odswiez_okladke(w, dry_run=False):
    """Przebudowa okladki z danych zapisanych przy wpisie: zdjecie auta z czolowki rankingu.
    Osobny tryb, bo okladki nie chcemy zmieniac przy kazdym przeliczeniu dostepnosci -
    obrazek skaczacy co dobe to szum w social i w Google Discover."""
    definicje = json.loads(rg.DEFINICJE.read_text(encoding="utf-8"))["rankingi"]
    d = definicje.get(w["dane"].get("definicja"))
    if not d:
        print("  brak definicji w rankingi.json"); return None
    d["_nazwa"] = w["dane"]["definicja"]
    pozycje, _ = rs.wzbogac(w["dane"].get("pozycje", []), "model")
    if dry_run:
        z = rg.foto_z_czolowki(pozycje)
        print(f"  kandydat: {z['auto']} ({z['szerokosc']} px)" if z else "  brak zdjecia o wystarczajacej rozdzielczosci")
        return None
    # Po skasowaniu zalacznika meta _thumbnail_id znika i `wp post meta get` konczy sie bledem,
    # nie pustym stringiem - stad odczyt przez eval zamiast twardego wywolania.
    stare = kb.wp("eval", f'echo (string) get_post_meta({w["id"]}, "_thumbnail_id", true);').strip()
    rg.okladka(str(w["id"]), d, {"zrodlo_data": w["dane"]["okres"]}, pozycje)
    nowe = kb.wp("eval", f'echo (string) get_post_meta({w["id"]}, "_thumbnail_id", true);').strip()
    if stare and nowe and stare != nowe:
        kb.wp("post", "delete", stare, "--force")
        print(f"  stara okladka {stare} usunieta")
    return nowe


def odswiez_ranking(w, miesiac=None, dry_run=False):
    """Podmiana danych rynkowych — świadoma, jeden wpis na raz."""
    definicje = json.loads(rg.DEFINICJE.read_text(encoding="utf-8"))["rankingi"]
    klucz_def = w["dane"].get("definicja")
    d = definicje.get(klucz_def)
    if not d:
        raise SystemExit(f"#{w['id']}: brak definicji `{klucz_def}` w rankingi.json")
    d["_nazwa"] = klucz_def

    stary_okres = w["dane"].get("okres")
    dane = rm.dongchedi_ranking(d["klasy"], miesiac, d.get("typ", 11), d.get("naped", ""), d.get("top"))
    if dane["dziury_w_czolowce"]:
        print("  ⚠ pozycje bez nazwy w czołówce — uzupełnij ranking_names.json:")
        for b in dane["dziury_w_czolowce"]:
            print(f"    {b['marka_cn']} {b['model_cn']} — {b['wartosc']} szt.")
        raise SystemExit(1)
    if dane["zrodlo_data"] == stary_okres:
        print(f"  źródło nadal podaje {stary_okres} — nowych danych nie ma, nic nie zmieniam")
        return None

    pozycje, trafione = rs.wzbogac(dane["ranking"], d.get("poziom_dopasowania", "model"))
    okres = rg.okres_slownie(dane["zrodlo_data"])
    tresc = w["tresc"]

    tresc = re.sub(r"<!--RANKING:START-->.*?<!--RANKING:END-->",
                   lambda _: "<!--RANKING:START-->" + rg.tabela_rankingu(pozycje, okres) + "<!--RANKING:END-->",
                   tresc, flags=re.S)
    tresc = re.sub(r"<!--ZRODLA:START-->.*?<!--ZRODLA:END-->",
                   lambda _: "<!--ZRODLA:START-->" + rg.sekcja_zrodla(dane, trafione) + "<!--ZRODLA:END-->",
                   tresc, flags=re.S)

    # Okres bywa wpisany w narrację („w czerwcu", „dane za czerwiec 2026") — prostujemy oba
    # przypadki i mówimy ile razy, żeby dało się sprawdzić, czy tekst dalej trzyma się sensu.
    stary_m, nowy_m = stary_okres[4:6], dane["zrodlo_data"][4:6]
    podmian = 0
    for tab in (rg.MIESIACE, rg.MIESIACE_D):
        for stara, nowa in ((f"{tab[stary_m]} {stary_okres[:4]}", f"{tab[nowy_m]} {dane['zrodlo_data'][:4]}"),
                            (tab[stary_m], tab[nowy_m])):
            if stara != nowa and stara in tresc:
                podmian += tresc.count(stara)
                tresc = tresc.replace(stara, nowa)

    print(f"  {stary_okres} → {dane['zrodlo_data']}, dopasowań: {trafione}/{len(pozycje)}, "
          f"nazw okresu w treści podmienionych: {podmian}")
    if dry_run:
        print("  (dry-run — nie zapisuję)")
        return {"okres": dane["zrodlo_data"], "trafione": trafione, "podglad": None}

    zapisz_tresc(w["id"], tresc, "ranking")
    meta = dict(w["dane"])
    meta.update({"okres": dane["zrodlo_data"], "porownanie_z": dane["porownanie_z"],
                 "pobrano": dane["pobrano"], "zrodlo_url": dane["zrodlo_url"],
                 "pozycje": [{k: p.get(k) for k in
                              ("pozycja", "marka", "model", "model_cn", "podmiot", "nasza_marka",
                               "nasza_serie", "wartosc", "poprzedni_miesiac", "zmiana_proc",
                               "klasa", "klucz")} for p in pozycje]})
    kb.wp("post", "meta", "set", str(w["id"]), META_DANE, json.dumps(meta, ensure_ascii=False))
    kb.wp("post", "meta", "set", str(w["id"]), META_STAN, str(trafione))
    return {"okres": dane["zrodlo_data"], "trafione": trafione, "podmian_okresu": podmian}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--oferta", action="store_true", help="przelicz bloki dostępności (domyślne)")
    ap.add_argument("--ranking", action="store_true", help="podmień dane rynkowe (świadomie)")
    ap.add_argument("--tabela", action="store_true", help="przebuduj sam znacznik tabeli z zapisanych danych")
    ap.add_argument("--okladka", action="store_true", help="przebuduj okladke ze zdjecia auta z czolowki")
    ap.add_argument("--wpis", help="ID albo slug wpisu; domyślnie wszystkie rankingi")
    ap.add_argument("--miesiac", help="RRRRMM dla --ranking")
    ap.add_argument("--dry-run", action="store_true")
    ap.add_argument("--no-mail", action="store_true")
    a = ap.parse_args()

    wpisy = wpisy_rankingowe(a.wpis)
    if not wpisy:
        print("Brak wpisów rankingowych z danymi.")
        return

    if a.okladka:
        for w in wpisy:
            print(f"#{w['id']} {w['tytul'][:58]}")
            odswiez_okladke(w, a.dry_run)
        return

    if a.tabela:
        for w in wpisy:
            print(f"#{w['id']} {w['tytul'][:60]}")
            przerenderuj_tabele(w, a.dry_run)
        return

    if a.ranking:
        if not a.wpis:
            raise SystemExit("--ranking wymaga --wpis: dane rynkowe podmieniamy świadomie, po jednym")
        for w in wpisy:
            print(f"#{w['id']} {w['tytul'][:60]}")
            wynik = odswiez_ranking(w, a.miesiac, a.dry_run)
            if wynik and not a.dry_run and not a.no_mail:
                kb.send_mail(
                    f"Prima-Auto: ranking odświeżony — {w['tytul']}",
                    f"<p>Podmieniono dane rynkowe na {rg.okres_slownie(wynik['okres'])}. "
                    f"Pozycji z naszą ofertą: {wynik['trafione']}. Nazw okresu w narracji "
                    f"podmienionych: {wynik.get('podmian_okresu', 0)} — warto przeczytać, "
                    f"czy komentarz do czołówki dalej się zgadza.</p>")
        return

    raporty = odswiez_oferte(wpisy, a.dry_run)
    bledy = [r for r in raporty if r.get("blad")]
    if bledy and not a.no_mail and not a.dry_run:
        kb.send_mail(
            "Prima-Auto: ranking — dostępność nie przeliczona",
            "<p>Guard zatrzymał przeliczenie dostępności w rankingach:</p><ul>" +
            "".join(f"<li>#{b['id']} {b['tytul']}: {b['blad']}</li>" for b in bledy) + "</ul>")


if __name__ == "__main__":
    main()
