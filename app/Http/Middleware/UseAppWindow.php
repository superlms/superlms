<?php

namespace App\Http\Middleware;

use App\Support\AppWindow;
use Closure;
use Illuminate\Http\Request;

/**
 * Keeps an installed panel app and the browser apart ({@see AppWindow}).
 *
 * A request of an app window gets the app's own session cookie; a request
 * under the app's mount has the mount taken off before it is routed; a
 * navigation of the app that is not under its mount yet (a link, a typed
 * address that came from a page of the app) is sent to its mounted address,
 * so the window never leaves the app's scope; and so is every redirect.
 */
class UseAppWindow
{
    public function handle(Request $request, Closure $next)
    {
        [$mount, $how] = AppWindow::detect($request);
        AppWindow::enter($mount, $how);

        if (!AppWindow::isApp()) {
            return $next($request);
        }

        if ($how !== 'path' && $request->isMethod('GET') && $this->isNavigation($request) && !$this->isFile($request->getPathInfo())) {
            return redirect()->to(AppWindow::mounted($request->getRequestUri()));
        }

        config(['session.cookie' => AppWindow::sessionCookie()]);
        // A session store that already exists keeps the name it was made with.
        app('session')->driver()->setName(AppWindow::sessionCookie());

        if ($how === 'path' && ($split = AppWindow::split($request->getPathInfo()))) {
            $this->setPath($request, $split[1]);
        }

        $response = $next($request);

        if ($response->isRedirection() && ($to = $response->headers->get('Location'))) {
            $ours = preg_match('#^https?://#i', $to)
                ? strcasecmp((string) parse_url($to, PHP_URL_HOST), $request->getHost()) === 0
                : (str_starts_with($to, '/') && !str_starts_with($to, '//'));

            if ($ours && !$this->isFile((string) parse_url($to, PHP_URL_PATH))) {
                $response->headers->set('Location', AppWindow::mounted($to));
            }
        }

        return $response;
    }

    /** A page being loaded in the window — not a fetch, a frame or a file. */
    private function isNavigation(Request $request): bool
    {
        return $request->headers->get('Sec-Fetch-Mode') === 'navigate'
            && $request->headers->get('Sec-Fetch-Dest') === 'document';
    }

    /** Static files and downloads keep their address. */
    private function isFile(string $path): bool
    {
        return (bool) preg_match('#\.[a-z0-9]+$#i', $path);
    }

    private function setPath(Request $request, string $path): void
    {
        $uri   = $path . ($request->server->get('QUERY_STRING', '') !== '' ? '?' . $request->server->get('QUERY_STRING') : '');
        $server = $request->server->all();
        $server['REQUEST_URI'] = $uri;
        unset($server['PATH_INFO']);

        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $server,
            $request->getContent(),
        );
    }
}
