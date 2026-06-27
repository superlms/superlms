# Phase 12 step 1 - Route53 hosted zone for superlms.in.
# After this, set the printed nameservers at your registrar.
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
aws cloudformation deploy --stack-name superlms-dns `
  --template-file (Join-Path $PSScriptRoot "hosted-zone.yaml") `
  --region $Region --no-fail-on-empty-changeset

Write-Host "`n==> Set THESE nameservers at your registrar (superlms.in):" -ForegroundColor Green
aws cloudformation describe-stacks --stack-name superlms-dns --region $Region `
  --query "Stacks[0].Outputs[?OutputKey=='NameServers'].OutputValue" --output text
Write-Host "`n    Wait for propagation (minutes-hours) BEFORE deploying the certs." -ForegroundColor Yellow
