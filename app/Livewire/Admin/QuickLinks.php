<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class QuickLinks extends Component
{
    public array $links = [];

    /** Display order: 'sidebar' (menu order) or 'asc' (A–Z by title). */
    public string $sort = 'sidebar';

    /** Desktop columns per row (mobile is forced to a comfortable count via CSS). */
    public $columns = 6;
    public array $columnOptions = [4, 5, 6, 7, 8, 10];

    /** Rows preference — purely informational for the grid sizing on desktop. */
    public $rows = 5;
    public array $rowOptions = [3, 4, 5, 6, 8];

    /** Captured at mount so route() works during Livewire updates. */
    public $organization = null;

    public function mount(): void
    {
        $this->organization = request()->route('organization')
            ?? auth()->user()?->organization;

        $configLinks = config('menu.admin', []);

        // Drop the Quick Links tile itself.
        $configLinks = array_filter($configLinks, fn($link) => $link['link'] !== 'admin.quick-links');

        // Hide modules this school has not been granted (core items always stay).
        $configLinks = \App\Support\ModuleAccess::filterMenu(
            array_values($configLinks),
            auth()->user()?->organization
        );

        $colors = [
            'blue', 'indigo', 'purple', 'green', 'yellow', 'pink', 'teal', 'rose',
            'cyan', 'lime', 'fuchsia', 'red', 'orange', 'amber', 'sky', 'violet', 'gray',
        ];

        foreach (array_values($configLinks) as $i => $link) {
            $this->links[] = [
                'title' => $link['title'],
                'route' => $link['link'],
                'icon'  => $link['icon'],
                'color' => $colors[abs(crc32((string) $link['title'])) % count($colors)],
                'order' => $i,
            ];
        }
    }

    protected function safeColumns(): int
    {
        $c = (int) $this->columns;
        return in_array($c, $this->columnOptions, true) ? $c : 6;
    }

    protected function safeRows(): int
    {
        $r = (int) $this->rows;
        return in_array($r, $this->rowOptions, true) ? $r : 5;
    }

    public function render()
    {
        $links = $this->links;

        if ($this->sort === 'asc') {
            usort($links, fn($a, $b) => strcasecmp($a['title'], $b['title']));
        } else {
            usort($links, fn($a, $b) => $a['order'] <=> $b['order']);
        }

        return view('livewire.admin.quick-links', [
            'orderedLinks' => $links,
            'columns'      => $this->safeColumns(),
            'rows'         => $this->safeRows(),
            'organization' => $this->organization,
        ]);
    }
}
