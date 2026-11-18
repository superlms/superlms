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

        // Each tile's soft tint follows its menu position ('order'), so it
        // stays the same in either sort (the view picks it).
        $user = auth()->user();

        // Sub-super-admins only see tiles for the functionalities granted to
        // them (Users management is reserved for the main super-admin).
        $configLinks = array_values(array_filter(
            $configLinks,
            function ($link) use ($user) {
                if ($link['link'] === 'super-admin.quick-links') {
                    return false;
                }
                // Off the sidebar ('sidebar' => false, e.g. Credit) = no tile either.
                if (($link['sidebar'] ?? true) === false) {
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

        // The page has no top bar on a computer (components.layouts.app), so
        // what the bar held comes onto the page's own header: the session, the
        // assistant, notifications, Profile and Logout.
        $user        = auth()->user();
        $sessionLink = $user?->canAccessSuperAdminRoute('super-admin.sessions') ?? false;

        return view('livewire.super-admin.quick-links', [
            'orderedLinks'        => $links,
            'sessionLabel'        => \App\Models\AcademicSession::currentLabel(),
            'sessionLink'         => $sessionLink,
            'sessionList'         => $sessionLink ? \App\Models\AcademicSession::switchable() : collect(),
            'unreadNotifications' => $this->unreadNotifications(),
            'assistantEnabled'    => $user
                && in_array($user->role, (array) config('gemini.roles', []), true)
                && app(\App\Services\Gemini\GeminiAssistant::class)->available(),
        ]);
    }

    /** Re-render the bell's badge when notifications are read. */
    #[\Livewire\Attributes\On('notifications-updated')]
    public function refreshNotifications(): void
    {
        //
    }

    private function unreadNotifications(): int
    {
        try {
            return (int) auth()->user()?->unreadNotifications()->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
