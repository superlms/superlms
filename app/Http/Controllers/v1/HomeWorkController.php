<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Admin\HomeWork;
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
        if (($user->role ?? null) === 'teacher') {
            $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
            $teaches = $teacher && TeacherTimeTable::where('teacher_detail_id', $teacher->id)
                ->where('organization_id', $user->organization_id)
                ->where('standard_id', $request->standard_id)
                ->where('section_id', $request->section_id)
                ->where('subject_id', $request->subject_id)
                ->exists();

            if (!$teaches) {
                return $this->responseService->errorResponse(
                    'You can only add homework for a class & subject assigned to you in the timetable.',
                    403
                );
            }
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

    // Update homework
    public function updateHomeWork(Request $request, $chapterId)
    {
        try {
            $homework = HomeWork::where('organization_id', Auth::user()->organization_id)
                ->findOrFail($chapterId);

            $data = [
                'standard_id' => $request->standard_id ?? $homework->standard_id,
                'section_id'  => $request->section_id ?? $homework->section_id,
                'subject_id'  => $request->subject_id ?? $homework->subject_id,
                'title'       => $request->input('title', $request->input('name', $homework->title)),
                'description' => $request->description ?? $homework->description,
            ];

            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('admin/homework', 's3');
                if ($path !== false) {
                    Storage::disk('s3')->setVisibility($path, 'public');
                    $data['file'] = $path;
                }
            }

            $homework->update($data);
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
            $homework = HomeWork::where('organization_id', Auth::user()->organization_id)
                ->findOrFail($chapterId);

            $homework->delete();
            DB::commit();

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

            return $this->responseService->success(
                [
                    'homeworks'  => $homeworks->getCollection()->map(fn($h) => $this->formatHomework($h))->values(),
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

            return $this->responseService->success(
                [
                    'student_info' => [
                        'id'       => $studentDetail->id,
                        'name'     => $studentDetail->full_name ?? $user->name,
                        'standard' => $studentDetail->standard->name ?? null,
                        'section'  => $studentDetail->section->name ?? null,
                        'roll_no'  => $studentDetail->roll_no,
                    ],
                    'homeworks'  => $homeworks->getCollection()->map(fn($h) => $this->formatHomework($h))->values(),
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

    // ── Helpers ────────────────────────────────────────────────────────────────

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
