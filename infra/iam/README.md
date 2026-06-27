# Phase 3 — IAM

All IAM identities for the AWS migration, as version-controlled policy
documents plus one idempotent setup script.

## Prerequisites (Phase 3.1 — do once, in the AWS Console as root)

1. **IAM → Users → Create user** `admin` → attach `AdministratorAccess`.
2. Create an **access key** (CLI) for `admin`, and enable **MFA** on it.
3. Locally: `aws configure` → paste key/secret → region `ap-south-1`.
4. Verify: `aws sts get-caller-identity` (note the 12-digit Account id).

## Run it

```powershell
cd infra/iam
./setup.ps1
```

The script reads your account id automatically, substitutes it into the
JSON templates, and creates everything. It is **idempotent** — re-running
updates trust policies / re-attaches policies without error.

## What gets created

| Identity | Purpose | Trusted by |
|----------|---------|------------|
| GitHub OIDC provider | Lets GitHub Actions get short-lived AWS creds (no stored keys) | — |
| `github-actions-deploy` | CI pushes images to ECR + updates ECS services | GitHub `superlms/superlms` `main` only |
| `ecsTaskExecutionRole` | ECS platform: pull image, read Secrets Manager, write CloudWatch logs | `ecs-tasks.amazonaws.com` |
| `superlms-task-role` | The app itself at runtime: S3 read/write | `ecs-tasks.amazonaws.com` |

## Files

| File | Used for |
|------|----------|
| `github-oidc-trust.json` | trust policy of `github-actions-deploy` |
| `github-deploy-permissions.json` | ECR push + ECS deploy + `iam:PassRole` |
| `ecs-tasks-trust.json` | trust policy shared by both ECS roles |
| `execution-role-secrets.json` | execution role → read `superlms/*` secrets |
| `app-task-permissions.json` | task role → S3 `superlms-*` buckets |

`ACCOUNT_ID` in every JSON is a placeholder the script replaces at runtime.

## After running

- Copy the printed **deploy role ARN** → add as GitHub repo secret
  `AWS_DEPLOY_ROLE_ARN` (used by the new ECS workflow in Phase 13).
- The old `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` GitHub secrets
  become obsolete once Phase 13 lands — delete them then.

## Notes / scoping

- S3 and Secrets resources are scoped to `superlms-*` names. Use that
  prefix when you create the bucket (Phase 5) and secrets (Phase 8).
- The deploy role is locked to the `main` branch. To allow deploys from
  the Actions tab on other branches, widen the `sub` condition in
  `github-oidc-trust.json` and re-run.
