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

        $user = auth()->user();

        // Sub-super-admins only see tiles for the functionalities granted to
        // them (Users management is reserved for the main super-admin).
        $configLinks = array_values(array_filter(
            $configLinks,
            function ($link) use ($user) {
                if ($link['link'] === 'super-admin.quick-links') {
                    return false;
                }
                if ($user && $user->isSubSuperAdmin()) {
                    return $link['link'] !== 'super-admin.users'
                        && in_array($link['link'], (array) $user->permissions, true);
                }
                return true;
            }
        ));

        foreach ($configLinks as $i => $link) {
            $this->links[] = [
                'title' => $link['title'],
                'route' => $link['link'],
                'icon'  => $link['icon'],
                'color' => $colors[abs(crc32((string) $link['title'])) % count($colors)],
                'order' => $i,
            ];
        }
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
