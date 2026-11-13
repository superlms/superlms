{{-- Body of the email sent the moment a time is picked on the booking page
     (App\Services\DemoMessages::confirmed), set in emails.demo-layout: when, the
     Meet link, add to a calendar, and the link to change the time. --}}
<h1 style="margin:0 0 12px;font-size:22px;line-height:30px;font-weight:700;color:#111827;">
    {{ $moved ? 'Your demo has a new time' : 'Your demo is booked!' }}
</h1>
<p style="margin:0 0 20px;font-size:15px;line-height:24px;color:#374151;">
    Hi {{ $name }}, {{ $moved ? 'your' : 'thank you — your' }} live demo of {{ $platform }} is on:
</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 24px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;">
    <tr>
        <td style="padding:16px 18px;">
            <p style="margin:0;font-size:17px;line-height:26px;font-weight:700;color:#1e3a8a;">{{ $when }}</p>
            <p style="margin:6px 0 0;font-size:13px;line-height:20px;color:#1e40af;word-break:break-all;">
                Meeting link: <a href="{{ $meetLink }}" style="color:#1d4ed8;">{{ $meetLink }}</a>
            </p>
        </td>
    </tr>
</table>
<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 20px;">
    <tr>
        <td style="border-radius:10px;background:#2563eb;">
            <a href="{{ $meetLink }}" style="display:inline-block;padding:13px 26px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:10px;">Join the meeting</a>
        </td>
        <td style="width:10px;"></td>
        <td style="border-radius:10px;border:1px solid #d1d5db;">
            <a href="{{ $googleCalUrl }}" style="display:inline-block;padding:12px 18px;font-size:14px;font-weight:600;color:#374151;text-decoration:none;border-radius:10px;">Add to Google Calendar</a>
        </td>
    </tr>
</table>
<p style="margin:0 0 20px;font-size:13px;line-height:20px;color:#6b7280;">
    A calendar invite (.ics) is attached for Outlook and Apple Calendar. Join a minute early from a laptop or phone — no software to install.
</p>
<p style="margin:0;font-size:13px;line-height:20px;color:#6b7280;">
    Can't make it? <a href="{{ $bookingUrl }}" style="color:#2563eb;">Pick another time</a>.
</p>
