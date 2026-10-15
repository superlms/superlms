<?php

namespace App\Http\Middleware;

use App\Support\CustomFee;
use App\Support\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks direct access (typed URL / bookmark) to a feature route that the
 * current user's school does not have enabled. Hiding the menu item alone is
 * not enough — this is the real enforcement layer.
 *
 * Core routes (not mapped to any module in config/modules.php) always pass.
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $moduleKey = ModuleAccess::moduleForLink($routeName);

        // Custom Fee swaps the ordinary fee screens for its own (App\Support\CustomFee).
        if (CustomFee::linkAllowed($request->user()?->organization, $routeName) === false) {
            abort(403, 'This feature is not enabled for your school. Please contact your administrator.');
        }

        if ($moduleKey !== null) {
            $org = $request->user()?->organization;

            if ($org && !$org->hasModule($moduleKey)) {
                abort(403, 'This feature is not enabled for your school. Please contact your administrator.');
            }
        }

        return $next($request);
    }
}
