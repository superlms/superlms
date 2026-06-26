<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ID Card — {{ $c['name'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins','Segoe UI',Arial,sans-serif; background: #ece9f5; padding: 28px 16px; }
        .toolbar { text-align: center; margin-bottom: 22px; }
        .toolbar button { background: #7c3aed; color: #fff; border: 0; padding: 9px 20px; border-radius: 8px;
            font-size: 13px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 10px rgba(124,58,237,.3); }
        @media print { body { background: #fff; padding: 0; } .toolbar { display: none; }
            .idc-card { box-shadow: none !important; border: 1px solid #eee; } }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">🖨 Print / Save as PDF</button></div>
    @include('admin.id-cards._card', ['c' => $c])
</body>
</html>
