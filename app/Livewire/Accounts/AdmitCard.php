<?php

namespace App\Livewire\Accounts;

use App\Models\Student\AdmitCard as ModelAdmitCard;
use App\Models\Admin\Exam;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AdmitCard extends Component
{
    use WithPagination;

    // ─── Filters ────────────────────────────────────────────────────────────────
    public string $search         = '';
    public string $examFilter     = '';
    public string $standardFilter = '';
    public string $sectionFilter  = '';
    public string $statusFilter   = '';
    public int    $perPage        = 15;

    // ─── Issue Admit Card Modal ──────────────────────────────────────────────────
    public bool   $showIssueModal     = false;
    public string $issueExam          = '';
    public string $issueStandard      = '';
    public string $issueSection       = '';
    public array  $issueStudents      = [];
    public array  $issueSubjects      = [];
    public string $issueInstructions  = '';
    public string $issueReportingTime = '08:30';

    // ─── Bulk Generate Full Screen ───────────────────────────────────────────────
    public bool   $showBulkScreen     = false;
    public string $bulkExam           = '';
    public string $bulkStandard       = '';
    public string $bulkSection        = '';
    public string $bulkGenerateType   = 'attendance';
    public int    $bulkPercentage     = 75;
    public array  $bulkSubjects       = [];
    public string $bulkInstructions   = '';
    public string $bulkReportingTime  = '08:30';

    // ─── Edit Modal ──────────────────────────────────────────────────────────────
    public bool   $showEditModal       = false;
    public ?int   $editCardId          = null;
    public string $editAdmitCardNumber = '';
    public string $editRollNumber      = '';
    public string $editExamRollNumber  = '';
    public string $editReportingTime   = '08:30';
    public string $editExamCenter      = '';
    public string $editExamCenterAddress = '';
    public string $editSeatNumber      = '';
    public string $editRoomNumber      = '';
    public string $editInstructions    = '';
    public string $editStatus          = 'active';
    public array  $editSubjects        = [];

    // ─── Delete ─────────────────────────────────────────────────────────────────
    public ?int $pendingDeleteId = null;

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function mount(): void
    {
        $this->issueSubjects = $this->defaultSubjectRow();
        $this->bulkSubjects  = $this->defaultSubjectRow();
        $this->editSubjects  = $this->defaultSubjectRow();
    }

    private function defaultSubjectRow(): array
    {
        return [[
            'subject_id'    => '',
            'subject_name'  => '',
            'exam_date'     => now()->addDays(7)->format('Y-m-d'),
            'exam_time'     => '09:00',
            'exam_duration' => '3 Hours',
            'status'        => 'eligible',
        ]];
    }

    // ─── Filter Watchers ────────────────────────────────────────────────────────
    public function updatedSearch(): void          { $this->resetPage(); }
    public function updatedExamFilter(): void      { $this->resetPage(); }
    public function updatedStandardFilter(): void  { $this->sectionFilter = ''; $this->resetPage(); }
    public function updatedSectionFilter(): void   { $this->resetPage(); }
    public function updatedStatusFilter(): void    { $this->resetPage(); }

    // ─── Issue Watchers ──────────────────────────────────────────────────────────
    public function updatedIssueStandard(): void
    {
        $this->issueSection  = '';
        $this->issueStudents = [];
        $this->issueSubjects = $this->loadClassSubjects($this->issueStandard, '');
    }

    public function updatedIssueSection(): void
    {
        $this->issueStudents = [];
        $this->issueSubjects = $this->loadClassSubjects($this->issueStandard, $this->issueSection);
    }

    public function updatedIssueExam(): void { $this->issueStudents = []; }

    // ─── Bulk Watchers ───────────────────────────────────────────────────────────
    public function updatedBulkStandard(): void
    {
        $this->bulkSection  = '';
        $this->bulkSubjects = $this->loadClassSubjects($this->bulkStandard, '');
    }

    public function updatedBulkSection(): void
    {
        $this->bulkSubjects = $this->loadClassSubjects($this->bulkStandard, $this->bulkSection);
    }

    // ─── Load class subjects ─────────────────────────────────────────────────────
    private function loadClassSubjects(string $standardId, string $sectionId): array
    {
        if (!$standardId) return $this->defaultSubjectRow();

        $subjectIds = SectionSubject::where('organization_id', $this->orgId())
            ->where('standard_id', $standardId)
            ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
            ->pluck('subject_id')->unique();

        $subjects = Subject::whereIn('id', $subjectIds)->where('is_active', true)->orderBy('id')->get();

        return $subjects->isEmpty() ? $this->defaultSubjectRow() : $subjects->map(fn($s) => [
            'subject_id'    => (string) $s->id,
            'subject_name'  => $s->name,
            'exam_date'     => now()->addDays(7)->format('Y-m-d'),
            'exam_time'     => '09:00',
            'exam_duration' => '3 Hours',
            'status'        => 'eligible',
        ])->toArray();
    }

    // ─── Issue Subject rows ──────────────────────────────────────────────────────
    public function addIssueSubject(): void
    {
        $this->issueSubjects[] = [
            'subject_id' => '', 'subject_name' => '',
            'exam_date'  => now()->addDays(7)->format('Y-m-d'),
            'exam_time'  => '09:00', 'exam_duration' => '3 Hours', 'status' => 'eligible',
        ];
    }

    public function removeIssueSubject(int $index): void
    {
        if (count($this->issueSubjects) > 1) {
            unset($this->issueSubjects[$index]);
            $this->issueSubjects = array_values($this->issueSubjects);
        }
    }

    public function syncIssueSubjectName(int $index): void
    {
        $id = $this->issueSubjects[$index]['subject_id'] ?? null;
        if ($id) {
            $sub = Subject::find($id);
            if ($sub) $this->issueSubjects[$index]['subject_name'] = $sub->name;
        }
    }

    // ─── Bulk Subject rows ───────────────────────────────────────────────────────
    public function addBulkSubject(): void
    {
        $this->bulkSubjects[] = [
            'subject_id' => '', 'subject_name' => '',
            'exam_date'  => now()->addDays(7)->format('Y-m-d'),
            'exam_time'  => '09:00', 'exam_duration' => '3 Hours', 'status' => 'eligible',
        ];
    }

    public function removeBulkSubject(int $index): void
    {
        if (count($this->bulkSubjects) > 1) {
            unset($this->bulkSubjects[$index]);
            $this->bulkSubjects = array_values($this->bulkSubjects);
        }
    }

    public function syncBulkSubjectName(int $index): void
    {
        $id = $this->bulkSubjects[$index]['subject_id'] ?? null;
        if ($id) {
            $sub = Subject::find($id);
            if ($sub) $this->bulkSubjects[$index]['subject_name'] = $sub->name;
        }
    }

    // ─── Student toggles ────────────────────────────────────────────────────────
    public function toggleIssueStudent(int $id): void
    {
        if (in_array($id, $this->issueStudents)) {
            $this->issueStudents = array_values(array_diff($this->issueStudents, [$id]));
        } else {
            $this->issueStudents[] = $id;
        }
    }

    public function selectAllIssueStudents(): void
    {
        $this->issueStudents = $this->issueAvailableStudents->pluck('id')->toArray();
    }

    public function deselectAllIssueStudents(): void
    {
        $this->issueStudents = [];
    }

    // ─── Issue Modal ─────────────────────────────────────────────────────────────
    public function openIssueModal(): void
    {
        $this->showIssueModal     = true;
        $this->issueExam          = '';
        $this->issueStandard      = '';
        $this->issueSection       = '';
        $this->issueStudents      = [];
        $this->issueSubjects      = $this->defaultSubjectRow();
        $this->issueInstructions  = '';
        $this->issueReportingTime = '08:30';
    }

    public function closeIssueModal(): void { $this->showIssueModal = false; }

    public function issueAdmitCards(): void
    {
        $this->validate([
            'issueExam'          => 'required|exists:exams,id',
            'issueStandard'      => 'required|exists:standards,id',
            'issueStudents'      => 'required|array|min:1',
            'issueSubjects'      => 'required|array|min:1',
            'issueSubjects.*.subject_id'    => 'required|exists:subjects,id',
            'issueSubjects.*.subject_name'  => 'required|string',
            'issueSubjects.*.exam_date'     => 'required|date',
            'issueSubjects.*.exam_time'     => 'required',
            'issueSubjects.*.exam_duration' => 'required|string',
        ]);

        $org      = Auth::user()->organization;
        $exam     = Exam::findOrFail($this->issueExam);
        $students = StudentDetail::with(['standard', 'section'])
            ->whereIn('id', $this->issueStudents)->get();

        $generated = 0;
        foreach ($students as $student) {
            if (ModelAdmitCard::where('student_detail_id', $student->id)
                ->where('exam_id', $this->issueExam)->exists()) continue;

            ModelAdmitCard::create([
                'student_detail_id'   => $student->id,
                'exam_id'             => $this->issueExam,
                'organization_id'     => $org->id,
                'admit_card_number'   => ModelAdmitCard::generateAdmitCardNumber($org->id, $this->issueExam),
                'student_name'        => $student->full_name,
                'father_name'         => $student->father_name,
                'mother_name'         => $student->mother_name,
                'roll_number'         => $student->roll_no ?? 'N/A',
                'standard_id'         => $student->standard_id,
                'section_id'          => $student->section_id,
                'exam_name'           => $exam->exam_name,
                'academic_year'       => $exam->academic_year,
                'reporting_time'      => $this->issueReportingTime,
                'exam_center'         => '',
                'exam_center_address' => '',
                'instructions'        => $this->issueInstructions,
                'allowed_items'       => [],
                'prohibited_items'    => [],
                'subjects'            => $this->issueSubjects,
                'status'              => 'active',
                'issue_date'          => now(),
                'created_by'          => Auth::id(),
            ]);
            $generated++;
        }

        $this->closeIssueModal();
        session()->flash('success', "Issued {$generated} admit card(s) successfully!");
        $this->resetPage();
    }

    // ─── Bulk Screen ─────────────────────────────────────────────────────────────
    public function openBulkScreen(): void
    {
        $this->showBulkScreen    = true;
        $this->bulkExam          = '';
        $this->bulkStandard      = '';
        $this->bulkSection       = '';
        $this->bulkGenerateType  = 'attendance';
        $this->bulkPercentage    = 75;
        $this->bulkSubjects      = $this->defaultSubjectRow();
        $this->bulkInstructions  = '';
        $this->bulkReportingTime = '08:30';
    }

    public function closeBulkScreen(): void { $this->showBulkScreen = false; }

    public function bulkGenerateAdmitCards(): void
    {
        $this->validate([
            'bulkExam'          => 'required|exists:exams,id',
            'bulkStandard'      => 'required|exists:standards,id',
            'bulkPercentage'    => 'required_unless:bulkGenerateType,none|integer|min:1|max:100',
            'bulkSubjects'      => 'required|array|min:1',
            'bulkSubjects.*.subject_id'    => 'required|exists:subjects,id',
            'bulkSubjects.*.subject_name'  => 'required|string',
            'bulkSubjects.*.exam_date'     => 'required|date',
            'bulkSubjects.*.exam_time'     => 'required',
            'bulkSubjects.*.exam_duration' => 'required|string',
        ]);

        $org  = Auth::user()->organization;
        $exam = Exam::findOrFail($this->bulkExam);

        $students = StudentDetail::with(['standard', 'section'])
            ->where('organization_id', $org->id)
            ->where('standard_id', $this->bulkStandard)
            ->when($this->bulkSection, fn($q) => $q->where('section_id', $this->bulkSection))
            ->whereDoesntHave('admitCards', fn($q) => $q->where('exam_id', $this->bulkExam))
            ->get();

        $eligible  = $students->filter(function ($s) {
            if ($this->bulkGenerateType === 'none') return true;
            return $this->bulkGenerateType === 'attendance'
                ? $this->meetsAttendanceCriteria($s->id)
                : $this->meetsFeeCriteria($s);
        });

        $generated = 0;
        foreach ($eligible as $student) {
            ModelAdmitCard::create([
                'student_detail_id'   => $student->id,
                'exam_id'             => $this->bulkExam,
                'organization_id'     => $org->id,
                'admit_card_number'   => ModelAdmitCard::generateAdmitCardNumber($org->id, $this->bulkExam),
                'student_name'        => $student->full_name,
                'father_name'         => $student->father_name,
                'mother_name'         => $student->mother_name,
                'roll_number'         => $student->roll_no ?? 'N/A',
                'standard_id'         => $student->standard_id,
                'section_id'          => $student->section_id,
                'exam_name'           => $exam->exam_name,
                'academic_year'       => $exam->academic_year,
                'reporting_time'      => $this->bulkReportingTime,
                'exam_center'         => '',
                'exam_center_address' => '',
                'instructions'        => $this->bulkInstructions,
                'allowed_items'       => [],
                'prohibited_items'    => [],
                'subjects'            => $this->bulkSubjects,
                'status'              => 'active',
                'issue_date'          => now(),
                'created_by'          => Auth::id(),
            ]);
            $generated++;
        }

        $skipped = $students->count() - $eligible->count();
        $this->closeBulkScreen();
        session()->flash('success', "Generated {$generated} admit cards. {$skipped} skipped.");
        $this->resetPage();
    }

    private function meetsAttendanceCriteria(int $studentId): bool
    {
        $total = StudentAttendance::where('student_detail_id', $studentId)->count();
        if ($total === 0) return true;
        $present = StudentAttendance::where('student_detail_id', $studentId)->where('status', 1)->count();
        return ($present / $total * 100) >= $this->bulkPercentage;
    }

    private function meetsFeeCriteria(StudentDetail $student): bool
    {
        $structures = FeeStructure::where('organization_id', $this->orgId())
            ->where('is_active', true)
            ->where('standard_id', $student->standard_id)
            ->where(fn($q) => $q->whereNull('section_id')->orWhere('section_id', $student->section_id))
            ->get();

        $academic  = $structures->where('fee_type', 'academic')->sum('amount');
        $transport = $student->transportation_required
            ? $structures->where('fee_type', 'transport')->sum('amount')
            : 0;
        $totalFee = $academic + $transport;

        if ($totalFee <= 0) return true;

        $paid = FeePayment::where('organization_id', $this->orgId())
            ->where('student_detail_id', $student->id)
            ->sum('amount');

        return ($paid / $totalFee * 100) >= $this->bulkPercentage;
    }

    // ─── Print All URL ────────────────────────────────────────────────────────────
    public function getPrintAllUrl(): string
    {
        $org    = Auth::user()->organization;
        $slug   = $org->serial_number ?? $org->id;
        $base   = route('accounts.admit-card.print-all', $slug);
        $params = array_filter([
            'exam_id'     => $this->examFilter,
            'standard_id' => $this->standardFilter,
            'section_id'  => $this->sectionFilter,
        ]);
        return $params ? $base . '?' . http_build_query($params) : $base;
    }

    // ─── Edit Modal ──────────────────────────────────────────────────────────────
    public function addEditSubject(): void
    {
        $this->editSubjects[] = [
            'subject_id' => '', 'subject_name' => '',
            'exam_date'  => now()->addDays(7)->format('Y-m-d'),
            'exam_time'  => '09:00', 'exam_duration' => '3 Hours', 'status' => 'eligible',
        ];
    }

    public function removeEditSubject(int $index): void
    {
        if (count($this->editSubjects) > 1) {
            unset($this->editSubjects[$index]);
            $this->editSubjects = array_values($this->editSubjects);
        }
    }

    public function syncEditSubjectName(int $index): void
    {
        $id = $this->editSubjects[$index]['subject_id'] ?? null;
        if ($id) {
            $sub = Subject::find($id);
            if ($sub) $this->editSubjects[$index]['subject_name'] = $sub->name;
        }
    }

    public function openEditModal(int $id): void
    {
        $card = ModelAdmitCard::where('organization_id', $this->orgId())->findOrFail($id);

        $this->editCardId            = $id;
        $this->editAdmitCardNumber   = $card->admit_card_number;
        $this->editRollNumber        = $card->roll_number;
        $this->editExamRollNumber    = $card->exam_roll_number ?? '';
        $this->editReportingTime     = $card->reporting_time?->format('H:i') ?? '08:30';
        $this->editExamCenter        = $card->exam_center ?? '';
        $this->editExamCenterAddress = $card->exam_center_address ?? '';
        $this->editSeatNumber        = $card->seat_number ?? '';
        $this->editRoomNumber        = $card->room_number ?? '';
        $this->editInstructions      = $card->instructions ?? '';
        $this->editStatus            = $card->status;
        $this->editSubjects          = !empty($card->subjects) ? $card->subjects : $this->defaultSubjectRow();
        $this->showEditModal         = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editCardId    = null;
        $this->resetValidation();
    }

    public function saveEditCard(): void
    {
        $this->validate([
            'editAdmitCardNumber' => 'required|string|max:50|unique:admit_cards,admit_card_number,' . $this->editCardId,
            'editRollNumber'      => 'required|string|max:50',
            'editStatus'          => 'required|in:active,inactive,used',
            'editSubjects'        => 'required|array|min:1',
            'editSubjects.*.subject_id'    => 'required|exists:subjects,id',
            'editSubjects.*.subject_name'  => 'required|string',
            'editSubjects.*.exam_date'     => 'required|date',
            'editSubjects.*.exam_time'     => 'required',
            'editSubjects.*.exam_duration' => 'required|string',
        ]);

        ModelAdmitCard::where('organization_id', $this->orgId())->findOrFail($this->editCardId)->update([
            'admit_card_number'   => $this->editAdmitCardNumber,
            'roll_number'         => $this->editRollNumber,
            'exam_roll_number'    => $this->editExamRollNumber ?: null,
            'reporting_time'      => $this->editReportingTime,
            'exam_center'         => $this->editExamCenter,
            'exam_center_address' => $this->editExamCenterAddress,
            'seat_number'         => $this->editSeatNumber ?: null,
            'room_number'         => $this->editRoomNumber ?: null,
            'instructions'        => $this->editInstructions,
            'status'              => $this->editStatus,
            'subjects'            => $this->editSubjects,
            'updated_by'          => Auth::id(),
        ]);

        $this->closeEditModal();
        session()->flash('success', 'Admit card updated successfully.');
    }

    // ─── Delete ─────────────────────────────────────────────────────────────────
    public function deleteCard(int $id): void  { $this->pendingDeleteId = $id; }
    public function cancelDelete(): void        { $this->pendingDeleteId = null; }

    public function doDelete(): void
    {
        ModelAdmitCard::where('id', $this->pendingDeleteId)
            ->where('organization_id', $this->orgId())->delete();
        $this->pendingDeleteId = null;
        session()->flash('success', 'Admit card deleted.');
    }

    // ─── Reset Filters ───────────────────────────────────────────────────────────
    public function resetFilters(): void
    {
        $this->reset(['search', 'examFilter', 'standardFilter', 'sectionFilter', 'statusFilter']);
        $this->resetPage();
    }

    // ─── Computed ────────────────────────────────────────────────────────────────
    #[\Livewire\Attributes\Computed]
    public function analytics(): array
    {
        $orgId = $this->orgId();
        $cardQuery = ModelAdmitCard::where('organization_id', $orgId);
        if ($this->examFilter)     $cardQuery->where('exam_id', $this->examFilter);
        if ($this->standardFilter) $cardQuery->where('standard_id', $this->standardFilter);
        if ($this->sectionFilter)  $cardQuery->where('section_id', $this->sectionFilter);
        $issued = (clone $cardQuery)->count();

        $studentQuery = StudentDetail::where('organization_id', $orgId);
        if ($this->standardFilter) $studentQuery->where('standard_id', $this->standardFilter);
        if ($this->sectionFilter)  $studentQuery->where('section_id', $this->sectionFilter);
        $total = $studentQuery->count();

        return ['total' => $total, 'issued' => $issued, 'remaining' => max(0, $total - $issued)];
    }

    #[\Livewire\Attributes\Computed]
    public function issueAvailableStudents()
    {
        if (!$this->issueExam || !$this->issueStandard) return collect();
        return StudentDetail::with(['standard', 'section'])
            ->where('organization_id', $this->orgId())
            ->where('standard_id', $this->issueStandard)
            ->when($this->issueSection, fn($q) => $q->where('section_id', $this->issueSection))
            ->whereDoesntHave('admitCards', fn($q) => $q->where('exam_id', $this->issueExam))
            ->orderBy('full_name')->get();
    }

    #[\Livewire\Attributes\Computed]
    public function allSubjects()
    {
        return Subject::where('organization_id', $this->orgId())
            ->where('is_active', true)->orderBy('id')->get();
    }

    // ─── Render ─────────────────────────────────────────────────────────────────
    public function render()
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('id')->get();

        $exams = Exam::where('organization_id', $orgId)->orderByDesc('created_at')->get();

        $filterSections = collect();
        if ($this->standardFilter) {
            $filterSections = Section::where('standard_id', $this->standardFilter)
                ->where('organization_id', $orgId)->orderBy('id')->get();
        }

        $issueSections = collect();
        if ($this->issueStandard) {
            $issueSections = Section::where('standard_id', $this->issueStandard)
                ->where('organization_id', $orgId)->orderBy('id')->get();
        }

        $bulkSections = collect();
        if ($this->bulkStandard) {
            $bulkSections = Section::where('standard_id', $this->bulkStandard)
                ->where('organization_id', $orgId)->orderBy('id')->get();
        }

        $admitCards = ModelAdmitCard::with(['studentDetail.standard', 'studentDetail.section', 'exam'])
            ->where('organization_id', $orgId)
            ->when($this->search, fn($q) => $q->where(fn($s) =>
                $s->where('admit_card_number', 'like', "%{$this->search}%")
                  ->orWhere('student_name', 'like', "%{$this->search}%")
            ))
            ->when($this->examFilter,     fn($q) => $q->where('exam_id', $this->examFilter))
            ->when($this->standardFilter, fn($q) => $q->where('standard_id', $this->standardFilter))
            ->when($this->sectionFilter,  fn($q) => $q->where('section_id', $this->sectionFilter))
            ->when($this->statusFilter,   fn($q) => $q->where('status', $this->statusFilter))
            ->latest()->paginate($this->perPage);

        $org = Auth::user()->organization;

        return view('livewire.accounts.admit-card', compact(
            'standards', 'exams', 'filterSections', 'issueSections', 'bulkSections',
            'admitCards', 'org'
        ));
    }
}
