<?php

namespace App\Support;

use App\Exports\TeachersExport;
use App\Models\Organization;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\Teacher\TeacherSubject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * The Teachers export for the admin app, built as the panel's Export builds it
 * (App\Livewire\Admin\Teacher::teacherExportData / exportTeachers /
 * exportTeachersPdf): every Add Teacher field, bank details, class-teacher
 * duty, subjects taught and attendance month by month for the running
 * session, as an Excel sheet or a PDF of record cards. The panel keeps its own
 * copy and is not changed by this one.
 */
class TeacherExport
{
    /**
     * Returns [$headings, $flatRows, $records] exactly as the panel's
     * teacherExportData() does:
     *   - $flatRows - one associative row per teacher (keys are the column
     *                 headings) for the spreadsheet.
     *   - $records  - the same data reshaped into PDF record cards.
     * Anything unavailable becomes "-".
     */
    public static function data(int $org): array
    {
        $teachers = TeacherDetail::with(['user', 'assignedClasses'])
            ->where('organization_id', $org)
            ->tap(fn ($q) => NameOrder::teachers($q))
            ->get();

        $ids = $teachers->pluck('id')->all();

        // Teacher bank columns only exist if `lms:migrate` ever added them.
        $hasBank = Schema::hasColumn('teacher_details', 'bank_name');
        $dash    = fn ($v) => ($v === null || $v === '') ? '-' : $v;

        $months      = AcademicYear::months();
        $sessionFrom = AcademicYear::start();
        $sessionTo   = AcademicYear::end();
        $session     = AcademicYear::label();

        // Attendance month by month, in one aggregate rather than per teacher.
        $monthly = TeacherAttendance::whereIn('teacher_detail_id', $ids)
            ->whereBetween('attendance_date', [$sessionFrom, $sessionTo])
            ->selectRaw("teacher_detail_id, DATE_FORMAT(attendance_date, '%Y-%m') as ym,
                         COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present")
            ->groupBy('teacher_detail_id', 'ym')
            ->get()
            ->groupBy('teacher_detail_id');

        // Lifetime attendance totals.
        $overall = TeacherAttendance::whereIn('teacher_detail_id', $ids)
            ->selectRaw('teacher_detail_id, COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present')
            ->groupBy('teacher_detail_id')
            ->get()
            ->keyBy('teacher_detail_id');

        // Subjects taught, one query for everyone.
        $subjectsByTeacher = TeacherSubject::with(['subject:id,name', 'standard:id,name', 'section:id,name'])
            ->whereIn('teacher_detail_id', $ids)
            ->get()
            ->groupBy('teacher_detail_id');

        $rows    = [];
        $records = [];

        foreach ($teachers as $i => $t) {
            $u = $t->user;

            $att      = $overall->get($t->id);
            $attTotal = (int) ($att->total ?? 0);
            $attPres  = (int) ($att->present ?? 0);
            $attStr   = $attTotal > 0 ? "{$attPres} / {$attTotal}" : '-';
            $attPct   = $attTotal > 0 ? round($attPres / $attTotal * 100, 1) . '%' : '-';

            // Month cells read "present/total"; a month with nothing marked is a dash.
            $byMonth    = ($monthly->get($t->id) ?? collect())->keyBy('ym');
            $monthCells = [];
            foreach ($months as $m) {
                $mRow = $byMonth->get($m['key']);
                $monthCells[$m['label']] = $mRow
                    ? ((int) $mRow->present) . '/' . ((int) $mRow->total)
                    : '-';
            }

            // Class-teacher duty - "5 - A", several joined by a comma.
            $classTeacherOf = $t->assignedClasses
                ->map(function ($a) {
                    $cls = $a->standard?->name;
                    if (!$cls) return null;
                    return $a->section?->name ? $cls . ' - ' . $a->section->name : $cls;
                })
                ->filter()
                ->unique()
                ->implode(', ');

            $subjects = ($subjectsByTeacher->get($t->id) ?? collect())
                ->map(function ($ts) {
                    $sub = $ts->subject?->name ?? 'Subject';
                    $cls = trim(($ts->standard?->name ?? '') . ($ts->section ? ' - ' . $ts->section->name : ''));
                    return $cls !== '' ? "{$sub} ({$cls})" : $sub;
                })
                ->unique()
                ->implode('; ');

            $dob = $u?->dob;
            if ($dob instanceof \Carbon\Carbon) $dob = $dob->format('d-m-Y');
            $doj = $t->date_of_joining;
            if ($doj instanceof \Carbon\Carbon) $doj = $doj->format('d-m-Y');

            $status = ($u?->is_active ? 'Active' : 'Inactive');

            // Spreadsheet row: every value gets its own column.
            $row = [
                'S.No'                  => $i + 1,
                'Employee ID'           => $dash($t->employee_id),
                'Username'              => $dash($u?->username),
                'Full Name'             => $dash($u?->name),
                'Email'                 => $dash($u?->email),
                'Mobile'                => $dash($u?->mobile_number),
                'Gender'                => $dash($u?->gender ? ucfirst($u->gender) : null),
                'Date of Birth'         => $dash($dob),
                'Date of Joining'       => $dash($doj),
                'Qualification'         => $dash($t->qualification),
                'Emergency Contact'     => $dash($t->emergency_contact),
                'Address'               => $dash($t->address),
                'City'                  => $dash($t->city),
                'State'                 => $dash($t->state),
                'Pincode'               => $dash($t->pincode),
                'Class Teacher Of'      => $dash($classTeacherOf),
                'Subjects (with Class)' => $dash($subjects),
                'Status'                => $status,
                'Bank Name'             => $dash($hasBank ? $t->bank_name : null),
                'Bank Account No'       => $dash($hasBank ? $t->bank_account_no : null),
                'Bank IFSC'             => $dash($hasBank ? $t->bank_ifsc : null),
                'Bank Branch'           => $dash($hasBank ? $t->bank_branch : null),
                'Account Holder'        => $dash($hasBank ? $t->bank_holder_name : null),
                'Attendance (P/Total)'  => $attStr,
                'Attendance %'          => $attPct,
            ];
            foreach ($monthCells as $label => $value) {
                $row[$label . " {$session}"] = $value;
            }

            $rows[] = $row;

            // PDF card: 21 fields in three rows of seven, months in a strip below.
            $records[] = [
                'no'    => $i + 1,
                'title' => $u?->name ?: '-',
                'badge' => trim(($t->employee_id ? $t->employee_id . ' - ' : '') . $status),
                'fields' => [
                    'Email'             => $dash($u?->email),
                    'Mobile'            => $dash($u?->mobile_number),
                    'Gender'            => $dash($u?->gender ? ucfirst($u->gender) : null),
                    'Date of Birth'     => $dash($dob),
                    'Date of Joining'   => $dash($doj),
                    'Qualification'     => $dash($t->qualification),
                    'Emergency Contact' => $dash($t->emergency_contact),

                    'Class Teacher Of'  => $dash($classTeacherOf),
                    'Subjects'          => $dash($subjects),
                    'Address'           => $dash($t->address),
                    'City'              => $dash($t->city),
                    'State'             => $dash($t->state),
                    'Pincode'           => $dash($t->pincode),
                    'Attendance'        => $attTotal > 0 ? "{$attStr}  ({$attPct})" : '-',

                    'Bank Name'         => $dash($hasBank ? $t->bank_name : null),
                    'Bank Account No'   => $dash($hasBank ? $t->bank_account_no : null),
                    'Bank IFSC'         => $dash($hasBank ? $t->bank_ifsc : null),
                    'Bank Branch'       => $dash($hasBank ? $t->bank_branch : null),
                    'Account Holder'    => $dash($hasBank ? $t->bank_holder_name : null),
                    'Employee ID'       => $dash($t->employee_id),
                    'Session'           => $session,
                ],
                'strip' => [
                    'label' => "Attendance {$session}",
                    'cells' => $monthCells,
                ],
            ];
        }

        $headings = $rows ? array_keys($rows[0]) : ['S.No'];

        return [$headings, $rows, $records];
    }

    /**
     * The Teachers PDF — the panel's and the admin app's alike, in the
     * Students PDF's layout (App\Support\PdfList): ten teachers to an A4
     * landscape page, one row each — serial number, photo in a small circle,
     * then five cells of four fields, the username among them. No status,
     * attendance or subjects (the sheet keeps them). Nothing is cut short: a
     * long value carries on to the next line.
     */
    public static function listPdf(int $org): string
    {
        $teachers = TeacherDetail::with(['user', 'assignedClasses'])
            ->where('organization_id', $org)
            ->tap(fn ($q) => NameOrder::teachers($q))
            ->get();

        $hasBank = Schema::hasColumn('teacher_details', 'bank_name');
        $photos  = PdfPhotos::squares($teachers->map(fn ($t) => $t->user?->image)->all());

        $rows = [];
        foreach ($teachers as $t) {
            $u = $t->user;

            $classTeacherOf = $t->assignedClasses
                ->map(function ($a) {
                    $cls = $a->standard?->name;
                    if (!$cls) return null;
                    return $a->section?->name ? $cls . ' - ' . $a->section->name : $cls;
                })
                ->filter()
                ->unique()
                ->implode(', ');

            $dob = $u?->dob;
            if ($dob instanceof \Carbon\Carbon) $dob = $dob->format('d-m-Y');
            $doj = $t->date_of_joining;
            if ($doj instanceof \Carbon\Carbon) $doj = $doj->format('d-m-Y');

            $name  = (string) ($u?->name ?? '');
            $image = $u?->image;

            $rows[] = [
                'photo'   => $image ? ($photos[$image] ?? null) : null,
                'initial' => mb_strtoupper(mb_substr(trim($name) ?: '?', 0, 1)),
                'cells'   => [
                    [['Name', $name], ['Username', $u?->username], ['Employee ID', $t->employee_id], ['Gender', $u?->gender ? ucfirst($u->gender) : null]],
                    [['DOB', $dob], ['Joined', $doj], ['Qualification', $t->qualification], ['Class Teacher', $classTeacherOf]],
                    [['Address', $t->address], ['City', $t->city], ['State', $t->state], ['Pincode', $t->pincode]],
                    [['Mobile', $u?->mobile_number], ['Email', $u?->email], ['Emergency', $t->emergency_contact], ['Bank', $hasBank ? $t->bank_name : null]],
                    [['Holder', $hasBank ? $t->bank_holder_name : null], ['A/c No', $hasBank ? $t->bank_account_no : null], ['IFSC', $hasBank ? $t->bank_ifsc : null], ['Branch', $hasBank ? $t->bank_branch : null]],
                ],
            ];
        }

        $orgModel = Organization::find($org);
        $total    = count($rows);

        return PdfList::render([
            'school' => $orgModel?->name,
            'logo'   => PdfList::logo($orgModel?->logo),
            'title'  => 'Teachers',
            'scope'  => 'All teachers',
            'count'  => $total . ' teacher' . ($total === 1 ? '' : 's'),
        ], [46, 50, 52, 60, 49.5], $rows);
    }

    /** The sheet's bytes, as the panel's exportTeachers() streams them. */
    public static function xlsx(array $headings, array $rows): string
    {
        return Excel::raw(new TeachersExport($headings, $rows), \Maatwebsite\Excel\Excel::XLSX);
    }

    /** The record cards as an A4 landscape PDF, as the panel's exportTeachersPdf() draws them. */
    public static function pdf(int $org, array $rows, array $records): string
    {
        $orgModel = Organization::find($org);
        $school = [
            'name' => $orgModel?->name,
            'logo' => ($orgModel?->logo && Str::startsWith($orgModel->logo, ['http://', 'https://'])) ? $orgModel->logo : null,
        ];

        return self::render('pdf.record-export', [
            'title'          => 'Teachers Report',
            'school'         => $school,
            'perRow'         => 7,
            'recordsByGroup' => ['' => $records],
            'total'          => count($rows),
        ]);
    }

    /**
     * Render a landscape export PDF with our bundled fonts; falls back to
     * default fonts if the dompdf font cache can't be written.
     */
    private static function render(string $view, array $data): string
    {
        $fontCache = storage_path('fonts');
        if (!is_dir($fontCache)) {
            @mkdir($fontCache, 0775, true);
        }

        $render = function (string $fontCss) use ($view, $data, $fontCache): string {
            return Pdf::loadView($view, $data + compact('fontCss'))
                ->setPaper('a4', 'landscape')
                ->setOption('dpi', 130)
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true)
                ->setOption('isFontSubsettingEnabled', true)
                ->setOption('fontDir', $fontCache)
                ->setOption('fontCache', $fontCache)
                ->setOption('defaultFont', 'DejaVu Sans')
                ->output();
        };

        try {
            return $render(PdfFonts::faceCss());
        } catch (\Throwable $e) {
            logger()->warning('Teacher export PDF custom-font render failed, using fallback: ' . $e->getMessage());
            return $render('');
        }
    }
}
