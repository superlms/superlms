<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Support\IdCardExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdCardExportController extends Controller
{
    /**
     * ID Card → Export's download: students, teachers or management & staff,
     * in the columns and order the school picked, each photo in its cell —
     * an .xlsx, or a .zip of it with a photos/ folder (App\Support\IdCardExport).
     *
     * GET /{organization}/id-card/export?type=student&cols=sr_no,photo,name&class=&section=&which=all&format=xlsx
     */
    public function download(Request $request, $organization)
    {
        $user  = Auth::user();
        $orgId = (int) $user->organization_id;

        $data = $request->validate([
            'type'    => 'required|in:' . implode(',', IdCardExport::TYPES),
            'cols'    => 'nullable|string|max:2000',
            'class'   => 'nullable|integer',
            'section' => 'nullable|integer',
            'which'   => 'nullable|in:' . implode(',', IdCardExport::WHICH),
            'format'  => 'nullable|in:xlsx,zip',
        ]);

        $type    = $data['type'];
        $columns = IdCardExport::clean($type, explode(',', (string) ($data['cols'] ?? '')));

        // Only this school's class and section.
        $standardId = $type === 'student' && !empty($data['class'])
            ? Standard::where('organization_id', $orgId)->whereKey($data['class'])->value('id')
            : null;
        $sectionId = $standardId && !empty($data['section'])
            ? Section::where('standard_id', $standardId)->whereKey($data['section'])->value('id')
            : null;

        // A whole school's photos take a while to fetch and fit.
        @set_time_limit(300);
        @ini_set('memory_limit', '1024M');

        [$path, $name, $mime, $work] = IdCardExport::build(
            $orgId,
            (string) ($user->organization->name ?? 'school'),
            $type,
            $columns,
            $standardId ? (int) $standardId : null,
            $sectionId ? (int) $sectionId : null,
            $data['which'] ?? 'all',
            $data['format'] ?? 'xlsx',
        );

        return response()->streamDownload(function () use ($path, $work) {
            readfile($path);
            IdCardExport::cleanup($work);
        }, $name, ['Content-Type' => $mime]);
    }
}
