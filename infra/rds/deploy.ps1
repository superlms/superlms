# ==========================================================================
# Phase 6 - deploy the EdyOne LMS database (RDS MySQL, Single-AZ).
#
# Idempotent. Prereq: VPC stack (Phase 4) exists - this imports its subnets
# and rds security group. RDS creation takes ~6-12 min. Run from this folder:
#   .\deploy.ps1
# ==========================================================================
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
$Stack  = "edyonelms-rds"
$Tpl    = Join-Path $PSScriptRoot "rds.yaml"

Write-Host "==> Validating template" -ForegroundColor Cyan
aws cloudformation validate-template --template-body "file://$($Tpl -replace '\\','/')" --region $Region | Out-Null

Write-Host "==> Deploying stack $Stack (RDS takes ~6-12 min, please wait)" -ForegroundColor Cyan
aws cloudformation deploy `
  --stack-name $Stack `
  --template-file $Tpl `
  --region $Region `
  --no-fail-on-empty-changeset

Write-Host "`n==> Stack outputs (exported for Secrets/ECS phases):" -ForegroundColor Green
aws cloudformation describe-stacks --stack-name $Stack --region $Region `
  --query "Stacks[0].Outputs[].{Key:OutputKey,Value:OutputValue}" --output table

Write-Host "`n==> The DB password lives in Secrets Manager (secret 'edyonelms/rds')." -ForegroundColor Green
Write-Host "    The app reads DB creds from there in Phase 8 - no plaintext password."

# Tear down later (keeps a final snapshot, see DeletionPolicy):
#   aws cloudformation delete-stack --stack-name edyonelms-rds --region ap-south-1
