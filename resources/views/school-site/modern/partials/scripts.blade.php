{{--
    What both modern templates do in the browser: the menu drawer, tables as
    cards on a phone, the scroll reveal, counters, sliders and quote rings,
    rows to swipe with arrows, the page finder, the lightbox, back to top and
    the enquiry forms (posted to the school's Website Enquiries, as Classic's).
--}}
<a href="#" class="to-top" id="toTop" aria-label="Back to top">&#8593;</a>

<div class="lb" id="lb" role="dialog" aria-modal="true" aria-label="Photo">
    <button class="lb-x" type="button" aria-label="Close">&times;</button>
    <button class="lb-p" type="button" aria-label="Previous">&#8249;</button>
    <img alt="">
    <button class="lb-n" type="button" aria-label="Next">&#8250;</button>
</div>

<script>
(function () {
    var html = document.documentElement;
    var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ── The menu drawer: the Menu button opens it; its ×, the dimmed page and
    // Escape close it; a group opens under itself (the current page's first).
    var menu = document.getElementById('menu'), toggle = document.getElementById('menuToggle');
    function setMenu(open) {
        menu.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        html.classList.toggle('menu-lock', open);
        if (open && !document.querySelector('.menu-list > li.open')) {
            var here = document.querySelector('.menu-list > li.has-drop.is-active');
            if (here) here.classList.add('open');
        }
    }
    if (toggle) toggle.addEventListener('click', function () { setMenu(!menu.classList.contains('open')); });
    document.querySelectorAll('[data-menu-close]').forEach(function (b) { b.addEventListener('click', function () { setMenu(false); }); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && menu.classList.contains('open')) setMenu(false); });
    document.querySelectorAll('.menu-list > li.has-drop > button').forEach(function (b) {
        b.addEventListener('click', function () {
            var li = b.parentElement, was = li.classList.contains('open');
            document.querySelectorAll('.menu-list > li.open').forEach(function (o) { o.classList.remove('open'); });
            if (!was) li.classList.add('open');
        });
    });
    function fit() { if (window.__fitMenu && !window.__fitMenu() && menu.classList.contains('open')) setMenu(false); }
    fit();
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);
    window.addEventListener('load', fit);
    requestAnimationFrame(function () { requestAnimationFrame(function () { html.classList.add('nav-anim'); }); });
    // Fitted again only when the width changes: a phone's address bar resizes
    // the height as the page scrolls, and nothing here depends on it.
    var fitTimer, fitWidth = window.innerWidth;
    window.addEventListener('resize', function () {
        if (window.innerWidth === fitWidth) return;
        fitWidth = window.innerWidth;
        clearTimeout(fitTimer); fitTimer = setTimeout(fit, 100);
    });
    // The school in the menu bar once the header has scrolled away.
    var head = document.querySelector('.hd');
    if (head && 'IntersectionObserver' in window) {
        new IntersectionObserver(function (e) { menu.classList.toggle('stuck', !e[0].isIntersecting); }).observe(head);
    } else { menu.classList.add('stuck'); }

    // ── Tables on a phone: each value carries its column's name.
    document.querySelectorAll('table.tbl, table.lms-tbl').forEach(function (t) {
        var heads = Array.prototype.map.call(t.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
        if (!heads.length) return;
        t.querySelectorAll('tbody tr').forEach(function (tr) {
            if (tr.children.length !== heads.length) return;
            Array.prototype.forEach.call(tr.children, function (td, i) { if (heads[i]) td.setAttribute('data-label', heads[i]); });
        });
    });

    // ── Sliders (the home photos, the leaders' quotes): [data-slider] holds
    // .slide items, with .sl-prev / .sl-next and .sl-dots buttons.
    document.querySelectorAll('[data-slider]').forEach(function (root) {
        var slides = root.querySelectorAll('.slide');
        if (slides.length < 2) return;
        var dots = root.querySelectorAll('.sl-dots button'), at = 0, timer = null, every = +root.dataset.slider || 5500;
        function go(to) {
            to = (to + slides.length) % slides.length;
            if (to === at) return;
            slides[at].classList.remove('on'); dots[at] && dots[at].classList.remove('on');
            slides[to].classList.add('on'); dots[to] && dots[to].classList.add('on');
            at = to;
        }
        function play() { if (!still) { stop(); timer = setInterval(function () { go(at + 1); }, every); } }
        function stop() { clearInterval(timer); timer = null; }
        var p = root.querySelector('.sl-prev'), n = root.querySelector('.sl-next');
        if (p) p.addEventListener('click', function () { go(at - 1); play(); });
        if (n) n.addEventListener('click', function () { go(at + 1); play(); });
        dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); play(); }); });
        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', play);
        document.addEventListener('visibilitychange', function () { document.hidden ? stop() : play(); });
        // A swipe changes the slide; a thumb scrolling the page over it never
        // does: the move must be mostly sideways and the page must not scroll.
        var x0 = null, y0 = 0, sy0 = 0;
        root.addEventListener('touchstart', function (e) {
            if (e.touches.length !== 1) { x0 = null; return; }
            x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; sy0 = window.scrollY; stop();
        }, { passive: true });
        root.addEventListener('touchend', function (e) {
            if (x0 === null) { play(); return; }
            var t = e.changedTouches[0], dx = t.clientX - x0, dy = t.clientY - y0; x0 = null;
            if (Math.abs(dx) > 50 && Math.abs(dx) > 2 * Math.abs(dy) && Math.abs(window.scrollY - sy0) < 8) go(at + (dx < 0 ? 1 : -1));
            play();
        }, { passive: true });
        root.addEventListener('touchcancel', function () { x0 = null; play(); }, { passive: true });
        play();
    });

    // ── Rows to swipe, with arrows: [data-rail-prev|next="id"] scroll #id a card.
    document.querySelectorAll('[data-rail-prev], [data-rail-next]').forEach(function (b) {
        b.addEventListener('click', function () {
            var rail = document.getElementById(b.dataset.railPrev || b.dataset.railNext);
            if (!rail) return;
            var card = rail.firstElementChild, step = card ? card.getBoundingClientRect().width + 18 : rail.clientWidth * .8;
            rail.scrollBy({ left: b.dataset.railPrev ? -step : step, behavior: still ? 'auto' : 'smooth' });
        });
    });

    // ── The page finder: what is typed picks out the site's pages.
    document.querySelectorAll('[data-find]').forEach(function (box) {
        var input = box.querySelector('input'), links = box.querySelectorAll('[data-find-list] a'), out = box.querySelector('[data-find-out]');
        if (!input || !out) return;
        function show() {
            var q = input.value.trim().toLowerCase(), n = 0;
            out.innerHTML = '';
            if (!q) { box.classList.remove('has-hits'); return; }
            links.forEach(function (a) {
                if (n < 5 && a.textContent.toLowerCase().indexOf(q) !== -1) { out.appendChild(a.cloneNode(true)); n++; }
            });
            if (!n) { var none = document.createElement('span'); none.textContent = 'No page found — try “fee”, “admission” or “result”.'; out.appendChild(none); }
            box.classList.add('has-hits');
        }
        input.addEventListener('input', show);
        input.addEventListener('focus', show);
        box.addEventListener('submit', function (e) { e.preventDefault(); var first = out.querySelector('a'); if (first) window.location.href = first.href; });
        document.addEventListener('click', function (e) { if (!box.contains(e.target)) box.classList.remove('has-hits'); });
    });

    // ── Counters count up when they come into view ("95%", "200+", "1,200").
    var counters = document.querySelectorAll('[data-count]');
    function count(el) {
        var m = el.textContent.trim().match(/^(\D*)(\d[\d,]*\.?\d*)(.*)$/);
        if (!m || still) return;
        var target = parseFloat(m[2].replace(/,/g, '')), dec = (m[2].split('.')[1] || '').length, t0 = null, comma = m[2].indexOf(',') !== -1;
        function step(t) {
            if (!t0) t0 = t;
            var k = Math.min(1, (t - t0) / 1300), v = target * (1 - Math.pow(1 - k, 3));
            var s = dec ? v.toFixed(dec) : String(Math.round(v));
            if (comma) s = Number(s).toLocaleString('en-IN', { minimumFractionDigits: dec, maximumFractionDigits: dec });
            el.textContent = m[1] + s + m[3];
            if (k < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }
    if (counters.length && 'IntersectionObserver' in window) {
        var co = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { co.unobserve(e.target); count(e.target); } }); }, { threshold: .6 });
        counters.forEach(function (el) { co.observe(el); });
    }

    // ── Scroll reveal: blocks rise in as they come into view, a row's one
    // after another. On a phone or tablet they only fade in with a short rise,
    // and a block that fills the screen (or sits in a swipe row) never moves.
    (function () {
        if (!('IntersectionObserver' in window) || still) return;
        var ITEMS = '.sec-head, .panel, .tile, .fac-card, .person, .leader, .photos figure, .cls article, .b-card, .stat, '
            + '.v-path, .v-point, .dash > *, .ev-card, .info-card, .lms-card, .quote-card, .mosaic > *, .step, .foot-grid > *, .reveal';
        var small = window.innerWidth <= 1020, vh = window.innerHeight || html.clientHeight;
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting) return;
                var el = e.target, done = false;
                io.unobserve(el);
                el.classList.add('rv-in');
                var clean = function (ev) {
                    if ((ev && ev.target !== el) || done) return;
                    done = true;
                    el.classList.remove('rv-on', 'rv-soft', 'rv-in');
                    el.style.removeProperty('--rv-delay');
                    el.removeEventListener('transitionend', clean);
                };
                el.addEventListener('transitionend', clean);
                setTimeout(clean, 1800);
            });
        }, { threshold: .1, rootMargin: '0px 0px -6% 0px' });
        function inSwipeRow(el) {
            for (var p = el.parentElement; p && p !== document.body; p = p.parentElement) {
                var o = getComputedStyle(p).overflowX;
                if (o === 'auto' || o === 'scroll') return true;
            }
            return false;
        }
        function tag(el) {
            if (el.classList.contains('rv-on') || (el.parentElement && el.parentElement.closest('.rv-on'))) return;
            if (el.getBoundingClientRect().top < vh * .9) return;   // already on screen: no flash
            if (small && (el.offsetHeight > vh * .6 || inSwipeRow(el))) return;
            var idx = 0, sib = el.previousElementSibling;
            while (sib) { idx++; sib = sib.previousElementSibling; }
            el.classList.add('rv-on');
            if (small) el.classList.add('rv-soft');
            if (idx) el.style.setProperty('--rv-delay', Math.min(idx, 6) * 80 + 'ms');
            io.observe(el);
        }
        function init() { document.querySelectorAll(ITEMS).forEach(tag); }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    })();

    // ── Back to top.
    var top = document.getElementById('toTop');
    window.addEventListener('scroll', function () { top.classList.toggle('show', window.scrollY > 600); }, { passive: true });
    top.addEventListener('click', function (e) { e.preventDefault(); window.scrollTo({ top: 0, behavior: still ? 'auto' : 'smooth' }); });

    // ── Lightbox for every [data-lb] photo; a .photos grid's photos page through together.
    var lb = document.getElementById('lb'), lbImg = lb.querySelector('img'), set = [], at = 0;
    function show(i) { at = (i + set.length) % set.length; lbImg.src = set[at]; }
    document.addEventListener('click', function (e) {
        var img = e.target.closest('[data-lb] img');
        if (!img) return;
        var grid = img.closest('.photos, .mosaic') || document;
        set = Array.prototype.map.call(grid.querySelectorAll('[data-lb] img'), function (i) { return i.src; });
        show(set.indexOf(img.src));
        lb.classList.add('open');
    });
    lb.querySelector('.lb-x').onclick = function () { lb.classList.remove('open'); };
    lb.querySelector('.lb-p').onclick = function () { show(at - 1); };
    lb.querySelector('.lb-n').onclick = function () { show(at + 1); };
    lb.addEventListener('click', function (e) { if (e.target === lb) lb.classList.remove('open'); });
    document.addEventListener('keydown', function (e) {
        if (!lb.classList.contains('open')) return;
        if (e.key === 'Escape') lb.classList.remove('open');
        if (e.key === 'ArrowLeft') show(at - 1);
        if (e.key === 'ArrowRight') show(at + 1);
    });

    // ── Enquiry forms (the quick call-back, Admission, Query, Career, Contact,
    // Appointment) post to the school's enquiry inbox, exactly as Classic's:
    // the form's type, every field filled in as its label and answer, name,
    // email and phone as they are, the form's kind as the subject.
    document.querySelectorAll('form.sform').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            var btn = form.querySelector('button[type=submit]'), msg = form.querySelector('.sform-msg');
            var say = function (t, ok) { msg.textContent = t; msg.style.color = ok ? '#059669' : '#DC2626'; };
            var missing = Array.prototype.find.call(form.querySelectorAll('[required]'), function (f) { return !f.value.trim(); });
            if (missing) { say('Please fill in ' + missing.dataset.label + '.'); missing.focus(); return; }

            var trap = form.querySelector('[name=sf_check]');
            var data = { organization_id: Number(form.dataset.org), company: trap ? trap.value : '', subject: form.dataset.kind,
                         type: form.dataset.type || null, fields: [] }, extra = [];
            form.querySelectorAll('[data-label]').forEach(function (f) {
                var v = f.value.trim(), n = f.name;
                if (v && f.type === 'date') { v = new Date(v + 'T00:00:00').toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }); }
                if (v) { data.fields.push({ label: f.dataset.label, value: v }); }
                if (n === 'name' || n === 'email' || n === 'phone') { data[n] = v; }
                else if (n === 'message') { data.message_body = v; }
                else if (v) { extra.push(f.dataset.label + ': ' + v); if (f.dataset.subject) data.subject += ' — ' + v; }
            });
            data.message = extra.join('\n') + (extra.length && data.message_body ? '\n\n' : '') + (data.message_body || '');
            delete data.message_body;

            btn.disabled = true; var label = btn.innerHTML; btn.textContent = 'Sending…'; msg.textContent = '';
            try {
                var res = await fetch('/api/website/school-contact', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(data),
                });
                var json = await res.json().catch(function () { return {}; });
                if (res.ok && json.success) { form.reset(); say(json.message || 'Thank you! We will get back to you soon.', true); }
                else {
                    var first = json.errors ? Object.values(json.errors)[0][0] : null;
                    say(first || json.message || 'Something went wrong. Please try again.');
                }
            } catch (err) {
                say('Network error. Please try again.');
            } finally {
                btn.disabled = false; btn.innerHTML = label;
            }
        });
    });
})();
</script>
