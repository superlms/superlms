<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Collapse the ~20 fine-grained FAQ categories into 7 clear buckets so the
     * public FAQ page shows a tidy set of chips, and top up each bucket so all
     * platform areas are covered.
     */
    public function up(): void
    {
        // 1) Remap every existing category into one of 7 umbrella buckets.
        $map = [
            'General'             => 'Getting Started',
            'Getting Started'     => 'Getting Started',
            'Pricing'             => 'Getting Started',
            'Product'             => 'Getting Started',
            'Onboarding'          => 'Getting Started',

            'Attendance'          => 'Academics & Exams',
            'Timetable'           => 'Academics & Exams',
            'Study Material'      => 'Academics & Exams',
            'Exams & Results'     => 'Academics & Exams',

            'Fees'                => 'Fees & Payroll',
            'Payroll & Staff'     => 'Fees & Payroll',

            'Admissions'          => 'Admissions & Students',

            'Communication'       => 'Communication',

            'Mobile Apps'         => 'Apps & Operations',
            'Customisation'       => 'Apps & Operations',
            'Reports & Analytics' => 'Apps & Operations',
            'Transport'           => 'Apps & Operations',

            'Data & Migration'    => 'Security & Support',
            'Security'            => 'Security & Support',
            'Support'             => 'Security & Support',
        ];

        foreach ($map as $old => $new) {
            DB::table('faqs')->where('category', $old)->update(['category' => $new]);
        }

        // 2) Top up each bucket so every platform area is represented.
        $now  = now();
        $faqs = [
            // ── Getting Started ──────────────────────────────────────────
            ['Getting Started', 'Which devices and platforms does SUPERLMS support?', 'SUPERLMS runs on any modern web browser and has dedicated Android and iOS apps for admins, teachers, students and parents — so everyone stays connected from anywhere.'],
            ['Getting Started', 'Can we try SUPERLMS before committing?', 'Absolutely. Request a free, no-obligation demo and our team will walk you through the platform with your school in mind, then help you pick the right plan.'],
            ['Getting Started', 'How long does it take to go live?', 'Most schools go live within a few days. Our onboarding team imports your data, configures classes and fees, and trains your staff so the switch is smooth.'],

            // ── Academics & Exams ────────────────────────────────────────
            ['Academics & Exams', 'Can teachers assign and collect homework online?', 'Yes. Teachers post homework with due dates and attachments; students submit digitally and teachers review and grade it all in one place.'],
            ['Academics & Exams', 'How are exams and report cards handled?', 'You can schedule exams, enter marks, and auto-generate report cards with grades, remarks and attendance — ready to share as PDF or print for parents.'],
            ['Academics & Exams', 'Can we build a class timetable in the system?', 'Yes. Create conflict-free, period-wise timetables for every class and section, assign teachers, and share the live schedule instantly to every phone.'],
            ['Academics & Exams', 'Can teachers share study material and notes?', 'Teachers can upload notes, PDFs, videos and slides class-wise, and students access them anytime, anywhere, on any device.'],

            // ── Fees & Payroll ───────────────────────────────────────────
            ['Fees & Payroll', 'Can parents pay fees online?', 'Yes. Parents pay securely online and get instant digital receipts, while your accounts team gets automatic reconciliation and dues tracking.'],
            ['Fees & Payroll', 'Can we set up our own fee structure and instalments?', 'Yes. Configure flexible fee heads (tuition, transport, exam and more), assign them class-wise, and set instalments, due dates and late fines.'],
            ['Fees & Payroll', 'Does SUPERLMS handle staff payroll?', 'Yes. Manage salaries, allowances, deductions and payslips, with staff attendance and leave feeding straight into monthly salary calculations.'],

            // ── Admissions & Students ────────────────────────────────────
            ['Admissions & Students', 'Can we manage the whole admission process online?', 'Yes. From online enquiry and application forms to document uploads, approval and one-click enrolment — the entire admission journey is paperless.'],
            ['Admissions & Students', 'Where is all the student information kept?', 'Every student has a single profile holding academics, attendance, fee status, uploaded documents and parent contact details — always a search away.'],
            ['Admissions & Students', 'Can we generate ID cards and certificates?', 'Yes. Design and bulk-generate student and staff ID cards, and issue transfer certificates and bonafide letters — QR-verified and tamper-proof.'],

            // ── Communication ────────────────────────────────────────────
            ['Communication', 'Do parents get instant notifications?', 'Yes. Parents receive instant app notifications for absences, fee dues, announcements and results, so nothing important is ever missed.'],
            ['Communication', 'Can we broadcast announcements in bulk?', 'Yes. Send announcements to the whole school, selected classes, or individual parents in one shot via app notification, SMS or email.'],

            // ── Apps & Operations ────────────────────────────────────────
            ['Apps & Operations', 'Is there a mobile app for parents and students?', 'Yes. Dedicated Android and iOS apps keep admins, teachers, students and parents connected — attendance, fees, homework, results and notices in their pocket.'],
            ['Apps & Operations', 'Can we manage school transport?', 'Yes. Set up routes, stops and vehicles, assign students, collect transport fees and keep parents updated on delays.'],
            ['Apps & Operations', 'Can the platform be branded for our school?', 'Yes. Your school name and logo appear across the platform and reports, so it feels like your very own system to everyone who uses it.'],
            ['Apps & Operations', 'What reports and analytics are available?', 'You get ready-made reports across attendance, fee collection, dues and exam performance, giving management a clear, real-time view of the school.'],
            ['Apps & Operations', 'Can we control staff access with roles and permissions?', 'Yes. Access is fully role-based, so admins, teachers and other staff each see only the features relevant to them.'],

            // ── Security & Support ───────────────────────────────────────
            ['Security & Support', 'Is our school data secure?', 'Yes. Your data is stored securely, access is role-based, and payments are processed through trusted, secure gateways. Your information is never shared without consent.'],
            ['Security & Support', 'Can we import our existing school data?', 'Yes. Our onboarding team helps you import your existing student, class and fee data during setup, so you start with everything already in place.'],
            ['Security & Support', 'What support channels do you offer?', 'We provide hands-on onboarding, staff training, and ongoing support over call, chat and WhatsApp — you are never left on your own.'],
        ];

        $rows = [];
        foreach ($faqs as [$category, $question, $answer]) {
            if (DB::table('faqs')->where('question', $question)->exists()) {
                continue; // keep re-runs and existing content safe
            }
            $rows[] = [
                'category'   => $category,
                'question'   => $question,
                'answer'     => $answer,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            DB::table('faqs')->insert($rows);
        }
    }

    public function down(): void
    {
        // Category consolidation is not reversed (the original fine-grained
        // categories are intentionally retired); seeded FAQs are left in place.
    }
};
