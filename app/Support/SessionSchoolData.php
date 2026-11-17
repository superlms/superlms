<?php

namespace App\Support;

use App\Models\AcademicSession;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * One school's data in one academic session (Super Admin → Session → a
 * session's schools → a school's ›): who was on its roll by the session's
 * last day — students, teachers and staff added on or before it (a row
 * with no date counts as always there) — and what happened in it: the
 * admissions, the fee that came in and the attendance marked between its
 * first and last day.
 */
class SessionSchoolData
{
    public const TABS = [
        'students'   => 'Students',
        'teachers'   => 'Teachers',
        'staff'      => 'Staff',
        'classes'    => 'Classes',
        'fees'       => 'Fees',
        'attendance' => 'Attendance',
    ];

    public const PAGE = 'rowsPage';

    private const PER_PAGE = 20;

    public readonly Carbon $start;
    public readonly Carbon $end;

    public function __construct(public readonly int $org, public readonly AcademicSession $session)
    {
        $this->start = $session->starts_on->copy()->startOfDay();
        $this->end   = $session->ends_on->copy()->endOfDay();
    }

    // ─── Who was on the roll by the session's end ────────────────────────────

    /** Rows added on or before the session's last day (or with no date). */
    private function byEnd($query)
    {
        $col = $query->getModel()->getTable() . '.created_at';

        return $query->where(fn ($q) => $q->whereNull($col)->orWhere($col, '<=', $this->end));
    }

    private function inSession($query, string $column)
    {
        return $query->whereDate($column, '>=', $this->start->toDateString())
            ->whereDate($column, '<=', $this->end->toDateString());
    }

    public function students()
    {
        return $this->byEnd(StudentDetail::where('organization_id', $this->org));
    }

    public function teachers()
    {
        return $this->byEnd(TeacherDetail::where('organization_id', $this->org));
    }

    /** Staff besides teachers (teachers are on Teachers). */
    public function staff()
    {
        return $this->byEnd(AdminEmployee::where('organization_id', $this->org)
            ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', 'teacher')));
    }

    public function payments()
    {
        return $this->inSession(FeePayment::where('organization_id', $this->org), 'payment_date');
    }

    public function isNew($createdAt): bool
    {
        return $createdAt && Carbon::parse($createdAt)->between($this->start, $this->end);
    }

    // ─── The figures ─────────────────────────────────────────────────────────

    public function summary(): array
    {
        $students = $this->students();
        $teachers = $this->teachers();

        $attendance = $this->attendanceByMonth();
        $marked  = array_sum(array_column($attendance, 'marked'));
        $present = array_sum(array_column($attendance, 'present'));

        $standardIds = Standard::where('organization_id', $this->org)->pluck('id');

        [$academic, $transport] = $this->collected();
        $months = $this->months();

        $admitted = (clone $students)->whereBetween('student_details.created_at', [$this->start, $this->end])->pluck('created_at');
        $joined   = (clone $teachers)->whereBetween('teacher_details.created_at', [$this->start, $this->end])->pluck('created_at');
        $fees     = $this->payments()->get(['payment_date', 'amount']);

        $bucket = fn ($dates, ?callable $value = null) => array_map(
            fn ($m) => $dates->filter(fn ($row) => Carbon::parse($value ? $row->payment_date : $row)->format('Y-m') === $m['key'])
                ->sum(fn ($row) => $value ? $value($row) : 1),
            $months,
        );

        $byMonth = collect($attendance)->keyBy('key');

        return [
            'students'       => (clone $students)->count(),
            'activeStudents' => (clone $students)->whereHas('user', fn ($q) => $q->where('is_active', 1))->count(),
            'newStudents'    => $admitted->count(),
            'teachers'       => (clone $teachers)->count(),
            'activeTeachers' => (clone $teachers)->whereHas('user', fn ($q) => $q->where('is_active', 1))->count(),
            'newTeachers'    => $joined->count(),
            'staff'          => $this->staff()->count(),
            'classes'        => $standardIds->count(),
            'sections'       => Section::whereIn('standard_id', $standardIds)->count(),
            'daysMarked'     => array_sum(array_column($attendance, 'days')),
            'marked'         => $marked,
            'present'        => $present,
            'presentPct'     => $marked > 0 ? round($present / $marked * 100, 1) : null,
            'academic'       => $academic,
            'transport'      => $transport,
            'collected'      => $academic + $transport,
            'payments'       => $fees->count(),
            'months'         => array_column($months, 'label'),
            'admissions'     => $bucket($admitted),
            'teacherJoins'   => $bucket($joined),
            'feeByMonth'     => $bucket($fees, fn ($p) => (float) $p->amount),
            'attendancePct'  => array_map(fn ($m) => (float) ($byMonth[$m['key']]['pct'] ?? 0), $months),
        ];
    }

    /**
     * [academic, bus] fee that came in during the session — a school on
     * Custom Fee counts its own payments, as its Analytics does.
     */
    private function collected(): array
    {
        $from = $this->start->toDateString();
        $to   = $this->end->toDateString();

        if (CustomFee::enabled($this->org)) {
            return [CustomFee::collected($this->org, $from, $to), 0.0];
        }

        $academic  = (float) $this->payments()->sum('amount');
        $transport = rescue(fn () => (float) $this->inSession(TransportFeePayment::where('organization_id', $this->org), 'payment_date')->sum('amount'), 0.0, false);

        return [round($academic, 2), round($transport, 2)];
    }

    /**
     * The session's months up to this one (all of them once it is over), for
     * the cards' sparklines — at least two. ['key' => '2026-04', 'label' => 'Apr 2026']
     */
    private function months(): array
    {
        $last = $this->end->copy()->min(now()->endOfMonth());
        if ($last->lt($this->start->copy()->addMonthNoOverflow()->startOfMonth())) {
            $last = $this->start->copy()->addMonthNoOverflow();
        }

        $out    = [];
        $cursor = $this->start->copy()->startOfMonth();
        while ($cursor->lte($last) && count($out) < 36) {
            $out[]  = ['key' => $cursor->format('Y-m'), 'label' => $cursor->format('M Y')];
            $cursor = $cursor->copy()->addMonthNoOverflow();
        }

        return $out;
    }

    /**
     * Attendance marked in the session, month by month:
     * [['key', 'label', 'days', 'marked', 'present', 'absent', 'pct'], …]
     */
    public function attendanceByMonth(): array
    {
        $days = $this->inSession(StudentAttendance::where('organization_id', $this->org), 'attendance_date')
            ->selectRaw('DATE(attendance_date) as d, COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $months = [];
        foreach ($days as $day) {
            $at  = Carbon::parse($day->d);
            $key = $at->format('Y-m');
            $months[$key] ??= ['key' => $key, 'label' => $at->format('F Y'), 'days' => 0, 'marked' => 0, 'present' => 0];
            $months[$key]['days']++;
            $months[$key]['marked']  += (int) $day->total;
            $months[$key]['present'] += (int) $day->present;
        }

        return array_values(array_map(fn ($m) => $m + [
            'absent' => $m['marked'] - $m['present'],
            'pct'    => $m['marked'] > 0 ? round($m['present'] / $m['marked'] * 100, 1) : 0.0,
        ], $months));
    }

    // ─── The lists ───────────────────────────────────────────────────────────

    public function rows(string $tab, string $search)
    {
        $term = trim($search);
        $like = '%' . $term . '%';

        return match ($tab) {
            'students' => $this->students()
                ->with(['user:id,email,is_active', 'standard:id,name', 'section:id,name'])
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('full_name', 'like', $like)
                    ->orWhere('admission_no', 'like', $like)
                    ->orWhere('roll_no', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('father_name', 'like', $like)))
                ->tap(fn ($q) => NameOrder::students($q))
                ->paginate(self::PER_PAGE, ['*'], self::PAGE),

            'teachers' => $this->teachers()
                ->with('user:id,name,email,mobile_number,is_active')
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('phone', 'like', $like)
                    ->orWhere('employee_id', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like))))
                ->tap(fn ($q) => NameOrder::teachers($q))
                ->paginate(self::PER_PAGE, ['*'], self::PAGE),

            'staff' => $this->staff()
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('name', 'like', $like)
                    ->orWhere('designation', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('email', 'like', $like)))
                ->orderBy('name')
                ->paginate(self::PER_PAGE, ['*'], self::PAGE),

            'fees' => $this->payments()
                ->with(['studentDetail:id,full_name,admission_no', 'standard:id,name', 'section:id,name'])
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('receipt_number', 'like', $like)
                    ->orWhereHas('studentDetail', fn ($s) => $s->where('full_name', 'like', $like)->orWhere('admission_no', 'like', $like))))
                ->orderByDesc('payment_date')->orderByDesc('id')
                ->paginate(self::PER_PAGE, ['*'], self::PAGE),

            default => null,
        };
    }

    /** Classes in class order, each section with its students on the roll by the session's end. */
    public function classes(string $search = ''): array
    {
        $counts = $this->students()
            ->selectRaw('standard_id, section_id, COUNT(*) as c')
            ->groupBy('standard_id', 'section_id')
            ->get()
            ->groupBy('standard_id');

        $term = mb_strtolower(trim($search));

        return Standard::with(['sections' => fn ($q) => $q->orderBy('name')])
            ->where('organization_id', $this->org)
            ->inClassOrder()
            ->get()
            ->filter(fn ($s) => $term === '' || str_contains(mb_strtolower($s->name), $term))
            ->map(function ($standard) use ($counts) {
                $bySection = ($counts[$standard->id] ?? collect())->keyBy(fn ($r) => (int) $r->section_id);

                return [
                    'name'     => $standard->name,
                    'board'    => $standard->board,
                    'sections' => $standard->sections->map(fn ($sec) => [
                        'name'     => $sec->name,
                        'students' => (int) ($bySection[(int) $sec->id]->c ?? 0),
                    ])->values()->all(),
                    'students' => (int) ($counts[$standard->id] ?? collect())->sum('c'),
                ];
            })
            ->values()
            ->all();
    }

    // ─── Excel ───────────────────────────────────────────────────────────────

    /** [$headings, $rows] of a tab, the whole list (not one page). */
    public function sheet(string $tab): array
    {
        $date   = fn ($d) => $d ? Carbon::parse($d)->format('d-m-Y') : '-';
        $status = fn ($on) => $on ? 'Active' : 'Inactive';

        $rows = match ($tab) {
            'students' => $this->students()->with(['user:id,is_active', 'standard:id,name', 'section:id,name'])
                ->tap(fn ($q) => NameOrder::students($q))->get()->values()
                ->map(fn ($s, $i) => [
                    'S.No'          => $i + 1,
                    'Name'          => $s->full_name ?: '-',
                    'Admission No.' => $s->admission_no ?: '-',
                    'Class'         => trim(($s->standard?->name ?? '') . ($s->section ? ' - ' . $s->section->name : '')) ?: '-',
                    'Roll No.'      => $s->roll_no ?: '-',
                    'Father'        => $s->father_name ?: '-',
                    'Phone'         => $s->phone ?: '-',
                    'Added On'      => $date($s->created_at),
                    'Status'        => $status($s->user?->is_active),
                ])->all(),

            'teachers' => $this->teachers()->with('user:id,name,email,mobile_number,is_active')
                ->tap(fn ($q) => NameOrder::teachers($q))->get()->values()
                ->map(fn ($t, $i) => [
                    'S.No'          => $i + 1,
                    'Name'          => $t->user?->name ?? '-',
                    'Email'         => $t->user?->email ?: '-',
                    'Employee ID'   => $t->employee_id ?: '-',
                    'Phone'         => $t->phone ?: ($t->user?->mobile_number ?: '-'),
                    'Qualification' => $t->qualification ?: '-',
                    'Joined'        => $date($t->date_of_joining),
                    'Status'        => $status($t->user?->is_active),
                ])->all(),

            'staff' => $this->staff()->orderBy('name')->get()->values()
                ->map(fn ($e, $i) => [
                    'S.No'         => $i + 1,
                    'Name'         => $e->name,
                    'Type'         => Str::headline((string) $e->type) ?: '-',
                    'Designation'  => $e->designation ?: '-',
                    'Mobile'       => $e->mobile ?: '-',
                    'Email'        => $e->email ?: '-',
                    'Salary'       => (float) $e->salary,
                    'Joining Date' => $date($e->joining_date),
                    'Status'       => $status($e->is_active),
                ])->all(),

            'classes' => collect($this->classes())->flatMap(fn ($class) => array_map(
                fn ($section) => ['Class' => $class['name'], 'Section' => $section['name'], 'Students' => $section['students']],
                $class['sections'] ?: [['name' => '-', 'students' => $class['students']]],
            ))->values()->all(),

            'fees' => $this->payments()->with(['studentDetail:id,full_name,admission_no', 'standard:id,name', 'section:id,name'])
                ->orderByDesc('payment_date')->orderByDesc('id')->get()->values()
                ->map(fn ($p, $i) => [
                    'S.No'          => $i + 1,
                    'Date'          => $date($p->payment_date),
                    'Receipt No.'   => $p->receipt_number ?: '-',
                    'Student'       => $p->studentDetail?->full_name ?? '-',
                    'Admission No.' => $p->studentDetail?->admission_no ?? '-',
                    'Class'         => trim(($p->standard?->name ?? '') . ' ' . ($p->section?->name ?? '')) ?: '-',
                    'Fee'           => Str::headline((string) $p->fee_type) ?: '-',
                    'Mode'          => Str::headline((string) $p->payment_mode) ?: '-',
                    'Amount'        => (float) $p->amount,
                ])->all(),

            'attendance' => array_map(fn ($m) => [
                'Month'        => $m['label'],
                'Days Marked'  => $m['days'],
                'Marked'       => $m['marked'],
                'Present'      => $m['present'],
                'Absent'       => $m['absent'],
                'Present %'    => $m['pct'],
            ], $this->attendanceByMonth()),

            default => [],
        };

        $headings = [
            'students'   => ['S.No', 'Name', 'Admission No.', 'Class', 'Roll No.', 'Father', 'Phone', 'Added On', 'Status'],
            'teachers'   => ['S.No', 'Name', 'Email', 'Employee ID', 'Phone', 'Qualification', 'Joined', 'Status'],
            'staff'      => ['S.No', 'Name', 'Type', 'Designation', 'Mobile', 'Email', 'Salary', 'Joining Date', 'Status'],
            'classes'    => ['Class', 'Section', 'Students'],
            'fees'       => ['S.No', 'Date', 'Receipt No.', 'Student', 'Admission No.', 'Class', 'Fee', 'Mode', 'Amount'],
            'attendance' => ['Month', 'Days Marked', 'Marked', 'Present', 'Absent', 'Present %'],
        ][$tab] ?? ['S.No'];

        return [$headings, $rows];
    }

    /** "adarsh-public-school-2026-27-students-2026-11-17.xlsx" */
    public function filename(string $schoolName, string $tab): string
    {
        $session = Str::slug(str_replace(['–', '—'], '-', $this->session->name));

        return Str::slug($schoolName) . '-' . $session . '-' . $tab . '-' . now()->format('Y-m-d') . '.xlsx';
    }
}
