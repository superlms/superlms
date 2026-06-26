<?php

namespace App\Livewire\SuperAdmin;

use App\Helpers\CityGetHelper;
use App\Models\Organization;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\ZeptoMailService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Teacher extends Component
{
    use WireUiActions, WithPagination;

    public int $totalSchools      = 0;
    public int $totalTeachers     = 0;
    public int $activeTeachers    = 0;
    public int $inactiveTeachers  = 0;

    public bool   $showViewModal  = false;
    public string $viewModalTitle = '';
    public array  $viewData       = [];
    public        $teacherImageUrl = null;

    public string $search             = '';
    public string $filterOrganization = '';
    public string $filterGender       = '';
    public string $filterStatus       = '';
    public int    $perPage            = 50;

    public $organizations = [];

    // ─── Add Teacher Panel ────────────────────────────────────────────────────
    public bool   $showAddPanel       = false;
    public string $addOrgId           = '';
    public string $addName            = '';
    public string $addEmail           = '';
    public string $addMobile          = '';
    public string $addDob             = '';
    public string $addGender          = '';
    public string $addEmployeeId      = '';
    public string $addDateOfJoining   = '';
    public string $addQualification   = '';
    public string $addAddress         = '';
    public string $addPincode         = '';
    public string $addEmergencyContact = '';
    public string $addState           = '';
    public string $addCity            = '';
    public        $addStates          = [];
    public        $addCities          = [];

    // ─── Delete Confirm ───────────────────────────────────────────────────────
    public bool $showDeleteConfirm = false;
    public      $deleteTargetId    = null;

    // ─── Edit Teacher Panel ───────────────────────────────────────────────────
    public bool   $showEditPanel          = false;
    public        $editDetailId           = null;
    public        $editUserId             = null;
    public string $editOrgId              = '';
    public string $editName               = '';
    public string $editEmail              = '';
    public string $editMobile             = '';
    public string $editDob                = '';
    public string $editGender             = '';
    public string $editEmployeeId         = '';
    public string $editDateOfJoining      = '';
    public string $editQualification      = '';
    public string $editAddress            = '';
    public string $editPincode            = '';
    public string $editEmergencyContact   = '';
    public string $editState              = '';
    public string $editCity               = '';
    public        $editStates             = [];
    public        $editCities             = [];

    protected $queryString = [
        'search'             => ['except' => ''],
        'filterOrganization' => ['except' => ''],
        'filterGender'       => ['except' => ''],
        'filterStatus'       => ['except' => ''],
    ];

    protected $listeners = ['onViewTeacherSuperAdmin' => 'viewTeacher', 'onDeleteTeacherSuperAdmin'];


    public function mount(): void
    {
        $this->organizations = Organization::orderBy('name')->get();
        $this->loadStats();
    }


    private function baseQuery()
    {
        return TeacherDetail::with(['user', 'user.organization', 'assignedSubjects.subject'])
            ->when($this->filterOrganization, fn($q) => $q->whereHas('user', fn($q) => $q->where('organization_id', $this->filterOrganization)))
            ->when($this->search, fn($q) => $q->where(fn($q) => $q
                ->where('employee_id', 'like', "%{$this->search}%")
                ->orWhere('qualification', 'like', "%{$this->search}%")
                ->orWhereHas('user', fn($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('mobile_number', 'like', "%{$this->search}%")
                )
            ))
            ->when($this->filterGender, fn($q) => $q->whereHas('user', fn($q) => $q->where('gender', $this->filterGender)))
            ->when($this->filterStatus !== '', fn($q) => $q->whereHas('user', fn($q) => $q->where('is_active', $this->filterStatus)));
    }

    private function loadStats(): void
    {
        $this->totalSchools     = Organization::count();
        $this->totalTeachers    = $this->baseQuery()->count();
        $this->activeTeachers   = $this->baseQuery()->whereHas('user', fn($q) => $q->where('is_active', 1))->count();
        $this->inactiveTeachers = $this->baseQuery()->whereHas('user', fn($q) => $q->where('is_active', 0))->count();
    }


    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->loadStats();
    }

    public function updatedFilterOrganization(): void
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
        $this->reset(['search', 'filterOrganization', 'filterGender', 'filterStatus']);
        $this->resetPage();
        $this->loadStats();
    }

    // ─── View ─────────────────────────────────────────────────────────────────

    public function viewTeacher($id): void
    {
        $user = User::with([
            'teacherDetail',
            'teacherDetail.assignedSubjects.subject',
            'organization',
        ])->find($id);

        if (!$user) {
            $this->notification()->error('Teacher not found!');
            return;
        }

        $detail   = $user->teacherDetail ?? null;
        $subjects = $detail?->assignedSubjects?->count()
            ? $detail->assignedSubjects->pluck('subject.name')->filter()->implode(', ')
            : '—';

        $this->teacherImageUrl = $user->image ?? null;
        $this->viewModalTitle  = 'Teacher Details';
        $this->viewData        = [
            'user'        => $user,
            'detail'      => $detail,
            'subjects'    => $subjects,
            'school_name' => $user->organization?->name ?? '—',
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

    public function onDeleteTeacherSuperAdmin($id): void
    {
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    public function doDeleteTeacher($id): void
    {
        $detail = TeacherDetail::find($id);
        if ($detail) {
            $user = User::find($detail->user_id);
            $detail->delete();
            $user?->delete();
            $this->showDeleteConfirm = false;
            $this->deleteTargetId    = null;
            $this->loadStats();
            $this->resetPage();
            $this->notification()->success('Teacher deleted successfully!');
        } else {
            $this->notification()->error('Teacher not found!');
        }
    }

    // ─── Edit Teacher Panel ────────────────────────────────────────────────────

    public function openEditPanel($id): void
    {
        $detail = TeacherDetail::with(['user'])->find($id);
        if (!$detail) {
            $this->notification()->error('Teacher not found!');
            return;
        }

        $this->editStates = (new CityGetHelper())->getState();

        $this->editDetailId         = $detail->id;
        $this->editUserId           = $detail->user_id;
        $this->editOrgId            = (string) ($detail->organization_id ?? '');
        $this->editName             = $detail->user?->name ?? '';
        $this->editEmail            = $detail->user?->email ?? '';
        $this->editMobile           = $detail->user?->mobile_number ?? '';
        $this->editDob              = $detail->user?->dob ?? '';
        $this->editGender           = $detail->user?->gender ?? '';
        $this->editEmployeeId       = $detail->employee_id ?? '';
        $this->editDateOfJoining    = $detail->date_of_joining
            ? \Carbon\Carbon::parse($detail->date_of_joining)->format('Y-m-d')
            : '';
        $this->editQualification    = $detail->qualification ?? '';
        $this->editAddress          = $detail->address ?? '';
        $this->editState            = $detail->state ?? '';
        $this->editCity             = $detail->city ?? '';
        $this->editPincode          = $detail->pincode ?? '';
        $this->editEmergencyContact = $detail->emergency_contact ?? '';

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

    public function updatedEditState(): void
    {
        $this->editCity   = '';
        $this->editCities = $this->editState
            ? (new CityGetHelper())->cityGetByState($this->editState)
            : [];
    }

    public function saveEditTeacher(): void
    {
        $this->validate([
            'editOrgId'            => 'required|integer|exists:organizations,id',
            'editName'             => 'required|string|max:255',
            'editEmail'            => 'required|email|max:100|unique:users,email,' . $this->editUserId,
            'editMobile'           => 'required|digits:10',
            'editDob'              => 'required|date|before:today',
            'editGender'           => 'required|in:male,female,other',
            'editEmployeeId'       => 'required|string|max:50',
            'editDateOfJoining'    => 'required|date|before_or_equal:today',
            'editQualification'    => 'required|string|max:255',
            'editAddress'          => 'required|string|max:500',
            'editPincode'          => 'required|digits:6',
            'editEmergencyContact' => 'required|digits:10',
        ]);

        User::where('id', $this->editUserId)->update([
            'name'            => $this->editName,
            'email'           => $this->editEmail,
            'mobile_number'   => $this->editMobile,
            'dob'             => $this->editDob,
            'gender'          => $this->editGender,
            'organization_id' => $this->editOrgId,
        ]);

        TeacherDetail::where('id', $this->editDetailId)->update([
            'organization_id'   => $this->editOrgId,
            'employee_id'       => $this->editEmployeeId,
            'date_of_joining'   => $this->editDateOfJoining,
            'qualification'     => $this->editQualification,
            'phone'             => $this->editMobile,
            'address'           => $this->editAddress,
            'city'              => $this->editCity ?: null,
            'state'             => $this->editState ?: null,
            'pincode'           => $this->editPincode,
            'emergency_contact' => $this->editEmergencyContact,
        ]);

        $this->closeEditPanel();
        $this->loadStats();
        $this->resetPage();
        $this->notification()->success('Teacher updated successfully!');
    }

    // ─── Export ───────────────────────────────────────────────────────────────

    public function exportTeachers()
    {
        if (!$this->filterOrganization) return;

        $teachers = $this->baseQuery()->get();

        return response()->streamDownload(function () use ($teachers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'S.No', 'Name', 'Email', 'Mobile', 'Gender',
                'Employee ID', 'Date of Birth', 'Date of Joining',
                'Qualification', 'Emergency Contact',
                'Address', 'City', 'State', 'Pincode',
                'Subjects', 'School', 'Status',
            ]);

            foreach ($teachers as $i => $t) {
                $subjects = $t->assignedSubjects?->count()
                    ? $t->assignedSubjects->pluck('subject.name')->filter()->implode(', ')
                    : '';

                fputcsv($handle, [
                    $i + 1,
                    $t->user?->name ?? '',
                    $t->user?->email ?? '',
                    $t->user?->mobile_number ?? '',
                    ucfirst($t->user?->gender ?? ''),
                    $t->employee_id ?? '',
                    $t->user?->dob ?? '',
                    $t->date_of_joining ?? '',
                    $t->qualification ?? '',
                    $t->emergency_contact ?? '',
                    $t->address ?? '',
                    $t->city ?? '',
                    $t->state ?? '',
                    $t->pincode ?? '',
                    $subjects,
                    $t->user?->organization?->name ?? '',
                    ($t->user?->is_active) ? 'Active' : 'Inactive',
                ]);
            }
            fclose($handle);
        }, 'teachers_' . now()->format('Y-m-d_H-i-s') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    // ─── Add Teacher Panel ────────────────────────────────────────────────────

    public function openAddPanel(): void
    {
        $this->addStates          = (new CityGetHelper())->getState();
        $this->addOrgId           = '';
        $this->addName            = '';
        $this->addEmail           = '';
        $this->addMobile          = '';
        $this->addDob             = '';
        $this->addGender          = '';
        $this->addEmployeeId      = '';
        $this->addDateOfJoining   = now()->format('Y-m-d');
        $this->addQualification   = '';
        $this->addAddress         = '';
        $this->addPincode         = '';
        $this->addEmergencyContact = '';
        $this->addState           = '';
        $this->addCity            = '';
        $this->addCities          = [];
        $this->resetValidation();
        $this->showAddPanel       = true;
    }

    public function closeAddPanel(): void
    {
        $this->showAddPanel = false;
        $this->resetValidation();
    }

    public function updatedAddState(): void
    {
        $this->addCity   = '';
        $this->addCities = $this->addState
            ? (new CityGetHelper())->cityGetByState($this->addState)
            : [];
    }

    public function saveNewTeacher(): void
    {
        $this->validate([
            'addOrgId'            => 'required|integer|exists:organizations,id',
            'addName'             => 'required|string|max:255',
            'addEmail'            => 'required|email|max:100',
            'addMobile'           => 'required|digits:10',
            'addDob'              => 'required|date|before:today',
            'addGender'           => 'required|in:male,female,other',
            'addEmployeeId'       => 'required|string|max:50',
            'addDateOfJoining'    => 'required|date|before_or_equal:today',
            'addQualification'    => 'required|string|max:255',
            'addAddress'          => 'required|string|max:500',
            'addPincode'          => 'required|digits:6',
            'addEmergencyContact' => 'required|digits:10',
        ]);

        $existing = User::where('email', $this->addEmail)->where('role', 'teacher')->first();
        if ($existing) {
            $this->addError('addEmail', 'A teacher with this email already exists.');
            return;
        }

        $org           = Organization::findOrFail($this->addOrgId);
        $plainPassword = Str::upper(Str::random(4)) . rand(100, 999) . Str::random(3);

        $user = User::create([
            'name'            => $this->addName,
            'email'           => $this->addEmail,
            'mobile_number'   => $this->addMobile,
            'dob'             => $this->addDob,
            'gender'          => $this->addGender,
            'role'            => 'teacher',
            'is_active'       => true,
            'organization_id' => $this->addOrgId,
            'password'        => Hash::make($plainPassword),
        ]);

        TeacherDetail::create([
            'user_id'           => $user->id,
            'organization_id'   => $this->addOrgId,
            'employee_id'       => $this->addEmployeeId,
            'date_of_joining'   => $this->addDateOfJoining,
            'qualification'     => $this->addQualification,
            'phone'             => $this->addMobile,
            'address'           => $this->addAddress,
            'city'              => $this->addCity ?: null,
            'state'             => $this->addState ?: null,
            'pincode'           => $this->addPincode,
            'emergency_contact' => $this->addEmergencyContact,
        ]);

        try {
            $templateKey = config('services.zeptomail.teacher_password_template_key');
            if ($templateKey) {
                ZeptoMailService::sendTemplate($templateKey, $this->addEmail, $this->addName, [
                    'password'      => $plainPassword,
                    'email_address' => $this->addEmail,
                    'school_name'   => $org->name,
                    'username'      => $this->addName,
                    'name'          => $this->addName,
                    'login_url'     => url('/login'),
                ]);
                Log::info('SuperAdmin: teacher welcome email sent', ['email' => $this->addEmail]);
            } else {
                Log::warning('ZEPTOMAIL_TEACHER_PASSWORD_TEMPLATE_KEY not configured — skipping welcome email.');
            }
        } catch (\Throwable $e) {
            Log::error('Teacher welcome email failed', ['email' => $this->addEmail, 'error' => $e->getMessage()]);
        }

        $this->closeAddPanel();
        $this->loadStats();
        $this->resetPage();
        $this->notification()->success('Teacher Added!', $this->addName . ' added to ' . $org->name . '.');
    }

    public function render()
    {
        $teachers = $this->baseQuery()->latest()->paginate($this->perPage);
        return view('livewire.super-admin.teacher', compact('teachers'));
    }
}