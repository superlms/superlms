<?php

namespace App\Livewire\SuperAdmin;

use App\Helpers\CityGetHelper;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\SchoolInfo;
use App\Models\Admin\Transportation;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\User;
use App\Services\ZeptoMailService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Student extends Component
{
    use WireUiActions, WithPagination;

    // ─── Stats ───────────────────────────────────────────────────────────────
    public int $totalSchools     = 0;
    public int $totalStudents    = 0;
    public int $activeStudents   = 0;
    public int $inactiveStudents = 0;

    // ─── View Modal ──────────────────────────────────────────────────────────
    public bool   $showViewModal  = false;
    public string $viewModalTitle = '';
    public array  $viewData       = [];
    public        $studentImageUrl = null;

    // ─── Filters ─────────────────────────────────────────────────────────────
    public string $search             = '';
    public string $filterOrganization = '';
    public string $filterClass        = '';
    public string $filterSection      = '';
    public string $filterGender       = '';
    public string $filterStatus       = '';
    public int    $perPage            = 50;

    // ─── Filter Options ───────────────────────────────────────────────────────
    public $organizations  = [];
    public $standards      = [];
    public $filterSections = [];

    protected $queryString = [
        'search'             => ['except' => ''],
        'filterOrganization' => ['except' => ''],
        'filterClass'        => ['except' => ''],
        'filterSection'      => ['except' => ''],
        'filterGender'       => ['except' => ''],
        'filterStatus'       => ['except' => ''],
    ];

    // ─── Add Student Panel ────────────────────────────────────────────────────
    public bool   $showAddPanel        = false;
    public string $addOrgId            = '';
    public string $addName             = '';
    public string $addEmail            = '';
    public string $addMobile           = '';
    public string $addGender           = '';
    public string $addDob              = '';
    public string $addFatherName       = '';
    public string $addMotherName       = '';
    public string $addReligion         = '';
    public string $addLocalAddress     = '';
    public string $addPermanentAddress = '';
    public string $addState            = '';
    public string $addCity             = '';
    public string $addPincode          = '';
    public string $addAadharNo         = '';
    public string $addBoard            = '';
    public string $addDateOfAdmission  = '';
    public string $addStandardId       = '';
    public string $addSectionId        = '';
    public        $addTransportation   = '0'; // '1' = Yes, '0' = No
    public        $addRoute            = '';
    public string $addApparId          = '';
    public string $addRegNo            = '';
    public        $addStandards        = [];
    public        $addSections         = [];
    public        $addStates           = [];
    public        $addCities           = [];
    public        $addRouteOptions     = [];

    // ─── Delete Confirm ───────────────────────────────────────────────────────
    public bool $showDeleteConfirm = false;
    public      $deleteTargetId    = null;

    // ─── Edit Student Panel ───────────────────────────────────────────────────
    public bool   $showEditPanel         = false;
    public        $editDetailId          = null;
    public        $editUserId            = null;
    public string $editOrgId             = '';
    public string $editName              = '';
    public string $editEmail             = '';
    public string $editMobile            = '';
    public string $editGender            = '';
    public string $editDob               = '';
    public string $editFatherName        = '';
    public string $editMotherName        = '';
    public string $editReligion          = '';
    public string $editLocalAddress      = '';
    public string $editPermanentAddress  = '';
    public string $editState             = '';
    public string $editCity              = '';
    public string $editPincode           = '';
    public string $editAadharNo          = '';
    public string $editBoard             = '';
    public string $editDateOfAdmission   = '';
    public string $editStandardId        = '';
    public string $editSectionId         = '';
    public        $editTransportation    = '0'; // '1' = Yes, '0' = No
    public        $editRoute             = '';
    public string $editApparId           = '';
    public string $editRegNo             = '';
    public        $editStandards         = [];
    public        $editSections          = [];
    public        $editStates            = [];
    public        $editCities            = [];
    public        $editRouteOptions      = [];

    protected $listeners = ['onViewStudentSuperAdmin', 'onDeleteStudentSuperAdmin'];

    // ─── Mount ────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->organizations = Organization::orderBy('name')->get();
        $this->loadStats();
    }

    // ─── Base Query (shared by stats, table, export) ──────────────────────────

    private function baseQuery()
    {
        return StudentDetail::with(['user', 'standard', 'section', 'user.organization'])
            ->when($this->filterOrganization, fn($q) => $q->whereHas('user', fn($q) => $q->where('organization_id', $this->filterOrganization)))
            ->when($this->search, fn($q) => $q->where(
                fn($q) => $q
                    ->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('admission_no', 'like', "%{$this->search}%")
                    ->orWhere('roll_no', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn($q) => $q->where('email', 'like', "%{$this->search}%"))
            ))
            ->when($this->filterClass,   fn($q) => $q->where('standard_id', $this->filterClass))
            ->when($this->filterSection, fn($q) => $q->where('section_id', $this->filterSection))
            ->when($this->filterGender,  fn($q) => $q->where('gender', $this->filterGender))
            ->when($this->filterStatus !== '', fn($q) => $q->whereHas('user', fn($q) => $q->where('is_active', $this->filterStatus)));
    }

    // ─── Stats ────────────────────────────────────────────────────────────────

    private function loadStats(): void
    {
        $this->totalSchools     = SchoolInfo::count();
        $this->totalStudents    = $this->baseQuery()->count();
        $this->activeStudents   = $this->baseQuery()->whereHas('user', fn($q) => $q->where('is_active', 1))->count();
        $this->inactiveStudents = $this->baseQuery()->whereHas('user', fn($q) => $q->where('is_active', 0))->count();
    }

    // ─── Filter Hooks ─────────────────────────────────────────────────────────

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadStats();
    }

    public function updatedFilterOrganization(): void
    {
        $this->resetPage();
        $this->filterClass   = '';
        $this->filterSection = '';

        $this->standards = $this->filterOrganization
            ? Standard::where('organization_id', $this->filterOrganization)->get()
            : [];
        $this->filterSections = [];
        $this->loadStats();
    }

    public function updatedFilterClass(): void
    {
        $this->resetPage();
        $this->filterSection  = '';
        $this->filterSections = $this->filterClass
            ? Section::where('standard_id', $this->filterClass)->get()
            : [];
        $this->loadStats();
    }

    public function updatedFilterSection(): void
    {
        $this->resetPage();
        $this->loadStats();
    }

    public function updatedFilterGender(): void
    {
        $this->resetPage();
        $this->loadStats();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
        $this->loadStats();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterOrganization', 'filterClass', 'filterSection', 'filterGender', 'filterStatus']);
        $this->standards      = [];
        $this->filterSections = [];
        $this->resetPage();
        $this->loadStats();
    }

    // ─── View ─────────────────────────────────────────────────────────────────

    public function onViewStudentSuperAdmin($id): void
    {
        $detail = StudentDetail::with(['user', 'standard', 'section', 'user.organization.schoolInfo', 'transportations'])
            ->where('user_id', $id)
            ->first();

        if (!$detail) {
            $this->notification()->error('Student not found!');
            return;
        }

        $this->studentImageUrl = $detail->user?->image;
        $this->viewModalTitle  = 'Student Details';
        $this->viewData        = [
            'user'     => $detail->user,
            'detail'   => $detail,
            'standard' => $detail->standard,
            'section'  => $detail->section,
        ];
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal  = false;
        $this->viewData       = [];
        $this->viewModalTitle = '';
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function onDeleteStudentSuperAdmin($id): void
    {
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    public function doDeleteStudent($id): void
    {
        $detail = StudentDetail::find($id);
        if ($detail) {
            $user = User::find($detail->user_id);
            $detail->delete();
            $user?->delete();
            $this->showDeleteConfirm = false;
            $this->deleteTargetId    = null;
            $this->loadStats();
            $this->resetPage();
            $this->notification()->success('Student deleted successfully!');
        } else {
            $this->notification()->error('Student not found!');
        }
    }

    // ─── Edit Student Panel ────────────────────────────────────────────────────

    public function openEditPanel($id): void
    {
        $detail = StudentDetail::with(['user'])->find($id);
        if (!$detail) {
            $this->notification()->error('Student not found!');
            return;
        }

        $this->editStates = (new CityGetHelper())->getState();

        $this->editDetailId         = $detail->id;
        $this->editUserId           = $detail->user_id;
        $this->editOrgId            = (string) ($detail->organization_id ?? '');
        $this->editName             = $detail->full_name ?? '';
        $this->editEmail            = $detail->user?->email ?? '';
        $this->editMobile           = $detail->phone ?? '';
        $this->editGender           = $detail->gender ?? '';
        $this->editDob              = $detail->dob?->format('Y-m-d') ?? '';
        $this->editFatherName       = $detail->father_name ?? '';
        $this->editMotherName       = $detail->mother_name ?? '';
        $this->editReligion         = $detail->religion ?? '';
        $this->editLocalAddress     = $detail->local_address ?? '';
        $this->editPermanentAddress = $detail->permanent_address ?? '';
        $this->editState            = $detail->state ?? '';
        $this->editCity             = $detail->city ?? '';
        $this->editPincode          = $detail->pincode ?? '';
        $this->editAadharNo         = $detail->aadhar_no ?? '';
        $this->editBoard            = $detail->board ?? '';
        $this->editDateOfAdmission  = $detail->date_of_admission?->format('Y-m-d') ?? '';
        $this->editStandardId       = (string) ($detail->standard_id ?? '');
        $this->editSectionId        = (string) ($detail->section_id ?? '');
        $this->editTransportation   = $detail->transportation_required ? '1' : '0';
        $this->editRoute            = $detail->transportations()->first()?->id ?? '';
        $this->editApparId          = $detail->appar_id ?? '';
        $this->editRegNo            = $detail->registration_number ?? '';

        $this->editStandards = $this->editOrgId
            ? Standard::where('organization_id', $this->editOrgId)->orderBy('name')->get()
            : [];

        $this->editSections = $this->editStandardId
            ? Section::where('standard_id', $this->editStandardId)->get()
            : [];

        $this->editRouteOptions = $this->editOrgId
            ? $this->routesForOrg($this->editOrgId)
            : [];

        $this->editCities = $this->editState
            ? (new CityGetHelper())->cityGetByState($this->editState)
            : [];

        $this->resetValidation();
        $this->showEditPanel = true;
    }

    public function closeEditPanel(): void
    {
        $this->showEditPanel = false;
        $this->resetValidation();
    }

    public function updatedEditOrgId(): void
    {
        $this->editStandardId = '';
        $this->editSectionId  = '';
        $this->editSections   = [];
        $this->editBoard      = '';
        $this->editRoute      = '';

        if ($this->editOrgId) {
            $this->editStandards    = Standard::where('organization_id', $this->editOrgId)->orderBy('name')->get();
            $this->editBoard        = Organization::find($this->editOrgId)?->education_board ?? '';
            $this->editRouteOptions = $this->routesForOrg($this->editOrgId);
        } else {
            $this->editStandards    = [];
            $this->editRouteOptions = [];
        }
    }

    public function updatedEditStandardId(): void
    {
        $this->editSectionId = '';
        $this->editSections  = $this->editStandardId
            ? Section::where('standard_id', $this->editStandardId)->get()
            : [];
    }

    public function updatedEditState(): void
    {
        $this->editCity   = '';
        $this->editCities = $this->editState
            ? (new CityGetHelper())->cityGetByState($this->editState)
            : [];
    }

    public function saveEditStudent(): void
    {
        $this->validate([
            'editOrgId'            => 'required|integer|exists:organizations,id',
            'editName'             => 'required|string|max:255',
            'editEmail'            => 'required|email|max:100|unique:users,email,' . $this->editUserId,
            'editMobile'           => 'required|digits:10',
            'editGender'           => 'required|in:male,female,other',
            'editDob'              => 'required|date|before:today',
            'editFatherName'       => 'required|string|max:255',
            'editMotherName'       => 'required|string|max:255',
            'editBoard'            => 'required|string|max:100',
            'editDateOfAdmission'  => 'required|date|before_or_equal:today',
            'editReligion'         => 'nullable|string|max:100',
            'editLocalAddress'     => 'nullable|string|max:500',
            'editPermanentAddress' => 'nullable|string|max:500',
            'editPincode'          => 'nullable|digits:6',
            'editAadharNo'         => 'nullable|digits:12',
            'editStandardId'       => 'required|integer|exists:standards,id',
            'editSectionId'        => 'required|integer|exists:sections,id',
            'editRoute'            => $this->editTransportation
                ? 'required|integer|exists:transportations,id'
                : 'nullable',
        ], [
            'editStandardId.required' => 'Please select a class.',
            'editSectionId.required'  => 'Please select a section.',
            'editRoute.required'      => 'Please select a transport route.',
        ]);

        User::where('id', $this->editUserId)->update([
            'name'            => $this->editName,
            'email'           => $this->editEmail,
            'mobile_number'   => $this->editMobile,
            'organization_id' => $this->editOrgId,
        ]);

        StudentDetail::where('id', $this->editDetailId)->update([
            'organization_id'         => $this->editOrgId,
            'standard_id'             => $this->editStandardId ?: null,
            'section_id'              => $this->editSectionId ?: null,
            'full_name'               => $this->editName,
            'father_name'             => $this->editFatherName,
            'mother_name'             => $this->editMotherName,
            'email'                   => $this->editEmail,
            'dob'                     => $this->editDob,
            'gender'                  => $this->editGender,
            'religion'                => $this->editReligion ?: null,
            'phone'                   => $this->editMobile,
            'local_address'           => $this->editLocalAddress ?: null,
            'permanent_address'       => $this->editPermanentAddress ?: null,
            'state'                   => $this->editState ?: null,
            'city'                    => $this->editCity ?: null,
            'pincode'                 => $this->editPincode ?: null,
            'aadhar_no'               => $this->editAadharNo ?: null,
            'board'                   => $this->editBoard,
            'date_of_admission'       => $this->editDateOfAdmission,
            'transportation_required' => (bool) $this->editTransportation,
            'appar_id'                => $this->editApparId ?: null,
            'registration_number'     => $this->editRegNo ?: null,
        ]);

        $detail = StudentDetail::find($this->editDetailId);
        if ($detail) {
            $this->syncStudentRoute($detail, (bool) $this->editTransportation, $this->editRoute, $this->editOrgId);
        }

        $this->closeEditPanel();
        $this->loadStats();
        $this->notification()->success('Student updated successfully!');
    }

    public function exportStudents()
    {
        if (!$this->filterOrganization) return;

        $students = $this->baseQuery()->with('transportations')->get();
        $ids      = $students->pluck('id')->all();

        // ── Attendance: present (status = 1) out of total marked days ──
        $attendance = StudentAttendance::whereIn('student_detail_id', $ids)
            ->selectRaw('student_detail_id, COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present')
            ->groupBy('student_detail_id')->get()->keyBy('student_detail_id');

        // ── Performance: marks obtained out of max across all exam copies ──
        $marks = ExamCopy::whereIn('student_detail_id', $ids)
            ->selectRaw('student_detail_id, SUM(marks_obtained) as obtained, SUM(max_marks) as max_total')
            ->groupBy('student_detail_id')->get()->keyBy('student_detail_id');

        // ── Fees paid (academic / transport) ──
        $academicPaid = FeePayment::whereIn('student_detail_id', $ids)->where('fee_type', 'academic')
            ->selectRaw('student_detail_id, SUM(amount) as paid')->groupBy('student_detail_id')->get()->keyBy('student_detail_id');
        $transportPaid = FeePayment::whereIn('student_detail_id', $ids)->where('fee_type', 'transport')
            ->selectRaw('student_detail_id, SUM(amount) as paid')->groupBy('student_detail_id')->get()->keyBy('student_detail_id');

        // ── Fee totals per class (memoised so we hit the DB once per class) ──
        $academicTotals = [];
        $transportTotals = [];
        $academicTotalFor = function ($stdId, $secId) use (&$academicTotals) {
            if (!$stdId) return 0.0;
            $key = $stdId . '-' . ($secId ?: '0');
            return $academicTotals[$key] ??= (float) FeeStructure::forClass((int) $stdId, $secId ? (int) $secId : null)
                ->academic()->active()->sum('amount');
        };
        $transportTotalFor = function ($stdId, $secId) use (&$transportTotals) {
            if (!$stdId) return 0.0;
            $key = $stdId . '-' . ($secId ?: '0');
            return $transportTotals[$key] ??= (float) FeeStructure::forClass((int) $stdId, $secId ? (int) $secId : null)
                ->transport()->active()->sum('amount');
        };

        return response()->streamDownload(function () use ($students, $attendance, $marks, $academicPaid, $transportPaid, $academicTotalFor, $transportTotalFor) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'S.No',
                'Full Name',
                'Email',
                'Mobile',
                'Gender',
                'Date of Birth',
                'Religion',
                'Board',
                'Class',
                'Section',
                'Roll No',
                'Admission No',
                'Date of Admission',
                'Father Name',
                'Mother Name',
                'Aadhar No',
                'Appar ID',
                'Registration Number',
                'Local Address',
                'Permanent Address',
                'City',
                'State',
                'Pincode',
                'Transportation Required',
                'Transport Route',
                'School',
                'Status',
                'Attendance (Present/Total)',
                'Attendance %',
                'Performance (Marks/Max)',
                'Performance %',
                'Academic Fee (Paid/Total)',
                'Academic Fee Pending',
                'Transport Fee (Paid/Total)',
                'Transport Fee Pending',
            ]);

            foreach ($students as $index => $s) {
                $att        = $attendance[$s->id] ?? null;
                $attTotal   = (int) ($att->total ?? 0);
                $attPresent = (int) ($att->present ?? 0);
                $attPct     = $attTotal > 0 ? round($attPresent / $attTotal * 100, 1) . '%' : '—';

                $mk      = $marks[$s->id] ?? null;
                $obt     = (float) ($mk->obtained ?? 0);
                $maxT    = (float) ($mk->max_total ?? 0);
                $perfPct = $maxT > 0 ? round($obt / $maxT * 100, 1) . '%' : '—';

                $acPaid  = (float) ($academicPaid[$s->id]->paid ?? 0);
                $acTotal = (float) $academicTotalFor($s->standard_id, $s->section_id);
                $trPaid  = (float) ($transportPaid[$s->id]->paid ?? 0);
                $trTotal = $s->transportation_required ? (float) $transportTotalFor($s->standard_id, $s->section_id) : 0.0;

                fputcsv($handle, [
                    $index + 1,
                    $s->full_name ?? '',
                    $s->user?->email ?? '',
                    $s->phone ?? '',
                    ucfirst($s->gender ?? ''),
                    $s->dob?->format('d-m-Y') ?? '',
                    $s->religion ?? '',
                    $s->board ?? '',
                    $s->standard?->name ?? '',
                    $s->section?->name ?? '',
                    $s->roll_no ?? '',
                    $s->admission_no ?? '',
                    $s->date_of_admission?->format('d-m-Y') ?? '',
                    $s->father_name ?? '',
                    $s->mother_name ?? '',
                    $s->aadhar_no ?? '',
                    $s->appar_id ?? '',
                    $s->registration_number ?? '',
                    $s->local_address ?? '',
                    $s->permanent_address ?? '',
                    $s->city ?? '',
                    $s->state ?? '',
                    $s->pincode ?? '',
                    $s->transportation_required ? 'Yes' : 'No',
                    $s->transportations->first()?->route_name ?? '',
                    $s->user?->organization?->name ?? '',
                    ($s->user?->is_active) ? 'Active' : 'Inactive',
                    $attPresent . '/' . $attTotal,
                    $attPct,
                    rtrim(rtrim(number_format($obt, 2, '.', ''), '0'), '.') . '/' . rtrim(rtrim(number_format($maxT, 2, '.', ''), '0'), '.'),
                    $perfPct,
                    number_format($acPaid, 2) . ' / ' . number_format($acTotal, 2),
                    number_format(max($acTotal - $acPaid, 0), 2),
                    number_format($trPaid, 2) . ' / ' . number_format($trTotal, 2),
                    number_format(max($trTotal - $trPaid, 0), 2),
                ]);
            }
            fclose($handle);
        }, 'students_' . now()->format('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    // ─── Add Student Panel ────────────────────────────────────────────────────

    public function openAddPanel(): void
    {
        $this->addStates          = (new CityGetHelper())->getState();
        $this->addOrgId           = '';
        $this->addName            = '';
        $this->addEmail           = '';
        $this->addMobile          = '';
        $this->addGender          = '';
        $this->addDob             = '';
        $this->addFatherName      = '';
        $this->addMotherName      = '';
        $this->addReligion        = '';
        $this->addLocalAddress    = '';
        $this->addPermanentAddress = '';
        $this->addState           = '';
        $this->addCity            = '';
        $this->addPincode         = '';
        $this->addAadharNo        = '';
        $this->addBoard           = '';
        $this->addDateOfAdmission = now()->format('Y-m-d');
        $this->addStandardId      = '';
        $this->addSectionId       = '';
        $this->addTransportation  = '0';
        $this->addRoute           = '';
        $this->addApparId         = '';
        $this->addRegNo           = '';
        $this->addStandards       = [];
        $this->addSections        = [];
        $this->addCities          = [];
        $this->addRouteOptions    = [];
        $this->resetValidation();
        $this->showAddPanel       = true;
    }

    public function closeAddPanel(): void
    {
        $this->showAddPanel = false;
        $this->resetValidation();
    }

    public function updatedAddOrgId(): void
    {
        $this->addStandardId = '';
        $this->addSectionId  = '';
        $this->addSections   = [];
        $this->addBoard      = '';
        $this->addRoute      = '';

        if ($this->addOrgId) {
            $this->addStandards    = Standard::where('organization_id', $this->addOrgId)->orderBy('name')->get();
            $org = Organization::find($this->addOrgId);
            $this->addBoard        = $org?->education_board ?? '';
            $this->addRouteOptions = $this->routesForOrg($this->addOrgId);
        } else {
            $this->addStandards    = [];
            $this->addRouteOptions = [];
        }
    }

    /** Active transport routes for an organization (id, name, fee). */
    private function routesForOrg($orgId)
    {
        return Transportation::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('route_name')
            ->get(['id', 'route_name', 'monthly_fee']);
    }

    public function updatedAddTransportation(): void
    {
        if (!$this->addTransportation) {
            $this->addRoute = '';
        }
    }

    public function updatedEditTransportation(): void
    {
        if (!$this->editTransportation) {
            $this->editRoute = '';
        }
    }

    /** Attach/detach the chosen route on a student detail, scoped to its org. */
    private function syncStudentRoute(StudentDetail $detail, $transportRequired, $routeId, $orgId): void
    {
        if ($transportRequired && $routeId) {
            $detail->transportations()->sync([
                (int) $routeId => ['organization_id' => $orgId],
            ]);
        } else {
            $detail->transportations()->detach();
        }
    }

    public function updatedAddStandardId(): void
    {
        $this->addSectionId = '';
        $this->addSections  = $this->addStandardId
            ? Section::where('standard_id', $this->addStandardId)->get()
            : [];
    }

    public function updatedAddState(): void
    {
        $this->addCity   = '';
        $this->addCities = $this->addState
            ? (new CityGetHelper())->cityGetByState($this->addState)
            : [];
    }

    public function saveNewStudent(): void
    {
        $this->validate([
            'addOrgId'           => 'required|integer|exists:organizations,id',
            'addName'            => 'required|string|max:255',
            'addEmail'           => 'required|email|max:100|unique:users,email',
            'addMobile'          => 'required|digits:10',
            'addGender'          => 'required|in:male,female,other',
            'addDob'             => 'required|date|before:today',
            'addFatherName'      => 'required|string|max:255',
            'addMotherName'      => 'required|string|max:255',
            'addBoard'           => 'required|string|max:100',
            'addDateOfAdmission' => 'required|date|before_or_equal:today',
            'addReligion'        => 'nullable|string|max:100',
            'addLocalAddress'    => 'nullable|string|max:500',
            'addPermanentAddress'=> 'nullable|string|max:500',
            'addPincode'         => 'nullable|digits:6',
            'addAadharNo'        => 'nullable|digits:12',
            'addStandardId'      => 'required|integer|exists:standards,id',
            'addSectionId'       => 'required|integer|exists:sections,id',
            'addRoute'           => $this->addTransportation
                ? 'required|integer|exists:transportations,id'
                : 'nullable',
        ], [
            'addStandardId.required' => 'Please select a class.',
            'addSectionId.required'  => 'Please select a section.',
            'addRoute.required'      => 'Please select a transport route.',
        ]);

        $org           = Organization::findOrFail($this->addOrgId);
        $plainPassword = Str::upper(Str::random(4)) . rand(100, 999) . Str::random(3);

        $user = User::create([
            'name'            => $this->addName,
            'email'           => $this->addEmail,
            'mobile_number'   => $this->addMobile,
            'role'            => 'user',
            'is_active'       => true,
            'organization_id' => $this->addOrgId,
            'password'        => Hash::make($plainPassword),
        ]);

        $admissionNo = $this->generateAdmissionNo($org, $this->addStandardId, $this->addSectionId);
        $rollNo      = $this->generateRollNo($this->addStandardId, $this->addSectionId);

        $detail = StudentDetail::create([
            'user_id'                => $user->id,
            'organization_id'        => $this->addOrgId,
            'standard_id'            => $this->addStandardId ?: null,
            'section_id'             => $this->addSectionId ?: null,
            'full_name'              => $this->addName,
            'father_name'            => $this->addFatherName,
            'mother_name'            => $this->addMotherName,
            'email'                  => $this->addEmail,
            'dob'                    => $this->addDob,
            'gender'                 => $this->addGender,
            'religion'               => $this->addReligion ?: null,
            'phone'                  => $this->addMobile,
            'local_address'          => $this->addLocalAddress ?: null,
            'permanent_address'      => $this->addPermanentAddress ?: null,
            'state'                  => $this->addState ?: null,
            'city'                   => $this->addCity ?: null,
            'pincode'                => $this->addPincode ?: null,
            'aadhar_no'              => $this->addAadharNo ?: null,
            'board'                  => $this->addBoard,
            'admission_no'           => $admissionNo,
            'date_of_admission'      => $this->addDateOfAdmission,
            'roll_no'                => $rollNo,
            'transportation_required'=> (bool) $this->addTransportation,
            'appar_id'               => $this->addApparId ?: null,
            'registration_number'    => $this->addRegNo ?: null,
        ]);

        $this->syncStudentRoute($detail, (bool) $this->addTransportation, $this->addRoute, $this->addOrgId);

        try {
            $templateKey = config('services.zeptomail.student_password_template_key');
            if ($templateKey) {
                ZeptoMailService::sendTemplate($templateKey, $this->addEmail, $this->addName, [
                    'password'         => $plainPassword,
                    'school_name'      => $org->name,
                    'admission_number' => $admissionNo,
                    'username'         => $this->addName,
                    'name'             => $this->addName,
                    'email'            => $this->addEmail,
                    'login_url'        => url('/login'),
                ]);
                Log::info('SuperAdmin: student welcome email sent', ['email' => $this->addEmail]);
            } else {
                Log::warning('ZEPTOMAIL_STUDENT_PASSWORD_TEMPLATE_KEY not configured — skipping welcome email.');
            }
        } catch (\Throwable $e) {
            Log::error('Student welcome email failed', ['email' => $this->addEmail, 'error' => $e->getMessage()]);
        }

        $this->closeAddPanel();
        $this->loadStats();
        $this->resetPage();
        $this->notification()->success('Student Added!', $this->addName . ' added to ' . $org->name . '.');
    }

    private function generateAdmissionNo(Organization $org, string $standardId, string $sectionId): string
    {
        $year       = date('Y');
        $schoolCode = $org->school_code ?? 'SCH';
        $cls        = $standardId ?: '0';
        $sec        = $sectionId ?: '0';

        $last = StudentDetail::where('organization_id', $org->id)
            ->where('admission_no', 'like', "$year$schoolCode$cls$sec%")
            ->orderBy('admission_no', 'desc')
            ->first();

        $serial = $last ? (int) substr($last->admission_no, -4) + 1 : 1;
        return $year . $schoolCode . $cls . $sec . str_pad($serial, 4, '0', STR_PAD_LEFT);
    }

    private function generateRollNo(string $standardId, string $sectionId): string
    {
        $shortYear    = substr(date('Y'), -2);
        $schoolSerial = '01';
        $classFmt     = str_pad($standardId ?: '0', 2, '0', STR_PAD_LEFT);
        $sectionCode  = $sectionId ? substr($sectionId, 0, 1) : '0';

        $last = StudentDetail::where('organization_id', $this->addOrgId)
            ->where('standard_id', $standardId ?: null)
            ->where('section_id', $sectionId ?: null)
            ->where('roll_no', 'like', "$shortYear$schoolSerial$classFmt$sectionCode%")
            ->orderBy('roll_no', 'desc')
            ->first();

        $serial = $last ? (int) substr($last->roll_no, -3) + 1 : 1;
        return $shortYear . $schoolSerial . $classFmt . $sectionCode . str_pad($serial, 3, '0', STR_PAD_LEFT);
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $students = $this->baseQuery()->latest()->paginate($this->perPage);
        return view('livewire.super-admin.student', compact('students'));
    }
}
