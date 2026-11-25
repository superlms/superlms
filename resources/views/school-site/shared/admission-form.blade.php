{{--
    The Online Admission form on every template (user's ask of 2026-11-25):
    the fields of the admin's Add Student form, in its order and with its
    marks — Full Name, Email, Mobile, Gender, Class, Section, Date of Birth,
    Date of Admission, Religion, Aadhar Number, Father's and Mother's Name,
    Apaar ID, Registration Number, State, City, Pincode, Transport (and its
    route), Local and Permanent Address — with the student's photo and a
    signature of 15 KB to 25 KB each. A bigger picture is made smaller in the
    browser until it is in that range; a smaller one is turned down.

    Posted (multipart) to SchoolAdmissionController, which puts it in the
    admin's Admissions and Website Enquiries both. Not a .sform form: the
    layouts' enquiry script posts those as JSON, with no files.
--}}
@php
    use App\Helpers\Constants;
    use App\Http\Controllers\SchoolAdmissionController;
    use App\Support\SchoolSiteData;

    $classes = $lms->admissionClasses();
    // No classes on the Standard page yet: their names from Website Data, else the usual list.
    $classNames = $classes ? [] : (array_values(array_filter(array_map(fn ($k) => trim((string) ($k['title'] ?? '')), $c['classes'] ?? [])))
        ?: ['Play Group', 'Nursery', 'LKG', 'UKG', 'Class I', 'Class II', 'Class III', 'Class IV', 'Class V', 'Class VI',
            'Class VII', 'Class VIII', 'Class IX', 'Class X', 'Class XI', 'Class XII']);
    $routes = $lms->admissionRoutes();
    $states = SchoolSiteData::statesAndCities();
    $minKb  = SchoolAdmissionController::MIN_KB;
    $maxKb  = SchoolAdmissionController::MAX_KB;
    $person = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="26" height="26" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
    $pen    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.86 4.49l2.65 2.65M4 20l4.24-.94L19.5 7.8a1.87 1.87 0 00-2.65-2.65L5.6 16.4 4 20z"/></svg>';
@endphp

@once
<style>
    .af [hidden] { display: none !important; }
    .af-file { display: flex; flex-direction: column; gap: 6px; }
    .af-file + .sform-grid, .af-grid + .af-file { margin-top: 16px; }
    .af-lbl { font-size: 12.5px; font-weight: 500; color: #374151; }
    .af-lbl b { color: #EF4444; font-weight: 500; }
    .af-lbl small { font-size: 12px; font-weight: 400; color: #9CA3AF; }
    .af-pick { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .af-prev { flex: none; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; overflow: hidden;
        border-radius: 50%; border: 1px solid #E5E7EB; color: var(--primary); background: color-mix(in srgb, var(--primary) 9%, #fff); }
    .af-prev img { width: 100%; height: 100%; object-fit: cover; }
    .af-sign .af-prev { width: 150px; border-radius: 8px; }
    .af-sign .af-prev img { object-fit: contain; background: #fff; }
    .lms-card .lms-form .sform.af label.af-btn, .sform.af label.af-btn { display: inline-flex; flex-direction: row; align-items: center; gap: 6px; padding: 8px 14px; margin: 0;
        border: 1px solid #D1D5DB; border-radius: 8px; background: #fff; font-size: 13px; font-weight: 600; color: #374151; cursor: pointer; transition: border-color .15s, color .15s; }
    .af label.af-btn:hover { border-color: var(--primary); color: var(--primary); }
    .af label.af-btn input { display: none; }
    .af-info { font-size: 12.5px; color: #6B7280; }
    .af-info.ok { color: #059669; font-weight: 500; }
    .af-info.err { color: #DC2626; font-weight: 500; }
    .af-note { margin: 4px 0 0; font-size: 11.5px; color: #9CA3AF; }
    .af-route { margin-top: 6px; padding: 8px 10px; border-radius: 8px; background: color-mix(in srgb, var(--primary) 7%, #fff); font-size: 12.5px; color: #374151; }
</style>
@endonce

<div class="sform af" data-af>
    <form data-af-form data-org="{{ (int) $site->organization_id }}" novalidate>
        <div class="hp" aria-hidden="true"><input type="text" name="sf_check" tabindex="-1" autocomplete="off"></div>

        <div class="af-file" data-af-file="photo" data-label="the student photo">
            <span class="af-lbl">Student Profile Photo <b>*</b> <small>(JPG / PNG, {{ $minKb }} KB to {{ $maxKb }} KB)</small></span>
            <div class="af-pick">
                <span class="af-prev" data-af-prev>{!! $person !!}</span>
                <label class="af-btn">Choose Photo<input type="file" accept="image/jpeg,image/png,image/webp" data-af-input hidden></label>
                <span class="af-info" data-af-info>No photo chosen</span>
            </div>
        </div>

        <div class="sform-grid af-grid">
            <label>
                <span>Full Name <b>*</b></span>
                <input type="text" name="name" data-label="Full Name" maxlength="50" placeholder="Enter full name" autocomplete="name" required>
            </label>
            <label>
                <span>Email <small style="color:#9CA3AF;font-weight:400">(optional)</small></span>
                <input type="email" name="email" data-label="Email" maxlength="191" placeholder="student@example.com" autocomplete="email">
            </label>
            <label>
                <span>Mobile <b>*</b></span>
                <input type="tel" name="mobile" data-label="Mobile" maxlength="10" inputmode="numeric" placeholder="10-digit mobile" autocomplete="tel"
                       oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)" required>
            </label>
            <label>
                <span>Gender <b>*</b></span>
                <select name="gender" data-label="Gender" required>
                    <option value="">Select Gender</option>
                    @foreach (Constants::GENDER as $g)
                        <option value="{{ $g }}">{{ ucfirst($g) }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Class <b>*</b></span>
                @if ($classes)
                    <select name="standard_id" data-label="Class" data-af-class required>
                        <option value="">Select Class</option>
                        @foreach ($classes as $k)
                            <option value="{{ $k['id'] }}">{{ $k['name'] }}</option>
                        @endforeach
                    </select>
                @else
                    <select name="class_name" data-label="Class" required>
                        <option value="">Select Class</option>
                        @foreach ($classNames as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                @endif
            </label>
            <label data-af-section-wrap hidden>
                <span>Section <b>*</b></span>
                <select name="section_id" data-label="Section" data-af-section>
                    <option value="">Select Section</option>
                </select>
            </label>
            <label>
                <span>Date of Birth <b>*</b></span>
                <input type="date" name="dob" data-label="Date of Birth" max="{{ now()->subDay()->toDateString() }}" required>
            </label>
            <label>
                <span>Date of Admission <small style="color:#9CA3AF;font-weight:400">(optional)</small></span>
                <input type="date" name="date_of_admission" data-label="Date of Admission">
            </label>
            <label>
                <span>Religion</span>
                <input type="text" name="religion" data-label="Religion" maxlength="20" placeholder="e.g. Hindu, Muslim">
            </label>
            <label>
                <span>Aadhar Number</span>
                <input type="text" name="aadhar_no" data-label="Aadhar Number" maxlength="12" inputmode="numeric" placeholder="12-digit Aadhar no."
                       oninput="this.value=this.value.replace(/\D/g,'').slice(0,12)">
            </label>
            <label>
                <span>Father's Name <b>*</b></span>
                <input type="text" name="father_name" data-label="Father's Name" maxlength="50" placeholder="Father's full name" required>
            </label>
            <label>
                <span>Mother's Name <small style="color:#9CA3AF;font-weight:400">(optional)</small></span>
                <input type="text" name="mother_name" data-label="Mother's Name" maxlength="50" placeholder="Mother's full name">
            </label>
            <label>
                <span>Apaar ID</span>
                <input type="text" name="appar_id" data-label="Apaar ID" maxlength="25" placeholder="Apaar ID">
            </label>
            <label>
                <span>Registration Number</span>
                <input type="text" name="registration_number" data-label="Registration Number" maxlength="25" placeholder="Registration number">
            </label>
            <label>
                <span>State</span>
                <select name="state" data-label="State" data-af-state>
                    <option value="">Select State</option>
                    @foreach (array_keys($states) as $state)
                        <option value="{{ $state }}">{{ $state }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>City</span>
                <select name="city" data-label="City" data-af-city disabled>
                    <option value="">Select City</option>
                </select>
            </label>
            <label>
                <span>Pincode</span>
                <input type="text" name="pincode" data-label="Pincode" maxlength="6" inputmode="numeric" placeholder="6-digit pincode"
                       oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)">
            </label>
            @if ($routes)
                <label>
                    <span>Transport Required? <b>*</b></span>
                    <select name="transport" data-label="Transport Required?" data-af-transport>
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </label>
                <label data-af-route-wrap hidden>
                    <span>Select Route <b>*</b></span>
                    <select name="route_id" data-label="Route" data-af-route>
                        <option value="">Select Route</option>
                        @foreach ($routes as $r)
                            <option value="{{ $r['id'] }}" data-monthly="{{ $r['monthly'] }}">{{ $r['name'] }} — ₹{{ number_format($r['monthly'], 0) }}/mo</option>
                        @endforeach
                    </select>
                    <span class="af-route" data-af-route-fee hidden></span>
                </label>
            @else
                <input type="hidden" name="transport" value="0">
            @endif
            <label class="wide">
                <span>Local Address</span>
                <textarea name="local_address" data-label="Local Address" rows="2" maxlength="250" placeholder="Current/local address"></textarea>
            </label>
            <label class="wide">
                <span>Permanent Address</span>
                <textarea name="permanent_address" data-label="Permanent Address" rows="2" maxlength="250" placeholder="Permanent address (if different)"></textarea>
            </label>
        </div>

        <div class="af-file af-sign" data-af-file="signature" data-label="the signature">
            <span class="af-lbl">Signature <b>*</b> <small>(JPG / PNG, {{ $minKb }} KB to {{ $maxKb }} KB)</small></span>
            <div class="af-pick">
                <span class="af-prev" data-af-prev>{!! $pen !!}</span>
                <label class="af-btn">Choose Signature<input type="file" accept="image/jpeg,image/png,image/webp" data-af-input hidden></label>
                <span class="af-info" data-af-info>No signature chosen</span>
            </div>
            <p class="af-note">A bigger photo or signature is made smaller to fit {{ $minKb }}–{{ $maxKb }} KB before it is sent.</p>
        </div>

        <div class="sform-foot">
            <button class="btn" type="submit">Submit</button>
            <p class="sform-msg" role="status"></p>
        </div>
    </form>
</div>
<script type="application/json" data-af-data>@json(['classes' => $classes, 'states' => $states])</script>
<script>
(function () {
    var form = document.querySelector('[data-af-form]');
    if (!form) return;
    var data = JSON.parse(document.querySelector('[data-af-data]').textContent || '{}');
    var MIN = {{ $minKb }} * 1024, MAX = {{ $maxKb }} * 1024;
    var msg = form.querySelector('.sform-msg'), btn = form.querySelector('button[type=submit]');
    var say = function (t, ok) { msg.textContent = t; msg.style.color = ok ? '#059669' : '#DC2626'; };
    var picked = {};
    var $ = function (sel) { return form.querySelector(sel); };
    var opt = function (value, text) { var o = document.createElement('option'); o.value = value; o.textContent = text; return o; };

    // ── Class → its sections (a class with none has no Section field) ──
    var classSel = $('[data-af-class]'), secWrap = $('[data-af-section-wrap]'), secSel = $('[data-af-section]');
    function fillSections() {
        var k = (data.classes || []).find(function (c) { return String(c.id) === (classSel ? classSel.value : ''); });
        var list = k ? k.sections : [];
        secSel.innerHTML = ''; secSel.appendChild(opt('', 'Select Section'));
        list.forEach(function (s) { secSel.appendChild(opt(s.id, s.name)); });
        if (list.length === 1) secSel.value = String(list[0].id);
        secWrap.hidden = !list.length;
        secSel.required = !!list.length;
    }
    if (classSel) classSel.addEventListener('change', fillSections);

    // ── State → its cities ──
    var stateSel = $('[data-af-state]'), citySel = $('[data-af-city]');
    function fillCities() {
        var list = (data.states || {})[stateSel.value] || [];
        citySel.innerHTML = ''; citySel.appendChild(opt('', 'Select City'));
        list.forEach(function (c) { citySel.appendChild(opt(c, c)); });
        citySel.disabled = !stateSel.value;
    }
    stateSel.addEventListener('change', fillCities);

    // ── Transport → its route and the route's fee ──
    var trSel = $('[data-af-transport]'), routeWrap = $('[data-af-route-wrap]'), routeSel = $('[data-af-route]'), routeFee = $('[data-af-route-fee]');
    var inr = function (n) { return '₹' + Number(n).toLocaleString('en-IN', { maximumFractionDigits: 2 }); };
    function showRoute() {
        if (!trSel) return;
        var on = trSel.value === '1';
        routeWrap.hidden = !on; routeSel.required = on;
        var o = routeSel.selectedOptions[0], m = o && o.value ? Number(o.dataset.monthly) : null;
        routeFee.hidden = !(on && m !== null);
        if (m !== null) routeFee.textContent = 'Monthly Fee ' + inr(m) + ' · Annual (× 11) ' + inr(m * 11);
    }
    if (trSel) { trSel.addEventListener('change', showRoute); routeSel.addEventListener('change', showRoute); }

    // ── Photo and signature: 15–25 KB, a bigger one made smaller here ──
    var kb = function (n) { return (Math.round(n / 102.4) / 10) + ' KB'; };
    function loadImg(file) {
        return new Promise(function (ok, no) {
            var url = URL.createObjectURL(file), img = new Image();
            img.onload = function () { ok(img); };
            img.onerror = function () { URL.revokeObjectURL(url); no(new Error('This file could not be read as a picture. Please choose a JPG or PNG.')); };
            img.src = url;
        });
    }
    function jpeg(img, side, q) {
        var w0 = img.naturalWidth, h0 = img.naturalHeight, s = Math.min(1, side / Math.max(w0, h0));
        var w = Math.max(1, Math.round(w0 * s)), h = Math.max(1, Math.round(h0 * s));
        var cv = document.createElement('canvas'); cv.width = w; cv.height = h;
        var cx = cv.getContext('2d'); cx.fillStyle = '#fff'; cx.fillRect(0, 0, w, h); cx.drawImage(img, 0, 0, w, h);
        return new Promise(function (ok) { cv.toBlob(function (b) { ok(b); }, 'image/jpeg', q); });
    }
    async function fit(file, what) {
        if (!/^image\/(jpeg|png|webp)$/i.test(file.type)) throw new Error('The ' + what + ' must be a JPG, PNG or WebP image.');
        if (file.size >= MIN && file.size <= MAX) return file;
        if (file.size < MIN) throw new Error('This ' + what + ' is only ' + kb(file.size) + '. It must be {{ $minKb }} KB to {{ $maxKb }} KB — please choose a clearer one.');
        var img = await loadImg(file), big = Math.min(2400, Math.max(img.naturalWidth, img.naturalHeight));
        var base = (file.name || what).replace(/\.[^.]+$/, '') || 'image';
        var done = function (b) { return new File([b], base + '.jpg', { type: 'image/jpeg' }); };
        var qs = [0.92, 0.85, 0.75, 0.65, 0.5];
        for (var i = 0; i < qs.length; i++) {
            var top = await jpeg(img, big, qs[i]);
            if (top.size < MIN) {
                if (i === 0) { top = await jpeg(img, big, 1); if (top.size >= MIN && top.size <= MAX) return done(top); }
                break;
            }
            if (top.size <= MAX) return done(top);
            var lo = 40, hi = big;
            for (var k = 0; k < 16 && hi - lo > 1; k++) {
                var mid = Math.round((lo + hi) / 2), b = await jpeg(img, mid, qs[i]);
                if (b.size > MAX) hi = mid; else if (b.size < MIN) lo = mid; else return done(b);
            }
        }
        throw new Error('This ' + what + ' could not be made {{ $minKb }} KB to {{ $maxKb }} KB. Please choose another one.');
    }
    form.querySelectorAll('[data-af-file]').forEach(function (box) {
        var key = box.dataset.afFile, input = box.querySelector('[data-af-input]'), prev = box.querySelector('[data-af-prev]'),
            info = box.querySelector('[data-af-info]'), blank = prev.innerHTML, idle = info.textContent,
            what = key === 'photo' ? 'photo' : 'signature';
        box._reset = function () { delete picked[key]; prev.innerHTML = blank; info.textContent = idle; info.className = 'af-info'; input.value = ''; };
        input.addEventListener('change', async function () {
            var file = input.files && input.files[0];
            if (!file) return;
            delete picked[key];
            info.className = 'af-info'; info.textContent = 'Checking…';
            try {
                var f = await fit(file, what);
                picked[key] = f;
                prev.innerHTML = ''; var im = new Image(); im.alt = ''; im.src = URL.createObjectURL(f); prev.appendChild(im);
                info.className = 'af-info ok';
                info.textContent = (f === file ? '' : 'Made smaller: ') + kb(f.size);
            } catch (e) {
                prev.innerHTML = blank; input.value = '';
                info.className = 'af-info err'; info.textContent = e.message;
            }
        });
    });

    // ── Send ──
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var fail = function (t, el) { say(t); if (el && el.focus) { el.focus(); } return false; };

        if (!picked.photo) return fail('Please add the student photo ({{ $minKb }} KB to {{ $maxKb }} KB).', $('[data-af-file=photo] input'));
        var missing = Array.prototype.find.call(form.querySelectorAll('[required]'), function (f) { return !f.disabled && !f.closest('[hidden]') && !f.value.trim(); });
        if (missing) return fail('Please fill in ' + missing.dataset.label + '.', missing);
        var v = function (n) { var f = $('[name=' + n + ']'); return f ? f.value.trim() : ''; };
        if (v('mobile').length !== 10) return fail('Mobile number must be exactly 10 digits.', $('[name=mobile]'));
        if (v('email') && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v('email'))) return fail('Please enter a valid email address.', $('[name=email]'));
        if (v('aadhar_no') && v('aadhar_no').length !== 12) return fail('Aadhar number must be exactly 12 digits.', $('[name=aadhar_no]'));
        if (v('pincode') && v('pincode').length !== 6) return fail('Pincode must be exactly 6 digits.', $('[name=pincode]'));
        if (!picked.signature) return fail('Please add the signature ({{ $minKb }} KB to {{ $maxKb }} KB).', $('[data-af-file=signature] input'));

        var fd = new FormData(form);
        fd.delete('sf_check');
        fd.append('company', v('sf_check'));
        fd.append('organization_id', form.dataset.org);
        fd.append('photo', picked.photo, picked.photo.name);
        fd.append('signature', picked.signature, picked.signature.name);

        var label = btn.textContent;
        btn.disabled = true; btn.textContent = 'Sending…'; msg.textContent = '';
        try {
            var res = await fetch('/api/website/school-admission', { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
            var json = await res.json().catch(function () { return {}; });
            if (res.ok && json.success) {
                form.reset();
                form.querySelectorAll('[data-af-file]').forEach(function (b) { b._reset(); });
                if (classSel) fillSections();
                fillCities(); showRoute();
                say(json.message || 'Thank you! Your admission form has been sent.', true);
            } else if (res.status === 429) {
                say('Too many tries. Please wait a minute and send it again.');
            } else {
                var errs = json.errors || {}, field = Object.keys(errs)[0];
                say(field ? errs[field][0] : (json.message || 'Something went wrong. Please try again.'));
                var el = field && $('[name=' + field + ']');
                if (el && el.focus) el.focus();
            }
        } catch (err) {
            say('Network error. Please try again.');
        } finally {
            btn.disabled = false; btn.textContent = label;
        }
    });
})();
</script>
