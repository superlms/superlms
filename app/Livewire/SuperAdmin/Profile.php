<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Organization;
use App\Models\User;
use App\Support\OtpNumbers;
use App\Support\Platform;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class Profile extends Component
{
    use WithFileUploads, WireUiActions;

    /** What a logo may be, and how big. */
    private const LOGO_TYPES  = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
    private const LOGO_MAX_KB = 2048;

    // A tab switch is one step for the header's Back.
    #[Url(history: true, except: 'profile')]
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

    // OTP numbers: the one being added, and the one being edited ('p' = the
    // primary, else the row id) with its new value.
    public string $newNumber  = '';
    public ?string $editing   = null;
    public string $editNumber = '';

    // Platform logo
    public $logo;

    public function showTab(string $tab): void
    {
        if ($tab === 'platform' && !$this->isMainSuperAdmin()) {
            return;
        }
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

        Auth::user()->rememberPlainPassword($this->newPassword);
        Auth::user()->update(['password' => Hash::make($this->newPassword)]);

        $this->reset(['currentPassword', 'newPassword', 'confirmPassword']);
        $this->notification()->success('Password updated successfully!');
    }

    // ─── OTP numbers ──────────────────────────────────────────────────────────

    public function addNumber(): void
    {
        $this->resetValidation('newNumber');

        if ($error = OtpNumbers::add(Auth::user(), $this->newNumber)) {
            $this->addError('newNumber', $error);
            return;
        }

        $this->newNumber = '';
        $this->notification()->success('Number added.');
    }

    public function startEdit(string $key): void
    {
        $number = $this->numberFor($key);
        if (!$number) {
            return;
        }

        $this->resetValidation('editNumber');
        $this->editing    = $key;
        $this->editNumber = $number['phone'];
    }

    public function cancelEdit(): void
    {
        $this->editing = null;
        $this->editNumber = '';
        $this->resetValidation('editNumber');
    }

    public function saveEdit(): void
    {
        if ($this->editing === null) {
            return;
        }

        $this->resetValidation('editNumber');

        if ($error = OtpNumbers::update(Auth::user(), self::idOf($this->editing), $this->editNumber)) {
            $this->addError('editNumber', $error);
            return;
        }

        $this->cancelEdit();
        $this->notification()->success('Number updated.');
    }

    public function deleteNumber(string $key): void
    {
        if ($error = OtpNumbers::delete(Auth::user(), self::idOf($key))) {
            $this->notification()->error($error);
            return;
        }

        if ($this->editing === $key) {
            $this->cancelEdit();
        }
        $this->notification()->success('Number deleted.');
    }

    public function makePrimary(int $id): void
    {
        if ($error = OtpNumbers::makePrimary(Auth::user(), $id)) {
            $this->notification()->error($error);
            return;
        }

        $this->cancelEdit();
        $this->notification()->success('Primary number changed — the code now goes there.');
    }

    // ─── Platform logo ────────────────────────────────────────────────────────

    public function updatedLogo(): void
    {
        $this->checkLogo();
    }

    public function saveLogo(): void
    {
        if (!$this->isMainSuperAdmin() || !$this->checkLogo()) {
            return;
        }

        try {
            Platform::storeLogo($this->logo);
        } catch (\InvalidArgumentException $e) {
            $this->addError('logo', $e->getMessage());
            return;
        } catch (\Throwable $e) {
            Log::error('Platform logo upload failed', ['error' => $e->getMessage()]);
            $this->addError('logo', 'The logo could not be stored: ' . $e->getMessage());
            return;
        }

        $this->reset('logo');
        $this->notification()->success('Platform logo updated — it shows everywhere now.');
    }

    public function resetLogo(): void
    {
        if (!$this->isMainSuperAdmin()) {
            return;
        }

        Platform::resetLogo();
        $this->reset('logo');
        $this->notification()->success('Back to the original SuperLMS logo.');
    }

    /** A specific message for each way a logo file can be wrong. */
    private function checkLogo(): bool
    {
        $this->resetValidation('logo');

        if (!$this->logo) {
            $this->addError('logo', 'Choose a logo file first.');
            return false;
        }

        $ext = strtolower((string) $this->logo->getClientOriginalExtension());
        if (!in_array($ext, self::LOGO_TYPES, true)) {
            $this->addError('logo', ($ext ? 'A .' . $ext . ' file' : 'A file without an extension') . ' can\'t be a logo — use PNG, JPG, WEBP or SVG.');
            return false;
        }

        $kb = (int) ceil($this->logo->getSize() / 1024);
        if ($kb > self::LOGO_MAX_KB) {
            $this->addError('logo', 'The file is ' . round($kb / 1024, 1) . ' MB — a logo can be at most 2 MB.');
            return false;
        }

        if ($ext !== 'svg' && !@getimagesizefromstring((string) $this->logo->get())) {
            $this->addError('logo', 'This .' . $ext . ' file is not a picture that can be opened — save it again as PNG and retry.');
            return false;
        }

        return true;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function isMainSuperAdmin(): bool
    {
        return Auth::user()?->role === 'super-admin';
    }

    private function numberFor(string $key): ?array
    {
        foreach (OtpNumbers::forUser(Auth::user()) as $n) {
            if (($n['primary'] && $key === 'p') || (!$n['primary'] && (string) $n['id'] === $key)) {
                return $n;
            }
        }

        return null;
    }

    private static function idOf(string $key): ?int
    {
        return $key === 'p' ? null : (int) $key;
    }

    /** Counts the platform runs on. Each one stands alone: a missing table leaves a dash. */
    private function platformDetails(): array
    {
        $count = function (callable $query) {
            try {
                return number_format((int) $query());
            } catch (\Throwable $e) {
                return '—';
            }
        };

        return [
            'name'     => config('app.name', 'SuperLMS'),
            'website'  => config('app.url'),
            'schools'  => $count(fn () => Organization::count()),
            'active'   => $count(fn () => Organization::where('status', true)->count()),
            'students' => $count(fn () => \App\Models\Student\StudentDetail::count()),
            'teachers' => $count(fn () => User::where('role', 'teacher')->count()),
            'admins'   => $count(fn () => User::whereIn('role', ['admin', 'sub-admin'])->count()),
            'super'    => $count(fn () => User::whereIn('role', ['super-admin', 'sub-super-admin'])->count()),
        ];
    }

    /** What the public website has collected or shows, with where to manage it. */
    private function websiteData(): array
    {
        $sources = [
            ['Blogs',                  \App\Models\Blog::class,                 'super-admin.website.blogs'],
            ['FAQs',                   \App\Models\Faq::class,                  'super-admin.website.faqs'],
            ['Contact enquiries',      \App\Models\WebsiteContact::class,       'super-admin.enquiries'],
            ['Demo requests',          \App\Models\WebsiteDemo::class,          'super-admin.enquiries'],
            ['Career applications',    \App\Models\CareerApplication::class,    'super-admin.website.careers'],
            ['Executive applications', \App\Models\ExecutiveApplication::class, 'super-admin.website.become-executive'],
        ];

        $rows = [];
        foreach ($sources as [$label, $model, $route]) {
            $count  = '—';
            $latest = null;
            try {
                $count  = number_format($model::count());
                $latest = $model::max('created_at');
            } catch (\Throwable $e) {
                // Table not there — shown as a dash.
            }

            $rows[] = [
                'label'  => $label,
                'count'  => $count,
                'latest' => $latest ? \Carbon\Carbon::parse($latest)->format('d M Y') : null,
                'url'    => Route::has($route) ? route($route) : null,
            ];
        }

        return $rows;
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

        $isMain = $user->role === 'super-admin';
        if ($this->activeTab === 'platform' && !$isMain) {
            $this->activeTab = 'profile';
        }

        $logoPreview = null;
        if ($this->logo && !$this->getErrorBag()->has('logo')) {
            try {
                $logoPreview = $this->logo->temporaryUrl();
            } catch (\Throwable $e) {
                $logoPreview = null;
            }
        }

        return view('livewire.super-admin.profile', [
            'user'          => $user,
            'grantedAccess' => $grantedAccess,
            'isMain'        => $isMain,
            'numbers'       => $this->activeTab === 'numbers' ? OtpNumbers::forUser($user) : [],
            'maxNumbers'    => OtpNumbers::MAX,
            'platform'      => $this->activeTab === 'platform' ? $this->platformDetails() : [],
            'websiteData'   => $this->activeTab === 'platform' ? $this->websiteData() : [],
            'logoUrl'       => Platform::logoUrl(),
            'customLogo'    => Platform::hasCustomLogo(),
            'logoPreview'   => $logoPreview,
        ]);
    }
}
