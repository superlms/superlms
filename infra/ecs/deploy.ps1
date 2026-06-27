# ==========================================================================
# Phase 10 - deploy the ECS cluster + ALB + services (superlms-ecs).
#
# Prereqs: network/rds/redis/media/ecr stacks deployed, the superlms/app
# secret created (Phase 8), and an image pushed to ECR (Phase 9 build-push).
# Idempotent. Run from this folder:  .\deploy.ps1  [-ImageTag <tag>]
# ==========================================================================
param([string]$ImageTag = "latest")
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
$Stack  = "superlms-ecs"
$Tpl    = Join-Path $PSScriptRoot "ecs.yaml"

# Resolve the superlms/app secret ARN (includes the random suffix).
$AppSecretArn = (aws secretsmanager describe-secret --secret-id superlms/app --region $Region --query ARN --output text).Trim()
if (-not $AppSecretArn -or $AppSecretArn -eq "None") { throw "secret superlms/app not found - run infra/secrets/setup-secrets.ps1 first." }
Write-Host "==> App secret: $AppSecretArn" -ForegroundColor Cyan

Write-Host "==> Validating template" -ForegroundColor Cyan
aws cloudformation validate-template --template-body "file://$($Tpl -replace '\\','/')" --region $Region | Out-Null

Write-Host "==> Deploying stack $Stack (image tag: $ImageTag)" -ForegroundColor Cyan
aws cloudformation deploy `
  --stack-name $Stack `
  --template-file $Tpl `
  --region $Region `
  --parameter-overrides ImageTag=$ImageTag AppSecretArn=$AppSecretArn `
  --no-fail-on-empty-changeset

Write-Host "`n==> App is live at:" -ForegroundColor Green
aws cloudformation describe-stacks --stack-name $Stack --region $Region `
  --query "Stacks[0].Outputs[?OutputKey=='AlbUrl'].OutputValue" --output text

Write-Host "`n==> NOTE: the DB has no tables yet. Run the one-off migration task"
Write-Host "    (Phase 11) before the app will work."
