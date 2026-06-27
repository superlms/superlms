# Phase 8 - Secrets Manager + env injection

Two secrets back the app on ECS:

| Secret | Created by | Holds |
|--------|-----------|-------|
| `superlms/rds` | Phase 6 (RDS stack) | DB `username`, `password` (+ host/port) |
| `superlms/app` | this phase (`setup-secrets.ps1`) | APP_KEY, ZeptoMail token + template keys, PhonePe keys |

Non-sensitive values are passed as plain `environment` in the task definition;
sensitive ones are pulled from these secrets via the task def `secrets` block
(Phase 10). The app never ships a `.env` on ECS.

## Run

```powershell
cd infra/secrets
copy app-secrets.example.json app-secrets.json   # gitignored
# edit app-secrets.json -> paste REAL values from the old EC2 .env
.\setup-secrets.ps1
```

> **APP_KEY:** if the old app has encrypted data, paste the **same** APP_KEY
> from the old `.env`. Leaving it empty generates a NEW key (fine for a fresh
> start, but would make old encrypted values undecryptable).

## Task definition env map (for Phase 10)

### Plain `environment` (non-secret)
```
APP_NAME=SuperLMS
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Kolkata
APP_URL=https://<domain-or-ALB-DNS>     # final domain in Phase 12
APP_LOCALE=en
LOG_CHANNEL=stderr                      # -> CloudWatch
LOG_LEVEL=info
DB_CONNECTION=mysql
DB_HOST=superlms-mysql.cb0gug82k78s.ap-south-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=superlms
SESSION_DRIVER=redis
SESSION_LIFETIME=120
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=superlms-redis.uktcyj.0001.aps1.cache.amazonaws.com
REDIS_PORT=6379
REDIS_PASSWORD=null
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=ap-south-1
AWS_BUCKET=superlms-media-540361297670
AWS_URL=https://dvtmf2beaqewz.cloudfront.net
AWS_USE_PATH_STYLE_ENDPOINT=false
ZEPTOMAIL_API_URL=https://api.zeptomail.in/v1.1
ZEPTOMAIL_FROM_EMAIL=noreply@superlms.in      # update to the real brand email
ZEPTOMAIL_FROM_NAME=SuperLMS
PHONEPE_CLIENT_VERSION=1
PHONEPE_ENV=production
RUN_MIGRATIONS=false                    # migrations run as a one-off task (Phase 11)
```

### `secrets` (valueFrom Secrets Manager)
```
DB_USERNAME            <- superlms/rds:username
DB_PASSWORD            <- superlms/rds:password
APP_KEY                <- superlms/app:APP_KEY
ZEPTOMAIL_API_TOKEN    <- superlms/app:ZEPTOMAIL_API_TOKEN
ZEPTOMAIL_OTP_TEMPLATE_KEY                <- superlms/app:...
ZEPTOMAIL_STUDENT_PASSWORD_TEMPLATE_KEY   <- superlms/app:...
ZEPTOMAIL_TEACHER_PASSWORD_TEMPLATE_KEY   <- superlms/app:...
ZEPTOMAIL_WELCOME_TEMPLATE_KEY            <- superlms/app:...
ZEPTOMAIL_FEE_RECEIPT_TEMPLATE_KEY        <- superlms/app:...
ZEPTOMAIL_ANNOUNCEMENT_TEMPLATE_KEY       <- superlms/app:...
ZEPTOMAIL_PASSWORD_CHANGED_TEMPLATE_KEY   <- superlms/app:...
ZEPTOMAIL_SCHOOL_CREATION_TEMPLATE_KEY    <- superlms/app:...
PHONEPE_CLIENT_ID         <- superlms/app:PHONEPE_CLIENT_ID
PHONEPE_CLIENT_SECRET     <- superlms/app:PHONEPE_CLIENT_SECRET
PHONEPE_WEBHOOK_USERNAME  <- superlms/app:PHONEPE_WEBHOOK_USERNAME
PHONEPE_WEBHOOK_PASSWORD  <- superlms/app:PHONEPE_WEBHOOK_PASSWORD
```

The `ecsTaskExecutionRole` already has `secretsmanager:GetSecretValue` on
`superlms/*`, so ECS can inject all of these.

## TODO - Firebase (push notifications)

`config/app.php` + `config/services.php` load Firebase creds from a FILE
(`public_path('superlms-lms-firebase-adminsdk-...json')`). That file is
gitignored and not in the image, and the real Google file is named after the
Firebase project (likely `edyone-lms-...`). To make push work on ECS (Phase 10):
1. store the service-account JSON as secret `superlms/firebase`,
2. make the config path env-driven (`env('FIREBASE_CREDENTIALS', ...)`),
3. have the container entrypoint write the secret to that path on boot.
Tracked as a Phase 10 task.
