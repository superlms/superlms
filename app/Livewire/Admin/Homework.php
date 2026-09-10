<?php

namespace App\Livewire\Admin;

use App\Models\Admin\HomeWork as ModalHomework;
use App\Models\Admin\HomeWorkCompletion;
use App\Models\Organization;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use WireUi\Traits\WireUiActions;
use Carbon\Carbon;

class Homework extends Component
{
    use WireUiActions, WithFileUploads, WithPagination;

    public $open = false;
    public $showViewModal = false;
    public $editId = null;
    public $embedded = false;

    // Tabs: 'homework' (assignments) | 'status' (per-student completion tracker)
    public string $activeTab = 'homework';

    // ── Homework Status tab filters: date → class → section → student → subject.
    //    Only class + section are required; a blank date means "the recent window",
    //    a blank student means "everyone in the section", a blank subject "all".
    public $hwStatusDate     = '';
    public $hwStatusStandard = '';
    public $hwStatusSection  = '';
    public $hwStatusStudent  = '';
    public $hwStatusSubject  = '';
    public $hwStatusSections  = [];
    public $hwStatusStudents  = [];
    public $hwStatusSubjects  = [];
    // How many days back the status register covers when no single date is picked.
    public int $hwStatusDays = 14;
    // Homework older than this is purged nightly (see routes/console.php), so
    // the status date picker stops there — there is nothing behind it to show.
    public const STATUS_DATE_WINDOW_DAYS = 30;
    
    // Form fields
    public $title = '';
    public $standard_id = '';
    public $section_id = '';
    public $subject_id = '';
    public $description = '';
    public $homework_file;
    public $is_active = true;
    public $subject_selection = 'single'; // 'single' or 'all'

    // "All subjects" mode — one homework entry per subject, keyed by subject id:
    //   [subjectId => ['title' => '', 'description' => '', 'file' => UploadedFile|null]]
    // A subject left with a blank title is treated as "no homework for it".
    public $subjectHomeworks = [];
    
    // Temporary URLs for preview
    public $tempFileUrl = null;
    
    // Dropdown data
    public $standards = [];
    public $sections = [];
    public $subjects = [];
    
    // View modal data
    public $viewModalTitle = '';
    public $viewHomework = null;

    // Delete confirm — a plain in-page modal rather than WireUI's dialog, whose
    // runtime-built colour classes are not in the compiled Tailwind bundle.
    public bool $showDeleteModal = false;
    public $pendingDeleteId = null;

    // Teachers dropdown
    public $teachers = [];

    // Table filters
    #[Url]
    public $search = '';

    #[Url]
    public $perPage = 10;

    #[Url]
    public $filterTeacher = '';

    // The date a homework was assigned. Sits between teacher and class in the
    // bar, and changing it deliberately leaves class/section/subject alone.
    #[Url]
    public $filterDate = '';

    #[Url]
    public $filterStandard = '';

    #[Url]
    public $filterSection = '';

    #[Url]
    public $filterSubject = '';

    #[Url]
    public $filterStatus = '';

    // Filter dropdown data
    public $filterSections = [];
    public $filterSubjects = [];

    protected $listeners = ['refresh-homework-list' => '$refresh'];

    public function mount($embedded = false)
    {
        $this->embedded = $embedded;
        $this->loadStandards();
        $this->loadTeachers();
        $this->loadFilterData();
    }

    public function loadStandards()
    {
        $organizationId = Auth::user()->organization_id;
        
        $this->standards = Standard::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    public function loadTeachers()
    {
        $organizationId = Auth::user()->organization_id;
        
        $this->teachers = User::where('organization_id', $organizationId)
            ->where('role','teacher')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function loadFilterData()
    {
        if ($this->filterStandard) {
            $this->filterSections = Section::where('standard_id', $this->filterStandard)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            $this->loadFilterSubjects($this->filterStandard, $this->filterSection);
        }
    }

    private function loadFilterSubjects($standardId, $sectionId = null)
    {
        $this->filterSubjects = $this->subjectsFor($standardId, $sectionId);
    }

    /**
     * The subjects taught to a class (or to one of its sections), as a plain
     * collection — every dropdown that needs them goes through here.
     */
    private function subjectsFor($standardId, $sectionId = null)
    {
        $organizationId = Auth::user()->organization_id;

        if ($sectionId) {
            return Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $sectionId)
                ->where('section_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $organizationId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        }

        return Subject::join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
            ->where('standard_subjects.standard_id', $standardId)
            ->where('subjects.organization_id', $organizationId)
            ->where('subjects.is_active', true)
            ->select('subjects.*')
            ->distinct()
            ->orderBy('subjects.name')
            ->get();
    }

    public function updated($property, $value)
    {
        // Reset pagination when filters change
        if (in_array($property, ['search', 'filterTeacher', 'filterDate', 'filterStandard', 'filterSection', 'filterSubject', 'filterStatus'])) {
            $this->resetPage();
        }

        // Handle filter standard change
        if ($property === 'filterStandard' && $value) {
            $this->filterSections = Section::where('standard_id', $value)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
            $this->filterSection = '';

            $this->loadFilterSubjects($value);
            $this->filterSubject = '';
        } elseif ($property === 'filterStandard' && !$value) {
            $this->filterSections = [];
            $this->filterSection = '';
            $this->filterSubjects = [];
            $this->filterSubject = '';
        }

        // Handle filter section change
        if ($property === 'filterSection' && $value && $this->filterStandard) {
            $this->loadFilterSubjects($this->filterStandard, $value);
            $this->filterSubject = '';
        } elseif ($property === 'filterSection' && !$value && $this->filterStandard) {
            $this->loadFilterSubjects($this->filterStandard);
            $this->filterSubject = '';
        }

        // Subject and teacher are two ways of narrowing the same class+section,
        // so only one is ever on: picking a teacher drops the subject (and the
        // picker itself), clearing the teacher hands the subject picker back.
        if ($property === 'filterTeacher' && $value) {
            $this->filterSubject = '';
        }

        // NOTE: filterDate is deliberately absent from the cascades above. Moving
        // through dates keeps whatever teacher / class / section / subject is
        // selected, so those only change when they are changed on purpose.

        // Handle subject selection type change in form
        if ($property === 'subject_selection') {
            if ($value === 'all') {
                $this->subject_id = ''; // Reset single subject selection
                $this->syncSubjectHomeworkRows();
            }
        }
    }

    /**
     * Keep $subjectHomeworks in sync with the currently loaded $subjects while
     * in "all subjects" mode — one blank entry per subject, preserving anything
     * the user has already typed.
     */
    private function syncSubjectHomeworkRows(): void
    {
        $rows = [];
        foreach ($this->subjects as $subject) {
            $existing = $this->subjectHomeworks[$subject->id] ?? [];
            $rows[$subject->id] = [
                'title'       => $existing['title'] ?? '',
                'description' => $existing['description'] ?? '',
                'file'        => $existing['file'] ?? null,
            ];
        }
        $this->subjectHomeworks = $rows;
    }

    public function updatedStandardId($value)
    {
        $this->section_id = '';
        $this->subject_id = '';
        $this->sections = [];
        $this->subjects = [];

        if ($value) {
            $this->sections = Section::where('standard_id', $value)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            $this->loadSubjectsForStandard($value);
        }

        if ($this->subject_selection === 'all') {
            $this->syncSubjectHomeworkRows();
        }
    }

    public function updatedSectionId($value)
    {
        $this->subject_id = '';

        if ($value && $this->standard_id) {
            $this->loadSubjectsForStandard($this->standard_id, $value);
        } elseif (!$value && $this->standard_id) {
            $this->loadSubjectsForStandard($this->standard_id);
        }

        if ($this->subject_selection === 'all') {
            $this->syncSubjectHomeworkRows();
        }
    }

    private function loadSubjectsForStandard($standardId, $sectionId = null)
    {
        $organizationId = Auth::user()->organization_id;

        if ($sectionId) {
            $this->subjects = Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $sectionId)
                ->where('section_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $organizationId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        } else {
            $this->subjects = Subject::join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
                ->where('standard_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $organizationId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        }

        if ($this->subjects->isEmpty()) {
            $this->subjects = collect();
        }
    }

    public function updatedHomeworkFile()
    {
        $this->validate([
            'homework_file' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png|max:1024',
        ], [
            'homework_file.max' => 'Attachment must be 1 MB (1024 KB) or smaller.',
        ]);
        $this->tempFileUrl = $this->homework_file->getClientOriginalName();
    }

    public function onAddHomework()
    {
        $this->open = true;
    }

    public function closeModal()
    {
        $this->open = false;
        $this->resetForm();
    }

    public function onSave()
    {
        // "All subjects" bulk create takes its own path (multiple rows).
        if (!$this->editId && $this->subject_selection === 'all') {
            $this->saveAllSubjects();
            return;
        }

        $fileMessages = ['homework_file.max' => 'Attachment must be 1 MB (1024 KB) or smaller.'];

        $this->validate([
            'title' => 'required|string|max:255',
            'standard_id' => 'required|exists:standards,id',
            'section_id' => 'nullable|exists:sections,id',
            'description' => 'required|string',
            'subject_selection' => 'required|in:single,all',
            'subject_id' => 'required|exists:subjects,id',
            'homework_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png|max:1024',
        ], $fileMessages);

        try {
            // section_id / subject_id are NOT NULL (default 0) on home_works, so
            // "none" is stored as 0 — passing null would violate the constraint
            // and silently fail the save.
            $data = [
                'title' => $this->title,
                'standard_id' => $this->standard_id,
                'section_id' => $this->section_id ?: 0,
                'subject_id' => $this->subject_id,
                'description' => $this->description,
                'user_id' => Auth::id(),
                'organization_id' => Auth::user()->organization_id,
            ];

            if ($this->editId) {
                $homework = ModalHomework::findOrFail($this->editId);

                if ($this->homework_file) {
                    if ($homework->file) {
                        $oldFilePath = parse_url($homework->file, PHP_URL_PATH);
                        Storage::disk('s3')->delete($oldFilePath);
                    }

                    $filePath = $this->homework_file->store('admin/homework/files', 's3');
                    Storage::disk('s3')->setVisibility($filePath, 'public');
                    $data['file'] = Storage::disk('s3')->url($filePath);
                }

                $homework->update($data);
                $this->notification()->success('Homework updated successfully!');
            } else {
                if ($this->homework_file) {
                    $filePath = $this->homework_file->store('admin/homework/files', 's3');
                    Storage::disk('s3')->setVisibility($filePath, 'public');
                    $data['file'] = Storage::disk('s3')->url($filePath);
                }

                ModalHomework::create($data);
                $this->notification()->success('Homework added successfully!');
            }

            $this->closeModal();
        } catch (\Throwable $e) {
            $this->notification()->error(
                'Error Saving Homework',
                $e->getMessage()
            );
            logger()->error('Homework save error: ' . $e->getMessage());
        }
    }

    /**
     * "All subjects" mode — create one homework row per subject that the admin
     * actually filled in. A subject whose title is left blank is skipped, i.e.
     * treated as "no homework for that subject". Each subject can carry its own
     * title, description and (≤1 MB) attachment.
     */
    private function saveAllSubjects(): void
    {
        $this->validate([
            'standard_id' => 'required|exists:standards,id',
            'section_id'  => 'nullable|exists:sections,id',
        ]);

        // Collect the filled-in subjects and validate their files up front.
        $toCreate = [];
        foreach ($this->subjects as $subject) {
            $entry = $this->subjectHomeworks[$subject->id] ?? [];
            $title = trim((string) ($entry['title'] ?? ''));

            if ($title === '') {
                continue; // blank title → no homework for this subject
            }

            if (!empty($entry['file'])) {
                $this->validate([
                    "subjectHomeworks.{$subject->id}.file" => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png|max:1024',
                ], [
                    "subjectHomeworks.{$subject->id}.file.max" => "{$subject->name}: attachment must be 1 MB (1024 KB) or smaller.",
                ]);
            }

            $toCreate[] = [
                'subject_id'  => $subject->id,
                'title'       => $title,
                'description' => trim((string) ($entry['description'] ?? '')),
                'file'        => $entry['file'] ?? null,
            ];
        }

        if (empty($toCreate)) {
            $this->notification()->error('Please fill homework for at least one subject.');
            return;
        }

        try {
            $org        = Auth::user()->organization_id;
            $userId     = Auth::id();
            $sectionId  = $this->section_id ?: 0;

            DB::beginTransaction();
            foreach ($toCreate as $row) {
                $data = [
                    'title'           => $row['title'],
                    'standard_id'     => $this->standard_id,
                    'section_id'      => $sectionId,
                    'subject_id'      => $row['subject_id'],
                    'description'     => $row['description'],
                    'user_id'         => $userId,
                    'organization_id' => $org,
                ];

                if (!empty($row['file'])) {
                    $filePath = $row['file']->store('admin/homework/files', 's3');
                    Storage::disk('s3')->setVisibility($filePath, 'public');
                    $data['file'] = Storage::disk('s3')->url($filePath);
                }

                ModalHomework::create($data);
            }
            DB::commit();

            $count = count($toCreate);
            $this->notification()->success("Homework added for {$count} subject(s)!");
            $this->closeModal();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->notification()->error('Error Saving Homework', $e->getMessage());
            logger()->error('Homework (all subjects) save error: ' . $e->getMessage());
        }
    }

    protected function resetForm()
    {
        $this->reset([
            'editId',
            'title',
            'standard_id',
            'section_id',
            'subject_id',
            'description',
            'homework_file',
            'subject_selection',
            'subjectHomeworks',
            'tempFileUrl',
            'sections',
            'subjects'
        ]);
        $this->resetErrorBag();
    }

    public function onEditHomework($id)
    {
        $homework = ModalHomework::findOrFail($id);

        $this->editId = $homework->id;
        $this->title = $homework->title;
        $this->standard_id = $homework->standard_id;
        // section_id / subject_id use 0 as the "none" sentinel on this table.
        $this->section_id = $homework->section_id ?: '';
        $this->subject_id = $homework->subject_id ?: '';
        $this->description = $homework->description;
        // Editing always targets one homework row, so it's always single-subject.
        $this->subject_selection = 'single';

        if ($homework->standard_id) {
            $this->sections = Section::where('standard_id', $homework->standard_id)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            $this->loadSubjectsForStandard($homework->standard_id, $homework->section_id);
        }

        $this->open = true;
    }

    public function onDeleteHomework($id)
    {
        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
    }

    public function doDeleteHomework()
    {
        $id = $this->pendingDeleteId;
        $this->cancelDelete();

        if (!$id) {
            return;
        }

        try {
            $homework = ModalHomework::where('organization_id', Auth::user()->organization_id)
                ->findOrFail($id);

            if ($homework->file) {
                $filePath = parse_url($homework->file, PHP_URL_PATH);
                Storage::disk('s3')->delete($filePath);
            }

            $homework->delete();

            $this->notification()->success('Homework deleted successfully!');
        } catch (\Exception $e) {
            $this->notification()->error(
                'Error Deleting Homework',
                $e->getMessage()
            );
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterTeacher', 'filterDate', 'filterStandard', 'filterSection', 'filterSubject']);
        $this->filterSections = [];
        $this->filterSubjects = [];
        $this->resetPage();
    }

    // ═══════════════════════════ TABS + STATUS TRACKER ═══════════════════════════

    public function switchTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['homework', 'status'], true) ? $tab : 'homework';
    }

    /** Oldest date the status register can be asked about (30 days back). */
    public function hwStatusMinDate(): string
    {
        return Carbon::today()->subDays(self::STATUS_DATE_WINDOW_DAYS)->toDateString();
    }

    /** Nothing is assigned in the future, so today is as far forward as it goes. */
    public function hwStatusMaxDate(): string
    {
        return Carbon::today()->toDateString();
    }

    /**
     * The picker carries min/max, but a date can still be typed straight into
     * it — anything outside the window snaps back to the nearest end.
     */
    public function updatedHwStatusDate($value): void
    {
        if (!$value) {
            return;
        }

        try {
            $picked = Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            $this->hwStatusDate = '';
            return;
        }

        $min = Carbon::parse($this->hwStatusMinDate());
        $max = Carbon::parse($this->hwStatusMaxDate());

        if ($picked->lt($min)) {
            $this->hwStatusDate = $min->toDateString();
            $this->notification()->info(
                'Only the last ' . self::STATUS_DATE_WINDOW_DAYS . ' days',
                'Homework older than that is removed automatically, so there is nothing to show behind ' . $min->format('d M Y') . '.'
            );
        } elseif ($picked->gt($max)) {
            $this->hwStatusDate = $max->toDateString();
        }
    }

    public function updatedHwStatusStandard($value): void
    {
        $this->hwStatusSection = '';
        $this->hwStatusStudent = '';
        $this->hwStatusSubject = '';
        $this->hwStatusStudents = [];
        $this->hwStatusSubjects = [];
        $this->hwStatusSections = $value
            ? Section::where('standard_id', $value)->where('is_active', true)->orderBy('id')->get()
            : [];
    }

    public function updatedHwStatusSection($value): void
    {
        $this->hwStatusStudent = '';
        $this->hwStatusSubject = '';
        $this->hwStatusStudents = ($value && $this->hwStatusStandard)
            ? StudentDetail::where('organization_id', Auth::user()->organization_id)
                ->where('standard_id', $this->hwStatusStandard)
                ->where('section_id', $value)
                ->whereNotNull('user_id')
                ->orderBy('full_name')
                ->get(['id', 'user_id', 'full_name', 'roll_no'])
            : [];

        $this->hwStatusSubjects = ($value && $this->hwStatusStandard)
            ? $this->subjectsFor($this->hwStatusStandard, $value)
            : [];
    }

    public function clearStatusFilters(): void
    {
        $this->reset(['hwStatusDate', 'hwStatusStandard', 'hwStatusSection', 'hwStatusStudent', 'hwStatusSubject']);
        $this->hwStatusSections = [];
        $this->hwStatusStudents = [];
        $this->hwStatusSubjects = [];
    }

    /** The register needs at least a class and a section to have any scope. */
    public function hwStatusReady(): bool
    {
        return (bool) ($this->hwStatusStandard && $this->hwStatusSection);
    }

    /** Homework in scope for the status filters: the section, narrowed by date and subject. */
    private function statusHomeworks()
    {
        $q = ModalHomework::with('subject:id,name')
            ->where('organization_id', Auth::user()->organization_id)
            ->where('standard_id', $this->hwStatusStandard)
            ->where('section_id', $this->hwStatusSection);

        if ($this->hwStatusSubject) {
            $q->where('subject_id', $this->hwStatusSubject);
        }

        // created_at is the assigned-on date. One date narrows to that day;
        // otherwise the register covers the recent window.
        if ($this->hwStatusDate) {
            $q->whereDate('created_at', $this->hwStatusDate);
        } else {
            $q->whereDate('created_at', '>=', Carbon::today()->subDays($this->hwStatusDays)->toDateString());
        }

        return $q->orderBy('created_at')->get();
    }

    /** [userId => [homeWorkId => true]] for the students who marked these done. */
    private function completionMap($homeworkIds): array
    {
        $map = [];
        foreach (HomeWorkCompletion::whereIn('home_work_id', $homeworkIds)->get(['user_id', 'home_work_id']) as $c) {
            $map[$c->user_id][$c->home_work_id] = true;
        }
        return $map;
    }

    /**
     * Build the status register. With a student picked it reads day by day (one
     * row per day, chips for that day's subjects); with no student it reads
     * student by student across whatever the date filter left in scope.
     *
     * @return array{mode:string, rows:array, scope:string}
     */
    private function buildStatusRows(): array
    {
        if (!$this->hwStatusReady()) {
            return ['mode' => 'none', 'rows' => [], 'scope' => ''];
        }

        $homeworks = $this->statusHomeworks();
        $done      = $this->completionMap($homeworks->pluck('id'));

        $scope = $this->hwStatusDate
            ? Carbon::parse($this->hwStatusDate)->format('l, d M Y')
            : 'Last ' . $this->hwStatusDays . ' days';
        if ($this->hwStatusSubject) {
            $subject = collect($this->hwStatusSubjects)->firstWhere('id', (int) $this->hwStatusSubject);
            if ($subject) $scope .= ' · ' . $subject->name;
        }

        return $this->hwStatusStudent
            ? ['mode' => 'by_day', 'rows' => $this->statusRowsByDay($homeworks, $done), 'scope' => $scope]
            : ['mode' => 'by_student', 'rows' => $this->statusRowsByStudent($homeworks, $done), 'scope' => $scope];
    }

    /** One row per day for the selected student. */
    private function statusRowsByDay($homeworks, array $done): array
    {
        $student = StudentDetail::where('organization_id', Auth::user()->organization_id)
            ->find($this->hwStatusStudent);
        if (!$student) {
            return [];
        }

        $mine   = $done[$student->user_id] ?? [];
        $byDate = $homeworks->groupBy(fn($h) => Carbon::parse($h->created_at)->toDateString());

        // A single date shows just that day; otherwise walk the window back.
        $dates = $this->hwStatusDate
            ? [Carbon::parse($this->hwStatusDate)]
            : collect(range(0, $this->hwStatusDays))->map(fn($i) => Carbon::today()->subDays($i))->all();

        $rows = [];
        foreach ($dates as $date) {
            $rows[] = [
                'date'  => $date->format('d M Y'),
                'day'   => $date->format('l'),
                'items' => $byDate->get($date->toDateString(), collect())->map(fn($h) => [
                    'subject'  => $h->subject->name ?? 'General',
                    'title'    => $h->title,
                    'complete' => isset($mine[$h->id]),
                ])->values()->all(),
            ];
        }

        return $rows;
    }

    /** One row per student in the section, across everything in scope. */
    private function statusRowsByStudent($homeworks, array $done): array
    {
        $rows = [];

        foreach ($this->hwStatusStudents as $student) {
            $mine = $done[$student->user_id] ?? [];

            $items = $homeworks->map(fn($h) => [
                'subject'  => $h->subject->name ?? 'General',
                'title'    => $h->title,
                'date'     => Carbon::parse($h->created_at)->format('d M'),
                'complete' => isset($mine[$h->id]),
            ])->values()->all();

            $completed = count(array_filter($items, fn($i) => $i['complete']));

            $rows[] = [
                'name'      => $student->full_name,
                'roll_no'   => $student->roll_no,
                'items'     => $items,
                'completed' => $completed,
                'total'     => count($items),
            ];
        }

        return $rows;
    }

    /** The Homework tab only lists rows once at least one filter is set. */
    private function hasActiveFilter(): bool
    {
        return $this->search !== '' || $this->filterTeacher !== '' || $this->filterDate !== ''
            || $this->filterStandard !== '' || $this->filterSection !== '' || $this->filterSubject !== '';
    }

    public function onViewHomework($id)
    {
        $homework = ModalHomework::with(['standard', 'section', 'subject', 'user'])->find($id);

        if (!$homework) {
            $this->notification()->error('Homework not found!');
            return;
        }

        $this->viewModalTitle = 'Homework Details';
        $this->viewHomework = $homework;
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewHomework = null;
        $this->viewModalTitle = '';
    }

    public function render()
    {
        // Best-effort opportunistic purge — trims homework older than 30 days on
        // render. The daily `homework:purge-old` schedule guarantees coverage;
        // this catches dev/preview environments where the scheduler isn't running.
        $this->purgeOldHomework();

        // Homework tab: only query once the admin has applied a filter.
        $isFiltered = $this->hasActiveFilter();
        $homeworks  = ($this->activeTab === 'homework' && $isFiltered) ? $this->getHomeworks() : null;

        // Status tab: the register, shaped by whether a student is selected.
        $status = $this->activeTab === 'status'
            ? $this->buildStatusRows()
            : ['mode' => 'none', 'rows' => [], 'scope' => ''];
        $statusRows = $status['rows'];

        // Statistics
        $statistics = $this->getStatistics();

        return view('livewire.admin.homework', compact('homeworks', 'statistics', 'isFiltered', 'status', 'statusRows'));
    }

    /**
     * Hard-delete this organization's homework older than 30 days, cleaning up
     * associated S3 files. Mirrors the daily console command for envs where the
     * scheduler may not be running.
     */
    protected function purgeOldHomework(): void
    {
        $cutoff = Carbon::now()->subDays(30);

        $stale = ModalHomework::where('organization_id', Auth::user()->organization_id)
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($stale as $row) {
            if ($row->file) {
                try {
                    Storage::disk('s3')->delete(ltrim(parse_url($row->file, PHP_URL_PATH), '/'));
                } catch (\Throwable $e) {
                    logger()->warning('Homework purge: failed to delete S3 file for #' . $row->id . ': ' . $e->getMessage());
                }
            }
            $row->delete();
        }
    }

    private function getHomeworks()
    {
        $query = ModalHomework::with(['standard', 'section', 'subject', 'user'])
            ->where('organization_id', Auth::user()->organization_id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('standard', function ($standardQuery) {
                        $standardQuery->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('subject', function ($subjectQuery) {
                        $subjectQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->filterTeacher) {
            $query->where('user_id', $this->filterTeacher);
        }

        // created_at is the assigned-on date for homework.
        if ($this->filterDate) {
            $query->whereDate('created_at', $this->filterDate);
        }

        if ($this->filterStandard) {
            $query->where('standard_id', $this->filterStandard);
        }

        if ($this->filterSection) {
            $query->where('section_id', $this->filterSection);
        }

        if ($this->filterSubject) {
            $query->where('subject_id', $this->filterSubject);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    private function getStatistics()
    {
        $organizationId = Auth::user()->organization_id;
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();

        return [
            'total' => ModalHomework::where('organization_id', $organizationId)->count(),
            'this_week' => ModalHomework::where('organization_id', $organizationId)
                ->where('created_at', '>=', $startOfWeek)
                ->count(),
            'by_teacher' => User::where('organization_id', $organizationId)
                ->whereHas('homeworks')
                ->distinct()
                ->count('id'),
            'by_class' => Standard::where('organization_id', $organizationId)
                ->whereHas('homeworks')
                ->distinct()
                ->count('id'),
        ];
    }
}