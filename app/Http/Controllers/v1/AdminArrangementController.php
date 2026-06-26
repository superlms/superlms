<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\TeacherArrangement;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\Standard;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * School-admin Arrangement (substitution) module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Arrangement.php — for a date, list each absent
 * teacher's slots, the available substitute teachers per slot (excluding busy /
 * already-assigned / absent), let an admin assign a substitute, and delete an
 * arrangement. Org-scoped, role-gated to admin / sub-admin.
 */
class AdminArrangementController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];
    private const DAY_NAMES = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        return [$user, null];
    }

    private function hhmm($t): string
    {
        return substr((string) $t, 0, 5);
    }

    // ══════════════════════════ INDEX ══════════════════════════

    /** GET /admin/arrangement?date=YYYY-MM-DD&standard_id= */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $date = $request->filled('date') ? $request->date : Carbon::today()->format('Y-m-d');
        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;

        // Stats
        $totalTeachers = TeacherDetail::where('organization_id', $orgId)
            ->whereHas('user', fn ($q) => $q->where('is_active', 1))->count();
        $absentDetailIds = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $date)->where('status', 0)
            ->pluck('teacher_detail_id')->toArray();
        $arrangementCount = TeacherArrangement::where('organization_id', $orgId)->whereDate('date', $date)->count();

        $stats = [
            'total_teachers' => $totalTeachers,
            'absent'         => count($absentDetailIds),
            'available'      => max(0, $totalTeachers - count($absentDetailIds)),
            'arrangements'   => $arrangementCount,
        ];

        $absentTeachers = TeacherDetail::with('user:id,name')
            ->whereIn('id', $absentDetailIds)->where('organization_id', $orgId)->get();

        $absentSlots = TeacherTimeTable::with(['standard:id,name', 'section:id,name', 'subject:id,name'])
            ->whereIn('teacher_detail_id', $absentDetailIds)
            ->where('day_of_week', $dayOfWeek)
            ->where('organization_id', $orgId)
            ->when($request->filled('standard_id'), fn ($q) => $q->where('standard_id', $request->standard_id))
            ->orderBy('start_time')->get()->groupBy('teacher_detail_id');

        $arrangements = TeacherArrangement::with(['substituteTeacher.user:id,name', 'timetable'])
            ->where('organization_id', $orgId)->whereDate('date', $date)->get()->keyBy('teacher_time_table_id');

        // Candidate substitutes (active, not absent).
        $activeTeachers = TeacherDetail::with('user:id,name')
            ->where('organization_id', $orgId)
            ->whereHas('user', fn ($q) => $q->where('is_active', 1))
            ->whereNotIn('id', $absentDetailIds)->get();
        $candidateIds = $activeTeachers->pluck('id')->toArray();

        $candidateBusy = TeacherTimeTable::where('organization_id', $orgId)
            ->where('day_of_week', $dayOfWeek)
            ->whereIn('teacher_detail_id', $candidateIds)
            ->get(['teacher_detail_id', 'start_time', 'end_time'])->groupBy('teacher_detail_id');

        $candidateAlreadySub = TeacherArrangement::with('timetable:id,start_time,end_time')
            ->where('organization_id', $orgId)->whereDate('date', $date)
            ->whereIn('substitute_teacher_id', $candidateIds)->get()->groupBy('substitute_teacher_id');

        // Build response per absent teacher.
        $teachers = $absentTeachers->map(function ($teacher) use ($absentSlots, $arrangements, $activeTeachers, $candidateBusy, $candidateAlreadySub) {
            $slots = ($absentSlots->get($teacher->id) ?? collect())->map(function ($slot) use ($arrangements, $activeTeachers, $candidateBusy, $candidateAlreadySub) {
                $arr = $arrangements->get($slot->id);

                $available = [];
                if (!$arr) {
                    $available = $activeTeachers->filter(function ($t) use ($slot, $candidateBusy, $candidateAlreadySub) {
                        $busy = $candidateBusy->get($t->id, collect())->first(fn ($b) => $b->start_time < $slot->end_time && $b->end_time > $slot->start_time);
                        if ($busy) return false;
                        $sub = $candidateAlreadySub->get($t->id, collect())->first(fn ($a) => $a->timetable && $a->timetable->start_time < $slot->end_time && $a->timetable->end_time > $slot->start_time);
                        if ($sub) return false;
                        return true;
                    })->map(fn ($t) => ['id' => $t->id, 'name' => $t->user->name ?? '—'])->values();
                }

                return [
                    'slot_id'    => $slot->id,
                    'subject'    => $slot->subject?->name ?? '—',
                    'class'      => $slot->standard?->name ?? '—',
                    'section'    => $slot->section?->name ?? null,
                    'start_time' => $this->hhmm($slot->start_time),
                    'end_time'   => $this->hhmm($slot->end_time),
                    'arrangement' => $arr ? [
                        'id'              => $arr->id,
                        'substitute_name' => $arr->substituteTeacher?->user?->name ?? 'Substitute',
                        'reason'          => $arr->reason,
                    ] : null,
                    'available_substitutes' => $available,
                ];
            })->values();

            return [
                'teacher_id'   => $teacher->id,
                'teacher_name' => $teacher->user->name ?? '—',
                'slots'        => $slots,
            ];
        })->filter(fn ($t) => count($t['slots']) > 0)->values();

        return $this->success([
            'date'     => $date,
            'day_name' => self::DAY_NAMES[$dayOfWeek] ?? '',
            'stats'    => $stats,
            'classes'  => Standard::where('organization_id', $orgId)->orderBy('order')->get(['id', 'name']),
            'teachers' => $teachers,
        ], 'Arrangements fetched.');
    }

    // ══════════════════════════ ASSIGN ══════════════════════════

    /** POST /admin/arrangement  (date, slot_id, substitute_id, reason) */
    public function assign(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'date'          => 'required|date',
            'slot_id'       => 'required|integer',
            'substitute_id' => 'required|integer',
            'reason'        => 'required|string|min:2',
        ])) return $err;

        $orgId = $user->organization_id;

        $slot = TeacherTimeTable::where('id', $request->slot_id)->where('organization_id', $orgId)->first();
        if (!$slot) return $this->error('Slot not found.', 404);

        if (!$this->isSubstituteAvailable((int) $request->substitute_id, $slot, $orgId, $request->date)) {
            return $this->error('This teacher is no longer available for this time.', 422);
        }

        if (TeacherArrangement::where('organization_id', $orgId)->whereDate('date', $request->date)
            ->where('teacher_time_table_id', $slot->id)->exists()) {
            return $this->error('This slot is already arranged.', 422);
        }

        $arr = TeacherArrangement::create([
            'original_teacher_id'   => $slot->teacher_detail_id,
            'substitute_teacher_id' => $request->substitute_id,
            'teacher_time_table_id' => $slot->id,
            'date'                  => $request->date,
            'reason'                => $request->reason,
            'arranged_by'           => $user->id,
            'organization_id'       => $orgId,
        ]);

        return $this->success(['id' => $arr->id], 'Substitute assigned.');
    }

    private function isSubstituteAvailable(int $substituteId, TeacherTimeTable $slot, int $orgId, string $date): bool
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;

        if (TeacherAttendance::where('organization_id', $orgId)->whereDate('attendance_date', $date)
            ->where('status', 0)->where('teacher_detail_id', $substituteId)->exists()) {
            return false;
        }
        if (TeacherTimeTable::where('organization_id', $orgId)->where('day_of_week', $dayOfWeek)
            ->where('teacher_detail_id', $substituteId)
            ->where('start_time', '<', $slot->end_time)->where('end_time', '>', $slot->start_time)->exists()) {
            return false;
        }
        if (TeacherArrangement::where('organization_id', $orgId)->whereDate('date', $date)
            ->where('substitute_teacher_id', $substituteId)
            ->whereHas('timetable', fn ($q) => $q->where('start_time', '<', $slot->end_time)->where('end_time', '>', $slot->start_time))
            ->exists()) {
            return false;
        }
        return true;
    }

    // ══════════════════════════ DELETE ══════════════════════════

    /** DELETE /admin/arrangement/{id} */
    public function destroy($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $arr = TeacherArrangement::where('organization_id', $user->organization_id)->find($id);
        if (!$arr) return $this->error('Arrangement not found.', 404);

        $arr->delete();
        return $this->success(null, 'Arrangement deleted.');
    }
}
