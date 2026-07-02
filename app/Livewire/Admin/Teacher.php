<?php

namespace App\Livewire\Admin;

use App\Helpers\CityGetHelper;
use App\Models\Teacher\TeacherDetail;
use App\Models\Teacher\AssignTeacherStandard;
use App\Models\Teacher\TeacherSubject;
use App\Models\Teacher\TeacherAttendance;
use App\Exports\TeachersExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use App\Models\Admin\SchoolInfo;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Teacher extends Component
{
    use WireUiActions, WithFileUploads, WithPagination;

    // ─── Edit state ──────────────────────────────────────────────────────
    public $teacherData = [];

    // ─── Form Fields ─────────────────────────────────────────────────────
    public $dob              = '';
    public $teacherName      = '';
    public $teacherEmail     = '';
    public $teacherMobile    = '';
    public $teacherGender    = '';
    public $teacherActive    = 0;
    public $employeeId       = '';
    public $dateOfJoining    = '';
    public $qualification    = '';
    public $address          = '';
    public $pincode          = '';
    public $emergencyContact = '';

    // ─── Location ────────────────────────────────────────────────────────
    public $states        = [];
    public $cities        = [];
    public $selectedState = null;
    public $selectedCity  = null;

    // ─── Uploads ─────────────────────────────────────────────────────────
    public $teacherImage       = null;
    public $teacherDetailImage = null;
    public $teacherImageUrl    = null;

    // ─── Modal ───────────────────────────────────────────────────────────
    public $open          = false;
    public $openImage     = false;
    public $showViewModal = false;
    public $editId        = null;
    public $imagePath     = null;
    public $viewModalTitle = '';
    public $viewData       = [];

    // ─── Stats ───────────────────────────────────────────────────────────
    public $totalSchools     = 0;
    public $totalTeachers    = 0;
    public $activeTeachers   = 0;
    public $inactiveTeachers = 0;
    public $lastMonthJoining = 0;
    public $thisYearJoining  = 0;

    // ─── Custom delete overlay (replaces broken WireUI dialog) ──────────
    public bool $showDeleteConfirm = false;
    public $deleteTargetId         = null;

    // ─── Search & Filters ────────────────────────────────────────────────
    public string $search        = '';
    public string $filterGender  = '';
    public string $filterStatus  = '';
    public string $filterClass   = '';
    public string $filterSection = '';
    public int    $perPage       = 25;

    // ─── Filter dependencies ─────────────────────────────────────────────
    public $standards      = [];
    public $filterSections = [];

    protected $queryString = [
        'search'        => ['except' => ''],
        'filterGender'  => ['except' => ''],
        'filterStatus'  => ['except' => ''],
        'filterClass'   => ['except' => ''],
        'filterSection' => ['except' => ''],
    ];

    protected $listeners = [
        'onViewTeacherAdmin',
        'onEditTeacher',
        'onDeleteTeacher',
        'onImageClick',
    ];

    public function mount(): void
    {
        $cityHelper       = new CityGetHelper();
        $this->states     = $cityHelper->getState();
        $this->standards  = Standard::where('organization_id', Auth::user()->organization_id)->get();
        $this->loadTeacherDashboardData();
    }

    public function loadTeacherDashboardData(): void
    {
        $org = Auth::user()->organization_id;

        $this->totalTeachers = TeacherDetail::where('organization_id', $org)->count();

        $this->lastMonthJoining = TeacherDetail::where('organization_id', $org)
            ->where('date_of_joining', '>=', now()->subMonth())->count();

        $academicYearStart = now()->month >= 3
            ? now()->startOfYear()->addMonths(2)->startOfMonth()
            : now()->subYear()->startOfYear()->addMonths(2)->startOfMonth();
        $this->thisYearJoining = TeacherDetail::where('organization_id', $org)
            ->where('date_of_joining', '>=', $academicYearStart)->count();

        $this->totalSchools = SchoolInfo::where('organization_id', $org)->count();

        $this->activeTeachers = User::where('organization_id', $org)
            ->where('role', 'teacher')->where('is_active', 1)->count();

        $this->inactiveTeachers = User::where('organization_id', $org)
            ->where('role', 'teacher')->where('is_active', 0)->count();
    }

    // ─── Filter resets ───────────────────────────────────────────────────
    public function updatedSearch(): void
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
    public function updatedFilterSection(): void
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

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterGender', 'filterStatus', 'filterClass', 'filterSection']);
        $this->filterSections = [];
        $this->resetPage();
    }

    // ─── Location ────────────────────────────────────────────────────────
    public function updatedSelectedState($value): void
    {
        $this->cities      = $value ? (new CityGetHelper())->cityGetByState($value) : [];
        $this->selectedCity = null;
    }

    // ─── Modal controls ──────────────────────────────────────────────────
    public function onAddTeacher(): void
    {
        $this->open = true;
    }

    public function closeModal(): void
    {
        $this->open = false;
        $this->resetForm();
        $this->dispatch('onUserAddUpdate');
    }

    public function closeViewModal(): void
    {
        $this->showViewModal  = false;
        $this->viewData       = [];
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

    // ─── Save ────────────────────────────────────────────────────────────
    public function onSave(): void
    {
        $rules = [
            'teacherName'      => 'required|string|max:50|regex:/^[A-Za-z ]+$/',
            'teacherEmail'     => 'required|email:rfc|max:191',
            'teacherMobile'    => 'required|digits:10',
            'dob'              => 'required|date|before:today',
            'teacherGender'    => 'required|string|in:male,female,other',
            'employeeId'       => 'required|string|max:20',
            'dateOfJoining'    => 'required|date|before_or_equal:today',
            'qualification'    => 'required|string|max:50',
            'address'          => 'required|string|max:1000',
            'pincode'          => 'required|digits:6',
            'emergencyContact' => 'required|digits:10',
            'teacherImage'     => 'nullable|image|max:1024', // 1 MB
        ];

        $messages = [
            'teacherName.regex'       => 'Name may contain only letters and spaces.',
            'teacherName.max'         => 'Name may not be longer than 50 characters.',
            'employeeId.max'          => 'Employee ID may not be longer than 20 characters.',
            'qualification.max'       => 'Qualification may not be longer than 50 characters.',
            'teacherImage.max'        => 'Image must be 1 MB or smaller.',
            'teacherMobile.digits'    => 'Mobile number must be exactly 10 digits.',
            'emergencyContact.digits' => 'Emergency contact must be exactly 10 digits.',
            'pincode.digits'          => 'Pincode must be exactly 6 digits.',
        ];

        // Unique email (exclude current user when editing, scope to role=teacher)
        if ($this->editId) {
            $rules['teacherEmail'] .= '|unique:users,email,' . $this->editId . ',id,role,teacher';
        } else {
            $existingUser = User::where('email', $this->teacherEmail)->where('role', 'teacher')->first();
            if ($existingUser) {
                $this->addError('teacherEmail', 'A teacher with this email already exists.');
                return;
            }
        }

        $this->validate($rules, $messages);

        try {
            $isEdit         = (bool) $this->editId;
            $plainPassword  = null;
            $teacher        = $isEdit ? User::findOrFail($this->editId) : new User();

            // Build base user payload (only User-table columns!)
            // dob + gender are added below via direct property set IFF the
            // columns exist on the users table — lms:migrate adds them but
            // we guard against a DB that's never had that command run.
            $userData = [
                'name'            => $this->teacherName,
                'email'           => $this->teacherEmail,
                'mobile_number'   => $this->teacherMobile,
                'role'            => 'teacher',
                'is_active'       => $this->teacherActive ?? 0,
                'organization_id' => Auth::user()->organization_id,
            ];

            if ($this->teacherImage) {
                if ($teacher->image) {
                    Storage::disk('s3')->delete(parse_url($teacher->image, PHP_URL_PATH));
                }
                $path = $this->teacherImage->store('admin/teachers/images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $userData['image'] = Storage::disk('s3')->url($path);
            }

            if (!$isEdit) {
                $plainPassword       = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789@#$!'), 0, 10);
                $userData['password'] = Hash::make($plainPassword);
            }

            $teacher->fill($userData);

            // dob/gender are added by `php artisan lms:migrate` — set them
            // only if those columns actually exist. Prevents
            // SQLSTATE[42S22] Unknown column 'dob' if migrate never ran.
            if (Schema::hasColumn('users', 'dob')) {
                $teacher->dob = $this->dob;
            }
            if (Schema::hasColumn('users', 'gender')) {
                $teacher->gender = $this->teacherGender;
            }

            // Atomic save — User row + TeacherDetail go together or not at all.
            // Wrapping them in one transaction means a mid-flow failure can't
            // leave an orphan User without its detail row, and lets several
            // admins on different devices create teachers concurrently without
            // half-written records. updateOrCreate is keyed on user_id so a
            // retried/concurrent save is idempotent rather than erroring.
            // attempts=5 → Laravel auto-retries the whole transaction on a
            // deadlock/lock-timeout, so many admins on different devices creating
            // teachers at the same instant all succeed without surfacing errors.
            DB::transaction(function () use ($teacher) {
                $teacher->save();

                // TeacherDetail upsert
                TeacherDetail::updateOrCreate(
                    ['user_id' => $teacher->id],
                    [
                        'organization_id'   => Auth::user()->organization_id,
                        'employee_id'       => $this->employeeId,
                        'date_of_joining'   => $this->dateOfJoining,
                        'qualification'     => $this->qualification,
                        'phone'             => $this->teacherMobile,
                        'address'           => $this->address,
                        'city'              => $this->selectedCity ?: null,
                        'state'             => $this->selectedState ?: null,
                        'pincode'           => $this->pincode,
                        'emergency_contact' => $this->emergencyContact,
                    ]
                );
            }, 5);

            // Send welcome email on creation only — dispatched after-response so
            // a slow ZeptoMail can never block the user's "Saving…" spinner.
            // Same pattern as Student.php for consistency.
            if (!$isEdit && $plainPassword) {
                $emailTemplateKey = config('services.zeptomail.teacher_password_template_key');
                if ($emailTemplateKey) {
                    $schoolName   = Organization::find(Auth::user()->organization_id)?->name ?? 'School';
                    $emailPayload = [
                        'template_key' => $emailTemplateKey,
                        'to_email'     => $teacher->email,
                        'to_name'      => $teacher->name,
                        'merge'        => [
                            'password'      => $plainPassword,
                            'email_address' => $teacher->email,
                            'school_name'   => $schoolName,
                            'username'      => $teacher->name,
                            'name'          => $teacher->name,
                            'login_url'     => url('/login'),
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
                            logger()->info('Teacher welcome email sent (after-response) to: ' . $emailPayload['to_email']);
                        } catch (\Throwable $e) {
                            logger()->error('Teacher welcome email failed (after-response) for ' . $emailPayload['to_email'] . ': ' . $e->getMessage());
                        }
                    })->afterResponse();
                } else {
                    logger()->warning('ZEPTOMAIL_TEACHER_PASSWORD_TEMPLATE_KEY not configured — skipping welcome email.');
                }
            }

            $this->notification()->success(
                $isEdit ? 'Teacher Updated Successfully!' : 'Teacher Created Successfully!'
            );

            // Clear any active search so the newly-added teacher's email doesn't auto-filter the list
            $this->search = '';
            $this->resetForm();

            $this->loadTeacherDashboardData();
            $this->resetPage();
            $this->dispatch('onTeacherAddUpdate');
        } catch (\Throwable $e) {
            $this->notification()->error('Error Saving Teacher', $e->getMessage());
            logger()->error('Teacher save error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    // ─── View ────────────────────────────────────────────────────────────
    public function onViewTeacherAdmin($id): void
    {
        $detail = TeacherDetail::with('user')->find($id);

        if (!$detail || !$detail->user) {
            $this->notification()->error('Teacher not found!');
            return;
        }

        $assignments = AssignTeacherStandard::with(['standard', 'section'])
            ->where('teacher_detail_id', $detail->id)->get();

        $this->teacherImageUrl = $detail->user->image;
        $this->viewModalTitle  = 'Teacher Details';
        $this->viewData        = [
            'user'        => $detail->user,
            'detail'      => $detail,
            'assignments' => $assignments,
        ];
        $this->showViewModal = true;
    }

    // ─── Edit ────────────────────────────────────────────────────────────
    public function onEditTeacher($id): void
    {
        $detail = TeacherDetail::find($id);
        if (!$detail) {
            abort(404);
        }

        $user = User::find($detail->user_id);
        if (!$user) {
            abort(404);
        }

        $this->teacherData      = $user->toArray();
        $this->editId           = $user->id;
        $this->teacherName      = (string) ($user->name ?? '');
        $this->teacherEmail     = (string) ($user->email ?? '');
        $this->teacherMobile    = (string) ($user->mobile_number ?? '');
        $this->teacherActive    = (int) ($user->is_active ?? 0);
        // dob/gender may be Carbon/null depending on whether lms:migrate added the columns
        $this->dob = $user->dob
            ? (is_string($user->dob) ? \Carbon\Carbon::parse($user->dob)->format('Y-m-d') : $user->dob->format('Y-m-d'))
            : '';
        $this->teacherGender    = (string) ($user->gender ?? '');
        $this->employeeId       = (string) ($detail->employee_id ?? '');
        $this->dateOfJoining    = $detail->date_of_joining ? $detail->date_of_joining->format('Y-m-d') : '';
        $this->qualification    = (string) ($detail->qualification ?? '');
        $this->address          = (string) ($detail->address ?? '');
        $this->selectedState    = $detail->state ?: null;
        $this->selectedCity     = $detail->city ?: null;
        $this->pincode          = (string) ($detail->pincode ?? '');
        $this->emergencyContact = (string) ($detail->emergency_contact ?? '');
        $this->teacherImageUrl  = $user->image;

        // Pre-load cities for the selected state so the City dropdown is populated
        if ($this->selectedState) {
            $this->cities = (new CityGetHelper())->cityGetByState($this->selectedState);
        }

        if ($this->selectedState) {
            $this->cities = (new CityGetHelper())->cityGetByState($this->selectedState);
        }

        $this->open = true;
        $this->dispatch('onUserAddUpdate');
    }

    // ─── Delete (custom overlay — replaces broken WireUI dialog) ────────
    public function onDeleteTeacher($id): void
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
        $detail = TeacherDetail::find($this->deleteTargetId);

        if ($detail) {
            AssignTeacherStandard::where('teacher_detail_id', $detail->id)->delete();
            $user = User::find($detail->user_id);
            if ($user && $user->image) {
                Storage::disk('s3')->delete(parse_url($user->image, PHP_URL_PATH));
            }
            $detail->delete();
            $user?->delete();
            $this->notification()->success('Teacher Deleted Successfully!');
            $this->loadTeacherDashboardData();
            $this->resetPage();
            $this->dispatch('onUserAddUpdate');
        } else {
            $this->notification()->error('Teacher not found!');
        }

        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    // Keep alias for any legacy callers
    public function doDeleteTeacher($id = null): void
    {
        if ($id) $this->deleteTargetId = $id;
        $this->confirmDelete();
    }

    // ─── Export ──────────────────────────────────────────────────────────
    /**
     * Export teachers as a ZIP containing BOTH an Excel (.xlsx) and a PDF file.
     * Each row carries the full form data plus overall attendance, the subjects
     * the teacher is assigned to (with class/section) and bank details. Missing
     * values are shown as a dash. The PDF is a report-card-style document with
     * the school header on top.
     */
    public function exportTeachers(): StreamedResponse
    {
        $org        = Auth::user()->organization_id;
        $orgModel   = Organization::find($org);
        $schoolInfo = SchoolInfo::where('organization_id', $org)->first();

        [$headings, $rows] = $this->teacherExportData($org);

        $stamp = now()->format('Y-m-d');

        // Excel (.xlsx) — raw bytes so we can bundle it into the zip.
        $xlsx = Excel::raw(new TeachersExport($headings, $rows), \Maatwebsite\Excel\Excel::XLSX);

        // PDF — report-card style with the school header.
        $pdf = Pdf::loadView('pdf.admin.teachers', [
            'organization' => $orgModel,
            'schoolInfo'   => $schoolInfo,
            'rows'         => $rows,
            'generatedAt'  => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->output();

        // Bundle both files into one zip.
        $tmpZip = tempnam(sys_get_temp_dir(), 'teachers_') . '.zip';
        $zip    = new \ZipArchive();
        $zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString("teachers_{$stamp}.xlsx", $xlsx);
        $zip->addFromString("teachers_{$stamp}.pdf", $pdf);
        $zip->close();

        return response()->streamDownload(function () use ($tmpZip) {
            readfile($tmpZip);
            @unlink($tmpZip);
        }, "teachers_{$stamp}.zip", ['Content-Type' => 'application/zip']);
    }

    /**
     * Build the export dataset: one associative row per teacher (keys double as
     * the column headings). Attendance, assigned subjects and bank details are
     * pulled per teacher; anything unavailable becomes "-".
     */
    private function teacherExportData(int $org): array
    {
        $teachers = TeacherDetail::with('user')->where('organization_id', $org)->get();

        // Teacher bank columns only exist if `lms:migrate` ever added them.
        $hasBank = Schema::hasColumn('teacher_details', 'bank_name');
        $dash    = fn ($v) => ($v === null || $v === '') ? '-' : $v;

        $rows = [];
        foreach ($teachers as $i => $t) {
            $u = $t->user;

            // Overall attendance
            $attTotal   = TeacherAttendance::where('teacher_detail_id', $t->id)->count();
            $attPresent = TeacherAttendance::where('teacher_detail_id', $t->id)->where('status', true)->count();
            $attPct     = $attTotal > 0 ? round($attPresent / $attTotal * 100, 1) . '%' : '-';

            // Subjects assigned, each with its class (and section)
            $subjects = TeacherSubject::with(['subject:id,name', 'standard:id,name', 'section:id,name'])
                ->where('teacher_detail_id', $t->id)
                ->get()
                ->map(function ($ts) {
                    $sub = $ts->subject?->name ?? 'Subject';
                    $cls = trim(($ts->standard?->name ?? '') . ($ts->section ? ' - ' . $ts->section->name : ''));
                    return $cls !== '' ? "{$sub} ({$cls})" : $sub;
                })
                ->unique()
                ->implode('; ');

            $dob = $u?->dob;
            if ($dob instanceof \Carbon\Carbon) $dob = $dob->format('d-m-Y');
            $doj = $t->date_of_joining;
            if ($doj instanceof \Carbon\Carbon) $doj = $doj->format('d-m-Y');

            $rows[] = [
                'S.No'                  => $i + 1,
                'Employee ID'           => $dash($t->employee_id),
                'Full Name'             => $dash($u?->name),
                'Email'                 => $dash($u?->email),
                'Mobile'                => $dash($u?->mobile_number),
                'Gender'                => $dash($u?->gender ? ucfirst($u->gender) : null),
                'Date of Birth'         => $dash($dob),
                'Date of Joining'       => $dash($doj),
                'Qualification'         => $dash($t->qualification),
                'Emergency Contact'     => $dash($t->emergency_contact),
                'Address'               => $dash($t->address),
                'City'                  => $dash($t->city),
                'State'                 => $dash($t->state),
                'Pincode'               => $dash($t->pincode),
                'Status'                => ($u?->is_active ? 'Active' : 'Inactive'),
                'Attendance'            => $attTotal > 0 ? "{$attPresent}/{$attTotal}" : '-',
                'Attendance %'          => $attPct,
                'Subjects (with Class)' => $subjects !== '' ? $subjects : '-',
                'Bank Name'             => $dash($hasBank ? $t->bank_name : null),
                'Bank Account No'       => $dash($hasBank ? $t->bank_account_no : null),
                'Bank IFSC'             => $dash($hasBank ? $t->bank_ifsc : null),
                'Bank Branch'           => $dash($hasBank ? $t->bank_branch : null),
                'Account Holder'        => $dash($hasBank ? $t->bank_holder_name : null),
            ];
        }

        $headings = $rows ? array_keys($rows[0]) : ['S.No'];

        return [$headings, $rows];
    }

    // ─── Reset ───────────────────────────────────────────────────────────
    protected function resetFormFields(): void
    {
        $this->reset([
            'teacherData',
            'dob',
            'editId',
            'selectedState',
            'selectedCity',
            'teacherName',
            'teacherEmail',
            'teacherMobile',
            'teacherGender',
            'employeeId',
            'dateOfJoining',
            'qualification',
            'address',
            'pincode',
            'emergencyContact',
            'teacherActive',
            'teacherImage',
            'teacherImageUrl',
        ]);
        $this->resetErrorBag();
    }

    protected function resetForm(): void
    {
        $this->resetFormFields();
        $this->open = false;
    }

    // ─── Render ──────────────────────────────────────────────────────────
    public function render()
    {
        $org = Auth::user()->organization_id;

        $teachers = TeacherDetail::with('user')
            ->where('organization_id', $org)
            ->when($this->search, fn($q) => $q->where(
                fn($q) => $q
                    ->where('employee_id', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhereHas(
                        'user',
                        fn($q) => $q
                            ->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%")
                    )
            ))
            ->when($this->filterGender, fn($q) => $q->whereHas(
                'user',
                fn($q) => $q->where('gender', $this->filterGender)
            ))
            ->when($this->filterStatus !== '', fn($q) => $q->whereHas(
                'user',
                fn($q) => $q->where('is_active', $this->filterStatus)
            ))
            ->when($this->filterClass, function ($q) {
                $teacherIds = AssignTeacherStandard::where('standard_id', $this->filterClass)
                    ->when($this->filterSection, fn($q) => $q->where('section_id', $this->filterSection))
                    ->pluck('teacher_detail_id');
                $q->whereIn('id', $teacherIds);
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.teacher', compact('teachers'));
    }
}
