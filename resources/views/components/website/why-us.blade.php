@php
    $def      = config('website_pages.why-us', []);
    // Header copy from config; the detailed reason sections below are fixed content.
    $tag      = $def['tag']      ?? 'Why SUPERLMS';
    $title    = $def['title']    ?? '';
    $subtitle = $def['subtitle'] ?? '';

    // Quick "reasons at a glance" cards.
    $reasons = [
        ['icon' => '🧩', 'title' => 'All-in-One', 'desc' => 'Software plus everything your campus needs — in one partner.'],
        ['icon' => '💰', 'title' => 'Genuinely Affordable', 'desc' => 'Transparent per-student pricing with no hidden setup fees.'],
        ['icon' => '🔁', 'title' => 'Hybrid Web + App', 'desc' => 'Full power on the web, dedicated apps on every phone.'],
        ['icon' => '🛠️', 'title' => 'We Set It Up', 'desc' => 'Integration and data upload handled entirely by our team.'],
        ['icon' => '🛟', 'title' => 'Real Human Support', 'desc' => 'Onboarding, training and help over call, chat & WhatsApp.'],
        ['icon' => '🇮🇳', 'title' => 'Built for India', 'desc' => 'Designed around Indian boards, fees and school workflows.'],
    ];

    // Feature chips (mirrors the Services range).
    $features = [
        '🎓 School LMS', '📝 Admissions', '🗓️ Attendance & Timetable', '📊 Exams & Report Cards',
        '💳 Online Fees', '📚 Study Material & Quizzes', '🔔 Notifications', '🆔 ID Cards',
        '🏦 School Loans', '🔬 Labs Setup', '🖥️ Smart Classrooms', '🚌 Smart Transport',
        '👕 Uniforms & Books', '💻 Online Education',
    ];
@endphp
@include('components.website.partials.head', ['title' => 'Why Us'])

<style>
  /* ── Reason detail sections (alternating full-width bands) ── */
  .wu-section { max-width:1100px; margin:0 auto; display:grid; grid-template-columns:1fr 1fr;
    gap:56px; align-items:center; }
  .wu-section.reverse .wu-visual { order:2; }
  .wu-eyebrow { display:inline-flex; align-items:center; gap:8px; font-size:12px; font-weight:700; letter-spacing:1px;
    text-transform:uppercase; color:var(--violet); background:var(--secondary-faint); border:1px solid var(--border);
    padding:5px 14px; border-radius:50px; margin-bottom:16px; }
  .wu-title { font-family:'Cormorant Garamond',serif; font-size:clamp(26px,3.2vw,38px); font-weight:700;
    color:var(--text); line-height:1.15; margin-bottom:14px; }
  .wu-desc { font-size:15px; color:var(--text3); line-height:1.85; margin-bottom:20px; }
  .wu-points { list-style:none; display:grid; gap:11px; }
  .wu-points li { display:flex; gap:11px; align-items:flex-start; font-size:14px; color:var(--text2); line-height:1.55; }
  .wu-check { flex-shrink:0; width:22px; height:22px; border-radius:7px; background:var(--secondary-faint);
    color:var(--violet); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; }

  .wu-visual { position:relative; border-radius:28px; min-height:300px; padding:36px;
    background:linear-gradient(135deg,var(--secondary-faint),var(--primary-faint)); border:1px solid var(--border);
    display:flex; align-items:center; justify-content:center; overflow:hidden; }
  .wu-visual::before { content:''; position:absolute; width:240px; height:240px; border-radius:50%;
    background:radial-gradient(circle,rgba(111,86,254,.16),transparent 70%); top:-70px; right:-60px; }
  .wu-emoji { font-size:120px; line-height:1; position:relative; z-index:1; filter:drop-shadow(0 14px 30px rgba(111,86,254,.25)); }
  .wu-badge { position:absolute; z-index:2; background:#fff; border:1px solid var(--border2); border-radius:14px;
    padding:9px 14px; box-shadow:0 12px 30px rgba(31,31,60,.12); font-size:12px; font-weight:700; color:var(--text);
    display:flex; align-items:center; gap:8px; }
  .wu-badge .sb-sub { font-size:10px; font-weight:500; color:var(--text3); }
  .wu-badge.b1 { top:26px; left:22px; }
  .wu-badge.b2 { bottom:26px; right:22px; }

  /* ── Feature chips ── */
  .wu-feature-chips { display:flex; flex-wrap:wrap; gap:10px; margin-top:6px; }
  .wu-feature-chips span { font-size:12.5px; font-weight:600; color:var(--text2); background:#fff;
    border:1px solid var(--border2); border-radius:50px; padding:7px 14px; }

  /* ── Price highlight ── */
  .wu-price { text-align:center; }
  .wu-price-amount { font-family:'Cormorant Garamond',serif; font-size:60px; font-weight:700; line-height:1;
    background:var(--grad1); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
  .wu-price-sub { font-size:13px; color:var(--text3); margin-top:8px; }

  @media (max-width:860px) {
    .wu-section { grid-template-columns:1fr; gap:30px; }
    .wu-section.reverse .wu-visual { order:0; }
    .wu-visual { min-height:230px; }
    .wu-emoji { font-size:90px; }
  }
</style>

  @include('components.website.header')

  {{-- ══════════════════ PAGE HEADER ══════════════════ --}}
  <section class="page-header">
    <div class="grid-bg"></div>
    <div class="page-header-content">
      <span class="section-tag tag-dual">★ {{ $tag }}</span>
      <h1 class="section-title">{{ $title }}</h1>
      <p class="section-subtitle">{{ $subtitle }}</p>
    </div>
  </section>

  {{-- ══════════════════ REASONS AT A GLANCE ══════════════════ --}}
  <section class="section" style="padding-bottom:24px;">
    <div class="cards-grid">
      @foreach ($reasons as $r)
      <div class="feature-card">
        <div class="feature-icon-wrap">{{ $r['icon'] }}</div>
        <h3 class="feature-title">{{ $r['title'] }}</h3>
        <p class="feature-desc">{{ $r['desc'] }}</p>
      </div>
      @endforeach
    </div>
  </section>

  {{-- ══════════════════ DETAILED REASONS ══════════════════ --}}
  {{-- 1. Everything in one place (all services / features) --}}
  <section class="section section-alt">
    <div class="wu-section">
      <div class="wu-visual">
        <span class="wu-emoji">🧩</span>
        <div class="wu-badge b1">🎓 50+ Modules</div>
        <div class="wu-badge b2">🤝 One Partner</div>
      </div>
      <div class="wu-content">
        <span class="wu-eyebrow">01 · One Partner, Everything</span>
        <h2 class="wu-title">Everything your school needs — in one place</h2>
        <p class="wu-desc">Most schools juggle five different vendors for software, ID cards, labs, transport and more.
          With SUPERLMS you get it all from a single trusted partner. Our LMS covers the entire academic workflow, and our
          services equip the rest of your campus — so nothing falls through the cracks.</p>
        <div class="wu-feature-chips">
          @foreach ($features as $f)<span>{{ $f }}</span>@endforeach
        </div>
        <div style="margin-top:22px;">
          <a href="{{ url('web/services') }}" class="btn btn-outline">Explore all services →</a>
        </div>
      </div>
    </div>

  </section>

  {{-- 2. Affordable pricing --}}
  <section class="section">
    <div class="wu-section reverse">
      <div class="wu-visual">
        <div class="wu-price">
          <div class="wu-price-amount">₹250</div>
          <div class="wu-price-sub">per student / year · all 50+ modules</div>
        </div>
        <div class="wu-badge b1">🚫 No Hidden Fees</div>
        <div class="wu-badge b2">🎁 Free Onboarding</div>
      </div>
      <div class="wu-content">
        <span class="wu-eyebrow">02 · Pricing</span>
        <h2 class="wu-title">Genuinely affordable — and transparent</h2>
        <p class="wu-desc">World-class school technology should not cost a fortune. SUPERLMS is priced so that schools of
          every size can afford it — one simple plan, billed per student, with every module included. No per-feature
          add-ons, no setup charges and absolutely no hidden fees.</p>
        <ul class="wu-points">
          <li><span class="wu-check">✓</span>One simple plan that unlocks all 50+ modules</li>
          <li><span class="wu-check">✓</span>Just ₹250 per student a year — billed annually</li>
          <li><span class="wu-check">✓</span>Free onboarding, data migration &amp; staff training included</li>
          <li><span class="wu-check">✓</span>Custom Enterprise pricing for large multi-campus groups</li>
        </ul>
        <div style="margin-top:22px;">
          <a href="{{ url('web/pricing') }}" class="btn btn-outline">View full pricing →</a>
        </div>
      </div>
    </div>

  </section>

  {{-- 3. Hybrid web + mobile --}}
  <section class="section section-alt">
    <div class="wu-section">
      <div class="wu-visual">
        <span class="wu-emoji">🔁</span>
        <div class="wu-badge b1">💻 Web Dashboard</div>
        <div class="wu-badge b2">📱 <div><div>4 Mobile Apps</div><div class="sb-sub">Admin · Teacher · Student · Parent</div></div></div>
      </div>
      <div class="wu-content">
        <span class="wu-eyebrow">03 · Hybrid Platform</span>
        <h2 class="wu-title">Powerful on the web, brilliant on mobile</h2>
        <p class="wu-desc">SUPERLMS is truly hybrid. Your office staff get a full-featured web dashboard for heavy
          administrative work, while teachers, students and parents stay connected through dedicated mobile apps. The
          same live data, everywhere — no compromises, no separate logins.</p>
        <ul class="wu-points">
          <li><span class="wu-check">✓</span>Complete web dashboard for admissions, fees, exams &amp; reports</li>
          <li><span class="wu-check">✓</span>Dedicated Android &amp; iOS apps for every role</li>
          <li><span class="wu-check">✓</span>Real-time sync — update once, see it everywhere</li>
          <li><span class="wu-check">✓</span>Mark attendance, pay fees and check results on the go</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- 4. Integration & data upload --}}
  <section class="section">
    <div class="wu-section reverse">
      <div class="wu-visual">
        <span class="wu-emoji">🛠️</span>
        <div class="wu-badge b1">📤 We Upload Your Data</div>
        <div class="wu-badge b2">⚡ Live in Days</div>
      </div>
      <div class="wu-content">
        <span class="wu-eyebrow">04 · Setup &amp; Integration</span>
        <h2 class="wu-title">We handle the integration &amp; data upload</h2>
        <p class="wu-desc">Switching systems sounds scary — but with SUPERLMS you don't lift a finger. Our team takes your
          existing records and uploads everything for you: students, staff, classes, fee structures and more. We
          integrate, configure and verify, so your school goes live smoothly without disrupting daily work.</p>
        <ul class="wu-points">
          <li><span class="wu-check">✓</span>Full data migration from spreadsheets or your old system</li>
          <li><span class="wu-check">✓</span>Classes, sections, subjects &amp; fee structures configured for you</li>
          <li><span class="wu-check">✓</span>Integration with payments, SMS &amp; communication channels</li>
          <li><span class="wu-check">✓</span>Most schools go live within a few days — at no extra cost</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- 5. Human support --}}
  <section class="section section-alt">
    <div class="wu-section">
      <div class="wu-visual">
        <span class="wu-emoji">🛟</span>
        <div class="wu-badge b1">📞 Call · Chat · WhatsApp</div>
        <div class="wu-badge b2">🧑‍🏫 Staff Training</div>
      </div>
      <div class="wu-content">
        <span class="wu-eyebrow">05 · Support</span>
        <h2 class="wu-title">Real humans who understand schools</h2>
        <p class="wu-desc">You're never left figuring things out alone. From day one you get hands-on onboarding, staff
          training and friendly ongoing support from a team that genuinely understands how Indian schools run — reachable
          over call, chat and WhatsApp whenever you need them.</p>
        <ul class="wu-points">
          <li><span class="wu-check">✓</span>Guided onboarding &amp; live staff training sessions</li>
          <li><span class="wu-check">✓</span>Support over call, chat and WhatsApp — not just email tickets</li>
          <li><span class="wu-check">✓</span>A real team that knows schools, not a generic call centre</li>
          <li><span class="wu-check">✓</span>Regular updates &amp; new features at no extra charge</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- 6. Built for India --}}
  <section class="section">
    <div class="wu-section reverse">
      <div class="wu-visual">
        <span class="wu-emoji">🇮🇳</span>
        <div class="wu-badge b1">🏫 Indian Boards</div>
        <div class="wu-badge b2">💵 Indian Fee Structures</div>
      </div>
      <div class="wu-content">
        <span class="wu-eyebrow">06 · Built for India</span>
        <h2 class="wu-title">Designed for Indian schools, not adapted</h2>
        <p class="wu-desc">SUPERLMS is built from the ground up around how Indian schools actually work — your boards, your
          fee cycles, your report cards and your day-to-day realities. It's not a foreign product forced to fit; it's
          made here, for schools like yours, and improved with feedback from real classrooms every day.</p>
        <ul class="wu-points">
          <li><span class="wu-check">✓</span>Workflows tuned to Indian boards &amp; academic calendars</li>
          <li><span class="wu-check">✓</span>Flexible fee structures, concessions &amp; receipts</li>
          <li><span class="wu-check">✓</span>Trusted by hundreds of schools across the country</li>
          <li><span class="wu-check">✓</span>Shaped by direct feedback from real schools</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- ══════════════════ CTA ══════════════════ --}}
  <section class="cta-section">
    <div class="cta-bg"></div>
    <div class="cta-card">
      <h2 class="cta-title">See why schools switch to <span class="gradient-text">SUPERLMS</span></h2>
      <p class="cta-desc">Book a free, no-obligation demo and we'll show you exactly how SUPERLMS works for your school —
        from the software to the services that equip your whole campus.</p>
      <div class="cta-actions">
        <a href="{{ url('web/demo') }}" class="btn btn-primary btn-xl">Request a Demo</a>
        <a href="{{ url('web/contact') }}" class="btn btn-outline btn-xl">Talk to Us</a>
      </div>
    </div>
  </section>

  @include('components.website.app-section')
  @include('components.website.footer')
</body>
</html>
