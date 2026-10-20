<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The notifications that came in while the school admin or the super-admin is
 * working on the panel — shown there as a toast with a voice
 * (partials.live-notify). They are the bell's own notifications, left unread:
 * the bell lists them as it always has.
 *
 * GET …/notification/live            → { now }            (start counting from now)
 * GET …/notification/live?since=…    → { now, items[] }   (what came in since)
 *
 * Only ever the last few minutes: a page coming back after a while starts
 * from now, so what came in while nobody was working stays in the bell alone.
 * What the person did themselves (the actor of an activity notification) is
 * left out — the page has already told them.
 */
class LiveNotificationController extends Controller
{
    public function __invoke(Request $request)
    {
        $user  = $request->user();
        $now   = now();
        $items = [];

        $since = $request->query('since');
        if ($user && is_string($since) && $since !== '') {
            try {
                $from = Carbon::parse($since);
            } catch (\Throwable $e) {
                $from = null;
            }

            if ($from && $from->gte($now->copy()->subMinutes(10))) {
                try {
                    $items = $user->unreadNotifications()
                        ->where('created_at', '>=', $from)
                        ->latest()
                        ->limit(10)
                        ->get()
                        ->reject(fn ($n) => (int) ($n->data['actor_id'] ?? 0) === (int) $user->id)
                        ->map(fn ($n) => [
                            'id'    => (string) $n->id,
                            'type'  => (string) ($n->data['type'] ?? ''),
                            'title' => Str::limit((string) ($n->data['title'] ?? 'New notification'), 120),
                            'body'  => Str::limit((string) ($n->data['body'] ?? ''), 200),
                        ])
                        ->values()
                        ->all();
                } catch (\Throwable $e) {
                    $items = []; // no notifications table yet — nothing to show
                }
            }
        }

        return response()
            ->json(['now' => $now->toDateTimeString(), 'items' => $items])
            ->header('Cache-Control', 'no-store');
    }
}
