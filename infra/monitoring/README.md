# Phase 16-17 - Autoscaling + Monitoring

Stack `superlms-monitoring`.

## Run

```powershell
cd infra/monitoring
.\deploy.ps1 -AlertEmail you@superlms.in
```
Then **confirm** the SNS subscription email AWS sends, or alarms can't notify.

## What it sets up

**Autoscaling (web):** target-tracking on CPU 60%, between `WebMin` (2) and
`WebMax` (6) tasks. ECS adds/removes web tasks automatically.

**Alarms -> SNS email:**
| Alarm | Trigger |
|-------|---------|
| `superlms-web-cpu-high` | web CPU > 80% (10 min) |
| `superlms-web-mem-high` | web memory > 85% (10 min) |
| `superlms-alb-5xx` | >10 ELB 5xx in 5 min |
| `superlms-unhealthy-targets` | any web target unhealthy (3 min) |
| `superlms-rds-cpu-high` | RDS CPU > 80% (10 min) |
| `superlms-rds-storage-low` | RDS free storage < 2 GB |

## Savings Plan (manual, Phase 17)

Once load is steady for ~1-2 weeks, buy a **Compute Savings Plan** (1-year,
no upfront) from the Billing console sized to your average Fargate spend — it
covers Fargate + Lambda and is the simplest ~20-30% saving. Review the
Cost Explorer "Savings Plans recommendations" page for the exact commitment.

## Go-live checklist

- [ ] App healthy on https://superlms.in (cert valid, HTTP->HTTPS redirect)
- [ ] Migrations run; login/OTP works (ZeptoMail verified)
- [ ] File upload/serve works (S3 + CloudFront)
- [ ] Worker processing jobs; scheduler running (check logs)
- [ ] Data migrated from old DB (Phase 14); spot-check records
- [ ] Mobile app pointed at superlms.in, new build on stores (Phase 15)
- [ ] Alarms confirmed (SNS email), test one alarm
- [ ] CI/CD: AWS_DEPLOY_ROLE_ARN secret added, merge to main, test a deploy
- [ ] Old EC2 kept read-only for a few days as fallback, then decommissioned
