<?php

namespace App\Livewire\Admin;

use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class TimeTable extends Component
{
    use WireUiActions, WithPagination;

    // ─── Tabs / view mode ────────────────────────────────────────────────
    public string $viewMode = 'class'; // 'class' | 'teacher'

    // ─── Filters ─────────────────────────────────────────────────────────
    public string $filterClass   = '';
    public string $filterSection = '';
    public string $filterTeacher = '';
    public array  $filterDays    = [];
    public array  $filterSections = [];
    public int    $perPage       = 20;

    // ─── Add / Edit panel state ──────────────────────────────────────────
    public bool $open    = false;
    public bool $isEdit  = false;

    public string $createStandardId = '';
    public string $createSectionId  = '';
    public array  $createSections   = [];
    public array  $sectionSubjects  = []; // [['id'=>, 'name'=>], ...] for the subject dropdown
    public array  $scheduleRows     = []; // one row per (subject · time slot · teacher)

    // ─── Delete confirm ──────────────────────────────────────────────────
    public bool   $showDeleteConfirm = false;
    public string $deleteStandardId  = '';
    public string $deleteSectionId   = '';

    // ─── Lookup data ─────────────────────────────────────────────────────
    public $standards   = [];
    public $allTeachers = [];

    // ─── Stats ───────────────────────────────────────────────────────────
    public int $totalClasses      = 0; // classes (standards)
    public int $totalSections     = 0; // sections across all classes
    public int $timetableCreated  = 0; // sections that have a timetable
    public int $remainingSections = 0; // sections still without one

    public array $daysOfWeek = [
        1 => 'Mon', 2 => 'Tue', 3 => 'Wed',
        4 => 'Thu', 5 => 'Fri', 6 => 'Sat',
    ];
    public array $daysOfWeekFull = [
        1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
        4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
    ];
    /** Mon–Sat default for create */
    private array $defaultDays = [1, 2, 3, 4, 5, 6];

    protected $queryString = [
        'viewMode'      => ['except' => 'class'],
        'filterClass'   => ['except' => ''],
        'filterSection' => ['except' => ''],
        'filterTeacher' => ['except' => ''],
    ];

    public function mount(): void
    {
        $org = Auth::user()->organization_id;
        // Order to match the Standard management page (which lists by id),
        // so classes appear here in the same sequence admins see there —
        // e.g. Class 1, 2, … 10 instead of alphabetical (2, 10, …).
        $this->standards   = Standard::where('organization_id', $org)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
        $this->allTeachers = TeacherDetail::with('user:id,name,email,is_active')
            ->where('organization_id', $org)
            ->whereHas('user', fn($q) => $q->where('is_active', 1))
            ->get();
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $org = Auth::user()->organization_id;

        $this->totalClasses  = $this->standards->count();
        $this->totalSections = Section::whereIn('standard_id', $this->standards->pluck('id'))
            ->where('is_active', true)
            ->count();

        // Distinct (class, section) pairs that already have at least one entry.
        $this->timetableCreated = TeacherTimeTable::where('organization_id', $org)
            ->select('standard_id', 'section_id')
            ->distinct()
            ->get()
            ->count();

        $this->remainingSections = max(0, $this->totalSections - $this->timetableCreated);
    }

    // ─── Tab switch ──────────────────────────────────────────────────────
    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['class', 'teacher'], true) ? $mode : 'class';
        $this->resetPage();
    }

    // ─── Filter handlers ─────────────────────────────────────────────────
    public function updatedFilterClass(): void
    {
        $this->filterSection  = '';
        $this->filterSections = $this->filterClass
            ? Section::where('standard_id', $this->filterClass)
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->toArray()
            : [];
        $this->resetPage();
    }

    public function updatedFilterSection(): void { $this->resetPage(); }
    public function updatedFilterTeacher(): void { $this->resetPage(); }

    public function toggleFilterDay(int $day): void
    {
        if (!in_array($day, $this->defaultDays, true)) return;
        $this->filterDays = in_array($day, $this->filterDays, true)
            ? array_values(array_diff($this->filterDays, [$day]))
            : array_merge($this->filterDays, [$day]);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['filterClass', 'filterSection', 'filterTeacher', 'filterDays']);
        $this->filterSections = [];
        $this->resetPage();
    }

    // ─── Add / Edit panel ────────────────────────────────────────────────
    public function onCreateTimetable(): void
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->open   = true;
    }

    public function closePanel(): void
    {
        $this->open = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['createStandardId', 'createSectionId', 'scheduleRows']);
        $this->createSections  = [];
        $this->sectionSubjects = [];
    }

    public function updatedCreateStandardId(): void
    {
        $this->createSections = $this->createStandardId
            ? Section::where('standard_id', $this->createStandardId)
                ->where('is_active', true)
                ->orderBy('id')
                ->get()
                ->toArray()
            : [];
        $this->createSectionId  = '';
        $this->sectionSubjects  = [];
        $this->scheduleRows     = [];
    }

    public function updatedCreateSectionId(): void
    {
        $this->loadSectionSubjects();
        $this->buildScheduleRowsFromSection();
        $this->prefillRowsFromExisting();
    }

    /** Subjects available for the chosen section (drives the per-row Subject dropdown). */
    private function loadSectionSubjects(): void
    {
        $this->sectionSubjects = [];
        if (!$this->createStandardId || !$this->createSectionId) return;

        $org = Auth::user()->organization_id;
        $subjects = SectionSubject::with('subject')
            ->where('organization_id', $org)
            ->where('standard_id', $this->createStandardId)
            ->where('section_id', $this->createSectionId)
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values();

        // Fallback to all active org subjects if no section_subjects rows exist
        if ($subjects->isEmpty()) {
            $subjects = Subject::where('organization_id', $org)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
        }

        $this->sectionSubjects = $subjects
            ->map(fn($s) => ['id' => (int) $s->id, 'name' => $s->name])
            ->all();
    }

    /** Prefills scheduleRows from existing teacher_time_tables entries (auto-switches to edit mode). */
    private function prefillRowsFromExisting(): void
    {
        if (!$this->createStandardId || !$this->createSectionId) return;

        $org  = Auth::user()->organization_id;
        $rows = TeacherTimeTable::with('subject:id,name')
            ->where('organization_id', $org)
            ->where('standard_id', $this->createStandardId)
            ->where('section_id',  $this->createSectionId)
            ->get();
        if ($rows->isEmpty()) return;

        $this->isEdit = true;

        // One form row per (subject · time slot · teacher); the selected weekdays collapse
        // back into that row's Days multi-select.
        $this->scheduleRows = [];
        $groups = $rows->groupBy(fn($r) =>
            $r->subject_id . '|' . substr($r->start_time, 0, 5) . '|' . substr($r->end_time, 0, 5) . '|' . $r->teacher_detail_id
        );
        foreach ($groups as $group) {
            $first = $group->first();
            $this->scheduleRows[] = [
                'subject_id'   => (int) $first->subject_id,
                'subject_name' => $first->subject?->name ?? 'Subject',
                'start_time'   => substr($first->start_time, 0, 5),
                'end_time'     => substr($first->end_time, 0, 5),
                'teacher_id'   => (int) $first->teacher_detail_id,
                'days'         => $group->pluck('day_of_week')->map(fn($d) => (int) $d)->unique()->sort()->values()->all(),
            ];
        }
    }

    /** A blank schedule row with sensible defaults. */
    private function blankRow(): array
    {
        return [
            'subject_id'   => (int) ($this->sectionSubjects[0]['id'] ?? 0),
            'subject_name' => $this->sectionSubjects[0]['name'] ?? '',
            'start_time'   => '09:00',
            'end_time'     => '10:00',
            'teacher_id'   => '',
            'days'         => [],
        ];
    }

    /** Pre-populates one row per subject mapped to the chosen section. */
    private function buildScheduleRowsFromSection(): void
    {
        $this->scheduleRows = [];
        foreach ($this->sectionSubjects as $s) {
            $this->scheduleRows[] = [
                'subject_id'   => (int) $s['id'],
                'subject_name' => $s['name'],
                'start_time'   => '09:00',
                'end_time'     => '10:00',
                'teacher_id'   => '',
                'days'         => [],
            ];
        }
    }

    /** Keep subject_name in sync when the row's subject dropdown changes. */
    public function updatedScheduleRows($value, $key): void
    {
        if (!str_ends_with($key, '.subject_id')) return;
        $idx = (int) explode('.', $key)[0];
        if (!isset($this->scheduleRows[$idx])) return;
        $name = collect($this->sectionSubjects)->firstWhere('id', (int) $value)['name'] ?? '';
        $this->scheduleRows[$idx]['subject_name'] = $name;
    }

    public function addRow(): void
    {
        $this->scheduleRows[] = $this->blankRow();
    }

    public function removeRow(int $index): void
    {
        if (!isset($this->scheduleRows[$index])) return;
        unset($this->scheduleRows[$index]);
        $this->scheduleRows = array_values($this->scheduleRows);
    }

    /** Lesson length for a row, e.g. "1h 30m" — shown next to the time inputs. */
    public function rowDuration(int $rowIndex): string
    {
        $row = $this->scheduleRows[$rowIndex] ?? null;
        if (!$row || empty($row['start_time']) || empty($row['end_time'])) return '';
        try {
            $s = \Carbon\Carbon::createFromFormat('H:i', substr($row['start_time'], 0, 5));
            $e = \Carbon\Carbon::createFromFormat('H:i', substr($row['end_time'], 0, 5));
            if ($e->lessThanOrEqualTo($s)) return '';
            $mins = $s->diffInMinutes($e);
            $h = intdiv($mins, 60);
            $m = $mins % 60;
            return trim(($h ? "{$h}h " : '') . ($m ? "{$m}m" : ($h ? '' : '0m')));
        } catch (\Throwable $e) {
            return '';
        }
    }

    // ─── Day availability ────────────────────────────────────────────────
    /**
     * Days already taken by ANOTHER row that overlaps this row's time slot.
     * A class can only be in one place at a time, so if 09:00–10:00 is filled by
     * Hindi on Mon/Tue/Thu, those weekdays are gone for any other 09:00–10:00 subject.
     */
    public function occupiedDaysForRow(int $rowIndex): array
    {
        $row = $this->scheduleRows[$rowIndex] ?? null;
        if (!$row) return [];

        $start = substr($row['start_time'] ?? '', 0, 5);
        $end   = substr($row['end_time'] ?? '', 0, 5);
        if (!$start || !$end || $start >= $end) return [];

        $occupied = [];
        foreach ($this->scheduleRows as $j => $other) {
            if ($j === $rowIndex) continue;
            $os = substr($other['start_time'] ?? '', 0, 5);
            $oe = substr($other['end_time'] ?? '', 0, 5);
            if (!$os || !$oe || $os >= $oe) continue;
            if ($os >= $end || $oe <= $start) continue; // no time overlap
            foreach (($other['days'] ?? []) as $d) {
                $occupied[(int) $d] = true;
            }
        }

        return array_keys($occupied);
    }

    /**
     * Weekdays this row may still pick: Mon–Sat minus days occupied by an overlapping
     * row. Days already selected in THIS row always stay (they aren't "occupied by others").
     */
    public function availableDaysForRow(int $rowIndex): array
    {
        $occupied = $this->occupiedDaysForRow($rowIndex);
        $selected = array_map('intval', $this->scheduleRows[$rowIndex]['days'] ?? []);
        $avail = array_diff($this->defaultDays, $occupied);
        return array_values(array_unique(array_merge($avail, $selected)));
    }

    // ─── Conflict checks ─────────────────────────────────────────────────
    /**
     * Availability check for a whole row: is the chosen teacher already busy — in
     * another class/section (saved), or on another subject in this same form — at an
     * overlapping time on any of this row's selected days? Returns a short reason or null.
     */
    public function getRowConflict(int $rowIndex): ?string
    {
        $row = $this->scheduleRows[$rowIndex] ?? null;
        if (!$row) return null;

        $teacherId = (int) ($row['teacher_id'] ?? 0);
        if (!$teacherId) return null;

        $start = substr($row['start_time'] ?? '', 0, 5);
        $end   = substr($row['end_time'] ?? '', 0, 5);
        if (!$start || !$end || $start >= $end) return null;

        $days = array_map('intval', $row['days'] ?? []);
        if (empty($days)) return null;

        // 1) Teacher already booked elsewhere (another class/section) at this time.
        //    The current section is excluded — it gets wiped & recreated on save.
        $q = TeacherTimeTable::with(['standard:id,name', 'section:id,name'])
            ->where('teacher_detail_id', $teacherId)
            ->whereIn('day_of_week', $days)
            ->where('start_time', '<', $end)
            ->where('end_time',   '>', $start);

        if ($this->createStandardId && $this->createSectionId) {
            $q->where(function ($q2) {
                $q2->where('standard_id', '!=', $this->createStandardId)
                   ->orWhere('section_id', '!=', $this->createSectionId);
            });
        }

        if ($clash = $q->first()) {
            $where = trim(($clash->standard?->name ?? '') . ' ' . ($clash->section?->name ?? ''));
            $dn    = $this->daysOfWeek[(int) $clash->day_of_week] ?? $clash->day_of_week;
            return 'Busy with ' . ($where !== '' ? $where : 'another class') . " ({$dn})";
        }

        // 2) Same teacher used on another subject in this form at an overlapping time & day.
        foreach ($this->scheduleRows as $j => $other) {
            if ($j === $rowIndex) continue;
            if ((int) ($other['teacher_id'] ?? 0) !== $teacherId) continue;
            $os = substr($other['start_time'] ?? '', 0, 5);
            $oe = substr($other['end_time'] ?? '', 0, 5);
            if (!$os || !$oe) continue;
            if ($os >= $end || $oe <= $start) continue;
            $shared = array_intersect($days, array_map('intval', $other['days'] ?? []));
            if (!empty($shared)) {
                $dn = $this->daysOfWeek[(int) reset($shared)] ?? reset($shared);
                return "Teacher already on another subject at this time ({$dn})";
            }
        }

        return null;
    }

    // ─── Save (create or edit) ───────────────────────────────────────────
    public function onSaveTimetable(): void
    {
        if (!$this->createStandardId) { $this->notification()->error('Please select a class.'); return; }
        if (!$this->createSectionId)  { $this->notification()->error('Please select a section.'); return; }

        // Keep only rows that have a subject, a teacher and at least one day.
        $rowsToSave = collect($this->scheduleRows)
            ->map(function ($row, $idx) {
                $row['__idx'] = $idx;
                return $row;
            })
            ->filter(fn($r) => (int) ($r['subject_id'] ?? 0) > 0
                && (int) ($r['teacher_id'] ?? 0) > 0
                && !empty($r['days'] ?? []))
            ->values()
            ->all();

        if (empty($rowsToSave) && !$this->isEdit) {
            $this->notification()->error('Add at least one row with a subject, teacher and days to save.');
            return;
        }

        foreach ($rowsToSave as $row) {
            $n = $row['subject_name'] ?? ('Subject ' . ((int) $row['__idx'] + 1));
            if (!$row['start_time'] || !$row['end_time'] || $row['start_time'] >= $row['end_time']) {
                $this->notification()->error("{$n}: invalid time range."); return;
            }
            // Class double-booking guard (defensive — the Days dropdown already hides taken days).
            $occupiedClash = array_intersect(
                array_map('intval', $row['days']),
                $this->occupiedDaysForRow((int) $row['__idx'])
            );
            if (!empty($occupiedClash)) {
                $dn = $this->daysOfWeekFull[(int) reset($occupiedClash)] ?? reset($occupiedClash);
                $this->notification()->error("{$n} ({$dn}): the class is already scheduled at this time."); return;
            }
            if ($conflict = $this->getRowConflict((int) $row['__idx'])) {
                $this->notification()->error("{$n}: {$conflict}"); return;
            }
        }

        try {
            DB::beginTransaction();
            $org = Auth::user()->organization_id;

            // Edit mode → wipe all existing entries for this (class, section) and recreate
            if ($this->isEdit) {
                TeacherTimeTable::where('organization_id', $org)
                    ->where('standard_id', $this->createStandardId)
                    ->where('section_id', $this->createSectionId)
                    ->delete();
            }

            $created = 0;
            // Guard against duplicate (subject, day, start, end) inserts.
            $seen = [];

            $tryCreate = function (int $teacherId, int $subjectId, int $day, string $start, string $end) use ($org, &$seen, &$created) {
                $key = $subjectId . '|' . $day . '|' . $start . '|' . $end;
                if (isset($seen[$key])) return;
                $seen[$key] = true;
                TeacherTimeTable::create([
                    'organization_id'   => $org,
                    'assigned_by'       => Auth::id(),
                    'teacher_detail_id' => $teacherId,
                    'standard_id'       => $this->createStandardId,
                    'section_id'        => $this->createSectionId,
                    'subject_id'        => $subjectId,
                    'day_of_week'       => $day,
                    'start_time'        => $start,
                    'end_time'          => $end,
                    'is_active'         => true,
                ]);
                $created++;
            };

            foreach ($rowsToSave as $row) {
                $teacherId = (int) ($row['teacher_id'] ?? 0);
                if (!$teacherId) continue;
                foreach ($row['days'] as $day) {
                    $day = (int) $day;
                    if (!in_array($day, $this->defaultDays, true)) continue;
                    $tryCreate($teacherId, (int) $row['subject_id'], $day, $row['start_time'], $row['end_time']);
                }
            }

            DB::commit();
            $this->notification()->success('Saved!', "{$created} timetable entries " . ($this->isEdit ? 'updated.' : 'created.'));
            $this->closePanel();
            $this->loadStats();
            $this->resetPage();
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error('Timetable save error: ' . $e->getMessage());
            $this->notification()->error('Error!', $e->getMessage());
        }
    }

    // ─── Edit whole section's timetable ──────────────────────────────────
    public function onEditSection(int $standardId, int $sectionId): void
    {
        $this->resetForm();
        $this->createStandardId = (string) $standardId;
        $this->updatedCreateStandardId();
        $this->createSectionId  = (string) $sectionId;
        $this->loadSectionSubjects();
        $this->buildScheduleRowsFromSection();
        $this->prefillRowsFromExisting();

        if (!$this->isEdit) {
            $this->notification()->error('No schedule found for this section.');
            return;
        }
        $this->open = true;
    }

    // ─── Delete whole section's timetable ────────────────────────────────
    public function onDeleteSection(int $standardId, int $sectionId): void
    {
        $this->deleteStandardId  = (string) $standardId;
        $this->deleteSectionId   = (string) $sectionId;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteStandardId  = '';
        $this->deleteSectionId   = '';
    }

    public function confirmDelete(): void
    {
        if (!$this->deleteStandardId || !$this->deleteSectionId) return;
        try {
            $org = Auth::user()->organization_id;
            TeacherTimeTable::where('organization_id', $org)
                ->where('standard_id', $this->deleteStandardId)
                ->where('section_id',  $this->deleteSectionId)
                ->delete();
            $this->notification()->success('Deleted!', 'Section timetable removed.');
            $this->loadStats();
        } catch (\Throwable $e) {
            $this->notification()->error('Error!', 'Failed to delete.');
        }
        $this->cancelDelete();
    }

    // ─── Render ──────────────────────────────────────────────────────────
    public function render()
    {
        $org = Auth::user()->organization_id;

        // CLASS VIEW: requires both class & section. TEACHER VIEW: requires teacher.
        $entries = collect();
        if ($this->viewMode === 'class' && $this->filterClass && $this->filterSection) {
            $entries = TeacherTimeTable::with([
                'teacher.user:id,name',
                'standard:id,name',
                'section:id,name',
                'subject:id,name,code',
            ])
                ->where('organization_id', $org)
                ->where('standard_id', $this->filterClass)
                ->where('section_id',  $this->filterSection)
                ->when(!empty($this->filterDays), fn($q) => $q->whereIn('day_of_week', $this->filterDays))
                ->get();
        } elseif ($this->viewMode === 'teacher' && $this->filterTeacher) {
            $entries = TeacherTimeTable::with([
                'teacher.user:id,name',
                'standard:id,name',
                'section:id,name',
                'subject:id,name,code',
            ])
                ->where('organization_id', $org)
                ->where('teacher_detail_id', $this->filterTeacher)
                ->when(!empty($this->filterDays), fn($q) => $q->whereIn('day_of_week', $this->filterDays))
                ->get();
        }

        // CLASS VIEW: one card containing all subject groups
        // TEACHER VIEW: one card per (class, section) of that teacher with the teacher's subject groups
        $sectionCards = collect();
        if ($entries->isNotEmpty()) {
            $sectionCards = $entries
                ->groupBy(fn($e) => $e->standard_id . '|' . ($e->section_id ?? ''))
                ->map(function ($items) {
                    $first = $items->first();
                    $subjectGroups = $items
                        ->groupBy(fn($e) => $e->subject_id . '|' . $e->start_time . '|' . $e->end_time)
                        ->map(function ($g) {
                            $byTeacher = $g->groupBy('teacher_detail_id')->map(function ($items) {
                                $first = $items->first();
                                return [
                                    'teacher_name' => $first->teacher?->user?->name ?? '—',
                                    'days'         => $items->pluck('day_of_week')->map(fn($d) => (int) $d)->sort()->values()->all(),
                                ];
                            })->sortByDesc(fn($t) => count($t['days']))->values()->all();

                            $first = $g->first();
                            return [
                                'subject'    => $first->subject?->name ?? '—',
                                'start_time' => $first->start_time,
                                'end_time'   => $first->end_time,
                                'teachers'   => $byTeacher,
                                'days'       => $g->pluck('day_of_week')->map(fn($d) => (int) $d)->unique()->sort()->values()->all(),
                            ];
                        })
                        ->sortBy('start_time')
                        ->values();

                    return [
                        'standard_id'    => $first->standard_id,
                        'section_id'     => $first->section_id,
                        'standard'       => $first->standard?->name ?? '—',
                        'section'        => $first->section?->name ?? '—',
                        'subject_groups' => $subjectGroups,
                    ];
                })
                ->sortBy([['standard_id', 'asc'], ['section_id', 'asc']])
                ->values();
        }

        return view('livewire.admin.time-table', [
            'sectionCards' => $sectionCards,
        ]);
    }
}
