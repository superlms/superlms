<?php

namespace App\Livewire\SuperAdmin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class Profile extends Component
{
    use WithFileUploads, WireUiActions;

    public string $activeTab = 'profile';

    // Photo
    public $photo;
    public $tempPhotoUrl;

    // Password
    public $currentPassword;
    public $newPassword;
    public $confirmPassword;
    public bool $showCurrentPassword = false;
    public bool $showNewPassword     = false;
    public bool $showConfirmPassword = false;

    public function showTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function togglePasswordVisibility(string $field): void
    {
        if ($field === 'current') {
            $this->showCurrentPassword = !$this->showCurrentPassword;
        } elseif ($field === 'new') {
            $this->showNewPassword = !$this->showNewPassword;
        } elseif ($field === 'confirm') {
            $this->showConfirmPassword = !$this->showConfirmPassword;
        }
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['image', 'max:2048']]);
        $this->tempPhotoUrl = $this->photo->temporaryUrl();
    }

    public function savePhoto(): void
    {
        $this->validate(['photo' => ['required', 'image', 'max:2048']]);

        $user = Auth::user();

        if ($user->image) {
            Storage::disk('s3')->delete(parse_url($user->image, PHP_URL_PATH));
        }

        $path = $this->photo->store('super-admin/profile/photos', 's3');
        Storage::disk('s3')->setVisibility($path, 'public');
        $user->update(['image' => Storage::disk('s3')->url($path)]);

        $this->reset('photo', 'tempPhotoUrl');
        $this->notification()->success('Profile photo updated successfully!');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'newPassword'     => [
                'required',
                'different:currentPassword',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'confirmPassword' => ['required', 'same:newPassword'],
        ]);

        Auth::user()->update(['password' => Hash::make($this->newPassword)]);

        $this->reset(['currentPassword', 'newPassword', 'confirmPassword']);
        $this->notification()->success('Password updated successfully!');
    }

    public function render()
    {
        $user = Auth::user();

        // For sub-super-admins, show the functionalities they were granted
        $grantedAccess = [];
        if ($user->role === 'sub-super-admin') {
            $catalog = collect(config('menu.super-admin', []))
                ->mapWithKeys(fn($i) => [$i['link'] => $i['title']]);
            $grantedAccess = collect((array) $user->permissions)
                ->map(fn($p) => $catalog[$p] ?? $p)
                ->all();
        }

        return view('livewire.super-admin.profile', [
            'user'          => $user,
            'grantedAccess' => $grantedAccess,
        ]);
    }
}
