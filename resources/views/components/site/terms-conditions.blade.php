@extends('components.site.layout')

@section('title', 'Terms & Conditions')

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">Legal</span>
            <h1 class="h1 rv" style="--rd:.05s">Terms &amp; <span class="grad">Conditions</span></h1>
            <p class="lead rv" style="--rd:.1s" id="tcPlatformName">SUPERLMS — superlms.in</p>
            <div class="phero-meta rv" style="--rd:.15s" id="tcLastUpdated">Last updated: —</div>
        </div>
    </section>

    <section class="section" style="padding-top:24px;">
        <div class="container">
            <div class="legal">

            <div id="tcSections"></div>
            <div id="tcFallback">
                <!-- 1 -->
                <div class="policy-section" id="t1">
                    <h2><span class="sec-num">1</span> Acceptance of Terms</h2>
                    <p>By accessing or using the SUPERLMS platform ("Service") available at superlms.in and our
                        mobile
                        applications, you ("User," "School," or "Institution") agree to be bound by these Terms &amp;
                        Conditions ("Terms"). These Terms constitute a legally binding agreement between you and SUPERLMS
                        LMS
                        ("Company," "we," "us," or "our").</p>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">⚠️</div>
                        <p>If you do not agree to these Terms, you must immediately discontinue use of our platform.
                            Continued use of the Service after any updates to these Terms constitutes acceptance of the
                            updated Terms.</p>
                    </div>
                </div>

                <!-- 2 -->
                <div class="policy-section" id="t2">
                    <h2><span class="sec-num">2</span> Description of Service</h2>
                    <p>SUPERLMS is a cloud-based Learning Management System (LMS) designed for schools and educational
                        institutions in India. The Service includes, but is not limited to:</p>
                    <ul>
                        <li>Student and teacher management modules</li>
                        <li>Attendance tracking (manual and biometric)</li>
                        <li>Academic management — timetables, syllabus, homework, quizzes</li>
                        <li>Fee collection, payroll, and financial management</li>
                        <li>Communication tools — announcements, WhatsApp and SMS notifications</li>
                        <li>Document generation — ID cards, admit cards, report cards, TC &amp; certificates</li>
                        <li>Library, transportation, and inventory management</li>
                        <li>Mobile applications for iOS and Android</li>
                    </ul>
                    <p>We reserve the right to modify, suspend, or discontinue any part of the Service at any time, with
                        reasonable notice where possible.</p>
                </div>

                <!-- 3 -->
                <div class="policy-section" id="t3">
                    <h2><span class="sec-num">3</span> User Accounts &amp; Registration</h2>
                    <p>To use the Service, your institution must register an account. You agree to:</p>
                    <ul>
                        <li>Provide accurate, complete, and current registration information</li>
                        <li>Maintain the security and confidentiality of your login credentials</li>
                        <li>Notify us immediately of any unauthorized use of your account</li>
                        <li>Be responsible for all activity that occurs under your account</li>
                        <li>Not share account credentials with unauthorized persons</li>
                        <li>Ensure all users (staff, teachers, parents, students) comply with these Terms</li>
                    </ul>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">🔐</div>
                        <p>Your school administrator is fully responsible for managing user access within your
                            institution.
                            SUPERLMS is not liable for unauthorized access resulting from negligent credential
                            management.
                        </p>
                    </div>
                </div>

                <!-- 4 -->
                <div class="policy-section" id="t4">
                    <h2><span class="sec-num">4</span> Subscription Plans &amp; Payment</h2>
                    <p>SUPERLMS offers the following subscription plans:</p>
                    <ul>
                        <li><strong>School Plan:</strong> ₹250/year (₹20.83/month), billed annually, for up to 500
                            students</li>
                        <li><strong>Enterprise Plan:</strong> Custom pricing for larger institutions — contact us for a
                            quote</li>
                    </ul>
                    <p>Payment terms:</p>
                    <ul>
                        <li>All fees are billed annually in advance in Indian Rupees (INR)</li>
                        <li>Payments are processed via secure, PCI-DSS compliant payment gateways</li>
                        <li>Subscriptions auto-renew unless cancelled at least 15 days before the renewal date</li>
                        <li>No refunds are provided for partial periods after the 7-day free trial</li>
                        <li>Pricing is subject to change with 30 days prior written notice</li>
                        <li>GST and applicable taxes will be added as per Indian tax regulations</li>
                    </ul>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">💳</div>
                        <p>A 7-day free trial is available for all new school registrations with no credit card
                            required.
                            After the trial period, a valid subscription is required to continue accessing the platform.
                        </p>
                    </div>
                </div>

                <!-- 5 -->
                <div class="policy-section" id="t5">
                    <h2><span class="sec-num">5</span> Acceptable Use Policy</h2>
                    <p>You agree to use the SUPERLMS platform only for lawful educational and administrative purposes.
                        You
                        must NOT:</p>
                    <ul>
                        <li>Use the platform to transmit spam, malware, or any harmful content</li>
                        <li>Attempt to gain unauthorized access to any part of our systems</li>
                        <li>Reverse engineer, decompile, or attempt to extract source code</li>
                        <li>Resell, redistribute, or sublicense the Service without written consent</li>
                        <li>Use the platform to store or share illegal, defamatory, or offensive content</li>
                        <li>Upload content that violates third-party intellectual property rights</li>
                        <li>Conduct automated scraping or data harvesting of any kind</li>
                        <li>Impersonate any person or institution or misrepresent your affiliation</li>
                    </ul>
                    <p>Violation of this Acceptable Use Policy may result in immediate account suspension or termination
                        without refund.</p>
                </div>

                <!-- 6 -->
                <div class="policy-section" id="t6">
                    <h2><span class="sec-num">6</span> Intellectual Property Rights</h2>
                    <p>All intellectual property in the SUPERLMS platform — including software, code, design,
                        trademarks,
                        logos, and content — is exclusively owned by SUPERLMS and its licensors. You are granted a
                        limited, non-exclusive, non-transferable license to use the Service during your active
                        subscription
                        period.</p>
                    <p>You retain full ownership of all data and content you upload to the platform ("Your Content"). By
                        using the Service, you grant SUPERLMS a limited license to process, store, and display Your
                        Content solely to provide the Service.</p>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">©️</div>
                        <p>The SUPERLMS name, logo, and all associated marks are registered trademarks. Unauthorized
                            use
                            of our trademarks is strictly prohibited and may result in legal action.</p>
                    </div>
                </div>

                <!-- 7 -->
                <div class="policy-section" id="t7">
                    <h2><span class="sec-num">7</span> Data Ownership &amp; Privacy</h2>
                    <p>You (the school/institution) own all data you input into the platform, including student records,
                        academic data, and financial information. SUPERLMS acts as a Data Processor on your behalf and
                        processes data only as instructed.</p>
                    <p>Our full data handling practices are described in our <a href="{{ url('web/privacy-policy') }}"
                            style="color:var(--violet);text-decoration:none;font-weight:600;">Privacy Policy</a>, which
                        is
                        incorporated into these Terms by reference. Key commitments:</p>
                    <ul>
                        <li>We will never sell your data or student data to third parties</li>
                        <li>Data is encrypted in transit and at rest</li>
                        <li>You may export all your data at any time during your subscription</li>
                        <li>Upon termination, data is available for export for 30 days, then securely deleted</li>
                        <li>We comply with the Indian IT Act 2000 and the Digital Personal Data Protection Act 2023</li>
                    </ul>
                </div>

                <!-- 8 -->
                <div class="policy-section" id="t8">
                    <h2><span class="sec-num">8</span> Service Availability &amp; SLA</h2>
                    <p>SUPERLMS targets a 99.9% uptime Service Level Agreement (SLA) measured monthly, excluding
                        scheduled
                        maintenance windows.</p>
                    <ul>
                        <li>Scheduled maintenance will be communicated at least 24 hours in advance</li>
                        <li>Emergency maintenance may occur without prior notice to protect platform security</li>
                        <li>Uptime credits are available for downtime exceeding the SLA threshold — contact support</li>
                        <li>We do not guarantee uninterrupted access during force majeure events</li>
                    </ul>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">⚡</div>
                        <p>We target 99.9% uptime. In the event of extended downtime beyond our SLA, affected schools
                            may be
                            eligible for prorated service credits. Contact support@superlms.in to submit a claim.</p>
                    </div>
                </div>

                <!-- 9 -->
                <div class="policy-section" id="t9">
                    <h2><span class="sec-num">9</span> Limitation of Liability</h2>
                    <p>To the maximum extent permitted by applicable Indian law, SUPERLMS and its directors, officers,
                        employees, and agents shall not be liable for:</p>
                    <ul>
                        <li>Indirect, incidental, special, consequential, or punitive damages</li>
                        <li>Loss of data, revenue, profits, or business opportunities</li>
                        <li>Damages resulting from unauthorized access to or use of your account</li>
                        <li>Service interruptions caused by third-party infrastructure or force majeure events</li>
                    </ul>
                    <p>Our total cumulative liability to you for any claims arising from your use of the Service shall
                        not
                        exceed the total subscription fees you paid in the 12 months preceding the claim.</p>
                </div>

                <!-- 10 -->
                <div class="policy-section" id="t10">
                    <h2><span class="sec-num">10</span> Termination</h2>
                    <p>Either party may terminate the subscription agreement:</p>
                    <ul>
                        <li><strong>By the School:</strong> Written notice at least 15 days before the renewal date via
                            email to support@superlms.in</li>
                        <li><strong>By SUPERLMS:</strong> Immediately, with or without notice, in cases of Terms
                            violations, non-payment, or fraudulent activity</li>
                        <li><strong>Mutual Agreement:</strong> Both parties may agree to terminate at any time</li>
                    </ul>
                    <p>Upon termination, your access to the platform will be suspended at the end of the current billing
                        cycle. All data remains available for export for 30 days after termination. No refunds are
                        issued
                        for unused portions of the subscription period unless termination is due to our material breach.
                    </p>
                </div>

                <!-- 11 -->
                <div class="policy-section" id="t11">
                    <h2><span class="sec-num">11</span> Governing Law &amp; Disputes</h2>
                    <p>These Terms shall be governed by and construed in accordance with the laws of India. Any disputes
                        arising out of or in connection with these Terms shall be subject to the exclusive jurisdiction
                        of
                        the courts in Aligarh, Uttar Pradesh, India.</p>
                    <p>Before initiating legal proceedings, both parties agree to attempt to resolve disputes through
                        good-faith negotiation for a period of 30 days. If unresolved, disputes may be referred to
                        binding
                        arbitration under the Arbitration and Conciliation Act, 1996 of India.</p>
                </div>

                <!-- 12 -->
                <div class="policy-section" id="t12">
                    <h2><span class="sec-num">12</span> Modifications to Terms</h2>
                    <p>We reserve the right to modify these Terms at any time. Material changes will be communicated
                        via:
                    </p>
                    <ul>
                        <li>Email notification to the registered school administrator at least 30 days in advance</li>
                        <li>A prominent banner notice within the SUPERLMS platform</li>
                        <li>Updated "Last Modified" date on this page</li>
                    </ul>
                    <p>Your continued use of the Service after the effective date of any changes constitutes your
                        acceptance
                        of the revised Terms. If you do not agree with the revised Terms, you must terminate your
                        subscription before the changes take effect.</p>
                    <div class="highlight-box">
                        <div class="highlight-box-icon">📢</div>
                        <p>We encourage you to review these Terms periodically. Minor clarifications and non-material
                            changes may be made without advance notice.</p>
                    </div>
                </div>

                <!-- 13 -->
                <div class="policy-section" id="t13">
                    <h2><span class="sec-num">13</span> Contact Us</h2>
                    <p>For any questions about these Terms &amp; Conditions, billing disputes, or legal enquiries,
                        please
                        contact us at:</p>
                    <div class="contact-info-card">
                        <div class="contact-info-row"><span>📧</span><span>support@superlms.in</span></div>
                        <div class="contact-info-row"><span>📱</span><span>+91 9084748563</span></div>
                        <div class="contact-info-row"><span>📍</span><span>Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137</span></div>
                    </div>
                </div>

            </div><!-- /tcFallback -->
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    <script>
        (async () => {
            try {
                const res = await fetch('/api/website/terms-conditions');
                const json = await res.json();
                const data = json.data;

                if (data && data.sections && data.sections.length) {
                    if (data.last_updated) {
                        document.getElementById('tcLastUpdated').textContent = 'Last updated: ' + data.last_updated;
                    }
                    if (data.platform_name) {
                        document.getElementById('tcPlatformName').textContent = data.platform_name;
                    }

                    const container = document.getElementById('tcSections');
                    data.sections.forEach((sec, i) => {
                        const div = document.createElement('div');
                        div.className = 'policy-section';
                        div.id = `tcs${i+1}`;
                        div.innerHTML =
                            `<h2><span class="sec-num">${i+1}</span> ${escHtml(sec.head)}</h2><p>${escHtml(sec.desc)}</p>`;
                        container.appendChild(div);
                    });

                    // Hide static fallback
                    document.getElementById('tcFallback').style.display = 'none';
                } else {
                    document.getElementById('tcLastUpdated').textContent = 'Last updated: January 1, 2025';
                }
            } catch (e) {
                document.getElementById('tcLastUpdated').textContent = 'Last updated: January 1, 2025';
            }
        })();

        function escHtml(str) {
            const d = document.createElement('div');
            d.appendChild(document.createTextNode(str || ''));
            return d.innerHTML;
        }
    </script>
@endpush
