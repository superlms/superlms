<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Organization;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
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
            'recent_activities'  => $activities,
        ], 'Admin analytics fetched.');
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
            'organization' => $org ? [
                'id'          => $org->id,
                'name'        => $org->name,
                'logo'        => $org->logo,
                'school_code' => $org->school_code,
            ] : null,
        ];
    }
}
