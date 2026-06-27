# Phase 9 - ECR (container registry)

Stack `superlms-ecr`: one repository `superlms-app` (scan-on-push, keep last
10 images). The single image runs all 4 roles (web/worker/scheduler/migrate)
via command override on ECS.

## 1. Create the repo

```powershell
cd infra/ecr
.\deploy.ps1
```

Exports `superlms-ecr-uri` (e.g. `540361297670.dkr.ecr.ap-south-1.amazonaws.com/superlms-app`).

## 2. Build + push the image (ARM64)

Run from a checkout that has the **production Dockerfile** (the Phase 1 docker
refactor branch):

```powershell
cd infra/ecr
.\build-push.ps1
```

- Builds `linux/arm64` (Graviton/Fargate ARM) and pushes `:latest` + `:<gitsha>`.
- On an x86 PC this uses QEMU emulation and is **slow** (15-40 min for the PHP
  extension compile). CI (Phase 13) builds it natively/faster.

## Notes

- The `github-actions-deploy` role can push to `superlms*` repos (Phase 3), so
  CI will reuse this repo in Phase 13.
- Fargate task architecture must be **ARM64** to match this image (set in the
  Phase 10 task definition).
