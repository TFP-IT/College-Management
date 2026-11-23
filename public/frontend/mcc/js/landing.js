/* ==========================================================================
   Maestro Crown College — landing page motion & interactions.
   Loaded (defer) BEFORE GSAP/Lenis: the core runs immediately in vanilla JS;
   scroll-linked effects start on DOMContentLoaded only if GSAP/Lenis loaded.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.documentElement;
    var RM = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var FINE = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    var $ = function (s, ctx) { return (ctx || document).querySelector(s); };
    var $$ = function (s, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(s)); };
    var lenis = null;

    root.classList.add('motion-ready');

    /* ── Split text into masked words ─────────────────────────────── */
    function splitWords(el) {
        var i = 0;
        var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);
        var nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(function (node) {
            var frag = document.createDocumentFragment();
            node.textContent.split(/(\s+)/).forEach(function (part) {
                if (!part) return;
                if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
                var w = document.createElement('span');
                w.className = 'w';
                var wi = document.createElement('span');
                wi.className = 'wi';
                wi.style.setProperty('--wi', i++);
                wi.textContent = part;
                w.appendChild(wi);
                frag.appendChild(w);
            });
            node.parentNode.replaceChild(frag, node);
        });
        el.setAttribute('aria-label', el.textContent.replace(/\s+/g, ' ').trim());
        el.classList.add('is-split');
    }
    $$('[data-split]').forEach(splitWords);

    /* ── Hero entrance ────────────────────────────────────────────── */
    var hero = $('[data-hero]');
    if (hero) {
        var start = root.classList.contains('intro-play') ? 1.15 : 0.1;
        var title = $('.hero-title', hero);
        if (title) title.style.setProperty('--d', start + 0.05 + 's');
        $$('[data-hero-item]', hero).forEach(function (el, i) {
            var base = i === 0 ? 0 : 0.35;
            el.style.setProperty('--d', (start + base + i * 0.1) + 's');
        });
        $$('[data-hero-card]', hero).forEach(function (el, i) {
            el.style.setProperty('--d', (start + 0.55 + i * 0.15) + 's');
        });
        requestAnimationFrame(function () { requestAnimationFrame(function () { hero.classList.add('is-go'); }); });
        setTimeout(function () { var intro = $('.mc-intro'); if (intro) intro.remove(); }, 2200);
    }

    /* ── Hero video: only on capable, non-data-saving, wider screens ─ */
    var video = $('[data-hero-video]');
    if (video) {
        var saveData = navigator.connection && navigator.connection.saveData;
        if (!RM && !saveData && window.innerWidth >= 768) {
            video.src = video.getAttribute('data-src');
            video.preload = 'auto';
            var playSafe = function () { var p = video.play(); if (p && p.catch) p.catch(function () {}); };
            playSafe();
            new IntersectionObserver(function (entries) {
                entries[0].isIntersecting ? playSafe() : video.pause();
            }, { threshold: 0.05 }).observe(video);
            document.addEventListener('visibilitychange', function () { document.hidden ? video.pause() : playSafe(); });
        }
    }

    /* ── Counters ─────────────────────────────────────────────────── */
    function runCounter(el) {
        if (el.dataset.counted) return;
        el.dataset.counted = '1';
        var end = parseFloat(el.getAttribute('data-count'));
        var plain = el.hasAttribute('data-plain');
        var from = plain ? end - 24 : 0;
        var fmt = function (n) { return plain ? String(Math.round(n)) : Math.round(n).toLocaleString('en-US'); };
        if (RM) { el.textContent = fmt(end); return; }
        var dur = 1800, t0 = null;
        function step(t) {
            if (!t0) t0 = t;
            var p = Math.min(1, (t - t0) / dur);
            var eased = p === 1 ? 1 : 1 - Math.pow(2, -10 * p); // expo out
            el.textContent = fmt(from + (end - from) * eased);
            if (p < 1) requestAnimationFrame(step);
        }
        el.textContent = fmt(from);
        requestAnimationFrame(step);
    }

    /* ── Rings ────────────────────────────────────────────────────── */
    $$('[data-ring]').forEach(function (c) {
        c.style.setProperty('--ring', (parseFloat(c.getAttribute('data-ring')) || 0) / 100);
    });

    /* ── Scroll reveal (IntersectionObserver + CSS transitions) ───── */
    $$('[data-stagger]').forEach(function (group) {
        $$('[data-reveal], [data-clip]', group).forEach(function (el, i) {
            if (!el.style.getPropertyValue('--d')) el.style.setProperty('--d', Math.min(i * 0.08, 0.64) + 's');
        });
    });

    function reveal(el, batchIndex) {
        if (el.classList.contains('is-in')) return;
        if (!el.style.getPropertyValue('--d') && batchIndex) el.style.setProperty('--d', Math.min(batchIndex * 0.08, 0.4) + 's');
        el.classList.add('is-in');
        $$('[data-count]', el).concat(el.matches('[data-count]') ? [el] : []).forEach(runCounter);
        if (el.hasAttribute('data-reveal')) {
            var done = function () { el.classList.add('reveal-done'); };
            el.addEventListener('transitionend', function te(e) {
                if (e.target === el && e.propertyName === 'opacity') { el.removeEventListener('transitionend', te); done(); }
            });
            setTimeout(done, 2400);
        }
    }

    var revealTargets = $$('[data-reveal], [data-clip], [data-split]:not(.hero-title), .app-devices, .trust-item');
    if ('IntersectionObserver' in window && !RM) {
        var io = new IntersectionObserver(function (entries) {
            var batch = 0;
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                reveal(entry.target, batch++);
                io.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
        revealTargets.forEach(function (el) { io.observe(el); });
    } else {
        revealTargets.forEach(function (el) { reveal(el, 0); });
    }

    /* ── Tabs (School / College) ──────────────────────────────────── */
    var tabs = $('[data-tabs]');
    if (tabs) {
        var buttons = $$('[data-tab]', tabs);
        var select = function (btn, focus) {
            var key = btn.getAttribute('data-tab');
            tabs.setAttribute('data-active', key);
            buttons.forEach(function (b) {
                var on = b === btn;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
                b.tabIndex = on ? 0 : -1;
            });
            $$('[data-panel]').forEach(function (panel) {
                var show = panel.getAttribute('data-panel') === key;
                if (!show) { panel.hidden = true; panel.classList.remove('is-active'); return; }
                if (!panel.hidden) return;
                panel.classList.add('is-entering');
                panel.hidden = false;
                panel.classList.add('is-active');
                $$('[data-panel-item]', panel).forEach(function (it, i) { it.style.setProperty('--d', i * 0.08 + 's'); });
                $$('.chips', panel).forEach(function (list, li) {
                    $$('li', list).forEach(function (chip, ci) { chip.style.setProperty('--cd', 0.25 + li * 0.08 + ci * 0.035 + 's'); });
                });
                void panel.offsetWidth; // commit the hidden state before transitioning in
                requestAnimationFrame(function () { panel.classList.remove('is-entering'); });
            });
            if (focus) btn.focus();
            if (window.ScrollTrigger) setTimeout(function () { window.ScrollTrigger.refresh(); }, 50);
        };
        buttons.forEach(function (btn, i) {
            btn.addEventListener('click', function () { select(btn); });
            btn.addEventListener('keydown', function (e) {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                e.preventDefault();
                var next = buttons[(i + (e.key === 'ArrowRight' ? 1 : buttons.length - 1)) % buttons.length];
                select(next, true);
            });
        });
        tabs.setAttribute('data-active', 'school');
    }

    /* ── Pointer effects: tilt, magnetic, spotlight (desktop only) ── */
    if (FINE && !RM) {
        $$('[data-tilt]').forEach(function (card) {
            card.addEventListener('pointermove', function (e) {
                var r = card.getBoundingClientRect();
                var px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
                card.style.transition = 'transform .15s ease-out, box-shadow .5s';
                card.style.transform = 'perspective(900px) rotateX(' + ((0.5 - py) * 10).toFixed(2) + 'deg) rotateY(' + ((px - 0.5) * 12).toFixed(2) + 'deg)';
                card.style.setProperty('--gx', (px * 100).toFixed(1) + '%');
                card.style.setProperty('--gy', (py * 100).toFixed(1) + '%');
            });
            card.addEventListener('pointerleave', function () {
                card.style.transition = 'transform .8s cubic-bezier(.22,1,.36,1), box-shadow .5s';
                card.style.transform = '';
            });
        });

        $$('[data-magnetic]').forEach(function (el) {
            var tx = 0, ty = 0, cx = 0, cy = 0, raf = null;
            var loop = function () {
                cx += (tx - cx) * 0.18; cy += (ty - cy) * 0.18;
                el.style.translate = cx.toFixed(2) + 'px ' + cy.toFixed(2) + 'px';
                if (Math.abs(tx - cx) > 0.1 || Math.abs(ty - cy) > 0.1) raf = requestAnimationFrame(loop);
                else { raf = null; if (!tx && !ty) el.style.translate = ''; }
            };
            var kick = function () { if (!raf) raf = requestAnimationFrame(loop); };
            el.addEventListener('pointermove', function (e) {
                var r = el.getBoundingClientRect();
                tx = (e.clientX - (r.left + r.width / 2)) * 0.25;
                ty = (e.clientY - (r.top + r.height / 2)) * 0.35;
                kick();
            });
            el.addEventListener('pointerleave', function () { tx = 0; ty = 0; kick(); });
        });

        $$('[data-spotlight]').forEach(function (card) {
            card.addEventListener('pointermove', function (e) {
                var r = card.getBoundingClientRect();
                card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                card.style.setProperty('--my', (e.clientY - r.top) + 'px');
            });
        });
    }

    /* ── FAQ: smooth open/close ───────────────────────────────────── */
    $$('[data-faq]').forEach(function (item) {
        var summary = $('summary', item), body = $('.faq-a', item), anim = null;
        summary.addEventListener('click', function (e) {
            if (RM || !body.animate) return;
            e.preventDefault();
            if (anim) anim.cancel();
            if (!item.open) {
                item.open = true;
                var h = body.offsetHeight;
                anim = body.animate([{ height: '0px', opacity: 0 }, { height: h + 'px', opacity: 1 }], { duration: 450, easing: 'cubic-bezier(.22,1,.36,1)' });
            } else {
                var h2 = body.offsetHeight;
                anim = body.animate([{ height: h2 + 'px', opacity: 1 }, { height: '0px', opacity: 0 }], { duration: 350, easing: 'cubic-bezier(.65,0,.35,1)' });
                anim.onfinish = function () { item.open = false; };
            }
            anim.addEventListener('finish', function () { anim = null; if (window.ScrollTrigger) window.ScrollTrigger.refresh(); });
        });
    });

    /* ── Gallery: category filter + show more ──────────────────── */
    var gal = $('[data-gallery]');
    if (gal) {
        var galItems = $$('.gal-item', gal);
        var initial = parseInt(gal.getAttribute('data-initial'), 10) || 12;
        var moreBtn = $('[data-gal-more]', gal), moreLabel = $('[data-gal-more-label]', gal);
        var filters = $$('[data-filter]', gal);
        var state = { cat: 'all', expanded: false };

        var applyGallery = function (scrollBack) {
            var matching = galItems.filter(function (el) { return state.cat === 'all' || el.getAttribute('data-cat') === state.cat; });
            var limit = state.expanded ? Infinity : initial;
            var entering = [];
            galItems.forEach(function (el) {
                var idx = matching.indexOf(el);
                var show = idx > -1 && idx < limit;
                if (show && el.hidden) entering.push(el);
                el.hidden = !show;
            });
            if (!RM && entering.length) {
                entering.forEach(function (el, i) { el.style.setProperty('--d', Math.min(i * 0.04, 0.5) + 's'); el.classList.add('is-entering'); });
                void gal.offsetWidth; // commit the hidden state before transitioning in
                requestAnimationFrame(function () { entering.forEach(function (el) { el.classList.remove('is-entering'); }); });
            }
            if (moreBtn) {
                moreBtn.hidden = matching.length <= initial;
                moreLabel.textContent = state.expanded ? 'Show fewer photos' : 'Show all ' + matching.length + ' photos';
                moreBtn.setAttribute('aria-expanded', state.expanded ? 'true' : 'false');
            }
            if (scrollBack) {
                var top = gal.getBoundingClientRect().top + window.scrollY - 80;
                if (lenis) lenis.scrollTo(top, { duration: 1 });
                else window.scrollTo({ top: top, behavior: RM ? 'auto' : 'smooth' });
            }
            if (window.ScrollTrigger) setTimeout(function () { window.ScrollTrigger.refresh(); }, 80);
        };

        filters.forEach(function (btn) {
            btn.addEventListener('click', function () {
                state.cat = btn.getAttribute('data-filter');
                state.expanded = false;
                filters.forEach(function (b) {
                    var on = b === btn;
                    b.classList.toggle('is-active', on);
                    b.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
                applyGallery(false);
            });
        });
        if (moreBtn) moreBtn.addEventListener('click', function () {
            var collapsing = state.expanded;
            state.expanded = !state.expanded;
            applyGallery(collapsing);
        });
        applyGallery(false);
    }

    /* ── Lightbox (navigates within its own scope: posters or gallery) ── */
    var modal = $('[data-lightbox-modal]');
    if (modal) {
        var img = $('[data-lightbox-img]', modal), cap = $('[data-lightbox-cap]', modal), count = $('[data-lightbox-count]', modal);
        var items = [], current = 0, lastFocus = null;
        var show = function (i, fromEl) {
            current = (i + items.length) % items.length;
            var item = items[current];
            var src = item.getAttribute('data-lightbox');
            img.src = src;
            img.alt = item.getAttribute('data-caption') || '';
            cap.textContent = item.getAttribute('data-caption') || '';
            if (count) count.textContent = items.length > 1 ? (current + 1) + ' / ' + items.length : '';
            if (RM || !img.animate) return;
            var run = function () {
                var to = img.getBoundingClientRect();
                if (fromEl && to.width) {
                    var from = fromEl.getBoundingClientRect();
                    img.animate([
                        { transform: 'translate(' + (from.left - to.left) + 'px,' + (from.top - to.top) + 'px) scale(' + (from.width / to.width) + ',' + (from.height / to.height) + ')', transformOrigin: '0 0', opacity: 0.6 },
                        { transform: 'none', transformOrigin: '0 0', opacity: 1 }
                    ], { duration: 550, easing: 'cubic-bezier(.22,1,.36,1)' });
                } else {
                    img.animate([{ opacity: 0, transform: 'scale(.96)' }, { opacity: 1, transform: 'none' }], { duration: 380, easing: 'ease-out' });
                }
            };
            if (img.complete && img.naturalWidth) run();
            else img.addEventListener('load', run, { once: true });
        };
        var open = function (btn) {
            var scope = btn.closest('[data-lightbox-scope]') || document;
            items = $$('[data-lightbox]', scope).filter(function (b) { return !b.hidden; });
            lastFocus = btn;
            modal.hidden = false;
            document.body.classList.add('is-locked');
            if (lenis) lenis.stop();
            show(Math.max(0, items.indexOf(btn)), btn);
            $('[data-lightbox-close]', modal).focus();
        };
        var close = function () {
            modal.hidden = true;
            document.body.classList.remove('is-locked');
            if (lenis) lenis.start();
            if (lastFocus) lastFocus.focus({ preventScroll: true });
        };
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-lightbox]');
            if (btn && !modal.contains(btn)) open(btn);
        });
        $('[data-lightbox-close]', modal).addEventListener('click', close);
        $('[data-lightbox-prev]', modal).addEventListener('click', function () { show(current - 1); });
        $('[data-lightbox-next]', modal).addEventListener('click', function () { show(current + 1); });
        modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
        var touchX = null;
        modal.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
        modal.addEventListener('touchend', function (e) {
            if (touchX === null) return;
            var dx = e.changedTouches[0].clientX - touchX;
            if (Math.abs(dx) > 50) show(current + (dx < 0 ? 1 : -1));
            touchX = null;
        });
        document.addEventListener('keydown', function (e) {
            if (modal.hidden) return;
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') show(current - 1);
            if (e.key === 'ArrowRight') show(current + 1);
        });
    }

    /* ── Fallback "lit" states when GSAP is unavailable ───────────── */
    function litAll() {
        $$('[data-journey-step]').forEach(function (s) { s.classList.add('is-lit'); });
        $$('[data-process-step]').forEach(function (s) { s.classList.add('is-reached'); });
    }

    /* ── Scroll-linked effects (GSAP + ScrollTrigger + Lenis) ─────── */
    document.addEventListener('DOMContentLoaded', function () {
        var gsap = window.gsap, ST = window.ScrollTrigger;
        if (RM || !gsap || !ST) { litAll(); return; }
        gsap.registerPlugin(ST);

        if (window.Lenis) {
            lenis = new window.Lenis({ lerp: 0.1, smoothWheel: true });
            lenis.on('scroll', ST.update);
            gsap.ticker.add(function (t) { lenis.raf(t * 1000); });
            gsap.ticker.lagSmoothing(0);
            window.addEventListener('mc:lock', function () { lenis.stop(); });
            window.addEventListener('mc:unlock', function () { lenis.start(); });
            document.addEventListener('click', function (e) {
                var a = e.target.closest('a[href^="#"]');
                if (!a || a.getAttribute('href').length < 2) return;
                var target = document.querySelector(a.getAttribute('href'));
                if (!target) return;
                e.preventDefault();
                var header = document.querySelector('[data-header]');
                lenis.scrollTo(target, { offset: -((header && header.offsetHeight) || 0) + 1, duration: 1.4 });
            });
        }

        // Hero: media drifts slower than content; copy fades as it leaves.
        if (hero) {
            gsap.to('[data-hero-media]', { yPercent: 16, scale: 1.06, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
            gsap.to('.hero-inner', { y: -70, opacity: 0.2, ease: 'none', scrollTrigger: { trigger: hero, start: '35% top', end: 'bottom top', scrub: true } });
            gsap.to('[data-hero-shade]', { opacity: 1.0, backgroundColor: 'rgba(26,8,8,.5)', ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
        }

        // Parallax layers (about collage).
        $$('[data-parallax]').forEach(function (el) {
            gsap.to(el, { yPercent: parseFloat(el.getAttribute('data-parallax')) || 8, ease: 'none', scrollTrigger: { trigger: el.parentElement, start: 'top bottom', end: 'bottom top', scrub: true } });
        });

        // Learning journey: pinned horizontal scroll on desktop, vertical draw on smaller screens.
        var journey = $('[data-journey]');
        if (journey) {
            var track = $('[data-journey-track]', journey);
            var line = $('[data-journey-line]', journey);
            var steps = $$('[data-journey-step]', journey);
            var light = function (p) {
                line.style.setProperty('--p', p.toFixed(4));
                steps.forEach(function (s, i) { s.classList.toggle('is-lit', p >= i / Math.max(1, steps.length - 1) - 0.04); });
            };
            line.style.setProperty('--p', 0);
            var mm = gsap.matchMedia();
            mm.add('(min-width: 1024px)', function () {
                    journey.classList.add('is-pinned');
                    var distance = function () { return Math.max(0, track.scrollWidth - journey.clientWidth); };
                    gsap.to(track, {
                        x: function () { return -distance(); },
                        ease: 'none',
                        scrollTrigger: {
                            trigger: journey, start: 'top top', end: function () { return '+=' + Math.max(distance(), window.innerHeight * 0.6); },
                            pin: true, scrub: 0.8, invalidateOnRefresh: true, anticipatePin: 1,
                            onUpdate: function (self) { light(self.progress); }
                        }
                    });
                    return function () { journey.classList.remove('is-pinned'); };
            });
            mm.add('(max-width: 1023px)', function () {
                    ST.create({ trigger: track, start: 'top 75%', end: 'bottom 65%', scrub: true, onUpdate: function (self) { light(self.progress); } });
            });
        }

        // Admission process: line draws, each step lights up as it is reached.
        var process = $('[data-process]');
        if (process) {
            var pLine = $('[data-process-line]', process);
            if (pLine) {
                pLine.style.setProperty('--p', 0);
                ST.create({ trigger: '.process-list', start: 'top 75%', end: 'bottom 55%', scrub: true, onUpdate: function (self) { pLine.style.setProperty('--p', self.progress.toFixed(4)); } });
            }
            $$('[data-process-step]', process).forEach(function (step) {
                ST.create({ trigger: step, start: 'top 72%', onEnter: function () { step.classList.add('is-reached'); }, onLeaveBack: function () { step.classList.remove('is-reached'); } });
            });
        }

        // Phones in the app section float apart slightly as you scroll.
        var front = $('.phone--front'), back = $('.phone--back');
        if (front && back) {
            gsap.to(front, { y: -40, ease: 'none', scrollTrigger: { trigger: '.app-devices', start: 'top bottom', end: 'bottom top', scrub: true } });
            gsap.to(back, { y: 30, ease: 'none', scrollTrigger: { trigger: '.app-devices', start: 'top bottom', end: 'bottom top', scrub: true } });
        }

        window.addEventListener('load', function () { ST.refresh(); });
        if (video) video.addEventListener('loadedmetadata', function () { ST.refresh(); });
    });
})();
