# ==========================================================================
# Phase 7 - deploy the superlms cache (ElastiCache Redis, single node).
#
# Idempotent. Prereq: VPC stack (Phase 4) exists - imports its subnets and
# redis security group. Takes ~5-8 min. Run from this folder:  .\deploy.ps1
# ==========================================================================
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
$Stack  = "superlms-redis"
$Tpl    = Join-Path $PSScriptRoot "redis.yaml"

Write-Host "==> Validating template" -ForegroundColor Cyan
aws cloudformation validate-template --template-body "file://$($Tpl -replace '\\','/')" --region $Region | Out-Null

Write-Host "==> Deploying stack $Stack (~5-8 min)" -ForegroundColor Cyan
aws cloudformation deploy `
  --stack-name $Stack `
  --template-file $Tpl `
  --region $Region `
  --no-fail-on-empty-changeset

Write-Host "`n==> Stack outputs (exported for Secrets/ECS phases):" -ForegroundColor Green
aws cloudformation describe-stacks --stack-name $Stack --region $Region `
  --query "Stacks[0].Outputs[].{Key:OutputKey,Value:OutputValue}" --output table

# Tear down later:  aws cloudformation delete-stack --stack-name superlms-redis --region ap-south-1
