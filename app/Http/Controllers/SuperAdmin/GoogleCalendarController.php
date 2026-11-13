<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\MeetLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Super Admin → Calendar → Queries → Booking settings → Connect Google: the
 * Google account whose Calendar a booked website demo goes on, with its
 * Google Meet link (App\Services\MeetLink). Google's consent screen, then back
 * here with a code that is swapped for a refresh token kept (encrypted) in
 * platform_settings. Only the main super admin reaches these routes.
 */
class GoogleCalendarController extends Controller
{
    public function connect(Request $request)
    {
        if (!MeetLink::googleConfigured()) {
            return redirect()->route('super-admin.calendar', ['tab' => 'queries'])
                ->with('google_calendar', 'Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to the server env first.');
        }

        $state = Str::random(40);
        $request->session()->put('google_calendar_state', $state);

        return redirect()->away(MeetLink::authUrl($state));
    }

    public function callback(Request $request)
    {
        $expected = $request->session()->pull('google_calendar_state');
        $back     = redirect()->route('super-admin.calendar', ['tab' => 'queries']);

        if (!$expected || !hash_equals((string) $expected, (string) $request->query('state'))) {
            return $back->with('google_calendar', 'Google sign-in expired. Please try Connect Google again.');
        }
        if ($request->query('error') || !$request->query('code')) {
            return $back->with('google_calendar', 'Google was not connected (' . ($request->query('error') ?: 'no code') . ').');
        }

        try {
            $email = MeetLink::connect((string) $request->query('code'));
        } catch (\Throwable $e) {
            report($e);

            return $back->with('google_calendar', 'Google was not connected: ' . $e->getMessage());
        }

        return $back->with('google_calendar_ok', 'Google connected' . ($email ? ' as ' . $email : '') . '. Booked demos now get a Google Meet link.');
    }
}
