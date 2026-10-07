"""Nagranie: prywatne oferty — co widzi klient (T-258). Playwright, widok telefonu, polskie plansze.

Użycie: python3.12 scripts/t258-nagranie-klient.py <out.webm> <aktualny_klucz> <stary_klucz>
Potem: ~/bin/ffmpeg -i out.webm -c:v libx264 -pix_fmt yuv420p -movflags +faststart out.mp4
Nie wysyła formularza zamówienia (tylko wypełnia). Klucz: wp eval "echo AsiaAuto_Private_Offer::getKey(ID);"
"""
import sys, shutil, pathlib
from playwright.sync_api import sync_playwright

OUT = pathlib.Path(sys.argv[1])
NEW, OLD = sys.argv[2], sys.argv[3]
BASE = "https://primaauto.com.pl/oferta/xiaomi-yu7-2025-387505/"
VW, VH = 390, 844

CARD = """<html><body style="margin:0;height:100vh;display:flex;align-items:center;justify-content:center;
background:#0f1d2e;color:#fff;font-family:Arial,sans-serif;text-align:center;padding:28px;box-sizing:border-box">
<div><div style="font-size:15px;letter-spacing:2px;color:#f5a623;margin-bottom:18px">{kicker}</div>
<div style="font-size:27px;font-weight:bold;line-height:1.3">{title}</div>
<div style="font-size:18px;line-height:1.5;margin-top:22px;color:#cfd8e3">{body}</div></div></body></html>"""

CAPTION_JS = """(t) => { let d = document.getElementById('__cap');
 if (!d) { d = document.createElement('div'); d.id='__cap'; document.body.appendChild(d); }
 d.style.cssText='position:fixed;left:10px;right:10px;top:10px;z-index:2147483647;background:rgba(15,29,46,.94);color:#fff;'+
 'font:bold 17px/1.4 Arial,sans-serif;padding:14px 16px;border-radius:10px;border-left:6px solid #f5a623;box-shadow:0 4px 18px rgba(0,0,0,.35)';
 d.innerHTML = t; }"""


def card(page, kicker, title, body, ms=4200):
    page.set_content(CARD.format(kicker=kicker, title=title, body=body))
    page.wait_for_timeout(ms)


def cap(page, text, ms=3500):
    page.evaluate(CAPTION_JS, text)
    page.wait_for_timeout(ms)


def deny_cookies(page):
    try:
        page.locator("button.cmplz-deny").first.click(timeout=4000)
    except Exception:
        pass
    try:
        page.locator("button.pa-news__x").first.click(timeout=1500)
    except Exception:
        pass


with sync_playwright() as p:
    b = p.chromium.launch()
    tmp = OUT.parent / "vid-tmp"
    shutil.rmtree(tmp, ignore_errors=True)
    ctx = b.new_context(viewport={"width": VW, "height": VH}, device_scale_factor=2, is_mobile=True, has_touch=True,
                        record_video_dir=str(tmp), record_video_size={"width": VW * 2, "height": VH * 2}, locale="pl-PL")
    page = ctx.new_page()

    card(page, "PRIMA AUTO · PRYWATNE OFERTY", "Co widzi klient,<br>któremu wyślesz link",
         "W panelu: widoczność <b>„Prywatny”</b> → <b>Kopiuj link</b>.<br>Link wysyłasz klientowi: WhatsApp, SMS, mail.", 5500)

    # 1. link z kluczem
    page.goto(f"{BASE}?k={NEW}", wait_until="networkidle")
    deny_cookies(page)
    cap(page, "Klient otwiera link — widzi pełną stronę auta: zdjęcia, cenę, parametry.", 4500)
    for _ in range(6):
        page.mouse.wheel(0, 420)
        page.wait_for_timeout(700)
    cap(page, "Tej oferty nie ma w wyszukiwarce, na liście aut ani w Google. Zobaczy ją tylko ktoś z linkiem.", 4500)
    page.evaluate("window.scrollTo({top:0,behavior:'smooth'})")
    page.wait_for_timeout(1200)

    # 2. zamówienie
    btn = page.locator('a[href*="zamow/?listing_id=387505"]:visible').first
    btn.scroll_into_view_if_needed()
    cap(page, "Klient klika „Zamów” — jak przy każdej innej ofercie.", 3000)
    btn.click()
    page.wait_for_load_state("networkidle")
    deny_cookies(page)
    cap(page, "Kreator rezerwacji otwiera się normalnie.", 3000)
    form = page.locator('form[data-form="start"]')
    for name, val in [("first_name", "Jan"), ("last_name", "Testowy"), ("email", "test@example.com"), ("phone", "500 600 700")]:
        f = form.locator(f'input[name="{name}"]')
        if f.count():
            f.first.scroll_into_view_if_needed()
            f.first.type(val, delay=60)
    for name in ("privacy", "reg_terms"):
        c = form.locator(f'input[name="{name}"]')
        if c.count():
            c.first.evaluate("el => { el.checked = true; el.dispatchEvent(new Event('change', {bubbles:true})); }")
            page.wait_for_timeout(400)
    form.locator('button[type="submit"]').first.scroll_into_view_if_needed()
    cap(page, "Klient wysyła formularz — zamówienie trafia do panelu „Zamówienia” jak każde inne.<br><small>(tu nagranie bez wysyłki — to tylko pokaz)</small>", 5500)

    # 3. bez klucza / stary link — świeża przeglądarka, bez ciasteczek
    ctx2_page = page
    ctx2_page.context.clear_cookies()
    card(page, "BEZPIECZEŃSTWO", "Bez linku nikt tej oferty nie zobaczy", "Pokazujemy w przeglądarce kogoś, kto nie dostał linku.", 4000)
    page.goto(BASE, wait_until="networkidle")
    deny_cookies(page)
    cap(page, "Adres bez klucza (bez końcówki <b>?k=…</b>) — klient widzi tylko <b>„To ogłoszenie nie jest już dostępne”</b>.", 4500)
    page.goto(f"{BASE}?k={OLD}", wait_until="networkidle")
    cap(page, "Stary link po kliknięciu <b>„Wygeneruj nowy klucz”</b> — to samo: oferta niedostępna. Tak „odbierasz” link klientowi.", 5000)

    card(page, "PODSUMOWANIE", "Jak to działa",
         "1. Widoczność <b>„Prywatny”</b> + Aktualizuj<br>2. <b>Kopiuj link</b> → wyślij klientowi<br>"
         "3. Nowy klucz = stary link przestaje działać<br>4. Upublicznienie: widoczność <b>„Publiczny”</b><br><br>"
         "<span style='color:#f5a623'>Tylko auta dodane ręcznie lub duplikaty</span> — auta z automatycznego importu nadpisze synchronizacja.", 8000)

    vid = page.video
    ctx.close()
    shutil.move(vid.path(), OUT)
    b.close()
print("OK", OUT)
