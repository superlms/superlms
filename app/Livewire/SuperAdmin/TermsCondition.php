<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use App\Models\Admin\TermAndCondition;
use WireUi\Traits\WireUiActions;

class TermsCondition extends Component
{
    use WireUiActions;

    public $termsCondition;

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

    public function mount()
    {
        $this->loadData();
    }

    private function loadData()
    {
        $this->termsCondition = TermAndCondition::first();

        if ($this->termsCondition) {
            $metadata       = $this->termsCondition->metadata ?? [];
            $this->sections = $metadata['sections'] ?? [['head' => '', 'desc' => '']];
            $this->last_updated = $this->termsCondition->last_updated
                ? $this->termsCondition->last_updated->format('Y-m-d')
                : now()->format('Y-m-d');
        } else {
            $this->sections     = [['head' => '', 'desc' => '']];
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

    // ─── Save ────────────────────────────────────────────────────────────────

    public function save()
    {
        $this->validate();

        // Only the sections are managed here — preserve any other metadata keys
        // the row may still carry. Basic information (platform/company/CIN)
        // now lives in the About App module.
        $metadata = $this->termsCondition?->metadata ?? [];
        $metadata['sections'] = $this->sections;

        $data = [
            'metadata'     => $metadata,
            'last_updated' => $this->last_updated ?: now()->format('Y-m-d'),
        ];

        if ($this->termsCondition) {
            $this->termsCondition->update($data);
        } else {
            $this->termsCondition = TermAndCondition::create($data);
        }

        $this->notification()->success('Success', 'Terms & Conditions saved successfully!');
        $this->activeTab = 'view';
    }

    public function render()
    {
        return view('livewire.super-admin.terms-condition');
    }
}
