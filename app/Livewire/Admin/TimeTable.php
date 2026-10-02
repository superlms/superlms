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
    public array  $scheduleRows     = []; // one row per time slot: start_time, end_time, parts[], sections[], shared

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
            ->inClassOrder()
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

    // ─── The form's rows ─────────────────────────────────────────────────
    // A row is one time slot of the class: its start and end, and who teaches
    // what in it. Usually that is one subject, one teacher, on the days picked
    // ("part" 0). When those days are not the whole week, the days left over can
    // be given to another subject and teacher — a further part, shown as a
    // column to the side — and so on until the week is covered. Each part is
    // saved as one teacher_time_tables entry per day, as it always was.
    //
    // 'sections' are other sections of the same class that take the row along
    // with this one: the same teacher, subject and time (a combined class).
    // 'shared' remembers what the row shared when it was loaded, so a change is
    // carried to those sections and a section taken off loses the period.

    /** Prefills scheduleRows from existing teacher_time_tables entries (auto-switches to edit mode). */
    private function prefillRowsFromExisting(): void
    {
        if (!$this->createStandardId || !$this->createSectionId) return;

        $org  = Auth::user()->organization_id;
        $rows = TeacherTimeTable::where('organization_id', $org)
            ->where('standard_id', $this->createStandardId)
            ->where('section_id',  $this->createSectionId)
            ->get();
        if ($rows->isEmpty()) return;

        $this->isEdit = true;

        // The same class's other sections, to see which take a slot along with this one.
        $siblings = TeacherTimeTable::where('organization_id', $org)
            ->where('standard_id', $this->createStandardId)
            ->where('section_id', '!=', $this->createSectionId)
            ->get()
            ->groupBy(fn($r) => substr($r->start_time, 0, 5) . '|' . substr($r->end_time, 0, 5));

        // One form row per time slot; within it a part per (subject · teacher), each
        // with its weekdays.
        $this->scheduleRows = [];
        $slots = $rows
            ->groupBy(fn($r) => substr($r->start_time, 0, 5) . '|' . substr($r->end_time, 0, 5))
            ->sortKeys();

        foreach ($slots as $slotKey => $group) {
            $first = $group->first();

            $parts = $group
                ->groupBy(fn($r) => $r->subject_id . '|' . $r->teacher_detail_id)
                ->map(fn($g) => [
                    'subject_id' => (int) $g->first()->subject_id,
                    'teacher_id' => (int) $g->first()->teacher_detail_id,
                    'days'       => $g->pluck('day_of_week')->map(fn($d) => (int) $d)->unique()->sort()->values()->all(),
                ])
                ->sortBy(fn($p) => $p['days'][0] ?? 9)
                ->values()
                ->all();

            $entries = $group->map(fn($r) => [(int) $r->teacher_detail_id, (int) $r->subject_id, (int) $r->day_of_week])->values()->all();

            // A sibling section shares the slot when it has every one of these entries.
            $shared = [];
            foreach (($siblings->get($slotKey) ?? collect())->groupBy('section_id') as $sectionId => $theirs) {
                $has = $theirs->map(fn($r) => $r->teacher_detail_id . '|' . $r->subject_id . '|' . $r->day_of_week)->flip();
                if (collect($entries)->every(fn($e) => $has->has(implode('|', $e)))) {
                    $shared[] = (int) $sectionId;
                }
            }

            $this->scheduleRows[] = [
                'start_time' => substr($first->start_time, 0, 5),
                'end_time'   => substr($first->end_time, 0, 5),
                'parts'      => $parts,
                'sections'   => $shared,
                'shared'     => [
                    'sections' => $shared,
                    'start'    => substr($first->start_time, 0, 5),
                    'end'      => substr($first->end_time, 0, 5),
                    'entries'  => $entries,
                ],
            ];
        }

        $this->syncParts();
    }

    /** One part of a row: a subject and a teacher on some weekdays. */
    private function blankPart(?int $subjectId = null): array
    {
        return [
            'subject_id' => (int) ($subjectId ?? ($this->sectionSubjects[0]['id'] ?? 0)),
            'teacher_id' => '',
            'days'       => [],
        ];
    }

    /** A blank schedule row with sensible defaults. */
    private function blankRow(?int $subjectId = null): array
    {
        return [
            'start_time' => '09:00',
            'end_time'   => '10:00',
            'parts'      => [$this->blankPart($subjectId)],
            'sections'   => [],
            'shared'     => ['sections' => [], 'start' => '', 'end' => '', 'entries' => []],
        ];
    }

    /** Pre-populates one row per subject mapped to the chosen section. */
    private function buildScheduleRowsFromSection(): void
    {
        $this->scheduleRows = [];
        foreach ($this->sectionSubjects as $s) {
            $this->scheduleRows[] = $this->blankRow((int) $s['id']);
        }
    }

    /** A subject's name, for messages. */
    private function subjectName($id): string
    {
        return collect($this->sectionSubjects)->firstWhere('id', (int) $id)['name'] ?? 'Subject';
    }

    /** Is this a time of day, 24-hour, as HH:MM? */
    private function isTime($value): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $value);
    }

    /**
     * What was typed in a time box, as 24-hour HH:MM where it can be read as
     * one: "9" → 09:00, "930" → 09:30, "9.5" → 09:05, "1330" → 13:30. Anything
     * else is left as typed, and the row says the time is not valid.
     */
    private function normaliseTime($value): string
    {
        $value = trim((string) $value);
        if ($value === '') return '';

        if (preg_match('/^(\d{1,2})\D+(\d{1,2})$/', $value, $m)) {
            [$h, $min] = [(int) $m[1], (int) $m[2]];
        } else {
            $digits = preg_replace('/\D/', '', $value);
            if ($digits === '' || strlen($digits) > 4) return $value;
            if (strlen($digits) <= 2) {
                [$h, $min] = [(int) $digits, 0];
            } elseif (strlen($digits) === 3) {
                [$h, $min] = [(int) substr($digits, 0, 1), (int) substr($digits, 1)];
            } else {
                [$h, $min] = [(int) substr($digits, 0, 2), (int) substr($digits, 2)];
            }
        }

        return ($h > 23 || $min > 59) ? $value : sprintf('%02d:%02d', $h, $min);
    }

    /** A time typed is put into HH:MM, and the side columns are offered or withdrawn. */
    public function updatedScheduleRows($value, $key): void
    {
        $bits = explode('.', (string) $key);
        $idx  = (int) $bits[0];

        if (isset($this->scheduleRows[$idx]) && in_array(end($bits), ['start_time', 'end_time'], true)) {
            $this->scheduleRows[$idx][end($bits)] = $this->normaliseTime($value);
        }

        $this->syncParts();
    }

    /**
     * Keeps each row's parts as the form shows them: the first always; a later
     * one only while it has days; and, when the days picked so far leave some of
     * the week free, one more to the side to give them to. A side column whose
     * subject or teacher is already chosen, but no day yet, is kept as it is.
     */
    private function syncParts(): void
    {
        foreach ($this->scheduleRows as $i => $row) {
            $parts = array_values($row['parts'] ?? []);
            if (empty($parts)) {
                $parts = [$this->blankPart()];
            }
            foreach ($parts as $k => $p) {
                $parts[$k]['days'] = array_values(array_unique(array_map('intval', $p['days'] ?? [])));
            }

            $first = array_shift($parts);
            $kept  = [$first];
            $tail  = count($parts) ? $parts[count($parts) - 1] : null;
            foreach ($parts as $p) {
                if (!empty($p['days'])) $kept[] = $p;
            }

            $taken = array_merge(...array_map(fn($p) => $p['days'], $kept));
            $free  = array_diff($this->slotDays($i), $taken);
            if (!empty($kept[count($kept) - 1]['days']) && !empty($free)) {
                $kept[] = ($tail && empty($tail['days'])) ? $tail : $this->blankPart();
            }

            $this->scheduleRows[$i]['parts']    = $kept;
            $this->scheduleRows[$i]['sections'] = array_values(array_unique(array_map('intval', $row['sections'] ?? [])));
        }
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
        $this->syncParts();
    }

    /** Lesson length for a row, e.g. "1h 30m" — shown next to the time inputs. */
    public function rowDuration(int $rowIndex): string
    {
        $row = $this->scheduleRows[$rowIndex] ?? null;
        if (!$row || !$this->isTime($row['start_time'] ?? '') || !$this->isTime($row['end_time'] ?? '')) return '';
        try {
            $s = \Carbon\Carbon::createFromFormat('H:i', $row['start_time']);
            $e = \Carbon\Carbon::createFromFormat('H:i', $row['end_time']);
            if ($e->lessThanOrEqualTo($s)) return '';
            $mins = $s->diffInMinutes($e);
            $h = intdiv($mins, 60);
            $m = $mins % 60;
            return trim(($h ? "{$h}h " : '') . ($m ? "{$m}m" : ($h ? '' : '0m')));
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Every weekday a row's parts have picked. */
    private function rowDays(array $row): array
    {
        $days = [];
        foreach (($row['parts'] ?? []) as $p) {
            foreach (($p['days'] ?? []) as $d) $days[(int) $d] = true;
        }
        return array_keys($days);
    }

    // ─── Day availability ────────────────────────────────────────────────
    /**
     * Days already taken by ANOTHER row that overlaps this row's time slot.
     * A class can only be in one place at a time, so if 09:00–10:00 is filled by
     * Hindi on Mon/Tue/Thu, those weekdays are gone for any other 09:00–10:00 row.
     */
    public function occupiedDaysForRow(int $rowIndex): array
    {
        $row = $this->scheduleRows[$rowIndex] ?? null;
        if (!$row) return [];

        $start = $row['start_time'] ?? '';
        $end   = $row['end_time'] ?? '';
        if (!$this->isTime($start) || !$this->isTime($end) || $start >= $end) return [];

        $occupied = [];
        foreach ($this->scheduleRows as $j => $other) {
            if ($j === $rowIndex) continue;
            $os = $other['start_time'] ?? '';
            $oe = $other['end_time'] ?? '';
            if (!$this->isTime($os) || !$this->isTime($oe) || $os >= $oe) continue;
            if ($os >= $end || $oe <= $start) continue; // no time overlap
            foreach ($this->rowDays($other) as $d) {
                $occupied[(int) $d] = true;
            }
        }

        return array_keys($occupied);
    }

    /** The weekdays a row's slot has to give out: Mon–Sat minus what overlapping rows hold. */
    private function slotDays(int $rowIndex): array
    {
        $own = $this->rowDays($this->scheduleRows[$rowIndex] ?? []);
        return array_values(array_unique(array_merge(array_diff($this->defaultDays, $this->occupiedDaysForRow($rowIndex)), $own)));
    }

    /**
     * Weekdays one part of a row may still pick: the slot's days minus those the
     * row's other parts have. Days already selected in THIS part always stay.
     */
    public function availableDaysForPart(int $rowIndex, int $partIndex): array
    {
        $row = $this->scheduleRows[$rowIndex] ?? null;
        if (!$row) return [];

        $others = [];
        foreach (($row['parts'] ?? []) as $k => $p) {
            if ($k === $partIndex) continue;
            foreach (($p['days'] ?? []) as $d) $others[] = (int) $d;
        }

        $avail = array_diff($this->slotDays($rowIndex), $others);
        sort($avail);
        return array_values($avail);
    }

    /** As it was asked before a row had parts: the first part's days. */
    public function availableDaysForRow(int $rowIndex): array
    {
        return $this->availableDaysForPart($rowIndex, 0);
    }

    /** The class's other sections a row may be shared with. */
    public function otherSections(): array
    {
        return array_values(array_filter($this->createSections, fn($s) => (string) $s['id'] !== (string) $this->createSectionId));
    }

    // ─── Conflict checks ─────────────────────────────────────────────────
    /**
     * Availability check for one part: is its teacher already busy — in another
     * class/section (saved), or elsewhere in this same form — at an overlapping
     * time on any of the part's days? Returns a short reason or null. A section
     * the row is shared with is not "another class": the teacher takes them
     * together.
     */
    public function getPartConflict(int $rowIndex, int $partIndex): ?string
    {
        $row  = $this->scheduleRows[$rowIndex] ?? null;
        $part = $row['parts'][$partIndex] ?? null;
        if (!$part) return null;

        $teacherId = (int) ($part['teacher_id'] ?? 0);
        if (!$teacherId) return null;

        $start = $row['start_time'] ?? '';
        $end   = $row['end_time'] ?? '';
        if (!$this->isTime($start) || !$this->isTime($end) || $start >= $end) return null;

        $days = array_map('intval', $part['days'] ?? []);
        if (empty($days)) return null;

        // 1) Teacher already booked elsewhere at this time. The current section is
        //    excluded — it gets wiped & recreated on save — and so are the sections
        //    this row is shared with.
        $together = array_merge([(int) $this->createSectionId], array_map('intval', $row['sections'] ?? []), array_map('intval', $row['shared']['sections'] ?? []));
        $clash = TeacherTimeTable::with(['standard:id,name', 'section:id,name'])
            ->where('teacher_detail_id', $teacherId)
            ->whereIn('day_of_week', $days)
            ->where('start_time', '<', $end)
            ->where('end_time',   '>', $start)
            ->get()
            ->first(fn($e) => !((string) $e->standard_id === (string) $this->createStandardId && in_array((int) $e->section_id, $together, true)));

        if ($clash) {
            $where = trim(($clash->standard?->name ?? '') . ' ' . ($clash->section?->name ?? ''));
            $dn    = $this->daysOfWeek[(int) $clash->day_of_week] ?? $clash->day_of_week;
            return 'Busy with ' . ($where !== '' ? $where : 'another class') . " ({$dn})";
        }

        // 2) Same teacher in another row of this form at an overlapping time & day.
        foreach ($this->scheduleRows as $j => $other) {
            if ($j === $rowIndex) continue;
            $os = $other['start_time'] ?? '';
            $oe = $other['end_time'] ?? '';
            if (!$this->isTime($os) || !$this->isTime($oe)) continue;
            if ($os >= $end || $oe <= $start) continue;
            foreach (($other['parts'] ?? []) as $op) {
                if ((int) ($op['teacher_id'] ?? 0) !== $teacherId) continue;
                $shared = array_intersect($days, array_map('intval', $op['days'] ?? []));
                if (!empty($shared)) {
                    $dn = $this->daysOfWeek[(int) reset($shared)] ?? reset($shared);
                    return "Teacher already on another subject at this time ({$dn})";
                }
            }
        }

        return null;
    }

    /** As it was asked before a row had parts: the first conflict among its parts. */
    public function getRowConflict(int $rowIndex): ?string
    {
        foreach (array_keys($this->scheduleRows[$rowIndex]['parts'] ?? []) as $p) {
            if ($conflict = $this->getPartConflict($rowIndex, (int) $p)) return $conflict;
        }
        return null;
    }

    /** The parts of a row that are complete: a subject, a teacher and at least one day. */
    private function completeParts(array $row): array
    {
        return array_values(array_filter($row['parts'] ?? [], fn($p) => (int) ($p['subject_id'] ?? 0) > 0
            && (int) ($p['teacher_id'] ?? 0) > 0
            && !empty($p['days'] ?? [])));
    }

    /**
     * A row shared with other sections: does one of them already have something
     * else at that time? What the row itself put there before does not count —
     * it is replaced on save. Returns a short reason or null.
     */
    public function getShareConflict(int $rowIndex): ?string
    {
        $row = $this->scheduleRows[$rowIndex] ?? null;
        $sections = array_values(array_intersect(array_map('intval', $row['sections'] ?? []), array_map(fn($s) => (int) $s['id'], $this->otherSections())));
        if (!$row || empty($sections)) return null;

        $start = $row['start_time'] ?? '';
        $end   = $row['end_time'] ?? '';
        if (!$this->isTime($start) || !$this->isTime($end) || $start >= $end) return null;

        $parts = $this->completeParts($row);
        $days  = array_values(array_unique(array_merge([], ...array_map(fn($p) => array_map('intval', $p['days']), $parts))));
        if (empty($days)) return null;

        $mine = [];
        foreach ($parts as $p) {
            foreach ($p['days'] as $d) $mine[(int) $p['teacher_id'] . '|' . (int) $p['subject_id'] . '|' . (int) $d] = true;
        }
        $was = [];
        if (($row['shared']['start'] ?? '') !== '') {
            foreach (($row['shared']['entries'] ?? []) as $e) {
                $was[implode('|', $e) . '|' . $row['shared']['start'] . '|' . $row['shared']['end']] = true;
            }
        }

        $theirs = TeacherTimeTable::with(['section:id,name', 'subject:id,name'])
            ->where('organization_id', Auth::user()->organization_id)
            ->where('standard_id', $this->createStandardId)
            ->whereIn('section_id', $sections)
            ->whereIn('day_of_week', $days)
            ->where('start_time', '<', $end)
            ->where('end_time',   '>', $start)
            ->get();

        foreach ($theirs as $e) {
            $key   = (int) $e->teacher_detail_id . '|' . (int) $e->subject_id . '|' . (int) $e->day_of_week;
            $times = substr($e->start_time, 0, 5) . '|' . substr($e->end_time, 0, 5);
            if (isset($mine[$key]) && $times === $start . '|' . $end) continue;   // already the same period
            if (isset($was[$key . '|' . $times])) continue;                       // this row's own, about to be replaced
            $dn = $this->daysOfWeek[(int) $e->day_of_week] ?? $e->day_of_week;
            return ($e->section?->name ?? 'Another section') . ' already has ' . ($e->subject?->name ?? 'a period') . " at this time ({$dn})";
        }

        return null;
    }

    // ─── Save (create or edit) ───────────────────────────────────────────
    public function onSaveTimetable(): void
    {
        if (!$this->createStandardId) { $this->notification()->error('Please select a class.'); return; }
        if (!$this->createSectionId)  { $this->notification()->error('Please select a section.'); return; }

        foreach (array_keys($this->scheduleRows) as $idx) {
            $this->scheduleRows[$idx]['start_time'] = $this->normaliseTime($this->scheduleRows[$idx]['start_time'] ?? '');
            $this->scheduleRows[$idx]['end_time']   = $this->normaliseTime($this->scheduleRows[$idx]['end_time'] ?? '');
        }
        $this->syncParts();

        // Keep only rows that have at least one complete part: a subject, a teacher
        // and at least one day.
        $rowsToSave = collect($this->scheduleRows)
            ->map(function ($row, $idx) {
                $row['__idx']   = $idx;
                $row['__parts'] = $this->completeParts($row);
                return $row;
            })
            ->filter(fn($r) => !empty($r['__parts']))
            ->values()
            ->all();

        if (empty($rowsToSave) && !$this->isEdit) {
            $this->notification()->error('Add at least one row with a subject, teacher and days to save.');
            return;
        }

        $siblingIds = array_map(fn($s) => (int) $s['id'], $this->otherSections());

        foreach ($rowsToSave as $row) {
            $n = $this->subjectName($row['__parts'][0]['subject_id']);
            if (!$this->isTime($row['start_time']) || !$this->isTime($row['end_time']) || $row['start_time'] >= $row['end_time']) {
                $this->notification()->error("{$n}: invalid time range."); return;
            }
            // Class double-booking guard (defensive — the Days dropdown already hides taken days).
            $occupiedClash = array_intersect($this->rowDays(['parts' => $row['__parts']]), $this->occupiedDaysForRow((int) $row['__idx']));
            if (!empty($occupiedClash)) {
                $dn = $this->daysOfWeekFull[(int) reset($occupiedClash)] ?? reset($occupiedClash);
                $this->notification()->error("{$n} ({$dn}): the class is already scheduled at this time."); return;
            }
            foreach (array_keys($this->scheduleRows[(int) $row['__idx']]['parts']) as $p) {
                if ($conflict = $this->getPartConflict((int) $row['__idx'], (int) $p)) {
                    $this->notification()->error($this->subjectName($this->scheduleRows[(int) $row['__idx']]['parts'][$p]['subject_id']) . ": {$conflict}"); return;
                }
            }
            if ($conflict = $this->getShareConflict((int) $row['__idx'])) {
                $this->notification()->error("{$n}: {$conflict}"); return;
            }
        }

        // The class's timetable as it was, so each teacher hears what changed —
        // and the same for every section a row is, or was, shared with.
        $org  = (int) Auth::user()->organization_id;
        $push = app(\App\Services\TeacherPushNotifier::class);
        $before = $push->timetableSnapshot($org, (int) $this->createStandardId, (int) $this->createSectionId);

        $touched = [];
        foreach ($this->scheduleRows as $row) {
            foreach (array_merge($row['sections'] ?? [], $row['shared']['sections'] ?? []) as $sid) {
                if (in_array((int) $sid, $siblingIds, true)) $touched[(int) $sid] = true;
            }
        }
        $beforeOthers = [];
        foreach (array_keys($touched) as $sid) {
            $beforeOthers[] = $push->timetableSnapshot($org, (int) $this->createStandardId, (int) $sid);
        }

        try {
            DB::beginTransaction();

            // Edit mode → wipe all existing entries for this (class, section) and recreate
            if ($this->isEdit) {
                TeacherTimeTable::where('organization_id', $org)
                    ->where('standard_id', $this->createStandardId)
                    ->where('section_id', $this->createSectionId)
                    ->delete();
            }

            // What each row had put in the sections it was shared with comes out
            // first; what it shares now goes back in below.
            foreach ($this->scheduleRows as $row) {
                $was = $row['shared'] ?? [];
                $wasSections = array_values(array_intersect(array_map('intval', $was['sections'] ?? []), $siblingIds));
                if (empty($wasSections) || ($was['start'] ?? '') === '') continue;
                foreach (($was['entries'] ?? []) as [$teacherId, $subjectId, $day]) {
                    TeacherTimeTable::where('organization_id', $org)
                        ->where('standard_id', $this->createStandardId)
                        ->whereIn('section_id', $wasSections)
                        ->where('teacher_detail_id', $teacherId)
                        ->where('subject_id', $subjectId)
                        ->where('day_of_week', $day)
                        ->where('start_time', 'like', $was['start'] . '%')
                        ->where('end_time', 'like', $was['end'] . '%')
                        ->delete();
                }
            }

            $created = 0;
            // Guard against duplicate (section, subject, day, start, end) inserts.
            $seen = [];

            $tryCreate = function (int $sectionId, int $teacherId, int $subjectId, int $day, string $start, string $end) use ($org, &$seen, &$created) {
                $key = $sectionId . '|' . $subjectId . '|' . $day . '|' . $start . '|' . $end;
                if (isset($seen[$key])) return;
                $seen[$key] = true;
                TeacherTimeTable::create([
                    'organization_id'   => $org,
                    'assigned_by'       => Auth::id(),
                    'teacher_detail_id' => $teacherId,
                    'standard_id'       => $this->createStandardId,
                    'section_id'        => $sectionId,
                    'subject_id'        => $subjectId,
                    'day_of_week'       => $day,
                    'start_time'        => $start,
                    'end_time'          => $end,
                    'is_active'         => true,
                ]);
                $created++;
            };

            foreach ($rowsToSave as $row) {
                $also = array_values(array_intersect(array_map('intval', $row['sections'] ?? []), $siblingIds));
                foreach ($row['__parts'] as $part) {
                    foreach ($part['days'] as $day) {
                        $day = (int) $day;
                        if (!in_array($day, $this->defaultDays, true)) continue;
                        $tryCreate((int) $this->createSectionId, (int) $part['teacher_id'], (int) $part['subject_id'], $day, $row['start_time'], $row['end_time']);

                        // The same period in each section the row is shared with —
                        // unless that section has it already.
                        foreach ($also as $sid) {
                            $there = TeacherTimeTable::where('organization_id', $org)
                                ->where('standard_id', $this->createStandardId)
                                ->where('section_id', $sid)
                                ->where('teacher_detail_id', (int) $part['teacher_id'])
                                ->where('subject_id', (int) $part['subject_id'])
                                ->where('day_of_week', $day)
                                ->where('start_time', 'like', $row['start_time'] . '%')
                                ->where('end_time', 'like', $row['end_time'] . '%')
                                ->exists();
                            if (!$there) {
                                $tryCreate($sid, (int) $part['teacher_id'], (int) $part['subject_id'], $day, $row['start_time'], $row['end_time']);
                            }
                        }
                    }
                }
            }

            DB::commit();
            $push->timetableSaved($before);
            foreach ($beforeOthers as $snapshot) {
                $push->timetableSaved($snapshot);
            }
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
            $push = app(\App\Services\TeacherPushNotifier::class);
            $before = $push->timetableSnapshot((int) $org, (int) $this->deleteStandardId, (int) $this->deleteSectionId);
            TeacherTimeTable::where('organization_id', $org)
                ->where('standard_id', $this->deleteStandardId)
                ->where('section_id',  $this->deleteSectionId)
                ->delete();
            $push->timetableSaved($before);
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
