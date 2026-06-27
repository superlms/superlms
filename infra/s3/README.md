# Phase 5 - S3 (private) + CloudFront (OAC)

One CloudFormation stack (`superlms-media`): a private S3 bucket fronted by
CloudFront using Origin Access Control. The bucket is never public; only the
CloudFront distribution can read objects.

## Run

```powershell
cd infra/s3
.\deploy.ps1
```

CloudFront takes ~3-5 min. The script prints the `AWS_BUCKET` and `AWS_URL`
values you'll feed into the app env in Phase 8.

## What gets created

| Resource | Notes |
|----------|-------|
| S3 bucket `superlms-media-<account>` | all public access blocked, SSE-S3, `BucketOwnerEnforced` (no ACLs), CORS for browser GET/PUT |
| CloudFront Origin Access Control | SigV4-signs CloudFront's requests to S3 |
| CloudFront distribution | HTTPS redirect, `PriceClass_200` (incl. India), managed CachingOptimized policy, default `*.cloudfront.net` cert |
| Bucket policy | allows `s3:GetObject` only from this distribution (`AWS:SourceArn` condition) |

## App wiring (set in Phase 8 secrets / env)

```
FILESYSTEM_DISK=s3
AWS_BUCKET=superlms-media-<account>
AWS_URL=https://<dxxxx>.cloudfront.net
AWS_DEFAULT_REGION=ap-south-1
AWS_ACCESS_KEY_ID=        # empty on ECS - task role provides creds
AWS_SECRET_ACCESS_KEY=    # empty on ECS - task role provides creds
```

- Writes (uploads) go straight to S3 via the **`superlms-task-role`** (Phase 3),
  which already grants `s3:*Object` + `ListBucket` on `superlms-*`.
- Reads (serving) go through **CloudFront** because `AWS_URL` points at the CDN.

## Notes

- Bucket is `DeletionPolicy: Retain` - deleting the stack keeps your files.
- Custom domain (e.g. `cdn.superlms...`) + ACM cert (us-east-1) are added in
  Phase 12; until then the default CloudFront domain works.
- Objects served via CloudFront are publicly fetchable by URL. If some
  uploads (exam copies etc.) must stay access-controlled, serve those with
  signed URLs / a private behavior - revisit when wiring the app (Phase 14/15).
