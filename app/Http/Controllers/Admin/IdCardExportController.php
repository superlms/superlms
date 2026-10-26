<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Support\IdCardExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdCardExportController extends Controller
{
    /**
     * ID Card → Export's file: students, teachers or management & staff, in
     * the columns and order the school picked, each photo in its cell — an
     * .xlsx, or a .zip of it with a photos/ folder (App\Support\IdCardExport).
     *
     * A whole school's photos take minutes, and the gateway answers 504 after
     * 60 s, so the panel builds the file a few seconds at a time:
     *
     *   POST /{organization}/id-card/export/start?type=student&cols=…&class=3,5,8&section=&which=all&format=xlsx
     *   POST /{organization}/id-card/export/{id}/step   — again until it says ready
     *   GET  /{organization}/id-card/export/{id}/file   — the file
     *
     * GET /{organization}/id-card/export?… still builds it in one request.
     */
    public function start(Request $request, $organization): JsonResponse
    {
        $user = Auth::user();
        [$type, $columns, $classes, $sectionId, $which, $format] = $this->picked($request, (int) $user->organization_id);

        @set_time_limit(110);
        @ini_set('memory_limit', '1024M');

        $job = IdCardExport::start(
            (int) $user->organization_id,
            (int) $user->id,
            (string) ($user->organization->name ?? 'school'),
            $type, $columns, $classes, $sectionId, $which, $format,
        );

        return response()->json($job + [
            'step_url' => route('admin.id-card.export.step', ['organization' => $organization, 'id' => $job['id']]),
            'file_url' => route('admin.id-card.export.file', ['organization' => $organization, 'id' => $job['id']]),
        ]);
    }

    public function step($organization, string $id): JsonResponse
    {
        $user = Auth::user();

        // A step answers in about ten seconds; this is only the safety net.
        @set_time_limit(110);
        @ini_set('memory_limit', '1024M');

        $status = IdCardExport::step($id, (int) $user->organization_id, (int) $user->id);
        abort_if($status === null, 404);

        return response()->json($status);
    }

    public function file($organization, string $id)
    {
        $user = Auth::user();
        $file = IdCardExport::file($id, (int) $user->organization_id, (int) $user->id);
        abort_if($file === null, 404);

        [$path, $name, $mime, $work] = $file;

        return response()->streamDownload(function () use ($path, $work) {
            readfile($path);
            IdCardExport::cleanup($work);
        }, $name, ['Content-Type' => $mime]);
    }

    /** The whole file in one request — fine for a class, too slow for a whole school's photos. */
    public function download(Request $request, $organization)
    {
        $user = Auth::user();
        [$type, $columns, $classes, $sectionId, $which, $format] = $this->picked($request, (int) $user->organization_id);

        @set_time_limit(300);
        @ini_set('memory_limit', '1024M');

        [$path, $name, $mime, $work] = IdCardExport::build(
            (int) $user->organization_id,
            (string) ($user->organization->name ?? 'school'),
            $type, $columns, $classes, $sectionId, $which, $format,
            (int) $user->id,
        );

        return response()->streamDownload(function () use ($path, $work) {
            readfile($path);
            IdCardExport::cleanup($work);
        }, $name, ['Content-Type' => $mime]);
    }

    /**
     * What the panel picked: [type, columns, class ids, section id, which, format].
     * Only this school's classes; a section only of the one class picked.
     */
    private function picked(Request $request, int $orgId): array
    {
        $data = $request->validate([
            'type'    => 'required|in:' . implode(',', IdCardExport::TYPES),
            'cols'    => 'nullable|string|max:2000',
            'class'   => 'nullable|string|max:2000',   // one class id, or several: "3,5,8"
            'section' => 'nullable|integer',
            'which'   => 'nullable|in:' . implode(',', IdCardExport::WHICH),
            'format'  => 'nullable|in:xlsx,zip',
        ]);

        $type    = $data['type'];
        $columns = IdCardExport::clean($type, explode(',', (string) ($data['cols'] ?? '')));

        $asked   = $type === 'student' ? IdCardExport::standardIds($data['class'] ?? null) : [];
        $classes = $asked
            ? Standard::where('organization_id', $orgId)->whereIn('id', $asked)->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];
        $sectionId = count($classes) === 1 && !empty($data['section'])
            ? Section::where('standard_id', $classes[0])->whereKey($data['section'])->value('id')
            : null;

        return [$type, $columns, $classes, $sectionId ? (int) $sectionId : null, $data['which'] ?? 'all', $data['format'] ?? 'xlsx'];
    }
}
