<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use App\Models\TermOfUse as TermOfUseModel;
use WireUi\Traits\WireUiActions;

class TermOfUse extends Component
{
    use WireUiActions;

    public $termOfUse;

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
        $this->termOfUse = TermOfUseModel::first();

        if ($this->termOfUse) {
            $metadata       = $this->termOfUse->metadata ?? [];
            $this->sections = $metadata['sections'] ?? [['head' => '', 'desc' => '']];
            $this->last_updated = $this->termOfUse->last_updated
                ? $this->termOfUse->last_updated->format('Y-m-d')
                : now()->format('Y-m-d');
        } else {
            $this->sections     = [['head' => '', 'desc' => '']];
            $this->last_updated = now()->format('Y-m-d');
        }
    }

    public function switchTab(string $tab): void
    {
        if ($tab === 'edit') {
            $this->last_updated = now()->format('Y-m-d');
            $this->loadData();
            $this->last_updated = now()->format('Y-m-d');
        }

        $this->activeTab = $tab;
    }

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


    public function save(): void
    {
        $this->validate();

        $data = [
            'metadata'     => ['sections' => $this->sections],
            'last_updated' => $this->last_updated ?: now()->format('Y-m-d'),
        ];

        if ($this->termOfUse) {
            $this->termOfUse->update($data);
        } else {
            $this->termOfUse = TermOfUseModel::create($data);
        }

        $this->notification()->success('Saved', 'Terms of Use saved successfully!');
        $this->activeTab = 'view';
    }

    public function render()
    {
        return view('livewire.super-admin.term-of-use');
    }
}