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

1. Google Cloud Console (console.cloud.google.com) → create a project (e.g. `SuperLMS`) →
   **APIs & Services → Library** → enable **Google Calendar API**.
2. **Google Auth Platform** (APIs & Services → OAuth consent screen) → **Get started**: app name SuperLMS,
   support email, audience **External** (or **Internal** if the account is Google Workspace), contact email.
   - **Data Access → Add or remove scopes**: `openid`, `.../auth/userinfo.email`, `.../auth/calendar.events`.
   - **Audience → Publish app** (status **In production**). Left in *Testing*, Google expires the refresh
     token after 7 days and Meet links quietly fall back to Jitsi. Unverified is fine: only the owner
     connects, through Google's "app isn't verified" screen (Advanced → Go to SuperLMS).
3. **Clients → Create client** → Web application → Authorized redirect URI:
   `https://superlms.in/calendar/google/callback`. Copy the Client ID and Client secret (or download the
   JSON) at once — the secret is not shown again later.
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

Create both in **English** (`en`, not English (US)), category **Utility**, variable type **Number**. Until
Meta approves them, only the emails go (the Queries → View panel shows the WhatsApp error). Names can be
changed with `WHATSAPP_TEMPLATE_DEMO_THANKS` / `WHATSAPP_TEMPLATE_DEMO_CONFIRMED`.

The send carries only the body variables (in the order below) and the button's `{{1}}`, so: the header and
footer must be plain **Text** with no variable (no image/video header), and the dynamic URL must be the only
(first) button. The wording itself can be changed in Meta freely as long as those stay the same. The demo's
length is not in the text on purpose — it is set in Booking Settings (15–60 min).

**Button URL:** with URL type **Dynamic**, type only `https://superlms.in/web/demo/book/` (ending in `/`) —
Meta puts `{{1}}` after it itself. The site also forwards the usual slips to the right page (a URL typed with its
own `{{1}}`, without the last `/`, without `book`, or just the domain), so a template already approved that way
still works: `DemoBookingController::loose`.

### `demo_request_thanks`

Header (Text): `Your SuperLMS demo request`

Body:
```
Hi {{1}},

Thank you for requesting a live demo of SuperLMS for {{2}}. We have received your request.

Next step: tap "Pick a time" below and choose a date and time that suits you. Your meeting link will be sent to you on WhatsApp and email as soon as you confirm.

In the demo, our team will walk you through SuperLMS and answer your questions about your school's needs.

If you have any questions before then, simply reply to this message.

Team SuperLMS
```
Samples: `{{1}}` Rahul · `{{2}}` Doon Public School

Footer: `This booking link is only for you. Please don't share it.`

Button: **Visit website** → text `Pick a time` → URL type **Dynamic** → Website URL `https://superlms.in/web/demo/book/`
(Meta adds `{{1}}`; sample URL: `https://superlms.in/web/demo/book/abc123`)

### `demo_meeting_confirmed`

Sent when a time is booked, and again when it is changed.

Header (Text): `Your SuperLMS demo is confirmed`

Body:
```
Hi {{1}},

Your SuperLMS live demo is confirmed.

📅 Date & time: {{2}}
🔗 Meeting link: {{3}}

Before the meeting:
• Please join 2–3 minutes early from a laptop or phone with a stable internet connection.
• Keep your questions ready: admissions, fees, attendance, exams, or anything else your school needs.

Need a different time? Tap "Change time" below and pick a new slot. You will get the updated details right away.

See you there!
Team SuperLMS
```
Samples: `{{1}}` Rahul · `{{2}}` Mon, 16 Nov 2026 at 11:00 AM (IST) · `{{3}}` https://meet.google.com/abc-defg-hij

Footer: `Reply to this message if you need any help.`

Button: **Visit website** → text `Change time` → URL type **Dynamic** → Website URL `https://superlms.in/web/demo/book/`
(Meta adds `{{1}}`; sample URL: `https://superlms.in/web/demo/book/abc123`)

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
