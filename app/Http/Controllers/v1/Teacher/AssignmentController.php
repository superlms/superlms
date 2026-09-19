<?php

namespace App\Http\Controllers\v1\Teacher;

use App\Http\Controllers\v1\ApiController;
use App\Models\Admin\Assignment\Assignment;
use App\Models\Admin\Assignment\AssignmentQuestion;
use App\Models\Admin\Assignment\AssignmentQuestionOption;
use App\Models\Admin\Assignment\AssignmentSubmission;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\Teacher\TeacherSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * A teacher's Assignments in the app — the admin panel's Assignments on the
 * same tables and rules, for the classes and subjects the teacher teaches:
 * a written task (text and/or a file back) or a set of MCQs, open from a start
 * to an end date and time, out of a number of marks, and the class's roster
 * with what each student turned in.
 *
 *   GET  teacher/assignments                          the ones still open or to come
 *   GET  teacher/assignments/{id}                     one, with its questions and roster
 *   GET  teacher/assignments/{id}/submissions/{sid}   what a student turned in
 *   POST teacher/assignments                          add
 *   POST teacher/assignments/{id}                     edit (multipart; remove_file drops the file)
 */
class AssignmentController extends ApiController
{
    /** The teacher's assignments whose end date has not passed, soonest due first. */
    public function index()
    {
        [$teacher, $err] = $this->teacher();
        if ($err) return $err;

        $triples = $this->triples($teacher);
        if (!$triples) {
            return $this->success(['assignments' => []], 'Assignments fetched successfully.');
        }

        $orgId = (int) $teacher->organization_id;
        $assignments = Assignment::with(['standard:id,name', 'section:id,name', 'subject', 'user:id,name'])
            ->withCount(['questions', 'submissions'])
            ->where('organization_id', $orgId)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->get()
            ->filter(fn ($a) => $this->teaches($triples, $a))
            ->sortBy(fn ($a) => $a->end_date?->timestamp ?? PHP_INT_MAX)
            ->values();

        $classSizes = $this->classSizes($orgId, $assignments);

        return $this->success([
            'assignments' => $assignments->map(fn ($a) => $this->format($a) + [
                'class_size'      => $classSizes[$a->standard_id . '-' . ($a->section_id ?? 0)] ?? 0,
                'submitted_count' => (int) $a->submissions_count,
            ])->all(),
        ], 'Assignments fetched successfully.');
    }

    /** One assignment — its questions with the right answers — and the class's roster. */
    public function show($id)
    {
        [$teacher, $err] = $this->teacher();
        if ($err) return $err;

        $assignment = $this->findOwn($teacher, $id);
        if (!$assignment) return $this->error('Assignment not found.', 404);

        $assignment->load(['questions.options']);
        $students = StudentDetail::where('organization_id', $assignment->organization_id)
            ->where('standard_id', $assignment->standard_id)
            ->when($assignment->section_id, fn ($q) => $q->where('section_id', $assignment->section_id))
            ->orderByRaw('CAST(roll_no AS UNSIGNED), full_name')
            ->get(['id', 'user_id', 'full_name', 'roll_no', 'image']);
        $submissions = AssignmentSubmission::where('assignment_id', $assignment->id)->get()->keyBy('user_id');

        $roster = $students->map(function ($st) use ($submissions) {
            $sub = $st->user_id ? $submissions->get($st->user_id) : null;

            return [
                'student_detail_id' => $st->id,
                'name'              => $st->full_name,
                'roll_no'           => $st->roll_no,
                'image'             => $st->image,
                'status'            => $sub ? $sub->status : 'pending',
                'submission_id'     => $sub?->id,
                'submitted_at'      => $sub?->submitted_at?->toDateTimeString(),
                'marks'             => $sub && $sub->marks !== null ? (float) $sub->marks : null,
                'mcq_score'         => $sub?->mcq_score,
            ];
        })->values();

        $submitted = $roster->where('status', '!=', 'pending')->count();

        return $this->success($this->format($assignment) + [
            'questions' => $assignment->questions->map(fn ($q) => [
                'id'            => $q->id,
                'question_text' => $q->question_text,
                'marks'         => (int) $q->marks,
                'options'       => $q->options->map(fn ($o) => [
                    'id'         => $o->id,
                    'text'       => $o->option_text,
                    'is_correct' => (bool) $o->is_correct,
                ])->values(),
            ])->values(),
            'stats' => [
                'total'     => $roster->count(),
                'submitted' => $submitted,
                'pending'   => $roster->count() - $submitted,
                'reviewed'  => $roster->whereIn('status', ['reviewed', 'approved'])->count(),
            ],
            'roster' => $roster,
        ], 'Assignment fetched successfully.');
    }

    /** What one student turned in: their text and file, or each MCQ with their choice and the right one. */
    public function submission($id, $submissionId)
    {
        [$teacher, $err] = $this->teacher();
        if ($err) return $err;

        $assignment = $this->findOwn($teacher, $id);
        if (!$assignment) return $this->error('Assignment not found.', 404);

        $sub = AssignmentSubmission::with(['student:id,full_name,roll_no,image', 'answers'])
            ->where('assignment_id', $assignment->id)
            ->find($submissionId);
        if (!$sub) return $this->error('Submission not found.', 404);

        $assignment->load('questions.options');
        $chosen = $sub->answers->keyBy('assignment_question_id');

        return $this->success([
            'id'           => $sub->id,
            'assignment'   => $this->format($assignment),
            'student'      => [
                'id'      => $sub->student?->id,
                'name'    => $sub->student?->full_name,
                'roll_no' => $sub->student?->roll_no,
                'image'   => $sub->student?->image,
            ],
            'status'       => $sub->status,
            'answer_text'  => $sub->answer_text,
            'file'         => $sub->file,
            'file_name'    => $sub->file_name ? rawurldecode($sub->file_name) : null,
            'marks'        => $sub->marks !== null ? (float) $sub->marks : null,
            'mcq_score'    => $sub->mcq_score,
            'remarks'      => $sub->remarks,
            'submitted_at' => $sub->submitted_at?->toDateTimeString(),
            'answers'      => $assignment->questions->map(function ($q) use ($chosen) {
                $answer = $chosen->get($q->id);

                return [
                    'question_id'      => $q->id,
                    'question_text'    => $q->question_text,
                    'marks'            => (int) $q->marks,
                    'chosen_option_id' => $answer?->assignment_question_option_id,
                    'is_correct'       => (bool) $answer?->is_correct,
                    'options'          => $q->options->map(fn ($o) => [
                        'id'         => $o->id,
                        'text'       => $o->option_text,
                        'is_correct' => (bool) $o->is_correct,
                    ])->values(),
                ];
            })->values(),
        ], 'Submission fetched successfully.');
    }

    public function store(Request $request)
    {
        return $this->save($request, null);
    }

    public function update(Request $request, $id)
    {
        return $this->save($request, $id);
    }

    // ── Saving, as the admin panel saves ──────────────────────────────────────

    private function save(Request $request, $id)
    {
        [$teacher, $err] = $this->teacher();
        if ($err) return $err;

        $questions = $request->input('questions', []);
        if (is_string($questions)) {
            $questions = json_decode($questions, true) ?: [];
        }

        $validator = Validator::make($request->all(), [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
            'subject_id'  => 'required|integer',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|in:written,mcq',
            'submission_mode' => 'nullable|in:text,file,both',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'total_marks' => 'nullable|integer|min:0|max:10000',
            'is_active'   => 'nullable|boolean',
            'file'        => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png|max:5120',
        ], [
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
            'file.max'                => 'Attachment must be 5 MB or smaller.',
        ]);
        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $triples = $this->triples($teacher);
        $triple = (int) $request->standard_id . '-' . (int) $request->section_id . '-' . (int) $request->subject_id;
        if (!in_array($triple, $triples, true)) {
            return $this->error('You don’t teach this subject in this class.', 403);
        }

        $isMcq = $request->type === 'mcq';
        if ($isMcq && ($problem = $this->questionProblem($questions))) {
            return $this->error($problem, 422);
        }

        $orgId = (int) $teacher->organization_id;
        $assignment = null;
        if ($id !== null) {
            $assignment = $this->findOwn($teacher, $id);
            if (!$assignment) return $this->error('Assignment not found.', 404);
        }

        try {
            DB::beginTransaction();

            $data = [
                'organization_id' => $orgId,
                'standard_id'     => (int) $request->standard_id,
                'section_id'      => (int) $request->section_id,
                'subject_id'      => (int) $request->subject_id,
                'title'           => $request->title,
                'description'     => $request->filled('description') ? $request->description : null,
                'type'            => $request->type,
                'submission_mode' => $isMcq ? 'text' : ($request->submission_mode ?: 'both'),
                'start_date'      => $request->start_date,
                'end_date'        => $request->end_date,
                'total_marks'     => (int) ($request->total_marks ?: 0),
                'is_active'       => $request->has('is_active') ? $request->boolean('is_active') : true,
            ];

            $assignment ??= new Assignment(['user_id' => $teacher->user_id]);

            // Attachment: replace it, drop it, or leave it alone.
            if ($request->hasFile('file')) {
                $this->deleteStoredFile($assignment->file);
                $path = $request->file('file')->store('admin/assignments/files', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $data['file'] = Storage::disk('s3')->url($path);
            } elseif ($request->boolean('remove_file') && $assignment->file) {
                $this->deleteStoredFile($assignment->file);
                $data['file'] = null;
            }

            $assignment->fill($data)->save();

            if ($isMcq) {
                $this->syncQuestions($assignment, $orgId, $questions);
            } else {
                // Switching away from MCQ clears the question set.
                $this->wipeQuestions($assignment);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error('Could not save the assignment: ' . $e->getMessage(), 500);
        }

        return $this->show($assignment->id);
    }

    /** Every MCQ row needs text, two filled options and one marked correct — or why not. */
    private function questionProblem(array $questions): ?string
    {
        if (empty($questions)) {
            return 'Add at least one question.';
        }
        foreach (array_values($questions) as $i => $row) {
            $label = 'Q' . ($i + 1);
            if (trim((string) ($row['question_text'] ?? '')) === '') {
                return "{$label}: question text is required.";
            }
            $filled = 0;
            $correct = false;
            foreach ((array) ($row['options'] ?? []) as $opt) {
                if (trim((string) ($opt['text'] ?? '')) === '') {
                    continue;
                }
                $filled++;
                $correct = $correct || !empty($opt['is_correct']);
            }
            if ($filled < 2) {
                return "{$label}: add at least two options.";
            }
            if (!$correct) {
                return "{$label}: mark the correct option.";
            }
        }

        return null;
    }

    private function syncQuestions(Assignment $assignment, int $orgId, array $questions): void
    {
        $keptQuestionIds = [];

        foreach (array_values($questions) as $order => $row) {
            $question = !empty($row['id'])
                ? AssignmentQuestion::where('assignment_id', $assignment->id)->find($row['id'])
                : null;

            $payload = [
                'organization_id' => $orgId,
                'assignment_id'   => $assignment->id,
                'question_text'   => $row['question_text'],
                'marks'           => max(1, (int) (($row['marks'] ?? 1) ?: 1)),
                'order'           => $order,
            ];

            $question ? $question->update($payload) : ($question = AssignmentQuestion::create($payload));

            $keptQuestionIds[] = $question->id;
            $keptOptionIds = [];

            foreach ((array) ($row['options'] ?? []) as $opt) {
                if (trim((string) ($opt['text'] ?? '')) === '') {
                    continue;
                }
                $optionPayload = [
                    'organization_id'        => $orgId,
                    'assignment_question_id' => $question->id,
                    'option_text'            => $opt['text'],
                    'is_correct'             => !empty($opt['is_correct']),
                ];
                $option = !empty($opt['id'])
                    ? AssignmentQuestionOption::where('assignment_question_id', $question->id)->find($opt['id'])
                    : null;
                $option ? $option->update($optionPayload) : ($option = AssignmentQuestionOption::create($optionPayload));
                $keptOptionIds[] = $option->id;
            }

            AssignmentQuestionOption::where('assignment_question_id', $question->id)
                ->whereNotIn('id', $keptOptionIds ?: [0])
                ->delete();
        }

        $dropped = AssignmentQuestion::where('assignment_id', $assignment->id)
            ->whereNotIn('id', $keptQuestionIds ?: [0])
            ->pluck('id');
        if ($dropped->isNotEmpty()) {
            AssignmentQuestionOption::whereIn('assignment_question_id', $dropped)->delete();
            AssignmentQuestion::whereIn('id', $dropped)->delete();
        }
    }

    private function wipeQuestions(Assignment $assignment): void
    {
        $ids = AssignmentQuestion::where('assignment_id', $assignment->id)->pluck('id');
        if ($ids->isNotEmpty()) {
            AssignmentQuestionOption::whereIn('assignment_question_id', $ids)->delete();
            AssignmentQuestion::whereIn('id', $ids)->delete();
        }
    }

    private function deleteStoredFile(?string $url): void
    {
        if (!$url) {
            return;
        }
        try {
            Storage::disk('s3')->delete(ltrim(parse_url($url, PHP_URL_PATH), '/'));
        } catch (\Throwable $e) {
            logger()->warning('Assignment file delete failed: ' . $e->getMessage());
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @return array{0: ?TeacherDetail, 1: ?\Illuminate\Http\JsonResponse} */
    private function teacher(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole('teacher')) return [null, $err];

        $teacher = TeacherDetail::where('user_id', $user->id)->first();
        if (!$teacher) return [null, $this->error('Teacher profile not found.', 404)];
        $teacher->organization_id = $teacher->organization_id ?: $user->organization_id;

        return [$teacher, null];
    }

    /** "standard-section-subject" for every class and subject the teacher teaches (timetable or assigned). */
    private function triples(TeacherDetail $teacher): array
    {
        $pick = fn ($q) => $q->where('teacher_detail_id', $teacher->id)
            ->where('organization_id', $teacher->organization_id)
            ->get(['standard_id', 'section_id', 'subject_id']);

        return $pick(TeacherTimeTable::query())->concat($pick(TeacherSubject::query()))
            ->filter(fn ($r) => $r->standard_id && $r->section_id && $r->subject_id)
            ->map(fn ($r) => (int) $r->standard_id . '-' . (int) $r->section_id . '-' . (int) $r->subject_id)
            ->unique()->values()->all();
    }

    /** Whether an assignment falls in one of the teacher's classes and subjects (a whole-class one in any section). */
    private function teaches(array $triples, Assignment $a): bool
    {
        if ($a->section_id) {
            return in_array("{$a->standard_id}-{$a->section_id}-{$a->subject_id}", $triples, true);
        }
        foreach ($triples as $t) {
            [$std, , $sub] = explode('-', $t);
            if ((int) $std === (int) $a->standard_id && (int) $sub === (int) $a->subject_id) {
                return true;
            }
        }

        return false;
    }

    private function findOwn(TeacherDetail $teacher, $id): ?Assignment
    {
        $a = Assignment::with(['standard:id,name', 'section:id,name', 'subject', 'user:id,name'])
            ->withCount('questions')
            ->where('organization_id', $teacher->organization_id)
            ->find($id);

        return $a && $this->teaches($this->triples($teacher), $a) ? $a : null;
    }

    /** Students per class-section of these assignments ("standard-section" → count; section 0 = whole class). */
    private function classSizes(int $orgId, $assignments): array
    {
        $sizes = [];
        foreach ($assignments->unique(fn ($a) => $a->standard_id . '-' . ($a->section_id ?? 0)) as $a) {
            $sizes[$a->standard_id . '-' . ($a->section_id ?? 0)] = StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $a->standard_id)
                ->when($a->section_id, fn ($q) => $q->where('section_id', $a->section_id))
                ->count();
        }

        return $sizes;
    }

    private function format(Assignment $a): array
    {
        return [
            'id'              => $a->id,
            'title'           => $a->title,
            'description'     => $a->description,
            'type'            => $a->type,
            'submission_mode' => $a->submission_mode,
            'file'            => $a->file,
            'standard_id'     => $a->standard_id,
            'standard'        => $a->standard->name ?? null,
            'section_id'      => $a->section_id,
            'section'         => $a->section->name ?? null,
            'subject_id'      => $a->subject_id,
            'subject'         => $a->subject->name ?? null,
            'subject_image'   => $a->subject?->iconUrl(),
            'created_by'      => $a->user->name ?? null,
            'start_date'      => $a->start_date?->toDateTimeString(),
            'end_date'        => $a->end_date?->toDateTimeString(),
            'window_status'   => $a->windowStatus(),
            'total_marks'     => $a->maxMarks(),
            'marks_set'       => (int) $a->total_marks,
            'question_count'  => $a->questions_count ?? $a->questions()->count(),
            'is_active'       => (bool) $a->is_active,
        ];
    }
}
