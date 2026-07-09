<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Transportation;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * School-admin Students module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Student.php — listing with filters/stats, full
 * profile CRUD, auto-generated admission_no / roll_no, board derived from the
 * chosen class, and transport-route sync. Org-scoped, role-gated.
 */
class AdminStudentController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        return [$user, null];
    }

    // ══════════════════════════ LIST + STATS ══════════════════════════

    private function shapeRow(StudentDetail $d): array
    {
        return [
            'id'           => $d->id,
            'user_id'      => $d->user_id,
            'full_name'    => $d->full_name,
            'email'        => $d->user->email ?? $d->email,
            'phone'        => $d->phone,
            'gender'       => $d->gender,
            'admission_no' => $d->admission_no,
            'roll_no'      => $d->roll_no,
            'standard_id'  => $d->standard_id,
            'class'        => $d->standard->name ?? null,
            'section_id'   => $d->section_id,
            'section'      => $d->section->name ?? null,
            'image'        => $d->user->image ?? null,
            'is_active'    => (bool) ($d->user->is_active ?? false),
        ];
    }

    /** GET /admin/students */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $query = StudentDetail::with(['user', 'standard', 'section'])
            ->whereHas('user', fn ($q) => $q->where('organization_id', $orgId))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('full_name', 'like', "%{$request->search}%")
                ->orWhere('admission_no', 'like', "%{$request->search}%")
                ->orWhere('roll_no', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%")
                ->orWhereHas('user', fn ($q) => $q->where('email', 'like', "%{$request->search}%"))))
            ->when($request->filled('class'), fn ($q) => $q->where('standard_id', $request->class))
            ->when($request->filled('section'), fn ($q) => $q->where('section_id', $request->section))
            ->when($request->filled('gender'), fn ($q) => $q->where('gender', $request->gender))
            ->when($request->filled('status') && $request->status !== '',
                fn ($q) => $q->whereHas('user', fn ($q) => $q->where('is_active', $request->status)));

        switch ($request->input('sort', 'name_asc')) {
            case 'admission_no':
                $query->orderByRaw('CAST(admission_no AS UNSIGNED) ASC')->orderBy('admission_no');
                break;
            case 'roll_no':
                $query->orderByRaw('CAST(roll_no AS UNSIGNED) ASC')->orderBy('roll_no');
                break;
            default:
                $query->orderBy('full_name');
        }

        $perPage  = (int) $request->input('per_page', 50);
        $paginator = $query->paginate($perPage);

        return $this->success([
            'students'   => collect($paginator->items())->map(fn ($d) => $this->shapeRow($d)),
            'pagination' => $this->paginationMeta($paginator),
            'stats'      => $this->stats($orgId),
        ], 'Students fetched.');
    }

    private function stats(int $orgId): array
    {
        $s = StudentDetail::where('organization_id', $orgId)->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN YEAR(created_at) = ? THEN 1 ELSE 0 END) as this_year,
            SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as last_month
        ', [now()->year, now()->subMonth()])->first();

        return [
            'total'      => (int) ($s->total ?? 0),
            'this_year'  => (int) ($s->this_year ?? 0),
            'last_month' => (int) ($s->last_month ?? 0),
            'active'     => StudentDetail::where('organization_id', $orgId)
                ->whereHas('user', fn ($q) => $q->where('is_active', true))->count(),
        ];
    }

    // ══════════════════════════ LOOKUPS ══════════════════════════

    /** GET /admin/students/lookups — classes, sections (optionally by class), transport routes. */
    public function lookups(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $classes = Standard::where('organization_id', $orgId)->orderBy('order')->orderBy('id')
            ->get(['id', 'name', 'code', 'board']);

        $sections = $request->filled('standard_id')
            ? Section::where('standard_id', $request->standard_id)->orderBy('id')->get(['id', 'name', 'code', 'standard_id'])
            : Section::whereHas('standard', fn ($q) => $q->where('organization_id', $orgId))
                ->orderBy('id')->get(['id', 'name', 'code', 'standard_id']);

        $routes = Transportation::where('organization_id', $orgId)->where('is_active', true)
            ->orderBy('route_name')->get(['id', 'route_name', 'monthly_fee']);

        return $this->success([
            'classes'   => $classes,
            'sections'  => $sections,
            'routes'    => $routes,
        ], 'Student lookups fetched.');
    }

    // ══════════════════════════ VIEW ══════════════════════════

    /** GET /admin/students/{id} */
    public function show($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $d = StudentDetail::with(['user', 'standard', 'section', 'transportations'])
            ->where('organization_id', $user->organization_id)->find($id);
        if (!$d || !$d->user) return $this->error('Student not found.', 404);

        $route = $d->transportations->first();

        return $this->success([
            'id'                => $d->id,
            'user_id'           => $d->user_id,
            'full_name'         => $d->full_name,
            'email'             => $d->user->email ?? $d->email,
            'phone'             => $d->phone,
            'gender'            => $d->gender,
            'dob'               => optional($d->dob)->format('Y-m-d'),
            'religion'          => $d->religion,
            'father_name'       => $d->father_name,
            'mother_name'       => $d->mother_name,
            'admission_no'      => $d->admission_no,
            'roll_no'           => $d->roll_no,
            'date_of_admission' => optional($d->date_of_admission)->format('Y-m-d'),
            'board'             => $d->board,
            'aadhar_no'         => $d->aadhar_no,
            'appar_id'          => $d->appar_id,
            'registration_number' => $d->registration_number,
            'standard_id'       => $d->standard_id,
            'class'             => $d->standard->name ?? null,
            'section_id'        => $d->section_id,
            'section'           => $d->section->name ?? null,
            'local_address'     => $d->local_address,
            'permanent_address' => $d->permanent_address,
            'state'             => $d->state,
            'city'              => $d->city,
            'pincode'           => $d->pincode,
            'image'             => $d->user->image ?? null,
            'is_active'         => (bool) ($d->user->is_active ?? false),
            'transportation_required' => (bool) $d->transportation_required,
            'route_id'          => $route?->id,
            'route_name'        => $route?->route_name,
        ], 'Student fetched.');
    }

    // ══════════════════════════ CREATE / UPDATE ══════════════════════════

    private function rules(bool $isEdit, $transportRequired): array
    {
        return [
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|max:50',
            'mobile'          => 'required|string|digits:10',
            'dob'             => 'required|date|before:today',
            'gender'          => 'required|string|in:male,female,other',
            'standard_id'     => 'required|integer|exists:standards,id',
            'section_id'      => 'required|integer|exists:sections,id',
            'father_name'     => 'required|string|max:255',
            'mother_name'     => 'nullable|string|max:255',
            'date_of_admission' => 'nullable|date|before_or_equal:today',
            'aadhar_no'       => 'nullable|digits:12',
            'pincode'         => 'nullable|digits:6',
            'religion'        => 'nullable|string|max:100',
            'local_address'   => 'nullable|string',
            'permanent_address' => 'nullable|string',
            'state'           => 'nullable|string|max:100',
            'city'            => 'nullable|string|max:100',
            'appar_id'        => 'nullable|string',
            'registration_number' => 'nullable|string',
            'is_active'       => 'nullable|boolean',
            'transportation_required' => 'nullable|boolean',
            'route_id'        => $transportRequired ? 'required|integer|exists:transportations,id' : 'nullable',
            'image'           => 'nullable|image|max:2048',
        ];
    }

    /** POST /admin/students (multipart) */
    public function store(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $transportRequired = $request->boolean('transportation_required');
        if ($err = $this->validateWith($request, $this->rules(false, $transportRequired))) return $err;

        // Email collision handling (mirrors web: block other accounts, reuse orphan).
        $orphanUserId = null;
        $existing = User::where('email', $request->email)->first(['id', 'role', 'organization_id']);
        if ($existing) {
            $sameOrgStudent = $existing->role === 'user' && (int) $existing->organization_id === (int) $orgId;
            if ($sameOrgStudent) {
                if (StudentDetail::where('user_id', $existing->id)->exists()) {
                    return $this->error('A student with this email already exists in this school.', 422);
                }
                $orphanUserId = $existing->id;
            } else {
                return $this->error('This email is already used by another account. Please use a different email.', 422);
            }
        }

        try {
            $plainPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789@#$!'), 0, 10);

            $userData = [
                'name'            => $request->name,
                'email'           => $request->email,
                'mobile_number'   => $request->mobile,
                'role'            => 'user',
                'is_active'       => $request->boolean('is_active'),
                'organization_id' => $orgId,
                'password'        => Hash::make($plainPassword),
            ];
            if (Schema::hasColumn('users', 'password_plain')) {
                $userData['password_plain'] = \Illuminate\Support\Facades\Crypt::encryptString($plainPassword);
            }
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('admin/students/images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $userData['image'] = Storage::disk('s3')->url($path);
            }

            [$detail, $admissionNo, $student] = DB::transaction(function () use ($request, $userData, $orgId, $orphanUserId) {
                if ($orphanUserId) User::where('id', $orphanUserId)->delete();

                $student = new User();
                $student->fill($userData)->save();

                $admissionNo = $this->generateAdmissionNumber($orgId, $request->standard_id, $request->section_id);
                $rollNo      = $this->generateRollNumber($request->standard_id, $request->section_id);
                $board       = Standard::where('id', (int) $request->standard_id)->value('board');

                $detail = StudentDetail::create($this->detailData($request, $student->id, $orgId, $admissionNo, $rollNo, $board));
                $this->syncTransport($detail, $request, $orgId);

                return [$detail, $admissionNo, $student];
            });

            $this->sendWelcomeEmail($student, $orgId, $plainPassword, $admissionNo);

            return $this->success($this->shapeRow($detail->fresh(['user', 'standard', 'section'])), 'Student Created Successfully!');
        } catch (\Throwable $e) {
            $msg = $e->getMessage() ?: 'Unknown error';
            if (str_contains($msg, '1062') && str_contains($msg, 'email')) {
                $msg = 'This email is already used by another account. Please use a different email.';
            }
            return $this->error('Error Saving Student: ' . $msg, 500);
        }
    }

    /** POST /admin/students/{id} (multipart update) */
    public function update(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $detail = StudentDetail::where('organization_id', $orgId)->find($id);
        if (!$detail) return $this->error('Student not found.', 404);
        $student = User::find($detail->user_id);
        if (!$student) return $this->error('Student account not found.', 404);

        $transportRequired = $request->boolean('transportation_required');
        $rules = $this->rules(true, $transportRequired);
        $rules['email'] .= '|unique:users,email,' . $student->id . ',id,role,user,organization_id,' . $orgId;
        if ($err = $this->validateWith($request, $rules)) return $err;

        try {
            $userData = [
                'name'          => $request->name,
                'email'         => $request->email,
                'mobile_number' => $request->mobile,
                'is_active'     => $request->boolean('is_active'),
            ];
            if ($request->hasFile('image')) {
                if ($student->image) $this->safeS3Delete($student->image);
                $path = $request->file('image')->store('admin/students/images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $userData['image'] = Storage::disk('s3')->url($path);
            }

            DB::transaction(function () use ($request, $student, $userData, $detail, $orgId) {
                $student->fill($userData)->save();
                $board = Standard::where('id', (int) $request->standard_id)->value('board');
                $detail->update($this->detailData($request, $student->id, $orgId, $detail->admission_no, $detail->roll_no, $board));
                $this->syncTransport($detail, $request, $orgId);
            });

            return $this->success($this->shapeRow($detail->fresh(['user', 'standard', 'section'])), 'Student Updated Successfully!');
        } catch (\Throwable $e) {
            return $this->error('Error Saving Student: ' . $e->getMessage(), 500);
        }
    }

    private function detailData(Request $request, int $userId, int $orgId, ?string $admissionNo, ?string $rollNo, ?string $board): array
    {
        return [
            'user_id'                => $userId,
            'standard_id'            => (int) $request->standard_id,
            'section_id'             => (int) $request->section_id,
            'full_name'              => $request->name,
            'father_name'            => $request->father_name,
            'mother_name'            => $request->mother_name,
            'email'                  => $request->email,
            'dob'                    => $request->dob,
            'gender'                 => $request->gender,
            'religion'               => $request->religion,
            'local_address'          => $request->local_address,
            'permanent_address'      => $request->permanent_address,
            'city'                   => $request->city,
            'state'                  => $request->state,
            'pincode'                => $request->pincode,
            'admission_no'           => $admissionNo,
            'date_of_admission'      => $request->date_of_admission ?: now()->toDateString(),
            'roll_no'                => $rollNo,
            'board'                  => $board,
            'aadhar_no'              => $request->aadhar_no,
            'phone'                  => $request->mobile,
            'transportation_required' => $request->boolean('transportation_required'),
            'organization_id'        => $orgId,
            'appar_id'               => $request->appar_id,
            'registration_number'    => $request->registration_number,
        ];
    }

    private function syncTransport(StudentDetail $detail, Request $request, int $orgId): void
    {
        try {
            if ($request->boolean('transportation_required') && $request->route_id) {
                $detail->transportations()->sync([(int) $request->route_id => ['organization_id' => $orgId]]);
            } else {
                $detail->transportations()->detach();
            }
        } catch (\Throwable $e) {
            logger()->error('AdminStudent syncTransport failed: ' . $e->getMessage());
        }
    }

    private function sendWelcomeEmail(User $student, int $orgId, string $plainPassword, ?string $admissionNo): void
    {
        $key = config('services.zeptomail.student_password_template_key');
        if (!$key) return;
        $schoolName = Organization::find($orgId)?->name ?? 'School';
        dispatch(function () use ($key, $student, $plainPassword, $admissionNo, $schoolName) {
            try {
                \App\Services\ZeptoMailService::sendTemplate($key, $student->email, $student->name, [
                    'password'         => $plainPassword,
                    'school_name'      => $schoolName,
                    'admission_number' => $admissionNo,
                    'username'         => $student->name,
                    'name'             => $student->name,
                    'email'            => $student->email,
                    'login_url'        => url('/login'),
                ]);
            } catch (\Throwable $e) {
                logger()->error('AdminStudent welcome email failed: ' . $e->getMessage());
            }
        })->afterResponse();
    }

    // ══════════════════════════ DELETE ══════════════════════════

    /** DELETE /admin/students/{id} */
    public function destroy($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $detail = StudentDetail::where('organization_id', $user->organization_id)->find($id);
        if (!$detail) return $this->error('Student not found.', 404);

        $student = User::find($detail->user_id);
        if ($student && $student->image) $this->safeS3Delete($student->image);
        $detail->delete();
        $student?->delete();

        return $this->success(null, 'Student Deleted Successfully!');
    }

    // ══════════════════════════ HELPERS ══════════════════════════

    private function safeS3Delete(?string $url): void
    {
        if (!$url) return;
        try { Storage::disk('s3')->delete(parse_url($url, PHP_URL_PATH)); }
        catch (\Throwable $e) { logger()->warning('AdminStudent s3 delete failed: ' . $e->getMessage()); }
    }

    private function generateAdmissionNumber(int $orgId, $classId, $sectionId): string
    {
        $sessionYear = (int) (now()->month >= 4 ? now()->year : now()->subYear()->year);
        $yy          = substr((string) $sessionYear, -2);
        $schoolCode  = (string) (Organization::find($orgId)?->school_code ?? '');

        $classRow   = Standard::find((int) $classId);
        $sectionRow = Section::find((int) $sectionId);
        $prefix = $yy . $schoolCode
            . $this->lastDigit($classRow?->code ?? $classRow?->id)
            . $this->lastDigit($sectionRow?->code ?? $sectionRow?->id);

        $last = StudentDetail::where('organization_id', $orgId)
            ->where('admission_no', 'like', $prefix . '%')
            ->orderByDesc('admission_no')->value('admission_no');
        $serial = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $serial, 4, '0', STR_PAD_LEFT);
    }

    private function generateRollNumber($classId, $sectionId): string
    {
        $last = StudentDetail::where('standard_id', (int) $classId)
            ->where('section_id', (int) $sectionId)
            ->whereNotNull('roll_no')
            ->orderByRaw('CAST(roll_no AS UNSIGNED) DESC')->value('roll_no');
        $serial = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1;

        return str_pad((string) $serial, 3, '0', STR_PAD_LEFT);
    }

    private function lastDigit($value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);
        return ($digits === '' || $digits === null) ? '0' : substr($digits, -1);
    }
}
