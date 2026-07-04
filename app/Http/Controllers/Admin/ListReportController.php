<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SchoolInfo;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Services\Lists\ListReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListReportController extends Controller
{
    /**
     * Generate a custom list as a printable PDF table.
     * GET /{organization}/lists/pdf?type=student&standard_id=..&columns=a,b,c&blanks=2
     */
    public function generate(Request $request, ListReportService $service)
    {
        $orgId = Auth::user()?->organization_id;
        abort_if(!$orgId, 403);

        $defs = ListReportService::definitions();
        $type = (string) $request->query('type');
        abort_if(!isset($defs[$type]), 404, 'Unknown list type.');

        $def = $defs[$type];

        // Ensure required filters are present.
        $filterParam = ['standard' => 'standard_id', 'section' => 'section_id', 'exam' => 'exam_id', 'month' => 'month'];
        foreach ($def['filters'] as $name => $rule) {
            if ($rule === 'required' && blank($request->query($filterParam[$name] ?? $name))) {
                abort(422, ucfirst($name) . ' is required for this list.');
            }
        }

        $columns = $request->query('columns');
        $columns = is_array($columns) ? $columns : array_filter(explode(',', (string) $columns));

        $params = [
            'standard_id' => $request->integer('standard_id') ?: null,
            'section_id'  => $request->integer('section_id') ?: null,
            'exam_id'     => $request->integer('exam_id') ?: null,
            'month'       => $request->query('month') ?: null,
            'columns'     => $columns,
        ];

        $data   = $service->build($type, $orgId, $params);
        $blanks = max(0, min(10, (int) $request->query('blanks', 0)));

        // If nothing was selected at all, fall back to every column for the type.
        if (empty($data['columns']) && $blanks === 0) {
            $params['columns'] = array_keys($def['columns']);
            $data = $service->build($type, $orgId, $params);
        }

        $org        = Organization::find($orgId);
        $schoolInfo = SchoolInfo::where('organization_id', $orgId)->first();

        $title       = trim((string) $request->query('title')) ?: $def['label'] . ' List';
        $orientation = $request->query('orientation') === 'landscape' ? 'landscape' : 'portrait';

        // Human-readable scope line (class / section / exam / month).
        $scope = [];
        if ($params['standard_id']) {
            $std = Standard::where('organization_id', $orgId)->find($params['standard_id']);
            if ($std) $scope[] = 'Class: ' . $std->name;
        }
        if ($params['section_id']) {
            $sec = Section::where('organization_id', $orgId)->find($params['section_id']);
            if ($sec) $scope[] = 'Section: ' . $sec->name;
        }
        if ($params['exam_id']) {
            $exam = \App\Models\Admin\Exam::where('organization_id', $orgId)->find($params['exam_id']);
            if ($exam) $scope[] = 'Exam: ' . $exam->exam_name;
        }
        if ($params['month']) {
            $scope[] = 'Month: ' . \Illuminate\Support\Carbon::parse($params['month'] . '-01')->format('F Y');
        }

        $pdf = Pdf::loadView('pdf.admin.list-report', [
            'title'      => $title,
            'columns'    => $data['columns'],
            'rows'       => $data['rows'],
            'count'      => $data['count'],
            'blanks'     => $blanks,
            'scope'      => $scope,
            'org'        => $org,
            'schoolInfo' => $schoolInfo,
        ])->setPaper('a4', $orientation)
          ->setOption('dpi', 130)
          ->setOption('defaultFont', 'DejaVu Sans');

        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $title);
        return $pdf->stream("{$safe}.pdf");
    }
}
