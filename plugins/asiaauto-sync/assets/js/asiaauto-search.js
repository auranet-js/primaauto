/**
 * AsiaAuto Search — układ z makiety I (T-116 domknięcie, 2026-09-03).
 *
 * Stan filtrów żyje w formularzu; z niego powstaje URL (deep-link) i zapytania REST
 * `search` + `search-counts`, wołane równolegle. Listy rozwijane, „Więcej filtrów",
 * kafle „Oferty" i paginacja mają obsługę tutaj. Bez zależności zewnętrznych.
 *
 * @since v0.37.0
 */
(function () {
    'use strict';

    var root = document.querySelector('form.aas');
    if (!root || typeof AA_SEARCH === 'undefined') return;

    var form    = root;
    var grid    = root.querySelector('.aas__grid');
    var toolbar = root.querySelector('.aas__toolbar');

    var page = parseInt(new URLSearchParams(location.search).get('strona') || '1', 10) || 1;
    var seq = 0, timer = null, zaslona = null;
    // T-252: otwarcie arkusza dokłada wpis do historii, żeby gest wstecz na Androidzie
    // zamykał arkusz, a nie wychodził z całej strony (wcześniej popstate robił reload).
    var popWHistorii = false, ignorujPop = false, ostatniTotal = null, dotykY = null;

    /** Spacja co trzy cyfry, zawsze — `toLocaleString('pl-PL')` nie grupuje liczb czterocyfrowych. */
    function fmt(n) {
        var s = String(n === undefined || n === null ? 0 : n);
        var cz = s.split('.');
        cz[0] = cz[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        return cz.join(',');
    }
    function slowoOfert(n) {
        n = Math.abs(n | 0);
        if (n === 1) return 'ofertę';
        var d = n % 10, s = n % 100;
        return (d >= 2 && d <= 4 && !(s >= 12 && s <= 14)) ? 'oferty' : 'ofert';
    }
    /** „100 000" / „5,0" → „100000" / „5.0"; puste, gdy to nie liczba dodatnia. */
    function liczba(v) {
        v = String(v || '').replace(/[\s ]/g, '').replace(',', '.');
        return /^\d+(\.\d+)?$/.test(v) && parseFloat(v) > 0 ? v : '';
    }

    var COL2PARAM = {
        make: 'marka', serie: 'model', color: 'kolor', fuel: 'paliwo', body: 'nadwozie',
        drive: 'naped', transmission: 'skrzynia', upholstery: 'tapicerka',
        sunroof: 'szyberdach', year: 'rocznik',
        interior_color: 'kolor_wnetrza', suspension: 'zawieszenie', sound_brand: 'audio', seats: 'miejsca'
    };
    function param(col) { return COL2PARAM[col] || col; }

    // ---------------------------------------------------------------- stan

    function state() {
        var out = { enum: {}, ranges: {}, flags: [], oferta: '' };
        AA_SEARCH.enums.forEach(function (col) {
            var name = param(col);
            var boxes = form.querySelectorAll('input[name="' + name + '[]"]:checked');
            if (boxes.length) out.enum[name] = Array.prototype.map.call(boxes, function (b) { return b.value; });
        });
        AA_SEARCH.ranges.forEach(function (k) {
            ['min', 'max'].forEach(function (side) {
                var el = form.querySelector('[name="' + k + '_' + side + '"]');
                var v = el ? liczba(el.value) : '';
                if (v !== '') out.ranges[k + '_' + side] = v;
            });
        });
        out.flags = Array.prototype.map.call(form.querySelectorAll('input[name="wyposazenie[]"]:checked'), function (b) { return b.value; });
        var of = form.querySelector('input[name="oferta"]:checked');
        out.oferta = of ? of.value : '';
        var sort = form.querySelector('.aas__sort-select');
        out.sort = sort ? sort.value : 'date_desc';
        return out;
    }

    function queryString(st, withPage) {
        var q = new URLSearchParams();
        if (st.oferta) q.set('oferta', st.oferta);
        Object.keys(st.enum).forEach(function (name) { q.set(name, st.enum[name].join(',')); });
        Object.keys(st.ranges).forEach(function (k) { q.set(k, st.ranges[k]); });
        if (st.flags.length) q.set('wyposazenie', st.flags.join(','));
        if (st.sort && st.sort !== 'date_desc') q.set('sort', st.sort);
        if (withPage && page > 1) q.set('strona', String(page));
        return q.toString();
    }

    function liczbaFiltrow(st) {
        var n = st.flags.length + Object.keys(st.ranges).length + (st.oferta ? 1 : 0);
        Object.keys(st.enum).forEach(function (k) { n += st.enum[k].length; });
        return n;
    }

    // ------------------------------------------------------ teksty i przyciski

    function ustawTotal(total) {
        root.querySelectorAll('.aas__total').forEach(function (el) { el.textContent = fmt(total); });
        root.querySelectorAll('.aas__total-slowo').forEach(function (el) { el.textContent = slowoOfert(total); });
        ostatniTotal = total;
        ustawPrzyciskArkusza(total);
    }

    /** Stopka arkusza na telefonie: „Pokaż 1 706 ofert". Liczba pochodzi z tego samego
        `search-counts`, który odświeża pasek narzędzi — arkusz go zasłania, więc bez tego
        użytkownik nie widzi skutku zaznaczenia (T-252). */
    function ustawPrzyciskArkusza(total) {
        var sel = otwarta();
        if (!sel) return;
        var pop = document.getElementById(sel.getAttribute('aria-controls'));
        var ok = pop && pop.querySelector('.aas__pop-ok');
        if (ok) ok.textContent = 'Pokaż ' + fmt(total) + ' ' + slowoOfert(total);
        ustawLicznikArkusza(pop);
    }

    /** Przed pierwszym `search-counts` liczba stoi w pasku narzędzi z SSR — bierzemy ją stamtąd,
        żeby przycisk nigdy nie pokazał bezużytecznego „Pokaż wyniki". */
    function totalZeStrony() {
        if (ostatniTotal !== null) return;
        var zDom = (root.querySelector('.aas__total') || {}).textContent || '';
        var n = parseInt(zDom.replace(/[^0-9]/g, ''), 10);
        ostatniTotal = isNaN(n) ? null : n;
    }

    function ustawLicznikArkusza(pop) {
        var el = pop && pop.querySelector('.aas__pop-ile');
        if (!el) return;
        var n = pop.querySelectorAll('.aas__opts input:checked, .aas__chip input:checked').length;
        el.textContent = n;
        el.hidden = n === 0;
    }

    /** Podsumowania czytamy z DOM (etykiety opcji), nie z osobnej mapy — nie mogą się rozjechać z PHP. */
    function odswiez() {
        var st = state();
        var bezMarki = !form.querySelector('input[name="marka[]"]:checked');

        // Wyposażenie ma na telefonie własne pole z arkuszem — jego podsumowanie liczymy tak samo
        // jak w enumach, tylko wiersze noszą klasę `.aas__chip` (na desktopie to pastylki).
        root.querySelectorAll('.aas__pole--flags').forEach(function (f) {
            var sel = f.querySelector('.aas__sel'), txt = f.querySelector('.aas__sel-text');
            if (!sel || !txt) return;
            var zazn = f.querySelectorAll('.aas__chip input:checked');
            if (!zazn.length) txt.textContent = txt.dataset.pusty;
            else {
                var pierwsza = zazn[0].parentNode.querySelector('.aas__chip-label').textContent.trim();
                txt.textContent = zazn.length > 1 ? pierwsza + ' +' + (zazn.length - 1) : pierwsza;
            }
            sel.classList.toggle('is-active', zazn.length > 0);
        });

        root.querySelectorAll('.aas__pole--enum').forEach(function (f) {
            var sel = f.querySelector('.aas__sel'), txt = f.querySelector('.aas__sel-text'), kropka = f.querySelector('.aas__sel > .aas__kropka');
            var zazn = f.querySelectorAll('.aas__opt input:checked');
            if (f.dataset.poMarce && bezMarki) {
                zazn.forEach(function (i) { i.checked = false; });
                zazn = [];
                sel.disabled = true;
                txt.textContent = txt.dataset.bezMarki;
            } else {
                if (f.dataset.poMarce) sel.disabled = false;
                if (!zazn.length) txt.textContent = txt.dataset.pusty;
                else {
                    var pierwsza = zazn[0].parentNode.querySelector('.aas__opt-label').textContent.trim();
                    txt.textContent = zazn.length > 1 ? pierwsza + ' +' + (zazn.length - 1) : pierwsza;
                }
            }
            sel.classList.toggle('is-active', zazn.length > 0);
            if (kropka) {
                var k = zazn.length ? zazn[0].parentNode.querySelector('.aas__kropka') : null;
                kropka.hidden = !k;
                if (k) kropka.style.background = k.style.background;
            }
        });
        root.querySelectorAll('.aas__pole--range').forEach(function (f) {
            var a = f.querySelector('[name$="_min"]'), b = f.querySelector('[name$="_max"]');
            f.classList.toggle('is-active', !!(liczba(a && a.value) || liczba(b && b.value)));
        });
        root.querySelectorAll('.aas__chip').forEach(function (l) {
            var i = l.querySelector('input');
            l.classList.toggle('is-active', !!(i && i.checked));
        });
        root.querySelectorAll('.aas__kafel').forEach(function (l) {
            var i = l.querySelector('input');
            l.classList.toggle('is-active', !!(i && i.checked));
        });

        // odznaka na „Więcej filtrów" = aktywne pola w zwiniętej sekcji
        var wiecej = root.querySelector('.aas__wiecej');
        var sekW = root.querySelector('.aas__sek--wiecej');
        if (wiecej && sekW) {
            var w = sekW.querySelectorAll('input[type="checkbox"]:checked').length;
            sekW.querySelectorAll('.aas__inp input').forEach(function (i) { if (liczba(i.value)) w++; });
            var nEl = wiecej.querySelector('.aas__wiecej-n');
            nEl.textContent = '(' + w + ')';
            nEl.hidden = w === 0;
        }

        // odznaki aktywnych filtrów na nagłówkach sekcji (telefon: widać na zwiniętej sekcji)
        root.querySelectorAll('.aas__sek').forEach(function (sek) {
            var badge = sek.querySelector('.aas__sek-n');
            if (!badge) return;
            var k = sek.querySelectorAll('.aas__sek-body input[type="checkbox"]:checked').length;
            sek.querySelectorAll('.aas__sek-body .aas__inp input').forEach(function (i) { if (liczba(i.value)) k++; });
            badge.textContent = k;
            badge.hidden = k === 0;
        });

        var n = liczbaFiltrow(st);
        root.querySelectorAll('.aas__n-filtrow').forEach(function (el) { el.textContent = n; });
        root.querySelectorAll('.aas__clear').forEach(function (el) { el.hidden = n === 0; });
    }

    function setCounts(data) {
        ustawTotal(data.total || 0);

        if (data.oferta) {
            root.querySelectorAll('.aas__kafel-n[data-oferta]').forEach(function (el) {
                var n = data.oferta[el.dataset.oferta] || 0;
                var kafel = el.closest('.aas__kafel');
                var radio = kafel.querySelector('input');
                el.textContent = fmt(n);
                kafel.classList.toggle('is-empty', n === 0 && !radio.checked);
                radio.disabled = n === 0 && !radio.checked;
            });
        }

        AA_SEARCH.enums.forEach(function (col) {
            var f = root.querySelector('.aas__pole--enum[data-col="' + col + '"]');
            if (!f) return;
            var box = f.querySelector('.aas__opts');
            var map    = (data.enum && data.enum[col]) || {};
            var labels = (data.labels && data.labels[col]) || {};
            var seen = {};
            var ukrywaj = !!f.dataset.ukrywaj;
            box.querySelectorAll('.aas__opt').forEach(function (label) {
                var input = label.querySelector('input');
                seen[input.value] = true;
                var n = map[input.value] || 0;
                var puste = (n === 0 && !input.checked);
                label.querySelector('.aas__opt-count').textContent = fmt(n);
                // reguła UX: zero widoczne, szare i niewybieralne; marka/model ukrywają zera (długie listy)
                label.classList.toggle('is-empty', puste);
                input.disabled = puste;
                label.hidden = puste && ukrywaj;
            });
            var nowe = [];
            Object.keys(map).forEach(function (slug) {
                if (seen[slug] || !map[slug]) return;
                nowe.push(buildOption(param(col), slug, labels[slug] || slug, map[slug], ''));
            });
            // modele dorysowane po wyborze marki wchodzą na koniec listy — przy sortowaniu
            // alfabetycznym trzeba przełożyć CAŁĄ listę, nie tylko doklejone
            if (nowe.length) {
                nowe.sort(function (a, b) {
                    if (col === 'year') return parseInt(b.dataset.value, 10) - parseInt(a.dataset.value, 10);
                    if (col === 'seats') return parseInt(a.dataset.value, 10) - parseInt(b.dataset.value, 10);
                    // marka i model alfabetycznie — ta sama reguła co w PHP (renderEnum)
                    if (col === 'make' || col === 'serie') return a.textContent.localeCompare(b.textContent, 'pl');
                    return (parseInt(b.dataset.n, 10) - parseInt(a.dataset.n, 10)) || a.textContent.localeCompare(b.textContent, 'pl');
                });
                nowe.forEach(function (el) { box.appendChild(el); });
                if (col === 'make' || col === 'serie') {
                    var wszystkie = [].slice.call(box.querySelectorAll('.aas__opt'));
                    wszystkie.sort(function (a, b) {
                        return a.querySelector('.aas__opt-label').textContent
                            .localeCompare(b.querySelector('.aas__opt-label').textContent, 'pl');
                    });
                    wszystkie.forEach(function (el) { box.appendChild(el); });
                }
            }
        });

        if (data.bounds) { ostatnieBounds = data.bounds; odswiezZakresy(); }

        if (data.flags) {
            Object.keys(data.flags).forEach(function (flag) {
                var input = form.querySelector('input[name="wyposazenie[]"][value="' + flag + '"]');
                if (!input) return;
                var chip = input.closest('.aas__chip');
                var n = data.flags[flag] || 0;
                var puste = n === 0 && !input.checked;
                chip.querySelector('.aas__chip-n').textContent = fmt(n);
                chip.classList.toggle('is-empty', puste);
                input.disabled = puste;
            });
        }
        odswiez();
    }

    var ostatnieBounds = null;

    /** Liczba do podpowiedzi jak `fmtLiczba()` w PHP: spacja co trzy cyfry (rocznik bez), metry z jednym miejscem. */
    function fmtPodpowiedz(v, f) {
        if (v === null || v === undefined) return '';
        var mult = parseInt(f.dataset.mult, 10) || 1;
        if (mult !== 1) return (Math.round(v / mult * 10) / 10).toFixed(1).replace('.', ',');
        var s = String(v);
        if (s.indexOf('.') !== -1) s = String(Math.round(v * 10) / 10).replace('.', ',');
        return f.dataset.grupuj === '0' ? s : fmt(s);
    }

    /** Podpowiedzi w polach = zakres PO zawężeniu; wartość poza zakresem dostaje „Brak ofert w tym zakresie". */
    function odswiezZakresy() {
        if (!ostatnieBounds) return;
        root.querySelectorAll('.aas__pole--range').forEach(function (f) {
            var b = ostatnieBounds[f.dataset.range];
            if (!b) return;
            var mult = parseInt(f.dataset.mult, 10) || 1;
            var poza = false;
            ['min', 'max'].forEach(function (side) {
                var el = f.querySelector('[name$="_' + side + '"]');
                if (!el) return;
                el.placeholder = fmtPodpowiedz(b[side], f);
                var v = liczba(el.value);
                if (v === '' || b.min === null) return;
                var x = parseFloat(v) * mult;
                if (side === 'min' && x > b.max) poza = true;
                if (side === 'max' && x < b.min) poza = true;
            });
            var hint = f.querySelector('.aas__hint');
            if (hint) hint.hidden = !poza;
        });
    }

    /** Ten sam markup co `renderOpcja()` w PHP. */
    function buildOption(name, value, label, n, kropka) {
        var l = document.createElement('label');
        l.className = 'aas__opt';
        l.dataset.value = value; l.dataset.n = n;
        var i = document.createElement('input');
        i.type = 'checkbox'; i.name = name + '[]'; i.value = value;
        var k = document.createElement('span');
        k.className = 'aas__check'; k.setAttribute('aria-hidden', 'true');
        l.appendChild(i); l.appendChild(k);
        if (kropka) { var d = document.createElement('i'); d.className = 'aas__kropka'; d.style.background = kropka; d.setAttribute('aria-hidden', 'true'); l.appendChild(d); }
        var s = document.createElement('span');
        s.className = 'aas__opt-label'; s.textContent = label;
        var c = document.createElement('span');
        c.className = 'aas__opt-count'; c.textContent = fmt(n);
        l.appendChild(s); l.appendChild(c);
        return l;
    }

    // ----------------------------------------------------------- paginacja

    function renderPagination(cur, pages) {
        var old = form.querySelector('.aas__pagination');
        if (old) old.remove();
        if (pages < 2) return;
        var nav = document.createElement('nav');
        nav.className = 'aas__pagination';
        nav.setAttribute('aria-label', 'Strony wyników');
        var ul = document.createElement('ul');
        ul.className = 'aas__pages';
        var qs = queryString(state(), false);
        var add = function (p, text, mod, current) {
            var li = document.createElement('li');
            var el = document.createElement(current ? 'span' : 'a');
            el.className = 'aas__page' + (mod ? ' aas__page--' + mod : '');
            el.textContent = text;
            if (current) el.setAttribute('aria-current', 'page');
            else { el.href = '?' + qs + (p > 1 ? (qs ? '&' : '') + 'strona=' + p : ''); el.dataset.page = p; }
            li.appendChild(el); ul.appendChild(li);
        };
        var kropki = function () {
            var li = document.createElement('li');
            li.className = 'aas__ellipsis'; li.setAttribute('aria-hidden', 'true'); li.textContent = '…';
            ul.appendChild(li);
        };
        if (cur > 1) add(cur - 1, 'Poprzednia', 'prev');
        var from = Math.max(1, cur - 2), to = Math.min(pages, cur + 2);
        if (from > 1) { add(1, '1'); if (from > 2) kropki(); }
        for (var i = from; i <= to; i++) add(i, String(i), i === cur ? 'current' : '', i === cur);
        if (to < pages) { if (to < pages - 1) kropki(); add(pages, String(pages)); }
        if (cur < pages) add(cur + 1, 'Następna', 'next');
        nav.appendChild(ul);
        form.appendChild(nav);
    }

    // -------------------------------------------------------------- fetch

    function przewinDoWynikow() {
        // `behavior: 'instant'` jawnie — motyw ma `scroll-behavior: smooth` na <html>
        var cel = Math.max(0, toolbar.getBoundingClientRect().top + window.scrollY - 90);
        try { window.scrollTo({ top: cel, behavior: 'instant' }); } catch (e) { window.scrollTo(0, cel); }
    }

    // Pamięć odpowiedzi w tej sesji strony: powrót do wcześniejszego stanu filtrów (odznacz/zaznacz)
    // nie idzie do sieci. Klucz = pełny query string, limit 60 wpisów.
    var pamiec = {}, pamiecKolejka = [];
    function zapamietaj(qs, res) {
        if (pamiec[qs]) return;
        pamiec[qs] = res; pamiecKolejka.push(qs);
        if (pamiecKolejka.length > 60) delete pamiec[pamiecKolejka.shift()];
    }

    function refresh(scrollUp) {
        var st = state();
        var qs = queryString(st, true);
        var mine = ++seq;
        grid.classList.add('is-loading');
        grid.setAttribute('aria-busy', 'true');
        history.replaceState({ q: qs }, '', qs ? '?' + qs : location.pathname);

        (pamiec[qs] ? Promise.resolve(pamiec[qs]) : Promise.all([
            fetch(AA_SEARCH.rest + 'search?' + qs).then(function (r) { return r.json(); }),
            fetch(AA_SEARCH.rest + 'search-counts?' + qs).then(function (r) { return r.json(); })
        ]).then(function (res) { zapamietaj(qs, res); return res; })).then(function (res) {
            if (mine !== seq) return;
            var results = res[0], counts = res[1];
            ostatnieCounts = counts;
            grid.innerHTML = results.html || '';
            page = results.page || 1;
            setCounts(counts);
            renderPagination(results.page || 1, results.pages || 1);
            toggleEmpty(results.total === 0);
            if (scrollUp) przewinDoWynikow();
        }).catch(function () {
            /* sieć padła — zostawiamy poprzednie wyniki */
        }).finally(function () {
            if (mine !== seq) return;
            grid.classList.remove('is-loading');
            grid.setAttribute('aria-busy', 'false');
        });
    }

    var ostatnieCounts = null;

    /** Aktywne filtry jako lista {etykieta, zdejmij()} — do pustego stanu. */
    /** „blokuje" = serwer policzył, że zdjęcie tego filtra przywraca wyniki (`blokady` w search-counts). */
    function aktywneFiltry() {
        var lista = [];
        var blok = (ostatnieCounts && ostatnieCounts.blokady) || [];
        var RANGE2COL = { cena: 'price', przebieg: 'mileage', rok: 'year', moc: 'power_km', zasieg: 'range_cltc', bateria: 'battery_kwh',
                          miejsca: 'seats', felgi: 'rim_in', przysp: 'accel_s', dlugosc: 'length_mm', dmc: 'gvw_kg', zasieg_calk: 'range_total' };
        root.querySelectorAll('.aas__pole--enum').forEach(function (f) {
            var col = f.dataset.col;
            f.querySelectorAll('.aas__opt input:checked').forEach(function (i) {
                var nazwa = i.parentNode.querySelector('.aas__opt-label').textContent.trim();
                lista.push({ tekst: nazwa, blokuje: blok.indexOf('enum:' + col) !== -1, zdejmij: function () { i.checked = false; } });
            });
        });
        root.querySelectorAll('.aas__pole--range').forEach(function (f) {
            var lab = f.querySelector('.aas__label').textContent.trim();
            var col = RANGE2COL[f.dataset.range] || f.dataset.range;
            ['min', 'max'].forEach(function (side) {
                var el = f.querySelector('[name$="_' + side + '"]');
                if (!el || !liczba(el.value)) return;
                lista.push({ tekst: lab + ' ' + el.value.trim(), blokuje: blok.indexOf('range:' + col) !== -1, zdejmij: function () { el.value = ''; } });
            });
        });
        root.querySelectorAll('input[name="wyposazenie[]"]:checked').forEach(function (i) {
            lista.push({ tekst: i.parentNode.querySelector('.aas__chip-label').textContent.trim(), blokuje: blok.indexOf('flag:' + i.value) !== -1, zdejmij: function () { i.checked = false; } });
        });
        var of = form.querySelector('input[name="oferta"]:checked');
        if (of && of.value) {
            var kafel = of.closest('.aas__kafel');
            lista.push({ tekst: kafel.querySelector('.aas__kafel-l').textContent.trim(), blokuje: blok.indexOf('oferta') !== -1, zdejmij: function () {
                var w = form.querySelector('input[name="oferta"][value=""]'); if (w) w.checked = true; } });
        }
        return lista;
    }

    function toggleEmpty(isEmpty) {
        var el = form.querySelector('.aas__empty');
        if (el) el.remove();
        if (!isEmpty) return;
        el = document.createElement('div');
        el.className = 'aas__empty';
        var p = document.createElement('p');
        p.textContent = 'Żadna oferta nie spełnia wszystkich kryteriów naraz. Zdejmij jeden z filtrów:';
        el.appendChild(p);
        var ul = document.createElement('div');
        ul.className = 'aas__zdejmij';
        aktywneFiltry().forEach(function (fl) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'aas__zdejmij-btn' + (fl.blokuje ? ' is-blokuje' : '');
            b.textContent = fl.tekst + ' ×';
            if (fl.blokuje) b.title = 'Zdjęcie tego filtra przywraca wyniki';
            b.addEventListener('click', function () { fl.zdejmij(); page = 1; odswiez(); refresh(); });
            ul.appendChild(b);
        });
        el.appendChild(ul);
        grid.insertAdjacentElement('afterend', el);
    }

    var debounced = function () {
        clearTimeout(timer);
        timer = setTimeout(function () { page = 1; refresh(); }, 350);
    };

    // ------------------------------------------------------- listy rozwijane

    function otwarta() { return root.querySelector('.aas__sel[aria-expanded="true"]'); }

    /** Publiczne zamknięcie: UI znika NATYCHMIAST, a wpis z historii zdejmujemy w tle.
        `history.back()` jest asynchroniczne — gdyby zamknięcie czekało na `popstate`,
        arkusz zostawałby widoczny przez jeden tick (i tak właśnie oblewał test Escape). */
    function zamknijListy() {
        if (!otwarta()) return;
        zamknijListyTeraz();
        if (popWHistorii) {
            popWHistorii = false;
            ignorujPop = true;
            setTimeout(function () { ignorujPop = false; }, 500);   // bezpiecznik, gdyby popstate nie przyszedł
            history.back();
        }
    }

    /** Po `history.back()` adres wraca do stanu sprzed otwarcia arkusza — filtry zaznaczone
        w środku muszą do niego wrócić, inaczej odświeżenie strony je gubi. */
    function przywrocUrl() {
        var qs = queryString(state(), true);
        history.replaceState({ q: qs }, '', qs ? '?' + qs : location.pathname);
    }

    function zamknijListyTeraz() {
        var sel = otwarta();
        if (!sel) return;
        sel.setAttribute('aria-expanded', 'false');
        var pop = document.getElementById(sel.getAttribute('aria-controls'));
        pop.hidden = true;
        pop.classList.remove('is-dluga');
        if (zaslona) { zaslona.remove(); zaslona = null; }
        document.body.style.overflow = '';
        dotykY = null;
    }

    function otworzListe(sel) {
        zamknijListyTeraz();               // poprzednia lista znika bez ruszania historii
        sel.setAttribute('aria-expanded', 'true');
        var pop = document.getElementById(sel.getAttribute('aria-controls'));
        pop.hidden = false;
        if (window.matchMedia('(max-width: 768px)').matches) {
            // 11 pozycji mieści się w arkuszu 78vh; dłuższe listy (marka 40, model do 44)
            // dostają pełną wysokość, bo i tak się przewijają — T-252 §2.
            pop.classList.toggle('is-dluga', (+pop.dataset.opcji || 0) > 11);
            ustawLicznikArkusza(pop);
            totalZeStrony();
            if (ostatniTotal !== null) ustawPrzyciskArkusza(ostatniTotal);
            if (!popWHistorii) { history.pushState({ q: (history.state || {}).q, pop: 1 }, ''); popWHistorii = true; }
            zaslona = document.createElement('div');
            zaslona.className = 'aas__zaslona';
            document.body.appendChild(zaslona);
            document.body.style.overflow = 'hidden';
            zaslona.addEventListener('click', zamknijListy);
        } else {
            pop.classList.remove('aas__pop--right');
            if (pop.getBoundingClientRect().right > window.innerWidth - 8) pop.classList.add('aas__pop--right');
        }
        var szukaj = pop.querySelector('.aas__szukaj input');
        if (szukaj) { szukaj.value = ''; filtrujOpcje(szukaj); }
        // fokus tylko na desktopie: na dotyku automatyczny fokus w polu wywołuje klawiaturę,
        // która wypycha arkusz od dołu i przewija stronę (iOS) — użytkownik sam stuknie w szukajkę
        if (!window.matchMedia('(max-width: 768px)').matches) {
            var pierwszy = szukaj || pop.querySelector('input');
            if (pierwszy) pierwszy.focus({ preventScroll: true });
        }
    }

    /** Szukajka w liście: `is-hidden` dla braku dopasowania; `hidden` z liczników zostaje osobno. */
    function filtrujOpcje(input) {
        var q = input.value.trim().toLowerCase();
        // `.aas__chip` to opcje wyposażenia — ten sam arkusz, inna klasa wiersza
        input.closest('.aas__pop').querySelectorAll('.aas__opt, .aas__chip').forEach(function (opt) {
            var name = opt.querySelector('.aas__opt-label, .aas__chip-label');
            opt.classList.toggle('is-hidden', !!q && name.textContent.toLowerCase().indexOf(q) === -1);
        });
    }

    // ------------------------------------------------------------ zdarzenia

    root.addEventListener('click', function (e) {
        var sel = e.target.closest('.aas__sel');
        if (sel) {
            e.preventDefault();
            if (sel.disabled) return;
            sel.getAttribute('aria-expanded') === 'true' ? zamknijListy() : otworzListe(sel);
            return;
        }
        // stopka i „×" arkusza (telefon) — obie drogi wyjścia idą przez tę samą ścieżkę
        if (e.target.closest('.aas__pop-x') || e.target.closest('.aas__pop-ok')) { zamknijListy(); return; }


        if (!e.target.closest('.aas__pop')) zamknijListy();

        var wiecej = e.target.closest('.aas__wiecej');
        if (wiecej) {
            var sek = document.getElementById(wiecej.getAttribute('aria-controls'));
            var otworz = sek.hidden;
            sek.hidden = !otworz;
            wiecej.setAttribute('aria-expanded', otworz ? 'true' : 'false');
            return;
        }
        if (e.target.closest('.aas__pokaz')) { przewinDoWynikow(); return; }
        var sekBtn = e.target.closest('.aas__sek-btn');
        if (sekBtn) {
            // zwijanie tylko na telefonie; sekcja „Oferty" nie ma ciała do zwinięcia
            var sek = sekBtn.closest('.aas__sek');
            if (!window.matchMedia('(max-width: 768px)').matches || !sek.querySelector('.aas__sek-body')) return;
            var zwin = !sek.classList.contains('is-zwinieta');
            sek.classList.toggle('is-zwinieta', zwin);
            sekBtn.setAttribute('aria-expanded', zwin ? 'false' : 'true');
            return;
        }
        var chipsWiecej = e.target.closest('.aas__chips-wiecej');
        if (chipsWiecej) {
            var chips = chipsWiecej.parentNode.querySelector('.aas__chips');
            var otw = !chips.classList.contains('is-open');
            chips.classList.toggle('is-open', otw);
            chipsWiecej.setAttribute('aria-expanded', otw ? 'true' : 'false');
            chipsWiecej.firstChild.textContent = otw ? 'Mniej wyposażenia ' : 'Więcej wyposażenia ';
            return;
        }

        var a = e.target.closest('.aas__page[data-page]');
        if (a) {
            e.preventDefault();
            page = parseInt(a.dataset.page, 10) || 1;
            refresh(true);
            return;
        }
        if (e.target.closest('.aas__clear')) {
            form.querySelectorAll('input[type="checkbox"]').forEach(function (b) { b.checked = false; });
            form.querySelectorAll('.aas__inp input').forEach(function (i) { i.value = ''; });
            var wszystkie = form.querySelector('input[name="oferta"][value=""]');
            if (wszystkie) wszystkie.checked = true;
            var sort = form.querySelector('.aas__sort-select');
            if (sort) sort.value = 'date_desc';
            zamknijListy();
            page = 1;
            odswiez();
            refresh();
        }
    });
    // kafel „Oferty": drugi klik w aktywny kafel = wszystkie (radio nie umie się odznaczyć samo)
    root.addEventListener('mousedown', function (e) {
        var kafel = e.target.closest('.aas__kafel');
        if (!kafel) return;
        var input = kafel.querySelector('input');
        if (input.checked) {
            e.preventDefault();
            var wszystkie = form.querySelector('input[name="oferta"][value=""]');
            if (wszystkie) { wszystkie.checked = true; page = 1; odswiez(); refresh(); }
        }
    });
    document.addEventListener('click', function (e) { if (!root.contains(e.target)) zamknijListy(); });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var sel = otwarta();
        if (sel) { zamknijListy(); sel.focus(); }
    });

    form.addEventListener('change', function (e) {
        var t = e.target;
        if (t.matches('input[type="checkbox"], input[name="oferta"], .aas__sort-select')) {
            page = 1;
            odswiez();
            refresh();
        }
    });
    form.addEventListener('input', function (e) {
        if (e.target.matches('.aas__inp input')) { odswiez(); odswiezZakresy(); debounced(); }
        else if (e.target.matches('.aas__szukaj input')) filtrujOpcje(e.target);
    });

    // Licznik w nagłówku arkusza ma reagować od razu — `search-counts` wraca dopiero po 350 ms.
    root.addEventListener('change', function (e) {
        var pop = e.target.closest('.aas__pop');
        if (pop) ustawLicznikArkusza(pop);
    });

    // Zsuwanie arkusza palcem w dół. Tylko od góry listy, żeby nie kolidowało z przewijaniem opcji.
    root.addEventListener('touchstart', function (e) {
        var pop = e.target.closest('.aas__pop');
        dotykY = (pop && !pop.hidden) ? e.touches[0].clientY : null;
    }, { passive: true });
    root.addEventListener('touchmove', function (e) {
        if (dotykY === null) return;
        var sel = otwarta();
        if (!sel) { dotykY = null; return; }
        var pop = document.getElementById(sel.getAttribute('aria-controls'));
        var opts = pop.querySelector('.aas__opts');
        if (opts && opts.scrollTop > 0) { dotykY = null; return; }
        if (e.touches[0].clientY - dotykY > 70) { dotykY = null; zamknijListy(); }
    }, { passive: true });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        zamknijListy();
        page = 1;
        refresh();
    });
    window.addEventListener('popstate', function () {
        if (ignorujPop) { ignorujPop = false; przywrocUrl(); return; }   // to nasz własny back()
        if (popWHistorii) {                                             // systemowe „wstecz" przy otwartym arkuszu
            popWHistorii = false;
            zamknijListyTeraz();
            przywrocUrl();
            return;
        }
        location.reload();
    });

    // Desktop: wszystkie sekcje rozwinięte niezależnie od stanu z SSR (zwijanie to zachowanie telefonu)
    var mqSek = window.matchMedia('(max-width: 768px)');
    var rozwinNaDesktop = function () {
        if (mqSek.matches) return;
        root.querySelectorAll('.aas__sek.is-zwinieta').forEach(function (sek) {
            sek.classList.remove('is-zwinieta');
            var btn = sek.querySelector('.aas__sek-btn');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        });
    };
    rozwinNaDesktop();
    mqSek.addEventListener('change', rozwinNaDesktop);

    // Telefon: przyklejony pasek „N ofert · Pokaż wyniki" widoczny, dopóki pasek wyników
    // nie wjedzie w widok — filtry mają na telefonie ~1 500 px, wyniki są pod nimi.
    var pasekDol = root.querySelector('.aas__pasek-dol');
    if (pasekDol && 'IntersectionObserver' in window) {
        var mq = window.matchMedia('(max-width: 768px)');
        var wynikiWidoczne = false;
        var pokazPasek = function () {
            pasekDol.hidden = !(mq.matches && !wynikiWidoczne && window.scrollY > 120);
        };
        new IntersectionObserver(function (en) {
            wynikiWidoczne = en[0].isIntersecting || en[0].boundingClientRect.top < 0;
            pokazPasek();
        }, { rootMargin: '0px 0px -40% 0px' }).observe(toolbar);
        window.addEventListener('scroll', pokazPasek, { passive: true });
        mq.addEventListener('change', pokazPasek);
    }

    odswiez();
})();
