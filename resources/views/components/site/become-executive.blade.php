@php
    $def      = config('website_pages.become-executive', []);
    // Header copy is fixed content (not editable from super-admin).
    $tag      = $def['tag']      ?? 'Partner Program';
    $title    = $def['title']    ?? '';
    $subtitle = $def['subtitle'] ?? '';

    $perks = [
        ['rupee', 'Earn ₹1 Lakh+ / Month', 'High commissions on every school you onboard, plus recurring income as they renew — your earnings grow with your effort, with no upper limit.'],
        ['globe', '100% Remote Work', 'Work from anywhere, in your own city or region. No office, no fixed desk — just your phone, a laptop and the freedom to build your own schedule.'],
        ['zap', 'Simple Joining Process', 'No lengthy interviews or complex paperwork. Fill one short form, get verified and start within days — onboarding is quick and completely hassle-free.'],
        ['landmark', 'Fast & Easy Payouts', 'Transparent payouts sent straight to your bank account on time, every time. Track every school and every payment clearly from one place.'],
        ['cap', 'Full Training & Support', 'Get product training, ready-made demos, brochures and pricing kits — plus a dedicated team that handles onboarding and tech support for your schools.'],
        ['trending-up', 'Grow Without Limits', 'Top performers unlock higher tiers, bigger incentives and leadership opportunities. The more schools you bring, the faster you rise.'],
    ];

    $doList = [
        ['school', 'Get schools to partner with us', "Reach out to schools in your area and introduce them to SUPERLMS — India's affordable, all-in-one school management platform."],
        ['handshake', 'Pitch & book demos', "Share our brochures and pricing, and book a demo with our team. We help you present SUPERLMS with confidence — even if you're brand new to sales."],
        ['check-circle', 'Help them onboard', 'Once a school says yes, our onboarding team sets everything up. You simply stay in touch and keep building relationships.'],
        ['banknote', 'Earn for every school', 'Get paid a healthy commission for each school you bring on board, plus recurring income for as long as they stay with SUPERLMS.'],
    ];

    $steps = [
        ['1', 'Apply', 'Fill the short application form below with your details and upload your resume or ID.'],
        ['2', 'Get Verified', 'Our team reviews your application and reaches out within a few days to welcome you on board.'],
        ['3', 'Train', 'Get product training, sales material and pricing so you can pitch SUPERLMS with confidence.'],
        ['4', 'Start Earning', 'Onboard schools, track your payouts, and earn recurring income — all on your own schedule.'],
    ];
@endphp
@extends('components.site.layout')

@section('title', 'Become an Executive')

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">{{ $tag }}</span>
            <h1 class="h1 rv" style="--rd:.05s">{{ $title }}</h1>
            <p class="lead rv" style="--rd:.1s">{{ $subtitle }}</p>
            <div class="actions rv" style="--rd:.15s">
                <a href="#apply" class="btn btn-primary">Apply to Partner</a>
                <a href="{{ url('web/about') }}" class="btn btn-outline">Learn About Us <x-site.icon name="arrow-right" class="ic-arrow" /></a>
            </div>
        </div>
    </section>

    {{-- Advantages --}}
    <section class="section" style="padding-top:40px;">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Why Become an Executive</span>
                <h2 class="h2">Perks that actually pay off</h2>
                <p class="lead">Partnering with SUPERLMS means real earning potential, full flexibility and a product
                    schools genuinely need — backed by a team that supports you at every step.</p>
            </div>
            <div class="grid g3">
                @foreach ($perks as $i => $p)
                    <div class="card card-soft rv" style="--rd:{{ ($i % 3) * .07 }}s">
                        <div class="icon-sq"><x-site.icon :name="$p[0]" /></div>
                        <h3>{{ $p[1] }}</h3>
                        <p>{{ $p[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Earnings banner --}}
    <section class="section-tight" style="padding-top:0;">
        <div class="container">
            <div class="earn rv rv-s">
                <div class="earn-s"><div class="earn-n">₹1 Lakh+</div><div class="earn-l">Monthly earning potential for active partners</div></div>
                <div class="earn-s"><div class="earn-n">100%</div><div class="earn-l">Remote &amp; flexible — work on your own terms</div></div>
                <div class="earn-s"><div class="earn-n">Recurring</div><div class="earn-l">Income that keeps coming as schools renew</div></div>
            </div>
        </div>
    </section>

    {{-- What you'll do --}}
    <section class="section section-soft">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Your Role</span>
                <h2 class="h2">What you'll do</h2>
                <p class="lead">Your core mission is simple — help schools in your region partner with SUPERLMS and go
                    digital. You connect them with us, and we take care of the rest.</p>
            </div>
            <div class="split">
                <div class="info-list rv rv-l">
                    @foreach ($doList as $d)
                        <div class="info">
                            <div class="icon-sq"><x-site.icon :name="$d[0]" /></div>
                            <div>
                                <div class="info-v" style="margin:0;font-size:17px;">{{ $d[1] }}</div>
                                <p style="font-size:15px;color:var(--muted);line-height:1.6;margin-top:4px;">{{ $d[2] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="rv rv-r">
                    <div class="vpanel mint" style="flex-direction:column;text-align:center;gap:18px;">
                        <div class="mk" style="--cyc:9s;width:100%;max-width:360px;">
                            <div class="mpanel round" style="padding:18px;text-align:left;">
                                <div class="mtitle"><x-site.icon name="school" /> Schools onboarded</div>
                                <div class="msub" style="margin:4px 0 10px;">Your region · this month</div>
                                @foreach ([['Sunrise Academy', 'Live'], ["St. Mary's School", 'Live'], ['Delhi Public School', 'Demo booked']] as $k => $sc)
                                    <div class="mline ap" style="--d:{{ 0.4 * $k }}s"><span>{{ $sc[0] }}</span><span class="st {{ $sc[1] === 'Live' ? 'ok' : 'wait' }}">{{ $sc[1] }}</span></div>
                                @endforeach
                                <div class="zero-total pop" style="--d:1.6s"><span>Fast &amp; Easy Payouts</span><b><x-site.icon name="check" /></b></div>
                            </div>
                        </div>
                        <div class="h4" style="font-family:var(--display);font-size:24px;font-weight:800;letter-spacing:-.03em;color:var(--ink);">Schools need you</div>
                        <p style="color:var(--text);max-width:420px;line-height:1.65;">Thousands of schools across India are still running on paperwork. You bring them a
                            product that practically sells itself — and earn well doing it.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Joining process --}}
    <section class="section">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">How It Works</span>
                <h2 class="h2">Simple joining process</h2>
                <p class="lead">Getting started takes minutes. Here's exactly how you go from applying to earning.</p>
            </div>
            <div class="steps">
                @foreach ($steps as $i => $s)
                    <div class="step" style="--i:{{ $i }}">
                        <div class="step-n">{{ $s[0] }}</div>
                        <h3>{{ $s[1] }}</h3>
                        <p>{{ $s[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Apply form --}}
    <section class="section section-soft" id="apply" style="scroll-margin-top:72px;">
        <div class="container">
            <div class="head rv">
                <span class="eyebrow">Apply Now</span>
                <h2 class="h2">Send us your application</h2>
                <p class="lead">Fill in your details and our partnerships team will get back to you. Fields marked
                    <span style="color:var(--rose)">*</span> are required.</p>
            </div>

            <div class="apply-wrap form-card rv">
                <form id="executiveForm" onsubmit="handleExecutiveSubmit(event)" enctype="multipart/form-data">
                    {{-- Honeypot: hidden from real users; bots that fill it are silently dropped. --}}
                    <input type="text" name="company" tabindex="-1" autocomplete="off"
                        aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;">
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
                                inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                placeholder="10-digit mobile number">
                        </div>
                        <div class="fgroup">
                            <label class="flabel">Qualification <span class="req">*</span></label>
                            <input class="finput" type="text" name="qualification" required maxlength="120" placeholder="e.g. B.Com, MBA, Graduate">
                        </div>
                        <div class="fgroup full">
                            <label class="flabel">Address <span class="req">*</span></label>
                            <textarea class="ftext" name="address" required maxlength="200" placeholder="Your city, area and state" style="height:90px;"></textarea>
                        </div>
                        <div class="fgroup full">
                            <label class="flabel">About You / Why you want to join</label>
                            <textarea class="ftext" name="description" maxlength="500" placeholder="Tell us a little about your experience and why you'd be a great fit (optional)"></textarea>
                        </div>
                        <div class="fgroup full">
                            <label class="flabel">Attach Document <span class="opt">(Resume / ID — PDF, DOC or image, max 5 MB)</span></label>
                            <label class="file-drop" for="executiveDoc">
                                <x-site.icon name="paperclip" />
                                <span class="file-drop-t">Click to upload your document</span>
                                <span class="file-drop-n" id="fileName"></span>
                                <input type="file" name="document" id="executiveDoc" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="checkExecutiveFile(this)">
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
    </section>

@endsection

@push('scripts')
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
                siteToast('Error', 'Only PDF, DOC, DOCX, JPG or PNG files are allowed.', false);
                return;
            }
            if (fileInput.files[0].size > EXEC_MAX_BYTES) {
                siteToast('Error', 'Document must be 5 MB or smaller.', false);
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
