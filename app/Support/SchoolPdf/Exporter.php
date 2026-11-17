<?php

namespace App\Support\SchoolPdf;

use App\Models\Organization;
use App\Support\PdfList;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Super Admin → School Data → a PDF of everything one school keeps in each
 * of its functionalities (Students, Fees, Attendance, Exams, Gate Pass …).
 *
 * Each functionality builds a document — a title, a few figures, then
 * blocks (a heading, a table, a card per person, a student's fee with its
 * payments) — and Renderer draws it plainly: black on white, thin rules,
 * no colour. The same PDFs go by mail to the school's registered email
 * (App\Jobs\SchoolDataMailPdf / SchoolDataMailSend).
 */
class Exporter
{
    /** key => [name, what the PDF holds, builder], in the school panel's order. */
    public const SECTIONS = [
        'students'      => ['Students', 'Every student\'s full details, attendance and fee', [People::class, 'students']],
        'teachers'      => ['Teachers', 'Every teacher\'s details, classes, subjects, salary', [People::class, 'teachers']],
        'staff'         => ['Staff', 'Drivers, office and support staff with bank details', [People::class, 'staff']],
        'classes'       => ['Classes', 'Classes and sections with students, subjects, teachers', [People::class, 'classes']],
        'fees'          => ['Fees', 'Each student\'s fee and every payment — when, how much, who took it', [Money::class, 'fees']],
        'ledger'        => ['Ledger', 'Every credit and debit', [Money::class, 'ledger']],
        'payroll'       => ['Payroll', 'Every salary paid', [Money::class, 'payroll']],
        'attendance'    => ['Attendance', 'Students, teachers and staff this session', [Academics::class, 'attendance']],
        'transport'     => ['Transport', 'Routes, drivers and the students on each', [Money::class, 'transport']],
        'homework'      => ['Homework', 'Homework given and who finished it', [Academics::class, 'homework']],
        'timetable'     => ['Time Table', 'The weekly periods of every class', [Academics::class, 'timetable']],
        'arrangement'   => ['Arrangement', 'Substitutions for absent teachers', [Academics::class, 'arrangement']],
        'announcements' => ['Announcements', 'Every announcement made', [Office::class, 'announcements']],
        'calendar'      => ['Calendar', 'Events, holidays and meetings', [Office::class, 'calendar']],
        'syllabus'      => ['Syllabus & Content', 'Chapters and topics by class and subject', [Academics::class, 'syllabus']],
        'assignments'   => ['Assignments', 'Assignments with submissions', [Academics::class, 'assignments']],
        'books'         => ['Books', 'Class books and the library', [Academics::class, 'books']],
        'enquiries'     => ['Enquiries', 'Website enquiries and questions to the school', [Office::class, 'enquiries']],
        'admissions'    => ['Admissions', 'Admission enquiries, fee and test result', [Office::class, 'admissions']],
        'id_cards'      => ['ID Cards', 'Cards issued to students, teachers, staff', [Office::class, 'idCards']],
        'documents'     => ['Documents', 'Documents the school uploaded', [Office::class, 'documents']],
        'exams'         => ['Exams', 'Exams and their date sheets', [Academics::class, 'exams']],
        'admit_cards'   => ['Admit Cards', 'Admit cards issued', [Academics::class, 'admitCards']],
        'seating'       => ['Seating Plan', 'Seating plans, seats and rooms', [Academics::class, 'seating']],
        'performance'   => ['Performance', 'Marks of every student in each exam', [Academics::class, 'performance']],
        'report_cards'  => ['Report Cards', 'Report cards issued', [Academics::class, 'reportCards']],
        'certificates'  => ['TC & Certificates', 'Transfer and other certificates', [Academics::class, 'certificates']],
        'complaints'    => ['Complaints', 'The complaints book', [Office::class, 'complaints']],
        'reception'     => ['Reception', 'The visitor book', [Office::class, 'reception']],
        'gate_pass'     => ['Gate Pass', 'Students who left early and who took them', [Office::class, 'gatePass']],
        'messaging'     => ['Messaging', 'WhatsApp, SMS and email messages sent', [Office::class, 'messaging']],
        'push'          => ['Push Notifications', 'App notifications sent', [Office::class, 'push']],
        'support'       => ['Support', 'Questions to SuperLMS and the replies', [Office::class, 'support']],
    ];

    /** The tables each functionality's record count is taken from. */
    private const COUNTS = [
        'students'      => ['student_details'],
        'teachers'      => ['teacher_details'],
        'classes'       => ['standards'],
        'fees'          => ['fee_payments', 'transport_fee_payments', 'custom_fee_payments'],
        'ledger'        => ['ledger_transactions'],
        'payroll'       => ['admin_salary_payments'],
        'transport'     => ['transportations'],
        'homework'      => ['home_works'],
        'arrangement'   => ['teacher_arrangements'],
        'announcements' => ['announcements'],
        'calendar'      => ['time_tables'],
        'syllabus'      => ['chapters', 'student_syllabi'],
        'assignments'   => ['assignments'],
        'books'         => ['books', 'libraries'],
        'enquiries'     => ['school_website_enquiries', 'admin_enquiries', 'contact_admin_students', 'contact_admin_teachers'],
        'admissions'    => ['admission_enquiries'],
        'id_cards'      => ['student_id_cards', 'teacher_id_cards', 'employee_id_cards'],
        'documents'     => ['admin_documents'],
        'exams'         => ['exams'],
        'admit_cards'   => ['admit_cards'],
        'seating'       => ['seating_plans'],
        'performance'   => ['exam_copies'],
        'report_cards'  => ['report_cards'],
        'certificates'  => ['transfer_certificates', 'certificates'],
        'complaints'    => ['school_complaints'],
        'reception'     => ['reception_visitors'],
        'gate_pass'     => ['student_gate_passes'],
        'messaging'     => ['messaging_campaigns'],
        'push'          => ['school_push_notifications'],
        'support'       => ['contact_super_admins'],
    ];

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::SECTIONS);
    }

    public static function label(string $key): string
    {
        return self::SECTIONS[$key][0] ?? Str::headline($key);
    }

    /** [key => how many records the school has there]. */
    public static function counts(int $org): array
    {
        $out = [];
        foreach (array_keys(self::SECTIONS) as $key) {
            $out[$key] = self::count($org, $key);
        }

        return $out;
    }

    public static function count(int $org, string $key): int
    {
        try {
            return match ($key) {
                'staff' => DB::table('admin_employees')->where('organization_id', $org)
                    ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', 'teacher'))->count(),
                'attendance' => DB::table('student_attendances')->where('organization_id', $org)
                    ->whereDate('attendance_date', '>=', \App\Support\AcademicYear::start()->toDateString())->count(),
                'timetable' => DB::table('teacher_time_tables')->where('organization_id', $org)->where('is_active', true)->count(),
                default => array_sum(array_map(
                    fn ($table) => Ctx::has($table) ? DB::table($table)->where('organization_id', $org)->count() : 0,
                    self::COUNTS[$key] ?? []
                )),
            };
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }

    /**
     * Whether the functionality has anything to send: its records, or for
     * Fees and Attendance the students themselves (whose fee and days stand
     * even before a payment or a register).
     */
    public static function hasData(int $org, string $key, ?int $count = null): bool
    {
        if (($count ?? self::count($org, $key)) > 0) {
            return true;
        }

        return in_array($key, ['fees'], true) && self::count($org, 'students') > 0;
    }

    /** The document a functionality's PDF draws. */
    public static function document(int $org, string $key): array
    {
        $build = self::SECTIONS[$key][2] ?? null;
        if (!$build) {
            throw new \InvalidArgumentException("Unknown School Data section [{$key}].");
        }

        return $build(new Ctx($org)) + ['orientation' => 'portrait', 'summary' => [], 'blocks' => [], 'subtitle' => ''];
    }

    /** The functionality's PDF bytes (drawn by Renderer). */
    public static function pdf(int $org, string $key): string
    {
        @set_time_limit(300);

        return Renderer::render(self::document($org, $key), self::school(Organization::find($org)), now()->format('d M Y, h:i A'));
    }

    /** "green-valley-school-fees-2026-11-17.pdf" */
    public static function filename(?Organization $org, string $key): string
    {
        return Str::slug(Str::limit((string) ($org?->name ?: 'school'), 60, '')) . '-' . Str::slug(self::label($key)) . '-' . now()->format('Y-m-d') . '.pdf';
    }

    /** The school's name, contact line and logo for the PDF's head. */
    private static function school(?Organization $org): array
    {
        if (!$org) {
            return ['name' => 'School', 'line' => '', 'logo' => null];
        }

        $logo = null;
        if ($org->logo) {
            $logo = Cache::remember('school-data-pdf-logo:' . md5((string) $org->logo), 600, function () use ($org) {
                $jpeg = PdfList::logo(\App\Support\PdfLogo::src($org->logo) ?? $org->logo);

                return $jpeg ? 'data:image/jpeg;base64,' . base64_encode($jpeg) : '';
            }) ?: null;
        }

        return [
            'name' => (string) $org->name,
            'line' => collect([
                trim(implode(', ', array_filter([$org->address, $org->city, $org->state, $org->postal_code]))),
                $org->mobile_number,
                $org->email,
            ])->filter()->implode(' · '),
            'ids'  => collect([
                $org->education_board,
                $org->affiliation_no ? 'Affiliation ' . $org->affiliation_no : null,
                $org->udise_number ? 'UDISE ' . $org->udise_number : null,
                $org->school_code ? 'Code ' . $org->school_code : null,
            ])->filter()->implode(' · '),
            'logo' => $logo,
        ];
    }
}
