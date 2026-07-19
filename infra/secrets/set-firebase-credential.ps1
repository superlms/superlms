# Store a Firebase service-account JSON into the superlms/app secret under the
# FIREBASE_CREDENTIALS key, so ECS can inject it for server-side FCM (push).
# The JSON never leaves your machine except to your own AWS Secrets Manager.
#
#   cd infra/secrets
#   .\set-firebase-credential.ps1 -JsonPath "C:\path\to\super-lms-48c90-firebase-adminsdk.json"
#
# Then apply it to ECS:   cd ..\ecs ;  .\deploy.ps1
# (deploy.ps1 registers a task def carrying the FIREBASE_CREDENTIALS secret and
#  rolls the service; config:cache at boot picks it up.)
param(
    [Parameter(Mandatory = $true)][string]$JsonPath,
    [string]$Region   = "ap-south-1",
    [string]$SecretId = "superlms/app"
)
$ErrorActionPreference = "Stop"

if (-not (Test-Path $JsonPath)) { throw "File not found: $JsonPath" }

# Validate it really is a service-account key.
$sa = Get-Content -Raw $JsonPath | ConvertFrom-Json
if (-not $sa.private_key -or -not $sa.client_email) {
    throw "This does not look like a Firebase service-account JSON (missing private_key / client_email)."
}
Write-Host "Service account -> project: $($sa.project_id), client: $($sa.client_email)" -ForegroundColor Cyan

# Merge FIREBASE_CREDENTIALS into the existing secret map (keep all other keys).
$current = aws secretsmanager get-secret-value --region $Region --secret-id $SecretId --query SecretString --output text
$map = if ($current -and $current -ne "None") { $current | ConvertFrom-Json } else { [pscustomobject]@{} }

$compactSa = ($sa | ConvertTo-Json -Compress -Depth 20)
$map | Add-Member -NotePropertyName FIREBASE_CREDENTIALS -NotePropertyValue $compactSa -Force

# Write the merged secret via a temp file (avoids CLI arg-length/quoting issues).
$tmp = New-TemporaryFile
($map | ConvertTo-Json -Compress -Depth 20) | Set-Content -Path $tmp -Encoding utf8 -NoNewline
try {
    aws secretsmanager put-secret-value --region $Region --secret-id $SecretId --secret-string "file://$($tmp.FullName)" | Out-Null
} finally {
    Remove-Item $tmp -Force
}

Write-Host "Stored FIREBASE_CREDENTIALS in $SecretId." -ForegroundColor Green
Write-Host "Next:  cd ..\ecs ;  .\deploy.ps1   # rolls ECS with the credential" -ForegroundColor Yellow
