# ==========================================================================
# Phase 3 - IAM setup for the EdyOne LMS AWS migration.
#
# Creates (idempotent - safe to re-run):
#   1. GitHub OIDC identity provider
#   2. github-actions-deploy   role  (assumed by GitHub Actions via OIDC)
#   3. ecsTaskExecutionRole          (ECS pulls image, reads secrets, logs)
#   4. edyonelms-task-role           (the app's own runtime permissions: S3)
#
# Prereqs: 'aws configure' already done with the admin user (Phase 3.1).
# Run from this folder:  .\setup.ps1
# ==========================================================================
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
$Dir    = $PSScriptRoot

# Resolve the account id from the configured admin credentials.
$AccountId = (aws sts get-caller-identity --query Account --output text).Trim()
if (-not $AccountId) { throw "Could not read AWS account id - is 'aws configure' done?" }
Write-Host "==> Account: $AccountId  Region: $Region" -ForegroundColor Cyan

# Render a policy JSON template (replace ACCOUNT_ID) into a temp file, return file:// uri.
function Render($name) {
  $src = Join-Path $Dir $name
  $dst = Join-Path $env:TEMP "iam-$name"
  (Get-Content $src -Raw).Replace("ACCOUNT_ID", $AccountId) | Set-Content $dst -Encoding ascii
  return "file://$($dst -replace '\\','/')"
}

function RoleExists($role) {
  try { aws iam get-role --role-name $role 2>$null | Out-Null; return $true } catch { return $false }
}

# 1. GitHub OIDC provider --------------------------------------------------
$oidcArn = "arn:aws:iam::${AccountId}:oidc-provider/token.actions.githubusercontent.com"
$exists = $false
try { aws iam get-open-id-connect-provider --open-id-connect-provider-arn $oidcArn 2>$null | Out-Null; $exists = $true } catch {}
if ($exists) {
  Write-Host "==> OIDC provider already exists, skipping" -ForegroundColor Yellow
} else {
  Write-Host "==> Creating GitHub OIDC provider"
  aws iam create-open-id-connect-provider `
    --url https://token.actions.githubusercontent.com `
    --client-id-list sts.amazonaws.com `
    --thumbprint-list 6938fd4d98bab03faadb97b34396831e3780aea1 | Out-Null
}

# 2. GitHub Actions deploy role --------------------------------------------
$deployRole = "github-actions-deploy"
if (RoleExists $deployRole) {
  Write-Host "==> $deployRole exists - updating trust + policy" -ForegroundColor Yellow
  aws iam update-assume-role-policy --role-name $deployRole --policy-document (Render "github-oidc-trust.json") | Out-Null
} else {
  Write-Host "==> Creating role $deployRole"
  aws iam create-role --role-name $deployRole `
    --assume-role-policy-document (Render "github-oidc-trust.json") `
    --description "Assumed by GitHub Actions (superlms/superlms main) to deploy to ECS" | Out-Null
}
aws iam put-role-policy --role-name $deployRole `
  --policy-name ecr-ecs-deploy --policy-document (Render "github-deploy-permissions.json") | Out-Null

# 3. ECS task EXECUTION role -----------------------------------------------
$execRole = "ecsTaskExecutionRole"
if (-not (RoleExists $execRole)) {
  Write-Host "==> Creating role $execRole"
  aws iam create-role --role-name $execRole `
    --assume-role-policy-document (Render "ecs-tasks-trust.json") `
    --description "ECS pulls images, reads secrets, writes logs" | Out-Null
} else {
  Write-Host "==> $execRole exists - ensuring policies attached" -ForegroundColor Yellow
}
aws iam attach-role-policy --role-name $execRole `
  --policy-arn arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy | Out-Null
aws iam put-role-policy --role-name $execRole `
  --policy-name read-app-secrets --policy-document (Render "execution-role-secrets.json") | Out-Null

# 4. App TASK role (runtime perms for the PHP app) -------------------------
$taskRole = "edyonelms-task-role"
if (-not (RoleExists $taskRole)) {
  Write-Host "==> Creating role $taskRole"
  aws iam create-role --role-name $taskRole `
    --assume-role-policy-document (Render "ecs-tasks-trust.json") `
    --description "Runtime permissions for the EdyOne LMS app (S3, etc.)" | Out-Null
} else {
  Write-Host "==> $taskRole exists - updating policy" -ForegroundColor Yellow
}
aws iam put-role-policy --role-name $taskRole `
  --policy-name app-runtime --policy-document (Render "app-task-permissions.json") | Out-Null

# Done - print the ARNs you'll need next -----------------------------------
Write-Host "`n==> DONE. Save these ARNs:" -ForegroundColor Green
Write-Host "  Deploy role (add to GitHub secret AWS_DEPLOY_ROLE_ARN):"
Write-Host "    arn:aws:iam::${AccountId}:role/${deployRole}"
Write-Host "  Task execution role:  arn:aws:iam::${AccountId}:role/${execRole}"
Write-Host "  App task role:        arn:aws:iam::${AccountId}:role/${taskRole}"
