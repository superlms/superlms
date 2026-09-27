<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\RulesAndRegulation;
use App\Support\DefaultSchoolRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The admin app's Rules & Regulations editor, as the web panel's
 * (App\Livewire\Admin\RulesAndRegulation) over the same row: the school's
 * sections (heading and description), additional information (key and
 * value) and attached PDFs (a title each, 2 MB). A school with nothing saved
 * yet starts from the standard school rules, as the panel's editor does.
 * Saving keeps the files already there and adds the new ones; a saved file
 * is removed on its own, straight away, as the panel's cross does. The
 * model's saved event tells the school's students and teachers, as it does
 * for the panel. What students and teachers read stays GET /rules-and-regulation.
 *
 *   GET  /admin/rules-and-regulation
 *   POST /admin/rules-and-regulation               (multipart: sections[i][head|desc],
 *                                                   additional_info[i][key|value],
 *                                                   files[i] + file_titles[i])
 *   POST /admin/rules-and-regulation/files/remove  (file_path)
 */
class AdminRulesController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    /** The panel's route, for a sub-admin's permissions. */
    private const PANEL_ROUTE = 'admin.rules-and-regulation';

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        // A sub-admin reaches it as on the panel: only with its permission.
        if (!$user->canAccessAdminRoute(self::PANEL_ROUTE)) {
            return [null, $this->error('You do not have access to Rules & Regulations.', 403)];
        }
        return [$user, null];
    }

    private function shape(?RulesAndRegulation $row): array
    {
        if (!$row) {
            // Nothing published yet: the standard rules, to edit and save.
            return [
                'exists'          => false,
                'using_defaults'  => true,
                'sections'        => DefaultSchoolRules::sections(),
                'additional_info' => [],
                'files'           => [],
                'last_updated'    => null,
            ];
        }

        $content = $row->content ?? [];

        return [
            'exists'          => true,
            'using_defaults'  => false,
            'sections'        => array_values($content['sections'] ?? []),
            'additional_info' => array_values($content['additional_info'] ?? []),
            'files'           => array_values($content['files'] ?? []),
            'last_updated'    => $content['last_updated'] ?? null,
        ];
    }

    /** GET /admin/rules-and-regulation */
    public function show()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $row = RulesAndRegulation::where('organization_id', $user->organization_id)->first();

        return $this->success($this->shape($row), 'Rules & Regulations fetched.');
    }

    /** POST /admin/rules-and-regulation — the panel's Save */
    public function save(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        // The panel's rules; it always keeps at least one section.
        if ($err = $this->validateWith($request, [
            'sections'                => 'required|array|min:1',
            'sections.*.head'         => 'required|string|max:255',
            'sections.*.desc'         => 'required|string',
            'additional_info'         => 'nullable|array',
            'additional_info.*.key'   => 'nullable|string|max:255',
            'additional_info.*.value' => 'nullable|string',
            'files'                   => 'nullable|array',
            'files.*'                 => 'nullable|file|mimes:pdf|max:2048',
            'file_titles'             => 'nullable|array',
            'file_titles.*'           => 'required_with:files.*|nullable|string|max:255',
        ], [
            'sections.required'             => 'Add at least one section.',
            'sections.min'                  => 'Add at least one section.',
            'sections.*.head.required'      => 'Every section needs a heading.',
            'sections.*.head.max'           => 'A heading may be at most 255 characters.',
            'sections.*.desc.required'      => 'Every section needs a description.',
            'additional_info.*.key.max'     => 'A key may be at most 255 characters.',
            'files.*.mimes'                 => 'Documents must be PDF files.',
            'files.*.max'                   => 'Each document must be 2 MB or smaller.',
            'file_titles.*.required_with'   => 'Every document needs a title.',
            'file_titles.*.max'             => 'A document title may be at most 255 characters.',
        ])) return $err;

        // A document's title is required even when no titles were sent at all
        // (required_with only looks at the titles that are there).
        $titles = (array) $request->input('file_titles', []);
        foreach ((array) $request->file('files', []) as $index => $file) {
            if ($file && trim((string) ($titles[$index] ?? '')) === '') {
                return $this->error('Every document needs a title.', 422, ["file_titles.$index" => ['Every document needs a title.']]);
            }
        }

        $row = RulesAndRegulation::where('organization_id', $user->organization_id)->first();

        $sections = collect($request->input('sections', []))
            ->map(fn ($s) => ['head' => (string) ($s['head'] ?? ''), 'desc' => (string) ($s['desc'] ?? '')])
            ->values()->all();
        $additional = collect($request->input('additional_info', []))
            ->map(fn ($a) => ['key' => $a['key'] ?? null, 'value' => $a['value'] ?? null])
            ->values()->all();

        $content = [
            'sections'        => $sections,
            'additional_info' => $additional,
            'last_updated'    => now()->toDateTimeString(),
        ];

        // Keep the files already there, then the new uploads.
        $existingFiles = $row ? (($row->content ?? [])['files'] ?? []) : [];
        $uploaded      = [];
        foreach ((array) $request->file('files', []) as $index => $file) {
            if (!$file) continue;
            $path = $file->store('admin/rules-regulations/files', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');

            $uploaded[] = [
                'title'     => $titles[$index] ?? 'Document',
                'file_path' => Storage::disk('s3')->url($path),
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(), // bytes – for size badge
            ];
        }
        $content['files'] = array_merge(array_values($existingFiles), $uploaded);

        if ($row) {
            $row->update(['organization_id' => $user->organization_id, 'content' => $content]);
            $message = 'Rules & Regulations updated successfully!';
        } else {
            $row = RulesAndRegulation::create(['organization_id' => $user->organization_id, 'content' => $content]);
            $message = 'Rules & Regulations created successfully!';
        }

        return $this->success($this->shape($row->fresh()), $message);
    }

    /** POST /admin/rules-and-regulation/files/remove (file_path) — the panel's cross on a saved file */
    public function removeFile(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['file_path' => 'required|string'])) return $err;

        $row = RulesAndRegulation::where('organization_id', $user->organization_id)->first();
        $content = $row ? ($row->content ?? []) : [];
        $files   = array_values($content['files'] ?? []);

        $index = collect($files)->search(fn ($f) => ($f['file_path'] ?? null) === $request->file_path);
        if ($index === false) return $this->error('File not found.', 404);

        // Delete from S3
        $path = parse_url($files[$index]['file_path'] ?? '', PHP_URL_PATH);
        if ($path) {
            try {
                Storage::disk('s3')->delete($path);
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        unset($files[$index]);
        $content['files'] = array_values($files);
        $row->update(['content' => $content]);

        return $this->success($this->shape($row->fresh()), 'File removed successfully!');
    }
}
