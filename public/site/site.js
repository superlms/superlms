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

  /* ── Hero: the main features, swiping ([data-hero-slider], 2026-11-19) ──
     The next slide comes when the current bar has filled (its animation's
     end), so pausing the bar (pointer over it, or off screen) pauses the
     slider too. Swipe or drag sideways, pick a pill, or use the arrow keys. */
  $$('[data-hero-slider]').forEach(function (root) {
    var frame = $('.hs-frame', root), track = $('.hs-track', root);
    var slides = $$('.hs-slide', root), bars = $$('.hs-bars i', root), tabs = $$('[data-hs-go]', root);
    var n = slides.length, cur = 0;
    if (!frame || !track || n < 2) { if (slides[0]) slides[0].classList.add('is-on'); return; }
    root.style.setProperty('--hs-dur', '5.5s');

    function show(i) {
      cur = (i + n) % n;
      track.style.transform = 'translateX(' + (-cur * 100) + '%)';
      slides.forEach(function (s, k) {
        s.classList.toggle('is-on', k === cur);
        s.setAttribute('aria-hidden', k === cur ? 'false' : 'true');
      });
      bars.forEach(function (b, k) { b.classList.remove('is-on'); b.classList.toggle('is-done', k < cur); });
      var bar = bars[cur];
      if (bar) { void bar.offsetWidth; bar.classList.add('is-on'); }
      tabs.forEach(function (t, k) {
        t.classList.toggle('is-on', k === cur);
        t.setAttribute('aria-selected', k === cur ? 'true' : 'false');
      });
    }
    bars.forEach(function (b, k) {
      var fill = b.firstElementChild;
      if (fill) fill.addEventListener('animationend', function () { if (k === cur) show(cur + 1); });
    });
    tabs.forEach(function (t) {
      t.addEventListener('click', function () { show(parseInt(t.getAttribute('data-hs-go'), 10) || 0); });
    });

    // Paused under the pointer, and while off screen.
    var hover = false, away = false;
    function sync() { root.classList.toggle('is-paused', hover || away); }
    frame.addEventListener('mouseenter', function () { hover = true; sync(); });
    frame.addEventListener('mouseleave', function () { hover = false; sync(); });
    if ('IntersectionObserver' in w) {
      new IntersectionObserver(function (es) { away = !es[0].isIntersecting; sync(); }).observe(root);
    }

    // Swipe / drag sideways.
    var x0 = null, dx = 0, width = 1;
    frame.addEventListener('pointerdown', function (e) {
      if (e.pointerType === 'mouse' && e.button !== 0) return;
      x0 = e.clientX; dx = 0; width = frame.offsetWidth || 1;
      root.classList.add('is-drag');
      try { frame.setPointerCapture(e.pointerId); } catch (err) {}
    });
    frame.addEventListener('pointermove', function (e) {
      if (x0 === null) return;
      dx = e.clientX - x0;
      track.style.transform = 'translateX(calc(' + (-cur * 100) + '% + ' + dx + 'px))';
    });
    function end() {
      if (x0 === null) return;
      x0 = null;
      root.classList.remove('is-drag');
      show(Math.abs(dx) > Math.min(80, width * .15) ? cur + (dx < 0 ? 1 : -1) : cur);
    }
    frame.addEventListener('pointerup', end);
    frame.addEventListener('pointercancel', end);
    root.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { show(cur + 1); e.preventDefault(); }
      if (e.key === 'ArrowLeft') { show(cur - 1); e.preventDefault(); }
    });

    show(0);
  });

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

  /* ── "Ask anything": a chat answered from this website (POST /api/website/ask).
        The panel opens above the pill; the conversation is kept for the tab.
        When there is no answer, WhatsApp is offered with the same question. ── */
  var ask = $('#askPill'), panel = $('#askPanel');
  if (ask && panel) {
    var inp = $('input', ask), sendBtn = $('button', ask), num = ask.getAttribute('data-wa');
    var body = $('#askBody'), intro = $('#askIntro'), logoSrc = ($('.askp-logo img', panel) || {}).src || '';
    var KEY = 'sl_ask_v1', busy = false, hist = [], footerInView = false;

    try { hist = JSON.parse(sessionStorage.getItem(KEY) || '[]') || []; } catch (e) { hist = []; }
    function save() { try { sessionStorage.setItem(KEY, JSON.stringify(hist.slice(-20))); } catch (e) {} }

    function esc(s) { return w.siteEsc(s); }
    // Markdown-lite: **bold**, [label](link) to this site / WhatsApp / Play / tel / mail, "- " lists.
    function md(s) {
      var h = esc(s);
      h = h.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, function (m, t, u) {
        var raw = u.replace(/&amp;/g, '&');
        if (!/^(\/(?!\/)|https:\/\/(www\.)?(superlms\.in|wa\.me|play\.google\.com)(\/|$)|tel:|mailto:)/.test(raw)) return t;
        var ext = /^https?:/.test(raw);
        return '<a href="' + esc(raw) + '"' + (ext ? ' target="_blank" rel="noopener noreferrer"' : '') + '>' + t + '</a>';
      });
      h = h.replace(/\*\*([^*]+)\*\*/g, '<b>$1</b>');
      var out = [], list = false;
      h.split(/\n/).forEach(function (l) {
        var m = l.match(/^\s*(?:[-*•]|\d+\.)\s+(.*)$/);
        if (m) { if (!list) { out.push('<ul>'); list = true; } out.push('<li>' + m[1] + '</li>'); return; }
        if (list) { out.push('</ul>'); list = false; }
        if (l.trim()) out.push('<p>' + l + '</p>');
      });
      if (list) out.push('</ul>');
      return out.join('');
    }

    function scroll() { body.scrollTop = body.scrollHeight; }

    function bubble(role, html) {
      var el = d.createElement('div');
      el.className = 'am ' + (role === 'user' ? 'user' : 'bot');
      el.innerHTML = role === 'user'
        ? '<div class="am-tx">' + html + '</div>'
        : '<span class="am-av"><img src="' + esc(logoSrc) + '" alt=""></span><div class="am-tx">' + html + '</div>';
      body.appendChild(el);
      if (intro) intro.hidden = true;
      scroll();
      return el;
    }

    // Words appear one after another, then the formatted answer takes over.
    function typeOut(el, text) {
      var tx = $('.am-tx', el), words = text.split(/(\s+)/), i = 0;
      if (reduce || words.length < 6) { tx.innerHTML = md(text); scroll(); return; }
      var step = Math.max(1, Math.round(words.length / 70));
      (function tick() {
        i = Math.min(words.length, i + step);
        tx.textContent = words.slice(0, i).join('');
        scroll();
        if (i < words.length) setTimeout(tick, 18); else { tx.innerHTML = md(text); scroll(); }
      })();
    }

    function open() {
      panel.hidden = false;
      ask.classList.add('is-chat');
      ask.classList.remove('is-hidden');
      requestAnimationFrame(function () { panel.classList.add('is-open'); });
      scroll();
    }
    function close() {
      panel.classList.remove('is-open');
      ask.classList.remove('is-chat');
      ask.classList.toggle('is-hidden', footerInView);
      setTimeout(function () { if (!panel.classList.contains('is-open')) panel.hidden = true; }, 260);
    }

    function waLink(q) { return 'https://wa.me/' + num + (q ? '?text=' + encodeURIComponent(q) : ''); }

    function send(q) {
      q = (q || '').trim();
      if (!q || busy) return;
      busy = true; sendBtn.disabled = true;
      open();
      bubble('user', esc(q));
      $('#askWa').href = waLink(q);
      var typing = d.createElement('div');
      typing.className = 'am bot';
      typing.innerHTML = '<span class="am-av"><img src="' + esc(logoSrc) + '" alt=""></span><div class="typing"><i></i><i></i><i></i></div>';
      body.appendChild(typing); scroll();

      fetch('/api/website/ask', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ question: q, history: hist.slice(-8) })
      }).then(function (r) { return r.json().catch(function () { return {}; }); })
        .then(function (j) {
          typing.remove();
          if (j && j.success && j.answer) {
            typeOut(bubble('model', ''), j.answer);
            hist.push({ role: 'user', text: q }, { role: 'model', text: j.answer });
            save();
          } else {
            fail((j && j.message) || 'I could not get an answer just now.', q);
          }
        })
        .catch(function () { typing.remove(); fail('No connection just now. Please try again.', q); })
        .then(function () { busy = false; sendBtn.disabled = false; });
    }

    function fail(msg, q) {
      var el = bubble('model', '<p>' + esc(msg) + '</p>');
      el.classList.add('err');
      var a = d.createElement('a');
      a.className = 'am-wa'; a.href = waLink(q); a.target = '_blank'; a.rel = 'noopener noreferrer';
      a.innerHTML = '<svg class="ic" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2a9.9 9.9 0 0 0-8.5 15l-1.4 5 5.1-1.3A9.9 9.9 0 1 0 12.04 2Zm5.8 14.1c-.2.7-1.4 1.3-2 1.4-.5.1-1.2.1-1.9-.1-.4-.1-1-.3-1.7-.6-3-1.3-4.9-4.3-5.1-4.5-.1-.2-1.2-1.6-1.2-3.1s.8-2.2 1-2.5c.3-.3.6-.4.8-.4h.6c.2 0 .4 0 .6.5l.9 2.2c.1.2.1.4 0 .5l-.3.5-.4.5c-.1.1-.3.3-.1.6.2.3.7 1.1 1.4 1.8 1 .9 1.8 1.1 2.1 1.3.3.1.4.1.6-.1l.9-1.1c.2-.3.4-.2.6-.1l2.2 1c.3.2.5.2.6.4 0 .1 0 .7-.2 1.3Z"/></svg> Ask on WhatsApp';
      $('.am-tx', el).appendChild(a);
      scroll();
    }

    // Restore this tab's conversation (shown when the panel opens).
    hist.forEach(function (t) { bubble(t.role === 'user' ? 'user' : 'model', t.role === 'user' ? esc(t.text) : md(t.text)); });

    inp.addEventListener('input', function () { ask.classList.toggle('has-text', inp.value.trim() !== ''); });
    inp.addEventListener('focus', function () { if (hist.length) open(); });
    ask.addEventListener('submit', function (e) {
      e.preventDefault();
      var q = inp.value;
      inp.value = ''; ask.classList.remove('has-text');
      if (q.trim()) send(q); else open();
    });
    $$('.askp-sug', panel).forEach(function (b) { b.addEventListener('click', function () { send(b.getAttribute('data-q')); }); });
    $('#askClose').addEventListener('click', close);
    $('#askReset').addEventListener('click', function () {
      hist = []; save();
      $$('.am', body).forEach(function (m) { m.remove(); });
      if (intro) intro.hidden = false;
      $('#askWa').href = waLink('');
      inp.focus();
    });
    d.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel.classList.contains('is-open')) close(); });

    // Out of the way while a page form is being filled or the footer is in view (unless chatting).
    var foot = $('.footer');
    if ('IntersectionObserver' in w && foot) {
      new IntersectionObserver(function (es) {
        es.forEach(function (e) {
          footerInView = e.isIntersecting;
          if (!panel.classList.contains('is-open')) ask.classList.toggle('is-hidden', footerInView);
        });
      }, { threshold: 0.05 }).observe(foot);
    }
    d.addEventListener('focusin', function (e) {
      if (e.target.closest && e.target.closest('form') && !e.target.closest('#askPill')) { ask.classList.add('is-hidden'); if (panel.classList.contains('is-open')) close(); }
    });
    d.addEventListener('focusout', function (e) {
      if (e.target.closest && e.target.closest('form') && !e.target.closest('#askPill') && !footerInView) ask.classList.remove('is-hidden');
    });
  }
})();
