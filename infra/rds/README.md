# Phase 6 - RDS MySQL

Stack `superlms-rds`: MySQL 8, `db.t4g.small`, Single-AZ, private subnets,
reachable only from the ECS security group. Master password is generated and
kept in Secrets Manager (`superlms/rds`) - never in plaintext.

## Run

```powershell
cd infra/rds
.\deploy.ps1
```

Takes ~6-12 min (RDS provisioning). Requires the Phase 4 VPC stack.

## What gets created

| Resource | Notes |
|----------|-------|
| Secrets Manager `superlms/rds` | generated 24-char password; after creation also holds host/port |
| DB subnet group | the two private subnets |
| RDS instance `superlms-mysql` | MySQL 8, db.t4g.small, 20 GB gp3, encrypted, 7-day backups, Single-AZ, not public |

`DeletionPolicy: Snapshot` - deleting the stack takes a final snapshot first.

## App wiring (Phase 8)

The app reads DB creds from secret `superlms/rds`. Exports for the ECS task def:

| Export | App env |
|--------|---------|
| `superlms-db-host` | `DB_HOST` |
| `superlms-db-port` | `DB_PORT` (3306) |
| `superlms-db-name` | `DB_DATABASE` (superlms) |
| `superlms-db-secret-arn` | source for `DB_USERNAME` / `DB_PASSWORD` (secret keys) |

## Notes

- Single-AZ now (cost). To go HA later: set `MultiAZ: true` and redeploy.
- Migrations are NOT auto-run; they run as a one-off ECS task (Phase 11).
