<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use App\Models\PrivacyPolicy as PrivacyPolicyModel;
use Carbon\Carbon;
use WireUi\Traits\WireUiActions;

class PrivacyPolicy extends Component
{
    use WireUiActions;

    public $privacyPolicy;

    public $sections     = [];
    public $last_updated = '';
    public $activeTab    = 'view';
    public $pendingDeleteSectionIndex = null;

    protected $rules = [
        'sections.*.head' => 'required|string|max:255',
        'sections.*.desc' => 'required|string',
        'last_updated'    => 'nullable|date',
    ];

    protected $messages = [
        'sections.*.head.required' => 'Section heading is required.',
        'sections.*.desc.required' => 'Section description is required.',
    ];

    public function mount(): void
    {
        $this->loadData();
    }

    private function loadData(): void
    {
        $this->privacyPolicy = PrivacyPolicyModel::first();

        if ($this->privacyPolicy) {
            $metadata       = $this->privacyPolicy->metadata ?? [];
            $this->sections = $metadata['sections'] ?? [['head' => '', 'desc' => '']];
            $this->last_updated = $this->privacyPolicy->last_updated
                ? $this->privacyPolicy->last_updated->format('Y-m-d')
                : now()->format('Y-m-d');
        } else {
            $this->sections     = [['head' => '', 'desc' => '']];
            $this->last_updated = now()->format('Y-m-d');
        }
    }

    // ─── Tab ────────────────────────────────────────────────────────────────

    public function switchTab(string $tab): void
    {
        if ($tab === 'edit') {
            $this->last_updated = now()->format('Y-m-d');
            $this->loadData();
            $this->last_updated = now()->format('Y-m-d'); // override after loadData
        }

        $this->activeTab = $tab;
    }

    // ─── Sections ────────────────────────────────────────────────────────────

    public function addSection(): void
    {
        $this->sections[] = ['head' => '', 'desc' => ''];
    }

    public function confirmRemoveSection(int $index): void
    {
        $this->pendingDeleteSectionIndex = $index;
    }

    public function executeRemoveSection(): void
    {
        if ($this->pendingDeleteSectionIndex !== null && count($this->sections) > 1) {
            unset($this->sections[$this->pendingDeleteSectionIndex]);
            $this->sections = array_values($this->sections);
        }
        $this->pendingDeleteSectionIndex = null;
    }

    public function cancelRemoveSection(): void
    {
        $this->pendingDeleteSectionIndex = null;
    }

    public function removeSection(int $index): void
    {
        $this->confirmRemoveSection($index);
    }

    // ─── Save ────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate();

        $metadata = ['sections' => $this->sections];

        $data = [
            'metadata'     => $metadata,
            'last_updated' => $this->last_updated ?: now()->format('Y-m-d'),
        ];

        if ($this->privacyPolicy) {
            $this->privacyPolicy->update($data);
        } else {
            $this->privacyPolicy = PrivacyPolicyModel::create($data);
        }

        $this->notification()->success('Saved', 'Privacy Policy saved successfully!');
        $this->activeTab = 'view';
    }

    public function render()
    {
        return view('livewire.super-admin.privacy-policy');
    }
}