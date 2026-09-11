<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ExamDatesheet;
use App\Models\Student\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DatesheetPrintController extends Controller
{
    /**
     * The datesheet on one landscape page — nothing but the school, the class
     * and the papers.
     * GET /{organization}/datesheet/{id}/print?subject=&section=
     */
    public function print(Request $request, $organization, $id)
    {
        $orgId = Auth::user()->organization_id;

        $datesheet = ExamDatesheet::with([
            'exam:id,exam_name,academic_year',
            'standard:id,name',
            'section:id,name',
            'papers.subject:id,name',
        ])->where('organization_id', $orgId)->findOrFail($id);

        $papers = $datesheet->papers
            ->sortBy(fn ($p) => ($p->exam_date?->toDateString() ?? '9999-12-31') . ' ' . ($p->start_time ?? ''))
            ->values();

        // One subject asked for → print that paper alone.
        if ($subjectId = (int) $request->query('subject')) {
            $papers = $papers->where('subject_id', $subjectId)->values();
        }

        // A class-wide sheet printed for one section carries that section's
        // name, so the copy on the noticeboard says who it is for.
        $sectionName = $datesheet->section->name ?? null;
        if (!$sectionName && ($sectionId = (int) $request->query('section'))) {
            $sectionName = Section::where('id', $sectionId)
                ->where('standard_id', $datesheet->standard_id)
                ->value('name');
        }

        return view('admin.datesheet-print', [
            'datesheet'    => $datesheet,
            'papers'       => $papers,
            'sectionName'  => $sectionName,
            'organization' => Auth::user()->organization,
        ]);
    }
}
