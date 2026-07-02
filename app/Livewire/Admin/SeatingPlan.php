<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Exam;
use App\Models\Admin\Seating\InvigilatorAssignment;
use App\Models\Admin\Seating\SeatAssignment;
use App\Models\Admin\Seating\SeatingInvigilator;
use App\Models\Admin\Seating\SeatingPlan as SeatingPlanModel;
use App\Models\Admin\Seating\SeatingRoom;
use App\Models\Admin\Seating\SeatingSeat;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Services\Seating\SeatingPlannerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class SeatingPlan extends Component
{
    use WithPagination, WireUiActions;

    public string $activeTab = 'plans'; // plans, rooms, invigilators, generate

    // ─── Room form ──────────────────────────────────────────────────────────
    public bool $showRoomPanel = false;
    public ?int $editRoomId = null;
    public array $roomForm = [
        'room_name' => '', 'building' => '', 'rows' => 5, 'columns' => 6,
        'is_active' => true, 'notes' => '',
    ];

    // ─── Invigilator form ───────────────────────────────────────────────────
    public bool $showInvigilatorPanel = false;
    public ?int $editInvigilatorId = null;
    public array $invigilatorForm = [
        'name' => '', 'email' => '', 'phone' => '',
        'available_dates_csv' => '', 'max_rooms' => 3,
        'is_active' => true, 'notes' => '',
    ];

    // ─── Generate plan form ─────────────────────────────────────────────────
    public bool $showGeneratePanel = false;
    public array $generateForm = [
        'exam_id' => '', 'name' => '', 'exam_date' => '',
        'session' => '', 'standard_ids' => [], 'room_ids' => [],
    ];

    // ─── Confirm delete state ───────────────────────────────────────────────
    public ?int $pendingDeleteRoomId = null;
    public ?int $pendingDeleteInvigilatorId = null;
    public ?int $pendingDeletePlanId = null;

    // ─── Plan viewer ────────────────────────────────────────────────────────
    public ?int $viewingPlanId = null;

    // ─── Filters ────────────────────────────────────────────────────────────
    public string $planSearch = '';

    public function mount(): void
    {
        // Default exam date = today
        $this->generateForm['exam_date'] = now()->toDateString();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  ROOMS
    // ═══════════════════════════════════════════════════════════════════════

    public function openRoomPanel(?int $id = null): void
    {
        $this->resetErrorBag();
        $this->editRoomId = $id;
        if ($id) {
            $room = SeatingRoom::find($id);
            if ($room) {
                $this->roomForm = [
                    'room_name' => $room->room_name,
                    'building'  => $room->building,
                    'rows'      => $room->rows,
                    'columns'   => $room->columns,
                    'is_active' => $room->is_active,
                    'notes'     => $room->notes,
                ];
            }
        } else {
            $this->roomForm = [
                'room_name' => '', 'building' => '', 'rows' => 5, 'columns' => 6,
                'is_active' => true, 'notes' => '',
            ];
        }
        $this->showRoomPanel = true;
    }

    public function closeRoomPanel(): void
    {
        $this->showRoomPanel = false;
        $this->editRoomId = null;
    }

    public function saveRoom(): void
    {
        $this->validate([
            'roomForm.room_name' => 'required|string|max:100',
            'roomForm.building'  => 'nullable|string|max:100',
            'roomForm.rows'      => 'required|integer|min:1|max:50',
            'roomForm.columns'   => 'required|integer|min:1|max:50',
        ]);

        $rows = (int) $this->roomForm['rows'];
        $cols = (int) $this->roomForm['columns'];
        $capacity = $rows * $cols;

        $data = [
            'organization_id' => Auth::user()->organization_id,
            'room_name'       => $this->roomForm['room_name'],
            'building'        => $this->roomForm['building'],
            'rows'            => $rows,
            'columns'         => $cols,
            'capacity'        => $capacity,
            'is_active'       => (bool) $this->roomForm['is_active'],
            'notes'           => $this->roomForm['notes'],
        ];

        DB::transaction(function () use ($data) {
            if ($this->editRoomId) {
                $room = SeatingRoom::find($this->editRoomId);
                $room->update($data);
                // regenerate seats only if rows/cols changed
                $this->regenerateSeats($room);
            } else {
                $room = SeatingRoom::create($data);
                $this->regenerateSeats($room);
            }
        });

        $this->notification()->success($this->editRoomId ? 'Room updated.' : 'Room added.');
        $this->closeRoomPanel();
    }

    private function regenerateSeats(SeatingRoom $room): void
    {
        SeatingSeat::where('room_id', $room->id)->delete();
        $rows = [];
        $now = now();
        for ($r = 1; $r <= $room->rows; $r++) {
            for ($c = 1; $c <= $room->columns; $c++) {
                $rows[] = [
                    'room_id'     => $room->id,
                    'row_no'      => $r,
                    'col_no'      => $c,
                    'seat_number' => chr(64 + $r) . $c, // A1, A2, B1, ...
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }
        SeatingSeat::insert($rows);
    }

    public function confirmDeleteRoom(int $id): void { $this->pendingDeleteRoomId = $id; }
    public function cancelDeleteRoom(): void { $this->pendingDeleteRoomId = null; }
    public function executeDeleteRoom(): void
    {
        if ($this->pendingDeleteRoomId) {
            SeatingRoom::where('id', $this->pendingDeleteRoomId)
                ->where('organization_id', Auth::user()->organization_id)
                ->delete();
            $this->notification()->success('Room removed.');
        }
        $this->pendingDeleteRoomId = null;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  INVIGILATORS
    // ═══════════════════════════════════════════════════════════════════════

    public function openInvigilatorPanel(?int $id = null): void
    {
        $this->resetErrorBag();
        $this->editInvigilatorId = $id;
        if ($id) {
            $inv = SeatingInvigilator::find($id);
            if ($inv) {
                $this->invigilatorForm = [
                    'name'                => $inv->name,
                    'email'               => $inv->email,
                    'phone'               => $inv->phone,
                    'available_dates_csv' => implode(', ', $inv->available_dates ?? []),
                    'max_rooms'           => $inv->max_rooms,
                    'is_active'           => $inv->is_active,
                    'notes'               => $inv->notes,
                ];
            }
        } else {
            $this->invigilatorForm = [
                'name' => '', 'email' => '', 'phone' => '',
                'available_dates_csv' => '', 'max_rooms' => 3,
                'is_active' => true, 'notes' => '',
            ];
        }
        $this->showInvigilatorPanel = true;
    }

    public function closeInvigilatorPanel(): void
    {
        $this->showInvigilatorPanel = false;
        $this->editInvigilatorId = null;
    }

    public function saveInvigilator(): void
    {
        $this->validate([
            'invigilatorForm.name'      => 'required|string|max:100',
            'invigilatorForm.email'     => 'nullable|email',
            'invigilatorForm.phone'     => 'nullable|string|max:20',
            'invigilatorForm.max_rooms' => 'required|integer|min:1|max:20',
        ]);

        $dates = array_filter(array_map('trim', explode(',', $this->invigilatorForm['available_dates_csv'] ?? '')));
        // validate Y-m-d
        $dates = array_values(array_filter($dates, fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)));

        $data = [
            'organization_id' => Auth::user()->organization_id,
            'name'            => $this->invigilatorForm['name'],
            'email'           => $this->invigilatorForm['email'],
            'phone'           => $this->invigilatorForm['phone'],
            'available_dates' => $dates,
            'max_rooms'       => (int) $this->invigilatorForm['max_rooms'],
            'is_active'       => (bool) $this->invigilatorForm['is_active'],
            'notes'           => $this->invigilatorForm['notes'],
        ];

        if ($this->editInvigilatorId) {
            SeatingInvigilator::find($this->editInvigilatorId)->update($data);
        } else {
            SeatingInvigilator::create($data);
        }

        $this->notification()->success($this->editInvigilatorId ? 'Invigilator updated.' : 'Invigilator added.');
        $this->closeInvigilatorPanel();
    }

    public function confirmDeleteInvigilator(int $id): void { $this->pendingDeleteInvigilatorId = $id; }
    public function cancelDeleteInvigilator(): void { $this->pendingDeleteInvigilatorId = null; }
    public function executeDeleteInvigilator(): void
    {
        if ($this->pendingDeleteInvigilatorId) {
            SeatingInvigilator::where('id', $this->pendingDeleteInvigilatorId)
                ->where('organization_id', Auth::user()->organization_id)
                ->delete();
            $this->notification()->success('Invigilator removed.');
        }
        $this->pendingDeleteInvigilatorId = null;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  GENERATE PLAN
    // ═══════════════════════════════════════════════════════════════════════

    public function openGeneratePanel(): void
    {
        $this->resetErrorBag();
        $this->generateForm = [
            'exam_id'      => '',
            'name'         => '',
            'exam_date'    => now()->toDateString(),
            'session'      => '',
            'standard_ids' => [],
            'room_ids'     => [],
        ];
        $this->showGeneratePanel = true;
    }

    public function closeGeneratePanel(): void
    {
        $this->showGeneratePanel = false;
    }

    public function generatePlan(SeatingPlannerService $planner): void
    {
        $this->validate([
            'generateForm.exam_id'        => 'required|integer|exists:exams,id',
            'generateForm.name'           => 'required|string|max:150',
            'generateForm.exam_date'      => 'required|date',
            'generateForm.standard_ids'   => 'required|array|min:1',
            'generateForm.standard_ids.*' => 'integer',
            'generateForm.room_ids'       => 'required|array|min:1',
            'generateForm.room_ids.*'     => 'integer',
        ]);

        $orgId = Auth::user()->organization_id;

        // Fetch students for the chosen classes
        $students = StudentDetail::with(['standard:id,name', 'section:id,name'])
            ->whereIn('standard_id', $this->generateForm['standard_ids'])
            ->where('organization_id', $orgId)
            ->whereNotNull('user_id')
            ->get();

        if ($students->isEmpty()) {
            $this->notification()->error('No students found for selected classes.');
            return;
        }

        $studentInput = $students->map(fn($s) => [
            'id'          => $s->user_id,
            'name'        => $s->full_name,
            'class_label' => ($s->standard->name ?? '?') . '-' . ($s->section->name ?? '-'),
        ])->toArray();

        $rooms = SeatingRoom::with('seats')
            ->whereIn('id', $this->generateForm['room_ids'])
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->get();

        if ($rooms->isEmpty()) {
            $this->notification()->error('No active rooms selected.');
            return;
        }

        $result = $planner->plan($studentInput, $rooms);

        DB::transaction(function () use ($result, $rooms, $orgId) {
            $plan = SeatingPlanModel::create([
                'organization_id' => $orgId,
                'exam_id'         => $this->generateForm['exam_id'],
                'name'            => $this->generateForm['name'],
                'exam_date'       => $this->generateForm['exam_date'],
                'session'         => $this->generateForm['session'] ?: null,
                'status'          => 'draft',
                'generated_at'    => now(),
                'total_students'  => $result['totals']['students'],
                'total_seats'     => $result['totals']['seats'],
                'conflict_count'  => $result['totals']['conflicts'],
            ]);

            $rows = [];
            $now = now();
            foreach ($result['assignments'] as $a) {
                if (!$a['seat_id']) continue;
                $rows[] = [
                    'seating_plan_id' => $plan->id,
                    'seat_id'         => $a['seat_id'],
                    'room_id'         => $a['room_id'],
                    'student_id'      => $a['student_id'],
                    'class_label'     => $a['class_label'],
                    'has_conflict'    => $a['has_conflict'] ? 1 : 0,
                    'is_locked'       => 0,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
            if ($rows) SeatAssignment::insert($rows);

            // Auto-assign invigilators
            $invigilators = SeatingInvigilator::where('organization_id', $orgId)->get();
            $invMap = (new SeatingPlannerService())->assignInvigilators($rooms, $invigilators, $plan->exam_date->toDateString());
            $invRows = [];
            foreach ($invMap as $roomId => $invigilatorIds) {
                foreach ($invigilatorIds as $iid) {
                    $invRows[] = [
                        'seating_plan_id' => $plan->id,
                        'room_id'         => $roomId,
                        'invigilator_id'  => $iid,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }
            if ($invRows) InvigilatorAssignment::insert($invRows);

            $this->viewingPlanId = $plan->id;
        });

        $this->notification()->success("Plan generated. {$result['totals']['students']} students seated, {$result['totals']['conflicts']} conflicts.");
        $this->closeGeneratePanel();
        $this->activeTab = 'plans';
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  PLAN ACTIONS
    // ═══════════════════════════════════════════════════════════════════════

    public function viewPlan(int $id): void { $this->viewingPlanId = $id; }
    public function closePlanView(): void { $this->viewingPlanId = null; }

    public function publishPlan(int $id): void
    {
        SeatingPlanModel::where('id', $id)
            ->where('organization_id', Auth::user()->organization_id)
            ->update(['status' => 'published']);
        $this->notification()->success('Plan published.');
    }

    public function confirmDeletePlan(int $id): void { $this->pendingDeletePlanId = $id; }
    public function cancelDeletePlan(): void { $this->pendingDeletePlanId = null; }
    public function executeDeletePlan(): void
    {
        if ($this->pendingDeletePlanId) {
            SeatingPlanModel::where('id', $this->pendingDeletePlanId)
                ->where('organization_id', Auth::user()->organization_id)
                ->delete();
            $this->notification()->success('Plan deleted.');
            if ($this->viewingPlanId === $this->pendingDeletePlanId) $this->viewingPlanId = null;
        }
        $this->pendingDeletePlanId = null;
    }

    public function render()
    {
        $orgId = Auth::user()->organization_id;

        $rooms = SeatingRoom::where('organization_id', $orgId)->orderBy('room_name')->get();
        $invigilators = SeatingInvigilator::where('organization_id', $orgId)->orderBy('name')->get();
        $exams = Exam::where('organization_id', $orgId)->orderBy('start_date', 'desc')->get(['id', 'exam_name', 'academic_year', 'exam_type', 'start_date']);
        $standards = Standard::where('organization_id', $orgId)->where('is_active', true)->orderBy('id')->get(['id', 'name']);

        $plans = SeatingPlanModel::with('exam:id,exam_name,exam_type')
            ->where('organization_id', $orgId)
            ->when($this->planSearch, fn($q) => $q->where('name', 'like', '%' . $this->planSearch . '%'))
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $viewingPlan = null;
        $planRooms = collect();
        $planAssignments = collect();
        $planInvigilators = collect();
        if ($this->viewingPlanId) {
            $viewingPlan = SeatingPlanModel::with('exam')->find($this->viewingPlanId);
            if ($viewingPlan) {
                $planAssignments = SeatAssignment::with(['seat', 'student:id,name'])
                    ->where('seating_plan_id', $viewingPlan->id)
                    ->orderBy('room_id')->orderBy('id')
                    ->get();
                $planRooms = SeatingRoom::whereIn('id', $planAssignments->pluck('room_id')->unique())->get();
                $planInvigilators = InvigilatorAssignment::with('invigilator:id,name,phone')
                    ->where('seating_plan_id', $viewingPlan->id)->get();
            }
        }

        return view('livewire.admin.seating-plan', compact(
            'rooms', 'invigilators', 'exams', 'standards', 'plans',
            'viewingPlan', 'planRooms', 'planAssignments', 'planInvigilators'
        ));
    }
}
