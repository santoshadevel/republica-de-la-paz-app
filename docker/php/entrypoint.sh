#!/bin/sh
set -e

cd /var/www/html

# Instalar dependencias si aún no existen (montaje limpio del volumen)
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo "==> Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generar APP_KEY si falta
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "==> Generando APP_KEY..."
    php artisan key:generate --force
fi

# Esperar a que MySQL acepte conexiones
if [ -n "$DB_HOST" ]; then
    echo "==> Esperando a la base de datos ($DB_HOST:${DB_PORT:-3306})..."
    until php -r "exit(@fsockopen(getenv('DB_HOST'), (int)(getenv('DB_PORT') ?: 3306)) ? 0 : 1);" 2>/dev/null; do
        sleep 2
    done
    echo "==> Base de datos disponible."

    echo "==> Ejecutando migraciones..."
    php artisan migrate --force --graceful
fi

# Asegurar permisos de storage y cache
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache
chmod -R ug+rw storage bootstrap/cache || true

exec "$@"
