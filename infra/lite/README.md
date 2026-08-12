# SuperLMS "lite" — single-box deploy on a new AWS account (~$33/mo)

Move SuperLMS from the current **Fargate + ALB + RDS + ElastiCache** stack
(~$150–165/mo) onto **one small EC2 box running Docker Compose** (app + MySQL +
Redis), in a **new AWS account**, keeping all data. Sized for **~4–5k students**
and daily activity.

> The current setup is fine engineering — it's just enterprise-shaped and
> priced. At this scale, one tuned box does the job for a fraction of the cost.
> This is actually the same shape the app ran in originally (see
> `../data/README.md`).

---

## 1. Why the bill is ~$150 (real numbers, Aug 2026)

| Piece | ~/mo | Removed in lite? |
|---|---|---|
| Fargate (2 web + worker + scheduler) | $53 | ✅ → containers on the box |
| RDS db.t4g.small | $35 | ✅ → MySQL container |
| Public IPv4 (per ALB node + per task) | $24 | ✅ → 1 Elastic IP (~$3.6) |
| ALB | $18 | ✅ → Caddy on the box |
| CloudWatch Container Insights | $18 | ✅ → Docker logs (json-file) |
| ElastiCache Redis | $15 | ✅ → Redis container |
| Route53 / Secrets / S3 | $3 | keep Route53 + S3; drop Secrets Mgr |
| GST 18% | ~$25 | shrinks with the base |

### Lite target

| Item | ~/mo |
|---|---|
| EC2 `t4g.medium` (4 GB) | $33 on-demand / **~$21 with 1-yr Savings Plan** |
| EBS 30 GB gp3 | ~$2.7 |
| 1 Elastic IP | ~$3.6 |
| S3 + CloudFront (media) | ~$1 |
| Route53 | ~$1 |
| **Total** | **~$41 on-demand → ~$33 with Savings Plan (incl. GST)** |

To stay **under $40 all-in** with the comfortable 4 GB box you need a **1-year
Compute Savings Plan** (a commitment — buy it yourself once the box is stable).
Pure on-demand lands at ~$41–48 with tax. A **new account's free credits**
cover the first months, so start on-demand and add the Savings Plan later.
`t4g.small` (2 GB) is cheaper (~$28 all-in) but tight for 4–5k students —
not recommended.

---

## 2. What's in this folder

| File | Purpose |
|---|---|
| `docker-compose.prod.yml` | The whole stack: Caddy, web, worker, scheduler, MySQL, Redis |
| `Caddyfile` | Auto-HTTPS (Let's Encrypt) — replaces ALB + ACM |
| `config/mysql-tuning.cnf` | MySQL tuned for a 4 GB shared box |
| `config/redis.conf` | Redis: cache+session+queue, `noeviction` |
| `config/php-fpm-pool.conf` | php-fpm `max_children=8` (fits 4 GB) |
| `.env.prod.example` | Production env template (copy to `.env`) |
| `provision-aws.ps1` | Create the EC2 + SG + Elastic IP in the new account |
| `setup-ec2.sh` | Bootstrap a fresh box (Docker, swap, clone, start) |
| `deploy.sh` | Build image, migrate, (re)start — run for every deploy |
| `backup-db.sh` | Daily `mysqldump` → S3 (cron) |

---

## 3. Prerequisites (you do these — I can't)

1. **Create the new AWS account** (console signup + payment method + verify).
   Creating accounts / entering payment info is on you.
2. **Configure a CLI profile** for it on this PC:
   ```bash
   aws configure --profile superlms-new
   ```
3. Decide the **cutover window** (a low-traffic hour; expect ~10–30 min where
   new writes on the old site won't carry over).

---

## 4. Provision the box (new account)

```powershell
# from infra/lite, with the new-account profile
./provision-aws.ps1 -Profile superlms-new -Region ap-south-1 `
  -KeyName superlms-key -MyIp <YOUR.PUBLIC.IP>/32
```
Note the **Elastic IP** it prints. (First set `REPO_URL` in `setup-ec2.sh` to
your Git remote, and make sure the repo is reachable — public, or add a deploy
key.)

---

## 5. First deploy (on the box)

```bash
ssh -i superlms-key.pem ec2-user@<ELASTIC_IP>
curl -fsSL https://raw.githubusercontent.com/<you>/superlms/main/infra/lite/setup-ec2.sh | bash
# it clones to /opt/superlms and creates infra/lite/.env from the template

nano /opt/superlms/infra/lite/.env     # fill EVERYTHING (see notes below)
cd /opt/superlms/infra/lite
newgrp docker
./deploy.sh                             # build + migrate + start
```

**`.env` notes**
- `APP_KEY`: **use the OLD account's APP_KEY** if the DB has encrypted columns
  (it does — `users.password_plain` is `Crypt`-encrypted). Get it from the old
  account:
  ```bash
  aws secretsmanager get-secret-value --secret-id superlms/app \
    --region ap-south-1 --query SecretString --output text   # (old profile)
  ```
- `DB_PASSWORD` / `DB_ROOT_PASSWORD`: pick new strong values.
- `AWS_*`: point at the media bucket + CloudFront in the **new** account
  (create them, or reuse — see step 7). Backups use `BACKUP_S3_BUCKET`.
- ZeptoMail / PhonePe / Firebase: copy from the old `superlms/app` secret.

At this point the app runs on a **fresh empty DB**. Next, load the real data.

---

## 6. Migrate the database (old → new)

**a. Dump the old DB.** The old data lives in RDS. Easiest is to run mysqldump
from anywhere that can reach it (temporarily make RDS public to your IP, per
`../data/README.md` §2 Option A, or from an old ECS/EC2 host):
```bash
mysqldump -h <old-rds-endpoint> -u superlms -p \
  --single-transaction --quick --routines --triggers \
  superlms | gzip > superlms-dump.sql.gz
```

**b. Copy the dump to the new box:**
```powershell
scp -i superlms-key.pem superlms-dump.sql.gz ec2-user@<ELASTIC_IP>:/tmp/
```

**c. Import into the box's MySQL container:**
```bash
# on the box
gunzip -c /tmp/superlms-dump.sql.gz | \
  docker exec -i superlms-mysql sh -c \
  'exec mysql -u root -p"$MYSQL_ROOT_PASSWORD" superlms'
# (MYSQL_ROOT_PASSWORD is already in the container env)
```
> Do **not** run `migrate:fresh` or seeders against real data. `deploy.sh`
> already ran plain `migrate` (additive) — safe to run again after import.

**d. Verify:** log in as a known user, open a dashboard, check row counts.

---

## 7. Migrate uploaded files (S3)

Media stays on S3 + CloudFront (cheap, keeps `cdn.superlms.in`). Create a
bucket + CloudFront distribution in the new account (mirror
`../s3/s3-cdn.yaml`, or make a simple bucket), then copy objects:
```powershell
aws s3 sync s3://superlms-media-540361297670 s3://<new-bucket> `
  --source-region ap-south-1 --region ap-south-1
```
Set `AWS_BUCKET` / `AWS_URL` in `.env` accordingly and `./deploy.sh` again.
(To skip S3 entirely, set `FILESYSTEM_DISK=local` — bigger EBS, no CDN; not
recommended if the mobile app uses `cdn.superlms.in`.)

---

## 8. Backups (do this before cutover)

```bash
( crontab -l 2>/dev/null; \
  echo "30 20 * * * /opt/superlms/infra/lite/backup-db.sh >> /var/log/superlms-backup.log 2>&1" ) | crontab -
/opt/superlms/infra/lite/backup-db.sh   # run once to confirm it uploads
```
Also enable **EBS snapshots** (Data Lifecycle Manager, daily) for whole-box
recovery. Redis AOF + MySQL both persist on the EBS volume.

---

## 9. Cutover (DNS)

DNS is on **Route 53** (see memory: `superlms-dns-on-route53`). Whether the
hosted zone stays in the old account or moves, the change is the same — point
the apex at the box:

1. Lower the `superlms.in` A-record **TTL to 60s** a few hours ahead.
2. At the window: dump → import the last delta (repeat §6 for rows changed
   since the first dump, or take a short read-only maintenance window).
3. Change the `superlms.in` (and `www`) **A record to the Elastic IP**
   (remove the old ALB alias). Caddy issues the TLS cert automatically within
   ~1 min once DNS resolves to the box.
4. Re-point `cdn.superlms.in` to the new CloudFront (if the bucket moved).
5. Watch: `docker compose -f docker-compose.prod.yml logs -f caddy web`.

**Rollback:** point the A record back at the old ALB alias. Keep the old stack
running read-only for a few days as a fallback before decommissioning.

---

## 10. Decommission the old account (after a few stable days)

Delete the CloudFormation stacks (reverse order) in the **old** account:
```powershell
# old profile / ap-south-1
aws cloudformation delete-stack --stack-name superlms-monitoring
aws cloudformation delete-stack --stack-name superlms-ecs
aws cloudformation delete-stack --stack-name superlms-redis
aws cloudformation delete-stack --stack-name superlms-rds     # snapshot kept (DeletionPolicy: Snapshot)
aws cloudformation delete-stack --stack-name superlms-s3      # bucket RETAINED — empty+delete manually if wanted
aws cloudformation delete-stack --stack-name superlms-vpc
```
Then remove leftover Elastic IPs, the ECR repo, CloudWatch log groups, and
Secrets Manager secrets. Confirm `$0` on the next old-account bill.

---

## 11. Day-2 operations

```bash
cd /opt/superlms/infra/lite
C="docker compose -f docker-compose.prod.yml --env-file .env"

$C ps                        # status
$C logs -f web               # tail app logs
$C restart web               # restart a service
git -C /opt/superlms pull && ./deploy.sh   # deploy a new version
docker stats --no-stream     # live memory/CPU per container
free -h                      # box memory + swap
```

**When to grow:** if `docker stats` shows web pinned at its mem_limit or CPU
sustained >80%, bump the instance to `t4g.large` (8 GB) and raise
`pm.max_children` + `innodb_buffer_pool_size`. One box scales a long way for
this workload; only split MySQL onto its own box if the DB becomes the bottleneck.
