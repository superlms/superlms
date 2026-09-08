<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy as ModelsExamCopy;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Url;
use WireUi\Traits\WireUiActions;

class ExamCopy extends Component
{
    use WireUiActions, WithPagination, WithFileUploads;

    // ─── Upload Copies modal (replaces former 'upload' tab) ─────────────────
    public bool $showUploadModal = false;
    public string $uploadExam     = '';
    public string $uploadStandard = '';
    public string $uploadSection  = '';
    public string $uploadSubject  = '';
    public array  $studentPdfs    = [];
    public array  $uploadedFiles  = [];

    // ─── Edit / upload single exam copy modal ───────────────────────────────
    public bool $showEditModal  = false;
    public string $editMode     = 'edit';   // 'edit' | 'upload'
    public $editCopyId          = null;
    public $editCopyMeta        = [];
    public $editPdf             = null;
    public string $editRemarks  = '';

    // ─── Delete confirm overlay (list row) ──────────────────────────────────
    public bool $showDeleteConfirm = false;
    public $pendingDeleteId        = null;

    // ─── Delete confirm overlay (upload panel row) ──────────────────────────
    public bool $showUploadDeleteConfirm = false;
    public $pendingUploadDeleteStudent   = null;

    // ─── Filters ────────────────────────────────────────────────────────────
    #[Url] public string $search         = '';
    #[Url] public int    $perPage        = 10;
    #[Url] public string $filterExam     = '';
    #[Url] public string $filterStandard = '';
    #[Url] public string $filterSection  = '';
    #[Url] public string $filterSubject  = '';
    #[Url] public string $filterStudent  = '';

    // ─── Stats ──────────────────────────────────────────────────────────────
    public int $totalExamCopies = 0;
    public int $totalStudents   = 0;
    public int $uploadedCopies  = 0;
    public int $pendingUploads  = 0;

    // ─── Dropdown data: shared ──────────────────────────────────────────────
    public $exams;
    public $standards;

    // ─── Dropdown data: upload panel ────────────────────────────────────────
    public $sections;
    public $subjects;
    public $students;

    // ─── Dropdown data: filter bar (kept separate so opening the upload
    //     panel can't rewrite what the filter bar is showing) ───────────────
    public $filterSections;
    public $filterSubjects;
    public $filterStudents;

    public function mount(): void
    {
        $this->loadFilters();
        $this->loadStatistics();

        if ($this->filterStandard) {
            $this->filterSections = Section::where('standard_id', $this->filterStandard)
                ->where('is_active', true)->get();
            $this->loadSubjectsForStandard($this->filterStandard, $this->filterSection ?: null);
        }
        if ($this->filterStandard && $this->filterSection) {
            $this->loadFilterStudents();
        }
    }

    public function loadFilters(): void
    {
        $orgId = Auth::user()->organization_id;

        // Match the Exam admin page order: by start_date ASC (nulls last), then id ASC.
        $this->exams = Exam::where('organization_id', $orgId)
            ->where('is_published', true)
            ->orderByRaw('start_date IS NULL, start_date ASC')
            ->orderBy('id', 'asc')
            ->get(['id', 'exam_name', 'academic_year', 'start_date']);

        $this->standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name']);

        $this->filterSubjects = Subject::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name']);

        $this->sections       = collect();
        $this->subjects       = collect();
        $this->students       = collect();
        $this->filterSections = collect();
        $this->filterStudents = collect();
    }

    private function loadFilterStudents(): void
    {
        if (!$this->filterStandard || !$this->filterSection) {
            $this->filterStudents = collect();
            return;
        }

        $this->filterStudents = StudentDetail::where('standard_id', $this->filterStandard)
            ->where('section_id', $this->filterSection)
            ->with('user:id,name')
            ->orderBy('full_name')
            ->orderBy('roll_no')
            ->get(['id', 'user_id', 'roll_no', 'admission_no', 'full_name']);
    }

    public function loadStatistics(): void
    {
        $orgId = Auth::user()->organization_id;
        $q = ModelsExamCopy::where('organization_id', $orgId);

        if ($this->filterExam)     $q->where('exam_id', $this->filterExam);
        if ($this->filterStandard) $q->where('standard_id', $this->filterStandard);
        if ($this->filterSection)  $q->where('section_id', $this->filterSection);
        if ($this->filterSubject)  $q->where('subject_id', $this->filterSubject);
        if ($this->filterStudent)  $q->where('student_detail_id', $this->filterStudent);

        $this->totalExamCopies = (clone $q)->count();
        $this->totalStudents   = (clone $q)
            ->whereNotNull('student_detail_id')
            ->distinct('student_detail_id')
            ->count('student_detail_id');
        $this->uploadedCopies  = (clone $q)->whereNotNull('pdf_path')->count();
        $this->pendingUploads  = (clone $q)->whereNull('pdf_path')->count();
    }

    // ═══════════════════════════════════════════════════════════════
    //  FILTER CASCADES
    // ═══════════════════════════════════════════════════════════════

    public function updatedFilterStandard($value): void
    {
        $this->filterSection = '';
        $this->filterSubject = '';
        $this->filterStudent = '';
        $this->filterStudents = collect();
        $this->resetPage();

        if ($value) {
            $this->filterSections = Section::where('standard_id', $value)
                ->where('is_active', true)->get();
            $this->loadSubjectsForStandard($value);
        } else {
            $this->filterSections = collect();
            $this->loadFilters();
        }
        $this->loadStatistics();
    }

    public function updatedFilterSection($value): void
    {
        $this->filterSubject = '';
        $this->filterStudent = '';
        $this->resetPage();

        if ($value && $this->filterStandard) {
            $this->loadSubjectsForStandard($this->filterStandard, $value);
        } elseif ($this->filterStandard) {
            $this->loadSubjectsForStandard($this->filterStandard);
        }
        $this->loadFilterStudents();
        $this->loadStatistics();
    }

    public function updatedFilterSubject(): void { $this->resetPage(); $this->loadStatistics(); }
    public function updatedFilterStudent(): void { $this->resetPage(); $this->loadStatistics(); }
    public function updatedFilterExam(): void    { $this->resetPage(); $this->loadStatistics(); }
    public function updatedSearch(): void        { $this->resetPage(); }

    public function clearSubjectFilters(): void
    {
        $this->reset(['search', 'filterExam', 'filterStandard', 'filterSection', 'filterSubject', 'filterStudent']);
        $this->resetPage();
        $this->loadFilters();
        $this->loadStatistics();
    }

    // ═══════════════════════════════════════════════════════════════
    //  UPLOAD COPIES modal
    // ═══════════════════════════════════════════════════════════════

    public function openUploadModal(): void
    {
        $this->reset(['uploadExam', 'uploadStandard', 'uploadSection', 'uploadSubject', 'studentPdfs', 'uploadedFiles']);
        $this->sections = collect();
        $this->subjects = collect();
        $this->students = collect();
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->showUploadDeleteConfirm = false;
        $this->pendingUploadDeleteStudent = null;
        $this->reset(['uploadExam', 'uploadStandard', 'uploadSection', 'uploadSubject', 'studentPdfs', 'uploadedFiles']);
        $this->sections = collect();
        $this->subjects = collect();
        $this->students = collect();
    }

    public function updatedUploadStandard($value): void
    {
        $this->uploadSection = '';
        $this->uploadSubject = '';
        $this->students      = collect();
        $this->resetStudentPdfs();

        if ($value) {
            $this->sections = Section::where('standard_id', $value)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
            $this->loadSubjectsWithMarks();
        } else {
            $this->sections = collect();
        }
    }

    public function updatedUploadSection($value): void
    {
        $this->uploadSubject = '';
        $this->resetStudentPdfs();

        if ($value && $this->uploadStandard) {
            $this->students = StudentDetail::where('standard_id', $this->uploadStandard)
                ->where('section_id', $value)
                ->with('user:id,name')
                ->orderBy('full_name')
                ->orderBy('roll_no')
                ->get(['id', 'user_id', 'roll_no', 'admission_no', 'image', 'full_name']);
            $this->loadSubjectsWithMarks();
        } else {
            $this->students = collect();
        }
    }

    public function updatedUploadSubject(): void { $this->loadStudentPdfs(); }
    public function updatedUploadExam(): void
    {
        $this->uploadSubject = '';
        $this->resetStudentPdfs();
        $this->loadSubjectsWithMarks();
        $this->loadStudentPdfs();
    }

    /**
     * Subject dropdown for the Upload modal is restricted to subjects where
     * marks have already been uploaded (via Performance) for the chosen
     * (exam, class, section). This enforces "only upload PDFs for subjects
     * whose marks exist".
     */
    private function loadSubjectsWithMarks(): void
    {
        if (!$this->uploadExam || !$this->uploadStandard || !$this->uploadSection) {
            $this->subjects = collect();
            return;
        }

        $orgId = Auth::user()->organization_id;

        $this->subjects = Subject::query()
            ->join('exam_copies', 'subjects.id', '=', 'exam_copies.subject_id')
            ->where('exam_copies.organization_id', $orgId)
            ->where('exam_copies.exam_id',         $this->uploadExam)
            ->where('exam_copies.standard_id',     $this->uploadStandard)
            ->where('exam_copies.section_id',      $this->uploadSection)
            ->whereNotNull('exam_copies.marks_obtained')
            ->where('subjects.organization_id', $orgId)
            ->where('subjects.is_active', true)
            ->select('subjects.id', 'subjects.name')
            ->distinct()
            ->orderBy('subjects.name')
            ->get();
    }

    private function loadStudentPdfs(): void
    {
        $this->studentPdfs   = [];
        $this->uploadedFiles = [];

        if (!$this->uploadExam || !$this->uploadStandard || !$this->uploadSection || !$this->uploadSubject) {
            return;
        }

        $standardName = optional($this->standards->firstWhere('id', (int) $this->uploadStandard))->name;
        $sectionName  = optional($this->sections->firstWhere('id', (int) $this->uploadSection))->name;
        $subjectName  = optional($this->subjects->firstWhere('id', (int) $this->uploadSubject))->name;

        foreach ($this->students as $student) {
            $existing = ModelsExamCopy::where('exam_id', $this->uploadExam)
                ->where('standard_id', $this->uploadStandard)
                ->where('section_id', $this->uploadSection)
                ->where('subject_id', $this->uploadSubject)
                ->where('student_detail_id', $student->id)
                ->first();

            $this->studentPdfs[$student->id] = [
                'copy_id'       => $existing ? $existing->id : null,
                'student_id'    => $student->id,
                'student_name'  => $student->user->name ?? $student->full_name ?? '—',
                'student_image' => $student->image ?? null,
                'roll_no'       => $student->roll_no,
                'admission_no'  => $student->admission_no,
                'standard_name' => $standardName,
                'section_name'  => $sectionName,
                'subject_name'  => $subjectName,
                'pdf_path'      => $existing?->pdf_path,
                'remarks'       => $existing?->remarks ?? '',
                'has_pdf'       => $existing && $existing->pdf_path,
            ];
        }
    }

    private function resetStudentPdfs(): void
    {
        $this->studentPdfs   = [];
        $this->uploadedFiles = [];
    }

    private function loadSubjectsForStandard($standardId, $sectionId = null): void
    {
        $orgId = Auth::user()->organization_id;

        if ($sectionId) {
            $this->filterSubjects = Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $sectionId)
                ->where('section_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $orgId)
                ->where('subjects.is_active', true)
                ->select('subjects.id', 'subjects.name')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        } else {
            $this->filterSubjects = Subject::join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
                ->where('standard_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $orgId)
                ->where('subjects.is_active', true)
                ->select('subjects.id', 'subjects.name')
                ->distinct()
                ->orderBy('subjects.name')
                ->get();
        }
    }

    /**
     * One-shot bulk save: validates everything up front, then writes all rows
     * inside a single DB transaction. If any single row fails, the whole
     * batch rolls back so the admin doesn't end up with half-saved state.
     */
    public function uploadPdfs(): void
    {
        $this->validate([
            'uploadExam'     => 'required',
            'uploadStandard' => 'required',
            'uploadSection'  => 'required',
            'uploadSubject'  => 'required',
        ], [
            'uploadExam.required'     => 'Please select an exam.',
            'uploadStandard.required' => 'Please select a class.',
            'uploadSection.required'  => 'Please select a section.',
            'uploadSubject.required'  => 'Please select a subject.',
        ]);

        $fileRules = [];
        foreach ($this->uploadedFiles as $studentId => $file) {
            if ($file) {
                $fileRules["uploadedFiles.$studentId"] = 'file|mimes:pdf|max:5120';
            }
        }
        if (!empty($fileRules)) $this->validate($fileRules);

        // Anything to save?
        $newFiles      = array_filter($this->uploadedFiles, fn($f) => (bool) $f);
        $remarksOnly   = false;
        foreach ($this->studentPdfs as $sid => $sp) {
            if (!empty($sp['copy_id']) && ($sp['remarks'] ?? '') !== '') {
                $remarksOnly = true;
                break;
            }
        }
        if (empty($newFiles) && !$remarksOnly) {
            $this->notification()->warning('Nothing to upload', 'Pick at least one PDF (or update a remark) before saving.');
            return;
        }

        try {
            $savedCount  = 0;
            $uploadedNew = [];      // paths uploaded to S3 in this pass (for rollback cleanup)
            $orgId       = Auth::user()->organization_id;

            // Upload all PDFs to S3 FIRST so we have the keys ready. If S3 fails
            // for any file, we abort before touching the DB.
            $pathByStudent = [];
            foreach ($newFiles as $studentId => $file) {
                $path = $file->store('admin/exam-copies', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $pathByStudent[$studentId] = $path;
                $uploadedNew[]             = $path;
            }

            // One DB transaction for all upserts.
            DB::transaction(function () use (&$savedCount, $pathByStudent, $orgId) {
                foreach ($this->studentPdfs as $studentId => $sp) {
                    $hasNewPath = isset($pathByStudent[$studentId]);
                    $hasRemark  = !empty($sp['copy_id']) && ($sp['remarks'] ?? '') !== '';
                    if (!$hasNewPath && !$hasRemark) continue;

                    $payload = [
                        'organization_id' => $orgId,
                        'uploaded_by'     => Auth::id(),
                        'remarks'         => $sp['remarks'] ?? '',
                    ];
                    if ($hasNewPath) $payload['pdf_path'] = $pathByStudent[$studentId];

                    // If replacing an existing PDF, delete the old S3 object.
                    $existing = ModelsExamCopy::where('exam_id', $this->uploadExam)
                        ->where('standard_id', $this->uploadStandard)
                        ->where('section_id', $this->uploadSection)
                        ->where('subject_id', $this->uploadSubject)
                        ->where('student_detail_id', $studentId)
                        ->first();
                    if ($hasNewPath && $existing && $existing->pdf_path) {
                        Storage::disk('s3')->delete($existing->pdf_path);
                    }

                    ModelsExamCopy::updateOrCreate(
                        [
                            'exam_id'           => $this->uploadExam,
                            'standard_id'       => $this->uploadStandard,
                            'section_id'        => $this->uploadSection,
                            'subject_id'        => $this->uploadSubject,
                            'student_detail_id' => $studentId,
                        ],
                        $payload
                    );
                    $savedCount++;
                }
            });

            // Saving lands the admin back on the Exam Copies home screen (the
            // list) rather than leaving them inside the upload panel.
            $examId     = $this->uploadExam;
            $standardId = $this->uploadStandard;
            $sectionId  = $this->uploadSection;

            $this->closeUploadModal();

            // Pre-load the list with exactly what was just uploaded, so the home
            // screen isn't empty (the list only renders once filtered).
            $this->filterExam     = (string) $examId;
            $this->filterStandard = (string) $standardId;
            $this->filterSection  = (string) $sectionId;
            $this->filterSubject  = '';
            $this->filterStudent  = '';
            $this->search         = '';

            $this->filterSections = Section::where('standard_id', $standardId)
                ->where('is_active', true)->get();
            $this->loadSubjectsForStandard($standardId, $sectionId);
            $this->loadFilterStudents();
            $this->resetPage();
            $this->loadStatistics();

            $this->notification()->success('Uploaded', "{$savedCount} record(s) saved in one go.");
        } catch (\Throwable $e) {
            // Clean up any S3 uploads on failure so we don't orphan files.
            foreach ($uploadedNew ?? [] as $path) {
                try { Storage::disk('s3')->delete($path); } catch (\Throwable $ignore) {}
            }
            logger()->error('ExamCopy uploadPdfs: ' . $e->getMessage());
            $this->notification()->error('Upload failed', $e->getMessage());
        }
    }

    /** Ask before removing an already-uploaded PDF from the upload panel. */
    public function askDeletePdfInUpload(int $studentId): void
    {
        $this->pendingUploadDeleteStudent = $studentId;
        $this->showUploadDeleteConfirm    = true;
    }

    public function cancelUploadDelete(): void
    {
        $this->showUploadDeleteConfirm    = false;
        $this->pendingUploadDeleteStudent = null;
    }

    public function confirmDeletePdfInUpload(): void
    {
        $studentId = $this->pendingUploadDeleteStudent;

        $this->showUploadDeleteConfirm    = false;
        $this->pendingUploadDeleteStudent = null;

        if ($studentId) {
            $this->deletePdfInUpload((int) $studentId);
        }
    }

    public function deletePdfInUpload(int $studentId): void
    {
        $copy = ModelsExamCopy::where('exam_id', $this->uploadExam)
            ->where('standard_id', $this->uploadStandard)
            ->where('section_id', $this->uploadSection)
            ->where('subject_id', $this->uploadSubject)
            ->where('student_detail_id', $studentId)
            ->first();

        if ($copy && $copy->pdf_path) {
            Storage::disk('s3')->delete($copy->pdf_path);
            $copy->pdf_path = null;
            $copy->save();
            $this->notification()->success('PDF removed.');
            $this->loadStudentPdfs();
            $this->loadStatistics();
        }
    }

    public function viewPdfInUpload(int $studentId): mixed
    {
        $copy = ModelsExamCopy::where('exam_id', $this->uploadExam)
            ->where('standard_id', $this->uploadStandard)
            ->where('section_id', $this->uploadSection)
            ->where('subject_id', $this->uploadSubject)
            ->where('student_detail_id', $studentId)
            ->first();

        if (!$copy || !$copy->pdf_path) {
            $this->notification()->error('PDF not found.');
            return null;
        }
        $this->dispatch('open-in-new-tab', url: Storage::disk('s3')->url($copy->pdf_path));
        return null;
    }

    // ═══════════════════════════════════════════════════════════════
    //  EDIT single copy
    // ═══════════════════════════════════════════════════════════════

    /** Same panel as Edit, but framed as the first upload for a pending copy. */
    public function openUploadForRow(int $id): void
    {
        $this->openEditModal($id, 'upload');
    }

    public function openEditModal(int $id, string $mode = 'edit'): void
    {
        $copy = ModelsExamCopy::with([
                'exam:id,exam_name',
                'standard:id,name',
                'section:id,name',
                'subject:id,name',
                'studentDetail:id,user_id,roll_no,admission_no',
                'studentDetail.user:id,name',
            ])->find($id);

        if (!$copy) return;

        $this->editCopyId = $id;
        $this->editMode   = $copy->pdf_path ? 'edit' : $mode;
        $this->editCopyMeta = [
            'student_name'  => $copy->studentDetail->user->name ?? '—',
            'admission_no'  => $copy->studentDetail->admission_no ?? '—',
            'standard_name' => $copy->standard->name ?? '—',
            'section_name'  => $copy->section->name ?? '—',
            'subject_name'  => $copy->subject->name ?? '—',
            'exam_name'     => $copy->exam->exam_name ?? '—',
            'pdf_path'      => $copy->pdf_path,
            'pdf_url'       => $copy->pdf_path ? Storage::disk('s3')->url($copy->pdf_path) : null,
        ];
        $this->editRemarks = (string) ($copy->remarks ?? '');
        $this->editPdf     = null;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editMode      = 'edit';
        $this->editCopyId    = null;
        $this->editCopyMeta  = [];
        $this->editRemarks   = '';
        $this->editPdf       = null;
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editRemarks' => 'nullable|string|max:1000',
            'editPdf'     => ($this->editMode === 'upload' ? 'required' : 'nullable') . '|file|mimes:pdf|max:5120',
        ], [
            'editPdf.required' => 'Please choose a PDF to upload.',
        ]);

        try {
            $copy = ModelsExamCopy::find($this->editCopyId);
            if (!$copy) return;

            $data = ['remarks' => $this->editRemarks ?: null];

            if ($this->editPdf) {
                if ($copy->pdf_path) {
                    Storage::disk('s3')->delete($copy->pdf_path);
                }
                $path = $this->editPdf->store('admin/exam-copies', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $data['pdf_path']    = $path;
                $data['uploaded_by'] = Auth::id();
            }

            $wasUpload = $this->editMode === 'upload';
            $copy->update($data);
            $this->notification()->success(
                $wasUpload ? 'Uploaded' : 'Updated',
                $wasUpload ? 'Exam copy uploaded successfully.' : 'Exam copy updated successfully.'
            );
            $this->closeEditModal();
            $this->loadStatistics();
        } catch (\Exception $e) {
            $this->notification()->error('Update failed', $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  DELETE (custom overlay)
    // ═══════════════════════════════════════════════════════════════

    public function onDelete(int $id): void
    {
        $this->pendingDeleteId    = $id;
        $this->showDeleteConfirm  = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->pendingDeleteId   = null;
    }

    public function confirmDelete(): void
    {
        try {
            $copy = ModelsExamCopy::find($this->pendingDeleteId);
            if ($copy) {
                if ($copy->pdf_path) {
                    Storage::disk('s3')->delete($copy->pdf_path);
                }
                $copy->delete();
                $this->notification()->success('Deleted', 'Exam copy deleted successfully.');
                $this->loadStatistics();
            }
        } catch (\Exception $e) {
            $this->notification()->error('Delete failed', $e->getMessage());
        }

        $this->showDeleteConfirm = false;
        $this->pendingDeleteId   = null;
    }

    // ═══════════════════════════════════════════════════════════════
    //  Helpers — PDF URL
    // ═══════════════════════════════════════════════════════════════

    public function getPdfUrl(?string $path): ?string
    {
        if (!$path) return null;
        return Storage::disk('s3')->url($path);
    }

    public function render()
    {
        $examCopies     = $this->getExamCopies();
        $filtersApplied = $this->filtersApplied;

        return view('livewire.admin.exam-copy', compact('examCopies', 'filtersApplied'));
    }

    /** True once enough filters are chosen for the list to render. */
    public function getFiltersAppliedProperty(): bool
    {
        return (bool) ($this->filterExam && $this->filterStandard && $this->filterSection);
    }

    private function getExamCopies()
    {
        // Nothing is listed until exam → class → section have been picked.
        if (!$this->filtersApplied) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        }

        $orgId = Auth::user()->organization_id;

        $query = ModelsExamCopy::with([
                'exam:id,exam_name',
                'standard:id,name',
                'section:id,name',
                'subject:id,name',
                'studentDetail:id,user_id,roll_no,admission_no,image,full_name',
                'studentDetail.user:id,name',
            ])
            ->where('organization_id', $orgId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('studentDetail.user', fn($u) => $u->where('name', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('studentDetail', fn($s) => $s->where('admission_no', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('subject', fn($s) => $s->where('name', 'like', '%' . $this->search . '%'));
            });
        }
        if ($this->filterExam)     $query->where('exam_id', $this->filterExam);
        if ($this->filterStandard) $query->where('standard_id', $this->filterStandard);
        if ($this->filterSection)  $query->where('section_id', $this->filterSection);
        if ($this->filterSubject)  $query->where('subject_id', $this->filterSubject);
        if ($this->filterStudent)  $query->where('student_detail_id', $this->filterStudent);

        return $query->orderByDesc('created_at')->paginate($this->perPage);
    }
}
