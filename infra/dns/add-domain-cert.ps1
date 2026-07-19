# Issue an ACM certificate for a school custom domain and attach it to the
# SUPERLMS ALB :443 listener (via SNI). Fixes ERR_CERT_COMMON_NAME_INVALID that
# happens when a school domain points at the ALB but has no cert of its own.
#
# These domains are NOT in the superlms Route53 zone (they live at Hostinger),
# so ACM validation is DNS but the CNAMEs must be added MANUALLY at the domain's
# registrar. The script prints them, waits for validation, then attaches the cert.
#
#   cd infra/dns
#   .\add-domain-cert.ps1 -Domain edyonelms.in
#
param(
    [Parameter(Mandatory = $true)][string]$Domain,
    [string]$Region = "ap-south-1",
    [bool]$IncludeWww = $true
)

$ErrorActionPreference = "Stop"

# 1. Request the cert (apex + optional www) --------------------------------
Write-Host "==> Requesting ACM certificate for $Domain ..." -ForegroundColor Cyan
$reqArgs = @("acm", "request-certificate", "--region", $Region,
    "--domain-name", $Domain, "--validation-method", "DNS",
    "--query", "CertificateArn", "--output", "text")
if ($IncludeWww) { $reqArgs += @("--subject-alternative-names", "www.$Domain") }
$certArn = & aws @reqArgs
Write-Host "    CertificateArn: $certArn"

Start-Sleep -Seconds 6   # let ACM populate the validation records

# 2. Print the DNS validation CNAMEs to add at Hostinger -------------------
Write-Host "`n==> Add these CNAME record(s) at your DNS provider (Hostinger), then leave this running:" -ForegroundColor Yellow
aws acm describe-certificate --region $Region --certificate-arn $certArn `
    --query "Certificate.DomainValidationOptions[].ResourceRecord" --output table

# 3. Wait for ISSUED -------------------------------------------------------
Write-Host "`n==> Waiting for the certificate to validate (add the CNAMEs above first) ..." -ForegroundColor Cyan
aws acm wait certificate-validated --region $Region --certificate-arn $certArn
Write-Host "    Certificate ISSUED." -ForegroundColor Green

# 4. Attach it to the ALB :443 listener as an additional (SNI) cert --------
Write-Host "`n==> Locating the ALB :443 listener ..." -ForegroundColor Cyan
$albDns = aws cloudformation list-exports --region $Region `
    --query "Exports[?Name=='superlms-alb-dns'].Value" --output text
$albArn = aws elbv2 describe-load-balancers --region $Region `
    --query "LoadBalancers[?DNSName=='$albDns'].LoadBalancerArn" --output text
$listenerArn = aws elbv2 describe-listeners --region $Region --load-balancer-arn $albArn `
    --query "Listeners[?Port==``443``].ListenerArn" --output text

if (-not $listenerArn) {
    throw "No :443 listener found — deploy the base superlms cert first (cd ..\ecs; .\deploy.ps1 -AlbCertArn <arn>)."
}

aws elbv2 add-listener-certificates --region $Region `
    --listener-arn $listenerArn --certificates CertificateArn=$certArn | Out-Null

Write-Host "`nDone. https://$Domain now serves its own certificate (SNI)." -ForegroundColor Green
Write-Host "Final check: set the domain + toggle Publish in the School Website builder." -ForegroundColor Green
