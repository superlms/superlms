<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;

class QuickLinks extends Component
{
    public array $links = [];

    /** Display order: 'sidebar' (menu order) or 'asc' (A–Z by title). */
    public string $sort = 'sidebar';

    public function mount()
    {
        $configLinks = config('menu.super-admin');

        // Stable palette — colour stays tied to a tile's title regardless of sort.
        $colors = [
            'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose',
            'red', 'orange', 'amber', 'lime', 'green', 'teal', 'cyan', 'sky',
        ];

        $configLinks = array_values(array_filter(
            $configLinks,
            fn($link) => $link['link'] !== 'super-admin.quick-links'
        ));

        foreach ($configLinks as $i => $link) {
            $this->links[] = [
                'title' => $link['title'],
                'route' => $link['link'],
                'icon'  => $link['icon'],
                'color' => $colors[abs(crc32((string) $link['title'])) % count($colors)],
                'order' => $i,
                'notif' => false,
            ];
        }

        // Notifications is a special action tile — kept in the list so it sorts too.
        $this->links[] = [
            'title' => 'Notifications',
            'route' => null,
            'icon'  => 'bell-alert',
            'color' => 'amber',
            'order' => count($this->links),
            'notif' => true,
        ];
    }

    public function render()
    {
        $links = $this->links;

        if ($this->sort === 'asc') {
            usort($links, fn($a, $b) => strcasecmp($a['title'], $b['title']));
        } else {
            usort($links, fn($a, $b) => $a['order'] <=> $b['order']);
        }

        return view('livewire.super-admin.quick-links', [
            'orderedLinks' => $links,
        ]);
    }
}
