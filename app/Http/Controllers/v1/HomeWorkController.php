<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Admin\HomeWork;
use App\Models\Admin\HomeWorkCompletion;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HomeWorkController extends Controller
{
    protected $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    // Create homework (teacher) — supports an optional file attachment.
    public function uploadHomeWork(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
            'subject_id'  => 'required|integer',
            'title'       => 'required_without:name|nullable|string|max:255',
            'name'        => 'required_without:title|nullable|string|max:255',
            'description' => 'nullable|string',
            'file'        => 'nullable|file|mimes:pdf,jpeg,jpg,png,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse($validator->errors()->first(), 422);
        }

        $user = Auth::user();

        // A teacher may only post homework for a (class, section, subject) that is
        // assigned to them in the timetable.
        if (($user->role ?? null) === 'teacher'
            && !$this->teaches($user, $request->standard_id, $request->section_id, $request->subject_id)) {
            return $this->responseService->errorResponse(
                'You can only add homework for a class & subject assigned to you in the timetable.',
                403
            );
        }

        DB::beginTransaction();
        try {
            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('admin/homework', 's3');
                if ($filePath === false) {
                    return $this->responseService->errorResponse('File upload failed.', 500);
                }
                Storage::disk('s3')->setVisibility($filePath, 'public');
            }

            $homework = HomeWork::create([
                'organization_id' => Auth::user()->organization_id,
                'user_id'         => Auth::id(),
                'standard_id'     => $request->standard_id,
                'section_id'      => $request->section_id,
                'subject_id'      => $request->subject_id,
                'title'           => $request->input('title', $request->name),
                'description'     => $request->description,
                'file'            => $filePath,
            ]);

            DB::commit();

            $homework->load(['standard', 'section', 'subject', 'user']);

            return $this->responseService->success(
                $this->formatHomework($homework),
                'Homework created successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseService->errorResponse(
                'Failed to create homework: ' . $e->getMessage(),
                500
            );
        }
    }

    // Update homework. Fields left out keep their value; a sent (even empty)
    // description replaces it; remove_file=1 drops the attachment.
    public function updateHomeWork(Request $request, $chapterId)
    {
        $validator = Validator::make($request->all(), [
            'standard_id' => 'nullable|integer',
            'section_id'  => 'nullable|integer',
            'subject_id'  => 'nullable|integer',
            'title'       => 'nullable|string|max:255',
            'name'        => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'remove_file' => 'nullable|boolean',
            'file'        => 'nullable|file|mimes:pdf,jpeg,jpg,png,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $user     = Auth::user();
            $homework = HomeWork::where('organization_id', $user->organization_id)
                ->findOrFail($chapterId);

            $isTeacher = ($user->role ?? null) === 'teacher';

            // A teacher edits only the homework they posted.
            if ($isTeacher && (int) $homework->user_id !== (int) $user->id) {
                return $this->responseService->errorResponse('You can only edit homework you posted.', 403);
            }

            $data = [
                'standard_id' => $request->standard_id ?? $homework->standard_id,
                'section_id'  => $request->section_id ?? $homework->section_id,
                'subject_id'  => $request->subject_id ?? $homework->subject_id,
                'title'       => $request->input('title', $request->input('name', $homework->title)),
                'description' => $request->has('description') ? $request->description : $homework->description,
            ];

            if (!trim((string) $data['title'])) {
                return $this->responseService->errorResponse('Please enter a homework title.', 422);
            }

            // Moving it to another class must still be one they teach.
            $movedClass = (int) $data['standard_id'] !== (int) $homework->standard_id
                || (int) $data['section_id'] !== (int) $homework->section_id
                || (int) $data['subject_id'] !== (int) $homework->subject_id;

            if ($isTeacher && $movedClass
                && !$this->teaches($user, $data['standard_id'], $data['section_id'], $data['subject_id'])) {
                return $this->responseService->errorResponse(
                    'You can only set homework for a class & subject assigned to you in the timetable.',
                    403
                );
            }

            $oldFile = $homework->file;

            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('admin/homework', 's3');
                if ($path === false) {
                    return $this->responseService->errorResponse('File upload failed.', 500);
                }
                Storage::disk('s3')->setVisibility($path, 'public');
                $data['file'] = $path;
            } elseif ($request->boolean('remove_file')) {
                $data['file'] = null;
            }

            $homework->update($data);

            if ($oldFile && array_key_exists('file', $data)) {
                $this->deleteFile($oldFile);
            }
            $homework->load(['standard', 'section', 'subject', 'user']);

            return $this->responseService->success(
                $this->formatHomework($homework),
                'Homework updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->responseService->errorResponse('homework not found', 404);
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to update homework: ' . $e->getMessage(),
                500
            );
        }
    }

    // Delete homework
    public function destroyHomeWork($chapterId)
    {
        DB::beginTransaction();
        try {
            $user     = Auth::user();
            $homework = HomeWork::where('organization_id', $user->organization_id)
                ->findOrFail($chapterId);

            // A teacher deletes only the homework they posted.
            if (($user->role ?? null) === 'teacher' && (int) $homework->user_id !== (int) $user->id) {
                DB::rollBack();
                return $this->responseService->errorResponse('You can only delete homework you posted.', 403);
            }

            $file = $homework->file;
            $homework->delete();
            DB::commit();

            if ($file) $this->deleteFile($file);

            return $this->responseService->success([], 'Homework deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->responseService->errorResponse('homework not found', 404);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseService->errorResponse(
                'Failed to delete homework: ' . $e->getMessage(),
                500
            );
        }
    }

    // Get single homework
    public function showSingleHomeWork($homeworkId)
    {
        try {
            $homework = HomeWork::with(['standard', 'section', 'subject', 'user'])
                ->where('organization_id', Auth::user()->organization_id)
                ->findOrFail($homeworkId);

            return $this->responseService->success(
                $this->formatHomework($homework),
                'homework retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->responseService->errorResponse('homework not found', 404);
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve homework: ' . $e->getMessage(),
                500
            );
        }
    }

    // List the authenticated TEACHER's own homework (defaults to the last 15 days).
    public function allHomeWork(Request $request)
    {
        try {
            $user = Auth::user();

            $query = HomeWork::with(['standard', 'section', 'subject', 'user'])
                ->where('organization_id', $user->organization_id)
                ->where('user_id', $user->id);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhereHas('standard', fn($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('section', fn($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('subject', fn($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            }

            if ($request->filled('standard_id')) $query->where('standard_id', $request->standard_id);
            if ($request->filled('section_id'))  $query->where('section_id', $request->section_id);
            if ($request->filled('subject_id'))  $query->where('subject_id', $request->subject_id);

            // Default window: last N days (15 unless overridden, or a specific date).
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            } elseif (!$request->filled('search')) {
                $days = (int) $request->get('days', 15);
                $query->where('created_at', '>=', now()->subDays(max(1, $days)));
            }

            $query->orderBy('created_at', 'desc');

            $perPage   = (int) $request->get('per_page', 100);
            $homeworks = $query->paginate($perPage);

            // Each homework carries its class's period in the teacher's timetable,
            // and a day's homework runs in period order (latest day first).
            $periods = $this->teacherPeriods($user);
            $list    = $this->inPeriodOrder($homeworks->getCollection()
                ->map(fn($h) => $this->formatHomework($h) + $this->periodOf($h, $periods)));

            return $this->responseService->success(
                [
                    'homeworks'  => $list,
                    'pagination' => [
                        'current_page' => $homeworks->currentPage(),
                        'last_page'    => $homeworks->lastPage(),
                        'per_page'     => $homeworks->perPage(),
                        'total'        => $homeworks->total(),
                    ],
                ],
                'Homeworks retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve homeworks: ' . $e->getMessage(),
                500
            );
        }
    }

    // Homework for the authenticated STUDENT's class (defaults to the last 15 days).
    public function studentHomeWork(Request $request)
    {
        try {
            $user           = Auth::user();
            $organizationId = $user->organization_id;

            $studentDetail = StudentDetail::with(['standard', 'section'])
                ->where('user_id', $user->id)
                ->where('organization_id', $organizationId)
                ->first();

            if (!$studentDetail) {
                return $this->responseService->errorResponse('Student details not found', 404);
            }

            $query = HomeWork::with(['standard', 'section', 'subject', 'user'])
                ->where('organization_id', $organizationId)
                ->where('standard_id', $studentDetail->standard_id)
                ->where('section_id', $studentDetail->section_id);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhereHas('subject', fn($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            }

            if ($request->filled('subject_id')) $query->where('subject_id', $request->subject_id);

            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
            } elseif (!$request->filled('search')) {
                $days = (int) $request->get('days', 15);
                $query->where('created_at', '>=', now()->subDays(max(1, $days)));
            }

            $query->orderBy('created_at', 'desc');

            $perPage   = (int) $request->get('per_page', 100);
            $homeworks = $query->paginate($perPage);

            // Which of these has this student already marked complete?
            $completedIds = HomeWorkCompletion::where('user_id', $user->id)
                ->whereIn('home_work_id', $homeworks->getCollection()->pluck('id'))
                ->pluck('home_work_id')
                ->flip();

            // Each homework carries its period in the class timetable, and a
            // day's homework runs in period order (latest day first).
            $periods = $this->periods([
                'organization_id' => $organizationId,
                'standard_id'     => $studentDetail->standard_id,
                'section_id'      => $studentDetail->section_id,
            ]);
            $list = $this->inPeriodOrder($homeworks->getCollection()->map(fn($h) => $this->formatHomework($h)
                + $this->periodOf($h, $periods)
                + ['is_completed' => $completedIds->has($h->id)]));

            return $this->responseService->success(
                [
                    'student_info' => [
                        'id'       => $studentDetail->id,
                        'name'     => $studentDetail->full_name ?? $user->name,
                        'standard' => $studentDetail->standard->name ?? null,
                        'section'  => $studentDetail->section->name ?? null,
                        'roll_no'  => $studentDetail->roll_no,
                    ],
                    'homeworks'  => $list,
                    'pagination' => [
                        'current_page' => $homeworks->currentPage(),
                        'last_page'    => $homeworks->lastPage(),
                        'per_page'     => $homeworks->perPage(),
                        'total'        => $homeworks->total(),
                    ],
                    'summary' => [
                        'total_homework' => $homeworks->total(),
                    ],
                ],
                'Student homework retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve student homework: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Student toggles a homework as done / not done from the app.
     * POST /homework/complete/{homework_id}  body: { completed: true|false }  (defaults true)
     * Presence of a completion row = "completed"; the admin Homework Status tab reads these.
     */
    public function markComplete(Request $request, $homeworkId)
    {
        try {
            $user = Auth::user();

            $homework = HomeWork::where('organization_id', $user->organization_id)->find($homeworkId);
            if (!$homework) {
                return $this->responseService->errorResponse('homework not found', 404);
            }

            $completed = $request->has('completed') ? $request->boolean('completed') : true;

            if ($completed) {
                $studentDetail = StudentDetail::where('user_id', $user->id)
                    ->where('organization_id', $user->organization_id)
                    ->first(['id']);

                HomeWorkCompletion::updateOrCreate(
                    ['home_work_id' => $homework->id, 'user_id' => $user->id],
                    [
                        'organization_id'   => $user->organization_id,
                        'student_detail_id' => $studentDetail?->id,
                        'completed_at'      => now(),
                    ]
                );
            } else {
                HomeWorkCompletion::where('home_work_id', $homework->id)
                    ->where('user_id', $user->id)
                    ->delete();
            }

            return $this->responseService->success(
                ['id' => $homework->id, 'is_completed' => $completed],
                $completed ? 'Homework marked as complete' : 'Homework marked as incomplete'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to update homework status: ' . $e->getMessage(),
                500
            );
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    // Is this (class, section, subject) assigned to the teacher in the timetable?
    private function teaches($user, $standardId, $sectionId, $subjectId): bool
    {
        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);

        return $teacher && TeacherTimeTable::where('teacher_detail_id', $teacher->id)
            ->where('organization_id', $user->organization_id)
            ->where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->exists();
    }

    // The teacher's periods.
    private function teacherPeriods($user)
    {
        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return collect();

        return $this->periods(['teacher_detail_id' => $teacher->id, 'organization_id' => $user->organization_id]);
    }

    // Timetable periods matching $where, active ones first, earliest first.
    private function periods(array $where)
    {
        return TeacherTimeTable::where($where)
            ->orderByDesc('is_active')
            ->orderBy('start_time')
            ->orderBy('day_of_week')
            ->get(['standard_id', 'section_id', 'subject_id', 'day_of_week', 'start_time', 'end_time']);
    }

    // Latest day first; within a day, in period order (homework without a period last).
    private function inPeriodOrder($list)
    {
        return $list->sort(fn($a, $b) => [$b['assigned_date'], $a['period_start'] ?? '99:99']
            <=> [$a['assigned_date'], $b['period_start'] ?? '99:99'])->values();
    }

    // The homework's class period on the weekday it was set — or, when that
    // class isn't taught that day, its earliest period in the week.
    private function periodOf(HomeWork $h, $periods): array
    {
        $slots = $periods->where('standard_id', $h->standard_id)
            ->where('section_id', $h->section_id)
            ->where('subject_id', $h->subject_id);

        $slot = $slots->firstWhere('day_of_week', $h->created_at?->dayOfWeekIso) ?? $slots->first();

        return [
            'period_start' => $slot?->start_time ? substr($slot->start_time, 0, 5) : null,
            'period_end'   => $slot?->end_time ? substr($slot->end_time, 0, 5) : null,
        ];
    }

    // Homework files are stored as an S3 path (app) or a full S3 URL (admin).
    private function deleteFile(string $file): void
    {
        try {
            $path = preg_match('#^https?://#i', $file) ? parse_url($file, PHP_URL_PATH) : $file;
            Storage::disk('s3')->delete(ltrim((string) $path, '/'));
        } catch (\Throwable $e) {
            logger()->warning('Homework file delete failed: ' . $e->getMessage());
        }
    }

    private function formatHomework(HomeWork $h): array
    {
        $fileUrl  = $h->file ? Storage::disk('s3')->url($h->file) : null;
        $fileType = null;
        if ($fileUrl) {
            $fileType = preg_match('/\.pdf(\?|$)/i', $fileUrl) ? 'pdf'
                : (preg_match('/\.(jpe?g|png|gif|webp)(\?|$)/i', $fileUrl) ? 'image' : 'doc');
        }

        return [
            'id'            => $h->id,
            'title'         => $h->title,
            'description'   => $h->description,
            'subject'       => $h->subject ? [
                'id'   => $h->subject->id,
                'name' => $h->subject->name,
                'code' => $h->subject->code ?? null,
            ] : null,
            'standard'      => $h->standard->name ?? null,
            'standard_id'   => $h->standard_id,
            'section'       => $h->section->name ?? null,
            'section_id'    => $h->section_id,
            'assigned_by'   => $h->user->name ?? 'Unknown',
            'assigned_date' => $h->created_at?->format('Y-m-d'),
            'assigned_time' => $h->created_at?->format('h:i A'),
            'days_ago'      => $h->created_at?->diffForHumans(),
            'file_url'      => $fileUrl,
            'file_type'     => $fileType,
        ];
    }
}
