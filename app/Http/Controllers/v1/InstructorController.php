<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Http\Request;

class InstructorController extends ApiController
{
    /**
     * GET /api/v1/instructors
     *
     * Scoping (automatic, by role):
     *  - Student (role=user) → only instructors who teach the student's
     *    (standard, section) via the timetable. Subjects are also scoped
     *    to what the instructor teaches that student's class.
     *  - Other roles → all active instructors in the organization.
     */
    public function index(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($user->role === 'user') {
            return $this->studentScopedIndex($request, $user);
        }

        return $this->organizationIndex($request, $user);
    }

    /**
     * GET /api/v1/instructors/{id}
     */
    public function show(int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($user->role === 'user') {
            return $this->studentScopedShow($id, $user);
        }

        $teacher = TeacherDetail::with([
            'user:id,name,email,image,is_active',
            'assignedSubjects.subject:id,name,code,image',
            'assignedSubjects.standard:id,name',
            'assignedSubjects.section:id,name',
            'assignedClasses.standard:id,name',
            'assignedClasses.section:id,name',
        ])
            ->where('organization_id', $user->organization_id)
            ->find($id);

        if (!$teacher) {
            return $this->error('Instructor not found.', 404);
        }

        return $this->success(
            $this->formatInstructor($teacher, full: true),
            'Instructor fetched successfully.'
        );
    }

    // ── Student-scoped (timetable-driven) ─────────────────────────────────────

    private function studentScopedIndex(Request $request, $user)
    {
        $studentDetail = StudentDetail::where('user_id', $user->id)->first();

        if (!$studentDetail) {
            return $this->error('Student profile not found.', 404);
        }

        $standardId = $studentDetail->standard_id;
        $sectionId  = $studentDetail->section_id;
        $orgId      = $user->organization_id;

        $timetableFilter = function ($q) use ($standardId, $sectionId, $request) {
            $q->where('standard_id', $standardId)
                ->where('section_id', $sectionId)
                ->where('is_active', true);

            if ($request->filled('subject_id')) {
                $q->where('subject_id', $request->subject_id);
            }
        };

        $query = TeacherDetail::with([
            'user:id,name,email,image,is_active',
            'timetables' => function ($q) use ($timetableFilter) {
                $timetableFilter($q);
                $q->with('subject:id,name,code,image');
            },
        ])
            ->where('organization_id', $orgId)
            ->whereHas('user', fn($q) => $q->where('is_active', true))
            ->whereHas('timetables', $timetableFilter);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas(
                'user',
                fn($q) => $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
            );
        }

        $instructors = $query->latest()->paginate((int) $request->get('per_page', 20));

        $items = $instructors->getCollection()->map(
            fn($t) => $this->formatStudentInstructor($t, $standardId, $sectionId)
        );

        return $this->paginated(
            $items,
            $this->paginationMeta($instructors),
            'Instructors fetched successfully.'
        );
    }

    private function studentScopedShow(int $id, $user)
    {
        $studentDetail = StudentDetail::where('user_id', $user->id)->first();

        if (!$studentDetail) {
            return $this->error('Student profile not found.', 404);
        }

        $standardId = $studentDetail->standard_id;
        $sectionId  = $studentDetail->section_id;

        $teacher = TeacherDetail::with([
            'user:id,name,email,image,is_active',
            'timetables' => function ($q) use ($standardId, $sectionId) {
                $q->where('standard_id', $standardId)
                    ->where('section_id', $sectionId)
                    ->where('is_active', true)
                    ->with('subject:id,name,code,image');
            },
        ])
            ->where('organization_id', $user->organization_id)
            ->whereHas('timetables', function ($q) use ($standardId, $sectionId) {
                $q->where('standard_id', $standardId)
                    ->where('section_id', $sectionId)
                    ->where('is_active', true);
            })
            ->find($id);

        if (!$teacher) {
            return $this->error('Instructor not found.', 404);
        }

        return $this->success(
            $this->formatStudentInstructor($teacher, $standardId, $sectionId, full: true),
            'Instructor fetched successfully.'
        );
    }

    // ── Non-student listing (legacy behavior) ─────────────────────────────────

    private function organizationIndex(Request $request, $user)
    {
        $query = TeacherDetail::with([
            'user:id,name,email,image,is_active',
            'assignedSubjects.subject:id,name,code,image',
            'assignedSubjects.standard:id,name',
            'assignedSubjects.section:id,name',
            'assignedClasses.standard:id,name',
            'assignedClasses.section:id,name',
        ])
            ->where('organization_id', $user->organization_id)
            ->whereHas('user', fn($q) => $q->where('is_active', true));

        if ($request->filled('subject_id')) {
            $query->whereHas(
                'assignedSubjects',
                fn($q) => $q->where('subject_id', $request->subject_id)
            );
        }

        if ($request->filled('standard_id')) {
            $query->whereHas(
                'assignedClasses',
                fn($q) => $q->where('standard_id', $request->standard_id)
            );
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas(
                'user',
                fn($q) => $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
            );
        }

        $instructors = $query->latest()->paginate((int) $request->get('per_page', 20));

        $items = $instructors->getCollection()->map(fn($t) => $this->formatInstructor($t));

        return $this->paginated($items, $this->paginationMeta($instructors), 'Instructors fetched successfully.');
    }

    // ── Formatters ────────────────────────────────────────────────────────────

    private function formatStudentInstructor(TeacherDetail $t, int $standardId, ?int $sectionId, bool $full = false): array
    {
        $timetables = $t->relationLoaded('timetables') ? $t->timetables : collect();

        $data = [
            'id'          => $t->id,
            'name'        => $t->user?->name,
            'email'       => $t->user?->email,
            'avatar'      => $t->user?->image,
            'employee_id' => $t->employee_id,
            'phone'       => $t->phone,

            // Subjects this instructor teaches *for the student's class* (via timetable)
            'subjects' => $timetables
                ->filter(fn($tt) => $tt->subject !== null)
                ->map(fn($tt) => [
                    'id'    => $tt->subject->id,
                    'name'  => $tt->subject->name,
                    'code'  => $tt->subject->code,
                    'image' => $tt->subject->image,
                ])
                ->unique('id')
                ->values(),
        ];

        if ($full) {
            $data['qualification']   = $t->qualification;
            $data['date_of_joining'] = $t->date_of_joining
                ? \Carbon\Carbon::parse($t->date_of_joining)->format('Y-m-d')
                : null;
            $data['phone']           = $t->phone;
            $data['city']            = $t->city;
            $data['state']           = $t->state;
            $data['address']         = $t->address;
        }

        return $data;
    }

    private function formatInstructor(TeacherDetail $t, bool $full = false): array
    {
        $data = [
            'id'          => $t->id,
            'name'        => $t->user?->name,
            'email'       => $t->user?->email,
            'avatar'      => $t->user?->image,
            'employee_id' => $t->employee_id,
            'phone'       => $t->phone,

            // Unique subjects across all assignments
            'subjects' => $t->assignedSubjects
                ->filter(fn($s) => $s->subject !== null)
                ->map(fn($s) => [
                    'id'    => $s->subject->id,
                    'name'  => $s->subject->name,
                    'code'  => $s->subject->code,
                    'image' => $s->subject->image,
                ])
                ->unique('id')
                ->values(),

            // Unique classes (standard + section) across all assignments
            'classes' => $t->assignedSubjects
                ->filter(fn($s) => $s->standard !== null)
                ->map(fn($s) => [
                    'standard_id'   => $s->standard?->id,
                    'standard_name' => $s->standard?->name,
                    'section_id'    => $s->section?->id,
                    'section_name'  => $s->section?->name,
                ])
                ->unique(fn($c) => $c['standard_id'] . '-' . $c['section_id'])
                ->values(),

            // From assignedClasses (AssignTeacherStandard)
            'assigned_classes' => $t->assignedClasses
                ->filter(fn($c) => $c->standard !== null)
                ->map(fn($c) => [
                    'standard_id'   => $c->standard?->id,
                    'standard_name' => $c->standard?->name,
                    'section_id'    => $c->section?->id,
                    'section_name'  => $c->section?->name,
                ])
                ->unique(fn($c) => $c['standard_id'] . '-' . $c['section_id'])
                ->values(),
        ];

        if ($full) {
            $data['qualification']   = $t->qualification;
            $data['date_of_joining'] = $t->date_of_joining
                ? \Carbon\Carbon::parse($t->date_of_joining)->format('Y-m-d')
                : null;
            $data['phone']           = $t->phone;
            $data['city']            = $t->city;
            $data['state']           = $t->state;
            $data['address']         = $t->address;
        }

        return $data;
    }
}
