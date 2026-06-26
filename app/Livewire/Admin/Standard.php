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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use WireUi\Traits\WireUiActions;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class Standard extends Component
{
    use WireUiActions, WithFileUploads;

    public $openStandard = false;
    public $openSection  = false;
    public $openSubject  = false;
    public $editId       = null;

    // Inline delete-confirm state (replaces WireUi dialog so the button always works)
    public bool $showDeleteConfirm = false;
    public ?string $deleteTargetType = null; // 'class' | 'section' | 'subject'
    public ?int $deleteTargetId       = null;
    public $standards, $sections, $subjects;

    // Standard fields (board comes from organization — no UI input)
    public $standardName   = '';
    public $standardCode   = '';
    public $standardBoard  = '';
    public $standardOrder  = '';
    public $standardActive = true;

    // View modal
    public $showViewModal  = false;
    public $viewModalTitle = '';
    public $viewData       = [];
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
    public $subjectName, $subjectCode, $subjectDescription;
    public $subjectActive                = true;
    public $selectedStandardForSubject   = null;
    public $selectedSectionsForSubject   = [];
    public $isMandatory                  = true;
    public $existingSubjects             = [];
    public $subjectImage;
    public $subjectDetailImage;
    public $subjectImageUrl, $subjectDetailImageUrl;
    public $subjectImagePreview          = null;
    public $subjectDetailImagePreview    = null;

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
        $this->loadStandards();
        $this->loadAllSections();
        $this->loadAllSubjects();
        $this->selectedSectionsForSubject = [];
    }

    private function resolveOrgBoard(): string
    {
        return (string) (Organization::find(Auth::user()->organization_id)?->education_board ?? '');
    }

    // Watch file uploads to generate previews
    public function updatedSubjectImage(): void
    {
        $this->validate(['subjectImage' => 'nullable|image|max:2048']);
        $this->subjectImagePreview = $this->subjectImage?->temporaryUrl();
    }

    public function updatedSubjectDetailImage(): void
    {
        $this->validate(['subjectDetailImage' => 'nullable|image|max:2048']);
        $this->subjectDetailImagePreview = $this->subjectDetailImage?->temporaryUrl();
    }

    public function updated($property): void
    {
        if ($property === 'selectedStandardForSubject' && $this->selectedStandardForSubject) {
            $this->loadSectionsForSelectedStandard();
            $this->loadExistingSubjectsForStandard();
        }
        if ($property === 'filterSubjectStandard') {
            $this->filterSection = '';
        }
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search', 'filterStandard', 'filterStatus', 'filterSection', 'filterSubjectStandard', 'perPage'])) {
            $this->closeModal();
        }
    }

    public function showTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->closeModal();
        $this->resetFilters();
    }

    public function drillIntoClass(int $standardId): void
    {
        $this->activeTab      = 'section';
        $this->filterStandard = $standardId;
        $this->search         = '';
        $this->filterStatus   = '';
    }

    public function drillIntoSection(int $sectionId): void
    {
        $section = Section::find($sectionId);
        if ($section) {
            $this->activeTab             = 'subject';
            $this->filterSubjectStandard = $section->standard_id;
            $this->filterSection         = $sectionId;
            $this->search                = '';
            $this->filterStatus          = '';
        }
    }

    public function loadStandards(): void
    {
        $this->standards = StudentStandard::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)->orderBy('id')->get();
    }

    public function loadAllSections(): void
    {
        $this->sections = Section::with('standard')
            ->whereHas('standard', fn($q) => $q->where('organization_id', Auth::user()->organization_id))
            ->where('is_active', true)->orderBy('id')->get();
    }

    public function loadAllSubjects(): void
    {
        $this->subjects = Subject::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)->orderBy('id')->get();
    }

    public function loadSectionsForSelectedStandard(): void
    {
        $this->sections = Section::where('standard_id', $this->selectedStandardForSubject)
            ->where('is_active', true)->orderBy('id')->get();
    }

    public function loadExistingSubjectsForStandard(): void
    {
        if (!$this->selectedStandardForSubject) {
            $this->existingSubjects = [];
            return;
        }
        $this->existingSubjects = StandardSubject::where('standard_id', $this->selectedStandardForSubject)
            ->where('organization_id', Auth::user()->organization_id)
            ->with('subject')
            ->get()
            ->map(fn($ss) => [
                'id'           => $ss->subject->id,
                'name'         => $ss->subject->name,
                'code'         => $ss->subject->code,
                'is_mandatory' => $ss->is_mandatory,
                'sections'     => SectionSubject::where('subject_id', $ss->subject_id)
                    ->where('standard_id', $this->selectedStandardForSubject)
                    ->pluck('section_id')->toArray(),
            ])->toArray();
    }

    // ─── Computed properties ──────────────────────────────────────────────────

    public function getFilteredStandardsProperty()
    {
        $query = StudentStandard::withCount('sections')
            ->with(['sections' => fn($q) => $q->orderBy('id')->select('id', 'standard_id', 'name')])
            ->where('organization_id', Auth::user()->organization_id);

        if ($this->search) {
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
            );
        }
        if ($this->filterStatus !== '') $query->where('is_active', $this->filterStatus === 'active');

        return $query->orderBy('id')->paginate($this->perPage);
    }

    public function getFilteredSectionsProperty()
    {
        // Require a class filter — if none, return empty paginator
        if (!$this->filterStandard) {
            return Section::whereRaw('1 = 0')->paginate($this->perPage);
        }

        $query = Section::with('standard')
            ->whereHas('standard', fn($q) => $q->where('organization_id', Auth::user()->organization_id))
            ->where('standard_id', $this->filterStandard);

        if ($this->search) {
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%")
            );
        }
        if ($this->filterStatus !== '') $query->where('is_active', $this->filterStatus === 'active');

        return $query->orderBy('id')->paginate($this->perPage);
    }

    public function getFilteredSubjectsProperty()
    {
        // Require a section filter — if none, return empty paginator
        if (!$this->filterSection) {
            return Subject::whereRaw('1 = 0')->paginate($this->perPage);
        }

        $query = Subject::with(['standards', 'sections'])
            ->where('organization_id', Auth::user()->organization_id)
            ->whereHas('sections', fn($q) => $q->where('section_id', $this->filterSection));

        if ($this->search) {
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%")
            );
        }
        if ($this->filterSubjectStandard) {
            $query->whereHas('standards', fn($q) => $q->where('standard_id', $this->filterSubjectStandard));
        }
        if ($this->filterStatus !== '') $query->where('is_active', $this->filterStatus === 'active');

        return $query->orderBy('id')->paginate($this->perPage);
    }

    public function getAvailableSectionsProperty()
    {
        $query = Section::with('standard')
            ->whereHas('standard', fn($q) => $q->where('organization_id', Auth::user()->organization_id))
            ->where('is_active', true);

        if ($this->filterSubjectStandard) {
            $query->where('standard_id', $this->filterSubjectStandard);
        }
        return $query->orderBy('id')->get();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterStandard', 'filterStatus',
                      'filterSubjectStandard', 'filterSection']);
        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->openStandard = false;
        $this->openSection  = false;
        $this->openSubject  = false;
        $this->reset([
            'editId', 'standardName', 'standardCode', 'standardOrder',
            'sectionName', 'sectionCode', 'sectionDescription', 'selectedStandard',
            'subjectName', 'subjectCode', 'subjectDescription', 'subjectActive',
            'selectedStandardForSubject', 'selectedSectionsForSubject', 'isMandatory',
            'subjectImage', 'subjectDetailImage', 'subjectImageUrl', 'subjectDetailImageUrl',
            'subjectImagePreview', 'subjectDetailImagePreview', 'existingSubjects',
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
        $this->standardBoard  = $this->resolveOrgBoard();
    }

    private function resetSectionFields(): void
    {
        $this->reset(['sectionName', 'sectionCode', 'sectionDescription', 'selectedStandard']);
        $this->sectionActive = true;
    }

    private function resetSubjectFields(): void
    {
        $this->reset([
            'subjectName', 'subjectCode', 'subjectDescription',
            'selectedStandardForSubject', 'selectedSectionsForSubject', 'isMandatory',
            'subjectImage', 'subjectDetailImage', 'subjectImageUrl', 'subjectDetailImageUrl',
            'subjectImagePreview', 'subjectDetailImagePreview', 'existingSubjects',
        ]);
        $this->subjectActive = true;
    }

    // ─── Open modals ──────────────────────────────────────────────────────────

    public function onStandard(): void
    {
        $this->editId = null;
        $this->resetStandardFields();
        $this->openStandard = true;
    }

    public function onSection(): void
    {
        $this->editId = null;
        $this->resetSectionFields();
        // Prefill class from drill-down filter if present
        if ($this->filterStandard) {
            $this->selectedStandard = $this->filterStandard;
        }
        $this->openSection = true;
    }

    public function onSubject(): void
    {
        $this->editId = null;
        $this->resetSubjectFields();
        // Prefill class/section from drill-down filters if present
        if ($this->filterSubjectStandard) {
            $this->selectedStandardForSubject = $this->filterSubjectStandard;
            $this->loadSectionsForSelectedStandard();
            $this->loadExistingSubjectsForStandard();
            if ($this->filterSection) {
                $this->selectedSectionsForSubject = [(int) $this->filterSection];
            }
        }
        $this->openSubject = true;
    }

    // ─── Save Standard ────────────────────────────────────────────────────────

    public function saveStandard(): void
    {
        $orgId = Auth::user()->organization_id;

        $this->validate([
            'standardName' => 'required|string|max:255',
            'standardCode' => 'required|string|max:50',
        ]);

        // Same-name OR same-code within org cannot exist
        $dupName = StudentStandard::where('organization_id', $orgId)
            ->where('name', $this->standardName)
            ->when($this->editId, fn($q) => $q->where('id', '!=', $this->editId))
            ->exists();
        if ($dupName) {
            $this->addError('standardName', 'A class with this name already exists.');
            return;
        }

        $dupCode = StudentStandard::where('organization_id', $orgId)
            ->where('code', $this->standardCode)
            ->when($this->editId, fn($q) => $q->where('id', '!=', $this->editId))
            ->exists();
        if ($dupCode) {
            $this->addError('standardCode', 'A class with this code already exists.');
            return;
        }

        $data = [
            'name'            => $this->standardName,
            'code'            => $this->standardCode,
            'board'           => $this->standardBoard ?: $this->resolveOrgBoard(),
            'order'           => $this->standardOrder ? (int) $this->standardOrder : 0,
            'is_active'       => $this->standardActive,
            'organization_id' => $orgId,
        ];

        if ($this->editId) {
            StudentStandard::find($this->editId)->update($data);
            $this->notification()->success('Class updated successfully!');
        } else {
            StudentStandard::create($data);
            $this->notification()->success('Class created successfully!');
        }

        $this->closeModal();
        $this->mount();
    }

    // ─── Save Section ─────────────────────────────────────────────────────────

    public function saveSection(): void
    {
        $this->validate([
            'sectionName'      => 'required|string|max:255',
            'sectionCode'      => 'required|string|max:50',
            'selectedStandard' => 'required|exists:standards,id',
        ]);

        // Same name+code combo cannot duplicate within a class
        $dup = Section::where('standard_id', $this->selectedStandard)
            ->where('name', $this->sectionName)
            ->where('code', $this->sectionCode)
            ->when($this->editId, fn($q) => $q->where('id', '!=', $this->editId))
            ->exists();
        if ($dup) {
            $this->addError('sectionName', 'A section with this name and code already exists in the selected class.');
            return;
        }

        $data = [
            'name'            => $this->sectionName,
            'code'            => $this->sectionCode,
            'description'     => $this->sectionDescription,
            'standard_id'     => $this->selectedStandard,
            'is_active'       => $this->sectionActive,
            'organization_id' => Auth::user()->organization_id,
        ];

        if ($this->editId) {
            Section::find($this->editId)->update($data);
            $this->notification()->success('Section updated successfully!');
        } else {
            Section::create($data);
            $this->notification()->success('Section created successfully!');
        }

        $this->closeModal();
        $this->activeTab = 'section';
        $this->mount();
    }

    // ─── Save Subject ─────────────────────────────────────────────────────────

    public function saveSubject(): void
    {
        $this->validate([
            'subjectName'                  => 'required|string|max:255',
            'subjectCode'                  => 'required|string|max:50',
            'selectedStandardForSubject'   => 'required|exists:standards,id',
            'selectedSectionsForSubject'   => 'required|array|min:1',
            'selectedSectionsForSubject.*' => 'exists:sections,id',
            'subjectImage'                 => 'nullable|image|max:2048',
            'subjectDetailImage'           => 'nullable|image|max:2048',
        ], [
            'selectedSectionsForSubject.required' => 'Please select at least one section.',
            'selectedSectionsForSubject.min'      => 'Please select at least one section.',
        ]);

        // Duplicate name OR code within the class
        $dupName = StandardSubject::where('standard_id', $this->selectedStandardForSubject)
            ->whereHas('subject', fn($q) => $q->where('name', $this->subjectName)
                ->when($this->editId, fn($q) => $q->where('id', '!=', $this->editId)))
            ->exists();

        if ($dupName) {
            $this->addError('subjectName', 'A subject with this name already exists in the selected class.');
            return;
        }

        $dupCode = StandardSubject::where('standard_id', $this->selectedStandardForSubject)
            ->whereHas('subject', fn($q) => $q->where('code', $this->subjectCode)
                ->when($this->editId, fn($q) => $q->where('id', '!=', $this->editId)))
            ->exists();

        if ($dupCode) {
            $this->addError('subjectCode', 'A subject with this code already exists in the selected class.');
            return;
        }

        $subjectData = [
            'name'            => $this->subjectName,
            'code'            => $this->subjectCode,
            'description'     => $this->subjectDescription,
            'organization_id' => Auth::user()->organization_id,
            'is_active'       => $this->subjectActive,
        ];

        // Image upload
        if ($this->subjectImage) {
            if ($this->editId) {
                $old = Subject::find($this->editId)?->image;
                if ($old) Storage::disk('s3')->delete(parse_url($old, PHP_URL_PATH));
            }
            $path = $this->subjectImage->store('admin/subjects/images', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $subjectData['image'] = Storage::disk('s3')->url($path);
        } elseif ($this->subjectImageUrl) {
            $subjectData['image'] = $this->subjectImageUrl;
        }

        // Detail image upload
        if ($this->subjectDetailImage) {
            if ($this->editId) {
                $old = Subject::find($this->editId)?->detail_image;
                if ($old) Storage::disk('s3')->delete(parse_url($old, PHP_URL_PATH));
            }
            $path = $this->subjectDetailImage->store('admin/subjects/detail-images', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $subjectData['detail_image'] = Storage::disk('s3')->url($path);
        } elseif ($this->subjectDetailImageUrl) {
            $subjectData['detail_image'] = $this->subjectDetailImageUrl;
        }

        try {
            if ($this->editId) {
                $subject = Subject::find($this->editId);
                $subject->update($subjectData);

                StandardSubject::updateOrCreate(
                    ['standard_id' => $this->selectedStandardForSubject, 'subject_id' => $subject->id],
                    ['organization_id' => Auth::user()->organization_id, 'is_mandatory' => $this->isMandatory]
                );

                SectionSubject::where('subject_id', $subject->id)
                    ->where('standard_id', $this->selectedStandardForSubject)->delete();

                foreach ($this->selectedSectionsForSubject as $sectionId) {
                    SectionSubject::create([
                        'section_id'      => $sectionId,
                        'subject_id'      => $subject->id,
                        'standard_id'     => $this->selectedStandardForSubject,
                        'organization_id' => Auth::user()->organization_id,
                    ]);
                }
                $this->notification()->success('Subject updated successfully!');
            } else {
                $subject = Subject::create($subjectData);

                StandardSubject::create([
                    'standard_id'     => $this->selectedStandardForSubject,
                    'subject_id'      => $subject->id,
                    'organization_id' => Auth::user()->organization_id,
                    'is_mandatory'    => $this->isMandatory,
                ]);

                foreach ($this->selectedSectionsForSubject as $sectionId) {
                    SectionSubject::create([
                        'section_id'      => $sectionId,
                        'subject_id'      => $subject->id,
                        'standard_id'     => $this->selectedStandardForSubject,
                        'organization_id' => Auth::user()->organization_id,
                    ]);
                }
                $this->notification()->success('Subject created successfully!');
            }

            $this->closeModal();
            $this->activeTab = 'subject';
            $this->mount();
        } catch (\Exception $e) {
            $this->notification()->error('Error!', 'Failed to save subject: ' . $e->getMessage());
        }
    }

    public function editStandard(int $id): void
    {
        $s = StudentStandard::find($id);
        if ($s) {
            $this->editId         = $id;
            $this->standardName   = $s->name;
            $this->standardCode   = $s->code;
            $this->standardBoard  = $s->board ?: $this->resolveOrgBoard();
            $this->standardOrder  = $s->order;
            $this->standardActive = $s->is_active;
            $this->openStandard   = true;
        }
    }

    public function editSection(int $id): void
    {
        $s = Section::find($id);
        if ($s) {
            $this->editId             = $id;
            $this->sectionName        = $s->name;
            $this->sectionCode        = $s->code;
            $this->sectionDescription = $s->description;
            $this->selectedStandard   = $s->standard_id;
            $this->sectionActive      = $s->is_active;
            $this->openSection        = true;
        }
    }

    public function editSubject(int $id): void
    {
        $subject = Subject::with(['standards', 'sections'])->find($id);
        if (!$subject) return;

        $this->editId               = $id;
        $this->subjectName          = $subject->name;
        $this->subjectCode          = $subject->code;
        $this->subjectDescription   = $subject->description;
        $this->subjectActive        = $subject->is_active;
        $this->subjectImageUrl      = $subject->image;
        $this->subjectDetailImageUrl = $subject->detail_image;
        $this->subjectImagePreview  = $subject->image;
        $this->subjectDetailImagePreview = $subject->detail_image;

        $ss = StandardSubject::where('subject_id', $id)->first();
        if ($ss) {
            $this->selectedStandardForSubject = $ss->standard_id;
            $this->isMandatory                = $ss->is_mandatory;
            $this->loadSectionsForSelectedStandard();
            $this->selectedSectionsForSubject = SectionSubject::where('subject_id', $id)
                ->where('standard_id', $ss->standard_id)->pluck('section_id')->toArray();
            $this->loadExistingSubjectsForStandard();
        }
        $this->openSubject = true;
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
        $standard = StudentStandard::find($id);
        if (!$standard) return;

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

        $this->notification()->success('Class deleted successfully!');
        $this->mount();
        $this->dispatch('onStandardAddUpdate');
    }

    public function performDeleteSection(int $id): void
    {
        $section = Section::find($id);
        if (!$section) return;

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

        $this->notification()->success('Section and its subjects deleted successfully!');
        $this->mount();
        $this->dispatch('onStandardAddUpdate');
    }

    /** Delete an S3 object without ever throwing (missing creds / object). */
    private function safeS3Delete(?string $url): void
    {
        if (!$url) return;
        try {
            Storage::disk('s3')->delete(parse_url($url, PHP_URL_PATH));
        } catch (\Throwable $e) {
            logger()->warning('safeS3Delete failed: ' . $e->getMessage());
        }
    }

    public function performDeleteSubject(int $id): void
    {
        try {
            if (\App\Models\Admin\TeacherTimeTable::where('subject_id', $id)->exists()
                || TeacherAssignment::where('subject_id', $id)->exists()) {
                $this->notification()->warning('Cannot Delete!', 'This subject is used in timetable or assignments.');
                return;
            }

            StandardSubject::where('subject_id', $id)->delete();
            SectionSubject::where('subject_id', $id)->delete();

            $subject = Subject::find($id);
            if ($subject) {
                if ($subject->image)        Storage::disk('s3')->delete(str_replace(Storage::disk('s3')->url(''), '', $subject->image));
                if ($subject->detail_image) Storage::disk('s3')->delete(str_replace(Storage::disk('s3')->url(''), '', $subject->detail_image));
                $subject->delete();
            }

            $this->notification()->success('Subject deleted successfully!');
            $this->mount();
        } catch (\Exception $e) {
            $this->notification()->error('Failed to delete subject: ' . $e->getMessage());
        }
    }

    public function onViewStandardAdmin(int $id): void
    {
        $s = StudentStandard::withCount(['sections', 'subjects'])->find($id);
        if (!$s) { $this->notification()->error('Class not found!'); return; }

        $this->viewModalTitle = 'Class Details';
        $this->viewData = [
            'name'           => $s->name,
            'code'           => $s->code,
            'board'          => $s->board,
            'order'          => $s->order,
            'is_active'      => $s->is_active ? 'Active' : 'Inactive',
            'sections_count' => $s->sections_count,
            'subjects_count' => $s->subjects_count,
            'created_at'     => $s->created_at->format('d M Y, h:i A'),
        ];
        $this->showViewModal = true;
    }

    public function onViewSectionAdmin(int $id): void
    {
        $s = Section::with(['standard', 'subjects'])->find($id);
        if (!$s) { $this->notification()->error('Section not found!'); return; }

        $this->viewModalTitle = 'Section Details';
        $this->viewData = [
            'name'           => $s->name,
            'code'           => $s->code,
            'description'    => $s->description,
            'class'          => $s->standard->name,
            'is_active'      => $s->is_active ? 'Active' : 'Inactive',
            'subjects_count' => $s->subjects->count(),
            'created_at'     => $s->created_at->format('d M Y, h:i A'),
        ];
        $this->showViewModal = true;
    }

    public function onViewSubjectAdmin(int $id): void
    {
        $s = Subject::with(['standards', 'sections'])->find($id);
        if (!$s) { $this->notification()->error('Subject not found!'); return; }

        $standard = $s->standards->first();
        $this->viewModalTitle = 'Subject Details';
        $this->viewData = [
            'name'         => $s->name,
            'code'         => $s->code,
            'description'  => $s->description,
            'is_active'    => $s->is_active ? 'Active' : 'Inactive',
            'image'        => $s->image,
            'detail_image' => $s->detail_image,
            'class'        => $standard?->name ?? 'Not assigned',
            'is_mandatory' => $standard ? ($standard->pivot?->is_mandatory ? 'Yes' : 'No') : 'N/A',
            'sections'     => $s->sections->pluck('name')->implode(', '),
            'created_at'   => $s->created_at->format('d M Y, h:i A'),
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
        return view('livewire.admin.standard', [
            'filteredStandards' => $this->filteredStandards,
            'filteredSections'  => $this->filteredSections,
            'filteredSubjects'  => $this->filteredSubjects,
            'allStandards'      => $this->standards,
            'availableSections' => $this->availableSections,
        ]);
    }
}
