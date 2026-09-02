/**
 * AsiaAuto Search — pasek filtrów poziomy (T-116 etap 3, przeprojektowanie).
 *
 * Stan filtrów żyje w formularzu; z niego powstaje URL (deep-link) i zapytania REST.
 * Wyniki i liczniki zależne przychodzą z dwóch tras (`search`, `search-counts`)
 * wołanych równolegle. Bez zależności zewnętrznych.
 *
 * @since v0.36.0
 */
(function () {
    'use strict';

    var root = document.querySelector('.aas');
    if (!root || typeof AA_SEARCH === 'undefined') return;

    var form    = root.querySelector('.aas__form');
    var grid    = root.querySelector('.aas__grid');
    var countEl = root.querySelector('.aas__count-num');
    var clearEl = root.querySelector('.aas__clear');
    var chipsEl = root.querySelector('.aas__chips');

    var page = parseInt(new URLSearchParams(location.search).get('strona') || '1', 10) || 1;
    var seq = 0, timer = null, zaslona = null;

    /**
     * Spacja co trzy cyfry, zawsze — jak `number_format($n, 0, ',', ' ')` w PHP.
     * `toLocaleString('pl-PL')` NIE grupuje liczb czterocyfrowych (CLDR
     * minimumGroupingDigits=2), więc SSR i JS pokazywałyby różne liczby.
     */
    function fmt(n) {
        return String(n || 0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    var COL2PARAM = {
        make: 'marka', serie: 'model', color: 'kolor', fuel: 'paliwo', body: 'nadwozie',
        drive: 'naped', transmission: 'skrzynia', upholstery: 'tapicerka',
        sunroof: 'szyberdach', year: 'rocznik'
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
                if (el && el.value !== '') out.ranges[k + '_' + side] = el.value;
            });
        });
        var flags = form.querySelectorAll('input[name="wyposazenie[]"]:checked');
        out.flags = Array.prototype.map.call(flags, function (b) { return b.value; });
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

    // ------------------------------------------------------------- pigułki

    /**
     * Podsumowanie wyboru czytamy z DOM popovera, nie z osobnej mapy etykiet —
     * dzięki temu nazwy nie są zduplikowane między PHP a JS i nie mogą się rozjechać.
     */
    function odswiezChipy() {
        root.querySelectorAll('.aas__chip').forEach(function (chip) {
            var pop = chip.querySelector('.aas__pop');
            if (!pop) return;
            var czesci = [];

            var zazn = pop.querySelectorAll('input[type="checkbox"]:checked');
            if (zazn.length === 1) {
                czesci.push(zazn[0].parentNode.querySelector('.aas__opt-label').textContent.trim());
            } else if (zazn.length > 1) {
                var pierwsza = zazn[0].parentNode.querySelector('.aas__opt-label').textContent.trim();
                czesci.push(pierwsza + ' +' + (zazn.length - 1));
            }

            pop.querySelectorAll('.aas__box--range').forEach(function (box) {
                var od = box.querySelector('[name$="_min"]'), doo = box.querySelector('[name$="_max"]');
                var a = od && od.value, b = doo && doo.value;
                if (!a && !b) return;
                var etykieta = box.querySelector('.aas__box-title');
                var nazwa = etykieta ? etykieta.childNodes[0].textContent.trim() : '';
                var zakres = (a && b) ? fmt(a) + '–' + fmt(b) : (a ? 'od ' + fmt(a) : 'do ' + fmt(b));
                czesci.push(czesci.length || pop.querySelectorAll('.aas__box--range').length > 1
                    ? nazwa + ' ' + zakres : zakres);
            });

            var val = chip.querySelector('.aas__chip-val');
            var tekst = czesci.slice(0, 2).join(' · ') + (czesci.length > 2 ? ' …' : '');
            if (val) val.textContent = tekst;
            chip.classList.toggle('is-active', czesci.length > 0);
        });

        // „Model" ma sens dopiero przy wybranej marce
        var chipModel = root.querySelector('.aas__chip[data-chip="serie"]');
        if (chipModel) {
            chipModel.hidden = form.querySelectorAll('input[name="marka[]"]:checked').length === 0;
        }
    }

    function setCounts(data) {
        var total = data.total || 0;
        if (countEl) countEl.textContent = fmt(total);

        if (data.oferta) {
            root.querySelectorAll('.aas__seg-n').forEach(function (el) {
                var n = data.oferta[el.dataset.oferta] || 0;
                el.textContent = fmt(n);
                var seg = el.closest('.aas__seg');
                seg.classList.toggle('is-empty', n === 0 && !seg.classList.contains('is-active'));
            });
        }

        AA_SEARCH.enums.forEach(function (col) {
            var box = form.querySelector('.aas__box[data-col="' + col + '"] .aas__opts');
            if (!box) return;
            var map    = (data.enum && data.enum[col]) || {};
            var labels = (data.labels && data.labels[col]) || {};
            var seen = {};
            box.querySelectorAll('.aas__opt').forEach(function (label) {
                var input = label.querySelector('input');
                if (!input) return;
                seen[input.value] = true;
                var n = map[input.value] || 0;
                var c = label.querySelector('.aas__opt-count');
                if (c) c.textContent = fmt(n);
                label.classList.toggle('aas__opt--empty', n === 0);
                label.hidden = (n === 0 && !input.checked);
            });
            Object.keys(map).forEach(function (slug) {
                if (seen[slug] || !map[slug]) return;
                box.appendChild(buildOption(param(col), slug, labels[slug] || slug, map[slug]));
            });
        });

        if (data.flags) {
            Object.keys(data.flags).forEach(function (flag) {
                var input = form.querySelector('input[name="wyposazenie[]"][value="' + flag + '"]');
                if (!input) return;
                var label = input.closest('.aas__opt');
                var n = data.flags[flag] || 0;
                var c = label && label.querySelector('.aas__opt-count');
                if (c) c.textContent = fmt(n);
                if (label) label.classList.toggle('aas__opt--empty', n === 0);
            });
        }
        odswiezChipy();
    }

    function buildOption(name, value, label, n) {
        var l = document.createElement('label');
        l.className = 'aas__opt';
        var i = document.createElement('input');
        i.type = 'checkbox'; i.name = name + '[]'; i.value = value;
        var s = document.createElement('span');
        s.className = 'aas__opt-label'; s.textContent = label;
        var c = document.createElement('span');
        c.className = 'aas__opt-count'; c.textContent = fmt(n);
        l.appendChild(i); l.appendChild(s); l.appendChild(c);
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

        var add = function (p, text, mod, current) {
            var li = document.createElement('li');
            var el = document.createElement(current ? 'span' : 'a');
            el.className = 'aas__page' + (mod ? ' aas__page--' + mod : '');
            el.textContent = text;
            if (current) el.setAttribute('aria-current', 'page');
            else { el.href = '?' + queryString(state(), false) + (p > 1 ? '&strona=' + p : ''); el.dataset.page = p; }
            li.appendChild(el); ul.appendChild(li);
        };
        var kropki = function () {
            var li = document.createElement('li');
            li.className = 'aas__ellipsis';
            li.setAttribute('aria-hidden', 'true');
            li.textContent = '…';
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

    function refresh(scrollUp) {
        var st = state();
        var qs = queryString(st, true);
        var mine = ++seq;

        grid.classList.add('is-loading');
        grid.setAttribute('aria-busy', 'true');

        if (clearEl) {
            var n = liczbaFiltrow(st);
            clearEl.hidden = n === 0;
        }
        history.replaceState({ q: qs }, '', qs ? '?' + qs : location.pathname);

        Promise.all([
            fetch(AA_SEARCH.rest + 'search?' + qs).then(function (r) { return r.json(); }),
            fetch(AA_SEARCH.rest + 'search-counts?' + qs).then(function (r) { return r.json(); })
        ]).then(function (res) {
            if (mine !== seq) return;
            var results = res[0], counts = res[1];
            grid.innerHTML = results.html || '';
            page = results.page || 1;
            setCounts(counts);
            renderPagination(results.page || 1, results.pages || 1);
            toggleEmpty(results.total === 0);
            if (scrollUp) {
                // `behavior: 'instant'` jawnie — motyw ustawia `scroll-behavior: smooth`
                // na <html>, więc animowane przewijanie zostałoby przerwane przez
                // zmianę wysokości dokumentu.
                var cel = Math.max(0, root.getBoundingClientRect().top + window.scrollY - 80);
                try { window.scrollTo({ top: cel, behavior: 'instant' }); }
                catch (e) { window.scrollTo(0, cel); }
            }
        }).catch(function () {
            /* sieć padła — zostawiamy poprzednie wyniki */
        }).finally(function () {
            if (mine !== seq) return;
            grid.classList.remove('is-loading');
            grid.setAttribute('aria-busy', 'false');
        });
    }

    function toggleEmpty(isEmpty) {
        var el = form.querySelector('.aas__empty');
        if (isEmpty && !el) {
            el = document.createElement('p');
            el.className = 'aas__empty';
            el.textContent = 'Żadna oferta nie spełnia tych kryteriów. Poluzuj filtry albo wyczyść je w całości.';
            grid.insertAdjacentElement('afterend', el);
        } else if (!isEmpty && el) {
            el.remove();
        }
    }

    var debounced = function () {
        clearTimeout(timer);
        timer = setTimeout(function () { page = 1; refresh(); }, 350);
    };

    // ------------------------------------------------------------ popovery

    function otwarty() { return root.querySelector('.aas__chip-btn[aria-expanded="true"]'); }

    function zamknijPop() {
        var btn = otwarty();
        if (!btn) return;
        btn.setAttribute('aria-expanded', 'false');
        document.getElementById(btn.getAttribute('aria-controls')).hidden = true;
        if (zaslona) { zaslona.remove(); zaslona = null; }
        document.body.style.overflow = '';
    }

    function otworzPop(btn) {
        zamknijPop();
        btn.setAttribute('aria-expanded', 'true');
        var pop = document.getElementById(btn.getAttribute('aria-controls'));
        pop.hidden = false;

        if (window.matchMedia('(max-width: 768px)').matches) {
            zaslona = document.createElement('div');
            zaslona.className = 'aas__zaslona';
            document.body.appendChild(zaslona);
            document.body.style.overflow = 'hidden';
            zaslona.addEventListener('click', zamknijPop);
        } else {
            // popover przy prawej krawędzi ekranu wychodzi poza widok — przypnij go do prawej
            pop.classList.remove('aas__pop--right');
            if (pop.getBoundingClientRect().right > window.innerWidth - 8) {
                pop.classList.add('aas__pop--right');
            }
        }
        var pierwszy = pop.querySelector('input, button');
        if (pierwszy) pierwszy.focus({ preventScroll: true });
    }

    root.addEventListener('click', function (e) {
        var btn = e.target.closest('.aas__chip-btn');
        if (btn) {
            e.preventDefault();
            btn.getAttribute('aria-expanded') === 'true' ? zamknijPop() : otworzPop(btn);
            return;
        }
        if (!e.target.closest('.aas__pop')) zamknijPop();
    });
    document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) zamknijPop();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var btn = otwarty();
        if (btn) { zamknijPop(); btn.focus(); }
    });

    // ------------------------------------------------------------ zdarzenia

    form.addEventListener('change', function (e) {
        var t = e.target;
        if (t.matches('input[type="checkbox"], input[name="oferta"], .aas__sort-select')) {
            page = 1;
            refresh();
            if (t.name === 'oferta') zamknijPop();
        }
    });
    form.addEventListener('input', function (e) {
        if (e.target.matches('input[type="number"]')) debounced();
    });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        zamknijPop();
        page = 1;
        refresh();
    });

    form.addEventListener('click', function (e) {
        var a = e.target.closest('.aas__page[data-page]');
        if (!a) return;
        e.preventDefault();
        page = parseInt(a.dataset.page, 10) || 1;
        refresh(true);
    });

    if (clearEl) clearEl.addEventListener('click', function () {
        form.querySelectorAll('input[type="checkbox"]').forEach(function (b) { b.checked = false; });
        form.querySelectorAll('input[type="number"]').forEach(function (i) { i.value = ''; });
        var wszystkie = form.querySelector('input[name="oferta"][value=""]');
        if (wszystkie) wszystkie.checked = true;
        var sort = form.querySelector('.aas__sort-select');
        if (sort) sort.value = 'date_desc';
        zamknijPop();
        page = 1;
        refresh();
    });

    window.addEventListener('popstate', function () { location.reload(); });

    odswiezChipy();
})();
