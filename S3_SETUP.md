# S3 File Storage Setup

The application code is already wired to upload files to S3 (see [`app/Helpers/FileUploadHelper.php`](app/Helpers/FileUploadHelper.php) and ~210 `Storage::disk('s3')` calls across the Livewire / Controller layer). This document covers the **infrastructure and configuration** steps needed to activate it.

> **Estimated time:** 20–30 minutes in the AWS Console + 5 minutes on the server.

---

## 1. Create the S3 bucket

1. AWS Console → **S3 → Create bucket**.
2. **Bucket name:** `superlms-prod` (or your own — must be globally unique).
3. **Region:** match your EC2 region. To find it, run on EC2:
   ```bash
   curl -s http://169.254.169.254/latest/meta-data/placement/region; echo
   ```
4. **Object Ownership:** *ACLs disabled* (default — keep it).
5. **Block Public Access settings for this bucket:** **uncheck** "Block all public access".
   - You must check the acknowledgement box that confirms public objects will be possible.
   - This is required because the app calls `setVisibility($path, 'public')` so files are served via direct URL.
6. **Bucket Versioning:** *Enable* (recommended — protects against accidental deletes).
7. **Default encryption:** *SSE-S3* (default — keep it).
8. **Create bucket.**

---

## 2. Attach a bucket policy (allow public reads on objects)

Open the bucket → **Permissions → Bucket policy → Edit** → paste the following (replace `superlms-prod` with your bucket name if different):

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "AllowPublicReadOfObjects",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::superlms-prod/*"
    }
  ]
}
```

Save changes.

---

## 3. Set CORS

Same bucket → **Permissions → Cross-origin resource sharing (CORS) → Edit** → paste:

```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedOrigins": [
      "https://superlms.in",
      "http://localhost:8080",
      "http://localhost:8000"
    ],
    "ExposeHeaders": []
  }
]
```

Save. Add any additional domains you serve from (staging, custom CloudFront, etc.).

---

## 4. Create a dedicated IAM user for the app

**Do not use your AWS root account credentials in the app.**

1. AWS Console → **IAM → Users → Create user**.
2. **User name:** `superlms-s3-app`.
3. **Access type:** check **"Programmatic access"** (we want an Access Key + Secret).
4. **Permissions → Attach policies directly → Create policy** (in a new tab):

   - **JSON tab**, paste this (substitute your bucket name):

     ```json
     {
       "Version": "2012-10-17",
       "Statement": [
         {
           "Sid": "BucketLevel",
           "Effect": "Allow",
           "Action": ["s3:ListBucket", "s3:GetBucketLocation"],
           "Resource": "arn:aws:s3:::superlms-prod"
         },
         {
           "Sid": "ObjectLevel",
           "Effect": "Allow",
           "Action": [
             "s3:PutObject",
             "s3:PutObjectAcl",
             "s3:GetObject",
             "s3:DeleteObject"
           ],
           "Resource": "arn:aws:s3:::superlms-prod/*"
         }
       ]
     }
     ```

   - **Policy name:** `SuperLMSS3Access`. Create policy.

5. Back on the user creation tab → refresh the policy list → attach `SuperLMSS3Access`.
6. Create the user. Once created, click into the user → **Security credentials → Create access key**:
   - Use case: **Application running outside AWS** (or "Other" — doesn't matter for billing).
   - **Copy the Access key ID and Secret access key NOW.** The Secret is shown only once. Store it in your password manager.

---

## 5. Update `.env` on the server

SSH into EC2 and edit `.env` in the project directory:

```bash
cd ~/superlms       # adjust path if different
nano .env
```

Set / add these lines:

```env
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=AKIA...your-key...
AWS_SECRET_ACCESS_KEY=...your-secret...
AWS_DEFAULT_REGION=ap-south-1
AWS_BUCKET=superlms-prod
AWS_URL=https://superlms-prod.s3.ap-south-1.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

**Notes:**
- Use the actual region your bucket lives in (replace `ap-south-1`).
- `AWS_URL` must match the region: format is `https://<BUCKET>.s3.<REGION>.amazonaws.com`. Without this, `Storage::disk('s3')->url($path)` may build broken URLs.
- Do **not** commit `.env` to git — the file is gitignored already.

Save and exit.

---

## 6. Reload the app container

```bash
docker compose up -d --force-recreate app
```

(No image rebuild needed — `.env` is read at container start.)

Clear / re-cache config so the new values take effect:

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan config:cache
```

---

## 7. Verify

Run the built-in health check (added with this setup):

```bash
docker compose exec app php artisan s3:health
```

You should see something like:

```
✓ Configuration looks valid (bucket: superlms-prod, region: ap-south-1)
✓ PUT  succeeded → s3-health-check/<timestamp>.txt
✓ EXISTS check passed
✓ GET  succeeded → content matched
✓ URL  generated  → https://superlms-prod.s3.ap-south-1.amazonaws.com/s3-health-check/<timestamp>.txt
✓ HEAD via HTTP   → 200 OK
✓ DELETE succeeded

S3 disk is healthy and ready.
```

If any step fails, the command prints which one and why. Common causes:

| Failure | Likely cause | Fix |
|---|---|---|
| `Access Denied` on PUT | IAM policy missing `s3:PutObject` | Recheck step 4 |
| `Access Denied` on PUT-ACL | Bucket has ACLs disabled but code calls `setVisibility('public')` | The bucket policy in step 2 already grants public read — but **the app code that calls `setVisibility` will throw**. Fix: enable ACLs on the bucket (Permissions → Object Ownership → "ACLs enabled" → "Bucket owner preferred"), OR remove the `setVisibility` calls (the bucket policy already makes everything public). |
| `403` on HEAD via HTTP | Bucket policy missing or region wrong in URL | Recheck step 2 and `AWS_URL` |
| `NoSuchBucket` | Bucket name typo in `.env` | Match exactly |
| `InvalidAccessKeyId` / `SignatureDoesNotMatch` | Wrong key/secret | Re-paste from password manager |

### Manual UI verification

Once `s3:health` passes:

1. Log into the admin panel and edit a teacher profile — upload a new photo.
2. The new image URL stored in the database should be `https://superlms-prod.s3.<region>.amazonaws.com/teacher-images/<random>.jpg`.
3. Open that URL in the browser — it should load.
4. Check the S3 bucket in the console — the file should appear under `teacher-images/`.

---

## 8. (Optional) Migrate existing local files to S3

Skip this section if the app is fresh / no real files have been uploaded yet.

If there are files in `storage/app/public/` (or anywhere on the EC2 host) that were uploaded **before** S3 was wired up, they live on the EC2 disk only — they're not in the bucket. To move them:

1. **Take a database snapshot first** (the migration will rewrite columns).
2. **List affected files:**
   ```bash
   docker compose exec app find storage/app/public -type f
   ```
3. We'll write a one-off script that:
   - Uploads each file to S3 under the same relative path.
   - Updates the DB columns that reference these files (teacher images, school logos, etc.) to the new S3 URLs.
   - Verifies before deleting the local copies.

This step is not automated yet because it needs a database snapshot and per-column mapping. Tell me when you're ready and I'll write the script.

---

## 9. (Optional) Hardening for production

- **CloudFront** in front of S3 for faster delivery + HTTPS without per-object ACLs.
- **Rotate the IAM access key every 90 days** (AWS Console → IAM → Users → Security credentials → Make active/inactive).
- **Enable bucket logging** to track access.
- **Lifecycle rules** — auto-delete old test files, transition cold data to S3 Glacier.
- **MFA on the AWS root account** if not already enabled.

These are all good practices but none are blocking the integration. Tackle them when you have a maintenance window.
