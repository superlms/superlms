<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // [category, question, answer] covering every major SUPERLMS platform area.
        $faqs = [
            // ── Admissions & Enrollment ──────────────────────────────────
            ['Admissions', 'Can we manage the entire admission process online?', 'Yes. SUPERLMS handles the full admission journey — online enquiry and application forms, document uploads, approval workflow, and one-click enrollment into a class and section. Approved students are automatically created with login credentials for the student and parent apps.'],
            ['Admissions', 'Can parents apply for admission from their phone?', 'Yes. Prospective parents can fill and submit the admission form from any device. Your admin team reviews each application, requests missing documents if needed, and approves it — all from the admin panel.'],
            ['Admissions', 'Can we collect an application or admission fee online?', 'Yes. You can attach a fee to the admission form so applicants pay securely online, and the payment is automatically recorded against the application.'],

            // ── Attendance ───────────────────────────────────────────────
            ['Attendance', 'How is student attendance marked?', 'Teachers mark attendance for their class in seconds from the web or mobile app. You can mark daily or period-wise attendance, and parents are notified automatically when their child is marked absent.'],
            ['Attendance', 'Do parents get notified when a student is absent?', 'Yes. As soon as a student is marked absent, the parent receives an instant notification, so there are no surprises and follow-up is easy.'],
            ['Attendance', 'Can we track staff and teacher attendance too?', 'Yes. SUPERLMS records staff attendance as well, which feeds directly into payroll for accurate salary and leave calculations.'],

            // ── Timetable ────────────────────────────────────────────────
            ['Timetable', 'Can we create a class timetable in the system?', 'Yes. You can build period-wise timetables for every class and section, assign subjects and teachers, and students and parents see the live schedule in their app.'],
            ['Timetable', 'Can teachers see their own teaching schedule?', 'Yes. Each teacher gets a personalised timetable showing exactly which class, subject and period they are teaching, on both web and mobile.'],

            // ── Exams & Results ──────────────────────────────────────────
            ['Exams & Results', 'Can we schedule exams and publish results online?', 'Yes. You can create exam schedules, enter marks, and publish results. Students and parents can view report cards and download them from the app.'],
            ['Exams & Results', 'Does the system generate report cards automatically?', 'Yes. Once marks are entered, SUPERLMS calculates totals, grades and percentages and generates report cards based on your grading scheme.'],
            ['Exams & Results', 'Can we use our own grading system?', 'Yes. Grading scales and pass criteria are configurable, so the report cards match your school\'s existing marking scheme.'],

            // ── Fees ─────────────────────────────────────────────────────
            ['Fees', 'Can we define our own fee structure?', 'Yes. You can set up flexible fee structures with multiple heads (tuition, transport, exam, etc.), assign them class-wise, and configure instalments and due dates.'],
            ['Fees', 'How do parents know about pending fees?', 'Parents see outstanding dues and upcoming due dates in their app and receive reminders, which dramatically reduces follow-up calls for your accounts team.'],
            ['Fees', 'Do we get automatic receipts and reconciliation?', 'Yes. Every online payment generates an instant digital receipt for the parent, and your accounts team gets automatic reconciliation and dues tracking.'],

            // ── Study Material ───────────────────────────────────────────
            ['Study Material', 'Can teachers share notes and study material?', 'Yes. Teachers can upload notes, documents, videos and other study material class-wise, and students can access them anytime from the app.'],
            ['Study Material', 'Can we assign and collect homework online?', 'Yes. Teachers can post homework and assignments with due dates, and students and parents are notified so nothing gets missed.'],

            // ── Communication ────────────────────────────────────────────
            ['Communication', 'How does the school communicate with parents?', 'SUPERLMS gives you in-app notifications and announcements to reach all parents, a class, or an individual instantly — no need for paper circulars or WhatsApp groups.'],
            ['Communication', 'Can we send notices to specific classes only?', 'Yes. Announcements and notices can be targeted to the whole school, selected classes, or individual students and parents.'],

            // ── Mobile Apps ──────────────────────────────────────────────
            ['Mobile Apps', 'Who gets a mobile app?', 'SUPERLMS has dedicated apps for admins, teachers, students and parents on both Android and iOS, so everyone stays connected from anywhere.'],
            ['Mobile Apps', 'Do we need to download apps from the Play Store or App Store?', 'Yes, the apps are available on the Google Play Store and Apple App Store. Your users simply install the app and log in with the credentials the school provides.'],

            // ── Reports & Analytics ──────────────────────────────────────
            ['Reports & Analytics', 'What reports does SUPERLMS provide?', 'You get ready-made reports across attendance, fee collection, dues, exam performance and more, giving management a clear, real-time picture of the school.'],
            ['Reports & Analytics', 'Can we export reports?', 'Yes. Key reports can be viewed on-screen and exported so you can share them with management or use them in your own records.'],

            // ── Payroll & Staff ──────────────────────────────────────────
            ['Payroll & Staff', 'Does SUPERLMS handle staff payroll?', 'Yes. You can manage staff records, salary structures and monthly payroll, with attendance and leave feeding directly into salary calculations.'],
            ['Payroll & Staff', 'Can we manage staff roles and permissions?', 'Yes. Access is role-based, so admins, teachers and other staff each see only the features relevant to them, keeping your data secure.'],

            // ── Transport ────────────────────────────────────────────────
            ['Transport', 'Can we manage school transport?', 'Yes. You can set up routes, stops and vehicles, assign students to routes, and manage transport fees alongside your regular fee structure.'],

            // ── Data & Migration ─────────────────────────────────────────
            ['Data & Migration', 'Can we import our existing student data?', 'Yes. Our onboarding team helps you import your existing student, class and fee data during setup, so you start with everything already in place.'],
            ['Data & Migration', 'Is our data safe if we ever want to leave?', 'Your data belongs to you. Key records can be exported, and we will assist you in retrieving your information should you ever need it.'],

            // ── Customisation ────────────────────────────────────────────
            ['Customisation', 'Can SUPERLMS be branded for our school?', 'Yes. Your school\'s name and logo appear across the platform and reports, so it feels like your own system to staff, students and parents.'],
            ['Customisation', 'Does each school get its own website?', 'Yes. SUPERLMS can provide your school with its own website presence, including an enquiry form that flows straight into your admin panel.'],
        ];

        $rows = [];
        foreach ($faqs as [$category, $question, $answer]) {
            // Skip anything already present (keeps re-runs and existing seeds safe).
            if (DB::table('faqs')->where('question', $question)->exists()) {
                continue;
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
        // Non-destructive by design: seeded FAQs are left in place on rollback
        // so manually curated content is never lost.
    }
};
