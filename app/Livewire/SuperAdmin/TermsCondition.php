<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Admin\TermAndCondition;
use Illuminate\Support\Facades\Storage;
use WireUi\Traits\WireUiActions;

class TermsCondition extends Component
{
    use WithFileUploads, WireUiActions;

    public $termsCondition;

    // Basic Information
    public $platform_name;
    public $company_name;
    public $company_cin;
    public $last_updated;
    public $platform_logo;
    public $logoPreview;

    // Terms & Conditions Sections
    public $sections = [];

    // Additional Metadata
    public $additional_info = [];

    // Active tab for view/edit
    public $activeTab = 'view';

    // For file uploads
    public $files = [];
    public $file_titles = [];

    // Delete confirmation indexes
    public $pendingDeleteSectionIndex = null;
    public $pendingDeleteAdditionalInfoIndex = null;
    public $pendingDeleteFileIndex = null;

    // Rules for validation
    protected $rules = [
        'platform_name' => 'required|string|max:255',
        'company_name' => 'required|string|max:255',
        'company_cin' => 'nullable|string|max:50',
        'last_updated' => 'nullable|date',
        'platform_logo' => 'nullable|image|max:2048',
        'sections.*.head' => 'required|string|max:255',
        'sections.*.desc' => 'required|string',
        'additional_info.*.key' => 'nullable|string|max:255',
        'additional_info.*.value' => 'nullable|string',
        'files.*' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        'file_titles.*' => 'required_with:files.*|string|max:255',
    ];

    public function mount()
    {
        $this->loadData();
    }

    private function loadData()
    {
        $this->termsCondition = TermAndCondition::first();

        if ($this->termsCondition) {
            $this->platform_name = $this->termsCondition->platform_name;
            $this->company_name = $this->termsCondition->company_name;
            $this->company_cin = $this->termsCondition->company_cin;
            $this->last_updated = $this->termsCondition->last_updated
                ? $this->termsCondition->last_updated->format('Y-m-d')
                : now()->format('Y-m-d');
            $this->logoPreview = $this->termsCondition->platform_logo;

            // Load sections from metadata
            $metadata = $this->termsCondition->metadata ?? [];
            $this->sections = $metadata['sections'] ?? [['head' => '', 'desc' => '']];
            $this->additional_info = $metadata['additional_info'] ?? [];

            // Reset new file upload fields
            $this->files = [];
            $this->file_titles = [];
        } else {
            // Initialize with one empty section
            $this->sections = [['head' => '', 'desc' => '']];
            $this->additional_info = [];
            $this->last_updated = now()->format('Y-m-d');
        }
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;

        if ($tab === 'edit') {
            $this->loadData();
        }
    }

    // ─── Section Methods ────────────────────────────────────────────────────

    public function addSection()
    {
        $this->sections[] = ['head' => '', 'desc' => ''];
    }

    public function confirmRemoveSection($index)
    {
        $this->pendingDeleteSectionIndex = $index;
    }

    public function executeRemoveSection()
    {
        if ($this->pendingDeleteSectionIndex !== null && count($this->sections) > 1) {
            unset($this->sections[$this->pendingDeleteSectionIndex]);
            $this->sections = array_values($this->sections);
        }
        $this->pendingDeleteSectionIndex = null;
    }

    public function cancelRemoveSection()
    {
        $this->pendingDeleteSectionIndex = null;
    }

    // Keep old method for compatibility
    public function removeSection($index)
    {
        $this->confirmRemoveSection($index);
    }

    // ─── Additional Info Methods ─────────────────────────────────────────────

    public function addAdditionalInfo()
    {
        $this->additional_info[] = ['key' => '', 'value' => ''];
    }

    public function confirmRemoveAdditionalInfo($index)
    {
        $this->pendingDeleteAdditionalInfoIndex = $index;
    }

    public function executeRemoveAdditionalInfo()
    {
        if ($this->pendingDeleteAdditionalInfoIndex !== null) {
            unset($this->additional_info[$this->pendingDeleteAdditionalInfoIndex]);
            $this->additional_info = array_values($this->additional_info);
        }
        $this->pendingDeleteAdditionalInfoIndex = null;
    }

    public function cancelRemoveAdditionalInfo()
    {
        $this->pendingDeleteAdditionalInfoIndex = null;
    }

    // Keep old method for compatibility
    public function removeAdditionalInfo($index)
    {
        $this->confirmRemoveAdditionalInfo($index);
    }

    // ─── File Field Methods ──────────────────────────────────────────────────

    public function addFileField()
    {
        $this->files[] = null;
        $this->file_titles[] = '';
    }

    public function removeFileField($index)
    {
        unset($this->files[$index]);
        unset($this->file_titles[$index]);
        $this->files = array_values($this->files);
        $this->file_titles = array_values($this->file_titles);
    }

    public function confirmDeleteFile($index)
    {
        $this->pendingDeleteFileIndex = $index;
    }

    public function executeDeleteFile()
    {
        if ($this->pendingDeleteFileIndex !== null) {
            $files = $this->termsCondition->metadata['files'] ?? [];

            if (isset($files[$this->pendingDeleteFileIndex])) {
                $file = $files[$this->pendingDeleteFileIndex];

                if (isset($file['file_path'])) {
                    $filePath = parse_url($file['file_path'], PHP_URL_PATH);
                    Storage::disk('s3')->delete($filePath);
                }

                unset($files[$this->pendingDeleteFileIndex]);
                $files = array_values($files);

                $metadata = $this->termsCondition->metadata;
                $metadata['files'] = $files;
                $this->termsCondition->update(['metadata' => $metadata]);

                $this->notification()->success('Deleted', 'File deleted successfully!');
            }
        }
        $this->pendingDeleteFileIndex = null;
    }

    public function cancelDeleteFile()
    {
        $this->pendingDeleteFileIndex = null;
    }

    // Keep old method for compatibility
    public function deleteFile($index)
    {
        $this->confirmDeleteFile($index);
    }

    // ─── Save ────────────────────────────────────────────────────────────────

    public function save()
    {
        $this->validate();

        // Prepare metadata
        $metadata = [
            'sections' => $this->sections,
            'additional_info' => $this->additional_info,
        ];

        $data = [
            'platform_name' => $this->platform_name,
            'company_name' => $this->company_name,
            'company_cin' => $this->company_cin,
            'last_updated' => $this->last_updated,
            'metadata' => $metadata,
        ];

        // Handle logo upload to S3
        if ($this->platform_logo) {
            if ($this->termsCondition && $this->termsCondition->platform_logo) {
                $oldLogoPath = parse_url($this->termsCondition->platform_logo, PHP_URL_PATH);
                Storage::disk('s3')->delete($oldLogoPath);
            }

            $logoPath = $this->platform_logo->store('superadmin/terms-conditions/logos', 's3');
            Storage::disk('s3')->setVisibility($logoPath, 'public');
            $data['platform_logo'] = Storage::disk('s3')->url($logoPath);
        }

        if ($this->termsCondition) {
            $this->termsCondition->update($data);
        } else {
            $this->termsCondition = TermAndCondition::create($data);
        }

        // Handle file uploads
        if ($this->files) {
            $uploadedFiles = [];
            foreach ($this->files as $index => $file) {
                if ($file) {
                    $filePath = $file->store('superadmin/terms-conditions/files', 's3');
                    Storage::disk('s3')->setVisibility($filePath, 'public');

                    $uploadedFiles[] = [
                        'title' => $this->file_titles[$index] ?? 'Document',
                        'file_path' => Storage::disk('s3')->url($filePath),
                        'file_type' => $file->getClientOriginalExtension(),
                    ];
                }
            }

            if (!empty($uploadedFiles)) {
                $currentFiles = $this->termsCondition->metadata['files'] ?? [];
                $allFiles = array_merge($currentFiles, $uploadedFiles);

                $metadata['files'] = $allFiles;
                $this->termsCondition->update(['metadata' => $metadata]);
            }
        }

        $this->notification()->success('Success', 'Terms Of Use saved successfully!');
        $this->activeTab = 'view';
    }

    public function render()
    {
        return view('livewire.super-admin.terms-condition');
    }
}
