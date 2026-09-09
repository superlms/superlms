<?php

namespace App\Livewire\Accounts;

use App\Models\Admin\SchoolUser;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class Profile extends Component
{
    use WireUiActions;

    public $user = [];
    public $schoolUser = [];
    public $organization = [];

    public function mount(): void
    {
        $authUser = Auth::user();
        $orgId    = $this->orgId();

        $schoolUserRecord = SchoolUser::where('user_id', $authUser->id)
            ->where('organization_id', $orgId)
            ->first();

        // Prefer the user account photo, fall back to the staff record photo
        $avatar = $authUser->image ?: ($schoolUserRecord->image ?? null);

        $this->user = [
            'name'          => $authUser->name,
            'email'         => $authUser->email,
            'role'          => $authUser->role,
            'phone'         => $authUser->mobile_number ?? '-',
            'created_at'    => $authUser->created_at?->format('d M Y'),
            'image'         => $avatar,
            'last_login_at' => $authUser->last_login_at
                ? $authUser->last_login_at->timezone('Asia/Kolkata')->format('d M Y, h:i A')
                : null,
        ];

        if ($schoolUserRecord) {
            $this->schoolUser = [
                'employee_id'      => $schoolUserRecord->employee_id ?? '-',
                'designation'      => $schoolUserRecord->designation ?? '-',
                'department'       => $schoolUserRecord->department ?? '-',
                'alternate_mobile' => $schoolUserRecord->alternate_mobile ?? '-',
                'address'          => $schoolUserRecord->address ?? '-',
                'is_active'        => $schoolUserRecord->is_active,
                'image'            => $avatar,
            ];
        }

        $org = Organization::find($orgId);
        if ($org) {
            $this->organization = [
                'name'               => $org->name ?? '-',
                'email'              => $org->email ?? '-',
                'phone'              => $org->mobile_number ?? '-',
                'address'            => $org->address ?? '-',
                'state'              => $org->state ?? '-',
                'logo'               => $org->logo ?? null,
                'school_code'        => $org->school_code ?? '-',
                'affiliation_number' => $org->affiliation_no ?? '-',
                'serial_number'      => $org->serial_number ?? '-',
                'udise_number'       => $org->udise_number ?? '-',
                'board'              => $org->education_board ?? '-',
                'created_at'         => $org->created_at?->format('d M Y'),
            ];
        }
    }

    // ─── Change password ─────────────────────────────────────────────────
    // Same flow the admin profile uses, so a change made here behaves exactly
    // as it does there — including keeping the recoverable copy in step.

    public bool $showPasswordPanel = false;
    public $currentPassword;
    public $newPassword;
    public $confirmPassword;
    public bool $showCurrentPassword = false;
    public bool $showNewPassword     = false;
    public bool $showConfirmPassword = false;

    public function openPasswordPanel(): void
    {
        $this->reset(['currentPassword', 'newPassword', 'confirmPassword']);
        $this->resetErrorBag();
        $this->showPasswordPanel = true;
    }

    public function closePasswordPanel(): void
    {
        $this->reset(['currentPassword', 'newPassword', 'confirmPassword']);
        $this->resetErrorBag();
        $this->showPasswordPanel = false;
    }

    public function togglePasswordVisibility($field): void
    {
        if ($field === 'current') {
            $this->showCurrentPassword = !$this->showCurrentPassword;
        } elseif ($field === 'new') {
            $this->showNewPassword = !$this->showNewPassword;
        } elseif ($field === 'confirm') {
            $this->showConfirmPassword = !$this->showConfirmPassword;
        }
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'newPassword' => [
                'required',
                'different:currentPassword',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            'confirmPassword' => ['required', 'same:newPassword'],
        ]);

        // Keeps users.password_plain in step, the way every other password path does.
        Auth::user()->rememberPlainPassword($this->newPassword);
        Auth::user()->update([
            'password' => Hash::make($this->newPassword)
        ]);

        $this->reset(['currentPassword', 'newPassword', 'confirmPassword']);
        $this->showPasswordPanel = false;
        $this->notification()->success('Password updated.');
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function render()
    {
        return view('livewire.accounts.profile');
    }
}
