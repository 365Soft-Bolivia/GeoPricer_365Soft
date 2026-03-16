#!/bin/bash
set -e

# ─────────────────────────────────────────────────────────────────────────────
# Entrypoint de la aplicación Laravel (GeoPricer)
# Solo ejecuta la inicialización cuando arranca el contenedor principal (app),
# NO cuando arranca queue worker o scheduler.
# ─────────────────────────────────────────────────────────────────────────────

cd /var/www/html

# Si el proceso es php-fpm (contenedor principal), ejecutar inicialización
if [ "$1" = "php-fpm" ] && [ "${SKIP_INIT:-false}" != "true" ]; then

    echo "[entrypoint] Esperando a que MySQL esté disponible..."
    until php artisan db:monitor --databases=mysql 2>/dev/null || \
          mysqladmin ping -h "${DB_HOST:-mysql}" -u "${DB_USERNAME:-geopricer}" \
          -p"${DB_PASSWORD}" --silent 2>/dev/null; do
        echo "[entrypoint] MySQL no está listo, reintentando en 3s..."
        sleep 3
    done
    echo "[entrypoint] MySQL disponible."

    echo "[entrypoint] Ejecutando migraciones..."
    php artisan migrate --force --no-interaction

    echo "[entrypoint] Optimizando la aplicación..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache

    echo "[entrypoint] Inicialización completada."
fi

exec "$@"
