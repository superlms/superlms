<?php

namespace App\Services\Gemini;

use App\Models\Admin\AdmissionEnquiry;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\Announcement;
use App\Models\Admin\Certificate;
use App\Models\Admin\Exam;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\HomeWork;
use App\Models\Admin\LedgerTransaction;
use App\Models\Admin\Transportation;
use App\Models\Admin\TransferCertificate;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\SuperAdmin\CreditQuery;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * The LMS → Gemini bridge.
 *
 * Builds a plain-text "knowledge pack" describing the signed-in user's slice of
 * the LMS: what the product is, what the panel can do, and a live snapshot of
 * this school's (or the platform's) numbers. That pack is what gets uploaded to
 * Gemini's context cache once and reused by every question, so a question only
 * pays for itself.
 *
 * It is deliberately a *summary*: anything specific (a named student, a fee
 * range, a date's attendance) is answered by a tool call in {@see LmsToolbox},
 * which re-queries live data under the same scope.
 */
class LmsKnowledge
{
    public function __construct(private readonly LmsScope $scope) {}

    /** Cached so a burst of questions runs the summary queries once. */
    public function pack(): string
    {
        $ttl = (int) config('gemini.cache.snapshot_seconds', 300);

        return Cache::remember(
            'gemini:pack:' . $this->scope->key(),
            $ttl,
            fn () => $this->build()
        );
    }

    /** Changes whenever the pack does — used to name/expire the Gemini cache. */
    public function fingerprint(): string
    {
        return substr(sha1($this->pack()), 0, 16);
    }

    private function build(): string
    {
        try {
            return $this->scope->isSchool() ? $this->schoolPack() : $this->platformPack();
        } catch (\Throwable $e) {
            Log::warning('gemini.knowledge build failed', ['error' => $e->getMessage()]);

            // A partial pack is still a usable assistant; tools re-query live data.
            return $this->productPack();
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Shared: what the product is.
    //
    // Two jobs. It gives the model the vocabulary to map a user's words ("TC",
    // "fee cycle", "arrangement") onto the right tool, and it answers the
    // "where do I do X" questions that no tool can. It is also the stable head
    // of every prompt: keeping it well above the model's minimum cacheable
    // prefix is what lets implicit caching hit, so questions after the first
    // one stop re-paying for this block. Keep it byte-stable — no timestamps,
    // no counts, nothing per-school.
    // ─────────────────────────────────────────────────────────────────────
    private function productPack(): string
    {
        return <<<TXT
        # SuperLMS — product reference

        SuperLMS is a multi-tenant school management system used by schools in
        India. Each school is an "organization"; every record in the system
        belongs to exactly one organization and never leaks between them.

        ## Panels and what each one owns

        ### Admin panel (roles: admin, sub-admin)
        Runs the day-to-day school. Screens:
        - Students — admissions, profiles, class/section assignment, ID cards,
          bulk import, login credentials sent by email.
        - Teachers — profiles, subjects taught, classes assigned, ID cards.
        - Users — sub-admins with a restricted set of screens.
        - Classes (Standards) & Sections — the class list, sections under each,
          and the subjects attached to them.
        - Attendance — daily student attendance and teacher attendance, marked
          per class/section, viewable by date, by month and yearly.
        - Time Table & Calendar — period-wise timetable, school calendar events.
        - Arrangement — when a teacher is absent, which other teacher covers
          each of their periods that day.
        - Exams — exam creation, datesheet, subject marks, exam copies, seating
          plans with rooms/seats/invigilators, admit cards, report cards.
        - Homework & Assignments — posted per class/section/subject; assignments
          can be MCQ based, with student submissions.
        - Syllabus, Books/Library, Documents, Announcements, Enquiries.
        - Transport — routes, drivers, stops, monthly fee, students on a route.
        - Certificates & TC — Achievement and Participation certificates, and
          Transfer Certificates, each printable as a PDF.
        - School website — the public site for the school, editable from here.

        ### Accounts panel (role: accounts)
        Runs the school's money. Screens:
        - Fee Structure — what each class is charged, per fee head and year.
        - Fee Cycles — the school's installment plan: each installment is a
          percentage of the year's fee with a due date and a per-day penalty.
        - Fee Submission — collecting a payment from a student and issuing a
          receipt.
        - Concessions and Penalties — waivers granted, late fees applied.
        - Transport fees, Payroll (salary payments to staff), Ledger.
        - Accounts dashboard — collection analytics.

        ### Super-admin panel (roles: super-admin, sub-super-admin)
        Runs the SuperLMS platform itself, above the schools:
        - Schools (organizations) — onboarding, activation, modules enabled.
        - Platform fee structures and platform fee payments — what SuperLMS
          charges each school, and what each school has paid.
        - Credits — schools requesting credit, with approval and penalties.
        - Support messages, LMS ratings, website demo requests, blogs, careers.
        - Its own employees, attendance, payroll and documents.
        A sub-super-admin can be limited to one school and to a subset of
        screens.

        ## Vocabulary — get these right
        - Standard = class (e.g. "10th"). Section = a division of a class ("A").
        - Admission No uniquely identifies a student inside a school. Roll No is
          only unique within a class.
        - There are three different fee things and they are never the same:
          1. Fee Structure — the amount a class is charged.
          2. Fee Cycle — the installment plan (percentage + due date + penalty).
          3. Fee Payment — money actually collected from a student, with a
             receipt number.
          "Pending fee" means what a class is charged minus what that student
          has actually paid.
        - Platform fees (super-admin) are what a school pays SuperLMS. They are
          unrelated to the fees a school collects from its students.
        - TC = Transfer Certificate, issued when a student leaves. Certificates
          are separate and are Achievement or Participation.
        - Arrangement = covering an absent teacher's periods.
        - Attendance status is present, absent, half day or holiday. A day
          nobody marked stays genuinely unmarked — it is NOT an absence, and it
          must never be reported as one.
        - Ledger = the school's own cash in / cash out book.
        - Concession = a waiver on a student's fee. Penalty = a late fee.
        - Academic year is written like "2026-27".
        - Money is Indian rupees and is written in the Indian digit grouping,
          e.g. ₹1,20,000.
        TXT;
    }

    // ─────────────────────────────────────────────────────────────────────
    // School snapshot (admin / sub-admin / accounts)
    // ─────────────────────────────────────────────────────────────────────
    private function schoolPack(): string
    {
        $orgId = (int) $this->scope->organizationId;
        $org   = Organization::find($orgId);

        $now        = now();
        $today      = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $yearStart  = $now->copy()->subYear()->toDateString();

        $students = StudentDetail::where('organization_id', $orgId)->count();
        $teachers = TeacherDetail::where('organization_id', $orgId)->count();
        $staff    = AdminEmployee::where('organization_id', $orgId)->count();

        $classRows = Standard::where('organization_id', $orgId)
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name', 'board']);

        $perClass = StudentDetail::where('organization_id', $orgId)
            ->selectRaw('standard_id, COUNT(*) as c')
            ->groupBy('standard_id')
            ->pluck('c', 'standard_id');

        $sectionsByClass = Section::where('organization_id', $orgId)
            ->orderBy('id')
            ->get(['id', 'standard_id', 'name'])
            ->groupBy('standard_id');

        $classLines = $classRows->map(function ($s) use ($perClass, $sectionsByClass) {
            $secs = ($sectionsByClass[$s->id] ?? collect())->pluck('name')->implode(', ');

            return sprintf(
                '- %s%s — %d student(s)%s',
                $s->name,
                $s->board ? ' [' . $s->board . ']' : '',
                (int) ($perClass[$s->id] ?? 0),
                $secs !== '' ? ' — sections: ' . $secs : ' — no sections'
            );
        })->implode("\n");

        $genders = StudentDetail::where('organization_id', $orgId)
            ->selectRaw('gender, COUNT(*) as c')->groupBy('gender')->pluck('c', 'gender');

        $subjects = Subject::where('organization_id', $orgId)->orderBy('name')->pluck('name');

        // ── Money ──
        $feeMonth   = (float) FeePayment::where('organization_id', $orgId)
            ->whereBetween('payment_date', [$monthStart, $today])->sum('amount');
        $feeYear    = (float) FeePayment::where('organization_id', $orgId)
            ->whereBetween('payment_date', [$yearStart, $today])->sum('amount');
        $feeAll     = (float) FeePayment::where('organization_id', $orgId)->sum('amount');
        $feeCount   = FeePayment::where('organization_id', $orgId)->count();

        $byMode = FeePayment::where('organization_id', $orgId)
            ->selectRaw('payment_mode, COUNT(*) c, SUM(amount) total')
            ->groupBy('payment_mode')->get()
            ->map(fn ($r) => sprintf('%s: %d payment(s), %s', $r->payment_mode ?: 'unspecified', (int) $r->c, $this->money($r->total)))
            ->implode('; ');

        $structures = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)
            ->with('standard:id,name')
            ->orderBy('standard_id')
            ->get(['id', 'standard_id', 'fee_name', 'amount', 'fee_type', 'academic_year'])
            ->map(fn ($f) => sprintf(
                '- %s / %s — %s (%s, %s)',
                $f->standard->name ?? 'all classes',
                $f->fee_name,
                $this->money($f->amount),
                $f->fee_type ?: 'n/a',
                $f->academic_year ?: 'current year'
            ))->implode("\n");

        $ledgerIn  = (float) LedgerTransaction::where('organization_id', $orgId)->where('type', 'in')->sum('amount');
        $ledgerOut = (float) LedgerTransaction::where('organization_id', $orgId)->where('type', 'out')->sum('amount');

        // ── Attendance (today) ──
        $att = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $today)
            ->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $attLine = $att->isEmpty()
            ? 'not marked yet today'
            : sprintf(
                'present %d, absent %d, half-day %d, holiday %d',
                (int) ($att[1] ?? 0), (int) ($att[0] ?? 0), (int) ($att[2] ?? 0), (int) ($att[3] ?? 0)
            );

        // ── Activity ──
        $exams      = Exam::where('organization_id', $orgId)->count();
        $nextExam   = Exam::where('organization_id', $orgId)
            ->whereDate('start_date', '>=', $today)->orderBy('start_date')->first(['exam_name', 'start_date']);
        $notices    = Announcement::where('organization_id', $orgId)->count();
        $homework   = HomeWork::where('organization_id', $orgId)->count();
        $routes     = Transportation::where('organization_id', $orgId)->count();
        $certs      = Certificate::where('organization_id', $orgId)->count();
        $tcs        = TransferCertificate::where('organization_id', $orgId)->count();
        $enquiries  = AdmissionEnquiry::where('organization_id', $orgId)->count();
        $panelUsers = User::where('organization_id', $orgId)
            ->whereIn('role', ['admin', 'sub-admin', 'accounts'])->count();

        $product = $this->productPack();

        return <<<TXT
        {$product}

        # Live snapshot — {$this->orgName($org)}
        (generated {$now->format('d M Y, H:i')} IST; counts are live at that moment)

        ## School
        - Name: {$this->orgName($org)}
        - Board: {$this->v($org?->education_board)} | Medium/State: {$this->v($org?->state)}
        - School code: {$this->v($org?->school_code)} | Affiliation no: {$this->v($org?->affiliation_no)} | UDISE: {$this->v($org?->udise_number)}
        - Address: {$this->v($org?->address)}
        - Contact: {$this->v($org?->email)} / {$this->v($org?->mobile_number)}
        - Panel users (admin/sub-admin/accounts): {$panelUsers}

        ## People
        - Students: {$students}
        - Teachers: {$teachers}
        - Non-teaching employees: {$staff}
        - Student gender split: male {$this->i($genders['male'] ?? 0)}, female {$this->i($genders['female'] ?? 0)}, other {$this->i($genders['other'] ?? 0)}

        ## Classes & sections
        {$classLines}

        ## Subjects
        {$this->listOrNone($subjects->implode(', '))}

        ## Fees collected (from students)
        - This month: {$this->money($feeMonth)}
        - Last 12 months: {$this->money($feeYear)}
        - All time: {$this->money($feeAll)} across {$feeCount} payment(s)
        - By mode: {$this->listOrNone($byMode)}

        ## Active fee structures
        {$this->listOrNone($structures)}

        ## School ledger
        - Cash in: {$this->money($ledgerIn)} | Cash out: {$this->money($ledgerOut)} | Balance: {$this->money($ledgerIn - $ledgerOut)}

        ## Attendance
        - Today ({$today}): {$attLine}

        ## Academics & operations
        - Exams created: {$exams}{$this->nextExamLine($nextExam)}
        - Announcements: {$notices} | Homework posted: {$homework}
        - Transport routes: {$routes}
        - Certificates issued: {$certs} | Transfer certificates: {$tcs}
        - Admission enquiries: {$enquiries}
        TXT;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Platform snapshot (super-admin / sub-super-admin)
    // ─────────────────────────────────────────────────────────────────────
    private function platformPack(): string
    {
        $now        = now();
        $today      = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth()->toDateString();

        $orgs     = Organization::count();
        $active   = Organization::where('status', 1)->count();
        $students = StudentDetail::count();
        $teachers = TeacherDetail::count();

        $schoolLines = Organization::orderByDesc('id')->limit(40)
            ->get(['id', 'name', 'status', 'education_board', 'state', 'serial_number'])
            ->map(fn ($o) => sprintf(
                '- %s (serial %s) — %s, %s, %s',
                $o->name,
                $o->serial_number ?: $o->id,
                $o->status ? 'active' : 'inactive',
                $o->education_board ?: 'board n/a',
                $o->state ?: 'state n/a'
            ))->implode("\n");

        $revMonth = (float) SuperAdminFeePayment::whereBetween('payment_date', [$monthStart, $today])->sum('amount');
        $revAll   = (float) SuperAdminFeePayment::sum('amount');
        $revCount = SuperAdminFeePayment::count();

        $credits = CreditQuery::selectRaw('status, COUNT(*) c, SUM(amount) total')
            ->groupBy('status')->get()
            ->map(fn ($r) => sprintf('%s: %d (%s)', $r->status ?: 'unset', (int) $r->c, $this->money($r->total)))
            ->implode('; ');

        $product = $this->productPack();

        return <<<TXT
        {$product}

        # Live snapshot — SuperLMS platform
        (generated {$now->format('d M Y, H:i')} IST)

        ## Platform
        - Schools (organizations): {$orgs} — active {$active}, inactive {$this->i($orgs - $active)}
        - Students across all schools: {$students}
        - Teachers across all schools: {$teachers}

        ## Platform fees charged to schools
        - This month: {$this->money($revMonth)}
        - All time: {$this->money($revAll)} across {$revCount} payment(s)

        ## Credit requests from schools
        - {$this->listOrNone($credits)}

        ## Schools (most recent first, up to 40)
        {$this->listOrNone($schoolLines)}
        TXT;
    }

    // ── formatting helpers ───────────────────────────────────────────────

    private function orgName(?Organization $org): string
    {
        return $org?->name ?: 'this school';
    }

    private function v(?string $value): string
    {
        return filled($value) ? $value : 'not set';
    }

    private function i(mixed $value): int
    {
        return (int) $value;
    }

    private function money(mixed $amount): string
    {
        return 'Rs ' . number_format((float) $amount, 2);
    }

    private function listOrNone(string $text): string
    {
        return trim($text) !== '' ? $text : 'none recorded';
    }

    private function nextExamLine(?Exam $exam): string
    {
        return $exam
            ? sprintf(' | next: %s on %s', $exam->exam_name, (string) $exam->start_date)
            : '';
    }
}
