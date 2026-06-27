# ==========================================================================
# Phase 4 - deploy the superlms network stack (VPC, subnets, SGs).
#
# Idempotent: re-running updates the stack in place. Prereq: 'aws configure'
# done with the admin user (Phase 3). Run from this folder:  .\deploy.ps1
# ==========================================================================
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
$Stack  = "superlms-network"
$Tpl    = Join-Path $PSScriptRoot "vpc.yaml"

Write-Host "==> Validating template" -ForegroundColor Cyan
aws cloudformation validate-template --template-body "file://$($Tpl -replace '\\','/')" --region $Region | Out-Null

Write-Host "==> Deploying stack $Stack (ap-south-1)" -ForegroundColor Cyan
aws cloudformation deploy `
  --stack-name $Stack `
  --template-file $Tpl `
  --region $Region `
  --no-fail-on-empty-changeset

Write-Host "`n==> Stack outputs (exported for RDS/ElastiCache/ECS phases):" -ForegroundColor Green
aws cloudformation describe-stacks --stack-name $Stack --region $Region `
  --query "Stacks[0].Outputs[].{Key:OutputKey,Value:OutputValue}" --output table

# To tear the whole network down later:
#   aws cloudformation delete-stack --stack-name superlms-network --region ap-south-1
