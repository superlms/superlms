<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\AdmissionEnquiry;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\ExamSubjectMark;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\HomeWork;
use App\Models\Admin\HomeWorkCompletion;
use App\Models\Admin\LedgerTransaction;
use App\Models\Admin\RateLms;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * School-admin entry point for the mobile app (Phase 0).
 *
 * Admins are Users with role 'admin' or 'sub-admin', scoped to an organization.
 * Login is email + password (mirrors the web checks, minus the web OTP step).
 */
class AdminController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    /**
     * POST /api/v1/admin/login
     */
    public function login(Request $request)
    {
        if ($err = $this->validateWith($request, [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ])) return $err;

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid email or password.', 401);
        }

        if (!in_array($user->role, self::ADMIN_ROLES, true)) {
            return $this->error('You do not have school admin access.', 403);
        }

        if (!$user->organization_id) {
            return $this->error('No organization assigned to this account.', 403);
        }

        if ($user->role === 'sub-admin' && !$user->is_active) {
            return $this->error('Your account is inactive. Please contact the administrator.', 403);
        }

        $token = $user->createToken('admin_token')->plainTextToken;
        $parts = explode('|', $token);

        return $this->success([
            'user'       => $this->profile($user),
            'token'      => end($parts),
            'token_type' => 'Bearer',
        ], 'Login successful.');
    }

    /**
     * GET /api/v1/admin/me
     */
    public function me()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        return $this->success($this->profile($user), 'Admin profile fetched.');
    }

    /**
     * GET /api/v1/admin/dashboard
     *
     * Headline counts for the admin home (Phase 0 shell).
     */
    public function dashboard()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $orgId = $user->organization_id;
        $now   = now();

        $feeMonth = (float) FeePayment::where('organization_id', $orgId)
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->sum('amount');

        return $this->success([
            'students'                  => StudentDetail::where('organization_id', $orgId)->count(),
            'teachers'                  => TeacherDetail::where('organization_id', $orgId)->count(),
            'fees_collected_total'      => (float) FeePayment::where('organization_id', $orgId)->sum('amount'),
            'fees_collected_this_month' => round($feeMonth, 2),
        ], 'Admin dashboard fetched.');
    }

    /**
     * GET /admin/analytics
     *
     * Mirrors app/Livewire/Admin/Analytics.php for the mobile app: headline
     * stats, student/teacher attendance (academic-year monthly bars + last-N-day
     * pies), top students, class distribution, fee summary and recent activity.
     */
    public function analytics(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $orgId = $user->organization_id;
        $today = Carbon::today();
        $days  = max(1, (int) $request->input('days', 7));

        // ── Stats cards ──
        $totalStudents = StudentDetail::where('organization_id', $orgId)->count();
        $presentToday  = StudentAttendance::where('organization_id', $orgId)->whereDate('attendance_date', $today)->where('status', 1)->count();
        $absentToday   = StudentAttendance::where('organization_id', $orgId)->whereDate('attendance_date', $today)->where('status', 0)->count();
        $newAdmissions = StudentDetail::where('organization_id', $orgId)->where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $teachers      = TeacherDetail::where('organization_id', $orgId)->count();

        // ── Academic-year months (Apr–Mar) ──
        $yearStart = $today->month >= 4 ? Carbon::create($today->year, 4, 1) : Carbon::create($today->year - 1, 4, 1);
        $months = []; $sPresent = []; $sAbsent = []; $tPresent = []; $tAbsent = [];
        for ($i = 0; $i < 12; $i++) {
            $mStart = $yearStart->copy()->addMonths($i)->startOfMonth();
            $mEnd   = $yearStart->copy()->addMonths($i)->endOfMonth();
            $months[]   = $mStart->format('M');
            $sPresent[] = StudentAttendance::where('organization_id', $orgId)->whereBetween('attendance_date', [$mStart, $mEnd])->where('status', 1)->count();
            $sAbsent[]  = StudentAttendance::where('organization_id', $orgId)->whereBetween('attendance_date', [$mStart, $mEnd])->where('status', 0)->count();
            $tPresent[] = TeacherAttendance::where('organization_id', $orgId)->whereBetween('attendance_date', [$mStart, $mEnd])->where('status', 1)->count();
            $tAbsent[]  = TeacherAttendance::where('organization_id', $orgId)->whereBetween('attendance_date', [$mStart, $mEnd])->where('status', 0)->count();
        }

        // ── Pies (last N days) ──
        $from = Carbon::now()->subDays($days - 1)->startOfDay();
        $studentPie = [
            'present' => StudentAttendance::where('organization_id', $orgId)->where('attendance_date', '>=', $from)->where('status', 1)->count(),
            'absent'  => StudentAttendance::where('organization_id', $orgId)->where('attendance_date', '>=', $from)->where('status', 0)->count(),
        ];
        $teacherPie = [
            'present' => TeacherAttendance::where('organization_id', $orgId)->where('attendance_date', '>=', $from)->where('status', 1)->count(),
            'absent'  => TeacherAttendance::where('organization_id', $orgId)->where('attendance_date', '>=', $from)->where('status', 0)->count(),
        ];

        // ── Top 3 students by attendance % ──
        $topStudents = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $orgId)
            ->withCount([
                'studentAttendances as present_count' => fn ($q) => $q->where('status', 1),
                'studentAttendances as total_count',
            ])
            ->orderByDesc('present_count')->take(3)->get()
            ->map(fn ($sd, $i) => [
                'rank'    => $i + 1,
                'name'    => $sd->user?->name ?? $sd->full_name ?? 'N/A',
                'class'   => $sd->standard?->name ?? '—',
                'section' => $sd->section?->name ?? '—',
                'score'   => $sd->total_count > 0 ? round(($sd->present_count / $sd->total_count) * 100, 1) : 0,
            ])->values()->toArray();

        // ── Class distribution (today) ──
        $cd = ['labels' => [], 'present' => [], 'absent' => []];
        foreach (Standard::where('organization_id', $orgId)->orderBy('id')->get() as $std) {
            $ids = StudentDetail::where('organization_id', $orgId)->where('standard_id', $std->id)->pluck('id');
            $cd['labels'][]  = $std->name;
            $cd['present'][] = StudentAttendance::whereIn('student_detail_id', $ids)->whereDate('attendance_date', $today)->where('status', 1)->count();
            $cd['absent'][]  = StudentAttendance::whereIn('student_detail_id', $ids)->whereDate('attendance_date', $today)->where('status', 0)->count();
        }

        // ── Fee summary ──
        $totalFee  = (float) FeeStructure::where('organization_id', $orgId)->where('is_active', true)->sum('amount');
        $collected = (float) FeePayment::where('organization_id', $orgId)->sum('amount');
        $fee = ['total' => $totalFee, 'collected' => $collected, 'remaining' => max(0, $totalFee - $collected)];

        // ── Recent activities ──
        $activities = [];
        foreach (StudentDetail::with('user')->where('organization_id', $orgId)->latest()->take(3)->get() as $sd) {
            $activities[] = ['title' => 'New Admission', 'description' => ($sd->user?->name ?? $sd->full_name ?? 'Student') . ' enrolled', 'time' => $sd->created_at?->diffForHumans(), 'color' => '#22C55E', 'ts' => $sd->created_at?->timestamp ?? 0];
        }
        foreach (FeePayment::with('studentDetail.user')->where('organization_id', $orgId)->latest()->take(3)->get() as $f) {
            $name = $f->studentDetail?->user?->name ?? 'Student';
            $activities[] = ['title' => 'Fee Paid', 'description' => '₹' . number_format($f->amount ?? 0) . " from {$name}", 'time' => $f->created_at?->diffForHumans(), 'color' => '#0EA5E9', 'ts' => $f->created_at?->timestamp ?? 0];
        }
        usort($activities, fn ($a, $b) => $b['ts'] - $a['ts']);
        $activities = array_map(fn ($a) => ['title' => $a['title'], 'description' => $a['description'], 'time' => $a['time'], 'color' => $a['color']], array_slice($activities, 0, 6));

        // ── School structure (classes / sections / subjects) ──
        $structure = [
            'classes'  => Standard::where('organization_id', $orgId)->count(),
            'sections' => Section::where('organization_id', $orgId)->count(),
            'subjects' => Subject::where('organization_id', $orgId)->count(),
        ];

        // ── Homework (last 60 days): assigned vs submitted vs pending ──
        $homeworks     = HomeWork::where('organization_id', $orgId)
            ->where('created_at', '>=', Carbon::now()->subDays(60))
            ->get(['id', 'section_id']);
        $sectionCounts = StudentDetail::where('organization_id', $orgId)
            ->selectRaw('section_id, COUNT(*) as c')->groupBy('section_id')->pluck('c', 'section_id');
        $hwExpected = 0;
        foreach ($homeworks as $hw) {
            $hwExpected += (int) ($sectionCounts[$hw->section_id] ?? 0);
        }
        $hwSubmitted = HomeWorkCompletion::whereIn('home_work_id', $homeworks->pluck('id'))->count();
        $homework = [
            'total'     => $homeworks->count(),
            'submitted' => $hwSubmitted,
            'pending'   => max(0, $hwExpected - $hwSubmitted),
        ];

        // ── Ledger (credit / expense / balance) ──
        $ledgerCredit  = (float) LedgerTransaction::forOrg($orgId)->credit()->sum('amount');
        $ledgerExpense = (float) LedgerTransaction::forOrg($orgId)->expense()->sum('amount');
        $ledger = ['credit' => $ledgerCredit, 'expense' => $ledgerExpense, 'balance' => $ledgerCredit - $ledgerExpense];

        // ── Enquiries (admission pipeline) ──
        $enqTotal    = AdmissionEnquiry::where('organization_id', $orgId)->count();
        $enqPending  = AdmissionEnquiry::where('organization_id', $orgId)->pending()->count();
        $enqAdmitted = AdmissionEnquiry::where('organization_id', $orgId)->admitted()->count();
        $enquiries = [
            'total'    => $enqTotal,
            'pending'  => $enqPending,
            'admitted' => $enqAdmitted,
            'other'    => max(0, $enqTotal - $enqPending - $enqAdmitted),
        ];

        // ── Exam performance (percentage distribution) ──
        $marks   = ExamSubjectMark::where('organization_id', $orgId)->whereNotNull('percentage')->pluck('percentage');
        $buckets = ['90–100' => 0, '75–89' => 0, '60–74' => 0, '40–59' => 0, '<40' => 0];
        foreach ($marks as $p) {
            if ($p >= 90)      $buckets['90–100']++;
            elseif ($p >= 75)  $buckets['75–89']++;
            elseif ($p >= 60)  $buckets['60–74']++;
            elseif ($p >= 40)  $buckets['40–59']++;
            else               $buckets['<40']++;
        }
        $performance = [
            'avg'     => $marks->count() ? round($marks->avg(), 1) : 0,
            'graded'  => $marks->count(),
            'buckets' => ['labels' => array_keys($buckets), 'values' => array_values($buckets)],
        ];

        return $this->success([
            'days'               => $days,
            'stats'              => compact('totalStudents', 'presentToday', 'absentToday', 'newAdmissions', 'teachers'),
            'student_monthly'    => ['months' => $months, 'present' => $sPresent, 'absent' => $sAbsent],
            'teacher_monthly'    => ['months' => $months, 'present' => $tPresent, 'absent' => $tAbsent],
            'student_pie'        => $studentPie,
            'teacher_pie'        => $teacherPie,
            'top_students'       => $topStudents,
            'class_distribution' => $cd,
            'fee'                => $fee,
            'structure'          => $structure,
            'homework'           => $homework,
            'ledger'             => $ledger,
            'enquiries'          => $enquiries,
            'performance'        => $performance,
            'recent_activities'  => $activities,
        ], 'Admin analytics fetched.');
    }

    /**
     * GET /admin/admissions — admission-enquiry pipeline (More → Admissions).
     */
    public function admissions(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $orgId = $user->organization_id;
        $q = AdmissionEnquiry::with('standard')->where('organization_id', $orgId);
        if ($status = $request->input('status')) $q->where('status', $status);
        if ($search = trim((string) $request->input('search', ''))) {
            $q->where(fn ($w) => $w->where('student_name', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $rows = $q->latest()->take(300)->get()->map(fn ($e) => [
            'id'               => $e->id,
            'student_name'     => $e->student_name,
            'email'            => $e->email,
            'mobile'           => $e->mobile,
            'guardian_name'    => $e->guardian_name,
            'address'          => $e->address,
            'class'            => $e->standard?->name,
            'stream'           => $e->stream,
            'admission_fee'    => (float) $e->admission_fee,
            'collected_amount' => (float) $e->collected_amount,
            'total_marks'      => $e->total_marks !== null ? (float) $e->total_marks : null,
            'obtained_marks'   => $e->obtained_marks !== null ? (float) $e->obtained_marks : null,
            'remarks'          => $e->remarks,
            'status'           => $e->status,
            'created_at'       => $e->created_at?->toIso8601String(),
        ])->values();

        return $this->success([
            'admissions' => $rows,
            'stats'      => [
                'total'    => AdmissionEnquiry::where('organization_id', $orgId)->count(),
                'pending'  => AdmissionEnquiry::where('organization_id', $orgId)->pending()->count(),
                'admitted' => AdmissionEnquiry::where('organization_id', $orgId)->admitted()->count(),
            ],
        ], 'Admissions fetched.');
    }

    /**
     * GET /admin/users — organization staff users (More → Users).
     */
    public function users(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $orgId = $user->organization_id;
        $q = User::where('organization_id', $orgId)->whereIn('role', ['admin', 'sub-admin']);
        if (($st = $request->input('status')) !== null && $st !== '') $q->where('is_active', (int) $st);
        if ($search = trim((string) $request->input('search', ''))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('mobile_number', 'like', "%{$search}%"));
        }

        $rows = $q->orderBy('name')->take(300)->get()->map(fn ($u) => [
            'id'              => $u->id,
            'name'            => $u->name,
            'email'           => $u->email,
            'mobile'          => $u->mobile_number,
            'role'            => $u->role,
            'gender'          => $u->gender,
            'is_active'       => (bool) $u->is_active,
            'image'           => $u->image,
            'date_of_joining' => $u->date_of_joining,
        ])->values();

        return $this->success([
            'users' => $rows,
            'stats' => [
                'total'  => User::where('organization_id', $orgId)->whereIn('role', ['admin', 'sub-admin'])->count(),
                'admins' => User::where('organization_id', $orgId)->where('role', 'admin')->count(),
                'active' => User::where('organization_id', $orgId)->whereIn('role', ['admin', 'sub-admin'])->where('is_active', 1)->count(),
            ],
        ], 'Users fetched.');
    }

    /**
     * GET /admin/rating — current organization LMS rating (More → Rate LMS).
     */
    public function getRating()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $r = RateLms::where('organization_id', $user->organization_id)->first();
        $feedback = $r?->feedback;
        if (is_string($feedback)) {
            $decoded = json_decode($feedback, true);
            if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) $feedback = $decoded;
        }

        return $this->success([
            'rated'        => (bool) $r,
            'rating'       => $r ? (int) $r->rating : 0,
            'feedback'     => $feedback ?? '',
            'submitted_at' => $r?->created_at?->toIso8601String(),
        ], 'Rating fetched.');
    }

    /**
     * POST /admin/rating — submit / update the organization's LMS rating.
     */
    public function submitRating(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        if ($err = $this->validateWith($request, [
            'rating'   => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['required', 'string'],
        ])) return $err;

        $r = RateLms::updateOrCreate(
            ['organization_id' => $user->organization_id],
            ['rating' => (int) $request->rating, 'feedback' => $request->feedback, 'status' => true],
        );

        return $this->success([
            'rated'        => true,
            'rating'       => (int) $r->rating,
            'feedback'     => $request->feedback,
            'submitted_at' => $r->created_at?->toIso8601String(),
        ], 'Thanks for rating!');
    }

    /** Shared exam/class/subject filter options for Performance & Exam Copy. */
    private function examFilterOptions(int $orgId): array
    {
        return [
            'exams'     => Exam::where('organization_id', $orgId)->orderByDesc('id')->get(['id', 'exam_name'])
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->exam_name])->values(),
            'standards' => Standard::where('organization_id', $orgId)->orderBy('id')->get(['id', 'name'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
            'subjects'  => Subject::where('organization_id', $orgId)->orderBy('name')->get(['id', 'name'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
        ];
    }

    /**
     * GET /admin/exam-copies — evaluated answer copies (marks + PDFs).
     */
    public function examCopies(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $orgId = $user->organization_id;
        $q = ExamCopy::with(['studentDetail.user', 'standard', 'section', 'subject', 'exam'])
            ->where('organization_id', $orgId);
        foreach (['exam_id', 'standard_id', 'section_id', 'subject_id'] as $p) {
            if ($request->filled($p)) $q->where($p, (int) $request->input($p));
        }
        if ($search = trim((string) $request->input('search', ''))) {
            $q->whereHas('studentDetail.user', fn ($w) => $w->where('name', 'like', "%{$search}%"));
        }

        $base = ExamCopy::where('organization_id', $orgId);
        foreach (['exam_id', 'standard_id', 'section_id', 'subject_id'] as $p) {
            if ($request->filled($p)) $base->where($p, (int) $request->input($p));
        }

        $rows = $q->latest()->take(300)->get()->map(fn ($c) => [
            'id'             => $c->id,
            'student'        => $c->studentDetail?->user?->name ?? $c->studentDetail?->full_name ?? '—',
            'class'          => $c->standard?->name,
            'section'        => $c->section?->name,
            'subject'        => $c->subject?->name,
            'exam'           => $c->exam?->exam_name,
            'marks_obtained' => $c->marks_obtained,
            'max_marks'      => $c->max_marks,
            'percentage'     => $c->percentage !== null ? (float) $c->percentage : null,
            'grade'          => $c->grade,
            'remarks'        => $c->remarks,
            'is_absent'      => (bool) $c->is_absent,
            'has_pdf'        => ! empty($c->pdf_path),
            'pdf_url'        => $c->pdf_path,
        ])->values();

        return $this->success([
            'copies'  => $rows,
            'stats'   => [
                'total'    => (clone $base)->count(),
                'uploaded' => (clone $base)->whereNotNull('pdf_path')->count(),
                'pending'  => (clone $base)->whereNull('pdf_path')->count(),
            ],
            'options' => $this->examFilterOptions($orgId),
        ], 'Exam copies fetched.');
    }

    /**
     * GET /admin/performance — exam performance aggregated per subject.
     */
    public function performance(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $orgId = $user->organization_id;
        $q = ExamCopy::where('organization_id', $orgId)->whereNotNull('percentage');
        foreach (['exam_id', 'standard_id', 'section_id', 'subject_id'] as $p) {
            if ($request->filled($p)) $q->where($p, (int) $request->input($p));
        }

        $overall = (clone $q)->selectRaw('COUNT(*) total, AVG(percentage) avg_pct, COUNT(DISTINCT exam_id) exams_cnt, COUNT(DISTINCT student_detail_id) students_cnt')->first();

        $subjectNames = Subject::where('organization_id', $orgId)->pluck('name', 'id');
        $bySubject = (clone $q)->selectRaw('subject_id, COUNT(*) cnt, AVG(percentage) avg_pct, MAX(percentage) max_pct, MIN(percentage) min_pct')
            ->groupBy('subject_id')->get()
            ->map(fn ($r) => [
                'subject'  => $subjectNames[$r->subject_id] ?? '—',
                'count'    => (int) $r->cnt,
                'avg'      => round((float) $r->avg_pct, 1),
                'max'      => round((float) $r->max_pct, 1),
                'min'      => round((float) $r->min_pct, 1),
            ])->sortByDesc('avg')->values();

        return $this->success([
            'stats'    => [
                'copies'   => (int) ($overall->total ?? 0),
                'avg'      => round((float) ($overall->avg_pct ?? 0), 1),
                'exams'    => (int) ($overall->exams_cnt ?? 0),
                'students' => (int) ($overall->students_cnt ?? 0),
            ],
            'subjects' => $bySubject,
            'options'  => $this->examFilterOptions($orgId),
        ], 'Performance fetched.');
    }

    private function profile(User $user): array
    {
        $org = Organization::find($user->organization_id);

        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'role'         => $user->role,
            'image'        => $user->image,
            'permissions'  => $user->apiPermissions(),
            'organization' => $org ? [
                'id'          => $org->id,
                'name'        => $org->name,
                'logo'        => $org->logo,
                'school_code' => $org->school_code,
            ] : null,
        ];
    }
}
