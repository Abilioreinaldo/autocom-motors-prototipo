#!/usr/bin/env bash
set -e
cd /var/www/html

# Espera o MySQL responder (até ~60s)
if [ -n "${DB_HOST:-}" ]; then
  for i in $(seq 1 30); do
    if php -r 'try{new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT")?:3306),getenv("DB_USERNAME"),getenv("DB_PASSWORD"));exit(0);}catch(Exception $e){exit(1);}' 2>/dev/null; then break; fi
    echo "[entrypoint] aguardando MySQL ($i/30)…"; sleep 2
  done
fi

if [ "${AUTOCOM_ROLE:-app}" = "app" ]; then
  php artisan storage:link --force >/dev/null 2>&1 || true
  if [ "${AUTOCOM_AUTO_MIGRATE:-true}" = "true" ]; then
    php artisan migrate --force
  fi
  php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
  php artisan filament:cache-components >/dev/null 2>&1 || true
fi

exec "$@"
