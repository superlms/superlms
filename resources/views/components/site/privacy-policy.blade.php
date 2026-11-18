@extends('components.site.layout')

@section('title', 'Privacy Policy')

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">Legal</span>
            <h1 class="h1 rv" style="--rd:.05s">Privacy <span class="grad">Policy</span></h1>
            <p class="lead rv" style="--rd:.1s">SUPERLMS — superlms.in</p>
            <div class="phero-meta rv" style="--rd:.15s" id="ppLastUpdated">Last updated: —</div>
        </div>
    </section>

    <section class="section" style="padding-top:24px;">
        <div class="container">
            <div class="legal">

            <div id="ppSections"></div>
            <div id="ppFallback"></div>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    <script>
        (async () => {
            try {
                const res = await fetch('/api/website/privacy-policy');
                const json = await res.json();
                const data = json.data;

                if (data && data.sections && data.sections.length) {
                    // Last updated
                    if (data.last_updated) {
                        document.getElementById('ppLastUpdated').textContent = 'Last updated: ' + data.last_updated;
                    }

                    // Sections
                    const container = document.getElementById('ppSections');
                    data.sections.forEach((sec, i) => {
                        const div = document.createElement('div');
                        div.className = 'policy-section';
                        div.id = `pps${i+1}`;
                        div.innerHTML =
                            `<h2><span class="sec-num">${i+1}</span> ${escHtml(sec.head)}</h2><p>${escHtml(sec.desc)}</p>`;
                        container.appendChild(div);
                    });
                } else {
                    document.getElementById('ppLastUpdated').textContent = 'Last updated: January 1, 2025';
                    document.getElementById('ppFallback').innerHTML = getStaticPrivacy();
                }
            } catch (e) {
                document.getElementById('ppLastUpdated').textContent = 'Last updated: January 1, 2025';
                document.getElementById('ppFallback').innerHTML = getStaticPrivacy();
            }
        })();

        function escHtml(str) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(str || ''));
            return d.innerHTML;
        }

        function getStaticPrivacy() {
            return `
        <div class="policy-section"><h2><span class="sec-num">1</span> Introduction</h2><p>SUPERLMS is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform.</p></div>
        <div class="policy-section"><h2><span class="sec-num">2</span> Information We Collect</h2><p>We collect personal information, student data, usage data, and device information necessary for providing our services.</p></div>
        <div class="policy-section"><h2><span class="sec-num">3</span> Contact Us</h2><p>For privacy-related inquiries, contact us at support@superlms.in or call +91 9084748563.</p></div>`;
        }
    </script>
@endpush
