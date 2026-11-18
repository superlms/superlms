@extends('components.site.layout')

@section('title', 'Request a Free Demo')

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">Free Demo</span>
            <h1 class="h1 rv" style="--rd:.05s">Request a Free <span class="grad">Demo</span></h1>
            <p class="lead rv" style="--rd:.1s">Discover how SUPERLMS streamlines teaching, enhances learning, and simplifies
                school management—all within one powerful platform. In your personalized 30-minute demo, our education
                specialist will guide you through key features and show how SUPERLMS can transform your institution's
                daily operations and academic experience.</p>
        </div>
    </section>

    <section class="section" style="padding-top:16px;">
        <div class="container">
            <div class="demo-grid">
                <div class="rv rv-l">
                    <span class="eyebrow">What You'll See</span>
                    <h2 class="h3">Your Personalized <span class="grad">Live Demo</span></h2>
                    <div style="margin-top:26px;">@include('components.site.mock.scene', ['m' => 'democall'])</div>
                    <div class="flist">
                        @foreach ([['cap', 'Complete Student Dashboard'], ['calendar-check', 'Live Attendance Marking'], ['chart', 'Real-time Analytics & Reports'], ['wallet', 'Fee Collection & Receipts'], ['pen', 'Assignment & Quiz Creator'], ['smartphone', 'Mobile App Showcase'], ['message', 'Parent-Teacher Communication'], ['lock', 'Admin Control Panel']] as $i => $f)
                            <div class="frow-item rv" style="--rd:{{ $i * .04 }}s"><x-site.icon :name="$f[0]" /> {{ $f[1] }} <x-site.icon name="check" class="ok" /></div>
                        @endforeach
                    </div>
                </div>

                <div class="rv rv-r sticky">
                    <div class="form-card">
                        <div class="h4">Book Your Free Demo</div>
                        <p class="form-sub">Fill in the form and our team will reach out within 3 business days.</p>
                        <form id="demoForm" onsubmit="handleDemoSubmit(event)">
                            <div class="frow">
                                <div class="fgroup">
                                    <label class="flabel" for="dName">Your Name <span class="req">*</span></label>
                                    <input class="finput" id="dName" type="text" name="name" placeholder="Full name" required maxlength="40" />
                                </div>
                                <div class="fgroup">
                                    <label class="flabel" for="dSchool">School Name <span class="req">*</span></label>
                                    <input class="finput" id="dSchool" type="text" name="school" placeholder="School / Institution" required maxlength="100" />
                                </div>
                            </div>
                            <div class="frow">
                                <div class="fgroup">
                                    <label class="flabel" for="dPhone">Phone <span class="req">*</span></label>
                                    <input class="finput" id="dPhone" type="tel" name="phone" placeholder="10-digit mobile number"
                                        required inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}"
                                        title="Enter a valid 10-digit Indian mobile number starting with 6-9"
                                        oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)" />
                                </div>
                                <div class="fgroup">
                                    <label class="flabel" for="dEmail">Email <span class="req">*</span></label>
                                    <input class="finput" id="dEmail" type="email" name="email" placeholder="Email address"
                                        required maxlength="75" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" title="Enter a valid email address" />
                                </div>
                            </div>
                            <div class="frow">
                                <div class="fgroup">
                                    <label class="flabel" for="dCity">City <span class="req">*</span></label>
                                    <input class="finput" id="dCity" type="text" name="city" placeholder="Your city" required maxlength="50" />
                                </div>
                                <div class="fgroup">
                                    <label class="flabel" for="dStudents">No. of Students <span class="req">*</span></label>
                                    <select class="fselect" id="dStudents" name="students" required>
                                        <option value="">Select range</option>
                                        <option>Under 200</option>
                                        <option>200–500</option>
                                        <option>500–1000</option>
                                        <option>1000–2000</option>
                                        <option>2000+</option>
                                    </select>
                                </div>
                            </div>
                            <div class="fgroup">
                                <label class="flabel" for="dRole">Your Role <span class="req">*</span></label>
                                <select class="fselect" id="dRole" name="role" required>
                                    <option value="">Select role</option>
                                    <option>Principal</option>
                                    <option>Administrator</option>
                                    <option>Director</option>
                                    <option>Manager</option>
                                    <option>Other</option>
                                </select>
                            </div>

                            {{-- Schedule-call fields: hidden until "Schedule a Call" is chosen --}}
                            <div id="scheduleFields" hidden>
                                <div class="frow">
                                    <div class="fgroup">
                                        <label class="flabel" for="preferredDate">Preferred Date <span class="req">*</span></label>
                                        <input class="finput" type="date" name="preferred_date" id="preferredDate" />
                                    </div>
                                    <div class="fgroup">
                                        <label class="flabel" for="preferredTime">Preferred Time Slot <span class="req">*</span></label>
                                        <select class="fselect" name="preferred_time" id="preferredTime">
                                            <option value="">Select a slot</option>
                                            <option>10:00 AM – 12:00 PM</option>
                                            <option>12:00 PM – 02:00 PM</option>
                                            <option>02:00 PM – 04:00 PM</option>
                                            <option>04:00 PM – 06:00 PM</option>
                                            <option>06:00 PM – 08:00 PM</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="mode" id="formMode" value="demo" />
                            <button type="submit" class="btn btn-primary btn-block btn-submit" id="demoSubmitBtn">Book My Free Demo</button>
                            <button type="button" class="btn btn-outline btn-ghost-full" id="scheduleToggleBtn" onclick="toggleScheduleMode()">📞 Schedule a Call Instead</button>
                            <p class="form-note">No credit card required &nbsp;·&nbsp; No commitment &nbsp;·&nbsp; Completely free</p>
                        </form>
                    </div>
                </div>
            </div>

            <div class="trust rv">
                <span><x-site.icon name="school" /> <b id="trustSchools">many</b> Schools Trust Us</span>
                <span><x-site.icon name="star" /> <b id="trustRating">4.9</b>/5 Average Rating</span>
                <span><x-site.icon name="lock" /> 256-bit SSL Encrypted</span>
                <span><x-site.icon name="cap" /> <b id="trustStudents">100K+</b> Active Students</span>
                <span><x-site.icon name="smartphone" /> iOS &amp; Android Apps</span>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
    // ── Dynamic trust badges (schools / rating / students) ──
    fetch('/api/website/stats')
        .then(r => r.json())
        .then(({ data }) => {
            if (!data) return;
            const sc = document.getElementById('trustSchools');
            const ra = document.getElementById('trustRating');
            const st = document.getElementById('trustStudents');
            if (sc && data.schools > 0) sc.textContent = data.schools + '+';
            if (ra && data.rating) ra.textContent = data.rating;
            if (st && data.students > 0) st.textContent = siteFmt(data.students);
        })
        .catch(() => {});

    // ── Schedule-a-Call toggle ──
    // Reveals the date + timeslot fields and switches the form into schedule
    // mode (posts to /schedule-call instead of /demo).
    function toggleScheduleMode() {
        const fields = document.getElementById('scheduleFields');
        const modeEl = document.getElementById('formMode');
        const submit = document.getElementById('demoSubmitBtn');
        const toggle = document.getElementById('scheduleToggleBtn');
        const dateEl = document.getElementById('preferredDate');
        const timeEl = document.getElementById('preferredTime');
        const on = modeEl.value !== 'schedule';

        modeEl.value = on ? 'schedule' : 'demo';
        fields.hidden = !on;
        dateEl.required = on;
        timeEl.required = on;
        submit.textContent = on ? 'Schedule My Call' : 'Book My Free Demo';
        toggle.textContent = on ? '↩ Book a Demo Instead' : '📞 Schedule a Call Instead';
        toggle.classList.toggle('is-active', on);

        // Don't allow past dates.
        if (on && !dateEl.min) dateEl.min = new Date().toISOString().split('T')[0];
        if (on) fields.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    async function handleDemoSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('.btn-submit');
        const orig = btn.textContent;
        const isSchedule = document.getElementById('formMode').value === 'schedule';
        btn.textContent = 'Submitting…';
        btn.disabled = true;

        const payload = {
            full_name: form.name.value,
            school_name: form.school.value,
            phone: form.phone.value,
            email: form.email.value,
            city: form.city.value,
            no_of_students: form.students.value,
            role: form.role.value,
        };
        if (isSchedule) {
            payload.preferred_date = form.preferred_date.value;
            payload.preferred_time = form.preferred_time.value;
        }

        try {
            const res = await fetch(isSchedule ? '/api/website/schedule-call' : '/api/website/demo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const json = await res.json();
            if (json.success) {
                form.reset();
                if (isSchedule) toggleScheduleMode(); // reset back to demo mode
                siteToast('Demo Requested!', json.message || 'Request received!', true);
                // A demo request: straight on to its own page to pick the
                // date and time (the same link went by email and WhatsApp).
                if (json.booking_url) {
                    setTimeout(() => { window.location.href = json.booking_url; }, 1200);
                }
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
