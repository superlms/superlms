# ==========================================================================
# Phase 9 - create the ECR repository (superlms-app).
# Idempotent. Run from this folder:  .\deploy.ps1
# ==========================================================================
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
$Stack  = "superlms-ecr"
$Tpl    = Join-Path $PSScriptRoot "ecr.yaml"

Write-Host "==> Validating template" -ForegroundColor Cyan
aws cloudformation validate-template --template-body "file://$($Tpl -replace '\\','/')" --region $Region | Out-Null

Write-Host "==> Deploying stack $Stack" -ForegroundColor Cyan
aws cloudformation deploy --stack-name $Stack --template-file $Tpl --region $Region --no-fail-on-empty-changeset

Write-Host "`n==> ECR repo:" -ForegroundColor Green
aws cloudformation describe-stacks --stack-name $Stack --region $Region `
  --query "Stacks[0].Outputs[].{Key:OutputKey,Value:OutputValue}" --output table
