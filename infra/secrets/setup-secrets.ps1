# ==========================================================================
# Phase 8 - create/update the app secret in Secrets Manager (superlms/app).
#
# Reads infra/secrets/app-secrets.json (gitignored - your REAL values),
# auto-generates APP_KEY if left empty, and stores the JSON as the secret
# 'superlms/app'. The ECS task definition (Phase 10) injects each key via
# the `secrets` block. Idempotent. Run from this folder:  .\setup-secrets.ps1
# ==========================================================================
$ErrorActionPreference = "Stop"
$Region = "ap-south-1"
$SecretName = "superlms/app"
$src = Join-Path $PSScriptRoot "app-secrets.json"

if (-not (Test-Path $src)) {
  throw "app-secrets.json not found. Copy app-secrets.example.json -> app-secrets.json and fill it."
}

# Load, drop the _comment helper key.
$data = Get-Content $src -Raw | ConvertFrom-Json
$obj = [ordered]@{}
foreach ($p in $data.PSObject.Properties) { if ($p.Name -ne "_comment") { $obj[$p.Name] = $p.Value } }

# Generate APP_KEY if empty (Laravel base64 32-byte key).
if ([string]::IsNullOrWhiteSpace($obj["APP_KEY"])) {
  $bytes = New-Object byte[] 32
  [System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
  $obj["APP_KEY"] = "base64:" + [Convert]::ToBase64String($bytes)
  Write-Host "==> Generated a new APP_KEY" -ForegroundColor Yellow
  Write-Host "    (If the old app has encrypted data, STOP and paste the old APP_KEY instead.)" -ForegroundColor Yellow
}

# Warn about any still-empty values (non-fatal).
foreach ($k in $obj.Keys) {
  if ([string]::IsNullOrWhiteSpace($obj[$k])) { Write-Host "    WARNING: $k is empty" -ForegroundColor Yellow }
}

$json = ($obj | ConvertTo-Json -Compress)
$tmp = Join-Path $env:TEMP "superlms-app-secret.json"
# Write UTF-8 WITHOUT a BOM - a BOM corrupts the JSON for ECS's secret parser
# ("invalid character" on retrieval). PS 5.1's Set-Content -Encoding utf8 adds one.
[System.IO.File]::WriteAllText($tmp, $json, (New-Object System.Text.UTF8Encoding($false)))

# Create or update.
$exists = $true
try { aws secretsmanager describe-secret --secret-id $SecretName --region $Region 2>$null | Out-Null } catch { $exists = $false }

if ($exists) {
  Write-Host "==> Updating secret $SecretName" -ForegroundColor Cyan
  aws secretsmanager put-secret-value --secret-id $SecretName --secret-string "file://$($tmp -replace '\\','/')" --region $Region | Out-Null
} else {
  Write-Host "==> Creating secret $SecretName" -ForegroundColor Cyan
  aws secretsmanager create-secret --name $SecretName --description "superlms app-level secrets (APP_KEY, ZeptoMail, PhonePe)" --secret-string "file://$($tmp -replace '\\','/')" --region $Region | Out-Null
}

Remove-Item $tmp -Force -ErrorAction SilentlyContinue

Write-Host "`n==> DONE. Secret $SecretName has $($obj.Keys.Count) keys." -ForegroundColor Green
Write-Host "    ARN:" -NoNewline
aws secretsmanager describe-secret --secret-id $SecretName --region $Region --query ARN --output text
Write-Host "    DB creds remain in the separate 'superlms/rds' secret (created by Phase 6)."
