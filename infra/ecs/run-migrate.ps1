# ==========================================================================
# Phase 11 - run database migrations as a ONE-OFF ECS task.
#
# Runs the superlms-migrate task definition once (migrate + lms:migrate),
# waits for it to stop, and reports the exit code. Safe to re-run (migrations
# are idempotent). Prereq: superlms-ecs stack deployed (Phase 10).
# Run from this folder:  .\run-migrate.ps1
# ==========================================================================
$ErrorActionPreference = "Stop"
$Region  = "ap-south-1"
$Cluster = "superlms"
$TaskDef = "superlms-migrate"

# Pull network settings from the network stack outputs.
function Out($k) { (aws cloudformation describe-stacks --stack-name superlms-network --region $Region --query "Stacks[0].Outputs[?OutputKey=='$k'].OutputValue" --output text).Trim() }
$subnets = Out "PublicSubnets"
$sg      = Out "EcsSgId"
$net = "awsvpcConfiguration={subnets=[$subnets],securityGroups=[$sg],assignPublicIp=ENABLED}"

Write-Host "==> Running one-off task $TaskDef on cluster $Cluster" -ForegroundColor Cyan
$taskArn = (aws ecs run-task --cluster $Cluster --task-definition $TaskDef `
  --launch-type FARGATE --network-configuration $net `
  --query "tasks[0].taskArn" --output text).Trim()
if (-not $taskArn -or $taskArn -eq "None") { throw "run-task failed to start a task." }
Write-Host "    task: $taskArn"

Write-Host "==> Waiting for the task to finish..." -ForegroundColor Cyan
aws ecs wait tasks-stopped --cluster $Cluster --tasks $taskArn --region $Region

$code   = (aws ecs describe-tasks --cluster $Cluster --tasks $taskArn --region $Region --query "tasks[0].containers[0].exitCode" --output text).Trim()
$reason = (aws ecs describe-tasks --cluster $Cluster --tasks $taskArn --region $Region --query "tasks[0].stoppedReason" --output text).Trim()

if ($code -eq "0") {
  Write-Host "`n==> Migrations completed successfully (exit 0)." -ForegroundColor Green
} else {
  Write-Host "`n==> Migration task exited with code $code. Reason: $reason" -ForegroundColor Red
  Write-Host "    Check logs: CloudWatch log group /ecs/superlms, stream prefix 'migrate'."
}
