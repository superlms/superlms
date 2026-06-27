# Phase 10 - ECS + ALB + services

Stack `superlms-ecs`: a Fargate (ARM64) cluster behind an internet-facing ALB,
running three services from the single `superlms-app` image.

| Service | Role | Count | Capacity | LB |
|---------|------|-------|----------|-----|
| `superlms-web` | nginx + php-fpm (default CMD) | 2 | FARGATE | ALB :80 -> :80 |
| `superlms-worker` | `php artisan queue:work` | 1 | FARGATE **Spot** | - |
| `superlms-scheduler` | `php artisan schedule:work` | 1 | FARGATE | - |

- Tasks run in **public subnets with a public IP** so they pull from ECR over
  the free Internet Gateway (no NAT). The ECS SG only allows inbound from the
  ALB, so they're not exposed.
- Plain config -> container `environment`; secrets -> `secrets` from
  `superlms/rds` (DB user/pass) and `superlms/app` (APP_KEY, ZeptoMail, PhonePe).
- Endpoints (DB/Redis/S3/CDN/ECR) are imported from the earlier stacks.
- Health check: ALB -> `/up` (Laravel health route).
- Logs -> CloudWatch `/ecs/superlms` (stream prefixes web/worker/scheduler).

## Prereqs

network, rds, redis, media, ecr stacks deployed; `superlms/app` secret created
(Phase 8); image pushed to ECR (Phase 9 `build-push.ps1`).

## Run

```powershell
cd infra/ecs
.\deploy.ps1                 # uses image tag "latest"
# or a specific build:
.\deploy.ps1 -ImageTag <gitsha>
```

Prints the ALB URL. **The DB is empty** until the one-off migration task
(Phase 11) runs - until then the app will error on DB queries.

## Notes / next

- HTTPS (443) + ACM cert + custom domain come in Phase 12 (add a 443 listener,
  redirect 80->443). For now it's HTTP on the ALB DNS name.
- `APP_URL` is set to the ALB DNS for now; switch to the real domain in Phase 12.
- Firebase push still needs wiring (see infra/secrets README TODO).
- Autoscaling + Savings Plan are Phase 16-17.
