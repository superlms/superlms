<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Organization;
use App\Models\OrganizationPaymentSetting;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\ZeptoMailService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Schools extends Component
{
    use WireUiActions, WithFileUploads, WithPagination;

    public int $totalSchools   = 0;
    public int $activeSchools  = 0;
    public int $pendingSchools = 0;
    public int $inactiveSchools = 0;
    public int $totalStudents  = 0;
    public int $totalTeachers  = 0;
    public     $avgStudents    = 0;

    // ── Registration analytics ────────────────────────────────────────────────
    public int $weekSchools      = 0;
    public int $monthSchools     = 0;
    public int $lastMonthSchools = 0;

    public string $search       = '';
    public string $statusFilter = ''; // '' | 'active' | 'inactive'
    public string $mediumFilter = ''; // '' | 'english' | 'hindi' | 'both'
    public string $boardFilter  = ''; // '' | any education_board value

    // ── Add-school flow ────────────────────────────────────────────────────────
    public int   $modalStep       = 1; // 1 = details, 2 = module selection (create only)
    public array $selectedModules = []; // module_key => bool for a new school

    public string $activeView   = 'list';
    public        $detailSchool = null;
    public string $detailTab    = 'overview';

    // ── Selected rows for analytics ───────────────────────────────────────────
    public $selectedStudentId = null;
    public $selectedTeacherId = null;

    // ── Per-school module toggles (module_key => bool) ─────────────────────────
    public array $moduleStates = [];

    public bool $showModal   = false;
    public      $editId      = null;
    public      $adminUserId = null;

    public $schoolName     = '';
    public $email          = '';
    public $mobileNumber   = '';
    public $state          = '';
    public $educationBoard = '';
    public $medium         = '';
    public $schoolCode     = '';
    public $affiliationNo  = '';
    public $udiseNumber    = '';
    public $serialNumber   = '';
    public $address        = '';
    public $logo;
    public $existingLogo   = null;

    public bool   $showBankModal   = false;
    public bool   $editBankMode    = false;

    public bool   $showDeleteConfirm = false;
    public        $deleteTargetId    = null;
    public string $bankName       = '';
    public string $bankAccountNo  = '';
    public string $bankIfsc       = '';
    public string $bankBranch     = '';
    public string $bankHolderName = '';

    // ── Online payment (per-org PhonePe) ──
    public bool   $showPaymentModal = false;
    public bool   $editPaymentMode  = false;
    public string $pgClientId        = '';
    public string $pgClientSecret    = '';   // blank = keep existing
    public string $pgClientVersion   = '1';
    public string $pgEnv             = 'sandbox';
    public string $pgWebhookUsername = '';
    public string $pgWebhookPassword = '';   // blank = keep existing
    public bool   $pgIsActive        = false;

    protected $listeners = [
        'editSchool'   => 'onEdit',
        'deleteSchool' => 'onDelete',
    ];

    public function mount(): void
    {
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $this->totalSchools   = Organization::count();
        $this->activeSchools  = Organization::where('status', true)->count();
        $this->pendingSchools = Organization::where('status', false)->count();
        $this->inactiveSchools = $this->pendingSchools;
        $this->totalStudents  = StudentDetail::count();
        $this->totalTeachers  = TeacherDetail::count();
        $this->avgStudents    = $this->totalSchools > 0
            ? round($this->totalStudents / $this->totalSchools) : 0;

        // Registered-school analytics
        $this->weekSchools      = Organization::where('created_at', '>=', now()->startOfWeek())->count();
        $this->monthSchools     = Organization::where('created_at', '>=', now()->startOfMonth())->count();
        $this->lastMonthSchools = Organization::whereBetween('created_at', [
            now()->subMonthNoOverflow()->startOfMonth(),
            now()->subMonthNoOverflow()->endOfMonth(),
        ])->count();
    }

    // ─── Search / filters ───────────────────────────────────────────────────────

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMediumFilter(): void
    {
        $this->resetPage();
    }

    public function updatedBoardFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'mediumFilter', 'boardFilter']);
        $this->resetPage();
    }

    // ─── Detail View ──────────────────────────────────────────────────────────

    public function viewSchoolDetail($id): void
    {
        $this->detailSchool = Organization::withCount([
            'students as total_students',
            'teachers as total_teachers',
        ])->find($id);

        if (!$this->detailSchool) {
            $this->notification()->error('School not found!');
            return;
        }
        $this->detailTab          = 'overview';
        $this->activeView         = 'detail';
        $this->selectedStudentId  = null;
        $this->selectedTeacherId  = null;
    }

    public function backToList(): void
    {
        $this->activeView         = 'list';
        $this->detailSchool       = null;
        $this->detailTab          = 'overview';
        $this->selectedStudentId  = null;
        $this->selectedTeacherId  = null;
    }

    public function setDetailTab(string $tab): void
    {
        $this->detailTab         = $tab;
        $this->selectedStudentId = null;
        $this->selectedTeacherId = null;

        if ($tab === 'modules') {
            $this->loadModuleStates();
        }
    }

    // ─── Module access (per-school feature toggles) ────────────────────────────

    private function loadModuleStates(): void
    {
        if (!$this->detailSchool) {
            $this->moduleStates = [];
            return;
        }

        $saved = \App\Models\OrganizationModule::where('organization_id', $this->detailSchool->id)
            ->pluck('enabled', 'module_key')
            ->toArray();

        $states = [];
        foreach (config('modules', []) as $key => $def) {
            $states[$key] = array_key_exists($key, $saved)
                ? (bool) $saved[$key]
                : (bool) ($def['default'] ?? true);
        }

        $this->moduleStates = $states;
    }

    public function enableAllModules(): void
    {
        foreach (array_keys(config('modules', [])) as $key) {
            $this->moduleStates[$key] = true;
        }
    }

    public function disableAllModules(): void
    {
        foreach (array_keys(config('modules', [])) as $key) {
            $this->moduleStates[$key] = false;
        }
    }

    public function saveModules(): void
    {
        if (!$this->detailSchool) {
            return;
        }

        foreach (config('modules', []) as $key => $def) {
            $enabled = (bool) ($this->moduleStates[$key] ?? ($def['default'] ?? true));

            \App\Models\OrganizationModule::updateOrCreate(
                ['organization_id' => $this->detailSchool->id, 'module_key' => $key],
                ['enabled' => $enabled],
            );
        }

        $this->notification()->success('Module access updated successfully!');
    }

    // ─── Student / Teacher selection ──────────────────────────────────────────

    public function selectStudent($id): void
    {
        $this->selectedStudentId = ($this->selectedStudentId == $id) ? null : $id;
    }

    public function selectTeacher($id): void
    {
        $this->selectedTeacherId = ($this->selectedTeacherId == $id) ? null : $id;
    }

    // ─── Bank Details ─────────────────────────────────────────────────────────

    public function openBankModal(): void
    {
        if ($this->detailSchool) {
            $this->bankName       = $this->detailSchool->bank_name ?? '';
            $this->bankAccountNo  = $this->detailSchool->bank_account_no ?? '';
            $this->bankIfsc       = $this->detailSchool->bank_ifsc ?? '';
            $this->bankBranch     = $this->detailSchool->bank_branch ?? '';
            $this->bankHolderName = $this->detailSchool->bank_holder_name ?? '';
            $this->editBankMode   = !empty($this->detailSchool->bank_name);
        }
        $this->showBankModal = true;
    }

    public function closeBankModal(): void
    {
        $this->showBankModal = false;
        $this->reset(['bankName', 'bankAccountNo', 'bankIfsc', 'bankBranch', 'bankHolderName']);
    }

    public function saveBankDetails(): void
    {
        $this->validate([
            'bankName'       => 'required|string|max:255',
            'bankAccountNo'  => 'required|string|max:50',
            'bankIfsc'       => 'required|string|max:20',
            'bankBranch'     => 'required|string|max:255',
            'bankHolderName' => 'required|string|max:255',
        ]);

        Organization::where('id', $this->detailSchool->id)->update([
            'bank_name'        => $this->bankName,
            'bank_account_no'  => $this->bankAccountNo,
            'bank_ifsc'        => strtoupper($this->bankIfsc),
            'bank_branch'      => $this->bankBranch,
            'bank_holder_name' => $this->bankHolderName,
        ]);

        $this->detailSchool = Organization::withCount([
            'students as total_students',
            'teachers as total_teachers',
        ])->find($this->detailSchool->id);

        $this->closeBankModal();
        $this->notification()->success('Bank details saved successfully!');
    }

    // ─── Online Payment (per-org PhonePe) ─────────────────────────────────────

    /** Current org's payment setting (for the detail tab display). */
    public function getPgSettingProperty(): ?OrganizationPaymentSetting
    {
        return $this->detailSchool
            ? OrganizationPaymentSetting::forOrg($this->detailSchool->id)
            : null;
    }

    public function openPaymentModal(): void
    {
        $setting = $this->pgSetting;

        $this->pgClientId        = $setting->client_id ?? '';
        $this->pgClientVersion   = $setting->client_version ?? '1';
        $this->pgEnv             = $setting->env ?? 'sandbox';
        $this->pgWebhookUsername = $setting->webhook_username ?? '';
        $this->pgIsActive        = (bool) ($setting->is_active ?? false);
        // Secrets are never sent back to the browser — blank means "keep".
        $this->pgClientSecret     = '';
        $this->pgWebhookPassword  = '';
        $this->editPaymentMode    = (bool) $setting;
        $this->showPaymentModal   = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->reset([
            'pgClientId', 'pgClientSecret', 'pgClientVersion', 'pgEnv',
            'pgWebhookUsername', 'pgWebhookPassword', 'pgIsActive', 'editPaymentMode',
        ]);
        $this->pgClientVersion = '1';
        $this->pgEnv = 'sandbox';
    }

    public function savePaymentSettings(): void
    {
        $this->validate([
            'pgClientId'        => 'required|string|max:255',
            'pgClientVersion'   => 'required|string|max:20',
            'pgEnv'             => 'required|in:sandbox,production',
            'pgWebhookUsername' => 'nullable|string|max:255',
        ]);

        $setting = OrganizationPaymentSetting::firstOrNew([
            'organization_id' => $this->detailSchool->id,
        ]);

        // Activation needs both Client ID and a secret (new or already stored).
        $hasSecret = filled($this->pgClientSecret) || ($setting->exists && filled($setting->client_secret));
        if ($this->pgIsActive && (!filled($this->pgClientId) || !$hasSecret)) {
            $this->addError('pgIsActive', 'Client ID and Client Secret are required to activate online collection.');
            return;
        }

        $setting->gateway          = 'phonepe';
        $setting->client_id        = $this->pgClientId;
        $setting->client_version   = $this->pgClientVersion;
        $setting->env              = $this->pgEnv;
        $setting->webhook_username = $this->pgWebhookUsername ?: null;
        $setting->is_active        = $this->pgIsActive;

        if (filled($this->pgClientSecret)) {
            $setting->client_secret = $this->pgClientSecret;
        }
        if (filled($this->pgWebhookPassword)) {
            $setting->webhook_password = $this->pgWebhookPassword;
        }

        $setting->save();

        $this->closePaymentModal();
        $this->notification()->success('Online payment settings saved!');
    }

    // ─── School CRUD ──────────────────────────────────────────────────────────

    public function openModal(): void
    {
        $this->resetForm();
        $this->initNewSchoolModules();
        $this->modalStep = 1;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /** Pre-tick the new-school module list from each module's config default. */
    private function initNewSchoolModules(): void
    {
        $states = [];
        foreach (config('modules', []) as $key => $def) {
            $states[$key] = (bool) ($def['default'] ?? true);
        }
        $this->selectedModules = $states;
    }

    /** Step 1 → 2: validate the school fields, then show module selection. */
    public function goToModuleStep(): void
    {
        try {
            $this->validate($this->schoolRules());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('School step-1 validation failed', [
                'email' => $this->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->notification()->error('Could not validate school details', $e->getMessage());
            return;
        }
        $this->modalStep = 2;
    }

    public function backToDetailsStep(): void
    {
        $this->modalStep = 1;
    }

    public function enableAllNewModules(): void
    {
        foreach (array_keys(config('modules', [])) as $key) {
            $this->selectedModules[$key] = true;
        }
    }

    public function disableAllNewModules(): void
    {
        foreach (array_keys(config('modules', [])) as $key) {
            $this->selectedModules[$key] = false;
        }
    }

    /** Validation rules shared by the step transition and the final save. */
    protected function schoolRules(): array
    {
        return [
            'schoolName'     => 'required|string|max:255',
            'email'          => [
                'required',
                'email',
                Rule::unique('organizations', 'email')->ignore($this->editId),
                Rule::unique('users', 'email')->ignore($this->adminUserId),
            ],
            'mobileNumber'   => 'required|string|max:15',
            'state'          => 'required|string',
            'educationBoard' => 'required|string',
            'medium'         => 'nullable|in:english,hindi,both',
            'schoolCode'     => [
                'required',
                'string',
                Rule::unique('organizations', 'school_code')->ignore($this->editId),
            ],
            'serialNumber'   => [
                'required',
                'string',
                Rule::unique('organizations', 'serial_number')->ignore($this->editId),
            ],
            'affiliationNo'  => 'nullable|string|max:100',
            'udiseNumber'    => 'nullable|string|max:100',
            'logo'           => 'nullable|image|max:2048',
        ];
    }

    public function onEdit($id): void
    {
        $school = Organization::find($id);
        if (!$school) return;

        $this->editId         = $id;
        $this->schoolName     = $school->name;
        $this->email          = $school->email;
        $this->mobileNumber   = $school->mobile_number;
        $this->state          = $school->state;
        $this->educationBoard = $school->education_board;
        $this->medium         = $school->medium ?? '';
        $this->schoolCode     = $school->school_code;
        $this->affiliationNo  = $school->affiliation_no;
        $this->udiseNumber    = $school->udise_number;
        $this->serialNumber   = $school->serial_number;
        $this->address        = $school->address;
        $this->existingLogo   = $school->logo;

        $this->adminUserId = User::where('organization_id', $id)
            ->where('role', 'admin')
            ->value('id');

        $this->showModal = true;
    }

    public function saveSchool(): void
    {
        // Wrap EVERYTHING (validation included) so no path can return a raw 500.
        // ValidationException is re-thrown so Livewire still shows inline field
        // errors; any other failure surfaces its real message in a toast.
        try {
            $this->validate($this->schoolRules());
            $this->persistSchool();

            $this->closeModal();
            $this->loadStats();
            $this->resetPage();
            $this->notification()->success('School saved successfully!');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('School save failed', [
                'editId' => $this->editId,
                'email'  => $this->email,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
            $this->notification()->error('Could not save school', $e->getMessage());
        }
    }

    /** Create/update the school + its admin user + module access, then email creds. */
    private function persistSchool(): void
    {
        $plainPassword = null;

        // Schema drift guard: on deploys where the `migrate` task didn't apply
        // 2026_06_29_000001 (or lms:migrate never ran), the affiliation/bank/udise
        // columns are missing and an insert with them 500s with "Unknown column".
        // Make the write self-healing — add any missing columns first (best effort,
        // outside the transaction since MySQL DDL implicitly commits).
        $this->ensureOrganizationColumns();

        // Upload the logo before the transaction so an S3 failure (e.g. a bucket
        // with ACLs disabled) never rolls back the school record — the logo is
        // optional, so we log and continue without it.
        $logoUrl = $this->existingLogo;
        if ($this->logo) {
            try {
                $path    = $this->logo->store('superadmin/schools/logos', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $logoUrl = Storage::disk('s3')->url($path);
            } catch (\Throwable $e) {
                Log::error('School logo upload failed', [
                    'school' => $this->schoolName,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        DB::transaction(function () use (&$plainPassword, $logoUrl) {
            $orgData = [
                'name'            => $this->schoolName,
                'email'           => $this->email,
                'mobile_number'   => $this->mobileNumber,
                'state'           => $this->state,
                'education_board' => $this->educationBoard,
                'medium'          => $this->medium ?: null,
                'school_code'     => $this->schoolCode,
                'affiliation_no'  => $this->affiliationNo,
                'udise_number'    => $this->udiseNumber,
                'serial_number'   => $this->serialNumber,
                'address'         => $this->address,
                'logo'            => $logoUrl,
                'status'          => true,
            ];

            // Final safety net: if a column still can't be created (e.g. the DB
            // user lacks ALTER), drop it from the payload rather than 500'ing.
            // The required fields (name/email/code) always exist, so the school
            // still gets created.
            $orgData = $this->onlyExistingColumns('organizations', $orgData);

            if ($this->editId) {
                Organization::findOrFail($this->editId)->update($orgData);
                if ($this->adminUserId) {
                    User::where('id', $this->adminUserId)->update([
                        'email'         => $this->email,
                        'mobile_number' => $this->mobileNumber,
                    ]);
                }
            } else {
                $org = Organization::create($orgData);

                $plainPassword = Str::upper(Str::random(4)) . rand(100, 999) . Str::random(3);

                User::create([
                    'name'            => $this->schoolName . ' Admin',
                    'email'           => $this->email,
                    'mobile_number'   => $this->mobileNumber,
                    'organization_id' => $org->id,
                    'role'            => 'admin',
                    'password'        => Hash::make($plainPassword),
                ]);

                // Persist the modules selected during the add-school flow.
                foreach (config('modules', []) as $key => $def) {
                    \App\Models\OrganizationModule::updateOrCreate(
                        ['organization_id' => $org->id, 'module_key' => $key],
                        ['enabled' => (bool) ($this->selectedModules[$key] ?? ($def['default'] ?? true))],
                    );
                }
            }
        });

        // Send welcome email after transaction so a mail failure never rolls back the school record
        if ($plainPassword !== null) {
            $templateKey = config('services.zeptomail.school_creation_template_key');
            if ($templateKey) {
                try {
                    ZeptoMailService::sendTemplate(
                        $templateKey,
                        $this->email,
                        $this->schoolName . ' Admin',
                        [
                            'school_name' => $this->schoolName,
                            'email'       => $this->email,
                            'password'    => $plainPassword,
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::error('School creation welcome email failed', [
                        'email' => $this->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Add any organizations columns the write path needs but that are missing
     * from the live DB (schema drift). Mirrors the hasColumn-guarded pattern in
     * the lms:migrate command, but runs at write time so a deploy that skipped
     * migrations can't break school creation. Best effort — swallows failures
     * (e.g. missing ALTER privilege); the onlyExistingColumns() net handles the
     * rest.
     */
    private function ensureOrganizationColumns(): void
    {
        $needed = [
            'affiliation_no', 'udise_number', 'medium', 'bank_name',
            'bank_account_no', 'bank_ifsc', 'bank_branch', 'bank_holder_name',
        ];

        $missing = array_filter(
            $needed,
            fn($col) => !Schema::hasColumn('organizations', $col),
        );

        if (empty($missing)) {
            return;
        }

        try {
            Schema::table('organizations', function (Blueprint $table) use ($missing) {
                foreach ($missing as $col) {
                    $table->string($col)->nullable();
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Could not auto-add missing organizations columns', [
                'missing' => array_values($missing),
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /** Keep only the keys that are real columns on the given table. */
    private function onlyExistingColumns(string $table, array $data): array
    {
        return array_filter(
            $data,
            fn($col) => Schema::hasColumn($table, $col),
            ARRAY_FILTER_USE_KEY,
        );
    }

    public function onDelete($id): void
    {
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    // ─── Login as School Admin ────────────────────────────────────────────────

    public function loginAsSchool($orgId): mixed
    {
        $admin = User::where('organization_id', $orgId)
            ->where('role', 'admin')
            ->first();

        if (!$admin) {
            $this->notification()->error('No admin account found for this school.');
            return null;
        }

        // Log into the ADMIN guard only — the super-admin session stays intact,
        // so closing the admin tab returns to a still-logged-in super-admin.
        Auth::guard('admin')->login($admin);
        return redirect()->route('admin.home', ['organization' => $admin->organization_id]);
    }

    public function doDelete($id): void
    {
        // Deleting the organization cascades to ALL of its data (students,
        // teachers, employees, users and every org-scoped record) via
        // Organization::purgeSchoolData(). Wrapped in a transaction so the whole
        // school is removed atomically — never a half-deleted school.
        try {
            DB::transaction(function () use ($id) {
                Organization::find($id)?->delete();
            });
        } catch (\Throwable $e) {
            Log::error("School delete failed for org #{$id}: {$e->getMessage()}");
            $this->notification()->error('Delete failed', 'Could not fully delete the school. Please try again.');
            return;
        }

        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;

        if ($this->activeView === 'detail' && $this->detailSchool?->id == $id) {
            $this->backToList();
        }

        $this->loadStats();
        $this->resetPage();
        $this->notification()->success('School and all its data permanently deleted!');
    }

    private function resetForm(): void
    {
        $this->reset([
            'schoolName',
            'email',
            'mobileNumber',
            'state',
            'educationBoard',
            'medium',
            'schoolCode',
            'affiliationNo',
            'udiseNumber',
            'serialNumber',
            'address',
            'logo',
            'existingLogo',
            'editId',
            'adminUserId',
            'modalStep',
            'selectedModules',
        ]);
    }

    public function render()
    {
        $schools = Organization::withCount([
            'students as total_students',
            'teachers as total_teachers',
        ])
            ->when($this->search, fn($q) => $q->where(
                fn($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('school_code', 'like', "%{$this->search}%")
                    ->orWhere('affiliation_no', 'like', "%{$this->search}%")
            ))
            ->when($this->statusFilter !== '', fn($q) => $q->where('status', $this->statusFilter === 'active'))
            ->when($this->mediumFilter !== '', fn($q) => $q->where('medium', $this->mediumFilter))
            ->when($this->boardFilter !== '', fn($q) => $q->where('education_board', $this->boardFilter))
            ->latest()
            ->paginate(12);

        // Distinct boards actually present in the data, for the filter dropdown.
        $boards = Organization::whereNotNull('education_board')
            ->where('education_board', '<>', '')
            ->distinct()
            ->orderBy('education_board')
            ->pluck('education_board');

        // ── Fee Stats ─────────────────────────────────────────────────────────
        $feeStats = [];
        if ($this->activeView === 'detail' && $this->detailSchool && $this->detailTab === 'fees') {
            $orgId = $this->detailSchool->id;

            // Financial year: April 2026 → March 2027
            $fyMonthlyChart = FeePayment::forOrg($orgId)
                ->where(function ($q) {
                    $q->where(function ($q) {
                        $q->whereYear('payment_date', 2026)
                            ->whereMonth('payment_date', '>=', 4);
                    })->orWhere(function ($q) {
                        $q->whereYear('payment_date', 2027)
                            ->whereMonth('payment_date', '<=', 3);
                    });
                })
                ->selectRaw('MONTH(payment_date) as month, YEAR(payment_date) as year, SUM(amount) as total')
                ->groupBy('month', 'year')
                ->get()
                ->mapWithKeys(fn($row) => ["{$row->year}-{$row->month}" => (float) $row->total])
                ->toArray();

            $feeStats = [
                'total_collected'    => FeePayment::forOrg($orgId)->sum('amount'),
                'this_month'         => FeePayment::forOrg($orgId)
                    ->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year)
                    ->sum('amount'),
                'last_month'         => FeePayment::forOrg($orgId)
                    ->whereMonth('payment_date', now()->subMonth()->month)
                    ->whereYear('payment_date', now()->subMonth()->year)
                    ->sum('amount'),
                'this_year'          => FeePayment::forOrg($orgId)
                    ->whereYear('payment_date', now()->year)
                    ->sum('amount'),
                'academic_total'     => FeePayment::forOrg($orgId)->academic()->sum('amount'),
                'transport_total'    => FeePayment::forOrg($orgId)->transport()->sum('amount'),
                'total_transactions' => FeePayment::forOrg($orgId)->count(),
                'this_month_count'   => FeePayment::forOrg($orgId)
                    ->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year)
                    ->count(),
                'recent_payments'    => FeePayment::forOrg($orgId)
                    ->with(['studentDetail.user', 'standard', 'section'])
                    ->latest('payment_date')
                    ->take(10)
                    ->get(),
                'fy_monthly_chart'   => $fyMonthlyChart,
                // Adjust this query to match your FeeStructure model
                'total_to_collect'   => 0,
            ];
        }

        // ── Student Stats ─────────────────────────────────────────────────────
        $studentStats = [];
        if ($this->activeView === 'detail' && $this->detailSchool && $this->detailTab === 'students') {
            $orgId = $this->detailSchool->id;
            $studentStats = [
                'total'    => StudentDetail::where('organization_id', $orgId)->count(),
                'active'   => StudentDetail::where('organization_id', $orgId)
                    ->whereHas('user', fn($q) => $q->where('is_active', true))->count(),
                'inactive' => StudentDetail::where('organization_id', $orgId)
                    ->whereHas('user', fn($q) => $q->where('is_active', false))->count(),
                'male'     => StudentDetail::where('organization_id', $orgId)
                    ->whereRaw('LOWER(gender) = ?', ['male'])->count(),
                'female'   => StudentDetail::where('organization_id', $orgId)
                    ->whereRaw('LOWER(gender) = ?', ['female'])->count(),
                'selected' => $this->selectedStudentId
                    ? StudentDetail::with(['user', 'standard', 'section'])
                    ->find($this->selectedStudentId)
                    : null,
            ];
        }

        // ── Teacher Stats ─────────────────────────────────────────────────────
        $teacherStats = [];
        if ($this->activeView === 'detail' && $this->detailSchool && $this->detailTab === 'teachers') {
            $orgId = $this->detailSchool->id;
            $teacherStats = [
                'total'    => TeacherDetail::where('organization_id', $orgId)->count(),
                'active'   => TeacherDetail::where('organization_id', $orgId)
                    ->whereHas('user', fn($q) => $q->where('is_active', true))->count(),
                'inactive' => TeacherDetail::where('organization_id', $orgId)
                    ->whereHas('user', fn($q) => $q->where('is_active', false))->count(),
                'selected' => $this->selectedTeacherId
                    ? TeacherDetail::with(['user', 'assignedSubjects.subject'])
                    ->find($this->selectedTeacherId)
                    : null,
            ];
        }

        return view('livewire.super-admin.schools', compact(
            'schools',
            'boards',
            'feeStats',
            'studentStats',
            'teacherStats'
        ));
    }
}
