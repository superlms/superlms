<?php

namespace App\Services\Lists;

use App\Models\Admin\Exam;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\StudentIdCard;
use App\Models\Admin\Transportation;
use App\Models\Student\AdmitCard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Support\Carbon;

/**
 * Config-driven builder for the admin "Lists" module.
 *
 * Each list type declares the filters it needs and the columns a user may pick.
 * `definitions()` drives the Livewire create-panel UI; `build()` turns a chosen
 * type + filters + column selection into a printable set of headers and rows.
 */
class ListReportService
{
    // Attendance status ints mirror App\Livewire\Admin\Attendance.
    private const ATT_PRESENT = 1;
    private const ATT_ABSENT  = 0;
    private const ATT_HALF    = 2;
    private const ATT_HOLIDAY = 3;

    /**
     * Every list type: label, icon (heroicon path), the filters it requires and
     * the columns it offers. Filters map name => 'required'|'optional'.
     */
    public static function definitions(): array
    {
        return [
            'student' => [
                'label'  => 'Students',
                'desc'   => 'Class roster with the student details you choose',
                'color'  => 'blue',
                'icon'   => 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.42a12 12 0 01.84 4.42 12 12 0 01-7 2.58 12 12 0 01-7-2.58 12 12 0 01.84-4.42L12 14z',
                'filters' => ['standard' => 'required', 'section' => 'optional'],
                'columns' => [
                    'roll_no'            => 'Roll No',
                    'full_name'          => 'Name',
                    'father_name'        => "Father's Name",
                    'mother_name'        => "Mother's Name",
                    'gender'             => 'Gender',
                    'dob'                => 'Date of Birth',
                    'admission_no'       => 'Admission No',
                    'registration_number'=> 'Registration No',
                    'date_of_admission'  => 'Admission Date',
                    'phone'              => 'Phone',
                    'email'              => 'Email',
                    'local_address'      => 'Address',
                    'city'               => 'City',
                    'state'              => 'State',
                    'board'              => 'Board',
                    'aadhar_no'          => 'Aadhaar No',
                ],
            ],
            'teacher' => [
                'label'  => 'Teachers',
                'desc'   => 'Staff directory',
                'color'  => 'indigo',
                'icon'   => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'filters' => [],
                'columns' => [
                    'employee_id'       => 'Employee ID',
                    'name'              => 'Name',
                    'email'             => 'Email',
                    'phone'             => 'Phone',
                    'qualification'     => 'Qualification',
                    'date_of_joining'   => 'Joining Date',
                    'city'              => 'City',
                    'state'             => 'State',
                    'emergency_contact' => 'Emergency Contact',
                ],
            ],
            'fee' => [
                'label'  => 'Fee Payments',
                'desc'   => 'Collected fee receipts',
                'color'  => 'emerald',
                'icon'   => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'filters' => ['standard' => 'optional', 'section' => 'optional'],
                'columns' => [
                    'receipt_number' => 'Receipt No',
                    'student'        => 'Student',
                    'class'          => 'Class',
                    'fee_type'       => 'Type',
                    'amount'         => 'Amount',
                    'payment_mode'   => 'Mode',
                    'payment_date'   => 'Date',
                    'remark'         => 'Remark',
                ],
            ],
            'transport' => [
                'label'  => 'Transport Routes',
                'desc'   => 'Bus routes with driver & timing',
                'color'  => 'amber',
                'icon'   => 'M8 7h8m-8 4h8m-8 4h4M4 5a2 2 0 012-2h12a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V5z',
                'filters' => [],
                'columns' => [
                    'route_name'      => 'Route',
                    'driver'          => 'Driver',
                    'pickup_time'     => 'Pickup Time',
                    'drop_time'       => 'Drop Time',
                    'pickup_location' => 'Pickup Point',
                    'drop_location'   => 'Drop Point',
                    'monthly_fee'     => 'Monthly Fee',
                    'capacity'        => 'Capacity',
                    'students_count'  => 'Students',
                ],
            ],
            'exam' => [
                'label'  => 'Exams',
                'desc'   => 'Configured examinations',
                'color'  => 'purple',
                'icon'   => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                'filters' => [],
                'columns' => [
                    'exam_name'     => 'Exam',
                    'exam_type'     => 'Type',
                    'term'          => 'Term',
                    'academic_year' => 'Academic Year',
                    'start_date'    => 'Start Date',
                    'end_date'      => 'End Date',
                    'total_marks'   => 'Total Marks',
                    'passing_marks' => 'Passing Marks',
                    'status'        => 'Status',
                ],
            ],
            'performance' => [
                'label'  => 'Performance',
                'desc'   => 'Exam marks by student & subject',
                'color'  => 'rose',
                'icon'   => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'filters' => ['exam' => 'required', 'standard' => 'required', 'section' => 'optional'],
                'columns' => [
                    'roll_no'        => 'Roll No',
                    'student'        => 'Student',
                    'subject'        => 'Subject',
                    'marks_obtained' => 'Marks',
                    'max_marks'      => 'Max',
                    'percentage'     => 'Percentage',
                    'grade'          => 'Grade',
                ],
            ],
            'attendance' => [
                'label'  => 'Attendance',
                'desc'   => 'Monthly attendance summary per student',
                'color'  => 'teal',
                'icon'   => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                'filters' => ['standard' => 'required', 'section' => 'optional', 'month' => 'required'],
                'columns' => [
                    'roll_no'    => 'Roll No',
                    'student'    => 'Student',
                    'present'    => 'Present',
                    'absent'     => 'Absent',
                    'half_day'   => 'Half Day',
                    'working'    => 'Working Days',
                    'percentage' => 'Percentage',
                ],
            ],
            'id_card' => [
                'label'  => 'ID Cards',
                'desc'   => 'Issued student ID cards',
                'color'  => 'cyan',
                'icon'   => 'M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5zM8 11a3 3 0 100-6 3 3 0 000 6zm10 0h-4m4 4h-4m-6 4a4 4 0 018 0',
                'filters' => ['standard' => 'optional', 'section' => 'optional'],
                'columns' => [
                    'card_number' => 'Card No',
                    'student'     => 'Student',
                    'class'       => 'Class',
                    'issue_date'  => 'Issued',
                    'expiry_date' => 'Expires',
                    'status'      => 'Status',
                ],
            ],
            'admit_card' => [
                'label'  => 'Admit Cards',
                'desc'   => 'Issued exam admit cards',
                'color'  => 'orange',
                'icon'   => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
                'filters' => ['exam' => 'optional', 'standard' => 'optional', 'section' => 'optional'],
                'columns' => [
                    'admit_card_number' => 'Admit Card No',
                    'student_name'      => 'Student',
                    'roll_number'       => 'Roll No',
                    'exam_name'         => 'Exam',
                    'class'             => 'Class',
                    'seat_number'       => 'Seat',
                    'room_number'       => 'Room',
                    'status'            => 'Status',
                ],
            ],
        ];
    }

    /**
     * Build a printable report.
     *
     * @param  array  $p  standard_id, section_id, exam_id, month, columns[]
     * @return array{columns: array<string>, rows: array<array<string>>, count: int}
     */
    public function build(string $type, int $orgId, array $p): array
    {
        $defs = self::definitions();
        $def  = $defs[$type] ?? null;
        if (!$def) {
            return ['columns' => [], 'rows' => [], 'count' => 0];
        }

        // Keep the type's canonical column order; only the ones the user picked.
        $picked = array_values(array_intersect(array_keys($def['columns']), $p['columns'] ?? []));

        [$records, $resolvers] = $this->fetch($type, $orgId, $p);

        $columns = array_map(fn ($k) => $def['columns'][$k], $picked);
        $rows = [];
        foreach ($records as $rec) {
            $row = [];
            foreach ($picked as $key) {
                $val = ($resolvers[$key] ?? fn () => '')($rec);
                $row[] = ($val === null || $val === '') ? '' : (string) $val;
            }
            $rows[] = $row;
        }

        return ['columns' => $columns, 'rows' => $rows, 'count' => count($rows)];
    }

    /**
     * Returns [iterable $records, array<string, callable> $resolvers] for a type.
     */
    private function fetch(string $type, int $orgId, array $p): array
    {
        $stdId  = $p['standard_id'] ?? null;
        $secId  = $p['section_id'] ?? null;
        $examId = $p['exam_id'] ?? null;

        $fmtDate = fn ($d) => $d ? Carbon::parse($d)->format('d M Y') : '';
        $fmtTime = fn ($t) => $t ? Carbon::parse($t)->format('h:i A') : '';

        switch ($type) {
            case 'student':
                $records = StudentDetail::with(['standard:id,name', 'section:id,name'])
                    ->where('organization_id', $orgId)
                    ->when($stdId, fn ($q) => $q->where('standard_id', $stdId))
                    ->when($secId, fn ($q) => $q->where('section_id', $secId))
                    ->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no')
                    ->get();
                return [$records, [
                    'roll_no'             => fn ($r) => $r->roll_no,
                    'full_name'           => fn ($r) => $r->full_name,
                    'father_name'         => fn ($r) => $r->father_name,
                    'mother_name'         => fn ($r) => $r->mother_name,
                    'gender'              => fn ($r) => $r->gender ? ucfirst($r->gender) : '',
                    'dob'                 => fn ($r) => $fmtDate($r->dob),
                    'admission_no'        => fn ($r) => $r->admission_no,
                    'registration_number' => fn ($r) => $r->registration_number,
                    'date_of_admission'   => fn ($r) => $fmtDate($r->date_of_admission),
                    'phone'               => fn ($r) => $r->phone,
                    'email'               => fn ($r) => $r->email,
                    'local_address'       => fn ($r) => $r->local_address,
                    'city'                => fn ($r) => $r->city,
                    'state'               => fn ($r) => $r->state,
                    'board'               => fn ($r) => $r->board,
                    'aadhar_no'           => fn ($r) => $r->aadhar_no,
                ]];

            case 'teacher':
                $records = TeacherDetail::with('user:id,name,email')
                    ->where('organization_id', $orgId)
                    ->get();
                return [$records, [
                    'employee_id'       => fn ($r) => $r->employee_id,
                    'name'              => fn ($r) => optional($r->user)->name,
                    'email'             => fn ($r) => optional($r->user)->email,
                    'phone'             => fn ($r) => $r->phone,
                    'qualification'     => fn ($r) => $r->qualification,
                    'date_of_joining'   => fn ($r) => $fmtDate($r->date_of_joining),
                    'city'              => fn ($r) => $r->city,
                    'state'             => fn ($r) => $r->state,
                    'emergency_contact' => fn ($r) => $r->emergency_contact,
                ]];

            case 'fee':
                $records = FeePayment::with(['studentDetail:id,full_name', 'standard:id,name', 'section:id,name'])
                    ->where('organization_id', $orgId)
                    ->when($stdId, fn ($q) => $q->where('standard_id', $stdId))
                    ->when($secId, fn ($q) => $q->where('section_id', $secId))
                    ->orderByDesc('payment_date')
                    ->get();
                return [$records, [
                    'receipt_number' => fn ($r) => $r->receipt_number,
                    'student'        => fn ($r) => optional($r->studentDetail)->full_name,
                    'class'          => fn ($r) => $this->classLabel($r->standard, $r->section),
                    'fee_type'       => fn ($r) => ucfirst((string) $r->fee_type),
                    'amount'         => fn ($r) => $r->amount !== null ? number_format((float) $r->amount, 2) : '',
                    'payment_mode'   => fn ($r) => ucfirst((string) $r->payment_mode),
                    'payment_date'   => fn ($r) => $fmtDate($r->payment_date),
                    'remark'         => fn ($r) => $r->remark,
                ]];

            case 'transport':
                $records = Transportation::with('driver.user:id,name')
                    ->where('organization_id', $orgId)
                    ->withCount('students')
                    ->orderBy('route_name')
                    ->get();
                return [$records, [
                    'route_name'      => fn ($r) => $r->route_name,
                    'driver'          => fn ($r) => optional(optional($r->driver)->user)->name,
                    'pickup_time'     => fn ($r) => $fmtTime($r->pickup_time),
                    'drop_time'       => fn ($r) => $fmtTime($r->drop_time),
                    'pickup_location' => fn ($r) => $r->pickup_location,
                    'drop_location'   => fn ($r) => $r->drop_location,
                    'monthly_fee'     => fn ($r) => $r->monthly_fee !== null ? number_format((float) $r->monthly_fee, 2) : '',
                    'capacity'        => fn ($r) => $r->capacity,
                    'students_count'  => fn ($r) => $r->students_count,
                ]];

            case 'exam':
                $records = Exam::where('organization_id', $orgId)
                    ->orderByDesc('start_date')
                    ->get();
                return [$records, [
                    'exam_name'     => fn ($r) => $r->exam_name,
                    'exam_type'     => fn ($r) => $r->exam_type,
                    'term'          => fn ($r) => $r->term,
                    'academic_year' => fn ($r) => $r->academic_year,
                    'start_date'    => fn ($r) => $fmtDate($r->start_date),
                    'end_date'      => fn ($r) => $fmtDate($r->end_date),
                    'total_marks'   => fn ($r) => $r->total_marks,
                    'passing_marks' => fn ($r) => $r->passing_marks,
                    'status'        => fn ($r) => ucfirst((string) ($r->status ?: ($r->is_published ? 'published' : 'draft'))),
                ]];

            case 'performance':
                $records = ExamCopy::with(['studentDetail:id,full_name,roll_no', 'subject:id,name'])
                    ->where('organization_id', $orgId)
                    ->when($examId, fn ($q) => $q->where('exam_id', $examId))
                    ->when($stdId, fn ($q) => $q->where('standard_id', $stdId))
                    ->when($secId, fn ($q) => $q->where('section_id', $secId))
                    ->get();
                return [$records, [
                    'roll_no'        => fn ($r) => optional($r->studentDetail)->roll_no,
                    'student'        => fn ($r) => optional($r->studentDetail)->full_name,
                    'subject'        => fn ($r) => optional($r->subject)->name,
                    'marks_obtained' => fn ($r) => $r->is_absent ? 'AB' : $r->marks_obtained,
                    'max_marks'      => fn ($r) => $r->max_marks,
                    'percentage'     => fn ($r) => $r->percentage !== null ? $r->percentage . '%' : '',
                    'grade'          => fn ($r) => $r->grade,
                ]];

            case 'attendance':
                return $this->fetchAttendance($orgId, $stdId, $secId, $p['month'] ?? null, $fmtDate);

            case 'id_card':
                $records = StudentIdCard::with(['studentDetail:id,full_name,standard_id,section_id', 'studentDetail.standard:id,name', 'studentDetail.section:id,name'])
                    ->where('organization_id', $orgId)
                    ->when($stdId, fn ($q) => $q->whereHas('studentDetail', fn ($s) => $s->where('standard_id', $stdId)))
                    ->when($secId, fn ($q) => $q->whereHas('studentDetail', fn ($s) => $s->where('section_id', $secId)))
                    ->orderByDesc('issue_date')
                    ->get();
                return [$records, [
                    'card_number' => fn ($r) => $r->card_number,
                    'student'     => fn ($r) => optional($r->studentDetail)->full_name,
                    'class'       => fn ($r) => $this->classLabel(optional($r->studentDetail)->standard, optional($r->studentDetail)->section),
                    'issue_date'  => fn ($r) => $fmtDate($r->issue_date),
                    'expiry_date' => fn ($r) => $fmtDate($r->expiry_date),
                    'status'      => fn ($r) => ucfirst((string) $r->status),
                ]];

            case 'admit_card':
                $records = AdmitCard::with('standard:id,name', 'section:id,name')
                    ->where('organization_id', $orgId)
                    ->when($examId, fn ($q) => $q->where('exam_id', $examId))
                    ->when($stdId, fn ($q) => $q->where('standard_id', $stdId))
                    ->when($secId, fn ($q) => $q->where('section_id', $secId))
                    ->orderByDesc('issue_date')
                    ->get();
                return [$records, [
                    'admit_card_number' => fn ($r) => $r->admit_card_number,
                    'student_name'      => fn ($r) => $r->student_name,
                    'roll_number'       => fn ($r) => $r->roll_number,
                    'exam_name'         => fn ($r) => $r->exam_name,
                    'class'             => fn ($r) => $this->classLabel($r->standard, $r->section),
                    'seat_number'       => fn ($r) => $r->seat_number,
                    'room_number'       => fn ($r) => $r->room_number,
                    'status'            => fn ($r) => ucfirst((string) $r->status),
                ]];
        }

        return [collect(), []];
    }

    /** Per-student monthly attendance tally. */
    private function fetchAttendance(int $orgId, $stdId, $secId, ?string $month, callable $fmtDate): array
    {
        $students = StudentDetail::where('organization_id', $orgId)
            ->when($stdId, fn ($q) => $q->where('standard_id', $stdId))
            ->when($secId, fn ($q) => $q->where('section_id', $secId))
            ->orderByRaw('CAST(roll_no AS UNSIGNED), roll_no')
            ->get(['id', 'full_name', 'roll_no']);

        [$year, $mon] = $month && str_contains($month, '-')
            ? array_map('intval', explode('-', $month))
            : [(int) date('Y'), (int) date('n')];

        $att = StudentAttendance::where('organization_id', $orgId)
            ->whereIn('student_detail_id', $students->pluck('id'))
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $mon)
            ->get(['student_detail_id', 'status'])
            ->groupBy('student_detail_id');

        $records = $students->map(function ($s) use ($att) {
            $rows    = $att->get($s->id, collect());
            $present = $rows->where('status', self::ATT_PRESENT)->count();
            $absent  = $rows->where('status', self::ATT_ABSENT)->count();
            $half    = $rows->where('status', self::ATT_HALF)->count();
            $working = $present + $absent + $half;
            $pct     = $working > 0 ? round(($present + 0.5 * $half) / $working * 100, 1) : 0;

            $s->setAttribute('att_present', $present);
            $s->setAttribute('att_absent', $absent);
            $s->setAttribute('att_half', $half);
            $s->setAttribute('att_working', $working);
            $s->setAttribute('att_pct', $pct);
            return $s;
        });

        return [$records, [
            'roll_no'    => fn ($r) => $r->roll_no,
            'student'    => fn ($r) => $r->full_name,
            'present'    => fn ($r) => $r->att_present,
            'absent'     => fn ($r) => $r->att_absent,
            'half_day'   => fn ($r) => $r->att_half,
            'working'    => fn ($r) => $r->att_working,
            'percentage' => fn ($r) => $r->att_pct . '%',
        ]];
    }

    private function classLabel($standard, $section): string
    {
        $std = $standard->name ?? '';
        $sec = $section->name ?? '';
        return trim($std . ($sec ? ' - ' . $sec : ''));
    }
}
