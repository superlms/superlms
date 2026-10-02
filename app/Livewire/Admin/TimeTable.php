<?php

namespace App\Livewire\Admin;

use App\Models\Admin\TeacherTimeTable;
use App\Models\Admin\TimetablePeriod;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use App\Services\TeacherPushNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class TimeTable extends Component
{
    use WireUiActions, WithPagination;

    /** The most periods a school's day can be given on the Periods tab. */
    public const MAX_PERIODS = 15;

    // ─── Tabs / view mode ────────────────────────────────────────────────
    public string $viewMode = 'class'; // 'class' | 'teacher' | 'periods'

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
    public array  $scheduleRows     = []; // one row per period: period, start_time, end_time, parts[], shared

    // ─── Periods panel state (the school's day) ──────────────────────────
    public bool   $showPeriodPanel = false;
    public string $periodCount     = '';  // how many periods there are
    public array  $periodRows      = [];  // [['start' => '09:00', 'end' => '09:45'], ...] in order
    public string $lunchStart      = '';
    public string $lunchEnd        = '';

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

    /** The school's periods and lunch break, read once a request. */
    private ?array $periodsRead = null;
    private ?array $lunchRead   = null;
    private bool   $lunchKnown  = false;
    private string $periodEndWas = '';

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
        $this->viewMode = in_array($mode, ['class', 'teacher', 'periods'], true) ? $mode : 'class';
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

    // ─── Times ───────────────────────────────────────────────────────────
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

    /** HH:MM as the HH:MM:SS a time column holds, so times compare alike wherever they are stored. */
    private function hms(string $time): string
    {
        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    /** How long it is from one time to another, e.g. "45m" or "1h 30m"; '' when they are not a span. */
    public function span($start, $end): string
    {
        if (!$this->isTime($start) || !$this->isTime($end) || $start >= $end) return '';
        $mins = (((int) substr($end, 0, 2)) * 60 + (int) substr($end, 3)) - (((int) substr($start, 0, 2)) * 60 + (int) substr($start, 3));
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return trim(($h ? "{$h}h " : '') . ($m ? "{$m}m" : ''));
    }

    // ─── Periods: the school's day ───────────────────────────────────────
    // How many periods there are, when each starts and ends, and when the
    // lunch break is. A class's timetable is filled in period by period; the
    // time of a period is taken from here.

    /** The school's periods in order: [['no' => 1, 'start' => '09:00', 'end' => '09:45'], …]. */
    public function periods(): array
    {
        return $this->periodsRead ??= TimetablePeriod::periodsOf((int) Auth::user()->organization_id);
    }

    /** The school's lunch break: ['start' => '12:00', 'end' => '12:30'], or null. */
    public function lunch(): ?array
    {
        if (!$this->lunchKnown) {
            $this->lunchRead  = TimetablePeriod::lunchOf((int) Auth::user()->organization_id);
            $this->lunchKnown = true;
        }
        return $this->lunchRead;
    }

    /** The Periods panel, opened on what is saved — to add the periods, or to change them. */
    public function openPeriodPanel(): void
    {
        $periods = $this->periods();
        $lunch   = $this->lunch();

        $this->periodRows  = array_map(fn ($p) => ['start' => $p['start'], 'end' => $p['end']], $periods);
        $this->periodCount = $periods ? (string) count($periods) : '';
        $this->lunchStart  = $lunch['start'] ?? '';
        $this->lunchEnd    = $lunch['end'] ?? '';
        $this->showPeriodPanel = true;
    }

    public function closePeriodPanel(): void
    {
        $this->showPeriodPanel = false;
        $this->reset(['periodCount', 'periodRows', 'lunchStart', 'lunchEnd']);
    }

    /** From the timetable form, when the school has no periods yet: over to the Periods tab, its panel open. */
    public function goAddPeriods(): void
    {
        $this->closePanel();
        $this->setViewMode('periods');
        $this->openPeriodPanel();
    }

    /** The number of periods typed: that many rows, what was typed in them kept. */
    public function updatedPeriodCount($value): void
    {
        $n = max(0, min(self::MAX_PERIODS, (int) preg_replace('/\D/', '', (string) $value)));
        $this->periodCount = $n ? (string) $n : '';

        $rows = array_slice(array_values($this->periodRows), 0, $n);
        while (count($rows) < $n) {
            $rows[] = ['start' => '', 'end' => ''];
        }
        $this->periodRows = $rows;
        $this->chainPeriodStarts();
    }

    /** The end a period had before a new one is typed over it (see below). */
    public function updatingPeriodRows($value, $key): void
    {
        [$idx, $field] = array_pad(explode('.', (string) $key), 2, '');
        $this->periodEndWas = $field === 'end' ? (string) ($this->periodRows[(int) $idx]['end'] ?? '') : '';
    }

    /**
     * A time typed is put into HH:MM. When a period's end is corrected, the
     * next period — if it started right at the old end — starts at the new one.
     */
    public function updatedPeriodRows($value, $key): void
    {
        [$idx, $field] = array_pad(explode('.', (string) $key), 2, '');
        $idx = (int) $idx;

        if (isset($this->periodRows[$idx]) && in_array($field, ['start', 'end'], true)) {
            $time = $this->normaliseTime($value);
            $this->periodRows[$idx][$field] = $time;

            if ($field === 'end' && $this->periodEndWas !== '' && $this->isTime($time)
                && ($this->periodRows[$idx + 1]['start'] ?? null) === $this->periodEndWas) {
                $this->periodRows[$idx + 1]['start'] = '';
            }
        }
        $this->chainPeriodStarts();
    }

    public function updatedLunchStart($value): void
    {
        $this->lunchStart = $this->normaliseTime($value);
    }

    public function updatedLunchEnd($value): void
    {
        $this->lunchEnd = $this->normaliseTime($value);
        $this->chainPeriodStarts();
    }

    /**
     * A period whose start is still empty starts where the one before it ends —
     * or, when that is where the lunch break starts, where the lunch break ends.
     * What has been typed is never changed.
     */
    private function chainPeriodStarts(): void
    {
        $lunch = $this->isTime($this->lunchStart) && $this->isTime($this->lunchEnd) && $this->lunchStart < $this->lunchEnd;

        foreach (array_keys($this->periodRows) as $k) {
            if ($k === 0 || ($this->periodRows[$k]['start'] ?? '') !== '') continue;
            $before = $this->periodRows[$k - 1]['end'] ?? '';
            if (!$this->isTime($before)) continue;
            $this->periodRows[$k]['start'] = ($lunch && $before === $this->lunchStart) ? $this->lunchEnd : $before;
        }
    }

    /**
     * What stands in the way of the periods as typed, by row (0, 1, …) and
     * 'lunch'. A row not filled in yet is only an error once saving ($strict).
     */
    public function periodErrors(bool $strict = false): array
    {
        $errors = [];
        $lastEnd = '';
        $lastNo  = 0;

        foreach (array_values($this->periodRows) as $k => $row) {
            $start = (string) ($row['start'] ?? '');
            $end   = (string) ($row['end'] ?? '');

            if ($start === '' || $end === '') {
                if ($strict) $errors[$k] = 'Enter its start and end time.';
                continue;
            }
            if (!$this->isTime($start) || !$this->isTime($end)) {
                $errors[$k] = 'Not a time. Type it as 24-hour, e.g. 09:00 or 13:30.';
                continue;
            }
            if ($start >= $end) {
                $errors[$k] = 'It has to end after it starts.';
            } elseif ($lastEnd !== '' && $start < $lastEnd) {
                $errors[$k] = "It starts before period {$lastNo} ends.";
            }
            $lastEnd = $end;
            $lastNo  = $k + 1;
        }

        if ($this->lunchStart !== '' || $this->lunchEnd !== '') {
            if (!$this->isTime($this->lunchStart) || !$this->isTime($this->lunchEnd)) {
                $errors['lunch'] = 'Enter when it starts and ends, as 24-hour, e.g. 12:00 and 12:30.';
            } elseif ($this->lunchStart >= $this->lunchEnd) {
                $errors['lunch'] = 'It has to end after it starts.';
            } else {
                foreach (array_values($this->periodRows) as $k => $row) {
                    if ($this->isTime($row['start'] ?? '') && $this->isTime($row['end'] ?? '')
                        && $row['start'] < $this->lunchEnd && $row['end'] > $this->lunchStart) {
                        $errors['lunch'] = 'It runs into period ' . ($k + 1) . '.';
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Saves the school's day. A period whose time was changed takes its classes
     * along: every timetable entry at the old time moves to the new one, so the
     * timetables already made stay on their periods.
     */
    public function savePeriods(): void
    {
        $this->periodRows = array_values($this->periodRows);
        foreach (array_keys($this->periodRows) as $k) {
            $this->periodRows[$k]['start'] = $this->normaliseTime($this->periodRows[$k]['start'] ?? '');
            $this->periodRows[$k]['end']   = $this->normaliseTime($this->periodRows[$k]['end'] ?? '');
        }
        $this->lunchStart = $this->normaliseTime($this->lunchStart);
        $this->lunchEnd   = $this->normaliseTime($this->lunchEnd);

        if (empty($this->periodRows)) {
            $this->notification()->error('Enter how many periods there are.');
            return;
        }
        if ($errors = $this->periodErrors(true)) {
            $k = array_key_first($errors);
            $this->notification()->error(($k === 'lunch' ? 'Lunch break' : 'Period ' . ($k + 1)) . ': ' . $errors[$k]);
            return;
        }

        $org  = (int) Auth::user()->organization_id;
        $push = app(TeacherPushNotifier::class);

        // The entries of each period whose time is changing — found before
        // anything moves, so two periods swapping or shifting never mix.
        $moves = [];
        foreach (TimetablePeriod::periodsOf($org) as $k => $was) {
            $now = $this->periodRows[$k] ?? null;
            if (!$now || ($was['start'] === $now['start'] && $was['end'] === $now['end'])) continue;
            $ids = TeacherTimeTable::where('organization_id', $org)
                ->where('start_time', 'like', $was['start'] . '%')
                ->where('end_time', 'like', $was['end'] . '%')
                ->pluck('id')
                ->all();
            if ($ids) $moves[] = [$ids, $now['start'], $now['end']];
        }

        // Each class whose timetable moves, as it was — so its teachers hear of it.
        $before = [];
        if ($moves) {
            $classes = TeacherTimeTable::whereIn('id', array_merge(...array_column($moves, 0)))
                ->select('standard_id', 'section_id')
                ->distinct()
                ->get();
            foreach ($classes as $c) {
                $before[] = $push->timetableSnapshot($org, (int) $c->standard_id, (int) $c->section_id);
            }
        }

        try {
            DB::beginTransaction();

            TimetablePeriod::where('organization_id', $org)->delete();
            foreach ($this->periodRows as $k => $row) {
                TimetablePeriod::create([
                    'organization_id' => $org,
                    'type'            => TimetablePeriod::PERIOD,
                    'period_no'       => $k + 1,
                    'start_time'      => $this->hms($row['start']),
                    'end_time'        => $this->hms($row['end']),
                ]);
            }
            if ($this->lunchStart !== '') {
                TimetablePeriod::create([
                    'organization_id' => $org,
                    'type'            => TimetablePeriod::LUNCH,
                    'period_no'       => null,
                    'start_time'      => $this->hms($this->lunchStart),
                    'end_time'        => $this->hms($this->lunchEnd),
                ]);
            }

            foreach ($moves as [$ids, $start, $end]) {
                TeacherTimeTable::whereIn('id', $ids)->update([
                    'start_time' => $this->hms($start),
                    'end_time'   => $this->hms($end),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error('Timetable periods save error: ' . $e->getMessage());
            $this->notification()->error('Error!', $e->getMessage());
            return;
        }

        foreach ($before as $snapshot) {
            $push->timetableSaved($snapshot);
        }

        $this->periodsRead = null;
        $this->lunchKnown  = false;
        $this->notification()->success('Saved!', count($this->periodRows) . ' period' . (count($this->periodRows) === 1 ? '' : 's') . ' saved.');
        $this->closePeriodPanel();
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
        $this->buildScheduleRows();
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
    // A row is one period of the school's day (Timetable → Periods): the form
    // shows its serial only; its time comes from the period. In it, who teaches
    // what: usually one teacher, one subject, the whole week ("part" 0). When
    // the days picked are not the whole week, the days left over get a line of
    // their own under it — another teacher, another subject — and so on until
    // the week is covered. Each part is saved as one teacher_time_tables entry
    // per day, as it always was.
    //
    // A part's 'sections' are other sections of the same class that take it
    // along with this one: the same teacher, subject and time (a combined
    // class). The row's 'shared' remembers what it had put into those sections
    // when it was loaded, so a change is carried to them and a section taken
    // off loses the period.
    //
    // A class that has entries at a time that is not one of the periods (a
    // timetable made before the periods were set, or from the app) keeps them:
    // each such time is a row of its own after the periods, 'period' null.

    /** One part of a row: a teacher and a subject on some weekdays, in this section and perhaps others. */
    private function blankPart(array $days = []): array
    {
        return [
            'teacher_id' => '',
            'subject_id' => '',
            'sections'   => [],
            'days'       => array_values($days),
            'picked'     => false, // its days are whatever is left over, until they are picked by hand
        ];
    }

    /** A row with nothing chosen in it yet. */
    private function isBlankRow(array $row): bool
    {
        $parts = $row['parts'] ?? [];
        return count($parts) === 1 && !(int) ($parts[0]['teacher_id'] ?? 0) && !(int) ($parts[0]['subject_id'] ?? 0);
    }

    /**
     * The form's rows for the chosen class and section: one for each of the
     * school's periods, filled with what the section has at that period's time;
     * then one for each other time it has a class at. A section that has
     * entries already is being edited.
     */
    private function buildScheduleRows(): void
    {
        $this->scheduleRows = [];
        if (!$this->createStandardId || !$this->createSectionId) return;

        $slot = fn ($r) => substr((string) $r->start_time, 0, 5) . '|' . substr((string) $r->end_time, 0, 5);

        $all = TeacherTimeTable::where('organization_id', Auth::user()->organization_id)
            ->where('standard_id', $this->createStandardId)
            ->get();
        [$mine, $theirs] = $all->partition(fn ($r) => (string) $r->section_id === (string) $this->createSectionId);
        $mine   = $mine->groupBy($slot);
        $theirs = $theirs->groupBy($slot);

        if ($mine->isNotEmpty()) {
            $this->isEdit = true;
        }

        foreach ($this->periods() as $p) {
            $key = $p['start'] . '|' . $p['end'];
            $this->scheduleRows[] = $this->rowFor($p['no'], $p['start'], $p['end'], $mine->pull($key), $theirs->get($key));
        }
        foreach ($mine->sortKeys() as $key => $group) {
            [$start, $end] = explode('|', $key);
            $this->scheduleRows[] = $this->rowFor(null, $start, $end, $group, $theirs->get($key));
        }

        // A period with nothing in it yet opens on the whole week — less any
        // day another row, at a time that runs into it, already holds.
        foreach ($this->scheduleRows as $i => $row) {
            if ($this->isBlankRow($row)) {
                $this->scheduleRows[$i]['parts'][0]['days'] = array_values(array_diff($this->defaultDays, $this->occupiedDaysForRow($i)));
            }
        }

        $this->syncParts();
    }

    /** One row: a period (or a time that is not one), with the section's entries at it as parts. */
    private function rowFor(?int $period, string $start, string $end, $mine, $theirs): array
    {
        $parts   = [];
        $entries = [];

        if ($mine && $mine->isNotEmpty()) {
            // What each other section of the class has at this time.
            $has = [];
            foreach (($theirs ?? []) as $r) {
                $has[(int) $r->section_id][(int) $r->teacher_detail_id . '|' . (int) $r->subject_id . '|' . (int) $r->day_of_week] = true;
            }
            ksort($has);

            $parts = $mine
                ->groupBy(fn ($r) => $r->subject_id . '|' . $r->teacher_detail_id)
                ->map(function ($g) use ($has, &$entries) {
                    $teacher = (int) $g->first()->teacher_detail_id;
                    $subject = (int) $g->first()->subject_id;
                    $days    = $g->pluck('day_of_week')->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();

                    // A section takes the part along when it has it on every one of its days.
                    $sections = [];
                    foreach ($has as $sectionId => $theirEntries) {
                        if (collect($days)->every(fn ($d) => isset($theirEntries["{$teacher}|{$subject}|{$d}"]))) {
                            $sections[] = (int) $sectionId;
                            foreach ($days as $d) {
                                $entries[] = [(int) $sectionId, $teacher, $subject, $d];
                            }
                        }
                    }

                    return ['teacher_id' => $teacher, 'subject_id' => $subject, 'sections' => $sections, 'days' => $days, 'picked' => true];
                })
                ->sortBy(fn ($p) => $p['days'][0] ?? 9)
                ->values()
                ->all();
        }

        return [
            'period'     => $period,
            'start_time' => $start,
            'end_time'   => $end,
            'parts'      => $parts ?: [$this->blankPart()],
            'shared'     => ['start' => $start, 'end' => $end, 'entries' => $entries],
        ];
    }

    /** A subject's name, for messages. */
    private function subjectName($id): string
    {
        return collect($this->sectionSubjects)->firstWhere('id', (int) $id)['name'] ?? 'Subject';
    }

    /** What a row is called in a message: "Period 3", or its time when it is not a period. */
    private function rowName(array $row): string
    {
        return ($row['period'] ?? null)
            ? 'Period ' . $row['period']
            : ($row['start_time'] ?? '') . ' – ' . ($row['end_time'] ?? '');
    }

    /** Days picked by hand in a part stay as picked; the lines under a row follow. */
    public function updatedScheduleRows($value, $key): void
    {
        if (preg_match('/^(\d+)\.parts\.(\d+)\.days/', (string) $key, $m)
            && isset($this->scheduleRows[(int) $m[1]]['parts'][(int) $m[2]])) {
            $this->scheduleRows[(int) $m[1]]['parts'][(int) $m[2]]['picked'] = true;
        }

        $this->syncParts();
    }

    /**
     * Keeps each row's parts as the form shows them. The first part has the
     * days picked in it. Each part after it has days out of what the parts
     * above it left: the ones picked in it by hand, or — until then — all of
     * them. A part left with no day goes; and while days are still left over
     * after the last part, one more is offered for them.
     */
    private function syncParts(): void
    {
        $siblings = array_map(fn ($s) => (int) $s['id'], $this->otherSections());

        foreach ($this->scheduleRows as $i => $row) {
            $parts = array_values($row['parts'] ?? []);
            if (empty($parts)) {
                $parts = [$this->blankPart()];
            }
            foreach ($parts as $k => $p) {
                $parts[$k]['days']     = array_values(array_unique(array_map('intval', $p['days'] ?? [])));
                $parts[$k]['sections'] = array_values(array_intersect(array_unique(array_map('intval', $p['sections'] ?? [])), $siblings));
                $parts[$k]['picked']   = (bool) ($p['picked'] ?? false);
                sort($parts[$k]['days']);
            }

            $slot  = $this->slotDays($i);
            $first = array_shift($parts);
            $kept  = [$first];
            $taken = $first['days'];

            foreach ($parts as $p) {
                $left = array_values(array_diff($slot, $taken));
                if (empty($left)) break;

                $p['days'] = $p['picked'] ? array_values(array_intersect($p['days'], $left)) : $left;
                if (empty($p['days'])) continue;

                $kept[] = $p;
                $taken  = array_merge($taken, $p['days']);
            }

            $left = array_values(array_diff($slot, $taken));
            if (!empty($first['days']) && !empty($left)) {
                $kept[] = $this->blankPart($left);
            }

            $this->scheduleRows[$i]['parts'] = $kept;
        }
    }

    /** A time that is not one of the school's periods can be taken off the form (and so, on save, the timetable). */
    public function removeRow(int $index): void
    {
        if (!isset($this->scheduleRows[$index]) || ($this->scheduleRows[$index]['period'] ?? null)) return;
        unset($this->scheduleRows[$index]);
        $this->scheduleRows = array_values($this->scheduleRows);
        $this->syncParts();
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
     * (The school's periods never overlap; a time from an older timetable may.)
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
        $days = array_values(array_unique(array_merge(array_diff($this->defaultDays, $this->occupiedDaysForRow($rowIndex)), $own)));
        sort($days);
        return $days;
    }

    /**
     * Weekdays one part of a row may pick: the slot's days minus those the
     * parts above it have. (The first part may pick any; a day it takes is
     * taken off the part under it.)
     */
    public function availableDaysForPart(int $rowIndex, int $partIndex): array
    {
        $row = $this->scheduleRows[$rowIndex] ?? null;
        if (!$row) return [];

        $above = [];
        foreach (($row['parts'] ?? []) as $k => $p) {
            if ($k >= $partIndex) break;
            foreach (($p['days'] ?? []) as $d) $above[] = (int) $d;
        }

        $avail = array_diff($this->slotDays($rowIndex), $above);
        sort($avail);
        return array_values($avail);
    }

    /** As it was asked before a row had parts: the first part's days. */
    public function availableDaysForRow(int $rowIndex): array
    {
        return $this->availableDaysForPart($rowIndex, 0);
    }

    /** The class's other sections a period may be shared with. */
    public function otherSections(): array
    {
        return array_values(array_filter($this->createSections, fn($s) => (string) $s['id'] !== (string) $this->createSectionId));
    }

    // ─── Conflict checks ─────────────────────────────────────────────────
    /**
     * Availability check for one part: is its teacher already busy — in another
     * class/section (saved), or elsewhere in this same form — at an overlapping
     * time on any of the part's days? Returns a short reason or null. A section
     * the part is shared with is not "another class": the teacher takes them
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
        //    this part is shared with, or the row was when it was loaded.
        $together = array_merge(
            [(int) $this->createSectionId],
            array_map('intval', $part['sections'] ?? []),
            array_map(fn ($e) => (int) $e[0], $row['shared']['entries'] ?? [])
        );
        $clash = TeacherTimeTable::with(['standard:id,name', 'section:id,name'])
            ->where('teacher_detail_id', $teacherId)
            ->whereIn('day_of_week', $days)
            ->where('start_time', '<', $this->hms($end))
            ->where('end_time',   '>', $this->hms($start))
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

    /** Is a part complete: a teacher, a subject and at least one day? */
    private function isComplete(array $part): bool
    {
        return (int) ($part['subject_id'] ?? 0) > 0
            && (int) ($part['teacher_id'] ?? 0) > 0
            && !empty($part['days'] ?? []);
    }

    /** The parts of a row that are complete. */
    private function completeParts(array $row): array
    {
        return array_values(array_filter($row['parts'] ?? [], fn($p) => $this->isComplete($p)));
    }

    /**
     * A row with a part shared with other sections: does one of them already
     * have something else at that time on those days? What the row itself put
     * there before does not count — it is replaced on save. Returns a short
     * reason or null.
     */
    public function getShareConflict(int $rowIndex): ?string
    {
        $row = $this->scheduleRows[$rowIndex] ?? null;
        if (!$row) return null;

        $start = $row['start_time'] ?? '';
        $end   = $row['end_time'] ?? '';
        if (!$this->isTime($start) || !$this->isTime($end) || $start >= $end) return null;

        $siblings = array_map(fn ($s) => (int) $s['id'], $this->otherSections());

        // What the row puts into each section now, and on which days.
        $mine = [];
        $ask  = [];
        foreach ($this->completeParts($row) as $p) {
            foreach (array_intersect(array_map('intval', $p['sections'] ?? []), $siblings) as $sectionId) {
                foreach ($p['days'] as $d) {
                    $mine[$sectionId . '|' . (int) $p['teacher_id'] . '|' . (int) $p['subject_id'] . '|' . (int) $d] = true;
                    $ask[$sectionId][(int) $d] = true;
                }
            }
        }
        if (empty($ask)) return null;

        // What it had put there when it was loaded.
        $was = [];
        foreach (($row['shared']['entries'] ?? []) as $e) {
            $was[implode('|', $e) . '|' . ($row['shared']['start'] ?? '') . '|' . ($row['shared']['end'] ?? '')] = true;
        }

        $theirs = TeacherTimeTable::with(['section:id,name', 'subject:id,name'])
            ->where('organization_id', Auth::user()->organization_id)
            ->where('standard_id', $this->createStandardId)
            ->whereIn('section_id', array_keys($ask))
            ->where('start_time', '<', $this->hms($end))
            ->where('end_time',   '>', $this->hms($start))
            ->get();

        foreach ($theirs as $e) {
            if (!isset($ask[(int) $e->section_id][(int) $e->day_of_week])) continue;
            $key   = (int) $e->section_id . '|' . (int) $e->teacher_detail_id . '|' . (int) $e->subject_id . '|' . (int) $e->day_of_week;
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

        $this->syncParts();

        // Keep only rows that have at least one complete part: a teacher, a
        // subject and at least one day.
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
            $this->notification()->error('Choose a teacher and a subject for at least one period.');
            return;
        }

        $siblingIds = array_map(fn($s) => (int) $s['id'], $this->otherSections());

        foreach ($rowsToSave as $row) {
            $n = $this->rowName($row);
            if (!$this->isTime($row['start_time']) || !$this->isTime($row['end_time']) || $row['start_time'] >= $row['end_time']) {
                $this->notification()->error("{$n}: invalid time range."); return;
            }
            // Class double-booking guard (defensive — the Days dropdown already hides taken days).
            $occupiedClash = array_intersect($this->rowDays(['parts' => $row['__parts']]), $this->occupiedDaysForRow((int) $row['__idx']));
            if (!empty($occupiedClash)) {
                $dn = $this->daysOfWeekFull[(int) reset($occupiedClash)] ?? reset($occupiedClash);
                $this->notification()->error("{$n} ({$dn}): the class is already scheduled at this time."); return;
            }
            foreach ($this->scheduleRows[(int) $row['__idx']]['parts'] as $p => $part) {
                if ($this->isComplete($part) && ($conflict = $this->getPartConflict((int) $row['__idx'], (int) $p))) {
                    $this->notification()->error("{$n}: {$conflict}"); return;
                }
            }
            if ($conflict = $this->getShareConflict((int) $row['__idx'])) {
                $this->notification()->error("{$n}: {$conflict}"); return;
            }
        }

        // The class's timetable as it was, so each teacher hears what changed —
        // and the same for every section a period is, or was, shared with.
        $org  = (int) Auth::user()->organization_id;
        $push = app(TeacherPushNotifier::class);
        $before = $push->timetableSnapshot($org, (int) $this->createStandardId, (int) $this->createSectionId);

        $touched = [];
        foreach ($this->scheduleRows as $row) {
            foreach (($row['parts'] ?? []) as $part) {
                foreach (($part['sections'] ?? []) as $sid) {
                    if (in_array((int) $sid, $siblingIds, true)) $touched[(int) $sid] = true;
                }
            }
            foreach (($row['shared']['entries'] ?? []) as $e) {
                if (in_array((int) $e[0], $siblingIds, true)) $touched[(int) $e[0]] = true;
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
                if (($was['start'] ?? '') === '') continue;
                foreach (($was['entries'] ?? []) as [$sectionId, $teacherId, $subjectId, $day]) {
                    if (!in_array((int) $sectionId, $siblingIds, true)) continue;
                    TeacherTimeTable::where('organization_id', $org)
                        ->where('standard_id', $this->createStandardId)
                        ->where('section_id', $sectionId)
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
                    'start_time'        => $this->hms($start),
                    'end_time'          => $this->hms($end),
                    'is_active'         => true,
                ]);
                $created++;
            };

            foreach ($rowsToSave as $row) {
                foreach ($row['__parts'] as $part) {
                    $also = array_values(array_intersect(array_map('intval', $part['sections'] ?? []), $siblingIds));
                    foreach ($part['days'] as $day) {
                        $day = (int) $day;
                        if (!in_array($day, $this->defaultDays, true)) continue;
                        $tryCreate((int) $this->createSectionId, (int) $part['teacher_id'], (int) $part['subject_id'], $day, $row['start_time'], $row['end_time']);

                        // The same period in each section the part is shared with —
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
        $this->isEdit = false;
        $this->createStandardId = (string) $standardId;
        $this->updatedCreateStandardId();
        $this->createSectionId  = (string) $sectionId;
        $this->loadSectionSubjects();
        $this->buildScheduleRows();

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
            $push = app(TeacherPushNotifier::class);
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

        // The school's day: the serial of the period each time is, and — on the
        // class's own timetable — where the lunch break falls.
        $periods  = $this->periods();
        $lunch    = $this->lunch();
        $periodNo = [];
        foreach ($periods as $p) {
            $periodNo[$p['start'] . '|' . $p['end']] = $p['no'];
        }

        // CLASS VIEW: one card with the section's periods.
        // TEACHER VIEW: one card per (class, section) of that teacher, with the teacher's periods there.
        // A row is one time of the day; its lines are who teaches what then, and on which days.
        $sectionCards = collect();
        if ($entries->isNotEmpty()) {
            $sectionCards = $entries
                ->groupBy(fn($e) => $e->standard_id . '|' . ($e->section_id ?? ''))
                ->map(function ($items) use ($periods, $periodNo, $lunch) {
                    $first = $items->first();
                    $rows = $items
                        ->groupBy(fn($e) => substr((string) $e->start_time, 0, 5) . '|' . substr((string) $e->end_time, 0, 5))
                        ->map(function ($g, $key) use ($periodNo) {
                            $first = $g->first();
                            return [
                                'type'       => 'period',
                                'no'         => $periodNo[$key] ?? null,
                                'start_time' => substr((string) $first->start_time, 0, 5),
                                'end_time'   => substr((string) $first->end_time, 0, 5),
                                'lines'      => $g
                                    ->groupBy(fn($e) => $e->subject_id . '|' . $e->teacher_detail_id)
                                    ->map(fn($l) => [
                                        'subject' => $l->first()->subject?->name ?? '—',
                                        'teacher' => $l->first()->teacher?->user?->name ?? '—',
                                        'days'    => $l->pluck('day_of_week')->map(fn($d) => (int) $d)->unique()->sort()->values()->all(),
                                    ])
                                    ->sortBy(fn($l) => $l['days'][0] ?? 9)
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->sortBy('start_time')
                        ->values();

                    // A school with no periods set numbers the rows as they come, as before.
                    if (empty($periods)) {
                        $rows = $rows->map(fn($r, $i) => array_merge($r, ['no' => $i + 1]));
                    }
                    $count = $rows->count();

                    if ($lunch && $this->viewMode === 'class') {
                        $at = $rows->search(fn($r) => $r['start_time'] >= $lunch['start']);
                        $rows->splice($at === false ? $rows->count() : $at, 0, [[
                            'type'       => 'lunch',
                            'start_time' => $lunch['start'],
                            'end_time'   => $lunch['end'],
                        ]]);
                    }

                    return [
                        'standard_id' => $first->standard_id,
                        'section_id'  => $first->section_id,
                        'standard'    => $first->standard?->name ?? '—',
                        'section'     => $first->section?->name ?? '—',
                        'rows'        => $rows->values(),
                        'count'       => $count,
                    ];
                })
                ->sortBy([['standard_id', 'asc'], ['section_id', 'asc']])
                ->values();
        }

        // PERIODS TAB: the day in order — each period, and the lunch break where it falls.
        $dayRows = collect($periods)->map(fn($p) => ['type' => 'period'] + $p);
        if ($lunch) {
            $dayRows->push(['type' => 'lunch', 'no' => null] + $lunch);
        }
        $dayRows = $dayRows->sortBy('start')->values();

        return view('livewire.admin.time-table', [
            'sectionCards' => $sectionCards,
            'periods'      => $periods,
            'lunch'        => $lunch,
            'dayRows'      => $dayRows,
        ]);
    }
}
