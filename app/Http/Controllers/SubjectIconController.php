<?php

namespace App\Http\Controllers;

use App\Support\SubjectIcons;
use Illuminate\Http\Response;

/**
 * Serves a subject's built-in icon as a standalone SVG, so the mobile apps can
 * render the same artwork the web panel draws inline. Public and immutable —
 * the icons ship with the code and never change per school.
 */
class SubjectIconController extends Controller
{
    public function show(string $key): Response
    {
        // URLs carry hyphens ("social-science"); the icon keys carry spaces.
        $key = str_replace('-', ' ', $key);

        return response(SubjectIcons::svgDocument($key), 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
