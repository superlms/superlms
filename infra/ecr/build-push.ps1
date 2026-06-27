# ==========================================================================
# Phase 9 - build the app image and push it to ECR.
#
# Builds linux/amd64 NATIVELY on an x86 PC (fast, no emulation). The ECS task
# definitions are set to X86_64 to match. CI (Phase 13) can later build ARM64
# natively on an ARM runner and flip ECS to Graviton.
#
# Run from a checkout that has the production Dockerfile (the Phase 1 docker
# refactor). Run from this folder:  .\build-push.ps1
# ==========================================================================
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
$RepoRoot = Resolve-Path (Join-Path $PSScriptRoot "..\..")

$AccountId = (aws sts get-caller-identity --query Account --output text).Trim()
$Registry  = "$AccountId.dkr.ecr.$Region.amazonaws.com"
$Image     = "$Registry/superlms-app"
$Sha       = (git -C $RepoRoot rev-parse --short HEAD).Trim()

# Native exe failures don't trip $ErrorActionPreference; check $LASTEXITCODE
# explicitly so a failed login/build/push can never look like success.
function Check($msg) { if ($LASTEXITCODE -ne 0) { throw "FAILED: $msg (exit $LASTEXITCODE)" } }

Write-Host "==> Logging in to ECR ($Registry)" -ForegroundColor Cyan
aws ecr get-login-password --region $Region | docker login --username AWS --password-stdin $Registry
Check "ECR login"

Write-Host "==> Building linux/amd64 image (native, $Image : latest, $Sha)" -ForegroundColor Cyan
docker build --platform linux/amd64 --target runtime -t "${Image}:latest" -t "${Image}:$Sha" $RepoRoot
Check "docker build"

Write-Host "==> Pushing to ECR" -ForegroundColor Cyan
docker push "${Image}:latest";  Check "push :latest"
docker push "${Image}:$Sha";    Check "push :$Sha"

Write-Host "`n==> Pushed:" -ForegroundColor Green
Write-Host "    ${Image}:latest"
Write-Host "    ${Image}:$Sha"
