<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the official SUPERLMS Terms of Use (23 separate sections, one per
 * numbered section of the legal document) into the term_of_uses table.
 * Replaces whatever content is currently there — the super-admin panel keeps
 * full edit control afterwards, this only sets the starting content.
 *
 * Section headings intentionally carry no numbers: both the super-admin view
 * and the public /web/terms-of-use page number sections automatically by
 * position.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sections = $this->sections();

        $data = [
            'metadata'     => json_encode(['sections' => $sections], JSON_UNESCAPED_UNICODE),
            'last_updated' => '2026-07-07',
            'updated_at'   => now(),
        ];

        $row = DB::table('term_of_uses')->orderBy('id')->first();

        if ($row) {
            DB::table('term_of_uses')->where('id', $row->id)->update($data);
        } else {
            DB::table('term_of_uses')->insert($data + ['created_at' => now()]);
        }
    }

    public function down(): void
    {
        // Content seed — nothing sensible to roll back to.
    }

    private function sections(): array
    {
        return [
            ['head' => 'Introduction and Acceptance', 'desc' => <<<'TXT'
SUPERLMS is a product of Super Learnings Private Limited.
Website: www.superlms.in
Registered Office: Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137
Email: support@superlms.in | Phone: +91 9084748563
Effective Date: 07 July 2026

These Terms of Use ("Terms of Use") govern Your access to and use of the SuperLMS website (superlms.in), mobile applications, Admin Panel, dashboards, and all associated pages, features, and content (collectively, the "Site"), as made available by Super Learnings Private Limited ("Company", "SuperLMS", "We", "Us", "Our").

These Terms of Use are distinct from, and to be read together with, Our Privacy Policy and Terms and Conditions (which govern Subscriptions, Service Agreements, and the substantive School LMS Services). Where these Terms of Use are silent, the Terms and Conditions shall apply.

By browsing, accessing, registering on, or otherwise using the Site — whether as a visitor, School representative, Teacher, Student, Parent/Guardian, or any other User — You confirm that You have read, understood, and agree to be bound by these Terms of Use. If You do not agree, please discontinue use of the Site immediately.

These Terms of Use are governed in accordance with applicable Indian law, including:
• The Indian Contract Act, 1872;
• The Information Technology Act, 2000, and rules framed thereunder;
• The Information Technology (Intermediary Guidelines and Digital Media Ethics Code) Rules, 2021;
• The Digital Personal Data Protection Act, 2023;
• The Copyright Act, 1957, and the Trade Marks Act, 1999;
• The Consumer Protection Act, 2019, and applicable e-commerce rules, to the extent relevant; and
• Any other applicable central or state legislation.
TXT],

            ['head' => 'Who May Use the Site', 'desc' => <<<'TXT'
1. The Site is intended for use by Schools, educational institutions, and their Authorised Users (Teachers, Staff, Students, Parents/Guardians, and Administrators).
2. Visitors browsing the public-facing pages of the Site (e.g., product information, pricing, blog) need not register an account, but remain subject to these Terms of Use.
3. Individuals under the age of 18 ("minors") may use the Site only as Students, under the supervision of their School and with parental/guardian consent, and may not independently create an account or accept these Terms of Use on their own behalf.
4. By using the Site, You represent that You have the legal capacity to enter into a binding agreement under the Indian Contract Act, 1872, or, if acting on behalf of a School, that You are duly authorised to bind that institution to these Terms of Use.
TXT],

            ['head' => 'Account Registration and Security', 'desc' => <<<'TXT'
1. Certain areas of the Site (Admin Panel, Teacher/Student/Parent dashboards) require registration and login credentials, which are issued through the onboarding process described in Our Privacy Policy.
2. You are responsible for maintaining the confidentiality of Your username, password, and any OTP/verification codes, and for all activities conducted under Your account.
3. You agree to immediately notify Us at support@superlms.in of any unauthorised access to or use of Your account, or any other breach of security.
4. SuperLMS shall not be liable for any loss or damage arising from Your failure to safeguard Your account credentials.
TXT],

            ['head' => 'Use of the Site — Permitted Use', 'desc' => <<<'TXT'
Subject to compliance with these Terms of Use, SuperLMS grants You a limited, non-exclusive, non-transferable, revocable right to access and use the Site solely for:
1. Legitimate educational, administrative, and academic purposes connected with Your role (School, Teacher, Student, Parent, or Administrator);
2. Viewing, downloading, or printing content (such as report cards, notices, or study material) made available to You through Your account, strictly for personal or institutional non-commercial use; and
3. Communicating with other Authorised Users through the Site's built-in communication tools, for purposes connected with Your educational role.
TXT],

            ['head' => 'Prohibited Uses', 'desc' => <<<'TXT'
You agree that You shall NOT:
1. Use the Site for any unlawful purpose, or in a manner that violates any applicable law, including the IT Act, 2000, or infringes the rights of any third party;
2. Upload, post, transmit, or share any content that is obscene, defamatory, discriminatory, hateful, threatening, or harmful to minors;
3. Impersonate any person or entity, or misrepresent Your affiliation with any School, Teacher, Student, or Parent account;
4. Attempt to gain unauthorised access to any account, data, server, or network connected to the Site, including through hacking, password mining, or credential stuffing;
5. Introduce any virus, malware, worm, trojan, or other harmful code to the Site;
6. Use bots, scrapers, spiders, or other automated means to access, extract, or index content from the Site without Our prior written consent;
7. Interfere with or disrupt the integrity or performance of the Site, including through denial-of-service attacks or excessive automated requests;
8. Copy, reproduce, republish, frame, or create derivative works from any part of the Site without Our prior written permission;
9. Use the Site to send unsolicited bulk communications ("spam") to other Users; or
10. Use the Site to bully, harass, stalk, or threaten any other User, including fellow students, teachers, or staff.

Violation of this Section may result in immediate suspension or termination of Your access, and SuperLMS reserves the right to pursue appropriate legal remedies, including reporting unlawful conduct (particularly conduct affecting the safety of minors) to appropriate law enforcement or regulatory authorities.
TXT],

            ['head' => 'User-Generated Content', 'desc' => <<<'TXT'
1. "User Content" means any content — including assignments, question banks, comments, messages, profile photographs, or documents — uploaded, posted, or transmitted by Users through the Site.
2. You retain ownership of Your User Content. By uploading User Content, You grant SuperLMS a limited, worldwide, royalty-free licence to host, store, reproduce, and display such content solely for the purpose of operating and providing the Services to You and Your School.
3. You represent and warrant that You own, or have the necessary rights and permissions to upload, any User Content, and that such content does not infringe the intellectual property, privacy, or other rights of any third party.
4. SuperLMS reserves the right (but is not obligated) to review, moderate, or remove any User Content that violates these Terms of Use, applicable law, or that We reasonably believe may harm minors or other Users, particularly under Our obligations relating to child safety.
TXT],

            ['head' => 'Intellectual Property Rights', 'desc' => <<<'TXT'
1. The Site, including its design, layout, source code, graphics, logos, "SuperLMS" trademark, and all underlying software and technology, is the exclusive property of Super Learnings Private Limited, protected under the Copyright Act, 1957, the Trade Marks Act, 1999, and other applicable intellectual property laws.
2. Nothing in these Terms of Use transfers any ownership rights in the Site or its content to You. Except as expressly permitted herein, You may not copy, modify, distribute, sell, lease, or create derivative works based on the Site or any part thereof.
3. Any feedback, suggestions, or ideas You provide to SuperLMS regarding the Site may be used by Us without any obligation to compensate You, and without any confidentiality obligation, unless otherwise agreed in writing.
TXT],

            ['head' => 'Third-Party Links and Content', 'desc' => <<<'TXT'
The Site may contain links to third-party websites, applications, or services (e.g., payment gateways, video-conferencing tools, educational resources) that are not owned or controlled by SuperLMS. We do not endorse and are not responsible for the content, accuracy, privacy practices, or availability of any third-party site. Your use of any third-party site linked from the Site is at Your own risk and subject to that third party's own terms.
TXT],

            ['head' => 'Communication Tools and Notifications', 'desc' => <<<'TXT'
1. The Site may enable communication between Schools, Teachers, Students, and Parents via SMS, email, WhatsApp, and in-app notifications.
2. Users agree to use such communication tools responsibly, respectfully, and solely for purposes connected with their educational role.
3. SuperLMS reserves the right to monitor communications on the Platform where reasonably necessary to ensure compliance with these Terms of Use, prevent misuse, or protect the safety of minors, subject to applicable law and Our Privacy Policy.
TXT],

            ['head' => 'Disclaimer of Warranties', 'desc' => <<<'TXT'
1. The Site is provided on an "as is" and "as available" basis, without warranties of any kind, whether express or implied, including implied warranties of merchantability, fitness for a particular purpose, or non-infringement, to the maximum extent permitted under applicable law.
2. SuperLMS does not warrant that the Site will be uninterrupted, timely, secure, or error-free, or that any defects will be corrected.
3. Any content, including AI-generated recommendations, question banks, or analytics available on the Site, is provided for informational and educational assistance purposes only, and must be reviewed by a qualified teacher or School official before being relied upon for academic or disciplinary decisions.
TXT],

            ['head' => 'Limitation of Liability', 'desc' => <<<'TXT'
To the maximum extent permitted under applicable Indian law:
1. SuperLMS shall not be liable for any indirect, incidental, consequential, special, exemplary, or punitive damages arising out of or in connection with Your use of, or inability to use, the Site;
2. SuperLMS's aggregate liability arising out of or in connection with these Terms of Use shall not exceed the amount (if any) paid by the concerned Institutional Client for access to the Site in the twelve (12) months preceding the event giving rise to the claim;
3. SuperLMS shall not be liable for any content posted by Users, or for any loss or damage arising from reliance on such content; and
4. Nothing in these Terms of Use excludes or limits SuperLMS's liability for fraud, wilful misconduct, gross negligence, or any liability that cannot lawfully be excluded.
TXT],

            ['head' => 'Indemnification', 'desc' => <<<'TXT'
You agree to indemnify, defend, and hold harmless Super Learnings Private Limited, its directors, officers, and employees, from any claims, liabilities, damages, and expenses (including reasonable legal fees) arising out of:
1. Your breach of these Terms of Use;
2. Your User Content, or Your use of the Site in violation of applicable law or third-party rights; or
3. Any unauthorised or unlawful use of Your account.
TXT],

            ['head' => 'Suspension and Termination of Access', 'desc' => <<<'TXT'
1. SuperLMS may suspend or terminate Your access to the Site, in whole or in part, immediately and without prior notice, in the event of a breach of these Terms of Use, suspected fraudulent or unlawful activity, or where required to comply with applicable law.
2. You may stop using the Site at any time. Where You hold an account through Your School, deactivation shall generally be managed by Your School's Administrator, in accordance with Our Privacy Policy.
3. Provisions relating to intellectual property, indemnification, limitation of liability, and dispute resolution shall survive termination of Your access to the Site.
TXT],

            ['head' => 'Grievance Redressal', 'desc' => <<<'TXT'
In accordance with the Information Technology (Intermediary Guidelines and Digital Media Ethics Code) Rules, 2021, SuperLMS has appointed a Grievance Officer to address complaints relating to the Site, including complaints regarding objectionable content or violation of these Terms of Use:

Grievance Officer
Super Learnings Private Limited
Email: support@superlms.in
Phone: +91 9084748563
Address: Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137
Business Hours: Monday–Saturday, 10:00 AM – 6:00 PM (IST)

Complaints shall be acknowledged within 48 hours and, where feasible, resolved within 30 days of receipt, consistent with applicable timelines under the IT Rules, 2021.
TXT],

            ['head' => 'Reporting Objectionable Content or Child Safety Concerns', 'desc' => <<<'TXT'
Given that the Site is used extensively by minors, SuperLMS takes reports of objectionable content, cyberbullying, or any conduct endangering child safety with the utmost seriousness. Users, Schools, or Parents may report such concerns directly to Our Grievance Officer at support@superlms.in, and SuperLMS shall act promptly to review, remove, or restrict access to the reported content or account, and, where appropriate, escalate the matter to the concerned School and/or law enforcement authorities.
TXT],

            ['head' => 'Changes to the Site and These Terms of Use', 'desc' => <<<'TXT'
1. SuperLMS reserves the right to modify, suspend, or discontinue any part of the Site, temporarily or permanently, at any time, with or without notice.
2. We may revise these Terms of Use periodically to reflect changes in law, technology, or Our business practices. Material changes will be notified via the Site, email, or in-app notification. Continued use of the Site after such changes constitutes Your acceptance of the revised Terms of Use.
TXT],

            ['head' => 'Force Majeure', 'desc' => <<<'TXT'
SuperLMS shall not be liable for any delay or failure in providing access to the Site arising from circumstances beyond its reasonable control, including natural disasters, war, civil unrest, epidemic/pandemic, governmental action, internet or telecommunications failure, power outage, or failure of third-party service/hosting providers.
TXT],

            ['head' => 'Severability and Waiver', 'desc' => <<<'TXT'
If any provision of these Terms of Use is found to be invalid or unenforceable by a court of competent jurisdiction, the remaining provisions shall continue in full force and effect. No failure or delay by SuperLMS in exercising any right under these Terms of Use shall operate as a waiver of that right.
TXT],

            ['head' => 'Governing Law and Jurisdiction', 'desc' => <<<'TXT'
These Terms of Use shall be governed by and construed in accordance with the laws of India. Subject to the "Dispute Resolution" section below, the courts at Aligarh, Uttar Pradesh shall have exclusive jurisdiction over any disputes arising out of or in connection with these Terms of Use.
TXT],

            ['head' => 'Dispute Resolution', 'desc' => <<<'TXT'
Any dispute arising out of or in connection with these Terms of Use shall first be addressed through good-faith negotiation between the parties. If unresolved within thirty (30) days, the dispute shall be referred to and finally resolved by arbitration under the Arbitration and Conciliation Act, 1996, before a sole arbitrator mutually appointed by the parties, with the seat and venue of arbitration at Aligarh, Uttar Pradesh, conducted in the English language.
TXT],

            ['head' => 'Who These Terms of Use Apply To — Schools, Teachers, Students, and Parents', 'desc' => <<<'TXT'
1. Schools, upon creating an Admin account on the Site, accept these Terms of Use on behalf of the institution and are responsible for ensuring their Teachers, Students, and Parents are made aware of these Terms.
2. Teachers and Staff, upon first login using credentials issued by the School Admin, are required to affirmatively accept these Terms of Use before accessing the Site.
3. Parents/Guardians, upon activating their Parent Portal login, accept these Terms of Use on their own behalf and, where applicable, on behalf of their child.
4. Students, where granted supervised access to the Student Dashboard, use the Site subject to acceptance of these Terms of Use by their School and parent/guardian.
5. Visitors browsing the public pages of the Site without logging in are bound by these Terms of Use to the extent applicable to their browsing activity (e.g., prohibited use, intellectual property, third-party links).
TXT],

            ['head' => 'Conclusion', 'desc' => <<<'TXT'
These Terms of Use are designed to ensure that the SuperLMS Site remains a safe, respectful, and lawfully-operated digital space for every School, Teacher, Student, and Parent who visits or uses it. In summary:
• The Site may be used only for legitimate educational and administrative purposes connected with Your role;
• Prohibited conduct — including harassment, unauthorised access, malicious code, and misuse of User Content — is strictly disallowed and may result in suspension or legal action;
• Intellectual property in the Site remains with SuperLMS, while Users retain ownership of their own uploaded content, subject to a limited licence for Us to operate the Services;
• Concerns regarding objectionable content, misuse, or child-safety issues can be raised directly with Our Grievance Officer, who is committed to prompt acknowledgment and resolution; and
• These Terms of Use work together with Our Privacy Policy and Terms and Conditions to provide a complete, transparent framework governing every School's, Teacher's, Student's, and Parent's relationship with SuperLMS.

We encourage all Users to read these Terms of Use carefully, alongside Our Privacy Policy and Terms and Conditions, and to reach out to Us at support@superlms.in with any questions or concerns. SuperLMS remains committed to keeping these Terms of Use aligned with evolving Indian law and best practices in the EdTech sector.
TXT],

            ['head' => 'Contact Us', 'desc' => <<<'TXT'
For any questions, concerns, or requests regarding these Terms of Use, please contact:

Super Learnings Private Limited
Email: support@superlms.in
Phone: +91 9084748563
Address: Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137
Business Hours: Monday–Saturday, 10:00 AM – 6:00 PM (IST)
TXT],
        ];
    }
};
