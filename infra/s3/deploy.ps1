# ==========================================================================
# Phase 5 - deploy the superlms media stack (private S3 + CloudFront OAC).
#
# Idempotent. Prereq: 'aws configure' done (Phase 3). CloudFront takes a few
# minutes to deploy. Run from this folder:  .\deploy.ps1
# ==========================================================================
param([string]$CdnCertArn = "")
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
$Stack  = "superlms-media"
$Tpl    = Join-Path $PSScriptRoot "s3-cdn.yaml"

Write-Host "==> Validating template" -ForegroundColor Cyan
aws cloudformation validate-template --template-body "file://$($Tpl -replace '\\','/')" --region $Region | Out-Null

$overrides = @()
if ($CdnCertArn) { $overrides += "CdnCertArn=$CdnCertArn"; Write-Host "==> CDN alias cdn.superlms.in enabled (cert provided)" -ForegroundColor Cyan }

Write-Host "==> Deploying stack $Stack (this can take 3-5 min for CloudFront)" -ForegroundColor Cyan
if ($overrides.Count -gt 0) {
  aws cloudformation deploy --stack-name $Stack --template-file $Tpl --region $Region --parameter-overrides $overrides --no-fail-on-empty-changeset
} else {
  aws cloudformation deploy --stack-name $Stack --template-file $Tpl --region $Region --no-fail-on-empty-changeset
}

Write-Host "`n==> Stack outputs:" -ForegroundColor Green
aws cloudformation describe-stacks --stack-name $Stack --region $Region `
  --query "Stacks[0].Outputs[].{Key:OutputKey,Value:OutputValue}" --output table

Write-Host "`n==> For the app env (Phase 8 secrets):" -ForegroundColor Green
$bucket = aws cloudformation describe-stacks --stack-name $Stack --region $Region --query "Stacks[0].Outputs[?OutputKey=='BucketName'].OutputValue" --output text
$cdn    = aws cloudformation describe-stacks --stack-name $Stack --region $Region --query "Stacks[0].Outputs[?OutputKey=='CloudFrontDomain'].OutputValue" --output text
Write-Host "  AWS_BUCKET=$bucket"
Write-Host "  AWS_URL=https://$cdn"
Write-Host "  AWS_DEFAULT_REGION=$Region"
Write-Host "  (leave AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY empty - ECS task role provides creds)"

# Tear down later:  aws cloudformation delete-stack --stack-name superlms-media --region ap-south-1
#   NOTE: bucket has DeletionPolicy=Retain, so it survives stack deletion
#   (empty + delete it manually if you really want it gone).
