# Zoho ZeptoMail Setup

## .env Configuration

Add these variables to your `.env` file:

```env
# Zoho ZeptoMail (Transactional Email)
ZEPTOMAIL_API_TOKEN=your-zeptomail-api-token
ZEPTOMAIL_OTP_TEMPLATE_KEY=your-otp-template-key
ZEPTOMAIL_FROM_EMAIL=noreply@superlms.site
ZEPTOMAIL_FROM_NAME="SuperLMS"
ZEPTOMAIL_BOUNCE_ADDRESS=bounce@superlms.site
```

## ZeptoMail Template Setup

1. Login to [ZeptoMail Dashboard](https://zeptomail.zoho.in/)
2. Go to **Email Templates** > **Create Template**
3. Create the OTP template with these merge fields:
   - `{{panelName}}` - Panel name (Admin Panel / Accounts Panel / School Panel)
   - `{{userName}}` - User's name
   - `{{otp}}` - 6-digit OTP code
4. Copy the **Template Key** from the template details
5. Set it as `ZEPTOMAIL_OTP_TEMPLATE_KEY` in `.env`

## How It Works

- `OtpMailService::sendOtp($user, 'Admin Panel')` generates a 6-digit OTP
- Saves OTP + 2-min expiry to the user record
- Calls ZeptoMail API (`POST https://api.zeptomail.in/v1.1/email/template`) with:
  - `template_key` from config
  - `merge_info` with `otp`, `userName`, `panelName`
  - `to` address from user email
- ZeptoMail renders the template with merge fields and sends it

## Getting API Token

1. Go to ZeptoMail Dashboard > **Settings** > **API Keys**
2. Generate a new Send Mail Token
3. Copy it to `ZEPTOMAIL_API_TOKEN` in `.env`
