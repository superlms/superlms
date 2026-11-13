{{-- The frame of the website demo emails (emails.demo-thanks, emails.demo-confirmed):
     the logo, a white card, the footer. Inline styles only — mail clients drop <style>. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $platform }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;">
                    <tr>
                        <td align="center" style="padding-bottom:20px;">
                            <img src="{{ $logoUrl }}" alt="{{ $platform }}" height="40" style="height:40px;max-width:180px;display:block;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;border-radius:16px;border:1px solid #e5e7eb;padding:32px 28px;">
                            {!! $content !!}
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 8px 0;font-size:12px;line-height:18px;color:#9ca3af;">
                            You are getting this because a demo of {{ $platform }} was requested with this email.<br>
                            {{ $platform }}@if ($phone) · {{ $phone }}@endif · <a href="{{ $website }}" style="color:#6b7280;">{{ preg_replace('~^https?://~', '', $website) }}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
