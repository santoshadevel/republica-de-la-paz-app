#!/bin/sh
set -e

cd /var/www/html

# APP_ROLE=web (default) -> migra, cachea y publica los assets, y sirve php-fpm.
# APP_ROLE=scheduler     -> sólo espera la DB y corre `schedule:work`.
# El scheduler NO migra ni cachea a propósito: si ambos contenedores lo hicieran,
# competirían por las migraciones en cada deploy.
APP_ROLE="${APP_ROLE:-web}"

echo "==> Rol del contenedor: $APP_ROLE"

echo "==> Preparando storage y bootstrap/cache..."
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# Esperar a que el cluster MySQL administrado acepte conexiones (máx ~60s)
if [ -n "$DB_HOST" ]; then
    echo "==> Esperando la base de datos ($DB_HOST:${DB_PORT:-3306})..."
    i=0
    until php -r "exit(@fsockopen(getenv('DB_HOST'), (int)(getenv('DB_PORT') ?: 3306)) ? 0 : 1);" 2>/dev/null; do
        i=$((i + 1))
        if [ "$i" -ge 30 ]; then
            echo "!! La base de datos no respondió tras 60s; continúo igual." >&2
            break
        fi
        sleep 2
    done
fi

if [ "$APP_ROLE" = "web" ]; then
    echo "==> Enlazando storage público..."
    php artisan storage:link 2>/dev/null || true

    echo "==> Ejecutando migraciones..."
    php artisan migrate --force

    echo "==> Cacheando configuración, rutas y vistas..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Publicar public/ (con los assets ya compilados) al volumen que sirve Caddy.
    # Se hace en cada arranque para que cada deploy refresque los assets.
    if [ -d /webroot ]; then
        echo "==> Publicando assets al web root compartido..."
        rsync -a --delete public/ /webroot/
    fi
fi

echo "==> Listo. Arrancando: $*"
exec "$@"
