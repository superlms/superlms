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
        $demo = $this->find($token);
        if (!$demo) {
            return $this->toBookingPage($token);
        }

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

    /**
     * A link that carries a booking token, but not as /web/demo/book/{token}.
     * Meta's dynamic URL button puts {{1}} after the URL typed into the
     * template itself, so a URL typed with its own {{1}}, without its last
     * "/", without "book", or as just the domain opens the WhatsApp button at
     * /web/demo/book/%7B%7B1%7D%7D{token}, /web/demo/book{token},
     * /web/demo/{token} or /{token}. The token is the link's last 40 letters
     * and digits (DemoBooking tokens are Str::random(40)); on to its page.
     */
    public function loose(string $path)
    {
        return $this->toBookingPage($path);
    }

    private function toBookingPage(string $path)
    {
        abort_unless(preg_match('/([A-Za-z0-9]{40})\/*$/', rawurldecode($path), $m), 404);

        $demo = WebsiteDemo::where('booking_token', $m[1])->first();
        abort_unless($demo, 404);

        return redirect()->route('website.demo.book', $demo->booking_token);
    }

    private function find(string $token): ?WebsiteDemo
    {
        if (!preg_match('/^[A-Za-z0-9]{20,64}$/', $token)) {
            return null;
        }

        return WebsiteDemo::where('booking_token', $token)->first();
    }

    private function demo(string $token): WebsiteDemo
    {
        return $this->find($token) ?? abort(404);
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
