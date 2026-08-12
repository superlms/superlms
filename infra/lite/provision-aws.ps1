<#
.SYNOPSIS
  Provision the lite single-box in a (NEW) AWS account: one t4g.medium EC2 with
  a 30 GB gp3 disk, a security group (SSH from your IP, HTTP/HTTPS from anyone),
  and an Elastic IP. Uses the default VPC — no VPC/ALB/RDS stacks needed.

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
  [string]$InstanceType = "t4g.medium",
  [int]$DiskGb = 30,
  [Parameter(Mandatory = $true)][string]$KeyName,
  [Parameter(Mandatory = $true)][string]$MyIp,   # your.public.ip/32  (for SSH)
  [string]$Name = "superlms-lite"
)
$ErrorActionPreference = "Stop"
function awscli { aws @args --profile $Profile --region $Region --output json }

Write-Host "==> Account check" -ForegroundColor Cyan
$acct = (awscli sts get-caller-identity | ConvertFrom-Json).Account
Write-Host "    Provisioning into account $acct ($Region)"

# Latest Amazon Linux 2023 arm64 AMI (public SSM parameter — always current).
Write-Host "==> Resolving latest AL2023 arm64 AMI" -ForegroundColor Cyan
$ami = (awscli ssm get-parameter --name `
  "/aws/service/ami-amazon-linux-latest/al2023-ami-kernel-default-arm64" `
  | ConvertFrom-Json).Parameter.Value
Write-Host "    AMI: $ami"

# Default VPC + a subnet in it.
$vpcId = (awscli ec2 describe-vpcs --filters "Name=isDefault,Values=true" `
  | ConvertFrom-Json).Vpcs[0].VpcId
$subnetId = (awscli ec2 describe-subnets --filters "Name=vpc-id,Values=$vpcId" `
  | ConvertFrom-Json).Subnets[0].SubnetId
Write-Host "    VPC $vpcId  Subnet $subnetId"

# Security group.
Write-Host "==> Security group" -ForegroundColor Cyan
$sgId = (awscli ec2 create-security-group --group-name "$Name-sg" `
  --description "SuperLMS lite box" --vpc-id $vpcId | ConvertFrom-Json).GroupId
awscli ec2 authorize-security-group-ingress --group-id $sgId `
  --ip-permissions "IpProtocol=tcp,FromPort=22,ToPort=22,IpRanges=[{CidrIp=$MyIp,Description=ssh}]" | Out-Null
awscli ec2 authorize-security-group-ingress --group-id $sgId `
  --ip-permissions "IpProtocol=tcp,FromPort=80,ToPort=80,IpRanges=[{CidrIp=0.0.0.0/0}]" `
  "IpProtocol=tcp,FromPort=443,ToPort=443,IpRanges=[{CidrIp=0.0.0.0/0}]" `
  "IpProtocol=udp,FromPort=443,ToPort=443,IpRanges=[{CidrIp=0.0.0.0/0}]" | Out-Null
Write-Host "    SG $sgId (22<-$MyIp, 80/443<-world)"

# Key pair (create if absent; saves private key next to this script).
$keyFile = Join-Path $PSScriptRoot "$KeyName.pem"
$exists = $true
try { awscli ec2 describe-key-pairs --key-names $KeyName | Out-Null } catch { $exists = $false }
if (-not $exists) {
  Write-Host "==> Creating key pair $KeyName -> $keyFile" -ForegroundColor Cyan
  $km = awscli ec2 create-key-pair --key-name $KeyName --query "KeyMaterial" --output text
  Set-Content -Path $keyFile -Value $km -NoNewline -Encoding ascii
  Write-Host "    Saved private key: $keyFile  (keep it safe!)"
}

# Launch instance with a 30 GB gp3 root volume.
Write-Host "==> Launching $InstanceType" -ForegroundColor Cyan
$bdm = "DeviceName=/dev/xvda,Ebs={VolumeSize=$DiskGb,VolumeType=gp3,DeleteOnTermination=true}"
$iid = (awscli ec2 run-instances --image-id $ami --instance-type $InstanceType `
  --key-name $KeyName --security-group-ids $sgId --subnet-id $subnetId `
  --block-device-mappings $bdm `
  --tag-specifications "ResourceType=instance,Tags=[{Key=Name,Value=$Name}]" `
  | ConvertFrom-Json).Instances[0].InstanceId
Write-Host "    Instance $iid — waiting to run..."
awscli ec2 wait instance-running --instance-ids $iid

# Elastic IP so the public IP is stable across stop/start (needed for DNS).
Write-Host "==> Allocating + associating Elastic IP" -ForegroundColor Cyan
$alloc = (awscli ec2 allocate-address --domain vpc | ConvertFrom-Json).AllocationId
awscli ec2 associate-address --instance-id $iid --allocation-id $alloc | Out-Null
$eip = (awscli ec2 describe-addresses --allocation-ids $alloc | ConvertFrom-Json).Addresses[0].PublicIp

Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host " Box is up." -ForegroundColor Green
Write-Host "   Instance : $iid ($InstanceType, ${DiskGb}GB)"
Write-Host "   Public IP: $eip   (Elastic IP $alloc)"
Write-Host "   SSH      : ssh -i `"$keyFile`" ec2-user@$eip"
Write-Host ""
Write-Host " Next:"
Write-Host "   1. Point superlms.in (A record) at $eip in Route 53."
Write-Host "   2. SSH in and run:  curl -fsSL <repo>/infra/lite/setup-ec2.sh | bash"
Write-Host "   3. Fill /opt/superlms/infra/lite/.env, then ./deploy.sh"
Write-Host "   See infra/lite/README.md for the full data-migration runbook."
Write-Host "============================================================" -ForegroundColor Green
