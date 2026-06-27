<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $label }}</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #f4f6fb; color: #1a1a2e;
        }
        .card {
            background: #fff; border-radius: 18px; padding: 36px 28px; max-width: 360px; width: 88%;
            box-shadow: 0 12px 40px rgba(0,0,0,.08); text-align: center;
        }
        .icon { width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 18px; display: flex;
            align-items: center; justify-content: center; font-size: 38px; color: #fff; }
        .ok   { background: #16a34a; }
        .fail { background: #dc2626; }
        .pend { background: #d97706; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p  { color: #6b7280; font-size: 14px; line-height: 1.5; margin: 0 0 22px; }
        a.btn { display: inline-block; background: #5a31f4; color: #fff; text-decoration: none;
            padding: 12px 22px; border-radius: 12px; font-weight: 600; font-size: 15px; }
    </style>
</head>
<body>
    <div class="card">
        @if ($state === 'COMPLETED')
            <div class="icon ok">&check;</div>
        @elseif ($state === 'FAILED')
            <div class="icon fail">&times;</div>
        @else
            <div class="icon pend">&hellip;</div>
        @endif

        <h1>{{ $label }}</h1>
        <p>You can now return to the SUPERLMS app. Your fee status will update automatically.</p>
        <a class="btn" href="{{ $deepLink }}">Return to app</a>
    </div>

    <script>
        // Best-effort: try to bounce straight back into the app.
        setTimeout(function () { window.location.href = @json($deepLink); }, 800);
    </script>
</body>
</html>
