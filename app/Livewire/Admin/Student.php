<?php

namespace App\Livewire\Admin;

use App\Helpers\CityGetHelper;
use App\Models\Admin\Transportation;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Student extends Component
{
    use WireUiActions, WithFileUploads, WithPagination;

    // ─── Edit state (holds user array during edit) ─────────────────────
    public $studentData = [];

    // ─── Form Fields ─────────────────────────────────────────────────────
    public $studentForm = [];
    public $dob           = '';
    public $studentsName  = '';
    public $studentsEmail = '';
    public $studentsMobile = '';
    public $studentsGender = '';
    // Board is no longer entered on the form — it's auto-derived from the
    // selected class (Standard::board). Kept here so old listeners/views
    // referencing it don't 500; cleared on form reset.
    public $studentsBoard  = '';
    public $studentsClass  = '';
    public $studentsSection = '';
    public $studentsActive  = 0;
    public $fatherName      = '';
    public $motherName      = '';
    public $religion        = '';
    public $localAddress    = '';
    public $permanentAddress = '';
    public $pincode          = '';
    public $aadharNo         = '';
    public $dateOfAdmission  = '';
    public $apparId          = null;
    public $registrationNumber = null;
    public $transportationRequired = '0'; // '1' = Yes, '0' = No (string keeps Livewire select binding reliable)

    // ─── Transport route selection ───────────────────────────────────────
    public $selectedRoute = null;
    public $routeOptions  = [];

    // ─── Location ────────────────────────────────────────────────────────
    public $states        = [];
    public $cities        = [];
    public $selectedState = null;
    public $selectedCity  = null;

    // ─── Uploads ─────────────────────────────────────────────────────────
    public $studentImage     = null;
    public $studentImageUrl  = null;

    // ─── Modal ───────────────────────────────────────────────────────────
    public $open          = false;
    public $openImage     = false;
    public $showViewModal = false;
    public $editId        = null;
    public $viewModalTitle = '';
    public $viewData       = [];
    public $imagePath      = null;

    // ─── Standards / Sections ────────────────────────────────────────────
    public $standards       = [];
    public $sections        = [];
    public $filterSections  = [];

    // ─── Stats ───────────────────────────────────────────────────────────
    public $totalStudents      = 0;
    public $activeStudents     = 0;
    public $lastYearStudents   = 0;
    public $thisYearStudents   = 0;
    public $lastMonthAdmissions = 0;
    public $thisYearAdmissions  = 0;

    // ─── Custom delete overlay (replaces broken WireUI dialog) ──────────
    public bool $showDeleteConfirm = false;
    public $deleteTargetId         = null;

    // ─── Inline save-error banner ───────────────────────────────────────
    // Shown at the top of the form when onSave throws. The WireUI toast is
    // easy to miss with the slide-in modal open, so we duplicate the error
    // here so it cannot be overlooked.
    public string $saveError = '';

    public string $search         = '';
    public string $filterClass    = '';
    public string $filterSection  = '';
    public string $filterGender   = '';
    public string $filterStatus   = '';
    /** Sort key — name_asc (A→Z), admission_no (asc), roll_no (asc). */
    public string $sortBy         = 'name_asc';
    public int    $perPage        = 50;

    protected $queryString = [
        'search'        => ['except' => ''],
        'filterClass'   => ['except' => ''],
        'filterSection' => ['except' => ''],
        'filterGender'  => ['except' => ''],
        'filterStatus'  => ['except' => ''],
        'sortBy'        => ['except' => 'name_asc'],
    ];

    protected $listeners = [
        'onViewStudentAdmin',
        'onEditStudent',
        'onDeleteStudent',
        'onImageClick',
    ];

    public function mount(): void
    {
        $cityHelper        = new CityGetHelper();
        $this->states      = $cityHelper->getState();
        $this->standards   = Standard::where('organization_id', Auth::user()->organization_id)->get();

        $this->loadRoutes();
        $this->loadSections();
        $this->loadStats();
    }

    private function loadRoutes(): void
    {
        $this->routeOptions = Transportation::where('organization_id', Auth::user()->organization_id)
            ->where('is_active', true)
            ->orderBy('route_name')
            ->get(['id', 'route_name', 'monthly_fee']);
    }

    public function updatedTransportationRequired($value): void
    {
        // Clear any selected route when transport is turned off
        if (!$this->transportationRequired) {
            $this->selectedRoute = null;
        }
    }

    /**
     * Attach the chosen transport route to the student (or detach all when
     * transport is not required). Assigning the route makes the route's fee
     * flow into the Transport module's student list & fee summary.
     */
    private function syncTransportRoute(StudentDetail $detail): void
    {
        if ($this->transportationRequired && $this->selectedRoute) {
            $detail->transportations()->sync([
                (int) $this->selectedRoute => ['organization_id' => Auth::user()->organization_id],
            ]);
        } else {
            $detail->transportations()->detach();
        }
    }

    private function loadStats(): void
    {
        $org = Auth::user()->organization_id;

        // Single aggregate query — was 6 separate COUNT(*) queries with
        // expensive whereHas('user') subqueries each time. StudentDetail
        // has its own organization_id column, so use it directly.
        $stats = StudentDetail::where('organization_id', $org)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN YEAR(created_at) = ? THEN 1 ELSE 0 END) as this_year,
                SUM(CASE WHEN YEAR(created_at) = ? THEN 1 ELSE 0 END) as last_year,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as last_month
            ', [now()->year, now()->subYear()->year, now()->subMonth()])
            ->first();

        $this->totalStudents       = (int) ($stats->total ?? 0);
        $this->thisYearStudents    = (int) ($stats->this_year ?? 0);
        $this->lastYearStudents    = (int) ($stats->last_year ?? 0);
        $this->lastMonthAdmissions = (int) ($stats->last_month ?? 0);
        $this->thisYearAdmissions  = $this->thisYearStudents;

        // Active count needs to join users for is_active — separate query
        $this->activeStudents = StudentDetail::where('organization_id', $org)
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->count();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSection(): void
    {
        $this->resetPage();
    }

    public function updatedFilterGender(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterClass(): void
    {
        $this->resetPage();
        $this->filterSection  = '';
        $this->filterSections = $this->filterClass
            ? Section::where('standard_id', $this->filterClass)->get()
            : [];
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterClass', 'filterSection', 'filterGender', 'filterStatus']);
        $this->sortBy         = 'name_asc';
        $this->filterSections = [];
        $this->resetPage();
    }

    public function updatedSelectedState($value): void
    {
        $this->cities      = $value ? (new CityGetHelper())->cityGetByState($value) : [];
        $this->selectedCity = null;
    }

    public function loadSections(): void
    {
        $this->sections = $this->studentsClass
            ? Section::where('standard_id', $this->studentsClass)->get()
            : [];
    }

    public function updatedStudentsClass(): void
    {
        $this->loadSections();
    }

    public function onAddStudent(): void
    {
        $this->open = true;
    }

    public function closeModal(): void
    {
        $this->open = false;
        $this->resetForm();
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewData      = [];
        $this->viewModalTitle = '';
    }

    public function onImageClick($id): void
    {
        $this->imagePath = User::find($id)?->image;
        $this->openImage = true;
    }

    public function closeImage(): void
    {
        $this->openImage = false;
    }

    public function getIsOrphanedProperty(): bool
    {
        // Student was orphaned when their previously-assigned class got deleted.
        // We only flag the *edit* form: a brand-new student doesn't have an id yet.
        return !empty($this->studentData['id']) && empty($this->studentsClass);
    }

    public function onSave(): void
    {
        // Clear any save error banner from a previous attempt.
        $this->saveError = '';

        $stepStart = microtime(true);
        $stepLog = function (string $step) use (&$stepStart) {
            $now = microtime(true);
            logger()->info('student-save step', ['step' => $step, 'ms' => (int) (($now - $stepStart) * 1000)]);
            $stepStart = $now;
        };
        $stepLog('start');

        // Orphaned students cannot be activated until a class is re-assigned (req #6).
        if ($this->isOrphaned && (int) $this->studentsActive === 1) {
            $this->addError('studentsActive', 'Assign a class before activating this student.');
            return;
        }

        $rules = [
            'studentsName'      => 'required|string|max:255',
            'studentsEmail'     => 'required|email|max:50',
            'studentsMobile'    => 'required|string|digits:10',
            'dob'               => 'required|date|before:today',
            'studentsGender'    => 'required|string|in:male,female,other',
            // Board is auto-fetched from the selected class — not a form field.
            'studentsClass'     => 'required|integer|exists:standards,id',
            'studentsSection'   => 'required|integer|exists:sections,id',
            'fatherName'        => 'required|string|max:255',
            'motherName'        => 'nullable|string|max:255',
            'dateOfAdmission'   => 'nullable|date|before_or_equal:today',
            'aadharNo'          => 'nullable|digits:12',
            'pincode'           => 'nullable|digits:6',
            'studentImage'      => 'nullable|image|max:2048',
            'transportationRequired' => 'boolean',
            'selectedRoute'     => $this->transportationRequired
                ? 'required|integer|exists:transportations,id'
                : 'nullable',
            'apparId'           => 'nullable|string',
            'registrationNumber' => 'nullable|string',
        ];

        $messages = [
            'studentsClass.required'   => 'Please select a class.',
            'studentsSection.required' => 'Please select a section.',
            'selectedRoute.required'   => 'Please select a transport route.',
        ];

        // Email uniqueness — must catch EVERY collision before we hit the
        // DB-level unique constraint on users.email. Otherwise the INSERT
        // crashes with a raw "SQLSTATE 1062 Duplicate entry" that the user
        // can't action.
        //
        // The users table has ONE unique constraint: email (no role/org
        // partial). So we look for any user with this email, then decide:
        //   - same org + role=user + has StudentDetail → real student, block
        //   - same org + role=user + no StudentDetail → orphan from a prior
        //     failed save, delete and recreate
        //   - any other case (different role, different org) → block with a
        //     clear message
        $orgId = Auth::user()->organization_id ?? null;

        // Holds an orphan User id queued for deletion inside the transaction.
        $orphanUserIdToDelete = null;

        if (empty($this->studentData['id'])) {
            $existingUser = User::where('email', $this->studentsEmail)
                ->first(['id', 'email', 'role', 'organization_id']);

            if ($existingUser) {
                $sameOrgStudent = $existingUser->role === 'user'
                    && (int) $existingUser->organization_id === (int) $orgId;

                if ($sameOrgStudent) {
                    $hasDetail = StudentDetail::where('user_id', $existingUser->id)->exists();

                    if ($hasDetail) {
                        // Real existing student in this school — block.
                        $this->addError('studentsEmail', 'A student with this email already exists in this school.');
                        return;
                    }

                    // Orphan User from a previous failed save — queue for deletion.
                    logger()->info('Found orphan student User row, queueing for delete-then-recreate', [
                        'user_id' => $existingUser->id,
                        'email'   => $existingUser->email,
                    ]);
                    $orphanUserIdToDelete = $existingUser->id;
                } else {
                    // Email is taken by some other user (different role, or
                    // a student in another school). The unique constraint on
                    // users.email is global, so we MUST block here — otherwise
                    // the INSERT crashes with SQLSTATE 1062 and the user only
                    // sees a cryptic toast.
                    $this->addError('studentsEmail', 'This email is already used by another account. Please use a different email.');
                    return;
                }
            }
        } else {
            // On edit, just make sure no OTHER student row in this org owns it
            $rules['studentsEmail'] .= '|unique:users,email,' . $this->studentData['id']
                . ',id,role,user'
                . ($orgId ? ',organization_id,' . $orgId : '');
        }

        $this->validate($rules, $messages);
        $stepLog('validated');

        // Catch \Throwable (not just \Exception) so PHP 8 Errors — TypeError
        // on a null Auth::user()->organization, BadMethodCallException on a
        // missing relationship, etc. — surface as a notification instead of
        // killing the request silently and leaving the UI stuck on "Saving…".
        try {
            $authUser = Auth::user();
            $orgId    = $authUser->organization_id ?? null;
            $org      = $authUser->organization ?? null; // may be null for some admin contexts
            $plainPassword = null;

            // Pick which User row to write into:
            //   - Edit mode  → load by id
            //   - Fresh add  → brand new (orphan, if any, is deleted in the
            //                  transaction below before the new User is saved)
            if (!empty($this->studentData['id'])) {
                $student = User::find($this->studentData['id']);
            } else {
                $student = new User();
            }

            $studentData = [
                'name'            => $this->studentsName,
                'email'           => $this->studentsEmail,
                'mobile_number'   => $this->studentsMobile,
                'role'            => 'user',
                'is_active'       => $this->studentsActive ?? 0,
                'organization_id' => $orgId,
            ];

            // S3 upload happens BEFORE the DB transaction — S3 can't be
            // rolled back, so we'd rather leak a small image file on a
            // transaction failure than block the save retry.
            if ($this->studentImage) {
                if ($student->image) {
                    Storage::disk('s3')->delete(parse_url($student->image, PHP_URL_PATH));
                }
                $path = $this->studentImage->store('admin/students/images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $studentData['image'] = Storage::disk('s3')->url($path);
                $stepLog('s3-upload');
            }

            // "isNew" = a brand-new student in the DB sense (no StudentDetail
            // yet). Edit mode is the only non-new case; orphan reuse is still
            // "new" because we're creating the missing StudentDetail.
            $isNew = empty($this->studentData['id']);
            if ($isNew) {
                $plainPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789@#$!'), 0, 10);
                $studentData['password'] = Hash::make($plainPassword);
            }

            // ─── Atomic save ──────────────────────────────────────────────
            // Wrap User save + StudentDetail upsert + transport-route sync
            // in a single transaction. If any step fails the whole thing
            // rolls back — no more orphan User rows from previous saves
            // dying mid-flow. THIS is the structural fix; the orphan-reuse
            // logic above is the self-heal for orphans that already exist.
            //
            // Concurrency: admission_no and roll_no are minted by reading the
            // current max for the org/class — a classic read-then-write race.
            // Two admins saving from different devices at the same instant would
            // otherwise read the same max and produce DUPLICATE numbers. We
            // serialise the whole create per-organisation with a MySQL named
            // lock so only one student-create runs at a time for a school; the
            // next one waits, then reads the freshly-committed max. The lock is
            // released in `finally` no matter what.
            $createLock = $isNew ? "student_create_{$orgId}" : null;
            $this->acquireCreationLock($createLock);
            try {
            [$detail, $admissionNo] = DB::transaction(function () use ($student, $studentData, $isNew, $org, $orgId, $orphanUserIdToDelete) {
                // Wipe any orphan User row holding the same email — cleans
                // the slate so the new User save can't trip a unique conflict
                // or hit stale row state.
                if ($orphanUserIdToDelete) {
                    User::where('id', $orphanUserIdToDelete)->delete();
                }

                $student->fill($studentData)->save();

                // Identifiers — on edit, keep what's there; otherwise mint.
                if ($isNew) {
                    $admissionNo = $this->generateAdmissionNumber();
                    $rollNo      = $this->generateRollNumber();
                } else {
                    $existingDetail = StudentDetail::where('user_id', $student->id)->first(['admission_no', 'roll_no']);
                    $admissionNo = $existingDetail->admission_no ?? $this->generateAdmissionNumber();
                    $rollNo      = $existingDetail->roll_no ?? $this->generateRollNumber();
                }

                // Board is auto-fetched from the chosen class. Null-safe so
                // a missing org relationship doesn't TypeError.
                $standardBoard = Standard::where('id', (int) $this->studentsClass)->value('board')
                    ?? ($org?->education_board)
                    ?? null;

                $detailData = [
                    'user_id'                => $student->id,
                    'standard_id'            => (int) $this->studentsClass,
                    'section_id'             => (int) $this->studentsSection,
                    'full_name'              => $this->studentsName,
                    'father_name'            => $this->fatherName,
                    'mother_name'            => $this->motherName,
                    'email'                  => $this->studentsEmail,
                    'dob'                    => $this->dob,
                    'gender'                 => $this->studentsGender,
                    'religion'               => $this->religion ?? null,
                    'local_address'          => $this->localAddress ?? null,
                    'permanent_address'      => $this->permanentAddress ?? null,
                    'city'                   => $this->selectedCity ?? null,
                    'state'                  => $this->selectedState ?? null,
                    'pincode'                => $this->pincode ?? null,
                    'admission_no'           => $admissionNo,
                    'date_of_admission'      => $this->dateOfAdmission ?: now()->toDateString(),
                    'roll_no'                => $rollNo,
                    'board'                  => $standardBoard,
                    'aadhar_no'              => $this->aadharNo ?? null,
                    'phone'                  => $this->studentsMobile,
                    'transportation_required' => (bool) $this->transportationRequired,
                    'organization_id'        => $orgId,
                    'appar_id'               => $this->apparId ?? null,
                    'registration_number'    => $this->registrationNumber ?? null,
                ];

                $detail = StudentDetail::updateOrCreate(['user_id' => $student->id], $detailData);

                // syncTransportRoute is wrapped because the pivot table or
                // relationship config can throw and we don't want a transport
                // misconfig to roll back the entire student creation.
                try { $this->syncTransportRoute($detail); }
                catch (\Throwable $e) { logger()->error('syncTransportRoute failed: ' . $e->getMessage()); }

                return [$detail, $admissionNo];
            });
            } finally {
                $this->releaseCreationLock($createLock);
            }

            $stepLog('db-saved');

            // ─── After commit ─────────────────────────────────────────────
            if (!$isNew) {
                $this->notification()->success('Student Updated Successfully!');
            } else {
                // Welcome email — needs to fire (carries password + admission_no
                // for first-time student login). But ZeptoMail can be slow and
                // blocked the request long enough to trip nginx's 504 earlier.
                //
                // Solution: dispatch the email send via Laravel's afterResponse
                // hook. PHP delivers the Livewire response to the browser first,
                // THEN runs this closure on the same FPM worker — so the user
                // sees "Student Created Successfully!" immediately while the
                // email goes out in the background. Combined with ZeptoMail's
                // 3s connect / 5s total timeout, the FPM worker is freed in ≤8s
                // either way.
                $emailTemplateKey = config('services.zeptomail.student_password_template_key');
                if ($emailTemplateKey && $plainPassword) {
                    $schoolName     = Organization::find($orgId)?->name ?? 'School';
                    $emailPayload   = [
                        'template_key' => $emailTemplateKey,
                        'to_email'     => $student->email,
                        'to_name'      => $student->name,
                        'merge'        => [
                            'password'         => $plainPassword,
                            'school_name'      => $schoolName,
                            'admission_number' => $admissionNo,
                            'username'         => $student->name,
                            'name'             => $student->name,
                            'email'            => $student->email,
                            'login_url'        => url('/login'),
                        ],
                    ];

                    dispatch(function () use ($emailPayload) {
                        try {
                            \App\Services\ZeptoMailService::sendTemplate(
                                $emailPayload['template_key'],
                                $emailPayload['to_email'],
                                $emailPayload['to_name'],
                                $emailPayload['merge'],
                            );
                            logger()->info('Student welcome email sent (after-response) to: ' . $emailPayload['to_email']);
                        } catch (\Throwable $e) {
                            logger()->error('Student welcome email failed (after-response) for ' . $emailPayload['to_email'] . ': ' . $e->getMessage());
                        }
                    })->afterResponse();
                } else {
                    logger()->warning('ZEPTOMAIL_STUDENT_PASSWORD_TEMPLATE_KEY not configured — skipping welcome email.');
                }

                $this->notification()->success('Student Created Successfully!');
            }

            $this->resetForm();
            $this->loadStats();
            $this->resetPage();
            $stepLog('done');
        } catch (\Throwable $e) {
            // \Throwable catches both \Exception and \Error (TypeError etc.) —
            // ensures the UI never stays stuck on "Saving…" because the
            // request returned 500 from an uncaught Error.
            $msg = $e->getMessage() ?: 'Unknown error';

            // Translate the common SQLSTATE 1062 duplicate-email error to a
            // user-friendly message. The broader pre-check above should catch
            // this, but keep the safety net for race conditions / column
            // collisions we haven't anticipated.
            if (str_contains($msg, '1062') && str_contains($msg, 'email')) {
                $msg = 'This email is already used by another account. Please use a different email.';
            }

            // Set BOTH the toast and the inline banner — the slide-in modal
            // covers most of the screen and toasts at top-end are easy to miss.
            $this->saveError = $msg;
            $this->notification()->error('Error Saving Student', $msg);
            logger()->error('Student save error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    public function onViewStudentAdmin($id): void
    {
        $detail = StudentDetail::with(['user', 'standard', 'section', 'transportations', 'organization'])->find($id);

        if (!$detail || !$detail->user) {
            $this->notification()->error('Student not found!');
            return;
        }

        $this->studentImageUrl = $detail->user->image;
        $this->viewModalTitle  = 'Student Details';
        $this->viewData        = [
            'user'         => $detail->user,
            'detail'       => $detail,
            'organization' => $detail->organization,
        ];
        $this->showViewModal   = true;
    }

    public function onEditStudent($id): void
    {
        // Close the view panel first so the edit slide-in doesn't stack on top.
        $this->showViewModal = false;
        $this->viewData      = [];

        $detail = StudentDetail::find($id);
        if (!$detail) {
            abort(404);
        }

        $user = User::find($detail->user_id)?->refresh();
        if (!$user) {
            abort(404);
        }

        $this->studentData       = $user->toArray();
        $this->editId            = $user->id;
        $this->studentsName      = (string) ($user->name ?? '');
        $this->studentsEmail     = (string) ($user->email ?? '');
        $this->studentsMobile    = (string) ($user->mobile_number ?? '');
        $this->studentsActive    = (int) ($user->is_active ?? 0);
        // Cast Carbon dates to Y-m-d strings — Livewire chokes on Carbon
        // instances in public properties and can 500 the request
        $this->dob               = $detail->dob ? $detail->dob->format('Y-m-d') : '';
        $this->dateOfAdmission   = $detail->date_of_admission ? $detail->date_of_admission->format('Y-m-d') : '';
        $this->fatherName        = (string) ($detail->father_name ?? '');
        $this->motherName        = (string) ($detail->mother_name ?? '');
        $this->studentsGender    = (string) ($detail->gender ?? '');
        $this->studentsBoard     = (string) ($detail->board ?? '');
        $this->studentsClass     = (string) ($detail->standard_id ?? '');
        $this->studentsSection   = (string) ($detail->section_id ?? '');
        $this->religion          = (string) ($detail->religion ?? '');
        $this->localAddress      = (string) ($detail->local_address ?? '');
        $this->permanentAddress  = (string) ($detail->permanent_address ?? '');
        $this->selectedState     = $detail->state ?: null;
        $this->selectedCity      = $detail->city ?: null;
        $this->pincode           = (string) ($detail->pincode ?? '');
        $this->aadharNo          = (string) ($detail->aadhar_no ?? '');
        $this->transportationRequired = $detail->transportation_required ? '1' : '0';
        $this->selectedRoute     = $detail->transportations()->first()?->id;
        $this->apparId           = $detail->appar_id;
        $this->registrationNumber = $detail->registration_number;
        $this->studentImageUrl   = $user->image;

        if ($this->selectedState) {
            $this->cities = (new CityGetHelper())->cityGetByState($this->selectedState);
        }

        $this->loadSections();
        $this->open = true;
    }

    public function onDeleteStudent($id): void
    {
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    public function confirmDelete(): void
    {
        $detail = StudentDetail::find($this->deleteTargetId);

        if ($detail) {
            $user = User::find($detail->user_id);
            if ($user && $user->image) {
                $oldPath = parse_url($user->image, PHP_URL_PATH);
                Storage::disk('s3')->delete($oldPath);
            }
            $detail->delete();
            $user?->delete();
            $this->notification()->success('Student Deleted Successfully!');
            $this->loadStats();
            $this->resetPage();
        } else {
            $this->notification()->error('Student not found!');
        }

        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    // Keep old method as alias in case something still calls it
    public function doDeleteStudent($id = null): void
    {
        if ($id) $this->deleteTargetId = $id;
        $this->confirmDelete();
    }

    public function exportStudents(): StreamedResponse
    {
        $org      = Auth::user()->organization_id;
        $students = StudentDetail::with(['user', 'standard', 'section', 'organization', 'transportations'])
            ->whereHas('user', fn($q) => $q->where('organization_id', $org))
            ->orderBy('full_name')
            ->get();

        return response()->stream(function () use ($students) {
            $handle = fopen('php://output', 'w');

            // Headers — mirror every field on the Add Student form, with
            // admission_no + roll_no front-and-centre and the organization name.
            fputcsv($handle, [
                'S.No',
                'Admission No',
                'Roll No',
                'Organization',
                'Full Name',
                'Email',
                'Mobile',
                'Gender',
                'Date of Birth',
                'Date of Admission',
                'Religion',
                'Aadhar No',
                'Father Name',
                'Mother Name',
                'Board (auto)',
                'Class',
                'Section',
                'Apaar ID',
                'Registration Number',
                'State',
                'City',
                'Pincode',
                'Local Address',
                'Permanent Address',
                'Transportation Required',
                'Transport Route',
                'Status',
            ]);

            foreach ($students as $index => $s) {
                $route = $s->transportations->first();
                fputcsv($handle, [
                    $index + 1,
                    $s->admission_no ?? '',
                    $s->roll_no ?? '',
                    $s->organization->name ?? '',
                    $s->full_name ?? '',
                    $s->user->email ?? '',
                    $s->phone ?? '',
                    ucfirst($s->gender ?? ''),
                    $s->dob?->format('d-m-Y') ?? '',
                    $s->date_of_admission?->format('d-m-Y') ?? '',
                    $s->religion ?? '',
                    $s->aadhar_no ?? '',
                    $s->father_name ?? '',
                    $s->mother_name ?? '',
                    $s->board ?? ($s->standard->board ?? ''),
                    $s->standard->name ?? '',
                    $s->section->name ?? '',
                    $s->appar_id ?? '',
                    $s->registration_number ?? '',
                    $s->state ?? '',
                    $s->city ?? '',
                    $s->pincode ?? '',
                    $s->local_address ?? '',
                    $s->permanent_address ?? '',
                    $s->transportation_required ? 'Yes' : 'No',
                    $route->route_name ?? '',
                    ($s->user->is_active ?? false) ? 'Active' : 'Inactive',
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_' . now()->format('Y-m-d_H-i-s') . '.csv"',
        ]);
    }

    /**
     * Serialise concurrent student creation per-organisation using a MySQL
     * application lock. Two admins on different devices hitting "Save" at the
     * same instant would otherwise read the same admission_no/roll_no max and
     * produce duplicates. GET_LOCK blocks the second caller until the first
     * commits and releases — guaranteeing unique, gap-free serials.
     *
     * Degrades gracefully: any failure (non-MySQL driver, lock timeout) is
     * logged and the save proceeds, so we never block a legitimate create.
     */
    protected function acquireCreationLock(?string $name): void
    {
        if (!$name) return;
        try {
            DB::selectOne('SELECT GET_LOCK(?, 15) AS got', [$name]);
        } catch (\Throwable $e) {
            logger()->warning('acquireCreationLock failed: ' . $e->getMessage());
        }
    }

    protected function releaseCreationLock(?string $name): void
    {
        if (!$name) return;
        try {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$name]);
        } catch (\Throwable $e) {
            logger()->warning('releaseCreationLock failed: ' . $e->getMessage());
        }
    }

    /**
     * Admission number = YY + SCHOOL_CODE + lastDigit(class.code) + lastDigit(section.code) + 0000
     *   YY = last 2 digits of academic session (Apr→Mar). Before April it rolls back a year.
     *   SCHOOL_CODE = organization.school_code (e.g. "TDS")
     *   class.code / section.code: the model's "code" column. Missing codes fall back to a
     *     digit derived from the id so we never produce a malformed number.
     *   serial = 4 digits, per organization, starting at 0001.
     * Example: 26TDS010001
     */
    protected function generateAdmissionNumber(): string
    {
        $sessionYear = (int) (now()->month >= 4 ? now()->year : now()->subYear()->year);
        $yy          = substr((string) $sessionYear, -2);
        // Null-safe: if the org relationship doesn't resolve (e.g. organization_id
        // points at a deleted row), the bare `Auth::user()->organization->...`
        // would throw "Attempt to read property on null" — TypeError — and the
        // whole save would die silently because TypeError is NOT a \Exception.
        $schoolCode  = (string) (Auth::user()->organization?->school_code ?? '');

        $classRow   = Standard::find((int) $this->studentsClass);
        $sectionRow = Section::find((int) $this->studentsSection);

        $classDigit   = $this->lastDigit($classRow?->code   ?? $classRow?->id);
        $sectionDigit = $this->lastDigit($sectionRow?->code ?? $sectionRow?->id);

        $prefix = $yy . $schoolCode . $classDigit . $sectionDigit;

        // Per-org serial: count rows whose admission_no starts with the same prefix.
        $last = StudentDetail::where('organization_id', Auth::user()->organization_id)
            ->where('admission_no', 'like', $prefix . '%')
            ->orderByDesc('admission_no')
            ->value('admission_no');

        $serial = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $serial, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Roll number = 3-digit serial scoped to class + section. First student = "001".
     * Each class+section combination keeps its own sequence.
     */
    protected function generateRollNumber(): string
    {
        $last = StudentDetail::where('standard_id', (int) $this->studentsClass)
            ->where('section_id',  (int) $this->studentsSection)
            ->whereNotNull('roll_no')
            ->orderByRaw('CAST(roll_no AS UNSIGNED) DESC')
            ->value('roll_no');

        $serial = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;

        return str_pad((string) $serial, 3, '0', STR_PAD_LEFT);
    }

    /** Pick the last numeric digit of a code-like value, fallback to "0". */
    protected function lastDigit($value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);
        if ($digits === '' || $digits === null) {
            return '0';
        }
        return substr($digits, -1);
    }

    protected function resetForm(): void
    {
        $this->reset([
            'studentData',
            'dob',
            'editId',
            'selectedState',
            'selectedCity',
            'studentsName',
            'studentsEmail',
            'studentsMobile',
            'studentsGender',
            'studentsBoard',
            'studentsClass',
            'studentsSection',
            'fatherName',
            'motherName',
            'religion',
            'localAddress',
            'permanentAddress',
            'pincode',
            'aadharNo',
            'dateOfAdmission',
            'studentImage',
            'studentImageUrl',
            'transportationRequired',
            'selectedRoute',
            'apparId',
            'registrationNumber',
            'studentsActive',
            'saveError',
        ]);
        $this->open     = false;
        $this->sections = [];
    }

    public function render()
    {
        $query = StudentDetail::with(['user', 'standard', 'section'])
            ->whereHas('user', fn($q) => $q->where('organization_id', Auth::user()->organization_id))
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
            ->when($this->filterStatus !== '', fn($q) => $q->whereHas(
                'user',
                fn($q) => $q->where('is_active', $this->filterStatus)
            ));

        // Sorting — default is name A→Z. admission_no / roll_no use natural-ish
        // numeric ordering by casting to UNSIGNED so "9" sorts before "10".
        switch ($this->sortBy) {
            case 'admission_no':
                $query->orderByRaw('CAST(admission_no AS UNSIGNED) ASC')
                      ->orderBy('admission_no', 'asc');
                break;
            case 'roll_no':
                $query->orderByRaw('CAST(roll_no AS UNSIGNED) ASC')
                      ->orderBy('roll_no', 'asc');
                break;
            case 'name_asc':
            default:
                $query->orderBy('full_name', 'asc');
                break;
        }

        $students = $query->paginate($this->perPage);

        return view('livewire.admin.student', compact('students'));
    }
}
