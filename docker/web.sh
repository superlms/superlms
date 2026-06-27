#!/usr/bin/env bash
# WEB role launcher: php-fpm in the background + nginx in the foreground.
# Both inherit this shell's stdout/stderr, so their logs go straight to the
# container output (and CloudWatch) - no supervisord pipe in the way.
set -e

# php-fpm: foreground mode but backgrounded here so it inherits our stdio;
# it logs (access + errors) to fd 2 per docker/php/www.conf.
php-fpm -F &
FPM_PID=$!

# If php-fpm dies, take the container down so ECS replaces it.
trap 'kill -TERM "$FPM_PID" 2>/dev/null' TERM INT

# nginx as the foreground process; logs to /dev/stdout + /dev/stderr.
exec nginx -g 'daemon off;'
