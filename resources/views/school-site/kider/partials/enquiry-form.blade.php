{{--
    Enquiry form — posts to the school's enquiry inbox via fetch: the form's
    type (the enquiry's type in the admin's Website Enquiries —
    SchoolWebsiteEnquiry::TYPES), every field filled in as its label and
    answer, and name, email, phone, subject and message as they are.
    Params: $site, $type (contact|query|appointment|career|admission), and
    optional $fields — each [name, label, type: text|email|tel|date|select|
    textarea, required, options, full, placeholder, subject (its answer is
    added to the subject)] — and $button.
--}}
@php
    $type   = $type ?? 'contact';
    $fields = $fields ?? [
        ['name' => 'name', 'label' => 'Your Name', 'required' => true, 'placeholder' => 'e.g. Priya Sharma'],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'you@example.com'],
        ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'placeholder' => '10-digit mobile'],
        ['name' => 'subject', 'label' => 'Subject', 'placeholder' => 'Admission enquiry'],
        ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'placeholder' => 'How can we help you?'],
    ];
    $button = $button ?? 'Send Message';
    $kind   = \App\Models\SchoolWebsiteEnquiry::TYPES[$type] ?? 'Enquiry';
@endphp
<form id="enquiryForm" class="form-card" data-type="{{ $type }}" data-kind="{{ $kind }}" onsubmit="return false;" novalidate>
    <input type="text" name="company" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;">
    <div class="form-grid">
        @foreach ($fields as $f)
            @php $f += ['type' => 'text', 'required' => false, 'options' => [], 'full' => false, 'placeholder' => '', 'subject' => false]; @endphp
            <div class="form-field {{ $f['full'] || $f['type'] === 'textarea' ? 'full' : '' }}">
                <label>{{ $f['label'] }}@if ($f['required']) <span style="color:var(--primary)">*</span>@endif</label>
                @if ($f['type'] === 'select')
                    <select name="{{ $f['name'] }}" data-label="{{ $f['label'] }}" @if ($f['subject']) data-subject="1" @endif @required($f['required'])>
                        <option value="">Select</option>
                        @foreach ($f['options'] as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                @elseif ($f['type'] === 'textarea')
                    <textarea name="{{ $f['name'] }}" rows="4" data-label="{{ $f['label'] }}" placeholder="{{ $f['placeholder'] }}" maxlength="3000" @required($f['required'])></textarea>
                @else
                    <input type="{{ $f['type'] }}" name="{{ $f['name'] }}" data-label="{{ $f['label'] }}" placeholder="{{ $f['placeholder'] }}"
                           @if ($f['subject']) data-subject="1" @endif maxlength="{{ $f['type'] === 'tel' ? 15 : 200 }}" @required($f['required'])>
                @endif
            </div>
        @endforeach
        <div class="form-field full" style="flex-direction:row;align-items:center;gap:16px;flex-wrap:wrap;">
            <button class="btn btn-primary btn-lg" type="submit" id="enquireBtn">{{ $button }}</button>
            <span id="enquiryMsg" class="form-note"></span>
        </div>
    </div>
</form>

@once
<style>
    .form-field select {
        font-family: 'Poppins', sans-serif; font-size: 14px; color: var(--text);
        background: var(--bg2); border: 1.5px solid var(--border2); border-radius: 14px;
        padding: 12px 16px; transition: all .2s; width: 100%;
    }
    .form-field select:focus { outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px var(--faint); }
</style>
@endonce

@push('scripts')
<script>
(function () {
    var form = document.getElementById('enquiryForm');
    if (!form) return;
    form.addEventListener('submit', async function () {
        var btn = document.getElementById('enquireBtn');
        var msg = document.getElementById('enquiryMsg');
        var say = function (t, ok) { msg.style.color = ok ? '#1a8a3a' : '#c0392b'; msg.textContent = t; };
        var missing = Array.prototype.find.call(form.querySelectorAll('[required]'), function (f) { return !f.value.trim(); });
        if (missing) { say('Please fill in ' + missing.dataset.label + '.'); missing.focus(); return; }

        // Name, email, phone, subject and message as they are; every field
        // filled in as its label and answer; any other field into the message too.
        var data = { organization_id: {{ (int) $site->organization_id }}, company: form.company.value,
                     type: form.dataset.type, subject: '', fields: [] }, extra = [], body = '', subj = '';
        form.querySelectorAll('[data-label]').forEach(function (f) {
            var v = f.value.trim(), n = f.name;
            if (v && f.type === 'date') { v = new Date(v + 'T00:00:00').toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }); }
            if (v) { data.fields.push({ label: f.dataset.label, value: v }); }
            if (n === 'name' || n === 'email' || n === 'phone') { data[n] = v; }
            else if (n === 'subject') { subj = v; }
            else if (n === 'message') { body = v; }
            else if (v) { extra.push(f.dataset.label + ': ' + v); if (f.dataset.subject) subj += (subj ? ' — ' : '') + v; }
        });
        data.subject = form.querySelector('[name=subject]') ? subj : form.dataset.kind + (subj ? ' — ' + subj : '');
        data.message = extra.join('\n') + (extra.length && body ? '\n\n' : '') + body;

        var label = btn.textContent;
        btn.disabled = true; btn.textContent = 'Sending...'; msg.textContent = '';
        try {
            var res = await fetch('/api/website/school-contact', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(data),
            });
            var json = await res.json().catch(function () { return {}; });
            if (res.ok && json.success) {
                form.reset();
                say(json.message || 'Thank you! We will get back to you soon.', true);
            } else {
                var first = json.errors ? Object.values(json.errors)[0][0] : null;
                say(first || json.message || 'Something went wrong. Please try again.');
            }
        } catch (e) {
            say('Network error. Please try again.');
        } finally {
            btn.disabled = false; btn.textContent = label;
        }
    });
})();
</script>
@endpush
