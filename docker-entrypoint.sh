#!/bin/sh
set -e

# (facultatif) créer les répertoires runtime
mkdir -p /run/nginx /var/log/nginx

# Démarre php-fpm en avant-plan (foreground) dans le fond
/usr/local/sbin/php-fpm -F &

# Vérifie qu'il écoute bien (optionnel, debug)
# sleep 0.5; ss -ltnp | grep ':9000' || echo "⚠️ php-fpm ne semble pas écouter sur 9000"

# Démarre nginx en avant-plan
exec /usr/sbin/nginx -g "daemon off;"
