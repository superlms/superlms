<#
.SYNOPSIS
  Provision the lite single-box in a (NEW) AWS account: one t4g.medium EC2 with
  a 30 GB gp3 disk, a security group (SSH from your IP, HTTP/HTTPS from anyone),
  and an Elastic IP. Uses the default VPC - no VPC/ALB/RDS stacks needed.
  Idempotent: safe to re-run after a partial failure (reuses SG / key / instance
  / EIP instead of duplicating them).

.NOTES
  - Run AFTER you have created the new AWS account and configured a CLI profile
    for it:  aws configure --profile superlms-new
  - This CREATES billable resources. Review, then run. ~$33-40/mo all-in.
  - It does NOT deploy the app; it prints the SSH command. Then follow README.md.

.EXAMPLE
  ./provision-aws.ps1 -Profile superlms-new -Region ap-south-1 -KeyName superlms-key -MyIp 203.0.113.4/32
#>
param(
  [Parameter(Mandatory = $true)][string]$Profile,
  [string]$Region = "ap-south-1",
  [string]$InstanceType = "t3.medium",   # x86 4GB; reliable Mumbai capacity (t4g/ARM often out of stock)
  [int]$DiskGb = 30,
  [Parameter(Mandatory = $true)][string]$KeyName,
  [Parameter(Mandatory = $true)][string]$MyIp,   # your.public.ip/32  (for SSH)
  [string]$Name = "superlms-lite"
)
# NOTE: 'Continue' (not 'Stop') on purpose. Native aws CLI writes to stderr on
# expected conditions (e.g. duplicate SG on a re-run); under 'Stop' PS 5.1 turns
# that stderr into a terminating error. We gate on $LASTEXITCODE + output checks
# instead, which is what makes this script safely idempotent.
$ErrorActionPreference = "Continue"
function awscli { aws @args --profile $Profile --region $Region --output json }

Write-Host "==> Account check" -ForegroundColor Cyan
$acct = (awscli sts get-caller-identity | ConvertFrom-Json).Account
Write-Host "    Provisioning into account $acct ($Region)"

# Pick the AMI arch from the instance family (ARM Graviton vs x86_64).
if ($InstanceType -match '^(t4g|m6g|m7g|c6g|c7g|r6g|r7g|a1|im4gn|g5g)\.') { $archParam = "arm64" } else { $archParam = "x86_64" }
Write-Host "==> Resolving latest AL2023 $archParam AMI" -ForegroundColor Cyan
$ami = (awscli ssm get-parameter --name `
  "/aws/service/ami-amazon-linux-latest/al2023-ami-kernel-default-$archParam" `
  | ConvertFrom-Json).Parameter.Value
Write-Host "    AMI: $ami ($archParam)"

# Default VPC + a subnet in it.
$vpcId = (awscli ec2 describe-vpcs --filters "Name=isDefault,Values=true" `
  | ConvertFrom-Json).Vpcs[0].VpcId
$subnetId = (awscli ec2 describe-subnets --filters "Name=vpc-id,Values=$vpcId" `
  | ConvertFrom-Json).Subnets[0].SubnetId
Write-Host "    VPC $vpcId  Subnet $subnetId"

# -- Security group (create or reuse by name) -------------------------------
Write-Host "==> Security group" -ForegroundColor Cyan
$sgOut = awscli ec2 create-security-group --group-name "$Name-sg" `
  --description "SuperLMS lite box" --vpc-id $vpcId 2>$null
if ($LASTEXITCODE -eq 0 -and $sgOut) {
  $sgId = ($sgOut | ConvertFrom-Json).GroupId
} else {
  $sgId = (awscli ec2 describe-security-groups `
    --filters "Name=group-name,Values=$Name-sg" "Name=vpc-id,Values=$vpcId" `
    | ConvertFrom-Json).SecurityGroups[0].GroupId
  Write-Host "    Reusing existing SG"
}
# Ingress rules (ignore 'already exists' errors on re-run).
awscli ec2 authorize-security-group-ingress --group-id $sgId `
  --ip-permissions "IpProtocol=tcp,FromPort=22,ToPort=22,IpRanges=[{CidrIp=$MyIp,Description=ssh}]" 2>$null | Out-Null
awscli ec2 authorize-security-group-ingress --group-id $sgId `
  --ip-permissions "IpProtocol=tcp,FromPort=80,ToPort=80,IpRanges=[{CidrIp=0.0.0.0/0}]" `
  "IpProtocol=tcp,FromPort=443,ToPort=443,IpRanges=[{CidrIp=0.0.0.0/0}]" `
  "IpProtocol=udp,FromPort=443,ToPort=443,IpRanges=[{CidrIp=0.0.0.0/0}]" 2>$null | Out-Null
Write-Host "    SG $sgId (22<-$MyIp, 80/443<-world)"

# -- Key pair (create if missing) -------------------------------------------
$keyFile = Join-Path $PSScriptRoot "$KeyName.pem"
awscli ec2 describe-key-pairs --key-names $KeyName 2>$null | Out-Null
if ($LASTEXITCODE -ne 0) {
  Write-Host "==> Creating key pair $KeyName -> $keyFile" -ForegroundColor Cyan
  # NOTE: aws --output text returns the PEM as multiple lines, which PowerShell
  # captures as a string array. Join with LF (NOT -NoNewline alone, which would
  # concatenate the lines into one and produce an unusable key).
  $km = aws ec2 create-key-pair --key-name $KeyName --query "KeyMaterial" `
    --output text --profile $Profile --region $Region
  [System.IO.File]::WriteAllText($keyFile, (($km -join "`n") + "`n"))
  Write-Host "    Saved private key: $keyFile  (keep it safe!)"
} else {
  Write-Host "==> Key pair $KeyName already exists in AWS"
  if (-not (Test-Path $keyFile)) {
    Write-Host "    WARNING: $keyFile missing locally. If lost, delete the key in AWS and re-run." -ForegroundColor Yellow
  }
}

# -- Instance (create or reuse by Name tag) ---------------------------------
Write-Host "==> Instance" -ForegroundColor Cyan
$desc = (awscli ec2 describe-instances `
  --filters "Name=tag:Name,Values=$Name" "Name=instance-state-name,Values=pending,running,stopped" `
  | ConvertFrom-Json)
$iid = $null
if ($desc.Reservations.Count -gt 0) { $iid = @($desc.Reservations.Instances.InstanceId)[0] }
if ($iid) {
  Write-Host "    Reusing existing instance $iid"
} else {
  $bdm = "DeviceName=/dev/xvda,Ebs={VolumeSize=$DiskGb,VolumeType=gp3,DeleteOnTermination=true}"
  # Try each subnet/AZ in the default VPC until one has capacity for this type.
  $subnets = (awscli ec2 describe-subnets --filters "Name=vpc-id,Values=$vpcId" | ConvertFrom-Json).Subnets
  foreach ($sn in $subnets) {
    Write-Host "    Trying $($sn.SubnetId) ($($sn.AvailabilityZone))..."
    $out = awscli ec2 run-instances --image-id $ami --instance-type $InstanceType `
      --key-name $KeyName --security-group-ids $sgId --subnet-id $sn.SubnetId `
      --block-device-mappings $bdm `
      --tag-specifications "ResourceType=instance,Tags=[{Key=Name,Value=$Name}]" 2>$null
    if ($LASTEXITCODE -eq 0 -and $out) {
      $iid = ($out | ConvertFrom-Json).Instances[0].InstanceId
      Write-Host "    Launched $iid ($InstanceType, $DiskGb GB) in $($sn.AvailabilityZone)" -ForegroundColor Green
      break
    }
    Write-Host "    no capacity there, trying next AZ..." -ForegroundColor Yellow
  }
}
if (-not $iid) {
  Write-Host "ERROR: could not launch $InstanceType in any AZ (capacity). Re-run shortly, or try a different type." -ForegroundColor Red
  exit 1
}
Write-Host "    Waiting for it to run..."
awscli ec2 wait instance-running --instance-ids $iid | Out-Null

# -- Elastic IP (reuse existing association, else allocate) ------------------
Write-Host "==> Elastic IP" -ForegroundColor Cyan
$eip = (awscli ec2 describe-addresses --filters "Name=instance-id,Values=$iid" `
  | ConvertFrom-Json).Addresses[0].PublicIp
if (-not $eip) {
  $alloc = (awscli ec2 allocate-address --domain vpc | ConvertFrom-Json).AllocationId
  awscli ec2 associate-address --instance-id $iid --allocation-id $alloc | Out-Null
  $eip = (awscli ec2 describe-addresses --allocation-ids $alloc | ConvertFrom-Json).Addresses[0].PublicIp
} else {
  Write-Host "    Reusing $eip"
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host " Box is up." -ForegroundColor Green
Write-Host "   Instance : $iid ($InstanceType, $DiskGb GB)"
Write-Host "   Public IP: $eip"
Write-Host "   SSH key  : $keyFile"
Write-Host "   SSH cmd  : ssh -i `"$keyFile`" ec2-user@$eip"
Write-Host ""
Write-Host " Next:"
Write-Host "   1. Point superlms.in (A record) at $eip in Route 53."
Write-Host '   2. On the box: curl -fsSL <raw-repo-url>/infra/lite/setup-ec2.sh | bash'
Write-Host "   3. Fill /opt/superlms/infra/lite/.env, then ./deploy.sh"
Write-Host "   See infra/lite/README.md for the full data-migration runbook."
Write-Host "============================================================" -ForegroundColor Green
