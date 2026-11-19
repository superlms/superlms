<?php

namespace App\Support;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\SchoolInfo;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Services\GradingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Admin → Performance → Progress Report: one exam's report card for a
 * student, on one A5 sheet — every subject of that exam with its marks and
 * grade, the total, the percentage and grade, the rank in the section and the
 * result.
 *
 * A section's cards are worked out together, because the rank is a student's
 * place in the section on that exam's overall marks (total obtained, then the
 * percentage), as the Performers tab ranks them — except that equal marks
 * share a rank here (1, 2, 2, 4), since the card is printed. An absent paper
 * counts as 0 of its maximum, as it does on the Performers tab. A deleted
 * student's or subject's marks are left out.
 *
 * The result is on the combined marks alone: Fail only when all the
 * subjects together come under the pass percentage (config/grading.php,
 * 35%) — a low mark in one subject does not fail the card.
 */
class ProgressReport
{
    /**
     * The cards of a section for one exam: each student who has marks in it,
     * A to Z by name.
     *
     * @return array<int, array{
     *     student_id: int, name: string, admission_no: string, roll_no: string,
     *     father_name: string, mother_name: string, dob: string, image: ?string,
     *     rows: array<int, array{subject: string, entered: bool, absent: bool, obtained: string, max: string, grade: string}>,
     *     entered: int, subjects: int, obtained: string, max: string, pct: float,
     *     grade: string, remark: string, result: string, rank: int, of: int
     * }>
     */
    public static function cards(int $orgId, Exam $exam, int $standardId, int $sectionId): array
    {
        $copies = ExamCopy::with([
            'studentDetail',
            'studentDetail.user:id,name,image',
            'subject:id,name',
        ])
            ->where('organization_id', $orgId)
            ->where('exam_id', $exam->id)
            ->where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->whereHas('studentDetail')
            ->whereHas('subject')
            ->get();

        if ($copies->isEmpty()) {
            return [];
        }

        // The exam's subjects for this section: every subject anyone in it
        // has marks in, A to Z — so a paper a student has no marks for yet
        // still shows on their card, as not entered.
        $subjects = $copies->pluck('subject')->unique('id')
            ->sortBy(fn ($s) => mb_strtolower((string) $s->name))
            ->values();

        $grading = app(GradingService::class);

        $cards = [];
        foreach ($copies->groupBy('student_detail_id') as $own) {
            $student   = $own->first()->studentDetail;
            $bySubject = $own->keyBy('subject_id');

            $rows = [];
            foreach ($subjects as $subject) {
                $c = $bySubject->get($subject->id);
                if (!$c) {
                    $rows[] = ['subject' => (string) $subject->name, 'entered' => false, 'absent' => false,
                               'obtained' => '', 'max' => '', 'grade' => ''];
                    continue;
                }
                $absent = (bool) $c->is_absent;
                $rows[] = [
                    'subject'  => (string) $subject->name,
                    'entered'  => true,
                    'absent'   => $absent,
                    'obtained' => $absent ? '' : self::num($c->marks_obtained),
                    'max'      => self::num($c->max_marks),
                    'grade'    => $c->grade_letter,
                ];
            }

            $obtained = (float) $own->filter(fn ($c) => !$c->is_absent)->sum('marks_obtained');
            $max      = (float) $own->sum('max_marks');
            $pct      = $max > 0 ? round($obtained / $max * 100, 2) : 0.0;
            $sat      = $own->contains(fn ($c) => !$c->is_absent);
            // Fail only when the combined marks of all the subjects come under
            // the pass percentage (an absent paper counts as 0 of its maximum).
            $result   = !$sat ? 'Absent' : ($grading->isPass($pct) ? 'Pass' : 'Fail');
            // The scale's word for that same percentage, so it agrees with the result.
            $remark   = !$sat ? 'Absent' : (string) ($grading->remarkFor($pct) ?? '');

            $cards[] = [
                'student_id'   => (int) $student->id,
                'name'         => (string) ($student->full_name ?: ($student->user?->name ?? '')),
                'admission_no' => (string) ($student->admission_no ?? ''),
                'roll_no'      => (string) ($student->roll_no ?? ''),
                'father_name'  => (string) ($student->father_name ?? ''),
                'mother_name'  => (string) ($student->mother_name ?? ''),
                'dob'          => $student->dob ? $student->dob->format('d/m/Y') : '',
                'image'        => $student->user?->image ?: $student->image,
                'rows'         => $rows,
                'entered'      => $own->count(),
                'subjects'     => $subjects->count(),
                'obtained'     => self::num($obtained),
                'max'          => self::num($max),
                'obtained_raw' => $obtained,
                'pct'          => $pct,
                'grade'        => $sat ? ($grading->gradeLetter($pct) ?? 'F') : 'AB',
                'remark'       => $remark,
                'result'       => $result,
                'rank'         => 0,
                'of'           => 0,
            ];
        }

        // Rank on the overall marks, then the percentage; equal marks share a rank.
        $order = $cards;
        usort($order, fn ($a, $b) => [$b['obtained_raw'], $b['pct']] <=> [$a['obtained_raw'], $a['pct']]);
        $rankOf = [];
        $prev = null;
        foreach ($order as $i => $c) {
            $key = $c['obtained_raw'] . '|' . $c['pct'];
            $rankOf[$c['student_id']] = $key === $prev ? $rankOf[$order[$i - 1]['student_id']] : $i + 1;
            $prev = $key;
        }
        foreach ($cards as &$c) {
            $c['rank'] = $rankOf[$c['student_id']];
            $c['of']   = count($cards);
            unset($c['obtained_raw']);
        }
        unset($c);

        return NameOrder::sortArray($cards, fn ($c) => $c['name']);
    }

    /**
     * What the sheet's head prints: the school's logo, name, address and
     * contacts, and the exam with its session and dates.
     */
    public static function head(Organization $org, Exam $exam, Standard $standard, Section $section): array
    {
        // A school under two names: the class's name and logo (SchoolBrand).
        $org = SchoolBrand::org($org, $standard);

        $website = trim((string) (SchoolInfo::where('organization_id', $org->id)->value('website_url') ?? ''));
        $website = $website !== '' ? rtrim(preg_replace('#^https?://#i', '', $website), '/') : '';

        $affiliation = trim((string) ($org->affiliation_no ?? ''));

        return [
            'logo'        => PdfLogo::src($org->logo),
            'school'      => (string) $org->name,
            // Under the name, each its own short line so none breaks at a
            // separator: the address, phone and email, website and affiliation.
            'lines'       => array_values(array_filter([
                trim((string) ($org->address ?? '')),
                implode('  ·  ', array_filter([$org->mobile_number ? 'Ph: ' . $org->mobile_number : null, $org->email ?: null])),
                implode('  ·  ', array_filter([$website ?: null, $affiliation !== '' ? 'Affiliation No: ' . $affiliation : null])),
            ], fn ($l) => $l !== '')),
            'exam'        => (string) $exam->exam_name,
            'session'     => self::session($exam),
            'dates'       => self::dates($exam),
            'class'       => trim((string) $standard->name),
            'section'     => trim((string) $section->name),
            'issued'      => now()->format('d/m/Y'),
            'scale'       => collect(app(GradingService::class)->scale())
                ->map(fn ($b) => $b['grade'] . ' ' . $b['min'] . '-' . $b['max'])
                ->implode('  ·  '),
        ];
    }

    /** The cards as one PDF, a card to each A5 page. */
    public static function pdf(array $head, array $cards): \Barryvdh\DomPDF\PDF
    {
        $fontDir = PdfFonts::cacheDir();
        // Without the embedded faces it still renders, in dompdf's own font.
        $fontCss = rescue(fn () => PdfFonts::faceCss(), '', false);

        return Pdf::loadView('pdf.progress-report', compact('head', 'cards', 'fontCss'))
            ->setPaper('a5', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            // So a school logo kept on S3 loads.
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('fontDir', $fontDir)
            ->setOption('fontCache', $fontDir)
            ->setOption('defaultFont', 'DejaVu Sans');
    }

    /** The PDF's file name: the exam, then the student or the class. */
    public static function fileName(array $head, array $cards): string
    {
        $who = count($cards) === 1
            ? $cards[0]['name']
            : $head['class'] . '-' . $head['section'];

        return 'progress_report_' . Str::slug($head['exam'] . ' ' . $who, '_') . '.pdf';
    }

    /**
     * The exam's session: as typed on the exam, else the academic year (April
     * to March) its first day falls in.
     */
    public static function session(Exam $exam): string
    {
        $typed = trim((string) ($exam->academic_year ?? ''));
        if ($typed !== '') {
            return $typed;
        }
        if (!$exam->start_date) {
            return '';
        }
        $y = (int) $exam->start_date->format('Y');
        $from = (int) $exam->start_date->format('n') >= 4 ? $y : $y - 1;

        return $from . '-' . ($from + 1);
    }

    /** "12/09/2026 - 20/09/2026", one date when it is a day's exam, or "". */
    private static function dates(Exam $exam): string
    {
        $from = $exam->start_date?->format('d/m/Y');
        $to   = $exam->end_date?->format('d/m/Y');

        return match (true) {
            $from && $to && $from !== $to => $from . ' - ' . $to,
            (bool) $from                  => $from,
            (bool) $to                    => $to,
            default                       => '',
        };
    }

    /** 86.50 → "86.5", 40.00 → "40". */
    private static function num($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') ?: '0';
    }
}
