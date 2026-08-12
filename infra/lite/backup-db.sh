#!/usr/bin/env bash
# Daily MySQL backup -> gzip -> S3. Install as a cron job (see below).
# Reads DB creds + BACKUP_S3_BUCKET/PREFIX from infra/lite/.env.
#
# Install (on the box):
#   ( crontab -l 2>/dev/null; echo "30 20 * * * /opt/superlms/infra/lite/backup-db.sh >> /var/log/superlms-backup.log 2>&1" ) | crontab -
#   # 20:30 UTC = 02:00 IST daily
set -euo pipefail
cd "$(dirname "$0")"

set -a; . ./.env; set +a

TS="$(date +%Y%m%d-%H%M%S)"
FILE="superlms-${TS}.sql.gz"
TMP="/tmp/${FILE}"

echo "[$(date -Is)] dumping ${DB_DATABASE}..."
docker exec superlms-mysql sh -c \
  "exec mysqldump -u root -p\"${DB_ROOT_PASSWORD}\" --single-transaction --quick --routines --triggers ${DB_DATABASE}" \
  | gzip > "${TMP}"

SIZE="$(du -h "${TMP}" | cut -f1)"
echo "[$(date -Is)] dump ${SIZE}"

if [ -n "${BACKUP_S3_BUCKET:-}" ]; then
  DEST="s3://${BACKUP_S3_BUCKET}/${BACKUP_S3_PREFIX:-db-backups}/${FILE}"
  echo "[$(date -Is)] uploading -> ${DEST}"
  aws s3 cp "${TMP}" "${DEST}" --only-show-errors
  # Keep 30 days of backups; delete older ones.
  CUTOFF="$(date -d '30 days ago' +%Y%m%d 2>/dev/null || date -v-30d +%Y%m%d)"
  aws s3 ls "s3://${BACKUP_S3_BUCKET}/${BACKUP_S3_PREFIX:-db-backups}/" \
    | awk '{print $4}' | grep -E '^superlms-[0-9]{8}' | while read -r k; do
        d="$(echo "$k" | sed -E 's/^superlms-([0-9]{8}).*/\1/')"
        [ -n "$d" ] && [ "$d" -lt "$CUTOFF" ] && \
          aws s3 rm "s3://${BACKUP_S3_BUCKET}/${BACKUP_S3_PREFIX:-db-backups}/$k" --only-show-errors || true
      done
else
  echo "[$(date -Is)] BACKUP_S3_BUCKET unset — keeping local copy at /var/backups/${FILE}"
  sudo mkdir -p /var/backups && sudo mv "${TMP}" "/var/backups/${FILE}"
  exit 0
fi

rm -f "${TMP}"
echo "[$(date -Is)] done."
