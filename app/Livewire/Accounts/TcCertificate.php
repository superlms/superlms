<?php

namespace App\Livewire\Accounts;

use App\Models\Admin\Certificate;
use App\Models\Admin\TransferCertificate;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class TcCertificate extends Component
{
    use WithPagination;

    // ─── Tabs: achievement | participation | tc ──────────────────────────────────
    public string $activeTab = 'achievement';

    // ─── Modals ─────────────────────────────────────────────────────────────────
    public bool $certModal    = false;
    public bool $tcModal      = false;
    public bool $showViewModal = false;

    public ?int   $editCertId  = null;
    public ?int   $editTcId    = null;
    public ?int   $viewId      = null;
    public string $viewType    = 'cert';
    public        $viewRecord  = null;

    // ─── Delete ─────────────────────────────────────────────────────────────────
    public ?int   $pendingDeleteId   = null;
    public string $pendingDeleteType = 'cert'; // cert | tc

    // ─── Certificate Form ────────────────────────────────────────────────────────
    public string $certType              = 'achievement';
    public ?int   $student_detail_id     = null;
    public string $event_name            = '';
    public string $issued_by             = '';
    public string $issued_by_designation = '';
    public string $description           = '';
    public string $issued_date           = '';

    // ─── TC Form ────────────────────────────────────────────────────────────────
    public ?int   $tc_student_id            = null;
    public string $book_no                  = '';
    public string $nationality              = 'Indian';
    public bool   $is_sc_st                 = false;
    public string $last_class_studied       = '';
    public string $exam_last_taken          = '';
    public string $whether_failed           = 'No';
    public string $subjects_studied         = '';
    public string $qualified_for_promotion  = 'Yes';
    public string $fees_paid_upto           = '';
    public string $fee_concession           = '';
    public int    $total_working_days       = 0;
    public int    $days_present             = 0;
    public string $is_ncc_scout             = 'No';
    public string $extra_activities         = '';
    public string $general_conduct          = 'Good';
    public string $application_date         = '';
    public string $tc_issue_date            = '';
    public string $reason_for_leaving       = '';
    public string $tc_remarks               = '';

    // ─── Filters ────────────────────────────────────────────────────────────────
    public string $search  = '';
    public int    $perPage = 10;
    public string $filterMonth   = '';   // YYYY-MM
    public string $filterClass   = '';
    public string $filterSection = '';

    // ─── Issue form: class/section + student picker ───────────
    public string $certClass         = '';
    public string $certSection       = '';
    public string $certStudentSearch = '';
    public string $tcClass           = '';
    public string $tcSection         = '';
    public string $tcStudentSearch   = '';

    // ─── Students List ───────────────────────────────────────────────────────────
    public array $students = [];

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function mount(): void
    {
        $this->issued_date      = now()->format('Y-m-d');
        $this->application_date = now()->format('Y-m-d');
        $this->tc_issue_date    = now()->format('Y-m-d');
        $this->loadStudents();
    }

    private function loadStudents(): void
    {
        $this->students = StudentDetail::where('organization_id', $this->orgId())
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'admission_no'])
            ->map(fn($s) => [
                'id'           => $s->id,
                'full_name'    => $s->full_name,
                'admission_no' => $s->admission_no,
            ])
            ->toArray();
    }

    // ─── Analytics & filter data ──────────────────────────────────────────────────

    #[Computed]
    public function analytics(): array
    {
        $orgId = $this->orgId();

        if ($this->activeTab === 'tc') {
            $base    = TransferCertificate::where('organization_id', $orgId);
            $dateCol = 'issue_date';
        } else {
            $base    = Certificate::where('organization_id', $orgId)->where('type', $this->activeTab);
            $dateCol = 'issued_date';
        }

        if ($this->filterClass) {
            $base->whereHas('student', fn($q) => $q->where('standard_id', $this->filterClass));
        }
        if ($this->filterSection) {
            $base->whereHas('student', fn($q) => $q->where('section_id', $this->filterSection));
        }

        $now     = now();
        $lastMon = $now->copy()->subMonthNoOverflow();

        return [
            'total'      => (clone $base)->count(),
            'this_month' => (clone $base)->whereYear($dateCol, $now->year)->whereMonth($dateCol, $now->month)->count(),
            'last_month' => (clone $base)->whereYear($dateCol, $lastMon->year)->whereMonth($dateCol, $lastMon->month)->count(),
            'this_week'  => (clone $base)->whereBetween($dateCol, [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->count(),
        ];
    }

    #[Computed]
    public function standards()
    {
        return Standard::where('organization_id', $this->orgId())->orderBy('id')->get();
    }

    private function sectionsFor(string $standardId)
    {
        if (!$standardId) return collect();
        return Section::where('organization_id', $this->orgId())
            ->where('standard_id', $standardId)->orderBy('id')->get();
    }

    #[Computed]
    public function filterSections() { return $this->sectionsFor($this->filterClass); }

    #[Computed]
    public function certSections() { return $this->sectionsFor($this->certClass); }

    #[Computed]
    public function tcSections() { return $this->sectionsFor($this->tcClass); }

    private function studentsFor(string $standardId, string $sectionId, string $search)
    {
        if (!$standardId) return collect();
        return StudentDetail::with(['standard', 'section'])
            ->where('organization_id', $this->orgId())
            ->where('standard_id', $standardId)
            ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
            ->when($search, fn($q) => $q->where(fn($s) =>
                $s->where('full_name', 'like', "%{$search}%")
                  ->orWhere('admission_no', 'like', "%{$search}%")))
            ->orderBy('full_name')->get();
    }

    #[Computed]
    public function certIssueStudents() { return $this->studentsFor($this->certClass, $this->certSection, $this->certStudentSearch); }

    #[Computed]
    public function tcIssueStudents() { return $this->studentsFor($this->tcClass, $this->tcSection, $this->tcStudentSearch); }

    // ─── Tab Switch ─────────────────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->search    = '';
        $this->resetPage();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterClass(): void   { $this->filterSection = ''; $this->resetPage(); }
    public function updatedFilterSection(): void  { $this->resetPage(); }
    public function updatedFilterMonth(): void    { $this->resetPage(); }
    public function updatedCertClass(): void      { $this->certSection = ''; $this->student_detail_id = null; }
    public function updatedTcClass(): void        { $this->tcSection = ''; $this->tc_student_id = null; }

    // ─── Certificate CRUD ────────────────────────────────────────────────────────

    public function openCertModal(?int $id = null): void
    {
        $this->resetCertForm();
        $this->editCertId = $id;

        if ($id) {
            $c = Certificate::where('organization_id', $this->orgId())->findOrFail($id);
            $this->certType              = $c->type;
            $this->student_detail_id     = $c->student_detail_id;
            $this->event_name            = $c->event_name;
            $this->issued_by             = $c->issued_by;
            $this->issued_by_designation = $c->issued_by_designation ?? '';
            $this->description           = $c->description ?? '';
            $this->issued_date           = $c->issued_date->format('Y-m-d');
        } else {
            $this->certType = in_array($this->activeTab, ['achievement', 'participation'])
                ? $this->activeTab : 'achievement';
        }

        $this->certModal = true;
    }

    public function saveCert(): void
    {
        $this->validate([
            'certType'              => 'required|in:achievement,participation',
            'student_detail_id'     => 'required|exists:student_details,id',
            'event_name'            => 'required|string|max:255',
            'issued_by'             => 'required|string|max:255',
            'issued_by_designation' => 'nullable|string|max:100',
            'description'           => 'nullable|string|max:1000',
            'issued_date'           => 'required|date',
        ]);

        $data = [
            'organization_id'       => $this->orgId(),
            'student_detail_id'     => $this->student_detail_id,
            'type'                  => $this->certType,
            'event_name'            => $this->event_name,
            'issued_by'             => $this->issued_by,
            'issued_by_designation' => $this->issued_by_designation ?: null,
            'description'           => $this->description ?: null,
            'issued_date'           => $this->issued_date,
        ];

        if ($this->editCertId) {
            Certificate::where('organization_id', $this->orgId())->findOrFail($this->editCertId)->update($data);
            session()->flash('success', 'Certificate updated successfully!');
        } else {
            Certificate::create($data);
            session()->flash('success', 'Certificate issued successfully!');
        }

        $this->closeCertModal();
    }

    public function closeCertModal(): void
    {
        $this->certModal  = false;
        $this->editCertId = null;
        $this->resetCertForm();
        $this->resetValidation();
    }

    private function resetCertForm(): void
    {
        $this->reset(['student_detail_id', 'event_name', 'issued_by', 'issued_by_designation', 'description',
            'certClass', 'certSection', 'certStudentSearch']);
        $this->certType    = in_array($this->activeTab, ['achievement', 'participation']) ? $this->activeTab : 'achievement';
        $this->issued_date = now()->format('Y-m-d');
    }

    // ─── TC CRUD ────────────────────────────────────────────────────────────────

    public function openTcModal(?int $id = null): void
    {
        $this->resetTcForm();
        $this->editTcId = $id;

        if ($id) {
            $tc = TransferCertificate::where('organization_id', $this->orgId())->findOrFail($id);
            $this->tc_student_id           = $tc->student_detail_id;
            $this->book_no                 = $tc->book_no ?? '';
            $this->nationality             = $tc->nationality;
            $this->is_sc_st                = $tc->is_sc_st;
            $this->last_class_studied      = $tc->last_class_studied ?? '';
            $this->exam_last_taken         = $tc->exam_last_taken ?? '';
            $this->whether_failed          = $tc->whether_failed;
            $this->subjects_studied        = $tc->subjects_studied ?? '';
            $this->qualified_for_promotion = $tc->qualified_for_promotion;
            $this->fees_paid_upto          = $tc->fees_paid_upto ?? '';
            $this->fee_concession          = $tc->fee_concession ?? '';
            $this->total_working_days      = $tc->total_working_days;
            $this->days_present            = $tc->days_present;
            $this->is_ncc_scout            = $tc->is_ncc_scout;
            $this->extra_activities        = $tc->extra_activities ?? '';
            $this->general_conduct         = $tc->general_conduct;
            $this->application_date        = $tc->application_date->format('Y-m-d');
            $this->tc_issue_date           = $tc->issue_date->format('Y-m-d');
            $this->reason_for_leaving      = $tc->reason_for_leaving ?? '';
            $this->tc_remarks              = $tc->remarks ?? '';
        }

        $this->tcModal = true;
    }

    public function saveTc(): void
    {
        $this->validate([
            'tc_student_id'    => 'required|exists:student_details,id',
            'application_date' => 'required|date',
            'tc_issue_date'    => 'required|date',
            'general_conduct'  => 'required|string',
        ]);

        $data = [
            'organization_id'         => $this->orgId(),
            'student_detail_id'       => $this->tc_student_id,
            'book_no'                 => $this->book_no ?: null,
            'nationality'             => $this->nationality,
            'is_sc_st'                => $this->is_sc_st,
            'last_class_studied'      => $this->last_class_studied ?: null,
            'exam_last_taken'         => $this->exam_last_taken ?: null,
            'whether_failed'          => $this->whether_failed,
            'subjects_studied'        => $this->subjects_studied ?: null,
            'qualified_for_promotion' => $this->qualified_for_promotion,
            'fees_paid_upto'          => $this->fees_paid_upto ?: null,
            'fee_concession'          => $this->fee_concession ?: null,
            'total_working_days'      => $this->total_working_days,
            'days_present'            => $this->days_present,
            'is_ncc_scout'            => $this->is_ncc_scout,
            'extra_activities'        => $this->extra_activities ?: null,
            'general_conduct'         => $this->general_conduct,
            'application_date'        => $this->application_date,
            'issue_date'              => $this->tc_issue_date,
            'reason_for_leaving'      => $this->reason_for_leaving ?: null,
            'remarks'                 => $this->tc_remarks ?: null,
        ];

        if ($this->editTcId) {
            TransferCertificate::where('organization_id', $this->orgId())->findOrFail($this->editTcId)->update($data);
            session()->flash('success', 'Transfer Certificate updated!');
        } else {
            TransferCertificate::create($data);
            session()->flash('success', 'Transfer Certificate issued!');
        }

        $this->closeTcModal();
    }

    public function closeTcModal(): void
    {
        $this->tcModal  = false;
        $this->editTcId = null;
        $this->resetTcForm();
        $this->resetValidation();
    }

    private function resetTcForm(): void
    {
        $this->reset(['tc_student_id', 'tcClass', 'tcSection', 'tcStudentSearch',
            'book_no', 'last_class_studied', 'exam_last_taken',
            'subjects_studied', 'fees_paid_upto', 'fee_concession', 'extra_activities',
            'reason_for_leaving', 'tc_remarks']);
        $this->nationality             = 'Indian';
        $this->is_sc_st                = false;
        $this->whether_failed          = 'No';
        $this->qualified_for_promotion = 'Yes';
        $this->total_working_days      = 0;
        $this->days_present            = 0;
        $this->is_ncc_scout            = 'No';
        $this->general_conduct         = 'Good';
        $this->application_date        = now()->format('Y-m-d');
        $this->tc_issue_date           = now()->format('Y-m-d');
    }

    // ─── View ───────────────────────────────────────────────────────────────────

    public function viewRecord(int $id, string $type): void
    {
        if ($type === 'tc') {
            $this->viewRecord = TransferCertificate::with(['student'])->where('organization_id', $this->orgId())->find($id);
        } else {
            $this->viewRecord = Certificate::with(['student'])->where('organization_id', $this->orgId())->find($id);
        }

        if ($this->viewRecord) {
            $this->viewType    = $type;
            $this->showViewModal = true;
        }
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewRecord    = null;
    }

    // ─── Delete ─────────────────────────────────────────────────────────────────

    public function deleteRecord(int $id, string $type): void
    {
        $this->pendingDeleteId   = $id;
        $this->pendingDeleteType = $type;
    }

    public function cancelDelete(): void
    {
        $this->pendingDeleteId   = null;
        $this->pendingDeleteType = 'cert';
    }

    public function doDelete(): void
    {
        if ($this->pendingDeleteType === 'tc') {
            TransferCertificate::where('organization_id', $this->orgId())
                ->where('id', $this->pendingDeleteId)->delete();
        } else {
            Certificate::where('organization_id', $this->orgId())
                ->where('id', $this->pendingDeleteId)->delete();
        }

        $this->pendingDeleteId   = null;
        $this->pendingDeleteType = 'cert';
        session()->flash('success', 'Record deleted successfully!');
    }

    // ─── Render ─────────────────────────────────────────────────────────────────

    public function render()
    {
        $orgId = $this->orgId();

        $certificates = collect();
        $tcList       = collect();

        if ($this->activeTab === 'tc') {
            $q = TransferCertificate::with('student')
                ->where('organization_id', $orgId);

            if ($this->search) {
                $q->where(fn($sq) =>
                    $sq->where('tc_no', 'like', "%{$this->search}%")
                       ->orWhereHas('student', fn($s) =>
                           $s->where('full_name', 'like', "%{$this->search}%")
                             ->orWhere('admission_no', 'like', "%{$this->search}%")
                       )
                );
            }

            if ($this->filterClass) {
                $q->whereHas('student', fn($s) => $s->where('standard_id', $this->filterClass));
            }
            if ($this->filterSection) {
                $q->whereHas('student', fn($s) => $s->where('section_id', $this->filterSection));
            }
            if ($this->filterMonth) {
                [$fy, $fm] = array_pad(explode('-', $this->filterMonth), 2, null);
                if ($fy && $fm) $q->whereYear('issue_date', $fy)->whereMonth('issue_date', $fm);
            }

            $tcList = $q->orderByDesc('issue_date')->paginate($this->perPage);
        } else {
            $q = Certificate::with('student')
                ->where('organization_id', $orgId)
                ->where('type', $this->activeTab);

            if ($this->search) {
                $q->where(fn($sq) =>
                    $sq->where('event_name', 'like', "%{$this->search}%")
                       ->orWhere('certificate_no', 'like', "%{$this->search}%")
                       ->orWhereHas('student', fn($s) =>
                           $s->where('full_name', 'like', "%{$this->search}%")
                             ->orWhere('admission_no', 'like', "%{$this->search}%")
                       )
                );
            }

            if ($this->filterClass) {
                $q->whereHas('student', fn($s) => $s->where('standard_id', $this->filterClass));
            }
            if ($this->filterSection) {
                $q->whereHas('student', fn($s) => $s->where('section_id', $this->filterSection));
            }
            if ($this->filterMonth) {
                [$fy, $fm] = array_pad(explode('-', $this->filterMonth), 2, null);
                if ($fy && $fm) $q->whereYear('issued_date', $fy)->whereMonth('issued_date', $fm);
            }

            $certificates = $q->orderByDesc('issued_date')->paginate($this->perPage);
        }

        // Analytics
        $achievementCount   = Certificate::where('organization_id', $orgId)->where('type', 'achievement')->count();
        $participationCount = Certificate::where('organization_id', $orgId)->where('type', 'participation')->count();
        $tcCount            = TransferCertificate::where('organization_id', $orgId)->count();

        return view('livewire.accounts.tc-certificate', compact(
            'certificates', 'tcList',
            'achievementCount', 'participationCount', 'tcCount'
        ));
    }
}
