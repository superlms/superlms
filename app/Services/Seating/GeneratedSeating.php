<?php

namespace App\Services\Seating;

use App\Models\Admin\Seating\SeatAssignment;
use App\Models\Admin\Seating\SeatingPlan;
use App\Models\Student\Standard;

/**
 * What an exam's seating plans already hold, read off their seats.
 *
 * A class is seated for an exam once: after its plan is generated it is not
 * generated again (the panel and the app API both ask here). Plans made twice
 * before that rule are left in place, but a listing shows only the newest copy.
 */
class GeneratedSeating
{
    /**
     * The classes (standard ids) that already sit in one of the exam's plans.
     * Seats carry the class as "NURSERY-SECTION A", so plans made before this
     * check count too.
     *
     * @return int[]
     */
    public static function standardIds(int $orgId, int $examId): array
    {
        if (!$orgId || !$examId) {
            return [];
        }

        $planIds = SeatingPlan::where('organization_id', $orgId)->where('exam_id', $examId)->pluck('id');
        if ($planIds->isEmpty()) {
            return [];
        }

        $labels = SeatAssignment::whereIn('seating_plan_id', $planIds)
            ->whereNotNull('student_id')
            ->whereNotNull('class_label')
            ->distinct()
            ->pluck('class_label');
        if ($labels->isEmpty()) {
            return [];
        }

        // The longest name first, so "Class 10-A" is Class 10 and not Class 1.
        $standards = Standard::where('organization_id', $orgId)->get(['id', 'name'])
            ->sortByDesc(fn ($s) => mb_strlen((string) $s->name))
            ->values();

        $ids = [];
        foreach ($labels as $label) {
            foreach ($standards as $s) {
                if ($s->name !== null && $s->name !== '' && str_starts_with((string) $label, $s->name . '-')) {
                    $ids[(int) $s->id] = true;
                    break;
                }
            }
        }

        return array_keys($ids);
    }

    /**
     * The plans a listing shows. In one exam session (date + shift) a plan is
     * hidden when newer plans of that session already seat every class it
     * seats — it is an older copy of the same plan made again.
     *
     * @param  \Illuminate\Support\Collection  $plans  the exam's plans (id, exam_date, session)
     * @return int[]
     */
    public static function currentPlanIds($plans): array
    {
        if ($plans->isEmpty()) {
            return [];
        }

        $labels = SeatAssignment::whereIn('seating_plan_id', $plans->pluck('id'))
            ->whereNotNull('student_id')
            ->select('seating_plan_id', 'class_label')
            ->distinct()
            ->get()
            ->groupBy('seating_plan_id')
            ->map(fn ($g) => $g->pluck('class_label')->filter()->map(fn ($l) => (string) $l)->unique()->values()->all());

        $keep = [];
        $plans->groupBy(fn ($p) => ($p->exam_date?->toDateString() ?? '') . '|' . SeatLocator::shiftOf($p->session))
            ->each(function ($session) use ($labels, &$keep) {
                $covered = [];
                foreach ($session->sortByDesc('id') as $p) {
                    $mine = $labels->get($p->id, []);
                    if (array_diff($mine, $covered)) {
                        $keep[] = (int) $p->id;
                    }
                    $covered = array_merge($covered, $mine);
                }
            });

        return $keep;
    }
}
