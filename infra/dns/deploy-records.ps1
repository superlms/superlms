# Phase 12 step 4 - alias records (superlms.in + www -> ALB, cdn -> CloudFront).
# Run LAST: after ecs deploy.ps1 -AlbCertArn ... (ALB exports) and
# s3 deploy.ps1 -CdnCertArn ... (CDN alias) have been applied.
$ErrorActionPreference = "Stop"
aws cloudformation deploy --stack-name superlms-dns-records `
  --template-file (Join-Path $PSScriptRoot "records.yaml") `
  --region ap-south-1 --no-fail-on-empty-changeset

Write-Host "`n==> Done. App: https://superlms.in  CDN: https://cdn.superlms.in" -ForegroundColor Green
Write-Host "    DNS can take a few minutes to resolve worldwide."
