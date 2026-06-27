# Phase 12 - Route53 + HTTPS (ACM)

App on **superlms.in** (root, path-based: /admin, /login, /accounts), CDN on
**cdn.superlms.in**. Domain is at an external registrar, so we create a Route53
hosted zone and you delegate to it.

## Order (do not skip the wait)

```powershell
cd infra/dns

# 1. hosted zone -> prints 4 nameservers
.\deploy-zone.ps1
```
2. At your **registrar**, replace superlms.in's nameservers with those 4.
   Wait until they propagate (check: `nslookup -type=NS superlms.in`).

```powershell
# 3. certs (ALB ap-south-1 + CDN us-east-1), DNS-validated via the zone
.\deploy-certs.ps1          # prints the two cert ARNs + the next commands

# 4. attach certs (use the ARNs printed above)
cd ..\ecs;  .\deploy.ps1 -AlbCertArn <alb-cert-arn>   # adds 443 + redirects 80->443, APP_URL=https://superlms.in
cd ..\s3;   .\deploy.ps1 -CdnCertArn <cdn-cert-arn>   # adds cdn.superlms.in alias to CloudFront

# 5. DNS records: superlms.in + www -> ALB, cdn -> CloudFront
cd ..\dns;  .\deploy-records.ps1
```

Then: **https://superlms.in** (HTTP auto-redirects to HTTPS) and
**https://cdn.superlms.in**.

## Stacks

| Stack | Region | What |
|-------|--------|------|
| `superlms-dns` | ap-south-1 | hosted zone superlms.in |
| `superlms-alb-cert` | ap-south-1 | cert superlms.in + www |
| `superlms-cdn-cert` | us-east-1 | cert cdn.superlms.in (CloudFront needs us-east-1) |
| `superlms-dns-records` | ap-south-1 | alias records |

## Email (ZeptoMail) - so noreply@superlms.in actually sends

Add the records ZeptoMail/Zoho gives you for **superlms.in** into the Route53
hosted zone (Console -> Route53 -> superlms.in):
- SPF (TXT): include zoho/zeptomail per their dashboard
- DKIM (TXT/CNAME): the selector record they provide
- (optional) DMARC TXT `_dmarc.superlms.in`
Then verify the domain in ZeptoMail. Until verified, OTP/password mails fail.

## Notes

- ALB/CDN cert params are optional - everything ran on HTTP before this phase;
  these commands just upgrade in place.
- Certs auto-renew (ACM) as long as the Route53 records stay.
