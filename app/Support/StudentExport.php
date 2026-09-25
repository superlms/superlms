<?php

namespace App\Support;

use App\Exports\StudentsExport;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * The Students export — the admin panel's and the admin app's alike: every Add
 * Student field plus admission and roll numbers, attendance and the academic +
 * transport fee position, for the whole school or one class (and one section
 * of it), as an Excel sheet or a PDF of record cards.
 */
class StudentExport
{
    /**
     * Build the export dataset. Returns [$headings, $flatRows, $recordsByClass]:
     *   - $flatRows       — one associative row per student (keys are the column
     *                       headings) for the spreadsheet, ordered class-by-class.
     *                       Carries every Add Student form field plus attendance
     *                       and the academic + transport fee position.
     *   - $recordsByClass — the same data as PDF record cards, grouped under
     *                       their "Class - Section" label in class order.
     * Attendance and fees are computed with the same logic the Fee module uses;
     * anything unavailable becomes "-". $classId (and $sectionId within it)
     * narrow it to one class; without, the whole school.
     */
    public static function data(int $org, ?int $classId = null, ?int $sectionId = null): array
    {
        $dash = fn ($v) => ($v === null || $v === '') ? '-' : $v;

        // Class-by-class order: class → section → numeric roll → name.
        $students = StudentDetail::with(['user', 'standard', 'section', 'organization', 'transportations'])
            ->where('organization_id', $org)
            ->whereHas('user', fn($q) => $q->where('organization_id', $org))
            // Class-wise export: one class, and one section within it when chosen.
            ->when($classId, fn($q) => $q->where('standard_id', $classId))
            ->when($classId && $sectionId, fn($q) => $q->where('section_id', $sectionId))
            ->orderBy('standard_id')
            ->orderBy('section_id')
            ->orderByRaw('CAST(roll_no AS UNSIGNED)')
            ->orderBy('full_name')
            ->get();

        $ids = $students->pluck('id')->all();

        // Attendance totals per student in one aggregate query.
        $attendance = StudentAttendance::whereIn('student_detail_id', $ids)
            ->selectRaw('student_detail_id, COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present')
            ->groupBy('student_detail_id')
            ->get()
            ->keyBy('student_detail_id');

        // Active fee structures for the org (small set — filtered per student below).
        $structures = FeeStructure::where('organization_id', $org)
            ->where('is_active', true)
            ->get();

        // Each student's own academic rows — Last Year Dues.
        $ownFees = FeeStructure::ownTotals($org, $ids);

        // All fee payments for these students, grouped by student.
        $payments = FeePayment::where('organization_id', $org)
            ->whereIn('student_detail_id', $ids)
            ->get()
            ->groupBy('student_detail_id');

        // Transport is billed on each student's route (monthly fee × billed
        // months) and paid into transport_fee_payments — see TransportBilling.
        $transportTotals = TransportBilling::yearTotals($org, $ids);
        $transportPaids  = TransportBilling::paidByStudent($org, $ids);

        $rows           = [];
        $recordsByClass = [];

        foreach ($students as $i => $s) {
            // ── Attendance (present / total) ──
            $att      = $attendance->get($s->id);
            $attTotal = (int) ($att->total ?? 0);
            $attPres  = (int) ($att->present ?? 0);
            $attStr   = $attTotal > 0 ? "{$attPres} / {$attTotal}" : '-';

            // ── Fees (paid / total), matching the Fee module's per-student calc ──
            $studentStructures = $structures->filter(
                fn ($st) => (int) $st->standard_id === (int) $s->standard_id
                    && (is_null($st->section_id) || (int) $st->section_id === (int) $s->section_id)
            );
            $academicTotal = (float) $studentStructures->where('fee_type', 'academic')->sum('amount')
                + (float) ($ownFees[$s->id] ?? 0);
            $transportTotal = (float) ($transportTotals[$s->id] ?? 0);

            $studentPayments = $payments->get($s->id, collect());
            $academicPaid  = (float) $studentPayments->where('fee_type', 'academic')->sum('amount');
            $transportPaid = (float) ($transportPaids[$s->id] ?? 0);

            $money = fn ($v) => number_format((float) $v, 0);
            $academicStr  = ($academicTotal > 0 || $academicPaid > 0)
                ? '₹' . $money($academicPaid) . ' / ₹' . $money($academicTotal) : '-';
            // On a route, not the transportation_required flag, which is not
            // kept in step with route assignments.
            $transportStr = ($transportTotal > 0 || $transportPaid > 0)
                ? '₹' . $money($transportPaid) . ' / ₹' . $money($transportTotal) : '-';

            $attPct       = $attTotal > 0 ? round($attPres / $attTotal * 100, 1) . '%' : '-';
            $academicDue  = ($academicTotal > 0 || $academicPaid > 0)
                ? '₹' . $money(max($academicTotal - $academicPaid, 0)) : '-';
            $transportDue = ($transportTotal > 0 || $transportPaid > 0)
                ? '₹' . $money(max($transportTotal - $transportPaid, 0)) : '-';

            $route     = $s->transportations->first();
            $className = $s->standard->name ?? '-';
            $secName   = $s->section->name ?? '';
            $classLabel = trim($className . ($secName !== '' ? ' - ' . $secName : ''));

            $row = [
                'S.No'                    => $i + 1,
                'Admission No'            => $dash($s->admission_no),
                'Roll No'                 => $dash($s->roll_no),
                'Organization'            => $dash($s->organization->name ?? null),
                'Full Name'               => $dash($s->full_name ?? ($s->user->name ?? null)),
                'Email'                   => $dash($s->user->email ?? null),
                'Mobile'                  => $dash($s->phone),
                'Gender'                  => $dash($s->gender ? ucfirst($s->gender) : null),
                'Date of Birth'           => $dash($s->dob?->format('d-m-Y')),
                'Date of Admission'       => $dash($s->date_of_admission?->format('d-m-Y')),
                'Religion'                => $dash($s->religion),
                'Aadhar No'               => $dash($s->aadhar_no),
                'Father Name'             => $dash($s->father_name),
                'Mother Name'             => $dash($s->mother_name),
                'Board (auto)'            => $dash($s->board ?? ($s->standard->board ?? null)),
                'Class'                   => $dash($className),
                'Section'                 => $dash($secName !== '' ? $secName : null),
                'Apaar ID'                => $dash($s->appar_id),
                'Registration Number'     => $dash($s->registration_number),
                'State'                   => $dash($s->state),
                'City'                    => $dash($s->city),
                'Pincode'                 => $dash($s->pincode),
                'Local Address'           => $dash($s->local_address),
                'Permanent Address'       => $dash($s->permanent_address),
                'Transportation Required' => $s->transportation_required ? 'Yes' : 'No',
                'Transport Route'         => $dash($route->route_name ?? null),
                'Attendance (P/Total)'    => $attStr,
                'Attendance %'            => $attPct,
                'Academic Fee (Paid/Total)'  => $academicStr,
                'Academic Fee Pending'       => $academicDue,
                'Transport Fee (Paid/Total)' => $transportStr,
                'Transport Fee Pending'      => $transportDue,
                'Status'                  => ($s->user->is_active ?? false) ? 'Active' : 'Inactive',
            ];

            $rows[] = $row;

            // PDF card: 21 fields in three rows of seven, with attendance and
            // both fee heads on a summary strip underneath.
            $status = ($s->user->is_active ?? false) ? 'Active' : 'Inactive';
            $recordsByClass[$classLabel][] = [
                'no'    => $i + 1,
                'title' => $s->full_name ?: ($s->user->name ?? '-'),
                'badge' => trim(
                    'Adm ' . ($s->admission_no ?: '-')
                    . ' · Roll ' . ($s->roll_no ?: '-')
                    . ' · ' . $status
                ),
                'fields' => [
                    'Class'              => $dash($className),
                    'Section'            => $dash($secName !== '' ? $secName : null),
                    'Board'              => $dash($s->board ?? ($s->standard->board ?? null)),
                    'Gender'             => $dash($s->gender ? ucfirst($s->gender) : null),
                    'Date of Birth'      => $dash($s->dob?->format('d-m-Y')),
                    'Date of Admission'  => $dash($s->date_of_admission?->format('d-m-Y')),
                    'Religion'           => $dash($s->religion),

                    'Father Name'        => $dash($s->father_name),
                    'Mother Name'        => $dash($s->mother_name),
                    'Email'              => $dash($s->user->email ?? null),
                    'Mobile'             => $dash($s->phone),
                    'Aadhar No'          => $dash($s->aadhar_no),
                    'Apaar ID'           => $dash($s->appar_id),
                    'Registration No'    => $dash($s->registration_number),

                    'Local Address'      => $dash($s->local_address),
                    'Permanent Address'  => $dash($s->permanent_address),
                    'City'               => $dash($s->city),
                    'State'              => $dash($s->state),
                    'Pincode'            => $dash($s->pincode),
                    'Transport'          => $s->transportation_required ? 'Yes' : 'No',
                    'Transport Route'    => $dash($route->route_name ?? null),
                ],
                'strip' => [
                    'label' => 'Attendance & Fees',
                    'cells' => [
                        'Attendance'    => $attStr,
                        'Attendance %'  => $attPct,
                        'Academic (Paid/Total)'  => $academicStr,
                        'Academic Pending'       => $academicDue,
                        'Transport (Paid/Total)' => $transportStr,
                        'Transport Pending'      => $transportDue,
                    ],
                ],
            ];
        }

        $headings = $rows ? array_keys($rows[0]) : ['S.No'];

        return [$headings, $rows, $recordsByClass];
    }

    /** The rows as an Excel (.xlsx) file's bytes. */
    public static function xlsx(array $headings, array $rows): string
    {
        return Excel::raw(new StudentsExport($headings, $rows), \Maatwebsite\Excel\Excel::XLSX);
    }

    /** The record cards as an A4 landscape PDF's bytes, under the school's name and logo. */
    public static function pdf(int $org, array $rows, array $recordsByClass): string
    {
        $orgModel = Organization::find($org);
        $school = [
            'name' => $orgModel?->name,
            'logo' => ($orgModel?->logo && Str::startsWith($orgModel->logo, ['http://', 'https://'])) ? $orgModel->logo : null,
        ];

        return self::render('pdf.record-export', [
            'title'          => 'Students Report',
            'school'         => $school,
            'perRow'         => 7,
            'recordsByGroup' => $recordsByClass,
            'total'          => count($rows),
        ]);
    }

    /** Filename part saying what was exported: "all" or "class-5-a". */
    public static function slug(?int $classId, ?int $sectionId): string
    {
        if (!$classId) {
            return 'all';
        }

        $class   = Standard::find($classId)?->name ?? 'class';
        $section = $sectionId ? Section::find($sectionId)?->name : null;

        return Str::slug(trim($class . ' ' . ($section ?? '')));
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
            logger()->warning('Export PDF custom-font render failed, using fallback: ' . $e->getMessage());
            return $render('');
        }
    }
}
