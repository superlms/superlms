#!/usr/bin/env bash
# Build/refresh the app image and (re)start the lite stack, then run migrations
# ONCE (not on every container — only here). Safe to re-run for every deploy:
#
#   cd /opt/superlms && git pull && cd infra/lite && ./deploy.sh
set -euo pipefail
cd "$(dirname "$0")"

COMPOSE="docker compose -f docker-compose.prod.yml --env-file .env"

if [ ! -f .env ]; then
  echo "ERROR: infra/lite/.env missing. cp .env.prod.example .env && edit it." >&2
  exit 1
fi

echo "==> Freeing disk before the build (old build cache + images no container uses)"
# Every deploy leaves a build's cache and the previous image behind, and the box
# once ran out of disk mid-build. Images of running containers and all volumes
# (MySQL, Redis, storage) are kept.
docker builder prune -af >/dev/null 2>&1 || true
docker image prune -af >/dev/null 2>&1 || true
df -h / | tail -1

echo "==> Building image + starting data services"
$COMPOSE build
$COMPOSE up -d mysql redis
echo "==> Waiting for MySQL to be healthy..."
until [ "$(docker inspect -f '{{.State.Health.Status}}' superlms-mysql 2>/dev/null || echo starting)" = "healthy" ]; do
  sleep 3; echo -n "."
done
echo " ok"

echo "==> Running migrations (schema + lms:migrate) as a one-off"
$COMPOSE run --rm --no-deps web sh -c \
  "php artisan migrate --force --no-interaction && php artisan lms:migrate --no-interaction"

echo "==> Starting web + worker + scheduler + caddy"
$COMPOSE up -d

echo "==> Caddy on the Caddyfile in the repo"
# Caddy reads its Caddyfile only when it starts or is told to reload, and
# `up -d` leaves a running Caddy alone — so a changed Caddyfile never took
# effect. Check the file first (a file that does not validate must never take
# the site down), then reload Caddy on it: graceful, no dropped connections,
# and nothing at all when the file has not changed. The file is mounted on its
# own; should the container still see an older copy of it, recreate Caddy
# instead (its certificates live in the caddy-data volume and stay).
if $COMPOSE run --rm --no-deps -T caddy caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile >/dev/null 2>&1; then
  want=$(sha256sum Caddyfile | cut -d' ' -f1)
  have=$(docker exec superlms-caddy sha256sum /etc/caddy/Caddyfile 2>/dev/null | cut -d' ' -f1 || true)
  if [ "$want" = "$have" ]; then
    docker exec superlms-caddy caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile
    echo "    Caddy reloaded."
  else
    $COMPOSE up -d --no-deps --force-recreate caddy
    echo "    Caddy recreated on the new Caddyfile."
  fi
else
  echo "WARNING: the Caddyfile does not validate — Caddy keeps running the config it has." >&2
fi

echo "==> Pruning old dangling images"
docker image prune -f >/dev/null 2>&1 || true

echo
echo "Done. Status:"
$COMPOSE ps
echo
echo "Health check:  curl -sfk https://\${APP_DOMAIN:-superlms.in}/up"
