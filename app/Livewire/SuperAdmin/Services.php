<?php

namespace App\Livewire\SuperAdmin;

use App\Livewire\SuperAdmin\Concerns\ManagesWebsitePage;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class Services extends Component
{
    use WireUiActions, ManagesWebsitePage;

    public function mount(): void
    {
        $this->slug = 'services';
        $this->loadPage();
    }

    protected function defaultMeta(): array
    {
        return [
            'tag'      => '',
            'title'    => '',
            'subtitle' => '',
            'items'    => [$this->rowTemplates()['items']],
        ];
    }

    protected function rowTemplates(): array
    {
        return ['items' => ['icon' => '', 'title' => '', 'desc' => '']];
    }

    public function render()
    {
        return view('livewire.super-admin.website.services');
    }
}
