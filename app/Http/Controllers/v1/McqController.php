<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Mcq\McqOption;
use App\Models\Mcq\McqQuestion;
use App\Models\Mcq\McqUserAnswer;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class McqController extends Controller
{
    protected $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    /**
     * Upload a new quiz/question
     */
    public function uploadQuiz(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            $validated = $request->validate([
                'question_text' => 'required|string',
                'standard_id' => 'required|exists:standards,id',
                'section_id' => 'required|exists:sections,id',
                'chapter_id' => 'nullable|exists:chapters,id',
                'topic_id' => 'nullable|exists:topics,id',
                'time_limit' => 'nullable|integer',
                'options' => 'required|array|min:2',
                'options.*.option_text' => 'required|string',
                'options.*.is_correct' => 'required|boolean'
            ]);

            // Create the question
            $question = McqQuestion::create([
                'organization_id' => $user->organization_id,
                'standard_id' => $validated['standard_id'],
                'section_id' => $validated['section_id'],
                'chapter_id' => $validated['chapter_id'] ?? null,
                'topic_id' => $validated['topic_id'] ?? null,
                'created_by' => $user->id,
                'question_text' => $validated['question_text'],
                'time_limit' => $validated['time_limit'] ?? 60,
                'is_active' => true
            ]);

            // Create options
            foreach ($validated['options'] as $option) {
                McqOption::create([
                    'organization_id' => $user->organization_id,
                    'mcq_question_id' => $question->id,
                    'option_text' => $option['option_text'],
                    'is_correct' => $option['is_correct']
                ]);
            }

            // Load relationships for response
            $question->load('options', 'chapter', 'topic');

            return $this->responseService->success(
                $question,
                'Quiz question uploaded successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * View all quiz questions with filters
     */
    public function viewAllQuizzes(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            $query = McqQuestion::with(['options', 'chapter', 'topic'])
                ->where('organization_id', $user->organization_id)
                ->where('is_active', true);

            // Apply filters
            if ($request->has('standard_id')) {
                $query->where('standard_id', $request->standard_id);
            }

            if ($request->has('section_id')) {
                $query->where('section_id', $request->section_id);
            }

            if ($request->has('chapter_id')) {
                $query->where('chapter_id', $request->chapter_id);
            }

            if ($request->has('topic_id')) {
                $query->where('topic_id', $request->topic_id);
            }

            $questions = $query->paginate($request->per_page ?? 15);

            return $this->responseService->success(
                $questions,
                'Quiz questions retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update an existing quiz question + its options (teacher only).
     */
    public function updateQuiz(Request $request, $questionId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->responseService->errorResponse('Authentication required', 401);
            }
            if ($user->role !== 'teacher') {
                return $this->responseService->errorResponse('Only teachers can update quizzes', 403);
            }

            $question = McqQuestion::where('organization_id', $user->organization_id)
                ->find($questionId);
            if (!$question) {
                return $this->responseService->errorResponse('Quiz question not found', 404);
            }

            $validated = $request->validate([
                'question_text'         => 'sometimes|required|string',
                'standard_id'           => 'sometimes|required|exists:standards,id',
                'section_id'            => 'sometimes|required|exists:sections,id',
                'chapter_id'            => 'nullable|exists:chapters,id',
                'topic_id'              => 'nullable|exists:topics,id',
                'time_limit'            => 'nullable|integer',
                'is_active'             => 'sometimes|boolean',
                'options'               => 'sometimes|array|min:2',
                'options.*.option_text' => 'required_with:options|string',
                'options.*.is_correct'  => 'required_with:options|boolean',
            ]);

            $question->update(array_filter([
                'question_text' => $validated['question_text'] ?? $question->question_text,
                'standard_id'   => $validated['standard_id']   ?? $question->standard_id,
                'section_id'    => $validated['section_id']    ?? $question->section_id,
                'chapter_id'    => $request->has('chapter_id') ? $validated['chapter_id'] : $question->chapter_id,
                'topic_id'      => $request->has('topic_id')   ? $validated['topic_id']   : $question->topic_id,
                'time_limit'    => $validated['time_limit']    ?? $question->time_limit,
                'is_active'     => $request->has('is_active')  ? $validated['is_active']   : $question->is_active,
            ], fn($v) => $v !== null));

            // Replace options if provided
            if (!empty($validated['options'])) {
                McqOption::where('mcq_question_id', $question->id)->delete();
                foreach ($validated['options'] as $option) {
                    McqOption::create([
                        'organization_id' => $user->organization_id,
                        'mcq_question_id' => $question->id,
                        'option_text'     => $option['option_text'],
                        'is_correct'      => $option['is_correct'],
                    ]);
                }
            }

            $question->load('options', 'chapter', 'topic');

            return $this->responseService->success($question, 'Quiz question updated successfully');
        } catch (Exception $e) {
            return $this->responseService->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a quiz question + its options/answers (teacher only).
     */
    public function deleteQuiz($questionId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->responseService->errorResponse('Authentication required', 401);
            }
            if ($user->role !== 'teacher') {
                return $this->responseService->errorResponse('Only teachers can delete quizzes', 403);
            }

            $question = McqQuestion::where('organization_id', $user->organization_id)
                ->find($questionId);
            if (!$question) {
                return $this->responseService->errorResponse('Quiz question not found', 404);
            }

            McqOption::where('mcq_question_id', $question->id)->delete();
            McqUserAnswer::where('mcq_question_id', $question->id)->delete();
            $question->delete();

            return $this->responseService->success(null, 'Quiz question deleted successfully');
        } catch (Exception $e) {
            return $this->responseService->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Submit user answer for a question
     */
    public function submitAnswer(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            $validated = $request->validate([
                'mcq_question_id' => 'required|exists:mcq_questions,id',
                'mcq_option_id' => 'required|exists:mcq_options,id',
                'time_taken' => 'required|integer|min:0'
            ]);

            // Get the selected option to check if it's correct
            $selectedOption = McqOption::find($validated['mcq_option_id']);

            // Check if user already answered this question
            $existingAnswer = McqUserAnswer::where('user_id', $user->id)
                ->where('mcq_question_id', $validated['mcq_question_id'])
                ->first();

            if ($existingAnswer) {
                // Update existing answer
                $existingAnswer->update([
                    'mcq_option_id' => $validated['mcq_option_id'],
                    'time_taken' => $validated['time_taken'],
                    'is_correct' => $selectedOption->is_correct
                ]);

                $answer = $existingAnswer;
            } else {
                // Create new answer
                $answer = McqUserAnswer::create([
                    'organization_id' => $user->organization_id,
                    'user_id' => $user->id,
                    'mcq_question_id' => $validated['mcq_question_id'],
                    'mcq_option_id' => $validated['mcq_option_id'],
                    'time_taken' => $validated['time_taken'],
                    'is_correct' => $selectedOption->is_correct
                ]);
            }

            return $this->responseService->success(
                $answer,
                'Answer submitted successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get user's answers for specific questions
     */
    public function getUserAnswers(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            $query = McqUserAnswer::with(['question', 'question.options', 'selectedOption'])
                ->where('user_id', $user->id)
                ->where('organization_id', $user->organization_id);

            if ($request->has('mcq_question_id')) {
                $query->where('mcq_question_id', $request->mcq_question_id);
            }

            $answers = $query->paginate($request->per_page ?? 15);

            // Transform the response to include correct/incorrect details
            $transformedAnswers = $answers->getCollection()->map(function ($answer) {
                $question = $answer->question;
                $selectedOption = $answer->selectedOption;
                $correctOption = $question->options->where('is_correct', true)->first();

                return [
                    'id' => $answer->id,
                    'question_id' => $answer->mcq_question_id,
                    'question_text' => $question->question_text,
                    'selected_option' => [
                        'id' => $selectedOption->id,
                        'text' => $selectedOption->option_text,
                        'is_correct' => (bool)$selectedOption->is_correct,
                    ],
                    'correct_option' => $correctOption ? [
                        'id' => $correctOption->id,
                        'text' => $correctOption->option_text,
                    ] : null,
                    'is_correct' => (bool)$answer->is_correct,
                    'time_taken' => $answer->time_taken,
                    'created_at' => $answer->created_at,
                    'updated_at' => $answer->updated_at,
                ];
            });

            // Replace the original collection with transformed data
            $answers->setCollection($transformedAnswers);

            return $this->responseService->success(
                $answers,
                'User answers retrieved successfully with correctness details'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }
}
