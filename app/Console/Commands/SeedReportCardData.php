<?php

namespace App\Console\Commands;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\SchoolInfo;
use App\Models\Organization;
use App\Models\Student\SectionSubject;
use App\Models\Student\StandardSubject;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Support\AcademicYear;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Stand up the exams a report card expects, and fill them in.
 *
 * The report card prints marks under Term-1 (Unit Test-1, Unit Test-2, Mid
 * Term) and Term-2 (Unit Test-1, Unit Test-2, End Term). Without that set
 * there is nothing for the columns to draw, and the issue screen turns every
 * student away.
 *
 * Everything here is additive and gap-filling: exams are matched by name, term
 * and session before being created, and a mark is only written where none
 * exists. Nothing already recorded is changed.
 *
 * Marks are filled for EVERY published exam the school has, not only the six
 * created here — the issue screen requires a mark for every published exam
 * against every subject, so one stray exam with no marks blocks the whole
 * class.
 *
 * Rows this command writes are tagged (`[seed]` in an exam's description, in
 * an attendance row's remarks) so `lms:clear-seeded-report-card-data` can take
 * them back out again.
 */
class SeedReportCardData extends Command
{
    protected $signature = 'lms:seed-report-card-data
                            {--org= : Only this organization id}
                            {--only-empty : Skip any school that already has exams or marks}
                            {--force : Seed even where the report-card set is already filled}
                            {--no-attendance : Skip the attendance fill}';

    protected $description = 'Create the report-card exam set and fill in marks so report cards can be issued';

    /** Marks the rows this command wrote, so they can be removed again. */
    public const SEED_TAG = '[seed]';

    /** The exam set the report card template is built around. */
    private const EXAM_SET = [
        ['name' => 'Unit Test-1', 'term' => 'Term-1', 'marks' => 20, 'month' => 5],
        ['name' => 'Unit Test-2', 'term' => 'Term-1', 'marks' => 20, 'month' => 7],
        ['name' => 'Mid Term',    'term' => 'Term-1', 'marks' => 80, 'month' => 9],
        ['name' => 'Unit Test-1', 'term' => 'Term-2', 'marks' => 20, 'month' => 11],
        ['name' => 'Unit Test-2', 'term' => 'Term-2', 'marks' => 20, 'month' => 1],
        ['name' => 'End Term',    'term' => 'Term-2', 'marks' => 80, 'month' => 3],
    ];

    /** Fallback subjects, used only for a section that has none of its own. */
    private const DEFAULT_SUBJECTS = [
        'Hindi', 'English', 'Mathematics', 'Science',
        'Social Science', 'Computer', 'General Knowledge',
    ];

    /** Don't bulk-write attendance for a school this size — it isn't a demo. */
    private const ATTENDANCE_STUDENT_CAP = 200;

    public function handle(): int
    {
        $orgIds = $this->option('org')
            ? [(int) $this->option('org')]
            : Organization::pluck('id')->all();

        foreach ($orgIds as $orgId) {
            $this->seedOrganization((int) $orgId);
        }

        return self::SUCCESS;
    }

    /**
     * The masthead reads an affiliation number above the frame on the left and
     * the school's website on the right. Both are ordinary school settings, and
     * a school that has not filled them in prints an empty strip there — which
     * makes the issued card look unfinished rather than like the template.
     *
     * Only blanks are filled, never an answer the school has already given, and
     * both stay editable from the school's own settings screens.
     */
    private function fillSchoolDetails(Organization $org): void
    {
        if (blank($org->affiliation_no)) {
            // Stable for a given school, so re-running does not churn the value.
            $org->forceFill(['affiliation_no' => '21' . str_pad((string) $org->id, 5, '0', STR_PAD_LEFT)])->save();
            $this->line("  affiliation number filled: {$org->affiliation_no}");
        }

        $info = SchoolInfo::firstOrNew(['organization_id' => $org->id]);

        if (blank($info->website_url)) {
            $slug = \Illuminate\Support\Str::slug($org->name ?: ('school-' . $org->id));
            $info->website_url = 'www.' . $slug . '.superlms.in';
            $info->organization_id = $org->id;
            $info->save();
            $this->line("  website filled: {$info->website_url}");
        }
    }

    private function seedOrganization(int $orgId): void
    {
        $org = Organization::find($orgId);
        if (!$org) {
            $this->warn("Organization {$orgId} not found — skipped.");
            return;
        }

        $label = "[{$orgId}] {$org->name}";

        $students = StudentDetail::with('user')
            ->where('organization_id', $orgId)
            ->where('standard_id', '>', 0)
            ->where('section_id', '>', 0)
            ->get();

        if ($students->isEmpty()) {
            $this->line("{$label}: no students with a class and section — skipped.");
            return;
        }

        // Strictest setting: leave alone any school that has started keeping
        // records of its own.
        if ($this->option('only-empty')) {
            if (Exam::where('organization_id', $orgId)->exists()
                || ExamCopy::where('organization_id', $orgId)->exists()) {
                $this->line("{$label}: already has exams or marks — skipped (--only-empty).");
                return;
            }
        }

        $this->info("{$label}: seeding…");

        $this->fillSchoolDetails($org);

        $subjectsBySection = $this->ensureSubjects($orgId, $students);
        $created           = $this->ensureExams($orgId);

        // Every published exam, not just the six — a published exam with no
        // marks against it blocks the issue screen for the whole class.
        $exams = Exam::where('organization_id', $orgId)
            ->where('is_published', true)
            ->orderBy('start_date')
            ->get();

        $marks = $this->fillMarks($orgId, $students, $subjectsBySection, $exams);

        $this->line("  exams created: {$created}, published exams filled: {$exams->count()}, marks written: {$marks}");

        if (!$this->option('no-attendance')) {
            $days = $this->fillAttendance($orgId, $students);
            $this->line("  attendance rows written: {$days}");
        }
    }

    /**
     * Every section needs subjects — the report card reads them from
     * section_subjects. A section that already has some is left alone; one
     * with none gets the default list, reusing the school's existing Subject
     * rows by name so nothing is duplicated.
     *
     * @return array<int, \Illuminate\Support\Collection> section_id => subject ids
     */
    private function ensureSubjects(int $orgId, $students): array
    {
        $pairs = $students
            ->map(fn ($s) => ['standard_id' => (int) $s->standard_id, 'section_id' => (int) $s->section_id])
            ->unique(fn ($p) => $p['standard_id'] . '-' . $p['section_id'])
            ->values();

        $result = [];

        foreach ($pairs as $pair) {
            $existing = SectionSubject::where('organization_id', $orgId)
                ->where('standard_id', $pair['standard_id'])
                ->where('section_id', $pair['section_id'])
                ->pluck('subject_id');

            if ($existing->isNotEmpty()) {
                $result[$pair['section_id']] = $existing;
                continue;
            }

            $subjectIds = collect();
            foreach (self::DEFAULT_SUBJECTS as $index => $name) {
                $subject = Subject::firstOrCreate(
                    ['organization_id' => $orgId, 'name' => $name],
                    ['code' => strtoupper(substr($name, 0, 3)) . ($index + 1), 'is_active' => true],
                );

                StandardSubject::firstOrCreate([
                    'organization_id' => $orgId,
                    'standard_id'     => $pair['standard_id'],
                    'subject_id'      => $subject->id,
                ], ['is_mandatory' => true]);

                SectionSubject::firstOrCreate([
                    'organization_id' => $orgId,
                    'standard_id'     => $pair['standard_id'],
                    'section_id'      => $pair['section_id'],
                    'subject_id'      => $subject->id,
                ]);

                $subjectIds->push($subject->id);
            }

            $result[$pair['section_id']] = $subjectIds;
        }

        return $result;
    }

    /**
     * The six exams the template draws, published and dated inside the running
     * session so they read as Completed rather than Upcoming. Matched on name,
     * term and session first, so re-running adds nothing.
     *
     * @return int how many were newly created
     */
    private function ensureExams(int $orgId): int
    {
        $sessionStart = AcademicYear::start();
        $session      = AcademicYear::label();
        $created      = 0;

        foreach (self::EXAM_SET as $spec) {
            // Months 4-12 fall in the session's first calendar year, 1-3 in the
            // second — the same April-to-March span the label describes.
            $year  = $spec['month'] >= 4 ? $sessionStart->year : $sessionStart->year + 1;
            $start = \Carbon\Carbon::create($year, $spec['month'], 10)->startOfDay();

            $exam = Exam::firstOrCreate(
                [
                    'organization_id' => $orgId,
                    'exam_name'       => $spec['name'],
                    'term'            => $spec['term'],
                    'academic_year'   => $session,
                ],
                [
                    'start_date'    => $start,
                    'end_date'      => $start->copy()->addDays(4),
                    'exam_type'     => 'theory',
                    'total_marks'   => $spec['marks'],
                    'passing_marks' => (int) ceil($spec['marks'] * 0.33),
                    'is_published'  => true,
                    'status'        => 'active',
                    'description'   => self::SEED_TAG . ' ' . $spec['term'] . ' — ' . $spec['name'],
                ],
            );

            if ($exam->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * One mark per student × subject × exam, for every published exam. A
     * student sits at a steady ability level across the year, so their marks
     * read like a real child's rather than noise: a per-student band, jiggled
     * a few percent per paper.
     */
    private function fillMarks(int $orgId, $students, array $subjectsBySection, $exams): int
    {
        if ($exams->isEmpty()) {
            return 0;
        }

        $examIds = $exams->pluck('id')->all();

        // Whatever is already recorded stays; we only fill the gaps.
        $existing = ExamCopy::where('organization_id', $orgId)
            ->whereIn('exam_id', $examIds)
            ->get(['student_detail_id', 'exam_id', 'subject_id'])
            ->map(fn ($c) => $c->student_detail_id . '-' . $c->exam_id . '-' . $c->subject_id)
            ->flip();

        $rows    = [];
        $written = 0;
        $now     = now();

        foreach ($students as $student) {
            $subjectIds = $subjectsBySection[$student->section_id] ?? collect();
            if ($subjectIds->isEmpty()) {
                continue;
            }

            $ability = random_int(58, 92); // this student's typical percentage

            foreach ($subjectIds as $subjectId) {
                foreach ($exams as $exam) {
                    $key = $student->id . '-' . $exam->id . '-' . $subjectId;
                    if ($existing->has($key)) {
                        continue;
                    }

                    // An exam with no total on it still needs a denominator.
                    $max = (float) ($exam->total_marks ?: 100);
                    $pct = max(35, min(99, $ability + random_int(-8, 8)));
                    $obt = round($max * $pct / 100, 2);

                    $rows[] = [
                        'organization_id'   => $orgId,
                        'user_id'           => $student->user_id,
                        'student_detail_id' => $student->id,
                        'standard_id'       => $student->standard_id,
                        'section_id'        => $student->section_id,
                        'subject_id'        => $subjectId,
                        'exam_id'           => $exam->id,
                        'marks_obtained'    => $obt,
                        'max_marks'         => $max,
                        'percentage'        => $max > 0 ? round($obt / $max * 100, 2) : 0,
                        'is_absent'         => false,
                        'is_recheck'        => false,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                    $written++;

                    if (count($rows) >= 500) {
                        DB::table('exam_copies')->insert($rows);
                        $rows = [];
                    }
                }
            }
        }

        if ($rows) {
            DB::table('exam_copies')->insert($rows);
        }

        return $written;
    }

    /**
     * Attendance for the session so far, so the card's attendance line isn't a
     * row of 0/0. Sundays off, roughly nine days in ten present. Skipped
     * entirely for a school that already marks attendance.
     */
    private function fillAttendance(int $orgId, $students): int
    {
        if ($students->count() > self::ATTENDANCE_STUDENT_CAP) {
            $this->line('  attendance skipped — more students than the demo cap.');
            return 0;
        }

        if (StudentAttendance::where('organization_id', $orgId)->exists()) {
            $this->line('  attendance skipped — this school already marks attendance.');
            return 0;
        }

        $cursor     = AcademicYear::start();
        $today      = now()->startOfDay();
        $sessionEnd = AcademicYear::end()->startOfDay();
        // Up to today, but never past the end of the session.
        $end = $today->lt($sessionEnd) ? $today : $sessionEnd;

        $days = [];
        while ($cursor->lte($end) && count($days) < 150) {
            if (!$cursor->isSunday()) {
                $days[] = $cursor->toDateString();
            }
            $cursor = $cursor->copy()->addDay();
        }

        if (empty($days)) {
            return 0;
        }

        $rows    = [];
        $written = 0;
        $now     = now();

        foreach ($students as $student) {
            foreach ($days as $day) {
                $rows[] = [
                    'student_detail_id' => $student->id,
                    'user_id'           => $student->user_id,
                    'organization_id'   => $orgId,
                    'attendance_date'   => $day,
                    'status'            => random_int(1, 100) <= 92 ? 1 : 0,
                    'remarks'           => self::SEED_TAG,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
                $written++;

                if (count($rows) >= 1000) {
                    DB::table('student_attendances')->insert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows) {
            DB::table('student_attendances')->insert($rows);
        }

        return $written;
    }
}
