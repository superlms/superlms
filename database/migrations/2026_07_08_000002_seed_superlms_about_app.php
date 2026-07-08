<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the official ABOUT SUPERLMS content into the about_apps row as
 * separate content sections (same pattern as the Terms of Use / Privacy
 * Policy seeds). Only heading/sub_heading/content are replaced — logo,
 * company info, core team, social media and documents already stored on
 * the row are preserved, and contact details / address are filled only
 * when currently empty. The super-admin panel keeps full edit control.
 */
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('about_apps')->orderBy('id')->first();

        $data = [
            'heading'     => 'SUPERLMS',
            'sub_heading' => "India's Affordable School Management & Learning Platform — a product of Super Learnings Private Limited, Aligarh, Uttar Pradesh",
            'content'     => json_encode($this->sections(), JSON_UNESCAPED_UNICODE),
            'updated_at'  => now(),
        ];

        $contactDetails = json_encode([
            ['type' => 'Email',   'value' => 'support@superlms.in'],
            ['type' => 'Phone',   'value' => '+91 9084748563'],
            ['type' => 'Website', 'value' => 'www.superlms.in'],
        ], JSON_UNESCAPED_UNICODE);

        $address = 'House No. 02, Braj Vihar Colony, Jattari, Khair, Aligarh, Uttar Pradesh – 202137';

        if ($row) {
            $existingContacts = json_decode($row->contact_details ?? '[]', true) ?: [];
            if (empty($existingContacts)) {
                $data['contact_details'] = $contactDetails;
            }
            if (empty($row->address)) {
                $data['address'] = $address;
            }
            DB::table('about_apps')->where('id', $row->id)->update($data);
        } else {
            $data['contact_details'] = $contactDetails;
            $data['address']         = $address;
            if (Schema::hasColumn('about_apps', 'company_name')) {
                $data['company_name'] = 'Super Learnings Private Limited';
            }
            DB::table('about_apps')->insert($data + ['created_at' => now()]);
        }
    }

    public function down(): void
    {
        // Content seed — nothing sensible to roll back to.
    }

    private function sections(): array
    {
        return [
            ['title' => 'Who We Are', 'description' => <<<'TXT'
SuperLMS is a cloud-based, all-in-one School Management and Learning Management System built to bring academics, administration, and communication onto a single platform. Instead of schools juggling separate registers, spreadsheets, WhatsApp groups, and paper records, SuperLMS unifies attendance, timetables, fee collection, assignments, examinations, report cards, and parent communication into one connected ecosystem — accessible from any device, anywhere.

SuperLMS is developed and operated by Super Learnings Private Limited, headquartered in Aligarh, Uttar Pradesh, and is trusted by schools across India — from small rural institutions to large multi-campus academies — with more than 50,000 students actively learning on the platform.
TXT],

            ['title' => 'Our Mission', 'description' => <<<'TXT'
Our mission is simple: make quality school-management technology accessible and affordable for every institution in India — not just large, well-funded schools, but also small-town and rural institutions that are often priced out of modern EdTech tools.

We believe that a school in a small town deserves the same powerful digital tools as a large city academy, at a price point that actually makes sense for its budget. This belief shapes every feature we build and every pricing decision we make.
TXT],

            ['title' => 'Our Story', 'description' => <<<'TXT'
SuperLMS began in 2024 out of a small office in Aligarh, Uttar Pradesh — not as a corporate initiative, but as a response to a very real, everyday problem. Our founding team watched teachers spend hours on paper attendance registers, principals repeatedly chase parents for pending fees, and students miss timely feedback on their academic progress simply because the tools available to schools hadn't kept pace with the rest of the world.

The first version of SuperLMS launched quietly with a single partner school and a core team of three people. Word spread organically — one principal recommending the platform to another, teachers sharing their experience in WhatsApp groups — and the school base grew steadily from there.

By the end of 2025, we had introduced dedicated Android and iOS applications, integrated biometric attendance, and real-time WhatsApp notifications for parents. Today, SuperLMS supports a growing network of schools across India, with fully integrated modules that continue to evolve based on direct feedback from the educators who use them every day.

Key milestones:
🌱 Bootstrapped Growth — grown from a handful of schools to a nationwide presence, driven almost entirely by word-of-mouth and product quality.
🇮🇳 Made in India — designed from the ground up by a team that understands the day-to-day realities of Indian schools.
💡 Innovation-First — new features, updates, and improvements released regularly, included in every plan at no extra cost.
🤝 Community-Driven — every major feature is shaped in collaboration with our network of partner schools.
TXT],

            ['title' => 'What SuperLMS Does', 'description' => <<<'TXT'
SuperLMS brings together every core function a school needs to run smoothly:

• Streamlines Academic Management — attendance, timetables, fee management, and note-sharing, all in one unified platform, saving administrators several hours of manual work every day.
• Enhances the Learning Experience — real-time progress tracking, digital resource sharing, and interactive assessment tools that help both teachers and students engage more effectively.
• Keeps Costs Affordable — flexible, transparent pricing designed to make advanced school-management features available to institutions of every size and budget.
• Provides Secure, Reliable Infrastructure — enterprise-grade security, daily backups, SSL encryption, and data handling aligned with Indian data protection law.
• Enables Multi-Platform Access — dedicated Android and iOS apps so students, teachers, and parents stay connected even in low-bandwidth areas.
• Offers Dedicated Onboarding & Support — every school is assigned an onboarding specialist, with fast, responsive support throughout their journey on the platform.
TXT],

            ['title' => 'Core Features', 'description' => <<<'TXT'
SuperLMS includes 50+ modules covering every aspect of school operations, including:

📊 Smart Analytics — real-time performance tracking, attendance heatmaps, subject-wise trend analysis, and exportable reports for admins, teachers, and students.
✅ Attendance Management — attendance marked via mobile, QR code, or biometric device, with instant SMS/WhatsApp alerts to parents when a student is absent.
🗓️ Timetable Scheduling — AI-assisted, conflict-free timetable generation with drag-and-drop editing and automatic substitute-teacher assignment.
💰 Fee Management — online and offline fee collection, late-fee automation, scholarship handling, instant digital receipts, and financial reporting.
📝 Assignments & Quizzes — creation and auto-grading of assignments and online tests (MCQ, true/false, short answer), with instant results and performance analytics.
🏫 School Administration — school profile management, student/staff records, bulk ID card and admit card generation, library, payroll, and transport route management.
📚 Digital Content & Library — subject-wise notes, videos, and PDFs accessible to students anytime, on any device, online or offline.
💬 Communication & Alerts — announcements, push notifications, homework alerts, and instant SMS/WhatsApp updates connecting parents, teachers, and students.
🎓 Report Cards & Certificates — auto-generated report cards, term results, transfer/bonafide certificates, and ID cards, ready to print with one click.
TXT],

            ['title' => 'Built for Every Role', 'description' => <<<'TXT'
SuperLMS provides a dedicated, role-based dashboard for every stakeholder in a school's ecosystem, so everyone works from a single source of truth:

🏛️ For Administrators — a complete school dashboard with live KPIs, student and teacher record management, fee/payroll/financial reporting, timetable generation, bulk ID/admit/report card generation, and transport and library management.

👩‍🏫 For Teachers — attendance marking from a mobile device in seconds, uploading notes and study materials, creating and auto-grading quizzes and assignments, tracking per-subject student performance, and communicating with parents.

🎓 For Students — anytime access to notes and study content, assignment tracking and digital submission, instant quiz results, attendance and performance visibility, and downloadable admit cards and report cards.

👨‍👩‍👧 For Parents — real-time attendance notifications via SMS/WhatsApp, academic progress and grade tracking, online fee payment with digital receipts, and direct communication with teachers.

🧾 For Accounts Teams — automated fee invoicing and dues tracking, payroll and salary slip management, income/expense ledgers, online payment reconciliation, and audit-ready financial exports.

📝 For Examination Management — exam schedules, datesheets and seating arrangements, grading scheme configuration, automated result processing and rank calculation, and instant publishing of results.
TXT],

            ['title' => 'How We Work — Our Onboarding Model', 'description' => <<<'TXT'
We follow a structured, school-first process to get every institution up and running smoothly:

1. School Onboarding — a free needs-assessment call, followed by platform configuration around the school's classes, subjects, academic year, branding, and user roles — typically completed within 3–5 business days.
2. Data Migration — existing student records, staff information, fee structures, and historical data are migrated securely, verified before go-live, with an emphasis on zero data loss.
3. Staff & User Training — live virtual training sessions for administrators, teachers, and parents, supported by guides, video tutorials, and a dedicated WhatsApp support group.
4. Go-Live & Monitoring — the school goes live with full platform access, with our team closely monitoring the first 30 days to ensure a smooth transition.
5. Continuous Updates — monthly feature releases, security patches, and performance improvements, included in every plan at no additional cost.
6. Ongoing Support — dedicated support available via phone, WhatsApp, email, and in-app chat, with fast response times on business days.
TXT],

            ['title' => 'Security & Reliability', 'description' => <<<'TXT'
We take the protection of school and student data seriously. Our infrastructure includes:

🔒 256-bit SSL Encryption for all data in transit, protecting logins, transactions, and communications;
🛡️ Role-Based Access Control, ensuring each user only sees the data relevant to their role;
☁️ Daily, Geo-Redundant Cloud Backups, recoverable within minutes; and
✅ Compliance-Aligned Data Handling, in keeping with the Indian Information Technology Act, 2000, and the Digital Personal Data Protection Act, 2023.

For full details on how We collect, use, and protect Personal Data, please refer to Our Privacy Policy.
TXT],

            ['title' => 'Our Platforms', 'description' => <<<'TXT'
SuperLMS is one of three connected platforms operated by Super Learnings Private Limited, designed to support schools, students, and families end-to-end:

• SuperLMS — our core school management system for attendance, fees, timetables, academics, and communication between administrators, teachers, students, and parents.
• Edyone — a companion EdTech platform offering academic and competitive-exam courses, interactive lessons, practice tests, and smart learning tools for students.
• Super Safe — a parental-control application focused on screen-time monitoring, content filtering, and online safety for children.

Together, these platforms aim to support a school's ecosystem beyond the classroom, into the home.
TXT],

            ['title' => 'Our Team', 'description' => <<<'TXT'
SuperLMS is built by a team of educators, engineers, and designers based in Aligarh, Uttar Pradesh — the same team that speaks directly with teachers, principals, and parents to understand what schools actually need, rather than guessing from a distance.

Founder & CEO — Annant Dagur
Annant founded SuperLMS with a clear goal: to make affordable, powerful school-management technology accessible to every institution in India, from large city schools to small towns. He continues to work closely with educators on the ground, shaping their everyday challenges into practical features used by thousands of students, teachers, and parents.

We are also actively growing our team of educators, engineers, and designers who want to work on technology that has a direct, visible impact on schools and students across the country.
TXT],

            ['title' => 'Why Schools Choose SuperLMS', 'description' => <<<'TXT'
• Affordable, transparent pricing — a single plan that unlocks all modules, with no hidden fees or per-feature add-ons.
• Fast, guided onboarding — most schools are fully set up within 3–5 business days, with free data migration.
• Built for the Indian school context — designed around how Indian schools actually operate, not adapted from a foreign product.
• Constant improvement — regular feature updates and security improvements, at no extra cost to existing schools.
• Responsive support — dedicated onboarding specialists and fast-response support across phone, WhatsApp, email, and in-app chat.
TXT],

            ['title' => 'Get in Touch', 'description' => <<<'TXT'
Whether You're a School exploring a new management system, a parent curious about how SuperLMS works, or someone interested in joining our team, We'd love to hear from You.

Super Learnings Private Limited
📧 Email: support@superlms.in
📱 Phone: +91 9084748563
📍 Address: House No. 02, Braj Vihar Colony, Jattari, Khair, Aligarh, Uttar Pradesh – 202137
🌐 Website: superlms.in
TXT],
        ];
    }
};
