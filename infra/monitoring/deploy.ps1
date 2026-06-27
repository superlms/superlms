# ==========================================================================
# Phase 16-17 - autoscaling + CloudWatch alarms (superlms-monitoring).
# Prereq: superlms-ecs deployed (imports ALB/TG full names). After deploy,
# CONFIRM the SNS subscription email AWS sends you. Run from this folder.
# ==========================================================================
param([string]$AlertEmail = "edyonelms1@gmail.com")
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
$Stack  = "superlms-monitoring"
$Tpl    = Join-Path $PSScriptRoot "monitoring.yaml"

Write-Host "==> Validating template" -ForegroundColor Cyan
aws cloudformation validate-template --template-body "file://$($Tpl -replace '\\','/')" --region $Region | Out-Null

Write-Host "==> Deploying $Stack (alerts -> $AlertEmail)" -ForegroundColor Cyan
aws cloudformation deploy `
  --stack-name $Stack `
  --template-file $Tpl `
  --region $Region `
  --parameter-overrides AlertEmail=$AlertEmail `
  --no-fail-on-empty-changeset

Write-Host "`n==> Done. CONFIRM the 'AWS Notification - Subscription Confirmation'" -ForegroundColor Green
Write-Host "    email sent to $AlertEmail, else alarms can't notify you."
