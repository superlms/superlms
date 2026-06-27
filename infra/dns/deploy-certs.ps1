# Phase 12 step 2 - ACM certs. Run AFTER the registrar nameservers point at
# Route53 (else validation hangs). Creates:
#   - ALB cert (ap-south-1): superlms.in + www.superlms.in
#   - CDN cert (us-east-1):  cdn.superlms.in
# Both DNS-validated automatically via the hosted zone. Each stack waits until
# the cert is ISSUED, which can take a few minutes after DNS resolves.
$ErrorActionPreference = "Stop"

$ZoneId = (aws cloudformation describe-stacks --stack-name superlms-dns --region ap-south-1 `
  --query "Stacks[0].Outputs[?OutputKey=='ZoneId'].OutputValue" --output text).Trim()
Write-Host "==> Hosted zone: $ZoneId" -ForegroundColor Cyan

Write-Host "==> Deploying ALB cert (ap-south-1) - may take a few min for validation" -ForegroundColor Cyan
aws cloudformation deploy --stack-name superlms-alb-cert `
  --template-file (Join-Path $PSScriptRoot "alb-cert.yaml") `
  --region ap-south-1 --no-fail-on-empty-changeset

Write-Host "==> Deploying CDN cert (us-east-1) - required region for CloudFront" -ForegroundColor Cyan
aws cloudformation deploy --stack-name superlms-cdn-cert `
  --template-file (Join-Path $PSScriptRoot "cdn-cert.yaml") `
  --region us-east-1 --parameter-overrides ZoneId=$ZoneId --no-fail-on-empty-changeset

$albArn = (aws cloudformation describe-stacks --stack-name superlms-alb-cert --region ap-south-1 --query "Stacks[0].Outputs[?OutputKey=='CertArn'].OutputValue" --output text).Trim()
$cdnArn = (aws cloudformation describe-stacks --stack-name superlms-cdn-cert --region us-east-1 --query "Stacks[0].Outputs[?OutputKey=='CertArn'].OutputValue" --output text).Trim()

Write-Host "`n==> Certs issued. Use these next:" -ForegroundColor Green
Write-Host "    ALB cert (ap-south-1): $albArn"
Write-Host "    CDN cert (us-east-1):  $cdnArn"
Write-Host "`n    Next:" -ForegroundColor Yellow
Write-Host "      cd ..\ecs;  .\deploy.ps1 -AlbCertArn $albArn"
Write-Host "      cd ..\s3;   .\deploy.ps1 -CdnCertArn $cdnArn"
Write-Host "      cd ..\dns;  .\deploy-records.ps1"
