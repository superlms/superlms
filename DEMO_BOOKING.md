# Website demo booking

How a "Request Demo" on the website turns into a booked meeting with a meeting link.

1. **The form** (`/web/demo` → `POST /api/website/demo`) saves the request (`website_demos`, status **Pending**)
   and gives it a private booking link: `https://superlms.in/web/demo/book/{token}`. The page moves the
   visitor straight to that link.
2. **Thank-you** — right after the form, by **email** (ZeptoMail, `resources/views/emails/demo-thanks.blade.php`)
   and **WhatsApp** (template `demo_request_thanks`), each with the booking link.
3. **Booking page** — shows the free days and times (Super Admin → Calendar → Queries → **Booking Settings**:
   days, hours, demo length, how far ahead, notice). A time is free when no other demo is booked then and the
   calendar owner has no timed meeting or event then.
4. **Picked a time** → the meeting link is made on the spot, the demo goes on the owner's Calendar as a meeting,
   the status turns **Booked**, and the visitor sees the link on the page and gets it by **email** (with an
   `.ics` invite and "Add to Google Calendar") and **WhatsApp** (template `demo_meeting_confirmed`).
   The same booking link lets them change the time until the demo starts (the Google Meet link stays the same).
5. **Super Admin → Calendar → Queries** lists every request with its Created At and status
   (Pending · Booked · Completed · Cancelled). ⋮ / View: copy or re-send the booking link, mark completed,
   cancel (taken off the calendar), reopen, delete. View shows what was sent and whether it went.

## Meeting links: Google Meet or Jitsi

Without any setup each booked demo gets a **Jitsi Meet** link (`https://meet.jit.si/SuperLMS-Demo-…`).

For **Google Meet** links (the demo is also put on that Google Calendar and Google sends the visitor an invite):

1. Google Cloud Console → a project → **APIs & Services → Library** → enable **Google Calendar API**.
2. **OAuth consent screen** → External, app name SuperLMS, add your Google account as a test user
   (or publish the app), scopes `openid`, `email`, `.../auth/calendar.events`.
3. **Credentials → Create credentials → OAuth client ID** → Web application →
   Authorized redirect URI: `https://superlms.in/calendar/google/callback`.
4. On the server, add to `/opt/superlms/infra/lite/.env` and recreate web/worker/scheduler:
   ```
   GOOGLE_CLIENT_ID=...apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=...
   ```
5. Super Admin (main account) → Calendar → Queries → **Booking Settings → Connect Google** → sign in with the
   Google account whose calendar should hold the demos. Done — the next booking gets a Google Meet link.

(Alternatively set `GOOGLE_CALENDAR_REFRESH_TOKEN` in the env instead of connecting.) If Google ever fails,
the booking still goes through with a Jitsi link.

## WhatsApp templates (Meta → WhatsApp Manager → Message templates)

Create both in **English** (`en`), category **Utility**. Until Meta approves them, only the emails go
(the Queries → View panel shows the WhatsApp error). Names can be changed with
`WHATSAPP_TEMPLATE_DEMO_THANKS` / `WHATSAPP_TEMPLATE_DEMO_CONFIRMED`.

### `demo_request_thanks`

Body:
```
Hi {{1}}, thank you for requesting a SuperLMS demo for {{2}}!

Please pick a date and time that suits you for your 30-minute live demo. Your meeting link is sent to you the moment you confirm.
```
Samples: `{{1}}` Rahul · `{{2}}` Doon Public School

Button: **Visit website** → text `Pick a time` → URL type **Dynamic** → `https://superlms.in/web/demo/book/{{1}}`
(sample: `https://superlms.in/web/demo/book/abc123`)

### `demo_meeting_confirmed`

Body:
```
Hi {{1}}, your SuperLMS demo is booked for {{2}}.

Join the meeting here: {{3}}

Please join a minute early from a laptop or phone. See you there!
```
Samples: `{{1}}` Rahul · `{{2}}` Mon, 16 Nov 2026 at 11:00 AM (IST) · `{{3}}` https://meet.google.com/abc-defg-hij

Button: **Visit website** → text `Change time` → URL type **Dynamic** → `https://superlms.in/web/demo/book/{{1}}`

## Emails

`resources/views/emails/demo-thanks.blade.php` and `demo-confirmed.blade.php` (frame: `demo-layout.blade.php`),
sent as plain HTML through ZeptoMail — no ZeptoMail template needed. Preview them from Booking Settings.

## Code

- `App\Http\Controllers\WebsiteController::demo` — saves, thanks, returns `booking_url`
- `App\Http\Controllers\DemoBookingController` — the booking page (`website.demo-booking` view)
- `App\Support\DemoBooking` — settings, free slots, book / cancel / reopen, calendar entry, template texts
- `App\Services\MeetLink` — Google Meet (Calendar API) or Jitsi
- `App\Services\DemoMessages` — the emails and WhatsApp messages
- `App\Livewire\SuperAdmin\Calendar` — Calendar and Queries tabs
