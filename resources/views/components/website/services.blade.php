@php
    $def      = config('website_pages.services', []);
    // Header copy from config; the detailed service sections below are fixed content.
    $tag      = $def['tag']      ?? 'Our Services';
    $title    = $def['title']    ?? '';
    $subtitle = $def['subtitle'] ?? '';

    $services = [
        ['id' => 'lms',        'icon' => '🎓', 'nav' => 'School LMS'],
        ['id' => 'idcards',    'icon' => '🆔', 'nav' => 'ID Cards'],
        ['id' => 'loans',      'icon' => '🏦', 'nav' => 'School Loans'],
        ['id' => 'labs',       'icon' => '🔬', 'nav' => 'Labs Setup'],
        ['id' => 'smart',      'icon' => '🖥️', 'nav' => 'Smart Classrooms'],
        ['id' => 'transport',  'icon' => '🚌', 'nav' => 'Smart Transport'],
        ['id' => 'essentials', 'icon' => '👕', 'nav' => 'Uniforms & Books'],
        ['id' => 'online',     'icon' => '💻', 'nav' => 'Online Education'],
    ];
@endphp
@include('components.website.partials.head', ['title' => 'Services'])

<style>
  /* ── Quick nav chips ── */
  .svc-nav { max-width:1100px; margin:0 auto 8px; display:flex; flex-wrap:nowrap; gap:8px; justify-content:center;
    overflow-x:auto; padding-bottom:4px; -ms-overflow-style:none; scrollbar-width:none; }
  .svc-nav::-webkit-scrollbar { display:none; }
  .svc-nav a { flex:0 0 auto; white-space:nowrap; font-size:12.5px; font-weight:600; padding:7px 13px; border-radius:50px;
    border:1px solid var(--border); background:#fff; color:var(--text2); text-decoration:none; transition:all .2s; }
  .svc-nav a:hover { border-color:var(--violet); color:var(--violet); transform:translateY(-2px); }

  @media (max-width:760px) { .svc-nav { justify-content:flex-start; } }

  /* ── Service section ── */
  .svc-band { scroll-margin-top:90px; }
  .svc-section { max-width:1100px; margin:0 auto; display:grid; grid-template-columns:1fr 1fr;
    gap:56px; align-items:center; }
  .svc-section.reverse .svc-visual { order:2; }

  .svc-eyebrow { display:inline-flex; align-items:center; gap:8px; font-size:12px; font-weight:700; letter-spacing:1px;
    text-transform:uppercase; color:var(--violet); background:var(--secondary-faint); border:1px solid var(--border);
    padding:5px 14px; border-radius:50px; margin-bottom:16px; }
  .svc-title { font-family:'Cormorant Garamond',serif; font-size:clamp(26px,3.2vw,38px); font-weight:700;
    color:var(--text); line-height:1.15; margin-bottom:14px; }
  .svc-desc { font-size:15px; color:var(--text3); line-height:1.85; margin-bottom:20px; }
  .svc-points { list-style:none; display:grid; gap:11px; }
  .svc-points li { display:flex; gap:11px; align-items:flex-start; font-size:14px; color:var(--text2); line-height:1.55; }
  .svc-check { flex-shrink:0; width:22px; height:22px; border-radius:7px; background:var(--secondary-faint);
    color:var(--violet); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; }

  /* ── Visual card ── */
  .svc-visual { position:relative; border-radius:28px; min-height:300px; padding:36px;
    background:linear-gradient(135deg,var(--secondary-faint),var(--primary-faint)); border:1px solid var(--border);
    display:flex; align-items:center; justify-content:center; overflow:hidden; }
  .svc-visual::before { content:''; position:absolute; width:240px; height:240px; border-radius:50%;
    background:radial-gradient(circle,rgba(111,86,254,.16),transparent 70%); top:-70px; right:-60px; }
  .svc-emoji { font-size:120px; line-height:1; position:relative; z-index:1; filter:drop-shadow(0 14px 30px rgba(111,86,254,.25)); }
  .svc-badge { position:absolute; z-index:2; background:#fff; border:1px solid var(--border2); border-radius:14px;
    padding:9px 14px; box-shadow:0 12px 30px rgba(31,31,60,.12); font-size:12px; font-weight:700; color:var(--text);
    display:flex; align-items:center; gap:8px; }
  .svc-badge .sb-sub { font-size:10px; font-weight:500; color:var(--text3); }
  .svc-badge.b1 { top:26px; left:22px; }
  .svc-badge.b2 { bottom:26px; right:22px; }

  @media (max-width:860px) {
    .svc-section { grid-template-columns:1fr; gap:30px; }
    .svc-section.reverse .svc-visual { order:0; }
    .svc-visual { min-height:230px; }
    .svc-emoji { font-size:90px; }
  }
</style>

  @include('components.website.header')

  {{-- ══════════════════ PAGE HEADER ══════════════════ --}}
  <section class="page-header">
    <div class="grid-bg"></div>
    <div class="page-header-content">
      <span class="section-tag tag-violet">⚙ {{ $tag }}</span>
      <h1 class="section-title">{{ $title }}</h1>
      <p class="section-subtitle">{{ $subtitle }}</p>
    </div>
  </section>

  {{-- ══════════════════ QUICK NAV ══════════════════ --}}
  <section class="section" style="padding-bottom:0;">
    <div class="svc-nav">
      @foreach ($services as $s)
        <a href="#{{ $s['id'] }}">{{ $s['icon'] }} {{ $s['nav'] }}</a>
      @endforeach
    </div>
  </section>

  {{-- ══════════════════ DETAILED SERVICES ══════════════════ --}}
  {{-- 1. LMS --}}
  <section class="section svc-band" id="lms">
    <div class="svc-section">
      <div class="svc-visual">
        <span class="svc-emoji">🎓</span>
        <div class="svc-badge b1">📚 50+ Modules</div>
        <div class="svc-badge b2">📱 <div><div>4 Apps</div><div class="sb-sub">Admin · Teacher · Student · Parent</div></div></div>
      </div>
      <div class="svc-content">
        <span class="svc-eyebrow">01 · Software</span>
        <h2 class="svc-title">All-in-One School LMS</h2>
        <p class="svc-desc">At the heart of everything is the SUPERLMS Learning Management System — a complete, affordable
          platform that runs your entire school from a single login. From admissions to report cards, attendance to fees,
          everything is connected and available on the web and on dedicated mobile apps.</p>
        <ul class="svc-points">
          <li><span class="svc-check">✓</span>Admissions, attendance, timetable, exams &amp; report cards</li>
          <li><span class="svc-check">✓</span>Online fee collection with instant receipts &amp; reconciliation</li>
          <li><span class="svc-check">✓</span>Study material, books, quizzes &amp; parent communication</li>
          <li><span class="svc-check">✓</span>Separate apps for admins, teachers, students and parents</li>
          <li><span class="svc-check">✓</span>Transparent per-student pricing with free onboarding &amp; training</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- 2. ID Cards --}}
  <section class="section svc-band section-alt" id="idcards">
    <div class="svc-section reverse">
      <div class="svc-visual">
        <span class="svc-emoji">🆔</span>
        <div class="svc-badge b1">🎨 Custom Branding</div>
        <div class="svc-badge b2">⚡ Bulk Printing</div>
      </div>
      <div class="svc-content">
        <span class="svc-eyebrow">02 · Identity</span>
        <h2 class="svc-title">Student &amp; Staff ID Cards</h2>
        <p class="svc-desc">We design, print and deliver professional ID cards for your students and staff — generated
          directly from your LMS data, so there is no manual typing or design hassle. Pick a template, approve the
          branding, and we handle the rest in bulk.</p>
        <ul class="svc-points">
          <li><span class="svc-check">✓</span>Beautiful templates customised with your school's branding</li>
          <li><span class="svc-check">✓</span>Auto-filled from student &amp; staff records — zero data entry</li>
          <li><span class="svc-check">✓</span>Durable PVC printed cards delivered to your school</li>
          <li><span class="svc-check">✓</span>Optional QR / barcode for attendance &amp; library access</li>
          <li><span class="svc-check">✓</span>Reprints for new admissions handled any time</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- 3. Loans --}}
  <section class="section svc-band" id="loans">
    <div class="svc-section">
      <div class="svc-visual">
        <span class="svc-emoji">🏦</span>
        <div class="svc-badge b1">📝 Easy Paperwork</div>
        <div class="svc-badge b2">📅 Flexible EMIs</div>
      </div>
      <div class="svc-content">
        <span class="svc-eyebrow">03 · Finance</span>
        <h2 class="svc-title">School Loans &amp; Financing</h2>
        <p class="svc-desc">Growing a school takes capital. Through our lending partners, we help schools access easy
          loans and financing for infrastructure, expansion, equipment and working capital — with minimal paperwork and
          guidance at every step.</p>
        <ul class="svc-points">
          <li><span class="svc-check">✓</span>Tie-ups with trusted banks &amp; NBFCs for school financing</li>
          <li><span class="svc-check">✓</span>Funding for buildings, labs, buses, panels &amp; renovations</li>
          <li><span class="svc-check">✓</span>Quick, simplified documentation with hands-on support</li>
          <li><span class="svc-check">✓</span>Flexible repayment options and comfortable EMIs</li>
          <li><span class="svc-check">✓</span>End-to-end guidance from application to disbursal</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- 4. Labs --}}
  <section class="section svc-band section-alt" id="labs">
    <div class="svc-section reverse">
      <div class="svc-visual">
        <span class="svc-emoji">🔬</span>
        <div class="svc-badge b1">💻 Computer Labs</div>
        <div class="svc-badge b2">⚗️ Science Labs</div>
      </div>
      <div class="svc-content">
        <span class="svc-eyebrow">04 · Infrastructure</span>
        <h2 class="svc-title">Computer &amp; Science Labs Setup</h2>
        <p class="svc-desc">We set up fully-equipped computer and science laboratories — from planning the layout to
          supplying, installing and maintaining everything. Give your students hands-on, practical learning environments
          without the procurement headache.</p>
        <ul class="svc-points">
          <li><span class="svc-check">✓</span>Computers, networking, furniture &amp; complete lab layout</li>
          <li><span class="svc-check">✓</span>Physics, chemistry &amp; biology lab equipment and kits</li>
          <li><span class="svc-check">✓</span>Professional installation, setup &amp; safety compliance</li>
          <li><span class="svc-check">✓</span>Maintenance &amp; AMC so labs keep running smoothly</li>
          <li><span class="svc-check">✓</span>Packages to fit every budget and school size</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- 5. Smart classrooms --}}
  <section class="section svc-band" id="smart">
    <div class="svc-section">
      <div class="svc-visual">
        <span class="svc-emoji">🖥️</span>
        <div class="svc-badge b1">📺 Interactive Panels</div>
        <div class="svc-badge b2">🎬 Digital Content</div>
      </div>
      <div class="svc-content">
        <span class="svc-eyebrow">05 · Classroom</span>
        <h2 class="svc-title">Smart Classrooms &amp; Resource Upgrades</h2>
        <p class="svc-desc">Transform ordinary classrooms into engaging, interactive learning spaces. We upgrade your
          resources with interactive flat panels, smart boards and ready-made digital curriculum content — plus the
          training your teachers need to use them confidently.</p>
        <ul class="svc-points">
          <li><span class="svc-check">✓</span>Interactive flat panels &amp; smart boards installed end-to-end</li>
          <li><span class="svc-check">✓</span>Curriculum-aligned digital content for every subject</li>
          <li><span class="svc-check">✓</span>Projectors, audio systems &amp; modern classroom furniture</li>
          <li><span class="svc-check">✓</span>Hands-on teacher training to make the most of the tech</li>
          <li><span class="svc-check">✓</span>Ongoing support and upgrades as your needs grow</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- 6. Transport --}}
  <section class="section svc-band section-alt" id="transport">
    <div class="svc-section reverse">
      <div class="svc-visual">
        <span class="svc-emoji">🚌</span>
        <div class="svc-badge b1">📍 Live GPS</div>
        <div class="svc-badge b2">📷 CCTV &amp; Biometric</div>
      </div>
      <div class="svc-content">
        <span class="svc-eyebrow">06 · Safety</span>
        <h2 class="svc-title">Smart &amp; Safe Transport</h2>
        <p class="svc-desc">Keep every child safe on the journey to and from school. We fit your buses with GPS tracking,
          biometric attendance, CCTV cameras and live monitoring — giving parents peace of mind and your school complete
          visibility over its fleet.</p>
        <ul class="svc-points">
          <li><span class="svc-check">✓</span>Real-time GPS tracking of every bus on a live map</li>
          <li><span class="svc-check">✓</span>Biometric / RFID boarding &amp; de-boarding attendance</li>
          <li><span class="svc-check">✓</span>CCTV cameras inside buses for continuous monitoring</li>
          <li><span class="svc-check">✓</span>Parent alerts for pick-up, drop and route updates</li>
          <li><span class="svc-check">✓</span>Speed, route &amp; driver monitoring with reports</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- 7. Uniforms & books --}}
  <section class="section svc-band" id="essentials">
    <div class="svc-section">
      <div class="svc-visual">
        <span class="svc-emoji">👕</span>
        <div class="svc-badge b1">📚 Books &amp; Stationery</div>
        <div class="svc-badge b2">🚚 Doorstep Delivery</div>
      </div>
      <div class="svc-content">
        <span class="svc-eyebrow">07 · Essentials</span>
        <h2 class="svc-title">Uniforms, Books &amp; Stationery</h2>
        <p class="svc-desc">We supply quality school uniforms, books and stationery to students — so parents don't have
          to run around markets and schools get consistent, affordable essentials delivered on time, every session.</p>
        <ul class="svc-points">
          <li><span class="svc-check">✓</span>Custom-stitched uniforms in your school's design &amp; colours</li>
          <li><span class="svc-check">✓</span>Comfortable, durable fabric built for daily wear</li>
          <li><span class="svc-check">✓</span>Complete book sets, notebooks &amp; stationery bundles</li>
          <li><span class="svc-check">✓</span>Delivery to the school or directly to families</li>
          <li><span class="svc-check">✓</span>Affordable, transparent pricing with bulk savings</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- 8. Online education --}}
  <section class="section svc-band section-alt" id="online">
    <div class="svc-section reverse">
      <div class="svc-visual">
        <span class="svc-emoji">💻</span>
        <div class="svc-badge b1">🎥 Live &amp; Recorded</div>
        <div class="svc-badge b2">📝 Tests &amp; Doubts</div>
      </div>
      <div class="svc-content">
        <span class="svc-eyebrow">08 · Learning</span>
        <h2 class="svc-title">Online Education</h2>
        <p class="svc-desc">Learning shouldn't stop at the school gate. We provide online education for students through
          live and recorded classes, digital study material and assessments — all accessible from the SUPERLMS app, so
          students can keep learning anytime, anywhere.</p>
        <ul class="svc-points">
          <li><span class="svc-check">✓</span>Live online classes plus recorded lectures to revisit</li>
          <li><span class="svc-check">✓</span>Digital notes, books and study material by subject</li>
          <li><span class="svc-check">✓</span>Online quizzes &amp; assessments with instant results</li>
          <li><span class="svc-check">✓</span>Doubt-solving support to keep students on track</li>
          <li><span class="svc-check">✓</span>Learn anywhere, on any device, through the mobile app</li>
        </ul>
      </div>
    </div>

  </section>

  {{-- ══════════════════ CTA ══════════════════ --}}
  <section class="cta-section">
    <div class="cta-bg"></div>
    <div class="cta-card">
      <h2 class="cta-title">Ready to digitise &amp; equip your <span class="gradient-text">school?</span></h2>
      <p class="cta-desc">Tell us what your school needs — software, infrastructure, transport, essentials or online
        learning — and we'll tailor the right mix of SUPERLMS services for you.</p>
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
