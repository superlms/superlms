<?php

namespace App\Livewire\Admin;

use App\Models\Admin\SchoolUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class AccountUsers extends Component
{
    use WireUiActions, WithPagination, WithFileUploads;

    public bool $showPanel = false;      // add / edit slide-in
    public bool $showViewPanel = false;  // view slide-in
    public bool $isEditing = false;
    public ?int $editUserId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $mobile_number = '';
    public string $alternate_mobile = '';
    public string $designation = '';
    public string $department = 'accounts';
    public string $employee_id = '';
    public string $address = '';

    public string $search = '';
    public string $filterStatus = '';

    public array $viewData = [];

    public $userImage = null;
    public ?string $existingImage = null;

    protected function rules(): array
    {
        $emailRule = $this->isEditing
            ? 'required|email|unique:users,email,' . $this->editUserId
            : 'required|email|unique:users,email';

        $passwordRule = $this->isEditing
            ? 'nullable|min:8|max:16'
            : 'required|min:8|max:16';

        return [
            'name'             => 'required|string|max:255',
            'email'            => $emailRule,
            'password'         => $passwordRule,
            'mobile_number'    => 'nullable|string|max:15',
            'alternate_mobile' => 'nullable|string|max:15',
            'designation'      => 'nullable|string|max:255',
            'department'       => 'nullable|string|max:255',
            'employee_id'      => 'nullable|string|max:255',
            'address'          => 'nullable|string|max:500',
            'userImage'        => 'nullable|image|max:2048', // max 2MB
        ];
    }

    protected $messages = [
        'userImage.max'   => 'Image must not be larger than 2MB.',
        'userImage.image' => 'The file must be a valid image.',
    ];

    private function orgId()
    {
        return Auth::user()->organization_id;
    }

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
        $this->search = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    public function openAdd()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showPanel = true;
    }

    public function editUser($userId)
    {
        $user = User::with('schoolUser')->find($userId);
        if (!$user || $user->organization_id !== $this->orgId()) {
            return;
        }

        $this->isEditing     = true;
        $this->editUserId    = $user->id;
        $this->name          = $user->name;
        $this->email         = $user->email;
        $this->mobile_number = $user->mobile_number ?? '';
        $this->alternate_mobile = $user->schoolUser->alternate_mobile ?? '';
        $this->designation   = $user->schoolUser->designation ?? '';
        $this->department    = $user->schoolUser->department ?? 'accounts';
        $this->employee_id   = $user->schoolUser->employee_id ?? '';
        $this->address       = $user->schoolUser->address ?? '';
        $this->existingImage = $user->schoolUser->image ?? null;
        $this->password      = '';
        $this->userImage     = null;
        $this->resetErrorBag();
        $this->showPanel     = true;
    }

    public function viewUser($userId)
    {
        $user = User::with('schoolUser')->find($userId);
        if (!$user || $user->organization_id !== $this->orgId()) {
            return;
        }

        $this->viewData = [
            'name'             => $user->name,
            'email'            => $user->email,
            'mobile_number'    => $user->mobile_number,
            'alternate_mobile' => $user->schoolUser->alternate_mobile ?? null,
            'designation'      => $user->schoolUser->designation ?? null,
            'department'       => $user->schoolUser->department ?? null,
            'employee_id'      => $user->schoolUser->employee_id ?? null,
            'address'          => $user->schoolUser->address ?? null,
            'image'            => $user->schoolUser->image ?? null,
            'is_active'        => (bool) $user->is_active,
        ];
        $this->showViewPanel = true;
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    public function closeViewPanel(): void
    {
        $this->showViewPanel = false;
        $this->viewData = [];
    }

    public function saveUser()
    {
        $this->validate();

        if ($this->isEditing) {
            $user = User::find($this->editUserId);
            if (!$user || $user->organization_id !== $this->orgId()) {
                return;
            }

            $userData = [
                'name'          => $this->name,
                'email'         => $this->email,
                'mobile_number' => $this->mobile_number ?: null,
            ];

            if ($this->password) {
                $userData['password'] = Hash::make($this->password);
            }

            $user->update($userData);

            $schoolUserData = [
                'organization_id'  => $this->orgId(),
                'designation'      => $this->designation ?: null,
                'department'       => $this->department ?: 'accounts',
                'employee_id'      => $this->employee_id ?: null,
                'alternate_mobile' => $this->alternate_mobile ?: null,
                'address'          => $this->address ?: null,
            ];

            // Handle image upload
            if ($this->userImage) {
                $schoolUser = $user->schoolUser;
                if ($schoolUser && $schoolUser->image) {
                    Storage::disk('s3')->delete(parse_url($schoolUser->image, PHP_URL_PATH));
                }
                $path = $this->userImage->store('admin/account-users/images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $schoolUserData['image'] = Storage::disk('s3')->url($path);
            }

            $user->schoolUser()->updateOrCreate(
                ['user_id' => $user->id],
                $schoolUserData
            );

            $this->notification()->success('User Updated', 'Account user updated successfully.');
        } else {
            $user = User::create([
                'name'            => $this->name,
                'email'           => $this->email,
                'password'        => Hash::make($this->password),
                'mobile_number'   => $this->mobile_number ?: null,
                'organization_id' => $this->orgId(),
                'role'            => 'accounts',
                'is_active'       => true,
            ]);

            $schoolUserData = [
                'user_id'          => $user->id,
                'organization_id'  => $this->orgId(),
                'designation'      => $this->designation ?: null,
                'department'       => $this->department ?: 'accounts',
                'employee_id'      => $this->employee_id ?: null,
                'alternate_mobile' => $this->alternate_mobile ?: null,
                'address'          => $this->address ?: null,
                'is_active'        => true,
            ];

            // Handle image upload
            if ($this->userImage) {
                $path = $this->userImage->store('admin/account-users/images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $schoolUserData['image'] = Storage::disk('s3')->url($path);
            }

            SchoolUser::create($schoolUserData);

            $this->notification()->success('User Created', 'Account user created successfully.');
        }

        $this->showPanel = false;
        $this->resetForm();
    }

    public function removeImage()
    {
        if ($this->isEditing && $this->existingImage) {
            $user = User::with('schoolUser')->find($this->editUserId);
            if ($user && $user->schoolUser && $user->schoolUser->image) {
                Storage::disk('s3')->delete(parse_url($user->schoolUser->image, PHP_URL_PATH));
                $user->schoolUser->update(['image' => null]);
            }
            $this->existingImage = null;
        }
        $this->userImage = null;
    }

    public function toggleActive($userId)
    {
        $user = User::with('schoolUser')->find($userId);
        if (!$user || $user->organization_id !== $this->orgId()) {
            return;
        }

        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        if ($user->schoolUser) {
            $user->schoolUser->update(['is_active' => $newStatus]);
        }

        $this->notification()->success(
            'Status Updated',
            $newStatus ? 'User activated.' : 'User deactivated.'
        );
    }

    private function resetForm()
    {
        $this->name             = '';
        $this->email            = '';
        $this->password         = '';
        $this->mobile_number    = '';
        $this->alternate_mobile = '';
        $this->designation      = '';
        $this->department       = 'accounts';
        $this->employee_id      = '';
        $this->address          = '';
        $this->userImage        = null;
        $this->existingImage    = null;
        $this->editUserId       = null;
        $this->isEditing        = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $base = User::where('organization_id', $this->orgId())->where('role', 'accounts');

        $analytics = [
            'total'    => (clone $base)->count(),
            'active'   => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
        ];

        $users = (clone $base)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus !== '', function ($query) {
                $query->where('is_active', (bool) $this->filterStatus);
            })
            ->with('schoolUser')
            ->latest()
            ->paginate(10);

        return view('livewire.admin.account-users', [
            'users'     => $users,
            'analytics' => $analytics,
        ]);
    }
}
