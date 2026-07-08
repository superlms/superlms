<?php

namespace App\Livewire\SuperAdmin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Users extends Component
{
    use WireUiActions, WithFileUploads, WithPagination;

    // ─── Slide-in panel state ────────────────────────────────────────────
    public bool $showPanel = false;
    public int  $step      = 1;          // 1 = personal details, 2 = permissions
    public ?int $editId    = null;

    // ─── Personal details ────────────────────────────────────────────────
    public $fullName          = '';
    public $email             = '';
    public $mobile            = '';
    public $alternativeMobile = '';
    public $address           = '';
    public $dob               = '';
    public $dateOfJoining     = '';
    public $gender            = '';
    public $isActive          = 1;

    // ─── Image upload ────────────────────────────────────────────────────
    public $image    = null;   // new upload
    public $imageUrl = null;   // existing url (edit)

    // ─── Permissions ─────────────────────────────────────────────────────
    public array $permissions = [];

    // Organization scope: '' = all organizations, otherwise an organization id
    public $allowedOrgId = '';

    // ─── View panel ──────────────────────────────────────────────────────
    public bool $showViewPanel = false;
    public array $viewData      = [];

    // ─── Delete overlay ──────────────────────────────────────────────────
    public bool $showDeleteConfirm = false;
    public ?int $deleteTargetId    = null;

    // ─── Search ──────────────────────────────────────────────────────────
    public string $search       = '';
    public string $filterStatus = '';

    protected $queryString = [
        'search'       => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterStatus']);
        $this->resetPage();
    }

    /**
     * The catalog of super-admin functionalities that can be granted, derived
     * from the super-admin menu (excluding this Users screen itself).
     *
     * @return array<string,string>  [routeName => title]
     */
    public function permissionCatalog(): array
    {
        return collect(config('menu.super-admin', []))
            ->reject(fn($i) => ($i['link'] ?? '') === 'super-admin.users')
            ->mapWithKeys(fn($i) => [$i['link'] => $i['title']])
            ->all();
    }

    // ─── Panel controls ──────────────────────────────────────────────────
    public function openCreate(): void
    {
        $this->resetForm();
        $this->step      = 1;
        $this->showPanel = true;
    }

    public function nextStep(): void
    {
        $this->validateStepOne();
        $this->step = 2;
    }

    public function backStep(): void
    {
        $this->step = 1;
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    protected function validateStepOne(): void
    {
        $rules = [
            'fullName'          => 'required|string|max:255',
            'email'             => 'required|email|max:191',
            'mobile'            => 'required|digits:10',
            'alternativeMobile' => 'nullable|digits:10',
            'address'           => 'nullable|string|max:500',
            'dob'               => 'nullable|date|before:today',
            'dateOfJoining'     => 'nullable|date|before_or_equal:today',
            'gender'            => 'required|in:male,female,other',
            'image'             => 'nullable|image|max:2048',
        ];

        if ($this->editId) {
            $rules['email'] .= '|unique:users,email,' . $this->editId;
        } else {
            $rules['email'] .= '|unique:users,email';
        }

        $this->validate($rules, [
            'mobile.digits'            => 'Mobile number must be exactly 10 digits.',
            'alternativeMobile.digits' => 'Alternative mobile must be exactly 10 digits.',
        ]);
    }

    // ─── Save ────────────────────────────────────────────────────────────
    public function save(): void
    {
        // Re-validate personal details (guards against skipping the step).
        // If it fails, jump back to step 1 so the errors are visible.
        try {
            $this->validateStepOne();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->step = 1;
            throw $e;
        }

        if (empty($this->permissions)) {
            $this->notification()->error('Select Access', 'Grant at least one functionality to this user.');
            return;
        }

        // Organization scope must be "all" or a real organization
        $this->validate([
            'allowedOrgId' => 'nullable|exists:organizations,id',
        ], [], ['allowedOrgId' => 'organization']);

        // Only allow permissions that exist in the catalog
        $allowed = array_keys($this->permissionCatalog());
        $grants  = array_values(array_intersect($allowed, $this->permissions));

        try {
            $isEdit        = (bool) $this->editId;
            $plainPassword = null;
            $user          = $isEdit ? User::findOrFail($this->editId) : new User();

            if ($this->image) {
                if ($user->image) {
                    Storage::disk('s3')->delete(parse_url($user->image, PHP_URL_PATH));
                }
                $path = $this->image->store('super-admin/users/images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $user->image = Storage::disk('s3')->url($path);
            }

            $user->name                    = $this->fullName;
            $user->email                   = $this->email;
            $user->mobile_number           = $this->mobile;
            $user->alternative_mobile      = $this->alternativeMobile ?: null;
            $user->address                 = $this->address ?: null;
            $user->dob                     = $this->dob ?: null;
            $user->gender                  = $this->gender;
            $user->date_of_joining         = $this->dateOfJoining ?: null;
            $user->role                    = 'sub-super-admin';
            $user->is_active               = (int) $this->isActive;
            $user->organization_id         = 0; // column is NOT NULL with default 0
            $user->allowed_organization_id = $this->allowedOrgId ?: null; // null = all organizations
            $user->permissions             = $grants;

            if (!$isEdit) {
                $plainPassword  = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789@#$!'), 0, 10);
                $user->password = Hash::make($plainPassword);
            }

            $user->save();

            // Send credentials on creation only — never blocks the save
            if (!$isEdit && $plainPassword) {
                $this->sendCredentialsEmail($user, $plainPassword);
            }

            $this->notification()->success(
                $isEdit ? 'User Updated' : 'User Created',
                $isEdit ? 'Sub super-admin updated successfully.' : 'Sub super-admin created and credentials emailed.'
            );

            $this->closePanel();
            // Clear any leftover list filter so the saved user is visible
            // (the search box used to pick up the new user's email).
            $this->reset(['search', 'filterStatus']);
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->notification()->error('Error Saving User', $e->getMessage());
            logger()->error('Sub super-admin save error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    protected function sendCredentialsEmail(User $user, string $plainPassword): void
    {
        try {
            $templateKey = config('services.zeptomail.sub_super_admin_password_template_key')
                ?: config('services.zeptomail.teacher_password_template_key');
            if (!$templateKey) {
                logger()->warning('No password template key configured — skipping sub super-admin credentials email.');
                return;
            }

            \App\Services\ZeptoMailService::sendTemplate(
                $templateKey,
                $user->email,
                $user->name,
                [
                    'password'      => $plainPassword,
                    'email_address' => $user->email,
                    'school_name'   => 'SUPERLMS',
                    'username'      => $user->name,
                    'name'          => $user->name,
                    'login_url'     => route('super-admin.login'),
                ]
            );
            logger()->info('Sub super-admin credentials emailed to: ' . $user->email);
        } catch (\Throwable $e) {
            logger()->error('Sub super-admin credentials email failed for ' . $user->email . ': ' . $e->getMessage());
        }
    }

    // ─── Edit ────────────────────────────────────────────────────────────
    public function edit(int $id): void
    {
        $user = User::where('role', 'sub-super-admin')->find($id);
        if (!$user) {
            $this->notification()->error('Not found', 'User not found.');
            return;
        }

        $this->editId            = $user->id;
        $this->fullName          = (string) ($user->name ?? '');
        $this->email             = (string) ($user->email ?? '');
        $this->mobile            = (string) ($user->mobile_number ?? '');
        $this->alternativeMobile = (string) ($user->alternative_mobile ?? '');
        $this->address           = (string) ($user->address ?? '');
        $this->dob               = $user->dob ? \Carbon\Carbon::parse($user->dob)->format('Y-m-d') : '';
        $this->dateOfJoining     = $user->date_of_joining ? \Carbon\Carbon::parse($user->date_of_joining)->format('Y-m-d') : '';
        $this->gender            = (string) ($user->gender ?? '');
        $this->isActive          = (int) ($user->is_active ?? 0);
        $this->imageUrl          = $user->image;
        $this->image             = null;
        $this->permissions       = (array) $user->permissions;
        $this->allowedOrgId      = $user->allowed_organization_id ? (string) $user->allowed_organization_id : '';

        $this->step      = 1;
        $this->showPanel = true;
    }

    // ─── View ────────────────────────────────────────────────────────────
    public function view(int $id): void
    {
        $user = User::where('role', 'sub-super-admin')->find($id);
        if (!$user) {
            $this->notification()->error('Not found', 'User not found.');
            return;
        }

        $catalog = $this->permissionCatalog();
        $this->viewData = [
            'name'               => $user->name,
            'email'              => $user->email,
            'mobile'             => $user->mobile_number,
            'alternative_mobile' => $user->alternative_mobile,
            'address'            => $user->address,
            'dob'                => $user->dob ? \Carbon\Carbon::parse($user->dob)->format('d M Y') : '—',
            'date_of_joining'    => $user->date_of_joining ? \Carbon\Carbon::parse($user->date_of_joining)->format('d M Y') : '—',
            'gender'             => $user->gender,
            'is_active'          => (bool) $user->is_active,
            'image'              => $user->image,
            'last_login_at'      => $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'Never',
            'organization'       => $user->allowed_organization_id
                ? (\App\Models\Organization::find($user->allowed_organization_id)?->name ?? 'Organization #' . $user->allowed_organization_id)
                : 'All Organizations',
            'permissions'        => collect((array) $user->permissions)
                ->map(fn($p) => $catalog[$p] ?? $p)
                ->all(),
        ];
        $this->showViewPanel = true;
    }

    public function closeViewPanel(): void
    {
        $this->showViewPanel = false;
        $this->viewData      = [];
    }

    // ─── Toggle status ───────────────────────────────────────────────────
    public function toggleStatus(int $id): void
    {
        $user = User::where('role', 'sub-super-admin')->find($id);
        if ($user) {
            $user->is_active = $user->is_active ? 0 : 1;
            $user->save();
            $this->notification()->success('Status Updated', $user->is_active ? 'User activated.' : 'User deactivated.');
        }
    }

    // ─── Delete ──────────────────────────────────────────────────────────
    public function confirmDeletePrompt(int $id): void
    {
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->deleteTargetId    = null;
        $this->showDeleteConfirm = false;
    }

    public function executeDelete(): void
    {
        $user = User::where('role', 'sub-super-admin')->find($this->deleteTargetId);
        if ($user) {
            if ($user->image) {
                Storage::disk('s3')->delete(parse_url($user->image, PHP_URL_PATH));
            }
            $user->delete();
            $this->notification()->success('User Deleted', 'Sub super-admin removed successfully.');
        }
        $this->deleteTargetId    = null;
        $this->showDeleteConfirm = false;
        $this->resetPage();
    }

    // ─── Permission bulk select ──────────────────────────────────────────
    public function selectAllPermissions(): void
    {
        $this->permissions = array_keys($this->permissionCatalog());
    }

    public function deselectAllPermissions(): void
    {
        $this->permissions = [];
    }

    // ─── Reset ───────────────────────────────────────────────────────────
    protected function resetForm(): void
    {
        $this->reset([
            'editId', 'fullName', 'email', 'mobile', 'alternativeMobile', 'address',
            'dob', 'dateOfJoining', 'gender', 'image', 'imageUrl', 'permissions',
            'allowedOrgId',
        ]);
        $this->isActive = 1;
        $this->step     = 1;
        $this->resetValidation();
    }

    // ─── Render ──────────────────────────────────────────────────────────
    public function render()
    {
        $users = User::where('role', 'sub-super-admin')
            ->when($this->search, fn($q) => $q->where(fn($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('mobile_number', 'like', "%{$this->search}%")))
            ->when($this->filterStatus !== '', fn($q) => $q->where('is_active', $this->filterStatus))
            ->latest()
            ->paginate(10);

        $analytics = [
            'total'    => User::where('role', 'sub-super-admin')->count(),
            'active'   => User::where('role', 'sub-super-admin')->where('is_active', 1)->count(),
            'inactive' => User::where('role', 'sub-super-admin')->where('is_active', 0)->count(),
        ];

        return view('livewire.super-admin.users', [
            'users'         => $users,
            'analytics'     => $analytics,
            'catalog'       => $this->permissionCatalog(),
            'organizations' => \App\Models\Organization::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
