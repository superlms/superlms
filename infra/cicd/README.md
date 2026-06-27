# Phase 13 - CI/CD (GitHub Actions -> ECS)

The workflow lives at [`.github/workflows/deploy.yml`](../../.github/workflows/deploy.yml).

## Flow

- **Every PR / push to main** runs `validate` (php lint, prod composer install,
  strict PSR-4, vite build, then boots Laravel against a real MySQL and runs
  config:cache / view:cache / migrate / lms:migrate / route:list).
- **Push to main (validate green)** runs `deploy`:
  1. OIDC-assume `AWS_DEPLOY_ROLE_ARN` (no stored AWS keys),
  2. build the **linux/arm64** image on a native ARM runner and push `:sha` + `:latest` to ECR,
  3. `cloudformation deploy` the `superlms-ecs` stack with the new image tag (rolling, zero-downtime),
  4. run the one-off `superlms-migrate` task,
  5. wait for `superlms-web` to stabilize.

## One-time setup

1. Add a **repo secret** in GitHub (`superlms/superlms` -> Settings -> Secrets
   -> Actions): `AWS_DEPLOY_ROLE_ARN = arn:aws:iam::540361297670:role/github-actions-deploy`.
2. (Optional) create a `production` Environment for approval gates.
3. **Delete the now-obsolete secrets** once this is live: `EC2_HOST`, `EC2_USER`,
   `EC2_SSH_KEY`, `EC2_PROJECT_PATH`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`,
   `ZEPTOMAIL_*` (app secrets now live in Secrets Manager, not GitHub).

## Notes

- This replaces the old `appleboy/ssh-action` EC2 deploy entirely.
- Activates only when this branch is merged to `main` (the go-live cutover).
  Until then the old EC2 site keeps running off whatever is currently on `main`.
- Migrations run after the rolling deploy (assumes backward-compatible changes).
  For a breaking migration, run `infra/ecs/run-migrate.ps1` manually first.
