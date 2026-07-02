<?php

namespace App\Livewire\Admin;

use App\Models\Admin\TeacherArrangement;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\Standard;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class Arrangement extends Component
{
    use WireUiActions;

    // ─── Date + filters ──────────────────────────────────────────────────
    public string $date = '';
    public string $filterClass = '';

    // ─── In-progress slot selections (keyed by timetable slot id) ─────────
    public array $slotSubstitutes = [];
    public array $slotReasons     = [];

    // ─── Stats ───────────────────────────────────────────────────────────
    public int $totalTeachers    = 0;
    public int $absentCount      = 0;
    public int $availableCount   = 0;
    public int $arrangementCount = 0;

    // ─── Lookup ──────────────────────────────────────────────────────────
    public $standards = [];

    // ─── Delete confirm overlay ──────────────────────────────────────────
    public bool   $showDeleteConfirm = false;
    public ?int   $deleteTargetId    = null;
    public string $deleteTargetLabel = '';

    public function mount(): void
    {
        $this->date = Carbon::today()->format('Y-m-d');
        $org = Auth::user()->organization_id;
        $this->standards = Standard::where('organization_id', $org)->orderBy('id')->get();
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $org = Auth::user()->organization_id;
        $this->totalTeachers = TeacherDetail::where('organization_id', $org)
            ->whereHas('user', fn($q) => $q->where('is_active', 1))
            ->count();
        $this->absentCount = TeacherAttendance::where('organization_id', $org)
            ->whereDate('attendance_date', $this->date)
            ->where('status', 0)
            ->count();
        $this->availableCount   = max(0, $this->totalTeachers - $this->absentCount);
        $this->arrangementCount = TeacherArrangement::where('organization_id', $org)
            ->whereDate('date', $this->date)
            ->count();
    }

    public function updatedDate(): void
    {
        $this->slotSubstitutes = [];
        $this->slotReasons     = [];
        $this->loadStats();
    }

    public function updatedFilterClass(): void
    {
        // keep selections but refresh page
    }

    public function clearFilters(): void
    {
        $this->reset(['filterClass']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  ASSIGN SUBSTITUTE FOR A SINGLE SLOT
    // ═══════════════════════════════════════════════════════════════════════
    public function assignSlot(int $slotId): void
    {
        $org          = Auth::user()->organization_id;
        $substituteId = $this->slotSubstitutes[$slotId] ?? null;
        $reason       = trim($this->slotReasons[$slotId] ?? '');

        if (!$substituteId) {
            $this->notification()->error('Pick a substitute teacher first.');
            return;
        }
        if ($reason === '') {
            $this->notification()->error('Reason is required.');
            return;
        }

        $slot = TeacherTimeTable::where('id', $slotId)
            ->where('organization_id', $org)
            ->first();
        if (!$slot) {
            $this->notification()->error('Slot not found.');
            return;
        }

        // Re-check at save time: substitute still available?
        if (!$this->isSubstituteAvailable((int) $substituteId, $slot, $org)) {
            $this->notification()->error('This teacher is no longer available for this time.');
            return;
        }

        // Already arranged?
        $exists = TeacherArrangement::where('organization_id', $org)
            ->whereDate('date', $this->date)
            ->where('teacher_time_table_id', $slot->id)
            ->exists();
        if ($exists) {
            $this->notification()->error('This slot is already arranged.');
            return;
        }

        try {
            TeacherArrangement::create([
                'original_teacher_id'   => $slot->teacher_detail_id,
                'substitute_teacher_id' => $substituteId,
                'teacher_time_table_id' => $slot->id,
                'date'                  => $this->date,
                'reason'                => $reason,
                'arranged_by'           => Auth::id(),
                'organization_id'       => $org,
            ]);

            unset($this->slotSubstitutes[$slotId], $this->slotReasons[$slotId]);
            $this->notification()->success('Substitute assigned.');
            $this->loadStats();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
            logger()->error('Arrangement assign error: ' . $e->getMessage());
        }
    }

    private function isSubstituteAvailable(int $substituteId, TeacherTimeTable $slot, int $org): bool
    {
        $dayOfWeek = Carbon::parse($this->date)->dayOfWeekIso;

        $isAbsent = TeacherAttendance::where('organization_id', $org)
            ->whereDate('attendance_date', $this->date)
            ->where('status', 0)
            ->where('teacher_detail_id', $substituteId)
            ->exists();
        if ($isAbsent) return false;

        $busy = TeacherTimeTable::where('organization_id', $org)
            ->where('day_of_week', $dayOfWeek)
            ->where('teacher_detail_id', $substituteId)
            ->where('start_time', '<', $slot->end_time)
            ->where('end_time', '>', $slot->start_time)
            ->exists();
        if ($busy) return false;

        $alreadySub = TeacherArrangement::where('organization_id', $org)
            ->whereDate('date', $this->date)
            ->where('substitute_teacher_id', $substituteId)
            ->whereHas('timetable', function ($q) use ($slot) {
                $q->where('start_time', '<', $slot->end_time)
                    ->where('end_time', '>', $slot->start_time);
            })
            ->exists();
        if ($alreadySub) return false;

        return true;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  DELETE ARRANGEMENT
    // ═══════════════════════════════════════════════════════════════════════
    public function deleteArrangement(int $id): void
    {
        $arr = TeacherArrangement::with(['substituteTeacher.user', 'timetable.standard', 'timetable.subject'])
            ->find($id);
        if (!$arr) return;

        $this->deleteTargetId    = $id;
        $this->deleteTargetLabel = ($arr->substituteTeacher?->user?->name ?? 'Substitute')
            . ' for ' . ($arr->timetable?->subject?->name ?? 'class')
            . ' (' . ($arr->timetable?->standard?->name ?? '') . ')';
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
        $this->deleteTargetLabel = '';
    }

    public function confirmDelete(): void
    {
        if (!$this->deleteTargetId) {
            $this->cancelDelete();
            return;
        }

        try {
            TeacherArrangement::findOrFail($this->deleteTargetId)->delete();
            $this->notification()->success('Arrangement deleted.');
            $this->loadStats();
        } catch (\Exception $e) {
            $this->notification()->error('Error: ' . $e->getMessage());
        }

        $this->cancelDelete();
    }

    // ─── Render ──────────────────────────────────────────────────────────
    public function render()
    {
        $org       = Auth::user()->organization_id;
        $dayOfWeek = Carbon::parse($this->date)->dayOfWeekIso;

        // 1. Absent teachers for this date
        $absentDetailIds = TeacherAttendance::where('organization_id', $org)
            ->whereDate('attendance_date', $this->date)
            ->where('status', 0)
            ->pluck('teacher_detail_id')
            ->toArray();

        $absentTeachers = TeacherDetail::with('user')
            ->whereIn('id', $absentDetailIds)
            ->where('organization_id', $org)
            ->get();

        // 2. Their day-of-week slots (optionally filtered by class)
        $absentSlots = TeacherTimeTable::with(['standard', 'section', 'subject'])
            ->whereIn('teacher_detail_id', $absentDetailIds)
            ->where('day_of_week', $dayOfWeek)
            ->where('organization_id', $org)
            ->when($this->filterClass, fn($q) => $q->where('standard_id', $this->filterClass))
            ->orderBy('start_time')
            ->get()
            ->groupBy('teacher_detail_id');

        // 3. Existing arrangements (keyed by teacher_time_table_id)
        $arrangementsForDate = TeacherArrangement::with(['substituteTeacher.user', 'timetable'])
            ->where('organization_id', $org)
            ->whereDate('date', $this->date)
            ->get()
            ->keyBy('teacher_time_table_id');

        // 4. Compute available substitutes per slot
        // Candidate pool: active teachers not absent today
        $activeTeachers = TeacherDetail::with('user')
            ->where('organization_id', $org)
            ->whereHas('user', fn($q) => $q->where('is_active', 1))
            ->whereNotIn('id', $absentDetailIds)
            ->get();

        $candidateIds = $activeTeachers->pluck('id')->toArray();

        // Each candidate's busy timetable on this day
        $candidateBusy = TeacherTimeTable::where('organization_id', $org)
            ->where('day_of_week', $dayOfWeek)
            ->whereIn('teacher_detail_id', $candidateIds)
            ->get(['teacher_detail_id', 'start_time', 'end_time'])
            ->groupBy('teacher_detail_id');

        // Each candidate's already-assigned substitute slots today
        $candidateAlreadySub = TeacherArrangement::with('timetable:id,start_time,end_time')
            ->where('organization_id', $org)
            ->whereDate('date', $this->date)
            ->whereIn('substitute_teacher_id', $candidateIds)
            ->get()
            ->groupBy('substitute_teacher_id');

        // Flatten all absent slots into a flat lookup by id (for in-memory overlap lookups)
        $allSlotsById = collect();
        foreach ($absentSlots as $slots) {
            foreach ($slots as $s) {
                $allSlotsById->put($s->id, $s);
            }
        }

        $slotAvailability = []; // [slot_id => Collection<TeacherDetail>]
        foreach ($absentSlots as $slots) {
            foreach ($slots as $slot) {
                // Skip computation for already-arranged slots
                if ($arrangementsForDate->has($slot->id)) {
                    $slotAvailability[$slot->id] = collect();
                    continue;
                }

                $available = $activeTeachers->filter(function ($teacher) use ($slot, $candidateBusy, $candidateAlreadySub) {
                    $tid = $teacher->id;

                    // Overlapping own-class slot?
                    $hasBusy = $candidateBusy->get($tid, collect())->first(function ($b) use ($slot) {
                        return $b->start_time < $slot->end_time && $b->end_time > $slot->start_time;
                    });
                    if ($hasBusy) return false;

                    // Overlapping already-assigned substitute slot?
                    $hasSub = $candidateAlreadySub->get($tid, collect())->first(function ($a) use ($slot) {
                        return $a->timetable
                            && $a->timetable->start_time < $slot->end_time
                            && $a->timetable->end_time > $slot->start_time;
                    });
                    if ($hasSub) return false;

                    return true;
                });

                // In-memory: exclude teachers selected for OTHER overlapping slots in current session
                $selections = $this->slotSubstitutes;
                $available  = $available->reject(function ($teacher) use ($slot, $selections, $allSlotsById) {
                    foreach ($selections as $otherSlotId => $selectedSubId) {
                        if ((int) $otherSlotId === (int) $slot->id) continue;
                        if ((int) $selectedSubId !== (int) $teacher->id) continue;

                        $otherSlot = $allSlotsById->get((int) $otherSlotId);
                        if (!$otherSlot) continue;

                        if ($otherSlot->start_time < $slot->end_time && $otherSlot->end_time > $slot->start_time) {
                            return true;
                        }
                    }
                    return false;
                });

                $slotAvailability[$slot->id] = $available->values();
            }
        }

        return view('livewire.admin.arrangement', compact(
            'absentTeachers',
            'absentSlots',
            'arrangementsForDate',
            'slotAvailability',
        ));
    }
}
