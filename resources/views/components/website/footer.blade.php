{{-- ══════════════════════════════════════
     COMMON FOOTER — FULL GRID
     (about, homepage, privacy, terms pages)
══════════════════════════════════════ --}}
{{-- Grid override so the extra "Company" column fits on every page.
     Placed here (after the per-page <head> CSS) so it wins by source order;
     full responsive rules included so mobile layouts stay intact. --}}
<style>
  footer .footer-grid { grid-template-columns: 2fr 1fr 1fr 1fr 1fr; gap: 40px; }
  @media (max-width: 768px) { footer .footer-grid { grid-template-columns: 1fr 1fr; gap: 36px; } }
  @media (max-width: 480px) { footer .footer-grid { grid-template-columns: 1fr; } }

  /* ══════════════════ GLOBAL SCROLL REVEAL ══════════════════
     Applied automatically (by the script at the end of this partial) to content
     blocks as they scroll into view, so every page gets strong, consistent
     motion. The homepage is skipped — it already has its own reveal system.
     Classes are removed once the entry animation finishes, so hover transforms
     on cards keep working normally. */
     Uses real 3D transforms (perspective + rotateX/rotateY + translateZ) so blocks
     tilt up out of the page as they enter — gives the whole site depth. */
  .ed-anim-on {
    opacity: 0;
    transition: opacity .85s cubic-bezier(.16, 1, .3, 1), transform .85s cubic-bezier(.16, 1, .3, 1);
    will-change: opacity, transform;
    transform-style: preserve-3d;
    backface-visibility: hidden;
  }
  .ed-anim-on.ed-up    { transform: perspective(1400px) rotateX(14deg) translateY(46px) translateZ(-60px); transform-origin: center top; }
  .ed-anim-on.ed-left  { transform: perspective(1400px) rotateY(-18deg) translateX(-60px) translateZ(-60px); transform-origin: left center; }
  .ed-anim-on.ed-right { transform: perspective(1400px) rotateY(18deg) translateX(60px) translateZ(-60px); transform-origin: right center; }
  .ed-anim-on.ed-zoom  { transform: perspective(1400px) rotateX(16deg) scale(.9) translateY(34px); transform-origin: center top; }
  .ed-anim-on.ed-flip  { transform: perspective(1400px) rotateX(34deg) translateY(40px); transform-origin: center top; }
  .ed-anim-on.ed-shown { opacity: 1; transform: perspective(1400px) rotateX(0) rotateY(0) translate3d(0,0,0) scale(1); }   /* must stay after the variants */
  @media (prefers-reduced-motion: reduce) {
    .ed-anim-on { opacity: 1 !important; transform: none !important; transition: none !important; }
  }
</style>
<footer>
  
    <div style="max-width:1280px;margin:0 auto;">
        <div class="footer-grid">

            {{-- Brand --}}
            <div>
                <a href="{{ url('/') }}"
                    style="text-decoration:none;display:flex;align-items:center;gap:10px;margin-bottom:2px;">
                    <div class="flex-shrink-0 flex">
                        <img src="{{ asset('website-image/Group 11525.png') }}" alt="Logo"
                            class="w-12 h-12 object-contain mb-2" style="width:48px;height:48px;object-fit:contain;">
                    </div>
                    <div class="logo-text">SUPER<span>LMS</span></div>
                </a>
                <p class="footer-brand-desc">SUPERLMS is India's leading affordable Learning Management System for
                    schools and educational institutions. Trusted by many schools across India.</p>
                <div class="footer-socials">
                    <a class="social-btn"
                        href="https://www.instagram.com/superlms.in?igsh=b2Y4dHhnbWFycWs4"
                        target="_blank" rel="noopener noreferrer" title="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                        </svg>
                    </a>
                    <a class="social-btn" href="https://whatsapp.com/channel/0029Vb8GOxcK5cD7a9WYCM0N"
                        target="_blank" rel="noopener noreferrer" title="WhatsApp">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                    </a>
                    <a class="social-btn" href="https://youtube.com/@superlms.education?si=8-lsDNs_TXAyC9qD" target="_blank"
                        rel="noopener noreferrer" title="YouTube">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                        </svg>
                    </a>
                    <a class="social-btn" href="mailto:support@superlms.in" title="Email">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M0 3v18h24V3H0zm21.518 2L12 12.713 2.482 5h19.036zM2 19V6.183l10 8.104 10-8.104V19H2z" />
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Quick Links --}}
            <div>
                <div class="footer-col-title">Quick Links</div>
                <ul class="footer-links">
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li><a href="{{ url('web/about') }}">About Us</a></li>
                    <li><a href="{{ url('web/features') }}">Features</a></li>
                    <li><a href="{{ url('web/pricing') }}">Pricing</a></li>
                    <li><a href="{{ url('web/contact') }}">Contact Us</a></li>
                    <li><a href="{{ url('web/demo') }}">Request Demo</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div>
                <div class="footer-col-title">Company</div>
                <ul class="footer-links">
                    <li><a href="{{ route('website.why-us') }}">Why Us</a></li>
                    <li><a href="{{ route('website.services') }}">Services</a></li>
                    <li><a href="{{ route('website.careers') }}">Careers</a></li>
                    <li><a href="{{ route('website.become-executive') }}">Become an Executive</a></li>
                    <li><a href="{{ route('website.blogs') }}">Blogs</a></li>
                    <li><a href="{{ route('website.faqs') }}">FAQs</a></li>
                </ul>
            </div>

            {{-- Legal --}}
            <div>
                <div class="footer-col-title">Legal</div>
                <ul class="footer-links">
                    <li><a href="{{ route('website.privacy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('website.terms-conditions') }}">Terms &amp; Conditions</a></li>
                    <li><a href="{{ route('website.terms-of-use') }}">Terms of Use</a></li>
                </ul>
            </div>

            {{-- Contact --}}
            <div>
                <div class="footer-col-title">Contact</div>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <a href="mailto:support@superlms.in"
                        style="display:flex;gap:8px;align-items:flex-start;color:var(--text3);font-size:12px;text-decoration:none;"
                        onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                        <span>📧</span><span>support@superlms.in</span>
                    </a>
                    <div style="display:flex;gap:8px;align-items:flex-start;color:var(--text3);font-size:12px;">
                        <span>📱</span><span>+91 9084748563</span>
                    </div>
                    <div style="display:flex;gap:8px;align-items:flex-start;color:var(--text3);font-size:12px;">
                        <span>📍</span><span>House No.02, Braj Vihar colony Jattari, Khair, Aligarh, UP 202137</span>
                    </div>
                </div>
            </div>

        </div>
        <div class="footer-bottom">
            <div class="footer-bottom-text">© 2026 SUPERLMS (Super Learnings Private Limited). All rights reserved. Made with ❤️ in India.</div>
            <div class="footer-bottom-links">
                <a href="{{ route('website.privacy') }}">Privacy Policy</a>
                <a href="{{ route('website.terms-conditions') }}">Terms &amp; Conditions</a>
                <a href="{{ route('website.terms-of-use') }}">Terms of Use</a>
            </div>
        </div>
    </div>
</footer>

{{-- ══════════════════ GLOBAL SCROLL-REVEAL ENGINE ══════════════════
     Auto-tags content blocks and reveals them on scroll. Runs on every page
     except the homepage (which has its own system). Elements already in view on
     load are left untouched (no flash). One-shot: classes are cleaned up after
     the animation so hover effects stay intact. --}}
<script>
  (function () {
    if (!('IntersectionObserver' in window)) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    // Homepage already ships its own .reveal system — don't double up.
    if (document.querySelector('.hero-section')) return;

    function init() {
      var SEL = '.section-head, .feature-card, .module-card, .card-about-item, .card-wmodel, '
        + '.team-card, .story-highlight, .info-card, .trust-badge, .feature-row, .blog-card, '
        + '.recent-card, .testimonial-card, .stat-card, .app-card, .module-mini, .role-card, '
        + '.hiw-card, .step-card, .job-card, .team-card-leader, .faq-item, .do-split, .apply-wrap, '
        + '.faq-chips, .svc-nav, .wu-feature-chips, .article-wrap, .recent-wrap, '
        + '.svc-section, .wu-section, .cta-card, .earn-banner, '
        + '.form-row, .price-card, .pricing-card, .job-pill, .wmodel-num';
      var EXCLUDE = '.navbar, header, .hero-section, footer, .page-header';
      // Skip anything already handled by the homepage reveal classes, just in case.
      var SKIP = '.reveal, .reveal-left, .reveal-right, .reveal-scale, .stagger-children';
      var vh = window.innerHeight || document.documentElement.clientHeight;

      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (!e.isIntersecting) return;
          var el = e.target;
          io.unobserve(el);
          el.classList.add('ed-shown');
          var cleaned = false;
          var done = function (ev) {
            if (ev && ev.target !== el) return;        // ignore bubbling child transitions
            if (cleaned) return;
            cleaned = true;
            el.classList.remove('ed-anim-on', 'ed-up', 'ed-left', 'ed-right', 'ed-zoom', 'ed-shown');
            el.style.transitionDelay = '';
            el.removeEventListener('transitionend', done);
          };
          el.addEventListener('transitionend', done);
          setTimeout(done, 1800);                        // fallback if transitionend never fires
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

      document.querySelectorAll(SEL).forEach(function (el) {
        if (el.closest(EXCLUDE) || el.closest(SKIP)) return;
        if (el.classList.contains('ed-anim-on')) return;
        // Don't animate a block that sits inside an already-tagged block — avoids
        // nested tilts fighting each other (parents are tagged first in DOM order).
        if (el.parentElement && el.parentElement.closest('.ed-anim-on')) return;
        if (el.getBoundingClientRect().top < vh * 0.88) return;   // already on screen → no flash

        var variant = 'ed-up';
        if (el.classList.contains('cta-card') || el.classList.contains('earn-banner')) {
          variant = 'ed-zoom';
        } else if (el.classList.contains('svc-section') || el.classList.contains('wu-section')) {
          variant = el.classList.contains('reverse') ? 'ed-right' : 'ed-left';
        } else if (el.classList.contains('section-head')) {
          variant = 'ed-flip';
        }

        var idx = 0, sib = el.previousElementSibling;
        while (sib) { idx++; sib = sib.previousElementSibling; }
        var delay = Math.min(idx, 7) * 70;

        el.classList.add('ed-anim-on', variant);
        if (delay) el.style.transitionDelay = delay + 'ms';
        io.observe(el);
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  })();
</script>
