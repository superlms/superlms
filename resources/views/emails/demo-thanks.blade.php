{{-- Body of the email sent right after a demo is requested on the website
     (App\Services\DemoMessages::thanks), set in emails.demo-layout: thank you, and
     the private link where they pick the demo's date and time. --}}
<h1 style="margin:0 0 12px;font-size:22px;line-height:30px;font-weight:700;color:#111827;">Thank you, {{ $name }}!</h1>
<p style="margin:0 0 16px;font-size:15px;line-height:24px;color:#374151;">
    Thanks for requesting a demo of {{ $platform }}@if ($demo->school_name) for <strong>{{ $demo->school_name }}</strong>@endif.
    Our team will walk you through admissions, fees, attendance, exams, the parent app and more — in a live, {{ $minutes }}-minute session.
</p>
<p style="margin:0 0 24px;font-size:15px;line-height:24px;color:#374151;">
    Pick a date and time that suits you, and your meeting link is sent to you straight away.
</p>
<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 24px;">
    <tr>
        <td style="border-radius:10px;background:#2563eb;">
            <a href="{{ $bookingUrl }}" style="display:inline-block;padding:13px 26px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:10px;">Choose date &amp; time</a>
        </td>
    </tr>
</table>
<p style="margin:0 0 6px;font-size:13px;line-height:20px;color:#6b7280;">Or open this link:</p>
<p style="margin:0 0 24px;font-size:13px;line-height:20px;word-break:break-all;"><a href="{{ $bookingUrl }}" style="color:#2563eb;">{{ $bookingUrl }}</a></p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f9fafb;border-radius:12px;">
    <tr>
        <td style="padding:14px 16px;font-size:13px;line-height:20px;color:#4b5563;">
            <strong style="color:#111827;">Your request</strong><br>
            {{ $demo->full_name }}@if ($demo->role) · {{ $demo->role }}@endif<br>
            @if ($demo->school_name){{ $demo->school_name }}@if ($demo->city), {{ $demo->city }}@endif<br>@endif
            @if ($demo->no_of_students){{ $demo->no_of_students }} students<br>@endif
            {{ $demo->phone }}
        </td>
    </tr>
</table>
