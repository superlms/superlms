<?php

namespace App\Livewire\Admin;

use App\Models\Admin\RulesAndRegulation as AdminRulesAndRegulation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class RulesAndRegulation extends Component
{
    use WireUiActions, WithFileUploads;

    public $activeTab = 'view'; // ← view first

    // Form fields
    public $sections       = [];
    public $existingContent = null;
    public $organizationId;
    public $additionalInfo = [];
    public $files          = [];
    public $fileTitles     = [];

    protected $rules = [
        'sections.*.head'        => 'required|string|max:255',
        'sections.*.desc'        => 'required|string',
        'additionalInfo.*.key'   => 'nullable|string|max:255',
        'additionalInfo.*.value' => 'nullable|string',
        'files.*'                => 'nullable|file|mimes:pdf|max:2048', 
        'fileTitles.*'           => 'required_with:files.*|string|max:255',
    ];

    public function mount(): void
    {
        $this->organizationId = Auth::user()->organization_id;
        $this->loadContent();
    }

    public function loadContent(): void
    {
        $this->existingContent = AdminRulesAndRegulation::where('organization_id', $this->organizationId)
            ->first();

        if ($this->existingContent) {
            $content            = $this->existingContent->content ?? [];
            $this->sections     = $content['sections'] ?? [];
            $this->additionalInfo = $content['additional_info'] ?? [];
        } else {
            $this->sections       = [['head' => '', 'desc' => '']];
            $this->additionalInfo = [];
        }

        $this->files      = [];
        $this->fileTitles = [];
    }

    // ─── Sections ────────────────────────────────────────────────────────────

    public function addSection(): void
    {
        $this->sections[] = ['head' => '', 'desc' => ''];
    }

    public function removeSection(int $index): void
    {
        if (count($this->sections) > 1) {
            unset($this->sections[$index]);
            $this->sections = array_values($this->sections);
        }
    }

    // ─── Additional info ─────────────────────────────────────────────────────

    public function addAdditionalInfo(): void
    {
        $this->additionalInfo[] = ['key' => '', 'value' => ''];
    }

    public function removeAdditionalInfo(int $index): void
    {
        unset($this->additionalInfo[$index]);
        $this->additionalInfo = array_values($this->additionalInfo);
    }

    // ─── File fields (new uploads) ────────────────────────────────────────────

    public function addFileField(): void
    {
        $this->files[]      = null;
        $this->fileTitles[] = '';
    }

    public function removeFileField(int $index): void
    {
        unset($this->files[$index], $this->fileTitles[$index]);
        $this->files      = array_values($this->files);
        $this->fileTitles = array_values($this->fileTitles);
    }

    // ─── Remove an already-saved file ────────────────────────────────────────

    public function removeExistingFile(int $index): void
    {
        if (!$this->existingContent) return;

        $content = $this->existingContent->content ?? [];
        $files   = $content['files'] ?? [];

        if (isset($files[$index])) {
            // Delete from S3
            $path = parse_url($files[$index]['file_path'] ?? '', PHP_URL_PATH);
            if ($path) Storage::disk('s3')->delete($path);

            unset($files[$index]);
            $content['files'] = array_values($files);
            $this->existingContent->update(['content' => $content]);
            $this->loadContent();
            $this->notification()->success('File removed successfully!');
        }
    }

    // ─── Save ─────────────────────────────────────────────────────────────────

    public function saveContent(): void
    {
        $this->validate();

        // Build base content
        $contentData = [
            'sections'       => $this->sections,
            'additional_info' => $this->additionalInfo,
            'last_updated'   => now()->toDateTimeString(),
        ];

        // Keep existing files
        $existingMeta  = $this->existingContent ? ($this->existingContent->content ?? []) : [];
        $existingFiles = $existingMeta['files'] ?? [];

        // Append new uploads
        $uploadedFiles = [];
        foreach ($this->files as $index => $file) {
            if ($file) {
                $filePath = $file->store('admin/rules-regulations/files', 's3');
                Storage::disk('s3')->setVisibility($filePath, 'public');

                $uploadedFiles[] = [
                    'title'     => $this->fileTitles[$index] ?? 'Document',
                    'file_path' => Storage::disk('s3')->url($filePath),
                    'file_type' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(), // bytes – for size badge
                ];
            }
        }

        $contentData['files'] = array_merge($existingFiles, $uploadedFiles);

        $data = [
            'organization_id' => $this->organizationId,
            'content'         => $contentData,
        ];

        if ($this->existingContent) {
            $this->existingContent->update($data);
            $this->notification()->success('Rules & Regulations updated successfully!');
        } else {
            AdminRulesAndRegulation::create($data);
            $this->notification()->success('Rules & Regulations created successfully!');
        }

        $this->loadContent();
        $this->activeTab = 'view';
    }

    // ─── Tab ─────────────────────────────────────────────────────────────────

    public function showTab(string $tab): void
    {
        $this->activeTab = $tab;
        if ($tab === 'edit') {
            $this->loadContent();
        }
    }

    public function render()
    {
        return view('livewire.admin.rules-and-regulation');
    }
}
