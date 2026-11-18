/* SUPERLMS website — "new" design behaviour.
   Nav, mobile menu, scroll reveal, live mockups (play only while on screen),
   counters, typing, tabs, FAQ, ask-pill → WhatsApp, toast. No dependencies. */
(function () {
  'use strict';
  var d = document, w = window;
  var reduce = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function $(s, r) { return (r || d).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || d).querySelectorAll(s)); }

  /* ── Number helpers (used by the pages' live-stat scripts) ── */
  w.siteFmt = function (n) {
    n = Number(n) || 0;
    if (n >= 1000) return Math.floor(n / 1000) + 'K+';
    return n + '+';
  };
  w.siteEsc = function (s) {
    if (s == null) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  };

  /* ── Navbar shadow on scroll ── */
  var nav = $('.nav');
  function onScroll() { if (nav) nav.classList.toggle('is-scrolled', w.scrollY > 8); }
  w.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ── Mobile menu ── */
  var mnav = $('#mnav');
  function openMenu() { if (!mnav) return; mnav.classList.add('is-open'); d.body.style.overflow = 'hidden'; }
  function closeMenu() { if (!mnav) return; mnav.classList.remove('is-open'); d.body.style.overflow = ''; }
  $$('[data-menu-open]').forEach(function (b) { b.addEventListener('click', openMenu); });
  $$('[data-menu-close]').forEach(function (b) { b.addEventListener('click', closeMenu); });
  if (mnav) {
    mnav.addEventListener('click', function (e) { if (e.target === mnav) closeMenu(); });
    $$('a', mnav).forEach(function (a) { a.addEventListener('click', closeMenu); });
  }
  d.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });

  /* ── Announcement bar (closable for the session) ── */
  var topbar = $('.topbar');
  if (topbar) {
    try { if (sessionStorage.getItem('sl_topbar') === '0') topbar.classList.add('is-hidden'); } catch (e) {}
    var tc = $('.topbar-close', topbar);
    if (tc) tc.addEventListener('click', function () {
      topbar.classList.add('is-hidden');
      try { sessionStorage.setItem('sl_topbar', '0'); } catch (e) {}
    });
  }

  /* ── Count-up ── */
  function countUp(el, to, suffix, decimals) {
    to = Number(to); if (isNaN(to)) return;
    suffix = suffix || ''; decimals = decimals || 0;
    if (reduce) { el.textContent = to.toFixed(decimals) + suffix; return; }
    var start = null, dur = 1400;
    function step(t) {
      if (!start) start = t;
      var p = Math.min(1, (t - start) / dur), e = 1 - Math.pow(1 - p, 3);
      el.textContent = (to * e).toFixed(decimals) + suffix;
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  w.siteCount = countUp;

  /* ── Typing loops: data-type="first|second|third" ── */
  function typer(el) {
    var lines = (el.getAttribute('data-type') || '').split('|'), li = 0, ci = 0, del = false;
    el.classList.add('blink-cursor');
    function tick() {
      var host = el.closest('.mk');
      if (host && !host.classList.contains('in')) return setTimeout(tick, 400);
      var s = lines[li] || '';
      if (!del) {
        ci++; el.textContent = s.slice(0, ci);
        if (ci >= s.length) { del = true; return setTimeout(tick, 2200); }
        return setTimeout(tick, 45 + Math.random() * 55);
      }
      ci = Math.max(0, ci - 2); el.textContent = s.slice(0, ci);
      if (ci === 0) { del = false; li = (li + 1) % lines.length; return setTimeout(tick, 380); }
      setTimeout(tick, 22);
    }
    if (reduce) { el.textContent = lines[0]; return; }
    tick();
  }
  $$('[data-type]').forEach(typer);

  /* ── Cycling highlight: data-cycle on a parent, children get .on in turn ── */
  $$('[data-cycle]').forEach(function (box) {
    var kids = $$('[data-ci]', box), i = 0, ms = Number(box.getAttribute('data-cycle')) || 2200;
    if (!kids.length || reduce) return;
    setInterval(function () {
      var host = box.closest('.mk');
      if (host && !host.classList.contains('in')) return;
      kids.forEach(function (k) { k.classList.remove('on'); });
      i = (i + 1) % kids.length; kids[i].classList.add('on');
      var tgt = box.getAttribute('data-cycle-target');
      if (tgt) {
        var label = kids[i].getAttribute('data-ci');
        $$(tgt).forEach(function (t) { t.textContent = label; });
      }
    }, ms);
  });

  /* ── Scroll reveal + live mockups ── */
  if ('IntersectionObserver' in w) {
    var once = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (!e.isIntersecting) return;
        var el = e.target;
        el.classList.add('in');
        once.unobserve(el);
        var c = el.getAttribute('data-count');
        if (c !== null) countUp(el, c, el.getAttribute('data-suffix'), Number(el.getAttribute('data-dec')) || 0);
      });
    }, { threshold: 0.14, rootMargin: '0px 0px -6% 0px' });
    $$('.rv, .steps, [data-count]').forEach(function (el) { once.observe(el); });

    var live = new IntersectionObserver(function (es) {
      es.forEach(function (e) { e.target.classList.toggle('in', e.isIntersecting); });
    }, { threshold: 0.08 });
    $$('.mk').forEach(function (el) { live.observe(el); });
  } else {
    $$('.rv, .steps, .mk').forEach(function (el) { el.classList.add('in'); });
  }

  /* ── Tabs: [data-tabs] > [data-tab=i] buttons + [data-panel=i] panels ── */
  $$('[data-tabs]').forEach(function (root) {
    var btns = $$('[data-tab]', root), panels = $$('[data-panel]', root);
    var auto = Number(root.getAttribute('data-auto')) || 0, timer = null, visible = false, cur = 0;
    root.style.setProperty('--tab-dur', (auto / 1000) + 's');
    function show(i, fromUser) {
      cur = i;
      btns.forEach(function (b, k) {
        b.classList.toggle('is-active', k === i);
        b.setAttribute('aria-selected', k === i ? 'true' : 'false');
        b.classList.remove('is-auto');
      });
      panels.forEach(function (p, k) { p.classList.toggle('is-active', k === i); });
      if (fromUser) { auto = 0; clearTimeout(timer); return; }
      if (auto && visible) {
        void btns[i].offsetWidth; btns[i].classList.add('is-auto');
        clearTimeout(timer);
        timer = setTimeout(function () { show((cur + 1) % btns.length); }, auto);
      }
    }
    btns.forEach(function (b, i) {
      b.addEventListener('click', function () {
        show(i, true);
        var bar = b.parentElement;
        if (bar && bar.scrollWidth > bar.clientWidth) bar.scrollTo({ left: b.offsetLeft - 40, behavior: 'smooth' });
      });
    });
    if (auto && 'IntersectionObserver' in w && !reduce) {
      new IntersectionObserver(function (es) {
        es.forEach(function (e) {
          visible = e.isIntersecting;
          if (visible && auto) show(cur); else clearTimeout(timer);
        });
      }, { threshold: 0.3 }).observe(root);
    }
    show(0, false);
  });

  /* ── FAQ: one open at a time + category chips ── */
  $$('[data-faq]').forEach(function (root) {
    var items = $$('.faq-item', root);
    items.forEach(function (it) {
      it.addEventListener('toggle', function () {
        if (it.open) items.forEach(function (o) { if (o !== it) o.open = false; });
      });
    });
    var chips = $$('[data-cat-btn]', root);
    function filter(cat) {
      items.forEach(function (it) {
        var show = cat === 'all' || it.getAttribute('data-cat') === cat;
        it.style.display = show ? '' : 'none';
        it.open = false;
      });
    }
    chips.forEach(function (c) {
      c.addEventListener('click', function () {
        chips.forEach(function (x) { x.classList.remove('is-active'); });
        c.classList.add('is-active');
        filter(c.getAttribute('data-cat-btn'));
      });
    });
    var first = chips.filter(function (c) { return c.classList.contains('is-active'); })[0];
    if (first) filter(first.getAttribute('data-cat-btn'));
  });

  /* ── Filter chips for card directories: [data-filter] ── */
  $$('[data-filter]').forEach(function (root) {
    var chips = $$('[data-f]', root), cards = $$('[data-fv]', root);
    chips.forEach(function (c) {
      c.addEventListener('click', function () {
        chips.forEach(function (x) { x.classList.remove('is-active'); });
        c.classList.add('is-active');
        var v = c.getAttribute('data-f');
        cards.forEach(function (k) { k.classList.toggle('is-out', v !== 'all' && k.getAttribute('data-fv') !== v); });
      });
    });
  });

  /* ── Toast ── */
  w.siteToast = function (title, msg, ok) {
    var t = $('#siteToast'); if (!t) return;
    $('.toast-t', t).textContent = title;
    $('.toast-m', t).textContent = msg;
    t.classList.toggle('is-err', ok === false);
    var ic = $('.toast-ic', t);
    if (ic) ic.innerHTML = ok === false
      ? '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>'
      : '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
    t.classList.add('is-show');
    clearTimeout(t._h);
    t._h = setTimeout(function () { t.classList.remove('is-show'); }, 4500);
  };

  /* ── Ask pill → opens a WhatsApp chat with the typed question ── */
  var ask = $('#askPill');
  if (ask) {
    var inp = $('input', ask), num = ask.getAttribute('data-wa');
    inp.addEventListener('input', function () { ask.classList.toggle('has-text', inp.value.trim() !== ''); });
    ask.addEventListener('submit', function (e) {
      e.preventDefault();
      var q = inp.value.trim();
      w.open('https://wa.me/' + num + (q ? '?text=' + encodeURIComponent(q) : ''), '_blank', 'noopener');
      inp.value = ''; ask.classList.remove('has-text');
    });
    // Out of the way while a form is being filled or the footer is in view.
    var foot = $('.footer');
    if ('IntersectionObserver' in w && foot) {
      new IntersectionObserver(function (es) {
        es.forEach(function (e) { ask.classList.toggle('is-hidden', e.isIntersecting); });
      }, { threshold: 0.05 }).observe(foot);
    }
    d.addEventListener('focusin', function (e) {
      if (e.target.closest && e.target.closest('form') && !e.target.closest('#askPill')) ask.classList.add('is-hidden');
    });
    d.addEventListener('focusout', function (e) {
      if (e.target.closest && e.target.closest('form') && !e.target.closest('#askPill')) ask.classList.remove('is-hidden');
    });
  }
})();
