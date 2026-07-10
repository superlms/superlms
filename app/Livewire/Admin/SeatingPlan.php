<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamDatesheet;
use App\Models\Admin\ExamDatesheetPaper;
use App\Models\Admin\Seating\InvigilatorAssignment;
use App\Models\Admin\Seating\SeatAssignment;
use App\Models\Admin\Seating\SeatingInvigilator;
use App\Models\Admin\Seating\SeatingPlan as SeatingPlanModel;
use App\Models\Admin\Seating\SeatingRoom;
use App\Models\Admin\Seating\SeatingSeat;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Services\Seating\SeatingPlannerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class SeatingPlan extends Component
{
    use WithPagination, WireUiActions;

    public string $activeTab = 'plans'; // plans, rooms, datesheet

    // ─── Datesheet ──────────────────────────────────────────────────────────
    public bool $showDatesheetPanel = false;
    public ?int $editDatesheetId = null;
    public $dsExamId = '';
    public $dsStandardId = '';
    public $dsSectionId = '';
    public array $dsPapers = [];   // [subject_id => ['name','exam_date','start_time','end_time','shift']]
    public ?int $viewingDatesheetId = null;
    public ?int $pendingDeleteDatesheetId = null;

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
    // Datesheet-driven: pick an exam, the datesheet tells us which classes have
    // papers on which dates. One seating plan is generated per exam session
    // (date + shift) across the selected classes.
    public bool $showGeneratePanel = false;
    public array $generateForm = [
        'exam_id' => '', 'name' => '',
        'standard_ids' => [], 'room_ids' => [],
    ];

    // ─── Confirm delete state ───────────────────────────────────────────────
    public ?int $pendingDeleteRoomId = null;
    public ?int $pendingDeleteInvigilatorId = null;
    public ?int $pendingDeletePlanId = null;

    // ─── Plan viewer ────────────────────────────────────────────────────────
    public ?int $viewingPlanId = null;

    // ─── Filters ────────────────────────────────────────────────────────────
    public string $planSearch = '';

    // ─── Graphical seat finder (exam → session → class → section → student → room) ──
    public string $filterExamId     = '';
    public string $filterPlanId     = '';
    public string $filterStandardId = '';
    public string $filterSectionId  = '';
    public string $filterStudentId  = '';   // holds the student's user_id
    public string $filterRoomId     = '';

    public function mount(): void
    {
        //
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  GRAPHICAL SEAT FINDER
    // ═══════════════════════════════════════════════════════════════════════

    /** Picking an exam auto-selects its first seating session and resets the rest. */
    public function updatedFilterExamId(): void
    {
        $this->reset(['filterPlanId', 'filterRoomId']);
        if ($this->filterExamId) {
            $first = SeatingPlanModel::where('organization_id', Auth::user()->organization_id)
                ->where('exam_id', (int) $this->filterExamId)
                ->orderBy('exam_date')->orderBy('id')->first();
            $this->filterPlanId = $first ? (string) $first->id : '';
        }
    }

    public function updatedFilterPlanId(): void
    {
        $this->filterRoomId = '';
    }

    public function updatedFilterStandardId(): void
    {
        $this->filterSectionId = '';
        $this->filterStudentId = '';
    }

    public function updatedFilterSectionId(): void
    {
        $this->filterStudentId = '';
    }

    public function clearGraphFilters(): void
    {
        $this->reset([
            'filterExamId', 'filterPlanId', 'filterStandardId',
            'filterSectionId', 'filterStudentId', 'filterRoomId',
        ]);
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
        $orgId = Auth::user()->organization_id;
        $this->generateForm = [
            'exam_id'      => '',
            'name'         => '',
            'standard_ids' => [],
            // Rooms: all active rooms selected by default; user can change.
            'room_ids'     => SeatingRoom::where('organization_id', $orgId)
                ->where('is_active', true)->pluck('id')
                ->map(fn($id) => (string) $id)->all(),
        ];
        $this->showGeneratePanel = true;
    }

    public function closeGeneratePanel(): void
    {
        $this->showGeneratePanel = false;
    }

    /** When the exam is picked, auto-select every class that has a datesheet for it. */
    public function updatedGenerateFormExamId(): void
    {
        $this->preselectDatesheetClasses();

        // Suggest a plan name from the exam if the user hasn't typed one.
        if (empty($this->generateForm['name']) && $this->generateForm['exam_id']) {
            $exam = Exam::where('organization_id', Auth::user()->organization_id)
                ->find($this->generateForm['exam_id']);
            if ($exam) {
                $this->generateForm['name'] = $exam->exam_name . ' — Seating';
            }
        }
    }

    /** Classes (standards) that have at least one dated paper for the chosen exam. */
    private function datesheetStandardIds(): array
    {
        $orgId  = Auth::user()->organization_id;
        $examId = (int) ($this->generateForm['exam_id'] ?? 0);
        if (!$examId) return [];

        return ExamDatesheet::where('organization_id', $orgId)
            ->where('exam_id', $examId)
            ->whereHas('papers', fn($q) => $q->whereNotNull('exam_date'))
            ->pluck('standard_id')->unique()->values()->all();
    }

    private function preselectDatesheetClasses(): void
    {
        // Store as strings so the checkbox bindings render as checked.
        $this->generateForm['standard_ids'] = array_map('strval', $this->datesheetStandardIds());
    }

    public function selectAllClasses(): void
    {
        $this->preselectDatesheetClasses();
    }

    public function clearAllClasses(): void
    {
        $this->generateForm['standard_ids'] = [];
    }

    public function selectAllRooms(): void
    {
        $orgId = Auth::user()->organization_id;
        $this->generateForm['room_ids'] = SeatingRoom::where('organization_id', $orgId)
            ->where('is_active', true)->pluck('id')->map(fn($id) => (string) $id)->all();
    }

    public function clearAllRooms(): void
    {
        $this->generateForm['room_ids'] = [];
    }

    public function generatePlan(SeatingPlannerService $planner): void
    {
        $this->validate([
            'generateForm.exam_id'        => 'required|integer|exists:exams,id',
            'generateForm.name'           => 'required|string|max:150',
            'generateForm.standard_ids'   => 'required|array|min:1',
            'generateForm.standard_ids.*' => 'integer',
            'generateForm.room_ids'       => 'required|array|min:1',
            'generateForm.room_ids.*'     => 'integer',
        ]);

        $orgId      = Auth::user()->organization_id;
        $examId     = (int) $this->generateForm['exam_id'];
        $baseName   = trim($this->generateForm['name']);
        $standardIds = array_map('intval', $this->generateForm['standard_ids']);

        // 1. Pull the datesheets (with papers) for this exam + selected classes.
        $datesheets = ExamDatesheet::with('papers.subject:id,name')
            ->where('organization_id', $orgId)
            ->where('exam_id', $examId)
            ->whereIn('standard_id', $standardIds)
            ->get();

        if ($datesheets->isEmpty()) {
            $this->notification()->error('No datesheet found for the selected exam & classes. Create a datesheet first.');
            return;
        }

        // 2. Group papers into exam sessions keyed by date + shift. Each session
        //    lists which class/section is examined and the subject involved.
        $sessions = []; // "Y-m-d|shift" => ['date','shift','entries'=>[['standard_id','section_id','subject']]]
        foreach ($datesheets as $ds) {
            foreach ($ds->papers as $p) {
                if (empty($p->exam_date)) continue;
                $dateStr = $p->exam_date->toDateString();
                $shift   = (int) ($p->shift ?: 1);
                $key     = $dateStr . '|' . $shift;

                $sessions[$key]['date']  = $dateStr;
                $sessions[$key]['shift'] = $shift;
                $sessions[$key]['entries'][] = [
                    'standard_id' => $ds->standard_id,
                    'section_id'  => $ds->section_id,
                    'subject'     => $p->subject->name ?? '',
                ];
            }
        }

        if (empty($sessions)) {
            $this->notification()->error('The datesheet has no dated papers to seat.');
            return;
        }

        // 3. Rooms selected for seating (active only).
        $baseRooms = SeatingRoom::with('seats')
            ->whereIn('id', $this->generateForm['room_ids'])
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->get();

        if ($baseRooms->isEmpty()) {
            $this->notification()->error('No active rooms selected.');
            return;
        }

        ksort($sessions); // chronological order

        $invigilators   = SeatingInvigilator::where('organization_id', $orgId)->get();
        $firstPlanId    = null;
        $createdPlans   = 0;
        $seatedTotal    = 0;
        $skippedNoStud  = 0;

        try {
        foreach ($sessions as $session) {
            // 3a. Students examined in this session: union of the classes/sections
            //     that have a paper on this date+shift.
            $students = StudentDetail::with(['standard:id,name', 'section:id,name'])
                ->where('organization_id', $orgId)
                ->whereNotNull('user_id')
                ->where(function ($q) use ($session) {
                    foreach ($session['entries'] as $e) {
                        $q->orWhere(function ($qq) use ($e) {
                            $qq->where('standard_id', $e['standard_id']);
                            if (!empty($e['section_id'])) {
                                $qq->where('section_id', $e['section_id']);
                            }
                        });
                    }
                })
                ->get()
                ->unique('user_id')
                ->values();

            if ($students->isEmpty()) {
                $skippedNoStud++;
                continue;
            }

            $studentInput = $students->map(fn($s) => [
                'id'          => $s->user_id,
                'name'        => $s->full_name,
                'class_label' => ($s->standard->name ?? '?') . '-' . ($s->section->name ?? '-'),
            ])->toArray();

            // 3b. Rooms for this session (clone the base list; add an overflow hall
            //     unique to this session if capacity is short).
            $rooms = $baseRooms->map(fn($r) => $r)->values();
            $capacity = (int) $rooms->sum('capacity');
            $overflow = $students->count() - $capacity;
            if ($overflow > 0) {
                $rooms->push($this->overflowHall($orgId, $session, $overflow));
            }

            $result = $planner->plan($studentInput, $rooms);

            DB::transaction(function () use ($result, $rooms, $orgId, $examId, $baseName, $session, $invigilators, $planner, &$firstPlanId, &$createdPlans, &$seatedTotal) {
                $label   = \Carbon\Carbon::parse($session['date'])->format('d M Y');
                $subject = collect($session['entries'])->pluck('subject')->filter()->unique()->implode(', ');
                $name    = $baseName . ' — ' . $label . ($session['shift'] > 1 ? ' (Shift ' . $session['shift'] . ')' : '');

                $plan = SeatingPlanModel::create([
                    'organization_id' => $orgId,
                    'exam_id'         => $examId,
                    'name'            => $name,
                    'exam_date'       => $session['date'],
                    'session'         => 'Shift ' . $session['shift'],
                    'status'          => 'draft',
                    'generated_at'    => now(),
                    'total_students'  => $result['totals']['students'],
                    'total_seats'     => $result['totals']['seats'],
                    'conflict_count'  => $result['totals']['conflicts'],
                    'notes'           => $subject !== '' ? 'Subjects: ' . $subject : null,
                ]);

                $now  = now();
                $rows = [];
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

                // Auto-assign invigilators for this session's date.
                $invMap  = $planner->assignInvigilators($rooms, $invigilators, $session['date']);
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

                $firstPlanId = $firstPlanId ?? $plan->id;
                $createdPlans++;
                $seatedTotal += $result['totals']['students'];
            });
        }
        } catch (\Throwable $e) {
            report($e);
            $this->notification()->error('Could not generate the seating plan: ' . $e->getMessage());
            return;
        }

        if ($createdPlans === 0) {
            $this->notification()->error('No students found for the selected classes on any datesheet date.');
            return;
        }

        $this->viewingPlanId = $firstPlanId;
        $msg = "{$createdPlans} seating plan(s) generated · {$seatedTotal} student-seatings.";
        if ($skippedNoStud > 0) {
            $msg .= " {$skippedNoStud} date(s) skipped (no students).";
        }
        $this->notification()->success($msg);
        $this->closeGeneratePanel();
        $this->activeTab = 'plans';
    }

    /**
     * Get (or resize) an overflow "Exam Hall" sized to the session's overflow.
     * The hall is unique per exam session so generating multiple plans in one
     * batch never clobbers an earlier plan's seats (seat rows cascade-delete
     * when a hall's seats are regenerated).
     */
    private function overflowHall(int $orgId, array $session, int $overflow): SeatingRoom
    {
        $cols       = 6;
        $rowsNeeded = max(1, (int) ceil($overflow / $cols));
        $hallName   = 'Exam Hall ' . $session['date'] . ' S' . $session['shift'];

        $hall = SeatingRoom::firstOrNew([
            'organization_id' => $orgId,
            'room_name'       => $hallName,
        ]);
        $hall->building  = 'Overflow';
        $hall->rows      = $rowsNeeded;
        $hall->columns   = $cols;
        $hall->capacity  = $rowsNeeded * $cols;
        $hall->is_active = true;
        $hall->save();
        $this->regenerateSeats($hall);

        return $hall->load('seats');
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

    // ═══════════════════════════════════════════════════════════════════════
    //  DATESHEET
    // ═══════════════════════════════════════════════════════════════════════

    public function openDatesheetCreate(): void
    {
        $this->resetErrorBag();
        $this->editDatesheetId = null;
        $this->dsExamId = '';
        $this->dsStandardId = '';
        $this->dsSectionId = '';
        $this->dsPapers = [];
        $this->showDatesheetPanel = true;
    }

    public function closeDatesheetPanel(): void
    {
        $this->showDatesheetPanel = false;
        $this->editDatesheetId = null;
    }

    public function updatedDsStandardId(): void
    {
        $this->dsSectionId = '';
        $this->loadDsSubjects();
    }

    public function updatedDsSectionId(): void
    {
        $this->loadDsSubjects();
    }

    /** Load the subjects for the chosen class/section as blank datesheet rows. */
    private function loadDsSubjects(): void
    {
        $this->dsPapers = [];
        if (!$this->dsStandardId) return;

        $orgId = Auth::user()->organization_id;

        if ($this->dsSectionId) {
            $subjects = Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $this->dsSectionId)
                ->where('section_subjects.standard_id', $this->dsStandardId)
                ->where('subjects.organization_id', $orgId)->where('subjects.is_active', true)
                ->select('subjects.*')->distinct()->orderBy('subjects.name')->get();
        } else {
            $subjects = Subject::join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
                ->where('standard_subjects.standard_id', $this->dsStandardId)
                ->where('subjects.organization_id', $orgId)->where('subjects.is_active', true)
                ->select('subjects.*')->distinct()->orderBy('subjects.name')->get();
        }

        foreach ($subjects as $s) {
            $this->dsPapers[$s->id] = [
                'name' => $s->name, 'exam_date' => '', 'start_time' => '', 'end_time' => '', 'shift' => 1,
            ];
        }
    }

    public function saveDatesheet(): void
    {
        $this->validate([
            'dsExamId'     => 'required|integer|exists:exams,id',
            'dsStandardId' => 'required|integer|exists:standards,id',
            'dsSectionId'  => 'nullable|integer|exists:sections,id',
        ]);

        $filled = collect($this->dsPapers)->filter(fn($p) => !empty($p['exam_date']));
        if ($filled->isEmpty()) {
            $this->notification()->error('Set a date for at least one subject.');
            return;
        }

        $orgId = Auth::user()->organization_id;

        DB::transaction(function () use ($orgId) {
            $ds = ExamDatesheet::updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'exam_id'         => $this->dsExamId,
                    'standard_id'     => $this->dsStandardId,
                    'section_id'      => $this->dsSectionId ?: null,
                ],
                []
            );

            ExamDatesheetPaper::where('exam_datesheet_id', $ds->id)->delete();

            foreach ($this->dsPapers as $subjectId => $p) {
                if (empty($p['exam_date'])) continue;
                ExamDatesheetPaper::create([
                    'exam_datesheet_id' => $ds->id,
                    'subject_id'        => $subjectId,
                    'exam_date'         => $p['exam_date'],
                    'start_time'        => $p['start_time'] ?: null,
                    'end_time'          => $p['end_time'] ?: null,
                    'shift'             => (int) ($p['shift'] ?? 1),
                ]);
            }
        });

        $this->notification()->success('Datesheet saved.');
        $this->closeDatesheetPanel();
    }

    public function viewDatesheet(int $id): void { $this->viewingDatesheetId = $id; }
    public function closeDatesheetView(): void { $this->viewingDatesheetId = null; }

    public function confirmDeleteDatesheet(int $id): void { $this->pendingDeleteDatesheetId = $id; }
    public function cancelDeleteDatesheet(): void { $this->pendingDeleteDatesheetId = null; }
    public function executeDeleteDatesheet(): void
    {
        if ($this->pendingDeleteDatesheetId) {
            $ds = ExamDatesheet::where('id', $this->pendingDeleteDatesheetId)
                ->where('organization_id', Auth::user()->organization_id)->first();
            if ($ds) {
                ExamDatesheetPaper::where('exam_datesheet_id', $ds->id)->delete();
                $ds->delete();
                $this->notification()->success('Datesheet deleted.');
            }
            if ($this->viewingDatesheetId === $this->pendingDeleteDatesheetId) $this->viewingDatesheetId = null;
        }
        $this->pendingDeleteDatesheetId = null;
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

        // ── Datesheet tab data ──
        $datesheets = ExamDatesheet::with(['exam:id,exam_name', 'standard:id,name', 'section:id,name'])
            ->withCount('papers')
            ->where('organization_id', $orgId)
            ->orderBy('created_at', 'desc')
            ->get();

        $dsSections = $this->dsStandardId
            ? Section::where('standard_id', $this->dsStandardId)->where('is_active', true)->orderBy('id')->get(['id', 'name'])
            : collect();

        $viewingDatesheet = $this->viewingDatesheetId
            ? ExamDatesheet::with(['exam:id,exam_name', 'standard:id,name', 'section:id,name', 'papers.subject:id,name'])
                ->where('organization_id', $orgId)->find($this->viewingDatesheetId)
            : null;

        // Classes that have a datesheet for the exam chosen in the Generate panel
        // (used to highlight / default-select the class checkboxes).
        $datesheetStdIds = $this->showGeneratePanel ? $this->datesheetStandardIds() : [];

        // ── Graphical seat finder ──
        $filterPlans = $this->filterExamId
            ? SeatingPlanModel::where('organization_id', $orgId)
                ->where('exam_id', (int) $this->filterExamId)
                ->orderBy('exam_date')->orderBy('id')
                ->get(['id', 'name', 'exam_date', 'session'])
            : collect();

        $filterSections = $this->filterStandardId
            ? Section::where('standard_id', $this->filterStandardId)->where('is_active', true)
                ->orderBy('id')->get(['id', 'name'])
            : collect();

        $filterStudents = $this->filterStandardId
            ? StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $this->filterStandardId)
                ->when($this->filterSectionId, fn($q) => $q->where('section_id', $this->filterSectionId))
                ->whereNotNull('user_id')
                ->orderBy('roll_no')->get(['id', 'user_id', 'full_name', 'roll_no'])
            : collect();

        $graphPlan        = null;
        $graphRooms       = collect();
        $graphAssignments = collect();
        $graphRoomOptions = collect();
        $graphMatchIds    = [];          // user_ids matching the class/section filter
        $graphFocusId     = $this->filterStudentId ? (int) $this->filterStudentId : null;

        if ($this->filterPlanId) {
            $graphPlan = SeatingPlanModel::with('exam:id,exam_name')
                ->where('organization_id', $orgId)->find($this->filterPlanId);

            if ($graphPlan) {
                $graphAssignments = SeatAssignment::with(['seat', 'student:id,name'])
                    ->where('seating_plan_id', $graphPlan->id)
                    ->when($this->filterRoomId, fn($q) => $q->where('room_id', (int) $this->filterRoomId))
                    ->orderBy('room_id')->orderBy('id')->get();

                $graphRooms = SeatingRoom::whereIn('id', $graphAssignments->pluck('room_id')->unique())
                    ->orderBy('room_name')->get();

                // All rooms in this plan (for the Room dropdown — unaffected by room filter).
                $allRoomIds = SeatAssignment::where('seating_plan_id', $graphPlan->id)
                    ->distinct()->pluck('room_id');
                $graphRoomOptions = SeatingRoom::whereIn('id', $allRoomIds)
                    ->orderBy('room_name')->get(['id', 'room_name']);

                if ($this->filterStandardId || $this->filterSectionId) {
                    $graphMatchIds = StudentDetail::where('organization_id', $orgId)
                        ->when($this->filterStandardId, fn($q) => $q->where('standard_id', $this->filterStandardId))
                        ->when($this->filterSectionId, fn($q) => $q->where('section_id', $this->filterSectionId))
                        ->whereNotNull('user_id')
                        ->pluck('user_id')->map(fn($v) => (int) $v)->all();
                }
            }
        }

        $graphFiltersActive = $this->filterStandardId || $this->filterSectionId || $this->filterStudentId || $this->filterRoomId;

        return view('livewire.admin.seating-plan', compact(
            'rooms', 'invigilators', 'exams', 'standards', 'plans',
            'viewingPlan', 'planRooms', 'planAssignments', 'planInvigilators',
            'datesheets', 'dsSections', 'viewingDatesheet', 'datesheetStdIds',
            'filterPlans', 'filterSections', 'filterStudents',
            'graphPlan', 'graphRooms', 'graphAssignments', 'graphRoomOptions',
            'graphMatchIds', 'graphFocusId', 'graphFiltersActive'
        ));
    }
}
