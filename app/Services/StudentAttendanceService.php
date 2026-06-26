<?php

namespace App\Services;

use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\AssignTeacherStandard;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentAttendanceService
{
    /**
     * Get students for attendance marking with today's status
     */
    public function getStudentsForAttendance($teacherDetailId, $organizationId, $date = null)
    {
        $date = $date ?? Carbon::today()->toDateString();

        return DB::transaction(function () use ($teacherDetailId, $organizationId, $date) {
            // Get all teacher assignments with standard_id and section_id pairs
            $teacherAssignments = AssignTeacherStandard::where('teacher_detail_id', $teacherDetailId)
                ->where('organization_id', $organizationId)
                ->get(['standard_id', 'section_id']);

            if ($teacherAssignments->isEmpty()) {
                return collect();
            }

            // Instead of using whereIn separately, we need to combine conditions
            $students = StudentDetail::with(['user', 'standard', 'section'])
                ->where('organization_id', $organizationId);

            // Create a query that matches exact standard_id and section_id pairs
            $students->where(function ($query) use ($teacherAssignments) {
                foreach ($teacherAssignments as $assignment) {
                    $query->orWhere(function ($q) use ($assignment) {
                        $q->where('standard_id', $assignment->standard_id)
                            ->where('section_id', $assignment->section_id);
                    });
                }
            });

            $students = $students->orderBy('roll_no')->get();

            return $students->map(function ($student) use ($date) {
                $attendance = StudentAttendance::where('student_detail_id', $student->id)
                    ->where('attendance_date', $date)
                    ->first();

                return [
                    'student_detail_id' => $student->id,
                    'user_id' => $student->user_id,
                    'roll_no' => $student->roll_no,
                    'full_name' => $student->full_name,
                    'standard_id' => $student->standard_id,
                    'section_id' => $student->section_id,
                    'standard_name' => $student->standard->name ?? null,
                    'section_name' => $student->section->name ?? null,
                    'attendance' => $attendance ? [
                        'status' => (bool)$attendance->status,
                        'remarks' => $attendance->remarks
                    ] : [
                        'status' => true,
                        'remarks' => null
                    ]
                ];
            });
        });
    }

    /**
     * Bulk submit attendance (optimized for many records)
     */
    public function bulkSubmitAttendance($data, $markedById, $organizationId)
    {
        return DB::transaction(function () use ($data, $markedById, $organizationId) {
            $date = $data['attendance_date'];
            $attendances = $data['attendances'];
            $studentIds = collect($attendances)->pluck('student_detail_id');

            // Get existing attendances for this date
            $existingAttendances = StudentAttendance::where('attendance_date', $date)
                ->whereIn('student_detail_id', $studentIds)
                ->get()
                ->keyBy('student_detail_id');

            $results = [];
            $batchSize = 100; // Process in batches of 100
            $chunks = array_chunk($attendances, $batchSize);

            foreach ($chunks as $chunk) {
                $batchResults = [];
                $updates = [];
                $inserts = [];

                foreach ($chunk as $attendance) {
                    $studentId = $attendance['student_detail_id'];

                    if (isset($existingAttendances[$studentId])) {
                        // Prepare updates
                        $updates[] = [
                            'id' => $existingAttendances[$studentId]->id,
                            'status' => $attendance['status'],
                            'remarks' => $attendance['remarks'] ?? null,
                            'marked_by' => $markedById,
                            'updated_at' => now()
                        ];
                    } else {
                        // Prepare inserts
                        $student = StudentDetail::find($studentId);
                        $inserts[] = [
                            'student_detail_id' => $studentId,
                            'user_id' => $student->user_id,
                            'organization_id' => $organizationId,
                            'attendance_date' => $date,
                            'status' => $attendance['status'],
                            'remarks' => $attendance['remarks'] ?? null,
                            'marked_by' => $markedById,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }

                // Bulk update existing records
                if (!empty($updates)) {
                    $this->bulkUpdateAttendances($updates);
                }

                // Bulk insert new records
                if (!empty($inserts)) {
                    StudentAttendance::insert($inserts);
                }

                $batchResults = array_merge(
                    $this->formatResults($updates, true),
                    $this->formatResults($inserts, false)
                );

                $results = array_merge($results, $batchResults);
            }

            return $results;
        });
    }

    /**
     * Bulk update attendances using raw query for better performance
     */
    protected function bulkUpdateAttendances($updates)
    {
        $table = (new StudentAttendance())->getTable();
        $cases = [];
        $ids = [];
        $params = [];

        foreach ($updates as $update) {
            $id = $update['id'];
            $cases[] = "WHEN {$id} then ?";
            $params[] = $update['status'];
            $cases[] = "WHEN {$id} then ?";
            $params[] = $update['remarks'];
            $cases[] = "WHEN {$id} then ?";
            $params[] = $update['marked_by'];
            $ids[] = $id;
        }

        $ids = implode(',', $ids);
        $cases = implode(' ', $cases);

        return DB::update(
            "UPDATE {$table} SET 
                status = CASE id {$cases} END,
                remarks = CASE id {$cases} END,
                marked_by = CASE id {$cases} END,
                updated_at = ?
            WHERE id IN ({$ids})",
            array_merge($params, $params, $params, [now()])
        );
    }

    /**
     * Format results for response
     */
    protected function formatResults($records, $isUpdate)
    {
        return array_map(function ($record) use ($isUpdate) {
            return [
                'student_detail_id' => $record['student_detail_id'] ?? null,
                'status' => (int)$record['status'], // 0=absent, 1=present, 4=holiday
                'remarks' => $record['remarks'] ?? null,
                'operation' => $isUpdate ? 'updated' : 'created'
            ];
        }, $records);
    }

    /**
     * Get attendance summary for a class on specific date
     */
    public function getDailyAttendanceSummary($organizationId, $standardId, $sectionId, $date)
    {
        $totalStudents = StudentDetail::where('organization_id', $organizationId)
            ->where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->count();

        $presentCount = StudentAttendance::where('organization_id', $organizationId)
            ->where('attendance_date', $date)
            ->where('status', true)
            ->whereHas('studentDetail', function ($query) use ($standardId, $sectionId) {
                $query->where('standard_id', $standardId)
                    ->where('section_id', $sectionId);
            })
            ->count();

        return [
            'date' => $date,
            'total_students' => $totalStudents,
            'present' => $presentCount,
            'absent' => $totalStudents - $presentCount,
            'attendance_percentage' => $totalStudents > 0
                ? round(($presentCount / $totalStudents) * 100, 2)
                : 0
        ];
    }
}
