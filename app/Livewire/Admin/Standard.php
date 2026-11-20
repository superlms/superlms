<?php

namespace App\Livewire\Admin;

use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard as StudentStandard;
use App\Models\Student\StandardSubject;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherAssignment;
use App\Models\User;
use App\Support\SchoolBrand;
use App\Support\StandardOrder;
use App\Support\StudentNumbers;
use App\Support\SubjectMove;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use WireUi\Traits\WireUiActions;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Standard extends Component
{
    // WithPagination keeps the list on its page across Livewire requests —
    // without it every action went back to page 1 and the page links broke.
    use WireUiActions, WithFileUploads, WithPagination;

    private const TABS      = ['standard', 'section', 'subject'];
    private const PER_PAGES = [10, 25, 50, 100];

    // Set only by the page's own edit buttons — never sent in by the browser.
    #[Locked]
    public $editId       = null;

    // Unified add/edit slide-in: choose what to add first (Class / Section /
    // Subject) and the matching form fields appear below in the same panel.
    public bool $openForm  = false;
    public string $addType = ''; // '' | 'class' | 'section' | 'subject'

    // Inline delete-confirm state (replaces WireUi dialog so the button always works)
    public bool $showDeleteConfirm = false;
    #[Locked]
    public ?string $deleteTargetType = null; // 'class' | 'section' | 'subject'
    #[Locked]
    public ?int $deleteTargetId       = null;

    // Standard fields (board comes from organization — no UI input)
    public $standardName   = '';
    public $standardCode   = '';
    public $standardBoard  = '';
    public $standardOrder  = '';
    public $standardActive = true;
    // Which of the school's two names this class's documents carry (1 = its
    // own, 2 = the second one the Super Admin set) — App\Support\SchoolBrand.
    // Only asked when the school has a second name.
    public $standardWing   = 1;

    // View modal
    public $showViewModal  = false;
    public $viewModalTitle = '';
    public $viewData       = [];
    // A tab switch is one step for the header's Back.
    #[Url(history: true, except: 'standard')]
    public $activeTab      = 'standard';

    // Search / filter
    public $search                = '';
    public $filterStandard        = '';
    public $filterStatus          = '';
    public $filterSubjectStandard = '';
    public $perPage               = 25;

    // Section fields
    public $sectionName        = '';
    public $sectionCode        = '';
    public $sectionDescription = '';
    public $sectionActive      = true;
    public $selectedStandard   = null;
    public $filterSection      = '';

    // Subject fields
    public $subjectName, $subjectCode;
    public $subjectActive                = true;
    public $selectedStandardForSubject   = null;
    public $selectedSectionsForSubject   = [];
    public $isMandatory                  = true;
    // The class a subject being edited was opened in — picking another class
    // moves the subject out of this one.
    #[Locked]
    public $subjectFromStandard          = null;
    // Subjects no longer carry an uploaded image — the icon is derived from the
    // subject name (App\Support\SubjectIcons). The detail image is still used
    // by the content screens, so it stays.
    public $subjectDetailImage;
    public $subjectDetailImageUrl;
    public $subjectDetailImagePreview    = null;

    // The class / section / subject lists are no longer kept in the page's
    // state: Livewire re-read every one of them (every subject of the school)
    // on each click and filter, before doing what was asked. Each is read
    // only when the page is drawn — see render().

    protected $listeners = [
        'onViewStandardAdmin',
        'onEditStandard',
        'onDeleteStandard',
        'onViewSectionAdmin',
        'onDeleteSection',
        'onEditSection',
        'onEditSubject',
        'onDeleteSubject',
        'onViewSubjectAdmin',
    ];

    public function mount(): void
    {
        $this->standardBoard = $this->resolveOrgBoard();
        $this->selectedSectionsForSubject = [];
    }

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    private function resolveOrgBoard(): string
    {
        return (string) (Organization::find($this->orgId())?->education_board ?? '');
    }

    /** The school's two names for the class form's Wing picker; empty when it has one. */
    public function getWingOptionsProperty(): array
    {
        return SchoolBrand::options(Organization::find($this->orgId()));
    }

    // ─── This school's own rows only ──────────────────────────────────────────
    // Every id below arrives from the browser, so each is looked up within the
    // admin's own school: another school's class, section or subject is never
    // shown, edited or deleted from here.

    private function ownClass($id): ?StudentStandard
    {
        return $id ? StudentStandard::where('organization_id', $this->orgId())->find((int) $id) : null;
    }

    private function ownSection($id): ?Section
    {
        return $id ? Section::whereIn('standard_id', StudentStandard::where('organization_id', $this->orgId())->select('id'))
            ->find((int) $id) : null;
    }

    private function ownSubject($id): ?Subject
    {
        return $id ? Subject::where('organization_id', $this->orgId())->find((int) $id) : null;
    }

    /** Every class of the school for the pickers — inactive ones too, so they can be found. */
    private function classOptions(): Collection
    {
        return StudentStandard::where('organization_id', $this->orgId())
            ->orderBy('order')->orderBy('id')->get(['id', 'name', 'is_active']);
    }

    /** A class's sections for the pickers — inactive ones too, so a saved tick is never lost. */
    private function sectionsOf($standardId): Collection
    {
        if (!$standardId || !$this->ownClass($standardId)) {
            return collect();
        }

        return Section::where('standard_id', (int) $standardId)->orderBy('id')->get(['id', 'standard_id', 'name', 'code', 'is_active']);
    }

    /** The class's one section, when it has just one — picked for the admin, no second step. */
    private function soleSectionOf($standardId): ?int
    {
        $ids = $this->sectionsOf($standardId)->pluck('id');

        return $ids->count() === 1 ? (int) $ids->first() : null;
    }

    private function perPage(): int
    {
        return in_array((int) $this->perPage, self::PER_PAGES, true) ? (int) $this->perPage : 25;
    }

    // Watch file uploads to generate previews
    public function updatedSubjectDetailImage(): void
    {
        $this->validate(['subjectDetailImage' => 'nullable|image|max:2048']);
        $this->subjectDetailImagePreview = $this->subjectDetailImage?->temporaryUrl();
    }

    public function updated($property): void
    {
        // A field the admin has since filled no longer shows its old error
        // ("The subject name field is required." under a typed name).
        $this->resetErrorBag(Str::before($property, '.'));

        if ($property === 'selectedStandardForSubject') {
            // Sections ticked under the previous class aren't this class's.
            $ids = $this->sectionsOf($this->selectedStandardForSubject)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $this->selectedSectionsForSubject = array_values(array_intersect(
                array_map('intval', (array) $this->selectedSectionsForSubject),
                $ids,
            ));
        }
        if ($property === 'filterSubjectStandard') {
            // A class with one section shows its subjects straight away: the
            // section is picked here, in the same request, instead of by the
            // browser in a second one after this one comes back.
            $this->filterSection = $this->soleSectionOf($this->filterSubjectStandard) ?? '';
        }
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search', 'filterStandard', 'filterStatus', 'filterSection', 'filterSubjectStandard', 'perPage'])) {
            if ($this->openForm) {
                $this->closeModal();
            }
            $this->resetPage();
        }
    }

    public function showTab(string $tab): void
    {
        $this->activeTab = in_array($tab, self::TABS, true) ? $tab : 'standard';
        $this->closeModal();
        $this->resetFilters();
    }

    public function drillIntoClass(int $standardId): void
    {
        if (!$this->ownClass($standardId)) {
            return;
        }
        $this->resetPage();
        $this->activeTab      = 'section';
        $this->filterStandard = $standardId;
        $this->search         = '';
        $this->filterStatus   = '';
    }

    public function drillIntoSection(int $sectionId): void
    {
        $section = $this->ownSection($sectionId);
        if ($section) {
            $this->resetPage();
            $this->activeTab             = 'subject';
            $this->filterSubjectStandard = $section->standard_id;
            $this->filterSection         = $sectionId;
            $this->search                = '';
            $this->filterStatus          = '';
        }
    }

    // ─── Lists ────────────────────────────────────────────────────────────────

    private function emptyPage()
    {
        return Subject::whereRaw('1 = 0')->paginate($this->perPage());
    }

    public function getFilteredStandardsProperty()
    {
        $query = StudentStandard::with(['sections' => fn($q) => $q->orderBy('id')->select('id', 'standard_id', 'name')])
            ->where('organization_id', $this->orgId());

        if ($this->search) {
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
            );
        }
        if ($this->filterStatus !== '') $query->where('is_active', $this->filterStatus === 'active');

        // By each class's Display Order.
        return $query->orderBy('order')->orderBy('id')->paginate($this->perPage());
    }

    public function getFilteredSectionsProperty()
    {
        // Require a class filter (of this school) — if none, return empty paginator
        if (!$this->filterStandard || !$this->ownClass($this->filterStandard)) {
            return $this->emptyPage();
        }

        $query = Section::with('standard:id,name')->where('standard_id', (int) $this->filterStandard);

        if ($this->search) {
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%")
            );
        }
        if ($this->filterStatus !== '') $query->where('is_active', $this->filterStatus === 'active');

        return $query->orderBy('id')->paginate($this->perPage());
    }

    /** The class whose subjects are listed: the one picked, else the picked section's own. */
    private function subjectListClassId(): int
    {
        $section = $this->ownSection($this->filterSection);
        if (!$section) {
            return 0;
        }
        // A section is listed under its own class only.
        if ($this->filterSubjectStandard && (int) $this->filterSubjectStandard !== (int) $section->standard_id) {
            return 0;
        }

        return (int) $section->standard_id;
    }

    public function getFilteredSubjectsProperty()
    {
        // Require a section filter — if none, return empty paginator
        $classId = $this->filterSection ? $this->subjectListClassId() : 0;
        if (!$classId) {
            return $this->emptyPage();
        }

        // The section's own subjects — its links saved for this class (the
        // rule every other screen lists them by, Subject::taughtIn). A link
        // left under the section by another class's subject no longer shows
        // that subject here, and each row names this class's sections only.
        $query = Subject::where('organization_id', $this->orgId())
            ->taughtIn($classId, (int) $this->filterSection)
            ->with(['sections' => fn($q) => $q->where('sections.standard_id', $classId)
                ->wherePivot('standard_id', $classId)
                ->orderBy('sections.id')]);

        if ($this->search) {
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%")
            );
        }
        if ($this->filterStatus !== '') $query->where('is_active', $this->filterStatus === 'active');

        return $query->orderBy('subjects.id')->paginate($this->perPage());
    }

    public function getAvailableSectionsProperty()
    {
        // Nothing to pick until a class is picked.
        return $this->sectionsOf($this->filterSubjectStandard);
    }

    /** The figure on each tab: the open tab's list, the others' plain totals. */
    private function tabCounts($list): array
    {
        $counts = [
            'standard' => StudentStandard::where('organization_id', $this->orgId())->count(),
            'section'  => $this->filterStandard && $this->ownClass($this->filterStandard)
                ? Section::where('standard_id', (int) $this->filterStandard)->count() : 0,
            'subject'  => ($classId = $this->filterSection ? $this->subjectListClassId() : 0)
                ? Subject::where('organization_id', $this->orgId())->taughtIn($classId, (int) $this->filterSection)->count() : 0,
        ];
        $counts[$this->activeTab] = $list->total();

        return $counts;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterStandard', 'filterStatus',
                      'filterSubjectStandard', 'filterSection']);
        $this->resetPage();
        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->openForm = false;
        $this->addType  = '';
        $this->reset([
            'editId', 'standardName', 'standardCode', 'standardOrder',
            'sectionName', 'sectionCode', 'sectionDescription', 'selectedStandard',
            'subjectName', 'subjectCode', 'subjectActive',
            'selectedStandardForSubject', 'selectedSectionsForSubject', 'isMandatory',
            'subjectDetailImage', 'subjectDetailImageUrl',
            'subjectDetailImagePreview', 'subjectFromStandard',
        ]);
        $this->standardActive = true;
        $this->sectionActive = true;
        $this->standardBoard = $this->resolveOrgBoard();
        $this->dispatch('onStandardAddUpdate');
    }

    private function resetStandardFields(): void
    {
        $this->reset(['standardName', 'standardCode', 'standardOrder']);
        $this->standardActive = true;
        $this->standardWing   = SchoolBrand::MAIN;
        $this->standardBoard  = $this->resolveOrgBoard();
        // Suggest the next code in sequence — the admin can overwrite it.
        $this->standardCode   = StudentNumbers::nextStandardCode($this->orgId());
    }

    private function resetSectionFields(): void
    {
        $this->reset(['sectionName', 'sectionCode', 'sectionDescription', 'selectedStandard']);
        $this->sectionActive = true;
    }

    private function resetSubjectFields(): void
    {
        $this->reset([
            'subjectName', 'subjectCode',
            'selectedStandardForSubject', 'selectedSectionsForSubject', 'isMandatory',
            'subjectDetailImage', 'subjectDetailImageUrl',
            'subjectDetailImagePreview', 'subjectFromStandard',
        ]);
        $this->subjectActive = true;
    }

    // ─── Unified add slide-in ───────────────────────────────────────────────

    /** Open the unified add panel — the user picks a type, then fields appear. */
    public function openAddForm(): void
    {
        $this->editId  = null;
        $this->addType = '';
        $this->resetStandardFields();
        $this->resetSectionFields();
        $this->resetSubjectFields();
        $this->resetValidation();
        $this->openForm = true;
    }

    /** When the "what to add" selector changes, prep that type's fields. */
    public function updatedAddType(): void
    {
        $this->editId = null;
        $this->resetValidation();

        match ($this->addType) {
            'class'   => $this->onStandard(),
            'section' => $this->onSection(),
            'subject' => $this->onSubject(),
            default   => null,
        };
    }

    /** Save whichever type is currently selected in the unified panel. */
    public function save(): void
    {
        match ($this->addType) {
            'class'   => $this->saveStandard(),
            'section' => $this->saveSection(),
            'subject' => $this->saveSubject(),
            default   => null,
        };
    }

    // ─── Per-type field prep (no open flag; panel is already open) ──────────────

    public function onStandard(): void
    {
        $this->resetStandardFields();
    }

    public function onSection(): void
    {
        $this->resetSectionFields();
        // Prefill class from drill-down filter if present
        if ($this->filterStandard && $this->ownClass($this->filterStandard)) {
            $this->selectedStandard = $this->filterStandard;
        }
    }

    public function onSubject(): void
    {
        $this->resetSubjectFields();
        // Prefill class/section from drill-down filters if present
        if ($this->filterSubjectStandard && $this->ownClass($this->filterSubjectStandard)) {
            $this->selectedStandardForSubject = $this->filterSubjectStandard;
            if ($this->filterSection && $this->sectionsOf($this->filterSubjectStandard)->contains('id', (int) $this->filterSection)) {
                $this->selectedSectionsForSubject = [(int) $this->filterSection];
            }
        }
    }

    // ─── Save Standard ────────────────────────────────────────────────────────

    public function saveStandard(): void
    {
        $orgId = $this->orgId();

        $this->validate([
            'standardName'  => 'required|string|max:255',
            'standardCode'  => 'required|string|max:10',
            'standardOrder' => ['nullable', StandardOrder::RULE],
            'standardWing'  => 'nullable|in:1,2',
        ], [
            'standardCode.required' => 'Please enter a class code.',
            'standardCode.max'      => 'Class code may not be longer than 10 characters.',
            'standardOrder.regex'   => 'Display order must be a whole number.',
        ]);

        if ($this->editId && !$this->ownClass($this->editId)) {
            $this->notification()->error('Class not found!', 'It may have been deleted. Please refresh the page.');
            $this->closeModal();
            return;
        }

        // Same-name within org cannot exist
        $dupName = StudentStandard::where('organization_id', $orgId)
            ->where('name', $this->standardName)
            ->when($this->editId, fn($q) => $q->where('id', '!=', $this->editId))
            ->exists();
        if ($dupName) {
            $this->addError('standardName', 'A class with this name already exists.');
            return;
        }

        // Nor can two classes share a code — roll numbers are built from it.
        $dupCode = StudentStandard::where('organization_id', $orgId)
            ->where('code', $this->standardCode)
            ->when($this->editId, fn($q) => $q->where('id', '!=', $this->editId))
            ->exists();
        if ($dupCode) {
            $this->addError('standardCode', 'A class with this code already exists.');
            return;
        }

        // Left blank, the class goes after the last one.
        $order = StandardOrder::parse($this->standardOrder) ?? StandardOrder::next($orgId);

        $data = [
            'name'            => $this->standardName,
            // A roll is this code + a 3-digit serial (05001), so what the admin
            // types here decides what this class's rolls look like; a changed
            // code changes them all (Standard model, StudentNumbers::recodeClass).
            'code'            => $this->standardCode,
            'board'           => $this->standardBoard ?: $this->resolveOrgBoard(),
            'order'           => $order,
            'is_active'       => $this->standardActive,
            'organization_id' => $orgId,
        ];

        // The name its documents carry — only when the school has two; a
        // school with one leaves every class as it is.
        if ($this->wingOptions && Schema::hasColumn('standards', 'wing')) {
            $data['wing'] = (int) $this->standardWing === SchoolBrand::SECOND ? SchoolBrand::SECOND : SchoolBrand::MAIN;
        }

        // No two classes share an order: whoever holds it moves down one.
        DB::transaction(function () use ($orgId, $order, $data) {
            StandardOrder::makeRoom($orgId, $order, $this->editId ? (int) $this->editId : null);

            if ($this->editId) {
                StudentStandard::where('organization_id', $orgId)->find($this->editId)->update($data);
            } else {
                StudentStandard::create($data);
            }
        });

        $this->notification()->success($this->editId ? 'Class updated successfully!' : 'Class created successfully!');

        $this->closeModal();
        $this->mount();
    }

    // ─── Save Section ─────────────────────────────────────────────────────────

    public function saveSection(): void
    {
        $orgId = $this->orgId();

        $this->validate([
            'sectionName'      => 'required|string|max:255',
            'selectedStandard' => ['required', Rule::exists('standards', 'id')->where('organization_id', $orgId)],
        ], [
            'selectedStandard.required' => 'Please select a class.',
            'selectedStandard.exists'   => 'Please select a class.',
        ]);

        $section = $this->editId ? $this->ownSection($this->editId) : null;
        if ($this->editId && !$section) {
            $this->notification()->error('Section not found!', 'It may have been deleted. Please refresh the page.');
            $this->closeModal();
            return;
        }

        // Its students stay in the class they were admitted to, so a section
        // with students cannot be moved under another class — they would be
        // in this section but still in the old class.
        if ($section && (int) $section->standard_id !== (int) $this->selectedStandard
            && StudentDetail::where('section_id', $section->id)->exists()) {
            $this->addError('selectedStandard', 'Students are assigned to this section. Move them to another section before changing its class.');
            return;
        }

        // Same name cannot duplicate within a class
        $dup = Section::where('standard_id', $this->selectedStandard)
            ->where('name', $this->sectionName)
            ->when($this->editId, fn($q) => $q->where('id', '!=', $this->editId))
            ->exists();
        if ($dup) {
            $this->addError('sectionName', 'A section with this name already exists in the selected class.');
            return;
        }

        $data = [
            'name'            => $this->sectionName,
            'description'     => $this->sectionDescription,
            'standard_id'     => $this->selectedStandard,
            'is_active'       => $this->sectionActive,
            'organization_id' => $orgId,
        ];

        $creating = !$section;
        if ($section) {
            $section->update($data);
            $this->notification()->success('Section updated successfully!');
        } else {
            Section::create($data);
            $this->notification()->success('Section created successfully!');
        }

        // Show the class the section was saved in, so it is there in the list.
        $classId = (int) $this->selectedStandard;
        $this->closeModal();
        $this->activeTab      = 'section';
        if ((int) $this->filterStandard !== $classId) {
            $this->filterStandard = $classId;
            $this->resetPage();
        }
        if ($creating) {
            $this->search       = '';
            $this->filterStatus = '';
        }
        $this->mount();
    }

    // ─── Save Subject ─────────────────────────────────────────────────────────

    public function saveSubject(): void
    {
        $org = $this->orgId();

        $this->validate([
            'subjectName'                  => 'required|string|max:255',
            'selectedStandardForSubject'   => ['required', Rule::exists('standards', 'id')->where('organization_id', $org)],
            'selectedSectionsForSubject'   => 'required|array|min:1',
            'selectedSectionsForSubject.*' => 'exists:sections,id',
            'subjectDetailImage'           => 'nullable|image|max:2048',
        ], [
            'selectedStandardForSubject.required' => 'Please select a class.',
            'selectedStandardForSubject.exists'   => 'Please select a class.',
            'selectedSectionsForSubject.required' => 'Please select at least one section.',
            'selectedSectionsForSubject.min'      => 'Please select at least one section.',
        ]);

        if ($this->editId && !$this->ownSubject($this->editId)) {
            $this->notification()->error('Subject not found!', 'It may have been deleted. Please refresh the page.');
            $this->closeModal();
            return;
        }

        $toStandard = (int) $this->selectedStandardForSubject;

        $picked = array_unique(array_map('intval', $this->selectedSectionsForSubject));
        $ownSections = SubjectMove::sectionsIn($picked, $toStandard);
        if (count($ownSections) !== count($picked)) {
            $this->addError('selectedSectionsForSubject', 'Please select sections of the selected class.');
            return;
        }

        // Editing a subject into another class moves it out of the class it
        // was opened in — unless that class's timetable or assignments use it.
        $fromStandard = $this->editId ? (int) $this->subjectFromStandard : 0;
        $moving       = $fromStandard && $fromStandard !== $toStandard;

        if ($this->editId) {
            if ($moving && ($blocker = SubjectMove::blocker((int) $this->editId, $fromStandard))) {
                $this->addError('selectedStandardForSubject', $blocker);
                return;
            }

            $dupName = Subject::where('organization_id', $org)
                ->where('name', $this->subjectName)
                ->where('id', '!=', $this->editId)
                ->whereHas('standards', fn($q) => $q->where('standard_id', $toStandard))
                ->exists();
            if ($dupName) {
                $this->addError('subjectName', 'A subject with this name already exists in the selected class.');
                return;
            }
        }

        // For CREATE: a subject with this name may already exist in the class but
        // be missing its section link (an orphan that shows in no section). Reuse
        // that subject and just add the missing links, rather than blocking it
        // forever. (Fixes: "already exists" yet nothing shows in the section.)
        $existing = null;
        if (!$this->editId) {
            $existing = Subject::where('organization_id', $org)
                ->where('name', $this->subjectName)
                ->whereHas('standards', fn($q) => $q->where('standard_id', $toStandard))
                ->first();
        }

        // Reusing an existing subject that is ALREADY linked to every selected
        // section is a true no-op duplicate → block. Otherwise we fall through
        // and repair the missing links.
        if ($existing) {
            $linked = SectionSubject::where('subject_id', $existing->id)
                ->where('standard_id', $toStandard)
                ->pluck('section_id')->map(fn($v) => (int) $v)->all();
            $missing = array_diff($picked, $linked);
            if (empty($missing)) {
                $this->addError('subjectName', 'A subject with this name already exists in the selected section(s).');
                return;
            }
        }

        $subjectData = [
            'name'            => $this->subjectName,
            'organization_id' => $org,
            'is_active'       => $this->subjectActive,
        ];

        // No subject image: the icon is derived from the name at render time.

        try {
            // Detail image upload
            if ($this->subjectDetailImage) {
                if ($this->editId) {
                    $this->safeS3Delete(Subject::find($this->editId)?->detail_image);
                }
                $path = $this->subjectDetailImage->store('admin/subjects/detail-images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $subjectData['detail_image'] = Storage::disk('s3')->url($path);
            } elseif ($this->subjectDetailImageUrl) {
                $subjectData['detail_image'] = $this->subjectDetailImageUrl;
            }

            // One transaction so the subject and ALL its class/section links are
            // saved together — never a subject that exists but is linked to no
            // section (which is what left subjects invisible before).
            DB::transaction(function () use ($org, $existing, $subjectData, $moving, $fromStandard, $toStandard, $picked) {
                if ($this->editId) {
                    $subject = Subject::find($this->editId);
                    $subject->update($subjectData);
                    // Moved: no longer in the old class or its sections.
                    if ($moving) {
                        SubjectMove::leave($subject->id, $fromStandard);
                    }
                    // Edit replaces this class's section links wholesale.
                    SectionSubject::where('subject_id', $subject->id)
                        ->where('standard_id', $toStandard)->delete();
                } elseif ($existing) {
                    $existing->update($subjectData);   // reuse the same-name subject
                    $subject = $existing;
                } else {
                    $subject = Subject::create($subjectData);
                }

                StandardSubject::updateOrCreate(
                    ['standard_id' => $toStandard, 'subject_id' => $subject->id],
                    ['organization_id' => $org, 'is_mandatory' => (bool) $this->isMandatory]
                );

                // firstOrCreate → idempotent: no duplicate pivot rows, and any
                // missing section link is repaired.
                foreach ($picked as $sectionId) {
                    SectionSubject::firstOrCreate(
                        [
                            'section_id'  => $sectionId,
                            'subject_id'  => $subject->id,
                            'standard_id' => $toStandard,
                        ],
                        ['organization_id' => $org]
                    );
                }
            });

            $this->notification()->success($this->editId ? 'Subject updated successfully!' : 'Subject saved successfully!');

            // Show it: stay on the section being viewed when the subject is in
            // it, else go to its class and first section.
            $creating = !$this->editId;
            $this->closeModal();
            $this->activeTab = 'subject';
            $viewing = (int) $this->filterSection;
            if (!in_array($viewing, $ownSections, true) || $this->subjectListClassId() !== $toStandard) {
                $this->filterSubjectStandard = $toStandard;
                $this->filterSection         = $ownSections[0];
                $this->resetPage();
            }
            if ($creating) {
                $this->search       = '';
                $this->filterStatus = '';
            }
            $this->mount();
        } catch (\Throwable $e) {
            logger()->error('saveSubject failed: ' . $e->getMessage());
            $this->notification()->error('Error!', 'Failed to save subject: ' . $e->getMessage());
        }
    }

    public function editStandard(int $id): void
    {
        $s = $this->ownClass($id);
        if ($s) {
            $this->resetValidation();
            $this->editId         = $id;
            $this->standardName   = $s->name;
            // A class from before codes existed opens with the next one suggested.
            $this->standardCode   = $s->code ?: StudentNumbers::nextStandardCode($s->organization_id);
            $this->standardBoard  = $s->board ?: $this->resolveOrgBoard();
            $this->standardOrder  = $s->order;
            $this->standardActive = (bool) $s->is_active;
            $this->standardWing   = SchoolBrand::wingOf($s);
            $this->addType        = 'class';
            $this->openForm       = true;
        }
    }

    public function editSection(int $id): void
    {
        $s = $this->ownSection($id);
        if ($s) {
            $this->resetValidation();
            $this->editId             = $id;
            $this->sectionName        = $s->name;
            $this->sectionCode        = $s->code;
            $this->sectionDescription = $s->description;
            $this->selectedStandard   = $s->standard_id;
            $this->sectionActive      = (bool) $s->is_active;
            $this->addType            = 'section';
            $this->openForm           = true;
        }
    }

    public function editSubject(int $id): void
    {
        $subject = $this->ownSubject($id);
        if (!$subject) return;

        $this->resetValidation();
        $this->editId               = $id;
        $this->subjectName          = $subject->name;
        $this->subjectCode          = $subject->code;
        $this->subjectActive        = (bool) $subject->is_active;
        $this->subjectDetailImageUrl = $subject->detail_image;
        $this->subjectDetailImagePreview = $subject->detail_image;

        // The class being viewed, when the subject is in it; else its first.
        $ss = $this->subjectLink($id);
        $this->subjectFromStandard = $ss?->standard_id;
        if ($ss) {
            $this->selectedStandardForSubject = $ss->standard_id;
            $this->isMandatory                = (bool) $ss->is_mandatory;
            // Only this class's own sections: a link left behind by a section
            // since moved to another class (or deleted) has no box to untick,
            // and would fail the save's "sections of the selected class" check.
            $this->selectedSectionsForSubject = SubjectMove::sectionsIn(
                SectionSubject::where('subject_id', $id)
                    ->where('standard_id', $ss->standard_id)->pluck('section_id')->all(),
                (int) $ss->standard_id,
            );
        }
        $this->addType  = 'subject';
        $this->openForm = true;
    }

    /** The subject's link to the class being viewed, when it is in it; else to its first class. */
    private function subjectLink(int $subjectId): ?StandardSubject
    {
        return StandardSubject::where('subject_id', $subjectId)
            ->when($this->filterSubjectStandard, fn($q) => $q->orderByRaw('standard_id = ? desc', [(int) $this->filterSubjectStandard]))
            ->orderBy('id')
            ->first();
    }

    public function onEditStandard(int $id): void  { $this->editStandard($id); }
    public function onEditSection(int $id): void   { $this->editSection($id); }
    public function onEditSubject(int $id): void   { $this->editSubject($id); }

    public function onDeleteStandard(int $id): void
    {
        $this->deleteTargetType  = 'class';
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }

    public function onDeleteSection(int $id): void
    {
        $this->deleteTargetType  = 'section';
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }

    public function onDeleteSubject(int $id): void
    {
        $this->deleteTargetType  = 'subject';
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetType  = null;
        $this->deleteTargetId    = null;
    }

    public function confirmDelete(): void
    {
        if (!$this->deleteTargetId || !$this->deleteTargetType) {
            $this->cancelDelete();
            return;
        }

        $id   = (int) $this->deleteTargetId;
        $type = $this->deleteTargetType;

        $this->cancelDelete();

        match ($type) {
            'class'   => $this->performDeleteStandard($id),
            'section' => $this->performDeleteSection($id),
            'subject' => $this->performDeleteSubject($id),
            default   => null,
        };
    }

    public function performDeleteStandard(int $id): void
    {
        $standard = $this->ownClass($id);
        if (!$standard) return;

        // Block delete while students are assigned to this class. Deleting is not
        // allowed here — the admin can edit the class, but must first move its
        // students to another class before it can be removed.
        if (StudentDetail::where('standard_id', $id)->exists()) {
            $this->notification()->warning(
                'Cannot Delete Class',
                'Students are assigned to this class. You can edit it, but it cannot be deleted until the students are moved to another class.'
            );
            return;
        }

        // Block if any sections still exist (req #4)
        if (Section::where('standard_id', $id)->exists()) {
            $this->notification()->warning('Cannot Delete!', 'Please delete all sections of this class first.');
            return;
        }

        try {
            DB::transaction(function () use ($id, $standard) {
                // Orphan students → inactive, null their class/section (req #6)
                $studentDetails = StudentDetail::where('standard_id', $id)->get();
                $userIds = $studentDetails->pluck('user_id')->filter()->all();
                if ($userIds) {
                    User::whereIn('id', $userIds)->update(['is_active' => false]);
                }
                StudentDetail::where('standard_id', $id)->update([
                    'standard_id' => null,
                    'section_id'  => null,
                ]);

                // Tidy up any standard-subject pivots (shouldn't have section-subject left at this point)
                StandardSubject::where('standard_id', $id)->delete();

                $standard->delete();
            });
        } catch (\Throwable $e) {
            logger()->error('performDeleteStandard failed: ' . $e->getMessage());
            $this->notification()->error('Could not delete class', $e->getMessage());
            return;
        }

        // The class being looked at is gone: back to the list of classes.
        if ((int) $this->filterStandard === $id || (int) $this->filterSubjectStandard === $id) {
            $this->resetFilters();
        }

        $this->notification()->success('Class deleted successfully!');
        $this->mount();
        $this->dispatch('onStandardAddUpdate');
    }

    public function performDeleteSection(int $id): void
    {
        $section = $this->ownSection($id);
        if (!$section) return;

        // Block delete while students are assigned to this section. The admin can
        // edit the section, but must first move its students to another section
        // before it can be removed.
        if (StudentDetail::where('section_id', $id)->exists()) {
            $this->notification()->warning(
                'Cannot Delete Section',
                'Students are assigned to this section. You can edit it, but it cannot be deleted until the students are moved to another section.'
            );
            return;
        }

        try {
            DB::transaction(function () use ($id, $section) {
                // Cascade subjects mapped to this section (req #5)
                $subjectIds = SectionSubject::where('section_id', $id)->pluck('subject_id')->unique();

                SectionSubject::where('section_id', $id)->delete();

                foreach ($subjectIds as $subjectId) {
                    $stillUsed = SectionSubject::where('subject_id', $subjectId)->exists();
                    if (!$stillUsed) {
                        StandardSubject::where('subject_id', $subjectId)->delete();
                        $subject = Subject::find($subjectId);
                        if ($subject) {
                            // S3 deletes are best-effort — a storage hiccup must
                            // not roll back (or 500) the whole section deletion.
                            $this->safeS3Delete($subject->image);
                            $this->safeS3Delete($subject->detail_image);
                            $subject->delete();
                        }
                    }
                }

                // Detach students from this section (so the class can later be
                // deleted). section_id is nullable — students become section-less.
                StudentDetail::where('section_id', $id)->update(['section_id' => null]);

                $section->delete();
            });
        } catch (\Throwable $e) {
            logger()->error('performDeleteSection failed: ' . $e->getMessage());
            $this->notification()->error('Could not delete section', $e->getMessage());
            return;
        }

        // The section being looked at is gone: back to its class's sections.
        if ((int) $this->filterSection === $id) {
            $this->filterSection = '';
            $this->resetPage();
        }

        $this->notification()->success('Section and its subjects deleted successfully!');
        $this->mount();
        $this->dispatch('onStandardAddUpdate');
    }

    /** Delete an S3 object without ever throwing (missing creds / object). */
    private function safeS3Delete(?string $url): void
    {
        if (!$url) return;
        try {
            Storage::disk('s3')->delete(ltrim((string) parse_url($url, PHP_URL_PATH), '/'));
        } catch (\Throwable $e) {
            logger()->warning('safeS3Delete failed: ' . $e->getMessage());
        }
    }

    public function performDeleteSubject(int $id): void
    {
        $subject = $this->ownSubject($id);
        if (!$subject) return;

        if (\App\Models\Admin\TeacherTimeTable::where('subject_id', $id)->exists()
            || TeacherAssignment::where('subject_id', $id)->exists()) {
            $this->notification()->warning('Cannot Delete!', 'This subject is used in timetable or assignments.');
            return;
        }

        try {
            // The subject and its links go together, or not at all — before, a
            // storage error after the links were gone left a subject no section
            // listed.
            DB::transaction(function () use ($id, $subject) {
                StandardSubject::where('subject_id', $id)->delete();
                SectionSubject::where('subject_id', $id)->delete();
                $subject->delete();
            });
        } catch (\Throwable $e) {
            logger()->error('performDeleteSubject failed: ' . $e->getMessage());
            $this->notification()->error('Could not delete subject', $e->getMessage());
            return;
        }

        $this->safeS3Delete($subject->image);
        $this->safeS3Delete($subject->detail_image);

        $this->notification()->success('Subject deleted successfully!');
        $this->mount();
    }

    public function onViewStandardAdmin(int $id): void
    {
        $s = $this->ownClass($id);
        if (!$s) { $this->notification()->error('Class not found!'); return; }

        $this->viewModalTitle = 'Class Details';
        $this->viewData = [
            'name'           => $s->name,
            'code'           => $s->code,
            'board'          => $s->board ?: $this->resolveOrgBoard(),
            'order'          => $s->order,
            'is_active'      => $s->is_active ? 'Active' : 'Inactive',
            'sections_count' => Section::where('standard_id', $s->id)->count(),
            'subjects_count' => Subject::where('organization_id', $this->orgId())->taughtIn($s->id)->count(),
            'created_at'     => $s->created_at?->format('d M Y, h:i A'),
        ];
        $this->showViewModal = true;
    }

    public function onViewSectionAdmin(int $id): void
    {
        $s = $this->ownSection($id);
        if (!$s) { $this->notification()->error('Section not found!'); return; }

        $this->viewModalTitle = 'Section Details';
        $this->viewData = [
            'name'           => $s->name,
            'code'           => $s->code,
            'description'    => $s->description,
            'class'          => $s->standard?->name,
            'is_active'      => $s->is_active ? 'Active' : 'Inactive',
            // Its own subjects — as the Subjects list counts them.
            'subjects_count' => Subject::where('organization_id', $this->orgId())->taughtIn($s->standard_id, $s->id)->count(),
            'created_at'     => $s->created_at?->format('d M Y, h:i A'),
        ];
        $this->showViewModal = true;
    }

    public function onViewSubjectAdmin(int $id): void
    {
        $s = $this->ownSubject($id);
        if (!$s) { $this->notification()->error('Subject not found!'); return; }

        // The class being viewed (or its first), with that class's own flag
        // and sections — the pivot the list read carried no Mandatory flag,
        // so every subject showed "No".
        $link     = $this->subjectLink($id);
        $standard = $link ? StudentStandard::find($link->standard_id) : null;
        $sections = $standard
            ? Section::where('standard_id', $standard->id)
                ->whereIn('id', SectionSubject::where('subject_id', $id)->where('standard_id', $standard->id)->select('section_id'))
                ->orderBy('id')->pluck('name')->unique()->implode(', ')
            : '';

        $this->viewModalTitle = 'Subject Details';
        $this->viewData = [
            'name'         => $s->name,
            'code'         => $s->code,
            'description'  => $s->description,
            'is_active'    => $s->is_active ? 'Active' : 'Inactive',
            'image'        => $s->image,
            'detail_image' => $s->detail_image,
            'class'        => $standard?->name ?? 'Not assigned',
            'is_mandatory' => $link ? ($link->is_mandatory ? 'Yes' : 'No') : 'N/A',
            'sections'     => $sections,
            'created_at'   => $s->created_at?->format('d M Y, h:i A'),
        ];
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal  = false;
        $this->viewData       = [];
        $this->viewModalTitle = '';
    }

    public function render()
    {
        // ?activeTab=anything-else opens on Classes rather than a blank page.
        if (!in_array($this->activeTab, self::TABS, true)) {
            $this->activeTab = 'standard';
        }

        // Only the open tab's list is read.
        $list = match ($this->activeTab) {
            'section' => $this->getFilteredSectionsProperty(),
            'subject' => $this->getFilteredSubjectsProperty(),
            default   => $this->getFilteredStandardsProperty(),
        };

        $subjectClassId = $this->activeTab === 'subject' && $this->filterSection ? $this->subjectListClassId() : 0;

        return view('livewire.admin.standard', [
            'list'              => $list,
            'tabCounts'         => $this->tabCounts($list),
            'filteredStandards' => $this->activeTab === 'standard' ? $list : null,
            'filteredSections'  => $this->activeTab === 'section' ? $list : null,
            'filteredSubjects'  => $this->activeTab === 'subject' ? $list : null,
            'subjectClassName'  => $subjectClassId ? StudentStandard::whereKey($subjectClassId)->value('name') : null,
            'allStandards'      => $this->classOptions(),
            'availableSections' => $this->getAvailableSectionsProperty(),
            // The subject form's section boxes: the class picked there.
            'formSections'      => $this->openForm && $this->addType === 'subject'
                ? $this->sectionsOf($this->selectedStandardForSubject) : collect(),
        ]);
    }
}
