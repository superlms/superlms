<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .logo { text-align: center; margin-bottom: 24px; }
        .title { text-align: center; font-size: 22px; font-weight: bold; color: #1f2937; margin-bottom: 8px; }
        .subtitle { text-align: center; font-size: 14px; color: #6b7280; margin-bottom: 32px; }
        .otp-box { text-align: center; background: #ecfdf5; border: 2px dashed #10b981; border-radius: 12px; padding: 24px; margin-bottom: 24px; }
        .otp-code { font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #059669; }
        .info { text-align: center; font-size: 13px; color: #9ca3af; margin-bottom: 8px; }
        .footer { text-align: center; font-size: 12px; color: #9ca3af; margin-top: 32px; border-top: 1px solid #e5e7eb; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">Accounts Panel - OTP Verification</div>
        <div class="subtitle">Hello {{ $userName }}, here is your one-time password</div>

        <div class="otp-box">
            <div class="otp-code">{{ $otp }}</div>
        </div>

        <p class="info">This OTP is valid for 2 minutes.</p>
        <p class="info">If you did not request this, please ignore this email.</p>

        <div class="footer">
            SuperLMS - Accounts Panel
        </div>
    </div>
</body>
</html>
