/**
 * AsiaAuto Search — wyszukiwarka zaawansowana (T-116 etap 3).
 *
 * Stan filtrów żyje w formularzu; z niego powstaje URL (deep-link) i zapytania REST.
 * Wyniki i liczniki zależne przychodzą z dwóch tras (`search`, `search-counts`)
 * wołanych równolegle. Bez zależności zewnętrznych.
 *
 * @since v0.35.0
 */
(function () {
    'use strict';

    var root = document.querySelector('.aas');
    if (!root || typeof AA_SEARCH === 'undefined') return;

    var form    = root.querySelector('.aas__form');
    var grid    = root.querySelector('.aas__grid');
    var panel   = root.querySelector('.aas__panel');
    var countEl = root.querySelector('.aas__count-num');
    var applyEl = root.querySelector('.aas__apply-count');
    var badgeEl = root.querySelector('.aas__badge');
    var pagWrap = root.querySelector('.aas__results');

    /**
     * Spacja co trzy cyfry, zawsze — jak `number_format($n, 0, ',', ' ')` w PHP.
     * `toLocaleString('pl-PL')` NIE grupuje liczb czterocyfrowych (CLDR:
     * minimumGroupingDigits=2 dla polskiego), więc SSR pokazywał „1 179",
     * a JS po pierwszym filtrze podmieniał to na „1179". Zmierzone 02.09.
     */
    function fmt(n) {
        return String(n || 0).replace(/\B(?=(\d{3})+(?!\d))/g, '\u00a0');
    }

    var page = parseInt(new URLSearchParams(location.search).get('strona') || '1', 10) || 1;
    var seq = 0;                      // numer żądania — chroni przed wyścigiem odpowiedzi
    var timer = null;

    // ---------------------------------------------------------------- stan

    function state() {
        var out = { enum: {}, ranges: {}, flags: [] };
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
        var sort = form.querySelector('.aas__sort-select');
        out.sort = sort ? sort.value : 'date_desc';
        return out;
    }

    /** kolumna w tabeli -> nazwa parametru URL */
    var COL2PARAM = {
        make: 'marka', serie: 'model', color: 'kolor', fuel: 'paliwo', body: 'nadwozie',
        drive: 'naped', transmission: 'skrzynia', upholstery: 'tapicerka',
        sunroof: 'szyberdach', year: 'rocznik'
    };
    function param(col) { return COL2PARAM[col] || col; }

    function queryString(st, withPage) {
        var q = new URLSearchParams();
        Object.keys(st.enum).forEach(function (name) { q.set(name, st.enum[name].join(',')); });
        Object.keys(st.ranges).forEach(function (k) { q.set(k, st.ranges[k]); });
        if (st.flags.length) q.set('wyposazenie', st.flags.join(','));
        if (st.sort && st.sort !== 'date_desc') q.set('sort', st.sort);
        if (withPage && page > 1) q.set('strona', String(page));
        return q.toString();
    }

    function activeCount(st) {
        var n = 0;
        Object.keys(st.enum).forEach(function (k) { n += st.enum[k].length; });
        n += Object.keys(st.ranges).length;
        n += st.flags.length;
        return n;
    }

    // ------------------------------------------------------------- render

    function setCounts(data) {
        var total = data.total || 0;
        if (countEl) countEl.textContent = fmt(total);
        if (applyEl) applyEl.textContent = fmt(total);

        AA_SEARCH.enums.forEach(function (col) {
            var section = form.querySelector('.aas__group[data-col="' + col + '"]');
            if (!section) return;
            var map    = (data.enum && data.enum[col]) || {};
            var labels = (data.labels && data.labels[col]) || {};
            var box    = section.querySelector('.aas__opts');
            if (!box) return;

            var seen = {};
            box.querySelectorAll('.aas__opt').forEach(function (label) {
                var input = label.querySelector('input');
                if (!input) return;
                seen[input.value] = true;
                var n = map[input.value] || 0;
                var c = label.querySelector('.aas__opt-count');
                if (c) c.textContent = fmt(n);
                label.classList.toggle('aas__opt--empty', n === 0);
                // opcja bez trafień i bez zaznaczenia znika z listy, ale zostaje w DOM
                label.hidden = (n === 0 && !input.checked);
            });

            // dorysuj opcje, których nie było w SSR (np. modele po wyborze marki)
            Object.keys(map).forEach(function (slug) {
                if (seen[slug] || !map[slug]) return;
                box.appendChild(buildOption(param(col), slug, labels[slug] || slug, map[slug]));
            });

            // Sekcja „Model" tylko przy wybranej marce. Warunkiem NIE może być
            // „są jakieś modele w licznikach", bo bez marki są wszystkie (2 596 termów)
            // — po „Wyczyść" panel pokazywał listę modeli bez kontekstu marki (02.09).
            if (col === 'serie') {
                var maMarke = form.querySelectorAll('input[name="marka[]"]:checked').length > 0;
                section.hidden = !maMarke;
                section.classList.toggle('aas__group--hidden', !maMarke);
            }
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

    function renderPagination(cur, pages) {
        var old = pagWrap.querySelector('.aas__pagination');
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
            if (current) { el.setAttribute('aria-current', 'page'); }
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
        pagWrap.appendChild(nav);
    }

    // -------------------------------------------------------------- fetch

    function refresh(pushUrl, scrollUp) {
        var st = state();
        var qs = queryString(st, true);
        var mine = ++seq;

        grid.classList.add('is-loading');
        grid.setAttribute('aria-busy', 'true');

        if (badgeEl) {
            var n = activeCount(st);
            badgeEl.textContent = n;
            badgeEl.hidden = n === 0;
        }
        if (pushUrl !== false) {
            history.replaceState({ q: qs }, '', qs ? '?' + qs : location.pathname);
        }

        Promise.all([
            fetch(AA_SEARCH.rest + 'search?' + qs).then(function (r) { return r.json(); }),
            fetch(AA_SEARCH.rest + 'search-counts?' + qs).then(function (r) { return r.json(); })
        ]).then(function (res) {
            if (mine !== seq) return;              // starsza odpowiedź — ignoruj
            var results = res[0], counts = res[1];
            grid.innerHTML = results.html || '';
            page = results.page || 1;
            setCounts(counts);
            renderPagination(results.page || 1, results.pages || 1);
            toggleEmpty(results.total === 0);
            // Przewijamy DOPIERO po podmianie wyników i bez animacji: `behavior:'smooth'`
            // odpalone przed fetchem Chrome anuluje, gdy zmiana treści przestawia wysokość
            // dokumentu — użytkownik zostawał na dole listy (zmierzone 02.09, scrollY 5217).
            if (scrollUp) {
                // `behavior: 'instant'` jawnie — motyw ustawia `scroll-behavior: smooth`
                // na <html>, więc samo scrollTo(x, y) też byłoby animowane i znów
                // przerwane przez zmianę wysokości dokumentu.
                var cel = Math.max(0, root.getBoundingClientRect().top + window.scrollY - 80);
                try { window.scrollTo({ top: cel, behavior: 'instant' }); }
                catch (e) { window.scrollTo(0, cel); }
            }
        }).catch(function () {
            /* sieć padła — zostawiamy poprzednie wyniki, użytkownik może spróbować ponownie */
        }).finally(function () {
            if (mine !== seq) return;
            grid.classList.remove('is-loading');
            grid.setAttribute('aria-busy', 'false');
        });
    }

    function toggleEmpty(isEmpty) {
        var el = pagWrap.querySelector('.aas__empty');
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

    // ------------------------------------------------------------ zdarzenia

    form.addEventListener('change', function (e) {
        var t = e.target;
        if (t.matches('input[type="checkbox"]') || t.matches('.aas__sort-select')) {
            page = 1;
            refresh();
        }
    });
    form.addEventListener('input', function (e) {
        if (e.target.matches('input[type="number"]')) debounced();
    });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        closeDrawer();
        page = 1;
        refresh();
    });

    // zwijanie grup
    form.addEventListener('click', function (e) {
        var btn = e.target.closest('.aas__group-toggle');
        if (!btn) return;
        var open = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', open ? 'false' : 'true');
        var box = document.getElementById(btn.getAttribute('aria-controls'));
        if (box) box.hidden = open;
    });

    // paginacja przez delegację
    pagWrap.addEventListener('click', function (e) {
        var a = e.target.closest('.aas__page[data-page]');
        if (!a) return;
        e.preventDefault();
        page = parseInt(a.dataset.page, 10) || 1;
        refresh(true, true);
    });

    // wyczyść
    var clearBtn = root.querySelector('.aas__clear');
    if (clearBtn) clearBtn.addEventListener('click', function () {
        form.querySelectorAll('input[type="checkbox"]').forEach(function (b) { b.checked = false; });
        form.querySelectorAll('input[type="number"]').forEach(function (i) { i.value = ''; });
        var sort = form.querySelector('.aas__sort-select');
        if (sort) sort.value = 'date_desc';
        page = 1;
        refresh();
    });

    // ------------------------------------------------------------ szuflada

    var openBtn  = root.querySelector('.aas__drawer-open');
    var closeBtn = root.querySelector('.aas__drawer-close');
    var lastFocus = null;

    function openDrawer() {
        lastFocus = document.activeElement;
        root.classList.add('is-open');
        if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        var first = panel.querySelector('button, input, select');
        if (first) first.focus();
        document.addEventListener('keydown', onKey);
    }
    function closeDrawer() {
        if (!root.classList.contains('is-open')) return;
        root.classList.remove('is-open');
        if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        document.removeEventListener('keydown', onKey);
        if (lastFocus) lastFocus.focus();
    }
    function onKey(e) {
        if (e.key === 'Escape') { closeDrawer(); return; }
        if (e.key !== 'Tab') return;
        // pułapka focusu w szufladzie (WCAG 2.1.2)
        var f = panel.querySelectorAll('button, input, select, a[href]');
        var vis = Array.prototype.filter.call(f, function (el) { return el.offsetParent !== null; });
        if (!vis.length) return;
        var first = vis[0], last = vis[vis.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
    if (openBtn)  openBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    root.addEventListener('click', function (e) {
        if (root.classList.contains('is-open') && !panel.contains(e.target) && !openBtn.contains(e.target)) {
            closeDrawer();
        }
    });

    // wstecz/dalej w przeglądarce
    window.addEventListener('popstate', function () { location.reload(); });
})();
