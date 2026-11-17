{{-- Super Admin → School Data → Send to Mail (App\Jobs\SchoolDataMailSend):
     the school's records as PDFs, attached. Plain, like the PDFs. --}}
<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#111827;font-size:14px;line-height:1.5">
    <div style="max-width:560px">
        <p style="margin:0 0 4px;font-size:18px;font-weight:bold">{{ $school }}</p>
        <p style="margin:0 0 20px;color:#6b7280;font-size:13px">School data from SuperLMS · {{ $date }}@if ($parts > 1) · email {{ $part }} of {{ $parts }}@endif</p>

        <p style="margin:0 0 12px">Your school's records are attached as PDF files, one for each part of the panel:</p>

        <table style="border-collapse:collapse;width:100%;font-size:13px">
            @foreach ($files as $f)
                <tr>
                    <td style="padding:6px 0;border-bottom:1px solid #e5e7eb">{{ $f['name'] }}</td>
                    <td style="padding:6px 0;border-bottom:1px solid #e5e7eb;text-align:right;color:#6b7280">
                        @if (!empty($f['count'])){{ number_format($f['count']) }} {{ $f['count'] === 1 ? 'record' : 'records' }}@endif
                    </td>
                </tr>
            @endforeach
        </table>

        @if (!empty($failed))
            <p style="margin:16px 0 0;color:#b91c1c;font-size:13px">Could not be made this time: {{ implode(', ', $failed) }}. Please ask SuperLMS to send them again.</p>
        @endif
        @if (!empty($empty))
            <p style="margin:16px 0 0;color:#6b7280;font-size:12px">Nothing recorded yet, so not attached: {{ implode(', ', $empty) }}.</p>
        @endif

        <p style="margin:24px 0 0;color:#6b7280;font-size:12px">Sent by SuperLMS. Keep these files safe — they hold your students' and staff's personal details.</p>
    </div>
</body>
</html>
