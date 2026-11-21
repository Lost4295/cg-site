#!/bin/bash
# Lance php-fpm et nginx ; si l'un des deux s'arrête, le conteneur s'arrête
# et Kubernetes le redémarre.
set -euo pipefail

php-fpm -F &
nginx -g 'daemon off;' &

wait -n
exit $?
