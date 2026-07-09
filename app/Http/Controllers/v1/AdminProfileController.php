<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\SchoolInfo;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

/**
 * Admin profile / School Info for the mobile app.
 *
 * Mirrors the web `App\Livewire\Components\Profile` logic — logo, school
 * details, USM (vision/mission/values/goals), custom sections, management
 * team, documents and password — over the same models/tables.
 */
class AdminProfileController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    /** Auth + role + organization guard. Returns [User, null] or [null, errorResponse]. */
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

    private function schoolInfoFor(int $orgId): SchoolInfo
    {
        return SchoolInfo::firstOrCreate(['organization_id' => $orgId]);
    }

    private function s3Upload($file, string $dir): string
    {
        $path = $file->store($dir, 's3');
        Storage::disk('s3')->setVisibility($path, 'public');
        return Storage::disk('s3')->url($path);
    }

    private function s3Delete(?string $url): void
    {
        if (!$url) return;
        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') return;
        try {
            Storage::disk('s3')->delete($path);
        } catch (\Throwable $e) {
            // best-effort cleanup
        }
    }

    private function shape(User $user): array
    {
        $org  = Organization::find($user->organization_id);
        $info = $this->schoolInfoFor($user->organization_id);
        $info->load(['managementTeam', 'documents']);

        return [
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'image' => $user->image,
            ],
            'organization' => $org ? [
                'id'          => $org->id,
                'name'        => $org->name,
                'logo'        => $org->logo,
                'school_code' => $org->school_code,
            ] : null,
            'school_info' => [
                'about_school'         => $info->about_school,
                'website_info'         => $info->website_info,
                'website_url'          => $info->website_url,
                'school_email'         => $info->school_email,
                'school_mobile'        => $info->school_mobile,
                'school_address'       => $info->school_address,
                'school_document_text' => $info->school_document_text,
                'usm_vision'           => $info->usm_vision,
                'usm_mission'          => $info->usm_mission,
                'usm_values'           => $info->usm_values,
                'usm_goals'            => $info->usm_goals,
                'custom_sections'      => array_values(array_filter(
                    (array) ($info->custom_sections ?? []),
                    fn ($s) => is_array($s)
                )),
            ],
            'management_team' => $info->managementTeam->map(fn ($m) => [
                'id'          => $m->id,
                'name'        => $m->name,
                'designation' => $m->designation,
                'photo_path'  => $m->photo_path,
            ])->values(),
            'documents' => $info->documents->map(fn ($d) => [
                'id'        => $d->id,
                'title'     => $d->title,
                'file_path' => $d->file_path,
                'file_type' => $d->file_type,
            ])->values(),
        ];
    }

    /** GET /api/v1/admin/profile */
    public function show()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        return $this->success($this->shape($user), 'Admin profile fetched.');
    }

    /** POST /api/v1/admin/profile/logo  (multipart: photo) */
    public function updateLogo(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['photo' => ['required', 'image', 'max:2048']])) return $err;

        $org = Organization::find($user->organization_id);
        $this->s3Delete($org->logo);
        $org->update(['logo' => $this->s3Upload($request->file('photo'), 'admin/profile/photos')]);

        return $this->success(['logo' => $org->fresh()->logo], 'School logo updated.');
    }

    /** PUT /api/v1/admin/profile/school-info  (json) */
    public function updateSchoolInfo(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'about_school'                 => 'nullable|string',
            'website_info'                 => 'nullable|string',
            'website_url'                  => 'nullable|url',
            'school_email'                 => 'nullable|email',
            'school_mobile'                => 'nullable|regex:/^[0-9]+$/|min:10|max:15',
            'school_address'               => 'nullable|string|max:255',
            'school_document_text'         => 'nullable|string',
            'usm_vision'                   => 'nullable|string',
            'usm_mission'                  => 'nullable|string',
            'usm_values'                   => 'nullable|string',
            'usm_goals'                    => 'nullable|string',
            'custom_sections'              => 'nullable|array',
            'custom_sections.*.title'      => 'nullable|string|max:255',
            'custom_sections.*.description' => 'nullable|string',
        ], [
            'school_mobile.regex' => 'Mobile number must contain only digits.',
            'school_mobile.min'   => 'Mobile number must be at least 10 digits.',
            'website_url.url'     => 'Website URL must start with http:// or https://.',
        ])) return $err;

        $sections = array_values(array_filter(
            (array) $request->input('custom_sections', []),
            fn ($s) => is_array($s) && (trim($s['title'] ?? '') !== '' || trim($s['description'] ?? '') !== '')
        ));

        $info = $this->schoolInfoFor($user->organization_id);
        $info->update([
            'about_school'         => $request->about_school,
            'website_info'         => $request->website_info,
            'website_url'          => $request->website_url,
            'school_email'         => $request->school_email,
            'school_mobile'        => $request->school_mobile,
            'school_address'       => $request->school_address,
            'school_document_text' => $request->school_document_text,
            'usm_vision'           => $request->usm_vision,
            'usm_mission'          => $request->usm_mission,
            'usm_values'           => $request->usm_values,
            'usm_goals'            => $request->usm_goals,
            'custom_sections'      => $sections,
        ]);

        return $this->success($this->shape($user), 'School information saved.');
    }

    /** POST /api/v1/admin/profile/members  (multipart: name, designation, photo?) */
    public function addMember(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'name'        => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'photo'       => 'nullable|image|max:2048',
        ])) return $err;

        $info  = $this->schoolInfoFor($user->organization_id);
        $photo = $request->hasFile('photo')
            ? $this->s3Upload($request->file('photo'), 'admin/school-management/photos')
            : null;

        $member = $info->managementTeam()->create([
            'name'        => $request->name,
            'designation' => $request->designation,
            'photo_path'  => $photo,
            'sort_order'  => (int) $info->managementTeam()->max('sort_order') + 1,
        ]);

        return $this->success([
            'id'          => $member->id,
            'name'        => $member->name,
            'designation' => $member->designation,
            'photo_path'  => $member->photo_path,
        ], 'Member added.');
    }

    /** POST /api/v1/admin/profile/members/{id}  (multipart: name, designation, photo?) */
    public function updateMember(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $info   = $this->schoolInfoFor($user->organization_id);
        $member = $info->managementTeam()->where('id', $id)->first();
        if (!$member) return $this->error('Member not found.', 404);

        if ($err = $this->validateWith($request, [
            'name'        => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'photo'       => 'nullable|image|max:2048',
        ])) return $err;

        $data = ['name' => $request->name, 'designation' => $request->designation];
        if ($request->hasFile('photo')) {
            $this->s3Delete($member->photo_path);
            $data['photo_path'] = $this->s3Upload($request->file('photo'), 'admin/school-management/photos');
        }
        $member->update($data);

        return $this->success([
            'id'          => $member->id,
            'name'        => $member->name,
            'designation' => $member->designation,
            'photo_path'  => $member->photo_path,
        ], 'Member updated.');
    }

    /** DELETE /api/v1/admin/profile/members/{id} */
    public function deleteMember($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $info   = $this->schoolInfoFor($user->organization_id);
        $member = $info->managementTeam()->where('id', $id)->first();
        if (!$member) return $this->error('Member not found.', 404);

        $this->s3Delete($member->photo_path);
        $member->delete();

        return $this->success(null, 'Member removed.');
    }

    /** POST /api/v1/admin/profile/documents  (multipart: title?, file=pdf) */
    public function addDocument(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'title' => 'nullable|string|max:255',
            'file'  => 'required|file|mimes:pdf|max:2048',
        ], [
            'file.mimes' => 'Document must be a PDF file.',
            'file.max'   => 'Document must not exceed 2 MB.',
        ])) return $err;

        $info = $this->schoolInfoFor($user->organization_id);
        $file = $request->file('file');
        $url  = $this->s3Upload($file, 'admin/school-documents');

        $doc = $info->documents()->create([
            'title'      => trim((string) $request->title) ?: $file->getClientOriginalName(),
            'file_path'  => $url,
            'file_type'  => $file->getClientOriginalExtension(),
            'sort_order' => (int) $info->documents()->max('sort_order') + 1,
        ]);

        return $this->success([
            'id'        => $doc->id,
            'title'     => $doc->title,
            'file_path' => $doc->file_path,
            'file_type' => $doc->file_type,
        ], 'Document uploaded.');
    }

    /** DELETE /api/v1/admin/profile/documents/{id} */
    public function deleteDocument($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $info = $this->schoolInfoFor($user->organization_id);
        $doc  = $info->documents()->where('id', $id)->first();
        if (!$doc) return $this->error('Document not found.', 404);

        $this->s3Delete($doc->file_path);
        $doc->delete();

        return $this->success(null, 'Document deleted.');
    }

    /** POST /api/v1/admin/profile/password */
    public function updatePassword(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'current_password' => ['required'],
            'new_password'     => [
                'required',
                'different:current_password',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ])) return $err;

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect.', 422);
        }

        $user->rememberPlainPassword($request->new_password);
        $user->update(['password' => Hash::make($request->new_password)]);

        return $this->success(null, 'Password updated.');
    }
}
