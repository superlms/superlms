<?php

namespace App\Livewire\Admin;

use App\Models\Admin\HomeWork as ModalHomework;
use App\Models\Organization;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use App\Models\Student\Subject;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;
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
    
    // Form fields
    public $title = '';
    public $standard_id = '';
    public $section_id = '';
    public $subject_id = '';
    public $description = '';
    public $homework_file;
    public $is_active = true;
    public $subject_selection = 'single'; // 'single' or 'all'
    
    // Temporary URLs for preview
    public $tempFileUrl = null;
    
    // Dropdown data
    public $standards = [];
    public $sections = [];
    public $subjects = [];
    
    // View modal data
    public $viewModalTitle = '';
    public $viewHomework = null;

    // Teachers dropdown
    public $teachers = [];

    // Table filters
    #[Url]
    public $search = '';

    #[Url]
    public $perPage = 10;

    #[Url]
    public $filterTeacher = '';

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
        $organizationId = Auth::user()->organization_id;

        if ($sectionId) {
            $this->filterSubjects = Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $sectionId)
                ->where('section_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $organizationId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        } else {
            $this->filterSubjects = Subject::join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
                ->where('standard_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $organizationId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        }
    }

    public function updated($property, $value)
    {
        // Reset pagination when filters change
        if (in_array($property, ['search', 'filterTeacher', 'filterStandard', 'filterSection', 'filterSubject', 'filterStatus'])) {
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

        // Handle subject selection type change in form
        if ($property === 'subject_selection') {
            if ($value === 'all') {
                $this->subject_id = ''; // Reset single subject selection
            }
        }
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
    }

    public function updatedSectionId($value)
    {
        $this->subject_id = '';
        
        if ($value && $this->standard_id) {
            $this->loadSubjectsForStandard($this->standard_id, $value);
        } elseif (!$value && $this->standard_id) {
            $this->loadSubjectsForStandard($this->standard_id);
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
            'homework_file' => 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png|max:10240',
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
        $this->validate([
            'title' => 'required|string|max:255',
            'standard_id' => 'required|exists:standards,id',
            'section_id' => 'nullable|exists:sections,id',
            'description' => 'required|string',
            'subject_selection' => 'required|in:single,all',
            'subject_id' => $this->subject_selection === 'single' ? 'required|exists:subjects,id' : 'nullable',
            'homework_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png|max:10240',
        ]);

        try {
            $data = [
                'title' => $this->title,
                'standard_id' => $this->standard_id,
                'section_id' => $this->section_id ?: null,
                'subject_id' => $this->subject_selection === 'all' ? null : $this->subject_id,
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
        } catch (\Exception $e) {
            $this->notification()->error(
                'Error Saving Homework',
                $e->getMessage()
            );
            logger()->error('Homework save error: ' . $e->getMessage());
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
        $this->section_id = $homework->section_id;
        $this->subject_id = $homework->subject_id;
        $this->description = $homework->description;
        $this->subject_selection = $homework->subject_id ? 'single' : 'all';

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
        $this->dialog()->confirm([
            'title' => 'Are you Sure?',
            'icon' => 'exclamation-circle',
            'iconColor' => 'text-red-500',
            'description' => 'Are you sure you want to delete this homework? The action cannot be undone.',
            'accept' => [
                'label' => 'Yes, delete it',
                'method' => 'doDeleteHomework',
                'params' => $id,
                'color' => 'negative',
                'size' => 'md',
            ],
            'reject' => [
                'label' => 'No',
                'size' => 'md',
            ],
        ]);
    }

    public function doDeleteHomework($id)
    {
        try {
            $homework = ModalHomework::findOrFail($id);

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
        $this->reset(['search', 'filterTeacher', 'filterStandard', 'filterSection', 'filterSubject']);
        $this->filterSections = [];
        $this->filterSubjects = [];
        $this->resetPage();
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
        $homeworks = $this->getHomeworks();
        
        // Statistics
        $statistics = $this->getStatistics();
        
        return view('livewire.admin.homework', compact('homeworks', 'statistics'));
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