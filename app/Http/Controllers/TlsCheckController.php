<?php

namespace App\Http\Controllers;

use App\Models\SchoolWebsite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

/**
 * Caddy asks here before it fetches a certificate for a host it has not seen
 * — a school's own domain (infra/lite/Caddyfile, on_demand_tls):
 *
 *   GET /internal/tls-check?domain=edyonelms.in
 *
 * 200 only for the domain of a published school website (www. or not), so no
 * one can make the box get certificates for any name they point at it.
 */
class TlsCheckController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $domain = strtolower(trim((string) $request->query('domain', '')));
        if ($domain === '' || strlen($domain) > 253 || ! preg_match('/^[a-z0-9.-]+$/', $domain)) {
            return response('', 404);
        }

        try {
            $hosted = Schema::hasTable('school_websites') && SchoolWebsite::forHost($domain) !== null;
        } catch (\Throwable $e) {
            $hosted = false;
        }

        return response('', $hosted ? 200 : 404);
    }
}
