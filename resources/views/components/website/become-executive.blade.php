@php
    $def      = config('website_pages.become-executive', []);
    // Header copy is fixed content (not editable from super-admin).
    $tag      = $def['tag']      ?? 'Partner Program';
    $title    = $def['title']    ?? '';
    $subtitle = $def['subtitle'] ?? '';
@endphp
@include('components.website.partials.head', ['title' => 'Become an Executive'])

<style>
  /* ── Earnings highlight banner ── */
  .earn-banner { max-width:1060px; margin:0 auto; background:var(--grad1); border-radius:24px;
    padding:44px 48px; color:#fff; display:grid; grid-template-columns:repeat(3,1fr); gap:28px;
    box-shadow:0 24px 60px rgba(111,86,254,.25); position:relative; overflow:hidden; }
  .earn-banner::before { content:''; position:absolute; top:-80px; right:-60px; width:260px; height:260px;
    border-radius:50%; background:rgba(255,255,255,.12); }
  .earn-stat { position:relative; z-index:1; }
  .earn-num { font-family:'Cormorant Garamond',serif; font-size:clamp(30px,4vw,46px); font-weight:700; line-height:1; }
  .earn-label { font-size:13px; opacity:.9; margin-top:8px; line-height:1.5; }

  /* ── What you'll do ── */
  .do-split { max-width:1060px; margin:0 auto; display:grid; grid-template-columns:1fr 1fr; gap:48px; align-items:center; }
  .do-list { display:flex; flex-direction:column; gap:16px; }
  .do-item { display:flex; gap:14px; align-items:flex-start; }
  .do-check { flex-shrink:0; width:26px; height:26px; border-radius:8px; background:var(--secondary-faint);
    color:var(--violet); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; }
  .do-item-title { font-size:15px; font-weight:600; color:var(--text); margin-bottom:3px; }
  .do-item-desc { font-size:13px; color:var(--text3); line-height:1.65; }
  .do-visual { background:linear-gradient(135deg,var(--secondary-faint),var(--primary-faint));
    border:1px solid var(--border); border-radius:24px; padding:40px; text-align:center; }
  .do-visual-emoji { font-size:64px; }
  .do-visual-title { font-family:'Cormorant Garamond',serif; font-size:26px; font-weight:600; color:var(--text); margin:14px 0 8px; }
  .do-visual-desc { font-size:14px; color:var(--text3); line-height:1.7; }

  /* ── Joining process steps ── */
  .steps-grid { max-width:1060px; margin:0 auto; display:grid; grid-template-columns:repeat(4,1fr); gap:20px; }
  .step-card { position:relative; background:#fff; border:1px solid var(--border2); border-radius:var(--radius);
    padding:30px 24px; box-shadow:var(--shadow3); transition:transform .25s, box-shadow .25s; }
  .step-card:hover { transform:translateY(-4px); box-shadow:var(--shadow2); }
  .step-num { width:42px; height:42px; border-radius:12px; background:var(--grad1); color:#fff;
    font-family:'Cormorant Garamond',serif; font-size:20px; font-weight:700;
    display:flex; align-items:center; justify-content:center; margin-bottom:16px; }
  .step-title { font-size:16px; font-weight:600; color:var(--text); margin-bottom:6px; }
  .step-desc { font-size:13px; color:var(--text3); line-height:1.6; }

  /* ── Apply form ── */
  .apply-wrap { max-width:760px; margin:0 auto; background:#fff; border:1px solid var(--border2);
    border-radius:24px; padding:40px; box-shadow:var(--shadow2); }
  .apply-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
  .field { display:flex; flex-direction:column; gap:6px; }
  .field.full { grid-column:1/-1; }
  .field label { font-size:13px; font-weight:600; color:var(--text); }
  .field label .req { color:var(--pink-dark); }
  .field input, .field textarea {
    width:100%; padding:12px 14px; font-size:14px; font-family:inherit; color:var(--text);
    background:var(--bg3); border:1px solid var(--border2); border-radius:12px; transition:border-color .2s, box-shadow .2s; }
  .field input:focus, .field textarea:focus { outline:none; border-color:var(--violet); box-shadow:0 0 0 3px rgba(111,86,254,.12); background:#fff; }
  .field textarea { resize:vertical; min-height:90px; }
  .file-drop { border:1.5px dashed var(--border); border-radius:12px; padding:18px; text-align:center;
    cursor:pointer; transition:background .2s, border-color .2s; background:var(--bg3); }
  .file-drop:hover { border-color:var(--violet); background:var(--secondary-faint); }
  .file-drop input { display:none; }
  .file-drop-icon { font-size:24px; }
  .file-drop-text { font-size:13px; color:var(--text3); margin-top:6px; }
  .file-drop-name { font-size:13px; font-weight:600; color:var(--violet); margin-top:6px; }
  .file-err { font-size:12px; color:#dc2626; margin-top:6px; }

  /* ── Toast ── */
  .toast { position:fixed; bottom:28px; right:28px; background:#fff; border:1px solid rgba(34,197,94,.3);
    border-radius:var(--radius); padding:16px 22px; display:flex; align-items:center; gap:12px;
    box-shadow:var(--shadow2); z-index:9999; max-width:340px; }
  .toast.hidden { display:none; }
  .toast-icon { font-size:22px; }
  .toast-title { font-size:14px; font-weight:600; color:var(--text); }
  .toast-msg { font-size:12px; color:var(--text3); margin-top:2px; }

  @media (max-width:760px) {
    .earn-banner { grid-template-columns:1fr; gap:22px; padding:34px 28px; text-align:center; }
    .do-split { grid-template-columns:1fr; gap:30px; }
    .steps-grid { grid-template-columns:1fr 1fr; }
    .apply-grid { grid-template-columns:1fr; }
    .apply-wrap { padding:28px 22px; }
  }
  @media (max-width:480px) { .steps-grid { grid-template-columns:1fr; } }
</style>

  @include('components.website.header')

  {{-- ══════════════════ PAGE HEADER ══════════════════ --}}
  <section class="page-header">
    <div class="grid-bg"></div>
    <div class="page-header-content">
      <span class="section-tag tag-pink">🤝 {{ $tag }}</span>
      <h1 class="section-title">{{ $title }}</h1>
      <p class="section-subtitle">{{ $subtitle }}</p>
      <div style="margin-top:28px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="#apply" class="btn btn-primary">Apply to Partner</a>
        <a href="{{ url('web/about') }}" class="btn btn-outline">Learn About Us →</a>
      </div>
    </div>
  </section>

  {{-- ══════════════════ ADVANTAGES ══════════════════ --}}
  <section class="section" style="padding-bottom:40px;">
    <div class="section-head">
      <span class="section-tag tag-pink">Why Become an Executive</span>
      <h2 class="section-title">Perks that actually pay off</h2>
      <p class="section-subtitle">Partnering with SUPERLMS means real earning potential, full flexibility and a product
        schools genuinely need — backed by a team that supports you at every step.</p>
    </div>
    <div class="cards-grid">
      <div class="feature-card">
        <div class="feature-icon-wrap">💰</div>
        <h3 class="feature-title">Earn ₹1 Lakh+ / Month</h3>
        <p class="feature-desc">High commissions on every school you onboard, plus recurring income as they renew —
          your earnings grow with your effort, with no upper limit.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon-wrap">🌍</div>
        <h3 class="feature-title">100% Remote Work</h3>
        <p class="feature-desc">Work from anywhere, in your own city or region. No office, no fixed desk — just your
          phone, a laptop and the freedom to build your own schedule.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon-wrap">⚡</div>
        <h3 class="feature-title">Simple Joining Process</h3>
        <p class="feature-desc">No lengthy interviews or complex paperwork. Fill one short form, get verified and
          start within days — onboarding is quick and completely hassle-free.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon-wrap">🏦</div>
        <h3 class="feature-title">Fast & Easy Payouts</h3>
        <p class="feature-desc">Transparent payouts sent straight to your bank account on time, every time. Track
          every school and every payment clearly from one place.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon-wrap">🎓</div>
        <h3 class="feature-title">Full Training & Support</h3>
        <p class="feature-desc">Get product training, ready-made demos, brochures and pricing kits — plus a dedicated
          team that handles onboarding and tech support for your schools.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon-wrap">📈</div>
        <h3 class="feature-title">Grow Without Limits</h3>
        <p class="feature-desc">Top performers unlock higher tiers, bigger incentives and leadership opportunities.
          The more schools you bring, the faster you rise.</p>
      </div>
    </div>
  </section>

  {{-- ══════════════════ EARNINGS BANNER ══════════════════ --}}
  <section class="section" style="padding-top:20px;padding-bottom:40px;">
    <div class="earn-banner">
      <div class="earn-stat">
        <div class="earn-num">₹1 Lakh+</div>
        <div class="earn-label">Monthly earning potential for active partners</div>
      </div>
      <div class="earn-stat">
        <div class="earn-num">100%</div>
        <div class="earn-label">Remote &amp; flexible — work on your own terms</div>
      </div>
      <div class="earn-stat">
        <div class="earn-num">Recurring</div>
        <div class="earn-label">Income that keeps coming as schools renew</div>
      </div>
    </div>
  </section>

  {{-- ══════════════════ WHAT YOU'LL DO ══════════════════ --}}
  <section class="section section-alt">
    <div class="section-head">
      <span class="section-tag tag-violet">Your Role</span>
      <h2 class="section-title">What you'll do</h2>
      <p class="section-subtitle">Your core mission is simple — help schools in your region partner with SUPERLMS and go
        digital. You connect them with us, and we take care of the rest.</p>
    </div>
    <div class="do-split">
      <div class="do-list">
        <div class="do-item">
          <div class="do-check">🏫</div>
          <div>
            <div class="do-item-title">Get schools to partner with us</div>
            <div class="do-item-desc">Reach out to schools in your area and introduce them to SUPERLMS — India's
              affordable, all-in-one school management platform.</div>
          </div>
        </div>
        <div class="do-item">
          <div class="do-check">🤝</div>
          <div>
            <div class="do-item-title">Pitch &amp; book demos</div>
            <div class="do-item-desc">Share our brochures and pricing, and book a demo with our team. We help you
              present SUPERLMS with confidence — even if you're brand new to sales.</div>
          </div>
        </div>
        <div class="do-item">
          <div class="do-check">✅</div>
          <div>
            <div class="do-item-title">Help them onboard</div>
            <div class="do-item-desc">Once a school says yes, our onboarding team sets everything up. You simply stay
              in touch and keep building relationships.</div>
          </div>
        </div>
        <div class="do-item">
          <div class="do-check">💸</div>
          <div>
            <div class="do-item-title">Earn for every school</div>
            <div class="do-item-desc">Get paid a healthy commission for each school you bring on board, plus recurring
              income for as long as they stay with SUPERLMS.</div>
          </div>
        </div>
      </div>
      <div class="do-visual">
        <div class="do-visual-emoji">🏫🤝🚀</div>
        <div class="do-visual-title">Schools need you</div>
        <div class="do-visual-desc">Thousands of schools across India are still running on paperwork. You bring them a
          product that practically sells itself — and earn well doing it.</div>
      </div>
    </div>
  </section>

  {{-- ══════════════════ JOINING PROCESS ══════════════════ --}}
  <section class="section">
    <div class="section-head">
      <span class="section-tag tag-pink">How It Works</span>
      <h2 class="section-title">Simple joining process</h2>
      <p class="section-subtitle">Getting started takes minutes. Here's exactly how you go from applying to earning.</p>
    </div>
    <div class="steps-grid">
      <div class="step-card">
        <div class="step-num">1</div>
        <div class="step-title">Apply</div>
        <div class="step-desc">Fill the short application form below with your details and upload your resume or ID.</div>
      </div>
      <div class="step-card">
        <div class="step-num">2</div>
        <div class="step-title">Get Verified</div>
        <div class="step-desc">Our team reviews your application and reaches out within a few days to welcome you on board.</div>
      </div>
      <div class="step-card">
        <div class="step-num">3</div>
        <div class="step-title">Train</div>
        <div class="step-desc">Get product training, sales material and pricing so you can pitch SUPERLMS with confidence.</div>
      </div>
      <div class="step-card">
        <div class="step-num">4</div>
        <div class="step-title">Start Earning</div>
        <div class="step-desc">Onboard schools, track your payouts, and earn recurring income — all on your own schedule.</div>
      </div>
    </div>
  </section>

  {{-- ══════════════════ APPLY FORM ══════════════════ --}}
  <section class="section section-alt" id="apply">
    <div class="section-head">
      <span class="section-tag tag-violet">Apply Now</span>
      <h2 class="section-title">Send us your application</h2>
      <p class="section-subtitle">Fill in your details and our partnerships team will get back to you. Fields marked
        <span style="color:var(--pink-dark)">*</span> are required.</p>
    </div>

    <div class="apply-wrap">
      <form id="executiveForm" onsubmit="handleExecutiveSubmit(event)" enctype="multipart/form-data">
        <div class="apply-grid">
          <div class="field">
            <label>Full Name <span class="req">*</span></label>
            <input type="text" name="full_name" required maxlength="50" placeholder="Your full name">
          </div>
          <div class="field">
            <label>Email <span class="req">*</span></label>
            <input type="email" name="email" required maxlength="50" pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
              title="Enter a valid email address" placeholder="you@example.com">
          </div>
          <div class="field">
            <label>Mobile Number <span class="req">*</span></label>
            <input type="tel" name="mobile" required maxlength="10" pattern="[6-9][0-9]{9}"
              inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')"
              placeholder="10-digit mobile number">
          </div>
          <div class="field">
            <label>Qualification <span class="req">*</span></label>
            <input type="text" name="qualification" required maxlength="120" placeholder="e.g. B.Com, MBA, Graduate">
          </div>
          <div class="field full">
            <label>Address <span class="req">*</span></label>
            <textarea name="address" required maxlength="200" placeholder="Your city, area and state"></textarea>
          </div>
          <div class="field full">
            <label>About You / Why you want to join</label>
            <textarea name="description" maxlength="500" placeholder="Tell us a little about your experience and why you'd be a great fit (optional)"></textarea>
          </div>
          <div class="field full">
            <label>Attach Document <span style="font-weight:400;color:var(--text3)">(Resume / ID — PDF, DOC or image, max 5 MB)</span></label>
            <label class="file-drop" for="executiveDoc">
              <div class="file-drop-icon">📎</div>
              <div class="file-drop-text">Click to upload your document</div>
              <div class="file-drop-name" id="fileName"></div>
              <input type="file" name="document" id="executiveDoc" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                onchange="checkExecutiveFile(this)">
            </label>
            <div class="file-err" id="fileErr"></div>
          </div>
          <div class="field full">
            <button type="submit" class="btn btn-primary btn-submit" style="width:100%;justify-content:center;">
              Submit Application</button>
          </div>
        </div>
      </form>
    </div>
  </section>

  {{-- Toast --}}
  <div class="toast hidden" id="toast">
    <div class="toast-icon">✅</div>
    <div>
      <div class="toast-title">Application Sent!</div>
      <div class="toast-msg">Our partnerships team will review your application and reach out soon.</div>
    </div>
  </div>

  <script>
    var EXEC_MAX_BYTES = 5 * 1024 * 1024; // 5 MB
    var EXEC_ALLOWED = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    function checkExecutiveFile(input) {
      var nameEl = document.getElementById('fileName');
      var errEl  = document.getElementById('fileErr');
      errEl.textContent = '';
      var f = input.files[0];
      if (!f) { nameEl.textContent = ''; return; }

      var ext = (f.name.split('.').pop() || '').toLowerCase();
      if (EXEC_ALLOWED.indexOf(ext) === -1) {
        errEl.textContent = 'Only PDF, DOC, DOCX, JPG or PNG files are allowed.';
        input.value = '';
        nameEl.textContent = '';
        return;
      }
      if (f.size > EXEC_MAX_BYTES) {
        errEl.textContent = 'This file is larger than 5 MB. Please upload a smaller file.';
        input.value = '';
        nameEl.textContent = '';
        return;
      }
      nameEl.textContent = f.name;
    }

    async function handleExecutiveSubmit(e) {
      e.preventDefault();
      var form = e.target;

      var fileInput = document.getElementById('executiveDoc');
      if (fileInput.files[0]) {
        var ext = (fileInput.files[0].name.split('.').pop() || '').toLowerCase();
        if (EXEC_ALLOWED.indexOf(ext) === -1) {
          showToast('Only PDF, DOC, DOCX, JPG or PNG files are allowed.', false);
          return;
        }
        if (fileInput.files[0].size > EXEC_MAX_BYTES) {
          showToast('Document must be 5 MB or smaller.', false);
          return;
        }
      }

      var btn = form.querySelector('.btn-submit');
      var orig = btn.textContent;
      btn.textContent = 'Submitting…';
      btn.disabled = true;

      try {
        var res = await fetch('/api/website/executive-apply', {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: new FormData(form),
        });
        var json = await res.json();
        if (json.success) {
          form.reset();
          document.getElementById('fileName').textContent = '';
          document.getElementById('fileErr').textContent = '';
          showToast(json.message || 'Application sent!', true);
        } else {
          showToast(Object.values(json.errors || {}).flat()[0] || 'Something went wrong.', false);
        }
      } catch (err) {
        showToast('Network error. Please try again.', false);
      } finally {
        btn.textContent = orig;
        btn.disabled = false;
      }
    }

    function showToast(msg, success) {
      var toast = document.getElementById('toast');
      toast.querySelector('.toast-title').textContent = success ? 'Application Sent!' : 'Error';
      toast.querySelector('.toast-msg').textContent = msg;
      toast.querySelector('.toast-icon').textContent = success ? '✅' : '❌';
      toast.classList.remove('hidden');
      setTimeout(function () { toast.classList.add('hidden'); }, 4500);
    }
  </script>

  @include('components.website.app-section')
  @include('components.website.footer')
</body>
</html>
