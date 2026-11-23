/* Maestro Crown College: shared site chrome behaviour (all public pages). */
(function () {
    'use strict';

    var root = document.documentElement;
    var header = document.querySelector('[data-header]');
    var progress = document.querySelector('[data-progress]');
    var applybar = document.querySelector('[data-applybar]');
    var transparentStart = header && header.getAttribute('data-transparent-start') === '1';
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function store(key, value) {
        try { if (value === undefined) return localStorage.getItem(key); localStorage.setItem(key, value); } catch (e) { return null; }
    }

    /* ── Announcement dismiss ─────────────────────────────── */
    var announceClose = document.querySelector('[data-announce-close]');
    if (announceClose) {
        announceClose.addEventListener('click', function () {
            root.classList.add('no-announce');
            var live = document.querySelector('.mc-live span:last-child');
            var session = live ? live.textContent.replace(/\D/g, '') : '1';
            store('mc-announce-hidden', session);
            window.dispatchEvent(new Event('resize'));
        });
    }

    /* ── Header states on scroll ──────────────────────────── */
    var lastY = window.scrollY;
    var ticking = false;
    var drawerOpen = false;

    function onScroll() {
        var y = window.scrollY;
        var vh = window.innerHeight;

        if (header) {
            header.classList.toggle('is-scrolled', y > 80);
            if (transparentStart) header.classList.toggle('is-transparent', y < vh * 0.6);

            var goingDown = y > lastY + 4;
            var goingUp = y < lastY - 4;
            if (!drawerOpen && !header.contains(document.activeElement)) {
                if (goingDown && y > vh * 0.9) header.classList.add('is-hidden');
                else if (goingUp || y < 120) header.classList.remove('is-hidden');
            }
        }

        if (progress) {
            var max = document.documentElement.scrollHeight - vh;
            progress.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, y / max) : 0) + ')';
        }

        if (applybar) {
            var cta = document.getElementById('final-cta');
            var nearCta = cta && cta.getBoundingClientRect().top < vh * 0.9;
            applybar.classList.toggle('is-visible', y > vh * 0.85 && !nearCta);
        }

        lastY = y;
        ticking = false;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) { window.requestAnimationFrame(onScroll); ticking = true; }
    }, { passive: true });
    onScroll();

    /* ── Dropdown menus (hover via CSS; click/keyboard here) ─ */
    var menus = Array.prototype.slice.call(document.querySelectorAll('.has-menu, [data-apply]'));

    function closeMenus(except) {
        menus.forEach(function (m) {
            if (m === except) return;
            m.classList.remove('is-open');
            var b = m.querySelector('button');
            if (b) b.setAttribute('aria-expanded', 'false');
        });
    }

    menus.forEach(function (menu) {
        var btn = menu.querySelector('button');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = !menu.classList.contains('is-open');
            closeMenus(menu);
            menu.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.has-menu, [data-apply]')) closeMenus();
    });

    /* ── Drawer ───────────────────────────────────────────── */
    var drawer = document.querySelector('[data-drawer]');
    var openBtn = document.querySelector('[data-drawer-open]');

    function setDrawer(open) {
        if (!drawer) return;
        drawerOpen = open;
        drawer.classList.toggle('is-open', open);
        drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (openBtn) openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('is-locked', open);
        window.dispatchEvent(new CustomEvent(open ? 'mc:lock' : 'mc:unlock'));
        if (open) {
            var first = drawer.querySelector('.mc-drawer-close');
            setTimeout(function () { if (first) first.focus(); }, reduceMotion ? 0 : 200);
        } else if (openBtn) {
            openBtn.focus();
        }
    }

    if (openBtn) openBtn.addEventListener('click', function () { setDrawer(true); });
    Array.prototype.forEach.call(document.querySelectorAll('[data-drawer-close]'), function (el) {
        el.addEventListener('click', function () { setDrawer(false); });
    });
    if (drawer) {
        drawer.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') return;
            var f = drawer.querySelectorAll('a[href], button, summary');
            var first = f[0], last = f[f.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
        drawer.querySelectorAll('a[href]').forEach(function (a) {
            a.addEventListener('click', function () { setDrawer(false); });
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        closeMenus();
        if (drawerOpen) setDrawer(false);
    });
})();
