<?php

namespace App\Livewire\SuperAdmin;

use App\Helpers\CityGetHelper;
use App\Models\Organization;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\ZeptoMailService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Teacher extends Component
{
    use WireUiActions, WithFileUploads, WithPagination;

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
    public        $addImage           = null;   // profile photo upload
    public        $addActive          = 0;      // Active (can log in) checkbox
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
    public        $editImage              = null;   // new profile photo upload
    public        $editImageUrl           = null;   // existing photo
    public        $editActive             = 0;      // Active (can log in) checkbox
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
            if ($user?->image) {
                Storage::disk('s3')->delete(parse_url($user->image, PHP_URL_PATH));
            }
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
        $this->editImage            = null;
        $this->editImageUrl         = $detail->user?->image;
        $this->editActive           = (int) ($detail->user?->is_active ?? 0);

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
        // Validation mirrors the admin Edit Teacher form exactly, plus the school select.
        $this->validate([
            'editOrgId'            => 'required|integer|exists:organizations,id',
            'editName'             => 'required|string|max:50|regex:/^[A-Za-z ]+$/',
            'editEmail'            => 'required|email:rfc|max:191|unique:users,email,' . $this->editUserId . ',id,role,teacher',
            'editMobile'           => 'required|digits:10',
            'editDob'              => 'required|date|before:today',
            'editGender'           => 'required|string|in:male,female,other',
            'editEmployeeId'       => 'required|string|max:20',
            'editDateOfJoining'    => 'required|date|before_or_equal:today',
            'editQualification'    => 'required|string|max:50',
            'editAddress'          => 'required|string|max:1000',
            'editPincode'          => 'required|digits:6',
            'editEmergencyContact' => 'required|digits:10',
            'editImage'            => 'nullable|image|max:1024', // 1 MB
        ], [
            'editName.regex'             => 'Name may contain only letters and spaces.',
            'editMobile.digits'          => 'Mobile number must be exactly 10 digits.',
            'editEmergencyContact.digits'=> 'Emergency contact must be exactly 10 digits.',
            'editPincode.digits'         => 'Pincode must be exactly 6 digits.',
            'editImage.max'              => 'Image must be 1 MB or smaller.',
        ]);

        $user     = User::find($this->editUserId);
        $oldEmail = $user?->email;

        $userData = [
            'name'            => $this->editName,
            'email'           => $this->editEmail,
            'mobile_number'   => $this->editMobile,
            'dob'             => $this->editDob,
            'gender'          => $this->editGender,
            'is_active'       => (int) $this->editActive,
            'organization_id' => $this->editOrgId,
        ];

        if ($this->editImage) {
            if ($user?->image) {
                Storage::disk('s3')->delete(parse_url($user->image, PHP_URL_PATH));
            }
            $path = $this->editImage->store('admin/teachers/images', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $userData['image'] = Storage::disk('s3')->url($path);
        }

        User::where('id', $this->editUserId)->update($userData);

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

        // Email changed → re-send credentials to the NEW address with the
        // SAME (unchanged) password, exactly like the admin module.
        if ($oldEmail && strcasecmp($oldEmail, $this->editEmail) !== 0) {
            try {
                $templateKey = config('services.zeptomail.teacher_password_template_key');
                if ($templateKey) {
                    $fresh = User::find($this->editUserId);
                    ZeptoMailService::sendTemplate($templateKey, $this->editEmail, $this->editName, [
                        'password'      => $fresh?->plainPassword() ?? 'Use your existing password (unchanged)',
                        'email_address' => $this->editEmail,
                        'school_name'   => Organization::find($this->editOrgId)?->name ?? 'School',
                        'username'      => $this->editName,
                        'name'          => $this->editName,
                        'login_url'     => url('/login'),
                    ]);
                    Log::info('SuperAdmin: teacher updated-email credentials sent', ['email' => $this->editEmail]);
                }
            } catch (\Throwable $e) {
                Log::error('SuperAdmin: teacher updated-email credentials failed', ['email' => $this->editEmail, 'error' => $e->getMessage()]);
            }
        }

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
        $this->addImage           = null;
        $this->addActive          = 0;
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
        // Validation mirrors the admin Add Teacher form exactly, plus the school select.
        $this->validate([
            'addOrgId'            => 'required|integer|exists:organizations,id',
            'addName'             => 'required|string|max:50|regex:/^[A-Za-z ]+$/',
            'addEmail'            => 'required|email:rfc|max:191',
            'addMobile'           => 'required|digits:10',
            'addDob'              => 'required|date|before:today',
            'addGender'           => 'required|string|in:male,female,other',
            'addEmployeeId'       => 'required|string|max:20',
            'addDateOfJoining'    => 'required|date|before_or_equal:today',
            'addQualification'    => 'required|string|max:50',
            'addAddress'          => 'required|string|max:1000',
            'addPincode'          => 'required|digits:6',
            'addEmergencyContact' => 'required|digits:10',
            'addImage'            => 'nullable|image|max:1024', // 1 MB
        ], [
            'addName.regex'             => 'Name may contain only letters and spaces.',
            'addMobile.digits'          => 'Mobile number must be exactly 10 digits.',
            'addEmergencyContact.digits'=> 'Emergency contact must be exactly 10 digits.',
            'addPincode.digits'         => 'Pincode must be exactly 6 digits.',
            'addImage.max'              => 'Image must be 1 MB or smaller.',
        ]);

        $existing = User::where('email', $this->addEmail)->first();
        if ($existing) {
            $this->addError('addEmail', 'This email is already used by another account.');
            return;
        }

        $org           = Organization::findOrFail($this->addOrgId);
        $plainPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789@#$!'), 0, 10);

        $userData = [
            'name'            => $this->addName,
            'email'           => $this->addEmail,
            'mobile_number'   => $this->addMobile,
            'dob'             => $this->addDob,
            'gender'          => $this->addGender,
            'role'            => 'teacher',
            'is_active'       => (int) $this->addActive,
            'organization_id' => $this->addOrgId,
            'password'        => Hash::make($plainPassword),
        ];
        if (Schema::hasColumn('users', 'password_plain')) {
            $userData['password_plain'] = \Illuminate\Support\Facades\Crypt::encryptString($plainPassword);
        }
        if ($this->addImage) {
            $path = $this->addImage->store('admin/teachers/images', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $userData['image'] = Storage::disk('s3')->url($path);
        }

        $user = User::create($userData);

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