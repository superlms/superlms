{{--
    The Boards page's body on every template (user's ask of 2026-11-25): a
    Class 9–12 student signs in with their name, the first word of their
    father's name and their date of birth — all three as the school has them
    — and the page shows their personal and class details, their fee and the
    school's payment QR (shared.boards-result, sent back by
    SchoolBoardsController). The tab stays signed in for a while on a token
    in sessionStorage; Sign out forgets it.
--}}
@include('school-site.shared.lms-style')
@include('school-site.shared.boards-style')

<div class="bd" data-bd data-org="{{ (int) $site->organization_id }}">
    <div class="lms-card bd-login" data-bd-login>
        <div class="lms-head">
            <h2>Boards Login</h2>
            <span class="meta">Class 9 to 12</span>
            <p class="note">Enter your details exactly as the school has them to see your details, class and fee.</p>
        </div>
        <div class="lms-form">
            <div class="sform">
                <form data-bd-form novalidate>
                    <div class="sform-grid bd-grid3">
                        <label>
                            <span>Student Name <b>*</b></span>
                            <input type="text" name="name" data-label="your name" maxlength="100" autocomplete="name" placeholder="Your full name" required>
                        </label>
                        <label>
                            <span>Father's Name (first word) <b>*</b></span>
                            <input type="text" name="father" data-label="the first word of your father's name" maxlength="60" autocomplete="off" placeholder="e.g. Ramesh" required>
                        </label>
                        {{-- Day / Month / Year lists, not the browser's date box: that box puts the
                             day and month in the browser's own order (mm/dd/yyyy in US English), so a
                             date typed the Indian way went in as another date and no student matched
                             (2026-11-27). --}}
                        <label>
                            <span>Date of Birth <b>*</b></span>
                            <span class="bd-dob">
                                <select name="dob_d" data-label="the day you were born" aria-label="Day of birth" required>
                                    <option value="">Day</option>
                                    @for ($d = 1; $d <= 31; $d++)
                                        <option value="{{ $d }}">{{ $d }}</option>
                                    @endfor
                                </select>
                                <select name="dob_m" data-label="the month you were born" aria-label="Month of birth" required>
                                    <option value="">Month</option>
                                    @foreach (['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $i => $m)
                                        <option value="{{ $i + 1 }}">{{ $m }}</option>
                                    @endforeach
                                </select>
                                <select name="dob_y" data-label="the year you were born" aria-label="Year of birth" required>
                                    <option value="">Year</option>
                                    @for ($y = now()->year - 5; $y >= now()->year - 35; $y--)
                                        <option value="{{ $y }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </span>
                        </label>
                    </div>
                    <div class="sform-foot">
                        <button class="btn" type="submit">Login</button>
                        <p class="sform-msg" role="status"></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div data-bd-result hidden></div>
</div>

<script>
(function () {
    var box = document.querySelector('[data-bd]');
    if (!box) return;
    var org = Number(box.dataset.org), key = 'lms-boards-' + org;
    var login = box.querySelector('[data-bd-login]'), out = box.querySelector('[data-bd-result]');
    var form = box.querySelector('[data-bd-form]'), msg = form.querySelector('.sform-msg'), btn = form.querySelector('button[type=submit]');
    var store = {
        get: function () { try { return sessionStorage.getItem(key); } catch (e) { return null; } },
        set: function (v) { try { sessionStorage.setItem(key, v); } catch (e) {} },
        del: function () { try { sessionStorage.removeItem(key); } catch (e) {} }
    };
    var say = function (t, ok) { msg.textContent = t; msg.style.color = ok ? '#059669' : '#DC2626'; };
    var field = function (n) { return form.querySelector('[name=' + n + ']'); };
    var dd = field('dob_d'), mm = field('dob_m'), yy = field('dob_y');
    var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    // Days in the month picked (Feb in a leap year until the year is picked).
    var daysIn = function () {
        var m = Number(mm.value), y = Number(yy.value) || 2000;
        return m ? new Date(y, m, 0).getDate() : 31;
    };
    // A day the month picked does not have cannot be chosen.
    function fitDays() {
        var max = daysIn();
        Array.prototype.forEach.call(dd.options, function (o) { o.disabled = o.value !== '' && Number(o.value) > max; });
    }
    mm.addEventListener('change', fitDays);
    yy.addEventListener('change', fitDays);
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };

    function show(json) {
        store.set(json.token);
        out.innerHTML = json.html;
        out.hidden = false;
        login.hidden = true;
        out.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function signOut() {
        store.del();
        out.innerHTML = '';
        out.hidden = true;
        login.hidden = false;
        form.reset();
        fitDays();
        msg.textContent = '';
    }
    async function post(url, data) {
        var res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(Object.assign({ organization_id: org }, data)),
        });
        var json = await res.json().catch(function () { return {}; });
        return { ok: res.ok && json.success, status: res.status, json: json };
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var missing = Array.prototype.find.call(form.querySelectorAll('[required]'), function (f) { return !f.value.trim(); });
        if (missing) { say((missing.tagName === 'SELECT' ? 'Please choose ' : 'Please enter ') + missing.dataset.label + '.'); missing.focus(); return; }
        var d = Number(dd.value), m = Number(mm.value), y = Number(yy.value);
        if (d > daysIn()) { say(MONTHS[m - 1] + ' ' + y + ' has only ' + daysIn() + ' days. Please check the date of birth.'); dd.focus(); return; }

        var label = btn.textContent;
        btn.disabled = true; btn.textContent = 'Checking…'; msg.textContent = '';
        try {
            var val = function (n) { return field(n).value.trim(); };
            var r = await post('/api/website/school-boards/login', { name: val('name'), father: val('father'), dob: y + '-' + pad(m) + '-' + pad(d) });
            if (r.ok) { show(r.json); }
            else if (r.status === 429) { say('Too many tries. Please wait a minute and try again.'); }
            else {
                var first = r.json.errors ? Object.values(r.json.errors)[0][0] : null;
                say(first || r.json.message || 'Something went wrong. Please try again.');
            }
        } catch (err) {
            say('Network error. Please try again.');
        } finally {
            btn.disabled = false; btn.textContent = label;
        }
    });

    out.addEventListener('click', function (e) {
        if (e.target.closest('[data-bd-out]')) { signOut(); }
        if (e.target.closest('[data-bd-print]')) { window.print(); }
    });

    // Still signed in from earlier in this tab: straight to the details.
    var token = store.get();
    if (token) {
        post('/api/website/school-boards/me', { token: token })
            .then(function (r) { if (r.ok) { show(r.json); } else { store.del(); } })
            .catch(function () {});
    }
})();
</script>
