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
        ['icon' => 'rupee',     'title' => 'Competitive Salary',  'desc' => 'Fair, market-aligned pay with performance incentives and on-time payouts — every single month.'],
        ['icon' => 'trending-up', 'title' => 'Learning & Growth', 'desc' => 'Real ownership from day one, hands-on mentorship and a clear path to grow with the company.'],
        ['icon' => 'handshake', 'title' => 'Supportive Team',      'desc' => 'Work with a friendly, helpful team that has your back and celebrates every win together.'],
        ['icon' => 'sprout',    'title' => 'Meaningful Impact',    'desc' => 'Your work directly helps hundreds of schools across India go digital and run better.'],
        ['icon' => 'sun',       'title' => 'Work–Life Balance',    'desc' => 'A flexible, respectful culture — we care about results and outcomes, not clock-watching.'],
        ['icon' => 'cap',       'title' => 'Training Provided',    'desc' => 'Full onboarding, product training and the tools you need to do your best work from day one.'],
    ];

    // Hiring / joining process steps.
    $steps = [
        ['n' => '1', 'title' => 'Apply / Enquiry', 'desc' => 'Submit the short application form with your details and resume — or enquire about a role you like.'],
        ['n' => '2', 'title' => 'Screening',       'desc' => 'Our team reviews your application and shortlists the candidates who best fit the role.'],
        ['n' => '3', 'title' => 'Interview',       'desc' => 'Have a friendly call / interview with the team to talk through your experience and the role.'],
        ['n' => '4', 'title' => 'Offer & Joining', 'desc' => 'Clear the interview, receive your offer, complete onboarding and join the SUPERLMS team.'],
    ];
@endphp
@extends('components.site.layout')

@section('title', 'Careers')

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">{{ $tag }}</span>
            <h1 class="h1 rv" style="--rd:.05s">{{ $title }}</h1>
            <p class="lead rv" style="--rd:.1s">{{ $subtitle }}</p>
            <div class="actions rv" style="--rd:.15s">
                <button type="button" class="btn btn-primary" onclick="openApply('')">Apply Now</button>
                <a href="#openings" class="btn btn-outline">View Openings <x-site.icon name="arrow-right" class="ic-arrow" /></a>
            </div>
        </div>
    </section>

    {{-- Why work with us --}}
    <section class="section" style="padding-top:40px;">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Why Join SUPERLMS</span>
                <h2 class="h2">A place to do your best work</h2>
                <p class="lead">We're a fast-growing team on a mission to make great school technology affordable for
                    every institution in India — and we look after the people who build it.</p>
            </div>
            <div class="grid g3">
                @foreach ($perks as $i => $p)
                    <div class="card card-soft rv" style="--rd:{{ ($i % 3) * .07 }}s">
                        <div class="icon-sq"><x-site.icon :name="$p['icon']" /></div>
                        <h3>{{ $p['title'] }}</h3>
                        <p>{{ $p['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Open positions --}}
    <section class="section section-soft" id="openings" style="scroll-margin-top:72px;">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Open Positions</span>
                <h2 class="h2">Current openings</h2>
                <p class="lead">Pick the role that fits you best and click Apply — it only takes a couple of minutes.</p>
            </div>
            <div class="jobs">
                @forelse ($jobs as $i => $job)
                    <div class="job rv" style="--rd:{{ $i * .06 }}s">
                        <div>
                            <div class="job-role">{{ $job['role'] ?? '' }}</div>
                            @if (!empty($job['salary']))
                                <div class="job-salary"><x-site.icon name="wallet" /> {{ $job['salary'] }}</div>
                            @endif
                            <div class="job-meta">
                                @if (!empty($job['department']))<span class="tag tag-gray"><x-site.icon name="briefcase" /> {{ $job['department'] }}</span>@endif
                                @if (!empty($job['location']))<span class="tag tag-gray"><x-site.icon name="map-pin" /> {{ $job['location'] }}</span>@endif
                                @if (!empty($job['type']))<span class="tag">{{ $job['type'] }}</span>@endif
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openApply('{{ addslashes($job['role'] ?? '') }}')">Apply Now</button>
                    </div>
                @empty
                    <p class="empty">No open positions right now — but we're always
                        happy to hear from great people. Use <strong>Apply Now</strong> above to send us your details.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Joining process --}}
    <section class="section">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">How Hiring Works</span>
                <h2 class="h2">Our joining process</h2>
                <p class="lead">From enquiry to offer, here's exactly what happens after you apply — simple,
                    transparent and quick.</p>
            </div>
            <div class="steps">
                @foreach ($steps as $i => $s)
                    <div class="step" style="--i:{{ $i }}">
                        <div class="step-n">{{ $s['n'] }}</div>
                        <h3>{{ $s['title'] }}</h3>
                        <p>{{ $s['desc'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="center" style="margin-top:44px;">
                <button type="button" class="btn btn-primary btn-lg" onclick="openApply('')">Apply Now</button>
            </div>
        </div>
    </section>

    {{-- Apply form (slides open) --}}
    <section id="applySection" style="scroll-margin-top:80px;">
        <div class="apply-reveal" id="applyReveal">
            <div>
                <div class="container" style="padding-bottom:80px;">
                    <div class="apply-wrap form-card">
                        <button type="button" class="apply-close" onclick="closeApply()" aria-label="Close"><x-site.icon name="x" /></button>
                        <div class="h4" id="applyTitle">Apply <span id="applyRoleLabel">to SUPERLMS</span></div>
                        <p class="form-sub">Fill in your details and attach your resume. Fields marked <span style="color:var(--rose)">*</span> are required.</p>

                        <form id="careerForm" onsubmit="handleCareerSubmit(event)" enctype="multipart/form-data">
                            <input type="hidden" name="job_role" id="jobRole">
                            <div class="fgrid">
                                <div class="fgroup">
                                    <label class="flabel">Full Name <span class="req">*</span></label>
                                    <input class="finput" type="text" name="full_name" required maxlength="50" placeholder="Your full name">
                                </div>
                                <div class="fgroup">
                                    <label class="flabel">Email <span class="req">*</span></label>
                                    <input class="finput" type="email" name="email" required maxlength="50" pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                                        title="Enter a valid email address" placeholder="you@example.com">
                                </div>
                                <div class="fgroup">
                                    <label class="flabel">Mobile Number <span class="req">*</span></label>
                                    <input class="finput" type="tel" name="mobile" required maxlength="10" pattern="[6-9][0-9]{9}"
                                        placeholder="10-digit mobile number">
                                </div>
                                <div class="fgroup">
                                    <label class="flabel">Experience <span class="req">*</span></label>
                                    <input class="finput" type="text" name="experience" required maxlength="200" placeholder="e.g. 2 years in sales / Fresher">
                                </div>
                                <div class="fgroup full">
                                    <label class="flabel">Address <span class="req">*</span></label>
                                    <textarea class="ftext" name="address" required maxlength="200" placeholder="Your city, area and state" style="height:90px;"></textarea>
                                </div>
                                <div class="fgroup full">
                                    <label class="flabel">About You / Why you want to join</label>
                                    <textarea class="ftext" name="description" maxlength="500" placeholder="Tell us a little about yourself and why you'd be a great fit (optional)"></textarea>
                                </div>
                                <div class="fgroup full">
                                    <label class="flabel">Attach Resume <span class="req">*</span></label>
                                    <label class="file-drop" for="careerDoc">
                                        <x-site.icon name="paperclip" />
                                        <span class="file-drop-t">Click to upload your resume</span>
                                        <span class="file-drop-h">PDF or Word (DOC/DOCX) — max 2 MB</span>
                                        <span class="file-drop-n" id="fileName"></span>
                                        <input type="file" name="document" id="careerDoc" required accept=".pdf,.doc,.docx" onchange="checkFile(this)">
                                    </label>
                                    <div class="file-err" id="fileErr"></div>
                                </div>
                                <div class="fgroup full">
                                    <button type="submit" class="btn btn-primary btn-block btn-submit">Submit Application</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
    var MAX_RESUME_BYTES = 2 * 1024 * 1024; // 2 MB

    function openApply(role) {
        document.getElementById('jobRole').value = role || '';
        document.getElementById('applyRoleLabel').textContent = role ? ('for ' + role) : 'to SUPERLMS';
        document.getElementById('applyReveal').classList.add('is-open');
        document.getElementById('applySection').scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(function () {
            var fn = document.querySelector('#careerForm input[name="full_name"]');
            if (fn) fn.focus({ preventScroll: true });
        }, 450);
    }

    function closeApply() {
        document.getElementById('applyReveal').classList.remove('is-open');
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
            siteToast('Error', 'Resume must be 2 MB or smaller.', false);
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
                siteToast('Application Sent!', json.message || 'Application sent!', true);
            } else {
                siteToast('Error', Object.values(json.errors || {}).flat()[0] || 'Something went wrong.', false);
            }
        } catch (err) {
            siteToast('Error', 'Network error. Please try again.', false);
        } finally {
            btn.textContent = orig;
            btn.disabled = false;
        }
    }
</script>
@endpush
