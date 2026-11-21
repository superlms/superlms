<?php

namespace App\Livewire\SuperAdmin;

use App\Jobs\SchoolDataMailSend;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Organization;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Support\AcademicYear;
use App\Support\SchoolFeeTotals;
use App\Support\SchoolMonthly;
use App\Support\SchoolPdf\Exporter;
use App\Support\StudentExport;
use App\Support\TeacherExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * Super Admin → School Data: one school at a glance, in the Home's analytics
 * look (2026-11-21). Pick a school and see its School Detail, then the
 * analytics of everything it keeps — students, attendance, fees, classes,
 * teachers, staff and how much each functionality is used — and then every
 * functionality of the school's panel with an Export PDF of all its records
 * (App\Support\SchoolPdf\Exporter), two to a row. Send to Mail sends all
 * those PDFs to the school's registered email; the header's Excel menu
 * downloads the students, teachers, staff, classes or fees as a sheet.
 *
 * No lists of records on the page itself (the user had them taken off).
 *
 * A sub super-admin limited to one school sees only that school.
 */
class SchoolData extends Component
{
    use WireUiActions;

    /** The lists the header's Excel menu downloads. */
    public const EXPORTS = [
        'students' => 'Students',
        'teachers' => 'Teachers',
        'staff'    => 'Staff',
        'classes'  => 'Classes',
        'fees'     => 'Fees',
    ];

    /** Months on the charts and the KPI cards' sparklines, this one included. */
    private const MONTHS = 12;

    /** Teal shades for a donut's parts (the Home's look), then a grey. */
    private const SHADES = ['#0f766e', '#0d9488', '#2fae9f', '#6cc9bb', '#a7e3d8', '#d5f2ec'];

    #[Url(except: null)]
    public ?int $school = null;

    public function mount(): void
    {
        if ($locked = $this->lockedSchool()) {
            $this->school = $locked;
        } elseif (!$this->school || !Organization::whereKey($this->school)->exists()) {
            $this->school = Organization::orderBy('name')->value('id');
        }
    }

    public function updatedSchool(): void
    {
        if ($locked = $this->lockedSchool()) {
            $this->school = $locked;
        } elseif (!$this->school || !Organization::whereKey($this->school)->exists()) {
            $this->school = Organization::orderBy('name')->value('id');
        }
    }

    /** One list as an Excel file — the whole school's. */
    public function export(string $list = 'students')
    {
        $org = $this->org();
        if (!$org || !array_key_exists($list, self::EXPORTS)) {
            return null;
        }

        try {
            [$headings, $rows] = match ($list) {
                'students' => array_slice(StudentExport::data($org->id), 0, 2),
                'teachers' => array_slice(TeacherExport::data($org->id), 0, 2),
                'staff'    => $this->staffSheet($org->id),
                'classes'  => $this->classesSheet($org->id),
                'fees'     => $this->feesSheet($org->id),
            };
            $bytes = StudentExport::xlsx($headings, $rows);
        } catch (\Throwable $e) {
            report($e);
            $this->notification()->error('Export failed', 'The Excel file could not be made. Please try again.');
            return null;
        }

        $name = Str::slug($org->name) . '-' . $list . '-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(fn () => print($bytes), $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** One functionality's PDF — everything the school keeps there (App\Support\SchoolPdf). */
    public function exportPdf(string $key)
    {
        $org = $this->org();
        if (!$org || !Exporter::exists($key)) {
            return null;
        }

        try {
            $bytes = Exporter::pdf($org->id, $key);
        } catch (\Throwable $e) {
            report($e);
            $this->notification()->error('Export failed', 'The ' . Exporter::label($key) . ' PDF could not be made. Please try again.');

            return null;
        }

        return response()->streamDownload(fn () => print($bytes), Exporter::filename($org, $key), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Every functionality's PDF to the school's registered email, made and
     * sent in the background (App\Jobs\SchoolDataMailSend).
     */
    public function sendToMail(): void
    {
        $org = $this->org();
        if (!$org) {
            return;
        }

        $to = trim((string) $org->email);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->notification()->error('No email', $org->name . ' has no registered email. Add one on Schools first.');

            return;
        }

        if (!SchoolDataMailSend::start($org, $to)) {
            $this->notification()->info('Already sending', 'The PDFs for ' . $org->name . ' are on their way to ' . $to . '.');

            return;
        }

        $this->notification()->success('Sending', 'All the PDFs will reach ' . $to . ' in a few minutes.');
    }

    // ─── The school and its figures ───────────────────────────────────────────

    private function lockedSchool(): ?int
    {
        $user = auth()->user();

        return $user && $user->isSubSuperAdmin() ? $user->allowedOrganizationId() : null;
    }

    /** The school on show — always their own for a sub super-admin limited to one. */
    private function org(): ?Organization
    {
        $id = $this->lockedSchool() ?? $this->school;

        return $id ? Organization::find($id) : null;
    }

    /**
     * The analytics of everything the school keeps: the KPI cards up top
     * (with how each moved, month by month), then a card each for the fee,
     * attendance, students, classes, teachers, staff, how fees were paid and
     * how much each functionality is used.
     */
    private function analytics(int $org, array $pdfCounts): array
    {
        $monthly = SchoolMonthly::forSchool($org, self::MONTHS);

        return [
            'months'     => $monthly['labels'],
            'students'   => $this->studentFigures($org, $monthly['students']),
            'teachers'   => $this->teacherFigures($org, $monthly['teachers']),
            'staff'      => $this->staffFigures($org),
            'classes'    => $this->classFigures($org),
            'attendance' => $this->attendanceFigures($org),
            'fee'        => SchoolFeeTotals::summary($org) + [
                'byMonth'   => $monthly['fee'],
                'thisMonth' => (float) (end($monthly['fee']) ?: 0),
                'modes'     => $this->feeModes($org),
            ],
            'usage'      => $this->usage($pdfCounts),
        ];
    }

    /** A figure that needs a table or column some schools' databases may lack. */
    private function safely(callable $figure, $fallback = null)
    {
        try {
            return $figure();
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    private function studentFigures(int $org, array $added): array
    {
        $students = StudentDetail::where('organization_id', $org);
        $total    = (clone $students)->count();
        $active   = (clone $students)->whereHas('user', fn ($q) => $q->where('is_active', 1))->count();

        $gender = $this->safely(fn () => (clone $students)
            ->selectRaw("LOWER(TRIM(COALESCE(gender, ''))) as g, COUNT(*) as c")
            ->groupBy('g')
            ->pluck('c', 'g')
            ->all(), []);
        $boys   = (int) ($gender['male'] ?? 0) + (int) ($gender['boy'] ?? 0) + (int) ($gender['m'] ?? 0);
        $girls  = (int) ($gender['female'] ?? 0) + (int) ($gender['girl'] ?? 0) + (int) ($gender['f'] ?? 0);

        return [
            'total'     => $total,
            'active'    => $active,
            'inactive'  => max(0, $total - $active),
            'added'     => $added,
            'thisMonth' => (int) (end($added) ?: 0),
            'boys'      => $boys,
            'girls'     => $girls,
            'otherGender' => max(0, $total - $boys - $girls),
            'noClass'   => (clone $students)->whereNull('standard_id')->count(),
        ];
    }

    private function teacherFigures(int $org, array $added): array
    {
        $teachers = TeacherDetail::where('organization_id', $org);
        $total    = (clone $teachers)->count();
        $active   = (clone $teachers)->whereHas('user', fn ($q) => $q->where('is_active', 1))->count();

        // Today's teacher attendance, where the school marks it.
        [$marked, $present] = $this->safely(function () use ($org) {
            $today = TeacherAttendance::where('organization_id', $org)->whereDate('attendance_date', now()->toDateString());

            return [(clone $today)->count(), (clone $today)->where('status', 1)->count()];
        }, [0, 0]);

        return [
            'total'     => $total,
            'active'    => $active,
            'inactive'  => max(0, $total - $active),
            'added'     => $added,
            'thisMonth' => (int) (end($added) ?: 0),
            'marked'    => $marked,
            'present'   => $present,
            'presentPct' => $marked > 0 ? round($present / $marked * 100, 1) : null,
        ];
    }

    /** Staff besides teachers: how many, of which kind, and their monthly salary bill. */
    private function staffFigures(int $org): array
    {
        $byType = $this->staffQuery($org)
            ->selectRaw("COALESCE(NULLIF(type, ''), 'staff') as t, COUNT(*) as c, SUM(CASE WHEN is_active = 1 THEN COALESCE(salary, 0) ELSE 0 END) as bill")
            ->groupBy('t')
            ->orderByDesc('c')
            ->get();

        return [
            'total'  => (int) $byType->sum('c'),
            'active' => $this->staffQuery($org)->where('is_active', 1)->count(),
            'bill'   => (float) $byType->sum('bill'),
            'byType' => $byType->map(fn ($r) => ['label' => Str::headline((string) $r->t), 'count' => (int) $r->c])->values()->all(),
        ];
    }

    /** Classes and sections, and the classes with the most students. */
    private function classFigures(int $org): array
    {
        $classes  = $this->classes($org);
        $sections = array_sum(array_map(fn ($c) => count($c['sections']), $classes));
        $ranked   = collect($classes)->sortByDesc('students')->filter(fn ($c) => $c['students'] > 0)->values();
        $withKids = $ranked->count();

        return [
            'classes'  => count($classes),
            'sections' => $sections,
            'average'  => $withKids > 0 ? (int) round($ranked->sum('students') / $withKids) : 0,
            'rows'     => $ranked->take(8)->map(fn ($c) => ['label' => $c['name'], 'count' => $c['students']])->all(),
            'more'     => max(0, $withKids - 8),
        ];
    }

    /** Students' attendance: today, each of the last 7 days, and this month's average. */
    private function attendanceFigures(int $org): array
    {
        $rows = StudentAttendance::where('organization_id', $org)
            ->whereDate('attendance_date', '>=', now()->startOfMonth()->min(now()->subDays(6))->toDateString())
            ->selectRaw('DATE(attendance_date) as d, COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present')
            ->groupBy('d')
            ->get()
            ->keyBy(fn ($r) => (string) $r->d);

        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $day    = now()->subDays($i);
            $row    = $rows[$day->toDateString()] ?? null;
            $week[] = [
                'label'  => $day->format('D'),
                'date'   => $day->format('d M'),
                'marked' => (int) ($row->total ?? 0),
                'pct'    => $row && $row->total > 0 ? round($row->present / $row->total * 100, 1) : null,
            ];
        }

        $todayRow = $rows[now()->toDateString()] ?? null;
        $marked   = (int) ($todayRow->total ?? 0);
        $present  = (int) ($todayRow->present ?? 0);

        $month        = $rows->filter(fn ($r, $d) => $d >= now()->startOfMonth()->toDateString());
        $monthMarked  = (int) $month->sum('total');
        $monthPresent = (int) $month->sum('present');

        return [
            'marked'    => $marked,
            'present'   => $present,
            'absent'    => max(0, $marked - $present),
            'pct'       => $marked > 0 ? round($present / $marked * 100, 1) : null,
            'week'      => $week,
            'monthPct'  => $monthMarked > 0 ? round($monthPresent / $monthMarked * 100, 1) : null,
            'monthDays' => $month->count(),
        ];
    }

    /** This session's fee payments by how they were paid (cash, UPI, online …), as donut parts. */
    private function feeModes(int $org): array
    {
        $from  = AcademicYear::start()->toDateString();
        $modes = [];
        $add   = function (string $table, array $only = []) use ($org, $from, &$modes) {
            $this->safely(function () use ($table, $only, $org, $from, &$modes) {
                DB::table($table)->where('organization_id', $org)
                    ->when($only, fn ($q) => $q->whereIn('fee_type', $only))
                    ->whereDate('payment_date', '>=', $from)
                    ->selectRaw("LOWER(COALESCE(NULLIF(payment_mode, ''), 'other')) as m, SUM(amount) as total")
                    ->groupBy('m')
                    ->get()
                    ->each(function ($r) use (&$modes) {
                        $modes[$r->m] = ($modes[$r->m] ?? 0) + (float) $r->total;
                    });
            });
        };

        if (\App\Support\CustomFee::enabled($org)) {
            $add('custom_fee_payments');
        } else {
            $add('fee_payments', ['academic', 'transport']);
            $add('transport_fee_payments');
        }

        arsort($modes);
        $modes = array_filter($modes, fn ($v) => $v > 0);

        $parts = [];
        foreach (array_slice($modes, 0, 5, true) as $mode => $amount) {
            $label   = in_array($mode, ['upi', 'neft', 'rtgs', 'imps'], true) ? strtoupper($mode) : Str::headline($mode);
            $parts[] = ['label' => $label, 'value' => $amount, 'color' => self::SHADES[count($parts)]];
        }
        $rest = array_sum(array_slice($modes, 5));
        if ($rest > 0) {
            $parts[] = ['label' => 'Others', 'value' => $rest, 'color' => '#e5e7eb'];
        }

        return $parts;
    }

    /** How many functionalities hold records, and the ones that hold the most. */
    private function usage(array $pdfCounts): array
    {
        $used = array_filter($pdfCounts, fn ($n) => $n > 0);
        arsort($used);

        return [
            'inUse' => count($used),
            'total' => count(Exporter::SECTIONS),
            'records' => array_sum($used),
            'rows'  => collect(array_slice($used, 0, 8, true))
                ->map(fn ($n, $key) => ['label' => Exporter::label($key), 'count' => $n])->values()->all(),
            'unused' => collect($pdfCounts)->filter(fn ($n) => $n <= 0)->keys()->map(fn ($k) => Exporter::label($k))->values()->all(),
        ];
    }

    private function staffQuery(int $org)
    {
        return AdminEmployee::where('organization_id', $org)
            ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', 'teacher'));
    }

    /** Classes in class order, each section with its students. */
    private function classes(int $org): array
    {
        $counts = StudentDetail::where('organization_id', $org)
            ->selectRaw('standard_id, section_id, COUNT(*) as c')
            ->groupBy('standard_id', 'section_id')
            ->get()
            ->groupBy('standard_id');

        return Standard::with(['sections' => fn ($q) => $q->orderBy('name')])
            ->where('organization_id', $org)
            ->inClassOrder()
            ->get()
            ->map(function ($standard) use ($counts) {
                $bySection = ($counts[$standard->id] ?? collect())->keyBy(fn ($r) => (int) $r->section_id);
                $sections  = $standard->sections->map(fn ($sec) => [
                    'name'     => $sec->name,
                    'students' => (int) ($bySection[(int) $sec->id]->c ?? 0),
                ])->values()->all();

                return [
                    'name'     => $standard->name,
                    'board'    => $standard->board,
                    'sections' => $sections,
                    'students' => (int) ($counts[$standard->id] ?? collect())->sum('c'),
                ];
            })
            ->values()
            ->all();
    }

    // ─── Excel sheets for the lists without an export of their own ───────────

    private function staffSheet(int $org): array
    {
        $rows = $this->staffQuery($org)->orderBy('name')->get()->values()->map(fn ($e, $i) => [
            'S.No'         => $i + 1,
            'Name'         => $e->name,
            'Type'         => Str::headline((string) $e->type) ?: '-',
            'Designation'  => $e->designation ?: '-',
            'Mobile'       => $e->mobile ?: '-',
            'Email'        => $e->email ?: '-',
            'Salary'       => (float) $e->salary,
            'Joining Date' => $e->joining_date ? Carbon::parse($e->joining_date)->format('d-m-Y') : '-',
            'Status'       => $e->is_active ? 'Active' : 'Inactive',
        ])->all();

        return [['S.No', 'Name', 'Type', 'Designation', 'Mobile', 'Email', 'Salary', 'Joining Date', 'Status'], $rows];
    }

    private function classesSheet(int $org): array
    {
        $rows = [];
        foreach ($this->classes($org) as $class) {
            foreach ($class['sections'] ?: [['name' => '-', 'students' => $class['students']]] as $section) {
                $rows[] = ['Class' => $class['name'], 'Section' => $section['name'], 'Students' => $section['students']];
            }
        }

        return [['Class', 'Section', 'Students'], $rows];
    }

    private function feesSheet(int $org): array
    {
        $rows = FeePayment::with(['studentDetail:id,full_name,admission_no', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $org)
            ->whereDate('payment_date', '>=', AcademicYear::start()->toDateString())
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->get()
            ->values()
            ->map(fn ($p, $i) => [
                'S.No'         => $i + 1,
                'Date'         => $p->payment_date ? Carbon::parse($p->payment_date)->format('d-m-Y') : '-',
                'Receipt No.'  => $p->receipt_number ?: '-',
                'Student'      => $p->studentDetail?->full_name ?? '-',
                'Admission No.' => $p->studentDetail?->admission_no ?? '-',
                'Class'        => trim(($p->standard?->name ?? '') . ' ' . ($p->section?->name ?? '')) ?: '-',
                'Fee'          => Str::headline((string) $p->fee_type) ?: '-',
                'Mode'         => Str::headline((string) $p->payment_mode) ?: '-',
                'Amount'       => (float) $p->amount,
            ])->all();

        return [['S.No', 'Date', 'Receipt No.', 'Student', 'Admission No.', 'Class', 'Fee', 'Mode', 'Amount'], $rows];
    }

    public function render()
    {
        $org       = $this->org();
        $pdfCounts = $org ? Exporter::counts($org->id) : [];

        return view('livewire.super-admin.school-data', [
            'schools'    => $this->lockedSchool() ? collect() : Organization::orderBy('name')->get(['id', 'name']),
            'locked'     => (bool) $this->lockedSchool(),
            'org'        => $org,
            'an'         => $org ? $this->analytics($org->id, $pdfCounts) : null,
            'pdfCounts'  => $pdfCounts,
            'mailStatus' => $org ? SchoolDataMailSend::status($org->id) : null,
        ]);
    }
}
