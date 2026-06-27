# Phase 12 step 1b - replicate Hostinger + ZeptoMail email records into Route53.
# Run right AFTER deploy-zone.ps1 and BEFORE switching nameservers, so email
# (MX/SPF/DKIM/DMARC + ZeptoMail bounce) keeps working through the cutover.
$ErrorActionPreference = "Stop"
aws cloudformation deploy --stack-name superlms-email-records `
  --template-file (Join-Path $PSScriptRoot "email-records.yaml") `
  --region ap-south-1 --no-fail-on-empty-changeset

Write-Host "`n==> Email records added to Route53." -ForegroundColor Green
Write-Host "    Now you can switch nameservers at Hostinger without breaking mail."
