<?php

namespace App\Http\Controllers\v1\Teacher;

use App\Http\Controllers\v1\AdminStudentController;
use App\Models\Admin\Transportation;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Teacher\AssignTeacherStandard;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Http\Request;

/**
 * A class teacher's own students, in the app's More screen.
 *
 * It is the admin panel's Students module — the same fields, the same
 * admission and roll numbers, the same welcome mail — narrowed to the classes
 * the teacher is class teacher of (assign_teacher_standards). A teacher who is
 * class teacher of nobody sees an empty list and can add no one.
 *
 *   GET    teacher/students/classes    the classes this teacher owns
 *   GET    teacher/students/lookups    those classes, their sections, routes
 *   GET    teacher/students            the roster, with filters and counts
 *   GET    teacher/students/{id}       one student in full
 *   POST   teacher/students            add
 *   POST   teacher/students/{id}       edit (multipart)
 *   DELETE teacher/students/{id}       remove
 */
class StudentController extends AdminStudentController
{
    /** Set by guard(); the class teacher rows are read from it. */
    private ?TeacherDetail $teacher = null;

    /** [[standard_id, section_id|null], …] — cached for the request. */
    private ?array $pairs = null;

    protected function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole('teacher')) return [null, $err];

        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }

        $this->teacher = TeacherDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (!$this->teacher) {
            return [null, $this->error('Teacher profile not found.', 404)];
        }

        return [$user, null];
    }

    // ── The classes this teacher is class teacher of ─────────────────────────

    /** @return \Illuminate\Support\Collection<AssignTeacherStandard> */
    private function assignments()
    {
        if (!$this->teacher) return collect();

        $rows = AssignTeacherStandard::with(['standard:id,name,order', 'section:id,name'])
            ->where('organization_id', $this->teacher->organization_id)
            ->where('teacher_detail_id', $this->teacher->id)
            ->get();

        return AssignTeacherStandard::sortInClassOrder($rows);
    }

    private function myPairs(): array
    {
        if ($this->pairs === null) {
            $this->pairs = $this->assignments()
                ->map(fn ($a) => [(int) $a->standard_id, $a->section_id ? (int) $a->section_id : null])
                ->all();
        }

        return $this->pairs;
    }

    protected function restrict($query)
    {
        $pairs = $this->myPairs();
        if (!$pairs) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($pairs) {
            foreach ($pairs as [$standardId, $sectionId]) {
                $q->orWhere(fn ($w) => $w->where('standard_id', $standardId)
                    ->when($sectionId, fn ($x) => $x->where('section_id', $sectionId)));
            }
        });
    }

    protected function mayTouch(?int $standardId, ?int $sectionId): bool
    {
        foreach ($this->myPairs() as [$myStandard, $mySection]) {
            if ($myStandard === (int) $standardId && ($mySection === null || $mySection === (int) $sectionId)) {
                return true;
            }
        }

        return false;
    }

    // ── What the form may choose from ────────────────────────────────────────

    /** GET /teacher/students/classes */
    public function classes()
    {
        [, $err] = $this->guard();
        if ($err) return $err;

        return $this->success([
            'classes' => $this->assignments()->map(fn ($a) => [
                'standard_id' => (int) $a->standard_id,
                'class'       => $a->standard->name ?? null,
                'section_id'  => $a->section_id ? (int) $a->section_id : null,
                'section'     => $a->section->name ?? null,
            ])->values(),
        ], 'Class teacher classes fetched.');
    }

    /**
     * GET /teacher/students/lookups — the admin lookups, cut to this teacher's
     * own classes and their sections. The routes are the school's, as the
     * transport question is the same one.
     */
    public function lookups(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $pairs       = $this->myPairs();
        $standardIds = array_values(array_unique(array_map(fn ($p) => $p[0], $pairs)));

        $classes = Standard::whereIn('id', $standardIds)
            ->where('organization_id', $user->organization_id)
            ->orderBy('order')->orderBy('id')
            ->get(['id', 'name', 'code', 'board']);

        // A pair with no section means the whole class is theirs.
        $wholeClasses = array_values(array_map(
            fn ($p) => $p[0],
            array_filter($pairs, fn ($p) => $p[1] === null),
        ));
        $sectionIds = array_values(array_filter(array_map(fn ($p) => $p[1], $pairs)));

        $sections = Section::whereIn('standard_id', $standardIds)
            ->where(fn ($q) => $q->whereIn('id', $sectionIds)->orWhereIn('standard_id', $wholeClasses))
            ->when($request->filled('standard_id'), fn ($q) => $q->where('standard_id', $request->standard_id))
            ->orderBy('id')
            ->get(['id', 'name', 'code', 'standard_id']);

        $routes = Transportation::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->orderBy('route_name')
            ->get(['id', 'route_name', 'monthly_fee']);

        return $this->success([
            'classes'  => $classes,
            'sections' => $sections,
            'routes'   => $routes,
        ], 'Student lookups fetched.');
    }
}
