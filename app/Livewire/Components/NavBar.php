<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class NavBar extends Component
{
    public bool   $showSuperAdminLogoutModal = false;
    public bool   $showAdminLogoutModal      = false;
    public bool   $showAccountsLogoutModal   = false;
    public bool   $showNotifications         = false;

    public string $search        = '';
    public array  $searchResults = [];
    public array  $recentSearches = [];

    private array $colorMap = [
        'blue'    => ['bg' => 'bg-blue-100',    'text' => 'text-blue-600'],
        'indigo'  => ['bg' => 'bg-indigo-100',  'text' => 'text-indigo-600'],
        'purple'  => ['bg' => 'bg-purple-100',  'text' => 'text-purple-600'],
        'green'   => ['bg' => 'bg-green-100',   'text' => 'text-green-600'],
        'yellow'  => ['bg' => 'bg-yellow-100',  'text' => 'text-yellow-600'],
        'pink'    => ['bg' => 'bg-pink-100',    'text' => 'text-pink-600'],
        'teal'    => ['bg' => 'bg-teal-100',    'text' => 'text-teal-600'],
        'rose'    => ['bg' => 'bg-rose-100',    'text' => 'text-rose-600'],
        'cyan'    => ['bg' => 'bg-cyan-100',    'text' => 'text-cyan-600'],
        'lime'    => ['bg' => 'bg-lime-100',    'text' => 'text-lime-600'],
        'fuchsia' => ['bg' => 'bg-fuchsia-100', 'text' => 'text-fuchsia-600'],
        'red'     => ['bg' => 'bg-red-100',     'text' => 'text-red-600'],
        'orange'  => ['bg' => 'bg-orange-100',  'text' => 'text-orange-600'],
        'amber'   => ['bg' => 'bg-amber-100',   'text' => 'text-amber-600'],
        'sky'     => ['bg' => 'bg-sky-100',     'text' => 'text-sky-600'],
        'violet'  => ['bg' => 'bg-violet-100',  'text' => 'text-violet-600'],
        'gray'    => ['bg' => 'bg-gray-100',    'text' => 'text-gray-500'],
    ];

    public function mount(): void
    {
        $this->recentSearches = session()->get('nav_recent_searches', []);
    }

    // ─── Search ───────────────────────────────────────────────────────────────

    public function updatedSearch(): void
    {
        if (trim($this->search) === '') {
            $this->searchResults = [];
            return;
        }

        $role      = Auth::user()->role;
        $menuKey   = \App\Helpers\Constants::ROLEVALUE[$role] ?? $role;
        $navItems  = config('menu')[$menuKey] ?? [];
        // Hide modules this school has not been granted (core items always stay).
        $navItems  = \App\Support\ModuleAccess::filterMenu($navItems, Auth::user()?->organization);
        $colorKeys = array_keys($this->colorMap);

        $this->searchResults = collect($navItems)
            ->filter(fn($item) => str_contains(
                strtolower($item['title']),
                strtolower(trim($this->search))
            ))
            ->values()
            ->take(7)
            ->map(function ($item) use ($colorKeys) {
                $color = $colorKeys[abs(crc32($item['title'])) % count($colorKeys)];
                return [
                    'title'     => $item['title'],
                    'link'      => $item['link'],
                    'icon'      => $item['icon'],
                    'bgClass'   => $this->colorMap[$color]['bg'],
                    'textClass' => $this->colorMap[$color]['text'],
                ];
            })
            ->toArray();
    }

    public function navigateTo(string $route, string $title, string $icon, string $bgClass, string $textClass): mixed
    {
        $recents = session()->get('nav_recent_searches', []);

        // Remove duplicate
        $recents = array_values(array_filter($recents, fn($r) => $r['route'] !== $route));

        // Prepend new
        array_unshift($recents, [
            'title'     => $title,
            'route'     => $route,
            'icon'      => $icon,
            'bgClass'   => $bgClass,
            'textClass' => $textClass,
        ]);

        $recents = array_slice($recents, 0, 5);

        session()->put('nav_recent_searches', $recents);

        $this->recentSearches = $recents;
        $this->search         = '';
        $this->searchResults  = [];

        // ✅ Pass organization param required by all admin routes
        return redirect()->route($route, [
            'organization' => Auth::user()->organization_id,
        ]);
    }

    public function clearRecentSearches(): void
    {
        session()->forget('nav_recent_searches');
        $this->recentSearches = [];
    }

    public function confirmLogout(): void
    {
        if (in_array(Auth::user()->role, ['super-admin', 'sub-super-admin'])) {
            $this->showSuperAdminLogoutModal = true;
        } elseif (Auth::user()->role === 'accounts') {
            $this->showAccountsLogoutModal = true;
        } else {
            $this->showAdminLogoutModal = true;
        }
    }

    #[On('open-notifications')]
    public function openNotifications(): void
    {
        $this->showNotifications = true;
    }

    #[On('open-logout-confirm')]
    public function openLogoutConfirm(): void
    {
        $this->confirmLogout();
    }

    public function superAdminLogout(): mixed
    {
        Auth::logout();
        return redirect()->route('super-admin.login');
    }

    public function adminLogout(): mixed
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }

    public function accountsLogout(): mixed
    {
        session()->forget('accounts_otp_verified');
        Auth::logout();
        return redirect()->route('accounts.login');
    }

    public function profilePage(): mixed
    {
        if (in_array(Auth::user()->role, ['super-admin', 'sub-super-admin'])) {
            return redirect()->route('super-admin.profile');
        }
        if (Auth::user()->role === 'accounts') {
            return redirect()->route('accounts.profile', ['organization' => Auth::user()->organization_id]);
        }
        return redirect()->route('admin.profile', ['organization' => Auth::user()->organization]);
    }

    public function messagesPage(): mixed
    {
        $user = Auth::user();

        if (in_array($user->role, ['admin', 'sub-admin'], true)) {
            return redirect()->route('admin.messages', ['organization' => $user->organization_id]);
        }
        if ($user->role === 'accounts') {
            return redirect()->route('accounts.messages', ['organization' => $user->organization_id]);
        }

        return null;
    }

    /**
     * Unread panel-to-panel chat messages for the navbar badge.
     * Guarded so a missing chat table can never break the global navbar.
     */
    protected function unreadMessagesCount(): int
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'sub-admin', 'accounts'], true)) {
            return 0;
        }

        try {
            $conversationIds = \App\Models\Chat\Conversation::whereHas(
                'participants',
                fn($q) => $q->where('user_id', $user->id)
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

    /**
     * Unread in-app notifications for the navbar bell badge.
     * Guarded so a missing notifications table can never break the navbar.
     */
    protected function unreadNotificationsCount(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        try {
            return $user->unreadNotifications()->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Re-render the badge when notifications are marked read. */
    #[On('notifications-updated')]
    public function refreshNotifications(): void
    {
        //
    }

    public function render()
    {
        return view('livewire.components.nav-bar', [
            'unreadMessages'      => $this->unreadMessagesCount(),
            'unreadNotifications' => $this->unreadNotificationsCount(),
        ]);
    }
}
