<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A single in-app activity notification for school admins.
 *
 * Stored on the `database` channel only. The payload is built upstream by
 * {@see \App\Support\ActivityNotifier} so the admin notification screen and
 * the navbar bell read a stable shape: type / title / body (+ metadata).
 */
class ActivityNotification extends Notification
{
    public function __construct(public array $payload)
    {
    }

    /** @return array<int,string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
