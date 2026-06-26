<?php

namespace App\Http\Controllers\v1;

use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\StandardSubject;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherAssignment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * School-admin Standards module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Standard.php — Classes, Sections and Subjects with
 * the same validation, duplicate checks and cascade-delete rules. Org-scoped and
 * role-gated to admin / sub-admin.
 */
class AdminStandardController extends ApiController
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

    private function orgBoard(int $orgId): string
    {
        return (string) (Organization::find($orgId)?->education_board ?? '');
    }

    private function absUrl(?string $v): ?string
    {
        if (!$v) return null;
        return str_starts_with($v, 'http') ? $v : Storage::disk('s3')->url($v);
    }

    private function safeS3Delete(?string $url): void
    {
        if (!$url) return;
        try {
            Storage::disk('s3')->delete(parse_url($url, PHP_URL_PATH));
        } catch (\Throwable $e) {
            logger()->warning('AdminStandard safeS3Delete failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════ LOOKUPS ══════════════════════════

    /** GET /admin/academic-lookups — classes (with their sections) for dropdowns. */
    public function lookups()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $classes = Standard::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name', 'code', 'board'])
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'code'     => $s->code,
                'board'    => $s->board,
                'sections' => Section::where('standard_id', $s->id)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->get(['id', 'name', 'code'])
                    ->toArray(),
            ]);

        return $this->success([
            'classes' => $classes,
            'board'   => $this->orgBoard($user->organization_id),
        ], 'Academic lookups fetched.');
    }

    // ══════════════════════════ STANDARDS (CLASSES) ══════════════════════════

    private function shapeStandard(Standard $s): array
    {
        return [
            'id'             => $s->id,
            'name'           => $s->name,
            'code'           => $s->code,
            'board'          => $s->board,
            'order'          => $s->order,
            'is_active'      => (bool) $s->is_active,
            'sections_count' => $s->sections_count ?? null,
            'subjects_count' => $s->subjects_count ?? null,
            'created_at'     => $s->created_at?->toIso8601String(),
        ];
    }

    /** GET /admin/standards?search=&status= */
    public function standards(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $orgId = $user->organization_id;
        $query = Standard::withCount(['sections', 'subjects'])
            ->where('organization_id', $orgId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%"));
        }
        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'], true)) {
            $query->where('is_active', $request->status === 'active');
        }

        $items = $query->orderBy('order')->orderBy('id')->get()->map(fn ($s) => $this->shapeStandard($s));

        $base = Standard::where('organization_id', $orgId);
        return $this->success([
            'standards' => $items,
            'stats'     => [
                'classes'  => (clone $base)->count(),
                'sections' => Section::whereHas('standard', fn ($q) => $q->where('organization_id', $orgId))->count(),
                'subjects' => Subject::where('organization_id', $orgId)->count(),
            ],
        ], 'Classes fetched.');
    }

    /** POST /admin/standards */
    public function storeStandard(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'name'      => 'required|string|max:255',
            'code'      => 'required|string|max:50',
            'order'     => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ])) return $err;

        $orgId = $user->organization_id;

        if (Standard::where('organization_id', $orgId)->where('name', $request->name)->exists()) {
            return $this->error('A class with this name already exists.', 422);
        }
        if (Standard::where('organization_id', $orgId)->where('code', $request->code)->exists()) {
            return $this->error('A class with this code already exists.', 422);
        }

        $s = Standard::create([
            'name'            => $request->name,
            'code'            => $request->code,
            'board'           => $this->orgBoard($orgId),
            'order'           => $request->filled('order') ? (int) $request->order : 0,
            'is_active'       => $request->boolean('is_active', true),
            'organization_id' => $orgId,
        ]);

        return $this->success($this->shapeStandard($s), 'Class created successfully!');
    }

    /** PUT /admin/standards/{id} */
    public function updateStandard(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $orgId = $user->organization_id;
        $s = Standard::where('organization_id', $orgId)->find($id);
        if (!$s) return $this->error('Class not found.', 404);

        if ($err = $this->validateWith($request, [
            'name'      => 'required|string|max:255',
            'code'      => 'required|string|max:50',
            'order'     => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ])) return $err;

        if (Standard::where('organization_id', $orgId)->where('name', $request->name)->where('id', '!=', $id)->exists()) {
            return $this->error('A class with this name already exists.', 422);
        }
        if (Standard::where('organization_id', $orgId)->where('code', $request->code)->where('id', '!=', $id)->exists()) {
            return $this->error('A class with this code already exists.', 422);
        }

        $s->update([
            'name'      => $request->name,
            'code'      => $request->code,
            'order'     => $request->filled('order') ? (int) $request->order : $s->order,
            'is_active' => $request->boolean('is_active', $s->is_active),
        ]);

        return $this->success($this->shapeStandard($s->fresh()), 'Class updated successfully!');
    }

    /** DELETE /admin/standards/{id} — blocks if sections exist; orphans students. */
    public function deleteStandard($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $standard = Standard::where('organization_id', $user->organization_id)->find($id);
        if (!$standard) return $this->error('Class not found.', 404);

        if (Section::where('standard_id', $id)->exists()) {
            return $this->error('Please delete all sections of this class first.', 422);
        }

        try {
            DB::transaction(function () use ($id, $standard) {
                $userIds = StudentDetail::where('standard_id', $id)->pluck('user_id')->filter()->all();
                if ($userIds) {
                    User::whereIn('id', $userIds)->update(['is_active' => false]);
                }
                StudentDetail::where('standard_id', $id)->update(['standard_id' => null, 'section_id' => null]);
                StandardSubject::where('standard_id', $id)->delete();
                $standard->delete();
            });
        } catch (\Throwable $e) {
            return $this->error('Could not delete class: ' . $e->getMessage(), 500);
        }

        return $this->success(null, 'Class deleted successfully!');
    }

    // ══════════════════════════ SECTIONS ══════════════════════════

    private function shapeSection(Section $s): array
    {
        return [
            'id'             => $s->id,
            'name'           => $s->name,
            'code'           => $s->code,
            'description'    => $s->description,
            'standard_id'    => $s->standard_id,
            'standard_name'  => $s->standard->name ?? null,
            'is_active'      => (bool) $s->is_active,
            'subjects_count' => $s->subjects_count ?? null,
            'created_at'     => $s->created_at?->toIso8601String(),
        ];
    }

    /** GET /admin/sections?standard_id=&search=&status= */
    public function sections(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $query = Section::with('standard')->withCount('subjects')
            ->whereHas('standard', fn ($q) => $q->where('organization_id', $user->organization_id));

        if ($request->filled('standard_id')) {
            $query->where('standard_id', $request->standard_id);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('code', 'like', "%$s%")->orWhere('description', 'like', "%$s%"));
        }
        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'], true)) {
            $query->where('is_active', $request->status === 'active');
        }

        $items = $query->orderBy('id')->get()->map(fn ($s) => $this->shapeSection($s));
        return $this->success(['sections' => $items], 'Sections fetched.');
    }

    /** POST /admin/sections */
    public function storeSection(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50',
            'description' => 'nullable|string',
            'standard_id' => 'required|exists:standards,id',
            'is_active'   => 'nullable|boolean',
        ])) return $err;

        if (Section::where('standard_id', $request->standard_id)
            ->where('name', $request->name)->where('code', $request->code)->exists()) {
            return $this->error('A section with this name and code already exists in the selected class.', 422);
        }

        $s = Section::create([
            'name'            => $request->name,
            'code'            => $request->code,
            'description'     => $request->description,
            'standard_id'     => $request->standard_id,
            'is_active'       => $request->boolean('is_active', true),
            'organization_id' => $user->organization_id,
        ]);

        return $this->success($this->shapeSection($s->load('standard')), 'Section created successfully!');
    }

    /** PUT /admin/sections/{id} */
    public function updateSection(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $s = Section::whereHas('standard', fn ($q) => $q->where('organization_id', $user->organization_id))->find($id);
        if (!$s) return $this->error('Section not found.', 404);

        if ($err = $this->validateWith($request, [
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50',
            'description' => 'nullable|string',
            'standard_id' => 'required|exists:standards,id',
            'is_active'   => 'nullable|boolean',
        ])) return $err;

        if (Section::where('standard_id', $request->standard_id)
            ->where('name', $request->name)->where('code', $request->code)
            ->where('id', '!=', $id)->exists()) {
            return $this->error('A section with this name and code already exists in the selected class.', 422);
        }

        $s->update([
            'name'        => $request->name,
            'code'        => $request->code,
            'description' => $request->description,
            'standard_id' => $request->standard_id,
            'is_active'   => $request->boolean('is_active', $s->is_active),
        ]);

        return $this->success($this->shapeSection($s->fresh('standard')), 'Section updated successfully!');
    }

    /** DELETE /admin/sections/{id} — cascades section's subjects. */
    public function deleteSection($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $section = Section::whereHas('standard', fn ($q) => $q->where('organization_id', $user->organization_id))->find($id);
        if (!$section) return $this->error('Section not found.', 404);

        try {
            DB::transaction(function () use ($id, $section) {
                $subjectIds = SectionSubject::where('section_id', $id)->pluck('subject_id')->unique();
                SectionSubject::where('section_id', $id)->delete();

                foreach ($subjectIds as $subjectId) {
                    if (!SectionSubject::where('subject_id', $subjectId)->exists()) {
                        StandardSubject::where('subject_id', $subjectId)->delete();
                        $subject = Subject::find($subjectId);
                        if ($subject) {
                            $this->safeS3Delete($subject->image);
                            $this->safeS3Delete($subject->detail_image);
                            $subject->delete();
                        }
                    }
                }

                StudentDetail::where('section_id', $id)->update(['section_id' => null]);
                $section->delete();
            });
        } catch (\Throwable $e) {
            return $this->error('Could not delete section: ' . $e->getMessage(), 500);
        }

        return $this->success(null, 'Section and its subjects deleted successfully!');
    }

    // ══════════════════════════ SUBJECTS ══════════════════════════

    private function shapeSubject(Subject $s): array
    {
        $standard = $s->standards->first();
        return [
            'id'           => $s->id,
            'name'         => $s->name,
            'code'         => $s->code,
            'description'  => $s->description,
            'is_active'    => (bool) $s->is_active,
            'image_url'    => $this->absUrl($s->image),
            'detail_image_url' => $this->absUrl($s->detail_image),
            'standard_id'  => $standard?->id,
            'standard_name' => $standard?->name,
            'is_mandatory' => $standard ? (bool) ($standard->pivot?->is_mandatory) : null,
            'section_ids'  => $s->sections->pluck('id')->toArray(),
            'sections'     => $s->sections->pluck('name')->implode(', '),
            'created_at'   => $s->created_at?->toIso8601String(),
        ];
    }

    /** GET /admin/subjects?section_id=&standard_id=&search=&status= */
    public function subjects(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $query = Subject::with(['standards', 'sections'])
            ->where('organization_id', $user->organization_id);

        if ($request->filled('section_id')) {
            $query->whereHas('sections', fn ($q) => $q->where('section_id', $request->section_id));
        }
        if ($request->filled('standard_id')) {
            $query->whereHas('standards', fn ($q) => $q->where('standard_id', $request->standard_id));
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%$s%")
                ->orWhere('code', 'like', "%$s%")->orWhere('description', 'like', "%$s%"));
        }
        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'], true)) {
            $query->where('is_active', $request->status === 'active');
        }

        $items = $query->orderBy('id')->get()->map(fn ($s) => $this->shapeSubject($s));
        return $this->success(['subjects' => $items], 'Subjects fetched.');
    }

    private function applySubjectImage(Request $request, string $field, array &$data, ?Subject $existing): void
    {
        if (!$request->hasFile($field)) return;
        if ($existing) $this->safeS3Delete($field === 'image' ? $existing->image : $existing->detail_image);
        $path = $request->file($field)->store('admin/subjects/' . ($field === 'image' ? 'images' : 'detail-images'), 's3');
        Storage::disk('s3')->setVisibility($path, 'public');
        $data[$field] = Storage::disk('s3')->url($path);
    }

    /** POST /admin/subjects (multipart) */
    public function storeSubject(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        // Accept section_ids as array or comma string (multipart friendliness).
        $sectionIds = $request->input('section_ids');
        if (is_string($sectionIds)) $sectionIds = array_filter(explode(',', $sectionIds));
        $request->merge(['section_ids' => array_values(array_map('intval', (array) $sectionIds))]);

        if ($err = $this->validateWith($request, [
            'name'         => 'required|string|max:255',
            'code'         => 'required|string|max:50',
            'description'  => 'nullable|string',
            'standard_id'  => 'required|exists:standards,id',
            'section_ids'  => 'required|array|min:1',
            'section_ids.*' => 'exists:sections,id',
            'is_mandatory' => 'nullable|boolean',
            'is_active'    => 'nullable|boolean',
            'image'        => 'nullable|image|max:2048',
            'detail_image' => 'nullable|image|max:2048',
        ], ['section_ids.required' => 'Please select at least one section.'])) return $err;

        $orgId = $user->organization_id;

        $dupName = StandardSubject::where('standard_id', $request->standard_id)
            ->whereHas('subject', fn ($q) => $q->where('name', $request->name))->exists();
        if ($dupName) return $this->error('A subject with this name already exists in the selected class.', 422);

        $dupCode = StandardSubject::where('standard_id', $request->standard_id)
            ->whereHas('subject', fn ($q) => $q->where('code', $request->code))->exists();
        if ($dupCode) return $this->error('A subject with this code already exists in the selected class.', 422);

        $data = [
            'name'            => $request->name,
            'code'            => $request->code,
            'description'     => $request->description,
            'organization_id' => $orgId,
            'is_active'       => $request->boolean('is_active', true),
        ];
        $this->applySubjectImage($request, 'image', $data, null);
        $this->applySubjectImage($request, 'detail_image', $data, null);

        try {
            $subject = DB::transaction(function () use ($request, $data, $orgId) {
                $subject = Subject::create($data);
                StandardSubject::create([
                    'standard_id'     => $request->standard_id,
                    'subject_id'      => $subject->id,
                    'organization_id' => $orgId,
                    'is_mandatory'    => $request->boolean('is_mandatory', true),
                ]);
                foreach ($request->section_ids as $sectionId) {
                    SectionSubject::create([
                        'section_id'      => $sectionId,
                        'subject_id'      => $subject->id,
                        'standard_id'     => $request->standard_id,
                        'organization_id' => $orgId,
                    ]);
                }
                return $subject;
            });
        } catch (\Throwable $e) {
            return $this->error('Failed to save subject: ' . $e->getMessage(), 500);
        }

        return $this->success($this->shapeSubject($subject->load(['standards', 'sections'])), 'Subject created successfully!');
    }

    /** POST /admin/subjects/{id} (multipart update) */
    public function updateSubject(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $orgId = $user->organization_id;
        $subject = Subject::where('organization_id', $orgId)->find($id);
        if (!$subject) return $this->error('Subject not found.', 404);

        $sectionIds = $request->input('section_ids');
        if (is_string($sectionIds)) $sectionIds = array_filter(explode(',', $sectionIds));
        $request->merge(['section_ids' => array_values(array_map('intval', (array) $sectionIds))]);

        if ($err = $this->validateWith($request, [
            'name'         => 'required|string|max:255',
            'code'         => 'required|string|max:50',
            'description'  => 'nullable|string',
            'standard_id'  => 'required|exists:standards,id',
            'section_ids'  => 'required|array|min:1',
            'section_ids.*' => 'exists:sections,id',
            'is_mandatory' => 'nullable|boolean',
            'is_active'    => 'nullable|boolean',
            'image'        => 'nullable|image|max:2048',
            'detail_image' => 'nullable|image|max:2048',
        ], ['section_ids.required' => 'Please select at least one section.'])) return $err;

        $dupName = StandardSubject::where('standard_id', $request->standard_id)
            ->whereHas('subject', fn ($q) => $q->where('name', $request->name)->where('id', '!=', $id))->exists();
        if ($dupName) return $this->error('A subject with this name already exists in the selected class.', 422);

        $dupCode = StandardSubject::where('standard_id', $request->standard_id)
            ->whereHas('subject', fn ($q) => $q->where('code', $request->code)->where('id', '!=', $id))->exists();
        if ($dupCode) return $this->error('A subject with this code already exists in the selected class.', 422);

        $data = [
            'name'        => $request->name,
            'code'        => $request->code,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', $subject->is_active),
        ];
        $this->applySubjectImage($request, 'image', $data, $subject);
        $this->applySubjectImage($request, 'detail_image', $data, $subject);

        try {
            DB::transaction(function () use ($request, $subject, $data, $orgId) {
                $subject->update($data);
                StandardSubject::updateOrCreate(
                    ['standard_id' => $request->standard_id, 'subject_id' => $subject->id],
                    ['organization_id' => $orgId, 'is_mandatory' => $request->boolean('is_mandatory', true)]
                );
                SectionSubject::where('subject_id', $subject->id)->where('standard_id', $request->standard_id)->delete();
                foreach ($request->section_ids as $sectionId) {
                    SectionSubject::create([
                        'section_id'      => $sectionId,
                        'subject_id'      => $subject->id,
                        'standard_id'     => $request->standard_id,
                        'organization_id' => $orgId,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return $this->error('Failed to update subject: ' . $e->getMessage(), 500);
        }

        return $this->success($this->shapeSubject($subject->fresh(['standards', 'sections'])), 'Subject updated successfully!');
    }

    /** DELETE /admin/subjects/{id} */
    public function deleteSubject($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $subject = Subject::where('organization_id', $user->organization_id)->find($id);
        if (!$subject) return $this->error('Subject not found.', 404);

        if (\App\Models\Admin\TeacherTimeTable::where('subject_id', $id)->exists()
            || TeacherAssignment::where('subject_id', $id)->exists()) {
            return $this->error('This subject is used in timetable or assignments.', 422);
        }

        StandardSubject::where('subject_id', $id)->delete();
        SectionSubject::where('subject_id', $id)->delete();
        $this->safeS3Delete($subject->image);
        $this->safeS3Delete($subject->detail_image);
        $subject->delete();

        return $this->success(null, 'Subject deleted successfully!');
    }
}
