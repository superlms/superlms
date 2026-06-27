@php
    $stored   = $page?->metadata ?? [];
    $def      = config('website_pages.careers', []);
    // Header copy is fixed content (no longer editable from super-admin), so
    // always use the config defaults. Only the job list is admin-managed.
    $tag      = $def['tag']      ?? 'Careers';
    $title    = $def['title']    ?? '';
    $subtitle = $def['subtitle'] ?? '';
    // Show ONLY the job openings added from the super-admin Careers screen
    // (no hardcoded fallback). Skip any blank rows without a role.
    $jobs     = collect($stored['jobs'] ?? [])
        ->filter(fn ($j) => !empty($j['role'] ?? null))
        ->values()
        ->all();

    // Why work with us — perks shown as cards.
    $perks = [
        ['icon' => '💰', 'title' => 'Competitive Salary',  'desc' => 'Fair, market-aligned pay with performance incentives and on-time payouts — every single month.'],
        ['icon' => '📈', 'title' => 'Learning & Growth',   'desc' => 'Real ownership from day one, hands-on mentorship and a clear path to grow with the company.'],
        ['icon' => '🤝', 'title' => 'Supportive Team',      'desc' => 'Work with a friendly, helpful team that has your back and celebrates every win together.'],
        ['icon' => '🌱', 'title' => 'Meaningful Impact',    'desc' => 'Your work directly helps hundreds of schools across India go digital and run better.'],
        ['icon' => '🧘', 'title' => 'Work–Life Balance',    'desc' => 'A flexible, respectful culture — we care about results and outcomes, not clock-watching.'],
        ['icon' => '🎓', 'title' => 'Training Provided',    'desc' => 'Full onboarding, product training and the tools you need to do your best work from day one.'],
    ];

    // Hiring / joining process steps.
    $steps = [
        ['n' => '1', 'title' => 'Apply / Enquiry', 'desc' => 'Submit the short application form with your details and resume — or enquire about a role you like.'],
        ['n' => '2', 'title' => 'Screening',       'desc' => 'Our team reviews your application and shortlists the candidates who best fit the role.'],
        ['n' => '3', 'title' => 'Interview',       'desc' => 'Have a friendly call / interview with the team to talk through your experience and the role.'],
        ['n' => '4', 'title' => 'Offer & Joining', 'desc' => 'Clear the interview, receive your offer, complete onboarding and join the SUPERLMS team.'],
    ];
@endphp
@include('components.website.partials.head', ['title' => 'Careers'])

<style>
  /* ── Jobs ── */
  .jobs-wrap { max-width:880px; margin:0 auto; display:flex; flex-direction:column; gap:16px; }
  .job-card { background:#fff; border:1px solid var(--border2); border-radius:var(--radius);
    padding:24px 28px; display:flex; align-items:center; justify-content:space-between;
    gap:20px; flex-wrap:wrap; transition:all .25s; box-shadow:var(--shadow3); }
  .job-card:hover { border-color:var(--border); transform:translateY(-2px); box-shadow:var(--shadow2); }
  .job-role { font-size:17px; font-weight:600; color:var(--text); margin-bottom:8px; }
  .job-salary { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:700;
    color:var(--violet); margin-bottom:10px; }
  .job-meta { display:flex; gap:10px; flex-wrap:wrap; }
  .job-pill { font-size:11px; font-weight:600; padding:4px 12px; border-radius:50px; background:var(--secondary-faint); color:var(--violet); }
  .job-pill.pink { background:var(--primary-faint); color:var(--pink-dark); }

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

  /* ── Apply form (inline, slides open below) ── */
  .apply-reveal { max-height:0; overflow:hidden; opacity:0;
    transition:max-height .55s cubic-bezier(.4,0,.2,1), opacity .45s ease; }
  .apply-reveal.open { max-height:2000px; opacity:1; }
  .apply-wrap { max-width:680px; margin:0 auto; background:#fff; border:1px solid var(--border2);
    border-radius:24px; padding:34px 34px 30px; box-shadow:var(--shadow2); position:relative; }
  .apply-modal-close { position:absolute; top:18px; right:18px; width:34px; height:34px; border:none;
    background:var(--bg3); color:var(--text3); border-radius:50%; font-size:20px; line-height:1; cursor:pointer;
    display:flex; align-items:center; justify-content:center; transition:all .2s; }
  .apply-modal-close:hover { background:var(--secondary-faint); color:var(--violet); }
  .apply-head { margin-bottom:22px; padding-right:30px; }
  .apply-head h3 { font-family:'Cormorant Garamond',serif; font-size:26px; font-weight:700; color:var(--text); line-height:1.2; }
  .apply-head p { font-size:13px; color:var(--text3); margin-top:4px; }

  .apply-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
  .field { display:flex; flex-direction:column; gap:6px; }
  .field.full { grid-column:1/-1; }
  .field label { font-size:13px; font-weight:600; color:var(--text); }
  .field label .req { color:var(--pink-dark); }
  .field input, .field textarea {
    width:100%; padding:11px 13px; font-size:14px; font-family:inherit; color:var(--text);
    background:var(--bg3); border:1px solid var(--border2); border-radius:12px; transition:border-color .2s, box-shadow .2s; }
  .field input:focus, .field textarea:focus { outline:none; border-color:var(--violet); box-shadow:0 0 0 3px rgba(111,86,254,.12); background:#fff; }
  .field textarea { resize:vertical; min-height:84px; }
  .file-drop { border:1.5px dashed var(--border); border-radius:12px; padding:16px; text-align:center;
    cursor:pointer; transition:background .2s, border-color .2s; background:var(--bg3); }
  .file-drop:hover { border-color:var(--violet); background:var(--secondary-faint); }
  .file-drop input { display:none; }
  .file-drop-icon { font-size:22px; }
  .file-drop-text { font-size:13px; color:var(--text3); margin-top:5px; }
  .file-drop-hint { font-size:11px; color:var(--text4); margin-top:2px; }
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
    .steps-grid { grid-template-columns:1fr 1fr; }
    .apply-grid { grid-template-columns:1fr; }
    .apply-wrap { padding:28px 22px; }
  }
  @media (max-width:480px) {
    .steps-grid { grid-template-columns:1fr; }
    .job-card { flex-direction:column; align-items:flex-start; }
  }
</style>

  @include('components.website.header')

  {{-- ══════════════════ PAGE HEADER ══════════════════ --}}
  <section class="page-header">
    <div class="grid-bg"></div>
    <div class="page-header-content">
      <span class="section-tag tag-violet">💼 {{ $tag }}</span>
      <h1 class="section-title">{{ $title }}</h1>
      <p class="section-subtitle">{{ $subtitle }}</p>
      <div style="margin-top:28px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" onclick="openApply('')">Apply Now</button>
        <a href="#openings" class="btn btn-outline">View Openings →</a>
      </div>
    </div>
  </section>

  {{-- ══════════════════ WHY WORK WITH US ══════════════════ --}}
  <section class="section" style="padding-bottom:48px;">
    <div class="section-head">
      <span class="section-tag tag-pink">Why Join SUPERLMS</span>
      <h2 class="section-title">A place to do your best work</h2>
      <p class="section-subtitle">We're a fast-growing team on a mission to make great school technology affordable for
        every institution in India — and we look after the people who build it.</p>
    </div>
    <div class="cards-grid">
      @foreach ($perks as $p)
      <div class="feature-card">
        <div class="feature-icon-wrap">{{ $p['icon'] }}</div>
        <h3 class="feature-title">{{ $p['title'] }}</h3>
        <p class="feature-desc">{{ $p['desc'] }}</p>
      </div>
      @endforeach
    </div>
  </section>

  {{-- ══════════════════ OPEN POSITIONS ══════════════════ --}}
  <section class="section section-alt" id="openings">
    <div class="section-head">
      <span class="section-tag tag-violet">Open Positions</span>
      <h2 class="section-title">Current openings</h2>
      <p class="section-subtitle">Pick the role that fits you best and click Apply — it only takes a couple of minutes.</p>
    </div>
    <div class="jobs-wrap">
      @forelse ($jobs as $job)
      <div class="job-card">
        <div>
          <div class="job-role">{{ $job['role'] ?? '' }}</div>
          @if (!empty($job['salary']))
            <div class="job-salary">💰 {{ $job['salary'] }}</div>
          @endif
          <div class="job-meta">
            @if (!empty($job['department']))<span class="job-pill">{{ $job['department'] }}</span>@endif
            @if (!empty($job['location']))<span class="job-pill pink">{{ $job['location'] }}</span>@endif
            @if (!empty($job['type']))<span class="job-pill">{{ $job['type'] }}</span>@endif
          </div>
        </div>
        <button type="button" class="btn btn-primary"
          onclick="openApply('{{ addslashes($job['role'] ?? '') }}')">Apply Now</button>
      </div>
      @empty
      <p style="text-align:center;color:var(--text3);font-size:14px;">No open positions right now — but we're always
        happy to hear from great people. Use <strong>Apply Now</strong> above to send us your details.</p>
      @endforelse
    </div>
  </section>

  {{-- ══════════════════ JOINING PROCESS ══════════════════ --}}
  <section class="section">
    <div class="section-head">
      <span class="section-tag tag-pink">How Hiring Works</span>
      <h2 class="section-title">Our joining process</h2>
      <p class="section-subtitle">From enquiry to offer, here's exactly what happens after you apply — simple,
        transparent and quick.</p>
    </div>
    <div class="steps-grid">
      @foreach ($steps as $s)
      <div class="step-card">
        <div class="step-num">{{ $s['n'] }}</div>
        <div class="step-title">{{ $s['title'] }}</div>
        <div class="step-desc">{{ $s['desc'] }}</div>
      </div>
      @endforeach
    </div>
    <div style="text-align:center;margin-top:40px;">
      <button type="button" class="btn btn-primary btn-lg" onclick="openApply('')">Apply Now</button>
    </div>
  </section>

  {{-- ══════════════════ APPLY FORM (slides open below) ══════════════════ --}}
  <section id="applySection" style="padding:0 6%;">
    <div class="apply-reveal" id="applyReveal">
     <div style="padding:72px 0;">
      <div class="apply-wrap">
      <button type="button" class="apply-modal-close" onclick="closeApply()" aria-label="Close">&times;</button>
      <div class="apply-head">
        <h3 id="applyTitle">Apply <span id="applyRoleLabel">to SUPERLMS</span></h3>
        <p>Fill in your details and attach your resume. Fields marked <span style="color:var(--pink-dark)">*</span> are required.</p>
      </div>

      <form id="careerForm" onsubmit="handleCareerSubmit(event)" enctype="multipart/form-data">
        <input type="hidden" name="job_role" id="jobRole">
        <div class="apply-grid">
          <div class="field">
            <label>Full Name <span class="req">*</span></label>
            <input type="text" name="full_name" required placeholder="Your full name">
          </div>
          <div class="field">
            <label>Email <span class="req">*</span></label>
            <input type="email" name="email" required placeholder="you@example.com">
          </div>
          <div class="field">
            <label>Mobile Number <span class="req">*</span></label>
            <input type="tel" name="mobile" required maxlength="10" pattern="[6-9][0-9]{9}"
              placeholder="10-digit mobile number">
          </div>
          <div class="field">
            <label>Experience <span class="req">*</span></label>
            <input type="text" name="experience" required placeholder="e.g. 2 years in sales / Fresher">
          </div>
          <div class="field full">
            <label>Address <span class="req">*</span></label>
            <textarea name="address" required placeholder="Your city, area and state"></textarea>
          </div>
          <div class="field full">
            <label>About You / Why you want to join</label>
            <textarea name="description" placeholder="Tell us a little about yourself and why you'd be a great fit (optional)"></textarea>
          </div>
          <div class="field full">
            <label>Attach Resume <span class="req">*</span></label>
            <label class="file-drop" for="careerDoc">
              <div class="file-drop-icon">📎</div>
              <div class="file-drop-text">Click to upload your resume</div>
              <div class="file-drop-hint">PDF or Word (DOC/DOCX) — max 2 MB</div>
              <div class="file-drop-name" id="fileName"></div>
              <input type="file" name="document" id="careerDoc" required accept=".pdf,.doc,.docx"
                onchange="checkFile(this)">
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
     </div>
    </div>
  </section>

  {{-- Toast --}}
  <div class="toast hidden" id="toast">
    <div class="toast-icon">✅</div>
    <div>
      <div class="toast-title">Application Sent!</div>
      <div class="toast-msg">Our team will review your application and reach out soon.</div>
    </div>
  </div>

  <script>
    var MAX_RESUME_BYTES = 2 * 1024 * 1024; // 2 MB

    function openApply(role) {
      document.getElementById('jobRole').value = role || '';
      document.getElementById('applyRoleLabel').textContent = role ? ('for ' + role) : 'to SUPERLMS';
      document.getElementById('applyReveal').classList.add('open');
      document.getElementById('applySection').scrollIntoView({ behavior: 'smooth', block: 'start' });
      setTimeout(function () {
        var fn = document.querySelector('#careerForm input[name="full_name"]');
        if (fn) fn.focus({ preventScroll: true });
      }, 450);
    }

    function closeApply() {
      document.getElementById('applyReveal').classList.remove('open');
    }

    function checkFile(input) {
      var nameEl = document.getElementById('fileName');
      var errEl  = document.getElementById('fileErr');
      errEl.textContent = '';
      var f = input.files[0];
      if (!f) { nameEl.textContent = ''; return; }
      if (f.size > MAX_RESUME_BYTES) {
        errEl.textContent = 'This file is larger than 2 MB. Please upload a smaller resume.';
        input.value = '';
        nameEl.textContent = '';
        return;
      }
      nameEl.textContent = f.name;
    }

    async function handleCareerSubmit(e) {
      e.preventDefault();
      var form = e.target;

      var fileInput = document.getElementById('careerDoc');
      if (fileInput.files[0] && fileInput.files[0].size > MAX_RESUME_BYTES) {
        showToast('Resume must be 2 MB or smaller.', false);
        return;
      }

      var btn = form.querySelector('.btn-submit');
      var orig = btn.textContent;
      btn.textContent = 'Submitting…';
      btn.disabled = true;

      try {
        var res = await fetch('/api/website/career-apply', {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
          body: new FormData(form),
        });
        var json = await res.json();
        if (json.success) {
          form.reset();
          document.getElementById('fileName').textContent = '';
          document.getElementById('fileErr').textContent = '';
          closeApply();
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
