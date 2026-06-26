<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\SectionSubject;
use App\Models\Student\StandardSubject;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\Teacher\TeacherSubject;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    protected ResponseService $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    public function getAllSubject(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            // Get student details
            $student = StudentDetail::where('user_id', $user->id)->first();

            if (!$student) {
                return $this->responseService->errorResponse(
                    'Student record not found',
                    404
                );
            }

            $subjects = collect();

            // First check if there are section-specific subjects
            if ($student->section_id) {
                $sectionSubjects = SectionSubject::where('section_id', $student->section_id)
                    ->where('standard_id', $student->standard_id)
                    ->where('organization_id', $user->organization_id)
                    ->with('subject')
                    ->get();

                if ($sectionSubjects->isNotEmpty()) {
                    $subjects = $sectionSubjects->map(function ($sectionSubject) {
                        return [
                            'id' => $sectionSubject->subject_id,
                            'name' => $sectionSubject->subject->name ?? null,
                            'image' => $sectionSubject->subject->image ?? null,
                            'detail_image' => $sectionSubject->subject->detail_image ?? null,
                            'is_mandatory' => true,
                            'type' => 'section_subject'
                        ];
                    });
                }
            }

            // If no section subjects found, get standard subjects
            if ($subjects->isEmpty()) {
                $standardSubjects = StandardSubject::where('standard_id', $student->standard_id)
                    ->where('organization_id', $user->organization_id)
                    ->with('subject')
                    ->get();

                $subjects = $standardSubjects->map(function ($standardSubject) {
                    return [
                        'id' => $standardSubject->subject_id,
                        'name' => $standardSubject->subject->name ?? null,
                        'image' => $standardSubject->subject->image ?? null,
                        'detail_image' => $standardSubject->subject->detail_image ?? null,
                        'is_mandatory' => $standardSubject->is_mandatory,
                        'type' => 'standard_subject'
                    ];
                });
            }

            // Remove duplicates (in case same subject exists in both)
            $subjects = $subjects->unique('id')->values();

            return $this->responseService->success(
                $subjects,
                'Subjects retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getTeacherSubject(Request $request)
    {
        try {
            $user = Auth::user();

            $teacherDetail = TeacherDetail::where('user_id', $user->id)->first();

            if (!$teacherDetail) {
                return $this->responseService->errorResponse(
                    'Teacher not found',
                    404
                );
            }


            $teacherSubjects = TeacherSubject::with(['subject', 'standard', 'section'])
                ->where('teacher_detail_id', $teacherDetail->id)
                ->get();

            // Source 2: From timetable (distinct subjects)
            $timetableSubjects = TeacherTimeTable::with(['subject', 'standard', 'section'])
                ->where('teacher_detail_id', $teacherDetail->id)
                ->select('subject_id', 'standard_id', 'section_id')
                ->distinct()
                ->get();

            // Combine and deduplicate subjects
            $allSubjects = collect();

            // Add from TeacherSubject
            foreach ($teacherSubjects as $ts) {
                if ($ts->subject) {
                    $allSubjects->push([
                        'source' => 'assigned_subjects',
                        'subject_id' => $ts->subject_id,
                        'subject_name' => $ts->subject->name,
                        'subject_code' => $ts->subject->code,
                        'subject_image' => $ts->subject->image ?? null,
                        'subject_detail_image' => $ts->subject->detail_image ?? null,
                        'standard_id' => $ts->standard_id,
                        'standard_name' => $ts->standard->name ?? null,
                        'section_id' => $ts->section_id,
                        'section_name' => $ts->section->name ?? null,
                    ]);
                }
            }

            // Add from Timetable (if not already added)
            foreach ($timetableSubjects as $tt) {
                if ($tt->subject && !$allSubjects->where('subject_id', $tt->subject_id)->first()) {
                    $allSubjects->push([
                        'source' => 'timetable',
                        'subject_id' => $tt->subject_id,
                        'subject_name' => $tt->subject->name,
                        'subject_code' => $tt->subject->code,
                        'subject_image' => $tt->subject->image ?? null,
                        'subject_detail_image' => $tt->subject->detail_image ?? null,
                        'standard_id' => $tt->standard_id,
                        'standard_name' => $tt->standard->name ?? null,
                        'section_id' => $tt->section_id,
                        'section_name' => $tt->section->name ?? null,
                    ]);
                }
            }

            // Group by standard
            $groupedSubjects = $allSubjects->groupBy('standard_name');

            // Get today's timetable
            $dayOfWeek = now()->dayOfWeekIso;
            $todayTimetable = TeacherTimeTable::with(['subject', 'standard', 'section'])
                ->where('teacher_detail_id', $teacherDetail->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->orderBy('start_time')
                ->get()
                ->map(function ($period) {
                    return [
                        'period_id' => $period->id,
                        'subject_name' => $period->subject->name ?? 'Free Period',
                        'standard_name' => $period->standard->name ?? null,
                        'section_name' => $period->section->name ?? null,
                        'start_time' => $period->start_time,
                        'end_time' => $period->end_time,
                        'room' => $period->room_number,
                    ];
                });

            $response = [
                'teacher' => [
                    'id' => $teacherDetail->id,
                    'name' => $teacherDetail->user->name,
                    'employee_id' => $teacherDetail->employee_id,
                ],
                'subjects' => $groupedSubjects,
                'today_timetable' => $todayTimetable,
                'summary' => [
                    'total_subjects' => $allSubjects->count(),
                    'total_standards' => $groupedSubjects->count(),
                    'total_periods_today' => $todayTimetable->count(),
                ]
            ];

            return $this->responseService->success(
                $response,
                'Teacher subjects retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to retrieve teacher subjects: ' . $e->getMessage(),
                500
            );
        }
    }
}
