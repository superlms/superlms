{{-- Enquiry form — posts to the public API via fetch. Param: $site --}}
<form id="enquiryForm" onsubmit="return false;">
    <div class="row g-3">
        <div class="col-md-6">
            <input type="text" name="name" class="form-control border-0 bg-light px-4 py-3" placeholder="Your Name" required>
        </div>
        <div class="col-md-6">
            <input type="email" name="email" class="form-control border-0 bg-light px-4 py-3" placeholder="Your Email">
        </div>
        <div class="col-md-6">
            <input type="text" name="phone" class="form-control border-0 bg-light px-4 py-3" placeholder="Your Phone">
        </div>
        <div class="col-md-6">
            <input type="text" name="subject" class="form-control border-0 bg-light px-4 py-3" placeholder="Subject">
        </div>
        <div class="col-12">
            <textarea name="message" class="form-control border-0 bg-light px-4 py-3" rows="4" placeholder="Message"></textarea>
        </div>
        <div class="col-12">
            <button class="btn btn-primary rounded-pill py-3 px-5" type="submit" id="enquireBtn">Send Message</button>
            <span id="enquiryMsg" class="ms-3 fw-medium"></span>
        </div>
    </div>
</form>

@push('scripts')
<script>
(function () {
    var form = document.getElementById('enquiryForm');
    if (!form) return;
    form.addEventListener('submit', async function () {
        var btn = document.getElementById('enquireBtn');
        var msg = document.getElementById('enquiryMsg');
        var data = Object.fromEntries(new FormData(form).entries());
        data.organization_id = {{ (int) $site->organization_id }};
        if (!data.name) { msg.textContent = 'Please enter your name.'; msg.style.color = '#c00'; return; }
        btn.disabled = true; btn.textContent = 'Sending...'; msg.textContent = '';
        try {
            var res = await fetch('/api/website/school-contact', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(data),
            });
            var json = await res.json();
            if (res.ok && json.success) {
                form.reset();
                msg.style.color = '#1a8a3a'; msg.textContent = json.message || 'Thank you! We will get back to you soon.';
            } else {
                msg.style.color = '#c00'; msg.textContent = json.message || 'Something went wrong. Please try again.';
            }
        } catch (e) {
            msg.style.color = '#c00'; msg.textContent = 'Network error. Please try again.';
        } finally {
            btn.disabled = false; btn.textContent = 'Send Message';
        }
    });
})();
</script>
@endpush
