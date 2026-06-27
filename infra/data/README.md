# Phase 14 - Data migration (old EC2 -> RDS + S3)

Move the live MySQL data and uploaded files from the old EC2 box to the new
RDS + S3. Do this during a short maintenance window (put old site read-only or
accept that writes after the dump won't carry over).

## 1. Dump the old database (on the old EC2, over SSH)

The old DB runs in the `mysql` docker compose service:

```bash
cd <old-project-path>      # where docker-compose.yml lives
# DB name + password are in the old .env (DB_DATABASE, DB_PASSWORD/DB_ROOT_PASSWORD)
docker compose exec -T mysql \
  mysqldump -u root -p"<DB_ROOT_PASSWORD>" \
  --single-transaction --quick --routines --triggers \
  <DB_DATABASE> | gzip > superlms-dump.sql.gz
```
Download it to your PC (from PowerShell):
```powershell
scp -i <key.pem> <user>@<ec2-host>:<path>/superlms-dump.sql.gz .
```

## 2. Import into RDS

RDS is private. Pick ONE:

### Option A - quick (temporarily expose RDS to your IP)
1. RDS console -> `superlms-mysql` -> Modify -> **Publicly accessible: Yes** -> apply now.
2. EC2 console -> Security Groups -> `superlms-rds-sg` -> add inbound MySQL/3306
   from **your IP/32**.
3. Get the DB password from Secrets Manager:
   ```powershell
   aws secretsmanager get-secret-value --secret-id superlms/rds --region ap-south-1 --query SecretString --output text
   ```
4. Import (needs the mysql client locally; or use MySQL Workbench):
   ```powershell
   # gunzip first (7-Zip) then:
   mysql -h superlms-mysql.cb0gug82k78s.ap-south-1.rds.amazonaws.com -u superlms -p superlms < superlms-dump.sql
   ```
5. **Revert:** RDS Publicly accessible -> No; remove the SG inbound rule.

### Option B - safe (bastion, no public RDS)
Launch a t3.micro in a public subnet of `superlms-vpc` (SG allowing your SSH),
add it to `superlms-rds-sg`, `scp` the dump there, `mysql` import to RDS over the
private network, then terminate the bastion.

> Note: APP_KEY changed (new key was generated). If the old DB has **encrypted
> columns**, set the old APP_KEY in `superlms/app` BEFORE the app reads them,
> or re-encrypt. Bcrypt password hashes are fine (not APP_KEY-dependent).

## 3. Sync uploaded files (old S3 -> new bucket)

The old app already used S3. Copy objects to the new bucket:
```powershell
aws s3 sync s3://<OLD_BUCKET> s3://superlms-media-540361297670 --region ap-south-1
```
(If old files were on the EC2 disk instead of S3, `aws s3 cp --recursive <local> s3://superlms-media-540361297670`.)

## 4. Verify

```powershell
# row counts sanity (via the app's migrate task host or temp public RDS)
# spot-check: log in as a known user, open dashboard, view an uploaded file.
```
- Login works (existing users)
- Dashboards show real data
- An old uploaded file opens (served via CloudFront)
- Don't run `migrate:fresh`/seeders against prod data!

## 5. Cutover

Keep the old EC2 running read-only for a few days as a fallback. Once verified,
decommission it. (CI/CD's old EC2 ssh-deploy is already replaced by Phase 13.)
