<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the official SUPERLMS Privacy Policy (24 separate sections, one per
 * numbered section of the legal document) into the privacy_policies table.
 * Replaces whatever content is currently there — the super-admin panel keeps
 * full edit control afterwards, this only sets the starting content.
 *
 * Section headings intentionally carry no numbers: both the super-admin view
 * and the public /web/privacy page number sections automatically by position.
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

        $row = DB::table('privacy_policies')->orderBy('id')->first();

        if ($row) {
            DB::table('privacy_policies')->where('id', $row->id)->update($data);
        } else {
            DB::table('privacy_policies')->insert($data + ['created_at' => now()]);
        }
    }

    public function down(): void
    {
        // Content seed — nothing sensible to roll back to.
    }

    private function sections(): array
    {
        return [
            ['head' => 'Introduction', 'desc' => <<<'TXT'
SUPERLMS is a product of Super Learnings Private Limited.
Website: www.superlms.in
Registered Office: Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137
Email: support@superlms.in | Phone: +91 9084748563
Effective Date: 07 July 2026

Super Learnings Private Limited ("Company", "SuperLMS", "We", "Us", or "Our") operates SuperLMS, a cloud-based Learning Management System ("Platform" or "Services") that provides schools and educational institutions with tools for attendance management, timetable scheduling, fee management, assessments, online examinations, digital content delivery, AI-enabled learning features, communication, and related administrative and academic functions.

This Privacy Policy explains how We collect, use, disclose, store, and protect Personal Data belonging to School administrators, teachers, staff, students, and parents/guardians ("You", "User", "Data Principal") who access or use the Platform, whether through our website, mobile applications, APIs, or any associated services.

This Policy is framed in accordance with, and is intended to comply with, the following Indian laws, rules, guidelines, and regulations, as applicable and as may be amended from time to time:
• The Digital Personal Data Protection Act, 2023 ("DPDP Act") and the rules framed thereunder;
• The Information Technology Act, 2000 ("IT Act"), including Sections 43A, 72, and 72A;
• The Information Technology (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules, 2011;
• The Information Technology (Intermediary Guidelines and Digital Media Ethics Code) Rules, 2021;
• CERT-In Directions, 2022, relating to information security practices, cyber incident reporting, and log retention;
• The National Education Policy (NEP), 2020, insofar as it relates to digital learning and data-driven education practices;
• The Right of Children to Free and Compulsory Education Act, 2009, to the extent applicable to student data handled on behalf of Schools;
• The Protection of Children from Sexual Offences Act, 2012 ("POCSO"), in respect of Our commitment to child safety and appropriate handling of minors' data;
• Guidelines issued by the Ministry of Electronics and Information Technology (MeitY), CBSE, State Education Boards/Departments, and other applicable regulatory bodies governing data handling by EdTech platforms; and
• Any other applicable central or state legislation governing data privacy and protection in India.

Where SuperLMS is accessed by Users located outside India, We also endeavour to align Our practices with internationally recognised privacy principles, including those under the General Data Protection Regulation (GDPR), wherever applicable.

By accessing or using the Platform, You consent to the collection, use, and disclosure of Your Personal Data as described in this Policy. If You do not agree with this Policy, please do not use the Platform.
TXT],

            ['head' => 'Applicability', 'desc' => <<<'TXT'
This Privacy Policy applies to all touchpoints of the SuperLMS ecosystem, including:
• www.superlms.in and associated sub-domains
• Mobile applications (Android/iOS)
• School LMS Portal
• Teacher Dashboard
• Student Dashboard
• Parent Portal
• School Administration Portal
• APIs and developer integrations
• Third-party integrations (payment, communication, video-conferencing)
• AI-enabled learning tools
• Online Examination System
• Communication modules (SMS/Email/WhatsApp/In-app)
TXT],

            ['head' => 'Definitions', 'desc' => <<<'TXT'
• "Personal Data" means any data about an individual who is identifiable by or in relation to such data, as defined under the DPDP Act, 2023.
• "Sensitive Personal Data or Information (SPDI)" means data such as biometric data, health records, financial information, and other categories identified under the IT Rules, 2011.
• "Data Principal" means the individual to whom the Personal Data relates (student, parent/guardian, teacher, or administrator).
• "Data Fiduciary" means the School/Institution which, along with SuperLMS in respect of processing carried out on its behalf, determines the purpose and means of processing Personal Data.
• "Data Processor" means SuperLMS, insofar as it processes Personal Data on behalf of a School pursuant to a service agreement.
• "Child" means an individual who has not completed the age of 18 years, as defined under the DPDP Act, 2023.
• "Institutional Client" means a School or educational institution that has subscribed to the Platform.
TXT],

            ['head' => 'Information We Collect', 'desc' => <<<'TXT'
4.1 Information provided by Schools/Institutions
• School name, address, registration/affiliation details, board affiliation (CBSE/ICSE/State Board), and administrative contact details.
• Class, section, and department structures.

4.2 Information relating to Students
• Full name, date of birth, gender (optional), photograph, admission/roll number, class and section, language preference.
• Parent/guardian name(s), contact number, email address, and residential address.
• Academic records including attendance, assignments, homework, quiz attempts, test/exam results, examination scores, certificates, course completion, report cards, and learning progress.
• Fee payment records, dues, and transaction history (processed through secure third-party payment gateways).
• Transport route details, if applicable.
• Health-related information voluntarily provided by parents/schools for emergency purposes (treated as Sensitive Personal Data).

4.3 Information relating to Teachers and Staff
• Name, contact details, employee ID, designation, qualifications, profile photograph, and employment-related records necessary for payroll, attendance, and administrative modules.

4.4 Information relating to Parents/Guardians
• Name, contact number, email address, and relationship to student, for the purpose of communication, fee payment, and progress tracking.

4.5 Device and Technical Information
• IP address, device type, operating system, browser type, screen resolution, device identifier, time zone, login history, and crash reports, collected automatically when the Platform is accessed via web or mobile applications.

4.6 Usage Information
• Login/logout time, pages visited, time spent, click activity, feature usage, search history, and learning analytics/performance metrics.

4.7 Cookies
We use cookies for login sessions, authentication, user preferences, analytics, performance improvement, security, and "remember me" functionality. Users may disable cookies through browser settings; however, some features may not function properly without them.

4.8 Biometric/Attendance Data
Where a School opts for biometric or QR-code based attendance, limited biometric identifiers may be processed strictly for attendance verification, subject to explicit consent obtained by the School.
TXT],

            ['head' => 'Information Collected From Children', 'desc' => <<<'TXT'
SuperLMS is primarily used by schools and educational institutions on behalf of students who may be minors. Accordingly:
• Schools act as the authorised educational institution responsible for onboarding students and obtaining parental/guardian consent wherever required under applicable law.
• We collect only information that is necessary and proportionate for educational purposes.
• We do not knowingly use children's data for behavioural advertising, profiling, or tracking.
• We implement additional technical and organisational safeguards for the protection of child data, in line with Section 9 of the DPDP Act, 2023.
TXT],

            ['head' => 'How We Use Information', 'desc' => <<<'TXT'
Your information may be used to:
• Create and manage user accounts (School, Teacher, Student, Parent, Admin);
• Provide core LMS services — attendance, timetable, fee management, assessments, and report generation;
• Deliver online classes and conduct assessments/examinations;
• Generate report cards, certificates, and academic records;
• Enable communication between administrators, teachers, students, and parents (SMS, WhatsApp, email, in-app notifications);
• Generate analytics, learning insights, and performance dashboards for authorised School personnel;
• Provide AI-assisted learning recommendations (see the "AI-Enabled Features" section);
• Facilitate customer support and technical maintenance;
• Prevent fraud and improve platform security;
• Comply with applicable legal, regulatory, and educational Board requirements; and
• Communicate important notices, product updates, and service-related information.

We do NOT use student or parent Personal Data for behavioural advertising, nor do We sell Personal Data to third parties for marketing purposes.
TXT],

            ['head' => 'AI-Enabled Features', 'desc' => <<<'TXT'
Certain features of the Platform may use Artificial Intelligence for purposes such as:
• Question and worksheet generation;
• Assignment evaluation and automated feedback;
• Personalised learning recommendations;
• Content suggestions based on learning progress; and
• Student performance insights for teachers.

AI-generated outputs are intended solely to assist educators and administrators, and must be reviewed by a qualified teacher or School official before being relied upon for any final academic, disciplinary, or evaluative decision. AI features do not make autonomous decisions that affect a student's academic standing without human review.
TXT],

            ['head' => 'Legal Basis for Processing', 'desc' => <<<'TXT'
In accordance with Section 4 of the DPDP Act, 2023, Personal Data is processed on the basis of:
• Consent, obtained by the Institutional Client from parents/guardians (on behalf of student Data Principals) and from staff, prior to onboarding onto the Platform;
• Performance of Contract, between SuperLMS and the Institutional Client;
• Compliance with Legal Obligations under applicable education and data protection law;
• Legitimate educational interests, including protection of students and School administration requirements.

Where the Data Principal is a Child, processing is undertaken only upon verifiable consent of the parent or lawful guardian, obtained by the Institutional Client, as mandated under Section 9 of the DPDP Act, 2023. SuperLMS does NOT undertake tracking, behavioural monitoring, or targeted advertising directed at children, in compliance with Section 9(3) of the DPDP Act.
TXT],

            ['head' => 'Data Sharing and Disclosure', 'desc' => <<<'TXT'
We do not sell Personal Data. Information may be shared only with:
1. Registered Schools, Teachers, and Parents/Guardians, on a role-based, need-to-know basis;
2. Cloud Infrastructure Providers, for secure hosting and backup;
3. Payment Partners (e.g., Razorpay and similar gateways), for fee collection;
4. SMS/Email/WhatsApp Business API Providers, for communication;
5. Video-Conferencing Providers (e.g., Zoom, Google Meet), where integrated for online classes;
6. Government Authorities/Law Enforcement Agencies, where legally required, including under the IT Act, 2000;
7. Auditors and Technology Partners, under strict confidentiality obligations; and
8. Successors in a Business Transfer (merger, acquisition, or sale of assets), subject to equivalent privacy protections.

We do not knowingly disclose student or parent data to advertisers or data brokers.
TXT],

            ['head' => 'Third-Party Integrations', 'desc' => <<<'TXT'
SuperLMS may integrate with trusted third-party platforms to deliver its Services, including but not limited to:
• Google Workspace / Microsoft 365
• Zoom / Google Meet
• Razorpay and other payment gateways
• SMS providers and WhatsApp Business APIs
• Email service providers
• Analytics services
• Cloud storage providers

Each of these third parties maintains its own privacy policy, and Users are encouraged to review the same. This Policy does not extend to the privacy practices of such independent third parties.
TXT],

            ['head' => 'Data Security', 'desc' => <<<'TXT'
We implement industry-standard, reasonable security practices in line with Rule 8 of the IT Rules, 2011, including:
• SSL/TLS encryption (256-bit) for data in transit and HTTPS across the Platform;
• Password hashing and encrypted database storage;
• Role-based access control and permission management;
• Two-factor authentication (where available);
• Firewalls, malware protection, and intrusion monitoring;
• Audit logs and regular security updates;
• Secure, geo-redundant, encrypted backups; and
• Periodic security audits and incident monitoring.

Despite these measures, no online system can be guaranteed 100% secure, and Users are encouraged to safeguard their own login credentials.
TXT],

            ['head' => 'Data Storage, Localisation, and Retention', 'desc' => <<<'TXT'
1. Data Localisation: Personal Data collected from Users in India is primarily stored on servers located within India.

2. Cross-Border Transfer: Where data is processed outside India through trusted infrastructure providers (e.g., for backup or support), We implement appropriate contractual and technical safeguards to maintain an equivalent level of protection, in compliance with Section 16 of the DPDP Act, 2023, and only to jurisdictions not restricted by the Central Government.

3. Retention: Personal Data is retained only as long as necessary for educational records, legal compliance, audit requirements, School administration, dispute resolution, and backup recovery, or as otherwise required under applicable Board/regulatory rules. Schools may request deletion of institutional data upon termination of their subscription, subject to contractual and statutory retention obligations.

4. Breach Notification: In the event of a Personal Data breach, We shall promptly investigate, contain, and mitigate the incident; notify the affected Institutional Client; and comply with applicable legal reporting obligations, including notification to the Data Protection Board of India under Section 8(6) of the DPDP Act, 2023, and to CERT-In where required under the CERT-In Directions, 2022.
TXT],

            ['head' => 'Educational Institutions as Data Fiduciaries', 'desc' => <<<'TXT'
Where SuperLMS is deployed for a School or institution:
• The School generally determines the purpose and means of processing student, parent, and staff data, and is responsible for obtaining necessary consents prior to onboarding onto the Platform;
• SuperLMS acts as a Data Processor/technology service provider, processing data under the School's instructions, except where SuperLMS independently determines processing purposes for its own operational obligations (e.g., platform security, billing, and legal compliance).
TXT],

            ['head' => 'Marketing and Service Communications', 'desc' => <<<'TXT'
We may send:
• Product updates and new feature announcements;
• Security alerts and maintenance notices; and
• Essential service-related notifications.

Users may opt out of promotional/marketing communications at any time, while continuing to receive essential service-related notices necessary for the functioning of the Platform.
TXT],

            ['head' => 'Rights of Data Principals', 'desc' => <<<'TXT'
Subject to verification and in coordination with the Institutional Client (as primary Data Fiduciary), Data Principals — or a parent/guardian acting on behalf of a Child — have the right to:
1. Access a summary of Personal Data being processed about them;
2. Correction and Updation of inaccurate, incomplete, or outdated Personal Data;
3. Erasure of Personal Data that is no longer necessary for the purpose it was collected, subject to statutory retention requirements;
4. Withdraw Consent at any time, without affecting the lawfulness of processing carried out prior to withdrawal;
5. Data Portability, where technically feasible;
6. Restrict Processing in certain circumstances;
7. Grievance Redressal, through the mechanism described in the "Grievance Redressal / Grievance Officer" section; and
8. Nominate another individual to exercise these rights in the event of death or incapacity, as provided under Section 14 of the DPDP Act, 2023.

Requests may be routed through the respective School's administration or directly to Our Grievance Officer at support@superlms.in.
TXT],

            ['head' => 'Limitation of Liability', 'desc' => <<<'TXT'
While We implement reasonable and industry-standard security practices, Users acknowledge that internet-based services carry inherent risks. SuperLMS shall not be liable for unauthorised access, loss, or misuse of data resulting from circumstances beyond its reasonable control, including but not limited to force majeure events, third-party breaches, or User negligence in safeguarding credentials.
TXT],

            ['head' => "Children's Privacy", 'desc' => <<<'TXT'
SuperLMS recognises the heightened responsibility involved in processing data relating to Children. Accordingly:
• Student accounts are created and managed by the Institutional Client, with parental/guardian consent obtained by the School prior to onboarding.
• We do not knowingly permit direct marketing communication to children.
• We do not process Child data for behavioural monitoring, profiling, or targeted advertising, in compliance with Section 9 of the DPDP Act, 2023.
• Parents/guardians may contact the School or Our Grievance Officer to review, correct, or request deletion of their child's data.
TXT],

            ['head' => 'Grievance Redressal / Grievance Officer', 'desc' => <<<'TXT'
In accordance with Section 13 of the DPDP Act, 2023, Rule 3(2) of the IT Intermediary Guidelines, 2021, and the IT Rules, 2011, We have designated a Grievance Officer to address concerns relating to this Policy:

Grievance Officer
Super Learnings Private Limited
Email: support@superlms.in
Phone: +91 9084748563
Address: Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137
Business Hours: Monday–Saturday, 10:00 AM – 6:00 PM (IST)

We shall acknowledge grievances within 48 hours and endeavour to resolve them within 30 days, as prescribed under applicable IT Rules. Data Principals who remain unsatisfied may escalate concerns to the Data Protection Board of India, once constituted and operational under the DPDP Act, 2023.
TXT],

            ['head' => 'Changes to this Privacy Policy', 'desc' => <<<'TXT'
We may revise this Privacy Policy periodically to reflect changes in law, regulatory guidance, or Our data processing practices. Material changes will be communicated to Institutional Clients and, where feasible, to Users via website notices, email, dashboard notifications, or mobile application notifications. Continued use of the Platform after such updates constitutes acceptance of the revised Policy.
TXT],

            ['head' => 'Governing Law and Jurisdiction', 'desc' => <<<'TXT'
This Privacy Policy shall be governed by and construed in accordance with the laws of India. Any disputes arising out of or in connection with this Policy shall be subject to the exclusive jurisdiction of the courts at Aligarh, Uttar Pradesh.
TXT],

            ['head' => 'How Schools and Users Are Onboarded onto SuperLMS', 'desc' => <<<'TXT'
SuperLMS follows a structured, consent-driven onboarding process to ensure that data collection begins only after appropriate authorisation is in place:

21.1 Onboarding a School (Institutional Client)
1. The School's authorised representative (Principal/Director/Administrator) submits a request via superlms.in, by contacting the SuperLMS sales/support team, or through a designated reseller/partner.
2. SuperLMS enters into a Service Agreement / Data Processing Agreement with the School, defining the scope of data processing, security obligations, and responsibilities of each party.
3. The School is provided with a dedicated Admin Panel, through which it uploads or enters institutional data (school structure, classes, sections, subjects, and fee heads).
4. The School designates one or more Super Admin(s) who are responsible for managing user creation, roles, and permissions within the Platform.

21.2 Adding Teachers and Staff
1. The School Admin creates teacher/staff profiles within the Admin Panel using official employment records (name, designation, contact details, subjects assigned).
2. Each teacher/staff member receives login credentials via email/SMS and is required to verify their account and accept the Platform's Terms of Use and this Privacy Policy upon first login.

21.3 Adding Students and Parents
1. The School Admin (or class teacher, where permitted) uploads student admission data, either manually or via bulk import (Excel/CSV), based on the School's own admission records.
2. Parental/guardian consent is obtained by the School at the time of admission or Platform onboarding, in accordance with Section 9 of the DPDP Act, 2023, prior to creating a student's digital profile.
3. Parent accounts are linked to the respective student profile(s) and are provided secure login access (via mobile number/email-based OTP or credentials) to view attendance, fees, academic progress, and communications.
4. Students (where age-appropriate and permitted by the School) may be provided limited, supervised access to the Student Dashboard for assignments, tests, and learning materials.

21.4 Self-Registration (where enabled)
Where the Platform permits self-registration (e.g., for parent or alumni portals), individuals may sign up directly using their mobile number or email, subject to OTP-based verification and acceptance of this Privacy Policy and the Platform's Terms of Use. Such self-registered accounts remain subject to verification and approval by the respective School Admin before full access is granted.

21.5 Removal/Deactivation of Users
Schools may deactivate or remove Teacher, Student, or Parent accounts at any time (e.g., upon transfer, withdrawal, or cessation of employment) through the Admin Panel. Upon deactivation, access is revoked immediately, and associated Personal Data is handled in accordance with the "Data Storage, Localisation, and Retention" section of this Policy.
TXT],

            ['head' => 'Consent', 'desc' => <<<'TXT'
By accessing or using SuperLMS — whether as a School, Teacher, Student, Parent/Guardian, or Administrator — You acknowledge that You have read, understood, and agreed to this Privacy Policy, and You consent to the collection, use, storage, and processing of Your (or Your child's) information as described herein, subject to applicable law. Where You are a parent/guardian providing consent on behalf of a Child, You confirm that You are legally authorised to do so.
TXT],

            ['head' => 'Conclusion — Summary for Schools, Teachers, Students, and Parents', 'desc' => <<<'TXT'
SuperLMS is built to serve as a trusted digital backbone for schools, and this Privacy Policy reflects Our ongoing commitment to safeguarding the personal and academic data of every School, Teacher, Student, and Parent who relies on the Platform.

In summary:
• Schools are onboarded through a formal Service Agreement, and remain the primary Data Fiduciary responsible for obtaining consent from staff, students, and parents before their data is added to the Platform.
• Teachers and Staff are added by the School Admin and gain access only to the data relevant to their role.
• Students and Parents are added by the School (or, where enabled, via verified self-registration), with parental consent obtained prior to creating a minor's profile, and are given secure, role-based access to relevant academic and administrative information.
• Every User — School, Teacher, Student, or Parent — has the right to access, correct, update, restrict, or request erasure of their Personal Data, to withdraw consent at any time, and to raise grievances directly with Our Grievance Officer, who is committed to acknowledging concerns within 48 hours and resolving them within 30 days.
• SuperLMS does NOT sell Personal Data, does NOT use children's data for advertising or profiling, and stores data securely on Indian servers with industry-standard encryption and access controls.

We encourage Schools, Teachers, Students, and Parents to read this Policy carefully and to reach out to Us at support@superlms.in with any questions, clarifications, or requests concerning their Personal Data. SuperLMS remains committed to evolving this Policy in step with changes in Indian data protection law, including the Digital Personal Data Protection Act, 2023, to ensure that trust, transparency, and student safety remain at the heart of Our Platform.
TXT],

            ['head' => 'Contact Us', 'desc' => <<<'TXT'
For any questions, concerns, or requests regarding this Privacy Policy or Your Personal Data, please contact:

Super Learnings Private Limited
Email: support@superlms.in
Phone: +91 9084748563
Address: Office No. 02, Braj Vihar Colony, Jattari Khair Aligarh, Uttar Pradesh India -202137
Business Hours: Monday–Saturday, 10:00 AM – 6:00 PM (IST)
TXT],
        ];
    }
};
