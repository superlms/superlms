# ==========================================================================
# Phase 9 - build the ARM64 app image and push it to ECR.
#
# IMPORTANT: run this from a checkout that has the PRODUCTION Dockerfile
# (nginx+php-fpm, roles, no boot migrations) - i.e. the branch carrying the
# Phase 1 docker refactor. It builds for linux/arm64 (Graviton/Fargate ARM).
#
# NOTE: on an x86 PC the ARM64 build runs under QEMU emulation and the PHP
# extension compile can take 15-40 min. That's expected; CI (Phase 13) will
# build natively. Run from this folder:  .\build-push.ps1
# ==========================================================================
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
$RepoRoot = Resolve-Path (Join-Path $PSScriptRoot "..\..")

$AccountId = (aws sts get-caller-identity --query Account --output text).Trim()
$Registry  = "$AccountId.dkr.ecr.$Region.amazonaws.com"
$Image     = "$Registry/superlms-app"
$Sha       = (git -C $RepoRoot rev-parse --short HEAD).Trim()

Write-Host "==> Logging in to ECR ($Registry)" -ForegroundColor Cyan
aws ecr get-login-password --region $Region | docker login --username AWS --password-stdin $Registry

# Ensure a buildx builder exists (needed for cross-platform builds).
$null = docker buildx inspect superlms-builder 2>$null
if ($LASTEXITCODE -ne 0) {
  Write-Host "==> Creating buildx builder 'superlms-builder'"
  docker buildx create --name superlms-builder --use | Out-Null
} else {
  docker buildx use superlms-builder
}

Write-Host "==> Building linux/arm64 image and pushing ($Image : latest, $Sha)" -ForegroundColor Cyan
Write-Host "    (this is slow under emulation - grab a chai)" -ForegroundColor Yellow
docker buildx build `
  --platform linux/arm64 `
  --target runtime `
  -t "${Image}:latest" `
  -t "${Image}:$Sha" `
  --push `
  $RepoRoot

Write-Host "`n==> Pushed:" -ForegroundColor Green
Write-Host "    ${Image}:latest"
Write-Host "    ${Image}:$Sha"
