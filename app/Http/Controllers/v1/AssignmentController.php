<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Admin\Assignment\Assignment;
use App\Models\Admin\Assignment\AssignmentQuestionOption;
use App\Models\Admin\Assignment\AssignmentSubmission;
use App\Models\Admin\Assignment\AssignmentSubmissionAnswer;
use App\Models\Student\StudentDetail;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Student-facing assignments API — the other half of the admin "Assignments"
 * screen. Everything a student submits here is what the admin Responses tab
 * lists, marks and moves through its status.
 */
class AssignmentController extends Controller
{
    protected $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /** POST /assignment/student — the logged-in student's assignments + their own attempt. */
    public function studentAssignments(Request $request)
    {
        try {
            $user   = Auth::user();
            $org    = $user->organization_id;
            $detail = StudentDetail::with(['standard', 'section'])
                ->where('user_id', $user->id)
                ->where('organization_id', $org)
                ->first();

            if (!$detail) {
                return $this->responseService->errorResponse('Student details not found', 404);
            }

            $query = Assignment::with(['standard', 'section', 'subject', 'user'])
                ->withCount('questions')
                ->where('organization_id', $org)
                ->where('is_active', true)
                ->where('standard_id', $detail->standard_id)
                ->where(fn($q) => $q->whereNull('section_id')->orWhere('section_id', $detail->section_id));

            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            // 'open' hides anything not yet started or already past its end date.
            if ($request->boolean('open_only')) {
                $query->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
                    ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()));
            }

            $assignments = $query->orderByDesc('id')->paginate((int) $request->get('per_page', 50));

            $submissions = AssignmentSubmission::where('user_id', $user->id)
                ->whereIn('assignment_id', $assignments->getCollection()->pluck('id'))
                ->get()
                ->keyBy('assignment_id');

            return $this->responseService->success([
                'student_info' => [
                    'id'       => $detail->id,
                    'name'     => $detail->full_name ?? $user->name,
                    'standard' => $detail->standard->name ?? null,
                    'section'  => $detail->section->name ?? null,
                    'roll_no'  => $detail->roll_no,
                ],
                'assignments' => $assignments->getCollection()
                    ->map(fn($a) => $this->formatAssignment($a, $submissions->get($a->id)))
                    ->values(),
                'pagination' => [
                    'current_page' => $assignments->currentPage(),
                    'last_page'    => $assignments->lastPage(),
                    'total'        => $assignments->total(),
                ],
            ], 'Assignments fetched successfully');
        } catch (Exception $e) {
            return $this->responseService->errorResponse('Failed to fetch assignments: ' . $e->getMessage(), 500);
        }
    }

    /** GET /assignment/{id} — one assignment with its questions (correct answers hidden until submitted). */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $assignment = Assignment::with(['standard', 'section', 'subject', 'user', 'questions.options'])
                ->withCount('questions')
                ->where('organization_id', $user->organization_id)
                ->find($id);

            if (!$assignment) {
                return $this->responseService->errorResponse('Assignment not found', 404);
            }

            $submission = AssignmentSubmission::with('answers')
                ->where('assignment_id', $assignment->id)
                ->where('user_id', $user->id)
                ->first();

            // Once the student has submitted (or the window has closed), it is safe
            // to show which option was the right one.
            $revealAnswers = (bool) $submission || $assignment->windowStatus() === 'closed';
            $chosen        = $submission ? $submission->answers->keyBy('assignment_question_id') : collect();

            $payload = $this->formatAssignment($assignment, $submission);

            $payload['questions'] = $assignment->questions->map(function ($q) use ($revealAnswers, $chosen) {
                $answer = $chosen->get($q->id);

                return [
                    'id'            => $q->id,
                    'question_text' => $q->question_text,
                    'marks'         => $q->marks,
                    'chosen_option_id' => $answer?->assignment_question_option_id,
                    'is_correct'    => $revealAnswers ? (bool) $answer?->is_correct : null,
                    'options'       => $q->options->map(fn($o) => [
                        'id'         => $o->id,
                        'text'       => $o->option_text,
                        'is_correct' => $revealAnswers ? (bool) $o->is_correct : null,
                    ])->values(),
                ];
            })->values();

            return $this->responseService->success($payload, 'Assignment fetched successfully');
        } catch (Exception $e) {
            return $this->responseService->errorResponse('Failed to fetch assignment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /assignment/submit/{id} — a student's attempt.
     *
     * Written assignments take `answer_text` and/or `file`; MCQ assignments take
     * `answers` as [{question_id, option_id}] and are graded on the spot.
     */
    public function submit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'answer_text'         => 'nullable|string',
            'file'                => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png|max:10240',
            'answers'             => 'nullable|array',
            'answers.*.question_id' => 'required_with:answers|integer',
            'answers.*.option_id'   => 'required_with:answers|integer',
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $user = Auth::user();

            $assignment = Assignment::with('questions.options')
                ->where('organization_id', $user->organization_id)
                ->where('is_active', true)
                ->find($id);

            if (!$assignment) {
                return $this->responseService->errorResponse('Assignment not found', 404);
            }

            $window = $assignment->windowStatus();

            if ($window === 'upcoming') {
                return $this->responseService->errorResponse('This assignment has not started yet', 422);
            }

            if ($window === 'closed') {
                return $this->responseService->errorResponse('This assignment is closed', 422);
            }

            $detail = StudentDetail::where('user_id', $user->id)
                ->where('organization_id', $user->organization_id)
                ->first(['id']);

            DB::beginTransaction();

            $submission = AssignmentSubmission::firstOrNew([
                'assignment_id' => $assignment->id,
                'user_id'       => $user->id,
            ]);

            $submission->organization_id   = $user->organization_id;
            $submission->student_detail_id = $detail?->id;
            $submission->status            = 'submitted';
            $submission->submitted_at      = now();

            if ($assignment->isMcq()) {
                $submission->answer_text = null;
            } elseif ($request->filled('answer_text')) {
                $submission->answer_text = $request->answer_text;
            }

            if ($request->hasFile('file')) {
                if ($submission->file) {
                    $this->deleteStoredFile($submission->file);
                }

                $path = $request->file('file')->store('student/assignments/files', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');

                $submission->file      = Storage::disk('s3')->url($path);
                $submission->file_name = $request->file('file')->getClientOriginalName();
            }

            $submission->save();

            if ($assignment->isMcq()) {
                $submission->mcq_score = $this->gradeMcq($assignment, $submission, $request->input('answers', []));
                $submission->save();
            }

            DB::commit();

            return $this->responseService->success(
                $this->formatAssignment($assignment->fresh(), $submission->fresh()),
                'Assignment submitted successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseService->errorResponse('Failed to submit assignment: ' . $e->getMessage(), 500);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Replaces the attempt's answers and returns the marks scored. */
    private function gradeMcq(Assignment $assignment, AssignmentSubmission $submission, array $answers): int
    {
        AssignmentSubmissionAnswer::where('assignment_submission_id', $submission->id)->delete();

        $questions = $assignment->questions->keyBy('id');
        $score     = 0;

        foreach ($answers as $answer) {
            $question = $questions->get((int) ($answer['question_id'] ?? 0));

            if (!$question) {
                continue;
            }

            $option = AssignmentQuestionOption::where('assignment_question_id', $question->id)
                ->find((int) ($answer['option_id'] ?? 0));

            $isCorrect = $option ? (bool) $option->is_correct : false;

            if ($isCorrect) {
                $score += (int) $question->marks;
            }

            AssignmentSubmissionAnswer::create([
                'organization_id'               => $assignment->organization_id,
                'assignment_submission_id'      => $submission->id,
                'assignment_question_id'        => $question->id,
                'assignment_question_option_id' => $option?->id,
                'is_correct'                    => $isCorrect,
            ]);
        }

        return $score;
    }

    private function deleteStoredFile(?string $url): void
    {
        if (!$url) {
            return;
        }

        try {
            Storage::disk('s3')->delete(ltrim(parse_url($url, PHP_URL_PATH), '/'));
        } catch (Exception $e) {
            logger()->warning('Assignment submission file delete failed: ' . $e->getMessage());
        }
    }

    private function formatAssignment(Assignment $a, ?AssignmentSubmission $submission): array
    {
        return [
            'id'              => $a->id,
            'title'           => $a->title,
            'description'     => $a->description,
            'type'            => $a->type,
            'submission_mode' => $a->submission_mode,
            'file'            => $a->file,
            'standard'        => $a->standard->name ?? null,
            'section'         => $a->section->name ?? null,
            'subject'         => $a->subject->name ?? null,
            'created_by'      => $a->user->name ?? null,
            'start_date'      => $a->start_date?->toDateTimeString(),
            'end_date'        => $a->end_date?->toDateTimeString(),
            'window_status'   => $a->windowStatus(),
            'total_marks'     => $a->maxMarks(),
            'question_count'  => $a->questions_count ?? $a->questions()->count(),
            'submission'      => $submission ? [
                'id'           => $submission->id,
                'status'       => $submission->status,
                'answer_text'  => $submission->answer_text,
                'file'         => $submission->file,
                'file_name'    => $submission->file_name,
                'marks'        => $submission->marks !== null ? (float) $submission->marks : null,
                'mcq_score'    => $submission->mcq_score,
                'remarks'      => $submission->remarks,
                'submitted_at' => $submission->submitted_at?->toDateTimeString(),
            ] : null,
        ];
    }
}
