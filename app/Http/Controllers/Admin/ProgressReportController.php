<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Exam;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Support\ModuleAccess;
use App\Support\ProgressReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Performance → Progress Report's PDF: one exam's report card on an A5 page,
 * for one student (?student=) or for every student of the section who has
 * marks in it, a card to a page. ?exam=, ?standard= and ?section= pick them;
 * every one must be the signed-in school's.
 */
class ProgressReportController extends Controller
{
    /** Streamed inline, for the View panel's frame. */
    public function view(Request $request, $organization)
    {
        [$head, $cards] = $this->cards($request);

        return ProgressReport::pdf($head, $cards)->stream(ProgressReport::fileName($head, $cards));
    }

    public function download(Request $request, $organization)
    {
        [$head, $cards] = $this->cards($request);

        return ProgressReport::pdf($head, $cards)->download(ProgressReport::fileName($head, $cards));
    }

    /** @return array{0: array, 1: array} the sheet's head and the cards */
    private function cards(Request $request): array
    {
        $user = Auth::user();
        $org  = $user->organization;
        $orgId = (int) $user->organization_id;

        // The routes are not on the module map (only the Performance screen
        // is), so its switch is checked here.
        abort_unless($org && ModuleAccess::allows($org, 'admin.performance'), 403,
            'This feature is not enabled for your school. Please contact your administrator.');

        $exam     = Exam::where('organization_id', $orgId)->findOrFail((int) $request->query('exam'));
        $standard = Standard::where('organization_id', $orgId)->findOrFail((int) $request->query('standard'));
        $section  = Section::where('standard_id', $standard->id)->findOrFail((int) $request->query('section'));

        $cards = ProgressReport::cards($orgId, $exam, (int) $standard->id, (int) $section->id);

        if ($student = (int) $request->query('student')) {
            $cards = array_values(array_filter($cards, fn ($c) => $c['student_id'] === $student));
        }

        abort_if($cards === [], 404, 'No marks have been uploaded in this exam for this class yet.');

        return [ProgressReport::head($org, $exam, $standard, $section), $cards];
    }
}
