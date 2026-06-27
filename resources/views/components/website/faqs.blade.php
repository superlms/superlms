@php
    $def      = config('website_pages.faqs', []);
    $tag      = $def['tag']      ?? 'Help Center';
    $title    = $def['title']    ?? '';
    $subtitle = $def['subtitle'] ?? '';
    $faqs       = $faqs ?? collect();
    $categories = $categories ?? collect();
@endphp
@include('components.website.partials.head', ['title' => 'FAQs'])

<style>
  /* ── FAQ category chips ── */
  .faq-chips { max-width: 1000px; margin: 0 auto 32px; display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
  .faq-chip {
    font-size: 13px; font-weight: 600; padding: 8px 13px; border-radius: 50px;
    border: 1px solid var(--border); background: #fff; color: var(--text2);
    cursor: pointer; transition: all .2s; white-space: nowrap;
  }
  .faq-chip:hover { border-color: var(--violet); color: var(--violet); }
  .faq-chip.active { background: var(--grad1); border-color: transparent; color: #fff; box-shadow: 0 6px 16px rgba(111,86,254,.25); }

  /* ── FAQ list ── */
  .faq-wrap { max-width: 820px; margin: 0 auto; display: flex; flex-direction: column; gap: 14px; }
  .faq-item { background: #fff; border: 1px solid var(--border2); border-radius: var(--radius); overflow: hidden; transition: border-color .2s, box-shadow .2s; }
  .faq-item[open] { border-color: var(--border); box-shadow: var(--shadow3); }
  .faq-q {
    list-style: none; cursor: pointer; padding: 20px 24px;
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    font-size: 15px; font-weight: 600; color: var(--text);
  }
  .faq-q::-webkit-details-marker { display: none; }
  .faq-icon { flex-shrink: 0; width: 26px; height: 26px; border-radius: 50%; background: var(--secondary-faint); color: var(--violet); display: flex; align-items: center; justify-content: center; font-size: 16px; transition: transform .25s; }
  .faq-item[open] .faq-icon { transform: rotate(45deg); background: var(--grad1); color: #fff; }
  .faq-a { padding: 0 24px 22px; font-size: 14px; color: var(--text3); line-height: 1.8; }
  .faq-empty { text-align: center; color: var(--text3); font-size: 14px; padding: 30px 0; }
</style>

  @include('components.website.header')

  {{-- ══════════════════ PAGE HEADER ══════════════════ --}}
  <section class="page-header">
    <div class="grid-bg"></div>
    <div class="page-header-content">
      <span class="section-tag tag-violet">❔ {{ $tag }}</span>
      <h1 class="section-title">{{ $title }}</h1>
      <p class="section-subtitle">{{ $subtitle }}</p>
    </div>
  </section>

  {{-- ══════════════════ FAQ LIST ══════════════════ --}}
  <section class="section">
    @if ($faqs->isEmpty())
      <p class="faq-empty">No FAQs published yet. Please check back soon.</p>
    @else
      {{-- Category chips --}}
      @if ($categories->count() > 1)
      <div class="faq-chips" id="faqChips">
        <button type="button" class="faq-chip active" data-cat="all">All</button>
        @foreach ($categories as $cat)
          <button type="button" class="faq-chip" data-cat="{{ $cat }}">{{ $cat }}</button>
        @endforeach
      </div>
      @endif

      <div class="faq-wrap" id="faqWrap">
        @foreach ($faqs as $faq)
        <details class="faq-item" data-cat="{{ $faq->category }}">
          <summary class="faq-q">{{ $faq->question }}<span class="faq-icon">+</span></summary>
          <div class="faq-a">{!! nl2br(e($faq->answer)) !!}</div>
        </details>
        @endforeach
      </div>
    @endif
  </section>

  {{-- ══════════════════ CTA ══════════════════ --}}
  <section class="cta-section">
    <div class="cta-bg"></div>
    <div class="cta-card">
      <h2 class="cta-title">Still have <span class="gradient-text">questions?</span></h2>
      <p class="cta-desc">Our friendly team is always happy to help you with anything about SUPERLMS — whether it's
        choosing the right plan, setting up your school, migrating your data or understanding a specific feature.
        Reach out over call, chat or WhatsApp and we'll get back to you quickly. You can also book a free, no-obligation
        demo and see exactly how SUPERLMS can work for your institution.</p>
      <div class="cta-actions">
        <a href="{{ url('web/contact') }}" class="btn btn-primary btn-xl">Contact Us</a>
        <a href="{{ url('web/demo') }}" class="btn btn-outline btn-xl">Request a Demo</a>
      </div>
    </div>
  </section>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var items = Array.prototype.slice.call(document.querySelectorAll('#faqWrap .faq-item'));

      // Only one open at a time.
      items.forEach(function (item) {
        item.addEventListener('toggle', function () {
          if (item.open) {
            items.forEach(function (other) { if (other !== item) other.open = false; });
          }
        });
      });

      // Category chip filtering.
      var chips = Array.prototype.slice.call(document.querySelectorAll('#faqChips .faq-chip'));
      chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
          chips.forEach(function (c) { c.classList.remove('active'); });
          chip.classList.add('active');
          var cat = chip.getAttribute('data-cat');
          items.forEach(function (item) {
            item.open = false; // close any open FAQ when switching
            var show = (cat === 'all') || (item.getAttribute('data-cat') === cat);
            item.style.display = show ? '' : 'none';
          });
        });
      });
    });
  </script>

  @include('components.website.app-section')
  @include('components.website.footer')
</body>
</html>
