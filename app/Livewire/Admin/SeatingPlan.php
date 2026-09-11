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
use App\Services\Seating\SeatLocator;
use App\Support\SeatLabel;
use App\Services\Seating\SeatingPlannerService;
use Illuminate\Support\Str;
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

    // ─── Datesheet filters — the tab shows nothing until a section is picked ──
    public string $dsFilterExamId     = '';
    public string $dsFilterStandardId = '';
    public string $dsFilterSectionId  = '';
    public string $dsFilterSubjectId  = '';
    public ?int $viewingDatesheetId = null;
    public ?int $pendingDeleteDatesheetId = null;

    // ─── Room form ──────────────────────────────────────────────────────────
    public bool $showRoomPanel = false;
    public ?int $editRoomId = null;
    public ?int $viewRoomId = null;          // room whose seat map is open
    public array $roomForm = [
        'room_name' => '', 'building' => '', 'rows' => 5, 'columns' => 6, 'seat_capacity' => 1,
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

    // ─── Seat finder ────────────────────────────────────────────────────────
    // Two ways in, chosen by $graphMode. Each asks for what it is named after
    // and nothing else, and both end at the same list of papers.
    //   'room'  — exam → room: every paper written in that room.
    //   'class' — exam → class → section: every paper the class sits, and where.
    public string $graphMode        = 'room';
    public string $filterExamId     = '';
    public string $filterStandardId = '';
    public string $filterSectionId  = '';
    public string $filterRoomId     = '';


    public function mount(): void
    {
        //
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  GRAPHICAL SEAT FINDER
    // ═══════════════════════════════════════════════════════════════════════

    /** Switch between looking room by room and class by class. */
    public function setGraphMode(string $mode): void
    {
        $this->graphMode = in_array($mode, ['room', 'class'], true) ? $mode : 'room';

        // Each mode keeps the exam and drops what the other one asked for.
        $this->reset($this->graphMode === 'room'
            ? ['filterStandardId', 'filterSectionId']
            : ['filterRoomId']);
    }

    /** Picking an exam drops everything downstream. */
    public function updatedFilterExamId(): void
    {
        $this->reset(['filterStandardId', 'filterSectionId', 'filterRoomId']);
    }

    public function updatedFilterStandardId(): void
    {
        $this->reset(['filterSectionId']);
    }

    public function clearGraphFilters(): void
    {
        $this->reset(['filterExamId', 'filterStandardId', 'filterSectionId', 'filterRoomId']);
    }

    /**
     * The class the filters name, as the planner writes it on an assignment
     * ("10-A"). Without a section it matches every section of the class.
     */
    private function classLabelFilter($query, string $column = 'class_label')
    {
        if (!$this->filterStandardId) {
            return $query;
        }

        $standard = Standard::where('organization_id', Auth::user()->organization_id)
            ->find($this->filterStandardId);
        if (!$standard) {
            return $query;
        }

        if ($this->filterSectionId) {
            $section = Section::find($this->filterSectionId);
            return $query->where($column, $standard->name . '-' . ($section->name ?? '-'));
        }

        return $query->where($column, 'like', $standard->name . '-%');
    }

    /**
     * Which paper the chosen class sits in each session, keyed "Y-m-d|shift".
     * A session is one date and shift for the whole school; the subject inside
     * it is the class's own, so it comes from the class's datesheet.
     *
     * @return array<string,string>
     */
    private function paperTitles(): array
    {
        if (!$this->filterExamId || !$this->filterStandardId) {
            return [];
        }

        $sheets = ExamDatesheet::with('papers.subject:id,name')
            ->where('organization_id', Auth::user()->organization_id)
            ->where('exam_id', (int) $this->filterExamId)
            ->where('standard_id', (int) $this->filterStandardId)
            ->when(
                $this->filterSectionId,
                fn ($q) => $q->where(fn ($qq) => $qq->whereNull('section_id')->orWhere('section_id', (int) $this->filterSectionId)),
            )
            ->orderByRaw('section_id IS NULL')   // the section's own sheet wins
            ->get();

        $titles = [];
        foreach ($sheets as $sheet) {
            foreach ($sheet->papers as $paper) {
                if (empty($paper->exam_date)) continue;
                $key = $paper->exam_date->toDateString() . '|' . (int) ($paper->shift ?: 1);
                // The first sheet seen wins, and section sheets come first.
                $titles[$key] ??= $paper->subject->name ?? 'Paper';
            }
        }

        return $titles;
    }

    /** Every generated session of the chosen exam, chronological. */
    private function examPlans()
    {
        if (! $this->filterExamId) {
            return collect();
        }

        return SeatingPlanModel::where('organization_id', Auth::user()->organization_id)
            ->where('exam_id', (int) $this->filterExamId)
            ->orderBy('exam_date')->orderBy('id')
            // The diagram's header shows the session's own state and totals.
            ->get(['id', 'name', 'exam_date', 'session', 'notes', 'status', 'total_students', 'total_seats', 'conflict_count']);
    }

    /**
     * Roll number, name and class for every seated student in the given
     * assignments — the seat chart prints roll numbers, not ids.
     *
     * @return array<int,array{roll:string,name:string,admission:?string}>
     */
    private function rollMapFor($assignments): array
    {
        $ids = collect($assignments)->pluck('student_id')->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return StudentDetail::where('organization_id', Auth::user()->organization_id)
            ->whereIn('user_id', $ids)
            ->get(['user_id', 'full_name', 'roll_no', 'admission_no'])
            ->keyBy('user_id')
            ->map(fn ($s) => [
                'roll'      => (string) ($s->roll_no ?: '—'),
                'name'      => (string) $s->full_name,
                'admission' => $s->admission_no,
            ])->all();
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
        // Editing from the seat map closes the map: both are slide-ins at the
        // same depth, and the form is rendered first, so the map would sit on
        // top of the panel the click just opened.
        $this->viewRoomId = null;
        $this->editRoomId = $id;
        if ($id) {
            $room = SeatingRoom::find($id);
            if ($room) {
                $this->roomForm = [
                    'room_name'     => $room->room_name,
                    'building'      => $room->building,
                    'rows'          => $room->rows,
                    'columns'       => $room->columns,
                    'seat_capacity' => max(1, (int) ($room->seat_capacity ?? 1)),
                    'is_active'     => $room->is_active,
                    'notes'         => $room->notes,
                ];
            }
        } else {
            $this->roomForm = [
                'room_name' => '', 'building' => '', 'rows' => 5, 'columns' => 6, 'seat_capacity' => 1,
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
            'roomForm.rows'          => 'required|integer|min:1|max:50',
            'roomForm.columns'       => 'required|integer|min:1|max:50',
            'roomForm.seat_capacity' => 'required|integer|min:1|max:10',
        ]);

        $rows = (int) $this->roomForm['rows'];
        $cols = (int) $this->roomForm['columns'];
        $perSeat = max(1, (int) $this->roomForm['seat_capacity']);
        // A seat is a desk, and a desk can take more than one candidate — so
        // the room holds rows × columns × seats-per-desk.
        $capacity = $rows * $cols * $perSeat;

        $data = [
            'organization_id' => Auth::user()->organization_id,
            'room_name'       => $this->roomForm['room_name'],
            'building'        => $this->roomForm['building'],
            'rows'            => $rows,
            'columns'         => $cols,
            'seat_capacity'   => $perSeat,
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
                    'seat_number' => SeatLabel::seat($r, $c), // A1, B1, A2, … — column letter, row number
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }
        SeatingSeat::insert($rows);
    }

    /** Open a room's seat map — every desk drawn, one icon per candidate. */
    public function viewRoom(int $id): void
    {
        $this->closeRoomPanel();   // only one slide-in at a time
        $this->viewRoomId = $id;
    }
    public function closeRoomView(): void { $this->viewRoomId = null; }

    public function confirmDeleteRoom(int $id): void { $this->pendingDeleteRoomId = $id; }
    public function cancelDeleteRoom(): void { $this->pendingDeleteRoomId = null; }
    public function executeDeleteRoom(): void
    {
        if ($this->pendingDeleteRoomId) {
            SeatingRoom::where('id', $this->pendingDeleteRoomId)
                ->where('organization_id', Auth::user()->organization_id)
                ->delete();
            if ($this->viewRoomId === $this->pendingDeleteRoomId) $this->viewRoomId = null;
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
                        'seat_position'   => $a['seat_position'] ?? 1,
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
        $hall->rows          = $rowsNeeded;
        $hall->columns       = $cols;
        $hall->seat_capacity = 1;
        $hall->capacity      = $rowsNeeded * $cols;
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
        // Start from whatever the filters point at, so "create" from the empty
        // state lands on the class the user was already looking for.
        $this->dsExamId     = $this->dsFilterExamId;
        $this->dsStandardId = $this->dsFilterStandardId;
        $this->dsSectionId  = $this->dsFilterSectionId;
        $this->dsPapers     = [];
        if ($this->dsStandardId) {
            $this->loadDsSubjects();
        }
        $this->showDatesheetPanel = true;
    }

    public function closeDatesheetPanel(): void
    {
        $this->showDatesheetPanel = false;
        $this->editDatesheetId = null;
    }

    public function updatedDsFilterExamId(): void
    {
        $this->dsFilterSubjectId = '';
    }

    public function updatedDsFilterStandardId(): void
    {
        $this->dsFilterSectionId = '';
        $this->dsFilterSubjectId = '';
    }

    public function updatedDsFilterSectionId(): void
    {
        $this->dsFilterSubjectId = '';
    }

    public function clearDatesheetFilters(): void
    {
        $this->reset(['dsFilterExamId', 'dsFilterStandardId', 'dsFilterSectionId', 'dsFilterSubjectId']);
    }

    /** The datesheet the filters point at: the section's own, else the class-wide one. */
    private function filteredDatesheet(): ?ExamDatesheet
    {
        if (!$this->dsFilterExamId || !$this->dsFilterStandardId || !$this->dsFilterSectionId) {
            return null;
        }

        return ExamDatesheet::with(['exam:id,exam_name,academic_year', 'standard:id,name', 'section:id,name', 'papers.subject:id,name'])
            ->where('organization_id', Auth::user()->organization_id)
            ->where('exam_id', $this->dsFilterExamId)
            ->where('standard_id', $this->dsFilterStandardId)
            ->where(fn ($q) => $q->where('section_id', $this->dsFilterSectionId)->orWhereNull('section_id'))
            ->orderByRaw('section_id IS NULL')  // the section's own sheet wins
            ->first();
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

    /** Load an existing datesheet into the create panel for editing. */
    public function openDatesheetEdit(int $id): void
    {
        $ds = ExamDatesheet::with('papers.subject:id,name')
            ->where('organization_id', Auth::user()->organization_id)
            ->find($id);

        if (!$ds) {
            $this->notification()->error('That datesheet is gone.');
            return;
        }

        $this->resetErrorBag();
        $this->editDatesheetId = $ds->id;
        $this->dsExamId     = (string) $ds->exam_id;
        $this->dsStandardId = (string) $ds->standard_id;
        $this->dsSectionId  = (string) ($ds->section_id ?? '');

        // Start from the class's subject list so a subject with no paper yet is
        // still offered, then fill in what the sheet already holds.
        $this->loadDsSubjects();

        foreach ($ds->papers as $paper) {
            $row = $this->dsPapers[$paper->subject_id] ?? ['name' => $paper->subject->name ?? 'Subject'];
            $this->dsPapers[$paper->subject_id] = array_merge($row, [
                'exam_date'  => $paper->exam_date?->toDateString() ?? '',
                'start_time' => $paper->start_time ? substr($paper->start_time, 0, 5) : '',
                'end_time'   => $paper->end_time ? substr($paper->end_time, 0, 5) : '',
                'shift'      => (int) ($paper->shift ?: 1),
            ]);
        }

        $this->showDatesheetPanel = true;
    }

    /**
     * Two papers clash when the same class sits both at once: the same date
     * with overlapping times, or — when a paper carries no times — the same
     * date and shift. Checked inside the form and against every datesheet the
     * class already has, including the class-wide one a section inherits.
     *
     * @return string[] one line per clash
     */
    private function datesheetClashes(): array
    {
        $rows = [];
        foreach ($this->dsPapers as $subjectId => $p) {
            if (empty($p['exam_date'])) continue;
            $rows[] = [
                'subject' => $p['name'] ?? 'Subject',
                'date'    => $p['exam_date'],
                'start'   => $p['start_time'] ?: null,
                'end'     => $p['end_time'] ?: null,
                'shift'   => (int) ($p['shift'] ?? 1),
            ];
        }

        $clashes = [];

        // Inside the form
        for ($i = 0; $i < count($rows); $i++) {
            for ($j = $i + 1; $j < count($rows); $j++) {
                if ($this->papersOverlap($rows[$i], $rows[$j])) {
                    $clashes[] = $rows[$i]['subject'] . ' and ' . $rows[$j]['subject']
                        . ' are both on ' . $this->prettyDate($rows[$i]['date']) . ' at the same time.';
                }
            }
        }

        // The sheet this save writes to is replaced wholesale, so its own papers
        // are not a clash with themselves.
        $targetId = $this->editDatesheetId ?: ExamDatesheet::where('organization_id', Auth::user()->organization_id)
            ->where('exam_id', $this->dsExamId)
            ->where('standard_id', $this->dsStandardId)
            ->where(fn ($q) => $this->dsSectionId
                ? $q->where('section_id', $this->dsSectionId)
                : $q->whereNull('section_id'))
            ->value('id');

        // Against what the class already has (any exam — the class can only sit
        // one paper at a time, whoever set it).
        $existing = ExamDatesheetPaper::query()
            ->join('exam_datesheets as d', 'd.id', '=', 'exam_datesheet_papers.exam_datesheet_id')
            ->join('subjects as s', 's.id', '=', 'exam_datesheet_papers.subject_id')
            ->leftJoin('exams as e', 'e.id', '=', 'd.exam_id')
            ->where('d.organization_id', Auth::user()->organization_id)
            ->where('d.standard_id', $this->dsStandardId)
            ->when(
                $this->dsSectionId,
                // A section's papers clash with its own sheet and with the
                // class-wide sheet it inherits; a class-wide sheet clashes with
                // every section of the class, so it filters on nothing.
                fn ($q) => $q->where(fn ($qq) => $qq->whereNull('d.section_id')->orWhere('d.section_id', $this->dsSectionId)),
            )
            ->when($targetId, fn ($q) => $q->where('d.id', '!=', $targetId))
            ->get([
                'exam_datesheet_papers.exam_date', 'exam_datesheet_papers.start_time',
                'exam_datesheet_papers.end_time', 'exam_datesheet_papers.shift',
                's.name as subject_name', 'e.exam_name as exam_name',
            ]);

        foreach ($rows as $row) {
            foreach ($existing as $old) {
                $other = [
                    'date'  => \Carbon\Carbon::parse($old->exam_date)->toDateString(),
                    'start' => $old->start_time ? substr($old->start_time, 0, 5) : null,
                    'end'   => $old->end_time ? substr($old->end_time, 0, 5) : null,
                    'shift' => (int) ($old->shift ?: 1),
                ];
                if ($this->papersOverlap($row, $other)) {
                    $clashes[] = $row['subject'] . ' on ' . $this->prettyDate($row['date'])
                        . ' runs into ' . $old->subject_name
                        . ($old->exam_name ? ' (' . $old->exam_name . ')' : '')
                        . ', already set at that time for this class.';
                }
            }
        }

        return array_values(array_unique($clashes));
    }

    /** Same day, and either overlapping clock times or the same shift. */
    private function papersOverlap(array $a, array $b): bool
    {
        if ($a['date'] !== $b['date']) return false;

        if ($a['start'] && $a['end'] && $b['start'] && $b['end']) {
            return $a['start'] < $b['end'] && $b['start'] < $a['end'];
        }

        return (int) $a['shift'] === (int) $b['shift'];
    }

    private function prettyDate(string $date): string
    {
        try {
            return \Carbon\Carbon::parse($date)->format('d M Y');
        } catch (\Throwable $e) {
            return $date;
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

        $clashes = $this->datesheetClashes();
        if ($clashes) {
            $this->addError('dsPapers', implode(' ', array_slice($clashes, 0, 3)));
            $this->notification()->error($clashes[0]);
            return;
        }

        $orgId = Auth::user()->organization_id;

        DB::transaction(function () use ($orgId) {
            $ds = $this->editDatesheetId
                ? ExamDatesheet::where('organization_id', $orgId)->findOrFail($this->editDatesheetId)
                : null;

            if ($ds) {
                $ds->update([
                    'exam_id'     => $this->dsExamId,
                    'standard_id' => $this->dsStandardId,
                    'section_id'  => $this->dsSectionId ?: null,
                ]);
            }

            $ds = $ds ?: ExamDatesheet::updateOrCreate(
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

        // Point the tab's filters at what was just saved, so the sheet is on
        // screen instead of an empty state.
        $this->dsFilterExamId     = (string) $this->dsExamId;
        $this->dsFilterStandardId = (string) $this->dsStandardId;
        if ($this->dsSectionId) {
            $this->dsFilterSectionId = (string) $this->dsSectionId;
        }
        $this->dsFilterSubjectId = '';

        $this->notification()->success($this->editDatesheetId ? 'Datesheet updated.' : 'Datesheet saved.');
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

        // The plans are not listed any more — the finder is how you reach one —
        // so all the header needs is how many there are.
        $planCount = SeatingPlanModel::where('organization_id', $orgId)->count();

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

        $viewingRoom = $this->viewRoomId
            ? SeatingRoom::with('seats')->where('organization_id', $orgId)->find($this->viewRoomId)
            : null;

        // ── Datesheet tab data ──
        $datesheets = ExamDatesheet::with(['exam:id,exam_name', 'standard:id,name', 'section:id,name'])
            ->withCount('papers')
            ->where('organization_id', $orgId)
            ->orderBy('created_at', 'desc')
            ->get();

        $dsSections = $this->dsStandardId
            ? Section::where('standard_id', $this->dsStandardId)->where('is_active', true)->orderBy('id')->get(['id', 'name'])
            : collect();

        // The tab is filter-driven: exam → class → section, and only then a
        // sheet. A subject narrows that sheet to the one paper.
        $dsFilterSections = $this->dsFilterStandardId
            ? Section::where('standard_id', $this->dsFilterStandardId)->where('is_active', true)
                ->orderBy('id')->get(['id', 'name'])
            : collect();

        $filteredDatesheet = $this->filteredDatesheet();
        $filteredPapers    = collect();
        $dsFilterSubjects  = collect();

        if ($filteredDatesheet) {
            $papers = $filteredDatesheet->papers
                ->sortBy(fn ($p) => ($p->exam_date?->toDateString() ?? '9999-12-31') . ' ' . ($p->start_time ?? ''))
                ->values();

            $dsFilterSubjects = $papers->map(fn ($p) => [
                'id'   => $p->subject_id,
                'name' => $p->subject->name ?? 'Subject',
            ])->unique('id')->values();

            $filteredPapers = $this->dsFilterSubjectId
                ? $papers->where('subject_id', (int) $this->dsFilterSubjectId)->values()
                : $papers;
        }

        $viewingDatesheet = $this->viewingDatesheetId
            ? ExamDatesheet::with(['exam:id,exam_name', 'standard:id,name', 'section:id,name', 'papers.subject:id,name'])
                ->where('organization_id', $orgId)->find($this->viewingDatesheetId)
            : null;

        // Classes that have a datesheet for the exam chosen in the Generate panel
        // (used to highlight / default-select the class checkboxes).
        $datesheetStdIds = $this->showGeneratePanel ? $this->datesheetStandardIds() : [];

        // ══ Seat finder ══
        // By room or by class, the answer is the same list: one row per paper,
        // and every row opens, downloads or prints the same seating list.
        $examPlans = $this->examPlans();

        $filterSections = $this->filterStandardId
            ? Section::where('standard_id', $this->filterStandardId)->where('is_active', true)
                ->orderBy('id')->get(['id', 'name'])
            : collect();

        // Rooms that actually hold a seat in this exam — no point offering the rest.
        $graphRoomOptions = $examPlans->isEmpty() ? collect() : SeatingRoom::whereIn(
            'id',
            SeatAssignment::whereIn('seating_plan_id', $examPlans->pluck('id'))->distinct()->pluck('room_id')
        )->orderBy('room_name')->get(['id', 'room_name', 'building']);

        $sessionRows = collect();

        $finderReady = $this->filterExamId && ($this->graphMode === 'room'
            ? (bool) $this->filterRoomId
            : $this->filterStandardId && $this->filterSectionId);

        if ($finderReady && $examPlans->isNotEmpty()) {
            // A class reads its own paper off its datesheet; a room holds
            // several classes at once, so it takes the session's own subjects.
            $titles = $this->graphMode === 'class' ? $this->paperTitles() : [];

            $query = SeatAssignment::whereIn('seating_plan_id', $examPlans->pluck('id'))
                ->whereNotNull('student_id');

            $seated = ($this->graphMode === 'room'
                ? $query->where('room_id', (int) $this->filterRoomId)
                : $this->classLabelFilter($query)
            )->get(['id', 'seating_plan_id', 'room_id', 'student_id', 'class_label']);

            $roomNames = SeatingRoom::whereIn('id', $seated->pluck('room_id')->unique())
                ->pluck('room_name', 'id');

            $sessionRows = $seated->groupBy('seating_plan_id')
                ->map(function ($group, $planId) use ($examPlans, $titles, $roomNames) {
                    $plan = $examPlans->firstWhere('id', (int) $planId);
                    if (!$plan) return null;

                    $date  = $plan->exam_date?->toDateString() ?? '';
                    $shift = SeatLocator::shiftOf($plan->session);
                    $rooms = $group->pluck('room_id')->unique()->values();

                    return [
                        'plan_id'  => (int) $planId,
                        'status'   => $plan->status,
                        'date'     => $plan->exam_date,
                        'session'  => $plan->session,
                        'subject'  => $titles[$date . '|' . $shift]
                            ?? ($plan->notes ? Str::after($plan->notes, 'Subjects: ') : 'Paper'),
                        'students' => $group->count(),
                        'classes'  => $group->pluck('class_label')->filter()->unique()->sort()->values()->all(),
                        'rooms'    => $rooms->map(fn ($id) => $roomNames[$id] ?? '—')->sort()->values()->all(),
                    ];
                })
                ->filter()
                ->sortBy(fn ($row) => ($row['date']?->toDateString() ?? '9999-12-31') . '|' . $row['plan_id'])
                ->values();
        }

        $graphFiltersActive = $this->filterExamId || $this->filterStandardId
            || $this->filterSectionId || $this->filterRoomId;

        // The plan viewer's own chart shows roll numbers too.
        $planRollMap = $this->rollMapFor($planAssignments);

        return view('livewire.admin.seating-plan', compact(
            'rooms', 'invigilators', 'exams', 'standards', 'planCount', 'viewingRoom',
            'viewingPlan', 'planRooms', 'planAssignments', 'planInvigilators', 'planRollMap',
            'datesheets', 'dsSections', 'viewingDatesheet', 'datesheetStdIds',
            'dsFilterSections', 'dsFilterSubjects', 'filteredDatesheet', 'filteredPapers',
            'filterSections', 'graphRoomOptions', 'sessionRows', 'graphFiltersActive'
        ));
    }
}
