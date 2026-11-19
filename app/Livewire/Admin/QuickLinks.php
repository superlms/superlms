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

        // The tiles are the sidebar's own list — the same screens, in the same
        // order — so a screen that is not in the sidebar (Exam Copy, opened
        // from More) is not a tile here either. It used to read the whole menu.
        $configLinks = \App\Support\AdminMenu::sidebar(auth()->user());

        // Drop the Quick Links tile itself.
        $configLinks = array_filter($configLinks, fn($link) => $link['link'] !== 'admin.quick-links');

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

        // The page has no top bar on a computer (components.layouts.app), as
        // the Super Admin's Quick Links (2026-11-19): what the bar held comes
        // onto the page's own header — the session, Ask AI, the clock,
        // notifications and messages (Profile and Logout are on the
        // sidebar's user card). Since 2026-11-19 it greets the role
        // ("Welcome! School Admin") in place of the page's name.
        $user = auth()->user();

        return view('livewire.admin.quick-links', [
            'orderedLinks'        => $links,
            'roleLabel'           => $user?->role === 'sub-admin' ? 'Sub Admin' : 'School Admin',
            'columns'             => $this->safeColumns(),
            'rows'                => $this->safeRows(),
            'organization'        => $this->organization,
            'sessionLabel'        => \App\Models\AcademicSession::currentLabel(),
            'unreadNotifications' => $this->unreadNotifications(),
            'unreadMessages'      => $this->unreadMessages(),
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

    /** Unread panel-to-panel chat messages (as the top bar counts them). */
    private function unreadMessages(): int
    {
        $user = auth()->user();
        if (!$user) {
            return 0;
        }

        try {
            $conversationIds = \App\Models\Chat\Conversation::whereHas(
                'participants',
                fn ($q) => $q->where('user_id', $user->id)
            )->pluck('id');

            if ($conversationIds->isEmpty()) {
                return 0;
            }

            return \App\Models\Chat\Message::whereIn('conversation_id', $conversationIds)
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
