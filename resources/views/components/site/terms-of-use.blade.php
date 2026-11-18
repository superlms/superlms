@extends('components.site.layout')

@section('title', 'Terms of Use')

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">Legal</span>
            <h1 class="h1 rv" style="--rd:.05s">Terms of <span class="grad">Use</span></h1>
            <p class="lead rv" style="--rd:.1s" id="touSubtitle">SUPERLMS — superlms.in</p>
            <div class="phero-meta rv" style="--rd:.15s" id="touLastUpdated">Last updated: —</div>
        </div>
    </section>

    <section class="section" style="padding-top:24px;">
        <div class="container">
            <div class="legal">

            <div id="touSections"></div>
            <div id="touFallback">
                <!-- Static fallback content -->
                <div class="policy-section" id="u1">
                    <h2><span class="sec-num">1</span> Acceptance of Terms of Use</h2>
                    <p>By accessing or using the SUPERLMS platform, you agree to comply with and be bound by these
                        Terms of Use. These Terms govern your day-to-day use of the platform and its features.</p>
                </div>

                <div class="policy-section" id="u2">
                    <h2><span class="sec-num">2</span> Permitted Use</h2>
                    <p>You may use the SUPERLMS platform only for lawful educational and institutional management
                        purposes. Any use outside the scope of your subscription plan or these terms is strictly
                        prohibited.</p>
                </div>

                <div class="policy-section" id="u3">
                    <h2><span class="sec-num">3</span> Contact Us</h2>
                    <p>For any questions about these Terms of Use, please contact us at:</p>
                    <div class="contact-info-card">
                        <div class="contact-info-row"><span>📧</span><span>support@superlms.in</span></div>
                        <div class="contact-info-row"><span>📱</span><span>+91 9084748563</span></div>
                        <div class="contact-info-row"><span>📍</span><span>Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137</span></div>
                    </div>
                </div>
            </div><!-- /touFallback -->
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    <script>
        (async () => {
            try {
                const res = await fetch('/api/website/terms-of-use');
                const json = await res.json();
                const data = json.data;

                if (data && data.sections && data.sections.length) {
                    if (data.last_updated) {
                        document.getElementById('touLastUpdated').textContent = 'Last updated: ' + data
                        .last_updated;
                    }

                    const container = document.getElementById('touSections');
                    data.sections.forEach((sec, i) => {
                        const div = document.createElement('div');
                        div.className = 'policy-section';
                        div.id = `tous${i+1}`;
                        div.innerHTML =
                            `<h2><span class="sec-num">${i+1}</span> ${escHtml(sec.head)}</h2><p>${escHtml(sec.desc)}</p>`;
                        container.appendChild(div);
                    });

                    // Hide static fallback
                    document.getElementById('touFallback').style.display = 'none';
                } else {
                    document.getElementById('touLastUpdated').textContent = 'Last updated: January 1, 2025';
                }
            } catch (e) {
                document.getElementById('touLastUpdated').textContent = 'Last updated: January 1, 2025';
            }
        })();

        function escHtml(str) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(str || ''));
            return d.innerHTML;
        }
    </script>
@endpush
