<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .title { text-align: center; font-size: 22px; font-weight: bold; color: #1f2937; margin-bottom: 8px; }
        .subtitle { text-align: center; font-size: 14px; color: #6b7280; margin-bottom: 32px; }
        .box { background: #f5f3ff; border: 2px dashed #7c3aed; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; }
        .row { font-size: 14px; color: #374151; margin: 6px 0; }
        .row strong { color: #1f2937; }
        .button { display: block; width: fit-content; margin: 0 auto 24px; background: #7c3aed; color: #ffffff !important; text-decoration: none; font-size: 14px; font-weight: bold; border-radius: 10px; padding: 12px 28px; }
        .info { text-align: center; font-size: 13px; color: #9ca3af; margin-bottom: 8px; }
        .info a { color: #7c3aed; }
        .footer { text-align: center; font-size: 12px; color: #9ca3af; margin-top: 32px; border-top: 1px solid #e5e7eb; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">Welcome to SuperLMS</div>
        <div class="subtitle">Hello {{ $userName }}, you have been added as a Super Admin user</div>

        <div class="box">
            <p class="row"><strong>Email:</strong> {{ $email }}</p>
            <p class="row"><strong>Password:</strong> {{ $password }}</p>
        </div>

        <a class="button" href="{{ $loginUrl }}">Login to Super Admin</a>

        <p class="info">You can change this password any time with <a href="{{ $forgotUrl }}">Forgot password</a> on the login page.</p>
        <p class="info">If you were not expecting this, please ignore this email.</p>

        <div class="footer">
            SuperLMS - Super Admin
        </div>
    </div>
</body>
</html>
