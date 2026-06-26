<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamSyllabusChapter;
use App\Models\Admin\Seating\SeatAssignment;
use App\Models\Admin\Seating\SeatingPlan;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\AdmitCard;
use App\Models\Student\Chapter;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use App\Models\Teacher\TeacherSubject;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExamController extends ApiController
{
    /**
     * GET /api/v1/exams
     *
     * List published exams for the school.
     * Filters: academic_year, exam_type, search
     */
    public function index(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $query = Exam::where('organization_id', $user->organization_id)
            ->where('is_published', true)
            ->when($request->filled('academic_year'), fn($q) => $q->where('academic_year', $request->academic_year))
            ->when($request->filled('exam_type'),     fn($q) => $q->where('exam_type', $request->exam_type))
            ->when($request->filled('term'),          fn($q) => $q->where('term', $request->term))
            ->when($request->filled('search'),        fn($q) => $q->where('exam_name', 'like', '%' . $request->search . '%'))
            ->latest('start_date');

        // Students/teachers only see exams that include their own assigned
        // subjects (student class+section, teacher timetable subjects). Exams
        // are not directly tied to a class — the link is via the syllabus rows.
        $scopedIds = $this->scopedExamIds($user);
        if (is_array($scopedIds)) {
            $query->whereIn('id', $scopedIds);
        }

        $exams = $query->paginate((int) $request->get('per_page', 20));

        $items = $exams->getCollection()->map(fn($e) => $this->formatExam($e));

        return $this->paginated($items, $this->paginationMeta($exams), 'Exams fetched successfully.');
    }

    /**
     * GET /api/v1/exams/{id}
     *
     * Single exam detail + syllabus scoped to caller (student class/section or teacher assignments).
     */
    public function show(int $id, Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $exam = Exam::where('organization_id', $user->organization_id)
            ->where('is_published', true)
            ->find($id);

        if (!$exam) {
            return $this->error('Exam not found.', 404);
        }

        $data             = $this->formatExam($exam, full: true);
        $data['syllabus'] = $this->getExamSyllabusForUser($exam, $user, $request);

        return $this->success($data, 'Exam fetched successfully.');
    }

    /**
     * GET /api/v1/exams/{id}/syllabus
     *
     * Just the syllabus chapters+topics for an exam, scoped to the caller.
     * Optional ?subject_id= to filter to one subject.
     */
    public function syllabus(int $id, Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $exam = Exam::where('organization_id', $user->organization_id)
            ->where('is_published', true)
            ->find($id);

        if (!$exam) {
            return $this->error('Exam not found.', 404);
        }

        return $this->success(
            $this->getExamSyllabusForUser($exam, $user, $request),
            'Exam syllabus fetched successfully.'
        );
    }

    /**
     * GET /api/v1/exams/{id}/admit-card
     *
     * Student-only. Returns the admit card the admin has *issued* for this
     * student + the selected exam — including the subject schedule (with each
     * subject's total / passing marks), the exam-level total/passing marks and a
     * `pdf_url` the app can preview (book-style) or download.
     *
     * 404 "Admit card has not been issued…" when no active card exists yet.
     */
    public function admitCard(int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($user->role !== 'user') {
            return $this->error('Only students can access admit cards.', 403);
        }

        $exam = Exam::where('organization_id', $user->organization_id)
            ->where('is_published', true)
            ->find($id);
        if (!$exam) {
            return $this->error('Exam not found.', 404);
        }

        $student = StudentDetail::with(['standard', 'section', 'organization', 'user'])
            ->where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();
        if (!$student) {
            return $this->error('Student details not found.', 404);
        }

        $admitCard = AdmitCard::with(['organization'])
            ->where('student_detail_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('status', 'active')
            ->latest()
            ->first();
        if (!$admitCard) {
            return $this->error('Admit card has not been issued for this exam yet.', 404);
        }

        $admitCard->seating_label = $this->resolveSeating($admitCard);

        return $this->success(
            $this->formatAdmitCard($admitCard, $exam, $student),
            'Admit card fetched successfully.'
        );
    }

    /**
     * GET /api/v1/exams/{id}/admit-card/pdf
     *
     * Student-only. Streams the issued admit card as a PDF (reuses the same
     * dompdf layout the admin panel prints). The app opens this URL in its
     * in-app PDF reader (with the Sanctum bearer header) for a full,
     * book-style preview, and downloads the same bytes to the device.
     */
    public function admitCardPdf(int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($user->role !== 'user') {
            return $this->error('Only students can access admit cards.', 403);
        }

        $exam = Exam::where('organization_id', $user->organization_id)
            ->where('is_published', true)
            ->find($id);
        if (!$exam) {
            return $this->error('Exam not found.', 404);
        }

        $student = StudentDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();
        if (!$student) {
            return $this->error('Student details not found.', 404);
        }

        $admitCard = AdmitCard::with([
            'studentDetail.standard',
            'studentDetail.section',
            'organization',
            'exam',
        ])
            ->where('student_detail_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('status', 'active')
            ->latest()
            ->first();
        if (!$admitCard) {
            return $this->error('Admit card has not been issued for this exam yet.', 404);
        }

        $admitCard->seating_label = $this->resolveSeating($admitCard);

        $pdf = Pdf::loadView('admin.admit-card-pdf', [
            'admitCard'    => $admitCard,
            'organization' => $admitCard->organization,
            'isPdf'        => true,
        ])->setPaper('a4', 'portrait')->setOption('isRemoteEnabled', true);

        $name = str_replace(' ', '_', $admitCard->student_name ?: ($student->full_name ?? 'admit_card'));

        return $pdf->stream("Admit_Card_{$name}.pdf");
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Exam IDs relevant to the caller.
     *
     * Scoping rule:
     *   - An exam that has NO syllabus rows in this org is treated as
     *     "school-wide" (admin hasn't added per-class syllabus yet) and is
     *     visible to every student/teacher. This is critical because admins
     *     usually publish the exam first and add syllabus later — otherwise
     *     freshly-added exams would be invisible to everyone.
     *   - An exam that HAS syllabus rows is filtered:
     *       Student → must have syllabus for their standard (+ section)
     *       Teacher → must have syllabus for one of their (class, subject)
     *                 pairs (from timetable or directly-assigned subjects)
     *
     * Returns null for non student/teacher roles (no scoping → all exams), or
     * an array of exam ids the caller is allowed to see.
     */
    private function scopedExamIds($user): ?array
    {
        $orgId = $user->organization_id;

        // Exams in this org that have at least one syllabus row. Exams NOT in
        // this set are unscoped → visible to everyone.
        $examsWithSyllabus = ExamSyllabusChapter::where('organization_id', $orgId)
            ->distinct()
            ->pluck('exam_id')
            ->all();

        $unboundExamIds = Exam::where('organization_id', $orgId)
            ->whereNotIn('id', $examsWithSyllabus)
            ->pluck('id')
            ->all();

        if ($user->role === 'user') {
            $student = StudentDetail::where('user_id', $user->id)->first(['standard_id', 'section_id']);
            if (!$student) return $unboundExamIds;

            $base = ExamSyllabusChapter::where('organization_id', $orgId)
                ->where('standard_id', $student->standard_id);
            if ($student->section_id) {
                $base->where(fn($q) => $q->where('section_id', $student->section_id)->orWhereNull('section_id'));
            }

            $matching = $base->distinct()->pluck('exam_id')->all();
            return array_values(array_unique(array_merge($matching, $unboundExamIds)));
        }

        if ($user->role === 'teacher') {
            $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
            if (!$teacher) return $unboundExamIds;

            $pairs = collect()
                ->merge(TeacherTimeTable::where('teacher_detail_id', $teacher->id)->get(['standard_id', 'subject_id']))
                ->merge(TeacherSubject::where('teacher_detail_id', $teacher->id)->get(['standard_id', 'subject_id']));

            $assignments = $pairs
                ->filter(fn($r) => $r->standard_id && $r->subject_id)
                ->map(fn($r) => $r->standard_id . '-' . $r->subject_id)
                ->unique()
                ->values()
                ->toArray();

            if (empty($assignments)) return $unboundExamIds;

            $matching = ExamSyllabusChapter::where('organization_id', $orgId)
                ->whereIn(\DB::raw('CONCAT(standard_id, "-", subject_id)'), $assignments)
                ->distinct()
                ->pluck('exam_id')
                ->all();

            return array_values(array_unique(array_merge($matching, $unboundExamIds)));
        }

        return null; // admin / accounts / other → no scoping
    }

    private function formatExam(Exam $e, bool $full = false): array
    {
        $now    = now();
        $status = match (true) {
            $e->start_date > $now  => 'upcoming',
            $e->end_date   < $now  => 'completed',
            default                => 'ongoing',
        };

        $data = [
            'id'            => $e->id,
            'exam_name'     => $e->exam_name,
            'term'          => $e->term,
            'exam_type'     => $e->exam_type,
            'academic_year' => $e->academic_year,
            'start_date'    => $e->start_date?->format('Y-m-d'),
            'end_date'      => $e->end_date?->format('Y-m-d'),
            'status'        => $status,
            'total_marks'   => $e->total_marks,
            'passing_marks' => $e->passing_marks,
        ];

        if ($full) {
            $data['description']    = $e->description;
            $data['status_label']   = $status;
            $data['days_remaining'] = $e->start_date > $now ? (int) $now->diffInDays($e->start_date) : 0;
        }

        return $data;
    }

    /**
     * Build syllabus[] for an exam scoped to the caller:
     *   - Student → their standard_id + section_id
     *   - Teacher → all (standard, subject) combos in their TeacherSubject assignments
     * Optional ?subject_id filter applies to both.
     *
     * Returns: [ { subject_id, subject_name, standard_id, standard_name,
     *             chapter_count, chapters: [ { id, name, description, order,
     *             topics: [ { id, topic_name } ] } ] } ]
     */
    private function getExamSyllabusForUser(Exam $exam, $user, Request $request): array
    {
        $orgId     = $user->organization_id;
        $subjectId = $request->get('subject_id');

        $base = ExamSyllabusChapter::where('organization_id', $orgId)
            ->where('exam_id', $exam->id);

        if ($user->role === 'user') {
            $student = StudentDetail::where('user_id', $user->id)->first(['id', 'standard_id', 'section_id']);
            if (!$student) return [];

            $base->where('standard_id', $student->standard_id);
            if ($student->section_id) {
                $base->where(function ($q) use ($student) {
                    $q->where('section_id', $student->section_id)
                      ->orWhereNull('section_id');
                });
            }
        } elseif ($user->role === 'teacher') {
            $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
            if (!$teacher) return [];

            // Teachers are assigned subjects primarily through the timetable, so
            // build the (standard_id, subject_id) pairs from the timetable AND any
            // directly-assigned subjects. Using only TeacherSubject left teachers
            // with no syllabus because assignments live in the timetable.
            $pairs = collect()
                ->merge(TeacherTimeTable::where('teacher_detail_id', $teacher->id)
                    ->get(['standard_id', 'subject_id']))
                ->merge(TeacherSubject::where('teacher_detail_id', $teacher->id)
                    ->get(['standard_id', 'subject_id']));

            $assignments = $pairs
                ->filter(fn($r) => $r->standard_id && $r->subject_id)
                ->map(fn($r) => $r->standard_id . '-' . $r->subject_id)
                ->unique()
                ->values()
                ->toArray();

            if (empty($assignments)) return [];

            $base->whereIn(\DB::raw('CONCAT(standard_id, "-", subject_id)'), $assignments);
        }

        if ($subjectId) {
            $base->where('subject_id', $subjectId);
        }

        $rows = $base->with(['standard:id,name', 'subject:id,name'])
            ->get(['exam_id', 'standard_id', 'subject_id', 'chapter_id']);

        if ($rows->isEmpty()) return [];

        $chapterIds = $rows->pluck('chapter_id')->unique()->values()->toArray();

        $chapters = Chapter::with(['topics:id,chapter_id,topic_name'])
            ->whereIn('id', $chapterIds)
            ->orderBy('order')
            ->get(['id', 'name', 'description', 'order', 'subject_id', 'standard_id'])
            ->keyBy('id');

        return $rows
            ->groupBy(fn($r) => $r->standard_id . '-' . $r->subject_id)
            ->map(function ($group) use ($chapters) {
                $first = $group->first();
                $chapterList = $group->pluck('chapter_id')
                    ->map(fn($cid) => $chapters->get($cid))
                    ->filter()
                    ->values()
                    ->map(fn($ch) => [
                        'id'          => $ch->id,
                        'name'        => $ch->name,
                        'description' => $ch->description,
                        'order'       => $ch->order,
                        'topics'      => $ch->topics->map(fn($t) => [
                            'id'         => $t->id,
                            'topic_name' => $t->topic_name,
                        ])->values(),
                    ]);

                return [
                    'standard_id'   => $first->standard_id,
                    'standard_name' => $first->standard->name ?? null,
                    'subject_id'    => $first->subject_id,
                    'subject_name'  => $first->subject->name ?? null,
                    'chapter_count' => $chapterList->count(),
                    'chapters'      => $chapterList,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Normalise an issued AdmitCard into the JSON the app renders: exam header,
     * subject schedule (each with its total/passing marks), student + center
     * details, and the PDF url. Marks fall back to the exam-level totals when a
     * subject row doesn't carry its own.
     */
    private function formatAdmitCard(AdmitCard $admitCard, Exam $exam, StudentDetail $student): array
    {
        $now = now();
        $examStatus = match (true) {
            $exam->start_date > $now => 'upcoming',
            $exam->end_date   < $now => 'completed',
            default                  => 'ongoing',
        };

        $examTotal   = $exam->total_marks;
        $examPassing = $exam->passing_marks;

        $subjects = collect($admitCard->subjects ?? [])->map(function ($subject) use ($examTotal, $examPassing) {
            $subjectId   = $subject['subject_id'] ?? null;
            $subjectCode = $subject['subject_code'] ?? (Subject::find($subjectId)?->code);
            $date = $subject['exam_date'] ?? null;
            $time = $subject['exam_time'] ?? null;

            $total   = $subject['total_marks']   ?? $subject['max_marks']  ?? $examTotal;
            $passing = $subject['passing_marks'] ?? $subject['pass_marks'] ?? $examPassing;

            return [
                'subject_id'          => $subjectId,
                'subject_name'        => $subject['subject_name'] ?? 'General',
                'subject_code'        => $subjectCode,
                'exam_date'           => $date,
                'exam_date_formatted' => $date ? Carbon::parse($date)->format('d M, Y') : null,
                'exam_day'            => $date ? Carbon::parse($date)->format('l') : null,
                'exam_time'           => $time,
                'exam_time_formatted' => $time ? Carbon::parse($time)->format('h:i A') : null,
                'exam_duration'       => $subject['exam_duration'] ?? '3 Hours',
                'total_marks'         => $total   !== null ? (float) $total   : null,
                'passing_marks'       => $passing !== null ? (float) $passing : null,
                'status'              => $subject['status'] ?? 'eligible',
            ];
        })->values();

        $org = $admitCard->organization;

        return [
            'id'                   => $admitCard->id,
            'issued'               => true,
            'admit_card_number'    => $admitCard->admit_card_number,
            'issue_date'           => $admitCard->issue_date?->format('Y-m-d'),
            'issue_date_formatted' => $admitCard->issue_date?->format('d M, Y'),
            'status'               => $admitCard->status,
            'pdf_url'              => url("/api/v1/exams/{$exam->id}/admit-card/pdf"),

            'exam' => [
                'id'             => $exam->id,
                'name'           => $exam->exam_name,
                'academic_year'  => $exam->academic_year,
                'exam_type'      => $exam->exam_type,
                'term'           => $exam->term,
                'start_date'     => $exam->start_date?->format('Y-m-d'),
                'end_date'       => $exam->end_date?->format('Y-m-d'),
                'status'         => $examStatus,
                'total_marks'    => $examTotal,
                'passing_marks'  => $examPassing,
                'total_subjects' => $subjects->count(),
            ],

            'subjects' => $subjects,

            'student' => [
                'full_name'        => $student->full_name,
                'admission_no'     => $student->admission_no,
                'roll_no'          => $student->roll_no,
                'roll_number'      => $admitCard->roll_number,
                'exam_roll_number' => $admitCard->exam_roll_number,
                'father_name'      => $admitCard->father_name ?: $student->father_name,
                'mother_name'      => $admitCard->mother_name ?: $student->mother_name,
                'image_url'        => $student->image ? Storage::url($student->image) : ($student->user->image ?? null),
                'class'            => trim(($student->standard->name ?? '') . (($student->section->name ?? '') ? ' - ' . $student->section->name : '')),
            ],

            'exam_center' => [
                'name'           => $admitCard->exam_center,
                'address'        => $admitCard->exam_center_address,
                'reporting_time' => $admitCard->reporting_time ? Carbon::parse($admitCard->reporting_time)->format('h:i A') : null,
                'seat_number'    => $admitCard->seat_number,
                'room_number'    => $admitCard->room_number,
                'seating_label'  => $admitCard->seating_label ?? null,
            ],

            'organization' => [
                'name'     => $org->name ?? null,
                'address'  => $org->address ?? null,
                'phone'    => $org->phone ?? ($org->mobile_number ?? null),
                'email'    => $org->email ?? null,
                'logo_url' => $org->logo ?? null,
            ],

            'exam_rules' => [
                'allowed_items'        => $admitCard->allowed_items ?? [],
                'prohibited_items'     => $admitCard->prohibited_items ?? [],
                'general_instructions' => $admitCard->instructions,
            ],
        ];
    }

    /**
     * Resolve "R(room)/ S(seat)" for the card's exam+student from the seating
     * plan, falling back to the card's own room/seat. Mirrors the admin panel.
     */
    private function resolveSeating(AdmitCard $admitCard): ?string
    {
        $planIds = SeatingPlan::where('organization_id', $admitCard->organization_id)
            ->where('exam_id', $admitCard->exam_id)
            ->pluck('id');

        if ($planIds->isNotEmpty()) {
            $studentUserId = $admitCard->studentDetail?->user_id;

            $assignment = SeatAssignment::with(['room', 'seat'])
                ->whereIn('seating_plan_id', $planIds)
                ->where(function ($q) use ($admitCard, $studentUserId) {
                    $q->where('student_id', $admitCard->student_detail_id);
                    if ($studentUserId) {
                        $q->orWhere('student_id', $studentUserId);
                    }
                })
                ->first();

            if ($assignment) {
                $room = $assignment->room?->room_name;
                $seat = $assignment->seat?->seat_number;
                if ($room && $seat) return 'R(' . $room . ')/ S(' . $seat . ')';
                if ($seat) return 'S(' . $seat . ')';
                if ($room) return 'R(' . $room . ')';
            }
        }

        if ($admitCard->room_number && $admitCard->seat_number) {
            return 'R(' . $admitCard->room_number . ')/ S(' . $admitCard->seat_number . ')';
        }
        if ($admitCard->seat_number) return 'S(' . $admitCard->seat_number . ')';
        if ($admitCard->room_number) return 'R(' . $admitCard->room_number . ')';

        return null;
    }
}
