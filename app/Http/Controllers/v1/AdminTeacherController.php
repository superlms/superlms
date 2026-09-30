<?php

namespace App\Http\Controllers\v1;

use App\Helpers\CityGetHelper;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Teacher\AssignTeacherStandard;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Support\LoginIdentifier;
use App\Support\TeacherExport;
use App\Support\Usernames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * School-admin Teachers module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Teacher.php — listing with filters/stats and full
 * profile CRUD. Org-scoped, role-gated to admin / sub-admin.
 */
class AdminTeacherController extends ApiController
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

    private function shapeRow(TeacherDetail $d): array
    {
        return [
            'id'          => $d->id,
            'user_id'     => $d->user_id,
            'name'        => $d->user->name ?? null,
            'email'       => $d->user->email ?? null,
            'username'    => $d->user->username ?? null,
            'phone'       => $d->phone,
            'gender'      => $d->user->gender ?? null,
            'employee_id' => $d->employee_id,
            'qualification' => $d->qualification,
            'image'       => $d->user->image ?? null,
            'is_active'   => (bool) ($d->user->is_active ?? false),
            // The panel's list shows these too: when they joined, and the
            // class (and section) they are class teacher of.
            'date_of_joining' => optional($d->date_of_joining)->format('Y-m-d'),
            'class_teacher'   => $d->assignedClasses
                ->filter(fn ($a) => $a->standard || $a->section)
                ->map(fn ($a) => [
                    'class'   => $a->standard->name ?? null,
                    'section' => $a->section->name ?? null,
                ])
                ->values(),
        ];
    }

    /** GET /admin/teachers */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $query = TeacherDetail::with(['user', 'assignedClasses'])
            ->where('organization_id', $orgId)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('employee_id', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%")
                ->orWhereHas('user', fn ($q) => $q
                    ->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%"))))
            ->when($request->filled('gender'), fn ($q) => $q->whereHas('user', fn ($q) => $q->where('gender', $request->gender)))
            ->when($request->filled('status') && $request->status !== '',
                fn ($q) => $q->whereHas('user', fn ($q) => $q->where('is_active', $request->status)))
            ->when($request->filled('class'), function ($q) use ($request) {
                $ids = AssignTeacherStandard::where('standard_id', $request->class)
                    ->when($request->filled('section'), fn ($q) => $q->where('section_id', $request->section))
                    ->pluck('teacher_detail_id');
                $q->whereIn('id', $ids);
            })
            ->latest();

        $paginator = $query->paginate((int) $request->input('per_page', 25));

        return $this->success([
            'teachers'   => collect($paginator->items())->map(fn ($d) => $this->shapeRow($d)),
            'pagination' => $this->paginationMeta($paginator),
            'stats'      => $this->stats($orgId),
        ], 'Teachers fetched.');
    }

    private function stats(int $orgId): array
    {
        return [
            'total'      => TeacherDetail::where('organization_id', $orgId)->count(),
            'active'     => User::where('organization_id', $orgId)->where('role', 'teacher')->where('is_active', 1)->count(),
            'inactive'   => User::where('organization_id', $orgId)->where('role', 'teacher')->where('is_active', 0)->count(),
            'last_month' => TeacherDetail::where('organization_id', $orgId)
                ->where('date_of_joining', '>=', now()->subMonth())->count(),
            // The panel's "This Year": joined since the academic year began (March).
            'this_year'  => TeacherDetail::where('organization_id', $orgId)
                ->where('date_of_joining', '>=', now()->month >= 3
                    ? now()->startOfYear()->addMonths(2)->startOfMonth()
                    : now()->subYear()->startOfYear()->addMonths(2)->startOfMonth())
                ->count(),
        ];
    }

    /**
     * GET /admin/teachers/lookups?state=
     *
     * What the panel's Teachers page picks from: the classes (in class order)
     * and their sections for the Class / Section filter, and the states — with
     * `state`, that state's cities — for the form's State and City.
     */
    public function lookups(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $classes = Standard::where('organization_id', $orgId)->inClassOrder()->get(['id', 'name']);
        $sections = Section::whereIn('standard_id', $classes->pluck('id'))
            ->orderBy('id')
            ->get(['id', 'name', 'standard_id']);

        $cities = new CityGetHelper();

        return $this->success([
            'classes'  => $classes->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'sections' => $sections->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'standard_id' => $s->standard_id])->values(),
            'states'   => array_values($cities->getState()),
            'cities'   => $request->filled('state') ? array_values($cities->cityGetByState($request->query('state'))) : [],
        ], 'Teacher lookups fetched.');
    }

    /**
     * GET /admin/teachers/export?format=xlsx|pdf — the panel's Export: every
     * teacher as an Excel sheet, or a PDF of record cards (App\Support\
     * TeacherExport, built as the panel builds it). The file is the download.
     */
    public function export(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['format' => 'required|in:xlsx,pdf'])) return $err;

        $orgId = (int) $user->organization_id;
        [$headings, $rows, $records] = TeacherExport::data($orgId);
        if (!$rows) {
            return $this->error('No teachers to export.', 422);
        }

        $name = 'teachers_' . now()->format('Y-m-d');
        [$bytes, $type, $name] = $request->format === 'pdf'
            ? [TeacherExport::listPdf($orgId), 'application/pdf', $name . '.pdf']
            : [TeacherExport::xlsx($headings, $rows), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $name . '.xlsx'];

        return response($bytes, 200, [
            'Content-Type'        => $type,
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'X-Export-Count'      => (string) count($rows),
        ]);
    }

    /**
     * POST /admin/teachers/{id}/photo — the panel's photo viewer: a new photo
     * (an image of up to 1 MB) in place of the old, or remove=1 to take it
     * off. The rest of the teacher is left as it is.
     */
    public function photo(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $detail = TeacherDetail::where('organization_id', $user->organization_id)->find($id);
        if (!$detail) return $this->error('Teacher not found.', 404);
        $teacher = User::where('id', $detail->user_id)->where('role', 'teacher')
            ->where('organization_id', $user->organization_id)->first();
        if (!$teacher) return $this->error('Teacher not found.', 404);

        if ($request->boolean('remove')) {
            if ($teacher->image) $this->safeS3Delete($teacher->image);
            $teacher->update(['image' => null]);

            return $this->success(['image' => null], 'Photo removed.');
        }

        if ($err = $this->validateWith($request, ['image' => 'required|image|max:1024'], [
            'image.max'   => 'Image must be 1 MB or smaller.',
            'image.image' => 'Please pick an image.',
        ])) return $err;

        try {
            if ($teacher->image) $this->safeS3Delete($teacher->image);
            $path = $request->file('image')->store('admin/teachers/images', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $teacher->update(['image' => Storage::disk('s3')->url($path)]);
        } catch (\Throwable $e) {
            return $this->error('Could not save the photo: ' . $e->getMessage(), 500);
        }

        return $this->success(['image' => $teacher->image], 'Photo updated.');
    }

    /** GET /admin/teachers/{id} */
    public function show($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $d = TeacherDetail::with('user')->where('organization_id', $user->organization_id)->find($id);
        if (!$d || !$d->user) return $this->error('Teacher not found.', 404);

        $assignments = AssignTeacherStandard::with(['standard', 'section'])
            ->where('teacher_detail_id', $d->id)->get()
            ->map(fn ($a) => [
                'class'   => $a->standard->name ?? null,
                'section' => $a->section->name ?? null,
            ]);

        return $this->success([
            'id'               => $d->id,
            'user_id'          => $d->user_id,
            'name'             => $d->user->name,
            'email'            => $d->user->email,
            'username'         => $d->user->username,
            'phone'            => $d->phone,
            'gender'           => $d->user->gender ?? null,
            'dob'              => $d->user->dob ? (is_string($d->user->dob) ? $d->user->dob : optional($d->user->dob)->format('Y-m-d')) : null,
            'employee_id'      => $d->employee_id,
            'date_of_joining'  => optional($d->date_of_joining)->format('Y-m-d'),
            'qualification'    => $d->qualification,
            'address'          => $d->address,
            'state'            => $d->state,
            'city'             => $d->city,
            'pincode'          => $d->pincode,
            'emergency_contact' => $d->emergency_contact,
            'image'            => $d->user->image,
            'is_active'        => (bool) $d->user->is_active,
            'assignments'      => $assignments,
        ], 'Teacher fetched.');
    }

    private function rules(): array
    {
        return [
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|max:191',
            // What they sign in with — an email may be shared, this may not.
            // Left out by an app from before usernames: one is made from the name.
            'username'         => ['nullable', 'string', 'regex:' . Usernames::REGEX],
            'mobile'           => 'required|string|digits:10',
            'dob'              => 'required|date|before:today',
            'gender'           => 'required|string|in:male,female,other',
            'employee_id'      => 'required|string|max:50',
            'date_of_joining'  => 'nullable|date|before_or_equal:today',
            'qualification'    => 'required|string|max:255',
            'address'          => 'required|string|max:1000',
            'pincode'          => 'required|digits:6',
            'emergency_contact' => 'nullable|string|digits:10',
            'state'            => 'nullable|string|max:100',
            'city'             => 'nullable|string|max:100',
            'is_active'        => 'nullable|boolean',
            'image'            => 'nullable|image|max:2048',
        ];
    }

    /**
     * GET /admin/teachers/username-check?username=&name=&ignore_user_id=
     *
     * Whether a username is free and well formed, for the Add/Edit Teacher
     * form to say as it is typed: what is wrong with it, the rules, and free
     * ones near it. With only `name`, a free username made from it.
     */
    public function usernameCheck(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $ignore   = $request->integer('ignore_user_id') ?: null;
        $username = Usernames::normalize($request->query('username'));

        if ($username === '') {
            return $this->success([
                'username'    => '',
                'available'   => false,
                'problems'    => [],
                'rules'       => Usernames::RULES,
                'suggestions' => $request->filled('name') ? [Usernames::suggest($request->query('name'), null, (int) $user->organization_id)] : [],
            ]);
        }

        $problems = Usernames::problems($username, $ignore);

        return $this->success([
            'username'    => $username,
            'available'   => $problems === [],
            'problems'    => $problems,
            'rules'       => Usernames::RULES,
            'suggestions' => Usernames::taken($username, $ignore) ? Usernames::alternatives($username, 3) : [],
        ]);
    }

    /** POST /admin/teachers (multipart) */
    public function store(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        if ($err = $this->validateWith($request, $this->rules())) return $err;

        if (!$request->filled('username')) {
            $request->merge(['username' => Usernames::suggest($request->name, $request->email, (int) $orgId)]);
        }
        if ($problems = Usernames::problems($request->username)) {
            return $this->error(implode(' ', $problems), 422);
        }
        if (LoginIdentifier::emailReserved($request->email)) {
            return $this->error('This email belongs to a school account. Please use a different one.', 422);
        }

        try {
            $plainPassword = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789@#$!'), 0, 10);
            $teacher = new User();
            $userData = [
                'name'            => $request->name,
                'email'           => $request->email,
                'username'        => Usernames::normalize($request->username),
                'mobile_number'   => $request->mobile,
                'role'            => 'teacher',
                'is_active'       => $request->boolean('is_active'),
                'organization_id' => $orgId,
                'password'        => Hash::make($plainPassword),
            ];
            if (Schema::hasColumn('users', 'password_plain')) {
                $userData['password_plain'] = \Illuminate\Support\Facades\Crypt::encryptString($plainPassword);
            }
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('admin/teachers/images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $userData['image'] = Storage::disk('s3')->url($path);
            }
            $teacher->fill($userData);
            if (Schema::hasColumn('users', 'dob')) $teacher->dob = $request->dob;
            if (Schema::hasColumn('users', 'gender')) $teacher->gender = $request->gender;

            DB::transaction(function () use ($teacher, $request, $orgId) {
                $teacher->save();
                TeacherDetail::updateOrCreate(['user_id' => $teacher->id], [
                    'organization_id'   => $orgId,
                    'employee_id'       => $request->employee_id,
                    'date_of_joining'   => $request->date_of_joining ?: null,
                    'qualification'     => $request->qualification,
                    'phone'             => $request->mobile,
                    'address'           => $request->address,
                    'city'              => $request->city,
                    'state'             => $request->state,
                    'pincode'           => $request->pincode,
                    'emergency_contact' => $request->emergency_contact ?: null,
                ]);
            });

            $this->sendWelcomeEmail($teacher, $orgId, $plainPassword);
            // And on WhatsApp: the username, and a link to the password and the app.
            \App\Services\WelcomeWhatsApp::teacher((int) $teacher->id);

            $detail = TeacherDetail::with('user')->where('user_id', $teacher->id)->first();
            return $this->success($this->shapeRow($detail), 'Teacher Created Successfully!');
        } catch (\Throwable $e) {
            return $this->error('Error Saving Teacher: ' . $e->getMessage(), 500);
        }
    }

    /** POST /admin/teachers/{id} (multipart update) */
    public function update(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $detail = TeacherDetail::where('organization_id', $orgId)->find($id);
        if (!$detail) return $this->error('Teacher not found.', 404);
        $teacher = User::find($detail->user_id);
        if (!$teacher) return $this->error('Teacher account not found.', 404);

        if ($err = $this->validateWith($request, $this->rules())) return $err;

        if (!$request->filled('username')) {
            $request->merge(['username' => $teacher->username ?: Usernames::suggest($request->name, $request->email, (int) $orgId)]);
        }
        if ($problems = Usernames::problems($request->username, $teacher->id)) {
            return $this->error(implode(' ', $problems), 422);
        }
        if (LoginIdentifier::emailReserved($request->email)) {
            return $this->error('This email belongs to a school account. Please use a different one.', 422);
        }
        // A new number gets the WhatsApp too.
        $oldMobile = $teacher->mobile_number;
        // And a new email the credentials, as the panel sends them.
        $oldEmail = $teacher->email;

        try {
            $userData = [
                'name'          => $request->name,
                'email'         => $request->email,
                'username'      => Usernames::normalize($request->username),
                'mobile_number' => $request->mobile,
                'is_active'     => $request->boolean('is_active'),
            ];
            if ($request->hasFile('image')) {
                if ($teacher->image) $this->safeS3Delete($teacher->image);
                $path = $request->file('image')->store('admin/teachers/images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $userData['image'] = Storage::disk('s3')->url($path);
            }
            $teacher->fill($userData);
            if (Schema::hasColumn('users', 'dob')) $teacher->dob = $request->dob;
            if (Schema::hasColumn('users', 'gender')) $teacher->gender = $request->gender;

            DB::transaction(function () use ($teacher, $request, $detail, $orgId) {
                $teacher->save();
                $detail->update([
                    'employee_id'       => $request->employee_id,
                    'date_of_joining'   => $request->date_of_joining ?: null,
                    'qualification'     => $request->qualification,
                    'phone'             => $request->mobile,
                    'address'           => $request->address,
                    'city'              => $request->city,
                    'state'             => $request->state,
                    'pincode'           => $request->pincode,
                    'emergency_contact' => $request->emergency_contact ?: null,
                ]);
            });

            if (\App\Services\WelcomeWhatsApp::numberChanged($oldMobile, $request->mobile)) {
                \App\Services\WelcomeWhatsApp::teacher((int) $teacher->id);
            }

            // Email changed → the credentials go to the new address, as on the
            // panel. The password is not changed: the stored one when it is
            // known, otherwise a note to keep using the existing one.
            if ($oldEmail && strcasecmp($oldEmail, $teacher->email) !== 0) {
                $this->sendWelcomeEmail(
                    $teacher,
                    $orgId,
                    $teacher->plainPassword() ?? 'Use your existing password (unchanged)'
                );
            }

            return $this->success($this->shapeRow($detail->fresh('user')), 'Teacher Updated Successfully!');
        } catch (\Throwable $e) {
            return $this->error('Error Saving Teacher: ' . $e->getMessage(), 500);
        }
    }

    /** DELETE /admin/teachers/{id} */
    public function destroy($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $detail = TeacherDetail::where('organization_id', $user->organization_id)->find($id);
        if (!$detail) return $this->error('Teacher not found.', 404);

        AssignTeacherStandard::where('teacher_detail_id', $detail->id)->delete();
        $teacher = User::find($detail->user_id);
        if ($teacher && $teacher->image) $this->safeS3Delete($teacher->image);
        $detail->delete();
        $teacher?->delete();

        return $this->success(null, 'Teacher Deleted Successfully!');
    }

    private function sendWelcomeEmail(User $teacher, int $orgId, string $plainPassword): void
    {
        $key = config('services.zeptomail.teacher_password_template_key');
        if (!$key) return;
        $schoolName = Organization::find($orgId)?->name ?? 'School';
        dispatch(function () use ($key, $teacher, $plainPassword, $schoolName) {
            try {
                \App\Services\ZeptoMailService::sendTemplate($key, $teacher->email, $teacher->name, [
                    'password'      => $plainPassword,
                    'email_address' => $teacher->email,
                    'school_name'   => $schoolName,
                    // The name they sign in with, now that they have one.
                    'username'      => $teacher->username ?: $teacher->name,
                    'name'          => $teacher->name,
                    'login_url'     => url('/login'),
                ]);
            } catch (\Throwable $e) {
                logger()->error('AdminTeacher welcome email failed: ' . $e->getMessage());
            }
        })->afterResponse();
    }

    private function safeS3Delete(?string $url): void
    {
        if (!$url) return;
        try { Storage::disk('s3')->delete(parse_url($url, PHP_URL_PATH)); }
        catch (\Throwable $e) { logger()->warning('AdminTeacher s3 delete failed: ' . $e->getMessage()); }
    }
}
