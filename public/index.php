<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Delete stale bootstrap cache files that reference missing classes (e.g. dev packages removed via --no-dev).
// Laravel will auto-regenerate them cleanly on the next request.
(static function () {
    foreach (['packages', 'services'] as $cache) {
        $path = __DIR__ . '/../bootstrap/cache/' . $cache . '.php';
        if (!file_exists($path)) {
            continue;
        }
        $data = @include $path;
        if (!is_array($data)) {
            @unlink($path);
            continue;
        }
        $missing = false;
        array_walk_recursive($data, static function ($value) use (&$missing) {
            if ($missing || !is_string($value) || !str_contains($value, '\\')) {
                return;
            }
            if (!class_exists($value) && !interface_exists($value)) {
                $missing = true;
            }
        });
        if ($missing) {
            @unlink($path);
        }
    }
})();

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
