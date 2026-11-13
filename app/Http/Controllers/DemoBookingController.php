<?php

namespace App\Http\Controllers;

use App\Models\WebsiteDemo;
use App\Services\DemoMessages;
use App\Support\DemoBooking;
use App\Support\Platform;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * The page a website demo request's thank-you links to (/web/demo/book/{token}):
 * the free dates and times (App\Support\DemoBooking), and once one is picked
 * the meeting with its Meet link, made on the spot and sent by email and
 * WhatsApp. The link works again to change the time until the demo starts.
 */
class DemoBookingController extends Controller
{
    public function show(string $token)
    {
        $demo = $this->demo($token);

        return view('website.demo-booking', [
            'demo'     => $demo,
            'slots'    => $this->canBook($demo) ? DemoBooking::slots($demo) : [],
            'canBook'  => $this->canBook($demo),
            'settings' => DemoBooking::settings(),
            'platform' => Platform::details()['platform_name'] ?: 'SuperLMS',
            'logoUrl'  => Platform::logoUrl(),
            'justBooked' => session('demo_booked'),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $demo = $this->demo($token);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
        ]);

        if (!$this->canBook($demo)) {
            return redirect()->route('website.demo.book', $token);
        }

        $result = DemoBooking::book($demo, $data['date'], $data['time']);
        if (!$result['ok']) {
            return redirect()->route('website.demo.book', $token)
                ->withErrors(['time' => 'Sorry, that time was just taken. Please pick another.'])
                ->withInput();
        }

        $demo->refresh();
        $moved = $result['moved'];
        dispatch(function () use ($demo, $moved) {
            DemoMessages::confirmed($demo->fresh(), $moved);
        })->afterResponse();

        return redirect()->route('website.demo.book', $token)->with('demo_booked', $moved ? 'moved' : 'booked');
    }

    private function demo(string $token): WebsiteDemo
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{20,64}$/', $token), 404);

        return WebsiteDemo::where('booking_token', $token)->firstOrFail();
    }

    /** A time can be picked while nothing is booked, or the booked demo is still ahead. */
    private function canBook(WebsiteDemo $demo): bool
    {
        return match ($demo->statusKey()) {
            'pending' => true,
            'booked'  => $demo->meeting_at === null || $demo->meeting_at->gt(Carbon::now()),
            default   => false,
        };
    }
}
