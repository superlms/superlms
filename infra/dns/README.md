# Phase 12 - Route53 + HTTPS (ACM)

App on **superlms.in** (root, path-based: /admin, /login, /accounts), CDN on
**cdn.superlms.in**. Domain is at an external registrar, so we create a Route53
hosted zone and you delegate to it.

## Order (prereq: app already live on ECS/ALB; do not skip the wait)

```powershell
cd infra/dns

# 1. hosted zone -> prints 4 nameservers
.\deploy-zone.ps1

# 1b. replicate existing Hostinger + ZeptoMail email records into Route53
#     (MX/SPF/DKIM/DMARC + bounce-zem) so mail survives the switch
.\deploy-email.ps1

# 1c. web records: superlms.in + www -> ALB, cdn -> CloudFront
.\deploy-records.ps1
```
2. At **Hostinger** (Domains -> superlms.in -> DNS/Nameservers -> Change
   nameservers -> Custom), set the 4 Route53 nameservers from step 1. Wait for
   propagation: `nslookup -type=NS superlms.in` should show `awsdns`.
   Email + web keep working because the records are already in Route53.

```powershell
# 3. certs (ALB ap-south-1 + CDN us-east-1), auto DNS-validated
.\deploy-certs.ps1          # prints the two cert ARNs + the next commands

# 4. attach certs (use the ARNs printed above)
cd ..\ecs;  .\deploy.ps1 -AlbCertArn <alb-cert-arn>   # adds 443 + 80->443 redirect, APP_URL=https://superlms.in
cd ..\s3;   .\deploy.ps1 -CdnCertArn <cdn-cert-arn>   # adds cdn.superlms.in alias
```

Then: **https://superlms.in** (HTTP auto-redirects to HTTPS).

### Email records copied (from Hostinger, Jun 2026)
MX mx1/mx2.hostinger.com; SPF `v=spf1 include:_spf.mail.hostinger.com ~all`;
DMARC `p=none`; DKIM hostingermail-a/b/c._domainkey; autodiscover; autoconfig;
ZeptoMail bounce-zem -> cluster89.zeptomail.in. The old web `A @ 2.57.91.91`
and `www CNAME` are intentionally dropped (web moves to the ALB).
> If ZeptoMail later flags SPF, merge the include into the one TXT:
> `v=spf1 include:_spf.mail.hostinger.com include:zeptomail.in ~all`
> (only ONE v=spf1 record is allowed). Check the ZeptoMail dashboard shows the
> domain still verified after the switch; add any DKIM record it asks for.

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
