@extends('components.site.layout')

@section('title', 'Contact Us')

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">Contact Us</span>
            <h1 class="h1 rv" style="--rd:.05s">Your Doubt, <span class="grad">Our Priority</span></h1>
            <p class="lead rv" style="--rd:.1s">Have a question or concern? We're always ready to help. No issue is too small, and
                every query receives our full attention. Our dedicated support team listens carefully, responds quickly, and
                provides clear, dependable solutions—so you can move forward with confidence and complete peace of mind.</p>
        </div>
    </section>

    <section class="section" style="padding-top:16px;">
        <div class="container contact-grid">
            <div class="rv rv-l">
                <span class="eyebrow">Get In Touch</span>
                <div class="info-list">
                    <a class="info" href="mailto:support@superlms.in">
                        <div class="icon-sq"><x-site.icon name="mail" /></div>
                        <div><div class="info-l">Email Address</div><div class="info-v">support@superlms.in</div></div>
                    </a>
                    <a class="info" href="tel:+919084748563">
                        <div class="icon-sq"><x-site.icon name="phone" /></div>
                        <div><div class="info-l">Phone / WhatsApp</div><div class="info-v">+91 9084748563</div></div>
                    </a>
                    <div class="info">
                        <div class="icon-sq"><x-site.icon name="map-pin" /></div>
                        <div><div class="info-l">Office Address</div><div class="info-v">Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137</div></div>
                    </div>
                </div>
                <div class="vpanel mint contact-vis" style="margin-top:14px;min-height:0;padding:28px;">
                    @include('components.site.mock.scene', ['m' => 'support'])
                </div>
            </div>

            <div class="rv rv-r sticky">
                <div class="form-card">
                    <div class="h4" style="margin-bottom:24px;">Send Us a Message</div>
                    <form id="contactForm" onsubmit="handleContactSubmit(event)">
                        <div class="frow">
                            <div class="fgroup">
                                <label class="flabel" for="cName">Full Name <span class="req">*</span></label>
                                <input class="finput" id="cName" type="text" name="name" placeholder="Your full name" required maxlength="40" />
                            </div>
                            <div class="fgroup">
                                <label class="flabel" for="cSchool">School Name <span class="req">*</span></label>
                                <input class="finput" id="cSchool" type="text" name="school" placeholder="Your school name" required maxlength="100" />
                            </div>
                        </div>
                        <div class="frow">
                            <div class="fgroup">
                                <label class="flabel" for="cPhone">Phone <span class="req">*</span></label>
                                <input class="finput" id="cPhone" type="tel" name="phone" placeholder="10-digit mobile number"
                                    required inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}"
                                    title="Enter a valid 10-digit Indian mobile number starting with 6-9"
                                    oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)" />
                            </div>
                            <div class="fgroup">
                                <label class="flabel" for="cEmail">Email <span class="req">*</span></label>
                                <input class="finput" id="cEmail" type="email" name="email" placeholder="your@email.com"
                                    required maxlength="75" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" title="Enter a valid email address" />
                            </div>
                        </div>
                        <div class="fgroup">
                            <label class="flabel" for="cSubject">Subject <span class="req">*</span></label>
                            <select class="fselect" id="cSubject" name="subject" required>
                                <option value="">Select a subject</option>
                                <option>General Inquiry</option>
                                <option>Request Demo</option>
                                <option>Pricing Information</option>
                                <option>Technical Support</option>
                                <option>Partnership</option>
                            </select>
                        </div>
                        <div class="fgroup">
                            <label class="flabel" for="cMsg">Message <span class="req">*</span></label>
                            <textarea class="ftext" id="cMsg" name="message" placeholder="Tell us how we can help..." required maxlength="2000"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-submit">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
    async function handleContactSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('.btn-submit');
        const orig = btn.textContent;
        btn.textContent = 'Sending…';
        btn.disabled = true;
        try {
            const res = await fetch('/api/website/contact', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    full_name: form.name.value,
                    school_name: form.school.value,
                    phone_number: form.phone.value,
                    email: form.email.value,
                    subject: form.subject.value,
                    description: form.message.value,
                }),
            });
            const json = await res.json();
            if (json.success) {
                form.reset();
                siteToast('Message Sent!', json.message || 'Message sent!', true);
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
