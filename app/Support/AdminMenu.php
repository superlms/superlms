<?php

namespace App\Support;

use App\Models\User;

/**
 * The school panel's sidebar, as one list.
 *
 * The sidebar and the Quick Links screen both show it, so both ask here: they
 * cannot drift apart again (Exam Copy left the sidebar for the More screen and
 * went on showing in Quick Links, which was reading the whole menu).
 *
 * config/menu.php's 'admin' list is wider than the sidebar: it also carries
 * screens that are opened from More ('sidebar' => false), which the page
 * search and the screens a sub-admin can be granted still need to find.
 */
class AdminMenu
{
    /**
     * What this user's sidebar lists, in order.
     *
     * - A sub-admin sees only the screens granted to them, and never Users.
     * - A screen of a module the school has not been given is left out.
     * - A screen marked 'sidebar' => false is opened from More instead.
     */
    public static function sidebar(?User $user): array
    {
        $items = config('menu.admin', []);

        if ($user?->role === 'sub-admin') {
            $granted = (array) $user->permissions;
            $items = array_filter(
                $items,
                fn ($i) => ($i['link'] ?? '') !== 'admin.users' && in_array($i['link'] ?? '', $granted, true)
            );
        }

        $items = ModuleAccess::filterMenu(array_values($items), $user?->organization);

        return array_values(array_filter($items, fn ($i) => ($i['sidebar'] ?? true) !== false));
    }
}
