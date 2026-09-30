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

    /**
     * The Students PDF: ten students to an A4 landscape page, one row each —
     * serial number, photo (or the name's initial), then cells of three
     * fields as plain "Label: value" lines — rows kept apart by a divider, and
     * no attendance or fees. Same students and order as the sheet.
     *
     * Drawn straight with TCPDF rather than laid out from HTML by dompdf: the
     * record cards took dompdf well over the gateway's minute for a whole
     * school (a 504), and even a plain table took it most of a minute. Photos
     * come small and cached (PdfPhotos). A value too long for its cell is cut
     * short with "…", so every row keeps one height and a page holds ten.
     */
    public static function listPdf(int $org, ?int $classId = null, ?int $sectionId = null): string
    {
        $students = StudentDetail::with(['user:id,name,email,image,is_active', 'standard:id,name,board', 'section:id,name', 'transportations'])
            ->where('organization_id', $org)
            ->whereHas('user', fn($q) => $q->where('organization_id', $org))
            ->when($classId, fn($q) => $q->where('standard_id', $classId))
            ->when($classId && $sectionId, fn($q) => $q->where('section_id', $sectionId))
            ->orderBy('standard_id')
            ->orderBy('section_id')
            ->orderByRaw('CAST(roll_no AS UNSIGNED)')
            ->orderBy('full_name')
            ->get();

        $photos = PdfPhotos::squares($students->map(fn ($s) => $s->user->image ?? null)->all());

        $dash = fn ($v) => ($v === null || trim((string) $v) === '') ? '-' : trim((string) $v);

        // Column widths in mm (277 across): S.No, photo, then the eight cells.
        $noW   = 8;
        $picW  = 17;
        $cellW = [38, 22, 30, 35, 42, 26, 28, 31];

        $orgModel = Organization::find($org);
        $scope = 'All students';
        if ($classId) {
            $scope = trim((Standard::find($classId)?->name ?? 'Class')
                . ($sectionId ? ' - ' . (Section::find($sectionId)?->name ?? '') : ''));
        }
        $total   = $students->count();
        $pages   = max(1, (int) ceil($total / 10));
        $printed = now()->format('d M Y, g:i A');

        // TCPDF 6.11 flags itself deprecated in favour of tc-lib-pdf once per
        // process; it still works, so keep that note out of the logs.
        if (!defined('TCPDF_SILENCE_DEPRECATION')) {
            define('TCPDF_SILENCE_DEPRECATION', true);
        }
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('SuperLMS');
        $pdf->SetTitle('Students');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 9, 10);
        $pdf->SetAutoPageBreak(false);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->setFontSubsetting(true);

        // Cut a value down to what fits in $width at the current font.
        $fit = function (string $text, float $width) use ($pdf): string {
            if ($pdf->GetStringWidth($text) <= $width) {
                return $text;
            }
            $len = mb_strlen($text);
            while ($len > 1 && $pdf->GetStringWidth(mb_substr($text, 0, $len) . '…') > $width) {
                $len--;
            }
            return mb_substr($text, 0, $len) . '…';
        };

        $logo = self::logoJpeg($orgModel?->logo);
        $grey = [107, 114, 128];
        $ink  = [17, 24, 39];

        $drawHeader = function (int $page) use ($pdf, $orgModel, $scope, $total, $printed, $pages, $logo, $grey, $ink, $fit) {
            $x = 10;
            if ($page === 1 && $logo) {
                $pdf->Image('@' . $logo, 10, 8.5, 0, 6, 'JPG');
                $x = $pdf->getImageRBX() + 2;
            }
            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->SetTextColor(...$ink);
            $name  = $orgModel?->name ?: 'School';
            $nameW = $pdf->GetStringWidth($name) + 1;
            $pdf->SetXY($x, 9);
            $pdf->Cell($nameW, 5, $name, 0, 0, 'L');

            $pdf->SetFont('dejavusans', '', 7.5);
            $pdf->SetTextColor(...$grey);
            $rest = 187 - ($x + $nameW);
            $pdf->Cell($rest, 5, $fit('  ·  Students  ·  ' . $scope . '  ·  ' . $total . ' student' . ($total === 1 ? '' : 's'), $rest), 0, 0, 'L');

            $pdf->SetXY(187, 9);
            $pdf->Cell(100, 5, $printed . '  ·  Page ' . $page . ' of ' . $pages, 0, 0, 'R');

            $pdf->SetDrawColor(...$ink);
            $pdf->SetLineWidth(0.3);
            $pdf->Line(10, 15.5, 287, 15.5);
        };

        $rowH  = 17.5;
        $lineH = 3.7;
        $top   = 16.5;

        $rows = $students->values();
        for ($page = 1; $page <= $pages; $page++) {
            $pdf->AddPage();
            $drawHeader($page);

            if ($total === 0) {
                $pdf->SetFont('dejavusans', '', 9);
                $pdf->SetTextColor(...$grey);
                $pdf->SetXY(10, 30);
                $pdf->Cell(277, 6, 'No students to export.', 0, 0, 'C');
            }

            foreach ($rows->slice(($page - 1) * 10, 10)->values() as $k => $s) {
                $i     = ($page - 1) * 10 + $k;
                $y     = $top + $k * $rowH;
                $name  = $s->full_name ?: ($s->user->name ?? '');
                $route = $s->transportations->first();
                $image = $s->user->image ?? null;

                // Serial number
                $pdf->SetFont('dejavusans', 'B', 8);
                $pdf->SetTextColor(...$ink);
                $pdf->SetXY(10, $y);
                $pdf->Cell($noW, $rowH, (string) ($i + 1), 0, 0, 'C', false, '', 0, false, 'T', 'M');

                // Photo, or the name's initial on grey
                $px    = 10 + $noW + 1.5;
                $py    = $y + ($rowH - 14) / 2;
                $photo = $image ? ($photos[$image] ?? null) : null;
                if ($photo) {
                    $pdf->Image('@' . $photo, $px, $py, 14, 14, 'JPG');
                } else {
                    $pdf->SetFillColor(229, 231, 235);
                    $pdf->Rect($px, $py, 14, 14, 'F');
                    $pdf->SetFont('dejavusans', '', 14);
                    $pdf->SetTextColor(...$grey);
                    $pdf->SetXY($px, $py);
                    $pdf->Cell(14, 14, mb_strtoupper(mb_substr(trim($name) ?: '?', 0, 1)), 0, 0, 'C', false, '', 0, false, 'T', 'M');
                }

                $cells = [
                    [['Name', $name], ['Adm No', $s->admission_no], ['Roll No', $s->roll_no]],
                    [['Class', $s->standard->name ?? null], ['Section', $s->section->name ?? null], ['Board', $s->board ?? ($s->standard->board ?? null)]],
                    [['Gender', $s->gender ? ucfirst($s->gender) : null], ['DOB', $s->dob?->format('d-m-Y')], ['Admitted', $s->date_of_admission?->format('d-m-Y')]],
                    [['Father', $s->father_name], ['Mother', $s->mother_name], ['Religion', $s->religion]],
                    [['Mobile', $s->phone], ['Email', $s->user->email ?? null], ['Aadhar', $s->aadhar_no]],
                    [['Apaar ID', $s->appar_id], ['Reg No', $s->registration_number], ['Status', ($s->user->is_active ?? false) ? 'Active' : 'Inactive']],
                    [['City', $s->city], ['State', $s->state], ['Pincode', $s->pincode]],
                    [['Address', $s->local_address], ['Permanent', $s->permanent_address], ['Transport', $route ? $route->route_name : 'No']],
                ];

                $cx = 10 + $noW + $picW;
                $ty = $y + ($rowH - 3 * $lineH) / 2;
                $pdf->SetFont('dejavusans', '', 6.8);
                foreach ($cells as $c => $fields) {
                    $w = $cellW[$c] - 1.5;
                    foreach ($fields as $l => [$label, $value]) {
                        $label .= ': ';
                        $lw = $pdf->GetStringWidth($label);
                        $pdf->SetXY($cx, $ty + $l * $lineH);
                        $pdf->SetTextColor(...$grey);
                        $pdf->Cell($lw, $lineH, $label, 0, 0, 'L');
                        $pdf->SetTextColor(...$ink);
                        $pdf->Cell($w - $lw, $lineH, $fit($dash($value), $w - $lw), 0, 0, 'L');
                    }
                    $cx += $cellW[$c];
                }

                // Divider under the student
                $pdf->SetDrawColor(209, 213, 219);
                $pdf->SetLineWidth(0.2);
                $pdf->Line(10, $y + $rowH, 287, $y + $rowH);
            }
        }

        return $pdf->Output('students.pdf', 'S');
    }

    /** The school's logo as JPEG bytes on white, 80px high (fetched once), or null. */
    private static function logoJpeg(?string $logo): ?string
    {
        if (!$logo || !Str::startsWith($logo, ['http://', 'https://'])) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->connectTimeout(3)->get($logo);
            if (!$response->successful() || strlen($response->body()) > 5_000_000) {
                return null;
            }
            $src = @imagecreatefromstring($response->body());
            if (!$src) {
                return null;
            }
            $h   = 80;
            $w   = max(1, (int) round(imagesx($src) * $h / max(1, imagesy($src))));
            $out = imagecreatetruecolor($w, $h);
            imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
            imagecopyresampled($out, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
            ob_start();
            imagejpeg($out, null, 90);
            $jpeg = (string) ob_get_clean();
            imagedestroy($out);
            imagedestroy($src);

            return $jpeg !== '' ? $jpeg : null;
        } catch (\Throwable $e) {
            // No logo is better than no PDF.
            return null;
        }
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
